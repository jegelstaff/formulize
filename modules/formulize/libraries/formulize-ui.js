/* Formulize UI: the behaviour of its popovers, menus and dialogs, in browsers
 * that don't have it yet.
 *
 * Formulize UI's components work with no script in current browsers: a
 * <dialog> is opened by a button with commandfor and command="show-modal", and
 * a popover is placed beside its button by CSS anchor positioning. Browsers
 * from before those (Safari before 26.2, which has neither before 26; Firefox
 * before 144 and 147) get them here instead, and so does a popover that a
 * browser's anchor positioning has put somewhere other than beside its button.
 * Where the browser does it all, this does nothing.
 *
 * Loaded with formulize-ui.css, wherever Formulize adds that (see
 * formulize_uiAddToTheme() and formulize_uiStylesheetLink() in
 * modules/formulize/include/functions.php). It can be loaded twice, harmlessly.
 */
(function () {
	'use strict';
	if (window.formulizeUi) {
		return;
	}
	window.formulizeUi = true;
	var doc = document;

	// ---- Buttons with commandfor: open and close dialogs and popovers ----------
	if (!('commandForElement' in HTMLButtonElement.prototype)) {
		doc.addEventListener('click', function (event) {
			var button = event.target.closest ? event.target.closest('button[commandfor]') : null;
			if (!button || button.disabled) {
				return;
			}
			var target = doc.getElementById(button.getAttribute('commandfor'));
			if (!target) {
				return;
			}
			var isDialog = target.tagName === 'DIALOG';
			var isOpenPopover = target.matches && target.matches(':popover-open');
			switch ((button.getAttribute('command') || '').toLowerCase()) {
				case 'show-modal':
					if (isDialog && !target.open) {
						target.formulizeUiOpener = button;
						target.showModal();
					}
					break;
				case 'close':
					if (isDialog && target.open) {
						target.close(button.value);
					}
					break;
				case 'request-close':
					if (isDialog && target.open) {
						if (target.requestClose) {
							target.requestClose(button.value);
						} else {
							target.close(button.value);
						}
					}
					break;
				case 'show-popover':
					if (target.showPopover && !isOpenPopover) {
						target.showPopover();
					}
					break;
				case 'hide-popover':
					if (target.hidePopover && isOpenPopover) {
						target.hidePopover();
					}
					break;
				case 'toggle-popover':
					if (target.togglePopover) {
						target.togglePopover();
					}
					break;
			}
		});
		// back to the button that opened it, as the browser does; Safari doesn't
		// give a clicked button the focus, so it would otherwise go to the page
		doc.addEventListener('close', function (event) {
			var dialog = event.target, opener = dialog.formulizeUiOpener;
			dialog.formulizeUiOpener = null;
			var focused = doc.activeElement;
			if (opener && doc.contains(opener) && (!focused || focused === doc.body || dialog.contains(focused))) {
				opener.focus();
			}
		}, true);
	}

	// ---- Popovers: beside their button -----------------------------------------
	// Placed here in a browser without anchor positioning, and in one whose
	// anchor positioning put it somewhere else (early Safari 26 does, for a
	// popover over a page that scrolls inside an element, as Lyris's does).
	(function () {
		var native = !!(window.CSS && CSS.supports && CSS.supports('position-area: block-end'));
		var open = [];
		var gap = 4, edge = 8;
		var props = ['margin', 'top', 'right', 'bottom', 'left', 'position-area'];

		// The button a popover is paired with: the one whose anchor-name its
		// position-anchor names, read from their style attributes since a browser
		// without anchor positioning drops those properties; or else the button
		// that opens it.
		var anchorOf = function (popover) {
			var match = /position-anchor\s*:\s*(--[\w-]+)/.exec(popover.getAttribute('style') || '');
			if (match) {
				var named = doc.querySelectorAll('[style*="anchor-name"]');
				for (var i = 0; i < named.length; i++) {
					var own = /anchor-name\s*:\s*(--[\w-]+)/.exec(named[i].getAttribute('style'));
					if (own && own[1] === match[1]) {
						return named[i];
					}
				}
			}
			return popover.id ? doc.querySelector('[popovertarget="' + popover.id + '"], [commandfor="' + popover.id + '"]') : null;
		};

		// Under the button, lined up with its start; above it, or lined up with
		// its end, when there isn't room; always inside the window.
		var place = function (popover) {
			var anchor = anchorOf(popover);
			if (!anchor) {
				return;
			}
			var a = anchor.getBoundingClientRect();
			var width = popover.offsetWidth, height = popover.offsetHeight;
			var viewWidth = doc.documentElement.clientWidth, viewHeight = window.innerHeight;
			var rtl = getComputedStyle(popover).direction === 'rtl';
			var top = a.bottom + gap;
			if (top + height > viewHeight - edge && a.top - gap - height >= edge) {
				top = a.top - gap - height;
			}
			var left = rtl ? a.right - width : a.left;
			if (rtl ? left < edge : left + width > viewWidth - edge) {
				left = rtl ? a.left : a.right - width;
			}
			popover.style.setProperty('position-area', 'none');
			popover.style.margin = '0';
			popover.style.right = 'auto';
			popover.style.bottom = 'auto';
			popover.style.top = Math.max(edge, Math.min(top, viewHeight - height - edge)) + 'px';
			popover.style.left = Math.max(edge, Math.min(left, viewWidth - width - edge)) + 'px';
		};

		// Whether the browser put it where it belongs: under its button, or above
		// it when there isn't room under it, and across from it.
		var besideAnchor = function (popover) {
			var anchor = anchorOf(popover);
			if (!anchor) {
				return true;
			}
			var a = anchor.getBoundingClientRect(), r = popover.getBoundingClientRect();
			var across = r.left < a.right && r.right > a.left;
			var roomBelow = a.bottom + gap + r.height <= window.innerHeight - edge;
			if (Math.abs(r.top - a.bottom) <= gap + 2) {
				return across;
			}
			return across && !roomBelow && Math.abs(a.top - r.bottom) <= gap + 2;
		};

		var placeAll = function () {
			for (var i = 0; i < open.length; i++) {
				place(open[i]);
			}
		};

		// toggle doesn't bubble, so it is caught on its way down
		doc.addEventListener('toggle', function (event) {
			var popover = event.target;
			if (!popover.classList || !popover.classList.contains('fz-popover')) {
				return;
			}
			var at = open.indexOf(popover);
			if (event.newState === 'open') {
				if (!native || !besideAnchor(popover)) {
					if (at < 0) {
						open.push(popover);
					}
					place(popover);
				}
			} else {
				if (at >= 0) {
					open.splice(at, 1);
				}
				// the next time it opens, the browser has another go
				for (var i = 0; i < props.length; i++) {
					popover.style.removeProperty(props[i]);
				}
			}
		}, true);
		// it follows its button when the page, or anything in it, scrolls
		window.addEventListener('scroll', placeAll, true);
		window.addEventListener('resize', placeAll);
	})();
})();
