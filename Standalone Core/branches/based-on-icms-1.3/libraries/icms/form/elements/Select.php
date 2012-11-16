<?php
/**
 * Creates a form select field (base class)
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version		SVN: $Id: Select.php 19892 2010-07-27 00:12:10Z skenow $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * A select field
 *
 * @category	ICMS
 * @package     Form
 * @subpackage  Elements
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */
class icms_form_elements_Select extends icms_form_Element {

	/**
	 * Options
	 * @var array
	 */
	private $_options = array();

	/**
	 * Allow multiple selections?
	 * @var	bool
	 */
	private $_multiple = false;

	/**
	 * Number of rows. "1" makes a dropdown list.
	 * @var	int
	 */
	private $_size;

	/**
	 * Pre-selcted values
	 * @var	array
	 */
	private $_value = array();

	/**
	 * Constructor
	 *
	 * @param	string	$caption	Caption
	 * @param	string	$name       "name" attribute
	 * @param	mixed	$value	    Pre-selected value (or array of them).
	 * @param	int		$size	    Number or rows. "1" makes a drop-down-list
	 * @param	bool    $multiple   Allow multiple selections?
	 */
	public function __construct($caption, $name, $value = null, $size = 1, $multiple = false) {
		$this->setCaption($caption);
		$this->setName($name);
		$this->_multiple = $multiple;
		$this->_size = (int) ($size);
		if (isset($value)) {
			$this->setValue($value);
		}
	}

	/**
	 * Are multiple selections allowed?
	 *
	 * @return	bool
	 */
	public function isMultiple() {
		return $this->_multiple;
	}

	/**
	 * Get the size
	 *
	 * @return	int
	 */
	public function getSize() {
		return $this->_size;
	}

	/**
	 * Get an array of pre-selected values
	 *
	 * @param	bool    $encode To sanitizer the text?
	 * @return	array
	 */
	public function getValue($encode = false) {
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
	public function setValue($value) {
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
	public function addOption($value, $name = ""){
		if ($name != "") {
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
	public function addOptionArray($options) {
		if (is_array($options)) {
			foreach ($options as $k=>$v) {
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
	public function getOptions($encode = false) {
		if (!$encode) {
			return $this->_options;
		}
		$value = array();
		foreach ($this->_options as $val => $name) {
			$value[$encode ? htmlspecialchars($val, ENT_QUOTES) : $val]
				= ($encode > 1) ? htmlspecialchars($name, ENT_QUOTES) : $name;
		}
		return $value;
	}

	/**
	 * Prepare HTML for output
	 *
	 * @return	string  HTML
	 */
	public function render() {
		$ele_name = $this->getName();
		$ele_value = $this->getValue();
		$ele_options = $this->getOptions();
		$ret = "<select size='" . $this->getSize() . "' " . $this->getExtra(); // extra space added by Freeform Solutions, Nov 15, 2012, to fix HTML validation
		if ($this->isMultiple() != false) {
			$ret .= " name='" . $ele_name . "[]' id='" . $ele_name . "' multiple='multiple'>\n"; // [] removed from ID by Freeform Solutions, Nov 15, 2012, to fix HTML validation and make javascript select by id work
		} else {
			$ret .= " name='" . $ele_name . "' id='" . $ele_name . "'>\n";
		}
		foreach ( $ele_options as $value => $name ) {
			$ret .= "<option value='" . htmlspecialchars($value, ENT_QUOTES) . "'";
			if (count($ele_value) > 0 && in_array($value, $ele_value)) {
				$ret .= " selected='selected'";
			}
			$ret .= ">" . $name . "</option>\n";
		}
		$ret .= "</select>";
		return $ret;
	}
}

