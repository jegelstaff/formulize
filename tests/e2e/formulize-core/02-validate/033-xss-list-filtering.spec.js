const { test, expect } = require('@playwright/test');
import { login, saveFormulizeForm, dbQuery, dbPrefix } from '../../utils';

/**
 * T2 (list pipeline) security regression tests.
 *
 * These verify that user-submitted content is neutralised when it is rendered back into a list of
 * entries - the stored-XSS surface. The assertions are deliberately DOM/behaviour based, never a text
 * search of the page source: a substring check for the payload would PASS whether the payload is inert
 * text or a live, executing element. Instead we assert that:
 *   - no dialog fires (a firing alert/confirm/prompt means script executed),
 *   - the payload did NOT materialise as a live DOM element (the injected node does not exist),
 *   - no scripted side-effect ran (a window flag the payload would set stays unset),
 *   - the value still round-tripped and is shown to the user (as inert, escaped text).
 *
 * Runs after the setup specs (001-021) have created the museum forms, so it can build on the
 * Collections form that already exists in the post-test-run database state.
 */
test.describe('T2 - XSS filtering in list output', () => {

	// If rendered live, this creates a uniquely identifiable <img> whose onerror sets a window flag.
	// If correctly escaped, it is inert text: no <img>, no flag, and the visible marker survives.
	const PROBE_CLASS = 'xss-probe-033';
	const MARKER = 'XSSMARK033';
	const PLAIN_TEXT_PAYLOAD = `${MARKER}"><img src=x class="${PROBE_CLASS}" onerror="window.__xss033=1">`;

	test('a payload saved into a plain-text field is inert when shown in the list', async ({ page }) => {
		// Any dialog here means an injected script executed - fail loudly.
		page.on('dialog', async dialog => {
			throw new Error(`XSS executed - unexpected dialog fired: ${dialog.message()}`);
		});

		await login(page, 'ahstaff', '12345');

		// Create a Collection whose Name is the payload (Name is a plain-text element).
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Collections', exact: true }).click();
		await page.getByRole('button', { name: 'Add Collections', exact: true }).click();
		await page.getByRole('textbox', { name: 'Name *' }).fill(PLAIN_TEXT_PAYLOAD);
		await page.getByRole('checkbox', { name: 'Children' }).check();
		await page.getByRole('checkbox', { name: 'Adults' }).check();
		await saveFormulizeForm(page);
		await page.getByRole('link', { name: 'Save and Close' }).click(); // returns to the list, clears entry lock

		// Now on the Collections list, with the payload entry rendered in a cell.
		// 1. The payload must NOT have rendered as a live element.
		await expect(page.locator(`img.${PROBE_CLASS}`)).toHaveCount(0);
		// 2. Its onerror side-effect must not have run.
		expect(await page.evaluate(() => window.__xss033)).toBeFalsy();
		// 3. The value did round-trip and is shown as (escaped) text - the marker prefix is visible.
		await expect(page.locator('body')).toContainText(MARKER);
	});

	// The Collections form belongs to the museum setup, so remove the entry rather than the form - the way
	// the person who made it would, with Delete selected in the Collections list. In afterAll so it runs
	// even when the test fails, and it removes every payload entry, including any a killed run left behind.
	test.afterAll(async ({ browser }) => {
		const entryIds = dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_collections WHERE collections_name LIKE '${MARKER}%'`)
			.map(row => parseInt(row[0], 10));
		if (entryIds.length === 0) { return; }
		const page = await browser.newPage();
		await login(page, 'ahstaff', '12345');
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await page.locator('#mainmenu').getByRole('link', { name: 'Collections', exact: true }).click();
		for (const entryId of entryIds) {
			await page.locator(`#delete_${entryId}`).check();
		}
		page.once('dialog', dialog => dialog.accept()); // "You are about to delete the selected entries"
		await page.locator('#formulize_deleteButton').click();
		await expect(page.locator(`#delete_${entryIds[0]}`)).toHaveCount(0);
		await page.close();
		expect(dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_collections WHERE collections_name LIKE '${MARKER}%'`)).toEqual([]);
	});
});
