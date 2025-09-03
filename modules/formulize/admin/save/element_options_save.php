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

// THIS FUNCTION TAKES A SERIES OF VALUES TYPED IN FORM RADIO BUTTONS, CHECKBOXES OR SELECTBOX OPTIONS, AND CHECKS TO SEE IF THEY WERE ENTERED WITH A UITEXT INDICATOR, AND IF SO, SPLITS THEM INTO THEIR ACTUAL VALUE PLUS THE UI TEXT AND RETURNS BOTH
// $values should be an array of all the options, so $ele_value for radio and checkboxes, $ele_value[2] for selectboxes
if(!function_exists("formulize_extractUIText")) {
function formulize_extractUIText($values) {
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
	// values are the text that was typed in
	// keys should remain unchanged
	$uitext = array();
	foreach($values as $key=>$value) {
		//print "<br>original value: $value<br>";
		//print "key: $key<br>";
		if(strstr($value, "|") AND substr(trans($value), 0, 7) != "{OTHER|") { // check for the pressence of the uitext deliminter, the "pipe" character
			$pipepos = strpos($value, "|");
			//print "pipe found: $pipepos<br>";
			$uivalue = substr($value, $pipepos+1);
			//print "uivalue: $uivalue<br>";
			$value = substr($value, 0, $pipepos);
			//print "value: $value<br>";
			$values[$key] = $value;
			$uitext[$value] = $uivalue;
		} else {
			$values[$key] = $value;
		}
	}
	return array(0=>$values, 1=>$uitext);
}
}

// this file handles saving of submissions from the element options page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

// invoke the necessary objects
$element_handler = xoops_getmodulehandler('elements','formulize');
if(!$ele_id = intval($_GET['ele_id'])) { // on new element saves, new ele_id can be passed through the URL of this ajax save
  if(!$ele_id = intval($_POST['formulize_admin_key'])) {
    print "Error: could not determine element id when saving options";
    return;
  }
}
$element = $element_handler->get($ele_id);
if($element->isSystemElement) {
	exit();
}
$ele_type = $element->getVar('ele_type');
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

if($_POST['element_delimit']) {
  if($_POST['element_delimit'] == "custom") {
    $processedValues['elements']['ele_delim'] = $_POST['element_delim_custom'];
  } else {
    $processedValues['elements']['ele_delim'] = $_POST['element_delimit'];
  }
}

if($ele_type == "subform") {

    if(!isset($processedValues['elements']['ele_value']['show_delete_button'])) {
        $processedValues['elements']['ele_value']['show_delete_button'] = 0;
    }
    if(!isset($processedValues['elements']['ele_value']['show_clone_button'])) {
        $processedValues['elements']['ele_value']['show_clone_button'] = 0;
    }

    if(!isset($processedValues['elements']['ele_value']['enforceFilterChanges'])) {
        $processedValues['elements']['ele_value']['enforceFilterChanges'] = 0;
    }

  if(!$_POST['elements-ele_value'][3]) {
    $processedValues['elements']['ele_value'][3] = 0;
  }
  // handle the "start" value, formerlly the blanks value (ele_value[2])
  // $_POST['subform_start'] will be 'empty', 'blanks', or 'prepop'
  // We need to set ele_value[2] to be the appropriate number of blanks
  // We need to set ele_value[subform_prepop_element] to be the element id of the element prepops are based on
  switch($_POST['subform_start']) {
    case "blanks":
        $processedValues['elements']['ele_value'][2] = intval($_POST['number_of_subform_blanks']);
        $processedValues['elements']['ele_value']['subform_prepop_element'] = 0;
        break;
    case "prepop":
        $processedValues['elements']['ele_value'][2] = 0;
        $processedValues['elements']['ele_value']['subform_prepop_element'] = intval($_POST['subform_start_prepop_element']);
        break;
    default:
        // implicitly case 'empty'
        $processedValues['elements']['ele_value'][2] = 0;
        $processedValues['elements']['ele_value']['subform_prepop_element'] = 0;
        break;

  }
  $processedValues['elements']['ele_value'][1] = implode(",",(array)$_POST['elements_ele_value_1']);
  $processedValues['elements']['ele_value']['disabledelements'] = (isset($_POST['elements_ele_value_disabledelements']) AND count((array) $_POST['elements_ele_value_disabledelements']) > 0) ? implode(",",$_POST['elements_ele_value_disabledelements']) : array();
  list($processedValues['elements']['ele_value'][7], $_POST['reload_option_page']) = parseSubmittedConditions('subformfilter', 'optionsconditionsdelete'); // post key, delete key
}

// check to see if we should be reassigning user submitted values, and if so, trap the old ele_value settings, and the new ones, and then pass off the job to the handling function that does that change
// SHOULD BE MOVED INSIDE CUSTOM ELEMENT CLASS FILES?
if(isset($_POST['changeuservalues']) AND $_POST['changeuservalues']==1) {
  include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
  $data_handler = new formulizeDataHandler($fid);
	$newValues = array();
  switch($ele_type) {
    case "select":
      $newValues = $processedValues['elements']['ele_value'][2];
      break;
  }
	if(!empty($newValues)) {
  	if(!$changeResult = $data_handler->changeUserSubmittedValues($ele_id, $newValues)) {
    	print "Error updating user submitted values for the options in element $ele_id";
  	}
	}
}


$ele_value_before_adminSave = "";
$ele_value_after_adminSave = "";
// call the adminSave method. IT SHOULD SET ele_value ON THE ELEMENT OBJECT, AND MUST SET IT IF IT IS MAKING CHANGES.
if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
  $customTypeHandler = xoops_getmodulehandler($ele_type."Element", 'formulize');
  $ele_value_before_adminSave = serialize($element->getVar('ele_value'));
  $changed = $customTypeHandler->adminSave($element, $processedValues['elements']['ele_value']); // cannot use getVar to retrieve ele_value from element, due to limitation of the base object class, when dealing with set values that are arrays and not being gathered directly from the database (it wants to unserialize them instead of treating them as literals)
  $ele_value_after_adminSave = is_array($element->vars['ele_value']['value']) ? serialize($element->vars['ele_value']['value']) : $element->vars['ele_value']['value']; // get raw value because it won't have been serialized yet since it hasn't been written to the DB...if we use getVar, it will try to unserialize it for us, but that won't work because it hasn't been serialized yet -- if this value is not an array, take it as is though, since then it is simply unchanged from when it was originally set as the serialized value we want
  if($ele_value_before_adminSave === $ele_value_after_adminSave) {
    unset($processedValues['elements']['ele_value']); // no change, so nothing to write below
  }
  // user indicated we should reload the page due to a change
  if($changed) {
    $_POST['reload_option_page'] = true; // force a reload, since the developer probably changed something the user did in the form, so we should reload to show the effect of this change
  }
}


foreach($processedValues['elements'] as $property=>$value) {
  // if we're setting something other than ele_value, or
  // we're setting ele_value for an element type that
  // has an adminSave method of its own.
  // We don't want to set ele_value if it was modified during
  // adminSave, because we might clobber user's changes
  // so the user has to setVar in adminSave themselves!

  if($property != 'ele_value' OR $ele_value_after_adminSave === "") {
  	$element->setVar($property, $value);
  }
}

if(!$ele_id = $element_handler->insert($element)) {
  print "Error: could not save the options for element: ".$xoopsDB->error();
}

if($_POST['reload_option_page'] OR (isset($ele_value['optionsLimitByElement']) AND $ele_value['optionsLimitByElement'] != $processedValues['elements']['ele_value']['optionsLimitByElement'])) {
  print "/* evalnow */ if(redirect=='') { redirect = 'reloadWithScrollPosition();'; }";
}

