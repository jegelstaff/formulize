<?php
// $Id: xoopsobject.php 19787 2010-07-13 15:37:14Z skenow $
if (!defined('ICMS_ROOT_PATH')) {
	exit();
}
/**
 * this file is for backward compatibility only
 * @package kernel
 * @deprecated use kernel/object.php instead
 * @todo remove this file in 1.4
 **/
icms_core_Debug::setDeprecated( '', 'class/xoopsobject.php will be removed in ImpressCMS 1.4 - use kernel/object.php');
/**
 * Load the new object class
 **/
require_once ICMS_ROOT_PATH.'/kernel/object.php';
?>