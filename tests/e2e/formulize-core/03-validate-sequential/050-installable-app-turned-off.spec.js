const { test, expect } = require('@playwright/test');
import { getSystemConfig, setSystemConfig } from '../../utils';

// Turning off the site as an installed app. The rest of the app's tests are in
// 02-validate/044-installable-app.spec.js. This one is here because it changes the setting for the whole
// site: every browser with the site open gets the worker that removes itself, which takes over at once, and
// that would land on whatever other specs were doing at the time.

async function waitForWorker(page) {
	return page.evaluate(() => Promise.race([
		navigator.serviceWorker.ready.then(r => r.active && r.active.scriptURL),
		new Promise(resolve => setTimeout(() => resolve(null), 20000)),
	]));
}

test.describe('Turning off the site as an installed app', () => {

	test('turned off, the installed worker removes itself and what it kept', async ({ page, request }) => {
		await page.goto('/');
		await waitForWorker(page);
		await page.reload();
		const original = getSystemConfig('formulizePWAEnabled');
		try {
			setSystemConfig('formulizePWAEnabled', 0);
			expect((await request.get('/modules/formulize/pwa/manifest.php')).status()).toBe(404);
			// the page no longer registers a worker; the installed one checks for itself, finds the one
			// that removes itself, and that one takes over
			await page.reload();
			expect(await page.locator('link[rel="manifest"]').count()).toBe(0);
			await expect.poll(async () => {
				await page.reload();
				return page.evaluate(async () => (await navigator.serviceWorker.getRegistrations()).length + (await caches.keys()).length);
			}, { timeout: 30000 }).toBe(0);
		} finally {
			setSystemConfig('formulizePWAEnabled', original === null ? 1 : original);
		}
	});
});
