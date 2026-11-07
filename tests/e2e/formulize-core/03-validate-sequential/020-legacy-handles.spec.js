const { test, expect } = require('@playwright/test');
const { execFileSync } = require('child_process');
const { webContainer } = require('../../docker-containers');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';
import {
	login, renameElementHandle, elementId, openArtifactOriginsMap, expectArtifactOriginsMap, loadSavedView, expectArtifactOriginsList,
	dbQuery, dbPrefix,
} from '../../utils';
import { asAdmin, asCurator } from './museum-checks';

/**
 * Settings written before Formulize saved element ids still name elements by handle. Renaming the element must turn
 * every one of those into the element's id, so the setting keeps working and never needs renaming again, and must
 * leave alone every value that only looks like the handle - a condition's term, a search, a button's value, a default.
 *
 * legacy-handles.php writes that kind of setting straight into the database, for the Donated to museum element, then
 * this renames the element through the admin UI and reads back what is stored.
 *
 * A linked list's source is checked by what a person gets as well: the Donor list has to offer the donors with its
 * source stored by handle, which is how an old site has it, and again once a rename has turned that into the id.
 */

const handle = 'artifacts_donated_to_museum';

function legacySettings(...args) {
	const out = execFileSync('docker', ['exec', webContainer(), 'php',
		'/var/www/html/tests/e2e/formulize-core/03-validate-sequential/legacy-handles.php', ...args], { encoding: 'utf8' });
	return out.trim().split('\n').pop();
}

test.describe.serial('Legacy handles in settings', () => {

	test.afterAll(() => {
		legacySettings('remove');
	});

	test('a rename turns the old handles into the id, and leaves the values that only look like the handle', async ({ page }) => {
		test.setTimeout(300000);
		legacySettings('remove'); // anything an interrupted run left
		expect(legacySettings('install', handle)).toBe('installed');
		const id = elementId(handle);
		const before = JSON.parse(legacySettings('read', handle));
		expect(before.condition_element).toBe(handle); // what the settings hold before the rename

		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		expect(await renameElementHandle(page, handle, 'artifacts_donated')).toBeNull(); // nothing in code it cannot follow
		const expectConverted = (stored) => {
			expect(stored.condition_element).toBe(id);
			expect(stored.list_column).toBe(id);
			expect(stored.map_column).toBe(id);
			expect(stored.button_element).toBe(id);
			expect(stored.screen_default_keys).toContain(id);
			expect(stored.screen_default_keys).not.toContain(handle);
			expect(stored.view_columns).toBe(`${id},artifacts_short_name`);
			expect(stored.view_sort).toBe(id);
			expect(stored.view_pubfilters).toBe(id);
			expect(stored.group_filter_element).toBe(id);
			// the values that are only the handle's text
			expect(stored.condition_term).toBe(handle);
			expect(stored.mapping_value).toBe(handle); // a setting that only holds ids, so the word there is not the element
			expect(stored.list_search).toBe(handle);
			expect(stored.button_value).toBe(handle);
			expect(stored.screen_default_values).toContain(handle);
			expect(stored.view_search).toBe(handle);
			expect(stored.group_filter_term).toBe(handle);
		};
		expectConverted(JSON.parse(legacySettings('read', handle)));

		// the map, which now finds its column by id, still draws every pin
		await page.goto('/user.php?op=logout');
		await login(page, 'curator1', '12345');
		await openArtifactOriginsMap(page);
		await expectArtifactOriginsMap(page);

		// renaming back leaves the ids as they are now, and the values alone
		await page.goto('/user.php?op=logout');
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		expect(await renameElementHandle(page, 'artifacts_donated', handle)).toBeNull();
		expectConverted(JSON.parse(legacySettings('read', handle)));
	});
});

test.describe.serial('A linked list whose source is stored by handle', () => {

	test.afterAll(() => {
		legacySettings('remove-source');
	});

	// As curator1, open the Roman coin and check the Donor list offers its donor, and has it selected
	async function expectTheDonorListToWork(page) {
		const donor = 'François-Marie Arouet';
		await page.goto('/user.php?op=logout');
		await login(page, 'curator1', '12345');
		await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
		await page.getByRole('row', { name: 'M001' }).getByRole('link').first().click();
		await expect(page.getByRole('button', { name: 'Save', exact: true })).toBeVisible();
		await expect(page.getByLabel('Donor').locator('option', { hasText: donor })).toHaveCount(1);
		await expect(page.getByLabel('Donor').locator('option:checked')).toHaveText(donor);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // clears the entry lock
		await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
	}

	async function renameAsAdmin(page, from, to) {
		await page.goto('/user.php?op=logout');
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		expect(await renameElementHandle(page, from, to)).toBeNull();
	}

	test('it works by handle, a rename turns that into the id, and it works by id', async ({ page }) => {
		test.setTimeout(300000);
		legacySettings('remove-source'); // anything an interrupted run left
		expect(legacySettings('install-source', 'donors_name')).toBe('installed');
		const stored = legacySettings('read-source');
		expect(stored).toMatch(/^\d+#\*=:\*donors_name$/);
		await expectTheDonorListToWork(page);

		await renameAsAdmin(page, 'donors_name', 'donors_full_name');
		const byId = `${stored.split('#*=:*')[0]}#*=:*${elementId('donors_full_name')}`;
		expect(legacySettings('read-source')).toBe(byId);
		await expectTheDonorListToWork(page);

		// renaming back leaves the id as it is
		await renameAsAdmin(page, 'donors_full_name', 'donors_name');
		expect(legacySettings('read-source')).toBe(byId);
		await expectTheDonorListToWork(page);
	});
});

test.describe.serial('An element whose handle is a number', () => {

	test.afterAll(() => {
		legacySettings('remove-numeric');
	});

	// The map plots by Latitude, and the Artifact origins list shows it, both naming it by its id
	const whereLatitudeIsUsed = () => [
		dbQuery(`SELECT m.lat_element FROM ${dbPrefix()}_formulize_screen_map m JOIN ${dbPrefix()}_formulize_screen s ON s.sid = m.sid WHERE s.screen_handle = 'artifact_origins'`)[0][0],
		dbQuery(`SELECT sv_oldcols FROM ${dbPrefix()}_formulize_saved_views WHERE sv_name = 'Artifact origins list'`)[0][0],
	];
	async function expectTheMapAndListToWork(page) {
		await asCurator(page);
		await openArtifactOriginsMap(page);
		await expectArtifactOriginsMap(page);
		await page.goto('/modules/formulize/');
		await loadSavedView(page, 'Artifact origins list');
		await expectArtifactOriginsList(page);
	}

	test('a rename gives it a handle, and the numbers that were its handle and its id stay as ids', async ({ page }) => {
		test.setTimeout(300000);
		legacySettings('remove-numeric'); // anything an interrupted run left
		const before = whereLatitudeIsUsed();
		const id = legacySettings('install-numeric');
		expect(id).toBe(before[0]); // the map names it by the number that is now its handle as well
		expect(before[1].split(',')).toContain(id);
		await expectTheMapAndListToWork(page);

		await asAdmin(page);
		expect(await renameElementHandle(page, id, 'artifacts_latitude')).toBeNull();
		expect(whereLatitudeIsUsed()).toEqual(before);
		await expectTheMapAndListToWork(page);
	});
});
