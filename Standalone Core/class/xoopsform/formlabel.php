<?php
/**
* Creates a form text label attribute
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	XoopsForms
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: formlabel.php 8662 2009-05-01 09:04:30Z pesianstranger $
*/

if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}

/**
 * @package     kernel
 * @subpackage  form
 * 
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
/**
 * A text label
 * 
 * @package     kernel
 * @subpackage  form
 * 
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsFormLabel extends XoopsFormElement {

	/**
   * Text
	 * @var	string	
	 * @access	private
	 */
	var $_value;

	/**
	 * Constructor
	 * 
	 * @param	string	$caption	Caption
	 * @param	string	$value		Text
	 */
	function XoopsFormLabel($caption = "", $value = "", $name = ""){
		$this->setCaption($caption);
		$this->setName($name);
		$this->_value = $value;
	}

	/**
	 * Get the "value" attribute
	 * 
	 * @param	bool    $encode To sanitizer the text?
	 * @return	string
	 */
	function getValue($encode = false) {
		return $encode ? htmlspecialchars($this->_value, ENT_QUOTES) : $this->_value;
	}

	/**
	 * Prepare HTML for output
	 * 
	 * @return	string
	 */
	function render() {
		return $this->getValue();
	}
}

?>