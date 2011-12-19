<?php
/**
* Creates a form select attribute (base class)
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	XoopsForms
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: formselect.php 9046 2009-07-22 14:14:40Z pesianstranger $
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
 * A select field
 * 
 * @package     kernel
 * @subpackage  form
 * 
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsFormSelect extends XoopsFormElement {

	/**
   * Options
	 * @var array   
	 * @access	private
	 */
	var $_options = array();

	/**
   * Allow multiple selections?
	 * @var	bool    
	 * @access	private
	 */
	var $_multiple = false;

	/**
     * Number of rows. "1" makes a dropdown list.
	 * @var	int 
	 * @access	private
	 */
	var $_size;

	/**
   * Pre-selcted values
	 * @var	array   
	 * @access	private
	 */
	var $_value = array();

	/**
	 * Constructor
	 * 
	 * @param	string	$caption	Caption
	 * @param	string	$name       "name" attribute
	 * @param	mixed	$value	    Pre-selected value (or array of them).
	 * @param	int		$size	    Number or rows. "1" makes a drop-down-list
   * @param	bool    $multiple   Allow multiple selections?
	 */
	function XoopsFormSelect($caption, $name, $value = null, $size = 1, $multiple = false){
		$this->setCaption($caption);
		$this->setName($name);
		$this->_multiple = $multiple;
		$this->_size = intval($size);		
		if (isset($value)) {
			$this->setValue($value);
		}
	}

	/**
	 * Are multiple selections allowed?
	 * 
   * @return	bool
	 */
	function isMultiple() {
		return $this->_multiple;
	}

	/**
	 * Get the size
	 * 
   * @return	int
	 */
	function getSize() {
		return $this->_size;
	}


	/**
	 * Get an array of pre-selected values
	 *
	 * @param	bool    $encode To sanitizer the text?
   * @return	array
	 */
	function getValue($encode = false) {
    	if (!$encode) {
        	return $this->_value;
    	}
    	$value = array();
    	foreach ($this->_value as $val) {
		    $value[] = $val ? htmlspecialchars($val, ENT_QUOTES) : $val;
    	}
    	return $value;
	}



	/**
	 * Set pre-selected values
	 * 
   * @param	$value	mixed
	 */
	function setValue($value) {
		if (is_array($value)) {
			foreach ($value as $v) {
				$this->_value[] = $v;
			}
		} else {
			$this->_value[] = $value;
		}
	}



	/**
	 * Add an option
   * 
	 * @param	string  $value  "value" attribute
   * @param	string  $name   "name" attribute
	 */
	function addOption($value, $name = ""){
		if ( $name != "" ) {
			$this->_options[$value] = $name;
		} else {
			$this->_options[$value] = $value;
		}
	}


	/**
	 * Add multiple options
	 * 
   * @param	array   $options    Associative array of value->name pairs
	 */
	function addOptionArray($options) {
		if ( is_array($options) ) {
			foreach ( $options as $k=>$v ) {
				$this->addOption($k, $v);
			}
		}
	}

	/**
	 * Get an array with all the options
	 *
	 * Note: both name and value should be sanitized. However for backward compatibility, only value is sanitized for now.
	 *
	 * @param	int     $encode     To sanitizer the text? potential values: 0 - skip; 1 - only for value; 2 - for both value and name
   * @return	array   Associative array of value->name pairs
	 */
	function getOptions($encode = false) {
    	if (!$encode) {
        	return $this->_options;
    	}
    	$value = array();
    	foreach ($this->_options as $val => $name) {
		    $value[ $encode ? htmlspecialchars($val, ENT_QUOTES) : $val ] = ($encode > 1) ? htmlspecialchars($name, ENT_QUOTES) : $name;
    	}
    	return $value;
	}

	/**
	 * Prepare HTML for output
	 * 
   * @return	string  HTML
	 */
	function render() {
		$ele_name = $this->getName();
		$ele_value = $this->getValue();
		$ele_options = $this->getOptions();
		$ret = "<select size='".$this->getSize()."'".$this->getExtra();
		if ($this->isMultiple() != false) {
			$ret .= " name='".$ele_name."[]' id='".$ele_name."[]' multiple='multiple'>\n";
		} else {
			$ret .= " name='".$ele_name."' id='".$ele_name."'>\n";
		}
		foreach ( $ele_options as $value => $name ) {
	        $ret .= "<option value='".htmlspecialchars($value, ENT_QUOTES)."'";
			if (count($ele_value) > 0 && in_array($value, $ele_value)) {
					$ret .= " selected='selected'";
			}
			$ret .= ">".$name."</option>\n";
		}
		$ret .= "</select>";
		return $ret;
	}
}

?>