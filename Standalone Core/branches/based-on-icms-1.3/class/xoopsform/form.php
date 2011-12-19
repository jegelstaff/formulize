<?php
/**
 * Creates a form object (Base Class)
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XoopsForms
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: form.php 20634 2010-12-30 22:48:01Z skenow $
 */

/**
 * Abstract base class for forms
 *
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @author  Taiwen Jiang    <phppp@users.sourceforge.net>
 * @copyright	copyright (c) 2000-2007 XOOPS.org
 *
 * @package     kernel
 * @subpackage  form
 * @deprecated	Use icms_form_Base, instead
 * @todo		Remove in version 1.4
 */
abstract class XoopsForm extends icms_form_Base {
	private $_deprecated;
	
	public function XoopsForm($title, $name, $action, $method = "post", $addtoken = false) {
		self::__construct($title, $name, $action, $method, $addtoken);
	}
	public function __construct($title, $name, $action, $method = "post", $addtoken = false) {
		parent::__construct($title, $name, $action, $method, $addtoken);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_Button', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>