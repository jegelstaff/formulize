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
// (a wheel event with the control key) is left alone. A theme that names
// nothing, or "none", is not affected. Published site-wide by footer.php.
(function () {
	'use strict';

	// whether an element scrolls in the direction asked
	function scrolls(el, dx, dy) {
		var style = window.getComputedStyle(el);
		return (dy && /(auto|scroll)/.test(style.overflowY) && el.scrollHeight > el.clientHeight) ||
			(dx && /(auto|scroll)/.test(style.overflowX) && el.scrollWidth > el.clientWidth);
	}

	// the container the theme names that is scrolling, if any
	function container(dx, dy) {
		var named = document.body ? document.body.getAttribute('data-formulize-scroll-container') : '';
		if (!named || named === 'none') {
			return null;
		}
		var els;
		try {
			els = document.querySelectorAll(named);
		} catch (e) {
			return null; // not a selector
		}
		for (var i = 0; i < els.length; i++) {
			if (scrolls(els[i], dx, dy)) {
				return els[i];
			}
		}
		return null;
	}

	document.addEventListener('wheel', function (ev) {
		if (ev.ctrlKey || ev.defaultPrevented) {
			return;
		}
		var dx = ev.deltaX, dy = ev.deltaY;
		if (ev.shiftKey && !dx) { // shift + wheel scrolls sideways
			dx = dy;
			dy = 0;
		}
		var target = container(dx, dy);
		if (!target || !(ev.target instanceof Element) || target.contains(ev.target)) {
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
