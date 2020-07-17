<?php
/**
 * Creates a button form attribut
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version	$Id: Button.php 19883 2010-07-23 01:29:20Z skenow $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * A button
 *
 * @author		Kazumi Ono	<onokazu@xoops.org>
 * @category	ICMS
 * @package     Form
 * @subpackage  Elements
 */
class icms_form_elements_Button extends icms_form_Element {

	/**
	 * Value
	 * @var	string
	 * @access	private
	 */
	private $_value;

	/**
	 * Type of the button. This could be either "button", "submit", or "reset"
	 * @var	string
	 * @access	private
	 */
	private $_type;

	/**
	 * Constructor
	 *
	 * @param	string  $caption    Caption
	 * @param	string  $name
	 * @param	string  $value
	 * @param	string  $type       Type of the button.
	 * This could be either "button", "submit", or "reset"
	 */
	public function __construct($caption, $name, $value = "", $type = "button") {
		$this->setCaption($caption);
		$this->setName($name);
		$this->_type = $type;
		$this->setValue($value);
	}

	/**
	 * Get the initial value
	 *
	 * @param	bool    $encode To sanitizer the text?
	 * @return	string
	 */
	public function getValue($encode = false) {
		return $encode ? htmlspecialchars($this->_value, ENT_QUOTES) : $this->_value;
	}

	/**
	 * Set the initial value
	 *
	 * @return	string
	 */
	public function setValue($value) {
		$this->_value = $value;
	}

	/**
	 * Get the type
	 *
	 * @return	string
	 */
	public function getType() {
		return in_array(strtolower($this->_type), array("button", "submit", "reset")) ? $this->_type : "button";
	}

	/**
	 * prepare HTML for output
	 *
	 * @return	string
	 */
	public function render() {
		return "<input type='".$this->getType()."' class='formButton' name='".$this->getName()."'  id='".$this->getName()."' value='".$this->getValue()."'".$this->getExtra()." />";
	}
}
