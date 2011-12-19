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
 * @version		SVN: $Id: Object.php 19999 2010-08-24 23:05:10Z skenow $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * A category of configs
 *
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @category	ICMS
 * @package     Config
 * @subpackage	Category
 */
class icms_config_category_Object extends icms_core_Object {
	/**
	 * Constructor
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->initVar('confcat_id', XOBJ_DTYPE_INT, null);
		$this->initVar('confcat_name', XOBJ_DTYPE_OTHER, null);
		$this->initVar('confcat_order', XOBJ_DTYPE_INT, 0);
	}
}

