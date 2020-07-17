<?php
/**
 * Manage configuration categories
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Config
 * @subpackage	Category
 * @author		Kazumi Ono (aka onokazo)
 * @version		SVN: $Id:Handler.php 19775 2010-07-11 18:54:25Z malanciault $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * Configuration category handler class.
 *
 * This class is responsible for providing data access mechanisms to the data source
 * of configuration category class objects.
 *
 * @author  	Kazumi Ono <onokazu@xoops.org>
 * @category	ICMS
 * @package     Config
 * @subpackage  Category
 */
class icms_config_category_Handler extends icms_core_ObjectHandler {

	/**
	 * Create a new category
	 *
	 * @param	bool    $isNew  Flag the new object as "new"?
	 *
	 * @return	object  New {@link icms_config_category_Object}
	 * @see htdocs/kernel/icms_core_ObjectHandler#create()
	 */
	public function &create($isNew = true)	{
		$confcat = new icms_config_category_Object();
		if ($isNew) {
			$confcat->setNew();
		}
		return $confcat;
	}

	/**
	 * Retrieve a {@link icms_config_category_Object}
	 *
	 * @param	int $id ConfigCategoryID to get
	 *
	 * @return	object|false  {@link icms_config_category_Object}, FALSE on fail
	 * @see htdocs/kernel/icms_core_ObjectHandler#get($int_id)
	 */
	public function &get($id) {
		$confcat = false;
		$id = (int) $id;
		if ($id > 0) {
			$sql = "SELECT * FROM " . $this->db->prefix('configcategory') . " WHERE confcat_id='" . $id . "'";
			if (!$result = $this->db->query($sql)) {
				return $confcat;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$confcat = new icms_config_category_Object();
				$confcat->assignVars($this->db->fetchArray($result), false);
			}
		}
		return $confcat;
	}

	/**
	 * Insert a {@link icms_config_category_Object} into the DataBase
	 *
	 * @param	object   &$confcat  {@link icms_config_category_Object}
	 *
	 * @return	bool    TRUE on success
	 * @see htdocs/kernel/icms_core_ObjectHandler#insert($object)
	 */
	public function insert(&$confcat) {
		/**
		 * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
		 */
		if (!is_a($confcat, 'icms_config_category_Object')) {
			return false;
		}
		if (!$confcat->isDirty()) {
			return true;
		}
		if (!$confcat->cleanVars()) {
			return false;
		}
		foreach ( $confcat->cleanVars as $k => $v) {
			${$k} = $v;
		}
		if ($confcat->isNew()) {
			$confcat_id = $this->db->genId('configcategory_confcat_id_seq');
			$sql = sprintf(
				"INSERT INTO %s (confcat_id, confcat_name, confcat_order)
				VALUES ('%u', %s, '%u')",
				$this->db->prefix('configcategory'), (int) ($confcat_id), $this->db->quoteString($confcat_name), (int) ($confcat_order)
				);
		} else {
			$sql = sprintf(
				"UPDATE %s SET confcat_name = %s, confcat_order = '%u'
				WHERE confcat_id = '%u'",
				$this->db->prefix('configcategory'), $this->db->quoteString($confcat_name), (int) ($confcat_order), (int) ($confcat_id));
		}
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		if (empty($confcat_id)) {
			$confcat_id = $this->db->getInsertId();
		}
		$confcat->assignVar('confcat_id', $confcat_id);
		return $confcat_id;
	}

	/**
	 * Delelete a {@link icms_config_category_Object}
	 *
	 * @param	object  &$confcat   {@link icms_config_category_Object}
	 *
	 * @return	bool    TRUE on success
	 * @see htdocs/kernel/icms_core_ObjectHandler#delete($object)
	 */
	public function delete(&$confcat) {
		/**
		 * @TODO: Change to if (!(class_exists($this->className) && $obj instanceof $this->className)) when going fully PHP5
		 */
		if (!is_a($confcat, 'icms_config_category_Object')) {
			return false;
		}

		$sql = sprintf(
			"DELETE FROM %s WHERE confcat_id = '%u'",
			$this->db->prefix('configcategory'), (int) ($configcategory->getVar('confcat_id'))
			);
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		return true;
	}

	/**
	 * Get some {@link icms_config_category_Object}s
	 *
	 * @param	object  $criteria   {@link icms_db_criteria_Element}
	 * @param	bool    $id_as_key  Use the IDs as keys to the array?
	 *
	 * @return	array   Array of {@link icms_config_category_Object}s
	 */
	public function getObjects($criteria = null, $id_as_key = false) {
		$ret = array();
		$limit = $start = 0;
		$sql = 'SELECT * FROM ' . $this->db->prefix('configcategory');
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' '.$criteria->renderWhere();
			$sort = !in_array($criteria->getSort(), array('confcat_id', 'confcat_name', 'confcat_order'))
					? 'confcat_order'
					: $criteria->getSort();
			$sql .= ' ORDER BY ' . $sort . ' ' . $criteria->getOrder();
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return $ret;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			$confcat = new icms_config_category_Object();
			$confcat->assignVars($myrow, false);
			if (!$id_as_key) {
				$ret[] =& $confcat;
			} else {
				$ret[$myrow['confcat_id']] =& $confcat;
			}
			unset($confcat);
		}
		return $ret;
	}
}

