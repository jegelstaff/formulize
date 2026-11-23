const { test, expect } = require('@playwright/test');
import {
	login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, waitForWorkingMessage, createMuseumForm, deleteMuseumFormsTitled,
	openAllColumnSections, applyColumnChanges, dbQuery, dbPrefix, leavePage,
	openEntry, entryArea, saveAndCloseEntry, openChangeColumns, showListSearches,
} from '../../utils';

// An element whose values are encrypted in the database.
//
//  - Turning encryption on for an element that has values encrypts the values already saved.
//  - What's stored can't be read, and everything people see is the values they typed: the list, the values its Search box
//    offers, searching, and the form.
//  - A value saved after that is encrypted too.
//  - A linked element that shows the encrypted values offers them as they were typed, and a list shows them that way.
//  - An export has the values as they were typed.
//
// The key is in no query: queries name a session variable that holds it. See formulize_aesKeySQL(). Values are decrypted where
// a query reads them, and never afterwards. See formulize_fieldValueSQL().

test.describe.configure({ mode: 'serial' });

const FORM_TITLE = 'Encryption Test';
const LINK_TITLE = 'Encryption Test Links';
const HANDLE = 'enc_secret';
const LINK_HANDLE = 'enc_secret_choice';
// the form that links to the other is deleted first
const FORM_TITLES = [LINK_TITLE, FORM_TITLE];

let fid = 0;

// the values stored in the element's field, as they are in the database
function storedValues() {
	const [[formHandle]] = dbQuery(`SELECT form_handle FROM ${dbPrefix()}_formulize_id WHERE id_form = ${fid}`);
	return dbQuery(`SELECT HEX(\`${HANDLE}\`) FROM ${dbPrefix()}_formulize_${formHandle} ORDER BY entry_id`).map(row => row[0]);
}

const hex = text => Buffer.from(text, 'utf8').toString('hex').toUpperCase();

async function addEntry(page, id, secret) {
	await page.goto(`/modules/formulize/index.php?fid=${fid}`);
	await openEntry(page, page.getByRole('button', { name: `Add ${FORM_TITLE}`, exact: true }));
	await entryArea(page).getByRole('textbox', { name: 'ID *' }).fill(id);
	await entryArea(page).getByLabel('Secret').fill(secret);
	await saveFormulizeForm(page);
	await saveAndCloseEntry(page);
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
}

// the form's list, with the Secret column added
async function listWithSecret(page) {
	await page.goto(`/modules/formulize/index.php?fid=${fid}`);
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
	const popup = await openChangeColumns(page);
	await openAllColumnSections(popup);
	await popup.getByRole('checkbox', { name: 'Secret', exact: true }).check();
	await applyColumnChanges(popup);
	await waitForWorkingMessage(page);
	await showListSearches(page);
	await expect(page.locator(`input[name="search_${HANDLE}"]`)).toBeVisible();
}

const offeredValues = async page => JSON.parse(await page.locator(`.formulize-searchbox[data-name="search_${HANDLE}"]`).getAttribute('data-options')).map(option => option.label).sort();

test.beforeAll(async ({ browser }) => {
	// a run that was interrupted could have left the form behind
	const page = await browser.newPage();
	await login(page);
	await deleteMuseumFormsTitled(page, FORM_TITLES);
	await page.close();
});

test.afterAll(async ({ browser }) => {
	const page = await browser.newPage();
	await login(page);
	await deleteMuseumFormsTitled(page, FORM_TITLES);
	await page.close();
});

test('A form with a text element and three entries', async ({ page }) => {
	await login(page);
	fid = await createMuseumForm(page, FORM_TITLE, 'ID');
	expect(fid).toBeGreaterThan(0);
	await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${fid}&aid=0&type=text`);
	await waitForAdminPageReady(page);
	await page.locator('input[name="elements-ele_caption"]').fill('Secret');
	await page.locator('input[name="elements-ele_handle"]').fill(HANDLE);
	await page.locator('input[name="elements-ele_colhead"]').fill('Secret');
	await saveAdminForm(page);
	await addEntry(page, 'E1', 'alpha');
	await addEntry(page, 'E2', 'beta');
	await addEntry(page, 'E3', 'alpha');
	expect(storedValues()).toEqual([hex('alpha'), hex('beta'), hex('alpha')]);
});

test('Turning encryption on encrypts the values already saved', async ({ page }) => {
	await login(page);
	const [[eleId]] = dbQuery(`SELECT ele_id FROM ${dbPrefix()}_formulize WHERE ele_handle = '${HANDLE}'`);
	const elementUrl = `/modules/formulize/admin/ui.php?page=element&ele_id=${eleId}&fid=${fid}&aid=0`;
	await page.goto(elementUrl);
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Advanced', exact: true }).click();
	await page.locator('input[name="elements-ele_encrypt"][value="1"]').check();
	await saveAdminForm(page);
	await page.goto(elementUrl);
	await waitForAdminPageReady(page);
	await expect(page.locator('input[name="elements-ele_encrypt"][value="1"]')).toBeChecked();

	// every value is still there, none of them can be read, and the two that were the same are still the same as each other
	const stored = storedValues();
	expect(stored).toHaveLength(3);
	for (const value of stored) {
		expect(value).toBeTruthy();
		expect([hex('alpha'), hex('beta')]).not.toContain(value);
	}
	expect(stored[0]).toEqual(stored[2]);
	expect(stored[1]).not.toEqual(stored[0]);
});

test('The list, its Search box and searching all work with the values people typed', async ({ page }) => {
	await login(page);
	await listWithSecret(page);
	await expect(page.getByText('Showing entries: 1 to 3 of 3.')).toBeVisible();
	await expect(page.getByText('alpha', { exact: true })).toHaveCount(2);
	await expect(page.getByText('beta', { exact: true })).toHaveCount(1);
	expect(await offeredValues(page)).toEqual(['alpha', 'beta']);

	// a typed search finds the values as they were typed
	const box = page.locator(`input[name="search_${HANDLE}"]`);
	await box.fill('alp');
	await box.press('Enter');
	await waitForWorkingMessage(page);
	await expect(page.getByText('Showing entries: 1 to 2 of 2.')).toBeVisible();

	// and another column's search narrows what the Secret box offers to the values in the entries left
	await page.locator(`input[name="search_${HANDLE}"]`).fill('');
	const [[idHandle]] = dbQuery(`SELECT ele_handle FROM ${dbPrefix()}_formulize WHERE id_form = ${fid} AND ele_caption = 'ID'`);
	const idBox = page.locator(`input[type="text"][name="search_${idHandle}"]`);
	await idBox.fill('=E2');
	await idBox.press('Enter');
	await waitForWorkingMessage(page);
	await expect(page.getByText('Showing entries: 1 to 1 of 1.')).toBeVisible();
	expect(await offeredValues(page)).toEqual(['beta']);
});

test('A value changed in the form is shown in the form, saved encrypted, and shown in the list', async ({ page }) => {
	await login(page);
	await listWithSecret(page);
	await openEntry(page, page.getByRole('row', { name: /beta/ }).getByRole('link').first());
	// in the entry, since the list's search box for the column is labelled Search Secret
	await expect(entryArea(page).getByLabel('Secret')).toHaveValue('beta');
	await entryArea(page).getByLabel('Secret').fill('gamma');
	await saveFormulizeForm(page);
	await expect(entryArea(page).getByLabel('Secret')).toHaveValue('gamma');
	await leavePage(page, `/modules/formulize/index.php?fid=${fid}`);

	const stored = storedValues();
	expect(stored).not.toContain(hex('gamma'));
	expect(stored[1]).not.toEqual(stored[0]);

	await listWithSecret(page);
	await expect(page.getByText('gamma', { exact: true })).toHaveCount(1);
	expect(await offeredValues(page)).toEqual(['alpha', 'gamma']);
});

test('A linked element offers the encrypted values as they were typed, and a list of its entries shows them', async ({ page }) => {
	await login(page);
	const linkFid = await createMuseumForm(page, LINK_TITLE, 'Name');
	expect(linkFid).toBeGreaterThan(0);
	await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${linkFid}&aid=0&type=checkboxLinked`);
	await waitForAdminPageReady(page);
	await page.locator('input[name="elements-ele_caption"]').fill('Secret choice');
	await page.locator('input[name="elements-ele_handle"]').fill(LINK_HANDLE);
	await page.locator('input[name="elements-ele_colhead"]').fill('Secret choice');
	await page.getByRole('link', { name: 'Options' }).click();
	await page.locator('#formlink').selectOption(`${FORM_TITLE}: Secret`);
	await saveAdminForm(page);

	// the form offers the values as they were typed: alpha, which E1 and E3 hold, and gamma, which E2 now holds
	await page.goto(`/modules/formulize/index.php?fid=${linkFid}`);
	await openEntry(page, page.getByRole('button', { name: /^Add Encryption Test Links?$/ })); // the button names one entry
	await entryArea(page).getByRole('textbox', { name: 'Name *' }).fill('L1');
	await expect(entryArea(page).getByRole('checkbox', { name: 'alpha', exact: true }).first()).toBeVisible();
	await entryArea(page).getByRole('checkbox', { name: 'gamma', exact: true }).check();
	await saveFormulizeForm(page);
	await saveAndCloseEntry(page);
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();

	// the list shows the value the entry points to, as it was typed
	const popup = await openChangeColumns(page);
	await openAllColumnSections(popup);
	await popup.getByRole('checkbox', { name: 'Secret choice', exact: true }).check();
	await applyColumnChanges(popup);
	await waitForWorkingMessage(page);
	await expect(page.getByText('gamma', { exact: true })).toHaveCount(1);
});

test('An export has the values as they were typed', async ({ page }) => {
	await login(page);
	await listWithSecret(page);
	// what the Export button opens, and what its popup sends to make the file
	const onclick = await page.locator('#formulize_exportButton').getAttribute('onclick');
	const exportUrl = onclick.match(/showPop\('([^']+)'/)[1];
	const exported = await (await page.request.post(exportUrl, { form: { metachoice: '0' } })).text();
	expect(exported).toContain('alpha');
	expect(exported).toContain('gamma');
	expect(exported).not.toContain('beta');
});
