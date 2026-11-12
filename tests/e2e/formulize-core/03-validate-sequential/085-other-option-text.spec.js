const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';
import { login, saveFormulizeForm, saveAdminForm, leavePage, waitForWorkingMessage, openAllColumnSections, applyColumnChanges, dbQuery, dbPrefix, sqlEscape } from '../../utils';

/**
 * What people type into the box of an Other option saves with the element's value.
 *
 * Radio buttons and checkboxes can have an Other option, such as {OTHER|30}, with a box people type their own text
 * into. The data table stores the Other option, and the text is kept in the formulize_other table. These tests give
 * the museum's Donors "Type of donor" radio buttons and Collections "Suitable for" checkboxes an Other option through
 * the admin UI, then check that the text:
 *   - saves for a new entry
 *   - saves when it's the only thing that changed in an existing entry, which counts as a change to the entry
 *   - is removed when the Other option is no longer chosen, so choosing it again shows an empty box
 *   - is kept by a clone
 *   - can be set through MCP update_entries, with a value that isn't one of the options
 *
 * They change the museum's elements and the AI assistant setting, so they run in the sequential stage, and put both
 * back afterwards. A site whose elements already have an Other option keeps it.
 */
test.describe.configure({ mode: 'serial' });

const OTHER_OPTION = '{OTHER|30}';
const COLLECTION_NAME = 'Other text 085';

function value(sql) {
	const rows = dbQuery(sql);
	return rows.length ? rows[0][0] : null;
}

function elementByHandle(handle) {
	const row = dbQuery(`SELECT ele_id, id_form FROM ${dbPrefix()}_formulize WHERE ele_handle = '${sqlEscape(handle)}'`)[0];
	return { id: parseInt(row[0], 10), fid: parseInt(row[1], 10) };
}

function screenOfType(fid, type) {
	return parseInt(value(`SELECT sid FROM ${dbPrefix()}_formulize_screen WHERE fid = ${fid} AND type = '${type}' ORDER BY sid LIMIT 1`), 10);
}

function hasOtherOption(element) {
	return value(`SELECT ele_value FROM ${dbPrefix()}_formulize WHERE ele_id = ${element.id}`).includes('{OTHER|');
}

// what is stored for an entry's Other box, as a list, since there should only ever be one
function storedOtherText(element, entryId) {
	return dbQuery(`SELECT other_text FROM ${dbPrefix()}_formulize_other WHERE id_req = ${entryId} AND ele_id = ${element.id}`).map(row => row[0]);
}

function modifiedAt(table, entryId) {
	return value(`SELECT mod_datetime FROM ${dbPrefix()}_formulize_${table} WHERE entry_id = ${entryId}`);
}

function collectionIds() {
	return dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_collections WHERE collections_name LIKE '${sqlEscape(COLLECTION_NAME)}%' ORDER BY entry_id`)
		.map(row => parseInt(row[0], 10));
}

let audience; // Collections "Suitable for", checkboxes
let donorType; // Donors "Type of donor", radio buttons
let addedOtherOptionTo = []; // the elements these tests gave an Other option, which they take away again
let collectionFormSid, collectionListSid, donorFormSid, donorListSid;
let collectionId = null;
let cloneId = null;
let donorId = null;
let aiAssistantWasOn = null;

// the box of the element's Other option, in the form for an entry
function otherBox(page, element, entryId) {
	return page.locator(`input[name="other[ele_${element.id}_${entryId === 'new' ? 'new_0' : entryId}]"]`);
}

// the Other option is the last of the element's options
function otherOption(page, element, entryId) {
	const suffix = element === audience ? '[]' : '';
	return page.locator(`input[name="de_${element.fid}_${entryId}_${element.id}${suffix}"]`).last();
}

// open a list at the page that shows the entry, going through the pages the way a person would
async function showInList(page, sid, entryId) {
	await page.goto(`/modules/formulize/index.php?sid=${sid}`);
	await expect(page.getByText(/Showing entries/).first()).toBeVisible();
	for (let pagesSeen = 0; pagesSeen < 20; pagesSeen++) {
		if (await page.locator(`#delete_${entryId}`).count() > 0) {
			return;
		}
		await page.getByRole('link', { name: 'Next', exact: true }).click();
		await waitForWorkingMessage(page);
	}
	throw new Error(`Entry ${entryId} is not in the list`);
}

// search the Collections list's Suitable for column, which the list doesn't show until it's added with Change columns.
// The column is headed by the element's column heading, Suitable audience.
async function searchSuitableFor(page, term) {
	await page.goto(`/modules/formulize/index.php?sid=${collectionListSid}`);
	await expect(page.getByText(/Showing entries/).first()).toBeVisible();
	const popupPromise = page.context().waitForEvent('page');
	await page.getByRole('button', { name: 'Change columns' }).click();
	const popup = await popupPromise;
	await popup.bringToFront();
	await openAllColumnSections(popup);
	await popup.getByRole('group', { name: 'Collections', exact: true }).getByLabel('Suitable audience', { exact: true }).check();
	await applyColumnChanges(popup);
	await expect(page.getByRole('link', { name: 'Suitable audience', exact: true })).toBeVisible();
	// the values the column's Search box lists, which are drawn with the page
	const offered = JSON.parse(await page.locator('.formulize-searchbox[data-name="search_collections_suitable_audience"]').getAttribute('data-options')).map(option => option.label);
	const searchBox = page.locator('input[name="search_collections_suitable_audience"]');
	await searchBox.fill(term);
	await searchBox.press('Enter');
	await waitForWorkingMessage(page);
	await expect(searchBox).toHaveValue(term);
	return offered;
}

// delete an entry with Delete selected, in a list that shows it
async function deleteFromList(page, sid, entryId) {
	await showInList(page, sid, entryId);
	await page.locator(`#delete_${entryId}`).check();
	page.once('dialog', dialog => dialog.accept()); // "You are about to delete the selected entries"
	await page.locator('#formulize_deleteButton').click();
	await expect(page.locator(`#delete_${entryId}`)).toHaveCount(0);
}

async function openEntry(page, sid, entryId) {
	await page.goto(`/modules/formulize/index.php?sid=${sid}${entryId === 'new' ? '' : `&ve=${entryId}`}`);
}

// the entry the form is showing, which a new entry gets once it's saved
async function renderedEntryId(page, fid) {
	const field = page.locator(`input[name="form_${fid}_rendered_entry[]"]`).first();
	await expect(field).toHaveValue(/^[1-9][0-9]*$/);
	return parseInt(await field.inputValue(), 10);
}

async function addOtherOption(page, element) {
	await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=${element.id}`);
	await page.getByRole('link', { name: 'Options' }).click();
	const optionBoxes = page.locator('input[name^="ele_value["]');
	const count = await optionBoxes.count();
	await page.getByRole('button', { name: 'Add' }).click();
	await page.locator(`input[name="ele_value[${count}]"]`).fill(OTHER_OPTION);
	await saveAdminForm(page);
	await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=${element.id}`);
	await page.getByRole('link', { name: 'Options' }).click();
	await expect(page.locator(`input[name^="ele_value["][value="${OTHER_OPTION}"]`)).toHaveCount(1);
}

async function removeOtherOption(page, element) {
	await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=${element.id}`);
	await page.getByRole('link', { name: 'Options' }).click();
	await page.locator(`input[name^="ele_value["][value="${OTHER_OPTION}"]`).fill(''); // a box with no text is left out when saving
	await saveAdminForm(page);
	expect(hasOtherOption(element)).toBe(false);
}

async function setAIAssistant(page, on) {
	await page.goto('/modules/formulize/admin/ui.php?page=settings&view=ai');
	await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
	const choice = page.locator(`input[name="formulizeAIAssistantEnabled"][value="${on ? 1 : 0}"]`);
	await choice.check();
	await page.getByRole('button', { name: 'Save your changes' }).click();
	await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
	await page.goto('/modules/formulize/admin/ui.php?page=settings&view=ai');
	await expect(choice).toBeChecked();
}

// call an MCP tool the way the embedded assistant does, with the admin's session
async function callTool(page, name, args) {
	const response = await page.request.post('/mcp/index.php/mcp', {
		headers: { 'Content-Type': 'application/json' },
		data: { jsonrpc: '2.0', id: 1, method: 'tools/call', params: { name, arguments: args } },
	});
	const body = await response.json();
	return body.error ? { error: body.error } : { result: JSON.parse(body.result.content[0].text) };
}

test.beforeAll(() => {
	audience = elementByHandle('collections_suitable_audience');
	donorType = elementByHandle('donors_type_of_donor');
	collectionFormSid = screenOfType(audience.fid, 'multiPage');
	collectionListSid = screenOfType(audience.fid, 'listOfEntries');
	donorFormSid = screenOfType(donorType.fid, 'multiPage');
	donorListSid = screenOfType(donorType.fid, 'listOfEntries');
});

test.describe('Text typed into Other boxes', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	});

	test('Give the elements an Other option', async ({ page }) => {
		for (const element of [audience, donorType]) {
			if (!hasOtherOption(element)) {
				await addOtherOption(page, element);
				addedOtherOptionTo.push(element);
			}
			expect(hasOtherOption(element)).toBe(true);
		}
	});

	test('A new entry saves its Other text', async ({ page }) => {
		await openEntry(page, collectionFormSid, 'new');
		await page.getByRole('textbox', { name: 'Name *' }).fill(COLLECTION_NAME);
		await page.getByRole('checkbox', { name: 'Adults' }).check();
		await otherOption(page, audience, 'new').check();
		await otherBox(page, audience, 'new').fill('Seniors groups');
		await saveFormulizeForm(page);
		collectionId = await renderedEntryId(page, audience.fid);
		await leavePage(page);

		await openEntry(page, collectionFormSid, collectionId);
		await expect(otherOption(page, audience, collectionId)).toBeChecked();
		await expect(otherBox(page, audience, collectionId)).toHaveValue('Seniors groups');
		await leavePage(page);
		expect(storedOtherText(audience, collectionId)).toEqual(['Seniors groups']);
	});

	test('Changing only the Other text saves it, and counts as a change to the entry', async ({ page }) => {
		const modifiedBefore = modifiedAt('collections', collectionId);
		await page.waitForTimeout(1100); // the modification time is kept to the second, so let one pass

		await openEntry(page, collectionFormSid, collectionId);
		await otherBox(page, audience, collectionId).fill('Seniors groups and schools');
		await saveFormulizeForm(page);
		await leavePage(page);

		await openEntry(page, collectionFormSid, collectionId);
		await expect(otherBox(page, audience, collectionId)).toHaveValue('Seniors groups and schools');
		await leavePage(page);
		expect(storedOtherText(audience, collectionId)).toEqual(['Seniors groups and schools']);
		expect(modifiedAt('collections', collectionId)).not.toEqual(modifiedBefore);
	});

	test('A radio button entry saves its Other text, and changes to it alone', async ({ page }) => {
		await openEntry(page, donorFormSid, 'new');
		await otherOption(page, donorType, 'new').check(); // saving waits for the conditional elements this changes
		await otherBox(page, donorType, 'new').fill('Friends of the museum');
		await saveFormulizeForm(page);
		donorId = await renderedEntryId(page, donorType.fid);
		await leavePage(page);
		expect(storedOtherText(donorType, donorId)).toEqual(['Friends of the museum']);

		await openEntry(page, donorFormSid, donorId);
		await expect(otherOption(page, donorType, donorId)).toBeChecked();
		await otherBox(page, donorType, donorId).fill('Friends of the museum society');
		await saveFormulizeForm(page);
		await leavePage(page);

		await openEntry(page, donorFormSid, donorId);
		await expect(otherBox(page, donorType, donorId)).toHaveValue('Friends of the museum society');
		await leavePage(page);
		expect(storedOtherText(donorType, donorId)).toEqual(['Friends of the museum society']);
	});

	test('A clone keeps the Other text', async ({ page }) => {
		const before = collectionIds();
		await showInList(page, collectionListSid, collectionId);
		page.on('dialog', dialog => dialog.accept('1')); // how many copies, when the list shows only this form
		await page.locator(`#delete_${collectionId}`).check();
		await page.getByRole('button', { name: 'Clone selected' }).click();
		// a list showing more than one form asks which forms to clone, with this one already chosen
		const chooseForms = page.locator('#formulize_clone_dialog_overlay');
		await chooseForms.or(page.locator(`#delete_${collectionId}`)).first().waitFor();
		if (await chooseForms.isVisible()) {
			await Promise.all([
				page.waitForEvent('load'),
				chooseForms.getByRole('button', { name: 'Clone', exact: true }).click(),
			]);
		}
		await expect.poll(() => collectionIds().length).toBe(before.length + 1);
		cloneId = collectionIds().find(entryId => !before.includes(entryId));

		await openEntry(page, collectionFormSid, cloneId);
		await expect(otherOption(page, audience, cloneId)).toBeChecked();
		await expect(otherBox(page, audience, cloneId)).toHaveValue('Seniors groups and schools');
		await leavePage(page);
		expect(storedOtherText(audience, cloneId)).toEqual(['Seniors groups and schools']);
	});

	test('Choosing something else removes the Other text', async ({ page }) => {
		// the column's Search box offers the text as one of its values, and a search for it finds the entry and its clone
		const offered = await searchSuitableFor(page, 'groups and schools');
		expect(offered).toContain('Seniors groups and schools');
		expect(offered).toContain('Adults');
		await expect(page.locator(`#delete_${collectionId}`)).toHaveCount(1);
		await expect(page.locator(`#delete_${cloneId}`)).toHaveCount(1);

		await openEntry(page, collectionFormSid, collectionId);
		await otherOption(page, audience, collectionId).uncheck();
		await saveFormulizeForm(page);
		await leavePage(page);
		expect(storedOtherText(audience, collectionId)).toEqual([]);

		// now it finds only the clone, which still has the text
		await searchSuitableFor(page, 'groups and schools');
		await expect(page.locator(`#delete_${cloneId}`)).toHaveCount(1);
		await expect(page.locator(`#delete_${collectionId}`)).toHaveCount(0);

		// choosing Other again starts with an empty box
		await openEntry(page, collectionFormSid, collectionId);
		await expect(otherOption(page, audience, collectionId)).not.toBeChecked();
		await expect(otherBox(page, audience, collectionId)).toHaveValue('');
		await leavePage(page);
	});

	test('MCP update_entries saves a value that is not an option as the Other text', async ({ page }) => {
		await page.goto('/modules/formulize/admin/ui.php?page=settings&view=ai');
		await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
		aiAssistantWasOn = await page.locator('input[name="formulizeAIAssistantEnabled"][value="1"]').isChecked();
		if (!aiAssistantWasOn) {
			await setAIAssistant(page, true);
		}

		const updated = await callTool(page, 'update_entries', {
			form_id: audience.fid,
			data: [{ entry_id: collectionId, collections_suitable_audience: ['Adults', 'Evening visitors'] }],
		});
		expect(updated.error).toBeUndefined();
		const refused = await callTool(page, 'update_entries', {
			form_id: audience.fid,
			data: [{ entry_id: collectionId, collections_suitable_audience: ['Evening visitors', 'Night owls'] }],
		});
		expect(refused.error.type).toBe('invalid_data'); // one Other option, so only one value that isn't an option

		await openEntry(page, collectionFormSid, collectionId);
		await expect(page.getByRole('checkbox', { name: 'Adults' })).toBeChecked();
		await expect(otherOption(page, audience, collectionId)).toBeChecked();
		await expect(otherBox(page, audience, collectionId)).toHaveValue('Evening visitors');
		await leavePage(page);
		expect(storedOtherText(audience, collectionId)).toEqual(['Evening visitors']);
	});
});

// Put the museum back: delete the entries these tests made, the way the person who made them would, take away the
// Other options they added, and put the AI assistant setting back. In afterAll, so it runs even when a test fails.
test.afterAll(async ({ browser }) => {
	const page = await browser.newPage();
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);

	for (const entryId of collectionIds()) {
		await deleteFromList(page, collectionListSid, entryId);
	}
	if (donorId) {
		await deleteFromList(page, donorListSid, donorId);
	}
	expect(collectionIds()).toEqual([]);
	if (donorId) {
		expect(value(`SELECT entry_id FROM ${dbPrefix()}_formulize_donors WHERE entry_id = ${donorId}`)).toBeNull();
		expect(storedOtherText(donorType, donorId)).toEqual([]);
	}

	for (const element of addedOtherOptionTo) {
		await removeOtherOption(page, element);
	}
	if (aiAssistantWasOn === false) {
		await setAIAssistant(page, false);
	}
	await page.close();
});
