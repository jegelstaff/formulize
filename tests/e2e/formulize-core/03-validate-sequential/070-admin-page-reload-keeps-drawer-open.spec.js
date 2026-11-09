const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, waitForAdminPageReady, saveAdminForm, dbQuery, dbPrefix } from '../../utils';

// When an admin page reloads after a save, it goes back to the drawer that was open. This uses the pages of the Artifact form
// screen, which 022 made. It adds a page and deletes it again, so the screen is left as it was, and that's why it's here and
// not in 02-validate.

// tall enough that the headings stay on the screen with a drawer open, because the mouse can't drag to something off the screen
test.use({ baseURL: E2E_TEST_BASE_URL, viewport: { width: 1280, height: 1200 } });

// By the title, which is a link. Chromium would start its own drag of it if the link allowed that, and the sortable would stop
// getting mouse events. The sortable only notices a drag that moves in steps.
async function dragDrawer(page, drawer, target) {
	const start = await drawer.locator('h3 a').boundingBox();
	const end = await target.locator('h3').boundingBox();
	const x = start.x + start.width / 2;
	await page.mouse.move(x, start.y + start.height / 2);
	await page.mouse.down();
	await page.mouse.move(x, end.y + (end.y > start.y ? end.height + 4 : -4), { steps: 15 });
	await page.mouse.up();
}

test('An admin page reloads with the same drawer open, or the first one if that drawer was deleted', async ({ page }) => {
	const [sid, fid] = dbQuery(`SELECT sid, fid FROM ${dbPrefix()}_formulize_screen WHERE title = 'Artifact' AND type = 'multiPage'`)[0];
	const aid = dbQuery(`SELECT appid FROM ${dbPrefix()}_formulize_application_form_link WHERE fid = ${fid}`)[0][0];
	const heading = title => page.getByRole('link', { name: title, exact: true });
	const drawer = title => page.locator('[id^="drawer-"]').filter({ has: heading(title) });
	const editLink = title => drawer(title).getByRole('link', { name: 'Edit this page' });
	const pageNames = page.locator('#sortable-list .accordion-name');

	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await page.goto(`/modules/formulize/admin/ui.php?page=screen&aid=${aid}&fid=${fid}&sid=${sid}&tab=pages`);
	await waitForAdminPageReady(page);
	await expect(editLink('Artifact')).toBeVisible();

	// 022 can give the screen more pages after these three. The drags only move Appraisal among these, so the rest stay put.
	const startingOrder = await pageNames.allTextContents();
	expect(startingOrder.slice(0, 3)).toEqual(['Artifact', 'Location and loans', 'Appraisal']);
	const appraisalFirst = ['Appraisal', ...startingOrder.filter(name => name !== 'Appraisal')];

	await heading('Appraisal').click();
	await expect(editLink('Appraisal')).toBeVisible();
	await expect(editLink('Artifact')).toBeHidden();

	// creating a page saves and reloads, and the new page's drawer is only on the page after that
	await page.getByRole('link', { name: 'Create a new page' }).click();
	await expect(heading('New page')).toBeVisible();
	await expect(editLink('Appraisal')).toBeVisible();
	await expect(editLink('Artifact')).toBeHidden();

	await heading('New page').click();
	await expect(editLink('New page')).toBeVisible();
	await expect(editLink('Appraisal')).toBeHidden();

	// the page being deleted is the last one, so the position of its drawer doesn't exist after the reload
	page.once('dialog', dialog => dialog.accept());
	await drawer('New page').getByRole('link', { name: 'Delete this page' }).click();
	await expect(heading('New page')).toHaveCount(0);
	await expect(editLink('Artifact')).toBeVisible();
	await expect(editLink('Appraisal')).toBeHidden();

	// moving the open page saves and reloads, and it stays open in its new position
	await heading('Appraisal').click();
	await expect(editLink('Appraisal')).toBeVisible();
	await expect(editLink('Artifact')).toBeHidden();
	await dragDrawer(page, drawer('Appraisal'), drawer('Artifact'));
	await saveAdminForm(page);
	await expect(pageNames).toHaveText(appraisalFirst);
	await expect(editLink('Appraisal')).toBeVisible();
	await expect(editLink('Location and loans')).toBeHidden();

	// the first click after a drag still works
	await dragDrawer(page, drawer('Appraisal'), drawer('Location and loans'));
	await heading('Artifact').click();
	await expect(editLink('Artifact')).toBeVisible();
	await heading('Appraisal').click();
	await expect(editLink('Appraisal')).toBeVisible();

	// and it's now back where it started, so saving leaves the screen as it was
	await saveAdminForm(page);
	await expect(pageNames).toHaveText(startingOrder);
	await expect(editLink('Appraisal')).toBeVisible();
});
