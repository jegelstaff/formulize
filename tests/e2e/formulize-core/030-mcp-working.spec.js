const { test, expect } = require('@playwright/test')
import { login } from '../utils';

test.describe('Check that tools/list is responding', () => {
	test('Create API Key', async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.getByRole('link', { name: 'Admin' }).click();
		await page.getByRole('link', { name: 'Manage API keys' }).click();
		await page.getByRole('textbox').fill('admin');
		await page.getByRole('button', { name: 'Search' }).click();
		await page.getByRole('radio', { name: 'admin' }).check();
		await page.getByRole('button', { name: 'Create' }).click();
  	await expect(page.getByRole('cell', { id: 'key-1' })).toBeVisible();
	}),
	test('Run tools list with API key', async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.getByRole('link', { name: 'Admin' }).click();
		await page.getByRole('link', { name: 'Manage API keys' }).click();
		const apiKey = await page.getByRole('cell', { id: 'key-1' }).innerText();
    await page.goto('/mcp/test.html');
   	await page.getByRole('textbox', { name: 'API Key (32-character hex):' }).fill(apiKey);
    await page.getByRole('button', { name: '🚀 Send Request' }).click();
    await expect(page.getByText('0: "name": "formulize" "')).toBeVisible();
		await expect(page.getByText('1: "name": "list_forms" "')).toBeVisible();
		await expect(page.getByText('2: "name": "list_applications" "')).toBeVisible();
		await expect(page.getByText('3: "name": "list_form_connections" "')).toBeVisible();
		await expect(page.getByText('4: "name": "list_screens" "')).toBeVisible();
		await expect(page.getByText('5: "name": "list_groups" "')).toBeVisible();
		await expect(page.getByText('6: "name": "list_group_members" "')).toBeVisible();
		await expect(page.getByText('7: "name": "list_users" "')).toBeVisible();
		await expect(page.getByText('8: "name": "list_a_users_groups" "')).toBeVisible();
		await expect(page.getByText('9: "name": "get_form_details" "')).toBeVisible();
		await expect(page.getByText('10: "name": "get_screen_details" "')).toBeVisible();
		await expect(page.getByText('11: "name": "create_entry" "')).toBeVisible();
		await expect(page.getByText('12: "name": "update_entry" "')).toBeVisible();
		await expect(page.getByText('13: "name": "get_entries_from_form" "')).toBeVisible();
		await expect(page.getByText('14: "name": "prepare_database_values_for_human_readability" "')).toBeVisible();
		await expect(page.getByText('15: "name": "test_connection" "')).toBeVisible();
		await expect(page.getByText('19: "name": "query_the_database_directly" "')).toBeVisible();
		await expect(page.getByText('19: "name": "create_form" "')).toBeVisible();
		await expect(page.getByText('20: "name": "create_list_element" "')).toBeVisible();
		await expect(page.getByText('21: "name": "create_application" "')).toBeVisible();
		await expect(page.getByText('22: "name": "update_list_element" "')).toBeVisible();
		await expect(page.getByText('23: "name": "create_linked_list_element" "')).toBeVisible();
		await expect(page.getByText('24: "name": "update_linked_list_element" "')).toBeVisible();
		await expect(page.getByText('25: "name": "create_user_list_element" "')).toBeVisible();
		await expect(page.getByText('26: "name": "update_user_list_element" "')).toBeVisible();
		await expect(page.getByText('27: "name": "create_selector_element" "')).toBeVisible();
		await expect(page.getByText('28: "name": "update_selector_element" "')).toBeVisible();
		await expect(page.getByText('29: "name": "create_text_box_element" "')).toBeVisible();
		await expect(page.getByText('30: "name": "update_text_box_element" "')).toBeVisible();
		await expect(page.getByText('31: "name": "create_subform_interface" "')).toBeVisible();
		await expect(page.getByText('32: "name": "update_subform_interface" "')).toBeVisible();
		await expect(page.getByText('33: "name": "read_system_activity_log" "')).toBeVisible();
	})
});
