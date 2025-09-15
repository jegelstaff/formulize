/**
 * Wait for the Formulize form token to be valid before moving on
 * @param {*} page Playwright page object
 */
export async function waitForFormulizeFormToken(page) {
	await page.waitForFunction(
		() => document.querySelector('input[name="XOOPS_TOKEN_REQUEST"]').value.length > 0
	);
}

export async function loginAsAdmin(page) {
	await loginAs('admin', page);
}

export async function loginAs(username, page) {
	let password = username === 'admin' ? 'password' : '12345';
	await page.goto('/');
	await page.locator('input[name="uname"]').click();
	await page.locator('input[name="uname"]').fill(username);
	await page.locator('input[name="uname"]').press('Tab');
	await page.locator('input[name="pass"]').fill(password);
	await page.locator('input[name="pass"]').press('Enter');
	await expect(page.getByRole('link', { name: 'Admin' })).toBeVisible();
}


export async function saveChanges(page, timeout = 30000) {

	await page.getByRole('button', { name: 'Save your changes' }).click();
	// First wait for opacity to drop (save in progress)
	await page.waitForFunction(
			() => {
					const element = document.querySelector('div.admin-ui');
					if (!element) return false;
					const opacity = parseFloat(window.getComputedStyle(element).opacity);
					return opacity < 1.0; // Wait for it to become less than full opacity
			},
			{ timeout }
	);
	// wait for the admin UI to become fully opaque again
	await page.waitForFunction(
		() => {
			const element = document.querySelector('div.admin-ui');
			if (!element) return false;
			const opacity = parseFloat(window.getComputedStyle(element).opacity);
			return opacity >= 1.0;
		},
  	{ timeout }
	);

}

