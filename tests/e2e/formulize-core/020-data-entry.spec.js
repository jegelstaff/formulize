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

		await loginAs('curator1', page);
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

		// Artifact 1: Roman Coin
		await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Roman Coin');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A very lovely Roman Coin.');
		await page.locator('input[name="de_1_new_4"]').fill('0.2'); // height
		await page.locator('input[name="de_1_new_5"]').fill('1.5'); // width
		await page.locator('input[name="de_1_new_6"]').fill('1.5'); // depth
		await page.locator('input[name="de_1_new_8"]').fill('150'); // year
		await page.getByRole('radio', { name: 'BCE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('2020-08-21');
		await page.getByRole('radio', { name: 'Yes' }).check(); // donated
		await page.getByLabel('Donor').selectOption('1'); // Voltaire
		await page.getByRole('slider', { name: 'Condition' }).fill('8');
		await page.getByRole('checkbox', { name: 'Ancient History' }).check();
		await waitForFormulizeFormToken(page);
		await page.getByRole('link', { name: ' Save and Leave' }).click();

		// Artifact 2: Persian necklace
		await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Persian necklace');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A beautiful Persian necklace.');
		await page.locator('input[name="de_1_new_4"]').fill('1.0'); // height
		await page.locator('input[name="de_1_new_5"]').fill('25.0'); // width
		await page.locator('input[name="de_1_new_6"]').fill('35.0'); // depth
		await page.locator('input[name="de_1_new_8"]').fill('350'); // year
		await page.getByRole('radio', { name: 'BCE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('2002-12-02');
		await page.getByRole('radio', { name: 'Yes' }).check(); // donated
		await page.getByLabel('Donor').selectOption('2'); // Emilie
		await page.getByRole('slider', { name: 'Condition' }).fill('9');
		await page.getByRole('checkbox', { name: 'Ancient History' }).check();
		await waitForFormulizeFormToken(page);
		await page.getByRole('link', { name: ' Save and Leave' }).click();

		// Artifact 3: Chinese Sword
		await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Chinese Sword');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('An engraved ceremonial sword.');
		await page.locator('input[name="de_1_new_4"]').fill('135.0'); // height
		await page.locator('input[name="de_1_new_5"]').fill('10.0'); // width
		await page.locator('input[name="de_1_new_6"]').fill('4.5'); // depth
		await page.locator('input[name="de_1_new_8"]').fill('198'); // year
		await page.getByRole('radio', { name: 'CE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('1993-08-10');
		await page.getByRole('radio', { name: 'Yes' }).check(); // donated
		await page.getByLabel('Donor').selectOption('3'); // Freeform Solutions
		await page.getByRole('slider', { name: 'Condition' }).fill('7');
		await page.getByRole('checkbox', { name: 'Ancient History' }).check();
		await waitForFormulizeFormToken(page);
		await page.getByRole('link', { name: ' Save and Leave' }).click();

		// Artifact 4: Egyptian Chariot
		await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Egyptian Chariot');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('An Egyptian Chariot that is partially damaged, but overall excellent condition.');
		await page.locator('input[name="de_1_new_4"]').fill('250.0'); // height
		await page.locator('input[name="de_1_new_5"]').fill('200.0'); // width
		await page.locator('input[name="de_1_new_6"]').fill('475.0'); // depth
		await page.locator('input[name="de_1_new_8"]').fill('1850'); // year
		await page.getByRole('radio', { name: 'BCE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('2011-09-19');
		await page.getByRole('radio', { name: 'No' }).check(); // not donated
		await page.getByRole('slider', { name: 'Condition' }).fill('6');
		await page.getByRole('checkbox', { name: 'Ancient History' }).check();
		await waitForFormulizeFormToken(page);
		await page.getByRole('link', { name: ' Save and Leave' }).click();

		// Artifact 5: Babylonian Spoon
		await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Babylonian Spoon');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A small Babylonian spoon, probably for a child.');
		await page.locator('input[name="de_1_new_4"]').fill('5.0'); // height
		await page.locator('input[name="de_1_new_5"]').fill('1.5'); // width
		await page.locator('input[name="de_1_new_6"]').fill('8.0'); // depth
		await page.locator('input[name="de_1_new_8"]').fill('1500'); // year
		await page.getByRole('radio', { name: 'BCE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('2015-09-26');
		await page.getByRole('radio', { name: 'Yes' }).check(); // donated
		await page.getByLabel('Donor').selectOption('1'); // Voltaire
		await page.getByRole('slider', { name: 'Condition' }).fill('8');
		await page.getByRole('checkbox', { name: 'Ancient History' }).check();
		await waitForFormulizeFormToken(page);
		await page.getByRole('link', { name: ' Save and Leave' }).click();

		// Logout and login as Modern History staff
		await page.getByRole('link', { name: 'Logout' }).click();
		await loginAs('mhstaff', page);
		await page.goto('/modules/formulize/index.php?fid=1');

		// Artifact 6: Florentine Book
		await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Florentine Book');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A book of Latin poetry from medieval Florence.');
		await page.locator('input[name="de_1_new_4"]').fill('22.0'); // height
		await page.locator('input[name="de_1_new_5"]').fill('12.0'); // width
		await page.locator('input[name="de_1_new_6"]').fill('3.0'); // depth
		await page.locator('input[name="de_1_new_8"]').fill('1588'); // year
		await page.getByRole('radio', { name: 'CE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('1988-02-29');
		await page.getByRole('radio', { name: 'Yes' }).check(); // donated
		await page.getByLabel('Donor').selectOption('2'); // Emilie
		await page.getByRole('slider', { name: 'Condition' }).fill('9');
		await page.getByRole('checkbox', { name: 'Modern History' }).check();
		await waitForFormulizeFormToken(page);
		await page.getByRole('link', { name: ' Save and Leave' }).click();

		// Artifact 7: French Musket
		await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
		await page.getByRole('textbox', { name: 'Short name *' }).fill('French Musket');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A musket from the Napoleonic wars.');
		await page.locator('input[name="de_1_new_4"]').fill('150.0'); // height
		await page.locator('input[name="de_1_new_5"]').fill('6.7'); // width
		await page.locator('input[name="de_1_new_6"]').fill('7.1'); // depth
		await page.locator('input[name="de_1_new_8"]').fill('1804'); // year
		await page.getByRole('radio', { name: 'CE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('1927-03-04');
		await page.getByRole('radio', { name: 'No' }).check(); // not donated
		await page.getByRole('slider', { name: 'Condition' }).fill('7');
		await page.getByRole('checkbox', { name: 'Modern History' }).check();
		await waitForFormulizeFormToken(page);
		await page.getByRole('link', { name: ' Save and Leave' }).click();

		// Artifact 8: Japanese Coin
		await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Japanese Coin');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A rare Meiji coin.');
		await page.locator('input[name="de_1_new_4"]').fill('0.2'); // height
		await page.locator('input[name="de_1_new_5"]').fill('25.0'); // width
		await page.locator('input[name="de_1_new_6"]').fill('25.0'); // depth
		await page.locator('input[name="de_1_new_8"]').fill('1880'); // year
		await page.getByRole('radio', { name: 'CE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('1955-11-25');
		await page.getByRole('radio', { name: 'Yes' }).check(); // donated
		await page.getByLabel('Donor').selectOption('3'); // Freeform Solutions
		await page.getByRole('slider', { name: 'Condition' }).fill('10');
		await page.getByRole('checkbox', { name: 'Modern History' }).check();
		await waitForFormulizeFormToken(page);
		await page.getByRole('link', { name: ' Save and Leave' }).click();

		// Artifact 9: American Bicycle
		await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
		await page.getByRole('textbox', { name: 'Short name *' }).fill('American Bicycle');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('An early bicycle. Paint is coming off.');
		await page.locator('input[name="de_1_new_4"]').fill('75.0'); // height
		await page.locator('input[name="de_1_new_5"]').fill('220.0'); // width
		await page.locator('input[name="de_1_new_6"]').fill('35.0'); // depth
		await page.locator('input[name="de_1_new_8"]').fill('1884'); // year
		await page.getByRole('radio', { name: 'CE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('2007-05-09');
		await page.getByRole('radio', { name: 'No' }).check(); // not donated
		await page.getByRole('slider', { name: 'Condition' }).fill('4');
		await page.getByRole('checkbox', { name: 'Modern History' }).check();
		await waitForFormulizeFormToken(page);
		await page.getByRole('link', { name: ' Save and Leave' }).click();

		// Artifact 10: Polynesian Canoe
		await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Polynesian Canoe');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A well preserved canoe used between islands.');
		await page.locator('input[name="de_1_new_4"]').fill('65.0'); // height
		await page.locator('input[name="de_1_new_5"]').fill('388.0'); // width
		await page.locator('input[name="de_1_new_6"]').fill('75.0'); // depth
		await page.locator('input[name="de_1_new_8"]').fill('1701'); // year
		await page.getByRole('radio', { name: 'CE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('1977-06-14');
		await page.getByRole('radio', { name: 'Yes' }).check(); // donated
		await page.getByLabel('Donor').selectOption('1'); // Voltaire
		await page.getByRole('slider', { name: 'Condition' }).fill('8');
		await page.getByRole('checkbox', { name: 'Modern History' }).check();
		await waitForFormulizeFormToken(page);
		await page.getByRole('link', { name: ' Save and Leave' }).click();

		// Artifact 11: Viking Silver Armband
		await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Viking Silver Armband');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A twisted silver armband with runic inscriptions, likely used as currency or status symbol in 9th century Scandinavia. Shows intricate craftsmanship with geometric patterns.');
		await page.locator('input[name="de_1_new_4"]').fill('8.5'); // height
		await page.locator('input[name="de_1_new_5"]').fill('12.0'); // width
		await page.locator('input[name="de_1_new_6"]').fill('1.2'); // depth
		await page.locator('input[name="de_1_new_8"]').fill('875'); // year
		await page.getByRole('radio', { name: 'CE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('2025-07-10');
		await page.getByRole('radio', { name: 'Yes' }).check(); // donated
		await page.getByLabel('Donor').selectOption('2'); // Emilie
		await page.getByRole('slider', { name: 'Condition' }).fill('9');
		await page.getByRole('checkbox', { name: 'Modern History' }).check();
		await waitForFormulizeFormToken(page);
		await page.getByRole('link', { name: ' Save and Leave' }).click();
		await page.getByRole('link', { name: 'Logout' }).click();

		await loginAs('curator1', page);
		await page.goto('/modules/formulize/index.php?fid=1');
		await page.locator('input[name="search_artifacts_short_name"]').fill('coin');
		await page.getByRole('link', { name: 'Short name' }).click();
		await expect(page.getByRole('row', { name: 'Check this box to select/unselect this entry.     Japanese Coin 1880CE Modern' }).getByRole('link')).toBeVisible();
await page.getByRole('row', { name: 'Check this box to select/unselect this entry.     Japanese Coin 1880CE Modern' }).getByRole('link').click();

	}),
	test('Create Exhibits', async ({ page }) => {



	}),
	test('Create Survey Entries', async ({ page }) => {



	})
});


