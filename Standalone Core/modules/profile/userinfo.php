<?php
/**
 * Extended User Profile
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license	LICENSE.txt
 * @license	GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package	modules
 * @since	1.2
 * @author	Jan Pedersen
 * @author	The SmartFactory <www.smartfactory.ca>
 * @author	Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version	$Id$
 */

include '../../mainfile.php';

include_once ICMS_ROOT_PATH . '/modules/system/constants.php';

$uid = !empty($_GET['uid'])?intval($_GET['uid']):'';

if ($uid <= 0) {
	if(is_object($icmsUser)){
		$uid = $icmsUser->getVar('uid');
	}else{
		header('location: '.ICMS_URL);
		exit();
	}
}

if($icmsModuleConfig['profile_social']==1){
	header('Location: '.ICMS_URL.'/modules/profile/index.php?uid='.$uid);
	exit();
}

$gperm_handler = & xoops_gethandler( 'groupperm' );
$groups = is_object($icmsUser) ? $icmsUser->getGroups() : array(ICMS_GROUP_ANONYMOUS);
if (!$icmsConfigUser['allow_annon_view_prof'] && !is_object($icmsUser)) {
	redirect_header(ICMS_URL.'/user.php', 3, _NOPERM);
	exit ();
}

if (is_object($icmsUser) && $uid == $icmsUser->getVar('uid')) {
    //disable cache
    $icmsConfig['module_cache'][$icmsModule->getVar('mid')] = 0;
    $xoopsOption['template_main'] = 'profile_userinfo.html';
    include ICMS_ROOT_PATH.'/header.php';

    $xoopsTpl->assign('user_ownpage', true);
    $xoopsTpl->assign('lang_editprofile', _PROFILE_MA_EDITPROFILE);
    $xoopsTpl->assign('lang_changepassword', _PROFILE_MA_CHANGEPASSWORD);
    $xoopsTpl->assign('lang_avatar', _PROFILE_MA_AVATAR);
    $xoopsTpl->assign('lang_logout', _PROFILE_MA_LOGOUT);
    if ($icmsConfigUser['self_delete'] == 1) {
        $xoopsTpl->assign('user_candelete', true);
        $xoopsTpl->assign('lang_deleteaccount', _PROFILE_MA_DELACCOUNT);
    } else {
        $xoopsTpl->assign('user_candelete', false);
    }
    $xoopsTpl->assign('user_changeemail', $icmsConfigUser['allow_chgmail']);
    $thisUser =& $icmsUser;
} else {
    $member_handler =& xoops_gethandler('member');
    $thisUser =& $member_handler->getUser($uid);
    if (!is_object($thisUser) || (!$thisUser->isActive() && (!$icmsUser || !$icmsUser->isAdmin()))) {
        redirect_header(ICMS_URL."/modules/".basename( dirname( __FILE__ ) ),3,_PROFILE_MA_SELECTNG);
        exit();
    }
    if ($icmsUserIsAdmin) {
        //disable cache
        $icmsConfig['module_cache'][$icmsModule->getVar('mid')] = 0;
    }
    $xoopsOption['template_main'] = 'profile_userinfo.html';
    include ICMS_ROOT_PATH.'/header.php';
    $xoopsTpl->assign('user_ownpage', false);
}

// adding profile stylesheet
$dirname = basename( dirname( __FILE__ ) );
$xoTheme->addStylesheet(ICMS_URL.'/modules/'.$dirname.'/assets/css/profile'.(@_ADM_USE_RTL == 1 ? '_rtl':'').'.css');
if(ereg('msie', strtolower($_SERVER['HTTP_USER_AGENT']))) {$xoTheme->addStylesheet(ICMS_URL.'/modules/'.$dirname.'/assets/css/tabs-ie.css');}

if ( is_object($icmsUser) && $icmsUser->isAdmin() ) {
    $xoopsTpl->assign('lang_editprofile', _PROFILE_MA_EDITPROFILE);
    $xoopsTpl->assign('lang_deleteaccount', _PROFILE_MA_DELACCOUNT);
    $xoopsTpl->assign('user_uid', $thisUser->getVar('uid'));
    $xoopsTpl->assign('userlevel', $thisUser->isActive());
}

// Dynamic User Profiles
$thisUsergroups =& $thisUser->getGroups();
$visibility_handler = icms_getmodulehandler( 'visibility', basename( dirname( __FILE__ ) ), 'profile' );
$fieldids = $visibility_handler->getVisibleFields($groups, $thisUsergroups);

$profile_handler =& icms_getmodulehandler( 'profile', basename( dirname( __FILE__ ) ), 'profile' );
$fields = $profile_handler->loadFields();
$cat_handler =& icms_getmodulehandler( 'category', basename( dirname( __FILE__ ) ), 'profile' );
$cat_crit = new CriteriaCompo();
$cat_crit->setSort("cat_weight");
$cats = $cat_handler->getObjects($cat_crit, true, false);
unset($cat_crit);

// Add core fields
//$categories[0]['cat_title'] = sprintf(_PROFILE_MA_ALLABOUT, $thisUser->getVar('uname'));
//if($thisUser->getVar('name')){
//    $categories[0]['fields'][] = array('title' => _PROFILE_MA_REALNAME, 'value' => $thisUser->getVar('name'));
//    $weights[0][] = 0;
//}
$avatar = '';
if($thisUser->getVar('user_avatar') && "blank.gif" != $thisUser->getVar('user_avatar')){
    $avatar = ICMS_UPLOAD_URL."/".$thisUser->getVar('user_avatar');
}elseif ($icmsConfigUser['avatar_allow_gravatar'] == 1) {
    $avatar = $thisUser->gravatar('G', $icmsConfigUser['avatar_width']);
}

if ($thisUser->getVar('user_viewemail') == 1 && is_object($icmsUser)) { //MPB disallow anonymous viewing
    $email = $thisUser->getVar('email', 'E');
} else {
    $email = _PROFILE_MA_SENDPM;
    if (is_object($icmsUser)) {
        // Module admins will be allowed to see emails
        if ($icmsUser->isAdmin() || ($icmsUser->getVar("uid") == $thisUser->getVar("uid"))) {
            $email = $thisUser->getVar('email', 'E');
        }
    }
}
//if ($email != "") {
//    $categories[0]['fields'][] = array('title' => _PROFILE_MA_EMAIL, 'value' => $email);
//    $weights[0][] = 0;
//}
foreach (array_keys($cats) as $i) {
    $categories[$i] = $cats[$i];
}

$profile_handler = icms_getmodulehandler( 'profile', basename( dirname( __FILE__ ) ), 'profile' );
$profile = $profile_handler->get($thisUser->getVar('uid'));
// Add dynamic fields
foreach (array_keys($fields) as $i) {
    //If field should be shown
    if (in_array($fields[$i]->getVar('fieldid'), $fieldids)) {
        $catid = $fields[$i]->getVar('catid');
        $value = $fields[$i]->getOutputValue($thisUser, $profile);
         if (is_array($value)) {
            $value = implode('<br />', array_values($value));
        }
        if($icmsModuleConfig['show_empty'] || $value){
            $categories[$catid]['fields'][$fields[$i]->getVar('field_weight')."_".$i] = array('title' => $fields[$i]->getVar('field_title'), 'value' => $value);
           	ksort($categories[$catid]['fields']);
           	$weights[$catid][] = $fields[$i]->getVar('catid');
        }
    }
}

//sort fields order in categories
foreach (array_keys($categories) as $i) {
    if (isset($categories[$i]['fields'])) {
        array_multisort($weights[$i], SORT_ASC, array_keys($categories[$i]['fields']), SORT_ASC, $categories[$i]['fields']);
    }
}

//ksort($categories);
$xoopsTpl->assign('categories', $categories);
// Dynamic user profiles end

if ($icmsModuleConfig['profile_search']) {
    $module_handler =& xoops_gethandler('module');
    $criteria = new CriteriaCompo(new Criteria('hassearch', 1));
    $criteria->add(new Criteria('isactive', 1));
    $modules = $module_handler->getObjects($criteria, true);
    $mids = array_keys($modules);

    $myts =& MyTextSanitizer::getInstance();
    $allowed_mids = $gperm_handler->getItemIds('module_read', $groups);
    if (count($mids) > 0 && count($allowed_mids) > 0) {
        foreach ($mids as $mid) {
            if ( in_array($mid, $allowed_mids)) {
                $results = $modules[$mid]->search('', '', 5, 0, $thisUser->getVar('uid'));
                $count = count($results);
                if (is_array($results) && $count > 0) {
                    for ($i = 0; $i < $count; $i++) {
                        if (isset($results[$i]['image']) && $results[$i]['image'] != '') {
                            $results[$i]['image'] = ICMS_URL.'/modules/'.$modules[$mid]->getVar('dirname').'/'.$results[$i]['image'];
                        } else {
                            $results[$i]['image'] = ICMS_URL.'/images/icons/posticon2.gif';
                        }
                        if (!preg_match("/^http[s]*:\/\//i", $results[$i]['link'])) {
                            $results[$i]['link'] = ICMS_URL."/modules/".$modules[$mid]->getVar('dirname')."/".$results[$i]['link'];
                        }
                        $results[$i]['title'] = $myts->makeTboxData4Show($results[$i]['title']);
                        $results[$i]['time'] = $results[$i]['time'] ? formatTimestamp($results[$i]['time']) : '';
                    }
                    if ($count == 5) {
                        $showall_link = '<a href="'.ICMS_URL.'/search.php?action=showallbyuser&amp;mid='.$mid.'&amp;uid='.$thisUser->getVar('uid').'">'._PROFILE_MA_SHOWALL.'</a>';
                    } else {
                        $showall_link = '';
                    }
                    $xoopsTpl->append('modules', array('name' => $modules[$mid]->getVar('name'), 'results' => $results, 'showall_link' => $showall_link));
                }
                unset($modules[$mid]);
            }
        }
    }
}

//get username for display
if ($icmsModuleConfig['index_real_name'] == 'real' && trim($thisUser->getVar('name'))) {
	$owner_name = is_object($thisUser) ? trim($thisUser->getVar('name')) : _GUESTS;
} elseif ($icmsModuleConfig['index_real_name'] == 'both' && trim($thisUser->getVar('name'))) {
	$owner_name = is_object($thisUser) ? trim($thisUser->getVar('name')).' ('.trim($thisUser->getVar('uname')).')' : _GUESTS;
} else {
	$owner_name = is_object($thisUser) ? trim($thisUser->getVar('uname')) : _GUESTS;
}
$xoopsTpl->assign('user_name_header', $owner_name);
//User info
$xoopsTpl->assign('uname', $thisUser->getVar('uname'));
// MPB - ADD - START
$xoopsTpl->assign('name', $thisUser->getVar('name'));
$xoopsTpl->assign('user_pmlink', "javascript:openWithSelfMain('".ICMS_URL."/pmlite.php?send2=1&amp;to_userid=".$thisUser->getVar('uid')."', 'pmlite', 450, 380);");
$xoopsTpl->assign('user_pmlink_imgsrc_alttxt', sprintf(_SENDPMTO, $thisUser->getVar('uname')));
// MPB - ADD - END
$xoopsTpl->assign('uname', $thisUser->getVar('uname'));
$xoopsTpl->assign('email', $email);
$xoopsTpl->assign('avatar', $avatar);
//$xoopsTpl->assign('profile_category', _PROFILE_MA_USERINFO);

include 'footer.php';
?>