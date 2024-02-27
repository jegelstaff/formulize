<?php
/**
 * Installer final page
 *
 * See the enclosed file license.txt for licensing information.
 * If you did not receive this file, get it at http://www.fsf.org/copyleft/gpl.html
 *
 * @copyright	The XOOPS project http://www.xoops.org/
 * @license	  http://www.fsf.org/copyleft/gpl.html GNU General Public License (GPL)
 * @package		installer
 * @since		Xoops 2.3.0
 * @author		Haruki Setoyama  <haruki@planewave.org>
 * @author 		Kazumi Ono <webmaster@myweb.ne.jp>
 * @author		Skalpa Keo <skalpa@xoops.org>
 * @version		$Id: page_end.php 20098 2010-09-07 16:19:19Z skenow $
 */
/**
 *
 */
require_once 'common.inc.php';
if (!defined( 'XOOPS_INSTALL' ) )	exit();

$success = isset($_GET['success']) ? trim($_GET['success']) : false;
if ($success) {
	if (is_dir(ICMS_ROOT_PATH.'/install')) {
		header('Location: '.ICMS_URL.'/index.php');
	}
	$_SESSION = array();
}

$wizard->setPage( 'end' );
$pageHasForm = false;
$content = "";
include "./language/$wizard->language/finish.php";

// destroy all the installation session
unset($_SESSION);
if(isset($_COOKIE[session_name()]))
{
	setcookie(session_name(), '', time()-60);
}
session_unset();
session_destroy();

// MODIFIED BY FREEFORM SOLUTIONS TO ADD IN THE CUSTOM CONFIGURATION FOR THE FORMULIZE STANDALONE VERSION
set_time_limit(0);
include_once './class/dbmanager.php';
$dbm = new db_manager();
$dbm->db->connect();
$formulizeStandaloneQueries = "";
if (file_exists(ICMS_ROOT_PATH."/install/sql/pdo.mysql.formulize_standalone.sql")) {
    $formulizeStandaloneQueries = str_replace("REPLACE_WITH_PREFIX", SDATA_DB_PREFIX, file_get_contents(ICMS_ROOT_PATH."/install/sql/pdo.mysql.formulize_standalone.sql"));
}

// Check what the module ids are, and replace in the file
include_once ICMS_ROOT_PATH."/mainfile.php";
include_once ICMS_ROOT_PATH."/include/common.php";
$module_handler = icms::handler('icms_module');
$formulizeModule = $module_handler->getByDirname('formulize');
$formulizeModuleId = $formulizeModule->getVar('mid');
$contentModule = $module_handler->getByDirname('content');
$contentModuleId = $contentModule->getVar('mid');
$profileModule = $module_handler->getByDirname('profile');
$profileModuleId = $profileModule->getVar('mid');
$protectorModule = $module_handler->getByDirname('protector');
$protectorModuleId = $profileModule->getVar('mid');
$timezone = new DateTimeZone(date_default_timezone_get());
$testDate = new DateTime("December 31 1969", $timezone);
$offset = $timezone->getOffset($testDate)/60/60;
$year = date("Y");

$formulizeStandaloneQueries = str_replace("REPLACE_WITH_PROFILE_MODULE_ID", $profileModuleId, $formulizeStandaloneQueries);
$formulizeStandaloneQueries = str_replace("REPLACE_WITH_CONTENT_MODULE_ID", $contentModuleId, $formulizeStandaloneQueries);
$formulizeStandaloneQueries = str_replace("REPLACE_WITH_FORMULIZE_MODULE_ID", $formulizeModuleId, $formulizeStandaloneQueries);
$formulizeStandaloneQueries = str_replace("REPLACE_WITH_PROTECTOR_MODULE_ID", $protectorModuleId, $formulizeStandaloneQueries);
$formulizeStandaloneQueries = str_replace("REPLACE_WITH_TIMEZONE", $offset, $formulizeStandaloneQueries);
$formulizeStandaloneQueries = str_replace("REPLACE_WITH_YEAR", $year, $formulizeStandaloneQueries);

foreach(explode(";\r",str_replace(array("\n","\n\r","\r\n"), "\r", $formulizeStandaloneQueries)) as $sql) { // convert all kinds of line breaks to \r and then split on semicolon-linebreak to get individual queries
	if($sql) {
		if(!$formulizeResult = $dbm->query($sql)) {
			$content = "<h3>Error:</h3><p>Some of the configuration settings were not saved properly in the database.  The website will still work, but it will behave more like a generic ImpressCMS+Formulize website, and not like a dedicated Formulize system.   Please send the following information to <a href=\"mailto:formulize@freeformsolutions.ca?subject=Formulize%20Standalone%20Install%20Error\">formulize@freeformsolutions.ca</a>:</p>
			<p><pre>".$dbm->db->error()."</pre></p>".$content;
		}
	}
}

// write a lock file so the install folder is inaccessible (if not deleted automatically)
icms_core_Filesystem::writeFile('', 'install', 'lock', ICMS_ROOT_PATH);

// END OF MODIFIED CODE
include 'install_tpl.php';
