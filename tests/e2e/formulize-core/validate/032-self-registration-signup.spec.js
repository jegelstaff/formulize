const { test, expect } = require('@playwright/test');
import { E2E_TEST_BASE_URL } from '../config';
import {
	setSystemConfig,
	getSystemConfig,
	getUserByLogin,
	getUserGroupIds,
	getPendingConfirmationCode,
	createTestGroup,
	deleteTestGroup,
	createToken,
	deleteToken,
	getTokenUses,
} from '../../utils';

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// Phase — /signup.php public self-service account registration
// ============================================================
// signup.php lets an anonymous visitor create their own account on the Formulize System Users form
// (the same form + readelements.php save pipeline as Edit Account and the webmaster Users list).
//
//   - Whether it is available at all is governed by the ImpressCMS "Allow new user registration"
//     system setting (icms_config.allow_register). Most sites keep this OFF; signup.php must respect it.
//   - New accounts are created INACTIVE. This suite treats level 0 as "Pending confirmation"
//     (semantically distinct from -1 Disabled); a pending account cannot log in.
//   - The visitor confirms with a code delivered by email/SMS. The docker test box cannot send mail,
//     so we read the code straight from the tfa_codes table (getPendingConfirmationCode) to simulate
//     the user receiving it — exactly the manual step a human tester would take here.
//   - On confirmation the account is activated (level 1) and the visitor is logged in automatically.
//   - An invitation token (?token=) adds membership in the group(s) the token grants, on top of the
//     Registered Users group everyone gets.
//
// Registered Users group id is the ImpressCMS constant XOOPS_GROUP_USERS = 2.
const REGISTERED_USERS_GROUP = 2;

// Remember the site's real allow_register value and restore it afterwards so this spec leaves the
// system exactly as it found it (most importantly: closed to registration).
let originalAllowRegister = null;
let originalRequireToken = null;

test.beforeAll(() => {
	originalAllowRegister = getSystemConfig('allow_register');
	originalRequireToken = getSystemConfig('requireTokenForSignup');
});

test.afterAll(() => {
	if (originalAllowRegister !== null) {
		setSystemConfig('allow_register', originalAllowRegister);
	}
	if (originalRequireToken !== null) {
		setSystemConfig('requireTokenForSignup', originalRequireToken);
	}
});

// Every test that does NOT specifically exercise the require-token gate assumes it is off, so make
// that explicit and independent of test order.
test.beforeEach(() => {
	setSystemConfig('requireTokenForSignup', 0);
});

/**
 * Fill and submit the account form for a new signup, then land on the confirmation step.
 * Returns the created user's uid (looked up by login name) once it exists as a pending account.
 */
async function submitSignupForm(page, { firstName, lastName, username, email, password }, tokenQuery = '') {
	await page.goto('/signup.php' + tokenQuery);
	// The account form starts hidden and is revealed by JS.
	await expect(page.locator('#formulizeform form')).toBeVisible();

	await page.getByRole('textbox', { name: 'First Name' }).fill(firstName);
	await page.getByRole('textbox', { name: 'Last Name' }).fill(lastName);
	await page.getByRole('textbox', { name: 'Username' }).fill(username);
	await page.getByRole('textbox', { name: 'Email Address' }).fill(email);
	// The password element renders one or two password inputs (password + verify); fill all of them.
	const passInputs = page.locator('#formulizeform input[type="password"]');
	const count = await passInputs.count();
	for (let i = 0; i < count; i++) {
		await passInputs.nth(i).fill(password);
	}

	// Submitting creates the (inactive) user and redirects to the confirmation step.
	await Promise.all([
		page.waitForURL(/op=confirm/, { timeout: 60000 }),
		page.getByRole('button', { name: 'Create Account' }).click(),
	]);

	const user = getUserByLogin(username);
	expect(user, 'new account should exist in the users table').not.toBeNull();
	return user.uid;
}

/**
 * On the confirmation step, read the delivered code from the database and submit it.
 */
async function confirmSignup(page, uid) {
	const code = getPendingConfirmationCode(uid);
	expect(code, 'a confirmation code should have been generated for the new user').toBeTruthy();
	await expect(page.locator('input[name="confirm_code"]')).toBeVisible();
	await page.locator('input[name="confirm_code"]').fill(code);
	await Promise.all([
		page.waitForURL(url => !/op=confirm/.test(url.href), { timeout: 60000 }),
		page.getByRole('button', { name: 'Confirm' }).click(),
	]);
}

// ---- A. Registration disabled is respected (the safe default) --------------
test('signup.php is unavailable when self-registration is disabled', async ({ page }) => {
	setSystemConfig('allow_register', 0);
	await page.goto('/signup.php');
	// signup.php redirects away; the account form must never be shown.
	await expect(page.locator('#formulizeform form')).toHaveCount(0);
	await expect(page.getByRole('heading', { name: 'Create an Account' })).toHaveCount(0);
});

// ---- B. Signup form renders (only) when enabled ----------------------------
test('signup form renders the expected fields when registration is enabled', async ({ page }) => {
	setSystemConfig('allow_register', 1);
	await page.goto('/signup.php');
	await expect(page.getByRole('heading', { name: 'Create an Account' })).toBeVisible();
	await expect(page.locator('#formulizeform form')).toBeVisible();

	// The fields a new signer-upper needs.
	await expect(page.getByRole('textbox', { name: 'First Name' })).toBeVisible();
	await expect(page.getByRole('textbox', { name: 'Username' })).toBeVisible();
	await expect(page.getByRole('textbox', { name: 'Email Address' })).toBeVisible();
	await expect(page.locator('#formulizeform input[type="password"]').first()).toBeVisible();

	// The invitation-code box is offered.
	await expect(page.locator('input[name="signup_token"]')).toBeVisible();

	// Admin-only account fields must never appear on the public signup form.
	for (const caption of ['Account Status', 'Group Memberships', 'Masquerade']) {
		await expect(page.locator('#formulizeform').getByText(caption, { exact: false })).toHaveCount(0);
	}
});

// ---- C. Full flow: signup → pending → confirm → active + logged in ---------
test('signup creates a pending account that activates and logs in after confirmation', async ({ page }) => {
	setSystemConfig('allow_register', 1);
	const suffix = Date.now();
	const creds = {
		firstName: 'Signup',
		lastName: 'Tester',
		username: 'signup' + suffix,
		email: `signup${suffix}@example.com`,
		password: 'Test12345',
	};

	const uid = await submitSignupForm(page, creds);

	// Before confirmation the account is Pending (level 0) and already belongs to Registered Users.
	const pending = getUserByLogin(creds.username);
	expect(pending.level).toBe(0);
	expect(getUserGroupIds(uid)).toContain(REGISTERED_USERS_GROUP);

	// Confirm with the code that would have been emailed/texted (read from the DB).
	await confirmSignup(page, uid);

	// The account is now Active (level 1) ...
	expect(getUserByLogin(creds.username).level).toBe(1);

	// ... and the visitor is logged in: the self-service Edit Account form (which requires a session)
	// renders instead of bouncing an anonymous visitor away.
	await page.goto('/edituser.php');
	await expect(page.locator('#formulizeform form')).toBeVisible();
});

// ---- D. A pending (unconfirmed) account cannot log in ----------------------
test('a pending account cannot log in before it is confirmed', async ({ page }) => {
	setSystemConfig('allow_register', 1);
	const suffix = Date.now();
	const creds = {
		firstName: 'Pending',
		lastName: 'Tester',
		username: 'pending' + suffix,
		email: `pending${suffix}@example.com`,
		password: 'Test12345',
	};

	const uid = await submitSignupForm(page, creds);
	expect(getUserByLogin(creds.username).level).toBe(0); // Pending

	// Attempt to log in with the (correct) credentials — it must be refused because the account is
	// not yet activated (checklogin.php rejects level <= 0). A successful login would land on a
	// /modules/formulize/... page; a refused one bounces back to index.php.
	await page.goto('/user.php');
	await page.locator('input[name="uname"]').fill(creds.username);
	await page.locator('input[name="pass"]').fill(creds.password);
	await page.locator('input[name="pass"]').press('Enter');
	await page.waitForLoadState('networkidle');
	expect(page.url()).not.toMatch(/\/modules\/formulize\//);

	// And it is still not logged in: Edit Account bounces the anonymous visitor away rather than
	// rendering the self-service form.
	await page.goto('/edituser.php');
	await expect(page.locator('#formulizeform form')).toHaveCount(0);
});

// ---- E. Invitation token grants an extra group -----------------------------
// A token in the sign-up URL should add membership in the group(s) it grants, on top of Registered
// Users. The token + a throwaway group are created directly in the DB (mirroring what the admin
// manage-tokens page stores) so this test needs no admin login and runs in any environment. The
// admin-facing token creation UI (custom token values) is exercised separately.
test('an invitation token in the URL adds the granted group membership on signup', async ({ page }) => {
	setSystemConfig('allow_register', 1);
	const suffix = Date.now();
	const tokenValue = 'E2ETOKEN' + suffix;
	const grantedGroupId = createTestGroup('E2E Signup Token Group ' + suffix);
	createToken(tokenValue, [grantedGroupId]); // unlimited uses

	try {
		const creds = {
			firstName: 'Token',
			lastName: 'Tester',
			username: 'token' + suffix,
			email: `token${suffix}@example.com`,
			password: 'Test12345',
		};

		// The token in the URL is accepted: the entry box is replaced by a confirmation line.
		await page.goto('/signup.php?token=' + tokenValue);
		await expect(page.getByText('Invitation code accepted', { exact: false })).toBeVisible();

		const uid = await submitSignupForm(page, creds, '?token=' + tokenValue);
		await confirmSignup(page, uid);

		// The account is active and holds BOTH Registered Users and the token-granted group.
		expect(getUserByLogin(creds.username).level).toBe(1);
		const groups = getUserGroupIds(uid);
		expect(groups).toContain(REGISTERED_USERS_GROUP);
		expect(groups).toContain(grantedGroupId);

		// The token was consumed once.
		expect(getTokenUses(tokenValue)).toBe(1);
	} finally {
		deleteToken(tokenValue);
		deleteTestGroup(grantedGroupId);
	}
});

// ---- F. "Require account tokens for public sign-ups?" preference ------------
// When on, signup.php must not present (or create) an account without a valid token.
test('requiring tokens blocks token-less signup and permits it with a valid token', async ({ page }) => {
	setSystemConfig('allow_register', 1);
	setSystemConfig('requireTokenForSignup', 1);

	// Without a token: the account form is not shown at all, and the required-token message is.
	await page.goto('/signup.php');
	await expect(page.getByRole('button', { name: 'Create Account' })).toHaveCount(0);
	await expect(page.getByText('need a valid invitation code', { exact: false })).toBeVisible();
	await expect(page.locator('input[name="signup_token"]')).toBeVisible();

	// With a valid token in the URL: the form appears and signup completes normally.
	const suffix = Date.now();
	const tokenValue = 'REQTOK' + suffix;
	const grantedGroupId = createTestGroup('E2E Req Token Group ' + suffix);
	createToken(tokenValue, [grantedGroupId]);

	try {
		const creds = {
			firstName: 'Required',
			lastName: 'Tester',
			username: 'reqtok' + suffix,
			email: `reqtok${suffix}@example.com`,
			password: 'Test12345',
		};

		await page.goto('/signup.php?token=' + tokenValue);
		await expect(page.getByText('Invitation code accepted', { exact: false })).toBeVisible();
		await expect(page.getByRole('button', { name: 'Create Account' })).toBeVisible();

		const uid = await submitSignupForm(page, creds, '?token=' + tokenValue);
		await confirmSignup(page, uid);

		expect(getUserByLogin(creds.username).level).toBe(1);
		expect(getUserGroupIds(uid)).toContain(grantedGroupId);
	} finally {
		deleteToken(tokenValue);
		deleteTestGroup(grantedGroupId);
	}
});
