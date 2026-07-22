const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, createMuseumForm, deleteMuseumForm, dbQuery, dbPrefix } from '../../utils';

/**
 * T3.5 - the EDITABLE form-display sinks (T3.1' / T3.2), and the D4 tripwire.
 *
 * This is the counterpart to 037: 037 covers the DISABLED render (value -> xoopsFormLabel -> the read-only
 * funnel); this covers the EDITABLE render, where the value goes into an `<input value="...">` attribute or
 * a `<textarea>` body via icms_form_elements_Text::render() / Textarea::render(). Different sink, different
 * failure mode - an editable value that is not escaped breaks OUT OF THE ATTRIBUTE (`">` closes it), which
 * is why every payload here starts with `">`.
 *
 * Two things are asserted for every field, and BOTH matter:
 *   - CORRECTNESS - toHaveValue(payload). The DOM `value` property is the DECODED string, so this asserts
 *     the value was escaped EXACTLY ONCE. A double-escaped value fails it (the user would see `&quot;` in
 *     their own text box), and so does a mangled or truncated one. This is the half a safety-only test
 *     misses, and it is the half that normalize-then-escape exists to protect.
 *   - SAFETY - no <img> at all. Unlike 034/036/037, where purification is an allow-list that legitimately
 *     KEEPS a handler-less <img>, an editable value is ESCAPED, not purified: it must produce no markup
 *     whatsoever. So `img.PROBE` toHaveCount(0) is the correct, strong assertion here.
 *
 * `email` is covered explicitly because it is the known-exposed case from X3: emailElement::loadValue()
 * returns the stored value untouched, so it relies entirely on the core sink (its editable branch is a
 * plain xoopsFormText - see emailElement::render()).
 *
 * The last test is the D4 TRIPWIRE. Text/textarea were historically safe in the form only because values
 * are HTML-escaped AT INTAKE; T3.1' is what makes the render sink self-sufficient. So that test writes RAW,
 * never-escaped payloads straight into the entry's table columns - exactly the state the database would be
 * in after D4 (remove intake escaping) lands - and asserts the form still renders them correctly and
 * inertly. If someone reverts the core sink, the earlier tests might still pass on intake escaping alone;
 * this one cannot. Planting via SQL is also the only practical way to get a payload into the email field,
 * whose intake validation is client-side (X7/T5.1) and would block the browser from submitting it.
 *
 * Requires enforcement ON (formulizeEnforceHtmlPurification = 1) - the suite default.
 */

// Every payload opens with `">` to attempt an attribute breakout, and carries an onerror side-effect.
// NB no single quotes and no `&` anywhere: single quotes would need escaping in the planted SQL, and an
// entity would drag in the accepted T3.4 fidelity tradeoff (`&copy;` -> `©`), which is pinned by
// tests/normalize_then_escape_test.php and is not what this spec is measuring.
const TEXT_MARKER = 'XSSEDTEXT039';
const TEXT_PROBE = 'xss-ed-text-039';
const TEXT_PAYLOAD = `${TEXT_MARKER}"><img src=x class="${TEXT_PROBE}" onerror="window.__xssEdText039=1">`;

const AREA_MARKER = 'XSSEDAREA039';
const AREA_PROBE = 'xss-ed-area-039';
const AREA_PAYLOAD = `${AREA_MARKER}"><img src=x class="${AREA_PROBE}" onerror="window.__xssEdArea039=1">`;

const MAIL_MARKER = 'XSSEDMAIL039';
const MAIL_PROBE = 'xss-ed-mail-039';
const MAIL_PAYLOAD = `${MAIL_MARKER}"><img src=x class="${MAIL_PROBE}" onerror="window.__xssEdMail039=1">`;

let testFid = 0;
let testEntryId = 0;
let formHandle = '';

// Shared assertion for one reopen of the entry in EDIT mode: every field shows exactly its payload, and
// none of them produced any markup. Taking the whole set in one pass keeps the two reopens identical, so
// the only difference between the intake-escaped case and the planted-raw case is how the data got there.
async function expectAllFieldsCorrectAndInert(page, expected) {
	// POSITIVE first - toHaveValue auto-waits, so it doubles as the readiness gate. A toHaveCount(0) must
	// never go first: it would pass vacuously against a page that has not finished loading.
	await expect(page.getByLabel('Edit Text')).toHaveValue(expected.text);
	// A plain Formulize textarea is not tied to its caption by a <label for>, so getByLabel does not find
	// it (see 021/037 - its id is markupName + '_tarea'). This form has exactly one textarea.
	await expect(page.locator('textarea[id$="_tarea"]')).toHaveValue(expected.area);
	await expect(page.getByLabel('Edit Email')).toHaveValue(expected.email);

	// SAFETY - escaped, not purified, so NO markup may survive anywhere on the page from any payload.
	await expect(page.locator(`img.${TEXT_PROBE}`)).toHaveCount(0);
	await expect(page.locator(`img.${AREA_PROBE}`)).toHaveCount(0);
	await expect(page.locator(`img.${MAIL_PROBE}`)).toHaveCount(0);
	// ...and no side-effect ran. (A page-wide [onerror] sweep is deliberately NOT used: the form chrome
	// carries its own legitimate handlers that have nothing to do with entry data - see 034.)
	expect(await page.evaluate(() => window.__xssEdText039)).toBeUndefined();
	expect(await page.evaluate(() => window.__xssEdArea039)).toBeUndefined();
	expect(await page.evaluate(() => window.__xssEdMail039)).toBeUndefined();
}

test.describe.serial('T3.5 - editable form sinks (text, textarea, email)', () => {

	test('build a throwaway form with text, plain-textarea and email elements', async ({ page }) => {
		await login(page, 'admin');

		// Self-heal any form left behind by an aborted previous run (see 036 for the rationale).
		for (const row of dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title = 'XSS Edit Test'`)) {
			await deleteMuseumForm(page, parseInt(row[0], 10));
		}

		testFid = await createMuseumForm(page, 'XSS Edit Test', 'ID');
		expect(testFid).toBeGreaterThan(0);

		const newElementUrl = type => `/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${testFid}&aid=0&type=${type}`;

		await page.goto(newElementUrl('text'));
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Edit Text');
		await page.locator('input[name="elements-ele_handle"]').fill('ed_text');
		await saveAdminForm(page);

		// Plain textarea - rich text deliberately left OFF. A rich-text value is purified (allow-list) and
		// would legitimately keep its <img>; a plain one is escaped and must not.
		await page.goto(newElementUrl('textarea'));
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Edit Area');
		await page.locator('input[name="elements-ele_handle"]').fill('ed_area');
		await saveAdminForm(page);

		// The X3 case: emailElement::loadValue() hands the stored value straight through, so its editable
		// render depends entirely on the core sink escaping it.
		await page.goto(newElementUrl('email'));
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Edit Email');
		await page.locator('input[name="elements-ele_handle"]').fill('ed_email');
		await saveAdminForm(page);
	});

	test('create an entry carrying payloads in the text and textarea fields', async ({ page }) => {
		await login(page, 'admin');
		await page.goto(`/modules/formulize/index.php?fid=${testFid}`);
		await page.getByRole('button', { name: 'Add XSS Edit Test', exact: true }).click();
		await page.getByRole('textbox', { name: 'ID *' }).fill('IDED039');
		await page.getByLabel('Edit Text').fill(TEXT_PAYLOAD);
		await page.locator('textarea[id$="_tarea"]').fill(AREA_PAYLOAD);
		// Email is left EMPTY here on purpose - its client-side validation would refuse to submit a payload.
		// It gets its value planted in the final test, which is where it belongs anyway (that test is about
		// the render sink standing on its own, with no intake in the picture at all).
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // clears the entry lock

		// Resolve ids from the DB - dbQuery returns rows as arrays of column values (mariadb -N), not objects.
		const handleRow = dbQuery(`SELECT form_handle FROM ${dbPrefix()}_formulize_id WHERE id_form = ${testFid}`);
		expect(handleRow.length, 'form handle should be resolvable').toBeGreaterThan(0);
		formHandle = handleRow[0][0];
		const entryRows = dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_${formHandle} ORDER BY entry_id DESC LIMIT 1`);
		expect(entryRows.length, 'the entry should have been created').toBeGreaterThan(0);
		testEntryId = parseInt(entryRows[0][0], 10);
		expect(testEntryId).toBeGreaterThan(0);
	});

	test('reopening the entry shows each payload verbatim in its box, and inert', async ({ page }) => {
		page.on('dialog', async dialog => {
			throw new Error(`XSS executed - unexpected dialog fired: ${dialog.message()}`);
		});
		await login(page, 'admin');
		await page.goto(`/modules/formulize/index.php?fid=${testFid}&ve=${testEntryId}`);

		// Email was never filled, so it is empty - the two payload fields are what is under test here.
		await expectAllFieldsCorrectAndInert(page, { text: TEXT_PAYLOAD, area: AREA_PAYLOAD, email: '' });
	});

	test('D4 tripwire: RAW unescaped values in the database still render correctly and inertly', async ({ page }) => {
		page.on('dialog', async dialog => {
			throw new Error(`XSS executed - unexpected dialog fired: ${dialog.message()}`);
		});
		expect(formHandle, 'the entry test must have run first').not.toBe('');

		// Overwrite all three columns with the payloads in their RAW form, bypassing intake entirely. This
		// is the post-D4 database state, and the only thing standing between it and a live XSS is the core
		// render sink. (The payloads contain no single quotes, so they need no escaping in this literal;
		// dbQuery passes the SQL as a single argv entry, never through a shell.)
		dbQuery(`UPDATE ${dbPrefix()}_formulize_${formHandle}
			SET ed_text = '${TEXT_PAYLOAD}', ed_area = '${AREA_PAYLOAD}', ed_email = '${MAIL_PAYLOAD}'
			WHERE entry_id = ${testEntryId}`);

		await login(page, 'admin');
		await page.goto(`/modules/formulize/index.php?fid=${testFid}&ve=${testEntryId}`);

		await expectAllFieldsCorrectAndInert(page, { text: TEXT_PAYLOAD, area: AREA_PAYLOAD, email: MAIL_PAYLOAD });
	});

	// Cleanup lives in afterAll rather than a trailing test(): test.describe.serial SKIPS every remaining
	// test once one fails, so a trailing cleanup test would never run on a failure and would leave the
	// throwaway form behind. afterAll runs either way. It needs its own page - afterAll is worker-scoped
	// and cannot use the test-scoped `page` fixture. (Same reasoning as 034.)
	test.afterAll(async ({ browser }) => {
		if (!testFid) { return; } // the build step never got far enough to create a form
		const page = await browser.newPage();
		await login(page, 'admin');
		await deleteMuseumForm(page, testFid);
		await page.close();
	});
});
