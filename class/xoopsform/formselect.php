<?php
/**
 * Creates a form select attribute (base class)
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XoopsForms
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: formselect.php 22373 2011-08-25 12:12:44Z mcdonald3072 $
 */

if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}


/**
 * A select field
 *
 * @package     kernel
 * @subpackage  form
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * @deprecated	Use icms_form_element_Select instead
 * @todo		Remove in version 1.4
 */
class XoopsFormSelect extends icms_form_elements_Select {

	private $_deprecated;

	function XoopsFormSelect($caption, $name, $value = null, $size = 1, $multiple = false){
		parent::__construct($caption, $name, $value, $size, $multiple);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_Select', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

