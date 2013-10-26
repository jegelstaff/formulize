<?php
/**
 * Creates a basic form element (Base Class)
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XoopsForms
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: formelement.php 19993 2010-08-23 20:57:57Z malanciault $
 */

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");
/**
 *
 *
 * @package     kernel
 * @subpackage  form
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @author      Taiwen Jiang    <phppp@users.sourceforge.net>
 * @copyright	copyright (c) 2000-2007 XOOPS.org
 */

/**
 * Abstract base class for form elements
 *
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @author  Taiwen Jiang    <phppp@users.sourceforge.net>
 * @copyright	copyright (c) 2000-2007 XOOPS.org
 *
 * @package     kernel
 * @subpackage  form
 */
abstract class XoopsFormElement extends icms_form_Element{
	private $_deprecated;
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_Element', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>