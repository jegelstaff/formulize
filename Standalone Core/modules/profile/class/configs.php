<?php

/**
* Classes responsible for managing profile configs objects
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.3
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @package		profile
* @version		$Id$
*/

if (!defined("ICMS_ROOT_PATH")) die("ICMS root path not defined");

// including the IcmsPersistabelSeoObject
include_once ICMS_ROOT_PATH . '/kernel/icmspersistableobject.php';
include_once(ICMS_ROOT_PATH . '/modules/profile/include/functions.php');

/**
 * Config status definitions
 */
define('PROFILE_CONFIG_STATUS_EVERYBODY', 1);
define('PROFILE_CONFIG_STATUS_MEMBERS', 2);
define('PROFILE_CONFIG_STATUS_FRIENDS', 3);
define('PROFILE_CONFIG_STATUS_PRIVATE', 4);

class ProfileConfigs extends IcmsPersistableObject {

	public $_user_suspended = false;
	public $_user_status = false;

	/**
	 * Constructor
	 *
	 * @param object $handler ProfilePostHandler object
	 */
	public function __construct(& $handler) {
		global $icmsConfig;

		$this->IcmsPersistableObject($handler);

		$this->quickInitVar('configs_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('config_uid', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('pictures', XOBJ_DTYPE_INT, false, false, false, PROFILE_CONFIG_STATUS_MEMBERS);
		$this->quickInitVar('audio', XOBJ_DTYPE_INT, false, false, false, PROFILE_CONFIG_STATUS_MEMBERS);
		$this->quickInitVar('videos', XOBJ_DTYPE_INT, false, false, false, PROFILE_CONFIG_STATUS_MEMBERS);
		$this->quickInitVar('friendship', XOBJ_DTYPE_INT, false, false, false, PROFILE_CONFIG_STATUS_MEMBERS);
		$this->quickInitVar('tribes', XOBJ_DTYPE_INT, false, false, false, PROFILE_CONFIG_STATUS_MEMBERS);
		$this->quickInitVar('profile_contact', XOBJ_DTYPE_INT, false, false, false, PROFILE_CONFIG_STATUS_MEMBERS);
		$this->quickInitVar('profile_general', XOBJ_DTYPE_INT, false, false, false, PROFILE_CONFIG_STATUS_MEMBERS);
		$this->quickInitVar('profile_stats', XOBJ_DTYPE_INT, false, false, false, PROFILE_CONFIG_STATUS_MEMBERS);
		$this->quickInitVar('profile_usercontributions', XOBJ_DTYPE_INT, false, false, false, PROFILE_CONFIG_STATUS_MEMBERS);
		$this->quickInitVar('suspension', XOBJ_DTYPE_INT, false);
		$this->quickInitVar('backup_password', XOBJ_DTYPE_TXTAREA, false);
		$this->quickInitVar('backup_email', XOBJ_DTYPE_TXTBOX, false);
		$this->quickInitVar('backup_sig', XOBJ_DTYPE_TXTAREA, false);
		$this->quickInitVar('end_suspension', XOBJ_DTYPE_LTIME, false);
		$this->quickInitVar('status', XOBJ_DTYPE_TXTBOX, false);

		$this->hideFieldFromForm('status');
		$this->hideFieldFromForm('backup_password');
		$this->hideFieldFromForm('configs_id');
		$this->hideFieldFromForm('backup_email');
		$this->hideFieldFromForm('backup_sig');
		$this->setControl('config_uid', 'user');
		$this->setControl('suspension', 'yesno');
		$this->setControl('pictures', array (
			'itemHandler' => 'configs',
			'method' => 'getConfig_statusArray',
			'module' => 'profile'
		));
		$this->setControl('audio', array (
			'itemHandler' => 'configs',
			'method' => 'getConfig_statusArray',
			'module' => 'profile'
		));
		$this->setControl('videos', array (
			'itemHandler' => 'configs',
			'method' => 'getConfig_statusArray',
			'module' => 'profile'
		));
		$this->setControl('friendship', array (
			'itemHandler' => 'configs',
			'method' => 'getConfig_statusArray',
			'module' => 'profile'
		));
		$this->setControl('tribes', array (
			'itemHandler' => 'configs',
			'method' => 'getConfig_statusArray',
			'module' => 'profile'
		));
		$this->setControl('profile_contact', array (
			'itemHandler' => 'configs',
			'method' => 'getConfig_statusArray',
			'module' => 'profile'
		));
		$this->setControl('profile_general', array (
			'itemHandler' => 'configs',
			'method' => 'getConfig_statusArray',
			'module' => 'profile'
		));
		$this->setControl('profile_stats', array (
			'itemHandler' => 'configs',
			'method' => 'getConfig_statusArray',
			'module' => 'profile'
		));
		$this->setControl('profile_usercontributions', array (
			'itemHandler' => 'configs',
			'method' => 'getConfig_statusArray',
			'module' => 'profile'
		));
	}

	/**
	 * Overriding the IcmsPersistableObject::getVar method to assign a custom method on some
	 * specific fields to handle the value before returning it
	 *
	 * @param str $key key of the field
	 * @param str $format format that is requested
	 * @return mixed value of the field that is requested
	 */
	function getVar($key, $format = 's') {
		if ($format == 's' && in_array($key, array ())) {
			return call_user_func(array ($this,	$key));
		}
		return parent :: getVar($key, $format);
	}
	/**
	 * Check to see wether the current user can edit or delete this config
	 *
	 * @return bool true if he can, false if not
	 */
	function userCanEditAndDelete() {
		global $icmsUser, $profile_isAdmin;
		if (!is_object($icmsUser)) {
			return false;
		}
		if ($profile_isAdmin) {
			return true;
		}
		return $this->getVar('config_uid', 'e') == $icmsUser->uid();
	}

}
class ProfileConfigsHandler extends IcmsPersistableObjectHandler {

	/**
	 * @public array of status
	 */
	public $_config_statusArray = array ();
	/**
	 * Constructor
	 */
	public function __construct(& $db) {
		$this->IcmsPersistableObjectHandler($db, 'configs', 'configs_id', '', '', 'profile');
	}
	/**
	 * Create the criteria that will be used by getConfigs and getConfigsCount
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of configs to return
	 * @param int $uid_owner if specifid, only the configs of this user will be returned
	 * @param int $config_id ID of a single config to retrieve
	 * @return CriteriaCompo $criteria
	 */
	function getConfigsCriteria($start = 0, $limit = 0, $uid_owner = false, $config_id = false) {
		global $icmsUser;

		$criteria = new CriteriaCompo();
		if ($start) {
			$criteria->setStart($start);
		}
		if ($limit) {
			$criteria->setLimit(intval($limit));
		}
		if ($uid_owner) {
			$criteria->add(new Criteria('config_uid', $uid_owner));
		}
		if ($config_id) {
			$criteria->add(new Criteria('configs_id', $config_id));
		}
		return $criteria;
	}

	/**
	 * Get single config object
	 *
	 * @param int $configs_id
	 * @return object ProfileConfig object
	 */
	function getConfig($uid_owner=false, $configs_id=false) {
		$ret = $this->getConfigs(0, 0, $uid_owner, $configs_id);
		return isset($ret[$uid_owner]) ? $ret[$uid_owner] : false;
	}

	/**
	 * Get configs as array, ordered by creation_time DESC
	 *
	 * @param int $start to which record to start
	 * @param int $limit max configs to display
	 * @param int $uid_owner if specifid, only the config of this user will be returned
	 * @param int $configs_id ID of a single config to retrieve
	 * @return array of configs
	 */
	function getConfigs($start = 0, $limit = 0, $uid_owner = false, $configs_id = false) {
		$criteria = $this->getConfigsCriteria($start, $limit, $uid_owner, $configs_id);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	/**
	 * Retreive the possible status of a config object
	 *
	 * @return array of status
	 */
	function getConfig_statusArray() {
		if (!$this->_config_statusArray) {
			$this->_config_statusArray[PROFILE_CONFIG_STATUS_EVERYBODY] = _CO_PROFILE_CONFIG_STATUS_EVERYBODY;
			$this->_config_statusArray[PROFILE_CONFIG_STATUS_MEMBERS] = _CO_PROFILE_CONFIG_STATUS_MEMBERS;
			$this->_config_statusArray[PROFILE_CONFIG_STATUS_FRIENDS] = _CO_PROFILE_CONFIG_STATUS_FRIENDS;
			$this->_config_statusArray[PROFILE_CONFIG_STATUS_PRIVATE] = _CO_PROFILE_CONFIG_STATUS_PRIVATE;
		}
		return $this->_config_statusArray;
	}

	/**
	 * Check wether the current user can access a section or not
	 *
	 * @return bool true if he can false if not
	 */
	function userCanAccessSection(& $obj, $item, $uid=false) {
		global $icmsUser, $profile_isAdmin;
		$status = $obj->getVar($item, 'e');
		$uid = isset($_REQUEST['uid'])?intval($_REQUEST['uid']):0;
		if ($profile_isAdmin) return true;
		if (is_object($icmsUser) && $icmsUser->getVar('uid') == $uid) return true;
		if ($status == PROFILE_CONFIG_STATUS_EVERYBODY) return true;
		if ($status == PROFILE_CONFIG_STATUS_MEMBERS && is_object($icmsUser)) return true;
		if ($status == PROFILE_CONFIG_STATUS_FRIENDS && is_object($icmsUser) && $icmsUser->uid() != $uid){
			$profile_friendship_handler = icms_getModuleHandler('friendship');
			$friendships = $profile_friendship_handler->getFriendships(0, 1, $icmsUser->getVar('uid'), $uid, PROFILE_FRIENDSHIP_STATUS_ACCEPTED);
			if (count($friendships) == 0) {
				return false;
			} else {
				return true;
			}
		}
		if ($status == PROFILE_CONFIG_STATUS_PRIVATE && is_object($icmsUser)) return $uid == $icmsUser->uid();
	}

	/**
	 * Check wether the current user can submit a new config or not
	 *
	 * @return bool true if he can false if not
	 */
	function userCanSubmit() {
		global $icmsUser;
		if (!is_object($icmsUser)) {
			return false;
		}
		return true;
	}

	/**
	 * BeforeSave event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before the object is inserted or updated.
	 *
	 * @param object $obj ImbloggingPost object
	 * @return true
	 */
	function beforeSave(& $obj) {
		$obj->_user_suspended = $obj->getVar('suspension', 'e');
		$obj->_user_status = $obj->getVar('status', 'e');
		return true;
	}


	function afterUpdate(& $obj) {
		$obj->_user_suspended = false;
		$obj->_user_status = false;
		return true;
	}

	/**
	 * AfterSave event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is inserted or updated
	 *
	 * @param object $obj ImbloggingPost object
	 * @return true
	 */
	function afterSave(& $obj) {
		global $icmsConfig;
		$uid = $obj->getVar('config_uid', 'e');
		$member_handler =& xoops_gethandler('member');
		$processUser =& $member_handler->getUser($uid);
		if ($obj->_user_suspended == 1 && $obj->_user_status == 0) {
			$obj->setVar('status', 1);
			$obj->setVar('backup_email', $processUser->email());
			$obj->setVar('backup_password', $processUser->pass());
			$pass = substr ( md5 ( time () ), 0, 8 );
			$processUser->setVar('pass', $pass, true);
			$processUser->setVar('email', $icmsConfig['adminmail']);
			$processUser->setVar('user_sig', '');
			if(!$member_handler->insertUser($processUser)){
				echo $processUser->getHtmlErrors();
			}
			$this->insert($obj);
		}
		if ($obj->_user_suspended == 0 &&  $obj->_user_status == 1){
			$pass = $obj->getVar('backup_password', 'e');
			$email = $obj->getVar('backup_email', 'e');
			$sig = $obj->getVar('backup_sig', 'e');
			$processUser->setVar('pass', $pass, true);
			$processUser->setVar('email', $email);
			$processUser->setVar('user_sig', $sig);
			if(!$member_handler->insertUser($processUser)){
			echo $processUser->getHtmlErrors();
			}
			$obj->setVar('suspension', 0);
			$obj->setVar('status', 0);
			$this->insert($obj);
		}
		return true;
	}
	/**
	 * Retreive the config_id of user
	 *
	 * @return array of amounts
	 */
	function getConfigIdPerUser($uid){
		$configs_id = 0;
		$configs = $this->getConfigs(0, 0, $uid);
		foreach($configs as $key => $config) {
			if ($config['config_uid'] == $uid) {
				$configs_id = $key;
			}
		}
		return $configs_id;
	}

}
?>