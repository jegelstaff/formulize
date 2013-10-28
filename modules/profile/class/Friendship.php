<?php
/**
* Class representing the profile friendship object
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.3
* @author		Jan Pedersen, Marcello Brandao, Sina Asghari, Gustavo Pilla <contact@impresscms.org>
* @package		profile
* @version		$Id: Friendship.php 21843 2011-06-23 14:54:52Z phoenyx $
*/

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_Friendship extends icms_ipf_Object {
	/**
	 * Constructor
	 *
	 * @param mod_profile_FriendshipHandler $handler object handler
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('friendship_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('friend1_uid', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('friend2_uid', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('creation_time', XOBJ_DTYPE_LTIME, false);
		$this->quickInitVar('status', XOBJ_DTYPE_INT, true, false, false, PROFILE_FRIENDSHIP_STATUS_PENDING);
		
		$this->setControl('friend1_uid', 'user');
		$this->setControl('status', array('itemHandler' => 'friendship', 'method' => 'getFriendship_statusArray', 'module' => 'profile'));

		$this->hideFieldFromForm('friend2_uid');
		$this->hideFieldFromForm('creation_time');
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
	 * get linked user name for this friend
	 *
	 * @return string linked user name
	 */
	public function getFriendLinkedUname() {
		return icms_member_user_Handler::getUserLink($this->getFriendUid());
	}

	/**
	 * get user name of the friend
	 *
	 * @return str user name
	 */
	public function getFriendUname() {
		return icms_member_user_Object::getUnameFromId($this->getFriendUid());
	}

	/**
	 * get user id for current friend
	 *
	 * @return int user id for current friend
	 */
	private function getFriendUid() {
		$uid = isset($_REQUEST['uid']) ? (int)$_REQUEST['uid'] : icms::$user->getVar('uid');
		$friend_uid = ($uid == $this->getVar('friend2_uid')) ? $this->getVar('friend1_uid') : $this->getVar('friend2_uid');
		return $friend_uid;
	}

	/**
	 * get avatar for friend
	 *
	 * @global array $icmsConfigUser user configuration
	 * @return str html code to display avatar
	 */
	public function getAvatar() {
		global $icmsConfigUser;
		$thisUser = icms::handler('icms_member')->getUser($this->getFriendUid());
		if (!is_object($thisUser)) return;
		$avatar = $thisUser->gravatar();
		if (!$icmsConfigUser['avatar_allow_gravatar'] && strpos($avatar, 'http://www.gravatar.com/avatar/') !== false) return false;
		return '<img src="'.$avatar.'" />';
	}

	/**
	 * Check to see wether the current user can edit or delete this friendship
	 *
	 * @global bool $profile_isAdmin true if current user is admin for profile module
	 * @return bool true if he can, false if not
	 */
	public function userCanEditAndDelete() {
		global $profile_isAdmin;
		
		if ($profile_isAdmin) return true;
		if (!is_object(icms::$user)) return false;
		if ($this->getVar('status') == PROFILE_FRIENDSHIP_STATUS_ACCEPTED) {
			return $this->getVar('friend1_uid', 'e') == icms::$user->getVar('uid') || $this->getVar('friend2_uid', 'e') == icms::$user->getVar('uid');
		} else {
			return $this->getVar('friend2_uid', 'e') == icms::$user->getVar('uid');
		}

		return false;
	}

	/**
	 * Overridding IcmsPersistable::toArray() method to add a few info
	 *
	 * @return array of friendship info
	 */
	public function toArray() {
		$ret = parent::toArray();
		$ret['creation_time'] = formatTimestamp($this->getVar('creation_time', 'e'), 'm');
		$ret['friendship_avatar'] = $this->getAvatar();
		$ret['friendship_linkedUname'] = $this->getFriendLinkedUname();
		$ret['friendship_uname'] = $this->getFriendUname();
		$ret['friend_uid'] = $this->getFriendUid();
		$ret['editItemLink'] = $this->getEditItemLink(false, true, true);
		$ret['deleteItemLink'] = $this->getDeleteItemLink(false, true, true);
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		return $ret;
	}
}
?>