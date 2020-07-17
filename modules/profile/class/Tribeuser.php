<?php
/**
 * Classes responsible for managing profile tribeuser objects
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.3
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @package		profile
 * @version		$Id: Tribeuser.php 21843 2011-06-23 14:54:52Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_Tribeuser extends icms_ipf_Object {
	/**
	 * Constructor
	 *
	 * @param mod_profile_TribeuserHandler $handler object handler
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('tribeuser_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('tribe_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('user_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('approved', XOBJ_DTYPE_INT, false, false, false, 1);
		$this->quickInitVar('accepted', XOBJ_DTYPE_INT, false, false, false, 1);

		$this->setControl('tribe_id', array('itemHandler' => 'Tribes', 'method' => 'getAllTribes', 'module' => 'profile'));
		$this->setControl('user_id', 'user');
		$this->setControl('approved', 'yesno');
		$this->setControl('accepted', 'yesno');
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
	 * Check to see wether the current user can edit or delete this tribeuser
	 *
	 * @global bool $profile_isAdmin true if current user is admin for profile module
	 * @return bool true if he can, false if not
	 */
	public function userCanEditAndDelete() {
		global $profile_isAdmin;

		if (!is_object(icms::$user)) return false;
		if ($profile_isAdmin) return true;
		return $this->getVar('user_id', 'e') == icms::$user->getVar('uid');
	}

	/**
	 * get avatar of the tribeuser
	 *
	 * @global array $icmsConfigUser user configuration
	 * @return string tribeuser avatar
	 */
	public function getTribeuserAvatar() {
		global $icmsConfigUser;

		$tribeUserId = $this->getVar('user_id', 'e');
		$thisUser = icms::handler('icms_member')->getUser($tribeUserId);
		if (!is_object($thisUser)) return;
		$avatar = $thisUser->gravatar();
		if (!$icmsConfigUser['avatar_allow_gravatar'] && strpos($avatar, 'http://www.gravatar.com/avatar/') !== false) return false;
		return '<img src="'.$thisUser->gravatar().'" />';
	}

	/**
	 * get linked tribe name
	 *
	 * @return str itemLink of tribe or tribe_id if no tribe was found
	 */
	public function getTribeName() {
		$profile_tribes_handler = icms_getModuleHandler('tribes', basename(dirname(dirname(__FILE__))), 'profile');
		$tribes = $profile_tribes_handler->getTribes(0, 1, false, $this->getVar('tribe_id'));
		if (count($tribes) == 1) return $tribes[$this->getVar('tribe_id')]['itemLink'];
		return $this->getVar('tribe_id');
	}

	/**
	 * get linked tribeuser user name from id
	 *
	 * @return str linked user name
	 */
	public function getTribeuserSender() {
		return icms_member_user_Handler::getUserLink($this->getVar('user_id', 'e'));
	}

	/**
	 * get id of the tribeuser
	 *
	 * @return int tribeuser id
	 */
	public function getTribeuserId() {
		return $this->getVar('tribeuser_id');
	}

	/**
	 * Overridding IcmsPersistable::toArray() method to add a few info
	 *
	 * @return array of tribeuser info
	 */
	public function toArray() {
		$ret = parent::toArray();
		$profile_tribes_handler = icms_getmodulehandler('tribes', basename(dirname(dirname(__FILE__))), 'profile');
		$tribe = $profile_tribes_handler->get($this->getVar('tribe_id'))->toArray();
		$ret['tribe_itemLink'] = $tribe['itemLink'];
		unset($profile_tribes_handler, $tribe);
		$ret['tribeuser_avatar'] = $this->getTribeuserAvatar();
		$ret['editItemLink'] = $this->getEditItemLink(false, true, true);
		$ret['deleteItemLink'] = $this->getDeleteItemLink(false, true, true);
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		$ret['tribeuser_sender_link'] = $this->getTribeuserSender();
		return $ret;
	}
}
?>