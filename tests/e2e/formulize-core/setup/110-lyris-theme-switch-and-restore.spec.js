const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, getSiteTheme, restoreSiteTheme } from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Lyris appearance (2 of 8) - switching the site to Lyris, and back
// ============================================================
// Everything else in the suite runs on the site's own theme (Anari, on a fresh install), so the Lyris
// specs switch to Lyris and back, and this one checks that the switch, and the switch back, work through
// the page an administrator would use: Admin > Appearance > Settings. The theme pages are shown in, and
// the theme webmasters are shown instead (include/common.php shows a webmaster the admin theme), are both
// set there. The other Lyris specs make the same switch directly in the database, which is quicker and has
// fewer ways to fail, and each puts the theme back in an afterAll.
//
// Whatever happens here, the afterAll puts the site's theme back as it found it, and checks that it did,
// so the validate specs never start on the wrong theme.

const SETTINGS_URL = '/modules/formulize/admin/ui.php?page=appearance&view=settings';
let original = null;

test.beforeAll(() => {
	original = getSiteTheme();
});

test.afterAll(() => {
	restoreSiteTheme(original);
	expect(getSiteTheme()).toEqual(original);
});

// Choose the site's theme, and the admin theme, on the Settings page, and save
async function chooseThemes(page, site, admin) {
	await page.goto(SETTINGS_URL);
	await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
	await page.locator('#theme_set').selectOption(site);
	await page.locator('#theme_admin_set').selectOption(admin);
	await Promise.all([
		page.waitForURL(/page=appearance/),
		page.getByRole('button', { name: 'Save your changes' }).click(),
	]);
	await page.goto(SETTINGS_URL);
	await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
	await expect(page.locator('#theme_set')).toHaveValue(site);
	await expect(page.locator('#theme_admin_set')).toHaveValue(admin);
}

// Which theme a page is shown in: Lyris's pages are a .lyris-app, Anari's a .site-layout, and each links
// its own stylesheets
async function expectTheme(page, theme) {
	await page.goto('/modules/formulize/application.php');
	const marker = { Lyris: '.lyris-app', Anari: '.site-layout' }[theme];
	if (marker) {
		await expect(page.locator(marker).first()).toBeAttached();
	}
	await expect(page.locator(`link[rel="stylesheet"][href*="/themes/${theme}/"]`).first()).toBeAttached();
	for (const other of ['Lyris', 'Anari'].filter(t => t !== theme)) {
		await expect(page.locator(`link[rel="stylesheet"][href*="/themes/${other}/"]`)).toHaveCount(0);
	}
}

async function signIn(browser, username, password) {
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await login(page, username, password);
	return page;
}

test.describe('Switching the site to Lyris and back', () => {

	test('Admin > Appearance > Settings switches the whole site to Lyris, and to Anari', async ({ browser }) => {
		const admin = await signIn(browser, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);

		await chooseThemes(admin, 'Anari', 'Anari');
		await expectTheme(admin, 'Anari');

		await chooseThemes(admin, 'Lyris', 'Lyris');
		await expectTheme(admin, 'Lyris');
		// and Lyris has its looks: its appearance stylesheet, written for its site look
		await expect(admin.locator('link[rel="stylesheet"][href*="/themes/Lyris/appearance/"]').first()).toBeAttached();
		// for everyone, not only webmasters
		const curator = await signIn(browser, 'curator1');
		await expectTheme(curator, 'Lyris');

		await chooseThemes(admin, 'Anari', 'Anari');
		await expectTheme(admin, 'Anari');
		await expectTheme(curator, 'Anari');
	});

	test('Admin > Appearance > Settings puts the site back on the theme it was on', async ({ browser }) => {
		const admin = await signIn(browser, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await chooseThemes(admin, original.site, original.admin);
		await expectTheme(admin, original.admin);
		const curator = await signIn(browser, 'curator1');
		await expectTheme(curator, original.site);
		const now = getSiteTheme();
		expect(now.site).toBe(original.site);
		expect(now.admin).toBe(original.admin);
	});
});
