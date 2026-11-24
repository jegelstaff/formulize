const { test, expect } = require('@playwright/test');
import { saveDrawerForm, waitForDrawerFormToken } from '../../utils';
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, leavePage, conditionalElementReady, conditionalChecksFinished, waitForJQueryAnimations, waitForAdminPageReady, saveFormulizeForm, saveAdminForm, openElementSettings, dbQuery, dbPrefix, sqlEscape, deleteMuseumFormsTitled } from '../../utils';

// A sub entry opened in the drawer, over a form screen. The drawer's form runs its scripts in the same page as the form
// behind it, and each has conditional elements and a rich text field.
//
//  - A conditional radio button in the drawer shows the option that was saved. conditional.js copies each conditional
//    element's markup into a hidden div to compare it later. The drawer's endpoint puts everything the form prints inside
//    its <form>, so when that div was printed with the rest, a copied radio button joined the real one's group, took the
//    checked state from it, and was then thrown away, leaving nothing checked.
//  - The form behind keeps its conditional elements working after the drawer closes. The drawer runs conditional.js
//    again, and it used to start by emptying its lists of which elements govern which, so the form behind lost its own.
//  - The form behind keeps its rich text when it's saved after the drawer closes. A rich text field is saved from a
//    hidden field that updateCKEditors() fills from the editor. The drawer replaced that function with one that knew only
//    its own editors, so the form behind saved its rich text fields empty.
//  - A value that has to be unique is checked in the drawer, and checked again whenever it changes. The check asks the
//    server, and its answer tries the save again. The drawer's validation used to throw on the check's code, which was
//    written for full screen's, so the save did nothing. And the drawer saves without reloading, so an answer for a
//    value checked before one save must not count for a value typed after it.
//  - A date field's earliest and latest dates are kept in a drawer opened from a list. The field calls a function that
//    only full screen forms used to define, so opened from a list, every change to the date threw, and any date was
//    accepted.
//  - The form behind keeps its own screen and relationship after the drawer closes, for the conditional elements it
//    fetches. The drawer replaced them with its own, so checkboxes were drawn with none of the screen's settings, and
//    the elements of a form whose relationship wasn't the drawer's were fetched with the wrong one.
//  - The drawer makes the checks full screen makes before it submits: it waits for conditional elements that are still
//    on their way, asks about letters in a number box, and runs a form's extra validation function. It used to make none
//    of them. Deleting a sub entry made no checks at all, and saved the form's rich text fields empty.
//
// The forms are made here with MCP, in the Museum application, and deleted at the end, so nothing the other specs use
// changes. MCP is part of the application, and 040 turns it on and makes an API key, which this reads from the admin UI.
// The entries are made with MCP. The tests run in order, and some rely on what an earlier one saved.

test.describe.configure({ mode: 'serial' });

const MCP = '/mcp/index.php/mcp';

// Every form this file makes has a title starting with this, so forms left by an interrupted run can be found. A form
// that links to another is listed before the form it links to, so they can be deleted in this order.
const CASES_FORM = 'Drawer Conditions Test Display Cases';
const EXHIBITIONS_FORM = 'Drawer Conditions Test Exhibitions';

// An exhibition that isn't travelling, so its Venue is hidden, with one display case that is spotlit, in a cool light.
// Spotlight colour is the conditional radio button the drawer has to show as it was saved.
const EXHIBITION = 'Northern Lights';
const DISPLAY_CASE = 'Case One';
const SAVED_SPOTLIGHT = 'Cool';
// what the exhibition's rich text Description says, which a save of the exhibition has to keep
const DESCRIPTION = 'Photographs of the aurora borealis';
// Each display case has a catalogue number, and no two can have the same one. Case Two is there to have one to clash with.
const CASE_NUMBER = 'C-001';
const OTHER_CASE = 'Case Two';
const OTHER_CASE_NUMBER = 'C-002';
const NEW_CASE_NUMBER = 'C-003';
// a display case can be installed in 2026, and no other year
const EARLIEST_INSTALLATION = '2026-01-01';
const LATEST_INSTALLATION = '2026-12-31';
const INSTALLED_ON = '2026-06-15';
// what the display cases' extra validation function refuses to have in a field, and what it says
const REFUSED_NAME = 'Broken case';
const REFUSAL = 'A broken case cannot be shown';

let api;
let apiKey;
let requestId = 0;
let exhibitionsFid;
let casesFid;
let exhibitionEntryId;
let caseEntryId;
let otherCaseEntryId;

async function tool(name, args) {
	const response = await api.post(MCP, {
		headers: { 'Authorization': `Bearer ${apiKey}` },
		data: { jsonrpc: '2.0', id: ++requestId, method: 'tools/call', params: { name, arguments: args } },
	});
	const body = await response.json();
	expect(body.error, `${name} ${JSON.stringify(args)} should succeed, and MCP said: ${body.error?.message ?? ''}`).toBeUndefined();
	return JSON.parse(body.result.content[0].text);
}

function dataTable(fid) {
	const [[formHandle]] = dbQuery(`SELECT form_handle FROM ${dbPrefix()}_formulize_id WHERE id_form = ${fid}`);
	return `${dbPrefix()}_formulize_${formHandle}`;
}

function entryIdWhere(fid, handle, value) {
	return dbQuery(`SELECT entry_id FROM ${dataTable(fid)} WHERE ${handle} = '${sqlEscape(value)}'`)[0][0];
}

function storedValue(fid, entryId, handle) {
	return dbQuery(`SELECT ${handle} FROM ${dataTable(fid)} WHERE entry_id = ${entryId}`)[0][0];
}

// the screen a form opens in, and that screen's relationship
function formScreen(fid) {
	const [[sid, frid]] = dbQuery(`SELECT s.sid, s.frid FROM ${dbPrefix()}_formulize_screen AS s INNER JOIN ${dbPrefix()}_formulize_id AS f ON f.defaultform = s.sid WHERE f.id_form = ${fid}`);
	return { sid, frid };
}

// the screen a form's entries open in when they're sub entries of another form, which is made with the subform, and its relationship
function subformScreen(fid, mainFid) {
	const screens = dbQuery(`SELECT sid, frid FROM ${dbPrefix()}_formulize_screen WHERE fid = ${fid} AND screen_handle = 'subform_for_form_${mainFid}'`);
	expect(screens.length, 'the subform should have made one screen for its entries').toBe(1);
	return { sid: screens[0][0], frid: screens[0][1] };
}

// Do something that makes a form fetch its conditional elements, and return the screen and relationship it fetched them with
async function fetchedWith(page, fid, action) {
	const parameters = request => new URL(request.url()).searchParams;
	const [request] = await Promise.all([
		page.waitForRequest(request => parameters(request).get('op') === 'get_element_row_html' && parameters(request).get('fid') === String(fid)),
		action(),
	]);
	return { sid: parameters(request).get('sid'), frid: parameters(request).get('frid') };
}

async function deleteTheForms(browser) {
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	try {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await deleteMuseumFormsTitled(page, [CASES_FORM, EXHIBITIONS_FORM]);
	} finally {
		await page.close();
	}
}

const drawer = page => page.locator('.formulize-drawer[role="dialog"]');
const radioIn = (container, handle, option) =>
	container.locator(`.form-row:has(.formulize-label-${handle})`).getByRole('radio', { name: option, exact: true });

// Open the exhibition full screen, and its display case in the drawer. The drawer's footer controls are put in after the
// fragment's scripts have run, and the form then fades in, so once both have happened the conditional elements' code
// has done what it does when a form loads.
async function openDisplayCaseInDrawer(page, entryId = caseEntryId, handleItShows = 'dct_case_spotlight') {
	await page.goto(`/modules/formulize/index.php?fid=${exhibitionsFid}&ve=${exhibitionEntryId}`);
	await page.waitForLoadState('load');
	await waitForJQueryAnimations(page);
	await page.locator(`a#view${entryId}`).click();
	await expect(drawer(page)).toBeVisible();
	await expect(drawer(page).locator('.formulize-drawer__foot input[type="button"]').first()).toBeVisible();
	await expect(drawer(page).locator('.formulize-drawer__body').first()).not.toHaveClass(/formulize-drawer__body--entering/);
	await expect(drawer(page).locator(`.formulize-label-${handleItShows}`)).toBeVisible();
}

// Close the drawer with its X. A change in the drawer asks whether to discard it first.
async function closeDrawer(page) {
	page.once('dialog', dialog => dialog.accept());
	await drawer(page).locator('.formulize-drawer__close').click();
	await expect(drawer(page)).toBeHidden();
}

// Leave the exhibition, so its lock is released, for the list of exhibitions. A list drawn on the server shows no
// working message, so wait for its footer.
async function leaveForExhibitionsList(page) {
	await leavePage(page, `/modules/formulize/master.php?fid=${exhibitionsFid}`);
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible({ timeout: 30000 });
}

test.beforeAll(async ({ browser, playwright }) => {
	api = await playwright.request.newContext({ baseURL: E2E_TEST_BASE_URL });
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	// masked on screen, and only available in the data-key attribute of its copy icon
	await page.goto('/modules/formulize/admin/ui.php?page=users&view=apikeys');
	await page.locator('.admin-ui').waitFor({ state: 'visible' });
	apiKey = await page.locator('td[id=key-1] .key-copy').getAttribute('data-key');
	expect(apiKey, '040 should have left an API key').toBeTruthy();
	await page.close();
	await deleteTheForms(browser); // anything an interrupted run left
});

test.afterAll(async ({ browser }) => {
	await deleteTheForms(browser);
	const left = dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title IN ('${sqlEscape(CASES_FORM)}', '${sqlEscape(EXHIBITIONS_FORM)}')`);
	expect(left, 'the forms this file made should be gone').toEqual([]);
	// the forms were put in Museum by name, which should find it, not make another
	expect(dbQuery(`SELECT appid FROM ${dbPrefix()}_formulize_applications WHERE name = 'Museum'`).length, 'there should still be one Museum application').toBe(1);
	await api?.dispose();
});

test('Make an exhibition with a display case, each with a conditional element and a rich text field', async () => {
	exhibitionsFid = parseInt((await tool('create_form', { title: EXHIBITIONS_FORM, application_id_or_name: 'Museum' })).form_id, 10);
	await tool('create_text_box_element', { form_id: exhibitionsFid, type: 'text', caption: 'Name', handle: 'dct_exhibition_name', properties: {}, principal_identifier: true });
	await tool('create_list_element', { form_id: exhibitionsFid, type: 'radio', caption: 'Travelling', handle: 'dct_exhibition_travelling', properties: { options: ['Yes', 'No'] } });
	await tool('create_text_box_element', {
		form_id: exhibitionsFid, type: 'text', caption: 'Venue', handle: 'dct_exhibition_venue', properties: {},
		display_conditions: [{ element: 'dct_exhibition_travelling', operator: '=', value: 'Yes', type: 'match-all' }],
	});
	await tool('create_text_box_element', { form_id: exhibitionsFid, type: 'textarea', caption: 'Description', handle: 'dct_exhibition_description', properties: { useRichTextEditor: 1 } });
	// checkboxes can hold several values, and nothing else depends on the audio guide, so they're fetched by themselves
	await tool('create_list_element', { form_id: exhibitionsFid, type: 'radio', caption: 'Audio guide', handle: 'dct_exhibition_audio', properties: { options: ['Yes', 'No'] } });
	await tool('create_list_element', {
		form_id: exhibitionsFid, type: 'checkbox', caption: 'Audio guide languages', handle: 'dct_exhibition_languages', properties: { options: ['English', 'French'] },
		display_conditions: [{ element: 'dct_exhibition_audio', operator: '=', value: 'Yes', type: 'match-all' }],
	});

	casesFid = parseInt((await tool('create_form', { title: CASES_FORM, application_id_or_name: 'Museum' })).form_id, 10);
	const caseName = await tool('create_text_box_element', { form_id: casesFid, type: 'text', caption: 'Name', handle: 'dct_case_name', properties: {}, principal_identifier: true });
	await tool('create_list_element', { form_id: casesFid, type: 'radio', caption: 'Lighting', handle: 'dct_case_lighting', properties: { options: ['Natural', 'Spotlit'] } });
	await tool('create_list_element', {
		form_id: casesFid, type: 'radio', caption: 'Spotlight colour', handle: 'dct_case_spotlight', properties: { options: ['Warm', 'Cool'] },
		display_conditions: [{ element: 'dct_case_lighting', operator: '=', value: 'Spotlit', type: 'match-all' }],
	});
	await tool('create_text_box_element', { form_id: casesFid, type: 'textarea', caption: 'Notes', handle: 'dct_case_notes', properties: { useRichTextEditor: 1 } });
	await tool('create_text_box_element', { form_id: casesFid, type: 'text', caption: 'Catalogue number', handle: 'dct_case_catalogue', properties: {} });
	await tool('create_text_box_element', { form_id: casesFid, type: 'number', caption: 'Shelves', handle: 'dct_case_shelves', properties: {} });
	// a script in a form's static content can give the form an extra validation function. Static content puts values in
	// place of curly brackets, so this does without them.
	await tool('create_static_content_element', {
		form_id: casesFid, type: 'fullWidthContent', caption: 'Case rules',
		properties: { content: `<script>window.formulizeExtraFormValidation = (valid, form) => valid && (Array.from(form.elements).every(field => field.value !== '${REFUSED_NAME}') || Boolean(alert('${REFUSAL}')));</script>` },
	});

	// The subform makes a linked element in the display cases form, pointing at the exhibition's Name
	await tool('create_subform_interface', {
		form_id: exhibitionsFid, type: 'subformListings', caption: 'Display cases', handle: 'dct_exhibition_cases',
		properties: { sourceForm: casesFid, elementsInRow: [parseInt(caseName.element_id, 10)], entryViewingMode: 'drawer' },
	});
	const linked = dbQuery(`SELECT ele_handle FROM ${dbPrefix()}_formulize WHERE id_form = ${casesFid} AND ele_type = 'selectLinked'`);
	expect(linked.length, 'the subform should have made one linked element in the display cases form').toBe(1);

	await tool('create_entries', { form_id: exhibitionsFid, data: [{ dct_exhibition_name: EXHIBITION, dct_exhibition_travelling: 'No', dct_exhibition_description: DESCRIPTION }] });
	await tool('create_entries', {
		form_id: casesFid,
		data: [
			{ dct_case_name: DISPLAY_CASE, dct_case_lighting: 'Spotlit', dct_case_spotlight: SAVED_SPOTLIGHT, dct_case_catalogue: CASE_NUMBER, [linked[0][0]]: EXHIBITION },
			{ dct_case_name: OTHER_CASE, dct_case_lighting: 'Natural', dct_case_catalogue: OTHER_CASE_NUMBER, [linked[0][0]]: EXHIBITION },
		],
	});
	exhibitionEntryId = entryIdWhere(exhibitionsFid, 'dct_exhibition_name', EXHIBITION);
	caseEntryId = entryIdWhere(casesFid, 'dct_case_name', DISPLAY_CASE);
	otherCaseEntryId = entryIdWhere(casesFid, 'dct_case_name', OTHER_CASE);
	// the values the drawer and the exhibition have to show, and keep, are the ones that were stored
	expect(storedValue(casesFid, caseEntryId, 'dct_case_spotlight')).toBe(SAVED_SPOTLIGHT);
	expect(storedValue(exhibitionsFid, exhibitionEntryId, 'dct_exhibition_description')).toContain(DESCRIPTION);
});

test('A conditional radio button in the drawer shows the option that was saved', async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openDisplayCaseInDrawer(page);
	await expect(radioIn(drawer(page), 'dct_case_lighting', 'Spotlit')).toBeChecked();
	await expect(radioIn(drawer(page), 'dct_case_spotlight', SAVED_SPOTLIGHT)).toBeChecked();

	// and the drawer's own conditions still work
	await radioIn(drawer(page), 'dct_case_lighting', 'Natural').check();
	await expect(drawer(page).locator('.formulize-label-dct_case_spotlight')).not.toBeVisible();

	await closeDrawer(page);
	await leaveForExhibitionsList(page);
});

test('The form behind the drawer keeps its conditional elements working after the drawer closes', async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openDisplayCaseInDrawer(page);
	await closeDrawer(page);

	const exhibition = page.locator('#formulizeform');
	await expect(exhibition.locator('.formulize-label-dct_exhibition_venue')).not.toBeVisible();
	await conditionalElementReady(page, 'dct_exhibition_venue', () => radioIn(exhibition, 'dct_exhibition_travelling', 'Yes').check());
	await expect(exhibition.locator('.formulize-label-dct_exhibition_venue')).toBeVisible();

	await leaveForExhibitionsList(page);
});

test('The form behind the drawer keeps its rich text when it is saved after the drawer closes', async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openDisplayCaseInDrawer(page);
	// the display case's Notes is a rich text field too, so the drawer has set up editors of its own
	await expect(drawer(page).locator('.ck-editor__editable')).toBeVisible();
	await closeDrawer(page);

	const exhibition = page.locator('#formulizeform');
	await expect(exhibition.locator('.ck-editor__editable')).toContainText(DESCRIPTION);
	// a change somewhere else in the form, so there is something to save. The Description isn't touched.
	await conditionalElementReady(page, 'dct_exhibition_venue', () => radioIn(exhibition, 'dct_exhibition_travelling', 'Yes').check());
	await saveFormulizeForm(page, 'Save');

	expect(storedValue(exhibitionsFid, exhibitionEntryId, 'dct_exhibition_travelling'), 'the save should have happened').toBe('Yes');
	expect(storedValue(exhibitionsFid, exhibitionEntryId, 'dct_exhibition_description'), 'the Description should be kept').toContain(DESCRIPTION);

	await leaveForExhibitionsList(page);
});

test('A value that has to be unique is checked in the drawer, each time it changes', async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	// a text box's values are made unique on its Options tab, which MCP has no setting for
	await openElementSettings(page, 'dct_case_catalogue', 'Options');
	await page.locator('input[name="elements-ele_value[9]"]').check();
	await saveAdminForm(page);
	expect(dbQuery(`SELECT ele_value FROM ${dbPrefix()}_formulize WHERE ele_handle = 'dct_case_catalogue'`)[0][0], 'the catalogue number should have to be unique').toMatch(/i:9;(?:i:1|s:1:"1");/);

	await openDisplayCaseInDrawer(page);
	const catalogue = drawer(page).getByRole('textbox', { name: 'Catalogue number' });
	const save = drawer(page).locator('.formulize-drawer__foot input[name="save"]');
	// the server's answer comes after the click, and tries the save again, which says the number is in use
	const saveAndExpectRefusal = async () => {
		const refusal = page.waitForEvent('dialog');
		await save.click();
		const dialog = await refusal;
		expect(dialog.message()).toContain('has been entered already');
		await dialog.accept();
	};
	// a text box marks the form changed when it loses focus
	const typeNumber = async number => {
		await catalogue.fill(number);
		await catalogue.press('Tab');
	};

	// Case Two's number is refused
	await typeNumber(OTHER_CASE_NUMBER);
	await saveAndExpectRefusal();
	expect(storedValue(casesFid, caseEntryId, 'dct_case_catalogue')).toBe(CASE_NUMBER);

	// a number no case has is saved
	await typeNumber(NEW_CASE_NUMBER);
	await saveDrawerForm(page);
	expect(storedValue(casesFid, caseEntryId, 'dct_case_catalogue'), 'the new number should be saved').toBe(NEW_CASE_NUMBER);
	await expect(catalogue).toHaveValue(NEW_CASE_NUMBER);

	// the drawer has saved without reloading the page, and Case Two's number is still refused after that
	await typeNumber(OTHER_CASE_NUMBER);
	await saveAndExpectRefusal();
	expect(storedValue(casesFid, caseEntryId, 'dct_case_catalogue')).toBe(NEW_CASE_NUMBER);

	await closeDrawer(page);
	await leaveForExhibitionsList(page);
});

test('A date field keeps to its earliest and latest dates in a drawer opened from a list', async ({ page }) => {
	await tool('create_selector_element', {
		form_id: casesFid, type: 'date', caption: 'Installed on', handle: 'dct_case_installed',
		properties: { minDate: EARLIEST_INSTALLATION, maxDate: LATEST_INSTALLATION },
	});
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	const pageErrors = [];
	page.on('pageerror', error => pageErrors.push(error.message));

	// the list of display cases opens its entries in the drawer, which is set on the list screen's Interface tab
	const [[listSid]] = dbQuery(`SELECT defaultlist FROM ${dbPrefix()}_formulize_id WHERE id_form = ${casesFid}`);
	await page.goto(`/modules/formulize/admin/ui.php?page=screen&fid=${casesFid}&sid=${listSid}&tab=interface`);
	await waitForAdminPageReady(page);
	await page.locator('input[name="screens-editdestination"][value="drawer"]').check();
	await saveAdminForm(page);
	expect(dbQuery(`SELECT editdestination FROM ${dbPrefix()}_formulize_screen_listofentries WHERE sid = ${listSid}`)[0][0], 'the list should open entries in the drawer').toBe('drawer');

	// a list drawn on the server shows no working message, so wait for its footer
	await page.goto(`/modules/formulize/index.php?sid=${listSid}`);
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible({ timeout: 30000 });
	await page.getByRole('row', { name: new RegExp(DISPLAY_CASE) }).locator('a[onclick^="javascript:goDetails"]').click();
	await expect(drawer(page)).toBeVisible();
	await expect(drawer(page).locator('.formulize-drawer__foot input[type="button"]').first()).toBeVisible();
	const installedOn = drawer(page).locator('input[type="date"]');
	await expect(installedOn).toBeVisible();
	await expect(installedOn).toHaveAttribute('min', EARLIEST_INSTALLATION);
	await expect(installedOn).toHaveAttribute('max', LATEST_INSTALLATION);

	// The field checks the date as soon as it changes, and says why it has emptied it. The message comes while the
	// change is still happening, so it's answered as it comes.
	const chooseAndExpectRefusal = async (date, reason) => {
		let message = '';
		await Promise.all([
			page.waitForEvent('dialog').then(dialog => { message = dialog.message(); return dialog.accept(); }),
			installedOn.fill(date),
		]);
		expect(message).toContain(reason);
		await expect(installedOn).toHaveValue('');
	};

	await chooseAndExpectRefusal('2025-12-31', 'too far in the past');
	await chooseAndExpectRefusal('2027-01-01', 'too far in the future');

	// a date in 2026 is kept, and saved
	await installedOn.fill(INSTALLED_ON);
	await expect(installedOn).toHaveValue(INSTALLED_ON);
	await saveDrawerForm(page);
	expect(storedValue(casesFid, caseEntryId, 'dct_case_installed'), 'the date should be saved').toBe(INSTALLED_ON);
	// the field in the form drawn again after the save still keeps to the limits
	await expect(installedOn).toHaveValue(INSTALLED_ON);
	await chooseAndExpectRefusal('2027-01-01', 'too far in the future');

	expect(pageErrors, 'nothing in the page should have thrown').toEqual([]);

	await closeDrawer(page);
	await leavePage(page, `/modules/formulize/index.php?sid=${listSid}`);
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible({ timeout: 30000 });
});

test('The form behind the drawer keeps its own screen and relationship after the drawer closes', async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);

	// The screen the display cases open in from the subform shows the form alone, so its relationship isn't the exhibitions' one
	const caseScreen = subformScreen(casesFid, exhibitionsFid);
	await page.goto(`/modules/formulize/admin/ui.php?page=screen&fid=${casesFid}&sid=${caseScreen.sid}&tab=relationships`);
	await waitForAdminPageReady(page);
	await page.locator('#screens-frid').selectOption('0');
	await saveAdminForm(page);
	caseScreen.frid = subformScreen(casesFid, exhibitionsFid).frid;
	expect(caseScreen.frid, 'the display cases should have no relationship').toBe('0');
	const exhibitionScreen = formScreen(exhibitionsFid);
	expect(exhibitionScreen.frid, 'the exhibitions should have one').not.toBe('0');

	// in the drawer, the display case's conditional elements are fetched with its own
	await openDisplayCaseInDrawer(page);
	expect(await fetchedWith(page, casesFid, () => radioIn(drawer(page), 'dct_case_lighting', 'Natural').check())).toEqual(caseScreen);
	await expect(drawer(page).locator('.formulize-label-dct_case_spotlight')).not.toBeVisible();
	await closeDrawer(page);

	// and behind it, the exhibition's are fetched with the exhibition's
	const exhibition = page.locator('#formulizeform');
	expect(await fetchedWith(page, exhibitionsFid, () =>
		conditionalElementReady(page, 'dct_exhibition_languages', () => radioIn(exhibition, 'dct_exhibition_audio', 'Yes').check())
	)).toEqual(exhibitionScreen);

	// The exhibition's screen has two columns, so the checkboxes are in the second one, the same as the Name is. Without
	// the screen they would be drawn in one column.
	const secondColumn = handle => exhibition.locator(`.form-row:has(.formulize-label-${handle}) .col2`);
	await expect(secondColumn('dct_exhibition_name').getByRole('textbox')).toBeVisible();
	await expect(secondColumn('dct_exhibition_languages').getByRole('checkbox', { name: 'French', exact: true })).toBeVisible();

	await leaveForExhibitionsList(page);
});

// Do something that makes the drawer or the page ask or say something, and return what it was. It's answered as it comes.
async function dialogFrom(page, action, answer = 'accept') {
	let message = '';
	await Promise.all([
		page.waitForEvent('dialog').then(dialog => { message = dialog.message(); return dialog[answer](); }),
		action(),
	]);
	return message;
}

const drawerSave = page => drawer(page).locator('.formulize-drawer__foot input[name="save"]');

test('The drawer waits for conditional elements before it saves', async ({ page }) => {
	// The Spotlight colour has to be given when it shows. It's made required now, after Case Two exists, because MCP asks for
	// every required element when it makes an entry, even one whose condition hides it, as Case Two's does.
	await tool('update_list_element', { element_identifier: 'dct_case_spotlight', required: true });
	expect(dbQuery(`SELECT ele_required FROM ${dbPrefix()}_formulize WHERE ele_handle = 'dct_case_spotlight'`)[0][0], 'the Spotlight colour should be required').toBe('1');
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openDisplayCaseInDrawer(page, otherCaseEntryId, 'dct_case_lighting');

	// Spotlit brings in the Spotlight colour, which is required. Saving straight away has to wait for it, and then say so.
	const refusal = await dialogFrom(page, async () => {
		await radioIn(drawer(page), 'dct_case_lighting', 'Spotlit').check();
		await drawerSave(page).click();
	});
	expect(refusal).toContain('Spotlight colour');
	expect(storedValue(casesFid, otherCaseEntryId, 'dct_case_lighting'), 'nothing should be saved yet').toBe('Natural');

	await conditionalChecksFinished(page);
	await radioIn(drawer(page), 'dct_case_spotlight', 'Warm').check();
	await saveDrawerForm(page);
	expect(storedValue(casesFid, otherCaseEntryId, 'dct_case_spotlight'), 'the colour should be saved').toBe('Warm');
	expect(storedValue(casesFid, otherCaseEntryId, 'dct_case_lighting')).toBe('Spotlit');

	await closeDrawer(page);
	await leaveForExhibitionsList(page);
});

test('Deleting a sub entry in the drawer keeps the rich text of the form it is in', async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	// the list of exhibitions opens its entries in the drawer, which is set on the list screen's Interface tab
	const [[listSid]] = dbQuery(`SELECT defaultlist FROM ${dbPrefix()}_formulize_id WHERE id_form = ${exhibitionsFid}`);
	await page.goto(`/modules/formulize/admin/ui.php?page=screen&fid=${exhibitionsFid}&sid=${listSid}&tab=interface`);
	await waitForAdminPageReady(page);
	await page.locator('input[name="screens-editdestination"][value="drawer"]').check();
	await saveAdminForm(page);

	await page.goto(`/modules/formulize/index.php?sid=${listSid}`);
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible({ timeout: 30000 });
	await page.getByRole('row', { name: new RegExp(EXHIBITION) }).locator('a[onclick^="javascript:goDetails"]').click();
	await expect(drawer(page)).toBeVisible();
	await expect(drawer(page).locator('.formulize-drawer__foot input[type="button"]').first()).toBeVisible();
	await expect(drawer(page).locator('.ck-editor__editable')).toContainText(DESCRIPTION);

	// a change somewhere else in the form, so that it's saved along with the delete. The Description isn't touched.
	await radioIn(drawer(page), 'dct_exhibition_travelling', 'No').check();
	await expect(drawer(page).locator('.formulize-label-dct_exhibition_venue')).not.toBeVisible();
	await conditionalChecksFinished(page);
	await drawer(page).locator(`input.delbox[name="delbox${otherCaseEntryId}"]`).check();
	await waitForDrawerFormToken(page);
	await dialogFrom(page, () => drawer(page).locator('input[name="deletesubs"]').click());

	await expect.poll(() => dbQuery(`SELECT entry_id FROM ${dataTable(casesFid)} WHERE entry_id = ${otherCaseEntryId}`).length, { message: 'the display case should be deleted' }).toBe(0);
	await expect(drawer(page)).not.toHaveClass(/formulize-drawer--busy/);
	expect(storedValue(exhibitionsFid, exhibitionEntryId, 'dct_exhibition_travelling'), 'the change should be saved').toBe('No');
	expect(storedValue(exhibitionsFid, exhibitionEntryId, 'dct_exhibition_description'), 'the Description should be kept').toContain(DESCRIPTION);

	await closeDrawer(page);
	await leavePage(page, `/modules/formulize/index.php?sid=${listSid}`);
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible({ timeout: 30000 });
});

test('The drawer asks about letters in a number box, and runs its own form\'s extra validation', async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openDisplayCaseInDrawer(page);
	const name = drawer(page).locator('.form-row:has(.formulize-label-dct_case_name)').getByRole('textbox');
	const shelves = drawer(page).getByRole('spinbutton', { name: 'Shelves' });

	// the display cases' extra validation refuses the name
	await name.fill(REFUSED_NAME);
	await name.press('Tab');
	expect(await dialogFrom(page, () => drawerSave(page).click())).toBe(REFUSAL);
	expect(storedValue(casesFid, caseEntryId, 'dct_case_name')).toBe(DISPLAY_CASE);
	await name.fill(DISPLAY_CASE);
	await name.press('Tab');

	// a number box can have letters as part of a number, and no means nothing is saved
	await shelves.fill('1e5');
	await shelves.press('Tab');
	expect(await dialogFrom(page, () => drawerSave(page).click(), 'dismiss')).toContain('1e5');
	expect(storedValue(casesFid, caseEntryId, 'dct_case_shelves'), 'nothing should be saved').not.toMatch(/\d/);

	await shelves.fill('12');
	await shelves.press('Tab');
	await saveDrawerForm(page);
	expect(Number(storedValue(casesFid, caseEntryId, 'dct_case_shelves')), 'the number should be saved').toBe(12);
	await closeDrawer(page);

	// the exhibition behind has no extra validation, so what the display cases refuse is fine there
	const exhibitionName = page.locator('#formulizeform .form-row:has(.formulize-label-dct_exhibition_name)').getByRole('textbox');
	await exhibitionName.fill(REFUSED_NAME);
	await exhibitionName.press('Tab');
	await saveFormulizeForm(page, 'Save');
	expect(storedValue(exhibitionsFid, exhibitionEntryId, 'dct_exhibition_name')).toBe(REFUSED_NAME);

	// put the name back, since tests after this one look for the exhibition by it
	await exhibitionName.fill(EXHIBITION);
	await exhibitionName.press('Tab');
	await saveFormulizeForm(page, 'Save');
	expect(storedValue(exhibitionsFid, exhibitionEntryId, 'dct_exhibition_name'), 'the exhibition should have its own name back').toBe(EXHIBITION);

	await leaveForExhibitionsList(page);
});
