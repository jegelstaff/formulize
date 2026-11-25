const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';
import { login, saveAdminSettings } from '../../utils';

/**
 * The site-wide AI key is still there after the module is updated.
 *
 * The update deletes every one of the module's settings and inserts them again with the values they had. The key's
 * setting is encrypted as it's written, so it's handled differently from the rest, and it used to be lost.
 *
 * This turns the AI assistant on and saves a made-up key, and puts both back afterwards.
 */

const SETTINGS_URL = '/modules/formulize/admin/ui.php?page=settings&view=ai';
const PROVIDER = 'openai';

let assistantWasOn;

async function openAISettings(page) {
	await page.goto(SETTINGS_URL);
	await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
}

const assistant = (page, on) => page.locator(`input[name="formulizeAIAssistantEnabled"][value="${on ? 1 : 0}"]`);
const provider = page => page.locator('#formulizeAIProvider');
const keyStatus = page => page.locator('.formulize-config-aikey-status');
const removeKey = page => page.locator('#formulizeAIApiKey_clear');

// The settings page asks the provider which models a key can use, as soon as one is typed. The key is made up, and a
// test shouldn't send it to a real provider, so answer that request here.
async function keepKeyFromProvider(page) {
	await page.route('**/ai/ai_proxy.php**', route => route.fulfill({ json: { models: [] } }));
}

test.describe.serial('The site-wide AI key and the module update', () => {

	test.beforeEach(async ({ page }) => {
		await keepKeyFromProvider(page);
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	});

	test('a key can be saved for the whole site', async ({ page }) => {
		await openAISettings(page);
		assistantWasOn = await assistant(page, true).isChecked();
		await assistant(page, true).check();
		await expect(provider(page)).toHaveValue('userspecified');
		await provider(page).selectOption(PROVIDER);
		await expect(keyStatus(page)).toContainText('No key is saved');
		await page.locator('#formulizeAIApiKey_new').fill('not-a-real-key');
		await saveAdminSettings(page);

		await openAISettings(page);
		await expect(provider(page)).toHaveValue(PROVIDER);
		await expect(keyStatus(page)).toContainText('A key is saved');
	});

	test('it is still saved after the module is updated', async ({ page }) => {
		test.setTimeout(300000);
		await page.goto('/modules/system/admin.php?fct=modulesadmin');
		await page.locator('a[href*="fct=modulesadmin"][href*="op=update"][href*="module=formulize"]').first().click();
		await page.getByRole('button', { name: 'Update' }).click();
		await expect(page.getByText('Formulize updated successfully')).toBeVisible({ timeout: 180000 });

		await openAISettings(page);
		await expect(provider(page)).toHaveValue(PROVIDER);
		await expect(keyStatus(page)).toContainText('A key is saved');
	});

	test('it is still saved after saving the settings again', async ({ page }) => {
		await openAISettings(page);
		await saveAdminSettings(page);
		await openAISettings(page);
		await expect(keyStatus(page)).toContainText('A key is saved');
	});
});

test.afterAll(async ({ browser }) => {
	const page = await browser.newPage();
	await keepKeyFromProvider(page);
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openAISettings(page);

	// the box only removes the key of the provider that's chosen, so do that before choosing another
	if (await provider(page).inputValue() === PROVIDER) {
		if (await removeKey(page).isVisible()) {
			await removeKey(page).check();
			await saveAdminSettings(page);
			await openAISettings(page);
			await expect(keyStatus(page)).toContainText('No key is saved');
		}
		await provider(page).selectOption('userspecified');
		await saveAdminSettings(page);
		await openAISettings(page);
		await expect(provider(page)).toHaveValue('userspecified');
	}
	if (assistantWasOn === false) {
		await assistant(page, false).check();
		await saveAdminSettings(page);
		await openAISettings(page);
		await expect(assistant(page, false)).toBeChecked();
	}
	await page.close();
});
