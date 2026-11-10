const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, saveAdminForm, openElementSettings, removeConditions, elementId } from '../../utils';

// A table of elements gives its display conditions to the elements in it, if they have none of their own. When the table's
// starting element changes, the elements that leave the table lose those conditions, and the ones that join it get them. When
// the table's conditions are removed, the elements in it lose their copies too. See issue 479.
//
// This uses the Dimensions table on Artifacts, which 010 made with Height, Width and Depth in one row. Starting it at Width
// puts Width, Depth and Year in it, so Height leaves and Year joins. Year is in the Date of origin table as well, which has no
// conditions. The table changes shape and gets a condition, so this is in 03-validate-sequential. It puts the table back the
// way 010 made it in the last test, and again in afterAll in case a test stopped partway.

test.use({ baseURL: E2E_TEST_BASE_URL });

// Each test opens the Display Settings of the table and four elements, one page each, and some save as well
test.describe.configure({ mode: 'serial', timeout: 300000 });

const GRID = 'artifacts_dimensions';
const ELEMENTS = ['artifacts_height', 'artifacts_width', 'artifacts_depth', 'artifacts_year'];
const CONDITION_XS = '#displayfilter > a.conditionsdelete';
const STARTING_ELEMENT = page => page.getByLabel('Choose the first element,');

// Set only once the first test has found no conditions on the table or its elements, so afterAll never removes conditions
// that something else put there.
let startedWithoutConditions = false;

test.beforeEach(async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
});

// How many display conditions an element's Display Settings tab lists
async function expectConditions(page, handle, count) {
	await openElementSettings(page, handle, 'Display Settings');
	await expect(page.locator(CONDITION_XS), `${handle} should have ${count} display condition(s)`).toHaveCount(count);
}

async function expectConditionsOn(page, withCondition) {
	for (const handle of ELEMENTS) {
		await expectConditions(page, handle, withCondition.includes(handle) ? 1 : 0);
	}
}

async function startTableAt(page, handle) {
	await openElementSettings(page, GRID, 'Options');
	await STARTING_ELEMENT(page).selectOption(elementId(handle));
	await saveAdminForm(page);
	await openElementSettings(page, GRID, 'Options');
	await expect(STARTING_ELEMENT(page)).toHaveValue(elementId(handle));
}

async function putTableBack(page) {
	await openElementSettings(page, GRID, 'Options');
	if (await STARTING_ELEMENT(page).inputValue() !== elementId('artifacts_height')) {
		await startTableAt(page, 'artifacts_height');
	}
	for (const handle of [GRID, ...ELEMENTS]) {
		await removeConditions(page, p => openElementSettings(p, handle, 'Display Settings'), CONDITION_XS);
	}
}

test('The table starts at Height, with no display conditions', async ({ page }) => {
	await openElementSettings(page, GRID, 'Options');
	await expect(STARTING_ELEMENT(page)).toHaveValue(elementId('artifacts_height'));
	await expectConditions(page, GRID, 0);
	await expectConditionsOn(page, []);
	startedWithoutConditions = true;
});

test('A condition on the table goes to the elements in it', async ({ page }) => {
	await openElementSettings(page, GRID, 'Display Settings');
	await page.locator('#new_elementfilter_element').selectOption('Donated to museum');
	await page.locator('#new_elementfilter_term').fill('Yes');
	await saveAdminForm(page);
	await expectConditions(page, GRID, 1);
	await expectConditionsOn(page, ['artifacts_height', 'artifacts_width', 'artifacts_depth']);
});

test('Starting the table at Width takes the condition off Height and gives it to Year', async ({ page }) => {
	await startTableAt(page, 'artifacts_width');
	await expectConditionsOn(page, ['artifacts_width', 'artifacts_depth', 'artifacts_year']);
});

test('Starting the table at Height again gives the condition back to Height and takes it off Year', async ({ page }) => {
	await startTableAt(page, 'artifacts_height');
	await expectConditionsOn(page, ['artifacts_height', 'artifacts_width', 'artifacts_depth']);
});

test('Removing the condition from the table takes it off the elements in it', async ({ page }) => {
	await removeConditions(page, p => openElementSettings(p, GRID, 'Display Settings'), CONDITION_XS);
	await expectConditions(page, GRID, 0);
	await expectConditionsOn(page, []);
});

test.afterAll(async ({ browser }) => {
	if (!startedWithoutConditions) {
		return;
	}
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await putTableBack(page);
	await page.close();
});
