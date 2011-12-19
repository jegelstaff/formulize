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

$profile_template = 'profile_changepass.html';
include 'header.php';

if (!$icmsUser) redirect_header(ICMS_URL, 2, _NOPERM);
if (!isset($_POST['submit'])) {
    //show change password form
    include_once ICMS_ROOT_PATH.'/class/xoopsformloader.php';
    $form = new XoopsThemeForm(_PROFILE_MA_CHANGEPASSWORD, 'form', $_SERVER['REQUEST_URI'], 'post', true);
    //$form->addElement(new XoopsFormLabel(_PROFILE_MA_USERLOGINNAME, $icmsUser->getVar('login_name', 'e'), "uname"), true);
    $form->addElement(new XoopsFormPassword(_PROFILE_MA_OLDPASSWORD, 'oldpass', 10, 50), true);
    $pwd_text = new XoopsFormPassword('', 'password', 10, 255, '', false, ($icmsConfigUser['pass_level']?'password_adv':''));
    $pwd_text2 = new XoopsFormPassword('', 'vpass', 10, 255);
    $pwd_tray = new XoopsFormElementTray(_PROFILE_MA_NEWPASSWORD.'<br />'._PROFILE_MA_VERIFYPASS);
    $pwd_tray->addElement($pwd_text);
    $pwd_tray->addElement($pwd_text2);
    $form->addElement($pwd_tray);
    $form->addElement(new XoopsFormButton('', 'submit', _SUBMIT, 'submit'));
    $form->assign($xoopsTpl);

	$xoopsTpl->assign('module_home', icms_getModuleName(true));
	$xoopsTpl->assign('categoryPath', _PROFILE_MA_CHANGEPASSWORD);

} else {
    include_once ICMS_ROOT_PATH.'/modules/'.$icmsModule->getVar('dirname').'/include/functions.php';
    $stop = checkPassword($icmsUser->getVar('login_name'), $_POST['oldpass'], $_POST['password'], $_POST['vpass']);
    if ($stop != '') {
        redirect_header(ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/userinfo.php?uid='.$icmsUser->getVar('uid'), 2, $stop);
    }
    else {
        //update password
          include_once ICMS_ROOT_PATH.'/class/icms_Password.php';
          $icmspass = new icms_Password();
          $salt = $icmspass->icms_createSalt();
          $pass = $icmspass->icms_encryptPass($_POST['password'], $salt);
          $icmsUser->setVar('pass', $pass);
          $icmsUser->setVar('enc_type', $icmsConfigUser['enc_type']);
          $icmsUser->setVar('pass_expired', 0);
          $icmsUser->setVar('salt', $salt);
          // Now we are using salt so this is not required!!
        //$icmsUser->setVar('pass', md5($_POST['newpass']));

        $member_handler =& xoops_gethandler('member');
        if ($member_handler->insertUser($icmsUser)) {
            redirect_header(ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/userinfo.php?uid='.$icmsUser->getVar('uid'), 2, _PROFILE_MA_PASSWORDCHANGED);
        }
        redirect_header(ICMS_URL.'/modules/'.basename( dirname( __FILE__ ) ).'/userinfo.php?uid='.$icmsUser->getVar('uid'), 2, _PROFILE_MA_ERRORDURINGSAVE);
    }
}

include 'footer.php';
?>