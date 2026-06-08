const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, saveAdminForm, waitForAdminPageReady } from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Phase 3 — Permission inheritance
// ============================================================
// Verifies the two inheritance mechanisms:
//   3.1 Template-group -> entry-group cascade: a permission granted on an EAG
//       template group (e.g. "Departments - All Users", set in 015) shows up on
//       each derived entry group ("Ancient History - All Users", etc.) as an
//       INHERITED (disabled, checked) checkbox in the permissions panel. The
//       entry group's panel is reached via the template panel's entry-group
//       selector (egs) widget.
// Read-only: it only views the inherited state, so it doesn't change any
// permissions and is safe to run before the data-entry tests (020+).

test.beforeEach(async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await page.goto('/modules/formulize/admin/ui.php?page=home');
	await waitForAdminPageReady(page);
});

test.describe('Permission inheritance', () => {

	test('Template-group permissions appear as inherited (read-only) on entry-group panels', async ({ page }) => {
		// Open the Artifacts form's Permissions tab (Artifacts got setStandardPermissions
		// on the template groups in 015).
		await page.getByRole('link', { name: 'Application: Museum' }).click();
		await page.getByText('Artifacts').first().click();
		await page.getByRole('link', { name: 'Permissions', exact: true }).click();

		// Show the template group's panel (which carries the entry-group-selector widget).
		await page.locator('#groups').selectOption(['Departments - All Users']);
		await page.getByRole('button', { name: 'Show permissions for these' }).click();
		await waitForAdminPageReady(page);

		// The permissions panels (incl. the egs widget) are in the DOM but display:none;
		// clicking the Permissions tab triggers jQuery-UI to reveal them (no page reload),
		// which can lag on slow hardware. jQuery-UI autocomplete only renders its dropdown
		// for a VISIBLE input, so click the tab and WAIT for the egs input to be visible as
		// the readiness signal before typing (generous timeout for slow machines).
		await page.getByRole('link', { name: 'Permissions', exact: true }).click();
		const egsInput = page.locator('input[id^="egs-ac-input-"]').first();
		await expect(egsInput).toBeVisible({ timeout: 30000 });
		// Type a clean term with real keystrokes so jQuery-UI autocomplete fires, then
		// click the suggestion (rendered as a plain <li>, no <a> in this jQuery-UI build).
		await egsInput.click();
		await egsInput.pressSequentially('All Users', { delay: 60 });
		const suggestion = page.locator('ul.ui-autocomplete:visible li', { hasText: 'Ancient History - All Users' }).first();
		await expect(suggestion).toBeVisible({ timeout: 30000 });
		await suggestion.click();
		await page.getByRole('button', { name: 'Show these groups' }).click();
		await waitForAdminPageReady(page);

		// The entry-group panel renders the cascaded permissions as inherited: disabled +
		// checked checkboxes (form_permissions_entry_group_panel.html). Locate it by CSS
		// (the panel may sit in an inactive jQuery-UI tab); toBeChecked/toBeDisabled only
		// require the element to be attached, not visible.
		const entryPanel = page.locator('.entry-group-panel').filter({ hasText: 'Ancient History - All Users' });
		const viewForm = entryPanel.locator('input[id$="_view_form"]').first();
		await expect(viewForm).toBeChecked();
		await expect(viewForm).toBeDisabled();
		// add_own_entry was also set on the template in 015 → inherited here too.
		const addOwn = entryPanel.locator('input[id$="_add_own_entry"]').first();
		await expect(addOwn).toBeChecked();
		await expect(addOwn).toBeDisabled();
	});
});
