const { test, expect } = require('@playwright/test');
import { saveDrawerForm } from '../../utils';
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, leavePage, conditionalElementReady, waitForJQueryAnimations, saveFormulizeForm, saveAdminForm, openElementSettings, dbQuery, dbPrefix, sqlEscape, deleteMuseumFormsTitled } from '../../utils';

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
//
// The forms are made here with MCP, in the Museum application, and deleted at the end, so nothing the other specs use
// changes. MCP is part of the application, and 040 turns it on and makes an API key, which this reads from the admin UI.
// The entries are made with MCP. The last test saves the exhibition, and the others leave their changes unsaved.

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

let api;
let apiKey;
let requestId = 0;
let exhibitionsFid;
let casesFid;
let exhibitionEntryId;
let caseEntryId;

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
async function openDisplayCaseInDrawer(page) {
	await page.goto(`/modules/formulize/index.php?fid=${exhibitionsFid}&ve=${exhibitionEntryId}`);
	await page.waitForLoadState('load');
	await waitForJQueryAnimations(page);
	await page.locator(`a#view${caseEntryId}`).click();
	await expect(drawer(page)).toBeVisible();
	await expect(drawer(page).locator('.formulize-drawer__foot input[type="button"]').first()).toBeVisible();
	await expect(drawer(page).locator('.formulize-drawer__body').first()).not.toHaveClass(/formulize-drawer__body--entering/);
	await expect(drawer(page).locator('.formulize-label-dct_case_spotlight')).toBeVisible();
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

	casesFid = parseInt((await tool('create_form', { title: CASES_FORM, application_id_or_name: 'Museum' })).form_id, 10);
	const caseName = await tool('create_text_box_element', { form_id: casesFid, type: 'text', caption: 'Name', handle: 'dct_case_name', properties: {}, principal_identifier: true });
	await tool('create_list_element', { form_id: casesFid, type: 'radio', caption: 'Lighting', handle: 'dct_case_lighting', properties: { options: ['Natural', 'Spotlit'] } });
	await tool('create_list_element', {
		form_id: casesFid, type: 'radio', caption: 'Spotlight colour', handle: 'dct_case_spotlight', properties: { options: ['Warm', 'Cool'] },
		display_conditions: [{ element: 'dct_case_lighting', operator: '=', value: 'Spotlit', type: 'match-all' }],
	});
	await tool('create_text_box_element', { form_id: casesFid, type: 'textarea', caption: 'Notes', handle: 'dct_case_notes', properties: { useRichTextEditor: 1 } });
	await tool('create_text_box_element', { form_id: casesFid, type: 'text', caption: 'Catalogue number', handle: 'dct_case_catalogue', properties: {} });

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
