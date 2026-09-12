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
let apiKey = null;

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
	const admin = getUserByLogin('admin');
	expect(admin, 'the admin user should exist').toBeTruthy();
	// 32 hex characters, the same shape formulizeAPIKeyHandler generates. Keys are matched
	// with an alphanumeric-only filter, so nothing else is safe to put in one.
	apiKey = Array.from({ length: 32 }, () => Math.floor(Math.random() * 16).toString(16)).join('');
	dbQuery(
		`INSERT INTO ${dbPrefix()}_formulize_apikeys (uid, apikey, expiry) ` +
		`VALUES (${admin.uid}, '${apiKey}', NULL)`
	);
});

test.afterAll(() => {
	if (apiKey) {
		dbQuery(`DELETE FROM ${dbPrefix()}_formulize_apikeys WHERE apikey = '${apiKey}'`);
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
		const res = await request.post(readUrl(FORM), {
			headers: { 'Authorization': `Bearer ${apiKey}` },
			data: { fields: ['donors_name', 'donors_type_of_donor'], limitSize: 5 }
		});
		expect(res.status()).toBe(200);
		expect(res.headers()['content-type']).toContain('application/json');

		const body = await res.json();
		expect(Array.isArray(body.data)).toBe(true);
		expect(body.meta.form).toBe(FORM);
		expect(body.meta.limitSize).toBe(5);
		expect(body.meta.count).toBe(body.data.length);
		expect(body.data.length).toBeLessThanOrEqual(5);

		// entry_id is always present, whether or not it was asked for, and the requested
		// fields are keyed by their handles.
		for (const row of body.data) {
			expect(row.entry_id).toBeGreaterThan(0);
			expect(row).toHaveProperty('donors_name');
			expect(row).toHaveProperty('donors_type_of_donor');
			// relationship defaults to 0, so nothing connected comes along
			expect(row).not.toHaveProperty('related');
		}
	});

	test('GET with query string parameters works too', async ({ request }) => {
		const res = await request.get(
			`${readUrl(FORM)}&fields=donors_name&limitSize=3`,
			{ headers: { 'Authorization': `Bearer ${apiKey}` } }
		);
		expect(res.status()).toBe(200);
		const body = await res.json();
		expect(Array.isArray(body.data)).toBe(true);
		expect(body.data.length).toBeLessThanOrEqual(3);
	});

	test('filters accept a bare condition, an explicit operator, and a group', async ({ request }) => {
		const read = (filter) => request.post(readUrl(FORM), {
			headers: { 'Authorization': `Bearer ${apiKey}` },
			data: { fields: ['donors_name', 'donors_type_of_donor'], filter, limitSize: null }
		});

		// A bare condition, with the operator defaulting to LIKE
		const bare = await read([{ element: 'donors_type_of_donor', value: 'Individual' }]);
		expect(bare.status()).toBe(200);
		const bareBody = await bare.json();
		for (const row of bareBody.data) {
			expect(row.donors_type_of_donor).toContain('Individual');
		}

		// An explicit operator
		const exact = await read([
			{ element: 'donors_type_of_donor', value: 'Individual', operator: '=' }
		]);
		expect(exact.status()).toBe(200);

		// A group, which is the whole point of supporting nesting at all
		const grouped = await read([
			{ any: [
				{ element: 'donors_type_of_donor', value: 'Individual', operator: '=' },
				{ element: 'donors_type_of_donor', value: 'Organization', operator: '=' }
			] }
		]);
		expect(grouped.status()).toBe(200);
		const groupedBody = await grouped.json();
		// The OR of both types must return at least as many entries as either type alone
		expect(groupedBody.data.length).toBeGreaterThanOrEqual(bareBody.data.length);
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
