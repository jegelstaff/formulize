const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import {
	login, getSiteTheme, setSiteThemeInDb, restoreSiteTheme, createLook, deleteLooksNamed, readLookFile,
	openAppearanceEditor, saveAppearanceEditor,
} from '../../utils';

const { execFileSync } = require('child_process');
const { webContainer } = require('../../docker-containers');

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Lyris appearance (4 of 8) - the simple editor
// ============================================================
// The simple editor has a look's logo and favicon, its colours, its main and secondary fonts, its size
// (Standard, Compact or Comfortable) and its page width and alignment. Each test makes a change, checks
// that the preview shows it straight away, saves, checks what was written to the look's stylesheet and
// that the editor opens on it again, and then takes it back to the theme's own, which the look then no
// longer records.
//
// All of it is done to one look, e2e-simple, based on nothing, so that what it is measured against is
// the Lyris theme itself and not whatever the site's Default look has been made to be. The afterAll
// deletes it and the images uploaded to it, and puts the site's theme back.

test.describe.configure({ mode: 'serial' });

const LOOK = 'e2e-simple';
let key = '';
let originalTheme = null;

// a 1 x 1 PNG, which the server recognises as an image whatever the file is called
const PNG = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', 'base64');

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
		deleteLeftoverUploads();
	} finally {
		restoreSiteTheme(originalTheme);
	}
});

test.beforeEach(async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
});

function preview(page) {
	return page.frameLocator('#formulize-editor-frame');
}

// A custom property as the preview's root element has it set
async function previewToken(page, token) {
	return preview(page).locator('html').evaluate((html, token) => html.style.getPropertyValue(token).trim(), token);
}

// What the editor knows about the theme: its colours' defaults, fonts, sizes and page width
async function editorData(page) {
	return page.evaluate(() => window.formulizeAppearanceEditor);
}

async function record(page) {
	return (await readLookFile(page, key)).record;
}

// Upload a logo and a favicon in the editor, open on the look, and save them; the look's record after
const uploaded = [];
async function uploadLogoAndFavicon(page) {
	await page.locator('#formulize-editor-appearance_logo').setInputFiles({ name: 'e2e-logo.png', mimeType: 'image/png', buffer: PNG });
	await expect(preview(page).locator('[data-fz-part="logo"] img')).toHaveAttribute('src', /^blob:/);
	await expect(page.locator('#formulize-editor-insp-body')).toContainText('A new image, saved when you save.');
	await page.locator('#formulize-editor-appearance_favicon').setInputFiles({ name: 'e2e-favicon.png', mimeType: 'image/png', buffer: PNG });
	await expect(page.locator('#formulize-editor-count')).toHaveText('2');
	await saveAppearanceEditor(page, LOOK);
	const saved = await record(page);
	expect(saved.appearance_logo).toMatch(/^formulize-appearance-logo-[0-9a-f-]+\.png$/);
	expect(saved.appearance_favicon).toMatch(/^formulize-appearance-favicon-[0-9a-f-]+\.png$/);
	uploaded.push(saved.appearance_logo, saved.appearance_favicon);
	return saved;
}

// Delete the images this spec uploaded that are still there, should a test have stopped before the save
// that takes them away. Deleting the look deletes the ones it still names.
function deleteLeftoverUploads() {
	for (const file of uploaded) {
		if (/^formulize-appearance-(logo|favicon)-[0-9a-f-]+\.png$/.test(file)) {
			execFileSync('docker', ['exec', webContainer(), 'rm', '-f', `/var/www/html/themes/Lyris/appearance/${file}`]);
		}
	}
}

test.describe('The simple editor', () => {

	test('A colour shows in the preview at once, is saved to the look, and can be reset to the theme\'s', async ({ page }) => {
		await openAppearanceEditor(page, key);
		const themePrimary = (await editorData(page)).colours.primary.default;
		const save = preview(page).locator('input[name="save"]');
		await expect(save).toHaveCSS('background-color', hexToRgb(themePrimary));

		await page.locator('input[data-colour="primary"]').fill('#1f6f8b');
		await expect.poll(() => previewToken(page, '--fz-color-accent')).toBe('#1f6f8b');
		await expect(save).toHaveCSS('background-color', 'rgb(31, 111, 139)');
		await expect(page.locator('#formulize-editor-count')).toHaveText('1');
		await saveAppearanceEditor(page, LOOK);
		expect((await record(page)).appearance_primary).toBe('#1f6f8b');

		await openAppearanceEditor(page, key);
		await expect(page.locator('input[data-colour="primary"]')).toHaveValue('#1f6f8b');
		await expect(save).toHaveCSS('background-color', 'rgb(31, 111, 139)');
		const reset = page.locator('button[data-colour-reset="primary"]');
		await expect(reset).toHaveText(`Reset to ${themePrimary}`);
		await reset.click();
		await expect(page.locator('input[data-colour="primary"]')).toHaveValue(themePrimary);
		await expect(save).toHaveCSS('background-color', hexToRgb(themePrimary));
		await saveAppearanceEditor(page, LOOK);
		expect((await record(page)).appearance_primary).toBeUndefined();
	});

	test('The main and secondary fonts, and a Google Font typed in, show in the preview and are saved', async ({ page }) => {
		await openAppearanceEditor(page, key);
		await page.locator('#formulize-editor-font-main').selectOption({ label: 'Inter' });
		await expect.poll(() => previewToken(page, '--fz-font-sans')).toContain('Inter');
		await page.locator('#formulize-editor-font-heading').selectOption({ label: 'IBM Plex Sans' });
		await expect.poll(() => previewToken(page, '--fz-font-heading')).toContain('IBM Plex Sans');
		await expect(page.locator('#formulize-editor-count')).toHaveText('2');
		await saveAppearanceEditor(page, LOOK);
		let saved = await record(page);
		expect(saved.appearance_font).toBe('inter');
		expect(saved.appearance_headingfont).toBe('ibm-plex');

		await openAppearanceEditor(page, key);
		await expect(page.locator('#formulize-editor-font-main')).toHaveValue('inter');
		await page.locator('#formulize-editor-font-main').selectOption('custom');
		await page.locator('input[data-custom-font="main"]').fill('Roboto Slab');
		await expect.poll(() => previewToken(page, '--fz-font-sans')).toContain('Roboto Slab');
		await saveAppearanceEditor(page, LOOK);
		saved = await record(page);
		expect(saved.appearance_font).toBe('custom');
		expect(saved.appearance_customfont).toBe('Roboto Slab');

		// back to the theme's own fonts
		await openAppearanceEditor(page, key);
		await page.locator('button[data-font-reset="main"]').click();
		await page.locator('button[data-font-reset="heading"]').click();
		await expect(page.locator('#formulize-editor-font-main')).toHaveValue('geist');
		await expect(page.locator('#formulize-editor-font-heading')).toHaveValue('geist');
		await saveAppearanceEditor(page, LOOK);
		saved = await record(page);
		expect(saved.appearance_font).toBeUndefined();
		expect(saved.appearance_headingfont).toBeUndefined();
	});

	test('A size writes its values into the look, and the preview shows them', async ({ page }) => {
		await openAppearanceEditor(page, key);
		const compact = (await editorData(page)).sizes.find(size => size.key === 'compact').values;
		const button = preview(page).locator('input[name="save"]');
		const standardHeight = parseFloat(await button.evaluate(el => getComputedStyle(el).height));
		await expect(page.locator('#formulize-editor-size')).toHaveValue('standard');

		await page.locator('#formulize-editor-size').selectOption('compact');
		await expect(button).toHaveCSS('height', `${compact['--fz-control-height'] * 4}px`);
		expect(compact['--fz-control-height'] * 4).toBeLessThan(standardHeight);
		await saveAppearanceEditor(page, LOOK);
		// every value of Compact's is in the look, except those that are the theme's own already: a look
		// only records what differs from what it is based on
		const tokens = (await editorData(page)).map.tokens;
		const overrides = JSON.parse((await record(page)).appearance_overrides);
		for (const [token, value] of Object.entries(compact)) {
			if (token in overrides) {
				expect(overrides[token], token).toEqual(value);
			} else {
				expect(String(tokens[token].default), token).toBe(String(value));
			}
		}
		expect(overrides['--fz-control-height']).toBe(compact['--fz-control-height']);

		await openAppearanceEditor(page, key);
		await expect(page.locator('#formulize-editor-size')).toHaveValue('compact');
		await expect(button).toHaveCSS('height', `${compact['--fz-control-height'] * 4}px`);
		// Standard is the theme's own sizes, which a look based on nothing doesn't need to record
		await page.locator('#formulize-editor-size').selectOption('standard');
		await expect(button).toHaveCSS('height', `${standardHeight}px`);
		await saveAppearanceEditor(page, LOOK);
		expect((await record(page)).appearance_overrides).toBeUndefined();
	});

	test('The page width and alignment show in the preview, keep to their limits, and are saved', async ({ page }) => {
		await openAppearanceEditor(page, key);
		const width = (await editorData(page)).contentWidth;

		await page.locator('button[data-cwidth="full"]').click();
		await expect.poll(() => previewToken(page, '--formulize-content-max-width')).toBe('100%');
		// a page as wide as the window has nowhere else to go
		await expect(page.locator('button[data-calign]')).toHaveCount(0);

		await page.locator('button[data-cwidth="max"]').click();
		const pixels = page.locator('input[data-cwidth-px]');
		await expect(pixels).toHaveValue(String(width.theme));
		await pixels.fill('50');
		await pixels.press('Tab');
		await expect(page.locator('input[data-cwidth-px]')).toHaveValue(String(width.min));
		await page.locator('input[data-cwidth-px]').fill('99999');
		await page.locator('input[data-cwidth-px]').press('Tab');
		await expect(page.locator('input[data-cwidth-px]')).toHaveValue(String(width.max));
		await page.locator('input[data-cwidth-px]').fill('900');
		await page.locator('input[data-cwidth-px]').press('Tab');
		await expect.poll(() => previewToken(page, '--formulize-content-max-width')).toBe('900px');

		await page.locator('button[data-calign="left"]').click();
		await expect(page.locator('button[data-calign="left"]')).toHaveAttribute('aria-pressed', 'true');
		await expect.poll(() => previewToken(page, '--formulize-content-align')).toBe(String(width.aligns.left));
		await saveAppearanceEditor(page, LOOK);
		const saved = await readLookFile(page, key);
		expect(saved.record.appearance_contentwidth).toBe('900');
		expect(saved.record.appearance_contentalign).toBe('left');
		expect(saved.css).toContain('--formulize-content-max-width: 900px');

		await openAppearanceEditor(page, key);
		await expect(page.locator('input[data-cwidth-px]')).toHaveValue('900');
		await page.locator('button[data-site-reset="contentalign"]').click();
		await page.locator('button[data-site-reset="contentwidth"]').click();
		await expect(page.locator('input[data-cwidth-px]')).toHaveValue(String(width.theme));
		await expect(page.locator(`button[data-calign="${width.align}"]`)).toHaveAttribute('aria-pressed', 'true');
		await saveAppearanceEditor(page, LOOK);
		const reset = await record(page);
		expect(reset.appearance_contentwidth).toBeUndefined();
		expect(reset.appearance_contentalign).toBeUndefined();
	});

	test('A logo and a favicon uploaded show in the preview, are saved with the look, and can be taken away again', async ({ page }) => {
		await openAppearanceEditor(page, key);
		const themeLogo = (await editorData(page)).themeLogoUrl;
		const logo = preview(page).locator('[data-fz-part="logo"] img');
		await expect(logo).toHaveAttribute('src', themeLogo);

		const saved = await uploadLogoAndFavicon(page);
		expect((await page.request.get(`/themes/Lyris/appearance/${saved.appearance_logo}`)).status()).toBe(200);
		expect((await page.request.get(`/themes/Lyris/appearance/${saved.appearance_favicon}`)).status()).toBe(200);

		await openAppearanceEditor(page, key);
		await expect(logo).toHaveAttribute('src', new RegExp(`/${saved.appearance_logo.replace(/\./g, '\\.')}(\\?|$)`));
		await expect(page.locator('#formulize-editor-insp-body')).toContainText('This look’s own.');
		await page.locator('button[data-upload-remove="appearance_logo"]').click();
		await page.locator('button[data-upload-remove="appearance_favicon"]').click();
		await expect(logo).toHaveAttribute('src', themeLogo);
		await saveAppearanceEditor(page, LOOK);

		const removed = await record(page);
		expect(removed.appearance_logo).toBeUndefined();
		expect(removed.appearance_favicon).toBeUndefined();
		await openAppearanceEditor(page, key);
		await expect(logo).toHaveAttribute('src', themeLogo);
	});

	// An image a save stops naming is deleted, once no look names it (a copy of a look has the same images)
	test('An image taken away is deleted from the server, once no look names it', async ({ page }) => {
		await openAppearanceEditor(page, key);
		const saved = await uploadLogoAndFavicon(page);
		await openAppearanceEditor(page, key);
		await page.locator('button[data-upload-remove="appearance_logo"]').click();
		await page.locator('button[data-upload-remove="appearance_favicon"]').click();
		await saveAppearanceEditor(page, LOOK);
		expect((await page.request.get(`/themes/Lyris/appearance/${saved.appearance_logo}`)).status()).toBe(404);
		expect((await page.request.get(`/themes/Lyris/appearance/${saved.appearance_favicon}`)).status()).toBe(404);
	});
});

function hexToRgb(hex) {
	const [r, g, b] = [1, 3, 5].map(i => parseInt(hex.substr(i, 2), 16));
	return `rgb(${r}, ${g}, ${b})`;
}
