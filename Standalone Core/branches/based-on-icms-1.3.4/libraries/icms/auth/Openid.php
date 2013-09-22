<?php
/**
 * Authorization classes, OpenID protocol class file
 *
 * This class handles the authentication of a user with its openid. If the the authenticate openid
 * is not found in the users database, the user will be able to create his account on this site or
 * associate its openid with is already registered account. This process is also taking into
 * consideration $icmsConfigPersonaUser['activation_type'].
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Auth
 * @subpackage	Openid
 * @since		1.1
 * @author		malanciault <marcan@impresscms.org)
 * @credits		Sakimura <http://www.sakimura.org/> Evan Prodromou <http://evan.prodromou.name/>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		SVN: $Id: Openid.php 11202 2011-04-26 16:38:01Z skenow $
 */

define('OPENID_STEP_BACK_FROM_SERVER', 1);
define('OPENID_STEP_REGISTER', 2);
define('OPENID_STEP_LINK', 3);
define('OPENID_STEP_NO_USER_FOUND', 4);
define('OPENID_STEP_USER_FOUND', 5);

/**
 * OpenID authorization class
 *
 * @category	ICMS
 * @package		Auth
 * @subpackage	Openid
 */
class icms_auth_Openid extends icms_auth_Object {

	/**
	 * @var string $displayid $displayid fetch from the openid authentication
	 */
	private $displayid;

	/**
	 * @var string $openid openid used for this authentication
	 */
	private $openid;

	/**
	 * OpenID response
	 *
	 * @var OpenIDResponse object
	 */
	private $response;

	/**
	 * Where are we in the process
	 * Possible options are
	 *   - OPENID_STEP_BACK_FROM_SERVER
	 *   - OPENID_STEP_REGISTER
	 *   - OPENID_STEP_LINK
	 *   - OPENID_STEP_NO_USER_FOUND
	 * @var int
	 */
	private $step = OPENID_STEP_BACK_FROM_SERVER;

	/**
	 * Authentication Service constructor
	 */
	public function __construct(&$dao) {
		parent::__construct($dao);
		$this->auth_method = 'openid';
	}
	
	/**
	 * Overloading method to allow access to private properties outside the class
	 * 
	 * Instead of creating separate methods for each private property, this allows you to 
	 * access (read) the properties and still keep them from being written from the public
	 * scope
	 * 
	 * @param string $name
	 */
	public function &__get($name) {
		return $this->$name;
	}

	/**
	 * Authenticate using the OpenID protocol
	 *
	 * @param bool $debug Turn debug on or not
	 * @return bool successful?
	 */
	public function authenticate($debug = FALSE) {
		// check to see if we already have an OpenID response in SESSION
		if (isset($_SESSION['openid_response'])) {
			if ($debug) icms_core_Debug::message(_CORE_OID_INSESSIONS);
			$this->response = unserialize($_SESSION['openid_response']);
		} else {
			if ($debug) icms_core_Debug::message(_CORE_OID_FETCHING);
			// Complete the authentication process using the server's response.
			$consumer = getConsumer();
			$return_to = getReturnTo();
			//$this->response = $consumer->complete($_GET);
			$this->response = $consumer->complete($return_to);
			$_SESSION['openid_response'] = serialize($this->response);
		}

		if ($this->response->status == Auth_OpenID_CANCEL) {
			if ($debug) icms_core_Debug::message(_CORE_OID_STATCANCEL);

			// This means the authentication was cancelled.
			$this->setErrors('100', _CORE_OID_VERIFCANCEL);
		} elseif ($this->response->status == Auth_OpenID_FAILURE) {
			if ($debug) icms_core_Debug::message(_CORE_OID_SERVERFAILED);

			$this->setErrors('101', _CORE_OID_FAILED . $this->response->message);
			if ($debug) {
				icms_core_Debug::message(_CORE_OID_DUMPREQ);
				icms_core_Debug::vardump($_REQUEST);
			}

			return FALSE;
		} elseif ($this->response->status == Auth_OpenID_SUCCESS) {
			// This means the authentication succeeded.
			$this->displayid = $this->response->getDisplayIdentifier();
			$this->openid = $this->response->identity_url;
			$sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($this->response);
			$sreg = $sreg_resp->contents();
			$_SESSION['openid_sreg'] = $sreg;

			if ($debug) {
				icms_core_Debug::message(_CORE_OID_SERVERSUCCESS);
				icms_core_Debug::message(_CORE_OID_DISPID . $this->displayid);
				icms_core_Debug::message(_CORE_OID_OPENID . $this->openid);
				icms_core_Debug::message(_CORE_OID_DUMPING);
				icms_core_Debug::vardump($sreg);
			}

			$esc_identity = htmlspecialchars($this->openid, ENT_QUOTES);

			$success = sprintf(_CORE_OID_SUCESSFULLYIDENTIFIED, $esc_identity, $this->displayid);

			if ($this->response->endpoint->canonicalID) {
				$success .= sprintf(_CORE_OID_CANONID, $this->response->endpoint->canonicalID);
			}

			/**
			 * Now, where are we in the process, just back from OpenID server or trying to register or
			 * trying to link to an existing account
			 */
			if (isset($_POST['openid_register'])) {
				if ($debug) icms_core_Debug::message(_CORE_OID_STEPIS . 'OPENID_STEP_REGISTER');
				$this->step = OPENID_STEP_REGISTER;
			} elseif (isset($_POST['openid_link'])) {
				if ($debug) icms_core_Debug::message(_CORE_OID_STEPIS . 'OPENID_STEP_LINK');
				$this->step = OPENID_STEP_LINK;
			} elseif (isset($_SESSION['openid_step'])) {
				if ($debug) icms_core_Debug::message(_CORE_OID_STEPIS . $_SESSION['openid_step']);
				$this->step = $_SESSION['openid_step'];
			} else {
				if ($debug) icms_core_Debug::message(_CORE_OID_CHECKINGID);
				// Do we already have a user with this openid
				$member_handler = icms::handler('icms_member');
				$criteria = new icms_db_criteria_Compo();
				$criteria->add(new icms_db_criteria_Item('openid', $this->openid));
				$users =& $member_handler->getUsers($criteria);
				if ($users && count($users) > 0) {
					$this->step = OPENID_STEP_USER_FOUND;
					if ($debug) icms_core_Debug::message(_CORE_OID_FOUNDSTEPIS . 'OPENID_STEP_USER_FOUND');
					return $users[0];
				} else {
					/*
					 * This openid was not found in the users table. Let's ask the user if he wants
					 * to create a new user account on the site or else login with his already registered
					 * account
					 */
					if ($debug) icms_core_Debug::message(_CORE_OID_NOTFOUNDSTEPIS . 'OPENID_STEP_NO_USER_FOUND');
					$this->step = OPENID_STEP_NO_USER_FOUND;
					return FALSE;
				}
			}
		}
	}

	/**
	 * Has an error occurred or not
	 *
	 * @return bool TRUE if number of errors are greater than 0
	 */
	public function errorOccured() {
		return count($this->getErrors()) > 0;
	}
}
