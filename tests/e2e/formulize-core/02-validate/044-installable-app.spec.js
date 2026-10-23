const { test, expect, devices } = require('@playwright/test');
import { getSystemConfig, setSystemConfig, login } from '../../utils';
import { E2E_TEST_BASE_URL, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';

// The site installed as an app (a progressive web app): the manifest, the head tags that make a page
// installable, and the service worker, which does nothing but show a page saying the device is offline
// when a page can't be loaded. See formulize_pwaFeaturesApply() and the files in modules/formulize/pwa.
//
// The rule these tests hold the worker to is that it never keeps a page. One address is drawn two ways
// (in a frame and on its own), and pages show people's entries, so all it may keep is the offline page
// and the stylesheets it is drawn with.
//
// localhost counts as a secure context, so all of this works over plain http here.
//
// The last test turns the app off, and back on again, so the tests must run in order in the same worker.
// CI runs the validate suite with --workers=4 --fully-parallel, which overrides the serial defaults in
// playwright.config.js.
test.describe.configure({ mode: 'serial' });

const OFFLINE_PAGE = '/modules/formulize/pwa/offline';

async function waitForWorker(page) {
	return page.evaluate(() => Promise.race([
		navigator.serviceWorker.ready.then(r => r.active && r.active.scriptURL),
		new Promise(resolve => setTimeout(() => resolve(null), 20000)),
	]));
}

async function cacheContents(page) {
	return page.evaluate(async () => {
		const contents = {};
		for (const name of await caches.keys()) {
			const cache = await caches.open(name);
			contents[name] = (await cache.keys()).map(request => {
				const url = new URL(request.url);
				return url.pathname + url.search;
			});
		}
		return contents;
	});
}

test.describe('Installing the site as an app', () => {

	test('the manifest names the app, uses paths only, and lists its icons', async ({ request }) => {
		const response = await request.get('/modules/formulize/pwa/manifest.php');
		expect(response.status()).toBe(200);
		expect(response.headers()['content-type']).toContain('application/manifest+json');
		const text = await response.text();
		// paths, never the host the server was reached by
		expect(text).not.toContain('http://');
		expect(text).not.toContain('https://');
		const manifest = JSON.parse(text);
		expect(manifest.start_url).toBe('/');
		expect(manifest.scope).toBe('/');
		expect(manifest.display).toBe('standalone');
		// Chrome and Edge give the window back and reload buttons; Safari ignores this and uses display
		expect(manifest.display_override).toEqual(['minimal-ui']);
		expect(manifest.name).toBeTruthy();
		expect(manifest.theme_color).toMatch(/^#[0-9a-f]{6}$/i);
		expect(manifest.background_color).toMatch(/^#[0-9a-f]{6}$/i);
		const icons = manifest.icons.map(icon => icon.sizes + ' ' + icon.purpose);
		expect(icons).toEqual(expect.arrayContaining(['192x192 any', '512x512 any', '192x192 maskable', '512x512 maskable']));
		for (const icon of manifest.icons) {
			expect((await request.get(icon.src)).status(), icon.src).toBe(200);
		}
	});

	test('a page carries the manifest, title bar colour and icon, but not in a frame', async ({ request }) => {
		const page = await (await request.get('/')).text();
		expect(page).toContain('rel="manifest"');
		expect(page).toMatch(/<meta name="theme-color" content="#[0-9a-f]{6}"/i);
		expect(page).toContain('rel="apple-touch-icon"');
		expect(page).toContain('navigator.serviceWorker.register');
		const framed = await (await request.get('/', { headers: { 'Sec-Fetch-Dest': 'iframe' } })).text();
		expect(framed).not.toContain('rel="manifest"');
		expect(framed).not.toContain('navigator.serviceWorker.register');
	});

	test('the service worker registers, and keeps nothing but the offline page and its stylesheets', async ({ page }) => {
		await page.goto('/');
		expect(await waitForWorker(page)).toContain('/modules/formulize/pwa/sw.php');
		await page.reload();
		expect(await page.evaluate(() => !!navigator.serviceWorker.controller)).toBe(true);
		for (const url of ['/modules/formulize/', '/user.php', '/']) {
			await page.goto(url);
		}
		const contents = await cacheContents(page);
		const names = Object.keys(contents);
		expect(names).toHaveLength(1);
		for (const url of contents[names[0]]) {
			expect(url === OFFLINE_PAGE || /\.css\?v=\d+$/.test(url), url).toBe(true);
		}
		expect(contents[names[0]]).toContain(OFFLINE_PAGE);
	});

	test('offline, a page load shows the offline page, drawn with its stylesheets', async ({ page, context }) => {
		await page.goto('/');
		await waitForWorker(page);
		await page.reload();
		await context.setOffline(true);
		try {
			await page.goto('/modules/formulize/');
			await expect(page.locator('.fz-empty__title')).toHaveText("You're offline");
			// drawn from the kept stylesheets, not unstyled
			const button = page.locator('.fz-empty .fz-btn--primary');
			await expect(button).toBeVisible();
			expect(await button.evaluate(el => getComputedStyle(el).backgroundColor)).not.toBe('rgba(0, 0, 0, 0)');
		} finally {
			await context.setOffline(false);
		}
		// Try again reloads the page, which is back
		await page.locator('.fz-empty .fz-btn--primary').click();
		await expect(page.locator('.fz-empty__title')).toHaveCount(0);
	});

	test('there is no install item for anyone who is not signed in', async ({ page }) => {
		for (const url of ['/', '/user.php']) {
			await page.goto(url);
			expect(await page.locator('[data-formulize-install-app]').count(), url).toBe(0);
		}
	});

	test('signed in on an iPhone, the last item in the menu shows the steps, and is not in the installed app', async ({ browser }) => {
		// the iPhone's user agent and size, in Chromium: the item is chosen by user agent, and on an
		// iPhone it shows the steps rather than waiting for an event Chromium won't fire in a test
		const { defaultBrowserType, ...iPhone } = devices['iPhone 13'];
		const context = await browser.newContext({ ...iPhone, baseURL: E2E_TEST_BASE_URL });
		const page = await context.newPage();
		try {
			await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
			await page.goto('/modules/formulize/');
			const item = page.locator('#mainmenu > li').last();
			expect(await item.getAttribute('data-formulize-install-app')).not.toBeNull();
			expect(await item.evaluate(el => el.hidden)).toBe(false);
			await expect(item).toHaveText('Add this site to your home screen');
			// clicked in the page, because on a phone the menu is closed
			await item.evaluate(el => el.querySelector('a').click());
			const dialog = page.locator('#formulize-install-app-dialog');
			await expect(dialog.locator('.fz-dialog__title')).toHaveText('Add this site to your home screen');
			expect(await dialog.evaluate(el => el.matches(':modal'))).toBe(true);
			await dialog.locator('.fz-dialog__footer button').click();
			expect(await dialog.evaluate(el => el.open)).toBe(false);

			// never offered in the app itself
			await context.addInitScript(() => {
				Object.defineProperty(navigator, 'standalone', { get: () => true });
			});
			await page.reload();
			expect(await page.locator('#mainmenu > li').last().evaluate(el => el.hidden)).toBe(true);
		} finally {
			await context.close();
		}
	});

	test('in the installed app, the back button and links to new windows stay in the app', async ({ page, context }) => {
		// Playwright can't put a page in standalone mode, so stand in iOS's own signal for it
		await page.addInitScript(() => {
			Object.defineProperty(navigator, 'standalone', { get: () => true });
		});
		await page.goto('/user.php');
		await page.goto('/');
		expect(await page.evaluate(() => window.formulize.isInstalledApp())).toBe(true);
		// there is a page to go back to, so the back button is unhidden
		const back = page.locator('[data-formulize-app-back]').first();
		expect(await back.evaluate(el => el.hidden)).toBe(false);
		await Promise.all([page.waitForURL(/\/user\.php$/), back.evaluate(el => el.click())]);

		await page.evaluate(() => {
			const link = document.createElement('a');
			link.id = 'pwa-test-link';
			link.href = '/user.php?from=link';
			link.target = '_blank';
			link.textContent = 'link';
			document.body.prepend(link);
		});
		let newWindows = 0;
		context.on('page', () => newWindows++);
		await Promise.all([page.waitForURL(/from=link$/), page.locator('#pwa-test-link').click({ force: true })]);
		expect(newWindows).toBe(0);
	});

	test('in a window with no browser buttons, a bar shows while the next page loads', async ({ browser }) => {
		// no worker, so that the slow page can be routed; the bar doesn't involve the worker
		const context = await browser.newContext({ baseURL: E2E_TEST_BASE_URL, serviceWorkers: 'block' });
		await context.addInitScript(() => {
			Object.defineProperty(navigator, 'standalone', { get: () => true });
			// each page reports the bar appearing, so a slow server can't make the check read the wrong page
			new MutationObserver(() => {
				const bar = document.querySelector('[role="progressbar"]');
				if (bar && !bar.hidden && !window.__barReported) {
					window.__barReported = true;
					console.log('loading bar shown');
				}
			}).observe(document, { subtree: true, childList: true, attributes: true, attributeFilter: ['hidden'] });
		});
		await context.route(/slow=1/, async route => {
			await new Promise(resolve => setTimeout(resolve, 4000));
			await route.continue().catch(() => {});
		});
		const page = await context.newPage();
		let shown = false;
		page.on('console', message => { if (message.text() === 'loading bar shown') shown = true; });
		try {
			await page.goto('/user.php');
			// a link that goes to the same place on the page loads nothing
			await page.evaluate(() => {
				const link = document.createElement('a'); link.href = '#somewhere'; document.body.prepend(link); link.click();
			});
			await page.waitForTimeout(500);
			expect(shown).toBe(false);
			// clicked in the page, because Playwright's own click waits for the next page to arrive
			await page.evaluate(() => {
				const link = document.createElement('a'); link.href = '/user.php?slow=1'; document.body.prepend(link); link.click();
			});
			await expect.poll(() => shown, { timeout: 3000 }).toBe(true);
			await page.waitForURL(/slow=1/, { timeout: 60000 });
			await page.waitForLoadState('load');
			expect(await page.locator('[role="progressbar"]:not([hidden])').count()).toBe(0);
		} finally {
			await context.close();
		}
	});

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
