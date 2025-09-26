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
##  URL: http://www.formulize.org                           ##
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
if($_POST['formulize_admin_key'] == "new") {
  $formObject = $form_handler->create();
	$fid = 0;
} else {
  $fid = intval($_POST['formulize_admin_key']);
  $formObject = $form_handler->get($fid);
}
$processedValues['forms']['fid'] = $fid;

// Check if the form is locked down
if($formObject->getVar('lockedform')) {
  return;
}

// check if the user has permission to edit the form
if($_POST['formulize_admin_key'] != "new" AND !$gperm_handler->checkRight("edit_form", $fid, $groups, $mid)) {
  return;
}

if(($_POST['new_app_yes_no'] == "yes" AND $_POST['applications-name'])) {
  $newAppObject = $application_handler->create();
	foreach($processedValues['applications'] as $property=>$value) {
    $newAppObject->setVar($property, $value);
  }
	if(!$application_handler->insert($newAppObject)) {
    print "Error: could not save the new application properly: ".$xoopsDB->error();
  } else {
  	$_POST['apps'][] = $newAppObject->getVar('appid');
	}
}

// interpret form object values that were submitted and need special handling
$processedValues['forms']['headerlist'] = (isset($_POST['headerlist']) and is_array($_POST['headerlist']))
    ? "*=+*:".implode("*=+*:",$_POST['headerlist']) : "";

$applicationIds = (isset($_POST['apps']) AND is_array($_POST['apps'])) ? $_POST['apps'] : array(0);
$groupsCanEdit = (isset($_POST['groups_can_edit']) AND is_array($_POST['groups_can_edit'])) ? $_POST['groups_can_edit'] : array(XOOPS_GROUP_ADMIN);
$formObject = formulizeHandler::upsertFormSchemaAndResources($processedValues['forms'], $groupsCanEdit, $applicationIds);
$fid = $formObject->getVar('fid');

// check if form handle changed
$formulize_altered_form_handle = $processedValues['forms']['form_handle'] != $formObject->getVar('form_handle') ? true : false;
// check if singular or plural changed
$singularPluralChanged = ($processedValues['forms']['plural'] != $formObject->getVar('plural') OR $processedValues['forms']['singular'] != $formObject->getVar('singular')) ? true : false;

// if we're making a new table form, then synch the "elements" for the form with the target table
if(isset($_POST['forms-tableform'])) {
  if(!$form_handler->createTableFormElements($_POST['forms-tableform'], $fid)) {
    print "Error: could not create all the placeholder elements for the tableform";
  }
}

// create the PI element if requested
if($_POST['pi_new_yes_no'] == "yes" AND isset($_POST['pi_new_caption']) AND $_POST['pi_new_caption'] != "") {
	$element_handler = xoops_getmodulehandler('textElement','formulize');
	$options = $element_handler->validateEleValuePublicAPIProperties([]);
	$elementObjectProperties = array(
		'id_form' => $fid,
		'ele_type' => 'text',
		'ele_caption' => $_POST['pi_new_caption'],
		'ele_handle' => $formObject->getVar('form_handle')."_".formulizeElement::sanitize_handle_name($_POST['pi_new_caption']),
		'ele_required' => 1,
		'ele_display' => 1,
		'ele_disabled' => 0,
		'ele_order' => 0,
		'ele_value' => $options['ele_value']
	);
	$screenIdsAndPagesForAdding = array(
		$formObject->getVar('defaultform') => array(0) // page 0 is the first page
	);
	$dataType = 'text';
	formulizeHandler::upsertElementSchemaAndResources($elementObjectProperties, $screenIdsAndPagesForAdding, $dataType, pi: true);
}

// if the form name was changed, etc, then force a reload of the page...
if((isset($_POST['reload_settings']) AND $_POST['reload_settings'] == 1) OR $formulize_altered_form_handle OR $newAppObject OR $singularPluralChanged OR ($_POST['application_url_id'] AND !in_array($_POST['application_url_id'], $applicationIds))) {
  if(!in_array($_POST['application_url_id'], $applicationIds)) {
    $appidToUse = count($applicationIds) > 0 ? intval($applicationIds[0]) : 0;
  } else {
    $appidToUse = intval($_POST['application_url_id']);
  }
  print "/* eval */ ";
  if($formulize_altered_form_handle) {
    print " alert('The Form Handle was changed for uniqueness, or because some characters, such as punctuation, are not allowed in the database table names or PHP variables.');\n";
  }
  print " reloadWithScrollPosition('".XOOPS_URL ."/modules/formulize/admin/ui.php?page=form&aid=$appidToUse&fid=$fid');";
}


