<?php
/**
 * Creates a form text label attribute
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version		$Id: Label.php 19891 2010-07-24 01:49:03Z skenow $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * A text label
 *
 * @category	ICMS
 * @package     Form
 * @subpackage  Elements
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */
class icms_form_elements_Label extends icms_form_Element {
	/**
	 * Text
	 * @var	string
	 */
	private $_value;

	/**
	 * Constructor
	 *
	 * @param	string	$caption	Caption
	 * @param	string	$value		Text
	 */
	public function __construct($caption = "", $value = "", $name = "") {
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
	public function getValue($encode = false) {
		return $encode ? htmlspecialchars($this->_value, ENT_QUOTES) : $this->_value;
	}

	/**
	 * Prepare HTML for output
	 *
	 * @return	string
	 */
	public function render() {
		return $this->getValue();
	}
}

