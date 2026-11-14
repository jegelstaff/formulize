const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import {
	login, dbQuery, dbPrefix, getSiteTheme, waitForAdminPageReady, saveAdminForm,
	chooseSiteThemes, expectSiteTheme, setDBVersion, screenTemplateFolderExists, openScreenTemplatesTab as openTemplatesTab,
	showScreenTemplatesFor as showTemplatesFor, screenTemplatesForm as templatesForm, customizeScreenTemplates as customizeTemplates,
	emptyScreenTemplates as emptyTemplates,
} from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Lyris appearance - the 8.30 update's questions about Lyris
// ============================================================
// When a site that uses Anari is updated to the version that makes Lyris the standard theme, a webmaster is asked
// first whether to switch, which screens should take copies of their custom templates along, and which should start
// using Lyris's screen settings. Nothing changes until the update runs, and cancelling changes nothing.
//
// The CI site is already up to date, so the spec sets the database version back to just before the version that
// brings in Lyris, which is when the update asks, and which patch 021 applies the answers at. That's read from
// include/lyris_update.php, not taken as one below what the code requires, because later versions keep coming.
// Every patch has run already, so running the update from there runs only the ones gated after it, and the update
// itself moves the version back to where the code needs it.
//
// It uses two museum list screens: Collections, which is given custom templates in Anari first, and Exhibits, which
// has none. It changes one setting on Collections, and the site's theme, and puts both back, through the admin pages,
// with every template it made emptied again.

test.describe.configure({ mode: 'serial' });

const UPDATE_PAGE = '/modules/formulize/admin/ui.php?op=patchDB';
// the dbversion that brings in Lyris: the update asks about Lyris only while the stored version is below it
const LYRIS_DBVERSION = (() => {
	const source = fs.readFileSync(path.resolve(__dirname, '../../../../modules/formulize/include/lyris_update.php'), 'utf8');
	const found = source.match(/define\('FORMULIZE_LYRIS_UPDATE_DBVERSION',\s*(\d+)\)/);
	if (!found) throw new Error('FORMULIZE_LYRIS_UPDATE_DBVERSION is not defined in include/lyris_update.php');
	return parseInt(found[1], 10);
})();
let required = 0;
let collections = null;
let exhibits = null;
let anariTopTemplate = '';
let settingsBefore = null;

function listScreen(title) {
	const [row] = dbQuery(`SELECT sid, fid FROM ${dbPrefix()}_formulize_screen WHERE title = '${title}' AND type = 'listOfEntries'`);
	return { sid: parseInt(row[0], 10), fid: parseInt(row[1], 10) };
}

// Every list screen's settings that the update can change, by screen
function listSettings() {
	const settings = {};
	for (const [sid, editdestination, usesearch, entriesperpage] of dbQuery(`SELECT sid, editdestination, usesearch, entriesperpage FROM ${dbPrefix()}_formulize_screen_listofentries`)) {
		settings[sid] = { editdestination, usesearch, entriesperpage };
	}
	return settings;
}

function screenTheme(sid) {
	return dbQuery(`SELECT theme FROM ${dbPrefix()}_formulize_screen WHERE sid = ${sid}`)[0][0];
}

function storedDBVersion() {
	return parseInt(dbQuery(`SELECT dbversion FROM ${dbPrefix()}_modules WHERE dirname = 'formulize'`)[0][0], 10);
}

// The version the installed code requires, as the Set Database Version page says, without setting anything
async function requiredDBVersion(page) {
	await page.goto('/modules/formulize/admin/ui.php?op=setDBVersion');
	const said = (await page.locator('body').innerText()).match(/Version required by the code currently installed:\s*(\d+)/);
	expect(said, 'the page says what version the code requires').not.toBeNull();
	return parseInt(said[1], 10);
}

async function adminPage(browser) {
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	return page;
}

// What one of the screen's templates holds, on its Templates tab, such as 'toptemplate'
async function templateText(page, screen, name) {
	await openTemplatesTab(page, screen);
	return templatesForm(page).locator(`textarea[name="screens-${name}"] + .CodeMirror`).evaluate(editor => editor.CodeMirror.getValue());
}

async function topTemplate(page, screen) {
	return templateText(page, screen, 'toptemplate');
}

// Add a line of our own to the end of the screen's top template, and save. Copies of the defaults alone don't count
// as templates of its own when they're copied to another theme, so this is what gives a screen something to take.
const TOP_TEMPLATE_CHANGE = '// Changed for the update to Lyris spec';
async function changeTopTemplate(page, screen) {
	await openTemplatesTab(page, screen);
	const input = templatesForm(page).locator('textarea[name="screens-toptemplate"] + .CodeMirror').getByRole('textbox');
	await input.press('ControlOrMeta+End');
	await input.press('Enter');
	await page.keyboard.type(TOP_TEMPLATE_CHANGE);
	await saveAdminForm(page);
}

// Open the questions on the update page, and choose what to do with the theme
async function openWizard(page, choice) {
	await page.goto(UPDATE_PAGE);
	await page.locator('#formulize-lyris-open').click();
	const dialog = page.locator('#formulize-lyris-wizard');
	await expect(dialog.getByRole('heading', { name: 'Formulize has a new theme' })).toBeVisible();
	await dialog.locator(`input[name="lyris_theme_choice"][value="${choice}"]`).check();
	return dialog;
}

async function next(dialog) {
	await dialog.getByRole('button', { name: 'Next', exact: true }).click();
}

test.beforeAll(async ({ browser }) => {
	collections = listScreen('Collections');
	exhibits = listScreen('Exhibits');
	const page = await adminPage(browser);
	try {
		expect(getSiteTheme().site).toBe('Anari');
		// a run that stopped partway can have left templates behind
		await emptyTemplates(page, collections);
		await emptyTemplates(page, exhibits);
		await customizeTemplates(page, collections);
		await changeTopTemplate(page, collections);
		anariTopTemplate = await topTemplate(page, collections);
		expect(anariTopTemplate).toContain(TOP_TEMPLATE_CHANGE);
		settingsBefore = listSettings();
		// kept only once the site is up to date, since afterAll puts the stored version back to it: a site that
		// isn't would otherwise be marked as updated without the update having run
		const codeVersion = await requiredDBVersion(page);
		expect(storedDBVersion(), 'the site has had the update its code needs, before the spec starts').toBe(codeVersion);
		required = codeVersion;
	} finally {
		await page.close();
	}
});

test.afterAll(async ({ browser }) => {
	// emptying and saving every template it made, for two screens in two themes, takes longer than a hook is given
	test.setTimeout(300000);
	const page = await adminPage(browser);
	try {
		// the version back where the code needs it, in case the update never ran
		if (required && storedDBVersion() !== required) {
			await setDBVersion(page, required);
		}
		if (getSiteTheme().site !== 'Anari' || getSiteTheme().admin !== 'Anari') {
			await chooseSiteThemes(page, 'Anari', 'Anari');
		}
		// Collections opens its entries full screen again, as it did
		const before = settingsBefore ? settingsBefore[collections.sid] : null;
		if (before && listSettings()[collections.sid].editdestination !== before.editdestination) {
			await page.goto(`/modules/formulize/admin/ui.php?page=screen&fid=${collections.fid}&sid=${collections.sid}&tab=interface`);
			await waitForAdminPageReady(page);
			await page.locator(`input[name="screens-editdestination"][value="${before.editdestination}"]`).check();
			await saveAdminForm(page);
		}
		// every template the spec made, in Lyris and in Anari, ending with each screen's Templates tab on Anari
		for (const screen of [collections, exhibits]) {
			for (const theme of ['Lyris', 'Anari']) {
				await showTemplatesFor(page, screen, theme);
				await emptyTemplates(page, screen);
			}
		}
	} finally {
		await page.close();
	}
	expect(storedDBVersion()).toBe(required);
	expect(getSiteTheme().site).toBe('Anari');
});

test('The update asks about Lyris first, and cancelling changes nothing', async ({ browser }) => {
	const page = await adminPage(browser);
	// just before the version that brings in Lyris, so the update asks about it again
	await setDBVersion(page, LYRIS_DBVERSION - 1);

	const dialog = await openWizard(page, 'switch');
	// Anari and Lyris, side by side, drawn from the Appearance editor's samples. Each theme has a frame for each
	// sample, and the List sample is the one shown first
	await expect(dialog.locator('iframe[data-theme="Anari"]:visible')).toHaveAttribute('src', /appearance_preview\.php\?theme=Anari&screen=list/);
	await expect(dialog.locator('iframe[data-theme="Lyris"]:visible')).toHaveAttribute('src', /appearance_preview\.php\?theme=Lyris&screen=list/);
	await next(dialog);
	// the looks Lyris comes with, to start with one
	await expect(dialog.getByRole('heading', { name: 'How Lyris looks' })).toBeVisible();
	await expect(dialog.locator('label', { hasText: 'Altan' })).toBeVisible();
	await next(dialog);
	// Lyris's settings, checked for every screen when the whole site switches
	await expect(dialog.getByRole('heading', { name: "Lyris's screen settings" })).toBeVisible();
	await expect(dialog.locator(`input[name="lyris_screen_settings[${collections.sid}][]"][value="editdestination"]`)).toBeChecked();
	await next(dialog);
	// Collections has a changed template of its own in Anari, and none in Lyris, and the wizard says which one
	await expect(dialog.getByRole('heading', { name: 'Screens with their own templates' })).toBeVisible();
	await expect(dialog.locator(`input.formulize-lyris-template-screen[value="${collections.sid}"]`)).toBeChecked();
	await expect(dialog.locator('label', { has: page.locator(`input.formulize-lyris-template-screen[value="${collections.sid}"]`) }).locator('.formulize-lyris-template-files')).toHaveText('Changed: toptemplate.php');
	await expect(dialog.locator(`input.formulize-lyris-template-screen[value="${exhibits.sid}"]`)).toHaveCount(0);
	await next(dialog);
	await expect(dialog.getByRole('heading', { name: 'Ready to update' })).toBeVisible();
	await expect(dialog.locator('.formulize-lyris-summary')).toContainText('Your site switches to Lyris.');
	await dialog.getByRole('button', { name: 'Cancel' }).click();
	await expect(dialog).toBeHidden();

	// nothing has changed
	expect(storedDBVersion()).toBe(LYRIS_DBVERSION - 1);
	expect(getSiteTheme().site).toBe('Anari');
	expect(getSiteTheme().admin).toBe('Anari');
	expect(screenTheme(collections.sid)).toBe('Anari');
	expect(listSettings()).toEqual(settingsBefore);
	await page.close();
});

test('The update switches the site, copies the chosen templates, and changes only the chosen settings', async ({ browser }) => {
	test.setTimeout(600000);
	const page = await adminPage(browser);
	const dialog = await openWizard(page, 'switch');
	await next(dialog);
	// the look Lyris starts with stays as it is
	await expect(dialog.getByRole('heading', { name: 'How Lyris looks' })).toBeVisible();
	await next(dialog);
	// only Collections, and only the drawer
	for (const all of await dialog.locator('.formulize-lyris-setting-all').all()) {
		await all.uncheck();
	}
	await dialog.locator(`input[name="lyris_screen_settings[${collections.sid}][]"][value="editdestination"]`).check();
	await next(dialog);
	await dialog.getByRole('button', { name: 'Select none' }).click();
	await dialog.locator(`input.formulize-lyris-template-screen[value="${collections.sid}"]`).check();
	await next(dialog);
	await expect(dialog.locator('.formulize-lyris-summary')).toContainText('1 screen gets Lyris\'s screen settings.');
	await expect(dialog.locator('.formulize-lyris-summary')).toContainText('1 screen gets a copy of its templates for Lyris.');
	// the update runs for a while, so the click doesn't wait for the page it brings
	await dialog.getByRole('button', { name: 'Update Formulize now' }).click({ noWaitAfter: true });
	const nextSteps = page.locator('.formulize-lyris-next-steps');
	await expect(nextSteps).toBeVisible({ timeout: 540000 });
	await expect(nextSteps).toContainText('What to do next');
	await expect(page.locator('#formulize-patch-panel')).toContainText('Your site now uses Lyris.');

	expect(storedDBVersion()).toBe(required);
	expect(getSiteTheme().site).toBe('Lyris');
	expect(getSiteTheme().admin).toBe('Lyris');
	await expectSiteTheme(page, 'Lyris');
	// every screen follows the site
	expect(screenTheme(collections.sid)).toBe('Lyris');
	expect(screenTheme(exhibits.sid)).toBe('Lyris');
	// Collections opens its entries in the drawer, and nothing else changed
	const after = listSettings();
	const expected = JSON.parse(JSON.stringify(settingsBefore));
	expected[collections.sid].editdestination = 'drawer';
	expect(after).toEqual(expected);
	// Collections has its Anari templates in Lyris: its changed one, and Anari's for the rest, so its rows still fit
	// the table they're in
	expect(await topTemplate(page, collections)).toBe(anariTopTemplate);
	expect(await templateText(page, collections, 'listtemplate')).toContain('floating-column');
	await page.close();
});

test('A screen without templates of its own starts from Lyris\'s templates', async ({ browser }) => {
	const page = await adminPage(browser);
	await openTemplatesTab(page, exhibits);
	await expect(page.locator('select[name="screens-theme"]')).toHaveValue('Lyris');
	await customizeTemplates(page, exhibits);
	expect(await topTemplate(page, exhibits)).toContain('fz-');
	// Lyris's variable templates come along too, so emptying the Templates tab alone leaves the screen's folder
	expect(screenTemplateFolderExists('Lyris', exhibits.sid, 'variables')).toBe(true);
	await emptyTemplates(page, exhibits);
	// emptying both tabs leaves nothing of the screen's own in Lyris, not even the folder
	expect(screenTemplateFolderExists('Lyris', exhibits.sid)).toBe(false);
	await page.close();
});

test('A screen\'s Templates tab copies its templates from another theme', async ({ browser }) => {
	const page = await adminPage(browser);
	await emptyTemplates(page, collections);
	await openTemplatesTab(page, collections);
	await expect(page.getByText('This screen has templates of its own in another theme.')).toBeVisible();
	const copy = page.getByRole('button', { name: "Copy this screen's templates from Anari" });
	await expect(copy).toBeVisible();
	await Promise.all([page.waitForEvent('load'), copy.click()]);
	expect(await topTemplate(page, collections)).toBe(anariTopTemplate);
	await expect(page.getByRole('button', { name: "Copy this screen's templates from Anari" })).toHaveCount(0);

	// switching the site back to Anari moves the screens back too
	await chooseSiteThemes(page, 'Anari', 'Anari');
	expect(screenTheme(collections.sid)).toBe('Anari');
	await openTemplatesTab(page, collections);
	await expect(page.locator('select[name="screens-theme"]')).toHaveValue('Anari');
	await page.close();
});
