const { test, expect } = require('@playwright/test')
import { saveChanges, waitForFormulizeFormToken } from '../utils';
import { loginAs } from '../utils';

test.describe('Data Entry', () => {

	test('Create Collections', async ({ page }) => {

		await loginAs('ahstaff', page);
		await page.locator('#burger-and-logo').getByRole('link').first().click();
  	await expect(page.getByRole('link', { name: 'Artifacts', exact: true })).toBeVisible();
	  await expect(page.getByRole('link', { name: 'Collections' })).toBeVisible();
	  await expect(page.getByRole('link', { name: 'Exhibits' })).toBeVisible();
	  await expect(page.getByRole('link', { name: 'Donors', exact: true })).toBeVisible();
	  await expect(page.getByRole('link', { name: 'Surveys' })).toBeVisible();
		await page.goto('/modules/formulize/index.php?fid=3');
		await page.getByRole('button', { name: 'Add Collection', exact: true }).click();
		await waitForFormulizeFormToken(page);
  	await page.getByRole('textbox', { name: 'Name *' }).fill('Ancient History');
	  await page.getByRole('checkbox', { name: 'Children' }).check();
  	await page.getByRole('checkbox', { name: 'Adults' }).check();
  	await page.getByRole('link', { name: ' Save and Leave' }).click();
		await page.getByRole('link', { name: 'Logout' }).click();

		await loginAs('mhstaff', page);
		await page.locator('#burger-and-logo').getByRole('link').first().click();
  	await expect(page.getByRole('link', { name: 'Artifacts', exact: true })).toBeVisible();
	  await expect(page.getByRole('link', { name: 'Collections' })).toBeVisible();
	  await expect(page.getByRole('link', { name: 'Exhibits' })).toBeVisible();
	  await expect(page.getByRole('link', { name: 'Donors', exact: true })).toBeVisible();
	  await expect(page.getByRole('link', { name: 'Surveys' })).toBeVisible();
		await page.goto('/modules/formulize/index.php?fid=3');
		await expect(page.getByText('No entries were found in the')).toBeVisible();
		await page.getByRole('button', { name: 'Add Collection', exact: true }).click();
		await waitForFormulizeFormToken(page);
  	await page.getByRole('textbox', { name: 'Name *' }).fill('Modern History');
	  await page.getByRole('checkbox', { name: 'Children' }).check();
  	await page.getByRole('checkbox', { name: 'Adults' }).check();
  	await page.getByRole('link', { name: ' Save and Leave' }).click();
		await page.getByRole('link', { name: 'Logout' }).click();
		await loginAs('ahstaff', page);
		await page.goto('/modules/formulize/index.php?fid=3');
		await expect(page.getByText('Modern History')).not.toBeVisible();
		await page.getByRole('link', { name: 'Logout' }).click();

		await loginAs('admin', page);
		await page.goto('/modules/formulize/index.php?fid=3');
		await page.getByRole('button', { name: 'Add Collections', exact: true }).click();
		await waitForFormulizeFormToken(page);
  	await page.getByRole('textbox', { name: 'Name *' }).fill('Coins');
   	await page.getByRole('checkbox', { name: 'Adults' }).check();
   	await page.getByRole('button', { name: 'Save' }).click();
		await waitForFormulizeFormToken(page);
		await page.getByRole('textbox', { name: 'Name *' }).fill('Weapons');
		await page.getByRole('checkbox', { name: 'Children' }).check();
   	await page.getByRole('checkbox', { name: 'Adults' }).check();
   	await page.getByRole('link', { name: ' Save and Leave' }).click();

	})
});


