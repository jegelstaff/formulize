<?php
/**
 * Exchange a device token for a real Formulize session.
 *
 * This runs every time the app opens a site. The app holds only a device token (in the OS
 * keychain); this turns that into an ordinary ImpressCMS session so that pages loaded in the
 * WebView behave exactly as they would in a browser - same permissions, same screens, same
 * everything. Keeping the WebView on a normal session cookie is what stops the app from becoming a
 * second, divergent implementation of Formulize.
 *
 * It is also the recovery path: when a session expires mid-use, the app detects the redirect to
 * /user.php, silently calls this endpoint again, and reloads the page the user was on. The user
 * never sees a login screen for an ordinary timeout.
 *
 * 2FA: a device token marked tfa_trusted skips the challenge (see verify.php for why that is
 * defensible). A token without it, belonging to an account that uses 2FA, is challenged here on
 * every session - which is the "keep asking me for a code even though my password is saved"
 * option the user chose when adding the site.
 */

$xoopsOption['ignore_closed_site'] = true;

require_once "../../../mainfile.php";
include_once XOOPS_ROOT_PATH . '/modules/formulize/app/common.php';
formulize_app_bootstrap();
include_once XOOPS_ROOT_PATH . '/include/2fa/manage.php';

formulize_app_requirePost();

global $icmsConfig;

$body = formulize_app_readJsonBody();
$deviceToken = formulize_app_field($body, 'device_token');

if ($deviceToken === '') {
    formulize_app_fail('missing_token', 'No device token was supplied.', 400);
}

$device = formulize_app_validateDeviceToken($deviceToken);
if (!$device) {
    // Unknown, revoked and expired are deliberately indistinguishable: the app's response to all
    // three is the same (ask the user to sign in again).
    formulize_app_fail('token_rejected', 'Please sign in again.', 401);
}

$member_handler = xoops_gethandler('member');
$user = $member_handler->getUser($device['uid']);
if (!is_object($user) || $user->getVar('level') <= 0) {
    formulize_app_fail('account_inactive', 'That account is not active.', 403);
}

// Closed-site rule, mirroring include/checklogin.php. Checked on every exchange, not just at
// sign-in, so closing a site actually locks out app users who already hold a token.
if ($icmsConfig['closesite'] == 1) {
    $allowed = false;
    foreach ($user->getGroups() as $group) {
        if (in_array($group, $icmsConfig['closesite_okgrp']) || ICMS_GROUP_ADMIN == $group) {
            $allowed = true;
            break;
        }
    }
    if (!$allowed) {
        formulize_app_fail('site_closed', 'This site is currently closed.', 403);
    }
}

// Untrusted device on a 2FA account: challenge before handing out a session.
if (!$device['tfa_trusted'] && $method = user2FAMethod($user)) {
    $sendError = false;
    switch ($method) {
        case TFA_SMS:
            $profile_handler = xoops_getmodulehandler('profile', 'profile');
            $profile = $profile_handler->get($device['uid']);
            $contact = preg_replace('/[^0-9]/', '', $profile->getVar('2faphone'));
            $methodName = 'sms';
            $sendError = sendCode(TFA_SMS, $device['uid']);
            break;
        case TFA_APP:
            $contact = '';
            $methodName = 'app';
            break;
        default:
            $contact = $user->getVar('email');
            $methodName = 'email';
            $sendError = sendCode(TFA_EMAIL, $device['uid']);
    }
    if ($sendError) {
        formulize_app_fail('code_send_failed', 'We could not send your verification code. Please try again shortly.', 502);
    }
    formulize_app_respond(array(
        'status' => '2fa_required',
        'uid' => intval($device['uid']),
        'method' => $methodName,
        'contact_hint' => formulize_app_maskContact($contact, $methodName),
        'challenge_token' => formulize_app_signChallenge($device['uid'], time() + FORMULIZE_APP_CHALLENGE_LIFETIME),
    ));
}

formulize_app_startSession($user);

// The session cookie is also returned in the body, not only in the Set-Cookie header.
//
// This is deliberate and is not the HttpOnly leak it looks like. HttpOnly exists to keep a cookie
// away from JavaScript running in a *page*, where cross-site scripting could steal it. The caller
// here is the app's own native networking code, not a web page, and it genuinely needs the value:
// on Android a cookie received by the app's HTTP client does not land in the WebView's separate
// cookie jar, so the app must write it there itself before the first page load. The header remains
// HttpOnly for anything that does share a jar, and the value is no more exposed than the header
// the same client just received.
$cookieName = ($icmsConfig['use_mysession'] && $icmsConfig['session_name'] != '')
    ? $icmsConfig['session_name']
    : session_name();

formulize_app_respond(array(
    'status' => 'ok',
    'user' => array(
        'uid' => intval($user->getVar('uid')),
        'name' => $user->getVar('uname'),
        // Same call the theme layer uses for $xoops_isadmin (libraries/icms/view/theme/Object.php),
        // so the app's Admin menu entry appears in exactly the same circumstances as the website's.
        'is_admin' => (bool) $user->isAdmin(),
    ),
    'session_cookie' => array(
        'name' => $cookieName,
        'value' => session_id(),
    ),
    'start_url' => XOOPS_URL . '/modules/formulize/',
));
