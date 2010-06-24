<?php
// $Id: jalali.php 8786 2009-05-28 21:06:30Z realtherplima $  //
/**
* Handles all jalali calendar functions within ImpressCMS
*
* These functions are some Persian users related functions
* In ImpressCMS we are trying to bring different calendar type in core, so this is the place to place them
* If you know other calendars, plaese contact ImpressCMS developers to add them to core ;-)
*
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @copyright (C) 2000  Roozbeh Pournader and Mohammad Toossi
* @copyright (C) jalali Date function by Milad Rastian (miladmovie AT yahoo DOT com)
* @copyright (C) 2003 FARSI PROJECTS GROUP
* @since		1.2
* @package	core
* @author		Roozbeh Pournader and Mohammad Toossi
* @author		jalali Date function by Milad Rastian (miladmovie AT yahoo DOT com)
* @author		FARSI PROJECTS GROUP
* @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version	$Id: jalali.php 8786 2009-05-28 21:06:30Z realtherplima $
*/
//																		   //
// The main function which convert Gregorian to Jalali calendars is:		 //
// JALAI DATE FUNCTION													   //
// this function is simillar than date function in PHP.					  //
// "jalali.php" is convertor to and from Gregorian and Jalali calendars.	 //
// Copyright (C) 2000  Roozbeh Pournader and Mohammad Toossi				 //
// Copyright (C) jalali Date function by Milad Rastian					   //
// (miladmovie AT yahoo DOT com)											 //
// Copyright (C) 2003 FARSI PROJECTS GROUP								   //
// This has been imported to ImpressCMS by stranger @ www.impresscms.ir	  //
// I would like to thank irmtfan @ www.jadoogaran.org for his script for	 //
// xoops (which is based for this work)									  //
//  ------------------------------------------------------------------------ //

/**
 * Divides two integers.
 *
 * @param	int	$a	Integer to divide
 * @param	int	$b	Integer to divide to
 * @return	int
 */
function div($a,$b) {
	return (int) ($a / $b);
}

/**
 * Converts gregorian to jalali calendar
 *
 * @param int $g_y	The gregorian year
 * @param int $g_m	The gregorian month
 * @param int $g_d	The gregorian day
 * @return array The jalali date array
 */
function gregorian_to_jalali ($g_y, $g_m, $g_d)
{
	$g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	$j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);

	$gy = $g_y-1600;
	$gm = $g_m-1;
	$gd = $g_d-1;

	$g_day_no = 365*$gy+div($gy+3,4)-div($gy+99,100)+div($gy+399,400);

	for ($i=0; $i < $gm; ++$i)
	   $g_day_no += $g_days_in_month[$i];
	if ($gm>1 && (($gy%4==0 && $gy%100!=0) || ($gy%400==0)))
	   /* leap and after Feb */
	   $g_day_no++;
	$g_day_no += $gd;

	$j_day_no = $g_day_no-79;

	$j_np = div($j_day_no, 12053); /* 12053 = 365*33 + 32/4 */
	$j_day_no = $j_day_no % 12053;

	$jy = 979+33*$j_np+4*div($j_day_no,1461); /* 1461 = 365*4 + 4/4 */

	$j_day_no %= 1461;

	if ($j_day_no >= 366) {
	   $jy += div($j_day_no-1, 365);
	   $j_day_no = ($j_day_no-1)%365;
	}

	for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i)
	   $j_day_no -= $j_days_in_month[$i];
	$jm = $i+1;
	$jd = $j_day_no+1;

	return array($jy, $jm, $jd);
}

/**
 * Converts jalali to gregorian calendar
 *
 * @param int $j_y	The jalali year
 * @param int $j_m	The jalali month
 * @param int $j_d	The jalali day
 * @return array The gregorian date array
 */
function jalali_to_gregorian($j_y, $j_m, $j_d)
{
	$g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	$j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);

  $jy = $j_y-979;
  $jm = $j_m-1;
  $jd = $j_d-1;

  $j_day_no = 365*$jy + div($jy, 33)*8 + div($jy%33+3, 4);
  for ($i=0; $i < $jm; ++$i)
	 $j_day_no += $j_days_in_month[$i];

  $j_day_no += $jd;

  $g_day_no = $j_day_no+79;

  $gy = 1600 + 400*div($g_day_no, 146097); /* 146097 = 365*400 + 400/4 - 400/100 + 400/400 */
  $g_day_no = $g_day_no % 146097;

  $leap = true;
  if ($g_day_no >= 36525) /* 36525 = 365*100 + 100/4 */
  {
	 $g_day_no--;
	 $gy += 100*div($g_day_no,  36524); /* 36524 = 365*100 + 100/4 - 100/100 */
	 $g_day_no = $g_day_no % 36524;

	 if ($g_day_no >= 365)
		$g_day_no++;
	 else
		$leap = false;
  }

  $gy += 4*div($g_day_no, 1461); /* 1461 = 365*4 + 4/4 */
  $g_day_no %= 1461;

  if ($g_day_no >= 366) {
	 $leap = false;

	 $g_day_no--;
	 $gy += div($g_day_no, 365);
	 $g_day_no = $g_day_no % 365;
  }

  for ($i = 0; $g_day_no >= $g_days_in_month[$i] + ($i == 1 && $leap); $i++)
	 $g_day_no -= $g_days_in_month[$i] + ($i == 1 && $leap);
  $gm = $i+1;
  $gd = $g_day_no+1;

  return array($gy, $gm, $gd);
}

/*
 * Finds the begining day of the month
 *
 * @param int $month	The month
 * @param int $day	The day
 * @param int $year	The year
 * @return string The beginning day of the month
 */
function mstart($month,$day,$year)
{
	list( $jyear, $jmonth, $jday ) = gregorian_to_jalali($year, $month, $day);
	list( $year, $month, $day ) = jalali_to_gregorian($jyear, $jmonth, '1');
	$timestamp=mktime(0,0,0,$month,$day,$year);
	return date('w',$timestamp);
}
// End of finding the begining day Of months

/*
 * Finds the last day of the month
 *
 * @param int $month	The month
 * @param int $day	The day
 * @param int $year	The year
 * @return string The last day of the month
 */
function lastday ($month,$day,$year)
{
	$lastdayen=date('d',mktime(0,0,0,$month+1,0,$year));
	list( $jyear, $jmonth, $jday ) = gregorian_to_jalali($year, $month, $day);
	$lastdatep=$jday;
	while($jday!='1')
	{
		if($day<$lastdayen)
		{
			$day++;
			list( $jyear, $jmonth, $jday ) = gregorian_to_jalali($year, $month, $day);
			if($jday=='1') break;
			if($jday='1') $lastdatep++;
		}
		else
		{
			$day=0;
			$month++;
			if($month==13)
			{
					$month='1';
					$year++;
			}
		}

	}
	return $lastdatep-1;
}

/*
 * Make time for jalali calendar
 *
 * @param int $hour	The hour
 * @param int $minute	The minute
 * @param int $second	The second
 * @param int $jmonth	The jalali month
 * @param int $jday	The jalali day
 * @param int $jyear	The jalali year
 * @return string The mktime string
 */
function jmaketime($hour,$minute,$second,$jmonth,$jday,$jyear)
{
	$basecheck = defined('_USE_LOCAL_NUM') && _USE_LOCAL_NUM;
	if ( $basecheck ){
	$hour = icms_conv_local2nr($hour);
	$minute = icms_conv_local2nr($minute);
	$second = icms_conv_local2nr($second);
	$jmonth = icms_conv_local2nr($jday);
	$jyear = icms_conv_local2nr($jyear);
	}
	list( $year, $month, $day ) = jalali_to_gregorian($jyear, $jmonth, $jday);
	$i=mktime($hour,$minute,$second,$month,$day,$year);
	return $i;
}

/*
 * Make time for jalali calendar
 *
 * @param string $type	The type of date string?
 * @param string $maket	The date string type
 * @return mixed The mktime string
 */
function jdate($type,$maket='now')
{
	global $icmsConfig;
	icms_loadLanguageFile('core', 'calendar');
	$result='';
	if($maket=='now'){
		$year=date('Y');
		$month=date('m');
		$day=date('d');
		list( $jyear, $jmonth, $jday ) = gregorian_to_jalali($year, $month, $day);
		$maket=jmaketime(date('h'),date('i'),date('s'),$jmonth,$jday,$jyear);
	}else{
		$date=date('Y-m-d',$maket);
		list( $year, $month, $day ) = preg_split ( '/-/', $date );

		list( $jyear, $jmonth, $jday ) = gregorian_to_jalali($year, $month, $day);
		}

	$need= $maket;
	$year=date('Y',$need);
	$month=date('m',$need);
	$day=date('d',$need);
	$i=0;
	while($i<strlen($type))
	{
		$subtype=substr($type,$i,1);
		switch ($subtype)
		{

			case 'A':
				$result1=date('a',$need);
				if($result1=='pm') $result.=_CAL_PM_LONG;
				else $result.=_CAL_AM_LONG;
				break;

			case 'a':
				$result1=date('a',$need);
				if($result1=='pm') $result.=_CAL_PM;
				else $result.=_CAL_AM;
				break;
			case 'd':
				list( $jyear, $jmonth, $jday ) = gregorian_to_jalali($year, $month, $day);
				if($jday<10)$result1='0'.$jday;
				else 	$result1=$jday;
				$result.=$result1;
				break;
			case 'D':
				$result1=date('D',$need);
				if($result1=='Sat') $result1=_CAL_SAT;
				else if($result1=='Sun') $result1=_CAL_SUN;
				else if($result1=='Mon') $result1=_CAL_MON;
				else if($result1=='Tue') $result1=_CAL_TUE;
				else if($result1=='Wed') $result1=_CAL_WED;
				else if($result1=='Thu') $result1=_CAL_THU;
								else if($result1=='Fri') $result1=_CAL_FRI;
				$result.=$result1;
				break;
			case'F':
				list( $jyear, $jmonth, $jday ) = gregorian_to_jalali($year, $month, $day);
				$result.=Icms_getMonthNameById($jmonth);
				break;
			case 'g':
				$result.=date('g',$need);
				break;
			case 'G':
				$result.=date('G',$need);
				break;
				case 'h':
				$result.=date('h',$need);
				break;
			case 'H':
				$result.=date('H',$need);
				break;
			case 'i':
				$result.=date('i',$need);
				break;
			case 'j':
				list( $jyear, $jmonth, $jday ) = gregorian_to_jalali($year, $month, $day);
				$result.=$jday;
				break;
			case 'l':
				$result1=date('l',$need);
				if($result1=='Saturday') $result1=_CAL_SATURDAY;
				else if($result1=='Sunday') $result1=_CAL_SUNDAY;
				else if($result1=='Monday') $result1=_CAL_MONDAY;
				else if($result1=='Tuesday') $result1=_CAL_TUESDAY;
				else if($result1=='Wednesday') $result1=_CAL_WEDNESDAY;
				else if($result1=='Thursday') $result1=_CAL_THURSDAY;
				else if($result1=='Friday') $result1=_CAL_FRIDAY;
				$result.=$result1;
				break;
			case 'm':
				list( $jyear, $jmonth, $jday ) = gregorian_to_jalali($year, $month, $day);
				if($jmonth<10) $result1='0'.$jmonth;
				else	$result1=$jmonth;
				$result.=$result1;
				break;
			case 'M':
				list( $jyear, $jmonth, $jday ) = gregorian_to_jalali($year, $month, $day);
				$result.=Icms_getMonthNameById($jmonth);
				break;
			case 'n':
				list( $jyear, $jmonth, $jday ) = gregorian_to_jalali($year, $month, $day);
				$result.=$jmonth;
				break;
			case 's':
				$result.=date('s',$need);
				break;
			case 'S':
				$result.=_CAL_SUFFIX;
				break;
			case 't':
				$result.=lastday ($month,$day,$year);
				break;
			case 'w':
				$result.=date('w',$need);
				break;
			case 'y':
				list( $jyear, $jmonth, $jday ) = gregorian_to_jalali($year, $month, $day);
				$result.=substr($jyear,2,4);
				break;
			case 'Y':
				list( $jyear, $jmonth, $jday ) = gregorian_to_jalali($year, $month, $day);
				$result.=$jyear;
				break;
			default:
				$result.=$subtype;
		}
	$i++;
	}
	return $result;
}


?>