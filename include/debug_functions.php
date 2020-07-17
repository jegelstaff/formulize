<?php
/**
 * Debugging functions
 *
 * @license GNU
 * @author marcan <marcan@smartfactory.ca>
 * @link http://impresscms.org ImpressCMS
 * @package core
 * @subpackage Debugging
 * @version	$Id: debug_functions.php 19787 2010-07-13 15:37:14Z skenow $
 * @deprecated	The static class icms_core_Debug has been created to replace these
 * @todo		Remove in version 1.4
 */

/**
 * Output a line of debug
 *
 * @param string $msg text to be outputed as a debug line
 * @param bool $exit if TRUE the script will end
 * @deprecated	use icms_core_Debug::message() instead
 */
function icms_debug($msg, $exit=false)
{
	icms_core_Debug::setDeprecated('icms_core_Debug::message');
	return icms_core_Debug::message($msg, $exit);
}

/**
 * Output a dump of a variable
 *
 * @param string $var variable which will be dumped
 * @deprecated	Use icms_core_Debug::vardump() instead
 */
function icms_debug_vardump($var)
{
	icms_core_Debug::setDeprecated('icms_core_Debug::vardump');
	return icms_core_Debug::vardump($var);
}

/**
 * Provides a backtrace for deprecated methods and functions, will be in the error section of debug
 *
 * @since ImpressCMS 1.3
 * @package core
 * @subpackage Debugging
 * @param string $replacement Method or function to be used instead of the deprecated method or function
 * @param string $extra Additional information to provide about the change
 * @deprecated	Use icms_core_Debug::setDeprecated instead
 */
function icms_deprecated( $replacement='', $extra='' ) {
	icms_core_Debug::setDeprecated('icms_core_Debug::setDeprecated');
	return icms_core_Debug::setDeprecated($replacement, $extra);
}
