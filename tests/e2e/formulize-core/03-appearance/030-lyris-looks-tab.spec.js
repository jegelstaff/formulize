const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import {
	login, dbQuery, dbPrefix, getSiteTheme, setSiteThemeInDb, restoreSiteTheme, openLooksTab, lookRow, lookKey,
	createLook, openLookMenuItem, getSiteLookKey, setSiteLook, deleteLooksNamed, readLookFile,
} from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Lyris appearance (3 of 8) - the Looks tab
// ============================================================
// Admin > Appearance > Looks lists a theme's looks as a tree, each look under the one it is based on,
// and does everything to them except change how one looks: make, duplicate, rename, rebase, set as the
// site look, and delete, with what can't be done refused. Its popovers say what each look changes, and
// what uses it.
//
// The site is on Lyris while this runs (switched in the database, see 020), so that the tab is about
// the site's own theme. The tests build on each other, in order, using looks named e2e-..., which the
// afterAll deletes, after putting back the site look and letting go of the screen it borrows, and then
// it puts the site's theme back.

test.describe.configure({ mode: 'serial' });

let originalTheme = null;
let originalSiteLook = '';
let screen = null; // a screen to set to one of the looks, for Used by
const keys = {};

async function adminPage(browser) {
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	return page;
}

test.beforeAll(async ({ browser }) => {
	originalTheme = getSiteTheme();
	setSiteThemeInDb('Lyris');
	const page = await adminPage(browser);
	originalSiteLook = await getSiteLookKey(page);
	await page.close();
	const rows = dbQuery(`SELECT sid, title FROM ${dbPrefix()}_formulize_screen WHERE look = '' ORDER BY sid LIMIT 1`);
	screen = { sid: parseInt(rows[0][0], 10), title: rows[0][1] };
});

test.afterAll(async ({ browser }) => {
	try {
		dbQuery(`UPDATE ${dbPrefix()}_formulize_screen SET look = '' WHERE sid = ${screen.sid}`);
		const page = await adminPage(browser);
		await setSiteLook(page, originalSiteLook);
		await deleteLooksNamed(page, 'e2e-');
		await page.close();
	} finally {
		restoreSiteTheme(originalTheme);
	}
});

test.beforeEach(async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
});

// The cells of a look's row: Look, Based on, Changes, Used by
function cell(page, name, column) {
	return lookRow(page, name).locator('td').nth({ look: 0, base: 1, changes: 2, used: 3 }[column]);
}

// What a look changes, in its row: a button that opens a popover with the details
function changesOf(page, name) {
	return cell(page, name, 'changes').locator('.formulize-looks__changes-btn');
}

// What uses a look, in its row: Not used, or a button that opens a popover with the details
function usedBy(page, name) {
	return cell(page, name, 'used').locator(':scope > .fz-text-subtle, :scope > .formulize-looks__more');
}

// The names of the looks, in the order of the tree, with how deep each is in it
async function tree(page) {
	return page.locator('table.fz-tree tbody tr').evaluateAll(rows => rows.map(row => ({
		name: row.querySelector('.formulize-looks__name strong').textContent,
		depth: parseInt(row.querySelector('.fz-tree__cell').style.getPropertyValue('--fz-depth'), 10) || 0,
	})));
}

test.describe('The Looks tab', () => {

	test('Create new look adds the look under the look it is based on, with the size chosen', async ({ page }) => {
		keys.parent = await createLook(page, 'e2e-parent', { base: '' });
		await expect(cell(page, 'e2e-parent', 'base')).toHaveText('No base look');
		await expect(changesOf(page, 'e2e-parent')).toHaveText('No changes');
		await expect(usedBy(page, 'e2e-parent')).toHaveText('Not used');

		// Create look based on this: the same dialog, with this look as the base
		await openLooksTab(page);
		const dialog = await openLookMenuItem(page, 'e2e-parent', 'Create look based on this');
		await expect(dialog).toHaveAttribute('id', 'looks-create');
		await expect(dialog.locator('#looks-create-base')).toHaveValue(keys.parent);
		await dialog.locator('#looks-create-name').fill('e2e-child');
		await dialog.locator('input[name="size"][value="compact"]').check();
		await dialog.getByRole('button', { name: 'Create look', exact: true }).click();
		await expect(page.locator('.fz-callout--success')).toHaveText('Created e2e-child.');
		keys.child = await lookKey(page, 'e2e-child');

		const rows = await tree(page);
		const at = rows.findIndex(row => row.name === 'e2e-parent');
		expect(rows[at + 1]).toEqual({ name: 'e2e-child', depth: rows[at].depth + 1 });
		await expect(cell(page, 'e2e-child', 'base')).toHaveText('e2e-parent');
		await expect(changesOf(page, 'e2e-child')).toHaveText(/Compact/);

		const child = await readLookFile(page, keys.child);
		expect(child.record.name).toBe('e2e-child');
		expect(child.record.base).toBe(keys.parent);
		expect(JSON.parse(child.record.appearance_overrides)).toEqual(expect.objectContaining({ '--fz-control-height': expect.any(Number) }));
	});

	test('Duplicate makes a look with the same base and changes, without the looks based on it', async ({ page }) => {
		await openLooksTab(page);
		let dialog = await openLookMenuItem(page, 'e2e-child', 'Duplicate');
		await expect(dialog.locator('input[name="name"]')).toHaveValue('e2e-child copy');
		await dialog.locator('input[name="name"]').fill('e2e-child-dup');
		await dialog.getByRole('button', { name: 'Duplicate', exact: true }).click();
		await expect(page.locator('.fz-callout--success')).toHaveText('Created e2e-child-dup, a copy.');
		keys.childDup = await lookKey(page, 'e2e-child-dup');
		const [child, copy] = [await readLookFile(page, keys.child), await readLookFile(page, keys.childDup)];
		expect(copy.record.base).toBe(keys.parent);
		expect(copy.record.appearance_overrides).toBe(child.record.appearance_overrides);
		await expect(cell(page, 'e2e-child-dup', 'base')).toHaveText('e2e-parent');

		dialog = await openLookMenuItem(page, 'e2e-parent', 'Duplicate');
		await expect(dialog).toContainText("The looks based on e2e-parent aren't copied.");
		await dialog.locator('input[name="name"]').fill('e2e-parent-dup');
		await dialog.getByRole('button', { name: 'Duplicate', exact: true }).click();
		await expect(page.locator('.fz-callout--success')).toHaveText('Created e2e-parent-dup, a copy.');
		const rows = await tree(page);
		const at = rows.findIndex(row => row.name === 'e2e-parent-dup');
		expect(rows[at + 1] === undefined || rows[at + 1].depth <= rows[at].depth).toBeTruthy();
		expect(rows.filter(row => row.name.startsWith('e2e-child'))).toHaveLength(2);
	});

	test('Rename changes the name and keeps the key, which screens and other looks know it by', async ({ page }) => {
		await openLooksTab(page);
		const dialog = await openLookMenuItem(page, 'e2e-child-dup', 'Rename');
		await dialog.locator('input[name="name"]').fill('e2e-renamed');
		await dialog.getByRole('button', { name: 'Rename', exact: true }).click();
		await expect(page.locator('.fz-callout--success')).toHaveText('Renamed it e2e-renamed.');
		await expect(lookRow(page, 'e2e-child-dup')).toHaveCount(0);
		expect(await lookKey(page, 'e2e-renamed')).toBe(keys.childDup);
		expect((await readLookFile(page, keys.childDup)).record.name).toBe('e2e-renamed');
	});

	test('Change base refuses the look itself and the looks based on it, and moves a look with its own changes', async ({ page }) => {
		await openLooksTab(page);
		let dialog = await openLookMenuItem(page, 'e2e-parent', 'Change base');
		const base = dialog.locator('select[name="base"]');
		await expect(base.locator(`option[value="${keys.parent}"]`)).toBeDisabled();
		await expect(base.locator(`option[value="${keys.parent}"]`)).toContainText('(this look)');
		await expect(base.locator(`option[value="${keys.child}"]`)).toBeDisabled();
		await expect(base.locator(`option[value="${keys.child}"]`)).toContainText('(based on this look)');
		// and the server refuses it too, for a form sent anyway
		await base.locator(`option[value="${keys.child}"]`).evaluate(option => { option.disabled = false; });
		await base.selectOption(keys.child);
		await dialog.getByRole('button', { name: 'Change base', exact: true }).click();
		await expect(page.locator('.fz-callout--danger')).toHaveText("e2e-parent can't be based on e2e-child, which is based on it.");
		expect((await readLookFile(page, keys.parent)).record.base).toBeUndefined();

		dialog = await openLookMenuItem(page, 'e2e-child', 'Change base');
		await dialog.locator('select[name="base"]').selectOption('');
		await dialog.getByRole('button', { name: 'Change base', exact: true }).click();
		await expect(page.locator('.fz-callout--success')).toHaveText('Changed what e2e-child is based on.');
		await expect(cell(page, 'e2e-child', 'base')).toHaveText('No base look');
		const child = await readLookFile(page, keys.child);
		expect(child.record.base).toBeUndefined();
		// it keeps its own changes
		expect(child.record.appearance_overrides).toBeTruthy();
		await expect(changesOf(page, 'e2e-child')).toHaveText(/Compact/);
	});

	test('The Changes and Used by popovers say what a look changes, and what uses it', async ({ page }) => {
		dbQuery(`UPDATE ${dbPrefix()}_formulize_screen SET look = '${keys.childDup}' WHERE sid = ${screen.sid}`);
		await openLooksTab(page);

		await changesOf(page, 'e2e-parent').click();
		let popover = page.locator(`#looks-${keys.parent}-base`);
		await expect(popover).toBeVisible();
		await expect(popover).toContainText('Nothing yet: it looks the same as the Lyris theme.');
		await page.keyboard.press('Escape');
		await expect(popover).toBeHidden();

		await changesOf(page, 'e2e-child').click();
		popover = page.locator(`#looks-${keys.child}-base`);
		await expect(popover.locator('dl')).toContainText('Size');
		await expect(popover.locator('dl')).toContainText('Compact');
		await expect(popover).toContainText('Everything else comes from the Lyris theme.');
		await page.keyboard.press('Escape');

		await changesOf(page, 'e2e-renamed').click();
		popover = page.locator(`#looks-${keys.childDup}-base`);
		await expect(popover.locator('.formulize-looks__chain')).toHaveText('e2e-parent');
		await expect(popover).toContainText('Everything else comes from e2e-parent.');
		await page.keyboard.press('Escape');

		// set to it directly, and through a look based on it
		await expect(usedBy(page, 'e2e-renamed')).toHaveText('1 screen');
		await usedBy(page, 'e2e-renamed').click();
		popover = page.locator(`#looks-${keys.childDup}-used`);
		await expect(popover).toContainText('Screens set to it');
		const link = popover.getByRole('link', { name: screen.title, exact: true });
		await expect(link).toHaveAttribute('href', new RegExp(`sid=${screen.sid}&tab=appearance`));
		await page.keyboard.press('Escape');

		await expect(usedBy(page, 'e2e-parent')).toHaveText(/No screens\s*\+1 indirectly/);
		await usedBy(page, 'e2e-parent').click();
		popover = page.locator(`#looks-${keys.parent}-used`);
		await expect(popover).toContainText('Through looks based on it');
		await expect(popover).toContainText('via e2e-renamed');
		await expect(popover).toContainText('A change to e2e-parent reaches these too');
		await page.keyboard.press('Escape');
	});

	test('Set as site look makes a look the site look, which then cannot be deleted', async ({ page }) => {
		await openLooksTab(page);
		let dialog = await openLookMenuItem(page, 'e2e-parent', 'Set as site look');
		await dialog.getByRole('button', { name: 'Set as site look', exact: true }).click();
		await expect(page.locator('.fz-callout--success')).toHaveText('e2e-parent is now the site look.');
		await expect(lookRow(page, 'e2e-parent')).toHaveClass(/formulize-looks__site/);
		await expect(lookRow(page, 'e2e-parent').locator('.fz-badge')).toHaveText('Site look');
		await expect(page.locator('.formulize-looks__context')).toContainText('Site look: e2e-parent');
		await expect(usedBy(page, 'e2e-parent')).toHaveText(/^The site/);
		expect(await getSiteLookKey(page)).toBe(keys.parent);

		dialog = await openLookMenuItem(page, 'e2e-parent', 'Delete');
		await expect(dialog).toContainText("e2e-parent can't be deleted");
		await expect(dialog).toContainText('e2e-parent is the site look. Change the site look first, then delete it.');
		await expect(dialog.getByRole('button', { name: 'Delete look' })).toHaveCount(0);
		await dialog.getByRole('button', { name: 'Close', exact: true }).click();
		await expect(dialog).toBeHidden();

		await setSiteLook(page, originalSiteLook);
		await expect(lookRow(page, 'e2e-parent')).not.toHaveClass(/formulize-looks__site/);
	});

	test('A look others are based on cannot be deleted until they are not; a look can be deleted then', async ({ page }) => {
		await openLooksTab(page);
		let dialog = await openLookMenuItem(page, 'e2e-parent', 'Delete');
		await expect(dialog).toContainText("e2e-parent can't be deleted yet");
		await expect(dialog.locator('li')).toHaveText(['e2e-renamed']);
		await expect(dialog.getByRole('button', { name: 'Delete look' })).toHaveCount(0);
		await dialog.getByRole('button', { name: 'Close', exact: true }).click();

		// a screen set to the look it deletes goes back to the site look
		dialog = await openLookMenuItem(page, 'e2e-renamed', 'Delete');
		await expect(dialog).toContainText('These screens use e2e-renamed, and will use the site look');
		await expect(dialog.locator('li')).toHaveText([screen.title]);
		await dialog.getByRole('button', { name: 'Delete look', exact: true }).click();
		await expect(page.locator('.fz-callout--success')).toHaveText('Deleted e2e-renamed.');
		await expect(lookRow(page, 'e2e-renamed')).toHaveCount(0);
		expect(await readLookFile(page, keys.childDup)).toBeNull();
		expect(dbQuery(`SELECT COUNT(*) FROM ${dbPrefix()}_formulize_screen WHERE sid = ${screen.sid} AND look = ''`)[0][0]).toBe('1');

		dialog = await openLookMenuItem(page, 'e2e-parent', 'Delete');
		await expect(dialog).toContainText('Nothing uses e2e-parent.');
		await dialog.getByRole('button', { name: 'Delete look', exact: true }).click();
		await expect(page.locator('.fz-callout--success')).toHaveText('Deleted e2e-parent.');
		expect(await readLookFile(page, keys.parent)).toBeNull();
	});
});
