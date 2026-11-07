const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, saveFormulizeForm, waitForAdminPageReady, createMuseumForm, dbQuery, dbPrefix } from '../../utils';
import { formId, startElement, finishElement, makeRequired, linkToSource, inheritPermissionsFromArtifacts, setElementsOnOnlyPage, saveAndClose } from './museum-forms';

// Donors' visits to the museum, and the things they offer to give while they're there, added so that a condition can go
// through a linked element to the form the { } is in. conditions-and-searches.md lists what the conditions should find, in
// section 4.
//
//   Visits    Name, and the Donor who came. Follow up on is a list of Offers, so when a donor comes back, an offer from
//             one of their visits can be followed up. That list is what the conditions narrow.
//   Offers    The Item offered, and the Visit it was offered at. One came by post, so it has no visit.
//
// Offers point at Visits, and Visits have a list of Offers, so these two forms have a link each way. The extraction layer
// merges two links between the same pair of forms into one alias (see self-referential-forms-plan.md), so the forms other
// specs use never have two, and no other spec uses these. There's no list or relationship screen for them. Follow up on is
// made after the entries, so the second link isn't there while they're made.
//
// Visits links to Donors, the same as Artifacts and Loan Requests do. There are no menu entries for these forms, so the menus
// other specs check don't change. They inherit their permissions from Artifacts, so curator1 can see them and add to them,
// the same as the forms 022 adds.

test.use({ baseURL: E2E_TEST_BASE_URL });

test.describe.configure({ mode: 'serial' });

// donor is someone in Donors. Leave it out for a visitor who isn't a donor.
const VISITS = [
	{ name: 'Arouet in March', donor: 'François-Marie Arouet' },
	{ name: 'Arouet in April', donor: 'François-Marie Arouet' },
	{ name: 'Du Châtelet in March', donor: 'Emilie Du Châtelet' },
	{ name: 'Walk-in in April' },
];

// visit is the visit it was offered at. Leave it out for an offer that came by post.
const OFFERS = [
	{ item: 'Letters', visit: 'Arouet in March' },
	{ item: 'Snuff box', visit: 'Arouet in April' },
	{ item: 'Telescope', visit: 'Du Châtelet in March' },
	{ item: 'Unlabelled box', visit: 'Walk-in in April' },
	{ item: 'Sketchbook' },
];

async function addEntry(page, formTitle, fill) {
	await login(page, 'curator1', '12345');
	await page.goto(`/modules/formulize/index.php?fid=${formId(formTitle)}`);
	const add = page.getByRole('button', { name: new RegExp(`^Add ${formTitle.replace(/s$/, '')}s?$`) });
	await add.click();
	await fill();
	await saveFormulizeForm(page);
	await saveAndClose(page);
	await expect(add).toBeVisible();
}

test.describe('Build the Visits and Offers forms', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.goto('/modules/formulize/admin/ui.php?page=home');
		await waitForAdminPageReady(page);
	});

	test('Create the Visits form', async ({ page }) => {
		expect(await createMuseumForm(page, 'Visits')).toBeGreaterThan(0);
	});

	test('Create the Offers form', async ({ page }) => {
		expect(await createMuseumForm(page, 'Offers')).toBeGreaterThan(0);
	});

	test('Create Visit Name Element', async ({ page }) => {
		await startElement(page, 'Visits', 'text', 'Name', 'visits_name');
		await makeRequired(page);
		await finishElement(page, 'Name');
	});

	test('Create Visit Donor Element', async ({ page }) => {
		await startElement(page, 'Visits', 'selectLinked', 'Donor', 'visits_donor');
		await linkToSource(page, 'Donors: Name');
		await finishElement(page, 'Donor');
	});

	test('Create Offer Item Element', async ({ page }) => {
		await startElement(page, 'Offers', 'text', 'Item', 'offers_item');
		await makeRequired(page);
		await finishElement(page, 'Item');
	});

	test('Create Offer Visit Element', async ({ page }) => {
		await startElement(page, 'Offers', 'selectLinked', 'Visit', 'offers_visit');
		await linkToSource(page, 'Visits: Name');
		await finishElement(page, 'Visit');
	});

	test('Visits inherits permissions from Artifacts', async ({ page }) => {
		await inheritPermissionsFromArtifacts(page, 'Visits');
	});

	test('Offers inherits permissions from Artifacts', async ({ page }) => {
		await inheritPermissionsFromArtifacts(page, 'Offers');
	});

	test('Visit form screen', async ({ page }) => {
		await setElementsOnOnlyPage(page, 'Visits', 'Visit', ['Visits: Name', 'Visits: Donor']);
	});

	test('Offer form screen', async ({ page }) => {
		await setElementsOnOnlyPage(page, 'Offers', 'Offer', ['Offers: Item', 'Offers: Visit']);
	});
});

test.describe('Data entry for Visits and Offers', () => {

	for (const visit of VISITS) {
		test(`Visit: ${visit.name}`, async ({ page }) => {
			await addEntry(page, 'Visits', async () => {
				await page.locator('.form-row:has(.formulize-label-visits_name) input[type="text"]').fill(visit.name);
				if (visit.donor) {
					await page.locator('.form-row:has(.formulize-label-visits_donor) select').selectOption({ label: visit.donor });
				}
			});
		});
	}

	for (const offer of OFFERS) {
		test(`Offer: ${offer.item}`, async ({ page }) => {
			await addEntry(page, 'Offers', async () => {
				await page.locator('.form-row:has(.formulize-label-offers_item) input[type="text"]').fill(offer.item);
				if (offer.visit) {
					await page.locator('.form-row:has(.formulize-label-offers_visit) select').selectOption({ label: offer.visit });
				}
			});
		});
	}
});

test.describe('The Follow up on list in Visits', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.goto('/modules/formulize/admin/ui.php?page=home');
		await waitForAdminPageReady(page);
	});

	test('Create Follow up on Element in Visits', async ({ page }) => {
		// nothing is chosen in it, so on a saved visit it offers exactly what its conditions find
		await startElement(page, 'Visits', 'selectLinked', 'Follow up on', 'visits_follow_up');
		await linkToSource(page, 'Offers: Item');
		await finishElement(page, 'Follow up on');
	});

	test('Follow up on is on the Visit form screen', async ({ page }) => {
		await setElementsOnOnlyPage(page, 'Visits', 'Visit', ['Visits: Name', 'Visits: Donor', 'Visits: Follow up on']);
	});
});

// Only what's stored is checked here. 045 opens the visits, so if the link each way stops a visit from drawing, the rows that
// use it say so, and the rest of the run isn't stopped.
test.describe('What was built', () => {

	test('Each visit has its donor', () => {
		const rows = dbQuery(`SELECT v.visits_name, IFNULL((SELECT d.donors_name FROM ${dbPrefix()}_formulize_donors AS d WHERE d.entry_id = v.visits_donor), 'nobody')
			FROM ${dbPrefix()}_formulize_visits AS v`);
		expect(Object.fromEntries(rows)).toEqual(Object.fromEntries(VISITS.map(visit => [visit.name, visit.donor ?? 'nobody'])));
	});

	test('Each offer has the visit it was offered at', () => {
		const rows = dbQuery(`SELECT o.offers_item, IFNULL((SELECT v.visits_name FROM ${dbPrefix()}_formulize_visits AS v WHERE v.entry_id = o.offers_visit), 'none')
			FROM ${dbPrefix()}_formulize_offers AS o`);
		expect(Object.fromEntries(rows)).toEqual(Object.fromEntries(OFFERS.map(offer => [offer.item, offer.visit ?? 'none'])));
	});

	test('Nothing is chosen in Follow up on', () => {
		expect(dbQuery(`SELECT COUNT(*) FROM ${dbPrefix()}_formulize_visits WHERE visits_follow_up IS NOT NULL AND visits_follow_up != ''`)[0][0]).toBe('0');
	});
});
