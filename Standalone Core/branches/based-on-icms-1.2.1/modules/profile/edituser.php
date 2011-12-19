<?php
/**
 * Extended User Profile
 *
 *
 * @copyright       The ImpressCMS Project http://www.impresscms.org/
 * @license         LICENSE.txt
 * @license			GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package         modules
 * @since           1.2
 * @author          Jan Pedersen
 * @author          The SmartFactory <www.smartfactory.ca>
 * @author	   		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version         $Id$
 */

include '../../mainfile.php';
include_once ICMS_ROOT_PATH.'/class/xoopsformloader.php';
include_once ICMS_ROOT_PATH.'/modules/'.basename( dirname( __FILE__ ) ).'/include/functions.php';
global $icmsConfigUser;
// If not a user, redirect
if (!is_object($icmsUser)) {
    redirect_header(ICMS_URL,3,_PROFILE_MA_NOEDITRIGHT);
    exit();
}

// initialize $op variable
$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : 'editprofile';

if ($op == 'save') {
    if (!$GLOBALS['xoopsSecurity']->check()) {
        redirect_header(ICMS_URL."/modules/".basename( dirname( __FILE__ ) ),3,_PROFILE_MA_NOEDITRIGHT."<br />".implode('<br />', $GLOBALS['xoopsSecurity']->getErrors()));
        exit;
    }
    $uid = 0;
    if (!empty($_POST['uid'])) {
        $uid = intval($_POST['uid']);
    }
    if (empty($uid) || ($icmsUser->getVar('uid') != $uid && !$icmsUser->isAdmin())) {
        redirect_header(ICMS_URL."/",3,_PROFILE_MA_NOEDITRIGHT);
        exit();
    }
    $errors = array();
    $myts =& MyTextSanitizer::getInstance();
    $member_handler =& xoops_gethandler('member');
    $edituser =& $member_handler->getUser($uid);
    if ($icmsUser->isAdmin()) {
        $edituser->setVar('login_name', $myts->stripSlashesGPC(trim($_POST['login_name'])));
        $edituser->setVar('uname', $myts->stripSlashesGPC(trim($_POST['uname'])));
        $edituser->setVar('email', $myts->stripSlashesGPC(trim($_POST['email'])));
    } else {
        if ($icmsConfigUser['allow_chguname'] == 1) $edituser->setVar('uname', $myts->stripSlashesGPC(trim($_POST['uname'])));
        if ($icmsConfigUser['allow_chgmail'] == 1) $edituser->setVar('email', $myts->stripSlashesGPC(trim($_POST['email'])));
    }
    if ($icmsConfigAuth['auth_openid'] == 1) {
        $edituser->setVar('openid', $myts->stripSlashesGPC(trim($_POST['openid'])));
        $edituser->setVar('user_viewoid', isset($_POST['user_viewoid']) ? intval($_POST['user_viewoid']) : 0);
    }
	
    $stop = userCheck($edituser);
    if (!empty($stop)) {
        echo "<span style='color:#ff0000;'>$stop</span>";
        redirect_header(ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/userinfo.php?uid='.$uid, 2);
    }

    // Dynamic fields
    $profile_handler =& icms_getmodulehandler( 'profile', basename( dirname( __FILE__ ) ), 'profile' );
    // Get fields
    $fields =& $profile_handler->loadFields();
    // Get ids of all available fields for the user groups $icmsUser is a member of.
    $gperm_handler =& xoops_gethandler('groupperm');
    $editable_fields =& $gperm_handler->getItemIds('profile_edit', $icmsUser->getGroups(), $icmsModule->getVar('mid'));

    $profile = $profile_handler->get($edituser->getVar('uid'));

    foreach (array_keys($fields) as $i) {
        if ($fields[$i]->getVar('field_edit') == 1) {
			$fieldname = $fields[$i]->getVar('field_name');
	        if (in_array($fields[$i]->getVar('fieldid'), $editable_fields) && ($fields[$i]->getvar('field_type') == "image" || isset($_REQUEST[$fieldname]))) {
	            if (in_array($fieldname, $profile_handler->getUserVars())) {
	                $value = $fields[$i]->getValueForSave($_REQUEST[$fieldname], $edituser->getVar($fieldname, 'n'));
	                $edituser->setVar($fieldname, $value);
	            }
	            else {
	                $value = $fields[$i]->getValueForSave((isset($_REQUEST[$fieldname]) ? $_REQUEST[$fieldname] : ""), $profile->getVar($fieldname, 'n'));
	                $profile->setVar($fieldname, $value);
	            }
	        }
	    }
    }
    if (!$member_handler->insertUser($edituser)) {
        include ICMS_ROOT_PATH.'/header.php';
        include_once 'include/forms.php';
        echo '<a href="'.ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/userinfo.php?uid='.$edituser->getVar('uid').'">'. _PROFILE_MA_PROFILE .'</a>&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;'. _PROFILE_MA_EDITPROFILE .'<br /><br />';
        $form =& getUserForm($edituser, $profile);
        echo $edituser->getHtmlErrors();
        $form->display();
    } else {
        $profile->setVar('profileid', $edituser->getVar('uid'));
        $profile_handler->insert($profile);
        unset($_SESSION['xoopsUserTheme']);
        redirect_header(ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/userinfo.php?uid='.$uid, 2, _PROFILE_MA_PROFUPDATED);
    }
}


if ($op == 'editprofile') {
    include_once ICMS_ROOT_PATH.'/header.php';
    include_once 'include/forms.php';
    echo '<a href="'.ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/userinfo.php?uid='.$icmsUser->getVar('uid').'">'. _PROFILE_MA_PROFILE .'</a>&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;'. _PROFILE_MA_EDITPROFILE .'<br /><br />';
    $form =& getUserForm($icmsUser);
    $form->display();
}


if ($op == 'delete') {
    if (!$icmsUser || $icmsConfigUser['self_delete'] != 1) {
        redirect_header(ICMS_URL.'/',5,_PROFILE_MA_NOPERMISS);
        exit();
    } else {
        $groups = $icmsUser->getGroups();
        if (in_array(ICMS_GROUP_ADMIN, $groups)){
            // users in the webmasters group may not be deleted
            redirect_header(ICMS_URL.'/index.php', 5, _PROFILE_MA_ADMINNO);
            exit();
        }
        $ok = !isset($_POST['ok']) ? 0 : intval($_POST['ok']);
        if ($ok != 1) {
            include ICMS_ROOT_PATH.'/header.php';
            xoops_confirm(array('op' => 'delete', 'ok' => 1), ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/edituser.php', _PROFILE_MA_SURETODEL.'<br/>'._PROFILE_MA_REMOVEINFO);
            include ICMS_ROOT_PATH.'/footer.php';
        } else {
            $del_uid = $icmsUser->getVar("uid");
            $member_handler =& xoops_gethandler('member');
            if (false != $member_handler->deleteUser($icmsUser)) {
                $online_handler =& xoops_gethandler('online');
                $online_handler->destroy($del_uid);
                xoops_notification_deletebyuser($del_uid);

                //logout user
                $_SESSION = array();
                session_destroy();
                if ($icmsConfig['use_mysession'] && $icmsConfig['session_name'] != '') {
                    setcookie($icmsConfig['session_name'], '', time()- 3600, '/',  '', 0);
                }
                redirect_header(ICMS_URL.'/', 5, _PROFILE_MA_BEENDELED);
            }
            redirect_header(ICMS_URL.'/',5,_PROFILE_MA_NOPERMISS);
        }
        exit();
    }
}

if ($op == 'avatarform') {
    include ICMS_ROOT_PATH.'/header.php';
    echo '<a href="'.ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/userinfo.php?uid='.$icmsUser->getVar('uid').'">'. _PROFILE_MA_PROFILE .'</a>&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;'. _PROFILE_MA_UPLOADMYAVATAR .'<br /><br />';
    $oldavatar = $icmsUser->getVar('user_avatar');
    if (!empty($oldavatar) && $oldavatar != 'blank.gif') {
        echo '<div style="text-align:center;"><h4 style="color:#ff0000; font-weight:bold;">'._PROFILE_MA_OLDDELETED.'</h4>';
        echo '<img src="'.ICMS_UPLOAD_URL.'/'.$oldavatar.'" alt="" /></div>';
    }
    if ($icmsConfigUser['avatar_allow_upload'] == 1 && $icmsUser->getVar('posts') >= $icmsConfigUser['avatar_minposts']) {
        include_once ICMS_ROOT_PATH.'/class/xoopsformloader.php';
        $form = new XoopsThemeForm(_PROFILE_MA_UPLOADMYAVATAR, 'uploadavatar', ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/edituser.php', 'post', true);
        $form->setExtra('enctype="multipart/form-data"');
        $form->addElement(new XoopsFormLabel(_PROFILE_MA_MAXPIXEL, $icmsConfigUser['avatar_width'].' x '.$icmsConfigUser['avatar_height']));
        $form->addElement(new XoopsFormLabel(_PROFILE_MA_MAXIMGSZ, $icmsConfigUser['avatar_maxsize']));
        $form->addElement(new XoopsFormFile(_PROFILE_MA_SELFILE, 'avatarfile', $icmsConfigUser['avatar_maxsize']), true);
        $form->addElement(new XoopsFormHidden('op', 'avatarupload'));
        $form->addElement(new XoopsFormHidden('uid', $icmsUser->getVar('uid')));
        $form->addElement(new XoopsFormButton('', 'submit', _SUBMIT, 'submit'));
            $form->display();
    }
    $avatar_handler =& xoops_gethandler('avatar');
    $form2 = new XoopsThemeForm(_PROFILE_MA_CHOOSEAVT, 'uploadavatar', ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/edituser.php', 'post', true);
    $avatar_select = new XoopsFormSelect('', 'user_avatar', $icmsUser->getVar('user_avatar'));
    $avatar_select->addOptionArray($avatar_handler->getList('S'));
    $avatar_select->setExtra("onchange='showImgSelected(\"avatar\", \"user_avatar\", \"uploads\", \"\", \"".ICMS_URL."\")'");
    $avatar_tray = new XoopsFormElementTray(_PROFILE_MA_AVATAR, '&nbsp;');
    $avatar_tray->addElement($avatar_select);
    $avatar_tray->addElement(new XoopsFormLabel('', "<img src='".ICMS_UPLOAD_URL."/".$icmsUser->getVar("user_avatar", "E")."' name='avatar' id='avatar' alt='' /> <a href=\"javascript:openWithSelfMain('".ICMS_URL."/misc.php?action=showpopups&amp;type=avatars','avatars',600,400);\">"._LIST."</a>"));
    $form2->addElement($avatar_tray);
    $form2->addElement(new XoopsFormHidden('uid', $icmsUser->getVar('uid')));
    $form2->addElement(new XoopsFormHidden('op', 'avatarchoose'));
    $form2->addElement(new XoopsFormButton('', 'submit2', _SUBMIT, 'submit'));
    $form2->display();
}

if ($op == 'avatarupload') {
    if (!$GLOBALS['xoopsSecurity']->check()) {
        redirect_header('index.php',3,_PROFILE_MA_NOEDITRIGHT."<br />".implode('<br />', $GLOBALS['xoopsSecurity']->getErrors()));
        exit;
    }
    $xoops_upload_file = array();
    $uid = 0;
    if (!empty($_POST['xoops_upload_file']) && is_array($_POST['xoops_upload_file'])){
        $xoops_upload_file = $_POST['xoops_upload_file'];
    }
    if (!empty($_POST['uid'])) {
        $uid = intval($_POST['uid']);
    }
    if (empty($uid) || $icmsUser->getVar('uid') != $uid ) {
        redirect_header('index.php',3,_PROFILE_MA_NOEDITRIGHT);
        exit();
    }
    if ($icmsConfigUser['avatar_allow_upload'] == 1 && $icmsUser->getVar('posts') >= $icmsConfigUser['avatar_minposts']) {
        include_once ICMS_ROOT_PATH.'/class/uploader.php';
        $uploader = new XoopsMediaUploader(ICMS_UPLOAD_PATH, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png', 'image/png'), $icmsConfigUser['avatar_maxsize'], $icmsConfigUser['avatar_width'], $icmsConfigUser['avatar_height']);
        if ($uploader->fetchMedia($_POST['xoops_upload_file'][0])) {
            $uploader->setPrefix('cavt');
            if ($uploader->upload()) {
                $avt_handler =& xoops_gethandler('avatar');
                $avatar =& $avt_handler->create();
                $avatar->setVar('avatar_file', $uploader->getSavedFileName());
                $avatar->setVar('avatar_name', $icmsUser->getVar('uname'));
                $avatar->setVar('avatar_mimetype', $uploader->getMediaType());
                $avatar->setVar('avatar_display', 1);
                $avatar->setVar('avatar_type', 'C');
                if (!$avt_handler->insert($avatar)) {
                    @unlink($uploader->getSavedDestination());
                } else {
                    $oldavatar = $icmsUser->getVar('user_avatar');
                    if (!empty($oldavatar) && $oldavatar != 'blank.gif' && !preg_match("/^savt/", strtolower($oldavatar))) {
                        $avatars =& $avt_handler->getObjects(new Criteria('avatar_file', $oldavatar));
                        $avt_handler->delete($avatars[0]);
                        $oldavatar_path = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH.'/'.$oldavatar));
                        if (0 === strpos($oldavatar_path, ICMS_UPLOAD_PATH) && is_file($oldavatar_path)) {
                            unlink($oldavatar_path);
                        }
                    }
                    $sql = sprintf("UPDATE %s SET user_avatar = %s WHERE uid = %u", $xoopsDB->prefix('users'), $xoopsDB->quoteString($uploader->getSavedFileName()), $icmsUser->getVar('uid'));
                    $xoopsDB->query($sql);
                    $avt_handler->addUser($avatar->getVar('avatar_id'), $icmsUser->getVar('uid'));
                    redirect_header('userinfo.php?t='.time().'&amp;uid='.$icmsUser->getVar('uid'),0, _PROFILE_MA_PROFUPDATED);
                }
            }
        }
        include ICMS_ROOT_PATH.'/header.php';
        echo $uploader->getErrors();
    }
}

if ($op == 'avatarchoose') {
    if (!$GLOBALS['xoopsSecurity']->check()) {
        redirect_header('index.php',3,_PROFILE_MA_NOEDITRIGHT."<br />".implode('<br />', $GLOBALS['xoopsSecurity']->getErrors()));
        exit;
    }
    $uid = 0;
    if (!empty($_POST['uid'])) {
        $uid = intval($_POST['uid']);
    }
    if (empty($uid) || $icmsUser->getVar('uid') != $uid ) {
        redirect_header('index.php', 3, _PROFILE_MA_NOEDITRIGHT);
        exit();
    }
    $user_avatar = '';
    if (!empty($_POST['user_avatar'])) {
        $user_avatar = trim($_POST['user_avatar']);
    }
    $user_avatarpath = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH.'/'.$user_avatar));
    if (0 === strpos($user_avatarpath, ICMS_UPLOAD_PATH) && is_file($user_avatarpath)) {
        $oldavatar = $icmsUser->getVar('user_avatar');
        $icmsUser->setVar('user_avatar', $user_avatar);
        $member_handler =& xoops_gethandler('member');
        if (!$member_handler->insertUser($icmsUser)) {
            include ICMS_ROOT_PATH.'/header.php';
            echo $icmsUser->getHtmlErrors();
            include ICMS_ROOT_PATH.'/footer.php';
            exit();
        }
        $avt_handler =& xoops_gethandler('avatar');
        if ($oldavatar && $oldavatar != 'blank.gif' && !preg_match("/^savt/", strtolower($oldavatar))) {
            $avatars =& $avt_handler->getObjects(new Criteria('avatar_file', $oldavatar));
            if (is_object($avatars[0])) {
                $avt_handler->delete($avatars[0]);
            }
            $oldavatar_path = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH.'/'.$oldavatar));
            if (0 === strpos($oldavatar_path, ICMS_UPLOAD_PATH) && is_file($oldavatar_path)) {
                unlink($oldavatar_path);
            }
        }
        if ($user_avatar != 'blank.gif') {
            $avatars =& $avt_handler->getObjects(new Criteria('avatar_file', $user_avatar));
            if (is_object($avatars[0])) {
                $avt_handler->addUser($avatars[0]->getVar('avatar_id'), $icmsUser->getVar('uid'));
            }
        }
    }
    redirect_header('userinfo.php?uid='.$uid, 0, _PROFILE_MA_PROFUPDATED);
}
include ICMS_ROOT_PATH.'/footer.php';
?>