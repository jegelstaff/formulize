const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import {
	login, leavePage, saveAdminForm, waitForAdminPageReady, conditionalChecksFinished, elementId, dbQuery, dbPrefix, sqlEscape, ensureMainMenuOpen,
	applyColumnChanges, openAllColumnSections, showPermissionsFor, getUserByLogin, openElementSettings, removeConditions,
} from '../../utils';
import {
	casesFor, howToCheck, namesIn, conditionsFor, conditionsWithCaptionsFor, expectedNames, namesOnPage, namesWhereShown, searchesFor, searchList,
	disagreementIn, checkRow, ARTIFACT_ELEMENTS, DONOR_ELEMENTS, COLLECTION_ELEMENTS, LOAN_ELEMENTS, OFFER_ELEMENTS,
} from '../condition-cases';

// Conditions and searches as a person meets them. Each row of conditions-and-searches.md is a setting and what should then be on
// the page, so this sets the condition with MCP, or types it into the admin UI, or types the search into the list, opens the page
// as curator1, and compares what's there with the row's Should show. That file says where to look in each section, and which names
// can show there, and every row is read the same way. Nothing a row checks is written here.
//
// Conditions take two paths, SQL for finding entries and an evaluation of one entry for displaying elements (see
// ai-context/conditions.md), and sections 1 to 4 and 6 are the first and section 5 is the second. Section 7 is the search boxes
// on a list, which go through formulize_parseFilter(), different code again, so a fix to one doesn't fix the others. Section 8
// types each row's conditions into the admin UI, the way a webmaster does, for the settings MCP can't reach. Sections 9 and 10 are
// sites that show dates another way. Section 11 is a group's filter, which is SQL of its own, and section 12 is the values that
// = conditions give a new entry. Section 13 is the values a list's Search box offers, which have to be the ones the element
// would offer in a form.
//
// How a section is built:
//   - Each row sets its condition, opens the page and compares. A row that disagrees is recorded, and the section carries on.
//     So is a row that can't be checked, ie: MCP refuses its setting or the page doesn't draw, with any PHP error logged
//     while it ran. See checkRow in condition-cases.js.
//   - The last test fails with every disagreement, by row. maxFailures stops a run at the first failure, and one run on GitHub
//     should show every condition and search, so they're all in this spec and nothing else fails on a disagreement. A test
//     only fails sooner if something is really wrong outside the rows.
//   - A row that should find nothing is loaded again to prove the query ran. A query that fails is treated as finding no
//     entries, and nothing is logged, so it looks the same. See provingConditions below.
//
// This changes settings on elements and screens other specs use, so it's in 03-validate-sequential. It puts them back in the
// test before last, and again in afterAll in case a section stopped partway.
//
// It runs after 040, which turns the MCP server on, makes an API key and leaves them there. No entry is saved on purpose, except in
// section 12, which adds an artifact for each row and deletes it again. Everything is put back checks they've gone. But changing
// to a tab of the Artifact screen saves the tab it's leaving. A subform filter of = with a { } reference writes the
// main entry's value into the subform entries on a save, so Keep filtered values in sync is off for section 4, and the Loan
// Requests are compared with what they were at the end. Date elements always submit YYYY-MM-DD, so a save is safe in section 9.
//
// Sections 9 and 10 change the site's Short date format in the Settings page, which every date on the site follows until it's put
// back. The spec checks a date on the page before it runs the rows, because a save that can't write its file says nothing.

// A click or key press that submits a page waits for the server to answer, and with no time limit of its own it waits as long
// as the test can run, which is an hour for section 7. With one, a row the server never answers is recorded and the rest run.
test.use({ baseURL: E2E_TEST_BASE_URL, actionTimeout: 30000 });

test.describe.configure({ mode: 'serial' });

const MCP = '/mcp/index.php/mcp';
const SECONDS_PER_ROW = 45; // room for rows that can't be checked, which each wait out the expect timeout
const SECONDS_PER_ADMIN_UI_ROW = 300; // typing conditions into the admin UI saves and reloads the page for each one

const MAIN_VAULT_SUBFORM = 'locations_subform_artifacts';
const LOANS_SUBFORM = 'artifacts_subform_loan_requests';
const ARTIFACT_OPTIONS = 'loans_artifact';
const DONOR_OPTIONS = 'loans_donor';
const COLLECTION_OPTIONS = 'loans_collections';
const FOLLOW_UP_OPTIONS = 'visits_follow_up';
const DISPLAYED_ELEMENT = 'artifacts_curator_note';
const WEAPONS_SUBFORM = 'collections_subform_artifacts';

// The Donor element on Artifacts, whose Search box in the Catalogue list section 13 reads. It shows each donor's name, the way
// 010 made it, and a row with a Shows column has it show another field of the donor instead, from this list.
const DONOR_COLUMN = 'artifacts_donor';
const DONOR_COLUMN_SOURCE = 'donors_name';
const DONOR_COLUMN_SHOWS = { 'Province': 'donors_province' };
const WEAPONS_SUBFORM_DELETE_LINKS = '#filterdiv > a.conditionsdelete';

// The Artifacts subform in Main Vault has no Add or Delete buttons, the way 022 made it. Section 12 turns them on to add an
// artifact and delete it again, and putSettingsBack turns them off.
const MAIN_VAULT_SUBFORM_AS_SETUP_LEFT_IT = { showAddButton: 0, showDeleteButton: 0 };

// The condition boxes in the admin UI that sections 8 and 11 type each row's conditions into, by the letter of the rows. open
// goes to the page they're on. filter is the name the boxes are made with, so the "match all" set is new_<filter>_element and
// the rest, and "match one or more" is new_<filter>_oom_element. list is where the conditions are listed once they're saved,
// a selector or a function that finds it, and deleteLinks are their Xs. Section 11's are named for its group, see auditorsFilterBoxes.
const ADMIN_UI_CONDITIONS = {
	A: { open: page => openElementSettings(page, 'loans_end_date', 'Display Settings'), filter: 'disabledconditions', list: '#disabledconditions', deleteLinks: '#disabledconditions > a.conditionsdelete', elements: LOAN_ELEMENTS },
	E: { open: page => openElementSettings(page, 'loans_donor', 'Advanced'), filter: 'dynamicDefaultConditions', list: '#dynamic-defaults', deleteLinks: 'div#dynamic-defaults a.conditionsdelete', elements: ARTIFACT_ELEMENTS },
	M: { open: page => openElementSettings(page, WEAPONS_SUBFORM, 'Options'), filter: 'subformfilter', list: '#filterdiv', deleteLinks: WEAPONS_SUBFORM_DELETE_LINKS, elements: ARTIFACT_ELEMENTS },
};

// What the admin UI calls the fields every entry has, in its lists of elements for conditions
const CONDITION_LABELS = {
	'creation_datetime': 'Creation date',
	'mod_datetime': 'Last modification date',
};

// Section 11's group, and the one person in it, who come from outside the museum to check its records. 023 makes them, and gives
// them every artifact. The group's filter decides which artifacts its members see, so no one else is in it.
const AUDITORS = 'Auditors';
const AUDITOR = 'auditor1';

// The lists in sections 6 and 7, by the name rows give them. A list with a handle is a list screen, opened by its address. The
// Artifacts list is the Artifacts form's own list, opened from the menu, and it has no fundamental filters.
const LISTS = {
	'Catalogue': { handle: 'artifact_catalogue', names: 'Artifact', elements: ARTIFACT_ELEMENTS },
	'Donor artifacts': { handle: 'donor_artifacts', names: 'Donor', elements: { ...ARTIFACT_ELEMENTS, ...DONOR_ELEMENTS } },
	'Artifacts': { open: openArtifactsListWithDates, names: 'Artifact', elements: ARTIFACT_ELEMENTS },
};
const LIST_SCREENS = Object.values(LISTS).filter(list => list.handle);

// What a row's On screen cell can put on the page in a new Loan Request. A value is what to fill in to have something to clear.
// A box to type in is found by its role in the element's row: a number box is a spinbutton, and a date box is a textbox, as in 020.
const ON_SCREEN = {
	'Era wanted': { handle: 'loans_era', kind: 'radio' },
	'Made after year': { handle: 'loans_made_after', kind: 'typed', role: 'spinbutton' },
	'Start date': { handle: 'loans_start_date', kind: 'typed', role: 'textbox', value: '2003-01-01' },
	'Materials they can display': { handle: 'loans_materials', kind: 'checkboxes', value: 'Iron' },
	'Collections wanted': { handle: 'loans_collections', kind: 'checkboxes', value: 'Coins' },
	'Donor contact': { handle: 'loans_donor', kind: 'list' },
	'Borrower': { handle: 'loans_borrower', kind: 'typed', role: 'textbox' },
	'Approved': { handle: 'loans_approved', kind: 'radio' },
	'Storage they have': { handle: 'loans_storage', kind: 'radio' },
};

let api; // talks to MCP, with its own connection, since afterAll has no request fixture
let apiKey;
let requestId = 0;
let loanRequestsToStart;
let artifactsToStart;
let keepInSyncToStart;
let shortDateFormatToRestore = null; // set while section 9 has the site showing dates another way
let weaponsSubformToStart = null; // the Weapons subform's settings, once anything an interrupted run left is taken away
let weaponsSubformMayHaveConditions = false; // set while section 8 is typing conditions into it
let auditorsMayHaveFilter = false; // set while section 11 is typing filters for the Auditors
const disagreements = [];

function formId(title) {
	return dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title = '${sqlEscape(title)}'`)[0][0];
}

function entryId(form, handle, name) {
	return dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_${form} WHERE ${handle} = '${sqlEscape(name)}'`)[0][0];
}

function screenId(handle) {
	return dbQuery(`SELECT sid FROM ${dbPrefix()}_formulize_screen WHERE screen_handle = '${sqlEscape(handle)}'`)[0][0];
}

function artifactIds() {
	return dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_artifacts ORDER BY entry_id`).map(row => row[0]);
}

function groupNamed(name) {
	const rows = dbQuery(`SELECT groupid FROM ${dbPrefix()}_groups WHERE name = '${sqlEscape(name)}'`);
	return rows.length ? parseInt(rows[0][0], 10) : null;
}

function loanRequests() {
	return dbQuery(`SELECT entry_id, loans_borrower, loans_artifact, loans_approved, loans_start_date, loans_end_date, loans_donor FROM ${dbPrefix()}_formulize_loan_requests ORDER BY entry_id`);
}

// Whether the Loan requests subform keeps filtered values in sync, from its serialized settings
function keepInSyncSetting() {
	const eleValue = dbQuery(`SELECT ele_value FROM ${dbPrefix()}_formulize WHERE ele_handle = '${LOANS_SUBFORM}'`)[0][0];
	const setting = eleValue.match(/"enforceFilterChanges";(?:i:|s:1:")(\d)/);
	return setting ? parseInt(setting[1], 10) : 1;
}

async function tool(name, args) {
	const response = await api.post(MCP, {
		headers: { 'Authorization': `Bearer ${apiKey}` },
		data: { jsonrpc: '2.0', id: ++requestId, method: 'tools/call', params: { name, arguments: args } },
	});
	const body = await response.json();
	// MCP doesn't log why it refused something, so its message goes in the row's readout
	expect(body.error, `${name} ${JSON.stringify(args)} should succeed, and MCP said: ${body.error?.message ?? ''}`).toBeUndefined();
	return JSON.parse(body.result.content[0].text);
}

const setSubformFilter = (handle, conditions, keepInSync = 1, otherProperties = {}) =>
	tool('update_subform_interface', { element_identifier: handle, properties: { sourceEntryFilter: conditions, keepFilteredValuesInSync: keepInSync, ...otherProperties } });
const setOptionsFilter = (handle, conditions) =>
	tool('update_linked_list_element', { element_identifier: handle, properties: { sourceEntryFilter: conditions } });
const setDisplayConditions = conditions =>
	tool('update_text_box_element', { element_identifier: DISPLAYED_ELEMENT, display_conditions: conditions });
const setFundamentalFilters = (listHandle, conditions) =>
	tool('update_list_screen', { screen_id: parseInt(screenId(listHandle), 10), fundamental_filters: conditions });
const setDonorColumn = (conditions, sourceElement = DONOR_COLUMN_SOURCE) =>
	tool('update_linked_list_element', { element_identifier: DONOR_COLUMN, properties: { sourceElement, sourceEntryFilter: conditions } });

async function putSettingsBack() {
	await setDonorColumn([]);
	await setSubformFilter(MAIN_VAULT_SUBFORM, [], 1, MAIN_VAULT_SUBFORM_AS_SETUP_LEFT_IT);
	await setSubformFilter(LOANS_SUBFORM, [], keepInSyncToStart);
	await setOptionsFilter(ARTIFACT_OPTIONS, []);
	await setOptionsFilter(DONOR_OPTIONS, []);
	await setOptionsFilter(COLLECTION_OPTIONS, []);
	await setOptionsFilter(FOLLOW_UP_OPTIONS, []);
	await setDisplayConditions([]);
	for (const list of LIST_SCREENS) {
		await setFundamentalFilters(list.handle, []);
	}
}

const listScreenUrls = new Map();

// Open one of the lists, on a fresh load
async function openList(page, list) {
	if (list.open) {
		await list.open(page);
		return;
	}
	if (!listScreenUrls.has(list.handle)) {
		listScreenUrls.set(list.handle, `/modules/formulize/index.php?sid=${screenId(list.handle)}`);
	}
	await page.goto(listScreenUrls.get(list.handle));
}

function compare(row, expected, shown) {
	const disagreement = disagreementIn(row['Row'], expected, shown);
	if (disagreement) {
		disagreements.push(disagreement);
	}
}

// A row that finds nothing looks the same as a query that failed, because a failed query is treated as finding no entries, so
// this is what proves the query ran. All the row's conditions go in the "one or more" set, alongside one that must match. The
// entry it matches is only there if the query worked, and it can't be there because of the row, since the row found nothing.
function provingConditions(conditions, element, name) {
	return [
		...conditions.map(condition => ({ ...condition, type: 'match-one-or-more' })),
		{ element, operator: '=', value: name, type: 'match-one-or-more' },
	];
}

async function proveQueryRan(row, apply, read, name) {
	await apply();
	if (!(await read()).includes(name)) {
		disagreements.push(`${row['Row']}: the query didn't run. ${name} should have shown with the conditions in the "one or more" set`);
	}
}

async function openEntry(page, fid, entry) {
	await leavePage(page, `/modules/formulize/index.php?fid=${fid}&ve=${entry}`); // releases the lock on the entry before this one
	await page.waitForLoadState('load');
}

// Open an artifact on one of its tabs, and wait for an element on that tab
async function openArtifactTab(page, artifact, tab = 'Location and loans', elementOnTab = 'artifacts_location') {
	await openEntry(page, formId('Artifacts'), entryId('artifacts', 'artifacts_short_name', artifact));
	await page.getByRole('link', { name: tab, exact: true }).click();
	await expect(page.locator(`.form-row:has(.formulize-label-${elementOnTab}) select`)).toBeVisible();
	await page.waitForLoadState('load');
}

async function openNewLoanRequest(page) {
	await leavePage(page, `/modules/formulize/index.php?fid=${formId('Loan Requests')}`);
	await page.getByRole('button', { name: /^Add Loan Requests?$/ }).click();
	await expect(page.locator('.form-row:has(.formulize-label-loans_borrower) input[type="text"]')).toBeVisible();
	await page.waitForLoadState('load');
}

// Change something on the page and wait for the conditional check it sets off. The options may end up just as they were, so
// conditionalElementReady, which waits for a change, can't be used.
async function changeOnScreen(page, change) {
	await Promise.all([
		page.waitForResponse(response => response.url().includes('formulize_xhr_responder.php')),
		change(),
	]);
	await conditionalChecksFinished(page);
}

async function putOnScreen(page, onScreen) {
	const [caption, value] = onScreen.split(': ');
	const { handle, kind, role, value: valueToClear } = ON_SCREEN[caption];
	const row = page.locator(`.form-row:has(.formulize-label-${handle})`);
	if (value === '(blank)') {
		await putOnScreen(page, `${caption}: ${valueToClear}`);
	}
	if (kind === 'radio') {
		await changeOnScreen(page, () => row.getByRole('radio', { name: value, exact: true }).check());
	} else if (kind === 'list') {
		await changeOnScreen(page, () => row.locator('select').selectOption({ label: value }));
	} else if (kind === 'typed') {
		await changeOnScreen(page, async () => {
			const box = row.getByRole(role);
			await box.fill(value === '(blank)' ? '' : value);
			await box.press('Tab');
		});
	} else if (value === '(blank)') {
		await changeOnScreen(page, () => row.getByRole('checkbox', { name: valueToClear, exact: true }).uncheck());
	} else {
		for (const box of value.split(' + ')) {
			await changeOnScreen(page, () => row.getByRole('checkbox', { name: box, exact: true }).check());
		}
	}
}

const shortDateFormatBox = page => page.locator('[data-conf-name="shortdatestring"]').getByRole('textbox');

async function openSystemSettings(page) {
	await page.goto('/modules/formulize/admin/ui.php?page=settings&view=system');
	await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
}

async function currentShortDateFormat(page) {
	await openSystemSettings(page);
	return shortDateFormatBox(page).inputValue();
}

// The setting has to be saved by the Settings page, which is what writes the file the format is read from
async function setShortDateFormat(page, format) {
	const formatBefore = await currentShortDateFormat(page);
	await shortDateFormatBox(page).fill(format);
	await page.getByRole('button', { name: 'Save your changes' }).click();
	await expect(async () => {
		await openSystemSettings(page);
		await expect(shortDateFormatBox(page)).toHaveValue(format, { timeout: 5000 });
	}).toPass({ timeout: 60000 });
	return formatBefore;
}

// The dates on a list follow the format, once PHP has picked up the file it's in
async function expectCatalogueToShow(page, date) {
	await expect(async () => {
		await page.goto(`/modules/formulize/index.php?sid=${screenId('artifact_catalogue')}`);
		await expect(page.locator('[id^="celladdress_"]').filter({ hasText: date }).first()).toBeVisible({ timeout: 5000 });
	}).toPass({ timeout: 60000 });
}

async function asAdmin(page) {
	await page.goto('/user.php?op=logout');
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
}

async function asCurator(page) {
	await page.goto('/user.php?op=logout');
	await login(page, 'curator1', '12345');
}

function weaponsSubformSettings() {
	return dbQuery(`SELECT ele_value FROM ${dbPrefix()}_formulize WHERE ele_id = ${elementId(WEAPONS_SUBFORM)}`)[0][0];
}

// Type conditions into an element's condition boxes in the admin UI, as a webmaster does, once the ones there are taken away. Each
// is saved, and must then be in the list of conditions. The admin UI calls != NOT.
async function typeConditions(page, boxes, conditions) {
	await asAdmin(page);
	await removeConditions(page, boxes.open, boxes.deleteLinks);
	for (const { caption, operator, value, type } of conditions) {
		const set = type === 'match-all' ? boxes.filter : `${boxes.filter}_oom`;
		const op = operator === '!=' ? 'NOT' : operator;
		const label = CONDITION_LABELS[caption] ?? caption;
		await boxes.open(page);
		// the visible ones, since a page can draw the same boxes twice, ie: the Permissions tab has a hidden copy of each group's
		// filter, for forms that inherit their permissions
		await page.locator(`#new_${set}_element:visible`).selectOption({ label });
		await page.locator(`#new_${set}_op:visible`).selectOption(op);
		await page.locator(`#new_${set}_term:visible`).fill(value);
		await saveAdminForm(page);
		await boxes.open(page);
		const list = typeof boxes.list === 'function' ? boxes.list(page) : page.locator(boxes.list);
		await expect(list).toContainText(`${label} ${op} ${value}`);
	}
}

// The rows of a section whose conditions are typed into the admin UI. A row with the same conditions as the row before it uses them
// as they are, so they're typed again only when they change, or when a row stopped partway. Each row is read by the person reader
// logs in, curator1 unless it says. read opens the page and returns what the row's Should show is out of, and what's there.
async function checkAdminUIRows(page, letter, read, { boxes = ADMIN_UI_CONDITIONS[letter], reader = asCurator } = {}) {
	let typed = null;
	for (const row of casesFor(letter)) {
		await checkRow(page, row['Row'], disagreements, async () => {
			const conditions = conditionsWithCaptionsFor(row, boxes.elements);
			if (JSON.stringify(conditions) !== typed) {
				typed = null;
				await typeConditions(page, boxes, conditions);
				typed = JSON.stringify(conditions);
			}
			await reader(page);
			const { everything, shown } = await read(row);
			compare(row, expectedNames(row['Should show'], everything), shown);
		});
	}
}

// The Artifacts list, with the Date of acquisition column added for this visit
async function openArtifactsListWithDates(page) {
	await page.goto('/modules/formulize/');
	await ensureMainMenuOpen(page);
	await page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true }).click();
	const popupPromise = page.context().waitForEvent('page');
	await page.getByRole('button', { name: 'Change columns' }).click();
	const columns = await popupPromise;
	await columns.bringToFront();
	await openAllColumnSections(columns);
	await columns.getByRole('checkbox', { name: 'Date of acquisition' }).check();
	await applyColumnChanges(columns);
	await expect(page.locator('input[name="search_artifacts_date_of_acquisition"]')).toBeVisible();
}

// The Auditors' filter is on the Permissions tab of Artifacts, in the group's own panel, which the page shows once it's asked for
async function openAuditorsPermissions(page) {
	const fid = formId('Artifacts');
	const aid = dbQuery(`SELECT appid FROM ${dbPrefix()}_formulize_application_form_link WHERE fid = ${fid}`)[0][0];
	await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${fid}&aid=${aid}`);
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Permissions', exact: true }).click();
	await showPermissionsFor(page, [AUDITORS]);
}

// The boxes for the Auditors' filter, which are named for the form and the group, like the rest of section 8's ADMIN_UI_CONDITIONS.
// The Permissions tab draws them twice, in the group's panel, and in a hidden section for forms that inherit their permissions,
// so only the visible Xs count.
function auditorsFilterBoxes() {
	const filter = `${formId('Artifacts')}_${groupNamed(AUDITORS)}_filter`;
	return {
		open: openAuditorsPermissions,
		filter,
		list: page => page.getByRole('group', { name: AUDITORS }).first(),
		deleteLinks: `a.conditionsdelete[target^="${filter}_"]:visible`,
		elements: ARTIFACT_ELEMENTS,
	};
}

async function asAuditor(page) {
	await page.goto('/user.php?op=logout');
	await login(page, AUDITOR, '12345');
}

// Add one artifact with the Artifacts subform in Main Vault, which is open, as a person does, and return its entry id. Pressing
// Add saves the page, and the subform makes the new artifact, with the values its = conditions give a new entry.
async function addArtifactInMainVault(page) {
	const before = artifactIds();
	await Promise.all([
		page.waitForNavigation({ waitUntil: 'load', timeout: 120000 }),
		page.locator('.formulize-subform input[name="addsub"]').click(),
	]);
	let added = [];
	await expect.poll(() => {
		added = artifactIds().filter(id => !before.includes(id));
		return added.length;
	}, { message: 'Add in the subform should make one new artifact', timeout: 30000 }).toBe(1);
	return added[0];
}

// Delete artifacts with the Artifacts subform in Main Vault, as a person does: tick them, press Delete and say yes. The subform
// has no conditions while this happens, so an artifact a condition would leave out is there to tick. Keep filtered values in sync
// stays off, since deleting saves the page too.
async function deleteArtifactsInMainVault(page, ids) {
	await setSubformFilter(MAIN_VAULT_SUBFORM, [], 0, { showAddButton: 1, showDeleteButton: 1 });
	await openEntry(page, formId('Locations'), entryId('locations', 'locations_name', 'Main Vault'));
	const subform = page.locator('.formulize-subform');
	await expect(subform).toBeVisible();
	for (const id of ids) {
		await subform.locator(`input.delbox[name="delbox${id}"]`).check();
	}
	page.once('dialog', dialog => dialog.accept());
	await Promise.all([
		page.waitForNavigation({ waitUntil: 'load', timeout: 120000 }),
		subform.locator('input[name="deletesubs"]').click(),
	]);
	await expect.poll(() => artifactIds().filter(id => ids.includes(id)), { message: 'the artifacts should be deleted', timeout: 30000 }).toEqual([]);
}

// The artifacts that section 12 made, and hasn't deleted yet, ie: because a row stopped partway
const addedArtifacts = () => artifactIds().filter(id => !artifactsToStart.includes(id));

// How a row's Saved cell disagrees with what the artifact holds, if it does. The cell is Caption: value, separated by ;, and
// (blank) for nothing, ie: Era: BCE; Date of acquisition: (blank). Read from the database, since what's saved is the question.
function savedDisagreement(row, entry) {
	const problems = [];
	for (const saved of row['Saved'].split(';').map(part => part.trim()).filter(part => part !== '')) {
		const [caption, expected] = saved.split(': ');
		const value = dbQuery(`SELECT ${ARTIFACT_ELEMENTS[caption]} FROM ${dbPrefix()}_formulize_artifacts WHERE entry_id = ${parseInt(entry, 10)}`)[0][0];
		const stored = (value === 'NULL' || value === '') ? '(blank)' : value;
		if (stored !== expected) {
			problems.push(`${caption} is ${stored}, not ${expected}`);
		}
	}
	return problems.length ? `${row['Row']}: ${problems.join(', ')}` : null;
}

// Section 11's filter on the Auditors is taken off, and the artifacts section 12 added are deleted, here as well as in the sections,
// in case one of them stopped partway. As a webmaster.
async function undoSections11And12(page) {
	if (auditorsMayHaveFilter) {
		await asAdmin(page);
		await removeConditions(page, openAuditorsPermissions, auditorsFilterBoxes().deleteLinks);
		auditorsMayHaveFilter = false;
	}
	if (addedArtifacts().length > 0) {
		await asAdmin(page);
		await deleteArtifactsInMainVault(page, addedArtifacts());
	}
}

test.beforeAll(async ({ browser, playwright }) => {
	api = await playwright.request.newContext({ baseURL: E2E_TEST_BASE_URL });
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	// masked on screen, and only available in the data-key attribute of its copy icon
	await page.goto('/modules/formulize/admin/ui.php?page=users&view=apikeys');
	await page.locator('.admin-ui').waitFor({ state: 'visible' });
	apiKey = await page.locator('td[id=key-1] .key-copy').getAttribute('data-key');
	await page.close();
	loanRequestsToStart = loanRequests();
	artifactsToStart = artifactIds();
	keepInSyncToStart = keepInSyncSetting();
});

test.afterAll(async ({ browser }) => {
	if (disagreements.length > 0) {
		console.log(`Conditions and searches that don't show what they should:\n${disagreements.join('\n')}`);
	}
	if (shortDateFormatToRestore !== null) {
		const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await setShortDateFormat(page, shortDateFormatToRestore);
		await page.close();
	}
	if (weaponsSubformMayHaveConditions) {
		const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
		await asAdmin(page);
		await removeConditions(page, ADMIN_UI_CONDITIONS.M.open, ADMIN_UI_CONDITIONS.M.deleteLinks);
		await page.close();
	}
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await undoSections11And12(page).catch(error => console.log(`What sections 11 and 12 changed could not all be put back: ${error.message}`));
	await page.close();
	await putSettingsBack();
	await api.dispose();
});

test('Section 1: the Artifacts subform in Main Vault', async ({ page }) => {
	const rows = casesFor('S');
	test.setTimeout(rows.length * SECONDS_PER_ROW * 1000);
	const { selector } = howToCheck('S');
	const everything = namesIn('Artifact');
	const fid = formId('Locations');
	const entry = entryId('locations', 'locations_name', 'Main Vault');
	const openAndRead = async () => {
		await openEntry(page, fid, entry);
		await expect(page.locator('.formulize-subform')).toBeVisible();
		return namesOnPage(page.locator(selector), everything);
	};

	await login(page, 'curator1', '12345');
	for (const row of rows) {
		await checkRow(page, row['Row'], disagreements, async () => {
			const conditions = conditionsFor(row, ARTIFACT_ELEMENTS);
			const expected = expectedNames(row['Should show'], everything);
			await setSubformFilter(MAIN_VAULT_SUBFORM, conditions);
			compare(row, expected, await openAndRead());
			if (expected.length === 0) {
				await proveQueryRan(row, () => setSubformFilter(MAIN_VAULT_SUBFORM, provingConditions(conditions, 'artifacts_short_name', 'Roman Coin')), openAndRead, 'Roman Coin');
			}
		});
	}
	await setSubformFilter(MAIN_VAULT_SUBFORM, []);
	await leavePage(page);
});

test('Section 2: { } in the Artifact list of a saved Loan Request', async ({ page }) => {
	const rows = casesFor('R');
	test.setTimeout(rows.length * SECONDS_PER_ROW * 1000);
	const { selector } = howToCheck('R');
	const everything = namesIn('Artifact');
	const fid = formId('Loan Requests');

	await login(page, 'curator1', '12345');
	for (const row of rows) {
		await checkRow(page, row['Row'], disagreements, async () => {
			await setOptionsFilter(ARTIFACT_OPTIONS, conditionsFor(row, ARTIFACT_ELEMENTS));
			await openEntry(page, fid, entryId('loan_requests', 'loans_borrower', row['Loan Request']));
			await expect(page.locator(`.form-row:has(.formulize-label-${ARTIFACT_OPTIONS}) select`)).toBeVisible();
			compare(row, expectedNames(row['Should show'], everything), await namesOnPage(page.locator(selector), everything));
		});
	}
	await setOptionsFilter(ARTIFACT_OPTIONS, []);
	await leavePage(page);
});

test('Section 3: { } in a new Loan Request, and as things change on screen', async ({ page }) => {
	const rows = casesFor('N');
	test.setTimeout(rows.length * SECONDS_PER_ROW * 1000);
	const { selector } = howToCheck('N');
	const everything = namesIn('Artifact');

	const readOptions = () => namesOnPage(page.locator(selector), everything);

	await login(page, 'curator1', '12345');
	for (const row of rows) {
		await checkRow(page, row['Row'], disagreements, async () => {
			const conditions = conditionsFor(row, ARTIFACT_ELEMENTS);
			const expected = expectedNames(row['Should show'], everything);
			await setOptionsFilter(ARTIFACT_OPTIONS, conditions);
			await openNewLoanRequest(page);
			if (row['On screen']) {
				await putOnScreen(page, row['On screen']);
			}
			compare(row, expected, await readOptions());
			if (expected.length === 0) {
				await proveQueryRan(row, () => setOptionsFilter(ARTIFACT_OPTIONS, provingConditions(conditions, 'artifacts_short_name', 'Roman Coin')), async () => {
					await openNewLoanRequest(page);
					return readOptions();
				}, 'Roman Coin');
			}
		});
	}
	await setOptionsFilter(ARTIFACT_OPTIONS, []);
	await leavePage(page);
});

test('Section 4: the Loan requests subform in an Artifact', async ({ page }) => {
	const rows = casesFor('L');
	test.setTimeout(rows.length * SECONDS_PER_ROW * 1000);
	const { selector } = howToCheck('L');

	await login(page, 'curator1', '12345');
	for (const row of rows) {
		await checkRow(page, row['Row'], disagreements, async () => {
			const everything = namesIn('Loan Request', request => request['Artifact'] === row['Artifact']);
			const conditions = conditionsFor(row, LOAN_ELEMENTS);
			const expected = expectedNames(row['Should show'], everything);
			const openAndRead = async () => {
				await openArtifactTab(page, row['Artifact']);
				await expect(page.locator('.formulize-subform')).toBeVisible();
				return namesOnPage(page.locator(selector), everything);
			};
			await setSubformFilter(LOANS_SUBFORM, conditions, 0);
			compare(row, expected, await openAndRead());
			if (expected.length === 0) {
				await proveQueryRan(row, () => setSubformFilter(LOANS_SUBFORM, provingConditions(conditions, 'loans_borrower', everything[0]), 0), openAndRead, everything[0]);
			}
		});
	}
	await setSubformFilter(LOANS_SUBFORM, [], keepInSyncToStart);
	await leavePage(page);
});

for (const section of [
	{ title: 'the Donor contact list', letter: 'D', handle: DONOR_OPTIONS, names: 'Donor', elements: DONOR_ELEMENTS, proof: ['donors_name', 'Freeform Solutions'] },
	{ title: 'the Collections wanted boxes', letter: 'C', handle: COLLECTION_OPTIONS, names: 'Collection', elements: COLLECTION_ELEMENTS, proof: ['collections_name', 'Coins'] },
]) {
	test(`Section 4: ${section.title} in a new Loan Request`, async ({ page }) => {
		const rows = casesFor(section.letter);
		test.setTimeout(rows.length * SECONDS_PER_ROW * 1000);
		const { selector } = howToCheck(section.letter);
		const everything = namesIn(section.names);
		const openAndRead = async () => {
			await openNewLoanRequest(page);
			return namesOnPage(page.locator(selector), everything);
		};

		await login(page, 'curator1', '12345');
		for (const row of rows) {
			await checkRow(page, row['Row'], disagreements, async () => {
				const conditions = conditionsFor(row, section.elements);
				const expected = expectedNames(row['Should show'], everything);
				await setOptionsFilter(section.handle, conditions);
				compare(row, expected, await openAndRead());
				if (expected.length === 0) {
					await proveQueryRan(row, () => setOptionsFilter(section.handle, provingConditions(conditions, ...section.proof)), openAndRead, section.proof[1]);
				}
			});
		}
		await setOptionsFilter(section.handle, []);
		await leavePage(page);
	});
}

test('Section 4: the Follow up on list in a Visit', async ({ page }) => {
	const rows = casesFor('O');
	test.setTimeout(rows.length * SECONDS_PER_ROW * 1000);
	const { selector } = howToCheck('O');
	const everything = namesIn('Offer');
	const fid = formId('Visits');

	await login(page, 'curator1', '12345');
	for (const row of rows) {
		await checkRow(page, row['Row'], disagreements, async () => {
			const conditions = conditionsFor(row, OFFER_ELEMENTS);
			const expected = expectedNames(row['Should show'], everything);
			const openAndRead = async () => {
				await openEntry(page, fid, entryId('visits', 'visits_name', row['Visit']));
				await expect(page.locator(`.form-row:has(.formulize-label-${FOLLOW_UP_OPTIONS}) select`)).toBeVisible();
				return namesOnPage(page.locator(selector), everything);
			};
			await setOptionsFilter(FOLLOW_UP_OPTIONS, conditions);
			compare(row, expected, await openAndRead());
			if (expected.length === 0) {
				await proveQueryRan(row, () => setOptionsFilter(FOLLOW_UP_OPTIONS, provingConditions(conditions, 'offers_item', everything[0])), openAndRead, everything[0]);
			}
		});
	}
	await setOptionsFilter(FOLLOW_UP_OPTIONS, []);
	await leavePage(page);
});

test('Section 4: values in the page\'s address', async ({ page }) => {
	const rows = casesFor('Q');
	test.setTimeout(rows.length * SECONDS_PER_ROW * 1000);
	const { selector } = howToCheck('Q');
	const everything = namesIn('Artifact');
	const fid = formId('Locations');
	const entry = entryId('locations', 'locations_name', 'Main Vault');

	await login(page, 'curator1', '12345');
	for (const row of rows) {
		await checkRow(page, row['Row'], disagreements, async () => {
			await setSubformFilter(MAIN_VAULT_SUBFORM, conditionsFor(row, ARTIFACT_ELEMENTS));
			const address = row['In the address'] ? `&${row['In the address']}` : '';
			await leavePage(page, `/modules/formulize/index.php?fid=${fid}&ve=${entry}${address}`);
			await page.waitForLoadState('load');
			await expect(page.locator('.formulize-subform')).toBeVisible();
			compare(row, expectedNames(row['Should show'], everything), await namesOnPage(page.locator(selector), everything));
		});
	}
	await setSubformFilter(MAIN_VAULT_SUBFORM, []);
	await leavePage(page);
});

// Set a row's display conditions, and open the artifacts in its Open cell to see which of them the element is on
async function checkDisplayRow(page, row, selector) {
	await setDisplayConditions(conditionsFor(row, ARTIFACT_ELEMENTS));
	const opened = expectedNames(row['Open'], namesIn('Artifact'));
	compare(row, expectedNames(row['Should show'], opened), await namesWhereShown(page, opened, artifact => openArtifactTab(page, artifact), selector));
}

test('Section 5: display conditions', async ({ page }) => {
	const rows = casesFor('V');
	test.setTimeout(rows.length * 2 * SECONDS_PER_ROW * 1000);
	const { selector } = howToCheck('V');

	await login(page, 'curator1', '12345');
	for (const row of rows) {
		await checkRow(page, row['Row'], disagreements, async () => {
			await checkDisplayRow(page, row, selector);
		});
	}
	await setDisplayConditions([]);
	await leavePage(page);
});

test('Section 6: fundamental filters', async ({ page }) => {
	const rows = casesFor('F');
	test.setTimeout(rows.length * SECONDS_PER_ROW * 1000);
	const { selector } = howToCheck('F');

	await login(page, 'curator1', '12345');
	for (const row of rows) {
		await checkRow(page, row['Row'], disagreements, async () => {
			const list = LISTS[row['List']];
			const everything = namesIn(list.names);
			await setFundamentalFilters(list.handle, conditionsFor(row, list.elements));
			await openList(page, list);
			await expect(page.getByText(/Showing entries:|No entries were found/).first()).toBeVisible();
			compare(row, expectedNames(row['Should show'], everything), await namesOnPage(page.locator(selector), everything));
		});
	}
	for (const list of LIST_SCREENS) {
		await setFundamentalFilters(list.handle, []);
	}
	await leavePage(page);
});

// Every row is one search, on a fresh load of the list. Nothing is changed, so this could run anywhere, but it's here so that a
// search that disagrees doesn't stop the conditions from being checked.
test('Section 7: searches on a list', async ({ page }) => {
	const rows = casesFor('B');
	test.setTimeout(rows.length * SECONDS_PER_ROW * 1000);
	const { selector } = howToCheck('B');

	await login(page, 'curator1', '12345');
	for (const row of rows) {
		await checkRow(page, row['Row'], disagreements, async () => {
			const list = LISTS[row['List']];
			const everything = namesIn(list.names);
			await searchList(page, () => openList(page, list), searchesFor(row, list.elements));
			compare(row, expectedNames(row['Should show'], everything), await namesOnPage(page.locator(selector), everything));
		});
	}
	await leavePage(page);
});

test('Section 8: a disabled condition, made in the admin UI', async ({ page }) => {
	test.setTimeout(casesFor('A').length * SECONDS_PER_ADMIN_UI_ROW * 1000);
	const { selector } = howToCheck('A');
	const openRequest = async borrower => {
		await openEntry(page, formId('Loan Requests'), entryId('loan_requests', 'loans_borrower', borrower));
		await expect(page.locator('.form-row:has(.formulize-label-loans_borrower)')).toBeVisible();
	};

	// End date is disabled for every group when its conditions are met. The rows give the conditions.
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openElementSettings(page, 'loans_end_date', 'Display Settings');
	await page.locator('select[name="elements_ele_disabled\\[\\]"]').selectOption('all');
	await saveAdminForm(page);

	await checkAdminUIRows(page, 'A', async row => {
		const everything = expectedNames(row['Open'], namesIn('Loan Request'));
		return { everything, shown: await namesWhereShown(page, everything, openRequest, selector) };
	});
	await leavePage(page, '/user.php?op=logout');

	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await removeConditions(page, ADMIN_UI_CONDITIONS.A.open, ADMIN_UI_CONDITIONS.A.deleteLinks);
	await page.locator('select[name="elements_ele_disabled\\[\\]"]').selectOption('none');
	await saveAdminForm(page);
	await openElementSettings(page, 'loans_end_date', 'Display Settings');
	await expect(page.locator('#disabledconditions > a.conditionsdelete')).toHaveCount(0);
	await expect(page.locator('select[name="elements_ele_disabled\\[\\]"] option:checked')).toHaveText('Disable for no groups');
});

test('Section 8: a dynamic default, made in the admin UI', async ({ page }) => {
	test.setTimeout(casesFor('E').length * SECONDS_PER_ADMIN_UI_ROW * 1000);
	const { selector } = howToCheck('E');
	const everything = namesIn('Donor');

	// Donor contact defaults to the Donor of the artifact that meets the conditions, when it's blank. The rows give the conditions,
	// which are only on offer once there's a source.
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openElementSettings(page, 'loans_donor', 'Advanced');
	await page.locator('select[name="elements-ele_dynamicdefault_source"]').selectOption(String(elementId('artifacts_donor')));
	await page.locator('#elements-ele_use_default_when_blank1').check();
	await saveAdminForm(page);

	// looked at and not saved, so the Loan Requests still have no donor contact
	await checkAdminUIRows(page, 'E', async row => {
		if (row['Loan Request'] === '(new)') {
			await openNewLoanRequest(page);
		} else {
			await openEntry(page, formId('Loan Requests'), entryId('loan_requests', 'loans_borrower', row['Loan Request']));
			await expect(page.locator(`.form-row:has(.formulize-label-${DONOR_OPTIONS}) select`)).toBeVisible();
		}
		return { everything, shown: await namesOnPage(page.locator(selector), everything) };
	});
	await leavePage(page, '/user.php?op=logout');

	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await removeConditions(page, ADMIN_UI_CONDITIONS.E.open, ADMIN_UI_CONDITIONS.E.deleteLinks);
	await page.locator('select[name="elements-ele_dynamicdefault_source"]').selectOption({ index: 0 });
	await page.locator('#elements-ele_use_default_when_blank0').check();
	await saveAdminForm(page);
	await openElementSettings(page, 'loans_donor', 'Advanced');
	await expect(page.locator('select[name="elements-ele_dynamicdefault_source"]')).toHaveJSProperty('selectedIndex', 0);
});

// The conditions are removed with each one's X, so the subform's settings end up exactly as setup left them. Weapons is only looked
// at, never saved.
test('Section 8: conditions typed in the admin UI, on the Artifacts subform of a collection', async ({ page }) => {
	test.setTimeout(casesFor('M').length * SECONDS_PER_ADMIN_UI_ROW * 1000);
	const { selector } = howToCheck('M');
	const everything = namesIn('Artifact', artifact => artifact['Collections'].split(', ').includes('Weapons'));
	weaponsSubformMayHaveConditions = true;
	await asAdmin(page);
	await removeConditions(page, ADMIN_UI_CONDITIONS.M.open, ADMIN_UI_CONDITIONS.M.deleteLinks); // anything an interrupted run left
	weaponsSubformToStart = weaponsSubformSettings();

	await checkAdminUIRows(page, 'M', async () => {
		await openEntry(page, formId('Collections'), entryId('collections', 'collections_name', 'Weapons'));
		await expect(page.locator('.formulize-subform')).toBeVisible();
		const shown = await namesOnPage(page.locator(selector), everything);
		await leavePage(page); // releases the entry lock
		return { everything, shown };
	});

	await asAdmin(page);
	await removeConditions(page, ADMIN_UI_CONDITIONS.M.open, ADMIN_UI_CONDITIONS.M.deleteLinks);
	weaponsSubformMayHaveConditions = false;
	await leavePage(page, '/user.php?op=logout');
});

test('Section 9: a site that shows dates as d/m/Y', async ({ page }) => {
	const conditionRows = casesFor('T');
	const displayRows = casesFor('W');
	const searchRows = casesFor('U');
	test.setTimeout((conditionRows.length + displayRows.length * 2 + searchRows.length) * SECONDS_PER_ROW * 1000 + 240000);
	const everything = namesIn('Artifact');
	const fid = formId('Locations');
	const mainVault = entryId('locations', 'locations_name', 'Main Vault');
	const catalogue = `/modules/formulize/index.php?sid=${screenId('artifact_catalogue')}`;

	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	shortDateFormatToRestore = await setShortDateFormat(page, 'd/m/Y');
	await page.goto('/user.php?op=logout');
	await login(page, 'curator1', '12345');
	await expectCatalogueToShow(page, '31/12/2002');

	const { selector: conditionSelector } = howToCheck('T');
	for (const row of conditionRows) {
		await checkRow(page, row['Row'], disagreements, async () => {
			await setSubformFilter(MAIN_VAULT_SUBFORM, conditionsFor(row, ARTIFACT_ELEMENTS));
			await openEntry(page, fid, mainVault);
			await expect(page.locator('.formulize-subform')).toBeVisible();
			compare(row, expectedNames(row['Should show'], everything), await namesOnPage(page.locator(conditionSelector), everything));
		});
	}
	await setSubformFilter(MAIN_VAULT_SUBFORM, []);

	const { selector: displaySelector } = howToCheck('W');
	for (const row of displayRows) {
		await checkRow(page, row['Row'], disagreements, async () => {
			await checkDisplayRow(page, row, displaySelector);
		});
	}
	await setDisplayConditions([]);

	const { selector: searchSelector } = howToCheck('U');
	await leavePage(page, catalogue);
	for (const row of searchRows) {
		await checkRow(page, row['Row'], disagreements, async () => {
			await searchList(page, () => openList(page, LISTS['Catalogue']), searchesFor(row, ARTIFACT_ELEMENTS));
			compare(row, expectedNames(row['Should show'], everything), await namesOnPage(page.locator(searchSelector), everything));
		});
	}

	await page.goto('/user.php?op=logout');
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await setShortDateFormat(page, shortDateFormatToRestore);
	shortDateFormatToRestore = null;
	await expectCatalogueToShow(page, '2002-12-31');
});

// S is the day's suffix, so Bronze Mirror's date is 31st December 2002. MySQL's DATE_FORMAT() has one specifier for the day with its
// suffix, where PHP has two, so it has to be translated. A format with an escaped character, ie: \o\f, isn't here, because the
// Settings page didn't keep the backslashes in run 42554478615, see Problems found in conditions-and-searches.md.
const DATE_FORMAT_WITH_WORDS = 'jS F Y';

test('Section 10: a site that shows dates with words', async ({ page }) => {
	const rows = casesFor('G');
	test.setTimeout(rows.length * SECONDS_PER_ROW * 1000 + 240000);
	const { selector } = howToCheck('G');
	const everything = namesIn('Artifact');
	const fid = formId('Locations');
	const mainVault = entryId('locations', 'locations_name', 'Main Vault');

	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	shortDateFormatToRestore = await currentShortDateFormat(page);
	// If the site doesn't show dates this way once it's saved, every row would be wrong for that reason, so it's recorded once, the
	// rows aren't run, and the rest of the spec carries on
	let datesShownWithWords = false;
	await checkRow(page, 'G', disagreements, async () => {
		await setShortDateFormat(page, DATE_FORMAT_WITH_WORDS);
		await page.goto('/user.php?op=logout');
		await login(page, 'curator1', '12345');
		await expectCatalogueToShow(page, '31st December 2002');
		datesShownWithWords = true;
	});

	if (datesShownWithWords) {
		for (const row of rows) {
			await checkRow(page, row['Row'], disagreements, async () => {
				await setSubformFilter(MAIN_VAULT_SUBFORM, conditionsFor(row, ARTIFACT_ELEMENTS));
				await openEntry(page, fid, mainVault);
				await expect(page.locator('.formulize-subform')).toBeVisible();
				compare(row, expectedNames(row['Should show'], everything), await namesOnPage(page.locator(selector), everything));
			});
		}
		await setSubformFilter(MAIN_VAULT_SUBFORM, []);
	}

	await page.goto('/user.php?op=logout');
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await setShortDateFormat(page, shortDateFormatToRestore);
	shortDateFormatToRestore = null;
	await expectCatalogueToShow(page, '2002-12-31');
});

// A group's filter is SQL of its own, in buildPerGroupFilterWhereClause(), not a condition, so it has its own rules. Each row's
// filter is typed into the Auditors' panel on the Permissions tab of Artifacts, as a webmaster does, and auditor1, their one member,
// opens the Catalogue list. The filter is taken off afterwards, since 023 left the Auditors with none. If 023 didn't make them,
// every row would be wrong for that reason, so it's recorded once.
test('Section 11: a group\'s filter on a date', async ({ page }) => {
	const rows = casesFor('J');
	test.setTimeout(rows.length * SECONDS_PER_ADMIN_UI_ROW * 1000 + 300000);
	const { selector } = howToCheck('J');
	const everything = namesIn('Artifact');

	const auditorsThere = groupNamed(AUDITORS) !== null && getUserByLogin(AUDITOR) !== null;
	if (!auditorsThere) {
		disagreements.push(`J: could not be checked. 023 should have made the ${AUDITORS} group and ${AUDITOR}`);
		return;
	}
	auditorsMayHaveFilter = true;
	await checkAdminUIRows(page, 'J', async () => {
		await openList(page, LISTS['Catalogue']);
		await expect(page.getByText(/Showing entries:|No entries were found/).first()).toBeVisible();
		return { everything, shown: await namesOnPage(page.locator(selector), everything) };
	}, { boxes: auditorsFilterBoxes(), reader: asAuditor });

	// recorded rather than thrown, so the sections after this one still run. Everything is put back tries again, and fails if it can't.
	await checkRow(page, 'J', disagreements, async () => {
		await asAdmin(page);
		await removeConditions(page, openAuditorsPermissions, auditorsFilterBoxes().deleteLinks);
		auditorsMayHaveFilter = false;
	});
	await leavePage(page, '/user.php?op=logout');
});

// Each row puts its conditions on the Artifacts subform in Main Vault, adds an artifact with the subform's Add button, and reads
// what the new artifact holds. Then it deletes the artifact with the subform's Delete button, whatever the row found, so the next
// row, and every spec after this one, has only the museum's artifacts. As a webmaster, because no one else can delete them.
// Keep filtered values in sync is off, or the save that Add makes would give every artifact in Main Vault the row's values.
test('Section 12: what = conditions give a new entry', async ({ page }) => {
	const rows = casesFor('K');
	test.setTimeout(rows.length * 3 * SECONDS_PER_ROW * 1000);
	const fid = formId('Locations');
	const mainVault = entryId('locations', 'locations_name', 'Main Vault');

	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	for (const row of rows) {
		await checkRow(page, row['Row'], disagreements, async () => {
			await setSubformFilter(MAIN_VAULT_SUBFORM, conditionsFor(row, ARTIFACT_ELEMENTS), 0, { showAddButton: 1, showDeleteButton: 1 });
			await openEntry(page, fid, mainVault);
			await expect(page.locator('.formulize-subform')).toBeVisible();
			const disagreement = savedDisagreement(row, await addArtifactInMainVault(page));
			if (disagreement) {
				disagreements.push(disagreement);
			}
		});
		if (addedArtifacts().length > 0) {
			await checkRow(page, row['Row'], disagreements, () => deleteArtifactsInMainVault(page, addedArtifacts()));
		}
	}
	await setSubformFilter(MAIN_VAULT_SUBFORM, [], 1, MAIN_VAULT_SUBFORM_AS_SETUP_LEFT_IT);
	await leavePage(page);
});

// Every value the Donor column's Search box offers in the Catalogue list, apart from Show all and Show Blanks, which head every
// Search box's list. Unlike namesOnPage, a value that isn't one of the names counts too, since what mustn't be offered is any
// value at all, ie: the entry ids stored in Artifacts. The values come with the page, so they're all there once the list opens.
async function valuesOffered(page, selector) {
	await openList(page, LISTS['Catalogue']);
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
	await page.locator(`input[name="search_${DONOR_COLUMN}"]`).click();
	const choices = page.locator(selector);
	await expect(choices.first()).toHaveText('Show all');
	const text = await choices.evaluateAll(found => found.map(item => (item.innerText || item.textContent || '').trim()));
	return text.filter(value => value !== 'Show all' && value !== 'Show Blanks').sort();
}

test('Section 13: the values a list\'s Search box offers', async ({ page }) => {
	const rows = casesFor('P');
	test.setTimeout(rows.length * SECONDS_PER_ROW * 1000);
	const { selector } = howToCheck('P');
	const read = () => valuesOffered(page, selector);

	await login(page, 'curator1', '12345');
	for (const row of rows) {
		await checkRow(page, row['Row'], disagreements, async () => {
			const shows = row['Shows'] || '';
			const sourceElement = shows ? DONOR_COLUMN_SHOWS[shows] : DONOR_COLUMN_SOURCE;
			if (!sourceElement) {
				throw new Error(`The Donor list can't be made to show ${shows}`);
			}
			const conditions = conditionsFor(row, DONOR_ELEMENTS);
			const expected = expectedNames(row['Should show'], namesIn(shows ? `${shows} shown` : 'Donor'));
			if (expected.length === 0 && shows) {
				throw new Error('A row that offers nothing is proved by a donor\'s name showing, so it can\'t have a Shows column');
			}
			await setDonorColumn(conditions, sourceElement);
			compare(row, expected, await read());
			if (expected.length === 0) {
				await proveQueryRan(row, () => setDonorColumn(provingConditions(conditions, 'donors_name', 'Freeform Solutions')), read, 'Freeform Solutions');
			}
		});
	}
	await setDonorColumn([]);
	await leavePage(page);
});

test('Everything is put back', async ({ page }) => {
	await undoSections11And12(page);
	await putSettingsBack();
	expect(loanRequests(), 'nothing here should have changed a Loan Request').toEqual(loanRequestsToStart);
	expect(artifactIds(), 'every artifact section 12 added should be deleted again').toEqual(artifactsToStart);
	// as the Permissions tab shows it. Saving that tab writes an empty filter for a group that has none, so there can be a row for it
	if (groupNamed(AUDITORS) !== null) {
		await asAdmin(page);
		await openAuditorsPermissions(page);
		await expect(page.locator(auditorsFilterBoxes().deleteLinks), `the ${AUDITORS} should have no filter on Artifacts, the way 023 left them`).toHaveCount(0);
	}
	if (weaponsSubformToStart !== null) {
		expect(weaponsSubformSettings(), 'the Artifacts subform in Collections should have the settings setup gave it').toBe(weaponsSubformToStart);
	}
	const everything = namesIn('Artifact');
	await asCurator(page); // undoSections11And12 may have logged in as a webmaster
	await openEntry(page, formId('Locations'), entryId('locations', 'locations_name', 'Main Vault'));
	await expect(page.locator('.formulize-subform')).toBeVisible();
	expect(await namesOnPage(page.locator(howToCheck('S').selector), everything)).toEqual([...everything].sort());
	await leavePage(page);
});

// The only test that fails on a disagreement, after every condition and search has been checked
test('Every condition and search shows what it should', () => {
	expect(disagreements, disagreements.join('\n')).toEqual([]);
});
