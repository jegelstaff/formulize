const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, createMuseumForm, deleteMuseumForm, dbQuery, dbPrefix } from '../../utils';

/**
 * T3.5 - eval-OUTPUT purification: admin-authored PHP that INTERPOLATES a referenced value.
 *
 * 036 covers {ref} substitution into captions and help text, but its PHP case deliberately returns a
 * CONSTANT - so it proves the eval runs without asserting anything about interpolation. This spec covers
 * the case 036 left open, and it is a genuinely separate code path, for a subtle reason:
 *
 * bindReferencesForPHPEval hands referenced values to the admin's code as real PHP VARIABLES rather than
 * splicing them in as text (that is what stops entry data from altering the code's structure). So code that
 * amounts to `return "Hi " . $some_field;` emits user data having performed NO { } substitution at all -
 * $substitutionHappened stays false and the purification inside formulize_replaceCurlyBracketVariables
 * never fires. The protection therefore cannot live in the substitution path; it has to be applied to the
 * eval's OUTPUT (elementrenderer.php, evalAdminPHPWithReferences). This spec is what proves that runs.
 *
 * Same admin-content trust model as 034/036: the admin's own markup is TRUSTED and must survive intact,
 * while the user-submitted value interpolated into it must lose its script vectors. Purification is an
 * allow-list, so the payload's handler-less <img> surviving is the CORRECT result, not a failure - the
 * assertions below are scoped accordingly.
 *
 * The last test pins the OTHER half of the rule. Output purification is applied only when a reference was
 * actually bound (`!empty($__formulizeRefValues)`): PHP with no {reference} in it produces output that is
 * 100% admin-authored, and that keeps its markup intact, interactive attributes included - the same
 * treatment an un-substituted caption gets (the T0.4 position). Asserting an onclick SURVIVES there looks
 * odd out of context, but it is the designed behaviour, and pinning it is what stops a future over-broad
 * purification from silently breaking every admin-built control in help text.
 *
 * Requires enforcement ON (formulizeEnforceHtmlPurification = 1) - the suite default.
 */

const MARKER = 'XSSPHP040';
const PROBE = 'xss-php-probe-040';
const PAYLOAD = `${MARKER}"><img src=x class="${PROBE}" onerror="window.__xssPhp040=1">`;

// Admin markup emitted BY the PHP, wrapped around the interpolated value - must survive purification.
const INTERP_WRAP = 'xss-interp-wrap-040';
// Admin markup emitted by PHP that references nothing - must survive UNpurified, handler included.
const NOREF_WRAP = 'xss-noref-wrap-040';

let testFid = 0;
let testEntryId = 0;

test.describe.serial('T3.5 - PHP interpolation of referenced values', () => {

	test('build the throwaway form: a source field, plus help text whose PHP interpolates it', async ({ page }) => {
		await login(page, 'admin');

		// Self-heal any form left behind by an aborted previous run (see 036 for the rationale).
		for (const row of dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title = 'XSS PHP Interp Test'`)) {
			await deleteMuseumForm(page, parseInt(row[0], 10));
		}

		testFid = await createMuseumForm(page, 'XSS PHP Interp Test', 'ID');
		expect(testFid).toBeGreaterThan(0);

		const newElementUrl = type => `/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${testFid}&aid=0&type=${type}`;

		// 1. The SOURCE field - holds the payload, and is what the PHP references.
		await page.goto(newElementUrl('text'));
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Interp Source');
		await page.locator('input[name="elements-ele_handle"]').fill('interp_src');
		await saveAdminForm(page);

		// 2. Help-text PHP that COMPOSES markup around the referenced value. {interp_src} inside the code is
		//    rewritten to a bound variable before the eval, so this is string interpolation of user data by
		//    admin code - not { } substitution - which is exactly the path under test. The <span> is the
		//    admin's own markup and must come through untouched.
		await page.goto(newElementUrl('text'));
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Interp Target');
		await page.locator('input[name="elements-ele_handle"]').fill('interp_target');
		await page.locator('textarea[name="elements-ele_desc"]')
			.fill(`<?php return "<span class='${INTERP_WRAP}'>Hi {interp_src}</span>"; ?>`);
		await saveAdminForm(page);

		// 3. Help-text PHP with NO reference at all - its output is entirely admin-authored, so it is left
		//    alone. The onclick here must SURVIVE; see the header comment.
		await page.goto(newElementUrl('text'));
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('No Ref Target');
		await page.locator('input[name="elements-ele_handle"]').fill('noref_target');
		await page.locator('textarea[name="elements-ele_desc"]')
			.fill(`<?php return "<button type='button' class='${NOREF_WRAP}' onclick='window.__adminRan040=1'>Go</button>"; ?>`);
		await saveAdminForm(page);
	});

	test('create an entry whose source field carries the payload', async ({ page }) => {
		await login(page, 'admin');
		await page.goto(`/modules/formulize/index.php?fid=${testFid}`);
		await page.getByRole('button', { name: 'Add XSS PHP Interp Test', exact: true }).click();
		await page.getByRole('textbox', { name: 'ID *' }).fill('IDPHP040');
		await page.getByLabel('Interp Source').fill(PAYLOAD);
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // clears the entry lock

		// The form view has to be opened by id (?ve=), and a reference only resolves against a SAVED entry.
		// dbQuery returns rows as arrays of column values (mariadb -N), not objects.
		const handleRow = dbQuery(`SELECT form_handle FROM ${dbPrefix()}_formulize_id WHERE id_form = ${testFid}`);
		expect(handleRow.length, 'form handle should be resolvable').toBeGreaterThan(0);
		const formHandle = handleRow[0][0];
		const rows = dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_${formHandle} ORDER BY entry_id DESC LIMIT 1`);
		expect(rows.length, 'the entry should have been created').toBeGreaterThan(0);
		testEntryId = parseInt(rows[0][0], 10);
		expect(testEntryId).toBeGreaterThan(0);
	});

	test('PHP that interpolates a reference: admin markup survives, the interpolated payload is filtered', async ({ page }) => {
		page.on('dialog', async dialog => {
			throw new Error(`XSS executed - unexpected dialog fired: ${dialog.message()}`);
		});
		await login(page, 'admin');
		await page.goto(`/modules/formulize/index.php?fid=${testFid}&ve=${testEntryId}`);

		// POSITIVE first (auto-waits, doubles as the readiness gate; a toHaveCount(0) must never lead).
		const wrap = page.locator(`span.${INTERP_WRAP}`);
		await expect(wrap).toHaveCount(1);            // the PHP ran and its own markup came through purification
		await expect(wrap).toContainText('Hi');       // ...including the literal text it composed
		await expect(wrap).toContainText(MARKER);     // ...and the interpolated value is still actually shown

		// SAFETY - the interpolated value's script vector did not survive. Scoped to the wrapper: the form
		// chrome legitimately carries its own on* handlers that have nothing to do with entry data (034).
		await expect(wrap.locator('[onerror]')).toHaveCount(0);
		await expect(wrap.locator('[onclick]')).toHaveCount(0);

		// Page-wide, the two invariants that actually matter:
		//   1. no copy of the payload anywhere carries an event handler
		//   2. every copy sits INSIDE the admin's wrapper - i.e. the payload's `">` never broke out of the
		//      string it was interpolated into and landed elsewhere in the DOM
		// The <img> itself survives by design (allow-list purification keeps ordinary formatting tags), so
		// it is not asserted away - same reasoning as 034/036.
		await expect(page.locator(`img.${PROBE}[onerror], img.${PROBE}[onclick]`)).toHaveCount(0);
		await expect(page.locator(`img.${PROBE}:not(.${INTERP_WRAP} img)`)).toHaveCount(0);
		expect(await page.evaluate(() => window.__xssPhp040)).toBeUndefined();
	});

	test('PHP with no reference is left alone - admin markup keeps its interactivity', async ({ page }) => {
		await login(page, 'admin');
		await page.goto(`/modules/formulize/index.php?fid=${testFid}&ve=${testEntryId}`);

		// No reference was bound, so the output is 100% admin-authored and purification correctly does not
		// run on it. This is the T0.4 position - admin content is trusted - and asserting it here is what
		// keeps a future broadening of the purification rule from silently stripping admin-built controls.
		const button = page.locator(`button.${NOREF_WRAP}`);
		await expect(button).toHaveCount(1);
		await expect(button).toHaveAttribute('onclick', /__adminRan040/);
	});

	// afterAll rather than a trailing test(): test.describe.serial skips everything after a failure, so a
	// trailing cleanup test would leave the throwaway form behind. See 034 for the full rationale.
	test.afterAll(async ({ browser }) => {
		if (!testFid) { return; }
		const page = await browser.newPage();
		await login(page, 'admin');
		await deleteMuseumForm(page, testFid);
		await page.close();
	});
});
