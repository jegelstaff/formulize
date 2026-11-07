const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import {
	login, leavePage, saveAdminForm, waitForAdminPageReady, conditionalChecksFinished, elementId, dbQuery, dbPrefix, sqlEscape, ensureMainMenuOpen,
	waitForWorkingMessage, applyColumnChanges, openAllColumnSections,
} from '../../utils';
import {
	casesFor, howToCheck, namesIn, conditionsFor, expectedNames, namesOnPage, searchesFor, searchList, disagreementIn, checkRow,
	ARTIFACT_ELEMENTS, DONOR_ELEMENTS, COLLECTION_ELEMENTS, LOAN_ELEMENTS, OFFER_ELEMENTS,
} from '../condition-cases';

// Conditions and searches as a person meets them. Each row of conditions-and-searches.md is a setting and what should then be on
// the page, so this sets the condition with MCP, or types the search into the list, opens the page as curator1, and compares
// what's there with the row. That file says where to look in each section, and which names can show there.
//
// Conditions take two paths, SQL for finding entries and an evaluation of one entry for displaying elements (see
// ai-context/conditions.md), and sections 1 to 4 and 6 are the first and section 5 is the second. Section 7 is the search boxes
// on a list, which go through formulize_parseFilter(), different code again, so a fix to one doesn't fix the others. Section 8
// makes conditions in the admin UI, the way a webmaster does, and some of its cases are written here rather than in that file.
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
// It runs after 040, which turns the MCP server on, makes an API key and leaves them there. No entry is saved on purpose, but
// changing to a tab of the Artifact screen saves the tab it's leaving. A subform filter of = with a { } reference writes the
// main entry's value into the subform entries on a save, so Keep filtered values in sync is off for section 4, and the Loan
// Requests are compared with what they were at the end. Date elements always submit YYYY-MM-DD, so a save is safe in section 9.
//
// Section 9 changes the site's Short date format in the Settings page, which every date on the site follows until it's put
// back. The spec checks a date on the page before it runs the rows, because a save that can't write its file says nothing.

// A click or key press that submits a page waits for the server to answer, and with no time limit of its own it waits as long
// as the test can run, which is an hour for section 7. With one, a row the server never answers is recorded and the rest run.
test.use({ baseURL: E2E_TEST_BASE_URL, actionTimeout: 30000 });

test.describe.configure({ mode: 'serial' });

const MCP = '/mcp/index.php/mcp';
const SECONDS_PER_ROW = 45; // room for rows that can't be checked, which each wait out the expect timeout

const MAIN_VAULT_SUBFORM = 'locations_subform_artifacts';
const LOANS_SUBFORM = 'artifacts_subform_loan_requests';
const ARTIFACT_OPTIONS = 'loans_artifact';
const DONOR_OPTIONS = 'loans_donor';
const COLLECTION_OPTIONS = 'loans_collections';
const FOLLOW_UP_OPTIONS = 'visits_follow_up';
const DISPLAYED_ELEMENT = 'artifacts_curator_note';
const WEAPONS_SUBFORM = 'collections_subform_artifacts';
const WEAPONS_SUBFORM_DELETE_LINKS = '#filterdiv > a.conditionsdelete';

const sword = 'Chinese Sword';
const chariot = 'Egyptian Chariot';
const musket = 'French Musket';
const WEAPONS = [sword, chariot, musket];

// Conditions typed into the filter boxes of the Artifacts subform in the Collections form, and which of the Weapons collection's
// artifacts it should then show. Setup gave Weapons three:
//   Chinese Sword     donated       CE    acquired 1993-08-10
//   Egyptian Chariot  not donated   BCE   acquired 2011-09-19
//   French Musket     not donated   CE    acquired 1927-03-04
// A term works when it's what people type, and not only when it's the value the element stores: Yes or No for a Yes/No element,
// which stores codes, and a date written out, which is stored as YYYY-MM-DD. The stored values still work too, since conditions
// made before now use them. A condition is [caption, operator, term] as the boxes take them. all is "match all of these", and
// any is "match one or more of these".
const TYPED_IN_THE_ADMIN_UI = [
	{ title: 'with no conditions, the Weapons collection shows all its artifacts', all: [], any: [], shows: [sword, chariot, musket] },
	{ title: 'Donated to museum = Yes shows only the donated artifacts', all: [['Donated to museum', '=', 'Yes']], any: [], shows: [sword] },
	{ title: 'Donated to museum = No shows only the artifacts that were not donated', all: [['Donated to museum', '=', 'No']], any: [], shows: [chariot, musket] },
	{ title: 'Donated to museum LIKE %yes%, with wildcards typed around it, shows only the donated artifacts', all: [['Donated to museum', 'LIKE', '%yes%']], any: [], shows: [sword] },
	{ title: 'Donated to museum = 1, the code for Yes, still shows only the donated artifacts', all: [['Donated to museum', '=', '1']], any: [], shows: [sword] },
	{ title: 'Era IN BCE, a list of one, shows only the BCE artifacts', all: [['Era', 'IN', 'BCE']], any: [], shows: [chariot] },
	{ title: 'Date of acquisition = August 10 1993, written out, shows only the artifact acquired that day', all: [['Date of acquisition', '=', 'August 10 1993']], any: [], shows: [sword] },
	{ title: 'Date of acquisition LIKE 2011, just the year, shows only the artifact acquired that year', all: [['Date of acquisition', 'LIKE', '2011']], any: [], shows: [chariot] },
	{ title: 'Date of acquisition >= 2000, a year with a comparison, is from the start of that year', all: [['Date of acquisition', '>=', '2000']], any: [], shows: [chariot] },
	{ title: 'Donor != Freeform Solutions includes the artifacts with no donor', all: [['Donor', 'NOT', 'Freeform Solutions']], any: [], shows: [chariot, musket] },
	{ title: 'Donor = {BLANK} shows the artifacts with no donor', all: [['Donor', '=', '{BLANK}']], any: [], shows: [chariot, musket] },
	{ title: 'Match all and match one or more together: not donated, and either BCE or acquired in 1993', all: [['Donated to museum', '=', 'No']], any: [['Era', '=', 'BCE'], ['Date of acquisition', 'LIKE', '1993']], shows: [chariot] },
	{ title: 'Match all with a reference to the collection on screen, and match one or more', all: [['Collections', '=', '{collections_name}']], any: [['Era', '=', 'BCE'], ['Donor', '=', 'Freeform Solutions']], shows: [sword, chariot] },
	{ title: 'Match one or more with a negated reference to the collection on screen', all: [], any: [['Collections', 'NOT', '{collections_name}'], ['Era', '=', 'BCE']], shows: [chariot] },
];

// Searches typed into the Date of acquisition box on the Artifacts list, which has the same date handling as conditions. Only the
// artifacts named in shows and hides are looked at. The list doesn't have the column, so it's added for the visit.
const DATE_SEARCHES = [
	{ term: '2011', shows: [chariot], hides: [sword, musket] },
	{ term: 'August 1993', shows: [sword], hides: [chariot, musket] },
	{ term: '>=2020', shows: ['Roman Coin'], hides: [sword, chariot, musket] },
	{ term: 'bread', shows: [], hides: [sword, chariot, musket, 'Roman Coin'] },
];

const LISTS = {
	'Catalogue': { handle: 'artifact_catalogue', names: 'Artifact', elements: ARTIFACT_ELEMENTS },
	'Donor artifacts': { handle: 'donor_artifacts', names: 'Donor', elements: { ...ARTIFACT_ELEMENTS, ...DONOR_ELEMENTS } },
};

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
let keepInSyncToStart;
let shortDateFormatToRestore = null; // set while section 9 has the site showing dates another way
let weaponsSubformToStart = null; // the Weapons subform's settings, once anything an interrupted run left is taken away
let weaponsSubformMayHaveConditions = false; // set while section 8 is typing conditions into it
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
	expect(body.error, `${name} ${JSON.stringify(args)} should succeed`).toBeUndefined();
	return JSON.parse(body.result.content[0].text);
}

const setSubformFilter = (handle, conditions, keepInSync = 1) =>
	tool('update_subform_interface', { element_identifier: handle, properties: { sourceEntryFilter: conditions, keepFilteredValuesInSync: keepInSync } });
const setOptionsFilter = (handle, conditions) =>
	tool('update_linked_list_element', { element_identifier: handle, properties: { sourceEntryFilter: conditions } });
const setDisplayConditions = conditions =>
	tool('update_text_box_element', { element_identifier: DISPLAYED_ELEMENT, display_conditions: conditions });
const setFundamentalFilters = (listHandle, conditions) =>
	tool('update_list_screen', { screen_id: parseInt(screenId(listHandle), 10), fundamental_filters: conditions });

async function putSettingsBack() {
	await setSubformFilter(MAIN_VAULT_SUBFORM, []);
	await setSubformFilter(LOANS_SUBFORM, [], keepInSyncToStart);
	await setOptionsFilter(ARTIFACT_OPTIONS, []);
	await setOptionsFilter(DONOR_OPTIONS, []);
	await setOptionsFilter(COLLECTION_OPTIONS, []);
	await setOptionsFilter(FOLLOW_UP_OPTIONS, []);
	await setDisplayConditions([]);
	for (const list of Object.values(LISTS)) {
		await setFundamentalFilters(list.handle, []);
	}
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

// The setting has to be saved by the Settings page, which is what writes the file the format is read from
async function setShortDateFormat(page, format) {
	const box = page.locator('[data-conf-name="shortdatestring"]').getByRole('textbox');
	const url = '/modules/formulize/admin/ui.php?page=settings&view=system';
	await page.goto(url);
	await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
	const formatBefore = await box.inputValue();
	await box.fill(format);
	await page.getByRole('button', { name: 'Save your changes' }).click();
	await expect(async () => {
		await page.goto(url);
		await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
		await expect(box).toHaveValue(format, { timeout: 5000 });
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

async function openElementSettings(page, handle, tab) {
	const eleId = elementId(handle);
	const fid = dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize WHERE ele_id = ${eleId}`)[0][0];
	const aid = dbQuery(`SELECT appid FROM ${dbPrefix()}_formulize_application_form_link WHERE fid = ${fid}`)[0][0];
	await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=${eleId}&fid=${fid}&aid=${aid}`);
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: tab, exact: true }).click();
}

// Remove every condition in a filter box, the way a webmaster does, with each condition's X, which saves and reloads the page
async function removeConditions(page, handle, tab, deleteLinks) {
	await openElementSettings(page, handle, tab);
	while (await page.locator(deleteLinks).count() > 0) {
		await Promise.all([
			page.waitForNavigation({ waitUntil: 'networkidle', timeout: 120000 }),
			page.locator(deleteLinks).first().click(),
		]);
		await openElementSettings(page, handle, tab);
	}
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

async function openWeaponsSubformOptions(page) {
	await openElementSettings(page, WEAPONS_SUBFORM, 'Options');
	await expect(page.locator('#new_subformfilter_element')).toBeVisible();
}

// Type conditions into the subform's two sets of boxes, as a webmaster does, once the ones there are taken away. Each is saved,
// and must then be in the list of conditions.
async function limitWeaponsSubform(page, all, any) {
	await asAdmin(page);
	await removeConditions(page, WEAPONS_SUBFORM, 'Options', WEAPONS_SUBFORM_DELETE_LINKS);
	await openWeaponsSubformOptions(page);
	for (const [set, conditions] of [['subformfilter', all], ['subformfilter_oom', any]]) {
		for (const [caption, op, term] of conditions) {
			await page.locator(`#new_${set}_element`).selectOption({ label: caption });
			await page.locator(`#new_${set}_op`).selectOption(op);
			await page.locator(`#new_${set}_term`).fill(term);
			await saveAdminForm(page);
			await openWeaponsSubformOptions(page);
			await expect(page.locator('#filterdiv')).toContainText(`${caption} ${op} ${term}`);
		}
	}
}

// Open the Weapons collection as curator1, and which of its artifacts the subform shows. It's left without saving.
async function weaponsShown(page) {
	await asCurator(page);
	await ensureMainMenuOpen(page);
	await page.locator('#mainmenu').getByRole('link', { name: 'Collections', exact: true }).click();
	await page.getByRole('row', { name: 'Weapons' }).getByRole('link').first().click();
	const subform = page.locator('.formulize-subform-table');
	await expect(subform).toBeVisible();
	const shown = [];
	for (const name of WEAPONS) {
		if (await subform.getByText(name, { exact: true }).count() > 0) {
			shown.push(name);
		}
	}
	await leavePage(page); // releases the entry lock
	await expect(subform).not.toBeVisible();
	return shown;
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
		await removeConditions(page, WEAPONS_SUBFORM, 'Options', WEAPONS_SUBFORM_DELETE_LINKS);
		await page.close();
	}
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

// Set a row's display conditions, and open the artifacts it names to see if the element is there
async function checkDisplayRow(page, row, selector) {
	await setDisplayConditions(conditionsFor(row, ARTIFACT_ELEMENTS));
	for (const [column, shouldShow] of [['Shows on', true], ['Hidden on', false]]) {
		if (row[column] === 'Nothing') {
			continue; // the condition holds for every artifact, or for none
		}
		expectedNames(row[column], namesIn('Artifact')); // a name that isn't an artifact is a mistake in the row
		await openArtifactTab(page, row[column]);
		if (await page.locator(selector).isVisible() !== shouldShow) {
			disagreements.push(`${row['Row']}: Curator note should ${shouldShow ? 'show' : 'be hidden'} on ${row[column]}`);
		}
	}
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
			await page.goto(`/modules/formulize/index.php?sid=${screenId(list.handle)}`);
			await expect(page.getByText(/Showing entries:|No entries were found/).first()).toBeVisible();
			compare(row, expectedNames(row['Should show'], everything), await namesOnPage(page.locator(selector), everything));
		});
	}
	for (const list of Object.values(LISTS)) {
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
	const listUrls = {};
	for (const [name, list] of Object.entries(LISTS)) {
		listUrls[name] = `/modules/formulize/index.php?sid=${screenId(list.handle)}`;
	}

	await login(page, 'curator1', '12345');
	for (const row of rows) {
		await checkRow(page, row['Row'], disagreements, async () => {
			const list = LISTS[row['List']];
			const everything = namesIn(list.names);
			await searchList(page, listUrls[row['List']], searchesFor(row, list.elements));
			compare(row, expectedNames(row['Should show'], everything), await namesOnPage(page.locator(selector), everything));
		});
	}
	await leavePage(page);
});

test('Section 7: date searches on the Artifacts list', async ({ page }) => {
	test.setTimeout(300000);
	const box = page.locator('input[name="search_artifacts_date_of_acquisition"]');

	await login(page, 'curator1', '12345');
	for (const search of DATE_SEARCHES) {
		const rowId = `Artifacts list, Date of acquisition: \`${search.term}\``;
		await checkRow(page, rowId, disagreements, async () => {
			if (!(await box.isVisible())) {
				await openArtifactsListWithDates(page); // the first time, and again if a search before this one had to leave the list
			}
			await box.fill(search.term);
			await box.press('Enter');
			await waitForWorkingMessage(page);
			await expect(page.getByText(/Showing entries:|No entries were found/).first()).toBeVisible();
			await expect(box).toHaveValue(search.term);
			const shown = [];
			for (const name of [...search.shows, ...search.hides]) {
				if (await page.getByRole('cell', { name, exact: true }).count() > 0) {
					shown.push(name);
				}
			}
			const disagreement = disagreementIn(rowId, search.shows, shown);
			if (disagreement) {
				disagreements.push(disagreement);
			}
		});
	}
	await leavePage(page);
});

test('Section 8: a disabled condition, made in the admin UI', async ({ page }) => {
	test.setTimeout(300000);
	const endDateCanBeEdited = async borrower => {
		await openEntry(page, formId('Loan Requests'), entryId('loan_requests', 'loans_borrower', borrower));
		await expect(page.locator('.form-row:has(.formulize-label-loans_borrower)')).toBeVisible();
		return page.locator('.form-row:has(.formulize-label-loans_end_date) input:not([type="hidden"]):not([disabled])').first().isVisible();
	};

	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openElementSettings(page, 'loans_end_date', 'Display Settings');
	await page.locator('select[name="elements_ele_disabled\\[\\]"]').selectOption('all');
	await page.locator('#new_disabledconditions_element').selectOption('Approved');
	await page.locator('#new_disabledconditions_op').selectOption('NOT');
	await page.locator('#new_disabledconditions_term').fill('Yes');
	await saveAdminForm(page);
	await openElementSettings(page, 'loans_end_date', 'Display Settings');
	await expect(page.locator('#disabledconditions')).toContainText('Approved');

	await page.goto('/user.php?op=logout');
	await login(page, 'curator1', '12345');
	for (const row of casesFor('A').filter(row => !['A4', 'A5'].includes(row['Row']))) { // A4 and A5 are the dynamic default, below
		await checkRow(page, row['Row'], disagreements, async () => {
			const shouldBeEditable = row['Should see'] === 'End date can be edited';
			if (await endDateCanBeEdited(row['Open']) !== shouldBeEditable) {
				disagreements.push(`${row['Row']}: ${row['Should see']}, on ${row['Open']}`);
			}
		});
	}
	await leavePage(page, '/user.php?op=logout');

	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await removeConditions(page, 'loans_end_date', 'Display Settings', '#disabledconditions > a.conditionsdelete');
	await page.locator('select[name="elements_ele_disabled\\[\\]"]').selectOption('none');
	await saveAdminForm(page);
	await openElementSettings(page, 'loans_end_date', 'Display Settings');
	await expect(page.locator('#disabledconditions > a.conditionsdelete')).toHaveCount(0);
	await expect(page.locator('select[name="elements_ele_disabled\\[\\]"] option:checked')).toHaveText('Disable for no groups');
});

test('Section 8: a dynamic default, made in the admin UI', async ({ page }) => {
	test.setTimeout(300000);
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openElementSettings(page, 'loans_donor', 'Advanced');
	await page.locator('select[name="elements-ele_dynamicdefault_source"]').selectOption(String(elementId('artifacts_donor')));
	await page.locator('#elements-ele_use_default_when_blank1').check();
	await saveAdminForm(page);
	await openElementSettings(page, 'loans_donor', 'Advanced'); // the conditions are only on offer once there's a source
	await page.locator('#new_dynamicDefaultConditions_element').selectOption('Short name');
	await page.locator('#new_dynamicDefaultConditions_term').fill('{loans_artifact}');
	await saveAdminForm(page);
	await openElementSettings(page, 'loans_donor', 'Advanced');
	await expect(page.locator('#dynamic-defaults')).toContainText('{loans_artifact}');

	// looked at and not saved, so University Library still has no donor contact
	await page.goto('/user.php?op=logout');
	await login(page, 'curator1', '12345');
	const [row] = casesFor('A').filter(row => row['Row'] === 'A4');
	await checkRow(page, row['Row'], disagreements, async () => {
		await openEntry(page, formId('Loan Requests'), entryId('loan_requests', 'loans_borrower', row['Open']));
		const shown = await page.locator('.form-row:has(.formulize-label-loans_donor) select option:checked').allTextContents();
		if (!shown.map(text => text.trim()).includes('Emilie Du Châtelet')) {
			disagreements.push(`${row['Row']}: Donor contact should show Emilie Du Châtelet, but shows ${shown.join(', ')}`);
		}
	});
	// a new Loan Request has no artifact chosen yet, so the condition finds nothing, and there's no donor to default to
	const [newEntryRow] = casesFor('A').filter(row => row['Row'] === 'A5');
	await checkRow(page, newEntryRow['Row'], disagreements, async () => {
		await openNewLoanRequest(page);
		const donorsShownNew = await namesOnPage(page.locator('.form-row:has(.formulize-label-loans_donor) select option:checked'), namesIn('Donor'));
		if (donorsShownNew.length > 0) {
			disagreements.push(`${newEntryRow['Row']}: Donor contact should show nothing, but shows ${donorsShownNew.join(', ')}`);
		}
	});
	await leavePage(page, '/user.php?op=logout');

	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await removeConditions(page, 'loans_donor', 'Advanced', 'div#dynamic-defaults a.conditionsdelete');
	await page.locator('select[name="elements-ele_dynamicdefault_source"]').selectOption({ index: 0 });
	await page.locator('#elements-ele_use_default_when_blank0').check();
	await saveAdminForm(page);
	await openElementSettings(page, 'loans_donor', 'Advanced');
	await expect(page.locator('select[name="elements-ele_dynamicdefault_source"]')).toHaveJSProperty('selectedIndex', 0);
});

// The conditions are typed into the boxes as a webmaster types them, and removed with each one's X, so the subform's settings end
// up exactly as setup left them. Weapons is only looked at, never saved.
test('Section 8: conditions typed in the admin UI, on the Artifacts subform of a collection', async ({ page }) => {
	test.setTimeout(TYPED_IN_THE_ADMIN_UI.length * 300000);
	weaponsSubformMayHaveConditions = true;
	await asAdmin(page);
	await removeConditions(page, WEAPONS_SUBFORM, 'Options', WEAPONS_SUBFORM_DELETE_LINKS); // anything an interrupted run left
	weaponsSubformToStart = weaponsSubformSettings();

	for (const typed of TYPED_IN_THE_ADMIN_UI) {
		const rowId = `Weapons collection: ${typed.title}`;
		await checkRow(page, rowId, disagreements, async () => {
			await limitWeaponsSubform(page, typed.all, typed.any);
			const disagreement = disagreementIn(rowId, typed.shows, await weaponsShown(page));
			if (disagreement) {
				disagreements.push(disagreement);
			}
		});
	}

	await asAdmin(page);
	await removeConditions(page, WEAPONS_SUBFORM, 'Options', WEAPONS_SUBFORM_DELETE_LINKS);
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
			await searchList(page, catalogue, searchesFor(row, ARTIFACT_ELEMENTS));
			compare(row, expectedNames(row['Should show'], everything), await namesOnPage(page.locator(searchSelector), everything));
		});
	}

	await page.goto('/user.php?op=logout');
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await setShortDateFormat(page, shortDateFormatToRestore);
	shortDateFormatToRestore = null;
	await expectCatalogueToShow(page, '2002-12-31');
});

test('Everything is put back', async ({ page }) => {
	await putSettingsBack();
	expect(loanRequests(), 'nothing here should have changed a Loan Request').toEqual(loanRequestsToStart);
	if (weaponsSubformToStart !== null) {
		expect(weaponsSubformSettings(), 'the Artifacts subform in Collections should have the settings setup gave it').toBe(weaponsSubformToStart);
	}
	const everything = namesIn('Artifact');
	await login(page, 'curator1', '12345');
	await openEntry(page, formId('Locations'), entryId('locations', 'locations_name', 'Main Vault'));
	await expect(page.locator('.formulize-subform')).toBeVisible();
	expect(await namesOnPage(page.locator(howToCheck('S').selector), everything)).toEqual([...everything].sort());
	await leavePage(page);
});

// The only test that fails on a disagreement, after every condition and search has been checked
test('Every condition and search shows what it should', () => {
	expect(disagreements, disagreements.join('\n')).toEqual([]);
});
