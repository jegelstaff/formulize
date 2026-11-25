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

// Runs on every page before its own scripts. Draws the overlay from sessionStorage, so the caption, badge and
// cursor position survive page loads and the drawer redrawing.
function overlayScript() {
	const draw = () => {
		if (document.getElementById('wt-caption')) { return; }
		const style = document.createElement('style');
		style.textContent = `
			#wt-caption { position: fixed; left: 24px; bottom: 96px; max-width: min(820px, calc(100vw - 440px)); width: max-content;
				background: rgba(17,24,39,.92); color: #fff; font: 500 20px/1.4 system-ui, sans-serif; padding: 14px 22px;
				border-radius: 12px; z-index: 2147483646; pointer-events: none; box-shadow: 0 8px 30px rgba(0,0,0,.35); }
			#wt-caption:empty { display: none; }
			#wt-caption small { display: block; font-size: 13px; letter-spacing: .06em; text-transform: uppercase; color: #fbbf24; margin-bottom: 4px; }
			#wt-badge { position: fixed; top: 10px; left: 50%; transform: translateX(-50%); background: #fbbf24; color: #111;
				font: 600 14px system-ui, sans-serif; padding: 6px 14px; border-radius: 999px; z-index: 2147483646; pointer-events: none; }
			#wt-badge:empty { display: none; }
			#wt-cursor { position: fixed; width: 22px; height: 22px; margin: -11px 0 0 -11px; border-radius: 50%;
				background: rgba(239,68,68,.35); border: 2px solid #ef4444; z-index: 2147483647; pointer-events: none;
				transition: transform .12s, background .12s; left: -50px; top: -50px; }
			#wt-cursor.down { transform: scale(.7); background: rgba(239,68,68,.8); }
			.wt-highlight { outline: 4px solid #fbbf24 !important; outline-offset: 3px; border-radius: 6px; }
		`;
		document.head.appendChild(style);
		for (const id of ['wt-caption', 'wt-badge', 'wt-cursor']) {
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
		window.__wtDraw = () => {
			const title = sessionStorage.getItem('wt-title') || '';
			const caption = sessionStorage.getItem('wt-caption') || '';
			document.getElementById('wt-caption').innerHTML = caption ? (title ? `<small>${title}</small>` : '') + caption : '';
			document.getElementById('wt-badge').textContent = sessionStorage.getItem('wt-badge') || '';
		};
		window.__wtDraw();
	};
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', draw);
	} else {
		draw();
	}
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
		await this.page.addInitScript(overlayScript);
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

	async setOverlay(key, value) {
		await this.page.evaluate(([k, v]) => {
			sessionStorage.setItem(k, v);
			if (window.__wtDraw) { window.__wtDraw(); }
		}, [key, value]);
	}

	/** Change the small heading above the captions. It starts as the test's title. */
	async title(text) {
		await this.setOverlay('wt-title', text);
	}

	/** Change the badge at the top of the screen. signIn() sets it. */
	async badge(text) {
		await this.setOverlay('wt-badge', text);
	}

	/**
	 * Show a caption and leave time to read it: about a third of a second a word, and at least 2.5 seconds, unless
	 * ms says otherwise. It stays up until the next caption, through anything done meanwhile. HTML is allowed.
	 */
	async say(html, ms) {
		this.closeCaption();
		this.captions.push({ start: this.seconds(), end: null, text: plainText(html) });
		await this.setOverlay('wt-caption', html);
		const words = plainText(html).split(' ').length;
		await this.pause(ms ?? Math.max(2500, words * 330));
	}

	/** Take the caption down. */
	async quiet() {
		this.closeCaption();
		await this.setOverlay('wt-caption', '');
	}

	async pause(ms) {
		await this.page.waitForTimeout(ms);
	}

	/** Glide the cursor to something, scrolling it into view first. */
	async moveTo(locator) {
		await locator.scrollIntoViewIfNeeded();
		const box = await locator.boundingBox();
		if (box) {
			await this.page.mouse.move(box.x + Math.min(box.width / 2, 120), box.y + box.height / 2, { steps: 28 });
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
		await locator.pressSequentially(text, { delay: 75 });
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
		await target.scrollIntoViewIfNeeded();
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
				await this.pause(3000);
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
