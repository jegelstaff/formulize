<?php
// $Id: userinfo.php,v 1.20 2005/06/26 15:38:21 mithyt2 Exp $
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //

$xoopsOption['pagetype'] = 'user';
include 'mainfile.php';
include_once XOOPS_ROOT_PATH.'/class/module.textsanitizer.php';
$module_handler =& xoops_gethandler('module');
$config_handler =& xoops_gethandler('config');
$regcodesModule =& $module_handler->getByDirname("reg_codes");
$regcodesConfig =& $config_handler->getConfigsByCat(0, $regcodesModule->getVar('mid'));
if(!$regcodesConfig['anons_view_profiles']) {
	$xoopsUser or redirect_header('index.php', 3, _NOPERM); // disallow anonymous users from viewing this page
}
include_once XOOPS_ROOT_PATH . '/modules/system/constants.php';

$uid = intval($_GET['uid']);
if ($uid <= 0) {
    redirect_header('index.php', 3, _US_SELECTNG);
    exit();
}

// ADDED BY FREEFORM SOLUTIONS AUGUST 20 2006
// recognize the limit_by_groups config option
$member_handler =& xoops_gethandler('member');
if($regcodesConfig['limit_by_groups']) {
	global $xoopsUser;
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS); 
	$isWebmaster = in_array(XOOPS_GROUP_ADMIN, $groups) ? true : false; 
	if(!$isWebmaster) {
		$allowed_users = array();
		foreach($groups as $group_id) {
			if($group_id == XOOPS_GROUP_USERS) { continue; } // exclude registered users group which should contain everybody
			$allowed_users_temp = $member_handler->getUsersByGroup($group_id);
			$allowed_users = array_merge($allowed_users, $allowed_users_temp);
			unset($allowed_users_temp);
		}
		if(!in_array($uid, $allowed_users)) {
			redirect_header('index.php', 3, _NOPERM); // disallow users from viewing profile info for users not in their own group(s)
		}
	}
} // END OF ADDED CODE BLOCK

$myts =& MyTextSanitizer::getInstance();
$gperm_handler = & xoops_gethandler( 'groupperm' );
$groups = is_object($xoopsUser) ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);

$isAdmin = $gperm_handler->checkRight( 'system_admin', XOOPS_SYSTEM_USER, $groups);         // isadmin is true if user has 'edit users' admin rights

if (is_object($xoopsUser)) {
    if ($uid == $xoopsUser->getVar('uid')) {
//        $config_handler =& xoops_gethandler('config'); // commented May 26 2006 since it is used above now (jwe - Freeform Solutions)
        $xoopsConfigUser =& $config_handler->getConfigsByCat(XOOPS_CONF_USER);
        $xoopsOption['template_main'] = 'system_userinfo.html';
        include XOOPS_ROOT_PATH.'/header.php';
        $xoopsTpl->assign('user_ownpage', true);
        $xoopsTpl->assign('lang_editprofile', _US_EDITPROFILE);
        $xoopsTpl->assign('lang_avatar', _US_AVATAR);
        $xoopsTpl->assign('lang_inbox', _US_INBOX);
        $xoopsTpl->assign('lang_logout', _US_LOGOUT);
        if ($xoopsConfigUser['self_delete'] == 1) {
            $xoopsTpl->assign('user_candelete', true);
            $xoopsTpl->assign('lang_deleteaccount', _US_DELACCOUNT);
        } else {
            $xoopsTpl->assign('user_candelete', false);
        }
        $thisUser =& $xoopsUser;
    } else {
        $member_handler =& xoops_gethandler('member');
        $thisUser =& $member_handler->getUser($uid);
        if (!is_object($thisUser) || !$thisUser->isActive() ) {
            redirect_header("index.php",3,_US_SELECTNG);
            exit();
        }
        $xoopsOption['template_main'] = 'system_userinfo.html';
        include XOOPS_ROOT_PATH.'/header.php';
        $xoopsTpl->assign('user_ownpage', false);
    }
} else {
    $member_handler =& xoops_gethandler('member');
    $thisUser =& $member_handler->getUser($uid);
    if (!is_object($thisUser) || !$thisUser->isActive()) {
        redirect_header("index.php",3,_US_SELECTNG);
        exit();
    }
    $xoopsOption['template_main'] = 'system_userinfo.html';
    include(XOOPS_ROOT_PATH.'/header.php');
    $xoopsTpl->assign('user_ownpage', false);
}

// *********************************
// ADDED BY FREEFORM SOLUTIONS -- JANUARY 3 2006
// *********************************

	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
	// get the form id and id_req of the user's entry
	global $xoopsDB, $xoopsUser;
	$formulizeModule =& $module_handler->getByDirname("formulize");
	$formulizeConfig =& $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
	$fid = $formulizeConfig['profileForm'];
	$mid = getFormulizeModId();
	$ownerObj = $member_handler->getUser($uid);
	$owner_groups = $ownerObj->getGroups(); 
	$singleInfo = getSingle($fid, $uid, $owner_groups, $member_handler, $gperm_handler, $mid);
	$id_req = $singleInfo['entry'];
	$upformq = "SELECT desc_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form='$fid' LIMIT 0,1";
	$upformres = $xoopsDB->query($upformq);
	if($upformarray = $xoopsDB->fetchArray($upformres)) {
		$desc_form = $upformarray['desc_form'];
      	// query the form for its data
      	$data = getData("", $fid, $id_req);
				// include only elements that are visible to the user's groups in the DB query below
      	$start = 1;
      	foreach($owner_groups as $thisgroup) {
      		if($start) {
      			$groups_query = "ele_display LIKE '%,$thisgroup,%'";
      			$start = 0;
      		} else {
      			$groups_query .= " OR ele_display LIKE '%,$thisgroup,%'";
      		}
      	}
      	// collect the element id numbers for use in a DB query, and apply the groups filter to each
      	$start = 1;
      	foreach($data[0][$desc_form][$id_req] as $ele_id=>$values) {
      		if($start) {
      			$ele_id_query = "(ele_id='$ele_id' AND (ele_display=1 OR ($groups_query)))";
      			$start = 0;
      		} else {
      			$ele_id_query .= " OR (ele_id='$ele_id' AND (ele_display=1 OR ($groups_query)))";
      		}
      	}
		// awareness of private flag added July 15 2006
		$pq = "";
		$currentUserID = is_object($xoopsUser) ? $xoopsUser->getVar('uid') : 0;
		$pq = (!$view_private_elements = $gperm_handler->checkRight("view_private_elements", $fid, $groups, $formulizeModule->getVar('mid')) AND $currentUserID != $uid) ? "AND ele_private=0" : "";
		if($pq == "" AND $currentUserID != $uid) { // if this is someone else's entry, and they do have view_private_elements permission, then check to see if they have permission to view this entry in this form before actually showing the private data
			include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
			$pq = security_check($fid, $id_req, $currentUserID, $uid, $groups, $formulizeModule->getVar('mid'), $gperm_handler, $owner_groups) ? "" : "AND ele_private=0";
		}
	     	// get the captions for the elements that are visible to the user's groups
      	$captionq = "SELECT ele_caption, ele_id, ele_display FROM " . $xoopsDB->prefix("formulize") . " WHERE ($ele_id_query) $pq AND ele_type <> 'ib' AND ele_type <> 'sep' AND ele_type <> 'areamodif' ORDER BY ele_order";
      	$captionres = $xoopsDB->query($captionq);
      	// collect the captions and their values into an array for passing to the template
      	$indexer = 0;
      	while($captionarray = $xoopsDB->fetchArray($captionres)) {
      		$formulize_profile[$indexer]['caption'] = $captionarray['ele_caption'];
      		foreach($data[0][$desc_form][$id_req][$captionarray['ele_id']] as $value) {
      			$formulize_profile[$indexer]['values'][] = $myts->makeClickable($value);
      		}
      		$indexer++;
      	}
      	$xoopsTpl->assign('formulize_profile', $formulize_profile);
	}

// ********************************
// END OF ADDED CODE
// ********************************

if ( is_object($xoopsUser) && $isAdmin ) {
    $xoopsTpl->assign('lang_editprofile', _US_EDITPROFILE);
    $xoopsTpl->assign('lang_deleteaccount', _US_DELACCOUNT);
    $xoopsTpl->assign('user_uid', $thisUser->getVar('uid'));
}
$xoopsTpl->assign('lang_allaboutuser', sprintf(_US_ALLABOUT,$thisUser->getVar('uname')));
$xoopsTpl->assign('lang_avatar', _US_AVATAR);
$xoopsTpl->assign('user_avatarurl', 'uploads/'.$thisUser->getVar('user_avatar'));
$xoopsTpl->assign('lang_realname', _US_REALNAME);
$xoopsTpl->assign('user_realname', $thisUser->getVar('name'));
$xoopsTpl->assign('lang_website', _US_WEBSITE);
$xoopsTpl->assign('user_websiteurl', '<a href="'.$thisUser->getVar('url', 'E').'" target="_blank">'.$thisUser->getVar('url').'</a>');
$xoopsTpl->assign('lang_email', _US_EMAIL);
$xoopsTpl->assign('lang_privmsg', _US_PM);
$xoopsTpl->assign('lang_icq', _US_ICQ);
$xoopsTpl->assign('user_icq', $thisUser->getVar('user_icq'));
$xoopsTpl->assign('lang_aim', _US_AIM);
$xoopsTpl->assign('user_aim', $thisUser->getVar('user_aim'));
$xoopsTpl->assign('lang_yim', _US_YIM);
$xoopsTpl->assign('user_yim', $thisUser->getVar('user_yim'));
$xoopsTpl->assign('lang_msnm', _US_MSNM);
$xoopsTpl->assign('user_msnm', $thisUser->getVar('user_msnm'));
$xoopsTpl->assign('lang_location', _US_LOCATION);
$xoopsTpl->assign('user_location', $thisUser->getVar('user_from'));
$xoopsTpl->assign('lang_occupation', _US_OCCUPATION);
$xoopsTpl->assign('user_occupation', $thisUser->getVar('user_occ'));
$xoopsTpl->assign('lang_interest', _US_INTEREST);
$xoopsTpl->assign('user_interest', $thisUser->getVar('user_intrest'));
$xoopsTpl->assign('lang_extrainfo', _US_EXTRAINFO);
$var = $thisUser->getVar('bio', 'N');
$xoopsTpl->assign('user_extrainfo', $myts->makeTareaData4Show( $var,0,1,1) );
$xoopsTpl->assign('lang_statistics', _US_STATISTICS);
$xoopsTpl->assign('lang_membersince', _US_MEMBERSINCE);
$var = $thisUser->getVar('user_regdate');
$xoopsTpl->assign('user_joindate', formatTimestamp( $var, 's' ) );
$xoopsTpl->assign('lang_rank', _US_RANK);
$xoopsTpl->assign('lang_posts', _US_POSTS);
$xoopsTpl->assign('lang_basicInfo', _US_BASICINFO);
$xoopsTpl->assign('lang_more', _US_MOREABOUT);
$xoopsTpl->assign('lang_myinfo', _US_MYINFO);
$xoopsTpl->assign('user_posts', $thisUser->getVar('posts'));
$xoopsTpl->assign('lang_lastlogin', _US_LASTLOGIN);
$xoopsTpl->assign('lang_notregistered', _US_NOTREGISTERED);

$xoopsTpl->assign('lang_signature', _US_SIGNATURE);
$var = $thisUser->getVar('user_sig', 'N');
$xoopsTpl->assign('user_signature', $myts->makeTareaData4Show( $var, 0, 1, 1 ) );

if ($thisUser->getVar('user_viewemail') == 1) {
    $xoopsTpl->assign('user_email', $thisUser->getVar('email', 'E'));
} else {
    if (is_object($xoopsUser)) {
        // All admins will be allowed to see emails, even those that are not allowed to edit users (I think it's ok like this)
	if ($xoopsUserIsAdmin || ($xoopsUser->getVar("uid") == $thisUser->getVar("uid")) || reg_codes_userCanViewProfileForm($fid, $formulizeModule->getVar('mid'), $groups, $thisUser, $gperm_handler)) { // modified by Freeform Solutions -- April 3 2007
            $xoopsTpl->assign('user_email', $thisUser->getVar('email', 'E'));
        } else {
            $xoopsTpl->assign('user_email', '&nbsp;');
        }
    }
}
if (is_object($xoopsUser)) {
    $xoopsTpl->assign('user_pmlink', "<a href=\"javascript:openWithSelfMain('".XOOPS_URL."/pmlite.php?send2=1&amp;to_userid=".$thisUser->getVar('uid')."', 'pmlite', 450, 380);\"><img src=\"".XOOPS_URL."/images/icons/pm.gif\" alt=\"".sprintf(_SENDPMTO,$thisUser->getVar('uname'))."\" /></a>");
} else {
    $xoopsTpl->assign('user_pmlink', '');
}
$userrank =& $thisUser->rank();
if ($userrank['image']) {
    $xoopsTpl->assign('user_rankimage', '<img src="'.XOOPS_UPLOAD_URL.'/'.$userrank['image'].'" alt="" />');
}
$xoopsTpl->assign('user_ranktitle', $userrank['title']);
$date = $thisUser->getVar("last_login");
if (!empty($date)) {
    $xoopsTpl->assign('user_lastlogin', formatTimestamp($date,"m"));
}


$module_handler =& xoops_gethandler('module');
$criteria = new CriteriaCompo(new Criteria('hassearch', 1));
$criteria->add(new Criteria('isactive', 1));
$mids =& array_keys($module_handler->getList($criteria));

foreach ($mids as $mid) {
    // Hack by marcan : only return results of modules for which user has access permission
  if ( $gperm_handler->checkRight('module_read', $mid, $groups)) {
    $module =& $module_handler->get($mid);
    $results =& $module->search('', '', 5, 0, $thisUser->getVar('uid'));
    $count = count($results);
    if (is_array($results) && $count > 0) {
        for ($i = 0; $i < $count; $i++) {
            if (isset($results[$i]['image']) && $results[$i]['image'] != '') {
                $results[$i]['image'] = 'modules/'.$module->getVar('dirname').'/'.$results[$i]['image'];
            } else {
                $results[$i]['image'] = 'images/icons/posticon2.gif';
            }
            
            if (!preg_match("/^http[s]*:\/\//i", $results[$i]['link'])) {
                $results[$i]['link'] = "modules/".$module->getVar('dirname')."/".$results[$i]['link'];
            }

            $results[$i]['title'] = $myts->makeTboxData4Show($results[$i]['title']);
            $results[$i]['time'] = $results[$i]['time'] ? formatTimestamp($results[$i]['time']) : '';
        }
        if ($count == 5) {
            $showall_link = '<a href="search.php?action=showallbyuser&amp;mid='.$mid.'&amp;uid='.$thisUser->getVar('uid').'">'._US_SHOWALL.'</a>';
        } else {
            $showall_link = '';
        }
        $xoopsTpl->append('modules', array('name' => $module->getVar('name'), 'results' => $results, 'showall_link' => $showall_link));
    }
    unset($module);
  }
}
include XOOPS_ROOT_PATH.'/footer.php';

// ADDED BY FREEFORM SOLUTIONS APRIL 3 2007
// THIS FUNCTION CHECKS TO SEE IF A USER HAS PERMISSION TO VIEW GLOBALSCOPE ON THE PROFILE FORM, OR
// WHETHER THEY CAN VIEW GROUPSCOPE IN SUCH A WAY THAT WOULD ENCOMPASS THE CURRENT USER BEING VIEWED
function reg_codes_userCanViewProfileForm($fid, $mid, $groups, $thisUser, $gperm_handler) {
	$canview = false;
	if($view_globalscope = $gperm_handler->checkRight("view_globalscope", $fid, $groups, $mid)) {
		$canview = true;
	}
	if(!$canview AND $gperm_handler->checkRight("view_groupscope", $fid, $groups, $mid)) {
		// user has view_groupscope (and not view globalscope)
		// so question is, is $thisUser a member of any of groups
		// with view_form that the current user is also a member of?
		$groups_view = $gperm_handler->getGroupIds("view_form", $fid, $mid);
		$thisUser_groups = $thisUser->getGroups();
		if(array_intersect($groups_view, $thisUser_groups, $groups)) {
			$canview = true;
		}
	}
	return $canview;
}

?>