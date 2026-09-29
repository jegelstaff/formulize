const { test, expect } = require('@playwright/test');
import { dbQuery, dbPrefix, getSystemConfig, setSystemConfig, getUserByLogin, login, deleteMuseumForm } from '../../utils';

// A Subform Interface shows entries from a source form, so the two forms have to be connected. When they
// are not, the create and update subform tools promise to connect them: a linked element is made in the
// source form, pointing at the Principal Identifier of the form the Subform Interface is in, and that
// connection is recorded in the Primary Relationship. These tests hold the tools to that promise.
//
// Both halves have to be checked. The create tool used to skip the whole thing, because a new element has
// no form to read yet, and the update tool made the linked element but never recorded the connection, so
// the Subform Interface had nothing to show. Each tool reported success either way.
//
// The forms made here are deleted through the admin UI at the end, which also makes this the place that
// checks form deletion: that it removes everything attached to a form (permissions, groupscope settings,
// group filters, saved views, advanced calculations, notification settings, permission inheritance), and
// that it leaves everything attached to other forms alone.

// Later tests build on the forms made by earlier ones, so they must run in order, in the same worker.
// CI runs the validate suite with --workers=4 --fully-parallel, which overrides the serial defaults in
// playwright.config.js.
test.describe.configure({ mode: 'serial' });

// index.php routes on the end of the path, and /mcp is the JSON-RPC endpoint
const MCP = '/mcp/index.php/mcp';
// Every form this file makes has a title starting with this, so that forms left behind by an interrupted
// earlier run can be found and removed before starting
const TITLE_PREFIX = 'MCP Subform Test';

let apiKey = null;
let adminUid = null;
let mcpServerWasEnabled = null;
let requestId = 0;
// The forms are made in the Museum application, so that they can be deleted through the admin UI with
// deleteMuseumForm, the same as the other validate specs' throwaway forms
let museumAppId = null;
// Every form made, so afterAll can remove them all, however far the tests got
const createdFids = [];

// Filled in as the tests go
let parentFid = null;
let parentPiId = null;
let childFid = null;
let childElementId = null;
let subformId = null;

/**
 * Call an MCP tool as the admin user.
 *
 * @param {import('@playwright/test').APIRequestContext} request
 * @param {string} name The tool name
 * @param {object} args The tool arguments
 * @returns {Promise<{result: object|null, error: object|null}>} The tool's decoded result, or the error it returned
 */
async function callTool(request, name, args) {
	const res = await request.post(MCP, {
		headers: { 'Authorization': `Bearer ${apiKey}` },
		data: { jsonrpc: '2.0', id: ++requestId, method: 'tools/call', params: { name, arguments: args } }
	});
	const body = await res.json();
	if (body.error) {
		return { result: null, error: body.error };
	}
	return { result: JSON.parse(body.result.content[0].text), error: null };
}

/**
 * Call an MCP tool that is expected to succeed, and return its result.
 */
async function callToolOk(request, name, args) {
	const { result, error } = await callTool(request, name, args);
	expect(error, `${name} should succeed, but returned: ${JSON.stringify(error)}`).toBeNull();
	return result;
}

/**
 * The linked elements in a form that draw their options from a given form. The source is stored in
 * ele_value as "<form id>#*=:*<element handle>", inside a serialized array.
 *
 * @param {number} fid The form the linked elements are in
 * @param {number} sourceFid The form they draw their options from
 * @returns {number[]} The element ids
 */
function linkedElementsIn(fid, sourceFid) {
	return dbQuery(
		`SELECT ele_id FROM ${dbPrefix()}_formulize WHERE id_form = ${fid} ` +
		`AND ele_type = 'selectLinked' AND ele_value LIKE '%"${sourceFid}#*=:*%'`
	).map(row => parseInt(row[0], 10));
}

/**
 * The Primary Relationship links between two forms, in either direction.
 *
 * @returns {{form1: number, form2: number, key1: number, key2: number, rel: number}[]}
 */
function primaryRelationshipLinks(fidA, fidB) {
	return dbQuery(
		`SELECT fl_form1_id, fl_form2_id, fl_key1, fl_key2, fl_relationship FROM ${dbPrefix()}_formulize_framework_links ` +
		`WHERE fl_frame_id = -1 AND ((fl_form1_id = ${fidA} AND fl_form2_id = ${fidB}) OR (fl_form1_id = ${fidB} AND fl_form2_id = ${fidA}))`
	).map(row => ({
		form1: parseInt(row[0], 10),
		form2: parseInt(row[1], 10),
		key1: parseInt(row[2], 10),
		key2: parseInt(row[3], 10),
		rel: parseInt(row[4], 10)
	}));
}

/**
 * Make a form with one text box in it, and return the ids of both.
 *
 * @param {boolean} pi Whether to ask for the text box to be the form's Principal Identifier. Asking makes no
 * difference here, since the first element with data in a form becomes its Principal Identifier regardless.
 */
async function makeFormWithTextBox(request, title, pi) {
	const form = await callToolOk(request, 'create_form', { title: `${TITLE_PREFIX} ${title}`, application_id_or_name: museumAppId });
	const fid = parseInt(form.form_id, 10);
	createdFids.push(fid);
	const element = await callToolOk(request, 'create_text_box_element', {
		form_id: fid,
		type: 'text',
		caption: 'Name',
		properties: {},
		principal_identifier: pi
	});
	return { fid, elementId: parseInt(element.element_id, 10) };
}

/**
 * The ids of the forms whose titles mark them as made by this file.
 */
function formsMadeByThisFile() {
	return dbQuery(`SELECT id_form FROM ${dbPrefix()}_formulize_id WHERE form_title LIKE '${TITLE_PREFIX}%'`)
		.map(row => parseInt(row[0], 10));
}

/**
 * Everything attached to a form that deleting the form has to remove, by kind, as lists of row ids. Each kind
 * is identified the way formulizeFormsHandler::delete() identifies it. Saved views are the subtle one: a view
 * saved with a relationship holds the relationship id in sv_formframe and the form in sv_mainform, so
 * sv_formframe only names the form when sv_mainform is blank (see getFormFramework()).
 *
 * @param {number} fid
 * @returns {Object<string, string[]>}
 */
function rowsAttachedToForm(fid) {
	const prefix = dbPrefix();
	const ids = (sql) => dbQuery(sql).map(row => row[0]);
	return {
		permissions: ids(
			`SELECT gperm_id FROM ${prefix}_group_permission WHERE gperm_itemid = ${fid} ` +
			`AND gperm_modid = (SELECT mid FROM ${prefix}_modules WHERE dirname = 'formulize')`
		),
		groupscopeSettings: ids(`SELECT groupscope_id FROM ${prefix}_formulize_groupscope_settings WHERE fid = ${fid}`),
		groupFilters: ids(`SELECT filterid FROM ${prefix}_formulize_group_filters WHERE fid = ${fid}`),
		savedViews: ids(
			`SELECT sv_id FROM ${prefix}_formulize_saved_views ` +
			`WHERE sv_mainform = ${fid} OR ((sv_mainform IS NULL OR sv_mainform = 0) AND sv_formframe = ${fid})`
		),
		advancedCalculations: ids(`SELECT acid FROM ${prefix}_formulize_advanced_calculations WHERE fid = ${fid}`),
		notificationSettings: ids(`SELECT not_cons_id FROM ${prefix}_formulize_notification_conditions WHERE not_cons_fid = ${fid}`)
	};
}

/**
 * Give a form one of each kind of row that rowsAttachedToForm() looks for, except permissions, which
 * create_form already granted. Straight into the database, so that what is being tested is the deletion,
 * not the various admin screens that would otherwise make these.
 *
 * Two saved views: a plain one, and one saved through a relationship whose id is otherFid. On the form that
 * survives, that second view has the deleted form's id in sv_formframe, so a deletion that matched
 * sv_formframe alone would wrongly take it.
 *
 * @param {number} fid The form to attach the rows to
 * @param {number} otherFid The other form in the test, used as the relationship id of the second saved view
 */
function seedRowsForForm(fid, otherFid) {
	const prefix = dbPrefix();
	// Registered Users (2) can see the entries of Webmasters (1), and has a filter on which entries it sees
	dbQuery(`INSERT INTO ${prefix}_formulize_groupscope_settings (groupid, fid, view_groupid) VALUES (2, ${fid}, 1)`);
	dbQuery(`INSERT INTO ${prefix}_formulize_group_filters (fid, groupid, filter) VALUES (${fid}, 2, '')`);
	dbQuery(
		`INSERT INTO ${prefix}_formulize_saved_views (sv_name, sv_owner_uid, sv_formframe, sv_mainform) ` +
		`VALUES ('${TITLE_PREFIX} view', ${adminUid}, ${fid}, 0), ('${TITLE_PREFIX} relationship view', ${adminUid}, ${otherFid}, ${fid})`
	);
	dbQuery(
		`INSERT INTO ${prefix}_formulize_advanced_calculations (fid, name, description, input, output, steps, steptitles, fltr_grps, fltr_grptitles) ` +
		`VALUES (${fid}, '${TITLE_PREFIX} calculation', '', '', '', '', '', '', '')`
	);
	dbQuery(
		`INSERT INTO ${prefix}_formulize_notification_conditions (not_cons_fid, not_cons_event, not_cons_uid, not_cons_con, not_cons_subject) ` +
		`VALUES (${fid}, 'new_entry', ${adminUid}, '', '${TITLE_PREFIX} notification')`
	);
}

/**
 * Delete forms through the admin UI. Deleting a form takes its elements, screens, menu links, data table and
 * Primary Relationship links with it, so this removes everything these tests made, including the linked
 * elements the tools put in the source forms, since every source form here is one of these forms too.
 * Forms that no longer exist are skipped, since the last test deletes one of them itself.
 */
async function deleteForms(browser, fids) {
	const existing = new Set(formsMadeByThisFile());
	fids = fids.filter(fid => existing.has(fid));
	if (!fids.length) { return; }
	const page = await browser.newPage();
	try {
		await login(page, 'admin');
		for (const fid of fids) {
			await deleteMuseumForm(page, fid);
		}
	} finally {
		await page.close();
	}
}

test.beforeAll(async ({ browser }) => {
	const museum = dbQuery(`SELECT appid FROM ${dbPrefix()}_formulize_applications WHERE name = 'Museum' LIMIT 1`);
	expect(museum.length, 'the Museum application from the setup tests should exist').toBe(1);
	museumAppId = parseInt(museum[0][0], 10);

	// A run that was interrupted before its afterAll could leave forms behind
	await deleteForms(browser, formsMadeByThisFile());

	// Straight into the database, as test 042 does for the Public API. Test 030 covers turning the MCP
	// server on through the admin UI; this file only needs it on.
	mcpServerWasEnabled = getSystemConfig('formulizeMCPServerEnabled');
	setSystemConfig('formulizeMCPServerEnabled', 1);
	const admin = getUserByLogin('admin');
	expect(admin, 'the admin user should exist').toBeTruthy();
	adminUid = admin.uid;
	apiKey = Array.from({ length: 32 }, () => Math.floor(Math.random() * 16).toString(16)).join('');
	dbQuery(`INSERT INTO ${dbPrefix()}_formulize_apikeys (uid, apikey, expiry) VALUES (${admin.uid}, '${apiKey}', NULL)`);
});

// afterAll rather than a trailing test(), because serial mode skips every remaining test after a failure,
// and that would leave the forms behind. afterAll runs either way.
test.afterAll(async ({ browser }) => {
	try {
		await deleteForms(browser, createdFids);
		// Nothing these tests made is left: no forms, no elements in them, no connections to them, and nothing
		// attached to them (create_form grants permissions, and the last test seeds everything else)
		if (createdFids.length) {
			const fids = createdFids.join(', ');
			const prefix = dbPrefix();
			expect(dbQuery(`SELECT id_form FROM ${prefix}_formulize_id WHERE id_form IN (${fids})`)).toEqual([]);
			expect(dbQuery(`SELECT ele_id FROM ${prefix}_formulize WHERE id_form IN (${fids})`)).toEqual([]);
			expect(dbQuery(
				`SELECT fl_id FROM ${prefix}_formulize_framework_links WHERE fl_form1_id IN (${fids}) OR fl_form2_id IN (${fids})`
			)).toEqual([]);
			for (const fid of createdFids) {
				expect(Object.values(rowsAttachedToForm(fid)).flat(), `rows still attached to deleted form ${fid}`).toEqual([]);
			}
		}
	} finally {
		// If deletion missed anything, the check above has already reported it. Clear it out regardless, so
		// that a regression caught here does not leave rows behind in the database for good.
		if (createdFids.length) {
			const fids = createdFids.join(', ');
			const prefix = dbPrefix();
			dbQuery(
				`DELETE FROM ${prefix}_group_permission WHERE gperm_itemid IN (${fids}) ` +
				`AND gperm_modid = (SELECT mid FROM ${prefix}_modules WHERE dirname = 'formulize')`
			);
			dbQuery(`DELETE FROM ${prefix}_formulize_groupscope_settings WHERE fid IN (${fids})`);
			dbQuery(`DELETE FROM ${prefix}_formulize_group_filters WHERE fid IN (${fids})`);
			dbQuery(`DELETE FROM ${prefix}_formulize_saved_views WHERE sv_name LIKE '${TITLE_PREFIX}%'`);
			dbQuery(`DELETE FROM ${prefix}_formulize_advanced_calculations WHERE fid IN (${fids})`);
			dbQuery(`DELETE FROM ${prefix}_formulize_notification_conditions WHERE not_cons_fid IN (${fids})`);
		}
		if (apiKey) {
			dbQuery(`DELETE FROM ${dbPrefix()}_formulize_apikeys WHERE apikey = '${apiKey}'`);
		}
		if (mcpServerWasEnabled !== null) {
			setSystemConfig('formulizeMCPServerEnabled', mcpServerWasEnabled);
		}
	}
});

test.describe('MCP subform tools connect the forms they join', () => {

	test('create_subform_interface connects a source form that was not connected', async ({ request }) => {
		const parent = await makeFormWithTextBox(request, 'Parent', true);
		const child = await makeFormWithTextBox(request, 'Child', false);
		parentFid = parent.fid;
		parentPiId = parent.elementId;
		childFid = child.fid;
		childElementId = child.elementId;

		// create_form grants permissions on the forms it makes. afterAll checks that deleting the forms removes
		// them, which only means something if there were some to remove.
		expect(dbQuery(
			`SELECT gperm_id FROM ${dbPrefix()}_group_permission WHERE gperm_itemid = ${parentFid} ` +
			`AND gperm_modid = (SELECT mid FROM ${dbPrefix()}_modules WHERE dirname = 'formulize')`
		).length).toBeGreaterThan(0);

		// Nothing joins the two forms yet, or this test would prove nothing
		expect(linkedElementsIn(childFid, parentFid)).toEqual([]);
		expect(primaryRelationshipLinks(parentFid, childFid)).toEqual([]);

		const subform = await callToolOk(request, 'create_subform_interface', {
			form_id: parentFid,
			type: 'subformListings',
			caption: 'Children',
			properties: { sourceForm: childFid, elementsInRow: [childElementId] }
		});
		subformId = parseInt(subform.element_id, 10);

		// A linked element in the child form, pointing at the parent's Principal Identifier...
		const linked = linkedElementsIn(childFid, parentFid);
		expect(linked.length, 'one linked element should be made in the source form').toBe(1);

		// ...and the connection recorded, with the parent as the "one" side. Without this row the
		// Subform Interface has no way to find the child entries, so the linked element alone is not enough.
		expect(primaryRelationshipLinks(parentFid, childFid)).toEqual([
			{ form1: parentFid, form2: childFid, key1: parentPiId, key2: linked[0], rel: 2 }
		]);
	});

	test('a second subform between the same forms reuses the connection', async ({ request }) => {
		expect(subformId, 'the previous test should have made the forms').toBeTruthy();

		// The existing connection has to be found, not duplicated. On create the check has only the form
		// id passed in to go on, since the new element does not exist yet.
		await callToolOk(request, 'create_subform_interface', {
			form_id: parentFid,
			type: 'subformEditableRow',
			caption: 'Children again',
			properties: { sourceForm: childFid, elementsInRow: [childElementId] }
		});
		expect(linkedElementsIn(childFid, parentFid).length).toBe(1);
		expect(primaryRelationshipLinks(parentFid, childFid).length).toBe(1);
	});

	test('update_subform_interface connects a new source form', async ({ request }) => {
		expect(subformId, 'the first test should have made the subform').toBeTruthy();
		const other = await makeFormWithTextBox(request, 'Other Child', false);
		expect(primaryRelationshipLinks(parentFid, other.fid)).toEqual([]);

		await callToolOk(request, 'update_subform_interface', {
			element_identifier: subformId,
			properties: { sourceForm: other.fid, elementsInRow: [other.elementId] }
		});

		const linked = linkedElementsIn(other.fid, parentFid);
		expect(linked.length, 'one linked element should be made in the new source form').toBe(1);
		expect(primaryRelationshipLinks(parentFid, other.fid)).toEqual([
			{ form1: parentFid, form2: other.fid, key1: parentPiId, key2: linked[0], rel: 2 }
		]);
	});

	test('a form with no Principal Identifier is refused, and nothing is left behind', async ({ request }) => {
		// With no Principal Identifier there is nothing for a linked element to point at, so the
		// connection cannot be made. The tool has to say so, rather than make a Subform Interface
		// that can never show anything.
		const noPi = await makeFormWithTextBox(request, 'No PI', false);
		const child = await makeFormWithTextBox(request, 'No PI Child', false);
		// Its text box became its Principal Identifier automatically, as the first element with data in the
		// form, so clear it. 0 is the only value update_form takes as "clear".
		await callToolOk(request, 'update_form', { form_id: noPi.fid, principal_identifier: 0 });
		expect(
			dbQuery(`SELECT pi FROM ${dbPrefix()}_formulize_id WHERE id_form = ${noPi.fid}`),
			'the form should have no Principal Identifier, or this test proves nothing'
		).toEqual([['0']]);
		const elementCountBefore = dbQuery(`SELECT COUNT(*) FROM ${dbPrefix()}_formulize WHERE id_form IN (${noPi.fid}, ${child.fid})`)[0][0];

		const { error } = await callTool(request, 'create_subform_interface', {
			form_id: noPi.fid,
			type: 'subformListings',
			caption: 'Children',
			properties: { sourceForm: child.fid, elementsInRow: [child.elementId] }
		});
		expect(error, 'the tool should refuse').not.toBeNull();
		expect(error.message).toContain('Principal Identifier');

		// Refused before anything was saved: no Subform Interface in the one form, no linked element in the other
		const elementCountAfter = dbQuery(`SELECT COUNT(*) FROM ${dbPrefix()}_formulize WHERE id_form IN (${noPi.fid}, ${child.fid})`)[0][0];
		expect(elementCountAfter).toBe(elementCountBefore);
		expect(primaryRelationshipLinks(noPi.fid, child.fid)).toEqual([]);
	});

	test('deleting a form removes everything attached to it, and nothing attached to any other form', async ({ page }) => {
		// Every kind of row that formulizeFormsHandler::delete() removes along with a form, on both forms, so
		// the deletion has something to remove and something it must leave alone. The second form also
		// inherits its permissions from the first.
		expect(parentFid && childFid, 'the first test should have made the forms').toBeTruthy();
		seedRowsForForm(parentFid, childFid);
		seedRowsForForm(childFid, parentFid);
		dbQuery(`UPDATE ${dbPrefix()}_formulize_id SET parent_perm_fid = ${parentFid} WHERE id_form = ${childFid}`);

		const parentRows = rowsAttachedToForm(parentFid);
		for (const [kind, ids] of Object.entries(parentRows)) {
			expect(ids.length, `the form being deleted should have ${kind}, or their removal proves nothing`).toBeGreaterThan(0);
		}
		const childRowsBefore = rowsAttachedToForm(childFid);
		// the plain view, and the one whose sv_formframe holds the deleted form's id as a relationship id
		expect(childRowsBefore.savedViews.length).toBe(2);

		await login(page, 'admin');
		await deleteMuseumForm(page, parentFid);

		const emptied = Object.fromEntries(Object.keys(parentRows).map(kind => [kind, []]));
		expect(rowsAttachedToForm(parentFid)).toEqual(emptied);
		expect(rowsAttachedToForm(childFid)).toEqual(childRowsBefore);
		// The inheriting form keeps its own copy of the permissions, and stops pointing at a form that is gone
		expect(dbQuery(`SELECT parent_perm_fid FROM ${dbPrefix()}_formulize_id WHERE id_form = ${childFid}`)).toEqual([['0']]);
	});
});
