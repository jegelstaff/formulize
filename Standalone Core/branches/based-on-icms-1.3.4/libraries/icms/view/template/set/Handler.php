<?php
/**
 * Manage template sets
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		View
 * @subpackage	Template
 * @version		SVN: $Id:Handler.php 19775 2010-07-11 18:54:25Z malanciault $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * Template set handler class.
 * This class is responsible for providing data access mechanisms to the data source
 * of template set class objects.
 *
 *
 * @author		Kazumi Ono <onokazu@xoops.org>
 * @category	ICMS
 * @package		View
 * @subpackage	Template
 */
class icms_view_template_set_Handler extends icms_core_ObjectHandler {

	/**
	 * create a new templateset instance
	 *
	 * @see icms_view_template_set_Object
	 * @param bool $isNew is the new tempateset new??
	 * @return object icms_view_template_set_Object {@link icms_view_template_set_Object} reference to the new template
	 **/
	public function &create($isNew = true) {
		$tplset = new icms_view_template_set_Object();
		if ($isNew) {
			$tplset->setNew();
		}
		return $tplset;
	}

	/**
	 * Gets templateset from database by ID
	 *
	 * @see icms_view_template_set_Object
	 * @param int $id of the tempateset to get
	 * @return object icms_view_template_set_Object {@link icms_view_template_set_Object} reference to the new template
	 **/
	public function &get($id) {
		$tplset = false;
		$id = (int) $id;
		if ($id > 0) {
			$sql = "SELECT * FROM " . $this->db->prefix('tplset')
				. " WHERE tplset_id='" . $id . "'";
			if (!$result = $this->db->query($sql)) {
				return $tplset;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$tplset = new icms_view_template_set_Object();
				$tplset->assignVars($this->db->fetchArray($result));
			}
		}
		return $tplset;
	}

	/**
	 * Gets templateset from database by Name
	 *
	 * @see icms_view_template_set_Object
	 * @param string $tplset_name of the tempateset to get
	 * @return object icms_view_template_set_Object {@link icms_view_template_set_Object} reference to the new template
	 **/
	public function &getByName($tplset_name) {
		$tplset = false;
		$tplset_name = trim($tplset_name);
		if ($tplset_name != '') {
			$sql = "SELECT * FROM " . $this->db->prefix('tplset')
				. " WHERE tplset_name=" . $this->db->quoteString($tplset_name) . "";
			if (!$result = $this->db->query($sql)) {
				return $tplset;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$tplset = new icms_view_template_set_Object();
				$tplset->assignVars($this->db->fetchArray($result));
			}
		}
		return $tplset;
	}

	/**
	 * Inserts templateset into the database
	 *
	 * @see icms_view_template_set_Object
	 * @param string $tplset_name of the tempateset to get
	 * @return object icms_view_template_set_Object {@link icms_view_template_set_Object} reference to the new template
	 **/
	public function insert(&$tplset) {
		/* As of PHP5.3.0, is_as() is no longer deprecated */
		if (!is_a($tplset, 'icms_view_template_set_Object')) {
			return false;
		}
		if (!$tplset->isDirty()) {
			return true;
		}
		if (!$tplset->cleanVars()) {
			return false;
		}
		foreach ($tplset->cleanVars as $k => $v) {
			${$k} = $v;
		}
		if ($tplset->isNew()) {
			$tplset_id = $this->db->genId('tplset_tplset_id_seq');
			$sql = sprintf(
				"INSERT INTO %s (tplset_id, tplset_name, tplset_desc, tplset_credits, tplset_created)
				VALUES ('%u', %s, %s, %s, '%u')",
				$this->db->prefix('tplset'),
				(int) $tplset_id,
				$this->db->quoteString($tplset_name),
				$this->db->quoteString($tplset_desc),
				$this->db->quoteString($tplset_credits),
				(int) $tplset_created
			);
		} else {
			$sql = sprintf(
				"UPDATE %s SET
				tplset_name = %s,
				tplset_desc = %s,
				tplset_credits = %s,
				tplset_created = '%u'
				WHERE tplset_id = '%u'",
				$this->db->prefix('tplset'),
				$this->db->quoteString($tplset_name),
				$this->db->quoteString($tplset_desc),
				$this->db->quoteString($tplset_credits),
				(int) $tplset_created,
				(int) $tplset_id
			);
		}
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		if (empty($tplset_id)) {
			$tplset_id = $this->db->getInsertId();
		}
		$tplset->assignVar('tplset_id', $tplset_id);
		return true;
	}

	/**
	 * Deletes templateset from the database
	 *
	 * @see icms_view_template_set_Object
	 * @param object $tplset {@link icms_view_template_set_Object} reference to the object of the tempateset to delete
	 * @return object icms_view_template_set_Object {@link icms_view_template_set_Object} reference to the new template
	 **/
	public function delete(&$tplset) {
		/* As of PHP5.3.0, ia_a() is no longer deprecated */
		if (!is_a($tplset, 'icms_view_template_set_Object')) {
			return false;
		}

		$sql = sprintf(
			"DELETE FROM %s WHERE tplset_id = '%u'",
			$this->db->prefix('tplset'),
			(int) $tplset->getVar('tplset_id')
		);
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		$sql = sprintf(
			"DELETE FROM %s WHERE tplset_name = %s",
			$this->db->prefix('imgset_tplset_link'),
			$this->db->quoteString($tplset->getVar('tplset_name'))
		);
		$this->db->query($sql);
		return true;
	}

	/**
	 * retrieve array of {@link icms_view_template_set_Object}s meeting certain conditions
	 * @param object $criteria {@link icms_db_criteria_Element} with conditions for the blocks
	 * @param bool $id_as_key should the tplfile's tpl_id be the key for the returned array?
	 * @return array {@link icms_view_template_set_Object}s matching the conditions
	 **/
	public function getObjects($criteria = null, $id_as_key = false) {
		$ret = array();
		$limit = $start = 0;
		$sql = 'SELECT * FROM ' . $this->db->prefix('tplset');
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' ' . $criteria->renderWhere() . ' ORDER BY tplset_id';
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return $ret;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			$tplset = new icms_view_template_set_Object();
			$tplset->assignVars($myrow);
			if (!$id_as_key) {
				$ret[] =& $tplset;
			} else {
				$ret[$myrow['tplset_id']] =& $tplset;
			}
			unset($tplset);
		}
		return $ret;
	}

	/**
	 * Count some tplfilesets
	 *
	 * @param   object  $criteria   {@link icms_db_criteria_Element}
	 * @return  int $count number of template filesets that match the criteria
	 **/
	public function getCount($criteria = null) {
		$sql = 'SELECT COUNT(*) FROM ' . $this->db->prefix('tplset');
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' ' . $criteria->renderWhere();
		}
		if (!$result =& $this->db->query($sql)) {
			return 0;
		}
		list($count) = $this->db->fetchRow($result);
		return $count;
	}

	/**
		* Gets list of tplset objects into an array
		*
		* @param array  $criteria  array of WHERE statement criteria
		*
		* @return array  The array of tplset objects
		*/
	public function getList($criteria = null) {
		$ret = array();
		$tplsets = $this->getObjects($criteria, true);
		foreach (array_keys($tplsets) as $i) {
			$ret[$tplsets[$i]->getVar('tplset_name')] = $tplsets[$i]->getVar('tplset_name');
		}
		return $ret;
	}
}

