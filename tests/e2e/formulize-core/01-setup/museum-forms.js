const { expect } = require('@playwright/test');
import { saveAdminForm, waitForAdminPageReady, editScreenPage, dbQuery, dbPrefix, sqlEscape, openApplicationAccordion } from '../../utils';

// What the setup specs that add forms to the museum share: 022 adds Locations and Loan Requests, and 023 adds Visits and Offers.

export function formId(title) {
	return dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title = '${sqlEscape(title)}'`)[0][0];
}

export function museumAppId(fid) {
	return dbQuery(`SELECT appid FROM ${dbPrefix()}_formulize_application_form_link WHERE fid = ${fid}`)[0][0];
}

// The app's form listing has every form's links in it at once, so go straight to the new element, as 035 and 038 do
export async function startElement(page, formTitle, type, caption, handle, columnHeading = '') {
	const fid = formId(formTitle);
	await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${fid}&aid=${museumAppId(fid)}&type=${type}`);
	await waitForAdminPageReady(page);
	await page.locator('input[name="elements-ele_caption"]').fill(caption);
	await page.locator('input[name="elements-ele_handle"]').fill(handle);
	if (columnHeading) {
		await page.locator('input[name="elements-ele_colhead"]').fill(columnHeading);
	}
}

export async function finishElement(page, heading) {
	await saveAdminForm(page);
	await expect(page.getByRole('heading')).toContainText(heading);
}

export async function makeRequired(page) {
	await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
}

export async function linkToSource(page, source) {
	await page.getByRole('link', { name: 'Options' }).click();
	await page.locator('#formlink').selectOption(source);
}

export async function inheritPermissionsFromArtifacts(page, formTitle) {
	const formUrl = `/modules/formulize/admin/ui.php?page=form&fid=${formId(formTitle)}&tab=permissions`;
	await page.goto(formUrl);
	await waitForAdminPageReady(page);
	await expect(page.locator('#perm_mode_inherit')).toBeVisible({ timeout: 30000 });
	await page.locator('#perm_mode_inherit').check();
	await page.locator('#parent_perm_fid_select').selectOption({ label: 'Artifacts' });
	await saveAdminForm(page);
	await page.goto(formUrl);
	await expect(page.locator('#perm_mode_inherit')).toBeChecked({ timeout: 30000 });
	await expect(page.locator('#parent_perm_fid_select option:checked')).toHaveText('Artifacts');
}

// Open a form's Screens tab. A form's name also appears in the Connections lists of other forms, so find its own box.
export async function openFormScreens(page, formTitle) {
	await page.goto('/modules/formulize/admin/ui.php?page=home');
	await waitForAdminPageReady(page);
	await openApplicationAccordion(page, 'Museum');
	const formBox = page.locator('div.form-listing-box')
		.filter({ has: page.locator('.form-name-text').filter({ hasText: new RegExp(`^${formTitle}$`) }) });
	await expect(formBox).toBeVisible();
	await formBox.locator('.form-name-text').click();
	await formBox.getByRole('link', { name: 'Screens' }).first().click();
}

export async function setElementsOnOnlyPage(page, formTitle, screenName, elements) {
	await openFormScreens(page, formTitle);
	await page.getByRole('link', { name: screenName, exact: true }).click();
	await waitForAdminPageReady(page);
	const screenUrl = page.url();
	await page.getByRole('link', { name: 'Pages' }).click();
	await page.getByRole('link', { name: 'Edit this page' }).click();
	await expect(page.getByText('Title for page number')).toBeVisible();
	await page.getByLabel('Form elements to display on').selectOption(elements);
	await saveAdminForm(page, 'popup');
	await page.getByRole('button', { name: 'close' }).click();
	await expectElementsOnPage(page, screenUrl, '', elements);
}

// Saving a page of a screen is what later specs depend on, so check it from a fresh load of the screen
export async function expectElementsOnPage(page, screenUrl, pageTitle, elements) {
	await page.goto(screenUrl);
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Pages' }).click();
	if (pageTitle) {
		await editScreenPage(page, pageTitle);
	} else {
		await page.getByRole('link', { name: 'Edit this page' }).click();
	}
	// allTextContents() doesn't wait for the popup's contents, which arrive all at once
	await expect(page.getByText('Title for page number')).toBeVisible();
	const selected = await page.getByLabel('Form elements to display on').locator('option:checked').allTextContents();
	expect(selected.map(label => label.trim()).sort()).toEqual([...elements].sort());
}

// The page has to have finished loading, or it can't release the lock on the entry as it goes
export async function saveAndClose(page) {
	await page.waitForLoadState('load');
	await page.getByRole('link', { name: 'Save and Close' }).click();
}
