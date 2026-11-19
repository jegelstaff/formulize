const { test, expect } = require('@playwright/test');
import { login, leavePage, elementRow, autocompleteBox, openElementSettings } from '../../utils';

// A linked element's scope: "limit them to values from the groups selected here", with "Only use groups that the current
// user is a member of".
//
// The Collections lists in Artifacts (checkboxes) and Exhibits (an autocomplete) are scoped to Departments - All Users, a
// template group, with that option on (01-setup/010). So a person is offered the collections made by someone in their own
// department. ahstaff made Ancient History, mhstaff made Modern History, and curator1, who is in both departments, made
// Coins and Weapons (01-setup/020). So:
//
//   ahstaff:  Ancient History, Coins, Weapons
//   mhstaff:  Modern History, Coins, Weapons
//   curator1: all four
//
// Nothing here is saved, so the tests can run alongside each other and the rest of 02-validate.

const ALL_COLLECTIONS = ['Ancient History', 'Modern History', 'Coins', 'Weapons'];

// Open a new artifact, and check which collections its Collections boxes offer
async function expectNewArtifactToOffer(page, user, offered) {
	await login(page, user);
	await page.locator('#burger-and-logo').getByRole('link').first().click();
	await page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true }).click();
	await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
	const collections = elementRow(page, 'artifacts_collections');
	await expect(collections.getByRole('checkbox', { name: offered[0], exact: true })).toBeVisible();
	for (const name of ALL_COLLECTIONS) {
		await expect(collections.getByRole('checkbox', { name, exact: true }), `${user} ${offered.includes(name) ? 'is' : 'is not'} offered ${name}`)
			.toHaveCount(offered.includes(name) ? 1 : 0);
	}
	await leaveWithoutSaving(page);
}

async function leaveWithoutSaving(page) {
	const agree = dialog => dialog.accept();
	page.on('dialog', agree);
	await leavePage(page, '/modules/formulize/');
	page.off('dialog', agree);
}

test('the Collections lists show Departments - All Users as their scope', async ({ page }) => {
	await login(page, 'admin');
	for (const handle of ['artifacts_collections', 'exhibits_collections']) {
		await openElementSettings(page, handle, 'Options');
		await expect(page.locator('#element-formlink_scope option:checked'), handle).toHaveText(['Departments - All Users']);
	}
});

test('Ancient History staff are offered their department\'s collections and the curators\', not Modern History\'s', async ({ page }) => {
	await expectNewArtifactToOffer(page, 'ahstaff', ['Ancient History', 'Coins', 'Weapons']);
});

test('Modern History staff are offered their department\'s collections and the curators\', not Ancient History\'s', async ({ page }) => {
	await expectNewArtifactToOffer(page, 'mhstaff', ['Modern History', 'Coins', 'Weapons']);
});

test('a curator in both departments is offered every collection', async ({ page }) => {
	await expectNewArtifactToOffer(page, 'curator1', ALL_COLLECTIONS);
});

test('the Exhibits collections autocomplete is limited the same way', async ({ page }) => {
	await login(page, 'ahstaff');
	await page.locator('#burger-and-logo').getByRole('link').first().click();
	await page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true }).click();
	await page.getByRole('button', { name: 'Add Exhibit', exact: true }).click();
	const box = autocompleteBox(page, 'exhibits_collections');
	await box.fill('co');
	await expect(page.getByText('Coins', { exact: true })).toBeVisible();
	await box.fill('mo');
	await expect(page.getByText('No match found')).toBeVisible();
	await expect(page.getByText('Modern History', { exact: true })).toHaveCount(0);
	await leaveWithoutSaving(page);
});
