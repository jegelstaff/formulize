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
##  Author of this file: Freeform Solutions 					     ##
##  Project: Formulize                                                       ##
###############################################################################

// THIS FILE CONTAINS FUNCTIONS RELATED TO SUBMITTING DATA TO THE DATABASE THAT IS READ FROM A SUBMITTED FORM

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

// this function handles creating the list of unified onetoone entries
function writeLinks($entries, $fids) {
	global $xoopsDB;
	// NOTE:  assumption is that the relationship between links will not change over time.  Ie: if a form is linked to another form onetoone, unified display, then the entries that are bound together will not become bound to other entries later.  Accordingly, this function checks to see whether an entry has already been written to the list, and if it has, then nothing occurs.
	// information is written from all form's points of view, ie: entry 1, entry 2 AND entry 2, entry 1
	foreach($fids as $fid) {
		foreach($entries[$fid] as $one_entry) {
			foreach($entries as $one_forms_entries) {
				if($one_forms_entries[0] == $one_entry) { continue; } // since we're looping through all the fids twice, concurrently, we could end up with the same value being found
				$check_q = q("SELECT link_form FROM " . $xoopsDB->prefix("formulize_onetoone_links") . " WHERE main_form = $one_entry AND link_form = " . $one_forms_entries[0]);
				if(count($check_q) == 0) { // entry not entered yet
					$write_q = "INSERT INTO " . $xoopsDB->prefix("formulize_onetoone_links") . " (main_form, link_form) VALUES (\"$one_entry\", \"" . $one_forms_entries[0] . "\")";
					if(!$result = $xoopsDB->query($write_q)) {
						exit("error writing linked entries to the database");
					}
				}
			}
		}
	}
}


// function handles reading of data from submitted form
function handleSubmission($formulize_mgr, $entries, $uid, $owner, $fid, $owner_groups, $groups, $profileForm="", $elements_allowed="", $mid="") {

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

// skip the security check if we're in wfdownloads/smartdownloads since that module should already be handling the security checking
$cururl = getCurrentURL();
if (!$GLOBALS['xoopsSecurity']->check() AND (!strstr($cururl, "modules/wfdownloads") AND !strstr($cururl, "modules/smartdownload"))) {
	print "<b>Error: the data you submitted could not be saved in the database.</b>";
	return;
}

global $xoopsDB;
$myts =& MyTextSanitizer::getInstance();


	$i=0;

	$date = date ("Y-m-d");
	unset($_POST['submit']);
	foreach( $_POST as $k => $v ){
		if( preg_match('/ele_/', $k)){
			$n = explode("_", $k);
			$ele[$n[1]] = $v;
			$id[$n[1]] = $n[1];
		}
//upload feature not enabled, does not work, this legacy code may be worked on in future
//Now superseded by integration with WF-Downloads -- jwe April 22, 2006
/*		if($k == 'xoops_upload_file'){ 
			$tmp = $k;
			$k = $v[0];			
			$v = $tmp;
			$n = explode("_", $k);
			$ele[$n[1]] = $v;
			$id[$n[1]] = $n[1];
		}*/
		if(substr($k, 0, 12) == "userprofile_") {
			$up[substr($k, 12)] = $v;
		}



	}

	if(isset($up)) {
		writeUserProfile($up, $uid);
	}

	$desc_form = array();
	$value = null;

	// lock the formulize_form table -- read and write
	// read from formulize, and formulize_id
	$xoopsDB->query("LOCK TABLES " . $xoopsDB->prefix("formulize") . " READ, " . $xoopsDB->prefix("formulize_id") . " READ, " . $xoopsDB->prefix("formulize_form") . " WRITE, " . $xoopsDB->prefix("group_permission") . " READ");    

	// START LOOPING THROUGH ALL THE ELEMENTS THAT WERE RETURNED FROM THE FORM
	foreach( $id as $i ){


	$element =& $formulize_mgr->get($i);

//print "<br>" .$i . ": " . $ele[$i];
//		if( !empty($ele[$i]) ){
		if(is_numeric($ele[$i]) OR $ele[$i] != "") {
//print "<br>" .$i . ": " . $ele[$i];
			//$pds = $element->getVar('pds');
			$id_form = $element->getVar('id_form');

			// do this code block only once per form...setup things necessary for processing later
			if(!$uids[$id_form]) {
				$fids[] = $id_form;
				$num_id = "";
				$proxyid = $uid; // changed from "" to $uid July 13 2005 so that proxy id always indicates the id of the user who last made a change (it is now a real modification user field)
				// handle the num_id for this form (the starting id_req to be used for new entries)
				$sql = $xoopsDB->query("SELECT id_req from " . $xoopsDB->prefix("formulize_form")." order by id_req DESC");
				list($id_req) = $xoopsDB->fetchRow($sql);
				if ($id_req == 0) { $num_id = 1; }
				else if ($num_id <= $id_req) $num_id = $id_req + 1;

				$uids[$id_form][$num_id] = $uid;
				// handle creating user ID lists in case of proxy entries
             		if(isset($_POST['proxyuser']) AND (count($_POST['proxyuser'])>1 OR (count($_POST['proxyuser']) == 1 AND $_POST['proxyuser'][0] != "noproxy")))
             		{
            			$proxyid = $uid; // proxy flag set to user who made entry
             			unset($uids[$id_form]);
             			foreach($_POST['proxyuser'] as $puser) {
             				if($puser != "noproxy") {
             					$uids[$id_form][$num_id] = $puser;
             					$num_id++;
             				}
             			}
	           		}	
             		elseif($entries[$id_form][0] AND (intval($owner) === 0 OR $owner > 0) AND $uid != $owner) // they are an admin who has updated someone's entry (could be simply a fellow member of the same groupscope) -- intval($owner) === 0 handles anons
             		{
             			$proxyid = $uid; // proxy flag set to user who updated entry
             			$uids[$id_form][$num_id] = intval($owner); // uid set to uid of the original entry
	           		}

				// handle the previous entries for this form
//				unset($prevEntry);
				$prevEntry[$id_form] = getEntryValues($entries[$id_form][0], $formulize_mgr, $groups, $id_form, $elements_allowed, $mid, $uid, $owner); 
			}

			$ele_id = $element->getVar('ele_id');
			$ele_type = $element->getVar('ele_type');
			$ele_value = $element->getVar('ele_value');
// don't use getVar method.  Go straight from DB instead, to avoid text sanitizing if the multi-language hack is in effect
//			$ele_caption = $element->getVar('ele_caption');
			$ecq = q("SELECT ele_caption FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id = '$ele_id'");
			$ele_caption = $ecq[0]['ele_caption'];
			$ele_caption = stripslashes($ele_caption);

			$ele_caption = eregi_replace ("&#039;", "`", $ele_caption);
			$ele_caption = eregi_replace ("&quot;", "`", $ele_caption);
			$ele_caption = eregi_replace ("'", "`", $ele_caption);
			$sql = $xoopsDB->query("SELECT desc_form from ".$xoopsDB->prefix("formulize_id")." WHERE id_form= ".$id_form.'');
			while ($row = mysql_fetch_array ($sql)) 
			{	$desc_form[] = $row['desc_form']; }

			$value = prepDataForWrite($element, $ele[$i]);

			if($value == "{SKIPTHISDATE}") { continue; } // move on to next form element if this is a date that we don't need to process

		$submittedcaptions[$id_form][] = $ele_caption;
		writeData($value, $entries[$id_form][0], $uids[$id_form], $prevEntry[$id_form], $id_form, $ele_caption, $proxyid, $date, $ele_type);

		} // end of if there's an element
	} // end of loop through all submitted elements

	// unlock tables
	$xoopsDB->query("UNLOCK TABLES");

	// note: you cannot completely erase an entry by blanking, because at least one element from a form needs to be sent in order for submittedcaptions array to include that form_id
	foreach($submittedcaptions as $f=>$cs) {
		blankEntries($cs, $prevEntry[$f], $entries[$f][0]);
	}

	// need to return comprehensive list of all entries, either the entry passed, or if no entry was passed, then the num_id used -- num_id is the value that gets passed as the id_req for new entries, and it is stored in the uids array here
	foreach($fids as $this_fid) {
		if(!$entries[$this_fid][0]) {
			unset($entries[$this_fid]);
			// convert uids to entries format and return
			foreach($uids[$this_fid] as $r=>$u) {
				$entries[$this_fid][] = $r;
				writeOtherValues($r, $this_fid);
				if(intval($u) === 0) { // if they're an anon user who has created their own new entries, then write the id_reqs as cookies.  Only works if nothing has been output to the page yet!  With xLanguage in use and the entire output of each pageload being buffered, this cookie setting should always work.
					setcookie('entryid_'.$this_fid, $r, time()+60*60*24*7, '/');	// the slash indicates the cookie is available anywhere in the domain (not just the current folder)				
					$_COOKIE['entryid_'.$this_fid] = $r;
				}			
			}
			sendNotifications($this_fid, "new_entry", $entries[$this_fid], $mid, $groups);
		} else {	
			writeOtherValues($entries[$this_fid][0], $this_fid);
			sendNotifications($this_fid, "update_entry", $entries[$this_fid], $mid, $groups);
		}
	} 
	return $entries;
}


// THIS FUNCTION WRITES DATA TO THE DATABASE
function writeData($value, $entry, $uids, $prevEntry, $id_form, $ele_caption, $proxyid, $date, $ele_type) {

//print "Form: $id_form" . ", Entry: $entry<br>"; // debug code

global $xoopsDB;

//print "<br>Value about to write:  $value";

// modified to update existing entries -- jwe 7/24/04

// Process to write over an entry...  (once again, assume captions are unique)
// 1. check to see if the current caption has a record that matches the viewentry (a record that is part of the current submission)
// 2. if the current caption does have a record, extract the ele_id
// 3. if there is a record and we've extracted an ele_id, then update the record with that ele_id, viewentry for id_req, and the info from the form
// 4. if the current caption does not have a record, then we create a new record (same as if viewentry were false, *except* we use the current viewentry for the id_req)

foreach($uids as $num_id=>$uid) { // added to handle multiple select proxy boxes May 3 05

if($entry) {

	// check to see if the caption exists...

	if(in_array($ele_caption, $prevEntry['captions'])) {
		//get the ele_id
		$extractEleid = "SELECT ele_id FROM " . $xoopsDB->prefix("formulize_form") . " WHERE ele_caption=\"$ele_caption\" AND id_req=$entry";
		//print "*extractEleid*". $extractEleid . "*";
		$resultExtractEleid = mysql_query($extractEleid);
		$finalresulteleidex = mysql_fetch_row($resultExtractEleid);
		$ele_id = $finalresulteleidex[0];

		$sql="UPDATE " .$xoopsDB->prefix("formulize_form") . " SET id_form=\"$id_form\", id_req=\"$entry\", ele_id=\"$ele_id\", ele_type=\"$ele_type\", ele_caption=\"$ele_caption\", ele_value=\"" . mysql_real_escape_string($value) . "\", uid=\"$uid\", proxyid=\"$proxyid\", date=\"$date\" WHERE ele_id = $ele_id";

	} else { // or if the caption does not exist (it was blank last time the form was filled in...make a new entry but use the current viewentry for the id_req (to tie this new entry to the other elements that are part of the same record)
		$sql="INSERT INTO ".$xoopsDB->prefix("formulize_form")." (id_form, id_req, ele_id, ele_type, ele_caption, ele_value, uid, proxyid, date) VALUES (\"$id_form\", \"$entry\", \"\", \"$ele_type\", \"$ele_caption\", \"" . mysql_real_escape_string($value) . "\", \"$uid\", \"$proxyid\", \"$date\")";
	}
} else { // not updating an old entry
	$sql="INSERT INTO ".$xoopsDB->prefix("formulize_form")." (id_form, id_req, ele_id, ele_type, ele_caption, ele_value, uid, proxyid, date, creation_date) VALUES (\"$id_form\", \"$num_id\", \"\", \"$ele_type\", \"$ele_caption\", \"" . mysql_real_escape_string($value) . "\", \"$uid\", \"$proxyid\", \"$date\", \"$date\")";
}

if($_GET['debug4']) { print $sql . "<br>"; }
$result = $xoopsDB->query($sql);
    if ($result == false) {
        die('The following SQL statement was rejected by the database: <br>' . $sql . '<br>');
    } 

}// end of the foreach uids for writing to the DB

}

// THIS FUNCTION TAKES THE DATA PASSED BACK FROM THE USERPROFILE PART OF A FORM AND SAVES IT AS PART OF THE XOOPS USER PROFILE
function writeUserProfile($data, $uid) {

	// following code largely borrowed from edituser.php
	// values we receive:
	// name
	// email
	// viewemail
	// timezone_offset
	// password
	// vpass
	// attachsig
	// user_sig
	// umode
	// uorder
	// notify_method
	// notify_mode

	global $xoopsUser, $xoopsConfig;
	$config_handler =& xoops_gethandler('config');
	$xoopsConfigUser =& $config_handler->getConfigsByCat(XOOPS_CONF_USER);

	include_once XOOPS_ROOT_PATH . "/language/" . $xoopsConfig['language'] . "/user.php";

	$errors = array();
    if (!empty($data['uid'])) {
        $uid = intval($data['uid']);
    }
    if (empty($uid)) {
	  redirect_header(XOOPS_URL,3,_US_NOEDITRIGHT);
        exit();
    } elseif(is_object($xoopsUser)) {
		if($xoopsUser->getVar('uid') != $uid) {
		  redirect_header(XOOPS_URL,3,_US_NOEDITRIGHT);
	        exit();	
		}
    }

    $myts =& MyTextSanitizer::getInstance();
    if ($xoopsConfigUser['allow_chgmail'] == 1) {
        $email = '';
        if (!empty($data['email'])) {
            $email = $myts->stripSlashesGPC(trim($data['email']));
        }
        if ($email == '' || !checkEmail($email)) {
            $errors[] = _US_INVALIDMAIL;
        }
    }
    $password = '';
    $vpass = '';
    if (!empty($data['password'])) {
     	  $password = $myts->stripSlashesGPC(trim($data['password']));
    }
    if ($password != '') {
     	  if (strlen($password) < $xoopsConfigUser['minpass']) {
           	$errors[] = sprintf(_US_PWDTOOSHORT,$xoopsConfigUser['minpass']);
        }
        if (!empty($data['vpass'])) { 
     	      $vpass = $myts->stripSlashesGPC(trim($data['vpass']));
        }
     	  if ($password != $vpass) {
            $errors[] = _US_PASSNOTSAME;
     	  }
    }
    if (count($errors) > 0) {
        echo '<div>';
        foreach ($errors as $er) {
            echo '<span style="color: #ff0000; font-weight: bold;">'.$er.'</span><br />';
        }
        echo '</div><br />';
    } else {
        $member_handler =& xoops_gethandler('member');
        $edituser =& $member_handler->getUser($uid);
        $edituser->setVar('name', $data['name']);
        if ($xoopsConfigUser['allow_chgmail'] == 1) {
            $edituser->setVar('email', $email, true);
        }
        $user_viewemail = (!empty($data['user_viewemail'])) ? 1 : 0;
        $edituser->setVar('user_viewemail', $user_viewemail);
        if ($password != '') {
            $edituser->setVar('pass', md5($password), true);
        }
        $edituser->setVar('timezone_offset', $data['timezone_offset']);
        $attachsig = !empty($data['attachsig']) ? 1 : 0;
	  $edituser->setVar('attachsig', $attachsig);
        $edituser->setVar('user_sig', xoops_substr($data['user_sig'], 0, 255));
        $edituser->setVar('uorder', $data['uorder']);
        $edituser->setVar('umode', $data['umode']);
        $edituser->setVar('notify_method', $data['notify_method']);
        $edituser->setVar('notify_mode', $data['notify_mode']);

        if (!$member_handler->insertUser($edituser)) {
            echo $edituser->getHtmlErrors();
		exit();
        }
    }

}

// function to blank entries that the user deleted on submission
function blankEntries($submittedcaptions, $prevEntry, $entry) {

	global $xoopsDB;

	$misscapindex = 0;

	/*print "Submitted captions:<br>";
	print_r($submittedcaptions);
	print "<br><br>";  // debug block

	print "Complete Captions:<br>";
	print_r($prevEntry);
	print "<br><br>";  // debug block
	*/

	foreach($prevEntry['captions'] as $existingCaption2) {
		// print"Exist: $existingCaption2<br>"; // debug code
		if(!in_array($existingCaption2, $submittedcaptions)) {
			$missingcaptions[] = $existingCaption2;
		}
	} 
	/*print "<br>Missing captions:<br>";
	print_r($missingcaptions);
	print "<br>"; // debug block
	*/
	//If there are existing captions that have not been sent for writing, then blank them.
	foreach($missingcaptions as $ele_cap2) {
	
		$extractEleid2 = "SELECT ele_id FROM " . $xoopsDB->prefix("formulize_form") . " WHERE ele_caption=\"$ele_cap2\" AND id_req=$entry";
		$resultExtractEleid2 = mysql_query($extractEleid2);
		$finalresulteleidex2 = mysql_fetch_row($resultExtractEleid2);
		$ele_id2 = $finalresulteleidex2[0];
		$sql="DELETE FROM " .$xoopsDB->prefix("formulize_form") . " WHERE ele_id = $ele_id2";
		//print $sql . "<br>";
		$result = $xoopsDB->query($sql);
		if(!$result) { exit("error blanking entries while using the following SQL statement:<br>$sql"); }
	}
}



?>