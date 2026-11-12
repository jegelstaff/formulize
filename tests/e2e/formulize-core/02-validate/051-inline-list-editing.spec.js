const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, addElementForm, ElementType, applyColumnChanges,
	createMuseumForm, deleteMuseumFormsTitled, leavePage, dbQuery, dbPrefix, waitForWorkingMessage, entryIsLocked,
	entriesThePageWillRelease } from '../../utils';

// A list screen can show a column as a form element, so a person edits a value right in the list: they click the
// pen beside the value, change it, and click the tick. Clicking the pen asks formulize_xhr_responder.php for the
// element (get_element_html), and the element comes back with a token for saving it on its own. That token is only
// given to a page that already holds a token of its own, so the list sends its token along, in a header.
//
// So this turns on inline editing for two columns in the admin, edits values in the list the way a person would, and
// checks the changes were saved. It checks that the same request, sent without the list's token, or with a wrong
// one, gets no element and no token. And it checks that a number edited in the list still lines up with the other
// numbers in its column, which are all drawn in boxes of the same width so their right edges meet, including when the
// new number is wider than the rest. Everything is made through the admin UI on a form of its own, which is deleted
// at the end. The database is only read, to find the form's list screen.
//
// An element opened from its pen locks its entry, like any editable element, so no one else edits it at the same
// time. The lock is for the list's page load, so it goes when the element is saved or closed, when the list submits
// to itself, as for a sort, and when the list goes away. Whether an entry is locked is read from the files on the
// server that hold the locks.

test.describe.configure({ mode: 'serial' });

const FORM_TITLE = 'Inline list editing';
const NOTE = 'inline_note';
const AMOUNT = 'inline_amount';
const BEFORE = 'Before051';
const AFTER = 'After051';

let fid = 0;
let listSid = 0;

test.afterAll(async ({ browser }) => {
	const page = await browser.newPage();
	await login(page, 'admin');
	await deleteMuseumFormsTitled(page, [FORM_TITLE]);
	await page.close();
});

const listUrl = () => `/modules/formulize/index.php?fid=${fid}`;
const interfaceTab = () => `/modules/formulize/admin/ui.php?page=screen&fid=${fid}&sid=${listSid}&tab=interface`;
const cellsOf = (page, handle) => page.locator(`[id^="deDiv_${handle}_"]`);

// The list, with columns added through the "Change columns" popup, since a new form doesn't show them
async function openListWithColumns(page, captions) {
	await page.goto(listUrl());
	const popupPromise = page.context().waitForEvent('page');
	await page.getByRole('button', { name: 'Change columns' }).click();
	const popup = await popupPromise;
	await popup.bringToFront();
	for (const caption of captions) {
		await popup.getByRole('checkbox', { name: caption }).check();
	}
	await applyColumnChanges(popup);
	await expect(page.locator('[id^="deDiv_"]').first()).toBeVisible();
}

// Click the pen in a cell, type a new value, click the tick, and wait for the cell to show the saved value, which
// is how the list shows it: a number gets its thousands separators
async function editInList(cell, value, shown = value) {
	await cell.locator('a.de-edit-icon').click();
	const box = cell.locator(`input[name^="de_${fid}_"]`);
	await expect(box).toBeVisible();
	await box.fill(value);
	await cell.locator('a.formulize-de-save').click();
	await expect(cell).not.toHaveClass(/formulize-de-editing/);
	await expect(cell).toContainText(shown);
}

// Where the right edge of each number in a column is on the page
function numberRightEdges(page, handle) {
	return cellsOf(page, handle).locator('span.formulize-numeric').evaluateAll(spans =>
		spans.map(span => Math.round(span.getBoundingClientRect().right)));
}

test('turn on inline editing for two columns of a list', async ({ page }) => {
	test.setTimeout(300000); // a form, two elements and the list screen's settings, each its own admin save
	await login(page, 'admin');
	await deleteMuseumFormsTitled(page, [FORM_TITLE]);
	fid = await createMuseumForm(page, FORM_TITLE, 'Name');
	expect(fid).toBeGreaterThan(0);

	for (const [type, caption, handle] of [[ElementType.text, 'Note', NOTE], [ElementType.textNumber, 'Amount', AMOUNT]]) {
		await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${fid}&tab=elements`);
		await waitForAdminPageReady(page);
		await addElementForm(page, type);
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill(caption);
		await page.locator('input[name="elements-ele_handle"]').fill(handle);
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText(caption);
	}

	// read only, to know which list screen the form was given
	for (const [sid, type] of dbQuery(`SELECT sid, type FROM ${dbPrefix()}_formulize_screen WHERE fid = ${fid}`)) {
		if (type === 'listOfEntries') listSid = parseInt(sid, 10);
	}
	expect(listSid, 'the new form has a list screen').toBeGreaterThan(0);

	// show both as form elements, opened by clicking a pen beside the value
	await page.goto(interfaceTab());
	await waitForAdminPageReady(page);
	await page.locator('select[name="screens-decolumns[]"]').selectOption([NOTE, AMOUNT]);
	await page.locator('input[name="screens-dedisplay"][value="1"]').check();
	await saveAdminForm(page);

	await page.goto(interfaceTab());
	await waitForAdminPageReady(page);
	for (const handle of [NOTE, AMOUNT]) {
		await expect(page.locator(`select[name="screens-decolumns[]"] option[value="${handle}"]`)).toHaveJSProperty('selected', true);
	}
	await expect(page.locator('input[name="screens-dedisplay"][value="1"]')).toBeChecked();
});

test('make two entries to edit', async ({ page }) => {
	await login(page, 'admin');
	for (const [name, note, amount] of [['Entry051', BEFORE, '250'], ['Entry051b', 'Other051', '5']]) {
		await page.goto(listUrl());
		await page.getByRole('button', { name: `Add ${FORM_TITLE}`, exact: true }).click();
		await page.getByRole('textbox', { name: /^Name/ }).fill(name);
		await page.getByLabel('Note').fill(note);
		await page.getByLabel('Amount').fill(amount);
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // clears the entry lock
		await expect(page.getByText(name, { exact: true })).toBeVisible();
	}
});

test('edit a value in the list, and the change is saved', async ({ page }) => {
	const dialogs = [];
	page.on('dialog', async dialog => {
		dialogs.push(dialog.message()); // a refused save is reported in an alert
		await dialog.dismiss();
	});

	await login(page, 'admin');
	await openListWithColumns(page, ['Note']);
	const found = cellsOf(page, NOTE).filter({ hasText: BEFORE });
	await expect(found).toHaveCount(1);
	// found by its value, which is replaced by a box once the pen is clicked, so hold on to it by its id
	const cell = page.locator(`#${await found.getAttribute('id')}`);

	// click the pen, and see what the list asked for and what came back
	const [elementRequest, elementResponse] = await Promise.all([
		page.waitForRequest(/formulize_xhr_responder\.php\?.*op=get_element_html/),
		page.waitForResponse(/formulize_xhr_responder\.php\?.*op=get_element_html/),
		cell.locator('a.de-edit-icon').click(),
	]);
	const sentHeaders = elementRequest.headers();
	expect(sentHeaders['x-formulize-token'], 'the list sends its token').toBeTruthy();
	expect(sentHeaders['x-requested-with']).toBe('XMLHttpRequest');
	expect(await elementResponse.text(), 'the element comes back with a token for saving it').toContain('detoken_');

	const box = cell.locator(`input[name^="de_${fid}_"]`);
	await expect(box).toHaveValue(BEFORE);
	await box.fill(AFTER);
	await cell.locator('a.formulize-de-save').click();

	// once saved, the cell shows the value read back from the server instead of the element
	await expect(cell).not.toHaveClass(/formulize-de-editing/);
	await expect(cell).toContainText(AFTER);
	expect(dialogs, 'no save was refused').toEqual([]);

	// the same request without the list's token, or with a wrong one, gets no element and no token
	const refused = await page.evaluate(async url => {
		const ask = async headers => (await fetch(url, { headers, credentials: 'same-origin' })).text();
		return {
			withoutToken: await ask({ 'X-Requested-With': 'XMLHttpRequest' }),
			wrongToken: await ask({ 'X-Requested-With': 'XMLHttpRequest', 'X-Formulize-Token': 'not-a-token' }),
		};
	}, elementRequest.url());
	expect(refused.withoutToken).not.toContain('detoken_');
	expect(refused.withoutToken).not.toContain(`de_${fid}_`);
	expect(refused.wrongToken).not.toContain('detoken_');
	expect(refused.wrongToken).not.toContain(`de_${fid}_`);

	// and the change is still there when the list is drawn again
	await leavePage(page, '/modules/formulize/index.php');
	await openListWithColumns(page, ['Note']);
	await expect(cellsOf(page, NOTE).filter({ hasText: AFTER })).toHaveCount(1);
	await leavePage(page);
});

test('a number edited in the list lines up with the rest of its column', async ({ page }) => {
	const dialogs = [];
	page.on('dialog', async dialog => {
		dialogs.push(dialog.message());
		await dialog.dismiss();
	});

	await login(page, 'admin');
	await openListWithColumns(page, ['Amount']);
	const smaller = cellsOf(page, AMOUNT).filter({ hasText: /^\s*5\s*$/ });
	await expect(smaller).toHaveCount(1);
	const cell = page.locator(`#${await smaller.getAttribute('id')}`);

	// as the list is drawn, 250 and 5 end at the same place
	let edges = await numberRightEdges(page, AMOUNT);
	expect(edges).toHaveLength(2);
	expect(new Set(edges).size, `right edges as drawn: ${edges}`).toBe(1);

	// still after one of them is edited
	await editInList(cell, '7');
	edges = await numberRightEdges(page, AMOUNT);
	expect(edges).toHaveLength(2);
	expect(new Set(edges).size, `right edges after an edit: ${edges}`).toBe(1);

	// and after an edit that makes it wider than the other number, which moves over to line up with it
	await editInList(cell, '12345', '12,345');
	edges = await numberRightEdges(page, AMOUNT);
	expect(edges).toHaveLength(2);
	expect(new Set(edges).size, `right edges after a wider number: ${edges}`).toBe(1);

	expect(dialogs, 'no save was refused').toEqual([]);
	await leavePage(page);
});

test('an element opened from its pen locks its entry until it is saved or closed, or the list moves on', async ({ page }) => {
	const dialogs = [];
	page.on('dialog', async dialog => {
		dialogs.push(dialog.message());
		await dialog.dismiss();
	});

	await login(page, 'admin');
	await openListWithColumns(page, ['Note']);
	const found = cellsOf(page, NOTE).filter({ hasText: AFTER });
	await expect(found).toHaveCount(1);
	const entry = parseInt(new RegExp(`^deDiv_${NOTE}_(\\d+)_\\d+$`).exec(await found.getAttribute('id'))[1], 10);
	// by its entry, since the number at the end of its id counts the cells as they're drawn, which a sort changes
	const cell = () => page.locator(`[id^="deDiv_${NOTE}_${entry}_"]`);
	const locked = () => expect.poll(() => entryIsLocked(fid, entry), { message: `entry ${entry} is locked` });

	// open it from its pen, and the entry is locked, for the list: the list's release will unlock it
	const open = async () => {
		await cell().locator('a.de-edit-icon').click();
		await expect(cell().locator(`input[name^="de_${fid}_"]`)).toBeVisible();
		await locked().toBe(true);
		expect(await entriesThePageWillRelease(page)).toContainEqual([fid, entry]);
	};

	await expect(cell()).toBeVisible();
	expect(entryIsLocked(fid, entry), 'not locked before the pen is clicked').toBe(false);

	// 1. closed with the X
	await open();
	await cell().locator('a.formulize-de-cancel').click();
	await expect(cell()).not.toHaveClass(/formulize-de-editing/);
	await locked().toBe(false);
	expect(await entriesThePageWillRelease(page)).not.toContainEqual([fid, entry]);

	// 2. saved with the tick
	await open();
	await cell().locator(`input[name^="de_${fid}_"]`).fill(AFTER);
	await cell().locator('a.formulize-de-save').click();
	await expect(cell()).not.toHaveClass(/formulize-de-editing/);
	await expect(cell()).toContainText(AFTER);
	await locked().toBe(false);

	// 3. the list submits to itself, for a sort
	await open();
	await page.getByRole('columnheader', { name: /Note/ }).getByRole('link').first().click();
	await waitForWorkingMessage(page);
	await locked().toBe(false);

	// 4. the list goes away. Its release can be lost on the way, in CI, and leavePage notes that on the test, so the
	// lock is only checked when the release arrived
	await expect(cell()).toBeVisible();
	await open();
	await leavePage(page, '/modules/formulize/index.php');
	if (!test.info().annotations.some(note => note.type === 'entry locks not released')) {
		expect(entryIsLocked(fid, entry), 'the release unlocked it').toBe(false);
	}

	expect(dialogs, 'no save was refused').toEqual([]);
});

test('delete the form', async ({ page }) => {
	await login(page, 'admin');
	await deleteMuseumFormsTitled(page, [FORM_TITLE]);
	await page.goto('/modules/formulize/admin/ui.php?page=home');
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	await expect(page.getByText(FORM_TITLE, { exact: true })).toHaveCount(0);
});
