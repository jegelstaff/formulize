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

// this file handles saving of submissions from the form_elements page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
	return;
}
$fid = intval($_POST['formulize_admin_key']);
$form_handler = xoops_getmodulehandler('forms','formulize');
$formObject = $form_handler->get($fid);

// Check if the form is locked down
if($formObject->getVar('lockedform')) {
	return;
}

// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $fid, $groups, $mid)) {
	return;
}

// invoke the necessary objects
$element_handler = xoops_getmodulehandler('elements','formulize');

// group elements by id
$processedElements = array();
foreach($processedValues['elements'] as $property=>$values) {
	foreach($values as $key=>$value) {
		$processedElements[$key][$property] = $value;
	}
}

// retrieve all the elements that belong to this form
$elements = $element_handler->getObjects(null,$fid);

// get the new order of the elements...
$newOrder = explode("drawer-2[]=", str_replace("&", "", $_POST['elementorder']));
unset($newOrder[0]);
// newOrder will have keys corresponding to the new order, and values corresponding to the old order

if(count($elements) != count($newOrder)) {
	print "Error: the number of elements being saved did not match the number of elements already in the database";
	return;
}

// modify elements
$oldOrderNumber = 1;
foreach($elements as $element) {
	$ele_id = $element->getVar('ele_id');

	// reset elements to deault
	$element->setVar('ele_req',0);
	$element->setVar('ele_private',0);
	$newOrderNumber = array_search($oldOrderNumber,$newOrder);
	$element->setVar('ele_order',$newOrderNumber);
	if($oldOrderNumber != $newOrderNumber) {
		$_POST['reload_elements'] = 1; // need to reload since the drawer numbers will be out of sequence now
	}
	$oldOrderNumber++;

	// apply settings submitted by user
	foreach($processedElements[$ele_id] as $property=>$value) {
		$element->setVar($property,$value);
	}

	// if there was no display property sent, and there was no custom flag sent, then blank the display settings
	if(!isset($processedElements[$ele_id]['ele_display']) AND !isset($_POST['customDisplayFlag'][$ele_id])) {
		$element->setVar('ele_display',0);
	}

	// presist changes
	if(!$element_handler->insert($element)) {
		print "Error: could not save the form elements properly: ".$xoopsDB->error();
	}
}

// handle any operations
if($_POST['convertelement']) {
	global $xoopsModuleConfig;
	$element =& $element_handler->get($_POST['convertelement']);
	$ele_type = $element->getVar('ele_type');
	$new_ele_value = array();
	if($ele_type == "text") { // converting to textarea
		$ele_value = $element->getVar('ele_value');
		$new_ele_value[0] = $ele_value[2]; // default value
		$new_ele_value[1] = $xoopsModuleConfig['ta_rows'];
		$new_ele_value[2] = $ele_value[0]; // width become cols
		$new_ele_value[3] = $ele_value[4]; // preserve any association that is going on
		$element->setVar('ele_value', $new_ele_value);
		$element->setVar('ele_type', "textarea");
		if( !$element_handler->insert($element)) {
			print "Error: could not complete conversion of the element";
		}
	} elseif($ele_type=="textarea") {
		$ele_value = $element->getVar('ele_value');
		$new_ele_value[0] = $ele_value[2]; // cols become width
		$new_ele_value[1] = $xoopsModuleConfig['t_max'];
		$new_ele_value[2] = $ele_value[0]; // default value
		$new_ele_value[3] = 0; // allow anything (do not restrict to just numbers)
		$new_ele_value[4] = $ele_value[3]; // preserve any association that is going on
		$element->setVar('ele_value', $new_ele_value);
		$element->setVar('ele_type', "text");
		if( !$element_handler->insert($element)) {
			print "Error: could not complete conversion of the element";
		}
	} elseif($ele_type=="radio") {
		$element->setVar('ele_type', "checkbox"); // just need to change type, ele_value format is the same
		if( !$element_handler->insert($element)) {
			print "Error: could not complete conversion of the element";
		} else {
			include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
			$data_handler = new formulizeDataHandler($element->getVar('id_form'));
			if(!$data_handler->convertRadioDataToCheckbox($element)) {
				print "Error: ". _AM_ELE_CHECKBOX_DATA_NOT_READY;
			}
		}
	} elseif($ele_type=="checkbox") {
		$element->setVar('ele_type', "radio");  // just need to change type, ele_value format is the same
		if( !$element_handler->insert($element)) {
			print "Error: could not complete conversion of the element";
		} else {
			include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
			$data_handler = new formulizeDataHandler($element->getVar('id_form'));
			if(!$data_handler->convertCheckboxDataToRadio($element)) {
				print "Error: "._AM_ELE_RADIO_DATA_NOT_READY;
			}
		}
	} elseif($ele_type=="select") {
		$element->setVar('ele_type', 'checkbox');
		$old_ele_value = $element->getVar('ele_value');
		if($element->isLinked) {
	  		// get all the source values, and make an array of those...ignore filters and so on
			$boxproperties = explode("#*=:*", $old_ele_value[2]);
			$sourceFid = $boxproperties[0];
			$sourceHandle = $boxproperties[1];
			$data_handler = new formulizeDataHandler($sourceFid);
			$options = $data_handler->findAllValuesForField($sourceHandle, "ASC");
			foreach($options as $option) {
				$new_ele_value[$option] = 0;
			}
		} else {
			$new_ele_value = $old_ele_value[2];
		}
		$element->setVar('ele_value', $new_ele_value);
		$element->setVar('ele_delim', 'br');
		if( !$element_handler->insert($element)) {
			print "Error: could not complete conversion of the element";
		}
	}
}

if($_POST['deleteelement']) {
	$element = $element_handler->get($_POST['deleteelement']);
	$ele_type = $element->getVar('ele_type');
	$element_handler->delete($element);
	if($ele_type != "areamodif" AND $ele_type != "ib" AND $ele_type != "sep" AND $ele_type != "subform" AND $ele_type != "grid") {
		$element_handler->deleteData($element); //added aug 14 2005 by jwe
	}
}

if($_POST['cloneelement']) {
	global $xoopsDB;
	// create a new element, and then direct them to the page for editing that element
	$thisElementObject = $element_handler->get($_POST['cloneelement']);
	$oldHandle = $thisElementObject->getVar('ele_handle');
	$thisElementObject->setVar('ele_id', 0);
	$thisElementObject->setVar('ele_handle', "");
	$sql = "SELECT max(ele_order) as new_order FROM ".$xoopsDB->prefix("formulize")." WHERE id_form = $fid";
	$res = $xoopsDB->query($sql);
	$array = $xoopsDB->fetchArray($res);
	$thisElementObject->setVar('ele_order', $array['new_order'] + 1);
	$ele_caption = $thisElementObject->getVar('ele_caption');
	$ele_colhead = $thisElementObject->getVar('ele_colhead');
	$thisElementObject->setVar('ele_caption', $ele_caption . " copy");
	if($ele_colhead) {
		$thisElementObject->setVar('ele_colhead', $ele_colhead . " copy");
	}
	$element_handler->insert($thisElementObject);
	$ele_id = $thisElementObject->getVar('ele_id');
	$fieldStateSQL = "SHOW COLUMNS FROM " . $xoopsDB->prefix("formulize_" . $thisElementObject->getVar('form_handle')) ." LIKE '$oldHandle'"; // note very odd use of LIKE as a clause of its own in SHOW statements, very strange, but that's what MySQL does
	if(!$fieldStateRes = $xoopsDB->query($fieldStateSQL)) {
  		$dataType = "text";
	} else {
		$fieldStateData = $xoopsDB->fetchArray($fieldStateRes);
		$dataType = $fieldStateData['Type'];
	}
	$form_handler->insertElementField($thisElementObject, $dataType);
	print "/* eval */ window.location = '".XOOPS_URL."/modules/formulize/admin/ui.php?page=element&ele_id=$ele_id&aid=".intval($_POST['aid'])."';";
}

if($_POST['reload_elements']) {
	print "/* eval */ reloadWithScrollPosition();";
}

?>
