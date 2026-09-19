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
	private $tokenDir = XOOPS_ROOT_PATH . '/tokens'; // directory where token files are stored

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
		// ALTERED BY FREEFORM SOLUTIONS FOR FORMULIZE. A token is only worth anything because it is
		// filed under a value that one browser alone holds. With nothing to file it under, the file
		// would be written with an empty key in the middle of its name, and validateToken() would
		// find it again by globbing that same empty key - so any browser at all could redeem it.
		// Refusing here fails closed: the page gets no token and its submission is rejected, rather
		// than the page looking protected while accepting anybody's submission.
		$bindKey = $this->tokenBindKey();
		if ($bindKey === '') {
			icms::$logger->addExtra(_CORE_TOKENVALID, 'No session or bind key to tie a security token to');
			return '';
		}
		$timeout = ($timeout == 0) ? (int) ($GLOBALS['icmsConfig']['session_expire'] * 60) : (int) $timeout; // session_expire is in minutes, we need seconds
		$timeout = time() + $timeout;
		// ALTERED BY FREEFORM SOLUTIONS FOR FORMULIZE. Was hash('sha256', uniqid(rand(), true)).
		// uniqid is the clock, and rand() is not a cryptographic generator, so the id was predictable
		// to anyone who could have one minted at a moment of their own choosing. An embedded screen
		// lets any website on the internet do exactly that, in a visitor's browser, with no session
		// and no login, which is what makes guessing worth attempting at all. Same length and same
		// alphabet as before, so nothing downstream changes and tokens already issued still validate.
		$token_id = bin2hex(random_bytes(32));
		// save token data on the server
		touch($this->tokenDir . '/' . $name . '_' . $bindKey . '_' . $token_id . '_' . $timeout);
		$token = hash('sha256',($token_id.$_SERVER['HTTP_USER_AGENT'].XOOPS_DB_PREFIX));
		return $token;
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
		if (empty($token)) {
			icms::$logger->addExtra(_CORE_TOKENVALID, _CORE_TOKENNOVALID);
			return false;
		}
		$validFound = false;
		$sessionTokenFilesOfType = array();
		foreach($this->tokenBindKeysToCheck() as $bindKey) {
			$sessionTokenFilesOfType = array_merge($sessionTokenFilesOfType, (array) glob($this->tokenDir . '/' . $name . '_' . $bindKey . '_*'));
		}
		foreach($sessionTokenFilesOfType as $tokenPathAndFileName) {
			if($this->tokenExpired($tokenPathAndFileName)) {
				unlink($tokenPathAndFileName);
				$str = _CORE_TOKENEXPIRED;
				$this->setErrors($str);
				icms::$logger->addExtra(_CORE_TOKENVALID, $str);
				continue; // skip expired tokens
			}
			// check if the token is valid
			// isolate the second last part of the file name, which is the token id
			$fileName = basename($tokenPathAndFileName);
			$fileNameParts = explode('_', $fileName);
			$token_id = $fileNameParts[count($fileNameParts) - 2];
			// check if the token matches the expected value
			if ($token === hash('sha256',($token_id.$_SERVER['HTTP_USER_AGENT'].XOOPS_DB_PREFIX))) {
				if ($clearIfValid AND (empty($_SERVER['HTTP_X_REQUESTED_WITH']) OR strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')) {
					// token should be valid once, so clear it once validated -- but ignore ajax requests, reset tokens only on full page loads
					unlink($tokenPathAndFileName);
				}
				icms::$logger->addExtra(_CORE_TOKENVALID, _CORE_TOKENISVALID);
				$validFound = true;
				break; // stop searching for a valid token
			}
		}
		if (!$validFound) {
			icms::$logger->addExtra(_CORE_TOKENVALID, _CORE_TOKENINVALID);
		}
		$this->garbageCollection($name);
		return $validFound;
	}

	/**
	 * The name this request's tokens are filed under. ALTERED BY FREEFORM SOLUTIONS FOR FORMULIZE.
	 *
	 * The session id, except for an anonymous visitor inside somebody else's frame, whose session
	 * cookie never reaches them: there, a cookie kept for the purpose stands in for it. See
	 * formulize_anonTokenBindKey().
	 *
	 * @return string The value to file a new token under
	 **/
	private function tokenBindKey() {
		if ($bindKey = formulize_anonTokenBindKey()) {
			return $bindKey;
		}
		return session_id();
	}

	/**
	 * The names a submitted token may be filed under. ALTERED BY FREEFORM SOLUTIONS FOR FORMULIZE.
	 *
	 * Always the session id. For an anonymous visitor with a bind cookie, that too: a token filed under
	 * the bind cookie can come back on a request that now carries a session cookie as well, and
	 * formulize_anonBindCookieValue() explains when. Logged in visitors are only ever checked against
	 * the session.
	 *
	 * @return array The values to look for token files under
	 **/
	private function tokenBindKeysToCheck() {
		$keys = array();
		// Empty keys are dropped rather than searched under. An empty key globs as tokenname__* and
		// would match the files written when there was nothing to bind to, which any browser could
		// then redeem. With no usable key at all this returns nothing, no file is looked at, and the
		// token is refused - the same fail-closed answer createToken() gives when it cannot bind one.
		if ($sessionId = session_id()) {
			$keys[] = $sessionId;
		}
		if ($bindKey = formulize_anonBindCookieValue()) {
			$keys[] = $bindKey;
		}
		return array_unique($keys);
	}

	/**
	 * Clear all token values from user's session
	 *
	 * @param string $name session name
	 **/
	public function clearTokens($name = _CORE_TOKEN) {
		$sessionTokenFilesOfType = glob($this->tokenDir . '/' . $name . '_' . session_id() . '_*');
		foreach($sessionTokenFilesOfType as $tokenPathAndFileName) {
			unlink($tokenPathAndFileName);
		}
	}

	/**
	 * Perform garbage collection, clearing expired tokens
	 *
	 * @param string $name session name
	 *
	 * @return void
	 **/
	public function garbageCollection($name = _CORE_TOKEN) {
		if(empty($_SERVER['HTTP_X_REQUESTED_WITH']) OR strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
			$tokenFilesOfType = glob($this->tokenDir . '/' . $name . '_*');
			foreach($tokenFilesOfType as $tokenPathAndFileName) {
				if($this->tokenExpired($tokenPathAndFileName)) {
					unlink($tokenPathAndFileName);
				}
			}
		}
	}
	/**
	 * Check if a token file has expired
	 * @param string $tokenPathAndFileName path and file name of the token file to check
	 * @return bool true if the token file has expired, false otherwise. Last part of the file name is the timeout.
	 */
	private function tokenExpired($tokenPathAndFileName) {
		$expired = true;
		$fileName = basename($tokenPathAndFileName);
		// isolate the part of the filename after the last underscore
		$timeout = substr($fileName, strrpos($fileName, '_') + 1);
		if (is_numeric($timeout) AND $timeout > time()) {
			$expired = false;
		}
		return $expired;
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

