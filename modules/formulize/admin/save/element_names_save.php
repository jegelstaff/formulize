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
  $element->setVar('ele_required', 0);
  $element->setVar('ele_encrypt', 0);
	$element->setVar('ele_order', null);
  $original_handle = "";
} else {
  $ele_id = intval($_POST['formulize_admin_key']);
  $element = $element_handler->get(intval($_POST['formulize_admin_key']));
  $fid = $element->getVar('id_form');
  $original_handle = $element->getVar('ele_handle');
}

if($element->isSystemElement) {
	exit();
}

if($element->getVar('ele_type') != 'grid') {
	$element->setVar('ele_order', figureOutOrder($_POST['orderpref'], $element->getVar('ele_order'), $fid));
}
$element->setVar('ele_sort', $_POST['sortpref']);

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
	$element->setVar($property, $element_handle_name);
}

// IF WHEN ELEMENTS USE UPSERT... ELEMENT TYPE DETERMINATION AND VALIDATION IS BASED ON ELEMENTS THAT HAVE MCP DESCRIPTION METHOD
// SO CUSTOM ELEMENTS, ETC, WON'T WORK WITH THAT. NEED A MORE ROBUST SOLUTION.

if(!$ele_id = $element_handler->insert($element)) {
  print "Error: could not save the element: ".$xoopsDB->error();
}

$finalHandle = $element->getVar('ele_handle');
if($finalHandle != $processedValues['elements']['ele_handle']) {
	$_POST['reload_names_page'] = 1;
}

// if the handle changed, we need to rename references to it in other elements, code files
$element_handler->renameElementResources($element, $original_handle);

// handle principal identifier
if($_POST['principalidentifier']) {
	$formObject->setVar('pi', $ele_id);
	$form_handler->insert($formObject);
} elseif($formObject->getVar('pi') == $ele_id) {
	$formObject->setVar('pi', 0);
	$form_handler->insert($formObject);
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
