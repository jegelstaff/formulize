<?php
/**
 * Check requirements of the module
 *
 * @copyright	The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
 * @package		content
 * @version		$Id: requirements.php 22685 2011-09-18 10:01:52Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

$failed_requirements = array();
if (ICMS_VERSION_BUILD < 50) $failed_requirements[] = _AM_CONTENT_REQUIREMENTS_ICMS_BUILD;

if (count($failed_requirements) > 0) {
	icms_cp_header();
	$icmsAdminTpl->assign('failed_requirements', $failed_requirements);
	$icmsAdminTpl->display(CONTENT_ROOT_PATH . 'templates/content_requirements.html');
	icms_cp_footer();
	exit;
}