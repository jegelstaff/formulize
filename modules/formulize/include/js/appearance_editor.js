// The advanced editor (modules/formulize/appearance_editor.php).
//
// The page shows one of the theme's sample screens in an iframe
// (appearance_preview.php) and sets the settings being edited on it, as inline
// custom properties on its root element, so the preview always shows exactly
// the values being edited. Clicking a part of the preview (anything with
// data-fz-part) selects that part: every one of it is outlined, and the
// inspector shows its settings (its fonts, colours, corners and sizes), which
// apply to every one of it on the site. With nothing selected, the inspector
// shows the site-wide settings (the Appearance page's: logo, colours, fonts, the
// look applied to the site and the page width) and, on its other tab, everything that has been
// changed.
//
// Everything about the parts comes from include/appearance_tokens.json, passed
// in as formulizeAppearanceEditor.map: the tokens' types and limits, the parts
// of the interface and the tokens of each, the site's colours a part can use,
// what can't be adjusted yet, which token has a separate value on phones, and
// the floors that depend on other tokens. The site-wide colours and fonts are
// the Appearance page's own.
//
// Save sends the form in the background and takes back what was saved, so the
// editor stays exactly where it was.

(function () {
	'use strict';

	var DATA = window.formulizeAppearanceEditor;
	if (!DATA) { return; }
	var MAP = DATA.map;

	// the text steps' sizes at the default 16px root (formulize-ui.css)
	var TEXT_PX = { 'xs': 12, 'xs-plus': 13, 'sm': 14, 'sm-plus': 15, 'base': 16, 'lg': 18, 'xl': 20, '2xl': 24, '3xl': 30 };
	var FULL = 999; // a fully rounded corner
	var ICON = {
		desktop: '<svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true"><rect x="2" y="3" width="16" height="11" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M7 17h6M10 14v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		phone: '<svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true"><rect x="5.5" y="2" width="9" height="16" rx="1.8" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M9 15.2h2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
		warn: '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path d="M8 1.8l6.5 11.4h-13z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M8 6.3v3.2M8 11.4v.1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>'
	};
	// Heights a theme may keep larger than the token at some widths (Lyris keeps
	// fields 44px tall on phones, for tapping): the editor measures the first of
	// these in the preview and says so when it is taller than the setting.
	var MEASURED = {
		'--fz-field-height': '[data-fz-part="field"]:not(textarea):not(select[multiple])',
		'--fz-control-height': '[data-fz-part="button"]:not(.fz-btn--icon):not(.formulize-drawer__btn--icon)'
	};
	// text on a background, which the inspector warns about when it is hard to
	// read (WCAG AA: 4.5:1, or 3:1 for large text such as titles)
	var CONTRAST = [
		['--fz-button-primary-text', '--fz-button-primary-bg'], ['--fz-button-text', '--fz-button-bg'],
		['--fz-tab-active-text', '--fz-tab-active-bg'], ['--fz-tab-text', '--fz-tab-bg'],
		['--fz-title-color', '--fz-form-bg', true], ['--fz-label-color', '--fz-form-bg'], ['--fz-help-color', '--fz-form-bg'],
		['--fz-value-color', '--fz-form-bg'], ['--fz-field-color', '--fz-field-bg'], ['--fz-menu-text', '--fz-menu-bg'],
		['--fz-header-text', '--fz-header-bg'], ['--fz-row-text', '--fz-row-bg'], ['--fz-row-text', '--fz-row-hover']
	];
	var SECTIONS = [['text', 'Text'], ['colour', 'Colours'], ['shape', 'Corners'], ['space', 'Size and spacing']];
	var UPLOADS = { appearance_logo: 'Logo', appearance_favicon: 'Favicon' };
	// What is being edited: the site appearance (EDIT is null), or a look, which is
	// a set of changes to the site appearance (BASE). A look's settings are measured
	// against the site appearance's, and only what differs from it is the look's.
	// A built-in look is read-only.
	var EDIT = DATA.editing, BASE = DATA.base, READONLY = !!(EDIT && EDIT.builtin);
	// the page width, for a theme that has one: its limits, and the theme's own
	// width ('full', or pixels), which is the default
	var WIDTH = DATA.contentWidth;

	var state = {
		preset: '',
		contentWidth: '', // 'full', or a maximum width in pixels
		overrides: {},
		colours: {},
		fonts: {},
		// per upload: a newly chosen file's preview address, or removed (back to the theme's own)
		uploads: { appearance_logo: { file: '', removed: false }, appearance_favicon: { file: '', removed: false } },
		raised: {},
		screen: Object.keys(MAP.screens)[0],
		phone: false,
		sel: null,
		origin: null,
		tab: 'site',
		openColour: null,
		dirty: false,
		saving: false
	};
	var savedUploads = {}; // the logo and favicon as saved: their addresses
	var partsOn = {}; // screen => { part: true }, from the samples' markup

	var $ = function (id) { return document.getElementById(id); };
	var frame = $('formulize-editor-frame'), head = $('formulize-editor-insp-head'), body = $('formulize-editor-insp-body');
	var hint = $('formulize-editor-hint'), DEFAULT_HINT = hint.innerHTML;

	function copy(o) { return JSON.parse(JSON.stringify(o)); }
	function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
	function entry(token) { return MAP.tokens[token]; }
	function type(token) { return entry(token).type; }
	function lookName(key) { return (DATA.looks[key] || DATA.looks['']).name; }
	function has(o, k) { return Object.prototype.hasOwnProperty.call(o, k); }
	function lower(s) { return s.charAt(0).toLowerCase() + s.slice(1); }

	// take on the settings as saved: when the page opens, and after a save
	function adopt(saved) {
		state.preset = saved.preset || '';
		state.contentWidth = WIDTH ? String(saved.contentWidth || WIDTH.theme) : '';
		state.overrides = Array.isArray(saved.overrides) ? {} : copy(saved.overrides || {});
		state.colours = copy(saved.colours);
		state.fonts = copy(saved.fonts);
		savedUploads = copy(saved.uploads);
	}
	adopt(DATA.state);

	/* ---- values ---- */
	function isSize(token) { return ['spacing', 'text', 'weight', 'leading', 'measure'].indexOf(type(token)) !== -1; }
	// what a part's setting is when the site appearance doesn't change it: the
	// theme's own. The preview is the site appearance itself, without the look
	// applied to the site, which is a set of changes on top of it.
	function presetValue(token) { return (BASE && has(BASE.overrides, token)) ? BASE.overrides[token] : entry(token)['default']; }
	function value(token) { return has(state.overrides, token) ? state.overrides[token] : presetValue(token); }
	function changed(token) { return has(state.overrides, token); }
	// what a value is measured against: the site appearance's, for a look, or the theme's own
	function baseName() { return BASE ? 'The site appearance’s' : 'The theme’s'; }
	function css(token, v) {
		var t = type(token);
		if (t === 'spacing') { return 'calc(var(--fz-spacing) * ' + v + ')'; }
		if (t === 'text') { return 'var(--fz-text-' + v + ')'; }
		if (t === 'measure') { return v + 'ch'; }
		if (t === 'font') { return v === 'secondary' ? 'var(--fz-font-heading)' : 'var(--fz-font-sans)'; }
		if (t === 'colour') { return MAP.colours[v] ? 'var(' + MAP.colours[v].css + ')' : v; }
		if (t === 'radius') { return v + 'px'; }
		return String(v);
	}
	function show(token, v) {
		var t = type(token);
		if (t === 'spacing') { return (Math.round(v * 4 * 100) / 100) + 'px'; }
		if (t === 'text') { return TEXT_PX[v] + 'px'; }
		if (t === 'measure') { return v + ' characters'; }
		if (t === 'font') { return (v === 'secondary' ? 'Secondary font' : 'Main font') + ' (' + fontName(v) + ')'; }
		if (t === 'colour') { return MAP.colours[v] ? MAP.colours[v].label : String(v).toUpperCase(); }
		if (t === 'radius') { return Number(v) >= FULL ? 'Fully rounded' : v + 'px'; }
		return String(v);
	}

	// the token's own limits, raised by its floor when another token's value calls for it
	function limits(token) {
		var e = entry(token), l = {};
		if (e.values) { l.values = e.values; return l; }
		if (e.min === undefined) { return l; } // a font or a colour
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

	/* ---- colours ---- */
	function hex6(h) {
		h = String(h || '').trim().toLowerCase();
		if (/^#[0-9a-f]{3}$/.test(h)) { h = '#' + h[1] + h[1] + h[2] + h[2] + h[3] + h[3]; }
		return /^#[0-9a-f]{6}$/.test(h) ? h : null;
	}
	function mixHex(a, pct, b) {
		var p = function (h) { return [1, 3, 5].map(function (i) { return parseInt(h.substr(i, 2), 16); }); };
		var x = p(a), y = p(b), w = pct / 100;
		return '#' + x.map(function (c, i) { var n = Math.round(c * w + y[i] * (1 - w)); return (n < 16 ? '0' : '') + n.toString(16); }).join('');
	}
	// a colour template from the Appearance page's colour map, worked out for one base colour
	function fromTemplate(template, base) {
		if (template === '%s') { return base; }
		var m = /color-mix\(in srgb,\s*%s\s+(\d+)%%?,\s*(#[0-9a-fA-F]{3,6})\)/.exec(template || '');
		return m ? mixHex(base, Number(m[1]), hex6(m[2])) : base;
	}
	// what one of the site's colours (primary, text-muted...) looks like right now
	function refHex(name) {
		var c = MAP.colours[name];
		if (!c) { return hex6(name) || '#000000'; }
		var site = c.from && DATA.colours[c.from];
		if (site && colourChanged(c.from)) { return fromTemplate(site.tokens[c.css], hex6(state.colours[c.from])); }
		var declared = hex6(DATA.restore[c.css]);
		if (declared) { return declared; }
		return site ? fromTemplate(site.tokens[c.css], hex6(site['default'])) : '#ffffff';
	}
	function tokenHex(token) { return refHex(value(token)); }
	function luminance(h) {
		return [1, 3, 5].map(function (i) { var c = parseInt(h.substr(i, 2), 16) / 255; return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); })
			.reduce(function (s, c, i) { return s + c * [0.2126, 0.7152, 0.0722][i]; }, 0);
	}
	function contrast(a, b) { var x = luminance(a), y = luminance(b); return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05); }

	/* ---- the site-wide settings ---- */
	function widthBase() { return BASE ? BASE.contentWidth : WIDTH.theme; }
	function widthChanged() { return !!WIDTH && state.contentWidth !== widthBase(); }
	function widthName(w) { return w === 'full' ? 'Full width' : 'Maximum width, ' + w + ' pixels'; }
	function colourBase(key) { return BASE ? BASE.colours[key] : DATA.colours[key]['default']; }
	function colourChanged(key) { return String(state.colours[key]).toLowerCase() !== String(colourBase(key)).toLowerCase(); }
	// for the preview, which is drawn against the theme's own: whether it differs from that
	function colourThemed(key) { return String(state.colours[key]).toLowerCase() !== String(DATA.colours[key]['default']).toLowerCase(); }
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
	// for the preview: whether a font differs from the theme's own
	function fontThemed(which) { return state.fonts[which] !== 'geist' && !(state.fonts[which] === 'custom' && !customName(which)); }
	// whether a font differs from what it is measured against: the site appearance's, for a look
	function fontChanged(which) {
		if (!BASE) { return fontThemed(which); }
		if (state.fonts[which] !== BASE.fonts[which]) { return !(state.fonts[which] === 'custom' && !customName(which)); }
		return state.fonts[which] === 'custom' && customName(which) !== (BASE.fonts[which + 'custom'] || '').replace(/[^a-zA-Z0-9 ]/g, '').trim();
	}
	function fontShown(which) {
		if (state.fonts[which] === 'custom') { return customName(which) || 'Other Google Font (no name yet)'; }
		return fontLabel(which === 'main' ? 'main' : 'heading', state.fonts[which]).replace(/ \(default\)$/, '');
	}
	// the name of the font a part set to the main or secondary font is in
	function fontName(role) {
		var main = fontShown('main');
		return (role === 'secondary' && fontThemed('heading')) ? fontShown('heading') : main;
	}
	function uploadChanged(key) { return !!(state.uploads[key].file || state.uploads[key].removed); }
	// whether a look has an image of its own, rather than the site appearance's
	function lookOwn(key) { return !!(BASE && savedUploads[key] && savedUploads[key] !== BASE.uploads[key]); }
	function uploadUrl(key) {
		var u = state.uploads[key];
		return u.file || (u.removed ? (BASE ? BASE.uploads[key] : '') : savedUploads[key]);
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
	function section(token) {
		var t = type(token);
		if (t === 'colour') { return entry(token).text ? 'text' : 'colour'; }
		return { text: 'text', weight: 'text', leading: 'text', font: 'text', radius: 'shape' }[t] || 'space';
	}
	// a setting's name where its part isn't already said, as in the Changes list:
	// "Button: Background", rather than just "Background"
	function fullLabel(token) {
		var e = entry(token);
		return isSize(token) ? e.label : MAP.components[home(token)].name + ': ' + lower(e.label);
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
		Object.keys(DATA.colours).forEach(function (key) {
			var c = DATA.colours[key];
			Object.keys(c.tokens).forEach(function (token) {
				if (colourThemed(key)) { root.style.setProperty(token, c.tokens[token].split('%s').join(state.colours[key]).replace(/%%/g, '%')); }
				else { restore(root, token); }
			});
		});
		[['main', '--fz-font-sans'], ['heading', '--fz-font-heading']].forEach(function (pair) {
			var font = fontFor(pair[0]);
			if (font && fontThemed(pair[0])) { root.style.setProperty(pair[1], font.stack); }
			else { restore(root, pair[1]); }
			if (font) { loadFont(d, font.google); }
		});
		// every part's own settings, and the tokens that follow them (the main
		// button's hover colour), which are put back to their Default otherwise
		Object.keys(MAP.tokens).forEach(function (token) {
			var e = entry(token), v = css(token, value(token));
			root.style.setProperty(token, v);
			Object.keys(e.derived || {}).forEach(function (d) {
				if (String(value(token)) !== String(e['default'])) { root.style.setProperty(d, e.derived[d].split('%s').join(v)); }
				else { root.style.removeProperty(d); }
			});
		});
		if (WIDTH) {
			if (state.contentWidth !== WIDTH.theme) { root.style.setProperty('--formulize-content-max-width', state.contentWidth === 'full' ? '100%' : state.contentWidth + 'px'); }
			else { restore(root, '--formulize-content-max-width'); }
			// and the body's class for it, which a theme can lay pages out differently
			// by, as Lyris puts a list in a card
			if (d.body) { d.body.classList.toggle('formulize-max-width', state.contentWidth !== 'full'); }
		}
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
	// a corner that can be fully rounded has one more stop at the end of its slider
	function sliderValue(token, v) { return (type(token) === 'radius' && Number(v) >= FULL) ? entry(token).max + 1 : v; }
	function fromSlider(token, v) { return (type(token) === 'radius' && entry(token).full && Number(v) > entry(token).max) ? FULL : Number(v); }
	function sliderMax(token, l) { return (type(token) === 'radius' && entry(token).full) ? l.max + 1 : l.max; }
	function widgetFor(token, id) {
		var e = entry(token), t = e.type, l = limits(token), v = value(token);
		if (t === 'font') {
			return { widget: '<div class="formulize-editor__seg formulize-editor__seg--full formulize-editor__seg--fonts" role="group" aria-labelledby="' + id + '">' + ['main', 'secondary'].map(function (r) {
				return '<button type="button" data-token="' + token + '" data-v="' + r + '" aria-pressed="' + (v === r) + '">' + (r === 'main' ? 'Main font' : 'Secondary font') +
					'<small>' + esc(fontName(r)) + (r === 'secondary' && !fontChanged('heading') ? ', the same as the main font' : '') + '</small></button>';
			}).join('') + '</div>', range: '', bare: true };
		}
		if (t === 'colour') {
			var open = state.openColour === token, hex = tokenHex(token), c = MAP.colours[v];
			var w = '<button type="button" class="formulize-editor__cw" id="' + id + '" data-cw="' + token + '" aria-expanded="' + open + '"><span class="formulize-editor__swatch" style="background:' + hex + '"></span>' +
				'<span class="formulize-editor__cw-name">' + esc(show(token, v)) + '</span><span class="formulize-editor__cw-from">' + (c ? (c.from ? 'from ' + esc(DATA.colours[c.from].label) : 'the theme’s') : 'its own') + '</span><span class="formulize-editor__cw-chev" aria-hidden="true">⌄</span></button>';
			if (open) {
				w += '<div class="formulize-editor__cw-pop"><p class="formulize-editor__cw-head">The site’s colours</p><div class="formulize-editor__cw-grid">' + MAP.types.colour.values.map(function (name) {
					return '<button type="button" class="formulize-editor__cw-opt" data-token="' + token + '" data-v="' + name + '" aria-pressed="' + (v === name) + '"><span class="formulize-editor__swatch" style="background:' + refHex(name) + '"></span><span>' + esc(MAP.colours[name].label) + '</span></button>';
				}).join('') + '</div>' +
				'<p class="formulize-editor__cw-head">A colour of its own</p><div class="formulize-editor__cw-custom"><input type="color" data-token="' + token + '" data-custom="1" value="' + hex + '" aria-label="Pick a colour"><input type="text" data-token="' + token + '" data-hex="1" value="' + hex + '" aria-label="Hex colour" spellcheck="false" maxlength="7"><span>Doesn’t follow the site’s colours.</span></div></div>';
			}
			return { widget: w, range: '', bare: true };
		}
		if (l.values) {
			return { widget: '<select id="' + id + '" data-token="' + token + '">' + l.values.map(function (x) {
				return '<option value="' + esc(x) + '"' + (String(x) === String(v) ? ' selected' : '') + '>' + esc(x) + (t === 'text' ? ' · ' + TEXT_PX[x] + 'px' : '') + '</option>';
			}).join('') + '</select>', range: '' };
		}
		var step = MAP.types[t].step, max = sliderMax(token, l), sv = sliderValue(token, v);
		return {
			widget: '<button type="button" class="formulize-editor__step" data-token="' + token + '" data-d="-1" aria-label="Smaller"' + (sv <= l.min ? ' disabled' : '') + '>−</button>' +
				'<input type="range" id="' + id + '" data-token="' + token + '" min="' + l.min + '" max="' + max + '" step="' + step + '" value="' + sv + '">' +
				'<button type="button" class="formulize-editor__step" data-token="' + token + '" data-d="1" aria-label="Larger"' + (sv >= max ? ' disabled' : '') + '>+</button>',
			range: '<div class="formulize-editor__range"><span>' + show(token, l.min) + '</span><span>' + show(token, fromSlider(token, max)) + '</span></div>'
		};
	}
	// the value, and what it is measured against; a font or a colour shows its
	// value in its control, so only says so when it has been changed
	function valHtml(token) {
		var v = value(token), t = type(token);
		var from = changed(token)
			? (EDIT ? 'changed by this look · ' : 'changed · ') + lower(baseName()) + ' is ' + show(token, presetValue(token))
			: (BASE ? 'from the site appearance' : 'the theme’s own');
		if (t === 'colour' || t === 'font') { return changed(token) ? '<span class="formulize-editor__from">' + esc(from) + '</span>' : ''; }
		return '<b>' + esc(show(token, v)) + '</b><span class="formulize-editor__from">' + esc(from) + '</span>';
	}
	function measuredHeight(token) {
		var d = doc(), sel = MEASURED[token];
		if (!d || !sel) { return 0; }
		var el = d.querySelector(sel);
		return el ? Math.round(el.getBoundingClientRect().height) : 0;
	}
	function note(text, icon, kind) {
		return '<p class="formulize-editor__note' + (kind ? ' formulize-editor__note--' + kind : '') + '">' + (icon ? ICON[icon] : '') + '<span>' + text + '</span></p>';
	}
	function notesHtml(token) {
		var l = limits(token), v = value(token), notes = '';
		if (state.raised[token]) { notes += note(esc('Raised to ' + show(token, v) + ' so it fits ' + l.why + '.')); }
		else if (l.why && v === l.min) { notes += note(esc('This is as small as it goes with ' + l.why + '.')); }
		var h = measuredHeight(token);
		if (h && h > v * 4 + 1) { notes += note(esc('Shown at ' + h + 'px here: the theme keeps it at least that tall at this width.'), state.phone ? 'phone' : ''); }
		CONTRAST.forEach(function (pair) {
			if (pair[0] !== token && pair[1] !== token) { return; }
			var ratio = contrast(tokenHex(pair[0]), tokenHex(pair[1])), need = pair[2] ? 3 : 4.5;
			if (ratio >= need) { return; }
			var other = pair[0] === token ? pair[1] : pair[0];
			notes += note(esc((pair[0] === token ? 'This text on ' + lower(fullLabel(other)) : lower(fullLabel(other)).replace(/^./, function (c) { return c.toUpperCase(); }) + ' on this') +
				' has a contrast of ') + '<b>' + ratio.toFixed(1) + ':1</b>' + esc('. Text needs ' + need + ':1 to be easy to read.'), 'warn', 'bad');
		});
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
			inner = (w.bare ? w.widget : '<div class="formulize-editor__widget">' + w.widget + '</div>') + w.range + '<div class="formulize-editor__val">' + valHtml(token) + '</div>' + notesHtml(token);
		}
		var labelTag = (e.type === 'font') ? '<span class="formulize-editor__lbl" id="' + id + '">' + esc(e.label) + '</span>' : '<label for="' + id + '">' + esc(e.label) + '</label>';
		return '<div class="formulize-editor__ctl' + (resets.length ? ' is-changed' : '') + '" data-ctl="' + token + '">' +
			'<div class="formulize-editor__ctl-head">' + labelTag + (resets.length ? '<button type="button" class="formulize-editor__link" data-reset="' + resets.join(' ') + '">Reset</button>' : '') + '</div>' +
			'<p class="formulize-editor__desc">' + esc(phone ? e.description.replace(/\.$/, '') + ', on desktop and on phones.' : e.description) + '</p>' + inner +
			(others.length ? '<p class="formulize-editor__shared">Also changes ' + esc(others.join(', ')) + '.</p>' : '') +
		'</div>';
	}
	function gapsBlock(list) {
		if (!list || !list.length) { return ''; }
		return '<details class="formulize-editor__gaps"><summary>Not adjustable yet</summary><ul>' + list.map(function (g) { return '<li>' + esc(g) + '</li>'; }).join('') + '</ul></details>';
	}
	function partBlock(c) {
		return SECTIONS.map(function (s) {
			var tokens = c.tokens.filter(function (t) { return section(t) === s[0]; });
			return tokens.length ? sect(s[1]) + tokens.map(control).join('') : '';
		}).join('') + gapsBlock(c.gaps);
	}

	/* ---- the inspector: the site-wide settings ---- */
	function sect(label) { return '<p class="formulize-editor__sect">' + esc(label) + '</p>'; }
	function uploadBlock(key) {
		var url = uploadUrl(key), isLogo = key === 'appearance_logo';
		var thumb = url ? '<img src="' + esc(url) + '" alt="">' : (isLogo ? '<img src="' + esc(DATA.themeLogoUrl) + '" alt="">' : '<span>—</span>');
		var desc = isLogo ? 'At the top left of every page.' : 'The small icon in the browser tab and in bookmarks.';
		var status = state.uploads[key].file ? 'A new image, saved when you save.'
			: BASE ? (lookOwn(key) && !state.uploads[key].removed ? 'This look’s own.' : 'The site appearance’s.')
			: (url ? 'Uploaded.' : 'The theme’s own.');
		var removable = BASE ? ((lookOwn(key) && !state.uploads[key].removed) || state.uploads[key].file) : url;
		return '<div class="formulize-editor__ctl' + (uploadChanged(key) ? ' is-changed' : '') + '">' +
			'<div class="formulize-editor__ctl-head"><span class="formulize-editor__lbl">' + UPLOADS[key] + '</span>' + (uploadChanged(key) ? '<button type="button" class="formulize-editor__link" data-upload-undo="' + key + '">Undo</button>' : '') + '</div>' +
			'<p class="formulize-editor__desc">' + desc + '</p>' +
			'<div class="formulize-editor__upload"><span class="formulize-editor__thumb' + (isLogo ? '' : ' formulize-editor__thumb--icon') + '">' + thumb + '</span>' +
				'<span class="formulize-editor__upload-acts"><label class="formulize-editor__btn formulize-editor__btn--sm" for="formulize-editor-' + key + '">Upload…</label>' +
				(removable ? '<button type="button" class="formulize-editor__btn formulize-editor__btn--sm" data-upload-remove="' + key + '">' + (BASE ? 'Use the site appearance’s' : 'Use the theme’s own') + '</button>' : '') +
				'<span class="formulize-editor__upload-status">' + esc(status) + ' ' + esc(DATA.uploads[key].types) + '.</span></span></div>' +
		'</div>';
	}
	function fontBlock(which) {
		var list = which === 'main' ? 'main' : 'heading', id = 'formulize-editor-font-' + which;
		var isCustom = state.fonts[which] === 'custom';
		var desc = which === 'main'
			? 'Fields, buttons, lists and help text, and any other part set to the main font.'
			: 'Titles and field labels, and any other part set to the secondary font. Leave it on the main font to use one font throughout.';
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
		var h = '<p class="formulize-editor__intro">' + (EDIT
			? 'What this look changes, site-wide. Anything it doesn’t change comes from the site appearance, and follows it when that changes. Click anything in the preview to change just that part.'
			: 'The same settings as the Appearance page: the site appearance, which every look starts from. Every part of the interface follows them, except for anything changed on the part itself: click anything in the preview to change just that part, its fonts, colours, corners and sizes.') + '</p>';
		h += sect('Logo') + uploadBlock('appearance_logo') + uploadBlock('appearance_favicon');
		h += sect('Colours') + Object.keys(DATA.colours).map(function (key) {
			var c = DATA.colours[key], id = 'formulize-editor-colour-' + key;
			return '<div class="formulize-editor__colour' + (colourChanged(key) ? ' is-changed' : '') + '"><input type="color" id="' + id + '" data-colour="' + key + '" value="' + esc(state.colours[key]) + '">' +
				'<label for="' + id + '" class="formulize-editor__lbl">' + esc(c.label) + '</label>' +
				(colourChanged(key) ? '<button type="button" class="formulize-editor__link" data-colour-reset="' + key + '">' + (BASE ? 'Use the site appearance’s' : 'Reset to ' + esc(c['default'])) + '</button>' : '<span class="formulize-editor__hex">' + esc(state.colours[key]) + '</span>') +
				'<span class="formulize-editor__desc">' + esc(c.description) + '</span></div>';
		}).join('');
		h += sect('Fonts') + fontBlock('main') + fontBlock('heading');
		if (WIDTH) { h += sect('Size') + widthBlock(); }
		if (!EDIT && Object.keys(DATA.looks).length > 1) {
			h += sect('Look') + '<div class="formulize-editor__ctl' + (state.preset !== '' ? ' is-changed' : '') + '"><div class="formulize-editor__ctl-head"><label for="formulize-editor-look">Look</label></div>' +
				'<p class="formulize-editor__desc">The look of the site. Every look starts from the settings here, and changes some of them: Compact makes everything smaller, and Comfortable larger and roomier. The preview shows the settings here, without the look.</p>' +
				'<div class="formulize-editor__widget"><select id="formulize-editor-look" data-look="1">' + Object.keys(DATA.looks).map(function (key) {
					return '<option value="' + esc(key) + '"' + (state.preset === key ? ' selected' : '') + '>' + esc(DATA.looks[key].name) + '</option>';
				}).join('') + '</select></div></div>';
		}
		return h;
	}
	// the page width: a maximum width in pixels, or full width
	function widthBlock() {
		var full = state.contentWidth === 'full';
		return '<div class="formulize-editor__ctl' + (widthChanged() ? ' is-changed' : '') + '"><div class="formulize-editor__ctl-head"><span class="formulize-editor__lbl" id="formulize-editor-width-label">Page width</span>' +
				(widthChanged() ? '<button type="button" class="formulize-editor__link" data-site-reset="contentwidth">Reset</button>' : '') + '</div>' +
			'<p class="formulize-editor__desc">How wide pages can get on a wide screen. A maximum width keeps forms, lists and the links along the top of the page beside the menu. The theme’s own is ' + esc(lower(widthName(WIDTH.theme))) + '. The preview is narrower than most screens, so it may not show the difference.</p>' +
			'<div class="formulize-editor__seg formulize-editor__seg--full" role="group" aria-labelledby="formulize-editor-width-label">' +
				'<button type="button" data-cwidth="max" aria-pressed="' + !full + '">Maximum width</button>' +
				'<button type="button" data-cwidth="full" aria-pressed="' + full + '">Full width</button></div>' +
			(full ? '' : '<label class="formulize-editor__custom-font"><span>Maximum width, in pixels, from ' + WIDTH.min + ' to ' + WIDTH.max + '</span><input type="number" data-cwidth-px="1" min="' + WIDTH.min + '" max="' + WIDTH.max + '" step="1" value="' + esc(state.contentWidth) + '"></label>') +
		'</div>';
	}
	// a width typed in, kept to the limits; anything that isn't a number leaves it as it was
	function setWidthPx(v) {
		var n = Math.round(Number(v));
		if (String(v).trim() === '' || !isFinite(n)) { return; }
		state.contentWidth = String(Math.min(WIDTH.max, Math.max(WIDTH.min, n)));
	}
	// everything that differs from the theme's own, site-wide and on parts
	function siteChanges() {
		var list = [];
		Object.keys(UPLOADS).forEach(function (key) {
			if (BASE ? (uploadChanged(key) || lookOwn(key)) : (uploadUrl(key) || uploadChanged(key))) {
				list.push({ name: UPLOADS[key], val: state.uploads[key].file ? 'New image' : (state.uploads[key].removed ? (BASE ? 'Back to the site appearance’s' : 'Back to the theme’s own') : (BASE ? 'This look’s own' : 'Uploaded')), reset: 'upload:' + key, resetLabel: uploadChanged(key) ? 'Undo' : (BASE ? 'Use the site appearance’s' : 'Use the theme’s own') });
			}
		});
		Object.keys(DATA.colours).forEach(function (key) {
			if (colourChanged(key)) { list.push({ name: DATA.colours[key].label, swatch: state.colours[key], val: state.colours[key] + ' · ' + lower(baseName()) + ' is ' + colourBase(key), reset: 'colour:' + key }); }
		});
		if (fontChanged('main')) { list.push({ name: 'Main font', val: fontShown('main'), reset: 'font:main' }); }
		if (fontChanged('heading')) { list.push({ name: 'Secondary font', val: fontShown('heading'), reset: 'font:heading' }); }
		if (state.preset !== '') { list.push({ name: 'Look', val: lookName(state.preset), reset: 'preset' }); }
		if (widthChanged()) { list.push({ name: 'Page width', val: widthName(state.contentWidth) + ' · ' + lower(baseName()) + ' is ' + lower(widthName(widthBase())), reset: 'contentwidth' }); }
		return list;
	}
	function changeCount() { return siteChanges().length + Object.keys(state.overrides).length; }
	function changesBlock() {
		var site = siteChanges(), parts = Object.keys(state.overrides), h = '';
		if (!site.length && !parts.length) {
			return '<div class="formulize-editor__empty"><p>' + (EDIT ? 'This look doesn’t change anything: it is the same as the site appearance.' : 'Nothing has been changed: everything is as the theme has it.') + '</p><p>Change the logo, colours, fonts' + (WIDTH ? ', page width' : '') + ' and look under Site-wide, or click anything in the preview, such as a button, a field or a list row, to change just that part. A change to a part applies to every one of it on the site.</p></div>';
		}
		if (site.length) {
			h += sect('Site-wide') + site.map(function (c) {
				return '<div class="formulize-editor__chg"><span class="formulize-editor__chg-name">' + esc(c.name) + '</span>' +
					'<span class="formulize-editor__chg-acts"><button type="button" class="formulize-editor__link" data-site-reset="' + esc(c.reset) + '">' + esc(c.resetLabel || 'Reset') + '</button></span>' +
					'<span class="formulize-editor__chg-val">' + (c.swatch ? '<span class="formulize-editor__swatch formulize-editor__swatch--sm" style="background:' + esc(c.swatch) + '"></span>' : '') + '<b>' + esc(c.val) + '</b></span></div>';
			}).join('');
		}
		if (parts.length) {
			h += sect('Parts') + parts.map(function (token) {
				return '<div class="formulize-editor__chg"><span class="formulize-editor__chg-name">' + (DESKTOP_OF[token] ? ICON.phone + ' ' : '') + esc(fullLabel(token)) + '</span>' +
					'<span class="formulize-editor__chg-acts"><button type="button" class="formulize-editor__link formulize-editor__link--plain" data-select="' + home(token) + '">Show</button><button type="button" class="formulize-editor__link" data-reset="' + token + '">Reset</button></span>' +
					'<span class="formulize-editor__chg-part">' + esc(usedBy(token).map(function (k) { return MAP.components[k].name; }).join(', ')) + '</span>' +
					'<span class="formulize-editor__chg-val">' + (type(token) === 'colour' ? '<span class="formulize-editor__swatch formulize-editor__swatch--sm" style="background:' + tokenHex(token) + '"></span>' : '') +
						'<b>' + esc(show(token, value(token))) + '</b> · ' + esc(baseName(token)) + ' is ' + esc(show(token, presetValue(token))) + '</span></div>';
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
			body.innerHTML = c.site === 'logo' ? uploadBlock('appearance_logo') + uploadBlock('appearance_favicon') : partBlock(c);
		} else {
			var n = changeCount();
			head.innerHTML = '<div class="formulize-editor__kicker">Nothing selected</div>' +
				'<div class="formulize-editor__seg formulize-editor__seg--full" role="tablist" aria-label="Settings">' +
					'<button type="button" role="tab" data-tab="site" aria-pressed="' + (state.tab === 'site') + '" aria-selected="' + (state.tab === 'site') + '">Site-wide</button>' +
					'<button type="button" role="tab" data-tab="changes" aria-pressed="' + (state.tab === 'changes') + '" aria-selected="' + (state.tab === 'changes') + '">Changes' + (n ? ' (' + n + ')' : '') + '</button></div>';
			body.innerHTML = state.tab === 'site' ? siteBlock() : changesBlock() + gapsBlock(MAP.gaps);
		}
		// the look being edited, said once above its settings; a built-in one can't be changed
		if (READONLY) {
			body.insertAdjacentHTML('afterbegin', '<div class="formulize-editor__lookbar formulize-editor__lookbar--ro"><p><b>' + esc(EDIT.name) + '</b> comes with Formulize, so it can’t be changed. ' + esc(EDIT.description) + '</p><p>To make a look of your own from it, duplicate it.</p>' +
				'<button type="button" class="formulize-editor__btn formulize-editor__btn--sm" data-look-act="duplicate" data-allow="1">Duplicate ' + esc(EDIT.name) + '…</button></div>');
			Array.prototype.forEach.call(body.querySelectorAll('input, select, textarea, button:not([data-allow]):not([data-select]), label.formulize-editor__btn'), function (el) {
				if (el.tagName === 'LABEL') { el.hidden = true; } else { el.disabled = true; }
			});
		} else if (EDIT) {
			body.insertAdjacentHTML('afterbegin', '<div class="formulize-editor__lookbar"><p>Editing the <b>' + esc(EDIT.name) + '</b> look: what it changes is marked, and everything else comes from the site appearance.</p></div>');
		}
	}

	/* ---- applying changes ---- */
	function render() {
		enforce();
		applyTokens();
		var n = changeCount(), count = $('formulize-editor-count');
		count.hidden = n === 0; count.textContent = n + (n === 1 ? ' change' : ' changes');
		$('formulize-editor-reset').disabled = n === 0 || READONLY;
		$('formulize-editor-save').disabled = READONLY || state.saving;
		drawLookHeader();
		// keep focus, and the inspector's scroll position, across the re-render
		var f = document.activeElement, attrs = ['data-token', 'data-d', 'data-v', 'data-cw', 'data-custom', 'data-hex', 'data-colour', 'data-font', 'data-custom-font', 'data-look', 'data-cwidth', 'data-cwidth-px', 'data-tab'];
		var key = (f && f.getAttribute && (body.contains(f) || head.contains(f))) ? attrs.map(function (a) { return f.getAttribute(a) || ''; }) : null;
		var scroll = body.parentNode.scrollTop;
		renderInspector();
		body.parentNode.scrollTop = scroll;
		if (key && key.join('')) {
			var again = Array.prototype.filter.call(document.querySelectorAll('#formulize-editor-insp-head *, #formulize-editor-insp-body *'), function (el) {
				return el.tagName === f.tagName && attrs.every(function (a, i) { return (el.getAttribute(a) || '') === key[i]; });
			})[0];
			if (again && !again.disabled) { again.focus({ preventScroll: true }); }
		}
	}
	function touch() { state.dirty = true; }
	function store(token, v) {
		var t = type(token);
		v = (t === 'text' || t === 'font' || t === 'colour') ? String(v) : fromSlider(token, v);
		if (v === presetValue(token)) { delete state.overrides[token]; } else { state.overrides[token] = v; }
		touch();
	}
	function setValue(token, v) { store(token, v); render(); pulse(); }
	// while a slider or a colour picker is dragged, only the preview and that
	// setting's value change: rebuilding the inspector would replace the control
	// under the pointer
	function dragValue(input) {
		var token = input.getAttribute('data-token');
		store(token, input.value); enforce(); applyTokens();
		var ctl = input.closest('.formulize-editor__ctl');
		ctl.querySelector('.formulize-editor__val').innerHTML = valHtml(token);
		ctl.classList.toggle('is-changed', changed(token));
		var cw = ctl.querySelector('.formulize-editor__cw');
		if (cw) {
			cw.querySelector('.formulize-editor__swatch').style.background = tokenHex(token);
			cw.querySelector('.formulize-editor__cw-name').textContent = show(token, value(token));
			cw.querySelector('.formulize-editor__cw-from').textContent = 'its own';
			Array.prototype.forEach.call(ctl.querySelectorAll('.formulize-editor__cw-opt'), function (o) { o.setAttribute('aria-pressed', 'false'); });
			var other = ctl.querySelector(input.getAttribute('data-hex') ? '[data-custom]' : '[data-hex]');
			if (other) { other.value = tokenHex(token); }
		}
	}
	// changing the phone value shows the phone preview, and the desktop value the desktop one
	function deviceFor(token) { return DESKTOP_OF[token] ? 'phone' : (PHONE_OF[token] ? 'desktop' : null); }

	function select(part, origin) {
		state.sel = part; state.origin = origin; state.openColour = null;
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
	function resetFont(which) {
		state.fonts[which] = BASE ? BASE.fonts[which] : 'geist';
		state.fonts[which + 'custom'] = BASE ? BASE.fonts[which + 'custom'] : '';
	}

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
		if (a('data-hex')) { if (hex6(el.value) && el.value.length === 7) { el.value = hex6(el.value); dragValue(el); } return; }
		if (el.type === 'range' || a('data-custom')) { dragValue(el); } else { setWidth(deviceFor(token)); setValue(token, el.value); }
	});
	body.addEventListener('change', function (ev) {
		var el = ev.target, a = function (n) { return el.getAttribute && el.getAttribute(n); };
		if (el.type === 'range' || a('data-colour') || a('data-custom-font') || a('data-custom') || a('data-hex')) { render(); pulse(); }
		else if (a('data-font')) { state.fonts[a('data-font')] = el.value; touch(); render(); }
		else if (a('data-look')) { state.preset = el.value; touch(); render(); }
		else if (a('data-cwidth-px')) { setWidthPx(el.value); touch(); render(); }
	});
	body.addEventListener('click', function (ev) {
		var b = ev.target.closest('button'); if (!b) { return; }
		var a = function (n) { return b.getAttribute(n); }, parts, token;
		if (a('data-reset')) {
			a('data-reset').split(' ').forEach(function (t) { delete state.overrides[t]; });
			touch(); render(); pulse();
		} else if (a('data-d')) {
			token = a('data-token');
			var l = limits(token), max = sliderMax(token, l), sv = Number(sliderValue(token, value(token)));
			setValue(token, Math.min(max, Math.max(l.min, sv + Number(a('data-d')) * MAP.types[type(token)].step)));
		} else if (a('data-cw')) {
			state.openColour = state.openColour === a('data-cw') ? null : a('data-cw'); render();
		} else if (a('data-v') && a('data-token')) {
			if (b.classList.contains('formulize-editor__cw-opt')) { state.openColour = null; } // a colour picked: done
			setValue(a('data-token'), a('data-v'));
		} else if (a('data-select')) {
			select(a('data-select'), null);
		} else if (a('data-cwidth')) {
			// a maximum width starts at the theme's own, or the usual one when that is full width
			if (a('data-cwidth') === 'full') { state.contentWidth = 'full'; }
			else if (state.contentWidth === 'full') { state.contentWidth = WIDTH.theme !== 'full' ? WIDTH.theme : String(WIDTH['default']); }
			touch(); render();
		} else if (a('data-colour-reset')) {
			state.colours[a('data-colour-reset')] = colourBase(a('data-colour-reset')); touch(); render();
		} else if (a('data-font-reset')) {
			resetFont(a('data-font-reset')); touch(); render();
		} else if (a('data-upload-undo')) {
			resetUpload(a('data-upload-undo')); touch(); render();
		} else if (a('data-upload-remove')) {
			resetUpload(a('data-upload-remove'));
			state.uploads[a('data-upload-remove')].removed = BASE ? lookOwn(a('data-upload-remove')) : !!savedUploads[a('data-upload-remove')]; touch(); render();
		} else if (a('data-look-act')) {
			ev.stopPropagation(); // it opens the looks' menu, which a click elsewhere closes
			lookAction(a('data-look-act'));
		} else if (a('data-site-reset')) {
			parts = a('data-site-reset').split(':');
			if (parts[0] === 'colour') { state.colours[parts[1]] = colourBase(parts[1]); }
			else if (parts[0] === 'font') { resetFont(parts[1]); }
			else if (parts[0] === 'preset') { state.preset = ''; }
			else if (parts[0] === 'contentwidth') { state.contentWidth = widthBase(); }
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
		if (!window.confirm(EDIT
			? 'Take back everything this look changes, so it is the same as the site appearance? Nothing changes until you save.'
			: 'Put everything back to how the ' + DATA.theme + ' theme has it: the logo and favicon, colours, fonts' + (WIDTH ? ', page width' : '') + ', the look and every part’s own settings? Nothing changes on the site until you save.')) { return; }
		state.overrides = {};
		if (!EDIT) { state.preset = ''; }
		if (WIDTH) { state.contentWidth = widthBase(); }
		Object.keys(DATA.colours).forEach(function (key) { state.colours[key] = colourBase(key); });
		resetFont('main'); resetFont('heading');
		Object.keys(UPLOADS).forEach(function (key) { resetUpload(key); state.uploads[key].removed = BASE ? lookOwn(key) : !!savedUploads[key]; });
		touch(); render();
	});

	/* ---- looks: what is being edited, and the looks' menu ---- */
	var menu = $('formulize-editor-look-menu'), lookBtn = $('formulize-editor-look-btn'), menuMode = '';
	function lookUrl(key) { return DATA.pageUrl + (key ? '&look=' + encodeURIComponent(key) : ''); }
	function drawLookHeader() {
		$('formulize-editor-look-name').textContent = EDIT ? EDIT.name : 'Site appearance';
		var box = $('formulize-editor-applied');
		if (!EDIT) { box.innerHTML = ''; return; }
		box.innerHTML = DATA.applied === EDIT.key
			? '<span class="formulize-editor__applied">✓ Applied to the site</span>'
			: '<button type="button" class="formulize-editor__btn" data-look-act="apply"' + (state.dirty ? ' disabled title="Save the look first"' : '') + '>Apply to the site</button>';
	}
	function drawMenu() {
		var h = '';
		if (menuMode === 'new' || menuMode === 'duplicate' || menuMode === 'rename') {
			var intro = menuMode === 'new' ? 'A new look starts out the same as the site appearance. Change what you want it to change.'
				: menuMode === 'duplicate' ? 'A copy of ' + EDIT.name + ', to change as you like. It isn’t linked to ' + EDIT.name + ': changes to one don’t change the other.'
				: 'A new name for ' + EDIT.name + '.';
			h = '<form class="formulize-editor__look-form" data-look-form="' + menuMode + '"><p>' + esc(intro) + '</p>' +
				'<label><span>Name</span><input type="text" id="formulize-editor-look-input" maxlength="60" required value="' + esc(menuMode === 'rename' ? EDIT.name : menuMode === 'duplicate' ? EDIT.name + ' copy' : '') + '" placeholder="e.g. Summer program"></label>' +
				'<p class="formulize-editor__look-err" id="formulize-editor-look-err" hidden></p>' +
				'<div class="formulize-editor__look-row"><button type="button" class="formulize-editor__btn formulize-editor__btn--sm" data-menu="back">Cancel</button><button type="submit" class="formulize-editor__btn formulize-editor__btn--sm formulize-editor__btn--primary">' + (menuMode === 'rename' ? 'Rename' : 'Make the look') + '</button></div></form>';
		} else if (menuMode === 'delete') {
			h = '<div class="formulize-editor__look-form"><p>Delete the <b>' + esc(EDIT.name) + '</b> look?' + (DATA.applied === EDIT.key ? ' It is applied to the site, so the site goes back to Default.' : '') + ' This can’t be undone.</p>' +
				'<div class="formulize-editor__look-row"><button type="button" class="formulize-editor__btn formulize-editor__btn--sm" data-menu="back">Cancel</button><button type="button" class="formulize-editor__btn formulize-editor__btn--sm formulize-editor__btn--danger" data-look-act="delete-yes">Delete the look</button></div></div>';
		} else {
			var item = function (key, name, small, badges, current) {
				return '<button type="button" class="formulize-editor__look-item" data-edit="' + esc(key) + '"' + (current ? ' aria-current="true"' : '') + '><span class="formulize-editor__look-check">' + (current ? '✓' : '') + '</span><span><span class="formulize-editor__look-title">' + esc(name) + '</span><small>' + esc(small) + '</small></span><span>' + badges + '</span></button>';
			};
			h = item('', 'Site appearance', 'The Appearance page’s settings, which every look starts from', '', !EDIT) +
				'<p class="formulize-editor__look-head">Looks</p>' +
				DATA.menuLooks.map(function (l) {
					var badges = (DATA.applied === l.key ? '<span class="formulize-editor__badge formulize-editor__badge--on">Applied</span>' : '') + (l.builtin ? '<span class="formulize-editor__badge">Built in</span>' : '');
					return item(l.key, l.name, l.builtin ? l.description : (l.changes ? l.changes + (l.changes === 1 ? ' change' : ' changes') + ' to the site appearance' : 'No changes yet'), badges, EDIT && EDIT.key === l.key);
				}).join('') +
				'<div class="formulize-editor__look-sep"></div>' +
				'<button type="button" class="formulize-editor__look-act" data-menu="new">New look…</button>' +
				(EDIT ? '<button type="button" class="formulize-editor__look-act" data-menu="duplicate">Duplicate ' + esc(EDIT.name) + '…</button>' : '') +
				(EDIT && !EDIT.builtin ? '<button type="button" class="formulize-editor__look-act" data-menu="rename">Rename ' + esc(EDIT.name) + '…</button><button type="button" class="formulize-editor__look-act formulize-editor__look-act--danger" data-menu="delete">Delete ' + esc(EDIT.name) + '…</button>' : '');
		}
		menu.innerHTML = h;
		var input = $('formulize-editor-look-input');
		if (input) { input.focus(); input.select(); }
	}
	function openMenu(open, mode) {
		menuMode = mode || '';
		menu.hidden = !open;
		lookBtn.setAttribute('aria-expanded', String(open));
		if (open) { drawMenu(); }
	}
	// a look action goes to the server and back to the editor; unsaved changes would be
	// lost, so they are asked about first
	function leaving() { return !state.dirty || window.confirm('You have changes that aren’t saved. Leave them?'); }
	function postLook(action, name) {
		if (!leaving()) { return; }
		var form = $('formulize-editor-lookform');
		form.querySelector('[name="appearance_look_action"]').value = action;
		form.querySelector('[name="appearance_look_name"]').value = name || '';
		submitting = true;
		form.submit();
	}
	function lookAction(act) {
		if (act === 'apply') { postLook('apply'); }
		else if (act === 'delete-yes') { postLook('delete'); }
		else { openMenu(true, act); } // duplicate, from the read-only note
	}
	function nameTaken(name, except) {
		return DATA.menuLooks.some(function (l) { return l.key !== except && l.name.toLowerCase() === name.toLowerCase(); });
	}
	lookBtn.addEventListener('click', function (ev) { ev.stopPropagation(); openMenu(menu.hidden); });
	menu.addEventListener('click', function (ev) {
		ev.stopPropagation(); // the menu redraws itself, so the click would otherwise read as one outside it
		var b = ev.target.closest('button'); if (!b) { return; }
		if (b.hasAttribute('data-edit')) {
			var key = b.getAttribute('data-edit');
			if ((EDIT ? EDIT.key : '') !== key && leaving()) { submitting = true; location.href = lookUrl(key); }
		} else if (b.getAttribute('data-menu')) {
			openMenu(true, b.getAttribute('data-menu') === 'back' ? '' : b.getAttribute('data-menu'));
		} else if (b.getAttribute('data-look-act')) {
			lookAction(b.getAttribute('data-look-act'));
		}
	});
	menu.addEventListener('submit', function (ev) {
		ev.preventDefault();
		var mode = ev.target.getAttribute('data-look-form'), name = $('formulize-editor-look-input').value.replace(/\s+/g, ' ').trim(), err = $('formulize-editor-look-err');
		var problem = !name ? 'Please give the look a name.' : nameTaken(name, mode === 'rename' ? EDIT.key : null) ? 'There is already a look with that name.' : '';
		if (problem) { err.textContent = problem; err.hidden = false; return; }
		postLook(mode, name);
	});
	document.addEventListener('click', function (ev) { if (!menu.hidden && !ev.target.closest('#formulize-editor-lookpick')) { openMenu(false); } });
	document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && !menu.hidden) { openMenu(false); lookBtn.focus(); } });
	$('formulize-editor-applied').addEventListener('click', function (ev) { var b = ev.target.closest('[data-look-act]'); if (b) { lookAction(b.getAttribute('data-look-act')); } });
	// what a look action did, after it comes back
	if (DATA.done) {
		setTimeout(function () {
			message({ 'new': 'Made the look ' + (EDIT ? EDIT.name : '') + '. Change what you want it to change, and save.', duplicate: 'Made the look ' + (EDIT ? EDIT.name : '') + ', a copy. Change what you want it to change, and save.', rename: 'Renamed the look ' + (EDIT ? EDIT.name : '') + '.', 'delete': 'Deleted the look.', apply: (EDIT ? EDIT.name : 'Default') + ' is now applied to the site.' }[DATA.done] || 'Done.');
		}, 0);
	}

	/* ---- saving ---- */
	// a message under the header, which a save replaces
	function message(text, error) {
		var box = $('formulize-editor-messages');
		box.innerHTML = '';
		(Array.isArray(text) ? text : [text]).forEach(function (t) {
			var p = document.createElement('p');
			p.className = 'formulize-editor__message' + (error ? ' formulize-editor__message--error' : '');
			p.setAttribute('role', error ? 'alert' : 'status');
			p.textContent = t;
			box.appendChild(p);
		});
		if (!error) { clearTimeout(message.timer); message.timer = setTimeout(function () { box.innerHTML = ''; }, 5000); }
	}
	// every setting goes in the form's hidden fields
	function fillForm(form) {
		enforce();
		var values = {
			appearance_look: state.preset,
			appearance_contentwidth: state.contentWidth,
			appearance_overrides: Object.keys(state.overrides).length ? JSON.stringify(state.overrides) : '',
			appearance_font: state.fonts.main,
			appearance_customfont: state.fonts.main === 'custom' ? state.fonts.maincustom : '',
			appearance_headingfont: state.fonts.heading,
			appearance_headingcustomfont: state.fonts.heading === 'custom' ? state.fonts.headingcustom : '',
			appearance_logo_remove: state.uploads.appearance_logo.removed ? '1' : '',
			appearance_favicon_remove: state.uploads.appearance_favicon.removed ? '1' : ''
		};
		Object.keys(DATA.colours).forEach(function (key) { values['appearance_' + key] = state.colours[key]; });
		// a look: the settings that differ from the site appearance's
		if (EDIT) {
			var look = {};
			Object.keys(DATA.colours).forEach(function (key) { if (colourChanged(key)) { look['appearance_' + key] = state.colours[key]; } });
			['main', 'heading'].forEach(function (which) {
				if (fontChanged(which)) {
					look[which === 'main' ? 'appearance_font' : 'appearance_headingfont'] = state.fonts[which] === 'geist' ? '' : state.fonts[which];
					look[which === 'main' ? 'appearance_customfont' : 'appearance_headingcustomfont'] = state.fonts[which] === 'custom' ? state.fonts[which + 'custom'] : '';
				}
			});
			if (widthChanged()) { look.appearance_contentwidth = state.contentWidth === WIDTH.theme ? '' : state.contentWidth; }
			if (Object.keys(state.overrides).length) { look.appearance_overrides = JSON.stringify(state.overrides); }
			values.appearance_look_settings = JSON.stringify(look);
		}
		Array.prototype.forEach.call(form.querySelectorAll('[data-setting]'), function (input) {
			input.value = has(values, input.getAttribute('data-setting')) ? values[input.getAttribute('data-setting')] : '';
		});
	}
	var submitting = false;
	$('formulize-editor-form').addEventListener('submit', function (ev) {
		var form = this;
		fillForm(form);
		if (!window.fetch || !window.FormData) { submitting = true; return; } // the form posts, and the page comes back
		ev.preventDefault();
		if (state.saving) { return; }
		state.saving = true;
		var save = $('formulize-editor-save');
		save.disabled = true; save.textContent = 'Saving…';
		var data = new FormData(form);
		data.append('appearance_editor_save', '1');
		data.append('appearance_editor_ajax', '1');
		fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (r) { return r.json(); })
			.then(function (result) {
				if (result.saved) {
					// what was saved is what the editor now starts from: a custom font with
					// no name, for one, comes back as the theme's own
					Object.keys(UPLOADS).forEach(function (key) { resetUpload(key); });
					adopt(result.state);
					state.dirty = false;
				}
				if (result.errors && result.errors.length) { message(result.errors, true); }
				else if (EDIT) { message('Saved the ' + EDIT.name + ' look.' + (DATA.applied === EDIT.key ? ' It is applied to the site, so the site has changed.' : ' It isn’t applied to the site, so the site hasn’t changed.')); }
				else { message('Saved. These settings now apply across the site in the ' + DATA.theme + ' theme.'); }
				render();
			})['catch'](function () {
				message('Nothing was saved: the site didn’t answer. Check your connection and save again.', true);
			}).then(function () {
				state.saving = false; save.disabled = false; save.textContent = 'Save';
			});
	});
	window.addEventListener('beforeunload', function (ev) {
		if (state.dirty && !submitting) { ev.preventDefault(); ev.returnValue = ''; }
	});
	var saved = document.querySelector('#formulize-editor-messages [role="status"]');
	if (saved) { setTimeout(function () { saved.hidden = true; }, 5000); }

	goScreen(state.screen);
	render();
})();
