const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import {
	login, dbQuery, dbPrefix, getSiteTheme, waitForAdminPageReady, saveAdminForm,
	chooseSiteThemes, expectSiteTheme, themeHasBeenActivated, forgetThemeActivated,
} from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Lyris appearance - the first switch to Lyris on Appearance > Settings
// ============================================================
// The first time a site's default theme is changed to Lyris on Appearance > Settings, saving asks about Lyris first:
// its look, Lyris's settings for the screens, and which screens take their custom templates along. It's the 8.30
// update's questions without the one about the theme. Once Lyris has been the site's theme, switching to it asks
// nothing.
//
// Formulize records that a theme has been the site's theme with activated.lock, in the theme's appearance folder,
// and the CI site has been on Lyris since it was installed. So the spec deletes that file first, as a webmaster
// would to be asked again. The application has no way to do that.
//
// It changes one setting on the museum's Collections list screen, and the site's theme, and puts both back.

test.describe.configure({ mode: 'serial' });

const SETTINGS_PAGE = '/modules/formulize/admin/ui.php?page=appearance&view=settings';
let collections = null;
let settingsBefore = null;

function listScreen(title) {
	const [row] = dbQuery(`SELECT sid, fid FROM ${dbPrefix()}_formulize_screen WHERE title = '${title}' AND type = 'listOfEntries'`);
	return { sid: parseInt(row[0], 10), fid: parseInt(row[1], 10) };
}

// Every list screen's settings that the questions can change, by screen
function listSettings() {
	const settings = {};
	for (const [sid, editdestination, usesearch, entriesperpage] of dbQuery(`SELECT sid, editdestination, usesearch, entriesperpage FROM ${dbPrefix()}_formulize_screen_listofentries`)) {
		settings[sid] = { editdestination, usesearch, entriesperpage };
	}
	return settings;
}

async function adminPage(browser) {
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	return page;
}

// Choose the themes on Appearance > Settings and click Save, without waiting for the page to be saved
async function chooseThemesAndSave(page, site, admin) {
	await page.goto(SETTINGS_PAGE);
	await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
	await page.locator('#theme_set').selectOption(site);
	await page.locator('#theme_admin_set').selectOption(admin);
	await page.getByRole('button', { name: 'Save your changes' }).click();
}

// The questions, once saving has opened them
async function openedWizard(page) {
	const dialog = page.locator('#formulize-lyris-wizard');
	await expect(dialog).toBeVisible();
	return dialog;
}

async function next(dialog) {
	await dialog.getByRole('button', { name: 'Next' }).click();
}

// Go through the steps to the summary, choosing none of the templates on the way, if there's a step for them
async function nextToSummary(dialog) {
	const summary = dialog.getByRole('heading', { name: 'Ready to switch' });
	for (let step = 0; step < 4 && !(await summary.isVisible()); step++) {
		if (await dialog.getByRole('heading', { name: 'Screens with their own templates' }).isVisible()) {
			await dialog.getByRole('button', { name: 'Select none' }).click();
		}
		await next(dialog);
	}
	await expect(summary).toBeVisible();
}

// Put Collections back on the setting it had for where it opens entries
async function restoreCollections(page) {
	const before = settingsBefore ? settingsBefore[collections.sid] : null;
	if (!before || listSettings()[collections.sid].editdestination === before.editdestination) {
		return;
	}
	await page.goto(`/modules/formulize/admin/ui.php?page=screen&fid=${collections.fid}&sid=${collections.sid}&tab=interface`);
	await waitForAdminPageReady(page);
	await page.locator(`input[name="screens-editdestination"][value="${before.editdestination}"]`).check();
	await saveAdminForm(page);
	expect(listSettings()[collections.sid].editdestination).toBe(before.editdestination);
}

test.beforeAll(async () => {
	expect(getSiteTheme().site).toBe('Anari');
	collections = listScreen('Collections');
	settingsBefore = listSettings();
	forgetThemeActivated('Lyris');
	expect(themeHasBeenActivated('Lyris')).toBe(false);
});

test.afterAll(async ({ browser }) => {
	const page = await adminPage(browser);
	try {
		// Lyris is recorded as having been the site's theme by switching to it, as it was before the spec
		if (!themeHasBeenActivated('Lyris')) {
			await chooseThemesAndSave(page, 'Lyris', 'Anari');
			const dialog = await openedWizard(page);
			await dialog.getByRole('button', { name: 'Switch without these changes' }).click();
			await expect.poll(() => getSiteTheme().site, { timeout: 60000 }).toBe('Lyris');
		}
		if (getSiteTheme().site !== 'Anari' || getSiteTheme().admin !== 'Anari') {
			await chooseSiteThemes(page, 'Anari', 'Anari');
		}
		await restoreCollections(page);
	} finally {
		await page.close();
	}
	expect(getSiteTheme().site).toBe('Anari');
	expect(themeHasBeenActivated('Lyris')).toBe(true);
});

test('Saving Lyris as the default theme asks about Lyris first, and Cancel saves nothing', async ({ browser }) => {
	const page = await adminPage(browser);
	await chooseThemesAndSave(page, 'Lyris', 'Anari');
	const dialog = await openedWizard(page);
	// Lyris was chosen already, so the questions start with its looks, and say why they're asked
	await expect(dialog.getByRole('heading', { name: 'Switch to Lyris' })).toBeVisible();
	await expect(dialog.getByRole('heading', { name: 'How Lyris looks' })).toBeVisible();
	await expect(dialog.getByText("Lyris hasn't been your site's theme before")).toBeVisible();
	await expect(dialog.getByRole('heading', { name: 'Formulize has a new theme' })).toHaveCount(0);
	// every look Lyris has, shown in a preview of Lyris
	await expect(dialog.locator('label', { hasText: 'Altan' })).toBeVisible();
	await expect(dialog.locator('iframe[data-theme="Lyris"][data-look]').first()).toHaveAttribute('src', /appearance_preview\.php\?theme=Lyris/);
	await next(dialog);
	// Lyris's settings, checked for every screen, since the whole site is switching
	await expect(dialog.getByRole('heading', { name: "Lyris's screen settings" })).toBeVisible();
	await expect(dialog.locator(`input[name="lyris_screen_settings[${collections.sid}][]"][value="editdestination"]`)).toBeChecked();
	await dialog.getByRole('button', { name: 'Cancel' }).click();
	await expect(dialog).toBeHidden();

	// nothing was saved, and the page is still the settings
	await expect(page).toHaveURL(/page=appearance&view=settings/);
	expect(getSiteTheme().site).toBe('Anari');
	expect(getSiteTheme().admin).toBe('Anari');
	expect(listSettings()).toEqual(settingsBefore);
	expect(themeHasBeenActivated('Lyris')).toBe(false);
	await page.close();
});

test('Switch to Lyris gives the chosen screens Lyris\'s settings, switches the site, and saves the other settings', async ({ browser }) => {
	test.setTimeout(300000);
	const page = await adminPage(browser);
	// the admin theme changes too, which the settings save, after the questions have switched the site's theme
	await chooseThemesAndSave(page, 'Lyris', 'Lyris');
	const dialog = await openedWizard(page);
	await next(dialog);
	// only Collections, and only the drawer
	await expect(dialog.getByRole('heading', { name: "Lyris's screen settings" })).toBeVisible();
	for (const all of await dialog.locator('.formulize-lyris-setting-all').all()) {
		await all.uncheck();
	}
	await dialog.locator(`input[name="lyris_screen_settings[${collections.sid}][]"][value="editdestination"]`).check();
	await nextToSummary(dialog);
	await expect(dialog.locator('.formulize-lyris-summary')).toContainText('Your site switches to Lyris.');
	await expect(dialog.locator('.formulize-lyris-summary')).toContainText('1 screen gets Lyris\'s screen settings.');
	await dialog.getByRole('button', { name: 'Switch to Lyris', exact: true }).click();

	// the settings are saved once the theme has switched, so the admin theme is the last thing to change
	await expect.poll(() => getSiteTheme().admin, { timeout: 120000 }).toBe('Lyris');
	expect(getSiteTheme().site).toBe('Lyris');
	await expectSiteTheme(page, 'Lyris');
	expect(themeHasBeenActivated('Lyris')).toBe(true);
	// every screen follows the site
	expect(dbQuery(`SELECT theme FROM ${dbPrefix()}_formulize_screen WHERE sid = ${collections.sid}`)[0][0]).toBe('Lyris');
	// Collections opens its entries in the drawer, and nothing else changed
	const expected = JSON.parse(JSON.stringify(settingsBefore));
	expected[collections.sid].editdestination = 'drawer';
	expect(listSettings()).toEqual(expected);
	await page.close();
});

test('Once Lyris has been the site\'s theme, switching to it again asks nothing', async ({ browser }) => {
	const page = await adminPage(browser);
	await chooseSiteThemes(page, 'Anari', 'Anari');
	await restoreCollections(page);
	await chooseThemesAndSave(page, 'Lyris', 'Anari');
	await expect.poll(() => getSiteTheme().site, { timeout: 60000 }).toBe('Lyris');
	await page.goto(SETTINGS_PAGE);
	await expect(page.locator('#theme_set')).toHaveValue('Lyris');
	await expect(page.locator('#formulize-lyris-wizard')).toHaveCount(0);
	expect(listSettings()).toEqual(settingsBefore);
	await chooseSiteThemes(page, 'Anari', 'Anari');
	await page.close();
});

test('Switch without these changes switches the site and leaves the screens as they are', async ({ browser }) => {
	forgetThemeActivated('Lyris');
	const page = await adminPage(browser);
	await chooseThemesAndSave(page, 'Lyris', 'Anari');
	const dialog = await openedWizard(page);
	await dialog.getByRole('button', { name: 'Switch without these changes' }).click();

	await expect.poll(() => getSiteTheme().site, { timeout: 60000 }).toBe('Lyris');
	await page.goto(SETTINGS_PAGE);
	await expect(page.locator('#theme_set')).toHaveValue('Lyris');
	await expect(page.locator('#theme_admin_set')).toHaveValue('Anari');
	expect(themeHasBeenActivated('Lyris')).toBe(true);
	expect(listSettings()).toEqual(settingsBefore);
	await chooseSiteThemes(page, 'Anari', 'Anari');
	await page.close();
});
