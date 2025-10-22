const { test, expect } = require('@playwright/test')
import { login } from '../utils';

test.describe('Validate Data', () => {
	test('Check the Romain Coin record is complete', async ({ page }) => {
		await login(page, 'curator1', '12345');
		const popupPromise = page.context().waitForEvent('page');
		await page.getByRole('button', { name: 'Change columns' }).click();
		const page3 = await popupPromise;
		await page3.bringToFront();

  	await page3.getByText('Entry ID').click();
  	await page3.getByRole('checkbox', { name: 'Date of acquisition' }).check();
  	await page3.getByRole('checkbox', { name: 'User who made entry' }).click();
  	await page3.getByRole('checkbox', { name: 'Creator\'s groups' }).check();
  	await page3.getByText('User who last modified entry').click();
	  await page3.getByRole('checkbox', { name: 'Creation date' }).click();
  	await page3.getByRole('checkbox', { name: 'Last modification date' }).check();
  	await page3.getByRole('checkbox', { name: 'Creator\'s e-mail address' }).check();
  	await page3.getByRole('checkbox', { name: 'Full description' }).check();
		await page3.getByRole('checkbox', { name: 'Height' }).check();
		await page3.getByRole('checkbox', { name: 'Width' }).check();
		await page3.getByRole('checkbox', { name: 'Depth' }).check();
		await page3.getByRole('checkbox', { name: 'Year', exact: true }).check();
		await page3.getByRole('checkbox', { name: 'Era', exact: true }).check();
		await page3.getByRole('checkbox', { name: 'Condition' }).check();
		await page3.getByRole('checkbox', { name: 'Donor', exact: true }).check();
		await page3.getByRole('checkbox', { name: 'Donated to museum' }).check();
		await page3.getByRole('checkbox', { name: 'Type of donor' }).check();
		await page3.getByRole('checkbox', { name: 'First name' }).check();
		await page3.getByRole('checkbox', { name: 'Last name' }).check();
		await page3.getByRole('checkbox', { name: 'Organization name' }).check();
		await page3.getByRole('group', { name: 'Donors up arrow' }).getByLabel('Name', { exact: true }).check();
		await page3.getByRole('checkbox', { name: 'Phone number' }).check();
		await page3.getByRole('checkbox', { name: 'Email address' }).check();
		await page3.getByRole('checkbox', { name: 'Street address' }).check();
		await page3.getByRole('checkbox', { name: 'Province' }).check();
		await page3.getByRole('checkbox', { name: 'Postal code' }).check();
		await page3.getByRole('checkbox', { name: 'Favourite colour' }).check();
		await page3.getByRole('group', { name: 'Collections up arrow' }).getByLabel('Name').check();
		await page3.getByRole('checkbox', { name: 'Backgrounder / Resume' }).check();
		await page3.getByRole('checkbox', { name: 'Suitable audience' }).check();
		await page3.getByRole('group', { name: 'Exhibits up arrow' }).getByLabel('Name').check();
		await page3.getByRole('checkbox', { name: 'Curator' }).check();
		await page3.getByRole('group', { name: 'Exhibits up arrow' }).getByLabel('Collections').check();
		await page3.getByRole('checkbox', { name: 'Artifacts' }).check();
		await page3.getByRole('checkbox', { name: 'Respondent name' }).check();
		await page3.getByRole('checkbox', { name: 'Exhibit' }).check();
		await page3.getByRole('checkbox', { name: 'Favourite artifact' }).check();
		await page3.getByRole('checkbox', { name: 'Rating' }).check();
		await page3.getByRole('button', { name: 'Change columns' }).click();

		await expect(page.locator('#celladdress_2_0')).toContainText('1');
		await expect(page.locator('#celladdress_2_1')).toContainText('Ancient History Staff');
		await expect(page.locator('#celladdress_2_2')).toContainText('Ancient History Staff');
		await expect(page.locator('#celladdress_2_6')).toContainText('Ancient History');
		await expect(page.locator('#celladdress_2_7')).toContainText('M001');
		await expect(page.locator('#celladdress_2_8')).toContainText('Roman Coin');
		await expect(page.locator('#celladdress_2_9')).toContainText('A very lovely Roman Coin.');
		await expect(page.locator('#celladdress_2_10')).toContainText('0.2');
		await expect(page.locator('#celladdress_2_11')).toContainText('1.5');
		await expect(page.locator('#celladdress_2_12')).toContainText('1.5');
		await expect(page.locator('#celladdress_2_13')).toContainText('150');
		await expect(page.locator('#celladdress_2_14')).toContainText('BCE');
		await expect(page.locator('#celladdress_2_15')).toContainText('150BCE');
		await expect(page.locator('#celladdress_2_16')).toContainText('2020-08-21');
		await expect(page.locator('#celladdress_2_17')).toContainText('Yes');
		await expect(page.locator('#celladdress_2_18')).toContainText('François-Marie Arouet');
		await expect(page.locator('#celladdress_2_19')).toContainText('8');
		await expect(page.locator('#celladdress_2_20')).toContainText('Ancient HistoryCoins');
		await expect(page.locator('#celladdress_2_21')).toContainText('Individual');
		await expect(page.locator('#celladdress_2_22')).toContainText('François-Marie');
		await expect(page.locator('#celladdress_2_23')).toContainText('Arouet');
		await expect(page.locator('#celladdress_2_24')).toContainText('');
		await expect(page.locator('#celladdress_2_25')).toContainText('François-Marie Arouet');
		await expect(page.locator('#celladdress_2_26')).toContainText('169-422-1778');
		await expect(page.locator('#celladdress_2_27')).toContainText('voltaire@enlightenment.org');
		await expect(page.locator('#celladdress_2_28')).toContainText('Château de...');
		await expect(page.locator('#celladdress_2_29')).toContainText('Quebec');
		await expect(page.locator('#celladdress_2_30')).toContainText('G1A 0A2');
		await expect(page.locator('#celladdress_2_31')).toContainText('#6c2d2d');
		await expect(page.locator('#celladdress_2_32')).toContainText('');
		await expect(page.locator('#celladdress_2_33')).toContainText('Ancient HistoryCoins');
		await expect(page.locator('#celladdress_2_34')).toContainText('ChildrenAdultsAdults');
		await expect(page.locator('#celladdress_2_35')).toContainText('History through the AgesAncient WondersPennies from the Past');
		await expect(page.locator('#celladdress_2_36')).toContainText('Curator OneCurator OneCurator Two');
		await expect(page.locator('#celladdress_2_37')).toContainText('Modern HistoryAncient HistoryAncient HistoryCoins');
		await expect(page.locator('#celladdress_2_38')).toContainText('M001 - Roman CoinM002 - Persian necklaceM003 - Chinese SwordM004 - Egyptian ChariotM005 - Babylonian SpoonM006 - Florentine BookM007 - French MusketM008 - Japanese CoinM009 - American BicycleM010 - Polynesian CanoeM011 - Viking Silver ArmbandM001 - Roman CoinM002 - Persian necklaceM003 - Chinese SwordM004 - Egyptian ChariotM005 - Babylonian SpoonM001 - Roman CoinM008 - Japanese Coin');
		await expect(page.locator('#celladdress_2_39')).toContainText('Ebanezer Scrooge');
		await expect(page.locator('#celladdress_2_40')).toContainText('Pennies from the Past');
		await expect(page.locator('#celladdress_2_41')).toContainText('M001 - Roman Coin');
		await expect(page.locator('#celladdress_2_42')).toContainText('Disappointing');

	}),
	test('Search for specific collection data', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await page.goto('/modules/formulize/index.php?fid=1');
		const popupPromise = page.context().waitForEvent('page');
		await page.getByRole('button', { name: 'Change columns' }).click();
		const page3 = await popupPromise;
		await page3.bringToFront();

  	await page3.getByRole('checkbox', { name: 'Full description' }).check();
		await page3.getByRole('checkbox', { name: 'Width' }).check();
		await page3.getByRole('group', { name: 'Collections up arrow' }).getByLabel('Name').check();
		await page3.getByRole('button', { name: 'Change columns' }).click();

		await page.locator('input[name="search_artifacts_full_description"]').fill('canoe');
		await page.getByRole('link', { name: 'Full description' }).click();
		await expect(page.locator('#celladdress_2_2')).toContainText('A well preserved canoe used between islands.');
		await expect(page.getByText('Showing entries: 1 to 1 of')).toBeVisible();
		await page.locator('input[name="search_artifacts_full_description"]').fill('');
		await page.locator('input[name="search_collections_name"]').fill('weapons');
		await page.getByRole('link', { name: 'Collections: Name' }).click();
		await expect(page.getByText('Showing entries: 1 to 3 of')).toBeVisible();
		await expect(page.locator('#celladdress_2_2')).toContainText('An engraved ceremonial sword.');
		await expect(page.locator('#celladdress_3_2')).toContainText('An Egyptian Chariot that is partially damaged, but overall excellent condition.');
		await expect(page.locator('#celladdress_4_2')).toContainText('A musket from the Napoleonic wars.');
		await page.locator('input[name="search_artifacts_width"]').fill('>6 AND <7');
		await page.getByRole('link', { name: 'Width' }).click();
		await expect(page.getByText('Showing entries: 1 to 1 of')).toBeVisible();
	})

	test('Search for specific artifact data as ahstaff', async ({ page }) => {
		await login(page, 'ahstaff', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true }).click();
		await page.locator('input[name="search_exhibits_artifacts"]').fill('x');
		await page.getByRole('columnheader', { name: 'Artifacts' }).getByRole('link').click();
		await expect(page.getByText('No entries were found in the')).toBeVisible();
		await page.locator('input[name="search_exhibits_artifacts"]').fill('');
		await page.getByRole('columnheader', { name: 'Artifacts' }).getByRole('link').click();
		await expect(page.getByText('Showing entries: 1 to 4 of')).toBeVisible();
		await page.locator('input[name="search_exhibits_artifacts"]').fill('11');
		await page.getByRole('columnheader', { name: 'Artifacts' }).getByRole('link').click();
		await expect(page.getByText('Showing entries: 1 to 1 of')).toBeVisible();
	})
	test('Search for specific artifact data as mhstaff', async ({ page }) => {
		await login(page, 'mhstaff', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true }).click();
		await page.locator('input[name="search_exhibits_artifacts"]').fill('11');
		await page.getByRole('columnheader', { name: 'Artifacts' }).getByRole('link').click();
		await expect(page.getByText('Showing entries: 1 to 2 of')).toBeVisible();
	}),
	test('Check that we can get to page 2 of entries', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true }).click();
		await page.getByRole('link', { name: '2', exact: true }).click();
		await expect(page.getByText('Showing entries: 11 to')).toBeVisible();
	})
});
