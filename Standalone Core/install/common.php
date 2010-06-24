<?php
/**
* Common class and functions used during installation
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		installer
* @since		XOOPS
* @author		http://www.xoops.org The XOOPS Project
* @author		modified by m0nty_
* @version		$Id: common.php 9621 2009-11-29 12:49:47Z Phoenyx $
*/
/**
 *
 */
defined("XOOPS_MAINFILE_INCLUDED") or die();

	@set_magic_quotes_runtime(0);
if (!defined('ICMS_ROOT_PATH')) {
	define( 'ICMS_ROOT_PATH', XOOPS_ROOT_PATH );
}
if (!defined('ICMS_TRUST_PATH')) {
	define( 'ICMS_TRUST_PATH', XOOPS_TRUST_PATH );
}
if (!defined('ICMS_URL')) {
	define( 'ICMS_URL', XOOPS_URL );
}
if (!defined('ICMS_GROUP_ADMIN')) {
	define('ICMS_GROUP_ADMIN', XOOPS_GROUP_ADMIN);
}
if (!defined('ICMS_GROUP_USERS')) {
	define('ICMS_GROUP_USERS', XOOPS_GROUP_USERS);
}
if (!defined('ICMS_GROUP_ANONYMOUS')) {
	define('ICMS_GROUP_ANONYMOUS', XOOPS_GROUP_ANONYMOUS);
}

/**
 * Creating ICMS specific constants
 */
define('ICMS_PLUGINS_PATH', ICMS_ROOT_PATH.'/plugins');
define('ICMS_PLUGINS_URL', ICMS_URL.'/plugins');
define('ICMS_PRELOAD_PATH', ICMS_PLUGINS_PATH.'/preloads');
define('ICMS_PURIFIER_CACHE', ICMS_TRUST_PATH.'/cache/htmlpurifier');
// ImpressCMS Modules path & url
define( 'ICMS_MODULES_PATH', ICMS_ROOT_PATH . '/modules' );
define( 'ICMS_MODULES_URL', ICMS_URL . '/modules' );
// ################# Creation of the ImpressCMS Libraries ##############
/**
 * @todo The definition of the library path needs to be in mainfile
 */
// ImpressCMS Third Party Libraries folder
define( 'ICMS_LIBRARIES_PATH', ICMS_ROOT_PATH . '/libraries' );
define( 'ICMS_LIBRARIES_URL', ICMS_URL . '/libraries' );
// ImpressCMS Third Party Library for PDF generator
define( 'ICMS_PDF_LIB_PATH', ICMS_ROOT_PATH . '/libraries/tcpdf' );
define( 'ICMS_PDF_LIB_URL', ICMS_URL . '/libraries/tcpdf' );

/**
 * Extremely reduced kernel class
 * This class should not really be defined in this file, but it wasn't worth including an entire
 * file for those two functions.
 * Few notes:
 * - modules should use this class methods to generate physical paths/URIs (the ones which do not conform
 * will perform badly when true URL rewriting is implemented)
 */
class xos_kernel_Xoops2 {
	var $paths = array(
		'www' => array(), 'modules' => array(), 'themes' => array(),
	);
	function xos_kernel_Xoops2() {
		$this->paths['www'] = array( XOOPS_ROOT_PATH, XOOPS_URL );
		$this->paths['modules'] = array( XOOPS_ROOT_PATH . '/modules', XOOPS_URL . '/modules' );
		$this->paths['themes'] = array( XOOPS_ROOT_PATH . '/themes', XOOPS_URL . '/themes' );
	}
	/**
	 * Convert a XOOPS path to a physical one
	 */
	function path( $url, $virtual = false ) {
		$path = '';
		@list( $root, $path ) = explode( '/', $url, 2 );
		if ( !isset( $this->paths[$root] ) ) {
			list( $root, $path ) = array( 'www', $url );
		}
		if ( !$virtual ) {		// Returns a physical path
			return $this->paths[$root][0] . '/' . $path;
		}
		return !isset( $this->paths[$root][1] ) ? '' : ( $this->paths[$root][1] . '/' . $path );
	}
	/**
	* Convert a XOOPS path to an URL
	*/
	function url( $url ) {
		return ( false !== strpos( $url, '://' ) ? $url : $this->path( $url, true ) );
	}
	/**
	* Build an URL with the specified request params
	*/
	function buildUrl( $url, $params = array() ) {
		if ( $url == '.' ) {
			$url = $_SERVER['REQUEST_URI'];
		}
		$split = explode( '?', $url );
		if ( count($split) > 1 ) {
			list( $url, $query ) = $split;
			parse_str( $query, $query );
			$params = array_merge( $query, $params );
		}
		if ( !empty( $params ) ) {
			foreach ( $params as $k => $v ) {
				$params[$k] = $k . '=' . rawurlencode($v);
			}
			$url .= '?' . implode( '&', $params );
		}
		return $url;
	}




}
global $xoops;
$xoops = new xos_kernel_Xoops2();

    // Instantiate security object
    require_once XOOPS_ROOT_PATH."/class/xoopssecurity.php";
    $xoopsSecurity = new XoopsSecurity();
    global $xoopsSecurity;
    //Check super globals
    $xoopsSecurity->checkSuperglobals();

    // ############## Activate error handler / logger class ##############
    global $xoopsLogger, $xoopsErrorHandler;

    include_once XOOPS_ROOT_PATH . '/class/logger.php';
    $xoopsLogger =& XoopsLogger::instance();
	$xoopsErrorHandler =& $xoopsLogger;
    $xoopsLogger->startTime();
    $xoopsLogger->startTime( 'ICMS Boot' );


define("XOOPS_SIDEBLOCK_LEFT",1);
define("XOOPS_SIDEBLOCK_RIGHT",2);
define("XOOPS_SIDEBLOCK_BOTH",-2);
define("XOOPS_CENTERBLOCK_LEFT",3);
define("XOOPS_CENTERBLOCK_RIGHT",5);
define("XOOPS_CENTERBLOCK_CENTER",4);
define("XOOPS_CENTERBLOCK_ALL",-6);
define("XOOPS_CENTERBLOCK_BOTTOMLEFT",6);
define("XOOPS_CENTERBLOCK_BOTTOMRIGHT",8);
define("XOOPS_CENTERBLOCK_BOTTOM",7);

define("XOOPS_BLOCK_INVISIBLE",0);
define("XOOPS_BLOCK_VISIBLE",1);
define("XOOPS_MATCH_START",0);
define("XOOPS_MATCH_END",1);
define("XOOPS_MATCH_EQUAL",2);
define("XOOPS_MATCH_CONTAIN",3);

define("ICMS_KERNEL_PATH", ICMS_ROOT_PATH."/kernel/");
define("ICMS_INCLUDE_PATH", ICMS_ROOT_PATH."/include");
define("ICMS_INCLUDE_URL", ICMS_ROOT_PATH."/include");
define("ICMS_UPLOAD_PATH", ICMS_ROOT_PATH."/uploads");
define("ICMS_UPLOAD_URL", ICMS_URL."/uploads");
define("ICMS_THEME_PATH", ICMS_ROOT_PATH."/themes");
define("ICMS_THEME_URL", ICMS_URL."/themes");
define("ICMS_COMPILE_PATH", ICMS_ROOT_PATH."/templates_c");
define("ICMS_CACHE_PATH", ICMS_ROOT_PATH."/cache");
define("ICMS_IMAGES_URL", ICMS_URL."/images");
define("ICMS_EDITOR_PATH", ICMS_ROOT_PATH."/editors");
define("ICMS_EDITOR_URL", ICMS_URL."/editors");
define('ICMS_IMANAGER_FOLDER_PATH',ICMS_UPLOAD_PATH.'/imagemanager');
define('ICMS_IMANAGER_FOLDER_URL',ICMS_UPLOAD_URL.'/imagemanager');


/**
 * @todo make this $icms_images_setname as an option in preferences...
 */
$icms_images_setname = 'crystal';
define("ICMS_IMAGES_SET_URL", ICMS_IMAGES_URL."/" . $icms_images_setname);

/**#@+
 * Deprectaed: for backward compatibility
 */
define("XOOPS_INCLUDE_PATH", ICMS_INCLUDE_PATH);
define("XOOPS_INCLUDE_URL", ICMS_INCLUDE_URL);
define("XOOPS_UPLOAD_PATH", ICMS_UPLOAD_PATH);
define("XOOPS_UPLOAD_URL", ICMS_UPLOAD_URL);
define("XOOPS_THEME_PATH", ICMS_THEME_PATH);
define("XOOPS_THEME_URL", ICMS_THEME_URL);
define("XOOPS_COMPILE_PATH", ICMS_COMPILE_PATH);
define("XOOPS_CACHE_PATH", ICMS_CACHE_PATH);
define("XOOPS_EDITOR_PATH", ICMS_EDITOR_PATH);
define("XOOPS_EDITOR_URL", ICMS_EDITOR_URL);


define("SMARTY_DIR", ICMS_LIBRARIES_PATH."/smarty/");


    if (!defined('XOOPS_XMLRPC')) {
        define('XOOPS_DB_CHKREF', 1);
    } else {
        define('XOOPS_DB_CHKREF', 0);
    }

    // ############## Include common functions file ##############
    include_once XOOPS_ROOT_PATH.'/include/functions.php';

    // #################### Connect to DB ##################
    require_once XOOPS_ROOT_PATH.'/class/database/databasefactory.php';
    if ($_SERVER['REQUEST_METHOD'] != 'POST' || !$xoopsSecurity->checkReferer(XOOPS_DB_CHKREF)) {
        define('XOOPS_DB_PROXY', 1);
    }
    $xoopsDB =& XoopsDatabaseFactory::getDatabaseConnection();

    // ################# Include required files ##############
    require_once XOOPS_ROOT_PATH.'/kernel/object.php';
    require_once XOOPS_ROOT_PATH.'/class/criteria.php';

    // #################### Include text sanitizer ##################
    include_once XOOPS_ROOT_PATH."/class/module.textsanitizer.php";

    // ################# Load Config Settings ##############
    $config_handler =& xoops_gethandler('config');
    $xoopsConfig =& $config_handler->getConfigsByCat(XOOPS_CONF);
	$icmsConfig =& $xoopsConfig;
	
    // #################### Error reporting settings ##################
    if ( $xoopsConfig['debug_mode'] == 1 || $xoopsConfig['debug_mode'] == 2 ) {
        error_reporting(E_ALL);
        $xoopsLogger->enableRendering();
        $xoopsLogger->usePopup = ( $xoopsConfig['debug_mode'] == 2 );
    } else {
	    error_reporting(0);
        $xoopsLogger->activated = false;
    }
	$xoopsSecurity->checkBadips();

    // ################# Include version info file ##############
    include_once XOOPS_ROOT_PATH."/include/version.php";

    // for older versions...will be DEPRECATED!
    $xoopsConfig['xoops_url'] = XOOPS_URL;
    $xoopsConfig['root_path'] = XOOPS_ROOT_PATH."/";


    // #################### Include site-wide lang file ##################
    if ( file_exists(XOOPS_ROOT_PATH."/language/".$xoopsConfig['language']."/global.php") ) {
        include_once XOOPS_ROOT_PATH."/language/".$xoopsConfig['language']."/global.php";
    } else {
        include_once XOOPS_ROOT_PATH."/language/english/global.php";
    }

    // ################ Include page-specific lang file ################
    if (isset($xoopsOption['pagetype']) && false === strpos($xoopsOption['pagetype'], '.')) {
        if ( file_exists(XOOPS_ROOT_PATH."/language/".$xoopsConfig['language']."/".$xoopsOption['pagetype'].".php") ) {
            include_once XOOPS_ROOT_PATH."/language/".$xoopsConfig['language']."/".$xoopsOption['pagetype'].".php";
        } else {
            include_once XOOPS_ROOT_PATH."/language/english/".$xoopsOption['pagetype'].".php";
        }
    }
    $xoopsOption = array();

    if ( !defined("XOOPS_USE_MULTIBYTES") ) {
        define("XOOPS_USE_MULTIBYTES",0);
    }

    /**#@+
     * Host abstraction layer
     */
    if ( !isset($_SERVER['PATH_TRANSLATED']) && isset($_SERVER['SCRIPT_FILENAME']) ) {
        $_SERVER['PATH_TRANSLATED'] =& $_SERVER['SCRIPT_FILENAME'];     // For Apache CGI
    } elseif ( isset($_SERVER['PATH_TRANSLATED']) && !isset($_SERVER['SCRIPT_FILENAME']) ) {
        $_SERVER['SCRIPT_FILENAME'] =& $_SERVER['PATH_TRANSLATED'];     // For IIS/2K now I think :-(
    }

    if ( empty( $_SERVER[ 'REQUEST_URI' ] ) ) {         // Not defined by IIS
        // Under some configs, IIS makes SCRIPT_NAME point to php.exe :-(
        if ( !( $_SERVER[ 'REQUEST_URI' ] = @$_SERVER['PHP_SELF'] ) ) {
            $_SERVER[ 'REQUEST_URI' ] = $_SERVER['SCRIPT_NAME'];
        }
        if ( isset( $_SERVER[ 'QUERY_STRING' ] ) ) {
            $_SERVER[ 'REQUEST_URI' ] .= '?' . $_SERVER[ 'QUERY_STRING' ];
        }
    }
    $xoopsRequestUri = $_SERVER[ 'REQUEST_URI' ];       // Deprecated (use the corrected $_SERVER variable now)
    /**#@-*/

    // ############## Login a user with a valid session ##############
    $xoopsUser = '';
    $xoopsUserIsAdmin = false;
    $member_handler =& xoops_gethandler('member');
    $sess_handler =& xoops_gethandler('session');
    if ($xoopsConfig['use_ssl'] && isset($_POST[$xoopsConfig['sslpost_name']]) && $_POST[$xoopsConfig['sslpost_name']] != '') {
        session_id($_POST[$xoopsConfig['sslpost_name']]);
    } elseif ($xoopsConfig['use_mysession'] && $xoopsConfig['session_name'] != '') {
        if (isset($_COOKIE[$xoopsConfig['session_name']])) {
            session_id($_COOKIE[$xoopsConfig['session_name']]);
        } else {
            // no custom session cookie set, destroy session if any
            $_SESSION = array();
            //session_destroy();
        }
        if (function_exists('session_cache_expire')) {
            session_cache_expire($xoopsConfig['session_expire']);
        }
        @ini_set('session.gc_maxlifetime', $xoopsConfig['session_expire'] * 60);
    }
    session_set_save_handler(array(&$sess_handler, 'open'), array(&$sess_handler, 'close'), array(&$sess_handler, 'read'), array(&$sess_handler, 'write'), array(&$sess_handler, 'destroy'), array(&$sess_handler, 'gc'));
    session_start();

    if (!empty($_SESSION['xoopsUserId'])) {
        $xoopsUser =& $member_handler->getUser($_SESSION['xoopsUserId']);
        if (!is_object($xoopsUser)) {
            $xoopsUser = '';
            $_SESSION = array();
        } else {
            if ($xoopsConfig['use_mysession'] && $xoopsConfig['session_name'] != '') {
                setcookie($xoopsConfig['session_name'], session_id(), time()+(60*$xoopsConfig['session_expire']), '/',  '', 0);
            }
            $xoopsUser->setGroups($_SESSION['xoopsUserGroups']);
            $xoopsUserIsAdmin = $xoopsUser->isAdmin();
        }
    }
    if (!empty($_POST['theme_select']) && in_array($_POST['theme_select'], $xoopsConfig['theme_set_allowed'])) {
        $xoopsConfig['theme_set'] = $_POST['theme_select'];
        $_SESSION['xoopsUserTheme'] = $_POST['theme_select'];
    } elseif (!empty($_SESSION['xoopsUserTheme']) && in_array($_SESSION['xoopsUserTheme'], $xoopsConfig['theme_set_allowed'])) {
        $xoopsConfig['theme_set'] = $_SESSION['xoopsUserTheme'];
    }

    if ($xoopsConfig['closesite'] == 1) {
        $allowed = false;
        if (is_object($xoopsUser)) {
            foreach ($xoopsUser->getGroups() as $group) {
                if (in_array($group, $xoopsConfig['closesite_okgrp']) || XOOPS_GROUP_ADMIN == $group) {
                    $allowed = true;
                    break;
                }
            }
        } elseif (!empty($_POST['xoops_login'])) {
            include_once XOOPS_ROOT_PATH.'/include/checklogin.php';
            exit();
        }
        if (!$allowed) {
            include_once XOOPS_ROOT_PATH.'/class/template.php';
            $xoopsTpl = new XoopsTpl();
            $xoopsTpl->assign( array(
            	'sitename' => $xoopsConfig['sitename'],
            	'xoops_themecss' => xoops_getcss(),
            	'xoops_imageurl' => XOOPS_THEME_URL.'/'.$xoopsConfig['theme_set'].'/',
            	'lang_login' => _LOGIN,
            	'lang_username' => _USERNAME,
            	'lang_password' => _PASSWORD,
            	'lang_siteclosemsg' => $xoopsConfig['closesite_text'],
            	'xoops_requesturi' => $_SERVER['REQUEST_URI'],
            ) );
            $xoopsTpl->caching = 0;
            $xoopsTpl->display('db:system_siteclosed.html');
            exit();
        }
        unset($allowed, $group);
    }

    if (file_exists('./xoops_version.php')) {
        $url_arr = explode( '/', strstr( $_SERVER['PHP_SELF'],'/modules/') );
        $module_handler =& xoops_gethandler('module');
        $xoopsModule =& $module_handler->getByDirname($url_arr[2]);
        unset($url_arr);
        if (!$xoopsModule || !$xoopsModule->getVar('isactive')) {
            include_once XOOPS_ROOT_PATH."/header.php";
            echo "<h4>"._MODULENOEXIST."</h4>";
            include_once XOOPS_ROOT_PATH."/footer.php";
            exit();
        }
        $moduleperm_handler =& xoops_gethandler('groupperm');
        if ($xoopsUser) {
            if (!$moduleperm_handler->checkRight('module_read', $xoopsModule->getVar('mid'), $xoopsUser->getGroups())) {
                redirect_header(XOOPS_URL."/user.php",1,_NOPERM);
            }
            $xoopsUserIsAdmin = $xoopsUser->isAdmin($xoopsModule->getVar('mid'));
        } else {
            if (!$moduleperm_handler->checkRight('module_read', $xoopsModule->getVar('mid'), XOOPS_GROUP_ANONYMOUS)) {
                redirect_header(XOOPS_URL."/user.php",1,_NOPERM);
            }
        }
        if ( file_exists(XOOPS_ROOT_PATH."/modules/".$xoopsModule->getVar('dirname')."/language/".$xoopsConfig['language']."/main.php") ) {
            include_once XOOPS_ROOT_PATH."/modules/".$xoopsModule->getVar('dirname')."/language/".$xoopsConfig['language']."/main.php";
        } else {
            if ( file_exists(XOOPS_ROOT_PATH."/modules/".$xoopsModule->getVar('dirname')."/language/english/main.php") ) {
                include_once XOOPS_ROOT_PATH."/modules/".$xoopsModule->getVar('dirname')."/language/english/main.php";
            }
        }
        if ($xoopsModule->getVar('hasconfig') == 1 || $xoopsModule->getVar('hascomments') == 1 || $xoopsModule->getVar( 'hasnotification' ) == 1) {
            $xoopsModuleConfig =& $config_handler->getConfigsByCat(0, $xoopsModule->getVar('mid'));
        }
    } elseif($xoopsUser) {
        $xoopsUserIsAdmin = $xoopsUser->isAdmin(1);
    }
    $xoopsLogger->stopTime( 'ICMS Boot' );
    $xoopsLogger->startTime( 'Module init' );

?>