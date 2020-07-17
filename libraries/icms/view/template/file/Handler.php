<?php
/**
 * Manage template files
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		View
 * @subpackage	Template
 * @version		SVN: $Id: Handler.php 20105 2010-09-08 15:39:19Z malanciault $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * Template file handler class.
 * This class is responsible for providing data access mechanisms to the data source
 * of template file class objects.
 *
 * @category	ICMS
 * @package		View
 * @subpackage	Template
 * @author		Kazumi Ono <onokazu@xoops.org>
 */
class icms_view_template_file_Handler extends icms_core_ObjectHandler {
	private $_prefetch_cache = array();

	/**
	 * create a new template instance
	 *
	 * @see icms_view_template_file_Object
	 * @param bool $isNew is the new tempate new??
	 * @return object icms_view_template_file_Object {@link icms_view_template_file_Object} reference to the new template
	 **/
	public function &create($isNew = true) {
		$tplfile = new icms_view_template_file_Object();
		if ($isNew) {
			$tplfile->setNew();
		}
		return $tplfile;
	}

	/**
	 * gets a new template instance
	 *
	 * @see icms_view_template_file_Object
	 * @param int $id ID of the template to get
	 * @param bool $getsource would you like to get the source?
	 * @return object icms_view_template_file_Object {@link icms_view_template_file_Object} reference to the new template
	 **/
	public function &get($id, $getsource = false) {
		$tplfile = false;
		$id = (int) $id;
		if ($id > 0) {
			if (!$getsource) {
				$sql = "SELECT * FROM " . $this->db->prefix('tplfile') . " WHERE tpl_id='" . $id . "'";
			} else {
				$sql = "SELECT f.*, s.tpl_source FROM " . $this->db->prefix('tplfile')
					. " f LEFT JOIN " . $this->db->prefix('tplsource')
					. " s  ON s.tpl_id=f.tpl_id WHERE f.tpl_id='" . $id . "'";
			}
			if (!$result = $this->db->query($sql)) {
				return $tplfile;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$tplfile = new icms_view_template_file_Object();
				$tplfile->assignVars($this->db->fetchArray($result));
			}
		}
		return $tplfile;
	}

	/**
	 * Loads Template source from DataBase
	 *
	 * @see icms_view_template_file_Object
	 * @param object $tplfile {@link icms_view_template_file_Object} object of the template file to load
	 * @return bool TRUE on success, FALSE if fail
	 **/
	public function loadSource(&$tplfile) {
		/* As of PHP5.3.0, is_a() is no longer deprecated */
		if (!is_a($tplfile, 'icms_view_template_file_Object')) {
			return false;
		}

		if (!$tplfile->getVar('tpl_source')) {
			$sql = "SELECT tpl_source FROM " . $this->db->prefix('tplsource')
				. " WHERE tpl_id='" . $tplfile->getVar('tpl_id') . "'";
			if (!$result = $this->db->query($sql)) {
				return false;
			}
			$myrow = $this->db->fetchArray($result);
			$tplfile->assignVar('tpl_source', $myrow['tpl_source']);
		}
		return true;
	}

	/**
	 * Inserts Template into the DataBase
	 *
	 * @see icms_view_template_file_Object
	 * @param object $tplfile {@link icms_view_template_file_Object} object of the template file to load
	 * @return bool TRUE on success, FALSE if fail
	 **/
	public function insert(&$tplfile) {
		/* As of PHP5.3.0, is_a() is no longer deprecated */
		if (!is_a($tplfile, 'icms_view_template_file_Object')) {
			return false;
		}
		if (!$tplfile->isDirty()) {
			return true;
		}
		if (!$tplfile->cleanVars()) {
			return false;
		}
		foreach ( $tplfile->cleanVars as $k => $v) {
			${$k} = $v;
		}
		if ($tplfile->isNew()) {
			$tpl_id = $this->db->genId('tpltpl_file_id_seq');
			$sql = sprintf(
				"INSERT INTO %s (tpl_id, tpl_module, tpl_refid, tpl_tplset, tpl_file, tpl_desc, tpl_lastmodified, tpl_lastimported, tpl_type)
				VALUES ('%u', %s, '%u', %s, %s, %s, '%u', '%u', %s)",
				$this->db->prefix('tplfile'),
				(int) ($tpl_id),
				$this->db->quoteString($tpl_module),
				(int) $tpl_refid,
				$this->db->quoteString($tpl_tplset),
				$this->db->quoteString($tpl_file),
				$this->db->quoteString($tpl_desc),
				(int) $tpl_lastmodified,
				(int) $tpl_lastimported,
				$this->db->quoteString($tpl_type)
			);
			if (!$result = $this->db->query($sql)) {
				return false;
			}
			if (empty($tpl_id)) {
				$tpl_id = $this->db->getInsertId();
			}
			if (isset($tpl_source) && $tpl_source != '') {
				$sql = sprintf("INSERT INTO %s (tpl_id, tpl_source)
					VALUES ('%u', %s)",
					$this->db->prefix('tplsource'),
					(int) $tpl_id,
					$this->db->quoteString($tpl_source)
				);
				if (!$result = $this->db->query($sql)) {
					$this->db->query(sprintf("DELETE FROM %s WHERE tpl_id = '%u'",
						$this->db->prefix('tplfile'), (int) $tpl_id)
					);
					return false;
				}
			}
			$tplfile->assignVar('tpl_id', $tpl_id);
		} else {
			$sql = sprintf(
				"UPDATE %s SET tpl_tplset = %s, tpl_file = %s, tpl_desc = %s, tpl_lastimported = '%u', tpl_lastmodified = '%u' WHERE tpl_id = '%u'",
				$this->db->prefix('tplfile'),
				$this->db->quoteString($tpl_tplset),
				$this->db->quoteString($tpl_file),
				$this->db->quoteString($tpl_desc),
				(int) $tpl_lastimported,
				(int) $tpl_lastmodified,
				(int) $tpl_id
			);
			if (!$result = $this->db->query($sql)) {
				return false;
			}
			if (isset($tpl_source) && $tpl_source != '') {
				$sql = sprintf("UPDATE %s SET tpl_source = %s WHERE tpl_id = '%u'",
					$this->db->prefix('tplsource'),
					$this->db->quoteString($tpl_source),
					(int) $tpl_id
				);
				if (!$result = $this->db->query($sql)) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * forces Template source into the DataBase
	 * @param object $tplfile {@link icms_view_template_file_Object} object of the template file to load
	 * @return bool TRUE on success, FALSE if fail
	 **/
	public function forceUpdate(&$tplfile) {
		/* As of PHP5.3.0, is_a() is no longer deprecated */
		if (!is_a($tplfile, 'icms_view_template_file_Object')) {
			return false;
		}
		if (!$tplfile->isDirty()) {
			return true;
		}
		if (!$tplfile->cleanVars()) {
			return false;
		}
		foreach ( $tplfile->cleanVars as $k => $v) {
			${$k} = $v;
		}
		if (!$tplfile->isNew()) {
			$sql = sprintf("UPDATE %s SET tpl_tplset = %s, tpl_file = %s, tpl_desc = %s, tpl_lastimported = '%u', tpl_lastmodified = '%u' WHERE tpl_id = '%u'", $this->db->prefix('tplfile'), $this->db->quoteString($tpl_tplset), $this->db->quoteString($tpl_file), $this->db->quoteString($tpl_desc), (int) ($tpl_lastimported), (int) ($tpl_lastmodified), (int) ($tpl_id));
			if (!$result = $this->db->queryF($sql)) {
				return false;
			}
			if (isset($tpl_source) && $tpl_source != '') {
				$sql = sprintf("UPDATE %s SET tpl_source = %s WHERE tpl_id = '%u'", $this->db->prefix('tplsource'), $this->db->quoteString($tpl_source), (int) ($tpl_id));
				if (!$result = $this->db->queryF($sql)) {
					return false;
				}
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Deletes Template from the DataBase
	 * @param object $tplfile {@link icms_view_template_file_Object} object of the template file to load
	 * @return bool TRUE on success, FALSE if fail
	 **/
	public function delete(&$tplfile) {
		/* As of PHP5.3.0, is_a() is no longer deprecated */
		if (!is_a($tplfile, 'icms_view_template_file_Object')) {
			return false;
		}
		$id = (int) ($tplfile->getVar('tpl_id'));
		$sql = sprintf("DELETE FROM %s WHERE tpl_id = '%u'", $this->db->prefix('tplfile'), $id);
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		$sql = sprintf("DELETE FROM %s WHERE tpl_id = '%u'", $this->db->prefix('tplsource'), $id);
		$this->db->query($sql);
		return true;
	}

	/**
	 * retrieve array of {@link icms_view_template_file_Object}s meeting certain conditions
	 * @param object $criteria {@link icms_db_criteria_Element} with conditions for the blocks
	 * @param bool $id_as_key should the tplfile's tpl_id be the key for the returned array?
	 * @return array {@link icms_view_template_file_Object}s matching the conditions
	 **/
	public function getObjects($criteria = null, $getsource = false, $id_as_key = false) {
		$ret = array();
		$limit = $start = 0;
		if ($getsource) {
			$sql = "SELECT f.*, s.tpl_source FROM " . $this->db->prefix('tplfile')
				. " f LEFT JOIN " . $this->db->prefix('tplsource') . " s ON s.tpl_id=f.tpl_id";
		} else {
			$sql = "SELECT * FROM " . $this->db->prefix('tplfile');
		}
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= " " . $criteria->renderWhere() . " ORDER BY tpl_refid";
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return $ret;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			$tplfile = new icms_view_template_file_Object();
			$tplfile->assignVars($myrow);
			if (!$id_as_key) {
				$ret[] =& $tplfile;
			} else {
				$ret[$myrow['tpl_id']] =& $tplfile;
			}
			unset($tplfile);
		}
		return $ret;
	}

	/**
	 * Count some tplfiles
	 *
	 * @param   object  $criteria   {@link icms_db_criteria_Element}
	 * @return  int
	 **/
	public function getCount($criteria = null)	{
		$sql = 'SELECT COUNT(*) FROM ' . $this->db->prefix('tplfile');
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
	 * Count some tplfiles for a module
	 *
	 * @param   string  $tplset Template Set
	 * @return  array $ret containing number of templates in the tpl_set or empty array if fails
	 **/
	public function getModuleTplCount($tplset) {
		$ret = array();
		$sql = "SELECT tpl_module, COUNT(tpl_id) AS count FROM " . $this->db->prefix('tplfile')
			. " WHERE tpl_tplset='" . $tplset . "' GROUP BY tpl_module";
		$result = $this->db->query($sql);
		if (!$result) {
			return $ret;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			if ($myrow['tpl_module'] != '') {
				$ret[$myrow['tpl_module']] = $myrow['count'];
			}
		}
		return $ret;
	}

	/**
	 * find tplfiles matching criteria
	 *
	 * @param   string  $tplset             template set
	 * @param   string  $type               template type
	 * @param   int     $refid              ref id
	 * @param   string  $module             module
	 * @param   string  $file               template file
	 * @param   bool    $getsource = false  get source or not
	 * @return  array $ret containing number of templates in the tpl_set or empty array if fails
	 **/
	public function find($tplset = null, $type = null, $refid = null, $module = null, $file = null, $getsource = false) {
		$criteria = new icms_db_criteria_Compo();
		if (isset($tplset)) {
			$criteria->add(new icms_db_criteria_Item('tpl_tplset', $tplset));
		}
		if (isset($module)) {
			$criteria->add(new icms_db_criteria_Item('tpl_module', $module));
		}
		if (isset($refid)) {
			$criteria->add(new icms_db_criteria_Item('tpl_refid', $refid));
		}
		if (isset($file)) {
			$criteria->add(new icms_db_criteria_Item('tpl_file', $file));
		}
		if (isset($type)) {
			if (is_array($type)) {
				$criteria2 = new icms_db_criteria_Compo();
				foreach ( $type as $t) {
					$criteria2->add(new icms_db_criteria_Item('tpl_type', $t), 'OR');
				}
				$criteria->add($criteria2);
			} else {
				$criteria->add(new icms_db_criteria_Item('tpl_type', $type));
			}
		}
		return $this->getObjects($criteria, $getsource, false);
	}

	/**
	 * Does the template exist in the database in the template set
	 *
	 * @param   string  $tplname        template name
	 * @param   string  $tplset_name    template set name
	 * @return  bool true if exists, false if not
	 **/
	public function templateExists($tplname, $tplset_name) {
		$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('tpl_file', trim($tplname)));
		$criteria->add(new icms_db_criteria_Item('tpl_tplset', trim($tplset_name)));
		if ($this->getCount($criteria) > 0) {
			return true;
		}
		return false;
	}

	/**
	 * Prefetch blocks to reduce the amount of queries required by Smarty to generate all blocks
	 * This function is called exclusively in icms_view_PageBuilder
	 *
	 * @global	array	$icmsConfig		icms configuration array
	 * @param	array	$block_arr		array of blocks to prefetch
	 * @return	bool					false if there are no blocks to prefetch, otherwise true
	 */
	public function prefetchBlocks(&$block_arr) {
		global $icmsConfig;

		if (count($block_arr) == 0) return false;
		$tplNames = array();

		/**
		 * @todo As soon as the criteria object is capable of rendering complex conditions,
		 * this should be converted into the criteria approach
		 */
		$sql = "SELECT f.*, s.tpl_source FROM " . $this->db->prefix("tplfile") . " f "
		     . "LEFT JOIN " . $this->db->prefix("tplsource") . " s ON s.tpl_id=f.tpl_id "
		     . "WHERE (tpl_tplset = '" . $icmsConfig["template_set"] . "' "
		     // always load the default templates as a fallback
		     . "OR tpl_tplset = 'default') AND (";

		foreach ($block_arr as $block) {
			$tplName = ($tplName = $block->getVar("template")) ? "$tplName" : "system_block_dummy.html";
			$tplNames[] = "tpl_file = '" . $tplName . "'";
		}
		$sql .= implode(" OR ", $tplNames);
		$sql .= ") ORDER BY tpl_refid";

		$result = $this->db->query($sql);
		if (!$result) return false;
		while ($myrow = $this->db->fetchArray($result)) {
			$tplfile = new icms_view_template_file_Object();
			$tplfile->assignVars($myrow);
			$this->_prefetch_cache[] =& $tplfile;
			unset($tplfile);
		}
		return true;
	}

	/**
	 * Return a prefetched block. This function only works if prefetchBlocks was called in advance.
	 * This function is used in the user function smarty_resource_db_tplinfo().
	 *
	 * @param	str		$tplset		template set that's currently in use
	 * @param	str		$tpl_name	name of the template
	 * @return	array				array of templates (just one item)
	 */
	public function getPrefetchedBlock($tplset, $tpl_name) {
		foreach($this->_prefetch_cache as $block) {
			if ($block->getVar("tpl_tplset") == $tplset && $block->getVar("tpl_file") == $tpl_name) {
				return array($block);
			}
		}

		/**
		 * try to get the template from the default template set (we've also prefetched it) if the
		 * template set is different from default
		 */
		if ($tplset != 'default') {
			foreach($this->_prefetch_cache as $block) {
				if ($block->getVar("tpl_tplset") == "default" && $block->getVar("tpl_file") == $tpl_name) {
					return array($block);
				}
			}
		}

		/**
		 * In case nothing was found, the following fallback tries to read the template again.
		 * This is the case for all non-block templates since blocks are prefetched before the
		 * content template(s) are required.
		 * To avoid further queries for the same block, we're adding it to the cache
		 */
		$blocks = $this->find($tplset, null, null, null, $tpl_name, true);
		array_merge($this->_prefetch_cache, $blocks);
		return $blocks;
	}
}