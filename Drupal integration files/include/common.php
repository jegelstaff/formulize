<?php
// $Id: common.php 790 2006-11-06 19:28:16Z phppp $
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //

defined("XOOPS_MAINFILE_INCLUDED") or die();

	set_magic_quotes_runtime(0);

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
$xoops =& new xos_kernel_Xoops2();

    // Instantiate security object
    require_once XOOPS_ROOT_PATH."/class/xoopssecurity.php";
    global $xoopsSecurity;
    $xoopsSecurity = new XoopsSecurity();
    //Check super globals
    $xoopsSecurity->checkSuperglobals();
    
    // ############## Activate error handler / logger class ##############
    global $xoopsLogger, $xoopsErrorHandler;
    
    include_once XOOPS_ROOT_PATH . '/class/logger.php';
    $xoopsLogger =& XoopsLogger::instance();
	$xoopsErrorHandler =& $xoopsLogger;
    $xoopsLogger->startTime();
    $xoopsLogger->startTime( 'XOOPS Boot' );
    
    
    define("XOOPS_SIDEBLOCK_LEFT",0);
    define("XOOPS_SIDEBLOCK_RIGHT",1);
    define("XOOPS_SIDEBLOCK_BOTH",2);
    define("XOOPS_CENTERBLOCK_LEFT",3);
    define("XOOPS_CENTERBLOCK_RIGHT",4);
    define("XOOPS_CENTERBLOCK_CENTER",5);
    define("XOOPS_CENTERBLOCK_ALL",6);
    define("XOOPS_CENTERBLOCK_BOTTOMLEFT",7);
    define("XOOPS_CENTERBLOCK_BOTTOMRIGHT",8);
    define("XOOPS_CENTERBLOCK_BOTTOM",9);
    define("XOOPS_BLOCK_INVISIBLE",0);
    define("XOOPS_BLOCK_VISIBLE",1);
    define("XOOPS_MATCH_START",0);
    define("XOOPS_MATCH_END",1);
    define("XOOPS_MATCH_EQUAL",2);
    define("XOOPS_MATCH_CONTAIN",3);
    define("SMARTY_DIR", XOOPS_ROOT_PATH."/class/smarty/");
    define("XOOPS_CACHE_PATH", XOOPS_ROOT_PATH."/cache");
    define("XOOPS_UPLOAD_PATH", XOOPS_ROOT_PATH."/uploads");
    define("XOOPS_THEME_PATH", XOOPS_ROOT_PATH."/themes");
    define("XOOPS_COMPILE_PATH", XOOPS_ROOT_PATH."/templates_c");
    define("XOOPS_THEME_URL", XOOPS_URL."/themes");
    define("XOOPS_UPLOAD_URL", XOOPS_URL."/uploads");

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

    // ADDED BY FREEFORM SOLUTIONS - MAY 11 2007 
    // IF THERE IS A LOGGED IN DRUPAL USER, THEN USE THAT USER ID FOR THE SESSION.
    // THIS TECHNIQUE RELIES ON THE XOOPS USER TABLE AND DRUPAL USER TABLE BEING IN SYNCH IN TERMS OF USER IDS!
    // IF THAT SYNCHRONIZATION IS ACHIEVED, THEN ALL PERMISSIONS IN THE XOOPS SYSTEM APPLY TO USERS LOGGED IN
    // ON THE DRUPAL SIDE.  A SIMILAR CORRESPONDENCE OF DRUPAL ROLES AND XOOPS GROUPS WOULD BE BENEFICIAL.
    if($user->uid) { $_SESSION['xoopsUserId'] = $user->uid; }

    $xoopsUser = '';
    $xoopsUserIsAdmin = false;
    $member_handler =& xoops_gethandler('member');
    $sess_handler =& xoops_gethandler('session');
    if ($xoopsConfig['use_ssl'] && isset($_POST[$xoopsConfig['sslpost_name']]) && $_POST[$xoopsConfig['sslpost_name']] != '') {
        session_id($_POST[$xoopsConfig['sslpost_name']]);
    } elseif ($xoopsConfig['use_mysession'] && $xoopsConfig['session_name'] != '') {
        if (isset($_COOKIE[$xoopsConfig['session_name']])) {
            session_id($_COOKIE[$xoopsConfig['session_name']]);
        }
        if (function_exists('session_cache_expire')) {
            session_cache_expire($xoopsConfig['session_expire']);
        }
        @ini_set('session.gc_maxlifetime', $xoopsConfig['session_expire'] * 60);     
    }
    session_set_save_handler(array(&$sess_handler, 'open'), array(&$sess_handler, 'close'), array(&$sess_handler, 'read'), array(&$sess_handler, 'write'), array(&$sess_handler, 'destroy'), array(&$sess_handler, 'gc'));
    session_start();
    
    // Remove expired session for xoopsUserId
    if ( $xoopsConfig['use_mysession'] && $xoopsConfig['session_name'] != '' && !isset($_COOKIE[$xoopsConfig['session_name']]) && !empty($_SESSION['xoopsUserId']) ) {
	    unset( $_SESSION['xoopsUserId'] );
    }

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
    if (!empty($_POST['xoops_theme_select']) && in_array($_POST['xoops_theme_select'], $xoopsConfig['theme_set_allowed'])) {
        $xoopsConfig['theme_set'] = $_POST['xoops_theme_select'];
        $_SESSION['xoopsUserTheme'] = $_POST['xoops_theme_select'];
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
                exit();
            }
            $xoopsUserIsAdmin = $xoopsUser->isAdmin($xoopsModule->getVar('mid'));
        } else {
            if (!$moduleperm_handler->checkRight('module_read', $xoopsModule->getVar('mid'), XOOPS_GROUP_ANONYMOUS)) {
                redirect_header(XOOPS_URL."/user.php",1,_NOPERM);
                exit();
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
    $xoopsLogger->stopTime( 'XOOPS Boot' );
    $xoopsLogger->startTime( 'Module init' );

?>