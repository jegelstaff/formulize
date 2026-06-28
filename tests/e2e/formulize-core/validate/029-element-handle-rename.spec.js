const { test, expect } = require('@playwright/test');
import { login, waitForAdminPageReady, openElementAccordion, saveAdminForm } from '../../utils';

/**
 * Read the current formula from a derived element's Options tab.
 * The formula is rendered server-side into the textarea with id="elements-ele_value",
 * so reading the DOM value property directly is reliable regardless of CodeMirror state.
 * Must already be on the Options tab when called.
 */
async function getDerivedFormula(page) {
	return page.locator('#elements-ele_value').inputValue();
}

test('Renaming an element handle updates its $handle reference in derived value formulas', async ({ page }) => {
	await login(page, 'admin');

	const navigateToArtifactsElements = async () => {
		await page.goto('/modules/formulize/admin/ui.php?page=home');
		await waitForAdminPageReady(page);
		await page.getByRole('link', { name: 'Application: Museum' }).click();
		await page.getByText('Artifacts').first().click();
		await page.getByRole('link', { name: 'Elements' }).first().click();
		await waitForAdminPageReady(page);
	};

	const openYearEraOptions = async () => {
		await navigateToArtifactsElements();
		await openElementAccordion(page, 'Year-Era');
		await page.getByRole('link', { name: 'Configure' }).click();
		await waitForAdminPageReady(page);
		await page.getByRole('link', { name: 'Options' }).click();
		await waitForAdminPageReady(page);
	};

	// Step 1: Rename artifacts_year to artifacts_year_modified
	await navigateToArtifactsElements();
	await openElementAccordion(page, 'Year Number Box');
	await page.getByRole('link', { name: 'Configure' }).click();
	await waitForAdminPageReady(page);
	await page.locator('input[name="elements-ele_handle"]').fill('artifacts_year_modified');
	await saveAdminForm(page);

	// Step 2: Verify the Year-Era formula was updated to use the new handle
	await openYearEraOptions();
	const formulaAfterRename = await getDerivedFormula(page);
	expect(formulaAfterRename).toContain('$artifacts_year_modified');
	// The old handle followed by a dot was in the original formula; it must be gone
	expect(formulaAfterRename).not.toContain('$artifacts_year.');

	// Step 3: Rename artifacts_year_modified back to artifacts_year to restore state
	await navigateToArtifactsElements();
	await openElementAccordion(page, 'Year Number Box');
	await page.getByRole('link', { name: 'Configure' }).click();
	await waitForAdminPageReady(page);
	await page.locator('input[name="elements-ele_handle"]').fill('artifacts_year');
	await saveAdminForm(page);

	// Step 4: Verify the formula was updated back to the original handle
	await openYearEraOptions();
	const formulaAfterRevert = await getDerivedFormula(page);
	expect(formulaAfterRevert).toContain('$artifacts_year.');
	expect(formulaAfterRevert).not.toContain('$artifacts_year_modified');
});
