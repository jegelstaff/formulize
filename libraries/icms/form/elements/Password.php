<?php
/**
 * Creates a form password field
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version		$Id: Password.php 19891 2010-07-24 01:49:03Z skenow $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * A password field
 *
 * @author 	Kazumi Ono	<onokazu@xoops.org>
 * @category	ICMS
 * @package 	Form
 * @subpackage 	Elements
 */
class icms_form_elements_Password extends icms_form_Element {
	/**
	 * Size of the field.
	 * @var 		int
	 */
	private $_size;

	/**
	 * Maximum length of the text
	 * @var 		int
	 */
	private $_maxlength;

	/**
	 * Initial content of the field.
	 * @var 		string
	 */
	private $_value;

	/**
	 * Turns off the browser autocomplete function.
	 * @var 		boolean
	 */
	public  $autocomplete = false;

	/**
	 * Initial content of the field.
	 * @var 		string
	 */
	private $_classname;

	/**
	 * Constructor
	 *
	 * @param	string	$caption	Caption
	 * @param	string	$name		"name" attribute
	 * @param	int		$size		Size of the field
	 * @param	int		$maxlength	Maximum length of the text
	 * @param	int		$value		Initial value of the field.
	 * 							<b>Warning:</b> this is readable in cleartext in the page's source!
	 */
	public function __construct($caption, $name, $size, $maxlength, $value = '', $autocomplete = false, $classname = '') {
		$this->setCaption($caption);
		$this->setName($name);
		$this->_size = (int) ($size);
		$this->_maxlength = (int) ($maxlength);
		$this->setValue($value);
		$this->autoComplete = !empty($autocomplete);
		$this->setClassName($classname);
	}

	/**
	 * Get the field size
	 *
	 * @return	int
	 */
	public function getSize() {
		return $this->_size;
	}

	/**
	 * Get the max length
	 *
	 * @return	int
	 */
	public function getMaxlength() {
		return $this->_maxlength;
	}

	/**
	 * Get the "value" attribute
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
	 * @param	$value	string
	 */
	public function setValue($value) {
		$this->_value = $value;
	}

	/**
	 * Set the initial value
	 *
	 * @param	$value	string
	 */
	public function setClassName($classname) {
		$this->_classname = $classname;
	}

	/**
	 * Get the "class" attribute
	 *
	 * @param	bool    $encode To sanitizer the text?
	 * @return	string
	 */
	public function getClassName($encode = false) {
		return $encode ? htmlspecialchars($this->_classname, ENT_QUOTES) : $this->_classname;
	}

	/**
	 * Prepare HTML for output
	 *
	 * @return	string	HTML
	 */
	public function render() {
		global $icmsConfigUser;
		if ($icmsConfigUser['pass_level'] > 20 ) {
			icms_PasswordMeter();
		}
		$ele_name = $this->getName();
		return "<input class='" . $this->getClassName()
			. "' type='password' name='" . $ele_name
			. "' id='" . $ele_name
			. "' size='" . $this->getSize()
			. "' maxlength='" . $this->getMaxlength()
			. "' value='" . $this->getValue() . "'" . $this->getExtra() . " " . ($this->autoComplete ? "" : "autocomplete='off' ")
			. "/>";
	}
}

