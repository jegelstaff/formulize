<?php
/**
 * Class to create a form field with a date selector
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version		SVN: $Id: Date.php 22552 2011-09-04 13:19:44Z phoenyx $
 **/

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

/**
 * A text field with calendar popup
 *
 * @category	ICMS
 * @package     Form
 * @subpackage  Elements
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */
class icms_form_elements_Date extends icms_form_elements_Text {


	/**
	 * Constructor
	 *
	 * @param string	$caption
	 * @param string	$name
	 * @param int		$size
	 * @param mixed		$value
	 */
	public function __construct($caption, $name, $size = 15, $value= 0) {
		// ALTERED BY FREEFORM SOLUTIONS FOR THE DATE DEFAULT CHANGES IN FORMULIZE STANDALONE
		if($value === "") {
			$value = _DATE_DEFAULT;
		} elseif(preg_replace("[^A-Z{}]","", $value) === "{TODAY}") { // check for {TODAY}, {TODAY-14} etc
			$number = preg_replace("[^0-9+-]","", $value);
			$value = mktime(date("H"), date("i"), date("s"), date("m") , date("d")+$number, date("Y")); // $value is going to be deterined based on UTC because the functions involved have no timezone awareness
            $offset = formulize_getUserUTCOffsetSecs(timestamp: $value);
            $value = $value + $offset;
		} elseif(!is_numeric($value)) {
			$value = mktime(date("H"), date("i"), date("s"), date("m") , date("d"), date("Y")); // $value is going to be deterined based on UTC because the functions involved have no timezone awareness
            $offset = formulize_getUserUTCOffsetSecs(timestamp: $value);
            $value = $value + $offset;
		} else {
			$value = intval($value);
		}
		parent::__construct($caption, $name, $size, 25, $value);
	}

	/**
	 * Render the Date field
	 */
	public function render() {
		$ele_name = $this->getName();
		// ALTERED BY FREEFORM SOLUTIONS FOR THE DATE DEFAULT CHANGES IN FORMULIZE STANDALONE
		if($this->getValue(false) !== _DATE_DEFAULT) {
			$ele_value = date(_SHORTDATESTRING, $this->getValue(false));
		} else {
			$ele_value = $this->getValue(false);
		}

		$result = "<input type='date' name='".$ele_name."' id='".$ele_name."' class=\"icms-date-box\" size='".$this->getSize()."' maxlength='".$this->getMaxlength()."' value='".$ele_value."'".$this->getExtra()." aria-describedby='".$ele_name."-help-text' />";
        return $result;
    }
}
