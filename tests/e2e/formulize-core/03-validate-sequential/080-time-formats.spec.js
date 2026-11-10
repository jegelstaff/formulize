const { test, expect } = require('@playwright/test');
import {
	login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, waitForWorkingMessage, applyColumnChanges, createMuseumForm, deleteMuseumFormsTitled,
	leavePage, elementRow, dbQuery, dbPrefix, sqlEscape,
} from '../../utils';
import { museumAppId } from '../01-setup/museum-forms';

// The formats time elements show times in. See issue 893.
//
// The Settings page has a 12-hour time format and a 24-hour time format, under Date & time formats. A time element shows times
// in one or the other, as its Display Format says. Whatever the formats are, a time has to read the same everywhere it's
// shown, and come back to the same time wherever it's read:
//   - lists show it in the element's format, and a time element that can't be changed shows it the same way on the form
//   - a search typed the way the list shows it finds it
//   - a condition on the time, which is checked against the entry when its form is drawn, compares it as a time, so 9:05 in
//     the morning is before 12:00 however it's written
// The checks run three times: with the formats a new site has, with formats a site might choose, one of which strtotime
// can't read, and with the element set to 24-hour.
//
// Everything is made through the admin UI on a form of its own, which is deleted at the end. The formats are site settings,
// so this is in 03-validate-sequential, and it puts them back in the last test and again in afterAll. The database is only
// read, to find the ids of what the spec made.

test.describe.configure({ mode: 'serial' });

const FORM_TITLE = 'Time Formats Test';
const STARTS_AT = 'timeformats_starts_at';
const NOTE = 'timeformats_afternoon_note';
const MORNING = 'Morning talk';
const AFTERNOON = 'Afternoon tour';

let fid = 0;
let entryIds = {}; // by name
let formatsBefore = null; // what the site's formats were, to put back

const listUrl = () => `/modules/formulize/index.php?fid=${fid}`;
const entryUrl = name => `/modules/formulize/index.php?fid=${fid}&ve=${entryIds[name]}`;
const startsAtElementUrl = () => {
	const [eleId] = dbQuery(`SELECT ele_id FROM ${dbPrefix()}_formulize WHERE ele_handle = '${sqlEscape(STARTS_AT)}'`)[0];
	return `/modules/formulize/admin/ui.php?page=element&ele_id=${eleId}&fid=${fid}&aid=${museumAppId(fid)}`;
};
const formatBox = (page, name) => page.locator(`[data-conf-name="${name}"]`).getByRole('textbox');
const formatPreview = (page, name) => page.locator(`.formulize-config-preview[data-preview-for="${name}"]`);

async function openSystemSettings(page) {
	await page.goto('/modules/formulize/admin/ui.php?page=settings&view=system');
	await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
}

async function currentFormats(page) {
	await openSystemSettings(page);
	return { twelve: await formatBox(page, 'timeformat12h').inputValue(), twentyFour: await formatBox(page, 'timeformat24h').inputValue() };
}

// The formats have to be saved by the Settings page, which is what writes the file they're read from
async function setFormats(page, { twelve, twentyFour }) {
	await openSystemSettings(page);
	await formatBox(page, 'timeformat12h').fill(twelve);
	await formatBox(page, 'timeformat24h').fill(twentyFour);
	await page.getByRole('button', { name: 'Save your changes' }).click();
	await expect(async () => {
		await openSystemSettings(page);
		await expect(formatBox(page, 'timeformat12h')).toHaveValue(twelve, { timeout: 5000 });
		await expect(formatBox(page, 'timeformat24h')).toHaveValue(twentyFour, { timeout: 5000 });
	}).toPass({ timeout: 60000 });
}

// The element's Display Format, on its Options tab, where each choice shows an example time in the site's format
async function setDisplayFormat(page, label) {
	await page.goto(startsAtElementUrl());
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Options' }).click();
	const choice = page.getByRole('radio', { name: label, exact: true });
	await expect(choice).toBeVisible();
	await choice.check();
	await saveAdminForm(page);
	await page.goto(startsAtElementUrl());
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Options' }).click();
	await expect(page.getByRole('radio', { name: label, exact: true })).toBeChecked();
}

// The list, with the event and its time as columns, since a new form's list doesn't show them
async function openList(page) {
	await page.goto(listUrl());
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
	const popupPromise = page.context().waitForEvent('page');
	await page.getByRole('button', { name: 'Change columns' }).click();
	const popup = await popupPromise;
	await popup.bringToFront();
	await popup.getByRole('checkbox', { name: 'Event', exact: true }).check();
	await popup.getByRole('checkbox', { name: 'Starts at', exact: true }).check();
	await applyColumnChanges(popup);
	await waitForWorkingMessage(page);
	await expect(page.locator(`input[name="search_${STARTS_AT}"]`)).toBeVisible();
}

const timeCell = (page, time) => page.locator(`[id^="celladdress_"]`).filter({ hasText: new RegExp(`^\\s*${time.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\s*$`) });

// The list shows each time in the element's format. The formats are read from a file the Settings page writes, so the first
// list after a change can be drawn before PHP picks it up.
async function expectListToShow(page, morning, afternoon) {
	await expect(async () => {
		await openList(page);
		await expect(timeCell(page, morning)).toHaveCount(1, { timeout: 5000 });
		await expect(timeCell(page, afternoon)).toHaveCount(1, { timeout: 5000 });
	}).toPass({ timeout: 60000 });
}

// A search typed the way the list shows the time finds that entry, and only that one
async function expectSearchToFind(page, term, name) {
	await openList(page);
	await page.locator(`input[name="search_${STARTS_AT}"]`).fill(term);
	await page.locator(`input[name="search_${STARTS_AT}"]`).press('Enter');
	await waitForWorkingMessage(page);
	await expect(page.getByText('Showing entries: 1 to 1 of 1.').first()).toBeVisible();
	await expect(page.locator('[id^="celladdress_"]').filter({ hasText: name })).toHaveCount(1);
	await expect(page.locator(`input[name="search_${STARTS_AT}"]`)).toHaveValue(term);
}

// Starts at can't be changed, so the form shows the time as text, in the element's format. Afternoon note is only shown when
// the time is after 12:00, which is checked against the entry as the form is drawn.
async function expectEntryToShow(page, name, time, noteShown) {
	await page.goto(entryUrl(name));
	await expect(page.getByRole('textbox', { name: /^Event/ })).toHaveValue(name);
	await expect(elementRow(page, STARTS_AT)).toContainText(time);
	if (noteShown) {
		await expect(page.getByRole('textbox', { name: 'Afternoon note' })).toBeVisible();
	} else {
		await expect(page.getByRole('textbox', { name: 'Afternoon note' })).not.toBeVisible();
	}
	await leavePage(page);
	await expect(page.locator('body')).toBeVisible();
}

test.beforeAll(async ({ browser }) => {
	// a run that was interrupted could have left the form behind
	const page = await browser.newPage();
	await login(page);
	await deleteMuseumFormsTitled(page, [FORM_TITLE]);
	await page.close();
});

test.afterAll(async ({ browser }) => {
	const page = await browser.newPage();
	await login(page);
	if (formatsBefore) {
		const formatsNow = await currentFormats(page);
		if (formatsNow.twelve !== formatsBefore.twelve || formatsNow.twentyFour !== formatsBefore.twentyFour) {
			await setFormats(page, formatsBefore);
		}
	}
	await deleteMuseumFormsTitled(page, [FORM_TITLE]);
	await page.close();
});

test('make a form with a time, and a note shown only for times after 12:00', async ({ page }) => {
	test.setTimeout(300000); // a form, two elements and a change to one of them, each its own admin save
	await login(page);
	formatsBefore = await currentFormats(page);
	fid = await createMuseumForm(page, FORM_TITLE, 'Event');
	expect(fid).toBeGreaterThan(0);
	const aid = museumAppId(fid);

	// typed into in lists, so the spec can search for a time the way the list shows it
	await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${fid}&aid=${aid}&type=time`);
	await waitForAdminPageReady(page);
	await page.locator('input[name="elements-ele_caption"]').fill('Starts at');
	await page.locator('input[name="elements-ele_handle"]').fill(STARTS_AT);
	await page.locator('select[name="elements-ele_searchtype"]').selectOption({ label: 'Search box - type only' });
	await saveAdminForm(page);
	await expect(page.getByRole('heading')).toContainText('Starts at');

	await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${fid}&aid=${aid}&type=text`);
	await waitForAdminPageReady(page);
	await page.locator('input[name="elements-ele_caption"]').fill('Afternoon note');
	await page.locator('input[name="elements-ele_handle"]').fill(NOTE);
	await page.getByRole('link', { name: 'Display Settings' }).click();
	await page.locator('#new_elementfilter_element').selectOption('Starts at');
	await page.locator('#new_elementfilter_op').selectOption('>');
	await page.locator('#new_elementfilter_term').fill('12:00');
	await saveAdminForm(page);
	await expect(page.getByRole('heading')).toContainText('Afternoon note');
	await page.getByRole('link', { name: 'Display Settings' }).click();
	await expect(page.locator('a.conditionsdelete[target^="elementfilter_"]')).toHaveCount(1);
	await expect(page.locator('body')).toContainText('Starts at > 12:00');
});

test('add a morning and an afternoon event, then make the time one that can\'t be changed', async ({ page }) => {
	test.setTimeout(180000);
	await login(page);
	for (const [name, time] of [[MORNING, '09:05'], [AFTERNOON, '13:30']]) {
		await page.goto(listUrl());
		await page.getByRole('button', { name: `Add ${FORM_TITLE}`, exact: true }).click();
		await page.getByRole('textbox', { name: /^Event/ }).fill(name);
		await page.getByLabel('Starts at').fill(time);
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // back to the list, which clears the entry lock
		await expect(page.getByText(name, { exact: true }).first()).toBeVisible();
	}

	// read only, to know the entries' ids
	const [[formHandle]] = dbQuery(`SELECT form_handle FROM ${dbPrefix()}_formulize_id WHERE id_form = ${fid}`);
	for (const [entryId, time] of dbQuery(`SELECT entry_id, \`${STARTS_AT}\` FROM ${dbPrefix()}_formulize_${formHandle}`)) {
		entryIds[time === '09:05:00' ? MORNING : AFTERNOON] = parseInt(entryId, 10);
	}
	expect(Object.keys(entryIds).sort()).toEqual([AFTERNOON, MORNING].sort());

	await page.goto(startsAtElementUrl());
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Display Settings' }).click();
	await page.locator('select[name="elements_ele_disabled\\[\\]"]').selectOption('all');
	await saveAdminForm(page);
	await page.goto(startsAtElementUrl());
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Display Settings' }).click();
	await expect(page.locator('select[name="elements_ele_disabled\\[\\]"] option[value="all"]')).toHaveJSProperty('selected', true);
});

test('with the formats a new site has, times read as they always have', async ({ page }) => {
	test.setTimeout(300000);
	await login(page);
	await setFormats(page, { twelve: 'g:ia', twentyFour: 'H:i' });
	await setDisplayFormat(page, '12-hour (e.g. 1:30pm)');

	await expectListToShow(page, '9:05am', '1:30pm');
	await expectSearchToFind(page, '1:30pm', AFTERNOON);
	await expectSearchToFind(page, '9:05am', MORNING);
	await expectEntryToShow(page, MORNING, '9:05am', false);
	await expectEntryToShow(page, AFTERNOON, '1:30pm', true);
});

test('with formats a site chooses, times show and read in those formats', async ({ page }) => {
	test.setTimeout(300000);
	await login(page);
	await setFormats(page, { twelve: 'g:i A', twentyFour: 'H\\hi' });

	// the Settings page shows what each format looks like
	await openSystemSettings(page);
	await expect(formatPreview(page, 'timeformat12h')).toContainText('2:05 PM');
	await expect(formatPreview(page, 'timeformat24h')).toContainText('14h05');

	// and so does the element's Display Format
	await setDisplayFormat(page, '12-hour (e.g. 1:30 PM)');
	await expect(page.getByRole('radio', { name: '24-hour (e.g. 13h30)', exact: true })).toBeVisible();

	await expectListToShow(page, '9:05 AM', '1:30 PM');
	await expectSearchToFind(page, '1:30 PM', AFTERNOON);
	await expectEntryToShow(page, MORNING, '9:05 AM', false);
	await expectEntryToShow(page, AFTERNOON, '1:30 PM', true);
});

test('with the element set to 24-hour, times show and read in the 24-hour format', async ({ page }) => {
	test.setTimeout(300000);
	await login(page);
	await setDisplayFormat(page, '24-hour (e.g. 13h30)');

	await expectListToShow(page, '09h05', '13h30');
	await expectSearchToFind(page, '13h30', AFTERNOON);
	await expectSearchToFind(page, '09:05', MORNING);
	await expectEntryToShow(page, MORNING, '09h05', false);
	await expectEntryToShow(page, AFTERNOON, '13h30', true);
});

test('put the site\'s formats back', async ({ page }) => {
	test.setTimeout(180000);
	await login(page);
	await setFormats(page, formatsBefore);
	const formatsNow = await currentFormats(page);
	expect(formatsNow).toEqual(formatsBefore);
});
