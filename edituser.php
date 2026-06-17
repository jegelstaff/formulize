<?php
/**
 * Self-service account editing.
 *
 * Renders the Formulize user-account profile form for the currently logged-in user. Every path
 * to editing one's own account — this page, the legacy profile module (which now hands off
 * here), and any old bookmark — goes through this same form and the same security, so a stale
 * link or a poked-at URL can never reach a different, weaker editing path.
 *
 * Self-edit only: the logged-in user always edits their own account, and any uid passed in the
 * request is ignored. Admins manage other users via modules/formulize/users.php.
 *
 * @package Formulize
 */

$xoopsOption['pagetype'] = 'user';
include 'mainfile.php';

if (!is_object(icms::$user)) {
	redirect_header('index.php', 3, _US_NOEDITRIGHT);
	exit();
}

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';

// Self only: always edit the logged-in user; ignore any uid in the request.
$uid = intval(icms::$user->getVar('uid'));

// Resolve the form/entry/screen for this user: their entries-are-users entry (if they can edit
// it) or the System Users form, where entry_id is the uid. Privileged fields are hidden via
// ele_display and refused server-side for non-webmasters (see userAccountElement /
// GroupMembershipService), so the System Users defaultform is safe to render for any user.
list($fid, $entry_id, $sid) = formulize_resolveUserAccountScreen($uid);

if (!$sid) {
	redirect_header(XOOPS_URL, 3, _US_NOEDITRIGHT);
	exit();
}

include_once XOOPS_ROOT_PATH . '/header.php';

// Process any submitted account data before rendering (save-before-display), mirroring
// initialize.php and users.php. The rendered form posts back here with its own hidden fields.
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/readelements.php';

// Render the resolved form screen for this entry.
$screen_handler = xoops_getmodulehandler('screen', 'formulize');
$thisScreen = $screen_handler->get($sid);
$type_handler = xoops_getmodulehandler($thisScreen->getVar('type') . 'Screen', 'formulize');
$type_handler->render($type_handler->get($sid), $entry_id, '');

include XOOPS_ROOT_PATH . '/footer.php';
