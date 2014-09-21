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

// this file handles saving of submissions from the element_names page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
	return;
}

global $xoopsDB;
$ele_type = $_POST['element_type'];

// invoke the necessary objects
$element_handler = xoops_getmodulehandler('elements','formulize');
if($_POST['formulize_admin_key'] == "new") {
	$element = $element_handler->create();
	$fid = intval($_POST['formulize_form_id']);
	$element->setVar('id_form', $fid);
	$element->setVar('ele_type', $ele_type);
	$element->setVar('ele_display', 1);
	$element->setVar('ele_disabled', 0);
	$element->setVar('ele_req', 0);
	$element->setVar('ele_encrypt', 0);
	$original_handle = "";
} else {
	$ele_id = intval($_POST['formulize_admin_key']);
	$element = $element_handler->get(intval($_POST['formulize_admin_key']));
	$fid = $element->getVar('id_form');
	$original_handle = $element->getVar('ele_handle');
}

$element->setVar('ele_order', figureOutOrder($_POST['orderpref'], $element->getVar('ele_order'), $fid));

$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($fid);
if($formObject->getVar('lockedform')) {
	return;
}
// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $fid, $groups, $mid)) {
	return;
}

$isNew = $_POST['formulize_admin_key'] == "new" ? true : false;
foreach ($processedValues['elements'] as $property => $element_handle_name) {
	if ($property == "ele_handle") {
		$element_handle_name = formulizeForm::sanitize_handle_name($element_handle_name);
		if (strlen($element_handle_name)) {
			$firstUniqueCheck = true;
			while (!$uniqueCheck = $form_handler->isHandleUnique($element_handle_name, $ele_id)) {
				if ($firstUniqueCheck) {
					$element_handle_name = $element_handle_name . "_".$fid;
					$firstUniqueCheck = false;
				} else {
					$element_handle_name = $element_handle_name . "_copy";
				}
			}
		}
		$ele_handle = $element_handle_name;
		if ($element_handle_name != $processedValues['elements']['ele_handle']) {
			$_POST['reload_names_page'] = 1;
		}
	}
	$element->setVar($property, $element_handle_name);
}

if(!$ele_id = $element_handler->insert($element)) {
	print "Error: could not save the element: ".$xoopsDB->error();
}

if($original_handle) { // rewrite references in other elements to this handle (linked selectboxes)
	if($ele_handle != $original_handle) {
		$ele_handle_len = strlen($ele_handle) + 5 + strlen($fid);
		$orig_handle_len = strlen($original_handle) + 5 + strlen($fid);
		$lsbHandleFormDefSQL = "UPDATE " . $xoopsDB->prefix("formulize") . " SET ele_value = REPLACE(ele_value, 's:$orig_handle_len:\"$fid#*=:*$original_handle', 's:$ele_handle_len:\"$fid#*=:*$ele_handle') WHERE ele_value LIKE '%$fid#*=:*$original_handle%'"; // must include the cap lengths or else the unserialization of this info won't work right later, since ele_value is a serialized array!
		if(!$res = $xoopsDB->query($lsbHandleFormDefSQL)) {
			print "Error:  update of linked selectbox element definitions failed.";
		}
	}
}

if($_POST['reload_names_page'] OR $isNew) {
	$url = "";
	$ele_id_to_send = 0;
	if($isNew) {
		$url = XOOPS_URL . "/modules/formulize/admin/ui.php?page=element&fid=$fid&aid=".intval($_POST['aid'])."&ele_id=$ele_id";
		$ele_id_to_send = $ele_id;
	}
	print "/* evalnow */ ele_id = $ele_id_to_send; redirect = \"reloadWithScrollPosition('$url');\";";
}

function figureOutOrder($orderChoice, $oldOrder, $fid) {
	global $xoopsDB;
	if($orderChoice === "bottom") {
		$sql = "SELECT max(ele_order) as new_order FROM ".$xoopsDB->prefix("formulize")." WHERE id_form = $fid";
		$res = $xoopsDB->query($sql);
		$array = $xoopsDB->fetchArray($res);
		$orderChoice = $array['new_order'] + 1;
	} elseif($orderChoice === "top") {
		$orderChoice = 0;
	} else {
		// convert the orderpref from the element ID to the order
		$sql = "SELECT ele_order FROM ".$xoopsDB->prefix("formulize")." WHERE ele_id = $orderChoice AND id_form = $fid";
		$res = $xoopsDB->query($sql);
		$array = $xoopsDB->fetchArray($res);
		$orderChoice = $array['ele_order'];
	}
	$orderValue = $orderChoice + 1;
	if($oldOrder != $orderValue) {
		// and we need to reorder all the elements equal to and higher than the current element
		$sql = "UPDATE ".$xoopsDB->prefix("formulize")." SET ele_order = ele_order + 1 WHERE ele_order >= $orderValue AND id_form = $fid";
		$res = $xoopsDB->query($sql);
	}
	return $orderValue;
}
