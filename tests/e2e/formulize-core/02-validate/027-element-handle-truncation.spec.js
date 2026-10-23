const { test, expect } = require('@playwright/test');
import { login, saveAdminForm, waitForAdminPageReady, addElementForm, ElementType, deleteElement, createMuseumForm, deleteMuseumForm, dbQuery, dbPrefix, sqlEscape } from '../../utils';

// A handle input that is exactly 60 characters (one over the 59-char limit)
const LONG_HANDLE_INPUT = 'this_handle_is_way_too_long_for_formulize_to_accept_as_is_xy';
// The expected saved value: server truncates to 59 chars via substr($handle, 0, 59)
const TRUNCATED_HANDLE = LONG_HANDLE_INPUT.substring(0, 59);

const FORM_TITLE = 'Handle Tests';

// Set by the test, consumed by the cleanup below
let createdFid = 0;

test.describe.serial('Element handle truncation and uniqueness', () => {

	test('Element handles are truncated to 59 chars and made unique with _f<fid> and _x<n> suffixes', async ({ page }) => {
		// Four element saves, an element deletion (which saves the whole Elements tab) and possibly a leftover
		// form to delete first add up to more than the default per-test timeout leaves room for under load.
		test.setTimeout(300000);
		await login(page, 'admin');

		// ── Clear out anything an interrupted earlier run left behind ────────────
		// Handles are unique across every form, not within one, so a leftover copy of this form still
		// holding these handles would push every handle below onto a different suffix.
		const prefix = dbPrefix();
		for (const [leftoverFid] of dbQuery(`SELECT id_form FROM ${prefix}_formulize_id WHERE form_title = '${sqlEscape(FORM_TITLE)}'`)) {
			await deleteMuseumForm(page, parseInt(leftoverFid, 10));
		}

		// ── Create a dedicated "Handle Tests" form in the Museum application ─────
		const fid = await createMuseumForm(page, FORM_TITLE);
		expect(fid).toBeGreaterThan(0);
		createdFid = fid;

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

		// ── Test 5: Suffixed handles survive a later save that frees up a shorter name ──
		// Saving the Elements tab saves every element on it - and deleting an element is such a save. Once
		// element one is gone its handle is free, and a suffixed handle is longer than 59 characters, so if a
		// save re-truncated handles it would rename element two to element one's old handle, and element
		// three to element two's, without moving their columns in the data table. The save that deletes
		// element one cannot show this, because it saves the elements before deleting it, while its handle
		// is still taken. So the tab is saved once more after the delete.
		await gotoElementsTab();
		await deleteElement(page, 'Long Handle One Text Box');
		await gotoElementsTab();
		await saveAdminForm(page);
		await gotoElementsTab();
		await expect(page.getByRole('link', { name: `Long Handle Two Text Box - ${handle2}`, exact: true })).toBeVisible();
		await expect(page.getByRole('link', { name: `Long Handle Three Text Box - ${handle3}`, exact: true })).toBeVisible();

		// and each surviving element still has its own column, while the deleted one's column is gone
		const [[formHandle]] = dbQuery(`SELECT form_handle FROM ${prefix}_formulize_id WHERE id_form = ${fid}`);
		const columns = dbQuery(`SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '${sqlEscape(`${prefix}_formulize_${formHandle}`)}'`).map(row => row[0]);
		expect(columns).toEqual(expect.arrayContaining([handle2, handle3, handle4]));
		expect(columns).not.toContain(handle1);
	});

	// Deleting the form takes its elements and its data table with it, so the next run starts clean.
	test.afterAll(async ({ browser }) => {
		if (!createdFid) { return; } // the test never got far enough to create a form
		const page = await browser.newPage();
		await login(page, 'admin');
		await deleteMuseumForm(page, createdFid);
		await page.close();
	});
});
