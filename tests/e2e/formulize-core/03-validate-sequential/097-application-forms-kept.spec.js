const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, waitForAdminPageReady, saveAdminForm, createMuseumForm, deleteMuseumFormsTitled, dbQuery, dbPrefix, sqlEscape } from '../../utils';

// Two people working on the same application don't undo each other's changes to which forms are in it.
//
// Saving an application's page saves every tab, including the list of its forms on the Settings tab. The list used to be
// saved as it was when the page was opened. So if someone else put a form in the application in the meantime, the save
// took it out, even when the person had only been working on the menu. And if someone else took a form out, the save put
// it back.
//
// One person has Museum's page open while the other changes which forms are in Museum. The forms are made here, and
// deleted at the end. The lists are compared to check that no other form moved.

test.describe.configure({ mode: 'serial' });

const FIRST_FORM = 'Application Forms Test First';
const SECOND_FORM = 'Application Forms Test Second';

let museumId;
let museumFormsAtFirst;
let firstFid;
let secondFid;

const formsInMuseum = () => dbQuery(`SELECT fid FROM ${dbPrefix()}_formulize_application_form_link WHERE appid = ${museumId} ORDER BY fid`)
	.map(row => parseInt(row[0], 10));
const museumFormsAnd = (...fids) => [...museumFormsAtFirst, ...fids].sort((a, b) => a - b);

// each person is logged in as the admin, in a browser of their own
async function personAtWork(browser) {
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	return page;
}

async function openMuseum(page, tab) {
	await page.goto(`/modules/formulize/admin/ui.php?page=application&aid=${museumId}&tab=${encodeURIComponent(tab)}`);
	await waitForAdminPageReady(page);
}

// Select and deselect forms in the list on the Settings tab, which is open, leaving the others as they are
async function changeForms(page, { select = [], deselect = [] }) {
	const list = page.locator('#applications-forms');
	await expect(list).toBeVisible();
	const selected = await list.evaluate(element => Array.from(element.selectedOptions, option => option.value));
	await list.selectOption([...selected.filter(fid => !deselect.map(String).includes(fid)), ...select.map(String)]);
}

async function deleteTheForms(browser) {
	const page = await personAtWork(browser);
	try {
		// a test that failed part way can have left a form out of Museum, which is where forms are deleted from
		const fids = dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title IN ('${sqlEscape(FIRST_FORM)}', '${sqlEscape(SECOND_FORM)}')`)
			.map(row => parseInt(row[0], 10));
		if (fids.some(fid => !formsInMuseum().includes(fid))) {
			await openMuseum(page, 'settings');
			await changeForms(page, { select: fids });
			await saveAdminForm(page);
		}
		await deleteMuseumFormsTitled(page, [FIRST_FORM, SECOND_FORM]);
	} finally {
		await page.close();
	}
}

test.beforeAll(async ({ browser }) => {
	museumId = parseInt(dbQuery(`SELECT appid FROM ${dbPrefix()}_formulize_applications WHERE name = 'Museum'`)[0][0], 10);
	await deleteTheForms(browser); // anything an interrupted run left
	museumFormsAtFirst = formsInMuseum();
	expect(museumFormsAtFirst.length, 'Museum should have its forms').toBeGreaterThan(0);
});

test.afterAll(async ({ browser }) => {
	await deleteTheForms(browser);
	expect(formsInMuseum(), 'Museum should have the forms it had at first').toEqual(museumFormsAtFirst);
});

test('A form put in an application stays there when someone saves a page they opened before', async ({ browser }) => {
	const first = await personAtWork(browser);
	const second = await personAtWork(browser);

	await openMuseum(first, 'menu entries');
	firstFid = await createMuseumForm(second, FIRST_FORM);
	expect(formsInMuseum()).toEqual(museumFormsAnd(firstFid));
	// the page that's open is from before the form was made
	await expect(first.locator(`#applications-forms option[value="${firstFid}"]`)).toHaveCount(0);
	await saveAdminForm(first);

	expect(formsInMuseum()).toEqual(museumFormsAnd(firstFid));
	await openMuseum(first, 'settings');
	await expect(first.locator(`#applications-forms option[value="${firstFid}"]`)).toHaveJSProperty('selected', true);

	await first.close();
	await second.close();
});

test('Changing which forms are in an application leaves a form put there by someone else', async ({ browser }) => {
	const first = await personAtWork(browser);
	const second = await personAtWork(browser);

	// one person takes the first form out, on a page from before the other person made the second form
	await openMuseum(first, 'settings');
	secondFid = await createMuseumForm(second, SECOND_FORM);
	expect(formsInMuseum()).toEqual(museumFormsAnd(firstFid, secondFid));
	await changeForms(first, { deselect: [firstFid] });
	await saveAdminForm(first);
	expect(formsInMuseum()).toEqual(museumFormsAnd(secondFid));

	// and puts it back
	await openMuseum(first, 'settings');
	await changeForms(first, { select: [firstFid] });
	await saveAdminForm(first);
	expect(formsInMuseum()).toEqual(museumFormsAnd(firstFid, secondFid));

	await first.close();
	await second.close();
});

test('A form taken out of an application stays out when someone saves a page they opened before', async ({ browser }) => {
	const first = await personAtWork(browser);
	const second = await personAtWork(browser);

	await openMuseum(first, 'menu entries');
	await openMuseum(second, 'settings');
	await changeForms(second, { deselect: [secondFid] });
	await saveAdminForm(second);
	expect(formsInMuseum()).toEqual(museumFormsAnd(firstFid));
	await saveAdminForm(first);
	expect(formsInMuseum()).toEqual(museumFormsAnd(firstFid));

	// put it back, so it can be deleted from Museum
	await openMuseum(second, 'settings');
	await changeForms(second, { select: [secondFid] });
	await saveAdminForm(second);
	expect(formsInMuseum()).toEqual(museumFormsAnd(firstFid, secondFid));

	await first.close();
	await second.close();
});
