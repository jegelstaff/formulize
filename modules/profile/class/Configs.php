<?php
/**
 * Class representing the profile configs object
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.3
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @package		profile
 * @version		$Id: Configs.php 20428 2010-11-21 12:38:18Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_Configs extends icms_ipf_Object {
	public $user_suspended = false;
	public $user_status = false;

	/**
	 * Constructor
	 *
	 * @param object $handler ProfilePostHandler object
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('configs_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('config_uid', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('pictures', XOBJ_DTYPE_INT, false, false, false, PROFILE_CONFIG_STATUS_MEMBERS);
		$this->quickInitVar('audio', XOBJ_DTYPE_INT, false, false, false, PROFILE_CONFIG_STATUS_MEMBERS);
		$this->quickInitVar('videos', XOBJ_DTYPE_INT, false, false, false, PROFILE_CONFIG_STATUS_MEMBERS);
		$this->quickInitVar('friendship', XOBJ_DTYPE_INT, false, false, false, PROFILE_CONFIG_STATUS_MEMBERS);
		$this->quickInitVar('tribes', XOBJ_DTYPE_INT, false, false, false, PROFILE_CONFIG_STATUS_MEMBERS);
		$this->quickInitVar('profile_usercontributions', XOBJ_DTYPE_INT, false, false, false, PROFILE_CONFIG_STATUS_MEMBERS);
		$this->quickInitVar('suspension', XOBJ_DTYPE_INT, false);
		$this->quickInitVar('backup_password', XOBJ_DTYPE_TXTAREA, false);
		$this->quickInitVar('backup_email', XOBJ_DTYPE_TXTBOX, false);
		$this->quickInitVar('backup_sig', XOBJ_DTYPE_TXTAREA, false);
		$this->quickInitVar('end_suspension', XOBJ_DTYPE_LTIME, false);
		$this->quickInitVar('status', XOBJ_DTYPE_TXTBOX, false);

		$this->hideFieldFromForm(array('configs_id', 'config_uid', 'backup_password', 'backup_email', 'backup_sig', 'status'));
		$this->setControl('config_uid', 'user');
		$this->setControl('suspension', 'yesno');
		$this->setControl('pictures', array('itemHandler' => 'configs',	'method' => 'getConfig_statusArray', 'module' => 'profile'));
		$this->setControl('audio', array('itemHandler' => 'configs', 'method' => 'getConfig_statusArray', 'module' => 'profile'));
		$this->setControl('videos', array('itemHandler' => 'configs', 'method' => 'getConfig_statusArray', 'module' => 'profile'));
		$this->setControl('friendship', array('itemHandler' => 'configs', 'method' => 'getConfig_statusArray', 'module' => 'profile'));
		$this->setControl('tribes', array('itemHandler' => 'configs', 'method' => 'getConfig_statusArray', 'module' => 'profile'));
		$this->setControl('profile_usercontributions', array('itemHandler' => 'configs', 'method' => 'getConfig_statusArray', 'module' => 'profile'));
	}

	/**
	 * Overriding the icms_ipf_Object::getVar method to assign a custom method on some
	 * specific fields to handle the value before returning it
	 *
	 * @param str $key key of the field
	 * @param str $format format that is requested
	 * @return mixed value of the field that is requested
	 */
	public function getVar($key, $format = 's') {
		if ($format == 's' && in_array($key, array())) {
			return call_user_func(array($this,	$key));
		}
		return parent::getVar($key, $format);
	}

	/**
	 * Check to see wether the current user can edit or delete this config
	 *
	 * @global bool $profile_isAdmin true if current user is admin for profile module
	 * @return bool true if he can, false if not
	 */
	public function userCanEditAndDelete() {
		global $profile_isAdmin;

		if (!is_object(icms::$user)) return false;
		if ($profile_isAdmin) return true;
		return $this->getVar('config_uid', 'e') == icms::$user->getVar('uid');
	}
}
?>