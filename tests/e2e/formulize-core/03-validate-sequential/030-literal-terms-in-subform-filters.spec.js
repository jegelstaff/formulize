const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';
import {
	login, saveAdminForm, waitForAdminPageReady, dbQuery, dbPrefix, elementId, ensureMainMenuOpen, leavePage, applyColumnChanges,
	openAllColumnSections, waitForWorkingMessage,
} from '../../utils';

/**
 * A condition works when its term is what people type, and not only when it is the value the element stores. Yes or
 * No for a Yes/No element, which stores codes, and a date written out, which is stored as YYYY-MM-DD. The stored values
 * still work as well, since conditions made before now use them. A list of terms with IN works, and a LIKE condition
 * on a date can name just the year. A negation includes artifacts with nothing in the field, {BLANK} finds them, and
 * "match all" and "match one or more" conditions work together, including { } references to the collection on screen.
 *
 * The same date handling is behind the search boxes on a list, so a date search is checked at the end, on the Artifacts list.
 *
 * The Artifacts subform in the Collections form is limited by each condition in turn, and the Weapons collection must
 * show the right weapons each time. Setup gave Weapons three weapons:
 *   Chinese Sword     donated       CE    acquired 1993-08-10
 *   Egyptian Chariot  not donated   BCE   acquired 2011-09-19
 *   French Musket     not donated   CE    acquired 1927-03-04
 * The subform has no conditions after setup. At the end the condition is removed, and the subform's settings must be
 * exactly what they were, so the museum is left as setup built it. Weapons is only looked at, never saved.
 */

const sword = 'Chinese Sword';
const chariot = 'Egyptian Chariot';
const musket = 'French Musket';

let settingsBefore = null; // the subform element's settings as setup left them

function subformSettings() {
	return dbQuery(`SELECT ele_value FROM ${dbPrefix()}_formulize WHERE ele_id = ${elementId('collections_subform_artifacts')}`)[0][0];
}

async function asAdmin(page) {
	await page.goto('/user.php?op=logout');
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
}
async function asCurator(page) {
	await page.goto('/user.php?op=logout');
	await login(page, 'curator1', '12345');
}

// Open the Options tab of the Artifacts subform element in the Collections form
async function openSubformOptions(page) {
	const eleId = elementId('collections_subform_artifacts');
	const fid = dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize WHERE ele_id = ${eleId}`)[0][0];
	const apps = dbQuery(`SELECT appid FROM ${dbPrefix()}_formulize_application_form_link WHERE fid = ${fid}`);
	await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=${eleId}&fid=${fid}&aid=${apps.length ? apps[0][0] : 0}`);
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Options' }).click();
	await expect(page.locator('#new_subformfilter_element')).toBeVisible();
}

// Remove every condition on the subform, one at a time, the way a webmaster does with each condition's X
async function removeConditions(page) {
	await openSubformOptions(page);
	const deleteLinks = page.locator('#filterdiv > a.conditionsdelete');
	while (await deleteLinks.count() > 0) {
		// the X saves the element and reloads the page
		await Promise.all([
			page.waitForNavigation({ waitUntil: 'networkidle', timeout: 120000 }),
			deleteLinks.first().click(),
		]);
		await openSubformOptions(page);
	}
}

// Limit the subform to artifacts that meet one condition, typed as a webmaster types it
async function limitSubformTo(page, elementLabel, op, term) {
	await asAdmin(page);
	await removeConditions(page);
	await page.locator('#new_subformfilter_element').selectOption({ label: elementLabel });
	await page.locator('#new_subformfilter_op').selectOption(op);
	await page.locator('#new_subformfilter_term').fill(term);
	await saveAdminForm(page);
	await openSubformOptions(page);
	await expect(page.locator('#filterdiv')).toContainText(`${elementLabel} ${op} ${term}`);
}

// Limit the subform to artifacts that meet several conditions. Each one is an array of the element, operator and term.
// The first set of boxes is "match all of these" and the second is "match one or more of these".
async function limitSubformToConditions(page, matchAll, matchOneOrMore) {
	await asAdmin(page);
	await removeConditions(page);
	for (const [set, conditions] of [['subformfilter', matchAll], ['subformfilter_oom', matchOneOrMore]]) {
		for (const [elementLabel, op, term] of conditions) {
			await page.locator(`#new_${set}_element`).selectOption({ label: elementLabel });
			await page.locator(`#new_${set}_op`).selectOption(op);
			await page.locator(`#new_${set}_term`).fill(term);
			await saveAdminForm(page);
			await openSubformOptions(page);
			await expect(page.locator('#filterdiv')).toContainText(`${elementLabel} ${op} ${term}`);
		}
	}
}

// Open the Weapons collection as curator1, check which artifacts its subform shows, and leave it without saving
async function expectWeaponsSubformToShow(page, shown, notShown) {
	await asCurator(page);
	await ensureMainMenuOpen(page);
	await page.locator('#mainmenu').getByRole('link', { name: 'Collections', exact: true }).click();
	await page.getByRole('row', { name: 'Weapons' }).getByRole('link').first().click();
	const subform = page.locator('.formulize-subform-table');
	await expect(subform).toBeVisible();
	for (const name of shown) {
		await expect(subform.getByText(name, { exact: true })).toBeVisible();
	}
	for (const name of notShown) {
		await expect(subform.getByText(name, { exact: true })).toHaveCount(0);
	}
	await leavePage(page); // releases the entry lock, and leaves Weapons as it was
	await expect(subform).not.toBeVisible();
}

// Not serial: each case takes away the conditions before it and sets its own, so a case that fails doesn't stop the rest from
// being tried. With one worker they still run in this order, and the first still takes settingsBefore.
test.describe('Literal terms in subform conditions', () => {

	test.beforeEach(() => {
		test.setTimeout(300000);
	});

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage();
		await asAdmin(page);
		await removeConditions(page);
		await page.close();
		if (settingsBefore !== null) {
			expect(subformSettings()).toBe(settingsBefore);
		}
	});

	test('with no conditions, the Weapons collection shows all its artifacts', async ({ page }) => {
		await asAdmin(page);
		await removeConditions(page); // anything an interrupted run left
		settingsBefore = subformSettings();
		await expectWeaponsSubformToShow(page, [sword, chariot, musket], []);
	});

	test('Donated to museum = Yes shows only the donated artifacts', async ({ page }) => {
		await limitSubformTo(page, 'Donated to museum', '=', 'Yes');
		await expectWeaponsSubformToShow(page, [sword], [chariot, musket]);
	});

	test('Donated to museum = No shows only the artifacts that were not donated', async ({ page }) => {
		await limitSubformTo(page, 'Donated to museum', '=', 'No');
		await expectWeaponsSubformToShow(page, [chariot, musket], [sword]);
	});

	test('Donated to museum LIKE %yes%, with wildcards typed around it, shows only the donated artifacts', async ({ page }) => {
		await limitSubformTo(page, 'Donated to museum', 'LIKE', '%yes%');
		await expectWeaponsSubformToShow(page, [sword], [chariot, musket]);
	});

	test('Donated to museum = 1, the code for Yes, still shows only the donated artifacts', async ({ page }) => {
		await limitSubformTo(page, 'Donated to museum', '=', '1');
		await expectWeaponsSubformToShow(page, [sword], [chariot, musket]);
	});

	test('Era IN BCE, a list of one, shows only the BCE artifacts', async ({ page }) => {
		await limitSubformTo(page, 'Era', 'IN', 'BCE');
		await expectWeaponsSubformToShow(page, [chariot], [sword, musket]);
	});

	test('Date of acquisition = August 10 1993, written out, shows only the artifact acquired that day', async ({ page }) => {
		await limitSubformTo(page, 'Date of acquisition', '=', 'August 10 1993');
		await expectWeaponsSubformToShow(page, [sword], [chariot, musket]);
	});

	test('Date of acquisition LIKE 2011, just the year, shows only the artifact acquired that year', async ({ page }) => {
		await limitSubformTo(page, 'Date of acquisition', 'LIKE', '2011');
		await expectWeaponsSubformToShow(page, [chariot], [sword, musket]);
	});

	test('Date of acquisition >= 2000, a year with a comparison, is from the start of that year', async ({ page }) => {
		await limitSubformTo(page, 'Date of acquisition', '>=', '2000');
		await expectWeaponsSubformToShow(page, [chariot], [sword, musket]);
	});

	test('Donor != Freeform Solutions includes the artifacts with no donor', async ({ page }) => {
		await limitSubformTo(page, 'Donor', 'NOT', 'Freeform Solutions');
		await expectWeaponsSubformToShow(page, [chariot, musket], [sword]);
	});

	test('Donor = {BLANK} shows the artifacts with no donor', async ({ page }) => {
		await limitSubformTo(page, 'Donor', '=', '{BLANK}');
		await expectWeaponsSubformToShow(page, [chariot, musket], [sword]);
	});

	test('Match all and match one or more together: not donated, and either BCE or acquired in 1993', async ({ page }) => {
		await limitSubformToConditions(page, [['Donated to museum', '=', 'No']], [['Era', '=', 'BCE'], ['Date of acquisition', 'LIKE', '1993']]);
		await expectWeaponsSubformToShow(page, [chariot], [sword, musket]);
	});

	test('Match all with a reference to the collection on screen, and match one or more', async ({ page }) => {
		await limitSubformToConditions(page, [['Collections', '=', '{collections_name}']], [['Era', '=', 'BCE'], ['Donor', '=', 'Freeform Solutions']]);
		await expectWeaponsSubformToShow(page, [sword, chariot], [musket]);
	});

	test('Match one or more with a negated reference to the collection on screen', async ({ page }) => {
		await limitSubformToConditions(page, [], [['Collections', 'NOT', '{collections_name}'], ['Era', '=', 'BCE']]);
		await expectWeaponsSubformToShow(page, [chariot], [sword, musket]);
	});
});

test.describe.serial('Date searches on a list', () => {

	// Search the Artifacts list by date of acquisition, the way a curator does. The column is added for this visit only.
	async function searchArtifactsByDate(page, term, shown, notShown) {
		const box = page.locator('input[name="search_artifacts_date_of_acquisition"]');
		await box.fill(term);
		await box.press('Enter');
		await waitForWorkingMessage(page);
		for (const name of shown) {
			await expect(page.getByRole('cell', { name, exact: true })).toBeVisible();
		}
		for (const name of notShown) {
			await expect(page.getByRole('cell', { name, exact: true })).toHaveCount(0);
		}
	}

	test('a year, a month, and something that is not a date', async ({ page }) => {
		test.setTimeout(300000);
		await login(page, 'curator1', '12345');
		await ensureMainMenuOpen(page);
		await page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true }).click();
		const popupPromise = page.context().waitForEvent('page');
		await page.getByRole('button', { name: 'Change columns' }).click();
		const columns = await popupPromise;
		await columns.bringToFront();
		await openAllColumnSections(columns);
		await columns.getByRole('checkbox', { name: 'Date of acquisition' }).check();
		await applyColumnChanges(columns);
		await expect(page.locator('input[name="search_artifacts_date_of_acquisition"]')).toBeVisible();

		await searchArtifactsByDate(page, '2011', [chariot], [sword, musket]);
		await searchArtifactsByDate(page, 'August 1993', [sword], [chariot, musket]);
		await searchArtifactsByDate(page, '>=2020', ['Roman Coin'], [sword, chariot, musket]);
		await searchArtifactsByDate(page, 'bread', [], [sword, chariot, musket, 'Roman Coin']);
		await leavePage(page);
	});
});
