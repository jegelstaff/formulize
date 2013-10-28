<?php
/**
 * Manage configuration items
 *
 * @copyright    http://www.xoops.org/ The XOOPS Project
 * @copyright    XOOPS_copyrights.txt
 * @copyright    http://www.impresscms.org/ The ImpressCMS Project
 * @license      LICENSE.txt
 * @package      core
 * @subpackage   config
 * @since        XOOPS
 * @author       Kazumi Ono (aka onokazo)
 * @author       http://www.xoops.org The XOOPS Project
 * @version      $Id:Handler.php 19775 2010-07-11 18:54:25Z malanciault $
 */

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**#@+
 * Config type
 */
define('ICMS_CONF', 1);
define('ICMS_CONF_USER', 2);
define('ICMS_CONF_METAFOOTER', 3);
define('ICMS_CONF_CENSOR', 4);
define('ICMS_CONF_SEARCH', 5);
define('ICMS_CONF_MAILER', 6);
define('ICMS_CONF_AUTH', 7);
define('ICMS_CONF_MULILANGUAGE', 8);
define('ICMS_CONF_CONTENT', 9);
define('ICMS_CONF_PERSONA', 10);
define('ICMS_CONF_CAPTCHA', 11);
define('ICMS_CONF_PLUGINS', 12);
define('ICMS_CONF_AUTOTASKS', 13);
define('ICMS_CONF_PURIFIER', 14);
/**#@-*/

/**
 * Configuration handler class.
 *
 * This class is responsible for providing data access mechanisms to the data source
 * of configuration class objects.
 *
 * @author       Kazumi Ono <onokazu@xoops.org>
 * @category	ICMS
 * @package     Config
 * @subpackage  Item
 */
class icms_config_Item_Handler extends icms_core_ObjectHandler {

	/**
	 * Create a new {@link icms_config_Item_Object}
	 *
	 * @see     icms_config_Item_Object
	 * @param	bool    $isNew  Flag the config as "new"?
	 * @return	object  reference to the new config
	 */
	public function &create($isNew = true) {
		$config = new icms_config_Item_Object();
		if ($isNew) {
			$config->setNew();
		}
		return $config;
	}

	/**
	 * Load a config from the database
	 *
	 * @param	int $id ID of the config
	 * @return	object  reference to the config, FALSE on fail
	 */
	public function &get($id) {
		$config = false;
		$id = (int) $id;
		if ($id > 0) {
			$sql = "SELECT * FROM " . $this->db->prefix('config') . " WHERE conf_id='" . $id . "'";
			if (!$result = $this->db->query($sql)) {
				return $config;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$myrow = $this->db->fetchArray($result);
				$config = new icms_config_Item_Object();
				$config->assignVars($myrow);
			}
		}
		return $config;
	}

	/**
	 * Insert a config to the database
	 *
	 * @param	object  &$config    {@link icms_config_Item_Object} object
	 * @return  mixed   FALSE on fail.
	 */
	public function insert(&$config) {
		/* As of PHP5.3.0, is_a() is no longer deprecated, no need to replace this */
		if (!is_a($config, 'icms_config_Item_Object')) {
			return false;
		}
		if (!$config->isDirty()) {
			return true;
		}
		if (!$config->cleanVars()) {
			return false;
		}
		foreach ( $config->cleanVars as $k => $v) {
			${$k} = $v;
		}
		if ($config->isNew()) {
			$conf_id = $this->db->genId('config_conf_id_seq');
			$sql = sprintf(
				"INSERT INTO %s (
				conf_id,
				conf_modid,
				conf_catid,
				conf_name,
				conf_title,
				conf_value,
				conf_desc,
				conf_formtype,
				conf_valuetype,
				conf_order)
				 VALUES ('%u', '%u', '%u', %s, %s, %s, %s, %s, %s, '%u')",
				$this->db->prefix('config'),
				(int) $conf_id,
				(int) $conf_modid,
				(int) $conf_catid,
				$this->db->quoteString($conf_name),
				$this->db->quoteString($conf_title),
				$this->db->quoteString($conf_value),
				$this->db->quoteString($conf_desc),
				$this->db->quoteString($conf_formtype),
				$this->db->quoteString($conf_valuetype),
				(int) $conf_order
			);
		} else {
			$sql = sprintf(
				"UPDATE %s SET conf_modid = '%u',
				conf_catid = '%u',
				conf_name = %s,
				conf_title = %s,
				conf_value = %s,
				conf_desc = %s,
				conf_formtype = %s,
				conf_valuetype = %s,
				conf_order = '%u'
				WHERE conf_id = '%u'",
				$this->db->prefix('config'),
				(int) $conf_modid,
				(int) $conf_catid,
				$this->db->quoteString($conf_name),
				$this->db->quoteString($conf_title),
				$this->db->quoteString($conf_value),
				$this->db->quoteString($conf_desc),
				$this->db->quoteString($conf_formtype),
				$this->db->quoteString($conf_valuetype),
				(int) $conf_order,
				(int) $conf_id
			);
		}
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		if (empty($conf_id)) {
			$conf_id = $this->db->getInsertId();
		}
		$config->assignVar('conf_id', $conf_id);
		return true;
	}

	/**
	 * Delete a config from the database
	 *
	 * @param	object  &$config    Config to delete
	 * @return	bool    Successful?
	 */
	public function delete(&$config) {
		/* As of PHP5.3.0, is_as() is no longer deprecated, there is no need to replace it */
		if (!is_a($config, 'icms_config_Item_Object')) {
			return false;
		}
		$sql = sprintf(
			"DELETE FROM %s WHERE conf_id = '%u'",
			$this->db->prefix('config'), (int) $config->getVar('conf_id')
		);
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		return true;
	}

	/**
	 * Get configs from the database
	 *
	 * @param	object  $criteria   {@link icms_db_criteria_Element}
	 * @param	bool    $id_as_key  return the config's id as key?
	 * @return	array   Array of {@link icms_config_Item_Object} objects
	 */
	public function getObjects($criteria = null, $id_as_key = false) {
		$ret = array();
		$limit = $start = 0;
		$sql = 'SELECT * FROM ' . $this->db->prefix('config');
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' ' . $criteria->renderWhere();
			$sql .= ' ORDER BY conf_order ASC';
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return false;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			$config = new icms_config_item_Object();
			$config->assignVars($myrow);
			if (!$id_as_key) {
				$ret[] =& $config;
			} else {
				$ret[$myrow['conf_id']] =& $config;
			}
			unset($config);
		}
		return $ret;
	}

	/**
	 * Count configs
	 *
	 * @param	object  $criteria   {@link icms_db_criteria_Element}
	 * @return	int     Count of configs matching $criteria
	 */
	public function getCount($criteria = null) {
		$ret = array();
		$limit = $start = 0;
		$sql = 'SELECT * FROM ' . $this->db->prefix('config');
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' ' . $criteria->renderWhere();
		}
		$result =& $this->db->query($sql);
		if (!$result) {
			return false;
		}
		list($count) = $this->db->fetchRow($result);
		return $count;
	}
}

