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
		} elseif(preg_replace("[^A-Z{}]","", $value) === "{TODAY}") { // check for {TODAY}, {TODAY-14} etc
			$number = preg_replace("[^0-9+-]","", $value);
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

		$result = "<input type='text' name='".$ele_name."' id='".$ele_name."' class=\"icms-date-box\" size='".$this->getSize()."' maxlength='".$this->getMaxlength()."' value='".$ele_value."'".$this->getExtra()." />&nbsp;&nbsp;<img src='" . ICMS_URL . "/images/calendar.png' class='no-print' alt='"._CALENDAR."' title='"._CALENDAR."' id='btn_".$ele_name."' onclick='return showCalendar(\"".$ele_name."\");'>";

		if (false and $icmsConfigPersona['use_jsjalali']) {
			$dateFormat = dateFormatToStrftime(_SHORTDATESTRING);
			$result = "<input name='".$ele_name."' id='".$ele_name."' class=\"icms-date-box\" size='".$this->getSize()."' maxlength='".$this->getMaxlength()."' value='".$jalali_ele_value."'".$this->getExtra()." />&nbsp;&nbsp;<img src='" . ICMS_URL . "/images/calendar.png' class='no-print' alt='"._CALENDAR."' title='"._CALENDAR."' id='btn_".$ele_name."'><script type='text/javascript'>
				Calendar.setup({
					inputField  : '".$ele_name."',
					ifFormat    : '$dateFormat',
					button      : 'btn_".$ele_name."',
					langNumbers : true,
					dateType    : '"._CALENDAR_TYPE."',
					onUpdate    : function(cal){document.getElementById('".$ele_name."').value = cal.date.print('$dateFormat');}
				});
			</script>";
		} else {
            // Thanks http://detectmobilebrowsers.com/
            $useragent=$_SERVER['HTTP_USER_AGENT'];
            if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))) {
                $inputType = 'date';
            } else {
                $inputType = 'text';
            }
            $result = "<input type='".$inputType."' name='".$ele_name."' id='".$ele_name.
                "' class=\"icms-date-box\" size='".$this->getSize()."' maxlength='".$this->getMaxlength().
                "' value='".$ele_value."'".$this->getExtra()." />";
            global $output_datepicker_defaults;
            if (!$output_datepicker_defaults AND $inputType == 'text') {
                $output_datepicker_defaults = $ele_name;
                $ICMS_URL = ICMS_URL;
                // the jQuery datepicker wants a date format such as yy-mm-dd, or yy-m-d.
                // yyyy-mm-dd gives a date like '20142014-10-11', so only yy, not yyyy.
                $dateFormat = dateFormatToStrftime(_SHORTDATESTRING);
                $dateFormat = str_replace(array("%y", "%m", "%d"), array("yy", "mm", "dd"), strtolower($dateFormat));
                // note: datepicker_defaults is a var so it is available later for date elements with date limits
                $result .= <<<EOF
<script>
var datepicker_defaults = {
    dateFormat: "$dateFormat",
    changeMonth: true,
    changeYear: true,
    hideIfNoPrevNext: true, // do not show the prev/next links if they are disabled
    numberOfMonths: 1,
    yearRange: "c-20:c+20",
    showOn: "both",
    buttonImageOnly: true,
    buttonImage: "$ICMS_URL/images/calendar.png",
    buttonText: "Calendar"
};

function loadFormulizeDatepicker(){
    jQuery.datepicker.setDefaults(datepicker_defaults);
        jQuery(".icms-date-box").datepicker();
}

jQuery(document).ready(function() {
    if(jQuery.datepicker === undefined) {
        setTimeout(loadFormulizeDatepicker, 1000); // hail mary, wait for a second if jQuery UI isn't finished loading??
    } else {
        loadFormulizeDatepicker();
    }
});
</script>
EOF;
            }
        }
        return $result;
    }
}
