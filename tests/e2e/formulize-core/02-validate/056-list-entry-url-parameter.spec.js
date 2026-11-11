const { test, expect } = require('@playwright/test');
import { login, dbQuery, dbPrefix } from '../../utils';
import { formId } from '../01-setup/museum-forms';

// ?le=N in a list's address shows only entry N of the list's form, rather than opening it the way ?ve=N does.
//
//  - It works at the index.php address and at the screen's alternate address, which passes its query string on.
//  - Searching and sorting the list keep it, since the list posts back to the address it was opened at.
//  - It can't show an entry the user may not see: ahstaff sees only Ancient History artifacts, so asking for a
//    Modern History one gives an empty list.
//  - An id that isn't a number, or isn't above 0, is ignored.
//
// Uses the museum's Artifacts list and only reads it, so it opens no entries and leaves no locks. The museum's
// data is listed at the end of formulize-core/conditions-and-searches.md.

let listSid = 0;
let romanCoinId = 0;
let florentineBookId = 0;

test.beforeAll(() => {
	listSid = dbQuery(`SELECT defaultlist FROM ${dbPrefix()}_formulize_id WHERE id_form = ${formId('Artifacts')}`)[0][0];
	romanCoinId = dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_artifacts WHERE artifacts_short_name = 'Roman Coin'`)[0][0];
	florentineBookId = dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_artifacts WHERE artifacts_short_name = 'Florentine Book'`)[0][0];
});

async function openList(page, address) {
	await page.goto(address);
	// a list drawn on the server shows no working message, so wait for its footer
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible({ timeout: 30000 });
}

const artifactRow = (page, name) => page.getByRole('row', { name: new RegExp(name) });

test.describe('The le parameter in a list\'s address', () => {

	test('shows only that entry at the index.php address', async ({ page }) => {
		await login(page, 'curator1');
		await openList(page, `/modules/formulize/index.php?sid=${listSid}&le=${romanCoinId}`);
		await expect(page.getByText('Showing entries: 1 to 1 of 1.')).toBeVisible();
		await expect(artifactRow(page, 'Roman Coin')).toBeVisible();
		await expect(page.getByText('Florentine Book', { exact: true })).toHaveCount(0);
	});

	test('shows only that entry at the screen\'s alternate address', async ({ page }) => {
		await login(page, 'curator1');
		await openList(page, `/${listSid}/?le=${florentineBookId}`);
		await expect(page.getByText('Showing entries: 1 to 1 of 1.')).toBeVisible();
		await expect(artifactRow(page, 'Florentine Book')).toBeVisible();
		await expect(page.getByText('Roman Coin', { exact: true })).toHaveCount(0);
	});

	test('searching and sorting the list keep it to that entry', async ({ page }) => {
		await login(page, 'curator1');
		await openList(page, `/modules/formulize/index.php?sid=${listSid}&le=${romanCoinId}`);
		// two artifacts match "coin", and only the one asked for is shown. Clicking the heading sorts, and submits the search
		await page.locator('input[name="search_artifacts_short_name"]').fill('coin');
		await page.getByRole('link', { name: 'Short name' }).click();
		await expect(page.getByText('Showing entries: 1 to 1 of 1.')).toBeVisible();
		await expect(artifactRow(page, 'Roman Coin')).toBeVisible();
		// a search the entry doesn't match leaves nothing, rather than showing other entries
		await page.locator('input[name="search_artifacts_short_name"]').fill('Florentine');
		await page.getByRole('link', { name: 'Short name' }).click();
		await expect(page.getByText('Showing entries: 0 to 0 of 0.')).toBeVisible();
		await expect(page.getByText('Florentine Book', { exact: true })).toHaveCount(0);
	});

	test('an entry the user may not see is not shown', async ({ page }) => {
		await login(page, 'ahstaff');
		await openList(page, `/modules/formulize/index.php?sid=${listSid}&le=${florentineBookId}`);
		await expect(page.getByText('Showing entries: 0 to 0 of 0.')).toBeVisible();
		await expect(page.getByText('Florentine Book', { exact: true })).toHaveCount(0);
		// and one they may see is
		await openList(page, `/modules/formulize/index.php?sid=${listSid}&le=${romanCoinId}`);
		await expect(page.getByText('Showing entries: 1 to 1 of 1.')).toBeVisible();
		await expect(artifactRow(page, 'Roman Coin')).toBeVisible();
	});

	test('an entry that isn\'t there gives an empty list, and an id that isn\'t one is ignored', async ({ page }) => {
		await login(page, 'curator1');
		await openList(page, `/modules/formulize/index.php?sid=${listSid}&le=999999999`);
		await expect(page.getByText('Showing entries: 0 to 0 of 0.')).toBeVisible();
		for (const value of ['0', '-5', 'abc']) {
			await openList(page, `/modules/formulize/index.php?sid=${listSid}&le=${value}`);
			await expect(page.getByText(/of 16\./)).toBeVisible();
		}
	});
});
