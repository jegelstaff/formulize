<?php
/**
 * Core class for managing comments
 *
 * @package     core
 * @subpackage	comment
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright 	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @since		XOOPS
 * @version		$Id: comment.php 9824 2010-02-01 20:54:51Z m0nty $
 */

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

/**
 * A Comment
 *
 * @package     kernel
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsComment extends XoopsObject
{

	/**
	 * Constructor
	 **/
	function XoopsComment()
	{
		$this->XoopsObject();
		$this->initVar('com_id', XOBJ_DTYPE_INT, null, false);
		$this->initVar('com_pid', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('com_modid', XOBJ_DTYPE_INT, null, false);
		$this->initVar('com_icon', XOBJ_DTYPE_OTHER, null, false);
		$this->initVar('com_title', XOBJ_DTYPE_TXTBOX, null, true, 255, true);
		$this->initVar('com_text', XOBJ_DTYPE_TXTAREA, null, true, null, true);
		$this->initVar('com_created', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('com_modified', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('com_uid', XOBJ_DTYPE_INT, 0, true);
		$this->initVar('com_ip', XOBJ_DTYPE_OTHER, null, false);
		$this->initVar('com_sig', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('com_itemid', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('com_rootid', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('com_status', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('com_exparams', XOBJ_DTYPE_OTHER, null, false, 255);
		$this->initVar('dohtml', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('dosmiley', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('doxcode', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('doimage', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('dobr', XOBJ_DTYPE_INT, 0, false);
	}

	/**
	 * Is this comment on the root level?
	 *
	 * @return  bool
	 **/
	function isRoot()
	{
		return ($this->getVar('com_id') == $this->getVar('com_rootid'));
	}
}

/**
 * XOOPS comment handler class.
 *
 * This class is responsible for providing data access mechanisms to the data source
 * of XOOPS comment class objects.
 *
 *
 * @package     kernel
 * @subpackage  comment
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsCommentHandler extends XoopsObjectHandler
{

	/**
	 * Create a {@link XoopsComment}
	 *
	 * @param	bool    $isNew  Flag the object as "new"?
	 *
	 * @return	object
	 * @see htdocs/kernel/XoopsObjectHandler#create()
	 */
	function &create($isNew = true)
	{
		$comment = new XoopsComment();
		if ($isNew) {
			$comment->setNew();
		}
		return $comment;
	}

	/**
	 * Retrieve a {@link XoopsComment}
	 *
	 * @param   int $id ID
	 *
	 * @return  object  {@link XoopsComment}, FALSE on fail
	 * @see htdocs/kernel/XoopsObjectHandler#get($int_id)
	 **/
	function &get($id)
	{
		$comment = false;
		$id = intval($id);
		if ($id > 0) {
			$sql = "SELECT * FROM ".$this->db->prefix('xoopscomments')." WHERE com_id='".$id."'";
			if (!$result = $this->db->query($sql)) {
				return $comment;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$comment = new XoopsComment();
				$comment->assignVars($this->db->fetchArray($result));
			}
		}
		return $comment;
	}

	/**
	 * Insert a comment to database
	 *
	 * @param   object  &$comment
	 *
	 * @return  bool
	 * @see htdocs/kernel/XoopsObjectHandler#insert($object)
	 **/
	function insert(&$comment)
	{
		/**
		 * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
		 */
		if (!is_a($comment, 'xoopscomment')) {
			return false;
		}
		if (!$comment->isDirty()) {
			return true;
		}
		if (!$comment->cleanVars()) {
			return false;
		}
		foreach ($comment->cleanVars as $k => $v) {
			${$k} = $v;
		}
		if ($comment->isNew()) {
			$com_id = $this->db->genId('xoopscomments_com_id_seq');
			$sql = sprintf("INSERT INTO %s (com_id, com_pid, com_modid, com_icon, com_title, com_text, com_created, com_modified, com_uid, com_ip, com_sig, com_itemid, com_rootid, com_status, com_exparams, dohtml, dosmiley, doxcode, doimage, dobr) VALUES ('%u', '%u', '%u', %s, %s, %s, '%u', '%u', '%u', %s, '%u', '%u', '%u', '%u', %s, '%u', '%u', '%u', '%u', '%u')", $this->db->prefix('xoopscomments'), intval($com_id), intval($com_pid), intval($com_modid), $this->db->quoteString($com_icon), $this->db->quoteString($com_title), $this->db->quoteString($com_text), intval($com_created), intval($com_modified), intval($com_uid), $this->db->quoteString($com_ip), intval($com_sig), intval($com_itemid), intval($com_rootid), intval($com_status), $this->db->quoteString($com_exparams),
			intval($dohtml), intval($dosmiley), intval($doxcode), intval($doimage), intval($dobr));
		} else {
			$sql = sprintf("UPDATE %s SET com_pid = '%u', com_icon = %s, com_title = %s, com_text = %s, com_created = '%u', com_modified = '%u', com_uid = '%u', com_ip = %s, com_sig = '%u', com_itemid = '%u', com_rootid = '%u', com_status = '%u', com_exparams = %s, dohtml = '%u', dosmiley = '%u', doxcode = '%u', doimage = '%u', dobr = '%u' WHERE com_id = '%u'", $this->db->prefix('xoopscomments'), intval($com_pid), $this->db->quoteString($com_icon), $this->db->quoteString($com_title), $this->db->quoteString($com_text), intval($com_created), intval($com_modified), intval($com_uid), $this->db->quoteString($com_ip), intval($com_sig), intval($com_itemid), intval($com_rootid), intval($com_status), $this->db->quoteString($com_exparams),
			intval($dohtml), intval($dosmiley), intval($doxcode), intval($doimage), intval($dobr), intval($com_id));
		}
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		if (empty($com_id)) {
			$com_id = $this->db->getInsertId();
		}
		$comment->assignVar('com_id', (int)$com_id);
		return true;
	}

	/**
	 * Delete a {@link XoopsComment} from the database
	 *
	 * @param   object  &$comment
	 *
	 * @return  bool
	 *  @see htdocs/kernel/XoopsObjectHandler#delete($object)
	 **/
	function delete(&$comment)
	{
		/**
		 * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
		 */
		if (!is_a($comment, 'xoopscomment')) {
			return false;
		}
		$sql = sprintf("DELETE FROM %s WHERE com_id = '%u'", $this->db->prefix('xoopscomments'), intval($comment->getVar('com_id')));
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		return true;
	}

	/**
	 * Get some {@link XoopsComment}s
	 *
	 * @param   object  $criteria
	 * @param   bool    $id_as_key  Use IDs as keys into the array?
	 *
	 * @return  array   Array of {@link XoopsComment} objects
	 **/
	function getObjects($criteria = null, $id_as_key = false)
	{
		$ret = array();
		$limit = $start = 0;
		$sql = 'SELECT * FROM '.$this->db->prefix('xoopscomments');
		if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
			$sql .= ' '.$criteria->renderWhere();
			$sort = ($criteria->getSort() != '') ? $criteria->getSort() : 'com_id';
			$sql .= ' ORDER BY '.$sort.' '.$criteria->getOrder();
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return $ret;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			$comment = new XoopsComment();
			$comment->assignVars($myrow);
			if (!$id_as_key) {
				$ret[] =& $comment;
			} else {
				$ret[$myrow['com_id']] =& $comment;
			}
			unset($comment);
		}
		return $ret;
	}

	/**
	 * Count Comments
	 *
	 * @param   object  $criteria   {@link CriteriaElement}
	 *
	 * @return  int     Count
	 **/
	function getCount($criteria = null)
	{
		$sql = 'SELECT COUNT(*) FROM '.$this->db->prefix('xoopscomments');
		if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
			$sql .= ' '.$criteria->renderWhere();
		}
		if (!$result =& $this->db->query($sql)) {
			return 0;
		}
		list($count) = $this->db->fetchRow($result);
		return $count;
	}

	/**
	 * Delete multiple comments
	 *
	 * @param   object  $criteria   {@link CriteriaElement}
	 *
	 * @return  bool
	 **/
	function deleteAll($criteria = null)
	{
		$sql = 'DELETE FROM '.$this->db->prefix('xoopscomments');
		if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
			$sql .= ' '.$criteria->renderWhere();
		}
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		return true;
	}

	/**
	 * Get a list of comments
	 *
	 * @param   object  $criteria   {@link CriteriaElement}
	 *
	 * @return  array   Array of raw database records
	 **/
	function getList($criteria = null)
	{
		$comments = $this->getObjects($criteria, true);
		$ret = array();
		foreach (array_keys($comments) as $i) {
			$ret[$i] = $comments[$i]->getVar('com_title');
		}
		return $ret;
	}

	/**
	 * Retrieves comments for an item
	 *
	 * @param   int     $module_id  Module ID
	 * @param   int     $item_id    Item ID
	 * @param   string  $order      Sort order
	 * @param   int     $status     Status of the comment
	 * @param   int     $limit      Max num of comments to retrieve
	 * @param   int     $start      Start offset
	 *
	 * @return  array   Array of {@link XoopsComment} objects
	 **/
	function getByItemId($module_id, $item_id, $order = null, $status = null, $limit = null, $start = 0)
	{
		$criteria = new CriteriaCompo(new Criteria('com_modid', intval($module_id)));
		$criteria->add(new Criteria('com_itemid', intval($item_id)));
		if (isset($status)) {
			$criteria->add(new Criteria('com_status', intval($status)));
		}
		if (isset($order)) {
			$criteria->setOrder($order);
		}
		if (isset($limit)) {
			$criteria->setLimit($limit);
			$criteria->setStart($start);
		}
		return $this->getObjects($criteria);
	}

	/**
	 * Gets total number of comments for an item
	 *
	 * @param   int     $module_id  Module ID
	 * @param   int     $item_id    Item ID
	 * @param   int     $status     Status of the comment
	 *
	 * @return  array   Array of {@link XoopsComment} objects
	 **/
	function getCountByItemId($module_id, $item_id, $status = null)
	{
		$criteria = new CriteriaCompo(new Criteria('com_modid', intval($module_id)));
		$criteria->add(new Criteria('com_itemid', intval($item_id)));
		if (isset($status)) {
			$criteria->add(new Criteria('com_status', intval($status)));
		}
		return $this->getCount($criteria);
	}

	/**
	 * Get the top {@link XoopsComment}s
	 *
	 * @param   int     $module_id
	 * @param   int     $item_id
	 * @param   strint  $order
	 * @param   int     $status
	 *
	 * @return  array   Array of {@link XoopsComment} objects
	 **/
	function getTopComments($module_id, $item_id, $order, $status = null)
	{
		$criteria = new CriteriaCompo(new Criteria('com_modid', intval($module_id)));
		$criteria->add(new Criteria('com_itemid', intval($item_id)));
		$criteria->add(new Criteria('com_pid', 0));
		if (isset($status)) {
			$criteria->add(new Criteria('com_status', intval($status)));
		}
		$criteria->setOrder($order);
		return $this->getObjects($criteria);
	}

	/**
	 * Retrieve a whole thread
	 *
	 * @param   int     $comment_rootid
	 * @param   int     $comment_id
	 * @param   int     $status
	 *
	 * @return  array   Array of {@link XoopsComment} objects
	 **/
	function getThread($comment_rootid, $comment_id, $status = null)
	{
		$criteria = new CriteriaCompo(new Criteria('com_rootid', intval($comment_rootid)));
		$criteria->add(new Criteria('com_id', intval($comment_id), '>='));
		if (isset($status)) {
			$criteria->add(new Criteria('com_status', intval($status)));
		}
		return $this->getObjects($criteria);
	}

	/**
	 * Update
	 *
	 * @param   object  &$comment       {@link XoopsComment} object
	 * @param   string  $field_name     Name of the field
	 * @param   mixed   $field_value    Value to write
	 *
	 * @return  bool
	 **/
	function updateByField(&$comment, $field_name, $field_value)
	{
		$comment->unsetNew();
		$comment->setVar($field_name, $field_value);
		return $this->insert($comment);
	}

	/**
	 * Delete all comments for one whole module
	 *
	 * @param   int $module_id  ID of the module
	 * @return  bool
	 **/
	function deleteByModule($module_id)
	{
		return $this->deleteAll(new Criteria('com_modid', intval($module_id)));
	}

	/**
	 * Change a value in multiple comments
	 *
	 * @param   string  $fieldname  Name of the field
	 * @param   string  $fieldvalue Value to write
	 * @param   object  $criteria   {@link CriteriaElement}
	 *
	 * @return  bool
	 **/
	/*
	 function updateAll($fieldname, $fieldvalue, $criteria = null)
	 {
	 $set_clause = is_numeric($fieldvalue) ? $filedname.' = '.$fieldvalue : $filedname.' = '.$this->db->quoteString($fieldvalue);
	 $sql = 'UPDATE '.$this->db->prefix('xoopscomments').' SET '.$set_clause;
	 if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
	 $sql .= ' '.$criteria->renderWhere();
	 }
	 if (!$result = $this->db->query($sql)) {
	 return false;
	 }
	 return true;
	 }
	 */
}
?>