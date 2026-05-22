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

// Entry point for the Users and Groups management pages.
// Displays a list-of-entries view for system users or groups,
// optionally merged with entries_are_users or entries_are_groups form data.

require_once "../../mainfile.php";

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/usersAndGroups.php';

global $xoopsUser;

// Determine which view to show (needed before any early redirects)
$view = isset($_GET['view']) ? $_GET['view'] : 'users';
if ($view != 'users' && $view != 'groups') {
	$view = 'users';
}

// SECURITY: The users view on this page MUST remain restricted to users with
// system_admin + XOOPS_SYSTEM_USER permission (effectively webmasters). Do NOT
// relax this to allow access based on entries-are-users edit rights alone.
//
// The reason: the system users table form treats entry_id as uid directly. If a
// non-webmaster could reach the users view and submit the form, processUserAccountSubmission()
// would resolve the target uid from entry_id without any per-user permission check
// beyond the form-level system_admin gate. Filtering the displayed list to "only users
// this person can edit via EAU forms" is not feasible with a simple SQL filter.
// Non-webmasters who need to manage a subset of users should do so through the
// entries-are-users forms directly, where per-entry permission checks apply normally.
$gperm_handler = xoops_gethandler('groupperm');
$groups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
$canManageUsers = $gperm_handler->checkRight('system_admin', XOOPS_SYSTEM_USER, $groups);
$canManageGroups = $gperm_handler->checkRight('system_admin', XOOPS_SYSTEM_GROUP, $groups);

if ($view == 'users' && !$canManageUsers) {
	if ($canManageGroups) {
		$view = 'groups';
	} else {
		redirect_header(XOOPS_URL, 3, 'You do not have permission to manage users or groups.');
		exit();
	}
}
if ($view == 'groups' && !$canManageGroups) {
	if ($canManageUsers) {
		$view = 'users';
	} else {
		redirect_header(XOOPS_URL, 3, 'You do not have permission to manage users or groups.');
		exit();
	}
}

// Register the ad hoc table form
if ($view == 'users') {
	$fid = ensureUsersTableForm();
} else {
	$fid = ensureGroupsTableForm();
}

if (!$fid) {
	redirect_header(XOOPS_URL, 3, 'Error: could not initialize the system table form.');
	exit();
}

// EAU entry routing: if viewing a users entry and the user has an EAU form entry,
// reroute ventry to the EAU entry and use the EAU form's default screen for display.
$eauViewEntryScreenId = 0;
if (!empty($_POST['ventry']) && is_numeric($_POST['ventry']) && $view == 'users') {
	$eauFids = getEntriesAreUsersForms();
	$eau_form_handler = xoops_getmodulehandler('forms', 'formulize');
	// primaryfid is written by writeHiddenSettings when displayForm renders an EAU entry.
	// If it matches an EAU form, ventry is already the EAU entry_id — skip the uid lookup.
	if (!empty($_POST['primaryfid']) && in_array(intval($_POST['primaryfid']), $eauFids)) {
		if ($eauFormObj = $eau_form_handler->get(intval($_POST['primaryfid']))) {
			$eauViewEntryScreenId = intval($eauFormObj->getVar('defaultform'));
		}
	} else {
		$eauMatches = findUserEauEntry(intval($_POST['ventry']));
		if (!empty($eauMatches)) {
			$firstMatch = $eauMatches[0];
			$_POST['ventry'] = $firstMatch['entry_id'];
			if ($eauFormObj = $eau_form_handler->get($firstMatch['fid'])) {
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
$GLOBALS['formulize_compositeDataMode'] = $view;
$GLOBALS['formulize_systemAdminPermissionVerified'] = true;

$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($fid);

// Ensure the system users form has a persisted multiPageScreen as its defaultform.
// When a user without an EAU entry is clicked, displayEntries routes through
// determineViewEntryScreen → formObject->defaultform, using the modern screen-based
// rendering path with full settings propagation.
// Also sync the screen's pages whenever the form's element list changes (e.g. when a new
// extraElement is added to ensureUsersTableForm after the screen was first created).
if ($view == 'users') {
	global $xoopsDB;
	include_once XOOPS_ROOT_PATH . '/modules/formulize/class/multiPageScreen.php';
	$mp_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
	$allElementIds = array_values($formObject->getVar('elements'));
	if (!$formObject->getVar('defaultform')) {
		$newScreen = $mp_handler->create();
		$mp_handler->setDefaultFormScreenVars($newScreen, $formObject);
		$newScreen->setVar('frid', 0);
		$newScreen->setVar('pages', serialize(array(0 => $allElementIds)));
		if ($mp_handler->insert($newScreen)) {
			$sid = intval($newScreen->getVar('sid'));
			$xoopsDB->queryF("UPDATE " . $xoopsDB->prefix("formulize_id") . " SET defaultform = $sid WHERE id_form = " . intval($fid));
			$formObject->setVar('defaultform', $sid);
		}
	} else {
		// Sync pages to include any elements added after the screen was created.
		// Use a direct SQL UPDATE on just the pages column to avoid routing through
		// the screen handler's insert(), which uses query() (not queryF()) for UPDATEs.
		$sid = intval($formObject->getVar('defaultform'));
		$pagesRow = $xoopsDB->queryF(
			"SELECT pages FROM " . $xoopsDB->prefix('formulize_screen_multipage') .
			" WHERE sid = $sid"
		);
		if ($pagesRow && $row = $xoopsDB->fetchArray($pagesRow)) {
			$screenPages = @unserialize($row['pages']);
			$screenElementIds = array();
			if (is_array($screenPages)) {
				foreach ($screenPages as $pageElements) {
					if (is_array($pageElements)) {
						$screenElementIds = array_merge($screenElementIds, $pageElements);
					}
				}
			}
			$sortedAll = $allElementIds;
			sort($sortedAll);
			sort($screenElementIds);
			if ($sortedAll !== $screenElementIds) {
				$xoopsDB->queryF(
					"UPDATE " . $xoopsDB->prefix('formulize_screen_multipage') .
					" SET pages = " . $xoopsDB->quoteString(serialize(array(0 => $allElementIds))) .
					" WHERE sid = $sid"
				);
			}
		}
	}
}

// Build a pseudo list-of-entries screen to leverage standard entriesdisplay machinery
// while customising button visibility. For the view-entry case, displayEntries routes
// to the EAU form's defaultform screen (if any), or falls back to the system users
// form's own defaultform screen via determineViewEntryScreen.
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/listOfEntriesScreen.php';
$screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
$pseudoScreen = $screen_handler->create();
$screen_handler->setDefaultListScreenVars($pseudoScreen, $eauViewEntryScreenId, $formObject);
// Override: no framework for ad hoc table forms
$pseudoScreen->setVar('frid', 0);
// Suppress buttons not appropriate for user/group management
$pseudoScreen->setVar('useclone', '');
$pseudoScreen->setVar('usedelete', $view == 'users' ? _formulize_DE_DELETE_USER_BUTTON : '');
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
// Set add button label: Add User for users view, none for groups view
$pseudoScreen->setVar('useaddupdate', $view == 'users' ? _AM_FORMULIZE_ADD_USER : '');

// Set advanceview to define the default columns shown on first load.
// advanceview format: array of [handle, search_value, sort_direction, search_type] per column.
// This bypasses the headerlist→getDefaultCols chain and lets entriesdisplay.php
// use our explicit column list directly.
if ($view == 'users') {
	$fidInt = intval($fid);
	$defaultHandles = array(
		'formulize_user_account_uid_'       . $fidInt,
		'formulize_user_account_firstname_' . $fidInt,
		'formulize_user_account_username_'  . $fidInt,
		'formulize_user_account_email_'     . $fidInt,
		'formulize_user_account_phone_'     . $fidInt,
		'formulize_user_account_status_'    . $fidInt,
	);
} else {
	// Groups: show all elements in the order they were created.
	global $xoopsDB;
	$groupEleResult = $xoopsDB->query(
		"SELECT ele_handle FROM " . $xoopsDB->prefix('formulize') .
		" WHERE id_form = " . intval($fid) . " ORDER BY ele_order ASC"
	);
	$defaultHandles = array();
	while ($groupEleRow = $xoopsDB->fetchArray($groupEleResult)) {
		$defaultHandles[] = $groupEleRow['ele_handle'];
	}
}
$advanceViewArray = array();
foreach ($defaultHandles as $avHandle) {
	$advanceViewArray[] = array($avHandle, '', 0, 'Box');
}
$pseudoScreen->setVar('advanceview', $advanceViewArray);

// Inject firstname and username as hidden values on each row so confirmDel() can
// display the user's name without a separate lookup.
// Also mark status as an editable inline column (decolumns).
if ($view == 'users') {
	$fidInt = intval($fid);
	$pseudoScreen->setVar('hiddencolumns', array(
		'formulize_user_account_firstname_' . $fidInt,
		'formulize_user_account_username_'  . $fidInt,
	));
	$pseudoScreen->setVar('decolumns', array(
		'formulize_user_account_status_' . $fidInt,
	));
}

// Handle user deletion before readelements.php and render, so Stage 2 never fires.
if ($view == 'users' && !empty($_POST['delconfirmed'])) {
	$currentUid = $xoopsUser ? intval($xoopsUser->getVar('uid')) : 0;
	foreach ($_POST as $key => $val) {
		if (strpos($key, 'delete_') === 0) {
			$uidToDelete = intval(substr($key, 7));
			if ($uidToDelete > 0 && $uidToDelete !== $currentUid) {
				$xoopsDB->queryF("DELETE FROM " . $xoopsDB->prefix('users') . " WHERE uid = $uidToDelete");
				$xoopsDB->queryF("DELETE FROM " . $xoopsDB->prefix('groups_users_link') . " WHERE uid = $uidToDelete");
			}
		}
	}
	unset($_POST['delconfirmed']);
}

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/readelements.php";
$screen_handler->render($pseudoScreen, '', '');

// Override confirmDel() to show user-specific warning with full name.
// Names are read from hidden inputs injected per row via hiddencolumns.
if ($view == 'users') {
	$fidInt = intval($fid);
	echo '<script>
function confirmDel() {
	var checked = jQuery("input[name^=\'delete_\']:checked");
	if (checked.length === 0) { return false; }
	var fid = ' . $fidInt . ';
	var names = [];
	checked.each(function() {
		var uid = parseInt(this.name.replace("delete_", ""), 10);
		var firstName = jQuery("input[name=\'hiddencolumn_" + uid + "_formulize_user_account_firstname_" + fid + "\']").val() || "";
		var username  = jQuery("input[name=\'hiddencolumn_" + uid + "_formulize_user_account_username_"  + fid + "\']").val() || "";
		names.push(firstName || username || uid);
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
}

include XOOPS_ROOT_PATH . '/footer.php';
