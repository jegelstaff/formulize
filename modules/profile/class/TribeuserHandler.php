<?php
/**
 * Class responsible for managing profile tribeuser objects
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.4
 * @author		phoenyx
 * @package		profile
 * @version		$Id: TribeuserHandler.php 20428 2010-11-21 12:38:18Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_TribeuserHandler extends icms_ipf_Handler {
	/**
	 * Constructor
	 *
	 * @param icms_db_legacy_Database $db databae object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'tribeuser', 'tribeuser_id', 'tribeuser_id', '', basename(dirname(dirname(__FILE__))));
	}

	/**
	 * Create the criteria that will be used by getTribeusers and getTribeusersCount
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of tribeusers to return
	 * @param int $user_id if specifid, only the tribeusers of this user will be returned
	 * @param int $tribeuser_id ID of a single tribeuser to retrieve
	 * @param int $tribe_id id of tribe
	 * @param str $condition
	 * @param bool $approved
	 * @param bool $accepted
	 * @return icms_db_criteria_Compo $criteria
	 */
	private function getTribeusersCriteria($start = 0, $limit = 0, $user_id = false, $tribeuser_id = false, $tribe_id = false, $condition = '=', $approved = false, $accepted = false) {
		$criteria = new icms_db_criteria_Compo();
		if ($start) $criteria->setStart((int)$start);
		if ($limit) $criteria->setLimit((int)$limit);
		if ($tribeuser_id) $criteria->add(new icms_db_criteria_Item('tribeusers_id', (int)$tribeuser_id));
		if ($tribe_id) {
			if (!is_array($tribe_id)) $tribe_id = array($tribe_id);
			$tribe_id = '('.implode(',', $tribe_id).')';
			$criteria->add(new icms_db_criteria_Item('tribe_id', $tribe_id, 'IN'));
		}
		if ($user_id) $criteria->add(new icms_db_criteria_Item('user_id', (int)$user_id, $condition));
		if ($approved !== false) $criteria->add(new icms_db_criteria_Item('approved', (int)$approved));
		if ($accepted !== false) $criteria->add(new icms_db_criteria_Item('accepted', (int)$accepted));
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
	 * @param str $condition
	 * @param bool $approved
	 * @param bool $accepted
	 * @return array of tribeusers
	 */
	public function getTribeusers($start = 0, $limit = 0, $user_id = false, $tribeusers_id = false, $tribe_id = false, $condition = '=', $approved = false, $accepted = false) {
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
	public function getInvitations($uid) {
		return $this->getTribeusers(0, 0, $uid, false, false, '=', false, 0);
	}

	/**
	 * Get all users for a tribe which want to be approved by the owner
	 *
	 * @param int $tribes_id tribe ID (might be an array)
	 * @return array all users that want to be approved by the owner of the tribe
	 */
	public function getApprovals($tribes_id = false) {
		if (is_array($tribes_id) && count($tribes_id) == 0) return array();
		return $this->getTribeusers(0, 0, false, false, $tribes_id, '=', 0);
	}

	/**
	 * Check wether the current user can submit a new tribeuser or not
	 *
	 * @return bool true if he can false if not
	 */
	public function userCanSubmit() {
		return is_object(icms::$user);
	}

	/**
	 * Retreive the config_id of user
	 *
	 * @param int $tribe_id ID of the tribe
	 * @param int $user_id ID of the user
	 * @return int tribeuser ID
	 */
	public function getTribeuserId($tribe_id, $user_id){
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
	public function getTribeuserCounts($tribe_id){
		return $this->getCount(new icms_db_criteria_Compo(new icms_db_criteria_Item('tribe_id', (int)$tribe_id)));
	}

	/**
	 * beforeInsert event
	 *
	 * Event automatically triggered by IcmsPersistable Framework before the object is inserted
	 *
	 * @param mod_profile_Tribeuser $obj mod_profile_Tribeuser object
	 * @return bool
	 */
	protected function beforeInsert(&$obj) {
		// check if the specified user already is a member of this tribe
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('user_id', $obj->getVar('user_id')));
		$criteria->add(new icms_db_criteria_Item('tribe_id', $obj->getVar('tribe_id')));
		if ($this->getCount($criteria) != 0) {
			$obj->setErrors(_PROFILE_TRIBEUSER_DUPLICATE);
			return false;
		}

		// check if the specified user is the owner of this tribe
		$profile_tribes_handler = icms_getModuleHandler('tribes', basename(dirname(dirname(__FILE__))), 'profile');
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('uid_owner', $obj->getVar('user_id')));
		$criteria->add(new icms_db_criteria_Item('tribes_id', $obj->getVar('tribe_id')));
		if ($profile_tribes_handler->getCount($criteria) > 0) {
			$obj->setErrors(_PROFILE_TRIBEUSER_OWNER);
			return false;
		}

		return true;
	}
}
?>