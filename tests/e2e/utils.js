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
	'captionedContent': 'element-captionedcontent',
	'fullWidthContent': 'element-fullwidthcontent',
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
	'element-captionedcontent': {
		tab: 'Layout',
		heading: 'Element: New element (Captioned Content)'
	},
	'element-fullwidthcontent': {
		tab: 'Layout',
		heading: 'Element: New element (Full Width Content)'
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
	// Note the working message appearing (display:block). Fire-and-forget — NOT awaited,
	// so it doesn't shift the timing of the wait below — but caught so that when the
	// message never appears (e.g. a plain navigation that doesn't run showLoading(); only
	// in-page list actions like search/sort/page/view do) the promise can't reject
	// unhandled with "Test ended" (the original latent bug). Only call this AFTER a list
	// action; for a plain navigation, wait for the "Showing entries" footer instead.
	page.waitForFunction(() => {
		const element = document.getElementById('workingmessage');
		return element && window.getComputedStyle(element).display === 'block';
	}, { timeout: 10000 }).catch(() => {});
	// Wait for it to disappear. A list action submits a full form (a page navigation), so
	// this wait can be torn down mid-navigation ("Target closed") under load — tolerate
	// that and fall back to networkidle so we still wait for the reloaded list to settle.
	await page.waitForFunction(() => {
		const element = document.getElementById('workingmessage');
		return !element || window.getComputedStyle(element).display === 'none';
	}, { timeout: 120000 }).catch(() => {});
	await page.waitForLoadState('networkidle').catch(() => {});
	// Ensure the data submitted error does not occurr
	await expect(page.getByText('Error: the data you submitted')).not.toBeVisible({ timeout: 10000 });
}

/**
 * Submit the "Change columns" popup, applying the boxes ticked in it.
 *
 * The popup's own button hands the column list to the opener and then calls window.self.close()
 * (see updateCols() in modules/formulize/include/changecols.php), so the page being clicked
 * destroys itself as a direct result of the click. Playwright's click is a protocol command that
 * needs a response from the page, and the window can die before that response returns — more
 * often when the machine is busy, which under `--workers=4 --fully-parallel` it is. When that
 * race is lost, click() rejects with "Target page, context or browser has been closed" even
 * though the click landed (the close IS the proof it landed). noWaitAfter narrows the window
 * but cannot eliminate it, so: swallow that rejection whenever the popup did close, and only
 * rethrow if the popup stayed open (a genuine click failure).
 *
 * @param {import('@playwright/test').Page} popup The "Change columns" popup page
 */
export async function applyColumnChanges(popup) {
	const closed = popup.waitForEvent('close');
	closed.catch(() => {}); // never let this reject unhandled if we rethrow below
	try {
		await popup.getByRole('button', { name: 'Change columns' }).click({ noWaitAfter: true });
	} catch (error) {
		if (!popup.isClosed()) throw error;
	}
	await closed; // popup closing is the signal that the column change was submitted
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
	await page.waitForFunction(
		(selector) => {
			const element = document.querySelector(selector);
			if (!element) return false;
			const opacity = parseFloat(window.getComputedStyle(element).opacity);
			return opacity >= 1.0;
		},
		opacityTarget, // Pass the selector as an argument
  	{ timeout }
	);
	// The opacity restore is a ~1ms fadeTo animation, so Playwright's opacity polling
	// can resolve the down/up cycle without ever waiting for the real save — the save
	// then POSTs each admin tab to save.php sequentially (some returning a reload, and
	// screen/template saves run an async PHP syntax-check BEFORE saving), and that
	// trailing work can still be in flight. So wait for the network to go idle before
	// returning, otherwise the save is silently lost when the test proceeds/ends (the
	// root cause of the intermittent menu/filter/label save losses).
	// On normal pages the network is already idle here, so this settles in ~1ms and
	// adds nothing; it only really waits for the saves where the opacity check resolved
	// too fast. The 30s bound is generous enough for long multi-template syntax checks
	// without slowing normal saves; the .catch() handles the rare admin page that has a
	// continuous background poll (the exception) so it can't hang the helper.
	await page.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
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

	let linkClass = 'delete'+accordionType+'link';

	// The page may load with the first accordion already open (e.g., after a delete+reload
	// the first remaining element's accordion opens automatically). If the target is already
	// open, there's nothing to click.
	const isTargetAlreadyOpen = () => page.evaluate(({ text, linkClass }) => {
		const norm = str => str.replace(/\s+/g, '');
		const headers = Array.from(document.querySelectorAll('h3'));
		const h = headers.find(h => norm(h.textContent).includes(norm(text)));
		if (!h) return false;
		const panel = h.nextElementSibling;
		const link = panel ? panel.querySelector('a.' + linkClass) : null;
		return !!(link && link.offsetParent !== null);
	}, { text: accordionHeaderText, linkClass });

	if (await isTargetAlreadyOpen()) return;

	// Wait for any open accordion to reach a stable state before clicking.
	// Accept 0 (all closed) or 1 (one accordion fully open, animation done). If another
	// accordion is open, jQuery UI will close it and open the target when we click.
	await page.waitForFunction(() => {
		const deleteLinks = Array.from(document.querySelectorAll('a'))
			.filter(a => a.textContent.includes('Delete') && a.offsetParent !== null);
		return deleteLinks.length === 0 || deleteLinks.length === 1;
	}, null, { timeout });

	// Re-check in case the target opened while we were waiting
	if (await isTargetAlreadyOpen()) return;

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
 * Robustly select an option from a Formulize / jQuery-UI autocomplete. Types the term (real
 * keystrokes — the admin jQuery-UI 1.8.2 autocomplete searches on keydown, so `.fill()` alone
 * may not trigger it), waits for the matching suggestion, and clicks the EXACT-text <li> (works
 * whether or not the item wraps an <a>: the admin widget has one, the front-end linked-element
 * widget does not). Retries the whole cycle if the suggestion never stabilises — the menu
 * re-renders per keystroke, so on fast CI a click can chase a detaching element and time out
 * (the same race that broke 016 and 005). Matching by exact text makes it safe when the search
 * returns several items (e.g. "hist" → both History Staff users).
 *
 * Callers own their post-select step: wait for an added row, OR — when the next action is a
 * button the lingering menu could overlap — wait for `ul.ui-autocomplete:visible` to be gone
 * and/or click that button with { force: true }.
 *
 * @param {object} page
 * @param {import('@playwright/test').Locator} input  the autocomplete <input> locator
 * @param {string} optionText  exact visible text of the option to select
 * @param {{ searchText?: string }} [opts]  text to type, if different from optionText
 */
export async function selectAutocompleteOption(page, input, optionText, opts = {}) {
	const term = opts.searchText ?? optionText;
	const escaped = optionText.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	const suggestion = page.locator('ul.ui-autocomplete:visible li')
		.filter({ hasText: new RegExp(`^${escaped}$`) }).first();
	let lastErr;
	for (let attempt = 1; attempt <= 3; attempt++) {
		try {
			await input.click();
			await input.fill('');
			await input.pressSequentially(term);
			await expect(suggestion).toBeVisible({ timeout: 12000 });
			// Click the item's <a> if it has one — the admin jQuery-UI autocomplete binds its
			// select handler to the <a>, so clicking the bare <li> doesn't select. The front-end
			// linked-element picker has no <a>, so click the <li> there.
			const anchor = suggestion.locator('a');
			const target = (await anchor.count()) > 0 ? anchor.first() : suggestion;
			await target.click({ timeout: 8000 });
			return;
		} catch (e) {
			lastErr = e;
			await input.fill('').catch(() => {});
			await page.keyboard.press('Escape').catch(() => {});
		}
	}
	throw lastErr;
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
	// Robust select (handles the per-keystroke re-render race that flaked on CI), then verify the
	// group was actually added before returning its id.
	await selectAutocompleteOption(page, page.locator('#entries_are_users_default_groups_user'), groupName);
	const addedItem = page.locator('.default-group-item').filter({ hasText: groupName });
	await expect(addedItem).toBeVisible({ timeout: 10000 });
	return await addedItem.first().getAttribute('data-groupid');
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
	// The container starts as display:none and is shown by JS after page init.
	// Wait for it to become visible before attempting to click the toggle inside it.
	await page.locator('#entries_are_users_default_groups_container').waitFor({ state: 'visible' });
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
	// Ensure admin-ui is fully visible and animated before interacting.
	// The window.load handler sets opacity 0 then animates to 1 over 250 ms,
	// so we must confirm display != none AND opacity == 1 before proceeding.
	await page.waitForFunction(() => {
		const el = document.querySelector('div.admin-ui');
		if (!el) return false;
		const style = window.getComputedStyle(el);
		return style.display !== 'none' && parseFloat(style.opacity) >= 1.0;
	}, null, { timeout: 30000 });

	const panel = page.locator(`.per-group-conditions-panel[data-groupid="${groupId}"]`);
	// The element select renders options labelled "FormName: ElementCaption".
	// Use a regex on the caption, with regex metacharacters escaped so that
	// captions like "Is curator?" don't accidentally match a shorter prefix.
	const escapedCaption = elementCaption.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	// selectOption({ label: RegExp }) is not supported — find the matching option
	// value first, then select by value.
	const elementSelect = panel.locator(`select[name="new_eaugroup_${groupId}_element"]`);
	const optionValue = await elementSelect.evaluate((select, cap) => {
		const re = new RegExp(cap);
		const opt = Array.from(select.options).find(o => re.test(o.text));
		return opt ? opt.value : null;
	}, escapedCaption);
	if (!optionValue) throw new Error(`No option matching /${escapedCaption}/ found in element select for group ${groupId}`);
	await elementSelect.selectOption(optionValue);
	await panel.locator(`select[name="new_eaugroup_${groupId}_op"]`).selectOption(op);
	await panel.locator(`input[name="new_eaugroup_${groupId}_term"]`).fill(value);
	// Clicking addcon triggers: AJAX save to save.php → success handler evaluates
	// reloadWithScrollPosition(url) → scrollposition form submits via POST → full
	// page navigation.  waitForNavigation reliably catches the form-submit navigation
	// regardless of how long the AJAX round-trip takes.
	await Promise.all([
		page.waitForNavigation({ waitUntil: 'networkidle', timeout: 120000 }),
		panel.locator('input[name="addcon"]').click()
	]);
	// After navigation the window.load handler sets admin-ui opacity 0 → 1 (250 ms).
	// Wait until that animation completes before returning.
	await page.waitForFunction(() => {
		const el = document.querySelector('div.admin-ui');
		if (!el) return false;
		const style = window.getComputedStyle(el);
		return style.display !== 'none' && parseFloat(style.opacity) >= 1.0;
	}, null, { timeout: 30000 });
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

/**
 * Robustly set the "show this link to these groups" selection for an application
 * menu entry, working around the Playwright-vs-browser-UI save race.
 *
 * The admin save is AJAX + an opacity animation. Playwright's save click (and
 * saveAdminForm's opacity wait) can complete before the browser has actually
 * persisted the multi-select change, silently dropping some of the chosen groups
 * — especially mid-suite under load. A green save-type test with no post-save
 * assertion is a latent bug. This helper therefore saves, then re-navigates and
 * VERIFIES the selection actually persisted, retrying up to `retries` times.
 *
 * @param {object} page Playwright page
 * @param {string} appName Application name, e.g. 'Museum' (the "Application: <appName>" link)
 * @param {string} accordionName Menu entry accordion header, e.g. 'Donors'
 * @param {string} groupsSelectId id of the groups multiselect, e.g. 'groups1'
 * @param {string[]} groupLabels Option labels to select (e.g. ['Webmasters', 'Departments - All Users']).
 *   Note the menu group list offers regular and template ("Form-based") groups only - entry groups are
 *   auto-managed and inherit their menu permissions from their template group, so select the template.
 * @param {object} [opts] { defaultScreenSelectId?: string, retries?: number }
 */
export async function setMenuEntryGroups(page, appName, accordionName, groupsSelectId, groupLabels, opts = {}) {
	const { defaultScreenSelectId = null, retries = 3 } = opts;

	const gotoEntry = async () => {
		await page.goto('/modules/formulize/admin/ui.php?page=home');
		await page.getByRole('link', { name: 'Application: ' + appName }).click();
		await page.getByRole('link', { name: 'Menu Entries' }).click();
		await openMenuAccordion(page, accordionName);
	};

	const readSelected = async () =>
		page.locator('#' + groupsSelectId).evaluate(sel =>
			Array.from(sel.selectedOptions).map(o => o.label));

	let lastSelected = [];
	for (let attempt = 1; attempt <= retries; attempt++) {
		await gotoEntry();
		await page.locator('#' + groupsSelectId).selectOption(groupLabels);
		if (defaultScreenSelectId) {
			await page.locator('#' + defaultScreenSelectId).selectOption(groupLabels);
		}
		await saveAdminForm(page);

		// Verify by re-navigating and reading back the persisted selection.
		await gotoEntry();
		lastSelected = await readSelected();
		if (groupLabels.every(g => lastSelected.includes(g))) {
			return; // persisted correctly
		}
	}
	throw new Error(
		`setMenuEntryGroups: "${accordionName}" did not persist groups ${JSON.stringify(groupLabels)} ` +
		`after ${retries} attempts; last persisted selection was ${JSON.stringify(lastSelected)}`);
}

export async function clearEntryLocks(page) {
	await page.evaluate(async () => {
		if (typeof window.formulize_clearEntryLocks === 'function') {
			await window.formulize_clearEntryLocks();
		}
	});
}

/**
 * Open the mobile flyout sidebar (which contains #mainmenu), but only if it isn't already
 * open. The Anari theme pre-opens the sidebar (site-layout__sidebar--open) whenever the
 * landing page URL is under /modules/formulize/application.php (theme.html:75-76) --
 * webmasters commonly land there by default, unlike regular staff accounts which land on
 * a specific form. Clicking the "open" trigger while the sidebar is already open hangs
 * forever, because the trigger button ends up covered by the now-open sidebar.
 *
 * The open/closed state is checked via the site-layout__sidebar--open class, not
 * page.locator('#mainmenu').isVisible() -- the sidebar is always technically "visible" by
 * Playwright's definition (non-zero size, not display:none/visibility:hidden) even while
 * closed, since closed is implemented via `transform: translateX(-338px)` (style.css:182-191),
 * which moves it off-screen without changing its bounding box or display. isVisible() can't
 * tell the two states apart, so it silently skips the click when the menu is actually closed
 * off-screen, leaving later clicks inside it (e.g. on a menu item) to fail with "element is
 * outside of the viewport".
 */
export async function ensureMainMenuOpen(page) {
	const isOpen = await page.locator('.js-flyout-menu').evaluate(
		el => el.classList.contains('site-layout__sidebar--open')
	);
	if (!isOpen) {
		await page.locator('#burger-and-logo').getByRole('link').first().click();
	}
}

// ============================================================================
// Direct database access (local Docker environment only)
// ----------------------------------------------------------------------------
// The e2e stack runs against the docker-compose environment (see docker-compose.yaml):
// a MariaDB container reachable by `docker exec`. A few features cannot be exercised
// end-to-end through the browser alone — most importantly self-registration, where the
// confirmation code is normally delivered by email/SMS (which the docker box cannot
// send). These helpers let a spec read that code straight from the database, and toggle
// system settings that have no convenient UI, exactly as a human tester would.
//
// Container name and credentials match docker-compose.yaml; override via env if needed.
// ============================================================================

const { execFileSync } = require('child_process');

const DB_CONTAINER = process.env.E2E_DB_CONTAINER || 'formulize-mariadb-1';
const DB_ROOT_PASS = process.env.E2E_DB_ROOT_PASS || 'abc123';
const DB_NAME = process.env.E2E_DB_NAME || 'formulize';

/**
 * Run a SQL statement against the test database and return the rows as an array of
 * column-value arrays (tab-separated, no header). Values are raw strings.
 * @param {string} sql
 * @returns {string[][]}
 */
export function dbQuery(sql) {
	// -N: skip column-name header. The SQL is passed as a single argv entry, never through a shell:
	// a shell would treat backticks (MySQL identifier quotes, e.g. `groups`) as command substitution.
	const out = execFileSync(
		'docker',
		['exec', DB_CONTAINER, 'mariadb', '-uroot', `-p${DB_ROOT_PASS}`, '-N', '-e', sql, DB_NAME],
		{ encoding: 'utf8' }
	);
	const trimmed = out.replace(/\n$/, '');
	if (trimmed === '') return [];
	return trimmed.split('\n').map(line => line.split('\t'));
}

let _dbPrefix = null;
/**
 * Discover the ImpressCMS table prefix for this install (it is randomized per install,
 * so it must not be hardcoded). Derived from the unique `<prefix>_config` table.
 * @returns {string}
 */
export function dbPrefix() {
	if (_dbPrefix !== null) return _dbPrefix;
	const rows = dbQuery('SHOW TABLES');
	const cfg = rows.map(r => r[0]).find(t => t.endsWith('_config'));
	if (!cfg) throw new Error('Could not determine DB table prefix (no *_config table found)');
	_dbPrefix = cfg.replace(/_config$/, '');
	return _dbPrefix;
}

/**
 * Read the current value of a system config setting (icms_config), e.g. 'allow_register'.
 * @param {string} name
 * @returns {string|null}
 */
export function getSystemConfig(name) {
	const rows = dbQuery(`SELECT conf_value FROM ${dbPrefix()}_config WHERE conf_name = '${name}' LIMIT 1`);
	return rows.length ? rows[0][0] : null;
}

/**
 * Set a system config setting (icms_config), e.g. toggle self-registration on/off.
 * @param {string} name
 * @param {string|number} value
 */
export function setSystemConfig(name, value) {
	dbQuery(`UPDATE ${dbPrefix()}_config SET conf_value = '${value}' WHERE conf_name = '${name}'`);
}

/**
 * Look up a user by login name. Returns { uid, level } or null.
 * @param {string} loginName
 * @returns {{uid:number, level:number}|null}
 */
export function getUserByLogin(loginName) {
	const rows = dbQuery(`SELECT uid, level FROM ${dbPrefix()}_users WHERE login_name = '${loginName}' LIMIT 1`);
	if (!rows.length) return null;
	return { uid: parseInt(rows[0][0], 10), level: parseInt(rows[0][1], 10) };
}

/**
 * Return the group ids a user belongs to.
 * @param {number} uid
 * @returns {number[]}
 */
export function getUserGroupIds(uid) {
	const rows = dbQuery(`SELECT groupid FROM ${dbPrefix()}_groups_users_link WHERE uid = ${parseInt(uid, 10)}`);
	return rows.map(r => parseInt(r[0], 10));
}

/**
 * Read the pending (email/SMS) confirmation code most recently generated for a user.
 * This is the code signup.php would have delivered; reading it here simulates the user
 * receiving it. Only non-app codes (method != TFA_APP=3) are numeric one-time codes.
 * @param {number} uid
 * @returns {string|null}
 */
export function getPendingConfirmationCode(uid) {
	const rows = dbQuery(
		`SELECT code FROM ${dbPrefix()}_tfa_codes WHERE uid = ${parseInt(uid, 10)} AND method != 3 ORDER BY created DESC, code_id DESC LIMIT 1`
	);
	return rows.length ? rows[0][0] : null;
}

/**
 * Create a throwaway user group and return its id. Used to give token tests a group to grant that
 * does not depend on any particular pre-existing groups (so the test runs in any environment).
 * @param {string} name
 * @param {boolean} isGroupTemplate Make it a template group (the kind entries-are-groups forms derive
 *                                  per-entry groups from). No user is ever a direct member of one.
 * @returns {number} the new group id
 */
export function createTestGroup(name, isGroupTemplate = false) {
	const p = dbPrefix();
	const safeName = name.replace(/'/g, "''");
	dbQuery(
		`INSERT INTO ${p}_groups (name, description, group_type, is_group_template) VALUES ('${safeName}', '', 'User', ${isGroupTemplate ? 1 : 0})`
	);
	const rows = dbQuery(`SELECT groupid FROM ${p}_groups WHERE name = '${safeName}' ORDER BY groupid DESC LIMIT 1`);
	return parseInt(rows[0][0], 10);
}

/**
 * Delete a group and any of its user memberships.
 * @param {number} groupId
 */
export function deleteTestGroup(groupId) {
	const p = dbPrefix();
	const id = parseInt(groupId, 10);
	dbQuery(`DELETE FROM ${p}_groups_users_link WHERE groupid = ${id}`);
	dbQuery(`DELETE FROM ${p}_groups WHERE groupid = ${id}`);
}

/**
 * Read a token's current use count (or null if the token no longer exists).
 * @param {string} key
 * @returns {number|null}
 */
export function getTokenUses(key) {
	const rows = dbQuery(`SELECT currentuses FROM ${dbPrefix()}_formulize_tokens WHERE tokenkey = '${key.replace(/[^A-Za-z0-9]/g, '')}' LIMIT 1`);
	return rows.length ? parseInt(rows[0][0], 10) : null;
}

/**
 * Delete a token by its key.
 * @param {string} key
 */
export function deleteToken(key) {
	dbQuery(`DELETE FROM ${dbPrefix()}_formulize_tokens WHERE tokenkey = '${key.replace(/[^A-Za-z0-9]/g, '')}'`);
}
