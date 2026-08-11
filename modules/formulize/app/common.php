<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
###############################################################################
##  Project: Formulize                                                       ##
###############################################################################

/**
 * Shared plumbing for the Formulize mobile app bridge (modules/formulize/app/*.php).
 *
 * WHAT THE BRIDGE IS FOR
 *
 * The mobile app shows Formulize pages in a WebView, but draws the site menu, the login screen and
 * the 2FA prompt as native mobile UI instead of HTML. It therefore needs two things the website
 * never had to expose: a way to sign in that returns data rather than a redirect to a page, and a
 * description of the menu as data rather than as a sidebar.
 *
 * These endpoints provide exactly that and nothing more. They are not a general-purpose API: page
 * content still comes from ordinary Formulize pages loaded in the WebView with a normal session
 * cookie. That is deliberate - it keeps every screen, permission check and template in one place,
 * so the app cannot drift away from what the website does.
 *
 * HOW A SESSION GETS ESTABLISHED
 *
 *   login.php   username + password  -> device token, or a 2FA challenge
 *   verify.php  2FA code             -> device token
 *   session.php device token         -> a real PHP session + cookie, which the WebView then uses
 *
 * The password is never stored on the device. The device token is, in the OS keychain, and it can
 * only be exchanged for a session - never used as a password, and revocable per device.
 *
 * All responses are JSON, and every response carries api_version so an older app can recognise a
 * newer server. Bump FORMULIZE_APP_API_VERSION only for breaking changes.
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

define('FORMULIZE_APP_API_VERSION', 1);

// How long a device token stays valid without being used to open a session. Any successful
// exchange pushes the expiry back out, so an app in regular use never expires; one abandoned for
// this long stops working and the user signs in again.
define('FORMULIZE_APP_TOKEN_LIFETIME', 60 * 60 * 24 * 180); // 180 days

// Sign-in rate limiting. Deliberately stricter on username than on IP: many users can legitimately
// share one IP (an office, a school, mobile carrier NAT), so a tight IP limit would lock out
// innocent people, while a tight per-username limit only affects the account being guessed at.
define('FORMULIZE_APP_LOGIN_MAX_PER_USER', 10);
define('FORMULIZE_APP_LOGIN_MAX_PER_IP', 50);
define('FORMULIZE_APP_LOGIN_WINDOW', 900); // 15 minutes

// How long the user has to enter their 2FA code before the challenge token issued by login.php
// stops being accepted and they have to enter their password again. Generous enough to cover
// waiting for an SMS or switching to an email app and back.
define('FORMULIZE_APP_CHALLENGE_LIFETIME', 600); // 10 minutes

/**
 * Prepare the request for a JSON response.
 *
 * Every endpoint calls this immediately after mainfile.php. Two things have to happen before any
 * output: the debug logger has to be silenced (it appends an HTML report to the response, which
 * would corrupt the JSON), and any output buffers opened during the CMS bootstrap have to be
 * discarded (the same reason formulize_xhr_responder.php does it). Also pulls in the Formulize
 * function library, which everything here relies on for formulize_db_escape().
 */
function formulize_app_bootstrap() {
    icms::$logger->disableLogger();
    while (ob_get_level()) {
        ob_end_clean();
    }
    include_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';
}

/**
 * Send a JSON response and stop.
 *
 * Every endpoint exits through here so the shape is identical everywhere: api_version always
 * present, Content-Type always set, and no stray output from the CMS bootstrap ahead of it (the
 * output buffers are drained in formulize_app_bootstrap()).
 */
function formulize_app_respond($data, $httpStatus = 200) {
    if (!headers_sent()) {
        http_response_code($httpStatus);
        header('Content-Type: application/json; charset=utf-8');
        // These endpoints are per-user and auth-bearing; never let anything cache them.
        header('Cache-Control: no-store');
    }
    $data['api_version'] = FORMULIZE_APP_API_VERSION;
    exit(json_encode($data));
}

/**
 * Send a JSON error and stop.
 *
 * $status is a short machine-readable string the app switches on; $message is human-readable text
 * the app may show. Deliberately separated so the app never has to parse prose, and so wording can
 * change without breaking clients.
 */
function formulize_app_fail($status, $message = '', $httpStatus = 400) {
    formulize_app_respond(array(
        'status' => $status,
        'message' => $message,
    ), $httpStatus);
}

/**
 * Read and decode a JSON request body.
 *
 * The app posts JSON rather than form-encoded data. Returns an array; a body that is absent,
 * malformed or not a JSON object yields an empty array, so callers can treat every field as
 * simply missing rather than having to distinguish "no body" from "bad body".
 */
function formulize_app_readJsonBody() {
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || $raw === '') {
        return array();
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : array();
}

/**
 * Pull a string field out of a decoded request body.
 *
 * Guards the type as well as presence: a JSON body can legitimately contain arrays, numbers or
 * null where a string is expected, and passing those straight into string functions is how PHP 8
 * turns a malformed request into a fatal error instead of a 400.
 */
function formulize_app_field($body, $key, $default = '') {
    if (!isset($body[$key]) || !is_string($body[$key])) {
        return $default;
    }
    return trim($body[$key]);
}

/**
 * Require that the request was a POST.
 *
 * The endpoints that change state or check credentials all demand POST, so that credentials can
 * never end up in a URL, a server access log, or a browser history.
 */
function formulize_app_requirePost() {
    if (!isset($_SERVER['REQUEST_METHOD']) || strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
        formulize_app_fail('method_not_allowed', 'This endpoint requires a POST request.', 405);
    }
}

/**
 * The client's IP, as the server sees it.
 *
 * REMOTE_ADDR only - deliberately not X-Forwarded-For, which the client can set to anything and
 * which would let an attacker sidestep the per-IP sign-in limit by varying a header. A site behind
 * a reverse proxy will see all app traffic as the proxy's IP, which makes the per-IP limit less
 * useful there but never less safe; the per-username limit is the one that actually protects an
 * account, and it is unaffected.
 */
function formulize_app_clientIp() {
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
}

/**
 * Count one attempt against a throttle key and report whether the limit is now exceeded.
 *
 * Returns true when the caller should refuse to proceed. The window is a sliding reset rather than
 * a rolling log: the first attempt starts a window, and once the window elapses the counter starts
 * over. That is coarser than a true rolling window but needs one row and one query, and is well
 * matched to "stop someone hammering this endpoint".
 *
 * Counting happens before the credential check, so a request that is refused for being over the
 * limit still counts - otherwise an attacker could keep a counter frozen by ignoring the refusals.
 */
function formulize_app_throttleExceeded($key, $maxAttempts, $windowSeconds) {
    global $xoopsDB;

    $table = $xoopsDB->prefix('formulize_app_throttle');
    $now = time();
    $escapedKey = formulize_db_escape($key);

    $res = $xoopsDB->queryF("SELECT attempts, first_attempt FROM `$table` WHERE throttle_key = '$escapedKey'");
    $row = ($res && $xoopsDB->getRowsNum($res)) ? $xoopsDB->fetchArray($res) : false;

    if ($row && (intval($row['first_attempt']) + $windowSeconds) > $now) {
        $attempts = intval($row['attempts']) + 1;
        $xoopsDB->queryF(
            "UPDATE `$table` SET attempts = $attempts, last_attempt = $now WHERE throttle_key = '$escapedKey'"
        );
        return $attempts > $maxAttempts;
    }

    // No row, or the previous window has elapsed: start a fresh window at one attempt.
    $xoopsDB->queryF(
        "REPLACE INTO `$table` (throttle_key, attempts, first_attempt, last_attempt)"
        . " VALUES ('$escapedKey', 1, $now, $now)"
    );

    // Opportunistically prune rows whose window is long past, so this table cannot grow without
    // bound on a site under sustained probing. Cheap because last_attempt is indexed.
    $pruneBefore = $now - ($windowSeconds * 4);
    $xoopsDB->queryF("DELETE FROM `$table` WHERE last_attempt < $pruneBefore");

    return 1 > $maxAttempts;
}

/**
 * Clear a throttle counter, called after a genuinely successful sign-in.
 *
 * Without this, a user who mistypes a password several times and then gets it right would still be
 * carrying those failures for the rest of the window.
 */
function formulize_app_throttleClear($key) {
    global $xoopsDB;
    $table = $xoopsDB->prefix('formulize_app_throttle');
    $escapedKey = formulize_db_escape($key);
    $xoopsDB->queryF("DELETE FROM `$table` WHERE throttle_key = '$escapedKey'");
}

/**
 * The site's branding, for the app to style its own native UI with.
 *
 * The app draws its header, drawer and sign-in screen natively, so it needs to know the site's
 * colours, font and logo in order to look like that site rather than like a generic app.
 *
 * The source is deliberately modules/formulize/include/appearance.php - the settings behind the
 * Appearance page in the Formulize admin UI - and NOT anything read out of a theme. A theme's
 * stylesheet is written for a web page and says nothing usable about how a native mobile header
 * should look, and hard-coding one theme's palette would make the app wrong on every other theme.
 * The Appearance settings are a Formulize-level concept that every theme is expected to honour, so
 * matching them keeps the native chrome and the WebView content consistent whatever theme is set.
 *
 * Returns null when the site has no appearance settings, which is the case on any Formulize older
 * than the release that introduced them. The app must treat this as "use your own defaults" rather
 * than as an error - it will be the normal answer for a while yet.
 */
function formulize_app_appearance() {
    $appearanceFile = XOOPS_ROOT_PATH . '/modules/formulize/include/appearance.php';
    if (!file_exists($appearanceFile)) {
        return null;
    }
    include_once $appearanceFile;
    if (!function_exists('formulize_getAppearanceSettings')) {
        return null;
    }

    $settings = formulize_getAppearanceSettings();
    $colours = array();
    foreach ($settings as $name => $value) {
        // Only colour keys here; the font and logo are handled separately below. An empty value
        // means "use the theme default", so it is omitted rather than sent as an empty string.
        if ($value === '' || strpos($name, 'appearance_') !== 0) {
            continue;
        }
        $key = substr($name, strlen('appearance_'));
        if (in_array($key, array('font', 'customfont', 'logo'), true)) {
            continue;
        }
        $colours[$key] = $value;
    }

    return array(
        'colours' => $colours,
        'font' => function_exists('formulize_getAppearanceFont') ? formulize_getAppearanceFont($settings) : '',
        'logo_url' => function_exists('formulize_getAppearanceLogoUrl') ? formulize_getAppearanceLogoUrl() : '',
    );
}

/**
 * Derive the secret used to sign 2FA challenge tokens.
 *
 * Same construction as formulize_anonEntryToken_secret() in include/functions.php: derived from
 * the site's database salt, with a distinct domain-separation string so this key can never be
 * interchanged with any other use of the salt.
 */
function formulize_app_challengeSecret() {
    $base = defined('XOOPS_DB_SALT') ? XOOPS_DB_SALT
        : (defined('XOOPS_DB_PASS') ? XOOPS_DB_PASS : 'formulize_app_challenge_fallback_secret');
    return hash_hmac('sha256', 'formulize_app_challenge_v1', $base, true);
}

/**
 * The single place the signed challenge payload is constructed, so signer and verifier can never
 * drift in how it is encoded - the failure mode that silently weakens a MAC.
 */
function formulize_app_challengeSignature($uid, $expires) {
    return hash_hmac('sha256', intval($uid) . ':' . intval($expires), formulize_app_challengeSecret());
}

/**
 * Build a 2FA challenge token: "<uid>.<expires>.<signature>".
 *
 * WHAT THIS IS FOR: it is proof that the bearer passed the password check for this uid a moment
 * ago. verify.php demands it alongside the 2FA code, so that possessing a username and guessing a
 * six-digit code is not by itself enough - the password is still genuinely required, exactly as it
 * is in the website's flow through include/checklogin.php.
 *
 * WHY NOT icms::$security->createToken(): that mechanism stores a file per token keyed by
 * session_id() and hashes in the User-Agent, so it requires the caller to hold a PHP session
 * across two requests before being logged in at all. That is fine for a browser and fragile for an
 * app. This token is stateless: nothing is written server-side, so there is no session to keep
 * alive, no file to clean up, and a retry of a mistyped code cannot consume it.
 *
 * The expiry is inside the signed payload, so its lifetime is enforced cryptographically rather
 * than being a value the client could edit.
 */
function formulize_app_signChallenge($uid, $expires) {
    $uid = intval($uid);
    $expires = intval($expires);
    return $uid . '.' . $expires . '.' . formulize_app_challengeSignature($uid, $expires);
}

/**
 * Verify a challenge token really was issued by us, for this uid, and has not expired.
 *
 * Uses hash_equals for the comparison so the check cannot be attacked by timing.
 */
function formulize_app_verifyChallenge($token, $uid) {
    if (!is_string($token)) {
        return false;
    }
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    list($tokenUid, $expires, $sig) = $parts;
    if (!is_numeric($tokenUid) || !is_numeric($expires)) {
        return false;
    }
    if (intval($tokenUid) !== intval($uid)) {
        return false; // a token issued for one account cannot be replayed against another
    }
    if (time() > intval($expires)) {
        return false;
    }
    return hash_equals(formulize_app_challengeSignature($tokenUid, $expires), (string) $sig);
}

/**
 * Issue a device token for a user and return the raw token.
 *
 * The raw token is returned to the caller exactly once, to be stored in the device's keychain.
 * Only its sha256 hash is kept here, so a disclosure of this table does not yield usable tokens.
 *
 * Note what is deliberately NOT included in the hash: the request IP. The equivalent web mechanism
 * (tfa_deviceTokenHash in include/2fa/manage.php) does fold in REMOTE_ADDR, which is right for a
 * browser but unusable for a phone that changes IP whenever it moves between cell and WiFi. See
 * admin/patches/011_app_tables.php for the full reasoning and what compensates for it.
 *
 * $tfaTrusted records that the user passed a real 2FA challenge on this device, which is what
 * later lets session.php skip re-challenging.
 */
function formulize_app_issueDeviceToken($uid, $deviceName, $platform, $tfaTrusted) {
    global $xoopsDB;

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $now = time();
    $expires = $now + FORMULIZE_APP_TOKEN_LIFETIME;

    $table = $xoopsDB->prefix('formulize_app_devices');
    $sql = "INSERT INTO `$table` (uid, token_hash, device_name, platform, tfa_trusted, created, last_used, expires, revoked)"
        . " VALUES ("
        . intval($uid) . ", "
        . "'" . formulize_db_escape($tokenHash) . "', "
        . "'" . formulize_db_escape(substr($deviceName, 0, 255)) . "', "
        . "'" . formulize_db_escape(substr($platform, 0, 20)) . "', "
        . ($tfaTrusted ? 1 : 0) . ", "
        . $now . ", " . $now . ", " . $expires . ", 0)";

    if (!$xoopsDB->queryF($sql)) {
        return false;
    }
    return $token;
}

/**
 * Look up a raw device token and return its row, or false if it is not usable.
 *
 * "Not usable" covers unknown, revoked and expired alike, and they are deliberately
 * indistinguishable to the caller: the app's response to all three is the same (ask the user to
 * sign in again), and collapsing them avoids handing an attacker a way to probe which tokens ever
 * existed.
 *
 * A successful lookup slides the expiry forward, so an app in regular use never expires.
 */
function formulize_app_validateDeviceToken($token) {
    global $xoopsDB;

    if (!is_string($token) || $token === '') {
        return false;
    }

    $tokenHash = hash('sha256', $token);
    $table = $xoopsDB->prefix('formulize_app_devices');
    $now = time();

    $res = $xoopsDB->query(
        "SELECT device_id, uid, tfa_trusted, device_name, platform FROM `$table`"
        . " WHERE token_hash = '" . formulize_db_escape($tokenHash) . "'"
        . " AND revoked = 0 AND expires > $now"
    );
    if (!$res || !$xoopsDB->getRowsNum($res)) {
        return false;
    }
    $row = $xoopsDB->fetchArray($res);

    $newExpiry = $now + FORMULIZE_APP_TOKEN_LIFETIME;
    $xoopsDB->queryF(
        "UPDATE `$table` SET last_used = $now, expires = $newExpiry WHERE device_id = " . intval($row['device_id'])
    );

    return $row;
}

/**
 * Revoke a single device token. Used when the app signs out or a site is removed from the app.
 *
 * Revoking rather than deleting keeps the row available for a future "your signed-in devices"
 * screen, so a user can see that a device was signed out rather than finding it vanished.
 */
function formulize_app_revokeDeviceToken($token) {
    global $xoopsDB;
    if (!is_string($token) || $token === '') {
        return;
    }
    $tokenHash = hash('sha256', $token);
    $table = $xoopsDB->prefix('formulize_app_devices');
    $xoopsDB->queryF(
        "UPDATE `$table` SET revoked = 1 WHERE token_hash = '" . formulize_db_escape($tokenHash) . "'"
    );
}

/**
 * Establish a real ImpressCMS session for a user, and mark it as an app session.
 *
 * This mirrors the session-establishment half of include/checklogin.php - the same session
 * regeneration, the same $_SESSION keys, the same cookie flags - because anything the rest of the
 * CMS reads to decide "who is logged in" must be set identically, or pages loaded in the WebView
 * would not recognise the user.
 *
 * What is deliberately NOT copied from checklogin.php: the autologin cookies (the app has its own,
 * better, device token) and the multi_login online-user check (which would refuse a sign-in on the
 * phone while the same person is logged in at their desk - reasonable for a website, wrong here).
 *
 * $_SESSION['formulize_app_session'] is the flag every other part of app mode keys off, most
 * importantly the stripped-down page chrome selected in header.php. It lives in the session rather
 * than being sniffed from a request header because react-native-webview only applies custom
 * headers to the first request of a page load, not to the navigations, form posts and XHR that
 * follow - whereas the session cookie is sent with all of them.
 */
function formulize_app_startSession($user) {
    global $icmsConfig;

    session_regenerate_id(true);
    $_SESSION = array();
    $_SESSION['xoopsUserId'] = $user->getVar('uid');
    $_SESSION['xoopsUserGroups'] = $user->getGroups();
    $_SESSION['xoopsUserLastLogin'] = $user->getVar('last_login');
    $_SESSION['formulize_app_session'] = true;

    $member_handler = icms::handler('icms_member');
    $user->setVar('last_login', time());
    $member_handler->updateUserByField($user, 'last_login', time());

    if ($icmsConfig['use_mysession'] && $icmsConfig['session_name'] != '') {
        $session_secure = substr(ICMS_URL, 0, 5) == 'https';
        setcookie($icmsConfig['session_name'], session_id(), array(
            'expires' => time() + (60 * $icmsConfig['session_expire']),
            'path' => '/',
            'domain' => '',
            'secure' => $session_secure,
            'httponly' => true,
            'samesite' => icms_core_Session::cookieSameSite($session_secure)
        ));
    }
}

/**
 * Require that this request carries a valid app session, and return the user object.
 *
 * Endpoints that expose a user's own data (menu.php) go through here. Note that the legacy
 * modules/formulize/app_list.php (now removed) did NOT do this - it relied entirely on permission filtering
 * inside the handlers it called. That is a thin guarantee to rest on, so the bridge is explicit.
 */
function formulize_app_requireSession() {
    global $xoopsUser;
    if (!is_object($xoopsUser)) {
        formulize_app_fail('not_signed_in', 'This request requires a signed-in user.', 401);
    }
    return $xoopsUser;
}

/**
 * Obscure a 2FA contact for display, so the app can tell the user where a code went without
 * disclosing the full address or number to whoever is holding the phone.
 *
 * "julian@example.com" -> "j*****n@example.com";  "4165551234" -> "******1234"
 */
function formulize_app_maskContact($contact, $method) {
    if ($method === 'app') {
        return '';
    }
    if ($method === 'sms') {
        $digits = preg_replace('/[^0-9]/', '', $contact);
        return strlen($digits) > 4
            ? str_repeat('*', strlen($digits) - 4) . substr($digits, -4)
            : $digits;
    }
    $at = strpos($contact, '@');
    if ($at === false || $at < 2) {
        return $contact;
    }
    $name = substr($contact, 0, $at);
    return substr($name, 0, 1) . str_repeat('*', max(1, strlen($name) - 2)) . substr($name, -1) . substr($contact, $at);
}
