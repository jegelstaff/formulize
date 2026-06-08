const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, saveAdminForm, openMenuAccordion, openElementAccordion, waitForAdminPageReady, setMenuEntryGroups, selectAutocompleteOption } from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

test.beforeEach(async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	// Direct navigation rather than the theme "Admin" link, whose destination
	// varies with DB/startpage state once forms exist (same pattern as 005/010).
	await page.goto('/modules/formulize/admin/ui.php?page=home');
	await waitForAdminPageReady(page);
})

// Check a permission checkbox inside a group's panel by the checkbox's stable
// name suffix (e.g. "_view_form"). The redesigned permissions panel made some
// label texts overlap (getByLabel('View the form') now also matches the
// groupscope checkbox), so target by name instead. .first() skips any entry-group
// preview rows that the template-group panel's entry-group-selector widget adds.
async function checkPerm(groupLocator, nameSuffix) {
	await groupLocator.locator(`input[name$="${nameSuffix}"]`).first().check();
}

// Permission name suffixes (from form_permissions_group_panel.html):
//   _view_form, _add_own_entry, _update_own_entry, _update_group_entries,
//   _update_other_entries, _delete_own_entry, _delete_group_entries,
//   _delete_other_entries, _view_groupscope, _view_globalscope,
//   _update_entry_ownership

// Permissions are now assigned to the EAG TEMPLATE groups created in 005.
// Granting a permission on a template group cascades to all of its per-department
// entry groups (e.g. "Departments - All Users" → "Ancient History - All Users" +
// "Modern History - All Users"). Combined with each user's dynamic groupscope
// (the union of their groups that hold view_form), this yields per-department
// data isolation: staff see only their department, curators (members of both
// departments' groups) see both. See plan Phase 5.2 + the cascade discussion.
async function setStandardPermissions(page) {
	await page.locator('#groups').selectOption(['Departments - All Users', 'Departments - Curators']);
	await page.getByRole('button', { name: 'Show permissions for these' }).click();
	await expect(page.getByRole('group', { name: 'Departments - All Users' }).locator('legend')).toBeVisible();
	// All department users: own entries + groupscope visibility (drives isolation).
	const allUsers = page.getByRole('group', { name: 'Departments - All Users' });
	await checkPerm(allUsers, '_view_form');
	await checkPerm(allUsers, '_add_own_entry');
	await checkPerm(allUsers, '_update_group_entries');
	await checkPerm(allUsers, '_delete_group_entries');
	await checkPerm(allUsers, '_view_groupscope');
	// Curators: full access across all entries.
	const curators = page.getByRole('group', { name: 'Departments - Curators' });
	await checkPerm(curators, '_view_form');
	await checkPerm(curators, '_add_own_entry');
	await checkPerm(curators, '_update_other_entries');
	await checkPerm(curators, '_delete_other_entries');
	await checkPerm(curators, '_view_globalscope');
	await checkPerm(curators, '_update_entry_ownership');
}

// Like setStandardPermissions but every department user gets global visibility
// (view/update/delete any entry), used for forms that are not department-isolated.
async function setGlobalPermissions(page) {
	await page.locator('#groups').selectOption(['Departments - All Users', 'Departments - Curators']);
	await page.getByRole('button', { name: 'Show permissions for these' }).click();
	await expect(page.getByRole('group', { name: 'Departments - All Users' }).locator('legend')).toBeVisible();
	const allUsers = page.getByRole('group', { name: 'Departments - All Users' });
	await checkPerm(allUsers, '_view_form');
	await checkPerm(allUsers, '_add_own_entry');
	await checkPerm(allUsers, '_update_other_entries');
	await checkPerm(allUsers, '_delete_other_entries');
	await checkPerm(allUsers, '_view_globalscope');
	const curators = page.getByRole('group', { name: 'Departments - Curators' });
	await checkPerm(curators, '_view_form');
	await checkPerm(curators, '_add_own_entry');
	await checkPerm(curators, '_update_other_entries');
	await checkPerm(curators, '_delete_other_entries');
	await checkPerm(curators, '_view_globalscope');
	await checkPerm(curators, '_update_entry_ownership');
	await saveAdminForm(page);
}

async function setAnonPermissions(page) {
	await page.locator('#groups').selectOption(['Anonymous Users']);
	await page.getByRole('button', { name: 'Show permissions for these' }).click();
	await expect(page.getByRole('group', { name: 'Anonymous Users' }).locator('legend')).toBeVisible();
	const anon = page.getByRole('group', { name: 'Anonymous Users' });
	await checkPerm(anon, '_view_form');
	await checkPerm(anon, '_add_own_entry');
	await checkPerm(anon, '_update_own_entry');
	await saveAdminForm(page);
}

// Phase 3 (3.2 form-to-form inheritance + 3.3 cascade-through): a museum form that needs the
// exact same permissions as Artifacts INHERITS from it instead of re-running
// setStandardPermissions. Saving with a parent calls formulizePermHandler::copyFormPermissions(),
// which copies Artifacts' full permission set — including the template→entry group cascade and
// groupscope — onto the child form (re-synced if Artifacts changes). De-duplicates the identical
// permission setup and proves inheritance carries the cascade. Must run AFTER the Artifacts test.
// `openFormPermissions` clicks the child form's name then its Permissions tab. The caller's
// beforeEach has already navigated to the Museum forms-list page (which has NO self-referential
// "Application: Museum" link), so openFormPermissions must NOT click that link; for the
// verify-after-save reopen we first click "Application: Museum" (present on the form admin page)
// to get back to the list, then call openFormPermissions again.
async function inheritPermissionsFromArtifacts(page, openFormPermissions) {
	await openFormPermissions();
	const formUrl = page.url(); // form admin URL (carries the fid) — used to reopen for the verify
	// Readiness: the permissions panel is revealed by jQuery-UI when the tab is shown.
	await expect(page.locator('#perm_mode_inherit')).toBeVisible({ timeout: 30000 });
	await page.locator('#perm_mode_inherit').check();
	await page.locator('#parent_perm_fid_select').selectOption({ label: 'Artifacts' });
	await saveAdminForm(page);
	// Verify-after-save: reopen the form by URL (robust — the admin nav links can be unreliable
	// from the post-inherit-save page). The inherit radio's checked state is set by JS on load and
	// the parent <option> is server-rendered, so these assert correctly even if the Permissions
	// tab isn't the active jQuery-UI tab (checkbox/select state doesn't require visibility).
	await page.goto(formUrl);
	await expect(page.locator('#perm_mode_inherit')).toBeChecked({ timeout: 30000 });
	await expect(page.locator('#parent_perm_fid_select option:checked')).toHaveText('Artifacts');
}

test.describe('Set Permissions', () => {

	test.beforeEach(async ({ page }) => {
		await page.getByRole('link', { name: 'Application: Museum' }).click();
	})

	test('Set permissions for Artifacts', async ({ page }) => {
		await page.getByText('Artifacts').first().click();
		await page.getByRole('link', { name: 'Permissions', exact: true }).click();
		await setStandardPermissions(page);
		await saveAdminForm(page);
		// Verify the per-user effective scope resolves from the standard permissions.
		// (Donors/Collections/Exhibits inherit these same perms, so the scopes apply there too.)
		await selectAutocompleteOption(page, page.locator('#submitted_user_user'), 'Modern History Staff', { searchText: 'hist' });
		// The jQuery-UI autocomplete dropdown and the fixed #admin_toolbar can both
		// intercept the button click (a UI/Playwright timing race). Wait for the
		// dropdown to close, then force past any fixed-toolbar overlap.
		await expect(page.locator('ul.ui-autocomplete:visible')).toHaveCount(0);
		await page.getByRole('button', { name: 'Show permissions for the user' }).click({ force: true });
		await waitForAdminPageReady(page);
		// The permissions redesign reworded the visibility labels:
		//   groupscope  -> "View entries made by their group(s):"
		//   globalscope -> "View entries made by anyone"
		// mhstaff is in Modern History - All Users (groupscope), so they see the
		// groupscope line but not the global one.
		await expect(page.getByText('View entries made by their group(s)')).toBeVisible();
		await expect(page.getByText('View entries made by anyone')).not.toBeVisible();
		await selectAutocompleteOption(page, page.locator('#submitted_user_user'), 'Curator One', { searchText: 'cur' });
		await expect(page.locator('ul.ui-autocomplete:visible')).toHaveCount(0);
		await page.getByRole('button', { name: 'Show permissions for the user' }).click({ force: true });
		await waitForAdminPageReady(page);
		// Curator One is in the Curators groups (globalscope).
		await expect(page.getByText('View entries made by anyone')).toBeVisible();
		await expect(page.getByText('View entries made by their group(s)')).not.toBeVisible();
	})

	test('Donors inherits permissions from Artifacts (form-to-form)', async ({ page }) => {
		await inheritPermissionsFromArtifacts(page, async () => {
			await page.getByText('Donors').first().click();
			await page.getByRole('link', { name: 'Permissions', exact: true }).click();
		});
	})

	test('Collections inherits permissions from Artifacts (form-to-form)', async ({ page }) => {
		await inheritPermissionsFromArtifacts(page, async () => {
			await page.getByText('Collections').first().click();
			await page.getByRole('link', { name: 'Permissions', exact: true }).click();
		});
	})

	test('Exhibits inherits permissions from Artifacts (form-to-form)', async ({ page }) => {
		await inheritPermissionsFromArtifacts(page, async () => {
			await page.getByText('Exhibits').nth(2).click();
			await page.getByRole('link', { name: 'Permissions', exact: true }).click();
		});
	})

	test('Set permissions for Surveys', async ({ page }) => {
		await page.getByText('Surveys').nth(2).click();
		await page.getByRole('link', { name: 'Permissions', exact: true }).click();
		await setGlobalPermissions(page);
		await saveAdminForm(page);
		await setAnonPermissions(page);
		await saveAdminForm(page);
	})

	test('Set Anonymous permissions to Survey Form', async ({ page }) => {
		await page.getByText('Surveys').nth(2).click();
		await page.locator('div[id^=form-details-box-]').nth(4).getByRole('link', { name: 'Screens' }).first().click();
		await page.getByRole('link', { name: 'Survey', exact: true }).click();
	  await page.getByText('No, only permission to view').click();
		await saveAdminForm(page);
		await page.goto('/');
		await page.getByText('Logout').click();
		// The Survey multipage screen is sid=16 now: the 4 forms 005 creates
		// (Departments, System Groups, Staff, System Users) consume screen ids
		// 1-6, shifting the museum screens up by 6 (was sid=10 pre-005-rewrite).
		await page.goto('/modules/formulize/index.php?sid=16');
		await expect(page.getByRole('heading', { name: 'Password:' })).not.toBeVisible();
		await expect(page.getByText('Which exhibit did you see?')).toBeVisible();
	})
})


test.describe('Set Menu Entries', () => {

	// Menu group selections are set via setMenuEntryGroups(), which saves then
	// re-navigates and verifies the selection actually persisted (retrying on
	// failure). This guards against the Playwright-vs-UI save race that silently
	// dropped these selections for some menus when each test just saved once.
	const museumMenuGroups = ['Webmasters', 'Ancient History - All Users', 'Modern History - All Users'];

	test('Set Menu Entry for Artifacts', async ({ page }) => {
		await setMenuEntryGroups(page, 'Museum', 'Artifacts', 'groups0', museumMenuGroups, { defaultScreenSelectId: 'defaultScreenGroups0' });
	})
	test('Set Menu Entry for Donors', async ({ page }) => {
		await setMenuEntryGroups(page, 'Museum', 'Donors', 'groups1', museumMenuGroups);
	})
	test('Set Menu Entry for Collections', async ({ page }) => {
		await setMenuEntryGroups(page, 'Museum', 'Collections', 'groups2', museumMenuGroups);
	})
	test('Set Menu Entry for Exhibits', async ({ page }) => {
		await setMenuEntryGroups(page, 'Museum', 'Exhibits', 'groups3', museumMenuGroups);
	})
	test('Set Menu Entry for Surveys', async ({ page }) => {
		await setMenuEntryGroups(page, 'Museum', 'Surveys', 'groups4', museumMenuGroups);
	})
})

test.describe('Set columns and elements for screens', () => {

	test.beforeEach(async ({ page }) => {
		await page.goto('/modules/formulize/admin/ui.php?page=home');
		await page.getByRole('link', { name: 'Application: Museum' }).click();
	})

	test('Artifacts form screen', async ({ page }) => {
		await page.locator('div[id^=form-details-box-]').nth(0).getByRole('link', { name: 'Screens' }).click();
		await page.getByRole('link', { name: 'Artifact', exact: true }).click();
		await page.getByRole('link', { name: 'Pages' }).click();
  	await page.getByRole('link', { name: 'Edit this page' }).click();
  	await page.getByLabel('Form elements to display on').selectOption([
			'Artifacts: ID Number',
			'Artifacts: Short name',
			'Artifacts: Full description',
			'Artifacts: Dimensions',
			'Artifacts: Height',
			'Artifacts: Width',
			'Artifacts: Depth',
			'Artifacts: Date of origin',
			'Artifacts: Year-Era',
			'Artifacts: Year',
			'Artifacts: Era',
			'Artifacts: Date of acquisition',
			'Artifacts: Donated to museum',
			'Artifacts: Donor',
			'Artifacts: Condition',
			'Artifacts: Collections',
			'Artifacts: Appears in these exhibits'
		]);
  	await saveAdminForm(page, 'popup');
  	await page.getByRole('button', { name: 'close' }).click();
  	await page.getByRole('link', { name: 'Artifacts' }).click();
  	await page.getByRole('link', { name: 'Artifacts' }).click();
  	await page.getByRole('link', { name: 'Entries' }).click();
		await page.locator('#cols-0').selectOption('artifacts_id_number');
  	await page.getByRole('button', { name: 'Add Column' }).click();
  	await page.locator('#cols-1').selectOption('artifacts_short_name');
  	await page.getByRole('button', { name: 'Add Column' }).click();
  	await page.locator('#cols-2').selectOption('artifacts_year_era');
  	await page.getByRole('button', { name: 'Add Column' }).click();
    await page.locator('#cols-3').selectOption('artifacts_collections');
		await saveAdminForm(page);
	})

	test('Donors form screen', async ({ page }) => {
		await page.locator('div[id^=form-details-box-]').nth(2).getByRole('link', { name: 'Screens' }).click();
  	await page.getByRole('link', { name: 'Donor', exact: true }).click();
		await page.getByRole('link', { name: 'Options' }).click();
  	await page.locator('#form-3 label').filter({ hasText: /^No$/ }).locator('#screens-printall').check();
		await saveAdminForm(page);
  	await page.getByRole('link', { name: 'Pages' }).click();
  	await page.getByRole('link', { name: 'Edit this page' }).click();
		await page.getByRole('textbox', { name: 'Title for page number' }).fill('Profile');
  	await page.getByLabel('Form elements to display on').selectOption([
			'Donors: Type of donor',
			'Donors: First name',
			'Donors: Last name',
			'Donors: Organization name',
			'Donors: Phone number',
			'Donors: Email Address',
			'Donors: Street address',
			'Donors: Province, Postal code',
			'Donors: Province',
			'Donors: Postal code',
			'Donors: Favourite colour',
			'Donors: Backgrounder / Resume'
		]);
  	await saveAdminForm(page, 'popup');
		await page.getByRole('button', { name: 'close' }).click();
		await page.getByRole('link', { name: 'Create a new page' }).click();
		await waitForAdminPageReady(page)
		await page.getByRole('link', { name: 'New page', exact: true }).click();
		await page.getByRole('link', { name: 'Edit this page' }).click();
  	await page.getByRole('textbox', { name: 'Title for page number' }).fill('Donated Artifacts');
		await page.getByLabel('Form elements to display on').selectOption(['Donors: Name', 'Donors: Donated artifacts']);
		await saveAdminForm(page, 'popup');
  	await page.getByRole('button', { name: 'close' }).click();
  	await page.getByRole('link', { name: 'Donors' }).click();
  	await page.getByRole('link', { name: 'Donors' }).click();
  	await page.getByRole('link', { name: 'Entries' }).click();
  	await page.locator('#cols-0').selectOption('donors_name');
  	await page.getByRole('button', { name: 'Add Column' }).click();
  	await page.locator('#cols-1').selectOption('donors_phone');
  	await page.getByRole('button', { name: 'Add Column' }).click();
  	await page.locator('#cols-2').selectOption('donors_email');
  	await saveAdminForm(page);
	})

	test('Move element between pages on Donors form screen', async ({ page }) => {
		await page.locator('div[id^=form-details-box-]').nth(2).getByRole('link', { name: 'Elements' }).click();
		await openElementAccordion(page, 'Street address');
		await page.getByRole('link', { name: 'Configure' }).click();
		await page.getByRole('link', { name: 'Display Settings' }).click();
		const screensFieldset = page.getByRole('group', {
			name: 'Form Screens to display this element on',
		});
		const checkboxByLabelText = (labelText) =>
			screensFieldset.locator('li', {
				has: page.locator('input[type="checkbox"]'),
			}).locator('label', { hasText: labelText })
		await checkboxByLabelText('Donated Artifacts').check();
		await checkboxByLabelText('Profile').uncheck();
		await saveAdminForm(page);
		await checkboxByLabelText('Donated Artifacts').uncheck();
		await checkboxByLabelText('Profile').check();
		await saveAdminForm(page);
		await page.goto('/modules/formulize/admin/ui.php?page=home');
	})

	test('Collections form screen', async ({ page }) => {
		await page.locator('div[id^=form-details-box-]').nth(1).getByRole('link', { name: 'Screens' }).click();
		await page.getByRole('link', { name: 'Collection', exact: true }).click();
		await page.getByRole('link', { name: 'Pages' }).click();
  	await page.getByRole('link', { name: 'Edit this page' }).click();
		await expect(page.getByText('Title for page number')).toBeVisible();
		await page.getByLabel('Form elements to display on').selectOption([
			'Collections: Name',
			'Collections: Suitable audience',
			'Collections: Artifacts'
		]);
		await saveAdminForm(page, 'popup');
		await page.getByRole('button', { name: 'close' }).click();
		await page.getByRole('link', { name: 'Collections' }).click();
	  await page.getByRole('link', { name: 'Collections' }).click();
  	await page.getByRole('link', { name: 'Entries' }).click();
  	await page.locator('#cols-0').selectOption('collections_name');
		await page.getByRole('link', { name: 'Buttons', exact: true }).click();
  	await page.getByRole('textbox', { name: 'What text should be on the \'Add multiple entries\' button?' }).fill('Add Collections');
  	await saveAdminForm(page);
	})

	test('Exhibits form screen', async ({ page }) => {
		await page.locator('div[id^=form-details-box-]').nth(3).getByRole('link', { name: 'Screens' }).click();
		await page.getByRole('link', { name: 'Exhibit', exact: true }).click();
		await page.getByRole('link', { name: 'Pages' }).click();
  	await page.getByRole('link', { name: 'Edit this page' }).click();
		await expect(page.getByText('Title for page number')).toBeVisible();
   	await page.getByLabel('Form elements to display on').selectOption([
			'Exhibits: Name',
			'Exhibits: Curator',
			'Exhibits: Collections',
			'Exhibits: Artifacts',
			'Exhibits: Surveys',
		]);
		await saveAdminForm(page, 'popup');
		await page.getByRole('button', { name: 'close' }).click();
		await page.getByRole('link', { name: 'Exhibits' }).click();
  	await page.getByRole('link', { name: 'Exhibits' }).click();
  	await page.getByRole('link', { name: 'Entries' }).click();
		await page.locator('#cols-0').selectOption('exhibits_name');
  	await page.getByRole('button', { name: 'Add Column' }).click();
		await page.locator('#cols-1').selectOption('exhibits_curator');
  	await page.getByRole('button', { name: 'Add Column' }).click();
		await page.locator('#cols-2').selectOption('exhibits_collections');
  	await page.getByRole('button', { name: 'Add Column' }).click();
		await page.locator('#cols-3').selectOption('exhibits_artifacts');
		await saveAdminForm(page);
	})

	test('Set Preferences for Rewrite Rules', async ({ page }) => {
		await page.getByRole('link', { name: 'Preferences' }).click();
		await page.locator('#formulizeRewriteRulesEnabled-13').check();
		await page.getByRole('button', { name: 'Save your changes' }).click();
		await waitForAdminPageReady(page);
		await expect(page.locator('#formulizeRewriteRulesEnabled-13')).toBeChecked();
  	await page.locator('div.CPbigTitle').getByRole('link', { name: 'Formulize', exact: true }).click();
  	await page.getByRole('link', { name: 'Application: Museum' }).click();
  	await page.locator('div[id^=form-details-box-]').nth(4).getByRole('link', { name: 'Screens' }).click();
  	await page.getByRole('link', { name: 'Survey', exact: true }).click();
  	await page.locator('input[name="screens-rewriteruleAddress"]').fill('survey');
  	await page.getByRole('button', { name: 'Save your changes' }).click();
	})

	test('Surveys form screen', async ({ page }) => {
		await page.locator('div[id^=form-details-box-]').nth(4).getByRole('link', { name: 'Screens' }).click();
  	await page.getByRole('link', { name: 'Survey', exact: true }).click();
		await page.getByRole('link', { name: 'Pages' }).click();
  	await page.getByRole('link', { name: 'Edit this page' }).click();
		await expect(page.getByText('Title for page number')).toBeVisible();
		await page.getByLabel('Form elements to display on').selectOption([
			'Surveys: Thank you for visiting...',
			'Surveys: Respondent name',
			'Surveys: Exhibit',
			'Surveys: Favourite artifact',
			'Surveys: Rating',
			'Surveys: Flagged',
			'Surveys: Staff comments'
		]);
		await saveAdminForm(page, 'popup');
		await page.getByRole('button', { name: 'close' }).click();
		await page.getByRole('link', { name: 'Surveys' }).click();
  	await page.getByRole('link', { name: 'Surveys' }).click();
  	await page.getByRole('link', { name: 'Entries' }).click();
		await page.locator('#cols-0').selectOption('creation_datetime');
  	await page.getByRole('button', { name: 'Add Column' }).click();
		await page.locator('#cols-1').selectOption('surveys_your_name');
  	await page.getByRole('button', { name: 'Add Column' }).click();
		await page.locator('#cols-2').selectOption('surveys_exhibit');
  	await page.getByRole('button', { name: 'Add Column' }).click();
		await page.locator('#cols-3').selectOption('surveys_favourite_artifact');
  	await page.getByRole('button', { name: 'Add Column' }).click();
		await page.locator('#cols-4').selectOption('surveys_rating');
		await saveAdminForm(page);
	})

	test('Procedures for Artifacts form', async ({ page }) => {
		await page.getByText('Artifacts').first().click();
		await page.getByRole('link', { name: 'Procedures' }).click();
		await page.getByRole('group', { name: 'Before Saving' }).locator('span').first().click();
  	await page.getByRole('group', { name: 'Before Saving' }).getByRole('textbox').press('ControlOrMeta+a');
   	await page.getByRole('group', { name: 'Before Saving' }).getByRole('textbox').fill('<?php\n\n// If there\'s a donor, mark as donated\nif($artifacts_donor) {\n\t$artifacts_donated_to_museum = 1; // yes is 1 in the database\n}\n');
		await page.getByRole('group', { name: 'After Saving' }).locator('span').first().click();
  	await page.getByRole('group', { name: 'After Saving' }).getByRole('textbox').press('ControlOrMeta+a');
   	await page.getByRole('group', { name: 'After Saving' }).getByRole('textbox').fill('<?php\n\n// standardize the artifacts ID numbers\nif(!$artifacts_id_number || !preg_match(\'/^M\d{3}$/\', $artifacts_id_number)) {\n\t$idLength = strlen($entry_id);\n\t$zeros = 3 - $idLength;\n\t$zeros = $zeros < 0 ? 0 : $zeros;\n\t$artifacts_id_number = "M";\n\tfor($i=1;$i<=$zeros;$i++) {\n\t\t$artifacts_id_number .= "0";\n\t}\n\t$artifacts_id_number .= $entry_id;\n\tformulize_writeEntry([\'artifacts_id_number\' => $artifacts_id_number], $entry_id);\n}');
		await saveAdminForm(page);
	})

});
