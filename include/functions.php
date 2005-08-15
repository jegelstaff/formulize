<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2005 Freeform Solutions                  ##
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

// identify form or framework
function getFormFramework($formframe, $mainform="") {
	global $xoopsDB;
	if(!empty($mainform)) { // a framework
		if(!is_numeric($formframe)) {
			$frameid = q("SELECT frame_id FROM " . $xoopsDB->prefix("formulize_frameworks") . " WHERE frame_name='$formframe'");
			$frid = $frameid[0]['frame_id'];
		} else {
			$frid = $formframe;
		}
		if(!is_numeric($mainform)) {
			$formcheck = q("SELECT ff_form_id FROM " . $xoopsDB->prefix("formulize_framework_forms") . " WHERE ff_frame_id='$frid' AND ff_handle='$mainform'");
			$fid = $formcheck[0]['ff_form_id'];
		} else {
			$fid = $mainform;
		}
		if (!$fid) { 
			print "Form Name: " . $form . "<br>";
			print "Form id: " . $fid . "<br>";
			print "Frame Name: " . $frame . "<br>";
			print "Frame id: " . $frid . "<br>";
			exit("selected form does not exist in framework"); 
		}
	} else { // a form
		$frid = "";
		if(!is_numeric($formframe)) { // if it's a title, convert to the id
			$formid = q("SELECT id_form FROM " . $xoopsDB->prefix("form_id") . " WHERE desc_form = '$formframe'");
			$fid = $formid[0]['id_form'];
		} else {
			$fid = $formframe;
		}
	}
	$to_return[0] = $fid;
	$to_return[1] = $frid;
	return $to_return;
}

// get the title of a form
function getFormTitle($fid) {
	global $xoopsDB;
	$titleq = q("SELECT desc_form FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form ='$fid'");
	return $titleq[0]['desc_form'];

}

//this function returns the list of all the user's full names for all the users in the current user's group(s)
function gatherNames($groups, $nametype) {
	global $xoopsDB;
	$member_handler =& xoops_gethandler('member');
	foreach($groups as $group) {
		if($group == XOOPS_GROUP_USERS) { continue; }
		$users =& $member_handler->getUsersByGroup($group);
		$all_users = array_merge($all_users, $users);
		unset($users);
	}
	array_unique($all_users);
	$filter = makeUidFilter($all_users);
	$names = q("SELECT $nametype FROM " . $xoopsDB->prefix("users") . " WHERE $filter");
	foreach($names as $name) {
		$found_names[] = $name[$nametype];
	}
	sort($found_names);
	return($found_names);
}

//get the currentURL
function getCurrentURL() {
	$url_parts = parse_url(XOOPS_URL);
	return $url_parts['scheme'] . "://" . $url_parts['host'] . $_SERVER['REQUEST_URI']; 
}

// this function returns a human readable, comma separated list of group names, given a string of comma separated group ids
function groupNameList($list) {
	global $xoopsDB;
	$grouplist = explode(",", $list);
	$start = 1;
	foreach($grouplist as $gid) {
		$groupnames = q("SELECT name FROM " . $xoopsDB->prefix("groups") . " WHERE groupid='$gid'");
		if($start) {
			$names = $groupnames[0]['name'];
			$start = 0;
		} else {
			$names .= ", " . $groupnames[0]['name'];
		}
	}
	return $names;
}

// return an array of the reports the user is allowed to see
function availReports($uid, $groups, $fid, $frid="0") {
	global $xoopsDB;

	// get old saved reports
      $s_reports = q("SELECT report_id, report_name FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_id_form='$fid' AND report_uid='$uid'");


	// get old published reports
	$published_reports = q("SELECT report_id, report_name, report_groupids, report_uid FROM " . $xoopsDB->prefix("form_reports") . " WHERE report_id_form='$fid' AND report_ispublished > 0");


	// cull published reports to ones that are published to a group that the user belongs to
	$indexer = 0;
	for($i=0;$i<count($published_reports);$i++) {
		$report_groups = explode("&*=%4#", $published_reports[$i]['report_groupids']);
		if(array_intersect($groups, $report_groups)) {
			$p_reports[$indexer]['report_id'] = $published_reports[$i]['report_id'];
			$p_reports[$indexer]['report_name'] = $published_reports[$i]['report_name'];
			$p_reports[$indexer]['report_uid'] = $published_reports[$i]['report_uid'];
			$indexer++;
		}
	}

	//--------------repeat for saved views (new system): ------------------------

	// get new saved reports
	if($frid) {
	      $ns_reports = q("SELECT sv_id, sv_name FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_formframe='$frid' AND sv_mainform='$fid' AND sv_owner_uid='$uid'");
	} else {
	      $ns_reports = q("SELECT sv_id, sv_name FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_formframe='$fid' AND sv_owner_uid='$uid'");
	}

	// get new published reports
	if($frid) {
		$npublished_reports = q("SELECT sv_id, sv_name, sv_pubgroups, sv_owner_uid FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_formframe='$frid' AND sv_mainform='$fid' AND sv_pubgroups != \"\"");
	} else {
		$npublished_reports = q("SELECT sv_id, sv_name, sv_pubgroups, sv_owner_uid FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_formframe='$fid' AND sv_pubgroups != \"\"");
	}

	// cull published reports to ones that are published to a group that the user belongs to
	$indexer = 0;
	for($i=0;$i<count($npublished_reports);$i++) {
		$nreport_groups = explode(",", $npublished_reports[$i]['sv_pubgroups']);
		if(array_intersect($groups, $nreport_groups)) {
			$np_reports[$indexer]['sv_id'] = $npublished_reports[$i]['sv_id'];
			$np_reports[$indexer]['sv_name'] = $npublished_reports[$i]['sv_name'];
			$np_reports[$indexer]['sv_uid'] = $npublished_reports[$i]['sv_owner_uid'];
			$indexer++;
		}
	}

	// parse out details from arrays for passing back....

	foreach($s_reports as $id=>$details) {
		$sortnames[] = $details['report_name'];
	}
	array_multisort($sortnames, $s_reports);
	unset($sortnames);
	foreach($p_reports as $id=>$details) {
		$sortnames[] = $details['report_name'];
	}
	array_multisort($sortnames, $p_reports);
	unset($sortnames);
	foreach($ns_reports as $id=>$details) {
		$sortnames[] = $details['report_name'];
	}
	array_multisort($sortnames, $ns_reports);
	unset($sortnames);
	foreach($np_reports as $id=>$details) {
		$sortnames[] = $details['report_name'];
	}
	array_multisort($sortnames, $np_reports);

      $to_return[0] = $s_reports;
	$to_return[1] = $p_reports;
	$to_return[2] = $ns_reports;
	$to_return[3] = $np_reports;

	return $to_return;
}

// security check to see if a form is allowed for the user:
function security_check($fid, $entry, $uid, $owner, $groups, $mid, $gperm_handler, $owner_groups) {

	// need to also allow viewing of forms if the user has rights because of a report
	if(!$gperm_handler->checkRight("view_form", $fid, $groups, $mid)) {
		return false;
	}

	// do security check on entry in form -- note: based on the initial entry passed, does not consider entries in one-to-one linked forms which are assumed to be allowed for the user if the main entry is.
	// allow user to see own entry
	// any entry if they have view_globalscope
	// other users in same group if they have view_groupscope
	// --report overrides need to be added in here for display of entries in reports

	if($entry) {
		$view_globalscope = $gperm_handler->checkRight("view_globalscope", $fid, $groups, $mid);
		if(!$view_globalscope) {
			if($owner != $uid) {
				$view_groupscope = $gperm_handler->checkRight("view_groupscope", $fid, $groups, $mid);
				$intersect_groups = array_intersect($owner_groups, $groups);
				if(!$view_groupscope OR (count($intersect_groups) == 1 AND $intersect_groups[0] == XOOPS_GROUP_USERS)) {
					return false;					
				}
			}
		}
	}
	return true;
}


// GET THE MODULE ID -- specifically get formulize, since if called from within a block, the xoopsModule module ID will not be formulize's id
function getFormulizeModId() {
	global $xoopsDB;
	$res4 = $xoopsDB->query("SELECT mid FROM ".$xoopsDB->prefix("modules")." WHERE dirname='formulize'");
	if ($res4) {
		while ($row = mysql_fetch_row($res4))
			$mid = $row[0];
	}
	return $mid;
}
// RETURNS THE RESULTS OF AN SQL STATEMENT -- ADDED April 25/05
// returns a multidimensioned array where the first index is the row of the result and the second index is the field name in that row
// borrowed from the extraction layer, but modified to use the XOOPS DB class
function q($query) {

	global $xoopsDB;

	//print "$query"; // debug code
	$res = $xoopsDB->query($query);
	while ($array = $xoopsDB->fetchArray($res)) {
		$result[] = $array;
	}
	return $result;
}



// THIS FUNCTION RETURNS AN ARRAY OF THE CATEGORY NAMES WHERE THE CATEGORY IDS ARE THE KEYS -- added April 25/05
function fetchCats() { 

	global $xoopsDB;

	$result = q("SELECT cat_id, cat_name FROM " . $xoopsDB->prefix("formulize_menu_cats") . " ORDER BY cat_name");
	foreach($result as $acat) {
		$cats[$acat['cat_id']] = $acat['cat_name'];
	}

	return $cats;

}

// THIS FUNCTION RETURNS THE CAT_ID OF THE CATEGORY WHERE A FORM IS FOUND (OR 0 IF THE FORM IS NOT FOUND)
function getMenuCat($fid) {
	global $xoopsDB;
	$foundCat = q("SELECT cat_id FROM " . $xoopsDB->prefix("formulize_menu_cats") . " WHERE id_form_array LIKE \"%,$fid,%\"");
	if(count($foundCat)>0) {
		return($foundCat[0]['cat_id']);
	} else {
		return 0;
	}
}

// this function truncates a string to a certain number of characters
function printSmart($value, $chars="35") {
	if(!is_numeric($value) AND $value == "") {
		$ret = "&nbsp;";
	} else {
		$temp = substr($value, 0, $chars);
		if(strlen($value)>$chars) { $temp .= "...."; }
		$ret = $temp;
	}
	return $ret;
}


// this function returns the headerlist for a form and gracefully degrades to other inputs if the headerlist itself is not specified.
function getHeaderList ($fid) {
	
	global $xoopsDB;

	$headerlist = array();

	$hlq = "SELECT headerlist FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form='$fid'";
	if($result = $xoopsDB->query($hlq)) {
		while ($row = $xoopsDB->fetchRow($result)) {
			$headerlist = explode("*=+*:", $row[0]); 
			array_shift($headerlist);
		}
	}

	if(count($headerlist)==0) { // if no header fields specified, then....
		// GATHER REQUIRED FIELDS FOR THIS FORM...
		$reqfq = "SELECT ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE ele_req=1 AND id_form='$fid'";
		if($result = $xoopsDB->query($reqfq)) {
			while ($row = $xoopsDB->fetchArray($result)) {
				$headerlist[] = $row["ele_caption"];
			}
		}
	} else { // IF there are no required fields THEN ... go with first field 
		$firstfq = "SELECT ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE id_form='$fid' ORDER BY ele_order ASC";
		if($result = $xoopsDB->query($firstfq)) {
			while ($row = $xoopsDB->fetchArray($resultfq)) {
				$headerlist[] = $row["ele_caption"];
			}
		}
	}
	return $headerlist;
} 

// THIS FUNCTION RETURNS AN ARRAY OF THE ALLOWED CATEGORIES, KEY BEING ID AND VALUE BEING NAME, BASED ON THE ALLOWEDFORMS ARRAY
function allowedCats($cats, $allowedForms) {
	global $xoopsDB;
	foreach($cats as $catid=>$catname) {
		$flatFormArray = q("SELECT id_form_array FROM " . $xoopsDB->prefix("formulize_menu_cats") . " WHERE cat_id='$catid'");
		$formsInCat = explode(",", trim($flatFormArray[0]['id_form_array'], ","));
		if(array_intersect($formsInCat, $allowedForms)) {
			$allowedCats[$catid] = $catname;
		}  		
	}
	return $allowedCats;
}

// THIS FUNCTION RETURNS THE FORMS THE USER IS ALLOWED TO VIEW
function allowedForms() {

	global $xoopsUser, $xoopsDB;

	// GET THE MODULE ID
	$res4 = $xoopsDB->query("SELECT mid FROM ".$xoopsDB->prefix("modules")." WHERE dirname='formulize'");
	if ($res4) {
		while ($row = mysql_fetch_row($res4))
			$module_id = $row[0];
	}

	// GET THE FORMS THE USER IS ALLOWED TO VIEW
	$groups = $xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;
	$gperm_handler = &xoops_gethandler('groupperm');
	$allowedForms = $gperm_handler->getItemIds("view_form", $groups, $module_id);

	return $allowedForms;

}

// THIS FUNCTION RETURNS THE NAME OF A FORM WHEN GIVEN THE ID (internal, not meant for public use)
function fetchFormName($id) {
	global $xoopsDB;
	$title_q = q("SELECT desc_form FROM " . $xoopsDB->prefix("form_id") . " WHERE id_form='$id'");
	return $title_q[0]['desc_form'];
}

// THIS FUNCTION RETURNS THE NAMES OF A FORM OR FORMS WHEN GIVEN AN id OR ARRAY OF ids
function fetchFormNames($ids) {

	if(is_array($ids)) {
		foreach($ids as $id) {
			$names[] = fetchFormName($id);
		}
		return $names;
	} else {
		$name = fetchFormName($ids);
		return $name;
	}

}

// THIS FUNCTION RETURNS THE FORMS IN A CATEGORY, IF GIVEN THE CATEGORY ID and the allowedforms for the user
function fetchFormsInCat($thisid, $allowedForms="") {

	global $xoopsDB;

		if(!is_array($allowedForms)) { $allowedForms = allowedForms(); }

		if($thisid == 0 ) { // GENERAL CAT...
			// 1. foreach allowed form, check to see if it's in a cat
			// 2. record each one in an array
			// 3. make formsInCat equal the difference between found array and allowed forms
			foreach($allowedForms as $thisform) {
				$found_q = q("SELECT * FROM " . $xoopsDB->prefix("formulize_menu_cats") . " WHERE id_form_array LIKE \"%,$thisform,%\"");
				if(count($found_q)>0) { $foundForms[] = $thisform; }
			}
			if(count($foundForms) > 0 ) {
				$formsInCat1 = array_diff($allowedForms, $foundForms);
			} else {
				$formsInCat1 = $allowedForms;
			}
		} else {
			$flatFormArray = q("SELECT id_form_array FROM " . $xoopsDB->prefix("formulize_menu_cats") . " WHERE cat_id='$thisid'");
			$formsInCat1 = explode(",", trim($flatFormArray[0]['id_form_array'], ","));  		
		}

		// exclude inactive forms, and sort
		foreach($formsInCat1 as $thisform) {
			$status_q = q("SELECT menuid, position FROM " . $xoopsDB->prefix("form_menu") . " WHERE menuid='$thisform' AND status=1");
			if(count($status_q)>0 AND in_array($thisform, $allowedForms)) { // only include active forms that the user is allowed to see
				$formpos[] = $status_q[0]['position']; 
				$formsInCat[] = $thisform; 
			}			
		}
		array_multisort($formpos, $formsInCat);				

	return $formsInCat;
}

// THIS FUNCTION DRAWS IN THE ELEMENTS OF THE FORM MENU
function drawMenu($thisid, $thiscat, $allowedForms, $id_form, $topwritten, $force_open) {

		global $xoopsDB;

		$formsInCat = fetchFormsInCat($thisid, $allowedForms);

		if(count($formsInCat)>0) { // user is allowed to see at least one form in this category
			$itemurl = XOOPS_URL."/modules/formulize/cat.php?cat=$thisid";
			if ($topwritten != 1) {
				$block .= "<a class=menuTop href=\"$itemurl\">$thiscat</a>";
				$topwritten = 1;
			} else {
                		$block .= "<a class=menuMain href=\"$itemurl\">$thiscat</a>";
			}

			// check to see if current cat is active (ie: has been clicked)
			if($force_open OR (isset($_GET['cat']) AND $thisid == $_GET['cat']) OR in_array($id_form, $formsInCat)) { // if we're viewing this category or a form in this category, or this is the only category (force open)...

				foreach($formsInCat as $thisform) {
					$title = fetchFormNames($thisform);
					$urltitle = str_replace(" ", "%20", $title);
					$suburl = XOOPS_URL."/modules/formulize/index.php?title=$urltitle";
					$block .= "<a class=menuSub href='$suburl'>$title</a>";
				}
			}
		}
		return $block;
}

// THIS FUNCTION DELETES ENTRIES FROM FORM_FORM WHEN PASSED AND ID_REQ
function deleteEntry($id_req) {
	global $xoopsDB;
	$sql = "DELETE FROM " . $xoopsDB->prefix("form_form") . " WHERE id_req='$id_req'";
	//print $sql . "<br>";
	if(!$result = $xoopsDB->query($sql)) {
		exit("Error: failed to delete entry $id_req");
	}
}

// GETS THE ID OF THE USER WHO OWNS AN ENTRY
function getEntryOwner($entry) {
	global $xoopsDB;
	$owner = q("SELECT uid FROM " . $xoopsDB->prefix("form_form") . " WHERE id_req = '$entry' LIMIT 0,1");
	return $owner[0]['uid'];
}

// THIS FUNCTION MAKES A UID= or UID= FILTER FOR AN sql QUERY
function makeUidFilter($users) {
	$start = 1;
	foreach($users as $user) {
		if($start) {
			$uq = "uid=$user";
			$start = 0;
		} else {
			$uq .= " OR uid=$user";
		}			
	}
	return $uq;
}

// function handles checking for all unified display - linking relationships for the form
function checkForLinks($frid, $fids, $fid, $entries, $gperm_handler, $owner_groups, $mid, $member_handler, $owner, $ud="1") {

		// by default (ie: when called from formDisplay) only look for unified display relationships
		// when $ud is specifically set to zero, ie: when called from displayEntries, look for any relationships in the framework
		if($ud) {
			$unified_display = "AND fl_unified_display = 1";
		} else {
			$unified_display = "";
		}

		global $xoopsDB;
		// get one-to-one links
		// note: we do not believe that the keys will be relevant to working out details for one-to-one forms, but we are leaving code as is for now
		$one_q1 = q("SELECT fl_form1_id, fl_key1, fl_key2 FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form2_id = $fid AND fl_relationship = 1 AND fl_frame_id = $frid $unified_display");
		$one_q2 = q("SELECT fl_form2_id, fl_key1, fl_key2 FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form1_id = $fid AND fl_relationship = 1 AND fl_frame_id = $frid $unified_display");
		$indexer=0;
		foreach($one_q1 as $res1) {
			$one_to_one[$indexer]['fid'] = $res1['fl_form1_id'];
			$one_to_one[$indexer]['keyself'] = $res1['fl_key1'];
			$one_to_one[$indexer]['keyother'] = $res1['fl_key2'];
			$indexer++;
		}
		foreach($one_q2 as $res2) {
			$one_to_one[$indexer]['fid'] = $res2['fl_form2_id'];
			$one_to_one[$indexer]['keyother'] = $res2['fl_key1'];
			$one_to_one[$indexer]['keyself'] = $res2['fl_key2'];
			$indexer++;
		}
		
		$indexer=0;
		// get one-to-many links
		$many_q1 = q("SELECT fl_form1_id, fl_key1, fl_key2 FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form2_id = $fid AND fl_relationship = 3 AND fl_frame_id = $frid $unified_display");
		$many_q2 = q("SELECT fl_form2_id, fl_key1, fl_key2 FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form1_id = $fid AND fl_relationship = 2 AND fl_frame_id = $frid $unified_display");

		foreach($many_q1 as $res1) {
			$one_to_many[$indexer]['fid'] = $res1['fl_form1_id'];
			$one_to_many[$indexer]['keyself'] = $res1['fl_key1'];
			$one_to_many[$indexer]['keyother'] = $res1['fl_key2'];
			$indexer++;
		}
		foreach($many_q2 as $res2) {
			$one_to_many[$indexer]['fid'] = $res2['fl_form2_id'];
			$one_to_many[$indexer]['keyother'] = $res2['fl_key1'];
			$one_to_many[$indexer]['keyself'] = $res2['fl_key2'];
			$indexer++;
		}
		$one_to_many = array_unique($one_to_many);

		// get MANY-TO-ONE links
		$many_q3 = q("SELECT fl_form1_id, fl_key1, fl_key2 FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form2_id = $fid AND fl_relationship = 2 AND fl_frame_id = $frid $unified_display");
		$many_q4 = q("SELECT fl_form2_id, fl_key1, fl_key2 FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form1_id = $fid AND fl_relationship = 3 AND fl_frame_id = $frid $unified_display");

		foreach($many_q3 as $res1) {
			$many_to_one[$indexer]['fid'] = $res1['fl_form1_id'];
			$many_to_one[$indexer]['keyself'] = $res1['fl_key1'];
			$many_to_one[$indexer]['keyother'] = $res1['fl_key2'];
			$indexer++;
		}
		foreach($many_q4 as $res2) {
			$many_to_one[$indexer]['fid'] = $res2['fl_form2_id'];
			$many_to_one[$indexer]['keyother'] = $res2['fl_key1'];
			$many_to_one[$indexer]['keyself'] = $res2['fl_key2'];
			$indexer++;
		}
		$many_to_one = array_unique($many_to_one);


		// STRONG ASSUMPTION IS THAT ONLY ONE KIND OF LINKING IS GOING TO BE FOUND!  IF A FORM IS LINKED IN MULTIPLE KINDS OF RELATIONSHIPS TO MULTIPLE OTHER FORMS, STRANGE THINGS COULD START HAPPENING...
		// Code and output arrays should be consistent, so it's not that results of this function will be inaccurate, but other code elsewhere may in fact have difficulty handling the results since not all eventualities may yet be accounted for in the logic

		// add to entries and fids array if one_to_one exists
		foreach($one_to_one as $one_fid) {
			$fids[] = $one_fid['fid'];
			if($entries[$fid][0]) {
				$findLinks_q = q("SELECT link_form FROM " . $xoopsDB->prefix("formulize_onetoone_links") . " WHERE main_form = " . $entries[$fid][0]);
				foreach($findLinks_q as $foundLink) {
					$entries[$one_fid['fid']][] = $foundLink['link_form'];
				}
			} else {
				$entries[$one_fid['fid']][] = "";
			}
		}
		foreach($one_to_many as $many_fid) {
			$sub_fids[] = $many_fid['fid'];
			if($entries[$fid][0]) {
				$entries_found = findLinkedEntries($fid, $many_fid, $entries[$fid][0], $gperm_handler, $owner_groups, $mid, $member_handler, $owner);
				foreach($entries_found as $many_entry) {
					$sub_entries[$many_fid['fid']][] = $many_entry;
				}
			} else {
				$sub_entries[$many_fid['fid']][] = "";
			}
		}

      	if(!$ud) {
      		$start = 1;
      		foreach($many_to_one as $many_fid) {
      			if($start) {
      				$sub_fids = $fids;
      				unset($fids);
      				$start = 0;
      			}
      			$fids[] = $many_fid['fid'];
      			// NOTE: no entries returned here.  assumption is that this is only used by displayEntries to handle presenting many_to_one relationship on that screen, and will never in fact be used by displayForm to put unified many_to_one relationships up for input (see the note in findLinkedEntries regarding the "mis-specified" relationship and what would have to happen in the UI to make this work over there)
				// NOTE: explicit exclusion of this code unless ud is 0, ie: unless called from displayEntries
      		}
		}

	$to_return['fids'] = $fids;
	$to_return['entries'] = $entries;
	$to_return['sub_fids'] = $sub_fids;
	$to_return['sub_entries'] = $sub_entries;

	return $to_return;

		// NOTE:  IT IS POSSIBLE FOR A FORM TO BE LINKED TO OTHER FORMS IN ONE TO ONE AND ONE TO MANY RELATIONSHIPS WITHIN THE SAME FRAMEWORK.  THIS CODE WILL RETURN ALL RELATIONSHIPS FOUND IN THE GIVEN FRAMEWORK FOR THAT FORM.  BE CAREFUL OF STRANGE RESULTS!
	
}


//THIS FUNCTION TAKES AN ARRAY AND DELETES ENTRIES IN A FORM
//based on assumption that id_req is unique.
function deleteFormEntries($array) {
	global $xoopsDB;
	$start=1;
	foreach($array as $id) {
		if($start) {
			$filter = "id_req = '$id'";
			$start = 0;
		} else {
			$filter .= " OR id_req = '$id'";
		}
	}
	$deleteq = "DELETE FROM " . $xoopsDB->prefix("form_form") . " WHERE ($filter)";
	if(!$res = $xoopsDB->query($deleteq)) {
		exit("Error deleting entries from the database with this statement:<br>$deleteq");
	}
}

// THIS FUNCTION CREATES AN EXPORT FILE ON THE SERVER AND RETURNS THE FILESNAME
function prepExport($headers, $cols, $data, $fdchoice, $custdel="", $title) {

	global $xoopsDB;
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";

	if($fdchoice == "comma") 
	{ 
		$fd = ",";
		$fxt = ".csv";
	}
	if($fdchoice == "tab")
	{
		$fd = "\t";
		$fxt = ".tabDelimited";
	}
	if($fdchoice == "custom")
	{
		$fd = $custdel;
		if(!$fd) { $fd = "**"; }
		$fxt = ".customDelimited";
	}
	$csvfile =  "Created by" . $fd . "Creation date" . $fd . "Modified by" . $fd . "Modification date";
	foreach($headers as $header)
	{
		$header = str_replace("\"", "\"\"", $header);
		$header = "\"" . $header . "\"";

		$csvfile .= $fd . $header;
	}
	
	$csvfile .= "\r\n";

	$colcounter = 0;
	$i=0;
	foreach($data as $entry) {

		$c_uid = display($entry, 'uid');
		$c_name_q = q("SELECT name, uname FROM " . $xoopsDB->prefix("users") . " WHERE uid='$c_uid'");
		$c_name = $c_name_q[0]['name'];
		if(!$c_name) { $c_name = $c_name_q[0]['uname']; }
		$c_date = display($entry, 'creation_date');
		$m_uid = display($entry, 'proxyid');
		if($m_uid) {
			$m_name_q = q("SELECT name, uname FROM " . $xoopsDB->prefix("users") . " WHERE uid='$m_uid'");
			$m_name = $m_name_q[0]['name'];
			if(!$m_name) { $m_name = $m_name_q[0]['uname']; }
		} else {
			$m_name = $c_name;
		}
		$m_date = display($entry, 'mod_date');

		// write in metadata
		$csvfile .= "\"$c_name\"" . $fd . "\"$c_date\"" . $fd . "\"$m_name\"" . $fd . "\"$m_date\"";
		
		// write in data
		foreach($cols as $col) {
			$data_to_write = displayTogether($entry, $col, "\n");
			$data_to_write = str_replace("\"", "\"\"", $data_to_write);
			$data_to_write = "\"" . $data_to_write . "\"";
			$csvfile .= $fd . $data_to_write;
		}
		$csvfile .= "\r\n"; // end of a line
	}
	$tempfold = time();
	$exfilename = _formulize_DE_XF . $tempfold . $fxt;
	// open the output file for writing
	$wpath = XOOPS_ROOT_PATH."/modules/formulize/export/$exfilename";
	//print $wpath;
	$exportfile = fopen($wpath, "w");
	fwrite ($exportfile, $csvfile);
	fclose ($exportfile);
	
	// need to add in logic to cull old files...

	return XOOPS_URL . "/modules/formulize/export/$exfilename";

}

// this function returns the data to summarize the details about the entry you are looking at
function getMetaData($entry, $member_handler) {
	global $xoopsDB;
	$meta = q("SELECT date, creation_date, uid, proxyid FROM " . $xoopsDB->prefix("form_form") . " WHERE id_req = $entry GROUP BY id_req");
	$meta_to_return['last_update'] = $meta[0]['date'];
	$meta_to_return['created'] = $meta[0]['creation_date'];
	$proxy = $member_handler->getUser($meta[0]['proxyid']);
	if($proxy) {
		if(!$proxy_name = $proxy->getVar('name')) { $proxy_name = $proxy->getVar('uname'); }
		$meta_to_return['last_update_by'] = $proxy_name;
	} else {
		$meta_to_return['last_update_by'] = _FORM_ANON_USER;
	}
	$user = $member_handler->getUser($meta[0]['uid']);
	if($user) {
		if(!$create_name = $user->getVar('name')) { $create_name = $user->getVar('uname'); }
		$meta_to_return['created_by'] = $create_name;
	} else {
		$meta_to_return['created_by'] = _FORM_ANON_USER;
	}
	return $meta_to_return;
}

// this function returns the complete set of columns that are in a form or framework
// the returned array contains one DB query result for each form
// ie:  $cols[form1] = all columns in that form, $cols[form2] = all columns in that form
// columns are the raw results from a function q query of the DB, ie: two dimensioned array, first dimension is a counter for the records returned, second dimension is the name of the db field returned
// in this case the db fields are ele_id and ele_caption
// $fid is required, $frid is optional

function getAllColList($fid, $frid="") {

	global $xoopsDB;
	if(!$frid AND !$fid) { exit("Error:  list of columns requested without specifying a form or a framework."); }
	// generate the $allcols list
	if($frid) {
		$fids[0] = $fid;
		$check_results = checkForLinks($frid, $fids, $fid, "", "", "", "", "", "", "0");
		$fids = $check_results['fids'];
		$sub_fids = $check_results['sub_fids'];
//		$all_fids = array_merge($sub_fids, $fids);
//		array_unique($all_fids);
//		foreach($all_fids as $this_fid) {
		foreach($fids as $this_fid) {
			$c = q("SELECT ele_id, ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE id_form='$this_fid' ORDER BY ele_order");
			$cols[$this_fid] = $c;
		}
		foreach($sub_fids as $this_fid) {
			$c = q("SELECT ele_id, ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE id_form='$this_fid' ORDER BY ele_order");
			$cols[$this_fid] = $c;
		}
	} else {
		$cols[$fid] = q("SELECT ele_id, ele_caption FROM " . $xoopsDB->prefix("form") . " WHERE id_form='$fid' ORDER BY ele_order");
	}

	return $cols;
}

// This function takes an array of advanced search terms and composes a human readable version of the query
function writableQuery($items, $mod="") {
	for($i=0;$i<count($items);$i++) {
		unset($temp_text);
		if(substr($items['as_' . $i], 0, 7) == "[field]" AND substr($items['as_' . $i], -8) == "[/field]") { // a field has been found
			$fieldLen = strlen($items['as_' . $i]);
			$items['as_' . $i] = substr($items['as_' . $i], 7, $fieldLen-15); // 15 is the length of [field][/field]
			$temp_text = getCalcHandleText($items['as_' . $i]);
			if(strlen($temp_text)>20) {
				$items['as_' . $i] = "<a href=\"\" alt=\"$temp_text\" title=\"$temp_text\" onclick=\"javascript:return false;\">" . printSmart($temp_text, "20") ."</a>";
			} else {
				$items['as_' . $i] = $temp_text;
			}			
		} elseif($items['as_' . $i] == "==") { 
			$items['as_' . $i]="="; 
		} elseif($items['as_' . $i] == "!=") {
			 $items['as_' . $i]=" NOT "; 
		} elseif($items['as_' . $i] == "NOT") { 
			$items['as_' . $i]=" NOT "; 
		} elseif($items['as_' . $i] == "AND") {
			$items['as_' . $i]=" AND ";
		} elseif($items['as_' . $i] == "OR") {
			$items['as_' . $i]=" OR "; 
		} elseif($items['as_' . $i] == "LIKE") {
			$items['as_' . $i]=" LIKE "; 
		} elseif($items['as_' . $i] == "NOT LIKE") {
			$items['as_' . $i]=" NOT LIKE "; 
		} elseif ($items['as_' . $i] == "{USER}" AND $mod != 1) {
			global $xoopsUser;
			$term = $xoopsUser->getVar('name');
			if(!$term) { $term = $xoopsUser->getVar('uname'); }
			$items['as_' . $i] = $term;
		} elseif ($items['as_' . $i] == "{TODAY}" AND $mod != 1) {
			$items['as_' . $i] = date("Y-m-d");
		}

		$item_to_write = stripslashes($items['as_' . $i]);

		if(strlen($item_to_write)>20 AND !$temp_text) {
			$item_to_write = "<a href=\"\" alt=\"" . $items['as_' . $i] . "\" title=\"" . $items['as_' . $i] . "\" onclick=\"javascript:return false;\">" . printSmart($item_to_write, "20") ."</a>";
		}			

		$qstring .= $item_to_write;
	}
	return $qstring;
}


// THIS FUNCTION TAKES A HANDLE FROM THE CALCULATIONS RESULT AND RETURNS THE TEXT TO PUT ON THE SCREEN THAT CORRESPONDS TO IT
// Also used for advanced searches
function getCalcHandleText($handle, $frid="") {
	global $xoopsDB;
	if($handle == "uid") {
		return _formulize_DE_CALC_CREATOR;
	} elseif($handle == "proxyid") {
		return _formulize_DE_CALC_MODIFIER;
	} elseif($handle == "creation_date") {
		return _formulize_DE_CALC_CREATEDATE;
	} elseif($handle == "mod_date") {
		return _formulize_DE_CALC_MODDATE;
	} elseif(is_numeric($handle)) {
		$caption = q("SELECT ele_caption FROM " . $xoopsDB->prefix("form"). " WHERE ele_id = '$handle'"); 
		return $caption[0]['ele_caption'];
	} else { // assume framework handle
		return getCaption($frid, $handle);
	}
}


// this function builds the scope used for passing to the getData function
// based on values of either mine, group, all, or a groupid string formatted with start, end and inbetween commas: ,1,3,
function buildScope($currentView, $member_handler, $uid, $groups) {
	if($currentView == "mine" OR substr($currentView, 0, 4) == "old_") {
		$all_users[] = $uid;
		$scope = makeUidFilter($all_users);
	} elseif($currentView == "group") {
		foreach($groups as $grp) {
			if($grp != XOOPS_GROUP_USERS) { // exclude registered users group since that's everyone!
				$temp_users = $member_handler->getUsersByGroup($grp);
				$all_users = array_merge($temp_users, $all_users);
				unset($temp_users);
			}
		}
		$scope = makeUidFilter($all_users);
	} elseif(strstr($currentView, ",")) { // advanced scope, or oldscope
		$grouplist = explode("," , trim($currentView, ","));
		foreach($grouplist as $grp) {
			if($grp == XOOPS_GROUP_ANONYMOUS) { $all_users[] = 0; }
			if($grp != XOOPS_GROUP_USERS) { // exclude registered users group since that's everyone!
				$temp_users = $member_handler->getUsersByGroup($grp);
				$all_users = array_merge($temp_users, $all_users);
				unset($temp_users);
			}
		}
		if(!isset($all_users[0])) { // safeguard against empty or invalid grouplists
			$all_users[] = $uid;
		}
		$scope = makeUidFilter($all_users);
	} elseif($currentView == "all") {
		$scope = "";
	} else { // in the case of an invalid currentView, show the user their own entries
		$all_users[] = $uid;
		$scope = makeUidFilter($all_users);
	}
	return $scope;
}


?>