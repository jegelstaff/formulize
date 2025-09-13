const { test, expect } = require('@playwright/test')
import { saveChanges } from '../utils';

test.describe('Create Users and Groups', () => {

	test('Create Groups', async ({ page }) => {

		await page.goto('/');
		await page.locator('input[name="uname"]').click();
		await page.locator('input[name="uname"]').fill('admin');
		await page.locator('input[name="uname"]').press('Tab');
		await page.locator('input[name="pass"]').fill('password');
		await page.locator('input[name="pass"]').press('Enter');
		await expect(page.getByRole('link', { name: 'Admin' })).toBeVisible();

   	await page.goto('/modules/system/admin.php?fct=groups');
		await page.locator('#name').fill('Ancient History');
		await page.getByRole('button', { name: 'Create New Group' }).click();
		await page.locator('#name').fill('Modern History');
		await page.getByRole('button', { name: 'Create New Group' }).click();
		await page.locator('#name').fill('Curators');
		await page.getByRole('button', { name: 'Create New Group' }).click();

	}),
	test('Create Users', async ({ page }) => {

		await page.goto('/');
		await page.locator('input[name="uname"]').click();
		await page.locator('input[name="uname"]').fill('admin');
		await page.locator('input[name="uname"]').press('Tab');
		await page.locator('input[name="pass"]').fill('password');
		await page.locator('input[name="pass"]').press('Enter');
		await expect(page.getByRole('link', { name: 'Admin' })).toBeVisible();

		await page.goto('/modules/system/admin.php?fct=users');
		await page.locator('#login_name').fill('ahstaff');
		await page.locator('#uname').fill('Ancient History Staff');
		await page.locator('#email').fill('ahstaff@museum.formulize.net');
		await page.locator('#password').fill('12345');
		await page.locator('#vpass').fill('12345');
		await page.getByRole('radio', { name: 'Active', exact: true }).check();
		await page.locator('#groups').selectOption(['4', '2']);
		await page.getByRole('button', { name: 'Save changes' }).click();
		await page.locator('#login_name').fill('mhstaff');
		await page.locator('#uname').fill('Modern History Staff');
		await page.locator('#email').fill('mhstaff@museum.formulize.net');
		await page.locator('#password').fill('12345');
		await page.locator('#vpass').fill('12345');
		await page.getByRole('radio', { name: 'Active', exact: true }).check();
		await page.locator('#groups').selectOption(['5', '2']);
		await page.getByRole('button', { name: 'Save changes' }).click();
		await page.locator('#login_name').fill('curator1');
		await page.locator('#uname').fill('Curator One');
		await page.locator('#email').fill('c1@museum.formulize.net');
		await page.locator('#password').fill('12345');
		await page.locator('#vpass').fill('12345');
		await page.getByRole('radio', { name: 'Active', exact: true }).check();
		await page.locator('#groups').selectOption(['6', '2']);
		await page.getByRole('button', { name: 'Save changes' }).click();
		await page.locator('#login_name').fill('curator2');
		await page.locator('#uname').fill('Curator Two');
		await page.locator('#email').fill('c2@museum.formulize.net');
		await page.locator('#password').fill('12345');
		await page.locator('#vpass').fill('12345');
		await page.getByRole('radio', { name: 'Active', exact: true }).check();
		await page.locator('#groups').selectOption(['6', '2']);
		await page.getByRole('button', { name: 'Save changes' }).click();

	})
});
