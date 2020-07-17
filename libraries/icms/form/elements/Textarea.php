<?php
/**
 * Creates a textarea form attribut
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version		SVN: $Id: Textarea.php 20924 2011-03-05 19:37:31Z skenow $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * A textarea
 *
 * @author		Kazumi Ono	<onokazu@xoops.org>
 * @category	ICMS
 * @package     Form
 * @subpackage  Elements
 */
class icms_form_elements_Textarea extends icms_form_Element {
	/**
	 * number of columns
	 * @var	int
	 */
	protected $_cols;

	/**
	 * number of rows
	 * @var	int
	 */
	protected $_rows;

	/**
	 * initial content
	 * @var	string
	 */
	protected $_value;

	/**
	 * Constuctor
	 *
	 * @param	string  $caption    caption
	 * @param	string  $name       name
	 * @param	string  $value      initial content
	 * @param	int     $rows       number of rows
	 * @param	int     $cols       number of columns
	 */
	public function __construct($caption, $name, $value = "", $rows = 5, $cols = 50) {
		$this->setCaption($caption);
		$this->setName($name);
		$this->_rows = (int) $rows;
		$this->_cols = (int) $cols;
		$this->setValue($value);
	}

	/**
	 * get number of rows
	 *
	 * @return	int
	 */
	public function getRows() {
		return $this->_rows;
	}

	/**
	 * Get number of columns
	 *
	 * @return	int
	 */
	public function getCols() {
		return $this->_cols;
	}

	/**
	 * Get initial content
	 *
	 * @param	bool    $encode To sanitize the text? Default value should be "true"; however we have to set "false" for backward compatibility
	 * @return	string
	 */
	public function getValue($encode = false) {
		return $encode ? htmlspecialchars($this->_value) : $this->_value;
	}

	/**
	 * Set initial content
	 *
	 * @param	$value	string
	 */
	public function setValue($value){
		$this->_value = $value;
	}

	/**
	 * prepare HTML for output
	 *
	 * @return string HTML
	 */
	public function render(){
		return "<textarea name='" . $this->getName()
			. "' id='" . $this->getName() . '_tarea'
			. "' rows='" . $this->getRows()
			. "' cols='" . $this->getCols()
			. "'" . $this->getExtra() . ">"
			. $this->getValue()
			. "</textarea>";
	}
}

