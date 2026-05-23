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

$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($fid);

// Build a pseudo list-of-entries screen to leverage standard entriesdisplay machinery.
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/listOfEntriesScreen.php';
$screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
$pseudoScreen = $screen_handler->create();
$screen_handler->setDefaultListScreenVars($pseudoScreen, 0, $formObject);
// Override: no framework for ad hoc table forms
$pseudoScreen->setVar('frid', 0);
$pseudoScreen->setVar('textwidth', 0);
// Suppress buttons not appropriate for group management
$pseudoScreen->setVar('useaddupdate', _AM_FORMULIZE_ADD_GROUP);
$pseudoScreen->setVar('usechangecols', '');
$pseudoScreen->setVar('useclone', '');
$pseudoScreen->setVar('usedelete', '');
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

// Set advanceview: groupid, name (sorted ASC by default), plus the two virtual injection columns.
$advanceViewArray = array(
	array('name',             '', 'ASC', 'Box'),
	array('group_categories', '', 0,     'Box'),
	array('group_entries',    '', 0,     'Box'),
);
$pseudoScreen->setVar('advanceview', $advanceViewArray);

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/readelements.php";
$screen_handler->render($pseudoScreen, '', '');

include XOOPS_ROOT_PATH . '/footer.php';
