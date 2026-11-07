const { expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';
import { login, saveFormulizeForm, dbQuery, dbPrefix, leavePage } from '../../utils';

/**
 * What a person does with the museum system to see that it works, for the specs that change something the system
 * depends on: a rename in 010, and an update of an old site in 030.
 */

export function rowOf(table, keyColumn, key, columns) {
	return dbQuery(`SELECT ${columns} FROM ${dbPrefix()}_${table} WHERE ${keyColumn} = ${key}`)[0];
}

export function formIdOf(title) {
	return dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title = '${title}'`)[0][0];
}

export async function asAdmin(page) {
	await page.goto('/user.php?op=logout');
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
}

export async function asCurator(page) {
	await page.goto('/user.php?op=logout');
	await login(page, 'curator1', '12345');
}

// Open an artifact from the Artifacts list, as curator1 sees it
export async function openArtifact(page, idNumber) {
	await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
	await page.getByRole('row', { name: idNumber }).getByRole('link').first().click();
	await expect(page.getByRole('button', { name: 'Save', exact: true })).toBeVisible();
}

// Change the Roman coin's year and back, as curator1, and check Year-Era follows each time
export async function expectYearEraToFollowTheYear(page) {
	await asCurator(page);
	await openArtifact(page, 'M001');
	const year = page.locator('.formulize-grid').nth(1).locator('input[type="number"]').nth(0);
	await year.fill('151');
	await saveFormulizeForm(page);
	await expect.poll(() => rowOf('formulize_artifacts', 'entry_id', 1, 'artifacts_year_era')[0]).toBe('151BCE');
	await year.fill('150');
	await saveFormulizeForm(page);
	await expect.poll(() => rowOf('formulize_artifacts', 'entry_id', 1, 'artifacts_year_era')[0]).toBe('150BCE');
	await page.getByRole('link', { name: 'Save and Close' }).click(); // clears the entry lock
	await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
}

// Add an artifact as curator1, filling in what the form requires, check the On After Save procedure gives it its
// standard ID number - M and its entry id - in the column ID Number has under the handle given, then delete it again
// from the Artifacts list
export async function expectANewArtifactToGetItsIdNumber(page, handle) {
	const shortName = `Rename check ${handle}`;
	const added = () => dbQuery(`SELECT entry_id, ${handle} FROM ${dbPrefix()}_formulize_artifacts WHERE artifacts_short_name = '${shortName}'`);
	await asCurator(page);
	await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
	await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
	await page.getByRole('textbox', { name: 'Short name *' }).fill(shortName);
	const dimensions = page.locator('.formulize-grid').nth(0).locator('input[type="number"]');
	await dimensions.nth(0).fill('1.0'); // height
	await dimensions.nth(1).fill('1.0'); // width
	await dimensions.nth(2).fill('1.0'); // depth
	await page.locator('.formulize-grid').nth(1).locator('input[type="number"]').nth(0).fill('1900'); // year
	await page.getByRole('radio', { name: 'CE', exact: true }).check();
	await saveFormulizeForm(page);
	await expect.poll(() => added().map(([entryId, idNumber]) => idNumber === 'M' + entryId.padStart(3, '0'))).toEqual([true]);
	await page.getByRole('link', { name: 'Save and Close' }).click(); // clears the entry lock
	await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
	await page.locator('input[name="search_artifacts_short_name"]').fill(shortName);
	await page.getByRole('link', { name: 'Short name' }).click();
	await expect(page.getByText('1 to 1 of 1.')).toBeVisible();
	await page.locator('[id^=delete_]').first().check();
	page.once('dialog', dialog => dialog.accept());
	await Promise.all([page.waitForEvent('load'), page.getByRole('button', { name: 'Delete selected' }).click()]);
	await expect.poll(() => added().length).toBe(0);
}

// Leave a form that was filled in and not saved, agreeing to lose the changes when the page asks
export async function leaveWithoutSaving(page) {
	const agree = dialog => dialog.accept();
	page.on('dialog', agree);
	await leavePage(page, '/modules/formulize/');
	page.off('dialog', agree);
}

// As curator1, open the Roman coin and check the Donor list offers its donor, and has it selected
export async function expectTheDonorListToWork(page) {
	await asCurator(page);
	await openArtifact(page, 'M001');
	await expect(page.getByLabel('Donor').locator('option', { hasText: 'François-Marie Arouet' })).toHaveCount(1);
	await expect(page.getByLabel('Donor').locator('option:checked')).toHaveText('François-Marie Arouet');
	await page.getByRole('link', { name: 'Save and Close' }).click();
	await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
}

// A survey of Ancient Wonders offers the artifacts that exhibit shows, as limited by the {surveys_exhibit} term
export async function expectSurveyToOfferTheExhibitsArtifacts(page) {
	const ancient = ['Roman Coin', 'Persian necklace', 'Chinese Sword', 'Egyptian Chariot', 'Babylonian Spoon'];
	const modern = ['Florentine Book', 'French Musket', 'Japanese Coin', 'American Bicycle', 'Polynesian Canoe', 'Viking Silver Armband'];
	const surveySid = dbQuery(`SELECT s.sid FROM ${dbPrefix()}_formulize_screen s WHERE s.fid = ${formIdOf('Surveys')} AND s.type = 'multiPage' ORDER BY s.sid LIMIT 1`)[0][0];
	await page.goto('/user.php?op=logout');
	await page.goto(`/modules/formulize/index.php?sid=${surveySid}`);
	await page.getByLabel('Which exhibit did you see?').selectOption('Ancient Wonders');
	const favourite = page.getByLabel('Which was your favourite');
	await expect(async () => {
		const offered = await favourite.locator('option').allTextContents();
		for (const name of ancient) {
			expect(offered).toContain(name);
		}
		for (const name of modern) {
			expect(offered).not.toContain(name);
		}
	}).toPass();
	await leaveWithoutSaving(page);
}
