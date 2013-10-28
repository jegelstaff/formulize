<?php
/**
 * Creates a checkbox form attribut
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XoopsForms
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: formcheckbox.php 20020 2010-08-25 14:25:59Z malanciault $
 */

if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}
/**
 * @package     kernel
 * @subpackage  form
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
/**
 * One or more Checkbox(es)
 *
 * @package     kernel
 * @subpackage  form
 *
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsFormCheckBox extends icms_form_elements_Checkbox {
	private $_deprecated;
	public function __construct($caption, $name, $value = null, $delimeter = "&nbsp;") {
		parent::__construct($caption, $name, $value, $delimeter);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_Checkbox', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>