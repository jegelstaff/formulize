const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import {
	login, getSiteTheme, setSiteThemeInDb, restoreSiteTheme, createLook, deleteLooksNamed, openAppearanceEditor,
} from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Lyris appearance (5 of 8) - the advanced editor
// ============================================================
// The advanced editor changes the parts of the interface one at a time: click a part in the preview,
// and every one of it is selected, and its settings are on the right. A change applies to every one of
// that part, on every sample screen. Sizes keep to their ranges, and to the room the text inside them
// needs. The inspector warns about text that would be hard to read. Switching to the simple editor and
// back keeps what hasn't been saved, and the preview can be seen at a phone's width.
//
// Nothing here is saved: every test works on e2e-advanced, a look based on nothing, and leaves it as it
// was. The afterAll deletes it, and puts the site's theme back.

test.describe.configure({ mode: 'serial' });

const LOOK = 'e2e-advanced';
let key = '';
let originalTheme = null;

async function adminPage(browser) {
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	return page;
}

test.beforeAll(async ({ browser }) => {
	originalTheme = getSiteTheme();
	setSiteThemeInDb('Lyris');
	const page = await adminPage(browser);
	key = await createLook(page, LOOK, { base: '' });
	await page.close();
});

test.afterAll(async ({ browser }) => {
	try {
		const page = await adminPage(browser);
		await deleteLooksNamed(page, 'e2e-');
		await page.close();
	} finally {
		restoreSiteTheme(originalTheme);
	}
});

test.beforeEach(async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openAppearanceEditor(page, key, 'advanced');
});

function preview(page) {
	return page.frameLocator('#formulize-editor-frame');
}

// The buttons in the preview whose height is the button height (not the icon buttons)
function buttons(page) {
	return preview(page).locator('[data-fz-part="button"]:not(.fz-btn--icon):not(.formulize-drawer__btn--icon)');
}

// One setting in the inspector
function control(page, token) {
	return page.locator(`.formulize-editor__ctl[data-ctl="${token}"]`);
}

async function heights(locator) {
	return locator.evaluateAll(els => els.map(el => Math.round(el.getBoundingClientRect().height)));
}

// The corners of each, or null while the preview is between two sample screens (its document is replaced)
async function radii(locator) {
	try {
		return await locator.evaluateAll(els => els.map(el => getComputedStyle(el).borderTopLeftRadius));
	} catch (e) {
		return null;
	}
}

// The different corners the preview's buttons have: one, when a change has reached them all
async function cornerSet(page, locator = buttons(page)) {
	const r = await radii(locator);
	return r && [...new Set(r)];
}

// Go to the List sample, from the screens the selected part is on, and wait until it has loaded (it has
// list rows, which the form doesn't) and the editor has set the look's settings on it
async function showListSample(page) {
	await page.locator('#formulize-editor-insp-head .formulize-editor__where').getByRole('button', { name: 'List', exact: true }).click();
	await expect(page.locator('#formulize-editor-screens button[data-s="list"]')).toHaveAttribute('aria-selected', 'true');
	await expect(preview(page).locator('[data-fz-part="row"]').first()).toBeVisible();
	await expect(preview(page).locator('html')).toHaveAttribute('style', /--fz-button-radius/);
}

async function selectButton(page) {
	await preview(page).locator('input[name="save"]').click();
	await expect(page.locator('#formulize-editor-insp-head h2')).toHaveText('Button');
}

test.describe('The advanced editor', () => {

	test('Clicking a part in the preview selects every one of it, and shows its settings', async ({ page }) => {
		const head = page.locator('#formulize-editor-insp-head');
		await expect(head).toContainText('Nothing selected');
		await expect(head.locator('h2')).toHaveText('Branding and layout');

		await selectButton(page);
		await expect(head).toContainText('Selected');
		await expect(head).toContainText('Changes apply to every button on the site, not just the one you clicked.');
		const all = preview(page).locator('[data-fz-part="button"]');
		const count = await all.count();
		expect(count).toBeGreaterThan(1);
		await expect(preview(page).locator('[data-fz-part="button"][data-fz-editor~="sel"]')).toHaveCount(count);
		await expect(preview(page).locator('[data-fz-editor~="origin"]')).toHaveCount(1);
		await expect(preview(page).locator('input[name="save"]')).toHaveAttribute('data-fz-editor', /origin/);
		const body = page.locator('#formulize-editor-insp-body');
		for (const section of ['Text', 'Colours', 'Corners', 'Size and spacing']) {
			await expect(body.locator('.formulize-editor__sect', { hasText: section })).toHaveCount(1);
		}
		await expect(control(page, '--fz-control-height')).toBeVisible();

		// another part
		await preview(page).locator('[data-fz-part="label"]').first().click();
		await expect(head.locator('h2')).toHaveText('Field label');
		await expect(preview(page).locator('[data-fz-editor~="sel"][data-fz-part="button"]')).toHaveCount(0);

		// Esc lets go of it
		await preview(page).locator('html').press('Escape');
		await expect(head.locator('h2')).toHaveText('Branding and layout');
		await expect(preview(page).locator('[data-fz-editor~="sel"]')).toHaveCount(0);
	});

	test('A change to a part applies to every one of it, on every sample screen', async ({ page }) => {
		await selectButton(page);
		const corners = control(page, '--fz-button-radius');
		const before = await corners.locator('.formulize-editor__val b').textContent();
		const radius = parseInt(before, 10);
		await expect.poll(() => cornerSet(page)).toEqual([`${radius}px`]);

		await corners.getByRole('button', { name: 'Larger' }).click();
		await expect(corners.locator('.formulize-editor__val b')).toHaveText(`${radius + 1}px`);
		await expect(corners.locator('.formulize-editor__from')).toHaveText(`changed by this look · the Lyris theme’s is ${radius}px`);
		await expect(corners.getByRole('button', { name: 'Reset' })).toBeVisible();
		await expect.poll(() => cornerSet(page)).toEqual([`${radius + 1}px`]);
		await expect(page.locator('#formulize-editor-count')).toHaveText('1');

		// the screens it is on are listed; on another, its buttons have the new corners too (the list's Add
		// button: see the fixme test below for the one there that doesn't)
		await showListSample(page);
		const add = preview(page).locator('#formulize_addButton');
		await expect(add).toHaveAttribute('data-fz-part', 'button');
		await expect.poll(() => cornerSet(page, add)).toEqual([`${radius + 1}px`]);

		// Reset puts the theme's back
		await control(page, '--fz-button-radius').getByRole('button', { name: 'Reset' }).click();
		await expect.poll(() => cornerSet(page, add)).toEqual([`${radius}px`]);
		await expect(page.locator('#formulize-editor-count')).toBeHidden();
	});

	// PRODUCT BUG, so fixme until it is fixed: the List sample marks its view switcher (the "All
	// registrations" menu in the title bar) as a Button part, so it is outlined and listed as a button, but
	// Lyris gives it the corners of --fz-radius-md (themes/Lyris/css/style.css, .lyris-list__titlebar
	// .lyris-view-switcher__toggle), so a change to the buttons' corners doesn't reach it, in the preview or
	// on a list. Either the sample shouldn't call it a button, or it should have the buttons' corners.
	test.fixme('A change to the buttons\' corners reaches every button on the List sample', async ({ page }) => {
		await selectButton(page);
		const corners = control(page, '--fz-button-radius');
		const radius = parseInt(await corners.locator('.formulize-editor__val b').textContent(), 10);
		await corners.getByRole('button', { name: 'Larger' }).click();
		await showListSample(page);
		await expect.poll(() => cornerSet(page, preview(page).locator('[data-fz-part="button"]:not(.fz-btn--icon)'))).toEqual([`${radius + 1}px`]);
	});

	test('A size keeps to its range, and to the room its text needs', async ({ page }) => {
		await selectButton(page);
		const height = control(page, '--fz-control-height');
		const slider = height.locator('input[type="range"]');
		const larger = height.getByRole('button', { name: 'Larger' });
		const smaller = height.getByRole('button', { name: 'Smaller' });
		const [min, max] = [Number(await slider.getAttribute('min')), Number(await slider.getAttribute('max'))];
		const range = height.locator('.formulize-editor__range span');
		await expect(range).toHaveText([`${min * 4}px`, `${max * 4}px`]);

		for (let i = 0; i < 50 && await larger.isEnabled(); i++) {
			await larger.click();
		}
		await expect(slider).toHaveValue(String(max));
		await expect(height.locator('.formulize-editor__val b')).toHaveText(`${max * 4}px`);
		await expect.poll(async () => [...new Set(await heights(buttons(page)))]).toEqual([max * 4]);
		for (let i = 0; i < 50 && await smaller.isEnabled(); i++) {
			await smaller.click();
		}
		await expect(slider).toHaveValue(String(min));
		await expect(height.locator('.formulize-editor__val b')).toHaveText(`${min * 4}px`);

		// a button can't be shorter than its text: larger text raises the smallest height it can have
		const text = control(page, '--fz-control-text').locator('select');
		const sizes = await text.locator('option').evaluateAll(options => options.map(option => option.value));
		await text.selectOption(sizes[sizes.length - 1]);
		await expect(height.locator('.formulize-editor__note')).toContainText(/^Raised to \d+px so it fits the button text size/);
		const raised = Number(await height.locator('input[type="range"]').getAttribute('min'));
		expect(raised).toBeGreaterThan(min);
		await expect(height.locator('input[type="range"]')).toHaveValue(String(raised));
		await expect(height.getByRole('button', { name: 'Smaller' })).toBeDisabled();
	});

	test('The inspector warns about text that would be hard to read on its background', async ({ page }) => {
		await selectButton(page);
		const text = control(page, '--fz-button-primary-text');
		await expect(text.locator('.formulize-editor__note--bad')).toHaveCount(0);
		await text.locator('button[data-cw]').click();
		// the main button's text in the same colour as the main button
		await text.locator('button.formulize-editor__cw-opt[data-v="primary"]').click();
		await expect(control(page, '--fz-button-primary-text').locator('.formulize-editor__note--bad')).toContainText('has a contrast of 1.0:1. Text needs 4.5:1 to be easy to read.');
		await expect(control(page, '--fz-button-primary-bg').locator('.formulize-editor__note--bad')).toContainText('1.0:1');
	});

	test('Switching between Simple and Advanced keeps what has not been saved', async ({ page }) => {
		await selectButton(page);
		const height = control(page, '--fz-control-height');
		await height.getByRole('button', { name: 'Larger' }).click();
		// one change, or two: a list's title bar can't be shorter than its buttons, so it may grow too
		await expect(page.locator('#formulize-editor-count')).toBeVisible();
		const count = await page.locator('#formulize-editor-count').textContent();
		const changed = await heights(buttons(page));

		await page.locator('#formulize-editor-mode button[data-mode="simple"]').click();
		await expect(page).toHaveURL(/mode=simple/);
		await expect(page.locator('#formulize-editor-count')).toHaveText(count);
		// a part's size changed one by one makes the look's size Custom
		const size = page.locator('#formulize-editor-size');
		await expect(size).toHaveValue('custom');
		expect(await heights(buttons(page))).toEqual(changed);
		// choosing a size asks first, and nothing changes when the answer is no
		let question = '';
		page.once('dialog', dialog => { question = dialog.message(); dialog.dismiss(); });
		await size.selectOption('standard');
		await expect.poll(() => question).toBe('Its sizes have been changed in the advanced editor. Replace them with Standard?');
		await expect(page.locator('#formulize-editor-size')).toHaveValue('custom');
		await expect(page.locator('#formulize-editor-count')).toHaveText(count);

		await page.locator('#formulize-editor-mode button[data-mode="advanced"]').click();
		await expect(page).toHaveURL(/mode=advanced/);
		await expect(page.locator('#formulize-editor-count')).toHaveText(count);
		expect(await heights(buttons(page))).toEqual(changed);

		// and yes replaces them: back to Standard, which is how the look was saved
		await page.locator('#formulize-editor-mode button[data-mode="simple"]').click();
		page.once('dialog', dialog => dialog.accept());
		await page.locator('#formulize-editor-size').selectOption('standard');
		await expect(page.locator('#formulize-editor-count')).toBeHidden();
		await expect(page.locator('#formulize-editor-unsaved')).toBeDisabled();
	});

	test('Desktop and Phone switch the width of the preview', async ({ page }) => {
		const frame = page.locator('#formulize-editor-frame');
		const desktop = (await frame.boundingBox()).width;
		const width = page.locator('#formulize-editor-width');
		const active = page.locator('.formulize-editor__dev.is-active .formulize-editor__dev-name');

		// a setting with a value of its own on phones shows both, the one for the preview's width highlighted.
		// Selected before the preview changes width, so the click doesn't land on a page being laid out again.
		await preview(page).locator('[data-fz-part="title"]').first().click();
		await expect(page.locator('#formulize-editor-insp-head h2')).toHaveText('Title');
		await expect(active).toHaveText('Desktop');

		await width.getByRole('button', { name: 'Phone' }).click();
		await expect(width.getByRole('button', { name: 'Phone' })).toHaveAttribute('aria-pressed', 'true');
		await expect(width.getByRole('button', { name: 'Desktop' })).toHaveAttribute('aria-pressed', 'false');
		await expect(frame).toHaveClass(/is-phone/);
		await expect.poll(async () => (await frame.boundingBox()).width).toBeLessThan(500);
		await expect(active).toHaveText('Phones (768px and below)');

		await width.getByRole('button', { name: 'Desktop' }).click();
		await expect(frame).not.toHaveClass(/is-phone/);
		await expect.poll(async () => (await frame.boundingBox()).width).toBe(desktop);
		await expect(active).toHaveText('Desktop');
	});
});
