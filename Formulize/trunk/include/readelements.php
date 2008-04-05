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

// if we're being called from pageworks, or elsewhere, then certain values won't be set so we'll need to check for them in other ways...
if(!$gperm_handler) {
	$gperm_handler =& xoops_gethandler('groupperm');
}
if(!isset($mid)) {
	$mid = getFormulizeModId();
}
	

if(!$myts) { $myts =& MyTextSanitizer::getInstance(); }

// lock formulize, formulize_form, formulize_other
$xoopsDB->query("LOCK TABLES " . $xoopsDB->prefix("formulize") . " READ, " . $xoopsDB->prefix("group_permission") . " READ, " . $xoopsDB->prefix("formulize_form") . " WRITE, " . $xoopsDB->prefix("formulize_other") . " WRITE");

$notEntriesList = array();
$formulize_submittedElementCaptions = array(); // put into global scope and pulled down by readform.php when determining what elements have been submitted, so we don't blank data that is sent this way

$groups = $xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS; // for some reason, even though this is set in pageworks index.php file, depending on how/when this file gets executed, it can have no value (in cases where there are pageworks blocks on pageworks pages, for instance?!)

foreach($_POST as $k=>$v) {

	if(substr($k, 0, 9) == "denosave_") { // handle no save elements
		$element_metadata = explode("_", $k);
		if(!$formulize_mgr) {
			$formulize_mgr =& xoops_getmodulehandler('elements', 'formulize');
		}
		$element =& $formulize_mgr->get($element_metadata[2]);
		$noSaveHandle = $element->getVar('ele_colhead') ? $element->getVar('ele_colhead') : $element->getVar('ele_caption');
		$noSaveHandle = str_replace(" ", "", ucwords(strtolower($noSaveHandle)));
		// note this will assign the raw value from POST to these globals.  It will not be human readable in many cases.
		$GLOBALS['formulizeEleSub_' . $noSaveHandle] = $v;
		$GLOBALS['formulizeEleSub_' . $element_metadata[2]] = $v;
		unset($element);
		continue;
	}

	// handle gathering of subform default blank submissions, and store them for processing after the entry is known, which is handled in formdisplay.php after handleformsubmisison (knowing the entry is really only required when linked selectboxes are in play -- common value frameworks could be done right here, but we don't do that for consistency)
	$desubformFound = false;
	$desubformEntryIndex = 100; // note that 0 to 4 are the only valid values for this
	$subformIdReq = 0;
	if(substr($k, 0, 11) == "deh_subform") {
		// need to prep the data for writing and store it
		$de_metadata = substr($k, 13); // must be two higher because of the pressence of the entry index number and the underscore
		$de_metadata = explode("_", $de_metadata);
		$desubformEntryIndex = substr($k, 11, 1); // get the number immediately after 'desubform' ... this will uniquely identify the defaultblank space within a given subform
		$deprefix = 'de_subform'.$desubformEntryIndex.'_';
		if(isset($_POST[$deprefix . $de_metadata[0] . "_" . $de_metadata[1]]) AND trim($_POST[$deprefix . $de_metadata[0] . "_" . $de_metadata[1]] === "")) { continue; } // skip any blank values, since in the case of subform defaults we don't want to save any information from elements that have not been filled in, don't even want to create the entries -- jwe Feb 3 2008
		$desubformFound = true;
	}
		

	if(substr($k, 0, 4) == "deh_" OR $desubformFound) { // find all deh cues
		if(!$desubformFound) {
			$de_metadata = substr($k, 4);
			$de_metadata = explode("_", $de_metadata);
			// de_metadata[0] is the entry that this value should go into -- "new" indicates it is a new entry that has not been created yet
			// de_metadata[1] is the ID of the element
			$deprefix = 'de_';
		}

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

		if(isset($_POST[$deprefix . $de_metadata[0] . "_" . $de_metadata[1]]) OR $v!="empty"){ // only initialize needed stuff if there is a value to write, or a value to blank
			$value = $_POST[$deprefix . $de_metadata[0] . "_" . $de_metadata[1]];
			$uid = $xoopsUser->getVar('uid');
			$date = date ("Y-m-d");
			$realcap = getRealCaption($de_metadata[1]);
			$value = prepDataForWrite($element, $value);
			if($value == "{SKIPTHISDATE}") {
				if($v=="set") {
					unset($_POST[$deprefix . $de_metadata[0] . "_" . $de_metadata[1]]); // blank this date if it has an existing (set) value
				} else {	
					continue; // ignore this date
				}
			} else {
				$formulize_submittedElementCaptions[$element->getVar('id_form')][] = $realcap; // this global array is picked up in formread.php if a regular form with the same elements is part of the same page.  It needs to be set only for regular entries (ie: don't set this for skipped dates)
			}
			if(!$maxIdReq) { $maxIdReq = getMaxIdReq(); }
			$GLOBALS['maxidreq'] = $maxIdReq; // last id req that would be written goes into global scope
			$GLOBALS['maxidreq'][$element->getVar('id_form')] = $maxIdReq; // the previous global address for maxidreq is deprecated and all references to maxidreq should be updated to use this form-specific version, since multiple forms can have elements showing up on the same pageload!!
		}

		$add_own_entry = $gperm_handler->checkRight("add_own_entry", $element->getVar('id_form'), $groups, $mid);
		$update_own_entry = $gperm_handler->checkRight("update_own_entry", $element->getVar('id_form'), $groups, $mid);
		$update_other_entries = $gperm_handler->checkRight("update_other_entries", $element->getVar('id_form'), $groups, $mid);

		$sql = "";
		
		// 2. -- new entry, with value... (assuming $v is "empty" when $de_metadata[0] is "new")
		if($de_metadata[0] == "new" AND isset($_POST[$deprefix . $de_metadata[0] . "_" . $de_metadata[1]]) AND $add_own_entry) {
			if($desubformFound) {
				// handle determination of maxIdReq differently for the special blank defaults in subforms
				$maxIdReq = isset($GLOBALS['formulize_subformCreateEntry'][$element->getVar('id_form')][$desubformEntryIndex]) ? $GLOBALS['formulize_subformCreateEntry'][$element->getVar('id_form')][$desubformEntryIndex] : getMaxIdReq(); // ensure subforms get the right id_req assigned if this is not the first item for that subform entry
			}
			$value = ($value == "{ID}" AND ($element->getVar('ele_type') == "text" OR $element->getVar('ele_type') == "textarea")) ? $maxIdReq : $value; // for textboxes or textarea boxes with a special value, sub in the id_req
			$value = ($value == "{SEQUENCE}" AND ($element->getVar('ele_type') == "text" OR $element->getVar('ele_type') == "textarea")) ? formulize_getMaxValue($element->getVar('ele_caption'), $element->getVar('id_form')) : $value; // handle the special {SEQUENCE} term
			$sql="INSERT INTO ".$xoopsDB->prefix("formulize_form")." (id_form, id_req, ele_id, ele_type, ele_caption, ele_value, uid, proxyid, date, creation_date) VALUES (\"" . $element->getVar('id_form') . "\", \"" . $maxIdReq . "\", \"\", \"" .$element->getVar('ele_type'). "\", \"$realcap\", \"" . mysql_real_escape_string($value) . "\", \"$uid\", \"$uid\", \"$date\", \"$date\")";
			$id_reqForWritingOther = $maxIdReq;
			$notEntriesList['new_entry'][$element->getVar('id_form')][] = $maxIdReq;
			if($desubformFound) {
				$GLOBALS['formulize_subformCreateEntry'][$element->getVar('id_form')][$desubformEntryIndex] = $maxIdReq; // store the id_req that was assigned to this entry
			}

		// 3. -- existing entry, no value, was set (assuming $de_metadata[0] is not NEW when $v is set) -- blank the value in the DB
		} elseif($v == "set" AND !isset($_POST[$deprefix . $de_metadata[0] . "_" . $de_metadata[1]]) AND ($update_other_entries OR ($update_own_entry AND $uid = getEntryOwner($de_metadata[0])))) { 
			$sql="DELETE FROM " .$xoopsDB->prefix("formulize_form") . " WHERE ele_caption=\"$realcap\" AND id_req='" . $de_metadata[0] . "'";
			$notEntriesList['update_entry'][$element->getVar('id_form')][] = $de_metadata[0];
			array_pop($formulize_submittedElementCaptions[$element->getVar('id_form')]); // remove the last caption that was saved in the array, since there was no data submitted for that element (it was blanked)

		// 5. -- existing entry, with value, was set (assuming $de_metadata[0] is not NEW when $v is set. -- update existing
		} elseif($v == "set" AND isset($_POST[$deprefix . $de_metadata[0] . "_" . $de_metadata[1]]) AND ($update_other_entries OR ($update_own_entry AND $uid = getEntryOwner($de_metadata[0])))) {
			$sql="UPDATE " .$xoopsDB->prefix("formulize_form") . " SET ele_value=\"" . mysql_real_escape_string($value) . "\", proxyid=\"$uid\", date=\"$date\" WHERE ele_caption=\"$realcap\" AND id_req='" . $de_metadata[0] . "'";
			$notEntriesList['update_entry'][$element->getVar('id_form')][] = $de_metadata[0];

		// 6. -- existing entry, with value, was empty -- insert with this $entry value
		} elseif($v == "empty" AND $de_metadata[0] != "new" AND isset($_POST[$deprefix . $de_metadata[0] . "_" . $de_metadata[1]]) AND ($update_other_entries OR ($update_own_entry AND $uid = getEntryOwner($de_metadata[0])))) {
			// need to get the creation date for this entry and the original user id
			$getMeta = "SELECT creation_date, uid FROM " . $xoopsDB->prefix(formulize_form) . " WHERE id_req='" . $de_metadata[0] . "' AND creation_date>0 ORDER BY creation_date DESC LIMIT 0,1";
			$gdres = $xoopsDB->query($getMeta);
			list($create_date, $org_uid) = $xoopsDB->fetchRow($gdres);
			$value = ($value == "{ID}" AND ($element->getVar('ele_type') == "text" OR $element->getVar('ele_type') == "textarea")) ? $de_metadata[0] : $value; // for textboxes or textarea boxes with a special value, sub in the id_req
			$value = ($value == "{SEQUENCE}" AND ($element->getVar('ele_type') == "text" OR $element->getVar('ele_type') == "textarea")) ? formulize_getMaxValue($element->getVar('ele_caption'), $element->getVar('id_form')) : $value; // handle the special {SEQUENCE} term
			$sql="INSERT INTO ".$xoopsDB->prefix("formulize_form")." (id_form, id_req, ele_id, ele_type, ele_caption, ele_value, uid, proxyid, date, creation_date) VALUES (\"" . $element->getVar('id_form') . "\", \"" . $de_metadata[0] . "\", \"\", \"" .$element->getVar('ele_type'). "\", \"$realcap\", \"" . mysql_real_escape_string($value) . "\", \"$org_uid\", \"$uid\", \"$date\", \"$create_date\")";

			$notEntriesList['update_entry'][$element->getVar('id_form')][] = $de_metadata[0];

		}

		if($sql) { // run the query
			//print $sql . "<br>";
			if(!$res = $xoopsDB->query($sql)) {
				exit("Error: unable to update value of displayed element using the following SQL:<br>$sql");
			}
			$GLOBALS['formulize_readElementsWasRun'] = true;
			writeOtherValues($id_reqForWritingOther, $element->getVar('id_form')); // note there is no specific permission check for whether they can update this data in the DB, but there should be no SQL unless they actually have permission (permission checks are made before $sql is created)
		}

		unset($_POST[$k]);  // prevents multiple operations with this data if multiple pageworks blocks are visible -- the fact this file is "include_once" now should actually handle the multiple block situation adequately, but this protection can't hurt to keep in
	}
	unset($element);
}

// unlock tables
$xoopsDB->query("UNLOCK TABLES");

// process notifications
$formulize_REprocessedNotifications = array();
foreach($notEntriesList as $notEvent=>$notDetails) {
	foreach($notDetails as $notFid=>$notIdReqs) {
		$notIdReqs = array_unique($notIdReqs); // this is not foolproof...won't properly handle cases where multiple entries are in effect on the same page/screen
		$serialized_notIdReqs = serialize($notIdReqs);
		if(!isset($formulize_REprocessedNotifications[$notFid][$notEvent][$serialized_notIdReqs])) { // do not send anything we've already sent
			$formulize_REprocessedNotifications[$notFid][$notEvent][$serialized_notIdReqs] = 1;
			sendNotifications($notFid, $notEvent, $notIdReqs);
		}
	}
}


?>