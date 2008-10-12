<?

###############################################################################
##             Pageworks - page logic and display module for XOOPS           ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions 					     ##
##  Project: Pageworks                                                       ##
###############################################################################

// THIS FILE DRAWS IN THE ui AND RESULTS FOR THE ACTIVITY LOGS
// The logs are for showing the number of views per page and uses of each search term

// Generate list of pages...

global $xoopsDB;
include_once XOOPS_ROOT_PATH . "/class/xoopsformloader.php";
include_once XOOPS_ROOT_PATH . "/modules/pageworks/admin/pages.php";

// this function determines the hits to a specified limit for a specified range, on a specified list of items
// $items is an array of pages, if empty then search terms are assumed
// $startdate and $enddate are the dates specified for the range
// $limit is the max number of results to return (ie: only the top ten pages for a month)
function getHitsForLog($items="", $startdate, $enddate, $limit) {
	global $xoopsDB;
	if(!$items) { // we're searching for search terms
		$log_item_filter = "log_item = 0";
	} else {
		$start = 1;
		foreach($items as $item) {
			$item = addslashes($item);
			if($start) {
				$log_item_filter = "log_item = '$item'";
				$start = 0;
			} else {
				$log_item_filter .= " OR log_item = '$item'";
			}
		}
	}

	$sql = "SELECT log_item FROM " . $xoopsDB->prefix("pageworks_log") . " WHERE ($log_item_filter) AND (log_date >= '$startdate' AND log_date <= '$enddate')";
	$allHits = $xoopsDB->query($sql);
	while($hit = $xoopsDB->fetchArray($allHits)) {
		$hits[] = $hit['log_item'];
	}
	$counts = array_count_values($hits);
	$found_items = array_keys($counts);
	array_multisort($counts, SORT_DESC, $found_items);
	if($limit == 0) { // if they specified no limit, then return all hits
		$limit = count($counts);
	}
	for($i=0;$i<$limit;$i++) {
		$thishit = array_shift($counts);
		if($thishit != "") { $found[$found_items[$i]] = $thishit; }
	}
	//print_r($found);
	//print "<br>";
	return $found;

}


// this function returns the last day in the month for each month of the year (figures out leap years by dividing year by four)
function monthData($year, $month) {
	switch($month) {
		case "1":
			$to_return['last'] = 31;
			$to_return['name'] = "January";
			break;
		case "2":
			if($year%4 == 0) {
				$to_return['last'] = 29;
			} else {
				$to_return['last'] = 28;
			}
			$to_return['name'] = "February";
			break;
		case "3":
			$to_return['last'] = 31;
			$to_return['name'] = "March";
			break;
		case "4":
			$to_return['last'] = 30;
			$to_return['name'] = "April";
			break;
		case "5":
			$to_return['last'] = 31;
			$to_return['name'] = "May";
			break;
		case "6":
			$to_return['last'] = 30;
			$to_return['name'] = "June";
			break;
		case "7":
			$to_return['last'] = 31;
			$to_return['name'] = "July";
			break;
		case "8":
			$to_return['last'] = 31;
			$to_return['name'] = "August";
			break;
		case "9":
			$to_return['last'] = 30;
			$to_return['name'] = "September";
			break;
		case "10":
			$to_return['last'] = 31;
			$to_return['name'] = "October";
			break;
		case "11":
			$to_return['last'] = 30;
			$to_return['name'] = "November";
			break;
		case "12":
			$to_return['last'] = 31;
			$to_return['name'] = "December";
			break;
	}
	return $to_return;
}

// this function draws in a result set returned by the getHitsForLog function
function drawResults($results) {
	global $xoopsDB;
	print "<tr><td class=head><p>Item</p></td><td class=head><p>Hits</p></td></tr>\n";
	foreach($results as $hit=>$count) {
		if($class == "even") {
			$class = "odd";
		} else {
			$class = "even";
		}
		if(is_numeric($hit)) { 
			$page_data = selectPage($hit);
			$page_data_result = $xoopsDB->fetchArray($page_data);
			$itemtag = $page_data_result['page_name'];
		} else {
			$itemtag = $hit;
		}
		print "<tr><td class=$class><p>$itemtag</p></td><td class=$class><p>$count</p></td></tr>\n";
	}
} 



// generate page list
$order = $_POST['listorder'];
$pages = selectPages($order); // $pages is a standard query result containing all pages
$indexer = 0;
while($page = $xoopsDB->fetchArray($pages)) {
	$pageOptions[$page['page_id']] = $page['page_id'] . " - " . $page['page_name'];
}

$logcontrols = new xoopsThemeForm('Website Activity Logs', 'logcontrols', XOOPS_URL.'/modules/pageworks/admin/index.php?op=logs');

// the list of pages
$pagelist = new xoopsFormSelect("View stats for which pages?", "selectedPages", $_POST['selectedPages'], "8", true);
$pagelist->setDescription("To view Stats for search terms instead, make sure no pages are selected.");
$pagelist->addOptionArray($pageOptions);

// the page order controls
if(!$_POST['listorder']) {
	$lodef = "creation";
} else {
	$lodef = $_POST['listorder'];
}
$listorder = new xoopsFormRadio("List pages in...", "listorder", $lodef);
$listorder->addOption("creation", "Creation order");
$listorder->addOption("alpha", "Alphabetical order");

// the number of results to return
if(!isset($_POST['maxresults'])) {
	$maxdef = 10;
} else {
	$maxdef = $_POST['maxresults'];
}
$maxresults = new xoopsFormText("View the top X results", "maxresults", 3, 3, $maxdef);
$maxresults->setDescription("To view all results, set this to '0'.");

// set start date
if(!$_POST['startdate']) {
	$sddef = "2005-01-01";
} else {
	$sddef = $_POST['startdate'];
}
$startdate = new xoopsFormTextDateSelect("View stats starting from this date:", "startdate", "10", strtotime($sddef));

// set end date
if(!$_POST['enddate']) {
	$today = getDate();
	$eddef = $today['year'] . "-" . $today['mon'] . "-" . $today['mday'];
} else {
	$eddef = $_POST['enddate'];
}
$enddate = new xoopsFormTextDateSelect("View stats ending on this date:", "enddate", "10", strtotime($eddef));

// show total results or results divided up for each month
if(!$_POST['bymonth']) {
	$bmdef = "0";
} else {
	$bmdef = $_POST['bymonth'];
}
$bymonth = new xoopsFormRadio("View stats month by month?", "bymonth", $bmdef);
$bymonth->addOption("1", "Yes");
$bymonth->addOption("0", "No");

// submit button
$submitbutton = new xoopsFormButton("", "submitx", "Show Stats", "submit");

$logcontrols->addElement($pagelist);
$logcontrols->addElement($listorder);
$logcontrols->addElement($maxresults);
$logcontrols->addElement($startdate);
$logcontrols->addElement($enddate);
$logcontrols->addElement($bymonth);
$logcontrols->addElement($submitbutton);

print $logcontrols->render();

// IF THEY SUBMITTED THE CONTROL FORM, THEN DRAW IN STATS ACCORDING TO OPTIONS...
if($_POST['submitx']) {


// 2. conditional based on bymonth or not
// 3. if not bymonth, total hits for items during range and then cull by maxresult limit
// 4. if by month, then repeat step three but the range is each month, and collect results together

if($_POST['bymonth']) {
	// 1. get start year, get end year
	// 2. get start month for each year, get end month for each year
	// loop through years and then months and get results for each
	$sstamp = strtotime($_POST['startdate']);
	$estamp = strtotime($_POST['enddate']);
	$s = getDate($sstamp);
	$e = getDate($estamp);
	$curyear = $s['year'];
	$curmonth = $s['mon'];
	$firstmonth = 1;
	while($curyear <= $e['year'] AND $curmonth <= $e['mon']) {
		if($firstmonth) {
			$sd = $_POST['startdate'];
			if($e['year'] == $s['year'] AND $e['mon'] == $s['mon']) { // if the two dates are in the same month
				$ed = $_POST['enddate'];
			} else {
				$lastday = monthData($curyear, $curmonth); 
				$ed = $curyear . "-" . $curmonth . "-" . $lastday['last'];
			}
			$found[$curyear][$curmonth] = getHitsForLog($_POST['selectedPages'], $sd, $ed, $_POST['maxresults']);
			$firstmonth = 0;
		} else {
			$sd = $curyear . "-" . $curmonth . "-01";
			if($e['year'] == $curyear AND $e['mon'] == $curmonth) { // this is the last month
				$ed = $_POST['enddate'];
			} else {
				$lastday = monthData($curyear, $curmonth); 
				$ed = $curyear . "-" . $curmonth . "-" . $lastday['last'];
			}
			$found[$curyear][$curmonth] = getHitsForLog($_POST['selectedPages'], $sd, $ed, $_POST['maxresults']);
		}
		//printDebug($curyear, $curmonth, $_POST['startdate'], $_POST['enddate'], $sd, $ed);
		$curmonth++;
		if($curmonth == "13") {
			$curmonth = 1;
			$curyear++;
		}
	}
} else {
	$found[] = getHitsForLog($_POST['selectedPages'], $_POST['startdate'], $_POST['enddate'], $_POST['maxresults']);
}

print "<br><br>";

print "<table class=outer><tr><th colspan=2>Activity Stats From " . $_POST['startdate'] . " to " . $_POST['enddate'] . "</th></tr>\n";

foreach($found as $year=>$months) {
	//print_r($months);
	//print "<br><br>";
	if($year == 0) { // this is not a bymonth result
		drawResults($months);
	} else { // divide results into months
		foreach($months as $month=>$results) {
			if(count($results)>0) {
				$monthData = monthData($year, $month);
				print "<tr><th colspan=2>" . $monthData['name'] . ", $year</th></tr>\n";
				drawResults($results);
			}
		}
	}
}

print "</table>";

 
}

// dumps info to screen regarding by-month hit count generation
function printDebug($curyear, $curmonth, $startdate, $enddate, $sd, $ed) {
	print "Start date for query: $startdate<br>";
	print "End date for query: $enddate<br>";
	print "Current month: $curmonth, $curyear<br>";
	print "Start date for this month: $sd<br>";
	print "End date for this month: $ed<br>";
	print "<br>";
}


?>