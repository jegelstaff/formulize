const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, waitForAdminPageReady, addElementForm, ElementType, setCodeMirrorValue, createMuseumForm,
	deleteMuseumForm, deleteMuseumFormsTitled, showPermissionsFor, waitForFormulizeFormToken, leavePage, dbQuery, dbPrefix } from '../../utils';
const { execFileSync } = require('child_process');
const { webContainer } = require('../../docker-containers');

// What an admin types into a form screen's intro and thanks text, a screen's templates, a text box's default value
// and a block of static content has to come back exactly as it was typed, however many times it is saved, and be
// shown to the people using the form exactly as written.
//
// Two things used to go wrong. Intro and thanks text was HTML encoded on every save, and decoded all the way on the
// way out, so entities an admin typed on purpose were lost: &nbsp; became a plain space, &amp;nbsp; lost its
// &amp;, and &lt;b&gt; turned into real bold. Once a screen was saved again, the loss was stored. And text box
// defaults and the markup of static content had their backslashes stripped as they were drawn, so ' aren\'t ' in
// PHP code became a parse error, and C:\temp became C:temp.
//
// Templates were already saved as typed, but a template is shown in a textarea, and one containing </textarea>
// used to end its box early and be cut short on the next save. And deleting a screen used to leave its own template
// files behind on the server.
//
// So each thing here is typed in the admin and saved, checked in the admin, saved again without being touched and
// checked again, and then looked at by curator2, a regular user, whose group is given access to the form for this. Everything
// is made through the admin UI on a form of its own, which the last test deletes. The database and the server's files
// are only read, to find the new screens and to check what deleting them did.

test.describe.configure({ mode: 'serial' });

const FORM_TITLE = 'Entities and backslashes';
const END_USER = 'curator2';
const END_USER_GROUP = 'All Curators';

// Typed text, with the backslashes and entities each case is about, and what a person should see
const INTRO = '<p id="eb-intro">A&nbsp;B | &amp;nbsp; | &lt;b&gt;x&lt;/b&gt; | aren\\\'t \\"y\\" C:\\temp</p>';
const INTRO_SEEN = 'A\u00a0B | &nbsp; | <b>x</b> | aren\\\'t \\"y\\" C:\\temp';
const THANKS = '<?php\nprint \'<p id="eb-thanks">aren\\\'t &amp;nbsp; C:\\\\temp</p>\';';
const THANKS_SEEN = 'aren\'t &nbsp; C:\\temp';
const PLAIN_DEFAULT = 'C:\\temp\\x';
const CODED_DEFAULT = '$default = \'aren\\\'t "ok" C:\\\\temp\';';
const CODED_DEFAULT_SEEN = 'aren\'t "ok" C:\\temp';
const CONTENT = '<p id="eb-content">regex \\d and C:\\temp</p>';
const CONTENT_SEEN = 'regex \\d and C:\\temp';
const TEMPLATE = '<?php\n// a closing </textarea> tag in a template stays in its box\nprint "<p id=\'eb-template\'>" . \'aren\\\'t C:\\\\temp \\d\' . "</p>";';
const TEMPLATE_SEEN = 'aren\'t C:\\temp \\d';

// The elements, and the box on each one's Options tab that holds what was typed
const ELEMENTS = [
	{ caption: 'Plain path', handle: 'eb_plain_path', type: ElementType.text, box: 'elements-ele_value[2]', typed: PLAIN_DEFAULT },
	{ caption: 'Coded default', handle: 'eb_coded_default', type: ElementType.text, box: 'elements-ele_value[2]', typed: CODED_DEFAULT },
	{ caption: 'Backslash content', handle: 'eb_backslash_content', type: ElementType.fullWidthContent, box: 'elements-ele_value[0]', typed: CONTENT },
];

let fid = 0;
let formSid = 0;
let listSid = 0;

// the last test deletes the form, so this only has anything to do when a test before it failed
test.afterAll(async ({ browser }) => {
	const page = await browser.newPage();
	await login(page, 'admin');
	await deleteMuseumFormsTitled(page, [FORM_TITLE]);
	await page.close();
});

const elementsTab = () => `/modules/formulize/admin/ui.php?page=form&fid=${fid}&tab=elements`;
const screenTab = (sid, tab) => `/modules/formulize/admin/ui.php?page=screen&fid=${fid}&sid=${sid}&tab=${tab}`;

// What a CodeMirror box holds, read from the editor rather than the textarea under it
function editorValue(page, boxName) {
	return page.locator(`textarea[name="${boxName}"] + .CodeMirror`).evaluate(editor => editor.CodeMirror.getValue());
}

// The folders of a screen's own templates on the server, in every theme. Read only, to check what the application did.
function screenTemplateFolders(sid) {
	const out = execFileSync('docker', ['exec', webContainer(), 'sh', '-c',
		`ls -d /var/www/html/modules/formulize/templates/screens/*/${parseInt(sid, 10)} 2>/dev/null || true`], { encoding: 'utf8' });
	return out.split('\n').filter(line => line.trim());
}

// The same page, as the end user, in a browser of its own so the admin stays logged in on the other
async function asEndUser(browser, work) {
	const context = await browser.newContext();
	const page = await context.newPage();
	try {
		await login(page, END_USER);
		await work(page);
	} finally {
		await context.close();
	}
}

async function openElementOptions(page, handle) {
	const [eleId] = dbQuery(`SELECT ele_id FROM ${dbPrefix()}_formulize WHERE ele_handle = '${handle}'`)[0];
	await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=${eleId}&fid=${fid}`);
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Options' }).click();
}

async function openFormScreenText(page) {
	await page.goto(screenTab(formSid, 'text'));
	await waitForAdminPageReady(page);
	await page.locator('a', { hasText: /^Text$/ }).first().click();
	await expect(page.locator('textarea[name="screens-introtext"] + .CodeMirror')).toBeVisible();
}

// Open a screen's templates, giving it its own copies of the default templates first if it has none
async function openScreenTemplates(page, sid) {
	await page.goto(screenTab(sid, 'templates'));
	await waitForAdminPageReady(page);
	const customize = page.getByRole('button', { name: 'Customize the Templates' });
	const templateEditor = page.locator('form:has(input[name="formulize_admin_handler"][value$="_templates"]) .CodeMirror').first();
	await expect(customize.or(templateEditor)).toBeVisible();
	if (await customize.isVisible()) {
		await Promise.all([page.waitForEvent('load'), customize.click()]);
		await page.goto(screenTab(sid, 'templates'));
		await waitForAdminPageReady(page);
	}
	await expect(templateEditor).toBeVisible();
}

// Check that what is in a box is what was typed, save without touching anything, and check again. A save posts every
// tab of the page, the way it does when an admin changes some other setting.
async function expectTypedThroughASecondSave(page, open, boxes) {
	await open();
	for (const [box, typed] of boxes) {
		expect(await editorValue(page, box), `${box} after saving`).toBe(typed);
	}
	await saveAdminForm(page);
	await open();
	for (const [box, typed] of boxes) {
		expect(await editorValue(page, box), `${box} after saving again`).toBe(typed);
	}
}

test('Build a form with typed defaults, static content and screen text', async ({ page }) => {
	test.setTimeout(300000); // a form, three elements, the screen's text and the form's permissions, each its own admin save
	await login(page, 'admin');
	await deleteMuseumFormsTitled(page, [FORM_TITLE]); // left behind by a run that was killed outright
	fid = await createMuseumForm(page, FORM_TITLE);

	for (const element of ELEMENTS) {
		await page.goto(elementsTab());
		await waitForAdminPageReady(page);
		await addElementForm(page, element.type);
		await waitForAdminPageReady(page);
		await page.locator('input[name="elements-ele_caption"]').fill(element.caption);
		await page.locator('input[name="elements-ele_handle"]').fill(element.handle);
		await page.getByRole('link', { name: 'Options' }).click();
		await setCodeMirrorValue(page, `textarea[name="${element.box}"]`, element.typed);
		await saveAdminForm(page);
		await expect(page.getByRole('heading')).toContainText(element.caption);
	}

	// read only, to know which screens the form was given
	for (const [sid, type] of dbQuery(`SELECT sid, type FROM ${dbPrefix()}_formulize_screen WHERE fid = ${fid}`)) {
		if (type === 'multiPage') formSid = parseInt(sid, 10);
		if (type === 'listOfEntries') listSid = parseInt(sid, 10);
	}
	expect(formSid, 'the new form has a form screen').toBeGreaterThan(0);
	expect(listSid, 'the new form has a list screen').toBeGreaterThan(0);

	await openFormScreenText(page);
	await setCodeMirrorValue(page, 'textarea[name="screens-introtext"]', INTRO);
	await setCodeMirrorValue(page, 'textarea[name="screens-thankstext"]', THANKS);
	// a new form screen ends on its last page of questions, so make it end on the thank-you page instead
	await page.locator('a', { hasText: /^Options$/ }).first().click();
	await page.locator('#screens-finishisdone-0').check();
	await saveAdminForm(page);

	// let the end user see the form and make entries in it
	const permissionsTab = `/modules/formulize/admin/ui.php?page=form&fid=${fid}&tab=permissions`;
	await page.goto(permissionsTab);
	await showPermissionsFor(page, [END_USER_GROUP]);
	const group = page.getByRole('group', { name: END_USER_GROUP });
	await group.locator('input[name$="_view_form"]').first().check();
	await group.locator('input[name$="_add_own_entry"]').first().check();
	await saveAdminForm(page);
	await page.goto(permissionsTab);
	await showPermissionsFor(page, [END_USER_GROUP]);
	await expect(group.locator('input[name$="_view_form"]').first()).toBeChecked();
	await expect(group.locator('input[name$="_add_own_entry"]').first()).toBeChecked();
});

test('The admin shows the intro, thanks, defaults and content as typed, after a second save too', async ({ page }) => {
	test.setTimeout(300000); // four pages, each opened, saved and opened again
	await login(page, 'admin');
	await expectTypedThroughASecondSave(page, () => openFormScreenText(page),
		[['screens-introtext', INTRO], ['screens-thankstext', THANKS]]);
	for (const element of ELEMENTS) {
		await expectTypedThroughASecondSave(page, () => openElementOptions(page, element.handle), [[element.box, element.typed]]);
	}
});

test('A regular user sees the intro, defaults and content as written, and the thanks text runs', async ({ browser }) => {
	await asEndUser(browser, async page => {
		await page.goto(`/modules/formulize/index.php?sid=${formSid}`);

		const intro = page.locator('#eb-intro');
		await expect(intro).toBeVisible();
		expect(await intro.innerText()).toBe(INTRO_SEEN);
		await expect(intro.locator('b')).toHaveCount(0);

		await expect(page.getByRole('textbox', { name: 'Plain path' })).toHaveValue(PLAIN_DEFAULT);
		await expect(page.getByRole('textbox', { name: 'Coded default' })).toHaveValue(CODED_DEFAULT_SEEN);
		await expect(page.locator('#eb-content')).toHaveText(CONTENT_SEEN);

		// a one page form reaches its thank-you page through the tab after its page of questions
		await page.locator('div#formulizeform').click();
		await waitForFormulizeFormToken(page);
		await page.getByRole('link', { name: 'Save and Close' }).click();
		await expect(page.locator('#eb-thanks')).toHaveText(THANKS_SEEN, { timeout: 30000 });
		await leavePage(page);
	});
});

test('Templates come back as typed after a second save, a </textarea> in one stays in its box, and a regular user sees what they print', async ({ page, browser }) => {
	test.setTimeout(300000); // two screens, each given its own templates, saved twice and viewed
	await login(page, 'admin');
	for (const [sid, box] of [[listSid, 'screens-toptemplate'], [formSid, 'toptemplate']]) {
		await openScreenTemplates(page, sid);
		await setCodeMirrorValue(page, `textarea[name="${box}"]`, TEMPLATE);
		await saveAdminForm(page);
		// all of it, including what comes after the </textarea>, which used to end the box early
		await expectTypedThroughASecondSave(page, () => openScreenTemplates(page, sid), [[box, TEMPLATE]]);
	}
	await asEndUser(browser, async userPage => {
		for (const sid of [listSid, formSid]) {
			await userPage.goto(`/modules/formulize/index.php?sid=${sid}`);
			await expect(userPage.locator('#eb-template'), `what the template of screen ${sid} prints`).toHaveText(TEMPLATE_SEEN);
			await leavePage(userPage);
		}
	});
});

test('Deleting the form removes its screens\' own template files', async ({ page }) => {
	await login(page, 'admin');
	// both screens have their own templates from the test before, which are files on the server
	for (const sid of [listSid, formSid]) {
		expect(screenTemplateFolders(sid), `screen ${sid} has its own templates`).not.toHaveLength(0);
	}
	await deleteMuseumForm(page, fid);
	for (const sid of [listSid, formSid]) {
		expect(screenTemplateFolders(sid), `the templates of deleted screen ${sid}`).toHaveLength(0);
	}
});
