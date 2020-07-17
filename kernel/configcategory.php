<?php
/**
 * Manage configuration categories
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		core
 * @subpackage	config
 * @since		XOOPS
 * @author		Kazumi Ono (aka onokazo)
 * @author		http://www.xoops.org The XOOPS Project
 * @version		$Id: configcategory.php 19431 2010-06-16 20:46:34Z david-sf $
 */

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
 * A category of configs
 *
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 *
 * @package     kernel
 * @subpackage	config
 * @deprecated	Use icms_config_category_Object, instead
 * @todo		Remove in version 1.4
 */
class XoopsConfigCategory extends icms_config_category_Object
{
	private $_deprecated;
	/**
	 * Constructor
	 *
	 */
	function XoopsConfigCategory()
	{
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_config_category_Object', sprintf(_CORE_REMOVE_IN_VERISON, '1.4'));

	}
}

/**
 * XOOPS configuration category handler class.
 *
 * This class is responsible for providing data access mechanisms to the data source
 * of XOOPS configuration category class objects.
 *
 * @author  Kazumi Ono <onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 *
 * @package     kernel
 * @subpackage  config
 * @deprecated	Use icms_config_category_Handler, instead
 * @todo		Remove in version 1.4
 */
class XoopsConfigCategoryHandler extends icms_config_category_Handler
{

	private $_deprecated;
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_config_category_Handler', sprintf(_CORE_REMOVE_IN_VERISON, '1.4'));
	}

}

