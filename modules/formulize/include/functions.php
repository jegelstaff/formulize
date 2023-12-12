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
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

// these are indexes in the ele_value array
define("EV_MULTIPLE_LIST_COLUMNS",          10);    // display multiple columns from linked lists for display in lists
define("EV_MULTIPLE_FORM_COLUMNS",          17);    // display multiple columns from linked lists for display in form elements
define("EV_MULTIPLE_SPREADSHEET_COLUMNS",   11);    // display multiple columns from linked lists for export to spreadsheets

define("SPREADSHEET_EXPORT_FOLDER",         "/cache/");   // used to be /modules/formulize/export/

$codeToIncludejQueryWhenNecessary = "
if (typeof jQuery == 'undefined') {
    var head = document.getElementsByTagName('head')[0];
    script = document.createElement('script');
    script.id = 'jQuery';
    script.type = 'text/javascript';
    script.src = '".XOOPS_URL."/modules/formulize/libraries/jquery/jquery-1.4.2.min.js';
    head.appendChild(script);
}
if (typeof jQuery.ui == 'undefined') {
    var head = document.getElementsByTagName('head')[0];
    script = document.createElement('script');
    script.id = 'jQueryUI';
    script.type = 'text/javascript';
    script.src = '".XOOPS_URL."/modules/formulize/libraries/jquery/jquery-ui-1.8.2.custom.min.js';
    head.appendChild(script);
    stylesheet = document.createElement('link');
    stylesheet.rel = 'stylesheet';
    stylesheet.type = 'text/css';
    stylesheet.href = '".XOOPS_URL."/modules/formulize/libraries/jquery/css/start/jquery-ui-1.8.2.custom.css';
    head.appendChild(stylesheet);
}
";

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/common.php";

function getFormFramework($formframe, $mainform=0) {
    static $cachedToReturn = array();
    global $xoopsDB;
    if($formframe AND isset($cachedToReturn[$formframe]) AND is_array($cachedToReturn[$formframe]) AND isset($cachedToReturn[$formframe][$mainform])) { return $cachedToReturn[$formframe][$mainform]; }
    if ($mainform) {
        // a framework
        if (!is_numeric($mainform)) {
            exit("Mainform must be numeric, was: '".strip_tags(htmlspecialchars($mainform))."'");
        }
        $frid = $formframe;
        $fid = $mainform;
        if (!is_numeric($formframe)) {
            $frameid = q("SELECT frame_id FROM " . $xoopsDB->prefix("formulize_frameworks") . " WHERE frame_name='" . formulize_db_escape($formframe) . "'");
            if(!$frid = intval($frameid[0]['frame_id'])) {
                exit("Cannot identify relationship using this text '".strip_tags(htmlspecialchars($formframe))."'");
            }
        }
    } else {
        // a form
        $frid = "";
        $fid = $formframe;
        if (!is_numeric($formframe)) { // if it's a title, convert to the id
            $formid = q("SELECT id_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE desc_form = '" . formulize_db_escape($formframe) . "'");
            if(!$fid = intval($formid[0]['id_form'])) {
                exit("Cannot identify mainform using this text '".strip_tags(htmlspecialchars($formframe))."'");
            }
        }
    }
    $to_return[0] = $fid;
    $to_return[1] = $frid;
    $cachedToReturn[$formframe][$mainform] = $to_return;
    return $to_return;
}


// get the title of a form
function getFormTitle($fid) {
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($fid);
    if(!$formObject) {
        debug_print_backtrace();
    }
	return html_entity_decode($formObject->getVar('title'),ENT_QUOTES);
}


//this function returns the list of all the user's full names for all the users in the specified group(s)
// $groups is an array of all the group ids that we should be considering
// $nametype is either uname or name
// $requireAllGroups is a 0 or 1, and if it's a 1, then we need to match only users who are members of all the groups specified
// $filter is the specified filters to run on the profile form, if any
// $limitByUsersGroups is a flag to indicate if we should be using only the users own groups
// $declaredUsersGroups is an array of all the groups that the declared user is a member of
function gatherNames($groups, $nametype, $requireAllGroups=false, $filter=false, $limitByUsersGroups=false, $declaredUsersGroups=array()) {
    if ($groups == $declaredUsersGroups) {
        $limitByUsersGroups = false;
    }
    global $xoopsDB;
    $member_handler =& xoops_gethandler('member');
    $all_users = array();
    $all_users_limited = array();
    $usersByGroup = array();
    foreach ($groups as $group) {
        $groupusers = $member_handler->getUsersByGroup($group);
        $all_users = array_merge((array)$groupusers, $all_users);
        if (in_array($group, $declaredUsersGroups) AND $group != XOOPS_GROUP_USERS) {
            // build a list of just the users in the declared user's groups
            $all_users_limited = array_merge((array)$groupusers, $all_users_limited);
        }
        $usersByGroup[$group] = $groupusers;
    }

    // if we require users to be people who are members of all specified groups...take the intersection of the user lists from all the groups
    if ($requireAllGroups) {
        $foundUsers = false;
        foreach ($usersByGroup as $group => $theseUsers) {
            // only include the users from this group if we're not limiting by the users groups, or if we are limiting, then there must be at least one user in this group who is in some group that the user is also a member of (not necessarily this group...current user and the matched user might have a different group in common, not this one, that's fine, we'll still use this group)
            if (!$limitByUsersGroups OR count((array) array_intersect($all_users_limited, $theseUsers))>0) {
                if (!$foundUsers) {
                    // need to seed the all users array so there's something to intersect with the first time, otherwise the list will be empty
                    $all_users = $theseUsers;
                }
                $foundUsers = true;
                $all_users = array_intersect((array)$theseUsers, $all_users);
            }
        }
    } elseif ($limitByUsersGroups) {
        // if we don't require people to be part of all groups, but we still want to limit by the user's own groups, then we simply use the subset of the groups that the declared user is a member of
        $all_users = $all_users_limited;
    }
    array_unique($all_users);

    // now convert the user id list into a set of objects
    $criteria = new Criteria('uid', "(".implode(",",$all_users).")", 'IN');
    $all_users = $member_handler->getUsers($criteria);

    $found_names = array();
    $found_uids = array();
    foreach ($all_users as $user) {
        $found_names[$user->getVar('uid')] = $user->getVar($nametype) != "" ? $user->getVar($nametype) : $user->getVar('uname');
        $found_uids[$user->getVar('uid')] = $user->getVar('uid');
    }

    // handle any filter that might be specified on the user profile form

    // determine which form the "User Profile" is
    $module_handler =& xoops_gethandler('module');
    $config_handler =& xoops_gethandler('config');
    $formulizeModule =& $module_handler->getByDirname("formulize");
    $formulizeConfig =& $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
    $fid = $formulizeConfig['profileForm'];

    if (is_array($filter) AND $fid) {
        $filterElements = $filter[0];
        $filterOps = $filter[1];
        $filterTerms = $filter[2];
        include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
        $start = true;
        for ($filterId = 0;$filterId<count((array) $filterElements);$filterId++) {
            if ($ops[$i] == "NOT") {
                $ops[$i] = "!=";
            }
            if (!$start) {
                $filter .= "][";
            }
            $start = false;
            $filterText .= $filterElements[$filterId]."/**/".$filterTerms[$filterId]."/**/".$filterOps[$filterId];
        }
        $profileData = getData("", $fid, $filterText, "AND", makeUidFilter($found_uids));
        $real_found_names = array();
        foreach ($profileData as $thisData) {
            $thisUid = display($thisData, "uid");
            $real_found_names[$thisUid] = $found_names[$thisUid];
        }
        unset($found_names);
        $found_names = $real_found_names;
    }

    natcasesort($found_names);
    return $found_names;
}


//get the currentURL
function getCurrentURL() {
    static $url = "";
    if ($url) {
        return $url;
    }

    $url_parts = parse_url(XOOPS_URL);
    $url = $url_parts['scheme'] . "://" . $_SERVER['HTTP_HOST'];
    $url = (isset($url_parts['port']) AND !strstr($_SERVER['HTTP_HOST'], ":")) ? $url . ":" . $url_parts['port'] : $url;
    // strip html tags, convert special chars to htmlchar equivalents, then convert back ampersand htmlchars to regular ampersands, so the URL doesn't bust on certain servers
    $url .= str_replace("&amp;", "&", htmlSpecialChars(strip_tags($_SERVER['REQUEST_URI'])));
    return $url;
}


// this function returns a human readable, comma separated list of group names, given a string of comma separated group ids
function groupNameList($list, $obeyMemberOnlyFlag = true) {
    global $xoopsDB;
    $grouplist = explode(",", trim($list, ","));
    if ($grouplist[0] == "onlymembergroups") { // first group might be a special key to tell us to limit the selected groups
        unset($grouplist[0]);
        global $xoopsUser;
        $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
    } else {
        $obeyMemberOnlyFlag = false; // no memberonly flag in effect, so we can ignore this operation later on
    }

    $start = 1;
    foreach ($grouplist as $gid) {
        if (!$obeyMemberOnlyFlag OR in_array($gid, $groups)) {
            $groupnames = q("SELECT name FROM " . $xoopsDB->prefix("groups") . " WHERE groupid='$gid'");
            if ($start) {
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
    if (!isset($cachedOwners[$vid])) {
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

    // get new saved reports
    if ($frid) {
        $saved_reports = q("SELECT sv_id, sv_name FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_formframe='$frid' AND sv_mainform='$fid' AND sv_owner_uid='$uid'");
    } else {
        $saved_reports = q("SELECT sv_id, sv_name FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_formframe='$fid' AND sv_owner_uid='$uid'");
    }

    // get new published reports
    if ($frid) {
        $published_reports = q("SELECT sv_id, sv_name, sv_pubgroups, sv_owner_uid FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_formframe='$frid' AND sv_mainform='$fid' AND sv_pubgroups != \"\"");
    } else {
        $published_reports = q("SELECT sv_id, sv_name, sv_pubgroups, sv_owner_uid FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_formframe='$fid' AND sv_pubgroups != \"\"");
    }

    // cull published reports to ones that are published to a group that the user belongs to
    $indexer = 0;
    $available_published_reports = array();
    for ($i = 0; $i < count((array) $published_reports); $i++) {
        $report_groups = explode(",", $published_reports[$i]['sv_pubgroups']);
        if (array_intersect($groups, $report_groups)) {
            $available_published_reports[$indexer]['sv_id']   = $published_reports[$i]['sv_id'];
            $available_published_reports[$indexer]['sv_name'] = $published_reports[$i]['sv_name'];
            $available_published_reports[$indexer]['sv_uid']  = $published_reports[$i]['sv_owner_uid'];
            $indexer++;
        }
    }

    // parse out details from arrays for passing back
    $sortnames = array();
    foreach ($saved_reports as $id=>$details) {
        $sortnames[] = $details['sv_name'];
    }
    array_multisort($sortnames, $saved_reports);

    $sortnames = array();
    foreach ($available_published_reports as $id=>$details) {
        $sortnames[] = $details['sv_name'];
    }
    array_multisort($sortnames, $available_published_reports);

    $to_return[0] = array();    // in an older version the saved and published reports were returned but then the
    $to_return[1] = array();    //  method changed, and new array indexes were added and these were left for compatability
    $to_return[2] = $saved_reports;
    $to_return[3] = $available_published_reports;

    return $to_return;
}


// security check to see if a form is allowed for the user:
function security_check($form_id, $entry_id="", $user_id="", $owner="", $groups="", $mid="", $gperm_handler="") {

    if (!$mid) { // if no mid specified, set it
        $mid = getFormulizeModId();
    }

    if (!$gperm_handler) {
        $gperm_handler =& xoops_gethandler('groupperm');
    }

    $user_id = intval($user_id);
    if (!$user_id) {
        global $xoopsUser;
        $user_id = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
    }

    if (!$groups) { // if no groups specified, use the declared uid user's groups
        global $xoopsUser;
        $groups = array(0=>XOOPS_GROUP_ANONYMOUS);
        if($xoopsUser AND $user_id == $xoopsUser->getVar('uid')) {
            $groups = $xoopsUser->getGroups();
        } elseif($user_id) {
            $member_handler = xoops_gethandler('member');
            if($uidObject = $member_handler->getUser($user_id)) {
                $groups = $uidObject->getGroups();
            }
        }
    }

    if (!$gperm_handler->checkRight("view_form", $form_id, $groups, $mid)) {
        return false;
    }

    if ($entry_id == "proxy" AND !$gperm_handler->checkRight("add_proxy_entries", $form_id, $groups, $mid)) {
        return false;
    }

    if ($entry_id == "new" AND !$gperm_handler->checkRight("add_own_entry", $form_id, $groups, $mid)) {
        return false;
    }

    if($entry_id == "new" OR $entry_id == "proxy") {
        $entry_id = ""; // if this is a new entry, then we don't do the check below to look for permissions on a specific entry, since there isn't one yet!
    }

    // do security check on entry in form -- note: based on the initial entry passed, does not consider entries in one-to-one linked forms which are assumed to be allowed for the user if the main entry is.
    // allow user to see own entry
    // any entry if they have view_globalscope
    // other users in the appropriate group if they have view_groupscope
    // --report overrides need to be added in here for display of entries in reports
    if ($entry_id) {
        $view_globalscope = $gperm_handler->checkRight("view_globalscope", $form_id, $groups, $mid);
        if (!$view_globalscope) {

            if (!$owner) {
               $owner = getEntryOwner($entry_id, $form_id);
            }

            if($owner == $user_id AND $user_id == 0) {
                // anonymous user so ownership isn't good enough, they either need explicit groupscope
                // or the entry needs to be one that they have rights to in virtue of a cookie or passcode on a screen on this form
                if(!$view_groupscope = $gperm_handler->checkRight("view_groupscope", $form_id, $groups, $mid)) {
                    $screen_handler = xoops_getmodulehandler('screen', 'formulize');
                    $candidateEntries = array();
                    $sid = 0;
                    foreach($_SESSION as $sessionVariable=>$value) {
                        if(substr($sessionVariable, 0, 19) == 'formulize_passCode_' AND is_numeric(str_replace('formulize_passCode_', '', $sessionVariable))) {
                            $sid = str_replace('formulize_passCode_', '', $sessionVariable);
                            $screenObject = $screen_handler->get($sid);
                            if($screenObject->getVar('fid') == $form_id) {
                                $data_handler = new formulizeDataHandler($form_id);
                                $candidateEntries = array_merge($candidateEntries, $data_handler->findAllEntriesWithValue('anon_passcode_'.$form_id, $value));
                            }
                        }
                    }
                    if(!$sid AND isset($_COOKIE['entryid_'.$form_id])) {
                        $candidateEntries[] = intval($_COOKIE['entryid_'.$form_id]);
                    }
                    if(in_array($entry_id, $candidateEntries)) {
                        return true;
                    }
                    return false;
                }
            } elseif ($owner != $user_id) {
                $view_groupscope = $gperm_handler->checkRight("view_groupscope", $form_id, $groups, $mid);
                // if no view_groupscope, then check to see if the settings for the form are "one entry per group" in which case override the groupscope setting
                if (!$view_groupscope) {
                    global $xoopsDB;
                    $smq = q("SELECT singleentry FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form=$form_id");
                    if ($smq[0]['singleentry'] == "group") {
                        $view_groupscope = true;
                    }
                }

                $groupScopeGroups = getGroupScopeGroups($form_id, $groups);
                $data_handler = new formulizeDataHandler($form_id);
                $intersect_groups = array_intersect($data_handler->getEntryOwnerGroups($entry_id), $groupScopeGroups);
                sort($intersect_groups); // necessary to make sure that 0 will be a valid key to use below

                if (!$view_groupscope OR (count((array) $intersect_groups) == 1 AND $intersect_groups[0] == XOOPS_GROUP_USERS) OR count((array) $intersect_groups) == 0) {
                    // if they have no groupscope, or if they do have groupscope, but the only point of overlap between the owner, the current user, and the groups with access is the registered users group, then..... (note that registered users will probably be an irrelevant check since the new "groups with access" checking ought to exclude registered users group in complex group setups)
                    // last hope...check for a unlocked view that has been published to them which covers a group that includes this entry
                    // 1. get groups for unlocked view for this user's groups where the mainform is $form_id or there is no mainform and formframe is $form_id
                    // 2. if group or all scope, allow it
                    // 3. or if there's an intersection on the owner_groups and the groups in an unlocked view, then allow it.
                    global $xoopsDB;
                    $unlockviews = q("SELECT sv_currentview, sv_pubgroups FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_lockcontrols=0 AND ((sv_formframe='$form_id' AND sv_mainform='') OR sv_mainform='$form_id')");
                    foreach ($unlockviews as $thisview) {
                        $pubbedgroups = explode(",", $thisview['sv_pubgroups']);
                        // if this saved view has been published to the user's groups
                        if (array_intersect($pubbedgroups, $groups)) {
                            // user has been published an unlocked view for which the scope is all
                            if ($thisview['sv_currentview'] == "all") {
                                return true;
                            }
                            // what about groupscope in the view?  is that accounted for below, or should we check against "group"??
                            $viewgroups = explode(",", $thisview['sv_currentview']);
                            if (array_intersect($data_handler->getEntryOwnerGroups($entry_id), $viewgroups)) {
                                return true;
                            }
                        }
                    }
                    return false;
                }
            }
        }

        // check to see if the entry matches the user's per group filters, if any
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $formObject = $form_handler->get($form_id);
        if ($perGroupFilter = $form_handler->getPerGroupFilterWhereClause($form_id)) {
            global $xoopsDB;
            $checkSQL = "SELECT count(entry_id) FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." WHERE entry_id = $entry_id $perGroupFilter";
            if (!$checkRes = $xoopsDB->query($checkSQL)) {
                return false;
            }
            $countRow = $xoopsDB->fetchRow($checkRes);
            if ($countRow[0] != 1) {
                return false;
            }
        }
    }

    return true;
}

// get the groupscope groups for a given form, for a given user
// use the specific groupscope groups if specified, otherwise, use view_form permission and the overlap with the user's groups, to determine the groups that form the groupscope
function getGroupScopeGroups($fid, $groups=array()) {
    if(!is_array($groups) OR count($groups)==0) {
        global $xoopsUser;
        $groups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
    }
    $gperm_handler = xoops_gethandler('groupperm');
    if($view_groupscope = $gperm_handler->checkRight("view_groupscope", $fid, $groups, getFormulizeModId())) {
        $formulize_permHandler = new formulizePermHandler($fid);
        $specificScopeGroups = $formulize_permHandler->getGroupScopeGroupIds($groups);
        if ($specificScopeGroups === false) {
            $groupsWithAccess = $gperm_handler->getGroupIds("view_form", $fid, getFormulizeModId());
            return array_intersect($groupsWithAccess, $groups);
        } else {
            return $specificScopeGroups;
        }
    }
    return array();
}

// GET THE MODULE ID -- specifically get formulize, since if called from within a block, the xoopsModule module ID will not be formulize's id
function getFormulizeModId() {
  global $xoopsDB;
    static $mid = "";
    if (!$mid) {
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
    if ($res = $xoopsDB->query($query)) {
        while ($array = $xoopsDB->fetchArray($res)) {
            if ($keyfield) {
                if (!$keyfieldOnly) {
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
    foreach ($result as $acat) {
        $cats[$acat['cat_id']] = $acat['cat_name'];
    }
    return $cats;
}


// THIS FUNCTION RETURNS THE CAT_ID OF THE CATEGORY WHERE A FORM IS FOUND (OR 0 IF THE FORM IS NOT FOUND)
function getMenuCat($fid) {
    global $xoopsDB;
    $foundCat = q("SELECT cat_id FROM " . $xoopsDB->prefix("formulize_menu_cats") . " WHERE id_form_array LIKE \"%,$fid,%\"");
    if (count((array) $foundCat)>0) {
        return($foundCat[0]['cat_id']);
    } else {
        return 0;
    }
}


// this function truncates a string to a certain number of characters
function printSmart($value, $chars="35") {
    if($chars AND !strstr(getCurrentURL(), 'master.php?')) {
        if (!is_numeric($value) AND $value == "") {
            $value = "&nbsp;";
        } else {
            $value = cutString(trans($value), $chars);
        }
    }
    return $value;
}


// this function handles cutting up a string and is multibyte aware -- thanks to Fram!
function cutString($string, $maxlen) {
    if(function_exists('mb_strlen')) {
        $len = (mb_strlen($string) > $maxlen)
            ? mb_strripos(mb_substr($string, 0, $maxlen), ' ')
            : $maxlen;
        $len = $len ? $len : $maxlen;
        $cutStr = mb_substr($string, 0, $len);
        return (mb_strlen($string) > $maxlen)
            ? $cutStr . '...'
            : $cutStr;
    } else {
        $len = (strlen($string) > $maxlen)
            ? strripos(substr($string, 0, $maxlen), ' ')
            : $maxlen;
        $cutStr = substr($string, 0, $len);
        return (strlen($string) > $maxlen)
            ? $cutStr . '...'
            : $cutStr;
    }
}


// this function returns the headerlist for a form and gracefully degrades to other inputs if the headerlist itself is not specified.
// need ids flag will cause the returned array to be IDs instead of header text
// convertIdsToElementHandles flag will have effect if ids have been returned, and will do one query to get all the element handles that patch the ids selected
// we do not filter the headerlist for private elements, because the columns in entriesdisplay are filtered for private columns (and display columns) after being gathered.
function getHeaderList ($fid, $needids=false, $convertIdsToElementHandles=false) {
    global $xoopsDB;

    $headerlist = array();

    $hlq = "SELECT headerlist FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form='$fid'";
    if ($result = $xoopsDB->query($hlq)) {
        while ($row = $xoopsDB->fetchRow($result)) {
            // check to see if there is actually any real data specified in this string, make sure it's not all separators.
            if ($somethingLeft = str_replace("*=+*:", "", $row[0])) {
                $headerlist = explode("*=+*:", $row[0]);
                array_shift($headerlist);
            }
        }

        // if the headerlist is using the new ID based system
        if (is_numeric($headerlist[0]) OR isMetaDataField($headerlist[0])) {
            // if we want actual text headers, convert ids to text
            if (!$needids) {
                $start = 1;
                $metaHeaderlist = array();
                foreach ($headerlist as $headerid=>$thisheaderid) {
                    if ($thisheaderid == "entry_id") {
                        $metaHeaderlist[] = _formulize_ENTRY_ID;
                        unset($headerlist[$headerid]);
                        continue;
                    }
                    if ($thisheaderid == "uid" OR $thisheaderid == "creation_uid") {
                        $metaHeaderlist[] = _formulize_DE_CALC_CREATOR;
                        unset($headerlist[$headerid]);
                        continue;
                    }
                    if ($thisheaderid == "proxyid" OR $thisheaderid == "mod_uid") {
                        $metaHeaderlist[] = _formulize_DE_CALC_MODIFIER;
                        unset($headerlist[$headerid]);
                        continue;
                    }
                    if ($thisheaderid == "creation_date" OR $thisheaderid == "creation_datetime") {
                        $metaHeaderlist[] = _formulize_DE_CALC_CREATEDATE;
                        unset($headerlist[$headerid]);
                        continue;
                    }
                    if ($thisheaderid == "mod_date" OR $thisheaderid == "mod_datetime") {
                        $metaHeaderlist[] = _formulize_DE_CALC_MODDATE;
                        unset($headerlist[$headerid]);
                        continue;
                    }
                    if ($thisheaderid == "creator_email") {
                        $metaHeaderlist[] = _formulize_DE_CALC_CREATOR_EMAIL;
                        unset($headerlist[$headerid]);
                        continue;
                    }
                    if ($start) {
                        $where_clause = "ele_id='$thisheaderid'";
                        $start = 0;
                    } else {
                        $where_clause .= " OR ele_id='$thisheaderid'";
                    }
                }
                if ($where_clause) {
                    $captionq = "SELECT ele_caption, ele_colhead FROM " . $xoopsDB->prefix("formulize") . " WHERE $where_clause AND (ele_type != \"ib\" AND ele_type != \"areamodif\" AND ele_type != \"subform\" AND ele_type != \"grid\") ORDER BY ele_order";
                    if ($rescaptionq = $xoopsDB->query($captionq)) {
                        unset($headerlist);
                        $headerlist = $metaHeaderlist;
                        while ($row = $xoopsDB->fetchArray($rescaptionq)) {
                            if ($row['ele_colhead'] != "") {
                                $headerlist[] = $row['ele_colhead'];
                            } else {
                                $headerlist[] = $row['ele_caption'];
                            }
                        }
                    } else {
                        exit("Error returning the default list of captions.");
                    }
                }
            } else { // if getting ids, need to convert old metadata values to new ones
                foreach ($headerlist as $headerListIndex=>$thisheaderid) {
                    if ($thisheaderid == "uid") {
                        $headerlist[$headerListIndex] = "creation_uid";
                    } elseif ($thisheaderid == "proxyid") {
                        $headerlist[$headerListIndex] = "mod_uid";
                    } elseif ($thisheaderid == "creation_date") {
                        $headerlist[$headerListIndex] = "creation_datetime";
                    } elseif ($thisheaderid == "mod_date") {
                        $headerlist[$headerListIndex] = $thisheaderid == "mod_datetime";
                    }
                }
            }
        } else { // not using new ID based system, so convert to ids if needids is true
            if ($needids) {
                $tempheaderlist = $headerlist;
                unset($headerlist);
                $headerlist = convertHeadersToIds($tempheaderlist, $fid);
            }
        }
    }

    if (count((array) $headerlist)==0) { // if no header fields specified, then
        // gather required fields for this form
        $reqfq = "SELECT ele_caption, ele_colhead, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE ele_req=1 AND id_form='$fid' AND (ele_type != \"ib\" AND ele_type != \"areamodif\" AND ele_type != \"subform\" AND ele_type != \"grid\") ORDER BY ele_order ASC LIMIT 3";
        if ($result = $xoopsDB->query($reqfq)) {
            while ($row = $xoopsDB->fetchArray($result)) {
                if ($needids) {
                    $headerlist[] = $row['ele_id'];
                } else {
                    if ($row['ele_colhead'] != "") {
                        $headerlist[] = $row['ele_colhead'];
                    } else {
                        $headerlist[] = $row['ele_caption'];
                    }
                }
            }
        }
    }

    if (count((array) $headerlist) == 0) {
        // IF there are no required fields THEN ... go with first three fields
        $firstfq = "SELECT ele_caption, ele_colhead, ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form='$fid' AND (ele_type != \"ib\" AND ele_type != \"areamodif\" AND ele_type != \"subform\" AND ele_type != \"grid\") ORDER BY ele_order ASC LIMIT 3";
        if ($result = $xoopsDB->query($firstfq)) {
            while ($row = $xoopsDB->fetchArray($result)) {
                if ($needids) {
                    $headerlist[] = $row['ele_id'];
                } else {
                    if ($row['ele_colhead'] != "") {
                        $headerlist[] = $row['ele_colhead'];
                    } else {
                        $headerlist[] = $row['ele_caption'];
                    }
                }
            }
        }
    }
    if ($needids AND $convertIdsToElementHandles) {
        $savedMetaHeaders = array();
        foreach ($headerlist as $thisheaderkey=>$thisheaderid) {
            // remove non numeric headers and save them
            if (!is_numeric($thisheaderid)) {
                $savedMetaHeaders[] = $thisheaderid;
                unset($headerlist[$thisheaderkey]);
            }
        }
        // if there are any numeric headers, then get the handles
        if (count((array) $headerlist)>0) {
            $sql = "SELECT ele_handle FROM ".$xoopsDB->prefix("formulize") . " WHERE ele_id IN (".implode(",",$headerlist).") ORDER BY ele_order";
            if ($res = $xoopsDB->query($sql)) {
                $headerlist = array();
                while ($array = $xoopsDB->fetchArray($res)) {
                    $headerlist[] = $array['ele_handle'];
                }
                $headerlist = array_merge($savedMetaHeaders, $headerlist); // add the non numeric headers back in to the front
            } else {
                print "Error: could not convert Element IDs to Handles when retrieving the header list.  SQL error: ".$xoopsDB->error()."<br>";
            }
        } else {
            // no numeric headers, so just return the non numeric ones
            $headerlist = $savedMetaHeaders;
        }
    }
    return $headerlist;
}


// gets the ele_ids of the headerlist for a form
// now only used when opening a legacy report from 1.6 or older, or when reading a headerlist that is based on the old non-ID based system
function convertHeadersToIds($headers, $fid) {
    global $xoopsDB;
    foreach ($headers as $cap) {
        $cap = addslashes($cap);
        $ele_id = q("SELECT ele_id FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form='$fid' AND ele_caption='" . str_replace("`", "'", $cap) . "'"); // assume only one match, even though that is not enforced!  Ignores colheads since no use of this function should ever be passing colheads to it (only used for legacy purposes).
        $ele_ids[] = $ele_id[0]['ele_id'];
    }
    return $ele_ids;
}


// this function returns an array of the allowed categories, key being id and value being name, based on the allowedforms array
function allowedCats($cats, $allowedForms) {
    global $xoopsDB;
    foreach ($cats as $catid=>$catname) {
        $flatFormArray = q("SELECT id_form_array FROM " . $xoopsDB->prefix("formulize_menu_cats") . " WHERE cat_id='$catid'");
        $formsInCat = explode(",", trim($flatFormArray[0]['id_form_array'], ","));
        if (array_intersect($formsInCat, $allowedForms)) {
            $allowedCats[$catid] = $catname;
        }
    }
    return $allowedCats;
}


// this function returns the forms the user is allowed to view
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
    if (!$pform) {
        return $allowedForms;
    }
    $pformKey = array_search($pform, $allowedForms);

    // if the profileForm is allowed
    if (isset($pformKey)) {
        // check if the user has view group or view global on that form
        if (!$pform_view_groupscope = $gperm_handler->checkRight("view_groupscope", $pform, $groups, $module_id) AND !$pform_view_globalscope = $gperm_handler->checkRight("view_globalscope", $pform, $groups, $module_id)) {
            // if no group or global perm, then remove from array
            unset($allowedForms[$pformKey]);
        }
    }

    return $allowedForms;
}


// this function returns the name of a form when given the id (internal, not meant for public use)
function fetchFormName($id) {
    global $xoopsDB;
    $title_q = q("SELECT desc_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form='$id'");
    return trans($title_q[0]['desc_form']);
}

// this function returns the names of a form or forms when given an id or array of ids
function fetchFormNames($ids) {
    if (is_array($ids)) {
        foreach ($ids as $id) {
            $names[] = fetchFormName($id);
        }
        return $names;
    } else {
        $name = fetchFormName($ids);
        return $name;
    }
}


// this function returns the forms in a category, if given the category id and the allowedforms for the user
function fetchFormsInCat($thisid, $allowedForms="") {
    global $xoopsDB;

    if (!is_array($allowedForms)) {
        $allowedForms = allowedForms();
    }

    if ($thisid == 0) { // general category
        // 1. foreach allowed form, check to see if it's in a cat
        // 2. record each one in an array
        // 3. make formsInCat equal the difference between found array and allowed forms
        foreach ($allowedForms as $thisform) {
            $found_q = q("SELECT * FROM " . $xoopsDB->prefix("formulize_menu_cats") . " WHERE id_form_array LIKE \"%,$thisform,%\"");
            if (count((array) $found_q)>0) { $foundForms[] = $thisform; }
        }
        if (count((array) $foundForms) > 0 ) {
            $formsInCat1 = array_diff($allowedForms, $foundForms);
        } else {
            $formsInCat1 = $allowedForms;
        }
    } else {
        $flatFormArray = q("SELECT id_form_array FROM " . $xoopsDB->prefix("formulize_menu_cats") . " WHERE cat_id='$thisid'");
        $formsInCat1 = explode(",", trim($flatFormArray[0]['id_form_array'], ","));
    }

    // exclude inactive forms, and sort
    foreach ($formsInCat1 as $thisform) {
        $status_q = q("SELECT menuid, position FROM " . $xoopsDB->prefix("formulize_menu") . " WHERE menuid='$thisform' AND status=1");
        if (count((array) $status_q) > 0 AND in_array($thisform, $allowedForms)) {
            // only include active forms that the user is allowed to see
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

    // user is allowed to see at least one form in this category
    if (count((array) $formsInCat)>0) {
        $itemurl = XOOPS_URL."/modules/formulize/cat.php?cat=$thisid";
        if ($topwritten != 1) {
            $block = "<a class=\"menuTop\" href=\"$itemurl\">$thiscat</a>";
            $topwritten = 1;
        } else {
            $block = "<a class=\"menuMain\" href=\"$itemurl\">$thiscat</a>";
        }

        // check to see if current cat is active (ie: has been clicked)
        // if we're viewing this category or a form in this category, or this is the only category (force open)
        if ($force_open OR (isset($_GET['cat']) AND $thisid == $_GET['cat']) OR in_array($id_form, $formsInCat)) {
            foreach ($formsInCat as $thisform) {
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

// THIS FUNCTION REMOVES ENTRIES FROM THE OTHER TABLE BASED ON AN IDREQ
function deleteMaintenance($id_req, $fid) {
    global $xoopsDB;

    // remove entries in the formulize_other table
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->get($fid);

    $sql3 = "DELETE FROM " . $xoopsDB->prefix("formulize_other") . " WHERE id_req='$id_req' AND ele_id IN (" . implode(",", $formObject->getVar('elements')) . ")"; //limit to id_reqs where the element is from the right form, since the new id_reqs (entry_ids) can be repeated across forms
    if (!$result3 = $xoopsDB->query($sql3)) {
        exit("Error: failed to delete 'Other' text for entry $id_req");
    }
}


//THIS FUNCTION ACTUALLY DOES THE DELETING OF A SPECIFIC ID_REQ
function deleteIdReq($id_req, $fid) {
    $data_handler = new formulizeDataHandler($fid);
    if (!$deleteResult = $data_handler->deleteEntries($id_req)) {
        exit("<br />Error deleting entry $id_req from the database for form $fid<br />");
    }

    deleteMaintenance($id_req, $fid);
}


// THIS FUNCTION DELETES ENTRIES FROM WHEN PASSED AN entry id (ID_REQ)
// HANDLES FRAMEWORKS TOO -- HANDLERS AND MID TO BE PASSED IN WHEN FRAMEWORKS ARE USED
// owner and owner_groups to be passed in when available (if called from a function where they have already been determined
// $fid is required
// $excludeFids is an array of forms that we do not want to delete from in this case regardless (optional)
function deleteEntry($id_req, $frid, $fid, $excludeFids=array()) {

    global $xoopsDB;
    $deletedEntries = array();

    if(!$id_req OR !$fid) {
        error_log("Formulize error: deletion requested without required parameters: entry id - ".$id_req.". form id - ".$fid.".");
        return false;
    }

    if ($frid) {
        // if a framework is passed, then delete all sub entry items found in a unified display relationship with the base entry, in addition to the base entry itself.
        $fids[0] = $fid;
        $entries[$fid][0] = $id_req;
        if (!$owner) {
            $owner = getEntryOwner($id_req, $fid);
        }
        if (!$owner_groups) {
            $data_handler = new formulizeDataHandler($fid);
            $owner_groups = $data_handler->getEntryOwnerGroups($id_req);
        }

        // check for entries in forms with a relationship to this one, where the unified_delete setting is enabled
        $unified_display = false;
        $unified_delete = true;
        $linkresults = checkForLinks($frid, $fids, $fid, $entries, $unified_display, $unified_delete);
        foreach ($linkresults['entries'] as $thisfid=>$ents) {
            foreach ($ents as $ent) {
                if ($ent AND !in_array($thisfid, (array) $excludeFids)) {
                    deleteIdReq($ent, $thisfid);
                    $deletedEntries[$thisfid][] = $ent;
                }
            }
        }
        foreach ($linkresults['sub_entries'] as $thisfid=>$ents) {
            foreach ($ents as $ent) {
                if ($ent AND !in_array($thisfid, (array) $excludeFids)) {
                    // look for any subsub links...they have to be defined as part of the relationship in effect
                    $sublinkresults = checkForLinks($frid, array($thisfid), $thisfid, array($thisfid=>array($ent)), $unified_display, $unified_delete);
                    foreach($sublinkresults['sub_entries'] as $subfid=>$subent) {
                        deleteIdReq($subent, $subfid);
                        $deletedEntries[$subfid][] = $subent;
                    }
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
    foreach ($deletedEntries as $thisfid=>$entries) {
        sendNotifications($thisfid, "delete_entry", $entries);
    }
}


// GETS THE ID OF THE USER WHO OWNS AN ENTRY
function getEntryOwner($entry, $fid) {
    static $entryOwners = array();
    $entry = intval($entry);
    if (isset($entryOwners[$entry][$fid])) {
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
    if (is_array($users)) {
        if (count((array) $users) > 1) {
            return "uid=" . implode(" OR uid=", $users);
        } else {
            return "uid=" . intval($users[0]);
        }
    } else {
        return "uid=" . intval($users);
    }
}


// FUNCTION HANDLES CHECKING FOR ALL LINKING RELATIONSHIPS FOR THE FORM
// returns the fids and entries passed to it, plus any others in a framework relationship
// $entries is optional, and if left out this will only return the linked forms
// final param is a flag to control whether only unified display relationships are returned or all relationships
function checkForLinks($frid, $fids, $fid, $entries, $unified_display=false, $unified_delete=false)
{

    if(!$frid) {
        if(is_array($entries)) {
            return array('fids'=>$fids, 'entries'=>$entries);
        } else {
            return array('fids'=>$fids);
        }
    }

    static $cachedCheckForLinks = array();
    $cacheKey = md5(serialize(func_get_args()));
    if(isset($cachedCheckForLinks[$cacheKey])) {
       return $cachedCheckForLinks[$cacheKey];
    }

    // by default (ie: when called from formDisplay) only look for unified display relationships
    // when $unified_display is specifically set to zero, ie: when called from displayEntries, look for any relationships in the framework
    if ($unified_display) {
        $unified_display = "AND fl_unified_display = 1";
    } else {
        $unified_display = "";
    }

    if ($unified_delete) {
        $unified_delete = "AND fl_unified_delete = 1";
    } else {
        $unified_delete = "";
    }

    global $xoopsDB;
    // get one-to-one links
    $one_q1 = q("SELECT fl_form1_id, fl_key1, fl_key2, fl_common_value FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form2_id = $fid AND fl_relationship = 1 AND fl_frame_id = $frid $unified_display $unified_delete");
    $one_q2 = q("SELECT fl_form2_id, fl_key1, fl_key2, fl_common_value FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form1_id = $fid AND fl_relationship = 1 AND fl_frame_id = $frid $unified_display $unified_delete");
    $indexer = 0;
    $one_to_one = array();
    $many_to_one = array();
    $one_to_many = array();
    foreach ($one_q1 as $res1) {
        $one_to_one[$indexer]['fid'] = $res1['fl_form1_id'];
        $one_to_one[$indexer]['keyself'] = $res1['fl_key1'];
        $one_to_one[$indexer]['keyother'] = $res1['fl_key2'];
        $one_to_one[$indexer]['common'] = $res1['fl_common_value'];
        $indexer++;
    }
    foreach ($one_q2 as $res2) {
        $one_to_one[$indexer]['fid'] = $res2['fl_form2_id'];
        $one_to_one[$indexer]['keyother'] = $res2['fl_key1'];
        $one_to_one[$indexer]['keyself'] = $res2['fl_key2'];
        $one_to_one[$indexer]['common'] = $res2['fl_common_value'];
        $indexer++;
    }

    // get one-to-many links
    $indexer=0;
    $many_q1 = q("SELECT fl_form1_id, fl_key1, fl_key2, fl_common_value FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form2_id = $fid AND fl_relationship = 3 AND fl_frame_id = $frid $unified_display $unified_delete");
    $many_q2 = q("SELECT fl_form2_id, fl_key1, fl_key2, fl_common_value FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form1_id = $fid AND fl_relationship = 2 AND fl_frame_id = $frid $unified_display $unified_delete");

    foreach ($many_q1 as $res1) {
        $one_to_many[$indexer]['fid'] = $res1['fl_form1_id'];
        $one_to_many[$indexer]['keyself'] = $res1['fl_key1'];
        $one_to_many[$indexer]['keyother'] = $res1['fl_key2'];
        $one_to_many[$indexer]['common'] = $res1['fl_common_value'];
        $indexer++;
    }
    foreach ($many_q2 as $res2) {
        $one_to_many[$indexer]['fid'] = $res2['fl_form2_id'];
        $one_to_many[$indexer]['keyother'] = $res2['fl_key1'];
        $one_to_many[$indexer]['keyself'] = $res2['fl_key2'];
        $one_to_many[$indexer]['common'] = $res2['fl_common_value'];
        $indexer++;
    }

    // get MANY-TO-ONE links
    // put in exclusion for links from a form to itself, since those will have been found above, and we want to assume such connections are normal one-to-many style...if picked up here, they will result in a one-to-one style and in that case it would make no sense?!
    $many_q3 = q("SELECT fl_form1_id, fl_key1, fl_key2, fl_common_value FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form2_id = $fid AND fl_relationship = 2 AND fl_frame_id = $frid AND fl_form2_id != fl_form1_id $unified_display $unified_delete");
    $many_q4 = q("SELECT fl_form2_id, fl_key1, fl_key2, fl_common_value FROM " . $xoopsDB->prefix("formulize_framework_links") . " WHERE fl_form1_id = $fid AND fl_relationship = 3 AND fl_frame_id = $frid AND fl_form2_id != fl_form1_id $unified_display $unified_delete");

    foreach ($many_q3 as $res1) {
        $many_to_one[$indexer]['fid'] = $res1['fl_form1_id'];
        $many_to_one[$indexer]['keyself'] = $res1['fl_key1'];
        $many_to_one[$indexer]['keyother'] = $res1['fl_key2'];
        $many_to_one[$indexer]['common'] = $res1['fl_common_value'];
        $indexer++;
    }

    foreach ($many_q4 as $res2) {
        $many_to_one[$indexer]['fid'] = $res2['fl_form2_id'];
        $many_to_one[$indexer]['keyother'] = $res2['fl_key1'];
        $many_to_one[$indexer]['keyself'] = $res2['fl_key2'];
        $many_to_one[$indexer]['common'] = $res2['fl_common_value'];
        $indexer++;
    }

    // no entries passed, so we don't need to figure out the entries, so return only the fids and subfids
    if (!is_array($entries)) {
        foreach ($one_to_one as $one_fid) {
            $fids[] = $one_fid['fid'];
        }
        foreach ($one_to_many as $many_fid) {
            $sub_fids[] = $many_fid['fid'];
        }
        $start = 1;
        foreach ($many_to_one as $many_fid) {
            if ($start) {
                // if there are many to one relationships, then invert the relationship of the forms we've collected so far
                $sub_fids = array_merge($fids, (array)$sub_fids);
                unset($fids);
                $start = 0;
            }
            $fids[] = $many_fid['fid'];
        }
        $to_return['fids'] = $fids;
        $to_return['sub_fids'] = $sub_fids;
        $cachedCheckForLinks[$cacheKey] = $to_return;
        return $to_return;
    }

    // $entries has been passed so we do need to gather them

    // add to entries and fids array if one_to_one exists
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    foreach ($one_to_one as $one_fid) {
        $fids[] = $one_fid['fid'];
        // figure out if these are common value links, or linked selectboxes
        if ($one_fid['common']) {
            $oneFormObject = $form_handler->get($one_fid['fid']);
            $formObject = $form_handler->get($fid);
            if(!$candidateElement = $element_handler->get($one_fid['keyself'])) {
                exit("Error: could not retrieve one of the key elements (".$one_fid['keyself'].") in Relationship $frid");
            }
            if(!$mainElement = $element_handler->get($one_fid['keyother'])) {
                exit("Error: could not retrieve one of the key elements (".$one_fid['keyother'].") in Relationship $frid");
            }
            $candidateHandle = $candidateElement->getVar('ele_handle');
            $mainHandle = $mainElement->getVar('ele_handle');
            // need to construct the query differently depending if either side or both allows multiple selections
            // keep in mind, we only want to return a single value, since this is one to one? Not necessarily in these strange conditions of multiple value elements?
            // first, prepare any asynch provided values...
            if($candidateElement->canHaveMultipleValues OR $mainElement->canHaveMultipleValues) { // experimental!
                $valueToCheckAgainst = isset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][intval($entries[$fid][0])][$mainHandle]) ? "'".formulize_db_escape($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][intval($entries[$fid][0])][$mainHandle])."'" : "main.`".$mainHandle."`";
                $whereClauseExtra = strstr($valueToCheckAgainst, '`') ? " AND main.entry_id = ".intval($entries[$fid][0]) : ""; // if we don't have an explicit value, we need to specify the entry the query should use for matching
            } else {
                $valueToCheckAgainst = isset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][intval($entries[$fid][0])][$mainHandle]) ? $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][intval($entries[$fid][0])][$mainHandle] : "main.`".$mainHandle."` AND main.entry_id = ".intval($entries[$fid][0]);
            }
            if($candidateElement->canHaveMultipleValues AND $mainElement->canHaveMultipleValues == false) { // experimental!
                $candidateEntry = q("SELECT candidate.entry_id FROM "
                    . $xoopsDB->prefix("formulize_".$oneFormObject->getVar('form_handle')) . " AS candidate, "
                    . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " AS main
                    WHERE (
                    candidate.`".$candidateHandle[0]['ele_handle']."` LIKE CONCAT('%*=+*:',".$valueToCheckAgainst.",'*=+*:%')
                    OR candidate.`".$candidateHandle[0]['ele_handle']."` LIKE CONCAT('%*=+*:',".$valueToCheckAgainst.")
                    ) ".$whereClauseExtra);
            } elseif($candidateElement->canHaveMultipleValues == false AND $mainElement->canHaveMultipleValues) { // experimental!
                $candidateEntry = q("SELECT candidate.entry_id FROM "
                    . $xoopsDB->prefix("formulize_".$oneFormObject->getVar('form_handle')) . " AS candidate, "
                    . $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " AS main
                    WHERE (
                    ".$valueToCheckAgainst." LIKE CONCAT('%*=+*:',candidate.`".$candidateHandle[0]['ele_handle']."`,'*=+*:%')
                    OR ".$valueToCheckAgainst." LIKE CONCAT('%*=+*:',candidate.`".$candidateHandle[0]['ele_handle']."`)
                    ) ".$whereClauseExtra);
            } elseif($candidateElement->canHaveMultipleValues AND $mainElement->canHaveMultipleValues) {
                print 'Error: you have a common value relationship between forms, where the two form elements both allow multiple values, so we really don\'t know what to do with that. Look at changing how the relationship is configured? Or contact <a href="mailto:info@formulize.org">info@formulize.org</a>.';
            } else {
                // normal case...
                $candidateEntry = q("SELECT candidate.entry_id FROM " . $xoopsDB->prefix("formulize_".$oneFormObject->getVar('form_handle')) . " AS candidate, ". $xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')) . " AS main WHERE candidate.`".$candidateHandle."` = ".$valueToCheckAgainst." LIMIT 0,1");
            }
            if ($candidateEntry[0]['entry_id']) {
                foreach($candidateEntry as $thisCandidateEntry) {
                    $entries[$one_fid['fid']][] = $thisCandidateEntry['entry_id'];
                }
            } else {
                $entries[$one_fid['fid']][] = "";
            }
        } else {
            // figure out which of the two elements is the source of the linked values
            $selfElement = $element_handler->get($one_fid['keyself']);
            $otherElement = $element_handler->get($one_fid['keyother']);
            if (is_object($selfElement)) {
                $selfEleValue = $selfElement->getVar('ele_value');
                if (strstr($selfEleValue[2], "#*=:*")) {
                    foreach($entries[$fid] as $thisTargetEntry) {
                        // self is the linked selectbox, other is the source of the values
                        if(isset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][intval($thisTargetEntry)][$selfElement->getVar('ele_handle')])) {
                            // if an asynch request has set an override value, use that!
                            $foundEntry = $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][intval($thisTargetEntry)][$selfElement->getVar('ele_handle')];
                        } else {
                            // get the entry in the $one_fid['fid'] form (form with the self element), that has the intval($entries[$fid][0]) entry (the entry we are calling up already) as it's linked value
                            $data_handler = new formulizeDataHandler($one_fid['fid']);
                            global $xoopsUser;
                            $scope_uids = array();
                            $groups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
                            $gperm_handler = xoops_gethandler('groupperm');
                            // users who can only see their own entries should be linked to their own entry in a one-to-one connection of this kind
                            if(!$gperm_handler->checkRight("view_globalscope", $one_fid['fid'], $groups, getFormulizeModId()) AND !$gperm_handler->checkRight("view_groupscope", $one_fid['fid'], $groups, getFormulizeModId())) {
                                $scope_uids = array($xoopsUser->getVar('uid'));
                            }
                            if($selfEleValue[1] == 1) {
                                // if we support multiple selections, then prepend and append a comma
                                $foundEntry = $data_handler->findFirstEntryWithValue($selfElement, ','.$thisTargetEntry.',', "LIKE", $scope_uids);
                            } else {
                                $foundEntry = $data_handler->findFirstEntryWithValue($selfElement, intval($thisTargetEntry), '=', $scope_uids);
                            }
                        }
                        if ($foundEntry !== false) {
                            $entries[$one_fid['fid']][] = $foundEntry;
                        } else {
                            $entries[$one_fid['fid']][] = "";
                        }
                    }
                } else {
                    // other is the linked selectbox, self is the source of the values
                    foreach($entries[$fid] as $thisTargetEntry) {
                        if(isset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][intval($thisTargetEntry)][$otherElement->getVar('ele_handle')])) {
                            // if an asynch request has set an override value, use that!
                            $foundEntry = $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][intval($thisTargetEntry)][$otherElement->getVar('ele_handle')];
                        } else {
                            // return the value of the $one_fid['keyother'] element in the $fid, in intval($entries[$fid][0]) entry
                            $data_handler = new formulizeDataHandler($fid);
                            $foundEntry = $data_handler->getElementValueInEntry(intval($thisTargetEntry), $one_fid['keyother']);
                        }
                        if ($foundEntry !== false) {
                            $entries[$one_fid['fid']] = explode(',',trim($foundEntry,',')); // remove commas, though there probably aren't any anymore since we're not storing ,id, in the DB as of F5, except for multiple selection boxes
                        } else {
                            $entries[$one_fid['fid']][] = "";
                        }
                    }
                }
            } else {
                $entries[$one_fid['fid']][] = "";
            }
        }
    }

    foreach ($one_to_many as $many_fid) {
        $sub_fids[] = $many_fid['fid'];
        if (isset($entries[$fid][0])) {
            // for some reason PHP 5 won't let us evaluate this directly
            if ($thisent = $entries[$fid][0]) {
                $entries_found = findLinkedEntries($fid, $many_fid, $entries[$fid][0]);
                if (is_array($entries_found)) {
                    foreach ($entries_found as $many_entry) {
                        $sub_entries[$many_fid['fid']][] = $many_entry;
                    }
                }
            }
        } else {
            $sub_entries[$many_fid['fid']][] = "";
        }
    }

    foreach ($many_to_one as $manyToOneFid) {
        array_unshift($fids, $manyToOneFid['fid']);
        if (isset($entries[$fid][0])) {
            // for some reason PHP 5 won't let us evaluate this directly
            if ($thisent = $entries[$fid][0]) {
                $entries_found = findLinkedEntries($fid, $manyToOneFid, $entries[$fid][0]);
                foreach ($entries_found as $many_entry) {
                    $entries[$manyToOneFid['fid']][] = $many_entry;
                }
            }
        } else {
            $entries[$manyToOneFid['fid']][] = "";
        }
    }

    $to_return['fids'] = $fids;
    $to_return['entries'] = $entries;
    $to_return['sub_fids'] = $sub_fids;
    $to_return['sub_entries'] = $sub_entries;

    $cachedCheckForLinks[$cacheKey] = $to_return;

    return $to_return;
}


// THIS FUNCTION CREATES AN EXPORT FILE ON THE SERVER AND RETURNS THE FILENAME
// $headers is the list of column headings in use
// $cols is the list of handles in the $data to use to get all the data for display, must be in synch with headers
// $data is the full dataset that is being prepped
// $fdchoice is either comma or calcs (calcs for when calcs are to be exported) - or custom, in which case custdel needs to contain the delimiter
// $title does not appear to be used
// $template is a flag indicating whether we are making a template for use updating/uploading data -- blank for a blank template, update for a template with data, blankprofile for the userprofile form, or boolean false otherwise
// $fid is the form id
function prepExport($headers, $cols, $data, $fdchoice, $custdel, $template, $fid) {

    global $xoopsDB, $xoopsUser;
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";

    if ($fdchoice == "update") { // reset headers and cols to include all data -- when creating a blank template, this reset has already happened before prepexport is called
        $fdchoice = "comma";
        $template = "update";
        if(!is_array($cols) OR count((array) $cols)==0 OR (count((array) $cols)==1 AND $cols[0] == "")) {
            unset($cols);
            $cols = array();
            $groups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
            $allColList = getAllColList($fid, "", $groups);
            foreach ($allColList[$fid] as $col) {
                $cols[] = $col['ele_handle'];
            }
            unset($headers);
            $headers = getHeaders($cols);
        }
    }
    if ($fdchoice == "comma") {
        $fd = ",";
        $fxt = ".csv";
    }
    if ($fdchoice == "tab") {
        $fd = "\t";
        $fxt = ".tabDelimited";
    }
    if ($fdchoice == "custom") {
        $fd = $custdel;
        if (!$fd) { $fd = "**"; }
        $fxt = ".customDelimited";
    }
    $lineStarted = false;
    if ($template) {
        // add in other profile fields -- username, realname, e-mail, password, registration code
        if ($template == "blankprofile") {
            $csvfile = "\"" . _formulize_DE_IMPORT_USERNAME . "\"$fd\"" . _formulize_DE_IMPORT_FULLNAME . "\"$fd\"" . _formulize_DE_IMPORT_PASSWORD . "\"$fd\"" . _formulize_DE_IMPORT_EMAIL . "\"$fd\"" .  _formulize_DE_IMPORT_REGCODE . "\"";
            $lineStarted = true;
        } else {
            if ($template == "update") {
                $csvfile = "\"" . _formulize_DE_IMPORT_IDREQCOL . "\"$fd\"" . _formulize_DE_CALC_CREATOR . "\"";
                $lineStarted = true;
            } else {
                $csvfile = "\"" . _formulize_DE_CALC_CREATOR . "\"";
                $lineStarted = true;
            }
        }
    } elseif ($_POST['metachoice'] == 1) {
        // only include metadata columns if the user requested them
        $csvfile =  "\"" . _formulize_ENTRY_ID . "\"$fd\"" . _formulize_DE_CALC_CREATOR . "\"$fd\"" . _formulize_DE_CALC_CREATEDATE . "\"$fd\"" . _formulize_DE_CALC_MODIFIER . "\"$fd\"" . _formulize_DE_CALC_MODDATE . "\"";
        $lineStarted = true;
    } else {
        if (in_array("entry_id", $cols)) {
            $csvfile .= "\"" . _formulize_ENTRY_ID . "\"";
            $lineStarted = true;
        }
        if (in_array("uid", $cols) OR in_array("creation_uid", $cols)) {
            $csvfile .= $lineStarted ? $fd : "";
            $csvfile .= "\"" . _formulize_DE_CALC_CREATOR . "\"";
            $lineStarted = true;
        }
        if (in_array("proxyid", $cols) OR in_array("mod_uid", $cols)) {
            $csvfile .= $lineStarted ? $fd : "";
            $csvfile .= "\"" . _formulize_DE_CALC_MODIFIER . "\"";
            $lineStarted = true;
        }
        if (in_array("creation_date", $cols) OR in_array("creation_datetime", $cols)) {
            $csvfile .= $lineStarted ? $fd : "";
            $csvfile .= "\"" . _formulize_DE_CALC_CREATEDATE . "\"";
            $lineStarted = true;
        }
        if (in_array("mod_date", $cols) OR in_array("mod_datetime", $cols)) {
            $csvfile .= $lineStarted ? $fd : "";
            $csvfile .= "\"" . _formulize_DE_CALC_MODDATE . "\"";
            $lineStarted = true;
        }
    }

    foreach ($headers as $header) {
        // ignore the metadata columns if they are selected, since we already handle them better above. as long as the user requested that they be included
        if ($header == "" OR ($_POST['metachoice'] == 1 AND ($header == _formulized_ENTRY_ID OR $header == _formulize_DE_CALC_CREATOR OR $header == _formulize_DE_CALC_MODIFIER OR $header==_formulize_DE_CALC_CREATEDATE OR $header ==_formulize_DE_CALC_MODDATE))) {
            continue;
        }
        $header = str_replace("\"", "\"\"", $header);
        $header = "\"" . trans($header) . "\"";
        if ($lineStarted) {
            $csvfile .= $fd;
        }
        $csvfile .= $header;
        $lineStarted = true;
    }
    $csvfile .= "\r\n";

    $colcounter = 0;
    $i=0;

    $secondaryData = array();
    foreach ($data as $entry) {

        // if we have secondary row data to draw in, do that before we get to the next entry...
        $csvfile = prepExportSecondaryData($csvfile, $cols, $fd, $secondaryData);
        $secondaryData = array(); // reset the secondary data

        $ids = internalRecordIds($entry, $fid);
        $id = $ids[0];
        $id_req[] = $id;

        $c_uid = display($entry, 'creation_uid');
        $c_name_q = q("SELECT name, uname FROM " . $xoopsDB->prefix("users") . " WHERE uid='$c_uid'");
        $c_name = $c_name_q[0]['name'];
        if (!$c_name) {
            $c_name = $c_name_q[0]['uname'];
        }
        $c_date = display($entry, 'creation_datetime');
        $m_uid = display($entry, 'mod_uid');
        if ($m_uid) {
            $m_name_q = q("SELECT name, uname FROM " . $xoopsDB->prefix("users") . " WHERE uid='$m_uid'");
            $m_name = $m_name_q[0]['name'];
            if (!$m_name) {
                $m_name = $m_name_q[0]['uname'];
            }
        } else {
            $m_name = $c_name;
        }
        $m_date = display($entry, 'mod_datetime');

        // write in metadata
        $lineStarted = false;
        // will be update only, since blank ones have no data
        if ($template) {
            $csvfile .= $id . $fd . "\"$c_name\"";
            $lineStarted = true;
        } elseif ($_POST['metachoice'] == 1) {
            $csvfile .= "\"$id\"" . $fd . "\"$c_name\"" . $fd . "\"$c_date\"" . $fd . "\"$m_name\"" . $fd . "\"$m_date\"";
            $lineStarted = true;
        }

        // write in data
        foreach ($cols as $col) {
            // ignore the metadata columns if they are selected, since we already handle them better above
            if (isMetaDataField($col) AND $_POST['metachoice'] == 1) {
                continue;
            }
            if ($col == "creation_uid" OR $col == "mod_uid" OR $col == "uid" OR $col == "proxyid") {
                $name_q = q("SELECT name, uname FROM " . $xoopsDB->prefix("users") . " WHERE uid=".intval(display($entry, $col)));
                $data_to_write = $name_q[0]['name'];
                if (!$data_to_write) { $data_to_write = $name_q[0]['uname']; }
            } elseif($col == 'entry_id') {
                $data_to_write = $id;
            } elseif($col == 'creation_datetime') {
                $data_to_write = $c_date;
            } elseif($col == 'mod_datetime') {
                $data_to_write = $m_date;
            } elseif($col == 'creator_email') {
                $data_to_write = display($entry, 'creator_email');
            } else {
                $data_to_write = prepareCellForSpreadsheetExport($col, $entry);
            }
            if ($lineStarted) {
                $csvfile .= $fd;
            }
            $csvfile .= $data_to_write;
            $lineStarted = true;
        }
        $csvfile .= "\r\n"; // end of a line
    }

    // grab and output any secondary data for the last entry, if there was any
    $csvfile = prepExportSecondaryData($csvfile, $cols, $fd, $secondaryData);

    $tempfold = microtime(true);
    $exfilename = _formulize_DE_XF . $tempfold . $fxt;

    // open the output file for writing
    $wpath = XOOPS_ROOT_PATH. SPREADSHEET_EXPORT_FOLDER . "$exfilename";
    $csvfile = html_entity_decode($csvfile, ENT_QUOTES);
    $exportfile = fopen($wpath, "w");
    fwrite($exportfile, $csvfile);
    fclose($exportfile);

    // garbage collection. delete files older than 6 hours
    formulize_scandirAndClean(XOOPS_ROOT_PATH . SPREADSHEET_EXPORT_FOLDER, _formulize_DE_XF);

    // write id_reqs and tempfold to the DB if we're making an update template
    if ($template == "update") {
        $sql = "INSERT INTO " . $xoopsDB->prefix("formulize_valid_imports") . " (file, id_reqs) VALUES (\"$tempfold\", \"" . serialize($id_req) . "\")";
        if (!$res = $xoopsDB->queryF($sql)) {
            exit("Error: could not write import information to the database.  SQL: $sql<br>".$xoopsDB->error());
        }
    }

    return XOOPS_URL . SPREADSHEET_EXPORT_FOLDER . "$exfilename";
}

function prepareCellForSpreadsheetExport($column, $entry) {
    static $formDataTypes = array();
    static $exportIntroChar = null;
    $data_to_write = '';
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $thisColumnElement = $element_handler->get($column);
    $columnElementType = $thisColumnElement->getVar('ele_type');
    $columnFid = $thisColumnElement->getVar('id_form');
    if(!isset($formDataTypes[$columnFid])) {
        $data_handler = new formulizeDataHandler($columnFid);
        $formDataTypes[$columnFid] = $data_handler->gatherDataTypes();
    }
    if($exportIntroChar === null) {
        $config_handler = xoops_gethandler('config');
        $formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
        switch($formulizeConfig['exportIntroChar']) {
            case 4:
                $exportIntroChar = "";
                break;
            case 3:
                $exportIntroChar = "\t";
                break;
            case 2:
                $exportIntroChar = "'";
                break;
            case 1:
            default:
                // Google wants a ' and Excel wants a tab...assume makecsv is going to be imported into Google, and otherwise we're downloading for Excel - default preference for handling strings in csv's, so they import without being mangled. Setting for no intro char may be useful when exporting to other programs that suck in raw data.
                $exportIntroChar = strstr(getCurrentURL(),'makecsv') ? "'" : "\t";
        }
    }

    // experimental, replace displayTogether with a technique for splitting contents onto other lines below...
    /*
    $raw_data = display($entry, $column);
    if(is_array($raw_data)) {
        $data_to_write = $raw_data[0];
        unset($raw_data[0]);
        $secondaryData[$col] = $raw_data; // NOTE: CODE WOULD HAVE TO CHANGE TO HANDLE PASSING BACK SECONDARY DATA
    } else {
        $data_to_write = $raw_data;
    }*/

    $data_to_write = strip_tags(str_replace(array('<br>','<br />'), "\n", preg_replace('#<script(.*?)>(.*?)</script>#is', '', displayTogether($entry, $column, ", "))));
    // really, we should go to the datatype of the thing that we're linking to, if the element is linked
    if(strstr($data_to_write, ',') OR
        $thisColumnElement->isLinked OR
        stristr($formDataTypes[$columnFid][$column], 'char') OR
        stristr($formDataTypes[$columnFid][$column], 'text') OR
        stristr($formDataTypes[$columnFid][$column], 'binary') OR
        strtolower($formDataTypes[$columnFid][$column]) == 'json'
    ) {
        $data_to_write = str_replace("\r\n", "\n", $data_to_write); // convert lines
        $data_to_write = str_replace('"', '""', $data_to_write); // escape quotes
        $data_to_write = undoAllHTMLChars(str_replace("&quot;", '""', $data_to_write)); // escape quotes
        $data_to_write = '"'.$exportIntroChar. trans($data_to_write).'"'; // encapsulate string with quotes
    }
    return $data_to_write;
}

// draw in secondary data rows (unique to AOHC)
function prepExportSecondaryData($csvfile, $cols, $fd, $secondaryData) {
    $secondaryDataKey = 1;
    while(count((array) $secondaryData)>0) {
        $lineStarted = false;
        if($_POST['metachoice'] == 1) { // not sure about this...interaction of selected/visible metadata columns plus request to include metadata columns might mess things up here.
            $csvfile .= $fd . $fd . $fd . $fd;
            $lineStarted = true;
        }
        foreach($cols as $col) {
            if($lineStarted) {
                $csvfile .= $fd;
            }
            if(isset($secondaryData[$col])) {
                $data_to_write = $secondaryData[$col][$secondaryDataKey];
                $data_to_write = str_replace("&quot;", "\"\"", $data_to_write);
                $data_to_write = "\"" . trans($data_to_write) . "\"";
                $data_to_write = str_replace("\r\n", "\n", $data_to_write);
                $csvfile .= $data_to_write;
                if(!isset($secondaryData[$col][$secondaryDataKey+1])) {
                    unset($secondaryData[$col]);
                }
            }
            $lineStarted = true;
        }
        $csvfile .= "\r\n"; // end of a line
        $secondaryDataKey++;
    }
    return $csvfile;
}

// this function returns the data to summarize the details about the entry you are looking at
// useOldCode is used to trigger the pre-3.0 logic only when the patching process is taking place.  After that, new process should kick in since new data structure is available.
function getMetaData($entry, $member_handler, $fid="", $useOldCode=false) {
    if (!$member_handler) {
        $member_handler =& xoops_gethandler('member');
    }

    if ($useOldCode) {
        global $xoopsDB;
        $meta = q("SELECT uid, date FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req = $entry AND date > 0 ORDER BY date DESC LIMIT 0,1");
        $meta_proxyid = q("SELECT proxyid FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req = $entry AND proxyid != uid ORDER BY date DESC LIMIT 0,1");
        $meta_creation_date = q("SELECT creation_date FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req = $entry AND creation_date > 0 ORDER BY creation_date ASC LIMIT 0,1");
        $meta_to_return['last_update'] = $meta[0]['date'];
        if ($meta_creation_date[0]['creation_date']) {
            $meta_to_return['created'] = $meta_creation_date[0]['creation_date'];
        } else {
            $meta_to_return['created'] = "???";
        }
        $user = $member_handler->getUser($meta[0]['uid']);
        $meta_to_return['created_by_uid'] = $meta[0]['uid'];
        if ($user) {
            if (!$create_name = $user->getVar('name')) {
                $create_name = $user->getVar('uname');
            }
            $meta_to_return['created_by'] = $create_name;
        } else {
            $meta_to_return['created_by'] = _FORM_ANON_USER;
        }
        if ($meta_proxyid[0]['proxyid']) {
            $proxy = $member_handler->getUser($meta_proxyid[0]['proxyid']);
            $meta_to_return['last_update_by_uid'] = $meta_proxyid[0]['proxyid'];
            if ($proxy) {
                if (!$proxy_name = $proxy->getVar('name')) {
                    $proxy_name = $proxy->getVar('uname');
                }
                $meta_to_return['last_update_by'] = $proxy_name;
            } else {
                $meta_to_return['last_update_by'] = _FORM_ANON_USER;
            }
        } else {
            $meta_to_return['last_update_by'] = $meta_to_return['created_by'];
            $meta_to_return['last_update_by_uid'] = $meta_to_return['created_by_uid'];
        }
        return $meta_to_return;
    } elseif ($fid) {
        // use new class in all cases, except where we're specifically asking for old logic, which is only necessary during the initial patching process for 3.0
        $data_handler = new formulizeDataHandler($fid);
        $meta_to_return = array();
        list($meta_to_return['created'], $meta_to_return['last_update'], $meta_to_return['created_by_uid'], $meta_to_return['last_update_by_uid']) = $data_handler->getEntryMeta($entry);
        if ($meta_to_return['created'] == 0) { // not sure if the new date format will ever evaluate to 0, but just in case
            $meta_to_return['created'] = "???";
        }
        if ($creator = $member_handler->getUser($meta_to_return['created_by_uid'])) {
            $meta_to_return['created_by'] = $creator->getVar('name') ? $creator->getVar('name') : $creator->getVar('uname');
        } else {
            $meta_to_return['created_by'] = _FORM_ANON_USER;
        }
        if ($modder = $member_handler->getUser($meta_to_return['last_update_by_uid'])) {
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
    $gperm_handler = xoops_gethandler('groupperm');
    $mid = getFormulizeModId();

    // if $groups then build the necessary filter
    // build query for display groups
    $gq = "";
    if (is_array($groups)) {
        $gq = "AND (ele_display='1'";
        foreach ($groups as $thisgroup) {
            $gq .= " OR ele_display LIKE '%,$thisgroup,%'";
        }
        $gq .= ")";
    } else {
    // reset groups to be based off user object (and this instantiates it if it weren't present before)
    $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
    }

    // if current user does NOT have view_private_elements permission, then set a query to exclude those elements
    $pq = "";
    if (!$view_private_elements = $gperm_handler->checkRight("view_private_elements", $fid, $groups, $mid)) {
        $pq = "AND ele_private=0";
    }

    if (!$includeBreaks) {
        $incbreaks = "AND (ele_type != \"ib\" AND ele_type != \"areamodif\")";
    } else {
        $incbreaks = "";
    }

    if (!$frid AND !$fid) {
        exit("Error:  list of columns requested without specifying a form or a framework.");
    }

    // generate the $allcols list
    if ($frid) {
        $fids[0] = $fid;
        $check_results = checkForLinks($frid, $fids, $fid, "");
        $fids = $check_results['fids'];
        $sub_fids = $check_results['sub_fids'];
        $uid = $xoopsUser ? $xoopsUser->getVar('uid') : "0";
        foreach ($fids as $this_fid) {
            if (security_check($this_fid, "", $uid, "", $groups, $mid, $gperm_handler)) {
                $c = q("SELECT ele_id, ele_caption, ele_colhead, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form='$this_fid' $gq $pq $incbreaks AND ele_type != \"subform\" AND ele_type != \"grid\" ORDER BY ele_order");
                $cols[$this_fid] = $c;
            }
        }
        foreach ($sub_fids as $this_fid) {
            if (security_check($this_fid, "", $uid, "", $groups, $mid, $gperm_handler)) {
                $c = q("SELECT ele_id, ele_caption, ele_colhead, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form='$this_fid' $gq $pq $incbreaks AND ele_type != \"subform\" AND ele_type != \"grid\" ORDER BY ele_order");
                $cols[$this_fid] = $c;
            }
        }
    } else {
        $cols[$fid] = q("SELECT ele_id, ele_caption, ele_colhead, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form='$fid' $gq $pq $incbreaks AND ele_type != \"subform\" AND ele_type != \"grid\" ORDER BY ele_order");
    }

    return $cols;
}

// THIS FUNCTION TAKES A ID FROM THE CALCULATIONS RESULT AND RETURNS THE TEXT TO PUT ON THE SCREEN THAT CORRESPONDS TO IT
function getCalcHandleText($handle, $forceColhead=true) {
    global $xoopsDB;
    if ($handle == "entry_id") {
        return _formulize_ENTRY_ID;
    } elseif ($handle == "creation_uid") {
        return _formulize_DE_CALC_CREATOR;
    } elseif ($handle == "mod_uid") {
        return _formulize_DE_CALC_MODIFIER;
    } elseif ($handle == "creation_datetime") {
        return _formulize_DE_CALC_CREATEDATE;
    } elseif ($handle == "mod_datetime") {
        return _formulize_DE_CALC_MODDATE;
    } elseif ($handle == "creator_email") {
        return _formulize_DE_CALC_CREATOR_EMAIL;
    } elseif (is_numeric($handle)) {
        $caption = q("SELECT ele_caption, ele_colhead FROM " . $xoopsDB->prefix("formulize"). " WHERE ele_id = '$handle'");
        if ($forceColhead AND $caption[0]['ele_colhead'] != "") {
            return $caption[0]['ele_colhead'];
        } else {
            return $caption[0]['ele_caption'];
        }
    } else { // something strange has happened
        return "Could not identify the column name";
    }
}


// this function builds the scope used for passing to the getData function
// based on values of either mine, group, all, or a groupid string formatted with start, end and inbetween commas: ,1,3,
// will return array of the scope, and the value of currentView, which may have been modified depending on the user's permissions
function buildScope($currentView, $uid, $fid, $currentViewCanExpand = false) {

    $gperm_handler = xoops_gethandler('groupperm');
    $member_handler = xoops_gethandler('member');
    $mid = getFormulizeModId();
    if($uidObject = $member_handler->getUser($uid)) {
        $groups = $uidObject->getGroups();
    } else {
        $groups = array(XOOPS_GROUP_ANONYMOUS);
    }

    $scope = "";
    if ($currentView == "blank") { // send an invalid scope
        $scope = "uid=\"blankscope\"";
    } elseif (strstr($currentView, ",")) { // advanced scope, or oldscope
        $grouplist = explode("," , trim($currentView, ","));
        if ($grouplist[0] == "onlymembergroups") { // first key may be a special flag to cause the scope to be handled differently
            global $xoopsUser;
            $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
            unset($grouplist[0]);
            $grouplist = array_intersect($groups, $grouplist);
        }
        // safeguard against empty or invalid grouplists
        if (count((array) $grouplist)==0) {
            $all_users[] = "";
            $scope = makeUidFilter($all_users);
        } else {
            $scope = $grouplist;
        }
    } elseif ($currentView == "all") {
        if ($hasGlobalScope = $gperm_handler->checkRight("view_globalscope", $fid, $groups, $mid) OR $currentViewCanExpand) {
            $scope = "";
        } elseif ($hasGroupScope = $gperm_handler->checkRight("view_groupscope", $fid, $groups, $mid)) {
            $currentView = "group";
        } else {
            $currentView = "mine";
        }
    }

    // do this second last, just in case currentview =all was passed in but not valid and defaulted back to group
    if ($currentView == "group") {

        if (!$hasGroupScope = $gperm_handler->checkRight("view_groupscope", $fid, $groups, $mid) AND !$currentViewCanExpand) {
            $currentView = "mine";
        } else {
            $formulize_permHandler = new formulizePermHandler($fid);
            $scopeGroups = $formulize_permHandler->getGroupScopeGroupIds($groups);
            if ($scopeGroups === false) {
                $groupsWithAccess = $gperm_handler->getGroupIds("view_form", $fid, $mid);
                $scopeGroups = array_intersect($groupsWithAccess, $groups);
            }
            // safeguard against empty or invalid grouplists
            if (count((array) $scopeGroups)==0) {
                $all_users[] = $uid;
                $scope = makeUidFilter($all_users);
            } else {
                $scope = $scopeGroups;
            }
        }
    }

    // catch all. if it's "mine" or an old view, or there's no scope yet defined, then treat it as just the user's own entries
    if ($currentView == "mine" OR substr($currentView, 0, 4) == "old_" OR ($scope == "" AND $currentView != "all")) {
        $all_users[] = $uid;
        $scope = makeUidFilter($all_users);
    }
    return array($scope, $currentView);
}

// THIS FUNCTION SENDS TEXT THROUGH THE TRANSLATION ROUTINE IF MARCAN'S MULTILANGUAGE HACK IS INSTALLED
// THIS FUNCTION IS ALSO AWARE OF THE XLANGUAGE MODULE IF THAT IS INSTALLED.
// $lang is optional and will force the translation to be in a certain language
function trans($string, $lang = null) {
    $myts = MyTextSanitizer::getInstance();
    if (function_exists('easiestml')) {
        global $easiestml_lang;
        $easiestml_lang = isset($_GET['lang']) ? $_GET['lang'] : $easiestml_lang;   // this is required when linked with a Drupal install
        $original_easiestml_lang = $easiestml_lang;
        $easiestml_lang = $lang ? $lang : $easiestml_lang;
        $string = easiestml($string);
        $easiestml_lang = $original_easiestml_lang;
    } elseif (function_exists('xlanguage_ml')) {
        $string = xlanguage_ml($string);
    } elseif (method_exists($myts, 'formatForML')) {
        $string = $myts->formatForML($string);
    }
    return $string;
}


// THIS FUNCTION MASSAGES DATA RETURNED FROM A FORM SUBMISSION SO IT CAN BE PUT IN THE DATABASE
// param it takes is the element object ($element), and the passed value from the form ($ele)
// entry_id is passed if known (but will be "new" for new entries, and default to null for subform blanks)
// subformblankcounter is passed when we are preparing subform blank values
function prepDataForWrite($element, $ele, $entry_id=null, $subformBlankCounter=null) {

    if(!$element = _getElementObject($element)) {
		return false;
    }

    $cacheKey = serialize(func_get_args());
    static $cachedPreppedValues = array();
    if(isset($cachedPreppedValues[$cacheKey])) {
        return $cachedPreppedValues[$cacheKey];
    }


    global $myts;
    if (!$myts) {
        $myts =& MyTextSanitizer::getInstance();
    }

    $ele_type = $element->getVar('ele_type');
    $ele_value = $element->getVar('ele_value');
    $ele_id = $element->getVar('ele_id');
    switch ($ele_type) {
        case 'text':
        // if $ele_value[3] is 1 (default is 0) then treat this as a numerical field
        if ($ele_value[3] AND $ele != "{ID}" AND $ele != "{SEQUENCE}") {
            $value = preg_replace ('/[^0-9.-]+/', '', $ele);
        } else {
            $value = $ele;
        }
        $value = $myts->htmlSpecialChars($value);
        $value = (!is_numeric($value) AND $value == "") ? "{WRITEASNULL}" : $value;
        break;


        case 'textarea':
        $value = $ele;
        $value = $myts->htmlSpecialChars($value);
        $value = (!is_numeric($value) AND $value == "") ? "{WRITEASNULL}" : $value;
        break;


        case 'areamodif':
        $value = $myts->stripSlashesGPC($ele);
        break;


        case 'radio':
        $value = '';
        $opt_count = 1;
        foreach($ele_value as $ele_value_key=>$ele_value_value) {
            if ($opt_count == $ele ) {
                $otherValue = checkOther($ele_value_key, $ele_id, $entry_id, $subformBlankCounter);
                if($otherValue !== false) {
                    if($subformBlankCounter !== null) {
                        $GLOBALS['formulize_other'][$ele_id]['blanks'][$subformBlankCounter] = $otherValue;
                    } else {
                        $GLOBALS['formulize_other'][$ele_id][$entry_id] = $otherValue;
                    }
                }
                $msg.= $myts->stripSlashesGPC($ele_value_key).'<br>';
                $ele_value_key = $myts->htmlSpecialChars($ele_value_key);
                $value = $ele_value_key;
            }
            $opt_count++;
        }
        // if a value was received that was out of range
        if ($ele >= $opt_count) {
            // get the out of range value from the hidden values that were passed back
            $value = $myts->htmlSpecialChars($_POST['formulize_hoorv_'.$ele_id.'_'.$ele]);
        }
        break;


        case 'yn':
        $value = $ele;
        break;


        case 'select':
        // handle the new possible default value -- sept 7 2007
        if ($ele_value[0] == 1 AND $ele == "none") { // none is the flag for the "Choose an option" default value
            $value = "{WRITEASNULL}"; // this flag is used to terminate processing of this value
            break;
        }

        $checkForNewValues = !is_array($ele) ? array($ele) : $ele;
        $newWrittenValues = array();
        foreach($checkForNewValues as $candidateNewValue) {
            if (!$ele_value['snapshot'] AND is_string($candidateNewValue) AND substr($candidateNewValue, 0, 9) == "newvalue:") {
            // need to add a new entry to the underlying source form if this is a link
            // need to add an option to the option list for the element list, if this is not a link.
            // check for the value first, in case we are handling a series of quick ajax requests for new elements, in which a new value is being sent with all of them. We don't want to write the new value once per request!
                $newValue = substr($candidateNewValue, 9);
                if ($element->isLinked) {
                    $boxproperties = explode("#*=:*", $ele_value[2]);
                    $sourceHandle = $boxproperties[1];
                    $needToWriteEntry = false;
                    $dataArrayToWrite[$sourceHandle] = $newValue;
                    if($newValue !== '') {
                        $needToWriteEntry = true;
                    }
                    $sourceFormObject = _getElementObject($sourceHandle);
                    // get other seed values passed from the form if we're making a new entry
                    if($otherMappings = $ele_value['linkedSourceMappings']) {
                        foreach($otherMappings as $thisMapping) {
                            $otherElementToWrite = _getElementObject($thisMapping['sourceForm']);
                            $valueToPrep = '';
                            if(is_numeric($thisMapping['thisForm'])) {
                                if(!$mappingThisFormElement = _getElementObject($thisMapping['thisForm'])) {
                                    print 'Error: could not determine the element for mapping a new value. '.strip_tags(htmlspecialchars($thisMapping['thisForm'],ENT_QUOTES)).' is not a valid element reference. Please update the mapping settings in element '.$ele_id;
                                }
                                if(isset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$entry_id][$mappingThisFormElement->getVar('ele_handle')])) {
                                    $newValue = $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$entry_id][$mappingThisFormElement->getVar('ele_handle')];
                                } else {
                            $valueToPrep = isset($_POST['de_'.$element->getVar('id_form').'_'.$entry_id.'_'.$thisMapping['thisForm']]) ? $_POST['de_'.$element->getVar('id_form').'_'.$entry_id.'_'.$thisMapping['thisForm']] : $_GET['de_'.$element->getVar('id_form').'_'.$entry_id.'_'.$thisMapping['thisForm']]; // GET is used in asynch conditional element evaluation...note this means mapped fields ALSO MUST HAVE A DISPLAY CONDITION!
                                    if($valueToPrep OR $valueToPrep === 0) {
                            $newValue = prepDataForWrite($otherElementToWrite, $valueToPrep, $entry_id);
                                    } else {
                                        $thisElementDataHandler = new formulizeDataHandler($element->getVar('id_form'));
                                        $newValue = $thisElementDataHandler->getElementValueInEntry($entry_id, $thisMapping['thisForm']); // lookup the value if we couldn't get it out of POST
                                    }
                                }
                            } else {
                                $newValue = $thisMapping['thisForm']; // literal mapping value instead of an element reference
                            }
                            if($otherElementToWrite->isLinked AND !$otherElementEleValue['snapshot'] AND !$valueToPrep AND $valueToPrep !== 0) {
                                // if the field we're mapping to is linked, and we didn't find a value to prep in POST or GET, then we need to convert the literal value to the correct foreign key
                                // UNLESS the two fields are both linked and pointing to the same source, then we can use the value we've got right now, which will be the foreign key
                                // OR if the element is two links from the same source at the other, then we need 'newvalue' to be not the value we have deduced at this point, but the value in the DB in that intermediate form, so we write a foreign key to the correct source to the other element
                                $thisFormMappingElementLinkProperties = false;
                                $linkProperties = false;
                                $linkToLinkProperties = false;
                                $thisFormMappingElement = _getElementObject($thisMapping['thisForm']);
                                if($thisFormMappingElement->isLinked) {
                                    $thisFormMappingElementEleValue = $thisFormMappingElement->getVar('ele_value');
                                    $thisFormMappingElementLinkProperties = explode("#*=:*", $thisFormMappingElementEleValue[2]); // returns array, first key is form id we're linked to, second key is element we're linked to
                                    // and now go figure out if there's a second level link and we'll use that foreign key instead if the other element links directly there
                                    if($linkToLinkElement = _getElementObject($thisFormMappingElementLinkProperties[1])) {
                                        if($linkToLinkElement->isLinked) {
                                            $linkToLinkEleValue = $linkToLinkElement->getVar('ele_value');
                                            $linkToLinkProperties = explode("#*=:*", $linkToLinkEleValue[2]);
                                        }
                                    }
                                }
                                $otherElementEleValue = $otherElementToWrite->getVar('ele_value');
                                if($otherElementToWrite->isLinked) {
                                    $linkProperties = explode("#*=:*", $otherElementEleValue[2]); // returns array, first key is form id we're linked to, second key is element we're linked to
                                }
                                // check what we're supposed to do...use the value we have, lookup the linktolink value, or lookup the value in the source of the other form, based on the value we have
                                if($element->isLinked AND $linkProperties[0] == $thisFormMappingElementLinkProperties[0]) {
                                    // two fields are pointing to the same source, so use the value we have...redundant but captured here for readability
                                    $newValue = $newValue;
                                } elseif($element->isLinked AND $linkToLinkElement AND $linkToLinkElement->isLinked AND $linkProperties[0] == $linkToLinkProperties[0]) {
                                    // the starting field is linked to an element, that is linked to the same source as the other element, so lookup the value of newvalue in that second form....and we should somehow make this all recursive, right???
                                    $linkToLinkDataHandler = new formulizeDataHandler($linkToLinkProperties[0]);
                                    $newValue = $linkToLinkDataHandler->findFirstEntryWithValue($linkToLinkProperties[1], $newValue);
                                } else {
                                    $linkDataHandler = new formulizeDataHandler($linkProperties[0]);
                                    $newValue = $linkDataHandler->findFirstEntryWithValue($linkProperties[1], $newValue);
                                }
                            }
                            $dataArrayToWrite[$otherElementToWrite->getVar('ele_handle')] = $newValue;
                            if($newValue !== '') {
                                $needToWriteEntry = true;
                            }
                        }
                    }
                    if($needToWriteEntry) {
                        // check if the new value plus all mappings, is actually new, and if so, write it. If we find something that matches, don't write it, use that entry id instead.
                        $dataHandler = new formulizeDataHandler($boxproperties[0]); // 0 key is the source fid
                        if(!$newEntryId = $dataHandler->findFirstEntryWithAllValues($dataArrayToWrite)) { // check if this value has been written already, if so, use that ID
                            if($newEntryId = formulize_writeEntry($dataArrayToWrite)) {
                            formulize_updateDerivedValues($newEntryId, $sourceFormObject->getVar('id_form'));
                            }
                        }
                        $newWrittenValues[] = $newEntryId;
                    }
                } else {
                    $element_handler = xoops_getmodulehandler('elements', 'formulize');
                    if(!is_array($ele_value[2]) OR !isset($ele_value[2][$newValue])) {
                        $ele_value[2][$newValue] = 0; // create new key in ele_value[2] for this new option, set to 0 to indicate it's not selected by default in new entries
                        $element->setVar('ele_value', $ele_value);
                        $element_handler->insert($element);
                    }
                    $allValues = array_keys($ele_value[2]);
                    $selectedKey = array_search($newValue, $allValues); // value to write is the number representing the position in the array of the key that is the text value the user made
                    $selectedKey = $element->canHaveMultipleValues ? $selectedKey : $selectedKey + 1; // because we add one to the key when evaluating against single option elements below and these thigns need to line up!! YUCK
                    $newWrittenValues[] = $selectedKey;
                }
                // remove the candidate value from the original $ele so we don't have a duplicate when trying to sort it out later
                if(is_array($ele)) {
                    unset($ele[array_search($candidateNewValue,$ele)]);
                } else {
                    $ele = '';
                }
            }
        }
        // need to update $ele with any newly written values, so they can be processed properly below
        foreach($newWrittenValues as $thisNewValue) {
            if(is_array($ele)) {
                $ele[] = $thisNewValue;
            } else {
                if(count((array) $newWrittenValues)>1) {
                    print "ERROR: more than one new value created in a selectbox, when the selectbox does not allow multiple values. Check the settings of element '".$element->getVar('ele_caption')."'.";
                }
                $ele = $thisNewValue;
            }
        }


        // section to handle linked select boxes differently from others
        // first, snapshots just take the literal value passed, and that is that
        if($ele_value['snapshot']) {
            $valuesToWrite = is_array($ele) ? $ele : array($ele);
            foreach($valuesToWrite as $i=>$thisValueToWrite) {
                if(substr($thisValueToWrite, 0, 9)=='newvalue:') {
                    $valuesToWrite[$i] = substr($thisValueToWrite, 9);
                }
            }
            $value = implode("*=+*:",$valuesToWrite);
            $value = strstr($value, "*=+*:") ? "*=+*:".$value : $value; // stick the multiple value indicator on the beginning if there are multiple values. Otherwise, take the value as is.
        // if we've got a formlink, then handle it here
        } elseif (!$ele_value['snapshot'] AND is_string($ele_value[2]) and strstr($ele_value[2], "#*=:*")) {
            if (is_array($ele)) {
                $startWhatWasSelected = true;
                foreach ($ele as $whatwasselected) {
                    if (!is_numeric($whatwasselected)) {
                        continue;
                    }
                    if ($startWhatWasSelected) {
                        $value = ",";
                        $startWhatWasSelected = false;
                    }
                    $value .= $whatwasselected.",";
                }
            } elseif (is_numeric($ele)) {
                $value =$ele;
            } else {
                $value = "";
            }
            break;
        } else {
            $value = '';
            // The following code block is a replacement for the previous method for reading a select box which didn't work reliably -- jwe 7/26/04
            if(!is_array($ele_value[2])) {
                $ele_value[2] = array();
                error_log('Formulize error: attempted to save data to selectbox that has no options (element id '.$ele_id.'). In prepDataForWrite function (modules/formulize/include/functions.php)');
            }
            $temparraykeys = array_keys($ele_value[2]);
            // ADDED June 18 2005 to handle pulling in usernames for the user's group(s) -- updated for real live use September 6 2006
            if ($temparraykeys[0] === "{FULLNAMES}" OR $temparraykeys[0] === "{USERNAMES}") {
                if (is_array($ele)) {
                    $value = "";
                    foreach ($ele as $auid) {
                        $value .= "*=+*:" . $auid;
                    }
                } else {
                    $value = $ele;
                }
                break;
            }

            // THIS REALLY OLD CODE IS HARD TO READ. HERE'S A GLOSS
            // ele_value[2] is all the options that make up this element.  The values passed back from the form will be numbers indicating which value was selected.  First value is 0 for a multi-selection box, and 1 for a single selection box.
            // Subsequent values are one number higher and so on all the way to the end.  Five values in a multiple selection box, the numbers are 0, 1, 2, 3, 4.
            // masterentlistjwe and entrycounterjwe will be the same!!  There's these array_keys calls here, which result basically in a list of numbers being created, keysPassedBack, and that list is going to start at 0 and go up to whatever the last value is.  It always starts at zero, even if the list is a single selection list.  entrycounterjwe will also always start at zero.
            // After that, we basically just loop through all the possible places, 0 through n, that the user might have selected, and we check if they did.
            // The check lines are if ($whattheuserselected == $masterentlistjwe) and $ele == ($masterentlistjwe+1). note the +1 to make this work for single selection boxes where the numbers start at 1 instead of 0.
            // This is all further complicated by the fact that we're grabbing values from $entriesPassedBack, which is just the list of options in the form, so that we can populate the ultimate $value that is going to be written to the database.
            $entriesPassedBack = array_keys($ele_value[2]);
            $keysPassedBack = array_keys($entriesPassedBack);
            $entrycounterjwe = 0;
            $numberOfSelectionsFound = 0;
            foreach ($keysPassedBack as $masterentlistjwe) {
                if (is_array($ele)) {
                    if (in_array($masterentlistjwe, $ele)) {
                        $entriesPassedBack[$entrycounterjwe] = $myts->htmlSpecialChars($entriesPassedBack[$entrycounterjwe]);
                        $value = $value . "*=+*:" . $entriesPassedBack[$entrycounterjwe];
                        $numberOfSelectionsFound++;
                    }
                    $entrycounterjwe++;
                } else {
                    // plus 1 because single entry select boxes start their option lists at 1.
                    if ($ele == ($masterentlistjwe+1)) {
                        $entriesPassedBack[$entrycounterjwe] = $myts->htmlSpecialChars($entriesPassedBack[$entrycounterjwe]);
                        $value = $entriesPassedBack[$entrycounterjwe];
                    }
                    $entrycounterjwe++;
                }
            }
            // handle out of range values that are in the DB, added March 2 2008 by jwe
            if (is_array($ele)) {
                // if a value was received that was out of range. in this case we are assuming that if there are more values passed back than selections found in the valid options for the element, then there are out-of-range values we want to preserve
                while ($numberOfSelectionsFound < count((array) $ele) AND $entrycounterjwe < 1000) {
                    // keep looking for more values. get them out of the hiddenOutOfRange info
                    if (in_array($entrycounterjwe, $ele)) {
                        $value = $value.'*=+*:'.$myts->htmlSpecialChars($_POST['formulize_hoorv_'.$ele_id.'_'.$entrycounterjwe]);
                        $numberOfSelectionsFound++;
                    }
                    $entrycounterjwe++;
                }
            } else {
                // if a value was received that was out of range. added by jwe March 2 2008 (note that unlike with radio buttons, we need to check only for greater than, due to the +1 (starting at 1) that happens with single option selectboxes
                if ($ele > $entrycounterjwe) {
                    // get the out of range value from the hidden values that were passed back
                    $value = $myts->htmlSpecialChars($_POST['formulize_hoorv_'.$ele_id.'_'.$ele]);
                }
            }
        } // end of if that checks for a linked select box.
        break;


        case 'date':
            $timestamp = strtotime($ele);
            if ($ele != _DATE_DEFAULT AND $ele != "" AND $timestamp !== false) { // $timestamp !== false should catch everything by itself? under some circumstance not yet figured out, the other checks could be useful?
                $ele = date("Y-m-d", $timestamp);
            } else {
                $ele = "{WRITEASNULL}"; // forget about this date element and go on to the next element in the form
            }
            $value = ''.$ele;
            break;


        case 'sep':
        $value = $myts->stripSlashesGPC($ele);
        break;


        case 'colorpick':
        $value = $ele;
        break;


        default:
        if (file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
            $customTypeHandler = xoops_getmodulehandler($ele_type."Element", 'formulize');
            $value = $customTypeHandler->prepareDataForSaving($ele, $element, $entry_id, $subformBlankCounter);
        }
    }

    $cachedPreppedValues[$cacheKey] = $value;
    return $value;
}

// this function takes an element id or element object and returns an array of the id of the source form and the id of the element in the source form, where the options are linked form
// it returns false if the element is not linked to another for source options
function getLinkedOptionsSourceForm($elementIdOrObject) {
    if(!$element = _getElementObject($elementIdOrObject)) {
		return false;
	}
    if($element->isLinked) {
        $ele_value = $element->getVar('ele_value');
        $linkProperties = explode("#*=:*",$ele_value[2]);
        return array($linkProperties[0], $linkProperties[1]); // id of the source form for the element's options
    } else {
        return false;
    }
}


// this function takes a literal text value and converts it to a value that is valid for storing in the database.
// it is similiar to prepdataforwrite except pdfw takes values submitted through a form and converts them for storage, and this takes literal values that people might have typed into a box somewhere, like in the conditions boxes
// curly brackey entry is the id number for the entry that we're supposed to check { } terms against.
// userComparisonId is the ID of the user that should be used for {USER} when the entry is new - optional, will default to the current user's id
function prepareLiteralTextForDB($elementObject, $value, $curlyBracketEntry = null, $userComparisonId = "") {

    static $cachedLiteralTexts = array();
    $cacheKey = serialize(func_get_args());
    if(isset($cachedLiteralTexts[$cacheKey])) {
        return $cachedLiteralTexts[$cacheKey];
    }

    global $xoopsUser;
    if ($userComparisonId === "") {
        $userComparisonId = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
    }
    $ele_type = $elementObject->getVar('ele_type');
    $ele_value = $elementObject->getVar('ele_value');

    $value = parseUserAndToday($value, $elementObject);

    // convert { } terms to their actual values
    $elementHandler = xoops_getmodulehandler('elements', 'formulize');

    if(substr($value,0,1) == "{" AND substr($value,-1)=="}" AND $curlyBracketEntry) {
        $sourceHandle = substr($value, 1, -1); // remove brackets, gives us the handle
        if($sourceElementObject = $elementHandler->get($sourceHandle)) {
        if($curlyBracketEntry != 'new') {
            // get the value of the handle in this entry
            $dataHandler = new formulizeDataHandler($sourceElementObject->getVar('id_form'));
            $value = $dataHandler->getElementValueInEntry($curlyBracketEntry, $sourceHandle);
            if($value !== false) {
                    $cachedLiteralTexts[$cacheKey] = $value;
                return $value;
            }
        } elseif(isset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat']['new'][substr($value, 1, -1)])) {
                $cachedLiteralTexts[$cacheKey] = $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat']['new'][substr($value, 1, -1)];
            return $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat']['new'][substr($value, 1, -1)];
        }
        } else {
            // source handle is an invalid reference, return false
            $cachedLiteralTexts[$cacheKey] = false;
            return false;
        }
        	}

    switch ($ele_type) {

        case "yn":
        // since we're matching based on even a single character match between the query and the yes/no language constants, if the current language has the same letters or letter combinations in yes and no, then sometimes only Yes may be searched for
        if (strstr(strtoupper(_formulize_TEMP_QYES), strtoupper($value)) OR strtoupper($value) == "YES") {
            $value = 1;
        } elseif (strstr(strtoupper(_formulize_TEMP_QNO), strtoupper($value)) OR strtoupper($value) == "NO") {
            $value = 2;
        } else {
            $value = "";
        }
        break;

        case "select":
        case "checkbox":
            if($elementObject->isLinked AND $ele_value['snapshot'] != 1) {
                list($sourceFidOfElement, $sourceHandleOfElement) = getLinkedOptionsSourceForm($elementObject);
                // get the entry id of the value in the linked source of the elementObject selectbox
                $dataHandler = new formulizeDataHandler($sourceFidOfElement);
                $value = $dataHandler->findFirstEntryWithValue($sourceHandleOfElement, $value);
            } else {
                // otherwise, if the element is not linked, or the element and the comparison value are both linked to the same source, use the $value as is
                // unless it's a multi selection element (checkboxes or listbox)
                if(($ele_type == "checkbox" OR $ele_value[1]) AND $value != "{BLANK}") {
                    $value = "*=+*:".$value;
                }
            }
            break;

        default:
        if (file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
            $customTypeHandler = xoops_getmodulehandler($ele_type."Element", 'formulize');
            $value = $customTypeHandler->prepareLiteralTextForDB($value, $elementObject);
        }
    }

    if ($value == "{USER}") {
        if ($curlyBracketEntry != "new") {
            // use the defined value for USER if this is an existing entry, and one was passed in (if none was passed in, this is set to match the current user at the top of this function.
            $value = $userComparisonId;
        } else {
            $value = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
        }
    }
    $cachedLiteralTexts[$cacheKey] = $value;
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
    if (!$data_handler->elementHasValueInEntry($entry, $element_id)) {
        return false;
    } else {
        return true;
    }
}


// this function checks for singleentry status and returns the appropriate entry in the form if there is one
function getSingle($fid, $uid, $groups, $member_handler, $gperm_handler, $mid) {
    global $xoopsDB, $xoopsUser;
    // determine single/multi status
    $smq = q("SELECT singleentry FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form=$fid");
    if ($smq[0]['singleentry'] != "") {
        // find the entry that applies
        $single['flag'] = $smq[0]['singleentry'] == "on" ? 1 : "group";
        // if we're looking for a regular single, find first entry for this user
        if ($smq[0]['singleentry'] == "on") {
            if (!$xoopsUser) {
                $single['entry'] = ""; // don't set an entry for anons. they should not share the same entry in a single entry form, since user zero is actually lots of different people. cookie logic in displayform will cause their past entry to show up for them, if they have cookies working
            } else {
                $data_handler = new formulizeDataHandler($fid);
                $single['entry'] = $data_handler->getFirstEntryForUsers($uid);
            }
        } elseif ($smq[0]['singleentry'] == "group") {
            // get the first entry belonging to anyone in their groups, excluding any groups that do not have add_own_entry permission
            $formulize_permHandler = new formulizePermHandler($fid);
            $intersect_groups = $formulize_permHandler->getGroupScopeGroupIds($groups); // use specified groups if any are available
            if ($intersect_groups === false) {
                $groupsWithAccess = $gperm_handler->getGroupIds("view_form", $fid, $mid);
                $intersect_groups = array_intersect($groups, $groupsWithAccess);
            }
            $data_handler = new formulizeDataHandler($fid);
            $single['entry'] = $data_handler->getFirstEntryForGroups($intersect_groups);
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
function checkOther($key, $target_element_id, $target_entry_id, $subformBlankCounter=null){
    global $myts;
    if (!preg_match('/\{OTHER\|+[0-9]+\}/', $key) ){
        return false;
    }else{
        if( !empty($_POST['other'])) {
            if($subformBlankCounter !== null) {
                return $_POST['other']['ele_'.$target_element_id.'_new_'.$subformBlankCounter];
            } elseif($target_entry_id == "new") {
                return $_POST['other']['ele_'.$target_element_id.'_'.$target_entry_id.'_0']; // a counter is always added to the end of other values for new entries! This is in case we might make all this smarter to handle multiple new entries in the same elements on the same page later??
            } else {
                return $_POST['other']['ele_'.$target_element_id.'_'.$target_entry_id];
            }
        }else{
            return "";
        }
    }
}

// THIS FUNCTION TAKES THE 'Other' VALUES USERS MAY HAVE WRITTEN INTO THE FORM, AND SAVES THEM TO THE db IN THEIR OWN TABLE
// The other values are generated by the prepDataForWrite function, so it has to be called prior to this one
// ADDED JWE - JUNE 1 2006
function writeOtherValues($id_req, $fid, $subformBlankCounter=null) {
    global $xoopsDB, $myts;
    if (!$myts){
        $myts =& MyTextSanitizer::getInstance();
    }

    include_once XOOPS_ROOT_PATH . "/modules/formulize/class/forms.php";
    $thisForm = new formulizeForm($fid);
    if (isset($GLOBALS['formulize_other']) and is_array($GLOBALS['formulize_other'])) {
        foreach ($GLOBALS['formulize_other'] as $ele_id=>$values) {

            // filter out any ele_ids that are not part of this form, since when a framework is used, the formulize_other array would contain ele_ids from multiple forms
            if (!in_array($ele_id, $thisForm->getVar('elements'))) {
                continue;
            }

            if(isset($values['blanks'][$subformBlankCounter])) {
                $value = $values['blanks'][$subformBlankCounter];
            } else {
                if(isset($values[$id_req])) {
                $value = $values[$id_req];
                } elseif(isset($values['new']) AND in_array($id_req, $GLOBALS['formulize_newEntryIds'][$fid])) {
                    $value = $values['new'];
                } else {
                    return false; // could not find anything to write that matches the passed entry id
                }
            }

            // determine the current status of that element
            $sql = "SELECT * FROM " . $xoopsDB->prefix("formulize_other") . " WHERE ele_id='$ele_id' AND id_req='$id_req' LIMIT 0,1";
            $result = $xoopsDB->query($sql);
            $array = $xoopsDB->fetchArray($result);
            if (isset($array['other_id'])) {
                $existing_value = true;
            } else {
                $existing_value = false;
            }

            $value = $myts->htmlSpecialChars($value);
            if ($value != "" AND $existing_value) {
                // update
                $sql = "UPDATE " . $xoopsDB->prefix("formulize_other") . " SET other_text=\"" . formulize_db_escape($value) . "\" WHERE id_req='$id_req' AND ele_id='$ele_id'";
            }elseif ($value != "" AND !$existing_value) {
                // add
                $sql = "INSERT INTO " . $xoopsDB->prefix("formulize_other") . " (id_req, ele_id, other_text) VALUES (\"$id_req\", \"$ele_id\", \"" . formulize_db_escape($value) . "\")";
            }elseif ($value == "" AND $existing_value) {
                // delete
                $sql = "DELETE FROM " . $xoopsDB->prefix("formulize_other") . " WHERE id_req='$id_req' AND ele_id='$ele_id'";
            }else {
                // do nothing (only other combination is.  if (!isset($value) AND !$existing_value). ie: nothing passed, and nothing existing in DB
                $sql = false;
            }

            if ($sql) {
                if (!$result = $xoopsDB->query($sql)) {
                    exit("Error writing 'Other' value to the database with this SQL:<br>$sql");
                }
            }
            unset($GLOBALS['formulize_other'][$ele_id][$id_req]);
        }
    }
}


// THIS FUNCTION CREATES A SERIES OF ARRAYS THAT CONTAIN ALL THE INFORMATION NECESSARY FOR THE LIST OF ELEMENTS THAT GETS DISPLAYED ON THE ADMIN SIDE WHEN CREATING OR EDITING CERTAIN FORM ELEMENTS
// new use with textboxes triggers a different value to be used -- just the ele_id from the 'formulize' table, which is all that is necessary to uniquely identify the element
// note that ele_value has different contents for textboxes and selectboxes
function createFieldList($val, $textbox=false, $limitToForm=false, $name="", $firstValue="", $multi_select = false) {
    global $xoopsDB;
    array($formids);
    array($formnames);
    array($totalcaptionlist);
    array($totalvaluelist);
    $captionlistindex = 0;

    if ($limitToForm) {
        $limitToForm = " WHERE id_form = ".intval($limitToForm);
    } else {
        $limitToForm = "";
    }
    if (!$name) {
        $name = 'formlink';
    }

    $formlist = "SELECT id_form, desc_form FROM " . $xoopsDB->prefix("formulize_id") . " $limitToForm ORDER BY desc_form";

    $resformlist = $xoopsDB->query($formlist);
    if ($resformlist) {
        // loop through each form
        while ($rowformlist = $xoopsDB->fetchRow($resformlist)) {
            $fieldnames = "SELECT ele_caption, ele_id, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE id_form=$rowformlist[0] ORDER BY ele_order";
            $resfieldnames = $xoopsDB->query($fieldnames);

            // loop through each caption in the current form
            while ($rowfieldnames = $xoopsDB->fetchRow($resfieldnames)) {
                // write formname: caption to the master array that will be passed to the select box.
                $totalcaptionlist[$captionlistindex] = printSmart(trans($rowformlist[1])) . ": " . printSmart(trans($rowfieldnames[0]), 50);
                $totalvaluelist[$captionlistindex] = $rowfieldnames[1];

                // if this is the selected entry
                if ($val == $totalvaluelist[$captionlistindex] OR
                    $val == $rowformlist[0] . "#*=:*" . $rowfieldnames[2] OR
                    (is_numeric($val) AND $val == $rowfieldnames[1])
                    ) {
                    $defaultlinkselection = $captionlistindex;
                }
                $captionlistindex++;
            }
        }
    }

    if ($textbox) {
        $am_ele_formlink = _AM_ELE_FORMLINK_TEXTBOX;
        $am_formlink_none = _AM_FORMLINK_NONE_TEXTBOX;
        $am_ele_formlink_desc = _AM_ELE_FORMLINK_DESC_TEXTBOX;
    } else {
        $am_ele_formlink = _AM_ELE_FORMLINK;
        $am_formlink_none = _AM_FORMLINK_NONE;
        $am_ele_formlink_desc = _AM_ELE_FORMLINK_DESC;
    }
    if ($firstValue) { // let a passed in value override the defaults
        $am_formlink_none = $firstValue;
    }

    // make the select box and add all the options
    $formlink = new XoopsFormSelect($am_ele_formlink, $name, '', $multi_select ? 8 : 1, $multi_select);
    $formlink->addOption("none", $am_formlink_none);
    for ($i=0;$i<$captionlistindex;$i++) {
        $formlink->addOption($totalvaluelist[$i], htmlspecialchars(strip_tags($totalcaptionlist[$i]), ENT_QUOTES));
    }

    if (isset($defaultlinkselection)) {
        $formlink->setValue($totalvaluelist[$defaultlinkselection]);
    }
    $formlink->setDescription($am_ele_formlink_desc);

    if (!$textbox) {
        // return two pieces of info for selectboxes, since we need to know the element selected
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
    if (!$element = _getElementObject($element)) {
        return false;
    }

    $original_value = $value;
    static $cachedValues = array();
    if (!isset($cachedValues[$element->getVar('ele_id')][$original_value])) {
        $data_handler = new formulizeDataHandler($fid);
        $entry_id = $data_handler->findFirstEntryWithValue($element, $value);
        if ($entry_id) {
            $cachedValues[$element->getVar('ele_id')][$original_value] = $entry_id;
        } else {
            $cachedValues[$element->getVar('ele_id')][$original_value] = false;
        }
    }
    return $cachedValues[$element->getVar('ele_id')][$original_value];
}


// THIS FUNCTION OUTPUTS THE TEXT THAT GOES ON THE SCREEN IN THE LIST OF ENTRIES TABLE
// It intelligently outputs links if the text should be a link (because of textbox associations, or linked selectboxes)
// $handle is the data handle for the element
function formatLinks($matchtext, $handle, $textWidth, $entryBeingFormatted) {

    if(!$textWidth) {
        $textWidth = 35;
    }

    // if the value has HTML formatting, leave it alone
    if(strlen($matchtext) > strlen(strip_tags($matchtext))) {
        return $matchtext;
    }

    formulize_benchmark("start of formatlinks");
    global $xoopsDB, $myts;
    static $cachedValues = array();
    static $cachedTypes = array();
		static $cachedEleUIText = array();
    $matchtext = $myts->undoHtmlSpecialChars($matchtext);
    if (isMetaDataField($handle)) {
        return printSmart(trans($myts->htmlSpecialChars($matchtext)), $textWidth);
    }
    if (!isset($cachedValues[$handle])) {
        $elementMetaData = formulize_getElementMetaData($handle, true);
				$cachedEleUIText[$handle] = $elementMetaData['ele_uitextshow'] ? unserialize($elementMetaData['ele_uitext']) : array();
        $ele_value = unserialize($elementMetaData['ele_value']);
        $ele_type = $elementMetaData['ele_type'];
        if (!$ele_value) {
            return _formatLinksRegularElement($matchtext, $textWidth, $ele_type, $handle, $entryBeingFormatted);
        }
        if (!isset($ele_value[4])) {
            $ele_value[4] = 0;
        }
        if (!isset($ele_value[3])) {
            $ele_value[3] = 0;
        }
        $cachedValues[$handle] = $ele_value;
        $cachedTypes[$handle] = $ele_type;
    } else {
        $ele_value = $cachedValues[$handle];
        $ele_type = $cachedTypes[$handle];
    }
    formulize_benchmark("got element info");
    // dealing with a textbox where an associated element has been set
    if (($ele_value[4] > 0 AND $ele_type=='text') OR ($ele_value[3] > 0 AND $ele_type=='textarea')) {
        $formulize_mgr = xoops_getmodulehandler('elements', 'formulize');
        if ($ele_type == 'text') {
            $target_element = $formulize_mgr->get($ele_value[4]);
        } else {
            $target_element = $formulize_mgr->get($ele_value[3]);
        }
        $target_fid = $target_element->getVar('id_form');
        // if user has no perm in target fid, then do not make link!
        if (!$target_allowed = security_check($target_fid)) {
            return printSmart(trans($myts->htmlSpecialChars($matchtext)), $textWidth);
        }
        $matchtexts = explode(";", $matchtext); // have to breakup the textbox's text since it may contain multiple matches.  Note no space after semicolon spliter, but we trim the results in the foreach loop below.
        $printText = "";
        $start = 1;
        foreach ($matchtexts as $thistext) {
            $thistext = trim($thistext);
            if (!$start) {
                $printText .= ", ";
            }
            if ($id_req = findMatchingIdReq($target_element, $target_fid, $thistext)) {
                $printText .= "<a href='" . XOOPS_URL . "/modules/formulize/index.php?fid=$target_fid&ve=$id_req' target='_blank'>" . printSmart(trans($myts->htmlSpecialChars($thistext)), $textWidth) . "</a>";
            } else {
                $printText .= $myts->htmlSpecialChars($thistext);
            }
            $start = 0;
        }
        return $printText;
    } elseif ($ele_type=='select' AND is_string($ele_value[2]) AND strstr($ele_value[2], "#*=:*") AND $ele_value[7] == 1) {
        // dealing with a linked selectbox
        $boxproperties = explode("#*=:*", $ele_value[2]);
        // NOTE:
        // boxproperties[0] is form_id
        // [1] is handle of linked field
        $target_fid = $boxproperties[0];
        // if user has no perm in target fid, then do not make link!
        if (!$target_allowed = security_check($target_fid)) {
            return printSmart(trans($myts->htmlSpecialChars($matchtext)), $textWidth);
        }
        static $cachedQueryResults = array();
        if (isset($cachedQueryResults[$boxproperties[0]][$boxproperties[1]][$entryBeingFormatted][$handle])) {
            $id_req = $cachedQueryResults[$boxproperties[0]][$boxproperties[1]][$entryBeingFormatted][$handle];
        } else {
            // get the targetEntry by checking in the entry we're processing, for the actual value recorded in the DB for the entry id we're pointing to
            if($ele_value['snapshot']) {
                // lookup the first item that matches the saved text, in the source form... only get the first value when there are multiple, as per the logic below for non-snapshotted elements, but we should probably smartly get them all and build links properly in multiselect cases
                $id_req = findMatchingIdReq($boxproperties[1], $boxproperties[0], $matchtext);
            } else {
            $elementHandle = $handle;
            if (is_array($elementHandle)) {
                $elementHandle = $elementHandle[0];
            }
                $element_handler = xoops_getmodulehandler('elements', 'formulize');
                $currentElementObject = $element_handler->get($elementHandle);
            $currentFormId = $currentElementObject->getVar('id_form');
            $data_handler = new formulizeDataHandler($currentFormId);
            $matchEntryList = explode(",", trim($data_handler->getElementValueInEntry($entryBeingFormatted, $elementHandle), ","));
                $id_req = $matchEntryList[0]; // should be smarter than this, can't we write each piece of text as a link to its own entry id?
            }
            $cachedQueryResults[$boxproperties[0]][$boxproperties[1]][$entryBeingFormatted][$handle] = $id_req;
        }
        if ($id_req) {
            return "<a href='" . XOOPS_URL . "/modules/formulize/index.php?fid=$target_fid&ve=$id_req' target='_blank'>" . printSmart(trans($myts->htmlSpecialChars($matchtext)), $textWidth) . "</a>";
        } else {
            // no id_req found
            return printSmart(trans($myts->htmlSpecialChars($matchtext)), $textWidth);
        }
    } elseif ($ele_type =='select' AND (isset($ele_value[2]['{USERNAMES}']) OR isset($ele_value[2]['{FULLNAMES}'])) AND $ele_value[7] == 1) {
        $nametype = isset($ele_value[2]['{USERNAMES}']) ? "uname" : "name";
        static $cachedUidResults = array();
        if (isset($cachedUidResults[$matchtext])) {
            $uids = $cachedUidResults[$matchtext];
        } else {
            $uids = q("SELECT uid FROM " . $xoopsDB->prefix("users") . " WHERE $nametype = '" . formulize_db_escape($matchtext) . "' ");
            $cachedUidResults[$matchtext] = $uids;
        }
        if (count((array) $uids) == 1) {
            return "<a href='" . XOOPS_URL . "/userinfo.php?uid=" . $uids[0]['uid'] . "' target=_blank>" . printSmart(trans($myts->htmlSpecialChars($matchtext)), $textWidth) . "</a>";
        } else {
            return printSmart(trans($myts->htmlSpecialChars($matchtext)), $textWidth);
        }
    } elseif ($ele_type == 'derived') {
        return formulize_text_to_hyperlink($matchtext, $textWidth); // allow HTML codes in derived values
    } elseif($ele_type == "textarea" AND isset($ele_value['use_rich_text']) AND $ele_value['use_rich_text']) {
        return printSmart(strip_tags($matchtext), 100); // don't mess with rich text!
    } elseif($ele_type == 'radio' OR $ele_type == 'checkbox') {
        return trans(formulize_swapUIText($matchtext, $cachedEleUIText[$handle]));
    } else { // regular element
        formulize_benchmark("done formatting, about to print");
        return _formatLinksRegularElement($matchtext, $textWidth, $ele_type, $handle, $entryBeingFormatted);
    }
}


// this function simply handles the operations for formatLinks when a plain element has been identified (not a linked selectbox, associated textbox, etc, etc)
function _formatLinksRegularElement($matchtext, $textWidth, $ele_type, $handle, $entryBeingFormatted) {
    if (file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
        $elementTypeHandler = xoops_getmodulehandler($ele_type."Element", "formulize");
        $matchtext = $elementTypeHandler->formatDataForList($matchtext, $handle, $entryBeingFormatted);
        return $matchtext;
    } else {
        global $myts;
        return formulize_text_to_hyperlink($myts->htmlSpecialChars($matchtext), $textWidth);
    }
}


function formulize_text_to_hyperlink($text, $textWidth) {
    global $myts;
    $text = $myts->makeClickable(printSmart(trans($text), $textWidth));
    return str_replace("<a ", "<a target='_blank' ", $text);
}


// THIS FUNCTION INTERPRETS A TEXTBOX'S DEFAULT VALUE AND RETURNS THE CORRECT STRING
// Takes $ele_value[2] as the input (third position in ele_value array from element object)
// $form_id and $entry_id are passed in so they can be accessible within the eval'd code if necessary
function getTextboxDefault($ele_value, $form_id, $entry_id, $placeholder="") {

    if($placeholder) { // default value is placeholder text, not actual default value. only possible for textboxes, not textareas
        return "";
    }

    global $xoopsUser;

    if (strstr($ele_value, "\$default")) { // php default value
        eval(stripslashes($ele_value));
        $ele_value = $default;
    }

    $foundTerms = array();
    $position = 0;
    $foundBracket = true;
    while ($foundBracket) {
        $position = strpos($ele_value, "{", $position);
        if ($position !== false) {
            $closePos = strpos($ele_value, "}", $position);
            if ($closePos) {
                $foundTerms[] = substr($ele_value, $position+1, $closePos-$position-1);
            }
            $position++;
        } else {
            $foundBracket = false;
        }
    }

    foreach ($foundTerms as $thisTerm) {
        $replacementValue = "";
        $searchTerm = $thisTerm;
        if (strtolower($thisTerm) == "date") {
            $replacementValue = date("Y-m-d");
        }
        if (strstr(strtolower($thisTerm), "today")) {
            $number = substr($thisTerm,5);
            if (!$number) {
                $number = 0;
            }
            $replacementValue = date("Y-m-d",mktime(0, 0, 0, date("m") , date("d")+$number, date("Y")));
        }
        if (strtolower($thisTerm) == "id") {
            $replacementValue = "{ID}";
        }
        if (strtolower($thisTerm) == "sequence") {
            $replacementValue = "{SEQUENCE}";
        }
        if (!$xoopsUser AND !$replacementValue) {
            $replacementValue = "";
        } elseif (!$replacementValue) {
            if (strtolower($thisTerm) == "mail") {
                $thisTerm = "email";
            }
            $replacementValue = $xoopsUser->getVar(strtolower($thisTerm));
            if ($replacementValue == "") {
                // need to get the profile module if XOOPS 2.3 is in effect and has that module installed
                global $xoopsDB;
                $sql = "SELECT isactive FROM ".$xoopsDB->prefix("modules")." WHERE dirname='profile'";
                if ($res = $xoopsDB->query($sql)) {
                    $array = $xoopsDB->fetchArray($res);
                    if ($array['isactive']==1) {
                        // this line will cause an abort of the page load if it fails, so must check for existence and active status of the module first!
                        $profile_handler = xoops_getmodulehandler('profile', 'profile');
                        $profile = $profile_handler->get($xoopsUser->getVar('uid'));
                        $replacementValue = $profile->getVar(strtolower($thisTerm));
                    }
                }
            }
        }
        if(!$replacementValue) { continue; }
        $ele_value = str_replace("{".$searchTerm."}", $replacementValue, $ele_value);
    }
    return $ele_value;
}


function getDateElementDefault($default_hint, $entry_id = false) {
    if($default_hint == "0000-00-00") {
        $offset = formulize_getUserUTCOffsetSecs(); // user offset from UTC
        return time() + $offset;
    } elseif(preg_replace("/[^A-Z{}]/", "", $default_hint) === "{TODAY}") {
        $number = str_replace('+', '', preg_replace("/[^0-9+-]/", "", $default_hint));
        $seedTime = mktime(date("H"), date("i"), date("s"), date("m"), (date("d") + intval($number)), date("Y")); // will be based on UTC
        $offset = formulize_getUserUTCOffsetSecs(timestamp: $seedTime); // user offset from UTC
        return $seedTime + $offset;
    } elseif(substr($default_hint, 0, 1) == '{' AND substr($default_hint, -1) == '}') {
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		$element_handle = substr($default_hint, 1, -1);
		$default_hint = '';
		if($elementObject = $element_handler->get($element_handle)) {
			if(isset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$entry_id][$element_handle])) {
				$default_hint = $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$entry_id][$element_handle];
			} elseif($entry_id AND $entry_id != 'new') {
				$dataHandler = new formulizeDataHandler($elementObject->getVar('id_form'));
				$default_hint = $dataHandler->getElementValueInEntry($entry_id, $element_handle);
			}
		}
	}
    return $default_hint ? strtotime($default_hint) : "";
}


// this function returns the entry ids of entries in one form that are linked to another
// IMPORTANT:  assume $startEntry is valid for the user(security check has already been executed by now)
// therefore just need to know the allowable uids (scope) in the $targetForm
// targetForm is a special array containing the keys as specified in the framework, and the target form
// keys:  fid, keyself, keyother
// keyself and other are the ele_id from the form table for the elements that need to be matched.
// SHOULD BE REFACTORED TO BE AWARE OF A FRID SO IT CAN DETERMINE THE CORRECT DIRECTION OF LINKING, AND THEN HANDLE DOUBLE LINKED SELECTBOXES
function findLinkedEntries($startForm, $targetForm, $startEntry) {

    $mid = getFormulizeModId();
    $gperm_handler = xoops_gethandler('groupperm');
    $member_handler = xoops_gethandler('member');

    // set scope filter -- may need to pass in some exceptions here in the case of viewing entries that are covered by reports?
    global $xoopsUser;
    $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
    $owner = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
    if ($global_scope = $gperm_handler->checkRight("view_globalscope", $targetForm['fid'], $groups, $mid)) {
        $all_users = "";
        $all_groups = "";
    } elseif ($group_scope = $gperm_handler->checkRight("view_groupscope", $targetForm['fid'], $groups, $mid)) {
        $formulize_permHandler = new formulizePermHandler($targetForm['fid']);
        $all_groups = $formulize_permHandler->getGroupScopeGroupIds($groups);
        if ($all_groups === false) {
            $groupsWithAccess = $gperm_handler->getGroupIds("view_form", $targetForm['fid'], $mid);
            $all_groups = array_intersect($groups, $groupsWithAccess);
        }
        $all_users = "";
        $uq = makeUidFilter($all_users);
    } else {
        $all_users = array(0=>$owner);
        $all_groups = "";
    }

    global $xoopsDB;
    //targetForm is a special array containing the keys as specified in the framework, and the target form
    //keys:  fid, keyself, keyother
    //keyself and other are the ele_id from the form table for the elements that need to be matched.  Must get captions and convert to formulize_form format in order to find the matching values

    // linking based on uid, in the case of one to one forms, assumption is that these forms are both single_entry forms (otherwise linking one_to_one based on uid doesn't make any sense)
    if ($targetForm['keyself'] == 0) {
        // get uid of first entry
        // look for that uid in the target form
        $data_handler_start = new formulizeDataHandler($startForm);
        $data_handler_target = new formulizeDataHandler($targetForm['fid']);
        list($creation_datetime, $mod_datetime, $creation_uid, $mod_uid) = $data_handler_start->getEntryMeta($startEntry);
        $entry_ids = $data_handler_target->getAllEntriesForUsers($creation_uid, $all_users, $all_groups);
        if (count((array) $entry_ids) > 0) {
            $entries_to_return = $entry_ids;
        } else {
            $entries_to_return = "";
        }
        return $entries_to_return;
    } elseif ($targetForm['common']) {
        // support for true shared values added September 4 2006
        // return id_reqs from $targetForm['fid'] where the value of the matching element is the same as in the startEntry, startForm
        $data_handler_start = new formulizeDataHandler($startForm);
        $data_handler_target = new formulizeDataHandler($targetForm['fid']);
	  $foundValue = $data_handler_start->getElementValueInEntry($startEntry, $targetForm['keyother']); // "other" and "start" are semantically the same here, both meaning the form that the main fid joins to
	  // if keyother is a username list and the found value is numeric, and keyself is not a username list, then convert to the proper sort of name according to the rules for that type of list.
	  // if keyself is a username list and keyother is not, then lookup the user id for the username that we found
	  $element_handler = xoops_getmodulehandler('elements', 'formulize');
	  $otherElement = $element_handler->get($targetForm['keyother']);
	  $otherEleValue = $otherElement->getVar('ele_value');
  	  $otherListType = ($otherElement->getVar('ele_type')=="select"
			    AND is_array($otherEleValue[2])
			    AND (key($otherEleValue[2]) == "{USERNAMES}" OR key($otherEleValue[2]) == "{FULLNAMES}")) ? key($otherEleValue[2]) : false;
	  $selfElement = $element_handler->get($targetForm['keyself']);
	  $selfEleValue = $selfElement->getVar('ele_value');
  	  $selfListType = ($selfElement->getVar('ele_type')=="select"
			    AND is_array($selfEleValue[2])
			    AND (key($selfEleValue[2]) == "{USERNAMES}" OR key($selfEleValue[2]) == "{FULLNAMES}")) ? key($selfEleValue[2]) : false;
	  if(is_numeric($foundValue) AND $otherListType AND !$selfListType) { // convert found id to the right kind of name
	    $user = $member_handler->getUser($foundValue);
	    if(is_object($user)) {
	      if($otherListType == "{FULLNAMES}") {
		$foundValue = $user->getVar('name') ? $user->getVar('name') : $user->getVar('uname');
	      } else {
		$foundValue = $user->getVar('uname');
	      }
	    }
	  } elseif($selfListType AND !$otherListType) { // convert found value to a user id
	    $nameType = $selfListType == "{FULLNAMES}" ? 'name' : 'uname';
	    $criteria = new Criteria($nameType, $foundValue, "=");
	    $users = $member_handler->getUsers($criteria);
	    if(empty($users) AND $selfListType == "{FULLNAMES}") {
              $criteria = new Criteria('uname', $foundValue, "=");
	      $users = $member_handler->getUsers($criteria);
	    }
	    if(isset($users[0])) {
	      $foundValue = $users[0]->getVar('uid');
	    }
	  }

        // it's possible that the foundValue will be a multiselection string, in which case we need to split it and find all the entries in the other form that match, ie: do this in a loop
        // can probably refactor this to take advantage of the new _findLinkedEntries function
        $entries_to_return = array();
        foreach(explode('*=+*:',$foundValue) as $thisFoundValue) {
            $entry_ids = $data_handler_target->findAllEntriesWithValue($targetForm['keyself'], $thisFoundValue, $all_users, $all_groups);
            if (count((array) $entry_ids) > 0) {
                $entries_to_return = array_unique(array_merge($entries_to_return, $entry_ids));
            }
            if($selfEleValue[1]) {
                $entry_ids = $data_handler_target->findAllEntriesWithValue($targetForm['keyself'], '%*=+*:'.$thisFoundValue.'*=+*:%', $all_users, $all_groups, "LIKE");
        if (count((array) $entry_ids) > 0) {
                    $entries_to_return = array_unique(array_merge($entries_to_return, $entry_ids));
                }
                $entry_ids = $data_handler_target->findAllEntriesWithValue($targetForm['keyself'], '%*=+*:'.$thisFoundValue, $all_users, $all_groups, "LIKE");
                if (count((array) $entry_ids) > 0) {
                    $entries_to_return = array_unique(array_merge($entries_to_return, $entry_ids));
                }
            }
        }
        return $entries_to_return;
    } else {
        // linking based on a shared value.  in the case of one to one forms assumption is that the shared value does not appear more than once in either form's field (otherwise this will be a defacto one to many link)
        // else we're looking at a classic "shared value" which is really a linked selectbox
        $element_handler = xoops_getmodulehandler('elements', 'formulize');
        $startElement = $element_handler->get($targetForm['keyother']);
        $startEleValue = $startElement->getVar('ele_value');
        $startEleValueParts = strstr($startEleValue[2], "#*=:*") ? explode("#*=:*", $startEleValue[2]) : array();
        // option 2, start form is the linked selectbox that points to the form in question
        if (count((array) $startEleValueParts)>0 AND $startEleValueParts[0] == $targetForm['fid']) {
            // so look in the startEntry for the values in its linked field and return them.  They will be a comma separated list of entry ids in the target form.
            $data_handler_start = new formulizeDataHandler($startForm);
            $foundValue = $data_handler_start->getElementValueInEntry($startEntry, $targetForm['keyother'], $all_users, $all_groups);
            if ($foundValue) {
                if($startEleValue['snapshot']) {
                    $valuesToLookFor = explode("*=+*:",trim($foundValue, "*=+*:"));
                    return _findLinkedEntries($targetForm['keyself'], $targetForm['fid'], $valuesToLookFor, $all_users, $all_groups);
                } else {
                return explode(",",trim($foundValue, ","));
                }
            } else {
                return false;
            }
        } else { // option 3. target form is the linked selectbox
            // so look for all the entry ids in the target form, where the linked field has the startEntry in it
            // if the targetFormKeySelf is a snapshot field, then we don't want to look up the entry id, convert to the value of the keyother field in the startform
            $targetElement = $element_handler->get($targetForm['keyself']);
            $targetEleValue = $targetElement->getVar('ele_value');
            if($targetEleValue['snapshot']) {
                $data_handler_start = new formulizeDataHandler($startForm);
                // if the targetElement has an alternate element choice for the list box display, use that field instead of the keyother
                if((is_array($targetEleValue[17]) AND count((array) $targetEleValue[17]) > 0) OR $targetEleValue[17]) {
                    $fieldToCheck = is_array($targetEleValue[17]) ? $targetEleValue[17][0] : $targetEleValue[17];
                } else {
                    $fieldToCheck = $targetForm['keyother'];
                }
                $foundValue = $data_handler_start->getElementValueInEntry($startEntry, $fieldToCheck, $all_users, $all_groups);
                $valuesToLookFor = explode("*=+*:",trim($foundValue, "*=+*:"));
            } else {
                $valuesToLookFor = $startEntry;
            }
            return _findLinkedEntries($targetForm['keyself'], $targetForm['fid'], $valuesToLookFor, $all_users, $all_groups);
        }
    }
}

function _findLinkedEntries($targetFormKeySelf, $targetFormFid, $valuesToLookFor, $all_users, $all_groups) {
    $data_handler_target = new formulizeDataHandler($targetFormFid);
    $valuesToLookFor = is_array($valuesToLookFor) ? $valuesToLookFor : array($valuesToLookFor);
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $selfElement = $element_handler->get($targetFormKeySelf);
    $selfEleValue = $selfElement->getVar('ele_value');

    $totalEntriesToReturn = array();
    foreach($valuesToLookFor as $valueToLookFor) {
        $entries_to_return = $data_handler_target->findAllEntriesWithValue($targetFormKeySelf, $valueToLookFor, $all_users, $all_groups);
        if($entries_to_return !== false) {
            $totalEntriesToReturn = array_unique(array_merge($entries_to_return, $totalEntriesToReturn));
        }
        if($selfEleValue[1]) {
            $entries_to_return = $data_handler_target->findAllEntriesWithValue($targetFormKeySelf, '%*=+*:'.$valueToLookFor.'*=+*:%', $all_users, $all_groups, 'LIKE');
            if($entries_to_return !== false) {
                $totalEntriesToReturn = array_unique(array_merge($entries_to_return, $totalEntriesToReturn));
            }
            $entries_to_return = $data_handler_target->findAllEntriesWithValue($targetFormKeySelf, '%*=+*:'.$valueToLookFor, $all_users, $all_groups, 'LIKE');
            if ($entries_to_return !== false) {
                $totalEntriesToReturn = array_unique(array_merge($entries_to_return, $totalEntriesToReturn));
            }
        }
    }
    if (count((array) $totalEntriesToReturn) > 0 ) {
        return $totalEntriesToReturn;
            } else {
                return false;
            }
        }



// this function takes an entry and makes copies of it
// can take an entry in a framework and make copies of all relevant entries in all relevant forms
// note that the same relative linked selectbox relationships are preserved in cloned framework entries, but links based on common values and uids are not modified at all. this might not be desired behaviour in all cases!!!
// entries in single-entry forms are never cloned
// $entryOrFilter is the entry id number, or can be a filter string or array!
function cloneEntry($entryOrFilter, $frid, $fid, $copies=1, $callback = null, $targetEntry = "new") {

    global $xoopsDB, $xoopsUser;

    // used for updating derived values later
    $originalFid = $fid;
    $originalFrid = $frid;

    include_once XOOPS_ROOT_PATH . "/modules/formulize/class/forms.php";
    $lsbpairs = array();
    if ($frid) {
        include_once XOOPS_ROOT_PATH . "/modules/formulize/class/frameworks.php";
        include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
        $thisframe = new formulizeFramework($frid);
        $links = $thisframe->getVar('links');
        // get the element ids of the elements that are linked selectboxes pointing to another form
        $lsbindexer = 0;
        foreach ($links as $link) {
            // not a common value link, and not a uid link (key is 0 for uid links)
            if (!$link->getVar('common') AND $link->getVar('key1') AND $link->getVar('relationship') > 1) {
            // 2 is one to many
            // 3 is many to one
            if ($link->getVar('relationship') == 2) { // key1 is the textbox, key2 is the lsb
              $lsbpairs[$link->getVar('key1')] = $link->getVar('key2');
            } else { // key 1 is the lsb and key 2 is the textbox
              $lsbpairs[$link->getVar('key2')] = $link->getVar('key1');
            }
            }
        }
    }
    $entries_data = getData($frid, $fid, $entryOrFilter);
    foreach($entries_data as $entry_data) {
        $ids = internalRecordIds($entry_data, "", "", true); // true causes the first key of the returned array to be the fids
        foreach ($ids as $fid=>$entryids) {
            foreach ($entryids as $id) {
                $entries_to_clone[$fid][] = $id;
            }
        }
    }

    $dataHandlers = array();
    $entryMap = array();
    for ($copy_counter = 0; $copy_counter<$copies; $copy_counter++) {
        foreach ($entries_to_clone as $fid=>$entries) {
            // never clone an entry in a form that is a single-entry form
            $thisform = new formulizeForm($fid);
            if ($thisform->getVar('single') != "off") {
                continue;
            }
            foreach ($entries as $thisentry) {
                if (!isset($dataHandlers[$fid])) {
                    $dataHandlers[$fid] = new formulizeDataHandler($fid);
                }
                $clonedEntryId = $dataHandlers[$fid]->cloneEntry($thisentry, $callback, $targetEntry);
                $dataHandlers[$fid]->setEntryOwnerGroups(getEntryOwner($clonedEntryId, $fid), $clonedEntryId);
                $entryMap[$fid][$thisentry][] = $clonedEntryId;
            }
        }
    }

    // all entries have been made.  Now we need to fix up any linked selectboxes
    if(count($entryMap) > 0 ) {
        $element_handler = xoops_getmodulehandler('elements', 'formulize');
        foreach ($lsbpairs as $source=>$lsb) {
            $sourceElement = $element_handler->get($source);
            $lsbElement = $element_handler->get($lsb);
            $dataHandlers[$lsbElement->getVar('id_form')]->reassignLSB($sourceElement->getVar('id_form'), $lsbElement, $entryMap);
        }
    }
    foreach($entryMap[$originalFid] as $clonedMainformEntries) {
        foreach($clonedMainformEntries as $clonedMainformEntryId) {
            formulize_updateDerivedValues($clonedMainformEntryId, $originalFid, $originalFrid);
        }
    }

    return $entryMap;
}


// THIS FUNCTION HANDLES SENDING OF NOTIFICATIONS
// Does some unconventional stuff to handle custom templates for messages, and sending to everyone in a group, or to the current user (like a confirmation message)
// $groups is ignored, and should not be specified.  Param exists for historical reasons only.
function sendNotifications($fid, $event, $entries, $mid="", $groups=array()) {

    // don't send a notification twice, so we store what we have processed already and don't process again
    static $processedNotifications = array();
    $serializedEntries = serialize($entries);
    if (isset($processedNotifications[$fid][$event][$serializedEntries])) {
        return;
    }
    $processedNotifications[$fid][$event][$serializedEntries] = true;

    // 1. Get all conditions attached to this fid for this event
    // 1b. determine what users have view_globalscope on the form, and what groups that the current user is a member of have view_groupscope on the form
    // 2. foreach entry, do the following
    // 4. foreach condition, do the following
    // 5. if there's actual terms attached to the condition, see if the entry matches the condition, and if not, move on to the next condition
    // 6. if there's a custom template or subject, then save that condition for later processing
    // 7. check the uid, curuser and groupid for this condition and store it
    // 8. after processing each condition
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
    $cons = q("SELECT * FROM " . $xoopsDB->prefix("formulize_notification_conditions") . " WHERE not_cons_fid=".intval($fid)." AND not_cons_event=\"".formulize_db_escape($event)."\"");
    if (count((array) $cons) == 0) {
        return;
    }

    if (!$mid) {
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

    // start main loop
    $notificationTemplateData = array();
    $notificationTemplateRevisionData = array();
    foreach ($entries as $entry) {

	$notificationTemplateData[$entry] = "";
    $notificationTemplateRevisionData[$entry] = "";

        // user list is potentially different for each entry. ignore anything that was passed in for $groups
        if (count((array) $groups) == 0) { // if no groups specified as the owner of the current entry, then let's get that from the table
            $data_handler = new formulizeDataHandler($fid);
            $groups = $data_handler->getEntryOwnerGroups($entry);
        }

        // get the uids of all the users who are members of groups that have a specified groupscope that includes a group that is an owner group of the entry
        if (!isset($formulize_permHandler)) {
            $formulize_permHandler = new formulizePermHandler($fid);
        }
        $groups_with_specified_scope = $formulize_permHandler->getGroupsHavingSpecificScope($groups);
        // gets groups that have specific scope that does not include these groups
        $groups_with_different_specified_scope = $formulize_permHandler->getGroupsHavingDifferentSpecificScope($groups);
        $specified_group_uids = array();
        $specified_different_group_uids = array();
        if ($groups_with_specified_scope !== false) {
            // array_intersect applied to groups that have groupscope permission, and the groups that have the specified scope over the ownergroups (just in case groups were picked in the perm UI, but groupscope itself was not assigned, or turned off)
            $specified_group_uids = formulize_getUsersByGroups(array_intersect((array)$groups_with_specified_scope, (array)$groups_group), $member_handler);
        }
        if ($groups_with_different_specified_scope !== false) {
            $specified_different_group_uids = formulize_getUsersByGroups(array_intersect((array)$groups_with_different_specified_scope, (array)$groups_group), $member_handler);
        }

        // take the intersection of groups with view form perm and the owner's groups (ie: the owner's groups that have view_form perm)
        $owner_groups_with_view = array_intersect($groups_view, $groups);
        // get users in the owners-groups-that-have-view_form-perm
        $owner_groups_user_ids = formulize_getUsersByGroups($owner_groups_with_view, $member_handler);
        // get the intersection of users in the owners-groups-that-have-view_form-perm and groups with groupscope
        $group_uids = array_intersect($group_user_ids, $owner_groups_user_ids);

        // remove the users from groups-with-a-specified-scope that doesn't-include-the-owner-groups, from the users that are part of dynamically generated groupscope (if a user has a specified scope, then that should override any dynamically generated scope, and if the specified scope does not include this entry's groups, then they need to be pulled)
        $group_uids = array_diff((array)$group_uids, (array)$specified_different_group_uids);
        $group_uids = array_unique(array_merge((array)$specified_group_uids, (array)$group_uids));

        $uids_complete = array(0=>getEntryOwner($entry,$fid));
        if (count((array) $group_uids) > 0 AND count((array) $global_uids) > 0) {
            $uids_complete = array_unique(array_merge((array)$group_uids, (array)$global_uids, $uids_complete));
        } elseif (count((array) $group_uids) > 0) {
            $uids_complete = array_unique(array_merge((array)$group_uids, $uids_complete));
        } elseif (count((array) $global_uids) > 0) {
            $uids_complete = array_unique(array_merge((array)$global_uids, $uids_complete));
        }

        $uids_conditions = array();
        $saved_conditions = array();
        $data = "";
        global $formulize_existingValues;
        foreach ($cons as $thiscon) {
            // there is a specific condition for this notification
            if ($thiscon['not_cons_con'] !== "all") {
                $thesecons = unserialize($thiscon['not_cons_con']);
                $elements = unserialize($thesecons[0]);
                $ops = unserialize($thesecons[1]);
                $terms = unserialize($thesecons[2]);
                $start = 1;
                $blankFilters = array();
                $noElementsChanged = true;
                for ($i=0;$i<count((array) $elements);$i++) {

                    // if event is update, then check if term is changed from previous save
                    // if all terms are same as previous save, then we ignore entry
                    if($event == 'update_entry') {
                        $elementEyeHandle = convertElementIdsToElementHandles(array($elements[$i]));
                        $elementEyeHandle = $elementEyeHandle[0];
                        if(isset($formulize_existingValues[$fid][$entry]['before_save'][$elementEyeHandle])
                           OR isset($formulize_existingValues[$fid][$entry]['after_save'][$elementEyeHandle])) {
                            $beforeCheckValue = isset($formulize_existingValues[$fid][$entry]['before_save'][$elementEyeHandle]) ? $formulize_existingValues[$fid][$entry]['before_save'][$elementEyeHandle] : '';
                            $afterCheckValue = isset($formulize_existingValues[$fid][$entry]['after_save'][$elementEyeHandle]) ? $formulize_existingValues[$fid][$entry]['after_save'][$elementEyeHandle] : '';
                            if($beforeCheckValue !== $afterCheckValue) {
                                $noElementsChanged = false;
                            }
                        }
                    }

                    $terms[$i] = parseUserAndToday($terms[$i]);

                    if ($ops[$i] == "NOT") {
                        $ops[$i] = "!=";
                    }
                    // seed with the entry
                    if($start) {
                        $filter = $entry;
                        $start = 0;
                    }
                    // add to blank filters if necessary, or add to regular filter
                    if ($terms[$i]=="{BLANK}") {
                        $blankFilter = $elements[$i]."/**//**/".$ops[$i]."][".$elements[$i]."/**//**/";
                        if ($ops[$i] == "!=" OR $ops[$i] == "NOT LIKE") {
                            $blankFilters['and'][] = $blankFilter." IS NOT NULL ";
                        } else {
                            $blankFilters['or'][] = $blankFilter." IS NULL ";
                        }
                    } else {
                        $filter .= "][".$elements[$i]."/**/".$terms[$i]."/**/".$ops[$i];
                    }
                }
                if($event == 'update_entry' AND $noElementsChanged) {
                    continue; // did not pass since there hasn't actually be an update that changed the elements on which the notification state depends
                }
                // add in blank filter stuff
                if (isset($blankFilters['and'])) {
                    foreach ($blankFilters['and'] as $thisAndFilter) {
                        $filter .= "][".$thisAndFilter;
                    }
                }
                // reconfigure if there's an 'or' filter for handling blanks
                if (isset($blankFilters['or'])) {
                    $filter = array(0=>array(0=>"and",1=>$filter), 1=>array(0=>"or",1=>implode("][",$blankFilters['or'])));
                }
                include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
                $data = getData("", $fid, $filter);
                if ($data[0] == "") {
                    continue;
                }
            }
            // condition passed the test, so check for custom template or subject
            if ($thiscon['not_cons_template'] OR $thiscon['not_cons_subject']) {
                $saved_conditions[] = $thiscon;
                continue; // proceed to the next one
            }
            // passed the test and not custom, so save the uid, curuser, groupid info
            list($uids_conditions, $omit_user) = compileNotUsers($uids_conditions, $thiscon, $uid, $member_handler, false, $entry, $fid);
        } // end of each condition

        if (isset($GLOBALS['formulize_notification_email'])) {
            $uids_complete[] = -1; // if we are notifying an arbitrary e-mail address, then this uid will have been added to the uids_conditions array, so let's add it to the complete array, so that our notification doesn't get ignored as being "out of scope" based on uids
        }

        // intersect all possible uids with the ones valid for this condition, and handle subscribing necessary users
        $uids_real = compileNotUsers2($uids_conditions, $uids_complete, $fid, $event, $mid);
        // cannot bug out (return) if $uids_real is empty, since there are still the custom conditions to evaluate below

        // get form object so the title can be used in notification messages
        static $formObjs = array(); // make this static so we don't have to hit the database over again if we've already got this form object
        include_once XOOPS_ROOT_PATH  . "/modules/formulize/class/forms.php";
        if (!isset($formObjs[$fid])) {
            $formObjs[$fid] = new formulizeForm($fid);
        }
        $extra_tags = array();
        if ($xoopsUser) {
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
        if ($profileFormId == $fid) {
            $owner = getEntryOwner($entry, $fid);
            $extra_tags['VIEWURL'] = XOOPS_URL."/userinfo.php?uid=$owner";
        } else {
            $extra_tags['VIEWURL'] = XOOPS_URL."/modules/formulize/index.php?fid=$fid&ve=$entry";
        }
        $extra_tags['ENTRYID'] = $entry;
        $extra_tags['SITEURL'] = XOOPS_URL;

        if (count((array) $uids_real) > 0) {
            formulize_processNotification($event, $extra_tags, $fid, $uids_real, $mid, $omit_user);
        }
        // reset for the potential processing of saved conditions
        if (isset($GLOBALS['formulize_notification_email'])) {
            unset($GLOBALS['formulize_notification_email']);
            unset($uids_complete[array_search(-1, $uids_complete)]);
        }
        unset($uids_real);

        // handle custom conditions
        foreach ($saved_conditions as $thiscon) {
            if ($thiscon['not_cons_template']) {
                $templateFileName = substr($thiscon['not_cons_template'], -4) == ".tpl" ? $thiscon['not_cons_template'] : $thiscon['not_cons_template'] . ".tpl";
                if (!file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/mail_template/".$templateFileName)) {
                    continue;
                } else {
                    $templateFileContents = file_get_contents(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/mail_template/".$templateFileName);
                }
            }
            if (($templateFileContents AND strstr($templateFileContents, "{ELEMENT")) OR strstr($thiscon['not_cons_subject'], "{ELEMENT")) {
                // gather the data for this entry and make it available to the template, since it uses an element tag in the message
                // Only do this getData call if we don't already have data from the database. $notificationTemplateData[$entry][0] == "" will probably never be true in Formulize 3.0 and higher, but will evaluate as expected, with a warning about [0] being an invalid offset or something like that
                if ($notificationTemplateData[$entry][0] == "" OR $notificationTemplateData[$entry] == "") {
                    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
                    $notificationTemplateData[$entry] = getData("", $fid, $entry);
                    // if the revision table is on for the form, then gather the data from the most revent revision for the entry
                    if(!isset($data_handler)) {
                        $data_handler = new formulizeDataHandler($fid);
                    }
                    // get the last revision we flagged (after saving, before updating derived values!)
                    if($event == 'update_entry' AND $revisionEntry = $data_handler->getRevisionForEntry($entry, $GLOBALS['formulize_snapshotRevisions'][$fid][$entry])) {
                        $notificationTemplateRevisionData[$entry] = $revisionEntry;
                    }
                }
                // get all the element IDs for the current form
                $form_handler = xoops_getmodulehandler('forms', 'formulize');
                $formObject = $form_handler->get($fid);
                foreach ($formObject->getVar('elementHandles') as $elementHandle) {
                    $extra_tags['ELEMENT'.strtoupper($elementHandle)] = trans(html_entity_decode(displayTogether($notificationTemplateData[$entry][0], $elementHandle, ", "), ENT_QUOTES));
                    if($notificationTemplateRevisionData[$entry]) {
                        $extra_tags['REVISION_ELEMENT_'.strtoupper($elementHandle)] = trans(html_entity_decode(displayTogether($notificationTemplateRevisionData[$entry][0], $elementHandle, ", "), ENT_QUOTES));
                    }
                    // for legacy compatibility, we provide both with and without _ keys in the extra tags array.
                    $extra_tags['ELEMENT_'.strtoupper($elementHandle)] = trans($extra_tags['ELEMENT'.strtoupper($elementHandle)]);
                }
            }
            $uids_cust_con = array();
            list($uids_cust_con, $omit_user) = compileNotUsers($uids_cust_con, $thiscon, $uid, $member_handler, true, $entry, $fid);
            if (isset($GLOBALS['formulize_notification_email'])) {
                $uids_complete[] = -1; // if we are notifying an arbitrary e-mail address, then this uid will have been added to the uids_conditions array, so let's add it to the complete array, so that our notification doesn't get ignored as being "out of scope" based on uids
            }
            $uids_cust_real = compileNotUsers2($uids_cust_con, $uids_complete, $fid, $event, $mid);
            if (count((array) $uids_cust_real) > 0) {
                formulize_processNotification($event, $extra_tags, $fid, $uids_cust_real, $mid, $omit_user, $thiscon['not_cons_subject'], $thiscon['not_cons_template']);
            }
            // reset for the next runthrough of the loop
            if (isset($GLOBALS['formulize_notification_email'])) {
                unset($GLOBALS['formulize_notification_email']);
                unset($uids_complete[array_search(-1, $uids_complete)]);
            }
            unset($uids_cust_real);
            unset($uids_cust_con);
        }

        unset($uids_conditions);
        unset($saved_conditions);
    } // end of each entry
}


// $template should include .tpl on the end
function sendNotificationToEmail($email, $event, $tags, $overrideSubject="", $overrideTemplate="") {
    $module_handler = xoops_gethandler('module');
    $module = $module_handler->get(getFormulizeModId());

    global $xoopsConfig;
    if (file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/mail_template/form_newentry.tpl")) {
        include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/modinfo.php";
        $templateDir = XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/mail_template/";
        include_once XOOPS_ROOT_PATH."/language/".$xoopsConfig['language']."/notification.php";
    } else {
        $templateDir = XOOPS_ROOT_PATH."/modules/formulize/language/english/mail_template/";
        include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/modinfo.php";
        include_once XOOPS_ROOT_PATH.'/language/english/notification.php';
    }

    $tags['X_ITEM_NAME'] = '[' . _NOT_ITEMNAMENOTAVAILABLE . ']';
    $tags['X_ITEM_URL']  = '[' . _NOT_ITEMURLNOTAVAILABLE . ']';
    $tags['X_ITEM_TYPE'] = '[' . _NOT_ITEMTYPENOTAVAILABLE . ']';
    $tags['X_MODULE'] = $module->getVar('name');
    $tags['X_MODULE_URL'] = XOOPS_URL . '/modules/' . $module->getVar('dirname') . '/';
    $tags['X_NOTIFY_CATEGORY'] = $category;
    $tags['X_NOTIFY_EVENT'] = $event;

    switch ($event) {
        case("new_entry"):
            $template = $overrideTemplate ? $overrideTemplate : 'form_newentry.tpl';
            $subject = $overrideSubject ? $overrideSubject : _MI_formulize_NOTIFY_NEWENTRY_MAILSUB;
            break;

        case("update_entry"):
            $template = $overrideTemplate ? $overrideTemplate : 'form_upentry.tpl';
            $subject = $overrideSubject ? $overrideSubject : _MI_formulize_NOTIFY_UPENTRY_MAILSUB;
            break;

        case("delete_entry"):
            $template = $overrideTemplate ? $overrideTemplate : 'form_delentry.tpl';
            $subject = $overrideSubject ? $overrideSubject : _MI_formulize_NOTIFY_DELENTRY_MAILSUB;
            break;

        default:
            $template = $overrideTemplate;
            $subject = $overrideSubject;
    }

    $template = substr($template, -4) == ".tpl" ? $template : $template . ".tpl";
    include_once XOOPS_ROOT_PATH . '/include/notification_constants.php';

  if(strstr($email, ',')) {
	$emails = explode(',',$email);
  } else {
	$emails = array($email);
  }
  $success = false;
  foreach($emails as $email) {
	$xoopsMailer = getMailer();
    $xoopsMailer->useMail();
    foreach ($tags as $k=>$v) {
        if(substr($k, 0, 11) == "ATTACHFILE-") {
            $fileName = substr($k, 11, strlen($k));
            $filePath = $v;
            $xoopsMailer->addAttachment($filePath, $fileName);
        } else {
            $xoopsMailer->assign($k, preg_replace("/&amp;/i", '&', $v));
        }
    }
    // Set up the mailer
    $xoopsMailer->setTemplateDir($templateDir);
    if (substr($template, -4)!=".tpl") {
        $template .= '.tpl';
    }
    $xoopsMailer->setTemplate($template);
    $xoopsMailer->setToEmails($email);
    $xoopsMailer->setSubject($subject);
    $success = $xoopsMailer->send();
  }
  return $success;
}


function formulize_getUsersByGroups($groups, $member_handler="") {
    if (!$member_handler) {
        $member_handler =& xoops_gethandler('member');
    }

    $users = array();
    foreach ($groups as $group) {
        if ($group == XOOPS_GROUP_USERS) {
            continue;
        }
        $temp_users = $member_handler->getUsersByGroup($group);
        $users = array_merge($users, (array)$temp_users);
        unset($temp_users);
    }
    return array_unique($users);
}


// this function can be called from within a loop, and will merge uids_conditions with all previously recorded values
function compileNotUsers($uids_conditions, $thiscon, $uid, $member_handler, $reinitialize, $entry, $fid) {
    static $omit_user = null;
    if ($reinitialize) {
        // need to do this when handling saved conditions, since each time we call this function it's a new "event" that we're dealing with
        $omit_user = null;
    }
    if ($thiscon['not_cons_uid'] > 0) {
        $uids_conditions[] = $thiscon['not_cons_uid'];
    } elseif ($thiscon['not_cons_curuser'] > 0) {
        $uids_conditions[] = $uid;
    } elseif ($thiscon['not_cons_groupid'] > 0) {
        $uids_temp = $member_handler->getUsersByGroup($thiscon['not_cons_groupid']);
        $uids_conditions = array_merge((array)$uids_temp, $uids_conditions);
        unset($uids_temp);
    } elseif ($thiscon['not_cons_creator'] > 0) {
        $uids_temp = getEntryOwner($entry, $fid);
        $uids_conditions[] = $uids_temp;
        unset($uids_temp);
    } elseif ($thiscon['not_cons_elementuids'] > 0) {
        // get the entry at issue and extract the uids from the specified element
        $data_handler = new formulizeDataHandler($fid);
        $value = $data_handler->getElementValueInEntry($entry, intval($thiscon['not_cons_elementuids']));
        if ($value) {
            $uids_temp = explode("*=+*:", trim($value,"*=+*:"));
            $uids_conditions = array_merge((array)$uids_temp, $uids_conditions);
        }
        unset($uids_temp);
    } elseif ($thiscon['not_cons_linkcreator'] > 0) {
        // get the entry at issue and extract the uid(s) of the creator(s) of the items selected in the specified element
        $data_handler = new formulizeDataHandler($fid);
        $value = $data_handler->getElementValueInEntry($entry, intval($thiscon['not_cons_linkcreator'])); // get the values in the linked fields
        // the entry ids (in their source form) of the items selected in the linked selectbox, should always be an array of at least one value
        $entry_ids = explode(",", trim($value, ","));
        if (count((array) $entry_ids) > 0) {
            // need to get the form that 'not_cons_linkcreator' is linked to
            $element_handler =& xoops_getmodulehandler('elements', 'formulize');
            $elementObject = $element_handler->get(intval($thiscon['not_cons_linkcreator']));
            // key 0 will be the form id that is the source for the values in this linked selectbox
            $linkProperties = explode("#*=:*", $elementObject->getVar('ele_value'));
            $data_handler2 = new formulizeDataHandler($linkProperties[0]);
            $uids_temp = $data_handler2->getAllUsersForEntries($entry_ids);
            if (count((array) $uids_temp) > 0) {
                // no need for type hint (array) in this case because getAllUsersForEntries always returns an array, even if its empty
                $uids_conditions = array_merge($uids_temp, $uids_conditions);
            }
            unset($uids_temp);
        } else {
            $uids_conditions = array();
        }
    } elseif ($thiscon['not_cons_elementemail'] > 0) {
        // get the element at issue and extract the e-mail address from it
        $data_handler = new formulizeDataHandler($fid);
        $value = $data_handler->getElementValueInEntry($entry, intval($thiscon['not_cons_elementemail']));
        if ($value) {
            // split on commas
            $values = explode(",", $value);
            $good_values = array();

            // check each email address, exclude the ones ending with .archived
            foreach ($values as $a_value) {
                // build a new array of emails
                if (".archived" != substr($a_value, -9)) {
                    $good_values[] = $a_value;
                }
            }

            // implode the new array of emails with commas, set $value to this new string
            $value = implode(",", $good_values);

            $GLOBALS['formulize_notification_email'] = $value;
            $uids_conditions = array_merge(array(-1), $uids_conditions); // minus 1 means we're sending direct to an email address, not using internal user notification logic based on user objects
        }
    } elseif($thiscon['not_cons_arbitrary']) {
        $GLOBALS['formulize_notification_email'] = $thiscon['not_cons_arbitrary'];
        $uids_conditions = array_merge(array(-1), $uids_conditions); // minus 1 means we're sending direct to an email address, not using internal user notification logic based on user objects
    }
    if (in_array($uid, $uids_conditions)) {
        // in Formulize, users are always notified of things, even things they do themselves.
        $omit_user = 0;
    }
    return array(0=>$uids_conditions, 1=>$omit_user);
}


function compileNotUsers2($uids_conditions, $uids_complete, $fid, $event, $mid) {
    global $xoopsDB;
    $notification_handler = xoops_gethandler('notification');
    $uids_conditions = array_unique($uids_conditions);
    $uids_real = array_intersect($uids_conditions, $uids_complete);
    // figure out who is not subscribed to the event, and subscribe them once
    $subd_uids = q("SELECT not_uid FROM " . $xoopsDB->prefix("xoopsnotifications") . " WHERE not_event=\"".formulize_db_escape($event)."\" AND not_category=\"form\" AND not_modid=$mid AND not_itemid=$fid");
    $uids_subd = array();
    foreach ($subd_uids as $thisuid) {
        $uids_subd[] = $thisuid['not_uid'];
    }
    $uids_not_subd = array_diff($uids_real, $uids_subd);
    foreach ($uids_not_subd as $thisuid) {
        if ($thisuid <= 0) {
            continue;
        }
        $notification_handler->subscribe("form", $fid, $event, XOOPS_NOTIFICATION_MODE_SENDONCETHENDELETE, $mid, $thisuid);
    }
    return $uids_real;
}

// send notifications, or cache notifications so they can be triggered by a cron job later
// turn on the preference in the module to use the cron feature
function formulize_processNotification($event, $extra_tags, $fid, $uids_to_notify, $mid, $omit_user, $subject="", $template="") {

	$config_handler = xoops_gethandler('config');
    $formulizeConfig = $config_handler->getConfigsByCat(0, $mid);
    $notifyByCron = $formulizeConfig['notifyByCron'];

    // template gets .tpl added on end when the notification system runs, so trip out any .tpl that might be in there now just in case
    $template = str_replace('.tpl', '', $template);

    if($notifyByCron) {
        $notFile = fopen(XOOPS_ROOT_PATH."/modules/formulize/cache/formulizeNotifications.txt","a");
        formulize_getLock($notFile);
        foreach($uids_to_notify as $uid_to_notify) {
            if($uid_to_notify>0) {
                formulize_processNotificationWriteLine($notFile, $event, $extra_tags, $fid, array($uid_to_notify), $mid, $omit_user, $subject, $template);
            } else {
                foreach(explode(",", $GLOBALS['formulize_notification_email']) as $email) {
                    formulize_processNotificationWriteLine($notFile, $event, $extra_tags, $fid, array(-1), $mid, $omit_user, $subject, $template, $email);
                }
            }
        }
        flock($notFile, LOCK_UN);
        fclose($notFile);
    } else {
        include_once XOOPS_ROOT_PATH."/modules/formulize/notify.php";
        formulize_notify($event, $extra_tags, $fid, $uids_to_notify, $mid, $omit_user, $subject, $template);
    }
}

function formulize_processNotificationWriteLine($notFile, $event, $extra_tags, $fid, $uid_to_notify, $mid, $omit_user, $subject, $template, $email="") {
    fwrite($notFile,
        serialize(
            array(
                $event,
                $extra_tags,
                $fid,
                $uid_to_notify,
                $mid,
                $omit_user,
                $subject,
                $template,
                $email
            )
        )."19690509\r\n"
    );
}

// this function attempts to get a lock on the given file resource
function formulize_getLock($fileResource) {
    $ourTurn = false;
    $lockTries = 0;
    while(!$ourTurn) {
        $ourTurn = flock($fileResource, LOCK_EX);
        if(!$ourTurn) {
            if($lockTries == 30) {
                exit("Formulize fatal error: Could not get a lock on the notifications cache file after one minute.");
            } else {
                $lockTries++;
                sleep(2);
            }
        }
    }
    return true;
}


// this function takes a series of columns and gets the headers for them
function getHeaders($cols, $colsIsElementHandles = true) {
    global $xoopsDB;

    foreach ($cols as $col) {
        if($col == "entry_id") {
            $headers[$col] = _formulize_ENTRY_ID;
        }elseif ($col == "creation_uid") {
            $headers[$col] = _formulize_DE_CALC_CREATOR;
        } elseif ($col == "mod_uid") {
            $headers[$col] = _formulize_DE_CALC_MODIFIER;
        } elseif ($col=="creation_datetime") {
            $headers[$col] = _formulize_DE_CALC_CREATEDATE;
        } elseif ($col=="mod_datetime") {
            $headers[$col] = _formulize_DE_CALC_MODDATE;
        } elseif ($col=="creator_email") {
            $headers[$col] = _formulize_DE_CALC_CREATOR_EMAIL;
        } else {
            if ($colsIsElementHandles) {
                $whereClause = "ele_handle = '$col'";
            } else {
                $whereClause = "ele_id = '$col'";
            }
            $temp_cap = q("SELECT ele_caption, ele_colhead, ele_handle FROM " . $xoopsDB->prefix("formulize") . " WHERE $whereClause");
            if ($temp_cap[0]['ele_colhead'] != "") {
                $headers[$temp_cap[0]['ele_handle']] = $temp_cap[0]['ele_colhead'];
            } else {
                $headers[$temp_cap[0]['ele_handle']] = $temp_cap[0]['ele_caption'];
            }
        }
    }
    return $headers;
}

// this function returns the handles of form elements based on the requested form and optionally framework id
function getDefaultCols($fid, $frid="") {
	global $xoopsDB, $xoopsUser;

	if($frid) { // expand the headerlist to include the other forms
		$fids[0] = $fid;
		$check_results = checkForLinks($frid, $fids, $fid, "");
		$fids = $check_results['fids'];
		$sub_fids = $check_results['sub_fids'];
		$gperm_handler = &xoops_gethandler('groupperm');
		$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
		$uid = $xoopsUser ? $xoopsUser->getVar('uid') : "0";
		$mid = getFormulizeModId();
		$ele_handles = array();
		$processedFids = array();
		foreach($fids as $this_fid) {
			if(security_check($this_fid, "", $uid, "", $groups, $mid, $gperm_handler) AND !isset($processedFids[$this_fid])) {
				$ele_handles = array_merge($ele_handles, getHeaderList($this_fid, true, true));
				$processedFids[$this_fid] = true;
			}
		}
		foreach($sub_fids as $this_fid) {
			if(security_check($this_fid, "", $uid, "", $groups, $mid, $gperm_handler) AND !isset($processedFids[$this_fid])) {
				$ele_handles = array_merge($ele_handles, getHeaderList($this_fid, true, true));
				$processedFids[$this_fid] = true;
			}
		}

		return $ele_handles;

	} else {
		$ele_handles = getHeaderList($fid, true, true); // third param causes element handles to be returned instead of IDs
		return $ele_handles;
	}

}

// THIS FUNCTION OVERWRITES OR APPENDS TO A VALUE IN A SPECIFIED FORM ELEMENT
// DEPRECATED. VERY INEFFICIENT, SINCE IT ONLY UPDATES ONE FIELD AT A TIME.  BETTER TO USE formulize_writeEntry, except in cases where you actually need to only update one field.  In most cases you want to update multiple fields in an entry, so don't use this inside a loop. it will generate more queries than you need
// prevValue is now completely not required.  lvoverride is only used if you want to pass in a pre-formatted ,1,3,15,17, style string for inserting into a linked selectbox field.
// linkedTargetHint is used if we are writing to a linked selectbox element, and we have some indication from the UI what the entry is that we're supposed to link to.  This allows for disambiguation of target values that we might be trying to link to, that might occur in more than one entry.
function writeElementValue($formframe, $ele, $entry, $value, $append="replace", $prevValue=null, $lvoverride=false, $linkedTargetHint = "") {

    global $xoopsUser, $formulize_mgr, $xoopsDB, $myts;
    if (!is_object($myts)) {
        $myts =& MyTextSanitizer::getInstance();
    }

    if (!$formulize_mgr) {
        $formulize_mgr =& xoops_getmodulehandler('elements', 'formulize');
    }

    $uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
    $date = date ("Y-m-d");

    if (is_numeric($ele)) {
        $element =& $formulize_mgr->get($ele);
        $element_id = $ele;
    } else {
        $framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
        $frameworkObject = $framework_handler->get($formframe);
        if (is_object($frameworkObject)) {
            $frameworkElementIds = $frameworkObject->getVar('element_ids');
            if (isset($frameworkElementIds[$ele])) {
                $element_id = $frameworkElementIds[$ele];
                $element =& $formulize_mgr->get($element_id);
            }
        }
        if (!is_object($element)) {
            // then check the element data handles instead
            $element =& $formulize_mgr->get($ele);
        }
    }

    if (!is_object($element)) {
        print "<b>Error: could not save the value for element: ".$ele.". Please notify your webmaster, or <a href=\"mailto:info@formulize.org\">info@formulize.org</a> about this error.</b>";
        return;
    }

    $ele_value = $element->getVar('ele_value');

    if (!is_array($value)) { // value can be an array of multiple values -- initially that only worked for linked selectboxes
        if ($element->getVar('ele_type') == "yn") {
            $value = strtoupper($value) == strtoupper(_formulize_TEMP_QYES) ? 1 : $value;
            $value = strtoupper($value) == strtoupper(_formulize_TEMP_QNO) ? 2 : $value;
        } else {
            $value = $myts->htmlSpecialChars($value);
        }
    } else {
        foreach ($value as $id=>$thisValue) {
            if ($element->getVar('ele_type') == "yn") {
                $value[$id] = strtoupper($value[$id]) == strtoupper(_formulize_TEMP_QYES) ? 1 : $value[$id];
                $value[$id] = strtoupper($value[$id]) == strtoupper(_formulize_TEMP_QNO) ? 2 : $value[$id];
            } else {
                $value[$id] = $myts->htmlSpecialChars($value[$id]);
            }
        }
    }

    $form_handler = xoops_getmodulehandler('forms', 'formulize');

    if (is_string($ele_value[2]) AND $foundit = strstr($ele_value[2], "#*=:*") AND !$lvoverride AND !$ele_value['snapshot']) {
        // completely rejig things for a linked selectbox
        $boxproperties = explode("#*=:*", $ele_value[2]);
        // NOTE:
        // boxproperties[0] is fid, 1 is the handle
        if (!is_array($value)) {
            // convert $value to an array if it's not already. arrays are only valid for linked selectboxes for now
            $temp_value = $value;
            unset($value);
            $value = array(0=>$temp_value);
        }
        static $cachedEntryIds = array();
        $foundEntryIds = array();
        $searchForValues = array();
        foreach ($value as $thisValue) {
            if (isset($cachedEntryIds[$boxproperties[0]][$boxproperties[1]][$thisValue])) {
                $foundEntryIds[] = $cachedEntryIds[$boxproperties[0]][$boxproperties[1]][$thisValue];
            } else {
                $searchForValues[] = formulize_db_escape(html_entity_decode($thisValue));
            }
        }

        // need to check for link to a link, and change target if that's what we're dealing with
        $element_handler = xoops_getmodulehandler('elements', 'formulize');
        $sourceElement = $element_handler->get($boxproperties[1]);
        $linkedTargetHintInQuery = "";
        if (is_object($sourceElement)) {
            $sourceEleValue = $sourceElement->getVar('ele_value');
            if (strstr($sourceEleValue[2], "#*=:*") AND !$sourceEleValue['snapshot']) {
                $sourceParts = explode("#*=:*", $sourceEleValue[2]);
                $sourceFormObject = $form_handler->get($sourceParts[0]);
                $linkQueryResult = q("SELECT `entry_id` FROM " . $xoopsDB->prefix("formulize_".$sourceFormObject->getVar('form_handle')) . " WHERE `".$sourceParts[1]."` = '".implode("' OR `".$sourceParts[1]."` = '", $searchForValues) . "'");
                unset($searchForValues);
                foreach ($linkQueryResult as $linkedEntryId) {
                    $searchForValues[] = $linkedEntryId['entry_id'];
                }
                if ($linkedTargetHint) {
                    $linkedTargetHintInQuery = " AND `entry_id` = $linkedTargetHint";
                }
            }
        }

        if (count((array) $searchForValues) > 0) {
            $boxFormObject = $form_handler->get($boxproperties[0]);
            $entry_id_q = q("SELECT `entry_id`, `".$boxproperties[1]."` FROM " . $xoopsDB->prefix("formulize_".$boxFormObject->getVar('form_handle')) . " WHERE `".$boxproperties[1]."` = '".implode("' OR `".$boxproperties[1]."` = '", $searchForValues) . "' $linkedTargetHintInQuery");
            foreach ($entry_id_q as $thisEntryId) {
                $cachedEntryIds[$boxproperties[0]][$boxproperties[1]][$thisEntryId[$boxproperties[1]]] = $thisEntryId['entry_id'];
                $foundEntryIds[] = $thisEntryId['entry_id'];
            }
        }
        if (count((array) $foundEntryIds)>1) {
            $foundEntryIdString = ",".implode(",", $foundEntryIds).",";
	} elseif(count((array) $foundEntryIds) == 1) {
	    $foundEntryIdString = $foundEntryIds[0];
	} else {
            $foundEntryIdString = "";
        }
        unset($value);
        $value = $foundEntryIdString;
        $append = "replace";
    }

    $elementFormObject = $form_handler->get($element->getVar('id_form'));

    $lockIsOn = false;
    if (($value == "{ID}" AND $entry == "new") OR $value == "{SEQUENCE}") {
        $lockIsOn = true;
        // need to lock table since there are multiple operations required on it for this one write transaction
        $xoopsDB->query("LOCK TABLES ".$xoopsDB->prefix("formulize_".$elementFormObject->getVar('form_handle'))." WRITE");
        $fromField = $value == "{ID}" ? "entry_id" : $element->getVar('ele_handle');
        $maxValueSQL = "SELECT MAX(`$fromField`) FROM " . $xoopsDB->prefix("formulize_".$elementFormObject->getVar('form_handle'));
        if ($maxValueRes = $xoopsDB->query($maxValueSQL)) {
            $maxValueArray = $xoopsDB->fetchArray($maxValueRes);
            $value = $maxValueArray["MAX(`$fromfield`)"] + 1;
        } else {
            exit("Error: could not determine max value to use for $value.  SQL:<br>$maxValueSQL<br>");
        }
    } elseif ($value == "{ID}" AND $entry != "new") {
        $value = $entry;
    }

    $needToSetOwner = false;
    if ($entry == "new") {
        // making a new entry
        $owner = is_numeric($append) ? $append : $uid; // for new entries, a numeric "action" indicates an owner for the entry that is different from the current user, ie: this is a proxy entry
        // no handling as yet for an array of values, which would be required for replacing the selections in a checkbox series or selectbox series.
        // radio buttons would also need to be massaged?
        $sql="INSERT INTO ".$xoopsDB->prefix("formulize_".$elementFormObject->getVar('form_handle'))." (creation_datetime, mod_datetime, creation_uid, mod_uid, `".$element->getVar('ele_handle')."`) VALUES (NOW(), NOW(), \"$owner\", \"$uid\", '".formulize_db_escape($value)."')";
        $needToSetOwner = true;
    } else {
        // not new entry, so update the existing entry
        if ($append=="remove") {
            $prevValue = q("SELECT `".$element->getVar('ele_handle')."` FROM ".$xoopsDB->prefix("formulize_".$elementFormObject->getVar('form_handle'))." WHERE entry_id=".intval($entry));
            if (strstr($prevValue[0][$element->getVar('ele_handle')], "*=+*:")) {
                $valueToWrite = str_replace("*=+*:" . $value, "", $prevValue[0][$element->getVar('ele_handle')]);
            } else {
                $valueToWrite = str_replace($value, "", $prevValue[0][$element->getVar('ele_handle')]);
            }
        } elseif ($append=="append") {
            $prevValue = q("SELECT `".$element->getVar('ele_handle')."` FROM ".$xoopsDB->prefix("formulize_".$elementFormObject->getVar('form_handle'))." WHERE entry_id=".intval($entry));
            switch ($element->getVar('ele_type')) {
                case "checkbox":
                    $valueToWrite = $prevValue[0][$element->getVar('ele_handle')] . "*=+*:" . $value;
                    break;

                case "select":
                    if ($ele_value[1]) { // multiple selections possible
                            $valueToWrite = $prevValue[0][$element->getVar('ele_handle')] . "*=+*:" . $value;
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
                    $valueToWrite = $prevValue[0][$element->getVar('ele_handle')] . $value;
                    break;

                default:
                    exit("Error: unknown type of element used in a call to displayButton");
            }
        } else {
            // append == "replace" or all other settings for append
            $valueToWrite = $value;
        }
        $sql = "UPDATE ".$xoopsDB->prefix("formulize_".$elementFormObject->getVar('form_handle'))." SET `".$element->getVar('ele_handle')."` = '".formulize_db_escape($valueToWrite)."' WHERE entry_id=".intval($entry);
    }

    if ($sql) {

        formulize_updateRevisionData($elementFormObject, $entry, true); // last true forces update (same as queryF below)

        if (!$res = $xoopsDB->queryF($sql)) {
            exit("Error: unable to execute a \"displayButton\" or writeElementValue call, using the following SQL:<br>$sql<br>" . $xoopsDB->error);
        }
        $GLOBALS['formulize_writeElementValueWasRun'] = true;
    }
    if ($lockIsOn) { $xoopsDB->query("UNLOCK TABLES"); }

    if ($entry == "new") {
        $insertedId = $xoopsDB->getInsertId();
    }

    if ($needToSetOwner) {
        include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
        $data_handler = new formulizeDataHandler($element->getVar('id_form'));
        if (!$groupResult = $data_handler->setEntryOwnerGroups($owner, $insertedId)) {
            print "ERROR: failed to write the entry ownership information to the database.<br>";
        }
    }

    // handle notifications
    // get the form ID of the form based on the element id
    switch ($entry) {
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


// THIS FUNCTION READS ALL THE FILES IN A DIRECTORY AND DELETES OLD ONES
// use the filter param to include only files containing a certain string in their names
// this function deletes old files, older than the $timeWindow specified, in seconds
// Returns an array of the files it did find
function formulize_scandirAndClean($dir, $filter="", $timeWindow=21600) {
    // filter must be present
    if (!$filter) {
        return false;
    }

    $currentTime = time();
    $targetTime = $currentTime - $timeWindow;
    $foundFiles = array();

    foreach (scandir($dir) as $fileName) {
        if (strstr($fileName, $filter)) {
            if (filemtime($dir.$fileName) < $targetTime) {
                unlink($dir.$fileName);
            } else {
                $foundFiles[] = $fileName;
            }
        }
    }
    return $foundFiles;
}



// THIS FUNCTION TAKES AN ARRAY WHERE THE KEYS ARE ELEMENT IDS AND THE VALUES ARE VALUES, AND IT WRITES THEM ALL TO A SPECIFIED ENTRY OR A NEW ENTRY
// values should be the correct values that would be passed back by the prepDataForWrite step
// originally, only $values and $entry were required
// $proxyUser, if present, is meant to override the current $xoopsUser uid value
// $action is deprecated
// $forceUpdate will cause queryF to be used in the data handler, which will allow updates on a get request
// $writeOwnerInfo causes the entry_owner_groups table to be updated when a new entry is written
// NOTE: $values takes ID numbers as keys, since that's how the datahandler expects things
function formulize_writeEntry($values, $entry_id="new", $action="replace", $proxyUser=false, $forceUpdate=false, $writeOwnerInfo=true) {
    if ($entry_id < 1 and "new" != $entry_id) {
        // safety net in case NULL is passed as $entry_id
        $entry_id = "new";
    }

    // get the form id from the element id of the first value in the values array
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $elementObject = $element_handler->get(key($values));
    if (is_object($elementObject)) {
        $data_handler = new formulizeDataHandler($elementObject->getVar('id_form'));
        if ($result = $data_handler->writeEntry($entry_id, $values, $proxyUser, $forceUpdate)) {
            if ($entry_id == "new" AND $writeOwnerInfo) {
                global $xoopsUser;
                if(isset($GLOBALS['formulize_overrideProxyUser'])) {
                    $ownerForGroups = $GLOBALS['formulize_overrideProxyUser'];
                } elseif ($proxyUser) {
                    $ownerForGroups = $proxyUser;
                } elseif ($xoopsUser) {
                    $ownerForGroups = $xoopsUser->getVar('uid');
                } else {
                    $ownerForGroups = 0;
                }
                $data_handler->setEntryOwnerGroups($ownerForGroups, $result); // result will be the ID number of the entry that was just written.
                if(isset($GLOBALS['formulize_overrideProxyUser'])) {
                    unset($GLOBALS['formulize_overrideProxyUser']);
                }
            }
            return $result;
        } else {
            if($result !== null) {
            exit("Error: data could not be written to the database for entry $entry_id in form ". $elementObject->getVar('id_form').".");
            } else {
                error_log('Formulize Notice: Nothing written for entry "'.$entry_id.'" presumably because the passed in values are unchanged from the saved values.');
            }
        }
    } else {
        print "<pre>";
        debug_print_backtrace();
        print "</pre>";
        exit("Error: invalid element in the value array: ".key($values).".");
    }
}


// THIS FUNCTION SYNCHS THE VALUES OF ELEMENTS IN SUBFORMS WITH THE VALUES OF ELEMENTS IN MAINFORMS, BASED ON = OPERATORS IN ANY SUBFORM ELEMENT CONDITIONS THAT EXIST IN THE MAINFORM
// this is so that the value of elements that were set at creation time based on the main form, are kept in synch with the mainform values if the mainform values change
// frid is the relationship that we're supposed to use to link the mainform and subform
// can only run once per page load
// WE ONLY ENFORCE THIS ON { } DYNAMIC REFERENCES TO ELEMENTS. IF WE ENFORCED LITERAL VALUES THEN IN THE CASE OF MULTIPLE SUBFORM ELEMENTS PULLING IN DIFFERENT SUBSETS OF ENTRIES IN THE SUBFORM, THE LAST SUBFORM ELEMENT'S LITERAL VALUES WOULD BE WRITTEN ONTO ALL THE ENTRIES (SO THE OTHER SUBFORM ELEMENTS WOULD END UP WITH NO ENTRIES THAT MATCH THE FILTERS)
function synchExistingSubformEntries($frid) {
    static $hasRun = false;
    if(!$hasRun AND $frid) {
        $form_handler = xoops_getmodulehandler('forms','formulize');
        $element_handler = xoops_getmodulehandler('elements','formulize');
        foreach($GLOBALS['formulize_allSubmittedEntryIds'] as $fid=>$mainFormEntryIds) {
            // does the fid have any subform elements with filters?
            if($formObject = $form_handler->get($fid)) {
                $elementTypes = $formObject->getVar('elementTypes');
                if($subformElementIds = array_keys($elementTypes, 'subform')) {
                    // okay, then loop through all the mainform entries that were saved/updated
                    $subEntriesForThismain = array();
                    foreach($mainFormEntryIds as $entry_id) {
                        // get the linked subform entries, if we haven't already...
                        if(!isset($subEntriesForThisMain[$entry_id])) {
                            $subEntriesForThismain[$entry_id] = checkForLinks($frid, array($fid), $fid, array($fid=>array($entry_id)), true); // final true means only unified display relationships are used
                        }
                        $checkForLinksResults = $subEntriesForThismain[$entry_id];
                        // check to see which subform element conditions we will care about
                        foreach($subformElementIds as $ele_id) {
                            $subformElementObject = $element_handler->get($ele_id);
                            $ele_value = $subformElementObject->getVar('ele_value');
                            // do not synchronize when the subform element has specifically turned off this feature!
                            if(isset($ele_value['enforceFilterChanges']) AND $ele_value['enforceFilterChanges'] == 0) { continue; }
                            $subformConditions = $ele_value[7];
                            $subformId = $ele_value[0];
                            //print 'subform id is'.$subformId;
                            if(is_array($subformConditions)) {
                                // loop through all the subform entries in the form this subform element uses, that are linked the mainform entry based on the declared form relationship
                                foreach($checkForLinksResults['sub_entries'][$subformId] as $subformEntryId) {
                                    $filterValues = array();
                                    foreach ($subformConditions[1] as $i=>$thisOp) {
                                        // for every condition that we actually care about...
                                        if ($thisOp == "=" AND $subformConditions[3][$i] != "oom" AND substr($subformConditions[2][$i],0,1) == "{" AND substr($subformConditions[2][$i],-1)=="}") {
                                            // foreach condition that is an = and 'match all' condition, prep the values for writing, and then update the entry
                                            $conditionElementObject = $element_handler->get($subformConditions[0][$i]);
                                            // check to see if the element we're writing to is linked and not snapshot, and pointing to the same form that a dynamic reference belongs to, if so use the mainform entry id
                                            $dynamicRefElement = $element_handler->get(substr($subformConditions[2][$i], 1, -1));
                                            $conditionElementEleValue = $conditionElementObject->getVar('ele_value');
                                            $conditionElementLinkProperties = explode("#*=:*", (string) $conditionElementEleValue[2]);
                                            if($dynamicRefElement AND $conditionElementObject->isLinked AND !$conditionElementEleValue['snapshot'] AND $dynamicRefElement->getVar('id_form') == $conditionElementLinkProperties[0]) {
                                                $filterValues[$subformConditions[0][$i]] = $entry_id;
                                            } elseif($dynamicRefElement) {
                                                $filterValues[$subformConditions[0][$i]] = prepareLiteralTextForDB($conditionElementObject, $subformConditions[2][$i], $entry_id);
                                            } else {
                                                continue; // if there is a { } term that is not a reference to an element, then we don't want to write anything!
                                            }
                                        }
                                    }
                                    if(count((array) $filterValues)) {
                                        formulize_writeEntry($filterValues, $subformEntryId);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    $hasRun = true;
}


// THIS FUNCTION SYNCHS ENTRIES WRITTEN IN BLANK DEFAULTS IN A SUBFORM, WITH THE PARENT FORM.  GETS EXECUTED IN FORMDISPLAY.PHP AND FORMDISPLAYPAGES.PHP AFTER A FORM SUBMISSION
function synchSubformBlankDefaults() {
    // handle creating linked/common values when default blank entries have been filled in on a subform -- sept 8 2007
    static $ids_to_return = array();
    if (isset($GLOBALS['formulize_subformCreateEntry'])) {
        foreach ($GLOBALS['formulize_subformCreateEntry'] as $sfid=>$sfid_id_reqs) {

            // actually write the linked/common values
            foreach ($sfid_id_reqs as $sfid_key=>$id_req_to_write) {

                // there may be multiple source entries, if the blanks were in the sub sub for example.
                // so in that case, assume that we have one blank per source entry, and deduce the value to write based on the different sources
                // if there is not a corresponding source for the blank, then we will default to key 0, which will give us the first (only?) source entry that was written
                if(isset($_POST['formulize_subformValueSourceEntry_'.$sfid][$sfid_key])) {
                    $subformValuesSourceEntryKey = $sfid_key;
                } else {
                    $subformValuesSourceEntryKey = 0;
                }

                $sourceEntryId = $_POST['formulize_subformValueSourceEntry_'.$sfid][$subformValuesSourceEntryKey] ? $_POST['formulize_subformValueSourceEntry_'.$sfid][$subformValuesSourceEntryKey] : "new";
                // if we need to get the parent entry id, take the first written new entry id in the source form, if the source entry was 'new', otherwise use the source entry id posted from the form
                $savedMainFormEntryId = $sourceEntryId == "new" ? $GLOBALS['formulize_newEntryIds'][$_POST['formulize_subformValueSourceForm_'.$sfid]][0] : intval($sourceEntryId);
                global $xoopsDB;
                // first, figure out the value we need to write in the subform entry
                if ($_POST['formulize_subformSourceType_'.$sfid]) {
                    // true if the source is a common value
                    $elementPostHandle = "de_".$_POST['formulize_subformValueSourceForm_'.$sfid]."_".$sourceEntryId."_".$_POST['formulize_subformValueSource_'.$sfid];
                    // grab the value from the parent element -- assume that it is a textbox of some kind!
                    if (isset($_POST[$elementPostHandle])) {
                        $value_to_write = $_POST[$elementPostHandle] === "{ID}" ? $GLOBALS['formulize_newEntryIds'][$_POST['formulize_subformValueSourceForm_'.$sfid]][0] : $_POST[$elementPostHandle]; // get the value right out of the posted submission if it's present, unless it's {ID} and then we need to get the new value that was written for that form -- assume the first written new entry is the one we want!!
                    } else {
                        // get this entry and see what the source value is
                        $data_handler = new formulizeDataHandler($_POST['formulize_subformValueSourceForm_'.$sfid]);
                        $value_to_write = $data_handler->getElementValueInEntry($savedMainFormEntryId, $_POST['formulize_subformValueSource_'.$sfid]);
                    }
                } else {
                    $value_to_write = $savedMainFormEntryId;
                }

                writeElementValue($sfid, $_POST['formulize_subformElementToWrite_'.$sfid], $id_req_to_write, $value_to_write, "replace", "", true); // Last param is override that allows direct writing to linked selectboxes if we have prepped the value first!

                // need to also enforce any equals conditions that are on the subform element, if any, and assign those values to the entries that were just added
                $element_handler = xoops_getmodulehandler('elements','formulize');
                $subformElement = $element_handler->get($GLOBALS['formulize_newSubformBlankElementIds'][$sfid][$id_req_to_write]);
                $subformEle_Value = $subformElement->getVar('ele_value');
                $subformConditions = $subformEle_Value[7];
                if (is_array($subformConditions)) {
                    $filterValues = array();
                    foreach ($subformConditions[1] as $i=>$thisOp) {
                        if ($thisOp == "=" AND $subformConditions[3][$i] != "oom") {
                            $conditionElementObject = $element_handler->get($subformConditions[0][$i]);
                            $filterValues[$subformConditions[0][$i]] = prepareLiteralTextForDB($conditionElementObject, $subformConditions[2][$i], $savedMainFormEntryId);
                        }
                    }
                    if(count((array) $filterValues)>0) {
                        formulize_writeEntry($filterValues,$id_req_to_write);
                    }
                }

                $ids_to_return[$sfid][] = $id_req_to_write; // add the just synched up entry to the list of entries in the subform
            }
        }
    }

    unset($GLOBALS['formulize_subformCreateEntry']); // unset so this function only runs once
    return $ids_to_return;
}


// internal function that retrieves an element object if necessary
function _getElementObject($element) {
    if (is_object($element)) {
        // the silly historical name of the element class
        if (get_class($element) != "formulizeformulize" AND is_subclass_of($element, 'formulizeformulize') == false) {
            return false;
        } else {
            return $element;
        }
    } else {
        $element_handler =& xoops_getmodulehandler('elements', 'formulize');
        $element = $element_handler->get($element);
        if (!is_object($element)) {
            return false;
        }   else {
            return $element;
        }
    }
}

// still in use but could/should be refactored
function convertElementIdsToElementHandles($ids, $fid=false) {
    $elementsToFrameworks = false;
    $idsToFrameworks = false;
    $frid = 0;
    $needToConvert = false;
    // convert values to array for checking in standard way
    if(!is_array($ids)) {
        $ids = array($ids);
    }
    foreach ($ids as $id) {
        if (is_numeric($id)) {
            $needToConvert = true;
            break;
        }
    }
    if ($needToConvert) {
        if(!$fid) {
            $element_handler = xoops_getModuleHandler('elements','formulize');
            $elementObject = $element_handler->get($ids[0]);
            $fid = $elementObject ? $elementObject->getVar('id_form') : false;
        }
        if($fid) {
            return convertAllHandlesAndIds($ids, $frid, $elementsToFrameworks, $idsToFrameworks, $fid);
        } else {
            exit("Error: cannot get handle for element ".$ids[0].", in 'convertElementIdsToElementHandles' function. There is no such element.");
        }
    } else {
        return $ids;
    }
}


// assume handles are unique within a framework (which they are supposed to be!)
// reverse flag is used only when this is called from the opposite function, which is really just a wrapper for calling this and asking for things the other way around. element handles converted to framework handles
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

    if (!is_array($handles)) {
        $temp = $handles;
        unset($handles);
        $handles[0] = $temp;
    }
    $to_return = array();
    if (!isset($cachedElementHandles[$frid]) OR ($fid AND !isset($cachedElementHandlesFromElementIds[$fid]))) {
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
        if (in_array("creation_uid",$handles)) { $to_return[] = "creation_uid"; }
        if (in_array("uid",$handles)) { $to_return[] = "creation_uid"; }
        if (in_array("creation_datetime",$handles)) { $to_return[] = "creation_datetime"; }
        if (in_array("creation_date",$handles)) { $to_return[] = "creation_datetime"; }
        if (in_array("mod_uid",$handles)) { $to_return[] = "mod_uid"; }
        if (in_array("proxyid",$handles)) { $to_return[] = "mod_uid"; }
        if (in_array("mod_date",$handles)) { $to_return[] = "mod_datetime"; }
        if (in_array("mod_datetime",$handles)) { $to_return[] = "mod_datetime"; }
        if (in_array("creator_email",$handles)) { $to_return[] = "creator_email"; }

        $cachedElementIds[$frid] = $cachedElementHandles[$frid];
        if ($fid) {
            $cachedElementHandlesFromElementIds[$fid] = $cachedElementHandles[$frid];
        }

        // now get all the rest of the handles
        if ($fid) {
            $idHandleQuery = q("SELECT ele_handle, ele_id FROM ".$xoopsDB->prefix("formulize") . " WHERE id_form=".intval($fid));
        } else {
            $idHandleQuery = q("SELECT t2.ele_handle, t1.fe_handle, t2.ele_id FROM " . $xoopsDB->prefix("formulize_framework_elements") . " as t1, " . $xoopsDB->prefix("formulize") . " as t2 WHERE t1.fe_frame_id='$frid' AND t1.fe_element_id=t2.ele_id");
        }
        foreach ($idHandleQuery as $thisIdRow) {
            if ($fid) {
                $cachedElementHandlesFromElementIds[$fid][$thisIdRow['ele_id']] = $thisIdRow['ele_handle'];
            } else {
                $cachedElementHandles[$frid][$thisIdRow['fe_handle']] = $thisIdRow['ele_handle'];
                $cachedElementIds[$frid][$thisIdRow['ele_id']] = $thisIdRow['fe_handle'];
            }

            // populate the to return array, to save us going through all the handles again, since we're doing that right now
            // use array_search and assign the values to the same position in the return array, to preserve order
            if ($fid) {
                $foundKey = array_search($thisIdRow['ele_id'], $handles);
                if ($foundKey !== false) {
                    $to_return[$foundKey] = $thisIdRow['ele_handle'];
                }
            } elseif ($ids) {
                $foundKey = array_search($thisIdRow['ele_id'],$handles); // handles could be an array of ids
                if ($foundKey !== false) {
                    $to_return[$foundKey] = $thisIdRow['fe_handle'];
                }
            } else {
                if ($reverse) { // element handles to framework handles
                    $foundKey = array_search($thisIdRow['ele_handle'],$handles);
                    if ($foundKey !== false) {
                        $to_return[$foundKey] = $thisIdRow['fe_handle'];
                    }
                } else { // framework handles to element handles
                    $foundKey = array_search($thisIdRow['fe_handle'],$handles); // if this is a handle we're being asked for
                    if ($foundKey !== false) {
                        $to_return[$foundKey] = $thisIdRow['ele_handle'];
                    }
                }
            }
        }
        // to_return is built with the keys from $handles, but in an arbitrary order depending on the order the elements were returned in the DB query above, so we need to put them into the correct order here to correspond with $handles
        ksort($to_return);
    }

    // if to_return was not set already, ie: when doing a database query, then loop through handles to get the values we need from the cached values array.  Also if the to_return array is not as big as we need it to be (probably because of duplicate ids/handles in the original handles array)
    if (count((array) $to_return)==0 OR count((array) $to_return) != count((array) $handles)) {
        $to_return = array();
        foreach ($handles as $handle) {
            if(!is_numeric($handle)) { // if we have a mix of ids and handles in the source, then let's just use the original source for non numerics!!
                $to_return[] = $handle;
                continue;
            }
            if ($fid) {
                $to_return[] = $cachedElementHandlesFromElementIds[$fid][$handle];
            } elseif ($ids) {
                $to_return[] = $cachedElementIds[$frid][$handle];
            } else {
                if ($reverse) {
                    // handle is an element handle, return key corresponding to this value in the cached handles array
                    $to_return[] = array_search($handle,$cachedElementHandles[$frid]);
                } else {
                    // handle is a framework handle, so return corresponding element handle from array
                    $to_return[] = $cachedElementHandles[$frid][$handle];
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
// name is the name of the form in the DOM, which will be submitted on change. Leave blank to not have the filter submit anything
// multi is used to determine if the options should be returned as a checkbox series supporting multiple values
function buildFilter($id, $element_identifier, $defaultText="", $formDOMId="", $defaultValue=false, $subfilter=false, $linked_ele_id = 0, $linked_data_id=0, $limit=false, $multi=false) {

    static $multiCounter = -1;
    if($multi) {
        $multiCounter++;
    }

    $counter = -1;

    // Changes made to allow the linking of one filter to another. This is acheieved as follows:
    // 1. Create a formulize form for managing the Main Filter List (form M)
    // 2. Create a formulize form for managing the Sub Filter list (form S), which includes a linked element to the data in form M,
    //    so that relation between the Main Filter & Sub Filter data can be specified
    // 3. Create a formulize form for the data that the Main & SubFilter act upon (form D)
    ///
    // In such a case, the parameters have the following meaning:
    //  - $id is the element id of the field to be filtered in Form D
    //  - $element_identifier is also the element id of the field to be filtered in Form D
    //  - $subfilter specifies if this filter is a subfilter
    //  - $linked_ele_id specifies the ele_id of the Main Filter field as it appears in Form S
    //  - $linked_data_id specifies the ele_id of the Main Filter field as it appears in Form D

    /* limit params work as follows: (a limit is some property of a field in the source entry in a linked selectbox)
    $limit = false, or if used then it's an array with these params
    'ele_id' = the id of the element to pay attention to for the limit condition
    'term' = the term used to build the condition
    'operator' = the operator used to build the condition
    */

    // limits are very similar to subfilters in their effect, but subfilters are meant for situations where one filter influences another filter
    // subfilters are kind of like dynamic limits, where the limit condition is not specified until the parent filter is chosen.

    global $xoopsDB; // required by q
    $multiIdCounter = 1;
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $elementObject = (is_object($element_identifier) AND is_a($element_identifier, 'formulizeformulize')) ? $element_identifier : $element_handler->get($element_identifier);

    $ORSETOperator = $elementObject->canHaveMultipleValues ? '' : '='; // if the element supports multiple values, which are crammed into the same cell in the DB, then no equals operator... if the options are inclusive of one another, ie: active and inactive, then this isn't going to work cleanly!

    if($multi) { // create the hidden field that will get the value assigned for submission
        $defaultHiddenValue = "";
        if(isset($_POST[$id])) {
            $defaultHiddenValue = htmlspecialchars(strip_tags($_POST[$id]), ENT_QUOTES);
        } elseif(isset($_GET[$id])) {
            $defaultHiddenValue = htmlspecialchars(strip_tags($_GET[$id]), ENT_QUOTES);
        } elseif($defaultValue) {
            if(is_string($defaultValue) AND substr($defaultValue,0,5)=="ORSET" AND substr($defaultValue, -2) == "//") {
                $defaultHiddenValue = $defaultValue;
            } elseif(is_string($defaultValue)) {
                $defaultHiddenValue = "ORSET$multiCounter$ORSETOperator".$defaultValue."//";
            } elseif(is_array($defaultValue)) {
                foreach($defaultValue as $dv) {
                    $defaultHiddenValue .= "ORSET$multiCounter$ORSETOperator".$dv."//";
                }
            }
        }
        $filter = "<input type='hidden' name='$id' id='".$id."_hiddenMulti' value='".strip_tags(htmlspecialchars($defaultHiddenValue))."'>\n
        <div style='float: left; padding-right: 1em; padding-bottom: 1em;'>\n";
    } else { // start the actual dropdown selectbox
        $filter = "<SELECT name=\"$id\" id=\"$id\"";
        if ($formDOMId == "{listofentries}") {
            $filter .= " onchange='javascript:showLoading();'"; // list of entries has a special javascript thing
        } elseif ($formDOMId) {
            $filter .= " onchange='javascript:document.$formDOMId.submit();'";
        }
        $filter .= ">\n";
    }

    if ($subfilter AND !(isset($_POST[$linked_data_id])) AND !(isset($_GET[$linked_data_id]))) {
        // If its a subfilter and the main filter is unselected, then put in 'Please select from above options first
        $filter .= $multi ? " <label for='".$multiIdCounter."_".$id."'><input type='checkbox' name='".$multiIdCounter."_".$id."' id='".$multiIdCounter."_".$id."' value='none' onclick=\"jQuery('#".$id."_hiddenMulti').val('none');jQuery('.$id').each(function() { jQuery(this).removeAttr('checked') }); jQuery('#apply-button-".$id."').show(200);\">&nbsp;Please select a primary filter first</label><br/>\n" : "<option value=\"none\">Please select a primary filter first</option>\n";
    } else {
        // Either it is not a subfilter, or it is a subfilter with the linked values set
        if ($formDOMId == "{listofentries}") {
            $defaultText = !$defaultText ? _AM_FORMLINK_PICK : $defaultText;
            // must not pass back a value when we're putting a filter on the list of entries page
            $checked = ((!isset($_POST[$id]) OR $_POST[$id] == '') AND (!isset($_GET[$id]) OR $_GET[$id] == '')) ? "checked" : "";
            $filter .= $multi ? " <label for='".$multiIdCounter."_".$id."'><input type='checkbox' name='".$multiIdCounter."_".$id."' id='".$multiIdCounter."_".$id."' value='' $checked onclick=\"jQuery('#".$id."_hiddenMulti').val('');jQuery('.$id').each(function() { jQuery(this).removeAttr('checked') }); jQuery('#apply-button-".$id."').show(200);\">&nbsp;$defaultText</label><br/>\n" : "<option value=\"\">".$defaultText."</option>\n";
            // add {BLANK} option if we're doing this for a QSF filter in a list of entries page
            if($defaultText == _formulize_QSF_DefaultText) {
                $multiIdCounter++;
                $counter++;
                $checked = "";
                $selected = "";
                $checkboxOption = "ORSET$multiCounter={BLANK}//";
                if(isset($_POST[$id])) {
                    $checked = strstr($_POST[$id], $checkboxOption) ? "checked" : "";
                    $selected = $_POST[$id] === 'qsf_0_{BLANK}' ? "selected" : "";
                }
                $filter .= $multi ? " <label for='".$multiIdCounter."_".$id."'><input type='checkbox' name='".$multiIdCounter."_".$id."' id='".$multiIdCounter."_".$id."' class='$id' value='ORSET$multiCounter={BLANK}//' $checked onclick=\"if(jQuery(this).attr('checked')) { jQuery('#".$id."_hiddenMulti').val(jQuery('#".$id."_hiddenMulti').val()+'".$checkboxOption."'); } else { jQuery('#".$id."_hiddenMulti').val(jQuery('#".$id."_hiddenMulti').val().replace('".$checkboxOption."', '')); } jQuery('#1_".$id."').removeAttr('checked'); jQuery('#apply-button-".$id."').show(200);\">&nbsp;{BLANK}</label><br/>\n" : "<option value=\"qsf_".$counter."_{BLANK}\" $selected>{BLANK}</option>\n";
            }
        } else {
            // not a filter for core use on list of entries screen, so if multi, set default text to "Any", otherwise use the "Choose an option" default, unless the user has specified something
            $defaultText = ($multi AND !$defaultText) ? _formulize_QSF_DefaultText : $defaultText;
            $defaultText = !$defaultText ? _AM_FORMLINK_PICK : $defaultText;
            $checked = "";
            // no form submission...
            if(!isset($_POST[$id]) AND !isset($_GET[$id])) {
                // there's no particular default specified, or we're simply going to set to whatever the user chooses on subsequent loads
                if(!$defaultValue OR $defaultValue === true) {
                    $checked = "checked";
                }
            // there is a form submission... check for "none" being checked
            } elseif($_POST[$id] == "none" OR $_GET[$id] == "none") {
                $checked = "checked";
            }
            $filter .= $multi ? " <label for='".$multiIdCounter."_".$id."'><input type='checkbox' name='".$multiIdCounter."_".$id."' id='".$multiIdCounter."_".$id."' value='none' $checked onclick=\"jQuery('#".$id."_hiddenMulti').val('none');jQuery('.$id').each(function() { jQuery(this).removeAttr('checked') }); jQuery('#apply-button-".$id."').show(200);\">&nbsp;$defaultText</label><br/>\n" :"<option value=\"none\">".$defaultText."</option>\n";
        }

        $element_value = $elementObject->getVar('ele_value');
        $ele_uitext = $elementObject->getVar('ele_uitext');
        switch ($elementObject->getVar('ele_type')) {
            case "select":
                $options = $element_value[2];
                break;
            case "checkbox":
                $checkboxHandler = xoops_getmodulehandler("checkboxElement", "formulize");
                $element_value = $checkboxHandler->backwardsCompatibility($element_value);
                $options = $element_value[2];
                break;
            case "radio":
                $options = $element_value;
                break;
            default:
                $options = array();
        }

        $useValue = ""; // flag to indicate if the value should be used for the label that users see, otherwise we use the key

        // if the $options is from a linked selectbox, then figure that out and gather the possible values
        // only linked selectboxes have this string in their options field
        if (is_string($options) AND strstr($options, "#*=:*")) {
            $boxproperties = explode("#*=:*", $options);
            $source_form_id = $boxproperties[0];
            $source_element_handle = $boxproperties[1];

            // process the limits
            $limitConditionTable = "";
            $limitConditionWhere = "";
            // NOTE: LIMIT['ELE_ID'] MUST BE THE HANDLE OF THE ELEMENT, NOT THE ELEMENT ID...THIS OBSCURE FEATURE ONLY USED IN THE MAP SITE WAS NOT UPDATED FOR 3.1...AS LONG AS ELEMENT HANDLES ARE NOT CUSTOMIZED, THIS SHOULD NOT BE A PROBLEM
            if (is_array($limit)) {
                //$limitCondition = $limit['ele_id'] . "/**/" . $limit['term'];
                //$limitCondition .= isset($limit['operator']) ? "/**/" . $limit['operator'] : "";
                $limitOperator = isset($limit['operator']) ? $limit['operator'] : " LIKE ";
                $likebits = (strstr($limitOperator, "LIKE") AND substr($limit['term'], 0, 1) != "%" AND substr($limit['term'], -1) != "%") ? "%" : "";
                $limitConditionWhere = " WHERE t1`".$limit['ele_id']."` ".$limitOperator." '$likebits".formulize_db_escape($limit['term'])."$likebits' ";
            } elseif ($subfilter) { // for subfilters, we're jumping back to another form to get the values, hence the join
                $linkedSourceElementObject = $element_handler->get($linked_ele_id);
                $linkedSourceElementEleValue = $linkedSourceElementObject->getVar('ele_value');
                // first part will be the form id of the source form, second part will be the element handle in that form
                $linkedSourceElementEleValueParts = explode("#*=:*", $linkedSourceElementEleValue[2]);
                $linkedFormObject = $form_handler->get($linkedSourceElementEleValueParts[0]);
                $limitConditionTable = ", ".$xoopsDB->prefix("formulize_".$linkedFormObject->getVar('form_handle'))." as t2 ";
                $limitConditionWhere = " WHERE t1.`$linked_ele_id` LIKE CONCAT('%',t2.entry_id,'%') AND t2.`".$linkedSourceElementEleValueParts[1]."` LIKE '%".formulize_db_escape($_POST[$linked_data_id])."%'";
            }
            unset($options);
            $options = array();

            $conditionsfilter = "";
            $conditionsfilter_oom = "";
            $extra_clause = "";
            if($elementFormObject = $form_handler->get($elementObject->getVar('id_form'))) {
                global $xoopsUser;
                $fakeOwnerUid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
                // remove any dynamic filters pointing to form elements since we're rendering without entry context
                foreach($element_value[5][2] as $i=>$conditionTerm) {
                    if(substr($conditionTerm, 0, 1)=="{" AND substr($conditionTerm, -1)=="}") {
                        $termToCheck = substr($conditionTerm, 1, -1);
                        if(!isset($_GET[$termToCheck]) AND !isset($_POST[$termToCheck])) {
                            unset($element_value[5][0][$i]);
                            unset($element_value[5][1][$i]);
                            unset($element_value[5][2][$i]);
                            unset($element_value[5][3][$i]);
                        }
                    }
                }
                list($conditionsfilter, $conditionsfilter_oom, $parentFormFrom) = buildConditionsFilterSQL($element_value[5], $source_form_id, 'new', $fakeOwnerUid, $elementFormObject, "t1");
                $sourceEntryIdsForFilters = array(); // filters never have any preselected values from the database
                list($sourceEntrySafetyNetStart, $sourceEntrySafetyNetEnd) = prepareLinkedElementSafetyNets($sourceEntryIdsForFilters, $conditionsfilter, $conditionsfilter_oom);
                $ele_value['formlink_useonlyusersentries'] = isset($ele_value['formlink_useonlyusersentries']) ? $ele_value['formlink_useonlyusersentries'] : 0;
                $pgroupsfilter = prepareLinkedElementGroupFilter($source_form_id, $element_value[3], $element_value[4], $element_value[6], $ele_value['formlink_useonlyusersentries']);
                $extra_clause = prepareLinkedElementExtraClause($pgroupsfilter, $parentFormFrom, $sourceEntrySafetyNetStart);
                $limitConditionWhere = substr($limitConditionWhere, 7); // cut off the WHERE in this clause, because the extra_clause already intros it
            }

            $sourceFormObject = $form_handler->get($source_form_id);

            // if no extra elements are selected for display as a form element, then display the linked element
            $linked_columns = array($boxproperties[1]);
            if (is_array($element_value[EV_MULTIPLE_FORM_COLUMNS]) AND count((array) $element_value[EV_MULTIPLE_FORM_COLUMNS]) > 0 AND $element_value[EV_MULTIPLE_FORM_COLUMNS][0] != 'none') {
                if($sourceElementObject = $element_handler->get($source_element_handle)) {
                    $form_handler = xoops_getmodulehandler('forms', 'formulize');
                    $sourceFormObject = $form_handler->get($sourceElementObject->getVar('id_form'));
                    $linked_columns = convertElementIdsToElementHandles($element_value[EV_MULTIPLE_FORM_COLUMNS], $sourceFormObject->getVar('id_form'));
                    // remove empty entries, which can happen if the "use the linked field selected above" option is selected
                    $linked_columns = array_filter($linked_columns);
                }
            }

            if(count($linked_columns)==1) {
                $select_column = "distinct(t1.`".$linked_columns[0]."`)";
            } else {
                for($i=0;isset($linked_columns[$i]);$i++) {
                    $select_column .= ', t1.`'.$linked_columns[$i]."`";
                }
                $select_column = trim($select_column, ",");
            }

            if ($dataResult = $xoopsDB->query("SELECT $select_column, t1.entry_id FROM ".$xoopsDB->prefix("formulize_".$sourceFormObject->getVar('form_handle'))." as t1 $limitConditionTable $extra_clause $conditionsfilter $conditionsfilter_oom $limitConditionWhere ORDER BY t1.`$source_element_handle`")) {
                $useValue = 'entryid';
                if(count($linked_columns)>1) {
                    $linked_column_count = count((array) $linked_columns);
                    while ($dataRow = $xoopsDB->fetchRow($dataResult)) {
                        $linked_column_values = array();
                        foreach (range(0, ($linked_column_count-1)) as $linked_column_index) {
                            $linked_value = '';
                            if ($dataRow[$linked_column_index] !== "") {
                                $linked_value = prepvalues($dataRow[$linked_column_index], $linked_columns[$linked_column_index], $dataRow[$linked_column_count]);
                                $linked_value = $linked_value[0];
                            }
                            if($linked_value != '' OR is_numeric($linked_value)) {
                                $linked_column_values[] = $linked_value;
                            }
                        }
                        if(count((array) $linked_column_values)>0) {
                            // set option to entry id, with the linked columns as the label
                            $options[$dataRow[$linked_column_count]] = implode(" | ", $linked_column_values);
                        }
                    }
                } else {
                    while($dataRow = $xoopsDB->fetchRow($dataResult)) {
                        $linked_value = prepvalues($dataRow[0], $linked_columns[0], $dataRow[1]);
                        $options[$dataRow[1]] = $linked_value[0];
                    }
                }
            }
        }

        if(!$options) {
            // figure out the distinct values for this field, use those instead
            // strip out HTML. Or, if there is <span class='formulize-filter-value'>Text</span> present in the value, use that marked up value instead.
            // only show the user values that match their visibility settings
            $gperm_handler = xoops_gethandler('groupperm');
            global $xoopsUser;
            $groups = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
            $groupScopeGroups = array();
            $scopeUids = array();
            if(!$view_globalscope = $gperm_handler->checkRight("view_globalscope", $elementObject->getVar('id_form'), $groups, getFormulizeModId())) {
                $groupScopeGroups = getGroupScopeGroups($elementObject->getVar('id_form'), $groups);
                if(count($groupScopeGroups)==0) {
                    $scopeUids[] = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
                }
            }
            $dataHandler = new formulizeDataHandler($elementObject->getVar('id_form'));
            // if no groups passed and no scope uids passed, then it just gets everything
            $options = $dataHandler->findAllValuesForField($elementObject->getVar('ele_handle'),'ASC',$groupScopeGroups,$scopeUids,true);// last true forces per group filters to be respected
            $options = is_array($options) ? array_unique($options) : array();
            $parsedOptions = array();
            foreach($options as $option) {
                $option = undoAllHTMLChars($option, ENT_QUOTES);
                if($option === "") { continue; } // skip blanks. {BLANK} is an option already.
                if($pos = strpos($option,"formulize-filter-value")) {
                    $startPos = strpos($option, '>', $pos);
                    $endPos = strpos($option, '<', $startPos);
                    $candidateOption = 'NOQSFEQUALS'.htmlspecialchars(strip_tags(substr($option, $startPos+1, $endPos-$startPos-1)), ENT_QUOTES);
                } else {
                    $candidateOption = htmlspecialchars(strip_tags($option), ENT_QUOTES);
                    if($candidateOption != $option) {
                        $candidateOption = 'NOQSFEQUALS'.$candidateOption;
                    }
                }
                if(!isset($parsedOptions[$candidateOption])) {
                    $parsedOptions[$candidateOption] = 0;
                }
            }
            $options = $parsedOptions;
        }

        // code copied from elementrender.php to make fullnames work for Drupalcamp demo
        if (key($options) === "{FULLNAMES}" OR key($options) === "{USERNAMES}") {
            if (key($options) === "{FULLNAMES}") { $nametype = "name"; }
            if (key($options) === "{USERNAMES}") { $nametype = "uname"; }
            $pgroups = array();
            if ($element_value[3]) {
                $scopegroups = explode(",",$element_value[3]);
                global $xoopsUser;
                $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
                if (!in_array("all", $scopegroups)) {
                    // limit by users's groups
                    if ($element_value[4]) {
                        // loop so we can get rid of reg users group simply
                        foreach ($groups as $gid) {
                            if ($gid == XOOPS_GROUP_USERS) { continue; }
                            if (in_array($gid, $scopegroups)) {
                                $pgroups[] = $gid;
                            }
                        }
                        if (count((array) $pgroups) > 0) {
                            unset($groups);
                            $groups = $pgroups;
                        } else {
                            $groups = array();
                        }
                    } else {
                        // don't limit by user's groups
                        $groups = $scopegroups;
                    }
                } else {
                    // really use all (otherwise, we're just going will all user's groups, so existing value of $groups will be okay
                    if (!$element_value[4]) {
                        unset($groups);
                        global $xoopsDB;
                        $allgroupsq = q("SELECT groupid FROM " . $xoopsDB->prefix("groups") . " WHERE groupid != " . XOOPS_GROUP_USERS);
                        foreach ($allgroupsq as $thisgid) {
                            $groups[] = $thisgid['groupid'];
                        }
                    }
                }
                $options = array();
                $namelist = gatherNames($groups, $nametype);
                foreach ($namelist as $auid=>$aname) {
                    $options[$auid] = $aname;
                }
                $useValue = 'uid';
                natcasesort($options);
            }
        }

        $counter++;
        $anythingChecked = false;
        foreach ($options as $option=>$option_value) {

            if($multi AND $counter > 0 AND ($counter+1) % 7 == 0) {
                $filter .= "\n</div><div style='float: left; padding-right: 1em; padding-bottom: 1em;'>\n";
            }

            $multiIdCounter++;
            $selected = "";
            $labeloption = $useValue ? $option_value : $option;
            $labeloption = str_replace('NOQSFEQUALS','',$labeloption); // When the special flag is being used to override equals operator for searches, we must not show the flag! Super kludgey, but it's such a nested exception, hard to make generalized and only takes a couple lines to handle like this
            $labeloption = formulize_swapUIText($labeloption, $ele_uitext);
            if (is_array($defaultValue)) {
                if($multi) {
                    $checked = "";
                    if($_POST[$id] != "none" AND $_GET[$id] != "none") {
                        $checked = (strstr($_POST[$id], "ORSET$multiCounter$ORSETOperator".$option."//") OR strstr($_GET[$id], "ORSET$multiCounter$ORSETOperator".$option."//")) ? "checked" : "";
                        $anythingChecked = $checked ? true : $anythingChecked;
                        $checked = (!$anythingChecked AND in_array($option, $defaultValue)) ? "checked" : $checked;
                    }
                    $filter .= "<label for='".$multiIdCounter."_".$id."'><input type='checkbox' name='".$multiIdCounter."_".$id."' id='".$multiIdCounter."_".$id."' class='$id' value='".$option."' $checked onclick=\"if(jQuery(this).attr('checked')) { jQuery('#".$id."_hiddenMulti').val(jQuery('#".$id."_hiddenMulti').val()+'ORSET$multiCounter$ORSETOperator".$option."//'); } else { jQuery('#".$id."_hiddenMulti').val(jQuery('#".$id."_hiddenMulti').val().replace('ORSET$multiCounter$ORSETOperator".$option."//', '')); } jQuery('#1_".$id."').removeAttr('checked'); jQuery('#apply-button-".$id."').show(200);\">&nbsp;".$labeloption."</label><br/>\n";
                } else {
                    $selected = ($_POST[$id] == $option OR $_GET[$id] == $option) ? "selected" : "";
                    $selected = (!$selected AND in_array($option, $defaultValue)) ? "selected" : $selected;
                    $filter .= "<option value=\"" . $option . "\" $selected>" . $labeloption . "</option>\n";
                }
            } else {
                if (preg_match('/\{OTHER\|+[0-9]+\}/', $option)) {
                    $option = str_replace(":", "", _formulize_OPT_OTHER);
                }
                if($multi) {
                    $option = "ORSET$multiCounter$ORSETOperator".$option."//";
                }
                if ((isset($_POST[$id]) OR isset($_GET[$id])) AND $defaultValue !== false) {
                    if ($formDOMId == "{listofentries}") {
                        if($multi AND strstr("ORSET$multiCounter$ORSETOperator".$defaultValue."//", $option)) { // the whole overrides as counter idea... so old, multi filters are not going to work with that...
                            $selected = "checked";
                        } elseif ( (is_numeric($defaultValue) AND $defaultValue == $counter) OR (!is_numeric($defaultValue) AND ($defaultValue === $option OR $defaultValue === '='.$option)) ) {
                            $selected = "selected";
                        }
                    } else {
                        if($multi AND (strstr($_POST[$id], $option) OR strstr($_GET[$id],$option))) {
                            $selected = "checked";
                        } elseif($_POST[$id] == $option OR $_GET[$id] == $option) {
                           $selected = "selected";
                        }
                    }
                } elseif($defaultValue) {
                    if($multi AND ($option == $defaultValue OR $labeloption == $defaultValue)) {
                        $selected = "checked";
                    } elseif($option == $defaultValue) {
                        $selected = "selected";
                    }
                }
                if ($formDOMId == "{listofentries}" AND !$multi) {
                    // need to pass this stupid thing back because we can't compare the option and the contents of $_POST...a typing problem in PHP??!!
                    $option = "qsf_".$counter."_$option";
                }
                if($multi) {
                    $filter .= " <label for='".$multiIdCounter."_".$id."'><input type='checkbox' name='".$multiIdCounter."_".$id."' id='".$multiIdCounter."_".$id."' class='$id' value='".$option."' $selected onclick=\"if(jQuery(this).attr('checked')) { jQuery('#".$id."_hiddenMulti').val(jQuery('#".$id."_hiddenMulti').val()+'".$option."'); } else { jQuery('#".$id."_hiddenMulti').val(jQuery('#".$id."_hiddenMulti').val().replace('".$option."', '')); } jQuery('#1_".$id."').removeAttr('checked'); jQuery('#apply-button-".$id."').show(200);\">&nbsp;".$labeloption."</label><br/>\n";
                } else {
                    $filter .= "<option value=\"$option\" $selected>".$labeloption."</option>\n";
                }
            }
            $counter++;
        }
    }
    if(!$multi) {
        $filter .= "</SELECT>\n";
    } elseif($formDOMId == "{listofentries}") {
        $filter .= "<br><input id='apply-button-".$id."' type='button' class='formulize-small-button' style='display: none' value='"._formulize_SUBMITTEXT."' onclick='showLoading();'></div><div style='clear: both'></div>\n";
    } elseif($formDOMId) {
        $filter .= "<br><input id='apply-button-".$id."' type='button' class='formulize-small-button' style='display: none' value='"._formulize_SUBMITTEXT."' onclick='window.document.".$formDOMId.".submit();'></div><div style='clear: both'></div>\n";
    } else {
        $filter .= "</div><div style='clear: both'></div>\n";
    }

    return $filter;
}


// THIS FUNCTION TAKES A VALUE AND THE UITEXT FOR THE ELEMENT, AND RETURNS THE UITEXT IN PLACE OF THE "DATA" TEXT
// also ensures HTML will work
function formulize_swapUIText($value, $uitexts=array()) {
    $originalValue = $value;
    // if value is an array, it has a key called 'value', which needs to be swapped
    if (is_array($value) AND is_array($uitexts)) {
        $value['value'] = isset($uitexts[$value['value']]) ? $uitexts[$value['value']] : $value['value'];
    } elseif(is_array($uitexts)) {
        $value = isset($uitexts[$value]) ? $uitexts[$value] : $value;
    }
    if ($value === "") {
        $value = $originalValue; // don't return "";
    }
    return $value;
}
// THIS FUNCTION TAKES A VALUE AND THE UITEXT FOR THE ELEMENT, AND RETURNS THE CORRESPONDING DB VALUE IF THE PASSED VALUE MATCHES A UITEXT
function formulize_swapDBText($value, $uitexts=array()) {
    if(!is_array($uitexts)) { return $value; }
    $dbtexts = array_flip($uitexts);
    $originalValue = $value;
    // if value is an array, it has a key called 'value', which needs to be swapped
    if (is_array($value) AND is_array($dbtexts)) {
        $value['value'] = isset($dbtexts[$value['value']]) ? $dbtexts[$value['value']] : $value['value'];
    } elseif(is_array($dbtexts)) {
        $value = isset($dbtexts[$value]) ? $dbtexts[$value] : $value;
    }
    if ($value === "") {
        $value = $originalValue; // don't return "";
    }
    return $value;
}


// formats numbers according to options users have specified
// decimalOverride is used to provide decimal values if specified format has no decimals (added for use in calculations)
function formulize_numberFormat($value, $elementIdOrHandle, $decimalOverride=0) {
    if (!is_numeric($value)) {
        return $value;
    }
    $id = is_numeric($elementIdOrHandle) ? $elementIdOrHandle : formulize_getIdFromElementHandle($elementIdOrHandle);
    $elementMetaData = formulize_getElementMetaData($id, false);
    if ($elementMetaData['ele_type'] == "text") {
        $ele_value = unserialize($elementMetaData['ele_value']);
        // value, decimaloverride, decimals, decsep exists, decsep, sep exists, sep, prefix exists, prefix
        return _formulize_numberFormat($value, $decimalOverride, $ele_value[5], isset($ele_value[7]), $ele_value[7], isset($ele_value[8]), $ele_value[8], isset($ele_value[6]), $ele_value[6], isset($ele_value[10]), $ele_value[10]);
    } elseif ($elementMetaData['ele_type'] == "derived") {
        $ele_value = unserialize($elementMetaData['ele_value']);
        // value, decimaloverride, decimals, decsep exists, decsep, sep exists, sep, prefix exists, prefix
        return _formulize_numberFormat($value, $decimalOverride, $ele_value[1], isset($ele_value[3]), $ele_value[3], isset($ele_value[4]), $ele_value[4], isset($ele_value[2]), $ele_value[2], isset($ele_value[5]), $ele_value[5]);
    }   else {
        return $value;
    }
}


// internal function used by formulize_numberFormat to actually do the formatting
// different element types have different parts of ele_value where the number values are stored, so that's the reason for abstracting this out one level
function _formulize_numberFormat($value, $decimalOverride, $decimals="", $decSepExists=false, $decsep="", $sepExists=false, $sep="", $prefixExists=false, $prefix="", $suffixExists=false, $suffix="") {
    $config_handler =& xoops_gethandler('config');
    $formulizeConfig =& $config_handler->getConfigsByCat(0, getFormulizeModId());
    if ($decimalOverride) {
        // use the override if it's present
        $decimals = $decimalOverride;
    } elseif (!is_numeric($decimals)) {
        // if there is no decimal value passed in for this element
        // or else use the module pref, and if there isn't one, use 0
        $decimals = isset($formulizeConfig['number_decimals']) ? $formulizeConfig['number_decimals'] : 0;
    }
    if ($decsep == "" AND !$decSepExists) {
        $decsep = isset($formulizeConfig['number_decimalsep']) ? $formulizeConfig['number_decimalsep'] : ".";
    }
    if ($sep == "" AND !$sepExists) {
        $sep = isset($formulizeConfig['number_sep']) ? $formulizeConfig['number_sep'] : ",";
    }
    if ($prefix == "" AND !$prefixExists) {
        // if no prefix actually is specified for the element, then use module pref if one is set, otherwise use ""
        $prefix = isset($formulizeConfig['number_prefix']) ? $formulizeConfig['number_prefix'] : "";
    }
    if ($suffix == "" AND !$suffixExists) {
        // if no prefix actually is specified for the element, then use module pref if one is set, otherwise use ""
        $suffix = isset($formulizeConfig['number_suffix']) ? $formulizeConfig['number_suffix'] : "";
    }
    return trans($prefix) . number_format($value, $decimals, trans($decsep), trans($sep)) . trans($suffix);
}


// NOTE, THIS FUNCTION ASSIGNS IDS INSTEAD OF HANDLES TO RESULT ARRAY.  THIS NEEDS TO BE LOOKED INTO
function formulize_getCalcs($formframe, $mainform, $savedView, $handle="all", $type="all", $grouping="all") {
    list($fid, $frid) = getFormFramework($formframe, $mainform);

    static $cachedResults = array();
    if (!isset($cachedResults[$frid][$fid][$savedView])) {
        include_once XOOPS_ROOT_PATH . "/modules/formulize/include/entriesdisplay.php";

        foreach ($_POST as $k=>$v) {
            if (substr($k, 0, 7) == "search_") {
                unset($_POST[$k]);
            }
        }

        $settings = array();
        // load the saved view requested, and get everything ready for calling gatherDataSet
        // pubfilters are ignored, not relevant for just getting calculations
        // the way this is all parsed may not be entirely right! reading in view and parsing cols, searches, etc, should be standardized in functions, this repeats some code from entriesdisplay.php that should be refactored!
        list($_POST['currentview'], $_POST['oldcols'], $_POST['asearch'], $_POST['calc_cols'], $_POST['calc_calcs'], $_POST['calc_blanks'], $_POST['calc_grouping'], $_POST['sort'], $_POST['order'], $_POST['hlist'], $_POST['hcalc'], $_POST['lockcontrols'], $quicksearches, $settings['global_search'], $pubfilters, $entriesPerPage) = loadReport($savedView, $fid, $frid);
        // must check for this and set it here, inside this section, where we know for sure that $_POST['lockcontrols'] has been set based on the database value for the saved view, and not anything else sent from the user!!!  Otherwise the user might be injecting a greater scope for themselves than they should have!
        $currentViewCanExpand = $_POST['lockcontrols'] ? false : true;
        // explode quicksearches into the search_ values
        $allqsearches = explode("&*=%4#", $quicksearches);
        $colsforsearches = explode(",", $_POST['oldcols']);
        for ($i=0;$i<count((array) $allqsearches);$i++) {
            if ($allqsearches[$i] != "") {
                // need to remove the hiddencolumn indicator if it is present
                $_POST["search_" . str_replace("hiddencolumn_", "", $colsforsearches[$i])] = $allqsearches[$i];
                if (strstr($colsforsearches[$i], "hiddencolumn_")) {
                    unset($colsforsearches[$i]); // remove columns that were added to the column list just so we would know the name of the hidden searches
                }
            }
        }
        foreach ($_POST as $k=>$v) {
            if (substr($k, 0, 7) == "search_" AND $v != "") {
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
        list($scope, $throwAwayCurrentView) = buildScope($_POST['currentview'], $uid, $fid, $currentViewCanExpand);

        // by calling this, we will set the base query that needs to be used in order to generate the calculations
        // special flag is used to force return once base query is set
        $GLOBALS['formulize_returnAfterSettingBaseQuery'] = true;
        formulize_gatherDataSet($settings, $searches, "", "", $frid, $fid, $scope);
        unset($GLOBALS['formulize_returnAfterSettingBaseQuery']);

        $ccols = explode("/", $_POST['calc_cols']);
        $ccalcs = explode("/", $_POST['calc_calcs']);
        // need to add in proper handling of long calculation results, like grouping percent breakdowns that result in many, many rows.
        foreach ($ccalcs as $onecalc) {
            $thesecalcs = explode(",", $onecalc);
            if (!is_array($thesecalcs)) {
                $thesecalcs[0] = "";
            }
            $totalalcs = $totalcalcs + count((array) $thesecalcs);
        }
        $cblanks = explode("/", $_POST['calc_blanks']);
        $cgrouping = explode("/", $_POST['calc_grouping']);
        //formulize_benchmark("before performing calcs");
        $cachedResults[$frid][$fid][$savedView] = performCalcs($ccols, $ccalcs, $cblanks, $cgrouping, $frid, $fid);
    }

    $calcResults = $cachedResults[$frid][$fid][$savedView];

    // calcResults has five keys: /*
    /*
    $to_return[0] = $masterResults; // main array used to make standard results in Formulize
	$to_return[1] = $blankSettings; // the blank settings for each column and requested calculation
	$to_return[2] = $groupingSettings; // the grouping settings for each column and requested calculation
	$to_return[3] = $groupingValues; // the actual value by which the result was grouped, for each column, calculation and specific calc id (representing an individual result)
	$to_return[4] = $masterResultsRaw; // the raw value of the calculation, by column, requested calculation, and calculation id -- not including some avg and any per
    */

    // individual handle requested, so convert to array
    $origHandle = $handle;
    if ($handle!="all" AND !is_array($handle)) {
        $handles[0] = $handle;
    } elseif (is_array($handle)) {
        $handles = $handle;
    } else {
        // all the handles in the result array
        $handles = array_keys($calcResults[0]);
    }

    foreach ($handles as $handle) {
        if ($grouping != "all") {
            $groupingTypeMap = array();
            foreach ($calcResults[3][$handle] as $groupType=>$values) {
                if ($groupType == $type OR $type == "all") {
                    foreach ($values as $groupingId=>$theseValues) {
                        if (array_search($grouping, $theseValues) !== false) {
                            // this is a grouping selection for this type that we need to return
                            $groupingTypeMap[$groupType][$groupingId] = true;
                        }
                    }
                }
            }
        }
        $indexer = 0;
        foreach ($calcResults[0][$handle] as $calcType=>$results) {
            if ($type == $calcType OR $type == "all") {
                foreach ($results as $groupingId=>$thisResult) {
                    if (isset($groupingTypeMap[$calcType][$groupingId]) OR $grouping == "all") {
                        if(isset($calcResults[4][$handle][$calcType][$groupingId])) {
                            $resultArray[$handle][$calcType][$indexer]['result'] = $calcResults[4][$handle][$calcType][$groupingId];
                            $resultArray[$handle][$calcType][$indexer]['readable'] = $thisResult;
                        } else {
                            $resultArray[$handle][$calcType][$indexer]['result'] = $thisResult;
                        }
                        $resultArray[$handle][$calcType][$indexer]['grouping'] = $calcResults[3][$handle][$calcType][$groupingId];
                        $indexer++;
                    }
                }
            }
        }
    }

    return $resultArray; // multiple handles requested so return everything
}


// This function creates the UI and hidden elements for a set of filter options, such as those used to create the per-group-filter options in the permission section, or could be used for selectbox filters or multipage screen "skip logic" settings
// $filterSettings is the actual filter settings in an array, as retrieved (and unserialized) from the DB
// $filterName is the unique name to use for this set of elements -- CANNOT HAVE UNDERSCORES IN IT! (breaks delete interpretation)
// $formWithSourceElements is the ID of the form to use to get the elements from to show in the filter options
// $formName is the name of the HTML form that this filter UI is being embedded into - ID really, not the name
// $frid is the form relationship id to be used to gather a set of elements, not just the mainform's elements - optional
// $defaultTypeIfNoFilterTypeGiven is the value ("all" or "oom") that should be used for the filter type, if no filter type is specified...this happens when old installations are upgraded to the new version that is type-aware for these filters, no filter type will be specified for all the conditions.  Therefore, we have to assume what it should be, and that is potentially different for each place this function is called, since the logic reading these filters for each of those places will have assumed one or the other.
// $groups is the groups to filter the elements with (only elements visible to those groups).  If no groups, then all elements are returned.
// filterAllText is the text to use for the "all" option
// filterConText is the text to use for the "con" option (ie: the radio button that shows there is a filter in effect)
// $filterButtonText is the text to use for the "add" button for adding a new filter to the list of conditions
// This function sets up a series of old_$filterName_elements hidden elements to perpetuate the ones that have been set already, and also some new_$filterName_elements that are the new ones users select
// When other code is handling the saving of this filter information later, it will have to take both the old and the new and munge them together
/* ALTERED - 20100315 - freeform - jeff/julian - start - commented match all, and
    added match one or more */
function formulize_createFilterUI($filterSettings, $filterName, $formWithSourceElements, $formName, $frid=0, $defaultTypeIfNoFilterTypeGiven="all", $groups=false, $filterAllText=_formulize_GENERIC_FILTER_ALL, $filterConText=_formulize_GENERIC_FILTER_CON, $filterButtonText=_formulize_GENERIC_FILTER_ADDBUTTON) {
    if (!$filterName OR !$formWithSourceElements OR !$formName) {
        return false;
    }

    // set all the elements that we want to show the user
    $cols = "";
    if ($groups) {
        $cols = getAllColList($formWithSourceElements, $frid, $groups);
    } else {
        $cols = getAllColList($formWithSourceElements, $frid);
    }

    $options = array('creation_uid'=>_formulize_DE_CALC_CREATOR, 'creation_datetime'=>_formulize_DE_CALC_CREATEDATE, 'mod_uid'=>_formulize_DE_CALC_MODIFIER, 'mod_datetime'=>_formulize_DE_CALC_MODDATE);
    if (is_array($cols)) {
        // setup the options array for form elements
        foreach ($cols as $f=>$vs) {
            foreach ($vs as $row=>$values) {
                if ($values['ele_colhead'] != "") {
                    $options[$values['ele_handle']] = printSmart(trans($values['ele_colhead']), 40);
                } else {
                    $options[$values['ele_handle']] = printSmart(trans(strip_tags($values['ele_caption'])), 40);
                }
            }
        }
    }

    // process existing conditions...setup needed variables
    $oldElementsName = $filterName."_elements";
    $oldOpsName = $filterName."_ops";
    $oldTermsName = $filterName."_terms";
    $oldTypesName = $filterName."_types";

    // unpack existing conditions
    if (is_array($filterSettings)) {
        ${$oldElementsName} = $filterSettings[0];
        ${$oldOpsName} = $filterSettings[1];
        ${$oldTermsName} = $filterSettings[2];
        if (isset($filterSettings[3])) {
            ${$oldTypesName} = $filterSettings[3];
        } else {
            if (is_array($filterSettings[0])) {
                foreach ($filterSettings[0] as $i => $thisFilterSettingsZero) {
                    ${$oldTypesName}[$i] = $defaultTypeIfNoFilterTypeGiven;
                }
            }
        }
    }

    // setup needed variables for the all or oom ui
    // > match all of these
    $conditionlist = "";
    $newElementName = "new_".$filterName."_element";
    $newOpName = "new_".$filterName."_op";
    $newTermName = "new_".$filterName."_term";
    // > match one or more of these
    $conditionlistOOM = "";
    $newElementNameOOM = "new_".$filterName."_oom_element";
    $newOpNameOOM = "new_".$filterName."_oom_op";
    $newTermNameOOM = "new_".$filterName."_oom_term";

    // make hidden elements for all the old conditions we found
    if (is_array(${$oldElementsName})) {
        $i=0;
        foreach (${$oldElementsName} as $x=>$thisOldElementsName) {
            // need to add [$i] to the generation of the hidden values here, so the hidden condition keys equal the flag on the deletion X
            // $x will be the order based on the filter settings that were passed in, might not start at 0.  $i will always start at 0, so this way we'll catch/correct any malformed arrays as people edit/save them
            $thisHiddenElement = new xoopsFormHidden($oldElementsName."[$i]", strip_tags(htmlspecialchars(${$oldElementsName}[$x])));
            ${$oldOpsName}[$x] = formulize_conditionsCleanOps(${$oldOpsName}[$x]);
            $thisHiddenOp = new xoopsFormHidden($oldOpsName."[$i]", ${$oldOpsName}[$x]);
            $thisHiddenTerm = new xoopsFormHidden($oldTermsName."[$i]", strip_tags(htmlspecialchars(${$oldTermsName}[$x])));
            $thisHiddenType = new xoopsFormHidden($oldTypesName."[$i]", strip_tags(htmlspecialchars(${$oldTypesName}[$x])));
            if (${$oldTypesName}[$x] == "all") {
                $conditionlist .= $options[${$oldElementsName}[$x]] . " " . ${$oldOpsName}[$x] . " " . ${$oldTermsName}[$x] . "&nbsp;&nbsp;<a class='conditionsdelete' title='Delete' target='".$filterName."_".$i."' href=''>X</a>\n".$thisHiddenElement->render()."\n".$thisHiddenOp->render()."\n".$thisHiddenTerm->render()."\n".$thisHiddenType->render()."\n<br />\n";
            } else {
                $conditionlistOOM .= $options[${$oldElementsName}[$x]] . " " . ${$oldOpsName}[$x] . " " . ${$oldTermsName}[$x] . "&nbsp;&nbsp;<a class='conditionsdelete' title='Delete' target='".$filterName."_".$i."' href=''>X</a>\n".$thisHiddenElement->render()."\n".$thisHiddenOp->render()."\n".$thisHiddenTerm->render()."\n".$thisHiddenType->render()."\n<br />\n";
            }
            $i++;
        }
    }

    // setup the new element, operator, term boxes
    // > match all of these
    $conditionui = "<i>" . _formulize_GENERIC_FILTER_MATCH_ALL . "</i>";
    $conditionui .= formulize_createFilterUIMatch($newElementName,$formName,$filterName,$options,$newOpName,$newTermName,$conditionlist);
    // > match one or more of these
    $conditionui .= "<br /><i>" . _formulize_GENERIC_FILTER_MATCH_ONEORMORE . "</i>";
    $conditionui .= formulize_createFilterUIMatch($newElementNameOOM,$formName,$filterName,$options,$newOpNameOOM,$newTermNameOOM,$conditionlistOOM);
    $conditionui .= "<br />";
    // build add another condition button
    $addcon = new xoopsFormButton('', 'addcon', $filterButtonText, 'button');

    return $conditionui . $addcon->render();
}

// this function checks the passed in op and returns it only if it matches one of the allowed types of ops (necessary since we cannot sanitize out < and > easily using normal sanitizing functions due to them being angle brackets in HTML)
function formulize_conditionsCleanOps($op) {
 $ops = array();
 $ops['='] = "=";
 $ops['NOT'] = "NOT";
 $ops['>'] = ">";
 $ops['<'] = "<";
 $ops['>='] = ">=";
 $ops['<='] = "<=";
 $ops['LIKE'] = "LIKE";
 $ops['NOT LIKE'] = "NOT LIKE";
 if(isset($ops[$op])) {
    return $op;
 } else {
    return "";
 }
}


function formulize_createFilterUIMatch($newElementName,$formName,$filterName,$options,$newOpName,$newTermName,$conditionlist) {
    // setup the new element, operator, term boxes
    $new_elementOpTerm = new xoopsFormElementTray('', "&nbsp;&nbsp;");
    $element = new xoopsFormSelect('', $newElementName);
    $element->addOptionArray($options);
    $op = new xoopsFormSelect('', $newOpName);
    $ops['='] = "=";
    $ops['NOT'] = "NOT";
    $ops['>'] = ">";
    $ops['<'] = "<";
    $ops['>='] = ">=";
    $ops['<='] = "<=";
    $ops['LIKE'] = "LIKE";
    $ops['NOT LIKE'] = "NOT LIKE";
    $op->addOptionArray($ops);
    $term = new xoopsFormText('', $newTermName, 10, 255);
    $term->setExtra(" class=\"condition_term\" ");
    $new_elementOpTerm->addElement($element);
    $new_elementOpTerm->addElement($op);
    $new_elementOpTerm->addElement($term);

    return "<br />$conditionlist" . $new_elementOpTerm->render();
}


function getExistingFilter($filterSettings, $filterName, $formWithSourceElements, $formName, $defaultTypeIfNoFilterTypeGiven="all", $groups=false, $filterAllText=_formulize_GENERIC_FILTER_ALL, $filterConText=_formulize_GENERIC_FILTER_CON, $filterButtonText=_formulize_GENERIC_FILTER_ADDBUTTON) {
    if (!$filterName OR !$formWithSourceElements OR !$formName) {
        return false;
    }

    // set all the elements that we want to show the user
    $cols = "";
    if ($groups) {
        $cols = getAllColList($formWithSourceElements, "", $groups);
    } else {
        $cols = getAllColList($formWithSourceElements);
    }

    $options = array('creation_uid'=>_formulize_DE_CALC_CREATOR, 'creation_datetime'=>_formulize_DE_CALC_CREATEDATE, 'mod_uid'=>_formulize_DE_CALC_MODIFIER, 'mod_datetime'=>_formulize_DE_CALC_MODDATE);
    if (is_array($cols)) {
        // setup the options array for form elements
        foreach ($cols as $f=>$vs) {
            foreach ($vs as $row=>$values) {
                if ($values['ele_colhead'] != "") {
                    $options[$values['ele_handle']] = printSmart(trans($values['ele_colhead']), 40);
                } else {
                    $options[$values['ele_handle']] = printSmart(trans(strip_tags($values['ele_caption'])), 40);
                }
            }
        }
    }

    // process existing conditions...setup needed variables
    $oldElementsName = $filterName."_elements";
    $oldOpsName = $filterName."_ops";
    $oldTermsName = $filterName."_terms";
    $oldTypesName = $filterName."_types";

    // unpack existing conditions
    if (is_array($filterSettings)) {
        ${$oldElementsName} = $filterSettings[0];
        ${$oldOpsName} = $filterSettings[1];
        ${$oldTermsName} = $filterSettings[2];
        if (isset($filterSettings[3])) {
            ${$oldTypesName} = $filterSettings[3];
        } else {
            if (is_array($filterSettings[0])) {
                foreach ($filterSettings[0] as $i => $thisFilterSettingsZero) {
                    ${$oldTypesName}[$i] = $defaultTypeIfNoFilterTypeGiven;
                }
            }
        }
    }

    // setup needed variables for the all or oom
    // > match all of these
    $conditionlist = array();

    // > match one or more of these
    $conditionlistOOM = array();


    if (is_array(${$oldElementsName})) {
        $i=0;
        foreach (${$oldElementsName} as $x=>$thisOldElementsName) {
            // need to add [$i] to the generation of the hidden values here, so the hidden condition keys equal the flag on the deletion X
            // $x will be the order based on the filter settings that were passed in, might not start at 0.  $i will always start at 0, so this way we'll catch/correct any malformed arrays as people edit/save them

            if (${$oldTypesName}[$x] == "all") {
                array_push($conditionlist, $options[${$oldElementsName}[$x]] . " " . ${$oldOpsName}[$x] . " " . ${$oldTermsName}[$x]);
            } else {
                array_push($conditionlistOOM, $options[${$oldElementsName}[$x]] . " " . ${$oldOpsName}[$x] . " " . ${$oldTermsName}[$x]);
            }
            $i++;
        }
    }

    $existingConditions = array();
    $existingConditions['all'] = $conditionlist;
    $existingConditions['oom'] = $conditionlistOOM;

    return $existingConditions;
}


// this function gets the password for the encryption/decryption process
// want to has the db pass since we don't want any SQL logging processes to include the db pass as plaintext
function getAESPassword() {
    $icmsDB = SDATA_DB_PASS;
    $xoopsDB = XOOPS_DB_PASS;
    $dbPass = $icmsDB ? $icmsDB : $xoopsDB;
    return sha1($dbPass."I'm a cool, cool, cool dingbat");
}


function convertTypeToText($type, $ele_value) {
    switch ($type) {
        case "text":
            return "Textbox";

        case "textarea":
            return "Multi-line text box";

        case "areamodif":
            return "Text for display (left and right cells)";

        case "ib":
            return "Text for display (spanning both cells)";

        case "select":
            if ($ele_value[8] == 1) {
                return "Autocomplete box";
            }
            if ($ele_value[0] == 1) {
                return "Dropdown box";
            } else {
                return "List box";
            }

        case "checkbox":
            return "Check boxes";

        case "radio":
            return "Radio buttons";

        case "yn":
            return "Yes/No radio buttons";

        case "date":
            return "Date box";

        case "subform":
            return "Subform (another form with a relationship to this one)";

        case "grid":
            return "Table of existing elements";

        case "derived":
            return "Value derived from other elements";

        case "colorpick":
            return "Color picker";

        default:
            // must be a custom element type
            $customTypeHandler = xoops_getmodulehandler($type."Element", 'formulize');
            $customTypeObject = $customTypeHandler->create();
            return $customTypeObject->name;
    }
}

/*
 * Returns the HTML formatted results of the calculation process
 *
 * $results = formulize_runAdvancedCalculation($acid);
 * print $results;
 */
function formulize_runAdvancedCalculation( $acid ) {
    $advanced_calculation_handler = xoops_getmodulehandler('advancedCalculation', 'formulize');
    $advCalcObject = $advanced_calculation_handler->get($acid);
    return $advanced_calculation_handler->calculate($advCalcObject);
}


function undoAllHTMLChars($text,$quotes=ENT_QUOTES) {
    $text = html_entity_decode($text,$quotes);
    while (strstr($text,"&amp;")) {
        $text = html_entity_decode($text,$quotes);
    }
    return html_entity_decode($text,$quotes);
}


// this function takes some code and instead of using eval to deal with it, it writes it to a file, includes it, and then deletes the file
// this may be faster in some cases than eval, although it is not currently used
// $execute needs to be set to true, so that the code will be run.  You can pass in multiple snippets of code at different times, and then run them all at once.  The snippets will be remembered between calls.
// $globals is an array of the names of global variables that you want included in the code
// $filterNames is an array of global variable names that you want in this scope (the filters from Procedures is what this was intended to support)
function formulize_includeEval($code, $execute=false, $globals=array(), $filterNames=array()) {
    static $codeParts = array();
    $codeParts[] = $code;
    if ($execute) {
        foreach ($filterNames as $thisFilterName) {
            ${$thisFilterName} = $GLOBALS[$thisFilterName];
        }
        $fileName = XOOPS_ROOT_PATH."/cache/formulize_includeEval_".microtime(true);
        $fileHandle = fopen($fileName,"w");
        fwrite($fileHandle,"<?php \n global $".implode(", $",$globals).";\n");
        foreach ($codeParts as $thisCode) {
            fwrite($fileHandle,$thisCode);
        }
        fclose($fileHandle);
        include $fileName;
        unlink($fileName);
    }
}


function formulize_addProcedureChoicesToPost($choices) {
    if (!strstr($choices,"&amp;")) {
        $choices = strip_tags(htmlspecialchars($choices)); // just in case this wasn't done prior to passing in
    }
    $acid_temp_parameters = explode( "&amp;", $choices );
    $acid_parameters = array();
    if ($acid_temp_parameters) {
        foreach ($acid_temp_parameters as $parameter ) {
            $temp_pair = explode( "=", $parameter );
            if (strpos($temp_pair[0],"[")) {
                $bracketPos = strpos($temp_pair[0],"[");
                $bracketPosEnd = strpos($temp_pair[0],"]",$bracketPos);
                $_POST[substr($temp_pair[0],0,$bracketPos)][substr($temp_pair[0],$bracketPos+1,$bracketPosEnd-$bracketPos-1)] = $temp_pair[1];
            } else {
                $_POST[ $temp_pair[0] ] = $temp_pair[1];
            }
        }
    }
}


// used in the admin UI
// returns false if the element cannot be required, otherwise returns the current required setting of the element
function removeNotApplicableRequireds($type, $req=0) {
    switch ($type) {
        case "text":
        case "textarea":
        case "select":
        case "radio":
        case "checkbox":
        case "date":
        case "yn":
            return $req;
    }

    if (file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$type."Element.php")) {
        $customTypeHandler = xoops_getmodulehandler($type."Element", 'formulize');
        $customTypeElement = $customTypeHandler->create();
        if ($customTypeElement->adminCanMakeRequired) {
            return $req;
        }
    }
    return false;
}


// used to handle filter conditions being saved in the admin UI
// returns $processedValues with the conditions values properly structured
// $filter_key is the name of this conditions UI's values in POST
// $delete_key is the name of the flag that is sent in POST when the user clicks an X to delete a condition
function parseSubmittedConditions($filter_key, $delete_key) {

    if(!isset($_POST[$filter_key."_elements"]) AND !isset($_POST["new_".$filter_key."_term"]) AND !isset($_POST["new_".$filter_key."_oom_term"])) {
        return "";
    }
    if ($_POST["new_".$filter_key."_term"] != "") {
        $_POST[$filter_key."_elements"][] = $_POST["new_".$filter_key."_element"];
        $_POST[$filter_key."_ops"][] = $_POST["new_".$filter_key."_op"];
        $_POST[$filter_key."_terms"][] = $_POST["new_".$filter_key."_term"];
        $_POST[$filter_key."_types"][] = "all";
        $_POST['reload_option_page'] = true;
    }
    if ($_POST["new_".$filter_key."_oom_term"] != "") {
        $_POST[$filter_key."_elements"][] = $_POST["new_".$filter_key."_oom_element"];
        $_POST[$filter_key."_ops"][] = $_POST["new_".$filter_key."_oom_op"];
        $_POST[$filter_key."_terms"][] = $_POST["new_".$filter_key."_oom_term"];
        $_POST[$filter_key."_types"][] = "oom";
        $_POST['reload_option_page'] = true;
    }

    // then remove any that we need to
    $conditionsDeleteParts = explode("_", $_POST[$delete_key]);
    $deleteTarget = $conditionsDeleteParts[1];
    if ($_POST[$delete_key]) {
        // go through the passed filter settings starting from the one we need to remove, and shunt the rest down one space
        // need to do this in a loop, because unsetting and key-sorting will maintain the key associations of the remaining high values above the one that was deleted
        $originalCount = count((array) $_POST[$filter_key."_elements"]);
        for ($i = $deleteTarget; $i < $originalCount; $i++) { // 2 is the X that was clicked for this page
            if ($i>$deleteTarget) {
                $_POST[$filter_key."_elements"][$i-1] = $_POST[$filter_key."_elements"][$i];
                $_POST[$filter_key."_ops"][$i-1] = $_POST[$filter_key."_ops"][$i];
                $_POST[$filter_key."_terms"][$i-1] = $_POST[$filter_key."_terms"][$i];
                $_POST[$filter_key."_types"][$i-1] = $_POST[$filter_key."_types"][$i];
            }
            if ($i==$deleteTarget OR $i+1 == $originalCount) {
                // first time through or last time through, unset things
                unset($_POST[$filter_key."_elements"][$i]);
                unset($_POST[$filter_key."_ops"][$i]);
                unset($_POST[$filter_key."_terms"][$i]);
                unset($_POST[$filter_key."_types"][$i]);
            }
        }
        $_POST['reload_option_page'] = true;
    }

    if (count((array) $_POST[$filter_key."_elements"]) > 0){
        $returnValues = array();
        $returnValues[0] = $_POST[$filter_key."_elements"];
        $returnValues[1] = $_POST[$filter_key."_ops"];
        $returnValues[2] = $_POST[$filter_key."_terms"];
        $returnValues[3] = $_POST[$filter_key."_types"];
    } else {
        $returnValues = "";
    }

    return $returnValues;
}


// this function will build a SQL ready string based on a conditions UI data array that gets passed in, plus parameters like the table it's supposed to look in
// conditions is the set of data from the conditions UI (ie: what the user chose)
// targetFormId is the id of the form that the conditions are supposed to apply to - if this is an array, then we're preparing conditions for an extract query, and the rest of the params don't matter
// curlyBracketEntry is the id of the entry that we should be evaluating { } terms against.  "new" for a new entry that hasn't been saved.
// userComparisonId is the id that should be used to compare {USER} to when $entry is not "new".  In some cases we may want to pass in the owner of the entry rather than the current user.  When entry is "new" then the current user is always used.
// curlyBracketForm is either the id or the form object for the form that should be used as the source form for any { } terms, ie: if the term is = {handleX} then this param is the form that handleX would be part of
// targetAlias is the alias used in SQL to refer to the table that the conditions should apply to.  This is optional in general, but required if the query you're building for uses an alias!
function buildConditionsFilterSQL($conditions, $targetFormId, $curlyBracketEntry=null, $userComparisonId=null, $curlyBracketForm=null, $targetAlias="") {

    // if we've been sent an array for the target form, then we're preparing a query for the extraction layer
    // in this case all the dynamic references are dependent on the particular record being evaluated by the database during the query
    // so lots of things are done differently from when we're just looking things up relative to a single known entry
    $extractionQuery = false;
    if(is_array($targetFormId)) {
        // first key-value pair is the main form
        // rest of the key-value pairs are whatever other forms are in the query
        $extractionQuery = true;
    }

    global $nonLinkedCurlyBracketSelfReference;
    $nonLinkedCurlyBracketSelfReference = false;

    $conditionsfilter = "";
    $conditionsfilter_oom = "";
    $conditionsfilterArray = array();
    $conditionsfilter_oomArray = array();
    $curlyBracketFormFrom = "";
    $curlyBracketFormconditionsfilter = "";
    $curlyBracketFormconditionsfilter_oom = "";
    if (is_array($conditions)) {
        $targetFormIdForConversion = $extractionQuery ? key($targetFormId) : $targetFormId;
        $filterElementHandles = convertElementIdsToElementHandles($conditions[0], $targetFormIdForConversion);
        $filterElementIds = $conditions[0];
        $filterOps = $conditions[1];
        $filterTerms = $conditions[2];
        $filterTypes = $conditions[3];
        $start = true;
        $start_oom = true;
        $targetFormObject = "";
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $element_handler = xoops_getmodulehandler('elements', 'formulize');
        for ($filterId = 0;$filterId<count((array) $filterElementHandles);$filterId++) {

            $filterOps[$filterId] = $filterOps[$filterId] == 'NOT' ? '!=' : $filterOps[$filterId]; // convert NOT to != to avoid syntax error
            // if this filter term is a { } term that matches a $_GET value, then let's use that instead
            if (substr($filterTerms[$filterId],0,1) == "{" AND substr($filterTerms[$filterId],-1)=="}") {
                $bracketlessFilterTerm = substr($filterTerms[$filterId],1,-1);
                if (isset($_GET[$bracketlessFilterTerm])) {
                    $filterTerms[$filterId] = formulize_db_escape($_GET[$bracketlessFilterTerm]);
                }
            }

            // convert the $filterElementId to a real id, since it's possible it could find its way in here as a handle...a legacy issue sort of
            if (!is_numeric($filterElementIds[$filterId])) {
                $elementObject = $element_handler->get($filterElementIds[$filterId]);
                if (is_object($elementObject)) {
                    $filterElementIds[$filterId] = $elementObject->getVar('ele_id');
                }
            }

            // if the filter is a { } filter, then deduce the curlybraketform if necessary, or verify that it is a valid handle in the curlyBraketForm if one was declared explicitly,
            // otherwise ignore this term since it is not valid
            // this can happen if you have an element that is used in different places in an application, and it is filtered by one value in one place, and another value in another place, but not both at the same time
            // Allow terms if there is an asynchronous match waiting to be made - used for prepop subforms sometimes
            if (substr($filterTerms[$filterId],0,1) == "{" AND substr($filterTerms[$filterId],-1)=="}") {
                $bareFilterTerm = substr($filterTerms[$filterId],1,-1);
                if (!$extractionQuery) {
                    if(is_numeric($curlyBracketForm)) { // curlyBracketForm could be passed in as an id, in which case we need to unpack it and validate the handle once
                        $curlyBracketForm = $form_handler->get($curlyBracketForm);
                    }
                    if(!in_array($bareFilterTerm,$curlyBracketForm->getVar('elementHandles'))
                        AND !isset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$curlyBracketEntry][$bareFilterTerm])
                        AND $bareFilterTerm != 'USER'
                        AND $bareFilterTerm != 'USERID'
                        AND $bareFilterTerm != 'BLANK'
                        AND !strstr($filterTerms[$filterId], '{TODAY')) {
                        continue;
                    }
                } else {
                    // curlyBracketForm is dependent on the form the handle is referencing
                    if($bareFilterTerm != 'USER'
                       AND $bareFilterTerm != 'USERID'
                       AND $bareFilterTerm != 'BLANK'
                   AND !strstr($filterTerms[$filterId], '{TODAY')) {
                        if($filterTermElement = $element_handler->get($bareFilterTerm)) {
                            $curlyBracketForm = $form_handler->get($filterTermElement->getVar('id_form'));
                            // AND FOR NOW, WE DON'T SUPPORT { } DYNAMIC TERMS ON EXTRACTION QUERIES...WHAT WOULD THAT EVEN MEAN...we could possibly do this, it would require a lot of further complications in the SQL
                            continue;
                        } elseif(isset($_POST[$bareFilterTerm]) OR isset($_GET[$bareFilterTerm])) {
                            // term is a URL or POST reference, so grab that
                            $curlyBracketForm = $form_handler->get(key($targetFormId));
                            $filterTerms[$filterId] = isset($_POST[$bareFilterTerm]) ? $_POST[$bareFilterTerm] : $_GET[$bareFilterTerm];
                            $bareFilterTerm = $filterTerms[$filterId];
                        } else {
                            // don't know what the term is!
                            global $xoopsUser;
                            if($xoopsUser AND in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
                                print "Error: { } term could not be resolved. Were you expecting it to be in the URL?";
                            }
                            return;
                        }
                    } else {
                        // { } term is not a handle reference, or a URL reference, it's one of our reserved keywords, so use the main form (first key in the fid-alias array map that was passed in)
                        reset($targetFormId);
                        $curlyBracketForm = $form_handler->get(key($targetFormId));
                    }
                }
            }
            if(!$extractionQuery AND !$targetFormObject) { // setup the targetFormObject once, based on the passed in targetFormId
                $targetFormObject = $form_handler->get($targetFormId, true); // true forces inclusion of all element types
            } elseif($extractionQuery) {
                // target form and alias depends on which form the filter element handle belongs to
                if($elementObject = $element_handler->get($filterElementIds[$filterId])) {
                    $targetFormObject = $form_handler->get($elementObject->getVar('id_form'), true); // true forces inclusion of all element types
                    $targetAlias = $targetFormId[$filterElementFid];
                } elseif(isMetaDataField($filterElementIds[$filterId])) {
                    $targetFormObject = $form_handler->get(key($targetFormId), true); // true forces inclusion of all element types
                    $targetAlias = $targetFormId[key($targetFormId)];
                } else {
                    print 'ERROR: could not gather element information for "'.strip_tags(htmlspecialchars($filterElementIds[$filterId])).'" when creating a SQL filter.';
                    continue;
                }
            }
            $targetAlias .= ($targetAlias AND substr($targetAlias, -1) != '.') ? "." : ""; // add a period to the end of the alias, if there is an alias and if it doesn't have a dot, so it will work in the sql statement

            list($conditionsFilterComparisonValue, $thisCurlyBracketFormFrom) =  _buildConditionsFilterSQL($filterId, $filterOps, $filterTerms, $filterElementIds, $curlyBracketEntry, $userComparisonId, $curlyBracketForm, $element_handler, $form_handler);

            // regular conditions
            if ($filterTypes[$filterId] != "oom" AND !strstr($conditionsFilterComparisonValue, "curlybracketform")) {
                $needIntroBoolean = true;
                list($conditionsfilter, $thiscondition) = _appendToCondition($conditionsfilter, "AND", $needIntroBoolean, $targetAlias, $filterElementHandles[$filterId], $filterOps[$filterId], $conditionsFilterComparisonValue);
                if($thiscondition) {
                    $conditionsfilterArray[$targetFormObject->getVar('id_form')][] = $thiscondition;
                }
            // regular oom conditions
            } elseif(!strstr($conditionsFilterComparisonValue, "curlybracketform")) {
                $needIntroBoolean = true;
                list($conditionsfilter_oom, $thiscondition) = _appendToCondition($conditionsfilter_oom, "OR", $needIntroBoolean, $targetAlias, $filterElementHandles[$filterId], $filterOps[$filterId], $conditionsFilterComparisonValue);
                if($thiscondition) {
                    $conditionsfilter_oomArray[$targetFormObject->getVar('id_form')][] = $thiscondition;
                }
            // curlybracketform conditions
            } elseif($filterTypes[$filterId] != "oom") {
                $needIntroBoolean = false;
                list($curlyBracketFormconditionsfilter, $thiscondition) = _appendToCondition($curlyBracketFormconditionsfilter, "AND", $needIntroBoolean, $targetAlias, $filterElementHandles[$filterId], $filterOps[$filterId], $conditionsFilterComparisonValue);
                if($thiscondition) {
                    $conditionsfilterArray[$targetFormObject->getVar('id_form')][] = $thiscondition;
                }
            // curlybracketform oom conditions
            } else {
                $needIntroBoolean = false;
                list($curlyBracketFormconditionsfilter_oom, $thiscondition) = _appendToCondition($curlyBracketFormconditionsfilter_oom, "OR", $needIntroBoolean, $targetAlias, $filterElementHandles[$filterId], $filterOps[$filterId], $conditionsFilterComparisonValue);
                if($thiscondition) {
                    $conditionsfilter_oomArray[$targetFormObject->getVar('id_form')][] = $thiscondition;
                }
            }

            $curlyBracketFormFrom = $thisCurlyBracketFormFrom ? $thisCurlyBracketFormFrom : $curlyBracketFormFrom; // if something was returned, use it, otherwise, stick with what we've got -- NOTE THIS MEANS YOU CAN'T HAVE DIVERGENT CURLY BRACKET REFERENCES??!!

        }
    }

    $curlyBracketFormconditionsfilter_oom_WHERE = '';
    if($curlyBracketFormFrom) {
        if($curlyBracketFormconditionsfilter) {
            $curlyBracketFormFrom = " INNER JOIN $curlyBracketFormFrom ON ($curlyBracketFormconditionsfilter $curlyBracketFormconditionsfilter_oom) ";
        } elseif($curlyBracketFormconditionsfilter_oom) {
            // strip out parts of the oom filter that we cannot use in the different clauses -- AND MAYBE WE NEED TO DO THIS FOR THE NON-OOM FILTERS??
            // IF THIS IS A SELF-REFERENCE, USE ONLY THE ENTRY ID PART IN THE on
            // OTHERWISE, USE ONLY THE NON-ENTRY ID PART
            // $nonLinkedCurlyBracketSelfReference is a global set in the building of the conditions...and our big assumption is that all { } references point to the same form, so if we detect that it has some circularity, then that needs to be the case across the board...seems brittle!
            $entryFilterPos = strpos($curlyBracketFormconditionsfilter_oom, 'AND curlybracketform.`entry_id`=');
            $nextBracketPos = strpos($curlyBracketFormconditionsfilter_oom, ')', $entryFilterPos);
            if($nonLinkedCurlyBracketSelfReference) {
                $curlyBracketFormFrom = " LEFT JOIN $curlyBracketFormFrom ON (".substr($curlyBracketFormconditionsfilter_oom, ($entryFilterPos+4), $nextBracketPos-$entryFilterPos-3); // grab everything up to the ) after the "entry_id =" part
            } else {
                $curlyBracketFormFrom = " LEFT JOIN $curlyBracketFormFrom ON ($curlyBracketFormconditionsfilter_oom) ";
            }
            // strip out the part of the oom filter that we cannot use in the WHERE clause
            $searchStartPos = 1;
            $curlyBracketFormconditionsfilter_oom_WHERE = $curlyBracketFormconditionsfilter_oom;
            while($searchStartPos <= strlen($curlyBracketFormconditionsfilter_oom_WHERE) AND $entryFilterPos = strpos($curlyBracketFormconditionsfilter_oom_WHERE, 'AND curlybracketform.`entry_id`=', $searchStartPos)) {
                $nextBracketPos = strpos($curlyBracketFormconditionsfilter_oom_WHERE, ')', $entryFilterPos);
                $curlyBracketFormconditionsfilter_oom_WHERE = substr($curlyBracketFormconditionsfilter_oom_WHERE, 0, $entryFilterPos).substr($curlyBracketFormconditionsfilter_oom_WHERE, $nextBracketPos);
                $searchStartPos = $nextBracketPos;
            }
            if($conditionsfilter_oom) {
                $conditionsfilter_oom .= " OR $curlyBracketFormconditionsfilter_oom_WHERE ";
            } else {
                $conditionsfilter_oom = " AND ( $curlyBracketFormconditionsfilter_oom_WHERE ";
            }
        } else {
            $curlyBracketFormFrom = ", $curlyBracketFormFrom ";
        }
    }

    // close any brackets created by the need for an intro boolean
    $conditionsfilter .= $conditionsfilter ? ")" : "";
    $conditionsfilter_oom .= $conditionsfilter_oom ? ")" : "";

    if($extractionQuery) { // when preparing for extract layer, we need to pass back an array of the parts, so they can be handled individually as required
        $conditionsfilterArray[0] = $conditionsfilter; // stick the whole thing on as the first item, individual parts grouped by form go next
        $conditionsfilter = $conditionsfilterArray;
        $conditionsfilter_oomArray[0] = $conditionsfilter_oom;
        $conditionsfilter_oom = $conditionsfilter_oomArray;
    }

    return array($conditionsfilter, $conditionsfilter_oom, $curlyBracketFormFrom);
}

// append a given value onto a given condition
function _appendToCondition($condition, $andor, $needIntroBoolean, $targetAlias, $filterElementHandle, $filterOp, $conditionsFilterComparisonValue) {

    if(!$conditionsFilterComparisonValue
       AND $conditionsFilterComparisonValue !== 0
       AND $conditionsFilterComparisonValue !== "0") {
        return array($condition, $conditionsFilterComparisonValue);
    }

    if(!$condition AND $needIntroBoolean) {
        $condition = " AND (";
    } elseif(!$condition AND !$needIntroBoolean) {
        $condition = "";
    } else {
        $condition .= " $andor ";
    }
    $dbSource = isset($GLOBALS['formulize_DBSourceJoin'][$filterElementHandle]) ? "(".$GLOBALS['formulize_DBSourceJoin'][$filterElementHandle].")" : "$targetAlias`".$filterElementHandle."`";
    if(strstr($conditionsFilterComparisonValue,'-->>ADDPLAINLITERAL<<--')) {
        $conditionsFilterComparisonValue = str_replace("-->>ADDPLAINLITERAL<<--", " OR $dbSource $filterOp ", $conditionsFilterComparisonValue);
    }
    $thiscondition = "($dbSource ".$filterOp." ".$conditionsFilterComparisonValue.")";
    $condition .= $thiscondition;
    return array($condition, $thiscondition);
}


// this function takes the info from the above function, and actually builds the parts of the SQL statement by analyzing the current situation
// $filterOps may be modified by this function
// $filterTerms may be modified by this function
function _buildConditionsFilterSQL($filterId, &$filterOps, &$filterTerms, $filterElementIds, $curlyBracketEntry, $userComparisonId, $curlyBracketForm, $element_handler, $form_handler) {

    global $xoopsUser, $xoopsDB, $nonLinkedCurlyBracketSelfReference;
    $curlyBracketEntryQuoted = $curlyBracketEntry == 'new' ? "'new'" : $curlyBracketEntry; // can't put text into the query without quotes!
    $conditionsFilterComparisonValue = "";
    $curlyBracketFormFrom = "";
    $filterTerms[$filterId] = trim($filterTerms[$filterId]);
    $bareFilterTerm = substr($filterTerms[$filterId],1,-1);
    $filterElementObject = $element_handler->get($filterElementIds[$filterId]);
    if ($filterOps[$filterId] == "NOT") { $filterOps[$filterId] = "!="; }
    $likebits = "";
    $origlikebits = "";
    if (strstr(strtoupper($filterOps[$filterId]), "LIKE")) {
        if(!strstr(trim($filterTerms[$filterId]), '%')) {
            $likebits = "%";
            $origlikebits = "%";
        }
        $quotes = "'";
    } else {
        $quotes = is_numeric($filterTerms[$filterId]) ? "" : "'";
        $filterOps[$filterId] = $filterOps[$filterId] == "=" ? "<=>" : $filterOps[$filterId];
    }
    if(!isset($filterElementIds[$filterId])) {
        print "Critical Error: You have a condition set that is relying on an deleted or renamed element: ".$filterElementIds[$filterId]." OR ".$filterId."<br>";
        print "The terms of the condition are: ";
        print_r($filterTerms);
        print "The elements on the target form are:  ";
        print_r($filterElementIds);
        exit();
    } else {
        // check for whether the source element is a linked element, and if so, figure out the entry id of the record in the source of that linked selectbox which matches the filter term instead
        // ie: left side is linked to something else
        if(!isMetaDataField($filterElementIds[$filterId])) {
            $targetElementObject = $element_handler->get($filterElementIds[$filterId]);
            $targetElementEleValue = $targetElementObject->getVar('ele_value'); // get the properties of the source element
        }
        if (!isMetaDataField($filterElementIds[$filterId]) AND $targetElementObject->isLinked AND !$targetElementEleValue['snapshot']) {
            $targetElementEleValueProperties = explode("#*=:*", $targetElementEleValue[2]); // split them up to get the properties of the linked selectbox that the source element is pointing at
            $targetSourceFid = $targetElementEleValueProperties[0]; // get the Fid that the source element is point at (the source of the source)
            $targetSourceFormObject = $form_handler->get($targetSourceFid); // get the form object based on that fid (we'll need the form handle later)
            $targetSourceHandle = $targetElementEleValueProperties[1]; // get the element handle in the source source form
            // now build a comparison value that contains a subquery on the source source form, instead of a literal match to the source form
            $overrideReturnedOp = '';
            $subQueryOp = $filterOps[$filterId];
            if($filterOps[$filterId] == '!=' ) {
                $subQueryOp = '<=>';
                $overrideReturnedOp = '!=';
            }
            // if the filter term is dynamic, figure out the db and literal values for that element in the current entry/context
            if (substr($filterTerms[$filterId],0,1) == "{" AND substr($filterTerms[$filterId],-1)=="}") {
                $quotes = '';
                if (isset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$curlyBracketEntry][$bareFilterTerm])) {
                    $filterTermToUse = "'".formulize_db_escape($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$curlyBracketEntry][$bareFilterTerm])."'";
                    $dbValueOfTerm = $filterTermToUse;
                } else {
                    $filterTermToUse = " curlybracketform.`".formulize_db_escape($bareFilterTerm)."` ";
                    $curlyBracketFormFrom = $xoopsDB->prefix("formulize_".$curlyBracketForm->getVar('form_handle'))." AS curlybracketform "; // set as a single value, we're assuming all { } terms refer to the same form
                    // get the actual DB value of the term in the curly bracket entry
                    $dbValueOfTerm = prepareLiteralTextForDB($filterElementObject, $filterTerms[$filterId], $curlyBracketEntry, $userComparisonId);
                }
                // establish the literal (human readable) value
                if (isset($GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$curlyBracketEntry][$bareFilterTerm])) {
                    $literalTermToUse = "'".formulize_db_escape($GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$curlyBracketEntry][$bareFilterTerm])."'";
                    $literalQuotes = "";
                } elseif($curlyBracketEntry != 'new') {
                    $preppedFormatValue = prepvalues($dbValueOfTerm, $bareFilterTerm, $curlyBracketEntry); // will be an array
                    if(is_array($preppedFormatValue) AND count((array) $preppedFormatValue)==1) {
                        $preppedFormatValue = $preppedFormatValue[0]; // take the single value if there's only one, same as display function does
                    }
                    $literalTermToUse = $preppedFormatValue;
                    $literalQuotes = (is_numeric($literalTermToUse) AND !$likebits) ? "" : "'";
                } else {
                    // for new entries maybe we should get the defaults?
                    $literalTermToUse = '';
                }
                // if there is a difference, setup an OR expression so we can catch both variations
                if($literalTermToUse != $dbValueOfTerm) {
                    $literalTermInSQL = "`$targetSourceHandle` ".$subQueryOp.$literalQuotes.$likebits.$literalTermToUse.$likebits.$literalQuotes;
                    $specialCharsTerm = htmlspecialchars($literalTermToUse, ENT_QUOTES);
                    if($specialCharsTerm != $literalTermToUse) {
                        $literalTermInSQL .= " OR ".str_replace($literalTermToUse, $specialCharsTerm, $literalTermInSQL);
                    }
                    $subQueryWhereClause = "(ss.`$targetSourceHandle` ".$subQueryOp.$quotes.$likebits.$filterTermToUse.$likebits.$quotes." OR ss.".$literalTermInSQL." )";
                } else {
                    $subQueryWhereClause = "ss.`$targetSourceHandle` ".$subQueryOp.$quotes.$likebits.$filterTermToUse.$likebits.$quotes;
                }
                // figure out if the curlybracketform field is linked and pointing to the same source as the target element is pointing to
                // because if it is, then we don't need to do a subquery later, we just compare directly to the $filterTermToUse
                $curlyBracketElementObject = $element_handler->get($bareFilterTerm);
                if ($curlyBracketElementObject->isLinked) {
                    $curlyBracketTargetElementEleValue = $curlyBracketElementObject->getVar('ele_value');
                    $curlyBracketTargetElementEleValueProperties = explode("#*=:*", $curlyBracketTargetElementEleValue[2]);
                    $curlyBracketTargetSourceFid = $curlyBracketTargetElementEleValueProperties[0];
                    if ($curlyBracketTargetSourceFid == $targetSourceFid) {
                        $conditionsFilterComparisonValue = " CONCAT('$likebits',$filterTermToUse,'$likebits') "; // filterTermToUse will already have , , around it so we don't need them in the two concat'd parts before and after
                    } elseif($targetSourceFid == $curlyBracketForm->getVar('id_form')) { // not quite the same source...this is when the curlybracket form is the source of the linked target element. Don't ask.
                        // find entries where the filter term contains the entry id of any entry that contains the same linked reference to the common underlying source. Ack.
                        $curlyBracketTargetSourceFormObject = $form_handler->get($curlyBracketTargetSourceFid);
                        $conditionsFilterComparisonValue = " ( SELECT ss.entry_id FROM ".$xoopsDB->prefix('formulize_'.$curlyBracketForm->getVar('form_handle'))." AS ss
                            WHERE ss.`".formulize_db_escape($bareFilterTerm)."` = (
                                SELECT sss.`".formulize_db_escape($bareFilterTerm)."` FROM ".$xoopsDB->prefix('formulize_'.$curlyBracketForm->getVar('form_handle'))." AS sss WHERE sss.`entry_id` = $curlyBracketEntry
                                ) ) ";
                        $filterOps[$filterId] = " IN ";
                    }
                } elseif($targetSourceFid == $curlyBracketForm->getVar('id_form')) { // self reference to the same form, but with a non-linked element...need to do some funky stuff when parsing this into the ON clause of the join!
                    $nonLinkedCurlyBracketSelfReference = true;
                }
                // curlybracket term found, but when it's not linked to the same source as the target, we have to work the likebits in as part of a concat, since our term is not a literal string anymore
                if ($likebits) {
                    $filterTermToUse = " CONCAT('$likebits',$filterTermToUse,'$likebits') ";
                }
                // then neuter these, so they don't screw up the building of the query...note the use of origlikebits so that the higher level part of the query retains that logic if the user asked for it
                $quotes = "";
                $likebits = "";
            } else {
								// term is not a dynamic reference to an element...
								$filterTermToUse = formulize_db_escape($filterTerms[$filterId]);
								$subQueryWhereClause = "ss.`$targetSourceHandle` ".$subQueryOp.$quotes.$likebits.$filterTermToUse.$likebits.$quotes;
								// the  target is a linked element (already know that from above), and so it has a foreign key in the database, and if the filter term is numeric and the operator is equals then no subquery is necessary, do a direct comparison instead
								// check first if the subquery would return values. If so, then we stick with that. Otherwise, toss the subquery and go with a straight comparison to the filter term on the assumption it is a foreign key.
								if(is_numeric($filterTerms[$filterId]) AND $filterOps[$filterId] === "<=>") {
									$targetSourceDataHandler = new formulizeDataHandler($targetSourceFid);
									$foundEntries = $targetSourceDataHandler->findAllEntriesWithValue($targetSourceHandle, $filterTermToUse, operator: '<=>');
									if($foundEntries === false OR count($foundEntries) == 0) {
										$conditionsFilterComparisonValue = $filterTerms[$filterId];
										unset($subQueryWhereClause);
									}
								}
            }
            // if we didn't jump the gun and set the comparison value already above...
            if (!$conditionsFilterComparisonValue) {
                if ($targetElementEleValue[1]) { // if the target allows multiple selections...
                    $conditionsFilterComparisonValue = " CONCAT('$origlikebits,',(SELECT ss.entry_id FROM ".$xoopsDB->prefix("formulize_".$targetSourceFormObject->getVar('form_handle'))." AS ss WHERE ".$subQueryWhereClause."),',$origlikebits') ";
                // for existing entries, or new entries with no dynamic reference, or new entries with an asynch value set
                } elseif($curlyBracketEntry != 'new' OR (substr($filterTerms[$filterId],0,1) != "{" AND substr($filterTerms[$filterId],-1)!="}") OR isset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$curlyBracketEntry][$bareFilterTerm])) {
                    // do a subquery against the source of the left side element...
                    $conditionsFilterComparisonValue = " (SELECT ss.entry_id FROM " . $xoopsDB->prefix("formulize_" . $targetSourceFormObject->getVar('form_handle')) . " AS ss WHERE ".$subQueryWhereClause. ") ";
                                // need to change the filterOp being used, so when this is inserted into the main query, we have a different op introducing the subquery
                                if($filterOps[$filterId] == "LIKE" OR $filterOps[$filterId] == "NOT LIKE") {
                                  $overrideReturnedOp = "IN";
                                }
                    $filterOps[$filterId] = $overrideReturnedOp ? $overrideReturnedOp : '<=>';
                // for new entries with a dynamic reference and no asynch value set...
                } else { // can't do a subquery into a curly bracket form for a 'new' value...return nothing
                    return array("", "");
                }
            }
            if (substr($filterTerms[$filterId],0,1) == "{" AND substr($filterTerms[$filterId],-1)=="}" AND !isset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$curlyBracketEntry][$bareFilterTerm])) {
                $conditionsFilterComparisonValue .= "  AND curlybracketform.`entry_id`=$curlyBracketEntryQuoted ";
            }
        } else {
            foreach ($filterTerms as $key => $value) {
                $filterTerms[$key] = parseUserAndToday($value, $filterElementIds[$filterId]); // pass element so we can check if it is a userlist and compare {USER} based on id instead of name
                $filterTerms[$key] = str_replace('{ID}',$curlyBracketEntry,$filterTerms[$key]);
            }
        }
    }

    if ($filterTerms[$filterId]=="{BLANK}") {
      $conditionsFilterComparisonValue = 'NULL';
      $filterTerms[$filterId]="";
      if($filterOps[$filterId] == '!=' OR $filterOps[$filterId] == 'NOT LIKE') {
        $filterOps[$filterId] = 'IS NOT';
      } else {
        $filterOps[$filterId] = 'IS';
      }
    }

    $plainLiteralValue = "";
	$literalToDBValue = "";
    if ($filterOps[$filterId] == "<=>") {

        // if we're handling a dynamic reference to an element, and the thing we're comparing the dynamic reference to is not a linked element, then we're going to cover our bases and do an OR of both the DB and literal value
        if (substr($filterTerms[$filterId],0,1) == "{" AND substr($filterTerms[$filterId],-1)=="}" AND (isMetaDataField($filterElementIds[$filterId]) OR !$targetElementObject->isLinked)) {
			if(isset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$curlyBracketEntry][$bareFilterTerm])) {
				// get the literal value based on the passed in database ready format
                $literalToDBValue = $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$curlyBracketEntry][$bareFilterTerm];
                // use the already declared API format value (determined when conditional elements are generated for example)
                $plainLiteralValue = $GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$curlyBracketEntry][$bareFilterTerm];
                // if the $bareFilterTerm is a linked element that points to the element we're querying against, then switch to the value of that element as found in the entry with entry id matching the asynch literal DB value. Bewildering but works.
                $curlyBracketElementObject = $element_handler->get($bareFilterTerm);
                if ($curlyBracketElementObject->isLinked) {
                    $curlyBracketTargetElementEleValue = $curlyBracketElementObject->getVar('ele_value');
                    $curlyBracketTargetElementEleValueProperties = explode("#*=:*", $curlyBracketTargetElementEleValue[2]);
                    $curlyBracketTargetElementLinkedSourceElementObject = $element_handler->get($curlyBracketTargetElementEleValueProperties[1]);
                    if($curlyBracketTargetElementLinkedSourceElementObject AND $curlyBracketTargetElementLinkedSourceElementObject->getVar('ele_id') == $filterElementIds[$filterId] AND $curlyBracketTargetElementEleValue['snapshot'] != 1) {
                        $dataHandler = new formulizeDataHandler($curlyBracketTargetElementEleValueProperties[0]);
                        $literalToDBValue = $dataHandler->getElementValueInEntry($literalToDBValue, $filterElementIds[$filterId]);
                    }
                }
			} elseif(!isset($GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$curlyBracketEntry][$bareFilterTerm])) {
                // convert any literal terms (including {} references to linked selectboxes) into the actual DB value...based on current saved value
                $literalToDBValue = prepareLiteralTextForDB($filterElementObject, $filterTerms[$filterId], $curlyBracketEntry, $userComparisonId); // prepends checkbox characters and converts yes/nos, {USER}, etc
                $literalToDBValue = str_replace('{ID}',$curlyBracketEntry,$literalToDBValue);
                // if no declared API format value, go look it up
                if($curlyBracketEntry AND $curlyBracketEntry != 'new') {
                $preppedFormatValue = prepvalues($literalToDBValue, substr($filterTerms[$filterId],1,-1), $curlyBracketEntry); // will be an array
                if(is_array($preppedFormatValue) AND count((array) $preppedFormatValue)==1) {
                    $preppedFormatValue = $preppedFormatValue[0]; // take the single value if there's only one, same as display function does
                }
                $plainLiteralValue = $preppedFormatValue;
            } else {
                    // for new entries get the defaults?? Needs testing
                }
            }
            $plainLiteralValue = $plainLiteralValue != $literalToDBValue ? $plainLiteralValue : "";
            $filterTerms[$filterId] = $literalToDBValue;
        }
    }

    if (!$conditionsFilterComparisonValue) {
        $conditionsFilterComparisonValue = $quotes.$likebits.formulize_db_escape($filterTerms[$filterId]).$likebits.$quotes;
        if($plainLiteralValue) {
            $specialCharsTerm = htmlspecialchars($plainLiteralValue, ENT_QUOTES);
            if($specialCharsTerm != $plainLiteralValue) {
                $quotes = (is_numeric($specialCharsTerm) AND !$likebits) ? "" : "'";
                $conditionsFilterComparisonValue .= '-->>ADDPLAINLITERAL<<--'.$quotes.$likebits.formulize_db_escape($specialCharsTerm).$likebits.$quotes;
            }
            $quotes = (is_numeric($plainLiteralValue) AND !$likebits) ? "" : "'";
            $conditionsFilterComparisonValue .= '-->>ADDPLAINLITERAL<<--'.$quotes.$likebits.formulize_db_escape($plainLiteralValue).$likebits.$quotes;
        }
    }

    // if it's a { } term, then assume it's a data handle for a field in the form where the element is being included
    // do this only when the left side is NOT linked, so as an alternative to the handling of { } above when left side is linked
    if (substr($filterTerms[$filterId],0,1) == "{" AND substr($filterTerms[$filterId],-1)=="}" AND (isMetaDataField($filterElementIds[$filterId]) OR !$targetElementObject->isLinked)) {
        if (isset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$curlyBracketEntry][$bareFilterTerm])) {
            $conditionsFilterComparisonValue = "'".$likebits.formulize_db_escape($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$curlyBracketEntry][$bareFilterTerm]).$likebits."'";
        } elseif ($curlyBracketEntry == "new") {
            $elementObject = $element_handler->get($bareFilterTerm);
            if (is_object($elementObject)) {
                $default = $elementObject->getDefaultValues($curlyBracketEntry); // only works for text, textarea, and non-linked selectboxes! all other elements will return an empty array
                $default = $default[0];
                if(is_numeric($default)) {
                    $conditionsFilterComparisonValue = $default;
                } elseif($default) {
                    $conditionsFilterComparisonValue ="'$likebits".$default."$likebits'";
                } else {
                    $conditionsFilterComparisonValue = "";
                }
            } else {
                $conditionsFilterComparisonValue = "";
            }
        } else {
            // set as a single value, use the declared curly bracket form, unless the term is from another form
            // if the term is from another form, then something a little odd is going on...we don't have relationship data to go on at this point, so we will ignore the curly bracket filter at this point
            if(in_array($bareFilterTerm, $curlyBracketForm->getVar('elementHandles'))) {
                $curlyBracketFormFrom = $xoopsDB->prefix("formulize_".$curlyBracketForm->getVar('form_handle'))." AS curlybracketform ";
                if ($likebits == "%") {
                    $conditionsFilterComparisonValue = " CONCAT('%',curlybracketform.`".$bareFilterTerm."`,'%') AND curlybracketform.`entry_id`=$curlyBracketEntryQuoted ";
                } else {
                    $conditionsFilterComparisonValue = " curlybracketform.`".$bareFilterTerm."` AND curlybracketform.`entry_id`=$curlyBracketEntryQuoted ";
                }
            } else {
                $filterOps[$filterId] = " != ";
                $conditionsFilterComparisonValue = " 'lkjdaf9887asd09809KJA$@$%98SD7ASDLJASLD32' "; // GUARANTEED NOT TO MATCH ANYTHING, SO ALL OPTIONS WILL BE RETURNED, SINCE FILTERTERM DOES NOT BELONG TO THE CURLY BRACKET FORM, SO wtf (But can happen if the element filter is used to limit subform values that are being prepopulated!)
            }
        }
    }

    // expand = '' to include is null
    if($conditionsFilterComparisonValue == "''" OR $conditionsFilterComparisonValue == '""') {
        switch($filterOps[$filterId]) {
            case "<=>":
            case "=":
                $conditionsFilterComparisonValue .= " OR `".$filterElementObject->getVar('ele_handle')."` IS NULL";
                break;
            case "!=":
                $conditionsFilterComparisonValue .= " AND `".$filterElementObject->getVar('ele_handle')."` IS NOT NULL";
                break;
        }
    }
    return array($conditionsFilterComparisonValue, $curlyBracketFormFrom);
}


// this function simply draws in the necessary xhr javascript that is used in forms sometimes, and in lists of entries sometimes
function drawXhrJavascript() {
global $xoopsUser;
// added xhr function Jan 5 2010
?>
function initialize_formulize_xhr() {
    if (window.XMLHttpRequest) {
        formulize_xhr = new XMLHttpRequest();
    } else if (window.ActiveXObject) {
        try {
            formuilze_xhr = new ActiveXObject("Msxml2.XMLHTTP");
        } catch (ex) {
            try {
                formulize_xhr = new ActiveXObject("Microsoft.XMLHTTP");
            } catch (ex) {
            }
        }
    }
}

function formulize_xhr_return(op,params,response) {
    // check that this is a valid operation we know how to handle
    if (op == 'check_for_unique_value') {
        response = JSON.parse(response);
        window.formulize_xhr_returned_check_for_unique_value[response.key] = response.val;
        validateAndSubmit(response.leave);
    } else if (op == 'get_element_html') {
        return renderElementHtml(response,params);
    } else if (op == 'get_element_value') {
        return renderElementNewValue(response,params);
    } else if (op == 'delete_uploaded_file') {
        if (response) {
            formulize_delete_successful(response);
        } else {
            formulize_delete_failed();
        }
    } else {
        return false;
    }
}

function formulize_xhr_send(op,params) {
    // check that this is a valid operation we know how to handle
    if (op != 'check_for_unique_value' && op != 'get_element_html' && op != 'get_element_value' && op != 'delete_uploaded_file') {
        return true;
    }
    // unpack the parameters
    var key;
    key = 1;
    var params_for_uri;
    params_for_uri = '';
    params.forEach (function(i) {
        params_for_uri += 'param' + key + '=' + encodeURIComponent(i) + '&';
        key++;
    });
    formulize_xhr.open("GET", '<?php print XOOPS_URL . "/modules/formulize/formulize_xhr_responder.php?"; ?>'+params_for_uri+'op='+op+'&uid=<?php print $xoopsUser ? $xoopsUser->getVar('uid') : 0; ?>', true);
    formulize_xhr.setRequestHeader( "If-Modified-Since", "Sat, 1 Jan 2000 00:00:00 GMT" );
    formulize_xhr.onreadystatechange = function() {
    //alert(formulize_xhr.readyState);
    if (formulize_xhr.readyState == 4) {
        //alert(formulize_xhr.responseText);
        formulize_xhr_return(op,params,formulize_xhr.responseText);
    }
    }
    formulize_xhr.send(null);
}
<?php
}

// provides the token that is associated witht he entry locking/unlocking for this page load
function getEntryLockSecurityToken() {
    static $token;
    $token = $token ? $token : $GLOBALS['xoopsSecurity']->createToken(0, 'formulize_entry_lock_token');
    return $token;
}

// this function creates the javascript snippet that will send the kill locks request
// unload causes it to return the script necessary for in an unload event, which uses a different call (beacon)
function formulize_javascriptForRemovingEntryLocks($unload=false) {
    static $cachedJS = array();
    if(count($cachedJS)==0) { // prepare everything only once
        global $entriesThatHaveBeenLockedThisPageLoad, $xoopsUser;
        $token = getEntryLockSecurityToken();
        $uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
        formulize_scandirAndClean(XOOPS_ROOT_PATH."/modules/formulize/temp/", ".token");
        if(count($entriesThatHaveBeenLockedThisPageLoad)>0) {
            file_put_contents(XOOPS_ROOT_PATH.'/modules/formulize/temp/'.$token.$uid.'.token', serialize($entriesThatHaveBeenLockedThisPageLoad));
        }
        // write a value that we can check later as an antiCSRF token. Cannot validate through the built in token system since it relies on session which will be borked if the user logs out. So we write a file instead.
        // must be raw js since this is sent through the beacon after page is torn down
        $cachedJS['unload'] = "var fd = new FormData();\n";
        $cachedJS['unload'] .= "fd.append('formulize_entry_lock_token', '".$token."');\n";
        $cachedJS['unload'] .= "fd.append('formulize_entry_lock_uid', '".$uid."');\n";
        $cachedJS['unload'] .= "navigator.sendBeacon('".XOOPS_URL."/modules/formulize/formulize_deleteEntryLock.php', fd);\n";
        // can do cleaner with jQuery since this runs inside the page while it exists
        $cachedJS['jQuery'] = "jQuery.post('".XOOPS_URL."/modules/formulize/formulize_deleteEntryLock.php', {\n";
        $cachedJS['jQuery'] .= "    'formulize_entry_lock_token': '".$token."',\n";
        $cachedJS['jQuery'] .= "    'formulize_entry_lock_uid': '".$uid."',\n";
        $cachedJS['jQuery'] .= "    async: false\n});\n";
    }
    return $unload ? $cachedJS['unload'] : $cachedJS['jQuery'];
}



// this function takes a value from the database that has gone through prepvalues (so it's ready for a dataset or already part of a dataset), and makes the display HTML for a list of entries
// we're kind of hacking this...assuming textWidth will be 200 in cases where we don't have it passed in.  With more acrobatics we could get the real text width as specified in the screen, but for columns that are rendered as elements, this is probably an OK compromise
// deDisplay is a flag to control whether the icon for switching an element to editable mode should be present or not
// localIds is an array of ids that will match the order of the values in the array...used to get the id for a subform entry that is being displayed in the list
// $fid is used only in the event of a mod_datetime or creation_datetime or creator_email field being drawn
// $deInstanceCounter is used for addressing editable elements in the list
function getHTMLForList($value, $handle, $entryId, $deDisplay=0, $textWidth=200, $localIds=array(), $fid=0, $row=0, $column=0, $deInstanceCounter=false) {
    $output = "<div class='main-cell-div' id='cellcontents_".$row."_".$column."'>";
    if (!is_array($value)) {
        $value = array($value);
    }
    if (!is_array($localIds)) {
        $localIds = array($localIds);
    }
    $countOfValue = count((array) $value);
    $counter = 1;
    static $cachedFormIds = array();
    static $cachedElementIds = array();
    static $cached_object_type = array();
    if (!isset($cachedFormIds[$handle])) {
        if ($handle == "mod_datetime" OR $handle == "creation_datetime" OR $handle == "creator_email") {
            $cachedFormIds[$handle] = $fid;
            $cachedElementIds[$handle] = $handle;
            $cached_object_type[$handle] = "email";
            if ($handle == "mod_datetime" OR $handle == "creation_datetime") {
                $cached_object_type[$handle] = "date";
            }
        } else {
            $element_handler = xoops_getmodulehandler('elements', 'formulize');
            $elementObject = $element_handler->get($handle);
            $cachedFormIds[$handle] = $elementObject->getVar('id_form');
            $cachedElementIds[$handle] = $elementObject->getVar('ele_id');
            $cached_object_type[$handle] = $elementObject->getVar('ele_type');
        }
    }
    $fid = $cachedFormIds[$handle];
    $element_type = $cached_object_type[$handle];
    foreach ($value as $valueId=>$v) {
        $elstyle = 'style="width: 100%;text-align: ';
        if (is_numeric($v)) {
            $elstyle .= 'right;"'; // and if there is a width that pushes the right edge over then it looks nice, sort of, but more formatting controls on table and whitespace between cells, etc... is necessary
        } else {
            $elstyle .= 'left"';
        }
        $thisEntryId = isset($localIds[$valueId]) ? $localIds[$valueId] : $entryId;
        if ($counter == 1 AND $deDisplay AND $element_type != 'derived') {
            $output .= '<div class="formulize-display-element-edit-icon"><a class="de-edit-icon" href="" onclick="renderElement(\''.$handle.'\', '.$cachedElementIds[$handle].', '.$thisEntryId.', '.$fid.',0,'.$deInstanceCounter.');return false;"></a></div><div class="formulize-display-element-contents">';
        }
        if ("date" == $element_type) {
            $time_value = strtotime($v);
            global $xoopsUser;
            $offset = ($handle == "mod_datetime" OR $handle == "creation_datetime") ? formulize_getUserServerOffsetSecs(timestamp: $time_value) : 0; // no hours/mins in plain dates, but for metadata, get user offset from server timezone which DB should have used to make the dates in question
            $dateStringFormat = ($handle == "mod_datetime" OR $handle == "creation_datetime") ? _MEDIUMDATESTRING : _SHORTDATESTRING; // constants set in /language/english/global.php
            $v = (false === $time_value) ? "" : date($dateStringFormat, ($time_value)+$offset);
        }
        $output .= '<span '.$elstyle.'>' . formulize_numberFormat(str_replace("\n", "<br>", formatLinks($v, $handle, $textWidth, $thisEntryId)), $handle);
        $output .= '</span>';
        if ($counter<$countOfValue) {
            $output .= "<br>";
        }
        $counter++;
    }
		if ($deDisplay AND $element_type != 'derived') {
			$output .= '</div>';
		}
        $output .= "</div>";
    return $output;
}


// THIS FUNCTION WILL CALL DISPLAY ELEMENT WITH THE RIGHT PARAMS TO RETURN THE HTML FOR AN ELEMENT THAT WILL NOT SAVE IN FORMULIZE BUT CAN BE USED TO GET THE UI FOR THE ELEMENT ON THE SCREEN
// MEANT FOR USE IN LIST OF ENTRIES TEMPLATES AND ELSEWHERE THAT THE DEVELOPER MIGHT WANT TO HAVE AN ELEMENT RENDERED CLEANLY FOR PICKING UP THE USER'S SELECTION LATER, BUT THEY DON'T WANT THE ELEMENT ACTUALLY TIED TO THE UNDERLYING ENTRY/VALUE
function htmlForElement($elementHandle, $nameForHTML="orphaned_formulize_element", $entry_id="new") {
    include_once XOOPS_ROOT_PATH ."/modules/formulize/include/elementdisplay.php";
    // 0 is the element HTML, 1 is the disabled flag
    $renderedElementArray = displayElement("", $elementHandle, $entry_id, $nameForHTML, null, null, false);
    if (is_array($renderedElementArray) AND $renderedElementArray[1]) {
        return "This form element cannot be displayed because it is disabled.";
    } elseif (is_array($renderedElementArray)) {
        return $renderedElementArray[0]->render();
    } else {
        return $renderedElementArray;
    }
}


// this function takes a series of element objects, and returns an array of xoopsFormHidden objects that can be later added to a form or rendered as required.
// The "decue" hidden elements that need to present to trigger reading of these values, are not being passed back with these objects, so the user code that calls this must create those cues as required.  See formdisplay.php for an example.
// $elements can be an array of element objects, or a single element object.
// The function will return an array where the keys are the element ids, and the values are the hidden element objects (xoopsFormHidden objects).
// For checkbox elements, the value will be an array of objects, each one representing one hidden value that should be included in the form
// $entry is the entry id of the entry being rendered, or "new" for new entries
// $fid is the form id of the form that we're rendering these elements for
// $screen is the screen in effect if any
function generateHiddenElements($elements, $entry, $screen) {
    $hiddenElements = array();
    foreach ($elements as $thisElement) {
        // display these elements as hidden elements with the default value

        $fid = $thisElement->getVar('id_form');
        switch ($thisElement->getVar('ele_type')) {
            case "radio":
                if ($entry == "new") {
                $indexer = 1;
                foreach ($thisElement->getVar('ele_value') as $k=>$v) {
                    if ($v == 1) {
                        $hiddenElements[$thisElement->getVar('ele_id')] = new xoopsFormHidden('de_'.$fid.'_'.$entry.'_'.$thisElement->getVar('ele_id'), $indexer);
                    }
                    $indexer++;
                }
            }
            break;

            case "select":
                $ele_value = $thisElement->getVar('ele_value');
                if($entry == "new") {
                    if($thisElement->isLinked)  {
                        if(is_array($ele_value[13]) AND $ele_value[13][0] != "") {
                            $defaultValue = $ele_value[1] ? $ele_value[13] : $ele_value[13][0]; // if not multiple selection, then use first (and only?) specified default value
                        } else {
                            $defaultValue = $ele_value[13] ? $ele_value[13] : null;
                        }
                        $hiddenElements[$thisElement->getVar('ele_id')] = new xoopsFormHidden('de_'.$fid.'_'.$entry.'_'.$thisElement->getVar('ele_id'), $defaultValue);
                    } else {
                        $indexer++;
                        $ele_value = $thisElement->getVar('ele_value');
                        foreach ($ele_value[2] as $k=>$v) {
                            if ($v == 1) {
                                $elementName = 'de_'.$fid.'_'.$entry.'_'.$thisElement->getVar('ele_id');
                                $elementName .= $ele_value[1] ? "[]" : ""; // if multiple selection allowed, use square brackets
                                $hiddenElements[$thisElement->getVar('ele_id')][] = new xoopsFormHidden($elementName, $indexer);
                            }
                            $indexer++;
                        }
                    }
                } else {
                    $data_handler = new formulizeDataHandler($thisElement->getVar('id_form'));
                    $selectOptions = $data_handler->getElementValueInEntry($entry, $thisElement);
                    if($thisElement->isLinked)  {
                        $separator = ',';
                        $selectOptions = trim($selectOptions);
                    } else {
                        $separator = '*=+*:';
                    }
                    $hiddenName = 'de_'.$fid.'_'.$entry.'_'.$thisElement->getVar('ele_id');
                    if($ele_value[1]) { // multi selection
                        $defaultValue = explode($separator,$selectOptions);
                        $defaultValue = is_array($defaultValue) ? $defaultValue : array($defaultValue);
                        $hiddenName .= '[]';
                    } else {
                        $defaultValue = $selectOptions;
                    }
                    $indexer = 1;
                    foreach($ele_value[2] as $k=>$v) {
                        if(!is_array($defaultValue) AND $k === $defaultValue) {
                            $hiddenElements[$thisElement->getVar('ele_id')] = new xoopsFormHidden($hiddenName, $indexer);
                        } elseif(is_array($defaultValue) AND in_array($k, $defaultValue)) {
                            $hiddenElements[$thisElement->getVar('ele_id')][] = new xoopsFormHidden($hiddenName, $indexer);
                        }
                        $indexer++;
                    }
                }
            break;


            case "checkbox":
                if ($entry == "new") {
                    $indexer = 1;
                    foreach ($thisElement->getVar('ele_value') as $k=>$v) {
                        if ($v == 1) {
                            $hiddenElements[$thisElement->getVar('ele_id')][] = new xoopsFormHidden('de_'.$fid.'_'.$entry.'_'.$thisElement->getVar('ele_id')."[]", $indexer);
                        }
                        $indexer++;
                    }
                } else {
                    $data_handler = new formulizeDataHandler($thisElement->getVar('id_form'));
                    $checkBoxOptions = $data_handler->getElementValueInEntry($entry, $thisElement);
                    $indexer = 1;
                    foreach ($thisElement->getVar('ele_value') as $k=>$v) {
                        if (strstr($checkBoxOptions, $k)) {
                            $hiddenElements[$thisElement->getVar('ele_id')][] = new xoopsFormHidden('de_'.$fid.'_'.$entry.'_'.$thisElement->getVar('ele_id')."[]", $indexer);
                        }
                        $indexer++;
                    }
                }
                break;

            case "yn":
                if ($entry == "new") {
                    $ele_value = $thisElement->getVar('ele_value');
                    // check to see if Yes is the value, and if so, set 1, otherwise, set 2.  2 is the value used when No is the selected option in YN radio buttons
                    $yesNoValue = $ele_value['_YES'] == 1 ? 1 : 2;
                    $hiddenElements[$thisElement->getVar('ele_id')] = new xoopsFormHidden('de_'.$fid.'_'.$entry.'_'.$thisElement->getVar('ele_id'), $yesNoValue);
                }
                break;

            case "text":
                if ($entry == "new") {
                    global $myts;
                    if (!$myts){ $myts =& MyTextSanitizer::getInstance(); }
                        $ele_value = $thisElement->getVar('ele_value');
                        $hiddenElements[$thisElement->getVar('ele_id')] = new xoopsFormHidden('de_'.$fid.'_'.$entry.'_'.$thisElement->getVar('ele_id'), $myts->htmlSpecialChars(getTextboxDefault($ele_value[2], $thisElement->getVar('id_form'), $entry, $ele_value[11])));
                    } else {
                        include_once XOOPS_ROOT_PATH . "/modules/formulize/class/data.php";
                        $data_handler = new formulizeDataHandler($fid);
                        $hiddenElements[$thisElement->getVar('ele_id')] = new xoopsFormHidden('de_'.$fid.'_'.$entry.'_'.$thisElement->getVar('ele_id'), $data_handler->getElementValueInEntry($entry, $thisElement));
                    }
                    break;

            case "textarea":
                if ($entry == "new") {
                    global $myts;
                    if (!$myts){
                        $myts =& MyTextSanitizer::getInstance();
                    }
                    $ele_value = $thisElement->getVar('ele_value');
                    $hiddenElements[$thisElement->getVar('ele_id')] = new xoopsFormHidden('de_'.$fid.'_'.$entry.'_'.$thisElement->getVar('ele_id'), $myts->htmlSpecialChars(getTextboxDefault($ele_value[0], $thisElement->getVar('id_form'), $entry)));
                } else {
                    include_once XOOPS_ROOT_PATH . "/modules/class/data.php";
                    $data_handler = new formulizeDataHandler($fid);
                    $hiddenElements[$thisElement->getVar('ele_id')] = new xoopsFormHidden('de_'.$fid.'_'.$entry.'_'.$thisElement->getVar('ele_id'), $data_handler->getElementValueInEntry($entry, $thisElement));
                }
                break;

            case "date":
                if ($entry == "new") {
                    $ele_value = $thisElement->getVar('ele_value');
                    if ($ele_value[0] == "" OR $ele_value[0] == _DATE_DEFAULT) {
                        $valueToUse = "";
                    } elseif (preg_replace("/[^A-Z{}]/","", $ele_value[0]) === "{TODAY}") {
                        $number = preg_replace("/[^0-9+-]/","", $ele_value[0]);
                        $valueToUse = date("Y-m-d", mktime(0, 0, 0, date("m") , intval(date("d"))+intval($number), date("Y")));
                    } else {
                        $valueToUse = $ele_value[0];
                    }
                    $hiddenElements[$thisElement->getVar('ele_id')] = new xoopsFormHidden('de_'.$fid.'_'.$entry.'_'.$thisElement->getVar('ele_id'), $valueToUse);
                }
                break;

            default:
                $ele_type = $thisElement->getVar('ele_type');
				if(file_exists(XOOPS_ROOT_PATH."/modules/formulize/class/".$ele_type."Element.php")) {
					$elementTypeHandler = xoops_getmodulehandler($ele_type."Element", "formulize");
					$hiddenElements[$thisElement->getVar('ele_id')] = $elementTypeHandler->render('', $thisElement->getVar('ele_caption'), 'de_'.$fid.'_'.$entry.'_'.$thisElement->getVar('ele_id'), false, $element, $entry, $screen, getEntryOwner($entry, $thisElement->getVar('id_form')), true); // last true causes a hidden default value element to be returned, but class must be written to be aware of this!
                }
        }
    }
    return $hiddenElements;
}


// Converts linked select boxes from single option only (big int)
// to a multi-option allowed select box with preceding and trailing commas
function convertSelectBoxToMulti($table, $column) {
    global $xoopsDB;

    $sql1 = "ALTER TABLE `$table` CHANGE `$column` `$column` TEXT";
    $sql2 = "UPDATE `$table` SET `$column`=CONCAT(',', `$column`, ',') WHERE `$column` NOT LIKE '%[^0-9]%'";

    if (!$result1 = $xoopsDB->query($sql1)) {
        return false;
    }

    if (!$result2 = $xoopsDB->query($sql2)) {
        return false;
    }

    return true;
}


// Converts a linked select box from multi-option allowed (with preceding and trailing
// commas) to a single option allowed select box with data type bigint.
function convertSelectBoxToSingle($table, $column) {
    global $xoopsDB;

    $sql1 = "UPDATE `$table` SET `$column`=SUBSTRING_INDEX (TRIM(BOTH ',' FROM `$column`), ',', 1) WHERE `$column` LIKE ',%,'";
    $sql2 = "ALTER TABLE `$table` CHANGE `$column` `$column` BIGINT NULL DEFAULT NULL";

    if (!$result1 = $xoopsDB->query($sql1)) {
        print "<br>$sql1<br>";
        return false;
    }

    if (!$result2 = $xoopsDB->query($sql2)) {
        print "<br>$sql2<br>";
        return false;
    }

    return true;
}

function formulize_db_escape($value) {
  global $xoopsDB;
  static $methodExists;
  if(!isset($methodExists)) {
    $methodExists = method_exists($xoopsDB, 'escape');
  }
  if($methodExists) {
    return $xoopsDB->escape($value);
  } else {
    $value = $xoopsDB->quote($value);
    return substr($value, 1,-1);
  }
}

// THANKS TO baptiste.place@utopiaweb.fr on php.net for this conversion function:
/**
 * Convert a date format to a strftime format
 *
 * Timezone conversion is done for unix. Windows users must exchange %z and %Z.
 *
 * Unsupported date formats : S, n, t, L, B, u, e, I, P, Z, c, r
 * Unsupported strftime formats : %U, %W, %C, %g, %r, %R, %T, %X, %c, %D, %F, %x
 *
 * @param string $dateFormat a date format
 * @return string
 */
function dateFormatToStrftime($dateFormat) {


/*
    // UNCOMMENT THIS BLOCK TO GET A DEBUG OUTPUT THAT SHOWS WHAT FORMAT CODES ARE SUPPORTED ON YOUR CURRENT SERVER!!
    // THANKS TO PHP.NET FOR THIS CODE!!
    static $check = false;
    if(!$check) {
        $check = true;
        print "<pre>";

        // Describe the formats.
        $strftimeFormats = array(
            'A' => 'A full textual representation of the day',
            'B' => 'Full month name, based on the locale',
            'C' => 'Two digit representation of the century (year divided by 100, truncated to an integer)',
            'D' => 'Same as "%m/%d/%y"',
            'E' => '',
            'F' => 'Same as "%Y-%m-%d"',
            'G' => 'The full four-digit version of %g',
            'H' => 'Two digit representation of the hour in 24-hour format',
            'I' => 'Two digit representation of the hour in 12-hour format',
            'J' => '',
            'K' => '',
            'L' => '',
            'M' => 'Two digit representation of the minute',
            'N' => '',
            'O' => '',
            'P' => 'lower-case "am" or "pm" based on the given time',
            'Q' => '',
            'R' => 'Same as "%H:%M"',
            'S' => 'Two digit representation of the second',
            'T' => 'Same as "%H:%M:%S"',
            'U' => 'Week number of the given year, starting with the first Sunday as the first week',
            'V' => 'ISO-8601:1988 week number of the given year, starting with the first week of the year with at least 4 weekdays, with Monday being the start of the week',
            'W' => 'A numeric representation of the week of the year, starting with the first Monday as the first week',
            'X' => 'Preferred time representation based on locale, without the date',
            'Y' => 'Four digit representation for the year',
            'Z' => 'The time zone offset/abbreviation option NOT given by %z (depends on operating system)',
            'a' => 'An abbreviated textual representation of the day',
            'b' => 'Abbreviated month name, based on the locale',
            'c' => 'Preferred date and time stamp based on local',
            'd' => 'Two-digit day of the month (with leading zeros)',
            'e' => 'Day of the month, with a space preceding single digits',
            'f' => '',
            'g' => 'Two digit representation of the year going by ISO-8601:1988 standards (see %V)',
            'h' => 'Abbreviated month name, based on the locale (an alias of %b)',
            'i' => '',
            'j' => 'Day of the year, 3 digits with leading zeros',
            'k' => 'Hour in 24-hour format, with a space preceding single digits',
            'l' => 'Hour in 12-hour format, with a space preceding single digits',
            'm' => 'Two digit representation of the month',
            'n' => 'A newline character ("\n")',
            'o' => '',
            'p' => 'UPPER-CASE "AM" or "PM" based on the given time',
            'q' => '',
            'r' => 'Same as "%I:%M:%S %p"',
            's' => 'Unix Epoch Time timestamp',
            't' => 'A Tab character ("\t")',
            'u' => 'ISO-8601 numeric representation of the day of the week',
            'v' => '',
            'w' => 'Numeric representation of the day of the week',
            'x' => 'Preferred date representation based on locale, without the time',
            'y' => 'Two digit representation of the year',
            'z' => 'Either the time zone offset from UTC or the abbreviation (depends on operating system)',
            '%' => 'A literal percentage character ("%")',
        );

        // Results.
        $strftimeValues = array();

        // Evaluate the formats whilst suppressing any errors.
        foreach($strftimeFormats as $format => $description){
            if (False !== ($value = @strftime("%{$format}"))){
                $strftimeValues[$format] = $value;
            }
        }

        // Find the longest value.
        $maxValueLength = 2 + max(array_map('strlen', $strftimeValues));

        // Report known formats.
        foreach($strftimeValues as $format => $value){
            echo "Known format   : '{$format}' = ", str_pad("'{$value}'", $maxValueLength), " ( {$strftimeFormats[$format]} )\n";
        }

        // Report unknown formats.
        foreach(array_diff_key($strftimeFormats, $strftimeValues) as $format => $description){
            echo "Unknown format : '{$format}'   ", str_pad(' ', $maxValueLength), ($description ? " ( {$description} )" : ''), "\n";
        }

        print "</pre>";
    }
*/

    $caracs = array(
        // Day - no strf eq : S
        'd' => '%d', 'D' => '%a', 'j' => '%e', 'l' => '%A', 'N' => '%u', 'w' => '%w', 'z' => '%j',
        // Week - no date eq : %U, %W
        'W' => '%V',
        // Month - no strf eq : n, t
        'F' => '%B', 'm' => '%m', 'M' => '%b',
        // Year - no strf eq : L; no date eq : %C, %g
        'o' => '%G', 'Y' => '%Y', 'y' => '%y',
        // Time - no strf eq : B, u; no date eq : %r, %R, %T, %X
        'a' => '%P', 'A' => '%p', 'g' => '%l', 'h' => '%I', 'H' => '%H', 'G' => '%H', 'i' => '%M', 's' => '%S',
        // Timezone - no strf eq : e, I, P, Z
        'O' => '%z', 'T' => '%Z',
        // Full Date / Time - no strf eq : c, r; no date eq : %c, %D, %F, %x
        'U' => '%s'
    );

    $dateFormat = strtr((string)$dateFormat, $caracs);

    // Windows follows its own rules....
    if(substr(PHP_OS,0,3) == 'WIN') {
        $dateFormat = str_replace('%e', '%#d', $dateFormat); // day of month
        $dateFormat = str_replace('%l', '%#I', $dateFormat); // 01-12 hour
        $dateFormat = str_replace('%P', '%p', $dateFormat); // AM/PM indicator
    }

    return $dateFormat;

}

// THIS FUNCTION PARSES OUT THE {USER} AND {TODAY} KEYWORDS INTO THEIR LITERAL VALUES
// if element is passed in, we will check the element to see if it is a username list, and use the uid instead of name if so -- intended for situations when you're parsing user and was the compare to the id, such as when building filters
function parseUserAndToday($term, $element=null) {
		global $xoopsUser;

    $uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
    $term = str_replace('{USER_ID}', $uid, $term);
    if (strstr($term, "{USER}")) {
        $name = 0;
		if($xoopsUser) {
            if(isMetaDataField($element)) {
                $name = $xoopsUser->getVar('uid');
            } elseif($element) {
                $element = _getElementObject($element);
                $ele_value = $element->getVar('ele_value');
                if(is_array($ele_value[2]) AND strstr(key($ele_value[2]), 'NAMES}')) {
                    $name = $xoopsUser->getVar('uid');
                }
            }
            if(!$name) {
                $name = htmlspecialchars_decode($xoopsUser->getVar('name'), ENT_QUOTES);
    			if(!$name) { $name = htmlspecialchars_decode($xoopsUser->getVar('uname'), ENT_QUOTES); }
            }
		}
        $term = str_replace('{USER}', $name, $term);
	}
 	if (substr(trim($term,"{}"), 0, 5) == "TODAY") {
		$number = substr(trim($term, "{}"), 5);
		$term = date("Y-m-d",mktime(0, 0, 0, date("m") , date("d")+$number, date("Y")));
	}
  return $term;
}

// THIS FUNCTION ESTABLISHES LINKS BETWEEN ANY 1-1 LINKED FORMS, ON THE SAME PAGE LOAD IN WHICH DATA WAS SAVED
// THIS FUNCTION SHOULD BE CALLED AFTER NEW ENTRIES HAVE BEEN SAVED IN A FORM, AND 1-1 LINKS NEED TO BE ESTABLISHED BETWEEN THE ENTRIES IN THAT RELATIONSHIP
// THIS FUNCTION SHOULD BE CALLED AFTER ANY APPLICABLE SECURITY CHECKS!  SO WE KNOW THE USER IS ALLOWED TO WRITE DATA TO THE POTENTIALLY AFFECTED ENTRIES.
// $frid is the form relationship id that in which we are trying to establish the links
// $fid is the form we're checking to see if new entry ids were written or not, and if they were, then we try to write the linked values into the right fields in the corresponding entries
// returns an array of arrays, that tells all the affected forms and entry ids
// THIS FUNCTION CAN PICK UP EXISTING ENTRIES AS WELL AS NEW ONES!! BUT IT ONLY WRITES TO THE DB IF THERE IS NO ONE TO ONE CONNECTION ESTABLISHED SO FAR. IT WILL NOT REWRITE EXISTING CONNECTIONS.
function formulize_makeOneToOneLinks($frid, $fid) {
    static $oneToOneLinksMade = array();
    $form1EntryIds = array();
    $form2EntryIds = array();
    $form1s = array();
    $form2s = array();
    if(!isset($oneToOneLinksMade[$frid][$fid]) AND isset($GLOBALS['formulize_allSubmittedEntryIds'][$fid])) {
        $frameworkHandler = xoops_getmodulehandler('frameworks', 'formulize');
        $frameworkObject = $frameworkHandler->get($frid);
        foreach($frameworkObject->getVar('links') as $thisLink) {
            if($thisLink->getVar('relationship') == 1 AND $thisLink->getVar('unifiedDisplay')) { // 1 signifies one to one relationships
                $form1 = $thisLink->getVar('form1');
                $form2 = $thisLink->getVar('form2');
                $key1 = $thisLink->getVar('key1');
                $key2 = $thisLink->getVar('key2');
                $form1EntryId = "";
                $form2EntryId = "";
                // check that the fid in question is form1 or form2, just in case
                if($fid != $form1 AND $fid != $form2) {
                    continue;
                }
                $entryToWriteToForm1 = $GLOBALS['formulize_allWrittenEntryIds'][$form1][0] ? $GLOBALS['formulize_allWrittenEntryIds'][$form1][0] : '';
                $entryToWriteToForm1 = (!$entryToWriteToForm1 AND $GLOBALS['formulize_allSubmittedEntryIds'][$form1][0]) ? $GLOBALS['formulize_allSubmittedEntryIds'][$form1][0] : $entryToWriteToForm1;
                if(!$entryToWriteToForm1 AND is_array($_POST['form_'.$form1.'_rendered_entry']) AND isset($_POST['form_'.$form1.'_rendered_entry'][0]) AND is_numeric($_POST['form_'.$form1.'_rendered_entry'][0])) {
                    if(count((array) $_POST['form_'.$form1.'_rendered_entry']) == 1) {
                        $entryToWriteToForm1 = intval($_POST['form_'.$form1.'_rendered_entry'][0]);
                    } else {
                        error_log("Formulize error: there was more than one entry in $form1 included in the dataset for this pageload, so we could not determine a single entry for establishing one-to-one connections.");
                    }
                    if(!$entryToWriteToForm1 AND isset($_POST['entry'.$form1]) AND is_numeric($_POST['entry'.$form1])) {
                        // last ditch... try to see if an entry in the main form was declared in the form submission itself (no element is present on screen it seems)
                        $entryToWriteToForm1 = $_POST['entry'.$form1];
                    } else {
                        error_log("Formulize error: we could not determine which entry in form $form1 we should use for writing the key value for the relationship. Is there an element of the main form present on the page?");
                    }
                }
                $entryToWriteToForm2 = $GLOBALS['formulize_allWrittenEntryIds'][$form2][0] ? $GLOBALS['formulize_allWrittenEntryIds'][$form2][0] : '';
                $entryToWriteToForm2 = (!$entryToWriteToForm2 AND $GLOBALS['formulize_allSubmittedEntryIds'][$form2][0]) ? $GLOBALS['formulize_allSubmittedEntryIds'][$form2][0] : $entryToWriteToForm2;
                if(!$entryToWriteToForm2 AND is_array($_POST['form_'.$form2.'_rendered_entry']) AND isset($_POST['form_'.$form2.'_rendered_entry'][0]) AND is_numeric($_POST['form_'.$form2.'_rendered_entry'][0])) {
                    if(count((array) $_POST['form_'.$form2.'_rendered_entry']) == 1) {
                        $entryToWriteToForm2 = intval($_POST['form_'.$form2.'_rendered_entry'][0]);
                    } else {
                        error_log("Formulize error: there was more than one entry in $form2 included in the dataset for this pageload, so we could not determine a single entry for establishing one-to-one connections.");
                    }
                    if(!$entryToWriteToForm2 AND isset($_POST['entry'.$form2]) AND is_numeric($_POST['entry'.$form2])) {
                        // last ditch... try to see if an entry in the main form was declared in the form submission itself (no element is present on screen it seems)
                        $entryToWriteToForm2 = $_POST['entry'.$form2];
                    } else {
                        error_log("Formulize error: we could not determine which entry in form $form2 we should use for writing the key value for the relationship. Is there an element of the main form present on the page?");
                    }
                }
                $existingValueForKey1 = false;
                if($entryToWriteToForm1) {
                    $dataHandlerForm1 = new formulizeDataHandler($form1);
                    $existingValueForKey1 = $dataHandlerForm1->elementHasValueInEntry($entryToWriteToForm1, $key1);
                }
                $existingValueForKey2 = false;
                if($entryToWriteToForm2) {
                    $dataHandlerForm2 = new formulizeDataHandler($form2);
                    $existingValueForKey2 = $dataHandlerForm2->elementHasValueInEntry($entryToWriteToForm2, $key2);
                }
                if($thisLink->getVar('common')) {
                    if($entryToWriteToForm1 AND ((!isset($_POST["de_".$form1."_new_".$key1]) OR $_POST["de_".$form1."_new_".$key1] === "") AND (!isset($_POST["de_".$form1."_".$GLOBALS['formulize_allSubmittedEntryIds'][$form1][0]."_".$key1]) OR $_POST["de_".$form1."_".$GLOBALS['formulize_allSubmittedEntryIds'][$form1][0]."_".$key1] === ""))) {
                        // if we don't have a value for this element, then populate it with the value from the other element
                        if(!$existingValueForKey1 AND $commonValueToWrite = formulize_findCommonValue($form1, $form2, $key1, $key2)) {
                            $form1EntryId = formulize_writeEntry(array($key1=>$commonValueToWrite), $entryToWriteToForm1);
                        }
                    }
                    if($entryToWriteToForm2 AND ((!isset($_POST["de_".$form2."_new_".$key2]) OR $_POST["de_".$form2."_new_".$key2] === "") AND (!isset($_POST["de_".$form2."_".$GLOBALS['formulize_allSubmittedEntryIds'][$form2][0]."_".$key2]) OR $_POST["de_".$form2."_".$GLOBALS['formulize_allSubmittedEntryIds'][$form2][0]."_".$key2] === ""))) {
                        // if we don't have a value for this element, then populate it with the value from the other element
                        if(!$existingValueForKey2 AND $commonValueToWrite = formulize_findCommonValue($form2, $form1, $key2, $key1)) { // since we're looking for the other form, swap the order of param inputs
                            $form2EntryId = formulize_writeEntry(array($key2=>$commonValueToWrite), $entryToWriteToForm2);
                        }
                    }
                } else {
                    // figure out which one is on which side of the linked selectbox
                    $element_handler = xoops_getmodulehandler('elements', 'formulize');
                    $linkedElement1 = $element_handler->get($key1);
                    $linkedElement1EleValue = $linkedElement1->getVar('ele_value');
                    $linkedElement1EleValueParts = strstr($linkedElement1EleValue[2], "#*=:*") ? explode("#*=:*", $linkedElement1EleValue[2]) : array();
                    if(count((array) $linkedElement1EleValueParts)>0 AND $linkedElement1EleValueParts[0] == $form2) {
                            // element 1 is the linked selectbox, so get the value of entry id for what we just created in form 2, and put it in element 1
                            $linkedValueToWrite = isset($GLOBALS['formulize_newEntryIds'][$form2][0]) ? $GLOBALS['formulize_newEntryIds'][$form2][0] : "";
                            $linkedValueToWrite = (!$linkedValueToWrite AND isset($GLOBALS['formulize_allSubmittedEntryIds'][$form2][0])) ? $GLOBALS['formulize_allSubmittedEntryIds'][$form2][0] : $linkedValueToWrite; // or get the first entry ID that we wrote to the form, if no new entries were written to the form
                            $linkedValueToWrite = (!$linkedValueToWrite AND $entryToWriteToForm2) ? $entryToWriteToForm2 : $linkedValueToWrite;
                        if($entryToWriteToForm1 AND !$existingValueForKey1 AND ((!isset($_POST["de_".$form1."_new_".$key1]) OR $_POST["de_".$form1."_new_".$key1] === "") AND (!isset($_POST["de_".$form1."_".$GLOBALS['formulize_allSubmittedEntryIds'][$form1][0]."_".$key1]) OR $_POST["de_".$form1."_".$GLOBALS['formulize_allSubmittedEntryIds'][$form1][0]."_".$key1] === ""))) {
                            $form1EntryId = formulize_writeEntry(array($key1=>$linkedValueToWrite), $entryToWriteToForm1);
                        } elseif(!$entryToWriteToForm1) {
                            $entryToWriteToForm1 = formulize_writeEntry(array($key1=>$linkedValueToWrite));
                        }
                    } else {
                            // element 2 is the linked selectbox, so get the value of entry id for what we just created in form 1 and put it in element 2
                            $linkedValueToWrite = isset($GLOBALS['formulize_newEntryIds'][$form1][0]) ? $GLOBALS['formulize_newEntryIds'][$form1][0] : "";
                            $linkedValueToWrite = (!$linkedValueToWrite AND isset($GLOBALS['formulize_allSubmittedEntryIds'][$form1][0])) ? $GLOBALS['formulize_allSubmittedEntryIds'][$form1][0] : $linkedValueToWrite; // or get the first entry ID that we wrote to the form, if no new entries were written to the form
                            $linkedValueToWrite = (!$linkedValueToWrite AND $entryToWriteToForm1) ? $entryToWriteToForm1 : $linkedValueToWrite;
                        if($entryToWriteToForm2 AND !$existingValueForKey2 AND ((!isset($_POST["de_".$form2."_new_".$key2]) OR $_POST["de_".$form2."_new_".$key2] === "") AND (!isset($_POST["de_".$form2."_".$GLOBALS['formulize_allSubmittedEntryIds'][$form2][0]."_".$key2]) OR $_POST["de_".$form2."_".$GLOBALS['formulize_allSubmittedEntryIds'][$form2][0]."_".$key2] === ""))) {
                            $form2EntryId = formulize_writeEntry(array($key2=>$linkedValueToWrite), $entryToWriteToForm2);
                        } elseif(!$entryToWriteToForm2) {
                            $entryToWriteToForm2 = formulize_writeEntry(array($key2=>$linkedValueToWrite));
                        }
                    }
                }
                $form1EntryIds[] = $entryToWriteToForm1;
                $form2EntryIds[] = $entryToWriteToForm2;
                $form1s[] = $form1;
                $form2s[] = $form2;
            }
        }
        $oneToOneLinksMade[$frid][$fid] = array($form1s, $form2s, $form1EntryIds, $form2EntryIds);
    }
    return $oneToOneLinksMake[$frid][$fid];
}

// THIS FUNCTION FIGURES OUT THE COMMON VALUE THAT WE SHOULD WRITE WHEN A FORM IN A ONE-TO-ONE RELATIONSHIP IS BEING DISPLAYED AFTER A NEW ENTRY HAS BEEN WRITTEN
function formulize_findCommonValue($form1, $form2, $key1, $key2) {
	$commonValueToWrite = "";
	if(isset($_POST["de_".$form2."_new_".$key2]) AND $_POST["de_".$form2."_new_".$key2] == "{ID}") { // common value is pointing at a textbox that copies the entry ID, so grab the entry ID of the entry just written in the other form
		$commonValueToWrite = $GLOBALS['formulize_newEntryIds'][$form2][0];
	} elseif(isset($_POST["de_".$form2."_new_".$key2])) { // grab the value just written in the field of the other form
		$commonValueToWrite = $_POST["de_".$form2."_new_".$key2];
	} elseif(isset($_POST["de_".$form2."_".$GLOBALS['formulize_allSubmittedEntryIds'][$form2][0]."_".$key2])) { // grab the value just written in the first entry we saved in the paired form
		$commonValueToWrite = $_POST["de_".$form2."_".$GLOBALS['formulize_allSubmittedEntryIds'][$form2][0]."_".$key2];
	} elseif(isset($GLOBALS['formulize_allSubmittedEntryIds'][$form2][0])) { // try to get the value saved in the DB for the target element in the first entry we just saved in the paired form
		$common_value_data_handler = new formulizeDataHandler($form2);
		if($candidateValue = $common_value_data_handler->getElementValueInEntry($GLOBALS['formulize_allSubmittedEntryIds'][$form2][0], $key2)) {
			$commonValueToWrite = $candidateValue;
		}
	} elseif(isset($_POST['entry'.$form2])) { // if nothing has been saved in this pageload, then go to the DB and see if there is an existing value for an entry that has been declared in the form submission itself to be part of the set of data we're working with
        $common_value_data_handler = new formulizeDataHandler($form2);
		if($candidateValue = $common_value_data_handler->getElementValueInEntry(intval($_POST['entry'.$form2]), $key2)) {
			$commonValueToWrite = $candidateValue;
		}
	}
	return $commonValueToWrite;
}

//Function used to check if the given field is within the list of metadata fields.
function isMetaDataField($field){
    $dataHandler = new formulizeDataHandler(false);
    $metadataFields = $dataHandler->metadataFields;
    foreach ($metadataFields as $value)
    {
        if($value == $field)
        {
            return true;
        }
    }
    return false;
}

/*
 * sendSaveLockPrefToTemplate function returns the isSaveLocked preference value. Used to send value to template
 *
 * return isSaveLocked       preference defining whether the functionality of saving is locked or not
 */
function sendSaveLockPrefToTemplate(){ //$xoopsTpl
    // get preference value
    $module_handler = xoops_gethandler('module');
    $config_handler = xoops_gethandler('config');
    $formulizeModule = $module_handler->getByDirname("formulize");
    $formulizeConfig = $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));

    return $formulizeConfig['isSaveLocked'];
}

// convert variable search terms to their literal values
// ie: {program} gets changed to the value of $_GET['program'] if that is set
// returns updated value, or false to kill value, or true to do nothing
function convertVariableSearchToLiteral($v, $requestKeyToUse) {
    global $xoopsUser;
    if(isset($_POST[$requestKeyToUse])) {
        return htmlspecialchars(strip_tags(trim($_POST[$requestKeyToUse])));
    } elseif(isset($_GET[$requestKeyToUse])) {
        return htmlspecialchars(strip_tags(trim($_GET[$requestKeyToUse])));
    } elseif($v == "{USER}" AND $xoopsUser) {
        return $xoopsUser->getVar('name') ? $xoopsUser->getVar('name') : $xoopsUser->getVar('uname');
    } elseif(!strstr($v, "{BLANK}") AND !strstr($v, "{TODAY") AND !strstr($v, "{PERGROUPFILTER}") AND !strstr($v, "{USER")) {
        return false; // clear terms where no match was found, because this term is not active on the current page, so don't confuse users by showing it
    }
    return true; // do nothing
}

// This function generates HTML for a smart list of columns to use in the change columns list and elsewhere
// The idea is to show columns based on their forms, in a way that mimics the links in the active relationships
function generateTidyElementList($mainformFid, $cols, $selectedCols=array()) {

    $html = "<div>";
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $counter = 0;
    $prevFid = 0;
    foreach($cols as $thisFid=>$columns) {
        if($thisFid != $prevFid) {
            $fidCounter = 0;
            $prevFid = $thisFid;
        }
        $formObject = $form_handler->get($thisFid);
        $boxeshtml = "";
        $hideform = count((array) $cols) > 1 ? "style='opacity: 0; max-height: 0; display: none;'" : "style='opacity: 1; max-height: 10000px; display: block;'"; // start forms closed unless they have selected columns, or unless this is the only form in the set
        $upDisplay = count((array) $cols) > 1 ? "style='display: none;'" : "style='display: inline;'";
        $downDisplay = count((array) $cols) > 1 ? "style='display: inline;'" : "style='display: none;'";
        if($fidCounter == 0 AND $thisFid == $mainformFid) { // add in metadata columns first time through
            array_unshift($columns,
                array('ele_handle'=>'entry_id', 'ele_caption' => _formulize_ENTRY_ID),
                array('ele_handle'=>'creation_uid', 'ele_caption' => _formulize_DE_CALC_CREATOR),
                array('ele_handle'=>'mod_uid', 'ele_caption' => _formulize_DE_CALC_MODIFIER),
                array('ele_handle'=>'creation_datetime', 'ele_caption' => _formulize_DE_CALC_CREATEDATE),
                array('ele_handle'=>'mod_datetime', 'ele_caption' => _formulize_DE_CALC_MODDATE),
                array('ele_handle'=>'creator_email', 'ele_caption' => _formulize_DE_CALC_CREATOR_EMAIL));
        }
        foreach($columns as $column) {
            $counter++;
            $fidCounter++;
            $selected = in_array($column['ele_handle'], $selectedCols) ? "checked='checked'" : "";
            if($selected) {
                $hideform = "style='opacity: 1; max-height: 10000px; display: block;'";
                $upDisplay = "style='display: inline;'";
                $downDisplay = "style='display: none;'";
            }
            $text = (isset($column['ele_colhead']) AND $column['ele_colhead'] != "") ? printSmart(trans($column['ele_colhead']), 75) : printSmart(trans(strip_tags($column['ele_caption'])), 75);
            $boxeshtml .= "<input type='checkbox' name='popnewcols[]' id='popnewcols".$counter."' class='colbox' value=\"{$column['ele_handle']}\" $selected />&nbsp;&nbsp;&nbsp;<label for='popnewcols".$counter."'>$text</label><br />\n";
        }
        $html .= "<p><a onclick='javascript:toggleCols($thisFid);return false;' style='cursor: pointer;'>".$formObject->getVar('title')." <span id='up_".$thisFid."' $upDisplay>&and;</span><span id='down_".$thisFid."' $downDisplay>&or;</span></a></p>\n";
        $html .= "<div class='elements-checkbox-list' id='cols_$thisFid' $hideform>\n";
        $html .= $boxeshtml;
        $html .= "</div>";
    }
    $html .="</div>

    <style>
        .elements-checkbox-list {
            transition: all 1s ease 0.25s;
        }
    </style>

    <script type='text/javascript'>
    function toggleCols(fid) {
        currentOpacity = document.getElementById('cols_'+fid).style.opacity;
        if (currentOpacity != 1) {
            document.getElementById('up_'+fid).style.display = 'inline';
            document.getElementById('down_'+fid).style.display = 'none';
            document.getElementById('cols_'+fid).style.maxHeight = '10000px';
            document.getElementById('cols_'+fid).style.opacity = 1;
            document.getElementById('cols_'+fid).style.display = 'block';
        } else {
            document.getElementById('up_'+fid).style.display = 'none';
            document.getElementById('down_'+fid).style.display = 'inline';
            document.getElementById('cols_'+fid).style.maxHeight = 0;
            document.getElementById('cols_'+fid).style.opacity = 0;
            document.getElementById('cols_'+fid).style.display = 'none';
        }
    }
    </script>

    ";

    return $html;

}


// update derived values in the passed in entry
function formulize_updateDerivedValues($entry, $fid, $frid="") {

    global $formulize_derivedValueBeingUpdated; // record what combination of updates we're doing, and don't retrigger the same one until we're done. This avoids nested calls which can create infinite loops, if for example a before or after save procedure triggers updating derived values in the same form
    if(!is_array($formulize_derivedValueBeingUpdated)) {
        $formulize_derivedValueBeingUpdated = array();
    } else {
        if(isset($formulize_derivedValueBeingUpdated[$entry][$fid][$frid])) {
            return;
        }
    }
    $formulize_derivedValueBeingUpdated[$entry][$fid][$frid] = true;

	$GLOBALS['formulize_forceDerivedValueUpdate'] = true;
	getData($frid, $fid, $entry);
	unset($GLOBALS['formulize_forceDerivedValueUpdate']);

    unset($formulize_derivedValueBeingUpdated[$entry][$fid][$frid]);
}

// this function writes the export query generated by getData, to a file for picking up later so we don't have to figure out all the bits and pieces again
function formulize_catchAndWriteExportQuery($fid) {
    static $lastExportTime = 0;
    $exportTime = time();
    $exportTime = $lastExportTime == $exportTime ? $exportTime + 1 : $exportTime;
    $lastExportTime = $exportTime;
    static $importExportCleanupDone;
    $queryForExportFile = fopen(XOOPS_ROOT_PATH . "/cache/exportQuery_".$exportTime.".formulize_cached_query_for_export", "w");
    fwrite($queryForExportFile, $fid."\n");
    global $xoopsUser;
    $exportUid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
    fwrite($queryForExportFile, $exportUid."\n");
    fwrite($queryForExportFile, $GLOBALS['formulize_queryForExport']);
    fclose($queryForExportFile);
    // cleanup old export files
    if(!$importExportCleanupDone) {
        formulize_benchmark("before scandir during export/import button creation");
        formulize_scandirAndClean(XOOPS_ROOT_PATH."/cache/", "exportQuery");
        formulize_benchmark("after scandir during export/import button creation.");
        $importExportCleanupDone = true;
    }
    return $exportTime;
}

// update the revision data for an entry
// fidOrObject is a form id or a form object for the form we're updating
// entry_to_return is the entry id of the entry we're currently storing in the revision table
function formulize_updateRevisionData($fidOrObject, $entry_to_return, $forceUpdate = false) {
    $form_handler = xoops_getmodulehandler('forms','formulize');
    if(!is_object($fidOrObject) AND is_numeric($fidOrObject)) {
        $formObject = $form_handler->get($fidOrObject);
    } else {
        $formObject = $fidOrObject;
    }
    if(is_object($formObject) AND $formObject->getVar('store_revisions') AND intval($entry_to_return) AND $form_handler->revisionsTableExists($formObject->getVar('id_form'))) {
        global $xoopsDB;
        static $cachedColumns = array();
        if(!isset($cachedColumns[$formObject->getVar('id_form')])) {
            $originalColumnsSQL = "SHOW COLUMNS FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'));
            if($originalColumnsRes = $xoopsDB->queryF($originalColumnsSQL)) {
                $columnList = array();
                while($array = $xoopsDB->fetchArray($originalColumnsRes)) {
                    $columnList[] = $array['Field'];
                }
            } else {
                exit("Error: could not retrieve the list of columns from the original datatable when preparing revision history.");
            }
            $cachedColumns[$formObject->getVar('id_form')] = $columnList;
        } else {
            $columnList = $cachedColumns[$formObject->getVar('id_form')];
        }
        $revisionSQL = "INSERT INTO ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')."_revisions")." (`".implode("`, `", $columnList)."`) SELECT original.* FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle'))." as original WHERE original.entry_id=$entry_to_return";
        if($forceUpdate) {
            $revisionRes = $xoopsDB->queryF($revisionSQL);
        } else {
            $revisionRes = $xoopsDB->query($revisionSQL);
        }
        if(!$revisionRes) {
            exit("Error: could not update revision information for entry $entry_to_return in form ".$formObject->getVar('form_handle').".  This is the query that failed:<br>$revisionSQL<br>Reported MySQL error (if any - if nothing, then query might have been attempted on a non POST submission, since no MySQL error is reported): ".$xoopsDB->error());
        }
    }
}

// get a list of the most recent revision ids for the entries in question
// fidOrObject is a form id or a form object for the form we're updating
// $entryIds is an array of entry ids or a single id
function formulize_getCurrentRevisions($fidOrObject, $entryIds) {
    $form_handler = xoops_getmodulehandler('forms','formulize');
    if(!is_object($fidOrObject) AND is_numeric($fidOrObject)) {
        $formObject = $form_handler->get($fidOrObject);
    } else {
        $formObject = $fidOrObject;
    }
    if(!is_array($entryIds)) {
        $entry = array(intval($entryIds));
    } else { // sanitize them
        $newEntryIds = array();
        foreach($entryIds as $id) {
            $newEntryIds[] = intval($id);
        }
        $entryIds = $newEntryIds;
    }
    if(is_object($formObject) AND $formObject->getVar('store_revisions') AND is_numeric($entryIds[0]) AND $form_handler->revisionsTableExists($formObject->getVar('id_form'))) {
        global $xoopsDB;
        $sql = "SELECT max(revision_id) as rev_id, entry_id FROM ".$xoopsDB->prefix("formulize_".$formObject->getVar('form_handle')."_revisions")." WHERE entry_id IN (".implode(",",$entryIds).")";
        if($res = $xoopsDB->query($sql)) {
            $results = array();
            while($array = $xoopsDB->fetchArray($res)) {
                $results[$array['entry_id']] = $array['rev_id'];
            }
            return $results;
        }
    }
    return false;
}

// This function reads filter conditions, and returns the appropriate filter values that should be used to enforce values in newly created entries
// used for example with subform filters when creating new elements, and fundamental filters on lists of entries
function getFilterValuesForEntry($subformConditions, $curlyBracketEntryid=null) {
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $filterValues = array();
    foreach($subformConditions[1] as $i=>$thisOp) {
        if($thisOp == "=" AND $subformConditions[3][$i] != "oom") {
            if($conditionElementObject = $element_handler->get($subformConditions[0][$i])) {
                // check first for URL matches
                if(substr($subformConditions[2][$i],0,1) == "{" AND substr($subformConditions[2][$i],-1)=="}") {
                    $curlyBracketTerm = substr($subformConditions[2][$i],1,-1);
                    if(isset($_GET[$curlyBracketTerm]) AND ($_GET[$curlyBracketTerm] OR $_GET[$curlyBracketTerm] === 0)) {
                        $filterValues[$conditionElementFid][$subformConditions[0][$i]] = strip_tags(htmlspecialchars($_GET[$curlyBracketTerm], ENT_QUOTES));
                        continue;
                    }
                }
                $conditionElementFid = $conditionElementObject->getVar('id_form');
                // if $subformConditions[0][$i] (left side) is linked to form X
                // and $subformConditions[2][$i] is a { } reference to an element in form X
                // then we just want to use $curlyBracketEntryid as the value
                $conditionElementEleValue = $conditionElementObject->getVar('ele_value');
                if($conditionElementObject->isLinked AND (!isset($conditionElementEleValue['snapshot']) OR !$conditionElementEleValue['snapshot']) AND substr($subformConditions[2][$i],0,1) == "{" AND substr($subformConditions[2][$i],-1)=="}" AND $curlyBracketEntryid) {
                    $ele_value = $conditionElementObject->getVar('ele_value');
                    $linkProperties = explode("#*=:*", $ele_value[2]);
                    $sourceFid = $linkProperties[0];
                    if($dynamicElement = $element_handler->get($curlyBracketTerm)) {
                        if($dynamicElement->getVar('id_form') == $sourceFid) {
                            $filterValues[$conditionElementFid][$subformConditions[0][$i]] = $curlyBracketEntryid;
                            continue;
                        }
                    }
                }
                if(!isset($filterValues[$conditionElementFid][$subformConditions[0][$i]])) {
                    $filterValues[$conditionElementFid][$subformConditions[0][$i]] = prepareLiteralTextForDB($conditionElementObject, $subformConditions[2][$i], $curlyBracketEntryid);
                }
            }
        }
    }
    return $filterValues;
}

// unclear if this successfully parses!
function formulize_validatePHPCode($theCode) {
    while(ob_get_level()) {
        ob_end_clean();
    }
    if ($theCode = trim($theCode) AND function_exists("shell_exec")) {
        $tmpfname = tempnam(XOOPS_ROOT_PATH.'/cache', 'FZ');
        file_put_contents($tmpfname, trim($theCode));
        //$output = shell_exec('php -l "'.$tmpfname.'" 2>&1');
        unlink($tmpfname);
        if (false !== strpos($output, "PHP Parse error")) {
            // remove the second line because detail about the error is on the first line
            $output = str_replace("\nErrors parsing {$tmpfname}\n", "", $output);
            return str_replace("PHP Parse error:  s", "S", str_replace(" in $tmpfname", "", $output));
        }
    }
    return '';
}


// return SQL ready for use in special queries for linked element source values
// t1 is the table where the values are being gathered from
// t2 is the entry_owner_groups table
function prepareLinkedElementGroupFilter($sourceFid, $groupSelections, $useOnlyUsersGroups, $userMustBeInAllGroups, $useOnlyUsersEntries) {

    global $regcode, $xoopsUser, $xoopsDB;
    // determine the groups that we're dealing with...
    if($regcode) { // if we're dealing with a registration code, determine group membership based on the code
        $reggroupsq = q("SELECT reg_codes_groups FROM " . XOOPS_DB_PREFIX . "_reg_codes WHERE reg_codes_code=\"$regcode\"");
        $groups = explode("&8(%$", $reggroupsq[0]['reg_codes_groups']);
        if($groups[0] === "") { unset($groups); } // if a code has no groups associated with it, then kill the null value that will be in position 0 in the groups array.
        $groups[] = XOOPS_GROUP_USERS;
        $groups[] = XOOPS_GROUP_ANONYMOUS;
    } else {
        $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
    }

    $pgroups = array();
    if($groupSelections) {
        $scopegroups = explode(",",$groupSelections);
        if(!in_array("all", $scopegroups)) {
            if($useOnlyUsersGroups) { // limit by user's groups
                foreach($groups as $gid) { // want to loop so we can get rid of reg users group simply
                    if($gid == XOOPS_GROUP_USERS) { continue; }
                    if(in_array($gid, $scopegroups)) {
                        $pgroups[] = $gid;
                    }
                }
            } else { // just use scopegroups
                $pgroups = $scopegroups;
            }
        } else {
            if($useOnlyUsersGroups) { // all groups selected, but limiting by user's groups is turned on
                foreach($groups as $gid) { // want to loop so we can get rid of reg users group simply
                    if($gid == XOOPS_GROUP_USERS) { continue; }
                    $pgroups[] = $gid;
                }
            } else { // all groups should be used
                unset($pgroups);
                $allgroupsq = q("SELECT groupid FROM " . $xoopsDB->prefix("groups"));
                foreach($allgroupsq as $thisgid) {
                    $pgroups[] = $thisgid['groupid'];
                }
            }
        }
    }

    array_unique($pgroups); // remove duplicate groups from the list

    if($useOnlyUsersEntries) {
        $pgroupsfilter = " t1.creation_uid = ".($xoopsUser ? $xoopsUser->getVar('uid') : 0);
    } elseif($userMustBeInAllGroups AND count((array) $pgroups) > 0) {  // means we must match all the current user's groups with the entry's groups, so we setup a series of exists clauses
        $pgroupsfilter = " (";
        $start = true;
        foreach($pgroups as $thisPgroup) {
            if(!$start) { $pgroupsfilter .= " AND "; }
            $pgroupsfilter .= "EXISTS(SELECT 1 FROM ".$xoopsDB->prefix("formulize_entry_owner_groups")." AS t3 WHERE t3.groupid=$thisPgroup AND t3.fid=$sourceFid AND t3.entry_id=t1.entry_id)";
            $start = false;
        }
        $pgroupsfilter .= ")";
    } elseif(count((array) $pgroups) > 0) {
        $pgroupsfilter = " t2.groupid IN (".formulize_db_escape(implode(",",$pgroups)).") AND t2.fid=$sourceFid";
    } elseif($groupSelections AND !in_array("all", $scopegroups) AND count((array) $pgroups)==0) {
        $pgroupsfilter = 'FALSE';
    } else {
        $pgroupsfilter = "";
    }
    return $pgroupsfilter;
}

// include any source entry ids that are selected currently, so current selections are not lost!!
// setup an OR condition as an alternative to the filter we've determined, just in case the selected value is outside what the filter returns
// t1 is the form we're gathering entries from
function prepareLinkedElementSafetyNets($sourceEntryIds) {
    if(count((array) $sourceEntryIds)>0 AND $sourceEntryIds[0]) {
        $sourceEntrySafetyNetStart = "( ";
        $sourceEntrySafetyNetEnd = " ) OR t1.entry_id IN (".implode(",",$sourceEntryIds).") ";
    } else {
        $sourceEntrySafetyNetStart = "";
        $sourceEntrySafetyNetEnd = "";
    }
    return array($sourceEntrySafetyNetStart, $sourceEntrySafetyNetEnd);
}

// t1 is the form we're gathering entries from
function prepareLinkedElementExtraClause($pgroupsfilter, $parentFormFrom, $sourceEntrySafetyNetStart) {
    $extra_clause = "";
    if ($pgroupsfilter) {
        if(strstr($pgroupsfilter,"t2")) {
            global $xoopsDB;
            $extra_clause = " INNER JOIN ".$xoopsDB->prefix("formulize_entry_owner_groups")." AS t2 ON t1.entry_id = t2.entry_id ";
        }
        $extra_clause .= " $parentFormFrom WHERE $sourceEntrySafetyNetStart $pgroupsfilter ";
    } else {
        $extra_clause = " $parentFormFrom WHERE $sourceEntrySafetyNetStart t1.entry_id>0 ";
    }
    return $extra_clause;
}

// this function takes a filter term passed in, and converts it to the actual value from GET or POST
function convertDynamicFilterTerms($term) {

    // check for starting and ending ! ! and put them back at the end if necessary
    $needPreserveHiddenMarkers = false;
    if(substr($term, 0, 1) == "!" AND substr($term, -1) == "!") {
        $needPreserveHiddenMarkers = true;
        $term = substr($term, 1, -1);
    }

    $operatorToPutBack = "";
    if(substr($term, 0, 1) == '=') {
        $operatorToPutBack = '=';
    }
    if(substr($term, 0, 1) == '>') {
        $operatorToPutBack = '>';
    }
    if(substr($term, 0, 1) == '<') {
        $operatorToPutBack = '<';
    }
    if(substr($term, 0, 1) == '!') {
        $operatorToPutBack = '!';
    }
    if(substr($term, 0, 2) == '!=') {
        $operatorToPutBack = '!=';
    }
    if(substr($term, 0, 2) == '<=') {
        $operatorToPutBack = '<=';
    }
    if(substr($term, 0, 2) == '>=') {
        $operatorToPutBack = '>=';
    }

    $valueToCheck = str_replace($operatorToPutBack, '', $term);

    if(substr($valueToCheck, 0, 1) == "{" AND substr($valueToCheck, -1) == "}") {
        $searchgetkey = substr($valueToCheck, 1, -1);
        if(isset($_POST[$searchgetkey]) OR isset($_GET[$searchgetkey])) {
            $term = isset($_POST[$searchgetkey]) ? htmlspecialchars(strip_tags(trim($_POST[$searchgetkey])), ENT_QUOTES) : "";
            $term = ($term==="" AND isset($_GET[$searchgetkey])) ? htmlspecialchars(strip_tags(trim($_GET[$searchgetkey])), ENT_QUOTES) : $term;
            if($term==="") {
                $term = "";
            }
            $term = $operatorToPutBack.$term;
        }
    }

    if($needPreserveHiddenMarkers) {
        $term = '!'.$term.'!';
    }
    return $term;
}

// this function takes an array of element handle=>search term pairs, and converts them into a filter string valid for using in a getData call
function formulize_parseSearchesIntoFilter($searches) {

    // build the filter out of the searches array
	$start = 1;
	$filter = "";
	$ORstart = 1;
	$ORfilter = "";
	$individualORSearches = array();
    $element_handler = xoops_getmodulehandler('elements','formulize');
	global $xoopsUser, $xoopsConfig;
	foreach($searches as $key => $master_one_search) { // $key is the element handle

		// convert "between 2001-01-01 and 2002-02-02" to a normal date filter with two dates
		$count = preg_match("/^[bB][eE][tT][wW][eE][eE][nN] ([\d]{1,4}[-][\d]{1,2}[-][\d]{1,4}) [aA][nN][dD] ([\d]{1,4}[-][\d]{1,2}[-][\d]{1,4})\$/", $master_one_search, $matches);
		if ($count > 0) {
			$master_one_search = ">={$matches[1]}//<={$matches[2]}";
		}

        $master_one_search = convertDynamicFilterTerms($master_one_search);
        if($master_one_search === "") { continue; }

		// split search based on new split string
		$intermediateArray = explode("//", trim($master_one_search, "//")); // ignore trailing // because that will just cause an unnecessary blank search

		$searchArray = array();

		foreach($intermediateArray as $one_search) {
			// if $one_search contains both OR and AND, just add it as-is; we don't support this kind of nesting
			if (strpos($one_search, " OR ") !== FALSE AND strpos($one_search, " AND ") !== FALSE) {
				$searchArray[] = $one_search;
			}
			// split on OR and add all split results, prepended with OR
			else if (strpos($one_search, " OR ") !== FALSE) {
				foreach(explode(" OR ", $one_search) as $or_term) {
						$searchArray[] = "OR" . $or_term;
				}
			}
			// split on AND and add all split results
			else if (strpos($one_search, " AND ") !== FALSE) {
				foreach(explode(" AND ", $one_search) as $and_term) {
					$searchArray[] = $and_term;
				}
			}
			// otherwise just add to the array
			else {
				$searchArray[] = $one_search;
			}
		}

		foreach($searchArray as $one_search) {
            // used for trapping the {BLANK} keywords into their own space so they don't interfere with each other, or other filters
            $addToItsOwnORFilter = false;
            $ownORFilterKey = "";

            $dataHandler = new formulizeDataHandler(false);
            $metadataFieldTypes = $dataHandler->metadataFieldTypes;

            if (isset($metadataFieldTypes[$key])){
                $ele_type = $metadataFieldTypes[$key];
            }
            else{
                $elementObject = $element_handler->get($key);
                if(!is_object($elementObject)) {
                    continue; // ignore references to non-elements (probably deleted columns in a saved view)
                }
                $ele_type = $elementObject->getVar('ele_type');
            }

		    // remove the qsf_ parts to make the quickfilter searches work
		    if(substr($one_search, 0, 4)=="qsf_") {
              $qsfparts = explode("_", $one_search);
			  $allowsMulti = false;
			  if($ele_type == "select") {
				$ele_value = $elementObject->getVar('ele_value');
				if($ele_value[1]) {
				  $allowsMulti = true;
				}
			  } elseif($ele_type == "checkbox") {
				$allowsMulti = true;
		      }
			  if($allowsMulti) {
				$one_search = $qsfparts[2]; // will default to using LIKE since there's no operator
			  } else {
                // if we've received the flag to not use the equals operator, remove the flag and don't use the operator
                $finalQSFParts2 = str_replace('NOQSFEQUALS','',$qsfparts[2]);
				$one_search = $finalQSFParts2 == $qsfparts[2] ? "=".$qsfparts[2] : $finalQSFParts2; // if no flag, two strings will be identical because nothing removed. simple, one speedy operation this way
			  }
		    }

			// strip out any starting and ending ! that indicate that the column should not be stripped
			if(substr($one_search, 0, 1) == "!" AND substr($one_search, -1) == "!") {
				$one_search = substr($one_search, 1, -1);
			}

            $one_search = convertDynamicFilterTerms($one_search); // probably don't need to do this again?? Except what we unpacked first time might have nested { } terms in it? If it did, we would need to do this, however rare that might be
            if($one_search === "") { continue; }

			// look for OR indicators...if all caps OR is at the front, then that means that this search is to put put into a separate set of OR filters that gets appended as a set to the main set of AND filters
		    $addToORFilter = false; // flag to indicate if we need to apply the current search term to a set of "OR'd" terms
			if(substr($one_search, 0, 2) == "OR" AND strlen($one_search) > 2) {
                if(substr($one_search, 2, 3)== "SET") {
                    $addToItsOwnORFilter = true;
                    $ownORFilterKey = substr($one_search, 2, 4);
                    $one_search = substr($one_search, 6);
                } else {
				$addToORFilter = true;
				$one_search = substr($one_search, 2);
			}
			}

			// look for operators
			$operators = array(0=>"=", 1=>">", 2=>"<", 3=>"!");
			$operator = "";
			if(in_array(substr($one_search, 0, 1), $operators)) {
				// operator found, check to see if it's <= or >= and set start point for term accordingly
				$startpoint = (substr($one_search, 0, 2) == ">=" OR substr($one_search, 0, 2) == "<=" OR substr($one_search, 0, 2) == "!=" OR substr($one_search, 0, 2) == "<>") ? 2 : 1;
				$operator = substr($one_search, 0, $startpoint);
        if($operator == "!") { $operator = "NOT LIKE"; }
				$one_search = substr($one_search, $startpoint);
			}

			// look for blank search terms and convert them to {BLANK} so they are handled properly
			if($one_search === "") {
				$one_search = "{BLANK}";
			}

			// look for { } and transform special terms into what they should be for the filter
			if(substr($one_search, 0, 1) == "{" AND substr($one_search, -1) == "}") {
				$searchgetkey = substr($one_search, 1, -1);

				if (substr($searchgetkey, 0, 5) == "TODAY") {
                    $number = substr($searchgetkey, 5); // note -- includes the +/- sign
                    $basetime = $number ? strtotime($number." day") : time();
                    $offset = formulize_getUserUTCOffsetSecs(timestamp: $basetime); // need to adjust for user time vs UTC, since time() is based on UTC
					$one_search = date("Y-m-d",($basetime+$offset));
				} elseif($searchgetkey == "USER") {
					if($xoopsUser) {
                        $one_search = htmlspecialchars_decode($xoopsUser->getVar('uname'), ENT_QUOTES);
						if(!$one_search) { $one_search = htmlspecialchars_decode($xoopsUser->getVar('login_name'), ENT_QUOTES); }
					} else {
						$one_search = 0;
					}
				} elseif($searchgetkey == "USERNAME") {
					if($xoopsUser) {
                        $one_search = htmlspecialchars_decode($xoopsUser->getVar('login_name'), ENT_QUOTES);
                    } else {
						$one_search = "";
					}
                } elseif($searchgetkey == "USER_ID") {
                    if($xoopsUser) {
                        $one_search = $xoopsUser->getVar('uid');
					} else {
						$one_search = "";
					}
				} elseif($searchgetkey == "BLANK") { // special case, we need to construct a special OR here that will look for "" OR IS NULL
				  if($operator == "!=" OR $operator == "NOT LIKE") {
				    $blankOp1 = "!=";
				    $blankOp2 = " IS NOT NULL ";
				  } else {
				    $addToItsOwnORFilter = $addToORFilter ? false : true; // if this is not going into an OR filter already because the user asked for it to, then let's
				    $blankOp1 = "=";
				    $blankOp2 = " IS NULL ";
				  }
				  $one_search = "/**/$blankOp1][$key/**//**/$blankOp2";
				  $operator = ""; // don't use an operator, we've specially constructed the one_search string to have all the info we need
				} elseif($searchgetkey == "PERGROUPFILTER") {
					$one_search = $searchgetkey;
					$operator = "";
				} elseif($searchgetkey) { // we were supposed to find something above, but did not, so there is a user defined search term, which has no value, ergo disregard this search term
					continue;
				} else {
					$one_search = "";
					$operator = "";
				}
			} else {
				// handle alterations to non { } search terms here...
				if ($ele_type == "date") {
                    $search_date = strtotime($one_search);
                    // only search on a valid date string (otherwise it will be converted to the unix epoch)
                    if (false === $search_date) {
                        continue;
                    }
				}
			}

			// do additional search for {USERNAME} or {USER} in case they are embedded in another string
			if($xoopsUser) {
                $one_search = str_replace("{USER}", htmlspecialchars_decode($xoopsUser->getVar('uname'), ENT_QUOTES), $one_search);
				$one_search = str_replace("{USERNAME}", htmlspecialchars_decode($xoopsUser->getVar('login_name'), ENT_QUOTES), $one_search);
                $one_search = str_replace("{USER_ID}", $xoopsUser->getVar('uid'), $one_search);
			}

            if(isset($GLOBALS['formulize_searchOperatorOverride'][$key])) {
                $operator = $GLOBALS['formulize_searchOperatorOverride'][$key];
            }
			if($operator) {
				$one_search = $one_search . "/**/" . $operator;
			}
			if($addToItsOwnORFilter) {
                if($ownORFilterKey) {
                    if(!isset($individualORSearches[$ownORFilterKey])) {
                        $individualORSearches[$ownORFilterKey] = $key ."/**/$one_search";
                    } else {
                        $individualORSearches[$ownORFilterKey] .= "][".$key ."/**/$one_search";
                    }
                } else {
				$individualORSearches[] = $key ."/**/$one_search";
                }
			} elseif($addToORFilter) {
				if(!$ORstart) { $ORfilter .= "]["; }
				$ORfilter .= $key . "/**/$one_search"; // . formulize_db_escape($one_search); // mysql_real_escape_string no longer necessary here since the extraction layer does the necessary dirty work for us
				$ORstart = 0;
			} else {
				if(!$start) { $filter .= "]["; }
				$filter .= $key . "/**/$one_search"; // . formulize_db_escape($one_search); // mysql_real_escape_string no longer necessary here since the extraction layer does the necessary dirty work for us
				$start = 0;
			}

		}
	}
	//print $filter;
	// if there's a set of options that have been OR'd, then we need to construction a more complex filter

	if($ORfilter OR count((array) $individualORSearches)>0) {
		$filterIndex = 0;
        if($filter) {
    		$arrayFilter[$filterIndex][0] = "and";
    		$arrayFilter[$filterIndex][1] = $filter;
        }
		if($ORfilter) {
			$filterIndex++;
			$arrayFilter[$filterIndex][0] = "or";
			$arrayFilter[$filterIndex][1] = $ORfilter;
		}
		if(count((array) $individualORSearches)>0) {
			foreach($individualORSearches as $thisORfilter) {
				$filterIndex++;
				$arrayFilter[$filterIndex][0] = "or";
				$arrayFilter[$filterIndex][1] = $thisORfilter;
			}
		}
		$filter = $arrayFilter;
	}

    return $filter;

}


function do_update_export($queryData, $frid, $fid) {
    // this is the old export code, which is used for 'update' mode
    $fdchoice = "update";

    $GLOBALS['formulize_doingExport'] = true;
    unset($queryData[0]); // get rid of the fid and userid lines
    unset($queryData[1]);
    $queryData = implode(" ", $queryData); // merge all remaining lines into one string to send to getData
    $data = getData($frid, $fid, $queryData);

    $cols = explode(",", $_GET['cols']);

    list($cols, $headers) = export_prepColumns($cols);

    $filename = prepExport($headers, $cols, $data, $fdchoice, "", false, $fid);

    $pathToFile = str_replace(XOOPS_URL,XOOPS_ROOT_PATH, $filename);

    if ($_GET['type'] == "update") {
        $fileForUser = str_replace(XOOPS_URL. SPREADSHEET_EXPORT_FOLDER, "", $filename);
    } else {
        $form_handler = xoops_getmodulehandler('forms','formulize');
        $formObject = $form_handler->get($fid);
        if (is_object($formObject)) {
            $formTitle = "'".str_replace(array(" ", "-", "/", "'", "`", "\\", ".", "?", ",", ")", "(", "[", "]"), "_", trans(undoAllHTMLChars($formObject->getVar('title'))))."'";
        } else {
            $formTitle = "a_form";
        }
        $fileForUser = _formulize_EXPORT_FILENAME_TEXT."_".$formTitle."_".date("M_j_Y_Hi").".csv";
    }

    header('Content-Description: File Transfer');
    header('Content-Type: text/csv; charset='._CHARSET);
    header('Content-Disposition: attachment; filename='.$fileForUser);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    if (strstr(strtolower(_CHARSET),'utf') AND $_POST['excel'] == 1) {
        echo "\xef\xbb\xbf"; // necessary to trigger certain versions of Excel to recognize the file as unicode
    }
    if (strstr(strtolower(_CHARSET),'utf-8') AND $_POST['excel'] != 1) {
        ob_start();
        readfile($pathToFile);
        $fileContents = ob_get_clean();
        header('Content-Length: '. filesize($pathToFile) * 2);
        // open office really wants it in UTF-16LE before it will actually trigger an automatic unicode opening?! -- this seems to cause problems on very large exports?
        print iconv("UTF-8","UTF-16LE//TRANSLIT", $fileContents);
    } else {
        header('Content-Length: '. filesize($pathToFile));
        readfile($pathToFile);
    }
}


function export_data($queryData, $frid, $fid, $groups, $columns, $include_metadata, $output_filename="") {

    global $xoopsDB;

    // generate the export filename, which the user will see
    $form_handler = xoops_getmodulehandler('forms','formulize');
    $formObject = $form_handler->get($fid);
    if (is_object($formObject)) {
        $formTitle = "'".str_replace(array(" ", "-", "/", "'", "`", "\\", ".", "?", ",", ")", "(", "[", "]"), "_", trans(undoAllHTMLChars($formObject->getVar('title'))))."'";
    } else {
        $formTitle = "a_form";
    }
    $export_filename = _formulize_EXPORT_FILENAME_TEXT."_".$formTitle."_".date("M_j_Y_Hi").".csv";

    $output_filename = $output_filename == 'USE_DEFAULT' ? $export_filename : $output_filename;

    if(!$output_filename) {

        icms::$logger->disableLogger();

        while(ob_get_level()) {
            ob_end_clean();
        }

        // output http headers
        header('Content-Description: File Transfer');
        header('Content-Type: text/csv; charset='._CHARSET);
        header('Content-Disposition: attachment; filename='.$export_filename);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

    }

    list($columns, $headers, $explodedColumns, $superHeaders, $handles) = export_prepColumns($columns,$include_metadata);

    // output export header
    $destination = $output_filename ? XOOPS_ROOT_PATH.'/modules/formulize/export/'.$output_filename : 'php://output'; // open a file handle to stdout if we're not making an actual file, because fputcsv() needs something to attach to
    $output_handle = fopen($destination, 'w');

    if (strstr(strtolower(_CHARSET),'utf') AND $_POST['excel'] == 1) {
        fwrite($output_handle, "\xef\xbb\xbf"); // necessary to trigger certain versions of Excel to recognize the file as unicode
    }

    if(count((array) $superHeaders)>0) {
        fputcsv($output_handle, $superHeaders);
    }
    fputcsv($output_handle, $headers);
    if(isset($_GET['showHandles'])) {
        fputcsv($output_handle, $handles);
    }

    // output export data
    $GLOBALS['formulize_doingExport'] = true;
    unset($queryData[0]); // get rid of the fid and userid lines
    unset($queryData[1]);

    $data_sql = implode(" ", $queryData); // merge all remaining lines into one string to send to getData
    if(substr($data_sql, 0, 12)=="USETABLEFORM") {
        $params = explode(" -- ", $data_sql);
        $sql = $params[1];
        $formname = $params[2];
        $fid = $params[3];

        $limitStart = 0;
        $limitSize = 1000;

        do {

            $querySql = $sql . " LIMIT $limitStart,$limitSize";

            $data = dataExtractionTableForm($querySql, $formname, $fid, false, false, false, false, false, false, false, $columns);
            foreach($data as $entry) {
                $row = array();
                foreach($columns as $column) {
                    $row[] = trans(html_entity_decode(displayTogether($entry, $column, ", "), ENT_QUOTES));
                }
                // output this row to the browser
                fputcsv($output_handle, $row);
            }
            $limitStart += $limitSize;
        } while ( is_array($data) and count((array) $data) > 0 );

    } else {

        if(isset($_GET['limitSize'])) { // if user set a specific limit, use that instead of gathering everything in 1000 record chunks...happens with makecsv.php and maybe other times
            $limitStart = isset($_GET['limitStart']) ? intval($_GET['limitStart']) : 0;
            $limitSize = (isset($_GET['limitSize']) AND $limitStart !== "") ? intval($_GET['limitSize']) : "";
        } else {
        $limitStart = 0;
            $limitSize = 1000;    // export in batches of 1000 records at a time
        }

        if(isset($_POST['nullOption']) AND $_POST['nullOption'] !== "") {
            $_POST['nullOption'] = htmlspecialchars(strip_tags($_POST['nullOption']), ENT_QUOTES);
        } else {
            unset($_POST['nullOption']);
        }

        do {
            // load part of the data, since a very large dataset could exceed the PHP memory limit
            $GLOBALS['formulize_doNotCacheDataSet'] = true;
            $data = getData($frid, $fid, $data_sql, "AND", null, $limitStart, $limitSize);
            if (is_array($data)) {
                foreach ($data as $entry) {
                    $i = 0;
                    $row = array();
                    foreach ($columns as $column) {
                        global $xoopsUser;
                        switch ($column) {
                            case "entry_id":
                            $ids = internalRecordIds($entry, $fid);
                            $row[] = $ids[0];
                            break;

                            case "uid":
                            case "creation_uid":
                                if(!isset($GLOBALS['formulize_useForeignKeysInDataset']['creation_uid']) AND !isset($GLOBALS['formulize_useForeignKeysInDataset']['all'])) {
                                    $c_uid = display($entry, 'creation_uid');
                                    $c_name_q = q("SELECT name, uname FROM " . $xoopsDB->prefix("users") . " WHERE uid='$c_uid'");
                                    $row[] = (isset($c_name_q[0]['name']) AND $c_name_q[0]['name']) ? $c_name_q[0]['name'] : $c_name_q[0]['uname'];
                                } else {
                                    $row[] = display($entry, 'creation_uid');
                                }
                            break;

                            case "proxyid":
                            case "mod_uid":
                            $m_uid = display($entry, 'mod_uid');
                            if ($m_uid AND !isset($GLOBALS['formulize_useForeignKeysInDataset']['mod_uid']) AND !isset($GLOBALS['formulize_useForeignKeysInDataset']['all'])) {
                                $m_name_q = q("SELECT name, uname FROM " . $xoopsDB->prefix("users") . " WHERE uid='$m_uid'");
                                $row[] = (isset($m_name_q[0]['name']) AND $m_name_q[0]['name']) ? $m_name_q[0]['name'] : $m_name_q[0]['uname'];
                            } else {
                                $row[] = $m_uid;
                            }
                            break;

                            case "creation_date":
                            case "creation_datetime":
                            $row[] = display($entry, 'creation_datetime');
                            break;

                            case "mod_date":
                            case "mod_datetime":
                            $row[] = display($entry, 'mod_datetime');
                            break;

                            case "creator_email":
                                $row[] =  display($entry, 'creator_email');
                            break;

                            default:
                            // two possible situations
                            // 1. regular, all values in one column
                            // 2. multiple options, put each option in a column, and assign the value indicator defined for the element to the column(s) that have the value
                            if(isset($explodedColumns[$column])) {
                                $colValues = display($entry, $column);
                                $colValues = is_array($colValues) ? $colValues : array($colValues);
                                foreach($explodedColumns[$column] as $thisOption=>$indicators) {
                                    if(substr($thisOption, 0, 7) == "{OTHER|") {
                                        $diff = array_diff($colValues, array_keys((array)$explodedColumns[$column]));
                                        $diff = array_values($diff); // indexes will be preserved from source, we need to index from 0
                                        $row[] = (count((array) $diff) > 0 AND trim($diff[0]) !== "") ? $indicators['hasValue'] : $indicators['doesNotHaveValue'];
                                    } else {
                                        $row[] = in_array($thisOption, $colValues) ? $indicators['hasValue'] : $indicators['doesNotHaveValue'];
                                    }
                                }
                            } else {
                                // if the cell has an "OTHER" option and the value of this entry is not a standard option, then simply put Other, and put the value into the next column
                                $columnMetadata = formulize_getElementMetaData($column, true);
                                if(strstr($columnMetadata['ele_value'],"{OTHER|")) {
                                    $valueToCheck = display($entry, $column);
                                    if($valueToCheck == "" OR optionIsValidForElement($valueToCheck, $columnMetadata['ele_id'])) {
                                        $row[] = prepareCellForSpreadsheetExport($column, $entry);
                                        $row[] = "";
                                    } else {
                                        $row[] = _formulize_OPT_OTHERWORD;
                                        $row[] = prepareCellForSpreadsheetExport($column, $entry);
                                    }
                                } else {
                                    $row[] = prepareCellForSpreadsheetExport($column, $entry);
                                }
                            }
                        }
                        if(isset($_POST['nullOption']) AND $row[$i] === "") {
                            $row[$i] = $_POST['nullOption'];
                        }
                        $i++;
                    }
                    // output this row to the browser
                    fwrite($output_handle, implode(',',$row)."\n");
                }

                // get the next set of data
                set_time_limit(90);
                if(!isset($_GET['limitSize'])) { // if we don't have a set size from makecsv.php or other user-set value
                $limitStart += $limitSize;
            }
            }
        } while (!isset($_GET['limitSize']) AND is_array($data) and count((array) $data) > 0 );

    }

    fclose($output_handle);
}

function export_prepColumns($columns,$include_metadata=0) {

    // get a list of columns for export
    $headers = array();
    $handles = array();
    $explodedColumns = array();
    $superHeaders = array();
    $superHeaderAssigned = false;
    $metaDataHeaders = array(_formulize_ENTRY_ID, _formulize_DE_CALC_CREATOR, _formulize_DE_CALC_MODIFIER, _formulize_DE_CALC_CREATEDATE, _formulize_DE_CALC_MODDATE, _formulize_DE_CALC_CREATOR_EMAIL);
    $metaDataColsToAdd = array("entry_id","creation_uid","mod_uid","creation_datetime","mod_datetime","creator_email");
    foreach ($columns as $thiscol) {
        $handles[] = $thiscol;
        if ("creator_email" == $thiscol) {
            $headers[] = _formulize_DE_CALC_CREATOR_EMAIL;
            unset($metaDataColsToAdd[5]);
            unset($metaDataHeaders[5]);
        } elseif ("entry_id" == $thiscol) {
            $headers[] = _formulize_ENTRY_ID;
            unset($metaDataColsToAdd[0]);
            unset($metaDataHeaders[0]);
        } elseif ("creation_uid" == $thiscol OR "uid" == $thiscol) {
            $headers[] = _formulize_DE_CALC_CREATOR;
            unset($metaDataColsToAdd[1]);
            unset($metaDataHeaders[1]);
        } elseif ("proxyid" == $thiscol OR "mod_uid" == $thiscol) {
            $headers[] = _formulize_DE_CALC_MODIFIER;
            unset($metaDataColsToAdd[2]);
            unset($metaDataHeaders[2]);
        } elseif ("creation_date" == $thiscol OR "creation_datetime" == $thiscol) {
            $headers[] = _formulize_DE_CALC_CREATEDATE;
            unset($metaDataColsToAdd[3]);
            unset($metaDataHeaders[3]);
        } elseif ("mod_date"  == $thiscol OR "mod_datetime" == $thiscol) {
            $headers[] = _formulize_DE_CALC_MODDATE;
            unset($metaDataColsToAdd[4]);
            unset($metaDataHeaders[4]);
        } else {
            $colMeta = formulize_getElementMetaData($thiscol, true);
            $exportOptions = unserialize($colMeta['ele_exportoptions']);

            /*if($thiscol == 'caregivers_language_requirements') {
                $exportOptions = array(
                    'columns' => array(
                        'Standard Mandarin',
                        'Other Mandarin',
                        'English',
                        'Malay',
                        'Tamil',
                        'Vietnamese',
                        'Tagalog',
                        'Indonesian',
                        'Other',
                    ),
                    'indicators' => array(
                        'hasValue' => 1,
                        'doesNotHaveValue' => 0
                    )
                );
            }*/

            if(is_array($exportOptions) AND count((array) $exportOptions)>0) {
                $superHeaderAssigned = false;
                foreach($exportOptions['columns'] as $explodedColumnHeader) {
                    if($superHeaderAssigned) {
                        $superHeaders[] = "";
                    } else {
                        $superHeaders[] = $colMeta['ele_colhead'] ? trans($colMeta['ele_colhead']) : trans($colMeta['ele_caption']);
                        $superHeaderAssigned = true;
                    }
                    $header = substr($explodedColumnHeader, 0, 7) == "{OTHER|" ? _formulize_OPT_OTHER : $explodedColumnHeader;
                    $header = formulize_swapUIText($header, unserialize($colMeta['ele_uitext']));
                    $headers[] = $header;
                    $explodedColumns[$thiscol][$explodedColumnHeader] = $exportOptions['indicators'];
                }
            } else {
                $headers[] = $colMeta['ele_colhead'] ? trans($colMeta['ele_colhead']) : trans($colMeta['ele_caption']);
                $superHeaders[] = "";
                // append additional column for "OTHER"
                if(strstr($colMeta['ele_value'],"{OTHER|")) {
                    $headers[] = "Other Value";
                    $superHeaders[] = "";
                    $handles[] = "";
                }
            }
        }
    }

    if(!$superHeaderAssigned) {
        $superHeaders = array();
    }

    if ($include_metadata AND count((array) $metaDataColsToAdd)>0) {
        // include metadata columns if the user requested them
        $columns = array_merge($metaDataColsToAdd, $columns);
        $headers = array_merge($metaDataHeaders,$headers);
    }
    return array($columns,$headers,$explodedColumns,$superHeaders,$handles);
}



// this function figures out certain default values for elements in a given entry in a form, and writes them to that entry
// used for setting values that are supposed to exist by default in newly created subform entries
function writeEntryDefaults($target_fid,$target_entry,$excludeHandles = array()) {

  $defaultValueMap = getEntryDefaults($target_fid,$target_entry);
  $defaultElementHandles = convertElementIdsToElementHandles(array_keys($defaultValueMap));

  $i = 0;
  foreach($defaultValueMap as $elementId=>$defaultTextToWrite) {
    if($defaultTextToWrite AND !in_array($defaultElementHandles[$i],$excludeHandles)) {
      writeElementValue($target_fid, $elementId, $target_entry, $defaultTextToWrite, "replace", null, true); // last true means we are passing in linked value foreign keys, no need to sort them out inside the function
    }
    $i++;
  }

}

// THIS NEEDS TO BE ADDED AS A METHOD IN THE CUSTOM ELEMENTS CLASS!
// returns an array of element id/default value pairs
// valid for a specific entry
function getEntryDefaults($target_fid,$target_entry) {

  static $cachedDefaults = array();

  if(isset($cachedDefaults[$target_fid][$target_entry])) {
    return $cachedDefaults[$target_fid][$target_entry];
  }

  $defaultValueMap = array();

  $element_handler = xoops_getmodulehandler('elements', 'formulize');

  $criteria = new CriteriaCompo();
  $criteria->add(new Criteria('ele_type', 'text'), 'OR');
  $criteria->add(new Criteria('ele_type', 'textarea'), 'OR');
  $criteria->add(new Criteria('ele_type', 'date'), 'OR');
  $criteria->add(new Criteria('ele_type', 'radio'), 'OR');
  $criteria->add(new Criteria('ele_type', 'checkbox'), 'OR');
  $criteria->add(new Criteria('ele_type', 'yn'), 'OR');
  $criteria->add(new Criteria('ele_type', 'select'), 'OR');
  $elementsForDefaults = $element_handler->getObjects($criteria,$target_fid); // get all the text or textarea elements in the form

  foreach($elementsForDefaults as $thisDefaultEle) {
    // need to write in any default values for any text boxes or text areas that are in the subform.  Perhaps other elements could be included too, but that would take too much work right now. (March 9 2009)
    $defaultTextToWrite = "";
    $ele_value_for_default = $thisDefaultEle->getVar('ele_value');
    switch($thisDefaultEle->getVar('ele_type')) {
      case "text":
        $defaultTextToWrite = getTextboxDefault($ele_value_for_default[2], $target_fid, $target_entry, $ele_value_for_default[11]); // position 2 is default value for text boxes
        break;
      case "textarea":
        $defaultTextToWrite = getTextboxDefault($ele_value_for_default[0], $target_fid, $target_entry); // position 0 is default value for text boxes
        break;
      case "date":
        $defaultTextToWrite = getDateElementDefault($ele_value_for_default[0], $target_entry);
        if (false !== $defaultTextToWrite) {
          $defaultTextToWrite = is_numeric($defaultTextToWrite) ? date("Y-m-d", $defaultTextToWrite) : $defaultTextToWrite;
        }
        break;
      case "radio":
        $thisDefaultEleValue = $thisDefaultEle->getVar('ele_value');
        $defaultTextToWrite = array_search(1, $thisDefaultEleValue);
        break;
      case "yn":
        $thisDefaultEleValue = $thisDefaultEle->getVar('ele_value');
        if($thisDefaultEleValue["_YES"] == 1) {
            $defaultTextToWrite = 1;
        } elseif($thisDefaultEleValue["_NO"] == 1) {
            $defaultTextToWrite = 2;
        }
        break;
      case "select":
      case "checkbox":
        $thisDefaultEleValue = $thisDefaultEle->getVar('ele_value');
        if($thisDefaultEle->isLinked AND !$thisDefaultEleValue['snapshot'])  {
            // default will be a foreign key or keys
            if(is_array($thisDefaultEleValue[13]) AND $thisDefaultEleValue[13][0] != "") {
                $defaultTextToWrite = $thisDefaultEleValue[1] ? $thisDefaultEleValue[13] : $thisDefaultEleValue[13][0]; // if not multiple selection, then use first (and only?) specified default value
            } else {
                $defaultTextToWrite = $thisDefaultEleValue[13] ? $thisDefaultEleValue[13] : null;
            }
            $defaultTextToWrite = (is_array($defaultTextToWrite) AND count($defaultTextToWrite) > 0) ? ','.implode(',',$defaultTextToWrite).',' : $defaultTextToWrite;
        } elseif($thisDefaultEle->isLinked) {
            // default is the literal value from the source, optionally with separator if multi
            $linkMeta = explode('#*=:*', $thisDefaultEleValue[2]);
            $linkFormId = $linkMeta[0];
            $linkElementId = $linkMeta[1];
            $data_handler = new formulizeDataHandler($linkFormId);
            $thisDefaultEleValue[13] = is_array($thisDefaultEleValue[13]) ? $thisDefaultEleValue[13] : array($thisDefaultEleValue[13]);
            $defaultTextToWrite = "";
            foreach($thisDefaultEleValue[13] as $thisDefaultValue) {
                $defaultTextToWrite .= strlen($defaultTextToWrite) > 0 ? '*=+*:' : '';
                $thisDefaultValue = $data_handler->getElementValueInEntry($thisDefaultValue,$linkElementId);
                $defaultTextToWrite .= $thisDefaultValue;
            }
        } else {
            // default is the literal text from the options list, optionally with separator if multi
            $defaultTextToWrite = "";
            foreach($thisDefaultEleValue[2] as $thisOption=>$isDefault) {
                if($isDefault) {
                    $defaultTextToWrite .= strlen($defaultTextToWrite) > 0 ? '*=+*:' : '';
                    $defaultTextToWrite .= $thisOption;
                }
            }
        }
    }
    if($defaultTextToWrite === "" OR $defaultTextToWrite === false OR $defaultTextToWrite === null) { continue; }
    $defaultValueMap[$thisDefaultEle->getVar('ele_id')] = $defaultTextToWrite;
  }
  $cachedDefaults[$target_fid][$target_entry] = $defaultValueMap;
  return $defaultValueMap;
}

// this function figures out if there is a viewentryscreen that we should be showing based on the current state
function determineViewEntryScreen($screen, $fid) {
    if($screen AND is_a($screen, 'formulizeListOfEntriesScreen')) {
        $screen_handler = xoops_getmodulehandler('screen', 'formulize');
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        if($_POST['overridescreen'] AND is_numeric($_POST['overridescreen'])) {
            return intval($_POST['overridescreen']);
        } elseif($screen AND $screen->getVar('viewentryscreen') AND $screen->getVar('viewentryscreen') != 'none') {
            return intval($screen->getVar('viewentryscreen'));
        } else {
            $form_handler = xoops_getmodulehandler('forms', 'formulize');
            $formObject = $form_handler->get($fid);
            return $formObject->defaultform;
        }
    }
    if($screen AND is_a($screen, 'formulizeTemplateScreen')) {
        if(isset($_POST['formulize_renderedEntryScreen']) AND is_numeric($_POST['formulize_renderedEntryScreen'])) {
            return intval($_POST['formulize_renderedEntryScreen']);
        } elseif($_POST['overridescreen'] AND is_numeric($_POST['overridescreen'])) {
            return intval($_POST['overridescreen']);
        } else {
            $form_handler = xoops_getmodulehandler('forms', 'formulize');
            $formObject = $form_handler->get($fid);
            return $formObject->defaultform;
        }
    }
    return false;
}

function checkForChrome() {

return "
function setAutocompleteFalse() {
    jQuery('.formulize_autocomplete').attr('autocomplete','chrome-is-the-new-ie');
}

function checkForChrome() {
    // Thanks to https://stackoverflow.com/questions/4565112/javascript-how-to-find-out-if-the-user-browser-is-chrome/13348618#13348618
    // See also https://bugs.chromium.org/p/chromium/issues/detail?id=468153#c164
    // please note,
    // that IE11 now returns undefined again for window.chrome
    // and new Opera 30 outputs true for window.chrome
    // but needs to check if window.opr is not undefined
    // and new IE Edge outputs to true now for window.chrome
    // and if not iOS Chrome check
    // so use the below updated condition
    var isChromium = window.chrome;
    var winNav = window.navigator;
    var vendorName = winNav.vendor;
    var isOpera = typeof window.opr !== 'undefined';
    var isIEedge = winNav.userAgent.indexOf('Edge') > -1;
    var isIOSChrome = winNav.userAgent.match('CriOS');

    if (isIOSChrome) {
       // is Google Chrome on IOS
       setAutocompleteFalse();
    } else if(
      isChromium !== null &&
      typeof isChromium !== 'undefined' &&
      vendorName === 'Google Inc.' &&
      isOpera === false &&
      isIEedge === false
    ) {
       // is Google Chrome
       setAutocompleteFalse();
    } else {
       // not Google Chrome
    }
}";

}

// THANKS TO https://stackoverflow.com/questions/2050859/copy-entire-contents-of-a-directory-to-another-using-php
// AND gimmicklessgpt at gmail dot com found at https://www.php.net/manual/en/function.copy.php#91010
function recurse_copy($src,$dst) {
    $dir = opendir($src);
    if(!file_exists($dst)) { @mkdir($dst); }
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                recurse_copy($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// element template is copied to elementtemplate1 and elementtemplate2 on so multipage templates are up to standard
function updateMultipageTemplates($screen) {
    $path = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$screen->getVar('theme')."/".$screen->getVar('sid')."/";
    $fileMappings = array(
        'elementtemplate'=>array(
            'elementtemplate1',
            'elementtemplate2'
        )
    );
    foreach($fileMappings as $oldFile=>$newFiles) {
        if(file_exists($path.$oldFile.'.php')) {
            foreach($newFiles as $newFile) {
                if(!file_exists($path.$newFile.'.php')) {
                    if(!copy($path.$oldFile.'.php', $path.$newFile.'.php')) {
                        exit("Could not copy $oldFile to $newFile for screen ".$screen->getVar('sid').". Please notify the webmaster or contact info@formulize.org for assistance.");
                    }
                }
            }
            unlink($path.$oldFile.'.php');
        }
    }
}

function userHasMobileClient() {
    $useragent=$_SERVER['HTTP_USER_AGENT'];
    if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))) {
        return true;
    }
    return false;
}

// this function reads the go_back values from POST and sets up parent entries in POST, which are used as a flag in key situations.
// the Go Back form, setup with the legacy form screen submit buttons, normally would include the parent entries
// however they are missing when a multipage form does a Go Back operation
// So the list screens check if they need to do this, and call this function if so
// Multipage screens check for this too, and call this function
// Essentially, this is a replacement for the Go Back form that's part of legacy form screens, which isn't really used anymore
// This function returns the active entry that should be displayed
function setupParentFormValuesInPostAndReturnEntryId() {
    $go_back_entry = strstr($_POST['go_back_entry'], ',') ? explode(',',$_POST['go_back_entry']) : array($_POST['go_back_entry']);
    $lastKey = count((array) $go_back_entry)-1;
    $_POST['parent_entry'] = $_POST['go_back_entry'];
    $_POST['parent_form'] = $_POST['go_back_form'];
    $_POST['parent_page'] = $_POST['go_back_page'];
    $_POST['parent_subformElementId'] = $_POST['go_back_subformElementId'];
    unset($_POST['go_back_form']);
    unset($_POST['go_back_entry']);
    unset($_POST['go_back_page']);
    unset($_POST['goto_sfid']);
    unset($_POST['sub_fid']);
    return $go_back_entry[$lastKey];
}

// this function takes a form id number and returns an array of the records in the DB where entries were found that did not have ownership info
// it repairs the ownership info of those entries, based on the current group memberships of the creation uid of the entries
function repairEOGTable($fid) {
    //check to see if there are entries in the form which
    //do not appear in the entry_owner_groups table. If so, it finds the
    // owner/creator of the entry and calls setEntryOwnerGroups() which inserts the
    //first, get the form ids and handles.
    if($fid = intval($fid)) {
        global $xoopsDB;
        $form_handler = xoops_getmodulehandler('forms','formulize');
        $formObject = $form_handler->get($fid);
        $form_handle = $formObject->getVar('form_handle');
        $missingEntries=q("SELECT main.entry_id,main.creation_uid From " . $xoopsDB->prefix("formulize_".$form_handle) . " as main WHERE NOT EXISTS(
        SELECT 1 FROM " . $xoopsDB->prefix("formulize_entry_owner_groups") . " as eog WHERE eog.fid=$fid and eog.entry_id=main.entry_id )");
        //now we got the missing entries in the form and the users who created them.
        $data_handler = new formulizeDataHandler($fid);
        foreach ($missingEntries as $entry){
            if (!$groupResult = $data_handler->setEntryOwnerGroups($entry['creation_uid'],$entry['entry_id'],true)) {
                print "ERROR: failed to write the entry ownership information to the database.<br>";
            }
        }
        return $missingEntries;
    } else {
        error_log('Formulize error: invalid fid passed to repairEOGTable');
    }
}

// user offset from server tz
// assume timestamp is based on server timezone!
// returns seconds
function formulize_getUserServerOffsetSecs($userObject=null, $timestamp=null) {
	// checks if the user's timezone and/or server timezone were in daylight savings at the given $timestamp (or current time) and adjusts offset accordingly
	global $xoopsConfig, $xoopsUser;
    $userObject = is_object($userObject) ? $userObject : $xoopsUser;
    $timestamp = $timestamp ? $timestamp : time();
	$serverTimeZone = $xoopsConfig['server_TZ'];
	$userTimeZone = $userObject ? $userObject->getVar('timezone_offset') : $serverTimeZone;
	$tzDiff = $userTimeZone - $serverTimeZone;
    $daylightSavingsAdjustment = getDaylightSavingsAdjustment($userTimeZone, $serverTimeZone, $timestamp);
    $tzDiff = $tzDiff + $daylightSavingsAdjustment;
    return $tzDiff * 3600;
}

// get user offset from UTC
// returns seconds
function formulize_getUserUTCOffsetSecs($userObject=null, $timestamp=null) {
    global $xoopsConfig, $xoopsUser;
    $userObject = is_object($userObject) ? $userObject : $xoopsUser;
    $timestamp = $timestamp ? $timestamp : time();
	$serverTimeZone = $xoopsConfig['server_TZ'];
	$userTimeZone = $userObject ? $userObject->getVar('timezone_offset') : $serverTimeZone;
    $userTimeZone = $userTimeZone + getDaylightSavingsAdjustment($userTimeZone, 0, $timestamp);
    return $userTimeZone * 3600;
}

// $userTimeZone and $compareTimeZone are numbers for the base offset (ie: when standard time is in effect)
// timestamp is a timestamp from the **$compareTimeZone** timezone, that we are using the determine if daylight savings is in effect
// This will necessarily be off by 1 hour when we're using the old basic tz numbers in XOOPS! (because they're standard time only)
// since the window for an error is really small and only at the moment the time changes, that's acceptable? Seems to be the best we can do without overhauling the entire timezone system :(
// we are seriously hampered by the fact that old XOOPS only uses a number for the timezone offset, not the actual timezone. Numbers to not equal timezones!
// if timezones are the same, no adjustment
// if timezones are both in daylight savings at a given time, no adjustment
// if timezones are neither in daylight savings at a given time, no adjustment
// if timezones are one in daylight savings and one not in daylight savings, calculate adjustment
// returns difference in hours
function getDaylightSavingsAdjustment($userTimeZone, $compareTimeZone, $timestamp) {

    // timezone name and number equivalents. crude!!
    // could/should be expanded (or better yet, proper timezones recorded for users!!)
    // XOOPS does not support two digit decimals for timezones (yet, we could add it)
    // In Australia, different timezones with the same base offset, have different daylight savings rules :(
    $tzNames = array(
        '-8'=>'PST8PDT',
        '-7'=>'MST7MDT',
        '-6'=>'CST6CDT',
        '-5'=>'EST5EDT',
        '-4'=>'Canada/Atlantic',
        '-3.5'=>'Canada/Newfoundland',
        '+0'=>'UTC',
        '+8'=>'Australia/Perth',
        '+8.75'=>'Australia/Eucla',
        '+9.5'=>'Australia/Adelaide',
        '+10'=>'Australia/Sydney'
    );

    // need plus or minus on the timezone number, even zero
    $userTimeZone = floatval($userTimeZone) >= 0 ? strval("+".floatval($userTimeZone)) : strval(floatval($userTimeZone));
    $compareTimeZone = floatval($compareTimeZone) >= 0 ? strval("+".floatval($compareTimeZone)) : strval(floatval($compareTimeZone));

    $adjustment = 0;
    if($userTimeZone != $compareTimeZone) {
        $timestamp = '@'.strtotime(date('Y-m-d H:i:s', $timestamp).' '.$compareTimeZone); // need to construct new timestamp with the xoops tz offset included, and @ sign in front so PHP dateTime will understand it
        $dt = new DateTime($timestamp);
        $dt->setTimezone(new DateTimeZone($tzNames[$compareTimeZone]));
        $compareDST = $dt->format('I');
        $dt->setTimezone(new DateTimeZone($tzNames[$userTimeZone]));
        $userDST = $dt->format('I');
        if($compareDST AND !$userDST) {
            $adjustment = -1;
        } elseif(!$compareDST AND $userDST) {
            $adjustment = +1;
        }
    }
    return $adjustment;

}
