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

// this file handles saving of submissions from the form_permissions page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

// CHECK IF THE FORM IS LOCKED DOWN AND SCOOT IF SO
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($_POST['formulize_admin_key']);
$form_id = $formObject->getVar('id_form');
if($formObject->getVar('lockedform')) {
  return;
}

// If the user doesn't have edit form permission, then do nothing
$formulize_module_id = getFormulizeModId();
if(!$gperm_handler->checkRight("edit_form", $form_id, $xoopsUser->getGroups(), $formulize_module_id)) {
  return;
}

global $xoopsDB;

// check to see if we're dealing with a grouplist save or deletion
if($_POST['grouplistname']) {

  $groupListId = intval($_POST['grouplistid']);
  $groupListGroups = formulize_db_escape(implode(",",$_POST['groups']));
  $name = formulize_db_escape($_POST['grouplistname']);
  // are we inserting or updating?
  $newList = $groupListId == 0 ? true : false;
  if(!$newList) {
	  // Get exisitng name to see if we update, or create new.
	  $result = $xoopsDB->query("SELECT gl_name FROM ".$xoopsDB->prefix("group_lists")." WHERE gl_id='".intval($groupListId)."'");
	  if($xoopsDB->getRowsNum($result) > 0) {
      $entry = $xoopsDB->fetchArray($result);
      if($entry['gl_name'] != $name) {
				$newList = true;
			}
	  }
	}
  if($newList) {
    $grouplist_query = "INSERT INTO ". $xoopsDB->prefix("group_lists") . " (gl_name, gl_groups) VALUES ('" . $name . "', '" . $groupListGroups . "')";
    $groupListId = $xoopsDB->getInsertId();
  } else {
    $grouplist_query = "UPDATE ". $xoopsDB->prefix("group_lists") . " SET gl_groups = '" . $groupListGroups . "', gl_name = '".$name."' WHERE gl_id='" . $groupListId . "'";
  }
  if(!$grouplist_result = $xoopsDB->query($grouplist_query)) {
    print "Error: could not add a group list ".$xoopsDB->error();
  }
}

if ($_POST['removelistid']) {
    if ($removelistid = intval($_POST['removelistid'])) {
        if (!$delete_result = $xoopsDB->query("DELETE FROM ".$xoopsDB->prefix("group_lists") . " WHERE gl_id='" . $removelistid . "'")) {
            print "Error: could not delete group list ".$xoopsDB->error();
        }
    }
    $_SESSION['formulize_selectedGroupList'] = 0;
}

include_once XOOPS_ROOT_PATH . "/modules/formulize/class/usersGroupsPerms.php";
$formulize_permHandler = new formulizePermHandler($form_id);
$groupsToClear = array();
$filterSettings = array();
$group_list = (isset($_POST['group_list']) and is_array($_POST['group_list'])) ? $_POST['group_list'] : array();
foreach($group_list as $group_id) {
  if(!is_numeric($group_id)) {
    continue;
  }

    // delete existing permission records for this group to start with a blank slate
    if (!$xoopsDB->query("DELETE FROM ".$xoopsDB->prefix("group_permission") . " WHERE gperm_groupid='$group_id' AND gperm_itemid='$form_id' AND gperm_modid='$formulize_module_id'")) {
        print "Error: could not delete the permissions for group $group_id";
    }

    // collect the list of enabled permissions submitted through the form
    $enabled_permissions = array();
    foreach(formulizePermHandler::getPermissionList() as $permission_name) {
        if ($_POST[$form_id."_".$group_id."_".$permission_name]) {
            $enabled_permissions[] = "($group_id, $form_id, $formulize_module_id, '$permission_name')";
  }
  }

    // enable only the selected permissions
    if (count((array) $enabled_permissions) > 0) {
        $insertSQL = "INSERT INTO ".$xoopsDB->prefix("group_permission") . " (`gperm_groupid`, `gperm_itemid`, `gperm_modid`, `gperm_name`) VALUES ".
            implode(", ", $enabled_permissions);
    if(!$xoopsDB->query($insertSQL)) {
      print "Error: could not set the permissions for group $group_id";
    }
  }

  // deal with specific groupscope settings
  if(!$formulize_permHandler->setGroupScopeGroups($group_id, $_POST["groupsscope_choice_".$form_id."_".$group_id])) {
	  print "Error: could not set the groupscope groups for form $form_id.";
  }

  // handle the per-group-filter-settings
  $filter_key = $form_id."_".$group_id."_filter";
	list($filterSettings[$group_id], $reloadFlag) = parseSubmittedConditions($filter_key, 'conditionsdelete', deleteTargetKey: 3, conditionsDeletePartsKeyOneMustMatch: $group_id); // reloadFlag is not used on this page, for some reason (reload is set/flagged through other means?)
	if(!isset($filterSettings[$group_id][0]) OR !is_array($filterSettings[$group_id][0]) OR count($filterSettings[$group_id][0]) == 0) {
    $groupsToClear[] = $group_id;
	}

}

// now update the per group filters
if(count((array) $groupsToClear)>0) {
  $form_handler->clearPerGroupFilters($groupsToClear, $form_id);
}
if(count((array) $filterSettings)>0) {
  $form_handler->setPerGroupFilters($filterSettings, $form_id);
}

if($_POST['reload'] OR $_POST['loadthislist']) {
  print "/* eval */ window.document.getElementById('form-".intval($_POST['form_number'])."').submit();";
}
