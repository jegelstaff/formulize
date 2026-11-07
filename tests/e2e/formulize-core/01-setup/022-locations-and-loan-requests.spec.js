const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, saveAdminForm, saveFormulizeForm, waitForAdminPageReady, editScreenPage, conditionalElementReady, createMuseumForm, leavePage, elementId, dbQuery, dbPrefix, sqlEscape } from '../../utils';

// Where artifacts are kept and who asks to borrow them, added to the museum so that conditions and searches can be tried
// on the page, with the kinds of values they have to cope with. The specs in 02-validate and 03-validate-sequential
// that do that rely on what's here, and conditions-and-searches.md lists what each one should find.
//
//   Locations        Name, and a subform of the artifacts stored there. Every artifact is in Main Vault, so that subform
//                    can show any artifact. A subform only ever shows entries connected to the entry that's open.
//   Loan Requests    What a borrower is asking for, and the Artifact they want. Those fields are what { } terms refer
//                    to when a condition narrows the Artifact list to what the borrower asked for. Storage they have
//                    is Climate controlled unless someone says otherwise, so a { } term has a default to refer to.
//   Artifacts        Location, Materials, On loan, Provenance, Storage, Appraiser email and Curator note, on two new tabs of
//                    the Artifact screen, and a subform of the Loan Requests for the artifact. Storage has text people see
//                    that's different from what's stored, and only the Roman Coin has a value. A third tab, Cataloguing,
//                    has who catalogued it, another artifact it's part of a set with, and how to handle it, which is
//                    checkboxes with text people see that's different from what's stored.
//   New entries      Three artifacts, with dates on either side of a new year and one with nothing but the required
//                    fields, and two donors whose names have ' and & in them. Hart & Sons Antiques are in Prince Edward
//                    Island, which is stored as 10, so a search for Ontario, which is 1, can show it isn't found too.
//
// This lives in 01-setup/ because it creates forms and elements, and it's numbered after 021 so that the entries made
// in 020 aren't disturbed. The Artifact screen only gets new tabs, but "Add Artifact" opens a form with every element on
// one page, so the new elements are there too. On loan has a Yes and a No of its own, which is why the radios below are
// found in their element's row.
//
// Blanks are stored in two ways, and searches have to treat them the same, so both are here on purpose. A text box left
// blank is saved as NULL, and so is everything else on the tabs below. An email element left blank is saved as ''.
// Appraiser email is on a tab of its own so that it stays NULL in the entries where that tab is never saved.
//
// The subforms are added as elements, instead of ticking "Create a Subform interface" on the linked elements, because
// that also puts the subform on the form's other multi-page screens, on a new page if it has to. The Artifacts form has
// screens for its subforms in Donors and Collections, and those should stay as they are. The linked element is what
// connects the forms, either way.
//
// There are no menu entries for the new forms and screens, so the menus that other specs check don't change.

test.use({ baseURL: E2E_TEST_BASE_URL });

test.describe.configure({ mode: 'serial' });

const MATERIALS = ['Iron', 'Cast iron', 'Bronze', 'Silver', 'Gold', 'Wood', 'Paper'];

const APPRAISER = 'appraiser@museum.example';

// The codes stored for the handling options, behind the text people see
const HANDLING = { 'Gloves': '1', 'Two people to lift': '2', 'Keep flat': '12' };

// appraiser: an address, or '' for saving the Appraisal tab with nothing in it. Leave it out and the tab is never saved.
// cataloguedBy, partOfSet and handling are on the Cataloguing tab, which is only saved for an artifact that has one of them.
const CATALOGUE = [
	{ name: 'Roman Coin', materials: ['Silver'], onLoan: 'Yes', appraiser: APPRAISER, storage: 'Climate controlled', cataloguedBy: 'Curator One', handling: ['Gloves'] },
	{ name: 'Persian necklace', materials: ['Gold', 'Silver'], cataloguedBy: 'Curator One' },
	{ name: 'Chinese Sword', materials: ['Iron'], onLoan: 'No', appraiser: '' },
	{ name: 'Egyptian Chariot', materials: ['Wood', 'Bronze'], provenance: 'Bought in Cairo', appraiser: '', handling: ['Two people to lift'] },
	{ name: 'Babylonian Spoon', materials: ['Bronze'], provenance: 'City Museum', cataloguedBy: 'Curator One' },
	{ name: 'Florentine Book', materials: ['Paper'], onLoan: 'Yes', provenance: "Bought at Sotheby's", cataloguedBy: 'Curator Two', handling: ['Gloves', 'Keep flat'] },
	{ name: 'French Musket', materials: ['Iron', 'Wood'], onLoan: 'No', provenance: 'Bought in Paris', appraiser: '', cataloguedBy: 'Curator Two' },
	{ name: 'Japanese Coin', materials: [], onLoan: 'No', appraiser: APPRAISER },
	{ name: 'American Bicycle', materials: ['Cast iron'] },
	{ name: 'Polynesian Canoe', materials: ['Wood'], handling: ['Two people to lift'] },
	{ name: 'Viking Silver Armband', materials: ['Silver'] },
	{ name: 'Candide - first edition', materials: ['Paper'], handling: ['Keep flat'] },
	{ name: 'Persian necklace - number 2', materials: [], partOfSet: 'Persian necklace' },
	{ name: 'Unknown Shard', materials: [] },
	{ name: 'Bronze Mirror', materials: ['Bronze'] },
	{ name: 'Silk Scroll', materials: ['Wood'], handling: ['Gloves', 'Keep flat'] },
];

function formId(title) {
	return dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title = '${sqlEscape(title)}'`)[0][0];
}

function museumAppId(fid) {
	return dbQuery(`SELECT appid FROM ${dbPrefix()}_formulize_application_form_link WHERE fid = ${fid}`)[0][0];
}

function screenId(handle) {
	return dbQuery(`SELECT sid FROM ${dbPrefix()}_formulize_screen WHERE screen_handle = '${sqlEscape(handle)}'`)[0][0];
}

function artifactEntryId(name) {
	return dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_artifacts WHERE artifacts_short_name = '${sqlEscape(name)}'`)[0][0];
}

// The app's form listing has every form's links in it at once, so go straight to the new element, as 035 and 038 do
async function startElement(page, formTitle, type, caption, handle, columnHeading = '') {
	const fid = formId(formTitle);
	await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=new&fid=${fid}&aid=${museumAppId(fid)}&type=${type}`);
	await waitForAdminPageReady(page);
	await page.locator('input[name="elements-ele_caption"]').fill(caption);
	await page.locator('input[name="elements-ele_handle"]').fill(handle);
	if (columnHeading) {
		await page.locator('input[name="elements-ele_colhead"]').fill(columnHeading);
	}
}

async function finishElement(page, heading) {
	await saveAdminForm(page);
	await expect(page.getByRole('heading')).toContainText(heading);
}

async function makeRequired(page) {
	await page.getByRole('group', { name: 'Make this element "required"' }).getByLabel('Yes').check();
}

async function linkToSource(page, source) {
	await page.getByRole('link', { name: 'Options' }).click();
	await page.locator('#formlink').selectOption(source);
}

// A new radio or checkbox element starts with one box for an option
async function fillOptions(page, options) {
	await page.getByRole('link', { name: 'Options' }).click();
	for (const [i, option] of options.entries()) {
		if (i > 0) {
			await page.getByRole('button', { name: 'Add' }).click();
		}
		await page.locator(`input[name="ele_value\\[${i}\\]"]`).fill(option);
	}
}

async function createNoThousandsSeparatorNumber(page, formTitle, caption, handle) {
	await startElement(page, formTitle, 'number', caption, handle);
	await page.getByRole('link', { name: 'Options' }).click();
	await page.getByRole('textbox', { name: 'Separate thousands with this' }).fill('');
	await finishElement(page, caption);
}

// A subform with no Add or Delete. Entries get into it through the linked element in the other form.
async function createSubform(page, formTitle, sourceForm, caption, handle, columns) {
	await startElement(page, formTitle, 'subformListings', caption, handle);
	await page.getByRole('link', { name: 'Options' }).click();
	await page.locator('#element-subform').selectOption({ label: sourceForm });
	await page.locator('select[name="elements_ele_value_1[]"]').selectOption(columns.map(label => ({ label })));
	await page.locator('[id="elements-ele_value[6]-no"]').check(); // hideaddentries (Show the add button) = No
	await page.getByRole('checkbox', { name: 'Show the Delete button' }).uncheck();
	await finishElement(page, caption);
}

async function inheritPermissionsFromArtifacts(page, formTitle) {
	const formUrl = `/modules/formulize/admin/ui.php?page=form&fid=${formId(formTitle)}&tab=permissions`;
	await page.goto(formUrl);
	await waitForAdminPageReady(page);
	await expect(page.locator('#perm_mode_inherit')).toBeVisible({ timeout: 30000 });
	await page.locator('#perm_mode_inherit').check();
	await page.locator('#parent_perm_fid_select').selectOption({ label: 'Artifacts' });
	await saveAdminForm(page);
	await page.goto(formUrl);
	await expect(page.locator('#perm_mode_inherit')).toBeChecked({ timeout: 30000 });
	await expect(page.locator('#parent_perm_fid_select option:checked')).toHaveText('Artifacts');
}

// Open a form's Screens tab. A form's name also appears in the Connections lists of other forms, so find its own box.
async function openFormScreens(page, formTitle) {
	await page.goto('/modules/formulize/admin/ui.php?page=home');
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	const formBox = page.locator('div.form-listing-box')
		.filter({ has: page.locator('.form-name-text').filter({ hasText: new RegExp(`^${formTitle}$`) }) });
	await expect(formBox).toBeVisible();
	await formBox.locator('.form-name-text').click();
	await formBox.getByRole('link', { name: 'Screens' }).first().click();
}

async function setElementsOnOnlyPage(page, formTitle, screenName, elements) {
	await openFormScreens(page, formTitle);
	await page.getByRole('link', { name: screenName, exact: true }).click();
	await waitForAdminPageReady(page);
	const screenUrl = page.url();
	await page.getByRole('link', { name: 'Pages' }).click();
	await page.getByRole('link', { name: 'Edit this page' }).click();
	await expect(page.getByText('Title for page number')).toBeVisible();
	await page.getByLabel('Form elements to display on').selectOption(elements);
	await saveAdminForm(page, 'popup');
	await page.getByRole('button', { name: 'close' }).click();
	await expectElementsOnPage(page, screenUrl, '', elements);
}

// Saving a page of a screen is what later specs depend on, so check it from a fresh load of the screen
async function expectElementsOnPage(page, screenUrl, pageTitle, elements) {
	await page.goto(screenUrl);
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Pages' }).click();
	if (pageTitle) {
		await editScreenPage(page, pageTitle);
	} else {
		await page.getByRole('link', { name: 'Edit this page' }).click();
	}
	// allTextContents() doesn't wait for the popup's contents, which arrive all at once
	await expect(page.getByText('Title for page number')).toBeVisible();
	const selected = await page.getByLabel('Form elements to display on').locator('option:checked').allTextContents();
	expect(selected.map(label => label.trim()).sort()).toEqual([...elements].sort());
}

async function addScreenPage(page, title, elements) {
	await page.getByRole('link', { name: 'Create a new page' }).click();
	await waitForAdminPageReady(page);
	await editScreenPage(page, 'New page');
	await page.getByRole('textbox', { name: 'Title for page number' }).fill(title);
	await page.getByLabel('Form elements to display on').selectOption(elements);
	await saveAdminForm(page, 'popup');
	await page.getByRole('button', { name: 'close' }).click();
}

async function createListScreen(page, formTitle, name, handle) {
	await openFormScreens(page, formTitle);
	await page.getByRole('link', { name: 'Create a new Screen' }).click();
	await page.getByRole('textbox', { name: 'Name of the screen:' }).fill(name);
	await page.getByLabel('What kind of screen is this:').selectOption('listOfEntries');
	await page.getByRole('textbox', { name: 'Handle for the screen:' }).fill(handle);
	await saveAdminForm(page);
	await waitForAdminPageReady(page);
}

function screenAdminUrl(formTitle, screenHandle) {
	const fid = formId(formTitle);
	return `/modules/formulize/admin/ui.php?page=screen&aid=${museumAppId(fid)}&fid=${fid}&sid=${screenId(screenHandle)}`;
}

// Every entry on one page, so a search can be read off the page with no paging
async function setListColumns(page, formTitle, screenHandle, handles) {
	await page.goto(screenAdminUrl(formTitle, screenHandle));
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Entries' }).click();
	for (const [i, handle] of handles.entries()) {
		if (i > 0) {
			await page.getByRole('button', { name: 'Add Column' }).click();
		}
		await page.locator(`#cols-${i}`).selectOption(handle);
	}
	await page.locator('#screens-entriesperpage').fill('50');
	await saveAdminForm(page);
	// and from a fresh load, since the searches depend on these columns being there
	await page.goto(screenAdminUrl(formTitle, screenHandle));
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Entries' }).click();
	for (const [i, handle] of handles.entries()) {
		await expect(page.locator(`#cols-${i}`)).toHaveValue(handle);
	}
	await expect(page.locator('#screens-entriesperpage')).toHaveValue('50');
}

// The page has to have finished loading, or it can't release the lock on the entry as it goes
async function saveAndClose(page) {
	await page.waitForLoadState('load');
	await page.getByRole('link', { name: 'Save and Close' }).click();
}

async function openArtifactsAndAdd(page, username) {
	await login(page, username, '12345');
	await page.locator('#burger-and-logo').getByRole('link').first().click();
	await page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true }).click();
	await page.getByRole('button', { name: 'Add Artifact', exact: true }).click();
}

async function fillRequiredArtifactFields(page, { name, height, width, depth, year, era }) {
	await page.getByRole('textbox', { name: 'Short name *' }).fill(name);
	await page.getByRole('spinbutton', { name: 'Height', exact: true }).fill(height);
	await page.getByRole('spinbutton', { name: 'Width', exact: true }).fill(width);
	await page.getByRole('spinbutton', { name: 'Depth', exact: true }).fill(depth);
	await page.getByRole('spinbutton', { name: 'Year', exact: true }).fill(year);
	await page.getByRole('radio', { name: era, exact: true }).check();
}

async function openDonorsAndAdd(page) {
	await login(page, 'curator1', '12345');
	await page.locator('#burger-and-logo').getByRole('link').first().click();
	await page.locator('#mainmenu').getByRole('link', { name: 'Donors', exact: true }).click();
	await page.getByRole('button', { name: 'Add Donor', exact: true }).click();
}

async function addLoanRequest(page, request) {
	await login(page, 'curator1', '12345');
	await page.goto(`/modules/formulize/index.php?fid=${formId('Loan Requests')}`);
	await page.getByRole('button', { name: /^Add Loan Requests?$/ }).click();
	await page.locator('.form-row:has(.formulize-label-loans_borrower) input[type="text"]').fill(request.borrower);
	await page.locator('.form-row:has(.formulize-label-loans_artifact) select').selectOption({ label: request.artifact });
	for (const collection of request.collections ?? []) {
		await page.locator('.form-row:has(.formulize-label-loans_collections)').getByRole('checkbox', { name: collection, exact: true }).check();
	}
	for (const material of request.materials ?? []) {
		await page.locator('.form-row:has(.formulize-label-loans_materials)').getByRole('checkbox', { name: material, exact: true }).check();
	}
	if (request.era) {
		await page.locator('.form-row:has(.formulize-label-loans_era)').getByRole('radio', { name: request.era, exact: true }).check();
	}
	if (request.madeAfter) {
		await page.locator('.form-row:has(.formulize-label-loans_made_after)').getByRole('spinbutton').fill(request.madeAfter);
	}
	if (request.donor) {
		await page.locator('.form-row:has(.formulize-label-loans_donor) select').selectOption({ label: request.donor });
	}
	if (request.start) {
		await page.getByRole('textbox', { name: 'Start date' }).fill(request.start);
	}
	if (request.end) {
		await page.getByRole('textbox', { name: 'End date' }).fill(request.end);
	}
	if (request.approved) {
		await page.locator('.form-row:has(.formulize-label-loans_approved)').getByRole('radio', { name: request.approved, exact: true }).check();
	}
	if (request.storage) {
		await page.locator('.form-row:has(.formulize-label-loans_storage)').getByRole('radio', { name: request.storage, exact: true }).check();
	}
	await saveFormulizeForm(page);
	await saveAndClose(page);
	await expect(page.getByRole('button', { name: /^Add Loan Requests?$/ })).toBeVisible();
}

test.describe('Build the Locations and Loan Requests forms', () => {

	test.beforeEach(async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.goto('/modules/formulize/admin/ui.php?page=home');
		await waitForAdminPageReady(page);
	});

	test('Create the Locations form', async ({ page }) => {
		expect(await createMuseumForm(page, 'Locations')).toBeGreaterThan(0);
	});

	test('Create the Loan Requests form', async ({ page }) => {
		expect(await createMuseumForm(page, 'Loan Requests')).toBeGreaterThan(0);
	});

	test('Create Location Name Element', async ({ page }) => {
		await startElement(page, 'Locations', 'text', 'Name', 'locations_name');
		await makeRequired(page);
		await finishElement(page, 'Name');
	});

	test('Create Location Element in Artifacts', async ({ page }) => {
		await startElement(page, 'Artifacts', 'selectLinked', 'Location', 'artifacts_location');
		await linkToSource(page, 'Locations: Name');
		await finishElement(page, 'Location');
	});

	test('Create Artifacts stored here Subform in Locations', async ({ page }) => {
		await createSubform(page, 'Locations', 'Artifacts', 'Artifacts stored here', 'locations_subform_artifacts', ['ID Number', 'Short name']);
	});

	test('Create Materials Element in Artifacts', async ({ page }) => {
		await startElement(page, 'Artifacts', 'checkbox', 'Materials', 'artifacts_materials');
		await fillOptions(page, MATERIALS);
		await finishElement(page, 'Materials');
	});

	test('Create On loan Element', async ({ page }) => {
		// no default, so it stays blank in an entry until someone answers it
		await startElement(page, 'Artifacts', 'yn', 'On loan', 'artifacts_on_loan');
		await finishElement(page, 'On loan');
	});

	test('Create Provenance Element', async ({ page }) => {
		await startElement(page, 'Artifacts', 'text', 'Provenance', 'artifacts_provenance');
		await finishElement(page, 'Provenance');
	});

	test('Create Appraiser email Element', async ({ page }) => {
		await startElement(page, 'Artifacts', 'email', 'Appraiser email', 'artifacts_appraiser_email');
		await finishElement(page, 'Appraiser email');
	});

	test('Create Curator note Element', async ({ page }) => {
		// nothing is typed into this one. It's here for display conditions to show and hide.
		await startElement(page, 'Artifacts', 'text', 'Curator note', 'artifacts_curator_note');
		await finishElement(page, 'Curator note');
	});

	test('Create Storage Element', async ({ page }) => {
		// 1 is part of 11 on purpose, so that looking for one by what people see can't find the other by its stored value
		await startElement(page, 'Artifacts', 'radio', 'Storage', 'artifacts_storage');
		await fillOptions(page, ['1|Standard', '11|Climate controlled']);
		const showWhatPeopleSee = page.getByRole('radio', { name: 'Show the same values the user sees' });
		await showWhatPeopleSee.check();
		await finishElement(page, 'Storage');
		// lists and searches depend on this setting, so check it from a fresh load
		const fid = formId('Artifacts');
		await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=${elementId('artifacts_storage')}&fid=${fid}&aid=${museumAppId(fid)}`);
		await waitForAdminPageReady(page);
		await page.getByRole('link', { name: 'Options' }).click();
		await expect(showWhatPeopleSee).toBeChecked();
	});

	test('Create Handling Element', async ({ page }) => {
		// 1 is part of 12 on purpose, the same as Storage
		await startElement(page, 'Artifacts', 'checkbox', 'Handling', 'artifacts_handling');
		await fillOptions(page, Object.entries(HANDLING).map(([text, code]) => `${code}|${text}`));
		const showWhatPeopleSee = page.getByRole('radio', { name: 'Show the same values the user sees' });
		await showWhatPeopleSee.check();
		await finishElement(page, 'Handling');
		const fid = formId('Artifacts');
		await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=${elementId('artifacts_handling')}&fid=${fid}&aid=${museumAppId(fid)}`);
		await waitForAdminPageReady(page);
		await page.getByRole('link', { name: 'Options' }).click();
		await expect(showWhatPeopleSee).toBeChecked();
	});

	test('Create Catalogued by Element', async ({ page }) => {
		await startElement(page, 'Artifacts', 'selectUsers', 'Catalogued by', 'artifacts_catalogued_by');
		await page.getByRole('link', { name: 'Options' }).click();
		await page.locator('#element-formlink_scope').selectOption('All Curators');
		await finishElement(page, 'Catalogued by');
	});

	test('Create Part of a set with Element', async ({ page }) => {
		// another artifact, so a condition on what this list offers refers to the form it's in
		await startElement(page, 'Artifacts', 'selectLinked', 'Part of a set with', 'artifacts_part_of_set');
		await linkToSource(page, 'Artifacts: Short name');
		await finishElement(page, 'Part of a set with');
	});

	test('Create Borrower Element', async ({ page }) => {
		await startElement(page, 'Loan Requests', 'text', 'Borrower', 'loans_borrower');
		await makeRequired(page);
		await finishElement(page, 'Borrower');
	});

	test('Create Collections wanted Element', async ({ page }) => {
		await startElement(page, 'Loan Requests', 'checkboxLinked', 'Collections wanted', 'loans_collections');
		await linkToSource(page, 'Collections: Name');
		await finishElement(page, 'Collections wanted');
	});

	test('Create Materials they can display Element', async ({ page }) => {
		await startElement(page, 'Loan Requests', 'checkbox', 'Materials they can display', 'loans_materials', 'Materials');
		await fillOptions(page, MATERIALS);
		await finishElement(page, 'Materials');
	});

	test('Create Era wanted Element', async ({ page }) => {
		await startElement(page, 'Loan Requests', 'radio', 'Era wanted', 'loans_era');
		await fillOptions(page, ['CE', 'BCE']);
		await finishElement(page, 'Era wanted');
	});

	test('Create Made after year Element', async ({ page }) => {
		await createNoThousandsSeparatorNumber(page, 'Loan Requests', 'Made after year', 'loans_made_after');
	});

	test('Create Donor contact Element', async ({ page }) => {
		await startElement(page, 'Loan Requests', 'selectLinked', 'Donor contact', 'loans_donor');
		await linkToSource(page, 'Donors: Name');
		await finishElement(page, 'Donor contact');
	});

	for (const [caption, handle] of [['Start date', 'loans_start_date'], ['End date', 'loans_end_date']]) {
		test(`Create ${caption} Element`, async ({ page }) => {
			await startElement(page, 'Loan Requests', 'date', caption, handle);
			await finishElement(page, caption);
		});
	}

	test('Create Artifact Element in Loan Requests', async ({ page }) => {
		await startElement(page, 'Loan Requests', 'selectLinked', 'Artifact', 'loans_artifact');
		await linkToSource(page, 'Artifacts: Short name');
		await finishElement(page, 'Artifact');
	});

	test('Create Approved Element', async ({ page }) => {
		await startElement(page, 'Loan Requests', 'yn', 'Approved', 'loans_approved');
		await finishElement(page, 'Approved');
	});

	test('Create Storage they have Element', async ({ page }) => {
		// the same values as Storage in Artifacts, so the two can be compared. Most borrowers are museums, so Climate controlled is the default.
		await startElement(page, 'Loan Requests', 'radio', 'Storage they have', 'loans_storage');
		await fillOptions(page, ['1|Standard', '11|Climate controlled']);
		const climateControlledByDefault = page.locator('input[name="defaultoption"]').nth(1); // the box next to the second option
		await climateControlledByDefault.check();
		const showWhatPeopleSee = page.getByRole('radio', { name: 'Show the same values the user sees' });
		await showWhatPeopleSee.check();
		await finishElement(page, 'Storage they have');
		const fid = formId('Loan Requests');
		await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=${elementId('loans_storage')}&fid=${fid}&aid=${museumAppId(fid)}`);
		await waitForAdminPageReady(page);
		await page.getByRole('link', { name: 'Options' }).click();
		await expect(climateControlledByDefault).toBeChecked();
		await expect(showWhatPeopleSee).toBeChecked();
	});

	test('Create Loan requests Subform in Artifacts', async ({ page }) => {
		await createSubform(page, 'Artifacts', 'Loan Requests', 'Loan requests', 'artifacts_subform_loan_requests', ['Borrower', 'Start date', 'End date', 'Approved']);
	});

	test('Locations inherits permissions from Artifacts', async ({ page }) => {
		await inheritPermissionsFromArtifacts(page, 'Locations');
	});

	test('Loan Requests inherits permissions from Artifacts', async ({ page }) => {
		await inheritPermissionsFromArtifacts(page, 'Loan Requests');
	});

	test('Location form screen', async ({ page }) => {
		await setElementsOnOnlyPage(page, 'Locations', 'Location', [
			'Locations: Name',
			'Locations: Artifacts stored here',
		]);
	});

	test('Loan Request form screen', async ({ page }) => {
		await setElementsOnOnlyPage(page, 'Loan Requests', 'Loan Request', [
			'Loan Requests: Borrower',
			'Loan Requests: Artifact',
			'Loan Requests: Collections wanted',
			'Loan Requests: Materials',
			'Loan Requests: Era wanted',
			'Loan Requests: Made after year',
			'Loan Requests: Donor contact',
			'Loan Requests: Start date',
			'Loan Requests: End date',
			'Loan Requests: Approved',
			'Loan Requests: Storage they have',
		]);
	});

	test('New tabs on the Artifact form screen', async ({ page }) => {
		const locationAndLoans = [
			'Artifacts: Location',
			'Artifacts: Materials',
			'Artifacts: On loan',
			'Artifacts: Provenance',
			'Artifacts: Storage',
			'Artifacts: Curator note',
			'Artifacts: Loan requests',
		];
		const appraisal = ['Artifacts: Appraiser email'];
		const cataloguing = ['Artifacts: Catalogued by', 'Artifacts: Part of a set with', 'Artifacts: Handling'];
		await openFormScreens(page, 'Artifacts');
		await page.getByRole('link', { name: 'Artifact', exact: true }).click();
		await waitForAdminPageReady(page);
		const screenUrl = page.url();
		await page.getByRole('link', { name: 'Pages' }).click();
		await addScreenPage(page, 'Location and loans', locationAndLoans);
		await addScreenPage(page, 'Appraisal', appraisal);
		await addScreenPage(page, 'Cataloguing', cataloguing);
		await expectElementsOnPage(page, screenUrl, 'Location and loans', locationAndLoans);
		await expectElementsOnPage(page, screenUrl, 'Appraisal', appraisal);
		await expectElementsOnPage(page, screenUrl, 'Cataloguing', cataloguing);
	});

	test('Catalogue list screen', async ({ page }) => {
		await createListScreen(page, 'Artifacts', 'Catalogue', 'artifact_catalogue');
		await setListColumns(page, 'Artifacts', 'artifact_catalogue', [
			'artifacts_short_name',
			'artifacts_year',
			'artifacts_era',
			'artifacts_date_of_acquisition',
			'artifacts_donated_to_museum',
			'artifacts_on_loan',
			'artifacts_donor',
			'artifacts_collections',
			'artifacts_materials',
			'artifacts_location',
			'artifacts_latitude',
			'artifacts_provenance',
			'artifacts_storage',
			'artifacts_appraiser_email',
			'artifacts_handling',
			'artifacts_catalogued_by',
			'creation_datetime',
			'mod_datetime',
		]);
	});

	test('Donor artifacts list screen', async ({ page }) => {
		// The relationship has to be saved first, because the columns on offer depend on it when the page is drawn
		await createListScreen(page, 'Donors', 'Donor artifacts', 'donor_artifacts');
		await page.getByRole('link', { name: 'Relationships' }).click();
		await page.locator('#screens-frid').selectOption('-1'); // the Primary Relationship
		await saveAdminForm(page);
		await setListColumns(page, 'Donors', 'donor_artifacts', [
			'donors_name',
			'donors_first_name',
			'donors_last_name',
			'donors_email',
			'donors_anonymous',
			'donors_province',
			'artifacts_short_name',
			'artifacts_collections',
			'artifacts_latitude',
		]);
	});
});

test.describe('Data entry for Locations', () => {

	test('Create Main Vault', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await page.goto(`/modules/formulize/index.php?fid=${formId('Locations')}`);
		await page.getByRole('button', { name: /^Add Locations?$/ }).click();
		await page.getByRole('textbox', { name: 'Name *' }).fill('Main Vault');
		await saveFormulizeForm(page);
		await saveAndClose(page);
		await expect(page.getByText('Main Vault')).toBeVisible();
	});
});

test.describe('Donors with punctuation in their names', () => {

	test('Create Aoife O\'Brien donor', async ({ page }) => {
		await openDonorsAndAdd(page);
		await conditionalElementReady(page, 'donors_first_name', () => page.getByText('Individual', { exact: true }).click());
		await page.getByRole('textbox', { name: 'First name' }).fill('Aoife');
		await page.getByRole('textbox', { name: 'Last name' }).fill('O\'Brien');
		await saveFormulizeForm(page);
		await saveAndClose(page);
		await expect(page.getByRole('button', { name: 'Add Donor', exact: true })).toBeVisible();
	});

	test('Create Hart & Sons Antiques donor', async ({ page }) => {
		await openDonorsAndAdd(page);
		await conditionalElementReady(page, 'donors_organization_name', () => page.getByText('Organization', { exact: true }).click());
		await page.getByRole('textbox', { name: 'Organization name' }).fill('Hart & Sons Antiques');
		await page.getByRole('combobox', { name: 'Province', exact: true }).selectOption('Prince Edward Island');
		await saveFormulizeForm(page);
		await saveAndClose(page);
		await expect(page.getByRole('button', { name: 'Add Donor', exact: true })).toBeVisible();
	});
});

// Each department's artifacts are counted by 026, which is why it matters who adds these
test.describe('Artifacts for blanks and year boundaries', () => {

	test('Create Unknown Shard, with only what is required', async ({ page }) => {
		await openArtifactsAndAdd(page, 'ahstaff');
		await fillRequiredArtifactFields(page, { name: 'Unknown Shard', height: '1', width: '1', depth: '1', year: '500', era: 'BCE' });
		await saveFormulizeForm(page);
		await saveAndClose(page);
		await expect(page.getByRole('button', { name: 'Add Artifact', exact: true })).toBeVisible();
	});

	test('Create Bronze Mirror, acquired on the last day of 2002', async ({ page }) => {
		await openArtifactsAndAdd(page, 'ahstaff');
		await fillRequiredArtifactFields(page, { name: 'Bronze Mirror', height: '30', width: '30', depth: '2', year: '200', era: 'BCE' });
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('2002-12-31');
		await conditionalElementReady(page, 'artifacts_donor', () => page.locator('.form-row:has(.formulize-label-artifacts_donated_to_museum)').getByRole('radio', { name: 'Yes', exact: true }).check()); // donated
		await page.getByLabel('Donor').selectOption({ label: 'Aoife O\'Brien' });
		await page.getByRole('checkbox', { name: 'Ancient History' }).check();
		await saveFormulizeForm(page);
		await saveAndClose(page);
		await expect(page.getByRole('button', { name: 'Add Artifact', exact: true })).toBeVisible();
	});

	test('Create Silk Scroll, acquired on the first day of 2003', async ({ page }) => {
		await openArtifactsAndAdd(page, 'mhstaff');
		await fillRequiredArtifactFields(page, { name: 'Silk Scroll', height: '40', width: '40', depth: '3', year: '1400', era: 'CE' });
		await page.getByRole('textbox', { name: 'Date of acquisition' }).fill('2003-01-01');
		await conditionalElementReady(page, 'artifacts_donor', () => page.locator('.form-row:has(.formulize-label-artifacts_donated_to_museum)').getByRole('radio', { name: 'Yes', exact: true }).check()); // donated
		await page.getByLabel('Donor').selectOption({ label: 'Hart & Sons Antiques' });
		await page.getByRole('checkbox', { name: 'Modern History' }).check();
		await saveFormulizeForm(page);
		await saveAndClose(page);
		await expect(page.getByRole('button', { name: 'Add Artifact', exact: true })).toBeVisible();
	});
});

test.describe('Catalogue every artifact', () => {

	for (const artifact of CATALOGUE) {
		test(`Catalogue ${artifact.name}`, async ({ page }) => {
			const artifactsFid = formId('Artifacts');
			await login(page, 'curator1', '12345');
			await page.goto(`/modules/formulize/index.php?fid=${artifactsFid}&ve=${artifactEntryId(artifact.name)}`);
			await page.waitForLoadState('load');

			await page.getByRole('link', { name: 'Location and loans', exact: true }).click();
			const location = page.locator('.form-row:has(.formulize-label-artifacts_location) select');
			await expect(location).toBeVisible();
			await location.selectOption({ label: 'Main Vault' });
			for (const material of artifact.materials) {
				await page.locator('.form-row:has(.formulize-label-artifacts_materials)').getByRole('checkbox', { name: material, exact: true }).check();
			}
			if (artifact.onLoan) {
				await page.locator('.form-row:has(.formulize-label-artifacts_on_loan)').getByRole('radio', { name: artifact.onLoan, exact: true }).check();
			}
			if (artifact.provenance) {
				await page.locator('.form-row:has(.formulize-label-artifacts_provenance) input[type="text"]').fill(artifact.provenance);
			}
			if (artifact.storage) {
				await page.locator('.form-row:has(.formulize-label-artifacts_storage)').getByRole('radio', { name: artifact.storage, exact: true }).check();
			}
			await saveFormulizeForm(page);

			if (artifact.appraiser !== undefined) {
				await page.getByRole('link', { name: 'Appraisal', exact: true }).click();
				const email = page.locator('.form-row:has(.formulize-label-artifacts_appraiser_email)').getByRole('textbox');
				await expect(email).toBeVisible();
				await email.fill(artifact.appraiser);
				await saveFormulizeForm(page);
			}

			if (artifact.cataloguedBy || artifact.partOfSet || artifact.handling) {
				await page.getByRole('link', { name: 'Cataloguing', exact: true }).click();
				const cataloguedBy = page.locator('.form-row:has(.formulize-label-artifacts_catalogued_by) select');
				await expect(cataloguedBy).toBeVisible();
				if (artifact.cataloguedBy) {
					await cataloguedBy.selectOption({ label: artifact.cataloguedBy });
				}
				if (artifact.partOfSet) {
					await page.locator('.form-row:has(.formulize-label-artifacts_part_of_set) select').selectOption({ label: artifact.partOfSet });
				}
				for (const handling of artifact.handling ?? []) {
					await page.locator('.form-row:has(.formulize-label-artifacts_handling)').getByRole('checkbox', { name: handling, exact: true }).check();
				}
				await saveFormulizeForm(page);
			}

			await leavePage(page, `/modules/formulize/index.php?fid=${artifactsFid}`); // releases the entry lock
			await expect(page.getByRole('button', { name: 'Add Artifact', exact: true })).toBeVisible();
		});
	}
});

test.describe('Data entry for Loan Requests', () => {

	test('City Museum asks for the Roman Coin', async ({ page }) => {
		await addLoanRequest(page, {
			borrower: 'City Museum',
			artifact: 'Roman Coin',
			collections: ['Coins'],
			materials: ['Silver'],
			era: 'BCE',
			madeAfter: '300',
			donor: 'François-Marie Arouet',
			start: '2026-01-15',
			end: '2026-06-30',
			approved: 'Yes',
		});
	});

	test('Harbour Gallery asks for the Roman Coin too', async ({ page }) => {
		await addLoanRequest(page, {
			borrower: 'Harbour Gallery',
			artifact: 'Roman Coin',
			collections: ['Weapons', 'Modern History'],
			materials: ['Iron', 'Wood'],
			era: 'CE',
			madeAfter: '1500',
			donor: 'Hart & Sons Antiques',
			start: '2003-01-01',
			approved: 'No',
			storage: 'Standard',
		});
	});

	test('University Library asks for the Florentine Book, and has not said anything else yet', async ({ page }) => {
		await addLoanRequest(page, { borrower: 'University Library', artifact: 'Florentine Book' });
	});

	// Two requests for an artifact with On loan left blank, so a subform condition has a blank to refer to
	test('Regional Archive asks for the Unknown Shard, and has no answer yet', async ({ page }) => {
		await addLoanRequest(page, { borrower: 'Regional Archive', artifact: 'Unknown Shard' });
	});

	test('Town Hall asks for the Unknown Shard, and is turned down', async ({ page }) => {
		await addLoanRequest(page, { borrower: 'Town Hall', artifact: 'Unknown Shard', approved: 'No' });
	});
});

test.describe('What was built', () => {

	test('Stored values match what was entered, with both kinds of blank', () => {
		const artifacts = `${dbPrefix()}_formulize_artifacts`;
		const rows = dbQuery(`SELECT a.artifacts_short_name, a.artifacts_location IS NOT NULL, IFNULL(a.artifacts_materials, 'NULL'), IFNULL(a.artifacts_on_loan, 'NULL'),
			a.artifacts_provenance IS NULL, IF(a.artifacts_appraiser_email IS NULL, 'NULL', IF(a.artifacts_appraiser_email = '', 'empty', a.artifacts_appraiser_email)), IFNULL(a.artifacts_storage, 'NULL'),
			IFNULL(a.artifacts_handling, 'NULL'), IFNULL((SELECT u.name FROM ${dbPrefix()}_users AS u WHERE u.uid = a.artifacts_catalogued_by), 'nobody'),
			IFNULL((SELECT s.artifacts_short_name FROM ${artifacts} AS s WHERE s.entry_id = a.artifacts_part_of_set), 'nothing')
			FROM ${artifacts} AS a`);
		const stored = Object.fromEntries(rows.map(row => [row[0], row.slice(1)]));
		expect(Object.keys(stored).sort()).toEqual(CATALOGUE.map(artifact => artifact.name).sort());
		for (const artifact of CATALOGUE) {
			const [hasLocation, materials, onLoan, provenanceIsNull, appraiser, storage, handling, cataloguedBy, partOfSet] = stored[artifact.name];
			expect(handling === 'NULL' ? [] : handling.split('*=+*:').filter(Boolean).sort(), `${artifact.name} handling`).toEqual((artifact.handling ?? []).map(text => HANDLING[text]).sort()); // the codes, not what people see
			expect(cataloguedBy, `${artifact.name} catalogued by`).toBe(artifact.cataloguedBy ?? 'nobody');
			expect(partOfSet, `${artifact.name} part of a set with`).toBe(artifact.partOfSet ?? 'nothing');
			expect(hasLocation, `${artifact.name} is in a location`).toBe('1');
			// stored one after another in the order of the element's options, with *=+*: in front of each one
			expect(materials === 'NULL' ? [] : materials.split('*=+*:').filter(Boolean).sort(), `${artifact.name} materials`).toEqual([...artifact.materials].sort());
			expect(onLoan, `${artifact.name} on loan`).toBe({ Yes: '1', No: '2' }[artifact.onLoan] ?? 'NULL');
			expect(provenanceIsNull, `${artifact.name} provenance`).toBe(artifact.provenance ? '0' : '1');
			expect(appraiser, `${artifact.name} appraiser`).toBe(artifact.appraiser === undefined ? 'NULL' : (artifact.appraiser || 'empty'));
			expect(storage, `${artifact.name} storage`).toBe({ 'Climate controlled': '11' }[artifact.storage] ?? 'NULL'); // the stored value, not what people see
		}
	});

	test('Every loan request has the storage they have, which is the default unless they said otherwise', () => {
		const storage = Object.fromEntries(dbQuery(`SELECT loans_borrower, IFNULL(loans_storage, 'NULL') FROM ${dbPrefix()}_formulize_loan_requests`));
		expect(storage).toEqual({ 'City Museum': '11', 'Harbour Gallery': '1', 'University Library': '11', 'Regional Archive': '11', 'Town Hall': '11' });
	});

	test('Hart & Sons Antiques are in Prince Edward Island, which is stored as 10', () => {
		expect(dbQuery(`SELECT donors_province FROM ${dbPrefix()}_formulize_donors WHERE donors_name = 'Hart & Sons Antiques'`)[0][0]).toBe('10');
	});

	test('Main Vault shows every artifact', async ({ page }) => {
		const locationsFid = formId('Locations');
		const entry = dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_locations WHERE locations_name = 'Main Vault'`)[0][0];
		await login(page, 'curator1', '12345');
		await page.goto(`/modules/formulize/index.php?fid=${locationsFid}&ve=${entry}`);
		const subform = page.locator('.formulize-subform-table');
		await expect(subform).toBeVisible();
		for (const artifact of CATALOGUE) {
			await expect(subform.getByText(artifact.name, { exact: true })).toBeVisible();
		}
		await leavePage(page, `/modules/formulize/index.php?fid=${locationsFid}`); // releases the entry lock
		await expect(page.getByText('Main Vault')).toBeVisible();
	});

	test('The Roman Coin shows its loan requests on its tab', async ({ page }) => {
		const artifactsFid = formId('Artifacts');
		await login(page, 'curator1', '12345');
		await page.goto(`/modules/formulize/index.php?fid=${artifactsFid}&ve=${artifactEntryId('Roman Coin')}`);
		await page.waitForLoadState('load');
		await page.getByRole('link', { name: 'Location and loans', exact: true }).click();
		const subform = page.locator('.formulize-subform-table');
		await expect(subform).toBeVisible();
		await expect(subform.getByText('City Museum', { exact: true })).toBeVisible();
		await expect(subform.getByText('Harbour Gallery', { exact: true })).toBeVisible();
		await expect(subform.getByText('University Library', { exact: true })).toHaveCount(0);
		await leavePage(page, `/modules/formulize/index.php?fid=${artifactsFid}`); // releases the entry lock
		await expect(page.getByRole('button', { name: 'Add Artifact', exact: true })).toBeVisible();
	});

	test('The Catalogue list has every artifact on one page', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await page.goto(`/modules/formulize/index.php?sid=${screenId('artifact_catalogue')}`);
		await expect(page.getByText(`Showing entries: 1 to ${CATALOGUE.length} of ${CATALOGUE.length}`)).toBeVisible();
		await expect(page.locator('input[name="search_artifacts_appraiser_email"]')).toBeVisible();
	});

	test('The Donor artifacts list has donors with their artifacts', async ({ page }) => {
		await login(page, 'curator1', '12345');
		await page.goto(`/modules/formulize/index.php?sid=${screenId('donor_artifacts')}`);
		await expect(page.getByRole('row', { name: /Aoife O'Brien/ })).toContainText('Bronze Mirror');
		await expect(page.locator('input[name="search_artifacts_collections"]')).toBeVisible();
	});
});
