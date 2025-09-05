const { test, expect } = require('@playwright/test')

test.describe('Create Forms', () => {
	test('Creation of Formulize Forms', async ({ page }) => {
		await page.goto('http://localhost:8080/');
		await page.locator('input[name="uname"]').click();
		await page.locator('input[name="uname"]').fill('admin');
		await page.locator('input[name="uname"]').press('Tab');
		await page.locator('input[name="pass"]').fill('admin');
		await page.locator('input[name="pass"]').press('Enter');
	})
});
