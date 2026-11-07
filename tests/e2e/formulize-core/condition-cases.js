const { expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');
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
	'Appraiser email': 'artifacts_appraiser_email',
	'creation_datetime': 'creation_datetime',
};
export const DONOR_ELEMENTS = {
	'Name': 'donors_name',
	'Type of donor': 'donors_type_of_donor',
	'First name': 'donors_first_name',
	'Last name': 'donors_last_name',
	'Email Address': 'donors_email',
	'Is the donor anonymous?': 'donors_anonymous',
	'Province': 'donors_province',
};
export const COLLECTION_ELEMENTS = {
	'Name': 'collections_name',
	'Suitable audience': 'collections_suitable_audience',
};
export const LOAN_ELEMENTS = {
	'Borrower': 'loans_borrower',
	'Approved': 'loans_approved',
	'Start date': 'loans_start_date',
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

// One cell of conditions, as MCP takes them
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
		return { element: elements[caption], operator: operator[1], value: term === '(empty)' ? '' : term, type };
	});
}

/**
 * The conditions in a row, as MCP takes them
 */
export function conditionsFor(row, elements) {
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
 * Type a row's searches in a list, on a fresh load of it. The boxes have to still hold what was typed afterwards, or a row
 * expecting everything would pass with no search at all.
 * @param {import('@playwright/test').Page} page
 * @param {string} listUrl - Where the list is
 * @param {{handle: string, term: string}[]} searches - From searchesFor
 */
export async function searchList(page, listUrl, searches) {
	await page.goto(listUrl);
	await expect(page.getByText(/Showing entries:/).first()).toBeVisible();
	for (const { handle, term } of searches) {
		await page.locator(`input[name="search_${handle}"]`).fill(term);
	}
	await page.locator(`input[name="search_${searches[searches.length - 1].handle}"]`).press('Enter');
	await waitForWorkingMessage(page);
	await expect(page.getByText(/Showing entries:|No entries were found/).first()).toBeVisible();
	for (const { handle, term } of searches) {
		await expect(page.locator(`input[name="search_${handle}"]`)).toHaveValue(term);
	}
}

/**
 * The search terms in a row, from its Typed in cell, ie: Short name: `ORRoman`<br>Date of acquisition: `ORRoman`
 */
export function searchesFor(row, elements) {
	return row['Typed in'].split('<br>').map(search => {
		const parts = search.match(/^(.+?): `(.*)`$/);
		if (!parts || !elements[parts[1]]) {
			throw new Error(`Could not read the search: ${search}`);
		}
		return { handle: elements[parts[1]], term: parts[2] };
	});
}
