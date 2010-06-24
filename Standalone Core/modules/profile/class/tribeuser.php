<?php

/**
* Classes responsible for managing profile tribeuser objects
*
* @copyright	GNU General Public License (GPL)
* @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since	1.3
* @author	Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @package	profile
* @version	$Id$
*/

if (!defined("ICMS_ROOT_PATH")) die("ICMS root path not defined");

// including the IcmsPersistabelSeoObject
include_once ICMS_ROOT_PATH . '/kernel/icmspersistableobject.php';
include_once(ICMS_ROOT_PATH . '/modules/profile/include/functions.php');

class ProfileTribeuser extends IcmsPersistableObject {

	/**
	 * Constructor
	 *
	 * @param object $handler ProfilePostHandler object
	 */
	public function __construct(& $handler) {
		global $icmsConfig;

		$this->IcmsPersistableObject($handler);

		$this->quickInitVar('tribeuser_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('tribe_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('user_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('approved', XOBJ_DTYPE_INT, false, false, false, 1);
		$this->quickInitVar('accepted', XOBJ_DTYPE_INT, false, false, false, 1);

		$this->setControl('tribe_id', array('itemHandler' => 'Tribes', 'method' => 'getAllTribes', 'module' => 'Profile'));
		$this->setControl('user_id', 'user');
		$this->setControl('approved', 'yesno');
		$this->setControl('accepted', 'yesno');
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
	 * Check to see wether the current user can edit or delete this tribeuser
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
		return $this->getVar('user_id', 'e') == $icmsUser->uid();
	}

	/**
	 * get avatar of the tribeuser
	 *
	 * @global array $icmsConfigUser user configuration
	 * @return string tribeuser avatar
	 */
	function getTribeuserAvatar() {
		global $icmsConfigUser;

		$tribeUserId = $this->getVar('user_id', 'e');
		$member_handler =& xoops_gethandler('member');
		$thisUser =& $member_handler->getUser($tribeUserId);
		$avatar = $thisUser->gravatar();
		if (!$icmsConfigUser['avatar_allow_gravatar'] && strpos($avatar, 'http://www.gravatar.com/avatar/') !== false) return false;
		return '<img src="'.$thisUser->gravatar().'" />';
	}

	/**
	 * get linked tribe name
	 *
	 * @return mixed itemLink of tribe or tribe_id if no tribe was found
	 */
	function getTribeName() {
		$profile_tribes_handler = icms_getModuleHandler('tribes');
		$tribes = $profile_tribes_handler->getTribes(0, 1, false, $this->getVar('tribe_id'));
		if (count($tribes) == 1) return $tribes[$this->getVar('tribe_id')]['itemLink'];
		return $this->getVar('tribe_id');
	}

	/**
	 * get linked tribeuser user name from id
	 *
	 * @return string linked user name
	 */
	function getTribeuserSender() {
		return icms_getLinkedUnameFromId($this->getVar('user_id', 'e'));
	}

	/**
	 * get id of the tribeuser
	 *
	 * @return int tribeuser id
	 */
	function getTribeuserId() {
		return $this->getVar('tribeuser_id');
	}

	/**
	 * Overridding IcmsPersistable::toArray() method to add a few info
	 *
	 * @return array of tribeuser info
	 */
	function toArray() {
		$ret = parent :: toArray();

		$profile_tribes_handler = icms_getmodulehandler('tribes');
		$tribe = $profile_tribes_handler->get($this->getVar('tribe_id'))->toArray();
		$ret['tribe_itemLink'] = $tribe['itemLink'];
		unset($profile_tribes_handler, $tribe);
		$ret['tribeuser_avatar'] = $this->getTribeuserAvatar();
		$ret['editItemLink'] = $this->getEditItemLink(false, true, true);
		$ret['deleteItemLink'] = $this->getDeleteItemLink(false, true, true);
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		$ret['tribeuser_senderid'] = $this->getVar('user_id','e');
		$ret['tribeuser_sender_link'] = $this->getTribeuserSender();
		return $ret;
	}
}
class ProfileTribeuserHandler extends IcmsPersistableObjectHandler {

	/**
	 * Constructor
	 */
	public function __construct(& $db) {
		$this->IcmsPersistableObjectHandler($db, 'tribeuser', 'tribeuser_id', 'tribeuser_id', '', 'profile');
	}

	/**
	 * Create the criteria that will be used by getTribeusers and getTribeusersCount
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of tribeusers to return
	 * @param int $user_id if specifid, only the tribeusers of this user will be returned
	 * @param int $tribeuser_id ID of a single tribeuser to retrieve
	 * @return CriteriaCompo $criteria
	 */
	function getTribeusersCriteria($start = 0, $limit = 0, $user_id = false, $tribeuser_id = false, $tribe_id = false, $condition = '=', $approved = false, $accepted = false) {
		global $icmsUser;

		$criteria = new CriteriaCompo();
		if ($start) {
			$criteria->setStart($start);
		}
		if ($limit) {
			$criteria->setLimit(intval($limit));
		}

		if ($tribeuser_id) {
			$criteria->add(new Criteria('tribeusers_id', $tribeuser_id));
		}
		if ($tribe_id) {
			if (!is_array($tribe_id)) $tribe_id = array($tribe_id);
			$tribe_id = '('.implode(',', $tribe_id).')';
			$criteria->add(new Criteria('tribe_id', $tribe_id, 'IN'));
		}
		if ($user_id) {
			$criteria->add(new Criteria('user_id', $user_id, $condition));
		}
		if ($approved !== false) {
			$criteria->add(new Criteria('approved', $approved));
		}
		if ($accepted !== false) {
			$criteria->add(new Criteria('accepted', $accepted));
		}
		return $criteria;
	}

	/**
	 * Get tribeusers as array, ordered by creation_time DESC
	 *
	 * @param int $start to which record to start
	 * @param int $limit max tribeusers to display
	 * @param int $user_id if specifid, only the tribeuser of this user will be returned
	 * @param int $tribeusers_id ID of a single tribeuser to retrieve
	 * @param int $tribe_id ID of the tribe
	 * @return array of tribeusers
	 */
	function getTribeusers($start = 0, $limit = 0, $user_id = false, $tribeusers_id = false, $tribe_id = false, $condition = '=', $approved = false, $accepted = false) {
		$criteria = $this->getTribeusersCriteria($start, $limit, $user_id, $tribeusers_id, $tribe_id, $condition, $approved, $accepted);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	/**
	 * Get all tribeuser objects where the user has not yet accepted the invitation
	 *
	 * @param int $uid user ID
	 * @return array all tribes where this user has not yet accepted the invitation
	 */
	function getInvitations($uid) {
		return $this->getTribeusers(0, 0, $uid, false, false, '=', false, 0);
	}

	/**
	 * Get all users for a tribe which want to be approved by the owner
	 *
	 * @param int $tribes_id tribe ID (might be an array)
	 * @return array all users that want to be approved by the owner of the tribe
	 */
	function getApprovals($tribes_id = false) {
		return $this->getTribeusers(0, 0, false, false, $tribes_id, '=', 0);
	}
	
	/**
	 * Check wether the current user can submit a new tribeuser or not
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
	 * Retreive the config_id of user
	 *
	 * @param int $tribe_id ID of the tribe
	 * @param int $user_id ID of the user
	 * @return int tribeuser ID
	 */
	function getTribeuserId($tribe_id, $user_id){
		$tribeuser = $this->getTribeusers(0, 1, $user_id, false, $tribe_id);
		if (count($tribeuser) == 0) return false;
		$keys = array_keys($tribeuser);
		return $keys[0];
	}

	/**
	 * Retreive the number of each item submitted by user in each section
	 *
	 * @return array of amounts
	 */
	function getTribeuserCounts($tribe_id){
		$sql = 'SELECT COUNT(*) FROM '.$this->table.' WHERE tribe_id="'.$tribe_id.'"';
		$result = $this->db->query($sql, false);
		list($ret) = $this->db->fetchRow($result);
		return ($ret);
	}

	/**
	 * insert a new object in the database
	 *
	 * @param	object	$obj reference to the object
	 * @param	bool	$force whether to force the query execution despite security settings
	 * @param	bool	$checkObject check if the object is dirty and clean the attributes
	 * @param	bool	$debug debug switch
	 * @return	bool FALSE if failed, TRUE if already present and unchanged or successful
	 */
	function insert(&$obj, $force = false, $checkObject = true, $debug=false) {
		if ($obj->isNew()) {
			// check if the specified user already is a member of this tribe
			$tribeUsers = $this->getTribeusers(0, 0, $obj->getVar('user_id'), false, $obj->getVar('tribe_id'));
			if (count($tribeUsers) != 0) {
				$obj->setErrors(_PROFILE_TRIBEUSER_DUPLICATE);
				return false;
			}

			// check if the specified user is the owner of this tribe
			$profile_tribes_handler = icms_getModuleHandler('tribes');
			$tribe = $profile_tribes_handler->getTribe($obj->getVar('tribe_id'), $obj->getVar('user_id'));
			if ($tribe != false) {
				$obj->setErrors(_PROFILE_TRIBEUSER_OWNER);
				return false;
			}
		}

		return parent::insert($obj, $force, $checkObject, $debug);
	}

	/**
	 * insert a new object in the database and output debug message
	 *
	 * @param	object	$obj reference to the object
	 * @param	bool	$force whether to force the query execution despite security settings
	 * @param	bool	$checkObject check if the object is dirty and clean the attributes
	 * @param	bool	$debug debug switch
	 * @return	bool FALSE if failed, TRUE if already present and unchanged or successful
	 */
	function insertD(&$obj, $force = false, $checkObject = true, $debug=false) {
		return $this->insert($obj, $force, $checkObject, true);
	}
}
?>