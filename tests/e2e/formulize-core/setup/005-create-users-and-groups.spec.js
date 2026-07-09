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
	clearEntryLocks,
	ensureMainMenuOpen,
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
		// Navigate directly — the "Admin" link destination varies by DB state
		// (it points to a screen-specific admin once any form becomes the startpage,
		// and with a fresh DB the module may redirect to the admin area itself).
		// Direct navigation is the pattern Block D already uses.
		await page.goto('/modules/formulize/admin/ui.php?page=home');
		await waitForAdminPageReady(page);
	});

	test('Create Departments EAG form with Staff and Curators categories', async ({ page }) => {
		await expect(page.getByRole('link', { name: 'Create a new form' })).toBeVisible();
		await page.getByRole('link', { name: 'Create a new form' }).click();
		await waitForAdminPageReady(page);
		await expect(page.locator('input[name="forms-form_title"]')).toBeVisible();

		await page.getByRole('textbox', { name: 'Form title:' }).fill('Departments');
		await page.locator('#applications-name').fill('Staff Management');
		// Select the "Create new PI element" radio so the backend actually
		// creates the Name textbox. Filling the caption without this leaves
		// pi_new_yes_no at its default "no" value.
		await page.locator('#pi-new-yes').click();
		await page.locator('input[name="pi_new_caption"]').fill('Name');

		await enableEntriesAreGroups(page);
		await addEAGCategory(page, 'Staff');
		await addEAGCategory(page, 'Curators');

		await saveAdminForm(page);

		phase1.departmentsFid = await getFidFromFormAdminPage(page);
		expect(phase1.departmentsFid).toBeGreaterThan(0);
	});

	test('Departments template groups appear on the Groups admin page', async ({ page }) => {
		// Navigate twice: the first load triggers ensureGroupsTableForm() first-time
		// DB setup (known first-load quirk where the headerlist may not render
		// correctly); the second load picks up the persisted form config.
		await page.goto('/modules/formulize/groups.php');
		await page.waitForLoadState('domcontentloaded');
		await page.goto('/modules/formulize/groups.php');
		// EAG template groups show as a single merged row per form (not one row
		// per category). The form name appears in a .template-group-form-name
		// element and the configured categories appear in a .main-cell-list ul.
		await expect(page.locator('.template-group-form-name')).toContainText('Departments');
		const categoryList = page.locator('.main-cell-list').first();
		await expect(categoryList).toContainText('All Users');
		await expect(categoryList).toContainText('Staff');
		await expect(categoryList).toContainText('Curators');
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
		await clearEntryLocks(page);
	});

	test('Add Modern History entry', async ({ page }) => {
		await page.locator('#formulize_addButton').click();
		await page.getByRole('textbox', { name: 'Name' }).fill('Modern History');
		await saveFormulizeForm(page, 'Save');
		await clearEntryLocks(page);
	});

	test('Six entry groups (Ancient/Modern × All Users/Staff/Curators) exist', async ({ page }) => {
		await page.goto('/modules/formulize/groups.php');
		// Entry groups are NOT shown as individual rows. Instead they are embedded
		// inside the Departments template-group merged row: the Members cell shows
		// each EAG entry as "Entry Name — N members". Seeing both entries there
		// confirms syncEntryGroups() ran successfully, creating 3 groups per entry
		// (2 entries × 3 categories = 6 groups total).
		const list = page.getByRole('rowgroup').last();
		await expect(list).toContainText('Ancient History');
		await expect(list).toContainText('Modern History');
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
		// After saving a new group the entry form stays open (edit view of the
		// new group). Navigate back to the list to assert the group appears.
		await page.goto('/modules/formulize/groups.php');
		await page.waitForLoadState('networkidle');
		await expect(page.getByRole('rowgroup').last()).toContainText('All Staff');
	});

	test('Create All Curators group', async ({ page }) => {
		await page.locator('#formulize_addButton').click();
		await page.getByRole('textbox', { name: 'Group Name' }).fill('All Curators');
		await saveFormulizeForm(page, 'Save');
		await page.goto('/modules/formulize/groups.php');
		await page.waitForLoadState('networkidle');
		await expect(page.getByRole('rowgroup').last()).toContainText('All Curators');
	});
});

// ============================================================
// Block D — Create Staff form (EAU) and its non-userAccount elements
// (userAccount elements get auto-injected on the form_type=users save)
// ============================================================
test.describe('D. Create Staff EAU form and its custom elements', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		// Navigate directly to the admin home rather than clicking the "Admin"
		// link: once the Departments form exists its default screen (sid=2)
		// becomes the module startpage, so the "Admin" link on that front-end
		// page points to the screen-specific admin rather than the home.
		await page.goto('/modules/formulize/admin/ui.php?page=home');
		await waitForAdminPageReady(page);
	});

	test('Create Staff form and toggle entries-are-users', async ({ page }) => {
		// The admin home shows apps as collapsed accordions once any form exists.
		// Expand the Staff Management accordion to reveal its 'Create a new form' link.
		// The link's URL encodes aid=1 so the new form is auto-assigned to that app.
		await page.getByRole('link', { name: 'Application: Staff Management' }).click();
		await page.getByRole('link', { name: 'Create a new form' }).click();
		await waitForAdminPageReady(page);
		await page.getByRole('textbox', { name: 'Form title:' }).fill('Staff');
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
		// Elements appear as accordion tabs whose accessible name starts with the
		// element caption then describes the type and handle. Use role='tab' +
		// a regex anchored at the start of the caption to avoid matching the
		// many other elements on the page that also contain these words.
		await expect(page.getByRole('tab', { name: /^Username / })).toBeVisible();
		await expect(page.getByRole('tab', { name: /^Password / })).toBeVisible();
		await expect(page.getByRole('tab', { name: /^Email Address / })).toBeVisible();
		await expect(page.getByRole('tab', { name: /^Full Name / })).toBeVisible();
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

	test('Configure Staff multipage screen pages to include all entry fields', async ({ page }) => {
		// The multipage screen is auto-created with one empty page. Elements added after
		// screen creation (Department, Is curator?) are not on any page. Assign all
		// form elements to page 0 so the entry form shows everything on one page.
		await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${phase1.staffFid}&tab=screens`);
		await waitForAdminPageReady(page);

		// The first configscreen link is the multipage screen (listed before listOfEntries screens).
		const multiPageSid = await page.locator('a.configscreen').first().getAttribute('target-sid');
		expect(multiPageSid).toBeTruthy();

		// Navigate directly to the screen's Pages tab.
		await page.goto(`/modules/formulize/admin/ui.php?page=screen&fid=${phase1.staffFid}&sid=${multiPageSid}&tab=pages`);
		await waitForAdminPageReady(page);

		// Edit page 0: select ALL options so every element (including Department
		// and Is curator? which were added after screen creation) is on this page.
		await page.locator('a[name="editpage"]').first().click();
		await expect(page.locator('#dialog-page-settings-content select[multiple]')).toBeVisible();

		const page0Select = page.locator('#dialog-page-settings-content select[multiple]');
		const allOptionValues = await page0Select.evaluate(sel =>
			Array.from(sel.options).map(o => o.value)
		);
		await page0Select.selectOption(allOptionValues);

		const save0 = page.waitForResponse(resp => resp.url().includes('save.php'));
		await page.locator('#dialog-page-settings-content #savebuttonpopup').click();
		await save0;

		await page.locator('.ui-dialog:has(#dialog-page-settings-content) .ui-dialog-titlebar-close').click();
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
		await allCuratorsPanel.locator('input[name$="_update_own_entry"]').check();
		await allCuratorsPanel.locator('input[name$="_view_private_elements"]').check();

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
			loginName: 'ahstaff',
			firstName: 'Ancient History',
			lastName: 'Staff',
			email: 'ahstaff@museum.formulize.net',
			password: '12345',
			departments: ['Ancient History'],
			isCurator: 'No',
		});
		await saveFormulizeForm(page, 'Save');
		await clearEntryLocks(page);
	});

	test('Create mhstaff (Modern History Staff, is_curator=No)', async ({ page }) => {
		await page.locator('#formulize_addButton').click();
		await fillStaffEntry(page, {
			username: 'mhstaff',
			loginName: 'mhstaff',
			firstName: 'Modern History',
			lastName: 'Staff',
			email: 'mhstaff@museum.formulize.net',
			password: '12345',
			departments: ['Modern History'],
			isCurator: 'No',
		});
		await saveFormulizeForm(page, 'Save');
		await clearEntryLocks(page);
	});

	test('Create curator1 (Curator One, is_curator=Yes, both departments)', async ({ page }) => {
		await page.locator('#formulize_addButton').click();
		await fillStaffEntry(page, {
			username: 'curator1',
			loginName: 'curator1',
			firstName: 'Curator',
			lastName: 'One',
			email: 'c1@museum.formulize.net',
			password: '12345',
			departments: ['Ancient History', 'Modern History'],
			isCurator: 'Yes',
		});
		await saveFormulizeForm(page, 'Save');
		await clearEntryLocks(page);
	});

	test('Create curator2 (Curator Two, is_curator=Yes, both departments)', async ({ page }) => {
		await page.locator('#formulize_addButton').click();
		await fillStaffEntry(page, {
			username: 'curator2',
			loginName: 'curator2',
			firstName: 'Curator',
			lastName: 'Two',
			email: 'c2@museum.formulize.net',
			password: '12345',
			departments: ['Ancient History', 'Modern History'],
			isCurator: 'Yes',
		});
		await saveFormulizeForm(page, 'Save');
		await clearEntryLocks(page);
	});
});

// Local helper for the four near-identical entry-creation flows above.
// All Staff form fields are on a single multipage screen page.
// The caller is responsible for clicking "Save and Finish" via
// saveFormulizeForm(page, 'Save and Finish').
async function fillStaffEntry(page, { username, loginName, firstName, lastName, email, password, departments, isCurator }) {
	await page.getByRole('textbox', { name: 'Username' }).fill(username);
	// "Login name" is a separate required plain-text element auto-created alongside
	// the userAccount elements when entries-are-users is toggled on.
	await page.getByRole('textbox', { name: 'Login name' }).fill(loginName);
	await page.getByRole('textbox', { name: 'First Name' }).fill(firstName);
	await page.getByRole('textbox', { name: 'Last Name' }).fill(lastName);
	await page.getByRole('textbox', { name: 'Email Address' }).fill(email);
	// Password element renders two password inputs (password + confirm).
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
		// Template groups are shown as a single merged row per EAG form —
		// not as individual rows per category. The row contains the form name
		// and a category list. The Members column must not list any test users
		// because template groups are permission anchors, not membership lists.
		const templateRow = page.locator('tr.entry-row').filter({
			has: page.locator('.template-group-form-name'),
		});
		await expect(templateRow).toBeVisible();
		const rowText = await templateRow.innerText();
		expect(rowText).not.toMatch(/ahstaff|mhstaff|curator1|curator2/);
	});

	test('ahstaff belongs to Ancient History - Staff and All Staff (not Curators)', async ({ page }) => {
		// Entry groups (e.g. "Ancient History - Staff") are NOT shown as individual
		// rows on groups.php — they are merged into the Departments template-group row.
		// Verify group assignments by opening the user's Staff entry and inspecting
		// the userAccountGroupMembership element tags.
		await page.goto(`/modules/formulize/master.php?fid=${phase1.staffFid}`);
		await page.getByRole('row', { name: /ahstaff/ }).getByRole('link').first().click();
		const markupName = `formulize_user_account_groupmembership_${phase1.staffFid}`;
		const groupTags = page.locator(`p.auto_multi_${markupName}`);
		await expect(groupTags.filter({ hasText: 'Ancient History - Staff' })).toBeVisible();
		await expect(groupTags.filter({ hasText: 'All Staff' })).toBeVisible();
		await expect(groupTags.filter({ hasText: 'Ancient History - Curators' })).toHaveCount(0);
		await expect(groupTags.filter({ hasText: 'All Curators' })).toHaveCount(0);
		await clearEntryLocks(page);
	});

	test('curator1 belongs to Curators groups for both departments (not Staff)', async ({ page }) => {
		await page.goto(`/modules/formulize/master.php?fid=${phase1.staffFid}`);
		await page.getByRole('row', { name: /curator1/ }).getByRole('link').first().click();
		const markupName = `formulize_user_account_groupmembership_${phase1.staffFid}`;
		const groupTags = page.locator(`p.auto_multi_${markupName}`);
		await expect(groupTags.filter({ hasText: 'Ancient History - Curators' })).toBeVisible();
		await expect(groupTags.filter({ hasText: 'Modern History - Curators' })).toBeVisible();
		await expect(groupTags.filter({ hasText: 'All Curators' })).toBeVisible();
		await expect(groupTags.filter({ hasText: 'Ancient History - Staff' })).toHaveCount(0);
		await expect(groupTags.filter({ hasText: 'All Staff' })).toHaveCount(0);
		await clearEntryLocks(page);
	});

	test('mhstaff belongs to Modern History - Staff and All Staff (not Curators)', async ({ page }) => {
		await page.goto(`/modules/formulize/master.php?fid=${phase1.staffFid}`);
		await page.getByRole('row', { name: /mhstaff/ }).getByRole('link').first().click();
		const markupName = `formulize_user_account_groupmembership_${phase1.staffFid}`;
		const groupTags = page.locator(`p.auto_multi_${markupName}`);
		await expect(groupTags.filter({ hasText: 'Modern History - Staff' })).toBeVisible();
		await expect(groupTags.filter({ hasText: 'All Staff' })).toBeVisible();
		await expect(groupTags.filter({ hasText: 'Ancient History - Staff' })).toHaveCount(0);
		await expect(groupTags.filter({ hasText: 'Modern History - Curators' })).toHaveCount(0);
		await expect(groupTags.filter({ hasText: 'All Curators' })).toHaveCount(0);
		await clearEntryLocks(page);
	});

	test('curator2 belongs to Curators groups for both departments (not Staff)', async ({ page }) => {
		await page.goto(`/modules/formulize/master.php?fid=${phase1.staffFid}`);
		await page.getByRole('row', { name: /curator2/ }).getByRole('link').first().click();
		const markupName = `formulize_user_account_groupmembership_${phase1.staffFid}`;
		const groupTags = page.locator(`p.auto_multi_${markupName}`);
		await expect(groupTags.filter({ hasText: 'Ancient History - Curators' })).toBeVisible();
		await expect(groupTags.filter({ hasText: 'Modern History - Curators' })).toBeVisible();
		await expect(groupTags.filter({ hasText: 'All Curators' })).toBeVisible();
		await expect(groupTags.filter({ hasText: 'Modern History - Staff' })).toHaveCount(0);
		await expect(groupTags.filter({ hasText: 'All Staff' })).toHaveCount(0);
		await clearEntryLocks(page);
	});

	// Groups.php shows flat-group members by uname (First Name + Last Name), not login_name.
	// So "ahstaff" (login_name) appears as "Ancient History Staff" (uname), etc.
	test('All Staff has Ancient History Staff and Modern History Staff (no Curators)', async ({ page }) => {
		const row = page.getByRole('row', { name: /All Staff/ });
		await expect(row).toContainText('Ancient History Staff');
		await expect(row).toContainText('Modern History Staff');
		await expect(row).not.toContainText('Curator One');
		await expect(row).not.toContainText('Curator Two');
	});

	test('All Curators has Curator One and Curator Two (no Staff)', async ({ page }) => {
		const row = page.getByRole('row', { name: /All Curators/ });
		await expect(row).toContainText('Curator One');
		await expect(row).toContainText('Curator Two');
		await expect(row).not.toContainText('Ancient History Staff');
		await expect(row).not.toContainText('Modern History Staff');
	});
});

// ============================================================
// Block I — New admin Users page UI coverage
// ============================================================
test.describe('I. New Users admin page UI', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		// Navigate twice: the first load triggers ensureUsersTableForm() first-time
		// DB setup; the second load picks up the persisted form config.
		await page.goto('/modules/formulize/users.php');
		await page.waitForLoadState('domcontentloaded');
		await page.goto('/modules/formulize/users.php');
	});

	// users.php renders System Users form elements for every listed user.
	// Clear those admin-owned locks after each test so subsequent tests
	// (including 007's curator1 self-edit) aren't blocked by a stale lock.
	test.afterEach(async ({ page }) => {
		await clearEntryLocks(page);
	});

	test('Search by username filters the user list', async ({ page }) => {
		const fid = await getFidFromListPage(page);
		const searchInput = page.locator(`input[name="search_formulize_user_account_username_${fid}"]`);
		await searchInput.fill('curator');
		// Press Enter to submit the controls form. Use Promise.all to start
		// waitForNavigation before pressing so we don't miss the navigation event.
		await Promise.all([
			page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
			searchInput.press('Enter'),
		]);
		await expect(page.getByText('curator1', { exact: true })).toBeVisible();
		await expect(page.getByText('curator2', { exact: true })).toBeVisible();
		await expect(page.getByText('ahstaff', { exact: true })).not.toBeVisible();
	});

	test('EAU type filter narrows to Staff users only', async ({ page }) => {
		// The dropdown onchange calls showLoading() which submits the controls form.
		// Use Promise.all so waitForNavigation is registered before the change event fires.
		await Promise.all([
			page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
			page.locator('#search_eau_type').selectOption('Staff'),
		]);
		// All four test users came from the Staff form, so all should
		// remain visible; the unfiltered list also contained the seed admin
		// user — that one should now be hidden.
		await expect(page.getByText('ahstaff', { exact: true })).toBeVisible();
		await expect(page.getByText('Ancient History Staff', { exact: false })).toBeVisible();
		await expect(page.getByText('admin', { exact: true })).not.toBeVisible();
	});

	test('Delete user flow removes a throwaway user', async ({ page }) => {
		// 1. Create a throwaway "tempuser" via Add Staff (master.php route).
		await page.goto(`/modules/formulize/master.php?fid=${phase1.staffFid}`);
		await page.locator('#formulize_addButton').click();
		await fillStaffEntry(page, {
			username: 'tempuser',
			loginName: 'tempuser',
			firstName: 'Temp',
			lastName: 'User',
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
		// confirmDel() calls showLoading() which submits the controls form; wait for navigation.
		await Promise.all([
			page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
			page.getByRole('button', { name: /Delete User/i }).click(),
		]);

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

		// The Group Membership element renders selected groups as <p class='auto_multi ...'> tags.
		// The click handler is delegated from the container div. The <p> has display:table-row so
		// hit-testing resolves to the container; use force:true to bypass Playwright's interceptor check.
		const markupName = 'formulize_user_account_groupmembership_' + phase1.staffFid;
		const allCuratorsTag = page.locator(`p.auto_multi_${markupName}`).filter({ hasText: /^All Curators$/ });
		// The Group Membership element renders its tags via JS after the entry form loads;
		// wait for the tag to actually be present/visible before clicking (otherwise, under
		// load, the click can fire before render — a real race that worsens on fast CI).
		await expect(allCuratorsTag).toBeVisible({ timeout: 30000 });
		await allCuratorsTag.click({ force: true });
		await saveFormulizeForm(page, 'Save');

		// Reload the entry and verify All Curators is back.
		await page.goto(`/modules/formulize/master.php?fid=${phase1.staffFid}`);
		await page.getByRole('row', { name: /curator1/ }).getByRole('link').first().click();
		const markupNameCheck = 'formulize_user_account_groupmembership_' + phase1.staffFid;
		await expect(page.locator(`p.auto_multi_${markupNameCheck}`).filter({ hasText: 'All Curators' })).toBeVisible();
		await clearEntryLocks(page);
	});

	// Enforced members have no Remove button; a non-enforced added member does and can be removed.
	// Every auto-assigned membership in this suite is enforced by an EAU default-group condition,
	// so curator1/curator2 (is_curator=Yes → All Curators) have no Remove button. To exercise a
	// removable member we add ahstaff (is_curator=No → NOT enforced in All Curators), confirm it
	// gets a Remove button while curator1 still doesn't, then remove ahstaff to restore state.
	// Members display the ImpressCMS uname (Full Name): "Curator One", "Ancient History Staff".
	// gmm Remove/Add buttons have pointer-events:none until hover, so click with force:true.
	test('All Curators: enforced members have no Remove button; a non-enforced added member can be removed', async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);

		const openAllCurators = async () => {
			await page.goto('/modules/formulize/groups.php');
			await page.waitForLoadState('networkidle');
			await page.getByRole('row', { name: /All Curators/ }).getByRole('link').first().click();
			await page.waitForLoadState('networkidle');
		};

		// 1. curator1 is an enforced member → no Remove button.
		await openAllCurators();
		const curator1Row = page.locator('.gmm-member-row').filter({ hasText: 'Curator One' });
		await expect(curator1Row).toBeVisible();
		await expect(curator1Row.getByRole('button', { name: /Remove/i })).toHaveCount(0);

		// 2. Add ahstaff (not enforced in All Curators) via the Add Members tab.
		await page.locator('.gmm-tab', { hasText: 'Add Members' }).click();
		const addSearch = page.locator('input[id^="gmm-add-search-"]');
		await addSearch.fill('ahstaff');
		await addSearch.press('Enter');
		const addRow = page.locator('.gmm-add-row').filter({ hasText: 'Ancient History Staff' });
		await expect(addRow).toBeVisible();
		await addRow.getByRole('button', { name: /Add/i }).click({ force: true });
		await saveFormulizeForm(page, 'Save');

		// 3. Reopen: ahstaff is now a member WITH a Remove button (not enforced); curator1 still has none.
		await openAllCurators();
		const ahstaffRow = page.locator('.gmm-member-row').filter({ hasText: 'Ancient History Staff' });
		await expect(ahstaffRow).toBeVisible();
		await expect(ahstaffRow.getByRole('button', { name: /Remove/i })).toHaveCount(1);
		await expect(page.locator('.gmm-member-row').filter({ hasText: 'Curator One' }).getByRole('button', { name: /Remove/i })).toHaveCount(0);

		// 4. Remove ahstaff and confirm it's gone (restores the original membership state).
		await ahstaffRow.getByRole('button', { name: /Remove/i }).click({ force: true });
		await saveFormulizeForm(page, 'Save');
		await openAllCurators();
		await expect(page.locator('.gmm-member-row').filter({ hasText: 'Ancient History Staff' })).toHaveCount(0);
		await clearEntryLocks(page);
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
		await clearEntryLocks(page);
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

	// Covers the "Users and Groups" section of the front-end menu (drawUsersAndGroupsMenuSection()
	// in usersAndGroups.php), rendered as a native <details>/<summary> disclosure by menu.html.
	// Regression guard for two things: (1) clicking the section header expands it locally, with
	// no page navigation, and (2) once you're actually on users.php/groups.php, the section is
	// server-rendered already expanded (the "$data['expanded']" flag) rather than requiring another
	// click — this is the exact bug class reported after the menu rearchitecture (a section not
	// auto-opening while already on one of its own pages).
	test('Users and Groups menu section expands on click without navigating, then auto-expands once on its own page', async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		// login() lands on a front-end module page (not users.php/groups.php), so the
		// section is collapsed to start.
		await ensureMainMenuOpen(page);

		const usersLink = page.locator('#mainmenu').getByRole('link', { name: 'Users', exact: true });
		const groupsLink = page.locator('#mainmenu').getByRole('link', { name: 'Groups', exact: true });

		// Collapsed by default: the sub-links exist in the DOM but aren't visible.
		await expect(usersLink).not.toBeVisible();
		await expect(groupsLink).not.toBeVisible();

		// Clicking the section header expands it instantly, with no page reload.
		const urlBeforeExpand = page.url();
		await page.locator('#mainmenu').getByText('Users and Groups', { exact: true }).click();
		await expect(usersLink).toBeVisible();
		await expect(groupsLink).toBeVisible();
		expect(page.url()).toBe(urlBeforeExpand);

		// Clicking a sub-link navigates normally.
		await usersLink.click();
		await expect(page).toHaveURL(/users\.php/);
		await expect(page.locator('#formulize_addButton')).toBeVisible();
		await clearEntryLocks(page);

		// Now that we're on users.php, the section should already be expanded on load, with
		// no click needed to reveal the sub-links again.
		await ensureMainMenuOpen(page);
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Users', exact: true })).toBeVisible();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Groups', exact: true })).toBeVisible();
	});
});
