import { test, expect } from '@playwright/test';
import { login } from '../utils';
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from './config';

test.describe('Basic functions', () => {
	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	});
	test('Admin login', async ({ page }) => {
  	await expect(page.locator('#formulize')).toContainText('Admin Edit Account Logout');
	});
	test('Admin logout', async ({ page }) => {
		await page.click('text=Logout');
		await expect(page.locator('#block_login_form')).toContainText('Username: Password: Login Lost your account info?');
	});
	test('Admin edit account', async ({ page }) => {
		await page.click('text=Edit Account');
		await page.locator('#timezone_offset').selectOption('-5');
  	await page.getByRole('button', { name: 'Save changes' }).click();
  	await expect(page.locator('#jGrowl')).toContainText('Profile updated!');
  	await expect(page.locator('#timezone_offset')).toHaveValue('-5');
	});
	test('Admin load admin screen', async ({ page }) => {
		await page.click('text=Admin');
  	await expect(page.locator('#xo-content')).toContainText('Formulize Preferences Copy Group Permissions Synchronize With Another System Manage API keys Manage Account Creation Tokens Email Users Create a new form Create a new reference to a datatable Manage your applications Forms that don\'t belong to an application Forms Screens Relationships Menu Entries Export (beta!) Add a Form To assign a form to an application, look on the Settings tab when configuring the form. No forms.');
	});
});
