const { test, expect } = require('@playwright/test');
const { execFileSync } = require('child_process');
const { webContainer } = require('../../docker-containers');
import { dbQuery, dbPrefix, openArtifactOriginsMap, expectArtifactOriginsMap, loadSavedView, expectCoinsByName, expectArtifactOriginsList } from '../../utils';
import {
	asAdmin, formIdOf, expectYearEraToFollowTheYear, expectANewArtifactToGetItsIdNumber, expectTheDonorListToWork,
	expectSurveyToOfferTheExhibitsArtifacts, asCurator,
} from './museum-checks';

/**
 * An update brings a site whose element handles have capitals or hyphens into the shape handles have now, along with
 * everything that was named after them, and the site works afterwards.
 *
 * Formulize will not create such a handle any more, so old-handles.php writes one, to real elements that drive real
 * features: in the element table, the data and revisions tables, the code files and the settings that name them. Then
 * an administrator runs the update the way a real site gets it, from the Update Formulize button.
 *
 * The system was right before it was broken, so that is the yardstick: afterwards the Formulize tables and the code and
 * template files have to be what they were, and then a person has to be able to use the features that depend on those
 * elements. The checks are the same ones the rename in 010 has to pass.
 *
 * This changes the whole system and relies on the update to put it back, so it runs after the other sequential specs
 * and nothing else can be running. If it fails part way through, restore a snapshot before running anything else.
 */

function oldHandles(...args) {
	const out = execFileSync('docker', ['exec', webContainer(), 'php',
		'/var/www/html/tests/e2e/formulize-core/03-validate-sequential/old-handles.php', ...args], { encoding: 'utf8' });
	return out.trim().split('\n').pop();
}
const planted = () => JSON.parse(oldHandles('planted'));
function elementHandle(id) {
	return dbQuery(`SELECT ele_handle FROM ${dbPrefix()}_formulize WHERE ele_id = ${id}`)[0][0];
}

// Click a button that runs the update, and give back what the page says it did
async function runUpdate(page, button) {
	await page.getByRole('button', { name: button }).click();
	await expect(page.getByRole('link', { name: 'Close' })).toBeVisible({ timeout: 120000 });
	return page.locator('body').innerText();
}

test.describe.serial('Updating a site with element handles in their old shapes', () => {

	test('the update brings the handles, the fields, the code and the settings back to what they should be', async ({ page }) => {
		test.setTimeout(600000);
		const alerts = [];
		page.on('dialog', dialog => {
			alerts.push(dialog.message());
			dialog.accept();
		});
		const donorsBioId = dbQuery(`SELECT ele_id FROM ${dbPrefix()}_formulize WHERE ele_handle = 'donors_bio'`)[0][0];

		oldHandles('remove-references'); // anything an interrupted run left
		expect(oldHandles('add-references')).toBe('added');
		// the yardstick has to hold the system, or comparing against it proves nothing
		const captured = JSON.parse(oldHandles('capture'));
		expect(captured.tables).toBeGreaterThan(30);
		expect(captured.elements).toBeGreaterThan(50);
		expect(captured.files).toBeGreaterThan(5);
		const changed = JSON.parse(oldHandles('break'));
		// every kind of place was really changed, or the update has less to do than this says
		for (const place of [
			'alternate elements of linked lists', 'code file names', 'conditions on elements', 'data table fields', 'element handles',
			'handles in code', 'linked sources', 'list columns', 'map columns', 'map elements', 'notification subjects', 'revisions table fields',
			'saved view columns', 'saved view sorting', 'a stale variable beside the right one', 'tokens in element settings', 'variables in code',
			'custom buttons', 'group filters', 'handles in custom button code', 'handles in custom pages', 'hidden and inline editing columns',
			'page conditions', 'saved view search boxes', 'screen defaults', 'tokens in list column searches', 'tokens in map column searches',
			'tokens in saved view searches',
		]) {
			expect(changed[place] || 0, place).toBeGreaterThan(0);
		}
		// What the comparison sees now is everything the update has to put back, one difference at a time. It has to see
		// the damage in the tables, the fields and the files, or an empty comparison at the end would prove nothing.
		const damage = JSON.parse(oldHandles('compare'));
		await test.info().attach('what the damage changed', { body: damage.join('\n'), contentType: 'text/plain' });
		console.log(`The damage made ${damage.length} differences from the system as it was`);
		expect(damage.some(difference => difference.startsWith('formulize row ') && difference.includes(' ele_handle: '))).toBe(true);
		expect(damage.some(difference => difference.startsWith('formulize_artifacts: columns differ'))).toBe(true);
		expect(damage.some(difference => difference.includes(' is now called '))).toBe(true);
		expect(damage.some(difference => difference.includes(' has changed: '))).toBe(true);

		await asAdmin(page);
		await page.goto('/modules/formulize/admin');
		const said = await runUpdate(page, 'Update Formulize');

		// what the administrator is told
		expect(said).toContain('Renaming element handles');
		expect(said).toContain('Artifacts_Year');
		expect(said).toContain('Updating references to element handles that still use capitals or hyphens');
		expect(said).toContain('Artifacts_ID_Number');
		expect(said).toContain('Element handles that could not be fixed');
		expect(said).toContain('Artifacts_Date_Of_Acquisition');
		expect(said).toContain('Old element handles still used in custom code');
		expect(said).toContain('on_before_save_artifacts');
		expect(alerts.join(' ')).toContain('could not be fixed');

		// the fields, in the data table and the revisions table
		let state = planted();
		expect(state.dbversion).toBe(27);
		for (const fields of [state.data_fields, state.revisions_fields]) {
			for (const field of ['artifacts_year', 'artifacts_era', 'artifacts_year_era', 'artifacts_short_name', 'artifacts_latitude', 'artifacts_donated_to_museum']) {
				expect(fields).toContain(field);
			}
		}
		// the element with nothing to rename is left as it was, and so is its neighbour in the list of what failed
		expect(state.data_fields).toContain('zz_moved_by_someone');
		expect(dbQuery(`SELECT BINARY ele_handle FROM ${dbPrefix()}_formulize WHERE ele_caption = 'Date of acquisition' OR ele_handle = 'Artifacts_Date_Of_Acquisition'`)[0][0]).toBe('Artifacts_Date_Of_Acquisition');
		// what only looks like a handle, and code the update could not be sure about, are left
		expect(state.caption).toBe('See Artifacts_Year and Artifacts-Era here');
		expect(state.term).toBe('Artifacts_Year');
		expect(state.key_left_in_code).toBe(true);
		// the element whose handle was already taken in another form gets one of its own
		const fid = formIdOf('Donors');
		expect(elementHandle(donorsBioId)).toBe(`artifacts_height_f${fid}`);

		// someone mends the field, and the update tries again. The stale spelling written this time is left, because
		// the search for those happens once, on the update that reaches this version.
		oldHandles('second-visit');
		await page.goto('/modules/formulize/admin/ui.php?op=patchDB-only');
		const saidAgain = await runUpdate(page, 'Update Formulize - Database Only');
		expect(saidAgain).toContain('Artifacts_Date_Of_Acquisition');
		expect(saidAgain).not.toContain('Element handles that could not be fixed');
		state = planted();
		expect(state.data_fields).toContain('artifacts_date_of_acquisition');
		expect(state.caption).toContain('{Artifacts_Width}');

		// what was planted that belongs to nothing real goes, and then the system has to be what it was
		expect(oldHandles('tidy', `artifacts_height_f${fid}`)).toBe('tidied');
		expect(JSON.parse(oldHandles('compare'))).toEqual([]);
		expect(oldHandles('remove-references')).toBe('removed');
	});

	test('the features that depend on those elements work', async ({ page }) => {
		test.setTimeout(600000);
		// a formula, on saving an entry
		await expectYearEraToFollowTheYear(page);
		// a procedure that named its element both ways
		await expectANewArtifactToGetItsIdNumber(page, 'artifacts_id_number');
		// a linked list, and the condition that shows it
		await expectTheDonorListToWork(page);
		// a {handle} term in a linked list's filter
		await expectSurveyToOfferTheExhibitsArtifacts(page);
		// the map, and saved views
		await asCurator(page);
		await openArtifactOriginsMap(page);
		await expectArtifactOriginsMap(page);
		await page.goto('/modules/formulize/');
		await loadSavedView(page, 'Coins by name');
		await expectCoinsByName(page);
		await loadSavedView(page, 'Artifact origins list');
		await expectArtifactOriginsList(page);
	});
});
