<?php
/**
* Common file of the module included on all pages of the module
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
* @package		content
* @version		$Id$
*/

if (!defined("ICMS_ROOT_PATH")) die("ICMS root path not defined");

if(!defined("CONTENT_DIRNAME"))		define("CONTENT_DIRNAME", $modversion['dirname'] = basename(dirname(dirname(__FILE__))));
if(!defined("CONTENT_URL"))			define("CONTENT_URL", ICMS_URL.'/modules/'.CONTENT_DIRNAME.'/');
if(!defined("CONTENT_ROOT_PATH"))	define("CONTENT_ROOT_PATH", ICMS_ROOT_PATH.'/modules/'.CONTENT_DIRNAME.'/');
if(!defined("CONTENT_IMAGES_URL"))	define("CONTENT_IMAGES_URL", CONTENT_URL.'images/');
if(!defined("CONTENT_ADMIN_URL"))	define("CONTENT_ADMIN_URL", CONTENT_URL.'admin/');

// Include the common language file of the module
icms_loadLanguageFile('content', 'common');

include_once(CONTENT_ROOT_PATH . "include/functions.php");

// Creating the module object to make it available throughout the module
$contentModule = icms_getModuleInfo(CONTENT_DIRNAME);
if (is_object($contentModule)){
	$content_moduleName = $contentModule->getVar('name');
}

// Find if the user is admin of the module and make this info available throughout the module
$content_isAdmin = icms_userIsAdmin(CONTENT_DIRNAME);

// Creating the module config array to make it available throughout the module
$contentConfig = icms_getModuleConfig(CONTENT_DIRNAME);

// including the content class
include_once(CONTENT_ROOT_PATH . 'class/content.php');

// creating the icmsPersistableRegistry to make it available throughout the module
global $icmsPersistableRegistry;
$icmsPersistableRegistry = IcmsPersistableRegistry::getInstance();

?>