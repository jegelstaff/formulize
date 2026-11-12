const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';
import { login, leavePage, dbQuery, dbPrefix, entryIsLocked, entriesThePageWillRelease } from '../../utils';

// The list of users shows a Masquerade button for each user, the same button their account shows in a form. It's
// the column's value in the list, not an element to edit there, so it shows without anything being clicked. A
// webmaster can't masquerade as themselves, so their own row has none. Nothing is clicked, because masquerading
// would change who the session is for. The database is only read, for curator1's uid.
//
// A user's Status is opened for editing from a pen beside it. So looking at the list locks no one, and anyone can open
// their own account meanwhile. Opening one user's Status locks only that user, until it's closed. Whether a user is
// locked is read from the files on the server that hold the locks. Nothing is saved.
//
// The list of users is drawn the way a list screen is, so it has the page width the site gives list screens, the same
// as the Artifacts list, whatever the theme and the site's settings are.

const uidOf = loginName => dbQuery(`SELECT uid FROM ${dbPrefix()}_users WHERE login_name = '${loginName}'`)[0][0];

// The width the theme keeps the page's content to, which a screen's page width sets
const contentMaxWidth = page => page.evaluate(() => getComputedStyle(document.documentElement).getPropertyValue('--formulize-content-max-width').trim());

test('the list of users is as wide as a list screen', async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	const artifacts = dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_handle = 'artifacts'`)[0][0];
	await page.goto(`/modules/formulize/index.php?fid=${artifacts}`);
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
	const listWidth = await contentMaxWidth(page);

	await page.goto('/modules/formulize/users.php');
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
	expect(await contentMaxWidth(page), 'the same page width as the Artifacts list').toBe(listWidth);

	await leavePage(page, '/modules/formulize/application.php?id=all');
	await expect(page).toHaveURL(/\/application\.php\?id=all$/);
});

test('each user in the list of users has a Masquerade button, except yourself', async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await page.goto('/modules/formulize/users.php');
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();

	const rowOf = loginName => page.getByRole('row').filter({ has: page.getByRole('cell', { name: loginName, exact: true }) });

	const curatorsButton = rowOf('curator1').getByRole('link', { name: 'Masquerade', exact: true });
	await expect(curatorsButton).toBeVisible();
	await expect(curatorsButton).toHaveAttribute('href', new RegExp(`/modules/formulize/masquerade\\.php\\?uid=${uidOf('curator1')}&`));

	await expect(rowOf(E2E_TEST_ADMIN_USERNAME)).toHaveCount(1);
	await expect(rowOf(E2E_TEST_ADMIN_USERNAME).getByRole('link', { name: 'Masquerade', exact: true })).toHaveCount(0);

	await leavePage(page, '/modules/formulize/application.php?id=all');
	await expect(page).toHaveURL(/\/application\.php\?id=all$/);
});

test('a user\'s Status opens from its pen, and only then is that user locked', async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await page.goto('/modules/formulize/users.php');
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();

	const rowOf = loginName => page.getByRole('row').filter({ has: page.getByRole('cell', { name: loginName, exact: true }) });
	const status = rowOf('curator1').locator('[id^="deDiv_formulize_user_account_status_"]');
	await expect(status).toHaveCount(1);
	// the System Users form, and the entry, which is the user's uid, are in the cell's id
	const [, fid, entry] = /^deDiv_formulize_user_account_status_(\d+)_(\d+)_\d+$/.exec(await status.getAttribute('id')).map(Number);
	expect(entry).toBe(parseInt(uidOf('curator1'), 10));

	// drawn with its pen and its status in words, not as a form element, so the list has locked no one
	await expect(status.locator('a.de-edit-icon')).toBeVisible();
	await expect(status).toContainText('Active');
	await expect(status.locator('select')).toHaveCount(0);
	expect(await entriesThePageWillRelease(page)).toEqual([]);
	expect(entryIsLocked(fid, entry), 'looking at the list locks no one').toBe(false);

	// opened from the pen, curator1 is locked, and the list's release covers it
	await status.locator('a.de-edit-icon').click();
	await expect(status.locator('select')).toBeVisible();
	await expect.poll(() => entryIsLocked(fid, entry), { message: 'opening Status locks the user' }).toBe(true);
	expect(await entriesThePageWillRelease(page)).toContainEqual([fid, entry]);

	// closed with the X, without saving, and the lock goes
	await status.locator('a.formulize-de-cancel').click();
	await expect(status.locator('select')).toHaveCount(0);
	await expect.poll(() => entryIsLocked(fid, entry), { message: 'closing Status releases the user' }).toBe(false);

	await leavePage(page, '/modules/formulize/application.php?id=all');
	await expect(page).toHaveURL(/\/application\.php\?id=all$/);
});
