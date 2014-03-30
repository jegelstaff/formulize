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

// handles all operations requested through the UI
// included in ui.php
// depends on declarations in ui.php file!!
global $xoopsDB;
include_once "formindex.php";
  $myCol = q("SELECT * FROM ". $xoopsDB->prefix("formulize"). " LIMIT 1");
    if(!isset($myCol[0]["ele_list_display"])){
      $statement="Alter table `".$xoopsDB->prefix("formulize")."` ADD `ele_list_display` text";
      $result=$xoopsDB->queryF($statement);
      $statement = "update " . $xoopsDB->prefix("formulize") . " set `ele_list_display` = `ele_display`";
      $result=$xoopsDB->queryF($sql);
    }   


ob_start();
if(isset($_GET['op'])) {
  switch($_GET['op']) {
    case "delete":
      deleteForm($_GET['fid']);
      break;
    // patch ops are only in formindex.php, must be called by going to that URL with the patch op in the URL as a param
    case "patch40":
    case "patchDB";
      patch40();
      break;
    case "patch31":
      patch31();
      break;
    case "patch22convertdata":
      patch22convertdata();
      break;
    case "patch30datastructure":
      patch30DataStructure();
      break;
   
  }
  
} else {
  patch40(); // do this which will double check if the user needs to apply a DB patch or not!!
}

$xoopsTpl->assign('opResults', ob_get_clean());


function deleteForm($fid) {

  $gperm_handler = &xoops_gethandler('groupperm');
	global $xoopsUser, $xoopsModule;
  $module_id = $xoopsModule->getVar('mid'); 
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
		if(!$gperm_handler->checkRight("delete_form", $fid, $groups, $module_id)) {
		return; 
	}

	global $xoopsDB, $myts, $eh;
        
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $form_handler->dropDataTable($fid);
        
	$sql = sprintf("DELETE FROM %s WHERE id_form = '%s'", $xoopsDB->prefix("formulize_id"), $fid);
	$xoopsDB->queryF($sql) or $eh->show("error supression 1 dans delform");

	$sql = sprintf("DELETE FROM %s WHERE id_form = '%u'", $xoopsDB->prefix("formulize"), $fid);
	$xoopsDB->queryF($sql) or $eh->show("error supression 2 dans delform");

	$sql = sprintf("DELETE FROM %s WHERE itemname = '%s'", $xoopsDB->prefix("formulize_menu"), $fid);
	$xoopsDB->queryF($sql) or $eh->show("error supression 3 dans delform");


// PERMISSION DELETION NOT OPERATING PROPERLY RIGHT NOW	
/*	$perms = getFormulizePerms();
	foreach($perms as $perm_name) {
		xoops_groupperm_deletebymoditem ($module_id,$perm_name,$id_form) ;
	}
*/
  
	xoops_notification_deletebyitem ($module_id, "form", $id_form); // added by jwe-10/10/04 to handle removing notifications for a form once it's gone

}