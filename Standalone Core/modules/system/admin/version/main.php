<?php
/**
* ImpressCMS Version Checker
*
* This page checks if the ImpressCMS install runs the latest released version
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		Administration
* @since		1.0
* @author		malanciault <marcan@impresscms.org)
* @version		$Id: main.php 8917 2009-06-23 20:59:36Z skenow $
*/

if ( !is_object($icmsUser) || !is_object($icmsModule) || !$icmsUser->isAdmin() ) {
	exit("Access Denied");
}

/*
 * If an mid is defined in the GET params then this file is called by clicking on a module Info button in
 * System Admin > Modules, so we need to display the module information pop up
 * 
 * @todo this has nothing to do in the version checker system module, but it is there as a 
 * reminiscence of XOOPS. It needs tp be moved elsewhere in 1.1
 */
if (isset($_GET['mid'])) {
	include_once XOOPS_ROOT_PATH . '/modules/system/admin/version/module_info.php';
	exit;
}

/**
 * Now here is the version checker :-)
 */
global $icmsAdminTpl, $xoTheme;
require_once XOOPS_ROOT_PATH.'/class/icmsversionchecker.php';
$icmsVersionChecker = IcmsVersionChecker::getInstance();
icms_cp_header();
if ($icmsVersionChecker->check()) {
	$icmsAdminTpl->assign('update_available', true);
	$icmsAdminTpl->assign('latest_changelog', $icmsVersionChecker->latest_changelog);
    $icmsAdminTpl->assign('latest_version', $icmsVersionChecker->latest_version_name); 
    $icmsAdminTpl->assign('latest_url', $icmsVersionChecker->latest_url);   
    if (ICMS_VERSION_STATUS == 10 && $icmsVersionChecker->latest_status < 10) {
		// I'm runing a final release so make sure to notify the user that the update is not a final
		$icmsAdminTpl->assign('not_a_final_comment', true);
	}
}
else {
	$checkerErrors = $icmsVersionChecker->getErrors(true);
	if ($checkerErrors) {
		$icmsAdminTpl->assign('errors', $checkerErrors);
	}
}

$icmsAdminTpl->assign('your_version', $icmsVersionChecker->installed_version_name);
$icmsAdminTpl->assign('lang_php_vesion', PHP_VERSION);
$icmsAdminTpl->assign('lang_mysql_version', mysql_get_server_info());
$icmsAdminTpl->assign('lang_server_api', PHP_SAPI);
$icmsAdminTpl->assign('lang_os_name', PHP_OS);
$icmsAdminTpl->assign('safe_mode', ini_get( 'safe_mode' ) ? _CO_ICMS_ON : _CO_ICMS_OFF);
$icmsAdminTpl->assign('register_globals', ini_get( 'register_globals' ) ? _CO_ICMS_ON : _CO_ICMS_OFF);
$icmsAdminTpl->assign('magic_quotes_gpc', ini_get( 'magic_quotes_gpc' ) ? _CO_ICMS_ON : _CO_ICMS_OFF);
$icmsAdminTpl->assign('allow_url_fopen', ini_get( 'allow_url_fopen' ) ? _CO_ICMS_ON : _CO_ICMS_OFF);
$icmsAdminTpl->assign('fsockopen', function_exists( 'fsockopen' ) ? _CO_ICMS_ON : _CO_ICMS_OFF);
$icmsAdminTpl->assign('allow_call_time_pass_reference', ini_get( 'allow_call_time_pass_reference' ) ? _CO_ICMS_ON : _CO_ICMS_OFF);
$icmsAdminTpl->assign('post_max_size', icms_conv_nr2local(ini_get( 'post_max_size' )));
$icmsAdminTpl->assign('max_input_time', icms_conv_nr2local(ini_get( 'max_input_time' )));
$icmsAdminTpl->assign('output_buffering', icms_conv_nr2local(ini_get( 'output_buffering' )));
$icmsAdminTpl->assign('max_execution_time', icms_conv_nr2local(ini_get( 'max_execution_time' )));
$icmsAdminTpl->assign('memory_limit', icms_conv_nr2local(ini_get( 'memory_limit' )));
$icmsAdminTpl->assign('file_uploads', ini_get( 'file_uploads' ) ? _CO_ICMS_ON : _CO_ICMS_OFF);
$icmsAdminTpl->assign('upload_max_filesize', icms_conv_nr2local(ini_get( 'upload_max_filesize' )));

$icmsAdminTpl->display(XOOPS_ROOT_PATH.'/modules/system/templates/admin/system_adm_version.html');
icms_cp_footer();

?>