<?php
/**
 * Creates a form datatime object
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XoopsForms
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: formdatetime.php 20020 2010-08-25 14:25:59Z malanciault $
 */

if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}

/**
 *
 *
 * @package     kernel
 * @subpackage  form
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

/**
 * Date and time selection field
 *
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 *
 * @package     kernel
 * @subpackage  form
 */
class XoopsFormDateTime extends icms_form_elements_Datetime {
	private $_deprecated;
	public function __construct($caption, $name, $size = 15, $value=0) {
		parent::__construct($caption, $name, $size, $value);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_Datetime', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

?>