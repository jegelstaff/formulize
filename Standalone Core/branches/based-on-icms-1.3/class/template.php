<?php
/**
 * @deprecated	Use libraries/icms/view/Tpl.php, instead
 * @todo		Remove this in version 1.4
 * The templates class that extends Smarty
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @subpackage Templates
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: template.php 19473 2010-06-18 21:42:21Z david-sf $
 */

if (!defined('SMARTY_DIR')) {
	exit();
}
/**
 * Base class: Smarty template engine
 */
require_once SMARTY_DIR.'Smarty.class.php';

/**
 * Template engine
 *
 * @package		kernel
 * @subpackage	core
 *
 * @author		Kazumi Ono 	<onokazu@xoops.org>
 * @copyright	(c) 2000-2003 The Xoops Project - www.xoops.org
 * @deprecated	Use icms_view_Tpl, instead
 * @todo		Remove in version 1.4 - there are no other occurrences in the core
 */
class XoopsTpl extends icms_view_Tpl {
	private $_deprecated;
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_view_Tpl', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}

}

/**
 * function to update compiled template file in templates_c folder
 *
 * @param   string  $tpl_id
 * @param   boolean $clear_old
 * @return  boolean
 * @deprecated	Use icms_view_Tpl::template_touch instead
 * @todo		Remove in version 1.4 - there are no other occurrences in the core
 **/
function xoops_template_touch($tpl_id, $clear_old = true) {
	icms_core_Debug::setDeprecated('icms_view_Tpl::template_touch($tplid)', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	icms_view_Tpl::template_touch($tplid);
}

/**
 * Clear the module cache
 *
 * @param   int $mid    Module ID
 * @return
 * @deprecated	Use icms_view_Tpl::template_clear_module_cache, instead
 * @todo		Remove in version 1.4 - there are no other occurrences in the core
 **/
function xoops_template_clear_module_cache($mid)
{
	icms_core_Debug::setDeprecated('icms_view_Tpl::template_clear_module_cache($mid)', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	icms_view_Tpl::template_clear_module_cache($mid);
}
?>