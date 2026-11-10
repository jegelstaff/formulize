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
	// fade and chevron, and measures how tall each is open, and which are too
	// tall to open all the way and scroll instead (--scrolls); marks the cards
	// in a grid of one column, which are only as tall as they need to be, up
	// to the height of the others (--single-column); opens one when it
	// is tapped (--open), without following a link on that first tap; closes it
	// when anything else is tapped; closes an open card with Esc (--closed)
	// until the pointer or the focus leaves it, or the focus moves on inside it;
	// and keeps the contents of a card still while it opens and closes (see the
	// end).
	(function () {
		var CARD = 'fz-expand-card', OVERFLOWS = CARD + '--overflows', SCROLLS = CARD + '--scrolls', SINGLE = CARD + '--single-column', OPEN = CARD + '--open', CLOSED = CARD + '--closed';
		var canHover = window.matchMedia ? window.matchMedia('(hover: hover)') : { matches: true };
		var lessMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : { matches: false };
		var seen = typeof WeakSet === 'function' ? new WeakSet() : null;

		// whether the card is in a grid of one column, as on a phone, where there
		// is no row of cards for it to line up with. A grid's columns are listed
		// one by one, those that auto-fit leaves empty too (as 0px), so a lone
		// card in a wide grid is not in one column.
		var oneColumn = function (slot) {
			var grid = slot.parentElement, style = grid && getComputedStyle(grid);
			var columns = style && style.display.indexOf('grid') !== -1 ? style.gridTemplateColumns.match(/[\d.]+px/g) : null;
			return !!columns && columns.length === 1;
		};
		var measure = function (slot) {
			var card = slot.firstElementChild, last = card && card.lastElementChild;
			if (last) {
				// how tall the card would be to show all of it, whether it is open,
				// closed or scrolled, to the fraction of a pixel
				var style = getComputedStyle(card);
				var needed = last.getBoundingClientRect().bottom - card.getBoundingClientRect().top + card.scrollTop + parseFloat(style.paddingBottom) + parseFloat(style.borderBottomWidth);
				// the height it opens to; in one column, the height of its place
				// when that is less than the others'; and so the page can be
				// scrolled to a little past the bottom of an open card
				slot.style.setProperty('--fz-expand-card-full-height', Math.ceil(needed) + 'px');
				slot.classList.toggle(SINGLE, oneColumn(slot));
				// against the room it has when closed
				slot.classList.toggle(OVERFLOWS, needed > slot.clientHeight + 1);
				// and against the most an open card may be (max(100%, 70vh) in the
				// CSS; a phone's vh is at least its innerHeight, so this errs towards
				// scrolling). A card that scrolls keeps room for a scrollbar, which
				// can make it taller; it only stops scrolling once it fits with that
				// room, so it doesn't flip back and forth.
				var most = Math.max(slot.clientHeight, 0.7 * window.innerHeight);
				if (needed > most + 1) {
					slot.classList.add(SCROLLS);
				} else if (needed < most) {
					slot.classList.remove(SCROLLS);
				}
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
		// the window's height changes how tall an open card may be
		var remeasuring = false;
		window.addEventListener('resize', function () {
			if (!remeasuring) {
				remeasuring = true;
				requestAnimationFrame(function () {
					remeasuring = false;
					var slots = doc.querySelectorAll('.' + CARD);
					for (var i = 0; i < slots.length; i++) {
						measure(slots[i]);
					}
				});
			}
		});
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
		// a card that has closed goes back to the top of what it holds: it
		// scrolls back up while it shrinks
		var rewind = function (slot) {
			requestAnimationFrame(function () {
				var card = slot.firstElementChild;
				if (card && card.scrollTop && !isOpen(slot)) {
					if (lessMotion.matches || !card.scrollTo) {
						card.scrollTop = 0;
					} else {
						card.scrollTo({ top: 0, behavior: 'smooth' });
					}
				}
			});
		};
		var closeOthers = function (keep) {
			var open = doc.querySelectorAll('.' + OPEN);
			for (var i = 0; i < open.length; i++) {
				if (open[i] !== keep) {
					open[i].classList.remove(OPEN);
					rewind(open[i]);
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
				rewind(slot);
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
					rewind(slots[i]);
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
		// the contents of a card stay still while it opens. When the focus moves
		// into a closed card, the browser scrolls the card to show what has the
		// focus, while the card is still the height it has closed, and the
		// contents would jump up and then slide back down as it grows. So that
		// is undone before it shows, and once the card is open, what has the
		// focus is scrolled into view if it still isn't. (With less motion, the
		// card is open at once and the browser's own scrolling is right.)
		doc.addEventListener('focusin', function (event) {
			var slot = event.target.closest ? event.target.closest('.' + CARD) : null;
			if (!slot || !slot.classList.contains(OVERFLOWS) || lessMotion.matches) {
				return;
			}
			var from = event.relatedTarget;
			var wasOpen = !slot.classList.contains(CLOSED) && (slot.classList.contains(OPEN) || (!!from && slot.contains(from)) || (canHover.matches && slot.matches(':hover')));
			if (!wasOpen) {
				var card = slot.firstElementChild;
				requestAnimationFrame(function () {
					card.scrollTop = 0;
				});
			}
		});
		doc.addEventListener('transitionend', function (event) {
			var card = event.target, slot = card.parentElement, focused = doc.activeElement;
			if (event.propertyName !== 'height' || !slot || !slot.classList || !slot.classList.contains(CARD) || !focused || !card.contains(focused) || !isOpen(slot)) {
				return;
			}
			var box = card.getBoundingClientRect(), item = focused.getBoundingClientRect();
			if (item.top < box.top || item.bottom > box.bottom) {
				focused.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
			}
		});
		doc.addEventListener('focusin', reopen);
		doc.addEventListener('pointerover', reopen);
		// and it goes back to its top when it closes
		var left = function (event) {
			var slot = event.target.closest ? event.target.closest('.' + CARD) : null;
			if (slot && !(event.relatedTarget && slot.contains(event.relatedTarget))) {
				rewind(slot);
			}
		};
		doc.addEventListener('focusout', left);
		doc.addEventListener('pointerout', left);
	})();
})();
