const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, leavePage, conditionalElementReady, waitForJQueryAnimations, dbQuery, dbPrefix, sqlEscape, deleteMuseumFormsTitled } from '../../utils';

// Conditional elements in a sub entry opened in the drawer, over a form that has conditional elements of its own.
//
//  - A conditional radio button in the drawer shows the option that was saved. conditional.js copies each conditional
//    element's markup into a hidden div to compare it later. The drawer's endpoint puts everything the form prints inside
//    its <form>, so when that div was printed with the rest, a copied radio button joined the real one's group, took the
//    checked state from it, and was then thrown away, leaving nothing checked.
//  - The form behind keeps its conditional elements working after the drawer closes. The drawer runs conditional.js
//    again, and it used to start by emptying its lists of which elements govern which, so the form behind lost its own.
//
// The forms are made here with MCP, in the Museum application, and deleted at the end, so nothing the other specs use
// changes. MCP is part of the application, and 040 turns it on and makes an API key, which this reads from the admin UI.
// Nothing is saved on the page: the entries are made with MCP, and the changes made on the page are left unsaved.

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

function entryIdWhere(fid, handle, value) {
	const [[formHandle]] = dbQuery(`SELECT form_handle FROM ${dbPrefix()}_formulize_id WHERE id_form = ${fid}`);
	return dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_${formHandle} WHERE ${handle} = '${sqlEscape(value)}'`)[0][0];
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
	await api?.dispose();
});

test('Make an exhibition with a display case, each with a conditional element', async () => {
	exhibitionsFid = parseInt((await tool('create_form', { title: EXHIBITIONS_FORM, application_id_or_name: 'Museum' })).form_id, 10);
	await tool('create_text_box_element', { form_id: exhibitionsFid, type: 'text', caption: 'Name', handle: 'dct_exhibition_name', properties: {}, principal_identifier: true });
	await tool('create_list_element', { form_id: exhibitionsFid, type: 'radio', caption: 'Travelling', handle: 'dct_exhibition_travelling', properties: { options: ['Yes', 'No'] } });
	await tool('create_text_box_element', {
		form_id: exhibitionsFid, type: 'text', caption: 'Venue', handle: 'dct_exhibition_venue', properties: {},
		display_conditions: [{ element: 'dct_exhibition_travelling', operator: '=', value: 'Yes', type: 'match-all' }],
	});

	casesFid = parseInt((await tool('create_form', { title: CASES_FORM, application_id_or_name: 'Museum' })).form_id, 10);
	const caseName = await tool('create_text_box_element', { form_id: casesFid, type: 'text', caption: 'Name', handle: 'dct_case_name', properties: {}, principal_identifier: true });
	await tool('create_list_element', { form_id: casesFid, type: 'radio', caption: 'Lighting', handle: 'dct_case_lighting', properties: { options: ['Natural', 'Spotlit'] } });
	await tool('create_list_element', {
		form_id: casesFid, type: 'radio', caption: 'Spotlight colour', handle: 'dct_case_spotlight', properties: { options: ['Warm', 'Cool'] },
		display_conditions: [{ element: 'dct_case_lighting', operator: '=', value: 'Spotlit', type: 'match-all' }],
	});

	// The subform makes a linked element in the display cases form, pointing at the exhibition's Name
	await tool('create_subform_interface', {
		form_id: exhibitionsFid, type: 'subformListings', caption: 'Display cases', handle: 'dct_exhibition_cases',
		properties: { sourceForm: casesFid, elementsInRow: [parseInt(caseName.element_id, 10)], entryViewingMode: 'drawer' },
	});
	const linked = dbQuery(`SELECT ele_handle FROM ${dbPrefix()}_formulize WHERE id_form = ${casesFid} AND ele_type = 'selectLinked'`);
	expect(linked.length, 'the subform should have made one linked element in the display cases form').toBe(1);

	await tool('create_entries', { form_id: exhibitionsFid, data: [{ dct_exhibition_name: EXHIBITION, dct_exhibition_travelling: 'No' }] });
	await tool('create_entries', {
		form_id: casesFid,
		data: [{ dct_case_name: DISPLAY_CASE, dct_case_lighting: 'Spotlit', dct_case_spotlight: SAVED_SPOTLIGHT, [linked[0][0]]: EXHIBITION }],
	});
	exhibitionEntryId = entryIdWhere(exhibitionsFid, 'dct_exhibition_name', EXHIBITION);
	caseEntryId = entryIdWhere(casesFid, 'dct_case_name', DISPLAY_CASE);
	// the value the drawer has to show is the one that was stored
	const [[formHandle]] = dbQuery(`SELECT form_handle FROM ${dbPrefix()}_formulize_id WHERE id_form = ${casesFid}`);
	expect(dbQuery(`SELECT dct_case_spotlight FROM ${dbPrefix()}_formulize_${formHandle} WHERE entry_id = ${caseEntryId}`)[0][0]).toBe(SAVED_SPOTLIGHT);
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
