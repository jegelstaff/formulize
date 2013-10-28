<?php
/**
 * Creates a form attribute which is able to select a usergroup
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XoopsForms
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: formselectgroup.php 19896 2010-07-27 14:53:09Z skenow $
 */

if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}

/**
 * A select field with a choice of available groups
 *
 * @package     kernel
 * @subpackage  form
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * @deprecated	Use icms_form_elements_select_Group
 * @todo		Remove in version 1.4 - all instances have been removed from the core
 */
class XoopsFormSelectGroup extends icms_form_elements_select_Group
{
	private $_deprecated;
	/**
	 * Constructor
	 *
	 * @param	string	$caption
	 * @param	string	$name
	 * @param	bool	$include_anon	Include group "anonymous"?
	 * @param	mixed	$value	    	Pre-selected value (or array of them).
	 * @param	int		$size	        Number or rows. "1" makes a drop-down-list.
	 * @param	bool    $multiple       Allow multiple selections?
	 */
	function XoopsFormSelectGroup($caption, $name, $include_anon = false, $value = null, $size = 1, $multiple = false)
	{
		parent::__construct($caption, $name, $include_anon, $value, $size, $multiple);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_select_Group', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>