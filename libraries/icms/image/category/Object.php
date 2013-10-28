<?php
/**
 * Image categories
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Image
 * @subpackage	Category
 * @version		SVN: $Id: Object.php 20016 2010-08-25 01:58:41Z skenow $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * An image category
 *
 * These categories are managed through a {@link icms_image_category_Handler} object

 * @category	ICMS
 * @package     Image
 * @subpackage	Category
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */
class icms_image_category_Object extends icms_core_Object {
	private $_imageCount;

	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->initVar('imgcat_id', XOBJ_DTYPE_INT, null, false);
		$this->initVar('imgcat_pid', XOBJ_DTYPE_INT, null, false);
		$this->initVar('imgcat_name', XOBJ_DTYPE_TXTBOX, null, true, 100);
		$this->initVar('imgcat_foldername', XOBJ_DTYPE_TXTBOX, null, true, 100);
		$this->initVar('imgcat_display', XOBJ_DTYPE_INT, 1, false);
		$this->initVar('imgcat_weight', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('imgcat_maxsize', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('imgcat_maxwidth', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('imgcat_maxheight', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('imgcat_type', XOBJ_DTYPE_OTHER, null, false);
		$this->initVar('imgcat_storetype', XOBJ_DTYPE_OTHER, null, false);
	}

	/**
	 * Set count of images in a category
	 * @param	int $value Value
	 */
	public function setImageCount($value) {
		$this->_imageCount = (int) $value;
	}

	/**
	 * Gets count of images in a category
	 * @return	int _imageCount number of images
	 */
	public function getImageCount() {
		return $this->_imageCount;
	}
}

