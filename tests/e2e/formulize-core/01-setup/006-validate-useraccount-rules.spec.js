const { test, expect } = require('@playwright/test');
import { E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD, E2E_TEST_BASE_URL } from '../config';
import { login } from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Phase 2 — userAccount element validation behaviors
// ============================================================
// Depends on 005 having created the Staff EAU form (handle "staff") and its
// four users (ahstaff/mhstaff/curator1/curator2). Each test opens a fresh
// Add-Staff dialog, enters an intentionally invalid state, clicks Save, and
// asserts the browser alert that the userAccount validators raise.
//
// All userAccount validators surface their errors via window.alert() and then
// return false to block submission (see formdisplay.php validateAndSubmit() +
// the per-element generateValidationCode()). Because submission is always
// blocked, no entry is ever created and the suite leaves no junk users behind.
//
// The exact alert strings, firing order, and click behavior below were captured
// from the live rendered form (not just read from source) — the validators run
// in form-field order: First Name → Last Name → Username (XHR uniqueness) →
// Password → combined Email/Phone (format + digit-count + at-least-one + XHR
// uniqueness) → Login name → selects. To isolate a given validator, every field
// ahead of it must hold a valid value.

const staff = { fid: null };

// Install a one-shot dialog listener, click Save, and assert the alert message.
// The username/email/phone uniqueness checks round-trip to the server via XHR
// and auto-resubmit, so the alert can appear a beat after the click — poll for
// it rather than racing the click.
async function expectAlertOnSave(page, pattern) {
	const messages = [];
	const handler = d => { messages.push(d.message()); d.accept().catch(() => {}); };
	page.on('dialog', handler);
	// Mirror saveFormulizeForm()'s token-settling click, then submit.
	await page.locator('div#formulizeform').click();
	await page.getByRole('button', { name: 'Save', exact: true }).click();
	await expect.poll(() => messages.length, { timeout: 20000 }).toBeGreaterThan(0);
	page.off('dialog', handler);
	expect(messages.some(m => pattern.test(m)),
		`expected an alert matching ${pattern}; saw: ${JSON.stringify(messages)}`).toBeTruthy();
}

// Discover the Staff form id once (via the Formulize menu) and open a fresh
// Add-Staff dialog on master.php.
async function openAddStaff(page) {
	await login(page, E2E_TEST_ADMIN_USERNAME, E2E_TEST_ADMIN_PASSWORD);
	if (!staff.fid) {
		await page.goto('/modules/formulize/');
		await page.waitForLoadState('networkidle');
		const href = await page.getByRole('link', { name: 'Staff', exact: true }).first().getAttribute('href');
		const m = href && href.match(/fid=(\d+)/);
		expect(m, 'Could not find the Staff form link in the Formulize menu').toBeTruthy();
		staff.fid = parseInt(m[1], 10);
	}
	await page.goto(`/modules/formulize/master.php?fid=${staff.fid}`);
	await page.locator('#formulize_addButton').click();
	await page.waitForLoadState('networkidle');
}

// Fill only the named fields; everything else is left at its default. Filling a
// field marks the form changed, which is what arms the client-side validators.
async function fillFields(page, f) {
	if (f.firstName !== undefined) await page.getByRole('textbox', { name: 'First Name' }).fill(f.firstName);
	if (f.lastName !== undefined) await page.getByRole('textbox', { name: 'Last Name' }).fill(f.lastName);
	if (f.username !== undefined) await page.getByRole('textbox', { name: 'Username' }).fill(f.username);
	if (f.loginName !== undefined) await page.getByRole('textbox', { name: 'Login name' }).fill(f.loginName);
	if (f.email !== undefined) await page.getByRole('textbox', { name: 'Email Address' }).fill(f.email);
	if (f.phone !== undefined) await page.getByRole('textbox', { name: 'Phone Number' }).fill(f.phone);
	if (f.password !== undefined || f.password2 !== undefined) {
		const pw = page.locator('input[type="password"]');
		if (f.password !== undefined) await pw.nth(0).fill(f.password);
		if (f.password2 !== undefined) await pw.nth(1).fill(f.password2);
	}
}

test.describe('Phase 2 — userAccount validation rules (Staff EAU form)', () => {

	test.beforeEach(async ({ page }) => {
		await openAddStaff(page);
	});

	test('Password is required on a new user', async ({ page }) => {
		// Valid name + unique username + valid email so validation reaches the
		// password check, then leave both password boxes blank.
		await fillFields(page, {
			firstName: 'Val', lastName: 'Idate',
			username: 'val_pwreq', loginName: 'val_pwreq',
			email: 'val_pwreq@x.net', password: '', password2: '',
		});
		await expectAlertOnSave(page, /Please enter a password for the account\./);
	});

	test('Password and confirmation must match', async ({ page }) => {
		await fillFields(page, {
			firstName: 'Val', lastName: 'Idate',
			username: 'val_pwmis', loginName: 'val_pwmis',
			email: 'val_pwmis@x.net', password: 'aaaa', password2: 'bbbb',
		});
		await expectAlertOnSave(page, /The passwords do not match\./);
	});

	test('Username must be unique (login already exists)', async ({ page }) => {
		// "ahstaff" is the login (login_name) of a user created in 005.
		await fillFields(page, {
			firstName: 'Val', lastName: 'Idate',
			username: 'ahstaff', loginName: 'ahstaff',
		});
		await expectAlertOnSave(page, /'Username' has been entered already/);
	});

	test('Email must be unique (address already exists)', async ({ page }) => {
		// ahstaff@museum.formulize.net belongs to a user created in 005.
		await fillFields(page, {
			firstName: 'Val', lastName: 'Idate',
			username: 'val_emuniq', loginName: 'val_emuniq',
			email: 'ahstaff@museum.formulize.net', password: '12345', password2: '12345',
		});
		await expectAlertOnSave(page, /'email address' has been entered already/);
	});

	test('Email must be a valid format', async ({ page }) => {
		await fillFields(page, {
			firstName: 'Val', lastName: 'Idate',
			username: 'val_emfmt', loginName: 'val_emfmt',
			email: 'not-an-email', password: '12345', password2: '12345',
		});
		await expectAlertOnSave(page, /email address you have entered is not valid/);
	});

	test('Phone number must have 10 digits', async ({ page }) => {
		// No email; a too-short phone trips the digit-count check.
		await fillFields(page, {
			firstName: 'Val', lastName: 'Idate',
			username: 'val_phone', loginName: 'val_phone',
			email: '', phone: '555-1234', password: '12345', password2: '12345',
		});
		await expectAlertOnSave(page, /phone number with 10 digits/);
	});

	test('At least one of email or phone is required', async ({ page }) => {
		await fillFields(page, {
			firstName: 'Val', lastName: 'Idate',
			username: 'val_atleast', loginName: 'val_atleast',
			email: '', phone: '', password: '12345', password2: '12345',
		});
		await expectAlertOnSave(page, /enter either an email address or a phone number/);
	});
});
