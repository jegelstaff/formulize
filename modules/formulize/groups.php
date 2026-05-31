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

// System Groups management page.
// Displays a list-of-entries view for system groups,
// optionally merged with entries_are_groups form data.

require_once "../../mainfile.php";

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/usersAndGroups.php';

global $xoopsUser;

$gperm_handler = xoops_gethandler('groupperm');
$groups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
$canManageGroups = $gperm_handler->checkRight('system_admin', XOOPS_SYSTEM_GROUP, $groups);

if (!$canManageGroups) {
	redirect_header(XOOPS_URL, 3, 'You do not have permission to manage groups.');
	exit();
}

$fid = ensureGroupsTableForm();

if (!$fid) {
	throw new Exception('Could not initialize the system groups table form.');
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
$GLOBALS['formulize_compositeDataMode'] = 'groups';
$GLOBALS['formulize_systemAdminPermissionVerified'] = true;
$GLOBALS['formulize_tableFormAdditionalOrderBy'] = 'is_group_template ASC';

$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($fid);
$fidInt = intval($fid);

// Build a pseudo list-of-entries screen to leverage standard entriesdisplay machinery.
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/listOfEntriesScreen.php';
$screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
$pseudoScreen = $screen_handler->create();
$screen_handler->setDefaultListScreenVars($pseudoScreen, 0, $formObject);
// Override: no framework for ad hoc table forms
$pseudoScreen->setVar('title', 'All Groups');
$pseudoScreen->setVar('frid', 0);
$pseudoScreen->setVar('textwidth', 0);
// Suppress buttons not appropriate for group management
$pseudoScreen->setVar('useaddupdate', _AM_FORMULIZE_ADD_GROUP);
$pseudoScreen->setVar('usechangecols', '');
$pseudoScreen->setVar('useclone', '');
$pseudoScreen->setVar('usedelete', _formulize_DE_DELETE_GROUP_BUTTON);
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

// Set advanceview: name, members, then Type (conditional, last).
// Categories and instances are injected but embedded inside other columns, not shown separately.
$advanceViewArray = array(
	array('formulize_group_name_' . $fidInt, '', 'ASC', 'Box'),
	array('group_members', '', 0, 'Box'),
);
if (!empty(getEntriesAreGroupsForms())) {
	$advanceViewArray[] = array('eag_type', '', 0, 'Filter');
}

$pseudoScreen->setVar('advanceview', $advanceViewArray);

// Inject the group name as a hidden value per row so confirmDel() can display it.
$pseudoScreen->setVar('hiddencolumns', array(
	'formulize_group_name_' . $fidInt,
));

// Compute which groups are safe to delete (before readelements.php so we have
// the correct state for both the deletion handler and the checkbox suppression).
$deletableGroupIds = getDeletableGroupIds();

// Handle group deletion before readelements.php so Stage 2 of entriesdisplay
// never fires on these entries.
if (!empty($_POST['delconfirmed'])) {
	$deletableSet = array_flip($deletableGroupIds);
	foreach ($_POST as $key => $val) {
		if (strpos($key, 'delete_') === 0) {
			$groupIdToDelete = intval(substr($key, 7));
			if ($groupIdToDelete > 0 && isset($deletableSet[$groupIdToDelete])) {
				deleteGroupById($groupIdToDelete);
			}
		}
	}
	unset($_POST['delconfirmed']);
	// Recompute after deletions so the checkbox suppression below reflects the new state.
	$deletableGroupIds = getDeletableGroupIds();
}

// Pass the deletable set to entriesdisplay so it can suppress checkboxes for
// groups that do not qualify for deletion.
$GLOBALS['formulize_deletableGroupIds'] = array_flip($deletableGroupIds);

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/readelements.php";
$screen_handler->render($pseudoScreen, '', '');

// Override confirmDel() to show the group name in the confirmation prompt.
echo '<script>
function confirmDel() {
	var checked = jQuery("input[name^=\'delete_\']:checked");
	if(checked.length === 0){ return false; }
	var names = [];
	checked.each(function(){
		var gid = parseInt(this.name.replace("delete_",""),10);
		var nameInput = jQuery("input[name=\'hiddencolumn_" + gid + "_formulize_group_name_' . $fidInt . '\']");
		names.push(nameInput.length ? nameInput.val() : gid);
	});
	var msg = ' . json_encode(_formulize_DE_DELETE_GROUP_CONFIRM) . '.replace("%s", names.join(", "));
	if(confirm(msg)){
		window.document.controls.delconfirmed.value = 1;
		window.document.controls.ventry.value = "";
		showLoading();
		return true;
	}
	return false;
}
</script>';

include XOOPS_ROOT_PATH . '/footer.php';
