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
 * @version         $Id: activate.php 21139 2011-03-20 20:58:11Z m0nty_ $
 */

//$xoopsOption['pagetype'] = "user";
include '../../mainfile.php';
include ICMS_ROOT_PATH.'/header.php';

if (isset($_REQUEST['op']) && $_REQUEST['op'] == "actv") {
	icms_loadLanguageFile('core', 'user');
	$id = (int)$_GET['id'];
	if (empty($id)) redirect_header(ICMS_URL, 3, _NOPERM);

	$member_handler = icms::handler('icms_member');
	$thisuser = $member_handler->getUser($id);
	if (!is_object($thisuser)) redirect_header(ICMS_URL, 3, _NOPERM);
	if ($thisuser->getVar('actkey') != trim($_GET['actkey'])) redirect_header(ICMS_URL, 3, _MD_PROFILE_ACTKEYNOT);
	if ($thisuser->getVar('level') > 0 ) redirect_header(ICMS_URL.'/modules/'.basename(dirname(__FILE__)).'/index.php', 3, _MD_PROFILE_ACONTACT);

	if (false != $member_handler->activateUser($thisuser)) {
		if (icms::$module->config['activation_type'] == 2) {
			$icmsMailer = new icms_messaging_Handler();
			$icmsMailer->useMail();
			$icmsMailer->setTemplateDir(ICMS_ROOT_PATH.'/modules/'.basename(dirname(__FILE__)).'/language/'.$icmsConfig['language'].'/mail_template');
			$icmsMailer->setTemplate('activated.tpl');
			$icmsMailer->assign('SITENAME', $icmsConfig['sitename']);
			$icmsMailer->assign('ADMINMAIL', $icmsConfig['adminmail']);
			$icmsMailer->assign('SITEURL', ICMS_URL);
			$icmsMailer->setToUsers($thisuser);
			$icmsMailer->setFromEmail($icmsConfig['adminmail']);
			$icmsMailer->setFromName($icmsConfig['sitename']);
			$icmsMailer->setSubject(sprintf(_MD_PROFILE_YOURACCOUNT, $icmsConfig['sitename']));

			if (!$icmsMailer->send()) {
				printf(_MD_PROFILE_ACTVMAILNG, $thisuser->getVar('uname'));
			} else {
				printf(_MD_PROFILE_ACTVMAILOK, $thisuser->getVar('uname'));
			}
		} else {
			redirect_header(ICMS_URL.'/user.php', 3, _MD_PROFILE_ACTLOGIN);
		}
	} else {
		redirect_header(ICMS_URL.'/index.php', 3, _MD_PROFILE_ACTFAILED);
	}
} elseif (!isset($_REQUEST['submit']) || !isset($_REQUEST['email']) || trim($_REQUEST['email']) == "") {
	$form = new icms_form_Theme('', 'form', 'activate.php');
	$form->addElement(new icms_form_elements_Text(_MD_PROFILE_EMAIL, 'email', 25, 255));
	$form->addElement(new icms_form_elements_Button('', 'submit', _SUBMIT, 'submit'));
	$form->display();
} else {
	$member_handler = icms::handler('icms_member');
	$getuser = $member_handler->getUsers(new icms_db_criteria_Item('email', icms_core_DataFilter::addSlashes(trim($_REQUEST['email']))));
	if (count($getuser) == 0) redirect_header(ICMS_URL, 2, _MD_PROFILE_SORRYNOTFOUND);
	if ($getuser[0]->isActive()) redirect_header(ICMS_URL, 2, sprintf(_MD_PROFILE_USERALREADYACTIVE, $getuser[0]->getVar('email')));
	if ($getuser[0]->isDisabled()) redirect_header(ICMS_URL, 2, sprintf(_MD_PROFILE_USERDISABLED, $getuser[0]->getVar('email')));

	$icmsMailer = new icms_messaging_Handler();
	$icmsMailer->useMail();
	$icmsMailer->setTemplate('register.tpl');
	$icmsMailer->setTemplateDir(ICMS_ROOT_PATH.'/modules/'.icms::$module->getVar('dirname').'/language/'.$icmsConfig['language'].'/mail_template/');
	$icmsMailer->assign('SITENAME', $icmsConfig['sitename']);
	$icmsMailer->assign('ADMINMAIL', $icmsConfig['adminmail']);
	$icmsMailer->assign('SITEURL', ICMS_URL);
	$icmsMailer->setToUsers($getuser[0]);
	$icmsMailer->setFromEmail($icmsConfig['adminmail']);
	$icmsMailer->setFromName($icmsConfig['sitename']);
	$icmsMailer->setSubject($icmsMailer->setSubject(sprintf(_MD_PROFILE_USERKEYFOR, $getuser[0]->getVar('uname'))));

	if (!$icmsMailer->send()) {
		echo _MD_PROFILE_YOURREGMAILNG;
	} else {
		echo _MD_PROFILE_YOURREGISTERED;
	}
}

include ICMS_ROOT_PATH.'/footer.php';