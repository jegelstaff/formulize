<?php
/**
 * Sign out of a site from the app.
 *
 * Two things have to happen, and doing only one of them is a bug:
 *   1. the device token is revoked, so it can never be exchanged for a session again
 *   2. the current session is destroyed
 *
 * If the app instead navigated the WebView to /user.php?op=logout it would do only the second, and
 * the next launch would silently sign the user back in from the stored token - so the app treats
 * Logout as a native action and calls this, which is why the Logout entry from menu.php carries no
 * URL.
 *
 * Also called when a site is removed from the app, so that removing a site actually withdraws the
 * device's access rather than just forgetting about it locally.
 */

$xoopsOption['ignore_closed_site'] = true;

require_once "../../../mainfile.php";
include_once XOOPS_ROOT_PATH . '/modules/formulize/app/common.php';
formulize_app_bootstrap();

formulize_app_requirePost();

$body = formulize_app_readJsonBody();
$deviceToken = formulize_app_field($body, 'device_token');

// Revoking is deliberately unconditional and unauthenticated beyond holding the token itself:
// possession of the token is the only thing it grants, so anyone able to present it is entitled to
// throw it away. Requiring a live session first would mean a user whose session had already
// expired could not sign out properly.
if ($deviceToken !== '') {
    formulize_app_revokeDeviceToken($deviceToken);
}

// End the session too, if this request carried one.
if (isset($_SESSION) && !empty($_SESSION)) {
    $_SESSION = array();
    session_destroy();
}

// Always reports success. There is no useful distinction for the app between "that token was
// revoked" and "that token was already gone" - either way the device now has no access - and
// reporting the difference would let someone probe which tokens exist.
formulize_app_respond(array('status' => 'ok'));
