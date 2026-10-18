// The appearance editor (modules/formulize/appearance_editor.php): one look, in
// the simple editor or the advanced one, switched in place.
//
// The page shows one of the theme's sample screens in an iframe
// (appearance_preview.php) and sets the settings being edited on it, as inline
// custom properties on its root element, so the preview always shows exactly
// the values being edited. Clicking a part of the preview (anything with
// data-fz-part) selects that part: every one of it is outlined, and the
// inspector shows its settings (its fonts, colours, corners and sizes), which
// apply to every one of it on the site. With nothing selected, the inspector
// shows the look's site-wide settings (logo, colours, fonts, page width, and in the
// simple editor its size) and, on its other tab, what has changed: what hasn't
// been saved, and what the look changes from the look it is based on.
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
	// The editor's words, from the language file: its _AM_APPEARANCE_EDITOR_JS_
	// constants, by the rest of their names (formulize_scriptText() and
	// libraries/formulize-text.js). lang() gives them as text, langHtml() as HTML, with
	// their blanks filled in by what follows, which for langHtml() is HTML already:
	// langHtml('APPLIES_TO', '<strong>' + esc(c.plural) + '</strong>').
	var TEXT = window.formulizeText(DATA.text);
	var lang = TEXT.text;
	var langHtml = TEXT.html;

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
		['--fz-header-text', '--fz-header-bg'], ['--fz-row-text', '--fz-row-bg'], ['--fz-row-text', '--fz-row-hover'],
		['--fz-sidemenu-color', '--fz-sidemenu-bg'], ['--fz-sidemenu-color', '--fz-sidemenu-hover-bg'], ['--fz-sidemenu-sub-color', '--fz-sidemenu-bg'],
		['--fz-sidemenu-active-color', '--fz-sidemenu-active-bg'], ['--fz-sidemenu-active-color', '--fz-sidemenu-sub-active-bg']
	];
	var SECTIONS = [['text', lang('SECTION_TEXT')], ['colour', lang('SECTION_COLOURS')], ['shape', lang('SECTION_CORNERS')], ['line', lang('SECTION_LINES')], ['space', lang('SECTION_SPACE')]];
	var UPLOADS = { appearance_logo: lang('LOGO'), appearance_favicon: lang('FAVICON') };
	// The look being edited (EDIT), measured against the look it is based on (BASE),
	// as that resolves, or against the theme's own appearance when it is based on
	// nothing (BASE is null). Only what differs is the look's own.
	var EDIT = DATA.editing, BASE = DATA.base, READONLY = false;
	// The parts' settings the look records itself, as saved. It can record one that is
	// the same as its base's, and it keeps it, rather than following its base when
	// that changes, unless it is reset.
	var OWN = (EDIT && EDIT.overrides) ? EDIT.overrides : {};
	// The simple editor (the look's logo, colours, fonts, page width and size) or the
	// advanced one (every part of it too). Switched in place: see setMode.
	var SIMPLE = DATA.mode !== 'advanced';
	// the page width, for a theme that has one: its limits, and the theme's own
	// width ('full', or pixels), which is the default
	var WIDTH = DATA.contentWidth;

	var state = {
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
		openColour: null,
		dirty: false,
		saving: false
	};
	var savedUploads = {}; // the logo and favicon as saved: their addresses
	var partsOn = {}; // screen => { part: true }, from the samples' markup
	var $ = function (id) { return document.getElementById(id); };
	var frame = $('formulize-editor-frame'), head = $('formulize-editor-insp-head'), body = $('formulize-editor-insp-body');
	var hint = $('formulize-editor-hint'), DEFAULT_HINT = hint.innerHTML;
	var frameStyle = null; // the editor's own styles in the preview, which depend on the editor

	function copy(o) { return JSON.parse(JSON.stringify(o)); }
	function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
	function entry(token) { return MAP.tokens[token]; }
	function type(token) { return entry(token).type; }
	function has(o, k) { return Object.prototype.hasOwnProperty.call(o, k); }
	function lower(s) { return s.charAt(0).toLowerCase() + s.slice(1); }

	// take on the settings as saved: when the page opens, and after a save. What is
	// saved is kept (SAVED), so the editor can tell what has changed since: its
	// changes are those, not what the look itself changes.
	var SAVED = null;
	function adopt(saved) {
		state.contentWidth = WIDTH ? String(saved.contentWidth || WIDTH.theme) : '';
		state.overrides = Array.isArray(saved.overrides) ? {} : copy(saved.overrides || {});
		state.colours = copy(saved.colours);
		state.fonts = copy(saved.fonts);
		savedUploads = copy(saved.uploads);
		enforce(); // the look's own settings: only what differs from its base's
		SAVED = copy({ contentWidth: state.contentWidth, overrides: state.overrides, colours: state.colours, fonts: state.fonts });
	}

	/* ---- values ---- */
	function isSize(token) { return ['spacing', 'text', 'weight', 'leading', 'measure'].indexOf(type(token)) !== -1; }
	// what a part's setting is when the look doesn't change it: its base's, or the theme's own
	function presetValue(token) { return (BASE && has(BASE.overrides, token)) ? BASE.overrides[token] : entry(token)['default']; }
	function value(token) { return has(state.overrides, token) ? state.overrides[token] : presetValue(token); }
	function changed(token) { return has(state.overrides, token); }
	// what a value is measured against, in a sentence: the base's, or the theme's own
	function baseName() { return BASE ? lang('POSSESSIVE', EDIT.baseName) : lang('THEMES'); }
	function css(token, v) {
		var t = type(token);
		if (t === 'spacing') { return 'calc(var(--fz-spacing) * ' + v + ')'; }
		if (t === 'text') { return 'var(--fz-text-' + v + ')'; }
		if (t === 'measure') { return v + 'ch'; }
		if (t === 'font') { return v === 'secondary' ? 'var(--fz-font-heading)' : 'var(--fz-font-sans)'; }
		if (t === 'colour') { return MAP.colours[v] ? 'var(' + MAP.colours[v].css + ')' : v; }
		if (t === 'radius' || t === 'line') { return v + 'px'; }
		return String(v);
	}
	function show(token, v) {
		var t = type(token);
		if (t === 'spacing') { return (Math.round(v * 4 * 100) / 100) + 'px'; }
		if (t === 'text') { return TEXT_PX[v] + 'px'; }
		if (t === 'measure') { return lang('CHARACTERS', v); }
		if (t === 'font') { return lang('FONT_IS', lang(v === 'secondary' ? 'SECONDARY_FONT' : 'MAIN_FONT'), fontName(v)); }
		if (t === 'colour') { return MAP.colours[v] ? MAP.colours[v].label : String(v).toUpperCase(); }
		if (t === 'radius') { return Number(v) >= FULL ? lang('FULLY_ROUNDED') : v + 'px'; }
		if (t === 'line') { return v + 'px'; }
		if (t === 'keyword') { return (entry(token).labels && entry(token).labels[v]) || String(v); }
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
				why = lang('FLOOR_TEXT', lower(entry(f.text).label), show(f.text, value(f.text)));
			} else {
				min = value(f.token) + f.plus;
				why = lang('FLOOR_TOKEN', lower(entry(f.token).label), show(f.token, value(f.token)));
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
			var recorded = has(OWN, t) && String(OWN[t]) === String(state.overrides[t]);
			if (state.overrides[t] === presetValue(t) && !state.raised[t] && !recorded) { delete state.overrides[t]; }
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
	function widthName(w) { return w === 'full' ? lang('FULL_WIDTH') : lang('MAX_WIDTH_PX', w); }
	function colourBase(key) { return BASE ? BASE.colours[key] : DATA.colours[key]['default']; }
	function colourChanged(key) { return String(state.colours[key]).toLowerCase() !== String(colourBase(key)).toLowerCase(); }
	// for the preview, which is drawn against the theme's own: whether it differs from that
	function colourThemed(key) { return String(state.colours[key]).toLowerCase() !== String(DATA.colours[key]['default']).toLowerCase(); }
	// a font's name, without the note its label may have ("(default)")
	function fontLabel(list, key) {
		var f = DATA.fonts[list].filter(function (x) { return x.key === key; })[0];
		return f ? f.name : key;
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
		if (state.fonts[which] === 'custom') { return customName(which) || lang('UNNAMED_FONT'); }
		return fontLabel(which === 'main' ? 'main' : 'heading', state.fonts[which]);
	}
	// the name of the font a part set to the main or secondary font is in
	function fontName(role) {
		var main = fontShown('main');
		return (role === 'secondary' && fontThemed('heading')) ? fontShown('heading') : main;
	}
	function uploadChanged(key) { return !!(state.uploads[key].file || state.uploads[key].removed); }
	// whether the look has an image of its own, rather than its base's (or the theme's)
	function lookOwn(key) {
		return BASE ? !!(savedUploads[key] && savedUploads[key] !== BASE.uploads[key]) : !!savedUploads[key];
	}
	// what shows when the look's own image is taken away: its base's, or the theme's own
	function fallbackUrl(key) { return BASE ? BASE.uploads[key] : ''; }
	function uploadUrl(key) {
		var u = state.uploads[key];
		return u.file || (u.removed ? fallbackUrl(key) : savedUploads[key]);
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
		return { text: 'text', weight: 'text', leading: 'text', font: 'text', keyword: 'text', radius: 'shape', line: 'line' }[t] || 'space';
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

	// the editor's own styles in the preview: what can be clicked, and what is selected.
	// What is hovered, selected or pulsing is marked with data-fz-editor, not a class,
	// so a part keeps its styles: written content is styled when it has no class.
	// A part that is a region of the page (the page, the side menu) is outlined
	// inside its edges, which are under what is beside it.
	function frameCss() {
		var regions = Object.keys(MAP.components).filter(function (k) { return MAP.components[k].region; }).map(function (k) { return '[data-fz-part="' + k + '"]'; });
		return FRAME_CSS_BASE.replace('%parts%', SIMPLE ? '[data-fz-part="logo"]' : '[data-fz-part]') +
			(regions.length ? ':is(' + regions.join(', ') + '):is([data-fz-editor~="hover"], [data-fz-editor~="sel"]) { outline-offset: -3px !important; }' : '');
	}
	var FRAME_CSS_BASE =
		'%parts% { cursor: pointer; }' +
		'[data-fz-part][data-fz-editor~="hover"] { outline: 1px dashed #3b6fd8 !important; outline-offset: 2px; }' +
		'[data-fz-part][data-fz-editor~="sel"] { outline: 2px solid #3b6fd8 !important; outline-offset: 2px; }' +
		'[data-fz-part][data-fz-editor~="sel"][data-fz-editor~="origin"] { outline-width: 3px !important; }' +
		'@keyframes formulize-editor-pulse { from { box-shadow: 0 0 0 0 rgba(59,111,216,.45); } to { box-shadow: 0 0 0 10px rgba(59,111,216,0); } }' +
		'[data-fz-part][data-fz-editor~="pulse"] { animation: formulize-editor-pulse .7s ease-out; }' +
		'@media (prefers-reduced-motion: reduce) { [data-fz-part][data-fz-editor~="pulse"] { animation: none; } }';

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
		// button's hover colour), which are put back to what they are declared as
		// otherwise, not to the saved stylesheet's, which follows the look applied
		Object.keys(MAP.tokens).forEach(function (token) {
			var e = entry(token), v = css(token, value(token));
			root.style.setProperty(token, v);
			Object.keys(e.derived || {}).forEach(function (d) {
				if (String(value(token)) !== String(e['default'])) { root.style.setProperty(d, e.derived[d].split('%s').join(v)); }
				else { restore(root, d); }
			});
		});
		if (WIDTH) {
			if (state.contentWidth !== WIDTH.theme) { root.style.setProperty('--formulize-content-max-width', state.contentWidth === 'full' ? '100%' : state.contentWidth + 'px'); }
			else { restore(root, '--formulize-content-max-width'); }
		}
		var logo = d.querySelector('[data-fz-part="logo"] img');
		if (logo) { logo.src = uploadUrl('appearance_logo') || DATA.themeLogoUrl; }
	}
	// add or remove one of the editor's marks on a part in the preview
	function mark(el, name, on) {
		var marks = (el.getAttribute('data-fz-editor') || '').split(' ').filter(function (m) { return m && m !== name; });
		if (on) { marks.push(name); }
		if (marks.length) { el.setAttribute('data-fz-editor', marks.join(' ')); } else { el.removeAttribute('data-fz-editor'); }
	}
	function markSelection() {
		var d = doc(); if (!d) { return; }
		Array.prototype.forEach.call(d.querySelectorAll('[data-fz-part]'), function (el) {
			var on = el.getAttribute('data-fz-part') === state.sel;
			mark(el, 'sel', on);
			mark(el, 'origin', on && el === state.origin);
		});
	}
	function pulse() {
		var d = doc(); if (!d || !state.sel) { return; }
		Array.prototype.forEach.call(d.querySelectorAll('[data-fz-part="' + state.sel + '"]'), function (el) {
			mark(el, 'pulse', false); void el.offsetWidth; mark(el, 'pulse', true);
		});
	}
	frame.addEventListener('load', function () {
		var d = doc(); if (!d || !d.documentElement) { return; }
		frameStyle = d.createElement('style'); frameStyle.textContent = frameCss(); d.head.appendChild(frameStyle);
		applyTokens(); markSelection();
		// in the preview, every click selects: links don't go anywhere, buttons
		// don't press, fields don't take focus
		d.addEventListener('mousedown', function (ev) { ev.preventDefault(); }, true);
		d.addEventListener('submit', function (ev) { ev.preventDefault(); }, true);
		d.addEventListener('click', function (ev) {
			ev.preventDefault(); ev.stopPropagation();
			var toggle = ev.target.closest ? ev.target.closest('[data-fz-toggle]') : null;
			// a sample's menu: a popover (popover="manual", so it stays open while its items are changed), or a panel that opens with a class
			if (toggle) { var panel = d.getElementById(toggle.getAttribute('data-fz-toggle')); if (panel) { if (panel.hasAttribute('popover') && panel.togglePopover) { panel.togglePopover(); } else { panel.classList.toggle('open'); } } }
			var el = ev.target.closest ? ev.target.closest(SIMPLE ? '[data-fz-part="logo"]' : '[data-fz-part]') : null;
			select(el && MAP.components[el.getAttribute('data-fz-part')] ? el.getAttribute('data-fz-part') : null, el);
		}, true);
		d.addEventListener('mouseover', function (ev) {
			Array.prototype.forEach.call(d.querySelectorAll('[data-fz-editor~="hover"]'), function (x) { mark(x, 'hover', false); });
			// in simple mode, only the logo is a part to click: everything else is the site's
			var el = ev.target.closest ? ev.target.closest(SIMPLE ? '[data-fz-part="logo"]' : '[data-fz-part]') : null;
			var c = el && MAP.components[el.getAttribute('data-fz-part')];
			if (c) { mark(el, 'hover', true); hint.innerHTML = langHtml('HINT_PART', '<b>' + esc(c.name) + '</b>', esc(c.plural)); }
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
				return '<button type="button" data-token="' + token + '" data-v="' + r + '" aria-pressed="' + (v === r) + '">' + esc(lang(r === 'main' ? 'MAIN_FONT' : 'SECONDARY_FONT')) +
					'<small>' + esc(r === 'secondary' && !fontChanged('heading') ? lang('SAME_AS_MAIN', fontName(r)) : fontName(r)) + '</small></button>';
			}).join('') + '</div>', range: '', bare: true };
		}
		if (t === 'colour') {
			var open = state.openColour === token, hex = tokenHex(token), c = MAP.colours[v];
			var w = '<button type="button" class="formulize-editor__cw" id="' + id + '" data-cw="' + token + '" aria-expanded="' + open + '"><span class="formulize-editor__swatch" style="background:' + hex + '"></span>' +
				'<span class="formulize-editor__cw-name">' + esc(show(token, v)) + '</span><span class="formulize-editor__cw-from">' + esc(c ? (c.from ? lang('FROM', DATA.colours[c.from].label) : lang('THEMES')) : lang('COLOUR_OWN')) + '</span><span class="formulize-editor__cw-chev" aria-hidden="true">⌄</span></button>';
			if (open) {
				w += '<div class="formulize-editor__cw-pop"><p class="formulize-editor__cw-head">' + langHtml('SITE_COLOURS') + '</p><div class="formulize-editor__cw-grid">' + MAP.types.colour.values.map(function (name) {
					return '<button type="button" class="formulize-editor__cw-opt" data-token="' + token + '" data-v="' + name + '" aria-pressed="' + (v === name) + '"><span class="formulize-editor__swatch" style="background:' + refHex(name) + '"></span><span>' + esc(MAP.colours[name].label) + '</span></button>';
				}).join('') + '</div>' +
				'<p class="formulize-editor__cw-head">' + langHtml('OWN_COLOUR') + '</p><div class="formulize-editor__cw-custom"><input type="color" data-token="' + token + '" data-custom="1" value="' + hex + '" aria-label="' + langHtml('PICK_COLOUR') + '"><input type="text" data-token="' + token + '" data-hex="1" value="' + hex + '" aria-label="' + langHtml('HEX_COLOUR') + '" spellcheck="false" maxlength="7"><span>' + langHtml('NOT_FOLLOWING') + '</span></div></div>';
			}
			return { widget: w, range: '', bare: true };
		}
		if (l.values) {
			return { widget: '<select id="' + id + '" data-token="' + token + '">' + l.values.map(function (x) {
				return '<option value="' + esc(x) + '"' + (String(x) === String(v) ? ' selected' : '') + '>' + esc(t === 'keyword' ? show(token, x) : x) + (t === 'text' ? ' · ' + TEXT_PX[x] + 'px' : '') + '</option>';
			}).join('') + '</select>', range: '' };
		}
		var step = MAP.types[t].step, max = sliderMax(token, l), sv = sliderValue(token, v);
		return {
			widget: '<button type="button" class="formulize-editor__step" data-token="' + token + '" data-d="-1" aria-label="' + langHtml('SMALLER') + '"' + (sv <= l.min ? ' disabled' : '') + '>−</button>' +
				'<input type="range" id="' + id + '" data-token="' + token + '" min="' + l.min + '" max="' + max + '" step="' + step + '" value="' + sv + '">' +
				'<button type="button" class="formulize-editor__step" data-token="' + token + '" data-d="1" aria-label="' + langHtml('LARGER') + '"' + (sv >= max ? ' disabled' : '') + '>+</button>',
			range: '<div class="formulize-editor__range"><span>' + show(token, l.min) + '</span><span>' + show(token, fromSlider(token, max)) + '</span></div>'
		};
	}
	// the value, and what it is measured against; a font or a colour shows its
	// value in its control, so only says so when it has been changed
	function valHtml(token) {
		var v = value(token), t = type(token);
		var from = changed(token)
			? lang('CHANGED_BY', baseName(), show(token, presetValue(token)))
			: (BASE ? lang('FROM', EDIT.baseName) : lang('THEME_OWN'));
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
		if (state.raised[token]) { notes += note(esc(lang('RAISED', show(token, v), l.why))); }
		else if (l.why && v === l.min) { notes += note(esc(lang('AT_FLOOR', l.why))); }
		var h = measuredHeight(token);
		if (h && h > v * 4 + 1) { notes += note(langHtml('MEASURED', h), state.phone ? 'phone' : ''); }
		CONTRAST.forEach(function (pair) {
			if (pair[0] !== token && pair[1] !== token) { return; }
			var ratio = contrast(tokenHex(pair[0]), tokenHex(pair[1])), need = pair[2] ? 3 : 4.5;
			if (ratio >= need) { return; }
			var other = pair[0] === token ? pair[1] : pair[0];
			var ratioHtml = '<b>' + ratio.toFixed(1) + ':1</b>';
			notes += note(pair[0] === token
				? langHtml('CONTRAST_TEXT_ON', esc(lower(fullLabel(other))), ratioHtml, need)
				: langHtml('CONTRAST_ON_THIS', esc(fullLabel(other)), ratioHtml, need), 'warn', 'bad');
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
				return '<div class="formulize-editor__dev' + (active ? ' is-active' : '') + '"><span class="formulize-editor__dev-icon" title="' + esc(label) + '">' + ICON[dev] + '</span><span class="formulize-editor__dev-name">' + esc(label) + '</span>' +
					'<div class="formulize-editor__widget">' + rw.widget + '</div><div class="formulize-editor__val">' + valHtml(t) + '</div>' + notesHtml(t) + '</div>';
			};
			inner = '<div class="formulize-editor__devs">' + row(token, 'desktop', lang('DESKTOP')) + row(phone, 'phone', lang('PHONES')) + '</div>';
		} else {
			w = widgetFor(token, id);
			inner = (w.bare ? w.widget : '<div class="formulize-editor__widget">' + w.widget + '</div>') + w.range + '<div class="formulize-editor__val">' + valHtml(token) + '</div>' + notesHtml(token);
		}
		var labelTag = (e.type === 'font') ? '<span class="formulize-editor__lbl" id="' + id + '">' + esc(e.label) + '</span>' : '<label for="' + id + '">' + esc(e.label) + '</label>';
		return '<div class="formulize-editor__ctl' + (resets.length ? ' is-changed' : '') + '" data-ctl="' + token + '">' +
			'<div class="formulize-editor__ctl-head">' + labelTag + (resets.length ? '<button type="button" class="formulize-editor__link" data-reset="' + resets.join(' ') + '">' + langHtml('RESET') + '</button>' : '') + '</div>' +
			'<p class="formulize-editor__desc">' + esc(phone ? lang('BOTH_DEVICES', e.description.replace(/\.$/, '')) : e.description) + '</p>' + inner +
			(others.length ? '<p class="formulize-editor__shared">' + esc(lang('ALSO_CHANGES', others.join(', '))) + '</p>' : '') +
		'</div>';
	}
	function gapsBlock(list) {
		if (!list || !list.length) { return ''; }
		return '<details class="formulize-editor__gaps"><summary>' + langHtml('NOT_ADJUSTABLE') + '</summary><ul>' + list.map(function (g) { return '<li>' + esc(g) + '</li>'; }).join('') + '</ul></details>';
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
		var desc = esc(lang(isLogo ? 'LOGO_DESC' : 'FAVICON_DESC'));
		// the look's own image goes back to its base's, or to the theme's own
		var own = lookOwn(key), removed = state.uploads[key].removed;
		var status = state.uploads[key].file ? lang('UPLOAD_NEW')
			: own && !removed ? lang('UPLOAD_OWN')
			: BASE ? lang('UPLOAD_BASE', EDIT.baseName)
			: lang('UPLOAD_THEME');
		var removable = (own && !removed) || state.uploads[key].file;
		return '<div class="formulize-editor__ctl' + (uploadChanged(key) ? ' is-changed' : '') + '">' +
			'<div class="formulize-editor__ctl-head"><span class="formulize-editor__lbl">' + esc(UPLOADS[key]) + '</span>' + (uploadChanged(key) ? '<button type="button" class="formulize-editor__link" data-upload-undo="' + key + '">' + langHtml('UNDO') + '</button>' : '') + '</div>' +
			'<p class="formulize-editor__desc">' + desc + '</p>' +
			'<div class="formulize-editor__upload"><span class="formulize-editor__thumb' + (isLogo ? '' : ' formulize-editor__thumb--icon') + '">' + thumb + '</span>' +
				'<span class="formulize-editor__upload-acts"><label class="formulize-editor__btn formulize-editor__btn--sm" for="formulize-editor-' + key + '">' + langHtml('UPLOAD') + '</label>' +
				(removable ? '<button type="button" class="formulize-editor__btn formulize-editor__btn--sm" data-upload-remove="' + key + '">' + esc(BASE ? lang('USE_BASE', EDIT.baseName) : lang('USE_THEME')) + '</button>' : '') +
				'<span class="formulize-editor__upload-status">' + esc(lang('UPLOAD_STATUS', status, DATA.uploads[key].types)) + '</span></span></div>' +
		'</div>';
	}
	function fontBlock(which) {
		var list = which === 'main' ? 'main' : 'heading', id = 'formulize-editor-font-' + which;
		var isCustom = state.fonts[which] === 'custom';
		var desc = esc(lang(which === 'main' ? 'MAIN_FONT_DESC' : 'SECONDARY_FONT_DESC'));
		return '<div class="formulize-editor__ctl' + (fontChanged(which) ? ' is-changed' : '') + '">' +
			'<div class="formulize-editor__ctl-head"><label for="' + id + '">' + langHtml(which === 'main' ? 'MAIN_FONT' : 'SECONDARY_FONT') + '</label>' + (fontChanged(which) ? '<button type="button" class="formulize-editor__link" data-font-reset="' + which + '">' + langHtml('RESET') + '</button>' : '') + '</div>' +
			'<p class="formulize-editor__desc">' + desc + '</p>' +
			'<div class="formulize-editor__widget"><select id="' + id + '" data-font="' + which + '">' + DATA.fonts[list].map(function (f) {
				return '<option value="' + esc(f.key) + '"' + (f.key === state.fonts[which] ? ' selected' : '') + '>' + esc(f.label) + '</option>';
			}).join('') + '</select></div>' +
			(isCustom ? '<label class="formulize-editor__custom-font"><span>' + langHtml('GOOGLE_FONT') + '</span><input type="text" data-custom-font="' + which + '" value="' + esc(state.fonts[which + 'custom'] || '') + '" placeholder="' + langHtml(which === 'main' ? 'MAIN_FONT_EXAMPLE' : 'SECONDARY_FONT_EXAMPLE') + '" spellcheck="false"></label>' +
				'<p class="formulize-editor__desc">' + langHtml('GOOGLE_FONT_HELP', '<a href="https://fonts.google.com" target="_blank" rel="noopener">fonts.google.com</a>') + '</p>' : '') +
		'</div>';
	}
	function siteBlock() {
		var sized = SIMPLE && DATA.sizes.length;
		var h = '<p class="formulize-editor__intro">' + esc([
			lang(WIDTH ? (sized ? 'INTRO_WIDTH_SIZE' : 'INTRO_WIDTH') : (sized ? 'INTRO_SIZE' : 'INTRO')),
			BASE ? lang('INTRO_BASE', EDIT.baseName) : lang('INTRO_THEME'),
			SIMPLE ? '' : lang('INTRO_CLICK')
		].join(' ').trim()) + '</p>';
		h += sect(lang('SECTION_LOGO')) + uploadBlock('appearance_logo') + uploadBlock('appearance_favicon');
		h += sect(lang('SECTION_COLOURS')) + Object.keys(DATA.colours).map(function (key) {
			var c = DATA.colours[key], id = 'formulize-editor-colour-' + key;
			return '<div class="formulize-editor__colour' + (colourChanged(key) ? ' is-changed' : '') + '"><input type="color" id="' + id + '" data-colour="' + key + '" value="' + esc(state.colours[key]) + '">' +
				'<label for="' + id + '" class="formulize-editor__lbl">' + esc(c.label) + '</label>' +
				(colourChanged(key) ? '<button type="button" class="formulize-editor__link" data-colour-reset="' + key + '">' + esc(BASE ? lang('USE_BASE', EDIT.baseName) : lang('RESET_TO', c['default'])) + '</button>' : '<span class="formulize-editor__hex">' + esc(state.colours[key]) + '</span>') +
				'<span class="formulize-editor__desc">' + esc(c.description) + '</span></div>';
		}).join('');
		h += sect(lang('SECTION_FONTS')) + fontBlock('main') + fontBlock('heading');
		if (sized) { h += sect(lang('SECTION_SIZE')) + sizeBlock(); }
		if (WIDTH) { h += sect(lang('SECTION_PAGE')) + widthBlock(); }
		return h;
	}
	// The look's size, in the simple editor: the sizes that come with Formulize, whose
	// values choosing one writes into the look; the same as its base, which sets none of
	// them; or custom, when they have been changed in the advanced editor.
	function sizeOf(key) { return DATA.sizes.filter(function (z) { return z.key === key; })[0]; }
	function currentSize() {
		if (!DATA.sizeTokens.some(changed)) { return BASE ? '' : 'standard'; }
		var match = DATA.sizes.filter(function (z) {
			return DATA.sizeTokens.every(function (t) { return !has(z.values, t) || String(value(t)) === String(z.values[t]); });
		})[0];
		return match ? match.key : 'custom';
	}
	function chooseSize(key) {
		if (currentSize() === 'custom' && !window.confirm(lang('SIZE_CONFIRM', key ? sizeOf(key).name : lang('SIZE_SAME_LOWER', EDIT.baseName)))) { render(); return; }
		DATA.sizeTokens.forEach(function (t) { delete state.overrides[t]; });
		if (key) { var z = sizeOf(key); Object.keys(z.values).forEach(function (t) { state.overrides[t] = z.values[t]; }); }
		touch(); render();
	}
	function sizeBlock() {
		var now = currentSize(), saved = savedSize();
		var options = (BASE ? [['', lang('SIZE_SAME', EDIT.baseName)]] : []).concat(DATA.sizes.map(function (z) { return [z.key, z.name]; }));
		var custom = now === 'custom';
		var desc = now === 'custom' ? lang('SIZE_CUSTOM_DESC')
			: now === '' ? lang('SIZE_BASE_DESC', EDIT.baseName)
			: sizeOf(now).description;
		return '<div class="formulize-editor__ctl' + (now !== saved ? ' is-changed' : '') + '"><div class="formulize-editor__ctl-head"><label for="formulize-editor-size">' + langHtml('SIZE') + '</label></div>' +
			'<p class="formulize-editor__desc">' + esc(desc) + '</p>' +
			'<div class="formulize-editor__widget"><select id="formulize-editor-size" data-size="1">' + options.map(function (o) {
				return '<option value="' + esc(o[0]) + '"' + (o[0] === now ? ' selected' : '') + '>' + esc(o[1]) + '</option>';
			}).join('') + (custom ? '<option value="custom" selected disabled>' + langHtml('SIZE_CUSTOM') + '</option>' : '') + '</select></div></div>';
	}
	// the size as saved, so the choice shows as changed until it is saved
	function savedSize() {
		var keep = state.overrides;
		state.overrides = copy(SAVED.overrides);
		var size = currentSize();
		state.overrides = keep;
		return size;
	}
	// the page width: a maximum width in pixels, or full width
	function widthBlock() {
		var full = state.contentWidth === 'full';
		return '<div class="formulize-editor__ctl' + (widthChanged() ? ' is-changed' : '') + '"><div class="formulize-editor__ctl-head"><span class="formulize-editor__lbl" id="formulize-editor-width-label">' + langHtml('WIDTH') + '</span>' +
				(widthChanged() ? '<button type="button" class="formulize-editor__link" data-site-reset="contentwidth">' + langHtml('RESET') + '</button>' : '') + '</div>' +
			'<p class="formulize-editor__desc">' + esc(lang('WIDTH_DESC', lower(widthName(WIDTH.theme)))) + '</p>' +
			'<div class="formulize-editor__seg formulize-editor__seg--full" role="group" aria-labelledby="formulize-editor-width-label">' +
				'<button type="button" data-cwidth="max" aria-pressed="' + !full + '">' + langHtml('MAX_WIDTH') + '</button>' +
				'<button type="button" data-cwidth="full" aria-pressed="' + full + '">' + langHtml('FULL_WIDTH') + '</button></div>' +
			(full ? '' : '<label class="formulize-editor__custom-font"><span>' + langHtml('WIDTH_PX', WIDTH.min, WIDTH.max) + '</span><input type="number" data-cwidth-px="1" min="' + WIDTH.min + '" max="' + WIDTH.max + '" step="1" value="' + esc(state.contentWidth) + '"></label>') +
		'</div>';
	}
	// a width typed in, kept to the limits; anything that isn't a number leaves it as it was
	function setWidthPx(v) {
		var n = Math.round(Number(v));
		if (String(v).trim() === '' || !isFinite(n)) { return; }
		state.contentWidth = String(Math.min(WIDTH.max, Math.max(WIDTH.min, n)));
	}
	// what has been changed and not saved yet: each with what it is now and what it
	// was when saved, and how to undo it (undo: 'kind:key')
	function unsaved() {
		var list = [];
		Object.keys(UPLOADS).forEach(function (key) {
			if (uploadChanged(key)) {
				list.push({ name: UPLOADS[key], now: state.uploads[key].file ? lang('NEW_IMAGE') : (BASE ? lang('POSSESSIVE', EDIT.baseName) : lang('THEME_OWN_CAP')), undo: 'upload:' + key });
			}
		});
		Object.keys(DATA.colours).forEach(function (key) {
			if (String(state.colours[key]).toLowerCase() !== String(SAVED.colours[key]).toLowerCase()) {
				list.push({ name: DATA.colours[key].label, swatch: state.colours[key], now: state.colours[key], was: SAVED.colours[key], undo: 'colour:' + key });
			}
		});
		['main', 'heading'].forEach(function (which) {
			if (state.fonts[which] !== SAVED.fonts[which] || (state.fonts[which] === 'custom' && customName(which) !== (SAVED.fonts[which + 'custom'] || '').replace(/[^a-zA-Z0-9 ]/g, '').trim())) {
				list.push({ name: lang(which === 'main' ? 'MAIN_FONT' : 'SECONDARY_FONT'), now: fontShown(which), undo: 'font:' + which });
			}
		});
		if (WIDTH && state.contentWidth !== SAVED.contentWidth) {
			list.push({ name: lang('WIDTH'), now: widthName(state.contentWidth), was: widthName(SAVED.contentWidth), undo: 'contentwidth' });
		}
		Object.keys(MAP.tokens).forEach(function (token) {
			var was = has(SAVED.overrides, token) ? SAVED.overrides[token] : presetValue(token);
			if (String(value(token)) !== String(was)) {
				list.push({ token: token, name: fullLabel(token), now: show(token, value(token)), was: show(token, was), swatch: type(token) === 'colour' ? tokenHex(token) : '', undo: 'token:' + token });
			}
		});
		return list;
	}
	function changeCount() { return unsaved().length; }
	function isDirty() { return changeCount() > 0; }
	// put one setting back to how it was saved
	function undo(what) {
		var parts = what.split(':'), kind = parts[0], key = parts.slice(1).join(':');
		if (kind === 'upload') { resetUpload(key); }
		else if (kind === 'colour') { state.colours[key] = SAVED.colours[key]; }
		else if (kind === 'font') { state.fonts[key] = SAVED.fonts[key]; state.fonts[key + 'custom'] = SAVED.fonts[key + 'custom']; }
		else if (kind === 'contentwidth') { state.contentWidth = SAVED.contentWidth; }
		else if (kind === 'token') {
			if (has(SAVED.overrides, key)) { state.overrides[key] = SAVED.overrides[key]; } else { delete state.overrides[key]; }
		}
	}
	// What the look changes: everything that differs from the look it is based on, as
	// that resolves, or from the theme's own appearance (DATA.reference), saved or not.
	var REF = DATA.reference;
	// what it is measured against, in a sentence
	function refText() { return BASE ? EDIT.baseName : lang('THEME_APPEARANCE'); }
	function changedHere() {
		var list = [];
		Object.keys(UPLOADS).forEach(function (key) {
			if ((uploadUrl(key) || '') !== (REF.uploads[key] || '')) {
				list.push({ name: UPLOADS[key], now: uploadUrl(key) ? lang('UPLOADED_IMAGE') : lang('THEME_OWN_CAP') });
			}
		});
		Object.keys(DATA.colours).forEach(function (key) {
			if (String(state.colours[key]).toLowerCase() !== String(REF.colours[key]).toLowerCase()) {
				list.push({ name: DATA.colours[key].label, swatch: state.colours[key], now: state.colours[key], was: REF.colours[key], reset: 'colour:' + key });
			}
		});
		['main', 'heading'].forEach(function (which) {
			if (state.fonts[which] !== REF.fonts[which] || (state.fonts[which] === 'custom' && customName(which) !== (REF.fonts[which + 'custom'] || ''))) {
				list.push({ name: lang(which === 'main' ? 'MAIN_FONT' : 'SECONDARY_FONT'), now: fontShown(which), reset: 'font:' + which });
			}
		});
		if (WIDTH && state.contentWidth !== REF.contentWidth) {
			list.push({ name: lang('WIDTH'), now: widthName(state.contentWidth), was: widthName(REF.contentWidth), reset: 'contentwidth' });
		}
		Object.keys(MAP.tokens).forEach(function (token) {
			var was = has(REF.overrides, token) ? REF.overrides[token] : entry(token)['default'];
			if (String(value(token)) !== String(was)) {
				list.push({ token: token, name: fullLabel(token), now: show(token, value(token)), was: show(token, was), swatch: type(token) === 'colour' ? tokenHex(token) : '', reset: 'token:' + token });
			}
		});
		return list;
	}
	// put one setting back to how it is in the theme, or in the look as it came: a change
	// like any other, saved with Save
	function resetToReference(what) {
		var parts = what.split(':'), kind = parts[0], key = parts.slice(1).join(':');
		if (kind === 'colour') { state.colours[key] = REF.colours[key]; }
		else if (kind === 'font') { state.fonts[key] = REF.fonts[key]; state.fonts[key + 'custom'] = REF.fonts[key + 'custom']; }
		else if (kind === 'contentwidth') { state.contentWidth = REF.contentWidth; }
		else if (kind === 'token') { store(key, has(REF.overrides, key) ? REF.overrides[key] : entry(key)['default']); }
	}
	// a part's setting has Show, which selects the part, in advanced mode only: a
	// part's own settings aren't changed in simple mode
	function changeRow(c, button) {
		return '<div class="formulize-editor__chg"><span class="formulize-editor__chg-name">' + (c.token && DESKTOP_OF[c.token] ? ICON.phone + ' ' : '') + esc(c.name) + '</span>' +
			'<span class="formulize-editor__chg-acts">' + (c.token && !SIMPLE ? '<button type="button" class="formulize-editor__link formulize-editor__link--plain" data-select="' + home(c.token) + '">' + langHtml('SHOW') + '</button>' : '') + button + '</span>' +
			(c.token ? '<span class="formulize-editor__chg-part">' + esc(usedBy(c.token).map(function (k) { return MAP.components[k].name; }).join(', ')) + '</span>' : '') +
			'<span class="formulize-editor__chg-val">' + (c.swatch ? '<span class="formulize-editor__swatch formulize-editor__swatch--sm" style="background:' + esc(c.swatch) + '"></span>' : '') + '<b>' + esc(c.now) + '</b>' + (c.was !== undefined ? c.wasText || '' : '') + '</span></div>';
	}
	// The two panels of changes, each opened by its button in the top bar: what the look
	// changes from its base, saved or not, with Reset; and what hasn't been saved, with
	// Undo and Undo all.
	var changesPanels = [$('formulize-editor-diff-panel'), $('formulize-editor-unsaved-panel')];
	function renderChanges() {
		var pending = unsaved(), here = changedHere();
		$('formulize-editor-diff-body').innerHTML = here.length ? '<p class="formulize-editor__intro">' + esc(lang('DIFFERS', refText())) + '</p>' + here.map(function (c) {
			c.wasText = c.was !== undefined ? ' · ' + esc(lang('BASE_IS', baseName(), c.was)) : '';
			return changeRow(c, c.reset ? '<button type="button" class="formulize-editor__link" data-refreset="' + esc(c.reset) + '">' + langHtml('RESET') + '</button>' : '');
		}).join('') : '<p class="formulize-editor__intro">' + esc(lang('SAME_AS_REF', refText())) + '</p>';
		$('formulize-editor-unsaved-body').innerHTML = pending.length ? pending.map(function (c) {
			c.wasText = c.was !== undefined ? ' · ' + esc(lang('WAS', c.was)) : '';
			return changeRow(c, '<button type="button" class="formulize-editor__link" data-undo="' + esc(c.undo) + '">' + langHtml('UNDO') + '</button>');
		}).join('') : '<p class="formulize-editor__intro">' + langHtml('ALL_SAVED') + '</p>';
		$('formulize-editor-unsaved-foot').innerHTML = '<button type="button" class="formulize-editor__btn" data-undo-all="1"' + (pending.length && !READONLY ? '' : ' disabled') + '>' + langHtml('UNDO_ALL') + '</button>';
	}

	function renderInspector() {
		if (state.sel) {
			var c = MAP.components[state.sel];
			var where = Object.keys(MAP.screens).filter(function (s) { return partsOn[s] && partsOn[s][state.sel]; }).map(function (s) {
				return '<button type="button" data-goto="' + s + '"' + (s === state.screen ? ' aria-current="true"' : '') + '>' + esc(MAP.screens[s]) + '</button>';
			}).join('');
			head.innerHTML = '<div class="formulize-editor__kicker">' + langHtml('SELECTED') + '</div><div class="formulize-editor__title"><h2>' + esc(c.name) + '</h2><button type="button" class="formulize-editor__x" data-deselect="1" aria-label="' + langHtml('DESELECT') + '">✕</button></div>' +
				'<div class="formulize-editor__scope">' + (c.site
					? '<span>' + langHtml('SITE_WIDE_PART', '<strong>' + esc(c.plural) + '</strong>') + '</span>'
					: '<span>' + langHtml('APPLIES_TO', '<strong>' + esc(c.plural) + '</strong>') + (where ? ' ' + langHtml('USED_ON') : '') + '</span>' + (where ? '<div class="formulize-editor__where">' + where + '</div>' : '')) + '</div>';
			// the logo's images, and in advanced mode its tokens too (the top bar's height, which it is sized from)
			body.innerHTML = c.site === 'logo' ? uploadBlock('appearance_logo') + uploadBlock('appearance_favicon') + (SIMPLE ? '' : partBlock(c)) : partBlock(c);
		} else {
			// the look's own settings; what has changed is in the Changes panel
			head.innerHTML = '<div class="formulize-editor__kicker">' + langHtml('NOTHING_SELECTED') + '</div><div class="formulize-editor__title"><h2>' + langHtml('TAB_SITE') + '</h2></div>';
			body.innerHTML = siteBlock() + (SIMPLE ? '' : gapsBlock(MAP.gaps));
		}
	}

	/* ---- applying changes ---- */
	function render() {
		enforce();
		applyTokens();
		var n = changeCount(), count = $('formulize-editor-count');
		count.hidden = n === 0; count.textContent = n;
		$('formulize-editor-unsaved').setAttribute('aria-label', n ? lang(n === 1 ? 'UNSAVED_CHANGE' : 'UNSAVED_CHANGES', n) : lang('UNSAVED'));
		renderChanges();
		$('formulize-editor-save').disabled = READONLY || state.saving || n === 0;
		drawLive();
		// keep focus, and the inspector's scroll position, across the re-render
		var f = document.activeElement, attrs = ['data-token', 'data-d', 'data-v', 'data-cw', 'data-custom', 'data-hex', 'data-colour', 'data-font', 'data-custom-font', 'data-size', 'data-cwidth', 'data-cwidth-px'];
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
		v = (t === 'text' || t === 'font' || t === 'colour' || t === 'keyword') ? String(v) : fromSlider(token, v);
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
			cw.querySelector('.formulize-editor__cw-from').textContent = lang('COLOUR_OWN');
			Array.prototype.forEach.call(ctl.querySelectorAll('.formulize-editor__cw-opt'), function (o) { o.setAttribute('aria-pressed', 'false'); });
			var other = ctl.querySelector(input.getAttribute('data-hex') ? '[data-custom]' : '[data-hex]');
			if (other) { other.value = tokenHex(token); }
		}
	}
	// changing the phone value shows the phone preview, and the desktop value the desktop one
	function deviceFor(token) { return DESKTOP_OF[token] ? 'phone' : (PHONE_OF[token] ? 'desktop' : null); }

	function select(part, origin) {
		// in simple mode, the logo is the only part there is to select
		if (SIMPLE && part && part !== 'logo') { return; }
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
		else if (a('data-size')) { chooseSize(el.value); }
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
			state.uploads[a('data-upload-remove')].removed = lookOwn(a('data-upload-remove')); touch(); render();
		} else if (a('data-undo')) {
			undo(a('data-undo')); render();
		} else if (a('data-refreset')) {
			resetToReference(a('data-refreset')); render();
		} else if (a('data-site-reset')) {
			parts = a('data-site-reset').split(':');
			if (parts[0] === 'colour') { state.colours[parts[1]] = colourBase(parts[1]); }
			else if (parts[0] === 'font') { resetFont(parts[1]); }
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
	});
	// the panels of changes: Show (which closes the panel to show the part), Undo, Reset and Undo all
	changesPanels.forEach(function (panel) {
		panel.addEventListener('click', function (ev) {
			var b = ev.target.closest('button'); if (!b) { return; }
			var a = function (n) { return b.getAttribute(n); };
			if (a('data-select')) { panel.hidePopover(); select(a('data-select'), null); }
			else if (a('data-undo')) { undo(a('data-undo')); render(); panel.focus(); }
			else if (a('data-refreset')) { resetToReference(a('data-refreset')); render(); panel.focus(); }
			else if (a('data-undo-all')) { undoAll(); }
		});
	});
	// Esc closes an open panel first, and only then lets go of the selected part
	document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape' && state.sel && !document.querySelector('.fz-popover:popover-open')) { select(null, null); } });
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
	// back to how the look was last saved
	function undoAll() {
		if (!window.confirm(lang('RESET_CONFIRM', EDIT.name))) { return; }
		state.overrides = copy(SAVED.overrides);
		state.colours = copy(SAVED.colours);
		state.fonts = copy(SAVED.fonts);
		state.contentWidth = SAVED.contentWidth;
		Object.keys(UPLOADS).forEach(resetUpload);
		render();
	}

	/* ---- the simple editor and the advanced one ---- */
	// Switched in place: what hasn't been saved stays. The address says which, so a
	// reload comes back to it.
	function setMode(advanced) {
		if (advanced === !SIMPLE) { return; }
		SIMPLE = !advanced;
		document.body.classList.toggle('formulize-editor--advanced', advanced);
		Array.prototype.forEach.call(document.querySelectorAll('#formulize-editor-mode [data-mode]'), function (b) {
			b.setAttribute('aria-pressed', String((b.getAttribute('data-mode') === 'advanced') === advanced));
		});
		$('formulize-editor-mode-field').value = advanced ? 'advanced' : 'simple';
		try { var url = new URL(location.href); url.searchParams.set('mode', advanced ? 'advanced' : 'simple'); history.replaceState(null, '', url.toString()); } catch (e) {}
		DEFAULT_HINT = langHtml(advanced ? 'HINT_ADVANCED' : 'HINT_SIMPLE');
		hint.innerHTML = DEFAULT_HINT;
		if (frameStyle) { frameStyle.textContent = frameCss(); }
		// in the simple editor, the logo is the only part there is to select
		if (SIMPLE && state.sel && state.sel !== 'logo') { state.sel = null; state.origin = null; markSelection(); }
		render();
	}
	if ($('formulize-editor-mode')) {
		$('formulize-editor-mode').addEventListener('click', function (ev) {
			var b = ev.target.closest('[data-mode]');
			if (b) { setMode(b.getAttribute('data-mode') === 'advanced'); }
		});
	}

	// What saving changes, beside Save, when the look is in use: the site, which uses it
	// or a look based on it, and the screens set to it or to a look based on it.
	function reachText() {
		var r = DATA.reach, screens = r.screens ? lang(r.screens === 1 ? 'REACH_SCREEN' : 'REACH_SCREENS', r.screens) : '';
		if (!r.site && !screens) { return ''; }
		var what = r.site ? (screens ? lang('REACH_SITE_AND', screens) : lang('REACH_SITE')) : screens;
		return r.site && !r.siteTheme ? lang('REACH_WHEN', what, DATA.theme) : what;
	}
	function drawLive() {
		var live = $('formulize-editor-live'), text = reachText();
		live.hidden = !text;
		live.textContent = text ? lang('SAVING_CHANGES', text) : '';
	}

	/* ---- saving ---- */
	// What an action or a save did, as a note over the bottom of the preview, so
	// nothing on the page moves. A new note replaces the last. One that went well
	// goes after a while, but not while the pointer is on it or it has focus; a
	// problem stays until it is closed.
	function message(text, error) {
		var box = $('formulize-editor-toasts');
		clearTimeout(message.timer);
		box.innerHTML = '';
		var t = document.createElement('div');
		t.className = 'formulize-editor__toast' + (error ? ' formulize-editor__toast--error' : '');
		t.setAttribute('role', error ? 'alert' : 'status');
		t.innerHTML = '<span class="formulize-editor__toast-icon" aria-hidden="true">' + (error ? '!' : '✓') + '</span><div class="formulize-editor__toast-text"></div><button type="button" class="formulize-editor__toast-close" aria-label="' + langHtml('CLOSE') + '">×</button>';
		var lines = t.querySelector('.formulize-editor__toast-text');
		(Array.isArray(text) ? text : [text]).forEach(function (line) { var p = document.createElement('p'); p.textContent = line; lines.appendChild(p); });
		function close() { clearTimeout(message.timer); t.classList.remove('is-shown'); setTimeout(function () { if (t.parentNode) { t.parentNode.removeChild(t); } }, 200); }
		function later() { if (!error) { clearTimeout(message.timer); message.timer = setTimeout(close, 5000); } }
		t.querySelector('button').addEventListener('click', close);
		t.addEventListener('mouseenter', function () { clearTimeout(message.timer); });
		t.addEventListener('mouseleave', later);
		t.addEventListener('focusin', function () { clearTimeout(message.timer); });
		t.addEventListener('focusout', later);
		box.appendChild(t);
		requestAnimationFrame(function () { requestAnimationFrame(function () { t.classList.add('is-shown'); }); });
		later();
	}
	// every setting goes in the form's hidden fields
	function fillForm(form) {
		enforce();
		// what the look changes itself: what differs from its base, or from the theme's own
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
		var values = {
			appearance_look_settings: JSON.stringify(look),
			appearance_logo_remove: state.uploads.appearance_logo.removed ? '1' : '',
			appearance_favicon_remove: state.uploads.appearance_favicon.removed ? '1' : ''
		};
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
		save.disabled = true; save.textContent = lang('SAVING');
		var data = new FormData(form);
		data.append('appearance_editor_save', '1');
		data.append('appearance_editor_ajax', '1');
		// no answer at all, and an answer that isn't the editor's, are told apart: the
		// second can come after the save has been made
		fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (r) { return r.json(); }, function () { throw new Error('offline'); })
			.then(function (result) {
				if (result.saved) {
					// what was saved is what the editor now starts from: a custom font with
					// no name, for one, comes back as the theme's own
					Object.keys(UPLOADS).forEach(function (key) { resetUpload(key); });
					// the parts' settings the look records, as saved
					OWN = result.own || {};
					adopt(result.state);
					state.dirty = false;
				}
				if (result.errors && result.errors.length) { message(result.errors, true); }
				else { message(savedText()); }
				render();
			})['catch'](function (e) {
				message(lang(e && e.message === 'offline' ? 'OFFLINE' : 'UNREADABLE'), true);
			}).then(function () {
				// Save is on again only if there is something left to save
				state.saving = false; save.disabled = READONLY || changeCount() === 0; save.textContent = lang('SAVE');
			});
	});
	window.addEventListener('beforeunload', function (ev) {
		if (isDirty() && !submitting) { ev.preventDefault(); ev.returnValue = ''; }
	});
	// what a save did: what it changed, when the look is in use
	function savedText() {
		var text = reachText();
		return lang('SAVED', EDIT.name) + (text ? ' ' + lang('SAVED_REACH', text) : '');
	}
	// what the page was sent back with
	if (DATA.errors && DATA.errors.length) { message(DATA.errors, true); }
	else if (DATA.saved) { message(savedText()); }

	adopt(DATA.state);
	goScreen(state.screen);
	render();
})();
