const { test, expect } = require('@playwright/test');
import { login, openArtifactOriginsMap, searchArtifactOriginsMap, expectArtifactOriginsMap, waitForMapToSettle } from '../../utils';

/**
 * The Artifact origins map screen, built in 015: every artifact with a place of origin is a pin, labelled with its
 * short name, whose popup shows its full description, and the map can be searched by short name. It has a link in the
 * Museum menu.
 *
 * Read only. 03-validate-sequential renames the elements the map is built on and runs these same checks again.
 */

test.describe('Artifact origins map', () => {

	test('shows a pin for each artifact, with its name and description', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await openArtifactOriginsMap(page);
		await expectArtifactOriginsMap(page);
	});

	test('is reached from the Museum menu', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Artifact origins', exact: true }).click();
		await waitForMapToSettle(page);
		await expectArtifactOriginsMap(page);
	});

	test('can be searched by short name', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await openArtifactOriginsMap(page);
		await expect(page.locator('img.leaflet-marker-icon[title="Polynesian Canoe"]')).toHaveCount(1);
		await searchArtifactOriginsMap(page, 'coin');
		await expect(page.locator('img.leaflet-marker-icon')).toHaveCount(2);
		await expect(page.locator('img.leaflet-marker-icon[title="Roman Coin"]')).toHaveCount(1);
		await expect(page.locator('img.leaflet-marker-icon[title="Japanese Coin"]')).toHaveCount(1);
	});
});
