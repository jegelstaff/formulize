const { test, expect } = require('@playwright/test');
const { execFileSync } = require('child_process');
const { webContainer } = require('../../docker-containers');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';
import { login, renameElementHandle, elementId, openArtifactOriginsMap, expectArtifactOriginsMap } from '../../utils';

/**
 * Settings written before Formulize saved element ids still name elements by handle. Renaming the element must turn
 * every one of those into the element's id, so the setting keeps working and never needs renaming again, and must
 * leave alone every value that only looks like the handle - a condition's term, a search, a button's value, a default.
 *
 * legacy-handles.php writes that kind of setting straight into the database, for the Donated to museum element, then
 * this renames the element through the admin UI and reads back what is stored.
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
