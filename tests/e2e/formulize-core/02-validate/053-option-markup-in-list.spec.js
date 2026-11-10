const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, createMuseumForm, deleteMuseumFormsTitled, applyColumnChanges, leavePage, autocompleteBox, dbQuery, dbPrefix } from '../../utils';

/**
 * Markup in the options of radio buttons, checkboxes and selects shows in a list the way the form shows it.
 *
 * An admin wrote the options, so a list draws a saved value that is exactly one of the options as it was written,
 * instead of escaping it into a wall of tags. That is formulizeBaseClassForListsElementHandler::valueIsAdminAuthored(),
 * called from the canonical safety step in formulizeElementsHandler::formatDataForList(). See
 * ai-context/xss-and-purification.md.
 *
 * Users can put text into two of these places, so the spec checks both halves, the admin's markup live and the
 * users' payloads inert:
 * - What a user types for an "Other" option is escaped in the list.
 * - A new value typed in an autocomplete that allows them becomes one of the element's options, so it is fully
 *   decoded and stripped of tags before it is added (formulizeSelectElementHandler::prepareDataForSaving()). The
 *   encoded payload is there because the Options tab decodes one level of entities each time an admin saves the
 *   element, so text that was only escaped would turn into markup after two saves. The spec does those saves.
 *
 * Requires enforcement ON (the suite default).
 */

const FORM_TITLE = 'Option Markup Test';

const RADIO_MARKER = 'OMRADIO053';
const RADIO_CLASS = 'om-radio-053';
const RADIO_OPTION = `<span class="${RADIO_CLASS}">${RADIO_MARKER}</span>`;

const CHECK_MARKER = 'OMCHECK053';
const CHECK_CLASS = 'om-check-053';
const CHECK_OPTION = `<i class="${CHECK_CLASS}">${CHECK_MARKER}</i>`;
const PLAIN_OPTION = 'Plain053';

const SELECT_MARKER = 'OMSELECT053';
const SELECT_CLASS = 'om-select-053';
const SELECT_OPTION = `<b class="${SELECT_CLASS}">${SELECT_MARKER}</b>`;

const OTHER_MARKER = 'OMOTHER053';
const OTHER_PROBE = 'om-other-053';
const OTHER_PAYLOAD = `${OTHER_MARKER}<img src=x class="${OTHER_PROBE}" onerror="window.__omOther053=1">`;

const AUTO_EXISTING = 'Existing053';
const AUTO_MARKER = 'OMAUTO053';
const AUTO_PROBE = 'om-auto-053';
const AUTO_PAYLOAD = `${AUTO_MARKER}<img src=x class="${AUTO_PROBE}" onerror="window.__omAuto053=1">`;
const ENCODED_MARKER = 'OMENC053';
const ENCODED_PROBE = 'om-enc-053';
const ENCODED_PAYLOAD = `${ENCODED_MARKER}&amp;lt;img src=x class="${ENCODED_PROBE}" onerror="window.__omEnc053=1"&amp;gt;`;

let testFid = 0;

test.describe.serial('Markup in options shows in lists', () => {

	test('build a form with option elements whose options have markup, and an autocomplete that allows new values', async ({ page }) => {
		await login(page, 'admin');
		await deleteMuseumFormsTitled(page, [FORM_TITLE]); // a form left behind by an aborted run

		testFid = await createMuseumForm(page, FORM_TITLE, 'ID');
		expect(testFid).toBeGreaterThan(0);

		const newElementUrl = type => `/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${testFid}&aid=0&type=${type}`;
		const elements = [
			{ type: 'radio', caption: 'Opt Radio', handle: 'om_radio', options: [RADIO_OPTION, '{OTHER|30}'] },
			{ type: 'checkbox', caption: 'Opt Check', handle: 'om_check', options: [CHECK_OPTION, PLAIN_OPTION] },
			{ type: 'select', caption: 'Opt Select', handle: 'om_select', options: [SELECT_OPTION, 'PlainSel053'] },
			{ type: 'autocomplete', caption: 'Opt Auto', handle: 'om_auto', options: [AUTO_EXISTING], allowNew: true },
		];
		for (const element of elements) {
			await page.goto(newElementUrl(element.type));
			await waitForAdminPageReady(page);
			await page.locator('input[name="elements-ele_caption"]').fill(element.caption);
			await page.locator('input[name="elements-ele_handle"]').fill(element.handle);
			await page.getByRole('link', { name: 'Options' }).click();
			for (let i = 0; i < element.options.length; i++) {
				if (i > 0) {
					await page.getByRole('button', { name: 'Add' }).click();
				}
				await page.locator(`input[name="ele_value\\[${i}\\]"]`).fill(element.options[i]);
			}
			if (element.allowNew) {
				await page.locator('[id="elements-ele_value[16]-1"]').check();
			}
			await saveAdminForm(page);
			await expect(page.getByRole('heading')).toContainText(element.caption);
		}
	});

	test('the form draws the radio and checkbox options as markup', async ({ page }) => {
		await login(page, 'admin');
		await page.goto(`/modules/formulize/index.php?fid=${testFid}`);
		await page.getByRole('button', { name: `Add ${FORM_TITLE}`, exact: true }).click();
		await expect(page.locator(`span.${RADIO_CLASS}`)).toHaveCount(1);
		await expect(page.locator(`i.${CHECK_CLASS}`)).toHaveCount(1);
		await leavePage(page, `/modules/formulize/index.php?fid=${testFid}`);
		await expect(page.getByRole('button', { name: `Add ${FORM_TITLE}`, exact: true })).toBeVisible();
	});

	test('save one entry that picks the options, and two with payloads typed by a user', async ({ page }) => {
		await login(page, 'admin');
		const listUrl = `/modules/formulize/index.php?fid=${testFid}`;
		const typeNewAutocompleteValue = async text => {
			await autocompleteBox(page, 'om_auto').fill(text);
			const newValueItem = page.locator('ul.ui-autocomplete:visible li', { hasText: 'New Value:' });
			await expect(newValueItem).toHaveCount(1);
			await newValueItem.click();
		};

		await page.goto(listUrl);
		await page.getByRole('button', { name: `Add ${FORM_TITLE}`, exact: true }).click();
		await page.getByRole('textbox', { name: 'ID *' }).fill('OM053A');
		await page.getByRole('radio', { name: RADIO_MARKER }).check();
		await page.getByRole('checkbox', { name: CHECK_MARKER }).check();
		await page.getByRole('checkbox', { name: PLAIN_OPTION }).check();
		await page.getByLabel('Opt Select').selectOption({ label: SELECT_MARKER });
		await saveFormulizeForm(page);
		await leavePage(page, listUrl);

		await page.getByRole('button', { name: `Add ${FORM_TITLE}`, exact: true }).click();
		await page.getByRole('textbox', { name: 'ID *' }).fill('OM053B');
		await page.getByRole('radio', { name: /^Other/ }).check();
		await page.locator('input[name^="other["]').fill(OTHER_PAYLOAD);
		await typeNewAutocompleteValue(AUTO_PAYLOAD);
		await saveFormulizeForm(page);
		await leavePage(page, listUrl);

		await page.getByRole('button', { name: `Add ${FORM_TITLE}`, exact: true }).click();
		await page.getByRole('textbox', { name: 'ID *' }).fill('OM053C');
		await typeNewAutocompleteValue(ENCODED_PAYLOAD);
		await saveFormulizeForm(page);
		await leavePage(page, listUrl);
		await expect(page.locator('body')).toContainText('OM053C');
	});

	test('the new values were added to the autocomplete\'s options with no tags, even after the admin saves it twice', async ({ page }) => {
		await login(page, 'admin');
		const eleRows = dbQuery(`SELECT ele_id FROM ${dbPrefix()}_formulize WHERE id_form = ${testFid} AND ele_handle = 'om_auto'`);
		expect(eleRows.length).toBe(1);
		const elementUrl = `/modules/formulize/admin/ui.php?page=element&ele_id=${eleRows[0][0]}&fid=${testFid}&aid=0`;
		const optionBoxes = page.locator('input[name^="ele_value["]');

		for (let save = 1; save <= 2; save++) {
			await page.goto(elementUrl);
			await waitForAdminPageReady(page);
			await page.getByRole('link', { name: 'Options' }).click();
			await expect(optionBoxes).toHaveCount(3); // the admin's option and the two a user added
			await saveAdminForm(page);
		}

		await page.goto(elementUrl);
		await waitForAdminPageReady(page);
		await page.getByRole('link', { name: 'Options' }).click();
		await expect(optionBoxes).toHaveCount(3);
		const options = await optionBoxes.evaluateAll(boxes => boxes.map(box => box.value));
		expect(options).toContain(AUTO_EXISTING);
		expect(options).toContain(AUTO_MARKER);
		expect(options).toContain(ENCODED_MARKER);
		for (const option of options) {
			expect(option).not.toContain('<');
			expect(option).not.toContain('&');
		}
	});

	test('the list shows the admin\'s markup live and the users\' payloads inert', async ({ page }) => {
		page.on('dialog', async dialog => {
			throw new Error(`XSS executed - unexpected dialog fired: ${dialog.message()}`);
		});
		await login(page, 'admin');
		await page.goto(`/modules/formulize/index.php?fid=${testFid}`);

		// the elements are not columns by default
		const popupPromise = page.context().waitForEvent('page');
		await page.getByRole('button', { name: 'Change columns' }).click();
		const popup = await popupPromise;
		await popup.bringToFront();
		for (const caption of ['Opt Radio', 'Opt Check', 'Opt Select', 'Opt Auto']) {
			await popup.getByRole('checkbox', { name: caption }).check();
		}
		await applyColumnChanges(popup);

		// Positive first, which also waits for the reloaded list: the admin's markup is live in the cells
		const radioCell = page.locator('div.main-cell-div', { has: page.locator(`span.${RADIO_CLASS}`) });
		await expect(radioCell).toHaveCount(1);
		await expect(radioCell.locator(`span.${RADIO_CLASS}`)).toHaveText(RADIO_MARKER);
		const checkCell = page.locator('div.main-cell-div', { has: page.locator(`i.${CHECK_CLASS}`) });
		await expect(checkCell).toHaveCount(1);
		await expect(checkCell.locator(`i.${CHECK_CLASS}`)).toHaveText(CHECK_MARKER);
		await expect(checkCell).toContainText(PLAIN_OPTION);
		const selectCell = page.locator('div.main-cell-div', { has: page.locator(`b.${SELECT_CLASS}`) });
		await expect(selectCell).toHaveCount(1);
		await expect(selectCell.locator(`b.${SELECT_CLASS}`)).toHaveText(SELECT_MARKER);
		// and no tags are showing as text
		await expect(radioCell).not.toContainText('<span');
		await expect(checkCell).not.toContainText('<i');
		await expect(selectCell).not.toContainText('<b');

		// What the user typed for Other is shown, escaped once, and nothing in it runs
		const otherCell = page.locator('div.main-cell-div', { hasText: OTHER_MARKER });
		await expect(otherCell).toHaveCount(1);
		await expect(otherCell).toContainText(`<img src=x class="${OTHER_PROBE}"`);
		await expect(page.locator(`img.${OTHER_PROBE}`)).toHaveCount(0);

		// The new autocomplete values lost their tags when they were added, so only the text is left
		for (const marker of [AUTO_MARKER, ENCODED_MARKER]) {
			const autoCell = page.locator('div.main-cell-div', { hasText: marker });
			await expect(autoCell).toHaveCount(1);
			await expect(autoCell).toHaveText(marker);
		}
		await expect(page.locator(`img.${AUTO_PROBE}`)).toHaveCount(0);
		await expect(page.locator(`img.${ENCODED_PROBE}`)).toHaveCount(0);

		expect(await page.evaluate(() => [window.__omOther053, window.__omAuto053, window.__omEnc053])).toEqual([undefined, undefined, undefined]);
	});

	// afterAll rather than a trailing test(): test.describe.serial skips everything after a failure, so a
	// trailing cleanup test would leave the throwaway form behind. See 034 for the full rationale.
	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage();
		await login(page, 'admin');
		await deleteMuseumFormsTitled(page, [FORM_TITLE]);
		await page.close();
	});
});
