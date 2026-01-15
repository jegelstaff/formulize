<?php
/**
 * Creates a textbox form attribut
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XoopsForms
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: formtext.php 20903 2011-02-27 02:57:18Z skenow $
 */

if(!defined('ICMS_ROOT_PATH')) {die('ImpressCMS root path not defined');}
/**
 * @package     kernel
 * @subpackage  form
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
/**
 * A simple text field
 *
 * @package     kernel
 * @subpackage  form
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * deprecated	Use icms_form_elements_Text
 * @todo		Remove in version 1.4
 */
class XoopsFormText extends icms_form_elements_Text
{

	private $_deprecated;

	/**
	 * Constructor
	 *
	 * @param	string	$caption	Caption
	 * @param	string	$name       "name" attribute
	 * @param	int		$size	    Size
	 * @param	int		$maxlength	Maximum length of text
	 * @param	string  $value      Initial text
 	 * @param	bool	$autocomplete	Whether to use autocomplete functionality in browser. Seems to have no effect in render method.
	 * @param	string	$type	Whether to treat it as a number or time when rendering
	 * @param	int		$decimals	Number of decimal places (only used if $type is 'number')
	 */
	function __construct($caption, $name, $size, $maxlength, $value = '', $autocomplete = false, $type = 'text', $decimals = 0)
	{
		parent::__construct($caption, $name, $size, $maxlength, $value, $autocomplete, $type, $decimals);
		//$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_Text', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

