/**
 * Wait for the Formulize form token to be valid before moving on
 * @param {*} page Playwright page object
 */

import { expect } from '@playwright/test';

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
	await expect(page.getByRole('link', { name: 'Edit Account' })).toBeVisible();
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

