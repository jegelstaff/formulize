const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, setCodeMirrorValue, createMuseumForm, deleteMuseumFormsTitled, conditionalChecksFinished, leavePage, autocompleteBox, dbQuery, dbPrefix, openEntry, entryArea } from '../../utils';

/**
 * A new value typed into an autocomplete is saved in the linked form once, when the entry is saved (issue #734).
 *
 * When the autocomplete governs a conditional element, choosing a value asks the server to redraw that element, and
 * the request carries the values on the page. That request used to save the new value in the linked form straight
 * away, and the save went through the linked form's before save procedure. When the entry was saved, Formulize looked
 * for an entry holding what was typed, to use it again. The procedure had changed it, so none matched, and the new
 * value was saved a second time.
 *
 * Here the procedure puts "Visitor: " in front of the name, and the autocomplete allows several values. Choosing a new
 * value must save nothing, and saving the entry must save it once, through the procedure.
 */

const SOURCE_TITLE = 'New Value Source 734';
const MAIN_TITLE = 'New Value Picker 734';
const TYPED = 'Jane Doe 734';
const STORED = `Visitor: ${TYPED}`;

let sourceFid = 0;
let mainFid = 0;

function dataTable(fid) {
	const rows = dbQuery(`SELECT form_handle FROM ${dbPrefix()}_formulize_id WHERE id_form = ${fid}`);
	expect(rows.length).toBe(1);
	return `${dbPrefix()}_formulize_${rows[0][0]}`;
}

function sourceEntries() {
	return dbQuery(`SELECT entry_id, nv734_name FROM ${dataTable(sourceFid)} ORDER BY entry_id`);
}

test.describe.serial('New values in an autocomplete are saved once', () => {

	test('build a linked form with a before save procedure, and a form with a multi-select autocomplete that allows new values and governs a condition', async ({ page }) => {
		await login(page, 'admin');
		await deleteMuseumFormsTitled(page, [MAIN_TITLE, SOURCE_TITLE]); // forms left behind by an aborted run

		sourceFid = await createMuseumForm(page, SOURCE_TITLE);
		expect(sourceFid).toBeGreaterThan(0);
		await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${sourceFid}&aid=0&type=text`);
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Name');
		await page.locator('input[name="elements-ele_handle"]').fill('nv734_name');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Name');

		const proceduresUrl = `/modules/formulize/admin/ui.php?page=form&fid=${sourceFid}&aid=0&tab=procedures`;
		await page.goto(proceduresUrl);
		await waitForAdminPageReady(page);
		await setCodeMirrorValue(page, 'textarea[name="forms-on_before_save"]', '<?php\n\n$nv734_name = \'Visitor: \'.$nv734_name;\n');
		await saveAdminForm(page);
		await page.goto(proceduresUrl);
		await waitForAdminPageReady(page);
		await expect(page.locator('textarea[name="forms-on_before_save"]')).toHaveValue(/Visitor: /);

		mainFid = await createMuseumForm(page, MAIN_TITLE);
		expect(mainFid).toBeGreaterThan(0);
		await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${mainFid}&aid=0&type=autocompleteLinked`);
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Pick 734');
		await page.locator('input[name="elements-ele_handle"]').fill('nv734_pick');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.locator('#formlink').selectOption(`${SOURCE_TITLE}: Name`);
		await page.getByRole('radio', { name: 'Allowed', exact: true }).check();
		await page.locator('[id="elements-ele_value[16]-1"]').check();
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Pick 734');

		// shown only when the autocomplete has a value it won't have, so choosing in the autocomplete asks for it to be redrawn
		await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${mainFid}&aid=0&type=text`);
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill('Note 734');
		await page.locator('input[name="elements-ele_handle"]').fill('nv734_note');
		await page.getByRole('link', { name: 'Display Settings' }).click();
		await page.locator('#new_elementfilter_element').selectOption('Pick 734');
		await page.locator('#new_elementfilter_term').fill('Nobody 734');
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText('Note 734');
	});

	test('choosing a new value saves nothing, and saving the entry saves it once', async ({ page }) => {
		await login(page, 'admin');
		const listUrl = `/modules/formulize/index.php?fid=${mainFid}`;
		await page.goto(listUrl);
		await openEntry(page, page.getByRole('button', { name: `Add ${MAIN_TITLE}`, exact: true }));

		await autocompleteBox(page, 'nv734_pick').fill(TYPED);
		const newValueItem = page.locator('ul.ui-autocomplete:visible li', { hasText: 'New Value:' });
		await expect(newValueItem).toHaveCount(1);
		await Promise.all([
			page.waitForResponse(response => response.url().includes('op=get_element_row_html')),
			newValueItem.click(),
		]);
		await conditionalChecksFinished(page);
		await expect(entryArea(page).locator('.auto_multi_label', { hasText: TYPED })).toHaveCount(1);
		expect(sourceEntries()).toEqual([]);

		await saveFormulizeForm(page);
		const saved = sourceEntries();
		expect(saved.length).toBe(1);
		expect(saved[0][1]).toBe(STORED);

		const picked = dbQuery(`SELECT nv734_pick FROM ${dataTable(mainFid)}`);
		expect(picked.length).toBe(1);
		expect(picked[0][0]).toMatch(new RegExp(`(^|,)${saved[0][0]}(,|$)`));

		await leavePage(page, listUrl);
		await expect(page.getByRole('button', { name: `Add ${MAIN_TITLE}`, exact: true })).toBeVisible();
	});

	// afterAll rather than a trailing test(): test.describe.serial skips everything after a failure, so a
	// trailing cleanup test would leave the throwaway forms behind. See 034 for the full rationale.
	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage();
		await login(page, 'admin');
		await deleteMuseumFormsTitled(page, [MAIN_TITLE, SOURCE_TITLE]);
		await page.close();
	});
});
