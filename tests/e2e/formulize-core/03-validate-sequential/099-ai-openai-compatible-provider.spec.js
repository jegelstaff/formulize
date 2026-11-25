const { test, expect } = require('@playwright/test');
const { execFileSync } = require('child_process');
const { webContainer } = require('../../docker-containers');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';
import { login, saveAdminSettings } from '../../utils';

/**
 * The AI assistant works with an OpenAI-compatible provider.
 *
 * OpenRouter and Ollama are both reached by the same code. A test can't use either of them, so this points the Ollama
 * setting at a stand-in, fake-ai-provider.php, which runs in the web container, and talks to it with the assistant.
 *
 * This turns the AI assistant on and sets its provider for everyone, and puts both back afterwards.
 */

const SETTINGS_URL = '/modules/formulize/admin/ui.php?page=settings&view=ai';
const FAKE_PORT = 11499;
const ADDRESS = `http://127.0.0.1:${FAKE_PORT}`;
const ORDINARY_ADDRESS = 'http://localhost:11434';
const FAKE_PROVIDER = '/var/www/html/tests/e2e/formulize-core/03-validate-sequential/fake-ai-provider.php';

let assistantWasOn;

const inContainer = (...args) => execFileSync('docker', ['exec', webContainer(), ...args], { encoding: 'utf8' });

async function openAISettings(page) {
	await page.goto(SETTINGS_URL);
	await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
}

const assistant = (page, on) => page.locator(`input[name="formulizeAIAssistantEnabled"][value="${on ? 1 : 0}"]`);
const provider = page => page.locator('#formulizeAIProvider');
const address = page => page.locator('#formulizeAIOllamaBaseUrl');
const model = page => page.locator('#formulizeAIModel');

async function ask(page, question) {
	const input = page.locator('#user-input');
	await expect(input).toBeEnabled();
	await input.fill(question);
	await page.locator('#send-btn').click();
}

const chat = page => page.locator('#chat-window');

test.describe.serial('An OpenAI-compatible provider', () => {

	test.beforeAll(() => {
		execFileSync('docker', ['exec', '-d', webContainer(), 'php', '-d', 'xdebug.mode=off', '-S', `127.0.0.1:${FAKE_PORT}`, FAKE_PROVIDER]);
		expect(inContainer('curl', '-s', '--retry', '10', '--retry-connrefused', '--retry-delay', '1', `${ADDRESS}/v1/models`)).toContain('fake-model-instruct');
	});

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	});

	test('the settings list its models', async ({ page }) => {
		await openAISettings(page);
		assistantWasOn = await assistant(page, true).isChecked();
		await assistant(page, true).check();
		await expect(provider(page)).toHaveValue('userspecified');
		await provider(page).selectOption('ollama');
		// the address the way an OpenAI-compatible service's is usually written
		await address(page).fill(`${ADDRESS}/v1`);
		await saveAdminSettings(page);

		await openAISettings(page);
		await expect(model(page).locator('option[value="fake-model-instruct"]')).toBeAttached();
		await expect(model(page).locator('option[value="another-fake-model"]')).toBeAttached();
		await expect(model(page).locator('option[value="fake-model-without-tools"]')).not.toBeAttached();
		await model(page).selectOption('fake-model-instruct');
		await saveAdminSettings(page);
		await openAISettings(page);
		await expect(model(page)).toHaveValue('fake-model-instruct');
	});

	test('the assistant can talk to it', async ({ page }) => {
		await page.goto('/ai/');
		await ask(page, 'Who are you?');
		await expect(chat(page)).toContainText(/I am fake-model-instruct, with [1-9]\d* tools and no key\./);

		await ask(page, 'Use a tool');
		await expect(chat(page)).toContainText('The tool said:');

		await ask(page, 'Fail');
		await expect(chat(page)).toContainText('Ollama (Local) sent back an error: The model is not available right now');

		await ask(page, 'Say nothing');
		await expect(chat(page)).toContainText('Ollama (Local) sent back an answer with nothing in it.');
	});

	test('the conversation is still there after a reload', async ({ page }) => {
		await page.goto('/ai/');
		await ask(page, 'Who are you?');
		await expect(chat(page)).toContainText('I am fake-model-instruct');
		await page.reload();
		await expect(chat(page)).toContainText('I am fake-model-instruct');
	});

	test('an unreachable address says so', async ({ page }) => {
		await openAISettings(page);
		await address(page).fill('http://127.0.0.1:11498');
		await saveAdminSettings(page);
		await page.goto('/ai/');
		await ask(page, 'Who are you?');
		await expect(chat(page)).toContainText('Could not reach the AI provider');
	});
});

test.afterAll(async ({ browser }) => {
	const page = await browser.newPage();
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openAISettings(page);
	if (await provider(page).inputValue() === 'ollama') {
		await address(page).fill(ORDINARY_ADDRESS);
		await saveAdminSettings(page);
		await openAISettings(page);
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
	// nothing in the container stops a process by name, so find it by what it was started with
	inContainer('sh', '-c', `for p in /proc/[0-9]*; do if tr '\\0' ' ' < $p/cmdline 2>/dev/null | grep -q '^php .*-S 127.0.0.1:${FAKE_PORT}'; then kill "\${p#/proc/}"; fi; done; true`);
});
