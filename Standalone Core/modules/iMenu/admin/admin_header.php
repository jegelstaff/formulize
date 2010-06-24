<?php
include("../../../mainfile.php");
require_once(XOOPS_ROOT_PATH."/kernel/module.php");
include_once(XOOPS_ROOT_PATH."/include/cp_functions.php");
if ( $xoopsUser ) {
	$xoopsModule = XoopsModule::getByDirname("iMenu");
	if ( !$xoopsUser->isAdmin($xoopsModule->mid()) ) { 
		redirect_header(XOOPS_URL."/",3,_NOPERM);
		exit();
	}
} else {
	redirect_header(XOOPS_URL."/",3,_NOPERM);
	exit();
}
if ( file_exists("../language/".$xoopsConfig['language']."/admin.php") ) {
	include("../language/".$xoopsConfig['language']."/admin.php");
} else {
	include("../language/english/admin.php");
}
?>
