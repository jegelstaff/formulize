<?php
/**
 * Creates a form attribut styled by the theme
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XoopsForms
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: themeform.php 20040 2010-08-25 19:09:41Z malanciault $
 */
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
 * base class
 */
defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * Form that will output as a theme-enabled HTML table
 *
 * Also adds JavaScript to validate required fields
 *
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 *
 * @package     kernel
 * @subpackage  form
 */
class XoopsThemeForm extends icms_form_Theme {
	private $_deprecated;
	public function __construct($title, $name, $action, $method = "post", $addtoken = false) {
		parent::__construct($title, $name, $action, $method, $addtoken);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_Theme', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

?>