const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, waitForAdminPageReady, addElementForm, ElementType, setCodeMirrorValue, createMuseumForm,
	deleteMuseumFormsTitled, showPermissionsFor, waitForFormulizeFormToken, leavePage, dbQuery, dbPrefix, sqlEscape } from '../../utils';
const { execFileSync } = require('child_process');
const { webContainer } = require('../../docker-containers');

// A form screen's navigation buttons, its Thank You page, and where its intro and thanks text are kept.
//
// The Options tab has a checkbox for each of the four buttons at the bottom of the form, and one for the Thank You
// page. A Thank You page holds the Save and Continue button on, since that is how users reach it, and text written
// for the Thank You page turns the page on. The link on the Thank You page says Continue unless the admin says
// otherwise. When the screen has no done destination of its own, users go back where they came from. A screen set to
// reload blank waits until after the Thank You page, which is still about the entry just saved, new or edited.
//
// The intro and thanks text are kept in files in modules/formulize/code/, named for the screen's handle, and so is
// the content of a static content element, HTML as well as PHP. A file moves when the screen's handle changes, and
// a cloned form gets copies of its elements' files.
//
// Everything is made through the admin UI on a form of its own, which the last test deletes. The database and the
// server's files are only read, to check what the application stored.

test.describe.configure({ mode: 'serial' });

const FORM_TITLE = 'Buttons and thanks';
const CLONED_TITLE = FORM_TITLE + ' - Cloned';
const END_USER = 'curator2';
const END_USER_GROUP = 'All Curators';
const CONTENT_HANDLE = 'bt_static_content';
const CONTENT = '<p id="bt-content">Some HTML in a static content element</p>';
const INTRO = '<?php\nprint "<p id=\'bt-intro\'>The intro ran " . (1 + 1) . " times</p>";';
const INTRO_SEEN = 'The intro ran 2 times';
const THANKS = '<div id="bt-thanks">All done here.</div>{thankYouNav}';
const RENAMED_HANDLE = 'bt_renamed_form_screen';
const NOTE_HANDLE = 'bt_note';
// thanks text that says which entry the Thank You page is about
const THANKS_WITH_ENTRY = '<?php\nprint "<div id=\'bt-entry\'>" . (is_numeric($entry_id) ? intval($entry_id) : "none") . "</div>" . $thankYouNav;';

let fid = 0;
let formSid = 0;

test.afterAll(async ({ browser }) => {
	const page = await browser.newPage();
	await login(page, 'admin');
	await deleteMuseumFormsTitled(page, [CLONED_TITLE, FORM_TITLE]);
	await page.close();
});

const screenTab = (tab) => `/modules/formulize/admin/ui.php?page=screen&fid=${fid}&sid=${formSid}&tab=${tab}`;

// What a file in modules/formulize/code/ holds, or null if there is no such file. Read only. A box's text is posted
// with \r\n line breaks, the way browsers send a textarea, so line breaks are compared as \n.
function codeFile(name) {
	const out = execFileSync('docker', ['exec', webContainer(), 'sh', '-c',
		`f='/var/www/html/modules/formulize/code/${name.replace(/[^A-Za-z0-9_.]/g, '')}'; if [ -f "$f" ]; then printf 'FOUND:'; cat "$f"; fi`], { encoding: 'utf8' });
	return out.startsWith('FOUND:') ? out.substring(6).replace(/\r\n/g, '\n') : null;
}

function screenHandle() {
	return dbQuery(`SELECT screen_handle FROM ${dbPrefix()}_formulize_screen WHERE sid = ${formSid}`)[0][0];
}

async function openScreenTab(page, tab, linkText) {
	await page.goto(screenTab(tab));
	await waitForAdminPageReady(page);
	await page.locator('a', { hasText: new RegExp(`^${linkText}$`) }).first().click();
}

const openOptions = (page) => openScreenTab(page, 'options', 'Options');

async function openText(page) {
	await openScreenTab(page, 'text', 'Text');
	await expect(page.locator('textarea[name="screens-introtext"] + .CodeMirror')).toBeVisible();
}

// What a CodeMirror box holds, read from the editor rather than the textarea under it
function editorValue(page, boxName) {
	return page.locator(`textarea[name="${boxName}"] + .CodeMirror`).evaluate(editor => editor.CodeMirror.getValue());
}

// The form, as the end user, in a browser of its own so the admin stays logged in on the other
async function asEndUser(browser, work) {
	const context = await browser.newContext();
	const page = await context.newPage();
	try {
		await login(page, END_USER);
		await page.goto(`/modules/formulize/index.php?sid=${formSid}`);
		await expect(page.locator('#bt-content')).toBeVisible();
		await work(page);
	} finally {
		await context.close();
	}
}

// Finish the one page form as the end user, and land on the Thank You page
async function finishForm(page) {
	await page.locator('div#formulizeform').click();
	await waitForFormulizeFormToken(page);
	await page.getByRole('button', { name: 'Save and Finish' }).click();
	await expect(page.getByRole('link', { name: 'Continue' })).toBeVisible({ timeout: 30000 });
}

test('Build a form with static content, and let the end user see it', async ({ page }) => {
	test.setTimeout(300000);
	await login(page, 'admin');
	await deleteMuseumFormsTitled(page, [CLONED_TITLE, FORM_TITLE]); // left behind by a run that was killed outright
	fid = await createMuseumForm(page, FORM_TITLE);

	await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${fid}&tab=elements`);
	await waitForAdminPageReady(page);
	await addElementForm(page, ElementType.fullWidthContent);
	await waitForAdminPageReady(page);
	await page.locator('input[name="elements-ele_caption"]').fill('Static content');
	await page.locator('input[name="elements-ele_handle"]').fill(CONTENT_HANDLE);
	await page.getByRole('link', { name: 'Options' }).click();
	await setCodeMirrorValue(page, 'textarea[name="elements-ele_value[0]"]', CONTENT);
	await saveAdminForm(page);
	await expect(page.getByRole('heading')).toContainText('Static content');

	// a box to type in, so that finishing the form saves an entry
	await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${fid}&tab=elements`);
	await waitForAdminPageReady(page);
	await addElementForm(page, ElementType.text);
	await waitForAdminPageReady(page);
	await page.locator('input[name="elements-ele_caption"]').fill('Note');
	await page.locator('input[name="elements-ele_handle"]').fill(NOTE_HANDLE);
	await saveAdminForm(page);
	await expect(page.getByRole('heading')).toContainText('Note');

	// HTML is kept in the element's file, like code, and not in the database
	expect(codeFile(`fullWidthContent_${CONTENT_HANDLE}.php`), 'the content is in its file').toBe(CONTENT);
	const [storedEleValue] = dbQuery(`SELECT ele_value FROM ${dbPrefix()}_formulize WHERE ele_handle = '${CONTENT_HANDLE}'`)[0];
	expect(storedEleValue, 'the database has no copy of the content').not.toContain('bt-content');

	for (const [sid, type] of dbQuery(`SELECT sid, type FROM ${dbPrefix()}_formulize_screen WHERE fid = ${fid}`)) {
		if (type === 'multiPage') formSid = parseInt(sid, 10);
	}
	expect(formSid, 'the new form has a form screen').toBeGreaterThan(0);

	const permissionsTab = `/modules/formulize/admin/ui.php?page=form&fid=${fid}&tab=permissions`;
	await page.goto(permissionsTab);
	await showPermissionsFor(page, [END_USER_GROUP]);
	const group = page.getByRole('group', { name: END_USER_GROUP });
	await group.locator('input[name$="_view_form"]').first().check();
	await group.locator('input[name$="_add_own_entry"]').first().check();
	await group.locator('input[name$="_update_own_entry"]').first().check();
	await saveAdminForm(page);
	await page.goto(permissionsTab);
	await showPermissionsFor(page, [END_USER_GROUP]);
	await expect(group.locator('input[name$="_view_form"]').first()).toBeChecked();
	await expect(group.locator('input[name$="_add_own_entry"]').first()).toBeChecked();
	await expect(group.locator('input[name$="_update_own_entry"]').first()).toBeChecked();
});

test('A new form screen has tabs, Save and Close, and no Thank You page', async ({ page, browser }) => {
	await login(page, 'admin');
	await openOptions(page);
	await expect(page.locator('#showtabs')).toBeChecked();
	await expect(page.locator('#showsavebutton')).toBeChecked();
	await expect(page.locator('#showclosebutton')).toBeChecked();
	await expect(page.locator('#showprevbutton')).not.toBeChecked();
	await expect(page.locator('#shownextbutton')).not.toBeChecked();
	await expect(page.locator('#showthankspage')).not.toBeChecked();
	await expect(page.locator('#thankyoulinktext')).toBeHidden();
	await expect(page.locator('#donedestmode-back')).toBeChecked();
	await expect(page.locator('#screens-donedest')).toBeHidden();
	// the box for a specific URL is there only while that is chosen
	await page.locator('#donedestmode-url').check();
	await expect(page.locator('#screens-donedest')).toBeVisible();
	await page.locator('#donedestmode-back').check();
	await expect(page.locator('#screens-donedest')).toBeHidden();

	await asEndUser(browser, async userPage => {
		await expect(userPage.getByRole('button', { name: 'Save', exact: true })).toBeVisible();
		await expect(userPage.getByRole('button', { name: 'Close', exact: true })).toBeVisible();
		await expect(userPage.getByRole('button', { name: 'Save and Finish' })).toHaveCount(0);
		await leavePage(userPage);
	});
});

test('The Thank You page holds Save and Continue on, and its link says Continue', async ({ page, browser }) => {
	await login(page, 'admin');
	await openOptions(page);
	await page.locator('#showthankspage').check();
	await expect(page.locator('#shownextbutton')).toBeChecked();
	await expect(page.locator('#shownextbutton')).toBeDisabled();
	await expect(page.locator('#thankyoulinktext')).toBeVisible();
	await expect(page.locator('#thankyoulinktext')).toHaveValue('Continue');
	await page.locator('#showclosebutton').uncheck();
	await saveAdminForm(page);

	await openOptions(page);
	await expect(page.locator('#showthankspage')).toBeChecked();
	await expect(page.locator('#shownextbutton')).toBeChecked();
	await expect(page.locator('#shownextbutton')).toBeDisabled();
	await expect(page.locator('#showclosebutton')).not.toBeChecked();
	await expect(page.locator('#thankyoulinktext')).toHaveValue('Continue');
	const [donedest, finishisdone, shownext] = dbQuery(`SELECT donedest, finishisdone, shownextbutton FROM ${dbPrefix()}_formulize_screen_multipage WHERE sid = ${formSid}`)[0];
	expect(donedest, 'back to the previous page is no done destination of its own').toBe('');
	expect(finishisdone, 'the screen ends on the Thank You page').toBe('0');
	expect(shownext, 'the screen has Save and Continue').toBe('1');

	await asEndUser(browser, async userPage => {
		await expect(userPage.getByRole('button', { name: 'Save', exact: true })).toBeVisible();
		await expect(userPage.getByRole('button', { name: 'Close', exact: true })).toHaveCount(0);
		await finishForm(userPage);
		await expect(userPage.getByRole('heading', { name: 'Thank you!' })).toBeVisible();
		await leavePage(userPage);
	});
});

test('Intro and thanks text are kept in files, run as written, and turn the Thank You page on', async ({ page, browser }) => {
	test.setTimeout(180000);
	await login(page, 'admin');
	// turn the Thank You page off first, so the thanks text is what turns it on
	await openOptions(page);
	await page.locator('#showthankspage').uncheck();
	await saveAdminForm(page);
	await openOptions(page);
	await expect(page.locator('#showthankspage')).not.toBeChecked();

	await openText(page);
	await setCodeMirrorValue(page, 'textarea[name="screens-introtext"]', INTRO);
	await setCodeMirrorValue(page, 'textarea[name="screens-thankstext"]', THANKS);
	await saveAdminForm(page);

	const handle = screenHandle();
	expect(codeFile(`multipage_introtext_${handle}.php`), 'the intro text is in its file').toBe(INTRO);
	expect(codeFile(`multipage_thankstext_${handle}.php`), 'the thanks text is in its file').toBe(THANKS);

	await openOptions(page);
	await expect(page.locator('#showthankspage')).toBeChecked();
	await expect(page.locator('#showthankspage')).toBeDisabled();

	await asEndUser(browser, async userPage => {
		await expect(userPage.locator('#bt-intro')).toHaveText(INTRO_SEEN);
		await finishForm(userPage);
		await expect(userPage.locator('#bt-thanks')).toHaveText('All done here.');
		await leavePage(userPage);
	});
});

test('The text files move with the screen\'s handle', async ({ page }) => {
	await login(page, 'admin');
	const oldHandle = screenHandle();
	await openScreenTab(page, 'settings', 'Settings');
	await page.locator('#screens-screen_handle').fill(RENAMED_HANDLE);
	await saveAdminForm(page);
	await expect.poll(() => screenHandle()).toBe(RENAMED_HANDLE);

	expect(codeFile(`multipage_thankstext_${RENAMED_HANDLE}.php`), 'the thanks text moved').toBe(THANKS);
	expect(codeFile(`multipage_introtext_${RENAMED_HANDLE}.php`), 'the intro text moved').toBe(INTRO);
	expect(codeFile(`multipage_thankstext_${oldHandle}.php`), 'nothing is left under the old handle').toBeNull();

	await openText(page);
	expect(await editorValue(page, 'screens-thankstext')).toBe(THANKS);
	expect(await editorValue(page, 'screens-introtext')).toBe(INTRO);
});

test('With reload blank, the Thank You page still knows the entry just saved, new or edited', async ({ page, browser }) => {
	test.setTimeout(180000);
	await login(page, 'admin');
	await openOptions(page);
	await page.locator('#blank').check();
	await saveAdminForm(page);
	await openOptions(page);
	await expect(page.locator('#blank')).toBeChecked();

	await openText(page);
	await setCodeMirrorValue(page, 'textarea[name="screens-thankstext"]', THANKS_WITH_ENTRY);
	await saveAdminForm(page);
	await openOptions(page);
	await expect(page.locator('#showthankspage')).toBeChecked();

	const [formHandle] = dbQuery(`SELECT form_handle FROM ${dbPrefix()}_formulize_id WHERE id_form = ${fid}`)[0];
	const entryWithNote = (note) => {
		const rows = dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_${formHandle.replace(/[^A-Za-z0-9_]/g, '')} WHERE ${NOTE_HANDLE} = '${sqlEscape(note)}'`);
		return rows.length ? rows[0][0] : '';
	};

	let newEntryId = '';
	await asEndUser(browser, async userPage => {
		await userPage.getByRole('textbox', { name: 'Note' }).fill('A new entry');
		await finishForm(userPage);
		await expect.poll(() => entryWithNote('A new entry'), 'the entry was saved').not.toBe('');
		newEntryId = entryWithNote('A new entry');
		await expect(userPage.locator('#bt-entry')).toHaveText(newEntryId);
		await leavePage(userPage);
	});

	// the same entry, opened again and changed
	const context = await browser.newContext();
	const userPage = await context.newPage();
	try {
		await login(userPage, END_USER);
		await userPage.goto(`/modules/formulize/index.php?sid=${formSid}&ve=${newEntryId}`);
		const note = userPage.getByRole('textbox', { name: 'Note' });
		await expect(note).toHaveValue('A new entry');
		await note.fill('An edited entry');
		await finishForm(userPage);
		await expect(userPage.locator('#bt-entry')).toHaveText(newEntryId);
		expect(entryWithNote('An edited entry'), 'the edit went to the same entry').toBe(newEntryId);
		await leavePage(userPage);
	} finally {
		await context.close();
	}
});

test('Emptying the text removes its files, and the Thank You page can be turned off again', async ({ page, browser }) => {
	await login(page, 'admin');
	await openText(page);
	await setCodeMirrorValue(page, 'textarea[name="screens-introtext"]', '');
	// an opening tag on its own is nothing
	await setCodeMirrorValue(page, 'textarea[name="screens-thankstext"]', '<?php\n\n');
	await saveAdminForm(page);
	expect(codeFile(`multipage_introtext_${RENAMED_HANDLE}.php`), 'no intro text, no file').toBeNull();
	expect(codeFile(`multipage_thankstext_${RENAMED_HANDLE}.php`), 'no thanks text, no file').toBeNull();

	await openOptions(page);
	await expect(page.locator('#showthankspage')).toBeEnabled();
	await page.locator('#showthankspage').uncheck();
	await expect(page.locator('#shownextbutton')).toBeEnabled();
	await page.locator('#shownextbutton').uncheck();
	await saveAdminForm(page);
	await openOptions(page);
	await expect(page.locator('#showthankspage')).not.toBeChecked();
	await expect(page.locator('#shownextbutton')).not.toBeChecked();

	await asEndUser(browser, async userPage => {
		await expect(userPage.locator('#bt-intro')).toHaveCount(0);
		await expect(userPage.getByRole('button', { name: 'Save and Finish' })).toHaveCount(0);
		await leavePage(userPage);
	});
});

test('A cloned form keeps its static content', async ({ page }) => {
	await login(page, 'admin');
	await page.goto('/modules/formulize/admin/ui.php?page=home');
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	await waitForAdminPageReady(page);
	const formBox = page.locator(`div.form-listing-box[formid="${fid}"]`);
	await expect(formBox).toBeVisible();
	await formBox.locator('.form-name-text').click();
	await Promise.all([
		page.waitForEvent('load'),
		formBox.locator('a.cloneform').click(),
	]);
	await expect.poll(() => dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title = '${sqlEscape(CLONED_TITLE)}'`).length).toBe(1);
	const [clonedFid] = dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title = '${sqlEscape(CLONED_TITLE)}'`)[0];
	const [clonedHandle] = dbQuery(`SELECT ele_handle FROM ${dbPrefix()}_formulize WHERE id_form = ${parseInt(clonedFid, 10)} AND ele_type = 'fullWidthContent'`)[0];
	expect(clonedHandle).not.toBe(CONTENT_HANDLE);
	expect(codeFile(`fullWidthContent_${clonedHandle}.php`), 'the clone has its own copy of the content').toBe(CONTENT);
});

test('Deleting the forms removes their files', async ({ page }) => {
	await login(page, 'admin');
	const [clonedHandle] = dbQuery(`SELECT e.ele_handle FROM ${dbPrefix()}_formulize AS e, ${dbPrefix()}_formulize_id AS f WHERE e.id_form = f.id_form AND f.form_title = '${sqlEscape(CLONED_TITLE)}' AND e.ele_type = 'fullWidthContent'`)[0];
	await deleteMuseumFormsTitled(page, [CLONED_TITLE, FORM_TITLE]);
	expect(codeFile(`fullWidthContent_${CONTENT_HANDLE}.php`)).toBeNull();
	expect(codeFile(`fullWidthContent_${clonedHandle}.php`)).toBeNull();
});
