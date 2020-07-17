<?php
/**
 * Authorization classes, Base class file
 *
 * defines abstract authentification wrapper class
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Auth
 * @version		SVN: $Id: Object.php 20740 2011-02-01 03:36:20Z skenow $
 */

/**
 * Authentification base class
 *
 * @category	ICMS
 * @package     Auth
 * @author	    Pierre-Eric MENUET	<pemphp@free.fr>
 */
class icms_auth_Object {

	private $_dao;

	private $_errors;

	/**
	 * Authentication Service constructor
	 */
	public function __construct(&$dao) {
		$this->_dao = $dao;
	}

	/**
	 * authenticate
	 *
	 * @abstract need to be written in the derived class
	 * @return bool whether user is authenticated
	 * @todo	Cannot declare this as abstract until the OpenID method is compliant
	 */
	public function authenticate($uname, $pwd = null) {
	}

	/**
	 * add an error
	 *
	 * @param string $value error to add
	 * @access public
	 */
	public function setErrors($err_no, $err_str) {
		$this->_errors[$err_no] = trim($err_str);
	}

	/**
	 * return the errors for this object as an array
	 *
	 * @return array an array of errors
	 * @access public
	 */
	public function getErrors() {
		return $this->_errors;
	}

	/**
	 * return the errors for this object as html
	 *
	 * @return string $ret html listing the errors
	 * @access public
	 */
	public function getHtmlErrors() {
		global $icmsConfigPersona;
		$ret = '<br />';
		if ($icmsConfigPersona['debug_mode'] < 3) {
			$ret .= _US_INCORRECTLOGIN;
		} else {
			if (empty($this->_errors)) {
				$ret .= _NONE . '<br />';
			} else {
				foreach ($this->_errors as $errno => $errstr) {
					$ret .=  $errstr . '<br/>';
			}
			/**
			 * Fix to replace the message "Incorrect Login using xoops authenticated method"
			 * as this message don't say much to normal users...
			 * This fix of course is temporary and will change in the future
			 */
			$auth_method_name = $this->auth_method == 'xoops' ? 'standard' : $this->auth_method;
			$ret .= sprintf(_AUTH_MSG_AUTH_METHOD, $auth_method_name);
		}
		return $ret;
		}
	}
}

