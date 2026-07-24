const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, waitForAdminPageReady, addElementForm, ElementType, openElementAccordion, getFidFromFormAdminPage } from '../../utils';

// A handle input that is exactly 60 characters (one over the 59-char limit)
const LONG_HANDLE_INPUT = 'this_handle_is_way_too_long_for_formulize_to_accept_as_is_xy';
// The expected saved value: server truncates to 59 chars via substr($handle, 0, 59)
const TRUNCATED_HANDLE = LONG_HANDLE_INPUT.substring(0, 59);

test('Element handles are truncated to 59 chars and made unique with _f<fid> and _x<n> suffixes', async ({ page }) => {
	await login(page, 'admin');

	// ── Create a dedicated "Handle Tests" form in the Museum application ─────
	await page.goto('/modules/formulize/admin/ui.php?page=home');
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	await page.getByRole('link', { name: 'Create a new form' }).click();
	await waitForAdminPageReady(page);
	await expect(page.locator('input[name="forms-form_title"]')).toBeVisible();
	await page.getByRole('textbox', { name: 'Form title:' }).fill('Handle Tests');
	await saveAdminForm(page);

	// Resolved by the form's handle read off the just-saved settings panel - see getFidFromFormAdminPage.
	const fid = await getFidFromFormAdminPage(page);
	expect(fid).toBeGreaterThan(0);

	const gotoElementsTab = async () => {
		await page.goto(`/modules/formulize/admin/ui.php?page=form&fid=${fid}&tab=elements`);
		await waitForAdminPageReady(page);
	};

	// ── Test 1: Long handle is truncated to 59 characters ───────────────────
	await gotoElementsTab();
	await addElementForm(page, ElementType.text);
	await waitForAdminPageReady(page);
	await page.locator('input[name="elements-ele_caption"]').fill('Long Handle One');
	await page.locator('input[name="elements-ele_handle"]').fill(LONG_HANDLE_INPUT);
	await saveAdminForm(page);
	// After the post-save redirect to the element configure page, the handle
	// input shows the value actually stored in the DB (not the submitted value).
	const handle1 = await page.locator('input[name="elements-ele_handle"]').inputValue();
	expect(handle1).toBe(TRUNCATED_HANDLE);
	expect(handle1.length).toBeLessThanOrEqual(59);

	// ── Test 2: Second element with the same long handle gets _f<fid> suffix ─
	await gotoElementsTab();
	await addElementForm(page, ElementType.text);
	await waitForAdminPageReady(page);
	await page.locator('input[name="elements-ele_caption"]').fill('Long Handle Two');
	await page.locator('input[name="elements-ele_handle"]').fill(LONG_HANDLE_INPUT);
	await saveAdminForm(page);
	const handle2 = await page.locator('input[name="elements-ele_handle"]').inputValue();
	// enforceUniqueElementHandles: first collision → append _f<formId>
	expect(handle2).toBe(`${TRUNCATED_HANDLE}_f${fid}`);

	// ── Test 3: Third element with same long handle gets _x2 suffix ─────────
	await gotoElementsTab();
	await addElementForm(page, ElementType.text);
	await waitForAdminPageReady(page);
	await page.locator('input[name="elements-ele_caption"]').fill('Long Handle Three');
	await page.locator('input[name="elements-ele_handle"]').fill(LONG_HANDLE_INPUT);
	await saveAdminForm(page);
	const handle3 = await page.locator('input[name="elements-ele_handle"]').inputValue();
	// enforceUniqueElementHandles: second collision (_f<fid> taken) → append _x2
	expect(handle3).toBe(`${TRUNCATED_HANDLE}_x2`);

	// ── Test 4: Invalid characters are stripped; hyphens/spaces → underscores
	// sanitize_handle_name: str_replace([" ", "-"], "_") then preg_replace(/[^a-zA-Z0-9_]+/, "") then strtolower
	await gotoElementsTab();
	await addElementForm(page, ElementType.text);
	await waitForAdminPageReady(page);
	await page.locator('input[name="elements-ele_caption"]').fill('Invalid Chars Test');
	await page.locator('input[name="elements-ele_handle"]').fill('My-Test Handle (with symbols) 2024!');
	await saveAdminForm(page);
	const handle4 = await page.locator('input[name="elements-ele_handle"]').inputValue();
	// Expected: '-' → '_', ' ' → '_', '(', ')', '!' removed, then lowercased
	expect(handle4).toBe('my_test_handle_with_symbols_2024');

	// ── Cleanup: delete all four test elements ───────────────────────────────
	await gotoElementsTab();
	for (const caption of [
		'Long Handle One Text Box',
		'Long Handle Two Text Box',
		'Long Handle Three Text Box',
		'Invalid Chars Test Text Box',
	]) {
		await openElementAccordion(page, caption);
		page.once('dialog', dialog => dialog.accept().catch(() => {}));
		await page.getByRole('link', { name: 'Delete' }).click();
		await waitForAdminPageReady(page);
	}
});
