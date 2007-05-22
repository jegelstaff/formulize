<?php
// $Id: xoopssecurity.php 2 2005-11-02 18:23:29Z skalpa $
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
// Author: Kazumi Ono (AKA onokazu)                                          //
// URL: http://www.myweb.ne.jp/, http://www.xoops.org/, http://jp.xoops.org/ //
// Project: The XOOPS Project                                                //
// ------------------------------------------------------------------------- //
/*
 * Class for managing security aspects such as checking referers, applying tokens and checking global variables for contamination
 *
 * @package        kernel
 * @subpackage    core
 *
 * @author        Jan Pedersen     <mithrandir@xoops.org>
 * @copyright    (c) 2000-2005 The Xoops Project - www.xoops.org
 */

class XoopsSecurity {
    var $errors = array();
    /**
     * Constructor
     *
     **/
    function XoopsSecurity() {
    }
    
    /**
    * Check if there is a valid token in $_REQUEST['XOOPS_TOKEN_REQUEST'] - can be expanded for more wide use, later (Mith)
    *
    * @param bool   $clearIfValid whether to clear the token after validation
    * @param string $token token to validate
    *
    * @return bool
    */
    function check($clearIfValid = true, $token = false) {
        return $this->validateToken($token, $clearIfValid);
    }

    /**
    * Create a token in the user's session
    *
    * @param int $timeout time in seconds the token should be valid
    *
    * @return string token value
    */
    function createToken($timeout = 0)
    {
        $this->garbageCollection();
        if ($timeout == 0) {
            $timeout = $GLOBALS['xoopsConfig']['session_expire'] * 60; //session_expire is in minutes, we need seconds
        }
        $token_id = md5(uniqid(rand(), true));
        // save token data on the server
        if (!isset($_SESSION['XOOPS_TOKEN_SESSION'])) {
            $_SESSION['XOOPS_TOKEN_SESSION'] = array();
        }
        $token_data = array('id' => $token_id, 'expire' => time() + intval($timeout));
        array_push($_SESSION['XOOPS_TOKEN_SESSION'], $token_data);
        return md5($token_id.$_SERVER['HTTP_USER_AGENT'].XOOPS_DB_PREFIX);
    }

    /**
    * Check if a token is valid. If no token is specified, $_REQUEST['XOOPS_TOKEN_REQUEST'] is checked
    *
    * @param string $token token to validate
    * @param bool   $clearIfValid whether to clear the token value if valid
    *
    * @return bool
    **/
    function validateToken($token = false, $clearIfValid = true)
    {
        global $xoopsLogger;
        $token = ($token === false) ? @$_REQUEST['XOOPS_TOKEN_REQUEST'] : $token;
        if (empty($token) || empty($_SESSION['XOOPS_TOKEN_SESSION'])) {
            $xoopsLogger->addExtra('Token Validation', 'No valid token found in request/session');
            return false;
        }
        $validFound = false;
        $token_data =& $_SESSION['XOOPS_TOKEN_SESSION'];
        foreach (array_keys($token_data) as $i) {
            if ($token === md5($token_data[$i]['id'].$_SERVER['HTTP_USER_AGENT'].XOOPS_DB_PREFIX)) {
                if ($this->filterToken($token_data[$i])) {
                    if ($clearIfValid) {
                        // token should be valid once, so clear it once validated
                        unset($token_data[$i]);
                    }
                    $xoopsLogger->addExtra('Token Validation', 'Valid token found');
                    $validFound = true;
                }
                else {
                    $str = 'Valid token expired';
                    $this->setErrors($str);
                    $xoopsLogger->addExtra('Token Validation', $str);
                }
            }
        }
        if (!$validFound) {
            $xoopsLogger->addExtra('Token Validation', 'No valid token found');
        }
        $this->garbageCollection();
        return $validFound;
    }

    /**
    * Clear all token values from user's session
    **/
    function clearTokens()
    {
        $_SESSION['XOOPS_TOKEN_SESSION'] = array();
    }

    /**
    * Check whether a token value is expired or not
    *
    * @param string $token
    *
    * @return bool
    **/
    function filterToken($token)
    {
        return (!empty($token['expire']) && $token['expire'] >= time());
    }

    /**
    * Perform garbage collection, clearing expired tokens
    *
    * @return void
    **/
    function garbageCollection() {
        if (isset($_SESSION['XOOPS_TOKEN_SESSION']) && count($_SESSION['XOOPS_TOKEN_SESSION']) > 0) {
            $_SESSION['XOOPS_TOKEN_SESSION'] = array_filter($_SESSION['XOOPS_TOKEN_SESSION'], array($this, 'filterToken'));
        }
    }
    /**
    * Check the user agent's HTTP REFERER against XOOPS_URL
    *
    * @param int $docheck 0 to not check the referer (used with XML-RPC), 1 to actively check it
    *
    * @return bool
    **/
    function checkReferer($docheck=1)
    {
        return true;
        $ref = xoops_getenv('HTTP_REFERER');
        if ($docheck == 0) {
            return true;
        }
        if ($ref == '') {
            return false;
        }
        if (strpos($ref, XOOPS_URL) !== 0 ) {
            return false;
        }
        return true;
    }
    
    /**
    * Check superglobals for contamination
    *
    * @return void
    **/
    function checkSuperglobals() {
        foreach (array('GLOBALS', '_SESSION', 'HTTP_SESSION_VARS', '_GET', 'HTTP_GET_VARS', '_POST', 'HTTP_POST_VARS', '_COOKIE', 'HTTP_COOKIE_VARS', '_REQUEST', '_SERVER', 'HTTP_SERVER_VARS', '_ENV', 'HTTP_ENV_VARS', '_FILES', 'HTTP_POST_FILES', 'xoopsDB', 'xoopsUser', 'xoopsUserId', 'xoopsUserGroups', 'xoopsUserIsAdmin', 'xoopsConfig', 'xoopsOption', 'xoopsModule', 'xoopsModuleConfig', 'xoopsRequestUri') as $bad_global) {
            if (isset($_REQUEST[$bad_global])) {
                header('Location: '.XOOPS_URL.'/');
                exit();
            }
        }
    }

    /**
    * Check if visitor's IP address is banned
    * Should be changed to return bool and let the action be up to the calling script
    *
    * @return void
    **/
    function checkBadips() {
        global $xoopsConfig;
        if ($xoopsConfig['enable_badips'] == 1 && isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] != '') {
            foreach ($xoopsConfig['bad_ips'] as $bi) {
                if (!empty($bi) && preg_match("/".$bi."/", $_SERVER['REMOTE_ADDR'])) {
                    exit();
                }
            }
        }
        unset($bi);
        unset($bad_ips);
        unset($xoopsConfig['badips']);
    }
    
    /**
    * Get the HTML code for a XoopsFormHiddenToken object - used in forms that do not use XoopsForm elements
    *
    * @return string
    **/
    function getTokenHTML() {
        require_once(XOOPS_ROOT_PATH."/class/xoopsformloader.php");
        $token = new XoopsFormHiddenToken();
        return $token->render();
    }
    
    /**
     * Add an error
     * 
     * @param   string  $error
     **/
    function setErrors($error)
    {
        $this->errors[] = trim($error);
    }
    
    /**
     * Get generated errors
     *
     * @param    bool    $ashtml Format using HTML?
     * 
     * @return    array|string    Array of array messages OR HTML string
     */
    function &getErrors($ashtml = false)
    {
        if (!$ashtml) {
            return $this->errors;
        } else {
            $ret = '';
            if (count($this->errors) > 0) {
                foreach ($this->errors as $error) {
                    $ret .= $error.'<br />';
                }
            }
            return $ret;
        }
    }
}
?>