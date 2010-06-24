<?php
/**
* Creates a checkbox form attribut
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	XoopsForms
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: formcheckbox.php 8662 2009-05-01 09:04:30Z pesianstranger $
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
 * One or more Checkbox(es)
 *
 * @package     kernel
 * @subpackage  form
 *
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsFormCheckBox extends XoopsFormElement {

	/**
   * Availlable options
	 * @var array
	 * @access	private
	 */
	var $_options = array();

	/**
   * pre-selected values in array
	 * @var	array
	 * @access	private
	 */
	var $_value = array();

	/**
     * HTML to seperate the elements
	 * @var	string  
	 * @access  private
	 */
	var $_delimeter;

	/**
	 * Constructor
	 *
   * @param	string  $caption
   * @param	string  $name
   * @param	mixed   $value  Either one value as a string or an array of them.
	 */
	function XoopsFormCheckBox($caption, $name, $value = null, $delimeter = "&nbsp;"){
		$this->setCaption($caption);
		$this->setName($name);
		if (isset($value)) {
			$this->setValue($value);
		}
		$this->_delimeter = $delimeter;
	}

	/**
	 * Get the "value"
	 *
	 * @param	bool    $encode   Would you like to sanitize the text?
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
	 * Set the "value"
	 *
   * @param	array
	 */
	function setValue($value) {
		$this->_value = array();
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
   * @param	string  $value
   * @param	string  $name
	 */
	function addOption($value, $name = "") {
		if ($name != "") {
			$this->_options[$value] = $name;
		} else {
			$this->_options[$value] = $value;
		}
	}

	/**
	 * Add multiple Options at once
	 *
   * @param	array   $options    Associative array of value->name pairs
	 */
	function addOptionArray($options) {
		if ( is_array($options) ) {
			foreach ( $options as $k => $v ) {
				$this->addOption($k, $v);
			}
		}
	}

	/**
	 * Get an array with all the options
	 *
	 * @param	int     $encode     To sanitize the text? potential values: 0 - skip; 1 - only for value; 2 - for both value and name
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
	 * Get the delimiter of this group
	 * 
	 * @param	bool    $encode To sanitizer the text?
   * @return	string  The delimiter
	 */
	function getDelimeter($encode = false) {
		return $encode ? htmlspecialchars(str_replace('&nbsp;', ' ', $this->_delimeter)) : $this->_delimeter;
	}

	/**
	 * prepare HTML for output
	 *
   * @return	string
	 */
	function render() {
		$ret = "";
		$ele_name = $this->getName();
		$ele_value = $this->getValue();
		$ele_options = $this->getOptions();
		$ele_extra = $this->getExtra();
		$ele_delimeter = $this->getDelimeter();
		if ( count($ele_options) > 1 && substr($ele_name, -2, 2) != "[]" ) {
			$ele_name = $ele_name."[]";
			$this->setName($ele_name);
		}
		foreach ( $ele_options as $value => $name ) {
			$ret .= "<input type='checkbox' name='".$ele_name."' value='".htmlspecialchars($value, ENT_QUOTES)."'";
			if (count($ele_value) > 0 && in_array($value, $ele_value)) {
				$ret .= " checked='checked'";
			}
			$ret .= $ele_extra." />".$name.$ele_delimeter."\n";
		}
		return $ret;
	}
}
?>