<?php
/**
 * Creates a form element tray
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version	$Id: Tray.php 20521 2010-12-12 14:52:03Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * A group of form elements
 *
 * @author		Kazumi Ono	<onokazu@xoops.org>
 * @category	ICMS
 * @package     Form
 * @subpackage  Elements
 */
class icms_form_elements_Tray extends icms_form_Element {

	/**
	 * array of form element objects
	 * @var array
	 * @access  private
	 */
	private $_elements = array();

	/**
	 * required elements
	 * @var array
	 */
	private $_required = array();

	/**
	 * HTML to separate the elements
	 * @var	string
	 * @access  private
	 */
	private $_delimeter;

	/**
	 * constructor
	 *
	 * @param	string  $caption    Caption for the group.
	 * @param	string  $delimiter  HTML to separate the elements
	 */
	public function __construct($caption, $delimeter = "&nbsp;", $name = "") {
		$this->setName($name);
		$this->setCaption($caption);
		$this->_delimeter = $delimeter;
	}

	/**
	 * Is this element a container of other elements?
	 *
	 * @return	bool true
	 */
	public function isContainer() {
		return true;
	}

	/**
	 * Find out if there are required elements.
	 *
	 * @return	bool
	 */
	public function isRequired() {
		return !empty($this->_required);
	}

	/**
	 * Add an element to the group
	 *
	 * @param	object  &$element    {@link icms_form_Element} to add
	 */
	public function addElement(&$formElement, $required = false) {
		$this->_elements[] =& $formElement;
		if (!$formElement->isContainer()) {
			if ($required) {
				$formElement->setRequired();
				$this->_required[] =& $formElement;
			}
		} else {
			$required_elements =& $formElement->getRequired();
			$count = count($required_elements);
			for ($i = 0 ; $i < $count; $i++) {
				$this->_required[] =& $required_elements[$i];
			}
		}
	}

	/**
	 * get an array of "required" form elements
	 *
	 * @return	array   array of {@link icms_form_Element}s
	 */
	public function &getRequired() {
		return $this->_required;
	}

	/**
	 * Get an array of the elements in this group
	 *
	 * @param	bool	$recurse	get elements recursively?
	 * @return  array   Array of {@link icms_form_Element} objects.
	 */
	public function &getElements($recurse = false) {
		if (!$recurse) {
			return $this->_elements;
		} else {
			$ret = array();
			$count = count($this->_elements);
			for ($i = 0; $i < $count; $i++) {
				if (!$this->_elements[$i]->isContainer()) {
					$ret[] =& $this->_elements[$i];
				} else {
					$elements =& $this->_elements[$i]->getElements(true);
					$count2 = count($elements);
					for ($j = 0; $j < $count2; $j++) {
						$ret[] =& $elements[$j];
					}
					unset($elements);
				}
			}
			return $ret;
		}
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
	 * prepare HTML to output this group
	 *
	 * @return	string  HTML output
	 */
	public function render() {
		$count = 0;
		$ret = "";
		foreach ($this->getElements() as $ele) {
			if ($count > 0) {
				$ret .= $this->getDelimeter();
			}
			if ($ele->getCaption() != '') {
				$ret .= $ele->getCaption() . "&nbsp;";
			}
			$ret .= $ele->render() . "\n";
			if (!$ele->isHidden()) {
				$count++;
			}
		}
		return $ret;
	}
}
