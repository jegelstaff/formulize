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

// This file receives ajax form submissions from the new admin UI

include_once "../../../mainfile.php";
global $xoopsUser;
if(!$xoopsUser) {
  print "not a user";
  return;
}
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
$fid = $_POST['fid'];
$groups = $xoopsUser->getGroups();
$mid = getFormulizeModId();
$gperm_handler = xoops_gethandler('groupperm');
if($fid == "new") {
  $permissionToCheck = "module_admin";
  $itemToCheck = $mid;
  $moduleToCheck = 1; // system module
  $operation = "INSERT INTO";
} else {
  $permissionToCheck = "edit_form";
  $itemToCheck = $fid;
  $moduleToCheck = $mid;
  $operation = "UPDATE";
}
if(!$gperm_handler->checkRight($permissionToCheck, $itemToCheck, $groups, $moduleToCheck)) {
  print "no permission";
  return;
}

// there is a logged in user and they have permission to edit this form...
// so read in all the submitted form values, and write them to the database
global $xoopsDB;
$sql = array();
$sqlFieldCue = array();
foreach($_POST as $k=>$value) {
  if(!strstr($k, "-")) { continue; } // ignore fields with no hyphen
  list($table, $field) = explode("-", $k);
  $table = mysql_real_escape_string($table);
  $field = mysql_real_escape_string($field);
  if(!isset($sql[$table])) {
    switch($operation) {
      case "INSERT INTO":
        $sql[$table] = $operation ." ".$xoopsDB->prefix($table) . " (";
        break;
      case "UPDATE":
        $sql[$table] = $operation ." ".$xoopsDB->prefix($table) . " SET ";
        break;
    }
    if($_POST[$table] AND $_POST[$table."_id"]) {
      $whereClause[$table] = " WHERE `".mysql_real_escape_string($_POST[$table])."` = ".mysql_real_escape_string($_POST[$table."_id"]);
    } else {
      print "no data for where clause"; // no data for where clause
      return;
    }
  }
  if(isset($sqlFieldCue[$table])) {
    $sql[$table] .= ", ";
    $sqlValues[$table] .= ", ";
  }
  switch($operation) {
    case "INSERT INTO":
      $sql[$table] .= "`$field`";
      $sqlValues[$table] .= "'".mysql_real_escape_string($value)."'";
      break;
    case "UPDATE":
      $sql[$table] .= "`$field` = '".mysql_real_escape_string($value)."'";
      break;
  }
  $sqlFieldCue[$table] = true;
}
foreach($sql as $table=>$thisSql) {
  if($operation == "INSERT INTO") {
    $thisSql .= ") VALUES (".$sqlValues[$table].")";
  } else {
    $thisSql .= $whereClause[$table];
  }
  if(!$res = $xoopsDB->query($thisSql)) {
    print "this SQL failed: ".$thisSql;
    return;
  }
}

// when inserting new forms we must also create the datatable for the form

