<?php
if( !defined("formulize_URL") ){
	define("formulize_URL", XOOPS_URL."/modules/formulize/");
}
if( !defined("formulize_ROOT_PATH") ){
	define("formulize_ROOT_PATH", XOOPS_ROOT_PATH."/modules/formulize/");
}
include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";
$formulize_mgr =& xoops_getmodulehandler('elements');
include_once formulize_ROOT_PATH.'class/elementrenderer.php';
?>