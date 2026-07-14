const { test, expect } = require('@playwright/test');
import { login, waitForAdminPageReady, openElementAccordion, saveAdminForm, addElementForm, ElementType, getFidFromFormAdminPage } from '../../utils';

// This spec builds its own throwaway form rather than renaming a handle on the shared Artifacts form,
// and deletes it again at the end.
// CI runs the validate suite with `--workers=4 --fully-parallel`, so this spec runs at the same time as
// the others: 028 recomputes the derived values of the Artifacts "Year-Era" element, and 025/026 read
// Artifacts data. Renaming an Artifacts handle out from under them (even briefly, before renaming it
// back) is a cross-file race that serial mode cannot fix, because those are different files. A private
// form keeps this test's mutations invisible to everyone else.
//
// The handles below reproduce the case that actually matters: the derived element (`hr_year_era`) has a
// handle with the renamed handle (`hr_year`) as a PREFIX. Rewriting the formula must replace the
// reference to `$hr_year` without corrupting `$hr_year_era`.

// The second test deletes the form the first one creates, so they must run in order, in the same
// worker. CI runs the validate suite with `--workers=4 --fully-parallel`, which overrides the serial
// defaults in playwright.config.js — without this they would race, and the fid below would be unset.
test.describe.configure({ mode: 'serial' });

// Set by the first test, consumed by the second (safe: serial mode keeps both in one worker, in order).
let createdFid = null;

/**
 * Read the current formula from a derived element's Options tab.
 * The formula is rendered server-side into the textarea with id="elements-ele_value",
 * so reading the DOM value property directly is reliable regardless of CodeMirror state.
 * Must already be on the Options tab when called.
 */
async function getDerivedFormula(page) {
	return page.locator('#elements-ele_value').inputValue();
}

test('Renaming an element handle updates its $handle reference in derived value formulas', async ({ page }) => {
	// This test does two full handle-rename cycles, each heavier than an ordinary admin save (see
	// the comment on the saveAdminForm() call below), on top of the usual create-3-elements setup.
	// The default per-test timeout doesn't leave enough headroom for that under load.
	test.setTimeout(300000);
	await login(page, 'admin');

	// ── Create a dedicated "Handle Rename Tests" form in the Museum application ──
	await page.goto('/modules/formulize/admin/ui.php?page=home');
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	await page.getByRole('link', { name: 'Create a new form' }).click();
	await waitForAdminPageReady(page);
	await expect(page.locator('input[name="forms-form_title"]')).toBeVisible();
	await page.getByRole('textbox', { name: 'Form title:' }).fill('Handle Rename Tests');
	await saveAdminForm(page);

	const fid = await getFidFromFormAdminPage(page);
	expect(fid).toBeGreaterThan(0);
	createdFid = fid; // the delete test below removes this form again

	// Repeatedly reopening the same element's Configure panel (Options -> save -> Options again)
	// leaves an async tail (the admin UI's own follow-up XHR/redirect handling) that can still be
	// in flight when the next step fires a fresh page.goto(), which Chromium then aborts
	// (net::ERR_ABORTED) because a navigation was already superseding it. A settled network is
	// not enough of a guarantee on its own, so retry once on a transient nav failure.
	const gotoElementsTab = async () => {
		try {
			await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${fid}&tab=elements`);
		} catch (err) {
			if (!/ERR_ABORTED/.test(err.message)) { throw err; }
			await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${fid}&tab=elements`);
		}
		await waitForAdminPageReady(page);
	};

	// ── The two elements the formula will reference ──────────────────────────
	for (const [caption, handle] of [['Rename Year', 'hr_year'], ['Rename Era', 'hr_era']]) {
		await gotoElementsTab();
		await addElementForm(page, ElementType.text);
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill(caption);
		await page.locator('input[name="elements-ele_handle"]').fill(handle);
		await saveAdminForm(page);
	}

	// ── The derived element whose formula references them ────────────────────
	// Its own handle (hr_year_era) starts with hr_year, which is the point: renaming hr_year must not
	// mangle it.
	await gotoElementsTab();
	await addElementForm(page, ElementType.derived);
	await waitForAdminPageReady(page);
	await page.locator('input[name="elements-ele_caption"]').fill('Rename Combined');
	await page.locator('input[name="elements-ele_handle"]').fill('hr_year_era');
	await page.getByRole('link', { name: 'Options' }).click();
	await page.locator('div:nth-child(5) > pre:nth-child(2)').click();
	await page.getByRole('group', { name: 'Formula for generating values' }).getByRole('textbox').fill('$value = $hr_year.$hr_era;');
	await saveAdminForm(page);

	const openCombinedOptions = async () => {
		await gotoElementsTab();
		await openElementAccordion(page, 'Rename Combined');
		await page.getByRole('link', { name: 'Configure' }).click();
		await waitForAdminPageReady(page);
		await page.getByRole('link', { name: 'Options' }).click();
		await waitForAdminPageReady(page);
	};

	const renameYearHandleTo = async (newHandle) => {
		await gotoElementsTab();
		await openElementAccordion(page, 'Rename Year');
		await page.getByRole('link', { name: 'Configure' }).click();
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_handle"]').fill(newHandle);
		// Renaming a handle does more server-side work than an ordinary save (renameElementResources()
		// scans and rewrites every derived-value code file, plus several extra DB updates), so give it
		// more headroom than saveAdminForm()'s default before treating a slow admin-ui fade as a hang.
		await saveAdminForm(page, 'regular', 180000);
		// The save can kick off a trailing async redirect/reload (see runSaveEvent() in ui.html)
		// that is not yet in flight when saveAdminForm() returns (it only waits for the admin-ui
		// opacity to settle, not for that follow-up work). Let it finish before the caller fires
		// off its own page.goto(), otherwise that navigation can race the tail end of this one and
		// get cancelled with net::ERR_ABORTED.
		await page.waitForLoadState('networkidle');
	};

	// Sanity check: the formula was stored as written.
	await openCombinedOptions();
	expect(await getDerivedFormula(page)).toContain('$hr_year.');

	// Step 1: Rename hr_year to hr_year_modified
	await renameYearHandleTo('hr_year_modified');

	// Step 2: Verify the formula was updated to use the new handle
	await openCombinedOptions();
	const formulaAfterRename = await getDerivedFormula(page);
	expect(formulaAfterRename).toContain('$hr_year_modified');
	// The old handle followed by a dot was in the original formula; it must be gone
	expect(formulaAfterRename).not.toContain('$hr_year.');

	// Step 3: Rename hr_year_modified back to hr_year
	await renameYearHandleTo('hr_year');

	// Step 4: Verify the formula was updated back to the original handle
	await openCombinedOptions();
	const formulaAfterRevert = await getDerivedFormula(page);
	expect(formulaAfterRevert).toContain('$hr_year.');
	expect(formulaAfterRevert).not.toContain('$hr_year_modified');
});

// Removes the form the test above created — which also exercises form deletion from the application's
// form list: the Delete link asks for confirmation in a JS confirm() dialog, and only deletes if the
// webmaster accepts.
test('Deleting a form from the application form list removes it, after confirming the warning', async ({ page }) => {
	expect(createdFid, 'the form-creation test must have run first').toBeGreaterThan(0);

	await login(page, 'admin');
	await page.goto('/modules/formulize/admin/ui.php?page=home');
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	await waitForAdminPageReady(page);

	// The Delete link is in the DOM for every form, but it lives inside the form's details panel, which
	// is hidden until the form's box in the listing is clicked (clickFormDetails() in
	// templates/js/formulize-admin-organize-forms.js). Click the form's name to open its panel — the
	// name, not the box itself, because the box's centre may land on the Elements/Screens links inside
	// it, which would navigate away instead of opening the panel.
	const formBox = page.locator(`div.form-listing-box[formid="${createdFid}"]`);
	await expect(formBox).toBeVisible();
	await formBox.locator('.form-name-text').click();

	// Each form's Delete link carries its fid in the target attribute, so this addresses exactly the
	// form we made and not any other form in the Museum application.
	const deleteLink = page.locator(`a.deleteformlink[target="${createdFid}"]`);
	await expect(deleteLink).toBeVisible();

	// The click raises a confirm() dialog warning that the data will be lost. Playwright dismisses
	// dialogs by default (which would cancel the delete), so accept it explicitly — and check the
	// warning is actually the one we expect, since accepting a dialog blind would hide a changed prompt.
	let dialogMessage = null;
	page.once('dialog', async dialog => {
		dialogMessage = dialog.message();
		await dialog.accept();
	});
	await deleteLink.click();

	await expect.poll(() => dialogMessage).toContain('Are you sure you want to delete this form?');

	// The form is gone from the listing. Assert on the form's box, not just its Delete link: the link is
	// hidden until its panel is opened, so a hidden-but-present link would still satisfy toHaveCount(0)
	// for the wrong reason.
	await waitForAdminPageReady(page);
	await expect(formBox).toHaveCount(0);
	await expect(page.getByText('Handle Rename Tests')).toHaveCount(0);

	// And it stays gone on a fresh load of the application page, so this is a real delete rather than
	// the listing merely re-rendering without it.
	await page.goto('/modules/formulize/admin/ui.php?page=home');
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	await waitForAdminPageReady(page);
	await expect(page.locator(`div.form-listing-box[formid="${createdFid}"]`)).toHaveCount(0);
	await expect(page.getByText('Handle Rename Tests')).toHaveCount(0);
});
