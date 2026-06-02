const { test, expect } = require('@playwright/test');
import { login, waitForAdminPageReady, openElementAccordion } from '../../utils';

test('Update Derived Values button triggers XHR and completes successfully', async ({ page }) => {
	let alertMessage = null;
	page.on('dialog', async dialog => {
		alertMessage = dialog.message();
		await dialog.dismiss();
	});

	await login(page, 'admin');
	await page.getByRole('link', { name: 'Admin' }).click();
	await page.getByRole('link', { name: 'Home' }).click();
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	await page.getByText('Artifacts').first().click();
	await page.getByRole('link', { name: 'Elements' }).first().click();

	await openElementAccordion(page, 'Year-Era Derived Value');
	await page.getByRole('link', { name: 'Configure' }).click();
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Options' }).click();
	await waitForAdminPageReady(page);

	await page.getByRole('button', { name: 'Update Derived Values' }).click();

	// "Updating Values..." indicator should appear while the XHR runs
	await expect(page.locator('[name="updateder_Info"]')).toBeVisible();

	// Wait for completion — controls return and indicator hides
	await expect(page.locator('[name="updateder_controls"]')).toBeVisible({ timeout: 60000 });
	await expect(page.locator('[name="updateder_Info"]')).not.toBeVisible();

	// An alert firing would mean the XHR returned an error
	expect(alertMessage).toBeNull();
});
