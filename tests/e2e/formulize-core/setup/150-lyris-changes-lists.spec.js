const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import {
	login, getSiteTheme, setSiteThemeInDb, restoreSiteTheme, createLook, deleteLooksNamed, readLookFile,
	openAppearanceEditor, saveAppearanceEditor,
} from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Lyris appearance (6 of 8) - the editor's lists of changes
// ============================================================
// Two buttons beside Save each open a list. Unsaved changes: what has been changed since the last save,
// each with what it was and Undo, and Undo all. Saved changes: what the look, as saved, changes from
// what it is based on, each with Reset, and a note on any that has been changed again or reset and not
// saved yet. In the advanced editor a change to a part has Show, which selects the part. Esc, or a click
// anywhere else, closes a list.
//
// It uses one look, e2e-changes, based on nothing, so what it is measured against is the Lyris theme
// itself. The afterAll deletes it, and puts the site's theme back.

test.describe.configure({ mode: 'serial' });

const LOOK = 'e2e-changes';
const COLOUR = '#aa3311';
let key = '';
let originalTheme = null;
let themePrimary = '';
let themeCorners = 0;

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
	const data = await page.evaluate(() => window.formulizeAppearanceEditor);
	themePrimary = data.colours.primary.default;
	themeCorners = data.map.tokens['--fz-button-radius'].default;
});

function preview(page) {
	return page.frameLocator('#formulize-editor-frame');
}

const unsavedButton = page => page.locator('#formulize-editor-unsaved');
const unsavedPanel = page => page.locator('#formulize-editor-unsaved-panel');
const savedButton = page => page.locator('#formulize-editor-diff');
const savedPanel = page => page.locator('#formulize-editor-diff-panel');
const count = page => page.locator('#formulize-editor-count');
const heading = page => page.locator('#formulize-editor-insp-head h2');

// A change's row in one of the lists, by the name of what changed
function row(panel, name) {
	return panel.locator('.formulize-editor__chg').filter({ has: panel.page().locator('.formulize-editor__chg-name', { hasText: name }) });
}

// Two changes: the look's primary colour, and the buttons' corners, one step rounder
async function makeTwoChanges(page) {
	await page.locator('input[data-colour="primary"]').fill(COLOUR);
	await expect(preview(page).locator('input[name="save"]')).toHaveCSS('background-color', hexToRgb(COLOUR));
	await preview(page).locator('input[name="save"]').click();
	await expect(heading(page)).toHaveText('Button');
	await page.locator('.formulize-editor__ctl[data-ctl="--fz-button-radius"]').getByRole('button', { name: 'Larger' }).click();
	await expect(count(page)).toHaveText('2');
}

test.describe('The lists of changes', () => {

	test('Unsaved changes lists each change since the last save, with what it was, Undo, Show and Undo all', async ({ page }) => {
		await expect(unsavedButton(page)).toBeDisabled();
		await expect(count(page)).toBeHidden();
		await expect(page.locator('#formulize-editor-save')).toBeDisabled();

		await makeTwoChanges(page);
		await expect(unsavedButton(page)).toBeEnabled();
		await expect(unsavedButton(page)).toHaveAttribute('aria-label', '2 unsaved changes');
		await unsavedButton(page).click();
		const panel = unsavedPanel(page);
		await expect(panel).toBeVisible();
		await expect(panel.locator('.formulize-editor__chg')).toHaveCount(2);
		await expect(row(panel, 'Primary')).toContainText(`${COLOUR} · was ${themePrimary}`);
		await expect(row(panel, 'Button: corners')).toContainText(`${themeCorners + 1}px · was ${themeCorners}px`);
		// only a part's change can be shown
		await expect(row(panel, 'Primary').getByRole('button', { name: 'Show' })).toHaveCount(0);

		await row(panel, 'Primary').getByRole('button', { name: 'Undo' }).click();
		await expect(count(page)).toHaveText('1');
		await expect(panel.locator('.formulize-editor__chg')).toHaveCount(1);
		await expect(preview(page).locator('input[name="save"]')).toHaveCSS('background-color', hexToRgb(themePrimary));

		// Esc closes the list first, and only then lets go of the part
		await page.keyboard.press('Escape');
		await expect(panel).toBeHidden();
		await expect(heading(page)).toHaveText('Button');
		await page.keyboard.press('Escape');
		await expect(heading(page)).toHaveText('Branding and layout');

		// Show closes the list and selects the part
		await unsavedButton(page).click();
		await row(panel, 'Button: corners').getByRole('button', { name: 'Show' }).click();
		await expect(panel).toBeHidden();
		await expect(heading(page)).toHaveText('Button');

		// Undo all asks first, then throws away everything not saved, and the list can't be opened
		await unsavedButton(page).click();
		let question = '';
		page.once('dialog', dialog => { question = dialog.message(); dialog.accept(); });
		await panel.getByRole('button', { name: 'Undo all' }).click();
		await expect.poll(() => question).toBe(`Throw away the changes you haven’t saved, so ${LOOK} is as it was last saved?`);
		await expect(count(page)).toBeHidden();
		await expect(unsavedButton(page)).toBeDisabled();
		await expect(panel).toBeHidden();
		await expect(page.locator('#formulize-editor-save')).toBeDisabled();
	});

	test('Saved changes lists what the saved look changes from the theme, with Reset and Show', async ({ page }) => {
		await savedButton(page).click();
		const panel = savedPanel(page);
		await expect(panel).toContainText('Nothing saved differs from the Lyris theme.');
		await page.keyboard.press('Escape');
		await expect(panel).toBeHidden();

		await makeTwoChanges(page);
		await saveAppearanceEditor(page, LOOK);
		// what is saved isn't in the other list
		await expect(unsavedButton(page)).toBeDisabled();

		await savedButton(page).click();
		await expect(panel).toContainText('The saved settings that differ from the Lyris theme.');
		await expect(panel.locator('.formulize-editor__chg')).toHaveCount(2);
		await expect(row(panel, 'Primary')).toContainText(`${COLOUR} · the Lyris theme’s is ${themePrimary}`);
		await expect(row(panel, 'Button: corners')).toContainText(`${themeCorners + 1}px · the Lyris theme’s is ${themeCorners}px`);

		// Reset is a change like any other: noted in this list, and undone or saved from the other
		await row(panel, 'Primary').getByRole('button', { name: 'Reset' }).click();
		await expect(row(panel, 'Primary')).toContainText('Reset, not saved yet');
		await expect(row(panel, 'Primary').getByRole('button', { name: 'Reset' })).toHaveCount(0);
		await expect(count(page)).toHaveText('1');
		await expect(preview(page).locator('input[name="save"]')).toHaveCSS('background-color', hexToRgb(themePrimary));

		// Show selects the part, and closes the list
		await row(panel, 'Button: corners').getByRole('button', { name: 'Show' }).click();
		await expect(panel).toBeHidden();
		await expect(heading(page)).toHaveText('Button');
		await page.locator('.formulize-editor__ctl[data-ctl="--fz-button-radius"]').getByRole('button', { name: 'Larger' }).click();
		await savedButton(page).click();
		await expect(row(panel, 'Button: corners')).toContainText('Changed again, not saved yet');

		// a click anywhere else closes it; Esc closes it before it lets go of the part
		await page.locator('.formulize-editor__editing h1').click();
		await expect(panel).toBeHidden();
		await savedButton(page).click();
		await expect(panel).toBeVisible();
		await page.keyboard.press('Escape');
		await expect(panel).toBeHidden();
		await expect(heading(page)).toHaveText('Button');
		await page.keyboard.press('Escape');
		await expect(heading(page)).toHaveText('Branding and layout');

		await saveAppearanceEditor(page, LOOK);
		await savedButton(page).click();
		await expect(panel.locator('.formulize-editor__chg')).toHaveCount(1);
		await expect(row(panel, 'Button: corners')).toContainText(`${themeCorners + 2}px · the Lyris theme’s is ${themeCorners}px`);
		const saved = (await readLookFile(page, key)).record;
		expect(saved.appearance_primary).toBeUndefined();
		expect(JSON.parse(saved.appearance_overrides)).toEqual({ '--fz-button-radius': themeCorners + 2 });
	});
});

function hexToRgb(hex) {
	const [r, g, b] = [1, 3, 5].map(i => parseInt(hex.substr(i, 2), 16));
	return `rgb(${r}, ${g}, ${b})`;
}
