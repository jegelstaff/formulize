<?php
/**
* Common file of the module included on all pages of the module
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: common.php 20170 2010-09-19 14:01:59Z phoenyx $
*/
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

if (!defined("BANNERS_DIRNAME"))	define("BANNERS_DIRNAME", $modversion['dirname'] = basename(dirname(dirname(__FILE__))));
if (!defined("BANNERS_URL"))		define("BANNERS_URL", ICMS_URL.'/modules/'.BANNERS_DIRNAME.'/');
if (!defined("BANNERS_ROOT_PATH"))	define("BANNERS_ROOT_PATH", ICMS_ROOT_PATH.'/modules/'.BANNERS_DIRNAME.'/');
if (!defined("BANNERS_IMAGES_URL"))	define("BANNERS_IMAGES_URL", BANNERS_URL.'images/');
if (!defined("BANNERS_ADMIN_URL"))	define("BANNERS_ADMIN_URL", BANNERS_URL.'admin/');

// Include the common language file of the module
icms_loadLanguageFile('banners', 'common');