<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
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
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

// display element data has the following format:
// de_[entry]_[elementid]
// entry is the id_req from formulize_form table
// elementid is the ele_id from the form table
// Need to check for the master list of elements that are being sent back first.

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

if(!$myts) { $myts =& MyTextSanitizer::getInstance(); }

// lock formulize, formulize_form, formulize_other
$xoopsDB->query("LOCK TABLES " . $xoopsDB->prefix("formulize") . " READ, " . $xoopsDB->prefix("formulize_form") . " WRITE, " . $xoopsDB->prefix("formulize_other") . " WRITE");

$notEntriesList = array();

foreach($_POST as $k=>$v) {

	if(substr($k, 0, 4) == "deh_") { // find all deh cues
		$de_metadata = substr($k, 4);
		$de_metadata = explode("_", $de_metadata);
		// de_metadata[0] is the entry that this value should go into -- "new" indicates it is a new entry that has not been created yet
		// de_metadata[1] is the ID of the element

		// cases to handle...
		// 1. new entry, no value (do nothing)
		// 2. new entry, with value (insert new entry)
		// 3. existing entry, no value, was set (so blank it)
		// 4. existing entry, no value, was empty (do nothing)
		// 5. existing entry, with value, was set (update existing entry)
		// 6. existing entry, with value, was empty (insert value to existing entry)

		if(!$formulize_mgr) {
			$formulize_mgr =& xoops_getmodulehandler('elements', 'formulize');
		}
		$element =& $formulize_mgr->get($de_metadata[1]);
		$id_reqForWritingOther = $de_metadata[0]; // used for passing the id_req to the function that handles 'Other' textboxes

		if($value = $_POST['de_' . $de_metadata[0] . "_" . $de_metadata[1]] OR $v!="empty"){ // only initialize needed stuff if there is a value to write, or a value to blank
			$uid = $xoopsUser->getVar('uid');
			$date = date ("Y-m-d");
			$realcap = getRealCaption($de_metadata[1]);
			$value = prepDataForWrite($element, $value);
			if($value == "{SKIPTHISDATE}") { continue; } // do not process this date element
			if(!$maxIdReq) { $maxIdReq = getMaxIdReq(); }
			$GLOBALS['maxidreq'] = $maxIdReq; // last id req that would be written goes into global scope
		}

		$sql = "";
		// 2. -- new entry, with value... (assuming $v is "empty" when $de_metadata[0] is "new")
		if($de_metadata[0] == "new" AND $_POST['de_' . $de_metadata[0] . "_" . $de_metadata[1]]) { 
			$sql="INSERT INTO ".$xoopsDB->prefix("formulize_form")." (id_form, id_req, ele_id, ele_type, ele_caption, ele_value, uid, proxyid, date, creation_date) VALUES (\"" . $element->getVar('id_form') . "\", \"" . $maxIdReq . "\", \"\", \"" .$element->getVar('ele_type'). "\", \"$realcap\", \"$value\", \"$uid\", \"$uid\", \"$date\", \"$date\")";
			$id_reqForWritingOther = $maxIdReq;
	
			$notEntriesList['new_entry'][$element->getVar('id_form')][] = $maxIdReq;

		// 3. -- existing entry, no value, was set (assuming $de_metadata[0] is not NEW when $v is set) -- blank the value in the DB
		} elseif($v == "set" AND !$_POST['de_' . $de_metadata[0] . "_" . $de_metadata[1]]) { 
			$sql="DELETE FROM " .$xoopsDB->prefix("formulize_form") . " WHERE ele_caption=\"$realcap\" AND id_req='" . $de_metadata[0] . "'";

			$notEntriesList['update_entry'][$element->getVar('id_form')][] = $de_metadata[0];

		// 5. -- existing entry, with value, was set (assuming $de_metadata[0] is not NEW when $v is set. -- update existing
		} elseif($v == "set" AND $_POST['de_' . $de_metadata[0] . "_" . $de_metadata[1]]) {
			$sql="UPDATE " .$xoopsDB->prefix("formulize_form") . " SET ele_value=\"$value\", proxyid=\"$uid\", date=\"$date\" WHERE ele_caption=\"$realcap\" AND id_req='" . $de_metadata[0] . "'";

			$notEntriesList['update_entry'][$element->getVar('id_form')][] = $de_metadata[0];

		// 6. -- existing entry, with value, was empty -- insert with this $entry value
		} elseif($v == "empty" AND $de_metadata[0] != "new" AND $_POST['de_' . $de_metadata[0] . "_" . $de_metadata[1]]) {
			// need to get the creation date for this entry and the original user id
			$getMeta = "SELECT creation_date, uid FROM " . $xoopsDB->prefix(formulize_form) . " WHERE id_req='" . $de_metadata[0] . "' AND creation_date>0 ORDER BY creation_date DESC LIMIT 0,1";
			$gdres = $xoopsDB->query($getMeta);
			list($create_date, $org_uid) = $xoopsDB->fetchRow($gdres);
			$sql="INSERT INTO ".$xoopsDB->prefix("formulize_form")." (id_form, id_req, ele_id, ele_type, ele_caption, ele_value, uid, proxyid, date, creation_date) VALUES (\"" . $element->getVar('id_form') . "\", \"" . $de_metadata[0] . "\", \"\", \"" .$element->getVar('ele_type'). "\", \"$realcap\", \"$value\", \"$org_uid\", \"$uid\", \"$date\", \"$create_date\")";

			$notEntriesList['update_entry'][$element->getVar('id_form')][] = $de_metadata[0];

		}

		if($sql) { // run the query
			//print $sql . "<br>";
			if(!$res = $xoopsDB->query($sql)) {
				exit("Error: unable to update value of displayed element using the following SQL:<br>$sql");
			}
		}

		writeOtherValues($id_reqForWritingOther, $element->getVar('id_form'));
		
		unset($_POST[$k]);  // prevents multiple operations with this data if multiple pageworks blocks are visible -- the fact this file is "include_once" now should actually handle the multiple block situation adequately, but this protection can't hurt to keep in
	}
	unset($element);
}

// unlock tables
$xoopsDB->query("UNLOCK TABLES");


// process notifications
foreach($notEntriesList as $notEvent=>$notDetails) {
	foreach($notDetails as $notFid=>$notIdReqs) {
		sendNotifications($notFid, $notEvent, $notIdReqs);
	}
}


?>