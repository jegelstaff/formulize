<?php
/**
 * Session Management
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		icms_core
 * @subpackage	icms_core_Session
 * @version		SVN: $Id: Session.php 21003 2011-03-10 18:46:15Z m0nty_ $
 */
/*
 Based on SecureSession class
 Written by Vagharshak Tozalakyan <vagh@armdex.com>
 Released under GNU Public License
 */
/**
 * Handler for a session
 * @category	ICMS
 * @package     Session
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */

class icms_core_Session {

	/**
	 * Initialize the session service
	 * @return icms_core_Session
	 */
	static public function service() {
		global $icmsConfig;
		if (file_exists(XOOPS_ROOT_PATH."/integration_api.php"))
			include_once(XOOPS_ROOT_PATH.'/integration_api.php'); // ADDED CODE BY FREEFORM SOLUTIONS
		$instance = new icms_core_Session(icms::$xoopsDB);
		session_set_save_handler(
			array($instance, 'open'), array($instance, 'close'), array($instance, 'read'),
			array($instance, 'write'), array($instance, 'destroy'), array($instance, 'gc')
		);
		$sslpost_name = isset($_POST[$icmsConfig['sslpost_name']]) ? $_POST[$icmsConfig['sslpost_name']] : "";
		$instance->sessionStart($sslpost_name);

		// ADDED CODE BY FREEFORM SOLUTIONS, SUPPORTING INTEGRATION WITH OTHER SYSTEMS
		// If this is a page load by another system, and we're being included, then we establish the user session based on the user id of the user in effect in the other system
		// This approach assumes correspondence between the user ids.
    $externalUid = 0;
    $xoops_userid = 0;

    include_once ICMS_ROOT_PATH . '/include/functions.php';

    // Also listens for a code from Google in the URL
    //if google user logged in and redirected to this page
		if (isset($_GET['code']) AND $client = setupAuthentication()) {
      //Get a google client object and send Client Request for email
      $objOAuthService = new Google_Service_Oauth2($client);

      //Authenticate code from Google OAuth Flow
			if(isset($_GET['code']) && isset($_GET['newcode'])){
				//for the create new user pathway to this session init call
				$userData["email"] = $_SESSION['email'];
				//finally guaranteed to be done with these
				unset($_SESSION['email']);
				unset($_SESSION['name']);
			}else if (isset($_GET['code'])){
				$client->authenticate($_GET['code']);
				$userData = $objOAuthService->userinfo->get();
			}

			// start up the integration API
			include_once XOOPS_ROOT_PATH."/integration_api.php";
			Formulize::init();

			// we need to now try and get an the resource mapping of the user if it exists
			if(isset($userData["email"]) AND $userData["email"] AND $internalUid = Formulize::getXoopsResourceID(Formulize::USER_RESOURCE, $userData["email"])) {
				$externalUid = $userData["email"];
			} elseif(isset($userData["email"]) AND $userData["email"]) {
				// No existing user - going to redirect to the create new user page
				$_SESSION['email'] = $userData["email"];
				$_SESSION['resouceMapKey'] = $userData["email"];
				$_SESSION['name'] = $userData["name"];
				$_SESSION['newuser'] = $_GET['code']; //add the google code to session and url and check this on the other end to make sure that they are equal
				$url = XOOPS_URL."/new_user.php?newuser=".$_GET['code'];
				header("Location: ".$url);
				exit;
			}
    }

		if(isset($_POST['SAMLResponse'])) {
				require_once XOOPS_ROOT_PATH.'/libraries/php-saml/_toolkit_loader.php';
				$auth = new OneLogin_Saml2_Auth();
				$auth->processResponse();
				$errors = $auth->getErrors();
				if (!empty($errors)) {
						// might want to uncomment output if you're debugging
						//echo '<p>', implode(', ', $errors), '</p>';
						//exit();
				}
				if($auth->isAuthenticated()) {
						// start up the integration API
						include_once XOOPS_ROOT_PATH."/integration_api.php";
						Formulize::init();
						if($internalUid = Formulize::getXoopsResourceID(Formulize::USER_RESOURCE, $auth->getNameId())) {
								$externalUid = $auth->getNameId();
						} else {
								// check if there is a group specified in the SAML response?
								$samlGroups = array();
								if($uid = $newFormulizeUser->insertAndMapUser(array_keys($samlGroups))) {
										header("Location: ".XOOPS_URL);
										exit();
								} else {
										// No existing user, no group, need to send to new user page to gather token...
										$_SESSION['resouceMapKey'] = $auth->getNameId();
										$samlAttributes = $auth->getAttributes();
										$_SESSION['name'] = $samlAttributes['firstName'][0].' '.$samlAttributes['lastName'][0]; //<<<<-MUST CONVERT TO SAML ATTRIBUTE FIRST NAME LAST NAME
										$_SESSION['newuser'] = bin2hex(random_bytes(32)); // add a key to the session and URL and check this on the other end to make sure that they are equal
										$url = XOOPS_URL."/new_user.php?newuser=".$_SESSION['newuser'];
										header("Location: ".$url);
										exit;
								}
						}
				}
		}

		// if the email passed by the validated OAuth request, that later passed authentication with Brightspace, matches an existing account, log that person in
		// Brightspace / LTI integration does not use the resource mapping table and the integration API because there may be multiple different integrations with a site, it is not designed to have a single integration with a single Formulize instance.
		if(isset($_SESSION['brightspaceUserId']) AND $_SESSION['brightspaceUserId'] AND isset($_SESSION['ext_d2l_orgdefinedid'])) {
			// lookup user who matches canonical id from brightspace
			include XOOPS_ROOT_PATH.'/libraries/brightspace/finduser.php';
			$xoops_userid = lookupBrightspaceUser($_SESSION['ext_d2l_orgdefinedid']);
			if(!$xoops_userid) {
				$externalUid = 0;
				$cookie_time = time() - 10000;
				$instance->update_cookie(session_id(), $cookie_time);
				$instance->destroy(session_id());
				unset($_SESSION['xoopsUserId']);
			}
		}

		if (isset($GLOBALS['formulizeHostSystemUserId'])) {
			if ($GLOBALS['formulizeHostSystemUserId']) {
				$externalUid = $GLOBALS['formulizeHostSystemUserId'];
			} else {
				$externalUid = 0;
				$cookie_time = time() - 10000;
				$instance->update_cookie(session_id(), $cookie_time);
				$instance->destroy(session_id());
				unset($_SESSION['xoopsUserId']);
			}
		}

		// if we're coming back from new_user.php after having made an account, we need to pick it up here
		if(isset($_SESSION['resouceMapKey']) AND $_SESSION['resouceMapKey']) {
				$externalUid = $_SESSION['resouceMapKey'];
		}

		if ($externalUid) {
      $xoops_userid = Formulize::getXoopsResourceID(Formulize::USER_RESOURCE, $externalUid);
    }

    if($xoops_userid) {
	    $icms_user = icms::handler('icms_member')->getUser($xoops_userid);

			if (is_object($icms_user)) {
				// set a few things in $_SESSION, similar to what include/checklogin.php does, and make a cookie and a database entry
				$_SESSION['xoopsUserId'] = $icms_user->getVar('uid');
				$_SESSION['xoopsUserGroups'] = $icms_user->getGroups();
				$_SESSION['xoopsUserLastLogin'] = $icms_user->getVar('last_login');
				$_SESSION['xoopsUserLanguage'] = $icms_user->language();
				$_SESSION['icms_fprint'] = $instance->createFingerprint();
                unset($_SESSION['resouceMapKey']);

				$xoops_user_theme = $icms_user->getVar('theme');
				if (in_array($xoops_user_theme, $icmsConfig['theme_set_allowed'])) {
					$_SESSION['xoopsUserTheme'] = $xoops_user_theme;
				}

				$instance->write(session_id(), session_encode());
				$icms_session_expiry = ini_get("session.gc_maxlifetime") / 60; // need to use the current maxlifetime setting, which will be coming from Drupal, so the timing of the sessions is synched.
				$cookie_time = time() + (60 * $icms_session_expiry);
				$instance->update_cookie(session_id(), $cookie_time);
			}

			if (function_exists("i18n_get_lang")) { // set icms language to match the currently active Drupal language
				$_GET['lang'] = i18n_get_lang();
			} elseif(function_exists("i18n_langcode")) {
				$_GET['lang'] = i18n_langcode();
			}
		}

		// If there's no xoopsUserId set in the $_SESSION yet, and there's an ICMS session cookie present, then let's make one last attempt to load the session (could be because we're embedded in a system that doesn't have a parallel user table like what is used above)
		// essentially, if session_start failed (which would happen if another system already started it) then we're trying again.
		// Possibly, we should be appending the existing $_SESSION data somehow?? Don't want to clobber session data from host system??
		$icms_session_name = ($icmsConfig['use_mysession'] && $icmsConfig['session_name'] != '') ? $icmsConfig['session_name'] : session_name();
		if (!isset($_SESSION['xoopsUserId']) && isset($_COOKIE[$icms_session_name])) {
			if ($icms_session_data = $instance->read($_COOKIE[$icms_session_name])) {
				session_decode($icms_session_data); // put session data into $_SESSION, including the xoopsUserId if present, same as if session_start had been successful
			}
		}
		// END OF ADDED CODE

		if (!empty($_SESSION['xoopsUserId'])) {
			$icms_user = icms::handler('icms_member')->getUser($_SESSION['xoopsUserId']); // ALTERED BY FREEFORM SOLUTIONS TO AVOID NAMING CONFLICT WITH GLOBAL USER OBJECT FROM EXTERNAL SYSTEMS
			if (!is_object($icms_user)) { // ALTERED BY FREEFORM SOLUTIONS TO AVOID NAMING CONFLICT WITH GLOBAL USER OBJECT FROM EXTERNAL SYSTEMS
				// Regenerate a new session id and destroy old session
				$instance->icms_sessionRegenerateId(true);
				$_SESSION = array();
			} else {
				icms::$user = $icms_user; // ALTERED BY FREEFORM SOLUTIONS TO AVOID NAMING CONFLICT WITH GLOBAL USER OBJECT FROM EXTERNAL SYSTEMS
				if ($icmsConfig['use_mysession'] && $icmsConfig['session_name'] != '') {
					// we need to secure cookie when using SSL
					$secure = substr(ICMS_URL, 0, 5) == 'https' ? 1 : 0;
					$arr_cookie_options = array (
						'expires' => 0,
						'path' => '/',
						'domain' => '',
						'secure' => ($secure ? true : false),
						'httponly' => true,
						'samesite' => 'None' // None || Lax  || Strict
						);
					setcookie($icmsConfig['session_name'], session_id(), $arr_cookie_options);
				}
				$icms_user->setGroups($_SESSION['xoopsUserGroups']); // ALTERED BY FREEFORM SOLUTIONS TO AVOID NAMING CONFLICT WITH GLOBAL USER OBJECT FROM EXTERNAL SYSTEMS
				if (!isset($_SESSION['UserLanguage']) || empty($_SESSION['UserLanguage'])) {
					$_SESSION['UserLanguage'] = $icms_user->getVar('language'); // ALTERED BY FREEFORM SOLUTIONS TO AVOID NAMING CONFLICT WITH GLOBAL USER OBJECT FROM EXTERNAL SYSTEMS
				}
			}
		} else { // set anon session cookie - necessary for preserving state in LTI systems...some browsers set one by default anyway, but it won't be secure and Samesite=None
      $instance->update_cookie();
    }

		include_once XOOPS_ROOT_PATH.'/modules/formulize/include/common.php';
		writeToFormulizeLog(array(
			'formulize_event'=>'session-loaded-for-user',
			'user_id'=>intval($_SESSION['xoopsUserId'])
		));

		return $instance;
	}

	/**
	 * Database connection
	 * @var	object
	 * @access	private
	 */
	private $db;

	private $mainSaltKey = XOOPS_DB_SALT;

	/**
	 * Security checking level
	 * Possible value:
	 *	0 - no check;
	 *	1 - check browser characteristics (HTTP_USER_AGENT);
	 *	2 - check browser and IP A.B;
	 *	3 - check browser and IP A.B.C, recommended;
	 *	4 - check browser and IP A.B.C.D;
	 * @var	int
	 * @access	public
	 */
	public $securityLevel = 3;

	/**
	 * Security checking level for IPv6 Address types
	 * Possible value:
	 *	0 - no check;
	 *	1 - check browser characteristics (HTTP_USER_AGENT);
	 *	2 - check browser and IPv6 aaaa:bbbb;
	 *	3 - check browser and IPv6 aaaa:bbbb:cccc;
	 *	4 - check browser and IPv6 aaaa:bbbb:cccc:dddd;
	 *  5 - check browser and IPv6 aaaa:bbbb:cccc:dddd:eeee;
	 *  6 - check browser and IPv6 aaaa:bbbb:cccc:dddd:eeee:ffff;
	 *  7 - check browser and IPv6 aaaa:bbbb:cccc:dddd:eeee:ffff:gggg; (recommended)
	 *  8 - check browser and IPv6 aaaa:bbbb:cccc:dddd:eeee:ffff:gggg:hhhh;
	 *
	 * @var	int
	 * @access	public
	 */
	public $ipv6securityLevel = 7;

	/**
	 * Enable regenerate_id
	 * @var	bool
	 * @access	public
	 */
	public $enableRegenerateId = false;

	/**
	 * Constructor
	 * @param object $db reference to the {@link XoopsDatabase} object
	 * Do we need this $db reference now we're using icms::$xoopsDB?????
	 *
	 */
	public function __construct(&$db) {
		$this->db =& $db;
	}

	/**
	 * Open a session
	 * @param	string  $save_path
	 * @param	string  $session_name
	 * @return	bool
	 */
	public function open($save_path, $session_name) {
		return true;
	}

	/**
	 * Close a session
	 * @return	bool
	 */
	public function close()	{
		self::gc_force();
		return true;
	}

	/**
	 * Read a session from the database
	 * @param	string  &sess_id    ID of the session
	 * @return	string   Session data
	 */
	public function read($sess_id) {
		return self::readSession($sess_id);
	}

	/**
	 * Inserts a session into the database
	 * @param   string  $sess_id
	 * @param   string  $sess_data
	 * @return  bool
	 **/
	public function write($sess_id, $sess_data) {
		return self::writeSession($sess_id, $sess_data);
	}

	/**
	 * Destroy a session
	 * @param   string  $sess_id
	 * @return  bool
	 **/
	public function destroy($sess_id) {
		return self::destroySession($sess_id);
	}

	/**
	 * Garbage Collector
	 * @param   int $expire Time in seconds until a session expires
	 * @return  bool
	 **/
	public function gc($expire) {
		return self::gcSession($expire);
	}

	/**
	 * Force gc for situations where gc is registered but not executed
	 **/
	public function gc_force() {
		if (rand(1, 100) < 11) {
			$expiration = empty($GLOBALS['icmsConfig']['session_expire'])
						? @ini_get('session.gc_maxlifetime')
						: $GLOBALS['icmsConfig']['session_expire'] * 60;
			$this->gc($expiration);
		}
	}

	/**
	 * Update the current session id with a newly generated one
	 * To be refactored
	 * @param   bool $delete_old_session
	 * @return  bool
	 **/
	public function icms_sessionRegenerateId($regenerate = false) {
		$old_session_id = session_id();
		if ($regenerate) {
			$success = session_regenerate_id(true);
			//			$this->destroy($old_session_id);
		} else {
			$success = session_regenerate_id();
		}
		// Force updating cookie for session cookie is not issued correctly in some IE versions,
		// or not automatically issued prior to PHP 4.3.3 for all browsers
		if ($success) {
			$this->update_cookie();
		}
		return $success;
	}

	/**
	 * Update cookie status for current session
	 * To be refactored
	 * @param   string  $sess_id    session ID
	 * @param   int     $expire     Time in seconds until a session expires
	 * @return  bool
	 **/
	public function update_cookie($sess_id = null, $expire = null) {
		global $icmsConfig;
		$secure = substr(ICMS_URL, 0, 5) == 'https' ? 1 : 0; // we need to secure cookie when using SSL
		$session_name = ($icmsConfig['use_mysession'] && $icmsConfig['session_name'] != '')
				? $icmsConfig['session_name'] : session_name();
		$session_id = empty($sess_id) ? session_id() : $sess_id;
        $arr_cookie_options = array (
            'expires' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => ($secure ? true : false),
            'httponly' => true,
            'samesite' => 'None' // None || Lax  || Strict
            );
        setcookie($session_name, $session_id, $arr_cookie_options);
	}

	/**
	 * Creates a Fingerprint of the current User Session
	 * Fingerprint stored in current $_SESSION['icms_fprint']
	 * To be refactored
	 * @return  string
	 **/
	public function createFingerprint() {
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		$userIP = $_SERVER['REMOTE_ADDR'];

		return self::sessionFingerprint($userIP, $userAgent);
	}

	/**
	 * Compares the Fingerprint stored in $_SESSION['icms_fprint'] by creating a new Fingerprint.
	 * If they match, the Session is valid.
	 * To be refactored
	 * @return  bool
	 **/
	public function checkFingerprint() {
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		$userIP = $_SERVER['REMOTE_ADDR'];
		$sessFprint = self::sessionFingerprint($userIP, $userAgent);

		if ($sessFprint == $_SESSION['icms_fprint']) {
			return true;
		} else {
			return false;
		}
	}

	// Call this when init session.
	public function sessionOpen($regenerate = false) {
		$_SESSION['icms_fprint'] = self::createFingerprint();
		if ($regenerate) {
			self::icms_sessionRegenerateId(true);
		}
	}

	public function removeExpiredCustomSession($sess) {
		global $icmsConfig;
		if ($icmsConfig['use_mysession'] && $icmsConfig['session_name'] != ''
				&& !isset($_COOKIE[$icmsConfig['session_name']]) && !empty($_SESSION[$sess]))
		{
			unset($_SESSION[$sess]);
		}
	}

	/**
	 * Closes the Session & removes Session Cookies for specified User Id
	 * To be refactored
	 * @param   string  $uid    User ID of user to close
	 * @return
	 **/
	public function sessionClose($uid) {
		global $icmsConfig;

		$uid = (int)$uid;
		session_regenerate_id(true);
		$_SESSION = array();
		if ($icmsConfig['use_mysession'] && $icmsConfig['session_name'] != '') {
			setcookie($icmsConfig['session_name'], '', time()- 3600, '/',  '', 0, 0);
		}
		// clear entry from online users table
		if ($uid > 0) {
			$online_handler = icms::handler('icms_core_Online');
			$online_handler->destroy($uid);
		}
		icms_Event::trigger('icms_core_Session', 'sessionClose', $this);
		return true;
	}

	/**
	 * Creates Session ID & Starts the session
	 * removes Expired Custom Sessions after session Start
	 * @param   string  $sslpost_name    sets the session_id as ssl Name defined in preferences (if SSL enabled)
	 * @return
	 **/
	public function sessionStart($sslpost_name = '') {
		global $icmsConfig;

		if ($icmsConfig['use_ssl'] && isset($sslpost_name) && $sslpost_name != '') {
			session_id($sslpost_name);
		} elseif ($icmsConfig['use_mysession'] && $icmsConfig['session_name'] != ''
			&& $icmsConfig['session_expire'] > 0)
		{
			if (isset($_COOKIE[$icmsConfig['session_name']])) {
				session_id($_COOKIE[$icmsConfig['session_name']]);
			}
			if (function_exists('session_cache_expire')) {
				session_cache_expire($icmsConfig['session_expire']);
			}
		}
        if(intval($icmsConfig['session_expire']) > 0) {
            @ini_set('session.gc_maxlifetime', intval($icmsConfig['session_expire']) * 60);
        }

		if ($icmsConfig['use_mysession'] && $icmsConfig['session_name'] != '') {
			session_name($icmsConfig['session_name']);
		} else {
			session_name('ICMSSESSION');
		}

		session_start();

		self::removeExpiredCustomSession('xoopsUserId');
		icms_Event::trigger('icms_core_Session', 'sessionStart', $this);
		return true;
	}

	// Internal function. Returns sha256 from fingerprint.
	private function sessionFingerprint($ip, $userAgent) {
		$securityLevel = (int) $this->securityLevel;
		$ipv6securityLevel = (int) $this->ipv6securityLevel;

		$fingerprint = $this->mainSaltKey;

		if (isset($ip) && icms_core_DataFilter::checkVar($ip, 'ip', 'ipv4')) {
			if ($securityLevel >= 1) {
				$fingerprint .= $userAgent;
			}
			if ($securityLevel >= 2) {
				$num_blocks = abs($securityLevel);
				if ($num_blocks > 4) {
					$num_blocks = 4;
				}
				$blocks = explode('.', $ip);
				for ($i = 0; $i < $num_blocks; $i++) {
					$fingerprint .= $blocks[$i] . '.';
				}
			}
		} elseif (isset($ip) && icms_core_DataFilter::checkVar($ip, 'ip', 'ipv6')) {
			if ($securityLevel >= 1) {
				$fingerprint .= $userAgent;
			}
			if ($securityLevel >= 2) {
				$num_blocks = abs($securityLevel);
				if ($num_blocks > 4) {
					$num_blocks = 4;
				}
				$blocks = explode(':', $ip);
				for ($i = 0; $i < $num_blocks; $i++) {
					$fingerprint .= $blocks[$i] . ':';
				}
			}
		} else {
			icms_core_Debug::message('ERROR (Session Fingerprint): Invalid IP format,
				IP must be a valid IPv4 or IPv6 format', false);
			$fingerprint = '';
			return $fingerprint;
		}
		return hash('sha256', $fingerprint);
	}

	/**
	 * Read a session from the database
	 * @param	string  &sess_id    ID of the session
	 * @return	string   Session data
	 * MODIFIED TO LOCK THE READING OF THE SESSION IF A PRIOR REQUEST FOR SAME USER IS STILL ACTIVE
	 * THIS IS TO PRESERVE THE INTEGRITY OF $_SESSION
	 * IF MULTIPLE REQUESTS (PROBABLY AJAX REQUESTS) ARRIVE CLOSE TOGETHER, ONE COULD START BEFORE THE PREVIOUS IS FINISHED AND SO THEY WOULD BOTH GET THE SAME $_SESSION.
	 * HOWEVER THIS IS BAD IF THE SUBSEQUENT REQUEST DEPENDS ON VALUES WRITTEN TO THE SESSION DATA DURING THE PRIOR REQUEST.
	 * THIS IS ESPECIALLY RELEVANT WITH REGARD TO THE ANTI-CSRF TOKENS WHICH ARE STORED IN THE SESSION
	 * When the session is loaded, sess_updated is set to 1. When session is written back at end of request, current time stamp replaces the 1
	 * If when we load a session, sess_updated is 1, we try again for up to 10 seconds to load it again
	 * If we don't get it after 10 seconds, we go with whatever we have in the DB at that time and write a note to the error log.
	 */
	private function readSession($sess_id) {

		static $cachedSessionIds = array();
		if(isset($cachedSessionIds[$sess_id])) { return $cachedSessionIds[$sess_id]; }

		$ticks = 0;
		$sess_data = '';
		$sess_updated = 0;
		static $sessionLoaded = false; // track within this session, whether we have ever loaded a session
		while($ticks<30) {
			$ticks++;
			$sql = sprintf('SELECT sess_data, sess_ip, sess_updated FROM %s WHERE sess_id = %s', icms::$xoopsDB->prefix('session'), icms::$xoopsDB->quoteString($sess_id));
			if (false != $result = icms::$xoopsDB->query($sql)) {
				if (icms::$xoopsDB->getRowsNum($result) > 0 AND list($sess_data, $sess_ip, $sess_updated) = icms::$xoopsDB->fetchRow($result)) {
					// session data locked, and we haven't already loaded a session in this PHP instantiation, wait 1/3rd of a second and try again
					if($sess_updated==1 AND !$sessionLoaded) {
						usleep(333333);
						continue; // continue while loop -- only circumstance in which we continue loop
					// got the session data, so mark updated time as "1" to indicate a request is in progress, and carry on.
					} else {
						$sessionLoaded = true;
						if($sess_data AND $sess_updated != 1) {
							$sql = sprintf('UPDATE %s SET sess_updated = 1 WHERE sess_id = %s',icms::$xoopsDB->prefix('session'),icms::$xoopsDB->quoteString($sess_id));
							icms::$xoopsDB->queryF($sql);
						}
						break; // session found
					}
				} else { // no row returned from DB, so session does not exist
					break;
				}
			} else { // query not valid
				break;
			}
		}
		// tried for ten seconds, still locked, go with what we got, write an error log note about this
		if($ticks >= 30 AND $sess_updated == 1) {
			$sessionLoaded = true;
			error_log('Formulize Standalone Error: After 10 seconds the session data was still locked by a prior request, so we\'re going with the current state of the session data anyway! URI: '.str_replace("&amp;", "&", htmlSpecialChars(strip_tags($_SERVER['REQUEST_URI']))));
		}
		if($sess_data) {
			// validate the IP and check that it's consistent with the previous
			$pos = 0;
			if ($this->ipv6securityLevel > 1 && icms_core_DataFilter::checkVar($sess_ip, 'ip', 'ipv6')) {
				$pos = 3; // for IPv6 localhost
				if ($_SERVER['REMOTE_ADDR'] != "::1") { // or if not localhost...
						$pos = strpos($sess_ip, ":", $this->ipv6securityLevel - 1);
				}
			} elseif ($this->securityLevel > 1 && icms_core_DataFilter::checkVar($sess_ip, 'ip', 'ipv4')) {
					$pos = strpos($sess_ip, ".", $this->securityLevel - 1);
			}
			if ($pos AND strncmp($sess_ip, $_SERVER['REMOTE_ADDR'], $pos)!=0) { // if not consistent then kill the session
				$sess_data = '';
				$this->destroySession($sess_id);
			}
		}
		$sess_data = !is_string($sess_data) ? '' : $sess_data; // must return a string!
		if($sess_data) { $cachedSessionIds[$sess_id] = $sess_data; }
    return $sess_data;
	}

	/**
	 * Inserts a session into the database
	 * @param   string  $sess_id
	 * @param   string  $sess_data
	 * @return  bool
	 **/
	private function writeSession($sess_id, $sess_data) {
		$sess_id = icms::$xoopsDB->quoteString($sess_id);
		$sess_data = icms::$xoopsDB->quoteString($sess_data);
		$sql = sprintf(
			"UPDATE %s SET sess_updated = '%u', sess_data = %s WHERE sess_id = %s",
			icms::$xoopsDB->prefix('session'), time(), $sess_data, $sess_id
			);
		icms::$xoopsDB->queryF($sql);
		if (!icms::$xoopsDB->getAffectedRows()) {
			$sql = sprintf(
				"INSERT INTO %s (sess_id, sess_updated, sess_ip, sess_data)"
				. " VALUES (%s, '%u', %s, %s)",
				icms::$xoopsDB->prefix('session'),
				$sess_id, time(),
				icms::$xoopsDB->quoteString($_SERVER['REMOTE_ADDR']),
				$sess_data
			);
            if(icms::$xoopsDB->queryF($sql)) { return true; } else { return false; }
		}
		return true;
	}

	/**
	 * Destroy a session stored in DB
	 * @param   string  $sess_id
	 * @return  bool
	 **/
	private function destroySession($sess_id) {
		$sql = sprintf(
			'DELETE FROM %s WHERE sess_id = %s',
			icms::$xoopsDB->prefix('session'), icms::$xoopsDB->quoteString($sess_id)
		);
		if (!$result = icms::$xoopsDB->queryF($sql)) {
			return false;
		}
		return true;
	}

	/**
	 * Garbage Collector
	 * @param   int $expire Time in seconds until a session expires
	 * @return  bool
	 **/
	private function gcSession($expire) {
		if (empty($expire)) {
			return true;
		}
		$mintime = time() - (int) $expire;
		$sql = sprintf("DELETE FROM %s WHERE sess_updated < '%u'", icms::$xoopsDB->prefix('session'), $mintime);
		if(icms::$xoopsDB->queryF($sql)) { return true; } else { return false; }
	}
}
