<?php
/**
 * Manage configuration options
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Config
 * @subpackage	Option
 * @author		Kazumi Ono (aka onokazo)
 * @version		SVN: $Id:Handler.php 19775 2010-07-11 18:54:25Z malanciault $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * Configuration option handler class.
 * This class is responsible for providing data access mechanisms to the data source
 * of configuration option class objects.
 *
 * @author  Kazumi Ono <onokazu@xoops.org>
 *
 * @category	ICMS
 * @package     Config
 * @subpackage  Option
 */
class icms_config_option_Handler extends icms_core_ObjectHandler {

	/**
	 * Create a new option
	 *
	 * @param	bool    $isNew  Flag the option as "new"?
	 *
	 * @return	object  {@link icms_config_option_Object}
	 */
	public function &create($isNew = true) {
		$confoption = new icms_config_option_Object();
		if ($isNew) {
			$confoption->setNew();
		}
		return $confoption;
	}

	/**
	 * Get an option from the database
	 *
	 * @param	int $id ID of the option
	 *
	 * @return	object  reference to the {@link icms_config_option_Object}, FALSE on fail
	 */
	public function &get($id) {
		$confoption = false;
		$id = (int) $id;
		if ($id > 0) {
			$sql = "SELECT * FROM " . $this->db->prefix('configoption') . " WHERE confop_id='" . $id . "'";
			if (!$result = $this->db->query($sql)) {
				return $confoption;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$confoption = new icms_config_option_Object();
				$confoption->assignVars($this->db->fetchArray($result));
			}
		}
		return $confoption;
	}

	/**
	 * Insert a new option in the database
	 *
	 * @param	object  &$confoption    reference to a {@link icms_config_option_Object}
	 * @return	bool    TRUE if successfull.
	 */
	public function insert(&$confoption) {
		/* As of PHP5.3.0, is_a() is no longer deprecated, no need to replace it */
		if (!is_a($confoption, 'icms_config_option_Object')) {
			return false;
		}
		if (!$confoption->isDirty()) {
			return true;
		}
		if (!$confoption->cleanVars()) {
			return false;
		}
		foreach ( $confoption->cleanVars as $k => $v) {
			${$k} = $v;
		}
		if ($confoption->isNew()) {
			$confop_id = $this->db->genId('configoption_confop_id_seq');
			$sql = sprintf(
				"INSERT INTO %s (confop_id, confop_name, confop_value, conf_id)
				VALUES ('%u', %s, %s, '%u')",
				$this->db->prefix('configoption'),
				(int) $confop_id,
				$this->db->quoteString($confop_name),
				$this->db->quoteString($confop_value),
				(int) $conf_id
				);
		} else {
			$sql = sprintf(
			"UPDATE %s SET confop_name = %s, confop_value = %s
			WHERE confop_id = '%u'",
			$this->db->prefix('configoption'),
			$this->db->quoteString($confop_name),
			$this->db->quoteString($confop_value),
			(int) ($confop_id)
			);
		}
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		if (empty($confop_id)) {
			$confop_id = $this->db->getInsertId();
		}
		$confoption->assignVar('confop_id', $confop_id);
		return $confop_id;
	}

	/**
	 * Delete an option
	 *
	 * @param	object  &$confoption    reference to a {@link icms_config_option_Object}
	 * @return	bool    TRUE if successful
	 */
	public function delete(&$confoption) {
		/* As of PHP5.3.0, is_a() is no longer deprecated, no need to replace it */
		if (!is_a($confoption, 'icms_config_option_Object')) {
			return false;
		}
		$sql = sprintf(
			"DELETE FROM %s WHERE confop_id = '%u'",
			$this->db->prefix('configoption'),
			(int) ($confoption->getVar('confop_id'))
			);
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		return true;
	}

	/**
	 * Get some {@link icms_config_option_Object}s
	 *
	 * @param	object  $criteria   {@link icms_db_criteria_Element}
	 * @param	bool    $id_as_key  Use the IDs as array-keys?
	 *
	 * @return	array   Array of {@link icms_config_option_Object}s
	 */
	public function getObjects($criteria = null, $id_as_key = false) {
		$ret = array();
		$limit = $start = 0;
		$sql = 'SELECT * FROM ' . $this->db->prefix('configoption');
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' ' . $criteria->renderWhere() . ' ORDER BY confop_id ' . $criteria->getOrder();
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return $ret;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			$confoption = new icms_config_option_Object();
			$confoption->assignVars($myrow);
			if (!$id_as_key) {
				$ret[] =& $confoption;
			} else {
				$ret[$myrow['confop_id']] =& $confoption;
			}
			unset($confoption);
		}
		return $ret;
	}
}

