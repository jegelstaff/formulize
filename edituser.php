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

if (!is_object($xoopsUser)) {
	redirect_header('index.php', 3, _US_NOEDITRIGHT);
	exit();
}

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';

// Self only: always edit the logged-in user; ignore any uid in the request.
$uid = intval($xoopsUser->getVar('uid'));

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

global $xoTheme;
if($xoTheme) {
    $cssVersion = formulize_get_file_version('/modules/formulize/templates/css/formulize.css');
		$jsVersion = formulize_get_file_version('/modules/formulize/libraries/formulize.js');
    $xoTheme->addStylesheet("/modules/formulize/templates/css/formulize.css?v=".$cssVersion);
    $xoTheme->addScript("/modules/formulize/libraries/formulize.js?v=".$jsVersion);
}

// Process any submitted account data before rendering (save-before-display), mirroring
// initialize.php and users.php. The rendered form posts back here with its own hidden fields.
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/readelements.php';

// Render the resolved form screen for this entry.
// Strip out certain things, more stuff if it's the system user form
$screen_handler = xoops_getmodulehandler('screen', 'formulize');
$thisScreen = $screen_handler->get($sid);
$type_handler = xoops_getmodulehandler($thisScreen->getVar('type') . 'Screen', 'formulize');
$thisScreen = $type_handler->get($sid);

// customize the screen for the Edit Account situation
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($thisScreen->getVar('fid'));

// count pages
list($pages, $pageTitles, $pageConditions) = $type_handler->traverseScreenPages($thisScreen);

if(count($pages) == 1) {
	$thisScreen->setVar('pagetitles', array(0 => _US_EDITPROFILE));
	$thisScreen->setVar('showpagetitles', 1);
	$thisScreen->setVar('navstyle', 3); // buttons only
	$thisScreen->setVar('showpageindicator', 2); // off
	$thisScreen->setVar('showpageselector', 2); // off
}

// limit to the Save button
$thisScreen->setVar('buttontext', array(
	'thankyoulinktext'=>'',
	'leaveButtonText'=>'',
	'prevButtonText'=>'',
	'saveButtonText'=>_formulize_SAVE,
	'nextButtonText'=>'',
	'finishButtonText'=>'',
	'printableViewButtonText'=>'',
	'closeButtonText'=>''
));

$type_handler->render($thisScreen, $entry_id, '');

include XOOPS_ROOT_PATH . '/footer.php';
