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
* @version	$Id: formhidden.php 8662 2009-05-01 09:04:30Z pesianstranger $
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
 * A hidden field
 * 
 * @package     kernel
 * @subpackage  form
 * 
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsFormHidden extends XoopsFormElement {

	/**
     * Value
	 * @var	string	
	 * @access	private
	 */
	var $_value;

	/**
	 * Constructor
	 * 
	 * @param	string	$name	"name" attribute
	 * @param	string	$value	"value" attribute
	 */
	function XoopsFormHidden($name, $value) {
		$this->setName($name);
		$this->setHidden();
		$this->setValue($value);
		$this->setCaption("");
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
	 * Sets the "value" attribute
	 * 
	 * @param  $value	string
	 */
	function setValue($value) {
		$this->_value = $value;
	}

	/**
	 * Prepare HTML for output
	 * 
	 * @return	string	HTML
	 */
	function render() {
    	$ele_name = $this->getName();
		return "<input type='hidden' name='".$ele_name."' id='".$ele_name."' value='".$this->getValue()."' />";
	}
}
?>