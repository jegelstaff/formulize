const { test, expect } = require('@playwright/test');
import { login, setMenuEntryGroups } from '../../utils';
import { E2E_TEST_BASE_URL, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';

// What anonymous visitors see in the menu. A menu item is shown to the groups it is given in its Menu
// Entries settings, but the menu itself is a block, and anonymous visitors only get it when Anonymous can
// read that block. Formulize keeps the two in step: Anonymous can read the menu block if, and only if, at
// least one menu item is given to Anonymous (syncAnonymousAccessToFormMenuBlock() in class/applications.php,
// checked directly by tests/menu_permissions_test.php). Here it is checked the way people meet it, by giving
// an existing item to Anonymous Users in the admin UI and taking it away again. Changing an existing item is
// the path that used to leave the menu block out of step.
//
// The museum system gives Anonymous no menu items (setup/015), so anonymous visitors start with no menu.
//
// The tests run in order in one worker: each one depends on the one before it. CI runs the validate suite
// with --workers=4 --fully-parallel, which overrides the serial defaults in playwright.config.js.
test.describe.configure({ mode: 'serial' });

// The groups setup/015 gives every Museum menu item, which the Surveys item goes back to at the end
const museumMenuGroups = ['Webmasters', 'Departments - All Users'];

// Set the Surveys menu item's groups as the webmaster, in a context of its own, so that the page the
// tests look through stays an anonymous visitor's
async function setSurveysMenuGroups(browser, groupLabels) {
	const context = await browser.newContext({ baseURL: E2E_TEST_BASE_URL });
	const page = await context.newPage();
	try {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await setMenuEntryGroups(page, 'Museum', 'Surveys', 'groups4', groupLabels);
	} finally {
		await context.close();
	}
}

// The links in the menu on the home page, where the menu is drawn open, and on the login page
async function expectAnonymousMenuLinks(page, linkNames) {
	for (const url of ['/', '/user.php']) {
		await page.goto(url);
		const links = page.locator('#mainmenu a');
		await expect(links, url).toHaveCount(linkNames.length);
		for (const name of linkNames) {
			await expect(page.locator('#mainmenu').getByRole('link', { name, exact: true }), url).toHaveCount(1);
		}
	}
}

// Whether the Surveys item is still given to Anonymous Users, so a failure part way through can put it back
let surveysGivenToAnonymous = false;

test.describe('Menu items for anonymous visitors', () => {

	test.afterAll(async ({ browser }) => {
		if (surveysGivenToAnonymous) {
			await setSurveysMenuGroups(browser, museumMenuGroups);
			surveysGivenToAnonymous = false;
		}
	});

	test('anonymous visitors see no menu links to start with', async ({ page }) => {
		await expectAnonymousMenuLinks(page, []);
	});

	test('given the Surveys menu item, anonymous visitors see a link to Surveys', async ({ page, browser }) => {
		surveysGivenToAnonymous = true;
		await setSurveysMenuGroups(browser, [...museumMenuGroups, 'Anonymous Users']);
		await expectAnonymousMenuLinks(page, ['Surveys']);
		// On the home page the menu is drawn open, so the link is there to be seen and followed. The item
		// points at the Surveys form rather than one of its screens, and anonymous visitors only reach forms
		// through screens, so this also checks that the form's default screen is what they get.
		await page.goto('/');
		const surveysLink = page.locator('#mainmenu').getByRole('link', { name: 'Surveys', exact: true });
		await expect(surveysLink).toBeVisible();
		await surveysLink.click();
		await expect(page.getByRole('textbox', { name: 'Your name' })).toBeVisible();
	});

	test('with the Surveys menu item taken away again, anonymous visitors see no menu links', async ({ page, browser }) => {
		await setSurveysMenuGroups(browser, museumMenuGroups);
		surveysGivenToAnonymous = false;
		await expectAnonymousMenuLinks(page, []);
	});
});
