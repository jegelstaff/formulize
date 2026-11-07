const { test, expect } = require('@playwright/test');
import { E2E_TEST_BASE_URL } from '../config';
import { login, dbQuery, dbPrefix, sqlEscape } from '../../utils';
import { casesFor, howToCheck, namesIn, expectedNames, namesOnPage, searchesFor, searchList, disagreementIn, checkRow, ARTIFACT_ELEMENTS, DONOR_ELEMENTS } from '../condition-cases';

// Section 7 of conditions-and-searches.md: terms typed in the search boxes on a list, and the entries that are then on the page.
// Searches go through formulize_parseFilter(), which is different code from conditions, so a fix to one doesn't fix the other.
// Every row is one search on a fresh load of the list.
//
// This only reads, so it's here rather than in 03-validate-sequential. The two lists are in 022, and nothing else uses them.
//
// Each test goes through all of its rows and reports every row that disagrees at the end, because one run on GitHub should show
// all of them, not one at a time. A row that can't be checked, ie: the page doesn't draw, is one of them, and the rest carry on.

test.use({ baseURL: E2E_TEST_BASE_URL });

const LISTS = [
	{ name: 'Catalogue', handle: 'artifact_catalogue', names: 'Artifact', elements: ARTIFACT_ELEMENTS },
	{ name: 'Donor artifacts', handle: 'donor_artifacts', names: 'Donor', elements: { ...ARTIFACT_ELEMENTS, ...DONOR_ELEMENTS } },
];

function screenId(handle) {
	return dbQuery(`SELECT sid FROM ${dbPrefix()}_formulize_screen WHERE screen_handle = '${sqlEscape(handle)}'`)[0][0];
}

for (const list of LISTS) {
	test(`Searches on the ${list.name} list`, async ({ page }) => {
		const rows = casesFor('B').filter(row => row['List'] === list.name);
		test.setTimeout(rows.length * 40000 + 60000); // room for rows that can't be checked, which each wait out the expect timeout
		const { selector } = howToCheck('B');
		const everything = namesIn(list.names);
		const listUrl = `/modules/formulize/index.php?sid=${screenId(list.handle)}`;
		const disagreements = [];

		await login(page, 'curator1', '12345');
		for (const row of rows) {
			await checkRow(page, row['Row'], disagreements, async () => {
				await searchList(page, listUrl, searchesFor(row, list.elements));
				const disagreement = disagreementIn(row['Row'], expectedNames(row['Should show'], everything), await namesOnPage(page.locator(selector), everything));
				if (disagreement) {
					disagreements.push(disagreement);
				}
			});
		}
		expect(disagreements, disagreements.join('\n')).toEqual([]);
	});
}
