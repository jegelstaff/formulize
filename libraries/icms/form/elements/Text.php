<?php
/**
 * Creates a textbox form field
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version		SVN: $Id: Text.php 19898 2010-07-27 16:07:52Z skenow $
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');
/**
 * A simple text field
 *
 * @category	ICMS
 * @package     Form
 * @subpackage  Elements
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */
class icms_form_elements_Text extends icms_form_Element {
	/**
	 * Size
	 * @var	int
	 */
	private $_size;

	/**
	 * Maximum length of the text
	 * @var	int
	 */
	private $_maxlength;

	/**
	 * Initial text
	 * @var	string
	 */
	private $_value;

	/**
	 * Turns off the browser autocomplete function.
	 * @var 		boolean
	 */
	public $autocomplete = false;

	/**
	 * The HTML 5 type of input element to use.
	 * @var 		string
	 */
	public $type = 'text';

	/**
	 * Constructor
	 *
	 * @param	string	$caption	Caption
	 * @param	string	$name       "name" attribute
	 * @param	int		$size	    Size
	 * @param	int		$maxlength	Maximum length of text
	 * @param	string  $value      Initial text
	 * @param	bool	$autocomplete	Whether to use autocomplete functionality in browser. Seems to have no effect in render method.
	 * @param	mixed	$type	Either 'text', 'number', or 'time'.
	 */
	public function __construct($caption, $name, $size, $maxlength, $value = '', $autocomplete = false, $type = 'text') {
		$this->setCaption($caption);
		$this->setName($name);
		$this->_size = (int) $size;
		$this->_maxlength = (int) $maxlength;
		$this->setValue($value);
		$this->autocomplete = !empty($autocomplete);
		$this->type = ($type === true ? 'number' : $type); // legacy, used to be boolean for number
	}

	/**
	 * Get size
	 *
	 * @return	int
	 */
	public function getSize() {
		return $this->_size;
	}

	/**
	 * Get maximum text length
	 *
	 * @return	int
	 */
	public function getMaxlength() {
		return $this->_maxlength;
	}

	/**
	 * Get input type
	 *
	 * @return	int
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Get initial content
	 *
	 * @param	bool    $encode To sanitizer the text? Default value should be "true"; however we have to set "false" for backward compat
	 * @return	string
	 */
	public function getValue($encode = false) {
		return $encode ? htmlspecialchars($this->_value, ENT_QUOTES) : $this->_value;
	}

	/**
	 * Set initial text value
	 *
	 * @param	$value  string
	 */
	public function setValue($value) {
		$this->_value = $value;
	}

	/**
	 * Prepare HTML for output
	 *
	 * @return	string  HTML
	 */
	public function render() {
		$type = $this->getType();
		return "<input type='$type' name='" . $this->getName()
			. "' id='" . $this->getName()
			. "' size='" . $this->getSize()
			. "' maxlength='" . $this->getMaxlength()
			. "' aria-describedby='" . $this->getName() . "-help-text"
			. "' value='" . $this->getValue() . "'" . $this->getExtra()
			. " />";
	}
}

