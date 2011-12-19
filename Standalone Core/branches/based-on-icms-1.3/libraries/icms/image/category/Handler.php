<?php
/**
 * Manage of Image categories
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Image
 * @subpackage	Category
 * @version		SVN: $Id: Handler.php 20105 2010-09-08 15:39:19Z malanciault $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * Image caetgory handler class.
 * This class is responsible for providing data access mechanisms to the data source
 * of image category class objects.
 *
 * @category	ICMS
 * @package		Image
 * @subpackage	Category
 * @author		Kazumi Ono <onokazu@xoops.org>
 */
class icms_image_category_Handler extends icms_core_ObjectHandler {

	/**
	 * Creates a new image category
	 *
	 * @param bool $isNew is the new image category new??
	 * @return object $imgcat {@link icms_image_category_Object} reference to the new image category
	 **/
	public function &create($isNew = true) {
		$imgcat = new icms_image_category_Object();
		if ($isNew) {
			$imgcat->setNew();
		}
		return $imgcat;
	}

	/**
	 * retrieve a specific {@link icms_image_category_Object}
	 *
	 * @see icms_image_category_Object
	 * @param integer $id imgcatID (imgcat_id) of the image category
	 * @return object icms_image_category_Object reference to the image category
	 **/
	public function &get($id) {
		$id = (int) ($id);
		$imgcat = false;
		if ($id > 0) {
			$sql = "SELECT * FROM ".$this->db->prefix('imagecategory') . " WHERE imgcat_id='" . $id . "'";
			if (!$result = $this->db->query($sql)) {
				return $imgcat;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$imgcat = new icms_image_category_Object();
				$imgcat->assignVars($this->db->fetchArray($result));
			}
		}
		return $imgcat;
	}

	/**
	 * Insert a new {@link icms_image_category_Object} into the database
	 *
	 * @param object icms_image_category_Object $imgcat reference to the image category to insert
	 * @return bool TRUE if succesful
	 **/
	public function insert(&$imgcat) {
		/* As of PHP 5.3, is_a is no longer deprecated, this is an acceptable usage
		 * and is compatible with more versions of PHP.  http://us2.php.net/manual/en/language.operators.type.php
		 */
		if (!is_a($imgcat, 'icms_image_category_Object')) {
			return false;
		}

		if (!$imgcat->isDirty()) {
			return true;
		}
		if (!$imgcat->cleanVars()) {
			return false;
		}
		foreach ( $imgcat->cleanVars as $k => $v) {
			${$k} = $v;
		}
		if ($imgcat->isNew()) {
			$imgcat_id = $this->db->genId('imgcat_imgcat_id_seq');
			$sql = sprintf("INSERT INTO %s (imgcat_id, imgcat_pid, imgcat_name, imgcat_foldername, imgcat_display, imgcat_weight, imgcat_maxsize, imgcat_maxwidth, imgcat_maxheight, imgcat_type, imgcat_storetype) VALUES ('%u', '%u', %s, %s, '%u', '%u', '%u', '%u', '%u', %s, %s)",
				$this->db->prefix('imagecategory'),
				(int) $imgcat_id,
				(int) $imgcat_pid,
				$this->db->quoteString($imgcat_name),
				$this->db->quoteString($imgcat_foldername),
				(int) $imgcat_display,
				(int) $imgcat_weight,
				(int) $imgcat_maxsize,
				(int) $imgcat_maxwidth,
				(int) $imgcat_maxheight,
				$this->db->quoteString($imgcat_type),
				$this->db->quoteString($imgcat_storetype)
			);
		} else {
			$sql = sprintf("UPDATE %s SET imgcat_pid = %u, imgcat_name = %s, imgcat_foldername = %s, imgcat_display = '%u', imgcat_weight = '%u', imgcat_maxsize = '%u', imgcat_maxwidth = '%u', imgcat_maxheight = '%u', imgcat_type = %s WHERE imgcat_id = '%u'",
				$this->db->prefix('imagecategory'),
				(int) $imgcat_pid,
				$this->db->quoteString($imgcat_name),
				$this->db->quoteString($imgcat_foldername),
				(int) $imgcat_display,
				(int) $imgcat_weight,
				(int) $imgcat_maxsize,
				(int) $imgcat_maxwidth,
				(int) $imgcat_maxheight,
				$this->db->quoteString($imgcat_type),
				(int) $imgcat_id
			);
		}
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		if (empty($imgcat_id)) {
			$imgcat_id = $this->db->getInsertId();
		}
		$imgcat->assignVar('imgcat_id', $imgcat_id);
		return true;
	}

	/**
	 * delete an {@link icms_image_category_Object} from the database
	 *
	 * @param object icms_image_category_Object $imgcat reference to the image category to delete
	 * @return bool TRUE if succesful
	 **/
	public function delete(&$imgcat) {
		/* As of PHP 5.3, is_a is no longer deprecated, this is an acceptable usage
		 * and is compatible with more versions of PHP. http://us2.php.net/manual/en/language.operators.type.php
		 */
		if (!is_a($imgcat, 'icms_image_category_Object')) {
			return false;
		}

		$sql = sprintf("DELETE FROM %s WHERE imgcat_id = '%u'", $this->db->prefix('imagecategory'), (int) ($imgcat->getVar('imgcat_id')));
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		return true;
	}

	/**
	 * retrieve array of {@link icms_image_category_Object}s meeting certain conditions
	 * @param object $criteria {@link icms_db_criteria_Element} with conditions for the image categories
	 * @param bool $id_as_key should the image category's imgcat_id be the key for the returned array?
	 * @return array {@link icms_image_category_Object}s matching the conditions
	 **/
	public function getObjects($criteria = null, $id_as_key = false) {
		$ret = array();
		$limit = $start = 0;
		$sql = 'SELECT DISTINCT c.* FROM ' . $this->db->prefix('imagecategory') . ' c LEFT JOIN '
			. $this->db->prefix('group_permission') . " l ON l.gperm_itemid=c.imgcat_id WHERE (l.gperm_name = 'imgcat_read' OR l.gperm_name = 'imgcat_write')";
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$where = $criteria->render();
			$sql .= ($where != '') ? ' AND ' . $where : '';
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$sql .= ' ORDER BY imgcat_weight, imgcat_id ASC';
		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return $ret;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			$imgcat = new icms_image_category_Object();
			$imgcat->assignVars($myrow);
			if (!$id_as_key) {
				$ret[] = &$imgcat;
			} else {
				$ret[$myrow['imgcat_id']] = &$imgcat;
			}
			unset($imgcat);
		}
		return $ret;
	}

	/**
	 * get number of {@link icms_image_category_Object}s matching certain conditions
	 *
	 * @param string $criteria conditions to match
	 * @return int number of {@link icms_image_category_Object}s matching the conditions
	 **/
	public function getCount($criteria = null) {
		$sql = 'SELECT COUNT(*) FROM ' . $this->db->prefix('imagecategory') . ' i LEFT JOIN '
			. $this->db->prefix('group_permission') . " l ON l.gperm_itemid=i.imgcat_id WHERE (l.gperm_name = 'imgcat_read' OR l.gperm_name = 'imgcat_write')";
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$where = $criteria->render();
			$sql .= ($where != '') ? ' AND ' . $where : '';
		}
		if (!$result = &$this->db->query($sql)) {
			return 0;
		}
		list($count) = $this->db->fetchRow($result);
		return $count;
	}

	/**
	 * get a list of {@link icms_image_category_Object}s matching certain conditions
	 * @param string $criteria conditions to match
	 * @return array array of {@link icms_image_category_Object}s matching the conditions
	 **/
	public function getList($groups = array(), $perm = 'imgcat_read', $display = null, $storetype = null) {
		$criteria = new icms_db_criteria_Compo();
		if (is_array($groups) && !empty($groups)) {
			$criteriaTray = new icms_db_criteria_Compo();
			foreach ( $groups as $gid) {
				$criteriaTray->add(new icms_db_criteria_Item('gperm_groupid', $gid), 'OR');
			}
			$criteria->add($criteriaTray);
			if ($perm == 'imgcat_read' || $perm == 'imgcat_write') {
				$criteria->add(new icms_db_criteria_Item('gperm_name', $perm));
				$criteria->add(new icms_db_criteria_Item('gperm_modid', 1));
			}
		}
		if (isset($display)) {
			$criteria->add(new icms_db_criteria_Item('imgcat_display', (int) ($display)));
		}
		if (isset($storetype)) {
			$criteria->add(new icms_db_criteria_Item('imgcat_storetype', $storetype));
		}
		$categories = &$this->getObjects($criteria, true);
		$ret = array();
		foreach (array_keys($categories) as $i) {
			$ret[$i] = $categories[$i]->getVar('imgcat_name');
		}
		return $ret;
	}

	/**
		* Gets list of categories for that image
		*
		* @param array  $groups  the usergroups to get the permissions for
		* @param string  $perm  the permissions to retrieve
		* @param string  $display
		* @param string  $storetype
		* @param int  $imgcat_id  the image cat id
		*
		* @return array  list of categories
		*/
	public function getCategList($groups = array(), $perm = 'imgcat_read', $display = null, $storetype = null, $imgcat_id=null) {
		$criteria = new icms_db_criteria_Compo();
		if (is_array($groups) && !empty($groups)) {
			$criteriaTray = new icms_db_criteria_Compo();
			foreach ( $groups as $gid) {
				$criteriaTray->add(new icms_db_criteria_Item('gperm_groupid', $gid), 'OR');
			}
			$criteria->add($criteriaTray);
			if ($perm == 'imgcat_read' || $perm == 'imgcat_write') {
				$criteria->add(new icms_db_criteria_Item('gperm_name', $perm));
				$criteria->add(new icms_db_criteria_Item('gperm_modid', 1));
			}
		}
		if (isset($display)) {
			$criteria->add(new icms_db_criteria_Item('imgcat_display', (int) ($display)));
		}
		if (isset($storetype)) {
			$criteria->add(new icms_db_criteria_Item('imgcat_storetype', $storetype));
		}
		if ($imgcat_id === NULL ) $imgcat_id = 0;
		$criteria->add(new icms_db_criteria_Item('imgcat_pid', $imgcat_id));
		$categories = &$this->getObjects($criteria, true);
		$ret = array();
		foreach ( array_keys($categories) as $i) {
			$ret[$i] = $categories[$i]->getVar('imgcat_name');
			$subcategories = $this->getCategList($groups, $perm, $display, $storetype, $categories[$i]->getVar('imgcat_id'));
			foreach ( array_keys($subcategories) as $j) {
				$ret[$j] = '-' . $subcategories[$j];
			}
		}

		return $ret;
	}

	/**
	 * Get the folder path or url
	 *
	 * @param integer $imgcat_id - Category ID
	 * @param string $full - if true return the full path or url else the relative path
	 * @param string $type - path or url
	 *
	 * @return string - full folder path or url
	 */
	function getCategFolder(&$imgcat, $full=true, $type='path') {
		/* As of PHP 5.3, is_a is no longer deprecated, this is an acceptable usage
		 * and is compatible with more versions of PHP. http://us2.php.net/manual/en/language.operators.type.php
		 */
		if (!is_a($imgcat, 'icms_image_category_Object')) {
			return false;
		}
		if ($imgcat->getVar('imgcat_pid') != 0) {
			$sup = $this->get($imgcat->getVar('imgcat_pid'));
			$supcateg = $this->getCategFolder($sup, false, $type);
		} else {
			$supcateg = 0;
		}
		$folder = ($supcateg) ? $supcateg . '/' : '';
		if ($full) {
			$folder = ( $type == 'path' )
					? ICMS_IMANAGER_FOLDER_PATH . '/' . $folder
					: ICMS_IMANAGER_FOLDER_URL . '/' . $folder;
		}

		return $folder . $imgcat->getVar('imgcat_foldername');
	}
}

