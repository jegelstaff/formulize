<?php
/**
 * Function to use timepicker
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package	core
 * @since	1.1
 * @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version	$Id: calendarjalalijs.php 22552 2011-09-04 13:19:44Z phoenyx $
 **/

defined('ICMS_ROOT_PATH') or exit();

global $icmsConfig, $icmsTheme;
icms_loadLanguageFile('core', 'calendar');

$icmsTheme->addLink("stylesheet", ICMS_URL . "/libraries/jalalijscalendar/aqua/style.css", array("type" => "text/css", "media" => "all"));
$icmsTheme->addScript(ICMS_URL . "/libraries/jalalijscalendar/calendar.js", array("type" => "text/javascript"));
$icmsTheme->addScript(ICMS_URL . "/libraries/jalalijscalendar/calendar-setup.js", array("type" => "text/javascript"));

if ($icmsConfig['use_ext_date'] == true && defined('_CALENDAR_TYPE') && _CALENDAR_TYPE == "jalali") {
	$icmsTheme->addScript(ICMS_URL . "/libraries/jalalijscalendar/jalali.js", array("type" => "text/javascript"));
	$GLOBALS['formulize_calendarFileRequired']['scripts-for-linking'][] = ICMS_URL . "/libraries/jalalijscalendar/jalali.js";
}
if ($icmsConfig['use_ext_date'] == true && file_exists(ICMS_ROOT_PATH . '/language/' . $icmsConfig['language'] . '/local.date.js')) {
	$icmsTheme->addScript(ICMS_URL . "/language/" . $icmsConfig['language'] . "/local.date.js", array("type" => "text/javascript"));
	$GLOBALS['formulize_calendarFileRequired']['scripts-for-linking'][] = ICMS_URL . "/language/" . $icmsConfig['language'] . "/local.date.js";
}

$src = 'Calendar._DN = new Array
("' . _CAL_SUNDAY . '",
 "' . _CAL_MONDAY . '",
 "' . _CAL_TUESDAY . '",
 "' . _CAL_WEDNESDAY . '",
 "' . _CAL_THURSDAY . '",
 "' . _CAL_FRIDAY . '",
 "' . _CAL_SATURDAY . '",
 "' . _CAL_SUNDAY . '");

Calendar._SDN = new Array
("' . _CAL_SUN . '",
 "' . _CAL_MON . '",
 "' . _CAL_TUE . '",
 "' . _CAL_WED . '",
 "' . _CAL_THU . '",
 "' . _CAL_FRI . '",
 "' . _CAL_SAT . '",
 "' . _CAL_SUN . '");

Calendar._FD = ' . _CAL_FIRSTDAY . ';

Calendar._MN = new Array
("' . _CAL_JANUARY . '",
 "' . _CAL_FEBRUARY . '",
 "' . _CAL_MARCH . '",
 "' . _CAL_APRIL . '",
 "' . _CAL_MAY . '",
 "' . _CAL_JUNE . '",
 "' . _CAL_JULY . '",
 "' . _CAL_AUGUST . '",
 "' . _CAL_SEPTEMBER . '",
 "' . _CAL_OCTOBER . '",
 "' . _CAL_NOVEMBER . '",
 "' . _CAL_DECEMBER . '");

Calendar._SMN = new Array
("' . _CAL_JAN . '",
 "' . _CAL_FEB . '",
 "' . _CAL_MAR . '",
 "' . _CAL_APR . '",
 "' . _CAL_MAY . '",
 "' . _CAL_JUN . '",
 "' . _CAL_JUL . '",
 "' . _CAL_AUG . '",
 "' . _CAL_SEP . '",
 "' . _CAL_OCT . '",
 "' . _CAL_NOV . '",
 "' . _CAL_DEC . '");

 // full month names
Calendar._JMN = new Array
("' . _CAL_FARVARDIN . '",
 "' . _CAL_ORDIBEHESHT . '",
 "' . _CAL_KHORDAD . '",
 "' . _CAL_TIR . '",
 "' . _CAL_MORDAD . '",
 "' . _CAL_SHAHRIVAR . '",
 "' . _CAL_MEHR . '",
 "' . _CAL_ABAN . '",
 "' . _CAL_AZAR . '",
 "' . _CAL_DEY . '",
 "' . _CAL_BAHMAN . '",
 "' . _CAL_ESFAND . '");
// short month names
Calendar._JSMN = new Array
("' . _CAL_FARVARDIN . '",
 "' . _CAL_ORDIBEHESHT . '",
 "' . _CAL_KHORDAD . '",
 "' . _CAL_TIR . '",
 "' . _CAL_MORDAD . '",
 "' . _CAL_SHAHRIVAR . '",
 "' . _CAL_MEHR . '",
 "' . _CAL_ABAN . '",
 "' . _CAL_AZAR . '",
 "' . _CAL_DEY . '",
 "' . _CAL_BAHMAN . '",
 "' . _CAL_ESFAND . '");

// tooltips
Calendar._TT = {};
Calendar._TT["INFO"] = "About the calendar";

Calendar._TT["ABOUT"] =
"JalaliJSCalendar\n" +
"Copyright (c) 2008 Ali Farhadi (http://farhadi.ir/)\n" + // do not translate this this ;-)
"Distributed under GNU GPL. See http://gnu.org/licenses/gpl.html for details.\n\n" +
"Based on The DHTML Calendar developed by Dynarch.com.\n" +
"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + // do not translate this this ;-)
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

Calendar._TT["PREV_YEAR"] = "' . _CAL_PREVYR . '";
Calendar._TT["PREV_MONTH"] = "' . _CAL_PREVMNTH . '";
Calendar._TT["GO_TODAY"] = "' . _CAL_GOTODAY . '";
Calendar._TT["NEXT_MONTH"] = "' . _CAL_NXTMNTH . '";
Calendar._TT["NEXT_YEAR"] = "' . _CAL_NEXTYR . '";
Calendar._TT["SEL_DATE"] = "' . _CAL_SELDATE . '";
Calendar._TT["DRAG_TO_MOVE"] = "' . _CAL_DRAGMOVE . '";
Calendar._TT["PART_TODAY"] = "(' . _CAL_TODAY . ')";

// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] = "' . _CAL_DSPFIRST . '";
Calendar._TT["WEEKEND"] = "' . _CAL_WEEKEND . '";
Calendar._TT["CLOSE"] = "' . _CLOSE . '";
Calendar._TT["TODAY"] = "' . _CAL_TODAY . '";
Calendar._TT["TIME_PART"] = "(Shift-)Click or drag to change value";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "%Y-%m-%d";
Calendar._TT["TT_DATE_FORMAT"] = "' . _CAL_TT_DATE_FORMAT . '";
Calendar._TT["WK"] = "' . _CAL_WK . '";
Calendar._TT["TIME"] = "' . _CAL_TIME . ' : ";
Calendar._TT["LAM"] = "' . _CAL_AM . '";
Calendar._TT["AM"] = "' . _CAL_AM_CAPS . '";
Calendar._TT["LPM"] = "' . _CAL_PM . '";
Calendar._TT["PM"] = "' . _CAL_PM_CAPS . '";
Calendar._NUMBERS = [' . _CAL_NUMS_ARRAY . '];
Calendar._DIR = "' . _CAL_DIRECTION . '";';

$icmsTheme->addScript("", array("type" => "text/javascript"), $src);
$GLOBALS['formulize_calendarFileRequired']['scripts-for-embedding'][] = $src;
$GLOBALS['formulize_calendarFileRequired']['scripts-for-linking'][] = ICMS_URL . "/libraries/jalalijscalendar/calendar.js";
$GLOBALS['formulize_calendarFileRequired']['scripts-for-linking'][] = ICMS_URL . "/libraries/jalalijscalendar/calendar-setup.js";
$GLOBALS['formulize_calendarFileRequired']['stylesheets'][] = ICMS_URL . "/libraries/jalalijscalendar/aqua/style.css";
