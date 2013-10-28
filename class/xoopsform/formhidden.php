<?php
/**
 * Creates a hidden form attribute
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XoopsForms
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: formhidden.php 20629 2010-12-30 16:16:12Z skenow $
 */

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
 * A hidden field
 *
 * @package     kernel
 * @subpackage  form
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * @deprecated	Use icms_form_elements_Hidden, instead
 * @todo		Remove in version 1.4
 */
class XoopsFormHidden extends icms_form_elements_Hidden {

	private $_deprecated;
	
	/**
	 * Constructor
	 *
	 * @param	string	$name	"name" attribute
	 * @param	string	$value	"value" attribute
	 */
	function __construct($name, $value) {
		parent::__construct($name, $value);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_Hidden', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}