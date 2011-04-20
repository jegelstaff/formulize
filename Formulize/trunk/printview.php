<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
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
##  Author of this file: Freeform Solutions and NS Tai (aka tuff) and others ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulize                                                       ##
###############################################################################

require_once "../../mainfile.php";
//include XOOPS_ROOT_PATH.'/header.php';

PRINT "<HTML>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";
PRINT "<HEAD>";
print "<link rel='stylesheet' type='text/css' media='all' href='".getcss($xoopsConfig['theme_set'])."'>\n";
// figure out if this is XOOPS or ICMS
if(file_exists(XOOPS_ROOT_PATH."/class/icmsform/index.html")) {
	print "<link rel=\"stylesheet\" media=\"screen\" href=\"".XOOPS_URL."/icms.css\" type=\"text/css\" />\n";
} 
PRINT "</HEAD>";

$formframe = $_POST['formframe'];
$ventry =  $_POST['lastentry'];
$mainform = $_POST['mainform'];
$ele_allowed = $_POST['elements_allowed'];
$screenid = $_POST['screenid'];
$currentPage = $_POST['currentpage'];

$titleOverride = "";

if($ele_allowed) { // only present when a specific page in a multipage is requested (or in future, a list of elements from a form screen)
	$elements_allowed = explode(",",$ele_allowed);
	$formframetemp = $formframe;
	unset($formframe);
	$formframe['formframe'] = $formframetemp;
	$formframe['elements'] = $elements_allowed;
	// if there's a currentPage, then use that page title from the screen (currentpage is only present if there's a screen id)
	if($currentPage) {
		$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
    $multiPageScreen = $screen_handler->get($screenid);
		$pageTitles = $multiPageScreen->getVar('pagetitles');
		$titleOverride = $pageTitles[$currentPage-1];
	}
}

if( ! is_array( $formframe ) && $screenid && !$ele_allowed) { // no element list passed in, but there is a screen id, so assume a multipage form
  $screen_handler =& xoops_getmodulehandler('screen', 'formulize');
  $screen = $screen_handler->get($screenid);
  $screen_type = $screen->getVar('type');

  if( $screen_type == 'multiPage' ) {
    $screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
    $multiPageScreen = $screen_handler->get($screenid);

    // start: taken from class/multiPageScreen.php
		$conditions = $multiPageScreen->getVar('conditions');
		if(isset($conditions[0]['details'])) {
				unset($conditions);
		    foreach($multiPageScreen->getVar('conditions') as $pageid=>$condata) {
		        $pagenumber = $pageid+1;
		        $conditions[$pagenumber] = array(0=>$condata['details']['elements'], 1=>$condata['details']['ops'], 2=>$condata['details']['terms']);
		    }
				ksort($conditions);
		} else {
				ksort($conditions);
				array_unshift($conditions, "");
				unset($conditions[0]);
		}
    //print_r( $conditions ); //exit();
    // stop

    $pages = $multiPageScreen->getVar('pages');

    $elements = array();
    foreach( $pages as $currentPage=>$page ) {
      if( canViewPage( $ventry, $currentPage+1, $conditions, $formframe, $mainform ) ) {
        foreach( $page as $element ) {
          $elements[] = $element;
        }
      }
    }
	  $formframetemp = $formframe;
	  unset($formframe);
	  $formframe['elements'] = $elements;
	  $formframe['formframe'] = $formframetemp;
		$pages = $multiPageScreen->getVar('pages');
		$pagetitles = $multiPageScreen->getVar('pagetitles');
		ksort($pages); // make sure the arrays are sorted by key, ie: page number
		ksort($pagetitles);
		array_unshift($pages, ""); // convention dictates that the page arrays start with [1] and not [0], for readability when manually using the API, so we bump up all the numbers by one by adding something to the front of the array
		array_unshift($pagetitles, ""); 
    unset($pages[0]); // get rid of the part we just unshifted, so the page count is correct
		unset($pagetitles[0]);
		$formframe['pagetitles'] = $pagetitles;
		$formframe['pages'] = $pages;
	
		// use the screen title
		$titleOverride = $multiPageScreen->getVar('title');
		
  }
}

$module_handler =& xoops_gethandler('module');
$config_handler =& xoops_gethandler('config');
$formulizeModule =& $module_handler->getByDirname("formulize");
$formulizeConfig =& $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
$modulePrefUseToken = $formulizeConfig['useToken'];
$useToken = $screen ? $screen->getVar('useToken') : $modulePrefUseToken;  // screen type for regular forms doesn't yet exist, but when it does, this check will be relevant
if(isset($GLOBALS['xoopsSecurity']) AND $useToken) { // avoid security check for versions of XOOPS that don't have that feature, or for when it's turned off
	if (!$GLOBALS['xoopsSecurity']->check()) { 
	  print "<b>Error: it appears you should not be viewing this page.  Please contact the webmaster for assistance.</b>";
		return false;
	}
}

//print "<p> formframe = ".$formframe."</p>";
//print "<p> mainform = ".$mainform."</p>";
//print "<p> ventry = ".$ventry."</p>";

print "<center>"; 
print "<table width=100%><tr><td width=5%></td><td width=90%>";
print "<div id=\"formulize-printpreview\">";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php"; // needed to get the benchmark function available
displayForm($formframe, $ventry, $mainform, "", "{NOBUTTON}", "", $titleOverride); // if it's a single and they don't have group or global scope
print "</div>";
print "</td><td width=5%></td></tr></table>";
print "</center></body>";

PRINT "</HTML>";
//include XOOPS_ROOT_PATH.'/footer.php';

function canViewPage( $entry, $currentPage, $conditions, $formframe, $mainform ) {
  // start: taken from include/formdisplaypages.php
  // check to see if there are conditions on this page, and if so are they met
  // if the conditions are not met, move on to the next page and repeat the condition check
  // conditions only checked once there is an entry!
  $pagesSkipped = false;
  if(is_array($conditions) AND $entry) {
    $conditionsMet = false;
    if(isset($conditions[$currentPage]) AND count($conditions[$currentPage][0])>0) { // conditions on the current page
			$thesecons = $conditions[$currentPage];
			$elements = $thesecons[0];
			$ops = $thesecons[1];
			$terms = $thesecons[2];
			$types = $thesecons[3]; // indicates if the term is part of a must or may set, ie: boolean and or or
			$start = 1;
			$oomstart = 1;
			$filter = "";
			$oomfilter = "";
			foreach($elements as $i=>$thisElement) {
				if($ops[$i] == "NOT") { $ops[$i] = "!="; }
				if($types[$i] == "oom") {
					if($oomstart) {
						$oomfilter = $elements[$i]."/**/".trans($terms[$i])."/**/".$ops[$i];
						$oomstart = 0;

					} else {
						$oomfilter .= "][".$elements[$i]."/**/".trans($terms[$i])."/**/".$ops[$i];
					}
				} else {

					if($start) {
						$filter = $entry."][".$elements[$i]."/**/".trans($terms[$i])."/**/".$ops[$i];
						$start = 0;
					} else {

						$filter .= "][".$elements[$i]."/**/".trans($terms[$i])."/**/".$ops[$i];
					}
				}
			}

			if($oomfilter AND $filter) {
				$finalFilter = array();
				$finalFilter[0][0] = "AND";
				$finalFilter[0][1] = $filter;
				$finalFilter[1][0] = "OR";
				$finalFilter[1][1] = $oomfilter;
				$masterBoolean = "AND";
			} elseif($oomfilter) {
				// need to add the $entry as a separate filter from the oom, so the entry and oom get an AND in between them
				$finalFilter = array();
				$finalFilter[0][0] = "AND";
				$finalFilter[0][1] = $entry;
				$finalFilter[1][0] = "OR";
				$finalFilter[1][1] = $oomfilter;
				$masterBoolean = "AND";
			} else {
				$finalFilter = $filter;
				$masterBoolean = "AND";
			}
			include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
			//$data = getData($frid, $fid, $finalFilter, $masterBoolean);
			if($mainform) {
				$data = getData($formframe, $mainform, $finalFilter, $masterBoolean);
			} else {
				$data = getData("", $formframe, $finalFilter, $masterBoolean);
			}
			if($data[0] == "") { 
				$pagesSkipped = true;
			} else {
				$conditionsMet = true;
			}
		} else {
			// no conditions on the current page
			$conditionsMet = true;
		}
  }
  // stop
  //print "conditionsMet: " . (($conditionsMet)?'true':'false') . ", pagesSkipped: " . (($pagesSkipped)?'true':'false');
  return ! $pagesSkipped;
}
?>
