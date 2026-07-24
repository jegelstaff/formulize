const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, applyColumnChanges, createMuseumForm, deleteMuseumForm } from '../../utils';

/**
 * T2 (list pipeline) - comprehensive XSS filtering on a purpose-built, throwaway form.
 *
 * Builds its own form with the three element behaviours that matter for output filtering, exercises them
 * with real payloads, asserts on the rendered DOM (never a text search of the page source), then deletes
 * the form so nothing is left behind:
 *
 *   - a plain TEXT element        -> the value must be ESCAPED (inert text, no markup of any kind)
 *   - a RICH-TEXT textarea        -> the value is intentional HTML, so it is PURIFIED: an allow-list
 *                                    filter that strips script/event vectors (on* handlers, javascript:
 *                                    URIs) but keeps ordinary formatting tags/attributes - including
 *                                    <img>, which is why a purified payload can still contain a (harmless,
 *                                    handler-stripped) <img> tag; that is not a vulnerability
 *   - a DERIVED element whose formula wraps the text field's value in admin-authored HTML
 *     (`<div class=...><b>..</b> $text</div>`) -> the X17 case: admin markup is TRUSTED and survives,
 *     and the user data interpolated into it goes through the same purification as rich text - any
 *     script vector in it is stripped, but harmless formatting tags (like the <img> from the TEXT
 *     payload, reused here since it's the same underlying entry value) can still appear
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

		// NEGATIVE (safety) assertions - safe now that the page is confirmed loaded.
		//
		// The actual XSS-safety invariant is "no event-handler attribute survives on the CONTENT DERIVED
		// FROM USER DATA", NOT "no <img> tag survives" and NOT "no on* attribute anywhere on the page" -
		// the list chrome itself is full of legitimate onclick handlers with nothing to do with entry data
		// (e.g. getHTMLForList's own per-cell edit icon: `onclick="renderElement(...)"`), so a page-wide
		// check is not a meaningful signal and was over-broad. Purification (rich text, derived) is an
		// ALLOW-LIST filter: it deliberately keeps ordinary formatting tags like <img> - rich content is
		// allowed to include real images, that is what "ordinary formatting kept" means - while stripping
		// every script vector (on* handlers, javascript: URIs, <script> tags). An <img> with no event
		// handler cannot execute anything, so its mere presence is not a vulnerability. Asserting
		// `img.PROBE toHaveCount(0)` for the rich-text/derived paths would fail against HTML Purifier's
		// correct, intended output (verified directly: purifying these exact payloads strips
		// onerror/onclick but keeps the <img> tag). So each check below is scoped to the specific cell
		// that holds the payload in question, via the `main-cell-div` wrapper every list cell gets.
		const richCell = page.locator('div.main-cell-div', { hasText: RICH_MARKER });
		await expect(richCell).toHaveCount(1); // exactly one cell carries the rich-text payload
		await expect(richCell.locator('[onerror]')).toHaveCount(0); // rich text: handler stripped
		await expect(richCell.locator('[onclick]')).toHaveCount(0);
		await expect(page.locator(`div.${DERIVED_WRAPPER} [onerror]`)).toHaveCount(0); // derived: handler stripped
		await expect(page.locator(`div.${DERIVED_WRAPPER} [onclick]`)).toHaveCount(0);

		// The plain TEXT field is the one path that must produce NO markup at all - it is escaped, not
		// purified, so its payload is inert text with no child elements. The only place `img.${TEXT_PROBE}`
		// can legitimately appear is inside the DERIVED wrapper: the admin's formula re-embeds this same raw
		// entry value there, and THAT copy goes through purification (img kept, handler stripped) - the X17
		// case this form exists to exercise, not a failure of the text field's own escaping.
		await expect(page.locator(`img.${TEXT_PROBE}`)).toHaveCount(1); // exactly the expected derived-wrapper echo
		await expect(page.locator(`div.${DERIVED_WRAPPER} img.${TEXT_PROBE}`)).toHaveCount(1); // ...and only there
	});

	// Cleanup lives in afterAll, not a trailing test(), because test.describe.serial SKIPS every
	// remaining test once one fails - a trailing 'cleanup' test would never run, leaving the throwaway
	// form behind forever. afterAll runs regardless of pass/fail (that is its whole purpose here), so
	// the form is always removed. It needs its own page: afterAll is worker-scoped and cannot use the
	// test-scoped `page` fixture, whose browser context is already gone by the time this runs.
	test.afterAll(async ({ browser }) => {
		if (!testFid) { return; } // the build step never got far enough to create a form
		const page = await browser.newPage();
		await login(page, 'admin');
		await deleteMuseumForm(page, testFid);
		await page.close();
	});
});
