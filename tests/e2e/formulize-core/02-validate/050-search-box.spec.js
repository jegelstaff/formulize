const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, waitForAdminPageReady, waitForWorkingMessage, openAllColumnSections, applyColumnChanges, createMuseumForm, deleteMuseumFormsTitled, dbQuery, dbPrefix, sqlEscape } from '../../utils';
import { formId, museumAppId } from '../01-setup/museum-forms';

// The Search box, and the search interface each element uses in lists.
//
//  - An element's Settings tab chooses the search interface it gets in lists. Elements that hold dates default to
//    Date range, and everything else to the Search box. The date ones are only offered for elements that hold dates.
//  - Choosing a column for a list screen starts it on its element's search interface. Rows already there are left
//    as they are.
//  - A column the screen doesn't list, such as one added with Change columns, uses its element's search interface.
//  - In the Search box, what's typed is an ordinary search, submitted with Enter. Ticking values that match what's
//    typed searches for exactly those instead.
//
// The museum's data is listed at the end of formulize-core/conditions-and-searches.md.

// One worker, in order: the second test makes a form that afterAll deletes
test.describe.configure({ mode: 'serial' });

const FORM_TITLE = 'Search Box Test';

function elementUrl(handle) {
	const [eleId, fid] = dbQuery(`SELECT ele_id, id_form FROM ${dbPrefix()}_formulize WHERE ele_handle = '${sqlEscape(handle)}'`)[0];
	return `/modules/formulize/admin/ui.php?page=element&ele_id=${eleId}&fid=${fid}&aid=${museumAppId(fid)}`;
}

// the Artifacts list screen, which curator1 lands on
function artifactsListScreenUrl() {
	const fid = formId('Artifacts');
	const sid = dbQuery(`SELECT sid FROM ${dbPrefix()}_formulize_screen WHERE fid = ${fid} AND type = 'listOfEntries' AND title = 'Artifacts'`)[0][0];
	return `/modules/formulize/admin/ui.php?page=screen&aid=${museumAppId(fid)}&fid=${fid}&sid=${sid}`;
}

const searchTypeSetting = page => page.locator('select[name="elements-ele_searchtype"]');

async function offeredSearchTypes(page) {
	return (await searchTypeSetting(page).locator('option').allTextContents()).map(text => text.trim());
}

// Log in as curator1, whose Artifacts list doesn't list Era or Donor, and add them with Change columns
async function artifactsListWithEraAndDonor(page) {
	await login(page, 'curator1', '12345');
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
	const popupPromise = page.context().waitForEvent('page');
	await page.getByRole('button', { name: 'Change columns' }).click();
	const popup = await popupPromise;
	await openAllColumnSections(popup);
	await popup.getByRole('checkbox', { name: 'Era', exact: true }).check();
	await popup.getByRole('checkbox', { name: 'Donor', exact: true }).check();
	await applyColumnChanges(popup);
	await waitForWorkingMessage(page);
	await expect(page.locator('input[name="search_artifacts_donor"]')).toBeVisible();
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
	await deleteMuseumFormsTitled(page, [FORM_TITLE]);
	await page.close();
});

test.describe('The search interface of an element', () => {

	test('Elements offer the search interfaces that suit them, defaulting to the Search box or a date range', async ({ page }) => {
		const allTypes = ['Search box', 'Simple text box', 'Dropdown list - select value', 'Dropdown list - exclude value', 'Date range', 'Month selection - current year', 'Month selection - last 12 months'];
		await login(page);
		await page.goto(elementUrl('artifacts_short_name'));
		await waitForAdminPageReady(page);
		await expect(searchTypeSetting(page).locator('option:checked')).toHaveText('Search box');
		expect(await offeredSearchTypes(page)).toEqual(allTypes.slice(0, 4));

		// a date element no one has chosen a search for
		await page.goto(elementUrl('loans_start_date'));
		await waitForAdminPageReady(page);
		await expect(searchTypeSetting(page).locator('option:checked')).toHaveText('Date range');
		expect(await offeredSearchTypes(page)).toEqual(allTypes);

		// a date element set to be typed into (see 01-setup/010), which still offers the date searches
		await page.goto(elementUrl('artifacts_date_of_acquisition'));
		await waitForAdminPageReady(page);
		await expect(searchTypeSetting(page).locator('option:checked')).toHaveText('Simple text box');
		expect(await offeredSearchTypes(page)).toEqual(allTypes);
	});

	test('A new element can choose its search interface, and keeps the choice', async ({ page }) => {
		await login(page);
		const fid = await createMuseumForm(page, FORM_TITLE);
		const aid = museumAppId(fid);

		// a new date element starts on Date range, and can choose a month selection
		await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${fid}&aid=${aid}&type=date`);
		await waitForAdminPageReady(page);
		await expect(searchTypeSetting(page).locator('option:checked')).toHaveText('Date range');
		await page.locator('input[name="elements-ele_caption"]').fill('Visited on');
		await searchTypeSetting(page).selectOption({ label: 'Month selection - last 12 months' });
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Visited on');
		const dateElementUrl = page.url();

		// a new text box starts on the Search box, and can choose the simple text box
		await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${fid}&aid=${aid}&type=text`);
		await waitForAdminPageReady(page);
		await expect(searchTypeSetting(page).locator('option:checked')).toHaveText('Search box');
		await page.locator('input[name="elements-ele_caption"]').fill('Visitor name');
		await searchTypeSetting(page).selectOption({ label: 'Simple text box' });
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Visitor name');
		const textElementUrl = page.url();

		await page.goto(dateElementUrl);
		await waitForAdminPageReady(page);
		await expect(searchTypeSetting(page).locator('option:checked')).toHaveText('Month selection - last 12 months');
		await page.goto(textElementUrl);
		await waitForAdminPageReady(page);
		await expect(searchTypeSetting(page).locator('option:checked')).toHaveText('Simple text box');
	});
});

test.describe('Columns on a list screen', () => {

	test('Choosing a column starts it on its element\'s search interface, and leaves the other rows alone', async ({ page }) => {
		await login(page);
		await page.goto(artifactsListScreenUrl());
		await waitForAdminPageReady(page);
		await page.getByRole('link', { name: 'Entries', exact: true }).click();
		const rows = page.locator('table.advanceview tr.advanceviewcol');
		await expect(rows.first()).toBeVisible();
		const before = await rows.locator('select[name^="search-type"]').evaluateAll(selects => selects.map(select => select.value));

		await page.getByRole('button', { name: 'Add Column' }).click();
		const newRow = rows.last();
		const searchType = newRow.locator('select[name^="search-type"]');
		const dateOptions = searchType.locator('option[value="DateRange"], option[value="MonthCurrentYear"], option[value="MonthLast12Months"]');

		await newRow.locator('select[id^="cols-"]').selectOption('artifacts_short_name');
		await expect(searchType).toHaveValue('MultiFilter');
		for (const option of await dateOptions.all()) {
			await expect(option).toBeDisabled();
		}

		// a date column starts on a date range, with the date searches offered
		await newRow.locator('select[id^="cols-"]').selectOption('creation_datetime');
		await expect(searchType).toHaveValue('DateRange');
		for (const option of await dateOptions.all()) {
			await expect(option).toBeEnabled();
		}

		// an element with a search chosen for it starts on that one: Date of acquisition is set to the simple text box (see 01-setup/010)
		await newRow.locator('select[id^="cols-"]').selectOption('artifacts_date_of_acquisition');
		await expect(searchType).toHaveValue('Box');
		for (const option of await dateOptions.all()) {
			await expect(option).toBeEnabled();
		}

		// the rows the screen had are as they were
		const after = await rows.locator('select[name^="search-type"]').evaluateAll(selects => selects.slice(0, -1).map(select => select.value));
		expect(after).toEqual(before);
	});
});

test.describe('The Search box in a list', () => {

	test('A column the screen doesn\'t list uses its element\'s Search box, and a typed search works as it always has', async ({ page }) => {
		await artifactsListWithEraAndDonor(page);
		const everything = await page.getByText(/Showing entries:/).first().textContent();
		const eraBox = page.locator('input[name="search_artifacts_era"]');
		await expect(eraBox).toHaveClass(/formulize-searchbox-input/);

		// a typed term matches as contained in the field: BC is in BCE, and not in CE
		await eraBox.fill('BC');
		await eraBox.press('Enter');
		await waitForWorkingMessage(page);
		await expect(page.getByText('Showing entries: 1 to 6 of 6.')).toBeVisible();
		await expect(page.locator('input[name="search_artifacts_era"]')).toHaveValue('BC');

		// the full search language works in it too
		await page.locator('input[name="search_artifacts_era"]').fill('=CE');
		await page.locator('input[name="search_artifacts_era"]').press('Enter');
		await waitForWorkingMessage(page);
		await expect(page.getByText('Showing entries: 1 to 8 of 8.')).toBeVisible();

		// Show all and Show Blanks head the list, and Show all clears the typed search
		await page.locator('input[name="search_artifacts_era"]').click();
		const panel = page.locator('.formulize-searchbox-panel:popover-open');
		const choices = panel.locator('.fz-choice');
		await expect(choices.nth(0)).toHaveText('Show all');
		await expect(choices.nth(1)).toHaveText('Show Blanks');
		await expect(choices.nth(0).getByRole('checkbox')).not.toBeChecked();
		await choices.nth(0).click();
		await expect(page.locator('input[name="search_artifacts_era"]')).toHaveValue('');
		await panel.getByRole('button', { name: 'Apply' }).click();
		await waitForWorkingMessage(page);
		await expect(page.getByText(/Showing entries:/).first()).toHaveText(everything);
	});

	test('Ticking values searches for exactly those values, and clearing them shows everything again', async ({ page }) => {
		await artifactsListWithEraAndDonor(page);
		const everything = await page.getByText(/Showing entries:/).first().textContent();
		const donorBox = page.locator('input[name="search_artifacts_donor"]');
		await donorBox.click();
		await donorBox.fill('Arouet');
		const panel = page.locator('.formulize-searchbox-panel:popover-open');
		await expect(panel.getByText('François-Marie Arouet', { exact: true })).toBeVisible();
		await expect(panel.getByText('Emilie Du Châtelet', { exact: true })).toHaveCount(0);
		await panel.getByText('François-Marie Arouet', { exact: true }).click();
		await panel.getByRole('button', { name: 'Apply' }).click();
		await waitForWorkingMessage(page);

		// Roman Coin, Babylonian Spoon, Polynesian Canoe and Candide
		await expect(page.getByText('Showing entries: 1 to 4 of 4.')).toBeVisible();
		const donorCell = page.locator('.formulize-searchbox').filter({ has: page.locator('.formulize-searchbox-value[name="search_artifacts_donor"]') });
		// the picked value shows inside the box, which is otherwise empty, since the picked value is the search
		await expect(donorCell.locator('.formulize-searchbox-picked-text')).toHaveText('François-Marie Arouet');
		await expect(donorCell.locator('.formulize-searchbox-input')).toHaveValue('');
		// clicking it opens the list, with the value ticked
		await donorCell.locator('.formulize-searchbox-picked-text').click();
		const reopened = page.locator('.formulize-searchbox-panel:popover-open');
		await expect(reopened.getByRole('checkbox', { name: 'François-Marie Arouet' })).toBeChecked();
		await expect(reopened.getByRole('checkbox', { name: 'Show all' })).not.toBeChecked();
		// Cancel closes it, and it stays closed, with the value still picked
		await reopened.getByRole('button', { name: 'Cancel' }).click();
		await expect(page.locator('.formulize-searchbox-panel:popover-open')).toHaveCount(0);
		await expect(donorCell.locator('.formulize-searchbox-input')).toBeFocused();
		await expect(page.locator('.formulize-searchbox-panel:popover-open')).toHaveCount(0);
		await expect(donorCell.locator('.formulize-searchbox-picked-text')).toHaveText('François-Marie Arouet');

		await donorCell.getByRole('button', { name: 'Clear the values picked' }).click();
		await waitForWorkingMessage(page);
		await expect(page.getByText(/Showing entries:/).first()).toHaveText(everything);
	});
});
