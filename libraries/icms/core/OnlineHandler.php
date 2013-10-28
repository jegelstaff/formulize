<?php
/**
 * Manage of online users
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Core
 * @version		SVN: $Id: OnlineHandler.php 20105 2010-09-08 15:39:19Z malanciault $
 */

/**
 * A handler for "Who is Online?" information
 *
 * @category	ICMS
 * @package     Core
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */
class icms_core_OnlineHandler {

	/**
	 * Database connection
	 *
	 * @var	object
	 */
	private $db;

	/**
	 * Constructor
	 *
	 * @param	object  &$db    {@link XoopsHandlerFactory}
	 */
	public function __construct(&$db) {
		$this->db =& $db;
	}

	/**
	 * Inserts online information into the database
	 *
	 * @param	int     $uid    UID of the active user
	 * @param	string  $uname  Username
	 * @param	string  $timestamp
	 * @param	string  $module Current module
	 * @param	string  $ip     User's IP adress
	 *
	 * @return	bool    TRUE on success
	 */
	public function write($uid, $uname, $time, $module, $ip) {
		$uid = (int) $uid;
		if ($uid > 0) {
			$sql = "SELECT COUNT(*) FROM " . $this->db->prefix('online')
				. " WHERE online_uid='" . $uid . "'";
		} else {
			$sql = "SELECT COUNT(*) FROM " . $this->db->prefix('online')
				. " WHERE online_uid='" . $uid . "' AND online_ip='" . $ip . "'";
		}
		list($count) = $this->db->fetchRow($this->db->queryF($sql));
		if ($count > 0) {
			$sql = "UPDATE " . $this->db->prefix('online')
				. " SET online_updated='" . $time . "', online_module = '" . $module
				. "' WHERE online_uid = '" . $uid . "'";
			if ($uid == 0) {
				$sql .= " AND online_ip='" . $ip . "'";
			}
		} else {
			$sql = sprintf(
				"INSERT INTO %s (online_uid, online_uname, online_updated, online_ip, online_module)"
				. " VALUES ('%u', %s, '%u', %s, '%u')",
				$this->db->prefix('online'),
				$uid,
				$this->db->quoteString($uname),
				(int) $time,
				$this->db->quoteString($ip),
				(int) $module
			);
		}
		if (!$this->db->queryF($sql)) {
			return false;
		}
		return true;
	}

	/**
	 * Delete online information for a user
	 *
	 * @param	int $uid    UID
	 *
	 * @return	bool    TRUE on success
	 */
	public function destroy($uid) {
		$sql = sprintf("DELETE FROM %s WHERE online_uid = '%u'", $this->db->prefix('online'), (int) ($uid));
		if (!$result = $this->db->queryF($sql)) {
			return false;
		}
		return true;
	}

	/**
	 * Garbage Collection
	 *
	 * Delete all online information that has not been updated for a certain time
	 *
	 * @param	int $expire Expiration time in seconds
	 */
	public function gc($expire) {
		$sql = sprintf("DELETE FROM %s WHERE online_updated < '%u'", $this->db->prefix('online'), time() - (int) ($expire));
		$this->db->queryF($sql);
	}

	/**
	 * Get an array of online information
	 *
	 * @param	object  $criteria   {@link icms_db_criteria_Element}
	 * @return	array   Array of associative arrays of online information
	 */
	public function getAll($criteria = null) {
		$ret = array();
		$limit = $start = 0;
		$sql = 'SELECT * FROM ' . $this->db->prefix('online');
		if (is_object($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' ' . $criteria->renderWhere();
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return false;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			$ret[] = $myrow;
			unset($myrow);
		}
		return $ret;
	}

	/**
	 * Count the number of online users
	 *
	 * @param	object  $criteria   {@link icms_db_criteria_Element}
	 */
	public function getCount($criteria = null) {
		$sql = 'SELECT COUNT(*) FROM ' . $this->db->prefix('online');
		if (is_object($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' ' . $criteria->renderWhere();
		}
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		list($ret) = $this->db->fetchRow($result);
		return $ret;
	}
}

