const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, addElementForm, ElementType } from '../../utils';

// Rich text (CKEditor) textareas mixed with ordinary textareas, with conditions on them.
//
// The bug this guards against: initializeCKEditor() used to decide what to turn into an
// editor purely by DOM presence -- `if(jQuery('#'+editorID).length > 0)`. But EVERY XOOPS
// textarea is rendered with id = markupName + '_tarea', not just the rich text ones. So when
// conditional.js revealed a conditionally-hidden row it called initializeCKEditor(handle +
// '_tarea') and that selector matched plain textareas just as happily as rich text ones --
// silently turning an ordinary textarea into a CKEditor, and renaming its input to
// 'useCKEditor' so its value no longer posted under its own name. The server now emits a
// catalogue (formulizeCKEditorIDs) of the elements that are actually supposed to be editors,
// including ones currently hidden by a condition, and initializeCKEditor guards on it.
//
// This lives in setup/ rather than validate/ because it creates elements. The validate specs
// run 4-way parallel in CI, where creating elements on a shared form would race with them;
// the setup specs run sequentially (--workers=1). It is numbered after 020 so that the donor
// entries created there are not disturbed by the new elements.
//
// Elements added to the Donors form:
//   donors_anonymous        radio Yes/No  -- governs the Bio
//   donors_bio              RICH TEXT textarea, shown only when the donor is NOT anonymous
//   donors_org_background   PLAIN textarea, shown only when Type of donor = Organization
// plus the already-existing donors_street_address, a PLAIN textarea that is always visible.
//
// Selecting "No" to anonymous AND "Organization" as the type puts a rich text editor and a
// conditionally-revealed plain textarea on the page at the same time -- the exact mix that
// used to break.

test.use({ baseURL: E2E_TEST_BASE_URL });

test.describe.configure({ mode: 'serial' });

test.describe('Donors rich text / plain textarea elements', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.goto('/modules/formulize/admin/ui.php?page=home');
		await waitForAdminPageReady(page);
		await page.getByRole('link', { name: 'Application: Museum' }).click();
		// The form list under an application is alphabetical by form title (applications.php
		// orders by forms.form_title), not creation order. With all 5 Museum forms present
		// (Artifacts, Collections, Donors, Exhibits, Surveys), Donors is the 3rd, index 2.
		await page.getByRole('link', { name: 'Elements' }).nth(2).click();
	})

	test('Create Is the donor anonymous Element', async ({ page }) => {
		await addElementForm(page, ElementType.radio);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Is the donor anonymous?');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_anonymous');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('button', { name: 'Add' }).click();
		await page.locator('input[name="ele_value\\[0\\]"]').click();
		await page.locator('input[name="ele_value\\[0\\]"]').fill('Yes');
		await page.locator('input[name="ele_value\\[0\\]"]').press('Tab');
		await page.locator('input[name="ele_value\\[1\\]"]').fill('No');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Is the donor anonymous?');
	});

	// The Bio is a RICH TEXT textarea that is hidden by a condition on first load. It is the
	// only rich text element on the Donors form, so it also covers the case where a form's
	// only editor is conditional: the CKEditor bootstrap JS still has to be emitted, otherwise
	// the editor never initializes when the condition later reveals it.
	test('Create Bio Element (rich text, conditional)', async ({ page }) => {
		await addElementForm(page, ElementType.textarea);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Bio');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_bio');
		await page.locator('textarea[name="elements-ele_desc"]').fill('A short biography of the donor, for use in exhibit credits');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.getByRole('checkbox', { name: 'Display this element using a' }).check(); // rich text (CKEditor)
		await page.getByRole('link', { name: 'Display Settings' }).click();
		await page.locator('#new_elementfilter_element').selectOption('Is the donor anonymous?');
		await page.locator('#new_elementfilter_term').fill('No');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Bio');
	});

	// A PLAIN textarea that is also revealed by a condition. This is the element the bug used
	// to corrupt: it must stay an ordinary textarea even though a CKEditor exists on the page.
	test('Create Organization background Element (plain, conditional)', async ({ page }) => {
		await addElementForm(page, ElementType.textarea);
		await waitForAdminPageReady(page)
		await page.locator('input[name="elements-ele_caption"]').fill('Organization background');
		await page.locator('input[name="elements-ele_handle"]').fill('donors_org_background');
		// deliberately NOT rich text - no "Display this element using a..." checkbox here
		await page.getByRole('link', { name: 'Display Settings' }).click();
		await page.locator('#new_elementfilter_element').selectOption('Type of donor');
		await page.locator('#new_elementfilter_term').fill('Organization');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Organization background');
	});

});

test.describe('CKEditor is only applied to rich text textareas', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Donors', exact: true }).click();
		await page.getByRole('button', { name: 'Add Donor', exact: true }).click();
	})

	test('Conditionally revealed textareas become editors only when they are rich text', async ({ page }) => {

		const streetAddress = page.locator('div.formulize-input-donors_street_address');
		const bio = page.locator('div.formulize-input-donors_bio');
		const orgBackground = page.locator('div.formulize-input-donors_org_background');

		// ---- On first load: nothing is answered, so both conditional elements are hidden.
		// The always-visible plain textarea (street address) must not be an editor.
		await expect(streetAddress.locator('textarea')).toBeVisible();
		await expect(streetAddress.locator('.ck-editor')).toHaveCount(0);

		// The Bio is rich text but hidden by its condition. The server must still have
		// catalogued it, so that the CKEditor bootstrap exists and it can be initialized when
		// it appears. Without that, a form whose only editor is conditional never gets one.
		const bootstrap = await page.evaluate(() => ({
			hasInitFunction: typeof initializeCKEditor === 'function',
			catalogue: typeof formulizeCKEditorIDs !== 'undefined' ? Object.keys(formulizeCKEditorIDs) : null,
			// the plain always-visible textarea's DOM id, which is <markupName>_tarea just like
			// a rich text one -- this collision is what the old code tripped over
			streetAddressEditorId: document.querySelector('div.formulize-input-donors_street_address textarea').id,
			// the bootstrap must declare the editor registry exactly once, not once per editor
			ckEditorsDeclarations: (document.documentElement.innerHTML.match(/var CKEditors = \{\};/g) || []).length,
		}));
		expect(bootstrap.hasInitFunction).toBe(true);
		expect(bootstrap.ckEditorsDeclarations).toBe(1);
		expect(bootstrap.catalogue).not.toBeNull();
		// the catalogue must NOT contain the plain textarea, even though its id ends in _tarea
		expect(bootstrap.catalogue).not.toContain(bootstrap.streetAddressEditorId);
		expect(bootstrap.streetAddressEditorId).toMatch(/_tarea$/);

		// ---- Reveal BOTH conditional textareas at once: a rich text one and a plain one.
		await page.getByText('Organization', { exact: true }).click(); // Type of donor -> reveals the plain Organization background
		await expect(orgBackground.locator('textarea')).toBeVisible();

		await page.getByText('No', { exact: true }).click(); // Is the donor anonymous? -> reveals the rich text Bio
		await expect(bio.locator('.ck-editor')).toBeVisible(); // the rich text one DID become an editor

		// THE REGRESSION: the plain conditional textarea must stay a plain textarea, even though
		// a CKEditor now exists on the page and initializeCKEditor() was called for this row too.
		await expect(orgBackground.locator('.ck-editor')).toHaveCount(0);
		// ...and it must still post under its own name (de_<fid>_new_<eleid>). The old code
		// renamed the textarea it hijacked to 'useCKEditor', which is how the field's value
		// went missing on save.
		await expect(orgBackground.locator('textarea')).toHaveAttribute('name', /^de_\d+_new_\d+$/);
		await expect(streetAddress.locator('.ck-editor')).toHaveCount(0); // still plain too

		// only the Bio is a live editor
		const liveEditors = await page.evaluate(() => Object.keys(CKEditors));
		expect(liveEditors).toHaveLength(1);

		// ---- Fill everything in and save. This exercises updateCKEditors(), which copies the
		// editor's content into the hidden input that carries the element's markup name.
		await page.getByRole('textbox', { name: 'Organization name' }).fill('L. Warner Foundation');
		await orgBackground.locator('textarea').fill('Founded in 1885 by a group of amateur archaeologists.');
		await bio.locator('.ck-content').fill('A distinguished patron of the museum.');

		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // clears the entry lock

		// ---- Reopen the saved entry: both values must have persisted, and on this load both
		// elements are rendered normally (their conditions are met by the saved data) rather
		// than injected by conditional.js.
		await page.locator('#mainmenu').getByRole('link', { name: 'Donors', exact: true }).click();
		await page.getByRole('link', { name: 'L. Warner Foundation' }).first().click();

		await expect(bio.locator('.ck-editor')).toBeVisible();
		await expect(bio.locator('.ck-content')).toContainText('A distinguished patron of the museum.');
		await expect(orgBackground.locator('textarea')).toHaveValue('Founded in 1885 by a group of amateur archaeologists.');
		await expect(orgBackground.locator('.ck-editor')).toHaveCount(0); // still not an editor

		// ---- Hide the rich text editor again: it must be destroyed and unregistered, not left
		// behind as a stale entry in the CKEditors registry.
		await page.getByText('Yes', { exact: true }).click(); // anonymous -> hides the Bio
		await expect(bio.locator('.ck-editor')).toHaveCount(0);
		await expect.poll(() => page.evaluate(() => Object.keys(CKEditors).length)).toBe(0);

		await page.getByRole('link', { name: 'Save and Close' }).click(); // clears the entry lock
	});

});
