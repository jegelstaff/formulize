<?php
/**
 * Handles all security functions within ImpressCMS
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Core
 * @subpackage	Security
 * @version		SVN: $Id: Security.php 22529 2011-09-02 19:55:40Z phoenyx $
 */
/**
 * Class for managing security aspects such as checking referers, applying tokens and checking global variables for contamination
 *
 * @category	ICMS
 * @package		Core
 * @subpackage	Security
 *
 * @author        Jan Pedersen     <mithrandir@xoops.org>
 */
class icms_core_Security {

	public $errors = array();

	/**
	 * Initialize the icms::$security service
	 */
	static public function service() {
		$instance = new icms_core_Security();
		$instance->checkSuperglobals();
		if ($_SERVER['REQUEST_METHOD'] != 'POST' || !$instance->checkReferer(XOOPS_DB_CHKREF)) {
			define('XOOPS_DB_PROXY', 1);
		}
		icms_Event::attach('icms', 'loadService-config', array($instance, 'checkBadips'));
		return $instance;
	}

	/**
	 * Constructor
	 *
	 **/
	public function __construct() {
	}

	/**
	 * Check if there is a valid token in $_REQUEST[$name . '_REQUEST'] - can be expanded for more wide use, later (Mith)
	 *
	 * @param bool   $clearIfValid whether to clear the token after validation
	 * @param string $token token to validate
	 * @param string $name session name
	 *
	 * @return bool
	 */
	public function check($clearIfValid = true, $token = false, $name = _CORE_TOKEN) {
		return $this->validateToken($token, $clearIfValid, $name);
	}

	/**
	 * Create a token in the user's session
	 *
	 * @param int $timeout time in seconds the token should be valid
	 * @param string $name session name
	 *
	 * @return string token value
	 */
	public function createToken($timeout = 0, $name = _CORE_TOKEN) {
		$this->garbageCollection($name);
		if ($timeout == 0) {
			$timeout = $GLOBALS['icmsConfig']['session_expire'] * 60; //session_expire is in minutes, we need seconds
		}
		$token_id = md5(uniqid(rand(), true));
		// save token data on the server
		if (!isset($_SESSION[$name . '_SESSION'])) {
			$_SESSION[$name . '_SESSION'] = array();
		}
		$token_data = array('id' => $token_id, 'expire' => time() + (int) ($timeout));
		array_push($_SESSION[$name . '_SESSION'], $token_data);
		return md5($token_id.$_SERVER['HTTP_USER_AGENT'].XOOPS_DB_PREFIX);
	}

	/**
	 * Check if a token is valid. If no token is specified, $_REQUEST[$name . '_REQUEST'] is checked
	 *
	 * @param string $token token to validate
	 * @param bool   $clearIfValid whether to clear the token value if valid
	 * @param string $name session name to validate
	 *
	 * @return bool
	 **/
	public function validateToken($token = false, $clearIfValid = true, $name = _CORE_TOKEN) {
		$token = ($token !== false) ? $token : ( isset($_REQUEST[$name . '_REQUEST']) ? $_REQUEST[$name . '_REQUEST'] : '' );
		if (empty($token) || empty($_SESSION[$name . '_SESSION'])) {
			icms::$logger->addExtra(_CORE_TOKENVALID, _CORE_TOKENNOVALID);
			return false;
		}
		$validFound = false;
		$token_data =& $_SESSION[$name . '_SESSION'];
		foreach (array_keys($token_data) as $i) {
			if ($token === md5($token_data[$i]['id'].$_SERVER['HTTP_USER_AGENT'].XOOPS_DB_PREFIX)) {
				if ($this->filterToken($token_data[$i])) {
					if ($clearIfValid) {
						// token should be valid once, so clear it once validated
						unset($token_data[$i]);
					}
					icms::$logger->addExtra(_CORE_TOKENVALID, _CORE_TOKENISVALID);
					$validFound = true;
				} else {
					$str = _CORE_TOKENEXPIRED;
					$this->setErrors($str);
					icms::$logger->addExtra(_CORE_TOKENVALID, $str);
				}
			}
		}
		if (!$validFound) {
			icms::$logger->addExtra(_CORE_TOKENVALID, _CORE_TOKENINVALID);
		}
		$this->garbageCollection($name);
		return $validFound;
	}

	/**
	 * Clear all token values from user's session
	 *
	 * @param string $name session name
	 **/
	public function clearTokens($name = _CORE_TOKEN) {
		$_SESSION[$name . '_SESSION'] = array();
	}

	/**
	 * Check whether a token value is expired or not
	 *
	 * @param string $token
	 *
	 * @return bool
	 **/
	public function filterToken($token) {
		return (!empty($token['expire']) && $token['expire'] >= time());
	}

	/**
	 * Perform garbage collection, clearing expired tokens
	 *
	 * @param string $name session name
	 *
	 * @return void
	 **/
	public function garbageCollection($name = _CORE_TOKEN) {
		if (isset($_SESSION[$name . '_SESSION']) && count($_SESSION[$name . '_SESSION']) > 0) {
			$_SESSION[$name . '_SESSION'] = array_filter($_SESSION[$name . '_SESSION'], array($this, 'filterToken'));
		}
	}
	/**
	 * Check the user agent's HTTP REFERER against ICMS_URL
	 *
	 * @param int $docheck 0 to not check the referer (used with XML-RPC), 1 to actively check it
	 *
	 * @return bool
	 **/
	public function checkReferer($docheck = 1) {
		return true; // ALTERED BY FREEFORM SOLUTIONS FOR THE FORMULIZE STANDALONE RELEASE
		$ref = xoops_getenv('HTTP_REFERER');
		if ($docheck == 0) {
			return true;
		}
		if ($ref == '') {
			return false;
		}
		if (strpos($ref, ICMS_URL) !== 0 ) {
			return false;
		}
		return true;
	}

	/**
	 * Check superglobals for contamination
	 *
	 * @return void
	 **/
	public function checkSuperglobals() {
		foreach (array('GLOBALS', '_SESSION', 'HTTP_SESSION_VARS', '_GET', 'HTTP_GET_VARS', '_POST', 'HTTP_POST_VARS', '_COOKIE', 'HTTP_COOKIE_VARS', '_REQUEST', '_SERVER', 'HTTP_SERVER_VARS', '_ENV', 'HTTP_ENV_VARS', '_FILES', 'HTTP_POST_FILES', 'xoopsDB', 'xoopsUser', 'xoopsUserId', 'xoopsUserGroups', 'xoopsUserIsAdmin', 'icmsConfig', 'xoopsOption', 'xoopsModule', 'xoopsModuleConfig', 'xoopsRequestUri') as $bad_global) {
			if (isset($_REQUEST[$bad_global])) {
				header('Location: ' . ICMS_URL);
				exit();
			}
		}
	}

	/**
	 * Check if visitor's IP address is banned
	 * @todo : Should be changed to return bool and let the action be up to the calling script
	 *
	 * @return void
	 **/
	public function checkBadips() {
		global $icmsConfig;
		if ($icmsConfig['enable_badips'] == 1 && isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] != '') {
			foreach ($icmsConfig['bad_ips'] as $bi) {
				if (!empty($bi) && preg_match("/".$bi."/", $_SERVER['REMOTE_ADDR'])) {
					exit();
				}
			}
		}
		unset($bi);
		unset($bad_ips);
		unset($icmsConfig['badips']);
	}

	/**
	 * Get the HTML code for a @link icms_form_elements_Hiddentoken object - used in forms that do not use XoopsForm elements
	 *
	 * @return string
	 **/
	public function getTokenHTML($name = _CORE_TOKEN) {
		$token = new icms_form_elements_Hiddentoken($name);
		return $token->render();
	}

	/**
	 * Add an error
	 *
	 * @param   string  $error
	 **/
	public function setErrors($error) {
		$this->errors[] = trim($error);
	}

	/**
	 * Get generated errors
	 *
	 * @param    bool    $ashtml Format using HTML?
	 *
	 * @return    array|string    Array of array messages OR HTML string
	 */
	public function &getErrors($ashtml = false) {
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

