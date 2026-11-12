const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD } from '../config';
import { login, saveAdminForm, waitForAdminPageReady } from '../../utils';

// The Menu tab of the admin UI (Admin > Menu, admin/site_menu.php): the top level of the menu, which is the
// applications, "Forms with no app" and the links beside them. These tests put it in order by keyboard and by
// dragging, add links beside the applications, check what people see in the menu and where they land when they
// log in, and do the same through the MCP tools. See ai-context/menus.md.
//
// It changes the menu everyone sees, so it is sequential. It records the order it found and puts it back, and
// deletes the links it made, through the same tab.

test.describe.configure({ mode: 'serial' });

const MENU_TAB = '/modules/formulize/admin/ui.php?page=site-menu';
const MCP = '/mcp/index.php/mcp';
const LINK_SCREEN = 'Menu tab test: collections';
const LINK_URL = 'Menu tab test: help';
const LINK_MCP = 'Menu tab test: from MCP';

let startingOrder = [];
let mcpWasEnabled = null;
let apiKey = null;
let createdApiKey = false;
let requestId = 0;

/** The data-id of each item at the top level of the Menu tab, in order, such as application-3 or link-41. */
async function menuTabOrder(page) {
	await expect(page.locator('#formulize-sitemenu-list > li').first()).toBeVisible();
	return page.locator('#formulize-sitemenu-list > li').evaluateAll(items => items.map(item => item.getAttribute('data-id')));
}

/** The item in the Menu tab with this title. */
function menuTabItem(page, title) {
	return page.locator('#formulize-sitemenu-list > li').filter({ has: page.locator('.fz-accordion__title', { hasText: title }) });
}

async function openMenuTab(page) {
	await page.goto(MENU_TAB);
	await waitForAdminPageReady(page);
	await expect(page.getByRole('heading', { name: 'What\'s in the menu' })).toBeVisible();
}

/**
 * Put the top level of the menu in the order given, with the keyboard, the way a person can: each handle in turn,
 * Home to the top, then down to its place. Doesn't save.
 * @param {string[]} order data-ids, as menuTabOrder() gives them
 */
async function arrangeMenuTab(page, order) {
	for (let place = 0; place < order.length; place++) {
		const handle = page.locator(`#formulize-sitemenu-list > li[data-id="${order[place]}"] > .fz-accordion__handle`);
		await handle.focus();
		await page.keyboard.press('Home');
		for (let step = 0; step < place; step++) {
			await page.keyboard.press('ArrowDown');
		}
	}
	expect(await menuTabOrder(page)).toEqual(order);
}

/** What a person sees at the top level of the menu: each application's name and each link's text, in order. */
async function frontEndMenu(page) {
	await page.goto('/modules/formulize/application.php?id=all');
	const items = page.locator('#mainmenu > li > a.main-menu__item-link, #mainmenu > details > summary');
	await expect(items.first()).toBeAttached();
	return (await items.allTextContents()).map(text => text.trim());
}

/** Add a link beside the applications, through the Menu tab. */
async function addTopLevelLink(page, { text, target, url, sees, landsOn = [] }) {
	await openMenuTab(page);
	await page.locator('#formulize-sitemenu-add > summary').click();
	const editor = page.locator('[data-sitemenu-editor="new"]');
	const goesTo = editor.getByRole('group', { name: 'This link goes to' });
	// a form or screen is chosen from a list, and a URL typed in; only the one chosen is shown
	await expect(goesTo.getByRole('radio', { name: 'A form or screen' })).toBeChecked();
	if (target === 'url') {
		await goesTo.getByRole('radio', { name: 'A URL' }).check();
		await expect(editor.getByLabel('Form or screen', { exact: true })).toBeHidden();
		await expect(editor.getByLabel('URL', { exact: true })).toBeVisible();
		await editor.getByLabel('URL', { exact: true }).fill(url);
	} else {
		await expect(editor.getByLabel('URL', { exact: true })).toBeHidden();
		await editor.getByLabel('Form or screen', { exact: true }).selectOption(target);
	}
	await editor.getByLabel('Text for this link').fill(text);
	for (const group of sees) {
		await editor.getByRole('group', { name: 'Show this link to these groups' }).getByLabel(group, { exact: true }).check();
	}
	for (const group of landsOn) {
		await editor.getByRole('group', { name: 'Send these groups to this link right after they log in' }).getByLabel(group, { exact: true }).check();
	}
	await Promise.all([
		page.waitForURL(/page=site-menu/, { waitUntil: 'load' }),
		page.getByRole('button', { name: 'Add the link' }).click(),
	]);
	await expect(menuTabItem(page, text)).toBeVisible();
}

/** Delete a link beside the applications, through the Menu tab. */
async function deleteTopLevelLink(page, text) {
	await openMenuTab(page);
	const item = menuTabItem(page, text);
	if (!(await item.count())) {
		return;
	}
	await item.locator('summary').click();
	page.once('dialog', dialog => dialog.accept());
	await item.getByRole('button', { name: 'Delete this link' }).click();
	await expect(menuTabItem(page, text)).toHaveCount(0, { timeout: 30000 });
}

/** The value of the option for a screen, in the Menu tab's list of where a link can go. */
async function screenOption(page, screenTitle) {
	const value = await page.locator('[data-sitemenu-editor="new"] [data-sitemenu-target] option').evaluateAll(
		(options, title) => (options.find(option => option.value.startsWith('sid=') && option.textContent.trim() === title) || {}).value,
		screenTitle
	);
	expect(value, `the screen ${screenTitle} should be in the list`).toBeTruthy();
	return value;
}

async function callTool(request, name, args) {
	const res = await request.post(MCP, {
		headers: { 'Authorization': `Bearer ${apiKey}` },
		data: { jsonrpc: '2.0', id: ++requestId, method: 'tools/call', params: { name, arguments: args } }
	});
	const body = await res.json();
	if (body.error) {
		return { result: null, error: body.error };
	}
	return { result: JSON.parse(body.result.content[0].text), error: null };
}

async function callToolOk(request, name, args) {
	const { result, error } = await callTool(request, name, args);
	expect(error, `${name} should succeed, but returned: ${JSON.stringify(error)}`).toBeNull();
	return result;
}

test.describe('The Menu tab', () => {

	test('puts the applications in order, by keyboard and by dragging, and the menu follows', async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await openMenuTab(page);
		startingOrder = await menuTabOrder(page);
		const museum = menuTabItem(page, 'Museum');
		const staff = menuTabItem(page, 'Staff Management');
		await expect(museum).toBeVisible();
		await expect(staff).toBeVisible();
		const museumId = await museum.getAttribute('data-id');
		const staffId = await staff.getAttribute('data-id');

		// Staff Management to the top with the keyboard. The focus stays on its handle, and a screen reader is told
		// where it went. A key that doesn't move the item announces nothing, and in the suite's own system Museum
		// and Staff Management may be the only two items, in that order, so End goes first. Then Home is a move
		// wherever Staff Management started.
		const staffHandle = staff.locator('.fz-accordion__handle');
		await staffHandle.focus();
		await page.keyboard.press('End');
		await page.keyboard.press('Home');
		await expect(staffHandle).toBeFocused();
		await expect(page.locator('#formulize-sitemenu-list + [role="status"]')).toHaveText(new RegExp(`^Moved Staff Management to position 1 of ${startingOrder.length}$`));
		expect((await menuTabOrder(page))[0]).toBe(staffId);
		await expect(page.locator('#savewarning')).toBeVisible();

		// Museum dragged by its handle above Staff Management, to the top. The mouse moves in steps, as a person's does.
		const handle = await museum.locator('.fz-accordion__handle').boundingBox();
		const list = await page.locator('#formulize-sitemenu-list').boundingBox();
		await page.mouse.move(handle.x + handle.width / 2, handle.y + handle.height / 2);
		await page.mouse.down();
		const steps = 20;
		for (let step = 1; step <= steps; step++) {
			await page.mouse.move(handle.x + handle.width / 2, handle.y + handle.height / 2 + (list.y - 20 - handle.y - handle.height / 2) * step / steps);
		}
		await page.mouse.up();
		const arranged = await menuTabOrder(page);
		expect(arranged[0]).toBe(museumId);
		expect(arranged[1]).toBe(staffId);

		await saveAdminForm(page);
		await openMenuTab(page);
		expect(await menuTabOrder(page)).toEqual(arranged);

		const menu = await frontEndMenu(page);
		expect(menu.indexOf('Museum')).toBeGreaterThanOrEqual(0);
		expect(menu.indexOf('Museum')).toBeLessThan(menu.indexOf('Staff Management'));
		expect(menu[0]).toBe('Museum');
	});

	test('an application opens to show its links, which are changed on its own Menu Entries tab', async ({ page }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		await openMenuTab(page);
		const museum = menuTabItem(page, 'Museum');
		await museum.locator('summary').click();
		await expect(museum.getByText('Artifacts', { exact: true })).toBeVisible();
		await expect(museum.getByRole('link', { name: 'Manage this application\'s menu entries' })).toHaveAttribute('href', /page=application&aid=\d+&tab=menu%20entries/);
		// read only: the links in an application have no fields here
		await expect(museum.locator('[data-sitemenu-editor]')).toHaveCount(0);
	});

	test('adds links beside the applications, which people see and can land on', async ({ page, browser }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
		// left over from an interrupted run
		await deleteTopLevelLink(page, LINK_SCREEN);
		await deleteTopLevelLink(page, LINK_URL);

		await openMenuTab(page);
		await page.locator('#formulize-sitemenu-add > summary').click();
		const collectionsScreen = await screenOption(page, 'Collections');
		await addTopLevelLink(page, { text: LINK_SCREEN, target: collectionsScreen, sees: ['All Staff'], landsOn: ['All Staff'] });
		await addTopLevelLink(page, { text: LINK_URL, target: 'url', url: 'https://example.com/help', sees: ['Registered Users'] });

		// a link without somewhere to go isn't saved
		await openMenuTab(page);
		await page.locator('#formulize-sitemenu-add > summary').click();
		await page.locator('[data-sitemenu-editor="new"]').getByLabel('Text for this link').fill('Goes nowhere');
		const alerts = [];
		page.on('dialog', dialog => { alerts.push(dialog.message()); dialog.accept(); });
		await page.getByRole('button', { name: 'Add the link' }).click();
		await expect.poll(() => alerts.length).toBe(1);
		expect(alerts[0]).toContain('Choose the form or screen that the new link goes to');

		// New links go at the end, after the applications, so ahstaff still lands on Artifacts in Museum, the start
		// page their department's groups have
		const ahstaff = await browser.newPage();
		await login(ahstaff, 'ahstaff');
		await expect(ahstaff).toHaveTitle(/^Artifacts/);
		const menu = await frontEndMenu(ahstaff);
		expect(menu).toContain(LINK_SCREEN);
		expect(menu).toContain(LINK_URL);
		expect(menu.indexOf('Museum')).toBeLessThan(menu.indexOf(LINK_SCREEN));
		await expect(ahstaff.locator('#mainmenu a', { hasText: LINK_URL })).toHaveAttribute('target', '_blank');
		await ahstaff.goto('/user.php?op=logout');

		// the admin isn't in All Staff, so doesn't see the link that is only for them
		expect(await frontEndMenu(page)).not.toContain(LINK_SCREEN);

		// moved to the top, the link is the first of ahstaff's start pages in the menu, so they land on it
		await openMenuTab(page);
		const linkId = await menuTabItem(page, LINK_SCREEN).getAttribute('data-id');
		const order = await menuTabOrder(page);
		await arrangeMenuTab(page, [linkId, ...order.filter(id => id !== linkId)]);
		await saveAdminForm(page);
		await login(ahstaff, 'ahstaff');
		await expect(ahstaff).toHaveTitle(/^Collections/);
		expect((await frontEndMenu(ahstaff))[0]).toBe(LINK_SCREEN);
		await ahstaff.close();
	});

	test('the MCP tools add links beside the applications and put the top level in order', async ({ page, request }) => {
		await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);

		// the MCP server on, and an API key for the admin, as 040 does
		await page.goto('/modules/formulize/admin/ui.php?page=settings&view=ai');
		await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
		mcpWasEnabled = await page.locator('input[name="formulizeMCPServerEnabled"][value="1"]').isChecked();
		if (!mcpWasEnabled) {
			await page.locator('input[name="formulizeMCPServerEnabled"][value="1"]').check();
			await page.getByRole('button', { name: 'Save your changes' }).click();
			await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
			await expect(page.locator('input[name="formulizeMCPServerEnabled"][value="1"]')).toBeChecked();
		}
		await page.goto('/modules/formulize/admin/ui.php?page=users&view=apikeys');
		await page.locator('.admin-ui').waitFor({ state: 'visible' });
		if (!(await page.locator('td[id=key-1]').count())) {
			await page.getByRole('textbox').fill('admin');
			await page.getByRole('button', { name: 'Search' }).click();
			await page.getByRole('radio', { name: 'admin' }).check();
			await page.getByRole('button', { name: 'Create' }).click();
			await expect(page.locator('td[id=key-1]')).toBeVisible();
			createdApiKey = true;
			await page.goto('/modules/formulize/admin/ui.php?page=users&view=apikeys');
			await page.locator('.admin-ui').waitFor({ state: 'visible' });
		}
		apiKey = await page.locator('td[id=key-1] .key-copy').getAttribute('data-key');
		expect(apiKey).toMatch(/^[0-9a-f]{32}$/);

		// both an application and the top level is refused
		const both = await callTool(request, 'create_menu_item', { application_id: 1, top_level: true, link_text: LINK_MCP, target: { url: 'https://example.com' }, groups_that_can_see: [2] });
		expect(both.error).not.toBeNull();

		const created = await callToolOk(request, 'create_menu_item', { top_level: true, link_text: LINK_MCP, target: { url: 'https://example.com/mcp' }, groups_that_can_see: [2] });
		const menuId = created.menu_item.menu_id;
		expect(created.where_it_went).toContain('change_menu_order');

		// listed beside the applications, at the end
		let listed = await callToolOk(request, 'list_menu_items', {});
		const fromMcp = listed.top_level_menu_items.find(item => item.menu_id === menuId);
		expect(fromMcp).toBeTruthy();
		const positions = [...listed.applications.map(app => app.menu_position), ...listed.top_level_menu_items.map(item => item.menu_position)];
		expect(fromMcp.menu_position).toBe(Math.max(...positions));

		// the whole top level, as it is now
		const current = [...listed.applications.map(app => ({ type: 'application', id: app.application_id, position: app.menu_position })),
			...listed.top_level_menu_items.map(item => ({ type: 'menu_item', id: item.menu_id, position: item.menu_position }))]
			.sort((a, b) => a.position - b.position).map(({ type, id }) => ({ type, id }));

		// a list that leaves something out is refused, and says what
		const incomplete = await callTool(request, 'change_menu_order', { items: current.slice(1) });
		expect(incomplete.error).not.toBeNull();
		expect(JSON.stringify(incomplete.error)).toContain('left out');

		// the new item to the top
		const moved = [{ type: 'menu_item', id: menuId }, ...current.filter(item => !(item.type === 'menu_item' && item.id === menuId))];
		const ordered = await callToolOk(request, 'change_menu_order', { items: moved });
		expect(ordered.top_level_of_the_menu[0]).toMatchObject({ type: 'menu_item', id: menuId, menu_position: 1 });
		await openMenuTab(page);
		expect((await menuTabOrder(page))[0]).toBe(`link-${menuId}`);

		// change_menu_item_order is for the items in an application
		const wrongTool = await callTool(request, 'change_menu_item_order', { application_id: -1, order: [menuId] });
		expect(JSON.stringify(wrongTool.error)).toContain('change_menu_order');

		// deleted, it leaves the menu and the order
		await callToolOk(request, 'update_menu_item', { menu_id: menuId, delete: true });
		listed = await callToolOk(request, 'list_menu_items', {});
		expect(listed.top_level_menu_items.find(item => item.menu_id === menuId)).toBeUndefined();
		await openMenuTab(page);
		expect(await menuTabOrder(page)).not.toContain(`link-${menuId}`);
	});
});

// Put back what these tests changed, however far they got: the links they made, the order of the menu, and the
// MCP server and API key, if they turned on or made them.
test.afterAll(async ({ browser }) => {
	const page = await browser.newPage();
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	await deleteTopLevelLink(page, LINK_SCREEN);
	await deleteTopLevelLink(page, LINK_URL);
	await deleteTopLevelLink(page, LINK_MCP);
	if (startingOrder.length) {
		await openMenuTab(page);
		const now = await menuTabOrder(page);
		const restored = startingOrder.filter(id => now.includes(id));
		if (JSON.stringify(now) !== JSON.stringify(restored)) {
			await arrangeMenuTab(page, restored);
			await saveAdminForm(page);
			await openMenuTab(page);
			expect(await menuTabOrder(page)).toEqual(restored);
		}
	}
	if (createdApiKey) {
		await page.goto('/modules/formulize/admin/ui.php?page=users&view=apikeys');
		await page.locator('.admin-ui').waitFor({ state: 'visible' });
		await Promise.all([page.waitForLoadState('load'), page.locator('td[id=key-1]').locator('xpath=preceding-sibling::td[1]').locator('a').click()]);
	}
	if (mcpWasEnabled === false) {
		await page.goto('/modules/formulize/admin/ui.php?page=settings&view=ai');
		await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
		await page.locator('input[name="formulizeMCPServerEnabled"][value="0"]').check();
		await page.getByRole('button', { name: 'Save your changes' }).click();
		await page.locator('.formulize-config-settings').waitFor({ state: 'visible' });
	}
	await page.close();
});
