<?php
/**
 * Creates a hidden form field
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version		$Id: Hidden.php 20462 2010-12-04 15:37:39Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * A hidden field
 *
 * @category	ICMS
 * @package     Form
 * @subpackage  Elements
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */
class icms_form_elements_Hidden extends icms_form_Element {

	/**
	 * Value
	 * @var	string
	 */
	private $_value;

	/**
	 * Constructor
	 *
	 * @param	string	$name	"name" attribute
	 * @param	string	$value	"value" attribute
	 */
	public function __construct($name, $value) {
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
	public function getValue($encode = false) {
		return $encode ? htmlspecialchars($this->_value, ENT_QUOTES) : $this->_value;
	}

	/**
	 * Sets the "value" attribute
	 *
	 * @param  $value	string
	 */
	public function setValue($value) {
		$this->_value = $value;
	}

	/**
	 * Prepare HTML for output
	 *
	 * @return	string	HTML
	 */
	public function render() {
		if (is_array($this->getValue())) {
			$ret = '';
			foreach ($this->getValue() as $value){
				$ret .= "<input type='hidden' name='" . $this->getName() . "[]' id='" . $this->getName() . "' value='" . htmlentities($value, ENT_QUOTES) . "' />\n";
			}
		} else {
			$ret = "<input type='hidden' name='" . $this->getName() . "' id='" . $this->getName() . "' value='" . htmlentities($this->getValue(), ENT_QUOTES) . "' />";
		}

		return $ret;
	}
}

