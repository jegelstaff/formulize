<?php

/**
* Classes responsible for managing profile visitors objects
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
include_once(ICMS_ROOT_PATH . '/modules/profile/include/functions.php');

class ProfileVisitors extends IcmsPersistableObject {

	/**
	 * Constructor
	 *
	 * @param object $handler ProfilePostHandler object
	 */
	public function __construct(& $handler) {
		global $icmsConfig;

		$this->IcmsPersistableObject($handler);

		$this->quickInitVar('visitors_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('uid_owner', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('uid_visitor', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('visit_time', XOBJ_DTYPE_LTIME, false);

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
}
class ProfileVisitorsHandler extends IcmsPersistableObjectHandler {

	/**
	 * Constructor
	 */
	public function __construct(& $db) {
		$this->IcmsPersistableObjectHandler($db, 'visitors', 'visitors_id', '', '', 'profile');
	}

	/**
	 * Create the criteria that will be used by getVisitors
	 *
	 * @param int $start to which record to start
	 * @param int $limit limit of tribes to return
	 * @param int $uid_owner if specifid, only the tribes of this user will be returned
	 * @param int $uid_visitor if specified, only the records with the specified user as a visitor will be returned
	 * @param int $visit_time if specified, only records with a visit time greater than the specified on will be returned
	 * @return CriteriaCompo $criteria
	 */
	function getVisitorsCriteria($start = 0, $limit = 0, $uid_owner = false, $uid_visitor = false, $visit_time = false) {
		global $icmsUser;

		$criteria = new CriteriaCompo();
		if ($start) {
			$criteria->setStart($start);
		}
		if ($limit) {
			$criteria->setLimit(intval($limit));
		}
		$criteria->setSort('visit_time');
		$criteria->setOrder('DESC');

		if ($uid_owner) {
			$criteria->add(new Criteria('uid_owner', $uid_owner));
		}
		if ($uid_visitor) {
			$criteria->add(new Criteria('uid_visitor', $uid_visitor));
		}
		if ($visit_time) {
			$criteria->add(new Criteria('visit_time', $visit_time, '>='));
		}
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
	function getVisitors($start = 0, $limit = 0, $uid_owner = false, $uid_visitor = false, $visit_time = false) {
		$criteria = $this->getVisitorsCriteria($start, $limit, $uid_owner, $uid_visitor);
		$ret = $this->getObjects($criteria, true, false);
		return $ret;
	}

	/**
	 * Insert log entry for visit in case last visit wasn't today
	 *
	 * @global object $icmsUser current user object
	 * @param int $uid_owner current user profile id
	 */
	function logVisitor($uid_owner) {
		global $icmsUser;

		if (!is_object($icmsUser)) return true;

		$timestamp = mktime(0, 0, 1, date('n'), date('j'), date('Y'));
		$visitors = $this->getVisitors(false, false, $uid_owner, $icmsUser->getVar('uid'), $timestamp);

		if (count($visitors) == 0 && $icmsUser->getVar('uid') != $uid_owner) {
			$newVisitor = new ProfileVisitors($this);
			$newVisitor->setNew();
			$newVisitor->setVar('uid_owner', $uid_owner);
			$newVisitor->setVar('uid_visitor', $icmsUser->getVar('uid'));
			$newVisitor->setVar('visit_time', time());
			$this->insert($newVisitor, true);
		}
	}
}
?>