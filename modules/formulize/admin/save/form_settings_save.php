<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2010 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions                                  ##
##  URL: http://www.freeformsolutions.ca/formulize                           ##
##  Project: Formulize                                                       ##
###############################################################################

// this file handles saving of submissions from the form_settings page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
	return;
}

// invoke the necessary objects
$form_handler = xoops_getmodulehandler('forms','formulize');
$application_handler = xoops_getmodulehandler('applications','formulize');
$newAppObject = false;
$selectedAppObjects = array();
if($_POST['formulize_admin_key'] == "new") {
	$formObject = $form_handler->create();
} else {
	$fid = intval($_POST['formulize_admin_key']);
	$formObject = $form_handler->get($fid);
}

// Check if the form is locked down
if($formObject->getVar('lockedform')) {
	return;
}

// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $fid, $groups, $mid) AND $_POST['formulize_admin_key'] != "new") {
	return;
}

if(($_POST['new_app_yes_no'] == "yes" AND $_POST['applications-name'])) {
	$newAppObject = $application_handler->create();
}

// get all the existing applcations that this form object was assigned to
if(isset($_POST['apps']) AND count($_POST['apps']) > 0) {
	$selectedAppObjects = $application_handler->get($_POST['apps']);
}

// interpret form object values that were submitted and need special handling
$processedValues['forms']['headerlist'] = (isset($_POST['headerlist']) and is_array($_POST['headerlist']))
? "*=+*:".implode("*=+*:",$_POST['headerlist']) : "";

// form_handle cannot have any period, strip all of the periods out
$form_handle_from_ui = $processedValues['forms']['form_handle'];
$corrected_form_handle = formulizeForm::sanitize_handle_name($form_handle_from_ui);
if($corrected_form_handle != $form_handle_from_ui) {
	$formulize_altered_form_handle = true;
	$processedValues['forms']['form_handle'] = $corrected_form_handle;
}

// form_handle can not be blank, default to form id if blank
if( $processedValues['forms']['form_handle'] == "" ) {
	$processedValues['forms']['form_handle'] = $fid;
}
$old_form_handle = $formObject->getVar( "form_handle" );

foreach($processedValues['forms'] as $property=>$value) {
	$formObject->setVar($property, $value);
}
if(!$form_handler->insert($formObject)) {
	print "Error: could not save the form properly: ".$xoopsDB->error();
}
$fid = $formObject->getVar('id_form');
if($_POST['formulize_admin_key'] == "new") {
	if(!$tableCreateRes = $form_handler->createDataTable($fid)) {
		print "Error: could not create data table for new form";
	}
	global $xoopsDB;
	// create the default screens for this form
	$formScreenHandler = xoops_getmodulehandler('formScreen', 'formulize');
	$defaultFormScreen = $formScreenHandler->create();
	$defaultFormScreen->setVar('displayheading', 1);
	$defaultFormScreen->setVar('reloadblank', 0);
	$defaultFormScreen->setVar('savebuttontext', _formulize_SAVE);
	$defaultFormScreen->setVar('alldonebuttontext', _formulize_DONE);
	$defaultFormScreen->setVar('title',"Regular '".$formObject->getVar('title')."'");
	$defaultFormScreen->setVar('fid',$fid);
	$defaultFormScreen->setVar('frid',0);
	$defaultFormScreen->setVar('type','form');
	$defaultFormScreen->setVar('useToken',1);
	if(!$defaultFormScreenId = $formScreenHandler->insert($defaultFormScreen)) {
		print "Error: could not create default form screen";
	}
	$listScreenHandler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
	$screen = $listScreenHandler->create();
	// View
	$screen->setVar('defaultview','all');
	$screen->setVar('usecurrentviewlist',_formulize_DE_CURRENT_VIEW);
	$screen->setVar('limitviews',serialize(array(0=>'allviews')));
	$screen->setVar('useworkingmsg',1);
	$screen->setVar('usescrollbox',1);
	$screen->setVar('entriesperpage',10);
	$screen->setVar('viewentryscreen',$defaultFormScreenId);
	// Headings
	$screen->setVar('useheadings',1);
	$screen->setVar('repeatheaders',5);
	$screen->setVar('usesearchcalcmsgs',1);
	$screen->setVar('usesearch',1);
	$screen->setVar('columnwidth',0);
	$screen->setVar('textwidth',35);
	$screen->setVar('usecheckboxes',0);
	$screen->setVar('useviewentrylinks',1);
	$screen->setVar('desavetext',_formulize_SAVE);
	// Buttons
	$screen->setVar('useaddupdate',_formulize_DE_ADDENTRY);
	$screen->setVar('useaddmultiple',_formulize_DE_ADD_MULTIPLE_ENTRY);
	$screen->setVar('useaddproxy',_formulize_DE_PROXYENTRY);
	$screen->setVar('useexport',_formulize_DE_EXPORT);
	$screen->setVar('useimport',_formulize_DE_IMPORT);
	$screen->setVar('usenotifications',_formulize_DE_NOTBUTTON);
	$screen->setVar('usechangecols',_formulize_DE_CHANGECOLS);
	$screen->setVar('usecalcs',_formulize_DE_CALCS);
	$screen->setVar('useadvcalcs',_formulize_DE_ADVCALCS);
	$screen->setVar('useexportcalcs',_formulize_DE_EXPORT_CALCS);
	$screen->setVar('useadvsearch','');
	$screen->setVar('useclone',_formulize_DE_CLONESEL);
	$screen->setVar('usedelete',_formulize_DE_DELETESEL);
	$screen->setVar('useselectall',_formulize_DE_SELALL);
	$screen->setVar('useclearall',_formulize_DE_CLEARALL);
	$screen->setVar('usereset',_formulize_DE_RESETVIEW);
	$screen->setVar('usesave',_formulize_DE_SAVE);
	$screen->setVar('usedeleteview',_formulize_DE_DELETE);
	$screen->setVar('title',"Entries in '".$formObject->getVar('title')."'");
	$screen->setVar('fid',$fid);
	$screen->setVar('frid',0);
	$screen->setVar('type','listOfEntries');
	$screen->setVar('useToken',1);
	if(!$defaultListScreenId = $listScreenHandler->insert($screen)) {
		print "Error: could not create default list screen";
	}
	$formObject->setVar('defaultform', $defaultFormScreenId);
	$formObject->setVar('defaultlist', $defaultListScreenId);
	if(!$form_handler->insert($formObject)) {
		print "Error: could not update form object with default screen ids: ".$xoopsDB->error();
	}
	// add edit permissions for the selected groups
	$gperm_handler = xoops_gethandler('groupperm');
	foreach($_POST['groups_can_edit'] as $thisGroupId) {
		$gperm_handler->addRight('edit_form', $fid, intval($thisGroupId), getFormulizeModId());
	}


} else if( $old_form_handle && $formObject->getVar( "form_handle" ) != $old_form_handle ) {
	//print "rename from $old_form_handle to " . $formObject->getVar( "form_handle" );
	if(!$renameResult = $form_handler->renameDataTable($old_form_handle, $formObject->getVar( "form_handle" ), $formObject)) {
		exit("Error: could not rename the data table in the database.");
	}
}

$selectedAppIds = array();
if($newAppObject) {
	// assign the form id to this new application
	$processedValues['applications']['forms'] = serialize(array($fid));
	foreach($processedValues['applications'] as $property=>$value) {
		$newAppObject->setVar($property, $value);
	}
	if(!$application_handler->insert($newAppObject)) {
		print "Error: could not save the new application properly: ".$xoopsDB->error();
	}
	$selectedAppIds[] = $newAppObject->getVar('appid');
}

// get the applications this form is assigned to
$assignedAppsForThisForm = $application_handler->getApplicationsByForm($fid);

// assign this form as required to the selected applications
foreach($selectedAppObjects as $thisAppObject) {
	$selectedAppIds[] = $thisAppObject->getVar('appid');
	$thisAppForms = $thisAppObject->getVar('forms');
	if(!in_array($fid, $thisAppForms)) {
		$thisAppForms[] = $fid;
		$thisAppObject->setVar('forms', serialize($thisAppForms));
		if(!$application_handler->insert($thisAppObject)) {
			print "Error: could not add the form to one of the applications properly: ".$xoopsDB->error();
		}
	}
}

// now remove the form from any applications it used to be assigned to, which were not selected
foreach($assignedAppsForThisForm as $assignedApp) {
	if(!in_array($assignedApp->getVar('appid'), $selectedAppIds)){
		// the form is no longer assigned to this app, so remove it from the apps form list
		$assignedAppForms = $assignedApp->getVar('forms');
		$key = array_search($fid, $assignedAppForms);
		unset($assignedAppForms[$key]);
		sort($assignedAppForms); // resets all the keys so there's no gaps
		$assignedApp->setVar('forms',serialize($assignedAppForms));
		if(!$application_handler->insert($assignedApp)) {
			print "Error: could not update one of the applications this form used to be assigned to, so that it's not assigned anymore.";
		}
	}
}

// if we're making a new table form, then synch the "elements" for the form with the target table
if(isset($_POST['forms-tableform'])) {
	if(!$form_handler->createTableFormElements($_POST['forms-tableform'], $fid)) {
		print "Error: could not create all the placeholder elements for the tableform";
	}
}

// if the revision history flag was on, then create the revisions history table, if it doesn't exist already
if(isset($_POST['forms-store_revisions']) AND $_POST['forms-store_revisions'] AND !$form_handler->revisionsTableExists($formObject)) {
	if(!$form_handler->createDataTable($fid, 0, false, true)) { // 0 is the id of a form we're cloning, false is the map of old elements to new elements when cloning so n/a here, true is the flag for making a revisions table
		print "Error: could not create the revision history table for the form";
	}
}

// if the form name was changed, then force a reload of the page...reload will be the application id
if((isset($_POST['reload_settings']) AND $_POST['reload_settings'] == 1) OR $formulize_altered_form_handle OR $newAppObject OR ($_POST['application_url_id'] AND !in_array($_POST['application_url_id'], $selectedAppIds))) {
	if(!in_array($_POST['application_url_id'], $selectedAppIds)) {
		$appidToUse = intval($selectedAppIds[0]);
	} else {
		$appidToUse = intval($_POST['application_url_id']);
	}
	print "/* eval */ ";
	if($formulize_altered_form_handle) {
		print " alert('Some characters, such as punctuation, were removed from the form handle because they ".
			"are not allowed in the database table names or PHP variables.');\n";
	}
	print " reloadWithScrollPosition('".XOOPS_URL ."/modules/formulize/admin/ui.php?page=form&aid=$appidToUse&fid=$fid');";
}

// need to do some other stuff here later to setup defaults for...
// screens?
// menu items?
// permissions?

// Auto menu link creation
// The link is shown to to Webmaster and registered users only (1,2 in $menuitems)
if($_POST['formulize_admin_key'] == "new") {
	$menuitems = "null::" . formulize_db_escape($formObject->getVar('title')) . "::fid=" . formulize_db_escape($fid) . "::::1,2::null";
	if(!empty($selectedAppIds)) {
		foreach($selectedAppIds as $appid) {
			$application_handler->insertMenuLink(formulize_db_escape($appid), $menuitems);
		}
	} else {
		$application_handler->insertMenuLink(0, $menuitems);
	}
}
