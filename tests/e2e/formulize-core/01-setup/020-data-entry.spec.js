const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, saveFormulizeForm, conditionalElementReady, openElementAccordion, saveAdminForm, applyColumnChanges, openAllColumnSections, ensureMainMenuOpen, leavePage, dbQuery, dbPrefix } from '../../utils';

test.describe('Validate menu entries', () => {
	test('Validate menu entries for ahstaff', async ({ page }) => {
		await login(page, 'ahstaff', '12345');
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true })).toBeVisible();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Collections', exact: true })).toBeVisible();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true })).toBeVisible();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Donors', exact: true })).toBeVisible();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Surveys', exact: true })).toBeVisible();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Artifact origins', exact: true })).toBeVisible();
	})
	test('Validate menu entries for mhstaff', async ({ page }) => {
		await login(page, 'mhstaff', '12345');
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true })).toBeVisible();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Collections', exact: true })).toBeVisible();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true })).toBeVisible();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Donors', exact: true })).toBeVisible();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Surveys', exact: true })).toBeVisible();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Artifact origins', exact: true })).toBeVisible();
	})
	test('Validate menu entries for curator1', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true })).toBeVisible();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Collections', exact: true })).toBeVisible();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true })).toBeVisible();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Donors', exact: true })).toBeVisible();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Surveys', exact: true })).toBeVisible();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Artifact origins', exact: true })).toBeVisible();
	})
})

test.describe('Data Entry for Collections', () => {
	test('Create Ancient History collection', async ({ page }) => {
		await login(page, 'ahstaff', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Collections', exact: true }).click();
		await page.getByRole('button', { name: 'Add Collections', exact: true }).click();
  	await page.getByRole('textbox', { name: 'Name *' }).fill('Ancient History');
	  await page.getByRole('checkbox', { name: 'Children' }).check();
  	await page.getByRole('checkbox', { name: 'Adults' }).check();
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create Modern History collection', async ({ page }) => {
		await login(page, 'mhstaff', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Collections', exact: true }).click();
		await page.getByRole('button', { name: 'Add Collections', exact: true }).click();
  	await page.getByRole('textbox', { name: 'Name *' }).fill('Modern History');
	  await page.getByRole('checkbox', { name: 'Children' }).check();
  	await page.getByRole('checkbox', { name: 'Adults' }).check();
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create Coins collection', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Collections', exact: true }).click();
		await page.getByRole('button', { name: 'Add Collections' }).click();
  	await page.getByRole('textbox', { name: 'Name *' }).fill('Coins');
   	await page.getByRole('checkbox', { name: 'Adults' }).check();
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create Weapons collection', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Collections', exact: true }).click();
		await page.getByRole('button', { name: 'Add Collections' }).click();
		await page.getByRole('textbox', { name: 'Name *' }).fill('Weapons');
		await page.getByRole('checkbox', { name: 'Children' }).check();
   	await page.getByRole('checkbox', { name: 'Adults' }).check();
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})
})

test.describe('Data Entry for Donors', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, 'curator1', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Donors', exact: true }).click();
		await page.getByRole('button', { name: 'Add Donor', exact: true }).click();
	})

	test('Create François-Marie donor', async ({ page }) => {
		await page.getByText('Individual').click();
		await expect(page.getByText('First name')).toBeVisible();
	  await page.getByRole('textbox', { name: 'First name' }).fill('François-Marie');
	  await page.getByRole('textbox', { name: 'Last name' }).fill('Arouet');
		await page.getByRole('textbox', { name: 'Email address' }).fill('voltaire@enlightenment.org');
	  await page.getByRole('textbox', { name: 'Favourite colour' }).fill('#6c2d2d');
  	await page.getByRole('textbox', { name: 'Phone number' }).fill('1694221778');
		await page.locator('.form-row:has(.formulize-label-donors_street_address) textarea').fill('Château de Cirey\nCirey-sur-Blaise\nFrance');
  	await page.getByRole('combobox').selectOption('Quebec');
  	await page.locator('.formulize-grid input[type="text"]').nth(0).fill('G1A 0A2');
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create Emilie donor', async ({ page }) => {
		await page.getByText('Individual').click();
	  await expect(page.getByText('First name')).toBeVisible();
	  await page.getByRole('textbox', { name: 'First name' }).fill('Emilie');
	  await page.getByRole('textbox', { name: 'Last name' }).fill('Du Châtelet');
		await page.getByRole('textbox', { name: 'Email address' }).fill('duchatelet@enlightenment.org');
	 	await page.getByRole('textbox', { name: 'Favourite colour' }).fill('#3b96c4');
  	await page.getByRole('textbox', { name: 'Phone number' }).fill('1706221749');
  	await page.locator('.form-row:has(.formulize-label-donors_street_address) textarea').fill('Château de Cirey\nCirey-sur-Blaise\nFrance');
  	await page.getByRole('combobox').selectOption('Quebec');
  	await page.locator('.formulize-grid input[type="text"]').nth(0).fill('G1A 0A2');
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create Freeform donor', async ({ page }) => {
	  await page.getByText('Organization').click();
	  await expect(page.getByText('First name')).not.toBeVisible();
		await page.getByRole('textbox', { name: 'Organization name' }).fill('Freeform Solutions');
		await page.getByRole('textbox', { name: 'Email address' }).fill('formulize@freeform.ca');
  	await page.getByRole('textbox', { name: 'Phone number' }).fill('4166863766');
  	await page.locator('.form-row:has(.formulize-label-donors_street_address) textarea').fill('17 Crossovers St\nToronto');
  	await page.getByRole('combobox').selectOption('Ontario');
  	await page.locator('.formulize-grid input[type="text"]').nth(0).fill('M4E 3X2');
  	await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

})

// Where an artifact comes from, which the Artifact origins map screen plots
async function fillOrigin(page, latitude, longitude) {
	await page.locator('.form-row:has(.formulize-label-artifacts_latitude) input[type="number"]').fill(latitude);
	await page.locator('.form-row:has(.formulize-label-artifacts_longitude) input[type="number"]').fill(longitude);
}

test.describe('Data entry for Artifacts as ahstaff', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, 'ahstaff', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true }).click();
		await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
	})

	test('Create Roman Coin', async ({ page }) => {
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A very lovely Roman Coin.');
		await page.locator('.formulize-grid').nth(1).locator('input[type="number"]').nth(0).fill('150'); // year
		await page.getByRole('radio', { name: 'BCE', exact: true }).check();
		page.once('dialog', dialog => {
			console.log(`Dialog message: ${dialog.message()}`);
			dialog.dismiss().catch(() => {});
		});
		await page.getByRole('button', { name: 'Save' }).click();
		await expect(page.locator('.form-row:has(.formulize-label-artifacts_short_name) input[type="text"]')).toBeFocused();
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Roman Coin');
		page.once('dialog', dialog => {
			console.log(`Dialog message: ${dialog.message()}`);
			dialog.dismiss().catch(() => {});
		});
		await page.getByRole('button', { name: 'Save' }).click();
		await expect(page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(0)).toBeFocused();
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(0).fill('0.2'); // height
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(1).fill('1.5'); // width
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(2).fill('1.5'); // depth
		await saveFormulizeForm(page);
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('2020-08-21');
		await page.getByRole('radio', { name: 'Yes' }).check(); // donated
		await page.getByLabel('Donor').selectOption({ label: 'François-Marie Arouet' });
		await page.getByRole('slider', { name: 'Condition' }).fill('8');
		await page.getByRole('checkbox', { name: 'Ancient History' }).check();
		await page.getByRole('checkbox', { name: 'Coins' }).check();
		await fillOrigin(page, '41.9028', '12.4964');
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create Persian necklace', async ({ page }) => {
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Persian necklace');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A beautiful Persian necklace.');
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(0).fill('1.0'); // height
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(1).fill('25.0'); // width
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(2).fill('35.0'); // depth
		await page.locator('.formulize-grid').nth(1).locator('input[type="number"]').nth(0).fill('350'); // year
		await page.getByRole('radio', { name: 'BCE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('2002-12-02');
		await page.getByRole('radio', { name: 'Yes' }).check(); // donated
		await page.getByLabel('Donor').selectOption({label: 'Emilie Du Châtelet'});
		await page.getByRole('slider', { name: 'Condition' }).fill('9');
		await page.getByRole('checkbox', { name: 'Ancient History' }).check();
		await fillOrigin(page, '29.9355', '52.8916');
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create Chinese Sword', async ({ page }) => {
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Chinese Sword');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('An engraved ceremonial sword.');
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(0).fill('135.0'); // height
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(1).fill('10.0'); // width
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(2).fill('4.5'); // depth
		await page.locator('.formulize-grid').nth(1).locator('input[type="number"]').nth(0).fill('198'); // year
		await page.getByRole('radio', { name: 'CE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('1993-08-10');
		await page.getByRole('radio', { name: 'Yes' }).check(); // donated
		await page.getByLabel('Donor').selectOption({ label: 'Freeform Solutions' });
		await page.getByRole('slider', { name: 'Condition' }).fill('7');
		await page.getByRole('checkbox', { name: 'Ancient History' }).check();
		await page.getByRole('checkbox', { name: 'Weapons' }).check();
		await fillOrigin(page, '34.3416', '108.9398');
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create Egyptian Chariot', async ({ page }) => {
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Egyptian Chariot');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('An Egyptian Chariot that is partially damaged, but overall excellent condition.');
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(0).fill('250.0'); // height
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(1).fill('200.0'); // width
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(2).fill('475.0'); // depth
		await page.locator('.formulize-grid').nth(1).locator('input[type="number"]').nth(0).fill('1850'); // year
		await page.getByRole('radio', { name: 'BCE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('2011-09-19');
		await page.getByRole('slider', { name: 'Condition' }).fill('6');
		await page.getByRole('checkbox', { name: 'Ancient History' }).check();
		await page.getByRole('checkbox', { name: 'Weapons' }).check();
		await expect(page.getByRole('radio', { name: 'No' })).toBeChecked(); // default should be selected already
		await fillOrigin(page, '25.6872', '32.6396');
		await saveFormulizeForm(page);
		await expect(page.getByRole('radio', { name: 'No' })).toBeChecked(); // default should have been saved
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create Babylonian Spoon', async ({ page }) => {
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Babylonian Spoon');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A small Babylonian spoon, probably for a child.');
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(0).fill('5.0'); // height
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(1).fill('1.5'); // width
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(2).fill('8.0'); // depth
		await page.locator('.formulize-grid').nth(1).locator('input[type="number"]').nth(0).fill('1500'); // year
		await page.getByRole('radio', { name: 'BCE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('2015-09-26');
		await page.getByRole('radio', { name: 'Yes' }).check(); // donated
		await page.getByLabel('Donor').selectOption({ label: 'François-Marie Arouet' });
		await page.getByRole('slider', { name: 'Condition' }).fill('8');
		await page.getByRole('checkbox', { name: 'Ancient History' }).check();
		await fillOrigin(page, '32.5422', '44.4209');
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})
})

test.describe('Data entry for Artifacts as mhstaff', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, 'mhstaff', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true }).click();
		await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
	})

	test('Create Florentine Book', async ({ page }) => {
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Florentine Book');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A book of Latin poetry from medieval Florence.');
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(0).fill('22.0'); // height
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(1).fill('12.0'); // width
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(2).fill('3.0'); // depth
		await page.locator('.formulize-grid').nth(1).locator('input[type="number"]').nth(0).fill('1588'); // year
		await page.getByRole('radio', { name: 'CE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('1988-02-29');
		await page.getByRole('radio', { name: 'Yes' }).check(); // donated
		await page.getByLabel('Donor').selectOption({label: 'Emilie Du Châtelet' });
		await page.getByRole('slider', { name: 'Condition' }).fill('9');
		await page.getByRole('checkbox', { name: 'Modern History' }).check();
		await fillOrigin(page, '43.7696', '11.2558');
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create French Musket', async ({ page }) => {
		await page.getByRole('textbox', { name: 'Short name *' }).fill('French Musket');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A musket from the Napoleonic wars.');
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(0).fill('150.0'); // height
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(1).fill('6.7'); // width
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(2).fill('7.1'); // depth
		await page.locator('.formulize-grid').nth(1).locator('input[type="number"]').nth(0).fill('1804'); // year
		await page.getByRole('radio', { name: 'CE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('1927-03-04');
		await page.getByRole('slider', { name: 'Condition' }).fill('7');
		await page.getByRole('checkbox', { name: 'Modern History' }).check();
		await page.getByRole('checkbox', { name: 'Weapons' }).check();
		await fillOrigin(page, '48.8566', '2.3522');
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create Japanese Coin', async ({ page }) => {
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Japanese Coin');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A rare Meiji coin.');
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(0).fill('0.2'); // height
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(1).fill('25.0'); // width
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(2).fill('25.0'); // depth
		await page.locator('.formulize-grid').nth(1).locator('input[type="number"]').nth(0).fill('1880'); // year
		await page.getByRole('radio', { name: 'CE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('1955-11-25');
		await page.getByRole('radio', { name: 'Yes' }).check(); // donated
		await page.getByLabel('Donor').selectOption({ label: 'Freeform Solutions' });
		await page.getByRole('slider', { name: 'Condition' }).fill('10');
		await page.getByRole('checkbox', { name: 'Modern History' }).check();
		await page.getByRole('checkbox', { name: 'Coins' }).check();
		await fillOrigin(page, '35.6762', '139.6503');
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create American Bicycle', async ({ page }) => {
		await page.getByRole('textbox', { name: 'Short name *' }).fill('American Bicycle');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('An early bicycle. Paint is coming off.');
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(0).fill('75.0'); // height
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(1).fill('220.0'); // width
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(2).fill('35.0'); // depth
		await page.locator('.formulize-grid').nth(1).locator('input[type="number"]').nth(0).fill('1884'); // year
		await page.getByRole('radio', { name: 'CE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('2007-05-09');
		await page.getByRole('slider', { name: 'Condition' }).fill('4');
		await page.getByRole('checkbox', { name: 'Modern History' }).check();
		await fillOrigin(page, '42.3601', '-71.0589');
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create Polynesian Canoe', async ({ page }) => {
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Polynesian Canoe');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A well preserved canoe used between islands.');
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(0).fill('65.0'); // height
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(1).fill('388.0'); // width
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(2).fill('75.0'); // depth
		await page.locator('.formulize-grid').nth(1).locator('input[type="number"]').nth(0).fill('1701'); // year
		await page.getByRole('radio', { name: 'CE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('1977-06-14');
		await page.getByRole('radio', { name: 'Yes' }).check(); // donated
		await page.getByLabel('Donor').selectOption({ label: 'François-Marie Arouet' });
		await page.getByRole('slider', { name: 'Condition' }).fill('8');
		await page.getByRole('checkbox', { name: 'Modern History' }).check();
		await fillOrigin(page, '-17.5516', '-149.5585');
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create Viking Silver Armband', async ({ page }) => {
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Viking Silver Armband');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A twisted silver armband with runic inscriptions, likely used as currency or status symbol in 9th century Scandinavia. Shows intricate craftsmanship with geometric patterns.');
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(0).fill('8.5'); // height
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(1).fill('12.0'); // width
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(2).fill('1.2'); // depth
		await page.locator('.formulize-grid').nth(1).locator('input[type="number"]').nth(0).fill('875'); // year
		await page.getByRole('radio', { name: 'CE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('2025-07-10');
		await page.getByRole('radio', { name: 'Yes' }).check(); // donated
		await page.getByLabel('Donor').selectOption({ label: 'Emilie Du Châtelet' });
		await page.getByRole('slider', { name: 'Condition' }).fill('9');
		await page.getByRole('checkbox', { name: 'Modern History' }).check();
		await fillOrigin(page, '59.8586', '17.6389');
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})
})

test('Clear data', async ({ page }) => {
	await login(page, 'curator1', '12345');
	await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
	await page.getByRole('row', { name: 'M001' }).getByRole('link').click();
	await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).click();
 	await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).press('ControlOrMeta+a');
	await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('');
	await saveFormulizeForm(page);
	await expect(page.getByRole('textbox', { name: 'Rich Text Editor, main' })).not.toHaveText('A very lovely Roman Coin.');
	await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A very lovely Roman Coin.');
	await saveFormulizeForm(page);
})

test('Test \'Close\' button functionality', async ({ page }) => {
	await login(page, 'curator1', '12345');
	await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
	await page.getByRole('row', { name: 'M001' }).getByRole('link').click();
	await expect(page.getByText('Ancient History', { exact: true })).toBeVisible();
	await page.getByRole('textbox', { name: 'Short Name' }).fill('Testdfgfdg');
	page.on('dialog', dialog => dialog.accept());
	await page.getByRole('button', { name: 'Close' }).click();
	await expect(page.getByText('Showing entries: 1 to 10 of 11.')).toBeVisible();
	await expect(page.getByText('Roman Coin')).toBeVisible();
})

test('Validate number of artifacts', async ({ page }) => {
	await login(page, 'curator1', '12345');
	await expect(page.getByText('Showing entries: 1 to 10 of 11.')).toBeVisible();
	await page.locator('input[name="search_artifacts_short_name"]').fill('coin');
	await page.getByRole('link', { name: 'Short name' }).click();
	await expect(page.getByRole('row', { name: 'Check this box to select/unselect this entry.' }).getByRole('link')).toHaveCount(2);
	await page.locator('input[name="search_artifacts_short_name"]').fill('');
	await page.locator('input[name="search_artifacts_collections"]').fill('weapons');
	await page.getByRole('link', { name: 'Short name' }).click();
	await expect(page.getByRole('row', { name: 'Check this box to select/unselect this entry.' }).getByRole('link')).toHaveCount(3);
})

test.describe('Data entry for Exhibits', () => {

	test('Create History through the Ages Exhibit', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true }).click();
		await expect(page.getByText('No entries were found in the')).toBeVisible();
		await page.getByRole('button', { name: 'Add Exhibit', exact: true }).click();
		await page.getByRole('textbox', { name: 'Name *' }).fill('History through the Ages');
		await page.getByLabel('Curator').selectOption('Curator One');
		await page.locator('input[type=text]').nth(1).fill('modern');
		// handle the conditional behaviour that happens when Modern History is selected
		await conditionalElementReady(
			page,
			'exhibits_artifacts',
			async () => {
				await page.getByText('Modern History', { exact: true }).click();
			}
		);
		// handle the conditional behaviour that happens when Ancient History is selected
		await page.locator('input[type=text]').nth(1).fill('an');
		await conditionalElementReady(
			page,
			'exhibits_artifacts',
			() => page.getByText('Ancient History', { exact: true }).click()
		);
		await page.locator('input[type=text]').nth(2).fill('roman');
  	await page.getByText('Roman Coin').click();
		await page.locator('input[type=text]').nth(2).fill('per');
		await page.getByText('Persian necklace').click();
		await page.locator('input[type=text]').nth(2).fill('chin');
		await page.getByText('Chinese Sword').click();
		await page.locator('input[type=text]').nth(2).fill('egyptian');
		await page.getByText('Egyptian Chariot').click();
		await page.locator('input[type=text]').nth(2).fill('bab');
		await page.getByText('Babylonian Spoon').click();
		await page.locator('input[type=text]').nth(2).fill('flor');
		await page.getByText('Florentine Book').click();
		await page.locator('input[type=text]').nth(2).fill('fren');
		await page.getByText('French Musket').click();
		await page.locator('input[type=text]').nth(2).fill('japa');
		await page.getByText('Japanese Coin').click();
		await page.locator('input[type=text]').nth(2).fill('amer');
		await page.getByText('American Bicycle').click();
		await page.locator('input[type=text]').nth(2).fill('poly');
		await page.getByText('Polynesian Canoe').click();
		await page.locator('input[type=text]').nth(2).fill('vik');
		await page.getByText('Viking Silver Armband').click();
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create Ancient Wonders Exhibit', async ({ page }) => {
		await login(page, 'ahstaff', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true }).click();
		await page.getByRole('button', { name: 'Add Exhibit', exact: true }).click();
		await page.getByRole('textbox', { name: 'Name *' }).fill('Ancient Wonders');
		await page.getByLabel('Curator').selectOption('Curator One');
		// handle the conditional behaviour that happens when Ancient History is selected
		await page.locator('input[type=text]').nth(1).fill('an');
		await conditionalElementReady(
			page,
			'exhibits_artifacts',
			() => page.getByText('Ancient History', { exact: true }).click()
		);
		await page.locator('input[type=text]').nth(2).fill('roman');
  	await page.getByText('Roman Coin').click();
		await page.locator('input[type=text]').nth(2).fill('per');
		await page.getByText('Persian necklace').click();
		await page.locator('input[type=text]').nth(2).fill('chin');
		await page.getByText('Chinese Sword').click();
		await page.locator('input[type=text]').nth(2).fill('egyptian');
		await page.getByText('Egyptian Chariot').click();
		await page.locator('input[type=text]').nth(2).fill('bab');
		await page.getByText('Babylonian Spoon').click();
		await page.locator('input[type=text]').nth(2).fill('M');
		await expect(page.getByText('No match found')).toBeVisible();
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create Modern Amazements Exhibit', async ({ page }) => {
		await login(page, 'mhstaff', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true }).click();
		await page.getByRole('button', { name: 'Add Exhibit', exact: true }).click();
		await page.getByRole('textbox', { name: 'Name *' }).fill('Modern Amazements');
		await page.getByLabel('Curator').selectOption('Curator One');
		// handle the conditional behaviour that happens when Modern History is selected
		await page.locator('input[type=text]').nth(1).fill('mo');
		await conditionalElementReady(
			page,
			'exhibits_artifacts',
			() => page.getByText('Modern History', { exact: true }).click()
		);
		await page.locator('input[type=text]').nth(2).fill('flor');
		await page.getByText('Florentine Book').click();
		await page.locator('input[type=text]').nth(2).fill('fren');
		await page.getByText('French Musket').click();
		await page.locator('input[type=text]').nth(2).fill('japa');
		await page.getByText('Japanese Coin').click();
		await page.locator('input[type=text]').nth(2).fill('amer');
		await page.getByText('American Bicycle').click();
		await page.locator('input[type=text]').nth(2).fill('poly');
		await page.getByText('Polynesian Canoe').click();
		await page.locator('input[type=text]').nth(2).fill('vik');
		await page.getByText('Viking Silver Armband').click();
		await page.locator('input[type=text]').nth(2).fill('M');
		await expect(page.getByText('No match found')).toBeVisible();
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create Heroic and Horrible Hand Weapons Exhibit', async ({ page }) => {
		await login(page, 'curator2', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true }).click();
		await page.getByRole('button', { name: 'Add Exhibit', exact: true }).click();
		await page.getByRole('textbox', { name: 'Name *' }).fill('Heroic and Horrible Hand Weapons');
		await page.getByLabel('Curator').selectOption('Curator Two');
		// handle the conditional behaviour that happens when Weapons is selected
		await page.locator('input[type=text]').nth(1).fill('weapons');
		await conditionalElementReady(
			page,
			'exhibits_artifacts',
			() => page.getByText('Weapons').click()
		);
		await page.locator('input[type=text]').nth(2).fill('chin');
		await page.getByText('Chinese Sword').click();
		await page.locator('input[type=text]').nth(2).fill('fren');
		await page.getByText('French Musket').click();
		await page.locator('input[type=text]').nth(2).fill('M');
		await expect(page.getByText('Egyptian Chariot')).toBeVisible();
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})

	test('Create Pennies from the Past Exhibit', async ({ page }) => {
		await login(page, 'curator2', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true }).click();
		await page.getByRole('button', { name: 'Add Exhibit', exact: true }).click();
		await page.getByRole('textbox', { name: 'Name *' }).fill('Pennies from the Past');
		await page.getByLabel('Curator').selectOption('Curator Two');
		// handle the conditional behaviour that happens when Coins is selected
		await page.locator('input[type=text]').nth(1).fill('coins');
		await conditionalElementReady(
			page,
			'exhibits_artifacts',
			() => page.getByText('Coins').click()
		);
		await page.locator('input[type=text]').nth(2).fill('roman');
  	await page.getByText('Roman Coin').click();
		await page.locator('input[type=text]').nth(2).fill('japa');
		await page.getByText('Japanese Coin').click();
		await page.locator('input[type=text]').nth(2).fill('M');
		await expect(page.getByText('No match found')).toBeVisible();
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	})
})

test.describe('Data entry for Survey', () => {
	let rewriteWorks = true;
	[
		{ name: 'Ramesses II', exhibit: 'History through the Ages', favourite: 'Egyptian Chariot', rating: 'Mind blowing!' },
		{ name: 'James Cook', exhibit: 'Modern Amazements', favourite: 'Polynesian Canoe', rating: 'Excellent' },
		{ name: 'Ebanezer Scrooge', exhibit: 'Pennies from the Past', favourite: 'Roman Coin', rating: 'Disappointing' },
		{ name: 'Napoleon Bonaparte', exhibit: 'Heroic and Horrible Hand Weapons', favourite: 'French Musket', rating: 'Good' },
		{ name: 'Machiavelli', exhibit: 'Modern Amazements', favourite: 'French Musket', rating: 'Excellent' },
		{ name: 'Darius the Great', exhibit: 'Ancient Wonders', favourite: 'Babylonian Spoon', rating: 'Excellent' }
	].forEach(({ name, exhibit, favourite, rating }) => {
		test(`Create survey for ${name}`, async ({ page }) => {
			if(rewriteWorks) {
				try {
					await page.goto('/survey');
					await page.waitForLoadState('networkidle');
					await page.waitForLoadState('domcontentloaded');
					rewriteWorks = await page.locator('.formulize-label-surveys_your_name').count() > 0;
				} catch {
					rewriteWorks = false;
				}
			}
			if(!rewriteWorks) {
				// Survey multipage screen is sid=16 after the 005 form/screen additions
				// (the 4 005 forms shifted museum screen ids up by 6; was sid=10).
				await page.goto('/modules/formulize/index.php?sid=16');
			}
			// say which way the survey was reached, so the run shows whether the .htaccess rewrite rules are in effect
			const reachedBy = rewriteWorks ? '/survey (the .htaccess rewrite rule worked)' : 'index.php?sid=16 (FALLBACK: /survey did not load the survey, so the .htaccess rewrite rule is not in effect)';
			console.log(`Survey for ${name} reached by ${reachedBy}`);
			test.info().annotations.push({ type: 'survey address', description: reachedBy });
			await page.getByRole('textbox', { name: 'Your name' }).fill(name);
			await expect(page.getByText('Thank you for visiting the Museum!')).toBeVisible();
			await expect(page.getByText('Flagged to the staff')).not.toBeVisible();
			await expect(page.getByText('Staff comments')).not.toBeVisible();
			await page.getByLabel('Which exhibit did you see?').selectOption(exhibit);
			// Wait for the favourite select to have options
			await expect(async () => {
				const optionCount = await page.getByLabel('Which was your favourite').locator('option').count();
				expect(optionCount).toBeGreaterThan(1); // More than just the "none" option
			}).toPass();
			await page.getByLabel('Which was your favourite').selectOption(favourite);
			await page.getByLabel('How would you rate the').selectOption(rating);
			await saveFormulizeForm(page);
			// After saving, the survey is drawn again with the saved entry, which locks the entry.
			// The lock is released when that page is left, so it must finish loading first:
			// leaving while it is still loading leaves the entry locked for the staff tests below.
			await expect(page.getByRole('textbox', { name: 'Your name' })).toHaveValue(name);
			await leavePage(page); // leaving the form clears the entry lock
			await expect(page.getByRole('textbox', { name: 'Your name' })).not.toBeVisible();
			})
	})
})

test.describe('Staff Comments for Surveys', () => {

		test('Staff see no comments by default' , async ({ page }) => {
			await login(page, 'mhstaff', '12345');
			await page.locator('#burger-and-logo').getByRole('link').first().click();
			await page.locator('#mainmenu').getByRole('link', { name: 'Surveys', exact: true }).click();
			await expect(page.getByText('No entries were found in the current view that match the current search terms.')).toBeVisible();
		}),
		test('Flag Ancient Wonders Survey' , async ({ page }) => {
			await login(page, 'curator1', '12345');
			await page.locator('#burger-and-logo').getByRole('link').first().click();
			await page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true }).click();
			await expect(page.getByText('Showing entries: 1 to 5')).toBeVisible();
			await page.getByRole('row', { name: 'Check this box to select/unselect this entry.   History through the Ages Curator' }).getByRole('link').click();
			await page.getByRole('radio', { name: 'Yes' }).check();
   		await page.locator('td.subform-textarea-element textarea').first().fill('Great Job -c1');
			await saveFormulizeForm(page);
			await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
		}),
		test('Flag Modern Amazements Survey' , async ({ page }) => {
			await login(page, 'curator2', '12345');
			await page.locator('#burger-and-logo').getByRole('link').first().click();
			await page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true }).click();
			await expect(page.getByText('Showing entries: 1 to 5')).toBeVisible();
			await page.getByRole('row', { name: 'Check this box to select/unselect this entry.   Pennies from the Past Curator' }).getByRole('link').click();
			await page.getByRole('radio', { name: 'Yes' }).check();
   		await page.locator('td.subform-textarea-element textarea').first().fill('Can we do better? -c2');
			await saveFormulizeForm(page);
			await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks

		}),
		test('AH Staff Comment' , async ({ page }) => {
			await login(page, 'ahstaff', '12345');
			await page.locator('#burger-and-logo').getByRole('link').first().click();
			await page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true }).click();
			await expect(page.getByText('Showing entries: 1 to 4')).toBeVisible();
			await page.getByRole('row', { name: 'Check this box to select/unselect this entry.   History through the Ages Curator' }).getByRole('link').click();
			const currentValue = await page.locator('td.subform-textarea-element textarea').first().inputValue();
			await page.locator('td.subform-textarea-element textarea').first().fill(currentValue + "\nThanks! -ahstaff");
			await expect(page.getByText('Flagged')).not.toBeVisible();
			await saveFormulizeForm(page);
			await expect(page.getByText('Great Job -c1 Thanks! -ahstaff')).toHaveValue('Great Job -c1\nThanks! -ahstaff');
			await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
		}),
		test('MH Staff Comment' , async ({ page }) => {
			await login(page, 'mhstaff', '12345');
			await page.locator('#burger-and-logo').getByRole('link').first().click();
			await page.locator('#mainmenu').getByRole('link', { name: 'Surveys', exact: true }).click();
			await expect(page.getByText('Showing entries: 1 to 2')).toBeVisible();
			await page.getByRole('row', { name: 'Ebanezer Scrooge' }).getByRole('link').click();
			await expect(page.getByText('Which was your favourite artifact?')).toBeVisible();
			const currentValue = await page.locator('.form-row:has(.formulize-label-surveys_staff_comments) textarea').inputValue();
			await page.locator('.form-row:has(.formulize-label-surveys_staff_comments) textarea').fill(currentValue + "\nBah Humbug -mhstaff");
			await expect(page.getByText('Flagged to the staff')).not.toBeVisible();
			await saveFormulizeForm(page);
			await expect(page.getByText('Can we do better? -c2 Bah Humbug -mhstaff')).toHaveValue('Can we do better? -c2\nBah Humbug -mhstaff');
			await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
		})

})

test.describe('New donation inside Donor record', () => {
	test('Create donation from Voltaire', async ({ page }) => {
		await login(page, 'mhstaff', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Donors', exact: true }).click();
		await page.getByRole('row', { name: 'François-Marie Arouet' }).getByRole('link').first().click();
	  await page.getByRole('link', { name: 'Donated Artifacts', exact: true }).click();
		await page.getByRole('button', { name: 'Add new artifact' }).click();
		await page.getByRole('tab').first().click(); // BUG - new entry showing up at top of list, sorting order is by aquisition date, no acquisition date yet so this is first in list, and by default UI opens the last entry in list because when sorted by creation order (which is default) the last entry in list would be the new one. Yuck.
		await page.getByRole('textbox', { name: 'Short name *' }).fill('Candide - first edition');
		await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('Pristine copy, signed by the author');
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(0).fill('25'); // height
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(1).fill('10'); // width
		await page.locator('.formulize-grid').nth(0).locator('input[type="number"]').nth(2).fill('3'); // depth
		await page.locator('.formulize-grid').nth(1).locator('input[type="number"]').nth(0).fill('1759'); // year
		await page.getByRole('radio', { name: 'CE', exact: true }).check();
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('2025-11-29');
		await page.getByRole('slider', { name: 'Condition' }).fill('10');
		await page.getByRole('checkbox', { name: 'Modern History' }).check();
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // necessary to clear entry locks
	}),
	test('Verify artifact shows as from Voltaire', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true }).click();
		await page.locator('input[name="search_artifacts_short_name"]').fill('Candide');
		await page.getByRole('link', { name: 'Short name' }).click();
		await page.getByRole('row', { name: 'Candide - first edition' }).getByRole('link').click();
		await expect(page.getByRole('radio', { name: 'Yes' })).toBeChecked();
		await expect(page.locator('.form-row:has(.formulize-label-artifacts_donor) select option[selected]')).toContainText('François-Marie Arouet');
		// leave the entry, which releases the lock on it
		await page.waitForLoadState('load');
		await ensureMainMenuOpen(page);
		await page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true }).click();
		await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
	}),
	test('Verify Add button limitation setting works as expected', async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.getByRole('link', { name: 'Admin' }).click();
		await page.getByRole('link', { name: 'Museum' }).click();
	  await page.getByRole('link', { name: 'Elements' }).nth(2).click();
		await openElementAccordion(page, 'Donated artifacts');
		await page.getByRole('link', { name: 'Configure' }).click();
		await page.getByRole('link', { name: 'Options' }).click();
		await page.locator('[id="elements-ele_value[addButtonLimit]"]').fill('1');
		await saveAdminForm(page);
		await page.goto('/');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Donors', exact: true }).click();
		await page.getByRole('row', { name: 'François-Marie Arouet' }).getByRole('link').first().click();
	  await page.getByRole('link', { name: 'Donated Artifacts', exact: true }).click();
		await expect(page.getByText('Candide')).toBeVisible(); // check for something that is in the page, to make sure that the page has actually loaded... the not.toBeVisible() check below is not sufficient, because it will pass if the page hasn't totally finished loading yet and it's not visible just because of timing issues
		await expect(page.getByText('Add new artifact')).not.toBeVisible();
		// The subform draws the donor's artifacts as forms the webmaster can edit, which locks them, Roman Coin among them.
		// Leaving the page releases them, so let it finish loading first, and then leave.
		await page.waitForLoadState('load');
		await page.getByRole('link', { name: 'Admin' }).click();
		await page.getByRole('link', { name: 'Museum' }).click();
	  await page.getByRole('link', { name: 'Elements' }).nth(2).click();
		await openElementAccordion(page, 'Donated artifacts');
		await page.getByRole('link', { name: 'Configure' }).click();
		await page.getByRole('link', { name: 'Options' }).click();
		await page.locator('[id="elements-ele_value[addButtonLimit]"]').fill('0');
		await saveAdminForm(page);
		await page.goto('/');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Donors', exact: true }).click();
		await page.getByRole('row', { name: 'François-Marie Arouet' }).getByRole('link').first().click();
	  await page.getByRole('link', { name: 'Donated Artifacts', exact: true }).click();
		await expect(page.getByText('Add new artifact')).toBeVisible();
		// leave, as above, or 045 finds Roman Coin locked by the webmaster
		await page.waitForLoadState('load');
		await ensureMainMenuOpen(page);
		await page.locator('#mainmenu').getByRole('link', { name: 'Donors', exact: true }).click();
		await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
	})
})

test.describe('Cloning entries', () => {
	test('Clone Persian necklace', async ({ page }) => {
		await login(page, 'ahstaff', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true }).click();
		await page.locator('input[name="search_artifacts_short_name"]').fill('Persian necklace');
		await page.getByRole('link', { name: 'Short name' }).click();
		await expect(page.getByText('1 to 1 of 1.')).toBeVisible();
  	await page.locator('[id^=delete_]').first().check();
		await expect(page.getByText('Clone selected')).toBeVisible();
		await page.getByRole('button', { name: 'Clone selected' }).click();
		await page.locator('#formulize_clone_dialog_overlay').waitFor();
		await page.locator('.clone_form_checkbox').first().check();
		await page.getByRole('button', { name: 'Clone', exact: true }).click();
		await expect(page.getByText('1 to 2 of 2.')).toBeVisible();
		await expect(page.getByText('M013')).toBeVisible();
		await page.getByRole('row', { name: 'Check this box to select/unselect this entry.   M013 Persian necklace' }).getByRole('link').click();
	  await page.getByRole('textbox', { name: 'Short name *' }).fill('Persian necklace - number 2');
	  await page.getByRole('textbox', { name: 'Rich Text Editor, main' }).fill('A beautiful Persian necklace. Very similar to the original one in the collection.');
  	await saveFormulizeForm(page);
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Donors', exact: true }).click();
		await expect(page.getByText('1 to 3 of 3.')).toBeVisible();
	})
})

test.describe('Change entry owner', () => {
	test('Make Freeform Solutions owned by curator2', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Donors', exact: true }).click();
		await expect(page.getByText('Change columns')).toBeVisible();
		const popupPromise = page.context().waitForEvent('page');
		await page.getByRole('button', { name: 'Change columns' }).click();
		const page2 = await popupPromise;
		await page2.bringToFront();
		await openAllColumnSections(page2);
		await page2.getByRole('checkbox', { name: 'User who made entry' }).check();
		await applyColumnChanges(page2);
		await expect(page.getByText('Curator One')).not.toHaveCount(0);
		await expect(page.getByText('Curator Two')).not.toBeVisible();
		await page.locator('input[name="search_donors_name"]').fill('Freeform Solutions');
		await page.getByRole('link', { name: 'Name' }).click();
		await expect(page.getByText('1 to 1 of 1.')).toBeVisible();
  	await page.locator('[id^=delete_]').first().check();
		await expect(page.getByText('Change owner')).toBeVisible();
		await page.getByRole('button', { name: 'Change owner' }).click();
		await page.locator('#formulize_changeowner_dialog_overlay').waitFor();
		await page.locator('.changeowner_form_checkbox').first().check();
		await page.locator('select[id=changeowner_selected_uid]').selectOption({ label: 'Curator Two' });
		await page.getByRole('button', { name: 'Confirm', exact: true }).click();
		await expect(page.getByText('Curator Two')).toBeVisible();
		await expect(page.getByText('Curator One')).not.toBeVisible();
		await expect(page.getByText('1 to 1 of 1.')).toBeVisible();
	})
})


// Save the list as it is as a new view, the way a person does: "Save view" opens a window that asks for a name in a
// dialog, then closes itself and has the list save the view.
async function saveCurrentViewAs(page, name) {
	await page.getByRole('button', { name: '...' }).click();
	const popupPromise = page.context().waitForEvent('page');
	await page.getByRole('button', { name: 'Save view' }).click();
	const popup = await popupPromise;
	await popup.waitForLoadState();
	popup.once('dialog', dialog => dialog.accept(name));
	const closed = popup.waitForEvent('close');
	closed.catch(() => {});
	try {
		await popup.getByRole('button', { name: 'Save the current view settings with these options' }).click({ noWaitAfter: true });
	} catch (error) {
		if (!popup.isClosed()) throw error;
	}
	await closed;
	// the view is saved when the list reloads, which it then shows the view in
	await expect(page.locator('select[name="currentview"] option:checked')).toHaveText(new RegExp(name));
}

test.describe('Saved views', () => {
	// Views a curator keeps, which name elements in their columns, searches and sorting. 03-validate-sequential
	// renames those elements and checks the views still load.

	test('Save a view of the coins, by name', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
		await page.locator('input[name="search_artifacts_short_name"]').fill('coin');
		await page.getByRole('link', { name: 'Short name' }).click();
		await expect(page.getByRole('row', { name: 'Check this box to select/unselect this entry.' })).toHaveCount(2);
		await saveCurrentViewAs(page, 'Coins by name');
		const view = dbQuery(`SELECT sv_quicksearches, sv_sort FROM ${dbPrefix()}_formulize_saved_views WHERE sv_name = 'Coins by name'`);
		expect(view).toHaveLength(1);
		expect(view[0][0]).toContain('coin');
		const shortNameId = dbQuery(`SELECT ele_id FROM ${dbPrefix()}_formulize WHERE ele_handle = 'artifacts_short_name'`)[0][0];
		expect(view[0][1]).toBe(shortNameId); // saved views name their elements by id
	})

	test('Save a view of where the artifacts come from', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
		const popupPromise = page.context().waitForEvent('page');
		await page.getByRole('button', { name: 'Change columns' }).click();
		const popup = await popupPromise;
		await popup.bringToFront();
		await openAllColumnSections(popup);
		await popup.getByRole('group', { name: 'Artifacts', exact: true }).getByLabel('Latitude', { exact: true }).check();
		await popup.getByRole('group', { name: 'Artifacts', exact: true }).getByLabel('Longitude', { exact: true }).check();
		await applyColumnChanges(popup);
		await expect(page.getByRole('link', { name: 'Latitude' })).toBeVisible();
		await saveCurrentViewAs(page, 'Artifact origins list');
		const columns = dbQuery(`SELECT sv_oldcols FROM ${dbPrefix()}_formulize_saved_views WHERE sv_name = 'Artifact origins list'`)[0][0];
		const latitudeId = dbQuery(`SELECT ele_id FROM ${dbPrefix()}_formulize WHERE ele_handle = 'artifacts_latitude'`)[0][0];
		expect(columns.split(',')).toContain(latitudeId);
	})
})

test.describe('Notifications', () => {

	test('Notify the curators when a well kept artifact is updated', async ({ page }) => {
		// The subject names the artifact by a tag, and the condition names Condition by its id. 03-validate-sequential
		// renames those elements and checks the notification follows.
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		const fid = dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title = 'Artifacts'`)[0][0];
		await page.goto(`/modules/formulize/index.php?fid=${fid}`);
		await page.getByRole('button', { name: '...' }).click();
		const popupPromise = page.context().waitForEvent('page');
		await page.getByRole('button', { name: 'Notifications' }).click();
		const popup = await popupPromise;
		await popup.waitForLoadState();
		await popup.locator('input[name="setwhen"][value="update_entry"]').check();
		await popup.locator('input[name="setwho"][value="groupid"]').check();
		await popup.locator('#gid').selectOption({ label: 'Departments - Curators' });
		await popup.locator('input[name="setfor"][value="con"]').check();
		await popup.locator('#new_element').selectOption({ label: 'Condition' });
		await popup.locator('#new_op').selectOption('>=');
		await popup.locator('#new_term').fill('8');
		// adding the condition sends the window back to itself with the choices made so far
		await Promise.all([popup.waitForEvent('load'), popup.getByRole('button', { name: 'Add another condition' }).click()]);
		await expect(popup.getByText('Condition >= 8')).toBeVisible();
		await popup.locator('input[name="setwhen"][value="update_entry"]').check();
		await popup.locator('input[name="setwho"][value="groupid"]').check();
		await popup.locator('#gid').selectOption({ label: 'Departments - Curators' });
		await popup.locator('input[name="setfor"][value="con"]').check();
		await popup.locator('#subject').fill('Artifact updated: {ELEMENT_ARTIFACTS_SHORT_NAME}');
		await Promise.all([popup.waitForEvent('load'), popup.getByRole('button', { name: 'Save this notification' }).click()]);
		// Verify what is stored: the tag in the subject, and the condition on Condition by its id
		const conditionId = dbQuery(`SELECT ele_id FROM ${dbPrefix()}_formulize WHERE ele_handle = 'artifacts_condition'`)[0][0];
		const notification = dbQuery(`SELECT not_cons_subject, not_cons_con FROM ${dbPrefix()}_formulize_notification_conditions WHERE not_cons_fid = ${fid}`);
		expect(notification).toHaveLength(1);
		expect(notification[0][0]).toBe('Artifact updated: {ELEMENT_ARTIFACTS_SHORT_NAME}');
		expect(notification[0][1]).toContain(`"${conditionId}"`);
		expect(notification[0][1]).toContain('"8"');
	})
})
