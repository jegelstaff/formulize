const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, createMuseumForm, deleteMuseumForm, dbQuery, dbPrefix } from '../../utils';

/**
 * T3.6 - read-only display funnel (makeValueSafeForReadOnlyDisplay), the DISABLED render path.
 *
 * A disabled element is a SEPARATE sink from the editable one. Editable text/textarea render through
 * icms_form_elements_Text/Textarea::render() (escaped at the core sink, covered by
 * normalize_then_escape_test.php). A DISABLED element instead hands its value to a xoopsFormLabel, which
 * renders whatever it is given AS-IS - so before the read-only funnel those branches emitted user data raw.
 *
 * To reach the disabled sink we set the element's Display Setting "Disable this element for any groups?" to
 * "all" AFTER the entry is saved (a disabled element cannot be filled, so the payload has to go in first).
 * ele_disabled == 1 disables unconditionally, webmaster included (isElementDisabledForUser), so the entry
 * reopened at index.php?fid=&ve= renders both elements disabled in the ordinary form view - no printable
 * view (that button needs a screen) and no permission scaffolding.
 *
 * This is the WIRING half of the coverage: the pure escape/purify functions are unit-tested, but only a
 * real render proves each element's disabled branch actually calls the funnel with the right arguments -
 * exactly the class of bug found during implementation (a shared helper called with an $element not in its
 * scope, a fatal that php -l and a unit test both miss).
 *
 * Each field asserts BOTH: the value round-tripped (shown), AND its payload is inert (no surviving event
 * handler, no dialog). Purification is an allow-list, so a handler-less <img> surviving is correct, not a
 * failure - same as 034/036. Requires enforcement ON (the suite default).
 */

const TEXT_MARKER = 'XSSROTEXT037';
const TEXT_PROBE = 'xss-ro-text-037';
const TEXT_PAYLOAD = `${TEXT_MARKER}"><img src=x class="${TEXT_PROBE}" onerror="window.__xssRoText037=1">`;

const AREA_MARKER = 'XSSROAREA037';
const AREA_PROBE = 'xss-ro-area-037';
const AREA_PAYLOAD = `${AREA_MARKER}<img src=x class="${AREA_PROBE}" onerror="window.__xssRoArea037=1">`;

let testFid = 0;
let testEntryId = 0;

test.describe.serial('T3.6 - read-only display funnel (disabled elements)', () => {

	test('build a throwaway form with a text and a plain-textarea element', async ({ page }) => {
		await login(page, 'admin');

		// self-heal any form left behind by a previous aborted run (see 036 for the rationale)
		for (const row of dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title = 'XSS ReadOnly Test'`)) {
			await deleteMuseumForm(page, parseInt(row[0], 10));
		}

		testFid = await createMuseumForm(page, 'XSS ReadOnly Test', 'ID');
		expect(testFid).toBeGreaterThan(0);

		const newElementUrl = type => `/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${testFid}&aid=0&type=${type}`;

		await page.goto(newElementUrl('text'));
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('RO Text');
		await page.locator('input[name="elements-ele_handle"]').fill('ro_text');
		await saveAdminForm(page);

		// A PLAIN textarea (rich text left off) - the disabled branch that was rewritten to escape the value
		// and convert \n to <br> through the funnel, rather than undoAllHTMLChars-and-emit-raw.
		await page.goto(newElementUrl('textarea'));
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('RO Area');
		await page.locator('input[name="elements-ele_handle"]').fill('ro_area');
		await saveAdminForm(page);
	});

	test('create an entry carrying payloads in both fields (while still editable)', async ({ page }) => {
		await login(page, 'admin');
		await page.goto(`/modules/formulize/index.php?fid=${testFid}`);
		await page.getByRole('button', { name: 'Add XSS ReadOnly Test', exact: true }).click();
		await page.getByRole('textbox', { name: 'ID *' }).fill('IDRO037');
		await page.getByLabel('RO Text').fill(TEXT_PAYLOAD);
		// A plain Formulize textarea is not associated to its caption by a <label for>, so getByLabel does
		// not find it (see 021 - the textarea's id is markupName + '_tarea'). This form has exactly one
		// content textarea, so target it by that id suffix.
		await page.locator('textarea[id$="_tarea"]').fill(AREA_PAYLOAD);
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // clear the entry lock

		// Resolve ids from the DB (dbQuery returns arrays of column values, not objects - it runs mariadb -N).
		const handleRow = dbQuery(`SELECT form_handle FROM ${dbPrefix()}_formulize_id WHERE id_form = ${testFid}`);
		const formHandle = handleRow[0][0];
		const entryRows = dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_${formHandle} ORDER BY entry_id DESC LIMIT 1`);
		testEntryId = parseInt(entryRows[0][0], 10);
		expect(testEntryId).toBeGreaterThan(0);
	});

	test('disable both elements for all groups', async ({ page }) => {
		await login(page, 'admin');
		// Element ids, so we can open each element's config directly.
		const eleRows = dbQuery(
			`SELECT ele_id, ele_handle FROM ${dbPrefix()}_formulize WHERE id_form = ${testFid} AND ele_handle IN ('ro_text','ro_area')`);
		expect(eleRows.length).toBe(2);

		for (const [eleId] of eleRows) {
			await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=${eleId}&fid=${testFid}&aid=0`);
			await waitForAdminPageReady(page);
			await page.getByRole('link', { name: 'Display Settings' }).click();
			await page.locator('select[name="elements_ele_disabled\\[\\]"]').selectOption('all');
			await saveAdminForm(page);
		}
	});

	test('the disabled render neutralises both payloads while showing the values', async ({ page }) => {
		page.on('dialog', async dialog => {
			throw new Error(`XSS executed - unexpected dialog fired: ${dialog.message()}`);
		});
		await login(page, 'admin');

		// Reopen the saved entry: both elements are now disabled-for-all-groups, so they render disabled in
		// the ordinary form view, exercising the read-only funnel with the stored payloads.
		await page.goto(`/modules/formulize/index.php?fid=${testFid}&ve=${testEntryId}`);

		// POSITIVE first (auto-waits, doubles as the readiness gate): the values round-tripped into the
		// disabled render. A toHaveCount(0) must not precede this or it could pass on a still-loading page.
		await expect(page.locator('body')).toContainText(TEXT_MARKER);
		await expect(page.locator('body')).toContainText(AREA_MARKER);

		// SAFETY: no payload's event handler survived the disabled render, and no dialog fired.
		await expect(page.locator(`img.${TEXT_PROBE}[onerror]`)).toHaveCount(0);
		await expect(page.locator(`img.${AREA_PROBE}[onerror]`)).toHaveCount(0);
		await expect(page.locator('[onerror]')).toHaveCount(0);
		expect(await page.evaluate(() => window.__xssRoText037)).toBeUndefined();
		expect(await page.evaluate(() => window.__xssRoArea037)).toBeUndefined();
	});

	test('cleanup: delete the throwaway form', async ({ page }) => {
		expect(testFid, 'the build test must have run first').toBeGreaterThan(0);
		await login(page, 'admin');
		await deleteMuseumForm(page, testFid);
	});
});
