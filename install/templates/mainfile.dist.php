<?php
/**
* All information in order to connect to database are going through here.
*
* Be careful if you are changing data's in this file.
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		installer
* @since		XOOPS
* @author		http://www.xoops.org The XOOPS Project
* @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id: mainfile.dist.php 22529 2011-09-02 19:55:40Z phoenyx $
*/

if (!defined("XOOPS_MAINFILE_INCLUDED")) {
	define("XOOPS_MAINFILE_INCLUDED",1);

	// ADDED BY FREEFORM SOLUTIONS
	// this setting determines which errors will be recorded in the error log
	// use one of the error log constants to log errors of that, or higher severity: http://www.php.net/manual/en/errorfunc.constants.php
	// ie: setting E_WARNING here will log E_WARNING and E_ERROR only
	// setting this to 0 will cause no errors to be logged
	// This constant also causes errors to still be logged (but not displayed) when the Developer Dashboard setting is off.
	// Comment out this constant if you don't want any of this behaviour
	define("ICMS_ERROR_LOG_SEVERITY", E_WARNING);
	
	// ADDED BY FREEFORM SOLUTIONS
	// Set the default timezone to UTC if there is no other timezone specifically set already
	if ("UTC" == @date_default_timezone_get()) {
		date_default_timezone_set("UTC");
	}
		
	// XOOPS Physical Path
	// Physical path to your main XOOPS directory WITHOUT trailing slash
	// Example: define('XOOPS_ROOT_PATH', '/path/to/xoops/directory');
	// ALTERED BY FREEFORM SOLUTIONS...
	// AS DEFINED IN INSTALLER BY USER:
	// define('XOOPS_ROOT_PATH', '');
	// AS DETERMINED FROM FIRST PRINCIPLES:
	define('XOOPS_ROOT_PATH', realpath(dirname(__FILE__)));

	// XOOPS Security Physical Path
	// Physical path to your security XOOPS directory WITHOUT trailing slash.
	// Ideally off your server WEB folder
	// Example: define('XOOPS_TRUST_PATH', '/path/to/trust/directory');
	define('XOOPS_TRUST_PATH', '');

	// sdata#--#

	// XOOPS Virtual Path (URL)
	// Virtual path to your main XOOPS directory WITHOUT trailing slash
	// Example: define('XOOPS_URL', 'http://url_to_xoops_directory');
	// ALTERED BY FREEFORM SOLUTIONS...
	// AS DEFINED IN INSTALLER BY USER:
	// define('XOOPS_URL', 'http://');
	// AS DETERMINED FROM FIRST PRINCIPLES:
	if (!defined("SITE_BASE_URL")) {
        # if this code is in a subfolder of the website, figure out what the subfolder url is
        $doc_root_len = strlen($_SERVER["DOCUMENT_ROOT"]);
        if (strlen(XOOPS_ROOT_PATH) > $doc_root_len) {
		$base_url = str_replace('\\', '/', substr(XOOPS_ROOT_PATH, $doc_root_len));
		define("SITE_BASE_URL", $base_url);
        }
        else
		define("SITE_BASE_URL", "");
	}

	$PortNum = (80 == $_SERVER["SERVER_PORT"]) ? "" : ":" . $_SERVER["SERVER_PORT"];
	define('XOOPS_URL', (443 == $_SERVER["SERVER_PORT"] ? "https://" : "http://") . $_SERVER['SERVER_NAME'] . $PortNum . SITE_BASE_URL );

	define('XOOPS_CHECK_PATH', 0);
	// Protect against external scripts execution if safe mode is not enabled
	if (XOOPS_CHECK_PATH && !@ini_get('safe_mode')) {
		if (function_exists('debug_backtrace')) {
			$xoopsScriptPath = debug_backtrace();
			if (!count($xoopsScriptPath)) {
			 	die("ImpressCMS path check: this file cannot be requested directly");
			}
			$xoopsScriptPath = $xoopsScriptPath[0]['file'];
		} else {
			$xoopsScriptPath = isset($_SERVER['PATH_TRANSLATED']) ? $_SERVER['PATH_TRANSLATED'] :  $_SERVER['SCRIPT_FILENAME'];
		}
		if (DIRECTORY_SEPARATOR != '/') {
			// IIS6 may double the \ chars
			$xoopsScriptPath = str_replace( strpos( $xoopsScriptPath, '\\\\', 2 ) ? '\\\\' : DIRECTORY_SEPARATOR, '/', $xoopsScriptPath);
		}
		if (strcasecmp( substr($xoopsScriptPath, 0, strlen(XOOPS_ROOT_PATH)), str_replace( DIRECTORY_SEPARATOR, '/', XOOPS_ROOT_PATH))) {
		 	exit("ImpressCMS path check: Script is not inside XOOPS_ROOT_PATH and cannot run.");
		}
	}

	// Database
	// Choose the database to be used
	define('XOOPS_DB_TYPE', 'mysql');
 
    // Set the database charset if applicable
    if (defined('XOOPS_DB_CHARSET')) die();
    define('XOOPS_DB_CHARSET', '');

	// Table Prefix
	// This prefix will be added to all new tables created to avoid name conflict in the database. If you are unsure, just use the default 'icms'.
	define('XOOPS_DB_PREFIX', 'icms');

	// Database Hostname
	// Hostname of the database server. If you are unsure, 'localhost' works in most cases.
	define('XOOPS_DB_HOST', 'localhost');

	// Database Username
	// Your database user account on the host
	define('XOOPS_DB_USER', '');

	// Database Password
	// Password for your database user account
	define('XOOPS_DB_PASS', '');

	// Database Name
	// The name of database on the host. The installer will attempt to create the database if not exist
	define('XOOPS_DB_NAME', '');

	// Password Salt Key $mainSalt
	// This salt will be appended to passwords in the icms_encryptPass() function.
	// Do NOT change this once your site is Live, doing so will invalidate everyones Password.
	define('XOOPS_DB_SALT', '');
	
	// Use persistent connection? (Yes=1 No=0)
	// Default is 'Yes'. Choose 'Yes' if you are unsure.
	define('XOOPS_DB_PCONNECT', 0);

	// (optional) Physical path to script that logs database queries.
	// Example: define('ICMS_LOGGING_HOOK', XOOPS_ROOT_PATH . '/modules/foobar/logging_hook.php');
	define('ICMS_LOGGING_HOOK', '');

	define("XOOPS_GROUP_ADMIN", "1");
	define("XOOPS_GROUP_USERS", "2");
	define("XOOPS_GROUP_ANONYMOUS", "3");

    foreach ( array('GLOBALS', '_SESSION', 'HTTP_SESSION_VARS', '_GET', 'HTTP_GET_VARS', '_POST', 'HTTP_POST_VARS', '_COOKIE', 'HTTP_COOKIE_VARS', '_REQUEST', '_SERVER', 'HTTP_SERVER_VARS', '_ENV', 'HTTP_ENV_VARS', '_FILES', 'HTTP_POST_FILES', 'xoopsDB', 'xoopsUser', 'xoopsUserId', 'xoopsUserGroups', 'xoopsUserIsAdmin', 'icmsConfig', 'xoopsOption', 'xoopsModule', 'xoopsModuleConfig', 'xoopsRequestUri') as $bad_global) {
        if (isset( $_REQUEST[$bad_global] )) {
            header( 'Location: '.XOOPS_URL.'/' );
            exit();
        }
    }

	define('ICMS_GROUP_ADMIN', XOOPS_GROUP_ADMIN);
	define('ICMS_GROUP_USERS', XOOPS_GROUP_USERS);
	define('ICMS_GROUP_ANONYMOUS', XOOPS_GROUP_ANONYMOUS);
	define( 'ICMS_URL', XOOPS_URL );
	define( 'ICMS_TRUST_PATH', XOOPS_TRUST_PATH );
	define( 'ICMS_ROOT_PATH', XOOPS_ROOT_PATH );
	if (!isset($xoopsOption['nocommon']) && XOOPS_ROOT_PATH != '') {
		include XOOPS_ROOT_PATH."/include/common.php";
	}
}
?>