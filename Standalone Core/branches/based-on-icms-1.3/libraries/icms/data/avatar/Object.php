<?php
/**
 * Manage avatars for users
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Data
 * @subpackage	Avatar
 * @author		Kazumi Ono (aka onokazo)
 * @version		SVN: $Id:Object.php 19775 2010-07-11 18:54:25Z malanciault $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * Avatar class
 * @category	ICMS
 * @package		Data
 * @subpackage	Avatar
 *
 */
class icms_data_avatar_Object extends icms_core_Object {
	/** @var integer */
	private $_userCount;

	/**
	 * Constructor for avatar class, initializing all the properties of the class object
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->initVar('avatar_id', XOBJ_DTYPE_INT, null, false);
		$this->initVar('avatar_file', XOBJ_DTYPE_OTHER, null, false, 30);
		$this->initVar('avatar_name', XOBJ_DTYPE_TXTBOX, null, true, 100);
		$this->initVar('avatar_mimetype', XOBJ_DTYPE_OTHER, null, false);
		$this->initVar('avatar_created', XOBJ_DTYPE_INT, null, false);
		$this->initVar('avatar_display', XOBJ_DTYPE_INT, 1, false);
		$this->initVar('avatar_weight', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('avatar_type', XOBJ_DTYPE_OTHER, 0, false);
	}

	/**
	 * Sets the value for the number of users
	 * @param integer $value
	 *
	 */
	public function setUserCount($value) {
		$this->_userCount = (int) $value;
	}

	/**
	 * Gets the value for the number of users
	 * @return integer
	 */
	public function getUserCount() {
		return $this->_userCount;
	}
}

