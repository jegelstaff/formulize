<?php
/**
* @copyright    http://www.xoops.org/ The XOOPS Project
* @copyright    XOOPS_copyrights.txt
* @copyright    http://www.impresscms.org/ The ImpressCMS Project
* @license      http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package      core
* @since        XOOPS
* @author       http://www.xoops.org The XOOPS Project
* @author       Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version      $Id: userinfo.php 9346 2009-09-06 16:13:57Z m0nty $
*/
/** Displays user profile
* @package      kernel
* @subpackage   users
*/
$xoopsOption['pagetype'] = 'user';
include 'mainfile.php';
$uid = intval($_GET['uid']);

if(icms_get_module_status('profile'))
{
    $module_handler = xoops_gethandler('module');
    $config_handler = xoops_gethandler('config');
    $icmsModule =& $module_handler->getByDirname('profile');
    $icmsModuleConfig =& $config_handler->getConfigsByCat(0, $icmsModule->getVar('mid'));

    if($icmsModuleConfig['profile_social'] && file_exists(ICMS_ROOT_PATH.'/modules/profile/index.php'))
    {
        header('Location: '.ICMS_URL.'/modules/profile/index.php?uid='.$uid);
        exit();
    }
    elseif(!$icmsModuleConfig['profile_social'] && file_exists(ICMS_ROOT_PATH.'/modules/profile/userinfo.php'))
    {
        header('Location: '.ICMS_URL.'/modules/profile/userinfo.php?uid='.$uid);
        exit();
    }
    unset($icmsModuleConfig, $icmsModule, $config_handler, $member_handler);
}

include_once ICMS_ROOT_PATH.'/modules/system/constants.php';

if(!$icmsConfigUser['allow_annon_view_prof'] && !is_object($icmsUser))
{
    redirect_header(ICMS_URL.'/user.php', 3, _NOPERM);
}
if($uid <= 0)
{
    redirect_header('index.php', 3, _US_SELECTNG);
}

include_once ICMS_ROOT_PATH.'/class/module.textsanitizer.php';

$gperm_handler = xoops_gethandler('groupperm');
$groups = is_object($icmsUser) ? $icmsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;

$isAdmin = $gperm_handler->checkRight('system_admin', XOOPS_SYSTEM_USER, $groups);

if(is_object($icmsUser))
{
    if($uid == intval($icmsUser->getVar('uid')))
    {
        $xoopsOption['template_main'] = 'system_userinfo.html';
        include ICMS_ROOT_PATH.'/header.php';
        $xoopsTpl->assign('user_ownpage', true);
        icms_makeSmarty(array(
            'user_ownpage' => true,
            'lang_editprofile' => _US_EDITPROFILE,
            'lang_avatar' => _US_AVATAR,
            'lang_inbox' => _US_INBOX,
            'lang_logout' => _US_LOGOUT,
            'user_candelete' => $icmsConfigUser['self_delete'] ? true : false,
            'lang_deleteaccount' => $icmsConfigUser['self_delete'] ? _US_DELACCOUNT : ''));
        $thisUser = & $icmsUser;
    }
    else
    {
        $member_handler = xoops_gethandler('member');
        $thisUser = & $member_handler->getUser($uid);
        if(!is_object($thisUser) || !$thisUser->isActive())
        {
            redirect_header('index.php', 3, _US_SELECTNG);
        }
        $xoopsOption['template_main'] = 'system_userinfo.html';
        include ICMS_ROOT_PATH.'/header.php';
        $xoopsTpl->assign('user_ownpage', false);
    }
}
else
{
    $member_handler = xoops_gethandler('member');
    $thisUser = & $member_handler->getUser($uid);
    if(!is_object($thisUser) || !$thisUser->isActive())
    {
        redirect_header('index.php', 3, _US_SELECTNG);
    }
    $xoopsOption['template_main'] = 'system_userinfo.html';
    include ICMS_ROOT_PATH.'/header.php';
    $xoopsTpl->assign('user_ownpage', false);
}

$myts = MyTextSanitizer::getInstance();
if(is_object($icmsUser) && $isAdmin)
{
    icms_makeSmarty(array(
        'lang_editprofile' => _US_EDITPROFILE,
        'lang_deleteaccount' => _US_DELACCOUNT,
        'user_uid' => intval($thisUser->getVar('uid'))
    ));
}
$userrank = & $thisUser->rank();
$date = $thisUser->getVar('last_login');
icms_makeSmarty(array(
    'user_avatarurl' => $icmsConfigUser['avatar_allow_gravatar'] == true
        ? $thisUser->gravatar('G', $icmsConfigUser['avatar_width'])
        : ICMS_UPLOAD_URL.'/'.$thisUser->getVar('user_avatar'),
    'user_websiteurl' => ($thisUser->getVar('url', 'E') == '') ? ''
        : '<a href="'.$thisUser->getVar('url', 'E').'" rel="external">'.$thisUser->getVar('url').'</a>',
    'lang_website' => _US_WEBSITE,
    'user_realname' => $thisUser->getVar('name'),
    'lang_realname' => _US_REALNAME,
    'lang_avatar' => _US_AVATAR,
    'lang_allaboutuser' => sprintf(_US_ALLABOUT, $thisUser->getVar('uname')),
    'user_alwopenid' => $icmsConfigAuth['auth_openid'],
    'lang_openid', $icmsConfigAuth['auth_openid'] == true ? _US_OPENID_FORM_CAPTION : '',
    'lang_email' => _US_EMAIL,
    'lang_privmsg' => _US_PM,
    'lang_icq' => _US_ICQ,
    'user_icq' => $thisUser->getVar('user_icq'),
    'lang_aim' => _US_AIM,
    'user_aim' => $thisUser->getVar('user_aim'),
    'lang_yim' => _US_YIM,
    'user_yim' => $thisUser->getVar('user_yim'),
    'lang_msnm' => _US_MSNM,
    'user_msnm' => $thisUser->getVar('user_msnm'),
    'lang_location' => _US_LOCATION,
    'user_location' => $thisUser->getVar('user_from'),
    'lang_occupation' => _US_OCCUPATION,
    'user_occupation' => $thisUser->getVar('user_occ'),
    'lang_interest' => _US_INTEREST,
    'user_interest' => $thisUser->getVar('user_intrest'),
    'lang_extrainfo' => _US_EXTRAINFO,
    'user_extrainfo' => $myts->displayTarea($thisUser->getVar('bio', 'N'), 0, 1, 1),
    'lang_statistics' => _US_STATISTICS,
    'lang_membersince' => _US_MEMBERSINCE,
    'user_joindate' => formatTimestamp($thisUser->getVar('user_regdate'), 's'),
    'lang_rank' => _US_RANK,
    'lang_posts' => _US_POSTS,
    'lang_basicInfo' => _US_BASICINFO,
    'lang_more' => _US_MOREABOUT,
    'lang_myinfo' => _US_MYINFO,
    'user_posts' => icms_conv_nr2local($thisUser->getVar('posts')),
    'lang_lastlogin' => _US_LASTLOGIN,
    'lang_notregistered' => _US_NOTREGISTERED,
    'user_pmlink' => is_object($icmsUser) ?
        "<a href=\"javascript:openWithSelfMain('".ICMS_URL."/pmlite.php?send2=1&amp;to_userid="
        .intval($thisUser->getVar('uid'))."', 'pmlite', 800,680);\">
        <img src=\"".ICMS_URL."/images/icons/".$icmsConfig['language']."/pm.gif\" alt=\""
        .sprintf(_SENDPMTO, $thisUser->getVar('uname'))."\" /></a>" : '',
    'user_rankimage' => $userrank['image'] ?
        '<img src="'.ICMS_UPLOAD_URL.'/system/userrank/'.$userrank['image'].'" alt="'.$userrank['title'].'" />' : '',
    'user_ranktitle' => $userrank['title'],
    'user_lastlogin' => !empty($date) ? formatTimestamp($thisUser->getVar('last_login'), 'm') : '',
    'xoops_pagetitle' => sprintf(_US_ALLABOUT, $thisUser->getVar('uname')),
    'user_email' => ($thisUser->getVar('user_viewemail') == true || (is_object($icmsUser) &&
        ($icmsUserIsAdmin || ($icmsUser->getVar('uid') == $thisUser->getVar('uid')))))
        ? $thisUser->getVar('email', 'E') : '&nbsp;',
    'user_openid' => ($icmsConfigAuth['auth_openid'] == true
        && ($thisUser->getVar('user_viewoid') == true || (is_object($icmsUser) && ($icmsUserIsAdmin
        || ($icmsUser->getVar('uid') == $thisUser->getVar('uid')))))) ? $thisUser->getVar('openid', 'E') : '&nbsp;'
    ));
if($icmsConfigUser['allwshow_sig'] == true)
{
    icms_makeSmarty(array(
        'user_showsignature' => true,
        'lang_signature' => _US_SIGNATURE,
        'user_signature' => $myts->displayTarea(one_wordwrap($thisUser->getVar('user_sig', 'N')), 1, 1)
    ));
}

$module_handler = xoops_gethandler('module');
$criteria = new CriteriaCompo(new Criteria('hassearch', 1));
$criteria->add(new Criteria('isactive', 1));
$mids = & array_keys($module_handler->getList($criteria));

foreach($mids as $mid)
{
    if($gperm_handler->checkRight('module_read', $mid, $groups))
    {
        $module = & $module_handler->get($mid);
        $results = & $module->search('', '', 5, 0, intval($thisUser->getVar('uid')));
        $count = count($results);
        if(is_array($results) && $count > 0)
        {
            for($i = 0; $i < $count; $i++)
            {
                if(isset($results[$i]['image']) && $results[$i]['image'] != '')
                {
                    $results[$i]['image'] = 'modules/'.$module->getVar('dirname').'/'.$results[$i]['image'];
                }
                else
                {
                    $results[$i]['image'] = 'images/icons/'.$icmsConfig['language'].'/posticon2.gif';
                }
                if(isset($results[$i]['link']) && $results[$i]['link'] != '')
                {
                    if(!preg_match("/^http[s]*:\/\//i", $results[$i]['link']))
                    {
                        $results[$i]['link'] = "modules/".$module->getVar('dirname')."/".$results[$i]['link'];
                    }
                }
                $results[$i]['title'] = $myts->makeTboxData4Show($results[$i]['title']);
                $results[$i]['time'] = $results[$i]['time'] ? formatTimestamp($results[$i]['time']) : '';
            }
            if($count == 5)
            {
                $showall_link = '<a href="search.php?action=showallbyuser&amp;mid='.intval($mid).'&amp;
                    uid='.intval($thisUser->getVar('uid')).'">'._US_SHOWALL.'</a>';
            }
            else
            {
                $showall_link = '';
            }
            $xoopsTpl->append('modules', array(
                'name' => $module->getVar('name'),
                'results' => $results,
                'showall_link' => $showall_link
                ));
        }
        unset ($module);
    }
}
include ICMS_ROOT_PATH.'/footer.php';
?>