/**
 * Wait for the Formulize form token to be valid before moving on
 * @param {*} page Playwright page object
 */
export async function waitForFormulizeFormToken(page) {
	await page.waitForFunction(
		() => document.querySelector('input[name="XOOPS_TOKEN_REQUEST"]').value.length > 0
	);
}
