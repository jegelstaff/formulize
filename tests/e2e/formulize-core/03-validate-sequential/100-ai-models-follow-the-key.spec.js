const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';
import { login, saveAdminSettings } from '../../utils';

/**
 * The list of AI models is refreshed as soon as a key is put in the box, however it gets there.
 *
 * fill() stands in for pasting with the mouse: the text arrives all at once, the box says it has new input, and no
 * keys are pressed. That's what the list used to miss.
 *
 * The keys are made up, and a test shouldn't send them to a real provider, so the requests for the models are answered
 * here, with a model named after the key. Nothing is saved except that the AI assistant is turned on, and that's put
 * back afterwards.
 */

const SETTINGS_URL = '/modules/formulize/admin/ui.php?page=settings&view=ai';

let assistantWasOn;

async function openAISettings(page) {
	await page.goto(SETTINGS_URL);
	await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
}

const assistant = (page, on) => page.locator(`input[name="formulizeAIAssistantEnabled"][value="${on ? 1 : 0}"]`);

// Answer with a model named after the key. A key that has "slow" in it is answered late.
async function answerForTheProvider(page) {
	await page.route('**/ai/ai_proxy.php**op=models**', async route => {
		const key = route.request().headers()['x-api-key'] || 'no-key';
		if (key.includes('slow')) {
			await new Promise(resolve => setTimeout(resolve, 4000));
		}
		await route.fulfill({ json: { models: [{ id: `model-for-${key}`, name: `Model for ${key}` }] } }).catch(() => {});
	});
}

test.beforeAll(async ({ browser }) => {
	const page = await browser.newPage();
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openAISettings(page);
	assistantWasOn = await assistant(page, true).isChecked();
	if (!assistantWasOn) {
		await assistant(page, true).check();
		await saveAdminSettings(page);
		await openAISettings(page);
		await expect(assistant(page, true)).toBeChecked();
	}
	await page.close();
});

test.afterAll(async ({ browser }) => {
	if (assistantWasOn === false) {
		const page = await browser.newPage();
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await openAISettings(page);
		await assistant(page, false).check();
		await saveAdminSettings(page);
		await openAISettings(page);
		await expect(assistant(page, false)).toBeChecked();
		await page.close();
	}
});

// The two places a key is entered: the settings for the whole site, and a person's own, in the assistant
const places = [
	{
		name: 'the settings page',
		open: async page => {
			await openAISettings(page);
			await page.locator('#formulizeAIProvider').selectOption('openai');
		},
		key: '#formulizeAIApiKey_new',
		models: '#formulizeAIModel',
	},
	{
		name: 'the assistant',
		open: async page => {
			await page.goto('/ai/');
			const key = page.locator('#ai-api-key');
			const toggle = page.locator('#settings-toggle');
			await expect(toggle).toBeVisible();
			// the settings are already open on a person's first visit
			if (await toggle.getAttribute('aria-expanded') !== 'true') {
				await toggle.click();
			}
			await expect(key).toBeVisible();
			await page.locator('#provider-select').selectOption('openai');
		},
		key: '#ai-api-key',
		models: '#model-name',
	},
];

for (const place of places) {
	test.describe(`The list of models in ${place.name}`, () => {

		test.beforeEach(async ({ page }) => {
			await answerForTheProvider(page);
			await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
			await place.open(page);
		});

		test('is refreshed when a key is pasted, and again when it is replaced', async ({ page }) => {
			const model = key => page.locator(`${place.models} option[value="model-for-${key}"]`);
			await page.locator(place.key).fill('pasted-key');
			await expect(model('pasted-key')).toBeAttached();
			await page.locator(place.key).fill('replacement-key');
			await expect(model('replacement-key')).toBeAttached();
		});

		test('is refreshed when a key is typed', async ({ page }) => {
			await page.locator(place.key).pressSequentially('typed-key');
			await expect(page.locator(`${place.models} option[value="model-for-typed-key"]`)).toBeAttached();
		});

		test('is not overwritten by a late answer about an earlier key', async ({ page }) => {
			const slowRequest = page.waitForRequest(request => request.headers()['x-api-key'] === 'slow-key');
			await page.locator(place.key).fill('slow-key');
			await slowRequest;
			const slowAnswer = page.waitForResponse(response => response.request().headers()['x-api-key'] === 'slow-key');
			await page.locator(place.key).fill('quick-key');
			await expect(page.locator(`${place.models} option[value="model-for-quick-key"]`)).toBeAttached();
			await slowAnswer;
			await expect(page.locator(place.models)).toBeEnabled();
			await expect(page.locator(`${place.models} option[value="model-for-quick-key"]`)).toBeAttached();
			await expect(page.locator(`${place.models} option[value="model-for-slow-key"]`)).not.toBeAttached();
		});
	});
}
