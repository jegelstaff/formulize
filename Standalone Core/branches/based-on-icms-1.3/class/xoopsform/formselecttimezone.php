<?php

/**
 * Creates a form with selectable timezone
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XoopsForms
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: formselecttimezone.php 20322 2010-11-04 03:57:45Z skenow $
 */

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");
/**
 * @package     kernel
 * @subpackage  form
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */


/**
 * A select box with timezones
 *
 * @package     kernel
 * @subpackage  form
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsFormSelectTimezone extends icms_form_elements_select_Timezone {
	private $_deprecated;
	public function __construct($caption, $name, $value = null, $size = 1) {
		parent::__construct($caption, $name, $value, $size);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_select_Timezone', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>