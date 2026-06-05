const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import {
	login,
	saveAdminForm,
	saveFormulizeForm,
	waitForAdminPageReady,
	waitForWorkingMessage,
	addElementForm,
	ElementType,
	enableEntriesAreUsers,
	enableEntriesAreGroups,
	addEAGCategory,
	addDefaultGroupToEAUForm,
	linkTemplateGroupToElement,
	showConditionsPanel,
	addConditionToPerGroupPanel,
	getFidFromFormAdminPage,
	getFidFromListPage,
} from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// Phase 1 of the EAU/EAG test plan.
// Replaces the legacy approach of creating groups and users via the XOOPS
// admin pages. Instead we create a Departments EAG form (which produces
// template + entry groups via category mechanics) and a Staff EAU form
// (which creates XOOPS users from its entries and auto-assigns group
// memberships via element-link + per-group conditions). Downstream tests
// (010, 015, 020, 025) continue to use the same four user logins.

// ---------- Shared state across this spec file ----------
// Test specs in this suite run sequentially (playwright.config.js:
// fullyParallel:false, workers:1) so we can lean on a module-scoped object
// to remember fids that earlier tests created. This is the same pattern the
// existing suite relies on implicitly via "downstream tests assume earlier
// state exists in the DB" — here we make it explicit by passing fids
// through this object.
const phase1 = {
	departmentsFid: null,
	staffFid: null,
	// Group ids returned by the default-groups autocomplete. Recorded as
	// strings (the value of data-groupid). Keys are the EAU default-group
	// names for readability.
	defaultGroupIds: {},
};

// ============================================================
// Block A — Create Departments form (EAG) with two categories
// ============================================================
test.describe('A. Create Departments form (EAG)', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.getByRole('link', { name: 'Admin' }).click();
	});

	test('Create Departments EAG form with Staff and Curators categories', async ({ page }) => {
		await expect(page.getByRole('link', { name: 'Create a new form' })).toBeVisible();
		await page.getByRole('link', { name: 'Create a new form' }).click();
		await waitForAdminPageReady(page);
		await expect(page.locator('input[name="forms-form_title"]')).toBeVisible();

		await page.getByRole('textbox', { name: 'Form title:' }).fill('Departments');
		await page.locator('#applications-name').fill('Staff Management');
		await page.locator('input[name="pi_new_caption"]').fill('Name');

		await enableEntriesAreGroups(page);
		await addEAGCategory(page, 'Staff');
		await addEAGCategory(page, 'Curators');

		await saveAdminForm(page);

		phase1.departmentsFid = await getFidFromFormAdminPage(page);
		expect(phase1.departmentsFid).toBeGreaterThan(0);
	});

	test('Departments template groups appear on the Groups admin page', async ({ page }) => {
		await page.goto('/modules/formulize/groups.php');
		await expect(page.getByRole('rowgroup').first()).toContainText('Departments - All Users');
		await expect(page.getByRole('rowgroup').first()).toContainText('Departments - Staff');
		await expect(page.getByRole('rowgroup').first()).toContainText('Departments - Curators');
	});
});

// ============================================================
// Block B — Add Departments entries → produces entry groups
// ============================================================
test.describe('B. Add Departments entries (creates Ancient History + Modern History groups)', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		// We rely on phase1.departmentsFid set by Block A. master.php shows
		// the raw default list screen with the "Add Departments" button.
		expect(phase1.departmentsFid, 'Departments form must have been created in Block A').toBeTruthy();
		await page.goto(`/modules/formulize/master.php?fid=${phase1.departmentsFid}`);
	});

	test('Add Ancient History entry', async ({ page }) => {
		await page.locator('#formulize_addButton').click();
		await page.getByRole('textbox', { name: 'Name' }).fill('Ancient History');
		await saveFormulizeForm(page, 'Save');
	});

	test('Add Modern History entry', async ({ page }) => {
		await page.locator('#formulize_addButton').click();
		await page.getByRole('textbox', { name: 'Name' }).fill('Modern History');
		await saveFormulizeForm(page, 'Save');
	});

	test('Six entry groups (Ancient/Modern × All Users/Staff/Curators) exist', async ({ page }) => {
		await page.goto('/modules/formulize/groups.php');
		// Increase the per-page list if necessary by switching to All
		// (default may paginate). Asserting against the rowgroup text body.
		const list = page.getByRole('rowgroup').first();
		await expect(list).toContainText('Ancient History - All Users');
		await expect(list).toContainText('Ancient History - Staff');
		await expect(list).toContainText('Ancient History - Curators');
		await expect(list).toContainText('Modern History - All Users');
		await expect(list).toContainText('Modern History - Staff');
		await expect(list).toContainText('Modern History - Curators');
	});
});

// ============================================================
// Block C — Create flat groups All Staff / All Curators via groups.php
// ============================================================
test.describe('C. Create flat groups via the Formulize Groups admin page', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.goto('/modules/formulize/groups.php');
	});

	test('Create All Staff group', async ({ page }) => {
		await page.locator('#formulize_addButton').click();
		// The Group Name virtual element renders as a text input labelled
		// "Group Name" on the new-entry form for the groups table form.
		await page.getByRole('textbox', { name: 'Group Name' }).fill('All Staff');
		await saveFormulizeForm(page, 'Save');
		await expect(page.getByRole('rowgroup').first()).toContainText('All Staff');
	});

	test('Create All Curators group', async ({ page }) => {
		await page.locator('#formulize_addButton').click();
		await page.getByRole('textbox', { name: 'Group Name' }).fill('All Curators');
		await saveFormulizeForm(page, 'Save');
		await expect(page.getByRole('rowgroup').first()).toContainText('All Curators');
	});
});

// ============================================================
// Block D — Create Staff form (EAU) and its non-userAccount elements
// (userAccount elements get auto-injected on the form_type=users save)
// ============================================================
test.describe('D. Create Staff EAU form and its custom elements', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.getByRole('link', { name: 'Admin' }).click();
	});

	test('Create Staff form and toggle entries-are-users', async ({ page }) => {
		await page.getByRole('link', { name: 'Create a new form' }).click();
		await waitForAdminPageReady(page);
		await page.getByRole('textbox', { name: 'Form title:' }).fill('Staff');
		// Since the "Staff Management" application already exists (created
		// in Block A when there were zero apps), the new-form panel renders
		// with #new-app-no already checked and the apps listbox visible.
		// Just pick the existing app by visible label.
		await page.locator('#apps').selectOption({ label: 'Staff Management' });
		await page.locator('input[name="pi_new_caption"]').fill('Login name');
		await enableEntriesAreUsers(page);
		await saveAdminForm(page);

		phase1.staffFid = await getFidFromFormAdminPage(page);
		expect(phase1.staffFid).toBeGreaterThan(0);
	});

	test('userAccount elements were auto-injected', async ({ page }) => {
		// Navigate directly to the Staff form's Elements tab.
		await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${phase1.staffFid}&tab=elements`);
		await waitForAdminPageReady(page);
		// Check a couple of representative auto-injected elements appear in
		// the elements accordion. These come from createUserAccountElements()
		// in modules/formulize/class/forms.php.
		await expect(page.getByText('Username', { exact: false })).toBeVisible();
		await expect(page.getByText('Password', { exact: false })).toBeVisible();
		await expect(page.getByText('Email Address', { exact: false })).toBeVisible();
		await expect(page.getByText('Full Name', { exact: false })).toBeVisible();
	});

	test('Add Department element (linked-checkbox → Departments form, multi-select)', async ({ page }) => {
		await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${phase1.staffFid}&tab=elements`);
		await waitForAdminPageReady(page);
		await addElementForm(page, ElementType.checkboxLinked);
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Department');
		await page.locator('input[name="elements-ele_handle"]').fill('staff_department');
		// Source the linked list from Departments / Name. The element-edit
		// page exposes the combined "Form: Element" picker as #formlink
		// (same pattern existing tests use for linked-checkbox elements —
		// see 010-create-forms-and-elements.spec.js:643).
		await page.getByRole('link', { name: 'Options' }).click();
		await page.locator('#formlink').selectOption('Departments: Name');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Department');
	});

	test('Add Is curator? element (yes/no radio)', async ({ page }) => {
		await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${phase1.staffFid}&tab=elements`);
		await waitForAdminPageReady(page);
		await addElementForm(page, ElementType.radioYN);
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Is curator?');
		await page.locator('input[name="elements-ele_handle"]').fill('staff_is_curator');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Is curator?');
	});
});

// ============================================================
// Block E — Configure EAU default groups + element links + conditions
// ============================================================
test.describe('E. Configure Staff EAU default groups, element links, conditions', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		expect(phase1.staffFid, 'Staff form must exist from Block D').toBeTruthy();
		await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${phase1.staffFid}&tab=settings`);
		await waitForAdminPageReady(page);
	});

	test('Add five default groups with element links', async ({ page }) => {
		// Template groups (linked to staff_department) — these auto-resolve
		// to the per-department entry groups for each user.
		phase1.defaultGroupIds.deptsAll = await addDefaultGroupToEAUForm(page, 'Departments - All Users');
		phase1.defaultGroupIds.deptsStaff = await addDefaultGroupToEAUForm(page, 'Departments - Staff');
		phase1.defaultGroupIds.deptsCurators = await addDefaultGroupToEAUForm(page, 'Departments - Curators');
		// Flat groups (no element link).
		phase1.defaultGroupIds.allStaff = await addDefaultGroupToEAUForm(page, 'All Staff');
		phase1.defaultGroupIds.allCurators = await addDefaultGroupToEAUForm(page, 'All Curators');

		// Link the three template groups to the staff_department element.
		// The eagFormId is the Departments form id (phase1.departmentsFid).
		await linkTemplateGroupToElement(page, phase1.departmentsFid, 'Department');

		await saveAdminForm(page);
	});

	test('Set per-group conditions (Curators groups Yes; Staff groups No)', async ({ page }) => {
		// Open all four panels that need conditions, then add them one by
		// one (each addcon click is its own save+reload — see helper).
		await showConditionsPanel(page, phase1.defaultGroupIds.deptsStaff);
		await addConditionToPerGroupPanel(page, phase1.defaultGroupIds.deptsStaff, 'Is curator?', '=', 'No');

		// After the previous save the page reloaded; the open-panels list is
		// preserved via #open_group_conditions_panels but the panel JS state
		// is re-initialised — re-open the next one explicitly.
		await showConditionsPanel(page, phase1.defaultGroupIds.deptsCurators);
		await addConditionToPerGroupPanel(page, phase1.defaultGroupIds.deptsCurators, 'Is curator?', '=', 'Yes');

		await showConditionsPanel(page, phase1.defaultGroupIds.allStaff);
		await addConditionToPerGroupPanel(page, phase1.defaultGroupIds.allStaff, 'Is curator?', '=', 'No');

		await showConditionsPanel(page, phase1.defaultGroupIds.allCurators);
		await addConditionToPerGroupPanel(page, phase1.defaultGroupIds.allCurators, 'Is curator?', '=', 'Yes');

		// Sanity check: each conditional default group reports a condition
		// count of at least 1 after the round-trips.
		for (const groupId of [
			phase1.defaultGroupIds.deptsStaff,
			phase1.defaultGroupIds.deptsCurators,
			phase1.defaultGroupIds.allStaff,
			phase1.defaultGroupIds.allCurators,
		]) {
			const count = await page
				.locator(`.toggle-group-conditions-btn[data-groupid="${groupId}"]`)
				.getAttribute('data-condition-count');
			expect(parseInt(count, 10)).toBeGreaterThanOrEqual(1);
		}
	});
});

// ============================================================
// Block F — Restrict Staff form permissions to Webmasters + All Curators
// ============================================================
test.describe('F. Set Staff form permissions (Webmasters + All Curators)', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		expect(phase1.staffFid).toBeTruthy();
		await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${phase1.staffFid}&tab=permissions`);
		await waitForAdminPageReady(page);
	});

	test('Grant Webmasters + All Curators (with view-private-elements) on Staff', async ({ page }) => {
		// The permissions admin uses #groups (a multi-select listbox) to
		// pick the groups whose perms we want to edit, then a button shows
		// their permission panels.
		await page.locator('#groups').selectOption(['Webmasters', 'All Curators']);
		await page.getByRole('button', { name: /Show permissions for these/i }).click();
		await waitForAdminPageReady(page);

		// Within the All Curators group panel, check the view_form,
		// add_own_entry, edit_own_entry, and view_private_elements
		// checkboxes. We address them by the standard
		// `{fid}_{groupId}_{perm}` name pattern. Curator group id is
		// looked up from the panel header by its visible name.
		const allCuratorsPanel = page.locator('fieldset').filter({ has: page.locator('legend').filter({ hasText: /^All Curators/ }) });
		await allCuratorsPanel.locator('input[name$="_view_form"]').check();
		await allCuratorsPanel.locator('input[name$="_add_own_entry"]').check();
		await allCuratorsPanel.locator('input[name$="_edit_own_entry"]').check();
		// "View private elements" — the exact perm name varies; match by label text.
		await allCuratorsPanel.getByLabel(/View.*private.*element/i).check();

		// Webmasters get the lot.
		const webmastersPanel = page.locator('fieldset').filter({ has: page.locator('legend').filter({ hasText: /^Webmasters/ }) });
		await webmastersPanel.locator('input[type="checkbox"]').evaluateAll(checkboxes => {
			checkboxes.forEach(cb => { if (!cb.disabled) cb.checked = true; });
		});

		await saveAdminForm(page);
	});
});

// ============================================================
// Block G — Create the four users via Add Staff entries
// ============================================================
test.describe('G. Create Staff entries (= users) via the Staff EAU form', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		expect(phase1.staffFid).toBeTruthy();
		await page.goto(`/modules/formulize/master.php?fid=${phase1.staffFid}`);
	});

	test('Create ahstaff (Ancient History Staff, is_curator=No)', async ({ page }) => {
		await page.locator('#formulize_addButton').click();
		await fillStaffEntry(page, {
			username: 'ahstaff',
			fullName: 'Ancient History Staff',
			email: 'ahstaff@museum.formulize.net',
			password: '12345',
			departments: ['Ancient History'],
			isCurator: 'No',
		});
		await saveFormulizeForm(page, 'Save');
	});

	test('Create mhstaff (Modern History Staff, is_curator=No)', async ({ page }) => {
		await page.locator('#formulize_addButton').click();
		await fillStaffEntry(page, {
			username: 'mhstaff',
			fullName: 'Modern History Staff',
			email: 'mhstaff@museum.formulize.net',
			password: '12345',
			departments: ['Modern History'],
			isCurator: 'No',
		});
		await saveFormulizeForm(page, 'Save');
	});

	test('Create curator1 (Curator One, is_curator=Yes, both departments)', async ({ page }) => {
		await page.locator('#formulize_addButton').click();
		await fillStaffEntry(page, {
			username: 'curator1',
			fullName: 'Curator One',
			email: 'c1@museum.formulize.net',
			password: '12345',
			departments: ['Ancient History', 'Modern History'],
			isCurator: 'Yes',
		});
		await saveFormulizeForm(page, 'Save');
	});

	test('Create curator2 (Curator Two, is_curator=Yes, both departments)', async ({ page }) => {
		await page.locator('#formulize_addButton').click();
		await fillStaffEntry(page, {
			username: 'curator2',
			fullName: 'Curator Two',
			email: 'c2@museum.formulize.net',
			password: '12345',
			departments: ['Ancient History', 'Modern History'],
			isCurator: 'Yes',
		});
		await saveFormulizeForm(page, 'Save');
	});
});

// Local helper for the four near-identical entry-creation flows above.
async function fillStaffEntry(page, { username, fullName, email, password, departments, isCurator }) {
	await page.getByRole('textbox', { name: 'Username' }).fill(username);
	await page.getByRole('textbox', { name: 'Full Name' }).fill(fullName);
	await page.getByRole('textbox', { name: 'Email Address' }).fill(email);
	// Password element renders two inputs (password + pw_two confirm).
	const passwordInputs = page.locator('input[type="password"]');
	await passwordInputs.nth(0).fill(password);
	await passwordInputs.nth(1).fill(password);
	for (const dept of departments) {
		await page.getByRole('checkbox', { name: dept, exact: true }).check();
	}
	await page.getByRole('radio', { name: isCurator, exact: true }).check();
}

// ============================================================
// Block H — Verify auto-assignment + template-groups-empty rule
// ============================================================
test.describe('H. Verify automatic group assignment', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.goto('/modules/formulize/groups.php');
	});

	test('Template groups have no members (design rule)', async ({ page }) => {
		// On the Groups page, the member-count column should show 0 for the
		// three template groups. We click into each by name to verify.
		for (const templateName of [
			'Departments - All Users',
			'Departments - Staff',
			'Departments - Curators',
		]) {
			const row = page.getByRole('row', { name: new RegExp(templateName) });
			await expect(row).toBeVisible();
			// The group_members column shows a list of members or zero.
			// Match a zero-members indicator (the standard column shows
			// either "0" or no usernames). We look for the row not
			// containing any of our test usernames.
			const rowText = await row.innerText();
			expect(rowText).not.toMatch(/ahstaff|mhstaff|curator1|curator2/);
		}
	});

	test('Ancient History - Staff has only ahstaff', async ({ page }) => {
		const row = page.getByRole('row', { name: /Ancient History - Staff/ });
		await expect(row).toContainText('ahstaff');
		await expect(row).not.toContainText('mhstaff');
		await expect(row).not.toContainText('curator1');
	});

	test('Ancient History - Curators has curator1 and curator2', async ({ page }) => {
		const row = page.getByRole('row', { name: /Ancient History - Curators/ });
		await expect(row).toContainText('curator1');
		await expect(row).toContainText('curator2');
		await expect(row).not.toContainText('ahstaff');
	});

	test('Modern History - Staff has only mhstaff', async ({ page }) => {
		const row = page.getByRole('row', { name: /Modern History - Staff/ });
		await expect(row).toContainText('mhstaff');
		await expect(row).not.toContainText('ahstaff');
		await expect(row).not.toContainText('curator1');
	});

	test('Modern History - Curators has curator1 and curator2', async ({ page }) => {
		const row = page.getByRole('row', { name: /Modern History - Curators/ });
		await expect(row).toContainText('curator1');
		await expect(row).toContainText('curator2');
		await expect(row).not.toContainText('mhstaff');
	});

	test('All Staff has ahstaff and mhstaff but no curators', async ({ page }) => {
		const row = page.getByRole('row', { name: /^All Staff/ });
		await expect(row).toContainText('ahstaff');
		await expect(row).toContainText('mhstaff');
		await expect(row).not.toContainText('curator1');
		await expect(row).not.toContainText('curator2');
	});

	test('All Curators has curator1 and curator2 but no staff', async ({ page }) => {
		const row = page.getByRole('row', { name: /^All Curators/ });
		await expect(row).toContainText('curator1');
		await expect(row).toContainText('curator2');
		await expect(row).not.toContainText('ahstaff');
		await expect(row).not.toContainText('mhstaff');
	});
});

// ============================================================
// Block I — New admin Users page UI coverage
// ============================================================
test.describe('I. New Users admin page UI', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.goto('/modules/formulize/users.php');
	});

	test('Search by username filters the user list', async ({ page }) => {
		const fid = await getFidFromListPage(page);
		const searchInput = page.locator(`input[name="search_formulize_user_account_username_${fid}"]`);
		await searchInput.fill('curator');
		// Trigger the change handler that submits the implicit filter.
		await searchInput.dispatchEvent('change');
		await waitForWorkingMessage(page);
		await expect(page.getByText('curator1', { exact: false })).toBeVisible();
		await expect(page.getByText('curator2', { exact: false })).toBeVisible();
		await expect(page.getByText('ahstaff', { exact: false })).not.toBeVisible();
	});

	test('EAU type filter narrows to Staff users only', async ({ page }) => {
		await page.locator('#search_eau_type').selectOption({ label: /Staff/ });
		await waitForWorkingMessage(page);
		// All four test users came from the Staff form, so all should
		// remain visible; the unfiltered list also contained the seed admin
		// user — that one should now be hidden.
		await expect(page.getByText('ahstaff', { exact: false })).toBeVisible();
		await expect(page.getByText('Ancient History Staff', { exact: false })).toBeVisible();
		await expect(page.getByText('admin', { exact: false })).not.toBeVisible();
	});

	test('Delete user flow removes a throwaway user', async ({ page }) => {
		// 1. Create a throwaway "tempuser" via Add Staff (master.php route).
		await page.goto(`/modules/formulize/master.php?fid=${phase1.staffFid}`);
		await page.locator('#formulize_addButton').click();
		await fillStaffEntry(page, {
			username: 'tempuser',
			fullName: 'Temp User',
			email: 'temp@museum.formulize.net',
			password: '12345',
			departments: ['Ancient History'],
			isCurator: 'No',
		});
		await saveFormulizeForm(page, 'Save');

		// 2. Go to the Users admin page, find the row, check delete, confirm.
		await page.goto('/modules/formulize/users.php');
		const row = page.getByRole('row', { name: /tempuser/ });
		await expect(row).toBeVisible();
		await row.locator('input[name^="delete_"]').check();
		// confirmDel() pops a native confirm dialog — pre-accept it.
		page.once('dialog', d => d.accept());
		await page.getByRole('button', { name: /Delete User/i }).click();
		await waitForWorkingMessage(page);

		// 3. Reload, assert gone.
		await page.goto('/modules/formulize/users.php');
		await expect(page.getByText('tempuser')).not.toBeVisible();
	});
});

// ============================================================
// Block K — Enforcement contract (per-group conditions hold on save)
// ============================================================
// These tests verify the bidirectional enforcement contract documented in
// modules/formulize/include/readelements.php:672,694 — when a user is
// removed from a condition-driven group, the next save re-adds them.
//
// NOTE: the userAccountGroupMembership element's autocomplete-tag-remove
// markup and the eagGroupMembers element's remove-member UI were not
// directly observed in the planning pass; these selectors are best-effort
// and may need adjustment on first run. See plan section "Block 1.6" and
// the userAccountGroupMembershipElement.php / eagGroupMembersElement.php
// classes for the rendering details.
test.describe('K. Enforcement of per-group conditions', () => {

	test('Removing All Curators from curator1\'s entry is reverted on save', async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.goto(`/modules/formulize/master.php?fid=${phase1.staffFid}`);
		await page.getByRole('row', { name: /curator1/ }).getByRole('link').first().click();

		// The Group Membership element renders selected groups as tags with
		// a remove link per group (typical formulize autocomplete pattern).
		// Locate the "All Curators" tag inside that element's container and
		// click its remove link. The container is identified by a label
		// "Group Membership" or the element handle.
		const groupMembershipBlock = page.locator('.formulize-label-formulize_user_account_groupmembership_' + phase1.staffFid).locator('..');
		const allCuratorsTag = groupMembershipBlock.locator('text=All Curators').locator('..');
		await allCuratorsTag.getByRole('link').click();
		await saveFormulizeForm(page, 'Save');

		// Reload the entry and verify All Curators is back.
		await page.goto(`/modules/formulize/master.php?fid=${phase1.staffFid}`);
		await page.getByRole('row', { name: /curator1/ }).getByRole('link').first().click();
		await expect(page.locator('.formulize-label-formulize_user_account_groupmembership_' + phase1.staffFid).locator('..')).toContainText('All Curators');
	});

	test('Removing curator1 from All Curators group is reverted on save', async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.goto('/modules/formulize/groups.php');
		await page.getByRole('row', { name: /^All Curators/ }).getByRole('link').first().click();

		// The eagGroupMembers element on a group entry exposes the member
		// list with a remove link per member. Click curator1's remove.
		const memberList = page.locator('.formulize-eag-group-members, [class*="group-members"]').first();
		const curator1Tag = memberList.locator('text=curator1').locator('..');
		await curator1Tag.getByRole('link').click();
		await saveFormulizeForm(page, 'Save');

		// Reload the group and verify curator1 is still listed.
		await page.goto('/modules/formulize/groups.php');
		await page.getByRole('row', { name: /^All Curators/ }).getByRole('link').first().click();
		await expect(page.locator('body')).toContainText('curator1');
	});
});

// ============================================================
// Block J — Visibility of the Users/Groups admin pages by role
// ============================================================
test.describe('J. Frontend menu visibility for Users/Groups', () => {

	test('Webmasters can reach users.php directly', async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.goto('/modules/formulize/users.php');
		await expect(page.locator('#formulize_addButton')).toBeVisible();
	});

	test('Non-webmaster is redirected away from users.php', async ({ page }) => {
		// ahstaff was just created in Block G. Their permissions don't
		// include system_admin on XOOPS_SYSTEM_USER, so users.php should
		// redirect them.
		await login(page, 'ahstaff', '12345');
		const response = await page.goto('/modules/formulize/users.php', { waitUntil: 'domcontentloaded' });
		// The redirect_header() at users.php:42-44 sends them away with a
		// "no permission" message. The destination is the site root, so we
		// assert we're no longer on users.php after the redirect resolves.
		await page.waitForLoadState('networkidle');
		await expect(page).not.toHaveURL(/users\.php(?:[?#]|$)/);
	});
});
