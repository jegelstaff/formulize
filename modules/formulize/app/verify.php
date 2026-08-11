<?php
/**
 * 2FA code verification for the Formulize mobile app - the second half of login.php.
 *
 * Takes the challenge token issued by login.php (proof that the password was accepted moments ago)
 * together with the code the user typed, and on success issues the device token.
 *
 * THE "TRUST THIS DEVICE" DECISION LIVES HERE. When the user opts in, the token is marked
 * tfa_trusted, and session.php will not challenge that device again. The justification is that the
 * phone has, right now, demonstrably satisfied both factors: the password, and a code delivered to
 * the account's own 2FA channel. Anyone holding the phone at that moment already has everything
 * needed to take the account over, so continuing to demand a code on that one device adds friction
 * without adding much protection.
 *
 * What keeps it honest, and why this is not simply "2FA off":
 *   - it applies only to this one device, never to the browser or any other device
 *   - the token is revocable server-side, and expires
 *   - the account owner is emailed when it is switched on, so it cannot be enabled silently
 */

$xoopsOption['ignore_closed_site'] = true;

require_once "../../../mainfile.php";
include_once XOOPS_ROOT_PATH . '/modules/formulize/app/common.php';
formulize_app_bootstrap();
include_once XOOPS_ROOT_PATH . '/include/2fa/manage.php'; // validateCode() and the TFA_* constants

formulize_app_requirePost();

global $icmsConfig, $xoopsDB;

$body = formulize_app_readJsonBody();
$uid = isset($body['uid']) ? intval($body['uid']) : 0;
$challengeToken = formulize_app_field($body, 'challenge_token');
$code = formulize_app_field($body, 'code');
$deviceName = formulize_app_field($body, 'device_name', 'Mobile device');
$platform = formulize_app_field($body, 'platform');
$trustDevice = !empty($body['trust_device']);

// Set when this verification is finishing a challenge raised by session.php rather than by
// login.php. That happens on every launch for a user who declined to trust the device, so without
// this the account would accumulate a new token per sign-in, none of them ever cleaned up. The app
// sends the token it is about to replace and it is revoked once the new one is issued.
$replaceToken = formulize_app_field($body, 'replace_device_token');

if (!$uid || $challengeToken === '' || $code === '') {
    formulize_app_fail('missing_fields', 'Enter the verification code that was sent to you.', 400);
}

// The challenge token is what proves the password was checked. Without this, knowing a username
// and guessing a six-digit code would be enough to get in. It is bound to this uid, so a token
// issued for one account cannot be replayed against another.
//
// Deliberately NOT consumed on failure: a mistyped code leaves the token usable, so the user can
// simply try again rather than being sent back to re-enter their password.
if (!formulize_app_verifyChallenge($challengeToken, $uid)) {
    formulize_app_fail('challenge_expired', 'That sign-in attempt has expired. Please sign in again.', 401);
}

$member_handler = xoops_gethandler('member');
$user = $member_handler->getUser($uid);
if (!is_object($user) || $user->getVar('level') <= 0) {
    formulize_app_fail('account_inactive', 'That account is not active.', 403);
}

// validateCode() carries its own rate limiting (TFA_MAX_ATTEMPTS then a TFA_LOCKOUT_SECONDS
// cooldown) and clears single-use email/SMS codes on success, so wrong-guess throttling for this
// step is already handled and is not duplicated here.
if (!validateCode($code, $uid)) {
    formulize_app_fail('invalid_code', 'That code was not correct. Please check and try again.', 401);
}

// Both factors satisfied. Clear the password throttle for this account: the user has now proved
// who they are, so earlier fumbled attempts should not keep counting against them.
formulize_app_throttleClear('login:user:' . strtolower($user->getVar('login_name')));

// Discard any leftover email/SMS codes for this user, matching what include/checklogin.php does on
// the website's login path.
//
// This matters more than it looks. validateCode() checks every code row on file for the user and
// never looks at how old any of them is - there is no expiry check in it at all, only the
// failed-attempt lockout. So an email or SMS code that was issued and never used stays valid
// indefinitely, and validateCode() will happily accept it. The website avoids accumulating those
// because checklogin.php clears them after each 2FA login; without this line the app path would
// not, and every unused code an app user was ever sent would remain a live second factor.
//
// Deliberately only on success, unlike checklogin.php which also clears on a failed attempt. An
// emailed code surviving one mistyped digit is the behaviour this endpoint wants - the same reason
// the challenge token is not consumed on failure - and the code is discarded here the moment the
// sign-in actually completes.
$xoopsDB->queryF(
    'DELETE FROM ' . $xoopsDB->prefix('tfa_codes')
    . ' WHERE uid = ' . intval($uid) . ' AND method != ' . TFA_APP
);

$token = formulize_app_issueDeviceToken($uid, $deviceName, $platform, $trustDevice ? 1 : 0);
if (!$token) {
    formulize_app_fail('server_error', 'Could not complete sign-in. Please try again.', 500);
}

// Retire the token being replaced, but only after the new one exists, so a failure above cannot
// leave the app holding nothing.
if ($replaceToken !== '') {
    formulize_app_revokeDeviceToken($replaceToken);
}

// Tell the account owner that a device was trusted. Best effort on purpose: the user has properly
// earned this token, so a mail server problem must not block their sign-in. The outcome is
// reported back so the app can say "we couldn't send the confirmation email" if it wants to.
$notified = false;
if ($trustDevice) {
    $mailer = new icms_messaging_Handler();
    $mailer->useMail();
    $mailer->setToUsers($user);
    $mailer->setTemplate('app_device_trusted.tpl');
    $mailer->assign('SITENAME', $icmsConfig['sitename']);
    $mailer->assign('ADMINMAIL', $icmsConfig['adminmail']);
    $mailer->assign('SITEURL', ICMS_URL . '/');
    $mailer->assign('IP', formulize_app_clientIp());
    $mailer->assign('DEVICE', $deviceName);
    $mailer->assign('USERNAME', $user->getVar('uname'));
    $mailer->assign('DATE', date('Y-m-d H:i'));
    $mailer->setFromEmail($icmsConfig['adminmail']);
    $mailer->setFromName($icmsConfig['sitename']);
    $mailer->setSubject('A new device was trusted for sign-in to ' . $icmsConfig['sitename']);
    $notified = (bool) $mailer->send();
}

formulize_app_respond(array(
    'status' => 'ok',
    'device_token' => $token,
    'tfa_trusted' => $trustDevice ? true : false,
    'owner_notified' => $notified,
    'user' => array(
        'uid' => intval($uid),
        'name' => $user->getVar('uname'),
    ),
));
