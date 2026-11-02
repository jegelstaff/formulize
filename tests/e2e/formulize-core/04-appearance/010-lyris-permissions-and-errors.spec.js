const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import {
	login, getSystemConfig, setSystemConfig, openLooksTab, lookRow, createLook, deleteLooksNamed,
	readLookFile, openAppearanceEditor, LYRIS_LOOKS_URL,
} from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Lyris appearance (1 of 8) - who can change a look, and what happens when a change can't be made
// ============================================================
// The appearance specs (010-080) test the Looks tab (Admin > Appearance > Looks) and the appearance
// editor on the Lyris theme. They run one at a time, after the setup and validate specs, on the
// finished museum system, and they put back whatever they change, so the site is as it was for what
// runs after them: see "Appearance" in utils.js. This one is first so that the suite stops straight away
// when the pages are open to the wrong people, or when a change that couldn't be made is reported as
// made (playwright.config.js stops at the first failure).
//
// It needs a user who isn't a Formulize admin (curator1, from 01-setup/005), and changes nothing on the site:
// the only look it makes, e2e-errors, is deleted at the end, and the system lock it turns on is turned
// off again in the same test.

const LOOK = 'e2e-errors';
let lookKey = '';

async function adminPage(browser) {
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	return page;
}

test.beforeAll(async ({ browser }) => {
	const page = await adminPage(browser);
	lookKey = await createLook(page, LOOK, { base: '' });
	await page.close();
});

test.afterAll(async ({ browser }) => {
	const page = await adminPage(browser);
	await deleteLooksNamed(page, 'e2e-');
	await page.close();
});

test.describe('Lyris looks: permissions', () => {

	test('Someone who is not a Formulize admin cannot open the Looks tab or the appearance editor', async ({ page }) => {
		await login(page, 'curator1');

		// The admin pages send someone who isn't an admin away, to user.php, without showing anything. Asked
		// for without following the redirect, since where user.php goes next is the profile module's business.
		const looksTab = await page.request.get(LYRIS_LOOKS_URL, { maxRedirects: 0 });
		expect(looksTab.status()).toBe(302);
		expect(looksTab.headers()['location']).toMatch(/\/user\.php$/);
		expect(await looksTab.text()).not.toContain('looks-create');

		await page.goto(`/modules/formulize/appearance_editor.php?theme=Lyris&look=${lookKey}`);
		await expect(page.getByText("Sorry, you don't have permission to access this area.")).toBeVisible();
		await expect(page.locator('#formulize-editor-frame')).toHaveCount(0);
	});

	test('Someone who is not a Formulize admin cannot change a look by posting to the pages directly', async ({ page }) => {
		const before = await readLookFile(page, lookKey);
		await login(page, 'curator1');

		// the Looks tab's forms: make a look, and delete one. The admin check comes before the token check,
		// so no token is needed to show that it refuses.
		await page.request.post('/modules/formulize/admin/looks.php', {
			form: { looks_action: 'create', theme: 'Lyris', name: 'e2e-intruder', base: '' },
		});
		await page.request.post('/modules/formulize/admin/looks.php', {
			form: { looks_action: 'delete', theme: 'Lyris', look: lookKey },
		});
		// the editor's save, as its script sends it
		const save = await page.request.post(`/modules/formulize/appearance_editor.php?theme=Lyris&look=${lookKey}`, {
			form: {
				appearance_editor_save: '1', appearance_editor_ajax: '1', theme: 'Lyris', look: lookKey,
				appearance_look_settings: JSON.stringify({ appearance_primary: '#ff0000' }),
			},
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
		});
		expect(await save.text()).not.toContain('"saved":true');

		expect(await readLookFile(page, 'e2e-intruder')).toBeNull();
		const after = await readLookFile(page, lookKey);
		expect(after).not.toBeNull();
		expect(after.record).toEqual(before.record);
		expect(after.record.appearance_primary).toBeUndefined();
	});
});

test.describe('Lyris looks: problems are reported, not ignored', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	});

	test('A look cannot be given a name the theme already has', async ({ page }) => {
		await openLooksTab(page);
		const rows = await page.locator('table.fz-tree tbody tr').count();
		await page.getByRole('button', { name: 'Create new look', exact: true }).click();
		await page.locator('#looks-create-name').fill(LOOK.toUpperCase());
		await page.locator('#looks-create').getByRole('button', { name: 'Create look', exact: true }).click();
		await expect(page.locator('.fz-callout--danger')).toHaveText(`Lyris already has a look called ${LOOK}. Please give this one another name.`);
		await expect(page.locator('table.fz-tree tbody tr')).toHaveCount(rows);
	});

	test('A Looks tab form sent with a stale token changes nothing, and says why', async ({ page }) => {
		await openLooksTab(page);
		await page.getByRole('button', { name: 'Create new look', exact: true }).click();
		const dialog = page.locator('#looks-create');
		await dialog.locator('#looks-create-name').fill('e2e-stale');
		// what a token that has expired looks like to the server: one it doesn't have
		await dialog.locator('input[name$="_token_REQUEST"]').evaluate(input => { input.value = 'stale'; });
		await dialog.getByRole('button', { name: 'Create look', exact: true }).click();
		await expect(page.locator('.fz-callout--danger')).toHaveText('Nothing was changed, because the page had been open too long. Please try again.');
		await expect(lookRow(page, 'e2e-stale')).toHaveCount(0);
		expect(await readLookFile(page, 'e2e-stale')).toBeNull();
	});

	test('The editor says when a save is refused because its token is stale, and the note stays', async ({ page }) => {
		await openAppearanceEditor(page, lookKey);
		await page.locator('input[data-colour="primary"]').fill('#7a1fa2');
		await expect(page.locator('#formulize-editor-count')).toHaveText('1');
		await page.locator('#formulize-editor-form input[name$="_token_REQUEST"]').evaluate(input => { input.value = 'stale'; });
		await page.locator('#formulize-editor-save').click();

		const problem = page.locator('.formulize-editor__toast[role="alert"]');
		await expect(problem).toContainText('Nothing was saved, because the page had been open too long.');
		// a note that went well goes after five seconds; a problem stays until it is closed
		await page.waitForTimeout(6000);
		await expect(problem).toBeVisible();
		// and the change is still there to save, not lost
		await expect(page.locator('#formulize-editor-count')).toHaveText('1');
		expect((await readLookFile(page, lookKey)).record.appearance_primary).toBeUndefined();
		await problem.getByRole('button', { name: 'Close' }).click();
		await expect(problem).toHaveCount(0);
	});

	test('A logo that is not an image is refused, whatever it is called, and the note says why', async ({ page }) => {
		await openAppearanceEditor(page, lookKey);
		await page.locator('#formulize-editor-appearance_logo').setInputFiles({
			name: 'logo.png', mimeType: 'image/png', buffer: Buffer.from('This is text, not a picture.'),
		});
		await expect(page.locator('#formulize-editor-count')).toHaveText('1');
		await page.locator('#formulize-editor-save').click();

		const problem = page.locator('.formulize-editor__toast[role="alert"]');
		await expect(problem).toContainText('The logo must be a PNG, JPEG, GIF, SVG, or WebP image.');
		await page.waitForTimeout(6000);
		await expect(problem).toBeVisible();
		expect((await readLookFile(page, lookKey)).record.appearance_logo).toBeUndefined();
	});

	test('While the system is locked, nothing is changed, and both pages say so', async ({ page }) => {
		const wasLocked = getSystemConfig('isSaveLocked');
		setSystemConfig('isSaveLocked', 1);
		try {
			await openLooksTab(page);
			await expect(page.locator('.fz-callout--warning')).toContainText('Nothing can be changed, because this system is locked.');
			await page.getByRole('button', { name: 'Create new look', exact: true }).click();
			await page.locator('#looks-create-name').fill('e2e-locked');
			await page.locator('#looks-create').getByRole('button', { name: 'Create look', exact: true }).click();
			await expect(page.locator('.fz-callout--danger')).toContainText('Nothing was changed, because this system is locked.');
			await expect(lookRow(page, 'e2e-locked')).toHaveCount(0);

			await openAppearanceEditor(page, lookKey);
			await expect(page.locator('.formulize-editor__message--note')).toContainText('Nothing can be saved, because this system is locked.');
			await page.locator('input[data-colour="primary"]').fill('#7a1fa2');
			await page.locator('#formulize-editor-save').click();
			await expect(page.locator('.formulize-editor__toast[role="alert"]')).toContainText('Nothing was saved, because this system is locked.');
			expect((await readLookFile(page, lookKey)).record.appearance_primary).toBeUndefined();
		} finally {
			setSystemConfig('isSaveLocked', wasLocked === null ? 0 : wasLocked);
		}
	});
});
