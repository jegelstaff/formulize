<?php 
/**
* Function to use timepicker
*
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package	core
* @since	1.1
* @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version	$Id: calendarjalalijs.php 8842 2009-06-12 15:22:49Z pesianstranger $
**/
if (!defined('ICMS_ROOT_PATH')) {
	exit();
}
	icms_loadLanguageFile('core', 'calendar');
		echo'<link rel="stylesheet" type="text/css" media="all" href="'.ICMS_URL.'/libraries/jalalijscalendar/aqua/style.css" />
<script type="text/javascript" src="'.ICMS_URL.'/libraries/jalalijscalendar/calendar.js"></script>
<script type="text/javascript" src="'.ICMS_URL.'/libraries/jalalijscalendar/calendar-setup.js"></script>
';
	if ( $icmsConfig['use_ext_date'] == true && defined ('_CALENDAR_TYPE') && _CALENDAR_TYPE == "jalali"){
		echo'<script type="text/javascript" src="'.ICMS_URL.'/libraries/jalalijscalendar/jalali.js"></script>';
}
	if ( $icmsConfig['use_ext_date'] == true && file_exists(ICMS_ROOT_PATH.'/language/'.$icmsConfig['language'].'/local.date.js')){
		echo'<script type="text/javascript" src="'.ICMS_URL.'/language/'.$icmsConfig['language'].'/local.date.js"></script>';
}
?>
<script type="text/javascript">


Calendar._DN = new Array
("<?php echo _CAL_SUNDAY;?>",
 "<?php echo _CAL_MONDAY;?>",
 "<?php echo _CAL_TUESDAY;?>",
 "<?php echo _CAL_WEDNESDAY;?>",
 "<?php echo _CAL_THURSDAY;?>",
 "<?php echo _CAL_FRIDAY;?>",
 "<?php echo _CAL_SATURDAY;?>",
 "<?php echo _CAL_SUNDAY;?>");

Calendar._SDN = new Array
("<?php echo _CAL_SUN;?>",
 "<?php echo _CAL_MON;?>",
 "<?php echo _CAL_TUE;?>",
 "<?php echo _CAL_WED;?>",
 "<?php echo _CAL_THU;?>",
 "<?php echo _CAL_FRI;?>",
 "<?php echo _CAL_SAT;?>",
 "<?php echo _CAL_SUN;?>");

Calendar._FD = <?php echo _CAL_FIRSTDAY;?>;

Calendar._MN = new Array
("<?php echo _CAL_JANUARY;?>",
 "<?php echo _CAL_FEBRUARY;?>",
 "<?php echo _CAL_MARCH;?>",
 "<?php echo _CAL_APRIL;?>",
 "<?php echo _CAL_MAY;?>",
 "<?php echo _CAL_JUNE;?>",
 "<?php echo _CAL_JULY;?>",
 "<?php echo _CAL_AUGUST;?>",
 "<?php echo _CAL_SEPTEMBER;?>",
 "<?php echo _CAL_OCTOBER;?>",
 "<?php echo _CAL_NOVEMBER;?>",
 "<?php echo _CAL_DECEMBER;?>");

Calendar._SMN = new Array
("<?php echo _CAL_JAN;?>",
 "<?php echo _CAL_FEB;?>",
 "<?php echo _CAL_MAR;?>",
 "<?php echo _CAL_APR;?>",
 "<?php echo _CAL_MAY;?>",
 "<?php echo _CAL_JUN;?>",
 "<?php echo _CAL_JUL;?>",
 "<?php echo _CAL_AUG;?>",
 "<?php echo _CAL_SEP;?>",
 "<?php echo _CAL_OCT;?>",
 "<?php echo _CAL_NOV;?>",
 "<?php echo _CAL_DEC;?>");


 // full month names
Calendar._JMN = new Array
("<?php echo _CAL_FARVARDIN;?>",
 "<?php echo _CAL_ORDIBEHESHT;?>",
 "<?php echo _CAL_KHORDAD;?>",
 "<?php echo _CAL_TIR;?>",
 "<?php echo _CAL_MORDAD;?>",
 "<?php echo _CAL_SHAHRIVAR;?>",
 "<?php echo _CAL_MEHR;?>",
 "<?php echo _CAL_ABAN;?>",
 "<?php echo _CAL_AZAR;?>",
 "<?php echo _CAL_DEY;?>",
 "<?php echo _CAL_BAHMAN;?>",
 "<?php echo _CAL_ESFAND;?>");
// short month names
Calendar._JSMN = new Array
("<?php echo _CAL_FARVARDIN;?>",
 "<?php echo _CAL_ORDIBEHESHT;?>",
 "<?php echo _CAL_KHORDAD;?>",
 "<?php echo _CAL_TIR;?>",
 "<?php echo _CAL_MORDAD;?>",
 "<?php echo _CAL_SHAHRIVAR;?>",
 "<?php echo _CAL_MEHR;?>",
 "<?php echo _CAL_ABAN;?>",
 "<?php echo _CAL_AZAR;?>",
 "<?php echo _CAL_DEY;?>",
 "<?php echo _CAL_BAHMAN;?>",
 "<?php echo _CAL_ESFAND;?>");


 
// tooltips
Calendar._TT = {};
Calendar._TT["INFO"] = "About the calendar";

Calendar._TT["ABOUT"] =
"JalaliJSCalendar\n" +
"Copyright (c) 2008 Ali Farhadi (http://farhadi.ir/)\n" + // don't translate this this ;-)
"Distributed under GNU GPL. See http://gnu.org/licenses/gpl.html for details.\n\n" +
"Based on The DHTML Calendar developed by Dynarch.com.\n" +
"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + // don't translate this this ;-)
"\n\n" +
"Date selection:\n" +
"- Use the \xab, \xbb buttons to select year\n" +
"- Use the " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " buttons to select month\n" +
"- Hold mouse button on any of the above buttons for faster selection.";
Calendar._TT["ABOUT_TIME"] = "\n\n" +
"Time selection:\n" +
"- Click on any of the time parts to increase it\n" +
"- or Shift-click to decrease it\n" +
"- or click and drag for faster selection.";

Calendar._TT["PREV_YEAR"] = "<?php echo _CAL_PREVYR;?>";
Calendar._TT["PREV_MONTH"] = "<?php echo _CAL_PREVMNTH;?>";
Calendar._TT["GO_TODAY"] = "<?php echo _CAL_GOTODAY;?>";
Calendar._TT["NEXT_MONTH"] = "<?php echo _CAL_NXTMNTH;?>";
Calendar._TT["NEXT_YEAR"] = "<?php echo _CAL_NEXTYR;?>";
Calendar._TT["SEL_DATE"] = "<?php echo _CAL_SELDATE;?>";
Calendar._TT["DRAG_TO_MOVE"] = "<?php echo _CAL_DRAGMOVE;?>";
Calendar._TT["PART_TODAY"] = "(<?php echo _CAL_TODAY;?>)";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] = "<?php echo _CAL_DSPFIRST;?>";

Calendar._TT["WEEKEND"] = "<?php echo _CAL_WEEKEND;?>";

Calendar._TT["CLOSE"] = "<?php echo _CLOSE;?>";
Calendar._TT["TODAY"] = "<?php echo _CAL_TODAY;?>";
Calendar._TT["TIME_PART"] = "(Shift-)Click or drag to change value";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "%Y-%m-%d";
Calendar._TT["TT_DATE_FORMAT"] = "<?php echo _CAL_TT_DATE_FORMAT;?>";

Calendar._TT["WK"] = "<?php echo _CAL_WK;?>";
Calendar._TT["TIME"] = "<?php echo _CAL_TIME;?> : ";

Calendar._TT["LAM"] = "<?php echo _CAL_AM;?>";
Calendar._TT["AM"] = "<?php echo _CAL_AM_CAPS;?>";
Calendar._TT["LPM"] = "<?php echo _CAL_PM;?>";
Calendar._TT["PM"] = "<?php echo _CAL_PM_CAPS;?>";

Calendar._NUMBERS = [<?php echo _CAL_NUMS_ARRAY;?>];

Calendar._DIR = '<?php echo _CAL_DIRECTION;?>';

</script>
