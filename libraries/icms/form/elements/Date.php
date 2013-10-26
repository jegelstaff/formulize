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
		} elseif(ereg_replace("[^A-Z{}]","", $value) === "{TODAY}") { // check for {TODAY}, {TODAY-14} etc
			$number = ereg_replace("[^0-9+-]","", $value);
			$value = mktime(0, 0, 0, date("m") , date("d")+$number, date("Y")); 
		} elseif(!is_numeric($value)) {
			$value = time();
		} else {
			$value = intval($value);
		}
		parent::__construct($caption, $name, $size, 25, $value);
	}

	/**
	 * Render the Date field
	 */
	public function render() {
		global $icmsConfigPersona;
		$ele_name = $this->getName();
		// ALTERED BY FREEFORM SOLUTIONS FOR THE DATE DEFAULT CHANGES IN FORMULIZE STANDALONE
		if($icmsConfigPersona['use_jsjalali']) {
			include_once ICMS_ROOT_PATH . '/include/jalali.php';	
		} 
		if($this->getValue(false) !== _DATE_DEFAULT) {
			$ele_value = date(_SHORTDATESTRING, $this->getValue(false));
			$jstime = formatTimestamp($this->getValue(false), _SHORTDATESTRING);
			if(_CALENDAR_TYPE=='jalali') {
				$jalali_ele_value = icms_conv_nr2local(jdate(_SHORTDATESTRING, $ele_value));
			} else {
				$jalali_ele_value = $ele_value;
			}
		} else {
			$ele_value = $this->getValue(false);
			$jstime = formatTimestamp(time(), _SHORTDATESTRING);
			$jalali_ele_value = $ele_value;
		}

		include_once ICMS_ROOT_PATH . '/include/calendar' . ($icmsConfigPersona['use_jsjalali'] == true ? 'jalali' : '') . 'js.php';

		$result = "<input type='text' name='".$ele_name."' id='".$ele_name."' size='".$this->getSize()."' maxlength='".$this->getMaxlength()."' value='".$ele_value."'".$this->getExtra()." />&nbsp;&nbsp;<img src='" . ICMS_URL . "/images/calendar.png' alt='"._CALENDAR."' title='"._CALENDAR."' id='btn_".$ele_name."' onclick='return showCalendar(\"".$ele_name."\");'>";
		
		if ($icmsConfigPersona['use_jsjalali']) {
			$result = "<input id='tmp_".$ele_name."' readonly='readonly' size='".$this->getSize()."' maxlength='".$this->getMaxlength()."' value='".$jalali_ele_value."' /><input type='hidden' name='".$ele_name."' id='".$ele_name."' value='".$ele_value."' ".$this->getExtra()." />&nbsp;&nbsp;<img src='" . ICMS_URL . "/images/calendar.png' alt='"._CALENDAR."' title='"._CALENDAR."' id='btn_".$ele_name."'><script type='text/javascript'>
				Calendar.setup({
					inputField  : 'tmp_".$ele_name."',
		       		ifFormat    : '%Y-%m-%d',
		       		button      : 'btn_".$ele_name."',
        			langNumbers : true,
        			dateType	: '"._CALENDAR_TYPE."',
					onUpdate	: function(cal){document.getElementById('".$ele_name."').value = cal.date.print('%Y-%m-%d');}
				});
			</script>";
		}
		return $result;
	}
}