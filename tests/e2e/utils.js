/**
 * Wait for the Formulize form token to be valid before moving on
 * @param {*} page Playwright page object
 */
export function waitForFormulizeFormToken(page) {
	return page.waitForFunction(
		() => document.querySelector('input[name="XOOPS_TOKEN_REQUEST"]').value.length > 0
	)
}

/**
 * Login function
 *
 * @param {*} page
 * @param {*} username
 * @param {*} password
 */
export async function login(page, username, password = '12345') {
	await page.goto('/user.php');
	await page.locator('input[name="uname"]').click();
	await page.locator('input[name="uname"]').fill(username);
	await page.locator('input[name="uname"]').press('Tab');
	await page.locator('input[name="pass"]').fill(password);
	await Promise.all([
    page.waitForURL(/\/modules\/formulize\/.*/),
    page.locator('input[name="pass"]').press('Enter')
  ]);
}

/**
 * Save changes on admin pages - handles both regular and popup saves
 * @param {*} page
 * @param {*} type
 * @param {*} timeout
 */
export async function saveAdminForm(page, type = 'regular', timeout = 10000) {

	let opacityTarget = 'div.admin-ui';

	// type can be 'regular' or 'popup'
	if(type == 'regular') {
		await page.getByRole('button', { name: 'Save your changes' }).click();
		// First wait for opacity to drop (save in progress)
		await page.waitForFunction(
			(selector) => {
				const element = document.querySelector(selector);
				if (!element) return false;
				const opacity = parseFloat(window.getComputedStyle(element).opacity);
				return opacity < 1.0; // Wait for it to become less than full opacity
			},
			opacityTarget, // Pass the selector as an argument
			{ timeout }
		);
	} else {
		let opacityTarget = '#dialog-page-settings';
		await page.getByLabel('Edit Page Settings').getByRole('button', { name: 'Save your changes' }).click();
		await page.waitForTimeout(200); // wait a moment for the dialog to start closing
	}

	// wait for the admin UI to become fully opaque again
	return page.waitForFunction(
		(selector) => {
			const element = document.querySelector(selector);
			if (!element) return false;
			const opacity = parseFloat(window.getComputedStyle(element).opacity);
			return opacity >= 1.0;
		},
		opacityTarget, // Pass the selector as an argument
  	{ timeout }
	);

}

export async function waitForAdminPageReady(page) {
  // Wait for network to be idle first
  await page.waitForLoadState('networkidle');

  // Wait for jQuery and jQuery UI to be fully loaded and ready
  return page.waitForFunction(() => {
    // Check if jQuery is loaded
    if (!window.$ || !window.jQuery) return false;

    // Check if document is ready
    if (document.readyState !== 'complete') return false;

    // Check if jQuery has no pending AJAX requests
    if ($.active && $.active > 0) return false;

    // Check if jQuery UI is loaded (if you use it)
    if (window.$ && !$.ui) return false;

    // Check if all jQuery UI widgets have been initialized
    // Look for common indicators that widgets are ready
    const hasLoadingElements = $('.ui-loading, .loading, [data-loading="true"]').length > 0;
    if (hasLoadingElements) return false;

    // Ensure jQuery's ready event has fired
    return $.isReady === true;
  });
}

// Possible function to employ if accordion clicks are not working reliably
// Attempts normal clicks a few times, then falls back to JavaScript if needed
// Requires the headerSelector (the clickable header) and contentSelector (the content that should appear)
// Optionally specify maxRetries (default 3)
export async function openAccordion(page, headerSelector, contentSelector, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    await page.locator(headerSelector).click();

    try {
      await page.locator(contentSelector).waitFor({
        state: 'visible',
        timeout: 2000
      });
      return; // Success, exit
    } catch (error) {
      if (i === maxRetries - 1) {
        // Last attempt failed, try JavaScript fallback
        console.log('Normal clicks failed, attempting JavaScript fallback...');

        try {
          await page.locator(headerSelector).evaluate((el, contentSel) => {
            // Try jQuery trigger first if available
            if (window.jQuery) {
              window.jQuery(el).trigger('click');
            }

            // Force open the accordion manually
            el.classList.add('active', 'open', 'expanded');

            // Find and show the content
            let content = el.nextElementSibling;

            // If contentSelector is provided, try to find it more specifically
            if (contentSel) {
              const foundContent = document.querySelector(contentSel);
              if (foundContent) {
                content = foundContent;
              }
            }

            if (content) {
              content.style.display = 'block';
              content.style.height = 'auto';
              content.style.overflow = 'visible';
              content.classList.remove('collapsed');
              content.classList.add('show', 'expanded');
            }
          }, contentSelector);

          // Wait a moment for any animations
          await page.waitForTimeout(500);

          // Check if the JavaScript fallback worked
          await page.locator(contentSelector).waitFor({
            state: 'visible',
            timeout: 2000
          });

          console.log('JavaScript fallback succeeded');
          return;

        } catch (jsError) {
          throw new Error(`Accordion failed to open after ${maxRetries} click attempts and JavaScript fallback. Last error: ${jsError.message}`);
        }
      }

      // Wait before retrying
      await page.waitForTimeout(500);
    }
  }
}

