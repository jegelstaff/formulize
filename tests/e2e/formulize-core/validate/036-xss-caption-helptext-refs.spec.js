const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, createMuseumForm, deleteMuseumForm, dbQuery, dbPrefix } from '../../utils';

/**
 * T3 - {reference} substitution into admin-authored display text (captions and help text).
 *
 * Captions and help text deliberately support HTML: they are authored by someone who can edit the form,
 * so that markup is TRUSTED and is rendered as written. What is NOT trusted is the value a {reference}
 * resolves to - that is user-submitted entry data, and getValue() decodes it back to its raw form on the
 * way out. Before this was fixed, that value was spliced into the authored HTML raw, so anything a user
 * could type into a referenced field became live markup in every caption that referenced it.
 *
 * The fix filters the substituted VALUE only (formulize_makeValueSafeForDisplay -> purification), and
 * leaves the surrounding authored markup untouched. So each test below asserts BOTH halves:
 *
 *   - SAFETY   - the payload's script vector (the on* handler) does not survive
 *   - FIDELITY - the admin's own markup around the reference DOES survive, and the referenced value is
 *                still actually shown (the fix must not blank the content out)
 *
 * Purification is an ALLOW-LIST filter, not an escaper: it deliberately keeps ordinary formatting tags
 * including <img>, while stripping on* handlers, javascript: URIs and <script>. So a surviving <img> with
 * no handler is the CORRECT result here, not a failure - same reasoning as 034.
 *
 * PHP support: confirmed asymmetric, and the tests reflect it. evalPHPStrings() has exactly ONE caller -
 * the help-text path - so <?php ?> runs in HELP TEXT ONLY. Captions get {ref} substitution but are never
 * eval'd. The 'caption PHP is not executed' test pins that down so it cannot regress into a second eval
 * surface unnoticed.
 *
 * Requires enforcement ON (formulizeEnforceHtmlPurification = 1) - the default this suite runs under.
 */

// If rendered live these create uniquely identifiable nodes / side-effects; if neutralised they are inert.
const MARKER = 'XSSREF036';
const PROBE = 'xss-ref-probe-036';
const PAYLOAD = `${MARKER}"><img src=x class="${PROBE}" onerror="window.__xssRef036=1">`;

// Admin-authored markup wrapped around each {reference} - these MUST survive untouched.
const CAP_WRAP = 'xss-cap-wrap-036';
const HELP_WRAP = 'xss-help-wrap-036';
const PHP_WRAP = 'xss-php-wrap-036';

// Emitted by admin-authored PHP in help text, to prove the PHP actually ran.
const PHP_RAN = 'PHPRAN036';
// Proves a <?php ?> block in a CAPTION is NOT executed (caption must show it as text, or at least not run it).
const CAP_PHP_SENTINEL = 'CAPPHP036';

let testFid = 0;
let testEntryId = 0;

test.describe.serial('T3 - {ref} substitution into captions and help text', () => {

	test('build the throwaway form: a source field, plus fields whose caption/help text reference it', async ({ page }) => {
		await login(page, 'admin');

		// Remove any form this spec left behind previously. If an earlier run failed part way through, its
		// cleanup test never ran, and a second form with the same title would then exist - which breaks the
		// exact-match "Add XSS Ref Test" button lookup and the delete navigation. Making the build step
		// self-healing keeps the spec re-runnable instead of needing manual DB cleanup between attempts.
		for (const row of dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title = 'XSS Ref Test'`)) {
			await deleteMuseumForm(page, parseInt(row[0], 10));
		}

		// Created inside the existing Museum application (as 029/034 do) so the cleanup test can find and
		// delete it. Creating via aid=0 instead leaves it unassigned, where delete navigation cannot reach it.
		testFid = await createMuseumForm(page, 'XSS Ref Test', 'ID');
		expect(testFid).toBeGreaterThan(0);

		const newElementUrl = type => `/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${testFid}&aid=0&type=${type}`;

		// 1. The SOURCE field - the one that holds the payload and that everything else references.
		await page.goto(newElementUrl('text'));
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Source Field');
		await page.locator('input[name="elements-ele_handle"]').fill('ref_src');
		await saveAdminForm(page);

		// 2. CAPTION containing a {ref}, wrapped in admin markup that must survive.
		await page.goto(newElementUrl('text'));
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]')
			.fill(`Cap <span class="${CAP_WRAP}">{ref_src}</span>`);
		await page.locator('input[name="elements-ele_handle"]').fill('cap_target');
		await saveAdminForm(page);

		// 3. HELP TEXT containing a {ref} (no PHP), wrapped in admin markup that must survive.
		await page.goto(newElementUrl('text'));
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Help Plain');
		await page.locator('input[name="elements-ele_handle"]').fill('help_plain');
		await page.locator('textarea[name="elements-ele_desc"]')
			.fill(`Help <span class="${HELP_WRAP}">{ref_src}</span>`);
		await saveAdminForm(page);

		// 4. HELP TEXT containing admin-authored PHP *and* a {ref} in the surrounding text.
		//    evalPHPStrings replaces only the <?php ?> region with the code's return value; the {ref} in the
		//    text around it is substituted afterwards by the normal display path. This exercises both.
		await page.goto(newElementUrl('text'));
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Help PHP');
		await page.locator('input[name="elements-ele_handle"]').fill('help_php');
		await page.locator('textarea[name="elements-ele_desc"]')
			.fill(`<?php return "${PHP_RAN}"; ?> <span class="${PHP_WRAP}">{ref_src}</span>`);
		await saveAdminForm(page);

		// 5. A CAPTION containing what looks like PHP. Captions are never eval'd - this pins that down.
		await page.goto(newElementUrl('text'));
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]')
			.fill(`CapPHP <?php return "${CAP_PHP_SENTINEL}"; ?>`);
		await page.locator('input[name="elements-ele_handle"]').fill('cap_php');
		await saveAdminForm(page);
	});

	test('create an entry whose source field carries the payload', async ({ page }) => {
		await login(page, 'admin');
		await page.goto(`/modules/formulize/index.php?fid=${testFid}`);
		await page.getByRole('button', { name: 'Add XSS Ref Test', exact: true }).click();
		await page.getByRole('textbox', { name: 'ID *' }).fill('IDREF036');
		await page.getByLabel('Source Field').fill(PAYLOAD);
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // clears the entry lock

		// Resolve the entry id from the DB rather than scraping it out of the UI - the form view has to be
		// opened by id (?ve=), and a {ref} only resolves against a SAVED entry (on a new entry it is '').
		// NB dbQuery returns rows as arrays of column values (it runs mariadb with -N), NOT as objects -
		// so these are indexed [row][column], not accessed by column name.
		const handleRow = dbQuery(`SELECT form_handle FROM ${dbPrefix()}_formulize_id WHERE id_form = ${testFid}`);
		expect(handleRow.length, 'form handle should be resolvable').toBeGreaterThan(0);
		const formHandle = handleRow[0][0];
		const rows = dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_${formHandle} ORDER BY entry_id DESC LIMIT 1`);
		expect(rows.length, 'the entry should have been created').toBeGreaterThan(0);
		testEntryId = parseInt(rows[0][0], 10);
		expect(testEntryId).toBeGreaterThan(0);
	});

	test('caption: the referenced value is filtered, the admin markup around it survives', async ({ page }) => {
		page.on('dialog', async dialog => {
			throw new Error(`XSS executed - unexpected dialog fired: ${dialog.message()}`);
		});
		await login(page, 'admin');
		await page.goto(`/modules/formulize/index.php?fid=${testFid}&ve=${testEntryId}`);

		// POSITIVE first - these auto-wait and so double as the readiness gate. A toHaveCount(0) must never
		// go first: it would pass vacuously against a page that has not finished loading.
		const capWrap = page.locator(`span.${CAP_WRAP}`);
		await expect(capWrap).toHaveCount(1);          // admin's own markup survived
		await expect(capWrap).toContainText(MARKER);   // and the referenced value is still shown

		// SAFETY - the script vector must not survive. Scoped to the caption wrapper, not page-wide: the
		// form chrome legitimately carries its own on* handlers that have nothing to do with entry data.
		await expect(capWrap.locator('[onerror]')).toHaveCount(0);
		await expect(capWrap.locator('[onclick]')).toHaveCount(0);

		// The payload's <img> SURVIVES, by design - purification is an allow-list that keeps ordinary
		// formatting tags (including img) and strips only the script vectors, same as 034. A handler-less
		// <img> cannot execute anything, so its presence is the correct result, not a vulnerability.
		// Note {ref_src} is referenced from THREE places on this form (this caption, the plain help text,
		// and the PHP help text), so three purified copies legitimately exist - which is why this cannot be
		// asserted as "no probe img outside the caption".
		//
		// The two invariants that actually matter, asserted page-wide:
		//   1. no copy anywhere carries an event handler
		//   2. every copy is inside one of the three admin-authored wrappers - i.e. the payload's `">`
		//      never broke out of the text it was substituted into and landed somewhere else in the DOM
		await expect(page.locator(`img.${PROBE}[onerror], img.${PROBE}[onclick]`)).toHaveCount(0);
		await expect(page.locator(
			`img.${PROBE}:not(.${CAP_WRAP} img):not(.${HELP_WRAP} img):not(.${PHP_WRAP} img)`
		)).toHaveCount(0);
		expect(await page.evaluate(() => window.__xssRef036)).toBeUndefined();
	});

	test('help text: the referenced value is filtered, the admin markup around it survives', async ({ page }) => {
		page.on('dialog', async dialog => {
			throw new Error(`XSS executed - unexpected dialog fired: ${dialog.message()}`);
		});
		await login(page, 'admin');
		await page.goto(`/modules/formulize/index.php?fid=${testFid}&ve=${testEntryId}`);

		const helpWrap = page.locator(`span.${HELP_WRAP}`);
		await expect(helpWrap).toHaveCount(1);
		await expect(helpWrap).toContainText(MARKER);

		await expect(helpWrap.locator('[onerror]')).toHaveCount(0);
		await expect(helpWrap.locator('[onclick]')).toHaveCount(0);
		expect(await page.evaluate(() => window.__xssRef036)).toBeUndefined();
	});

	test('help text PHP: the admin code runs, and a {ref} in the surrounding text is still filtered', async ({ page }) => {
		page.on('dialog', async dialog => {
			throw new Error(`XSS executed - unexpected dialog fired: ${dialog.message()}`);
		});
		await login(page, 'admin');
		await page.goto(`/modules/formulize/index.php?fid=${testFid}&ve=${testEntryId}`);

		// The PHP region really was executed (its return value replaced the <?php ?> block)...
		await expect(page.locator('body')).toContainText(PHP_RAN);
		// ...and the {ref} in the text AROUND the code was substituted and filtered.
		const phpWrap = page.locator(`span.${PHP_WRAP}`);
		await expect(phpWrap).toHaveCount(1);
		await expect(phpWrap).toContainText(MARKER);
		await expect(phpWrap.locator('[onerror]')).toHaveCount(0);
		await expect(phpWrap.locator('[onclick]')).toHaveCount(0);
		expect(await page.evaluate(() => window.__xssRef036)).toBeUndefined();
	});

	test('caption PHP is NOT executed - captions are not an eval surface', async ({ page }) => {
		await login(page, 'admin');
		await page.goto(`/modules/formulize/index.php?fid=${testFid}&ve=${testEntryId}`);

		// evalPHPStrings has exactly one caller (the help-text path), so a <?php ?> block in a caption must
		// never run. The sentinel is the code's RETURN value - if it appears, the caption was eval'd.
		await expect(page.locator('body')).toContainText('CapPHP');       // the caption itself rendered
		await expect(page.locator('body')).not.toContainText(CAP_PHP_SENTINEL); // but its "code" did not run
	});

	test('cleanup: delete the throwaway form', async ({ page }) => {
		expect(testFid, 'the build test must have run first').toBeGreaterThan(0);
		await login(page, 'admin');
		await deleteMuseumForm(page, testFid);
	});
});
