const { expect, test } = require('@playwright/test')

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
 * A day passing, as far as the site's service worker goes. sw.php may be kept for a day, so a browser only
 * finds a new worker, such as one with a new theme or one that removes itself, once its copy is a day old.
 * Clearing the browser's cache is that copy running out. The next page opened has the browser check again.
 * Chromium only.
 * @param {import('@playwright/test').Page} page
 */
export async function aDayPassesForTheWorker(page) {
	const cdp = await page.context().newCDPSession(page);
	await cdp.send('Network.clearBrowserCache');
	await cdp.detach();
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
	// Ensure the save was not refused
	await expect(page.getByText('Your changes were not saved.')).not.toBeVisible({ timeout: 10000 });
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
	// Ensure the save was not refused
	await expect(page.getByText('Your changes were not saved.')).not.toBeVisible({ timeout: 10000 });
}

/**
 * Open every form's section in the "Change columns" popup, as a person would by clicking each one's
 * heading. The popup shows the columns of each form in a box that opens and closes, and only those
 * with a column already chosen start open (see generateTidyElementList in
 * modules/formulize/include/functions.php). The checkboxes in a closed one can't be clicked.
 *
 * A section's checkboxes are then found by the form's name: popup.getByRole('group', { name: 'Exhibits', exact: true }).
 *
 * Callers usually hand over the popup as soon as it opens, before its sections have loaded, so this
 * waits for the last one to appear. Counting the closed sections any sooner finds none.
 *
 * @param {import('@playwright/test').Page} popup The "Change columns" popup page
 */
export async function openAllColumnSections(popup) {
	await popup.waitForLoadState();
	await popup.locator('details > summary').last().waitFor();
	const closed = popup.locator('details:not([open]) > summary');
	while (await closed.count() > 0) {
		await closed.first().click();
	}
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
	// The opacity is what says the save is finished. The page stays faded until the code check and every
	// tab's POST to save.php have come back (see runSaveEvent() in ui.html), and if the save asked for a
	// reload it stays faded until that reload replaces the page.
	// This wait adds nothing when the save stays on the same page: networkidle fires once per page, and this
	// page has already had it. It counts after a reload, when it lets the new page finish loading. The
	// .catch() is for a page that never goes quiet, which shouldn't stop the test.
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

	// When the page loads, the first drawer slides open. The accordion ignores a click on another heading until it's done, and
	// a drawer that is part way open already has one visible Delete link, so the checks below can't tell it from one that's done.
	await waitForJQueryAnimations(page);

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
	await waitForJQueryAnimations(page);
}

/**
 * Clicks an element accordion item and waits for the jQuery UI accordion animation to complete
 * @param {object} page - Playwright page object
 * @param {string} accordionHeaderText - Text in the header of the accordion we're going to open
 * @param {number} timeout - Maximum time to wait for animation completion (default 2000ms)
 */
/**
 * Set the contents of a CodeMirror-backed code box (any .code-textarea), replacing whatever is in it.
 *
 * Every code box in the admin UI is one of these: templates/admin/ui.html runs CodeMirror.fromTextArea()
 * over each .code-textarea on load, which leaves the real textarea display:none behind an editor widget.
 * Filling that textarea directly therefore waits forever on an element that will never become visible.
 * What CodeMirror does expose is its own input box, inside the wrapper it inserts directly after the
 * textarea it took over - which is what the selector below walks to.
 *
 * The select-all is not optional. CodeMirror applies input by replacing the current SELECTION, so filling
 * a box that already holds something lands the new text ALONGSIDE the old rather than replacing it. On an
 * empty box selecting all is a harmless no-op, so this is right in both cases and there is no second
 * variant to pick between. Pressing on the box also focuses it, so no click is needed first.
 *
 * The wait at the end is doing real work. This CodeMirror (2.34) reads its input box on a timer rather
 * than on the event, and only when it notices does its onChange call save() to copy the text into the
 * textarea that actually gets posted. Returning earlier leaves the caller racing that - a save that lands
 * first posts the OLD content, silently and without failing anything.
 *
 * @param {object} page Playwright page
 * @param {string} boxSelector CSS selecting the box's own (hidden) textarea - the name attribute is the
 *   stable way to say which box, and is the only way to be specific on the pages that have several
 *   (the form procedures tab has four). E.g. 'textarea[name="forms-on_before_save"]'.
 * @param {string} value The text to put in the box
 */
export async function setCodeMirrorValue(page, boxSelector, value) {
	const underlying = page.locator(boxSelector);
	const editor = page.locator(`${boxSelector} + .CodeMirror`);
	// No editor is a genuine failure, not something to work around by filling the raw textarea - but say so
	// here, otherwise it surfaces as a timeout on the input box INSIDE an editor that was never created.
	// ui.html makes one for every .code-textarea on load; a few are made later by their own page's script
	// (the multipage page-settings popup builds its one when it opens), so the panel may need opening first.
	await expect(editor, `no CodeMirror editor next to ${boxSelector}`).toBeVisible({ timeout: 15000 });
	const input = editor.getByRole('textbox');
	await input.press('ControlOrMeta+a'); // focuses the box, and selects what is in it so the fill replaces it
	await input.fill(value);
	await expect(underlying).toHaveValue(value, { timeout: 15000 });
}

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
 * Wait until no jQuery animation is running on the page. Use it after a click that starts a slide or a
 * show (an accordion section opening, a panel growing), before clicking something that the animation moves.
 * Playwright's own stability check looks at one element over a couple of frames, and can pass part way
 * through a slow animation on a busy CI runner.
 * jQuery adds an element to its animation timers as soon as the animation is called, so a wait made right
 * after the click that starts it can't return before it begins.
 * An animation that starts when the page loads, like the first drawer of an accordion sliding open, only
 * begins in a ready handler, so this also waits for jQuery's ready event. Before that there is nothing
 * animating yet, and the wait would pass at once.
 * @param {Page} page - Playwright page object
 */
export async function waitForJQueryAnimations(page) {
	await page.waitForFunction(() => window.jQuery && jQuery.isReady && jQuery(':animated').length === 0);
}

/**
 * Open an application's section on the admin home page. The section slides open, and the forms in it move down
 * while it does, so a click on one of them mid-slide can land between boxes and open nothing. This waits for
 * the slide to finish. Clicking an application that's already open closes it.
 * @param {Page} page - Playwright page object
 * @param {string} appName - The application's name, as it shows after "Application: "
 */
export async function openApplicationAccordion(page, appName) {
	await page.getByRole('link', { name: 'Application: ' + appName }).click();
	await waitForJQueryAnimations(page);
}

/**
 * Open the settings popup of a page of a multi-page screen, from the Pages tab. The page's drawer has to be closed, because
 * clicking an open drawer closes it. A drawer that's closing and one that's opening are both on the page for a moment, so
 * "Edit this page" on its own can match two links. This uses the one in the page's own drawer, so a click that the accordion
 * ignored because it was still moving can't end up editing a different page.
 * @param {import('@playwright/test').Page} page
 * @param {string} title - The title of the page, as it shows on its drawer
 */
export async function editScreenPage(page, title) {
	const drawer = page.locator('[id^="drawer-"]').filter({ has: page.getByRole('link', { name: title, exact: true }) });
	// the first drawer slides open when the page loads, and the accordion ignores a click on another heading until it's done
	await waitForJQueryAnimations(page);
	await drawer.getByRole('link', { name: title, exact: true }).click();
	await waitForJQueryAnimations(page);
	await drawer.getByRole('link', { name: 'Edit this page' }).click();
}

/**
 * Show some groups' permissions on a form's Permissions tab. "Show permissions for these groups" saves the page, which then
 * reloads with those groups' panels, so this waits for the reload, and for each group's panel to be there. The button only
 * works once the script at the end of the page has run, so this waits for the page to be ready first.
 * @param {import('@playwright/test').Page} page
 * @param {string[]} groups - The names of the groups, as the list shows them
 */
export async function showPermissionsFor(page, groups) {
	await waitForAdminPageReady(page);
	await page.locator('#groups').selectOption(groups.map(label => ({ label })));
	await Promise.all([
		page.waitForEvent('load', { timeout: 60000 }),
		page.getByRole('button', { name: 'Show permissions for these' }).click(),
	]);
	await waitForAdminPageReady(page);
	for (const group of groups) {
		await expect(page.getByRole('group', { name: group }).locator('legend')).toBeVisible();
	}
}

/**
 * Waits for a conditional element to appear or be updated after a trigger action
 * Does not work for conditional elements that disappear on a trigger action - will need a different function for that
 * @param {Page} page - Playwright page object
 * @param {string} handle - The element handle of the conditional element, not of the element the action changes
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

	await conditionalChecksFinished(page);
}

/**
 * Wait for conditionalCheckInProgress to be stable at 0, because we might have cascading deferred conditional checks :(
 * conditionalElementReady() does this. Use it directly, after the check has come back, for an action that might leave the
 * conditional element exactly as it was, which conditionalElementReady() can't wait for.
 * @param {Page} page - Playwright page object
 */
export async function conditionalChecksFinished(page) {
	// a page stays open across checks, so the timer from the last check doesn't count for this one
	await page.evaluate(() => { window._stableCheckStart = null; });
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
			// Check if it's been stable for a second
			return Date.now() - window._stableCheckStart > 1000;
		} else {
			// Reset the timer if it goes back to busy
			window._stableCheckStart = null;
			return false;
		}
	}, {}, { timeout: 30000 });

	// Clean up the marker
	await page.evaluate(() => delete window._stableCheckStart);

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

/**
 * Delete an element from a form's Elements tab.
 *
 * Delete no longer raises a native confirm(). It opens the element usage dialog (the markup and
 * the click handlers are in templates/admin/form_elements.html), which fetches a report from
 * the get_element_usage op of formulize_xhr_responder.php saying where the element is used and what deleting it would cost —
 * screens it appears on, other elements that depend on it, references in code — and only offers
 * the button that carries the deletion out once that report has arrived. So a delete is now:
 * open the element's accordion, click Delete, wait for the report, then confirm.
 *
 * The dialog is also reachable from the Usage link beside Delete, which shows the same report
 * with no delete button; this helper only drives the delete path.
 *
 * @param {object} page Playwright page, on a form's Elements tab
 * @param {string} accordionHeaderText Text in the header of the element's accordion, as passed to
 *   openElementAccordion (a leading portion of the header is enough)
 * @param {object} [opts]
 * @param {(string|RegExp)[]} [opts.expectInReport] Text the usage report must contain, asserted
 *   before the deletion is confirmed
 * @param {number} [opts.timeout] How long to allow for the report to arrive and for the element to
 *   disappear afterwards (default 30000ms)
 */
export async function deleteElement(page, accordionHeaderText, opts = {}) {
	const { expectInReport = [], timeout = 30000 } = opts;

	await openElementAccordion(page, accordionHeaderText);
	await page.getByRole('link', { name: 'Delete' }).click();

	const dialog = page.getByRole('dialog', { name: 'Delete this element?' });
	await expect(dialog).toBeVisible();

	// While the report is being fetched the dialog says so and offers only a Close button; the
	// confirm button is added when the report comes back, so waiting for it is what waits for the
	// report. If the fetch fails the dialog says that instead and this is where the test stops.
	const confirmButton = dialog.getByRole('button', { name: 'Delete this element' });
	await expect(confirmButton).toBeVisible({ timeout });

	for (const expected of expectInReport) {
		await expect(dialog).toContainText(expected);
	}

	// Confirming hands off to the admin form's save button, which posts the deletion and reloads the
	// element list — so the element's accordion going away is the signal that the delete went through.
	await confirmButton.click();
	await expect(page.getByRole('link', { name: accordionHeaderText })).not.toBeVisible({ timeout });
	await waitForAdminPageReady(page);
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
 * Determine the fid of the form whose settings panel the admin UI has just saved and re-rendered, by
 * reading the form's HANDLE off the panel and looking that handle up in the database.
 * MUST be called AFTER saveAdminForm() has re-rendered the settings panel (every caller creates a form
 * then calls this straight away), while the test still has that page visible, because the handle is
 * only settled once the save has run, it might be altered by the save routine, and this function needs
 * to read it off the re-rendered panel. The handle is unique, so this is a reliable way to resolve the
 * fid of the form that was just saved.
 *
 * @param {object} page Playwright page sitting on a just-saved form settings panel
 * @returns {Promise<number>}
 * @throws if the handle never appears, no form matches it, or (impossibly) more than one does
 */
export async function getFidFromFormAdminPage(page) {
	const handleInput = page.locator('input[name="forms-form_handle"]');
	await expect(handleInput).toBeVisible();

	// Read the final, deduplicated handle off the re-rendered panel. Poll so we never read an empty box
	// before the panel has settled.
	let handle = '';
	await expect.poll(
		async () => { handle = (await handleInput.inputValue()).trim(); return handle; },
		{ message: 'form handle never appeared on the settings panel after saving', timeout: 15000 }
	).not.toBe('');

	const prefix = dbPrefix();
	let rows = [];
	await expect.poll(
		() => {
			rows = dbQuery(`SELECT id_form FROM ${prefix}_formulize_id WHERE form_handle = '${sqlEscape(handle)}'`);
			return rows.length;
		},
		{ message: `no form with handle "${handle}" appeared in the database after saving`, timeout: 15000 }
	).toBeGreaterThan(0);
	if (rows.length > 1) {
		// Handles are unique by construction, so this should be unreachable - assert it anyway so a
		// surprise duplicate fails loudly here rather than as something unrecognisable downstream.
		throw new Error(
			`Handle "${handle}" matched ${rows.length} forms (ids ${rows.map(r => r[0]).join(', ')}); ` +
			`form handles are supposed to be unique.`
		);
	}
	return parseInt(rows[0][0], 10);
}

/**
 * Create a form inside the Museum application (the shared throwaway-form pattern used by the
 * XSS/handle-rename validate specs), returning its fid.
 *
 * @param {object} page Playwright page
 * @param {string} title Form title
 * @param {string} [piCaption] Caption for the primary identifier field; left unset if omitted
 * @returns {Promise<number>}
 */
export async function createMuseumForm(page, title, piCaption = null) {
	await page.goto('/modules/formulize/admin/ui.php?page=home');
	await waitForAdminPageReady(page);
	await openApplicationAccordion(page, 'Museum');
	await page.getByRole('link', { name: 'Create a new form' }).click();
	await waitForAdminPageReady(page);
	await expect(page.locator('input[name="forms-form_title"]')).toBeVisible();
	await page.getByRole('textbox', { name: 'Form title:' }).fill(title);
	if (piCaption) {
		await page.locator('input[name="pi_new_caption"]').fill(piCaption);
		// typing in the caption ticks "yes" through keyup/change handlers, which fill() does not reliably set off before the save
		await page.locator('#pi-new-yes').check();
	}
	await saveAdminForm(page);
	// Resolve by the form's HANDLE, read back off the re-rendered settings panel - see
	// getFidFromFormAdminPage for why the handle (system-unique, only known post-save) is immune to the
	// same-titled-leftover mis-resolution the old title lookup suffered, and the CI failure that motivated it.
	return await getFidFromFormAdminPage(page);
}

/**
 * Delete a form from the Museum application's form list (open its panel, click Delete, accept the
 * confirm() warning). This also exercises form deletion from the application's form list itself.
 *
 * @param {object} page Playwright page
 * @param {number} fid The form to delete
 * @param {{expectDialogMessage?: string, formTitleText?: string}} [opts]
 *   expectDialogMessage: if given, assert the confirm() dialog's text contains this
 *   formTitleText: if given, assert this text is gone from the page after deletion
 */
export async function deleteMuseumForm(page, fid, opts = {}) {
	const { expectDialogMessage = null, formTitleText = null } = opts;

	await page.goto('/modules/formulize/admin/ui.php?page=home');
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: 'Application: Museum' }).click();
	await waitForAdminPageReady(page);
	// The application's accordion section slides open (jQuery UI animates its height and padding), and the
	// form boxes inside move down while it does. A click on a form's name during the slide can land between
	// boxes and open nothing, so wait for the slide to finish first.
	await waitForJQueryAnimations(page);

	// The Delete link is in the DOM for every form, but it lives inside the form's details panel, which
	// is hidden until the form's box in the listing is clicked (clickFormDetails() in
	// templates/js/formulize-admin-organize-forms.js). Click the form's name to open its panel — the
	// name, not the box itself, because the box's centre may land on the Elements/Screens links inside
	// it, which would navigate away instead of opening the panel.
	const formBox = page.locator(`div.form-listing-box[formid="${fid}"]`);
	await expect(formBox).toBeVisible();
	await formBox.locator('.form-name-text').click();

	// Each form's Delete link carries its fid in the target attribute, so this addresses exactly the
	// form we made and not any other form in the Museum application.
	const deleteLink = page.locator(`a.deleteformlink[target="${fid}"]`);
	await expect(deleteLink).toBeVisible();
	// The panel grows open with an animation too, so let it finish before clicking inside it
	await waitForJQueryAnimations(page);

	// The click raises a confirm() dialog warning that the data will be lost. Playwright dismisses
	// dialogs by default (which would cancel the delete), so accept it explicitly.
	let dialogMessage = null;
	page.once('dialog', async dialog => {
		dialogMessage = dialog.message();
		await dialog.accept();
	});
	await deleteLink.click();

	if (expectDialogMessage) {
		await expect.poll(() => dialogMessage).toContain(expectDialogMessage);
	}

	// The form is gone from the listing. Assert on the form's box, not just its Delete link: the link is
	// hidden until its panel is opened, so a hidden-but-present link would still satisfy toHaveCount(0)
	// for the wrong reason.
	await waitForAdminPageReady(page);
	await expect(formBox).toHaveCount(0);
	if (formTitleText) {
		await expect(page.getByText(formTitleText)).toHaveCount(0);
	}
}

/**
 * Delete every Museum form with one of these titles, in the order given. A spec that builds throwaway
 * forms calls this from afterAll, so its forms are removed even when a test fails part way through, and
 * at the start of its build step, to clear forms that a run which was killed outright left behind.
 * Going by title rather than fid catches a form that was saved before the test could read its fid back.
 * List a form that links to another form before the form it links to.
 *
 * @param {object} page Playwright page, logged in as admin
 * @param {string[]} titles Form titles
 */
export async function deleteMuseumFormsTitled(page, titles) {
	for (const title of titles) {
		for (const row of dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title = '${sqlEscape(title)}'`)) {
			await deleteMuseumForm(page, parseInt(row[0], 10));
		}
	}
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
		await openApplicationAccordion(page, appName);
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

/**
 * An element's row on a form, found by its handle, so a field can be found inside it rather than by counting fields on the page
 * @param {import('@playwright/test').Page} page
 * @param {string} handle - The element's handle
 */
export function elementRow(page, handle) {
	return page.locator(`.form-row:has(.formulize-label-${handle})`);
}

/**
 * The box to type in, of an autocomplete element. Its row also has hidden inputs that hold what was chosen.
 * @param {import('@playwright/test').Page} page
 * @param {string} handle - The element's handle
 */
export function autocompleteBox(page, handle) {
	return elementRow(page, handle).locator('input.formulize_autocomplete');
}

/**
 * Leave the page by going somewhere else, the way a person does, so the entries it locked are released.
 *
 * A form showing an entry for editing locks it, and the page releases the lock as it goes away. So a test that
 * opens an entry must leave it, or the next person to open the entry finds it locked. Closing the browser at the
 * end of a test doesn't count as leaving. The page can only release its locks once it has finished loading, so
 * this waits for that first.
 *
 * The release is a request the page sends as it goes away. Playwright doesn't report it, and it can still be on
 * its way after the next page has loaded, so a test that ends then can close the browser before it's sent. So this
 * waits until the server has handled it, which shows because the file listing what the page locked is gone.
 *
 * In CI a release sometimes never arrives, with the browser still open. Often nothing depends on that lock, so the
 * test carries on, with a note of what's still locked, and entry-lock-reporter.js lists those in the run's summary.
 * A later test that opens one of those entries as someone else finds it read-only.
 *
 * @param {string} url - Where to go. Assert something on that page afterwards, as after any navigation.
 */
export async function leavePage(page, url = '/') {
	await leaveWith(page, () => page.goto(url));
}

/**
 * Reload the page, the way a person does, and wait until the server has handled the release of the entries it
 * locked, as leavePage does. The release arrives after the new page was drawn.
 */
export async function reloadPage(page) {
	await leaveWith(page, () => page.reload());
}

async function leaveWith(page, navigation) {
	await page.waitForLoadState('load');
	const lockLists = await entryLockListFiles(page);
	const leftUrl = page.url();
	await navigation();
	let unreleased = lockLists;
	for (const waited of [0, 100, 250, 500, 1000, 2000, 3000, 3000]) { // ten seconds in all
		await new Promise(resolve => setTimeout(resolve, waited));
		const tempFiles = serverTempFiles();
		unreleased = unreleased.filter(file => tempFiles.includes(file));
		if (unreleased.length === 0) {
			return;
		}
	}
	noteUnreleasedLocks(leftUrl, unreleased);
}

// The annotation entry-lock-reporter.js looks for
const UNRELEASED_LOCKS_NOTE = 'entry locks not released';

// What a list file on the server says a page locked, as [fid, entry] pairs of numbers. Empty if there's no such file,
// which is the case once the page's release has been handled, or if the page locked nothing.
function entriesInLockList(file) {
	let list = '';
	try {
		list = execFileSync('docker', ['exec', webContainer(), 'cat', `/var/www/html/modules/formulize/temp/${file}`], { encoding: 'utf8' });
	} catch (e) {
		return [];
	}
	const entries = [];
	// a serialized PHP array of form ids, each with an array of entry ids
	for (const [, fid, entryList] of list.matchAll(/i:(\d+);a:\d+:\{([^}]*)\}/g)) {
		for (const [, entry] of entryList.matchAll(/i:(\d+);b:1;/g)) {
			entries.push([parseInt(fid, 10), parseInt(entry, 10)]);
		}
	}
	return entries;
}

/**
 * The entries the page's release will unlock when it goes away, as [fid, entry] pairs of numbers. Read from the
 * files on the server that list what the page locked, which is checking what the application did.
 * @param {import('@playwright/test').Page} page
 */
export async function entriesThePageWillRelease(page) {
	return (await entryLockListFiles(page)).flatMap(entriesInLockList);
}

/**
 * Whether an entry is locked for editing, by anyone. Read from its lock file on the server, which is checking what
 * the application did.
 * @param {number} fid - The form
 * @param {number} entry - The entry
 */
export function entryIsLocked(fid, entry) {
	return serverTempFiles().includes(`entry_${entry}_in_form_${fid}_is_locked_for_editing`);
}

// Note on the test what the page that was left still has locked, which is in its list files
function noteUnreleasedLocks(leftUrl, lockLists) {
	const entries = lockLists.flatMap(entriesInLockList).map(([fid, entry]) => `entry ${entry} in form ${fid}`);
	const left = leftUrl.replace(/^https?:\/\/[^/]+/, '');
	test.info().annotations.push({
		type: UNRELEASED_LOCKS_NOTE,
		description: `Left ${left}, and its release never reached the server, so these stay locked: ${entries.join(', ') || 'none any more'}`,
	});
}

// The files on the server that list what a page locked, which the page's release deletes. There's one for the page and
// one for the form last shown in its drawer, named by the token and uid in the code that sends the release. A page
// that locked nothing has the code but no file.
async function entryLockListFiles(page) {
	const html = await page.content();
	const files = [...html.matchAll(/'formulize_entry_lock_token', '([0-9a-f]+)'\);\s*fd\.append\('formulize_entry_lock_uid', '(\d+)'\)/g)]
		.map(([, token, uid]) => `${token}${uid}.token`);
	return [...new Set(files)];
}

function serverTempFiles() {
	return execFileSync('docker', ['exec', webContainer(), 'ls', '/var/www/html/modules/formulize/temp'], { encoding: 'utf8' }).split('\n');
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
// The container is whichever one Compose is running for this checkout (see docker-containers.js);
// the credentials match docker-compose.yaml. Override either via env if needed.
// ============================================================================

const { execFileSync } = require('child_process');
const { dbContainer, webContainer } = require('./docker-containers');

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
		['exec', dbContainer(), 'mariadb', '-uroot', `-p${DB_ROOT_PASS}`, '-N', '-e', sql, DB_NAME],
		{ encoding: 'utf8' }
	);
	const trimmed = out.replace(/\n$/, '');
	if (trimmed === '') return [];
	return trimmed.split('\n').map(line => line.split('\t'));
}

/**
 * Escape a value for use inside a single-quoted SQL string literal. Test-grade, not a general-purpose
 * escaper: it covers the backslash and single quote that would break out of the literal, which is all
 * the fixture data here (form titles, handles) can contain. Do not reach for this to build queries out
 * of arbitrary input - use it for the literals this file already constructs.
 * @param {string} value
 * @returns {string}
 */
export function sqlEscape(value) {
	return String(value).replace(/\\/g, '\\\\').replace(/'/g, "''");
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

// ============================================================================
// Appearance: the site's theme, and the Lyris theme's looks
// ----------------------------------------------------------------------------
// The suite runs on Anari. The specs that test Lyris's looks and the appearance
// editor (formulize-core/04-appearance) run after the setup and validate specs, one
// at a time, and the PHP integrity tests run after them, against the built system.
// So whatever one of those specs changes globally, it puts back in an afterAll,
// which runs whether its tests passed or failed: the site's theme (getSiteTheme /
// restoreSiteTheme), Lyris's site look (getSiteLookKey / setSiteLook), and the looks
// it made, which are all named e2e-something (deleteLooksNamed). The shipped Default
// look is never edited.
// ============================================================================

const SAFE_THEME = /^[A-Za-z0-9_-]+$/;

/**
 * The site's theme as it stands, to put back with restoreSiteTheme(): the theme pages
 * are shown in (theme_set), the theme webmasters are shown instead (theme_admin_set:
 * include/common.php switches a webmaster to it), and each user's own theme, which
 * Admin > Appearance > Settings sets to the site's theme whenever that is changed.
 * @returns {{site: string, admin: string, users: Array<[number, string]>}}
 */
export function getSiteTheme() {
	return {
		site: getSystemConfig('theme_set'),
		admin: getSystemConfig('theme_admin_set'),
		users: dbQuery(`SELECT uid, theme FROM ${dbPrefix()}_users`).map(r => [parseInt(r[0], 10), r[1] === 'NULL' ? '' : r[1]]),
	};
}

/**
 * Show the whole site in a theme, for everyone, webmasters included: what saving
 * Admin > Appearance > Settings with that theme in both of its theme choices does.
 * For the specs' own setup and teardown, where going through the page every time
 * would only add ways to fail; 04-appearance/020-lyris-theme-switch-and-restore goes through it.
 * @param {string} theme a theme's folder name, e.g. 'Lyris'
 */
export function setSiteThemeInDb(theme) {
	if (!SAFE_THEME.test(theme)) throw new Error(`Not a theme name: ${theme}`);
	setSystemConfig('theme_set', theme);
	setSystemConfig('theme_admin_set', theme);
	dbQuery(`UPDATE ${dbPrefix()}_users SET theme = '${theme}'`);
}

/**
 * Put the site's theme back as getSiteTheme() found it.
 * @param {{site: string, admin: string, users: Array<[number, string]>}} state
 */
export function restoreSiteTheme(state) {
	for (const theme of [state.site, state.admin]) {
		if (!SAFE_THEME.test(theme)) throw new Error(`Not a theme name: ${theme}`);
	}
	setSystemConfig('theme_set', state.site);
	setSystemConfig('theme_admin_set', state.admin);
	const byTheme = {};
	for (const [uid, theme] of state.users) {
		(byTheme[theme] = byTheme[theme] || []).push(uid);
	}
	for (const theme of Object.keys(byTheme)) {
		dbQuery(`UPDATE ${dbPrefix()}_users SET theme = '${sqlEscape(theme)}' WHERE uid IN (${byTheme[theme].join(',')})`);
	}
}

export const LYRIS_LOOKS_URL = '/modules/formulize/admin/ui.php?page=appearance&view=looks&theme=Lyris';

/**
 * Open the Looks tab (Admin > Appearance > Looks) on the Lyris theme's looks.
 * @param {object} page Playwright page
 */
export async function openLooksTab(page) {
	await page.goto(LYRIS_LOOKS_URL);
	await expect(page.locator('#looks-create')).toBeAttached();
	await expect(page.locator('table.fz-tree')).toBeVisible();
}

/**
 * A look's row on the Looks tab, found by the look's name.
 * @param {object} page Playwright page
 * @param {string} name the look's name
 */
export function lookRow(page, name) {
	return page.locator('table.fz-tree tbody tr').filter({
		has: page.locator('.formulize-looks__name strong').getByText(name, { exact: true }),
	});
}

/**
 * A look's key (its stylesheet's name, which screens and other looks know it by), read off its row:
 * each row's controls are named after it (looks-<key>-more).
 * @param {object} page Playwright page, on the Looks tab
 * @param {string} name the look's name
 * @returns {Promise<string>}
 */
export async function lookKey(page, name) {
	const target = await lookRow(page, name).locator('button[popovertarget$="-more"]').getAttribute('popovertarget');
	return target.replace(/^looks-/, '').replace(/-more$/, '');
}

/**
 * Make a Lyris look with Create new look on the Looks tab.
 * @param {object} page Playwright page
 * @param {string} name its name
 * @param {{base?: string|null, size?: string}} [opts] base: the key of the look to base it on, or '' for
 *   no base look; left as the dialog has it (Default) when null. size: 'standard', 'compact' or
 *   'comfortable', or '' for the same as its base.
 * @returns {Promise<string>} the new look's key
 */
export async function createLook(page, name, opts = {}) {
	const { base = null, size = '' } = opts;
	await openLooksTab(page);
	await page.getByRole('button', { name: 'Create new look', exact: true }).click();
	const dialog = page.locator('#looks-create');
	await expect(dialog).toBeVisible();
	await dialog.locator('#looks-create-name').fill(name);
	if (base !== null) {
		await dialog.locator('#looks-create-base').selectOption(base);
	}
	if (size) {
		await dialog.locator(`input[name="size"][value="${size}"]`).check();
	}
	await dialog.getByRole('button', { name: 'Create look', exact: true }).click();
	await expect(page.locator('.fz-callout--success')).toHaveText(`Created ${name}.`);
	return lookKey(page, name);
}

/**
 * Open one of a look's dialogs from its row's ⋯ menu (Duplicate, Rename, Change base, Set as site look,
 * Delete, Create look based on this), and return the dialog.
 * @param {object} page Playwright page, on the Looks tab
 * @param {string} name the look's name
 * @param {string} item the menu item's text
 */
export async function openLookMenuItem(page, name, item) {
	const row = lookRow(page, name);
	await row.getByRole('button', { name: `More for ${name}` }).click();
	const menu = row.locator('.fz-menu[id$="-more"]');
	await expect(menu).toBeVisible();
	const button = menu.getByRole('button', { name: item, exact: true });
	const target = await button.getAttribute('commandfor');
	await button.click();
	const dialog = page.locator(`dialog#${target}`);
	await expect(dialog).toBeVisible();
	return dialog;
}

/**
 * The key of the Lyris look the site uses: the row with the Site look badge.
 * @param {object} page Playwright page
 * @returns {Promise<string>}
 */
export async function getSiteLookKey(page) {
	await openLooksTab(page);
	const target = await page.locator('tr.formulize-looks__site button[popovertarget$="-more"]').getAttribute('popovertarget');
	return target.replace(/^looks-/, '').replace(/-more$/, '');
}

/**
 * Make a Lyris look the site look, with Change site look on the Looks tab.
 * @param {object} page Playwright page
 * @param {string} key the look's key
 */
export async function setSiteLook(page, key) {
	if (await getSiteLookKey(page) === key) {
		return;
	}
	await page.locator('#looks-site-button').click();
	const dialog = page.locator('#looks-site');
	await expect(dialog).toBeVisible();
	await dialog.locator(`input[name="look"][value="${key}"]`).check();
	await dialog.getByRole('button', { name: 'Set as site look', exact: true }).click();
	await expect(page.locator('.fz-callout--success')).toContainText('is now the site look');
	expect(await getSiteLookKey(page)).toBe(key);
}

/**
 * Delete every Lyris look whose name starts with a prefix, through the Looks tab, the looks based on
 * another before it, and let go of any screen still set to one (a look deleted while Lyris isn't the
 * site's theme keeps its screens' setting). For cleaning up after a spec: call it in an afterAll, after
 * putting the site look back.
 * @param {object} page Playwright page
 * @param {string} prefix e.g. 'e2e-'
 */
export async function deleteLooksNamed(page, prefix) {
	for (let attempt = 0; attempt < 30; attempt++) {
		await openLooksTab(page);
		// the deepest of them in the tree first: nothing is based on it
		const looks = await page.locator('table.fz-tree tbody tr').evaluateAll((rows, prefix) => rows.map(row => ({
			name: row.querySelector('.formulize-looks__name strong').textContent,
			depth: parseInt(row.querySelector('.fz-tree__cell').style.getPropertyValue('--fz-depth'), 10) || 0,
		})).filter(look => look.name.startsWith(prefix)).sort((a, b) => b.depth - a.depth), prefix);
		if (!looks.length) {
			break;
		}
		const dialog = await openLookMenuItem(page, looks[0].name, 'Delete');
		await dialog.getByRole('button', { name: 'Delete look', exact: true }).click();
		await expect(page.locator('.fz-callout--success')).toHaveText(`Deleted ${looks[0].name}.`);
	}
	dbQuery(`UPDATE ${dbPrefix()}_formulize_screen SET look = '' WHERE look LIKE '${sqlEscape(prefix)}%'`);
}

/**
 * Read a Lyris look's stylesheet, as the site serves it: the record of the look at the top (its name,
 * base and what it changes itself), and the whole file. Null when the look has no stylesheet.
 * @param {object} page Playwright page
 * @param {string} key the look's key
 * @returns {Promise<{record: Object<string, string>, css: string}|null>}
 */
export async function readLookFile(page, key) {
	const response = await page.request.get(`/themes/Lyris/appearance/looks/${key}.css?e2e=${Date.now()}`);
	if (response.status() === 404) {
		return null;
	}
	expect(response.ok()).toBeTruthy();
	const css = await response.text();
	const block = css.split('BEGIN FORMULIZE APPEARANCE LOOK')[1].split('END FORMULIZE APPEARANCE LOOK')[0];
	const record = {};
	for (const line of block.split('\n').slice(1)) {
		const match = line.match(/^\s*\*\s*([a-z_]+):\s?(.*)$/);
		if (match) {
			record[match[1]] = match[2];
		}
	}
	return { record, css };
}

/**
 * Open the appearance editor on a Lyris look, and wait until the preview is showing the look: the editor
 * sets every part's setting on the preview's root element once the preview has loaded.
 * @param {object} page Playwright page
 * @param {string} key the look's key
 * @param {string} [mode] 'simple' or 'advanced'
 */
export async function openAppearanceEditor(page, key, mode = 'simple') {
	await page.goto(`/modules/formulize/appearance_editor.php?theme=Lyris&look=${encodeURIComponent(key)}&mode=${mode}`);
	await expect(page.frameLocator('#formulize-editor-frame').locator('html')).toHaveAttribute('style', /--fz-control-height/);
	await expect(page.locator('#formulize-editor-insp-head h2')).toBeVisible();
}

/**
 * Save the look open in the appearance editor, and wait for the note saying it was saved.
 * @param {object} page Playwright page
 * @param {string} name the look's name
 */
export async function saveAppearanceEditor(page, name) {
	const save = page.locator('#formulize-editor-save');
	await expect(save).toBeEnabled();
	await save.click();
	await expect(page.locator('.formulize-editor__toast[role="status"]')).toContainText(`Saved ${name}.`);
	await expect(save).toHaveText('Save');
	await expect(save).toBeDisabled();
}

/**
 * Rename an element's handle in its settings, the way a webmaster does, and wait until the new handle is stored.
 * When the rename leaves places that still name the old handle, or the handle asked for is one Formulize keeps for
 * itself, the save says so in a dialog, which this reads and closes, the way a person does.
 * The caller must be logged in as a webmaster.
 * @param {import('@playwright/test').Page} page
 * @param {string} oldHandle
 * @param {string} newHandle
 * @param {string} storedHandle - The handle the element ends up with, when it is not the one asked for.
 * @returns {Promise<string|null>} What the dialog said, or null when there was none
 */
export async function renameElementHandle(page, oldHandle, newHandle, storedHandle = newHandle) {
	const [eleId, fid] = dbQuery(`SELECT ele_id, id_form FROM ${dbPrefix()}_formulize WHERE ele_handle = '${sqlEscape(oldHandle)}'`)[0];
	const apps = dbQuery(`SELECT appid FROM ${dbPrefix()}_formulize_application_form_link WHERE fid = ${fid}`);
	await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=${eleId}&fid=${fid}&aid=${apps.length ? apps[0][0] : 0}`);
	await waitForAdminPageReady(page);
	await page.locator('input[name="elements-ele_handle"]').fill(newHandle);
	await saveAdminForm(page);
	await expect.poll(() => dbQuery(`SELECT ele_handle FROM ${dbPrefix()}_formulize WHERE ele_id = ${eleId}`)[0][0], { timeout: 30000 }).toBe(storedHandle);
	// the dialog is opened by the save's response, so it is there by the time the save has finished
	const notice = page.locator('#elementHandleNotice');
	if (await notice.count() === 0) {
		return null;
	}
	await expect(notice).toBeVisible();
	const said = await notice.innerText();
	await page.locator('.ui-dialog').filter({ has: notice }).getByRole('button', { name: 'OK' }).click();
	await expect(notice).toHaveCount(0);
	return said;
}

/**
 * An element's id, by its handle.
 * @param {string} handle
 * @returns {string}
 */
export function elementId(handle) {
	return dbQuery(`SELECT ele_id FROM ${dbPrefix()}_formulize WHERE ele_handle = '${sqlEscape(handle)}'`)[0][0];
}

/**
 * Open an element's page in the admin UI, by its handle, and go to one of its tabs.
 * @param {import('@playwright/test').Page} page
 * @param {string} handle - The element's handle
 * @param {string} tab - The tab's name, ie: 'Options' or 'Display Settings'
 */
export async function openElementSettings(page, handle, tab) {
	const eleId = elementId(handle);
	const fid = dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize WHERE ele_id = ${eleId}`)[0][0];
	const aid = dbQuery(`SELECT appid FROM ${dbPrefix()}_formulize_application_form_link WHERE fid = ${fid}`)[0][0];
	await page.goto(`/modules/formulize/admin/ui.php?page=element&ele_id=${eleId}&fid=${fid}&aid=${aid}`);
	await waitForAdminPageReady(page);
	await page.getByRole('link', { name: tab, exact: true }).click();
}

/**
 * Remove every condition in a filter box, the way a webmaster does, with each condition's X, which saves and reloads the page.
 * @param {import('@playwright/test').Page} page
 * @param {function} open - Goes to the page the box is on, ie: page => openElementSettings(page, handle, 'Display Settings')
 * @param {string} deleteLinks - A selector for the conditions' Xs, ie: '#displayfilter > a.conditionsdelete'
 */
export async function removeConditions(page, open, deleteLinks) {
	await open(page);
	while (await page.locator(deleteLinks).count() > 0) {
		// The click has its own timeout, shorter than the navigation's, so a click that can't land fails with the reason
		// Playwright gives, ie: another element intercepts it, rather than with the navigation that never came
		await Promise.all([
			page.waitForNavigation({ waitUntil: 'networkidle', timeout: 120000 }),
			page.locator(deleteLinks).first().click({ timeout: 30000 }),
		]);
		await open(page);
	}
}

/**
 * Open the Artifact origins map screen (015), found by its handle, and wait for its pins to stop moving.
 * @param {import('@playwright/test').Page} page
 */
export async function openArtifactOriginsMap(page) {
	const sid = dbQuery(`SELECT sid FROM ${dbPrefix()}_formulize_screen WHERE screen_handle = 'artifact_origins'`)[0][0];
	await page.goto(`/modules/formulize/index.php?sid=${sid}`);
	await waitForMapToSettle(page);
}

/**
 * Search the Artifact origins map by short name, the way a person does: type in the box and press Enter, which
 * reloads the screen.
 * @param {import('@playwright/test').Page} page
 * @param {string} term
 */
export async function searchArtifactOriginsMap(page, term) {
	const box = page.locator('input[name^="search_"]').first();
	await box.fill(term);
	await Promise.all([page.waitForEvent('load'), box.press('Enter')]);
	await waitForMapToSettle(page);
}

/**
 * The map fits itself to its pins once the page is shown, which moves every pin, so wait until the first pin stays
 * where it is before anything is clicked.
 * @param {import('@playwright/test').Page} page
 */
export async function waitForMapToSettle(page) {
	await expect(page.locator('.leaflet-container')).toBeVisible();
	const pin = page.locator('img.leaflet-marker-icon').first();
	await expect(pin).toBeVisible();
	let last = null;
	await expect.poll(async () => {
		const box = await pin.boundingBox();
		const where = box ? `${Math.round(box.x)},${Math.round(box.y)}` : '';
		const settled = where !== '' && where === last;
		last = where;
		return settled;
	}, { intervals: [500], timeout: 20000 }).toBe(true);
}

// The artifacts 020 gives a place of origin. Validate specs that copy an artifact add pins of their own, so the
// map is checked for these rather than for a count.
export const ARTIFACTS_WITH_ORIGINS = ['Roman Coin', 'Persian necklace', 'Chinese Sword', 'Egyptian Chariot', 'Babylonian Spoon',
	'Florentine Book', 'French Musket', 'Japanese Coin', 'American Bicycle', 'Polynesian Canoe', 'Viking Silver Armband'];

/**
 * What the Artifact origins map shows: a pin for each of the artifacts 020 gave a place of origin, and the Roman
 * coin's popup with its name and description.
 * @param {import('@playwright/test').Page} page
 */
export async function expectArtifactOriginsMap(page) {
	for (const name of ARTIFACTS_WITH_ORIGINS) {
		await expect(page.locator(`img.leaflet-marker-icon[title="${name}"]`)).toHaveCount(1);
	}
	await page.locator('img.leaflet-marker-icon[title="Roman Coin"]').click();
	await expect(page.locator('.leaflet-popup-content')).toContainText('Roman Coin');
	await expect(page.locator('.leaflet-popup-content')).toContainText('A very lovely Roman Coin.');
}

/**
 * Load one of the current user's saved views from the list's view menu, which reloads the list with it.
 * @param {import('@playwright/test').Page} page
 * @param {string} name
 */
export async function loadSavedView(page, name) {
	await expect(page.getByText('Showing entries: 1 to')).toBeVisible();
	const views = page.locator('select[name="currentview"]');
	const value = await views.locator('option', { hasText: name }).first().getAttribute('value');
	await Promise.all([page.waitForEvent('load'), views.selectOption(value)]);
	await expect(page.locator('select[name="currentview"] option:checked')).toHaveText(new RegExp(name));
}

/**
 * What the "Coins by name" saved view (020) shows: the two coins, by name, with its search in the Short name box.
 * @param {import('@playwright/test').Page} page
 */
export async function expectCoinsByName(page) {
	const rows = page.getByRole('row', { name: 'Check this box to select/unselect this entry.' });
	await expect(rows).toHaveCount(2);
	await expect(rows.nth(0)).toContainText('Japanese Coin');
	await expect(rows.nth(1)).toContainText('Roman Coin');
	await expect(page.locator('input[name^="search_"]').filter({ has: page.locator('xpath=self::*[@value="coin"]') })).toHaveCount(1);
}

/**
 * What the "Artifact origins list" saved view (020) shows: Latitude and Longitude columns, with the Roman coin's
 * place of origin.
 * @param {import('@playwright/test').Page} page
 */
export async function expectArtifactOriginsList(page) {
	await expect(page.getByRole('link', { name: 'Latitude' })).toBeVisible();
	await expect(page.getByRole('link', { name: 'Longitude' })).toBeVisible();
	await expect(page.getByRole('row', { name: /Roman Coin/ })).toContainText('41.9028');
}
