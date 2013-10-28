<?php
/**
 * Class responsible for managing profile configs objects
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.4
 * @author		phoenyx
 * @package		profile
 * @version		$Id: ConfigsHandler.php 20562 2010-12-19 18:26:36Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

// Config status definitions
define('PROFILE_CONFIG_STATUS_EVERYBODY', 1);
define('PROFILE_CONFIG_STATUS_MEMBERS', 2);
define('PROFILE_CONFIG_STATUS_FRIENDS', 3);
define('PROFILE_CONFIG_STATUS_PRIVATE', 4);

class mod_profile_ConfigsHandler extends icms_ipf_Handler {
	private $_config_statusArray = array();

	/**
	 * Constructor
	 *
	 * @param icms_db_legacy_Database $db database connection object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'configs', 'configs_id', '', '', basename(dirname(dirname(__FILE__))));
	}

	/**
	 * Retreive the possible status of a config object
	 *
	 * @return array of status
	 */
	public function getConfig_statusArray() {
		if (!$this->_config_statusArray) {
			$this->_config_statusArray[PROFILE_CONFIG_STATUS_EVERYBODY] = _CO_PROFILE_CONFIG_STATUS_EVERYBODY;
			$this->_config_statusArray[PROFILE_CONFIG_STATUS_MEMBERS] = _CO_PROFILE_CONFIG_STATUS_MEMBERS;
			$this->_config_statusArray[PROFILE_CONFIG_STATUS_FRIENDS] = _CO_PROFILE_CONFIG_STATUS_FRIENDS;
			$this->_config_statusArray[PROFILE_CONFIG_STATUS_PRIVATE] = _CO_PROFILE_CONFIG_STATUS_PRIVATE;
		}
		return $this->_config_statusArray;
	}

	/**
	 * Create the criteria that will be used by getConfigs and getConfigsCount
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of configs to return
	 * @param int $uid_owner if specifid, only the configs of this user will be returned
	 * @param int $configs_id ID of a single config to retrieve
	 * @return icms_db_criteria_Compo $criteria
	 */
	private function getConfigsCriteria($start = 0, $limit = 0, $uid_owner = false, $configs_id = false) {
		$criteria = new icms_db_criteria_Compo();
		if ($start) $criteria->setStart((int)$start);
		if ($limit) $criteria->setLimit((int)$limit);
		if ($uid_owner) $criteria->add(new icms_db_criteria_Item('config_uid', (int)$uid_owner));
		if ($configs_id) $criteria->add(new icms_db_criteria_Item('configs_id', (int)$configs_id));

		return $criteria;
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
	public function getConfigs($start = 0, $limit = 0, $uid_owner = false, $configs_id = false) {
		$criteria = $this->getConfigsCriteria($start, $limit, $uid_owner, $configs_id);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	public function &getConfigPerUser($uid, $new = false) {
		static $buffer = array();

		if (!isset($buffer[$uid])) {
			$configs = $this->getObjects(icms_buildCriteria(array('config_uid' => $uid)));
			if (is_array($configs) && count($configs) > 0 && is_object($configs[0])) {
				$buffer[$uid] = $configs[0];
			} elseif ($new) {
				return $this->get(0);
			}
		}

		return $buffer[$uid];
	}

	/**
	 * check if user is allowed to access a given section
	 *
	 * @param str $item the section (e.g. audio)
	 * @param int $uid user id
	 * @return bool
	 */
	public function userCanAccessSection($item, $uid){
		global $profile_isAdmin;

		if ($profile_isAdmin) return true;
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		if (!$module->config["profile_social"]) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);

		$configsObj = $this->getConfigPerUser($uid);
		if (is_object($configsObj)) {
			$status = $configsObj->getVar($item, 'e');
			if ($profile_isAdmin) return true;
			if (is_object(icms::$user) && icms::$user->getVar('uid') == $uid) return true;
			if ($status == PROFILE_CONFIG_STATUS_EVERYBODY) return true;
			if ($status == PROFILE_CONFIG_STATUS_MEMBERS && is_object(icms::$user)) return true;
			if ($status == PROFILE_CONFIG_STATUS_FRIENDS && is_object(icms::$user) && icms::$user->getVar('uid') != $uid) {
				$profile_friendship_handler = icms_getModuleHandler('friendship', basename(dirname(dirname(__FILE__))), 'profile');
				$friendships = $profile_friendship_handler->getFriendships(0, 1, icms::$user->getVar('uid'), $uid, PROFILE_FRIENDSHIP_STATUS_ACCEPTED);
				return (count($friendships) != 0);
			}
			if ($status == PROFILE_CONFIG_STATUS_PRIVATE && is_object(icms::$user)) return $uid == icms::$user->getVar('uid');
		}
		return false;
	}

	/**
	 * Check wether the current user can submit a new config or not
	 *
	 * @return bool true if he can false if not
	 */
	public function userCanSubmit() {
		return is_object(icms::$user);
	}

	/**
	 * BeforeSave event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before the object is inserted or updated.
	 *
	 * @param object $obj ImbloggingPost object
	 * @return true
	 */
	protected function beforeSave(&$obj) {
		$obj->user_suspended = $obj->getVar('suspension', 'e');
		$obj->user_status = $obj->getVar('status', 'e');
		return true;
	}

	/**
	 * AfterUpdate event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is updated
	 *
	 * @param mod_profile_Configs $obj object
	 * @return bool true
	 */
	protected function afterUpdate(&$obj) {
		$obj->user_suspended = false;
		$obj->user_status = false;
		return true;
	}

	/**
	 * AfterSave event
	 *
	 * Event automatically triggered by IcmsPersistable Framework after the object is inserted or updated
	 *
	 * @param mod_profile_Configs $obj object
	 * @global array $icmsConfig CMS configuration
	 * @return true
	 */
	protected function afterSave(&$obj) {
		global $icmsConfig;
		$uid = $obj->getVar('config_uid');
		$member_handler = icms::handler('icms_member');
		$online_handler = icms::handler('icms_core_Online');
		$processUser = $member_handler->getUser($uid);
		if ($obj->user_suspended == 1 && $obj->user_status == 0) {
			$obj->setVar('status', 1);
			$obj->setVar('backup_password', $processUser->pass());
			$obj->setVar('backup_email', $processUser->email());
			$obj->setVar('backup_sig', $processUser->getVar('user_sig', 'e'));
			$processUser->setVar('pass', substr(md5(time()), 0, 8), true);
			$processUser->setVar('email', $icmsConfig['adminmail']);
			$processUser->setVar('user_sig', '');
			if (!$member_handler->insertUser($processUser)) return $processUser->getHtmlErrors();
			$online_handler->destroy($uid);
			$this->insert($obj);
		} elseif ($obj->user_suspended == 0 &&  $obj->user_status == 1){
			$processUser->setVar('pass', $obj->getVar('backup_password', 'e'), true);
			$processUser->setVar('email', $obj->getVar('backup_email', 'e'));
			$processUser->setVar('user_sig', $obj->getVar('backup_sig', 'e'));
			if (!$member_handler->insertUser($processUser)) return $processUser->getHtmlErrors();
			$online_handler->destroy($uid);
			$obj->setVar('suspension', 0);
			$obj->setVar('status', 0);
			$obj->setVar('backup_password', '');
			$obj->setVar('backup_email', '');
			$obj->setVar('backup_sig', '');
			$this->insert($obj);
		}
		return true;
	}
}
?>