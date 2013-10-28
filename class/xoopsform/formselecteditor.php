<?php

/**
 * Creates a form attribute which is able to select an editor
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XoopsForms
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: formselecteditor.php 20030 2010-08-25 17:33:59Z malanciault $
 */
/**
 * base class
 */

/**
 * A select box with available editors
 *
 * @package     kernel
 * @subpackage  form
 *
 * @author	    phppp (D.J.)
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsFormSelectEditor extends icms_form_elements_select_Editor {
	private $_errors;
	public function __construct(&$form, $name="editor", $value=null, $noHtml=false) {
		parent::__construct($form, $name, $value, $noHtml);
		$this->_errors = icms_core_Debug::setDeprecated('icms_form_elements_select_Editor', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>