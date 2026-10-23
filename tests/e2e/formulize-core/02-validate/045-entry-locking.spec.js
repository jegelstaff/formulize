const { test, expect } = require('@playwright/test');
import { login, ensureMainMenuOpen, leavePage } from '../../utils';
import { E2E_TEST_BASE_URL, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';

// Entry locking: while someone has an entry open in a form, anyone else who opens it gets a message
// saying who has it, and sees the values as text instead of form elements, until the first person
// leaves the page. Webmasters can ignore the lock (the ignore_editing_lock permission, which only
// Webmasters hold), and doing so takes the lock over.
//
// Uses the curators and the Roman Coin artifact from the setup specs. Both curators can edit
// other people's artifacts, so nothing but the lock stands in the way of the second one. No other
// validate spec opens an artifact in a form, so the lock can't get in the way of the specs running
// alongside this one. Nothing here saves the entry, since 025 checks who last modified it.
//
// Every test locks the same entry, so they must not run at the same time. CI runs the validate
// suite with --workers=4 --fully-parallel, which overrides the serial defaults in playwright.config.js.
test.describe.configure({ mode: 'serial' });

async function signIn(browser, username, password = '12345') {
	const context = await browser.newContext({ baseURL: E2E_TEST_BASE_URL });
	const page = await context.newPage();
	await login(page, username, password);
	return page;
}

async function openRomanCoin(page) {
	await ensureMainMenuOpen(page);
	await page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true }).click();
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
	await page.getByRole('row', { name: 'M001' }).getByRole('link').first().click();
	await expect(page.locator('.formulize-label-artifacts_short_name')).toBeVisible();
	// the lock message is added when the document is ready
	await page.waitForLoadState('load');
}

// Leaving the page releases any lock this page took. The page sends that request as it goes away,
// and it has been handled by the time the next page has loaded.
async function leave(page) {
	await leavePage(page);
	await expect(page.locator('.formulize-label-artifacts_short_name')).not.toBeVisible();
}

const lockMessage = page => page.locator('.formulize-entry-lock-message');
const shortNameRow = page => page.locator('#formulizeform .form-row:has(.formulize-label-artifacts_short_name)');
const shortNameBox = page => shortNameRow(page).getByRole('textbox');
// Fields for the entry's values. Not the Save and Close buttons, which are still there, or the
// Change entry owner list, which is hidden in the Office Use Only section and isn't one of the form's elements.
const editableFields = page => page.locator('#formulizeform').locator(
	'input:not([type="hidden"]):not([type="button"]):not([type="submit"]):not([disabled]), textarea:not([disabled]), select:not([disabled]), [contenteditable="true"]'
).filter({ visible: true });

async function expectEditable(page) {
	await expect(shortNameBox(page)).toHaveValue('Roman Coin');
	await expect(lockMessage(page)).toHaveCount(0);
}

async function expectLockedBy(page, name) {
	await expect(lockMessage(page)).toBeVisible();
	await expect(lockMessage(page)).toHaveCount(1);
	await expect(lockMessage(page)).toContainText(`has been opened by '${name}'`);
	// the values are there as text, with nothing to edit them in
	await expect(shortNameRow(page)).toContainText('Roman Coin');
	await expect(shortNameBox(page)).toHaveCount(0);
	await expect(editableFields(page)).toHaveCount(0);
}

test.describe('Entry locking', () => {

	let pages = [];

	// leave whatever is still open, so a failure here doesn't leave Roman Coin locked
	test.afterEach(async () => {
		for (const page of pages) {
			await leavePage(page).catch(() => {});
			await page.context().close();
		}
		pages = [];
	});

	test('a curator who opens an entry another curator has open sees who has it, and cannot edit it', async ({ browser }) => {
		const curator1 = await signIn(browser, 'curator1');
		const curator2 = await signIn(browser, 'curator2');
		pages.push(curator1, curator2);

		await openRomanCoin(curator1);
		await expectEditable(curator1); // your own lock doesn't stop you

		await openRomanCoin(curator2);
		await expectLockedBy(curator2, 'Curator One');
		// directly above the form, since the entry isn't in a drawer
		await expect(curator2.locator('.formulize-entry-lock-message + #formulizeform')).toHaveCount(1);
	});

	test('when the curator who has the entry leaves, the other curator can edit it', async ({ browser }) => {
		const curator1 = await signIn(browser, 'curator1');
		const curator2 = await signIn(browser, 'curator2');
		pages.push(curator1, curator2);

		await openRomanCoin(curator1);
		await openRomanCoin(curator2);
		await expectLockedBy(curator2, 'Curator One');

		// opening a locked entry doesn't take the lock, so leaving it doesn't release it
		await leave(curator2);
		await openRomanCoin(curator2);
		await expectLockedBy(curator2, 'Curator One');

		await leave(curator1);
		await openRomanCoin(curator2);
		await expectEditable(curator2);
	});

	test('a webmaster can edit a locked entry, and takes the lock over from the curator', async ({ browser }) => {
		const curator1 = await signIn(browser, 'curator1');
		const admin = await signIn(browser, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		const curator2 = await signIn(browser, 'curator2');
		pages.push(curator1, admin, curator2);

		await openRomanCoin(curator1);
		await openRomanCoin(admin);
		await expectEditable(admin);

		// the curator leaving doesn't release a lock that isn't theirs any more...
		await leave(curator1);
		await openRomanCoin(curator2);
		await expectLockedBy(curator2, E2E_TEST_ADMIN_USERNAME);

		// ...but the webmaster leaving does
		await leave(admin);
		await leave(curator2);
		await openRomanCoin(curator2);
		await expectEditable(curator2);
	});
});
