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

// Ensure the system users table is registered as an ad hoc table form.
// Returns the form ID, or false on failure. Cached per page load.
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
			'extraElements' => array(
				array('handle' => 'phone',            'typeForCaption' => 'userAccountPhone'),
				array('handle' => 'tfa_method',       'typeForCaption' => 'userAccount2FA'),
				array('handle' => 'timezone',         'typeForCaption' => 'userAccountTimezone'),
				array('handle' => 'group_memberships','typeForCaption' => 'userAccountGroupMembership'),
				array('handle' => 'password',         'typeForCaption' => 'userAccountPassword'),
				array('handle' => 'masquerade',       'typeForCaption' => 'userAccountMasquerade', 'description' => _formulize_UA_MASQUERADE_HELP),
			),
			// Default visible columns in canonical order; others available via Change Columns.
			'defaultColumns' => array('uid', 'uname', 'login_name', 'email', 'phone'),
		)
	);
	return $fid;
}

// Ensure the system groups table is registered as an ad hoc table form.
// Returns the form ID, or false on failure. Cached per page load.
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
				'name'              => 'Group Name',
				'description'       => 'Description',
				'group_type'        => 'Group Type',
				'is_group_template' => 'Is Template',
				'form_id'           => 'Form ID',
				'entry_id'          => 'Entry ID',
			),
			// Virtual columns injected post-query for template groups.
			// virtual=true stores a marker in ele_value so dataExtractionTableForm
			// skips these in SELECT/ORDER BY/WHERE (they have no real DB backing).
			// type points to element class files that implement buildSearchWhereClause
			// so search terms on these columns delegate to correlated subqueries.
			'extraElements' => array(
				array('handle' => 'group_categories', 'caption' => 'Categories', 'virtual' => true, 'type' => 'eagGroupCategories'),
				array('handle' => 'group_entries',    'caption' => 'Instances',  'virtual' => true, 'type' => 'eagGroupEntries'),
			),
			// Default visible columns; others accessible via Change Columns.
			'defaultColumns' => array('groupid', 'name', 'group_categories', 'group_entries'),
		)
	);
	return $fid;
}

// Get all forms that have entries_are_users enabled.
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

// Find the EAU form entry (or entries) that correspond to a given uid.
// Returns array of arrays, each with keys 'fid' and 'entry_id'.
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

// Get all forms that have entries_are_groups enabled.
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

// Gather a composite dataset for Users or Groups, merging system table data
// with entries_are_users or entries_are_groups form data.
// This mirrors the signature and return format of formulize_gatherDataSet().
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
	if (isset($GLOBALS['formulize_compositeDataMode']) AND $GLOBALS['formulize_compositeDataMode'] === 'groups') {
		$entryIdFilter = 'entry_id/**//**/IS NULL';
		$filter = $filter ? $filter . '][' . $entryIdFilter : $entryIdFilter;
		global $xoopsDB;
		$groupsTable = $xoopsDB->prefix('groups');
		$GLOBALS['formulize_tableFormAdditionalWhere'] =
			"(form_id IS NULL OR form_id = 0) OR groupid IN (SELECT MIN(groupid) FROM `$groupsTable` WHERE entry_id IS NULL AND form_id > 0 GROUP BY form_id)";
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

// Merge entries_are_users form data into the system users dataset.
// $data is in standard getData format, $systemFid is the ad hoc users form ID.
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


// Look up profile fields and inject them into the system users dataset.
// Handles phone (2faphone), timezone, and tfa_method (2famethod) — each only injected
// when the corresponding element exists in the ad hoc users form.
// $data is the standard getData result keyed by handle; $systemFid is the ad hoc users form ID.
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

// Inject group membership names into the system users dataset.
// Only runs when a group_memberships virtual element exists in the ad hoc users form.
// Injects a comma-separated list of group names (excluding Registered Users and Anonymous).
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

// Merge entries_are_groups form data into the system groups dataset.
// Injects virtual categories/entries columns into template group rows.
// Entry-group rows (entry_id IS NOT NULL) are excluded at the SQL query level
// via fundamental_filters on the pseudo screen, so pagination counts are correct.
function mergeGroupsCompositeData($data) {
	if (!is_array($data) || count($data) == 0) {
		return $data;
	}

	$systemFormHandle = '__system_groups';

	$eagFids = getEntriesAreGroupsForms();

	// Inject virtual columns into all remaining rows (plain groups get empty arrays;
	// template groups get their categories and entry names).
	$fid  = ensureGroupsTableForm();
	$data = injectGroupCategoriesData($data, $systemFormHandle, $fid);
	$data = injectGroupEntriesData($data, $systemFormHandle, $fid, $eagFids);

	return $data;
}

// Inject "Categories" (template group names for the same EAG form) into template group rows.
// Plain group rows receive an empty array.
// $data is in standard getData format; $systemFormHandle is '__system_groups'.
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
				$data[$index][$systemFormHandle][$pkValue]['name'] = array($formTitlesByFormId[$formId]);
			}
		}
	}

	return $data;
}

// Inject "Entries" (PI values from the EAG form data table) into template group rows.
// Queries the EAG form's data table using the form's PI element to get entry names.
// Falls back to stripping the category suffix from entry group names if no PI element is set.
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
	$entriesByFormId = array(); // form_id => [pi_value, ...]
	if (!empty($templateFormIds)) {
		$form_handler    = xoops_getmodulehandler('forms', 'formulize');
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		foreach ($templateFormIds as $eagFid) {
			$formObject  = $form_handler->get($eagFid);
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

// Helper: get the element ID for a given column name in an ad hoc table form.
// Column names are stored as captions (with underscores replaced by spaces) or custom labels.
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
// MENU FUNCTIONS
// ============================================================================

// Draw the Users and Groups menu section for the Formulize menu block.
// Returns array($htmlContent, $dataArray) or array(false, false) if user lacks permission.
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
