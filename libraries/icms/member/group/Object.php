<?php
/**
 * Manage groups
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Member
 * @subpackage	Group
 * @author		Kazumi Ono (aka onokazo)
 * @version		SVN: $Id: Object.php 19775 2010-07-11 18:54:25Z malanciault $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * a group of users
 *
 * @author		Kazumi Ono <onokazu@xoops.org>
 * @category	ICMS
 * @package		Member
 * @subpackage	Group
 */
class icms_member_group_Object extends icms_core_Object {
	/**
	 * constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->initVar('groupid', XOBJ_DTYPE_INT, null, false);
		$this->initVar('name', XOBJ_DTYPE_TXTBOX, null, true, 100);
		$this->initVar('description', XOBJ_DTYPE_TXTAREA, null, false);
		$this->initVar('group_type', XOBJ_DTYPE_OTHER, null, false);
	}
}
