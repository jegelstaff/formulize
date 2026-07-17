const { test, expect } = require('@playwright/test');
import { E2E_TEST_BASE_URL } from '../config';
import { login, dbQuery, dbPrefix } from '../../utils';
const { execFileSync } = require('child_process');

test.use({ baseURL: E2E_TEST_BASE_URL });

// ============================================================
// A4 — modern password hashing (bcrypt + pepper), live login checks
// ============================================================
// The password KDF migration (audit item A4 / plan item P2.7) replaced the fast
// salted-SHA256 hashing with bcrypt (cost 12), keyed through the site pepper
// (XOOPS_DB_SALT), and rewired login to fetch-then-verify with a transparent
// rehash-on-login for legacy accounts. The pure-logic side is covered by
// tests/password_hashing_test.php; what THAT test cannot reach is the live
// end-to-end login path — icms_member_Handler::loginUser() actually fetching the
// row, verifying in PHP, and WRITING the upgraded hash back to the users table.
// That DB-write-on-login is exactly the part only a real login can exercise, so
// it is verified here.
//
// This lives in the `setup` project (which CI runs with --workers=1, i.e. strictly
// sequential) rather than `validate` (--fully-parallel), because the upgrade test
// deliberately rewrites the shared `admin` account's stored hash; running it in a
// parallel worker would race other specs that log in as admin. It runs right after
// install and leaves admin in a normal, logged-in-able state (a fresh bcrypt hash).

const WEB_CONTAINER = process.env.E2E_WEB_CONTAINER || 'formulize-web-1';
const ADMIN = 'admin';
const ADMIN_PASS = 'password';

/**
 * Produce a hash in the exact shape a PRE-migration account stored: the app's own
 * legacy code path, encryptPass($pass, $salt, enc_type=1, reset=1) -> salted SHA256
 * (sha256($salt . md5($pass) . XOOPS_DB_SALT)). Computed inside the web container so
 * it uses the install's real pepper. reset=1 pins enc_type=1 regardless of config.
 * The 64-hex-char match also strips any incidental bootstrap output.
 */
function legacyHash(password, salt) {
	const php =
		'require "/var/www/html/mainfile.php";' +
		'echo "<H>".(new icms_core_Password())->encryptPass(' +
		JSON.stringify(password) + ', ' + JSON.stringify(salt) + ', 1, 1)."</H>";';
	const out = execFileSync('docker', ['exec', WEB_CONTAINER, 'php', '-r', php], { encoding: 'utf8' });
	const m = out.match(/<H>([0-9a-f]{64})<\/H>/);
	if (!m) throw new Error('Failed to compute legacy hash in container. php output:\n' + out);
	return m[1];
}

function storedPass(login_name) {
	const rows = dbQuery(
		`SELECT pass FROM ${dbPrefix()}_users WHERE login_name = '${login_name}' LIMIT 1`);
	expect(rows.length).toBe(1);
	return rows[0][0];
}

test('legacy SHA256 account logs in and is transparently upgraded to bcrypt cost 12', async ({ page }) => {
	const prefix = dbPrefix();
	const salt = 'a4TestSalt0000000000';

	// Arrange: force admin's stored credential back to the legacy salted-SHA256 form
	// a pre-migration account would have had, so we can observe the upgrade-on-login.
	const legacy = legacyHash(ADMIN_PASS, salt);
	expect(legacy).toMatch(/^[0-9a-f]{64}$/); // sanity: really a fast-hash hex, not bcrypt
	dbQuery(
		`UPDATE ${prefix}_users SET pass = '${legacy}', salt = '${salt}', enc_type = 1 ` +
		`WHERE login_name = '${ADMIN}'`);
	expect(storedPass(ADMIN)).toBe(legacy); // starting state confirmed legacy

	// Act: log in with the same plaintext through the real UI. login() waits for the
	// post-login /modules/formulize/ URL, so reaching the next line means the legacy
	// hash verified and authentication succeeded.
	await login(page, ADMIN, ADMIN_PASS);

	// Assert: loginUser() transparently rewrote the stored hash to bcrypt cost 12.
	const after = storedPass(ADMIN);
	expect(after).toMatch(/^\$2y\$12\$/);
	expect(after).not.toBe(legacy);
});

test('a second login on the now-bcrypt account still succeeds and does not rewrite the hash', async ({ page }) => {
	// After the previous test admin is stored as bcrypt cost 12. Logging in again must
	// still verify (proving password_verify works against the upgraded hash) and must
	// NOT need another rewrite (cost already matches -> passwordNeedsUpgrade is false).
	const before = storedPass(ADMIN);
	expect(before).toMatch(/^\$2y\$12\$/);

	await login(page, ADMIN, ADMIN_PASS);

	// bcrypt embeds a random salt, so a needless re-hash would change the string; an
	// unchanged value confirms loginUser correctly left the already-current hash alone.
	expect(storedPass(ADMIN)).toBe(before);
});

test('a wrong password is rejected', async ({ page }) => {
	await page.goto('/user.php');
	await page.locator('input[name="uname"]').fill(ADMIN);
	await page.locator('input[name="pass"]').fill('definitely-not-the-admin-password');
	await page.locator('input[name="pass"]').press('Enter');

	// Must NOT reach a Formulize module page, and the login form must still be present.
	await page.waitForLoadState('networkidle');
	expect(page.url()).not.toMatch(/\/modules\/formulize\//);
	await expect(page.locator('input[name="pass"]')).toBeVisible();
});
