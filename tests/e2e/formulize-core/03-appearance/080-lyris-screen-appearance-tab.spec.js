const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import {
	login, dbQuery, dbPrefix, getSiteTheme, setSiteThemeInDb, restoreSiteTheme, createLook, deleteLooksNamed,
	createMuseumForm, deleteMuseumForm, saveAdminForm, waitForAdminPageReady, setCodeMirrorValue,
} from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Lyris appearance (8 of 8) - a screen's Appearance tab
// ============================================================
// Each screen has an Appearance tab. Look shows the screen with one of the theme's looks instead of the
// site's; Page width lets it use the full width of the window, whatever its look's page width is; and
// Introductory text is shown above the screen. Each is checked on the screen's own page, as the site
// shows it.
//
// It makes its own form in the Museum application, so the screens it changes are its own, and a look,
// e2e-screen, which is Compact so that a screen shown with it is measurably different from the site.
// The form is made, and deleted again in the afterAll, on the site's own theme, the way the specs that
// share the createMuseumForm helper use it; the site is on Lyris for the tests themselves, since a
// screen's looks are the site theme's. The afterAll puts the site's theme back first.

test.describe.configure({ mode: 'serial' });

const LOOK = 'e2e-screen';
const FORM = 'E2E Lyris screen appearance';
let originalTheme = null;
let key = '';
let fid = 0;
const screens = {};

async function adminPage(browser) {
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	return page;
}

test.beforeAll(async ({ browser }) => {
	originalTheme = getSiteTheme();
	const page = await adminPage(browser);
	fid = await createMuseumForm(page, FORM, 'E2E name');
	for (const [sid, type] of dbQuery(`SELECT sid, type FROM ${dbPrefix()}_formulize_screen WHERE fid = ${fid} ORDER BY sid`)) {
		screens[type] = screens[type] || parseInt(sid, 10);
	}
	setSiteThemeInDb('Lyris');
	key = await createLook(page, LOOK, { base: '', size: 'compact' });
	await page.close();
});

test.afterAll(async ({ browser }) => {
	restoreSiteTheme(originalTheme);
	const page = await adminPage(browser);
	try {
		if (fid) {
			await deleteMuseumForm(page, fid);
		}
	} finally {
		await deleteLooksNamed(page, 'e2e-');
		await page.close();
	}
});

test.beforeEach(async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
});

async function openAppearanceTab(page, sid) {
	await page.goto(`/modules/formulize/admin/ui.php?page=screen&fid=${fid}&sid=${sid}&tab=appearance`);
	await waitForAdminPageReady(page);
	await expect(page.locator('#screens-look')).toBeVisible();
}

// Write a screen's introductory text as code, and save the tab
async function saveIntroductoryText(page, sid, html) {
	await openAppearanceTab(page, sid);
	const toggle = page.locator('#formulize-screen-intro-toggle');
	if (await toggle.textContent() === 'Edit as code') {
		await toggle.click();
	}
	await expect(toggle).toHaveText('Edit as rich text');
	await setCodeMirrorValue(page, 'textarea[name="screens-toptext"]', html);
	await saveAdminForm(page);
}

// A custom property as the page's root element computes it
async function rootValue(page, property) {
	return page.locator('html').evaluate((html, property) => getComputedStyle(html).getPropertyValue(property).trim(), property);
}

test.describe("A screen's Appearance tab", () => {

	test('Look and Page width show the screen with a look of its own, at the full width of the window', async ({ page }) => {
		expect(screens.listOfEntries).toBeTruthy();
		await openAppearanceTab(page, screens.listOfEntries);
		const look = page.locator('#screens-look');
		// the site look is the one marked, and choosing it follows the site
		await expect(look.locator('option[value=""]')).toHaveText(/\(active Look for site\)$/);
		await expect(look).toHaveValue('');
		await look.selectOption(key);
		await expect(page.locator('#formulize-screen-look-edit')).toHaveAttribute('href', new RegExp(`appearance_editor\\.php\\?theme=Lyris&look=${key}$`));
		await page.locator('#screens-pagewidth-full').check();
		await saveAdminForm(page);

		await openAppearanceTab(page, screens.listOfEntries);
		await expect(page.locator('#screens-look')).toHaveValue(key);
		await expect(page.locator('#screens-pagewidth-full')).toBeChecked();
		expect(dbQuery(`SELECT look, pagewidth FROM ${dbPrefix()}_formulize_screen WHERE sid = ${screens.listOfEntries}`)[0]).toEqual([key, 'full']);

		// the screen's page: the look's stylesheet, with its sizes, and the full width
		const compact = (await createdLookValues(page))['--fz-control-height'];
		await page.goto(`/modules/formulize/index.php?sid=${screens.listOfEntries}`);
		await expect(page.locator(`link[rel="stylesheet"][href*="/themes/Lyris/appearance/looks/${key}.css"]`)).toBeAttached();
		expect(await rootValue(page, '--fz-control-height')).toMatch(new RegExp(`\\*\\s*${compact}\\)$`));
		expect(await rootValue(page, '--formulize-content-max-width')).toBe('100%');

		// a screen left to follow the site doesn't have them
		await page.goto(`/modules/formulize/index.php?sid=${screens.multiPage}`);
		await expect(page.locator('link[rel="stylesheet"][href*="/themes/Lyris/appearance/"]').first()).toBeAttached();
		await expect(page.locator(`link[rel="stylesheet"][href*="/looks/${key}.css"]`)).toHaveCount(0);
		expect(await rootValue(page, '--fz-control-height')).not.toMatch(new RegExp(`\\*\\s*${compact}\\)$`));
		expect(await rootValue(page, '--formulize-content-max-width')).not.toBe('100%');
	});

	test('Introductory text is shown above a list and above a form', async ({ page }) => {
		await saveIntroductoryText(page, screens.listOfEntries, '<p id="e2e-intro-list">Read this before the entries.</p>');
		await saveIntroductoryText(page, screens.multiPage, '<p id="e2e-intro-form">Read this before filling in the form.</p>');

		await page.goto(`/modules/formulize/index.php?sid=${screens.listOfEntries}`);
		const listIntro = page.locator('.formulize-screen-intro #e2e-intro-list');
		await expect(listIntro).toHaveText('Read this before the entries.');
		// above the entries
		const headings = page.locator('.lyris-list-table thead, table thead').first();
		await expect(headings).toBeVisible();
		expect((await listIntro.boundingBox()).y).toBeLessThan((await headings.boundingBox()).y);

		await page.goto(`/modules/formulize/index.php?sid=${screens.multiPage}`);
		await expect(page.locator('.formulize-screen-intro #e2e-intro-form')).toHaveText('Read this before filling in the form.');
		const field = page.locator('input[type="text"]').first();
		await expect(field).toBeVisible();
		expect((await page.locator('#e2e-intro-form').boundingBox()).y).toBeLessThan((await field.boundingBox()).y);

		// kept as written, and opens as code again
		await openAppearanceTab(page, screens.listOfEntries);
		await expect(page.locator('#formulize-screen-intro-toggle')).toHaveText('Edit as rich text');
		expect(dbQuery(`SELECT toptextcode FROM ${dbPrefix()}_formulize_screen WHERE sid = ${screens.listOfEntries}`)[0][0]).toBe('1');
	});
});

// The values the look, Compact, gives the sizes, read off its stylesheet
async function createdLookValues(page) {
	const response = await page.request.get(`/themes/Lyris/appearance/looks/${key}.css?e2e=${Date.now()}`);
	const css = await response.text();
	const overrides = css.match(/appearance_overrides:\s*(\{.*\})/)[1];
	return JSON.parse(overrides);
}
