const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login } from '../../utils';

// These tests change site-wide settings (the MCP server, the AI assistant) and read the first API key in the
// list, so they run in the sequential stage, where no other spec can change those underneath them. In
// 02-validate, 043 turned the MCP server back off when it finished, and added and deleted API keys of its own.
//
// The second test reads the API key created by the first one (td#key-1), so they must run in order.
test.describe.configure({ mode: 'serial' });

// Every tool, in the order tools/list gives them. The first is named after the server, which is
// 'formulize' on a fresh install.
const EXPECTED_TOOLS = [
	'formulize',
	'list_forms',
	'list_applications',
	'list_menu_items',
	'list_form_connections',
	'list_screens',
	'list_groups',
	'list_group_members',
	'list_users',
	'list_a_users_groups',
	'get_form_details',
	'get_element_details',
	'get_screen_details',
	'get_application_details',
	'create_entries',
	'update_entries',
	'get_entries_from_form',
	'prepare_database_values_for_human_readability',
	'test_connection',
	'locate_captain_picard',
	'open_the_pod_bay_doors_hal',
	'lets_play_global_thermonuclear_war',
	'query_the_database_directly',
	'create_form',
	'update_form',
	'create_list_element',
	'update_list_element',
	'create_linked_list_element',
	'update_linked_list_element',
	'create_user_list_element',
	'update_user_list_element',
	'create_static_content_element',
	'update_static_content_element',
	'create_selector_element',
	'update_selector_element',
	'create_derived_value_element',
	'update_derived_value_element',
	'create_text_box_element',
	'update_text_box_element',
	'create_table_of_elements',
	'update_table_of_elements',
	'create_subform_interface',
	'update_subform_interface',
	'delete_element',
	'create_form_screen',
	'update_form_screen',
	'change_form_screen_page_order',
	'create_list_screen',
	'update_list_screen',
	'create_menu_item',
	'update_menu_item',
	'change_menu_item_order',
	'change_menu_order',
	'update_application_forms',
	'create_users',
	'update_users',
	'create_groups',
	'update_groups',
	'update_group_members',
	'get_form_permissions_by_group',
	'set_form_permissions',
	'set_form_permission_inheritance',
	'get_custom_code',
	'update_form_code',
	'update_application_code',
	'get_documentation',
	'read_system_activity_log',
];

// test.html asks for the lists of tools, resources and prompts, one after another, when it loads, when
// the API key box loses focus, and when the auth mode changes. Each answer adds one to the count on the
// page. It ignores a second round asked for while one is running, and Clear discards only the answer
// on its way in, so wait for each round to finish before doing the next thing. networkidle can't say
// when that is: it fires once per page load, and can fire before the first round has started.
async function expectResponses(page, text) {
	await expect(page.locator('#fixed-stats')).toHaveText(new RegExp('\\b' + text), { timeout: 120000 });
}

// Send the request in the page's request box, which is tools/list until something changes it, with
// nothing else running, and return the names of the tools in the response
async function sendAndReadToolNames(page) {
	await page.getByRole('button', { name: 'Clear', exact: true }).click();
	await expectResponses(page, '0 total');
	const [response] = await Promise.all([
		page.waitForResponse(r => r.url().includes('/index.php/mcp') && r.request().postData().includes('tools/list')),
		page.getByRole('button', { name: '🚀 Send Request' }).click(),
	]);
	await expectResponses(page, '1 total, 1 success');
	const names = (await response.json()).result.tools.map(tool => tool.name);
	// and the page shows it
	await expect(page.getByText(`0: "name": "${names[0]}" "`).first()).toBeVisible();
	return names;
}

test.describe('Check that tools/list is responding', () => {
	test('Create API Key', async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		// API Keys are now under Users → API Keys in the Formulize admin UI.
		await page.goto('/modules/formulize/admin/ui.php?page=users&view=apikeys');
		await page.locator('.admin-ui').waitFor({ state: 'visible' });
		await page.getByRole('textbox').fill('admin');
		await page.getByRole('button', { name: 'Search' }).click();
		await page.getByRole('radio', { name: 'admin' }).check();
		await page.getByRole('button', { name: 'Create' }).click();
		await expect(page.locator('td[id=key-1]')).toBeVisible();
	}),
	test('Run tools list with API key and session auth', async ({ page }) => {
		// The tool catalog has 66 tools with large descriptions, and the page takes a while to draw them.
		// So the tools are checked in the response, not on the page, and this leaves headroom under load.
		test.setTimeout(300000);
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		// AI settings (MCP server and AI assistant) are now under Settings → AI in the Formulize admin UI.
		await page.goto('/modules/formulize/admin/ui.php?page=settings&view=ai');
		// Wait for the settings form to become visible: the admin-ui wrapper starts with
		// display:none and is revealed by $(window).load in formulize-admin.js.
		const mcpServerEnabled = page.locator('input[name="formulizeMCPServerEnabled"][value="1"]');
		const aiAssistantEnabled = page.locator('input[name="formulizeAIAssistantEnabled"][value="0"]');
		await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
		await mcpServerEnabled.check();
		await aiAssistantEnabled.check();
		await page.getByRole('button', { name: 'Save your changes' }).click();
		await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
		// With MCP enabled, the preference description shows the external-assistant setup instructions.
		// Confirming this text is visible verifies the setting was saved on.
		await expect(page.getByText('for users to use an external AI assistant')).toBeVisible();
		await expect(mcpServerEnabled).toBeChecked();
		// Logging is under Settings → System.
		await page.goto('/modules/formulize/admin/ui.php?page=settings&view=system');
		const loggingEnabled = page.locator('input[name="formulizeLoggingOnOff"][value="1"]');
		await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
		await loggingEnabled.check();
		await page.getByRole('button', { name: 'Save your changes' }).click();
		await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
		// API Keys are under Users → API Keys. This is a fresh page load (not right after creation), so
		// the key is masked on screen and only available via the data-key attribute on its copy icon.
		await page.goto('/modules/formulize/admin/ui.php?page=users&view=apikeys');
		await page.locator('.admin-ui').waitFor({ state: 'visible' });
		const apiKey = await page.locator('td[id=key-1] .key-copy').getAttribute('data-key');
		await page.goto('/user.php?op=logout');
		await page.goto('/mcp/test.html');

		// --- API key auth (external MCP server path) ---
		await expectResponses(page, '3 total, 0 success');
		await expect(page.getByText(/httpStatus\": 401/).first()).toBeVisible();
		await page.getByRole('textbox', { name: 'API Key (32-character hex):' }).fill(apiKey);
		await page.locator('div[class="response-header"]').click();
		await expectResponses(page, '6 total, 3 success');
		expect(await sendAndReadToolNames(page)).toEqual(EXPECTED_TOOLS);

		// --- Session auth (embedded AI assistant path) ---
		// 401 for user not logged in
		await page.goto('/mcp/test.html');
		await expectResponses(page, '3 total, 0 success');
		await page.locator('input[name="authMode"][value="session"]').check();
		await expectResponses(page, '6 total, 0 success');
		await expect(page.getByText(/httpStatus\": 401/).first()).toBeVisible();

		// 503 for user logged in but MCP server unavailable
		await page.goto('/');
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.goto('/mcp/test.html');
		await expectResponses(page, '3 total, 0 success');
		await page.locator('input[name="authMode"][value="session"]').check();
		await expectResponses(page, '6 total, 0 success');
		await expect(page.getByText(/httpStatus\": 503/).first()).toBeVisible();

		// --- enable assistant ---
		// Navigate to Settings → AI to enable the embedded AI assistant.
		await page.goto('/modules/formulize/admin/ui.php?page=settings&view=ai');
		const aiAssistantEnabled2 = page.locator('input[name="formulizeAIAssistantEnabled"][value="1"]');
		await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
		await aiAssistantEnabled2.check();
		await page.getByRole('button', { name: 'Save your changes' }).click();
		await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
		await expect(page.getByText('Learn more: https://formulize.org/ai/setup-embedded')).toBeVisible();
		await expect(aiAssistantEnabled2).toBeChecked();
		await page.goto('/mcp/test.html');
		await expectResponses(page, '3 total, 0 success');
		await expect(page.getByText(/httpStatus\": 401/).first()).toBeVisible();
		await page.locator('input[name="authMode"][value="session"]').check();
		await expectResponses(page, '6 total, 3 success');
		expect(await sendAndReadToolNames(page)).toEqual(EXPECTED_TOOLS);
	})
});
