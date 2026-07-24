const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, applyColumnChanges, createMuseumForm, deleteMuseumForm } from '../../utils';

/**
 * T2.10 - a clickable-in-list linked select must render its value as an escaped <a> pointing at the right
 * source entry. This is the branch that moved into selectElement::composeMarkupForList; the museum forms
 * have no clickable-in-list select, so this builds its own.
 *
 * Scenario (self-contained, self-cleaning):
 *   - a SOURCE form with a text element, holding ONE entry whose value is an XSS payload
 *   - a TARGET form with a selectLinked element pointing at that source element, "clickable in lists" = Yes
 *   - an entry in the target that selects the payload source entry
 *   - the target's list shows the linked value as <a href='...fid=<source>&ve=<entry>'>escaped payload</a>:
 *       * the link goes to the source form (composeMarkupForList built the right href)
 *       * the payload is inert text in the link, not a live element (escaped via the canonical path)
 *
 * Requires enforcement ON (the fresh-install default the suite runs under).
 */

const LINK_MARKER = 'LINKMARK035';
const LINK_PROBE = 'xss-link-probe-035';
const LINK_PAYLOAD = `${LINK_MARKER}"><img src=x class="${LINK_PROBE}" onerror="window.__xssLink035=1">`;

let sourceFid = 0;
let targetFid = 0;

test.describe.serial('T2.10 - clickable linked select renders an escaped link', () => {

	test('build the source form and give it one payload entry', async ({ page }) => {
		await login(page, 'admin');
		sourceFid = await createMuseumForm(page, 'XSS Link Source', 'ID');
		expect(sourceFid).toBeGreaterThan(0);

		// a text element to hold the (payload) value that the target will link to
		await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${sourceFid}&aid=0&type=text`);
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Source Name');
		await page.locator('input[name="elements-ele_handle"]').fill('xss_source_name');
		await page.locator('input[name="elements-ele_colhead"]').fill('Source Name');
		await saveAdminForm(page);

		// one entry, value = payload
		await page.goto(`/modules/formulize/index.php?fid=${sourceFid}`);
		await page.getByRole('button', { name: 'Add XSS Link Source', exact: true }).click();
		await page.getByRole('textbox', { name: 'ID *' }).fill('SRC035');
		await page.getByLabel('Source Name').fill(LINK_PAYLOAD);
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click();
	});

	test('build the target form with a clickable linked select onto the source', async ({ page }) => {
		await login(page, 'admin');
		targetFid = await createMuseumForm(page, 'XSS Link Target', 'ID');
		expect(targetFid).toBeGreaterThan(0);

		// Navigate straight to the "new element" admin URL rather than clicking through the app/form
		// listing: every form's "Elements" link is present in that page's DOM at once (form_listing.html
		// loops over ALL forms in the app), so a bare .first() always lands on whichever form sorts first
		// alphabetically ("Artifacts") instead of the one just clicked - silently creating the element on
		// the wrong form. See 034's use of the same direct-URL approach.
		await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${targetFid}&aid=0&type=selectLinked`);
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Linked Value');
		await page.locator('input[name="elements-ele_handle"]').fill('xss_linked_value');
		await page.locator('input[name="elements-ele_colhead"]').fill('Linked Value');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.locator('#formlink').selectOption('XSS Link Source: Source Name');
		// "make these values clickable in lists" - the element radio (ele_value index 7) that triggers the
		// composeMarkupForList link-building branch under test.
		await page.locator('input[name="elements-ele_value[7]"][value="1"]').check();
		await saveAdminForm(page);
	});

	test('an entry that selects the payload source entry shows an escaped link to it', async ({ page }) => {
		page.on('dialog', async dialog => {
			throw new Error(`XSS executed - unexpected dialog fired: ${dialog.message()}`);
		});
		await login(page, 'admin');

		// create the target entry, selecting the (only) source entry - by index 1, past the "none" option,
		// to avoid matching a payload-laden option label
		await page.goto(`/modules/formulize/index.php?fid=${targetFid}`);
		await page.getByRole('button', { name: 'Add XSS Link Target', exact: true }).click();
		await page.getByRole('textbox', { name: 'ID *' }).fill('TGT035');
		await page.getByLabel('Linked Value').selectOption({ index: 1 });
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click();
		// "Save and Close" releases the entry lock via async AJAX and then navigates itself (a POST-based
		// screen reload, not a plain redirect) - both in flight after the click resolves. Unlike 034/036,
		// which end their test right here and let the next test start a fresh page, this test keeps going
		// on the SAME page - so the explicit goto below can fire while that navigation is still in progress
		// and get net::ERR_ABORTED when the click's own navigation wins the race. Let it settle first.
		await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});

		// view the target list, reveal the linked column
		await page.goto(`/modules/formulize/index.php?fid=${targetFid}`);
		const popupPromise = page.context().waitForEvent('page');
		await page.getByRole('button', { name: 'Change columns' }).click();
		const popup = await popupPromise;
		await popup.bringToFront();
		await popup.getByRole('checkbox', { name: 'Linked Value' }).check();
		await applyColumnChanges(popup);

		// POSITIVE first (readiness gate + correctness): a clickable link into the SOURCE form exists, and
		// the payload marker is present as its (escaped) text.
		const link = page.locator(`a[href*="fid=${sourceFid}&ve="][target="_blank"]`);
		await expect(link).toHaveCount(1);
		await expect(link).toContainText(LINK_MARKER);

		// NEGATIVE (safety), now that the page has rendered: the payload is inert text in the link, not a
		// live element, and nothing carries an executable handler.
		await expect(page.locator(`img.${LINK_PROBE}`)).toHaveCount(0);
		await expect(page.locator(`a[href*="fid=${sourceFid}&ve="] [onerror]`)).toHaveCount(0);
	});

	test('cleanup: delete both throwaway forms', async ({ page }) => {
		expect(targetFid, 'the build tests must have run first').toBeGreaterThan(0);
		await login(page, 'admin');
		await deleteMuseumForm(page, targetFid);
		await deleteMuseumForm(page, sourceFid);
	});
});
