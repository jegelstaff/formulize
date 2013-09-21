<?php
// $Id: xoopsuser.php 10337 2010-07-13 15:37:14Z skenow $
// this file is for backward compatibility only
if (!defined('ICMS_ROOT_PATH')) {
	exit();
}
icms_core_Debug::setDeprecated( '','class/xoopsuser.php file will be removed in ImpressCMS 1.4 - use kernel/user.php' );
/**
 * Include the user class
 * @deprecated use kernel/user.php instead
 * @todo Remove this file in 1.4
 */
require_once ICMS_ROOT_PATH.'/kernel/user.php';
?>