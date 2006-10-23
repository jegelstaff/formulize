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

// Added Oct. 16 2006
// setup flag for whether the Freeform Solutions user archiving patch has been applied to the core
global $xoopsDB;
$sql = "SELECT * FROM " . $xoopsDB->prefix("users") . " LIMIT 0,1";
if($res = $xoopsDB->query($sql)) {
  $resarray = $xoopsDB->fetchArray($res);
  $GLOBALS['formulize_archived_available'] = isset($resarray['archived']) ? true : false;
} else {
  $GLOBALS['formulize_archived_available'] = false;
}

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
			$formid = q("SELECT id_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE desc_form = '$formframe'");
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
	$titleq = q("SELECT desc_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form ='$fid'");
	return $titleq[0]['desc_form'];

}

//this function returns the list of all the user's full names for all the users in the specified group(s)
// $nametype is either uname or name
function gatherNames($groups, $nametype) {
	global $xoopsDB;
	$member_handler =& xoops_gethandler('member');
	$all_users = array();
	foreach($groups as $group) {
		if($group == XOOPS_GROUP_USERS) { continue; }
		$groupusers = $member_handler->getUsersByGroup($group, true);
		$all_users = array_merge($groupusers, $all_users);
	}
	array_unique($all_users);
	foreach($all_users as $user) {
		$found_names[$user->getVar('uid')] = $user->getVar($nametype);
	}
	natsort($found_names);	
	return $found_names;

// recoded above to use $member_handler, and that will intelligently take archived users into account if the Freeform archive patch is in effect.
// rather inefficient to do it this way, since the member handler has to do one DB query per user to get the required info?
// checking for presence of archived field in the users table, and then custom crafting SQL would result in faster execution.
/*
	$all_users = array();
	foreach($groups as $group) {
		if($group == XOOPS_GROUP_USERS) { continue; }
		$users =& $member_handler->getUsersByGroup($group);
		$all_users = array_merge($all_users, $users);
		unset($users);
	}
	array_unique($all_users);
	$filter = makeUidFilter($all_users);
	$names = q("SELECT $nametype, uid FROM " . $xoopsDB->prefix("users") . " WHERE $filter ORDER BY $nametype");
	foreach($names as $name) {
		$found_names[$name['uid']] = $name[$nametype];
	} 
	return($found_names);
*/
}

//get the currentURL
function getCurrentURL() {
	static $url = "";
	if($url) { return $url; }
	$url_parts = parse_url(XOOPS_URL);
//	return $url_parts['scheme'] . "://" . $url_parts['host'] . $_SERVER['REQUEST_URI']; 
	$url = $url_parts['scheme'] . "://" . $url_parts['host']; 
	$url = isset($url_parts['port']) ? $url . ":" . $url_parts['port'] : $url;
	$url .= $_SERVER['REQUEST_URI']; 
	return $url;
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
      $s_reports = q("SELECT report_id, report_name FROM " . $xoopsDB->prefix("formulize_reports") . " WHERE report_id_form='$fid' AND report_uid='$uid'");


	// get old published reports
	$published_reports = q("SELECT report_id, report_name, report_groupids, report_uid FROM " . $xoopsDB->prefix("formulize_reports") . " WHERE report_id_form='$fid' AND report_ispublished > 0");


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

	if($entry == "proxy") { $entry=""; }

	if(!$groups) { // if no groups specified, use current user
		global $xoopsUser;
		$groups = $xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;
	}

	if(!$mid) { // if no mid specified, set it
		$mid = getFormulizeModId();
	}

	if(!$gperm_handler) {
		$gperm_handler =& xoops_gethandler('groupperm');
 	}

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
				// if no view_groupscope, then check to see if the settings for the form are "one entry per group" in which case override the groupscope setting
				if(!$view_groupscope) { 
					global $xoopsDB;
					$smq = q("SELECT singleentry FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form=$fid");
					if($smq[0]['singleentry'] == "group") { $view_groupscope = true; }
				}
				$groupsWithAccess = $gperm_handler->getGroupIds("view_form", $fid, $mid);
				$intersect_groups = array_intersect($owner_groups, $groupsWithAccess, $groups);
				sort($intersect_groups); // necessary to make sure that 0 will be a valid key to use below
/*print_r($groups);
print "<br>";
print_r($owner_groups);
print "<br>";
print_r($groupsWithAccess);
print "<br>";
print_r($intersect_groups);
print "<br>";*/

				if(!$view_groupscope OR (count($intersect_groups) == 1 AND $intersect_groups[0] == XOOPS_GROUP_USERS) OR count($intersect_groups) == 0) {
					// if they have no groupscope, or if they do have groupscope, but the only point of overlap between the owner, the current user, and the groups with access is the registered users group, then..... (note that registered users will probably be an irrelevant check since the new "groups with access" checking ought to exclude registered users group in complex group setups)
					// last hope...check for a unlocked view that has been published to them which covers a group that includes this entry
					// 1. get groups for unlocked view for this user's groups where the mainform is $fid or there is no mainform and formframe is $fid
					// 2. if group or all scope, allow it
					// 3. or if there's an intersection on the owner_groups and the groups in an unlocked view, then allow it.
					global $xoopsDB;
					$unlockviews = q("SELECT sv_currentview, sv_pubgroups FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_lockcontrols=0 AND ((sv_formframe='$fid' AND sv_mainform='') OR sv_mainform='$fid')");
					foreach($unlockviews as $thisview) {
						$pubbedgroups = explode(",", $thisview['sv_pubgroups']);
						if(array_intersect($pubbedgroups, $groups)) { // if this saved view has been published to the user's groups...
							if($thisview['sv_currentview'] == "all") { return true; } // user has been published an unlocked view for which the scope is all
							// what about groupscope in the view?  is that accounted for below, or should we check against "group"??
							$viewgroups = explode(",", $thisview['sv_currentview']);
							if(array_intersect($owner_groups, $viewgroups)) { return true; }
						}
					}					
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
// KEYFIELD IS OPTIONAL, and sets the key of the result array to be one of the fields in the query.  Useful if you want to use isset with a value to determine the presence of something in the result set, instead of searching the array.
function q($query, $keyfield="") {

	global $xoopsDB;
	$result = array();
	//print "$query"; // debug code
	if($res = $xoopsDB->query($query)) {
		while ($array = $xoopsDB->fetchArray($res)) {
			if($keyfield) {
				$result[$array[$keyfield]] = $array;
			} else {
				$result[] = $array;
			}
		}
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
// need ids flag will cause the returned array to be IDs instead of header text
// we do not filter the headerlist for private elements, because the columns in entriesdisplay are filtered for private columns (and display columns) after being gathered.
function getHeaderList ($fid, $needids=false) {
	
	global $xoopsDB;

	$headerlist = array();

	$hlq = "SELECT headerlist FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form='$fid'";
	if($result = $xoopsDB->query($hlq)) {
		while ($row = $xoopsDB->fetchRow($result)) {
			$headerlist = explode("*=+*:", $row[0]); 
			array_shift($headerlist);
		}
		// handling for id based headerlists added March 6 2005, by jwe
		if(is_numeric($headerlist[0]) OR $headerlist[0] == "uid" OR $headerlist[0] == "proxyid" OR $headerlist[0] == "creation_date" OR $headerlist[0] == "mod_date") { // if the headerlist is using the new ID based system
			if(!$needids) { // if we want actual text headers, convert ids to text...
      			$start = 1;
      			foreach($headerlist as $thisheaderid) {
					if($thisheaderid == "uid") {
						$headerlist[] = _formulize_DE_CALC_CREATOR;
						continue; 
					}
					if($thisheaderid == "proxyid") {
						$headerlist[] = _formulize_DE_CALC_MODIFIER;
						continue; 
					}
					if($thisheaderid == "creation_date") {
						$headerlist[] = _formulize_DE_CALC_CREATEDATE;
						continue; 
					}
					if($thisheaderid == "mod_date") {
						$headerlist[] = _formulize_DE_CALC_MODDATE;
						continue; 
					}
      				if($start) {
      					$where_clause = "ele_id='$thisheaderid'";
      					$start = 0;
      				} else {
      					$where_clause .= " OR ele_id='$thisheaderid'";
      				}
      			}
      			$captionq = "SELECT ele_caption, ele_colhead FROM " . $xoopsDB->prefix("formulize") . " WHERE $where_clause AND (ele_type != \"ib\" AND ele_type != \"areamodif\" AND ele_type != \"subform\") ORDER BY ele_order";
      			if($rescaptionq = $xoopsDB->query($captionq)) {
      				unset($headerlist);
      				while ($row = $xoopsDB->fetchArray($rescaptionq)) {
     						if($row['ele_colhead'] != "") {
     							$headerlist[] = $row['ele_colhead'];						
     						} else {
     							$headerlist[] = $row['ele_caption'];
     						}
      				}
      			} else {
      				exit("Error returning the default list of captions.");
      			}
			}
		} else { // not using new ID based system, so convert to ids if needids is true
			if($needids) {
				$tempheaderlist = $headerlist;
				unset($headerlist);
				$headerlist = convertHeadersToIds($tempheaderlist, $fid); 
			}
		}
	}


	if(count($headerlist)==0) { // if no header fields specified, then....
		// GATHER REQUIRED FIELDS FOR THIS FORM...
		$reqfq = "SELECT ele_caption, ele_colhead, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_req=1 AND id_form='$fid' AND (ele_type != \"ib\" AND ele_type != \"areamodif\" AND ele_type != \"subform\") ORDER BY ele_order ASC LIMIT 3";
		if($result = $xoopsDB->query($reqfq)) {
			while ($row = $xoopsDB->fetchArray($result)) {
				if($needids) {
					$headerlist[] = $row['ele_id'];
				} else {
					if($row['ele_colhead'] != "") {
						$headerlist[] = $row['ele_colhead'];						
					} else {
						$headerlist[] = $row['ele_caption'];
					}
				}
			}
		}
	} 

	if(count($headerlist) == 0) { 
		// IF there are no required fields THEN ... go with first three fields 
		$firstfq = "SELECT ele_caption, ele_colhead, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form='$fid' AND (ele_type != \"ib\" AND ele_type != \"areamodif\" AND ele_type != \"subform\") ORDER BY ele_order ASC LIMIT 3";
		if($result = $xoopsDB->query($firstfq)) {
			while ($row = $xoopsDB->fetchArray($result)) {
				if($needids) {
					$headerlist[] = $row['ele_id'];
				} else {
					if($row['ele_colhead'] != "") {
						$headerlist[] = $row['ele_colhead'];						
					} else {
						$headerlist[] = $row['ele_caption'];
					}
				}
			}
		}
	}
	return $headerlist;
} 

// gets the ele_ids of the headerlist for a form
// now only used when opening a legacy report from 1.6 or older, or when reading a headerlist that is based on the old non-ID based system
function convertHeadersToIds($headers, $fid) {
	global $xoopsDB;
	foreach($headers as $cap) {
		$cap = addslashes($cap);
		$ele_id = q("SELECT ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form='$fid' AND ele_caption='" . str_replace("`", "'", $cap) . "'"); // assume only one match, even though that is not enforced!  Ignores colheads since no use of this function should ever be passing colheads to it (only used for legacy purposes).
		$ele_ids[] = $ele_id[0]['ele_id'];
	}
	return $ele_ids;
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

	// EXCLUDE THE USERPROFILE FORM UNLESS THE USER HAS VIEW_GROUPSCOPE OR VIEW_GLOBALSCOPE ON IT
	// added Mar 15 2006, jwe
	$config_handler =& xoops_gethandler('config');
	$xoopsModuleConfig =& $config_handler->getConfigsByCat(0, $module_id); // get the *Formulize* module config settings
 	$pform = $xoopsModuleConfig['profileForm'];
	if(!$pform) { return $allowedForms; }
	$pformKey = array_search($pform, $allowedForms);
	if(isset($pformKey)) { // if the profileForm is allowed...
		// check if the user has view group or view global on that form....
		if(!$pform_view_groupscope = $gperm_handler->checkRight("view_groupscope", $pform, $groups, $module_id) AND !$pform_view_globalscope = $gperm_handler->checkRight("view_globalscope", $pform, $groups, $module_id)) {
			// if no group or global perm, then remove from array....
			unset($allowedForms[$pformKey]);
		}
	}

	return $allowedForms;

}

// THIS FUNCTION RETURNS THE NAME OF A FORM WHEN GIVEN THE ID (internal, not meant for public use)
function fetchFormName($id) {
	global $xoopsDB;
	$title_q = q("SELECT desc_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form='$id'");
	return trans($title_q[0]['desc_form']);
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
			$status_q = q("SELECT menuid, position FROM " . $xoopsDB->prefix("formulize_menu") . " WHERE menuid='$thisform' AND status=1");
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
				$block = "<a class=\"menuTop\" href=\"$itemurl\">$thiscat</a>";
				$topwritten = 1;
			} else {
                		$block = "<a class=\"menuMain\" href=\"$itemurl\">$thiscat</a>";
			}

			// check to see if current cat is active (ie: has been clicked)
			if($force_open OR (isset($_GET['cat']) AND $thisid == $_GET['cat']) OR in_array($id_form, $formsInCat)) { // if we're viewing this category or a form in this category, or this is the only category (force open)...

				foreach($formsInCat as $thisform) {
					// altered sept 8 to use IDs
					$title = fetchFormNames($thisform);
					//$urltitle = str_replace(" ", "%20", $title);
					$suburl = XOOPS_URL."/modules/formulize/index.php?fid=$thisform";
					$block .= "<a class=\"menuSub\" href='$suburl'>$title</a>";

				}
			}
		}
		return $block;
}


//THIS FUNCTION TAKES AN ARRAY AND DELETES ENTRIES IN A FORM
//based on assumption that id_req is unique.
//only called from the displayForm function, when handling deletion of entries in a subform.  This is a much more efficient function for that task that the normal deleteEntry function below.
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
	$deleteq = "DELETE FROM " . $xoopsDB->prefix("formulize_form") . " WHERE ($filter)";
	if(!$res = $xoopsDB->query($deleteq)) {
		exit("Error deleting entries from the database with this statement:<br>$deleteq");
	}

	// only do the maintenance if the main deletion was successful (otherwise we potentially mangle data for entries that are still around)
	foreach($array as $id_req) {
		deleteMaintenance($id_req);
	}

	// notifications in this case are handled in the formdisplay.php file where this function is called
}

// THIS FUNCTION REMOVES ENTRIES FROM THE OTHER TABLE BASED ON AN IDREQ
// ALSO REMOVED ENTRIES FROM THE ONE TO ONE TABLE

function deleteMaintenance($id_req) {

	global $xoopsDB;

	// remove listings in one_to_one links table
	$sql = "DELETE FROM ". $xoopsDB->prefix("formulize_onetoone_links") . " WHERE main_form='$id_req' OR link_form='$id_req'";
	if(!$result2 = $xoopsDB->query($sql)) {
		exit("Error: failed to delete one to one links for entry $id_req");
	}


	// remove entries in the formulize_other table
	$sql3 = "DELETE FROM " . $xoopsDB->prefix("formulize_other") . " WHERE id_req='$id_req'";
	if(!$result3 = $xoopsDB->query($sql3)) {
		exit("Error: failed to delete 'Other' text for entry $id_req");
	}

}



//THIS FUNCTION ACTUALLY DOES THE DELETING OF A SPECIFIC ID_REQ
function deleteIdReq($id_req) {

	global $xoopsDB;
	$sql = "DELETE FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req='$id_req'";
	//print $sql . "<br>";
	if(!$result = $xoopsDB->query($sql)) {
		exit("Error: failed to delete entry $id_req");
	}

	deleteMaintenance($id_req);

}

// THIS FUNCTION DELETES ENTRIES FROM formulize_FORM WHEN PASSED AN ID_REQ
// HANDLES FRAMEWORKS TOO -- HANDLERS AND MID TO BE PASSED IN WHEN FRAMEWORKS ARE USED
// owner and owner_groups to be passed in when available (if called from a function where they have already been determined
function deleteEntry($id_req, $frid="", $fid="", $gperm_handler="", $member_handler="", $mid="", $owner="", $owner_groups="") {

	global $xoopsDB;
	$deletedEntries = array();

	if($frid) { // if a framework is passed, then delete all items found in a unified display relationship with the base entry, in addition to the base entry itself
		$fids[0] = $fid;
		$entries[$fid][0] = $id_req;
		if(!$owner) { $owner = getEntryOwner($entry); }
		if(!$owner_groups) { $owner_groups =& $member_handler->getGroupsByUser($owner, FALSE); }
		$linkresults = checkForLinks($frid, $fids, $fid, $entries, $gperm_handler, $owner_groups, $mid, $member_handler, $owner);
		foreach($linkresults['entries'] as $thisfid=>$ents) {
			foreach($ents as $ent) {
				if($ent) { 
					deleteIdReq($ent); 
					$deletedEntries[$thisfid][] = $ent;
				}
			}
		}
		foreach($linkresults['sub_entries'] as $thisfid=>$ents) {
			foreach($ents as $ent) {
				if($ent) { 
					deleteIdReq($ent); 
					$deletedEntries[$thisfid][] = $ent;
				}

			}
		}
	} else {
		$deletedFid = q("SELECT id_form FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req=\"" . intval($id_req) . "\" LIMIT 0,1");
		deleteIdReq($id_req);
		$deletedEntries[$deletedFid[0]['id_form']][] = $id_req;
	} // end of if frid

	// do notifications
	foreach($deletedEntries as $thisfid=>$entries) {
		sendNotifications($thisfid, "delete_entry", $entries, $mid); // last param, groups, is missing, notification function will put it in itself
	}


}

// GETS THE ID OF THE USER WHO OWNS AN ENTRY
function getEntryOwner($entry) {
	global $xoopsDB;
	$owner = q("SELECT uid FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req = '$entry' LIMIT 0,1");
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

// FUNCTION HANDLES CHECKING FOR ALL LINKING RELATIONSHIPS FOR THE FORM
// returns the fids and entries passed to it, plus any others in a framework relationship
// final param is a flag to control whether only unified display relationships are returned or all relationships
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
		$one_q1 = q("SELECT fl_form1_id, fl_key1, fl_key2, fl_common_value FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form2_id = $fid AND fl_relationship = 1 AND fl_frame_id = $frid $unified_display");
		$one_q2 = q("SELECT fl_form2_id, fl_key1, fl_key2, fl_common_value FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form1_id = $fid AND fl_relationship = 1 AND fl_frame_id = $frid $unified_display");
		$indexer=0;
		foreach($one_q1 as $res1) {
			$one_to_one[$indexer]['fid'] = $res1['fl_form1_id'];
			$one_to_one[$indexer]['keyself'] = $res1['fl_key1'];
			$one_to_one[$indexer]['keyother'] = $res1['fl_key2'];
			$one_to_one[$indexer]['common'] = $res1['fl_common_value'];
			$indexer++;
		}
		foreach($one_q2 as $res2) {
			$one_to_one[$indexer]['fid'] = $res2['fl_form2_id'];
			$one_to_one[$indexer]['keyother'] = $res2['fl_key1'];
			$one_to_one[$indexer]['keyself'] = $res2['fl_key2'];
			$one_to_one[$indexer]['common'] = $res2['fl_common_value'];
			$indexer++;
		}
		
		$indexer=0;
		// get one-to-many links
		$many_q1 = q("SELECT fl_form1_id, fl_key1, fl_key2, fl_common_value FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form2_id = $fid AND fl_relationship = 3 AND fl_frame_id = $frid $unified_display");
		$many_q2 = q("SELECT fl_form2_id, fl_key1, fl_key2, fl_common_value FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form1_id = $fid AND fl_relationship = 2 AND fl_frame_id = $frid $unified_display");

		foreach($many_q1 as $res1) {
			$one_to_many[$indexer]['fid'] = $res1['fl_form1_id'];
			$one_to_many[$indexer]['keyself'] = $res1['fl_key1'];
			$one_to_many[$indexer]['keyother'] = $res1['fl_key2'];
			$one_to_many[$indexer]['common'] = $res1['fl_common_value'];
			$indexer++;
		}
		foreach($many_q2 as $res2) {
			$one_to_many[$indexer]['fid'] = $res2['fl_form2_id'];
			$one_to_many[$indexer]['keyother'] = $res2['fl_key1'];
			$one_to_many[$indexer]['keyself'] = $res2['fl_key2'];
			$one_to_many[$indexer]['common'] = $res2['fl_common_value'];
			$indexer++;
		}
		$one_to_many = array_unique($one_to_many);

		// get MANY-TO-ONE links
		$many_q3 = q("SELECT fl_form1_id, fl_key1, fl_key2, fl_common_value FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form2_id = $fid AND fl_relationship = 2 AND fl_frame_id = $frid $unified_display");
		$many_q4 = q("SELECT fl_form2_id, fl_key1, fl_key2, fl_common_value FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form1_id = $fid AND fl_relationship = 3 AND fl_frame_id = $frid $unified_display");

		foreach($many_q3 as $res1) {
			$many_to_one[$indexer]['fid'] = $res1['fl_form1_id'];
			$many_to_one[$indexer]['keyself'] = $res1['fl_key1'];
			$many_to_one[$indexer]['keyother'] = $res1['fl_key2'];
			$many_to_one[$indexer]['common'] = $res1['fl_common_value'];
			$indexer++;
		}
		foreach($many_q4 as $res2) {
			$many_to_one[$indexer]['fid'] = $res2['fl_form2_id'];
			$many_to_one[$indexer]['keyother'] = $res2['fl_key1'];
			$many_to_one[$indexer]['keyself'] = $res2['fl_key2'];
			$many_to_one[$indexer]['common'] = $res2['fl_common_value'];
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
					// look for the found id_req in the formulize_form table as part of the current fid, and only record it if found
					$find_req = q("SELECT ele_id FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req='" . $foundLink['link_form'] . "' AND id_form='" . $one_fid['fid'] . "' LIMIT 1");
					if($find_req[0]['ele_id']) {
						$entries[$one_fid['fid']][] = $foundLink['link_form'];
					}
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



// THIS FUNCTION CREATES AN EXPORT FILE ON THE SERVER AND RETURNS THE FILESNAME
// $headers is the list of column headings in use
// $cols is the list of handles in the $data to use to get all the data for display, must be in synch with headers
// $data is the full dataset that is being prepped
// $fdchoice is either comma or calcs (calcs for when calcs are to be exported)
// $title does not appear to be used
// $template is a flag indicating whether we are making a template for use updating/uploading data -- blank for a blank template, update for a template with data, blankprofile for the userprofile form
// $fid is the form id
// $groups is the user's groups
function prepExport($headers, $cols, $data, $fdchoice, $custdel="", $title, $template=false, $fid, $groups) {

	// export of calculations added August 22 2006
	// come up with a filename and then return it
	// rest of logic in entriesdisplay.php will take the filename and create a file with the calculations in it once they are performed
	if($fdchoice == "calcs") { 
		$tempfold = time();
		$exfilename = _formulize_DE_XF . $tempfold . ".html";
		return XOOPS_URL . "/modules/formulize/export/$exfilename";
	}

	global $xoopsDB;
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";

	if($fdchoice == "update") { // reset headers and cols to include all data -- when creating a blank template, this reset has already happened before prepexport is called
		$fdchoice = "comma";
		$template = "update";
		$cols1 = getAllColList($fid, "", $groups);
		unset($cols);
		$cols = array();
		foreach($cols1[$fid] as $col) {
			$cols[] = $col['ele_id'];
		}
		unset($headers);
		$headers = getHeaders($cols);
	}
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
	if($template) {
		if($template == "blankprofile") { // add in other profile fields -- username, realname, e-mail, password, registration code
			$csvfile = "\"" . _formulize_DE_IMPORT_USERNAME . "\"$fd\"" . _formulize_DE_IMPORT_FULLNAME . "\"$fd\"" . _formulize_DE_IMPORT_PASSWORD . "\"$fd\"" . _formulize_DE_IMPORT_EMAIL . "\"$fd\"" . 	_formulize_DE_IMPORT_REGCODE . "\"";					
		} else {
			if($template == "update") {
				$csvfile = "\"" . _formulize_DE_IMPORT_IDREQCOL . "\"$fd\"" . _formulize_DE_CALC_CREATOR . "\"";
			} else {
				$csvfile = "\"" . _formulize_DE_CALC_CREATOR . "\"";
			}
		}
	} else {
		$csvfile =  "\"" . _formulize_DE_CALC_CREATOR . "\"$fd\"" . _formulize_DE_CALC_CREATEDATE . "\"$fd\"" . _formulize_DE_CALC_MODIFIER . "\"$fd\"" . _formulize_DE_CALC_MODDATE . "\"";
	}
	foreach($headers as $header)
	{
		if($header == _formulize_DE_CALC_CREATOR OR $header == _formulize_DE_CALC_MODIFIER OR $header==_formulize_DE_CALC_CREATEDATE OR $header ==_formulize_DE_CALC_MODDATE) { continue; } // ignore the metadata columns if they are selected, since we already handle them better above
		$header = str_replace("\"", "\"\"", $header);
		$header = "\"" . trans($header) . "\"";
		$csvfile .= $fd . $header;
	}
	
	$csvfile .= "\r\n";

	$colcounter = 0;
	$i=0;
	foreach($data as $entry) {

		// if this file is being generated for downloading and then uploading with changes, record all the id_reqs
		if($template == "update") {
			$formhandle = getFormHandlesFromEntry($entry);
			$ids = internalRecordIds($entry, $formhandle[0]);
			$id = $ids[0];
			$id_req[] = $id;
		}

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
		if($template) { // will be update only, since blank ones have no data
			$csvfile .= $id . $fd . "\"$c_name\"";
		} else {
			$csvfile .= "\"$c_name\"" . $fd . "\"$c_date\"" . $fd . "\"$m_name\"" . $fd . "\"$m_date\"";
		}
		
		// write in data
		foreach($cols as $col) {
			if($col == "uid" OR $col == "proxyid" OR $col=="creation_date" OR $col =="mod_date") { continue; } // ignore the metadata columns if they are selected, since we already handle them better above
			$data_to_write = displayTogether($entry, $col, "\n");
			$data_to_write = str_replace("\"", "\"\"", $data_to_write);
			$data_to_write = "\"" . trans($data_to_write) . "\"";
			$data_to_write = str_replace("\r\n", "\n", $data_to_write);
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
	
	// write id_reqs and tempfold to the DB if we're making an update template
	if($template == "update") {
		$sql = "INSERT INTO " . $xoopsDB->prefix("formulize_valid_imports") . " (file, id_reqs) VALUES (\"$tempfold\", \"" . serialize($id_req) . "\")";
		if(!$res = $xoopsDB->query($sql)) {
			exit("Error: could not write import information to the database.  SQL: $sql");
		}
	}


	// need to add in logic to cull old files...
	


	return XOOPS_URL . "/modules/formulize/export/$exfilename";

}

// this function returns the data to summarize the details about the entry you are looking at
function getMetaData($entry, $member_handler) {
	global $xoopsDB;
	$meta = q("SELECT uid, date FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req = $entry AND date > 0 ORDER BY date DESC LIMIT 0,1");
	$meta_proxyid = q("SELECT proxyid FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req = $entry AND proxyid != uid ORDER BY date DESC LIMIT 0,1");
	$meta_creation_date = q("SELECT creation_date FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req = $entry AND creation_date > 0 ORDER BY creation_date ASC LIMIT 0,1");
	$meta_to_return['last_update'] = $meta[0]['date'];
	if($meta_creation_date[0]['creation_date']) {
		$meta_to_return['created'] = $meta_creation_date[0]['creation_date'];
	} else {
		$meta_to_return['created'] = "???";
	}
	$user = $member_handler->getUser($meta[0]['uid']);
	$meta_to_return['created_by_uid'] = $meta[0]['uid'];
	if($user) {
		if(!$create_name = $user->getVar('name')) { $create_name = $user->getVar('uname'); }
		$meta_to_return['created_by'] = $create_name;
	} else {
		$meta_to_return['created_by'] = _FORM_ANON_USER;
	}
	if($meta_proxyid[0]['proxyid']) {
		$proxy = $member_handler->getUser($meta_proxyid[0]['proxyid']);
		$meta_to_return['last_update_by_uid'] = $meta_proxyid[0]['proxyid'];
		if($proxy) {
			if(!$proxy_name = $proxy->getVar('name')) { $proxy_name = $proxy->getVar('uname'); }
			$meta_to_return['last_update_by'] = $proxy_name;
		} else {
			$meta_to_return['last_update_by'] = _FORM_ANON_USER;
		}
	} else {
		$meta_to_return['last_update_by'] = $meta_to_return['created_by'];
		$meta_to_return['last_update_by_uid'] = $meta_to_return['created_by_uid'];
	}
	return $meta_to_return;
}

// this function returns the complete set of columns that are in a form or framework
// the returned array contains one DB query result for each form
// ie:  $cols[form1] = all columns in that form, $cols[form2] = all columns in that form
// columns are the raw results from a function q query of the DB, ie: two dimensioned array, first dimension is a counter for the records returned, second dimension is the name of the db field returned
// in this case the db fields are ele_id and ele_caption and ele_colhead
// $fid is required, $frid is optional
// $groups is the grouplist of the current user.  It is optional.  If present it will limit the columns returned to the ones where display is 1 or the display includes that group

function getAllColList($fid, $frid="", $groups="", $includeBreaks=false) {

	global $xoopsDB, $xoopsUser;
	$gperm_handler = &xoops_gethandler('groupperm');
	$mid = getFormulizeModId();

	// if $groups then build the necessary filter
	// build query for display groups
	$gq = "";
	if($groups) {
		$gq = "AND (ele_display=1";
		foreach($groups as $thisgroup) {
			$gq .= " OR ele_display LIKE '%,$thisgroup,%'";
		}
		$gq .= ")";
	} 

	// reset groups to be based off user object (and this instantiates it if it weren't present before)
	$groups = $xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;		

	// if current user does NOT have view_private_elements permission, then set a query to exclude those elements
	$pq = "";
	if(!$view_private_elements = $gperm_handler->checkRight("view_private_elements", $fid, $groups, $mid)) {
		$pq = "AND ele_private=0";
	}

	if(!$includeBreaks) {
		$incbreaks = "AND (ele_type != \"ib\" AND ele_type != \"areamodif\")";
	} else {
		$incbreaks = "";
	}

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
		$uid = $xoopsUser ? $xoopsUser->getVar('uid') : "0";
		foreach($fids as $this_fid) {
			if(security_check($this_fid, "", $uid, "", $groups, $mid, $gperm_handler)) { 
				$c = q("SELECT ele_id, ele_caption, ele_colhead FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form='$this_fid' $gq $pq $incbreaks AND ele_type != \"subform\" ORDER BY ele_order");
				$cols[$this_fid] = $c;
			} 
		}
		foreach($sub_fids as $this_fid) {
			if(security_check($this_fid, "", $uid, "", $groups, $mid, $gperm_handler)) { 
				$c = q("SELECT ele_id, ele_caption, ele_colhead FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form='$this_fid' $gq $pq $incbreaks AND ele_type != \"subform\" ORDER BY ele_order");
				$cols[$this_fid] = $c;
			}
		}
	} else {
		$cols[$fid] = q("SELECT ele_id, ele_caption, ele_colhead FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form='$fid' $gq $pq $incbreaks AND ele_type != \"subform\" ORDER BY ele_order");
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
				$items['as_' . $i] = "<a href=\"\" alt=\"" . trans($temp_text) . "\" title=\"" . trans($temp_text) . "\" onclick=\"javascript:return false;\">" . printSmart(trans($temp_text), "20") ."</a>";
			} else {
				$items['as_' . $i] = trans($temp_text);
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
		} elseif ($items['as_' . $i] == "{BLANK}" AND $mod != 1) {
			$items['as_' . $i] = "\" \"";
 		} elseif (ereg_replace("[^A-Z{}]","", $items['as_' . $i]) == "{TODAY}" AND $mod != 1) {
			$number = ereg_replace("[^0-9+-]","", $items['as_' . $i]);
			$items['as_' . $i] = date("Y-m-d",mktime(0, 0, 0, date("m") , date("d")+$number, date("Y")));
//		lines below commented and replaced with the above check by dpicella which accounts for + and - numbers after {TODAY, ie: {TODAY-14}
//		} elseif ($items['as_' . $i] == "{TODAY}" AND $mod != 1) {
//			$items['as_' . $i] = date("Y-m-d");
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
	} elseif($handle == "creator_email") {
		return _formulize_DE_CALC_CREATOR_EMAIL;
	} elseif(is_numeric($handle)) {
		$caption = q("SELECT ele_caption, ele_colhead FROM " . $xoopsDB->prefix("formulize"). " WHERE ele_id = '$handle'"); 
		if($caption[0]['ele_colhead'] != "") {
			return $caption[0]['ele_colhead'];			
		} else {
			return $caption[0]['ele_caption'];
		}
	} else { // assume framework handle
		return getCaption($frid, $handle, true); // true flag turns on returning of colhead if present
	}
}


// this function builds the scope used for passing to the getData function
// based on values of either mine, group, all, or a groupid string formatted with start, end and inbetween commas: ,1,3,
function buildScope($currentView, $member_handler, $gperm_handler, $uid, $groups, $fid, $mid) {
	if($currentView == "mine" OR substr($currentView, 0, 4) == "old_") {
		$all_users[] = $uid;
		$scope = makeUidFilter($all_users);
	} elseif($currentView == "group") {
		$groupsWithAccess = $gperm_handler->getGroupIds("view_form", $fid, $mid);
		$all_users = array();
		foreach($groups as $grp) {
			if(in_array($grp, $groupsWithAccess)) { // include only groups that have access to view the form ($groups is the user's own groups, so this excludes groups they are a member of but which do not have access to the form -- allows for situations where two users are members of one all encompassing group (Actua National) plus also each members of their own local groups (VV and AES), and you want groupscope to cover only the local groups, so therefore you do NOT give view_form permission (or any form permissions probably) to the all encompassing group.
				if($grp != XOOPS_GROUP_USERS) { // exclude registered users group since that's everyone!  (Note that registered users will likely be excluded now by the previous check, since a rigorously structured group system is unlikely to lead to Registered Users group being handed any form permissions.)
					if(!$GLOBALS['formulize_archived_available']) {
						$temp_users = $member_handler->getUsersByGroup($grp);
					} else {
						$temp_users = $member_handler->getUsersByGroup($grp, false, 0, 0, true); // final true param will include archived users
					}
					$all_users = array_merge($temp_users, $all_users);
					unset($temp_users);
				}
			}
		}
		if(!isset($all_users[0])) { // safeguard against empty or invalid grouplists
			$all_users[] = $uid;
		}
		$scope = makeUidFilter($all_users);
	} elseif(strstr($currentView, ",")) { // advanced scope, or oldscope
		$grouplist = explode("," , trim($currentView, ","));
		$all_users = array();
		foreach($grouplist as $grp) {
			if($grp == XOOPS_GROUP_ANONYMOUS) { 
				$all_users[] = 0; 
				continue;
			}
			if($grp != XOOPS_GROUP_USERS) { // exclude registered users group since that's everyone!
				if(!$GLOBALS['formulize_archived_available']) {
					$temp_users = $member_handler->getUsersByGroup($grp);
				} else {
					$temp_users = $member_handler->getUsersByGroup($grp, false, 0, 0, true); // final true param will include archived users
				}
				$all_users = array_merge($temp_users, $all_users);
				unset($temp_users);
			}
		}
		if(!isset($all_users[0])) { // safeguard against empty or invalid grouplists
			$all_users[] = "";
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

// THIS FUNCTION SENDS TEXT THROUGH THE TRANSLATION ROUTINE IF MARCAN'S MULTILANGUAGE HACK IS INSTALLED
// THIS FUNCTION IS ALSO AWARE OF THE XLANGUAGE MODULE IF THAT IS INSTALLED.  
function trans($string) {
	$myts =& MyTextSanitizer::getInstance();
	if(method_exists($myts, 'formatForML')) {
		$string = $myts->formatForML($string);
	} else {
		if(function_exists('xlanguage_ml')) {
			$string = xlanguage_ml($string);
		}
	} 
	return $string;
}

// THIS FUNCTION FIGURES OUT THE MAX ID_REQ IN USE AND RETURNS THE NEXT VALID ID_REQ
function getMaxIdReq() {
	global $xoopsDB;
	$sql = $xoopsDB->query("SELECT id_req from " . $xoopsDB->prefix("formulize_form")." order by id_req DESC LIMIT 0,1");
	list($id_req) = $xoopsDB->fetchRow($sql);
	if ($id_req == 0) { $num_id = 1; }
	else if ($num_id <= $id_req) $num_id = $id_req + 1;
	return $num_id;
}

// THIS FUNCTION GETS THE CAPTION BASED ON A DB QUERY, NOT ON GETVAR, so the value returned is the actual full caption for the element
// used by elementdisplay.php
function getRealCaption($ele_id) {
	global $xoopsDB;
	$sql = "SELECT ele_caption FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id = '$ele_id'";
	$res = $xoopsDB->query($sql);
	list($dec) = $xoopsDB->fetchRow($res);
	$dec = stripslashes($dec);
	$dec = str_replace ("&#039;", "`", $dec);
	$dec = str_replace ("&quot;", "`", $dec);
	$dec = str_replace ("'", "`", $dec);
	return $dec;
}

// THIS FUNCTION MASSAGES DATA RETURNED FROM A FORM SUBMISSION SO IT CAN BE PUT IN THE DATABASE
// param it takes is the element object ($element), and the passed value from the form ($ele)
function prepDataForWrite($element, $ele) {

	global $myts;
	if(!$myts) { $myts =& MyTextSanitizer::getInstance(); }

	$ele_type = $element->getVar('ele_type');
	$ele_value = $element->getVar('ele_value');
	$ele_id = $element->getVar('ele_id');
		switch($ele_type){
				case 'text':
					if($ele_value[3]) { // if $ele_value[3] is 1 (default is 0) then treat this as a numerical field
						$value = ereg_replace ('[^0-9.-]+', '', $ele);
					} else {
						$value = $ele; 
					}
					if(get_magic_quotes_gpc()) { $value = stripslashes($value); }
					$value = $myts->htmlSpecialChars($value);
				break;
				case 'textarea':
					$value = $ele; 
					if(get_magic_quotes_gpc()) { $value = stripslashes($value); }
					$value = $myts->htmlSpecialChars($value);
				break;
				case 'areamodif':
					$value = $myts->stripSlashesGPC($ele);
				break;
				case 'radio':
					$value = '';
					$opt_count = 1;
					while( $v = each($ele_value) ){
						if( $opt_count == $ele ){
							$GLOBALS['formulize_other'][$ele_id] = checkOther($v['key'], $ele_id);
							$msg.= $myts->stripSlashesGPC($v['key']).'<br>';
							if(get_magic_quotes_gpc()) { $v['key'] = stripslashes($v['key']); }
							$v['key'] = $myts->htmlSpecialChars($v['key']);
							$value = $v['key'];
						}
						$opt_count++;
					}
				break;
				case 'yn':
					$value = $ele;
				break;
				case 'checkbox':
					$value = '';
					$opt_count = 1;
					while( $v = each($ele_value) ){
						if( is_array($ele) ){
							if( in_array($opt_count, $ele) ){
								$GLOBALS['formulize_other'][$ele_id] = checkOther($v['key'], $ele_id);
								if(get_magic_quotes_gpc()) { $v['key'] = stripslashes($v['key']); }
								$v['key'] = $myts->htmlSpecialChars($v['key']);
								$value = $value.'*=+*:'.$v['key'];
							}
							$opt_count++;
						}else{
							if( !empty($ele) ){
								$GLOBALS['formulize_other'][$ele_id] = checkOther($v['key'], $ele_id);
								if(get_magic_quotes_gpc()) { $v['key'] = stripslashes($v['key']); }
								$v['key'] = $myts->htmlSpecialChars($v['key']);
								$value = $value.'*=+*:'.$v['key'];
							}
						}						
					}
				break;
				case 'select':
					// section to handle linked select boxes differently from others...
					$formlinktrue = 0;
					if(is_array($ele))  // look for the formlink delimiter
					{
						foreach($ele as $justacheck)
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
						if(strstr($ele, "#*=:*"))
						{
							$formlinktrue = 1;
						}
					}
					if($formlinktrue) // if we've got a formlink, then handle it here...
					{
						if(is_array($ele))
						{
							//print_r($ele);
							array($compparts);
							$compinit = 0;
							$selinit = 0;
							foreach($ele as $whatwasselected)
							{
								$whatwasselected = lsbSubmitPatch($whatwasselected);
								// if(isset($_GET['debug4'])) { print "<br>$whatwasselected<br>"; }
								$compparts = explode("#*=:*", $whatwasselected);
								if(get_magic_quotes_gpc()) { // strip the slashes since we may be getting slashes passed in from a previous writing to the DB (caption names with apostrophes will generate slashes in the DB when magic quotes is on -- and the value passed back for formlinks is based on the current value in the DB).

									$compparts[1] = stripslashes($compparts[1]);
								}
								// if(isset($_GET['debug4'])) { print_r($compparts); }
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
							$ele = lsbSubmitPatch($ele);
							$value = $ele;
							if(get_magic_quotes_gpc()) { // strip the slashes since we may be getting slashes passed in from a previous writing to the DB (caption names with apostrophes will generate slashes in the DB when magic quotes is on -- and the value passed back for formlinks is based on the current value in the DB).
								$value = stripslashes($value);
							}							
						}	
//						print "<br>VALUE: $value";	
						break;			
					}
					else
					{


					$value = '';

							// The following code block is a replacement for the previous method for reading a select box which didn't work reliably -- jwe 7/26/04
							// print_r($ele_value[2]);
							$temparraykeys = array_keys($ele_value[2]);
							if($temparraykeys[0] == "{FULLNAMES}" OR $temparraykeys[0] == "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s) -- updated for real live use September 6 2006
								if(is_array($ele)) {
									$value = "";
									foreach($ele as $auid) {
										$value .= "*=+*:" . $auid;
									}
								} else {
									$value = $ele;
								}
								break;
							}

							$entriesPassedBack = array_keys($ele_value[2]);
							$keysPassedBack = array_keys($entriesPassedBack);
							$entrycounterjwe = 0;
							foreach($keysPassedBack as $masterentlistjwe)
							{
	      						if(is_array($ele))

								{
									foreach($ele as $whattheuserselected)
									{
										// if the user selected an entry found in the master list of all possible entries...
										//print "internal loop $entrycounterjwe<br>userselected: $whattheuserselected<br>selectbox contained: $masterentlistjwe<br><br>";	
										if($whattheuserselected == $masterentlistjwe)
										{
											//print "WE HAVE A MATCH!<BR>"; -- note: nametype should
											if(get_magic_quotes_gpc()) { $entriesPassedBack[$entrycounterjwe] = stripslashes($entriesPassedBack[$entrycounterjwe]); }
											$entriesPassedBack[$entrycounterjwe] = $myts->htmlSpecialChars($entriesPassedBack[$entrycounterjwe]);
 											$value = $value . "*=+*:" . $entriesPassedBack[$entrycounterjwe];
											//print "$value<br><br>";
										}
									}
									$entrycounterjwe++;
								}
								else
								{
									//print "internal loop $entrycounterjwe<br>userselected: $ele<br>selectbox contained: $masterentlistjwe<br><br>";	
									if($ele == ($masterentlistjwe+1)) // plus 1 because single entry select boxes start their option lists at 1.
									{
										//print "WE HAVE A MATCH!<BR>";
										if(get_magic_quotes_gpc()) { $entriesPassedBack[$entrycounterjwe] = stripslashes($entriesPassedBack[$entrycounterjwe]); }
										$entriesPassedBack[$entrycounterjwe] = $myts->htmlSpecialChars($entriesPassedBack[$entrycounterjwe]);
										$value = $entriesPassedBack[$entrycounterjwe];
										//print "$value<br><br>";

										break;
									}
									$entrycounterjwe++;
								}
							}
					// print "selects: $value<br>";
				break;
				} // end of if that checks for a linked select box.
				case 'date':
					// code below commented/added by jwe 10/23/04 to convert dates into the proper standard format
					if($ele != "YYYY-mm-dd" AND $ele != "") { 
						$ele = date("Y-m-d", strtotime($ele)); 
					} else {
						$ele = "{SKIPTHISDATE}"; // forget about this date element and go on to the next element in the form
					}
					$value = ''.$ele;
				break;
				case 'sep':
					$value = $myts->stripSlashesGPC($ele);
				break;
				default:
				break;
			}

	return $value;

}

// THIS FUNCTION CONTRIBUTED BY DPICELLA.  Added in Mar 15 2006.
// Not currently in use due to current version of PHP natively supporting this feature.
/*
A shorter function for recognising dates before 1970 and returning a negative number is below. All it does is replaces years before 1970 with  ones 68 years later (1904 becomes 1972), and then offsets the return value by a couple billion seconds. It works back to 1/1/1902, but only on dates that have a century.
Note that a negative number is stored the same as a really big positive number. 0x80000000 is the number of seconds between 13/12/1901 20:45:54 and 1/1/1970 00:00:00. And 1570448 is the seconds between this date and 1/1/1902 00:00:00, which is 68 years before 1/1/1970.
*/
function safestrtotime ($s) {
       $basetime = 0;
       if (preg_match ("/19(\d\d)/", $s, $m) && ($m[1] < 70)) {
               $s = preg_replace ("/19\d\d/", 1900 + $m[1]+68, $s);
               $basetime = 0x80000000 + 1570448;
       }
       return $basetime + strtotime ($s);
}


// FIGURES OUT IF THE CURRENT ELEMENT HAS A VALUE FOR THE CURRENT ENTRY
function getElementValue($entry, $caption) {
	global $xoopsDB;

	$evq = "SELECT ele_value FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req='$entry' AND ele_caption='$caption'";
	if($res = $xoopsDB->query($evq)) {
		$array = $xoopsDB->fetchArray($res); 
		if(isset($array['ele_value'])) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}

}

// this function checks for singleentry status and returns the appropriate entry in the form if there is one
function getSingle($fid, $uid, $groups, $member_handler, $gperm_handler, $mid) {
	global $xoopsDB;
	// determine single/multi status
	$smq = q("SELECT singleentry FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form=$fid");
	if($smq[0]['singleentry'] != "") {
		// find the entry that applies
		$single['flag'] = 1;
		if($smq[0]['singleentry'] == "on") { // if we're looking for a regular single, find first entry for this user
			$entry_q = q("SELECT id_req FROM " . $xoopsDB->prefix("formulize_form") . " WHERE uid=$uid AND id_form=$fid ORDER BY id_req LIMIT 0,1");
			if($entry_q[0]['id_req']) {
				$single['entry'] = $entry_q[0]['id_req'];
			} else {
				$single['entry'] = "";	
			}
		} elseif($smq[0]['singleentry'] == "group") { // get the first entry belonging to anyone in their groups, excluding any groups that do not have view_form permission
			$groupsWithAccess = $gperm_handler->getGroupIds("view_form", $fid, $mid);
			$intersect_groups = array_intersect($groups, $groupsWithAccess);
			$all_users = array();
			foreach($intersect_groups as $grp) {
				if($grp != XOOPS_GROUP_USERS) { // exclude registered users group since that's everyone! -- superfluous now since registered users would normally be ignored since people probably would not be handing out perms to registered users group (on the other hand, if someone wanted to, it should be allowed now, since it won't screw things up necessarily, thanks to the use of groupsWithAccess)
					$users = $member_handler->getUsersByGroup($grp);
					$all_users = array_merge($users, $all_users);
					unset($users);
				}
			}
			$uq = makeUidFilter($all_users);
			$entry_q = q("SELECT id_req FROM " . $xoopsDB->prefix("formulize_form") . " WHERE ($uq) AND id_form=$fid ORDER BY id_req LIMIT 0,1");
			if($entry_q[0]['id_req']) {
				$single['entry'] = $entry_q[0]['id_req'];
			} else {
				$single['entry'] = "";	
			}
		} else {
			exit("Error: invalid value found for singleentry for form $fid");
		}
	} else {
		$single['flag'] = 0;
	}
	return $single;

}

// FUNCTION COPIED FROM LIASE 1.26
// JWE - JUNE 1 2006
function checkOther($key, $id){
	global $myts;
	if( !preg_match('/\{OTHER\|+[0-9]+\}/', $key) ){
		return false;
	}else{
		if( !empty($_POST['other']['ele_'.$id]) ){
			return $_POST['other']['ele_'.$id];
		}else{
			return "";
		}
	}
}

// THIS FUNCTION TAKES THE 'Other' VALUES USERS MAY HAVE WRITTEN INTO THE FORM, AND SAVES THEM TO THE db IN THEIR OWN TABLE
// The other values are generated by the prepDataForWrite function, so it has to be called prior to this one 
// ADDED JWE - JUNE 1 2006
function writeOtherValues($id_req, $fid) {
	global $xoopsDB, $myts;
	include_once XOOPS_ROOT_PATH . "/modules/formulize/class/forms.php";
	$thisForm = new formulizeForm($fid);
	foreach($GLOBALS['formulize_other'] as $ele_id=>$value) {

		// filter out any ele_ids that are not part of this form, since when a framework is used, the formulize_other array would contain ele_ids from multiple forms
		if(!in_array($ele_id, $thisForm->getVar('elements'))) { continue; }
		// determine the current status of that element
		$sql = "SELECT * FROM " . $xoopsDB->prefix("formulize_other") . " WHERE ele_id='$ele_id' AND id_req='$id_req' LIMIT 0,1"; 
		$result = $xoopsDB->query($sql);
		$array = $xoopsDB->fetchArray($result);
		if(isset($array['other_id'])) { 
			$existing_value = true;
		} else {
			$existing_value = false;
		}

		if(get_magic_quotes_gpc()) { $value = stripslashes($value); }
		$value = $myts->htmlSpecialChars($value);
		if($value != "" AND $existing_value) { // update
			$sql = "UPDATE " . $xoopsDB->prefix("formulize_other") . " SET other_text=\"" . mysql_real_escape_string($value) . "\" WHERE id_req='$id_req' AND ele_id='$ele_id'";
		}elseif($value != "" AND !$existing_value) { // add 
			$sql = "INSERT INTO " . $xoopsDB->prefix("formulize_other") . " (id_req, ele_id, other_text) VALUES (\"$id_req\", \"$ele_id\", \"" . mysql_real_escape_string($value) . "\")";
		}elseif($value == "" AND $existing_value) { // delete
			$sql = "DELETE FROM " . $xoopsDB->prefix("formulize_other") . " WHERE id_req='$id_req' AND ele_id='$ele_id'";
		}else { // do nothing (only other combination is....  if(!isset($value) AND !$existing_value) ....ie: nothing passed, and nothing existing in DB
			$sql = false;
		}
		if($sql) {
			if(!$result = $xoopsDB->query($sql)) {
				exit("Error writing 'Other' value to the database with this SQL:<br>$sql");
			}
		}
		unset($GLOBALS['formulize_other'][$ele_id]);
	}
}



// THIS FUNCTION CREATES A SERIES OF ARRAYS THAT CONTAIN ALL THE INFORMATION NECESSARY FOR THE LINKED SELECTBOX (AND TEXTBOX) ELEMENTS TO WORK ON THE ADMIN SIDE
// new use with textboxes triggers a different value to be used -- just the ele_id from the 'formulize' table, which is all that is necessary to uniquely identify the element
// note that ele_value has different contents for textboxes and selectboxes
function createFieldList($val, $textbox=false) {

	global $xoopsDB;
      array($formids);
      array($formnames);
      array($totalcaptionlist);
      array($totalvaluelist);
      $captionlistindex = 0;

      $formlist = "SELECT id_form, desc_form FROM " . $xoopsDB->prefix("formulize_id") . " ORDER BY desc_form";
      $resformlist = mysql_query($formlist);
      if($resformlist)
      {
      	while ($rowformlist = mysql_fetch_row($resformlist)) // loop through each form
      	{
      		$fieldnames = "SELECT ele_caption, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=$rowformlist[0] ORDER BY ele_order";
      		$resfieldnames = mysql_query($fieldnames);
      		
      		while ($rowfieldnames = mysql_fetch_row($resfieldnames)) // loop through each caption in the current form
      		{

      			$totalcaptionlist[$captionlistindex] = printSmart(trans($rowformlist[1])) . ": " . printSmart(trans($rowfieldnames[0]), 50);  // write formname: caption to the master array that will be passed to the select box.
// with xlanguage, translated captions are getting garbled so we have to rely on the ele_id exclusively
//				if($textbox) {
					$totalvaluelist[$captionlistindex] = $rowfieldnames[1];
//				} else {
//	      			$totalvaluelist[$captionlistindex] = $rowformlist[0] . "#*=:*" . $rowfieldnames[0];
//				}

      			if($val == $totalvaluelist[$captionlistindex] OR $val == $rowformlist[0] . "#*=:*" . $rowfieldnames[0]) // if this is the selected entry...
      			{
      				$defaultlinkselection = $captionlistindex;
      			}
      			$captionlistindex++;
      		}
      	}
      }

	if($textbox) {
		$am_ele_formlink = _AM_ELE_FORMLINK_TEXTBOX;
		$am_formlink_none = _AM_FORMLINK_NONE_TEXTBOX;
		$am_ele_formlink_desc = _AM_ELE_FORMLINK_DESC_TEXTBOX;
	} else {
		$am_ele_formlink = _AM_ELE_FORMLINK;
		$am_formlink_none = _AM_FORMLINK_NONE;
		$am_ele_formlink_desc = _AM_ELE_FORMLINK_DESC;
	}

	// make the select box and add all the options... -- jwe 7/29/04
	$formlink = new XoopsFormSelect($am_ele_formlink, 'formlink', '' , 1, false);
	$formlink->addOption("none", $am_formlink_none);
	for($i=0;$i<$captionlistindex;$i++)
	{
		$formlink->addOption($totalvaluelist[$i], $totalcaptionlist[$i]);
	}
	if($defaultlinkselection)
	{
		$formlink->setValue($totalvaluelist[$defaultlinkselection]);
	}
	$formlink->setDescription($am_ele_formlink_desc);
	
	return $formlink;

} 

// THIS FUNCTION TAKES AN ELEMENT OBJECT, AND A VALUE AND SEARCHES IN THE ELEMENT'S FORM FOR THE FIRST ID_REQ THAT MATCHES THE VALUE
// Used by the new textbox link option to find a matching entry, so that it can be linked in the list of entries screen.
// Matches must be exact!  
// Returns the id_req that matches the value, or false if nothing found
function findMatchingIdReq($element, $fid, $value) {

	if(!is_object($element)) { return false; }

	global $xoopsDB, $myts;
	// get the caption so we can do the formulize_form caption formatting switch!
	$caption = $element->getVar('ele_caption');
	$ffcaption = str_replace("'", "`", $caption);
	$ffcaption = str_replace("&#039;", "`", $ffcaption);
	$ffcaption = str_replace("&quot;", "`", $ffcaption);
	$value = $myts->htmlSpecialChars($value); // data is stored in DB after htmlSpecialChars conversion 
	$sql = "SELECT id_req FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_form=$fid AND ele_caption='$ffcaption' AND ele_value='" . mysql_real_escape_string($value) . "' ORDER BY id_req LIMIT 0,1";
	$result = $xoopsDB->query($sql);
	$array = $xoopsDB->fetchArray($result);
	if(count($array) > 0) {
		return $array['id_req'];
	} else {
		return false;
	}
	
}

// THIS FUNCTION OUTPUTS THE TEXT THAT GOES ON THE SCREEN IN THE LIST OF ENTRIES TABLE
// It intelligently outputs links if the text should be a link (because of textbox associations, or linked selectboxes)
// $handle-id is the handle for frameworks or the element id if there is no frid
function checkForLink($matchtext, $handleid, $frid) {
	global $xoopsDB, $myts;
	$matchtext = $myts->undoHtmlSpecialChars($matchtext);
	if($handleid == "uid" OR $handleid=="proxyid" OR $handleid=="creation_date" OR $handleid == "mod_date" OR $handleid == "creator_email") { return printSmart(trans($myts->htmlSpecialChars($matchtext))); }
	include_once XOOPS_ROOT_PATH . "/modules/formulize/class/frameworks.php";
	if(is_numeric($frid) and $frid!=0) {
		$framework = new formulizeFramework($frid);
		$element_ids = $framework->getVar('element_ids');
		$element_id = $element_ids[$handleid]; 
	} else {
		$element_id = $handleid;
	}
	// based on that element_id, we need to get the target element id where we should be doing the checking
	$formulize_mgr =& xoops_getmodulehandler('elements', 'formulize');
	$element =& $formulize_mgr->get($element_id);
	if(!is_object($element)) { return printSmart(trans($myts->htmlSpecialChars($matchtext))); }
	$ele_value = $element->getVar('ele_value');
	$ele_type = $element->getVar('ele_type');
	if(!isset($ele_value[4])) { $ele_value[4] = 0; }
	if(!isset($ele_value[3])) { $ele_value[3] = 0; }
	if(($ele_value[4] > 0 AND $ele_type=='text') OR ($ele_value[3] > 0 AND $ele_type=='textarea')) { // dealing with a textbox where an associated element has been set
		if($ele_type == 'text') {
			$target_element =& $formulize_mgr->get($ele_value[4]);
		} else {
			$target_element =& $formulize_mgr->get($ele_value[3]);
		}
		$target_fid = $target_element->getVar('id_form');
		// if user has no perm in target fid, then do not make link!
		if(!$target_allowed = security_check($target_fid)) {
			return printSmart(trans($myts->htmlSpecialChars($matchtext)));
		}
		$matchtexts = explode(";", $matchtext); // have to breakup the textbox's text since it may contain multiple matches.  Note no space after semicolon spliter, but we trim the results in the foreach loop below.
		$printText = "";
		$start = 1;
		foreach($matchtexts as $thistext) {
			$thistext = trim($thistext);
			if(!$start) { $printText .= ", "; }
			if($id_req = findMatchingIdReq($target_element, $target_fid, $thistext)) {
				$printText .= "<a href='" . XOOPS_URL . "/modules/formulize/index.php?fid=$target_fid&ve=$id_req' target='_blank'>" . printSmart(trans($myts->htmlSpecialChars($thistext))) . "</a>";
			} else {
				$printText .= $myts->htmlSpecialChars($thistext);
			}
			$start = 0;
		}
		return $printText;
	} elseif($ele_type=='select' AND strstr($ele_value[2], "#*=:*")) { // dealing with a linked selectbox
		$boxproperties = explode("#*=:*", $ele_value[2]);
		// NOTE:
		// boxproperties[0] is form_id
		// [1] is caption of linked field (NOT formulize_form format)
		$element_id_q = q("SELECT ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form='" . $boxproperties[0] . "' AND ele_caption='" . mysql_real_escape_string($boxproperties[1]) . "' LIMIT 0,1"); // should only be one match anyway, so limit 0,1 ought to be unnecessary
		$target_fid = $boxproperties[0];
		// if user has no perm in target fid, then do not make link!
		if(!$target_allowed = security_check($target_fid)) {
			return printSmart(trans($myts->htmlSpecialChars($matchtext)));
		}
		$target_element =& $formulize_mgr->get($element_id_q[0]['ele_id']);
		if($id_req = findMatchingIdReq($target_element, $target_fid, $matchtext)) {
			return "<a href='" . XOOPS_URL . "/modules/formulize/index.php?fid=$target_fid&ve=$id_req' target='_blank'>" . printSmart(trans($myts->htmlSpecialChars($matchtext))) . "</a>";
		} else { // no id_req found
			return printSmart(trans($myts->htmlSpecialChars($matchtext)));
		}
	} elseif($ele_type =='select' AND (key($ele_value[2]) == "{USERNAMES}" OR key($ele_value[2]) == "{FULLNAMES}")) {
		$nametype = key($ele_value[2]) == "{USERNAMES}" ? "uname" : "name";
		$uids = q("SELECT uid FROM " . $xoopsDB->prefix("users") . " WHERE $nametype = '" . mysql_real_escape_string($myts->htmlSpecialChars($matchtext)) . "'");
		if(count($uids) == 1) {
			return "<a href='" . XOOPS_URL . "/userinfo.php?uid=" . $uids[0]['uid'] . "' target=_blank>" . printSmart(trans($myts->htmlSpecialChars($matchtext))) . "</a>";
		} else {
			return printSmart(trans($myts->htmlSpecialChars($matchtext)));
		}
	} else { // regular element
		return printSmart(trans($myts->htmlSpecialChars($matchtext)));
	}
} 

// THIS FUNCTION INTERPRETS A TEXTBOX'S DEFAULT VALUE AND RETURNS THE CORRECT STRING
// Takes $ele_value[2] as the input (third position in ele_value array from element object)
function getTextboxDefault($ele_value) {

	global $xoopsUser;

	if(strstr($ele_value, "\$default")) { // php default value
		eval(stripslashes($ele_value));
		$ele_value = $default;
	}

	$ele_value = preg_replace('/\{DATE\}/', date("Y-m-d"), $ele_value);
	if (ereg_replace("[^A-Z{}]","", $ele_value) == "{TODAY}") {
		$number = ereg_replace("[^0-9+-]","", $ele_value);
		$ele_value = date("Y-m-d",mktime(0, 0, 0, date("m") , date("d")+$number, date("Y")));
	}

	if( !is_object($xoopsUser) ){
		$ele_value = preg_replace('/\{NAME\}/', '', $ele_value);
		$ele_value = preg_replace('/\{name\}/', '', $ele_value);
		$ele_value = preg_replace('/\{UNAME\}/', '', $ele_value);
		$ele_value = preg_replace('/\{uname\}/', '', $ele_value);
		$ele_value = preg_replace('/\{EMAIL\}/', '', $ele_value);
		$ele_value = preg_replace('/\{email\}/', '', $ele_value);
		$ele_value = preg_replace('/\{MAIL\}/', '', $ele_value);
		$ele_value = preg_replace('/\{mail\}/', '', $ele_value);
	}else{
		$ele_value = preg_replace('/\{NAME\}/', $xoopsUser->getVar('name', 'e'), $ele_value); // modified to call real name 9/16/04 by jwe
		$ele_value = preg_replace('/\{name\}/', $xoopsUser->getVar('name', 'e'), $ele_value); // modified to call real name 9/16/04 by jwe
		$ele_value = preg_replace('/\{UNAME\}/', $xoopsUser->getVar('uname', 'e'), $ele_value);
		$ele_value = preg_replace('/\{uname\}/', $xoopsUser->getVar('uname', 'e'), $ele_value);
		$ele_value = preg_replace('/\{MAIL\}/', $xoopsUser->getVar('email', 'e'), $ele_value);
		$ele_value = preg_replace('/\{mail\}/', $xoopsUser->getVar('email', 'e'), $ele_value);
		$ele_value = preg_replace('/\{EMAIL\}/', $xoopsUser->getVar('email', 'e'), $ele_value);
		$ele_value = preg_replace('/\{email\}/', $xoopsUser->getVar('email', 'e'), $ele_value);
	}

	return $ele_value;
}


// this function returns the entry ids of entries in one form that are linked to another
// no scope control here...need to add that in, or add it in to the extraction layer
// IMPORTANT:  assume $startEntry is valid for the user(security check has already been executed by now)
// therefore just need to know the allowable uids (scope) in the $targetForm
function findLinkedEntries($startForm, $targetForm, $startEntry, $gperm_handler, $owner_groups, $mid, $member_handler, $owner) {


	// set scope filter -- may need to pass in some exceptions here in the case of viewing entries that are covered by reports?
	// DEPRECATED: scope based on the owner's scope within the subform, since that is the entries that the owner would see, the entries that belong to this entry, within the subform
	// Scope now based on user's permission level, so they can see what they should see, regardless of the owner's permission
	global $xoopsUser;
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
	if($global_scope = $gperm_handler->checkRight("view_globalscope", $targetForm['fid'], $groups, $mid)) {
		$scope_filter = "";
	} elseif($group_scope = $gperm_handler->checkRight("view_groupscope", $targetForm['fid'], $groups, $mid)) {
		$groupsWithAccess = $gperm_handler->getGroupIds("view_form", $targetForm['fid'], $mid);
		$all_users = array();
		foreach($groups as $grp) {
			if(in_array($grp, $groupsWithAccess)) { // include only owner_groups that have view_form permission (so exclude groups the owner is a member of which aren't able to view the form)
				if($grp != XOOPS_GROUP_USERS) { // exclude registered users group since that's everyone!
					$users = $member_handler->getUsersByGroup($grp);
					$all_users = array_merge($users, $all_users);
					unset($users);
				}
			}
		}
		$uq = makeUidFilter($all_users);
		$scope_filter = "AND ($uq)";
	} else {
		$scope_filter = "AND uid=$owner";
	} 

	global $xoopsDB;
	//targetForm is a special array containing the keys as specified in the framework, and the target form
	//keys:  fid, keyself, keyother

	//keyself and other are the ele_id from the form table for the elements that need to be matched.  Must get captions and convert to formulize_form format in order to find the matching values

	//print_r($targetForm);
	//print "<br>$startForm<br>$startEntry<br>";
	

	if($targetForm['keyself'] == 0) { // linking based on uid, in the case of one to one forms, assumption is that these forms are both single_entry forms (otherwise linking one_to_one based on uid doesn't make any sense)
		// get uid of first entry
		// look for that uid in the target form
		$uid_q = q("SELECT uid FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_form = $startForm AND id_req = $startEntry GROUP BY uid");
		// Question? is the error condition below valid?  Might you not have one to one linking in a multi form, in which case multiple uids returned is okay?
		if(count($uid_q)>1) { exit("Error: more than one user id found for a single entry while trying to display a form"); }		
		$entries_q = q("SELECT id_req FROM " . $xoopsDB->prefix("formulize_form") . " WHERE uid = " . $uid_q[0]['uid'] . " AND id_form = " . $targetForm['fid'] . " $scope_filter GROUP BY id_req ORDER BY id_req DESC"); 
		if($entries_q[0]['id_req']) {
			foreach($entries_q as $entry) {
				$entries_to_return[] = $entry['id_req'];
			}
		} else {
			$entries_to_return = "";
		}
		return $entries_to_return;
	// support for true shared values added September 4 2006
	} elseif($targetForm['common']) {
		// return id_reqs from $targetForm['fid'] where the value of the matching element is the same as in the startEntry, startForm
		// get captions first...
		$othercaption = q("SELECT ele_caption FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id = '" . $targetForm['keyother']."'"); 
		$othercaption = str_replace ("&#039;", "`", $othercaption[0]['ele_caption']);
		$othercaption = str_replace ("&quot;", "`", $othercaption);
		$othercaption = str_replace ("'", "`", $othercaption);
		$selfcaption = q("SELECT ele_caption FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id = '" . $targetForm['keyself']."'"); 
		$selfcaption = str_replace ("&#039;", "`", $selfcaption[0]['ele_caption']);
		$selfcaption = str_replace ("&quot;", "`", $selfcaption);
		$selfcaption = str_replace ("'", "`", $selfcaption);
		$scope_filter = str_replace ("uid", "t1.uid", $scope_filter);
		// based on exact matches in the database at the moment, which works for textboxes and possibly others, but won't work for linked selectboxes and may not work in other situations; reformatting of data from two separate queries and then a comparison will be necessary to cover all cases
		//print "SELECT t1.id_req FROM " . $xoopsDB->prefix("formulize_form") . " AS t1, " . $xoopsDB->prefix("formulize_form") . " AS t2 WHERE t1.id_form=" . intval($targetForm['fid']) . " $scope_filter AND t1.ele_caption='$selfcaption' AND t1.ele_value=t2.ele_value AND t2.ele_caption='$othercaption' AND t2.id_form=" . intval($startForm) . " AND t2.id_req = " .intval($startEntry);
		$entries_q = q("SELECT t1.id_req FROM " . $xoopsDB->prefix("formulize_form") . " AS t1, " . $xoopsDB->prefix("formulize_form") . " AS t2 WHERE t1.id_form=" . intval($targetForm['fid']) . " $scope_filter AND t1.ele_caption='$selfcaption' AND t1.ele_value=t2.ele_value AND t2.ele_caption='$othercaption' AND t2.id_form=" . intval($startForm) . " AND t2.id_req = " .intval($startEntry) . " ORDER BY t1.id_req DESC");
		if($entries_q[0]['id_req']) {
			foreach($entries_q as $entry) {
				$entries_to_return[] = $entry['id_req'];
			}
		} else {
			$entries_to_return = "";
		}
		return $entries_to_return;
	// else we're looking at a classic "shared value" which is really a linked selectbox
	} else { // linking based on a shared value.  in the case of one to one forms assumption is that the shared value does not appear more than once in either form's field (otherwise this will be a defacto one to many link)
		//get value at startEntry, for the keyother caption
		//look for that value in the target form's keyself

		$caption = q("SELECT ele_caption FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id = '" . $targetForm['keyother']."'"); 
		$ffcaption = str_replace ("&#039;", "`", $caption[0]['ele_caption']);
		$ffcaption = str_replace ("&quot;", "`", $ffcaption);
		$ffcaption = str_replace ("'", "`", $ffcaption);

		$sourceValue = q("SELECT ele_id, ele_value FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req = '$startEntry' AND ele_caption = '$ffcaption' AND id_form = '$startForm'");				

		$caption2 = q("SELECT ele_caption FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id = '" . $targetForm['keyself']."'"); 
		$ffcaption = str_replace ("&#039;", "`", $caption2[0]['ele_caption']);
		$ffcaption = str_replace ("&quot;", "`", $ffcaption);
		$ffcaption = str_replace ("'", "`", $ffcaption);

		// check to see if we found a linked value
		// if so, then prepare to look for the ele_id of the other
		// if not, then get the ele_id and we'll look for that in the value of the other
		if(strstr($sourceValue[0]['ele_value'], "#*=:*")) {
			// get the ele_id from the link
			$parts = explode("#*=:*", $sourceValue[0]['ele_value']);
			if(strstr($parts[2], "[=*9*:")) { exit("Error: subform entry found with more than one value linked to parent form"); }
			$targetValue = q("SELECT id_req FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_form = '" . $targetForm['fid'] . "' AND ele_caption = '$ffcaption' AND ele_id = '" . $parts[2] . "' $scope_filter GROUP BY id_req ORDER BY id_req DESC");				
			if($targetValue[0]['id_req']) {
				$entries_to_return[0] = $targetValue[0]['id_req'];
			} else {
				$entries_to_return[0] = "";
			}
			return $entries_to_return;						
		} else { // look for the ele_id in the value -- can't imagine this will ever be used, since it is a reverse direction for the linking, ie: the many form is being used as the main, and the subform is the "one form" in the one to many relationship.  This seems to only occur if the relationship of the forms is mis-specified.
			// what should happen here is that the behaviour of the add one/add another, etc UI is different
			// instead of add buttons, you simply have a link to the one entry that corresponds to what the user has selected in the linked select box. 
			// except the problem with that is we then have to change the link on the fly without reloading the page every time the selection in the linked selectbox is altered by the user
			// this will hopefully never have to happen!
			$targetValue = q("SELECT id_req FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_form = '" . $targetForm['fid'] . "' AND ele_caption = '$ffcaption' AND ele_value LIKE '%#*=:*" . $sourceValue[0]['ele_id'] . "%' $scope_filter GROUP BY id_req ORDER BY id_req DESC");
			if($targetValue[0]['id_req']) {
				foreach($targetValue as $tv) {
					$entries_to_return[] = $tv['id_req']; 
				}
			} else {
				$entries_to_return[0] = "";
			}
			return $entries_to_return;
		}
	}
}


// this function takes an entry and makes copies of it
// can take an entry in a framework and make copies of all relevant entries in all relevant forms
// note that the same relative linked selectbox relationships are preserved in cloned framework entries, but links based on common values and uids are not modified at all
// entries in single-entry forms are never cloned
function cloneEntry($entry, $frid, $fid, $copies) {

	global $xoopsDB, $xoopsUser;
	include_once XOOPS_ROOT_PATH . "/modules/formulize/class/forms.php";
	$replaceit = array('flag'=>false, 'key'=>0, 'ele_id'=>0);
	$lsbpair = array();
	if($frid) {
		include_once XOOPS_ROOT_PATH . "/modules/formulize/class/frameworks.php";
		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
		$thisframe = new formulizeFramework($frid);
		$links = $thisframe->getVar('links');
		// get the element ids of the elements that are linked selectboxes pointing to another form
		$lsbindexer = 0;
		foreach($links as $link) {

			if(!$link->getVar('common') AND $link->getVar('key1')) { // not a common value link, and not a uid link (key is 0 for uid links)
				$cap1 = getCaptionFFbyId($link->getVar('key1'), $frid, $link->getVar('form1')); // key1 and 2 are the element ids of the elements in the link
				$cap2 = getCaptionFFbyId($link->getVar('key2'), $frid, $link->getVar('form2'));
				$lsbpair[$cap1] = $cap2; 
				$lsbpair[$cap2] = $cap1;
				$lsbindexer++;
			}
		}
		$entries_query = getData($frid, $fid, $entry);
		$ids = internalRecordIds($entries_query[0], "", "", true); // true causes the first key of the returned array to be the fids
		foreach($ids as $fid=>$entryids) {
			foreach($entryids as $id) {
				$entries_to_clone[$fid][] = $id;
			}
		}
	} else {
		$entries_to_clone[$fid][] = $entry;
	}

	// lock formulize_form
	$xoopsDB->query("LOCK TABLES " . $xoopsDB->prefix("formulize_form") . " WRITE, " . $xoopsDB->prefix("formulize_id") . " READ, " . $xoopsDB->prefix("formulize") . " READ");    

	for($copy_counter = 0; $copy_counter<$copies; $copy_counter++) {

	foreach($entries_to_clone as $fid=>$entries) {

		// never clone an entry in a form that is a single-entry form
		$thisform = new formulizeForm($fid);
		if($thisform->getVar('single') != "off") { continue; }
		foreach($entries as $thisentry) {

       		// 1. get this entry from formulize_form
       		// 2. get maxidreq
       		// 3. write each element back to DB
       		// 4. check if this element is part of a linked pair
       		// 5. if it's not the selectbox side, check to see if this ele_id is wanted, and if so, apply it.  Otherwise, store the ele_id for later use
       		// 6. if it is the selectbox side, see if we have the ele_id and use it.  Otherwise, flag this ele_id as required.

       		$entryValues = q("SELECT * FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req='$thisentry'");
       		$maxIdReq = getMaxIdReq();
       		foreach($entryValues as $row=>$record) {
       			$sql = "INSERT INTO " . $xoopsDB->prefix("formulize_form") . " (";
       			$start = 1;
       			foreach($record as $thisfield=>$value) {
       		            // Handle the commas necessary between fields
       		            if(!$start) { $sql .= ", "; }
       	      	      $start = 0;
       	            	$sql .= "`$thisfield`";
       			} 
       			$sql .= ") VALUES (";
       	       	$start = 1;
				
       			foreach($record as $thisfield=>$value) {
       	            	// this is the key part that changes the id of the form to the new form that was just made
       				if($thisfield == "id_req") { $value = $maxIdReq; }
       				if($thisfield == "ele_id") { $value = ""; }
					if($thisfield == "proxyid") { $value = $xoopsUser->getVar('uid'); }
					if($thisfield == "ele_value" AND isset($lsbpair[$record['ele_caption']])) { // if this is part of a linked selectbox pairing
						if(strstr($value, "#*=:*")) { // it's the selectbox side, so figure out what the wanted id(s) are
							$boxproperties = explode("#*=:*", $value);
//print_r($boxproperties);
//print "<br>";
							$selectedvalues = explode("[=*9*:", $boxproperties[2]);
//print_r($selectedvalues);
//print "<br>";
							foreach($selectedvalues as $key=>$wantedid) {
//print "Looking for $wantedid<br>";
								if(isset($availableIds[$wantedid])) { // the necessary element was already written to the DB, so we'll use that value
//print "We think it's available and set at " . $availableIds[$wantedid] . "<br>";
									$selectedvalues[$key] = $availableIds[$wantedid];
//print_r($selectedvalues);
//print "<br>";
								} else {
									$wantedIds[$wantedid] = array(0=>$key, 1=>$record['ele_id']); // flag this value for replacement 
									$replacementIds[$record['ele_id']] = ""; // to be updated below once this record has been written to the DB
//print "Not available, so marking it as wanted<br>";
								}
							}
//print_r($selectedvalues);
//print "<br>";
							$eids = implode("[=*9*:", $selectedvalues);
							unset($boxproperties[2]);
//print_r($boxproperties);
//print "<br>";
							$value = implode("#*=:*", $boxproperties) . "#*=:*$eids";
//print $value ."<br>";
						} else { // it's not the selectbox side
							if(isset($wantedIds[$record['ele_id']])) { // this element's ele_id needs to be applied to a selectbox element that was already written
//print $record['ele_id'] . " is wanted, so flagging it for replacement<br>";				
								$replaceit['flag'] = true;
								$replaceit['key'] = $wantedIds[$record['ele_id']][0];
								$replaceit['ele_id'] = $replacementIds[$wantedIds[$record['ele_id']][1]];
							} else {
//print "setting avail " . $record['ele_id'] . "<br>";
								$availableIds[$record['ele_id']] = ""; // to be updated below once this record has been written to the DB
							}
							$value .= " " . $copy_counter+1;
						}
					}
       				// Handle the commas necessary between fields
       				if(!$start) { $sql .= ", "; }
       				$start = 0;
       				$value = mysql_real_escape_string($value);
       				$sql .= "\"$value\"";
       			}
       			$sql .= ")";
       			//echo $sql . "<br>";
                   
       			if(!$datares = $xoopsDB->query($sql)) {
       				exit("Error cloning data for entry: $thisentry.  SQL statement that caused the error:<br>$sql<br>");
       			}

				if(isset($availableIds[$record['ele_id']])) { 
					$availableIds[$record['ele_id']] = $xoopsDB->getInsertId(); 
//print "avail " . $record['ele_id'] . " now set to: " . $availableIds[$record['ele_id']] . "<br>"; 
				}
				if(isset($replacementIds[$record['ele_id']])) { 
					$replacementIds[$record['ele_id']] = $xoopsDB->getInsertId(); 
//print "replacement for " . $record['ele_id'] . " now set to: " . $replacementIds[$record['ele_id']] . "<br>"; 
				}
				if($replaceit['flag']) {
//print "SELECT ele_value FROM " . $xoopsDB->query("formulize_form") . " WHERE ele_id=" . $replaceit['ele_id'] . "<br>";
					$bpq = q("SELECT ele_value FROM " . $xoopsDB->prefix("formulize_form") . " WHERE ele_id=" . $replaceit['ele_id']);
//print_r($bpq);
//print "<Br>";
					$boxproperties = explode("#*=:*",$bpq[0]['ele_value']);
//print_r($replaceit);
//print "<br>";
//print_r($boxproperties);
//print "<br>";
					$selectedvalues = explode("[=*9*:", $boxproperties[2]);
					$selectedvalues[$replaceit['key']] = $xoopsDB->getInsertId();
					$eids = implode("[=*9*:", $selectedvalues);
					unset($boxproperties[2]);
					$sbvalue = implode("#*=:*", $boxproperties) . "#*=:*$eids";;
//print $sbvalue . "<br>";
					$box_record_write = "UPDATE " . $xoopsDB->prefix("formulize_form") . " SET ele_value=\"" . mysql_real_escape_string($sbvalue) . "\" WHERE ele_id=".$replaceit['ele_id'];
	       			if(!$datares = $xoopsDB->query($box_record_write)) {
	       				exit("Error cloning selectbox data for entry: $thisentry.  SQL statement that caused the error:<br>$box_record_write<br>");
      	 			}
					$replaceit = array('flag'=>false, 'key'=>0, 'ele_id'=>0);
				}
			}
		} // end of loop through all entries that need copying
		} // end of copy counter
	}

	// unlock tables
	$xoopsDB->query("UNLOCK TABLES");

}

// THIS FUNCTION HANDLES SENDING OF NOTIFICATIONS
// Does some unconventional stuff to handle custom templates for messages, and sending to everyone in a group, or to the current user (like a confirmation message)
function sendNotifications($fid, $event, $entries, $mid="", $groups=array()) {

	// 1. Get all conditions attached to this fid for this event
	// 1b. determine what users have view_globalscope on the form, and what groups that the current user is a member of have view_groupscope on the form
	// 2. foreach entry, do the following
	// 4. foreach condition, do the following
	// 5. if there's actual terms attached to the condition, see if the entry matches the condition, and if not, move on to the next condition
	// 6. if there's a custom template or subject, then save that condition for later processing
	// 7. check the uid, curuser and groupid for this condition and store it
	// 8. after processing each condition...
 	// 9. set the intersection of the view_group/global users and the users in the conditions
	// 10. determine which users are not subscribed to this event
	// 11. subscribe the necessary users with a oncethendelete notification mode
	// 12. trigger this notification event
	// 13. foreach custom template and/or subject, do this
	// 14. determine the uid, curuser, groupid settings and gather the uids
	// 15. set the intersection of the users
	// 16. change the modinfo for this event so the custom template/subject is used
	// 17. determine the users subscribed and subscribe the necessary others with a oncethendelete mode
	// 18. trigger the notification

	global $xoopsDB, $xoopsUser, $xoopsConfig;

	$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;

	// 1.  get all conditions for this fid and event
	$cons = q("SELECT * FROM " . $xoopsDB->prefix("formulize_notification_conditions") . " WHERE not_cons_fid=".intval($fid)." AND not_cons_event=\"".mysql_real_escape_string($event)."\"");
	if(count($cons) == 0) { return; }

	if(!$mid) {
		$mid = getFormulizeModId();
	}

	if(count($groups) == 0) {
		$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
	}

	// 1b. get the complete list of all possible users to notify
	$gperm_handler =& xoops_gethandler('groupperm');
	$groups_global = $gperm_handler->getGroupIds("view_globalscope", $fid, $mid);
	$groups_group = $gperm_handler->getGroupIds("view_groupscope", $fid, $mid);
	$groups_group = array_intersect($groups, $groups_group);
	$groups_complete = array_unique(array_merge($groups_group, $groups_global));
	$member_handler =& xoops_gethandler('member');
	$uids_complete = array();
	foreach($groups_complete as $gid) {
		$uids = $member_handler->getUsersByGroup($gid);
		$uids_complete = array_merge($uids, $uids_complete);
		unset($uids);
	}
	$uids_complete = array_unique($uids_complete);

	$notification_handler =& xoops_gethandler('notification');

	// start main loop
	foreach($entries as $entry) {
		$uids_conditions = array();
		$saved_conditions = array();
		foreach($cons as $thiscon) {
			if($thiscon['not_cons_con'] !== "all") { // there is a specific condition for this notification
				$thesecons = unserialize($thiscon['not_cons_con']);
				$elements = unserialize($thesecons[0]);
				$ops = unserialize($thesecons[1]);
				$terms = unserialize($thesecons[2]);
				$start = 1;
				for($i=0;$i<count($elements);$i++) {
					if($ops[$i] == "NOT") { $ops[$i] = "!="; }
					if($start) {
						$filter = $entry."][".$elements[$i]."/**/".$terms[$i]."/**/".$ops[$i];
						$start = 0;
					} else {
						$filter .= "][".$elements[$i]."/**/".$terms[$i]."/**/".$ops[$i];
					}
				}
				include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
				$data = getData("", $fid, $filter);
				if($data[0] == "") { continue; }
			}
			// condition passed the test, so check for custom template or subject
			if($thiscon['not_cons_template'] OR $thiscon['not_cons_subject']) {
				$saved_conditions[] = $thiscon;
				continue; // proceed to the next one
			}
			// passed the test and not custom, so save the uid, curuser, groupid info
			list($uids_conditions, $omit_user) = compileNotUsers($uids_conditions, $thiscon, $uid, $member_handler);
		} // end of each condition

		// intersect all possible uids with the ones valid for this condition, and handle subscribing necessary users
		$uids_real = compileNotUsers2($uids_conditions, $uids_complete, $notification_handler, $fid, $event, $mid);

		// get form object so the title can be used in notification messages
		static $formObjs = array(); // make this static so we don't have to hit the database over again if we've already got this form object
		include_once XOOPS_ROOT_PATH  . "/modules/formulize/class/forms.php";
		if(!isset($formObjs[$fid])) {
			$formObjs[$fid] = new formulizeForm($fid);
		}
		$extra_tags = array();
		$extra_tags['ENTRYUSERNAME'] = $xoopsUser->getVar('name') ? $xoopsUser->getVar('name') : $xoopsUser->getVar('uname');
		$extra_tags['FORMNAME'] = $formObjs[$fid]->getVar('title');
		$extra_tags['VIEWURL'] = XOOPS_URL."/modules/formulize/index.php?fid=$fid&ve=$entry";
		$extra_tags['ENTRYID'] = $entry;			

		if(count($uids_real) > 0) {
			$notification_handler->triggerEvent("form", $fid, $event, $extra_tags, $uids_real, $mid, $omit_user);
		}
		unset($uids_real);

		// handle custom conditions
		foreach($saved_conditions as $thiscon) {
			if($thiscon['not_cons_template'] AND !file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/mail_template/".$thiscon['not_cons_template'].".tpl")) { continue; }
			$uids_cust_con = array();
			list($uids_cust_con, $omit_user) = compileNotUsers($uids_cust_con, $thiscon, $uid, $member_handler, true);
			$uids_cust_real = compileNotUsers2($uids_cust_con, $uids_complete, $notification_handler, $fid, $event, $mid);			
			// set the custom template and subject
	            $module_handler =& xoops_gethandler('module');
      	      $module =& $module_handler->get($mid);
			$not_config =& $module->getInfo('notification');
			switch($event) {
				case "new_entry":
					$evid = 1;
					break;
				case "update_entry":
					$evid = 2;
					break;
				case "delete_entry":
					$evid = 3;
					break;
			}
			$oldsubject = $not_config['event'][$evid]['mail_subject'];
			$oldtemp = $not_config['event'][$evid]['mail_template'];
			$not_config['event'][$evid]['mail_template'] = $thiscon['not_cons_template'] == "" ? $not_config['event'][$evid]['mail_template'] : $thiscon['not_cons_template'];
			$not_config['event'][$evid]['mail_subject'] = $thiscon['not_cons_subject'] == "" ? $not_config['event'][$evid]['mail_subject'] : $thiscon['not_cons_subject'];
			// trigger the event
			if(count($uids_cust_real) > 0) {
				$notification_handler->triggerEvent("form", $fid, $event, $extra_tags, $uids_cust_real, $mid, $omit_user);
			}
			$not_config['event'][$evid]['mail_subject'] = $oldsubject;
			$not_config['event'][$evid]['mail_template'] = $oldtemp;
			unset($uids_cust_real);
			unset($uids_cust_con);
		}

		unset($uids_conditions);
		unset($saved_conditions);
	} // end of each entry
}

function compileNotUsers($uids_conditions, $thiscon, $uid, $member_handler, $reinitialize=false) {
	static $omit_user = null;
	if($reinitialize) { $omit_user = null; } // need to do this when handling saved conditions, since each time we call this function it's a new "event" that we're dealing with
	if($thiscon['not_cons_uid'] > 0) {
		$uids_conditions[] = $thiscon['not_cons_uid'];
	} elseif($thiscon['not_cons_curuser'] > 0) {
		$uids_conditions[] = $uid;
		$omit_user = 0;
	} elseif($thiscon['not_cons_groupid'] > 0) {
		$uids_temp = $member_handler->getUsersByGroup($thiscon['not_cons_groupid']);				
		$uids_conditions = array_merge($uids_temp, $uids_conditions);
		unset($uids_temp);
	}
	return array(0=>$uids_conditions, 1=>$omit_user);
}

function compileNotUsers2($uids_conditions, $uids_complete, $notification_handler, $fid, $event, $mid) {
	global $xoopsDB;
	$uids_conditions = array_unique($uids_conditions);
	$uids_real = array_intersect($uids_conditions, $uids_complete);
	// figure out who is not subscribed to the event, and subscribe them once
	$subd_uids = q("SELECT not_uid FROM " . $xoopsDB->prefix("xoopsnotifications") . " WHERE not_event=\"".mysql_real_escape_string($event)."\" AND not_category=\"form\" AND not_modid=$mid AND not_itemid=$fid"); 
	$uids_subd = array();
	foreach($subd_uids as $thisuid) {
		$uids_subd[] = $thisuid['not_uid'];
	}		
	$uids_not_subd = array_diff($uids_real, $uids_subd);
	foreach($uids_not_subd as $thisuid) {
		$notification_handler->subscribe("form", $fid, $event, XOOPS_NOTIFICATION_MODE_SENDONCETHENDELETE, $mid, $thisuid);
	}
	return $uids_real;	
}

// THIS PATCH TAKES THE COOKY SYNTAX SUBMITTED BY A LINKED SELECTBOX AND CORRECTS IT IF XLANGUAGE IS INSTALLED
// xlanguage garbles translated captions, which unfortunately form part of the lsb syntax currently
function lsbSubmitPatch($text) {
	if(function_exists('xlanguage_ml')) {
		global $xoopsDB;
		$parts = explode("#*=:*", $text); // part 2 will be the ele_id of the selection in formulize_form
		$sql = "SELECT ele_caption FROM " . $xoopsDB->prefix("formulize_form") . " WHERE ele_id=" . intval($parts[2]);
		$res = $xoopsDB->query($sql);
		$caption = $xoopsDB->fetchRow($res);
		$parts[1] = str_replace("`", "'", $caption[0]);
		$text = implode("#*=:*", $parts);
	}
	return $text;
}

// this function takes a series of columns and gets the headers for them
function getHeaders($cols, $frid="") {
	global $xoopsDB;
	foreach($cols as $col) {
		if($col == "uid") {
			$headers[] = _formulize_DE_CALC_CREATOR;
		} elseif($col == "proxyid") {
			$headers[] = _formulize_DE_CALC_MODIFIER;
		} elseif($col=="creation_date") {
			$headers[] = _formulize_DE_CALC_CREATEDATE;
		} elseif($col=="mod_date") {
			$headers[] = _formulize_DE_CALC_MODDATE;
		} elseif($col=="creator_email") {
			$headers[] = _formulize_DE_CALC_CREATOR_EMAIL;
		} elseif($frid) {
       		$headers[] = getCaption($frid, $col, true);
       	} else {
       		$temp_cap = q("SELECT ele_caption, ele_colhead FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id = '$col'"); 
			if($temp_cap[0]['ele_colhead'] != "") {
				$headers[] = $temp_cap[0]['ele_colhead'];
			} else {
	       		$headers[] = $temp_cap[0]['ele_caption'];
			}
       	}
	}
	return $headers;
}

?>