const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, createMuseumForm, deleteMuseumFormsTitled, openEntry, entryArea, saveAndCloseEntry, leavePage, waitForWorkingMessage, goToListPage, dbQuery, dbPrefix } from '../../utils';
import { museumAppId, startElement, linkToSource, finishElement } from '../01-setup/museum-forms';

/**
 * A list sorted by a column from a connected form, where that column is a linked element, is in the order of what
 * the column shows, page by page. Issue #498.
 *
 * Artifact Enquiries are about an artifact, through a linked element, so the list of them can show the artifact's
 * Donor. That's a linked element too, onto the derived Name in Donors. It stores the donor's entry id, and the ids
 * are in a different order from the names, so a sort that used what's stored would show these in another order.
 *
 * A connected form's sort decides which entries are on each page, as well as their order, and the query that does
 * it depends on how many forms are connected: one, or more than one, when the entries go through a temporary
 * table. See "Two ways to build the query" in ai-context/formulize-module-overview.md. So the list shows three a
 * page, the first test checks with one connected form, and the next adds a second connection and checks again.
 */

const FORM_TITLE = 'Artifact Enquiries';

// In the order they're made, which is the order of their donors' entry ids, 1, 2, 3, 5 and 6
const ENQUIRIES = [
	{ artifact: 'Roman Coin', donor: 'François-Marie Arouet' },
	{ artifact: 'Persian necklace', donor: 'Emilie Du Châtelet' },
	{ artifact: 'Chinese Sword', donor: 'Freeform Solutions' },
	{ artifact: 'Bronze Mirror', donor: "Aoife O'Brien" },
	{ artifact: 'Silk Scroll', donor: 'Hart & Sons Antiques' },
];

// The artifacts, by their donors' names
const FIRST_PAGE_A_TO_Z = ['Bronze Mirror', 'Persian necklace', 'Roman Coin'];
const SECOND_PAGE_A_TO_Z = ['Chinese Sword', 'Silk Scroll'];
const FIRST_PAGE_Z_TO_A = ['Silk Scroll', 'Chinese Sword', 'Roman Coin'];

let fid = 0;

function listUrl() {
	return `/modules/formulize/index.php?fid=${fid}`;
}

// The Artifact column, which is the second, in the order the list shows the rows
function artifactCells(page) {
	return page.locator('tr.entry-row td.column1');
}

// The heading's link sorts the list. In Anari the heading can also have links to freeze the column and to more
// information, so the heading is found by the link, and the heading says how the list is sorted.
function donorSortLink(page) {
	return page.locator('th[scope="col"]').getByRole('link', { name: 'Donor', exact: true });
}

function donorHeading(page) {
	return page.locator('th[scope="col"]').filter({ has: donorSortLink(page) });
}

async function sortByDonor(page, direction) {
	await donorSortLink(page).click();
	await waitForWorkingMessage(page);
	await expect(donorHeading(page)).toHaveAttribute('aria-sort', direction);
}

async function expectSortedByDonorName(page) {
	await login(page, 'admin');
	await page.goto(listUrl());
	await expect(artifactCells(page)).toHaveCount(3);

	await sortByDonor(page, 'ascending');
	await expect(artifactCells(page)).toHaveText(FIRST_PAGE_A_TO_Z);
	await goToListPage(page, 2);
	await expect(artifactCells(page)).toHaveText(SECOND_PAGE_A_TO_Z);

	await goToListPage(page, 1);
	await sortByDonor(page, 'descending');
	await expect(artifactCells(page)).toHaveText(FIRST_PAGE_Z_TO_A);
}

test.describe.serial('Sorting a list by a linked element on a connected form', () => {

	test('make enquiries about artifacts, and a list of them that shows each artifact\'s donor, three a page', async ({ page }) => {
		await login(page, 'admin');
		await deleteMuseumFormsTitled(page, [FORM_TITLE]); // a form left behind by an aborted run

		fid = await createMuseumForm(page, FORM_TITLE, 'Enquiry');
		expect(fid).toBeGreaterThan(0);
		const [[enquiryHandle]] = dbQuery(`SELECT ele_handle FROM ${dbPrefix()}_formulize WHERE id_form = ${fid} AND ele_caption = 'Enquiry'`);

		await startElement(page, FORM_TITLE, 'selectLinked', 'Artifact', 'enquiries_artifact');
		await linkToSource(page, 'Artifacts: Short name');
		await finishElement(page, 'Artifact');

		for (const enquiry of ENQUIRIES) {
			await page.goto(listUrl());
			await openEntry(page, page.getByRole('button', { name: /^Add Artifact Enquir(y|ies)$/ }));
			await entryArea(page).getByRole('textbox', { name: 'Enquiry' }).fill(`About the ${enquiry.artifact}`);
			await entryArea(page).locator('.form-row:has(.formulize-label-enquiries_artifact) select').selectOption({ label: enquiry.artifact });
			await saveFormulizeForm(page);
			await saveAndCloseEntry(page);
		}

		// the list's columns, and three entries a page, so the five are on two pages
		const [[sid]] = dbQuery(`SELECT defaultlist FROM ${dbPrefix()}_formulize_id WHERE id_form = ${fid}`);
		const screenUrl = `/modules/formulize/admin/ui.php?page=screen&aid=${museumAppId(fid)}&fid=${fid}&sid=${sid}`;
		const columns = [enquiryHandle, 'enquiries_artifact', 'artifacts_donor'];
		await page.goto(screenUrl);
		await waitForAdminPageReady(page);
		await page.getByRole('link', { name: 'Entries' }).click();
		for (const [i, handle] of columns.entries()) {
			if (i > 0) {
				await page.getByRole('button', { name: 'Add Column' }).click();
			}
			await page.locator(`#cols-${i}`).selectOption(handle);
		}
		await page.locator('#screens-entriesperpage').fill('3');
		await saveAdminForm(page);
		await page.goto(screenUrl);
		await waitForAdminPageReady(page);
		await page.getByRole('link', { name: 'Entries' }).click();
		for (const [i, handle] of columns.entries()) {
			await expect(page.locator(`#cols-${i}`)).toHaveValue(handle);
		}
		await expect(page.locator('#screens-entriesperpage')).toHaveValue('3');

		// the list shows three a page now. The tests below read both pages, so they show that every enquiry was saved
		await page.goto(listUrl());
		await expect(artifactCells(page)).toHaveCount(3);
		await leavePage(page);
	});

	test('with one connected form, the pages go by the donors\' names', async ({ page }) => {
		await expectSortedByDonorName(page);
		await leavePage(page);
	});

	test('with two connected forms, the pages go by the donors\' names', async ({ page }) => {
		await login(page, 'admin');
		// a second link, so the list's entries go through the temporary table
		await startElement(page, FORM_TITLE, 'selectLinked', 'Collection of interest', 'enquiries_collection');
		await linkToSource(page, 'Collections: Name');
		await finishElement(page, 'Collection of interest');

		await expectSortedByDonorName(page);
		await leavePage(page);
	});

	// afterAll rather than a trailing test(), because serial mode skips every test after a failure
	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage();
		await login(page, 'admin');
		await deleteMuseumFormsTitled(page, [FORM_TITLE]);
		await page.close();
	});
});
