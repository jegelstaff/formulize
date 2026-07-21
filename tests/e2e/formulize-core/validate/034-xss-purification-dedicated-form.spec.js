const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, applyColumnChanges, createMuseumForm, deleteMuseumForm } from '../../utils';

/**
 * T2 (list pipeline) - comprehensive XSS filtering on a purpose-built, throwaway form.
 *
 * Builds its own form with the three element behaviours that matter for output filtering, exercises them
 * with real payloads, asserts on the rendered DOM (never a text search of the page source), then deletes
 * the form so nothing is left behind:
 *
 *   - a plain TEXT element        -> the value must be ESCAPED (inert text)
 *   - a RICH-TEXT textarea        -> the value is intentional HTML, so it is PURIFIED: script/event
 *                                    vectors stripped, ordinary formatting kept
 *   - a DERIVED element whose formula wraps the text field's value in admin-authored HTML
 *     (`<div class=...><b>..</b> $text</div>`) -> the X17 case: admin markup is TRUSTED and survives,
 *     but the user data interpolated into it is NOT, so its payload is stripped
 *
 * Requires enforcement to be ON (formulizeEnforceHtmlPurification = 1), which is the fresh-install
 * default the suite runs under. Runs after the setup specs so the Museum application exists to host it.
 */

// If rendered live these create uniquely identifiable nodes / side-effects; if neutralised they are inert.
const TEXT_MARKER = 'XSSTEXT034';
const TEXT_PROBE = 'xss-text-probe-034';
const TEXT_PAYLOAD = `${TEXT_MARKER}"><img src=x class="${TEXT_PROBE}" onerror="window.__xssText034=1">`;

const RICH_MARKER = 'XSSRICH034';
const RICH_PROBE = 'xss-rich-probe-034';
const RICH_PAYLOAD = `${RICH_MARKER}<b>bold</b><img src=x class="${RICH_PROBE}" onerror="window.__xssRich034=1">`;

const DERIVED_WRAPPER = 'xss-derived-wrapper-034'; // admin markup in the derived formula - SHOULD survive

let testFid = 0;

test.describe.serial('T2 - XSS filtering on a dedicated form', () => {

	test('build the throwaway form with text, rich-text and derived elements', async ({ page }) => {
		await login(page, 'admin');

		// Create the form INSIDE the existing Museum application (created by the setup specs), the same way
		// 029 does - so the cleanup test can find it under "Application: Museum" and delete it. Creating it
		// via the aid=0 URL instead leaves it unassigned, where the delete navigation cannot reach it.
		testFid = await createMuseumForm(page, 'XSS Filter Test', 'ID');
		expect(testFid).toBeGreaterThan(0);

		// Add elements by navigating straight to the "new element" admin URL for each type - more robust
		// than driving the "Add an element" dialog, and it does not depend on being on the elements page.
		const newElementUrl = type => `/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${testFid}&aid=0&type=${type}`;

		// Plain text element.
		await page.goto(newElementUrl('text'));
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Free Text');
		await page.locator('input[name="elements-ele_handle"]').fill('xss_text');
		await saveAdminForm(page);

		// Rich-text textarea element - the rich-text control is a checkbox on the Options tab.
		await page.goto(newElementUrl('textarea'));
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Rich Text');
		await page.locator('input[name="elements-ele_handle"]').fill('xss_rich');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.locator('#elements-ele_value-use-rich-text').check();
		await saveAdminForm(page);

		// Derived element: admin HTML wrapping the user's text value (the X17 mix).
		await page.goto(newElementUrl('derived'));
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Derived Wrap');
		await page.locator('input[name="elements-ele_handle"]').fill('xss_derived');
		await page.getByRole('link', { name: 'Options' }).click();
		// Same pattern as 010: click the CodeMirror area to focus it, then fill the underlying textbox.
		await page.locator('div:nth-child(5) > pre:nth-child(2)').click();
		await page.getByRole('group', { name: 'Formula for generating values' }).getByRole('textbox')
			.fill(`$value = "<div class='${DERIVED_WRAPPER}'><b>Wrapped:</b> ".$xss_text."</div>";`);
		await saveAdminForm(page);
	});

	test('create an entry carrying payloads in the text and rich-text fields', async ({ page }) => {
		await login(page, 'admin');
		// index.php?fid= shows the LIST for this form; open the entry form via its Add button.
		await page.goto(`/modules/formulize/index.php?fid=${testFid}`);
		await page.getByRole('button', { name: 'Add XSS Filter Test', exact: true }).click();
		// The PI element "ID" is a required field - the form will not submit until it has a value.
		await page.getByRole('textbox', { name: 'ID *' }).fill('IDTEST034');
		await page.getByLabel('Free Text').fill(TEXT_PAYLOAD);
		// CKEditor exposes an ARIA textbox for the rich-text area.
		await page.getByRole('textbox', { name: /Rich Text Editor/i }).fill(RICH_PAYLOAD);
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // clears the entry lock
	});

	test('the list neutralises every payload while keeping admin/formatting markup', async ({ page }) => {
		page.on('dialog', async dialog => {
			throw new Error(`XSS executed - unexpected dialog fired: ${dialog.message()}`);
		});

		await login(page, 'admin');
		await page.goto(`/modules/formulize/index.php?fid=${testFid}`);

		// The custom elements are not shown as columns by default - add them via the Change columns popup.
		const popupPromise = page.context().waitForEvent('page');
		await page.getByRole('button', { name: 'Change columns' }).click();
		const popup = await popupPromise;
		await popup.bringToFront();
		await popup.getByRole('checkbox', { name: 'Free Text' }).check();
		await popup.getByRole('checkbox', { name: 'Rich Text' }).check();
		await popup.getByRole('checkbox', { name: 'Derived Wrap' }).check();
		await applyColumnChanges(popup);

		// POSITIVE assertions first - these double as the readiness gate. Applying columns reloads the
		// list; a toContainText / toHaveCount(1) auto-waits until the content is actually present, so once
		// these pass we know the reloaded page has settled. (A toHaveCount(0) must NOT go first: it would
		// pass vacuously on a still-loading page, where nothing is present yet.) They also assert the
		// values round-tripped: text and rich markers are shown, and the derived admin wrapper survives
		// purification. We assert on the DOM, never a text search of the raw page source.
		await expect(page.locator('body')).toContainText(TEXT_MARKER);   // plain text round-tripped
		await expect(page.locator('body')).toContainText(RICH_MARKER);   // rich text round-tripped (as plain snippet)
		await expect(page.locator(`div.${DERIVED_WRAPPER}`)).toHaveCount(1); // derived admin markup survives (X17)

		// NEGATIVE (safety) assertions - safe now that the page is confirmed loaded. Nothing the user
		// submitted can execute: no probe rendered as a live element, and no on* handler attribute exists
		// on the user data interpolated into the trusted derived wrapper (an absent attribute cannot fire).
		await expect(page.locator(`img.${TEXT_PROBE}`)).toHaveCount(0); // text escaped -> not a live element
		await expect(page.locator(`img.${RICH_PROBE}`)).toHaveCount(0); // rich stripped -> not a live element
		await expect(page.locator(`div.${DERIVED_WRAPPER} [onerror]`)).toHaveCount(0); // handlers stripped
		await expect(page.locator(`div.${DERIVED_WRAPPER} [onclick]`)).toHaveCount(0);
	});

	test('cleanup: delete the throwaway form', async ({ page }) => {
		expect(testFid, 'the build test must have run first').toBeGreaterThan(0);
		await login(page, 'admin');
		await deleteMuseumForm(page, testFid);
	});
});
