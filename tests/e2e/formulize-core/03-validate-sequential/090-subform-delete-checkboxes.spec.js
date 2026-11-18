const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, showPermissionsFor, leavePage, dbQuery, dbPrefix, sqlEscape, elementId } from '../../utils';
import { formId, museumAppId, saveAndClose } from '../01-setup/museum-forms';

// Which entries in a subform get a checkbox, and which of them can really be deleted.
//
// A subform gives a checkbox to each entry the user can delete, and none to the others, and deleting checks each entry
// again, whatever was posted. Which entries a user can delete follows the permissions:
//   - Delete their own entries: the entries they made.
//   - Delete entries made by their group(s): the entries made by their groups. Any of the user's groups can give the
//     permission, but their groups are the user's groups that have view_form on the form, whichever group gave it. That's
//     what the Permissions tab says next to view_form, and it's the same groups whose entries they can view and update.
//
// The Artifacts subform in Main Vault shows all 16 artifacts. ahstaff made 8 of them, which belong to Ancient History, and
// mhstaff made the other 8, which belong to Modern History. For this spec:
//   - Departments - All Users can delete their own entries, instead of their groups' entries, as 015 sets it
//   - Artifact Overseers, a new group, can view everyone's entries and delete their groups' entries, and doesn't have
//     view_form, so it isn't one of anyone's groups
//   - Both Departments Staff (bothstaff) is a new user in both departments, and not a curator
//   - Ancient History Overseer (ahoverseer) is a new user in Ancient History, and in Artifact Overseers
//   - the subform shows its Delete button, which 022 turned off
// So:
//   - mhstaff can delete their own artifacts, which bothstaff sees and can't delete
//   - ahoverseer sees every artifact, through Artifact Overseers, and can delete only Ancient History's, since that's
//     their only group with view_form
//   - once bothstaff is in Artifact Overseers too, they can delete every artifact, since both departments are their groups
//
// These are shared by every spec that uses the museum system, so this is in 03-validate-sequential. The last test puts
// them back, and afterAll does again, in case a test stopped partway. Two users, a group and a throwaway artifact are
// added and removed. The database is only read, to find ids and to check what's stored.

test.describe.configure({ mode: 'serial' });

const ANCIENT = ['Roman Coin', 'Persian necklace', 'Chinese Sword', 'Egyptian Chariot', 'Babylonian Spoon', 'Persian necklace - number 2', 'Unknown Shard', 'Bronze Mirror'];
const MODERN = ['Florentine Book', 'French Musket', 'Japanese Coin', 'American Bicycle', 'Polynesian Canoe', 'Viking Silver Armband', 'Candide - first edition', 'Silk Scroll'];
const BOTH = { login: 'bothstaff', firstName: 'Both Departments', lastName: 'Staff', departments: ['Ancient History', 'Modern History'] };
const OVERSEER = { login: 'ahoverseer', firstName: 'Ancient History', lastName: 'Overseer', departments: ['Ancient History'] };
const OVERSEERS = 'Artifact Overseers';
const TEMPLATE_GROUP = 'Departments - All Users';
const THROWAWAY = 'Delete check throwaway';
const SUBFORM = 'locations_subform_artifacts';

const uname = user => `${user.firstName} ${user.lastName}`;

function groupId(name) {
	const rows = dbQuery(`SELECT groupid FROM ${dbPrefix()}_groups WHERE name = '${sqlEscape(name)}'`);
	return rows.length ? rows[0][0] : null;
}

function artifactCount(name) {
	return Number(dbQuery(`SELECT COUNT(*) FROM ${dbPrefix()}_formulize_artifacts WHERE artifacts_short_name = '${sqlEscape(name)}'`)[0][0]);
}

function artifactEntryId(name) {
	return dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_artifacts WHERE artifacts_short_name = '${sqlEscape(name)}'`)[0][0];
}

function userExists(user) {
	return dbQuery(`SELECT uid FROM ${dbPrefix()}_users WHERE login_name = '${sqlEscape(user.login)}'`).length > 0;
}

function groupsOf(user) {
	return dbQuery(`SELECT g.name FROM ${dbPrefix()}_groups g, ${dbPrefix()}_groups_users_link l, ${dbPrefix()}_users u WHERE g.groupid = l.groupid AND l.uid = u.uid AND u.login_name = '${sqlEscape(user.login)}'`).map(row => row[0]);
}

const mainVaultUrl = () => {
	const entry = dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_locations WHERE locations_name = 'Main Vault'`)[0][0];
	return `/modules/formulize/index.php?fid=${formId('Locations')}&ve=${entry}`;
};

const subformSettingsUrl = () => {
	const fid = formId('Locations');
	return `/modules/formulize/admin/ui.php?page=element&ele_id=${elementId(SUBFORM)}&fid=${fid}&aid=${museumAppId(fid)}`;
};

const staffListUrl = () => `/modules/formulize/master.php?fid=${formId('Staff')}`;

// A permission's checkbox in a group's panel on the Artifacts form's Permissions tab
const permBox = (page, group, permission) => page.locator(`[id="${formId('Artifacts')}_${groupId(group)}_${permission}"]`);

// "Show permissions for these groups" saves the page first, which is harmless here, since no entry group's panel is shown
async function openArtifactsPermissions(page, groups) {
	await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${formId('Artifacts')}&tab=permissions`);
	await showPermissionsFor(page, groups);
}

async function setSubformDeleteButton(page, on) {
	await page.goto(subformSettingsUrl());
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Options' }).click();
	const box = page.getByRole('checkbox', { name: 'Show the Delete button' });
	await expect(box).toBeVisible();
	if (await box.isChecked() !== on) {
		await box.setChecked(on);
		await saveAdminForm(page);
		await page.goto(subformSettingsUrl());
		await waitForAdminPageReady(page);
		await page.getByRole('link', { name: 'Options' }).click();
	}
	if (on) {
		await expect(page.getByRole('checkbox', { name: 'Show the Delete button' })).toBeChecked();
	} else {
		await expect(page.getByRole('checkbox', { name: 'Show the Delete button' })).not.toBeChecked();
	}
}

async function makeStaffUser(page, user) {
	await page.goto(staffListUrl());
	await page.locator('#formulize_addButton').click();
	await page.getByRole('textbox', { name: 'Username' }).fill(user.login);
	await page.getByRole('textbox', { name: 'Login name' }).fill(user.login);
	await page.getByRole('textbox', { name: 'First Name' }).fill(user.firstName);
	await page.getByRole('textbox', { name: 'Last Name' }).fill(user.lastName);
	await page.getByRole('textbox', { name: 'Email Address' }).fill(`${user.login}@museum.formulize.net`);
	const passwords = page.locator('input[type="password"]');
	await passwords.nth(0).fill('12345');
	await passwords.nth(1).fill('12345');
	for (const department of user.departments) {
		await page.getByRole('checkbox', { name: department, exact: true }).check();
	}
	await page.getByRole('radio', { name: 'No', exact: true }).check(); // not a curator
	await saveFormulizeForm(page, 'Save');
	await expect.poll(() => userExists(user)).toBe(true);
	await leavePage(page, staffListUrl());
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
}

// A user's row in a list. Only an entry's row, since a search row holds what was typed into it.
const entryRow = (page, text) => page.locator('tr.entry-row').filter({ hasText: text });

async function openGroupsPage(page) {
	await page.goto('/modules/formulize/groups.php');
	await page.waitForLoadState('networkidle');
}

async function makeGroup(page, name) {
	await openGroupsPage(page);
	await page.locator('#formulize_addButton').click();
	await page.getByRole('textbox', { name: 'Group Name' }).fill(name);
	await saveFormulizeForm(page, 'Save');
	await expect.poll(() => groupId(name)).not.toBeNull();
	await leavePage(page, '/modules/formulize/groups.php'); // the new group's form stays open after the save
	await expect(entryRow(page, name)).toHaveCount(1);
}

// On the group's Add Members tab, as 005 does
async function addMember(page, groupName, user) {
	await openGroupsPage(page);
	await entryRow(page, groupName).getByRole('link').first().click();
	await page.waitForLoadState('networkidle');
	await page.locator('.gmm-tab', { hasText: 'Add Members' }).click();
	const search = page.locator('input[id^="gmm-add-search-"]');
	await search.fill(user.login);
	await search.press('Enter');
	const addRow = page.locator('.gmm-add-row').filter({ hasText: uname(user) });
	await expect(addRow).toBeVisible();
	await addRow.getByRole('button', { name: /Add/i }).click({ force: true });
	await saveFormulizeForm(page, 'Save');
	await expect.poll(() => groupsOf(user)).toContain(groupName);
	await leavePage(page, '/modules/formulize/groups.php');
	await expect(entryRow(page, groupName)).toHaveCount(1);
}

// A group can be deleted once it has no members, so the users go first
async function removeGroup(page, name) {
	if (groupId(name) === null) {
		return;
	}
	await openGroupsPage(page);
	await entryRow(page, name).locator('input[name^="delete_"]').check();
	page.once('dialog', dialog => dialog.accept());
	await Promise.all([
		page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
		page.getByRole('button', { name: 'Delete group' }).click(),
	]);
	await expect.poll(() => groupId(name)).toBeNull();
}

// The account is deleted on the Users page, as 005 does, and the Staff entry it came from in the Staff list
async function removeUser(page, user) {
	if (userExists(user)) {
		// the list is paged, so search for the user first
		await page.goto('/modules/formulize/users.php');
		const search = page.locator('input[name^="search_formulize_user_account_username_"]');
		await search.fill(user.login);
		await Promise.all([
			page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
			search.press('Enter'),
		]);
		const row = entryRow(page, user.login);
		await expect(row).toBeVisible();
		await row.locator('input[name^="delete_"]').check();
		page.once('dialog', dialog => dialog.accept());
		await Promise.all([
			page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
			page.getByRole('button', { name: /Delete User/i }).click(),
		]);
		await expect.poll(() => userExists(user)).toBe(false);
		await leavePage(page, staffListUrl()); // users.php locks the users it lists
	} else {
		await page.goto(staffListUrl());
	}
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
	if (await entryRow(page, user.login).count() > 0) {
		await entryRow(page, user.login).locator('[id^=delete_]').check();
		page.once('dialog', dialog => dialog.accept());
		await Promise.all([page.waitForEvent('load'), page.getByRole('button', { name: 'Delete selected' }).click()]);
		await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
		await expect(entryRow(page, user.login)).toHaveCount(0);
	}
}

// A throwaway artifact left by a run that stopped partway, deleted from the Artifacts list, as a person would
async function removeThrowaway(page) {
	if (artifactCount(THROWAWAY) === 0) {
		return;
	}
	// the list is paged, so search for it first, as museum-checks.js does
	await page.goto(`/modules/formulize/index.php?fid=${formId('Artifacts')}`);
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
	await page.locator('input[name="search_artifacts_short_name"]').fill(THROWAWAY);
	await page.getByRole('link', { name: 'Short name' }).click();
	await expect(page.getByText(/ of 1\./).first()).toBeVisible();
	await entryRow(page, THROWAWAY).locator('[id^=delete_]').check();
	page.once('dialog', dialog => dialog.accept());
	await Promise.all([page.waitForEvent('load'), page.getByRole('button', { name: 'Delete selected' }).click()]);
	await expect.poll(() => artifactCount(THROWAWAY)).toBe(0);
}

// As 015 set them. Artifact Overseers' permissions go with the group.
async function restorePermissions(page) {
	await openArtifactsPermissions(page, [TEMPLATE_GROUP]);
	const deleteGroup = permBox(page, TEMPLATE_GROUP, 'delete_group_entries');
	const deleteOwn = permBox(page, TEMPLATE_GROUP, 'delete_own_entry');
	if (!await deleteGroup.isChecked() || await deleteOwn.isChecked()) {
		await deleteGroup.check();
		await deleteOwn.uncheck();
		await saveAdminForm(page);
		await openArtifactsPermissions(page, [TEMPLATE_GROUP]);
	}
	await expect(permBox(page, TEMPLATE_GROUP, 'delete_group_entries')).toBeChecked();
	await expect(permBox(page, TEMPLATE_GROUP, 'delete_own_entry')).not.toBeChecked();
}

async function restoreEverything(page) {
	await login(page);
	await removeThrowaway(page);
	await setSubformDeleteButton(page, false);
	await restorePermissions(page);
	await removeUser(page, BOTH);
	await removeUser(page, OVERSEER);
	await removeGroup(page, OVERSEERS);
}

// The subform in Main Vault, and an artifact's row in it
const subform = page => page.locator('.formulize-subform');
const artifactRow = (page, name) => subform(page).locator('tr').filter({ has: page.getByText(name, { exact: true }) });
const deleteButton = page => subform(page).getByRole('button', { name: 'Delete', exact: true });

async function openMainVault(page, username) {
	await login(page, username);
	await page.goto(mainVaultUrl());
	await expect(page.locator('.formulize-subform-table')).toBeVisible();
}

async function leaveMainVault(page) {
	await leavePage(page, '/modules/formulize/');
	await expect(page.locator('body')).toBeVisible();
}

// Every artifact named has a row, and a checkbox only if it's in the ones that can be deleted
async function expectBoxes(page, { shown, deletable }) {
	for (const name of shown) {
		await expect(artifactRow(page, name)).toHaveCount(1);
		await expect(artifactRow(page, name).locator('input.delbox')).toHaveCount(deletable.includes(name) ? 1 : 0);
	}
}

test.beforeAll(async ({ browser }) => {
	// a run that stopped partway could have left these behind
	test.setTimeout(300000);
	const page = await browser.newPage();
	await login(page);
	await removeThrowaway(page);
	await removeUser(page, BOTH);
	await removeUser(page, OVERSEER);
	await removeGroup(page, OVERSEERS);
	await page.close();
});

let restored = false; // set by the last test, so afterAll only puts things back when a test stopped partway

test.afterAll(async ({ browser }) => {
	if (restored) {
		return;
	}
	test.setTimeout(300000);
	const page = await browser.newPage();
	await restoreEverything(page);
	await page.close();
});

test('make a user in both departments, and one in Ancient History who is an Artifact Overseer', async ({ page }) => {
	test.setTimeout(300000);
	await login(page);
	await makeStaffUser(page, BOTH);
	await makeStaffUser(page, OVERSEER);
	await makeGroup(page, OVERSEERS);
	await addMember(page, OVERSEERS, OVERSEER);
	expect(groupsOf(BOTH)).toEqual(expect.arrayContaining(['Ancient History - All Users', 'Modern History - All Users']));
	expect(groupsOf(OVERSEER)).toEqual(expect.arrayContaining(['Ancient History - All Users', OVERSEERS]));
	for (const user of [BOTH, OVERSEER]) {
		expect(groupsOf(user)).not.toContain('Ancient History - Curators');
		expect(groupsOf(user)).not.toContain('Modern History - Curators');
	}
	expect(groupsOf(OVERSEER)).not.toContain('Modern History - All Users');
});

test('department users delete their own artifacts, and Artifact Overseers view everything and delete their groups\'', async ({ page }) => {
	test.setTimeout(300000);
	await login(page);
	await openArtifactsPermissions(page, [TEMPLATE_GROUP, OVERSEERS]);
	await permBox(page, TEMPLATE_GROUP, 'delete_group_entries').uncheck();
	await permBox(page, TEMPLATE_GROUP, 'delete_own_entry').check();
	await permBox(page, OVERSEERS, 'view_globalscope').check();
	await permBox(page, OVERSEERS, 'delete_group_entries').check();
	await saveAdminForm(page);
	// from a fresh load
	await openArtifactsPermissions(page, [TEMPLATE_GROUP, OVERSEERS]);
	await expect(permBox(page, TEMPLATE_GROUP, 'delete_group_entries')).not.toBeChecked();
	await expect(permBox(page, TEMPLATE_GROUP, 'delete_own_entry')).toBeChecked();
	await expect(permBox(page, OVERSEERS, 'view_form')).not.toBeChecked(); // so it isn't one of their groups
	await expect(permBox(page, OVERSEERS, 'view_globalscope')).toBeChecked();
	await expect(permBox(page, OVERSEERS, 'delete_group_entries')).toBeChecked();
});

test('the Artifacts subform in Main Vault shows its Delete button', async ({ page }) => {
	await login(page);
	await setSubformDeleteButton(page, true);
});

test('mhstaff can check their own artifacts', async ({ page }) => {
	await openMainVault(page, 'mhstaff');
	await expectBoxes(page, { shown: MODERN, deletable: MODERN });
	for (const name of ANCIENT) {
		await expect(artifactRow(page, name)).toHaveCount(0);
	}
	await leaveMainVault(page);
});

test('someone else in Modern History sees those artifacts, and can\'t check them', async ({ page }) => {
	await openMainVault(page, BOTH.login);
	await expectBoxes(page, { shown: [...ANCIENT, ...MODERN], deletable: [] });
	await leaveMainVault(page);
});

test('an Artifact Overseer in Ancient History sees every artifact, and can check only Ancient History\'s', async ({ page }) => {
	await openMainVault(page, OVERSEER.login);
	await expectBoxes(page, { shown: [...ANCIENT, ...MODERN], deletable: ANCIENT });
	// checking one shows the Delete button, and nothing is deleted here
	await expect(deleteButton(page)).toBeHidden();
	await artifactRow(page, 'Roman Coin').locator('input.delbox').check();
	await expect(deleteButton(page)).toBeVisible();
	await artifactRow(page, 'Roman Coin').locator('input.delbox').uncheck();
	await expect(deleteButton(page)).toBeHidden();
	await leaveMainVault(page);
});

test('deleting checks each entry, whatever was posted', async ({ page }) => {
	// An artifact of the overseer's own, which they can delete, in Main Vault
	await login(page, OVERSEER.login);
	await page.goto(`/modules/formulize/index.php?fid=${formId('Artifacts')}`);
	await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
	await page.getByRole('textbox', { name: 'Short name *' }).fill(THROWAWAY);
	await page.getByRole('spinbutton', { name: 'Height', exact: true }).fill('1');
	await page.getByRole('spinbutton', { name: 'Width', exact: true }).fill('1');
	await page.getByRole('spinbutton', { name: 'Depth', exact: true }).fill('1');
	await page.getByRole('spinbutton', { name: 'Year', exact: true }).fill('1900');
	await page.getByRole('radio', { name: 'CE', exact: true }).check();
	await saveFormulizeForm(page);
	await page.getByRole('link', { name: 'Location and loans', exact: true }).click();
	const location = page.locator('.form-row:has(.formulize-label-artifacts_location) select');
	await expect(location).toBeVisible();
	await location.selectOption({ label: 'Main Vault' });
	await saveFormulizeForm(page);
	await saveAndClose(page);
	await expect(page.getByRole('button', { name: 'Add Artifact', exact: true })).toBeVisible();

	// Check it, and post a box for the Florentine Book with it, which the overseer has no box for. Deleting the one shows
	// the request was carried out, and the Florentine Book still being there shows the other was refused.
	await page.goto(mainVaultUrl());
	await expect(page.locator('.formulize-subform-table')).toBeVisible();
	await expect(artifactRow(page, 'Florentine Book').locator('input.delbox')).toHaveCount(0);
	await artifactRow(page, THROWAWAY).locator('input.delbox').check();
	await page.evaluate(id => {
		const box = document.createElement('input');
		box.type = 'checkbox';
		box.className = 'delbox';
		box.name = 'delbox' + id;
		box.value = id;
		box.checked = true;
		box.hidden = true;
		document.querySelector('.formulize-subform .formulize-subform__body').appendChild(box);
	}, artifactEntryId('Florentine Book'));
	page.once('dialog', dialog => dialog.accept());
	await Promise.all([
		page.waitForEvent('load'),
		deleteButton(page).click(),
	]);
	await expect(page.locator('.formulize-subform-table')).toBeVisible();
	await expect(artifactRow(page, THROWAWAY)).toHaveCount(0);
	await expect(artifactRow(page, 'Florentine Book')).toHaveCount(1);
	expect(artifactCount(THROWAWAY)).toBe(0);
	expect(artifactCount('Florentine Book')).toBe(1);
	await leaveMainVault(page);
});

test('an Artifact Overseer in both departments can check every artifact, since both are their groups', async ({ page }) => {
	await login(page);
	await addMember(page, OVERSEERS, BOTH);
	await page.goto('/user.php?op=logout');
	await openMainVault(page, BOTH.login);
	await expectBoxes(page, { shown: [...ANCIENT, ...MODERN], deletable: [...ANCIENT, ...MODERN] });
	await leaveMainVault(page);
});

test('put the museum back', async ({ page }) => {
	test.setTimeout(300000);
	await restoreEverything(page);
	expect(userExists(BOTH)).toBe(false);
	expect(userExists(OVERSEER)).toBe(false);
	expect(groupId(OVERSEERS)).toBeNull();
	expect(artifactCount(THROWAWAY)).toBe(0);
	restored = true;
});
