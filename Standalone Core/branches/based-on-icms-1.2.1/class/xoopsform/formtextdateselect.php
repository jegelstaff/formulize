<?php
// $Id: formtextdateselect.php,v 1.1 2003/03/04 10:35:30 okazu Exp $
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
// Author: Kazumi Ono (AKA onokazu)                                          //
// URL: http://www.myweb.ne.jp/, http://www.xoops.org/, http://jp.xoops.org/ //
// Project: The XOOPS Project                                                //
// ------------------------------------------------------------------------- //
/**
 * @package     kernel
 * @subpackage  form
 * 
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

/**
 * A text field with calendar popup
 * 
 * @package     kernel
 * @subpackage  form
 * 
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

class XoopsFormTextDateSelect extends XoopsFormText
{

	function XoopsFormTextDateSelect($caption, $name, $size = 15, $value= 0)
	{
		//$value = !is_numeric($value) ? time() : intval($value);
		$this->XoopsFormText($caption, $name, $size, 25, $value);
	}

	function render()
	{
		global $xoopsTpl;
		if($this->getValue()) {
			$dateValue = date("Y-m-d", $this->getValue()); 
		} else {
			$dateValue = "YYYY-mm-dd";
		}
		$jstime = date("Y-m-d", time());

		// IF A TEMPLATE IS IN EFFECT, THEN PUT THE CALENDAR CODE IN THE RIGHT PLACE WITHIN THE TEMPLATE SO FORMATTING OF OTHER PARTS OF THE PAGE IS NOT AFFECTED
		// NOTE: THEME.HTML FILE MUST BE MODIFIED TO ACCEPT <{$calendarjs}> IN THE RIGHT PLACE (RIGHT AFTER TITLE IS RECOMMENDED)
		if($xoopsTpl) { 
		
		include_once XOOPS_ROOT_PATH.'/language/'.$GLOBALS['xoopsConfig']['language'].'/calendar.php';

		// figure out if this is XOOPS or ICMS
		if(file_exists(XOOPS_ROOT_PATH."/class/icmsform/index.html")) {
			$calendarIncludePath = "/libraries/jscalendar/";
		} else {
			$calendarIncludePath = "/include/";
		}

		// READ IN THE TEXT FORMERLY INCLUDED BY CALENDARJS.PHP
		$calendarjs = '<link rel="stylesheet" type="text/css" media="all" href="' . XOOPS_URL . $calendarIncludePath.'calendar-blue.css" />
		<script type="text/javascript" src="' . XOOPS_URL.$calendarIncludePath.'calendar.js"></script>
		<script type="text/javascript">
		<!--
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
		  formulizechanged=1;
		  var el = xoopsGetElementById(id);
		  if (calendar != null) {
		    calendar.hide();
		  } else {
		    var cal = new Calendar(true, new Date(\'' . $jstime . '\'), selected, closeHandler);
		    calendar = cal;
		    cal.setRange(2000, 2015);
		    calendar.create();
		  }
		  calendar.sel = el;
		  if (el.value != "" && el.value != "YYYY-mm-dd") {
			calendar.parseDate(el.value);
		  } else {
			calendar.parseDate(\'' . $jstime . '\');
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
		//-->
		</script>';
		
			$xoopsTpl->assign('calendarjs', $calendarjs); 

		} else { // IF NO TEMPLATE IS IN EFFECT, THEN INCLUDE THE RAW JAVASCRIPT AND STYLESHEET CODE DIRECTLY
			include_once XOOPS_ROOT_PATH.'/include/calendarjs.php';
		}
		return "<input type='text' name='".$this->getName()."' id='".$this->getName()."' size='".$this->getSize()."' maxlength='".$this->getMaxlength()."' value='".$dateValue."'".$this->getExtra()." /><input type='button' value=' ... ' onclick='return showCalendar(\"".$this->getName()."\");'>";
	}
}
?>