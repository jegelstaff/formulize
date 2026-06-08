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

// System Users management page.
// Displays a list-of-entries view for system users,
// optionally merged with entries_are_users form data.

require_once "../../mainfile.php";

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/usersAndGroups.php';

global $xoopsUser;

// SECURITY: This page MUST remain restricted to users with system_admin + XOOPS_SYSTEM_USER
// permission (effectively webmasters). Do NOT relax this to allow access based on
// entries-are-users edit rights alone.
//
// The reason: the system users table form treats entry_id as uid directly. If a
// non-webmaster could reach this page and submit the form, processUserAccountSubmission()
// would resolve the target uid from entry_id without any per-user permission check
// beyond the form-level system_admin gate. Filtering the displayed list to "only users
// this person can edit via EAU forms" is not feasible with a simple SQL filter.
// Non-webmasters who need to manage a subset of users should do so through the
// entries-are-users forms directly, where per-entry permission checks apply normally.
$gperm_handler = xoops_gethandler('groupperm');
$groups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
$canManageUsers = $gperm_handler->checkRight('system_admin', XOOPS_SYSTEM_USER, $groups);

if (!$canManageUsers) {
	redirect_header(XOOPS_URL, 3, 'You do not have permission to manage users.');
	exit();
}

$fid = ensureUsersTableForm();

if (!$fid) {
	throw new Exception('Could not initialize the system table form.');
}

// EAU entry routing: if viewing a users entry and the user has an EAU form entry,
// reroute ventry to the EAU entry and use the EAU form's default screen for display.
$eauViewEntryScreenId = 0;
$form_handler = xoops_getmodulehandler('forms', 'formulize');
if (!empty($_POST['ventry']) && is_numeric($_POST['ventry'])) {
	$eauFids = getEntriesAreUsersForms();
	// primaryfid is written by writeHiddenSettings when displayForm renders an EAU entry.
	// If it matches an EAU form, ventry is already the EAU entry_id — skip the uid lookup.
	if (!empty($_POST['primaryfid']) && in_array(intval($_POST['primaryfid']), $eauFids)) {
		if ($eauFormObj = $form_handler->get(intval($_POST['primaryfid']))) {
			$eauViewEntryScreenId = intval($eauFormObj->getVar('defaultform'));
		}
	} else {
		$eauMatches = findUserEauEntry(intval($_POST['ventry']));
		if (!empty($eauMatches)) {
			$firstMatch = $eauMatches[0];
			$_POST['ventry'] = $firstMatch['entry_id'];
			if ($eauFormObj = $form_handler->get($firstMatch['fid'])) {
				$eauViewEntryScreenId = intval($eauFormObj->getVar('defaultform'));
			}
		}
	}
}

// Now safe to start page output
include_once XOOPS_ROOT_PATH . '/header.php';

global $xoTheme;
if ($xoTheme) {
	$cssVersion = formulize_get_file_version('/modules/formulize/templates/css/formulize.css');
	$jsVersion = formulize_get_file_version('/modules/formulize/libraries/formulize.js');
	$xoTheme->addStylesheet("/modules/formulize/templates/css/formulize.css?v=" . $cssVersion);
	$xoTheme->addScript("/modules/formulize/libraries/formulize.js?v=" . $jsVersion);
}

// Set composite data mode so formulize_gatherDataSet() delegates to our composite function.
// Set the permission flag so low-level form checks know system_admin was already verified above.
$GLOBALS['formulize_compositeDataMode'] = 'users';
$GLOBALS['formulize_systemAdminPermissionVerified'] = true;

$formObject = $form_handler->get($fid);

// Build a pseudo list-of-entries screen to leverage standard entriesdisplay machinery
// while customising button visibility. For the view-entry case, displayEntries routes
// to the EAU form's defaultform screen (if any), or falls back to the system users
// form's own defaultform screen via determineViewEntryScreen.
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/listOfEntriesScreen.php';
$screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
$pseudoScreen = $screen_handler->create();
$screen_handler->setDefaultListScreenVars($pseudoScreen, $eauViewEntryScreenId, $formObject);
// Override: no framework for ad hoc table forms
$pseudoScreen->setVar('title', 'All Users');
$pseudoScreen->setVar('frid', 0);
$pseudoScreen->setVar('textwidth', 0);
// Suppress buttons not appropriate for user management
$pseudoScreen->setVar('useclone', '');
$pseudoScreen->setVar('usedelete', _formulize_DE_DELETE_USER_BUTTON);
$pseudoScreen->setVar('useexport', '');
$pseudoScreen->setVar('useimport', '');
$pseudoScreen->setVar('usechangeowner', '');
$pseudoScreen->setVar('useaddmultiple', '');
$pseudoScreen->setVar('useaddproxy', '');
$pseudoScreen->setVar('usenotifications', '');
$pseudoScreen->setVar('usecalcs', '');
$pseudoScreen->setVar('useadvcalcs', '');
$pseudoScreen->setVar('useexportcalcs', '');
$pseudoScreen->setVar('usecurrentviewlist', '');
$pseudoScreen->setVar('useselectall', '');
$pseudoScreen->setVar('useclearall', '');
$pseudoScreen->setVar('useaddupdate', _AM_FORMULIZE_ADD_USER);

// Set advanceview to define the default columns shown on first load.
// advanceview format: array of [handle, search_value, sort_direction, search_type] per column.
// This bypasses the headerlist→getDefaultCols chain and lets entriesdisplay.php
// use our explicit column list directly.
// Include the Type column only when EAU forms exist (i.e. both regular and EAU users are present).
$fidInt = intval($fid);
$advanceViewArray = array(
	array('formulize_user_account_fullname_'   . $fidInt, '', 0, 'Box'),
	array('formulize_user_account_username_'   . $fidInt, '', 0, 'Box'),
	array('formulize_user_account_email_'      . $fidInt, '', 0, 'Box'),
	array('formulize_user_account_phone_'      . $fidInt, '', 0, 'Box'),
);
$advanceViewArray[] = array('formulize_user_account_status_'     . $fidInt, '', 0, 'Box');
$advanceViewArray[] = array('formulize_user_account_masquerade_' . $fidInt, '', 0, 'Box');
if (!empty(getEntriesAreUsersForms())) {
	$advanceViewArray[] = array('eau_type', '', 0, 'Filter');
}
$pseudoScreen->setVar('advanceview', $advanceViewArray);

// Inject fullname and username as hidden values on each row so confirmDel() can
// display the user's name without a separate lookup.
// Also mark status as an editable inline column (decolumns).
$pseudoScreen->setVar('hiddencolumns', array(
	'formulize_user_account_fullname_'  . $fidInt,
	'formulize_user_account_username_'  . $fidInt,
));
$pseudoScreen->setVar('decolumns', array(
	'formulize_user_account_status_'     . $fidInt,
	'formulize_user_account_masquerade_' . $fidInt,
));

// Handle user deletion before readelements.php and render, so Stage 2 never fires.
if (!empty($_POST['delconfirmed'])) {
	$currentUid = $xoopsUser ? intval($xoopsUser->getVar('uid')) : 0;
	$currentUserIsWebmaster = $xoopsUser && in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups());
	foreach ($_POST as $key => $val) {
		if (strpos($key, 'delete_') === 0) {
			$uidToDelete = intval(substr($key, 7));
			if ($uidToDelete <= 0 || $uidToDelete === $currentUid) {
				continue;
			}
			// Webmasters can delete anyone; others must have edit rights on the
			// EAU entry associated with the user being deleted.
			if (!$currentUserIsWebmaster) {
				$eauMatches = findUserEauEntry($uidToDelete);
				$canDelete = false;
				foreach ($eauMatches as $match) {
					if (formulizePermHandler::user_can_edit_entry($match['fid'], $currentUid, $match['entry_id'])) {
						$canDelete = true;
						break;
					}
				}
				if (!$canDelete) {
					continue;
				}
			}
			$xoopsDB->query("DELETE FROM " . $xoopsDB->prefix('users') . " WHERE uid = $uidToDelete");
			$xoopsDB->query("DELETE FROM " . $xoopsDB->prefix('groups_users_link') . " WHERE uid = $uidToDelete");
		}
	}
	unset($_POST['delconfirmed']);
}

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/readelements.php";
$screen_handler->render($pseudoScreen, '', '');

// Override confirmDel() to show user-specific warning with full name.
// Names are read from hidden inputs injected per row via hiddencolumns.
echo '<script>
function confirmDel() {
	var checked = jQuery("input[name^=\'delete_\']:checked");
	if (checked.length === 0) { return false; }
	var fid = ' . $fidInt . ';
	var names = [];
	checked.each(function() {
		var uid = parseInt(this.name.replace("delete_", ""), 10);
		var fullName = jQuery("input[name=\'hiddencolumn_" + uid + "_formulize_user_account_fullname_" + fid + "\']").val() || "";
		var username = jQuery("input[name=\'hiddencolumn_" + uid + "_formulize_user_account_username_" + fid + "\']").val() || "";
		names.push(fullName || username || uid);
	});
	var fullName = names.join(", ");
	var msgTemplate = ' . json_encode(_formulize_DE_DELETE_USER_CONFIRM) . ';
	var msg = msgTemplate.replace("%s", fullName);
	var answer = confirm(msg);
	if (answer) {
		window.document.controls.delconfirmed.value = 1;
		window.document.controls.ventry.value = "";
		showLoading();
		return true;
	} else {
		return false;
	}
}
</script>';

include XOOPS_ROOT_PATH . '/footer.php';
