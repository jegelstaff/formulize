const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, createMuseumForm, deleteMuseumFormsTitled,
	showPermissionsFor, openElementSettings, openApplicationAccordion, openMenuAccordion, dbQuery, dbPrefix, sqlEscape,
	openEntry, entryArea, saveAndCloseEntry,
} from '../../utils';

// Template groups in the admin's lists of groups, and in a group's scope.
//
//  - The lists of groups on the Permissions tab, in a linked element's scope and in a menu link put regular groups
//    and template groups under their own headings, and leave out each department's own groups.
//  - A regular group whose scope ("View entries made by their group(s)", Customize) is a template group sees the
//    entries of every group in that template group's category, because the department groups are stored with it.
//  - The change scope dialog lists the department groups under the name of their template group, and doesn't offer
//    the template group itself, which owns no entries.
//
// Builds a throwaway form, with Departments - All Users able to add entries and Auditors scoped to Departments - Staff.
// ahstaff is in Ancient History - Staff, and curator1 is in no Staff group. afterAll deletes the form. The tests run
// in order: each one depends on the ones before it.
test.describe.configure({ mode: 'serial' });

const FORM_TITLE = 'Template group scope test'; // no final s, which the Add button's label would leave off
const STAFF_ENTRY = 'Made by a staff member';
const CURATOR_ENTRY = 'Made by a curator';

let fid = 0;
let listSid = 0;

function groupId(name) {
	return parseInt(dbQuery(`SELECT groupid FROM ${dbPrefix()}_groups WHERE name = '${sqlEscape(name)}'`)[0][0], 10);
}

// The Staff group of each department, which is what Departments - Staff stands for
function departmentStaffGroupIds() {
	const departmentsFid = dbQuery(`SELECT form_id FROM ${dbPrefix()}_groups WHERE name = 'Departments - Staff'`)[0][0];
	return dbQuery(`SELECT groupid FROM ${dbPrefix()}_groups WHERE form_id = ${departmentsFid} AND is_group_template = 0 AND entry_id > 0 AND name LIKE '% - Staff'`)
		.map(row => parseInt(row[0], 10));
}

async function expectGroupHeadings(list) {
	await expect(list.locator('optgroup[label="Regular groups"] option', { hasText: 'All Curators' })).toHaveCount(1);
	await expect(list.locator('optgroup[label="Form-based groups"] option', { hasText: 'Departments - Staff' })).toHaveCount(1);
	await expect(list.locator('option', { hasText: 'Ancient History - Staff' })).toHaveCount(0);
}

async function addEntry(browser, user, name) {
	const context = await browser.newContext();
	const page = await context.newPage();
	try {
		await login(page, user);
		await page.goto(`/modules/formulize/index.php?sid=${listSid}`);
		await openEntry(page, page.getByRole('button', { name: `Add ${FORM_TITLE}`, exact: true }));
		await entryArea(page).getByRole('textbox', { name: 'Name *' }).fill(name);
		await saveFormulizeForm(page);
		await saveAndCloseEntry(page); // clears the entry lock
		await expect(page.getByText(name)).toBeVisible();
	} finally {
		await context.close();
	}
}

test.afterAll(async ({ browser }) => {
	const page = await browser.newPage();
	await login(page, 'admin');
	await deleteMuseumFormsTitled(page, [FORM_TITLE]);
	await page.close();
});

test('lists of groups have headings for regular and form-based groups, and leave out the department groups', async ({ page }) => {
	await login(page, 'admin');
	await deleteMuseumFormsTitled(page, [FORM_TITLE]); // whatever a killed run left behind
	fid = await createMuseumForm(page, FORM_TITLE, 'Name');
	listSid = parseInt(dbQuery(`SELECT sid FROM ${dbPrefix()}_formulize_screen WHERE fid = ${fid} AND type = 'listOfEntries'`)[0][0], 10);

	await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${fid}&tab=permissions`);
	await waitForAdminPageReady(page);
	await expectGroupHeadings(page.locator('#groups'));

	await openElementSettings(page, 'exhibits_collections', 'Options');
	await expectGroupHeadings(page.locator('#element-formlink_scope'));

	await page.goto('/modules/formulize/admin/ui.php?page=home');
	await openApplicationAccordion(page, 'Museum');
	await page.getByRole('link', { name: 'Menu Entries' }).click();
	await openMenuAccordion(page, 'Artifacts');
	await expectGroupHeadings(page.locator('#groups0'));
});

test('a regular group scoped to a template group stores the department groups with it', async ({ page }) => {
	await login(page, 'admin');
	const auditorsId = groupId('Auditors');
	const templateId = groupId('Departments - Staff');
	await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${fid}&tab=permissions`);
	await showPermissionsFor(page, ['Departments - All Users', 'Auditors']);

	const allUsers = page.getByRole('group', { name: 'Departments - All Users' });
	await allUsers.locator('input[name$="_view_form"]').first().check();
	await allUsers.locator('input[name$="_add_own_entry"]').first().check();
	const auditors = page.getByRole('group', { name: 'Auditors' });
	await auditors.locator('input[name$="_view_form"]').first().check();
	await auditors.locator('input[name$="_view_groupscope"]').first().check();
	await auditors.getByRole('link', { name: '[Customize]' }).click();
	const scopeList = page.locator(`select[name="groupsscope_choice_${fid}_${auditorsId}[]"]`);
	await expect(scopeList).toBeVisible();
	await scopeList.selectOption({ label: 'Departments - Staff' });
	await saveAdminForm(page);

	// what was stored: the template group, and every department's Staff group
	const staffGroups = departmentStaffGroupIds();
	expect(staffGroups.length, 'the museum has more than one department').toBeGreaterThan(1);
	await expect.poll(() => dbQuery(`SELECT view_groupid FROM ${dbPrefix()}_formulize_groupscope_settings WHERE fid = ${fid} AND groupid = ${auditorsId}`)
		.map(row => parseInt(row[0], 10)).sort((a, b) => a - b)).toEqual([templateId, ...staffGroups].sort((a, b) => a - b));

	// what the tab shows: only the template group is chosen, and the summary counts the groups it stands for
	await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${fid}&tab=permissions`);
	await showPermissionsFor(page, ['Auditors']);
	await expect(scopeList.locator('option:checked')).toHaveText(['Departments - Staff']);
	await expect(page.locator(`#groupscope_inline_${fid}_${auditorsId} em`)).toHaveText(`Departments - Staff (${staffGroups.length} groups)`);
});

test('the regular group sees the entries of the department Staff groups, and no others', async ({ browser }) => {
	await addEntry(browser, 'ahstaff', STAFF_ENTRY);
	await addEntry(browser, 'curator1', CURATOR_ENTRY);

	const context = await browser.newContext();
	const page = await context.newPage();
	try {
		await login(page, 'auditor1');
		await page.goto(`/modules/formulize/index.php?sid=${listSid}`);
		await expect(page.getByText(STAFF_ENTRY)).toBeVisible();
		await expect(page.getByText(CURATOR_ENTRY)).toHaveCount(0);
	} finally {
		await context.close();
	}
});

test('the change scope dialog lists the department groups under their template group', async ({ browser }) => {
	const context = await browser.newContext();
	const page = await context.newPage();
	try {
		await login(page, 'auditor1');
		// the address the list's script opens, when someone chooses to pick groups
		await page.goto(`/modules/formulize/include/changescope.php?fid=${fid}&frid=0&scope=`);
		const groups = page.locator('#newscope');
		await expect(groups.locator('optgroup[label="Departments - Staff"] option', { hasText: 'Ancient History - Staff' })).toHaveCount(1);
		await expect(groups.locator('option').filter({ hasText: /^Departments - Staff$/ })).toHaveCount(0);
	} finally {
		await context.close();
	}
});
