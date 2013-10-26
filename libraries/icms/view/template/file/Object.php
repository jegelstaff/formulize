<?php
/**
 * Template file object
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		View
 * @subpackage	Template
 * @version		SVN: $Id: Object.php 19775 2010-07-11 18:54:25Z malanciault $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * Base class for all templates
 *
 * @author Kazumi Ono (AKA onokazu)
 * @category	ICMS
 * @package		View
 * @subpackage	Template
 **/
class icms_view_template_file_Object extends icms_core_Object {

	/**
	 * constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->initVar('tpl_id', XOBJ_DTYPE_INT, null, false);
		$this->initVar('tpl_refid', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('tpl_tplset', XOBJ_DTYPE_OTHER, null, false);
		$this->initVar('tpl_file', XOBJ_DTYPE_TXTBOX, null, true, 100);
		$this->initVar('tpl_desc', XOBJ_DTYPE_TXTBOX, null, false, 100);
		$this->initVar('tpl_lastmodified', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('tpl_lastimported', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('tpl_module', XOBJ_DTYPE_OTHER, null, false);
		$this->initVar('tpl_type', XOBJ_DTYPE_OTHER, null, false);
		$this->initVar('tpl_source', XOBJ_DTYPE_SOURCE, null, false);
	}

	/**
	 * Gets Template Source
	 */
	public function getSource()	{
		return $this->getVar('tpl_source');
	}

	/**
	 * Gets Last Modified timestamp
	 */
	public function getLastModified()	{
		return $this->getVar('tpl_lastmodified');
	}
}

