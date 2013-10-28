<?php
/**
 * Management of members
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Member
 * @author		modified by UnderDog <underdog@impresscms.org>
 * @version		SVN: $Id: Handler.php 21105 2011-03-19 00:39:26Z m0nty_ $
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * Member handler class.
 * This class provides simple interface (a facade class) for handling groups/users/
 * membership data.
 *
 *
 * @author  Kazumi Ono <onokazu@xoops.org>
 * @category	ICMS
 * @package		Member
 */
class icms_member_Handler {

	/**#@+
	 * holds reference to group handler(DAO) class
	 * @access private
	 */
	private $_gHandler;

	/**
	 * holds reference to user handler(DAO) class
	 */
	private $_uHandler;

	/**
	 * holds reference to membership handler(DAO) class
	 */
	private $_mHandler;

	/**
	 * holds temporary user objects
	 */
	private $_members = array();
	/**#@-*/

	protected $db;

	/**
	 * constructor
	 *
	 */
	public function __construct(&$db) {
		$this->_gHandler = new icms_member_group_Handler($db);
		$this->_uHandler = new icms_member_user_Handler($db);
		$this->_mHandler = new icms_member_group_membership_Handler($db);
		$this->db = &$db;
	}

	/**
	 * create a new group
	 *
	 * @return object icms_member_group_Object {@link icms_member_group_Object} reference to the new group
	 */
	public function &createGroup(&$isNew = TRUE) {
		$inst = & $this->_gHandler->create();
		return $inst;
	}

	/**
	 * create a new user
	 *
	 * @return object icms_member_user_Object {@link icms_member_user_Object} reference to the new user
	 */
	public function &createUser(&$isNew = TRUE) {
		$inst = & $this->_uHandler->create();
		return $inst;
	}

	/**
	 * retrieve a group
	 *
	 * @param int $id ID for the group
	 * @return object icms_member_group_Object {@link icms_member_group_Object} reference to the group
	 */
	public function getGroup($id) {
		return $this->_gHandler->get($id);
	}

	/**
	 * retrieve a user
	 *
	 * @param int $id ID for the user
	 * @return object icms_member_user_Object {@link icms_member_user_Object} reference to the user
	 */
	public function &getUser($id) {
		if (! isset($this->_members[$id])) {
			$this->_members[$id] = & $this->_uHandler->get($id);
		}
		return $this->_members[$id];
	}

	/**
	 * delete a group
	 *
	 * @param object $group {@link icms_member_group_Object} reference to the group to delete
	 * @return bool FALSE if failed
	 */
	public function deleteGroup(&$group) {
		$this->_gHandler->delete($group);
		$this->_mHandler->deleteAll(new icms_db_criteria_Item('groupid', $group->getVar('groupid')));
		return true;
	}

	/**
	 * delete a user
	 *
	 * @param object $user {@link icms_member_user_Object} reference to the user to delete
	 * @return bool FALSE if failed
	 */
	public function deleteUser(&$user) {
		$this->_uHandler->delete($user);
		$this->_mHandler->deleteAll(new icms_db_criteria_Item('uid', $user->getVar('uid')));
		return true;
	}

	/**
	 * insert a group into the database
	 *
	 * @param object $group {@link icms_member_group_Object} reference to the group to insert
	 * @return bool TRUE if already in database and unchanged
	 * FALSE on failure
	 */
	public function insertGroup(&$group) {
		return $this->_gHandler->insert($group);
	}

	/**
	 * insert a user into the database
	 *
	 * @param object $user {@link icms_member_user_Object} reference to the user to insert
	 * @return bool TRUE if already in database and unchanged
	 * FALSE on failure
	 */
	public function insertUser(&$user, $force = false) {
		return $this->_uHandler->insert($user, $force);
	}

	/**
	 * retrieve groups from the database
	 *
	 * @param object $criteria {@link icms_db_criteria_Element}
	 * @param bool $id_as_key use the group's ID as key for the array?
	 * @return array array of {@link icms_member_group_Object} objects
	 */
	public function getGroups($criteria = null, $id_as_key = false) {
		return $this->_gHandler->getObjects($criteria, $id_as_key);
	}

	/**
	 * retrieve users from the database
	 *
	 * @param object $criteria {@link icms_db_criteria_Element}
	 * @param bool $id_as_key use the group's ID as key for the array?
	 * @return array array of {@link icms_member_user_Object} objects
	 */
	public function getUsers($criteria = null, $id_as_key = false) {
		return $this->_uHandler->getObjects($criteria, $id_as_key);
	}

	/**
	 * get a list of groupnames and their IDs
	 *
	 * @param object $criteria {@link icms_db_criteria_Element} object
	 * @return array associative array of group-IDs and names
	 */
	public function getGroupList($criteria = null) {
		$groups = $this->_gHandler->getObjects($criteria, true);
		$ret = array();
		foreach (array_keys($groups) as $i) {
			$ret[$i] = $groups[$i]->getVar('name');
		}
		return $ret;
	}

	/**
	 * get a list of usernames and their IDs
	 *
	 * @param object $criteria {@link icms_db_criteria_Element} object
	 * @return array associative array of user-IDs and names
	 */
	public function getUserList($criteria = null) {
		$users = $this->_uHandler->getObjects($criteria, true);
		$ret = array();
		foreach (array_keys($users) as $i) {
			$ret[$i] = $users[$i]->getVar('uname');
		}
		return $ret;
	}

	/**
	 * add a user to a group
	 *
	 * @param int $group_id ID of the group
	 * @param int $user_id ID of the user
	 * @return object icms_member_group_membership_Object {@link icms_member_group_membership_Object}
	 */
	public function addUserToGroup($group_id, $user_id) {
		$mship =& $this->_mHandler->create();
		$mship->setVar('groupid', $group_id);
		$mship->setVar('uid', $user_id);
		return $this->_mHandler->insert($mship);
	}

	/**
	 * remove a list of users from a group
	 *
	 * @param int $group_id ID of the group
	 * @param array $user_ids array of user-IDs
	 * @return bool success?
	 */
	public function removeUsersFromGroup($group_id, $user_ids = array()) {
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('groupid', $group_id));
		$criteria2 = new icms_db_criteria_Compo();
		foreach ($user_ids as $uid) {
			$criteria2->add(new icms_db_criteria_Item('uid', $uid), 'OR');
		}
		$criteria->add($criteria2);
		return $this->_mHandler->deleteAll($criteria);
	}

	/**
	 * get a list of users belonging to a group
	 *
	 * @param int $group_id ID of the group
	 * @param bool $asobject return the users as objects?
	 * @param int $limit number of users to return
	 * @param int $start index of the first user to return
	 * @return array Array of {@link icms_member_user_Object} objects (if $asobject is TRUE)
	 * or of associative arrays matching the record structure in the database.
	 */
	public function getUsersByGroup($group_id, $asobject = false, $limit = 0, $start = 0) {
		$user_ids = $this->_mHandler->getUsersByGroup($group_id, $limit, $start);
		if (! $asobject) {
			return $user_ids;
		} else {
			$ret = array();
			foreach ($user_ids as $u_id) {
				$user =& $this->getUser($u_id);
				if (is_object($user)) {
					$ret[] =& $user;
				}
				unset($user);
			}
			return $ret;
		}
	}

	/**
	 * get a list of groups that a user is member of
	 *
	 * @param int $user_id ID of the user
	 * @param bool $asobject return groups as {@link icms_member_group_Object} objects or arrays?
	 * @return array array of objects or arrays
	 */
	public function getGroupsByUser($user_id, $asobject = false) {
		$group_ids = $this->_mHandler->getGroupsByUser($user_id);
		if (! $asobject) {
			return $group_ids;
		} else {
			foreach ($group_ids as $g_id) {
				$ret[] =& $this->getGroup($g_id);
			}
			return $ret;
		}
	}

	public function icms_getLoginFromUserEmail($email = '') {
		$table = new icms_db_legacy_updater_Table('users');

		if ($email !== '') {
			if ($table->fieldExists('loginname')) {
				$sql = icms::$xoopsDB->query("SELECT loginname, email FROM " . icms::$xoopsDB->prefix('users')
					. " WHERE email = '" . @htmlspecialchars($email, ENT_QUOTES, _CHARSET) . "'");
			} elseif ($table->fieldExists('login_name')) {
				$sql = icms::$xoopsDB->query("SELECT login_name, email FROM " . icms::$xoopsDB->prefix('users')
					 . " WHERE email = '" . @htmlspecialchars($email, ENT_QUOTES, _CHARSET) . "'");
			}
			list($uname, $email) = icms::$xoopsDB->fetchRow($sql);
		} else {
			redirect_header('user.php', 2, _US_SORRYNOTFOUND);
		}
		return $uname;
	}

	/**
	 * log in a user
	 * @param string $uname username as entered in the login form
	 * @param string $pwd password entered in the login form
	 * @return object icms_member_user_Object {@link icms_member_user_Object} reference to the logged in user. FALSE if failed to log in
	 */
	public function loginUser($uname, $pwd) {

		$icmspass = new icms_core_Password();

		if (strstr($uname, '@')) {
			$uname = self::icms_getLoginFromUserEmail($uname);
		}

		$is_expired = $icmspass->passExpired($uname);
		if ($is_expired == 1) {
			redirect_header(ICMS_URL . '/user.php?op=resetpass&uname=' . $uname, 5, _US_PASSEXPIRED, false);
		}
		$salt = $icmspass->getUserSalt($uname);
		$enc_type = $icmspass->getUserEncType($uname);
		$pwd = $icmspass->encryptPass($pwd, $salt, $enc_type);
		
		$table = new icms_db_legacy_updater_Table('users');
		if ($table->fieldExists('loginname')) {
			$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('loginname', $uname));
		} elseif ($table->fieldExists('login_name')) {
			$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('login_name', $uname));
		} else {
			$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('uname', $uname));
		}
		$criteria->add(new icms_db_criteria_Item('pass', $pwd));
		$user = $this->_uHandler->getObjects($criteria, false);
		if (!$user || count($user) != 1) {
			$user = false;
			return $user;
		}
		return $user[0];
	}

	/**
	 * logs in a user with an md5 encrypted password
	 *
	 * @param string $uname username
	 * @param string $md5pwd password encrypted with md5
	 * @return object icms_member_user_Object {@link icms_member_user_Object} reference to the logged in user. FALSE if failed to log in
	 */
	/*	function &loginUserMd5($uname, $md5pwd) {
	 $table = new icms_db_legacy_updater_Table('users');
	 if ($table->fieldExists('loginname')) {
	 $criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('loginname', $uname));
	 } elseif ($table->fieldExists('login_name')) {
	 $criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('login_name', $uname));
	 } else {
	 $criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('uname', $uname));
	 }
	 $criteria->add(new icms_db_criteria_Item('pass', $md5pwd));
	 $user = $this->_uHandler->getObjects($criteria, false);
	 if (! $user || count($user) != 1) {
	 $user = false;
	 return $user;
	 }
	 return $user [0];
	 } */

	/**
	 * count users matching certain conditions
	 *
	 * @param object $criteria {@link icms_db_criteria_Element} object
	 * @return int
	 */
	public function getUserCount($criteria = null) {
		return $this->_uHandler->getCount($criteria);
	}

	/**
	 * count users belonging to a group
	 *
	 * @param int $group_id ID of the group
	 * @return int
	 */
	public function getUserCountByGroup($group_id) {
		return $this->_mHandler->getCount(new icms_db_criteria_Item('groupid', $group_id));
	}

	/**
	 * updates a single field in a users record
	 *
	 * @param object $user {@link icms_member_user_Object} reference to the {@link icms_member_user_Object} object
	 * @param string $fieldName name of the field to update
	 * @param string $fieldValue updated value for the field
	 * @return bool TRUE if success or unchanged, FALSE on failure
	 */
	public function updateUserByField(&$user, $fieldName, $fieldValue) {
		$user->setVar($fieldName, $fieldValue);
		return $this->insertUser($user);
	}

	/**
	 * updates a single field in a users record
	 *
	 * @param string $fieldName name of the field to update
	 * @param string $fieldValue updated value for the field
	 * @param object $criteria {@link icms_db_criteria_Element} object
	 * @return bool TRUE if success or unchanged, FALSE on failure
	 */
	public function updateUsersByField($fieldName, $fieldValue, $criteria = null) {
		return $this->_uHandler->updateAll($fieldName, $fieldValue, $criteria);
	}

	/**
	 * activate a user
	 *
	 * @param object $user {@link icms_member_user_Object} reference to the object
	 * @return bool successful?
	 */
	public function activateUser(&$user) {
		if ($user->getVar('level') != 0) {
			return true;
		}
		$user->setVar('level', 1);
		return $this->_uHandler->insert($user, true);
	}

	/**
	 * Get a list of users belonging to certain groups and matching criteria
	 * Temporary solution
	 *
	 * @param int $groups IDs of groups
	 * @param object $criteria {@link icms_db_criteria_Element} object
	 * @param bool $asobject return the users as objects?
	 * @param bool $id_as_key use the UID as key for the array if $asobject is TRUE
	 * @return array Array of {@link icms_member_user_Object} objects (if $asobject is TRUE)
	 * or of associative arrays matching the record structure in the database.
	 */
	public function getUsersByGroupLink($groups, $criteria = null, $asobject = false, $id_as_key = false) {
		$ret = array();

		$select = $asobject ? "u.*" : "u.uid";
		$sql[] = "	SELECT DISTINCT {$select} "
				. "	FROM " . icms::$xoopsDB->prefix("users") . " AS u"
				. " LEFT JOIN " . icms::$xoopsDB->prefix("groups_users_link") . " AS m ON m.uid = u.uid"
				. "	WHERE 1 = '1'";
		if (! empty($groups)) {
			$sql[] = "m.groupid IN (" . implode(", ", $groups) . ")";
		}
		$limit = $start = 0;
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql_criteria = $criteria->render();
			if ($criteria->getSort() != '') {
				$sql_criteria .= ' ORDER BY ' . $criteria->getSort() . ' ' . $criteria->getOrder();
			}
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
			if ($sql_criteria) {
				$sql[] = $sql_criteria;
			}
		}
		$sql_string = implode(" AND ", array_filter($sql));
		if (! $result =icms::$xoopsDB->query($sql_string, $limit, $start)) {
			return $ret;
		}
		while ($myrow = icms::$xoopsDB->fetchArray($result)) {
			if ($asobject) {
				$user = new icms_member_user_Object();
				$user->assignVars($myrow);
				if (! $id_as_key) {
					$ret[] =& $user;
				} else {
					$ret[$myrow['uid']] =& $user;
				}
				unset($user);
			} else {
				$ret[] = $myrow['uid'];
			}
		}
		return $ret;
	}

	/**
	 * Get count of users belonging to certain groups and matching criteria
	 * Temporary solution
	 *
	 * @param array $groups IDs of groups
	 * @return int count of users
	 */
	public function getUserCountByGroupLink($groups, $criteria = null) {
		$ret = 0;

		$sql[] = "	SELECT COUNT(DISTINCT u.uid) "
				. "	FROM " . icms::$xoopsDB->prefix("users") . " AS u"
				. " LEFT JOIN " . icms::$xoopsDB->prefix("groups_users_link") . " AS m ON m.uid = u.uid"
				. "	WHERE 1 = '1'";
		if (! empty($groups)) {
			$sql[] = "m.groupid IN (" . implode(", ", $groups) . ")";
		}
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql[] = $criteria->render();
		}
		$sql_string = implode(" AND ", array_filter($sql));
		if (! $result = icms::$xoopsDB->query($sql_string)) {
			return $ret;
		}
		list($ret) = icms::$xoopsDB->fetchRow($result);
		return $ret;
	}

	/**
	 * Gets the usergroup with the most rights for a specific userid
	 *
	 * @param int  $uid  the userid to get the usergroup for
	 *
	 * @return int  the best usergroup belonging to the userid
	 */
	public function getUserBestGroup($uid) {
		$ret = ICMS_GROUP_ANONYMOUS;
		$uid = (int) $uid;
		$gperms = array();
		if ($uid <= 0) {
			return $ret;
		}

		$groups = $this->getGroupsByUser($uid);
		if (in_array(ICMS_GROUP_ADMIN, $groups)) {
			$ret = ICMS_GROUP_ADMIN;
		} else {
			foreach ($groups as $group) {
				$sql = 'SELECT COUNT(gperm_id) as total FROM '
					. icms::$xoopsDB->prefix("group_permission")
					. ' WHERE gperm_groupid=' . $group;
				if (! $result = icms::$xoopsDB->query($sql)) {
					return $ret;
				}
				list($t) = icms::$xoopsDB->fetchRow($result);
				$gperms[$group] = $t;
			}
			foreach ($gperms as $key => $val) {
				if ($val == max($gperms)) {
					$ret = $key;
					break;
				}
			}
		}

		return $ret;
	}
}

