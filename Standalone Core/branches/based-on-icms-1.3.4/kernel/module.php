<?php
/**
 * Manage of modules
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: module.php 19118 2010-03-27 17:46:23Z skenow $
 * @deprecated
 * @todo	Remove in version 1.4
 */

if(!defined('ICMS_ROOT_PATH')){exit();}


/**
 * A Module
 *
 * @package	kernel
 * @author	Kazumi Ono 	<onokazu@xoops.org>
 * @copyright	(c) 2000-2003 The Xoops Project - www.xoops.org
 * @deprecated	Use icms_module_Object, instead
 * @todo		Remove in version 1.4
 **/
class XoopsModule extends icms_module_Object {

	private $_deprecated;

	/**
	 * Constructor
	 */
	function XoopsModule()
	{
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_module_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));

	}
}

/**
 * XOOPS module handler class.
 *
 * This class is responsible for providing data access mechanisms to the data source
 * of XOOPS module class objects.
 *
 * @package	kernel
 * @author	Kazumi Ono 	<onokazu@xoops.org>
 * @copyright	(c) 2000-2003 The Xoops Project - www.xoops.org
 * @deprecated	Use icms_module_Handler, instead
 * @todo		Remove in version 1.4
 **/
class XoopsModuleHandler extends icms_module_Handler {
	private $_deprecated;
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_module_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
