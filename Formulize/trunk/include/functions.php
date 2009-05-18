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

include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";


// identify form or framework
function getFormFramework($formframe, $mainform="") {
	global $xoopsDB;

	if(!empty($mainform)) { // a framework
		if(!is_numeric($formframe)) {
			$frameid = q("SELECT frame_id FROM " . $xoopsDB->prefix("formulize_frameworks") . " WHERE frame_name='" . mysql_real_escape_string($formframe) . "'");
			$frid = $frameid[0]['frame_id'];
		} else {
			$frid = $formframe;
		}
		if(!is_numeric($mainform)) {
			$formcheck = q("SELECT ff_form_id FROM " . $xoopsDB->prefix("formulize_framework_forms") . " WHERE ff_frame_id='$frid' AND ff_handle='" . mysql_real_escape_string($mainform) . "'");
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
			$formid = q("SELECT id_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE desc_form = '" . mysql_real_escape_string($formframe) . "'");
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
// $requireAllGroups is a 0 or 1, and if it's a 1, then we need to match only users who are members of all the groups specified
// $filter is the specified filters to run on the profile form, if any
function gatherNames($groups, $nametype, $requireAllGroups=false, $filter=false) {
	global $xoopsDB;
	$member_handler =& xoops_gethandler('member');
	$all_users = array();
  $usersByGroup = array();
	foreach($groups as $group) {
		if($group == XOOPS_GROUP_USERS) { continue; }
		$groupusers = $member_handler->getUsersByGroup($group, true);
		if(!$requireAllGroups) {
			$all_users = array_merge((array)$groupusers, $all_users);
		} else {
			$usersByGroup[] = $groupusers;
		}
	}
  if($requireAllGroups) {
    $all_users = $usersByGroup[0]; // need to seed the all users array so there's something to intersect with the first time, otherwise the list will be empty
		foreach($usersByGroup as $theseUsers) {
			$all_users = array_intersect((array)$theseUsers, $all_users);
		}
	}
	array_unique($all_users);
	
  $found_names = array();
  $found_uids = array();
  foreach($all_users as $user) {
		$found_names[$user->getVar('uid')] = $user->getVar($nametype);
    $found_uids[$user->getVar('uid')] = $user->getVar('uid');
	}
	
  // handle any filter that might be specified on the user profile form
  
  // determine which form the "User Profile" is
  $module_handler =& xoops_gethandler('module');
  $config_handler =& xoops_gethandler('config');
  $formulizeModule =& $module_handler->getByDirname("formulize");
	$formulizeConfig =& $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
  $fid = $formulizeConfig['profileForm'];
  
  if(is_array($filter) AND $fid) {
    $filterElements = $filter[0];
    $filterOps = $filter[1];
    $filterTerms = $filter[2];
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
    $start = true;
    for($filterId = 0;$filterId<count($filterElements);$filterId++) {
      if($ops[$i] == "NOT") { $ops[$i] = "!="; }
      if(!$start) {
        $filter .= "][";
      }
      $start = false;
      $filterText .= $filterElements[$filterId]."/**/".$filterTerms[$filterId]."/**/".$filterOps[$filterId];
    }
    $profileData = getData("", $fid, $filterText, "AND", makeUidFilter($found_uids));
    $real_found_names = array();
    foreach($profileData as $thisData) {
      $thisUid = display($thisData, "uid");
      $real_found_names[$thisUid] = $found_names[$thisUid];
    }
    unset($found_names);
    $found_names = $real_found_names;
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
		$all_users = array_merge($all_users, (array)$users);
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
	$url = $url_parts['scheme'] . "://" . $url_parts['host']; 
	$url = isset($url_parts['port']) ? $url . ":" . $url_parts['port'] : $url;
	$url .= str_replace("&amp;", "&", htmlSpecialChars(strip_tags($_SERVER['REQUEST_URI'])));  // strip html tags, convert special chars to htmlchar equivalents, then convert back ampersand htmlchars to regular ampersands, so the URL doesn't bust on certain servers
	return $url;
}

// this function returns a human readable, comma separated list of group names, given a string of comma separated group ids
function groupNameList($list, $obeyMemberOnlyFlag = true) {
	global $xoopsDB;
	$grouplist = explode(",", trim($list, ","));
  if($grouplist[0] == "onlymembergroups") { // first group might be a special key to tell us to limit the selected groups
    unset($grouplist[0]);
    global $xoopsUser;
    $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
  } else {
    $obeyMemberOnlyFlag = false; // no memberonly flag in effect, so we can ignore this operation later on
  }
	$start = 1;
	foreach($grouplist as $gid) {
    if(!$obeyMemberOnlyFlag OR in_array($gid, $groups)) {
      $groupnames = q("SELECT name FROM " . $xoopsDB->prefix("groups") . " WHERE groupid='$gid'");
      if($start) {
      	$names = $groupnames[0]['name'];
      	$start = 0;
      } else {
      	$names .= ", " . $groupnames[0]['name'];
      }
    }
	}
	return $names;
}

// THIS FUNCTION RETURNS THE OWNER OF A GIVEN SAVED VIEW
// only checks based on 2.0 saved view format, not 1.6 or earlier format
function getSavedViewOwner($vid) {
  static $cachedOwners = array();
  $vid = intval($vid);
  if(!isset($cachedOwners[$vid])) {
    global $xoopsDB;
    $sql = "SELECT sv_owner_uid FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_id = $vid";
    $result = $xoopsDB->query($sql);
    $array = $xoopsDB->fetchArray($result);
    $cachedOwners[$vid] = intval($array['sv_owner_uid']) > 0 ? intval($array['sv_owner_uid']) : false; // record "false" if sql failed
  }
  return $cachedOwners[$vid];
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
function security_check($fid, $entry="", $uid="", $owner="", $groups="", $mid="", $gperm_handler="") {

	if($entry == "proxy") { $entry=""; }

	if(!$groups) { // if no groups specified, use current user
		global $xoopsUser;
		$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
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
        $data_handler = new formulizeDataHandler($fid);
				$intersect_groups = array_intersect($data_handler->getEntryOwnerGroups($entry), $groupsWithAccess, $groups);
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
							if(array_intersect($data_handler->getEntryOwnerGroups($entry), $viewgroups)) { return true; }
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
	static $mid = "";
	if(!$mid) {
		$res4 = $xoopsDB->query("SELECT mid FROM ".$xoopsDB->prefix("modules")." WHERE dirname='formulize'");
		if ($res4) {
			while ($row = $xoopsDB->fetchRow($res4))
				$mid = $row[0];
		}
	}
	return $mid;
}
// RETURNS THE RESULTS OF AN SQL STATEMENT -- ADDED April 25/05
// returns a multidimensioned array where the first index is the row of the result and the second index is the field name in that row
// borrowed from the extraction layer, but modified to use the XOOPS DB class
// KEYFIELD IS OPTIONAL, and sets the key of the result array to be one of the fields in the query.  Useful if you want to use isset with a value to determine the presence of something in the result set, instead of searching the array.
function q($query, $keyfield="", $keyfieldOnly = false) {

	global $xoopsDB;
	$result = array();
	//print "$query"; // debug code
	if($res = $xoopsDB->query($query)) {
                while ($array = $xoopsDB->fetchArray($res)) {
			if($keyfield) {
                          if(!$keyfieldOnly) {
				$result[$array[$keyfield]] = $array;
                          } else {
                                $result[] = $array[$keyfield];
                          }
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
		$temp = substr(trans($value), 0, $chars);
		if(strlen(trans($value))>$chars) { $temp .= "...."; }
		$ret = $temp;
	}
	return $ret;
}


// this function returns the headerlist for a form and gracefully degrades to other inputs if the headerlist itself is not specified.
// need ids flag will cause the returned array to be IDs instead of header text
// convertIdsToElementHandles flag will have effect if ids have been returned, and will do one query to get all the element handles that patch the ids selected
// we do not filter the headerlist for private elements, because the columns in entriesdisplay are filtered for private columns (and display columns) after being gathered.
function getHeaderList ($fid, $needids=false, $convertIdsToElementHandles=false) {
	
	global $xoopsDB;

	$headerlist = array();

	$hlq = "SELECT headerlist FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form='$fid'";
	if($result = $xoopsDB->query($hlq)) {
		while ($row = $xoopsDB->fetchRow($result)) {
			$headerlist = explode("*=+*:", $row[0]); 
			array_shift($headerlist);
		}
		// handling for id based headerlists added March 6 2005, by jwe
		if(is_numeric($headerlist[0]) OR $headerlist[0] == "uid" OR $headerlist[0] == "proxyid" OR $headerlist[0] == "creation_date" OR $headerlist[0] == "mod_date" OR $headerlist[0] == "creator_email" OR $headerlist[0] == "creation_uid" OR $headerlist[0] == "mod_uid" OR $headerlist[0] == "creation_datetime" OR $headerlist[0] == "mod_datetime") { // if the headerlist is using the new ID based system
			if(!$needids) { // if we want actual text headers, convert ids to text...
      			$start = 1;
						$metaHeaderlist = array();
      			foreach($headerlist as $headerid=>$thisheaderid) {
					if($thisheaderid == "uid" OR $thisheaderid == "creation_uid") {
						$metaHeaderlist[] = _formulize_DE_CALC_CREATOR;
						unset($headerlist[$headerid]);
						continue; 
					}
					if($thisheaderid == "proxyid" OR $thisheaderid == "mod_uid") {
						$metaHeaderlist[] = _formulize_DE_CALC_MODIFIER;
						unset($headerlist[$headerid]);
						continue; 
					}
					if($thisheaderid == "creation_date" OR $thisheaderid == "creation_datetime") {
						$metaHeaderlist[] = _formulize_DE_CALC_CREATEDATE;
						unset($headerlist[$headerid]);
						continue; 
					}
					if($thisheaderid == "mod_date" OR $thisheaderid == "mod_datetime") {
						$metaHeaderlist[] = _formulize_DE_CALC_MODDATE;
						unset($headerlist[$headerid]);
						continue; 
					}
                                        if($thisheaderid == "creator_email") {
						$metaHeaderlist[] = _formulize_DE_CALC_CREATOR_EMAIL;
						unset($headerlist[$headerid]);
						continue; 
					}
      				if($start) {
      					$where_clause = "ele_id='$thisheaderid'";
      					$start = 0;
      				} else {
      					$where_clause .= " OR ele_id='$thisheaderid'";
      				}
      			}
      			$captionq = "SELECT ele_caption, ele_colhead FROM " . $xoopsDB->prefix("formulize") . " WHERE $where_clause AND (ele_type != \"ib\" AND ele_type != \"areamodif\" AND ele_type != \"subform\" AND ele_type != \"grid\") ORDER BY ele_order";
      			if($rescaptionq = $xoopsDB->query($captionq)) {
      				unset($headerlist);
							$headerlist = $metaHeaderlist;
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
			} else { // if getting ids, need to convert old metadata values to new ones
        foreach($headerlist as $headerListIndex=>$thisheaderid) {
					if($thisheaderid == "uid") {
            $headerlist[$headerListIndex] = "creation_uid";
          } elseif($thisheaderid == "proxyid") {
            $headerlist[$headerListIndex] = "mod_uid";
          } elseif($thisheaderid == "creation_date") {
            $headerlist[$headerListIndex] = "creation_datetime";
          } elseif($thisheaderid == "mod_date") {
            $headerlist[$headerListIndex] = $thisheaderid == "mod_datetime";
          }
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
		$reqfq = "SELECT ele_caption, ele_colhead, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_req=1 AND id_form='$fid' AND (ele_type != \"ib\" AND ele_type != \"areamodif\" AND ele_type != \"subform\" AND ele_type != \"grid\") ORDER BY ele_order ASC LIMIT 3";
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
		$firstfq = "SELECT ele_caption, ele_colhead, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form='$fid' AND (ele_type != \"ib\" AND ele_type != \"areamodif\" AND ele_type != \"subform\" AND ele_type != \"grid\") ORDER BY ele_order ASC LIMIT 3";
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
  if($needids AND $convertIdsToElementHandles) {
    $savedMetaHeaders = array();
    foreach($headerlist as $thisheaderkey=>$thisheaderid) { // remove non numeric headers and save them
      if(!is_numeric($thisheaderid)) {
        $savedMetaHeaders[] = $thisheaderid;
        unset($headerlist[$thisheaderkey]);
      }
    }
    if(count($headerlist)>0) {// if there are any numeric headers, then get the handles
      $sql = "SELECT ele_handle FROM ".$xoopsDB->prefix("formulize") . " WHERE ele_id IN (".implode(",",$headerlist).") ORDER BY ele_order";
      if($res = $xoopsDB->query($sql)) {
        $headerlist = array();
        while($array = $xoopsDB->fetchArray($res)) {
          $headerlist[] = $array['ele_handle'];
        }
        $headerlist = array_merge($savedMetaHeaders, $headerlist); // add the non numeric headers back in to the front
      } else {
        print "Error: could not convert Element IDs to Handles when retrieving the header list.  SQL error: ".mysql_error()."<br>";
      }
    } else { // no numeric headers, so just return the non numeric ones
      $headerlist = $savedMetaHeaders;
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
        $module_id = getFormulizeModId();

	// GET THE FORMS THE USER IS ALLOWED TO VIEW
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
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
function deleteFormEntries($array, $fid) {
	$data_handler = new formulizeDataHandler($fid);
	if(!$deleteResult = $data_handler->deleteEntries($array)) {
	  	exit("Error deleting entries from the database for form $fid");
	}

	// only do the maintenance if the main deletion was successful (otherwise we potentially mangle data for entries that are still around)
	foreach($array as $id_req) {
		deleteMaintenance($id_req, $fid);
	}

	// notifications in this case are handled in the formdisplay.php file where this function is called
}

// THIS FUNCTION REMOVES ENTRIES FROM THE OTHER TABLE BASED ON AN IDREQ
function deleteMaintenance($id_req, $fid) {

	global $xoopsDB;

	
	// remove entries in the formulize_other table
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$formObject = $form_handler->get($fid);
        	
	$sql3 = "DELETE FROM " . $xoopsDB->prefix("formulize_other") . " WHERE id_req='$id_req' AND ele_id IN (" . implode(",", $formObject->getVar('elements')) . ")"; //limit to id_reqs where the element is from the right form, since the new id_reqs (entry_ids) can be repeated across forms
	if(!$result3 = $xoopsDB->query($sql3)) {
		exit("Error: failed to delete 'Other' text for entry $id_req");
	}

}

//THIS FUNCTION ACTUALLY DOES THE DELETING OF A SPECIFIC ID_REQ
function deleteIdReq($id_req, $fid) {

	$data_handler = new formulizeDataHandler($fid);
	if(!$deleteResult = $data_handler->deleteEntries($id_req)) {
	  	exit("Error deleting entries from the database for form $fid");
	}

	deleteMaintenance($id_req, $fid);


}

// THIS FUNCTION DELETES ENTRIES FROM WHEN PASSED AN entry id (ID_REQ)
// HANDLES FRAMEWORKS TOO -- HANDLERS AND MID TO BE PASSED IN WHEN FRAMEWORKS ARE USED
// owner and owner_groups to be passed in when available (if called from a function where they have already been determined
// $fid is required
function deleteEntry($id_req, $frid="", $fid, $gperm_handler="", $member_handler="", $mid="", $owner="", $owner_groups="") {

	global $xoopsDB;
	$deletedEntries = array();

	if($frid) { // if a framework is passed, then delete all sub entry items found in a unified display relationship with the base entry, in addition to the base entry itself.  
		$fids[0] = $fid;
		$entries[$fid][0] = $id_req;
		if(!$owner) { $owner = getEntryOwner($id_req, $fid); }
		if(!$owner_groups) {
      $data_handler = new formulizeDataHandler($fid);
      $owner_groups = $data_handler->getEntryOwnerGroups($id_req);
      //$owner_groups =& $member_handler->getGroupsByUser($owner, FALSE);
    }
		$linkresults = checkForLinks($frid, $fids, $fid, $entries, $gperm_handler, $owner_groups, $mid, $member_handler, $owner);
		foreach($linkresults['entries'] as $thisfid=>$ents) {
			foreach($ents as $ent) {
				if($ent) { 
					deleteIdReq($ent, $thisfid); 
					$deletedEntries[$thisfid][] = $ent;
				}
			}
		}
		foreach($linkresults['sub_entries'] as $thisfid=>$ents) {
			foreach($ents as $ent) {
				if($ent) { 
					deleteIdReq($ent, $thisfid); 
					$deletedEntries[$thisfid][] = $ent;
				}

			}
		}
	} else {
		deleteIdReq($id_req, $fid);
		$deletedEntries[$fid][] = $id_req;
	} // end of if frid

	// do notifications
	foreach($deletedEntries as $thisfid=>$entries) {
		sendNotifications($thisfid, "delete_entry", $entries, $mid); // last param, groups, is missing, notification function will put it in itself
	}


}

// GETS THE ID OF THE USER WHO OWNS AN ENTRY
function getEntryOwner($entry, $fid) {
	static $entryOwners = array();
	$entry = intval($entry);
	if(isset($entryOwners[$entry][$fid])) {
		return $entryOwners[$entry][$fid];
	} else {
    $data_handler = new formulizeDataHandler($fid);
    list($creation_datetime, $mod_datetime, $creation_uid, $mod_uid) = $data_handler->getEntryMeta($entry);
    $entryOwners[$entry][$fid] = $creation_uid;
	}
  return $entryOwners[$entry][$fid];
}

// THIS FUNCTION MAKES A UID= or UID= FILTER FOR AN sql QUERY
function makeUidFilter($users) {
  
  if(is_array($users)) {
    if(count($users) > 1) {
      return "uid=" . implode(" OR uid=", $users);
    } else {
      return "uid=" . intval($users[0]);
    }
  } else {
    return "uid=" . intval($users);
  }
	/*$start = 1;
	foreach($users as $user) {
		if($start) {
			$uq = "uid=$user";
			$start = 0;
		} else {
			$uq .= " OR uid=$user";
		}			
	}
	return $uq;*/
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

  if(!is_array($entries)) { // no entries passed, so we don't need to figure out the entries, so return only the fids and subfids
    foreach($one_to_one as $one_fid) {
      $fids[] = $one_fid['fid'];
    }
    foreach($one_to_many as $many_fid) {
      $sub_fids[] = $many_fid['fid'];
    }
    $start = 1;
    foreach($many_to_one as $many_fid) {
      if($start) {
        $sub_fids = array_merge($fids, (array)$sub_fids); // if there are many to one relationships, then invert the relationship of the forms we've collected so far
        unset($fids);
        $start = 0;
      }
      $fids[] = $many_fid['fid'];
    }
    $to_return['fids'] = $fids;
    $to_return['sub_fids'] = $sub_fids;
    return $to_return;
  }

  // $entries has been passed so we do need to gather them...

  // add to entries and fids array if one_to_one exists
  // ONLY WORKS WITH COMMON VALUES RIGHT NOW!!!
  // one to one linkages using linked selectboxes would probably be very uncommon
  $allFidsFoundChecker = array();
  $mainHandle = q("SELECT ele_handle FROM ".$xoopsDB->prefix("formulize")." WHERE ele_id=".$one_to_one[0]['keyother']);
  foreach($one_to_one as $one_fid) {
    $fids[] = $one_fid['fid'];
    $candidateHandle = q("SELECT ele_handle FROM ".$xoopsDB->prefix("formulize")." WHERE ele_id=".$one_fid['keyself']);
		$candidateEntry = q("SELECT candidate.entry_id FROM " . $xoopsDB->prefix("formulize_".$one_fid['fid']) . " AS candidate, ". $xoopsDB->prefix("formulize_".$fid) . " AS main WHERE candidate.".$candidateHandle[0]['ele_handle']."=main.".$mainHandle[0]['ele_handle']." AND main.entry_id = ".intval($entries[$fid][0])." LIMIT 0,1");
		/*print "SELECT ele_handle FROM ".$xoopsDB->prefix("formulize")." WHERE ele_id=".$one_fid['keyself'] . "<br><pre>";
		print_r($candidateHandle);
		print "</pre><br>SELECT candidate.entry_id FROM " . $xoopsDB->prefix("formulize_".$one_fid['fid']) . " AS candidate, ". $xoopsDB->prefix("formulize_".$fid) . " AS main WHERE candidate.".$candidateHandle[0]['ele_handle']."=main.".$mainHandle[0]['ele_handle']." AND main.entry_id = ".intval($entries[$fid][0])." LIMIT 0,1<br><pre>";
		print_r($candidateEntry);
		print "</pre>";*/
    if($candidateEntry[0]['entry_id']) {
      $entries[$one_fid['fid']][] = $candidateEntry[0]['entry_id'];
    } else {
      $entries[$one_fid['fid']][] = "";
    }
    $allFidsFoundChecker[$one_fid['fid']] = false;
  }
  
  foreach($one_to_many as $many_fid) {
    $sub_fids[] = $many_fid['fid'];
    if(isset($entries[$fid][0])) {
      if($thisent = $entries[$fid][0]) { // for some reason PHP 5 won't let us evaluate this directly
        $entries_found = findLinkedEntries($fid, $many_fid, $entries[$fid][0], $gperm_handler, $owner_groups, $mid, $member_handler, $owner);
        foreach($entries_found as $many_entry) {
          $sub_entries[$many_fid['fid']][] = $many_entry;
        }
      }
    } else {
      $sub_entries[$many_fid['fid']][] = "";
    }
  }

  // don't bother getting entries from many to one relationships...it is not expected to ever be necessary

	$to_return['fids'] = $fids;
	$to_return['entries'] = $entries;
	$to_return['sub_fids'] = $sub_fids;
	$to_return['sub_entries'] = $sub_entries;

	return $to_return;
	
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
  $lineStarted = false;
	if($template) {
		if($template == "blankprofile") { // add in other profile fields -- username, realname, e-mail, password, registration code
			$csvfile = "\"" . _formulize_DE_IMPORT_USERNAME . "\"$fd\"" . _formulize_DE_IMPORT_FULLNAME . "\"$fd\"" . _formulize_DE_IMPORT_PASSWORD . "\"$fd\"" . _formulize_DE_IMPORT_EMAIL . "\"$fd\"" . 	_formulize_DE_IMPORT_REGCODE . "\"";
      $lineStarted = true;
		} else {
			if($template == "update") {
				$csvfile = "\"" . _formulize_DE_IMPORT_IDREQCOL . "\"$fd\"" . _formulize_DE_CALC_CREATOR . "\"";
        $lineStarted = true;
			} else {
				$csvfile = "\"" . _formulize_DE_CALC_CREATOR . "\"";
        $lineStarted = true;
			}
		}
	} elseif($_POST['metachoice'] == 1) { // only include metadata columns if the user requested them
		$csvfile =  "\"" . _formulize_DE_CALC_CREATOR . "\"$fd\"" . _formulize_DE_CALC_CREATEDATE . "\"$fd\"" . _formulize_DE_CALC_MODIFIER . "\"$fd\"" . _formulize_DE_CALC_MODDATE . "\"";
    $lineStarted = true;
	} else {
      if(in_array("uid", $cols) OR in_array("creation_uid", $cols)) {
        $csvfile .= "\"" . _formulize_DE_CALC_CREATOR . "\"";
        $lineStarted = true;
      }
      if(in_array("creation_date", $cols) OR in_array("creation_datetime", $cols)) {
        $csvfile .= $lineStarted ? $fd : "";
        $csvfile .= "\"" . _formulize_DE_CALC_CREATEDATE . "\"";
        $lineStarted = true;
      }
      if(in_array("proxyid", $cols) OR in_array("mod_uid", $cols)) {
        $csvfile .= $lineStarted ? $fd : "";
        $csvfile .= "\"" . _formulize_DE_CALC_MODIFIER . "\"";
        $lineStarted = true;
      }
      if(in_array("mod_date", $cols) OR in_array("mod_datetime", $cols)) {
        $csvfile .= $lineStarted ? $fd : "";
        $csvfile .= "\"" . _formulize_DE_CALC_MODDATE . "\"";
        $lineStarted = true;
      }
  }
	foreach($headers as $header)
	{
		if($header == "" OR ($_POST['metachoice'] == 1 AND ($header == _formulize_DE_CALC_CREATOR OR $header == _formulize_DE_CALC_MODIFIER OR $header==_formulize_DE_CALC_CREATEDATE OR $header ==_formulize_DE_CALC_MODDATE))) { continue; } // ignore the metadata columns if they are selected, since we already handle them better above...as long as the user requested that they be included
		$header = str_replace("\"", "\"\"", $header);
		$header = "\"" . trans($header) . "\"";
    if($lineStarted) {
      $csvfile .= $fd;
    }
		$csvfile .= $header;
    $lineStarted = true;
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

		$c_uid = display($entry, 'creation_uid');
		$c_name_q = q("SELECT name, uname FROM " . $xoopsDB->prefix("users") . " WHERE uid='$c_uid'");
		$c_name = $c_name_q[0]['name'];
		if(!$c_name) { $c_name = $c_name_q[0]['uname']; }
		$c_date = display($entry, 'creation_datetime');
		$m_uid = display($entry, 'mod_uid');
		if($m_uid) {
			$m_name_q = q("SELECT name, uname FROM " . $xoopsDB->prefix("users") . " WHERE uid='$m_uid'");
			$m_name = $m_name_q[0]['name'];
			if(!$m_name) { $m_name = $m_name_q[0]['uname']; }
		} else {
			$m_name = $c_name;
		}
		$m_date = display($entry, 'mod_datetime');

		// write in metadata
    $lineStarted = false;
		if($template) { // will be update only, since blank ones have no data
			$csvfile .= $id . $fd . "\"$c_name\"";
      $lineStarted = true;
		} elseif($_POST['metachoice'] == 1) {
			$csvfile .= "\"$c_name\"" . $fd . "\"$c_date\"" . $fd . "\"$m_name\"" . $fd . "\"$m_date\"";
      $lineStarted = true;
		} 
		
		// write in data
		foreach($cols as $col) {
			if(($col == "uid" OR $col == "proxyid" OR $col=="creation_date" OR $col =="mod_date" OR $col == "creation_uid" OR $col == "mod_uid" OR $col == "creation_datetime" OR $col == "mod_datetime") AND $_POST['metachoice'] == 1) { continue; } // ignore the metadata columns if they are selected, since we already handle them better above
			$data_to_write = displayTogether($entry, $col, "\n");
			$data_to_write = str_replace("&quot;", "&quot;&quot;", $data_to_write);
			$data_to_write = "\"" . trans($data_to_write) . "\"";
			$data_to_write = str_replace("\r\n", "\n", $data_to_write);
      if($lineStarted) {
        $csvfile .= $fd;
      }
			$csvfile .= $data_to_write;
      $lineStarted = true;
		}
		$csvfile .= "\r\n"; // end of a line
	}
	$tempfold = time();
	$exfilename = _formulize_DE_XF . $tempfold . $fxt;
	// open the output file for writing
	$wpath = XOOPS_ROOT_PATH."/modules/formulize/export/$exfilename";
	//print $wpath;
	$csvfile = html_entity_decode($csvfile, ENT_QUOTES);
	$exportfile = fopen($wpath, "w");
	fwrite ($exportfile, $csvfile);
	fclose ($exportfile);

  // garbage collection...delete files older than 6 hours
	formulize_scandirAndClean(XOOPS_ROOT_PATH."/modules/formulize/export/", _formulize_DE_XF); 

	// write id_reqs and tempfold to the DB if we're making an update template
	if($template == "update") {
		$sql = "INSERT INTO " . $xoopsDB->prefix("formulize_valid_imports") . " (file, id_reqs) VALUES (\"$tempfold\", \"" . serialize($id_req) . "\")";
		if(!$res = $xoopsDB->queryF($sql)) {
			exit("Error: could not write import information to the database.  SQL: $sql<br>".mysql_error());
		}
	}


	// need to add in logic to cull old files...
	


	return XOOPS_URL . "/modules/formulize/export/$exfilename";

}

// this function returns the data to summarize the details about the entry you are looking at
// useOldCode is used to trigger the pre-3.0 logic only when the patching process is taking place.  After that, new process should kick in since new data structure is available.
function getMetaData($entry, $member_handler, $fid="", $useOldCode=false) {
  
        if(!$member_handler) {
          $member_handler =& xoops_gethandler('member');
        }
  
  if($useOldCode) {
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
  
  // use new class in all cases, except where we're specifically asking for old logic, which is only necessary during the initial patching process for 3.0
  } elseif($fid) {
    $data_handler = new formulizeDataHandler($fid);
    $meta_to_return = array();
    list($meta_to_return['created'], $meta_to_return['last_update'], $meta_to_return['created_by_uid'], $meta_to_return['last_update_by_uid']) = $data_handler->getEntryMeta($entry);
    if($meta_to_return['created'] == 0) { // not sure if the new date format will ever evaluate to 0, but just in case...
      $meta_to_return['created'] = "???";
    }
    if($creator = $member_handler->getUser($meta_to_return['created_by_uid'])) {
      $meta_to_return['created_by'] = $creator->getVar('name') ? $creator->getVar('name') : $creator->getVar('uname');  
    } else {
      $meta_to_return['created_by'] = _FORM_ANON_USER;
    }
    if($modder = $member_handler->getUser($meta_to_return['last_update_by_uid'])) {
      $meta_to_return['last_update_by'] = $modder->getVar('name') ? $modder->getVar('name') : $modder->getVar('uname');  
    } else {
      $meta_to_return['last_update_by'] = _FORM_ANON_USER;
    }
    return $meta_to_return;
  } else {
    exit("Error: must use a form id when retrieving metadata.");
  }

  
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
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);		

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
		$uid = $xoopsUser ? $xoopsUser->getVar('uid') : "0";
		foreach($fids as $this_fid) {
			if(security_check($this_fid, "", $uid, "", $groups, $mid, $gperm_handler)) { 
				$c = q("SELECT ele_id, ele_caption, ele_colhead, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form='$this_fid' $gq $pq $incbreaks AND ele_type != \"subform\" AND ele_type != \"grid\" ORDER BY ele_order");
				$cols[$this_fid] = $c;
			} 
		}
		foreach($sub_fids as $this_fid) {
			if(security_check($this_fid, "", $uid, "", $groups, $mid, $gperm_handler)) { 
				$c = q("SELECT ele_id, ele_caption, ele_colhead, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form='$this_fid' $gq $pq $incbreaks AND ele_type != \"subform\" AND ele_type != \"grid\" ORDER BY ele_order");
				$cols[$this_fid] = $c;
			}
		}
	} else {
		$cols[$fid] = q("SELECT ele_id, ele_caption, ele_colhead, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form='$fid' $gq $pq $incbreaks AND ele_type != \"subform\" AND ele_type != \"grid\" ORDER BY ele_order");
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
			$temp_text = getCalcHandleText($items['as_' . $i], "", true); // last param forces colhead
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


// THIS FUNCTION TAKES A ID FROM THE CALCULATIONS RESULT AND RETURNS THE TEXT TO PUT ON THE SCREEN THAT CORRESPONDS TO IT
// Also used for advanced searches
function getCalcHandleText($handle, $frid="", $forceColhead=false) {
	global $xoopsDB;
	if($handle == "creation_uid") {
		return _formulize_DE_CALC_CREATOR;
	} elseif($handle == "mod_uid") {
		return _formulize_DE_CALC_MODIFIER;
	} elseif($handle == "creation_datetime") {
		return _formulize_DE_CALC_CREATEDATE;
	} elseif($handle == "mod_datetime") {
		return _formulize_DE_CALC_MODDATE;
	} elseif($handle == "creator_email") {
		return _formulize_DE_CALC_CREATOR_EMAIL;
	} elseif(is_numeric($handle)) {
		$caption = q("SELECT ele_caption, ele_colhead FROM " . $xoopsDB->prefix("formulize"). " WHERE ele_id = '$handle'");
		if($forceColhead AND $caption[0]['ele_colhead'] != "") {
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
        if($currentView == "blank") { // send an invalid scope
                $scope = "uid=\"blankscope\"";
        } elseif($currentView == "mine" OR substr($currentView, 0, 4) == "old_") {
		$all_users[] = $uid;
		$scope = makeUidFilter($all_users);
	} elseif($currentView == "group") {
		$groupsWithAccess = $gperm_handler->getGroupIds("view_form", $fid, $mid);
    $scopeGroups = array_intersect($groupsWithAccess, $groups);
    if(count($scopeGroups)==0) { // safeguard against empty or invalid grouplists
		//if(!isset($all_users[0])) { 
			$all_users[] = $uid;
      $scope = makeUidFilter($all_users);
		} else {
      $scope = $scopeGroups;
    }
		
	} elseif(strstr($currentView, ",")) { // advanced scope, or oldscope
		$grouplist = explode("," , trim($currentView, ","));
    if($grouplist[0] == "onlymembergroups") { // first key may be a special flag to cause the scope to be handled differently
      global $xoopsUser;
      $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
      unset($grouplist[0]);
      $grouplist = array_intersect($groups, $grouplist);
    }
    if(count($grouplist)==0) { // safeguard against empty or invalid grouplists
		//if(!isset($all_users[0])) { 
			$all_users[] = "";
      $scope = makeUidFilter($all_users);
		} else {
      $scope = $grouplist;
    }
		
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
  if(function_exists('xlanguage_ml')) {
		$string = xlanguage_ml($string);
  } elseif(method_exists($myts, 'formatForML')) {
		$string = $myts->formatForML($string);
	} 
	return $string;
}

// THIS FUNCTION FIGURES OUT THE MAX ID_REQ IN USE AND RETURNS THE NEXT VALID ID_REQ
// ***DEPRECATED*** only used in the patching logic for patching up to 3.0
function getMaxIdReq() {
    global $xoopsDB;
    $sql = $xoopsDB->query("SELECT id_req from " . $xoopsDB->prefix("formulize_form")." order by id_req DESC LIMIT 0,1");
    list($id_req) = $xoopsDB->fetchRow($sql);
    if ($id_req == 0) { $num_id = 1; }
    else if ($num_id <= $id_req) $num_id = $id_req + 1;
    return $num_id;
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
          if($ele >= $opt_count) { // if a value was received that was out of range...added by jwe March 2 2008
            $value = $myts->htmlSpecialChars($_POST['formulize_hoorv_'.$ele_id.'_'.$ele]); // get the out of range value from the hidden values that were passed back
          }
				break;
				case 'yn':
					$value = $ele;
				break;
				case 'checkbox':
					$value = '';
					$opt_count = 1;
          $numberOfSelectionsFound = 0;
					while( $v = each($ele_value) ){
						if( is_array($ele) ){ // it's always an array...right?!
							if( in_array($opt_count, $ele) ){
                $numberOfSelectionsFound++;
								$GLOBALS['formulize_other'][$ele_id] = checkOther($v['key'], $ele_id);
								if(get_magic_quotes_gpc()) { $v['key'] = stripslashes($v['key']); }
								$v['key'] = $myts->htmlSpecialChars($v['key']);
								$value = $value.'*=+*:'.$v['key'];
							}
							$opt_count++;
						}/*else{ // single value passed back...under what circumstances would this ever get triggered??  Isn't $ele always an array for a checkbox series, even when a single value is checked?  jwe March 2 2008
							if( !empty($ele) ){
								$GLOBALS['formulize_other'][$ele_id] = checkOther($v['key'], $ele_id);
								if(get_magic_quotes_gpc()) { $v['key'] = stripslashes($v['key']); }
								$v['key'] = $myts->htmlSpecialChars($v['key']);
								$value = $value.'*=+*:'.$v['key'];
							}
						}	*/					
					}
          while($numberOfSelectionsFound < count($ele)) { // if a value was received that was out of range...added by jwe March 2 2008...in this case we are assuming that if there are more values passed back than selections found in the valid options for the element, then there are out-of-range values we want to preserve
            if(in_array($opt_count, $ele)) { // keep looking for more values...get them out of the hiddenOutOfRange info
              $value = $value.'*=+*:'.$myts->htmlSpecialChars($_POST['formulize_hoorv_'.$ele_id.'_'.$opt_count]);
              $numberOfSelectionsFound++;
            }
            $opt_count++;
          }
				break;
				case 'select':
          
          // handle the new possible default value -- sept 7 2007
              if($ele_value[0] == 1 AND $ele == "none") { // none is the flag for the "Choose an option" default value
                $value = "{SKIPTHISDATE}"; // this flag is used to terminate processing of this value
                break;
              }
          
					// section to handle linked select boxes differently from others...
          $ele_value_from_object = $element->getVar('ele_value');
					if(strstr($ele_value_from_object[2], "#*=:*")) { // if we've got a formlink, then handle it here...
						if(is_array($ele)) {
							//print_r($ele);
              $value = ",";
              foreach($ele as $whatwasselected) {
                $value .= $whatwasselected.",";
              }
						} else {
              $value = ",".$ele.",";
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
							if($temparraykeys[0] === "{FULLNAMES}" OR $temparraykeys[0] === "{USERNAMES}") { // ADDED June 18 2005 to handle pulling in usernames for the user's group(s) -- updated for real live use September 6 2006
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

              // THIS REALLY OLD CODE IS HARD TO READ....HERE'S A GLOSS...
              // ele_value[2] is all the options that make up this element.  The values passed back from the form will be numbers indicating which value was selected.  First value is 0 for a multi-selection box, and 1 for a single selection box.
              // Subsequent values are one number higher and so on all the way to the end.  Five values in a multiple selection box, the numbers are 0, 1, 2, 3, 4.
              // masterentlistjwe and entrycounterjwe will be the same!!  There's these array_keys calls here, which result basically in a list of numbers being created, keysPassedBack, and that list is going to start at 0 and go up to whatever the last value is.  It always starts at zero, even if the list is a single selection list.  entrycounterjwe will also always start at zero.
              // After that, we basically just loop through all the possible places, 0 through n, that the user might have selected, and we check if they did.
              // The check lines are if($whattheuserselected == $masterentlistjwe) and $ele == ($masterentlistjwe+1) ....note the +1 to make this work for single selection boxes where the numbers start at 1 instead of 0.
              // This is all further complicated by the fact that we're grabbing values from $entriesPassedBack, which is just the list of options in the form, so that we can populate the ultimate $value that is going to be written to the database.
            
							$entriesPassedBack = array_keys($ele_value[2]);
							$keysPassedBack = array_keys($entriesPassedBack);
							$entrycounterjwe = 0;
              $numberOfSelectionsFound = 0;
							foreach($keysPassedBack as $masterentlistjwe)
							{
	      					if(is_array($ele)) {
                    //foreach($ele as $whattheuserselected) // note this loop within a loop should not be necessary...we do not need to check all the submitted values from the form once for each possible value in the form!
                    if(in_array($masterentlistjwe, $ele)) 
                    {
                      // if the user selected an entry found in the master list of all possible entries...
                      //print "internal loop $entrycounterjwe<br>userselected: $whattheuserselected<br>selectbox contained: $masterentlistjwe<br><br>";	
                      //if($whattheuserselected == $masterentlistjwe) // this check is encompassed above with the in_array check
                      //{
                        //print "WE HAVE A MATCH!<BR>"; -- note: nametype should
                        if(get_magic_quotes_gpc()) { $entriesPassedBack[$entrycounterjwe] = stripslashes($entriesPassedBack[$entrycounterjwe]); }
                        $entriesPassedBack[$entrycounterjwe] = $myts->htmlSpecialChars($entriesPassedBack[$entrycounterjwe]);
                        $value = $value . "*=+*:" . $entriesPassedBack[$entrycounterjwe];
                        $numberOfSelectionsFound++;
                        //print "$value<br><br>";
                      //}
                    }
                    $entrycounterjwe++;
                  } else {
                    //print "internal loop $entrycounterjwe<br>userselected: $ele<br>selectbox contained: $masterentlistjwe<br><br>";	
                    if($ele == ($masterentlistjwe+1)) // plus 1 because single entry select boxes start their option lists at 1.
                    {
                      //print "WE HAVE A MATCH!<BR>";
                      if(get_magic_quotes_gpc()) { $entriesPassedBack[$entrycounterjwe] = stripslashes($entriesPassedBack[$entrycounterjwe]); }
                      $entriesPassedBack[$entrycounterjwe] = $myts->htmlSpecialChars($entriesPassedBack[$entrycounterjwe]);
                      $value = $entriesPassedBack[$entrycounterjwe];
                      //print "$value<br><br>";
                    }
                    $entrycounterjwe++;
                  }
							}
              // handle out of range values that are in the DB, added March 2 2008 by jwe
              if(is_array($ele)) {
                while($numberOfSelectionsFound < count($ele)) { // if a value was received that was out of range...added by jwe March 2 2008...in this case we are assuming that if there are more values passed back than selections found in the valid options for the element, then there are out-of-range values we want to preserve
                  if(in_array($entrycounterjwe, $ele)) { // keep looking for more values...get them out of the hiddenOutOfRange info
                    $value = $value.'*=+*:'.$myts->htmlSpecialChars($_POST['formulize_hoorv_'.$ele_id.'_'.$entrycounterjwe]);
                    $numberOfSelectionsFound++;
                  }
                  $entrycounterjwe++;
                }
              } else {
                if($ele > $entrycounterjwe) { // if a value was received that was out of range...added by jwe March 2 2008 (note that unlike with radio buttons, we need to check only for greater than, due to the +1 (starting at 1) that happens with single option selectboxes
                  $value = $myts->htmlSpecialChars($_POST['formulize_hoorv_'.$ele_id.'_'.$ele]); // get the out of range value from the hidden values that were passed back
                }
              }
                
                
          } // end of if that checks for a linked select box.
              
              
					// print "selects: $value<br>";
				break;
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
				/*
				 * Hack by Félix<INBOX International>
				 * Adding colorpicker form element
				 */
				case 'colorpick':
					$value = $ele;
				break;
				/*
				 * End of Hack by Félix<INBOX International>
				 * Adding colorpicker form element
				 */
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
// Only returns true or false, not the actual value
function getElementValue($entry, $element_id, $fid) {
        $data_handler = new formulizeDataHandler($fid);
        if(!$data_handler->elementHasValueInEntry($entry, $element_id)) {
          return false;
        } else {
          return true;
        }
}

// this function checks for singleentry status and returns the appropriate entry in the form if there is one
function getSingle($fid, $uid, $groups, $member_handler, $gperm_handler, $mid) {
	global $xoopsDB;
	// determine single/multi status
	$smq = q("SELECT singleentry FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form=$fid");
	if($smq[0]['singleentry'] != "") {
		// find the entry that applies
		$single['flag'] = $smq[0]['singleentry'] == "on" ? 1 : "group";
		if($smq[0]['singleentry'] == "on") { // if we're looking for a regular single, find first entry for this user
      $data_handler = new formulizeDataHandler($fid);
      $single['entry'] = $data_handler->getFirstEntryForUsers($uid); 
		} elseif($smq[0]['singleentry'] == "group") { // get the first entry belonging to anyone in their groups, excluding any groups that do not have add_own_entry permission
			$groupsWithAccess = $gperm_handler->getGroupIds("add_own_entry", $fid, $mid);
			$intersect_groups = array_intersect($groups, $groupsWithAccess);
			$all_users = array();
      global $formulize_archived_available;
			foreach($intersect_groups as $grp) {
				if($grp != XOOPS_GROUP_USERS) { // exclude registered users group since that's everyone! -- superfluous now since registered users would normally be ignored since people probably would not be handing out perms to registered users group (on the other hand, if someone wanted to, it should be allowed now, since it won't screw things up necessarily, thanks to the use of groupsWithAccess)
          if($formulize_archived_available) {
            $users = $member_handler->getUsersByGroup($grp, false, 0, 0, true);  // last param will include archived users based on the Freeform archived user core hack
          } else {
            $users = $member_handler->getUsersByGroup($grp);  
          }
					$all_users = array_merge((array)$users, $all_users);
					unset($users);
				}
			}
      $data_handler = new formulizeDataHandler($fid);
      $single['entry'] = $data_handler->getFirstEntryForUsers($all_users);
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
	/*
	 * Hack by Félix <INBOX Solutions> for sedonde
	 * myts == NULL
	 */
	if(!$myts){
		$myts =& MyTextSanitizer::getInstance();
	}
	/*
	 * Hack by Félix <INBOX Solutions> for sedonde
	 * myts == NULL
	 */
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



// THIS FUNCTION CREATES A SERIES OF ARRAYS THAT CONTAIN ALL THE INFORMATION NECESSARY FOR THE LIST OF ELEMENTS THAT GETS DISPLAYED ON THE ADMIN SIDE WHEN CREATING OR EDITING CERTAIN FORM ELEMENTS
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
      $resformlist = $xoopsDB->query($formlist);
      if($resformlist)
      {
      	while ($rowformlist = $xoopsDB->fetchRow($resformlist)) // loop through each form
      	{
      		$fieldnames = "SELECT ele_caption, ele_id, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=$rowformlist[0] ORDER BY ele_order";
      		$resfieldnames = $xoopsDB->query($fieldnames);
      		
      		while ($rowfieldnames = $xoopsDB->fetchRow($resfieldnames)) // loop through each caption in the current form
      		{

      			$totalcaptionlist[$captionlistindex] = printSmart(trans($rowformlist[1])) . ": " . printSmart(trans($rowfieldnames[0]), 50);  // write formname: caption to the master array that will be passed to the select box.

				
					$totalvaluelist[$captionlistindex] = $rowfieldnames[1];
				


      			if($val == $totalvaluelist[$captionlistindex] OR $val == $rowformlist[0] . "#*=:*" . $rowfieldnames[2]) // if this is the selected entry...
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
        
	if(isset($defaultlinkselection))
	{
          $formlink->setValue($totalvaluelist[$defaultlinkselection]);
	}
	$formlink->setDescription($am_ele_formlink_desc);

  
  if(!$textbox)	{ // return two pieces of info for selectboxes, since we need to know the element selected
    $to_return = array();
    $to_return[] = $formlink;
    $to_return[] = isset($defaultlinkselection) ? $totalvaluelist[$defaultlinkselection] : "";
    return $to_return;
  } else {
    return $formlink;
  }

} 

// THIS FUNCTION TAKES AN ELEMENT OBJECT, AND A VALUE AND SEARCHES IN THE ELEMENT'S FORM FOR THE FIRST ID_REQ THAT MATCHES THE VALUE
// Used by the new textbox link option to find a matching entry, so that it can be linked in the list of entries screen.
// Matches must be exact!  
// Returns the id_req that matches the value, or false if nothing found
function findMatchingIdReq($element, $fid, $value) {

	if(!is_object($element)) { return false; }

  $original_value = $value;
  static $cachedValues = array();
  if(!isset($cachedValues[$element->getVar('ele_id')][$original_value])) {
    $data_handler = new formulizeDataHandler($fid);
    $entry_id = $data_handler->findFirstEntryWithValue($element, $value);
    if($entry_id) {
          $cachedValues[$element->getVar('ele_id')][$original_value] = $entry_id;
    } else {
          $cachedValues[$element->getVar('ele_id')][$original_value] = false;
    }
  }
  return $cachedValues[$element->getVar('ele_id')][$original_value];
	
}


// THIS FUNCTION OUTPUTS THE TEXT THAT GOES ON THE SCREEN IN THE LIST OF ENTRIES TABLE
// It intelligently outputs links if the text should be a link (because of textbox associations, or linked selectboxes)
// $handle is the form or framework handle
function formatLinks($matchtext, $handle, $frid, $textWidth=35) {
  formulize_benchmark("start of formatlinks");
	global $xoopsDB, $myts;
  static $cachedValues = array();
  static $cachedTypes = array();
	$matchtext = $myts->undoHtmlSpecialChars($matchtext);
	if($handle == "uid" OR $handle=="proxyid" OR $handle=="creation_date" OR $handle == "mod_date" OR $handle == "creator_email" OR $handle == "creation_uid" OR $handle == "mod_uid" OR $handle == "creation_datetime" OR $handle == "mod_datetime") { return printSmart(trans($myts->htmlSpecialChars($matchtext)), $textWidth); }
  if(!isset($cachedValues[$handle])) {
    if($frid) {
      $resultArray = formulize_getElementHandleAndIdFromFrameworkHandle($handle, $frid);
      $elementMetaData = formulize_getElementMetaData($resultArray[1], false);
    } else {
      $elementMetaData = formulize_getElementMetaData($handle, true);
    }
    $ele_value = unserialize($elementMetaData['ele_value']);
    $ele_type = $elementMetaData['ele_type'];
    if(!$ele_value) { return printSmart(trans($myts->htmlSpecialChars($matchtext)), $textWidth); }
    if(!isset($ele_value[4])) { $ele_value[4] = 0; }
    if(!isset($ele_value[3])) { $ele_value[3] = 0; }
    $cachedValues[$handle] = $ele_value;
    $cachedTypes[$handle] = $ele_type;
  } else {
    $ele_value = $cachedValues[$handle];
    $ele_type = $cachedTypes[$handle];
  }
	formulize_benchmark("got element info");
	if(($ele_value[4] > 0 AND $ele_type=='text') OR ($ele_value[3] > 0 AND $ele_type=='textarea')) { // dealing with a textbox where an associated element has been set
		$formulize_mgr = xoops_getmodulehandler('elements', 'formulize');
		if($ele_type == 'text') {
			$target_element = $formulize_mgr->get($ele_value[4]);
		} else {
			$target_element = $formulize_mgr->get($ele_value[3]);
		}
		$target_fid = $target_element->getVar('id_form');
		// if user has no perm in target fid, then do not make link!
		if(!$target_allowed = security_check($target_fid)) {
			return printSmart(trans($myts->htmlSpecialChars($matchtext)), $textWidth);
		}
		$matchtexts = explode(";", $matchtext); // have to breakup the textbox's text since it may contain multiple matches.  Note no space after semicolon spliter, but we trim the results in the foreach loop below.
		$printText = "";
		$start = 1;
		foreach($matchtexts as $thistext) {
			$thistext = trim($thistext);
			if(!$start) { $printText .= ", "; }
			if($id_req = findMatchingIdReq($target_element, $target_fid, $thistext)) {
				$printText .= "<a href='" . XOOPS_URL . "/modules/formulize/index.php?fid=$target_fid&ve=$id_req' target='_blank'>" . printSmart(trans($myts->htmlSpecialChars($thistext)), $textWidth) . "</a>";
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
		// [1] is handle of linked field 
		$target_fid = $boxproperties[0];
		// if user has no perm in target fid, then do not make link!
		if(!$target_allowed = security_check($target_fid)) {
			return printSmart(trans($myts->htmlSpecialChars($matchtext)), $textWidth);
		}
                static $cachedQueryResults = array();
                if(isset($cachedQueryResults[$boxproperties[0]][$boxproperties[1]][$matchtext])) {
                  $id_req = $cachedQueryResults[$boxproperties[0]][$boxproperties[1]][$matchtext];
                } else {
                  $element_id_q = q("SELECT ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form='" . $boxproperties[0] . "' AND ele_handle='" . mysql_real_escape_string($boxproperties[1]) . "' LIMIT 0,1"); // should only be one match anyway, so limit 0,1 ought to be unnecessary
									$formulize_mgr = xoops_getmodulehandler('elements', 'formulize');
                  $target_element =& $formulize_mgr->get($element_id_q[0]['ele_id']);
                  $id_req = findMatchingIdReq($target_element, $target_fid, $matchtext);
                  $cachedQueryResults[$boxproperties[0]][$boxproperties[1]][$matchtext] = $id_req;
                }
		if($id_req) {
			return "<a href='" . XOOPS_URL . "/modules/formulize/index.php?fid=$target_fid&ve=$id_req' target='_blank'>" . printSmart(trans($myts->htmlSpecialChars($matchtext)), $textWidth) . "</a>";
		} else { // no id_req found
			return printSmart(trans($myts->htmlSpecialChars($matchtext)), $textWidth);
		}
	} elseif($ele_type =='select' AND (isset($ele_value[2]['{USERNAMES}']) OR isset($ele_value[2]['{FULLNAMES}']))) {
		$nametype = isset($ele_value[2]['{USERNAMES}']) ? "uname" : "name";
		$archiveFilter = $GLOBALS['formulize_archived_available'] ? " AND archived = 0" : "";
                static $cachedUidResults = array();
                if(isset($cachedUidResults[$matchtext])) {
                  $uids = $cachedUidResults[$matchtext];
                } else {
                  $uids = q("SELECT uid FROM " . $xoopsDB->prefix("users") . " WHERE $nametype = '" . mysql_real_escape_string($myts->htmlSpecialChars($matchtext)) . "' $archiveFilter");
                  $cachedUidResults[$matchtext] = $uids;
                }
		if(count($uids) == 1) {
			return "<a href='" . XOOPS_URL . "/userinfo.php?uid=" . $uids[0]['uid'] . "' target=_blank>" . printSmart(trans($myts->htmlSpecialChars($matchtext)), $textWidth) . "</a>";
		} else {
			return printSmart(trans($myts->htmlSpecialChars($matchtext)), $textWidth);
		}
	} else { // regular element
    formulize_benchmark("done formatting, about to print");
		return printSmart(trans($myts->htmlSpecialChars($matchtext)), $textWidth);
	}
} 

// THIS FUNCTION INTERPRETS A TEXTBOX'S DEFAULT VALUE AND RETURNS THE CORRECT STRING
// Takes $ele_value[2] as the input (third position in ele_value array from element object)
// $form_id and $entry_id are passed in so they can be accessible within the eval'd code if necessary
function getTextboxDefault($ele_value, $form_id, $entry_id) {

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
// IMPORTANT:  assume $startEntry is valid for the user(security check has already been executed by now)
// therefore just need to know the allowable uids (scope) in the $targetForm
// $owner_groups appears to be deprecated and not used in this function any longer...see DEPRECATED note below
function findLinkedEntries($startForm, $targetForm, $startEntry, $gperm_handler, $owner_groups, $mid, $member_handler, $owner) {


	// set scope filter -- may need to pass in some exceptions here in the case of viewing entries that are covered by reports?
	// DEPRECATED: scope based on the owner's scope within the subform, since that is the entries that the owner would see, the entries that belong to this entry, within the subform
	// Scope now based on user's permission level, so they can see what they should see, regardless of the owner's permission
	global $xoopsUser;
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
	if($global_scope = $gperm_handler->checkRight("view_globalscope", $targetForm['fid'], $groups, $mid)) {
		$scope_filter = ""; // deprecated
    $all_users = ""; // deprecated, now using groups
    $all_groups = "";
	} elseif($group_scope = $gperm_handler->checkRight("view_groupscope", $targetForm['fid'], $groups, $mid)) {
		$groupsWithAccess = $gperm_handler->getGroupIds("add_own_entry", $targetForm['fid'], $mid);
    $all_groups = array_intersect($groups, $groupsWithAccess);
    $all_users = "";
		/*$all_users = array(); // deprecated, now using groups
		foreach($groups as $grp) {
			if(in_array($grp, $groupsWithAccess)) { // include only owner_groups that have view_form permission (so exclude groups the owner is a member of which aren't able to view the form)
				if($grp != XOOPS_GROUP_USERS) { // exclude registered users group since that's everyone!
					$users = $member_handler->getUsersByGroup($grp);
					$all_users = array_merge((array)$users, $all_users);
					unset($users);
				}
			}
		}*/
		$uq = makeUidFilter($all_users);
		$scope_filter = "AND ($uq)"; // deprecated
	} else {
		$scope_filter = "AND uid=$owner"; // deprecated
    $all_users = array(0=>$owner);
    $all_groups = "";
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
    $data_handler_start = new formulizeDataHandler($startForm);
    $data_handler_target = new formulizeDataHandler($targetForm['fid']);
    $metaData = $data_handler_start->getEntryMeta($startEntry);
    $entry_ids = $data_handler_target->getAllEntriesForUsers($metaData['creation_uid'], $all_users, $all_groups);
    if(count($entry_ids) > 0) {
      $entries_to_return = $entry_ids;
    } else {
      $entries_to_return = "";
    }
    return $entries_to_return;
	// support for true shared values added September 4 2006
	} elseif($targetForm['common']) {
		// return id_reqs from $targetForm['fid'] where the value of the matching element is the same as in the startEntry, startForm
    $data_handler_start = new formulizeDataHandler($startForm);
    $data_handler_target = new formulizeDataHandler($targetForm['fid']);
    $foundValue = $data_handler_start->getElementValueInEntry($startEntry, $targetForm['keyother']);
    $entry_ids = $data_handler_target->findAllEntriesWithValue($targetForm['keyself'], $foundValue, $all_users, $all_groups);
    if(count($entry_ids) > 0) {
      $entries_to_return = $entry_ids;
    } else {
      $entries_to_return = "";
    }
    return $entries_to_return;
	// else we're looking at a classic "shared value" which is really a linked selectbox
	} else { // linking based on a shared value.  in the case of one to one forms assumption is that the shared value does not appear more than once in either form's field (otherwise this will be a defacto one to many link)
		
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $startElement = $element_handler->get($targetForm['keyother']);
    $startEleValue = $startElement->getVar('ele_value');
    if(strstr($startEleValue[2], "#*=:*")) { // option 2, start form is the linked selectbox
      // so look in the startEntry for the values in its linked field and return them.  They will be a comma separated list of entry ids in the target form.
      $data_handler_start = new formulizeDataHandler($startForm);
      $foundValue = $data_handler_start->getElementValueInEntry($startEntry, $targetForm['keyother'], $all_users, $all_groups);
      if($foundValue) {
        return explode(",",trim($foundValue, ","));
      } else {
        return false;
      }
    } else { // option 3. target form is the linked selectbox
      // so look for all the entry ids in the target form, where the linked field has the startEntry in it
      $data_handler_target = new formulizeDataHandler($targetForm['fid']);
      $entries_to_return = $data_handler_target->findAllEntriesWithValue($targetForm['keyself'], $startEntry, $all_users, $all_groups, "{LINKEDSEARCH}");
      if($entries_to_return !== false) {
        return $entries_to_return;
      } else {
        return false;
      }
    }
	}
}


// this function takes an entry and makes copies of it
// can take an entry in a framework and make copies of all relevant entries in all relevant forms
// note that the same relative linked selectbox relationships are preserved in cloned framework entries, but links based on common values and uids are not modified at all...this might not be desired behaviour in all cases!!!
// entries in single-entry forms are never cloned

function cloneEntry($entry, $frid, $fid, $copies) {


	global $xoopsDB, $xoopsUser;
	include_once XOOPS_ROOT_PATH . "/modules/formulize/class/forms.php";
	$lsbpairs = array();
	if($frid) {
		include_once XOOPS_ROOT_PATH . "/modules/formulize/class/frameworks.php";
		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
		$thisframe = new formulizeFramework($frid);
		$links = $thisframe->getVar('links');
		// get the element ids of the elements that are linked selectboxes pointing to another form
		$lsbindexer = 0;
		foreach($links as $link) {
			if(!$link->getVar('common') AND $link->getVar('key1') AND $link->getVar('relationship') > 1) { // not a common value link, and not a uid link (key is 0 for uid links)
        // 2 is one to many
        // 3 is many to one
        if($link->getVar('relationship') == 2) { // key1 is the textbox, key2 is the lsb
          $lsbpairs[$link->getVar('key1')] = $link->getVar('key2');
        } else { // key 1 is the lsb and key 2 is the textbox
          $lsbpairs[$link->getVar('key2')] = $link->getVar('key1');
        }
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
	$dataHandlers = array();
	for($copy_counter = 0; $copy_counter<$copies; $copy_counter++) {

    foreach($entries_to_clone as $fid=>$entries) {
  
      // never clone an entry in a form that is a single-entry form
      $thisform = new formulizeForm($fid);
      if($thisform->getVar('single') != "off") { continue; }
      foreach($entries as $thisentry) {
  
            if(!isset($dataHandlers[$fid])) {
              $dataHandlers[$fid] = new formulizeDataHandler($fid);
            }
            $clonedEntryId = $dataHandlers[$fid]->cloneEntry($thisentry);
            $dataHandlers[$fid]->setEntryOwnerGroups(getEntryOwner($thisentry, $fid), $clonedEntryId);
            $entryMap[$fid][$thisentry][] = $clonedEntryId;
            
      }
    }
  }
  
  // all entries have been made.  Now we need to fix up any linked selectboxes
  $element_handler = xoops_getmodulehandler('elements', 'formulize');
  foreach($lsbpairs as $source=>$lsb) {
      $sourceElement = $element_handler->get($source);
      $lsbElement = $element_handler->get($lsb);
      $dataHandlers[$lsbElement->getVar('id_form')]->reassignLSB($sourceElement->getVar('id_form'), $lsbElement, $entryMap);
  }
  

}

// THIS FUNCTION HANDLES SENDING OF NOTIFICATIONS
// Does some unconventional stuff to handle custom templates for messages, and sending to everyone in a group, or to the current user (like a confirmation message)
// $groups is ignored, and should not be specified.  Param exists for historical reasons only.
function sendNotifications($fid, $event, $entries, $mid="", $groups=array()) {

	// don't send a notification twice, so we store what we have processed already and don't process again
	static $processedNotifications = array();
	$serializedEntries = serialize($entries);
	if(isset($processedNotifications[$fid][$event][$serializedEntries])) { return; }
	$processedNotifications[$fid][$event][$serializedEntries] = true;

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

	// 1b. get the complete list of all possible users to notify

	$gperm_handler =& xoops_gethandler('groupperm');
	$member_handler =& xoops_gethandler('member');

	// get uids of all users with global scope
	$groups_global = $gperm_handler->getGroupIds("view_globalscope", $fid, $mid);
	$global_uids = formulize_getUsersByGroups($groups_global, $member_handler);

	// get uids of all users with group scope who share a group membership with the owner of the entry, **and the shared membership is in a group that has access to the form**
	// start with users who have groupscope
	$groups_group = $gperm_handler->getGroupIds("view_groupscope", $fid, $mid);
	$group_user_ids = formulize_getUsersByGroups($groups_group, $member_handler);
	// get groups with view_form, 
	$groups_view = $gperm_handler->getGroupIds("view_form", $fid, $mid);
	

	$notification_handler =& xoops_gethandler('notification');

	// start main loop
	foreach($entries as $entry) {
    
    // user list is potentially different for each entry...ignore anything that was passed in for $groups
    if(count($groups) == 0) { // if no groups specified as the owner of the current entry, then let's get that from the table
      $data_handler = xoops_getmodulehandler('data', 'formulize');
    	$groups = $data_handler->getEntryOwnerGroups($entry);
    }
    // take the intersection of groups with view form perm and the owner's groups (ie: the owner's groups that have view_form perm)
    $owner_groups_with_view = array_intersect($groups_view, $groups);
    // get users in the owners-groups-that-have-view_form-perm
    $owner_groups_user_ids = formulize_getUsersByGroups($owner_groups_with_view, $member_handler);
    // get the intersection of users in the owners-groups-that-have-view_form-perm and groups with groupscope
    $group_uids = array_intersect($group_user_ids, $owner_groups_user_ids);
  
    $uids_complete = array();
    if(count($group_uids) > 0 AND count($global_uids) > 0) {
      $uids_complete = array_unique(array_merge((array)$group_uids, (array)$global_uids));
    } elseif(count($group_uids) > 0) {
      $uids_complete = $group_uids;
    } elseif(count($global_uids) > 0) {
      $uids_complete = $global_uids;
    } else {
      continue; // no possible notification users found
    }

		$uids_conditions = array();
		$saved_conditions = array();
    $data = "";
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
			list($uids_conditions, $omit_user) = compileNotUsers($uids_conditions, $thiscon, $uid, $member_handler, false, $entry, $fid);
		} // end of each condition

		// intersect all possible uids with the ones valid for this condition, and handle subscribing necessary users

		$uids_real = compileNotUsers2($uids_conditions, $uids_complete, $notification_handler, $fid, $event, $mid);
		// cannot bug out (return) if $uids_real is empty, since there are still the custom conditions to evaluate below

		// get form object so the title can be used in notification messages
		static $formObjs = array(); // make this static so we don't have to hit the database over again if we've already got this form object
		include_once XOOPS_ROOT_PATH  . "/modules/formulize/class/forms.php";
		if(!isset($formObjs[$fid])) {
			$formObjs[$fid] = new formulizeForm($fid);
		}
		$extra_tags = array();
		if($xoopsUser) {
      $extra_tags['ENTRYUSERNAME'] = $xoopsUser->getVar('uname');
			$extra_tags['ENTRYNAME'] = $xoopsUser->getVar('name') ? $xoopsUser->getVar('name') : $xoopsUser->getVar('uname');
		} else {
			$extra_tags['ENTRYUSERNAME'] = _FORM_ANON_USER;
		}
		$extra_tags['FORMNAME'] = trans($formObjs[$fid]->getVar('title'));
		// determine if this is the profile form and if so, construct the URL for the notification differently
		// so the user goes to the userinfo.php page instead of the form page
		$config_handler =& xoops_gethandler('config');
		$formulizeConfig =& $config_handler->getConfigsByCat(0, $mid);
     	     	$profileFormId = $formulizeConfig['profileForm'];
		if($profileFormId == $fid) {
			$owner = getEntryOwner($entry, $fid);
			$extra_tags['VIEWURL'] = XOOPS_URL."/userinfo.php?uid=$owner";
		} else {
			$extra_tags['VIEWURL'] = XOOPS_URL."/modules/formulize/index.php?fid=$fid&ve=$entry";
		}
		$extra_tags['ENTRYID'] = $entry;			

		if(count($uids_real) > 0) {
			$notification_handler->triggerEvent("form", $fid, $event, $extra_tags, $uids_real, $mid, $omit_user);
		}
		unset($uids_real);

		// handle custom conditions
		foreach($saved_conditions as $thiscon) {
      if($thiscon['not_cons_template']) {
        if(!file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/mail_template/".$thiscon['not_cons_template'].".tpl")) {
          continue;
        } else {
          $templateFileContents = file_get_contents(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/mail_template/".$thiscon['not_cons_template'].".tpl");
          if(strstr($templateFileContents, "{ELEMENT")) {
            // gather the data for this entry and make it available to the template, since it uses an element tag in the message
            if($data === "") {
              include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
              $data = getData("", $fid, $entry); 
            }
            // get all the element IDs for the current form
            $form_handler = xoops_getmodulehandler('forms', 'formulize');
            $formObject = $form_handler->get($fid);
            foreach($formObject->getVar('elementHandles') as $elementHandle) {
              $extra_tags['ELEMENT'.strtoupper($elementHandle)] = html_entity_decode(displayTogether($data[0], $elementHandle, ", "), ENT_QUOTES);
              $extra_tags['ELEMENT_'.strtoupper($elementHandle)] = $extra_tags['ELEMENT'.strtoupper($elementHandle)]; // for legacy compatibility, we provide both with and without _ keys in the extra tags array.
            }
          }
        }
      }
			$uids_cust_con = array();
			list($uids_cust_con, $omit_user) = compileNotUsers($uids_cust_con, $thiscon, $uid, $member_handler, true, $entry, $fid);
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

function formulize_getUsersByGroups($groups, $member_handler="") {

	if(!$member_handler) {
		$member_handler =& xoops_gethandler('member');
	}

	$users = array();
	foreach($groups as $group) {
		if($group == XOOPS_GROUP_USERS) { continue; }
		$temp_users = $member_handler->getUsersByGroup($group);
		$users = array_merge($users, (array)$temp_users);
		unset($temp_users);
	}
	return array_unique($users);
}

// this function can be called from within a loop, and will merge uids_conditions with all previously recorded values
function compileNotUsers($uids_conditions, $thiscon, $uid, $member_handler, $reinitialize=false, $entry, $fid) {
	static $omit_user = null;
	if($reinitialize) { $omit_user = null; } // need to do this when handling saved conditions, since each time we call this function it's a new "event" that we're dealing with
	if($thiscon['not_cons_uid'] > 0) {
		$uids_conditions[] = $thiscon['not_cons_uid'];
	} elseif($thiscon['not_cons_curuser'] > 0) {
		$uids_conditions[] = $uid;
	} elseif($thiscon['not_cons_groupid'] > 0) {
		$uids_temp = $member_handler->getUsersByGroup($thiscon['not_cons_groupid']);				
		$uids_conditions = array_merge((array)$uids_temp, $uids_conditions);
		unset($uids_temp);
	} elseif($thiscon['not_cons_creator'] > 0) {
    $uids_temp = getEntryOwner($entry, $fid);
    $uids_conditions[] = $uids_temp;
    unset($uids_temp);
  } elseif($thiscon['not_cons_elementuids'] > 0) { // get the entry at issue and extract the uids from the specified element
    $data_handler = new formulizeDataHandler($fid);
    $value = getElementValueInEntry($entry, intval($thiscon['not_cons_elementuids']));
    if($value) {
      $uids_temp = explode("*=+*:", $value);
      $uids_conditions = array_merge((array)$uids_temp, $uids_conditions);
    }
    unset($uids_temp);
  } elseif($thiscon['not_cons_linkcreator'] > 0) { // get the entry at issue and extract the uid(s) of the creator(s) of the items selected in the specified element
    $data_handler = new formulizeDataHandler($fid);
    $value = getElementValueInEntry($entry, intval($thiscon['not_cons_linkcreator'])); // get the values in the linked fields
    $entry_ids = explode(",", trim($value, ",")); // the entry ids (in their source form) of the items selected in the linked selectbox, should always be an array of at least one value
    if(count($entry_ids) > 0) {
      // need to get the form that 'not_cons_linkcreator' is linked to
      $element_handler =& xoops_getmodulehandler('elements', 'formulize');
      $elementObject = $element_handler->get(intval($thiscon['not_cons_linkcreator']));
      $linkProperties = explode("#*=:*", $elementObject->getVar('ele_value')); // key 0 will be the form id that is the source for the values in this linked selectbox
      $data_handler2 = new formulizeDataHandler($linkProperties[0]);
      $uids_temp = getAllUsersForEntries($entry_ids);
      if(count($uids_temp) > 0) {
        $uids_conditions = array_merge($uids_temp, $uids_conditions); // not need for type hint (array) in this case because getAllUsersForEntries always returns an array, even if its empty
      }
      unset($uids_temp);
    } else {
      $uids_conditions = array();
    }
  }
  if(in_array($uid, $uids_conditions)) { // in Formulize, users are always notified of things, even things they do themselves.
    $omit_user = 0;
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

// this function takes a series of columns and gets the headers for them
function getHeaders($cols, $frid="", $colsIsElementHeaders = false) {
	global $xoopsDB;
  
	foreach($cols as $col) {
		if($col == "creation_uid") {
			$headers[] = _formulize_DE_CALC_CREATOR;
		} elseif($col == "mod_uid") {
			$headers[] = _formulize_DE_CALC_MODIFIER;
		} elseif($col=="creation_datetime") {
			$headers[] = _formulize_DE_CALC_CREATEDATE;
		} elseif($col=="mod_datetime") {
			$headers[] = _formulize_DE_CALC_MODDATE;
		} elseif($col=="creator_email") {
			$headers[] = _formulize_DE_CALC_CREATOR_EMAIL;
		} elseif($frid) {
          $headers[] = getCaption($frid, $col, true, true);
       	} else {
          if($colsIsElementHeaders) {
            $whereClause = "ele_handle = '$col'";
          } else {
            $whereClause = "ele_id = '$col'";
          }
       		$temp_cap = q("SELECT ele_caption, ele_colhead FROM " . $xoopsDB->prefix("formulize") . " WHERE $whereClause"); 
			if($temp_cap[0]['ele_colhead'] != "") {
				$headers[] = $temp_cap[0]['ele_colhead'];
			} else {
	       		$headers[] = $temp_cap[0]['ele_caption'];
			}
       	}
	}
	return $headers;
}

// THIS FUNCTION OVERWRITES OR APPENDS TO A VALUE IN A SPECIFIED FORM ELEMENT
// Formerly located in pageworks/include/functions.php
//function writeElementValue($ele, $entry, $value, $append, $prevValue) {
// DEPRECATED...VERY INEFFICIENT, SINCE IT ONLY UPDATES ONE FIELD AT A TIME.  BETTER TO USE formulize_writeEntry, except in cases where you actually need to only update one field.  In most cases you want to update multiple fields in an entry, so don't use this inside a loop...it will generate more queries than you need
// prevValue is now completely not required.  lvoverride is only used if you want to pass in a pre-formatted ,1,3,15,17, style string for inserting into a linked selectbox field.
function writeElementValue($formframe = "", $ele, $entry, $value, $append, $prevValue=null, $lvoverride=false) {

/*
print "$formframe<br>";
print "$ele<br>";
print "$entry<br>";
print "$value<br>";
print "$append<br>";
print "$prevValue<br><br>";
*/

        //require_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";

	if(get_magic_quotes_gpc()) { $value = stripslashes($value); }

	global $xoopsUser, $formulize_mgr, $xoopsDB, $myts;
	if(!is_object($myts)) { $myts =& MyTextSanitizer::getInstance(); }

	if(!$formulize_mgr) {
		$formulize_mgr =& xoops_getmodulehandler('elements', 'formulize');
	}

	$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
	$date = date ("Y-m-d");

	if(is_numeric($ele)) {
  		$element =& $formulize_mgr->get($ele);
      $element_id = $ele;
  } else {
      $framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
      $frameworkObject = $framework_handler->get($formframe);
      $frameworkElementIds = $frameworkObject->getVar('element_ids');
      $element_id = $frameworkElementIds[$ele];
  		$element =& $formulize_mgr->get($element_id);
  }

  	$ele_value = $element->getVar('ele_value');

    //echo "$ele, $entry, $value, $append, $prevValue " . (strstr($ele_value[2], "#*=:*") ? "t" : "f" ) . "<br>";
      if(!is_array($value)) { // value can be an array of multiple values -- initially that only worked for linked selectboxes
        if($element->getVar('ele_type') == "yn") {
          $value = strtoupper($value) == strtoupper(_formulize_TEMP_QYES) ? 1 : $value;
          $value = strtoupper($value) == strtoupper(_formulize_TEMP_QNO) ? 2 : $value;
        } else {
          $value = $myts->htmlSpecialChars($value);
        }
      } else {
        foreach($value as $id=>$thisValue) {
          if($element->getVar('ele_type') == "yn") {
            $value[$id] = strtoupper($value[$id]) == strtoupper(_formulize_TEMP_QYES) ? 1 : $value[$id];
            $value[$id] = strtoupper($value[$id]) == strtoupper(_formulize_TEMP_QNO) ? 2 : $value[$id];
          } else {
            $value[$id] = $myts->htmlSpecialChars($value[$id]);
          }
        }
      }

    	if($foundit = strstr($ele_value[2], "#*=:*") AND !$lvoverride) { // completely rejig things for a linked selectbox
          $boxproperties = explode("#*=:*", $ele_value[2]);
          // NOTE:
          // boxproperties[0] is fid, 1 is the handle
	        if(!is_array($value)) { // convert $value to an array if it's not already...arrays are only valid for linked selectboxes for now
            $temp_value = $value;
            unset($value);
            $value = array(0=>$temp_value);
          }
          static $cachedEntryIds = array();
          $foundEntryIds = array();
          $searchForValues = array();
          foreach($value as $thisValue) {
            if(isset($cachedEntryIds[$boxproperties[0]][$boxproperties[1]][$thisValue])) {
              $foundEntryIds[] = $cachedEntryIds[$boxproperties[0]][$boxproperties[1]][$thisValue];
            } else {
              $searchForValues[] = $thisValue;
            }
          }
          if(count($searchForValues) > 0) {
              $entry_id_q = q("SELECT `entry_id`, `".$boxproperties[1]."` FROM " . $xoopsDB->prefix("formulize_".$boxproperties[0]) . " WHERE `".$boxproperties[1]."` = '".implode("' OR `".$boxproperties[1]."` = '", $searchForValues) . "'");
              foreach($entry_id_q as $thisEntryId) {
                $cachedEntryIds[$boxproperties[0]][$boxproperties[1]][$thisEntryId[$boxproperties[1]]] = $thisEntryId['entry_id'];
                $foundEntryIds[] = $thisEntryId['entry_id'];
              }
          }
          if(count($foundEntryIds)>0) {
            $foundEntryIdString = ",".implode(",", $foundEntryIds).",";
          } else {
            $foundEntryIdString = "";
          }
          unset($value);
          $value = $foundEntryIdString;
          $append = "replace";
        }
        
        $lockIsOn = false;
        if(($value == "{ID}" AND $entry == "new") OR $value == "{SEQUENCE}") {
                  $lockIsOn = true;
                  $xoopsDB->query("LOCK TABLES ".$xoopsDB->prefix("formulize_".$element->getVar('id_form'))." WRITE"); // need to lock table since there are multiple operations required on it for this one write transaction
                  $fromField = $value == "{ID}" ? "entry_id" : $element->getVar('ele_handle');
                  $maxValueSQL = "SELECT MAX(`$fromField`) FROM " . $xoopsDB->prefix("formulize_".$element->getVar('id_form'));
                  if($maxValueRes = $xoopsDB->query($maxValueSQL)) {
                    $maxValueArray = $xoopsDB->fetchArray($maxValueRes);
                    $value = $maxValueArray["MAX(`$fromfield`)"] + 1;
                  } else {
                    exit("Error: could not determine max value to use for $value.  SQL:<br>$maxValueSQL<br>");
                  }
        } elseif($value == "{ID}" AND $entry != "new") {
          $value = $entry;
        }
        
        
        $needToSetOwner = false;
        if($entry == "new") { // making a new entry...
                $owner = is_numeric($append) ? $append : $uid; // for new entries, a numeric "action" indicates an owner for the entry that is different from the current user, ie: this is a proxy entry
                // no handling as yet for an array of values, which would be required for replacing the selections in a checkbox series or selectbox series.
                // radio buttons would also need to be massaged?
                $sql="INSERT INTO ".$xoopsDB->prefix("formulize_".$element->getVar('id_form'))." (creation_datetime, mod_datetime, creation_uid, mod_uid, `".$element->getVar('ele_handle')."`) VALUES (NOW(), NOW(), \"$owner\", \"$uid\", '".mysql_real_escape_string($value)."')";
                $needToSetOwner = true;
        } else {
          // not new entry, so update the existing entry
          if($append=="remove") {
            $prevValue = q("SELECT `".$element->getVar('ele_handle')."` FROM ".$xoopsDB->prefix("formulize_".$element->getVar('id_form'))." WHERE entry_id=".intval($entry));
            if(strstr($prevValue[0]['ele_value'], "*=+*:")) {
                    $valueToWrite = str_replace("*=+*:" . $value, "", $prevValue[0]['ele_value']);
            } else {
                    $valueToWrite = str_replace($value, "", $prevValue[0]['ele_value']);
            }
          } elseif($append=="append") {
            $prevValue = q("SELECT `".$element->getVar('ele_handle')."` FROM ".$xoopsDB->prefix("formulize_".$element->getVar('id_form'))." WHERE entry_id=".intval($entry));
            switch($element->getVar('ele_type')) {
                    case "checkbox":
                            $valueToWrite = $prevValue[0]['ele_value'] . "*=+*:" . $value;
                            break;	
                    case "select":
                            if($ele_value[1]) { // multiple selections possible
                                    $valueToWrite = $prevValue[0]['ele_value'] . "*=+*:" . $value;
                            } else { // cannot append to dropdowns
                                    $valueToWrite = $value;
                            }
                            break;
                    case "yn": // cannot append to yn
                    case "date": // cannot append to date
                    case "radio": // cannot append to radios
                            $valueToWrite = $value;
                            break;
                    case "text": 
                    case "textarea":
                            $valueToWrite = $prevValue[0]['ele_value'] . $value;
                            break;
                    default:
                            exit("Error: unknown type of element used in a call to displayButton");
            }	
          } else { // append == "replace" or all other settings for append
              $valueToWrite = $value;
          }
          $sql = "UPDATE ".$xoopsDB->prefix("formulize_".$element->getVar('id_form'))." SET `".$element->getVar('ele_handle')."` = '".mysql_real_escape_string($valueToWrite)."' WHERE entry_id=".intval($entry);
        }
        if($sql) { // run the query
                //print $sql . "<br>";
                if(!$res = $xoopsDB->queryF($sql)) {
                        exit("Error: unable to execute a \"displayButton\" or writeElementValue call, using the following SQL:<br>$sql<br>" . $xoopsDB->error);
                }
                $GLOBALS['formulize_writeElementValueWasRun'] = true;
        }
    if($lockIsOn) { $xoopsDB->query("UNLOCK TABLES"); }

    if($entry == "new") {
      $insertedId = $xoopsDB->getInsertId();
    }
    
    if($needToSetOwner) {
      include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
      $data_handler = new formulizeDataHandler($element->getVar('id_form'));
      if(!$groupResult = $data_handler->setEntryOwnerGroups($owner, $insertedId)) {
				print "ERROR: failed to write the entry ownership information to the database.<br>";
			}
    }
  

// handle notifications
// get the form ID of the form based on the element id
switch($entry) {
  case "new":
	sendNotifications($element->getVar('id_form'), "new_entry", array(0=>$insertedId));
	break;
  default:
	sendNotifications($element->getVar('id_form'), "update_entry", array(0=>$entry));
	break;
}
	unset($element);

	return $entry == "new" ? $insertedId : $entry;
}


// THIS FUNCTION READS ALL THE FILES IN A DIRECTORY AND RETURNS THEIR NAMES IN AN ARRAY
// use the filter param to include only files containing a certain string in their names
function formulize_scandirAndClean($dir, $filter="", $timeWindow=21600) {
	  return true;
		if(!$filter) { return false; } // filter must be present
	
		$currentTime = time();
		$targetTime = $currentTime - $timeWindow;
	
		// if it's PHP 5, then do this:
		if(version_compare(PHP_VERSION, '5.0.0')) { // native scandir in PHP is much faster!!!
			foreach(scandir($dir) as $fileName) {
				 if (strstr($fileName, $filter) AND filemtime($dir.$fileName) < $targetTime) {
						unlink($dir.$fileName);
				 }
			}
		} else {
			// if it's PHP 4, then do this:
			if ($handle = opendir($dir)) {
					while (false !== ($file = readdir($handle))) {
							$fileName = basename($file);
							if (strstr($fileName, $filter) AND filemtime($file) < $targetTime) {
								  unlink($file);
							}
					}
					closedir($handle);
			}
			
		}
		return true;

}



// THIS FUNCTION TAKES AN ARRAY WHERE THE KEYS ARE ELEMENT IDS AND THE VALUES ARE VALUES, AND IT WRITES THEM ALL TO A SPECIFIED ENTRY OR A NEW ENTRY
// originally, only $values and $entry were required
// $proxyUser, if present, is meant to override the current $xoopsUser uid value
// $action is deprecated
// $forceUpdate will cause queryF to be used in the data handler, which will allow updates on a get request
// $writeOwnerInfo causes the entry_owner_groups table to be updated when a new entry is written
// NOTE: $values takes ID numbers as keys, since that's how the datahandler expects things
function formulize_writeEntry($values, $entry="new", $action="replace", $proxyUser=false, $forceUpdate=false, $writeOwnerInfo=true) {
  
  // get the form id from the element id of the first value in the values array
  $element_handler = xoops_getmodulehandler('elements', 'formulize');
  $elementObject = $element_handler->get(key($values));
  if(is_object($elementObject)) {
    $data_handler = new formulizeDataHandler($elementObject->getVar('id_form'));
    if($result = $data_handler->writeEntry($entry, $values, $proxyUser, $forceUpdate)) {
      global $xoopsUser;
      if($proxyUser) {
        $ownerForGroups = $proxyUser;
      } elseif($xoopsUser) {
        $ownerForGroups = $xoopsUser->getVar('uid');
      } else {
        $ownerForGroups = 0;
      }
			if($entry == "new" AND $writeOwnerInfo) {
				$data_handler->setEntryOwnerGroups($ownerForGroups, $result); // result will be the ID number of the entry that was just written.
			}
      return $result;
    } else {
      exit("Error: data could not be written to the database for entry $entry in form ". $elementObject->getVar('id_form').".");
    }
  } else {
    exit("Error: invalid element in the value array: ".key($values).".");
  }
} 


// THIS FUNCTION RETURNS A NUMBER BASED ON THE PREVIOUS MAXIMUM NUMBER IN A GIVEN FIELD IN A FORM
function formulize_getMaxValue($cap, $fid) {
  global $xoopsDB;
  $sql = "SELECT ele_value FROM " . $xoopsDB->prefix("formulize_form") . " WHERE ele_caption = \"" . mysql_real_escape_string($cap) . "\" AND id_form = " . intval($fid) . " ORDER BY (ele_value+0) DESC LIMIT 0,1"; // order by field+0 forces the sorting to be based on numeric values, since it performs math on each cell and therefore sorts by the numerical result of that expression
  print "$sql<br>";
  $res = $xoopsDB->query($sql);
  $array = $xoopsDB->fetchArray($res);
  $value = $array['ele_value'];
  print "$value<br>";
  $value = is_numeric($value) ? intval($value) + 1 : $value;
  print "$value<br>";
  return $value;
}

//  THIS FUNCTION SYNCHS ENTRIES WRITTEN IN BLANK DEFAULTS IN A SUBFORM, WITH THE PARENT FORM.  GETS EXECUTED IN FORMDISPLAY.PHP AND FORMDISPLAYPAGES.PHP AFTER A FORM SUBMISSION
function synchSubformBlankDefaults($fid, $entry) {
  // handle creating linked/common values when default blank entries have been filled in on a subform -- sept 8 2007
	$ids_to_return = array();
	if(isset($GLOBALS['formulize_subformCreateEntry'])) {
    foreach($GLOBALS['formulize_subformCreateEntry'] as $sfid=>$sfid_id_reqs) {
      global $xoopsDB;
      // first, figure out the value we need to write in the subform entry
      if($_POST['formulize_subformSourceType_'.$sfid]) { // true if the source is a common value
        $elementPostHandle = "de_".$_POST['formulize_subformValueSourceForm_'.$sfid]."_".$_POST['formulize_subformValueSourceEntry_'.$sfid]."_".$_POST['formulize_subformValueSource_'.$sfid];
        // grab the value from the parent element -- assume that it is a textbox of some kind!
        if (isset($_POST[$elementPostHandle])) {
          $value_to_write = $_POST[$elementPostHandle]; // get the value right out of the posted submission if it's present
        } else {
          // get this entry and see what the source value is
          $data_handler = new formulizeDataHandler($_POST['formulize_subformValueSourceForm_'.$sfid]);
          $value_to_write = $data_handler->getElementValueInEntry($_POST['formulize_subformValueSourceEntry_'.$sfid], $_POST['formulize_subformValueSource_'.$sfid]);
        }
      } else {
        $value_to_write = ",$entry,";
      }
      // actually write the linked/common values...
      foreach($sfid_id_reqs as $id_req_to_write) {
        writeElementValue($sfid, $_POST['formulize_subformElementToWrite_'.$sfid], $id_req_to_write, $value_to_write, "replace", "", true); // Last param is override that allows direct writing to linked selectboxes if we have prepped the value first!
        $ids_to_return[$sfid][] = $id_req_to_write; // add the just synched up entry to the list of entries in the subform
      }
		}
	}
  unset($GLOBALS['formulize_subformCreateEntry']); // unset so this function only runs once
  return $ids_to_return;
}  

// THIS FUNCTION TAKES SOME TEXT AND REPLACES CARRIAGE RETURNS WITH <BR> TAGS FOR OUTPUT TO THE SCREEN, IF THE $handleid IS A TEXTAREA BOX
function formulize_replaceLineBreaks($value, $handleid, $frid) {
	if($handleid == "uid" OR $handleid=="proxyid" OR $handleid=="creation_date" OR $handleid == "mod_date" OR $handleid == "creator_email" OR $handleid == "creation_uid" OR $handleid == "mod_uid" OR $handleid == "creation_datetime" OR $handleid == "mod_datetime") { return $value; }
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
	if(!is_object($element)) { return $value; }
	$ele_type = $element->getVar('ele_type');
	if($ele_type != "textarea") { return $value; }
	$value = str_replace("\n", "<br>", $value);
	return $value;
}

// internal function that retrieves an element object if necessary
	function _getElementObject($element) {
		if(is_object($element)) {
			if(get_class($element) != "formulizeformulize") { // the silly historical name of the element class
				return false;
			} else {
				return $element;
			}
		} elseif(is_numeric($element)) {
			$element_handler =& xoops_getmodulehandler('elements', 'formulize');
			$element = $element_handler->get($element);
			if(!is_object($element)) {
				return false;
			}	else {
				return $element;
			}
		} else {
			return false;
		}
	}


// this function takes element handles and converts them to framework handles
function convertElementHandlesToFrameworkHandles($handles, $frid) {
	$elementsToFrameworks = true;
	$idsToFrameworks = false;
	return convertAllHandlesAndIds($handles, $frid, $elementsToFrameworks, $idsToFrameworks); // true is a "Reverse" flag that changes how the function works
}

function convertFrameworkHandlesToElementHandles($handles, $frid) {
	$elementsToFrameworks = false;
	$idsToFrameworks = false;
	return convertAllHandlesAndIds($handles, $frid, $elementsToFrameworks, $idsToFrameworks);
}

function convertElementIdsToFrameworkHandles($ids, $frid) {
	$elementsToFrameworks = false;
	$idsToFrameworks = true;
	return convertAllHandlesAndIds($ids, $frid, $elementsToFrameworks, $idsToFrameworks);
}

function convertElementIdsToElementHandles($ids, $fid) {
	$elementsToFrameworks = false;
	$idsToFrameworks = false;
	$frid = 0;
	return convertAllHandlesAndIds($ids, $frid, $elementsToFrameworks, $idsToFrameworks, $fid);
}

// assume handles are unique within a framework (which they are supposed to be!)
// reverse flag is used only when this is called from the opposite function, which is really just a wrapper for calling this and asking for things the other way around...element handles converted to framework handles
// This function essentially makes a framework handle/element handle map for the entire framework, and caches it, so once a framework is mapped, we never hit the database again.  Then we just call the function to return the values we are looking for.
// Ids is a flag that will cause framework handles to be returned when element ids are passed
// fid is the form id for use when going from element ids to handles
function convertAllHandlesAndIds($handles, $frid, $reverse=false, $ids=false, $fid=false) {
	
	// reverse means elements to frameworks
	// $ids means return ids from whatever the source is
	// $fid means we're working with a form only (and for now that defaults to returning handles)
	
	static $cachedElementHandles = array();
	static $cachedElementIds = array();
	static $cachedElementHandlesFromElementIds = array();
	
	if(!is_array($handles)) { 
		$temp = $handles;
		unset($handles);
		$handles[0] = $temp;
	}
	$to_return = array();
	if(!isset($cachedElementHandles[$frid])) {
		global $xoopsDB;
		
		$cachedElementHandles[$frid]['creation_uid'] = "creation_uid";
		$cachedElementHandles[$frid]['creation_datetime'] = "creation_datetime";
		$cachedElementHandles[$frid]['mod_uid'] = "mod_uid";
		$cachedElementHandles[$frid]['mod_datetime'] = "mod_datetime";
		$cachedElementHandles[$frid]['creator_email'] = "creator_email";
    $cachedElementHandles[$frid]['uid'] = "creation_uid"; // must put these deprecated ones last, so that searches through the cached values will find the true values first
    $cachedElementHandles[$frid]['creation_date'] = "creation_datetime";
		$cachedElementHandles[$frid]['proxyid'] = "mod_uid";
		$cachedElementHandles[$frid]['mod_date'] = "mod_datetime";

    // for this first time through, we need to add these to "to_return" if necessary, since they will only be picked up from these arrays on subsequent queries
    if(in_array("creation_uid",$handles)) { $to_return[] = "creation_uid"; }
    if(in_array("uid",$handles)) { $to_return[] = "creation_uid"; }
    if(in_array("creation_datetime",$handles)) { $to_return[] = "creation_datetime"; }
    if(in_array("creation_date",$handles)) { $to_return[] = "creation_datetime"; }
    if(in_array("mod_uid",$handles)) { $to_return[] = "mod_uid"; }
    if(in_array("proxyid",$handles)) { $to_return[] = "mod_uid"; }
    if(in_array("mod_date",$handles)) { $to_return[] = "mod_datetime"; }
    if(in_array("mod_datetime",$handles)) { $to_return[] = "mod_datetime"; }
    if(in_array("creator_email",$handles)) { $to_return[] = "creator_email"; }

		$cachedElementIds[$frid] = $cachedElementHandles[$frid];
		if($fid) {
			$cachedElementHandlesFromElementIds[$fid] = $cachedElementHandles[$frid];	
		}
		
		// now get all the rest of the handles
		if($fid) {
			$idHandleQuery = q("SELECT ele_handle, ele_id FROM ".$xoopsDB->prefix("formulize") . " WHERE id_form=".intval($fid));
		} else {
			$idHandleQuery = q("SELECT t2.ele_handle, t1.fe_handle, t2.ele_id FROM " . $xoopsDB->prefix("formulize_framework_elements") . " as t1, " . $xoopsDB->prefix("formulize") . " as t2 WHERE t1.fe_frame_id='$frid' AND t1.fe_element_id=t2.ele_id");						
		}
		foreach($idHandleQuery as $thisIdRow) {
			if($fid) {
				$cachedElementHandlesFromElementIds[$fid][$thisIdRow['ele_id']] = $thisIdRow['ele_handle'];
			} else {
				$cachedElementHandles[$frid][$thisIdRow['fe_handle']] = $thisIdRow['ele_handle'];
				$cachedElementIds[$frid][$thisIdRow['ele_id']] = $thisIdRow['fe_handle'];	
			}
			
			// populate the to return array, to save us going through all the handles again, since we're doing that right now
			// use array_search and assign the values to the same position in the return array, to preserve order
			if($fid) {
				$foundKey = array_search($thisIdRow['ele_id'], $handles);
				if($foundKey !== false) {
					$to_return[$foundKey] = $thisIdRow['ele_handle'];
				}
			} elseif($ids) {
				$foundKey = array_search($thisIdRow['ele_id'],$handles); // handles could be an array of ids
				if($foundKey !== false) { 
					$to_return[$foundKey] = $thisIdRow['fe_handle'];	
				}
			} else {
				if($reverse) { // element handles to framework handles
					$foundKey = array_search($thisIdRow['ele_handle'],$handles);
					if($foundKey !== false) {
						$to_return[$foundKey] = $thisIdRow['fe_handle'];	
					}
				} else { // framework handles to element handles
					$foundKey = array_search($thisIdRow['fe_handle'],$handles); // if this is a handle we're being asked for
					if($foundKey !== false) { 
						$to_return[$foundKey] = $thisIdRow['ele_handle'];
					}
				}	
			}
		}
    ksort($to_return); // to_return is built with the keys from $handles, but in an arbitrary order depending on the order the elements were returned in the DB query above, so we need to put them into the correct order here to correspond with $handles
	}
	if(count($to_return)==0) { // if to_return was not set already, ie: when doing a database query, then loop through handles to get the values we need from the cached values array
		foreach($handles as $handle) {
			if($fid) {
				$to_return[] = $cachedElementHandlesFromElementIds[$fid][$handle];
			} elseif($ids) {
				$to_return[] = $cachedElementIds[$frid][$handle];
			} else {
				if($reverse) {
					$to_return[] = array_search($handle,$cachedElementHandles[$frid]);	// handle is an element handle, return key corresponding to this value in the cached handles array
				} else {
					$to_return[] = $cachedElementHandles[$frid][$handle];		// handle is a framework handle, so return corresponding element handle from array
				}
			}
		}	
	}
	return $to_return;
}

// THIS FUNCTION ACTUALLY BUILDS THE SELECT FORM ELEMENT AND RETURNS IT AS A STRING
// Used to create a drop down list that can act as a filter in a user interface
// The dropdown list is made up of the options for the specified ele_id
// If the dropdown list is a linked selectbox, the values can be optionally limited by the "limit" params, based on values in another field in each entry that underlies the link
// ie: build a filter with the names of all activity entries, but limit it to activity entries where the date of the activity is 2007
function buildFilter($id, $ele_id, $defaulttext="", $name="", $overrides=array(0=>""), $subfilter=false, $linked_ele_id = 0, $linked_data_id=0, $limit=false) { 

	// Changes made to allow the linking of one filter to another. This is acheieved as follows:
	// 1. Create a formulize form for managing the Main Filter List (form M)
	// 2. Create a formulize form for managing the Sub Filter list (form S), which includes a linked element to the data in form M, 
	//    so that relation between the Main Filter & Sub Filter data can be specified 
	// 3. Create a formulize form for the data that the Main & SubFilter act upon (form D)
	///
	// In such a case, the parameters have the following meaning:
	//  - $id is the element id of the field to be filtered in Form D
	//  - $ele_id is also the element id of the field to be filtered in Form D
	//  - $subfilter specifies if this filter is a subfilter
	//  - $linked_ele_id specifies the ele_id of the Main Filter field as it appears in Form S
	//  - $linked_data_id specifies the ele_id of the Main Filter field as it appears in Form D

	/* limit params work as follows: (a limit is some property of a field in the source entry in a linked selectbox)
	$limit = false, or if used then it's an array with these params...
	'ele_id' = the id of the element to pay attention to for the limit condition
	'term' = the term used to build the condition
	'operator' = the operator used to build the condition
	*/
	
	// limits are very similar to subfilters in their effect, but subfilters are meant for situations where one filter influences another filter
	// subfilters are kind of like dynamic limits, where the limit condition is not specified until the parent filter is chosen.

	global $xoopsDB;		// required by q
	$filter = "<SELECT name=\"$id\" id=\"$id\"";
	if($name == "{listofentries}") {
		$filter .= " onchange='javascript:showLoading();'"; // list of entries has a special javascript thing
	} elseif($name) {
		$filter .= " onchange='javascript:document.$name.submit();'";
	}
	$filter .= ">\n";
	
	if ($subfilter AND !(isset($_POST[$linked_data_id])) AND !(isset($_GET[$linked_data_id])))  { 
		// If its a subfilter and the main filter is unselected, then put in 'Please select from above options first
		$filter .= "<option value=\"none\">Please select a primary filter first</option>\n"; 
		}
	else {
		// Either it is not a subfilter, or it is a subfilter with the linked values set
		$defaulttext = $defaulttext ? $defaulttext: _AM_FORMLINK_PICK;
		if($name == "{listofentries}") {
			$filter .= "<option value=\"\">".$defaulttext."</option>\n"; // must not pass back a value when we're putting a filter on the list of entries page
		} else {
			$filter .= "<option value=\"none\">".$defaulttext."</option>\n";
		}

    $form_element = q("SELECT ele_value, ele_type FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_id = " . $ele_id);
    $element_value = unserialize($form_element[0]["ele_value"]);
	switch($form_element[0]["ele_type"]) {
		case "select":
			$options = $element_value[2];
			break;
		case "radio":
		case "checkbox":
			$options = $element_value;
			break;
	}

	// if the $options is from a linked selectbox, then figure that out and gather the possible values
	// only linked selectboxes have this string in their options field
	if(strstr($options, "#*=:*")) {
		$boxproperties = explode("#*=:*", $options);
		$source_form_id = $boxproperties[0];
		$source_element_handle = $boxproperties[1];
		
		// process the limits
		$limitCondition = "";
		if(is_array($limit)) {
			$limitCondition = $limit['ele_id'] . "/**/" . $limit['term'];
			$limitCondition .= isset($limit['operator']) ? "/**/" . $limit['operator'] : "";
		}
		
			if (!$subfilter) {
		$data = getData("", $source_form_id, $limitCondition);
				}
			else {
				$getDataFilter .= $linked_ele_id . "/**/" . $_POST[$linked_data_id];
				$getDataFilter .= $limitCondition ? "][" . $limitCondition : "";
				$data = getData("", $source_form_id, $getDataFilter);
				}
		unset($options);
		foreach($data as $entry) {
			$option_text = display($entry, $source_element_handle);
			$options[$option_text] = ""; // it's the key that gets used in the loop below
		}
	}
	
	$nametype = "";
	if(key($options) === "{FULLNAMES}" OR key($options) === "{USERNAMES}") { // code copied from elementrender.php to make fullnames work for Drupalcamp demo
		if(key($options) === "{FULLNAMES}") { $nametype = "name"; }
		if(key($options) === "{USERNAMES}") { $nametype = "uname"; }
		$pgroups = array();
		if($element_value[3]) {
			$scopegroups = explode(",",$element_value[3]);
			global $xoopsUser;
			$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
			if(!in_array("all", $scopegroups)) {
				if($element_value[4]) { // limit by users's groups
					foreach($groups as $gid) { // want to loop so we can get rid of reg users group simply
						if($gid == XOOPS_GROUP_USERS) { continue; }
						if(in_array($gid, $scopegroups)) {
							$pgroups[] = $gid;
						}
					}
					if(count($pgroups) > 0) { 
						unset($groups);
						$groups = $pgroups;
      				} else {
      					$groups = array();
      				}
      			} else { // don't limit by user's groups
					$groups = $scopegroups;
				}
			} else { // use all
				if(!$element_value[4]) { // really use all (otherwise, we're just going will all user's groups, so existing value of $groups will be okay
					unset($groups);
					global $xoopsDB;
					$allgroupsq = q("SELECT groupid FROM " . $xoopsDB->prefix("groups") . " WHERE groupid != " . XOOPS_GROUP_USERS);
					foreach($allgroupsq as $thisgid) {
						$groups[] = $thisgid['groupid'];
					} 
				}
			}
			$options = array();
			$namelist = gatherNames($groups, $nametype);
			foreach($namelist as $auid=>$aname) {
				$options[$aname] = $auid; // backwards to how elementrenderer.php does it, since logic below to build list is different
			}
		}
	}

    if($name != "{listofentries}") { ksort($options); }

    $counter = 0;
    foreach($options as $option=>$option_value) {
      if(is_array($overrides) AND isset($overrides[$option])) {
          $selected = ($_POST[$id] == $option OR $_GET[$id] == $option) ? "selected" : ""; 
          $filter .= "<option value=\"" . $overrides[$option][1] . "\" $selected>" . $overrides[$option][0] . "</option>\n";
      } else {
        if(preg_match('/\{OTHER\|+[0-9]+\}/', $option)) { $option = str_replace(":", "", _formulize_OPT_OTHER); }
        $passoption = $nametype ? $option_value : $option; // str_replace(" ", "_", $option); // if a nametype is in effect, then use the value, otherwise, use the key -- also, no longer swapping out spaces for underscores
        if((isset($_POST[$id]) OR isset($_GET[$id])) AND $overrides !== false) {
          if($name == "{listofentries}") {
            $selected = (is_numeric($overrides) AND $overrides == $counter) ? "selected" : "";
          } else {
            $selected = ($_POST[$id] == $passoption OR $_GET[$id] == $passoption) ? "selected" : "";
          }
        } else {
          $selected = "";
        }
        if($name == "{listofentries}") { $passoption = "qsf_".$counter."_$passoption"; } // need to pass this stupid thing back because we can't compare the option and the contents of $_POST...a typing problem in PHP??!!
	      $filter .= "<option value=\"$passoption\" $selected>$option</option>\n";
      }
      $counter++;
    }
    
	}
	$filter .= "</SELECT>\n";

	return $filter;
}

// THIS FUNCTION TAKES A VALUE AND THE UITEXT FOR THE ELEMENT, AND RETURNS THE UITEXT IN PLACE OF THE "DATA" TEXT
function formulize_swapUIText($value, $uitexts) {
  // if value is an array, it has a key called 'value', which needs to be swapped
  if(is_array($value)) {
    $value['value'] = isset($uitexts[$value['value']]) ? $uitexts[$value['value']] : $value['value'];
  } else {
    $value = isset($uitexts[$value]) ? $uitexts[$value] : $value;
  }
  return $value;
}

// formats numbers according to options users have specified
// decimalOverride is used to provide decimal values if specified format has no decimals (added for use in calculations)
function formulize_numberFormat($value, $handle, $frid="", $decimalOverride=0) {
	if(!is_numeric($value)) { return $value; }
	if($frid) {
    $resultArray = formulize_getElementHandleAndIdFromFrameworkHandle($handle, $frid);
    $id = $resultArray[1];
  } else {
    $id = formulize_getIdFromElementHandle($handle);
  }
	$elementMetaData = formulize_getElementMetaData($id, false);
	if($elementMetaData['ele_type'] == "text") {
		$ele_value = unserialize($elementMetaData['ele_value']);
		return _formulize_numberFormat($value, $decimalOverride, $ele_value[5], isset($ele_value[7]), $ele_value[7], isset($ele_value[8]), $ele_value[8], isset($ele_value[6]), $ele_value[6]); // value, decimaloverride, decimals, decsep exists, decsep, sep exists, sep, prefix exists, prefix
	} elseif($elementMetaData['ele_type'] == "derived") {
		$ele_value = unserialize($elementMetaData['ele_value']);
		return _formulize_numberFormat($value, $decimalOverride, $ele_value[1], isset($ele_value[3]), $ele_value[3], isset($ele_value[4]), $ele_value[4], isset($ele_value[2]), $ele_value[2]); // value, decimaloverride, decimals, decsep exists, decsep, sep exists, sep, prefix exists, prefix    if(($ele_value[1] === "" OR !isset($ele_value[1])) AND $decimalOverride) { $ele_value[1] = $decimalOverride; }
	}	else {
		return $value;
	}
}

// internal function used by formulize_numberFormat to actually do the formatting
// different element types have different parts of ele_value where the number values are stored, so that's the reason for abstracting this out one level
function _formulize_numberFormat($value, $decimalOverride, $decimals="", $decSepExists=false, $decsep="", $sepExists=false, $sep="", $prefixExists=false, $prefix="") {
	$config_handler =& xoops_gethandler('config');
	$formulizeConfig =& $config_handler->getConfigsByCat(0, getFormulizeModId());
	if($decimalOverride) {
		$decimals = $decimalOverride; // use the override if it's present
	} elseif(!is_numeric($decimals)) { // if there is no decimal value passed in for this element
		$decimals = isset($formulizeConfig['number_decimals']) ? $formulizeConfig['number_decimals'] : 0; // or else use the module pref, and if there isn't one, use 0
	}
	if($decsep == "" AND !$decSepExists) {
		$decsep = isset($formulizeConfig['number_decimalsep']) ? $formulizeConfig['number_decimalsep'] : "."; 
	}
	if($sep == "" AND !$sepExists) {
		$sep = isset($formulizeConfig['number_sep']) ? $formulizeConfig['number_sep'] : ","; 
	}
	if($prefix == "" AND !$prefixExists) {
		$prefix = isset($formulizeConfig['number_prefix']) ? $formulizeConfig['number_prefix'] : ""; // if no prefix actually is specified for the element, then use module pref if one is set, otherwise use ""
	}
	return $prefix . number_format($value, $decimals, $decsep, $sep);
}


// This function will print out the specific calculation requested, from the saved view specified
// $savedView can be the id number of a saved view, or it can be the typed name
// $formframe is the framework or form id, and $mainform is the form id if framework is used
// $handle is the element that you want to get the calculation for, or elements (can be an array or handles)
// $type is the type of calculation for that element that you want to get, default is get all types
// $grouping is the grouping option you want to get for that element/type pair, default is all grouping options

// example of how you can traverse the result array:
/*
 
// Function to display data from saved view with formatting
// $calcs is the result of a call to the formulize_getCalcs function
function printCalcResult($calcs) {
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	foreach($calcs as $handle=>$thisCalcData) {
		$elementObject = $element_handler->get($handle); // only works when handle and ele_id are the same
		$caption = $elementObject->getVar('ele_caption');
		print "<h3>$caption</h3>\n";
		foreach($thisCalcData as $type=>$results) {
			if(count($results)>1) {
				foreach($results as $result) {
					print "<p><b>Grouped By: ".implode(",",$result['grouping'])."</b></p>\n";
					print $result['result'];
				}
			}	 
			else {
				print $results[0]['result'];
			}
		}
	}
}
*/

// NOTE, THIS FUNCTION ASSIGNS IDS INSTEAD OF HANDLES TO RESULT ARRAY.  THIS NEEDS TO BE LOOKED INTO


function formulize_getCalcs($formframe, $mainform, $savedView, $handle="all", $type="all", $grouping="all") {

  list($fid, $frid) = getFormFramework($formframe, $mainform);
  
  static $cachedResults = array();
  if(!isset($cachedResults[$frid][$fid][$savedView])) {
  
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/entriesdisplay.php";
  
    foreach($_POST as $k=>$v) {
      if(substr($k, 0, 7) == "search_") {
        unset($_POST[$k]);
      }
    }
    // load the saved view requested, and get everything ready for calling gatherDataSet
    list($_POST['currentview'], $_POST['oldcols'], $_POST['asearch'], $_POST['calc_cols'], $_POST['calc_calcs'], $_POST['calc_blanks'], $_POST['calc_grouping'], $_POST['sort'], $_POST['order'], $_POST['hlist'], $_POST['hcalc'], $_POST['lockcontrols'], $quicksearches) = loadReport($savedView, $fid, $frid);
    // explode quicksearches into the search_ values
    $allqsearches = explode("&*=%4#", $quicksearches);
    $colsforsearches = explode(",", $_POST['oldcols']);
    for($i=0;$i<count($allqsearches);$i++) {
      if($allqsearches[$i] != "") {
        $_POST["search_" . str_replace("hiddencolumn_", "", $colsforsearches[$i])] = $allqsearches[$i]; // need to remove the hiddencolumn indicator if it is present
        if(strstr($colsforsearches[$i], "hiddencolumn_")) {
          unset($colsforsearches[$i]); // remove columns that were added to the column list just so we would know the name of the hidden searches
        }
      }
    }
    foreach($_POST as $k=>$v) {
      if(substr($k, 0, 7) == "search_" AND $v != "") {
        $thiscol = substr($k, 7);
        $searches[$thiscol] = $v;
      }
    }
    global $xoopsUser;
    $mid = getFormulizeModId();
    $gperm_handler =& xoops_gethandler('groupperm');
    $member_handler =& xoops_gethandler('member');
    $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
    $uid = $xoopsUser ? $xoopsUser->getVar('uid') : "0";
    //print_r($_POST['currentview']);
    $scope = buildScope($_POST['currentview'], $member_handler, $gperm_handler, $uid, $groups, $fid, $mid);
    /*print "Saved View: $savedView<br>";
    print "Currentview setting: " . $_POST['currentview'] . "<br>";
    print "Scope generated for view: ";
    print_r($scope);
    print "<br><br>";*/
    
    // by calling this, we will set the base query that needs to be used in order to generate the calculations
    // special flag is used to force return once base query is set
    $GLOBALS['formulize_returnAfterSettingBaseQuery'] = true;
    formulize_gatherDataSet(array(), $searches, "", "", $frid, $fid, $scope);
    unset($GLOBALS['formulize_returnAfterSettingBaseQuery']);
    
    $ccols = explode("/", $_POST['calc_cols']);
		$ccalcs = explode("/", $_POST['calc_calcs']);
		// need to add in proper handling of long calculation results, like grouping percent breakdowns that result in many, many rows.
		foreach($ccalcs as $onecalc) {
			$thesecalcs = explode(",", $onecalc);
			if(!is_array($thesecalcs)) { $thesecalcs[0] = ""; }
			$totalalcs = $totalcalcs + count($thesecalcs);
		}
		$cblanks = explode("/", $_POST['calc_blanks']);
		$cgrouping = explode("/", $_POST['calc_grouping']);
    //formulize_benchmark("before performing calcs");
		$cachedResults[$frid][$fid][$savedView] = performCalcs($ccols, $ccalcs, $cblanks, $cgrouping, $frid, $fid);
    
  }
  $calcResults = $cachedResults[$frid][$fid][$savedView];

  // individual handle requested, so convert to array
  $origHandle = $handle;
  if($handle!="all" AND !is_array($handle)) {
    $handles[0] = $handle;
  } elseif(is_array($handle)) {
    $handles = $handle;
  } else {
    $handles = array_keys($calcResults[0]); // all the handles in the result array
  }
  
  
    
  foreach($handles as $handle) {
  
    if($grouping != "all") {
      $groupingTypeMap = array();
      foreach($calcResults[3][$handle] as $groupType=>$values) {
        if($groupType == $type OR $type == "all") {
          foreach($values as $groupingId=>$theseValues) {
            if(array_search($grouping, $theseValues) !== false) {
              $groupingTypeMap[$groupType][$groupingId] = true; // this is a grouping selection for this type that we need to return
            }
          }
        }
      }
    }
    $indexer = 0;
    foreach($calcResults[0][$handle] as $calcType=>$results) {
      if($type == $calcType OR $type == "all") {
        foreach($results as $groupingId=>$thisResult) {
          if(isset($groupingTypeMap[$calcType][$groupingId]) OR $grouping == "all") {
            //print "found $handle ... $calcType ... $groupingId<br>";
            $resultArray[$handle][$calcType][$indexer]['result'] = $thisResult;
            $resultArray[$handle][$calcType][$indexer]['grouping'] = $calcResults[3][$handle][$calcType][$groupingId];
            $indexer++;
          }
        }
      }
    }
    
  }
  /*if($origHandle != "all" AND !is_array($origHandle)) {
    if($type == "all") {
      return $resultArray[$origHandle]; // specific handle requested, so return all types for that handle
    } else {
      if(count($resultArray[$origHandle][$type]) == 1) {
        return $resultArray[$origHandle][$type][0]['result']; // specific type on a specific handle requested and there's only one grouping result, so just return the plain result
      } else {
        return $resultArray[$origHandle][$type]; // specific type on a specific handle requested, so return all groupings and results
      }
    }
  }*/
  return $resultArray; // multiple handles requested so return everything
  
  
  
}



?>