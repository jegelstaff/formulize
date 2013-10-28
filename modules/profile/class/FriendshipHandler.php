<?php
/**
 * Class responsible for managing profile friendship objects
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.4
 * @author		phoenyx
 * @package		profile
 * @version		$Id: FriendshipHandler.php 20428 2010-11-21 12:38:18Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

define('PROFILE_FRIENDSHIP_STATUS_PENDING', 1);
define('PROFILE_FRIENDSHIP_STATUS_ACCEPTED', 2);
define('PROFILE_FRIENDSHIP_STATUS_REJECTED', 3);

class mod_profile_FriendshipHandler extends icms_ipf_Handler {
	private $_friendship_statusArray = array();

	/**
	 * Constructor
	 *
	 * @param icms_db_legacy_Database $db database connection object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'friendship', 'friendship_id', 'friend1_uid', '', basename(dirname(dirname(__FILE__))));
	}

	/**
	 * Create the criteria that will be used by getFriendships
	 *
	 * @param int $start to which record to start
	 * @param int $limit max friendships to display
	 * @param int $friend1_uid only the friendship of this user will be returned
	 * @param int $friend2_uid if specifid, the friendship of these two users will be returned.
	 * @param int $status only get friendships with the specified status
	 * @return icms_db_criteria_Compo $criteria
	 */
	private function getFriendshipsCriteria($start = 0, $limit = 0, $friend1_uid = 0, $friend2_uid = 0, $status = 0) {
		$criteria = new icms_db_criteria_Compo();
		if ($start) $criteria->setStart((int)$start);
		if ($limit) $criteria->setLimit((int)$limit);
		$criteria->setSort('creation_time');
		$criteria->setOrder('DESC');

		if ($status == PROFILE_FRIENDSHIP_STATUS_PENDING && $friend2_uid) {
			$criteria->add(new icms_db_criteria_Item('status', (int)$status));
			$criteria->add(new icms_db_criteria_Item('friend2_uid', (int)$friend2_uid));
		} else {
			if ($status) $criteria->add(new icms_db_criteria_Item('status', (int)$status));
			if ($friend2_uid > 0) {
				$criteria->add(new icms_db_criteria_Item('friend1_uid', (int)$friend1_uid));
				$criteria->add(new icms_db_criteria_Item('friend2_uid', (int)$friend2_uid));
				$criteria->add(new icms_db_criteria_Item('friend1_uid', (int)$friend2_uid), 'OR');
				$criteria->add(new icms_db_criteria_Item('friend2_uid', (int)$friend1_uid));
			} elseif ($friend1_uid > 0) {
				$criteria->add(new icms_db_criteria_Item('friend1_uid', (int)$friend1_uid));
				$criteria->add(new icms_db_criteria_Item('friend2_uid', (int)$friend1_uid), 'OR');
			}
			if ($status) $criteria->add(new icms_db_criteria_Item('status', (int)$status));
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
	public function getFriendships($start = 0, $limit = 0, $friend1_uid = 0, $friend2_uid = 0, $status = 0) {
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
	public function getFriendshipsSorted($friend1_uid, $isOwner) {
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
	public function getFriendship_statusArray() {
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
		return is_object(icms::$user);
	}
}
?>