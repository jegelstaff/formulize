const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login } from '../../utils';

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
		// API Keys are under Users → API Keys.
		await page.goto('/modules/formulize/admin/ui.php?page=users&view=apikeys');
		await page.locator('.admin-ui').waitFor({ state: 'visible' });
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
		await expect(page.locator('#fixed-stats')).toContainText('0 total');
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
		await expect(page.getByText('21: "name": "create_form_screen" "').first()).toBeVisible();
		await expect(page.getByText('22: "name": "update_form_screen" "').first()).toBeVisible();
		await expect(page.getByText('23: "name": "change_form_screen_page_order" "').first()).toBeVisible();
		await expect(page.getByText('24: "name": "create_list_element" "').first()).toBeVisible();
		await expect(page.getByText('25: "name": "update_list_element" "').first()).toBeVisible();
		await expect(page.getByText('26: "name": "create_linked_list_element" "').first()).toBeVisible();
		await expect(page.getByText('27: "name": "update_linked_list_element" "').first()).toBeVisible();
		await expect(page.getByText('28: "name": "create_user_list_element" "').first()).toBeVisible();
		await expect(page.getByText('29: "name": "update_user_list_element" "').first()).toBeVisible();
		await expect(page.getByText('30: "name": "create_static_content_element" "').first()).toBeVisible();
		await expect(page.getByText('31: "name": "update_static_content_element" "').first()).toBeVisible();
		await expect(page.getByText('32: "name": "create_selector_element" "').first()).toBeVisible();
		await expect(page.getByText('33: "name": "update_selector_element" "').first()).toBeVisible();
		await expect(page.getByText('34: "name": "create_derived_value_element" "').first()).toBeVisible();
		await expect(page.getByText('35: "name": "update_derived_value_element" "').first()).toBeVisible();
		await expect(page.getByText('36: "name": "create_text_box_element" "').first()).toBeVisible();
		await expect(page.getByText('37: "name": "update_text_box_element" "').first()).toBeVisible();
		await expect(page.getByText('38: "name": "create_table_of_elements" "').first()).toBeVisible();
		await expect(page.getByText('39: "name": "update_table_of_elements" "').first()).toBeVisible();
		await expect(page.getByText('40: "name": "create_subform_interface" "').first()).toBeVisible();
		await expect(page.getByText('41: "name": "update_subform_interface" "').first()).toBeVisible();
		await expect(page.getByText('42: "name": "read_system_activity_log" "').first()).toBeVisible();

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
		await page.waitForLoadState('networkidle');
		await expect(page.getByText(/httpStatus\": 401/).first()).toBeVisible();
		await page.locator('input[name="authMode"][value="session"]').check();
		await page.locator('div[class="response-header"]').click();
		await page.waitForLoadState('networkidle');
		await page.getByRole('button', { name: 'Clear', exact: true }).click();
		await expect(page.locator('#fixed-stats')).toContainText('0 total');
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
		await expect(page.getByText('42: "name": "read_system_activity_log" "').first()).toBeVisible();
	})
});
