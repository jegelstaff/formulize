const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';
import { login, clearEntryLocks } from '../../utils';

// Companion to the "Users and Groups" menu-disclosure test in
// setup/005-create-users-and-groups.spec.js. Covers the same CSS-only
// <details>/<summary> disclosure behaviour (see menu.html and
// drawMenuSection() in mymenu.php), but for the "Museum" application
// section instead of the Users/Groups section.
//
// Only meaningful for a webmaster. A webmaster sees two top-level menu
// entries -- the "Staff Management" app created in setup/005, plus "Museum"
// -- so Museum's expand/collapse state is driven by which page is active
// rather than being force-expanded (drawMenuSection()'s $forceOpen only
// kicks in when there's a single top-level application). A regular museum
// user (ahstaff etc.) has permission to see only the Museum app, so it's
// their sole top-level entry and is always force-expanded -- there's
// nothing to toggle for them, which is why this test specifically logs in
// as the webmaster.
test.describe('Museum menu section disclosure (webmaster)', () => {
	test('Museum menu section is closed on users.php, expands on click without navigating, then auto-expands on its own page', async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await page.goto('/modules/formulize/users.php');
		await expect(page.locator('#formulize_addButton')).toBeVisible();
		await clearEntryLocks(page);
		await page.locator('#burger-and-logo').getByRole('link').first().click();

		const artifactsLink = page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true });

		// On users.php, Museum isn't the active section, so it starts collapsed.
		await expect(artifactsLink).not.toBeVisible();

		// Clicking the section header expands it instantly, with no page reload.
		const urlBeforeExpand = page.url();
		await page.locator('#mainmenu').getByText('Museum', { exact: true }).click();
		await expect(artifactsLink).toBeVisible();
		expect(page.url()).toBe(urlBeforeExpand);

		// Clicking a sub-link navigates normally.
		await artifactsLink.click();
		await expect(page).not.toHaveURL(/users\.php/);
		await expect(page.getByRole('button', { name: 'Add Artifact', exact: true })).toBeVisible();

		// Now that we're viewing a Museum screen, the section should already be
		// expanded on load, with no click needed to reveal the sub-links again.
		await page.locator('#burger-and-logo').getByRole('link').first().click();
		await expect(page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true })).toBeVisible();

		await page.goto('/modules/formulize/users.php');
		await clearEntryLocks(page);
	});
});
