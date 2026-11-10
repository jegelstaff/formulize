const { test, expect } = require('@playwright/test');
import { login, leavePage, dbQuery, dbPrefix } from '../../utils';
import { formId, museumAppId } from '../01-setup/museum-forms';

// Someone who follows a link to a page they can't see until they log in is sent to the login page, and after
// logging in they arrive at the page the link was for, not somewhere near it.
//
//  - A clean URL to an entry keeps the entry. Formulize takes the entry out of the address it draws the page
//    with when the visitor may not see that entry, so the login page must be given the address that was asked for.
//  - Someone not logged in who may see a screen, but not the entry a link asks for, is asked to log in, rather
//    than shown the screen with no entry in it. Unless the screen takes a passcode, because the passcode is
//    what lets them see the entry. And an entry that isn't there just leaves them on the screen.
//  - An admin page keeps its address, query string and all. The core checks access to admin pages before
//    Formulize runs, and used to send the visitor to the login page with nowhere to go back to.
//  - Someone already logged in who may not use the admin pages is told so, and sent to their applications,
//    rather than round in circles.
//
// Anonymous Users may add surveys, but not see them, and the Surveys list takes a passcode while the Survey
// form screen doesn't. The entries used here are ones the other validate specs only read in lists, so no
// other spec holds a lock on them.

const ARTIFACT = 'French Musket';
const SURVEY_COMMENT = 'Can we do better? -c2';

let artifactsFid = 0;
let artifactsAid = 0;
let artifactsListSid = 0;
let artifactsFormSid = 0;
let artifactId = 0;
let surveyFormSid = 0;
let surveyListSid = 0;
let surveyId = 0;

test.beforeAll(() => {
	artifactsFid = formId('Artifacts');
	artifactsAid = museumAppId(artifactsFid);
	[artifactsFormSid, artifactsListSid] = dbQuery(`SELECT defaultform, defaultlist FROM ${dbPrefix()}_formulize_id WHERE id_form = ${artifactsFid}`)[0];
	artifactId = dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_artifacts WHERE artifacts_short_name = '${ARTIFACT}'`)[0][0];
	[surveyFormSid, surveyListSid] = dbQuery(`SELECT defaultform, defaultlist FROM ${dbPrefix()}_formulize_id WHERE id_form = ${formId('Surveys')}`)[0];
	surveyId = dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_surveys WHERE surveys_staff_comments LIKE 'Can we do better?%'`)[0][0];
});

function adminScreenPath() {
	return `/modules/formulize/admin/ui.php?page=screen&sid=${artifactsFormSid}&fid=${artifactsFid}&aid=${artifactsAid}`;
}

// Log in on the login page a link sent us to, and wait to arrive wherever the login sends us
async function logInWhereSent(page, username, password = '12345') {
	await expect(page).toHaveURL(/\/user\.php\?xoops_redirect=/);
	await page.locator('input[name="uname"]').fill(username);
	await page.locator('input[name="pass"]').fill(password);
	await Promise.all([
		page.waitForURL(url => !url.pathname.endsWith('/user.php')),
		page.locator('input[name="pass"]').press('Enter'),
	]);
}

test.describe('Logging in returns to the address that was asked for', () => {

	test('a clean URL to an entry on a form no one may see without logging in shows that entry after logging in', async ({ page }) => {
		await page.goto(`/${artifactsListSid}/${artifactId}/`);
		await logInWhereSent(page, 'curator1');
		await expect(page.getByRole('textbox', { name: 'Short name *' })).toHaveValue(ARTIFACT);
		expect(new URL(page.url()).pathname.startsWith(`/${artifactsListSid}/${artifactId}/`)).toBe(true);
		await leavePage(page);
	});

	test('a clean URL to an entry on a screen anyone may use asks for a login, and shows the entry after it', async ({ page }) => {
		await page.goto(`/${surveyFormSid}/${surveyId}/`);
		await logInWhereSent(page, 'curator1');
		await expect(page.locator('.form-row:has(.formulize-label-surveys_staff_comments) textarea')).toHaveValue(new RegExp(SURVEY_COMMENT.replace('?', '\\?')));
		await leavePage(page);
	});

	test('an index.php address to an entry on a screen anyone may use asks for a login too', async ({ page }) => {
		await page.goto(`/modules/formulize/index.php?sid=${surveyFormSid}&ve=${surveyId}`);
		await logInWhereSent(page, 'curator1');
		await expect(page.locator('.form-row:has(.formulize-label-surveys_staff_comments) textarea')).toHaveValue(new RegExp(SURVEY_COMMENT.replace('?', '\\?')));
		await leavePage(page);
	});

	test('an entry on a screen that takes a passcode asks for the passcode, not a login', async ({ page }) => {
		await page.goto(`/${surveyListSid}/${surveyId}/`);
		await expect(page.locator('input[name="passcode"]')).toBeVisible();
		await expect(page).not.toHaveURL(/\/user\.php/);
	});

	test('an entry that is not there leaves the visitor on the screen', async ({ page }) => {
		await page.goto(`/${surveyFormSid}/999999/`);
		await expect(page.locator('.formulize-label-surveys_rating')).toBeVisible();
		await expect(page).not.toHaveURL(/\/user\.php/);
	});

	test('an admin page is shown after logging in', async ({ page }) => {
		await page.goto(adminScreenPath());
		await logInWhereSent(page, 'admin', 'password');
		const url = new URL(page.url());
		expect(url.pathname + url.search).toBe(adminScreenPath());
		await expect(page.locator('input[name="screens-title"]')).toBeVisible();
	});

	test('someone logged in who may not use the admin pages is told so, and sent to their applications', async ({ page }) => {
		await login(page, 'ahstaff');
		await page.goto(adminScreenPath(), { waitUntil: 'commit' });
		await page.waitForURL(/\/modules\/formulize\/application\.php\?id=all/);
		await expect(page.getByText('Sorry, you do not have permission to view this page. Please contact your site administrator.')).toBeVisible();
	});

});
