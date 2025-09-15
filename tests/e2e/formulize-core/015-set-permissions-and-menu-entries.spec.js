const { test, expect } = require('@playwright/test')
import { loginAsAdmin, saveChanges } from '../utils';

async function setStandardPermissions(page) {
	await page.locator('#groups').selectOption(['4', '5', '6']);
	await page.getByRole('button', { name: 'Show permissions for these' }).click();
	await expect(page.getByRole('group', { name: 'Ancient History' }).locator('legend')).toBeVisible();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('View the form').check();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('Create their own entries in').check();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('Update entries made by their').check();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('Delete entries made by their').check();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('View entries by other users').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('View the form').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('Create their own entries in').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('Update entries made by their').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('Delete entries made by their').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('View entries by other users').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('View the form').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('Create their own entries in').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('Update entries made by anyone').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('Delete entries made by anyone').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('View entries by all other').check();
	await saveChanges(page);
}

async function setGlobalPermissions(page) {
	await page.locator('#groups').selectOption(['4', '5', '6']);
	await page.getByRole('button', { name: 'Show permissions for these' }).click();
	await expect(page.getByRole('group', { name: 'Ancient History' }).locator('legend')).toBeVisible();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('View the form').check();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('Create their own entries in').check();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('Update entries made by anyone').check();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('Delete entries made by anyone').check();
	await page.getByRole('group', { name: 'Ancient History' }).getByLabel('View entries by all other').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('View the form').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('Create their own entries in').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('Update entries made by anyone').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('Delete entries made by anyone').check();
	await page.getByRole('group', { name: 'Modern History' }).getByLabel('View entries by all other').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('View the form').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('Create their own entries in').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('Update entries made by anyone').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('Delete entries made by anyone').check();
	await page.getByRole('group', { name: 'Curators' }).getByLabel('View entries by all other').check();
	await saveChanges(page);
}

async function setAnonPermissions(page) {
	await page.locator('#groups').selectOption(['3']);
	await page.getByRole('button', { name: 'Show permissions for these' }).click();
	await expect(page.getByRole('group', { name: 'Anonymous Users' }).locator('legend')).toBeVisible();
	await page.getByRole('group', { name: 'Anonymous Users' }).getByLabel('View the form').check();
	await page.getByRole('group', { name: 'Anonymous Users' }).getByLabel('Create their own entries in').check();
	await page.getByRole('group', { name: 'Anonymous Users' }).getByLabel('Update entries made by themselves').check();
	await saveChanges(page);
}

test.describe('Set Permissions and Menu Entries', () => {

	test('Set Permissions on first five forms', async ({ page }) => {

		await loginAsAdmin(page);

		await page.goto('/modules/formulize/admin/ui.php?page=form&fid=1&aid=1&tab=permissions');
		await setStandardPermissions(page);
		await page.goto('/modules/formulize/admin/ui.php?page=form&fid=2&aid=1&tab=permissions');
		await setStandardPermissions(page);
		await page.goto('/modules/formulize/admin/ui.php?page=form&fid=3&aid=1&tab=permissions');
		await setStandardPermissions(page);
		await page.goto('/modules/formulize/admin/ui.php?page=form&fid=4&aid=1&tab=permissions');
		await setStandardPermissions(page);
		await page.goto('/modules/formulize/admin/ui.php?page=form&fid=5&aid=1&tab=permissions');
		await setGlobalPermissions(page);
		await setAnonPermissions(page);

	}),
	test('Set Anon Access to Survey Form', async ({ page }) => {

		await loginAsAdmin(page);

		await page.goto('/modules/formulize/admin/ui.php?page=screen&aid=1&fid=5&sid=9');
	  await page.getByText('No, only permission to view').click();
		await saveChanges(page);

	}),
	test('Set Menu Entries', async ({ page }) => {

		await loginAsAdmin(page);

		await page.goto('/modules/formulize/admin/ui.php?page=application&aid=1&tab=menu%20entries');
	  await page.getByRole('link', { name: 'Artifacts' }).click();
	  await page.locator('#groups0').selectOption(['1', '4', '5']);
		await page.locator('#defaultScreenGroups0').selectOption(['1', '4', '5']);
	  await page.getByRole('link', { name: 'Donors' }).click();
	  await page.locator('#groups1').selectOption(['1', '4', '5']);
	  await page.getByRole('link', { name: 'Collections' }).click();
	  await page.locator('#groups2').selectOption(['1', '4', '5']);
  	await page.getByRole('link', { name: 'Exhibits' }).click();
	  await page.locator('#groups3').selectOption(['1', '4', '5']);
	  await page.getByRole('link', { name: 'Surveys' }).click();
	  await page.locator('#groups4').selectOption(['1', '4', '5']);
	  await page.getByRole('link', { name: 'Artifacts' }).click();
		await saveChanges(page);

	})
});


