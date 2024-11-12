/**
 * Wait for the Formulize form token to be valid before moving on
 * @param {*} page Playwright page object
 */
export async function waitForFormulizeFormToken(page) {
	await page.waitForFunction(
		() => document.querySelector('input[name="XOOPS_TOKEN_REQUEST"]').value.length > 0
	);
}

/**
 * Simple login function for the Formulize login page
 * @param {*} page Playwright page object
 * @param {*} username The username to login with
 * @param {*} password	The password to login with
 */
export async function login(page, username, password) {
	await page.goto('/');
	await page.locator('input[name="uname"]').click();
	await page.locator('input[name="uname"]').fill(username);
	await page.locator('input[name="uname"]').press('Tab');
	await page.locator('input[name="pass"]').fill(password);
	await page.getByRole('button', { name: 'Login' }).click();
}
