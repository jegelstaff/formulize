<?php
/**
 * Manage images
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Image
 * @version		SVN: $Id:Handler.php 19775 2010-07-11 18:54:25Z malanciault $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * Image handler class.
 *
 * This class is responsible for providing data access mechanisms to the data source
 * of image class objects.
 *
 * @category	ICMS
 * @package		Image
 * @author		Kazumi Ono 	<onokazu@xoops.org>
 */
class icms_image_Handler extends icms_core_ObjectHandler {

	/**
	 * Create a new {@link icms_image_Object}
	 *
	 * @param   boolean $isNew  Flag the object as "new"
	 * @return  object
	 **/
	public function &create($isNew = true) {
		$image = new icms_image_Object();
		if ($isNew) {
			$image->setNew();
		}
		return $image;
	}

	/**
	 * Load a {@link icms_image_Object} object from the database
	 *
	 * @param   int     $id     ID
	 * @param   boolean $getbinary
	 * @return  object  {@link icms_image_Object}, FALSE on fail
	 **/
	public function &get($id, $getbinary=true) {
		$image = false;
		$id = (int) $id;
		if ($id > 0) {
			$sql = "SELECT i.*, b.image_body FROM "
				. $this->db->prefix('image') . " i LEFT JOIN "
				. $this->db->prefix('imagebody')
				. " b ON b.image_id=i.image_id WHERE i.image_id='" . $id . "'";
			if (!$result = $this->db->query($sql)) {
				return $image;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$image = new icms_image_Object();
				$image->assignVars($this->db->fetchArray($result));
			}
		}
		return $image;
	}

	/**
	 * Write a {@link icms_image_Object} object to the database
	 *
	 * @param   object  &$image {@link icms_image_Object}
	 * @return  bool
	 **/
	public function insert(&$image) {
		/* As of PHP 5.3, is_a is no longer deprecated, this is an acceptable usage
		 * and is compatible with more versions of PHP. http://us2.php.net/manual/en/language.operators.type.php
		 */
		if (!is_a($image, 'icms_image_Object')) {
			return false;
		}

		if (!$image->isDirty()) {
			return true;
		}
		if (!$image->cleanVars()) {
			return false;
		}
		foreach ( $image->cleanVars as $k => $v) {
			${$k} = $v;
		}
		if ($image->isNew()) {
			$image_id = $this->db->genId('image_image_id_seq');
			$sql = sprintf(
				"INSERT INTO %s (image_id, image_name, image_nicename, image_mimetype, image_created, image_display, image_weight, imgcat_id) VALUES ('%u', %s, %s, %s, '%u', '%u', '%u', '%u')",
				$this->db->prefix('image'),
				(int) $image_id,
				$this->db->quoteString($image_name),
				$this->db->quoteString($image_nicename),
				$this->db->quoteString($image_mimetype),
				time(),
				(int) $image_display,
				(int) $image_weight,
				(int) $imgcat_id
			);
			if (!$result = $this->db->queryF($sql)) {
				return false;
			}
			if (empty($image_id)) {
				$image_id = $this->db->getInsertId();
			}
			if (isset($image_body) && $image_body != '') {
				$sql = sprintf(
					"INSERT INTO %s (image_id, image_body) VALUES ('%u', %s)",
					$this->db->prefix('imagebody'),
					(int) ($image_id),
					$this->db->quoteString($image_body)
				);
				if (!$result = $this->db->queryF($sql)) {
					$sql = sprintf("DELETE FROM %s WHERE image_id = '%u'", $this->db->prefix('image'), (int) ($image_id));
					$this->db->query($sql);
					return false;
				}
			}
			$image->assignVar('image_id', $image_id);
		} else {
			$sql = sprintf(
				"UPDATE %s SET image_name = %s, image_nicename = %s, image_display = '%u', image_weight = '%u', imgcat_id = '%u' WHERE image_id = '%u'",
				$this->db->prefix('image'),
				$this->db->quoteString($image_name),
				$this->db->quoteString($image_nicename),
				(int) $image_display,
				(int) $image_weight,
				(int) $imgcat_id,
				(int) $image_id
			);
			if (!$result = $this->db->queryF($sql)) {
				return false;
			}
			if (isset($image_body) && $image_body != '') {
				$sql = sprintf(
					"UPDATE %s SET image_body = %s WHERE image_id = '%u'",
					$this->db->prefix('imagebody'),
					$this->db->quoteString($image_body),
					(int) $image_id
				);
				if (!$result = $this->db->queryF($sql)) {
					$this->db->query(sprintf("DELETE FROM %s WHERE image_id = '%u'", $this->db->prefix('image'), (int) $image_id));
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Delete an image from the database
	 *
	 * @param   object  &$image {@link icms_image_Object}
	 * @return  bool
	 **/
	public function delete(&$image) {
		/* As of PHP 5.3, is_a is no longer deprecated, this is an acceptable usage
		 * and is compatible with more versions of PHP. http://us2.php.net/manual/en/language.operators.type.php
		 */
		if (!is_a($image, 'icms_image_Object')) {
			return false;
		}

		$id = (int) ($image->getVar('image_id'));
		$sql = sprintf("DELETE FROM %s WHERE image_id = '%u'", $this->db->prefix('image'), $id);
		if (!$result = $this->db->query($sql)) {
			return false;
		}
		$sql = sprintf("DELETE FROM %s WHERE image_id = '%u'", $this->db->prefix('imagebody'), $id);
		$this->db->query($sql);
		return true;
	}

	/**
	 * Load {@link icms_image_Object}s from the database
	 *
	 * @param   object  $criteria   {@link icms_db_criteria_Element}
	 * @param   boolean $id_as_key  Use the ID as key into the array
	 * @param   boolean $getbinary
	 * @return  array   Array of {@link icms_image_Object} objects
	 **/
	public function getObjects($criteria = null, $id_as_key = false, $getbinary = false) {
		$ret = array();
		$limit = $start = 0;
		if ($getbinary) {
			$sql = "SELECT i.*, b.image_body FROM ".$this->db->prefix('image')." i LEFT JOIN ".$this->db->prefix('imagebody')." b ON b.image_id=i.image_id";
		} else {
			$sql = "SELECT * FROM ".$this->db->prefix('image');
		}
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= " ".$criteria->renderWhere();
			$sort = !in_array($criteria->getSort(), array('image_id', 'image_created', 'image_mimetype', 'image_display', 'image_weight'))
					? 'image_weight'
					: $criteria->getSort();
			$sql .= " ORDER BY " . $sort . " " . $criteria->getOrder();
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return $ret;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			$image = new icms_image_Object();
			$image->assignVars($myrow);
			if (!$id_as_key) {
				$ret[] = &$image;
			} else {
				$ret[$myrow['image_id']] = &$image;
			}
			unset($image);
		}
		return $ret;
	}

	/**
	 * Count some images
	 *
	 * @param   object  $criteria   {@link icms_db_criteria_Element}
	 * @return  int
	 **/
	public function getCount($criteria = null) {
		$sql = 'SELECT COUNT(*) FROM '.$this->db->prefix('image');
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' '.$criteria->renderWhere();
		}
		if (!$result = &$this->db->query($sql)) {
			return 0;
		}
		list($count) = $this->db->fetchRow($result);
		return $count;
	}

	/**
	 * Get a list of images
	 *
	 * @param   int     $imgcat_id
	 * @param   bool    $image_display
	 * @return  array   Array of {@link icms_image_Object} objects
	 **/
	public function getList($imgcat_id, $image_display = null) {
		$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('imgcat_id', (int) ($imgcat_id)));
		if (isset($image_display)) {
			$criteria->add(new icms_db_criteria_Item('image_display', (int) ($image_display)));
		}
		$images = &$this->getObjects($criteria, false, true);
		$ret = array();
		foreach ( array_keys($images) as $i) {
			$ret[$images[$i]->getVar('image_name')] = $images[$i]->getVar('image_nicename');
		}
		return $ret;
	}
}

