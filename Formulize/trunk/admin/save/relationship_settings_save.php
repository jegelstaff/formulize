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

// this file handles saving of submissions from the relationship_settings page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}


$op = $_POST['formulize_admin_op'];
$frid = $_POST['formulize_admin_key'];


// save all changes, the user could have modified links and then clicked add or
// remove which causes a page reload, so preserve all user changes
updateframe($frid);

switch ($op) {
	case "addlink":
		$fid1 = $processedValues['relationships']['fid1'];
		$fid2 = $processedValues['relationships']['fid2'];
		if(addlink($fid1, $fid2, $frid)) {
      // send code to client that will to be evaluated
      print "/* eval */ window.location = window.location;";
    }
		break;
	case "dellink":
    $lid = $_POST['formulize_admin_lid'];
    if(deletelink($lid, $frid)) {
      // send code to client that will to be evaluated
      print "/* eval */ window.location = window.location;";
    }
		break;
}


function addlink($fid1, $fid2, $cf) {
	global $xoopsDB;

  $success = true;

	// This is the function that makes a new form entry in the framework DB tables
	$forms = array();
	$forms[] = $fid1;
	$forms[] = $fid2;

	// check that the forms are new and if so add them to the forms table
	foreach($forms as $fid) {
		$checkq = "SELECT ff_id FROM " . $xoopsDB->prefix("formulize_framework_forms") . " WHERE ff_form_id='$fid' AND ff_frame_id = '$cf'";
		$res=$xoopsDB->query($checkq);
		if($xoopsDB->getRowsNum($res)==0) {
			$writeform = "INSERT INTO " . $xoopsDB->prefix("formulize_framework_forms") . " (ff_frame_id, ff_form_id) VALUES ('$cf', '$fid')";
			if(!$res = $xoopsDB->query($writeform)) {
				print "Error: could not add form: $fid to framework: $cf";
        $success = false;
			}
		}		
	}

	// write the link to the links table
	$writelink = "INSERT INTO " . $xoopsDB->prefix("formulize_framework_links") . " (fl_frame_id, fl_form1_id, fl_form2_id, fl_key1, fl_key2, fl_relationship, fl_unified_display, fl_common_value) VALUES ('$cf', '$fid1', '$fid2', 0, 0, 1, 0, 0)";
	if(!$res = $xoopsDB->query($writelink)) {
		print "Error: could not write link of $fid1 and $fid2 to the links table for framework $cf";
    $success = false;
	}

  return $success;
}

function deletelink($fl_id, $cf) {
	global $xoopsDB;

  $success = true;

	$deleteq = "DELETE FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_id='$fl_id'";
	if(!$res = $xoopsDB->queryF($deleteq)) {
		print "Error: link deletion unsuccessful";
    $success = false;
	}

  return $success;
}


function updateframe($cf) {
  global $processedValues;

	// update the frame with the settings specified on the main modframe page
	//print_r($processedValues['relationships']);
  $links = array( );

	foreach($processedValues['relationships'] as $key=>$value) {
		if(substr($key, 0, 3) == "rel") {
			$fl_id = substr($key, 3);
      $links[$fl_id] = '';
			updaterels($fl_id, $value);
		}

		if(substr($key, 0, 8) == "linkages") {
			$fl_id = substr($key, 8);
      $links[$fl_id] = '';
			updatelinks($fl_id, $value);
		}

		if(substr($key, 0, 7) == "display") {
			$fl_id = substr($key, 7);
      $links[$fl_id] = '*';
			updatedisplays($fl_id, $value);
		}
	}

  // for checkboxes that have not been checked, reset to nothing
	foreach($links as $key=>$value) {
    if(!$value == '*') {
			updatedisplays($key, 0);
    }
  }
}

function updaterels($fl_id, $value) {
	global $xoopsDB;

	$sql = "UPDATE " . $xoopsDB->prefix("formulize_framework_links") . " SET fl_relationship='$value' WHERE fl_id='$fl_id'";
	if(!$res = $xoopsDB->query($sql)) {
		print "Error: could not update relationship for framework link $fl_id";
	}
}

function updatelinks($fl_id, $value) {
  global $xoopsDB, $processedValues;

	/*if($value == "common" AND $_POST['common_fl_id'] == $fl_id) {
		$keys[0] = $_POST['common1choice'];
		$keys[1] = $_POST['common2choice'];
		$common = 1;
	} else {*/
		$keys = explode("+", $value);
		//$common = $_POST['preservecommon'.$fl_id] == $value ? 1 : 0;
		$common = $processedValues['relationships']['preservecommon'.$fl_id] == $value ? 1 : 0;
	//}

  if(intval($keys[0]) > 0 && intval($keys[1]) > 0 && $common == 0) {
    $common = 1;
  }

	$sql = "UPDATE " . $xoopsDB->prefix("formulize_framework_links") . " SET fl_key1='" . $keys[0] . "', fl_key2='" . $keys[1] . "', fl_common_value='$common' WHERE fl_id='$fl_id'";
	if(!$res = $xoopsDB->query($sql)) {
		print "Error: could not update key fields for framework link $fl_id";
	}
}

function updatedisplays($fl_id, $value) {
	global $xoopsDB;
	$sql = "UPDATE " . $xoopsDB->prefix("formulize_framework_links") . " SET fl_unified_display='$value' WHERE fl_id='$fl_id'";	
	if(!$res = $xoopsDB->query($sql)) {
		print "Error: could not update unified display setting for framework link $fl_id";
	}
}
?>
