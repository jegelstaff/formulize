<?php

/**
* Classes responsible for managing profile friendship objects
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.3
* @author		Jan Pedersen, Marcello Brandao, Sina Asghari, Gustavo Pilla <contact@impresscms.org>
* @package		profile
* @version		$Id$
*/

if (!defined("ICMS_ROOT_PATH")) die("ICMS root path not defined");

// including the IcmsPersistabelSeoObject
include_once ICMS_ROOT_PATH . '/kernel/icmspersistableobject.php';
include_once ICMS_ROOT_PATH . '/modules/profile/include/functions.php';

define('PROFILE_FRIENDSHIP_STATUS_PENDING', 1);
define('PROFILE_FRIENDSHIP_STATUS_ACCEPTED', 2);
define('PROFILE_FRIENDSHIP_STATUS_REJECTED', 3);

class ProfileFriendship extends IcmsPersistableObject {

	/**
	 * Constructor
	 *
	 * @param object $handler ProfilePostHandler object
	 */
	public function __construct(& $handler) {
		global $icmsConfig;

		$this->IcmsPersistableObject($handler);

		$this->quickInitVar('friendship_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('friend1_uid', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('friend2_uid', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('creation_time', XOBJ_DTYPE_LTIME, false);
		$this->quickInitVar('status', XOBJ_DTYPE_INT, true, false, false, PROFILE_FRIENDSHIP_STATUS_PENDING);
		
		$this->setControl('friend1_uid', 'user');
		$this->setControl('status', array(
				'itemHandler' => 'friendship',
				'method' => 'getFriendship_statusArray',
				'module' => 'profile'
		));

		$this->hideFieldFromForm('friend2_uid');
		$this->hideFieldFromForm('creation_time');
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
	 * get linked user name for this friend
	 *
	 * @global object $icmsUser current user object
	 * @return string linked user name
	 */
	function getFriendLinkedUname() {
		global $icmsUser;
		$uid = isset($_REQUEST['uid'])?intval($_REQUEST['uid']):$icmsUser->uid();
		$friend_uid = ($uid==$this->getVar('friend2_uid')) ? $this->getVar('friend1_uid') : $this->getVar('friend2_uid');
		return icms_getLinkedUnameFromId($friend_uid);
	}

	/**
	 * get user name of the friend
	 *
	 * @global object $icmsUser user obect
	 * @return string user name
	 */
	function getFriendUname() {
		global $icmsUser;
		$uid = isset($_REQUEST['uid'])?intval($_REQUEST['uid']):$icmsUser->uid();
		$friend_uid = ($uid==$this->getVar('friend2_uid')) ? $this->getVar('friend1_uid') : $this->getVar('friend2_uid');
		return XoopsUser::getUnameFromId($friend_uid);
	}

	/**
	 * get user id for current friend
	 *
	 * @global object $icmsUser current user object
	 * @return int user id for current friend
	 */
	function getFriendUid() {
		global $icmsUser;
		$uid = isset($_REQUEST['uid'])?intval($_REQUEST['uid']):$icmsUser->uid();
		$friend_uid = ($uid==$this->getVar('friend2_uid')) ? $this->getVar('friend1_uid') : $this->getVar('friend2_uid');
		return $friend_uid;
	}

	function getAvatar() {
		global $icmsUser, $icmsConfigUser;
		$uid = isset($_REQUEST['uid'])?intval($_REQUEST['uid']):$icmsUser->uid();
		$friend = ($uid==$this->getVar('friend2_uid')) ? $this->getVar('friend1_uid') : $this->getVar('friend2_uid');
		$member_handler =& xoops_gethandler('member');
		$thisUser =& $member_handler->getUser($friend);
		$avatar = $thisUser->gravatar();
		if (!$icmsConfigUser['avatar_allow_gravatar'] && strpos($avatar, 'http://www.gravatar.com/avatar/') !== false) return false;
		return '<img src="'.$thisUser->gravatar().'" />';
	}

	/**
	 * Check to see wether the current user can edit or delete this friendship
	 *
	 * @return bool true if he can, false if not
	 */
	function userCanEditAndDelete() {
		global $icmsUser, $profile_isAdmin;
		
		if ($profile_isAdmin) return true;
		if (!is_object($icmsUser)) return false;
		if ($this->getVar('status') == PROFILE_FRIENDSHIP_STATUS_ACCEPTED) {
			return $this->getVar('friend1_uid', 'e') == $icmsUser->getVar('uid') || $this->getVar('friend2_uid', 'e') == $icmsUser->getVar('uid');
		} else {
			return $this->getVar('friend2_uid', 'e') == $icmsUser->getVar('uid');
		}

		return false;
	}

	/**
	 * Overridding IcmsPersistable::toArray() method to add a few info
	 *
	 * @return array of friendship info
	 */
	function toArray() {
		$ret = parent :: toArray();
		$ret['creation_time'] = formatTimestamp($this->getVar('creation_time', 'e'), 'm');
		$ret['friendship_avatar'] = $this->getAvatar();
		$ret['friendship_linkedUname'] = $this->getFriendLinkedUname();
		$ret['friendship_uname'] = $this->getFriendUname();
		$ret['friend_uid'] = $this->getFriendUid();
		$ret['editItemLink'] = $this->getEditItemLink(false, true, true);
		$ret['deleteItemLink'] = $this->getDeleteItemLink(false, true, true);
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		$ret['friendship_senderid'] = $this->getVar('friend1_uid','e');
		return $ret;
	}
}
class ProfileFriendshipHandler extends IcmsPersistableObjectHandler {
	/**
	 * @public array of status
	 */
	public $_friendship_statusArray = array ();

	/**
	 * Constructor
	 */
	public function __construct(&$db) {
		$this->IcmsPersistableObjectHandler($db, 'friendship', 'friendship_id', 'friend1_uid', '', 'profile');
	}

	/**
	 * Create the criteria that will be used by getFriendships
	 *
	 * @param int $start to which record to start
	 * @param int $limit max friendships to display
	 * @param int $friend1_uid only the friendship of this user will be returned
	 * @param int $friend2_uid if specifid, the friendship of these two users will be returned.
	 * @param int $status only get friendships with the specified status
	 * @return CriteriaCompo $criteria
	 */
	function getFriendshipsCriteria($start = 0, $limit = 0, $friend1_uid = 0, $friend2_uid = 0, $status = 0) {
		$criteria = new CriteriaCompo();
		if ($start) $criteria->setStart($start);
		if ($limit) $criteria->setLimit(intval($limit));
		$criteria->setSort('creation_time');
		$criteria->setOrder('DESC');

		if ($status == PROFILE_FRIENDSHIP_STATUS_PENDING && $friend2_uid) {
			$criteria->add(new Criteria('status', $status));
			$criteria->add(new Criteria('friend2_uid', $friend2_uid));
		} else {
			if ($status) $criteria->add(new Criteria('status', $status));
			if ($friend2_uid > 0) {
				$criteria->add(new Criteria('friend1_uid', $friend1_uid));
				$criteria->add(new Criteria('friend2_uid', $friend2_uid));
				$criteria->add(new Criteria('friend1_uid', $friend2_uid), 'OR');
				$criteria->add(new Criteria('friend2_uid', $friend1_uid));
			} elseif ($friend1_uid > 0) {
				$criteria->add(new Criteria('friend1_uid', $friend1_uid));
				$criteria->add(new Criteria('friend2_uid', $friend1_uid), 'OR');
			}
			if ($status) $criteria->add(new Criteria('status', $status));
		}

		return $criteria;
	}

	/**
	 * Get friendships as array, ordered by creation_time DESC
	 *
	 * @param int $start to which record to start
	 * @param int $limit max friendships to display
	 * @param int $friend1_uid only the friendship of this user will be returned
	 * @param int $friend2_uid if specifid, the friendship of these two users will be returned.
	 * @param int $status only get friendships with the specified status
	 * @return array of friendships
	 */
	function getFriendships($start = 0, $limit = 0, $friend1_uid = 0, $friend2_uid = 0, $status = 0) {
		$criteria = $this->getFriendshipsCriteria($start, $limit, $friend1_uid, $friend2_uid, $status);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	/**
	 * Get friendships and sort them according to their status
	 *
	 * @param int $friend1_uid user id to get friendships for
	 * @param boolean $isOwner true if the user is on it's own profile
	 * @return array of friendships sorted by status
	 */
	function getFriendshipsSorted($friend1_uid, $isOwner) {
		$friendshipsArray = $this->getFriendships(false, false, $friend1_uid);
		$ret = array();
		$ret[PROFILE_FRIENDSHIP_STATUS_PENDING] = array();
		$ret[PROFILE_FRIENDSHIP_STATUS_ACCEPTED] = array();
		$ret[PROFILE_FRIENDSHIP_STATUS_REJECTED] = array();
		foreach ($friendshipsArray as $key => $friendship) {
			if ($friendship['status'] == PROFILE_FRIENDSHIP_STATUS_ACCEPTED || (($friendship['status'] == PROFILE_FRIENDSHIP_STATUS_PENDING || $friendship['status'] == PROFILE_FRIENDSHIP_STATUS_REJECTED) && $friendship['friend2_uid'] == $friend1_uid && $isOwner)) {
				$ret[$friendship['status']][$key] = $friendship;
			}
		}
		return $ret;
	}

	/**
	 * Retreive the possible status of a friendship object
	 *
	 * @return array of status
	 */
	function getFriendship_statusArray() {
		if (!$this->_friendship_statusArray) {
			$this->_friendship_statusArray[PROFILE_FRIENDSHIP_STATUS_PENDING] = _CO_PROFILE_FRIENDSHIP_STATUS_PENDING;
			$this->_friendship_statusArray[PROFILE_FRIENDSHIP_STATUS_ACCEPTED] = _CO_PROFILE_FRIENDSHIP_STATUS_ACCEPTED;
			$this->_friendship_statusArray[PROFILE_FRIENDSHIP_STATUS_REJECTED] = _CO_PROFILE_FRIENDSHIP_STATUS_REJECTED;
		}
		return $this->_friendship_statusArray;
	
	}
	
	/**
	 * Check wether the current user can submit a new friendship or not
	 *
	 * @return bool true if he can false if not
	 */
	function userCanSubmit() {
		global $icmsUser;
		if (!is_object($icmsUser)) return false;

		return true;
	}
}
?>