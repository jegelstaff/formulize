const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';
import { login, leavePage, dbQuery, dbPrefix } from '../../utils';

// The list of users shows a Masquerade button for each user, the same button their account shows in a form. It's
// the column's value in the list, not an element to edit there, so it shows without anything being clicked. A
// webmaster can't masquerade as themselves, so their own row has none. Nothing is clicked, because masquerading
// would change who the session is for. The database is only read, for curator1's uid.

const uidOf = loginName => dbQuery(`SELECT uid FROM ${dbPrefix()}_users WHERE login_name = '${loginName}'`)[0][0];

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

	// the list locks the users it shows, in its Status column, until it's left
	await leavePage(page, '/modules/formulize/application.php?id=all');
	await expect(page).toHaveURL(/\/application\.php\?id=all$/);
});
