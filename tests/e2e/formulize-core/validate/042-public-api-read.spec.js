const { test, expect } = require('@playwright/test');
import { dbQuery, dbPrefix, setSystemConfig, getUserByLogin } from '../../utils';

// These tests share the API key and the enabled preference set up by the first one, and the
// last one deliberately turns the API off and back on again, so they must run in order in the
// same worker. CI runs the validate suite with --workers=4 --fully-parallel, which overrides
// the serial defaults in playwright.config.js.
test.describe.configure({ mode: 'serial' });

// The endpoint is reached through a rewrite rule in production, but index.php reads the same
// apiPath parameter directly, so calling the file works without any rewrite rule at all.
// Github's test environment is unreliable about .htaccess, and the rewrite is not what these
// tests are checking, so they call the file directly and leave pretty URLs to manual testing.
const API = '/modules/formulize/public_api/index.php?apiPath=formulize-public-api/v1';
const readUrl = (form) => `${API}/form/${form}/read`;

const FORM = 'donors';
// The Surveys form carries a per-group filter, set up in test 015: members of All Staff see
// only the entries flagged to the staff. That makes it the one form where the answer depends
// on who is asking, which is what the scope test needs.
const SCOPED_FORM = 'surveys';
let apiKey = null;
// A key for a user who is not a webmaster. Webmasters pass every "has this user permission"
// check by virtue of being webmasters, so a suite that only ever calls as admin exercises
// almost none of the permission layer. This key is what makes the scope test mean something.
let staffApiKey = null;
// Discovered from the database in beforeAll rather than hardcoded, so that the tests that
// need them say where they come from. artifactsForm is the form connected to donors, used
// for the relationship and raw tests; passwordField is the one field that no user at all is
// allowed to read, which is what makes it a real test of the field permission gate.
let artifactsForm = null;
let passwordField = null;

/**
 * POST a read request as the user the API key belongs to.
 *
 * @param {import('@playwright/test').APIRequestContext} request
 * @param {object} body The read parameters
 * @param {string} form The form handle or id to read. Defaults to the donors form.
 */
const apiRead = (request, body, form = FORM) => request.post(readUrl(form), {
	headers: { 'Authorization': `Bearer ${apiKey}` },
	data: body
});

/**
 * Make a random API key, in the shape formulizeAPIKeyHandler generates.
 *
 * 32 hex characters. Keys are matched with an alphanumeric-only filter, so nothing else is
 * safe to put in one.
 *
 * @returns {string}
 */
const makeApiKey = () => Array.from({ length: 32 }, () => Math.floor(Math.random() * 16).toString(16)).join('');

/**
 * Give a user an API key, and return it.
 *
 * @param {string} loginName The login name of the user the key should act as
 * @returns {string} The new key
 */
function createApiKeyFor(loginName) {
	const user = getUserByLogin(loginName);
	expect(user, `the ${loginName} user should exist`).toBeTruthy();
	const key = makeApiKey();
	dbQuery(
		`INSERT INTO ${dbPrefix()}_formulize_apikeys (uid, apikey, expiry) ` +
		`VALUES (${user.uid}, '${key}', NULL)`
	);
	return key;
}

/**
 * Turn the Public API preference on or off directly in the database.
 *
 * Saving it through the admin UI triggers a live curl self-check that requires the rewrite
 * rule to be in place, and force-reverts the setting when it is not. Writing the value
 * straight to the config table keeps these tests independent of that.
 */
function setPublicApi(enabled) {
	setSystemConfig('formulizePublicAPIEnabled', enabled ? 1 : 0);
}

test.beforeAll(() => {
	setPublicApi(true);
	// A key for the admin user. Test 030 already covers creating one through the admin UI;
	// what matters here is that the API accepts it, so this goes straight into the table.
	apiKey = createApiKeyFor('admin');
	// And one for ahstaff, from test 005: Ancient History Staff, and so a member of All Staff,
	// which is the group the Surveys filter applies to. Not a webmaster.
	staffApiKey = createApiKeyFor('ahstaff');

	// The Artifacts form from test 010, which is connected to Donors through its Donor
	// linked-select element and so is part of the Primary Relationship.
	const artifacts = dbQuery(
		`SELECT form_handle FROM ${dbPrefix()}_formulize_id WHERE form_title = 'Artifacts' LIMIT 1`
	);
	artifactsForm = artifacts.length ? artifacts[0][0] : null;

	// Any password element, on whatever entries-are-users form has one. getAllAllowedColHandles
	// removes these for every user, so this is a field that exists, on a form this key can read,
	// and that is still refused.
	const password = dbQuery(
		`SELECT e.ele_handle, f.form_handle FROM ${dbPrefix()}_formulize e ` +
		`INNER JOIN ${dbPrefix()}_formulize_id f ON f.id_form = e.id_form ` +
		`WHERE e.ele_type = 'userAccountPassword' LIMIT 1`
	);
	passwordField = password.length ? { handle: password[0][0], form: password[0][1] } : null;
});

test.afterAll(() => {
	for (const key of [apiKey, staffApiKey]) {
		if (key) {
			dbQuery(`DELETE FROM ${dbPrefix()}_formulize_apikeys WHERE apikey = '${key}'`);
		}
	}
	setPublicApi(true);
});


test.describe('Public API read endpoint', () => {

	test('anonymous is refused on a form the anonymous group cannot view', async ({ request }) => {
		const res = await request.post(readUrl(FORM), {
			data: { fields: ['donors_name'] }
		});
		expect(res.status()).toBe(403);
		const body = await res.json();
		expect(body.error.code).toBe('permission_denied');
	});

	test('an API key returns data in the documented envelope', async ({ request }) => {
		// A limit below the number of seeded donors, so that it has to actually cut the result
		// down. A limit larger than the data would be echoed back in meta and asserted against
		// happily while never reaching the query at all.
		const res = await request.post(readUrl(FORM), {
			headers: { 'Authorization': `Bearer ${apiKey}` },
			data: { fields: ['donors_name', 'donors_type_of_donor'], limitSize: 2 }
		});
		expect(res.status()).toBe(200);
		expect(res.headers()['content-type']).toContain('application/json');

		const body = await res.json();
		expect(Array.isArray(body.data)).toBe(true);
		expect(body.meta.form).toBe(FORM);
		expect(body.meta.limitSize).toBe(2);
		// Both against the number asked for rather than against each other: meta.count is
		// count($rows) and data is $rows, so comparing the two can never fail.
		expect(body.data.length).toBe(2);
		expect(body.meta.count).toBe(2);

		// entry_id is always present, whether or not it was asked for, and the requested
		// fields are keyed by their handles.
		for (const row of body.data) {
			expect(row.entry_id).toBeGreaterThan(0);
			// Non-empty, not merely present: a field that resolved to null for every entry
			// would satisfy toHaveProperty while carrying no data at all.
			expect(typeof row.donors_name).toBe('string');
			expect(row.donors_name.length).toBeGreaterThan(0);
			expect(typeof row.donors_type_of_donor).toBe('string');
			expect(row.donors_type_of_donor.length).toBeGreaterThan(0);
			// relationship defaults to 0, so nothing connected comes along
			expect(row).not.toHaveProperty('related');
		}
	});

	test('the unfiltered read returns every entry the form has', async ({ request }) => {
		// The only assertion in this file measured against the database rather than against
		// another response from the same endpoint. Everything else here is relative, so a
		// regression that quietly narrowed every result equally -- a scope downgrade, a join
		// that dropped rows -- would keep all of those comparisons true. This one notices.
		const res = await apiRead(request, { fields: ['donors_name'], limitSize: null });
		expect(res.status()).toBe(200);
		const body = await res.json();

		const rows = dbQuery(`SELECT entry_id FROM ${dbPrefix()}_formulize_${FORM} ORDER BY entry_id`);
		const idsInTable = rows.map(row => parseInt(row[0], 10));
		expect(idsInTable.length, 'the donors form should have entries in it').toBeGreaterThan(0);
		expect(body.data.map(row => row.entry_id).sort((a, b) => a - b)).toEqual(idsInTable);
	});

	test('GET with query string parameters works too', async ({ request }) => {
		// Two fields in one comma separated parameter, which is the only way a query string can
		// say what a JSON array says in a body, and a limit below the number of seeded donors.
		const res = await request.get(
			`${readUrl(FORM)}&fields=donors_name,donors_type_of_donor&limitSize=3`,
			{ headers: { 'Authorization': `Bearer ${apiKey}` } }
		);
		expect(res.status()).toBe(200);
		const body = await res.json();
		expect(Array.isArray(body.data)).toBe(true);
		expect(body.data.length).toBe(3);
		for (const row of body.data) {
			// Both halves of the comma separated list were read, not just the first
			expect(typeof row.donors_name).toBe('string');
			expect(row.donors_name.length).toBeGreaterThan(0);
			expect(typeof row.donors_type_of_donor).toBe('string');
			expect(row.donors_type_of_donor.length).toBeGreaterThan(0);
		}
	});

	test('filters accept a bare condition, an explicit operator, and a group', async ({ request }) => {
		const read = (filter) => request.post(readUrl(FORM), {
			headers: { 'Authorization': `Bearer ${apiKey}` },
			data: { fields: ['donors_name', 'donors_type_of_donor'], filter, limitSize: null }
		});

		const ids = async (filter) => {
			const res = await read(filter);
			expect(res.status()).toBe(200);
			return (await res.json()).data.map(row => row.entry_id).sort((a, b) => a - b);
		};

		const everything = await ids('');
		expect(everything.length, 'the donors form should have entries in it').toBeGreaterThan(0);

		// A bare condition, with the operator defaulting to LIKE. The value is deliberately a
		// fragment of the stored value rather than the whole of it, so that LIKE and = cannot
		// return the same thing and the default is pinned down.
		const bare = await read([{ element: 'donors_type_of_donor', value: 'Individ' }]);
		expect(bare.status()).toBe(200);
		const bareBody = await bare.json();
		expect(bareBody.data.length, 'the LIKE filter has to select something').toBeGreaterThan(0);
		expect(bareBody.data.length).toBeLessThan(everything.length); // and has to leave something out
		for (const row of bareBody.data) {
			expect(row.donors_type_of_donor).toContain('Individ');
		}
		const bareIds = bareBody.data.map(row => row.entry_id).sort((a, b) => a - b);

		// An explicit operator, which must actually be used: = against the same fragment matches
		// nothing, where the LIKE above matched. An operator that was accepted and then ignored
		// would give the fragment LIKE's answer here.
		expect(await ids([{ element: 'donors_type_of_donor', value: 'Individ', operator: '=' }])).toEqual([]);
		// and = against the whole value finds exactly what the LIKE fragment found
		expect(await ids([{ element: 'donors_type_of_donor', value: 'Individual', operator: '=' }])).toEqual(bareIds);

		// A group, which is the whole point of supporting nesting at all. Its two halves are on
		// different elements and together they select some but not all of the entries, so a
		// group that was dropped and left the query unfiltered fails here.
		const organizationNamed = await ids([{ element: 'donors_name', value: 'Freeform' }]);
		expect(organizationNamed.length).toBeGreaterThan(0);
		const grouped = await ids([
			{ any: [
				{ element: 'donors_type_of_donor', value: 'Individual', operator: '=' },
				{ element: 'donors_name', value: 'Freeform' }
			] }
		]);
		const union = [...new Set([...bareIds, ...organizationNamed])].sort((a, b) => a - b);
		expect(grouped).toEqual(union);
		expect(grouped.length, 'the group must not select everything, or it proves nothing').toBeLessThan(everything.length);
	});

	test('andOr joins the top level items, groups included', async ({ request }) => {
		// Every set here is read back from the endpoint itself rather than assumed from the
		// seeded entries, so the test says what the booleans have to do and nothing about the data.
		const ids = async (filter, andOr) => {
			const data = { fields: ['donors_name'], filter, limitSize: null };
			if (andOr) { data.andOr = andOr; }
			const res = await request.post(readUrl(FORM), {
				headers: { 'Authorization': `Bearer ${apiKey}` },
				data
			});
			expect(res.status()).toBe(200);
			const body = await res.json();
			return new Set(body.data.map(row => row.entry_id));
		};
		const sorted = (ids) => [...ids].sort((a, b) => a - b);

		const bare = [{ element: 'donors_last_name', value: 'a' }];
		const group = [{ any: [
			{ element: 'donors_type_of_donor', value: 'Individual', operator: '=' },
			{ element: 'donors_type_of_donor', value: 'Organization', operator: '=' }
		] }];

		const bareIds = await ids(bare);
		const groupIds = await ids(group);
		const union = new Set([...bareIds, ...groupIds]);
		const intersection = new Set([...bareIds].filter(id => groupIds.has(id)));
		// If the two halves matched the same entries, AND and OR would agree and prove nothing
		expect(union.size, 'the two halves of the filter must select different entries').toBeGreaterThan(intersection.size);

		// A group is one top level item like any other, so andOr decides what joins it to the
		// condition beside it. Forcing AND whenever a group is present would return the
		// intersection for both of these.
		const combined = [...bare, ...group];
		expect(sorted(await ids(combined, 'OR'))).toEqual(sorted(union));
		expect(sorted(await ids(combined, 'AND'))).toEqual(sorted(intersection));
	});

	test('two blank tests are two conditions, not one', async ({ request }) => {
		// A blank test is two comparisons underneath, empty or null. Sharing one expression
		// between two of them would put OR between all four comparisons, quietly turning
		// "both of these are blank" into "either of them is".
		const ids = async (filter, andOr) => {
			const data = { fields: ['donors_name'], filter, limitSize: null };
			if (andOr) { data.andOr = andOr; }
			const res = await request.post(readUrl(FORM), {
				headers: { 'Authorization': `Bearer ${apiKey}` },
				data
			});
			expect(res.status()).toBe(200);
			const body = await res.json();
			return new Set(body.data.map(row => row.entry_id));
		};
		const sorted = (ids) => [...ids].sort((a, b) => a - b);

		const blankOrganization = { element: 'donors_organization_name', value: '{BLANK}', operator: '=' };
		const blankFirstName = { element: 'donors_first_name', value: '{BLANK}', operator: '=' };

		const organizationIds = await ids([blankOrganization]);
		const firstNameIds = await ids([blankFirstName]);
		const union = new Set([...organizationIds, ...firstNameIds]);
		const intersection = new Set([...organizationIds].filter(id => firstNameIds.has(id)));
		expect(union.size, 'the two blank tests must select different entries').toBeGreaterThan(intersection.size);

		// The default AND means both fields blank, and OR means either one
		const both = [blankOrganization, blankFirstName];
		expect(sorted(await ids(both))).toEqual(sorted(intersection));
		expect(sorted(await ids(both, 'OR'))).toEqual(sorted(union));
	});

	test('a nested group is refused rather than quietly flattened', async ({ request }) => {
		const res = await request.post(readUrl(FORM), {
			headers: { 'Authorization': `Bearer ${apiKey}` },
			data: {
				fields: ['donors_name'],
				filter: [ { any: [ { all: [ { element: 'donors_name', value: 'x' } ] } ] } ]
			}
		});
		expect(res.status()).toBe(400);
		const body = await res.json();
		expect(body.error.message).toContain('nested');
	});

	test('a filter cannot reach past the conditions format', async ({ request }) => {
		const read = (filter) => request.post(readUrl(FORM), {
			headers: { 'Authorization': `Bearer ${apiKey}` },
			data: { fields: ['donors_name'], filter }
		});

		// gatherDataset runs a filter string beginning with SELECT as a complete query, which is
		// how the export feature reuses a query it built itself. No caller of this endpoint may
		// get near that, so a filter string is not a thing the endpoint accepts at all.
		const rawSql = await read('SELECT uname, pass FROM ' + dbPrefix() + '_users');
		expect(rawSql.status()).toBe(400);

		// The same refusal for anything else that is not an entry id or a list of conditions
		const legacyString = await read('donors_name/**/x/**/LIKE');
		expect(legacyString.status()).toBe(400);

		// A value carrying the term separators would be read back as extra conditions, on an
		// element that never passed the permission check
		const breakout = await read([
			{ element: 'donors_name', value: 'x][no_such_field_at_all/**/1/**/=' }
		]);
		expect(breakout.status()).toBe(400);

		// Filtering on a field reveals its contents, so it goes through the same gate as fields
		const hiddenField = await read([
			{ element: 'no_such_field_at_all', value: 'x', operator: '=' }
		]);
		expect(hiddenField.status()).toBe(400);
		const hiddenBody = await hiddenField.json();
		expect(hiddenBody.error.message).toContain('no_such_field_at_all');

		// and the valid form of all of that still works
		const ok = await read([{ element: 'donors_name', value: 'a' }]);
		expect(ok.status()).toBe(200);
	});

	test('metadata fields come back alongside element fields', async ({ request }) => {
		const res = await apiRead(request, {
			fields: ['donors_name', 'creation_datetime', 'creation_uid'],
			limitSize: 5
		});
		expect(res.status()).toBe(200);
		const body = await res.json();
		expect(body.data.length).toBeGreaterThan(0);
		for (const row of body.data) {
			// Metadata cannot go through getValue, so it is read straight out of the record
			// instead. Reading it against the wrong form handle or the wrong local entry id
			// would not fail, it would quietly come back null, which is what this catches.
			expect(String(row.creation_datetime)).toMatch(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/);
			expect(String(row.creation_uid)).toMatch(/^\d+$/);
		}

		// entry_id is a complete request on its own. It is skipped in the field loop because
		// the row already carries it, and that must not leave the request with nothing to do.
		const justTheId = await apiRead(request, { fields: ['entry_id'], limitSize: 5 });
		expect(justTheId.status()).toBe(200);
		const justTheIdBody = await justTheId.json();
		expect(justTheIdBody.data.length).toBeGreaterThan(0);
		for (const row of justTheIdBody.data) {
			expect(Object.keys(row)).toEqual(['entry_id']);
		}
	});

	test('sortField and sortOrder order the entries, and limitStart pages through them', async ({ request }) => {
		const idsFor = async (options) => {
			const res = await apiRead(request, Object.assign({ fields: ['donors_name'] }, options));
			expect(res.status()).toBe(200);
			return (await res.json()).data.map(row => row.entry_id);
		};

		// entry_id sorts numerically, so nothing here depends on the database's collation
		const ascending = await idsFor({ sortField: 'entry_id', sortOrder: 'ASC', limitSize: null });
		const descending = await idsFor({ sortField: 'entry_id', sortOrder: 'DESC', limitSize: null });
		expect(ascending.length, 'paging needs at least three entries to say anything').toBeGreaterThanOrEqual(3);
		expect(ascending).toEqual([...ascending].sort((a, b) => a - b));
		expect(descending).toEqual([...ascending].reverse());

		// entry_id is also the default sort field, so everything above would hold just as well if
		// sortField were validated and then never passed to the query. Sorting by an element
		// instead is what proves it arrives: the donors are seeded in an order that does not
		// match their names, so this ordering cannot coincide with the default one.
		const byName = await idsFor({ sortField: 'donors_name', sortOrder: 'ASC', limitSize: null });
		const byNameDescending = await idsFor({ sortField: 'donors_name', sortOrder: 'DESC', limitSize: null });
		// Compared as sets, so this says the ordering changed and nothing was gained or lost.
		// The comparison is deliberately not against a hardcoded order: which name sorts first is
		// the database collation's business, not this endpoint's.
		expect([...byName].sort((a, b) => a - b)).toEqual(ascending);
		expect(byName, 'sorting by name must differ from sorting by entry_id').not.toEqual(ascending);
		expect(byNameDescending).toEqual([...byName].reverse());

		// A window one row further in starts with the row the previous window had second.
		expect(await idsFor({ sortField: 'entry_id', limitStart: 0, limitSize: 2 })).toEqual(ascending.slice(0, 2));
		expect(await idsFor({ sortField: 'entry_id', limitStart: 1, limitSize: 2 })).toEqual(ascending.slice(1, 3));

		// meta reports the window that was used, which is what a caller pages with
		const res = await apiRead(request, { fields: ['donors_name'], limitStart: 1, limitSize: 2 });
		const meta = (await res.json()).meta;
		expect(meta.limitStart).toBe(1);
		expect(meta.limitSize).toBe(2);
	});

	test('a relationship brings connected entries back under related', async ({ request }) => {
		expect(artifactsForm, 'the Artifacts form from test 010 should exist').toBeTruthy();
		const connectedField = artifactsForm + '_short_name';
		const fields = ['donors_name', connectedField];

		// Without a relationship, a connected form's field is not a field of this form at all.
		// That is the same gate a field nobody may read goes through, so it must refuse here too.
		const noRelationship = await apiRead(request, { fields, limitSize: null });
		expect(noRelationship.status()).toBe(400);

		const res = await apiRead(request, { fields, relationship: -1, limitSize: null });
		expect(res.status()).toBe(200);
		const body = await res.json();
		expect(body.data.length).toBeGreaterThan(0);

		let children = 0;
		for (const row of body.data) {
			// A connected form's values live under related, never flattened onto the parent,
			// because one donor can have many artifacts and a flat row could not hold them.
			expect(row).not.toHaveProperty(connectedField);
			if (!row.related) {
				continue; // related is left out entirely rather than sent as an empty object
			}
			expect(Object.keys(row.related).length).toBeGreaterThan(0);
			for (const child of (row.related[artifactsForm] ?? [])) {
				// Each connected entry carries its own entry_id, from its own form
				expect(child.entry_id).toBeGreaterThan(0);
				expect(child).toHaveProperty(connectedField);
				children++;
			}
		}
		expect(children, 'the donors in the test data have artifacts connected to them').toBeGreaterThan(0);

		// A relationship this form is not part of is an error, and says which ones it is in
		const wrongRelationship = await apiRead(request, { fields: ['donors_name'], relationship: 999999 });
		expect(wrongRelationship.status()).toBe(400);
		const wrongBody = await wrongRelationship.json();
		expect(wrongBody.error.message).toContain('999999');
		expect(wrongBody.error.context.valid_relationship_ids_for_form).toContain(-1);
	});

	test('raw returns database values, either throughout or for the fields named', async ({ request }) => {
		expect(artifactsForm, 'the Artifacts form from test 010 should exist').toBeTruthy();
		const donorField = artifactsForm + '_donor';
		const nameField = artifactsForm + '_short_name';

		const rowsWith = async (raw) => {
			const body = { fields: [nameField, donorField], sortField: 'entry_id', limitSize: null };
			if (raw !== undefined) { body.raw = raw; }
			const res = await apiRead(request, body, artifactsForm);
			expect(res.status()).toBe(200);
			return (await res.json()).data;
		};

		const readable = await rowsWith();
		const allRaw = await rowsWith(true);
		const donorOnlyRaw = await rowsWith([donorField]);

		expect(readable.length).toBeGreaterThan(0);
		expect(allRaw.map(row => row.entry_id)).toEqual(readable.map(row => row.entry_id));
		expect(donorOnlyRaw.map(row => row.entry_id)).toEqual(readable.map(row => row.entry_id));

		// The donor field is a linked element, so the database holds the donor's entry id while
		// the readable value is the donor's name. The two cannot be mistaken for each other.
		let compared = 0;
		for (let i = 0; i < readable.length; i++) {
			if (readable[i][donorField] === null || readable[i][donorField] === '') {
				continue;
			}
			compared++;
			expect(String(allRaw[i][donorField])).toMatch(/^\d+$/);
			expect(String(readable[i][donorField])).not.toMatch(/^\d+$/);
			// Naming one field leaves every other field readable, which is the whole reason
			// for accepting a list here rather than only true or false.
			expect(donorOnlyRaw[i][donorField]).toEqual(allRaw[i][donorField]);
			expect(donorOnlyRaw[i][nameField]).toEqual(readable[i][nameField]);
		}
		expect(compared, 'the artifacts in the test data have donors selected').toBeGreaterThan(0);
	});

	test('a linked element filters by its readable value, not its foreign key', async ({ request }) => {
		expect(artifactsForm, 'the Artifacts form from test 010 should exist').toBeTruthy();
		const donorField = artifactsForm + '_donor';

		const all = await apiRead(request, { fields: [donorField], limitSize: null }, artifactsForm);
		expect(all.status()).toBe(200);
		const donorNames = (await all.json()).data.map(row => row[donorField]).filter(Boolean);
		expect(donorNames.length).toBeGreaterThan(0);

		// Prefer a plain name, so that a failure here means the foreign key was not resolved
		// rather than that some accent or entity survived the round trip.
		const aName = donorNames.find(name => /^[A-Za-z0-9 ]+$/.test(name)) ?? donorNames[0];

		const byName = await apiRead(request, {
			fields: [donorField],
			filter: [{ element: donorField, value: aName, operator: '=' }],
			limitSize: null
		}, artifactsForm);
		expect(byName.status()).toBe(200);
		const matched = (await byName.json()).data;

		// The database holds the donor's entry id in this column, so a filter that went
		// straight to SQL would match nothing at all. Every entry showing that donor on
		// screen has to come back, and no others.
		for (const row of matched) {
			expect(row[donorField]).toBe(aName);
		}
		expect(matched.length).toBe(donorNames.filter(name => name === aName).length);
		expect(matched.length).toBeGreaterThan(0);
	});

	test('a field that exists but cannot be read is refused, in fields, filter and sortField', async ({ request }) => {
		// A password element is a real element of a real form that no user may read as a
		// column, not even the webmaster this API key belongs to. That makes it the one field
		// whose refusal cannot be confused with the field simply not existing.
		expect(passwordField, 'test 005 creates an entries-are-users form with a password element').toBeTruthy();
		const { form, handle } = passwordField;

		const asField = await apiRead(request, { fields: [handle], limitSize: 1 }, form);
		expect(asField.status()).toBe(400);
		const asFieldBody = await asField.json();
		expect(asFieldBody.error.code).toBe('unknown_element');
		expect(asFieldBody.error.message).toContain(handle);

		// Filtering on it, or sorting by it, reveals it just as surely as returning it
		const asFilter = await apiRead(request, {
			fields: ['creation_datetime'],
			filter: [{ element: handle, value: 'x' }],
			limitSize: 1
		}, form);
		expect(asFilter.status()).toBe(400);

		const asSort = await apiRead(request, { fields: ['creation_datetime'], sortField: handle, limitSize: 1 }, form);
		expect(asSort.status()).toBe(400);

		// The form itself reads fine, so all three refusals are about the field, not the form
		const ok = await apiRead(request, { fields: ['creation_datetime'], limitSize: 1 }, form);
		expect(ok.status()).toBe(200);
	});

	test('a key for a non-webmaster sees only the entries that user may see', async ({ request }) => {
		// Every other test in this file calls as admin, who is a webmaster and so passes any
		// "does this user have permission" check by virtue of being one. That leaves the part of
		// the endpoint that decides which entries a caller may see almost entirely unexercised,
		// even though it is the whole security story of a public read API: an endpoint that
		// ignored the key's identity, or ran every request with the permissions of whoever
		// happened to be convenient, would pass every other test here.
		//
		// Test 015 gives All Staff a group filter on the Surveys form, so its members see only
		// the entries flagged to the staff. ahstaff is in that group; admin is not.
		const readAs = async (key) => {
			const res = await request.post(readUrl(SCOPED_FORM), {
				headers: { 'Authorization': `Bearer ${key}` },
				data: { fields: ['surveys_your_name'], limitSize: null }
			});
			expect(res.status()).toBe(200);
			return (await res.json()).data.map(row => row.entry_id).sort((a, b) => a - b);
		};

		const asAdmin = await readAs(apiKey);
		const asStaff = await readAs(staffApiKey);

		// What the filter is supposed to select, read from the database rather than from the
		// endpoint under test. A yn element stores 1 for Yes.
		const flaggedRows = dbQuery(
			`SELECT entry_id FROM ${dbPrefix()}_formulize_${SCOPED_FORM} ` +
			`WHERE surveys_flagged_to_the_staff = 1 ORDER BY entry_id`
		);
		const flagged = flaggedRows.map(row => parseInt(row[0], 10));
		expect(flagged.length, 'some surveys should be flagged to the staff').toBeGreaterThan(0);
		expect(flagged.length, 'and some should not, or the filter selects everything and proves nothing')
			.toBeLessThan(asAdmin.length);

		// The staff key sees the flagged entries and nothing else, and the admin key sees more
		expect(asStaff).toEqual(flagged);
		expect(asAdmin.length).toBeGreaterThan(asStaff.length);
	});

	test('a filter can be a single entry id', async ({ request }) => {
		const all = await apiRead(request, { fields: ['donors_name'], limitSize: null });
		expect(all.status()).toBe(200);
		const ids = (await all.json()).data.map(row => row.entry_id);
		expect(ids.length).toBeGreaterThan(1);

		const one = await apiRead(request, { fields: ['donors_name'], filter: ids[1] });
		expect(one.status()).toBe(200);
		const body = await one.json();
		expect(body.data.length).toBe(1);
		expect(body.data[0].entry_id).toBe(ids[1]);
	});

	test('not blank is the exact complement of blank', async ({ request }) => {
		const ids = async (filter) => {
			const res = await apiRead(request, { fields: ['donors_name'], filter, limitSize: null });
			expect(res.status()).toBe(200);
			return (await res.json()).data.map(row => row.entry_id).sort((a, b) => a - b);
		};

		const everything = await ids('');
		const blank = await ids([{ element: 'donors_organization_name', value: '{BLANK}', operator: '=' }]);
		const notBlank = await ids([{ element: 'donors_organization_name', value: '{BLANK}', operator: '!=' }]);

		// Both halves have to select something, or "they do not overlap" is true of nothing
		expect(blank.length).toBeGreaterThan(0);
		expect(notBlank.length).toBeGreaterThan(0);
		expect(blank.filter(id => notBlank.includes(id))).toEqual([]);
		// != {BLANK} is (not empty AND not null), the mirror of (empty OR null). Getting the
		// boolean wrong in either half would leave entries in neither set, or in both.
		expect([...blank, ...notBlank].sort((a, b) => a - b)).toEqual(everything);
	});

	test('a blank test in the wrong kind of group is refused', async ({ request }) => {
		const blank = (operator) => ({ element: 'donors_organization_name', value: '{BLANK}', operator });

		// "is blank" is (empty OR null), so inside an all group its two halves would be ANDed
		// into something that can never be true. Refusing beats answering nothing at all.
		const blankInAll = await apiRead(request, { fields: ['donors_name'], filter: [{ all: [blank('=')] }] });
		expect(blankInAll.status()).toBe(400);
		expect((await blankInAll.json()).error.message).toContain('donors_organization_name');

		// and the mirror image: "is not blank" is (not empty AND not null), so it needs an all group
		const notBlankInAny = await apiRead(request, { fields: ['donors_name'], filter: [{ any: [blank('!=')] }] });
		expect(notBlankInAny.status()).toBe(400);

		// Each one is fine in the group whose own operator already matches
		const blankInAny = await apiRead(request, { fields: ['donors_name'], filter: [{ any: [blank('=')] }] });
		expect(blankInAny.status()).toBe(200);
		const notBlankInAll = await apiRead(request, { fields: ['donors_name'], filter: [{ all: [blank('!=')] }] });
		expect(notBlankInAll.status()).toBe(200);
	});

	test('bad requests report the right status codes', async ({ request }) => {
		// fields is required
		const noFields = await request.post(readUrl(FORM), {
			headers: { 'Authorization': `Bearer ${apiKey}` },
			data: {}
		});
		expect(noFields.status()).toBe(400);

		// a field that does not exist
		const badField = await request.post(readUrl(FORM), {
			headers: { 'Authorization': `Bearer ${apiKey}` },
			data: { fields: ['no_such_field_at_all'] }
		});
		expect(badField.status()).toBe(400);

		// an invalid key is rejected, rather than silently falling back to anonymous
		const badKey = await request.post(readUrl(FORM), {
			headers: { 'Authorization': 'Bearer notarealkey' },
			data: { fields: ['donors_name'] }
		});
		expect(badKey.status()).toBe(401);

		// a form that does not exist
		const noForm = await request.post(readUrl('no_such_form_here'), {
			headers: { 'Authorization': `Bearer ${apiKey}` },
			data: { fields: ['donors_name'] }
		});
		expect(noForm.status()).toBe(404);

		// a method that does not exist on a form
		const noMethod = await request.post(`${API}/form/${FORM}/frobnicate`, {
			headers: { 'Authorization': `Bearer ${apiKey}` },
			data: { fields: ['donors_name'] }
		});
		expect(noMethod.status()).toBe(404);

		// an operator that is not one of the supported ones, rather than passed through
		const badOperator = await apiRead(request, {
			fields: ['donors_name'],
			filter: [{ element: 'donors_name', value: 'x', operator: 'SOUNDS LIKE' }]
		});
		expect(badOperator.status()).toBe(400);

		// paging parameters outside what the endpoint will do. The ceiling on limitSize is
		// what stops one request pulling the whole database into memory, so it has to hold.
		const tooMany = await apiRead(request, { fields: ['donors_name'], limitSize: 10001 });
		expect(tooMany.status()).toBe(400);
		const negativeStart = await apiRead(request, { fields: ['donors_name'], limitStart: -1 });
		expect(negativeStart.status()).toBe(400);

		// an http method that is neither GET nor POST
		const wrongMethod = await request.fetch(readUrl(FORM), {
			method: 'PUT',
			headers: { 'Authorization': `Bearer ${apiKey}` },
			data: { fields: ['donors_name'] }
		});
		expect(wrongMethod.status()).toBe(405);
	});

	test('OPTIONS preflight is answered', async ({ request }) => {
		const res = await request.fetch(readUrl(FORM), { method: 'OPTIONS' });
		expect(res.status()).toBe(204);
	});

	test('the existing status endpoint still behaves as the admin check expects', async ({ request }) => {
		const res = await request.get(`${API}/status`);
		expect(res.status()).toBe(200);
		const body = await res.json();
		// The enable check in libraries/icms/config/item/Handler.php reads exactly this.
		expect(body.status).toBe('healthy');
		expect(body).toHaveProperty('authorization_header_received');
	});

	test('the API returns 503 when the preference is off', async ({ request }) => {
		setPublicApi(false);
		try {
			const res = await request.post(readUrl(FORM), {
				headers: { 'Authorization': `Bearer ${apiKey}` },
				data: { fields: ['donors_name'] }
			});
			expect(res.status()).toBe(503);
		} finally {
			setPublicApi(true);
		}
	});
});
