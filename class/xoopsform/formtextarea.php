<?php
/**
 * Creates a textarea form attribut
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XoopsForms
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: formtextarea.php 19900 2010-07-27 16:46:11Z skenow $
 */

if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}

/**
 * A textarea
 *
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * @deprecated	Use icms_form_elements_Textarea
 * @todo		Remove in version 1.4 - all instances have been removed from the core
 */
class XoopsFormTextArea extends icms_form_elements_Textarea {

	private $_deprecated;
	/**
	 * Constuctor
	 *
	 * @param	string  $caption    caption
	 * @param	string  $name       name
	 * @param	string  $value      initial content
	 * @param	int     $rows       number of rows
	 * @param	int     $cols       number of columns
	 */
	function XoopsFormTextArea($caption, $name, $value = "", $rows = 5, $cols = 50) {
		parent::__construct($caption, $name, $value, $rows, $cols);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_Textarea', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
