/*
 * What a page needs when the site is installed as an app and running in a window of its own. On every
 * page (formulize_addAppHeadTags() adds it), since an app installed earlier stays on the device even
 * after the site stops offering to be installed.
 *
 * There are two kinds of app window. Chrome and Edge open the app with back and reload buttons in its
 * title bar (minimal-ui), which also show when a page is loading. Safari, on iPhones, iPads and Macs,
 * opens it with no buttons at all (standalone), and then the page has to supply what is missing.
 *
 *  - formulize.isInstalledApp(): the one place that decides whether the page is in an installed app,
 *    of either kind
 *  - formulizeOpenWindow(): window.open, kept inside the app for the site's own pages
 *  - links that open a new window: the same, for the site's own pages
 *  - in a window with no buttons: the theme's back button, [data-formulize-app-back], once there is a
 *    page to go back to, and a bar across the top while the next page is loading
 *
 * In a browser tab none of this changes anything.
 */
(function () {
	'use strict';

	var formulize = window.formulize = window.formulize || {};

	function matches(query) {
		return !!(window.matchMedia && window.matchMedia(query).matches);
	}

	// A window with no browser buttons of its own. iPhones and iPads also say so with navigator.standalone.
	function inBareWindow() {
		return matches('(display-mode: standalone)') || window.navigator.standalone === true;
	}

	// The native app will answer yes here as well, for its own app mode.
	formulize.isInstalledApp = function () {
		return inBareWindow() || matches('(display-mode: minimal-ui)');
	};

	// Whether a URL is one of the site's own pages, inside the app, rather than somewhere else.
	function isInApp(url) {
		var target;
		try {
			target = new URL(url, window.location.href);
		} catch (e) {
			return false;
		}
		return target.origin === window.location.origin
			&& target.pathname.indexOf(formulize.appScope || '/') === 0;
	}

	// In the app, a new window either leaves the app or opens a bare one with no way back, depending
	// on the device. So the site's own pages open in the app's window instead. Anywhere else, and for
	// any other site, this is window.open. Returns the new window, or null when there is none.
	window.formulizeOpenWindow = function (url, name, features) {
		if (formulize.isInstalledApp() && isInApp(url)) {
			window.location.href = url;
			return null;
		}
		return window.open(url, name, features);
	};

	// The same for links that open a new window. A link with download keeps its download, and a click
	// with a key held down is left to do what that key does.
	document.addEventListener('click', function (event) {
		if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
			return;
		}
		if (!formulize.isInstalledApp() || !event.target.closest) {
			return;
		}
		var link = event.target.closest('a[target="_blank"]');
		if (!link || !link.href || link.hasAttribute('download') || !isInApp(link.href)) {
			return;
		}
		event.preventDefault();
		window.location.href = link.href;
	});

	// ---- The back button ----

	function canGoBack() {
		if (window.navigation && typeof window.navigation.canGoBack === 'boolean') {
			return window.navigation.canGoBack;
		}
		return window.history.length > 1;
	}

	function showBackButtons() {
		if (!inBareWindow() || !canGoBack()) {
			return;
		}
		Array.prototype.forEach.call(document.querySelectorAll('[data-formulize-app-back]'), function (button) {
			button.removeAttribute('hidden');
			button.addEventListener('click', function (event) {
				event.preventDefault();
				window.history.back();
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', showBackButtons);
	} else {
		showBackButtons();
	}

	// ---- The loading bar ----
	// A browser shows that the next page is on its way, in the tab or the address bar. A window with no
	// buttons shows nothing, so a click seems to do nothing until the page changes all at once. This
	// puts a thin bar across the top while the next page loads. It waits a moment before it appears,
	// so a page that loads quickly doesn't flash it.
	//
	// It goes when the page is replaced. A page can also stay: a link can turn out to be a download, or
	// a form can be sent into a frame. Then the next click, tap or key press takes the bar away.

	var loadingBar = null;
	var loadingTimer = null;

	function showLoading() {
		if (!inBareWindow() || loadingTimer || (loadingBar && !loadingBar.hidden) || !document.body) {
			return;
		}
		loadingTimer = window.setTimeout(function () {
			loadingTimer = null;
			if (!loadingBar) {
				loadingBar = document.createElement('div');
				loadingBar.setAttribute('role', 'progressbar');
				loadingBar.setAttribute('aria-label', (formulize.appStrings && formulize.appStrings.loading) || 'Loading');
				loadingBar.style.cssText = 'position: fixed; top: var(--fz-safe-area-top, 0px); inset-inline-start: 0; z-index: 2147483000;'
					+ ' height: calc(var(--fz-spacing, 0.25rem) * 0.75); background: var(--fz-color-accent, #2e3340); pointer-events: none;';
				document.body.appendChild(loadingBar);
			}
			// grows quickly at first, then ever more slowly, and never reaches the end on its own
			var still = matches('(prefers-reduced-motion: reduce)');
			loadingBar.style.transition = 'none';
			loadingBar.style.width = still ? '100%' : '0';
			loadingBar.hidden = false;
			if (!still) {
				loadingBar.getBoundingClientRect();
				loadingBar.style.transition = 'width 10s cubic-bezier(.1, .7, .2, 1)';
				loadingBar.style.width = '90%';
			}
		}, 150);
	}

	function hideLoading() {
		if (loadingTimer) {
			window.clearTimeout(loadingTimer);
			loadingTimer = null;
		}
		if (loadingBar) {
			loadingBar.hidden = true;
		}
	}

	// whether a form is sent to this window, rather than into a frame or another window
	function submitsHere(form) {
		var target = form && form.getAttribute('target');
		return !target || target === '_self' || target === '_top' || target === '_parent';
	}

	if (window.navigation && window.navigation.addEventListener) {
		// Every navigation of this window: links, forms, forms sent by script, and going back. Not a
		// link with download (downloadRequest is its file name, which can be ''), nor a move within
		// the page.
		window.navigation.addEventListener('navigate', function (event) {
			if (event.hashChange || typeof event.downloadRequest === 'string' || (event.destination && event.destination.sameDocument)) {
				return;
			}
			showLoading();
		});
	} else {
		// Browsers without the Navigation API: a link or a form sent to this window. A form sent by
		// script fires no event, so that is caught where it is sent.
		document.addEventListener('click', function (event) {
			if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || !event.target.closest) {
				return;
			}
			var link = event.target.closest('a[href]');
			if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self')) {
				return;
			}
			var url;
			try {
				url = new URL(link.href, window.location.href);
			} catch (e) {
				return;
			}
			if (!/^https?:$/.test(url.protocol) || (url.hash && url.href.split('#')[0] === window.location.href.split('#')[0])) {
				return;
			}
			showLoading();
		});
		document.addEventListener('submit', function (event) {
			if (!event.defaultPrevented && submitsHere(event.target)) {
				showLoading();
			}
		});
		if (inBareWindow() && window.HTMLFormElement) {
			var submit = window.HTMLFormElement.prototype.submit;
			window.HTMLFormElement.prototype.submit = function () {
				if (submitsHere(this)) {
					showLoading();
				}
				return submit.apply(this, arguments);
			};
		}
	}

	// The page is still here: it was a download, or the form went somewhere else.
	['pointerdown', 'keydown'].forEach(function (type) {
		document.addEventListener(type, function () {
			if (loadingBar && !loadingBar.hidden) {
				hideLoading();
			}
		}, true);
	});
	// Back on a page kept by the browser, which still has the bar it had when it was left.
	window.addEventListener('pageshow', hideLoading);
})();
