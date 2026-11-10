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

if(isset($_POST['orderpref'])) {
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

if(!$isNew
	AND ((
		$element->getVar('ele_colhead')
		AND $element->getVar('ele_colhead') != $processedValues['elements']['ele_colhead']
	) OR (
		$element->getVar('ele_caption') != $processedValues['elements']['ele_caption']
	))) {
	$_POST['reload_names_page'] = 1;
}

// only a search interface this element can have is kept; anything else leaves the setting as it was. A new element
// isn't an object of its own type yet, so ask one of those which interfaces suit it.
if(isset($processedValues['elements']['ele_searchtype'])) {
	$searchTypeElement = $element;
	if($isNew AND file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".basename($ele_type)."Element.php")
		AND $typeHandler = xoops_getmodulehandler(basename($ele_type)."Element", 'formulize')) {
		$searchTypeElement = $typeHandler->create();
	}
	if(!in_array($processedValues['elements']['ele_searchtype'], $searchTypeElement->allowedListSearchTypes(), true)) {
		unset($processedValues['elements']['ele_searchtype']);
	}
}

foreach ($processedValues['elements'] as $property => $element_handle_name) {
	$element->setVar($property, $element_handle_name);
}

// IF WHEN ELEMENTS USE UPSERT... ELEMENT TYPE DETERMINATION AND VALIDATION IS BASED ON ELEMENTS THAT HAVE MCP DESCRIPTION METHOD
// SO CUSTOM ELEMENTS, ETC, WON'T WORK WITH THAT. NEED A MORE ROBUST SOLUTION.

if(!$ele_id = $element_handler->insert($element)) {
  print "Error: could not save the element: ".$xoopsDB->error();
}

$finalHandle = $element->getVar('ele_handle');
if($element->isSystemElement == false AND $finalHandle != $processedValues['elements']['ele_handle']) {
	$_POST['reload_names_page'] = 1;
}

// if the handle changed, we need to rename references to it in other elements, code files
$renameWarnings = $element_handler->renameElementResources($element, $original_handle);

// what to tell the person about the handle: one they asked for that Formulize keeps for itself, and the places the
// rename left naming the old handle, because they may or may not mean this element
$handleNotices = array();
$requestedHandle = formulizeElement::sanitize_handle_name($processedValues['elements']['ele_handle']);
if($element->isSystemElement == false AND $requestedHandle !== '' AND $requestedHandle != $finalHandle
	AND in_array($requestedHandle, formulizeElementsHandler::reservedHandleNames(), true)) {
	$handleNotices[] = '<p>'.sprintf(_AM_ELE_HANDLE_RESERVED, '<b>'.htmlspecialchars($requestedHandle).'</b>', '<b>'.htmlspecialchars($finalHandle).'</b>').'</p>';
}
if($renameWarnings) {
	$handleNotices[] = '<p>'.sprintf(_AM_ELE_RENAME_LEFT_INTRO, '<b>'.htmlspecialchars($original_handle).'</b>', '<b>'.htmlspecialchars($finalHandle).'</b>').'</p>'
		.'<ul class="formulize-rename-left"><li>'.implode('</li><li>', array_map('htmlspecialchars', $renameWarnings)).'</li></ul>';
}

// handle principal identifier
if($_POST['principalidentifier']) {
	$formObject->setVar('pi', $ele_id);
	$form_handler->insert($formObject);
} elseif($formObject->getVar('pi') == $ele_id) {
	$formObject->setVar('pi', 0);
	$form_handler->insert($formObject);
}

// a dialog telling a person about their element's new handle, for the admin save to run, which runs $whenClosed after
$handleNoticeDialog = function($whenClosed) use ($handleNotices) {
  $okButton = json_encode(_AM_ELE_RENAME_NOTICE_OK);
  return "$('<div id=\"elementHandleNotice\"></div>').html(".json_encode(implode('', $handleNotices)).").dialog({modal: true, width: 600, title: "
    .json_encode(_AM_ELE_RENAME_NOTICE_TITLE).", buttons: (function() { var b = {}; b[$okButton] = function() { $(this).dialog('close'); }; return b; })(),"
    ." close: function() { $(this).dialog('destroy').remove(); $whenClosed }});";
};

if($_POST['reload_names_page'] OR $isNew) {
  $url = "";
  $ele_id_to_send = 0;
  if($isNew) {
    $url = XOOPS_URL . "/modules/formulize/admin/ui.php?page=element&fid=$fid&aid=".intval($_POST['aid'])."&ele_id=$ele_id";
    $ele_id_to_send = $ele_id;
  }
  $reload = "reloadWithScrollPosition('$url');";
  if($handleNotices) {
    // the page reloads once the notice is closed, rather than out from under it
    print "/* evalnow */ ele_id = $ele_id_to_send; redirect = ''; ".$handleNoticeDialog($reload);
  } else {
    print "/* evalnow */ ele_id = $ele_id_to_send; redirect = \"$reload\";";
  }
} elseif($handleNotices) {
  print "/* evalnow */ ".$handleNoticeDialog('');
}
