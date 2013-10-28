<?php
/**
 * Classes responsible for managing profile visitors objects
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.4
 * @author		phoenyx
 * @package		profile
 * @version		$Id: VisitorsHandler.php 20428 2010-11-21 12:38:18Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_VisitorsHandler extends icms_ipf_Handler {
	/**
	 * Constructor
	 *
	 * @param icms_db_legacy_Database $db database object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'visitors', 'visitors_id', '', '', basename(dirname(dirname(__FILE__))));
	}

	/**
	 * Create the criteria that will be used by getVisitors
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of tribes to return
	 * @param int $uid_owner if specifid, only the tribes of this user will be returned
	 * @param int $uid_visitor if specified, only the records with the specified user as a visitor will be returned
	 * @param int $visit_time if specified, only records with a visit time greater than the specified on will be returned
	 * @return icms_db_criteria_Compo $criteria
	 */
	private function getVisitorsCriteria($start = 0, $limit = 0, $uid_owner = false, $uid_visitor = false, $visit_time = false) {
		$criteria = new icms_db_criteria_Compo();
		if ($start) $criteria->setStart((int)$start);
		if ($limit) $criteria->setLimit((int)$limit);
		$criteria->setSort('visit_time');
		$criteria->setOrder('DESC');
		if ($uid_owner) $criteria->add(new icms_db_criteria_Item('uid_owner', (int)$uid_owner));
		if ($uid_visitor) $criteria->add(new icms_db_criteria_Item('uid_visitor', (int)$uid_visitor));
		if ($visit_time) $criteria->add(new icms_db_criteria_Item('visit_time', $visit_time, '>='));
		return $criteria;
	}

	/**
	 * Get visitors as array, ordered by visit_time DESC
	 *
	 * @param int $start to which record to start
	 * @param int $limit max tribes to display
	 * @param int $uid_owner if specifid, only the visitors of this user will be returned
	 * @param int $uid_visitor if specified, only the records with the specified user as a visitor will be returned
	 * @param int $visit_time if specified, only records with a visit time greater than the specified on will be returned
	 * @return array of visitors
	 */
	public function getVisitors($start = 0, $limit = 0, $uid_owner = false, $uid_visitor = false, $visit_time = false) {
		$criteria = $this->getVisitorsCriteria($start, $limit, $uid_owner, $uid_visitor, $visit_time);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	/**
	 * Insert log entry for visit in case last visit wasn't today
	 *
	 * @param int $uid_owner current user profile id
	 * @return bool
	 */
	public function logVisitor($uid_owner) {
		if (!is_object(icms::$user)) return true;

		$timestamp = mktime(0, 0, 1, date('n'), date('j'), date('Y'));
		$visitors = $this->getVisitors(false, false, $uid_owner, icms::$user->getVar('uid'), $timestamp);

		if (count($visitors) == 0 && icms::$user->getVar('uid') != $uid_owner) {
			$newVisitor = $this->get(0);
			$newVisitor->setVar('uid_owner', $uid_owner);
			$newVisitor->setVar('uid_visitor', icms::$user->getVar('uid'));
			$newVisitor->setVar('visit_time', date(_DATESTRING));
			return $newVisitor->store(true);
		} else {
			return false;
		}
	}
}
?>