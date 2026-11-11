/* Formulize UI: the behaviour of its popovers, menus and dialogs, in browsers
 * that don't have it yet, the little that its expanding cards need a script
 * for, and the moving of accordion items (see the end of this file).
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
	// in a grid of one column, as on a phone, which are as tall as they need to
	// be and never open (--single-column); opens one when it
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
			// in one column, every card shows all it holds and nothing opens it,
			// so a card that was open by a tap is closed
			var single = oneColumn(slot);
			slot.classList.toggle(SINGLE, single);
			if (single) {
				slot.classList.remove(OVERFLOWS, SCROLLS, OPEN);
			} else if (last) {
				// how tall the card would be to show all of it, whether it is open,
				// closed or scrolled, to the fraction of a pixel
				var style = getComputedStyle(card);
				var needed = last.getBoundingClientRect().bottom - card.getBoundingClientRect().top + card.scrollTop + parseFloat(style.paddingBottom) + parseFloat(style.borderBottomWidth);
				// the height it opens to; and so the page can be scrolled to a little
				// past the bottom of an open card
				slot.style.setProperty('--fz-expand-card-full-height', Math.ceil(needed) + 'px');
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
			return !slot.classList.contains(SINGLE) && !slot.classList.contains(CLOSED) && (slot.classList.contains(OPEN) || slot.matches(':focus-within') || (canHover.matches && slot.matches(':hover')));
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

	// ---- Accordions put in order by dragging (fz-accordion__handle) ------------
	// An item with a handle can be dragged up and down its own list by the
	// handle, with a mouse or a finger, or moved with the arrow keys, Home and
	// End while the handle has the focus. Esc while dragging puts it back. Each
	// move fires fz-accordion-reorder on the list, with the item and its place
	// before and after, and says where it went to screen readers, in the words
	// the page put in the list's data-fz-moved.
	(function () {
		var LIST = 'fz-accordion', ITEM = 'fz-accordion__item', HANDLE = 'fz-accordion__handle', DRAGGING = ITEM + '--dragging';
		var EDGE = 48; // how near the top or bottom of the window the page scrolls
		var drag = null;

		var handleOf = function (target) {
			var handle = target && target.closest ? target.closest('.' + HANDLE) : null;
			var item = handle && handle.parentElement;
			var list = item && item.parentElement;
			if (!item || !list || !item.classList.contains(ITEM) || !list.classList.contains(LIST)) {
				return null;
			}
			return { handle: handle, item: item, list: list };
		};
		var itemsOf = function (list) {
			return Array.prototype.filter.call(list.children, function (child) {
				return child.classList.contains(ITEM);
			});
		};
		// sprintf's %s and %1$s, for the words in data-fz-moved
		var fill = function (words, values) {
			var next = 0;
			return words.replace(/%%|%(?:(\d+)\$)?s/g, function (blank, number) {
				if (blank === '%%') {
					return '%';
				}
				var value = number ? values[number - 1] : values[next++];
				return value === undefined ? '' : String(value);
			});
		};
		var announce = function (list, item) {
			var words = list.getAttribute('data-fz-moved');
			if (!words) {
				return;
			}
			var status = list.formulizeUiStatus;
			if (!status) {
				status = list.formulizeUiStatus = doc.createElement('div');
				status.className = 'fz-sr-only';
				status.setAttribute('role', 'status');
				status.setAttribute('aria-live', 'polite');
				list.parentNode.insertBefore(status, list.nextSibling);
			}
			var title = item.querySelector('.fz-accordion__title');
			var items = itemsOf(list);
			status.textContent = fill(words, [title ? title.textContent.trim() : '', items.indexOf(item) + 1, items.length]);
		};
		var moved = function (list, item, from) {
			var to = itemsOf(list).indexOf(item);
			if (to === from) {
				return;
			}
			var event;
			try {
				event = new CustomEvent('fz-accordion-reorder', { bubbles: true, detail: { item: item, from: from, to: to } });
			} catch (e) {
				event = doc.createEvent('CustomEvent');
				event.initCustomEvent('fz-accordion-reorder', true, false, { item: item, from: from, to: to });
			}
			list.dispatchEvent(event);
			announce(list, item);
		};
		// put the item at a place in the list, counting only the items
		var place = function (list, item, to) {
			var others = itemsOf(list).filter(function (other) { return other !== item; });
			list.insertBefore(item, others[to] || null);
		};

		// The keyboard, on a focused handle
		doc.addEventListener('keydown', function (event) {
			if (drag && event.key === 'Escape') {
				event.preventDefault();
				end(true);
				return;
			}
			var found = handleOf(event.target);
			if (!found || event.target !== found.handle || event.altKey || event.ctrlKey || event.metaKey) {
				return;
			}
			var items = itemsOf(found.list), from = items.indexOf(found.item), to;
			switch (event.key) {
				case 'ArrowUp': to = from - 1; break;
				case 'ArrowDown': to = from + 1; break;
				case 'Home': to = 0; break;
				case 'End': to = items.length - 1; break;
				default: return;
			}
			event.preventDefault();
			if (to < 0 || to >= items.length || to === from) {
				return;
			}
			place(found.list, found.item, to);
			found.handle.focus();
			moved(found.list, found.item, from);
		});

		// The pointer: the item follows it up and down, and changes places with
		// the item next to it once the pointer passes the middle of that one.
		// Positions are measured from the top of the page, not the window, so
		// they hold while the page scrolls.
		var scrollTop = function () {
			return window.pageYOffset || doc.documentElement.scrollTop || 0;
		};
		var follow = function () {
			var y = drag.y + scrollTop();
			var item = drag.item, list = drag.list;
			var changed = true;
			while (changed) {
				changed = false;
				var items = itemsOf(list), index = items.indexOf(item);
				var before = items[index - 1], after = items[index + 1];
				var top = item.offsetTop;
				if (before) {
					var box = before.getBoundingClientRect();
					if (y < box.top + scrollTop() + box.height / 2) {
						list.insertBefore(item, before);
						drag.start += item.offsetTop - top;
						changed = true;
						continue;
					}
				}
				if (after) {
					var box2 = after.getBoundingClientRect();
					if (y > box2.top + scrollTop() + box2.height / 2) {
						list.insertBefore(item, after.nextSibling);
						drag.start += item.offsetTop - top;
						changed = true;
					}
				}
			}
			item.style.transform = 'translateY(' + (y - drag.start) + 'px)';
		};
		// near the top or bottom of the window the page scrolls, for as long as
		// the pointer stays there
		var scroll = function () {
			if (!drag || !drag.active) {
				return;
			}
			var step = drag.y < EDGE ? -(EDGE - drag.y) / 4 : (drag.y > window.innerHeight - EDGE ? (drag.y - window.innerHeight + EDGE) / 4 : 0);
			if (step) {
				window.scrollBy(0, step);
				follow();
			}
			drag.frame = window.requestAnimationFrame(scroll);
		};
		var end = function (cancel) {
			if (!drag) {
				return;
			}
			var finished = drag;
			drag = null;
			if (finished.frame) {
				window.cancelAnimationFrame(finished.frame);
			}
			if (finished.handle.hasPointerCapture && finished.handle.hasPointerCapture(finished.pointer)) {
				finished.handle.releasePointerCapture(finished.pointer);
			}
			if (!finished.active) {
				return;
			}
			finished.item.style.transform = '';
			finished.item.classList.remove(DRAGGING);
			if (cancel) {
				place(finished.list, finished.item, finished.from);
				return;
			}
			moved(finished.list, finished.item, finished.from);
		};
		doc.addEventListener('pointerdown', function (event) {
			var found = handleOf(event.target);
			if (!found || drag || (event.pointerType === 'mouse' && event.button !== 0)) {
				return;
			}
			drag = {
				handle: found.handle,
				item: found.item,
				list: found.list,
				pointer: event.pointerId,
				from: itemsOf(found.list).indexOf(found.item),
				y: event.clientY,
				start: event.clientY + scrollTop(),
				active: false,
				frame: 0
			};
			if (found.handle.setPointerCapture) {
				found.handle.setPointerCapture(event.pointerId);
			}
		});
		doc.addEventListener('pointermove', function (event) {
			if (!drag || event.pointerId !== drag.pointer) {
				return;
			}
			drag.y = event.clientY;
			if (!drag.active) {
				// it is a drag once the pointer has moved a little, not a click
				if (Math.abs(event.clientY + scrollTop() - drag.start) < 4) {
					return;
				}
				drag.active = true;
				drag.item.classList.add(DRAGGING);
				drag.frame = window.requestAnimationFrame(scroll);
			}
			event.preventDefault();
			follow();
		});
		doc.addEventListener('pointerup', function (event) {
			if (drag && event.pointerId === drag.pointer) {
				end(false);
			}
		});
		doc.addEventListener('pointercancel', function (event) {
			if (drag && event.pointerId === drag.pointer) {
				end(true);
			}
		});
	})();
})();
