<?php
/**
 * Extended User Profile
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		LICENSE.txt
 * @license		GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package		modules
 * @since		1.2
 * @author		Jan Pedersen
 * @author		The SmartFactory <www.smartfactory.ca>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: register.php 21139 2011-03-20 20:58:11Z m0nty_ $
 */

include_once '../../mainfile.php';
$xoopsOption['template_main'] = 'profile_register.html';
include ICMS_ROOT_PATH.'/header.php';

if (icms::$user && icms::$module->config['profile_social']) {
	header('location: index.php?uid='.icms::$user->getVar('uid'));
	exit();
} elseif (icms::$user && !icms::$module->config['profile_social']) {
	header('location: userinfo.php?uid='.icms::$user->getVar('uid'));
	exit();
}

if (empty($icmsConfigUser['allow_register'])) redirect_header(ICMS_URL.'/', 6, _MD_PROFILE_NOREGISTER);
if ($icmsConfigUser['pass_level'] > 20) icms_PasswordMeter();
$member_handler = icms::handler('icms_member');
if (count($_POST) == 0) unset($_SESSION['profile']);
$template_dir = ICMS_ROOT_PATH.'/language/'.$icmsConfig['language'].'/mail_template';
if (!file_exists($template_dir)) $template_dir = ICMS_ROOT_PATH.'/language/english/mail_template';

$newuser = isset($_SESSION['profile']['uid']) ? $member_handler->getUser($_SESSION['profile']['uid']) : $member_handler->createUser();

$profile_handler = icms_getmodulehandler('profile', basename(dirname(__FILE__)), 'profile');
$profile = $profile_handler->get($newuser->getVar('uid'));

$op = !isset($_POST['op']) ? 'register' : $_POST['op'];

$current_step = isset($_POST['step']) ? $_POST['step'] : 0;
$criteria = new icms_db_criteria_Compo();
$criteria->setSort('step_order');
$regstep_handler = icms_getmodulehandler('regstep', basename(dirname(__FILE__)), 'profile');
$steps = $regstep_handler->getObjects($criteria);
if (count($steps) == 0) redirect_header(ICMS_URL.'/', 6, _MD_PROFILE_NOSTEPSAVAILABLE);

switch ($op) {
	case 'step':
		// Get dynamic fields
		$fields = $profile_handler->loadFields();
		if (count($fields) > 0) {
			foreach (array_keys($fields) as $i) {
				$fieldname = $fields[$i]->getVar('field_name');
				if (isset($_POST[$fieldname])) {
					if ($fields[$i]->getVar('field_type') == 'date' || $fields[$i]->getVar('field_type') == 'longdate') {
						$_SESSION['profile'][$fieldname] = trim(strtotime($_POST[$fieldname]));
					} elseif($fields[$i]->getVar('field_type') == 'datetime') {
						$_SESSION['profile'][$fieldname] = trim(strtotime($_POST[$fieldname]['date']) + $_POST[$fieldname]['time']);
					} else {
						$_SESSION['profile'][$fieldname] = trim($_POST[$fieldname]);
					}
				}
			}
		}
		// if first step was previous step, check user data as they will always be at first step
		if ($current_step == 0) {
			$stop = '';

			$login_name = isset($_POST['login_name']) ? trim($_POST['login_name']) : '';
			$uname = isset($_POST['uname']) ? trim($_POST['uname']) : '';
			$email = isset($_POST['email']) ? trim($_POST['email']) : '';
			$vpass = isset($_POST['vpass']) ? icms_core_DataFilter::stripSlashesGPC($_POST['vpass']) : '';
			$pass = isset($_POST['pass']) ? icms_core_DataFilter::stripSlashesGPC($_POST['pass']) : '';

			$icmspass = new icms_core_Password();
			$salt = icms_core_Password::createSalt();
			$enc_pass = $icmspass->encryptPass($pass, $salt, $icmsConfigUser['enc_type']);

			if ($icmsConfigUser['use_captcha'] == 1) {
				$icmsCaptcha = icms_form_elements_captcha_Object::instance();
				if (!$icmsCaptcha->verify()) $stop .= $icmsCaptcha->getMessage().'<br />';
			}

			if ($icmsConfigUser['reg_dispdsclmr'] != 0 && $icmsConfigUser['reg_disclaimer'] != '' && !isset($_POST['agree_disc'])) $stop .= _MD_PROFILE_UNEEDAGREE.'<br />';
			icms_loadLanguageFile('core', 'user');
			$stop .= icms::handler('icms_member_user')->userCheck($login_name, $uname, $email, $pass, $vpass);
			if (empty($stop)) {
				$_SESSION['profile']['login_name'] = $login_name;
				$_SESSION['profile']['uname'] = $uname;
				$_SESSION['profile']['email'] = $email;
				$_SESSION['profile']['salt'] = $salt;
				$_SESSION['profile']['pass'] = $enc_pass;
				$_SESSION['profile']['enc_type'] = $icmsConfigUser['enc_type'];
				$_SESSION['profile']['user_avatar'] = 'blank.gif';
				$_SESSION['profile']['uorder'] = $icmsConfig['com_order'];
				$_SESSION['profile']['umode'] = $icmsConfig['com_mode'];
				$_SESSION['profile']['actkey'] = substr(icms_core_Password::createSalt(8), 0, 8);
			}
		}
		// Set vars
		$uservars = $profile_handler->getUserVars();
		foreach ($_SESSION['profile'] as $field => $value) {
			if (in_array($field, $uservars)) {
				$newuser->setVar($field, $value);
			} else {
				$profile->setVar($field, $value);
			}
		}
		if (empty($stop)) {
			$save = false;
			for ($i = 0; $i <= $current_step; $i++) if ($steps[$i]->getVar('step_save')) $save = true;
			if ($save) {
				if (!$member_handler->insertUser($newuser)) {
					$stop .= _MD_PROFILE_REGISTERNG.'<br />';
					$stop .= implode('<br />', $newuser->getErrors());
					$icmsTpl->assign('stop', $stop);
				} else {
					$_SESSION['profile']['uid'] = $newuser->getVar('uid');
					$profile->setVar('profileid', $newuser->getVar('uid'));
					$profile_handler->insert($profile);
					if ($newuser->isNew()) $icmsTpl->append('confirm', postSaveProcess($newuser));
				}
			}
			if (!empty($stop) || isset($steps[$current_step+1])) {
				// There are errors or we can proceed to next step
				$next_step = empty($stop) ? $current_step + 1 : $current_step;
				include_once 'include/forms.php';
				// Set field persistance - load newuser with session vars
				$uservars = $profile_handler->getUserVars();
				foreach ($_SESSION['profile'] as $field => $value) {
					if (in_array($field, $uservars)) $newuser->setVar($field, $value);
				}
				$reg_form = getRegisterForm($newuser, $profile, $next_step, $steps[$next_step]);
				$reg_form->assign($icmsTpl);
				$icmsTpl->assign('stop', $stop);
			} else {
				// No errors and no more steps, finish
				$icmsTpl->append('confirm', _MD_PROFILE_REGISTER_FINISH);
			}
		} else {
			include_once 'include/forms.php';
			// Set field persistance - load newuser with session vars
			$uservars = $profile_handler->getUserVars();
			foreach ($_SESSION['profile'] as $field => $value) {
				if (in_array($field, $uservars)) $newuser->setVar($field, $value);
			}
			$reg_form = getRegisterForm($newuser, $profile, $current_step, $steps[$current_step]);
			$reg_form->assign($icmsTpl);
			$icmsTpl->assign('stop', $stop);
		}
		break;
	case 'register':
	default:
		include_once 'include/forms.php';
		$reg_form = getRegisterForm($newuser, $profile, 0, $steps[0]);
		$reg_form->assign($icmsTpl);
		break;
}

include 'footer.php';

function postSaveProcess($newuser) {
	global $icmsConfigUser, $icmsConfig, $template_dir, $member_handler;
	$newid = (int) $newuser->getVar('uid');

	if (!$member_handler->addUserToGroup(ICMS_GROUP_USERS, $newid)) return _MD_PROFILE_REGISTERNG;
	if ($icmsConfigUser['new_user_notify'] == 1 && !empty($icmsConfigUser['new_user_notify_group'])) {
		$icmsMailer = new icms_messaging_Handler();
		$icmsMailer->useMail();
		$icmsMailer->setToGroups($member_handler->getGroup($icmsConfigUser['new_user_notify_group']));
		$icmsMailer->setFromEmail($icmsConfig['adminmail']);
		$icmsMailer->setFromName($icmsConfig['sitename']);
		$icmsMailer->setSubject(sprintf(_MD_PROFILE_NEWUSERREGAT,$icmsConfig['sitename']));
		$icmsMailer->setBody(sprintf(_MD_PROFILE_HASJUSTREG, $newuser->getVar('uname')));
		$icmsMailer->send(true);
	}
	if ($icmsConfigUser['activation_type'] == 1) return '';
	if ($icmsConfigUser['activation_type'] == 0) {
		$icmsMailer = new icms_messaging_Handler();
		$icmsMailer->useMail();
		$icmsMailer->setTemplate('register.tpl');
		$icmsMailer->setTemplateDir($template_dir);
		$icmsMailer->assign('X_SITENAME', $icmsConfig['sitename']);
		$icmsMailer->assign('X_ADMINMAIL', $icmsConfig['adminmail']);
		$icmsMailer->assign('X_SITEURL', ICMS_URL.'/');
		$icmsMailer->assign('X_USERPASSWORD', $_POST['vpass']);
		$icmsMailer->assign('X_USERLOGINNAME', $_POST['login_name']);
		$icmsMailer->setToUsers(new icms_member_user_Object($newid));
		$icmsMailer->setFromEmail($icmsConfig['adminmail']);
		$icmsMailer->setFromName($icmsConfig['sitename']);
		$icmsMailer->setSubject(sprintf(_MD_PROFILE_USERKEYFOR, $newuser->getVar('uname')));

		if (!$icmsMailer->send(true) ) {
			return _MD_PROFILE_YOURREGMAILNG;
		} else {
			return _MD_PROFILE_YOURREGISTERED;
		}
	} elseif ($icmsConfigUser['activation_type'] == 2) {
		$icmsMailer = new icms_messaging_Handler();
		$icmsMailer->useMail();
		$icmsMailer->setTemplate('adminactivate.tpl');
		$icmsMailer->setTemplateDir($template_dir);
		$icmsMailer->assign('USERNAME', $newuser->getVar('uname'));
		$icmsMailer->assign('USERLOGINNAME', $newuser->getVar('login_name'));
		$icmsMailer->assign('USEREMAIL', $newuser->getVar('email'));
		$icmsMailer->assign('USERACTLINK', ICMS_URL.'/user.php?op=actv&id='.$newid.'&actkey='.$newuser->getVar('actkey'));
		$icmsMailer->assign('SITENAME', $icmsConfig['sitename']);
		$icmsMailer->assign('ADMINMAIL', $icmsConfig['adminmail']);
		$icmsMailer->assign('SITEURL', ICMS_URL);
		$icmsMailer->setToGroups($member_handler->getGroup($icmsConfigUser['activation_group']));
		$icmsMailer->setFromEmail($icmsConfig['adminmail']);
		$icmsMailer->setFromName($icmsConfig['sitename']);
		$icmsMailer->setSubject(sprintf(_MD_PROFILE_USERKEYFOR, $newuser->getVar('uname')));

		if (!$icmsMailer->send(true)) {
			return _MD_PROFILE_YOURREGMAILNG;
		} else {
			return _MD_PROFILE_YOURREGISTERED2;
		}
	}

	return '';
}