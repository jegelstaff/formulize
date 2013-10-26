<?php
/**
 * Creates a form radiobutton attribute
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version		$Id: Radioyn.php 19891 2010-07-24 01:49:03Z skenow $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * Yes/No radio buttons.
 *
 * A pair of radio buttons labeled _YES and _NO with values 1 and 0
 * @category	ICMS
 * @package     Form
 * @subpackage  Elements
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */
class icms_form_elements_Radioyn extends icms_form_elements_Radio {
	/**
	 * Constructor
	 *
	 * @param	string	$caption
	 * @param	string	$name
	 * @param	string	$value		Pre-selected value, can be "0" (No) or "1" (Yes)
	 * @param	string	$yes		String for "Yes"
	 * @param	string	$no			String for "No"
	 */
	public function __construct($caption, $name, $value = null, $yes = _YES, $no = _NO) {
		parent::__construct($caption, $name, $value);
		$this->addOption(1, '&nbsp;' . $yes . '&nbsp;');
		$this->addOption(0, '&nbsp;' . $no);
	}
}

