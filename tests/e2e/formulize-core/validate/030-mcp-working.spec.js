const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login } from '../../utils';

test.describe('Check that tools/list is responding', () => {
	test('Create API Key', async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.getByRole('link', { name: 'Admin' }).click();
		await page.getByRole('link', { name: 'Home' }).click();
		await page.getByRole('link', { name: 'API Keys' }).click();
		await page.getByRole('textbox').fill('admin');
		await page.getByRole('button', { name: 'Search' }).click();
		await page.getByRole('radio', { name: 'admin' }).check();
		await page.getByRole('button', { name: 'Create' }).click();
		await expect(page.locator('td[id=key-1]')).toBeVisible();
	}),
	test('Run tools list with API key and session auth', async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.getByRole('link', { name: 'Admin' }).click();
		await page.getByRole('link', { name: 'Home' }).click();
		await page.getByRole('link', { name: 'Preferences' }).click();
		// Use stable name+value attributes instead of auto-generated counter-based IDs.
		// Also wait for the preferences form to become visible: it starts at opacity:0 and is revealed
		// by $(window).load, which may fire after waitForAdminPageReady resolves (especially on retry
		// when jGrowl fires and adds resources that delay the load event).
		// Enable both the external MCP server and the embedded AI assistant in one save so that a
		// concurrent validate test cannot overwrite one while the other is being tested.
		const mcpServerEnabled = page.locator('input[name="formulizeMCPServerEnabled"][value="1"]');
		const loggingEnabled = page.locator('input[name="formulizeLoggingOnOff"][value="1"]');
		const aiAssistantEnabled = page.locator('input[name="formulizeAIAssistantEnabled"][value="0"]');
		await page.locator('#formulize-prefs-hide-on-load').waitFor({ state: 'visible' });
		await mcpServerEnabled.check();
		await loggingEnabled.check();
		await aiAssistantEnabled.check();
		await page.getByRole('button', { name: 'Save your changes' }).click();
		await page.locator('#formulize-prefs-hide-on-load').waitFor({ state: 'visible' });
		// With MCP enabled, the preference label shows the external-assistant setup instructions.
		// Confirming this text is visible verifies the setting was saved on.
		await expect(page.getByText('See further setup instructions for external AI assistants')).toBeVisible();
		await expect(mcpServerEnabled).toBeChecked();
		await page.locator('#formulize-prefs-hide-on-load').getByRole('link', { name: 'Formulize', exact: true }).click();
		await page.getByRole('link', { name: 'API keys' }).click();
		const apiKey = await page.locator('td[id=key-1]').innerText();
		await page.goto('/user.php?op=logout');
		await page.goto('/mcp/test.html');

		// --- API key auth (external MCP server path) ---
		await page.waitForLoadState('networkidle');
		await expect(page.getByText(/httpStatus\": 401/).first()).toBeVisible();
		await page.getByRole('textbox', { name: 'API Key (32-character hex):' }).fill(apiKey);
		await page.locator('div[class="response-header"]').click();
		await page.waitForLoadState('networkidle');
		await page.getByRole('button', { name: 'Clear', exact: true }).click();
		await expect(page.getByText(/0 total/)).toBeVisible();
		await page.getByRole('button', { name: '🚀 Send Request' }).click();
		// expect 1 total or 2 total or 3 total to be visible
		await expect(page.getByText(/[1-3] total/)).toBeVisible();
		await expect(page.getByText('0: "name": "formulize" "').first()).toBeVisible();
		await expect(page.getByText('1: "name": "list_forms" "').first()).toBeVisible();
		await expect(page.getByText('2: "name": "list_applications" "').first()).toBeVisible();
		await expect(page.getByText('3: "name": "list_form_connections" "').first()).toBeVisible();
		await expect(page.getByText('4: "name": "list_screens" "').first()).toBeVisible();
		await expect(page.getByText('5: "name": "list_groups" "').first()).toBeVisible();
		await expect(page.getByText('6: "name": "list_group_members" "').first()).toBeVisible();
		await expect(page.getByText('7: "name": "list_users" "').first()).toBeVisible();
		await expect(page.getByText('8: "name": "list_a_users_groups" "').first()).toBeVisible();
		await expect(page.getByText('9: "name": "get_form_details" "').first()).toBeVisible();
		await expect(page.getByText('10: "name": "get_screen_details" "').first()).toBeVisible();
		await expect(page.getByText('11: "name": "create_entries" "').first()).toBeVisible();
		await expect(page.getByText('12: "name": "update_entries" "').first()).toBeVisible();
		await expect(page.getByText('13: "name": "get_entries_from_form" "').first()).toBeVisible();
		await expect(page.getByText('14: "name": "prepare_database_values_for_human_readability" "').first()).toBeVisible();
		await expect(page.getByText('15: "name": "test_connection" "').first()).toBeVisible();
		await expect(page.getByText('19: "name": "query_the_database_directly" "').first()).toBeVisible();
		await expect(page.getByText('20: "name": "create_form" "').first()).toBeVisible();
		await expect(page.getByText('21: "name": "create_list_element" "').first()).toBeVisible();
		await expect(page.getByText('22: "name": "update_list_element" "').first()).toBeVisible();
		await expect(page.getByText('23: "name": "create_linked_list_element" "').first()).toBeVisible();
		await expect(page.getByText('24: "name": "update_linked_list_element" "').first()).toBeVisible();
		await expect(page.getByText('25: "name": "create_user_list_element" "').first()).toBeVisible();
		await expect(page.getByText('26: "name": "update_user_list_element" "').first()).toBeVisible();
		await expect(page.getByText('27: "name": "create_selector_element" "').first()).toBeVisible();
		await expect(page.getByText('28: "name": "update_selector_element" "').first()).toBeVisible();
		await expect(page.getByText('29: "name": "create_derived_value_element" "').first()).toBeVisible();
		await expect(page.getByText('30: "name": "update_derived_value_element" "').first()).toBeVisible();
		await expect(page.getByText('31: "name": "create_text_box_element" "').first()).toBeVisible();
		await expect(page.getByText('32: "name": "update_text_box_element" "').first()).toBeVisible();
		await expect(page.getByText('33: "name": "create_table_of_elements" "').first()).toBeVisible();
		await expect(page.getByText('34: "name": "update_table_of_elements" "').first()).toBeVisible();
		await expect(page.getByText('35: "name": "create_subform_interface" "').first()).toBeVisible();
		await expect(page.getByText('36: "name": "update_subform_interface" "').first()).toBeVisible();
		await expect(page.getByText('37: "name": "read_system_activity_log" "').first()).toBeVisible();

		// --- Session auth (embedded AI assistant path) ---
		// 401 for user not logged in
		await page.goto('/mcp/test.html');
		await page.waitForLoadState('networkidle');
		await page.getByRole('button', { name: 'Clear', exact: true }).click();
		await page.locator('input[name="authMode"][value="session"]').check();
		await page.locator('div[class="response-header"]').click();
		await page.waitForLoadState('networkidle');
		await expect(page.getByText(/httpStatus\": 401/).first()).toBeVisible();

		// 503 for user logged in but MCP server unavailable
		await page.goto('/');
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.goto('/mcp/test.html');
		await page.waitForLoadState('networkidle');
		await page.getByRole('button', { name: 'Clear', exact: true }).click();
		await page.locator('input[name="authMode"][value="session"]').check();
		await page.locator('div[class="response-header"]').click();
		await page.waitForLoadState('networkidle');
		await expect(page.getByText(/httpStatus\": 503/).first()).toBeVisible();

		// --- login again and enable assistant ---
		await page.goto('/');
		await page.getByRole('link', { name: 'Admin' }).click();
		await page.getByRole('link', { name: 'Home' }).click();
		await page.getByRole('link', { name: 'Preferences' }).click();
		// Use stable name+value attributes instead of auto-generated counter-based IDs.
		// Also wait for the preferences form to become visible: it starts at opacity:0 and is revealed
		// by $(window).load, which may fire after waitForAdminPageReady resolves (especially on retry
		// when jGrowl fires and adds resources that delay the load event).
		// Enable both the external MCP server and the embedded AI assistant in one save so that a
		// concurrent validate test cannot overwrite one while the other is being tested.
		const aiAssistantEnabled2 = page.locator('input[name="formulizeAIAssistantEnabled"][value="1"]');
		await page.locator('#formulize-prefs-hide-on-load').waitFor({ state: 'visible' });
		await aiAssistantEnabled2.check();
		await page.getByRole('button', { name: 'Save your changes' }).click();
		await page.locator('#formulize-prefs-hide-on-load').waitFor({ state: 'visible' });
		// With MCP enabled, the preference label shows the external-assistant setup instructions.
		// Confirming this text is visible verifies the setting was saved on.
		await expect(page.getByText('Learn more: https://formulize.org/ai/setup-embedded')).toBeVisible();
		await expect(aiAssistantEnabled2).toBeChecked();
		await page.goto('/mcp/test.html');
		await page.waitForLoadState('networkidle');
		await expect(page.getByText(/httpStatus\": 401/).first()).toBeVisible();
		await page.locator('input[name="authMode"][value="session"]').check();
		await page.locator('div[class="response-header"]').click();
		await page.waitForLoadState('networkidle');
		await page.getByRole('button', { name: 'Clear', exact: true }).click();
		await expect(page.getByText(/0 total/)).toBeVisible();
		await page.getByRole('button', { name: '🚀 Send Request' }).click();
		// expect 1 total or 2 total or 3 total to be visible
		await expect(page.getByText(/[1-3] total/)).toBeVisible();
		await expect(page.getByText('0: "name": "formulize" "').first()).toBeVisible();
		await expect(page.getByText('1: "name": "list_forms" "').first()).toBeVisible();
		await expect(page.getByText('2: "name": "list_applications" "').first()).toBeVisible();
		await expect(page.getByText('3: "name": "list_form_connections" "').first()).toBeVisible();
		await expect(page.getByText('4: "name": "list_screens" "').first()).toBeVisible();
		await expect(page.getByText('5: "name": "list_groups" "').first()).toBeVisible();
		await expect(page.getByText('6: "name": "list_group_members" "').first()).toBeVisible();
		await expect(page.getByText('7: "name": "list_users" "').first()).toBeVisible();
		await expect(page.getByText('8: "name": "list_a_users_groups" "').first()).toBeVisible();
		await expect(page.getByText('9: "name": "get_form_details" "').first()).toBeVisible();
		await expect(page.getByText('15: "name": "test_connection" "').first()).toBeVisible();
		await expect(page.getByText('19: "name": "query_the_database_directly" "').first()).toBeVisible();
		await expect(page.getByText('37: "name": "read_system_activity_log" "').first()).toBeVisible();
	})
});
