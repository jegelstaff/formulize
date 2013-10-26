<?php
/**
 * Creates a form datatime object
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version	$Id: Datetime.php 19906 2010-07-27 21:26:39Z skenow $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * Date and time selection field
 *
 * This extends the icms_form_elements_Tray class because this field actually contains
 * 2 different elements - the date and the time
 *
 * @author		Kazumi Ono	<onokazu@xoops.org>
 * @category	ICMS
 * @package     Form
 * @subpackage	Elements
 */
class icms_form_elements_Datetime extends icms_form_elements_Tray {

	/**
	 * Constructor
	 * @param	string  $caption    Caption of the element
	 * @param	string  $name       Name of the element
	 * @param	string  $size       Size of the element
	 * @param	string  $value      Value of the element
	 */
	public function __construct($caption, $name, $size = 15, $value=0) {
		parent::__construct($caption, '&nbsp;');
		$value = (int) ($value);
		$value = ($value > 0) ? $value : time();
		$datetime = getDate($value);
		$this->addElement(new icms_form_elements_Date('', $name.'[date]', $size, $value));
		$timearray = array();
		for ($i = 0; $i < 24; $i++) {
			for ($j = 0; $j < 60; $j = $j + 10) {
				$key = ($i * 3600) + ($j * 60);
				$timearray[$key] = ($j != 0) ? $i.':'.$j : $i.':0'.$j;
			}
		}
		ksort($timearray);
		$timeselect = new icms_form_elements_Select('', $name.'[time]', $datetime['hours'] * 3600 + 600 * ceil($datetime['minutes'] / 10));
		$timeselect->addOptionArray($timearray);
		$this->addElement($timeselect);
	}
}

