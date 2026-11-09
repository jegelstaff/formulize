const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';
import {
	asAdmin, asCurator, openArtifact, rowOf, formIdOf, expectYearEraToFollowTheYear, expectANewArtifactToGetItsIdNumber,
	expectTheDonorListToWork, expectSurveyToOfferTheExhibitsArtifacts, leaveWithoutSaving,
} from './museum-checks';
import {
	saveFormulizeForm, saveAdminForm, waitForAdminPageReady, dbQuery, dbPrefix, renameElementHandle, elementId,
	openArtifactOriginsMap, expectArtifactOriginsMap, loadSavedView, expectCoinsByName, expectArtifactOriginsList,
	conditionalElementReady, setCodeMirrorValue, autocompleteBox,
} from '../../utils';

/**
 * Renaming an element's handle, through its settings as a webmaster does, keeps everything that depends on the
 * element working. Each test renames elements the museum system's screens, formulas, saved views, custom button and
 * notification use, checks those still work and that what they store is right, then renames the elements back and
 * checks again, leaving the system as setup built it.
 *
 * What is stored has two kinds of reference in it. Settings name an element by its id, which a rename does not
 * change, so those must be exactly as they were. Code and text name it by its handle - $handle in a formula, a
 * {handle} token, a notification's {ELEMENT_HANDLE} tag, the handle given to formulize_writeEntry() - so those must now say the
 * new handle. Where code names the old handle in a way that may or may not be the element, the rename leaves it and
 * says so in a dialog when the handle is saved; every rename here checks that it says so exactly when it should.
 */

const codeDir = path.resolve(__dirname, '../../../../modules/formulize/code');

function eleValueOf(handleOrId) {
	const where = /^\d+$/.test(String(handleOrId)) ? `ele_id = ${handleOrId}` : `ele_handle = '${handleOrId}'`;
	return dbQuery(`SELECT ele_value FROM ${dbPrefix()}_formulize WHERE ${where}`)[0][0];
}
function dataColumnExists(formHandle, column) {
	return dbQuery(`SHOW COLUMNS FROM ${dbPrefix()}_formulize_${formHandle} LIKE '${column}'`).length === 1;
}
// Open a screen's templates in its settings, first giving the screen its own copies of the default templates with
// the "Customize the Templates" button, if it does not have them yet. Says whether it had to.
async function openScreenTemplates(page, fid, sid) {
	const templates = `/modules/formulize/admin/ui.php?page=screen&fid=${fid}&sid=${sid}&tab=templates`;
	await page.goto(templates);
	await waitForAdminPageReady(page);
	const customize = page.getByRole('button', { name: 'Customize the Templates' });
	// every tab is on the page, so only the editors in the templates tab's form count
	const templateEditor = templatesForm(page).locator('.CodeMirror').first();
	await expect(customize.or(templateEditor)).toBeVisible();
	const customized = await customize.isVisible();
	if (customized) {
		await Promise.all([page.waitForEvent('load'), customize.click()]);
		await page.goto(templates);
		await waitForAdminPageReady(page);
	}
	await expect(templateEditor).toBeVisible();
	return customized;
}
// The form in a screen's settings that holds its template boxes
function templatesForm(page) {
	return page.locator('form:has(input[name="formulize_admin_handler"][value$="_templates"])');
}
// Put code in one of a screen's template boxes, and save it, in the screen's settings
async function setScreenTemplate(page, fid, sid, box, code) {
	await openScreenTemplates(page, fid, sid);
	await setCodeMirrorValue(page, `textarea[name="screens-${box}"]`, code);
	await saveAdminForm(page);
}
// What one of a screen's template boxes shows, in the screen's settings
async function shownScreenTemplate(page, fid, sid, box) {
	await openScreenTemplates(page, fid, sid);
	return templatesForm(page).locator(`textarea[name="screens-${box}"] + .CodeMirror`).evaluate(editor => editor.CodeMirror.getValue());
}
// Empty all of a screen's template boxes and save, in the screen's settings, which puts it back on the default
// templates
async function useDefaultScreenTemplates(page, fid, sid) {
	await openScreenTemplates(page, fid, sid);
	const templateBoxes = templatesForm(page).locator('textarea.code-textarea');
	for (const name of await templateBoxes.evaluateAll(boxes => boxes.map(box => box.name))) {
		await setCodeMirrorValue(page, `textarea[name="${name}"]`, '');
	}
	await saveAdminForm(page);
	await page.goto(`/modules/formulize/admin/ui.php?page=screen&fid=${fid}&sid=${sid}&tab=templates`);
	await waitForAdminPageReady(page);
	await expect(page.getByRole('button', { name: 'Customize the Templates' })).toBeVisible();
}

test.describe.serial('Renaming element handles', () => {

	test('Year: the Year-Era formula follows the rename, and keeps working', async ({ page }) => {
		test.setTimeout(300000);
		const formulaFile = path.join(codeDir, 'derived_artifacts_year_era.php');
		const formula = () => fs.readFileSync(formulaFile, 'utf8').replace(/\r\n/g, '\n'); // a form posts a textarea's lines with \r\n
		expect(formula()).toBe('$value = $artifacts_year.$artifacts_era;');
		await asAdmin(page);
		expect(await renameElementHandle(page, 'artifacts_year', 'artifacts_year_made')).toBeNull();
		expect(formula()).toBe('$value = $artifacts_year_made.$artifacts_era;');
		expect(dataColumnExists('artifacts', 'artifacts_year_made')).toBe(true);
		expect(dataColumnExists('artifacts', 'artifacts_year')).toBe(false);
		// the formula runs on save
		await expectYearEraToFollowTheYear(page);
		// and back
		await asAdmin(page);
		expect(await renameElementHandle(page, 'artifacts_year_made', 'artifacts_year')).toBeNull();
		expect(formula()).toBe('$value = $artifacts_year.$artifacts_era;');
		expect(dataColumnExists('artifacts', 'artifacts_year')).toBe(true);
		await expectYearEraToFollowTheYear(page);
	});

	test('ID Number: the On After Save procedure that standardizes it follows the rename, and keeps working', async ({ page }) => {
		test.setTimeout(300000);
		// The procedure names ID Number twice: as the $artifacts_id_number variable, which Formulize gives the
		// Artifacts form's own procedures, and as the key of the array it gives formulize_writeEntry()
		const procedureFile = path.join(codeDir, 'on_after_save_artifacts.php');
		const procedure = () => fs.readFileSync(procedureFile, 'utf8').replace(/\r\n/g, '\n');
		const namesIdNumberAs = handle => [
			`if(!$${handle} || !preg_match('/^M\\d{3}$/', $${handle})) {`,
			`formulize_writeEntry(['${handle}' => $${handle}], $entry_id);`,
		];
		for (const line of namesIdNumberAs('artifacts_id_number')) {
			expect(procedure()).toContain(line);
		}
		await asAdmin(page);
		expect(await renameElementHandle(page, 'artifacts_id_number', 'artifacts_catalogue_number')).toBeNull();
		for (const line of namesIdNumberAs('artifacts_catalogue_number')) {
			expect(procedure()).toContain(line);
		}
		expect(procedure()).not.toContain('artifacts_id_number');
		expect(dataColumnExists('artifacts', 'artifacts_catalogue_number')).toBe(true);
		await expectANewArtifactToGetItsIdNumber(page, 'artifacts_catalogue_number');
		// and back
		await asAdmin(page);
		expect(await renameElementHandle(page, 'artifacts_catalogue_number', 'artifacts_id_number')).toBeNull();
		for (const line of namesIdNumberAs('artifacts_id_number')) {
			expect(procedure()).toContain(line);
		}
		expect(procedure()).not.toContain('artifacts_catalogue_number');
		expect(dataColumnExists('artifacts', 'artifacts_id_number')).toBe(true);
		await expectANewArtifactToGetItsIdNumber(page, 'artifacts_id_number');
	});

	test('Donors Name: the Donor list keeps offering the donors, and its own formula file follows it', async ({ page }) => {
		test.setTimeout(300000);
		const donorsNameId = elementId('donors_name');
		const source = `${formIdOf('Donors')}#*=:*${donorsNameId}`;
		expect(eleValueOf('artifacts_donor')).toContain(`"${source}"`); // the Donor list names its source by id
		await asAdmin(page);
		expect(await renameElementHandle(page, 'donors_name', 'donors_full_name')).toBeNull();
		expect(eleValueOf('artifacts_donor')).toContain(`"${source}"`); // which a rename does not change
		expect(fs.existsSync(path.join(codeDir, 'derived_donors_full_name.php'))).toBe(true);
		expect(fs.existsSync(path.join(codeDir, 'derived_donors_name.php'))).toBe(false);
		await expectTheDonorListToWork(page);
		// and back
		await asAdmin(page);
		expect(await renameElementHandle(page, 'donors_full_name', 'donors_name')).toBeNull();
		expect(eleValueOf('artifacts_donor')).toContain(`"${source}"`);
		expect(fs.existsSync(path.join(codeDir, 'derived_donors_name.php'))).toBe(true);
	});

	test('Exhibit collections and Survey exhibit: the {handle} terms in linked list filters follow the rename, and still filter', async ({ page }) => {
		test.setTimeout(300000);
		// A new exhibit of Ancient History offers only its artifacts, as filtered by the {exhibits_collections} term.
		// The Collections box is found by its element's handle, which is whatever it has been renamed to at the time.
		const expectExhibitToOfferItsCollectionsArtifacts = async collectionsHandle => {
			await asCurator(page);
			await page.locator('#burger-and-logo').getByRole('link').first().click();
			await page.locator('#mainmenu').getByRole('link', { name: 'Exhibits', exact: true }).click();
			await page.getByRole('button', { name: 'Add Exhibit', exact: true }).click();
			await autocompleteBox(page, collectionsHandle).fill('an');
			await conditionalElementReady(page, 'exhibits_artifacts', () => page.getByText('Ancient History', { exact: true }).click());
			await autocompleteBox(page, 'exhibits_artifacts').fill('roman');
			await expect(page.getByText('Roman Coin')).toBeVisible();
			await autocompleteBox(page, 'exhibits_artifacts').fill('flor');
			await expect(page.getByText('No match found')).toBeVisible();
			await leaveWithoutSaving(page);
		};
		await asAdmin(page);
		expect(await renameElementHandle(page, 'exhibits_collections', 'exhibits_collection_list')).toBeNull();
		expect(eleValueOf('exhibits_artifacts')).toContain('{exhibits_collection_list}');
		expect(eleValueOf('exhibits_artifacts')).not.toContain('{exhibits_collections}');
		expect(await renameElementHandle(page, 'surveys_exhibit', 'surveys_exhibit_seen')).toBeNull();
		expect(eleValueOf('surveys_favourite_artifact')).toContain('{surveys_exhibit_seen}');
		expect(eleValueOf('surveys_favourite_artifact')).not.toContain('{surveys_exhibit}');
		await expectExhibitToOfferItsCollectionsArtifacts('exhibits_collection_list');
		await expectSurveyToOfferTheExhibitsArtifacts(page);
		// and back
		await asAdmin(page);
		expect(await renameElementHandle(page, 'exhibits_collection_list', 'exhibits_collections')).toBeNull();
		expect(await renameElementHandle(page, 'surveys_exhibit_seen', 'surveys_exhibit')).toBeNull();
		expect(eleValueOf('exhibits_artifacts')).toContain('{exhibits_collections}');
		expect(eleValueOf('surveys_favourite_artifact')).toContain('{surveys_exhibit}');
		await expectExhibitToOfferItsCollectionsArtifacts('exhibits_collections');
		await expectSurveyToOfferTheExhibitsArtifacts(page);
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

		expect(await renameElementHandle(page, 'artifacts_short_name', 'artifacts_name')).toBeNull();
		expect(await renameElementHandle(page, 'artifacts_latitude', 'artifacts_lat')).toBeNull();
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
		expect(await renameElementHandle(page, 'artifacts_name', 'artifacts_short_name')).toBeNull();
		expect(await renameElementHandle(page, 'artifacts_lat', 'artifacts_latitude')).toBeNull();
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

	test('Condition: the custom button and the notification keep naming it by id, the list template\'s search box follows it, and what may not be it is pointed out', async ({ page }) => {
		test.setTimeout(300000);
		const conditionId = elementId('artifacts_condition');
		const [listSid, theme] = dbQuery(`SELECT sid, theme FROM ${dbPrefix()}_formulize_screen WHERE title = 'Artifacts' AND type = 'listOfEntries'`)[0];
		const buttons = () => dbQuery(`SELECT customactions FROM ${dbPrefix()}_formulize_screen_listofentries WHERE sid = ${listSid}`)[0][0];
		const fid = formIdOf('Artifacts');
		const notificationCondition = () => dbQuery(`SELECT not_cons_con FROM ${dbPrefix()}_formulize_notification_conditions WHERE not_cons_fid = ${fid}`)[0][0];
		const buttonsBefore = buttons();
		const notificationBefore = notificationCondition();
		expect(notificationBefore).toContain(`"${conditionId}"`);

		// The Artifacts list's bottom template, set in the list's settings with a line in front naming the Condition
		// search box's variable, which a rename follows, and the handle as an array key, which it cannot know is the
		// element. Put back as it was at the end, through the same settings.
		const template = () => shownScreenTemplate(page, fid, listSid, 'bottomtemplate');
		const withSearch = (handle) => "<?php $artifactSearches = array('artifacts_condition' => isset($quickSearch" + handle + ") ? $quickSearch" + handle + " : ''); ?>";
		await asAdmin(page);
		const customized = await openScreenTemplates(page, fid, listSid);
		const asItWas = await template();
		try {
			await setScreenTemplate(page, fid, listSid, 'bottomtemplate', withSearch('artifacts_condition') + asItWas);
			const saved = await template();
			expect(saved.startsWith(withSearch('artifacts_condition'))).toBe(true);
			const said = await renameElementHandle(page, 'artifacts_condition', 'artifacts_state');
			expect(said).toContain(`${theme}/${listSid}/bottomtemplate.php`);
			expect(said).toContain('artifacts_condition');
			// the same template, with only the search box's variable changed
			expect(await template()).toBe(saved.replaceAll('$quickSearchartifacts_condition', '$quickSearchartifacts_state'));
			expect(buttons()).toBe(buttonsBefore);
			expect(notificationCondition()).toBe(notificationBefore);
			// the button beside an artifact marks it as in perfect condition, in the list drawn with that template
			const spoon = dbQuery(`SELECT entry_id, artifacts_state FROM ${dbPrefix()}_formulize_artifacts WHERE artifacts_short_name = 'Babylonian Spoon'`)[0];
			await asCurator(page);
			await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
			await Promise.all([page.waitForEvent('load'),
				page.getByRole('row', { name: /Babylonian Spoon/ }).getByRole('button', { name: 'Mark as in perfect condition' }).click()]);
			await expect.poll(() => dbQuery(`SELECT artifacts_state FROM ${dbPrefix()}_formulize_artifacts WHERE entry_id = ${spoon[0]}`)[0][0]).toBe('10');
			// and back, where nothing names artifacts_state but the search box variable, so there is nothing to point out
			await asAdmin(page);
			expect(await renameElementHandle(page, 'artifacts_state', 'artifacts_condition')).toBeNull();
			expect(await template()).toBe(saved);
			expect(buttons()).toBe(buttonsBefore);
			expect(notificationCondition()).toBe(notificationBefore);
			// the spoon's condition as it was, in its form
			await asCurator(page);
			await openArtifact(page, 'Babylonian Spoon');
			await page.getByRole('slider', { name: 'Condition' }).fill(spoon[1]);
			await saveFormulizeForm(page);
			await expect.poll(() => dbQuery(`SELECT artifacts_condition FROM ${dbPrefix()}_formulize_artifacts WHERE entry_id = ${spoon[0]}`)[0][0]).toBe(spoon[1]);
			await page.getByRole('link', { name: 'Save and Close' }).click(); // clears the entry lock
			await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
		} finally {
			// as it was: on the default templates again if this test gave the list its own, or else with its own
			// bottom template as it found it
			await asAdmin(page);
			if (customized) {
				await useDefaultScreenTemplates(page, fid, listSid);
			} else {
				await setScreenTemplate(page, fid, listSid, 'bottomtemplate', asItWas);
			}
		}
	});

	test('A name Formulize keeps for its own variables in code is not given as a handle, and the save says why', async ({ page }) => {
		test.setTimeout(300000);
		const ratingId = elementId('surveys_rating');
		const fid = formIdOf('Surveys');
		await asAdmin(page);
		// a name already taken gets the form's id after it, and so does a kept one
		const said = await renameElementHandle(page, 'surveys_rating', 'value', `value_f${fid}`);
		expect(said).toContain('value is a name Formulize uses for its own purposes in code');
		expect(said).toContain(`value_f${fid}`);
		expect(elementId(`value_f${fid}`)).toBe(ratingId);
		expect(await renameElementHandle(page, `value_f${fid}`, 'surveys_rating')).toBeNull();
		expect(elementId('surveys_rating')).toBe(ratingId);
	});
});
