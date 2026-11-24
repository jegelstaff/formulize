const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, waitForJQueryAnimations, waitForAdminPageReady, saveAdminForm, elementRow, dbQuery, dbPrefix, sqlEscape, deleteMuseumFormsTitled } from '../../utils';

// Changing any kind of field marks the form as changed, so closing it asks whether to discard the change, and saving it
// saves. One listener on the page does this, and not the fields themselves, so an element's onchange can do work of its own.
//
// Each kind of field is changed full screen, which is where most people use a form. The drawer draws the same fields, so a
// few of them are changed there, in a drawer opened from a list, where nothing but the drawer brings the listener.
//
// A field's own onchange is checked too. A date outside its limits is refused as soon as it's chosen. The date element
// used to add two onchange attributes to its field, and the browser used only the first, which didn't check the date.
//
// The form is made here with MCP, in the Museum application, and deleted at the end, so nothing the other specs use
// changes. 040 turns MCP on and makes an API key, which this reads from the admin UI. Every change is discarded.

test.describe.configure({ mode: 'serial' });

const FORM = 'Form Changed Test Objects';
const OBJECT = 'Object One';
const DISCARD = 'Discard unsaved changes to this entry?';
const EARLIEST = '2026-01-01';
const LATEST = '2026-12-31';

// a text box changes when it loses focus, the way it does when a person moves on to the next field
const type = value => async row => {
	const box = row.locator('input, textarea').first();
	await box.fill(value);
	await box.press('Tab');
};
const choose = option => async row => row.getByRole('radio', { name: option, exact: true }).click();

// each kind of field, the MCP tool that makes it, and how a person changes it
const FIELDS = [
	{ kind: 'text box', tool: 'create_text_box_element', type: 'text', change: type('New text') },
	{ kind: 'multi-line text box', tool: 'create_text_box_element', type: 'textarea', change: type('New text') },
	{ kind: 'number box', tool: 'create_text_box_element', type: 'number', change: type('7') },
	{ kind: 'email address', tool: 'create_text_box_element', type: 'email', change: type('someone@example.com') },
	{ kind: 'phone number', tool: 'create_text_box_element', type: 'phone', change: type('416-555-0100') },
	{ kind: 'dropdown list', tool: 'create_list_element', type: 'select', properties: { options: ['Red', 'Blue'] }, change: row => row.locator('select').selectOption({ label: 'Blue' }) },
	{ kind: 'radio buttons', tool: 'create_list_element', type: 'radio', properties: { options: ['Red', 'Blue'] }, change: choose('Blue') },
	{ kind: 'yes/no radio buttons', tool: 'create_list_element', type: 'yn', change: choose('Yes') },
	{ kind: 'checkboxes', tool: 'create_list_element', type: 'checkbox', properties: { options: ['Red', 'Blue'] }, change: row => row.getByRole('checkbox', { name: 'Blue', exact: true }).check() },
	{ kind: 'date', tool: 'create_selector_element', type: 'date', change: row => row.locator('input[type="date"]').fill('2026-06-15') },
	{ kind: 'date with limits', tool: 'create_selector_element', type: 'date', properties: { minDate: EARLIEST, maxDate: LATEST }, change: row => row.locator('input[type="date"]').fill('2026-06-15') },
	{ kind: 'time', tool: 'create_selector_element', type: 'time', change: row => row.locator('input[type="time"]').fill('10:30') },
];
FIELDS.forEach((field, i) => { field.handle = `fct_field_${i}`; });
const field = kind => FIELDS.find(f => f.kind === kind);
const RADIO_WITH_VALUE = 'fct_radio_with_value';
// The last element's label, which shows that the form is there. Not its row, which has no height in Anari (see elementRow() in utils.js).
const lastLabel = container => container.locator(`.formulize-label-${RADIO_WITH_VALUE}`);

let api;
let apiKey;
let requestId = 0;
let fid;
let listSid;

async function tool(name, args) {
	const response = await api.post('/mcp/index.php/mcp', {
		headers: { 'Authorization': `Bearer ${apiKey}` },
		data: { jsonrpc: '2.0', id: ++requestId, method: 'tools/call', params: { name, arguments: args } },
	});
	const body = await response.json();
	expect(body.error, `${name} ${JSON.stringify(args)} should succeed, and MCP said: ${body.error?.message ?? ''}`).toBeUndefined();
	return JSON.parse(body.result.content[0].text);
}

async function deleteTheForm(browser) {
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	try {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await deleteMuseumFormsTitled(page, [FORM]);
	} finally {
		await page.close();
	}
}

const editDestination = () => dbQuery(`SELECT editdestination FROM ${dbPrefix()}_formulize_screen_listofentries WHERE sid = ${listSid}`)[0][0];

// where the list opens its entries, which is set on its Interface tab: full screen, or the drawer
async function openEntriesIn(page, destination) {
	await page.goto(`/modules/formulize/admin/ui.php?page=screen&fid=${fid}&sid=${listSid}&tab=interface`);
	await waitForAdminPageReady(page);
	await page.locator(`input[name="screens-editdestination"][value="${destination}"]`).check();
	await saveAdminForm(page);
	expect(editDestination(), `the list should open entries in the ${destination}`).toBe(destination);
}

const expectTheList = page => expect(page.getByText(/Showing entries:/).first()).toBeVisible({ timeout: 30000 });

async function openFromList(page) {
	await page.goto(`/modules/formulize/index.php?sid=${listSid}`);
	await expectTheList(page);
	await page.getByRole('row', { name: new RegExp(OBJECT) }).locator('a[onclick^="javascript:goDetails"]').click();
}

async function openFullScreen(page) {
	await openFromList(page);
	await expect(lastLabel(page)).toBeVisible();
	await page.waitForLoadState('load');
	await waitForJQueryAnimations(page);
}

const drawer = page => page.locator('.formulize-drawer[role="dialog"]');
const drawerRow = (page, handle) => drawer(page).locator(`.form-row:has(.formulize-label-${handle})`);

// The drawer's footer controls are put in after its form's scripts have run
async function openInDrawer(page) {
	await openFromList(page);
	await expect(drawer(page)).toBeVisible();
	await expect(drawer(page).locator('.formulize-drawer__foot input[type="button"]').first()).toBeVisible();
	await expect(drawer(page).locator('.formulize-drawer__body').first()).not.toHaveClass(/formulize-drawer__body--entering/);
	await expect(lastLabel(drawer(page))).toBeVisible();
}

// Do something, accept whatever the page asks while it happens, and return what it asked
async function questionsAskedBy(page, action) {
	const asked = [];
	const answer = dialog => { asked.push(dialog.message()); return dialog.accept(); };
	page.on('dialog', answer);
	try {
		await action();
	} finally {
		page.off('dialog', answer);
	}
	return asked;
}

// Closing goes back to the list, which releases the entry's lock
const closeFullScreen = page => questionsAskedBy(page, async () => {
	await page.getByRole('button', { name: 'Close', exact: true }).click();
	await expectTheList(page);
});
const closeDrawer = page => questionsAskedBy(page, async () => {
	await drawer(page).locator('.formulize-drawer__close').click();
	await expect(drawer(page)).toBeHidden();
});

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
	await deleteTheForm(browser); // anything an interrupted run left
});

test.afterAll(async ({ browser }) => {
	await deleteTheForm(browser);
	const left = dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title = '${sqlEscape(FORM)}'`);
	expect(left, 'the form this file made should be gone').toEqual([]);
	await api?.dispose();
});

test('Make a form with a field of each kind', async ({ page }) => {
	fid = parseInt((await tool('create_form', { title: FORM, application_id_or_name: 'Museum' })).form_id, 10);
	await tool('create_text_box_element', { form_id: fid, type: 'text', caption: 'Name', handle: 'fct_name', properties: {}, principal_identifier: true });
	for (const { tool: toolName, type: elementType, kind, handle, properties } of FIELDS) {
		await tool(toolName, { form_id: fid, type: elementType, caption: `A ${kind}`, handle, properties: properties ?? {} });
	}
	await tool('create_list_element', { form_id: fid, type: 'radio', caption: 'Radio buttons with a value', handle: RADIO_WITH_VALUE, properties: { options: ['Red', 'Blue'] } });
	await tool('create_entries', { form_id: fid, data: [{ fct_name: OBJECT, [RADIO_WITH_VALUE]: 'Red' }] });

	listSid = dbQuery(`SELECT defaultlist FROM ${dbPrefix()}_formulize_id WHERE id_form = ${fid}`)[0][0];
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openEntriesIn(page, 'screen');
});

test('Closing a form that has not changed does not ask anything', async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openFullScreen(page);
	expect(await closeFullScreen(page)).toEqual([]);
});

for (const { kind, handle, change } of FIELDS) {
	test(`Closing a form asks whether to discard a change to a field: ${kind}`, async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await openFullScreen(page);
		await change(elementRow(page, handle));
		expect(await closeFullScreen(page)).toEqual([DISCARD]);
	});
}

// Clicking the radio button that's chosen clears it. The page's script does that, with a change event of jQuery's own.
test('Closing a form asks whether to discard the clearing of a radio button', async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openFullScreen(page);
	const red = elementRow(page, RADIO_WITH_VALUE).getByRole('radio', { name: 'Red', exact: true });
	await expect(red).toBeChecked();
	await red.click();
	await expect(red).not.toBeChecked();
	expect(await closeFullScreen(page)).toEqual([DISCARD]);
});

test('A date outside its limits is refused as soon as it is chosen', async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openFullScreen(page);
	const date = elementRow(page, field('date with limits').handle).locator('input[type="date"]');
	for (const [outside, reason] of [['2025-12-31', 'too far in the past'], ['2027-01-01', 'too far in the future']]) {
		// the field stays in focus, so it's the change that's checked, and not leaving the field
		const said = await questionsAskedBy(page, () => date.fill(outside));
		expect(said.length, `${outside} should be refused, once`).toBe(1);
		expect(said[0]).toContain(reason);
		await expect(date).toHaveValue('');
	}
	await closeFullScreen(page);
});

test('A drawer opened from a list asks whether to discard a change, and only then', async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await openEntriesIn(page, 'drawer');

	await openInDrawer(page);
	expect(await closeDrawer(page)).toEqual([]);

	for (const kind of ['text box', 'dropdown list', 'checkboxes', 'date']) {
		await openInDrawer(page);
		await field(kind).change(drawerRow(page, field(kind).handle));
		expect(await closeDrawer(page), kind).toEqual([DISCARD]);
	}
});
