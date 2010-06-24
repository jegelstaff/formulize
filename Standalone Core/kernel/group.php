<?php
/**
* Manage groups and memberships
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		LICENSE.txt
* @package		core
* @subpackage	member
* @since		XOOPS
* @author		Kazumi Ono (aka onokazo)
* @author		http://www.xoops.org The XOOPS Project
* @version		$Id: group.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

/**
 * a group of users
 *
 * @copyright copyright (c) 2000-2003 XOOPS.org
 * @author Kazumi Ono <onokazu@xoops.org>
 * @package kernel
 */
class XoopsGroup extends XoopsObject
{
	/**
	 * constructor
	 */
	function XoopsGroup()
	{
		$this->XoopsObject();
		$this->initVar('groupid', XOBJ_DTYPE_INT, null, false);
		$this->initVar('name', XOBJ_DTYPE_TXTBOX, null, true, 100);
		$this->initVar('description', XOBJ_DTYPE_TXTAREA, null, false);
		$this->initVar('group_type', XOBJ_DTYPE_OTHER, null, false);
	}
}

/**
 * XOOPS group handler class.
 * This class is responsible for providing data access mechanisms to the data source
 * of XOOPS group class objects.
 *
 * @author Kazumi Ono <onokazu@xoops.org>
 * @copyright copyright (c) 2000-2003 XOOPS.org
 * @package kernel
 * @subpackage member
 */
class XoopsGroupHandler extends XoopsObjectHandler
{

	/**
	 * create a new {@link XoopsGroup} object
	 *
	 * @param bool $isNew mark the new object as "new"?
	 * @return object XoopsGroup {@link XoopsGroup} reference to the new object
	 * @see htdocs/kernel/XoopsObjectHandler#create()
	 */
	function &create($isNew = true)
	{
		$group = new XoopsGroup();
		if ($isNew) {
			$group->setNew();
		}
		return $group;
	}

	/**
	 * retrieve a specific group
	 *
	 * @param int $id ID of the group to get
	 * @return object XoopsGroup {@link XoopsGroup} reference to the group object, FALSE if failed
	 * @see htdocs/kernel/XoopsObjectHandler#get($int_id)
	 */
	function &get($id)
	{
		$id = intval($id);
		$group = false;
		if ($id > 0) {
			$sql = "SELECT * FROM ".$this->db->prefix('groups')." WHERE groupid='".$id."'";
			if (!$result = $this->db->query($sql)) {
				return $group;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$group = new XoopsGroup();
				$group->assignVars($this->db->fetchArray($result));
			}
		}
		return $group;
	}

	/**
	 * insert a group into the database
	 *
	 * @param object reference to the group object
	 * @return mixed ID of the group if inserted, FALSE if failed, TRUE if already present and unchanged.
	 * @see htdocs/kernel/XoopsObjectHandler#insert($object)
	 */
	function insert(&$group)
	{
		/**
		 * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
		 */
		if (!is_a($group, 'xoopsgroup')) {
			return false;
		}
		if (!$group->isDirty()) {
			return true;
		}
		if (!$group->cleanVars()) {
			return false;
		}
		foreach ($group->cleanVars as $k => $v) {
			${$k} = $v;
		}
		if ($group->isNew()) {
			$groupid = $this->db->genId('group_groupid_seq');
			$sql = sprintf("INSERT INTO %s (groupid, name, description, group_type) VALUES ('%u', %s, %s, %s)", $this->db->prefix('groups'), intval($groupid), $this->db->quoteString($name), $this->db->quoteString($description), $this->db->quoteString($group_type));
		} else {
			$sql = sprintf("UPDATE %s SET name = %s, description = %s, group_type = %s WHERE groupid = '%u'", $this->db->prefix('groups'), $this->db->quoteString($name), $this->db->quoteString($description), $this->db->quoteString($group_type), intval($groupid));
		}
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		if (empty($groupid)) {
			$groupid = $this->db->getInsertId();
		}
		$group->assignVar('groupid', $groupid);
		return true;
	}

	/**
	 * remove a group from the database
	 *
	 * @param object $group reference to the group to be removed
	 * @return bool FALSE if failed
	 * @see htdocs/kernel/XoopsObjectHandler#delete($object)
	 */
	function delete(&$group)
	{
		/**
		 * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
		 */
		if (!is_a($group, 'xoopsgroup')) {
			return false;
		}
		$sql = sprintf("DELETE FROM %s WHERE groupid = '%u'", $this->db->prefix('groups'), intval($group->getVar('groupid')));
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		return true;
	}

	/**
	 * retrieve groups from the database
	 *
	 * @param object $criteria {@link CriteriaElement} with conditions for the groups
	 * @param bool $id_as_key should the groups' IDs be used as keys for the associative array?
	 * @return mixed Array of groups
	 */
	function getObjects($criteria = null, $id_as_key = false)
	{
		$ret = array();
		$limit = $start = 0;
		$sql = "SELECT * FROM ".$this->db->prefix('groups');
		if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
			$sql .= " ".$criteria->renderWhere();
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return $ret;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			$group = new XoopsGroup();
			$group->assignVars($myrow);
			if (!$id_as_key) {
				$ret[] =& $group;
			} else {
				$ret[$myrow['groupid']] =& $group;
			}
			unset($group);
		}
		return $ret;
	}
}

/**
 * membership of a user in a group
 *
 * @author Kazumi Ono <onokazu@xoops.org>
 * @copyright copyright (c) 2000-2003 XOOPS.org
 * @package kernel
 * @subpackage	member
 */
class XoopsMembership extends XoopsObject
{
	/**
	 * constructor
	 */
	function XoopsMembership()
	{
		$this->XoopsObject();
		$this->initVar('linkid', XOBJ_DTYPE_INT, null, false);
		$this->initVar('groupid', XOBJ_DTYPE_INT, null, false);
		$this->initVar('uid', XOBJ_DTYPE_INT, null, false);
	}
}

/**
 * XOOPS membership handler class. (Singleton)
 *
 * This class is responsible for providing data access mechanisms to the data source
 * of XOOPS group membership class objects.
 *
 * @author Kazumi Ono <onokazu@xoops.org>
 * @copyright copyright (c) 2000-2003 XOOPS.org
 * @package kernel
 * @subpackage	member
 */
class XoopsMembershipHandler extends XoopsObjectHandler
{
	/**
	 * create a new membership
	 *
	 * @param bool $isNew should the new object be set to "new"?
	 * @return object XoopsMembership {@link XoopsMembership}
	 * @see htdocs/kernel/XoopsObjectHandler#create()
	 */
	function &create($isNew = true)
	{
		$mship = new XoopsMembership();
		if ($isNew) {
			$mship->setNew();
		}
		return $mship;
	}

	/**
	 * retrieve a membership
	 *
	 * @param int $id ID of the membership to get
	 * @return mixed reference to the object if successful, else FALSE
	 * @see htdocs/kernel/XoopsObjectHandler#get($int_id)
	 */
	function &get($id)
	{
		$id = intval($id);
		$mship = false;
		if ($id > 0) {
			$sql = "SELECT * FROM ".$this->db->prefix('groups_users_link')." WHERE linkid='".$id."'";
			if (!$result = $this->db->query($sql)) {
				return $mship;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$mship = new XoopsMembership();
				$mship->assignVars($this->db->fetchArray($result));
			}
		}
		return $mship;
	}

	/**
	 * inserts a membership in the database
	 *
	 * @param object $mship reference to the membership object
	 * @return bool TRUE if already in DB or successful, FALSE if failed
	 * @see htdocs/kernel/XoopsObjectHandler#insert($object)
	 */
	function insert(&$mship)
	{
		/**
		 * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
		 */
		if (!is_a($mship, 'xoopsmembership')) {
			return false;
		}
		if (!$mship->isDirty()) {
			return true;
		}
		if (!$mship->cleanVars()) {
			return false;
		}
		foreach ($mship->cleanVars as $k => $v) {
			${$k} = $v;
		}
		if ($mship->isNew()) {
			$linkid = $this->db->genId('groups_users_link_linkid_seq');
			$sql = sprintf("INSERT INTO %s (linkid, groupid, uid) VALUES ('%u', '%u', '%u')", $this->db->prefix('groups_users_link'), intval($linkid), intval($groupid), intval($uid));
		} else {
			$sql = sprintf("UPDATE %s SET groupid = '%u', uid = '%u' WHERE linkid = '%u'", $this->db->prefix('groups_users_link'), intval($groupid), intval($uid), intval($linkid));
		}
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		if (empty($linkid)) {
			$linkid = $this->db->getInsertId();
		}
		$mship->assignVar('linkid', $linkid);
		return true;
	}

	/**
	 * delete a membership from the database
	 *
	 * @param object $mship reference to the membership object
	 * @return bool FALSE if failed
	 * @see htdocs/kernel/XoopsObjectHandler#delete($object)
	 */
	function delete(&$mship)
	{
		/**
		 * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
		 */
		if (!is_a($mship, 'xoopsmembership')) {
			return false;
		}

		$sql = sprintf("DELETE FROM %s WHERE linkid = '%u'", $this->db->prefix('groups_users_link'), intval($groupm->getVar('linkid')));
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		return true;
	}

	/**
	 * retrieve memberships from the database
	 *
	 * @param object $criteria {@link CriteriaElement} conditions to meet
	 * @param bool $id_as_key should the ID be used as the array's key?
	 * @return array array of references
	 */
	function getObjects($criteria = null, $id_as_key = false)
	{
		$ret = array();
		$limit = $start = 0;
		$sql = "SELECT * FROM ".$this->db->prefix('groups_users_link');
		if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
			$sql .= " ".$criteria->renderWhere();
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return $ret;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			$mship = new XoopsMembership();
			$mship->assignVars($myrow);
			if (!$id_as_key) {
				$ret[] =& $mship;
			} else {
				$ret[$myrow['linkid']] =& $mship;
			}
			unset($mship);
		}
		return $ret;
	}

	/**
	 * count how many memberships meet the conditions
	 *
	 * @param object $criteria {@link CriteriaElement} conditions to meet
	 * @return int
	 */
	function getCount($criteria = null)
	{
		$sql = "SELECT COUNT(*) FROM ".$this->db->prefix('groups_users_link');
		if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
			$sql .= " ".$criteria->renderWhere();
		}
		$result = $this->db->query($sql);
		if (!$result) {
			return 0;
		}
		list($count) = $this->db->fetchRow($result);
		return $count;
	}

	/**
	 * delete all memberships meeting the conditions
	 *
	 * @param object $criteria {@link CriteriaElement} with conditions to meet
	 * @return bool
	 */
	function deleteAll($criteria = null)
	{
		$sql = "DELETE FROM ".$this->db->prefix('groups_users_link');
		if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
			$sql .= " ".$criteria->renderWhere();
		}
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		return true;
	}

	/**
	 * retrieve groups for a user
	 *
	 * @param int $uid ID of the user
	 * @param bool $asobject should the groups be returned as {@link XoopsGroup}
	 * objects? FALSE returns associative array.
	 * @return array array of groups the user belongs to
	 */
	function getGroupsByUser($uid)
	{
		$ret = array();
		$sql = "SELECT groupid FROM ".$this->db->prefix('groups_users_link')." WHERE uid='".intval($uid)."'";
		$result = $this->db->query($sql);
		if (!$result) {
			return $ret;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			$ret[] = $myrow['groupid'];
		}
		return $ret;
	}

	/**
	 * retrieve users belonging to a group
	 *
	 * @param int $groupid ID of the group
	 * @param bool $asobject return users as {@link XoopsUser} objects?
	 * FALSE will return arrays
	 * @param int $limit number of entries to return
	 * @param int $start offset of first entry to return
	 * @return array array of users belonging to the group
	 */
	function getUsersByGroup($groupid, $limit=0, $start=0)
	{
		$ret = array();
		$sql = "SELECT uid FROM ".$this->db->prefix('groups_users_link')." WHERE groupid='".intval($groupid)."'";
		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return $ret;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			$ret[] = $myrow['uid'];
		}
		return $ret;
	}
}
?>