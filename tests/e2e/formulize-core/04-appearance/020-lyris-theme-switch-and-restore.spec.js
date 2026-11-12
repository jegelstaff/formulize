const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, getSiteTheme, aDayPassesForTheWorker, chooseSiteThemes, expectSiteTheme, dbQuery, dbPrefix, waitForAdminPageReady } from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Lyris appearance (2 of 8) - switching the site to Lyris, and back
// ============================================================
// Everything else in the suite runs on Anari, which 01-setup/001 switches a fresh install to, so the Lyris
// specs switch to Lyris and back, and this one checks that the switch, and the switch back, work through
// the page an administrator would use: Admin > Appearance > Settings. The theme pages are shown in, and
// the theme webmasters are shown instead (include/common.php shows a webmaster the admin theme), are both
// set there. The other Lyris specs make the same switch directly in the database, which is quicker and has
// fewer ways to fail, and each puts the theme back in an afterAll.
//
// Changing the site's theme there also moves every screen to it, so a screen's Templates tab opens on the templates
// the site uses. So whatever happens here, the afterAll puts the site's theme back as it found it through the same
// page, which moves the screens back too, and checks that it did, so nothing that runs after it starts on the wrong
// theme.

let original = null;

test.beforeAll(() => {
	original = getSiteTheme();
});

test.afterAll(async ({ browser }) => {
	const admin = await signIn(browser, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	try {
		await chooseSiteThemes(admin, original.site, original.admin);
	} finally {
		await admin.close();
	}
	const now = getSiteTheme();
	expect(now.site).toBe(original.site);
	expect(now.admin).toBe(original.admin);
});

// The theme a screen's Templates tab shows, which is the theme its templates are edited for
async function templatesTabTheme(page) {
	const [sid, fid] = dbQuery(`SELECT sid, fid FROM ${dbPrefix()}_formulize_screen WHERE title = 'Artifacts' AND type = 'listOfEntries'`)[0];
	await page.goto(`/modules/formulize/admin/ui.php?page=screen&fid=${fid}&sid=${sid}&tab=templates`);
	await waitForAdminPageReady(page);
	return page.locator('select[name="screens-theme"]').inputValue();
}

// Which theme the offline page is in, which says which worker is in charge: each worker keeps the
// offline page as it was when the worker was made
async function expectOfflineTheme(page, theme) {
	await page.context().setOffline(true);
	try {
		await page.goto('/modules/formulize/application.php');
		await expect(page.locator('.fz-empty__title')).toHaveText("You're offline");
		await expect(page.locator(`link[rel="stylesheet"][href*="/themes/${theme}/"]`).first()).toBeAttached();
	} finally {
		await page.context().setOffline(false);
	}
}

// The worker waiting to take over, if there is one
async function workerIsWaiting(page) {
	return page.evaluate(async () => !!(await navigator.serviceWorker.getRegistration()).waiting);
}

async function signIn(browser, username, password) {
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await login(page, username, password);
	return page;
}

test.describe('Switching the site to Lyris and back', () => {

	test('Admin > Appearance > Settings switches the whole site to Lyris, and to Anari', async ({ browser }) => {
		const admin = await signIn(browser, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);

		await chooseSiteThemes(admin, 'Anari', 'Anari');
		await expectSiteTheme(admin, 'Anari');

		await chooseSiteThemes(admin, 'Lyris', 'Lyris');
		// every screen follows the site, so its Templates tab shows the templates the site uses
		expect(await templatesTabTheme(admin)).toBe('Lyris');
		await expectSiteTheme(admin, 'Lyris');
		// and Lyris has its looks: its appearance stylesheet, written for its site look
		await expect(admin.locator('link[rel="stylesheet"][href*="/themes/Lyris/appearance/"]').first()).toBeAttached();
		// for everyone, not only webmasters
		const curator = await signIn(browser, 'curator1');
		await expectSiteTheme(curator, 'Lyris');

		await chooseSiteThemes(admin, 'Anari', 'Anari');
		expect(await templatesTabTheme(admin)).toBe('Anari');
		await expectSiteTheme(admin, 'Anari');
		await expectSiteTheme(curator, 'Anari');
	});

	// A new site starts on Lyris, and the rest of the suite runs on Anari, so this is where signing in on Lyris is
	// checked: from the Sign in link in its header, which opens the sidebar on the login block on the front page
	test('A visitor signs in on Lyris from the Sign in link in the header', async ({ browser }) => {
		const admin = await signIn(browser, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await chooseSiteThemes(admin, 'Lyris', 'Lyris');
		const visitor = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
		try {
			await visitor.goto('/');
			await visitor.locator('header').getByRole('link', { name: 'Sign in' }).click();
			const loginBlock = visitor.locator('#lyris-sidebar');
			await loginBlock.locator('input[name="uname"]').fill('curator1');
			await loginBlock.locator('input[name="pass"]').fill('12345');
			await loginBlock.locator('input[name="pass"]').press('Enter');
			await expect(visitor.locator('header').getByRole('link', { name: 'Account' })).toBeVisible();
			await expect(visitor.locator('header').getByRole('link', { name: 'Sign out' })).toBeVisible();
		} finally {
			await visitor.close();
			await chooseSiteThemes(admin, 'Anari', 'Anari');
		}
	});

	// A change of theme makes a new service worker, which a browser finds within a day, and which waits until
	// the site has been closed. Pages come from the server all the while, so someone with the site open sees
	// the new theme as soon as they load a page. Only the offline page is out of date until then.
	test('A person with the site open gets the new theme at once, and the new offline page once they close the site', async ({ browser }) => {
		test.setTimeout(300000);
		const context = await browser.newContext({ baseURL: E2E_TEST_BASE_URL });
		const admin = await context.newPage();
		await login(admin, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await chooseSiteThemes(admin, 'Anari', 'Anari');
		await expectSiteTheme(admin, 'Anari');
		await admin.evaluate(() => navigator.serviceWorker.ready);

		await chooseSiteThemes(admin, 'Lyris', 'Lyris');
		await aDayPassesForTheWorker(admin);
		await expectSiteTheme(admin, 'Lyris');
		await expect.poll(() => workerIsWaiting(admin), { timeout: 60000 }).toBe(true);
		await expectSiteTheme(admin, 'Lyris');
		expect(await workerIsWaiting(admin)).toBe(true);
		await expectOfflineTheme(admin, 'Anari');

		await admin.close();
		const back = await context.newPage();
		await expectSiteTheme(back, 'Lyris');
		await expect.poll(() => workerIsWaiting(back), { timeout: 60000 }).toBe(false);
		await expectOfflineTheme(back, 'Lyris');
	});

	test('Admin > Appearance > Settings puts the site back on the theme it was on', async ({ browser }) => {
		const admin = await signIn(browser, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await chooseSiteThemes(admin, original.site, original.admin);
		await expectSiteTheme(admin, original.admin);
		const curator = await signIn(browser, 'curator1');
		await expectSiteTheme(curator, original.site);
		const now = getSiteTheme();
		expect(now.site).toBe(original.site);
		expect(now.admin).toBe(original.admin);
	});
});
