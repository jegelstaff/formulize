<?php
/**
* Check requirements of the module
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: requirements.php 22692 2011-09-18 10:32:55Z phoenyx $
*/
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

$failed_requirements = array();

// check for ImpressCMS build number
$required_icms_build = 50;
if (ICMS_VERSION_BUILD < $required_icms_build)
	$failed_requirements[] = sprintf(_AM_BANNERS_REQUIREMENTS_ICMS_BUILD, $required_icms_build, ICMS_VERSION_BUILD);

// checking for the Smarty Plugin
$library_file = ICMS_LIBRARIES_PATH.'/smarty/icms_plugins/function.banners.php';
$banners_file = BANNERS_ROOT_PATH.'plugins/function.banners.php';
if (!is_file($library_file))
	$failed_requirements[] = sprintf(_AM_BANNERS_REQUIREMENTS_SMARTY_PLUGIN, $banners_file, $library_file);

if (count($failed_requirements) > 0) {
	icms_cp_header();
	$icmsAdminTpl->assign('failed_requirements', $failed_requirements);
	$icmsAdminTpl->display(BANNERS_ROOT_PATH.'templates/banners_requirements.html');
	icms_cp_footer();
	exit;
}