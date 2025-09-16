const { test, expect } = require('@playwright/test')
import { waitForFormulizeFormToken } from '../utils';
import { loginAs } from '../utils';

test.describe('Data Entry', () => {

	test('Create Collections', async ({ page }) => {

		await loginAs('ahstaff', page);
		await page.locator('#burger-and-logo').getByRole('link').first().click();
  	await expect(page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true })).toBeVisible();
	  await expect(page.locator('#mainmenu').getByRole('link', { name: 'Collections', exact: true })).toBeVisible();
	  await expect(page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true })).toBeVisible();
	  await expect(page.locator('#mainmenu').getByRole('link', { name: 'Donors', exact: true })).toBeVisible();
	  await expect(page.locator('#mainmenu').getByRole('link', { name: 'Surveys', exact: true })).toBeVisible();
		await page.goto('/modules/formulize/index.php?fid=3');
		await page.getByRole('button', { name: 'Add Collection', exact: true }).click();
  	await page.getByRole('textbox', { name: 'Name *' }).fill('Ancient History');
	  await page.getByRole('checkbox', { name: 'Children' }).check();
  	await page.getByRole('checkbox', { name: 'Adults' }).check();
		await waitForFormulizeFormToken(page);
  	await page.getByRole('link', { name: ' Save and Leave' }).click();
		await page.getByRole('link', { name: 'Logout' }).click();

		await loginAs('mhstaff', page);
		await page.locator('#burger-and-logo').getByRole('link').first().click();
  	await expect(page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true })).toBeVisible();
	  await expect(page.locator('#mainmenu').getByRole('link', { name: 'Collections', exact: true })).toBeVisible();
	  await expect(page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true })).toBeVisible();
	  await expect(page.locator('#mainmenu').getByRole('link', { name: 'Donors', exact: true })).toBeVisible();
	  await expect(page.locator('#mainmenu').getByRole('link', { name: 'Surveys', exact: true })).toBeVisible();
		await page.goto('/modules/formulize/index.php?fid=3');
		await expect(page.getByText('No entries were found in the')).toBeVisible();
		await page.getByRole('button', { name: 'Add Collection', exact: true }).click();
  	await page.getByRole('textbox', { name: 'Name *' }).fill('Modern History');
	  await page.getByRole('checkbox', { name: 'Children' }).check();
  	await page.getByRole('checkbox', { name: 'Adults' }).check();
		await waitForFormulizeFormToken(page);
  	await page.getByRole('link', { name: ' Save and Leave' }).click();
		await page.getByRole('link', { name: 'Logout' }).click();
		await loginAs('ahstaff', page);
		await page.goto('/modules/formulize/index.php?fid=3');
		await expect(page.getByText('Modern History')).not.toBeVisible();
		await page.getByRole('link', { name: 'Logout' }).click();

		await loginAs('admin', page);
		await page.goto('/modules/formulize/index.php?fid=3');
		await page.getByRole('button', { name: 'Add Collections' }).click();
  	await page.getByRole('textbox', { name: 'Name *' }).fill('Coins');
   	await page.getByRole('checkbox', { name: 'Adults' }).check();
		await waitForFormulizeFormToken(page);
   	await page.getByRole('button', { name: 'Save' }).click();
		await page.getByRole('textbox', { name: 'Name *' }).fill('Weapons');
		await page.getByRole('checkbox', { name: 'Children' }).check();
   	await page.getByRole('checkbox', { name: 'Adults' }).check();
		await waitForFormulizeFormToken(page);
   	await page.getByRole('link', { name: ' Save and Leave' }).click();

	}),
	test('Create Donors', async ({ page }) => {

		await loginAs('curator1', page);
		await page.goto('/modules/formulize/index.php?fid=2');
		await expect(page.getByText('No entries were found in the')).toBeVisible();
		await page.getByRole('button', { name: 'Add Donor', exact: true }).click();
		await page.getByRole('radio', { name: 'Individual' }).check();
		await expect(page.getByText('First name')).toBeVisible();
	  await page.getByRole('textbox', { name: 'First name' }).fill('François-Marie');
	  await page.getByRole('textbox', { name: 'Last name' }).fill('Arouet');
		await page.getByRole('textbox', { name: 'Email address' }).fill('voltaire@enlightenment.org');
	  await page.getByRole('textbox', { name: 'Favourite colour' }).fill('#6c2d2d');
  	await page.getByRole('textbox', { name: 'Phone number' }).fill('1694221778');
  	await page.locator('#de_2_new_21_tarea').fill('Château de Cirey\nCirey-sur-Blaise\nFrance');
  	await page.getByRole('combobox').selectOption('2');
  	await page.locator('input[name="de_2_new_23"]').fill('G1A 0A2');
		await waitForFormulizeFormToken(page);
  	await page.getByRole('link', { name: ' Save and Leave' }).click();

		await page.getByRole('button', { name: 'Add Donor', exact: true }).click();
		await page.getByRole('radio', { name: 'Individual' }).check();
	  await expect(page.getByText('First name')).toBeVisible();
	  await page.getByRole('textbox', { name: 'First name' }).fill('Emilie');
	  await page.getByRole('textbox', { name: 'Last name' }).fill('Du Châtelet');
		await page.getByRole('textbox', { name: 'Email address' }).fill('duchatelet@enlightenment.org');
	 	await page.getByRole('textbox', { name: 'Favourite colour' }).fill('#3b96c4');
  	await page.getByRole('textbox', { name: 'Phone number' }).fill('1706221749');
  	await page.locator('#de_2_new_21_tarea').fill('Château de Cirey\nCirey-sur-Blaise\nFrance');
  	await page.getByRole('combobox').selectOption('2');
  	await page.locator('input[name="de_2_new_23"]').fill('G1A 0A2');
		await waitForFormulizeFormToken(page);
  	await page.getByRole('link', { name: ' Save and Leave' }).click();

		await page.getByRole('button', { name: 'Add Donor', exact: true }).click();
	  await page.getByRole('radio', { name: 'Organization' }).check();
	  await expect(page.getByText('Organization name')).toBeVisible();
		await page.getByRole('textbox', { name: 'Organization name' }).fill('Freeform Solutions');
		await page.getByRole('textbox', { name: 'Email address' }).fill('formulize@freeform.ca');
  	await page.getByRole('textbox', { name: 'Phone number' }).fill('4166863766');
  	await page.locator('#de_2_new_21_tarea').fill('17 Crossovers St\nToronto');
  	await page.getByRole('combobox').selectOption('1');
  	await page.locator('input[name="de_2_new_23"]').fill('M4E 3X2');
		await waitForFormulizeFormToken(page);
  	await page.getByRole('link', { name: ' Save and Leave' }).click();

	}),
	test('Create Artifacts', async ({ page }) => {

		await loginAs('ahstaff', page);
		await page.goto('/modules/formulize/index.php?fid=1');
		await expect(page.getByText('No entries were found in the')).toBeVisible();
		await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
		await page2.getByRole('textbox', { name: 'Short name *' }).fill('Name');
		await page2.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('There is a lot to say in the description');
		await page2.locator('input[name="de_1_new_4"]').fill('1'); // height
		await page2.locator('input[name="de_1_new_5"]').fill('2'); // width
		await page2.locator('input[name="de_1_new_6"]').fill('3'); // depth
		await page2.locator('input[name="de_1_new_8"]').fill('1999'); // year
		await page2.getByRole('radio', { name: 'CE', exact: true }).check();
		await page2.getByRole('textbox', { name: 'Date of acquisition' }).fill('2025-09-17');
		await page2.getByRole('radio', { name: 'No' }).check(); // not donated
		await page2.getByRole('radio', { name: 'Yes' }).check(); // donated
  	await expect(page.getByText('Donor', { exact: true })).toBeVisible(); // if donated, check that donor shows up
		await page.getByLabel('Donor').selectOption('2'); // Emilie
		await page.getByLabel('Donor').selectOption('1'); // Voltaire
		await page.getByLabel('Donor').selectOption('3'); // Freeform
		await page2.getByRole('slider', { name: 'Condition' }).fill('5'); // slider 1-10
		await page3.getByRole('checkbox', { name: 'Ancient History' }).check();
		await page3.getByRole('checkbox', { name: 'Modern History' }).check();
		await waitForFormulizeFormToken(page);
		await page.getByRole('link', { name: ' Save and Leave' }).click();

	}),
	test('Create Exhibits', async ({ page }) => {



	}),
	test('Create Survey Entries', async ({ page }) => {



	})
});


