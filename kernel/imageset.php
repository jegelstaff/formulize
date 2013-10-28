<?php
/**
* Manage of imagesets baseclass
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: imageset.php 21142 2011-03-20 21:43:09Z skenow $
*/

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 *
 *
 * @package     kernel
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

/**
 * An imageset
 *
 * These sets are managed through a {@link XoopsImagesetHandler} object
 *
 * @package     kernel
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsImageset extends XoopsObject {
	private $_deprecated;
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_image_set_Object', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

/**
* XOOPS imageset handler class.
* This class is responsible for providing data access mechanisms to the data source
* of XOOPS imageset class objects.
*
*
* @author  Kazumi Ono <onokazu@xoops.org>
*/
class XoopsImagesetHandler extends XoopsObjectHandler {
	private $_deprecated;
	public function __construct(&$db) {
		parent::__construct($db);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_image_set_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>