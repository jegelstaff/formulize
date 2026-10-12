// Popover pickers for the checkbox and date range filters in a list's column search cells.
//
// buildFilter (with $multi) and formulize_buildDateRangeFilter render these filters as controls that sit in the
// cell, which is what a page gets without this script. This script swaps each one in a column search cell for a
// button that shows the search in effect, and moves the filter's own controls into a panel that the button opens.
// The hidden fields that carry the search values, and the handlers that fill them in, are left alone, so the
// panel's Apply submits exactly what the in-cell Apply would have. The panel only drafts a search: the button keeps
// showing the one in effect until Apply, and Cancel (or Escape, or clicking elsewhere) puts the draft back.
//
// The hidden fields stay in the cell, inside the list's form. Everything else moves to a panel on the body, placed
// with position: fixed, so the table's scrolling and the theme's stacking can't clip it.
//
// Webmasters get a "Type a search term" button in the panel, which swaps the popover for an ordinary search box (the
// hidden field itself, made visible), so they can type terms the panel can't make, like {TODAY-7}. That choice is
// remembered per screen and column in localStorage, since the next page load would otherwise bring the popover back,
// and a button beside the box returns to the popover.

// ---------------------------------------------------------------------------
// UI text. PHP sets window.formulizeFilterPopoverLang with the localized strings (see interfaceJavascript in
// entriesdisplay.php). The defaults below keep the component working if the script loads on its own.
// ---------------------------------------------------------------------------
window.formulizeFilterPopoverLang = window.formulizeFilterPopoverLang || {
	anyDate: 'Any date',
	selected: '%s selected',
	find: 'Find',
	noMatches: 'Nothing matches',
	apply: 'Apply',
	clear: 'Clear',
	cancel: 'Cancel',
	fromDate: 'From %s',
	untilDate: 'Until %s',
	from: 'From:',
	to: 'To:',
	today: 'Today',
	lastMonday: 'Last Monday',
	firstOfMonth: 'First of this month',
	daysAgo: '%s days ago',
	jan1: 'Jan 1 this year',
	nextFriday: 'Next Friday',
	lastOfMonth: 'Last day of this month',
	daysFromNow: '%s days from now',
	dec31: 'Dec 31 this year',
	numberOfDays: 'Number of days',
	typeSearch: 'Type a search term',
	pickInstead: 'Pick instead of typing'
};

// typeSearch is whether this user may swap a popover for a typed search box (webmasters only); screen names the
// screen (or form) the choice is remembered for. Both come from interfaceJavascript too.
window.formulizeFilterPopoverSettings = window.formulizeFilterPopoverSettings || { typeSearch: false, screen: '' };

(function () {

	var lang = window.formulizeFilterPopoverLang;
	var settings = window.formulizeFilterPopoverSettings;
	var FIND_THRESHOLD = 12; // a list with more options than this gets a find box
	var openPicker = null;
	var panelCounter = 0;

	var caretIcon = '<svg class="formulize-filter-trigger-caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
	var pickerIcon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="m9 11 3 3 3-3"/></svg>';

	function make(tag, className, text) {
		var node = document.createElement(tag);
		if(className) { node.className = className; }
		if(text) { node.textContent = text; }
		return node;
	}

	function makeButton(className, text) {
		var button = make('button', className, text);
		button.type = 'button';
		return button;
	}

	// the column heading over a search cell, to name the panel for screen readers
	function columnName(cell) {
		var heading = document.getElementById(cell.id.replace('celladdress_1_', 'celladdress_h1_'));
		return heading ? heading.textContent.replace(/\s+/g, ' ').trim() : '';
	}

	// --- dates ---------------------------------------------------------------

	function iso(date) {
		var month = String(date.getMonth() + 1);
		var day = String(date.getDate());
		return date.getFullYear() + '-' + (month.length < 2 ? '0' + month : month) + '-' + (day.length < 2 ? '0' + day : day);
	}

	function today() {
		var date = new Date();
		date.setHours(12, 0, 0, 0); // midday, so adding days never trips over a daylight saving change
		return date;
	}

	function isoDaysFromToday(days) {
		var date = today();
		date.setDate(date.getDate() + days);
		return iso(date);
	}

	// the most recent weekday before today (0 is Sunday), so on a Monday "last Monday" is a week ago
	function isoLastWeekday(weekday) {
		var date = today();
		date.setDate(date.getDate() - ((date.getDay() - weekday + 7) % 7 || 7));
		return iso(date);
	}

	// the first weekday after today, so on a Friday "next Friday" is a week away
	function isoNextWeekday(weekday) {
		var date = today();
		date.setDate(date.getDate() + ((weekday - date.getDay() + 7) % 7 || 7));
		return iso(date);
	}

	function isoMonthStart() { var date = today(); return iso(new Date(date.getFullYear(), date.getMonth(), 1)); }
	function isoMonthEnd() { var date = today(); return iso(new Date(date.getFullYear(), date.getMonth() + 1, 0)); }
	function isoYearStart() { return iso(new Date(today().getFullYear(), 0, 1)); }
	function isoYearEnd() { return iso(new Date(today().getFullYear(), 11, 31)); }

	// a term in a date search is either a date, or {TODAY} give or take some days, which the search resolves itself
	function resolveTerm(term) {
		var relative = /^\{TODAY([+-]\d+)?\}$/.exec(term);
		return relative ? isoDaysFromToday(relative[1] ? parseInt(relative[1], 10) : 0) : term;
	}

	function readableDate(iso) {
		var parts = iso.split('-');
		if(parts.length != 3) { return iso; }
		var date = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
		var options = { month: 'short', day: 'numeric' };
		if(date.getFullYear() != new Date().getFullYear()) { options.year = 'numeric'; }
		return date.toLocaleDateString(undefined, options);
	}

	// --- the shared shell: a trigger button in the cell, a panel on the body ---

	// hidden is the field carrying the filter's search value; makePicker builds this picker again, for coming back
	// from a typed search
	function Picker(cell, filter, hidden, makePicker) {
		var picker = this;
		picker.cell = cell;
		picker.filter = filter;
		picker.hidden = hidden;
		picker.makePicker = makePicker;
		picker.minWidth = 288; // px; the date range panel needs more, for two dates and two columns of shortcuts
		picker.ownApply = filter.querySelector('.formulize-filter-apply');

		picker.trigger = makeButton('formulize-filter-trigger');
		picker.trigger.setAttribute('aria-haspopup', 'dialog');
		picker.trigger.setAttribute('aria-expanded', 'false');
		picker.triggerText = make('span', 'formulize-filter-trigger-text');

		picker.panel = make('div', 'formulize-filter-panel');
		picker.panel.id = 'formulize-filter-panel-' + (++panelCounter);
		picker.panel.setAttribute('role', 'dialog');
		picker.panel.setAttribute('aria-label', columnName(cell));
		picker.panel.hidden = true;
		picker.trigger.setAttribute('aria-controls', picker.panel.id);
		picker.top = make('div', 'formulize-filter-panel-top');
		picker.body = make('div', 'formulize-filter-panel-body');
		var foot = make('div', 'formulize-filter-panel-foot');
		var cancelButton = makeButton('fz-btn fz-btn--sm formulize-filter-cancel', lang.cancel);
		var applyButton = makeButton('fz-btn fz-btn--sm fz-btn--primary formulize-filter-panel-apply', lang.apply);
		if(settings.typeSearch) {
			var typeButton = makeButton('fz-btn fz-btn--sm fz-btn--ghost formulize-filter-type-search', lang.typeSearch);
			typeButton.addEventListener('click', function () { picker.toTypedSearch(); });
			foot.appendChild(typeButton);
		}
		foot.appendChild(make('span', 'formulize-filter-spacer'));
		foot.appendChild(cancelButton);
		foot.appendChild(applyButton);
		picker.panel.appendChild(picker.top);
		picker.panel.appendChild(picker.body);
		picker.panel.appendChild(foot);

		filter.parentNode.insertBefore(picker.trigger, filter);
		document.body.appendChild(picker.panel);

		picker.trigger.addEventListener('click', function () {
			if(openPicker === picker) { picker.close(true); } else { picker.open(); }
		});
		cancelButton.addEventListener('click', function () { picker.close(true); });
		applyButton.addEventListener('click', function () { picker.apply(); });
	}

	Picker.prototype.open = function () {
		if(openPicker) { openPicker.close(true); }
		openPicker = this;
		this.snapshot = this.takeSnapshot();
		this.beforeOpen();
		this.panel.hidden = false;
		this.trigger.setAttribute('aria-expanded', 'true');
		this.place();
		var first = this.firstFocus();
		if(first) { first.focus(); }
	};

	// restore puts the filter back the way it was when the panel opened, which is what Cancel, Escape and
	// clicking elsewhere all mean
	Picker.prototype.close = function (restore) {
		if(restore && this.snapshot) { this.restoreSnapshot(this.snapshot); }
		this.panel.hidden = true;
		this.trigger.setAttribute('aria-expanded', 'false');
		if(openPicker === this) { openPicker = null; }
		this.updateTrigger();
	};

	Picker.prototype.apply = function () {
		if(this.hidden.value === this.snapshot.hidden) {
			this.close(false); // nothing changed, so there's nothing to reload for
			return;
		}
		this.panel.querySelectorAll('button').forEach(function (button) { button.disabled = true; });
		this.ownApply.click(); // the filter's own Apply, hidden in the panel, submits the list as it always has
	};

	// anything drafted in the panel is dropped, so the box starts with the search in effect
	Picker.prototype.toTypedSearch = function () {
		this.close(true);
		this.hidden.parentNode.insertBefore(this.filter, this.hidden.nextSibling); // out of the panel, back beside its hidden field in the cell
		this.trigger.remove();
		this.panel.remove();
		rememberTypedSearch(this.hidden, true);
		typedSearch(this.filter, this.hidden, this.makePicker).focus();
	};

	// --- typed search, for webmasters --------------------------------------------

	function typedSearchKey(hidden) {
		return 'formulize-typed-search:' + settings.screen + ':' + hidden.name;
	}

	// only webmasters can choose it, so only they get it back; anyone else on the same browser gets the popover
	function wantsTypedSearch(hidden) {
		if(!settings.typeSearch) { return false; }
		try { return localStorage.getItem(typedSearchKey(hidden)) === '1'; } catch (e) { return false; }
	}

	function rememberTypedSearch(hidden, typed) {
		try {
			if(typed) { localStorage.setItem(typedSearchKey(hidden), '1'); } else { localStorage.removeItem(typedSearchKey(hidden)); }
		} catch (e) { /* without storage the choice lasts until the page reloads */ }
	}

	// The hidden field becomes the search box, so it keeps its name and the search in effect, and submits with the
	// list's form like the other column search boxes. The filter's own controls come out of the page meanwhile, and
	// go back in for the picker if the button beside the box is used. Returns the box.
	function typedSearch(filter, hidden, makePicker) {
		filter.parentNode.insertBefore(hidden, filter);
		filter.remove();
		var row = make('div', 'formulize-filter-typed');
		hidden.parentNode.insertBefore(row, hidden);
		row.appendChild(hidden);
		hidden.type = 'text';
		hidden.setAttribute('enterkeyhint', 'search');
		hidden.formulizeTypedChange = hidden.formulizeTypedChange || function () {
			if(window.document.controls && window.document.controls.ventry) { window.document.controls.ventry.value = ''; } // as the other search boxes do
		};
		hidden.addEventListener('change', hidden.formulizeTypedChange);

		var back = makeButton('fz-btn fz-btn--sm fz-btn--ghost fz-btn--icon formulize-filter-typed-back');
		back.innerHTML = pickerIcon;
		back.title = lang.pickInstead;
		back.setAttribute('aria-label', lang.pickInstead);
		back.addEventListener('click', function () {
			rememberTypedSearch(hidden, false);
			hidden.removeEventListener('change', hidden.formulizeTypedChange);
			hidden.type = 'hidden';
			row.parentNode.insertBefore(hidden, row);
			row.parentNode.insertBefore(filter, row);
			row.remove();
			makePicker().trigger.focus();
		});
		row.appendChild(back);
		return hidden;
	}

	Picker.prototype.place = function () {
		var rect = this.trigger.getBoundingClientRect();
		var viewWidth = document.documentElement.clientWidth;
		var viewHeight = window.innerHeight;
		var width = Math.min(Math.max(rect.width, this.minWidth), viewWidth - 16);
		var spaceBelow = viewHeight - rect.bottom - 12;
		var spaceAbove = rect.top - 12;
		var above = spaceBelow < 280 && spaceAbove > spaceBelow;
		this.panel.style.width = width + 'px';
		this.panel.style.left = Math.min(Math.max(8, rect.left), viewWidth - width - 8) + 'px';
		this.panel.style.maxHeight = Math.max(160, Math.min(above ? spaceAbove : spaceBelow, 460)) + 'px';
		this.panel.style.top = above ? '' : (rect.bottom + 6) + 'px';
		this.panel.style.bottom = above ? (viewHeight - rect.top + 6) + 'px' : '';
	};

	// more is how many other choices there are beyond the one named, shown as a "+2" badge
	Picker.prototype.setTrigger = function (text, isDefault, more, title) {
		this.triggerText.textContent = text;
		this.trigger.classList.toggle('formulize-filter-trigger-default', isDefault);
		this.trigger.title = title || text;
		var badge = this.trigger.querySelector('.formulize-filter-trigger-more');
		if(badge) { badge.remove(); }
		if(more > 0) {
			badge = make('span', 'fz-badge fz-badge--plain fz-badge--accent formulize-filter-trigger-more', '+' + more);
			badge.title = lang.selected.replace('%s', more + 1);
			this.triggerText.after(badge);
		}
	};

	// --- checkboxes ------------------------------------------------------------

	function CheckboxPicker(cell, filter, hidden, makePicker) {
		Picker.call(this, cell, filter, hidden, makePicker);
		var picker = this;
		picker.list = filter.querySelector('.formulize-multifilter-options');
		picker.allOption = filter.querySelector('.formulize-multifilter-option-all');
		picker.options = Array.prototype.filter.call(filter.querySelectorAll('.formulize-multifilter-option'), function (option) {
			return option !== picker.allOption;
		});
		// the order they came in, which survives the panel moving ticked ones up, and the picker being built again
		picker.options.forEach(function (option, index) {
			if(option.formulizeOrder === undefined) { option.formulizeOrder = index; }
		});

		picker.trigger.appendChild(picker.triggerText);
		picker.trigger.insertAdjacentHTML('beforeend', caretIcon);
		picker.body.appendChild(filter);

		if(picker.options.length > FIND_THRESHOLD) {
			picker.find = make('input', 'fz-input formulize-filter-find');
			picker.find.type = 'search';
			picker.find.placeholder = lang.find;
			picker.find.setAttribute('aria-label', lang.find + (picker.panel.getAttribute('aria-label') ? ' ' + picker.panel.getAttribute('aria-label') : ''));
			picker.find.autocomplete = 'off';
			picker.top.appendChild(picker.find);
			picker.noMatches = make('p', 'formulize-filter-no-matches', lang.noMatches);
			picker.noMatches.hidden = true;
			picker.body.appendChild(picker.noMatches);
			picker.find.addEventListener('input', function () { picker.applyFind(); });
			picker.find.addEventListener('keydown', function (event) {
				if(event.key === 'Enter') { event.preventDefault(); } // the list's form would submit
			});
		} else {
			picker.top.hidden = true;
		}

		picker.updateTrigger();
	}
	CheckboxPicker.prototype = Object.create(Picker.prototype);

	CheckboxPicker.prototype.checkedOptions = function () {
		return this.options.filter(function (option) { return option.querySelector('input').checked; });
	};

	CheckboxPicker.prototype.updateTrigger = function () {
		var names = this.checkedOptions().map(function (option) { return option.textContent.trim(); });
		var value = this.hidden.value;
		if(names.length === 0 && value && value != 'none') {
			this.setTrigger(value, false, 0); // a typed search term that isn't one of the options
		} else if(names.length === 0) {
			this.setTrigger(this.allOption ? this.allOption.textContent.trim() : '', true, 0);
		} else {
			this.setTrigger(names[0], false, names.length - 1, names.join(', '));
		}
	};

	// the boxes themselves are kept, not their positions, since opening the panel moves the ticked ones up
	CheckboxPicker.prototype.takeSnapshot = function () {
		return {
			hidden: this.hidden.value,
			boxes: Array.prototype.map.call(this.filter.querySelectorAll('input[type="checkbox"]'), function (box) {
				return { box: box, checked: box.checked };
			})
		};
	};

	CheckboxPicker.prototype.restoreSnapshot = function (snapshot) {
		this.hidden.value = snapshot.hidden;
		snapshot.boxes.forEach(function (saved) { saved.box.checked = saved.checked; });
	};

	// ticked options move up under "Show all", so the current choice is in view when the panel opens
	CheckboxPicker.prototype.beforeOpen = function () {
		var picker = this;
		var ordered = picker.options.slice().sort(function (a, b) {
			var aChecked = a.querySelector('input').checked ? 0 : 1;
			var bChecked = b.querySelector('input').checked ? 0 : 1;
			return aChecked - bChecked || a.formulizeOrder - b.formulizeOrder;
		});
		ordered.forEach(function (option) { picker.list.appendChild(option); });
		if(picker.find) {
			picker.find.value = '';
			picker.applyFind();
		}
		picker.body.scrollTop = 0;
	};

	CheckboxPicker.prototype.applyFind = function () {
		var term = this.find.value.trim().toLowerCase();
		var shown = 0;
		this.options.forEach(function (option) {
			var match = !term || option.textContent.toLowerCase().indexOf(term) != -1;
			option.hidden = !match;
			if(match) { shown++; }
		});
		if(this.allOption) { this.allOption.hidden = !!term; }
		this.noMatches.hidden = shown > 0;
	};

	CheckboxPicker.prototype.firstFocus = function () {
		return this.find || this.filter.querySelector('input[type="checkbox"]');
	};

	// --- date ranges -------------------------------------------------------------

	// The shortcuts under the dates: the left column sets From, the right column sets To. Today and the counts of
	// days go into the search relative to today, like {TODAY-30}, so a saved or reloaded list keeps meaning
	// "30 days ago" rather than the date that was when it was set. The search has no relative form for the calendar
	// ones (last Monday, the first of the month and so on), so they go in as the date they fall on today. Clear
	// empties its side, leaving the search open-ended that way.
	var DEFAULT_DAYS = 30;
	var dateShortcuts = {
		from: [
			{ label: lang.today, term: '{TODAY}' },
			{ label: lang.lastMonday, date: function () { return isoLastWeekday(1); } },
			{ label: lang.firstOfMonth, date: isoMonthStart },
			{ count: lang.daysAgo, term: function (days) { return '{TODAY-' + days + '}'; }, pattern: /^\{TODAY-(\d+)\}$/ },
			{ label: lang.jan1, date: isoYearStart },
			{ label: lang.clear, clear: true }
		],
		to: [
			{ label: lang.today, term: '{TODAY}' },
			{ label: lang.nextFriday, date: function () { return isoNextWeekday(5); } },
			{ label: lang.lastOfMonth, date: isoMonthEnd },
			{ count: lang.daysFromNow, term: function (days) { return '{TODAY+' + days + '}'; }, pattern: /^\{TODAY\+(\d+)\}$/ },
			{ label: lang.dec31, date: isoYearEnd },
			{ label: lang.clear, clear: true }
		]
	};

	function DateRangePicker(cell, filter, hidden, makePicker) {
		Picker.call(this, cell, filter, hidden, makePicker);
		var picker = this;
		picker.minWidth = 360;
		picker.start = filter.querySelector('input[id^="formulize_daterange_sta_"]');
		picker.end = filter.querySelector('input[id^="formulize_daterange_end_"]');

		// the hidden field carries the search value, so it stays in the cell, inside the list's form
		filter.parentNode.insertBefore(picker.hidden, filter);
		picker.start.classList.add('fz-input');
		picker.end.classList.add('fz-input');

		picker.trigger.appendChild(picker.triggerText);
		picker.trigger.insertAdjacentHTML('beforeend', caretIcon);
		picker.top.hidden = true;
		picker.body.appendChild(filter);

		var columns = make('div', 'formulize-filter-shortcuts');
		picker.shortcuts = [];
		['from', 'to'].forEach(function (side) {
			var column = make('div', 'formulize-filter-shortcut-column');
			column.setAttribute('role', 'group');
			column.setAttribute('aria-label', String(lang[side]).replace(/[:\s]+$/, ''));
			dateShortcuts[side].forEach(function (shortcut) {
				var item = { side: side, shortcut: shortcut };
				if(shortcut.count) {
					// a label styled as a button, holding the box for the number: clicking it, or typing a number, chooses it
					item.element = make('label', 'fz-btn fz-btn--sm fz-btn--ghost formulize-filter-shortcut formulize-filter-shortcut-count');
					var words = shortcut.count.split('%s');
					item.days = make('input', 'formulize-filter-days');
					item.days.type = 'number';
					item.days.min = '1';
					item.days.max = '3650';
					item.days.value = DEFAULT_DAYS;
					item.days.setAttribute('aria-label', lang.numberOfDays + ': ' + shortcut.count.replace('%s', '').replace(/\s+/g, ' ').trim());
					if(words[0].trim()) { item.element.appendChild(make('span', '', words[0].trim())); }
					item.element.appendChild(item.days);
					if(words[1] && words[1].trim()) { item.element.appendChild(make('span', '', words[1].trim())); }
					var chooseCount = function () {
						var count = parseInt(item.days.value, 10);
						if(count > 0) { picker.choose(side, shortcut.term(count)); }
					};
					item.element.addEventListener('click', chooseCount);
					item.days.addEventListener('input', chooseCount);
					item.days.addEventListener('keydown', function (event) {
						if(event.key === 'Enter') { event.preventDefault(); chooseCount(); } // the list's form would submit
					});
				} else {
					item.element = makeButton('fz-btn fz-btn--sm fz-btn--ghost formulize-filter-shortcut', shortcut.label);
					item.element.setAttribute('aria-pressed', 'false');
					item.element.addEventListener('click', function () {
						picker.choose(side, shortcut.clear ? '' : shortcut.term || shortcut.date());
					});
				}
				column.appendChild(item.element);
				picker.shortcuts.push(item);
			});
			columns.appendChild(column);
		});
		picker.body.appendChild(columns);

		// formulize_buildDateRangeFilter's handlers hear typed dates on the document: they write them into the hidden
		// field as they change, and put the two in order when a box is left. Listening on the window, which hears both
		// last, sees the dates they settled on.
		['change', 'focusout'].forEach(function (type) {
			window.addEventListener(type, function (event) {
				if(event.target === picker.start || event.target === picker.end) { picker.markShortcuts(); }
			});
		});
		picker.updateTrigger();
	}
	DateRangePicker.prototype = Object.create(Picker.prototype);

	// the hidden field holds >=from//<=to, or just one of the two
	DateRangePicker.prototype.terms = function () {
		var terms = { from: '', to: '' };
		this.hidden.value.split('//').forEach(function (part) {
			if(part.substr(0, 2) == '>=') { terms.from = part.substr(2); }
			if(part.substr(0, 2) == '<=') { terms.to = part.substr(2); }
		});
		return terms;
	};

	// the dates are filled in so people can see what the terms mean today. They're set directly, without a change
	// event, so formulize_buildDateRangeFilter's handlers don't swap relative terms for literal dates.
	DateRangePicker.prototype.setTerms = function (terms) {
		this.hidden.value = (terms.from ? '>=' + terms.from : '') + (terms.from && terms.to ? '//' : '') + (terms.to ? '<=' + terms.to : '');
		this.start.value = terms.from ? resolveTerm(terms.from) : '';
		this.end.value = terms.to ? resolveTerm(terms.to) : '';
	};

	// a shortcut sets its own side; if that crosses the other date, the other date comes along to the same day, the
	// same as when a date is typed (see formulize_buildDateRangeFilter)
	DateRangePicker.prototype.choose = function (side, term) {
		var terms = this.terms();
		terms[side] = term;
		if(terms.from && terms.to && resolveTerm(terms.from) > resolveTerm(terms.to)) {
			terms[side == 'from' ? 'to' : 'from'] = term;
		}
		this.setTerms(terms);
		this.markShortcuts();
	};

	// the date a shortcut stands for today, '' for Clear; a count of days uses the number in its box
	DateRangePicker.prototype.shortcutDate = function (item) {
		var shortcut = item.shortcut;
		if(shortcut.clear) { return ''; }
		if(item.days) { return resolveTerm(shortcut.term(parseInt(item.days.value, 10) || 0)); }
		return shortcut.term ? resolveTerm(shortcut.term) : shortcut.date();
	};

	// a shortcut is highlighted when the date in its side's box is the date it stands for, however that date got
	// there, so typing today's date lights up Today. On the 1st, Today and the first of the month both light up.
	DateRangePicker.prototype.markShortcuts = function () {
		var picker = this;
		this.shortcuts.forEach(function (item) {
			var date = item.side == 'from' ? picker.start.value : picker.end.value;
			var on = picker.shortcutDate(item) === date;
			item.element.classList.toggle('formulize-filter-shortcut-active', on);
			if(!item.days) { item.element.setAttribute('aria-pressed', String(on)); }
		});
	};

	DateRangePicker.prototype.updateTrigger = function () {
		var terms = this.terms();
		var start = this.start.value;
		var end = this.end.value;
		if(!this.hidden.value) {
			this.setTrigger(lang.anyDate, true, 0);
		} else if(terms.from == '{TODAY}' && terms.to == '{TODAY}') {
			this.setTrigger(lang.today, false, 0);
		} else if(start && end) {
			this.setTrigger(start === end ? readableDate(start) : readableDate(start) + ' – ' + readableDate(end), false, 0);
		} else if(start) {
			this.setTrigger(lang.fromDate.replace('%s', readableDate(start)), false, 0);
		} else if(end) {
			this.setTrigger(lang.untilDate.replace('%s', readableDate(end)), false, 0);
		} else {
			this.setTrigger(this.hidden.value, false, 0);
		}
	};

	DateRangePicker.prototype.takeSnapshot = function () {
		return { hidden: this.hidden.value, start: this.start.value, end: this.end.value };
	};

	DateRangePicker.prototype.restoreSnapshot = function (snapshot) {
		this.hidden.value = snapshot.hidden;
		this.start.value = snapshot.start;
		this.end.value = snapshot.end;
	};

	// with no date search in effect, both dates start at today, ready to Apply or adjust; the snapshot is already
	// taken, so Cancel still leaves no date search in effect
	DateRangePicker.prototype.beforeOpen = function () {
		if(!this.hidden.value) {
			this.setTerms({ from: '{TODAY}', to: '{TODAY}' });
		}
		// a count of days in effect, like {TODAY-14}, goes back in its box; otherwise the boxes start at the default
		var terms = this.terms();
		this.shortcuts.forEach(function (item) {
			if(!item.days) { return; }
			var count = item.shortcut.pattern.exec(terms[item.side]);
			item.days.value = count ? count[1] : DEFAULT_DAYS;
		});
		this.markShortcuts();
		this.body.scrollTop = 0;
	};

	DateRangePicker.prototype.firstFocus = function () {
		return this.start;
	};

	// --- wiring ------------------------------------------------------------------

	// a picker for the filter, or the typed search box if a webmaster chose that here last time
	function start(Kind, cell, filter, hidden) {
		var makePicker = function () { return new Kind(cell, filter, hidden, makePicker); };
		if(wantsTypedSearch(hidden)) {
			typedSearch(filter, hidden, makePicker);
		} else {
			makePicker();
		}
	}

	function init() {
		var cells = document.querySelectorAll('#listofentries td[id^="celladdress_1_"]');
		Array.prototype.forEach.call(cells, function (cell) {
			// buildFilter puts the checkboxes' hidden ORSET field just before them
			var checkboxes = cell.querySelector('.formulize-multifilter');
			if(checkboxes && checkboxes.previousElementSibling && checkboxes.previousElementSibling.type === 'hidden' && checkboxes.querySelector('.formulize-filter-apply')) {
				start(CheckboxPicker, cell, checkboxes, checkboxes.previousElementSibling);
			}
			var dates = cell.querySelector('.formulize-daterange');
			if(dates && dates.querySelector('.formulize-filter-apply') && dates.querySelector('input[type="hidden"]')) {
				start(DateRangePicker, cell, dates, dates.querySelector('input[type="hidden"]'));
			}
		});
		if(!cells.length) { return; }

		// a press anywhere outside the open panel and its button puts things back and closes it, like Cancel
		document.addEventListener('mousedown', function (event) {
			if(openPicker && !openPicker.panel.contains(event.target) && !openPicker.trigger.contains(event.target)) {
				openPicker.close(true);
			}
		}, true);
		document.addEventListener('keydown', function (event) {
			if(openPicker && event.key === 'Escape') {
				var trigger = openPicker.trigger;
				openPicker.close(true);
				trigger.focus();
			}
		});
		// keep the panel with its button as the page or the table scrolls, but not when the panel's own list scrolls
		document.addEventListener('scroll', function (event) {
			if(openPicker && !openPicker.panel.contains(event.target)) { openPicker.place(); }
		}, true);
		window.addEventListener('resize', function () {
			if(openPicker) { openPicker.place(); }
		});
	}

	if(document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})();
