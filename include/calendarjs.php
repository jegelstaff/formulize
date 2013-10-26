<?php
/**
 * Javascript and styles used for calendars
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: calendarjs.php 22600 2011-09-08 15:14:24Z mcdonald3072 $
 */

defined('ICMS_ROOT_PATH') or exit();

include_once(ICMS_ROOT_PATH . "/header.php"); // sometimes (ie: ajax conditional element checks) the header is not included and $icmsTheme is null

global $icmsTheme;
icms_loadLanguageFile('core', 'calendar');

$icmsTheme->addLink("stylesheet", ICMS_URL . "/libraries/jscalendar/calendar-blue.css", array("type" => "text/css", "media" => "all"));
$icmsTheme->addScript(ICMS_URL . "/libraries/jscalendar/calendar.js", array("type" => "text/javascript"));

$time = isset($jstime) ? $jstime : "null";
$src = '<!--
var calendar = null;

function selected(cal, date) {
  cal.sel.value = date;
}

function closeHandler(cal) {
  cal.hide();
  Calendar.removeEvent(document, "mousedown", checkCalendar);
}

function checkCalendar(ev) {
  var el = Calendar.is_ie ? Calendar.getElement(ev) : Calendar.getTargetElement(ev);
  for (; el != null; el = el.parentNode)
	if (el == calendar.element || el.tagName == "A") break;
  if (el == null) {
	calendar.callCloseHandler(); Calendar.stopEvent(ev);
  }
}
function showCalendar(id) {
  var el = xoopsGetElementById(id);
  if (calendar != null) {
	calendar.hide();
  } else {
	var cal = new Calendar('._CAL_MONDAYFIRST.', "' . $time . '", selected, closeHandler);
	calendar = cal;
	cal.setRange(1900, 2050); // ALTERED BY FREEFORM SOLUTIONS SO THE CALENDARS CAN HAVE A WIDER RANGE
	calendar.create();
  }
  calendar.sel = el;
  // ALTERED BY FREEFORM SOLUTIONS FOR THE DATE DEFAULT CHANGES IN FORMULIZE STANDALONE
  if (el.value != "" && el.value != "'._DATE_DEFAULT.'") {
  	calendar.parseDate(el.value);
  } else {
	calendar.parseDate("'.$time.'");
  }
  calendar.showAtElement(el);
  Calendar.addEvent(document, "mousedown", checkCalendar);
  return false;
}

Calendar._DN = new Array
("' . _CAL_SUNDAY . '",
 "' . _CAL_MONDAY . '",
 "' . _CAL_TUESDAY . '",
 "' . _CAL_WEDNESDAY . '",
 "' . _CAL_THURSDAY . '",
 "' . _CAL_FRIDAY . '",
 "' . _CAL_SATURDAY . '",
 "' . _CAL_SUNDAY . '");
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

Calendar._TT = {};
Calendar._TT["TOGGLE"] = "' . _CAL_TGL1STD . '";
Calendar._TT["PREV_YEAR"] = "' . _CAL_PREVYR . '";
Calendar._TT["PREV_MONTH"] = "' . _CAL_PREVMNTH . '";
Calendar._TT["GO_TODAY"] = "' . _CAL_GOTODAY . '";
Calendar._TT["NEXT_MONTH"] = "' . _CAL_NXTMNTH . '";
Calendar._TT["NEXT_YEAR"] = "' . _CAL_NEXTYR . '";
Calendar._TT["SEL_DATE"] = "' . _CAL_SELDATE . '";
Calendar._TT["DRAG_TO_MOVE"] = "' . _CAL_DRAGMOVE . '";
Calendar._TT["PART_TODAY"] = "(' . _CAL_TODAY . ')";
Calendar._TT["MON_FIRST"] = "' . _CAL_DISPM1ST . '";
Calendar._TT["SUN_FIRST"] = "' . _CAL_DISPS1ST . '";
Calendar._TT["CLOSE"] = "' . _CLOSE . '";
Calendar._TT["TODAY"] = "' . _CAL_TODAY . '";

// date formats
Calendar._TT["DEF_DATE_FORMAT"] = "y-mm-dd";
Calendar._TT["TT_DATE_FORMAT"] = "y-mm-dd";

Calendar._TT["WK"] = "";
//-->';

$icmsTheme->addScript("", array("type" => "text/javascript"), $src);
$GLOBALS['formulize_calendarFileRequired']['scripts-for-embedding'][] = $src;
$GLOBALS['formulize_calendarFileRequired']['scripts-for-linking'][] = ICMS_URL . "/libraries/jscalendar/calendar.js";
$GLOBALS['formulize_calendarFileRequired']['stylesheets'][] = ICMS_URL . "/libraries/jscalendar/calendar-blue.css";
