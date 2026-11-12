const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import {
	login, dbQuery, dbPrefix, getSiteTheme, restoreSiteTheme, setSystemConfig,
	createMuseumForm, deleteMuseumFormsTitled, waitForAdminPageReady, leavePage,
} from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Lyris appearance - the settings new screens start with
// ============================================================
// A theme's screen-defaults.json says how new screens start out in that theme. Lyris opens a list's entries
// in the drawer, hides the list's search row until it is opened, shows 20 entries a page, and puts a form's
// captions above its questions. Anari puts a form's captions in a column of their own, 20% wide.
//
// It makes one form with the site on Lyris and one with the site on Anari, and reads each one's default
// screens on their admin pages, as an administrator sees them. The site's theme is the only one that
// counts: the administrator is a webmaster, who is shown the admin theme, and the admin theme stays as the
// site had it throughout. The forms are deleted, and the site's theme put back, in the afterAll.

test.describe.configure({ mode: 'serial' });

const LYRIS_FORM = 'E2E Lyris new screen defaults';
const ANARI_FORM = 'E2E Anari new screen defaults';
let originalTheme = null;

async function adminPage(browser) {
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	return page;
}

// The site's theme, as Admin > Appearance > Settings sets it, leaving the admin theme as it is
function setSiteThemeOnly(theme) {
	setSystemConfig('theme_set', theme);
}

// The form's default list and form screens, as the form records them
function defaultScreens(fid) {
	const [row] = dbQuery(`SELECT defaultlist, defaultform FROM ${dbPrefix()}_formulize_id WHERE id_form = ${fid}`);
	return { list: parseInt(row[0], 10), form: parseInt(row[1], 10) };
}

async function openScreenTab(page, fid, sid, tab) {
	await page.goto(`/modules/formulize/admin/ui.php?page=screen&fid=${fid}&sid=${sid}&tab=${tab}`);
	await waitForAdminPageReady(page);
}

async function expectListSettings(page, fid, sid, { editdestination, usesearch, entriesperpage }) {
	await openScreenTab(page, fid, sid, 'entries');
	await expect(page.locator('#screens-entriesperpage')).toHaveValue(entriesperpage);
	await openScreenTab(page, fid, sid, 'interface');
	await expect(page.locator(`input[name="screens-editdestination"][value="${editdestination}"]`)).toBeChecked();
	await expect(page.locator(`input[name="screens-usesearch"][value="${usesearch}"]`)).toBeChecked();
}

async function expectFormSettings(page, fid, sid, { columns, column1width, column2width }) {
	await openScreenTab(page, fid, sid, 'options');
	await expect(page.locator(columns === 1 ? '#onecolumn' : '#twocolumns')).toBeChecked();
	await expect(page.locator(columns === 1 ? 'input[name="singlecolumn1width"]' : 'input[name="doublecolumn1width"]')).toHaveValue(column1width);
	await expect(page.locator('input[name="screens-column2width"]')).toHaveValue(column2width);
}

test.beforeAll(async ({ browser }) => {
	originalTheme = getSiteTheme();
	// clear forms that a run which was killed outright left behind
	const page = await adminPage(browser);
	await deleteMuseumFormsTitled(page, [LYRIS_FORM, ANARI_FORM]);
	await page.close();
});

test.afterAll(async ({ browser }) => {
	restoreSiteTheme(originalTheme);
	const page = await adminPage(browser);
	try {
		await deleteMuseumFormsTitled(page, [LYRIS_FORM, ANARI_FORM]);
	} finally {
		await page.close();
	}
});

test.beforeEach(async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
});

test('A new form on Lyris gets a list that opens entries in the drawer, and a form with one column', async ({ page }) => {
	setSiteThemeOnly('Lyris');
	const fid = await createMuseumForm(page, LYRIS_FORM);
	const screens = defaultScreens(fid);

	await expectListSettings(page, fid, screens.list, { editdestination: 'drawer', usesearch: '2', entriesperpage: '20' });
	await expectFormSettings(page, fid, screens.form, { columns: 1, column1width: 'auto', column2width: 'auto' });

	// and the list does open a new entry in the drawer, on the same page
	await page.goto(`/modules/formulize/index.php?sid=${screens.list}`);
	await page.locator('#formulize_addButton').click();
	await expect(page.locator('.formulize-drawer[role="dialog"]')).toBeVisible();
	expect(new URL(page.url()).searchParams.get('sid')).toBe(String(screens.list));
	await leavePage(page);
});

test('A new form on Anari gets a list that opens entries full screen, and a form with two columns', async ({ page }) => {
	setSiteThemeOnly('Anari');
	const fid = await createMuseumForm(page, ANARI_FORM);
	const screens = defaultScreens(fid);

	await expectListSettings(page, fid, screens.list, { editdestination: 'screen', usesearch: '1', entriesperpage: '10' });
	await expectFormSettings(page, fid, screens.form, { columns: 2, column1width: '20%', column2width: 'auto' });
});
