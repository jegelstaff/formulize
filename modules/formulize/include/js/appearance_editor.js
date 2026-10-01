// The advanced editor (modules/formulize/appearance_editor.php).
//
// The page shows one of the theme's sample screens in an iframe
// (appearance_preview.php) and sets the settings being edited on it, as inline
// custom properties on its root element, so the preview always shows exactly
// the values being edited. Clicking a part of the preview (anything with
// data-fz-part) selects that part: every one of it is outlined, and the
// inspector shows its settings, which apply to every one of it on the site.
// With nothing selected, the inspector shows the site-wide settings (the
// Appearance page's: logo, colours, fonts and the Size preset) and, on its
// other tab, everything that has been changed.
//
// Everything about the parts comes from include/appearance_tokens.json, passed
// in as formulizeAppearanceEditor.map: the tokens' types and limits, the parts
// of the interface and the tokens of each, what can't be adjusted yet, which
// token has a separate value on phones, and the floors that depend on other
// tokens. The colours and fonts are the Appearance page's own.

(function () {
	'use strict';

	var DATA = window.formulizeAppearanceEditor;
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
	var UPLOADS = { appearance_logo: 'Logo', appearance_favicon: 'Favicon' };

	var state = {
		preset: DATA.preset || '',
		overrides: Array.isArray(DATA.overrides) ? {} : copy(DATA.overrides || {}),
		colours: {},
		fonts: { main: DATA.fonts.font, maincustom: DATA.fonts.customfont, heading: DATA.fonts.headingfont, headingcustom: DATA.fonts.headingcustomfont },
		// per upload: a newly chosen file's preview address, or removed (back to the theme's own)
		uploads: { appearance_logo: { file: '', removed: false }, appearance_favicon: { file: '', removed: false } },
		raised: {},
		screen: Object.keys(MAP.screens)[0],
		phone: false,
		sel: null,
		origin: null,
		tab: 'site',
		dirty: false
	};
	Object.keys(DATA.colours).forEach(function (key) { state.colours[key] = DATA.colours[key].value; });
	var partsOn = {}; // screen => { part: true }, from the samples' markup

	var $ = function (id) { return document.getElementById(id); };
	var frame = $('formulize-editor-frame'), head = $('formulize-editor-insp-head'), body = $('formulize-editor-insp-body');
	var hint = $('formulize-editor-hint'), DEFAULT_HINT = hint.innerHTML;

	function copy(o) { return JSON.parse(JSON.stringify(o)); }
	function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
	function entry(token) { return MAP.tokens[token]; }
	function presetName(key) { return DATA.presets[key] || DATA.presets['']; }
	function has(o, k) { return Object.prototype.hasOwnProperty.call(o, k); }

	/* ---- values ---- */
	function presetValue(token) {
		var e = entry(token);
		return (state.preset !== '' && e.presets[state.preset] !== undefined) ? e.presets[state.preset] : e['default'];
	}
	function value(token) { return has(state.overrides, token) ? state.overrides[token] : presetValue(token); }
	function changed(token) { return has(state.overrides, token); }
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

	/* ---- the site-wide settings ---- */
	function colourChanged(key) { return state.colours[key].toLowerCase() !== DATA.colours[key]['default'].toLowerCase(); }
	function fontLabel(list, key) {
		var f = DATA.fonts[list].filter(function (x) { return x.key === key; })[0];
		return f ? f.label : key;
	}
	// a typed-in Google Font name, cleaned the way the server cleans it
	function customName(which) { return (state.fonts[which + 'custom'] || '').replace(/[^a-zA-Z0-9 ]/g, '').trim(); }
	// {stack, google} for the main or the secondary font, or null for "leave it to the theme"
	function fontFor(which) {
		var key = state.fonts[which];
		if (key === 'custom') {
			var name = customName(which);
			return name ? { stack: "'" + name + "', sans-serif", google: name.replace(/ /g, '+') + ':wght@400;500;600;700' } : null;
		}
		if (which === 'heading' && key === 'geist') { return null; } // same as the main font
		return DATA.fonts.stacks[key] || null;
	}
	function fontChanged(which) { return state.fonts[which] !== 'geist' && !(state.fonts[which] === 'custom' && !customName(which)); }
	function fontShown(which) {
		if (state.fonts[which] === 'custom') { return customName(which) || 'Other Google Font (no name yet)'; }
		return fontLabel(which === 'main' ? 'main' : 'heading', state.fonts[which]).replace(/ \(default\)$/, '');
	}
	function uploadChanged(key) { return !!(state.uploads[key].file || state.uploads[key].removed); }
	function uploadUrl(key) {
		var u = state.uploads[key];
		return u.file || (u.removed ? '' : DATA.uploads[key].url);
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
		'[data-fz-part].formulize-editor-hover { outline: 1px dashed #3b6fd8 !important; outline-offset: 2px; }' +
		'[data-fz-part].formulize-editor-sel { outline: 2px solid #3b6fd8 !important; outline-offset: 2px; }' +
		'[data-fz-part].formulize-editor-sel.formulize-editor-origin { outline-width: 3px !important; }' +
		'@keyframes formulize-editor-pulse { from { box-shadow: 0 0 0 0 rgba(59,111,216,.45); } to { box-shadow: 0 0 0 10px rgba(59,111,216,0); } }' +
		'[data-fz-part].formulize-editor-pulse { animation: formulize-editor-pulse .7s ease-out; }' +
		'@media (prefers-reduced-motion: reduce) { [data-fz-part].formulize-editor-pulse { animation: none; } }';

	// a property the theme declares itself, put back when a setting returns to the
	// theme's own; the saved stylesheet in the preview would otherwise still apply
	function restore(root, token) {
		if (DATA.restore[token] !== null && DATA.restore[token] !== undefined) { root.style.setProperty(token, DATA.restore[token]); }
		else { root.style.removeProperty(token); }
	}
	function loadFont(d, google) {
		if (!google || d.querySelector('link[data-font="' + google + '"]')) { return; }
		var link = d.createElement('link');
		link.rel = 'stylesheet'; link.href = 'https://fonts.googleapis.com/css2?family=' + google + '&display=swap'; link.setAttribute('data-font', google);
		d.head.appendChild(link);
	}
	function applyTokens() {
		var d = doc();
		if (!d || !d.documentElement) { return; }
		var root = d.documentElement;
		Object.keys(MAP.tokens).forEach(function (token) { root.style.setProperty(token, css(token, value(token))); });
		Object.keys(DATA.colours).forEach(function (key) {
			var c = DATA.colours[key];
			Object.keys(c.tokens).forEach(function (token) {
				if (colourChanged(key)) { root.style.setProperty(token, c.tokens[token].split('%s').join(state.colours[key]).replace(/%%/g, '%')); }
				else { restore(root, token); }
			});
		});
		[['main', '--fz-font-sans'], ['heading', '--fz-font-heading']].forEach(function (pair) {
			var font = fontFor(pair[0]);
			if (font && fontChanged(pair[0])) { root.style.setProperty(pair[1], font.stack); }
			else { restore(root, pair[1]); }
			if (font) { loadFont(d, font.google); }
		});
		var logo = d.querySelector('[data-fz-part="logo"] img');
		if (logo) { logo.src = uploadUrl('appearance_logo') || DATA.themeLogoUrl; }
	}
	function markSelection() {
		var d = doc(); if (!d) { return; }
		Array.prototype.forEach.call(d.querySelectorAll('[data-fz-part]'), function (el) {
			var on = el.getAttribute('data-fz-part') === state.sel;
			el.classList.toggle('formulize-editor-sel', on);
			el.classList.toggle('formulize-editor-origin', on && el === state.origin);
		});
	}
	function pulse() {
		var d = doc(); if (!d || !state.sel) { return; }
		Array.prototype.forEach.call(d.querySelectorAll('[data-fz-part="' + state.sel + '"]'), function (el) {
			el.classList.remove('formulize-editor-pulse'); void el.offsetWidth; el.classList.add('formulize-editor-pulse');
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
			Array.prototype.forEach.call(d.querySelectorAll('.formulize-editor-hover'), function (x) { x.classList.remove('formulize-editor-hover'); });
			var el = ev.target.closest ? ev.target.closest('[data-fz-part]') : null;
			var c = el && MAP.components[el.getAttribute('data-fz-part')];
			if (c) { el.classList.add('formulize-editor-hover'); hint.innerHTML = '<b>' + esc(c.name) + '</b> · click to change ' + esc(c.plural); }
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

	/* ---- the inspector: a part's settings ---- */
	function widgetFor(token, id) {
		var e = entry(token), l = limits(token), v = value(token);
		if (l.values) {
			return { widget: '<select id="' + id + '" data-token="' + token + '">' + l.values.map(function (x) {
				return '<option value="' + esc(x) + '"' + (String(x) === String(v) ? ' selected' : '') + '>' + esc(x) + (e.type === 'text' ? ' · ' + TEXT_PX[x] + 'px' : '') + '</option>';
			}).join('') + '</select>', range: '' };
		}
		var step = MAP.types[e.type].step;
		return {
			widget: '<button type="button" class="formulize-editor__step" data-token="' + token + '" data-d="-1" aria-label="Smaller"' + (v <= l.min ? ' disabled' : '') + '>−</button>' +
				'<input type="range" id="' + id + '" data-token="' + token + '" min="' + l.min + '" max="' + l.max + '" step="' + step + '" value="' + v + '">' +
				'<button type="button" class="formulize-editor__step" data-token="' + token + '" data-d="1" aria-label="Larger"' + (v >= l.max ? ' disabled' : '') + '>+</button>',
			range: '<div class="formulize-editor__range"><span>' + show(token, l.min) + '</span><span>' + show(token, l.max) + '</span></div>'
		};
	}
	function valHtml(token) {
		var v = value(token);
		return '<b>' + show(token, v) + '</b><span class="formulize-editor__from">' +
			(changed(token) ? 'changed · ' + esc(presetName(state.preset)) + ' is ' + show(token, presetValue(token)) : 'from ' + esc(presetName(state.preset))) + '</span>';
	}
	function measuredHeight(token) {
		var d = doc(), sel = MEASURED[token];
		if (!d || !sel) { return 0; }
		var el = d.querySelector(sel);
		return el ? Math.round(el.getBoundingClientRect().height) : 0;
	}
	function note(text, icon) {
		return '<p class="formulize-editor__note">' + (icon ? ICON[icon] : '') + '<span>' + esc(text) + '</span></p>';
	}
	function notesHtml(token) {
		var l = limits(token), v = value(token), notes = '';
		if (state.raised[token]) { notes += note('Raised to ' + show(token, v) + ' so it fits ' + l.why + '.'); }
		else if (l.why && v === l.min) { notes += note('This is as small as it goes with ' + l.why + '.'); }
		var h = measuredHeight(token);
		if (h && h > v * 4 + 1) { notes += note('Shown at ' + h + 'px here: the theme keeps it at least that tall at this width.', state.phone ? 'phone' : ''); }
		return notes;
	}
	function control(token) {
		var e = entry(token), id = 'formulize-editor-' + token.replace(/[^a-z0-9]/g, '');
		var phone = PHONE_OF[token];
		var resets = [token, phone].filter(function (t) { return t && changed(t); });
		var others = usedBy(token).filter(function (k) { return k !== state.sel; }).map(function (k) { return MAP.components[k].name; });
		var inner, w;
		if (phone) {
			var row = function (t, dev, label) {
				var rw = widgetFor(t, 'formulize-editor-' + t.replace(/[^a-z0-9]/g, ''));
				var active = (dev === 'phone') === state.phone;
				return '<div class="formulize-editor__dev' + (active ? ' is-active' : '') + '"><span class="formulize-editor__dev-icon" title="' + label + '">' + ICON[dev] + '</span><span class="formulize-editor__dev-name">' + label + '</span>' +
					'<div class="formulize-editor__widget">' + rw.widget + '</div><div class="formulize-editor__val">' + valHtml(t) + '</div>' + notesHtml(t) + '</div>';
			};
			inner = '<div class="formulize-editor__devs">' + row(token, 'desktop', 'Desktop') + row(phone, 'phone', 'Phones (768px and below)') + '</div>';
		} else {
			w = widgetFor(token, id);
			inner = '<div class="formulize-editor__widget">' + w.widget + '</div>' + w.range + '<div class="formulize-editor__val">' + valHtml(token) + '</div>' + notesHtml(token);
		}
		return '<div class="formulize-editor__ctl' + (resets.length ? ' is-changed' : '') + '">' +
			'<div class="formulize-editor__ctl-head"><label for="' + id + '">' + esc(e.label) + '</label>' + (resets.length ? '<button type="button" class="formulize-editor__link" data-reset="' + resets.join(' ') + '">Reset</button>' : '') + '</div>' +
			'<p class="formulize-editor__desc">' + esc(phone ? e.description.replace(/\.$/, '') + ', on desktop and on phones.' : e.description) + '</p>' + inner +
			(others.length ? '<p class="formulize-editor__shared">Also changes ' + esc(others.join(', ')) + '.</p>' : '') +
		'</div>';
	}
	function gapsBlock(list) {
		if (!list || !list.length) { return ''; }
		return '<details class="formulize-editor__gaps"><summary>Not adjustable yet</summary><ul>' + list.map(function (g) { return '<li>' + esc(g) + '</li>'; }).join('') + '</ul></details>';
	}

	/* ---- the inspector: the site-wide settings ---- */
	function sect(label) { return '<p class="formulize-editor__sect">' + esc(label) + '</p>'; }
	function uploadBlock(key) {
		var url = uploadUrl(key), isLogo = key === 'appearance_logo';
		var thumb = url ? '<img src="' + esc(url) + '" alt="">' : (isLogo ? '<img src="' + esc(DATA.themeLogoUrl) + '" alt="">' : '<span>—</span>');
		var desc = isLogo ? 'At the top left of every page.' : 'The small icon in the browser tab and in bookmarks.';
		var status = state.uploads[key].file ? 'A new image, saved when you save.' : (url ? 'Uploaded.' : 'The theme’s own.');
		return '<div class="formulize-editor__ctl' + (uploadChanged(key) ? ' is-changed' : '') + '">' +
			'<div class="formulize-editor__ctl-head"><span class="formulize-editor__lbl">' + UPLOADS[key] + '</span>' + (uploadChanged(key) ? '<button type="button" class="formulize-editor__link" data-upload-undo="' + key + '">Undo</button>' : '') + '</div>' +
			'<p class="formulize-editor__desc">' + desc + '</p>' +
			'<div class="formulize-editor__upload"><span class="formulize-editor__thumb' + (isLogo ? '' : ' formulize-editor__thumb--icon') + '">' + thumb + '</span>' +
				'<span class="formulize-editor__upload-acts"><label class="formulize-editor__btn formulize-editor__btn--sm" for="formulize-editor-' + key + '">Upload…</label>' +
				(url ? '<button type="button" class="formulize-editor__btn formulize-editor__btn--sm" data-upload-remove="' + key + '">Use the theme’s own</button>' : '') +
				'<span class="formulize-editor__upload-status">' + esc(status) + ' ' + esc(DATA.uploads[key].types) + '.</span></span></div>' +
		'</div>';
	}
	function fontBlock(which) {
		var list = which === 'main' ? 'main' : 'heading', id = 'formulize-editor-font-' + which;
		var isCustom = state.fonts[which] === 'custom';
		var desc = which === 'main'
			? 'Body text, tables, buttons and form controls.'
			: 'Headings, form labels, and the drawer’s title. Leave it on the main font to use one font throughout.';
		return '<div class="formulize-editor__ctl' + (fontChanged(which) ? ' is-changed' : '') + '">' +
			'<div class="formulize-editor__ctl-head"><label for="' + id + '">' + (which === 'main' ? 'Main font' : 'Secondary font') + '</label>' + (fontChanged(which) ? '<button type="button" class="formulize-editor__link" data-font-reset="' + which + '">Reset</button>' : '') + '</div>' +
			'<p class="formulize-editor__desc">' + desc + '</p>' +
			'<div class="formulize-editor__widget"><select id="' + id + '" data-font="' + which + '">' + DATA.fonts[list].map(function (f) {
				return '<option value="' + esc(f.key) + '"' + (f.key === state.fonts[which] ? ' selected' : '') + '>' + esc(f.label) + '</option>';
			}).join('') + '</select></div>' +
			(isCustom ? '<label class="formulize-editor__custom-font"><span>Google Font name</span><input type="text" data-custom-font="' + which + '" value="' + esc(state.fonts[which + 'custom'] || '') + '" placeholder="' + (which === 'main' ? 'e.g. Roboto' : 'e.g. Playfair Display') + '" spellcheck="false"></label>' +
				'<p class="formulize-editor__desc">The exact name as it appears on <a href="https://fonts.google.com" target="_blank" rel="noopener">fonts.google.com</a>. The font is loaded from Google Fonts.</p>' : '') +
		'</div>';
	}
	function siteBlock() {
		var h = '<p class="formulize-editor__intro">The same settings as the Appearance page. Every part of the interface follows them, except for anything changed on the part itself: click anything in the preview to change just that part.</p>';
		h += sect('Logo') + uploadBlock('appearance_logo') + uploadBlock('appearance_favicon');
		h += sect('Colours') + Object.keys(DATA.colours).map(function (key) {
			var c = DATA.colours[key], id = 'formulize-editor-colour-' + key;
			return '<div class="formulize-editor__colour' + (colourChanged(key) ? ' is-changed' : '') + '"><input type="color" id="' + id + '" data-colour="' + key + '" value="' + esc(state.colours[key]) + '">' +
				'<label for="' + id + '" class="formulize-editor__lbl">' + esc(c.label) + '</label>' +
				(colourChanged(key) ? '<button type="button" class="formulize-editor__link" data-colour-reset="' + key + '">Reset to ' + esc(c['default']) + '</button>' : '<span class="formulize-editor__hex">' + esc(state.colours[key]) + '</span>') +
				'<span class="formulize-editor__desc">' + esc(c.description) + '</span></div>';
		}).join('');
		h += sect('Fonts') + fontBlock('main') + fontBlock('heading');
		h += sect('Size') + '<div class="formulize-editor__ctl' + (state.preset !== '' ? ' is-changed' : '') + '"><div class="formulize-editor__ctl-head"><span class="formulize-editor__lbl" id="formulize-editor-preset-label">Size preset</span></div>' +
			'<p class="formulize-editor__desc">Sets every size at once. Sizes changed on a part stay changed.</p>' +
			'<div class="formulize-editor__seg formulize-editor__seg--full" role="group" aria-labelledby="formulize-editor-preset-label">' + Object.keys(DATA.presets).map(function (key) {
				return '<button type="button" data-preset="' + esc(key) + '" aria-pressed="' + (state.preset === key) + '">' + esc(DATA.presets[key]) + '</button>';
			}).join('') + '</div></div>';
		return h;
	}
	// everything that differs from the theme's own, site-wide and on parts
	function siteChanges() {
		var list = [];
		Object.keys(UPLOADS).forEach(function (key) {
			if (uploadUrl(key) || uploadChanged(key)) {
				list.push({ name: UPLOADS[key], val: state.uploads[key].file ? 'New image' : (state.uploads[key].removed ? 'Back to the theme’s own' : 'Uploaded'), reset: 'upload:' + key, resetLabel: uploadChanged(key) ? 'Undo' : 'Use the theme’s own' });
			}
		});
		Object.keys(DATA.colours).forEach(function (key) {
			if (colourChanged(key)) { list.push({ name: DATA.colours[key].label, swatch: state.colours[key], val: state.colours[key] + ' · the theme’s is ' + DATA.colours[key]['default'], reset: 'colour:' + key }); }
		});
		if (fontChanged('main')) { list.push({ name: 'Main font', val: fontShown('main'), reset: 'font:main' }); }
		if (fontChanged('heading')) { list.push({ name: 'Secondary font', val: fontShown('heading'), reset: 'font:heading' }); }
		if (state.preset !== '') { list.push({ name: 'Size preset', val: presetName(state.preset), reset: 'preset' }); }
		return list;
	}
	function changeCount() { return siteChanges().length + Object.keys(state.overrides).length; }
	function changesBlock() {
		var site = siteChanges(), parts = Object.keys(state.overrides), h = '';
		if (!site.length && !parts.length) {
			return '<div class="formulize-editor__empty"><p>Nothing has been changed: everything is as the theme has it.</p><p>Change the logo, colours, fonts and Size preset under Site-wide, or click anything in the preview, such as a button, a field or a list row, to change just that part. A change to a part applies to every one of it on the site.</p></div>';
		}
		if (site.length) {
			h += sect('Site-wide') + site.map(function (c) {
				return '<div class="formulize-editor__chg"><span class="formulize-editor__chg-name">' + esc(c.name) + '</span>' +
					'<span class="formulize-editor__chg-acts"><button type="button" class="formulize-editor__link" data-site-reset="' + esc(c.reset) + '">' + esc(c.resetLabel || 'Reset') + '</button></span>' +
					'<span class="formulize-editor__chg-val">' + (c.swatch ? '<span class="formulize-editor__swatch" style="background:' + esc(c.swatch) + '"></span>' : '') + '<b>' + esc(c.val) + '</b></span></div>';
			}).join('');
		}
		if (parts.length) {
			h += sect('Parts, on top of the ' + presetName(state.preset) + ' preset') + parts.map(function (token) {
				return '<div class="formulize-editor__chg"><span class="formulize-editor__chg-name">' + (DESKTOP_OF[token] ? ICON.phone + ' ' : '') + esc(entry(token).label) + '</span>' +
					'<span class="formulize-editor__chg-acts"><button type="button" class="formulize-editor__link formulize-editor__link--plain" data-select="' + home(token) + '">Show</button><button type="button" class="formulize-editor__link" data-reset="' + token + '">Reset</button></span>' +
					'<span class="formulize-editor__chg-part">' + esc(usedBy(token).map(function (k) { return MAP.components[k].name; }).join(', ')) + '</span>' +
					'<span class="formulize-editor__chg-val"><b>' + show(token, value(token)) + '</b> · ' + esc(presetName(state.preset)) + ' is ' + show(token, presetValue(token)) + '</span></div>';
			}).join('');
		}
		return h;
	}

	function renderInspector() {
		if (state.sel) {
			var c = MAP.components[state.sel];
			var where = Object.keys(MAP.screens).filter(function (s) { return partsOn[s] && partsOn[s][state.sel]; }).map(function (s) {
				return '<button type="button" data-goto="' + s + '"' + (s === state.screen ? ' aria-current="true"' : '') + '>' + esc(MAP.screens[s]) + '</button>';
			}).join('');
			head.innerHTML = '<div class="formulize-editor__kicker">Selected</div><div class="formulize-editor__title"><h2>' + esc(c.name) + '</h2><button type="button" class="formulize-editor__x" data-deselect="1" aria-label="Deselect">✕</button></div>' +
				'<div class="formulize-editor__scope">' + (c.site
					? '<span>A site-wide setting: it changes <strong>' + esc(c.plural) + '</strong>.</span>'
					: '<span>Changes apply to <strong>' + esc(c.plural) + '</strong> on the site, not just the one you clicked.' + (where ? ' Used on these screens, outlined in each:' : '') + '</span>' + (where ? '<div class="formulize-editor__where">' + where + '</div>' : '')) + '</div>';
			body.innerHTML = c.site === 'logo' ? uploadBlock('appearance_logo') + uploadBlock('appearance_favicon') : c.tokens.map(control).join('') + gapsBlock(c.gaps);
		} else {
			var n = changeCount();
			head.innerHTML = '<div class="formulize-editor__kicker">Nothing selected</div>' +
				'<div class="formulize-editor__seg formulize-editor__seg--full" role="tablist" aria-label="Settings">' +
					'<button type="button" role="tab" data-tab="site" aria-pressed="' + (state.tab === 'site') + '" aria-selected="' + (state.tab === 'site') + '">Site-wide</button>' +
					'<button type="button" role="tab" data-tab="changes" aria-pressed="' + (state.tab === 'changes') + '" aria-selected="' + (state.tab === 'changes') + '">Changes' + (n ? ' (' + n + ')' : '') + '</button></div>';
			body.innerHTML = state.tab === 'site' ? siteBlock() : changesBlock() + gapsBlock(MAP.gaps);
		}
	}

	/* ---- applying changes ---- */
	function render() {
		enforce();
		applyTokens();
		var n = changeCount(), count = $('formulize-editor-count');
		count.hidden = n === 0; count.textContent = n + (n === 1 ? ' change' : ' changes');
		$('formulize-editor-reset').disabled = n === 0;
		// keep focus on the control being used across the re-render
		var f = document.activeElement, attrs = ['data-token', 'data-d', 'data-colour', 'data-font', 'data-custom-font', 'data-preset', 'data-tab'];
		var key = (f && f.getAttribute && (body.contains(f) || head.contains(f))) ? attrs.map(function (a) { return f.getAttribute(a) || ''; }) : null;
		renderInspector();
		if (key && key.join('')) {
			var again = Array.prototype.filter.call(document.querySelectorAll('#formulize-editor-insp-head *, #formulize-editor-insp-body *'), function (el) {
				return el.tagName === f.tagName && attrs.every(function (a, i) { return (el.getAttribute(a) || '') === key[i]; });
			})[0];
			if (again && !again.disabled) { again.focus(); }
		}
	}
	function touch() { state.dirty = true; }
	function store(token, v) {
		v = entry(token).type === 'text' ? v : Number(v);
		if (v === presetValue(token)) { delete state.overrides[token]; } else { state.overrides[token] = v; }
		touch();
	}
	function setValue(token, v) { store(token, v); render(); pulse(); }
	// while a slider is dragged, only the preview and that setting's value change:
	// rebuilding the inspector would replace the slider under the pointer
	function dragValue(input) {
		var token = input.getAttribute('data-token');
		store(token, input.value); enforce(); applyTokens();
		var ctl = input.closest('.formulize-editor__ctl');
		ctl.querySelector('.formulize-editor__val').innerHTML = valHtml(token);
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
		body.parentNode.scrollTop = 0;
	}
	function goScreen(screen) {
		state.screen = screen; state.origin = null;
		Array.prototype.forEach.call(document.querySelectorAll('#formulize-editor-screens button'), function (b) { b.setAttribute('aria-selected', String(b.getAttribute('data-s') === screen)); });
		frame.src = frameUrl(screen);
		renderInspector();
	}
	function setWidth(dev) {
		if (!dev || (dev === 'phone') === state.phone) { return; }
		state.phone = dev === 'phone';
		frame.classList.toggle('is-phone', state.phone);
		Array.prototype.forEach.call(document.querySelectorAll('#formulize-editor-width button'), function (b) { b.setAttribute('aria-pressed', String((b.getAttribute('data-w') === 'phone') === state.phone)); });
		// the inspector's notes measure the preview, which takes a moment to settle at the new width
		setTimeout(renderInspector, 300);
		renderInspector();
	}
	function resetUpload(key) {
		var input = $('formulize-editor-' + key);
		if (input) { input.value = ''; }
		if (state.uploads[key].file) { URL.revokeObjectURL(state.uploads[key].file); }
		state.uploads[key] = { file: '', removed: false };
	}
	function resetFont(which) { state.fonts[which] = 'geist'; state.fonts[which + 'custom'] = ''; }

	/* ---- events ---- */
	body.addEventListener('input', function (ev) {
		var el = ev.target, a = function (n) { return el.getAttribute && el.getAttribute(n); };
		if (a('data-colour')) {
			// while a colour is picked, only the preview and that row change
			state.colours[a('data-colour')] = el.value; touch(); applyTokens();
			el.parentNode.classList.toggle('is-changed', colourChanged(a('data-colour')));
			return;
		}
		if (a('data-custom-font')) { state.fonts[a('data-custom-font') + 'custom'] = el.value; touch(); applyTokens(); return; }
		var token = a('data-token'); if (!token) { return; }
		if (el.type === 'range') { dragValue(el); } else { setWidth(deviceFor(token)); setValue(token, el.value); }
	});
	body.addEventListener('change', function (ev) {
		var el = ev.target, a = function (n) { return el.getAttribute && el.getAttribute(n); };
		if (el.type === 'range' || a('data-colour') || a('data-custom-font')) { render(); pulse(); }
		else if (a('data-font')) { state.fonts[a('data-font')] = el.value; touch(); render(); }
	});
	body.addEventListener('click', function (ev) {
		var b = ev.target.closest('button'); if (!b) { return; }
		var a = function (n) { return b.getAttribute(n); }, parts;
		if (a('data-reset')) {
			a('data-reset').split(' ').forEach(function (t) { delete state.overrides[t]; });
			touch(); render(); pulse();
		} else if (a('data-d')) {
			var token = a('data-token'), l = limits(token);
			setValue(token, Math.min(l.max, Math.max(l.min, value(token) + Number(a('data-d')) * MAP.types[entry(token).type].step)));
		} else if (a('data-select')) {
			select(a('data-select'), null);
		} else if (a('data-preset') !== null) {
			state.preset = a('data-preset'); touch(); render();
		} else if (a('data-colour-reset')) {
			state.colours[a('data-colour-reset')] = DATA.colours[a('data-colour-reset')]['default']; touch(); render();
		} else if (a('data-font-reset')) {
			resetFont(a('data-font-reset')); touch(); render();
		} else if (a('data-upload-undo')) {
			resetUpload(a('data-upload-undo')); touch(); render();
		} else if (a('data-upload-remove')) {
			resetUpload(a('data-upload-remove'));
			state.uploads[a('data-upload-remove')].removed = !!DATA.uploads[a('data-upload-remove')].url; touch(); render();
		} else if (a('data-site-reset')) {
			parts = a('data-site-reset').split(':');
			if (parts[0] === 'colour') { state.colours[parts[1]] = DATA.colours[parts[1]]['default']; }
			else if (parts[0] === 'font') { resetFont(parts[1]); }
			else if (parts[0] === 'preset') { state.preset = ''; }
			else if (parts[0] === 'upload') {
				if (uploadChanged(parts[1])) { resetUpload(parts[1]); } else { state.uploads[parts[1]].removed = true; }
			}
			touch(); render();
		}
	});
	head.addEventListener('click', function (ev) {
		var b = ev.target.closest('button'); if (!b) { return; }
		if (b.getAttribute('data-deselect')) { select(null, null); }
		else if (b.getAttribute('data-goto')) { goScreen(b.getAttribute('data-goto')); }
		else if (b.getAttribute('data-tab')) { state.tab = b.getAttribute('data-tab'); render(); }
	});
	document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && state.sel) { select(null, null); } });
	$('formulize-editor-canvas').addEventListener('click', function (ev) { if (ev.target === ev.currentTarget) { select(null, null); } });
	// a logo or favicon chosen from the inspector shows in the preview straight away
	Object.keys(UPLOADS).forEach(function (key) {
		$('formulize-editor-' + key).addEventListener('change', function () {
			var file = this.files && this.files[0];
			if (state.uploads[key].file) { URL.revokeObjectURL(state.uploads[key].file); }
			state.uploads[key] = { file: file ? URL.createObjectURL(file) : '', removed: false };
			touch(); render();
		});
	});

	// the screen tabs, from the samples the theme provides
	var tabs = $('formulize-editor-screens');
	Object.keys(MAP.screens).forEach(function (screen) {
		var b = document.createElement('button');
		b.type = 'button'; b.setAttribute('role', 'tab'); b.setAttribute('data-s', screen); b.textContent = MAP.screens[screen];
		b.addEventListener('click', function () { goScreen(screen); });
		tabs.appendChild(b);
	});
	Array.prototype.forEach.call(document.querySelectorAll('#formulize-editor-width button'), function (b) {
		b.addEventListener('click', function () { setWidth(b.getAttribute('data-w')); });
	});
	// back to the theme's own for everything; nothing is saved until Save
	$('formulize-editor-reset').addEventListener('click', function () {
		if (!window.confirm('Put everything back to how the ' + DATA.theme + ' theme has it: the logo and favicon, colours, fonts, Size preset and every part’s own settings? Nothing changes on the site until you save.')) { return; }
		state.overrides = {}; state.preset = '';
		Object.keys(DATA.colours).forEach(function (key) { state.colours[key] = DATA.colours[key]['default']; });
		resetFont('main'); resetFont('heading');
		Object.keys(UPLOADS).forEach(function (key) { resetUpload(key); state.uploads[key].removed = !!DATA.uploads[key].url; });
		touch(); render();
	});

	// saving: every setting goes in the form's hidden fields
	var submitting = false;
	$('formulize-editor-form').addEventListener('submit', function () {
		enforce();
		var values = {
			appearance_size: state.preset,
			appearance_overrides: Object.keys(state.overrides).length ? JSON.stringify(state.overrides) : '',
			appearance_font: state.fonts.main,
			appearance_customfont: state.fonts.main === 'custom' ? state.fonts.maincustom : '',
			appearance_headingfont: state.fonts.heading,
			appearance_headingcustomfont: state.fonts.heading === 'custom' ? state.fonts.headingcustom : '',
			appearance_logo_remove: state.uploads.appearance_logo.removed ? '1' : '',
			appearance_favicon_remove: state.uploads.appearance_favicon.removed ? '1' : ''
		};
		Object.keys(DATA.colours).forEach(function (key) { values['appearance_' + key] = state.colours[key]; });
		Array.prototype.forEach.call(this.querySelectorAll('[data-setting]'), function (input) {
			input.value = has(values, input.getAttribute('data-setting')) ? values[input.getAttribute('data-setting')] : '';
		});
		submitting = true;
	});
	window.addEventListener('beforeunload', function (ev) {
		if (state.dirty && !submitting) { ev.preventDefault(); ev.returnValue = ''; }
	});
	var saved = $('formulize-editor-saved');
	if (saved) { setTimeout(function () { saved.hidden = true; }, 5000); }

	goScreen(state.screen);
	render();
})();
