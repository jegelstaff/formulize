const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login, leavePage, openAllColumnSections, applyColumnChanges, dbQuery, dbPrefix } from '../../utils';

// Links to a user's account go through userinfo.php, wherever they come from. Your own account opens in edituser.php. For
// someone else's, the permissions decide whether you get nothing, a disabled form or a working one.
//
// This changes what the All Curators group can do with the Staff form, through MCP as 045 does, and puts it back afterwards.
// That's why it's here and not in 02-validate. ahstaff has an entry in Staff, and isn't in All Curators.

test.use({ baseURL: E2E_TEST_BASE_URL });

test.describe.configure({ mode: 'serial' });

const MCP = '/mcp/index.php/mcp';
const NO_PERMISSION = "Sorry, you don't have permission to access this area.";
const NO_USER = 'No User Selected! Please go back and try again.';
// on for every group, or the thing that grants access, so not abilities that can be set
const NOT_ABILITIES = ['view_form', 'view_their_own_entries', 'manage_own'];

let api;
let apiKey;
let staffFid;
let curatorsGroup;
let curatorAbilitiesToStart;

const uidOf = loginName => dbQuery(`SELECT uid FROM ${dbPrefix()}_users WHERE login_name = '${loginName}'`)[0][0];

function curatorAbilities() {
	return dbQuery(`SELECT gperm_name FROM ${dbPrefix()}_group_permission WHERE gperm_groupid = ${curatorsGroup} AND gperm_itemid = ${staffFid}
		AND gperm_modid = (SELECT mid FROM ${dbPrefix()}_modules WHERE dirname = 'formulize')`).map(row => row[0]).sort();
}

async function setCuratorAbilities(abilities) {
	const response = await api.post(MCP, {
		headers: { 'Authorization': `Bearer ${apiKey}` },
		data: { jsonrpc: '2.0', id: 1, method: 'tools/call', params: { name: 'set_form_permissions', arguments: {
			form_id: parseInt(staffFid, 10),
			groups: [{ group_id: parseInt(curatorsGroup, 10), grants_access: true, abilities }],
		} } },
	});
	const body = await response.json();
	expect(body.error, 'set_form_permissions should succeed').toBeUndefined();
}

const accountForm = page => page.locator('.formulizeThemeForm');
const saveControl = page => page.getByRole('link', { name: /^Save/ }).or(page.getByRole('button', { name: /^Save/ }));
const editableFields = page => accountForm(page).locator('input:not([type="hidden"], [type="button"], [type="submit"], [disabled]), textarea:not([disabled]), select:not([disabled])');

// A page showing an account locks it until the page is left, and users.php locks every user in its list. So each test
// leaves, or the next one finds the account drawn read only. This page shows no accounts.
async function leave(page) {
	await leavePage(page, '/modules/formulize/application.php?id=all');
	await expect(page).toHaveURL(/\/application\.php\?id=all$/);
}

test.beforeAll(async ({ browser, playwright }) => {
	api = await playwright.request.newContext({ baseURL: E2E_TEST_BASE_URL });
	const page = await browser.newPage({ baseURL: E2E_TEST_BASE_URL });
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	// masked on screen, and only available in the data-key attribute of its copy icon
	await page.goto('/modules/formulize/admin/ui.php?page=users&view=apikeys');
	await page.locator('.admin-ui').waitFor({ state: 'visible' });
	apiKey = await page.locator('td[id=key-1] .key-copy').getAttribute('data-key');
	await page.close();
	staffFid = dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_handle = 'staff'`)[0][0];
	curatorsGroup = dbQuery(`SELECT groupid FROM ${dbPrefix()}_groups WHERE name = 'All Curators'`)[0][0];
	curatorAbilitiesToStart = curatorAbilities().filter(name => !NOT_ABILITIES.includes(name));
});

test.afterAll(async () => {
	await setCuratorAbilities(curatorAbilitiesToStart);
	await api.dispose();
});

test('Your own account opens in Edit Account', async ({ page }) => {
	await login(page, 'curator1', '12345');
	await page.goto(`/userinfo.php?uid=${uidOf('curator1')}`);
	await expect(page).toHaveURL(/\/edituser\.php$/);
	await expect(editableFields(page)).not.toHaveCount(0);

	await leavePage(page, '/user.php'); // already logged in, so this goes to your account too
	await expect(page).toHaveURL(/\/edituser\.php$/);
	await expect(editableFields(page)).not.toHaveCount(0);
	await leave(page);
});

test('Someone else\'s account shows nothing without permission to see it', async ({ page }) => {
	await login(page, 'curator1', '12345');
	await page.goto(`/userinfo.php?uid=${uidOf('ahstaff')}`);
	await expect(page.getByText(NO_PERMISSION)).toBeVisible();
	await expect(accountForm(page)).toHaveCount(0);

	// nor can the System Users form be reached another way, for someone with no entry in a form that makes users
	await page.goto(`/userinfo.php?uid=${uidOf('admin')}`);
	await expect(page.getByText(NO_PERMISSION)).toBeVisible();
	await expect(accountForm(page)).toHaveCount(0);
});

test('Permission to see someone\'s entry gets a disabled form', async ({ page }) => {
	await setCuratorAbilities([...curatorAbilitiesToStart, 'view_globalscope']);
	await login(page, 'curator1', '12345');
	await page.goto(`/userinfo.php?uid=${uidOf('ahstaff')}`);
	await expect(page).toHaveURL(/\/modules\/formulize\/index\.php\?sid=\d+&ve=\d+$/);
	await expect(accountForm(page)).toBeVisible();
	await expect(editableFields(page)).toHaveCount(0);
	await expect(saveControl(page)).toHaveCount(0);
	await leave(page);
});

test('Permission to update someone\'s entry gets a working form', async ({ page }) => {
	await setCuratorAbilities([...curatorAbilitiesToStart, 'view_globalscope', 'update_other_entries']);
	await login(page, 'curator1', '12345');
	await page.goto(`/userinfo.php?uid=${uidOf('ahstaff')}`);
	await expect(accountForm(page)).toBeVisible();
	await expect(editableFields(page)).not.toHaveCount(0);
	await expect(saveControl(page).first()).toBeVisible();
	await leave(page);
	await setCuratorAbilities(curatorAbilitiesToStart);
	expect(curatorAbilities().filter(name => !NOT_ABILITIES.includes(name))).toEqual(curatorAbilitiesToStart);
});

test('Someone who manages users goes to users.php, and back to the list when they\'re done', async ({ page }) => {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await page.goto(`/userinfo.php?uid=${uidOf('ahstaff')}`);
	await expect(page).toHaveURL(new RegExp(`/modules/formulize/users\\.php\\?ve=${uidOf('ahstaff')}$`));
	await expect(editableFields(page)).not.toHaveCount(0);
	await page.waitForLoadState('load');
	await page.getByRole('link', { name: 'Save and Close' }).click();
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
	await expect(page).toHaveURL(/\/modules\/formulize\/users\.php$/);
	await leave(page);
});

test('A name in the Creator column goes to the same place', async ({ page }) => {
	const openCreator = async () => {
		const fid = dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_handle = 'donors'`)[0][0];
		await page.goto(`/modules/formulize/index.php?fid=${fid}`);
		await expect(page.getByText('Change columns')).toBeVisible();
		const popupPromise = page.context().waitForEvent('page');
		await page.getByRole('button', { name: 'Change columns' }).click();
		const popup = await popupPromise;
		await popup.bringToFront();
		await openAllColumnSections(popup);
		await popup.getByRole('checkbox', { name: 'User who made entry' }).check();
		await applyColumnChanges(popup);
		const tabPromise = page.context().waitForEvent('page');
		await page.locator('a[href*="userinfo.php"]').filter({ hasText: 'Curator One' }).first().click(); // opens in a new tab
		return tabPromise;
	};

	await login(page, 'curator1', '12345');
	const ownAccount = await openCreator();
	await expect(ownAccount).toHaveURL(/\/edituser\.php$/);
	await expect(editableFields(ownAccount)).not.toHaveCount(0);
	await leave(ownAccount);
	await ownAccount.close();

	await page.goto('/user.php?op=logout');
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	const theirAccount = await openCreator();
	await expect(theirAccount).toHaveURL(new RegExp(`/modules/formulize/users\\.php\\?ve=${uidOf('curator1')}$`));
	await expect(editableFields(theirAccount)).not.toHaveCount(0);
	await leave(theirAccount);
	await theirAccount.close();
});

test('A link to a user that isn\'t there says so', async ({ page }) => {
	await login(page, 'curator1', '12345');
	await page.goto('/userinfo.php?uid=999999');
	await expect(page.getByText(NO_USER)).toBeVisible();
	await page.goto('/userinfo.php');
	await expect(page.getByText(NO_USER)).toBeVisible();
});

test('An anonymous visitor gets nothing', async ({ page }) => {
	await page.goto(`/userinfo.php?uid=${uidOf('ahstaff')}`);
	await expect(page.getByText(NO_PERMISSION)).toBeVisible();
	await expect(accountForm(page)).toHaveCount(0);
});
