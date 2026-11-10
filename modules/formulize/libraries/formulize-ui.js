/* Formulize UI: the behaviour of its popovers, menus and dialogs, in browsers
 * that don't have it yet, and the little that its expanding cards need a
 * script for (see the end of this file).
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
		// it when there isn't room under it; and lined up with the button's start,
		// or across from it when there isn't room for that (Safari 26 lines it up
		// with the button's end in a cell of a table that scrolls, with room).
		var besideAnchor = function (popover) {
			var anchor = anchorOf(popover);
			if (!anchor) {
				return true;
			}
			var a = anchor.getBoundingClientRect(), r = popover.getBoundingClientRect();
			var rtl = getComputedStyle(popover).direction === 'rtl';
			var atStart = rtl ? Math.abs(r.right - a.right) <= 2 : Math.abs(r.left - a.left) <= 2;
			var roomAtStart = rtl ? a.right - r.width >= edge : a.left + r.width <= doc.documentElement.clientWidth - edge;
			if (!atStart && roomAtStart) {
				return false;
			}
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

	// ---- Expanding cards (fz-expand-card) ---------------------------------------
	// CSS opens a card on hover and when something in it has the focus. This
	// marks the cards that have more than fits (--overflows), which shows the
	// fade and chevron; opens one when it is tapped (--open), without following
	// a link on that first tap; closes it when anything else is tapped; and
	// closes an open card with Esc (--closed) until the pointer or the focus
	// leaves it, or the focus moves on inside it.
	(function () {
		var CARD = 'fz-expand-card', OVERFLOWS = CARD + '--overflows', OPEN = CARD + '--open', CLOSED = CARD + '--closed';
		var canHover = window.matchMedia ? window.matchMedia('(hover: hover)') : { matches: true };
		var seen = typeof WeakSet === 'function' ? new WeakSet() : null;

		var measure = function (slot) {
			var card = slot.firstElementChild, last = card && card.lastElementChild;
			if (last) {
				// how tall the card would be to show all of it, whether it is open,
				// closed or scrolled, against the room it has when closed
				var style = getComputedStyle(card);
				var needed = last.offsetTop + last.offsetHeight + parseFloat(style.paddingBottom) + parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth);
				slot.classList.toggle(OVERFLOWS, needed > slot.clientHeight + 1);
				// so the page can be scrolled to a little past the bottom of an open card
				slot.style.setProperty('--fz-expand-card-full-height', Math.ceil(needed) + 'px');
			}
		};
		var resized = typeof ResizeObserver === 'function' ? new ResizeObserver(function (entries) {
			for (var i = 0; i < entries.length; i++) {
				var slot = entries[i].target.closest('.' + CARD);
				if (slot) {
					measure(slot);
				}
			}
		}) : null;
		var scan = function () {
			var slots = doc.querySelectorAll('.' + CARD);
			for (var i = 0; i < slots.length; i++) {
				var slot = slots[i];
				if (seen && seen.has(slot)) {
					continue;
				}
				if (seen) {
					seen.add(slot);
				}
				measure(slot);
				if (resized && slot.firstElementChild) {
					// its width changes how its text wraps; its contents change how tall it is
					resized.observe(slot);
					for (var c = slot.firstElementChild.firstElementChild; c; c = c.nextElementSibling) {
						resized.observe(c);
					}
				}
			}
		};
		if (doc.readyState === 'loading') {
			doc.addEventListener('DOMContentLoaded', scan);
		} else {
			scan();
		}
		// cards that arrive later, with a screen loaded into the page
		if (typeof MutationObserver === 'function' && seen) {
			var queued = false;
			new MutationObserver(function () {
				if (!queued) {
					queued = true;
					requestAnimationFrame(function () {
						queued = false;
						scan();
					});
				}
			}).observe(doc.documentElement, { childList: true, subtree: true });
		}

		var isOpen = function (slot) {
			return !slot.classList.contains(CLOSED) && (slot.classList.contains(OPEN) || slot.matches(':focus-within') || (canHover.matches && slot.matches(':hover')));
		};
		var closeOthers = function (keep) {
			var open = doc.querySelectorAll('.' + OPEN);
			for (var i = 0; i < open.length; i++) {
				if (open[i] !== keep) {
					open[i].classList.remove(OPEN);
				}
			}
		};

		// the card a finger or pen came down on, and whether it was open then,
		// before the tap focused a link in it. A mouse opens a card by hovering,
		// and a click from the keyboard or a screen reader has no tap before it.
		var downSlot = null, downWasOpen = false;
		doc.addEventListener('pointerdown', function (event) {
			downSlot = event.pointerType !== 'mouse' && event.target.closest ? event.target.closest('.' + CARD) : null;
			downWasOpen = !!downSlot && isOpen(downSlot);
		}, true);
		doc.addEventListener('click', function (event) {
			var slot = event.target.closest ? event.target.closest('.' + CARD) : null;
			var tapped = !!slot && slot === downSlot;
			downSlot = null;
			closeOthers(slot);
			if (!tapped || !slot.classList.contains(OVERFLOWS)) {
				return;
			}
			if (!downWasOpen) {
				// the first tap opens the card; a link under it is followed on the next
				event.preventDefault();
				slot.classList.remove(CLOSED);
				slot.classList.add(OPEN);
			} else if (slot.classList.contains(OPEN) && !event.target.closest('a')) {
				// a tap on the open card, not on a link, closes it
				slot.classList.remove(OPEN);
			}
		});
		doc.addEventListener('keydown', function (event) {
			if (event.key !== 'Escape') {
				return;
			}
			var slots = doc.querySelectorAll('.' + CARD);
			for (var i = 0; i < slots.length; i++) {
				if (isOpen(slots[i])) {
					slots[i].classList.remove(OPEN);
					slots[i].classList.add(CLOSED);
				}
			}
		});
		// a card closed with Esc opens again when the focus moves inside it, or
		// when the pointer or the focus comes back to it after leaving
		var reopen = function (event) {
			var closed = doc.querySelectorAll('.' + CLOSED);
			for (var i = 0; i < closed.length; i++) {
				if (event.type === 'focusin' || !closed[i].contains(event.target)) {
					closed[i].classList.remove(CLOSED);
				}
			}
		};
		doc.addEventListener('focusin', reopen);
		doc.addEventListener('pointerover', reopen);
	})();
})();
