<?php

include_once "../../../mainfile.php";
$module_handler = xoops_gethandler('module');
global $xoopsUser;
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/common.php';
if (!$xoopsUser) {
    print "Error: you are not logged in";
    exit();
}
$gperm_handler = xoops_gethandler('groupperm');
$groups = $xoopsUser->getGroups();
$mid = getFormulizeModId();
$permissionToCheck = "module_admin";
$itemToCheck = $mid;
$moduleToCheck = 1; // system module
if (!$gperm_handler->checkRight($permissionToCheck, $itemToCheck, $groups, $moduleToCheck)) {
    print "Error: you do not have permission to save this data";
    exit();
}
$formulizeModule = $module_handler->getByDirname("formulize");
$formulizeConfig = $config_handler->getConfigsByCat(0, $mid);
if ($formulizeConfig['isSaveLocked']){
	print "Error: this system is locked";
  exit();
}
