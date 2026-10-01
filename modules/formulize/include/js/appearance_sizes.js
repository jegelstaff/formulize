// The advanced size editor (modules/formulize/appearance_sizes.php).
//
// The page shows one of the theme's sample screens in an iframe
// (appearance_preview.php) and sets every size token on it, as an inline style
// on its root element, so the preview always shows exactly the values being
// edited. Clicking a part of the preview (anything with data-fz-part) selects
// that part: every one of it is outlined, and the inspector shows the tokens
// that size it, which apply to every one of it on the site. With nothing
// selected, the inspector lists the sizes that have been changed.
//
// Everything about the sizes comes from include/appearance_tokens.json, passed
// in as formulizeAppearanceSizes.map: their types and limits, the parts of the
// interface and the tokens of each, what can't be adjusted yet, which token
// has a separate value on phones, and the floors that depend on other tokens.

(function () {
	'use strict';

	var DATA = window.formulizeAppearanceSizes;
	if (!DATA) { return; }
	var MAP = DATA.map;

	// the text steps' sizes at the default 16px root (formulize-ui.css)
	var TEXT_PX = { 'xs': 12, 'xs-plus': 13, 'sm': 14, 'sm-plus': 15, 'base': 16, 'lg': 18, 'xl': 20, '2xl': 24, '3xl': 30 };
	var ICON = {
		desktop: '<svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true"><rect x="2" y="3" width="16" height="11" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M7 17h6M10 14v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		phone: '<svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true"><rect x="5.5" y="2" width="9" height="16" rx="1.8" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M9 15.2h2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>'
	};
	// Heights a theme may keep larger than the token at some widths (Lyris keeps
	// fields 44px tall on phones, for tapping): the editor measures the first of
	// these in the preview and says so when it is taller than the setting.
	var MEASURED = {
		'--fz-field-height': '[data-fz-part="field"]:not(textarea):not(select[multiple])',
		'--fz-control-height': '[data-fz-part="button"]:not(.fz-btn--icon):not(.formulize-drawer__btn--icon)'
	};

	var state = {
		preset: DATA.preset || '',
		overrides: Array.isArray(DATA.overrides) ? {} : copy(DATA.overrides || {}),
		raised: {},
		screen: Object.keys(MAP.screens)[0],
		phone: false,
		sel: null,
		origin: null,
		dirty: false
	};
	var partsOn = {}; // screen => { part: true }, from the samples' markup

	var $ = function (id) { return document.getElementById(id); };
	var frame = $('formulize-sizes-frame'), head = $('formulize-sizes-insp-head'), body = $('formulize-sizes-insp-body');
	var hint = $('formulize-sizes-hint'), DEFAULT_HINT = hint.innerHTML;

	function copy(o) { return JSON.parse(JSON.stringify(o)); }
	function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
	function entry(token) { return MAP.tokens[token]; }
	function presetName(key) { return DATA.presets[key] || DATA.presets['']; }

	/* ---- values ---- */
	function presetValue(token) {
		var e = entry(token);
		return (state.preset !== '' && e.presets[state.preset] !== undefined) ? e.presets[state.preset] : e['default'];
	}
	function value(token) { return Object.prototype.hasOwnProperty.call(state.overrides, token) ? state.overrides[token] : presetValue(token); }
	function changed(token) { return Object.prototype.hasOwnProperty.call(state.overrides, token); }
	function css(token, v) {
		var t = entry(token).type;
		if (t === 'spacing') { return 'calc(var(--fz-spacing) * ' + v + ')'; }
		if (t === 'text') { return 'var(--fz-text-' + v + ')'; }
		if (t === 'measure') { return v + 'ch'; }
		return String(v);
	}
	function show(token, v) {
		var t = entry(token).type;
		if (t === 'spacing') { return (Math.round(v * 4 * 100) / 100) + 'px'; }
		if (t === 'text') { return TEXT_PX[v] + 'px'; }
		if (t === 'measure') { return v + ' characters'; }
		return String(v);
	}
	function lower(s) { return s.charAt(0).toLowerCase() + s.slice(1); }

	// the token's own limits, raised by its floor when another token's value calls for it
	function limits(token) {
		var e = entry(token), l = {};
		if (e.values) { l.values = e.values; return l; }
		l.min = e.min; l.max = e.max;
		var f = e.floor;
		if (f) {
			var min, why;
			if (f.text) {
				min = Math.ceil((TEXT_PX[value(f.text)] * f.leading + f.extra) / 4 * 2) / 2;
				why = 'the ' + lower(entry(f.text).label) + ' (' + show(f.text, value(f.text)) + ')';
			} else {
				min = value(f.token) + f.plus;
				why = 'the ' + lower(entry(f.token).label) + ' (' + show(f.token, value(f.token)) + ') and its padding';
			}
			if (min > l.min) { l.min = Math.min(min, l.max); l.why = why; }
		}
		return l;
	}
	// keep every value inside its limits; a value pushed under a floor by a change
	// elsewhere is raised, and the inspector says why
	function enforce() {
		state.raised = {};
		Object.keys(MAP.tokens).forEach(function (token) {
			var l = limits(token);
			if (l.min === undefined) { return; }
			if (value(token) < l.min) { state.overrides[token] = l.min; state.raised[token] = l.why; }
		});
		Object.keys(state.overrides).forEach(function (t) {
			if (state.overrides[t] === presetValue(t) && !state.raised[t]) { delete state.overrides[t]; }
		});
	}

	/* ---- parts ---- */
	var PHONE_OF = {}, DESKTOP_OF = {};
	Object.keys(MAP.tokens).forEach(function (t) { if (entry(t).phone) { PHONE_OF[t] = entry(t).phone; DESKTOP_OF[entry(t).phone] = t; } });
	function usedBy(token) {
		if (DESKTOP_OF[token]) { return usedBy(DESKTOP_OF[token]); }
		return Object.keys(MAP.components).filter(function (k) { return MAP.components[k].tokens.indexOf(token) !== -1; });
	}
	function home(token) {
		var t = DESKTOP_OF[token] || token;
		return entry(t).home || usedBy(t)[0];
	}

	/* ---- the preview ---- */
	function doc() { try { return frame.contentDocument; } catch (e) { return null; } }
	function frameUrl(screen) { return DATA.previewUrl + encodeURIComponent(screen); }

	var FRAME_CSS =
		'[data-fz-part] { cursor: pointer; }' +
		'[data-fz-part].formulize-sizes-hover { outline: 1px dashed #3b6fd8 !important; outline-offset: 2px; }' +
		'[data-fz-part].formulize-sizes-sel { outline: 2px solid #3b6fd8 !important; outline-offset: 2px; }' +
		'[data-fz-part].formulize-sizes-sel.formulize-sizes-origin { outline-width: 3px !important; }' +
		'@keyframes formulize-sizes-pulse { from { box-shadow: 0 0 0 0 rgba(59,111,216,.45); } to { box-shadow: 0 0 0 10px rgba(59,111,216,0); } }' +
		'[data-fz-part].formulize-sizes-pulse { animation: formulize-sizes-pulse .7s ease-out; }' +
		'@media (prefers-reduced-motion: reduce) { [data-fz-part].formulize-sizes-pulse { animation: none; } }';

	function applyTokens() {
		var d = doc();
		if (!d || !d.documentElement) { return; }
		Object.keys(MAP.tokens).forEach(function (token) { d.documentElement.style.setProperty(token, css(token, value(token))); });
	}
	function markSelection() {
		var d = doc(); if (!d) { return; }
		Array.prototype.forEach.call(d.querySelectorAll('[data-fz-part]'), function (el) {
			var on = el.getAttribute('data-fz-part') === state.sel;
			el.classList.toggle('formulize-sizes-sel', on);
			el.classList.toggle('formulize-sizes-origin', on && el === state.origin);
		});
	}
	function pulse() {
		var d = doc(); if (!d || !state.sel) { return; }
		Array.prototype.forEach.call(d.querySelectorAll('[data-fz-part="' + state.sel + '"]'), function (el) {
			el.classList.remove('formulize-sizes-pulse'); void el.offsetWidth; el.classList.add('formulize-sizes-pulse');
		});
	}
	frame.addEventListener('load', function () {
		var d = doc(); if (!d || !d.documentElement) { return; }
		var style = d.createElement('style'); style.textContent = FRAME_CSS; d.head.appendChild(style);
		applyTokens(); markSelection();
		// in the preview, every click selects: links don't go anywhere, buttons
		// don't press, fields don't take focus
		d.addEventListener('mousedown', function (ev) { ev.preventDefault(); }, true);
		d.addEventListener('submit', function (ev) { ev.preventDefault(); }, true);
		d.addEventListener('click', function (ev) {
			ev.preventDefault(); ev.stopPropagation();
			var toggle = ev.target.closest ? ev.target.closest('[data-fz-toggle]') : null;
			if (toggle) { var panel = d.getElementById(toggle.getAttribute('data-fz-toggle')); if (panel) { panel.classList.toggle('open'); } }
			var el = ev.target.closest ? ev.target.closest('[data-fz-part]') : null;
			select(el && MAP.components[el.getAttribute('data-fz-part')] ? el.getAttribute('data-fz-part') : null, el);
		}, true);
		d.addEventListener('mouseover', function (ev) {
			Array.prototype.forEach.call(d.querySelectorAll('.formulize-sizes-hover'), function (x) { x.classList.remove('formulize-sizes-hover'); });
			var el = ev.target.closest ? ev.target.closest('[data-fz-part]') : null;
			var c = el && MAP.components[el.getAttribute('data-fz-part')];
			if (c) { el.classList.add('formulize-sizes-hover'); hint.innerHTML = '<b>' + esc(c.name) + '</b> · click to change ' + esc(c.plural); }
			else { hint.innerHTML = DEFAULT_HINT; }
		});
		d.addEventListener('mouseleave', function () { hint.innerHTML = DEFAULT_HINT; });
		d.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') { select(null, null); } });
		renderInspector();
	});

	// which parts each sample screen has, for "used on these screens"
	Object.keys(MAP.screens).forEach(function (screen) {
		partsOn[screen] = {};
		fetch(frameUrl(screen), { credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (html) {
			var parsed = new DOMParser().parseFromString(html, 'text/html');
			Array.prototype.forEach.call(parsed.querySelectorAll('[data-fz-part]'), function (el) { partsOn[screen][el.getAttribute('data-fz-part')] = true; });
			if (state.sel) { renderInspector(); }
		})['catch'](function () {});
	});

	/* ---- the inspector ---- */
	function widgetFor(token, id) {
		var e = entry(token), l = limits(token), v = value(token);
		if (l.values) {
			return { widget: '<select id="' + id + '" data-token="' + token + '">' + l.values.map(function (x) {
				return '<option value="' + esc(x) + '"' + (String(x) === String(v) ? ' selected' : '') + '>' + esc(x) + (e.type === 'text' ? ' · ' + TEXT_PX[x] + 'px' : '') + '</option>';
			}).join('') + '</select>', range: '' };
		}
		var step = MAP.types[e.type].step;
		return {
			widget: '<button type="button" class="formulize-sizes__step" data-token="' + token + '" data-d="-1" aria-label="Smaller"' + (v <= l.min ? ' disabled' : '') + '>−</button>' +
				'<input type="range" id="' + id + '" data-token="' + token + '" min="' + l.min + '" max="' + l.max + '" step="' + step + '" value="' + v + '">' +
				'<button type="button" class="formulize-sizes__step" data-token="' + token + '" data-d="1" aria-label="Larger"' + (v >= l.max ? ' disabled' : '') + '>+</button>',
			range: '<div class="formulize-sizes__range"><span>' + show(token, l.min) + '</span><span>' + show(token, l.max) + '</span></div>'
		};
	}
	function valHtml(token) {
		var v = value(token);
		return '<b>' + show(token, v) + '</b><span class="formulize-sizes__from">' +
			(changed(token) ? 'changed · ' + esc(presetName(state.preset)) + ' is ' + show(token, presetValue(token)) : 'from ' + esc(presetName(state.preset))) + '</span>';
	}
	function measuredHeight(token) {
		var d = doc(), sel = MEASURED[token];
		if (!d || !sel) { return 0; }
		var el = d.querySelector(sel);
		return el ? Math.round(el.getBoundingClientRect().height) : 0;
	}
	function notesHtml(token) {
		var l = limits(token), v = value(token), notes = [];
		if (state.raised[token]) { notes.push(['', 'Raised to ' + show(token, v) + ' so it fits ' + l.why + '.']); }
		else if (l.why && v === l.min) { notes.push(['', 'This is as small as it goes with ' + l.why + '.']); }
		var h = measuredHeight(token);
		if (h && h > v * 4 + 1) {
			notes.push([state.phone ? 'phone' : '', 'Shown at ' + h + 'px here: the theme keeps it at least that tall at this width.']);
		}
		return notes.map(function (n) {
			return '<p class="formulize-sizes__note">' + (n[0] ? ICON[n[0]] : '') + '<span>' + esc(n[1]) + '</span></p>';
		}).join('');
	}
	function control(token) {
		var e = entry(token), id = 'formulize-sizes-' + token.replace(/[^a-z0-9]/g, '');
		var phone = PHONE_OF[token];
		var resets = [token, phone].filter(function (t) { return t && changed(t); });
		var others = usedBy(token).filter(function (k) { return k !== state.sel; }).map(function (k) { return MAP.components[k].name; });
		var inner, w;
		if (phone) {
			var row = function (t, dev, label) {
				var rw = widgetFor(t, 'formulize-sizes-' + t.replace(/[^a-z0-9]/g, ''));
				var active = (dev === 'phone') === state.phone;
				return '<div class="formulize-sizes__dev' + (active ? ' is-active' : '') + '"><span class="formulize-sizes__dev-icon" title="' + label + '">' + ICON[dev] + '</span><span class="formulize-sizes__dev-name">' + label + '</span>' +
					'<div class="formulize-sizes__widget">' + rw.widget + '</div><div class="formulize-sizes__val">' + valHtml(t) + '</div>' + notesHtml(t) + '</div>';
			};
			inner = '<div class="formulize-sizes__devs">' + row(token, 'desktop', 'Desktop') + row(phone, 'phone', 'Phones (768px and below)') + '</div>';
		} else {
			w = widgetFor(token, id);
			inner = '<div class="formulize-sizes__widget">' + w.widget + '</div>' + w.range + '<div class="formulize-sizes__val">' + valHtml(token) + '</div>' + notesHtml(token);
		}
		return '<div class="formulize-sizes__ctl' + (resets.length ? ' is-changed' : '') + '">' +
			'<div class="formulize-sizes__ctl-head"><label for="' + id + '">' + esc(e.label) + '</label>' + (resets.length ? '<button type="button" class="formulize-sizes__link" data-reset="' + resets.join(' ') + '">Reset</button>' : '') + '</div>' +
			'<p class="formulize-sizes__desc">' + esc(phone ? e.description.replace(/\.$/, '') + ', on desktop and on phones.' : e.description) + '</p>' + inner +
			(others.length ? '<p class="formulize-sizes__shared">Also changes ' + esc(others.join(', ')) + '.</p>' : '') +
		'</div>';
	}
	function gapsBlock(list) {
		if (!list || !list.length) { return ''; }
		return '<details class="formulize-sizes__gaps"><summary>Not adjustable yet</summary><ul>' + list.map(function (g) { return '<li>' + esc(g) + '</li>'; }).join('') + '</ul></details>';
	}
	function renderInspector() {
		if (state.sel) {
			var c = MAP.components[state.sel];
			var where = Object.keys(MAP.screens).filter(function (s) { return partsOn[s] && partsOn[s][state.sel]; }).map(function (s) {
				return '<button type="button" data-goto="' + s + '"' + (s === state.screen ? ' aria-current="true"' : '') + '>' + esc(MAP.screens[s]) + '</button>';
			}).join('');
			head.innerHTML = '<div class="formulize-sizes__kicker">Selected</div><div class="formulize-sizes__title"><h2>' + esc(c.name) + '</h2><button type="button" class="formulize-sizes__x" data-deselect="1" aria-label="Deselect">✕</button></div>' +
				'<div class="formulize-sizes__scope"><span>Changes apply to <strong>' + esc(c.plural) + '</strong> on the site, not just the one you clicked.' + (where ? ' Used on these screens, outlined in each:' : '') + '</span>' + (where ? '<div class="formulize-sizes__where">' + where + '</div>' : '') + '</div>';
			body.innerHTML = c.tokens.map(control).join('') + gapsBlock(c.gaps);
		} else {
			var list = Object.keys(state.overrides);
			head.innerHTML = '<div class="formulize-sizes__kicker">Nothing selected</div><div class="formulize-sizes__title"><h2>' + (list.length ? 'Changed sizes' : 'No changes') + '</h2></div>';
			body.innerHTML = list.length
				? '<p class="formulize-sizes__sect">On top of the ' + esc(presetName(state.preset)) + ' preset</p>' + list.map(function (token) {
					return '<div class="formulize-sizes__chg"><span class="formulize-sizes__chg-name">' + (DESKTOP_OF[token] ? ICON.phone + ' ' : '') + esc(entry(token).label) + '</span>' +
						'<span class="formulize-sizes__chg-acts"><button type="button" class="formulize-sizes__link formulize-sizes__link--plain" data-select="' + home(token) + '">Show</button><button type="button" class="formulize-sizes__link" data-reset="' + token + '">Reset</button></span>' +
						'<span class="formulize-sizes__chg-part">' + esc(usedBy(token).map(function (k) { return MAP.components[k].name; }).join(', ')) + '</span>' +
						'<span class="formulize-sizes__chg-val"><b>' + show(token, value(token)) + '</b> · ' + esc(presetName(state.preset)) + ' is ' + show(token, presetValue(token)) + '</span></div>';
				}).join('') + '<div class="formulize-sizes__empty"><p>Click anything in the preview to change its size.</p></div>' + gapsBlock(MAP.gaps)
				: '<div class="formulize-sizes__empty"><p>Everything is at the ' + esc(presetName(state.preset)) + ' preset’s sizes.</p><p>Click anything in the preview, such as a button, a field or a list row, to change its size. The change applies to every one of them on the site.</p></div>' + gapsBlock(MAP.gaps);
		}
	}

	/* ---- applying changes ---- */
	function render() {
		enforce();
		applyTokens();
		Array.prototype.forEach.call(document.querySelectorAll('#formulize-sizes-preset button'), function (b) { b.setAttribute('aria-pressed', String(b.getAttribute('data-p') === state.preset)); });
		var n = Object.keys(state.overrides).length, count = $('formulize-sizes-count');
		count.hidden = n === 0; count.textContent = n + (n === 1 ? ' size changed' : ' sizes changed');
		$('formulize-sizes-reset').disabled = n === 0;
		// keep focus on the control being used across the re-render
		var focused = document.activeElement, key = focused && focused.getAttribute && focused.getAttribute('data-token') ? [focused.tagName, focused.getAttribute('data-token'), focused.getAttribute('data-d') || ''] : null;
		renderInspector();
		if (key) {
			var again = Array.prototype.filter.call(body.querySelectorAll('[data-token="' + key[1] + '"]'), function (el) { return el.tagName === key[0] && (el.getAttribute('data-d') || '') === key[2]; })[0];
			if (again && !again.disabled) { again.focus(); }
		}
	}
	function store(token, v) {
		v = entry(token).type === 'text' ? v : Number(v);
		if (v === presetValue(token)) { delete state.overrides[token]; } else { state.overrides[token] = v; }
		state.dirty = true;
	}
	function setValue(token, v) { store(token, v); render(); pulse(); }
	// while a slider is dragged, only the preview and that setting's value change:
	// rebuilding the inspector would replace the slider under the pointer
	function dragValue(input) {
		var token = input.getAttribute('data-token');
		store(token, input.value); enforce(); applyTokens();
		var ctl = input.closest('.formulize-sizes__ctl');
		ctl.querySelector('.formulize-sizes__val').innerHTML = valHtml(token);
		ctl.classList.toggle('is-changed', changed(token));
	}
	// changing the phone value shows the phone preview, and the desktop value the desktop one
	function deviceFor(token) { return DESKTOP_OF[token] ? 'phone' : (PHONE_OF[token] ? 'desktop' : null); }

	function select(part, origin) {
		state.sel = part; state.origin = origin;
		if (part && partsOn[state.screen] && Object.keys(partsOn[state.screen]).length && !partsOn[state.screen][part]) {
			var s = Object.keys(MAP.screens).filter(function (x) { return partsOn[x] && partsOn[x][part]; })[0];
			if (s) { goScreen(s); }
		}
		markSelection(); renderInspector(); pulse();
	}
	function goScreen(screen) {
		state.screen = screen; state.origin = null;
		Array.prototype.forEach.call(document.querySelectorAll('#formulize-sizes-screens button'), function (b) { b.setAttribute('aria-selected', String(b.getAttribute('data-s') === screen)); });
		frame.src = frameUrl(screen);
		renderInspector();
	}
	function setWidth(dev) {
		if (!dev || (dev === 'phone') === state.phone) { return; }
		state.phone = dev === 'phone';
		frame.classList.toggle('is-phone', state.phone);
		Array.prototype.forEach.call(document.querySelectorAll('#formulize-sizes-width button'), function (b) { b.setAttribute('aria-pressed', String((b.getAttribute('data-w') === 'phone') === state.phone)); });
		// the inspector's notes measure the preview, which takes a moment to settle at the new width
		setTimeout(renderInspector, 300);
		renderInspector();
	}

	/* ---- events ---- */
	body.addEventListener('input', function (ev) {
		var el = ev.target, token = el.getAttribute && el.getAttribute('data-token'); if (!token) { return; }
		if (el.type === 'range') { dragValue(el); } else { setWidth(deviceFor(token)); setValue(token, el.value); }
	});
	body.addEventListener('change', function (ev) { if (ev.target.type === 'range') { render(); pulse(); } });
	body.addEventListener('click', function (ev) {
		var b = ev.target.closest('button'); if (!b) { return; }
		if (b.getAttribute('data-reset')) {
			b.getAttribute('data-reset').split(' ').forEach(function (t) { delete state.overrides[t]; });
			state.dirty = true; render(); pulse();
		} else if (b.getAttribute('data-d')) {
			var token = b.getAttribute('data-token'), l = limits(token);
			setValue(token, Math.min(l.max, Math.max(l.min, value(token) + Number(b.getAttribute('data-d')) * MAP.types[entry(token).type].step)));
		} else if (b.getAttribute('data-select')) {
			select(b.getAttribute('data-select'), null);
		}
	});
	head.addEventListener('click', function (ev) {
		var b = ev.target.closest('button'); if (!b) { return; }
		if (b.getAttribute('data-deselect')) { select(null, null); }
		else if (b.getAttribute('data-goto')) { goScreen(b.getAttribute('data-goto')); }
	});
	document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && state.sel) { select(null, null); } });
	$('formulize-sizes-canvas').addEventListener('click', function (ev) { if (ev.target === ev.currentTarget) { select(null, null); } });

	// the preset buttons, from the presets on offer
	var presetBox = $('formulize-sizes-preset');
	Object.keys(DATA.presets).forEach(function (key) {
		var b = document.createElement('button');
		b.type = 'button'; b.setAttribute('data-p', key); b.textContent = DATA.presets[key];
		b.addEventListener('click', function () { state.preset = key; state.dirty = true; render(); });
		presetBox.appendChild(b);
	});
	// the screen tabs, from the samples the theme provides
	var tabs = $('formulize-sizes-screens');
	Object.keys(MAP.screens).forEach(function (screen) {
		var b = document.createElement('button');
		b.type = 'button'; b.setAttribute('role', 'tab'); b.setAttribute('data-s', screen); b.textContent = MAP.screens[screen];
		b.addEventListener('click', function () { goScreen(screen); });
		tabs.appendChild(b);
	});
	Array.prototype.forEach.call(document.querySelectorAll('#formulize-sizes-width button'), function (b) {
		b.addEventListener('click', function () { setWidth(b.getAttribute('data-w')); });
	});
	$('formulize-sizes-reset').addEventListener('click', function () { state.overrides = {}; state.dirty = true; render(); });

	// saving: the preset and the changed sizes go in the form's hidden fields
	var submitting = false;
	$('formulize-sizes-form').addEventListener('submit', function () {
		enforce();
		$('formulize-sizes-size').value = state.preset;
		$('formulize-sizes-overrides').value = Object.keys(state.overrides).length ? JSON.stringify(state.overrides) : '';
		submitting = true;
	});
	window.addEventListener('beforeunload', function (ev) {
		if (state.dirty && !submitting) { ev.preventDefault(); ev.returnValue = ''; }
	});
	var saved = $('formulize-sizes-saved');
	if (saved) { setTimeout(function () { saved.hidden = true; }, 5000); }

	goScreen(state.screen);
	render();
})();
