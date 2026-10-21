const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import {
	login, dbQuery, dbPrefix, getSiteTheme, setSiteThemeInDb, restoreSiteTheme, createLook, getSiteLookKey, setSiteLook,
	deleteLooksNamed, readLookFile, openAppearanceEditor, saveAppearanceEditor,
} from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Lyris appearance (7 of 8) - saving, and looks based on other looks
// ============================================================
// Saving writes the look's stylesheet (themes/Lyris/appearance/looks/<look>.css): a record of what the
// look changes itself, then the look's CSS as pages are shown with it. A look based on another follows
// it in everything it doesn't change itself, so a change to a base is written into the looks based on
// it too. A look only keeps what differs from its base: set something to what the base has, or change
// the base to match it, and the look follows the base for it from then on. And beside Save, the editor
// says what a save will change: the site, when the look is the site look or one it is based on, and the
// screens set to the look or to a look based on it.
//
// It uses e2e-base, based on nothing, and e2e-kid, based on e2e-base, and borrows one screen, set to
// e2e-kid for the last test. The afterAll lets go of the screen, puts the site look back, deletes the
// looks, and puts the site's theme back.

test.describe.configure({ mode: 'serial' });

const BASE = 'e2e-base';
const KID = 'e2e-kid';
const keys = {};
let originalTheme = null;
let originalSiteLook = '';
let screen = 0;

async function adminPage(browser) {
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	return page;
}

test.beforeAll(async ({ browser }) => {
	originalTheme = getSiteTheme();
	setSiteThemeInDb('Lyris');
	const page = await adminPage(browser);
	originalSiteLook = await getSiteLookKey(page);
	keys.base = await createLook(page, BASE, { base: '' });
	keys.kid = await createLook(page, KID, { base: keys.base });
	await page.close();
	screen = parseInt(dbQuery(`SELECT sid FROM ${dbPrefix()}_formulize_screen WHERE look = '' ORDER BY sid LIMIT 1`)[0][0], 10);
});

test.afterAll(async ({ browser }) => {
	try {
		dbQuery(`UPDATE ${dbPrefix()}_formulize_screen SET look = '' WHERE sid = ${screen}`);
		const page = await adminPage(browser);
		await setSiteLook(page, originalSiteLook);
		await deleteLooksNamed(page, 'e2e-');
		await page.close();
	} finally {
		restoreSiteTheme(originalTheme);
	}
});

test.beforeEach(async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
});

// Set a look's primary colour in the simple editor, and save it
async function savePrimary(page, key, name, colour) {
	await openAppearanceEditor(page, key);
	await page.locator('input[data-colour="primary"]').fill(colour);
	await saveAppearanceEditor(page, name);
}

// The primary colour a look's stylesheet gives pages
async function cssPrimary(page, key) {
	const { css } = await readLookFile(page, key);
	return css.match(/--fz-color-accent:\s*([^;]+);/)[1].trim();
}

test.describe('Saving, and looks based on other looks', () => {

	test('Saving writes the look\'s stylesheet, and a change to a look reaches the looks based on it', async ({ page }) => {
		await savePrimary(page, keys.base, BASE, '#8b1f5c');
		expect((await readLookFile(page, keys.base)).record.appearance_primary).toBe('#8b1f5c');
		expect(await cssPrimary(page, keys.base)).toBe('#8b1f5c');

		// e2e-kid doesn't change it, so it records nothing, and has its base's
		const kid = await readLookFile(page, keys.kid);
		expect(kid.record.base).toBe(keys.base);
		expect(kid.record.appearance_primary).toBeUndefined();
		expect(await cssPrimary(page, keys.kid)).toBe('#8b1f5c');

		await openAppearanceEditor(page, keys.kid);
		await expect(page.locator('.formulize-editor__editing')).toContainText(`Lyris · based on ${BASE}`);
		await expect(page.locator('#formulize-editor-insp-body')).toContainText(`Anything it doesn’t change comes from ${BASE}, and follows it when it changes.`);
		await expect(page.locator('input[data-colour="primary"]')).toHaveValue('#8b1f5c');
		await expect(page.locator('.formulize-editor__colour', { has: page.locator('input[data-colour="primary"]') })).not.toHaveClass(/is-changed/);
	});

	test('A look keeps what it changes itself when the look it is based on changes', async ({ page }) => {
		await savePrimary(page, keys.kid, KID, '#1f8b3a');
		expect((await readLookFile(page, keys.kid)).record.appearance_primary).toBe('#1f8b3a');
		await page.locator('#formulize-editor-diff').click();
		await expect(page.locator('#formulize-editor-diff-panel')).toContainText(`The saved settings that differ from ${BASE}.`);
		await expect(page.locator('#formulize-editor-diff-panel .formulize-editor__chg')).toContainText(`#1f8b3a · ${BASE}’s is #8b1f5c`);

		await savePrimary(page, keys.base, BASE, '#333399');
		expect(await cssPrimary(page, keys.base)).toBe('#333399');
		expect((await readLookFile(page, keys.kid)).record.appearance_primary).toBe('#1f8b3a');
		expect(await cssPrimary(page, keys.kid)).toBe('#1f8b3a');
	});

	test('A look only keeps what differs from its base, whichever of them changed to match', async ({ page }) => {
		// the look set to what its base has: Use e2e-base's
		await openAppearanceEditor(page, keys.kid);
		const useBase = page.locator('button[data-colour-reset="primary"]');
		await expect(useBase).toHaveText(`Use ${BASE}’s`);
		await useBase.click();
		await expect(page.locator('input[data-colour="primary"]')).toHaveValue('#333399');
		await saveAppearanceEditor(page, KID);
		expect((await readLookFile(page, keys.kid)).record.appearance_primary).toBeUndefined();
		await page.locator('#formulize-editor-diff').click();
		await expect(page.locator('#formulize-editor-diff-panel')).toContainText(`Nothing saved differs from ${BASE}.`);

		// the base changed to what the look has: the look drops it, and follows its base from then on
		await savePrimary(page, keys.kid, KID, '#1f8b3a');
		expect((await readLookFile(page, keys.kid)).record.appearance_primary).toBe('#1f8b3a');
		await savePrimary(page, keys.base, BASE, '#1f8b3a');
		expect((await readLookFile(page, keys.kid)).record.appearance_primary).toBeUndefined();
		await savePrimary(page, keys.base, BASE, '#5c3d99');
		expect(await cssPrimary(page, keys.kid)).toBe('#5c3d99');
	});

	test('Beside Save, the editor says what a save changes: the site, and the screens set to the look or looks based on it', async ({ page }) => {
		const live = page.locator('#formulize-editor-live');
		// nothing uses them
		await openAppearanceEditor(page, keys.base);
		await expect(live).toBeHidden();
		await openAppearanceEditor(page, keys.kid);
		await expect(live).toBeHidden();

		// a screen set to e2e-kid: a change to e2e-base reaches it too
		dbQuery(`UPDATE ${dbPrefix()}_formulize_screen SET look = '${keys.kid}' WHERE sid = ${screen}`);
		await openAppearanceEditor(page, keys.kid);
		await expect(live).toHaveText('Saving changes 1 screen');
		await openAppearanceEditor(page, keys.base);
		await expect(live).toHaveText('Saving changes 1 screen');

		// e2e-base as the site look
		await setSiteLook(page, keys.base);
		await openAppearanceEditor(page, keys.base);
		await expect(live).toHaveText('Saving changes the site and 1 screen');
		await page.locator('input[data-colour="primary"]').fill('#99331f');
		await page.locator('#formulize-editor-save').click();
		await expect(page.locator('.formulize-editor__toast[role="status"] .formulize-editor__toast-text')).toHaveText(`Saved ${BASE}. This changed the site and 1 screen.`);
		await openAppearanceEditor(page, keys.kid);
		await expect(live).toHaveText('Saving changes 1 screen');

		await setSiteLook(page, originalSiteLook);
		await openAppearanceEditor(page, keys.base);
		await expect(live).toHaveText('Saving changes 1 screen');
	});
});
