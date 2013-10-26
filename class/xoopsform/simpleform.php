<?php
/**
 * Creates a simple form
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XoopsForms
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: simpleform.php 22560 2011-09-05 21:45:09Z skenow $
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');
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
 * Form that will output as a simple HTML form with minimum formatting
 *
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 *
 * @package     kernel
 * @subpackage  form
 */
class XoopsSimpleForm extends icms_form_Simple {
	private $_deprecated;
	public function __construct($title, $name, $action, $method = "post", $addtoken = false) {
		parent::__construct($title, $name, $action, $method, $addtoken);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_Simple', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>