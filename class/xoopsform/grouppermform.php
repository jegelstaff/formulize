<?php
/**
* Creates a group permission form
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	XoopsForms
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: grouppermform.php 20322 2010-11-04 03:57:45Z skenow $
*/

/**
 * @package     kernel
 * @subpackage  form
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}


/**
 * Renders a form for setting module specific group permissions
 *
 * @author Kazumi Ono <onokazu@myweb.ne.jp>
 * @copyright copyright (c) 2000-2003 XOOPS.org
 * @package kernel
 * @subpackage form
 */
class XoopsGroupPermForm extends icms_form_Groupperm {
	private $_deprecated;
	public function __construct($title, $modid, $permname, $permdesc, $url = "") {
		parent::__construct($title, $modid, $permname, $permdesc, $url);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_Groupperm', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

/**
 * Renders checkbox options for a group permission form
 *
 * @author Kazumi Ono <onokazu@myweb.ne.jp>
 * @copyright copyright (c) 2000-2003 XOOPS.org
 * @package kernel
 * @subpackage form
 */
class XoopsGroupFormCheckBox extends icms_form_elements_Groupperm
{
	private $_deprecated;
	public function __construct($caption, $name, $groupId, $values = null) {
		parent::__construct($caption, $name, $groupId, $values);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_Groupperm', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>