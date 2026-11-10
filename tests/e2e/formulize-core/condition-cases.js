const { expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const { webContainer } = require('../docker-containers');
import { waitForWorkingMessage } from '../utils';

// Reads conditions-and-searches.md, for the specs that check what it says. That file is the test data: this only knows how
// to read it. It says what each row should show, and where on the page to look.

const CASES_FILE = path.join(__dirname, 'conditions-and-searches.md');

// the caption in a condition, and the element it means. The same caption can mean different elements in different forms
export const ARTIFACT_ELEMENTS = {
	'Short name': 'artifacts_short_name',
	'Year': 'artifacts_year',
	'Era': 'artifacts_era',
	'Date of acquisition': 'artifacts_date_of_acquisition',
	'Donated to museum': 'artifacts_donated_to_museum',
	'Donor': 'artifacts_donor',
	'Collections': 'artifacts_collections',
	'Latitude': 'artifacts_latitude',
	'Materials': 'artifacts_materials',
	'On loan': 'artifacts_on_loan',
	'Provenance': 'artifacts_provenance',
	'Storage': 'artifacts_storage',
	'Appraiser email': 'artifacts_appraiser_email',
	'Location': 'artifacts_location',
	'Handling': 'artifacts_handling',
	'Catalogued by': 'artifacts_catalogued_by',
	'creation_datetime': 'creation_datetime',
	'mod_datetime': 'mod_datetime',
};
export const DONOR_ELEMENTS = {
	'Name': 'donors_name',
	'Type of donor': 'donors_type_of_donor',
	'First name': 'donors_first_name',
	'Last name': 'donors_last_name',
	'Email Address': 'donors_email',
	'Is the donor anonymous?': 'donors_anonymous',
	'Province': 'donors_province',
	'creation_uid': 'creation_uid',
};
export const COLLECTION_ELEMENTS = {
	'Name': 'collections_name',
	'Suitable audience': 'collections_suitable_audience',
};
export const LOAN_ELEMENTS = {
	'Borrower': 'loans_borrower',
	'Approved': 'loans_approved',
	'Start date': 'loans_start_date',
	'Donor contact': 'loans_donor',
};
export const OFFER_ELEMENTS = {
	'Item': 'offers_item',
	'Visit': 'offers_visit',
};

function tableCells(line) {
	return line.split('|').slice(1, -1).map(cell => cell.trim());
}

// Every table in the file with this as the heading of its first column, as objects keyed by the table's headings
function tablesHeaded(firstHeading) {
	const rows = [];
	let headings = null;
	for (const line of fs.readFileSync(CASES_FILE, 'utf8').split('\n')) {
		if (!line.startsWith('|')) {
			headings = null;
			continue;
		}
		const cells = tableCells(line);
		if (cells.every(cell => /^-+$/.test(cell))) {
			continue;
		}
		if (headings === null) {
			headings = cells;
		} else if (headings[0] === firstHeading) {
			rows.push(Object.fromEntries(headings.map((heading, i) => [heading, cells[i]])));
		}
	}
	return rows;
}

/**
 * The rows whose ids start with a letter, as objects keyed by their table's headings, ie: casesFor('S')
 */
export function casesFor(letter) {
	const cases = tablesHeaded('Row').filter(row => new RegExp(`^${letter}\\d+$`).test(row['Row']));
	if (cases.length === 0) {
		throw new Error(`conditions-and-searches.md has no rows for ${letter}`);
	}
	return cases;
}

/**
 * How the rows that start with a letter are checked: the selector for what to read, and the table of names it can hold
 */
export function howToCheck(letter) {
	const howTo = tablesHeaded('Rows').find(row => row['Rows'] === letter);
	if (!howTo) {
		throw new Error(`conditions-and-searches.md does not say how to check the rows for ${letter}`);
	}
	return { selector: howTo['Look at'].replace(/^`|`$/g, ''), names: howTo['Names'] };
}

/**
 * Every name in one of the tables at the end of the file, ie: namesIn('Artifact'). If given a filter, only those rows.
 */
export function namesIn(table, filter = () => true) {
	const names = tablesHeaded(table).filter(filter).map(row => row[table]);
	if (names.length === 0) {
		throw new Error(`conditions-and-searches.md has no names for ${table}`);
	}
	return names;
}

// One cell of conditions, as MCP takes them, with the caption each one was written with
function conditionsIn(cell, type, elements) {
	return cell.split(';').map(condition => condition.trim()).filter(condition => condition !== '').map(condition => {
		const operator = condition.match(/ (NOT LIKE|LIKE|IN|!=|>=|<=|=|>|<)( |$)/);
		if (!operator) {
			throw new Error(`No operator in the condition: ${condition}`);
		}
		const caption = condition.slice(0, operator.index);
		if (!elements[caption]) {
			throw new Error(`No element for the caption "${caption}" in the condition: ${condition}`);
		}
		const term = condition.slice(operator.index + operator[0].length).trim();
		return { caption, element: elements[caption], operator: operator[1], value: term === '(empty)' ? '' : term, type };
	});
}

/**
 * The conditions in a row, as MCP takes them
 */
export function conditionsFor(row, elements) {
	return conditionsWithCaptionsFor(row, elements).map(({ caption, ...condition }) => condition);
}

/**
 * The conditions in a row, with the caption each one was written with, for typing them into the admin UI
 */
export function conditionsWithCaptionsFor(row, elements) {
	return [
		...conditionsIn(row['All of'], 'match-all', elements),
		...conditionsIn(row['Any of'], 'match-one-or-more', elements),
	];
}

/**
 * What a row's Should show cell means, as a sorted list of names. A name that isn't in the set is a mistake in the row.
 */
export function expectedNames(cell, everything) {
	if (cell === 'Nothing') {
		return [];
	}
	if (cell === 'Everything') {
		return [...everything].sort();
	}
	const except = cell.startsWith('Everything except ');
	const named = (except ? cell.slice('Everything except '.length) : cell).split(', ');
	const unknown = named.filter(name => !everything.includes(name));
	if (unknown.length > 0) {
		throw new Error(`Not one of the names that can show here: ${unknown.join(', ')}`);
	}
	return (except ? everything.filter(name => !named.includes(name)) : named).sort();
}

/**
 * The names on the page. Text only counts if it's exactly one of the names that can show there, and a name in several cells
 * counts once, since a list with a connected form can repeat it.
 * @param {import('@playwright/test').Locator} locator - The cells, options or labels to read
 * @param {string[]} everything - The names that can show there
 * @returns {Promise<string[]>} The names found, sorted
 */
export async function namesOnPage(locator, everything) {
	const text = await locator.evaluateAll(found => found.map(item => (item.innerText || item.textContent || '').trim()));
	return [...new Set(text.filter(item => everything.includes(item)))].sort();
}

/**
 * The entries, out of those a row opens, where something is on the page, ie: the artifacts that show Curator note. For sections that
 * look for one thing on each entry, rather than reading names on one page.
 * @param {import('@playwright/test').Page} page
 * @param {string[]} names - The entries to open, by name
 * @param {(name: string) => Promise<void>} open - Opens one of them
 * @param {string} selector - What to look for
 * @returns {Promise<string[]>} The names of the entries it's on, sorted
 */
export async function namesWhereShown(page, names, open, selector) {
	const shown = [];
	for (const name of names) {
		await open(name);
		if (await page.locator(selector).first().isVisible()) {
			shown.push(name);
		}
	}
	return shown.sort();
}

/**
 * How a row disagrees with the page, in words, or null if it doesn't. The match is exact, so something extra counts as much as something missing.
 */
export function disagreementIn(row, expected, shown) {
	const missing = expected.filter(name => !shown.includes(name));
	const extra = shown.filter(name => !expected.includes(name));
	if (missing.length === 0 && extra.length === 0) {
		return null;
	}
	return [
		`${row}:`,
		missing.length > 0 ? `missing ${missing.join(', ')}.` : '',
		extra.length > 0 ? `should not be there: ${extra.join(', ')}.` : '',
	].filter(part => part !== '').join(' ');
}

/**
 * Type or pick a row's searches in a list, on a fresh load of it. The boxes have to still hold what was typed afterwards, or a
 * row expecting everything would pass with no search at all.
 * @param {import('@playwright/test').Page} page
 * @param {() => Promise<void>} openList - Opens the list
 * @param {{handle: string, term: string, picked: boolean}[]} searches - From searchesFor
 */
export async function searchList(page, openList, searches) {
	await openList();
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
	if (searches.some(search => search.picked)) {
		if (searches.length > 1) {
			throw new Error('A search picked from a date range has to be the only one in its row, since its Apply searches the list');
		}
		await pickDateRange(page, searches[0]);
	} else {
		for (const { handle, term } of searches) {
			await page.locator(`input[name="search_${handle}"]`).fill(term);
		}
		await page.locator(`input[name="search_${searches[searches.length - 1].handle}"]`).press('Enter');
	}
	await waitForWorkingMessage(page);
	await expect(page.getByText(/Showing entries:|No entries were found/).first()).toBeVisible();
	for (const { handle, term } of searches) {
		await expect(page.locator(`input[name="search_${handle}"]`)).toHaveValue(term);
	}
}

// The shortcut on each side of a date range that makes each term the search can have there
const DATE_RANGE_SHORTCUTS = { '{TODAY}': 'Today', '': 'Clear' };

/**
 * Pick a search from the date range in a column's search cell, and apply it. The cell has a button that opens the date range
 * in a panel (see filterpopovers.js), and both of its dates start at today. The From and To shortcuts set each side, and the
 * search they make goes into the column's hidden search field, which Apply submits.
 * @param {import('@playwright/test').Page} page
 * @param {{handle: string, term: string}} search - The search the date range has to make, ie: <={TODAY}
 */
async function pickDateRange(page, { handle, term }) {
	const sides = { From: '', To: '' };
	for (const part of term.split('//')) {
		if (part.startsWith('>=')) {
			sides.From = part.slice(2);
		} else if (part.startsWith('<=')) {
			sides.To = part.slice(2);
		} else {
			throw new Error(`A date range can't make the search ${term}`);
		}
	}
	const hidden = page.locator(`input[name="search_${handle}"]`);
	const trigger = page.locator('td[id^="celladdress_1_"]').filter({ has: hidden }).locator('button.formulize-filter-trigger');
	await trigger.click();
	const panel = page.locator(`#${await trigger.getAttribute('popovertarget')}`);
	await expect(panel).toBeVisible();
	for (const [side, value] of Object.entries(sides)) {
		if (!(value in DATE_RANGE_SHORTCUTS)) {
			throw new Error(`The date range has no shortcut for ${value} in ${term}`);
		}
		await panel.getByRole('group', { name: side, exact: true }).getByRole('button', { name: DATE_RANGE_SHORTCUTS[value], exact: true }).click();
	}
	await expect(hidden).toHaveValue(term);
	// the hidden field already holds the search, so the list has to have reloaded with it before the field proves anything
	const reloaded = page.waitForEvent('load');
	await panel.locator('.formulize-filter-panel-apply').click();
	await reloaded;
	// and the date range, which the script draws again on the new page, shows the search in effect, rather than Any date
	await expect(trigger).toBeVisible();
	await expect(trigger).not.toHaveText('Any date');
}

/**
 * The search terms in a row, from its Typed in cell, ie: Short name: `ORRoman`<br>Date of acquisition: `ORRoman`. A column
 * followed by picked is a search picked from its date range, ie: mod_datetime, picked: `<={TODAY}`
 */
export function searchesFor(row, elements) {
	return row['Typed in'].split('<br>').map(search => {
		const parts = search.match(/^(.+?)(, picked)?: `(.*)`$/);
		if (!parts || !elements[parts[1]]) {
			throw new Error(`Could not read the search: ${search}`);
		}
		return { handle: elements[parts[1]], term: parts[3], picked: Boolean(parts[2]) };
	});
}

/**
 * The PHP errors in the log from a moment on, in words. A row that breaks the page often leaves one, and it says where to
 * look. Only uncaught exceptions are logged, so a query that fails isn't here.
 * @param {number} since - Seconds since the epoch, like the log's microtime
 * @returns {string[]}
 */
export function phpErrorsSince(since) {
	let log = '';
	try {
		log = execSync(`docker exec ${webContainer()} tail -n 500 /var/www/html/logs/formulize_log_active.log`, { encoding: 'utf8', timeout: 10000, maxBuffer: 5 * 1024 * 1024 });
	} catch (e) {
		return []; // no log, or no container to read it from, which is no reason to stop the row
	}
	const errors = [];
	for (const line of log.split('\n')) {
		try {
			const entry = JSON.parse(line);
			if (entry.formulize_event === 'PHP-error-recorded' && entry.microtime >= since) {
				errors.push(`${entry.PHP_error_string} (${entry.PHP_error_file}:${entry.PHP_error_errline})`);
			}
		} catch (e) {
			// not a line of JSON
		}
	}
	return [...new Set(errors)];
}

/**
 * Check one row so that a row that can't be checked is recorded, and the rows after it still run. A setting MCP refuses, a
 * page that doesn't finish drawing, or anything else that stops the check becomes a disagreement saying so, and the page goes
 * somewhere neutral for the next row. When a row disagrees for any reason, the PHP errors logged while it ran are added too.
 * Each disagreement is printed as soon as it's found, so the log shows how far a run has got, and a run that's stopped partway
 * still shows what it found. For a row that can't be checked, the whole error is printed too, while its disagreement keeps only
 * the start of it, so the summary stays short. Nothing prints for a row that agrees.
 * @param {import('@playwright/test').Page} page
 * @param {string} rowId - The row, ie: S12
 * @param {string[]} disagreements - Where the row's disagreements go
 * @param {() => Promise<void>} check - Sets up the row and compares, adding to disagreements
 */
export async function checkRow(page, rowId, disagreements, check) {
	const since = Date.now() / 1000;
	const before = disagreements.length;
	let fullMessage = '';
	try {
		await check();
	} catch (error) {
		fullMessage = String(error && error.message ? error.message : error).replace(/\u001b\[[0-9;]*m/g, '');
		const lines = fullMessage.split('\n').map(line => line.trim()).filter(line => line !== '');
		// three lines, since the third says what was waited for, ie: "- waiting for getByRole('button', ...)" after "Call log:"
		disagreements.push(`${rowId}: could not be checked. ${lines.slice(0, 3).join(' ')}`);
		await page.goto('/modules/formulize/index.php').catch(() => {});
	}
	if (disagreements.length > before) {
		for (const error of phpErrorsSince(since)) {
			disagreements.push(`${rowId}: PHP logged ${error}`);
		}
	}
	for (const disagreement of disagreements.slice(before)) {
		console.log(disagreement);
	}
	// the whole error goes in the log, call log and all, since the disagreement keeps only its start
	if (fullMessage) {
		console.log(`${rowId}: the whole error:\n${fullMessage}`);
	}
}
