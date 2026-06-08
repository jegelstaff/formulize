const { test, expect } = require('@playwright/test');
import { login } from '../../utils';

// ============================================================
// Phase 4 — Cross-department data isolation
// ============================================================
// Verifies that the EAU/EAG group model + the "their group(s)" (groupscope)
// visibility set on the Artifacts form in 015 produces per-department isolation
// on the data created in 020:
//   - ahstaff (Ancient History - All Users, groupscope) sees only Ancient
//     History artifacts (the ones ahstaff created), NOT Modern History ones.
//   - mhstaff (Modern History - All Users, groupscope) sees the inverse.
//   - curator1 (Departments - Curators, global view, and a member of BOTH
//     departments' groups) sees every artifact.
//
// Isolation is NOT configured per-form explicitly — it falls out of each user's
// dynamic groupscope (the union of their groups that hold view_form) combined
// with entry ownership. See plan Phase 4 + the cascade discussion.
//
// Representative artifacts (from 020):
//   Ancient History (owned by ahstaff): "Roman Coin"
//   Modern History  (owned by mhstaff): "Florentine Book"

async function openArtifactsList(page, username) {
	await login(page, username, '12345');
	await page.locator('#burger-and-logo').getByRole('link').first().click();
	await page.locator('#mainmenu').getByRole('link', { name: 'Artifacts', exact: true }).click();
	// Wait until the list has actually rendered (the "Showing entries" footer is
	// present) before asserting — otherwise a not.toBeVisible() check could pass
	// merely because the list hasn't loaded yet.
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible({ timeout: 30000 });
}

test.describe('Cross-department isolation (Artifacts)', () => {

	test('ahstaff sees only Ancient History artifacts', async ({ page }) => {
		await openArtifactsList(page, 'ahstaff');
		// Sees their own department's data...
		await expect(page.getByText('Roman Coin', { exact: true }).first()).toBeVisible();
		// ...but not Modern History data owned by mhstaff.
		await expect(page.getByText('Florentine Book', { exact: true })).not.toBeVisible();
		await expect(page.getByText('Showing entries: 1 to 6 of 6')).toBeVisible();
	});

	test('mhstaff sees only Modern History artifacts', async ({ page }) => {
		await openArtifactsList(page, 'mhstaff');
		await expect(page.getByText('Florentine Book', { exact: true }).first()).toBeVisible();
		await expect(page.getByText('Roman Coin', { exact: true })).not.toBeVisible();
		await expect(page.getByText('Showing entries: 1 to 7 of 7')).toBeVisible();
	});

	test('curator1 sees artifacts from both departments', async ({ page }) => {
		await openArtifactsList(page, 'curator1');
		// A curator is a member of both departments' groups AND has global view,
		// so both departments' artifacts are visible.
		await expect(page.getByText('Roman Coin', { exact: true }).first()).toBeVisible();
		await expect(page.getByText('Florentine Book', { exact: true }).first()).toBeVisible();
		await expect(page.getByText('of 13')).toBeVisible();
	});
});
