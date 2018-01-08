<?php
/**
 * Extended User Profile
 *
 * @copyright       The ImpressCMS Project http://www.impresscms.org/
 * @license         LICENSE.txt
 * @license			GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package         modules
 * @since           1.2
 * @author          Jan Pedersen
 * @author          The SmartFactory <www.smartfactory.ca>
 * @author	   		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version         $Id: changepass.php 21139 2011-03-20 20:58:11Z m0nty_ $
 */

$profile_template = 'profile_changepass.html';
include 'header.php';

if (!icms::$user) redirect_header(ICMS_URL, 2, _NOPERM);
if (!isset($_POST['submit'])) {
	//show change password form
	$form = new icms_form_Theme(_MD_PROFILE_CHANGEPASSWORD, 'form', $_SERVER['REQUEST_URI'], 'post', true);
	$form->addElement(new icms_form_elements_Password(_MD_PROFILE_OLDPASSWORD, 'oldpass', 10, 50), true);
	$pwd_tray = new icms_form_elements_Tray(_MD_PROFILE_NEWPASSWORD.'<br />'._MD_PROFILE_VERIFYPASS);
	$pwd_tray->addElement(new icms_form_elements_Password('', 'password', 10, 255, '', false, ($icmsConfigUser['pass_level'] ? 'password_adv' : '')));
	$pwd_tray->addElement(new icms_form_elements_Password('', 'vpass', 10, 255));
	$form->addElement($pwd_tray);
	$form->addElement(new icms_form_elements_Button('', 'submit', _SUBMIT, 'submit'));
	$form->assign($icmsTpl);
} else {
	$stop = '';
	$member_handler = icms::handler('icms_member');
	$username = icms::$user->getVar('uname');
	$password = !empty($_POST['password']) ? icms_core_DataFilter::stripSlashesGPC(trim($_POST['password'])) : '';
	$oldpass = !empty($_POST['oldpass']) ? icms_core_DataFilter::stripSlashesGPC(trim($_POST['oldpass'])) : '';
	$vpass = !empty($_POST['vpass']) ? icms_core_DataFilter::stripSlashesGPC(trim($_POST['vpass'])) : '';
	if (empty($password) || empty($oldpass) || empty($vpass)) {
		$stop .=  _MD_PROFILE_PROVIDEPWDS;
	} else {
		icms_loadLanguageFile('core', 'user');
		if (!$member_handler->loginUser(addslashes(icms::$user->getVar('login_name')), addslashes($oldpass))) $stop .= _US_BADPWD."<br />";
		if (strlen($password) < $icmsConfigUser['minpass']) $stop .= sprintf(_US_PWDTOOSHORT, $icmsConfigUser['minpass'])."<br />";
		if ($password != $vpass) $stop .= _US_PASSNOTSAME."<br />";
		if ($password == $username || $password == icms_core_DataFilter::utf8_strrev($username, true) || strripos($password, $username) === true) $stop .= _US_BADPWD;
	}

	if ($stop != '') {
		redirect_header(PROFILE_URL.'changepass.php', 2, $stop);
	} else {
		$icmspass = new icms_core_Password();
		$salt = icms_core_Password::createSalt();
		$pass = $icmspass->encryptPass($_POST['password'], $salt, $icmsConfigUser['enc_type']);
		icms::$user->setVar('salt', $salt, true);
		icms::$user->setVar('pass', $pass, true);
		icms::$user->setVar('enc_type', $icmsConfigUser['enc_type'], true);

		if ($member_handler->insertUser(icms::$user)) {
			redirect_header(PROFILE_URL.'/userinfo.php?uid='.icms::$user->getVar('uid'), 2, _MD_PROFILE_PASSWORDCHANGED);
		} else {
			redirect_header(PROFILE_URL.'/userinfo.php?uid='.icms::$user->getVar('uid'), 2, _MD_PROFILE_ERRORDURINGSAVE);
		}
	}
}
$icmsTpl->assign('profile_category_path', _MD_PROFILE_CHANGEPASSWORD);

include 'footer.php';