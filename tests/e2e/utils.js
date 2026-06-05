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
 * @param {string} buttonText - The text of the button to click to trigger the save (default 'Save')
 * @param {number} timeout - Maximum time to wait for the saving process to complete (default 120000ms)
 */
export async function saveFormulizeForm(page, buttonText = 'Save', timeout = 120000) {
	// Wait for the formulize page token
  await page.locator('div#formulizeform').click();
	await waitForFormulizeFormToken(page);
	await Promise.all([
    page.waitForFunction(() => {
      const element = document.getElementById('savingmessage');
      return element &&
             window.getComputedStyle(element).display === 'flex' &&
             window.getComputedStyle(element).opacity === '1';
    }),
    page.getByRole('button', { name: buttonText }).click()
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
 * Wait for Working message to disappear and page to be loaded
 */
export async function waitForWorkingMessage(page) {
	// first, make sure it is visible
	page.waitForFunction(() => {
		const element = document.getElementById('workingmessage');
		return element && window.getComputedStyle(element).display === 'block';
	}, { timeout: 10000 });
	// then wait for it to disappear
	await page.waitForFunction(() => {
		const element = document.getElementById('workingmessage');
		return !element || window.getComputedStyle(element).display === 'none';
	}, { timeout: 120000 });
	await page.waitForLoadState('networkidle');
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

/**
 * Open an accordion, with different options for different types of accordions
 * @param {object} page - Playwright page object
 * @param {string} accordionType - The type of accordion, element and menu supported at present
 * @param {string} accordionHeaderText - Text in the header of the accordion we're going to open
 * @param {number} timeout - Maximum time to wait for animation completion (default 2000ms)
 */
 async function openAccordion(page, accordionType, accordionHeaderText, timeout = 2000) {
	await expect(page.getByRole('link', { name: accordionHeaderText })).toBeVisible();

	if(accordionType !== 'element' && accordionType !== 'menu') {
		throw new Error('Unsupported accordion type: ' + accordionType);
	}

	let targetNumber = 0; // all menu and element accordions are closed by default
	let linkClass = 'delete'+accordionType+'link';

	// Check there's the expected number of links visible (menu starts empty)
	await page.waitForFunction((targetNumber) => {
		const deleteLinks = Array.from(document.querySelectorAll('a'))
			.filter(a => a.textContent.includes('Delete') && a.offsetParent !== null);
		return deleteLinks.length === targetNumber;
	}, targetNumber, { timeout });

	// click what we want to open
	await page.getByRole('link', { name: accordionHeaderText }).click();

	// debugging... show browser console messages
	page.on('console', msg => console.log('Browser log:', msg.text()));

	// Wait for the accordion animation to complete
	await page.waitForFunction(({text, linkClass}) => {

		// Normalize whitespace function
		const normalizeText = (str) => str.replace(/\s+/g, '');
		const normalizedSearchText = normalizeText(text);

		// Find the h3 with this text
		const headers = Array.from(document.querySelectorAll('h3'));
		const targetHeader = headers.find(h => normalizeText(h.textContent).includes(normalizedSearchText));

		if (!targetHeader) return false;

		// Get the content div (next sibling of h3)
		const contentPanel = targetHeader.nextElementSibling;
		if (!contentPanel) return false;

		// Check if the delete link exists and is visible within this content panel
		const deleteLink = contentPanel.querySelector('a.'+linkClass);
		const deleteVisible = deleteLink && deleteLink.offsetParent !== null;

		// Check there's only one Delete link visible on the page
		const deleteLinks = Array.from(document.querySelectorAll('a'))
			.filter(a => a.textContent.includes('Delete') && a.offsetParent !== null);

		return deleteVisible && deleteLinks.length === 1;
	}, { text: accordionHeaderText, linkClass: linkClass }, { timeout });
}

/**
 * Clicks an element accordion item and waits for the jQuery UI accordion animation to complete
 * @param {object} page - Playwright page object
 * @param {string} accordionHeaderText - Text in the header of the accordion we're going to open
 * @param {number} timeout - Maximum time to wait for animation completion (default 2000ms)
 */
export async function openElementAccordion(page, accordionHeaderText, timeout = 2000) {
	await openAccordion(page, 'element', accordionHeaderText, timeout);
}

/**
 * Clicks a menu accordion item and waits for the jQuery UI accordion animation to complete
 * @param {object} page - Playwright page object
 * @param {string} accordionHeaderText - Text in the header of the accordion we're going to open
 * @param {number} timeout - Maximum time to wait for animation completion (default 2000ms)
 */
export async function openMenuAccordion(page, accordionHeaderText, timeout = 2000) {
	await openAccordion(page, 'menu', accordionHeaderText, timeout);
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
 * Waits for a conditional element to appear or be updated after a trigger action
 * Does not work for conditional elements that disappear on a trigger action - will need a different function for that
 * @param {Page} page - Playwright page object
 * @param {string} className - The class name of the conditional element (without the dot)
 * @param {Function} triggerAction - An async function that triggers the conditional behavior
 */
export async function conditionalElementReady(page, handle, triggerAction) {

	// The child element has a reliable class
	const childSelector = `.formulize-label-${handle}`;

	// Check if child exists
	const childCount = await page.locator(childSelector).count();

	let initialHTML = '';

	if (childCount > 0) {
		// Get the parent of that child
		const parent = page.locator(childSelector).locator('..');
		initialHTML = await parent.innerHTML();
	}

	await triggerAction();

	// Wait for the parent's content to change
	await page.waitForFunction(
		({ handle, initialHTML }) => {
			// Check if child exists
			if(initialHTML == '') {
				return document.querySelector(`.formulize-label-${handle}`) !== null;
			} else {
				const child = document.querySelector(`.formulize-label-${handle}`);
				const parent = child ? child.parentElement : null;
				return parent && parent.innerHTML !== initialHTML;
			}
		},
		{ handle, initialHTML }
	);

	// Wait for the child to fade in
	await expect(page.locator(childSelector)).toHaveCSS('opacity', '1');

	// Wait for conditionalCheckInProgress to be stable at 0
	// Because we might have cascading deferred conditional checks :(
	await page.waitForFunction(() => {
		// Store the stable check start time on window object
		if (!window._stableCheckStart) {
			window._stableCheckStart = null;
		}

		const isReady = typeof window.conditionalCheckInProgress === 'undefined' ||
		                window.conditionalCheckInProgress === 0;

		if (isReady) {
			// Start/continue the stability timer
			if (!window._stableCheckStart) {
				window._stableCheckStart = Date.now();
			}
			// Check if it's been stable for 300ms
			return Date.now() - window._stableCheckStart > 1000;
		} else {
			// Reset the timer if it goes back to busy
			window._stableCheckStart = null;
			return false;
		}
	}, {}, { timeout: 30000 });

	// Clean up the marker
	await page.evaluate(() => delete window._stableCheckStart);

	await page.waitForLoadState('networkidle');

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

// =============================================================================
// EAU / EAG helpers (Phase 1 — see plan: the-current-branch-adds-luminous-catmull.md)
// =============================================================================

/**
 * On a form-settings page, switch the form type to "Entries are Users" and
 * wait for the user-account configuration container to slide into view.
 * The userAccount system elements get auto-injected on the next save.
 *
 * @param {object} page Playwright page
 */
export async function enableEntriesAreUsers(page) {
	await page.locator('#form_type-users').check();
	await expect(page.locator('#entries_are_users_container')).toBeVisible();
}

/**
 * On a form-settings page, switch the form type to "Entries are Groups" and
 * wait for the categories configuration container to slide into view.
 *
 * @param {object} page Playwright page
 */
export async function enableEntriesAreGroups(page) {
	await page.locator('#form_type-groups').check();
	await expect(page.locator('#entries_are_groups_container')).toBeVisible();
	await expect(page.locator('#group_categories_container')).toBeVisible();
}

/**
 * Add a category to an Entries-Are-Groups form's settings. The implicit
 * "All Users" base category is always present and not editable.
 *
 * @param {object} page Playwright page
 * @param {string} categoryName The visible name of the new category
 */
export async function addEAGCategory(page, categoryName) {
	const beforeCount = await page.locator('#group_categories_list .group-category-item').count();
	await page.locator('#add_group_category_btn').click();
	// wait for the new input row to appear and gain focus
	await expect(page.locator('#group_categories_list .group-category-item')).toHaveCount(beforeCount + 1);
	const newInput = page.locator('#group_categories_list input[name^="group_categories[new_"]').last();
	await newInput.fill(categoryName);
}

/**
 * Use the EAU default-groups autocomplete to add a group as a default
 * membership. Returns the data-groupid attribute of the resulting
 * `.default-group-item`, which subsequent helpers reference.
 *
 * @param {object} page Playwright page
 * @param {string} groupName The exact group name to add
 * @returns {Promise<string>} The group's data-groupid
 */
export async function addDefaultGroupToEAUForm(page, groupName) {
	const input = page.locator('#entries_are_users_default_groups_user');
	await input.click();
	await input.fill('');
	await input.pressSequentially(groupName);
	// jQuery UI autocomplete renders results into a ul.ui-autocomplete that's
	// appended to body; match the exact text in a list item.
	await page.locator('ul.ui-autocomplete:visible li').filter({ hasText: new RegExp(`^${groupName}$`) }).first().click();
	const item = page.locator('.default-group-item').filter({ hasText: groupName });
	await expect(item).toBeVisible();
	return await item.first().getAttribute('data-groupid');
}

/**
 * Inside the template-group cluster on the EAU form-settings page, check the
 * element-link checkbox for the named element. The element is identified by
 * its visible caption text inside the cluster's checkboxes table.
 *
 * @param {object} page Playwright page
 * @param {string|number} eagFormId The id of the EAG form this cluster belongs to
 * @param {string} elementCaption The visible caption of the element to link
 */
export async function linkTemplateGroupToElement(page, eagFormId, elementCaption) {
	const cluster = page.locator(`.template-group-cluster[data-eagformid="${eagFormId}"]`);
	const label = cluster.locator('label').filter({ hasText: elementCaption });
	await label.locator('input.template-group-element-checkbox').check();
}

/**
 * Show the per-group conditions panel for the given default-group entry.
 * The panel is hidden by default and slides open on click.
 *
 * @param {object} page Playwright page
 * @param {string|number} groupId The data-groupid of the default-group-item
 */
export async function showConditionsPanel(page, groupId) {
	await page.locator(`.toggle-group-conditions-btn[data-groupid="${groupId}"]`).click();
	await expect(page.locator(`.per-group-conditions-panel[data-groupid="${groupId}"]`)).toBeVisible();
}

/**
 * Add a single equality condition to the per-group conditions panel for the
 * given group. Filling the "all of these" row and clicking the panel's
 * `addcon` button triggers a form-save+reload that persists the condition.
 * Caller is responsible for opening the panel first via showConditionsPanel.
 *
 * @param {object} page Playwright page
 * @param {string|number} groupId The data-groupid of the panel
 * @param {string} elementCaption The element caption shown in the select
 * @param {string} op The operator label, e.g. '=' or '!='
 * @param {string} value The value to match
 */
export async function addConditionToPerGroupPanel(page, groupId, elementCaption, op, value) {
	const panel = page.locator(`.per-group-conditions-panel[data-groupid="${groupId}"]`);
	// The element select renders options labelled "FormName: ElementCaption".
	// Use a regex on the caption, with regex metacharacters escaped so that
	// captions like "Is curator?" don't accidentally match a shorter prefix.
	const escapedCaption = elementCaption.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	await panel.locator(`select[name="new_eaugroup_${groupId}_element"]`).selectOption({ label: new RegExp(escapedCaption) });
	await panel.locator(`select[name="new_eaugroup_${groupId}_op"]`).selectOption(op);
	await panel.locator(`input[name="new_eaugroup_${groupId}_term"]`).fill(value);
	// Clicking addcon inside the panel triggers a form save and reload.
	// Wait for the admin-ui opacity dance the same way saveAdminForm does.
	await Promise.all([
		page.waitForFunction(() => {
			const el = document.querySelector('div.admin-ui');
			return el && parseFloat(window.getComputedStyle(el).opacity) < 1.0;
		}, null, { timeout: 120000 }),
		panel.locator('input[name="addcon"]').click()
	]);
	await page.waitForFunction(() => {
		const el = document.querySelector('div.admin-ui');
		return el && parseFloat(window.getComputedStyle(el).opacity) >= 1.0;
	}, null, { timeout: 120000 });
}

/**
 * On users.php or groups.php, read the form id (fid) from the hidden
 * `oldcols` input that carries the current column list. Column handles
 * follow the pattern `..._{fid}` so we extract the trailing integer from
 * the first column.
 *
 * @param {object} page Playwright page
 * @returns {Promise<number>}
 */
export async function getFidFromListPage(page) {
	const oldcols = await page.locator('input[name="oldcols"]').inputValue();
	const firstCol = oldcols.split(',')[0];
	const match = firstCol.match(/_(\d+)$/);
	if (!match) {
		throw new Error(`Could not parse fid from oldcols value: ${oldcols}`);
	}
	return parseInt(match[1], 10);
}

/**
 * After saving a brand-new form, the form-settings page renders with the
 * new fid in the URL (or in a hidden field). Returns it as an integer.
 *
 * @param {object} page Playwright page
 * @returns {Promise<number>}
 */
export async function getFidFromFormAdminPage(page) {
	// Try URL first.
	const url = page.url();
	const urlMatch = url.match(/[?&]fid=(\d+)/);
	if (urlMatch) return parseInt(urlMatch[1], 10);
	// Fallback: read the hidden formulize_admin_key on the form settings panel
	const key = await page.locator('input[name="formulize_admin_key"]').first().inputValue();
	if (/^\d+$/.test(key)) return parseInt(key, 10);
	throw new Error('Could not determine fid for the current admin page');
}
