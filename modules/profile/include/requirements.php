<?php
/**
* Check requirements of the module
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		2.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		profile
* @version		$Id:$
*/
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

$failed_requirements = array();

// check for ImpressCMS build number
$required_icms_build = 50;
if (ICMS_VERSION_BUILD < $required_icms_build)
	$failed_requirements[] = sprintf(_AM_PROFILE_REQUIREMENTS_ICMS_BUILD, $required_icms_build, ICMS_VERSION_BUILD);

if (count($failed_requirements) > 0) {
	icms_cp_header();
	$icmsAdminTpl->assign('failed_requirements', $failed_requirements);
	$icmsAdminTpl->display(PROFILE_ROOT_PATH . 'templates/profile_requirements.html');
	icms_cp_footer();
	exit;
}