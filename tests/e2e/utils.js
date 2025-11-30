const { expect } = require('@playwright/test')

export const ElementType = {
	'text': 'element-text',
	'textNumber': 'element-text-number',
	'textPhone': 'element-text-phone',
	'textEmail': 'element-text-email',
	'textarea': 'element-textarea',
	'select': 'element-select',
	'selectLinked': 'element-select-linked',
	'selectUsers': 'element-select-users',
	'selectProvince': 'element-select-province',
	'radio': 'element-radio',
	'radioYN': 'element-radio-yn',
	'radioProvince': 'element-radio-province',
	'checkbox': 'element-checkbox',
	'checkboxLinked': 'element-checkbox-linked',
	'autocomplete': 'element-autocomplete',
	'autocompleteLinked': 'element-autocomplete-linked',
	'autocompleteUsers': 'element-autocomplete-users',
	'listbox': 'element-listbox',
	'listboxLinked': 'element-listbox-linked',
	'listboxUsers': 'element-listbox-users',
	'date': 'element-date',
	'time': 'element-time',
	'fileupload': 'element-fileupload',
	'slider': 'element-slider',
	'duration': 'element-duration',
	'colorpick': 'element-colorpick',
	'areadmodif': 'element-areadmodif',
	'ib': 'element-ib',
	'grid': 'element-grid',
	'derived': 'element-derived',
	'googleaddress': 'element-googleaddress',
	'googlefilepicker': 'element-googlefilepicker',
	'subformListings': 'element-subformlistings',
	'subformEditableRow': 'element-subformeditablerow',
	'subformFullForm': 'element-subformfullform',
};

// Category lookup - just define the mapping rules
const elementToContentMapping = {
	'element-text': {
		tab: 'Text Boxes',
		heading: 'Element: New element (Text Box)'
	},
	'element-text-number': {
		tab: 'Text Boxes',
		heading: 'Element: New element (Number Box)'
	},
	'element-text-phone': {
		tab: 'Text Boxes',
		heading: 'Element: New element (Phone Number)'
	},
	'element-text-email': {
		tab: 'Text Boxes',
		heading: 'Element: New element (Email Address)'
	},
	'element-textarea': {
		tab: 'Text Boxes',
		heading: 'Element: New element (Multi-line Textbox)'
	},
	'element-select': {
		tab: 'Lists',
		heading: 'Element: New element (Dropdown List)'
	},
	'element-select-linked': {
		tab: 'Lists',
		heading: 'Element: New element (Linked Dropdown List)'
	},
	'element-select-users': {
		tab: 'Lists',
		heading: 'Element: New element (Dropdown List of Users)'
	},
	'element-select-province': {
		tab: 'Lists',
		heading: 'Element: New element (Province Dropdown List)'
	},
	'element-radio': {
		tab: 'Lists',
		heading: 'Element: New element (Radio Buttons)'
	},
	'element-radio-yn': {
		tab: 'Lists',
		heading: 'Element: New element (Yes/No Radio Buttons)'
	},
	'element-radio-province': {
		tab: 'Lists',
		heading: 'Element: New element (Province Radio Buttons)'
	},
	'element-checkbox': {
		tab: 'Lists',
		heading: 'Element: New element (Checkboxes)'
	},
	'element-checkbox-linked': {
		tab: 'Lists',
		heading: 'Element: New element (Linked Checkboxes)'
	},
	'element-autocomplete': {
		tab: 'Lists',
		heading: 'Element: New element (Autocomplete List)'
	},
	'element-autocomplete-linked': {
		tab: 'Lists',
		heading: 'Element: New element (Linked Autocomplete List)'
	},
	'element-autocomplete-users': {
		tab: 'Lists',
		heading: 'Element: New element (Autocomplete List of Users)'
	},
	'element-listbox': {
		tab: 'Lists',
		heading: 'Element: New element (Listbox)'
	},
	'element-listbox-linked': {
		tab: 'Lists',
		heading: 'Element: New element (Linked Listbox)'
	},
	'element-listbox-users': {
		tab: 'Lists',
		heading: 'Element: New element (Listbox of Users)'
	},
	'element-date': {
		tab: 'Selectors',
		heading: 'Element: New element (Date Selector)'
	},
	'element-time': {
		tab: 'Selectors',
		heading: 'Element: New element (Time Selector)'
	},
	'element-fileupload': {
		tab: 'Selectors',
		heading: 'Element: New element (File Upload Box)'
	},
	'element-slider': {
		tab: 'Selectors',
		heading: 'Element: New element (Range Slider)'
	},
	'element-duration': {
		tab: 'Selectors',
		heading: 'Element: New element (Duration)'
	},
	'element-colorpick': {
		tab: 'Selectors',
		heading: 'Element: New element (Color Picker)'
	},
	'element-areadmodif': {
		tab: 'Layout',
		heading: 'Element: New element (Text for display (caption and contents))'
	},
	'element-ib': {
		tab: 'Layout',
		heading: 'Element: New element (Text for display (spanning the form))'
	},
	'element-grid': {
		tab: 'Layout',
		heading: 'Element: New element (Table of elements)'
	},
	'element-derived': {
		tab: 'Misc',
		heading: 'Element: New element (Value derived from other elements)'
	},
	'element-googleaddress': {
		tab: 'Misc',
		heading: 'Element: New element (Google Address)'
	},
	'element-googlefilepicker': {
		tab: 'Misc',
		heading: 'Element: New element (Google File Picker)'
	},
	'element-subformlistings': {
		tab: 'Embed a form',
		heading: 'Element: New element (Embeded Form (list view))'
	},
	'element-subformeditablerow': {
		tab: 'Embed a form',
		heading: 'Element: New element (Embeded Form (editable rows))'
	},
	'element-subformfullform': {
		tab: 'Embed a form',
		heading: 'Element: New element (Embeded Form (full entries))'
	},
};

/**
 * Wait for the Formulize form token to be valid before moving on
 * @param {*} page Playwright page object
 */
export async function waitForFormulizeFormToken(page) {
	await page.waitForFunction(
		() => document.querySelector('#formulize_mainform input[name="XOOPS_TOKEN_REQUEST"]').value.length > 0
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
	username = username || 'admin';
	password = username == 'admin' ? 'password' : password;
	// Go to login page
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
 * Formulize Saving validation
 */
export async function saveFormulizeForm(page, timeout = 120000) {
	// Wait for the formulize page token
	await waitForFormulizeFormToken(page);
	await Promise.all([
    page.waitForFunction(() => {
      const element = document.getElementById('savingmessage');
      return element &&
             window.getComputedStyle(element).display === 'flex' &&
             window.getComputedStyle(element).opacity === '1';
    }),
    page.getByRole('button', { name: 'Save' }).click()
  ]);
	// await saving animation dissapear
  await page.waitForFunction(() => {
    const element = document.getElementById('savingmessage');
    return element && window.getComputedStyle(element).display === 'none';
  }, { timeout: 120000 });
	// Ensure the data submitted error does not occurr
	await expect(page.getByText('Error: the data you submitted')).not.toBeVisible({ timeout: 10000 });
}

/**
 * Save changes on admin pages - handles both regular and popup saves
 * @param {*} page
 * @param {*} type
 * @param {*} timeout
 */
export async function saveAdminForm(page, type = 'regular', timeout = 120000) {

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

/**
 *
 * @param {*} page
 * @param {ElementTypeMapping[keyof ElementTypeMapping]} type
 */
export async function addElementForm(page, type) {
	const { tab, heading } = elementToContentMapping[type]
	await page.getByRole('link', { name: 'Add an element' }).first().click();
 	await page.getByRole('link', { name: tab }).click();
	await page.getByTestId(type).click();
	await expect(page.getByRole('heading')).toContainText(heading);
}
