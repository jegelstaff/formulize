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

// Functions for the Users and Groups management feature.
// Handles ad hoc table form registration, composite data gathering,
// and the permanent menu section.

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/extract.php';
include_once XOOPS_ROOT_PATH . '/modules/system/constants.php';

// ============================================================================
// REGISTRATION FUNCTIONS
// ============================================================================

/**
 * Ensure the system users table is registered as an ad hoc table form.
 *
 * Also ensures a persisted multiPageScreen exists as the defaultform for the resulting
 * form and keeps its pages list in sync with the current element set.
 *
 * @return int|false The form ID, or false on failure (cached per page load)
 */
function ensureUsersTableForm() {
	static $fid = null;
	if ($fid !== null) {
		return $fid;
	}
	global $xoopsDB;
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$fid = $form_handler->ensureAdHocTableForm(
		$xoopsDB->prefix('users'),
		'__system_users',
		'System Users',
		array(
			// Exclude all columns we don't use. login_name (Username) and
			// uname (Full Name) are intentionally kept. Columns absent from
			// the table in any given install are silently ignored.
			'excludeColumns' => array(
				'pass', 'salt', 'enc_type',
				'actkey', 'actlink', 'emailflag',
				'name', 'url', 'user_avatar', 'user_gravatar',
				'theme', 'timezone_offset', 'uorder', 'uhits',
				'notify_mode', 'user_viewemail', 'user_mailok',
				'user_sig', 'user_posts',
				'posts', 'user_from', 'bio', 'openid',
				// Actual column names in the ImpressCMS users table:
				'user_icq', 'user_aim', 'user_yim', 'user_msnm',
				'attachsig', 'rank', 'umode',
				'user_occ', 'user_intrest', 'language',
				'user_viewoid', 'pass_expired',
			),
			// Typed columns get captions and canonical ele_order from their classes.
			// source_column is automatically stored in ele_value when the canonical
			// handle differs from the DB column name.
			'columnTypes' => array(
				'uid'           => 'userAccountUid',
				'uname'         => 'userAccountFirstName',
				'login_name'    => 'userAccountUsername',
				'email'         => 'userAccountEmail',
				'level'         => 'userAccountStatus',
				'notify_method' => 'userAccountNotificationMethod',
				'user_regdate'  => 'userAccountRegistrationDate',
				'last_login'    => 'userAccountLastLogin',
			),
			// Virtual fields injected post-query (profile-backed or computed).
			// typeForCaption points to the userAccount class whose ->name is used.
			// tfa_method handle avoids leading digit (2famethod column).
			// fullname and lastname both read from uname via source_column; fullname is for the list,
			// lastname is for the edit form (both split the uname value at display/load time).
			'extraElements' => array(
				array('handle' => 'fullname',  'typeForCaption' => 'userAccountFullName',  'source_column' => 'uname'),
				array('handle' => 'lastname',  'typeForCaption' => 'userAccountLastName',  'source_column' => 'uname'),
				array('handle' => 'phone',            'typeForCaption' => 'userAccountPhone'),
				array('handle' => 'tfa_method',       'typeForCaption' => 'userAccount2FA'),
				array('handle' => 'timezone',         'typeForCaption' => 'userAccountTimezone'),
				array('handle' => 'group_memberships','typeForCaption' => 'userAccountGroupMembership'),
				array('handle' => 'password',         'typeForCaption' => 'userAccountPassword'),
				array('handle' => 'masquerade',       'typeForCaption' => 'userAccountMasquerade', 'description' => _formulize_UA_MASQUERADE_HELP),
				array('handle' => 'eau_type', 'caption' => 'Type', 'virtual' => true, 'type' => 'userEauType'),
			),
			// Default visible columns for use if/when for is displayed without defined columns from a screen
			'defaultColumns' => array('uname', 'login_name', 'email', 'phone', 'eau_type', 'level'),
		)
	);
	// Ensure a persisted multiPageScreen exists as the defaultform for this form.
	// When a user without an EAU entry is clicked, displayEntries routes through
	// formObject->defaultform to show the edit form with full settings propagation.
	// Sync pages whenever extraElements change (e.g. when a new element is added).
	if ($fid) {
		include_once XOOPS_ROOT_PATH . '/modules/formulize/class/multiPageScreen.php';
		$mp_handler    = xoops_getmodulehandler('multiPageScreen', 'formulize');
		$formObject    = $form_handler->get($fid);
		$allElementIds = array_values($formObject->getVar('elements'));
		if (!$formObject->getVar('defaultform')) {
			$newScreen = $mp_handler->create();
			$mp_handler->setDefaultFormScreenVars($newScreen, $formObject);
			$newScreen->setVar('frid', 0);
			$newScreen->setVar('pages', serialize(array(0 => $allElementIds)));
			if ($mp_handler->insert($newScreen, true)) {
				$sid = intval($newScreen->getVar('sid'));
				$xoopsDB->queryF("UPDATE " . $xoopsDB->prefix("formulize_id") . " SET defaultform = $sid WHERE id_form = " . intval($fid));
				// defaultform was just set via direct SQL, but $formObject is the cached instance
				// returned by get(); keep it in sync so determineViewEntryScreen() resolves correctly
				// on this same first page load (no extra DB re-fetch needed).
				$formObject->setVar('defaultform', $sid);
			}
		} else {
			$sid      = intval($formObject->getVar('defaultform'));
			$pagesRow = $xoopsDB->queryF(
				"SELECT pages FROM " . $xoopsDB->prefix('formulize_screen_multipage') . " WHERE sid = $sid"
			);
			if ($pagesRow && $row = $xoopsDB->fetchArray($pagesRow)) {
				$screenPages      = @unserialize($row['pages']);
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
	return $fid;
}

/**
 * Ensure the system groups table is registered as an ad hoc table form.
 *
 * Also ensures a persisted multiPageScreen exists as the defaultform for the resulting
 * form (containing name, description, and the members widget) and keeps the screen in sync.
 *
 * @return int|false The form ID, or false on failure (cached per page load)
 */
function ensureGroupsTableForm() {
	static $fid = null;
	if ($fid !== null) {
		return $fid;
	}
	global $xoopsDB;
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$fid = $form_handler->ensureAdHocTableForm(
		$xoopsDB->prefix('groups'),
		'__system_groups',
		'System Groups',
		array(
			'columnLabels' => array(
				'groupid'           => 'Group ID',
				'group_type'        => 'Group Type',
				'is_group_template' => 'Is Template',
				'form_id'           => 'Form ID',
				'entry_id'          => 'Entry ID',
			),
			// Typed columns get captions and canonical handles from their classes.
			'columnTypes' => array(
				'name'        => 'groupName',
				'description' => 'groupDescription',
			),
			// Virtual columns injected post-query for template groups.
			// virtual=true stores a marker in ele_value so dataExtractionTableForm
			// skips these in SELECT/ORDER BY/WHERE (they have no real DB backing).
			// type points to element class files that implement buildSearchWhereClause
			// so search terms on these columns delegate to correlated subqueries.
			'extraElements' => array(
				array('handle' => 'eag_type',         'caption' => 'Type',       'virtual' => true, 'type' => 'eagGroupType'),
				array('handle' => 'group_categories', 'caption' => 'Categories', 'virtual' => true, 'type' => 'eagGroupCategories'),
				array('handle' => 'group_entries',    'caption' => 'Instances',  'virtual' => true, 'type' => 'eagGroupEntries'),
				array('handle' => 'group_members',    'caption' => 'Members',    'virtual' => true, 'type' => 'eagGroupMembers'),
			),
			// Default visible columns; others accessible via Change Columns.
			'defaultColumns' => array('name', 'group_members', 'group_categories', 'group_entries'),
		)
	);
	// Ensure a persisted multiPageScreen exists as the defaultform for this form.
	// When a group row is clicked, displayEntries routes through formObject->defaultform
	// to show the edit form. Pages list contains only name and description elements.
	// Sync pages whenever element IDs change (e.g. after type migration renames them).
	if ($fid) {
		include_once XOOPS_ROOT_PATH . '/modules/formulize/class/multiPageScreen.php';
		$mp_handler      = xoops_getmodulehandler('multiPageScreen', 'formulize');
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		$formObject      = $form_handler->get($fid);
		$fidInt          = intval($fid);
		$desiredEleIds   = array();
		if ($nameEle = $element_handler->get('formulize_group_name_' . $fidInt)) {
			$desiredEleIds[] = intval($nameEle->getVar('ele_id'));
		}
		if ($descEle = $element_handler->get('formulize_group_description_' . $fidInt)) {
			$desiredEleIds[] = intval($descEle->getVar('ele_id'));
		}
		$membersRes = $xoopsDB->query(
			"SELECT ele_id FROM " . $xoopsDB->prefix('formulize') .
			" WHERE id_form = $fidInt AND ele_type = 'eagGroupMembers' LIMIT 1"
		);
		if ($membersRes && $membersRow = $xoopsDB->fetchArray($membersRes)) {
			$desiredEleIds[] = intval($membersRow['ele_id']);
		}
		if (!empty($desiredEleIds)) {
			if (!$formObject->getVar('defaultform')) {
				$newScreen = $mp_handler->create();
				$mp_handler->setDefaultFormScreenVars($newScreen, $formObject);
				$newScreen->setVar('frid', 0);
				$newScreen->setVar('pages', serialize(array(0 => $desiredEleIds)));
				if ($mp_handler->insert($newScreen, true)) {
					$sid = intval($newScreen->getVar('sid'));
					$xoopsDB->queryF("UPDATE " . $xoopsDB->prefix("formulize_id") . " SET defaultform = $sid WHERE id_form = $fidInt");
					// defaultform was just set via direct SQL, but $formObject is the cached instance
					// returned by get(); keep it in sync so downstream lookups resolve correctly on
					// this same first page load (no extra DB re-fetch needed).
					$formObject->setVar('defaultform', $sid);
				}
			} else {
				$sid      = intval($formObject->getVar('defaultform'));
				$pagesRow = $xoopsDB->queryF(
					"SELECT pages FROM " . $xoopsDB->prefix('formulize_screen_multipage') . " WHERE sid = $sid"
				);
				if ($pagesRow && $row = $xoopsDB->fetchArray($pagesRow)) {
					$screenPages  = @unserialize($row['pages']);
					$screenEleIds = array();
					if (is_array($screenPages)) {
						foreach ($screenPages as $pageEles) {
							if (is_array($pageEles)) {
								$screenEleIds = array_merge($screenEleIds, $pageEles);
							}
						}
					}
					$sortedDesired = $desiredEleIds;
					sort($sortedDesired);
					$sortedScreen = $screenEleIds;
					sort($sortedScreen);
					if ($sortedDesired !== $sortedScreen) {
						$xoopsDB->queryF(
							"UPDATE " . $xoopsDB->prefix('formulize_screen_multipage') .
							" SET pages = " . $xoopsDB->quoteString(serialize(array(0 => $desiredEleIds))) .
							" WHERE sid = $sid"
						);
					}
				}
			}
		}
	}
	return $fid;
}

/**
 * Return the form IDs of all forms that have entries_are_users enabled.
 *
 * @return int[] Array of form IDs
 */
function getEntriesAreUsersForms() {
	global $xoopsDB;
	$sql = "SELECT id_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE entries_are_users = 1";
	$res = $xoopsDB->query($sql);
	$fids = array();
	while ($row = $xoopsDB->fetchArray($res)) {
		$fids[] = intval($row['id_form']);
	}
	return $fids;
}

/**
 * Find the EAU form entries that correspond to a given uid.
 *
 * Searches all entries-are-users forms and returns every match.
 *
 * @param int $uid The user ID to look up
 * @return array Array of arrays, each with keys 'fid' and 'entry_id'
 */
function findUserEauEntry($uid) {
	global $xoopsDB;
	$uid = intval($uid);
	if (!$uid) {
		return array();
	}
	$eauFids = getEntriesAreUsersForms();
	if (empty($eauFids)) {
		return array();
	}
	$matches = array();
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	foreach ($eauFids as $fid) {
		$formObj = $form_handler->get($fid);
		if (!$formObj) {
			continue;
		}
		$formHandle = $formObj->getVar('form_handle', 'raw');
		$uidCol = 'formulize_user_account_uid_' . $fid;
		$dataTable = $xoopsDB->prefix('formulize_' . $formHandle);
		$sql = "SELECT entry_id FROM " . $dataTable . " WHERE `" . $uidCol . "` = " . $uid . " LIMIT 1";
		$res = $xoopsDB->query($sql);
		if ($res && ($row = $xoopsDB->fetchArray($res))) {
			$matches[] = array('fid' => $fid, 'entry_id' => intval($row['entry_id']));
		}
	}
	return $matches;
}

/**
 * Resolve which form, entry, and screen should be used to render a user's account profile.
 *
 * If the user has an entries-are-users (EAU) form entry, that form's defaultform screen and the
 * EAU entry are returned. Otherwise it falls back to the System Users ad hoc table form, where
 * the entry_id IS the uid.
 *
 * Shared by edituser.php (self-service) and users.php (webmaster list routing).
 *
 * @param int  $uid                 The user ID to resolve a profile for
 * @return array list($fid, $entry_id, $sid)
 */
function formulize_resolveUserAccountScreen($uid) {
	$uid = intval($uid);
	global $xoopsUser;
	$form_handler = xoops_getmodulehandler('forms', 'formulize');

	// Authority is the CURRENT (viewing) user's, not the target's. Two ways to be routed to a
	// user's EAU entry screen:
	//   - the current user can manage user accounts (system_admin on XOOPS_SYSTEM_USER — a
	//     permission, e.g. a webmaster on users.php) and so may view any user's EAU profile; or
	//   - the current user has view_form permission AND can edit the specific entry
	//     (own-account grant via isUserOwnAccountEntry) AND can actually see the entry
	//     (security_check). The security_check gate handles the case where a user has view_form
	//     but cannot see their own EAU entry (e.g. entry created by an admin and the user has
	//     neither view_globalscope nor ownership in Formulize terms) — in that case we fall
	//     through to the system users form rather than routing somewhere that yields _NO_PERM.
	// user_can_edit_entry does NOT auto-grant webmasters, so canManageUsers is required, not redundant.
	$gperm_handler = xoops_gethandler('groupperm');
	$activeUid = $xoopsUser ? intval($xoopsUser->getVar('uid')) : 0;
	$activeGroups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
	$canManageUsers = (bool) $gperm_handler->checkRight('system_admin', XOOPS_SYSTEM_USER, $activeGroups);
	$mid = getFormulizeModId();

	foreach (findUserEauEntry($uid) as $match) {
		if (
			(
				$canManageUsers
				OR (
					$gperm_handler->checkRight("view_form", $match['fid'], $activeGroups, $mid)
					AND formulizePermHandler::user_can_edit_entry($match['fid'], $activeUid, $match['entry_id'])
					AND security_check($match['fid'], $match['entry_id'], $activeUid, '', $activeGroups, $mid, $gperm_handler)
				)
			)
			AND $eauForm = $form_handler->get($match['fid'])
		) {
			return array($match['fid'], $match['entry_id'], intval($eauForm->getVar('defaultform')));
		}
	}

	$fid = ensureUsersTableForm();
	$sysForm = $fid ? $form_handler->get($fid) : false;
	return array($fid, $uid, $sysForm ? intval($sysForm->getVar('defaultform')) : 0);
}

/**
 * Return the form IDs of all forms that have entries_are_groups enabled.
 *
 * @return int[] Array of form IDs
 */
function getEntriesAreGroupsForms() {
	global $xoopsDB;
	$sql = "SELECT id_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE entries_are_groups = 1";
	$res = $xoopsDB->query($sql);
	$fids = array();
	while ($row = $xoopsDB->fetchArray($res)) {
		$fids[] = intval($row['id_form']);
	}
	return $fids;
}

// ============================================================================
// COMPOSITE DATA GATHERING
// ============================================================================

/**
 * Build a SQL AND clause for group name LIKE conditions for use in the template-group dedup subquery.
 *
 * Extracts LIKE search terms from the group name element search in $searches and combines
 * them with OR so the subquery selects a representative row for any EAG form whose template
 * groups include at least one matching name. The outer WHERE enforces actual AND/OR logic.
 *
 * @param array  $searches  The searches array from the list-of-entries settings
 * @param int    $systemFid The ad hoc groups form ID (used to locate the name element handle)
 * @param object $db        Database connection
 * @return string SQL fragment like " AND (name LIKE '%Part%')", or "" when no name search
 */
function formulize_buildGroupNameDedupCondition($searches, $systemFid, $db) {
	$nameHandle = 'formulize_group_name_' . intval($systemFid);
	if (empty($searches[$nameHandle])) {
		return '';
	}
	$raw        = $searches[$nameHandle];
	$likeClauses = array();
	foreach (explode('//', $raw) as $part) {
		$part = trim(preg_replace('/^(OR|AND)/i', '', trim($part)));
		if ($part === '' || preg_match('/^[=<>!]/', $part)) {
			continue;
		}
		if (in_array(strtoupper($part), array('{BLANK}', '{TODAY}', '{USER}'))) {
			continue;
		}
		$likeClauses[] = 'name LIKE ' . $db->quoteString('%' . formulize_db_escape($part) . '%');
	}
	if (empty($likeClauses)) {
		return '';
	}
	return ' AND (' . implode(' OR ', $likeClauses) . ')';
}

/**
 * Gather a composite dataset for Users or Groups, merging system table data with EAU/EAG form data.
 *
 * Mirrors the signature and return format of formulize_gatherDataSet(). Delegates the
 * system table query to getData() and then injects EAU or EAG form data via the merge helpers.
 *
 * @param array       $settings   List-of-entries settings (columns, page size, global search, etc.)
 * @param array       $searches   Current search/filter values keyed by element handle
 * @param string      $sort       Sort column handle
 * @param string      $order      Sort direction ('ASC' or 'DESC')
 * @param int|mixed   $frid       Framework ID (0 for none)
 * @param int         $fid        The ad hoc system form ID
 * @param mixed       $scope      Scope restriction (user/group/global scope string or array)
 * @param object|null $screen     Screen object, or null
 * @param string      $currentURL Current page URL (empty = return empty result)
 * @param int         $forcequery 1 to bypass the data cache
 * @return array Array of [data, regeneratePageNumbers, filterToCompare, flatScope]
 */
function formulize_gatherCompositeDataSet($settings, $searches, $sort, $order, $frid, $fid, $scope, $screen = null, $currentURL = "", $forcequery = 0) {

	global $xoopsUser;

	if (!is_array($searches)) {
		$searches = array();
	}

	// Setup flatScope for comparison
	$flatScope = is_array($scope) ? serialize($scope) : $scope;

	$showcols = explode(",", $settings['oldcols']);
	if ($settings['global_search']) {
		foreach ($showcols as $column) {
			if (isset($searches[$column]) && $searches[$column]) {
				$searches[$column] .= "//OR" . $settings['global_search'];
			} else {
				$searches[$column] = "OR" . $settings['global_search'];
			}
		}
	}

	$filter = formulize_parseSearchesIntoFilter($searches);
	$filterToCompare = is_array($filter) ? serialize($filter) : $filter;

	$regeneratePageNumbers = false;
	if (!isset($_POST['lastentry']) AND ((isset($_POST['formulize_previous_filter']) AND $filterToCompare != $_POST['formulize_previous_filter']) OR (isset($_POST['formulize_previous_scope']) AND $flatScope != $_POST['formulize_previous_scope']))) {
		$regeneratePageNumbers = true;
	}

	$formulize_LOEPageSize = is_object($screen) ? $screen->getVar('entriesperpage') : 10;
	$formulize_LOEPageSize = (isset($_POST['formulize_entriesPerPage']) AND $_POST['formulize_entriesPerPage'] !== "") ? intval($_POST['formulize_entriesPerPage']) : $formulize_LOEPageSize;
	if ($formulize_LOEPageSize) {
		$limitStart = (isset($_POST['formulize_LOEPageStart']) AND !$regeneratePageNumbers) ? intval($_POST['formulize_LOEPageStart']) : 0;
		$limitSize = $formulize_LOEPageSize;
	} else {
		$limitStart = 0;
		$limitSize = 0;
	}

	// Get the system table data (the driving table)
	$GLOBALS['formulize_getCountForPageNumbers'] = true;
	$GLOBALS['formulize_setBaseQueryForCalcs'] = true;
	$GLOBALS['formulize_setQueryForExport'] = true;

	if ($screen) {
		$fundamental_filters = $screen->getVar('fundamental_filters');
		if (is_array($fundamental_filters) AND count($fundamental_filters) > 0) {
			$filter = array('fundamental_filters' => $fundamental_filters, 'active_filters' => $filter);
		}
	}

	// For the groups composite mode, exclude entry-group rows and collapse template groups so
	// that exactly one row per EAG form appears — making pagination counts correct.
	// When the user is searching on the group name column, inject that condition into the MIN
	// subquery so a representative row is chosen from among matching template groups; this
	// ensures that a search for a category name (e.g. "Participants") finds the EAG form even
	// when the minimum-groupid template group for that form has a different category name.
	if (isset($GLOBALS['formulize_compositeDataMode']) AND $GLOBALS['formulize_compositeDataMode'] === 'groups') {
		$entryIdFilter = 'entry_id/**//**/IS NULL';
		$filter = $filter ? $filter . '][' . $entryIdFilter : $entryIdFilter;
		global $xoopsDB;
		$groupsTable        = $xoopsDB->prefix('groups');
		$systemFid          = ensureGroupsTableForm();
		$nameDedupCondition = formulize_buildGroupNameDedupCondition($searches, $systemFid, $xoopsDB);
		$GLOBALS['formulize_tableFormAdditionalWhere'] =
			"(form_id IS NULL OR form_id = 0) OR groupid IN (SELECT MIN(groupid) FROM `$groupsTable` WHERE entry_id IS NULL AND form_id > 0{$nameDedupCondition} GROUP BY form_id)";
	}

	$data = getData($frid, $fid, $filter, "AND", $scope, $limitStart, $limitSize, $sort, $order, $forcequery);

	// If we deleted entries and the current page is now empty, shunt back 1 page
	if (count((array)$data) == 0 AND isset($_POST['delconfirmed']) AND $_POST['delconfirmed'] AND $limitStart > 0) {
		$_POST['formulize_LOEPageStart'] = $_POST['formulize_LOEPageStart'] - $formulize_LOEPageSize;
		$data = getData($frid, $fid, $filter, "AND", $scope, ($limitStart - $formulize_LOEPageSize), $limitSize, $sort, $order, $forcequery);
	}

	unset($GLOBALS['formulize_tableFormAdditionalWhere']);

	if ($currentURL == "") {
		return array(0 => "", 1 => "", 2 => "");
	}

	// Now merge with entries_are_users or entries_are_groups form data
	$compositeMode = $GLOBALS['formulize_compositeDataMode'];
	if ($compositeMode == 'users') {
		$data = mergeUsersCompositeData($data, $fid);
	} elseif ($compositeMode == 'groups') {
		$data = mergeGroupsCompositeData($data);
	}

	$to_return[0] = $data;
	$to_return[1] = $regeneratePageNumbers;
	$to_return[2] = $filterToCompare;
	$to_return[3] = $flatScope;
	return $to_return;
}

/**
 * Merge entries_are_users form data into the system users dataset.
 *
 * For each EAU form the current user has view permission on, finds entries whose uid
 * matches users in $data and merges those entries into the data array under the EAU
 * form handle.
 *
 * @param array $data      Standard getData result keyed by system form handle
 * @param int   $systemFid The ad hoc users form ID
 * @return array Updated data array
 */
function mergeUsersCompositeData($data, $systemFid) {
	if (!is_array($data) || count($data) == 0) {
		return $data;
	}

	$eauFids = getEntriesAreUsersForms();
	if (count($eauFids) == 0) {
		return $data;
	}

	global $xoopsDB, $xoopsUser;
	$gperm_handler = xoops_gethandler('groupperm');
	$mid = getFormulizeModId();
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);

	// Extract UIDs from the system table data.
	// The users table form routes through dataExtraction(); main.uid serves as entry_id,
	// so uid is both the array key and the 'uid' element value.
	$systemFormHandle = '__system_users';

	$uids = array();
	foreach ($data as $index => $entry) {
		if (isset($entry[$systemFormHandle])) {
			foreach (array_keys($entry[$systemFormHandle]) as $pkValue) {
				// pkValue IS the uid — it is the primary key returned by dataExtractionTableForm.
				$uids[intval($pkValue)] = $index;
			}
		}
	}

	if (count($uids) == 0) {
		return $data;
	}

	// For each EAU form, get matching entries and merge
	foreach ($eauFids as $eauFid) {
		// Check if user has view permission on this form
		if (!$gperm_handler->checkRight("view_form", $eauFid, $groups, $mid)) {
			continue;
		}

		// Determine the user's scope for this form
		$view_globalscope = $gperm_handler->checkRight("view_globalscope", $eauFid, $groups, $mid);
		$view_groupscope = $gperm_handler->checkRight("view_groupscope", $eauFid, $groups, $mid);

		$eauScope = "";
		if ($view_globalscope) {
			$eauScope = "";
		} elseif ($view_groupscope) {
			$eauScope = buildScope("group", $xoopsUser, $eauFid);
		} else {
			$eauScope = buildScope("mine", $xoopsUser, $eauFid);
		}

		// Query the EAU form's data table to find entries matching our UIDs
		$uidColumn = 'formulize_user_account_uid_' . intval($eauFid);
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$eauFormObject = $form_handler->get($eauFid);
		$eauFormHandle = $eauFormObject->getVar('form_handle');

		// Get all entries from this EAU form (with scope)
		$eauData = getData("", $eauFid, "", "AND", $eauScope);

		if (!is_array($eauData) || count($eauData) == 0) {
			continue;
		}

		// Find the element ID for the uid column in the EAU form
		$eauUidElementHandle = $uidColumn;
		// Build a map of uid => EAU entry data
		foreach ($eauData as $eauEntry) {
			if (!isset($eauEntry[$eauFormHandle])) {
				continue;
			}
			foreach ($eauEntry[$eauFormHandle] as $entryId => $elements) {
				// Find the uid value in this EAU entry
				$entryUid = null;
				foreach ($elements as $eleHandle => $value) {
					if ($eleHandle == $eauUidElementHandle || strpos($eleHandle, 'formulize_user_account_uid') !== false) {
						$entryUid = is_array($value) ? $value[0] : $value;
						break;
					}
				}
				if ($entryUid !== null && isset($uids[$entryUid])) {
					$dataIndex = $uids[$entryUid];
					// Merge EAU form data into the system entry
					if (!isset($data[$dataIndex][$eauFormHandle])) {
						$data[$dataIndex][$eauFormHandle] = array();
					}
					$data[$dataIndex][$eauFormHandle][$entryId] = $elements;
				}
			}
		}
	}

	return $data;
}


/**
 * Look up profile fields and inject them into the system users dataset.
 *
 * Handles phone (2faphone), timezone, and tfa_method (2famethod) — each only injected
 * when the corresponding element exists in the ad hoc users form.
 *
 * @param array  $data             Standard getData result keyed by system form handle
 * @param string $systemFormHandle Handle of the system users form ('__system_users')
 * @param int    $systemFid        The ad hoc users form ID
 * @return array Updated data array
 */
function injectProfileData($data, $systemFormHandle, $systemFid) {
	if (!is_array($data) || count($data) == 0) {
		return $data;
	}

	global $xoopsDB;

	// Discover which profile-backed elements are registered in this form, keyed by ele_type.
	// Using ele_type rather than handle is handle-naming-convention agnostic.
	$typeToProfileCol = array(
		'userAccountPhone'    => '2faphone',
		'userAccountTimezone' => 'timezone',
		'userAccount2FA'      => '2famethod',
	);
	$typeList = "'" . implode("','", array_keys($typeToProfileCol)) . "'";
	$res = $xoopsDB->query(
		"SELECT ele_handle, ele_type FROM " . $xoopsDB->prefix("formulize") .
		" WHERE id_form = " . intval($systemFid) .
		" AND ele_type IN (" . $typeList . ")"
	);
	$activeElements = array(); // ele_handle => profileCol
	if ($res) {
		while ($row = $xoopsDB->fetchArray($res)) {
			if (isset($typeToProfileCol[$row['ele_type']])) {
				$activeElements[$row['ele_handle']] = $typeToProfileCol[$row['ele_type']];
			}
		}
	}
	if (empty($activeElements)) {
		return $data;
	}

	// Collect all UIDs present in the result (the pk key IS the uid value)
	$uidList = array();
	foreach ($data as $entry) {
		if (isset($entry[$systemFormHandle])) {
			foreach (array_keys($entry[$systemFormHandle]) as $uid) {
				$uidList[] = intval($uid);
			}
		}
	}
	if (empty($uidList)) {
		return $data;
	}

	// Build SELECT list for only the profile columns we actually need
	$profileCols = array('profileid');
	foreach (array_unique(array_values($activeElements)) as $col) {
		$profileCols[] = '`' . $col . '`';
	}
	$profileTable = $xoopsDB->prefix('profile_profile');
	$safeUids     = implode(',', $uidList);
	$sql = "SELECT " . implode(', ', $profileCols) . " FROM $profileTable WHERE profileid IN ($safeUids)";
	$res = $xoopsDB->query($sql);

	$profileByUid = array();
	if ($res) {
		while ($row = $xoopsDB->fetchArray($res)) {
			$profileByUid[intval($row['profileid'])] = $row;
		}
	}

	// Inject profile values into each row, keyed by the actual ele_handle from the DB
	foreach ($data as $index => $entry) {
		if (isset($entry[$systemFormHandle])) {
			foreach (array_keys($entry[$systemFormHandle]) as $uid) {
				$profile = $profileByUid[intval($uid)] ?? array();
				foreach ($activeElements as $handle => $col) {
					$data[$index][$systemFormHandle][$uid][$handle] = $profile[$col] ?? '';
				}
			}
		}
	}

	return $data;
}

/**
 * Inject group membership names into the system users dataset.
 *
 * Only runs when a group_memberships virtual element exists in the ad hoc users form.
 * Injects a comma-separated list of group names (excluding Registered Users and Anonymous).
 *
 * @param array  $data             Standard getData result keyed by system form handle
 * @param string $systemFormHandle Handle of the system users form ('__system_users')
 * @param int    $systemFid        The ad hoc users form ID
 * @return array Updated data array
 */
function injectGroupMembershipData($data, $systemFormHandle, $systemFid) {
	if (!is_array($data) || count($data) == 0) {
		return $data;
	}

	global $xoopsDB;

	// Only proceed if a group membership element actually exists in this form.
	// Look up by ele_type to be agnostic of handle naming convention.
	$res = $xoopsDB->query(
		"SELECT ele_handle FROM " . $xoopsDB->prefix("formulize") .
		" WHERE id_form = " . intval($systemFid) . " AND ele_type = 'userAccountGroupMembership'"
	);
	if (!$res || $xoopsDB->getRowsNum($res) == 0) {
		return $data;
	}
	$gmRow = $xoopsDB->fetchArray($res);
	$groupMembershipHandle = $gmRow['ele_handle'];

	// Collect all UIDs present in the result.
	$uidList = array();
	foreach ($data as $entry) {
		if (isset($entry[$systemFormHandle])) {
			foreach (array_keys($entry[$systemFormHandle]) as $uid) {
				$uidList[] = intval($uid);
			}
		}
	}
	if (empty($uidList)) {
		return $data;
	}

	// Query group memberships and names in one join, excluding system groups.
	// DISTINCT eliminates duplicate rows that can occur when the groups_users_link table has redundant entries.
	$safeUids = implode(',', $uidList);
	$sql = "SELECT DISTINCT gul.uid, g.name"
		. " FROM " . $xoopsDB->prefix('groups_users_link') . " gul"
		. " JOIN "  . $xoopsDB->prefix('groups') . " g ON g.groupid = gul.groupid"
		. " WHERE gul.uid IN ($safeUids)"
		. " AND g.groupid NOT IN (" . XOOPS_GROUP_USERS . "," . XOOPS_GROUP_ANONYMOUS . ")"
		. " ORDER BY g.name";
	$res = $xoopsDB->query($sql);

	$groupsByUid = array();
	if ($res) {
		while ($row = $xoopsDB->fetchArray($res)) {
			$groupsByUid[intval($row['uid'])][] = $row['name'];
		}
	}

	// Inject into each row.
	foreach ($data as $index => $entry) {
		if (isset($entry[$systemFormHandle])) {
			foreach (array_keys($entry[$systemFormHandle]) as $uid) {
				$names = isset($groupsByUid[intval($uid)]) ? $groupsByUid[intval($uid)] : array();
				$data[$index][$systemFormHandle][$uid][$groupMembershipHandle] = $names; // array; formatted for display in prepareDataForDataset
			}
		}
	}

	return $data;
}

/**
 * Inject the EAU form title ("Type") for each user that belongs to an entries-are-users form.
 *
 * Users not linked to any EAU form receive the label "Regular". Only runs when a
 * userEauType virtual element exists in the ad hoc users form.
 *
 * @param array  $data             Standard getData result keyed by system form handle
 * @param string $systemFormHandle Handle of the system users form ('__system_users')
 * @param int    $systemFid        The ad hoc users form ID
 * @return array Updated data array
 */
function injectUserEauTypeData($data, $systemFormHandle, $systemFid) {
	if (!is_array($data) || count($data) == 0) {
		return $data;
	}

	global $xoopsDB;

	// Only proceed if a userEauType element actually exists in this form.
	$res = $xoopsDB->query(
		"SELECT ele_handle FROM " . $xoopsDB->prefix("formulize") .
		" WHERE id_form = " . intval($systemFid) . " AND ele_type = 'userEauType'"
	);
	if (!$res || $xoopsDB->getRowsNum($res) == 0) {
		return $data;
	}
	$row = $xoopsDB->fetchArray($res);
	$typeHandle = $row['ele_handle'];

	$eauFids = getEntriesAreUsersForms();
	if (empty($eauFids)) {
		return $data;
	}

	// Collect all UIDs present in the result set.
	$uidList = array();
	foreach ($data as $entry) {
		if (isset($entry[$systemFormHandle])) {
			foreach (array_keys($entry[$systemFormHandle]) as $uid) {
				$uidList[] = intval($uid);
			}
		}
	}
	if (empty($uidList)) {
		return $data;
	}

	// For each EAU form, query its data table once to find which UIDs are linked to it.
	$form_handler  = xoops_getmodulehandler('forms', 'formulize');
	$uidToFormTitles = array();
	foreach ($eauFids as $eauFid) {
		$eauFormObj = $form_handler->get($eauFid);
		if (!$eauFormObj) {
			continue;
		}
		$formHandle = $eauFormObj->getVar('form_handle', 'raw');
		if (!preg_match('/^[a-z0-9_]+$/i', $formHandle)) {
			continue;
		}
		$formTitle = $eauFormObj->getSingular();
		$uidCol    = 'formulize_user_account_uid_' . intval($eauFid);
		$dataTable = $xoopsDB->prefix('formulize_' . $formHandle);
		$safeUids  = implode(',', $uidList);
		$innerRes  = $xoopsDB->query(
			"SELECT `$uidCol` FROM `$dataTable`"
			. " WHERE `$uidCol` IN ($safeUids)"
		);
		if ($innerRes) {
			while ($innerRow = $xoopsDB->fetchArray($innerRes)) {
				$uid = intval($innerRow[$uidCol]);
				if ($uid > 0) {
					$uidToFormTitles[$uid][] = $formTitle;
				}
			}
		}
	}

	// Inject into each row; users with no EAU form get an empty array.
	foreach ($data as $index => $entry) {
		if (isset($entry[$systemFormHandle])) {
			foreach (array_keys($entry[$systemFormHandle]) as $uid) {
				$titles = isset($uidToFormTitles[intval($uid)]) ? $uidToFormTitles[intval($uid)] : array('Regular');
				$data[$index][$systemFormHandle][$uid][$typeHandle] = $titles;
			}
		}
	}

	return $data;
}

/**
 * Inject the group type label ("Regular" or "Form-based") into every group row.
 *
 * Regular groups (is_group_template = 0) receive "Regular"; template groups receive "Form-based".
 * Only runs when an eagGroupType element exists in the ad hoc groups form.
 *
 * @param array  $data             Standard getData result keyed by system form handle
 * @param string $systemFormHandle Handle of the system groups form ('__system_groups')
 * @param int    $systemFid        The ad hoc groups form ID
 * @return array Updated data array
 */
function injectGroupTypeData($data, $systemFormHandle, $systemFid) {
	if (!is_array($data) || count($data) == 0) {
		return $data;
	}

	global $xoopsDB;

	$res = $xoopsDB->query(
		"SELECT ele_handle FROM " . $xoopsDB->prefix("formulize") .
		" WHERE id_form = " . intval($systemFid) . " AND ele_type = 'eagGroupType'"
	);
	if (!$res || $xoopsDB->getRowsNum($res) == 0) {
		return $data;
	}
	$row = $xoopsDB->fetchArray($res);
	$typeHandle = $row['ele_handle'];

	foreach ($data as $index => $entry) {
		if (isset($entry[$systemFormHandle])) {
			foreach (array_keys($entry[$systemFormHandle]) as $pkValue) {
				$elements   = $entry[$systemFormHandle][$pkValue];
				$isTemplate = (int)(is_array($elements['is_group_template'] ?? null)
					? ($elements['is_group_template'][0] ?? 0)
					: ($elements['is_group_template'] ?? 0));
				$data[$index][$systemFormHandle][$pkValue][$typeHandle] = array($isTemplate ? 'Form-based' : 'Regular');
			}
		}
	}

	return $data;
}

/**
 * Inject group member names into every group row.
 *
 * Shows up to 15 names (or entry links for template groups); if the group has more,
 * appends a summary line. Only runs when an eagGroupMembers element exists in the form.
 *
 * @param array  $data             Standard getData result keyed by system form handle
 * @param string $systemFormHandle Handle of the system groups form ('__system_groups')
 * @param int    $systemFid        The ad hoc groups form ID
 * @return array Updated data array
 */
function injectGroupMembersData($data, $systemFormHandle, $systemFid) {
	if (!is_array($data) || count($data) == 0) {
		return $data;
	}

	global $xoopsDB;

	$displayLimit = 15;

	// Only proceed if the element exists in this form.
	$res = $xoopsDB->query(
		"SELECT ele_handle FROM " . $xoopsDB->prefix("formulize") .
		" WHERE id_form = " . intval($systemFid) . " AND ele_type = 'eagGroupMembers'"
	);
	if (!$res || $xoopsDB->getRowsNum($res) == 0) {
		return $data;
	}
	$row = $xoopsDB->fetchArray($res);
	$membersHandle = $row['ele_handle'];

	// Collect all groupids from the result set, noting which are template groups.
	$groupIds             = array();
	$templateGroupFormIds = array(); // gid => form_id
	foreach ($data as $entry) {
		if (isset($entry[$systemFormHandle])) {
			foreach (array_keys($entry[$systemFormHandle]) as $pkValue) {
				$elements   = $entry[$systemFormHandle][$pkValue];
				$rawGid     = $elements['groupid'] ?? null;
				$gid        = (int)(is_array($rawGid) ? ($rawGid[0] ?? 0) : $rawGid);
				if ($gid) {
					$groupIds[$gid] = $gid;
					$isTemplate = (int)(is_array($elements['is_group_template'] ?? null)
						? ($elements['is_group_template'][0] ?? 0)
						: ($elements['is_group_template'] ?? 0));
					$formId = (int)(is_array($elements['form_id'] ?? null)
						? ($elements['form_id'][0] ?? 0)
						: ($elements['form_id'] ?? 0));
					if ($isTemplate && $formId) {
						$templateGroupFormIds[$gid] = $formId;
					}
				}
			}
		}
	}
	if (empty($groupIds)) {
		return $data;
	}

	$inList     = implode(',', $groupIds);
	$gulTable   = $xoopsDB->prefix('groups_users_link');
	$groupsTable = $xoopsDB->prefix('groups');
	$usersTable = $xoopsDB->prefix('users');

	// Total member count per group (one query).
	// JOIN to users filters out orphaned groups_users_link rows for deleted users.
	$countsByGid = array();
	$cntRes = $xoopsDB->query(
		"SELECT gul.groupid, COUNT(*) AS cnt FROM `$gulTable` gul"
		. " JOIN `$usersTable` u ON u.uid = gul.uid"
		. " WHERE gul.groupid IN ($inList) GROUP BY gul.groupid"
	);
	if ($cntRes) {
		while ($cntRow = $xoopsDB->fetchArray($cntRes)) {
			$countsByGid[intval($cntRow['groupid'])] = intval($cntRow['cnt']);
		}
	}

	// For template groups: build per-entry "link (N members)" lists.
	// Each bullet is "viewEntryLink (N Members)"; overflow: "and X more (Y total members)".
	$templateGroupData = array(); // gid => array of formatted strings
	if (!empty($templateGroupFormIds)) {
		$form_handler    = xoops_getmodulehandler('forms', 'formulize');
		$element_handler = xoops_getmodulehandler('elements', 'formulize');

		foreach (array_unique(array_values($templateGroupFormIds)) as $eagFid) {
			$formObject = $form_handler->get($eagFid);
			if (!$formObject) {
				continue;
			}
			$formHandle        = $formObject->getVar('form_handle', 'raw');
			$defaultFormScreen = intval($formObject->getVar('defaultform'));
			if (!preg_match('/^[a-z0-9_]+$/i', $formHandle)) {
				continue;
			}
			$dataTable = $xoopsDB->prefix('formulize_' . $formHandle);

			// DISTINCT member count per entry_id (across all category groups for that entry).
			// LEFT JOIN to users filters out orphaned groups_users_link rows for deleted users
			// while still counting entries that have zero valid members (gul.uid IS NULL).
			$memberCountsByEntryId = array();
			$perEntryRes = $xoopsDB->query(
				"SELECT g.entry_id, COUNT(DISTINCT gul.uid) AS cnt"
				. " FROM `$groupsTable` g"
				. " LEFT JOIN `$gulTable` gul ON gul.groupid = g.groupid"
				. " LEFT JOIN `$usersTable` u ON u.uid = gul.uid"
				. " WHERE g.form_id = " . intval($eagFid)
				. " AND g.is_group_template = 0 AND g.entry_id > 0"
				. " AND (gul.uid IS NULL OR u.uid IS NOT NULL)"
				. " GROUP BY g.entry_id"
			);
			if ($perEntryRes) {
				while ($row = $xoopsDB->fetchArray($perEntryRes)) {
					$memberCountsByEntryId[intval($row['entry_id'])] = intval($row['cnt']);
				}
			}

			// Build "viewEntryLink (members: N)" strings using PI values in PI order.
			// entryItemsData tracks [text, entry_id] so overflow entry_ids can be queried.
			$piElementId    = intval($formObject->getVar('pi'));
			$entryItemsData = array();
			if ($piElementId && ($piElement = $element_handler->get($piElementId))) {
				$piHandle = formulize_db_escape($piElement->getVar('ele_handle', 'raw'));
				$piRes    = $xoopsDB->query(
					"SELECT DISTINCT `$piHandle`, `entry_id` FROM `$dataTable`"
					. " WHERE `$piHandle` IS NOT NULL AND `$piHandle` != '' ORDER BY `$piHandle`"
				);
				while ($piRes && ($piRow = $xoopsDB->fetchRow($piRes))) {
					$entryId          = intval($piRow[1]);
					$cnt              = isset($memberCountsByEntryId[$entryId]) ? $memberCountsByEntryId[$entryId] : 0;
					$link             = viewEntryLink($piRow[0], $entryId, override_screen_id: $defaultFormScreen);
					$entryItemsData[] = array('text' => $link . ' &mdash; ' . $cnt . ' members', 'entry_id' => $entryId);
				}
			}
			if (empty($entryItemsData)) {
				// Fallback when no PI element is set: use entry_id as label.
				foreach ($memberCountsByEntryId as $entryId => $cnt) {
					$link             = viewEntryLink('Entry ' . $entryId, $entryId, override_screen_id: $defaultFormScreen);
					$entryItemsData[] = array('text' => $link . ' &mdash; ' . $cnt . ' members', 'entry_id' => intval($entryId));
				}
			}

			$totalEntries = count($entryItemsData);
			if ($totalEntries > $displayLimit) {
				$moreCount       = $totalEntries - $displayLimit;
				$overflowItems   = array_slice($entryItemsData, $displayLimit);
				$entryItemsData  = array_slice($entryItemsData, 0, $displayLimit);
				// Count DISTINCT members across only the overflow entries' groups.
				$overflowMemberCount = 0;
				$overflowEntryIds    = array_filter(array_column($overflowItems, 'entry_id'));
				if (!empty($overflowEntryIds)) {
					$overflowInList = implode(',', array_map('intval', $overflowEntryIds));
					$overflowRes    = $xoopsDB->query(
						"SELECT COUNT(DISTINCT gul.uid) AS cnt"
						. " FROM `$groupsTable` g"
						. " JOIN `$gulTable` gul ON gul.groupid = g.groupid"
						. " JOIN `$usersTable` u ON u.uid = gul.uid"
						. " WHERE g.form_id = " . intval($eagFid)
						. " AND g.is_group_template = 0 AND g.entry_id IN ($overflowInList)"
					);
					if ($overflowRes && ($oRow = $xoopsDB->fetchArray($overflowRes))) {
						$overflowMemberCount = intval($oRow['cnt']);
					}
				}
				$entryItemsData[] = array('text' => 'and ' . $moreCount . ' more &mdash; ' . $overflowMemberCount . ' members', 'entry_id' => 0);
			}
			$entryItems = array_column($entryItemsData, 'text');

			// Map the result to each template group gid that references this form.
			foreach ($templateGroupFormIds as $gid => $fid) {
				if ($fid === $eagFid) {
					$templateGroupData[$gid] = $entryItems;
				}
			}
		}
	}

	// Member names for non-template, non-registered-users groups only.
	$nonListGids = array_merge(array(XOOPS_GROUP_USERS), array_keys($templateGroupFormIds));
	$listGids    = array_diff($groupIds, $nonListGids);
	$namesByGid  = array();
	if (!empty($listGids)) {
		$listInList = implode(',', $listGids);
		$namesRes = $xoopsDB->query(
			"SELECT gul.groupid, u.uname"
			. " FROM `$gulTable` gul"
			. " JOIN `$usersTable` u ON u.uid = gul.uid"
			. " WHERE gul.groupid IN ($listInList)"
			. " ORDER BY gul.groupid, u.uname"
		);
		if ($namesRes) {
			while ($nRow = $xoopsDB->fetchArray($namesRes)) {
				$gid = intval($nRow['groupid']);
				if (!isset($namesByGid[$gid])) {
					$namesByGid[$gid] = array();
				}
				if (count($namesByGid[$gid]) < $displayLimit) {
					$namesByGid[$gid][] = $nRow['uname'];
				}
			}
		}
	}

	// Inject into each row.
	foreach ($data as $index => $entry) {
		if (!isset($entry[$systemFormHandle])) {
			continue;
		}
		foreach (array_keys($entry[$systemFormHandle]) as $pkValue) {
			$elements = $entry[$systemFormHandle][$pkValue];
			$rawGid   = $elements['groupid'] ?? null;
			$gid      = (int)(is_array($rawGid) ? ($rawGid[0] ?? 0) : $rawGid);
			if ($gid == XOOPS_GROUP_USERS) {
				$total = isset($countsByGid[$gid]) ? $countsByGid[$gid] : 0;
				$names = array($total . ' total');
			} elseif (isset($templateGroupFormIds[$gid])) {
				$names = isset($templateGroupData[$gid]) ? $templateGroupData[$gid] : array();
			} else {
				$total = isset($countsByGid[$gid]) ? $countsByGid[$gid] : 0;
				$names = isset($namesByGid[$gid]) ? $namesByGid[$gid] : array();
				if ($total > $displayLimit) {
					$names[] = '...and more. (' . $total . ' total)';
				}
			}
			$data[$index][$systemFormHandle][$pkValue][$membersHandle] = $names;
		}
	}

	return $data;
}

/**
 * Merge entries_are_groups form data into the system groups dataset.
 *
 * Injects virtual categories/entries/members/type columns into template group rows.
 * Entry-group rows (entry_id IS NOT NULL) are excluded at the SQL query level via
 * fundamental_filters on the pseudo screen, so pagination counts are correct.
 *
 * @param array $data Standard getData result keyed by system groups form handle
 * @return array Updated data array
 */
function mergeGroupsCompositeData($data) {
	if (!is_array($data) || count($data) == 0) {
		return $data;
	}

	$systemFormHandle = '__system_groups';

	$eagFids = getEntriesAreGroupsForms();

	// Inject virtual columns into all remaining rows (plain groups get empty arrays;
	// template groups get their categories and entry names).
	$fid  = ensureGroupsTableForm();
	$data = injectGroupTypeData($data, $systemFormHandle, $fid);
	$data = injectGroupCategoriesData($data, $systemFormHandle, $fid);
	$data = injectGroupEntriesData($data, $systemFormHandle, $fid, $eagFids);
	$data = injectGroupMembersData($data, $systemFormHandle, $fid);

	return $data;
}

/**
 * Inject "Categories" (template group names for the same EAG form) into template group rows.
 *
 * Plain group rows and non-template groups receive an empty array. Also renames the
 * 'name' column for template group rows to the EAG form title.
 *
 * @param array  $data             Standard getData result keyed by system form handle
 * @param string $systemFormHandle Handle of the system groups form ('__system_groups')
 * @param int    $systemFid        The ad hoc groups form ID
 * @return array Updated data array
 */
function injectGroupCategoriesData($data, $systemFormHandle, $systemFid) {
	if (!is_array($data) || count($data) == 0) {
		return $data;
	}

	global $xoopsDB;

	// Find the virtual categories element handle.
	$res = $xoopsDB->query(
		"SELECT ele_handle FROM " . $xoopsDB->prefix("formulize") .
		" WHERE id_form = " . intval($systemFid) . " AND ele_handle = 'group_categories'"
	);
	if (!$res || $xoopsDB->getRowsNum($res) == 0) {
		return $data;
	}
	$row = $xoopsDB->fetchArray($res);
	$categoriesHandle = $row['ele_handle'];

	// Collect template form_ids from the data (one row per EAG form after SQL dedup).
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$formTitlesByFormId = array();
	$templateFormIds = array();
	foreach ($data as $entry) {
		if (!isset($entry[$systemFormHandle])) {
			continue;
		}
		foreach ($entry[$systemFormHandle] as $elements) {
			$isTemplate = (int)(is_array($elements['is_group_template'] ?? null)
				? ($elements['is_group_template'][0] ?? 0)
				: ($elements['is_group_template'] ?? 0));
			$formId = (int)(is_array($elements['form_id'] ?? null)
				? ($elements['form_id'][0] ?? 0)
				: ($elements['form_id'] ?? 0));
			if ($isTemplate && $formId) {
				$templateFormIds[$formId] = $formId;
			}
		}
	}

	// Query the groups table directly for ALL template group names across all pages,
	// since the SQL dedup means the data only carries one representative row per EAG form.
	// Strip the "{FormTitle} - " prefix so only the bare category name is stored.
	$categoriesByFormId = array();
	if (!empty($templateFormIds)) {
		$inList = implode(',', array_map('intval', $templateFormIds));
		$res = $xoopsDB->query(
			"SELECT form_id, name FROM " . $xoopsDB->prefix('groups') .
			" WHERE is_group_template = 1 AND form_id IN ($inList) ORDER BY name"
		);
		while ($res && ($row = $xoopsDB->fetchArray($res))) {
			$fid2 = (int)$row['form_id'];
			$name = $row['name'];
			if (!isset($formTitlesByFormId[$fid2])) {
				$formObject = $form_handler->get($fid2);
				$formTitlesByFormId[$fid2] = $formObject ? $formObject->getVar('form_title', 'raw') : '';
			}
			$prefix = $formTitlesByFormId[$fid2] ? $formTitlesByFormId[$fid2] . ' - ' : '';
			$categoryName = ($prefix && strpos($name, $prefix) === 0) ? substr($name, strlen($prefix)) : $name;
			$categoriesByFormId[$fid2][] = $categoryName;
		}
	}

	// Inject into each row. For template group rows also rename 'name' to the EAG form title.
	foreach ($data as $index => $entry) {
		if (!isset($entry[$systemFormHandle])) {
			continue;
		}
		foreach (array_keys($entry[$systemFormHandle]) as $pkValue) {
			$elements   = $entry[$systemFormHandle][$pkValue];
			$isTemplate = (int)(is_array($elements['is_group_template'] ?? null)
				? ($elements['is_group_template'][0] ?? 0)
				: ($elements['is_group_template'] ?? 0));
			$formId = (int)(is_array($elements['form_id'] ?? null)
				? ($elements['form_id'][0] ?? 0)
				: ($elements['form_id'] ?? 0));
			$categories = ($isTemplate && $formId && isset($categoriesByFormId[$formId]))
				? $categoriesByFormId[$formId]
				: array();
			$data[$index][$systemFormHandle][$pkValue][$categoriesHandle] = $categories;
			if ($isTemplate && $formId && isset($formTitlesByFormId[$formId])) {
				$data[$index][$systemFormHandle][$pkValue]['formulize_group_name_' . $systemFid] = array($formTitlesByFormId[$formId]);
			}
		}
	}

	return $data;
}

/**
 * Inject "Entries" (PI values from the EAG form data table) into template group rows.
 *
 * Queries the EAG form's data table using the form's PI element to get entry names.
 * Falls back to stripping the category suffix from entry group names if no PI element is set.
 * Plain/non-template group rows receive an empty array.
 *
 * @param array      $data             Standard getData result keyed by system form handle
 * @param string     $systemFormHandle Handle of the system groups form ('__system_groups')
 * @param int        $systemFid        The ad hoc groups form ID
 * @param int[]|null $eagFids          Optional pre-fetched EAG form IDs (unused; kept for signature compat)
 * @return array Updated data array
 */
function injectGroupEntriesData($data, $systemFormHandle, $systemFid, $eagFids = null) {
	if (!is_array($data) || count($data) == 0) {
		return $data;
	}

	global $xoopsDB;

	// Find the virtual entries element handle.
	$res = $xoopsDB->query(
		"SELECT ele_handle FROM " . $xoopsDB->prefix("formulize") .
		" WHERE id_form = " . intval($systemFid) . " AND ele_handle = 'group_entries'"
	);
	if (!$res || $xoopsDB->getRowsNum($res) == 0) {
		return $data;
	}
	$row = $xoopsDB->fetchArray($res);
	$entriesHandle = $row['ele_handle'];

	// Collect the unique EAG form_ids referenced by template group rows.
	$templateFormIds = array();
	foreach ($data as $entry) {
		if (!isset($entry[$systemFormHandle])) {
			continue;
		}
		foreach ($entry[$systemFormHandle] as $elements) {
			$isTemplate = (int)(is_array($elements['is_group_template'] ?? null)
				? ($elements['is_group_template'][0] ?? 0)
				: ($elements['is_group_template'] ?? 0));
			$formId = (int)(is_array($elements['form_id'] ?? null)
				? ($elements['form_id'][0] ?? 0)
				: ($elements['form_id'] ?? 0));
			if ($isTemplate && $formId) {
				$templateFormIds[$formId] = $formId;
			}
		}
	}

	// For each EAG form, retrieve entry names via the form's PI element.
	$displayLimit       = 15;
	$entriesByFormId    = array(); // form_id => [pi_value, ...]
	$formPluralsByFormId = array(); // form_id => plural label
	if (!empty($templateFormIds)) {
		$form_handler    = xoops_getmodulehandler('forms', 'formulize');
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		foreach ($templateFormIds as $eagFid) {
			$formObject  = $form_handler->get($eagFid);
			$formPluralsByFormId[$eagFid] = $formObject ? $formObject->getPlural() : 'entries';
			if (!$formObject) {
				continue;
			}
			$piElementId = intval($formObject->getVar('pi'));
			$formHandle  = $formObject->getVar('form_handle', 'raw');
			$dataTable   = $xoopsDB->prefix('formulize_' . $formHandle);
			if ($piElementId) {
				// Query PI values from the data table.
				$piElement = $element_handler->get($piElementId);
				if ($piElement) {
					$piHandle = formulize_db_escape($piElement->getVar('ele_handle', 'raw'));
					$sql = "SELECT DISTINCT `$piHandle`, `entry_id` FROM $dataTable WHERE `$piHandle` IS NOT NULL AND `$piHandle` != '' ORDER BY `$piHandle`";
					$res = $xoopsDB->query($sql);
					while ($res && ($piRow = $xoopsDB->fetchRow($res))) {
						$entriesByFormId[$eagFid][] = viewEntryLink($piRow[0], $piRow[1], override_screen_id: $formObject->getVar('defaultform'));
					}
					continue;
				}
			}
			// Fallback: derive entry names by stripping category suffixes from entry group names.
			// Entry groups follow the format "{PI value} - {Category name}".
			// Collect all category names for this form from the groups table.
			$catNames = array();
			$catRes   = $xoopsDB->query(
				"SELECT name FROM " . $xoopsDB->prefix('groups') .
				" WHERE form_id = " . intval($eagFid) . " AND is_group_template = 1"
			);
			while ($catRes && ($catRow = $xoopsDB->fetchArray($catRes))) {
				$catNames[] = $catRow['name'];
			}
			if (empty($catNames)) {
				continue;
			}
			// Sort by length descending so longer suffixes are matched first (avoids partial stripping).
			usort($catNames, function($a, $b) { return strlen($b) - strlen($a); });
			$piValues = array();
			$entryGroupRes = $xoopsDB->query(
				"SELECT name, entry_id FROM " . $xoopsDB->prefix('groups') .
				" WHERE form_id = " . intval($eagFid) . " AND is_group_template = 0 AND entry_id > 0"
			);
			while ($entryGroupRes && ($egRow = $xoopsDB->fetchArray($entryGroupRes))) {
				$entryName = $egRow['name'];
				$entryId	 = $egRow['entry_id'];
				foreach ($catNames as $cat) {
					$suffix = ' - ' . $cat;
					if (substr($entryName, -strlen($suffix)) === $suffix) {
						$entryName = substr($entryName, 0, -strlen($suffix));
						break;
					}
				}
				$piValues[$entryName] = viewEntryLink($entryName, $entryId, override_screen_id: $formObject->getVar('defaultform'));
			}
			ksort($piValues);
			$entriesByFormId[$eagFid] = array_values($piValues);
		}
	}

	// Apply display limit; append overflow summary using the form's plural label.
	foreach ($entriesByFormId as $eagFid => $entries) {
		$total = count($entries);
		if ($total > $displayLimit) {
			$entriesByFormId[$eagFid] = array_slice($entries, 0, $displayLimit);
			$entriesByFormId[$eagFid][] = '...and more. (' . $total . ' total)';
		}
	}

	// Inject into each template group row.
	foreach ($data as $index => $entry) {
		if (!isset($entry[$systemFormHandle])) {
			continue;
		}
		foreach (array_keys($entry[$systemFormHandle]) as $pkValue) {
			$elements   = $entry[$systemFormHandle][$pkValue];
			$isTemplate = (int)(is_array($elements['is_group_template'] ?? null)
				? ($elements['is_group_template'][0] ?? 0)
				: ($elements['is_group_template'] ?? 0));
			$formId = (int)(is_array($elements['form_id'] ?? null)
				? ($elements['form_id'][0] ?? 0)
				: ($elements['form_id'] ?? 0));
			$entries = ($isTemplate && $formId && isset($entriesByFormId[$formId]))
				? $entriesByFormId[$formId]
				: array();
			$data[$index][$systemFormHandle][$pkValue][$entriesHandle] = $entries;
		}
	}

	return $data;
}

/**
 * Get the element ID for a given column name in an ad hoc table form.
 *
 * Column names are stored as captions (with underscores replaced by spaces) or custom labels.
 *
 * @param int    $fid        The form ID
 * @param string $columnName The database column name to look up
 * @return int|false The element ID, or false if not found
 */
function getElementIdByColumnName($fid, $columnName) {
	global $xoopsDB;
	$caption = str_replace("_", " ", $columnName);
	$sql = "SELECT ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form = " . intval($fid) . " AND (ele_caption = " . $xoopsDB->quoteString($caption) . " OR ele_caption = " . $xoopsDB->quoteString($columnName) . ")";
	$res = $xoopsDB->query($sql);
	if ($res && $row = $xoopsDB->fetchArray($res)) {
		return $row['ele_id'];
	}
	return false;
}

// ============================================================================
// GROUP DELETION FUNCTIONS
// ============================================================================

/**
 * Return an array of group IDs that are safe to delete.
 *
 * A group is deletable when:
 *   1. It is not a built-in system group (webmasters=1, registered=2, anonymous=3).
 *   2. It has no members in groups_users_link.
 *   3. It either has no rows in formulize_entry_owner_groups, or any rows it does
 *      have are for forms on which the group no longer holds view_form permission.
 *
 * @return int[] Array of deletable group IDs
 */
function getDeletableGroupIds() {
	global $xoopsDB;
	$modId         = getFormulizeModId();
	$groupsTable   = $xoopsDB->prefix('groups');
	$gulTable      = $xoopsDB->prefix('groups_users_link');
	$eogTable      = $xoopsDB->prefix('formulize_entry_owner_groups');
	$gpermTable    = $xoopsDB->prefix('group_permission');

	$sql = "SELECT g.groupid FROM `$groupsTable` g"
		. " WHERE g.groupid NOT IN (1, 2, 3)"
		. " AND NOT EXISTS ("
		. "   SELECT 1 FROM `$gulTable` gul WHERE gul.groupid = g.groupid"
		. " )"
		. " AND NOT EXISTS ("
		. "   SELECT 1 FROM `$eogTable` eog"
		. "   JOIN `$gpermTable` gp"
		. "     ON gp.gperm_name = 'view_form'"
		. "     AND gp.gperm_groupid = g.groupid"
		. "     AND gp.gperm_itemid = eog.fid"
		. "     AND gp.gperm_modid = " . intval($modId)
		. "   WHERE eog.groupid = g.groupid"
		. " )";

	$res = $xoopsDB->query($sql);
	$ids = array();
	while ($res && $row = $xoopsDB->fetchArray($res)) {
		$ids[] = intval($row['groupid']);
	}
	return $ids;
}

/**
 * Delete a group by ID.
 *
 * Cleans up groups_users_link, group_permission, and formulize_entry_owner_groups
 * in addition to removing the groups table row.
 *
 * @param int $groupId The group ID to delete
 * @return bool True on success, false if $groupId is 0 or invalid
 */
function deleteGroupById($groupId) {
	global $xoopsDB;
	$groupId = intval($groupId);
	if (!$groupId) {
		return false;
	}
	$gulTable   = $xoopsDB->prefix('groups_users_link');
	$gpermTable = $xoopsDB->prefix('group_permission');
	$eogTable   = $xoopsDB->prefix('formulize_entry_owner_groups');
	$groupsTable = $xoopsDB->prefix('groups');

	$xoopsDB->queryF("DELETE FROM `$gulTable`   WHERE groupid = $groupId");
	$xoopsDB->queryF("DELETE FROM `$gpermTable` WHERE gperm_groupid = $groupId");
	$xoopsDB->queryF("DELETE FROM `$eogTable`   WHERE groupid = $groupId");
	$xoopsDB->queryF("DELETE FROM `$groupsTable` WHERE groupid = $groupId");
	return true;
}

// ============================================================================
// MENU FUNCTIONS
// ============================================================================

/**
 * Draw the Users and Groups menu section for the Formulize menu block.
 *
 * Returns both an HTML string (for non-template menu mode) and a structured data array
 * (for template menu mode). Returns array(false, false) if the current user lacks
 * both system_admin/user and system_admin/group permissions.
 *
 * @return array Two-element array: [string|false $htmlContent, array|false $dataArray]
 */
function drawUsersAndGroupsMenuSection() {
	global $xoopsUser;

	$gperm_handler = xoops_gethandler('groupperm');
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
	$canManageUsers = $gperm_handler->checkRight('system_admin', XOOPS_SYSTEM_USER, $groups);
	$canManageGroups = $gperm_handler->checkRight('system_admin', XOOPS_SYSTEM_GROUP, $groups);

	if (!$canManageUsers && !$canManageGroups) {
		return array(false, false);
	}

	$usersUrl  = XOOPS_URL . "/modules/formulize/users.php";
	$groupsUrl = XOOPS_URL . "/modules/formulize/groups.php";
	$baseUrl   = $canManageUsers ? $usersUrl : $groupsUrl;
	$currentURL = getCurrentURL();

	// Determine active state
	$isActive = strpos($currentURL, '/modules/formulize/users.php') !== false
	         || strpos($currentURL, '/modules/formulize/groups.php') !== false;
	$menuActive = $isActive ? ' menuActive' : '';

	// Category label - could be configurable in the future
	$categoryTitle = 'Users and Groups';

	// Build HTML string (for non-template menu mode)
	$block = "<a class=\"menuMain$menuActive\" href=\"$baseUrl\">$categoryTitle</a>";

	// Build structured data (for template menu mode)
	$data = array(
		'url' => $baseUrl,
		'title' => $categoryTitle,
		'active' => ($isActive ? 1 : 0),
		'target' => '',
		'icon' => ''
	);

	// Sub-items
	if ($isActive) {
		$view = strstr(getCurrentURL(), '/modules/formulize/users.php') !== false ? 'users' : 'groups';

		if ($canManageUsers) {
			$usersActive = ($view == 'users') ? ' menuSubActive' : '';
			$block .= "<a class=\"menuSub$usersActive\" href='$usersUrl'>Users</a>";
			$data['subs'][] = array('url' => $usersUrl, 'title' => 'Users', 'active' => ($view == 'users' ? 1 : 0), 'target' => '');
		}

		if ($canManageGroups) {
			$groupsActive = ($view == 'groups') ? ' menuSubActive' : '';
			$block .= "<a class=\"menuSub$groupsActive\" href='$groupsUrl'>Groups</a>";
			$data['subs'][] = array('url' => $groupsUrl, 'title' => 'Groups', 'active' => ($view == 'groups' ? 1 : 0), 'target' => '');
		}
	}

	return array($block, $data);
}
