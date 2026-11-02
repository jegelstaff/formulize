const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';
import {
	login, saveFormulizeForm, saveAdminForm, waitForAdminPageReady, dbQuery, dbPrefix, renameElementHandle, elementId,
	openArtifactOriginsMap, expectArtifactOriginsMap, loadSavedView, expectCoinsByName, expectArtifactOriginsList,
} from '../../utils';

/**
 * Renaming an element's handle, through its settings as a webmaster does, keeps everything that depends on the
 * element working. Each test renames elements the museum system's screens, formulas, saved views, custom button and
 * notification use, checks those still work and that what they store is right, then renames the elements back and
 * checks again, leaving the system as setup built it.
 *
 * What is stored has two kinds of reference in it. Settings name an element by its id, which a rename does not
 * change, so those must be exactly as they were. Code and text name it by its handle - $handle in a formula, a
 * {handle} token, a notification's {ELEMENT_HANDLE} tag - so those must now say the new handle.
 */

const codeDir = path.resolve(__dirname, '../../../../modules/formulize/code');

function rowOf(table, keyColumn, key, columns) {
	return dbQuery(`SELECT ${columns} FROM ${dbPrefix()}_${table} WHERE ${keyColumn} = ${key}`)[0];
}
function eleValueOf(handleOrId) {
	const where = /^\d+$/.test(String(handleOrId)) ? `ele_id = ${handleOrId}` : `ele_handle = '${handleOrId}'`;
	return dbQuery(`SELECT ele_value FROM ${dbPrefix()}_formulize WHERE ${where}`)[0][0];
}
function dataColumnExists(formHandle, column) {
	return dbQuery(`SHOW COLUMNS FROM ${dbPrefix()}_formulize_${formHandle} LIKE '${column}'`).length === 1;
}
async function asAdmin(page) {
	await page.goto('/user.php?op=logout');
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
}
async function asCurator(page) {
	await page.goto('/user.php?op=logout');
	await login(page, 'curator1', '12345');
}
// Open an artifact from the Artifacts list, as curator1 sees it
async function openArtifact(page, idNumber) {
	await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
	await page.getByRole('row', { name: idNumber }).getByRole('link').first().click();
	await expect(page.getByRole('button', { name: 'Save', exact: true })).toBeVisible();
}

test.describe.serial('Renaming element handles', () => {

	test('Year: the Year-Era formula follows the rename and keeps working', async ({ page }) => {
		test.setTimeout(300000);
		await asAdmin(page);
		await renameElementHandle(page, 'artifacts_year', 'artifacts_year_made');
		const formula = () => fs.readFileSync(path.join(codeDir, 'derived_artifacts_year_era.php'), 'utf8');
		expect(formula()).toContain('$artifacts_year_made.');
		expect(formula()).not.toMatch(/\$artifacts_year(?![A-Za-z0-9_])/);
		expect(dataColumnExists('artifacts', 'artifacts_year_made')).toBe(true);
		expect(dataColumnExists('artifacts', 'artifacts_year')).toBe(false);
		// the formula runs on save: change the Roman coin's year and back, and Year-Era follows
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
		// and back
		await asAdmin(page);
		await renameElementHandle(page, 'artifacts_year_made', 'artifacts_year');
		expect(formula()).toContain('$artifacts_year.');
		expect(formula()).not.toContain('artifacts_year_made');
		expect(dataColumnExists('artifacts', 'artifacts_year')).toBe(true);
	});

	test('Donors Name: the Donor list keeps offering the donors, and its own formula file follows it', async ({ page }) => {
		test.setTimeout(300000);
		const donorsNameId = elementId('donors_name');
		const donorFid = dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title = 'Donors'`)[0][0];
		const source = `${donorFid}#*=:*${donorsNameId}`;
		expect(eleValueOf('artifacts_donor')).toContain(`"${source}"`); // the Donor list names its source by id
		await asAdmin(page);
		await renameElementHandle(page, 'donors_name', 'donors_full_name');
		expect(eleValueOf('artifacts_donor')).toContain(`"${source}"`); // which a rename does not change
		expect(fs.existsSync(path.join(codeDir, 'derived_donors_full_name.php'))).toBe(true);
		expect(fs.existsSync(path.join(codeDir, 'derived_donors_name.php'))).toBe(false);
		await asCurator(page);
		await openArtifact(page, 'M001');
		await expect(page.getByLabel('Donor').locator('option', { hasText: 'François-Marie Arouet' })).toHaveCount(1);
		await expect(page.getByLabel('Donor').locator('option:checked')).toHaveText('François-Marie Arouet');
		await page.getByRole('link', { name: 'Save and Close' }).click();
		await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
		// and back
		await asAdmin(page);
		await renameElementHandle(page, 'donors_full_name', 'donors_name');
		expect(eleValueOf('artifacts_donor')).toContain(`"${source}"`);
		expect(fs.existsSync(path.join(codeDir, 'derived_donors_name.php'))).toBe(true);
	});

	test('Exhibit collections and Survey exhibit: the {handle} terms in linked list filters follow the rename', async ({ page }) => {
		test.setTimeout(300000);
		await asAdmin(page);
		await renameElementHandle(page, 'exhibits_collections', 'exhibits_collection_list');
		expect(eleValueOf('exhibits_artifacts')).toContain('{exhibits_collection_list}');
		expect(eleValueOf('exhibits_artifacts')).not.toContain('{exhibits_collections}');
		await renameElementHandle(page, 'surveys_exhibit', 'surveys_exhibit_seen');
		expect(eleValueOf('surveys_favourite_artifact')).toContain('{surveys_exhibit_seen}');
		expect(eleValueOf('surveys_favourite_artifact')).not.toContain('{surveys_exhibit}');
		// the exhibit form still draws its artifacts, filtered by the term
		await asCurator(page);
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true }).click();
		await page.getByRole('row', { name: /History through the Ages/ }).getByRole('link').first().click();
		await expect(page.getByRole('button', { name: 'Save', exact: true })).toBeVisible();
		await expect(page.getByText('There was an error')).toHaveCount(0);
		await page.getByRole('link', { name: 'Save and Close' }).click();
		// and back
		await asAdmin(page);
		await renameElementHandle(page, 'exhibits_collection_list', 'exhibits_collections');
		await renameElementHandle(page, 'surveys_exhibit_seen', 'surveys_exhibit');
		expect(eleValueOf('exhibits_artifacts')).toContain('{exhibits_collections}');
		expect(eleValueOf('surveys_favourite_artifact')).toContain('{surveys_exhibit}');
	});

	test('Short name and Latitude: the map, saved views, help text and notification all follow the rename', async ({ page }) => {
		test.setTimeout(600000);
		const shortNameId = elementId('artifacts_short_name');
		const latitudeId = elementId('artifacts_latitude');
		const mapBefore = dbQuery(`SELECT m.lat_element, m.label_element, m.columns FROM ${dbPrefix()}_formulize_screen_map m JOIN ${dbPrefix()}_formulize_screen s ON s.sid = m.sid WHERE s.screen_handle = 'artifact_origins'`)[0];
		const viewsBefore = dbQuery(`SELECT sv_name, sv_oldcols, sv_sort FROM ${dbPrefix()}_formulize_saved_views WHERE sv_name IN ('Coins by name', 'Artifact origins list') ORDER BY sv_name`);
		expect(mapBefore[0]).toBe(latitudeId);
		expect(mapBefore[1]).toBe(shortNameId);
		// help text that shows the artifact's short name, through a {handle} token
		await asAdmin(page);
		const descriptionId = elementId('artifacts_full_description');
		const fid = dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize WHERE ele_id = ${descriptionId}`)[0][0];
		const aid = dbQuery(`SELECT appid FROM ${dbPrefix()}_formulize_application_form_link WHERE fid = ${fid}`)[0][0];
		const descriptionSettings = `/modules/formulize/admin/ui.php?page=element&ele_id=${descriptionId}&fid=${fid}&aid=${aid}`;
		const originalHelpText = dbQuery(`SELECT ele_desc FROM ${dbPrefix()}_formulize WHERE ele_id = ${descriptionId}`)[0][0];
		await page.goto(descriptionSettings);
		await waitForAdminPageReady(page);
		await page.locator('textarea[name="elements-ele_desc"]').fill('Describe the {artifacts_short_name}');
		await saveAdminForm(page);
		await expect.poll(() => dbQuery(`SELECT ele_desc FROM ${dbPrefix()}_formulize WHERE ele_id = ${descriptionId}`)[0][0]).toBe('Describe the {artifacts_short_name}');

		await renameElementHandle(page, 'artifacts_short_name', 'artifacts_name');
		await renameElementHandle(page, 'artifacts_latitude', 'artifacts_lat');
		// settings name them by id, so nothing there has changed
		const mapAfter = dbQuery(`SELECT m.lat_element, m.label_element, m.columns FROM ${dbPrefix()}_formulize_screen_map m JOIN ${dbPrefix()}_formulize_screen s ON s.sid = m.sid WHERE s.screen_handle = 'artifact_origins'`)[0];
		expect(mapAfter).toEqual(mapBefore);
		expect(dbQuery(`SELECT sv_name, sv_oldcols, sv_sort FROM ${dbPrefix()}_formulize_saved_views WHERE sv_name IN ('Coins by name', 'Artifact origins list') ORDER BY sv_name`)).toEqual(viewsBefore);
		// text names them by handle, so that has
		expect(dbQuery(`SELECT ele_desc FROM ${dbPrefix()}_formulize WHERE ele_id = ${descriptionId}`)[0][0]).toBe('Describe the {artifacts_name}');
		const subject = () => dbQuery(`SELECT not_cons_subject FROM ${dbPrefix()}_formulize_notification_conditions WHERE not_cons_fid = ${fid}`)[0][0];
		expect(subject()).toBe('Artifact updated: {ELEMENT_ARTIFACTS_NAME}');

		// and it all still works
		await asCurator(page);
		await openArtifactOriginsMap(page);
		await expectArtifactOriginsMap(page);
		await page.goto('/modules/formulize/');
		await loadSavedView(page, 'Coins by name');
		await expectCoinsByName(page);
		await loadSavedView(page, 'Artifact origins list');
		await expectArtifactOriginsList(page);
		await page.goto('/modules/formulize/');
		await openArtifact(page, 'M001');
		await expect(page.getByText('Describe the Roman Coin')).toBeVisible();
		await page.getByRole('link', { name: 'Save and Close' }).click();
		await expect(page.getByText('Showing entries: 1 to')).toBeVisible();

		// and back
		await asAdmin(page);
		await renameElementHandle(page, 'artifacts_name', 'artifacts_short_name');
		await renameElementHandle(page, 'artifacts_lat', 'artifacts_latitude');
		expect(subject()).toBe('Artifact updated: {ELEMENT_ARTIFACTS_SHORT_NAME}');
		expect(dbQuery(`SELECT ele_desc FROM ${dbPrefix()}_formulize WHERE ele_id = ${descriptionId}`)[0][0]).toBe('Describe the {artifacts_short_name}');
		await page.goto(descriptionSettings);
		await waitForAdminPageReady(page);
		await page.locator('textarea[name="elements-ele_desc"]').fill(originalHelpText);
		await saveAdminForm(page);
		await expect.poll(() => dbQuery(`SELECT ele_desc FROM ${dbPrefix()}_formulize WHERE ele_id = ${descriptionId}`)[0][0]).toBe(originalHelpText);
		await asCurator(page);
		await openArtifactOriginsMap(page);
		await expectArtifactOriginsMap(page);
		await page.goto('/modules/formulize/');
		await loadSavedView(page, 'Coins by name');
		await expectCoinsByName(page);
	});

	test('Condition: the custom button and the notification keep naming it by id, and the button still works', async ({ page }) => {
		test.setTimeout(300000);
		const conditionId = elementId('artifacts_condition');
		const buttons = () => dbQuery(`SELECT customactions FROM ${dbPrefix()}_formulize_screen_listofentries l JOIN ${dbPrefix()}_formulize_screen s ON s.sid = l.sid WHERE s.title = 'Artifacts' AND s.type = 'listOfEntries'`)[0][0];
		const fid = dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title = 'Artifacts'`)[0][0];
		const notificationCondition = () => dbQuery(`SELECT not_cons_con FROM ${dbPrefix()}_formulize_notification_conditions WHERE not_cons_fid = ${fid}`)[0][0];
		const buttonsBefore = buttons();
		const notificationBefore = notificationCondition();
		expect(notificationBefore).toContain(`"${conditionId}"`);
		await asAdmin(page);
		await renameElementHandle(page, 'artifacts_condition', 'artifacts_state');
		expect(buttons()).toBe(buttonsBefore);
		expect(notificationCondition()).toBe(notificationBefore);
		// the button beside an artifact marks it as in perfect condition
		const spoon = dbQuery(`SELECT entry_id, artifacts_state FROM ${dbPrefix()}_formulize_artifacts WHERE artifacts_short_name = 'Babylonian Spoon'`)[0];
		await asCurator(page);
		await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
		await Promise.all([page.waitForEvent('load'),
			page.getByRole('row', { name: /Babylonian Spoon/ }).getByRole('button', { name: 'Mark as in perfect condition' }).click()]);
		await expect.poll(() => dbQuery(`SELECT artifacts_state FROM ${dbPrefix()}_formulize_artifacts WHERE entry_id = ${spoon[0]}`)[0][0]).toBe('10');
		dbQuery(`UPDATE ${dbPrefix()}_formulize_artifacts SET artifacts_state = '${spoon[1]}' WHERE entry_id = ${spoon[0]}`); // as it was
		// and back
		await asAdmin(page);
		await renameElementHandle(page, 'artifacts_state', 'artifacts_condition');
		expect(buttons()).toBe(buttonsBefore);
		expect(notificationCondition()).toBe(notificationBefore);
	});
});
