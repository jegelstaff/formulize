const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import {
	login, dbQuery, dbPrefix, getSiteTheme, waitForAdminPageReady, saveAdminForm, setCodeMirrorValue, chooseSiteThemes,
	createMuseumForm, deleteMuseumForm, deleteMuseumFormsTitled, screenTemplateFolderExists, openScreenTemplatesTab,
	showScreenTemplatesFor, screenTemplatesForm, variableTemplatesForm, variablesSubtab, customizeScreenTemplates,
	emptyScreenTemplates,
} from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Lyris appearance - a list screen's own variable templates
// ============================================================
// In Lyris, three small parts of a list are drawn by variable templates: $currentViewList, $moreActionsButton and
// $pageNavControls. "Customize the Templates" gives a list screen its own copies of them, as well as of its five main
// templates, and they're edited on a Variables sub-tab beside Templates, on the screen's Appearance tab.
//
// The screen's folder of templates goes once both sets are cleared, in either order, and while it's there the Templates
// tab doesn't offer to customize again, so seeding never copies over anything the screen has. Deleting a screen, or its
// form, removes its folders, variables/ and all. The module update removes copies of the defaults that were never
// edited.
//
// Anari has no variable templates, so the spec switches the site to Lyris, which moves every screen's Templates tab to
// Lyris too, and puts it back to Anari at the end. It uses the Exhibits list, and a form of its own that it deletes.
// Everything is done through the admin UI. The server's files are only read, to check what the application did.

test.describe.configure({ mode: 'serial' });

const FORM_TITLE = 'E2E Variable templates';
const SCREEN_TITLE = 'E2E Variable templates list';
const MARKER = 'Variables tab marker';
const MARKER_LINE = `print "<span class='e2e-variables-marker'>${MARKER}</span>";`;
const VARIABLE_NAMES = ['currentViewList', 'moreActionsButton', 'pageNavControls'];
const MAIN_TEMPLATES = ['toptemplate', 'openlisttemplate', 'listtemplate', 'closelisttemplate', 'bottomtemplate'];

let exhibits = null;
let fid = 0;

function listScreen(title) {
	const [row] = dbQuery(`SELECT sid, fid FROM ${dbPrefix()}_formulize_screen WHERE title = '${title}' AND type = 'listOfEntries'`);
	return { sid: parseInt(row[0], 10), fid: parseInt(row[1], 10) };
}

function screenTheme(sid) {
	return dbQuery(`SELECT theme FROM ${dbPrefix()}_formulize_screen WHERE sid = ${sid}`)[0][0];
}

async function adminPage(browser) {
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	return page;
}

// The sub-tab of the Appearance tab that's open
function openSubtab(page) {
	return page.locator('#formulize-screen-appearance-tabs > ul > li.ui-tabs-selected');
}

// What a variable template's box holds, read from its editor
function variableTemplateText(page, name) {
	return page.locator(`textarea[name="variabletemplates-${name}"] + .CodeMirror`).evaluate(editor => editor.CodeMirror.getValue());
}

test.beforeAll(async ({ browser }) => {
	test.setTimeout(300000);
	exhibits = listScreen('Exhibits');
	const page = await adminPage(browser);
	try {
		expect(getSiteTheme().site).toBe('Anari');
		await deleteMuseumFormsTitled(page, [FORM_TITLE]); // left behind by a run that was killed outright
		await chooseSiteThemes(page, 'Lyris', 'Lyris');
		expect(screenTheme(exhibits.sid)).toBe('Lyris');
		await emptyScreenTemplates(page, exhibits); // left behind by a run that stopped partway
	} finally {
		await page.close();
	}
});

test.afterAll(async ({ browser }) => {
	test.setTimeout(300000);
	const page = await adminPage(browser);
	try {
		await deleteMuseumFormsTitled(page, [FORM_TITLE]);
		await showScreenTemplatesFor(page, exhibits, 'Lyris');
		await emptyScreenTemplates(page, exhibits);
		if (getSiteTheme().site !== 'Anari' || getSiteTheme().admin !== 'Anari') {
			await chooseSiteThemes(page, 'Anari', 'Anari');
		}
	} finally {
		await page.close();
	}
	expect(getSiteTheme().site).toBe('Anari');
	expect(screenTheme(exhibits.sid)).toBe('Anari');
	expect(screenTemplateFolderExists('Lyris', exhibits.sid)).toBe(false);
});

test('A screen with no variable templates has no Variables tab, even when it is asked for', async ({ browser }) => {
	const page = await adminPage(browser);
	await openScreenTemplatesTab(page, exhibits);
	await expect(page.getByRole('button', { name: 'Customize the Templates' })).toBeVisible();
	await expect(variablesSubtab(page)).toHaveCount(0);
	// asked for by name, it opens on Options
	await openScreenTemplatesTab(page, exhibits, 'variables');
	await expect(openSubtab(page)).toHaveText('Options');
	await expect(page.locator('#formulize-screen-appearance-options')).toBeVisible();
	await page.close();
});

test('Customizing a Lyris list\'s templates gives it a Variables tab with a box for each part', async ({ browser }) => {
	const page = await adminPage(browser);
	await customizeScreenTemplates(page, exhibits);
	expect(screenTemplateFolderExists('Lyris', exhibits.sid, 'variables')).toBe(true);
	await expect(variablesSubtab(page)).toBeVisible();
	await variablesSubtab(page).click();
	await expect(variableTemplatesForm(page).locator('legend')).toHaveText(VARIABLE_NAMES.map(name => '$' + name));
	await expect(variableTemplatesForm(page)).toContainText('variable templates in the Lyris theme');
	// in Anari, which has none, the tab isn't there
	await showScreenTemplatesFor(page, exhibits, 'Anari');
	await expect(variablesSubtab(page)).toHaveCount(0);
	await showScreenTemplatesFor(page, exhibits, 'Lyris');
	await expect(variablesSubtab(page)).toBeVisible();
	await page.close();
});

test('A change to a variable template shows in the list', async ({ browser }) => {
	const page = await adminPage(browser);
	await openScreenTemplatesTab(page, exhibits, 'variables');
	await expect(openSubtab(page)).toHaveText('Variables');
	const input = page.locator('textarea[name="variabletemplates-pageNavControls"] + .CodeMirror').getByRole('textbox');
	await input.press('ControlOrMeta+End');
	await input.press('Enter');
	await page.keyboard.type(MARKER_LINE);
	await expect(page.locator('textarea[name="variabletemplates-pageNavControls"]')).toHaveValue(new RegExp(MARKER));
	await saveAdminForm(page);
	await openScreenTemplatesTab(page, exhibits, 'variables');
	expect(await variableTemplateText(page, 'pageNavControls')).toContain(MARKER_LINE);

	await page.goto(`/modules/formulize/index.php?sid=${exhibits.sid}`);
	await expect(page.locator('.e2e-variables-marker')).toHaveText(MARKER);
	await page.close();
});

test('With only variable templates left, the Templates tab says to clear them first', async ({ browser }) => {
	const page = await adminPage(browser);
	await openScreenTemplatesTab(page, exhibits);
	for (const name of MAIN_TEMPLATES) {
		await setCodeMirrorValue(page, `textarea[name="screens-${name}"]`, '');
	}
	await saveAdminForm(page);
	await openScreenTemplatesTab(page, exhibits);
	await expect(screenTemplatesForm(page).locator('textarea.code-textarea')).toHaveCount(0);
	await expect(page.getByRole('button', { name: 'Customize the Templates' })).toHaveCount(0);
	await expect(screenTemplatesForm(page)).toContainText('This screen still has variable templates of its own, on the Variables tab.');
	expect(screenTemplateFolderExists('Lyris', exhibits.sid)).toBe(true);
	await expect(variablesSubtab(page)).toBeVisible();
	await variablesSubtab(page).click();
	expect(await variableTemplateText(page, 'pageNavControls')).toContain(MARKER_LINE);
	await page.close();
});

test('Emptying the variable templates removes the tab and the screen\'s folder', async ({ browser }) => {
	const page = await adminPage(browser);
	await openScreenTemplatesTab(page, exhibits, 'variables');
	for (const name of VARIABLE_NAMES) {
		await setCodeMirrorValue(page, `textarea[name="variabletemplates-${name}"]`, '');
	}
	// the last one gone, the save reloads the page, which can't come back to a Variables tab, so it opens on Options
	await saveAdminForm(page);
	await waitForAdminPageReady(page);
	await expect(variablesSubtab(page)).toHaveCount(0);
	await expect(openSubtab(page)).toHaveText('Options');
	expect(screenTemplateFolderExists('Lyris', exhibits.sid)).toBe(false);
	await openScreenTemplatesTab(page, exhibits);
	await expect(page.getByRole('button', { name: 'Customize the Templates' })).toBeVisible();
	// and the list is drawn with Lyris's own again
	await page.goto(`/modules/formulize/index.php?sid=${exhibits.sid}`);
	await expect(page.locator('.lyris-pagination')).toBeAttached();
	await expect(page.locator('.e2e-variables-marker')).toHaveCount(0);
	await page.close();
});

test('Emptying both tabs in one save removes the screen\'s folder', async ({ browser }) => {
	const page = await adminPage(browser);
	await customizeScreenTemplates(page, exhibits);
	expect(screenTemplateFolderExists('Lyris', exhibits.sid, 'variables')).toBe(true);
	await emptyScreenTemplates(page, exhibits);
	expect(screenTemplateFolderExists('Lyris', exhibits.sid)).toBe(false);
	await page.close();
});

test('The update removes copies of the defaults, and keeps what was edited', async ({ browser }) => {
	test.setTimeout(600000); // two screens customized, a form made, and the update run
	const page = await adminPage(browser);
	// Exhibits, with one variable template edited
	await customizeScreenTemplates(page, exhibits);
	await openScreenTemplatesTab(page, exhibits, 'variables');
	const input = page.locator('textarea[name="variabletemplates-pageNavControls"] + .CodeMirror').getByRole('textbox');
	await input.press('ControlOrMeta+End');
	await input.press('Enter');
	await page.keyboard.type(MARKER_LINE);
	await saveAdminForm(page);
	await openScreenTemplatesTab(page, exhibits, 'variables');
	expect(await variableTemplateText(page, 'pageNavControls')).toContain(MARKER_LINE);
	// and the list of a form of our own, customized and not edited
	fid = await createMuseumForm(page, FORM_TITLE);
	const untouched = { sid: parseInt(dbQuery(`SELECT sid FROM ${dbPrefix()}_formulize_screen WHERE fid = ${fid} AND type = 'listOfEntries'`)[0][0], 10), fid };
	expect(screenTheme(untouched.sid)).toBe('Lyris');
	await customizeScreenTemplates(page, untouched);
	expect(screenTemplateFolderExists('Lyris', untouched.sid, 'variables')).toBe(true);

	await page.goto('/modules/formulize/admin/ui.php?op=patchDB-only&f=002');
	await page.getByRole('button', { name: 'Update Formulize - Database Only (002 only)' }).click();
	await expect(page.getByRole('link', { name: 'Close' })).toBeVisible({ timeout: 120000 });
	expect(await page.locator('body').innerText()).toContain("copies of their theme's default templates");

	// the edited one stays, and the copies beside it go
	expect(screenTemplateFolderExists('Lyris', exhibits.sid, 'variables/pageNavControls.php')).toBe(true);
	expect(screenTemplateFolderExists('Lyris', exhibits.sid, 'variables/currentViewList.php')).toBe(false);
	expect(screenTemplateFolderExists('Lyris', exhibits.sid, 'toptemplate.php')).toBe(false);
	await openScreenTemplatesTab(page, exhibits, 'variables');
	await expect(variableTemplatesForm(page).locator('legend')).toHaveText(['$pageNavControls']);
	expect(await variableTemplateText(page, 'pageNavControls')).toContain(MARKER_LINE);
	// the screen that was never edited has nothing of its own left
	expect(screenTemplateFolderExists('Lyris', untouched.sid)).toBe(false);
	await openScreenTemplatesTab(page, untouched);
	await expect(page.getByRole('button', { name: 'Customize the Templates' })).toBeVisible();
	// what's left of Exhibits' is cleared by afterAll
	await page.close();
});

test('Deleting a screen removes its templates, variable templates and all', async ({ browser }) => {
	const page = await adminPage(browser);
	await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${fid}&tab=screens`);
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Create a new Screen' }).click();
	await waitForAdminPageReady(page);
	await page.getByRole('textbox', { name: 'Name of the screen:' }).fill(SCREEN_TITLE);
	await page.getByLabel('What kind of screen is this:').selectOption('listOfEntries');
	await page.getByRole('textbox', { name: 'Handle for the screen:' }).fill('e2e_variable_templates_list');
	await saveAdminForm(page);
	await waitForAdminPageReady(page);
	const screen = { sid: parseInt(dbQuery(`SELECT sid FROM ${dbPrefix()}_formulize_screen WHERE fid = ${fid} AND title = '${SCREEN_TITLE}'`)[0][0], 10), fid };
	await customizeScreenTemplates(page, screen);
	expect(screenTemplateFolderExists('Lyris', screen.sid, 'variables')).toBe(true);

	await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${fid}&tab=screens`);
	await waitForAdminPageReady(page);
	const remove = page.locator(`a.deletescreen[target="${screen.sid}"]`);
	await expect(remove).toBeVisible();
	page.once('dialog', dialog => dialog.accept());
	await remove.click();
	// the save reloads the page without the screen
	await expect(remove).toHaveCount(0, { timeout: 60000 });
	await waitForAdminPageReady(page);
	expect(dbQuery(`SELECT sid FROM ${dbPrefix()}_formulize_screen WHERE sid = ${screen.sid}`)).toHaveLength(0);
	expect(screenTemplateFolderExists('Lyris', screen.sid)).toBe(false);
	await page.close();
});

test('Deleting a form removes its screens\' templates, variable templates and all', async ({ browser }) => {
	const page = await adminPage(browser);
	const list = { sid: parseInt(dbQuery(`SELECT sid FROM ${dbPrefix()}_formulize_screen WHERE fid = ${fid} AND type = 'listOfEntries'`)[0][0], 10), fid };
	await customizeScreenTemplates(page, list);
	expect(screenTemplateFolderExists('Lyris', list.sid, 'variables')).toBe(true);
	await deleteMuseumForm(page, fid);
	expect(screenTemplateFolderExists('Lyris', list.sid)).toBe(false);
	await page.close();
});
