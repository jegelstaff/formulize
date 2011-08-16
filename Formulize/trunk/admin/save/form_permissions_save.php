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

// this file handles saving of submissions from the form_permissions page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

// CHECK IF THE FORM IS LOCKED DOWN AND SCOOT IF SO
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($_POST['formulize_admin_key']);
$fid = $formObject->getVar('id_form');
if($formObject->getVar('lockedform')) {
  return;
}

// If the user doesn't have edit form permission, then do nothing
if(!$gperm_handler->checkRight("edit_form", $fid, $xoopsUser->getGroups(), getFormulizeModId())) {
  return;
}

global $xoopsDB;

// check to see if we're dealing with a grouplist save or deletion
if($_POST['grouplistname']) {
 
  $groupListId = intval($_POST['grouplistid']);
  $groupListGroups = mysql_real_escape_string(implode(",",$_POST['groups']));
  $name = mysql_real_escape_string($_POST['grouplistname']);
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
    print "Error: could not add a group list ".mysql_error(); 
  }
}

if($_POST['removelistid']) {
  $id = intval($_POST['removelistid']);
	if($id) {
    $delete_query = "DELETE FROM ".$xoopsDB->prefix("group_lists") . " WHERE gl_id='" . $id . "'";
    if(!$delete_result = $xoopsDB->query($delete_query)) {
      print "Error: could not delete group list ".mysql_error();
    }
  }
  $_SESSION['formulize_selectedGroupList'] = 0;
}


include_once XOOPS_ROOT_PATH . "/modules/formulize/class/usersGroupsPerms.php";
$formulize_permHandler = new formulizePermHandler($fid);
$groupsToClear = array();
$filterSettings = array();
foreach($_POST['group_list'] as $gid) {
  if(!is_numeric($gid)) {
    continue;
  }
  // deal with regular permissions
  // in order to limit this to two operations per group, we wholesale delete, and then insert, so it's easier to construct a single insert query to cover all the perms
  $mid = getFormulizeModId();
  $deleteSQL = "DELETE FROM ".$xoopsDB->prefix("group_permission") . " WHERE gperm_groupid='$gid' AND gperm_itemid='$fid' AND gperm_modid='$mid'";
  $insertSQL = "INSERT INTO ".$xoopsDB->prefix("group_permission") . " (`gperm_groupid`, `gperm_itemid`, `gperm_modid`, `gperm_name`) VALUES ";
  $permsToAdd = array();
  foreach(array("view_form", "add_own_entry", "update_own_entry", "delete_own_entry", "update_other_entries", "delete_other_entries", "add_proxy_entries", "view_groupscope", "view_globalscope", "view_private_elements", "update_other_reports", "delete_other_reports", "publish_reports", "publish_globalscope", "set_notifications_for_others", "import_data", "edit_form", "delete_form", "update_entry_ownership") as $thisPerm) {
    if($_POST[$fid."_".$gid."_".$thisPerm]) {
      $permsToAdd[] = "($gid, $fid, $mid, '$thisPerm')";
    }
  }
  if(!$xoopsDB->query($deleteSQL)) {
    print "Error: could not delete the permissions for group $gid";
  }
  if(count($permsToAdd)>0) {
    $insertSQL .= implode(", ", $permsToAdd);
    if(!$xoopsDB->query($insertSQL)) {
      print "Error: could not set the permissions for group $gid";
    }
  }
  // deal with specific groupscope settings
  if(!$formulize_permHandler->setGroupScopeGroups($gid, $_POST["groupsscope_choice_".$fid."_".$gid])) {
	  print "Error: could not set the groupscope groups for form $fid.";
  }
  // handle the per-group-filter-settings
  $filter_key = $fid."_".$gid."_filter";
  
  if($_POST["new_".$filter_key."_term"] != "") {
    $_POST[$filter_key."_elements"][] = $_POST["new_".$filter_key."_element"];
    $_POST[$filter_key."_ops"][] = $_POST["new_".$filter_key."_op"];
    $_POST[$filter_key."_terms"][] = $_POST["new_".$filter_key."_term"];
    $_POST[$filter_key."_types"][] = "all";
  }
  if($_POST["new_".$filter_key."_oom_term"] != "") {
    $_POST[$filter_key."_elements"][] = $_POST["new_".$filter_key."_oom_element"];
    $_POST[$filter_key."_ops"][] = $_POST["new_".$filter_key."_oom_op"];
    $_POST[$filter_key."_terms"][] = $_POST["new_".$filter_key."_oom_term"];
    $_POST[$filter_key."_types"][] = "oom";
  }
  $conditionsDeleteParts = explode("_", $_POST['conditionsdelete']);
  if($_POST['conditionsdelete'] != "" AND $conditionsDeleteParts[1] == $gid) { // key 1 will be the group id where the X was clicked
    // go through the passed filter settings starting from the one we need to remove, and shunt the rest down one space
    // need to do this in a loop, because unsetting and key-sorting will maintain the key associations of the remaining high values above the one that was deleted
    $originalCount = count($_POST[$filter_key."_elements"]);
    for($i=$conditionsDeleteParts[3];$i<$originalCount;$i++) { // 3 is the X that was clicked for this group
      if($i>$conditionsDeleteParts[3]) {
        $_POST[$filter_key."_elements"][$i-1] = $_POST[$filter_key."_elements"][$i];
        $_POST[$filter_key."_ops"][$i-1] = $_POST[$filter_key."_ops"][$i];
        $_POST[$filter_key."_terms"][$i-1] = $_POST[$filter_key."_terms"][$i];
        $_POST[$filter_key."_types"][$i-1] = $_POST[$filter_key."_types"][$i];
      }
      if($i==$conditionsDeleteParts[3] OR $i+1 == $originalCount) {
        // first time through or last time through, unset the first elements
        unset($_POST[$filter_key."_elements"][$i]);
        unset($_POST[$filter_key."_ops"][$i]);
        unset($_POST[$filter_key."_terms"][$i]);
        unset($_POST[$filter_key."_types"][$i]);
      }
    }
  }
  if(!is_array($_POST[$filter_key."_elements"]) OR count($_POST[$filter_key."_elements"]) == 0) {
    $groupsToClear[] = $gid;
  } else {
    $filterSettings[$gid][0] = $_POST[$filter_key."_elements"];
    $filterSettings[$gid][1] = $_POST[$filter_key."_ops"];
    $filterSettings[$gid][2] = $_POST[$filter_key."_terms"];
    $filterSettings[$gid][3] = $_POST[$filter_key."_types"];
  }
}

// now update the per group filters
if(count($groupsToClear)>0) {
  $form_handler->clearPerGroupFilters($groupsToClear, $fid);
}
if(count($filterSettings)>0) {
  $form_handler->setPerGroupFilters($filterSettings, $fid);
}

if($_POST['reload'] OR $_POST['loadthislist']) {
  print "/* eval */ window.document.getElementById('form-".intval($_POST['form_number'])."').submit();";
} 