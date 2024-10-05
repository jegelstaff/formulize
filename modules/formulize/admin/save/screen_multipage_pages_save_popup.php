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

// this file handles saving of submissions from the screen_multipage_pages page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

//print_r($_POST);
//print_r($processedValues);

$sid = $_POST['formulize_admin_key'];

$screens = $processedValues['screens'];

$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
$screen = $screen_handler->get($sid);
// CHECK IF THE FORM IS LOCKED DOWN AND SCOOT IF SO
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($screen->getVar('fid'));
if($formObject->getVar('lockedform')) {
  return;
}
// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $screen->getVar('fid'), $groups, $mid)) {
  return;
}
$pages = $screen->getVar('pages');
$pagetitles = $screen->getVar('pagetitles');
$conditions = $screen->getVar('conditions');
$conditionsStateChanged = false;
foreach($screens as $k=>$v) {
	if(substr($k, 0, 10) == "pagetitle_") {
		$page_number = substr($k, 10);
		$pagetitles[$page_number] = $v;

		// grab any conditions for this page too
		$filter_key = 'pagefilter_'.$page_number;
		list($conditions[$page_number], $pageConditionsStateChanged) = parseSubmittedConditions($filter_key, 'conditionsdelete', deleteTargetKey: 2, conditionsDeletePartsKeyOneMustMatch: $page_number);
		$conditionsStateChanged = $pageConditionsStateChanged ? $pageConditionsStateChanged : $conditionsStateChanged; // if conditions changed on this page, then we need to set the overall flag. But don't set the overall flag to false just because nothing changed, since something might have changed on another page!

  } elseif(substr($k, 0, 4) == "page") { // page must come last since those letters are common to the beginning of everything
		$pages[substr($k, 4)] = unserialize($v); // arrays will have been serialized when they were put into processedValues
	}
}

$screen->setVar('pages',serialize($pages));
$screen->setVar('pagetitles',serialize($pagetitles));
$screen->setVar('conditions',serialize($conditions));

// need to strip out HTML chars from these textboxes so that the insert method behaves correctly (it assumes that there are no HTML chars, because that's how it would be if there were a real save coming from the save box on the text tab)
$screen->setVar('introtext', undoAllHTMLChars($screen->getVar('introtext', "e")));
$screen->setVar('thankstext', undoAllHTMLChars($screen->getVar('thankstext', "e")));

if(!$screen_handler->insert($screen)) {
  print "Error: could not save the screen properly: ".$xoopsDB->error();
}

// reload the page if the state has changed
if($conditionsStateChanged) {
    print "/* eval */ reloadPopup();";
}
?>
