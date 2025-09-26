const { test, expect } = require('@playwright/test')
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from './config';
import { login } from '../utils';

test.describe('Installation of Formulize', () => {
	test('Run the Installer', async ({ page }) => {
		await page.goto('/install/index.php');
  	// Welcome page
		await expect(page.locator('h2')).toContainText('Welcome to the Formulize installation assistant');
		await page.getByRole('button', { name: 'Next' }).click();
  	// Check server configuration
		await expect(page.locator('h2')).toContainText('Checking your server configuration');
		await page.getByRole('button', { name: 'Next' }).click();
		// Path settings
		await expect(page.locator('h2')).toContainText('Paths settings');
  	await page.getByLabel('ImpressCMS physical trust path').fill('/var/www/html/trust');
  	await page.getByRole('button', { name: 'Next' }).click();
		// Database connection
		await expect(page.locator('h2')).toContainText('Database connection');
  	await page.getByLabel('Server hostname').fill('mariadb');
  	await page.getByLabel('User name').fill('user');
  	await page.getByLabel('Password').fill('password');
  	await page.getByRole('button', { name: 'Next' }).click();
		// Database configuration
		await expect(page.locator('h2')).toContainText('Database configuration');
  	await page.getByLabel('Database name').fill('formulize');
  	await page.getByRole('button', { name: 'Next' }).click();
		// Saving configuration
		await expect(page.locator('h2')).toContainText('Saving your system configuration');
  	await expect(page.getByRole('paragraph')).toContainText('The installer is now ready to save the specified settings to mainfile.php.Press next to proceed.');
  	await page.getByRole('button', { name: 'Next' }).click();
		// DB table creation
		await expect(page.locator('h2')).toContainText('Database tables creation');
		await expect(page.getByRole('paragraph')).toContainText('No ImpressCMS tables were detected.The installer is now ready to create the ImpressCMS system tables.Press next to proceed.');
  	await page.getByRole('button', { name: 'Next' }).click();
		// DB table creation results
		await expect(page.locator('h2')).toContainText('Database tables creation');
		await expect(page.locator('#tablescreate')).toContainText('_avatar created.');
  	await page.getByRole('button', { name: 'Next' }).click();
		// Initial settings
		await expect(page.locator('h2')).toContainText('Please enter your initial settings');
		await page.getByLabel('Admin Display Name').fill('admin');
		await page.getByLabel('Admin login').fill('admin');
		await page.getByLabel('Admin e-mail').fill('formulize@example.com');
		await page.getByLabel('Admin password').fill('password');
		await page.getByLabel('Confirm password').fill('password');
  	await page.getByRole('button', { name: 'Next' }).click();
		// Saving to DB
		await expect(page.locator('h2')).toContainText('Saving your settings to the database');
		await expect(page.getByRole('paragraph')).toContainText('The installer is now ready to insert initial data into your database.THIS COULD TAKE A REALLY LONG TIME DEPENDING ON YOUR SERVER SOFTWARE AND CONFIGURATION!');
  	await page.getByRole('button', { name: 'Next' }).click();
		// DB saving results
		await expect(page.locator('h2')).toContainText('Saving your settings to the database');
		await expect(page.locator('#tablesfill')).toContainText('1 entries inserted to table');
  	await page.getByRole('button', { name: 'Next' }).click();
		// Install modules
		await expect(page.locator('h2')).toContainText('Installation of modules');
  	await page.getByRole('button', { name: 'Next' }).click();
		// Install modules results
		await expect(page.locator('h2')).toContainText('Installation of modules');
  	await page.getByRole('button', { name: 'Next' }).click();
		// Install finished
		await expect(page.locator('h2')).toContainText('Installation completed');
		await expect(page.getByRole('button', { name: 'Show my site' })).toBeVisible();
  	await page.getByRole('button', { name: 'Show my site' }).click();
		// Running instance
		await expect(page.locator('#main-logo').getByRole('link', { name: 'Logo image' })).toBeVisible();

	}),
	test('Update Formulize', async ({ page }) => {

		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
  	await page.goto('/modules/formulize/admin');
		await expect(page.getByRole('link', { name: 'Create a new form' })).toBeVisible();

		// apply DB patch if necessary
		const isPatchVisible = await page.locator('input[name="patch40"]').isVisible();
		if (isPatchVisible) {
			await page.getByRole('button', { name: 'Apply Database Patch for' }).click();
			await expect(page.getByRole('link', { name: 'Close' })).toBeVisible();
			await page.getByRole('link', { name: 'Close' }).click();
		}

		// update module
		await page.getByRole('link', { name: 'arrow Modules' }).click();
		await page.getByRole('link', { name: 'Update' }).nth(2).click();
		await page.getByRole('button', { name: 'Update' }).click();
		await expect(page.getByRole('link', { name: 'Back to Module Administration' })).toBeVisible();
		await page.goto('/modules/formulize/admin/');

	})
});
