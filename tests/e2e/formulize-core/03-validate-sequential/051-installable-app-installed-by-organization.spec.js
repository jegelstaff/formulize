const { test, expect, devices } = require('@playwright/test');
import { login } from '../../utils';
import { E2E_TEST_BASE_URL, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';

// The site installed as an app by its organization, through browser policy or device management, rather
// than by the people using it. The site then doesn't offer to be installed, but keeps the manifest and the
// service worker, which the organization's installs use. See formulize_pwaOffersInstalling().
//
// The rest of the app's tests are in 02-validate/044-installable-app.spec.js. This one is here because it
// changes the setting for the whole site, and 044 expects the install item in the menu.

const MANAGED = "Yes, your organization installs it on people's devices";

const appSetting = page => page.locator('[data-conf-name="formulizePWAEnabled"] select');

async function openAdvancedSettings(page) {
	await page.goto('/modules/formulize/admin/ui.php?page=settings&view=advanced');
	await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
}

// Saved by the Settings page, as a webmaster does. Returns the label of the choice it replaced.
async function chooseAppSetting(page, label) {
	await openAdvancedSettings(page);
	const before = await appSetting(page).evaluate(select => select.options[select.selectedIndex].text);
	await appSetting(page).selectOption({ label });
	await page.getByRole('button', { name: 'Save your changes' }).click();
	await expect(async () => {
		await openAdvancedSettings(page);
		expect(await appSetting(page).evaluate(select => select.options[select.selectedIndex].text)).toBe(label);
	}).toPass({ timeout: 60000 });
	return before;
}

test.describe('The site installed as an app by its organization', () => {

	test('the site keeps what the installs use, and does not offer to be installed', async ({ page, browser, request }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		const original = await chooseAppSetting(page, MANAGED);
		try {
			// the name under the icon still applies to the organization's installs
			await expect(page.locator('[data-conf-name="formulizePWAShortName"]')).toBeVisible();

			// what the organization's installs use: the manifest, and the worker with its offline page
			expect((await request.get('/modules/formulize/pwa/manifest.php')).status()).toBe(200);
			const source = await (await request.get('/')).text();
			expect(source).toContain('rel="manifest"');
			expect(source).toContain('navigator.serviceWorker.register');

			// signed in on an iPhone, where the menu would otherwise always show the steps for installing
			const { defaultBrowserType, ...iPhone } = devices['iPhone 13'];
			const context = await browser.newContext({ ...iPhone, baseURL: E2E_TEST_BASE_URL });
			const phone = await context.newPage();
			try {
				await login(phone, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
				await phone.goto('/modules/formulize/');
				await expect(phone.locator('#mainmenu > li').first()).toBeAttached();
				expect(await phone.locator('[data-formulize-install-app]').count()).toBe(0);
				// Chrome's own install banner on Android is held back
				expect(await phone.evaluate(() => {
					const event = new Event('beforeinstallprompt', { cancelable: true });
					window.dispatchEvent(event);
					return event.defaultPrevented;
				})).toBe(true);
			} finally {
				await context.close();
			}
		} finally {
			await chooseAppSetting(page, original);
		}
	});
});
