<?php
/**
 * Creates a form radiobutton attribute (base class)
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version		$Id: Radio.php 19891 2010-07-24 01:49:03Z skenow $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * A Group of radiobuttons
 *
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 */
class icms_form_elements_Radio extends icms_form_Element {

	/**
	 * Array of Options
	 * @var	array
	 */
	private $_options = array();

	/**
	 * Pre-selected value
	 * @var	string
	 */
	private $_value = null;

	/**
	 * HTML to separate the elements
	 * @var	string
	 */
	private $_delimeter;

	/**
	 * Constructor
	 *
	 * @param	string	$caption	Caption
	 * @param	string	$name		"name" attribute
	 * @param	string	$value		Pre-selected value
	 */
	public function __construct($caption, $name, $value = null, $delimeter = "") {
		$this->setCaption($caption);
		$this->setName($name);
		if (isset($value)) {
			$this->setValue($value);
		}
		$this->_delimeter = $delimeter;
	}

	/**
	 * Get the "value" attribute
	 *
	 * @param	bool    $encode To sanitizer the text?
	 * @return	string
	 */
	public function getValue($encode = false) {
		return ($encode && $this->_value !== null)
			? htmlspecialchars($this->_value, ENT_QUOTES)
			: $this->_value;
	}

	/**
	 * Set the pre-selected value
	 *
	 * @param	$value	string
	 */
	public function setValue($value) {
		$this->_value = $value;
	}

	/**
	 * Add an option
	 *
	 * @param	string	$value	"value" attribute - This gets submitted as form-data.
	 * @param	string	$name	"name" attribute - This is displayed. If empty, we use the "value" instead.
	 */
	public function addOption($value, $name = "") {
		if ($name != "") {
			$this->_options[$value] = $name;
		} else {
			$this->_options[$value] = $value;
		}
	}

	/**
	 * Adds multiple options
	 *
	 * @param	array	$options	Associative array of value->name pairs.
	 */
	function addOptionArray($options) {
		if (is_array($options)) {
			foreach ($options as $k => $v) {
				$this->addOption($k, $v);
			}
		}
	}

	/**
	 * Get an array with all the options
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
			$value[$encode ? htmlspecialchars($val, ENT_QUOTES) : $val]
				= ($encode > 1) ? htmlspecialchars($name, ENT_QUOTES) : $name;
		}
		return $value;
	}

	/**
	 * Get the delimiter of this group
	 *
	 * @param	bool    $encode To sanitizer the text?
	 * @return	string  The delimiter
	 */
	public function getDelimeter($encode = false) {
		return $encode ? htmlspecialchars(str_replace('&nbsp;', ' ', $this->_delimeter)) : $this->_delimeter;
	}

	/**
	 * Prepare HTML for output
	 *
	 * @return	string	HTML
	 */
	public function render() {
		$ret = "";
		$ele_name = $this->getName();
		$ele_value = $this->getValue();
		$ele_options = $this->getOptions();
		$ele_extra = $this->getExtra();
		$ele_delimeter = $this->getDelimeter();
		static $counter = 0;
		foreach ($ele_options as $value => $name) {
			$counter++;
			$ret .= "<input type='radio' id='" . $ele_name."-".$counter . "' name='" . $ele_name . "' value='" . htmlspecialchars($value, ENT_QUOTES) . "'";
			if ($value == $ele_value) {
				$ret .= " checked='checked'";
			}
			$ret .= $ele_extra . " /><label for='" . $ele_name."-".$counter . "'>" . $name . "</label>" . $ele_delimeter . "\n";
		}
		return $ret;
	}
}

