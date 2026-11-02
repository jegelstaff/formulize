const { test } = require('@playwright/test');
import { login, loadSavedView, expectCoinsByName, expectArtifactOriginsList } from '../../utils';

/**
 * The saved views curator1 made in 020 load with what was saved in them: the search and sort of "Coins by name",
 * and the columns of "Artifact origins list".
 *
 * Read only. 03-validate-sequential renames the elements these views name and runs these same checks again.
 */

test.describe('Saved views', () => {

	test('Coins by name shows the coins, sorted by name', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await loadSavedView(page, 'Coins by name');
		await expectCoinsByName(page);
	});

	test('Artifact origins list shows where each artifact comes from', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await loadSavedView(page, 'Artifact origins list');
		await expectArtifactOriginsList(page);
	});
});
