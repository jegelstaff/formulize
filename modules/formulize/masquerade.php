<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
###############################################################################
##  Author of this file: Formulize Incorporated                              ##
##  Project: Formulize                                                       ##
###############################################################################

// Masquerade endpoint: lets an authorized user log in as another user.
// Webmasters are always allowed. Non-webmasters are allowed if they have
// edit permission on the target user's EAU entry.
// Replicates the session-switch logic from modules/profile/admin/user.php.

require_once "../../mainfile.php";

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/usersAndGroups.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/usersGroupsPerms.php';

global $xoopsUser, $icmsConfig;

$currentGroups = $xoopsUser ? $xoopsUser->getGroups() : array();
$currentUid    = $xoopsUser ? intval($xoopsUser->getVar('uid')) : 0;
$targetUid     = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
$isWebmaster   = in_array(XOOPS_GROUP_ADMIN, $currentGroups);

if (!$targetUid || $targetUid === $currentUid) {
	redirect_header(XOOPS_URL . '/modules/formulize/users.php', 1, '');
	exit();
}

$member_handler = xoops_gethandler('member');
$targetUser = $member_handler->getUser($targetUid);

if (!$targetUser) {
	redirect_header(XOOPS_URL . '/modules/formulize/users.php', 3, 'User not found.');
	exit();
}

// Never allow a non-webmaster to masquerade as a webmaster -- that would be a straight
// privilege escalation (a manager with edit_other_entries on an entries-are-users form can
// edit an admin's EAU record, so EAU-edit rights alone must NOT be sufficient to become an
// admin). Only a webmaster may masquerade as another webmaster.
$targetIsWebmaster = in_array(XOOPS_GROUP_ADMIN, $targetUser->getGroups());

// Permission check: webmasters always allowed; others need edit access on
// the target user's EAU entry in at least one entries-are-users form.
$allowed = $isWebmaster;
if (!$allowed && !$targetIsWebmaster) {
	$eauMatches = findUserEauEntry($targetUid);
	foreach ($eauMatches as $match) {
		if (formulizePermHandler::user_can_edit_entry($match['fid'], $currentUid, $match['entry_id'])) {
			$allowed = true;
			break;
		}
	}
}

if (!$allowed) {
	redirect_header(XOOPS_URL . '/', 3, 'You do not have permission to masquerade as this user.');
	exit();
}

// Save the real user's uid so the "Revert" button can restore it.
if (!isset($_SESSION['masquerade_xoopsUserId'])) {
	$_SESSION['masquerade_xoopsUserId'] = $_SESSION['xoopsUserId'];
}

// Switch session to target user (mirrors profile/admin/user.php masquerade case).
$_SESSION['xoopsUserId']        = $targetUser->getVar('uid');
$_SESSION['xoopsUserGroups']    = $targetUser->getGroups();
$_SESSION['xoopsUserLastLogin'] = $targetUser->getVar('last_login');
$_SESSION['xoopsUserLanguage']  = $targetUser->language();
if (isset($_SESSION['XOOPS_TOKEN_SESSION'])) {
	unset($_SESSION['XOOPS_TOKEN_SESSION']);
}

$xoops_user_theme = $targetUser->getVar('theme');
if (in_array($xoops_user_theme, $icmsConfig['theme_set_allowed'])) {
	$_SESSION['xoopsUserTheme'] = $xoops_user_theme;
} elseif (isset($_SESSION['xoopsUserTheme'])) {
	unset($_SESSION['xoopsUserTheme']);
}

header('Location: ' . XOOPS_URL . '/');
exit();
