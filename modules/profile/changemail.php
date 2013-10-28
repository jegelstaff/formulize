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
 * @version         $Id: changemail.php 21139 2011-03-20 20:58:11Z m0nty_ $
 */

include '../../mainfile.php';
if (!icms::$user || !$icmsConfigUser['allow_chgmail']) redirect_header(ICMS_URL.'/modules/'.basename(dirname(__FILE__)), 3, _NOPERM);
$profile_template = 'profile_changemail.html';
include 'header.php';

if (!isset($_POST['submit']) && !isset($_REQUEST['oldmail'])) {
	//show change password form
	$form = new icms_form_Theme(_MD_PROFILE_CHANGEMAIL, 'form', $_SERVER['REQUEST_URI'], 'post', true);
	$form->addElement(new icms_form_elements_Text(_MD_PROFILE_NEWMAIL, 'newmail', 15, 50), true);
	$form->addElement(new icms_form_elements_Button('', 'submit', _SUBMIT, 'submit'));
	$form->assign($icmsTpl);
} else {
	//compute unique key
	$key = md5(substr(icms::$user->getVar('pass'), 0, 5));
	if (!isset($_REQUEST['oldmail'])) {
		if (!icms_core_DataFilter::checkVar($_POST['newmail'], 'email', 0, 1)) {
			redirect_header(ICMS_URL.'/modules/'.basename(dirname(__FILE__)).'changemail.php', 2, _MD_PROFILE_INVALIDMAIL);
		}

		//send email to new email address with key
		$icmsMailer = new icms_messaging_Handler();
		$icmsMailer->useMail();
		$icmsMailer->setTemplateDir(ICMS_ROOT_PATH.'/modules/'.basename(dirname(__FILE__)).'/language/'.$icmsConfig['language'].'/mail_template');
		$icmsMailer->setTemplate('changemail.tpl');
		$icmsMailer->assign('SITENAME', $icmsConfig['sitename']);
		$icmsMailer->assign('X_UNAME', icms::$user->getVar('uname'));
		$icmsMailer->assign('ADMINMAIL', $icmsConfig['adminmail']);
		$icmsMailer->assign('SITEURL', ICMS_URL);
		$icmsMailer->assign('IP', $_SERVER['REMOTE_ADDR']);
		$icmsMailer->assign('NEWEMAIL_LINK', ICMS_URL.'/modules/'.basename(dirname(__FILE__)).'/changemail.php?code='.$key.'&oldmail='.icms::$user->getVar('email'));
		$icmsMailer->assign('NEWEMAIL', $_POST['newmail']);
		$icmsMailer->setToEmails($_POST['newmail']);
		$icmsMailer->setFromEmail($icmsConfig['adminmail']);
		$icmsMailer->setFromName($icmsConfig['sitename']);
		$icmsMailer->setSubject(sprintf(_MD_PROFILE_NEWEMAILREQ,$icmsConfig['sitename']));

		if ($icmsMailer->send()) {
			//set proposed email as the user's newemail
			$profile_profile_handler = icms_getModuleHandler('profile', basename(dirname(__FILE__)), 'profile');
			$profile = $profile_profile_handler->get(icms::$user->getVar('uid'));
			$profile->setVar('newemail', $_POST['newmail']);
			if ($profile_profile_handler->insert($profile)) redirect_header(ICMS_URL.'/', 2, _MD_PROFILE_NEWMAILMSGSENT);
		} else {
			//relevant error messages
			echo $icmsMailer->getErrors();
		}
	} else {
		//check unique key
		if (!isset($_GET['code'])) redirect_header(ICMS_URL, 2, _MD_PROFILE_CONFCODEMISSING);
		$code = trim($_GET['code']);
		if ($code != $key) redirect_header (ICMS_URL.'/modules/'.basename(dirname(__FILE__)), 3, _MD_PROFILE_CONFCODEWRONG);

		//change email address to the proposed on
		$profile_profile_handler = icms_getModuleHandler('profile', basename(dirname(__FILE__)), 'profile');
		$profile = $profile_profile_handler->get(icms::$user->getVar('uid'));
		icms::$user->setVar('email', $profile->getVar('newemail'));

		//update user data
		if (icms::handler('icms_member')->insertUser(icms::$user, true)) {
			redirect_header(ICMS_URL.'/modules/'.basename(dirname(__FILE__)), 2, _MD_PROFILE_EMAILCHANGED);
		} else {
			echo implode('<br />', icms::$user->getErrors());
		}
	}
}
$icmsTpl->assign('profile_category_path', _MD_PROFILE_CHANGEMAIL);

include 'footer.php';