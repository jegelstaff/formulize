// Scrolling a theme's scroll container from anywhere on the page.
//
// A theme can say which element scrolls a screen's content, with the
// data-formulize-scroll-container attribute on its <body> (see "Say what
// scrolls" in docs/themes.md). It can name several, for a theme that scrolls a
// different one at different widths: whichever of them is scrolling is the
// one. Saving a form uses it to put a reader back where they were
// (formulize_scrollContainer in include/formdisplay.php), and this uses it so
// that the mouse wheel or trackpad scrolls it from anywhere on the page, not
// only with the pointer over it: over the margins beside a centred column, a
// title bar, the bar of buttons at the bottom of a form.
//
// Anything under the pointer that scrolls on its own (an open menu, a sidebar,
// a wide table) keeps its scroll, the container keeps its own, and pinch-zoom
// (a wheel event with the control key) is left alone. So is the wheel over a
// modal, such as the entry drawer, or its backdrop: the page behind a modal
// stays where it is, as the modal means it to. And so is the wheel over a menu
// that has been opened outside the container (an autocomplete's list of
// suggestions): it belongs to a field in the container, and scrolling the
// container would leave it behind. A theme that names nothing, or "none", is
// not affected. Published site-wide by footer.php.
(function () {
	'use strict';

	// a modal, and its backdrop; and a menu or list of options, opened over the page
	var LEAVE = '[aria-modal="true"], [role="dialog"], .formulize-drawer-scrim, [role="menu"], [role="listbox"], .ui-menu';

	// whether an element scrolls in the direction asked
	function scrolls(el, dx, dy) {
		var style = window.getComputedStyle(el);
		return (dy && /(auto|scroll)/.test(style.overflowY) && el.scrollHeight > el.clientHeight) ||
			(dx && /(auto|scroll)/.test(style.overflowX) && el.scrollWidth > el.clientWidth);
	}

	// the elements the theme names, looked up once per frame: a trackpad sends many
	// wheel events a second
	var named = null;
	function namedElements() {
		if (named === null) {
			var selector = document.body ? document.body.getAttribute('data-formulize-scroll-container') : '';
			named = [];
			if (selector && selector !== 'none') {
				try {
					named = document.querySelectorAll(selector);
				} catch (e) {
					// not a selector
				}
			}
			window.requestAnimationFrame(function () { named = null; });
		}
		return named;
	}

	// the container the theme names that is scrolling, if any
	function container(dx, dy) {
		var els = namedElements();
		for (var i = 0; i < els.length; i++) {
			if (scrolls(els[i], dx, dy)) {
				return els[i];
			}
		}
		return null;
	}

	document.addEventListener('wheel', function (ev) {
		if (ev.ctrlKey || ev.defaultPrevented || !(ev.target instanceof Element) || ev.target.closest(LEAVE)) {
			return;
		}
		var dx = ev.deltaX, dy = ev.deltaY;
		if (ev.shiftKey && !dx) { // shift + wheel scrolls sideways
			dx = dy;
			dy = 0;
		}
		var target = container(dx, dy);
		if (!target || target.contains(ev.target)) {
			return;
		}
		for (var el = ev.target; el && el !== document.body && el !== document.documentElement; el = el.parentElement) {
			if (scrolls(el, dx, dy)) {
				return;
			}
		}
		// lines or pages, as some mice report them, in pixels
		var unit = ev.deltaMode === 1 ? 16 : (ev.deltaMode === 2 ? target.clientHeight : 1);
		target.scrollBy(dx * unit, dy * unit);
		ev.preventDefault();
	}, { passive: false });
})();
