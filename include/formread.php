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
function handleSubmission($formulize_mgr, $entries, $uid, $owner, $fid, $owner_groups, $groups) {

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
		if($k == 'xoops_upload_file'){ //upload feature not enabled, does not work, this legacy code may be worked on in future
			$tmp = $k;
			$k = $v[0];			
			$v = $tmp;
			$n = explode("_", $k);
			$ele[$n[1]] = $v;
			$id[$n[1]] = $n[1];
		}
	}

	$up = array();
	$desc_form = array();
	$value = null;

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
				$sql = $xoopsDB->query("SELECT id_req from " . $xoopsDB->prefix("form_form")." order by id_req DESC");
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
             		elseif($entries[$id_form][0] AND $uid != $owner) // they are an admin who has updated someone's entry (could be simply a fellow member of the same groupscope)
             		{
             			$proxyid = $uid; // proxy flag set to user who updated entry
             			$uids[$id_form][$num_id] = $owner; // uid set to uid of the original entry
	           		}

				// handle the previous entries for this form
//				unset($prevEntry);
				$prevEntry[$id_form] = getEntryValues($entries[$id_form][0], $formulize_mgr, $groups, $id_form);
			}

			$ele_id = $element->getVar('ele_id');
			$ele_type = $element->getVar('ele_type');
			$ele_value = $element->getVar('ele_value');
// don't use getVar method.  Go straight from DB instead, to avoid text sanitizing if the multi-language hack is in effect
//			$ele_caption = $element->getVar('ele_caption');
			$ecq = q("SELECT ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE ele_id = '$ele_id'");
			$ele_caption = $ecq[0]['ele_caption'];
			$ele_caption = stripslashes($ele_caption);
			$ele_caption = eregi_replace ("&#039;", "`", $ele_caption);
			$ele_caption = eregi_replace ("&quot;", "`", $ele_caption);
			$ele_caption = eregi_replace ("'", "`", $ele_caption);
			$sql = $xoopsDB->query("SELECT desc_form from ".$xoopsDB->prefix("form_id")." WHERE id_form= ".$id_form.'');
			while ($row = mysql_fetch_array ($sql)) 
			{	$desc_form[] = $row['desc_form']; }
			
			switch($ele_type){
				case 'text':
					if($ele_value[3]) { // if $ele_value[3] is 1 (default is 0) then treat this as a numerical field
						$value = ereg_replace ('[^0-9.]+', '', $ele[$i]);
					} else {
						$value = $ele[$i]; // trim added by jwe 9/01/04 -- removed 10/07/04
					}
				break;
				case 'textarea':
					$value = $ele[$i]; // trim added by jwe 9/01/04 -- removed 10/07/04

				break;
				case 'areamodif':
					$value = $myts->stripSlashesGPC($ele[$i]);
				break;
				case 'radio':
					$value = '';
					$opt_count = 1;
					while( $v = each($ele_value) ){
						if( $opt_count == $ele[$i] ){
							$msg.= $myts->stripSlashesGPC($v['key']).'<br>';
							$value = $v['key'];
						}
						$opt_count++;
					}
				break;
				case 'yn':
					$value = $ele[$i];
				break;
				case 'checkbox':
					$value = '';
					$opt_count = 1;
					while( $v = each($ele_value) ){
						if( is_array($ele[$i]) ){
							if( in_array($opt_count, $ele[$i]) ){
								$value = $value.'*=+*:'.$v['key'];
							}
							$opt_count++;
						}else{
							if( !empty($ele[$i]) ){
								$value = $value.'*=+*:'.$v['key'];
							}
						}						
					}
				break;
				case 'select':
					// section to handle linked select boxes differently from others...
					$formlinktrue = 0;
					if(is_array($ele[$i]))  // look for the formlink delimiter
					{
						foreach($ele[$i] as $justacheck)
						{
							if(strstr($justacheck, "#*=:*"))
							{
								$formlinktrue = 1;
								break;
							}
						}
					}
					else
					{
						if(strstr($ele[$i], "#*=:*"))
						{
							$formlinktrue = 1;
						}
					}
					if($formlinktrue) // if we've got a formlink, then handle it here...
					{
						if(is_array($ele[$i]))
						{
							//print_r($ele[$i]);
							array($compparts);
							$compinit = 0;
							$selinit = 0;
							foreach($ele[$i] as $whatwasselected)
							{
							//	print "<br>$whatwasselected<br>";
								$compparts = explode("#*=:*", $whatwasselected);
							//	print_r($compparts);
								if($compinit == 0)
								{
									$value = $compparts[0] . "#*=:*" . $compparts[1] . "#*=:*";
									$compinit = 1;
								}
								if($selinit == 1)
								{
									$value = $value . "[=*9*:";
								}
								$value = $value . $compparts[2];
								$selinit = 1;
							}
						}
						else
						{
							$value = $ele[$i];
						}	
//						print "<br>VALUE: $value";	
						break;			
					}
					else
					{


					$value = '';


							// The following code block is a replacement for the previous method for reading a select box which didn't work reliably -- jwe 7/26/04
							// print_r($ele_value[2]);
							$nametype = "";
							$temparraykeys = array_keys($ele_value[2]);
							if($temparraykeys[0] == "{FULLNAMES}" OR $temparraykeys[0] == "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s)
								if($temparraykeys[0] == "{FULLNAMES}") { $nametype = "name"; }
								if($temparraykeys[0] == "{USERNAMES}") { $nametype = "uname"; }
								unset($ele_value[2]);
								if(count($owner_groups)>0) {
									$ele_value[2] = gatherNames($owner_groups, $nametype);
								} else {
									$ele_value[2] = gatherNames($groups, $nametype);
								}
							}

							$entriesPassedBack = array_keys($ele_value[2]);
							$keysPassedBack = array_keys($entriesPassedBack);
							$entrycounterjwe = 0;
							foreach($keysPassedBack as $masterentlistjwe)
							{
	      						if(is_array($ele[$i]))

								{
									foreach($ele[$i] as $whattheuserselected)
									{
										// if the user selected an entry found in the master list of all possible entries...
										//print "internal loop $entrycounterjwe<br>userselected: $whattheuserselected<br>selectbox contained: $masterentlistjwe<br><br>";	
										if($whattheuserselected == $masterentlistjwe)
										{
											//print "WE HAVE A MATCH!<BR>";
											if($nametype) { 
												$value .= "*=+*:" . $ele_value[2][$entriesPassedBack[$entrycounterjwe]]; 
											} else {
												$value = $value . "*=+*:" . $entriesPassedBack[$entrycounterjwe];
											}
											//print "$value<br><br>";
										}
									}
									$entrycounterjwe++;
								}
								else
								{
									//print "internal loop $entrycounterjwe<br>userselected: $ele[$i]<br>selectbox contained: $masterentlistjwe<br><br>";	
									if($ele[$i] == ($masterentlistjwe+1)) // plus 1 because single entry select boxes start their option lists at 1.
									{
										//print "WE HAVE A MATCH!<BR>";
										if($nametype) { 
											$value = $ele_value[2][$entriesPassedBack[$entrycounterjwe]]; 
										} else {
											$value = $entriesPassedBack[$entrycounterjwe];
										}
										//print "$value<br><br>";
										break;
									}
									$entrycounterjwe++;
								}
							}
					// print "selects: $value<br>";
				break;
				} // end of if that checks for a linked select box.
				case 'areamodif':
					$value = $ele[$i];
				break;
				case 'date':
					// code below commented/added by jwe 10/23/04 to convert dates into the proper standard format
					if($ele[$i] != "YYYY-mm-dd" AND $ele[$i] != "") { 
						$ele[$i] = date("Y-m-d", strtotime($ele[$i])); 
					} else {
						continue 2; // forget about this date element and go on to the next element in the form
					}
					$value = ''.$ele[$i];
				break;
				case 'sep':
					$value = $myts->stripSlashesGPC($ele[$i]);
				break;
				default:
				break;
			}

		$submittedcaptions[$id_form][] = $ele_caption;
		writeData($value, $entries[$id_form][0], $uids[$id_form], $prevEntry[$id_form], $id_form, $ele_caption, $proxyid, $date, $ele_type);

		} // end of if there's an element
	} // end of loop through all submitted elements

	foreach($submittedcaptions as $f=>$cs) {
		blankEntries($cs, $prevEntry[$f], $entries[$f][0]);
	}

	// need to return comprehensive list of all entries, either the entry passed, or if no entry was passed, then the num_id used
	foreach($fids as $this_fid) {
		if(!$entries[$this_fid][0]) {
			unset($entries[$this_fid]);
			// convert uids to entries format and return
			foreach($uids[$this_fid] as $r=>$u) {
				$entries[$this_fid][] = $r;
			}
		}	
	} 
	return $entries;
}

// THIS FUNCTION WRITES DATA TO THE DATABASE
function writeData($value, $entry, $uids, $prevEntry, $id_form, $ele_caption, $proxyid, $date, $ele_type) {

//print "Form: $id_form" . ", Entry: $entry<br>"; // debug code

global $xoopsDB;

$value = addslashes ($value);

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
		$extractEleid = "SELECT ele_id FROM " . $xoopsDB->prefix("form_form") . " WHERE ele_caption=\"$ele_caption\" AND id_req=$entry";
		//print "*extractEleid*". $extractEleid . "*";
		$resultExtractEleid = mysql_query($extractEleid);
		$finalresulteleidex = mysql_fetch_row($resultExtractEleid);
		$ele_id = $finalresulteleidex[0];

		$sql="UPDATE " .$xoopsDB->prefix("form_form") . " SET id_form=\"$id_form\", id_req=\"$entry\", ele_id=\"$ele_id\", ele_type=\"$ele_type\", ele_caption=\"$ele_caption\", ele_value=\"$value\", uid=\"$uid\", proxyid=\"$proxyid\", date=\"$date\" WHERE ele_id = $ele_id";
		
	} else { // or if the caption does not exist (it was blank last time the form was filled in...make a new entry but use the current viewentry for the id_req (to tie this new entry to the other elements that are part of the same record)
		$sql="INSERT INTO ".$xoopsDB->prefix("form_form")." (id_form, id_req, ele_id, ele_type, ele_caption, ele_value, uid, proxyid, date) VALUES (\"$id_form\", \"$entry\", \"\", \"$ele_type\", \"$ele_caption\", \"$value\", \"$uid\", \"$proxyid\", \"$date\")";
	}
} else { // not updating an old entry
	$sql="INSERT INTO ".$xoopsDB->prefix("form_form")." (id_form, id_req, ele_id, ele_type, ele_caption, ele_value, uid, proxyid, date, creation_date) VALUES (\"$id_form\", \"$num_id\", \"\", \"$ele_type\", \"$ele_caption\", \"$value\", \"$uid\", \"$proxyid\", \"$date\", \"$date\")";
}

//print $sql . "<br>";
$result = $xoopsDB->query($sql);
    if ($result == false) {
        die('The following SQL statement was rejected by the database: <br>' . $sql . '<br>');
    } 

}// end of the foreach uids for writing to the DB

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
	
		$extractEleid2 = "SELECT ele_id FROM " . $xoopsDB->prefix("form_form") . " WHERE ele_caption=\"$ele_cap2\" AND id_req=$entry";
		$resultExtractEleid2 = mysql_query($extractEleid2);
		$finalresulteleidex2 = mysql_fetch_row($resultExtractEleid2);
		$ele_id2 = $finalresulteleidex2[0];
		$sql="DELETE FROM " .$xoopsDB->prefix("form_form") . " WHERE ele_id = $ele_id2";
		//print $sql . "<br>";
		$result = $xoopsDB->query($sql);
		if(!$result) { exit("error blanking entries while using the following SQL statement:<br>$sql"); }
	}
}



?>