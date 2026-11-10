const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, editScreenPage, saveFormulizeForm, waitForAdminPageReady, createMuseumForm, deleteMuseumFormsTitled, leavePage, dbQuery, dbPrefix } from '../../utils';

// Alternate URLs (issues 1046 and 1047). 01-setup/015 turns them on.
//
//  - Every screen can be reached at its id, /<sid>/ and /<sid>/<entry>/, without an alternate address of its own.
//  - A form screen with no address of its own is reached at the address of the list it shows entries for, so
//    giving only the list an address is enough.
//  - Links Formulize writes use the clean address, and so does the address bar once someone is on one. An
//    index.php?sid= address someone went to themselves is left alone when the screen has no address typed for it.
//  - An address that is only a number can't be typed for a screen, since numbers are screen ids.
//
// Builds a throwaway form, whose default list and two-page form screen are the screens under test, so nothing
// here touches the screens other specs use. The tests run in order: each one depends on the ones before it.
test.describe.configure({ mode: 'serial' });

const FORM_TITLE = 'Alternate URL Test';
const FIRST_PAGE = 'First page';
const SECOND_PAGE = 'Second page';
const LIST_ADDRESS = 'alternate-url-test-list';

let fid = 0;
let aid = 0;
let listSid = 0;
let formSid = 0;
let aliceId = 0;

// The path and query of the page's address, decoded, so page titles can be compared as they are written
function pathOf(page) {
	const url = new URL(page.url());
	return decodeURIComponent(url.pathname) + url.search;
}

async function openScreenSettings(page, sid) {
	await page.goto(`/modules/formulize/admin/ui.php?page=screen&aid=${aid}&fid=${fid}&sid=${sid}`);
	await waitForAdminPageReady(page);
}

// Set a screen's alternate address, and optionally its identifying element, in the screen's settings
async function setAlternateAddress(page, sid, address, elementLabel = null) {
	await openScreenSettings(page, sid);
	await page.locator('input[name="screens-rewriteruleAddress"]').fill(address);
	if (elementLabel) {
		await page.locator('select[name="screens-rewriteruleElement"]').selectOption({ label: elementLabel });
	}
	await saveAdminForm(page);
}

// The list's link to an entry, from the row showing that name
function entryLink(page, name) {
	return page.getByRole('row', { name: new RegExp(name) }).locator('a[onclick^="javascript:goDetails"]');
}

async function addEntry(page, name) {
	await page.goto(`/modules/formulize/index.php?sid=${listSid}`);
	await page.getByRole('button', { name: `Add ${FORM_TITLE}`, exact: true }).click();
	await page.getByRole('textbox', { name: 'Name *' }).fill(name);
	await saveFormulizeForm(page);
	await page.getByRole('link', { name: 'Save and Close' }).click(); // clears the entry lock
}

test.describe('Alternate URLs', () => {

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage();
		await login(page, 'admin');
		await deleteMuseumFormsTitled(page, [FORM_TITLE]);
		await page.close();
	});

	test('build a form with a Name and a Notes element', async ({ page }) => {
		await login(page, 'admin');
		await deleteMuseumFormsTitled(page, [FORM_TITLE]); // whatever a killed run left behind
		fid = await createMuseumForm(page, FORM_TITLE, 'Name');
		aid = parseInt(dbQuery(`SELECT appid FROM ${dbPrefix()}_formulize_applications WHERE name = 'Museum'`)[0][0], 10);
		listSid = parseInt(dbQuery(`SELECT sid FROM ${dbPrefix()}_formulize_screen WHERE fid = ${fid} AND type = 'listOfEntries'`)[0][0], 10);
		formSid = parseInt(dbQuery(`SELECT sid FROM ${dbPrefix()}_formulize_screen WHERE fid = ${fid} AND type = 'multiPage'`)[0][0], 10);

		await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${fid}&aid=0&type=text`);
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Notes');
		await page.locator('input[name="elements-ele_handle"]').fill('alternate_url_test_notes');
		await saveAdminForm(page);
	});

	test('give the form screen two pages, Name on the first and Notes on the second', async ({ page }) => {
		// the way 015 builds the Donor screen
		await login(page, 'admin');
		await openScreenSettings(page, formSid);
		await page.getByRole('link', { name: 'Pages' }).click();
		await page.getByRole('link', { name: 'Edit this page' }).click();
		await page.getByRole('textbox', { name: 'Title for page number' }).fill(FIRST_PAGE);
		await page.getByLabel('Form elements to display on').selectOption([`${FORM_TITLE}: Name`]);
		await saveAdminForm(page, 'popup');
		await page.getByRole('button', { name: 'close' }).click();
		await page.getByRole('link', { name: 'Create a new page' }).click();
		await waitForAdminPageReady(page);
		await editScreenPage(page, 'New page');
		await page.getByRole('textbox', { name: 'Title for page number' }).fill(SECOND_PAGE);
		await page.getByLabel('Form elements to display on').selectOption([`${FORM_TITLE}: Notes`]);
		await saveAdminForm(page, 'popup');
		await page.getByRole('button', { name: 'close' }).click();
	});

	test('add two entries', async ({ page }) => {
		await login(page, 'admin');
		await addEntry(page, 'Alice');
		await addEntry(page, 'Bob');
		await page.goto(`/modules/formulize/index.php?sid=${listSid}`);
		const onclick = await entryLink(page, 'Alice').getAttribute('onclick');
		aliceId = parseInt(onclick.match(/goDetails\('(\d+)'\)/)[1], 10);
		expect(aliceId).toBeGreaterThan(0);
	});

	test('a list is reached at its id, and links to its entries at its id', async ({ page }) => {
		await login(page, 'admin');
		await page.goto(`/${listSid}/`);
		await expect(entryLink(page, 'Alice')).toHaveAttribute('href', new RegExp(`/${listSid}/${aliceId}/$`));
		await entryLink(page, 'Alice').click();
		await expect(page.getByRole('textbox', { name: 'Name *' })).toHaveValue('Alice');
		// the form screen shows the list's entry, so it is at the list's address, on its first page
		await expect.poll(() => pathOf(page)).toBe(`/${listSid}/${aliceId}/${FIRST_PAGE}/`);
		await leavePage(page);
	});

	test('a form screen is reached at its id with an entry', async ({ page }) => {
		await login(page, 'admin');
		await page.goto(`/${formSid}/${aliceId}/`);
		await expect(page.getByRole('textbox', { name: 'Name *' })).toHaveValue('Alice');
		await leavePage(page);
	});

	test('an index.php address to a screen with no alternate address of its own is left as it was', async ({ page }) => {
		await login(page, 'admin');
		await page.goto(`/modules/formulize/index.php?sid=${formSid}&ve=${aliceId}`);
		await expect(page.getByRole('textbox', { name: 'Name *' })).toHaveValue('Alice');
		expect(pathOf(page)).toBe(`/modules/formulize/index.php?sid=${formSid}&ve=${aliceId}`);
		await leavePage(page);
	});

	test('addresses that are no screen are not found', async ({ page }) => {
		expect((await page.request.get('/alternate-url-test-no-such-address/')).status()).toBe(404);
		expect((await page.request.get('/999999/')).status()).toBe(404);
	});

	test('with an address on the list only, the form screen shows entries at the list\'s address', async ({ page }) => {
		await login(page, 'admin');
		await setAlternateAddress(page, listSid, LIST_ADDRESS);

		await page.goto(`/modules/formulize/index.php?sid=${listSid}`);
		await expect(entryLink(page, 'Alice')).toHaveAttribute('href', new RegExp(`/${LIST_ADDRESS}/${aliceId}/$`));
		await entryLink(page, 'Alice').click();
		await expect(page.getByRole('textbox', { name: 'Name *' })).toHaveValue('Alice');
		await expect.poll(() => pathOf(page)).toBe(`/${LIST_ADDRESS}/${aliceId}/${FIRST_PAGE}/`);

		await page.getByRole('link', { name: SECOND_PAGE, exact: true }).click();
		await expect(page.getByRole('textbox', { name: 'Notes' })).toBeVisible();
		await expect.poll(() => pathOf(page)).toBe(`/${LIST_ADDRESS}/${aliceId}/${SECOND_PAGE}/`);

		// back to the list, at the index.php address it was opened at
		await page.getByRole('link', { name: 'Save and Close' }).click();
		await expect(entryLink(page, 'Alice')).toBeVisible();
	});

	test('the list\'s address with an entry and a page goes straight to that page, and back to the list when done', async ({ page }) => {
		await login(page, 'admin');
		await page.goto(`/${LIST_ADDRESS}/${aliceId}/${encodeURIComponent(SECOND_PAGE)}/`);
		await expect(page.getByRole('textbox', { name: 'Notes' })).toBeVisible();
		await expect.poll(() => pathOf(page)).toBe(`/${LIST_ADDRESS}/${aliceId}/${SECOND_PAGE}/`);
		await page.getByRole('link', { name: 'Save and Close' }).click();
		await expect(entryLink(page, 'Alice')).toBeVisible();
		expect(pathOf(page)).toBe(`/${LIST_ADDRESS}/`);
	});

	test('the list can identify entries by their name', async ({ page }) => {
		await login(page, 'admin');
		await setAlternateAddress(page, listSid, LIST_ADDRESS, 'Name');
		await page.goto(`/${LIST_ADDRESS}/`);
		await expect(entryLink(page, 'Bob')).toHaveAttribute('href', new RegExp(`/${LIST_ADDRESS}/Bob/$`));
		await page.goto(`/${LIST_ADDRESS}/Bob/`);
		await expect(page.getByRole('textbox', { name: 'Name *' })).toHaveValue('Bob');
		await expect.poll(() => pathOf(page)).toBe(`/${LIST_ADDRESS}/Bob/${FIRST_PAGE}/`);
		await leavePage(page);
	});

	test('a screen with no address of its own shows its id in the address box, and saving that keeps it that way', async ({ page }) => {
		await login(page, 'admin');
		await openScreenSettings(page, formSid);
		const address = page.locator('input[name="screens-rewriteruleAddress"]');
		await expect(address).toHaveValue(`${formSid}`);
		await saveAdminForm(page);
		await openScreenSettings(page, formSid);
		await expect(address).toHaveValue(`${formSid}`);
		// still reached by its id, as every screen is
		expect((await page.request.get(`/${formSid}/`)).status()).toBeLessThan(400);
	});

	test('an alternate address that is only a number is not saved', async ({ page }) => {
		await login(page, 'admin');
		await openScreenSettings(page, listSid);
		await page.locator('input[name="screens-rewriteruleAddress"]').fill('2026');
		let message = '';
		page.once('dialog', async dialog => {
			message = dialog.message();
			await dialog.accept();
		});
		await saveAdminForm(page);
		await expect.poll(() => message).toContain('It can\'t be only a number');
		await openScreenSettings(page, listSid);
		await expect(page.locator('input[name="screens-rewriteruleAddress"]')).toHaveValue(LIST_ADDRESS);
	});
});
