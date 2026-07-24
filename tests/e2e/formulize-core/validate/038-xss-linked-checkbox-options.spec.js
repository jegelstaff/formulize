const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, createMuseumForm, deleteMuseumForm, dbQuery, dbPrefix } from '../../utils';

/**
 * T3.6 - linked-element OPTION LABELS in the form render.
 *
 * The three core option elements (Select, Checkbox, Radio ::render()) escape the option VALUE but emit the
 * option LABEL raw ("... />$name</label>"). For a LINKED element that label is text pulled from ANOTHER
 * form's entries - user data - so it is a live vector in the EDITABLE form, not just the disabled view.
 * selectElement already escaped its linked option text; checkboxElement was fixed to match
 * ($ele_uitext = htmlSpecialChars(undoAllHTMLChars(...))). 035 covers the linked-SELECT LIST render; this
 * covers the linked-CHECKBOX FORM render, which is a different sink (the option <label>, not a list cell).
 *
 * Scenario (self-contained, self-cleaning):
 *   - a SOURCE form with a text element, one entry whose value is an XSS payload
 *   - a TARGET form with a checkboxLinked element pointing at that source element
 *   - opening the target's ADD form renders one checkbox whose LABEL is the source payload text:
 *       * the marker is present (the option rendered)
 *       * the payload is inert - its <img> did not become a live element and no on* handler survived
 *
 * Requires enforcement ON (the fresh-install default the suite runs under).
 */

const LINK_MARKER = 'CBLINKMARK038';
const LINK_PROBE = 'xss-cblink-probe-038';
const LINK_PAYLOAD = `${LINK_MARKER}<img src=x class="${LINK_PROBE}" onerror="window.__xssCbLink038=1">`;

let sourceFid = 0;
let targetFid = 0;

test.describe.serial('T3.6 - linked checkbox option labels are escaped in the form render', () => {

	test('build the source form and give it one payload entry', async ({ page }) => {
		await login(page, 'admin');

		// self-heal leftovers from an aborted run (see 036 for the rationale)
		for (const title of ['XSS CBLink Source', 'XSS CBLink Target']) {
			for (const row of dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title = '${title}'`)) {
				await deleteMuseumForm(page, parseInt(row[0], 10));
			}
		}

		sourceFid = await createMuseumForm(page, 'XSS CBLink Source', 'ID');
		expect(sourceFid).toBeGreaterThan(0);

		await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${sourceFid}&aid=0&type=text`);
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Source Name');
		await page.locator('input[name="elements-ele_handle"]').fill('cblink_source_name');
		await page.locator('input[name="elements-ele_colhead"]').fill('Source Name');
		await saveAdminForm(page);

		await page.goto(`/modules/formulize/index.php?fid=${sourceFid}`);
		await page.getByRole('button', { name: 'Add XSS CBLink Source', exact: true }).click();
		await page.getByRole('textbox', { name: 'ID *' }).fill('CBSRC038');
		await page.getByLabel('Source Name').fill(LINK_PAYLOAD);
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click();
	});

	test('build the target form with a linked checkbox onto the source', async ({ page }) => {
		await login(page, 'admin');
		targetFid = await createMuseumForm(page, 'XSS CBLink Target', 'ID');
		expect(targetFid).toBeGreaterThan(0);

		// Direct "new element" URL - the app form-listing DOM contains every form's Elements link at once,
		// so clicking through is unreliable (see 035's note). checkboxLinked is the linked-checkbox type.
		await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${targetFid}&aid=0&type=checkboxLinked`);
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Linked Choice');
		await page.locator('input[name="elements-ele_handle"]').fill('cblink_choice');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.locator('#formlink').selectOption('XSS CBLink Source: Source Name');
		await saveAdminForm(page);
	});

	test('the target ADD form renders the linked option label with the payload neutralised', async ({ page }) => {
		page.on('dialog', async dialog => {
			throw new Error(`XSS executed - unexpected dialog fired: ${dialog.message()}`);
		});
		await login(page, 'admin');

		// Open the target's ADD form: the linked checkbox renders one option whose LABEL is the source
		// entry's (payload) text. This is the option-<label> sink, exercised in the editable form.
		await page.goto(`/modules/formulize/index.php?fid=${targetFid}`);
		await page.getByRole('button', { name: 'Add XSS CBLink Target', exact: true }).click();

		// POSITIVE first (readiness gate + correctness): the option rendered and shows the marker text.
		await expect(page.locator('body')).toContainText(LINK_MARKER);

		// SAFETY: the payload's <img> did not become a live element in the option label, no on* handler
		// survived, and no dialog fired. (The <img> class probe count being 0 is the strong check here -
		// unlike purified rich text, an ESCAPED label contains no live <img> at all.)
		await expect(page.locator(`img.${LINK_PROBE}`)).toHaveCount(0);
		await expect(page.locator('[onerror]')).toHaveCount(0);
		expect(await page.evaluate(() => window.__xssCbLink038)).toBeUndefined();
	});

	test('cleanup: delete both throwaway forms', async ({ page }) => {
		expect(targetFid, 'the build tests must have run first').toBeGreaterThan(0);
		await login(page, 'admin');
		await deleteMuseumForm(page, targetFid);
		await deleteMuseumForm(page, sourceFid);
	});
});
