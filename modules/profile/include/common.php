<?php
/**
* Common file of the module included on all pages of the module
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
* @package		improfile
* @version		$Id: common.php 20562 2010-12-19 18:26:36Z phoenyx $
*/

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

if (!defined("PROFILE_DIRNAME"))	define("PROFILE_DIRNAME", $modversion['dirname'] = basename(dirname(dirname(__FILE__))));
if (!defined("PROFILE_URL"))		define("PROFILE_URL", ICMS_URL.'/modules/'.PROFILE_DIRNAME.'/');
if (!defined("PROFILE_ROOT_PATH"))	define("PROFILE_ROOT_PATH", ICMS_ROOT_PATH.'/modules/'.PROFILE_DIRNAME.'/');
if (!defined("PROFILE_IMAGES_URL"))	define("PROFILE_IMAGES_URL", PROFILE_URL.'images/');
if (!defined("PROFILE_ADMIN_URL"))	define("PROFILE_ADMIN_URL", PROFILE_URL.'admin/');

// Include the common language file of the module
icms_loadLanguageFile(basename(dirname(dirname(__FILE__))), 'common');

// Find if the user is admin of the module and make this info available throughout the module
$profile_isAdmin = icms_userIsAdmin(PROFILE_DIRNAME);
?>