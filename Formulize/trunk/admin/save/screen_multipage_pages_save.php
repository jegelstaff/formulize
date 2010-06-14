<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2010 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions                                  ##
##  URL: http://www.freeformsolutions.ca/formulize                           ##
##  Project: Formulize                                                       ##
###############################################################################

// this file handles saving of submissions from the screen_multipage_pages page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}


//print_r($_POST);
//print_r($processedValues);


$aid = intval($_POST['aid']);
$sid = $_POST['formulize_admin_key'];
$op = $_POST['formulize_admin_op'];
$index = $_POST['formulize_admin_index'];

$screens = $processedValues['screens'];


$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
$screen = $screen_handler->get($sid);

// conditions...
// pagecons1 is the yes/no for conditions -- stored as: conditions[1]['pagecons']
// page1elements, page1ops, page1terms are arrays with all the condition details -- stored as: conditions[1]['details']['elements'][0..n], etc

$pages = array();
$pagetitles = array();
//$conditions = array();

foreach($screens as $k=>$v) {
	if(substr($k, 0, 10) == "pagetitle_") {
		$pagetitles[substr($k, 10)] = $v; 
	/*}elseif(substr($k, 0, 8) == "pagecons") {
    $conditions[substr($k, 8)]['pagecons'] = $v;
	}elseif(substr($k, 0, 12) == "pageelements") {
    foreach($v as $key=>$value) {
        $conditions[substr($k, 12)]['details']['elements'][$key] = $value; 
    }
  }elseif(substr($k, 0, 7) == "pageops") {
    foreach($v as $key=>$value) {
        $conditions[substr($k, 7)]['details']['ops'][$key] = $value;
    }
  }elseif(substr($k, 0, 9) == "pageterms") {
    foreach($v as $key=>$value) {
        if($value != "") {
            $conditions[substr($k, 9)]['details']['terms'][$key] = $value;
        } else {
            unset($conditions[substr($k, 9)]['details']['elements'][$key]); // don't record elements or ops if there is no term specified
            unset($conditions[substr($k, 9)]['details']['ops'][$key]);
        }
    }*/
  }elseif(substr($k, 0, 4) == "page") { // page must come last since those letters are common to the beginning of everything
		$pages[substr($k, 4)] = $v;
	} 
}
// handle "deleting" conditions...
/*foreach($conditions as $pagenum=>$datapiece) {
   if(isset($datapiece['pagecons']) AND $datapiece['pagecons'] == "none") {
        $conditions[$pagenum]['details']['elements'] = array();
        $conditions[$pagenum]['details']['ops'] = array();
        $conditions[$pagenum]['details']['terms'] = array();
    }
}*/


// alter the information based on a user add or delete
switch ($op) {
	case "addpage":
    $pages[]=array();
    $pagetitles[]='New page';
    //$conditions[]=array();
		break;
	case "delpage":
    array_splice($pages, $index, 1);
    array_splice($pagetitles, $index, 1);
    //array_splice($conditions, $index, 1);
		break;
}


//print_r($pages);
//print_r($pagetitles);


$screen->setVar('pages',serialize($pages));
$screen->setVar('pagetitles',serialize($pagetitles));
//$screen->setVar('conditions',serialize($conditions));


if(!$screen_handler->insert($screen)) {
  print "Error: could not save the screen properly: ".mysql_error();
}


// make sure the data was saved before reloading page
switch ($op) {
	case "addpage":
    // send code to client that will to be evaluated
    print "/* eval */ window.location = window.location;";
		break;
	case "delpage":
    // send code to client that will to be evaluated
    print "/* eval */ window.location = window.location;";
		break;
}
?>
