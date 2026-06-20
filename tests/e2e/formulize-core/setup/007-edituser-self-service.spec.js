const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, saveFormulizeForm } from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Phase — /edituser.php unified self-service account editing
// ============================================================
// /edituser.php now renders the Formulize user-account profile form for the
// logged-in user instead of the legacy ImpressCMS profile form, and the legacy
// modules/profile/edituser.php redirects here. Resolution (see
// formulize_resolveUserAccountScreen in usersAndGroups.php):
//   - a user WITH an editable entries-are-users (EAU) entry → that form's
//     defaultform screen for their entry;
//   - otherwise → the System Users ad hoc form, where entry_id IS the uid.
//
// Privileged user-account fields (Account Status, Group Memberships, Masquerade)
// are flagged $adminOnly on their element classes. That single flag drives both
// webmaster-only visibility (ele_display) and a server-side write guard, so a
// non-webmaster self-editing never sees them and can never change them — even by
// forging POST fields.
//
// Depends on 005 having created the Staff EAU form and its users
// (ahstaff/mhstaff/curator1/curator2, password 12345). Because adminOnly
// ele_display is applied at element creation, these assertions assume 005 ran
// with this code in place (the suite always re-runs 005 after DB cleanup).
//
// NOTE: this spec was authored without a live environment; selectors/labels were
// taken from 005/006 and the element language constants, but should be verified
// and tuned against the running app on first execution.

const NON_ADMIN = { username: 'curator1', password: '12345' };

// Caption text of the webmaster-only fields (modules/formulize/language/english/main.php).
const ADMIN_ONLY_CAPTIONS = ['Account Status', 'Group Memberships', 'Masquerade'];

// ---- A. Legacy profile-module entry point redirects to /edituser.php --------
test('legacy modules/profile/edituser.php redirects to the unified form', async ({ page }) => {
	await login(page, NON_ADMIN.username, NON_ADMIN.password);
	await page.goto('/modules/profile/edituser.php');
	await page.waitForLoadState('networkidle');
	// The handoff is a redirect to the root self-service page.
	await expect(page).toHaveURL(/\/edituser\.php(\?.*)?$/);
	// Wait for the inner form to be revealed by JS (it starts display:none).
	await expect(page.locator('#formulizeform form')).toBeVisible();
});

// ---- B. Non-webmaster self-service: self-editable fields shown, ------------
//         admin-only fields hidden
test('non-webmaster sees self-editable fields but not admin-only fields', async ({ page }) => {
	await login(page, NON_ADMIN.username, NON_ADMIN.password);
	await page.goto('/edituser.php');
	await page.waitForLoadState('networkidle');

	// Wait for the inner form to be revealed by JS (it starts display:none).
	await expect(page.locator('#formulizeform form')).toBeVisible();

	// Self-editable fields are present (Email + a password field at minimum).
	// getByRole uses the accessibility tree, which requires the form to be visible first (done above).
	await expect(page.getByRole('textbox', { name: 'Email Address' })).toBeVisible();
	await expect(page.locator('#formulizeform input[type="password"]').first()).toBeVisible();

	// Webmaster-only fields must NOT be rendered (hidden by ele_display).
	for (const caption of ADMIN_ONLY_CAPTIONS) {
		await expect(page.locator('#formulizeform').getByText(caption, { exact: false }))
			.toHaveCount(0);
	}
});

// ---- C. Contrast/regression: a webmaster DOES see the admin-only fields -----
// The admin account has no EAU entry, so /edituser.php resolves to the System
// Users form for the admin's own uid; as a webmaster the adminOnly fields render.
test('webmaster self-service still shows the admin-only fields', async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await page.goto('/edituser.php');
	await page.waitForLoadState('networkidle');

	// Wait for the inner form to be revealed by JS (it starts display:none).
	await expect(page.locator('#formulizeform form')).toBeVisible();
	await expect(page.locator('#formulizeform').getByText('Account Status', { exact: false }))
		.not.toHaveCount(0);
});

// ---- D. Self-edit cannot escalate privileges --------------------------------
// users.php is webmaster-only. A non-webmaster must not be able to reach it
// before or after saving their own account through /edituser.php — proving the
// self-service save grants no group/permission escalation. (A finer-grained
// forged-field probe — injecting decue_/de_ for the Group Memberships element
// and asserting the groups_users_link rows are unchanged — should be added once
// the live element ids are known; this end-to-end check needs none of that.)
test('self-edit does not grant webmaster access', async ({ page }) => {
	await login(page, NON_ADMIN.username, NON_ADMIN.password);

	const usersPageDenied = async () => {
		await page.goto('/modules/formulize/users.php');
		await page.waitForLoadState('networkidle');
		// The page redirects non-webmasters away; the user-management list never renders.
		await expect(page.getByText('permission to manage users', { exact: false }).first())
			.toBeVisible()
			.catch(async () => {
				// Some redirects land on the home/login page instead of showing the message;
				// in that case simply assert we are NOT on a rendered users list.
				await expect(page.locator('input[name="oldcols"]')).toHaveCount(0);
			});
	};

	// Perform a normal self-edit save (touch the phone field, then save).
	await page.goto('/edituser.php');
	await page.waitForLoadState('networkidle');
	// Wait for the inner form to be revealed by JS (it starts display:none).
	await expect(page.locator('#formulizeform form')).toBeVisible();
	const phone = page.getByRole('textbox', { name: 'Phone Number' });
	const phoneCount = await phone.count();
	if (phoneCount) {
		await phone.first().fill('5551234567');
	}
	await saveFormulizeForm(page);
	// After save the form reloads; wait for it to be visible again before asserting values.
	await expect(page.locator('#formulizeform form')).toBeVisible();
	if (phoneCount) {
		// expect the phone number to appear in the Phone Number field when the page reappears
		await expect(page.getByRole('textbox', { name: 'Phone Number' }).first()).toHaveValue('555-123-4567');
	}
	// expect the user to be denied the users.php page
	await usersPageDenied();
});
