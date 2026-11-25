// Records Formulize walkthrough videos: a person's view of a workflow, slowed down, with captions, a badge saying
// who is signed in, and a cursor that glides to what it clicks. See docs/recording_walkthroughs.md and
// ai-context/walkthrough-videos.md.
//
// A walkthrough imports test and expect from here, not from @playwright/test, so one outside the repository
// still finds the copy of Playwright installed in tests/e2e:
//
//   import { test, expect } from '/path/to/formulize/tests/e2e/walkthroughs/walkthrough.js';
//   test('Adding an artifact', async ({ walk }) => { await walk.signIn('ahstaff'); ... });

import { test as base, expect } from '@playwright/test';
import { leavePage, saveFormulizeForm, ensureMainMenuOpen } from '../utils.js';

export { expect };

// Every pause and movement is scaled by this. The first walkthroughs recorded were 30% too slow to watch, so the
// default is 0.7 of their timings. WALKTHROUGH_SPEED speeds it up further, or slows it down below 1.
const SPEED = Number(process.env.WALKTHROUGH_SPEED) || 1;
const TIME = 0.7 / SPEED;

// The lower third is a card of a fixed size, this far from the bottom left corner. Nothing being shown should be
// scrolled behind it.
const LOWER_THIRD = { height: 112, gap: 28 };
const LOWER_THIRD_HEIGHT = LOWER_THIRD.height + LOWER_THIRD.gap;
const FADE_MS = 350;

// Runs on every page before its own scripts. Draws the overlay from sessionStorage, so the caption, badge and
// cursor position survive page loads and the drawer redrawing.
function overlayScript({ height, gap, fade }) {
	const draw = () => {
		if (document.getElementById('wt-lower')) { return; }
		const style = document.createElement('style');
		// A semi-transparent dark card, so the page stays visible behind it, with an accent bar, white text and a light
		// accent heading. Its width stops clear of the drawer.
		style.textContent = `
			#wt-lower { position: fixed; left: ${gap}px; bottom: ${gap}px; width: min(760px, calc(100vw - 440px - ${gap * 2}px));
				height: ${height}px; box-sizing: border-box; padding: 18px 26px 0 26px; z-index: 2147483646; pointer-events: none;
				background: rgba(15,23,42,.85);
				border-left: 6px solid #3b82f6; border-radius: 10px; box-shadow: 0 12px 32px rgba(15,23,42,.25);
				opacity: 0; transform: translateY(10px); transition: opacity ${fade}ms ease, transform ${fade}ms ease; }
			#wt-lower.on { opacity: 1; transform: none; }
			#wt-lower .wt-title { font: 700 12px/1 system-ui, sans-serif; letter-spacing: .1em; text-transform: uppercase; color: #bfdbfe; text-shadow: 0 1px 3px rgba(0,0,0,.7); margin-bottom: 9px; }
			#wt-lower .wt-title:empty { display: none; }
			#wt-lower .wt-text { font: 500 20px/1.4 system-ui, sans-serif; color: #f8fafc; text-shadow: 0 1px 3px rgba(0,0,0,.7);
				display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
			#wt-badge { position: fixed; top: 10px; left: 50%; transform: translateX(-50%); background: #0f172a; color: #f8fafc;
				font: 600 14px system-ui, sans-serif; padding: 7px 16px 7px 14px; border-radius: 999px; z-index: 2147483646; pointer-events: none;
				box-shadow: 0 4px 14px rgba(15,23,42,.25); }
			#wt-badge::before { content: ''; display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #3b82f6; margin-right: 8px; vertical-align: 1px; }
			#wt-badge:empty { display: none; }
			#wt-cursor { position: fixed; width: 22px; height: 22px; margin: -11px 0 0 -11px; border-radius: 50%;
				background: rgba(239,68,68,.35); border: 2px solid #ef4444; z-index: 2147483647; pointer-events: none;
				transition: transform .12s, background .12s; left: -50px; top: -50px; }
			#wt-cursor.down { transform: scale(.7); background: rgba(239,68,68,.8); }
			.wt-highlight { outline: 4px solid #ef4444 !important; outline-offset: 3px; border-radius: 6px; }
		`;
		document.head.appendChild(style);
		const lower = document.createElement('div');
		lower.id = 'wt-lower';
		lower.innerHTML = '<div class="wt-title"></div><div class="wt-text"></div>';
		document.body.appendChild(lower);
		for (const id of ['wt-badge', 'wt-cursor']) {
			const el = document.createElement('div');
			el.id = id;
			document.body.appendChild(el);
		}
		const cursor = document.getElementById('wt-cursor');
		const pos = JSON.parse(sessionStorage.getItem('wt-pos') || '[-50,-50]');
		cursor.style.left = pos[0] + 'px';
		cursor.style.top = pos[1] + 'px';
		document.addEventListener('mousemove', e => {
			cursor.style.left = e.clientX + 'px';
			cursor.style.top = e.clientY + 'px';
			sessionStorage.setItem('wt-pos', JSON.stringify([e.clientX, e.clientY]));
		}, true);
		document.addEventListener('mousedown', () => cursor.classList.add('down'), true);
		document.addEventListener('mouseup', () => cursor.classList.remove('down'), true);

		const wait = ms => new Promise(resolve => setTimeout(resolve, ms));
		const fill = () => {
			lower.querySelector('.wt-title').textContent = sessionStorage.getItem('wt-title') || '';
			lower.querySelector('.wt-text').innerHTML = sessionStorage.getItem('wt-caption') || '';
			document.getElementById('wt-badge').textContent = sessionStorage.getItem('wt-badge') || '';
		};
		// Draw what's stored, fading the caption out and in when it changes. A new page shows it at once.
		window.__wtDraw = async (animate = true) => {
			const caption = sessionStorage.getItem('wt-caption') || '';
			const shown = lower.classList.contains('on');
			if (!animate) {
				lower.style.transition = 'none';
				fill();
				lower.classList.toggle('on', !!caption);
				void lower.offsetHeight;
				lower.style.transition = '';
				return;
			}
			if (shown && lower.querySelector('.wt-text').innerHTML !== caption) {
				lower.classList.remove('on');
				await wait(fade);
			}
			fill();
			lower.classList.toggle('on', !!caption);
			await wait(fade);
		};
		window.__wtDraw(false);
	};
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', draw);
	} else {
		draw();
	}
}

// Scrolls an element into a comfortable view, in each box that scrolls around it and then the page, with an
// animation a viewer can follow. Returns whether anything moved.
async function smoothScroll(el, { reserve, time }) {
	const ease = t => t < .5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
	const animate = (box, delta, ms) => new Promise(resolve => {
		const from = box.scrollTop;
		const was = box.style.scrollBehavior;
		box.style.scrollBehavior = 'auto'; // a smooth scroll-behavior would fight the frames
		const t0 = performance.now();
		const step = now => {
			const t = Math.min(1, (now - t0) / ms);
			box.scrollTop = from + delta * ease(t);
			if (t < 1) {
				requestAnimationFrame(step);
			} else {
				box.style.scrollBehavior = was;
				resolve();
			}
		};
		requestAnimationFrame(step);
	});
	const page = document.scrollingElement || document.documentElement;
	const boxes = [];
	for (let p = el.parentElement; p && p !== document.body && p !== document.documentElement; p = p.parentElement) {
		if (/(auto|scroll|overlay)/.test(getComputedStyle(p).overflowY) && p.scrollHeight > p.clientHeight + 1) {
			boxes.push(p);
		}
	}
	boxes.push(page);
	const margin = 60;
	let moved = false;
	for (const box of boxes) {
		const r = el.getBoundingClientRect();
		const b = box === page ? { top: 0, bottom: innerHeight } : box.getBoundingClientRect();
		const top = Math.max(b.top, 0) + margin;
		const bottom = Math.min(b.bottom, innerHeight - reserve) - margin;
		if (r.top >= top && r.bottom <= bottom) {
			continue;
		}
		// centre it in what's left above the lower third, or bring its top into view if it's too tall for that
		let delta = r.height > bottom - top ? r.top - top : (r.top + r.height / 2) - (top + bottom) / 2;
		delta = Math.max(-box.scrollTop, Math.min(box.scrollHeight - box.clientHeight - box.scrollTop, delta));
		if (Math.abs(delta) < 2) {
			continue;
		}
		await animate(box, delta, Math.min(1500, 500 + Math.abs(delta) * 0.6) * time);
		moved = true;
	}
	return moved;
}

const escapeRegExp = text => text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
const plainText = html => html.replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim();

export class Walkthrough {
	/**
	 * @param {import('@playwright/test').Page} page
	 * @param {import('@playwright/test').TestInfo} testInfo
	 */
	constructor(page, testInfo) {
		this.page = page;
		this.testInfo = testInfo;
		this.startedAt = Date.now();
		this.captions = []; // { start, end, text } in seconds from the start of the video, for the subtitle file
	}

	async start() {
		await this.page.addInitScript(overlayScript, { ...LOWER_THIRD, fade: FADE_MS });
		await this.page.goto('/user.php');
		await this.page.evaluate(title => {
			sessionStorage.setItem('wt-title', title);
			sessionStorage.setItem('wt-caption', '');
			sessionStorage.setItem('wt-badge', '');
		}, this.testInfo.title);
	}

	async stop() {
		this.closeCaption();
		await this.testInfo.attach('walkthrough-captions', {
			body: JSON.stringify(this.captions),
			contentType: 'application/json',
		});
	}

	seconds() {
		return (Date.now() - this.startedAt) / 1000;
	}

	closeCaption() {
		const last = this.captions[this.captions.length - 1];
		if (last && last.end === null) {
			last.end = this.seconds();
		}
	}

	/** Store a part of the overlay and draw it, waiting for the caption to fade out and in if it changed. */
	async setOverlay(key, value) {
		await this.page.evaluate(async ([k, v]) => {
			sessionStorage.setItem(k, v);
			if (window.__wtDraw) { await window.__wtDraw(); }
		}, [key, value]);
	}

	/** Change the small heading above the caption in the lower third. It starts as the test's title. */
	async title(text) {
		await this.setOverlay('wt-title', text);
	}

	/** Change the badge at the top of the screen. signIn() sets it. */
	async badge(text) {
		await this.setOverlay('wt-badge', text);
	}

	/**
	 * Show a caption in the lower third and leave time to read it, unless ms says otherwise. The caption before it
	 * fades out first. It stays up until the next caption, through anything done meanwhile. HTML is allowed, and
	 * two lines fit.
	 */
	async say(html, ms) {
		this.closeCaption();
		this.captions.push({ start: this.seconds(), end: null, text: plainText(html) });
		await this.setOverlay('wt-caption', html);
		const words = plainText(html).split(' ').length;
		await this.pause(ms ?? Math.max(2500, words * 330));
	}

	/** Fade the caption out. */
	async quiet() {
		this.closeCaption();
		await this.setOverlay('wt-caption', '');
	}

	/** Wait for the viewer. It's scaled by WALKTHROUGH_SPEED, so use page.waitForTimeout() for a wait the page needs. */
	async pause(ms) {
		await this.page.waitForTimeout(Math.round(ms * TIME));
	}

	/**
	 * Scroll something into view the way a person would, so the viewer sees the page move: in the drawer or any
	 * other box that scrolls, then the page, and clear of the lower third. Nothing moves if it's already in view.
	 */
	async scrollTo(locator) {
		const target = locator.first();
		await target.waitFor({ state: 'attached' });
		if (await target.evaluate(smoothScroll, { reserve: LOWER_THIRD_HEIGHT, time: TIME })) {
			await this.pause(250);
		}
	}

	/** Glide the cursor to something, scrolling to it first. */
	async moveTo(locator) {
		await this.scrollTo(locator);
		const box = await locator.boundingBox();
		if (box) {
			await this.page.mouse.move(box.x + Math.min(box.width / 2, 120), box.y + box.height / 2, { steps: Math.max(8, Math.round(28 * TIME)) });
		}
		await this.pause(300);
	}

	async click(locator) {
		await this.moveTo(locator);
		await locator.click();
		await this.pause(400);
	}

	/** Type into a field one key at a time, so the viewer sees it typed. */
	async type(locator, text) {
		await this.moveTo(locator);
		await locator.click();
		await locator.pressSequentially(text, { delay: Math.round(75 * TIME) });
		await this.pause(300);
	}

	/** Fill a date box, which takes the whole date at once. */
	async setDate(locator, isoDate) {
		await this.moveTo(locator);
		await locator.click();
		await locator.fill(isoDate);
		await locator.dispatchEvent('change');
		await this.pause(500);
	}

	/**
	 * Choose the option of a dropdown whose text contains this. The dropdown is focused first, as a person's
	 * click would: a form in the drawer can't be saved until something in it has been focused or clicked.
	 */
	async pick(locator, text) {
		await this.moveTo(locator);
		const value = await locator.evaluate((select, t) => {
			const option = Array.from(select.options).find(o => o.text.includes(t));
			return option ? option.value : null;
		}, text);
		if (value === null) {
			const all = await locator.locator('option').allTextContents();
			throw new Error(`No option containing "${text}". Options: ${all.slice(0, 15).join(' | ')}`);
		}
		await locator.focus();
		await this.pause(350);
		await locator.selectOption(value);
		await this.pause(700);
	}

	/** Put a yellow outline around something for a while, to draw the eye to it. */
	async highlight(locator, ms = 2500) {
		const target = locator.first();
		await this.scrollTo(target);
		await target.evaluate(el => el.classList.add('wt-highlight'));
		await this.pause(ms);
		await target.evaluate(el => el.classList.remove('wt-highlight')).catch(() => {});
	}

	/**
	 * Sign in on the login page, typing slowly. The admin's password comes from E2E_TEST_ADMIN_PASSWORD, as in the
	 * tests, and everyone else's is 12345 unless options.password says otherwise. options.label is shown in the
	 * badge, such as "Pat, program administrator".
	 */
	async signIn(login, options = {}) {
		const password = options.password
			?? (login === (process.env.E2E_TEST_ADMIN_USERNAME || 'admin') ? (process.env.E2E_TEST_ADMIN_PASSWORD || 'password') : '12345');
		const label = options.label ? `${login} · ${options.label}` : login;
		await this.badge('');
		await this.page.goto('/user.php?op=logout').catch(() => {});
		await this.page.goto('/user.php');
		if (options.caption !== false) {
			await this.say(options.caption ?? `Signing in as <b>${label}</b>.`, 1500);
		}
		await this.type(this.page.locator('input[name="uname"]'), login);
		await this.type(this.page.locator('input[name="pass"]'), password);
		await Promise.all([
			this.page.waitForURL(/\/modules\/formulize\/.*/),
			this.page.locator('input[name="pass"]').press('Enter'),
		]);
		await this.badge(label);
		await this.pause(800);
	}

	/**
	 * Go to an address the way a person leaves a page, so the entries an open form locked are released before the
	 * next step. Use it rather than page.goto() whenever a form could be open.
	 */
	async go(url) {
		await leavePage(this.page, url);
		await this.page.waitForLoadState('load');
		await this.pause(800);
	}

	/**
	 * Go somewhere through the main menu, by the link's exact text. The click is shown, then go() makes the
	 * journey, since a plain click wouldn't wait for the open form's locks to be released.
	 */
	async menu(label) {
		if (await this.page.locator('.js-flyout-menu').count()) {
			await ensureMainMenuOpen(this.page); // Anari keeps the menu behind a burger
		}
		const menu = (await this.page.locator('#mainmenu').count()) ? this.page.locator('#mainmenu') : this.page;
		const link = menu.locator('a:visible', { hasText: new RegExp('^\\s*' + escapeRegExp(label) + '\\s*$') }).first();
		await this.moveTo(link);
		const href = await link.getAttribute('href');
		await this.page.evaluate(() => document.getElementById('wt-cursor')?.classList.add('down'));
		await this.pause(250);
		await this.go(href);
	}

	/** Lyris's slide-out drawer, where entries open from a list. */
	drawer() {
		return this.page.getByRole('dialog', { name: 'Detail panel' });
	}

	/** The drawer if it's open, otherwise the page. */
	async scope() {
		return (await this.drawer().isVisible()) ? this.drawer() : this.page;
	}

	/** A list row containing this text. */
	row(text) {
		return this.page.getByRole('row').filter({ hasText: text });
	}

	/** Open an entry from a list, with the link to it in its row. In Lyris it opens in the drawer. */
	async openEntry(rowText) {
		const link = this.row(rowText).first().locator('a[href*="ve="]').first();
		await this.moveTo(link);
		await link.click();
		await this.page.locator('[name^="de_"]:visible').first().waitFor({ timeout: 30000 });
		await this.pause(1200);
	}

	/**
	 * Click a custom button in a list row, accepting any confirmation, and wait for the list to come back.
	 * Returns the row again, which may have gone if the button took the entry off the list.
	 */
	async rowButton(rowText, label) {
		const row = this.row(rowText);
		await expect(row).toHaveCount(1);
		const button = row.getByRole('button', { name: label });
		await this.moveTo(button);
		this.page.once('dialog', dialog => dialog.accept());
		await Promise.all([this.page.waitForNavigation({ waitUntil: 'load' }), button.click()]);
		await expect(this.page.getByText('Showing entries')).toBeVisible();
		await this.pause(800);
		return this.row(rowText);
	}

	/**
	 * Click a button that saves the form, and wait for the save to finish. On a page this is saveFormulizeForm().
	 * In the drawer, Save draws the saved entry again, and a button that also leaves, like Save and Close, goes
	 * back to the list or the parent entry. With nothing changed, leaving sends nothing, so that only waits for
	 * the drawer to move on.
	 */
	async save(label = 'Save') {
		const drawer = this.drawer();
		if (await drawer.isVisible()) {
			const button = drawer.getByRole('button', { name: label, exact: true }).last();
			await this.moveTo(button);
			if (label === 'Save') {
				await Promise.all([
					this.page.waitForResponse(r => r.request().method() === 'POST' && /formdisplay-elementsonly|readelements/.test(r.url()), { timeout: 60000 }),
					button.click(),
				]);
				await expect(drawer.locator('[name*="_new_"]')).toHaveCount(0, { timeout: 30000 });
			} else {
				await button.click();
				await this.page.waitForTimeout(3000); // nothing to wait for if nothing changed, so give the drawer time to move on
			}
			await this.page.waitForLoadState('load');
			await expect(this.page.getByText('Your changes were not saved.')).not.toBeVisible();
			await this.pause(800);
			return;
		}
		await this.moveTo(this.page.getByRole('button', { name: label, exact: true }));
		await saveFormulizeForm(this.page, label);
		await this.page.waitForLoadState('load');
		await this.pause(800);
	}

	/** A subform, in the page or the drawer, by its caption. */
	subform(caption, scope = this.page) {
		const page = typeof scope.page === 'function' ? scope.page() : scope;
		return scope.locator('.formulize-subform').filter({ has: page.locator('.subform-caption', { hasText: caption }) }).first();
	}

	/** The ids of the entries a form's fields are showing for, found by one element's fields. */
	async entryIds(scope, fid, elementId) {
		return scope.locator(`[name^="de_${fid}_"][name$="_${elementId}"]`)
			.evaluateAll(fields => [...new Set(fields.map(f => f.getAttribute('name').split('_')[2]))]);
	}

	/** Click Add One in a subform, and return the id of the entry it made, found by one element of the sub form. */
	async addSubformEntry(caption, fid, elementId, scope = this.page) {
		const before = await this.entryIds(scope, fid, elementId);
		await this.click(this.subform(caption, scope).getByRole('button', { name: 'Add One' }));
		let id;
		await expect.poll(async () => {
			id = (await this.entryIds(scope, fid, elementId)).find(i => i !== 'new' && !before.includes(i));
			return !!id;
		}, { timeout: 30000 }).toBeTruthy();
		await this.pause(1000);
		return id;
	}

	/** Open an item on a calendar screen by its exact text, after moving forward this many months. */
	async calendarItem(label, monthsAhead = 0) {
		for (let i = 0; i < monthsAhead; i++) {
			await this.click(this.page.getByRole('link', { name: '>', exact: true }));
			await this.page.waitForLoadState('load');
			await this.pause(1000);
		}
		const item = this.page.getByRole('link', { name: label, exact: true }).first();
		await this.highlight(item, 1500);
		await this.click(item);
		await this.page.waitForLoadState('load');
		await this.page.locator('[name^="de_"]:visible').first().waitFor({ timeout: 30000 });
		await this.pause(1000);
	}

	/** End with a last caption, then a moment of the page without it. */
	async finish(html) {
		await this.say(html, 4000);
		await this.quiet();
		await this.pause(800);
	}
}

export const test = base.extend({
	walk: async ({ page }, use, testInfo) => {
		const walk = new Walkthrough(page, testInfo);
		await walk.start();
		await use(walk);
		await walk.stop();
	},
});
