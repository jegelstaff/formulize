const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import {
	login, saveFormulizeForm, saveAdminForm, waitForAdminPageReady, createMuseumForm, dbQuery, dbPrefix, sqlEscape, leavePage, showPermissionsFor,
	selectAutocompleteOption, getUserByLogin, getUserGroupIds,
} from '../../utils';
import { formId, startElement, finishElement, makeRequired, linkToSource, inheritPermissionsFromArtifacts, setElementsOnOnlyPage, saveAndClose } from './museum-forms';
import { namesIn, namesOnPage } from '../condition-cases';

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
//
// Then Auditors, a group, and auditor1, its one member, who comes from outside the museum to check its records. They can see
// every artifact, and they aren't staff, so they're added on the Groups and Users pages, not through the forms the staff are
// entries in. Section 11 of conditions-and-searches.md gives Auditors a filter on Artifacts, and auditor1 opens the Catalogue
// list to see which artifacts it leaves them. A group's filter applies to everyone in the group, so no one else is in it.

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

const AUDITORS = 'Auditors';
const AUDITOR = 'auditor1';

const auditorsGroupId = () => parseInt(dbQuery(`SELECT groupid FROM ${dbPrefix()}_groups WHERE name = '${sqlEscape(AUDITORS)}'`)[0][0], 10);

test.describe('Auditors, who check the artifacts from outside the museum', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	});

	test('Create the Auditors group on the Groups page', async ({ page }) => {
		await page.goto('/modules/formulize/groups.php');
		await page.locator('#formulize_addButton').click();
		await page.getByRole('textbox', { name: 'Group Name' }).fill(AUDITORS);
		await saveFormulizeForm(page, 'Save');
		// the new group's form stays open after the save, so go back to the list to see it there
		await page.goto('/modules/formulize/groups.php');
		await page.waitForLoadState('networkidle');
		await expect(page.getByRole('rowgroup').last()).toContainText(AUDITORS);
	});

	test('Add auditor1 on the Users page, in Auditors and no other group', async ({ page }) => {
		await page.goto('/modules/formulize/users.php');
		await page.locator('#formulize_addButton').click();
		await page.getByRole('textbox', { name: 'First Name' }).fill('Auditor');
		await page.getByRole('textbox', { name: 'Last Name' }).fill('One');
		await page.getByRole('textbox', { name: 'Username' }).fill(AUDITOR);
		await page.getByRole('textbox', { name: 'Email Address' }).fill(`${AUDITOR}@museum.formulize.net`);
		// a password box and the box to type it again
		const passwordBoxes = page.locator('input[type="password"]');
		await passwordBoxes.nth(0).fill('12345');
		await passwordBoxes.nth(1).fill('12345');
		const groupsBox = page.locator('.form-row').filter({ hasText: 'Group Memberships' }).locator('input.formulize_autocomplete');
		await selectAutocompleteOption(page, groupsBox, AUDITORS);
		await expect(page.locator('p.auto_multi .auto_multi_label', { hasText: new RegExp(`^${AUDITORS}$`) })).toBeVisible();
		await saveFormulizeForm(page, 'Save');
		await leavePage(page); // users.php locks the users it shows
		const auditor = getUserByLogin(AUDITOR);
		expect(auditor, `${AUDITOR} should have an account`).not.toBeNull();
		expect(getUserGroupIds(auditor.uid)).toContain(auditorsGroupId());
	});

	test('Auditors can see every artifact', async ({ page }) => {
		const artifacts = formId('Artifacts');
		const aid = dbQuery(`SELECT appid FROM ${dbPrefix()}_formulize_application_form_link WHERE fid = ${artifacts}`)[0][0];
		await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${artifacts}&aid=${aid}`);
		await waitForAdminPageReady(page);
		await page.getByRole('link', { name: 'Permissions', exact: true }).click();
		await showPermissionsFor(page, [AUDITORS]);
		const auditors = page.getByRole('group', { name: AUDITORS });
		await auditors.locator('input[name$="_view_form"]').first().check();
		await auditors.locator('input[name$="_view_globalscope"]').first().check();
		await saveAdminForm(page);
	});

	test('auditor1 sees every artifact in the Catalogue list', async ({ page }) => {
		const catalogue = dbQuery(`SELECT sid FROM ${dbPrefix()}_formulize_screen WHERE screen_handle = 'artifact_catalogue'`)[0][0];
		await page.goto('/user.php?op=logout');
		await login(page, AUDITOR, '12345');
		await page.goto(`/modules/formulize/index.php?sid=${catalogue}`);
		await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
		const everything = namesIn('Artifact');
		expect(await namesOnPage(page.locator('[id^="celladdress_"]'), everything)).toEqual([...everything].sort());
	});
});
