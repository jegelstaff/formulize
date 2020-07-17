<?php
/**
 * Manage of template files
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: tplfile.php 19431 2010-06-16 20:46:34Z david-sf $
 */

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
 * @package kernel
 * @copyright copyright &copy; 2000 XOOPS.org
 */

/**
 * Base class for all templates
 *
 * @author Kazumi Ono (AKA onokazu)
 * @copyright copyright &copy; 2000 XOOPS.org
 * @package kernel
 * @deprecated	Use icms_view_template_file_Object, instead
 * @todo		Remove in version 1.4
 **/
class XoopsTplfile extends icms_view_template_file_Object
{
	private $_deprecated;

	/**
	 * constructor
	 */
	function XoopsTplfile()
	{
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_view_template_file_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

/**
 * XOOPS template file handler class.
 * This class is responsible for providing data access mechanisms to the data source
 * of XOOPS template file class objects.
 *
 *
 * @author  Kazumi Ono <onokazu@xoops.org>
 * @deprecated	Use icms_view_template_file_Handler, instead
 * @todo		Remove in version 1.4
 */
class XoopsTplfileHandler extends icms_view_template_file_Handler
{
	private $_deprecated;

	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_view_template_file_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}

}
