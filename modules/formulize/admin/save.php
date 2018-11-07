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

// This file receives ajax form submissions from the new admin UI

include_once "../../../mainfile.php";

$module_handler = xoops_gethandler('module');
$config_handler = xoops_gethandler('config');
$formulizeModule = $module_handler->getByDirname("formulize");
$formulizeConfig = $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
if ($formulizeConfig['isSaveLocked']){
  exit();
}

ob_end_clean();
ob_end_clean(); // in some cases ther appear to be two buffers active?!  So we must try to end twice.
global $xoopsUser;
if (!$xoopsUser) {
    print "Error: you are not logged in";
    return;
}
$gperm_handler = xoops_gethandler('groupperm');
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
$groups = $xoopsUser->getGroups();
$mid = getFormulizeModId();
$permissionToCheck = "module_admin";
$itemToCheck = $mid;
$moduleToCheck = 1; // system module
if (!$gperm_handler->checkRight($permissionToCheck, $itemToCheck, $groups, $moduleToCheck)) {
    print "Error: you do not have permission to save this data";
    return;
}

// process all the submitted form values, looking for ones that can be immediately assigned to objects
$processedValues = array();
foreach($_POST as $k=>$v) {
    if (!strstr($k, "-")) {
        // ignore fields with no hyphen
        continue;
    }
    list($class, $property) = explode("-", $k);
    $v = recursive_stripslashes($v);
    if (is_array($v) AND $class != "elements") {
        // elements class is written using cleanVars so arrays are serialized automagically
        $v = serialize($v);
    }
    $processedValues[$class][$property] = $v;
}

$popupSave = isset($_GET['popupsave']) ? "_popup" : "";

// include the form-specific handler to invoke the necessary objects and insert them all in the DB
if (file_exists(XOOPS_ROOT_PATH."/modules/formulize/admin/save/".str_replace(array("\\","/"),"", $_POST['formulize_admin_handler'])."_save".$popupSave.".php")) {
    include XOOPS_ROOT_PATH."/modules/formulize/admin/save/".str_replace(array("\\","/"),"", $_POST['formulize_admin_handler'])."_save".$popupSave.".php";
}

