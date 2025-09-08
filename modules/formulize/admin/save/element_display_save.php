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

// this file contains objects to retrieve screen(s) information for elements

include_once XOOPS_ROOT_PATH."/modules/formulize/class/formScreen.php";

// this file handles saving of submissions from the element display page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

// invoke the necessary objects
$element_handler = xoops_getmodulehandler('elements','formulize');
if(!$ele_id = intval($_GET['ele_id'])) { // on new element saves, new ele_id can be passed through the URL of this ajax save
  if(!$ele_id = intval($_POST['formulize_admin_key'])) {
    print "Error: could not determine element id when saving display settings";
    return;
  }
}
$element = $element_handler->get($ele_id);
if($element->isSystemElement) {
    exit();
}

$fid = $element->getVar('id_form');

$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($fid);
if($formObject->getVar('lockedform')) {
  return;
}
// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $fid, $groups, $mid)) {
  return;
}

// do not need to serialize this when assigning, since the elements class calls cleanvars from the xoopsobject on all properties prior to insertion, and that intelligently serializes properties that have been declared as arrays
list($parsedFilterSettings, $filterSettingsChanged) = parseSubmittedConditions('elementfilter', 'display-conditionsdelete');
list($parsedDisabledConditions, $disabledConditionsChanged) = parseSubmittedConditions('disabledconditions', 'disabled-conditionsdelete');
$_POST['reload_element_pages'] = ($filterSettingsChanged OR $disabledConditionsChanged) ? true : false;
$element->setVar('ele_filtersettings', $parsedFilterSettings);
$element->setVar('ele_disabledconditions', $parsedDisabledConditions);

// check that the checkboxes have no values, and if so, set them to "" in the processedValues array
if(!isset($_POST['elements-ele_private'])) {
    $processedValues['elements']['ele_private'] = "";
}
foreach($processedValues['elements'] as $property=>$value) {
  $element->setVar($property, $value);
}

if($_POST['elements_ele_display'][0] == "all") {
	$display = 1;
} else if($_POST['elements_ele_display'][0] == "none") {
	$display = 0;
} else {
	$display = "," . implode(",", $_POST['elements_ele_display']) . ",";
}
$element->setVar('ele_display', $display);

if($_POST['elements_ele_disabled'][0] == "none") {
	$disabled = 0;
} else if($_POST['elements_ele_disabled'][0] == "all"){
  $disabled = 1;
} else {
  $disabled = "," . implode(",", $_POST['elements_ele_disabled']) . ",";
}
$element->setVar('ele_disabled', $disabled);

// Saving element existence in multi-paged screens
$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
$legacyFormScreens = $_POST['elements_form_screens'];
if (!is_array($legacyFormScreens)) {
  $legacyFormScreens = array();
}
$multipageFormScreens = $_POST['multi_page_screens'];
if (is_array($multipageFormScreens)) {
	foreach($multipageFormScreens as $key => $page_value) {
		// can ignore this top tree node (the template must hand back some placeholder value for it).
		// Since the script below will loop through each child node regardless
		// RELIES ON JS TO HAVE SELECTED ALL THE SUB NODES IN BROWSER PRIOR TO SUBMIT!
		if ($page_value == "all") {
				unset($multipageFormScreens[$key]);
		}
	}
}
$screen_handler->addElementToScreenPagesFromUI($element, $multipageFormScreens, $legacyFormScreens);

if(!$ele_id = $element_handler->insert($element)) {
  print "Error: could not save the display settings for element: ".$xoopsDB->error();
}

// if this is a grid element, set the display conditions for the contained elements, if they have no conditions already!
if($element->getVar('ele_type')=='grid') {
	$ele_value = $element->getVar('ele_value');
	$gridCount = count(explode(",", $ele_value[1])) * count(explode(",", $ele_value[2]));
	include_once XOOPS_ROOT_PATH.'/modules/formulize/include/griddisplay.php';
	foreach(elementsInGrid($ele_value[4], $element->getVar('id_form'), $gridCount) as $gridElementId) {
		$gridElementObject = $element_handler->get($gridElementId);
		$gridElementFilterSettings = $gridElementObject->getVar('ele_filtersettings');
		if(!is_array($gridElementFilterSettings[0]) OR count((array) $gridElementFilterSettings[0]) == 0) {
			$gridElementObject->setVar('ele_filtersettings', $parsedFilterSettings);
			if(!$element_handler->insert($gridElementObject)) {
				$elementLabel = $gridElementObject->getVar('ele_colhead') ? $gridElementObject->getVar('ele_colhead') : $gridElementObject->getVar('ele_caption');
				print "Error: could not apply grid display settings to the element ".$elementLabel."\n";
			}
		}
	}
}

if($_POST['reload_element_pages']) {
  print "/* evalnow */ if(redirect=='') { redirect = 'reloadWithScrollPosition();'; }";
}

