<?php
// $Id: calendar.php 9536 2009-11-13 18:59:32Z pesianstranger $
//%%%%%		Time Zone	%%%%
define("_CAL_SUNDAY", "Sunday");
define("_CAL_MONDAY", "Monday");
define("_CAL_TUESDAY", "Tuesday");
define("_CAL_WEDNESDAY", "Wednesday");
define("_CAL_THURSDAY", "Thursday");
define("_CAL_FRIDAY", "Friday");
define("_CAL_SATURDAY", "Saturday");
define("_CAL_JANUARY", "January");
define("_CAL_FEBRUARY", "February");
define("_CAL_MARCH", "March");
define("_CAL_APRIL", "April");
define("_CAL_MAY", "May");
define("_CAL_JUNE", "June");
define("_CAL_JULY", "July");
define("_CAL_AUGUST", "August");
define("_CAL_SEPTEMBER", "September");
define("_CAL_OCTOBER", "October");
define("_CAL_NOVEMBER", "November");
define("_CAL_DECEMBER", "December");
define("_CAL_TGL1STD", "Toggle first day of week");
define("_CAL_PREVYR", "Prev. year");
define("_CAL_PREVMNTH", "Prev. month");
define("_CAL_GOTODAY", "Go Today");
define("_CAL_NXTMNTH", "Next month");
define("_CAL_NEXTYR", "Next year");
define("_CAL_SELDATE", "Select date");
define("_CAL_DRAGMOVE", "Drag to move");
define("_CAL_TODAY", "Today");
define("_CAL_DISPM1ST", "Display Monday first");
define("_CAL_DISPS1ST", "Display Sunday first");

############# added since 1.1.2 #############
define("_CAL_SUN", "Sun");
define("_CAL_MON", "Mon");
define("_CAL_TUE", "Tue");
define("_CAL_WED", "Wed");
define("_CAL_THU", "Thu");
define("_CAL_FRI", "Fri");
define("_CAL_SAT", "Sat");
// First day of the week. "0" means display Sunday first, "1" means display Monday first, etc...
define("_CAL_FIRSTDAY", "1");
define("_CAL_JAN", "Jan");
define("_CAL_FEB", "Feb");
define("_CAL_MAR", "Mar");
define("_CAL_APR", "Apr");
define("_CAL_JUN", "Jun");
define("_CAL_JUL", "Jul");
define("_CAL_AUG", "Aug");
define("_CAL_SEP", "Sept");
define("_CAL_OCT", "Oct");
define("_CAL_NOV", "Nov");
define("_CAL_DEC", "Dec");
// Direction of the calendar, ltr for left to right and rtl for roght to left (for Persian, Arabic, etc.)
define("_CAL_DIRECTION", "ltr");
define("_CAL_AM", "am");
define("_CAL_AM_CAPS", "AM");
define("_CAL_PM", "pm");
define("_CAL_PM_CAPS", "PM");
define("_CAL_TIME", "Time");
define("_CAL_WK", "wk"); // shorten of week-end
// This may be local-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
define("_CAL_WEEKEND", "0,6");
define("_CAL_DSPFIRST", "Display %s first");
//This constants are for the jalali calendar included in the library
define('_CAL_FARVARDIN','Farvardin');
define('_CAL_ORDIBEHESHT','Ordibehest');
define('_CAL_KHORDAD','Khordad');
define('_CAL_TIR','Tir');
define('_CAL_MORDAD','Mordad');
define('_CAL_SHAHRIVAR','Shahrivar');
define('_CAL_MEHR','Mehr');
define('_CAL_ABAN','Aban');
define('_CAL_AZAR','Azar');
define('_CAL_DEY','Day');
define('_CAL_BAHMAN','Bahman');
define('_CAL_ESFAND','Esfand');
define("_CAL_NUMS_ARRAY", "'0', '1', '2', '3', '4', '5', '6', '7', '8', '9'"); // numeric values can differ in different languages
define("_CAL_TT_DATE_FORMAT", "%a, %b %e");
############# added since 1.2 #############
define("_CAL_SUFFIX", "th");
define('_CAL_AM_LONG','before noon');
define('_CAL_PM_LONG','after noon');
?>