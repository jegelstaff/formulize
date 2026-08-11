<?php
/**
 * Password sign-in for the Formulize mobile app.
 *
 * Checks a username and password and returns one of three things:
 *   - a device token, when the account has no 2FA
 *   - a 2FA challenge, when it does (the code is sent here, and verify.php finishes the job)
 *   - a failure
 *
 * This is the website's include/checklogin.php flow expressed as data instead of redirects, and it
 * deliberately reuses the same authentication and 2FA machinery so the two can never disagree
 * about who may sign in.
 *
 * SECURITY NOTE: this is an unauthenticated endpoint that checks passwords, so it is a password
 * guessing surface and is rate limited below. Nothing that already existed covered it - the limits
 * in include/2fa/manage.php count wrong 2FA *codes*, which only come into play after a password
 * has already been accepted.
 */

// Must stay reachable on a closed site so an administrator can still sign in; the closed-site
// access rule is then enforced explicitly below, exactly as include/checklogin.php does it.
$xoopsOption['ignore_closed_site'] = true;

require_once "../../../mainfile.php";
include_once XOOPS_ROOT_PATH . '/modules/formulize/app/common.php';
formulize_app_bootstrap();
include_once XOOPS_ROOT_PATH . '/include/2fa/manage.php'; // 2FA constants, user2FAMethod(), sendCode()

formulize_app_requirePost();

global $icmsConfig;

$body = formulize_app_readJsonBody();
$username = formulize_app_field($body, 'username');
$password = formulize_app_field($body, 'password');
$deviceName = formulize_app_field($body, 'device_name', 'Mobile device');
$platform = formulize_app_field($body, 'platform');

if ($username === '' || $password === '') {
    formulize_app_fail('missing_credentials', 'Enter your username and password.', 400);
}

// Count this attempt against both limits BEFORE checking the password, and evaluate both counters
// rather than short-circuiting: if the || stopped at the first one, the second counter would never
// advance and could be kept frozen indefinitely.
$userKey = 'login:user:' . strtolower($username);
$ipKey = 'login:ip:' . formulize_app_clientIp();
$userExceeded = formulize_app_throttleExceeded($userKey, FORMULIZE_APP_LOGIN_MAX_PER_USER, FORMULIZE_APP_LOGIN_WINDOW);
$ipExceeded = formulize_app_throttleExceeded($ipKey, FORMULIZE_APP_LOGIN_MAX_PER_IP, FORMULIZE_APP_LOGIN_WINDOW);
if ($userExceeded || $ipExceeded) {
    formulize_app_fail(
        'rate_limited',
        'Too many sign-in attempts. Please wait a few minutes and try again.',
        429
    );
}

// Same authentication entry point the website uses (include/checklogin.php, include/2fa/challenge.php).
icms_loadLanguageFile('core', 'auth');
$icmsAuth =& icms_auth_Factory::getAuthConnection(icms_core_DataFilter::addSlashes($username));
$user =& $icmsAuth->authenticate($username, $password);

if (!is_object($user)) {
    // Deliberately the same answer whether the username exists or not, so this cannot be used to
    // work out which accounts are real.
    formulize_app_fail('invalid_credentials', 'That username and password did not match.', 401);
}

if ($user->getVar('level') <= 0) {
    formulize_app_fail('account_inactive', 'That account is not active.', 403);
}

// Closed-site rule, mirroring include/checklogin.php: when the site is closed only members of the
// permitted groups (and administrators) may sign in.
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

$uid = $user->getVar('uid');

// Does this account use 2FA? Only asked once the password has been accepted, so the answer never
// leaks anything about an account to someone who does not already hold its password.
//
// Note that the website's "remember this device" cookie (userRemembersDevice) is deliberately NOT
// consulted here. That mechanism is for browsers and is bound to the request IP, which a phone
// changes constantly. The app's equivalent is its device token, and at this point in the flow
// there isn't one yet - so an account with 2FA is always challenged on first sign-in.
$method = user2FAMethod($user);

if ($method) {
    $sendError = false;
    switch ($method) {
        case TFA_SMS:
            $profile_handler = xoops_getmodulehandler('profile', 'profile');
            $profile = $profile_handler->get($uid);
            $contact = preg_replace('/[^0-9]/', '', $profile->getVar('2faphone'));
            $methodName = 'sms';
            $sendError = sendCode(TFA_SMS, $uid);
            break;
        case TFA_APP:
            // Nothing to send: the user reads a rotating code out of their authenticator app. Also
            // matches include/2fa/challenge.php, which skips sendCode() for this method.
            $contact = '';
            $methodName = 'app';
            break;
        default:
            $contact = $user->getVar('email');
            $methodName = 'email';
            $sendError = sendCode(TFA_EMAIL, $uid);
    }

    // sendCode() returns false when it succeeded (or when it deliberately declined to resend a
    // code that is still live), and error text when delivery actually failed.
    if ($sendError) {
        formulize_app_fail(
            'code_send_failed',
            'We could not send your verification code. Please try again shortly.',
            502
        );
    }

    formulize_app_respond(array(
        'status' => '2fa_required',
        'uid' => intval($uid),
        'method' => $methodName,
        'contact_hint' => formulize_app_maskContact($contact, $methodName),
        'challenge_token' => formulize_app_signChallenge($uid, time() + FORMULIZE_APP_CHALLENGE_LIFETIME),
    ));
}

// No 2FA on this account: the password alone was enough, so issue the device token now. It is
// marked tfa_trusted = 0 because no 2FA challenge was passed - there was none to pass.
formulize_app_throttleClear($userKey);

$token = formulize_app_issueDeviceToken($uid, $deviceName, $platform, 0);
if (!$token) {
    formulize_app_fail('server_error', 'Could not complete sign-in. Please try again.', 500);
}

formulize_app_respond(array(
    'status' => 'ok',
    'device_token' => $token,
    'user' => array(
        'uid' => intval($uid),
        'name' => $user->getVar('uname'),
    ),
));
