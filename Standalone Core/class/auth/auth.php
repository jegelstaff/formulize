<?php
// $Id: auth.php 8662 2009-05-01 09:04:30Z pesianstranger $
// auth.php - defines abstract authentification wrapper class
/**
* Authorization classes, Base class file
*
* defines abstract authentification wrapper class
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	Authorization
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: auth.php 8662 2009-05-01 09:04:30Z pesianstranger $
*/


/**
 * Authentification base class
 *
 * @package     kernel
 * @subpackage  auth
 * @author	    Pierre-Eric MENUET	<pemphp@free.fr>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsAuth {

  	var	$_dao;

  	var	$_errors;


  	/**
  	 * Authentication Service constructor
  	 */
  	function XoopsAuth (&$dao){
  		$this->_dao = $dao;
  	}

  	/**
     * authenticate
     *
  	 * @abstract need to be write in the derived class
     * @return bool whether user is authenticated
  	 */
  	function authenticate() {
  		$authenticated = false;

  		return $authenticated;
  	}

    /**
     * add an error
     *
     * @param string $value error to add
     * @access public
     */
    function setErrors($err_no, $err_str)
    {
        $this->_errors[$err_no] = trim($err_str);
    }

    /**
     * return the errors for this object as an array
     *
     * @return array an array of errors
     * @access public
     */
    function getErrors()
    {
        return $this->_errors;
    }

    /**
     * return the errors for this object as html
     *
     * @return string $ret html listing the errors
     * @access public
     */
    function getHtmlErrors()
    {
    	global $icmsConfigPersona;
        $ret = '<br />';
        if ( $icmsConfigPersona['debug_mode'] == 1 || $icmsConfigPersona['debug_mode'] == 2 )
        {
	        if (!empty($this->_errors)) {
	            foreach ($this->_errors as $errno => $errstr) {
	                $ret .=  $errstr . '<br/>';
	            }
	        } else {
	            $ret .= _NONE.'<br />';
	        }
	        /**
	         * Fix to replace the message "Incorrect Login using xoops authenticated method"
	         * as this message don't say much to normal users...
	         * This fix of course is temporary and will change in the future
	         */
	        $auth_method_name = $this->auth_method == 'xoops' ? 'standard' : $this->auth_method;
	        $ret .= sprintf(_AUTH_MSG_AUTH_METHOD, $auth_method_name);
        }
	    else {
	    	$ret .= _US_INCORRECTLOGIN;
	    }
        return $ret;
    }
}

?>