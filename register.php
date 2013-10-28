<?php
/**
 * Registration process for new users
 * Gathers required information and validates the new user
 *  
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		Member
 * @subpackage	Users
 * @version		SVN: $Id: register.php 21047 2011-03-14 15:52:14Z m0nty_ $
 */

$xoopsOption['pagetype'] = 'user';

include 'mainfile.php';
if (icms_get_module_status('profile') && file_exists(ICMS_MODULES_PATH . '/profile/register.php')) {
	header('Location: ' . ICMS_MODULES_URL . '/profile/register.php');
	exit();
}

if ($icmsConfigUser['allow_register'] == 0 && $icmsConfigUser['activation_type'] != 3) {
	redirect_header('index.php', 6, _US_NOREGISTER);
}
if (is_object(icms::$user)) {
	redirect_header('index.php', 6, _US_ALREADY_LOGED_IN);
}
$op = !isset($_POST['op']) ? 'register' : filter_input(INPUT_POST, 'op');
$login_name = isset($_POST['login_name']) ? icms_core_DataFilter::stripSlashesGPC($_POST['login_name']) : '';
$uname = isset($_POST['uname']) ? icms_core_DataFilter::stripSlashesGPC($_POST['uname']) : '';
$email = isset($_POST['email']) ? trim(icms_core_DataFilter::stripSlashesGPC($_POST['email'])) : '';
$url = isset($_POST['url']) ? trim(icms_core_DataFilter::stripSlashesGPC($_POST['url'])) : '';
$pass = isset($_POST['pass']) ? icms_core_DataFilter::stripSlashesGPC($_POST['pass']) : '';
$vpass = isset($_POST['vpass']) ? icms_core_DataFilter::stripSlashesGPC($_POST['vpass']) : '';
$timezone_offset = isset($_POST['timezone_offset']) ? (float)($_POST['timezone_offset']) : $icmsConfig['default_TZ'];
$user_viewemail = (isset($_POST['user_viewemail']) && (int) $_POST['user_viewemail']) ? 1 : 0;
$user_mailok = (isset($_POST['user_mailok']) && (int) $_POST['user_mailok']) ? 1 : 0;
$agree_disc = (isset($_POST['agree_disc']) && (int) $_POST['agree_disc']) ? 1 : 0;
$actkey = isset($_POST['actkey']) ? trim(icms_core_DataFilter::stripSlashesGPC($_POST['actkey'])) : '';
$salt = isset($_POST['salt']) ? trim(icms_core_DataFilter::stripSlashesGPC($_POST['salt'])) : '';
$enc_type = $icmsConfigUser['enc_type'];

$thisuser = icms::handler('icms_member_user');
switch ($op) {
	case 'newuser':
		include 'header.php';
		$xoTheme->addScript('', array('type' => ''), '
				$(".password").passStrength({
					shortPass: 		"top_shortPass",
					badPass:		"top_badPass",
					goodPass:		"top_goodPass",
					strongPass:		"top_strongPass",
					baseStyle:		"top_testresult",
					messageloc:		0
				});
			});
		');
		$stop = '';
		if (!icms::$security->check()) {
			$stop .= implode('<br />', icms::$security->getErrors()) . "<br />";
		}
		if ($icmsConfigUser['reg_dispdsclmr'] != 0 && $icmsConfigUser['reg_disclaimer'] != '') {
			if (empty($agree_disc)) {
				$stop .= _US_UNEEDAGREE . '<br />';
			}
		}
		$stop .= $thisuser->userCheck($login_name, $uname, $email, $pass, $vpass);
		if (empty($stop)) {
			echo _US_LOGINNAME . ": " . icms_core_DataFilter::htmlSpecialChars($login_name) . "<br />"
				. _US_NICKNAME . ": " . icms_core_DataFilter::htmlSpecialChars($uname) . "<br />"
				. _US_EMAIL . ": " . icms_core_DataFilter::htmlSpecialChars($email) . "<br />";
			if ($url != '') {
				$url = formatURL($url);
				echo _US_WEBSITE . ': ' . icms_core_DataFilter::htmlSpecialChars($url) . '<br />';
			}
			$f_timezone = ($timezone_offset < 0) ? 'GMT ' . $timezone_offset : 'GMT +' . $timezone_offset;
			echo _US_TIMEZONE . ": $f_timezone<br />";
			echo "<form action='register.php' method='post'><input type='hidden' name='login_name' value='" 
				. icms_core_DataFilter::htmlSpecialChars($login_name) 
				. "' /><input type='hidden' name='uname' value='" . icms_core_DataFilter::htmlSpecialChars($uname) 
				. "' /><input type='hidden' name='email' value='" . icms_core_DataFilter::htmlSpecialChars($email) 
				. "' /><input type='hidden' name='user_viewemail' value='" . (int) $user_viewemail 
				. "' /><input type='hidden' name='timezone_offset' value='" . $timezone_offset 
				. "' /><input type='hidden' name='url' value='" . icms_core_DataFilter::htmlSpecialChars($url) 
				. "' /><input type='hidden' name='pass' value='" . icms_core_DataFilter::htmlSpecialChars($pass) 
				. "' /><input type='hidden' name='vpass' value='" . icms_core_DataFilter::htmlSpecialChars($vpass) 
				. "' /><input type='hidden' name='user_mailok' value='" . (int) $user_mailok 
				. "' /><input type='hidden' name='actkey' value='" . icms_core_DataFilter::htmlSpecialChars($actkey) 
				. "' /><input type='hidden' name='salt' value='" . icms_core_DataFilter::htmlSpecialChars($salt) 
				. "' /><input type='hidden' name='enc_type' value='". (int) $enc_type 
				. "' /><input type='hidden' name='agree_disc' value='" . (int) $agree_disc 
				. "' /><br /><br /><input type='hidden' name='op' value='finish' />" . icms::$security->getTokenHTML() 
				. "<input type='submit' value='". _US_FINISH ."' /></form>";
		} else {
			echo "<span style='color:#ff0000;'>$stop</span>";
			include 'include/registerform.php';
			$reg_form->display();
		}
		$xoopsTpl->assign('icms_pagetitle', _US_USERREG);
		include 'footer.php';
		break;
		
	case 'finish':
		include 'header.php';
		$stop = $thisuser->userCheck($login_name, $uname, $email, $pass, $vpass);
		if (!icms::$security->check()) {
			$stop .= implode('<br />', icms::$security->getErrors()) . "<br />";
		}
		if ($icmsConfigUser['use_captcha'] == 1) {
			$icmsCaptcha = icms_form_elements_captcha_Object::instance();
			if (! $icmsCaptcha->verify()) {
				$stop .= $icmsCaptcha->getMessage() . '<br />';

			}
		}

		if ($icmsConfigUser['reg_dispdsclmr'] != 0 && $icmsConfigUser['reg_disclaimer'] != '') {
			if (empty($agree_disc)) {
				$stop .= _US_UNEEDAGREE . '<br />';
			}
		}

		if (empty($stop)) {
			$member_handler = icms::handler('icms_member');
			$newuser =& $member_handler->createUser();
			$newuser->setVar('user_viewemail', $user_viewemail, TRUE);
			$newuser->setVar('login_name', $login_name, TRUE);
			$newuser->setVar('uname', $uname, TRUE);
			$newuser->setVar('email', $email, TRUE);
			if ($url != '') {
				$newuser->setVar('url', formatURL($url), TRUE);
			}
			$newuser->setVar('user_avatar', 'blank.gif', TRUE);
			include_once 'include/checkinvite.php';
			$valid_actkey = check_invite_code($actkey);
			$newuser->setVar('actkey', $valid_actkey ? $actkey : substr(md5(uniqid(mt_rand(), 1)), 0, 8), TRUE);

			$icmspass = new icms_core_Password();

			$salt = $icmspass->createSalt();
			$newuser->setVar('salt', $salt, TRUE);
			$pass1 = $icmspass->encryptPass($pass, $salt, $enc_type);
			$newuser->setVar('pass', $pass1, TRUE);
			$newuser->setVar('timezone_offset', $timezone_offset, TRUE);
			$newuser->setVar('user_regdate', time(), TRUE);
			$newuser->setVar('uorder', $icmsConfig['com_order'], TRUE);
			$newuser->setVar('umode', $icmsConfig['com_mode'], TRUE);
			$newuser->setVar('user_mailok', $user_mailok, TRUE);
			$newuser->setVar('enc_type', $enc_type, TRUE);
			$newuser->setVar('notify_method', 2);
			if ($valid_actkey || $icmsConfigUser['activation_type'] == 1) {
				$newuser->setVar('level', 1, TRUE);
			}
			if (!$member_handler->insertUser($newuser)) {
				echo _US_REGISTERNG;
				include 'footer.php';
				exit();
			}
			$newid = (int) $newuser->getVar('uid');
			if (!$member_handler->addUserToGroup(XOOPS_GROUP_USERS, $newid)) {
				echo _US_REGISTERNG;
				include 'footer.php';
				exit();
			}

			// Send notification about the new user register to the selected group if config is true on admin preferences
			if ($icmsConfigUser['new_user_notify'] == 1) {
				$newuser->newUserNotifyAdmin();
			}

			// update invite_code (if any)
			if ($valid_actkey) {
				update_invite_code($actkey, $newid);
			}
			if ($icmsConfigUser['activation_type'] == 1 || $icmsConfigUser['activation_type'] == 3) {
				redirect_header('index.php', 4, _US_ACTLOGIN);
				exit();
			}

			$thisuser = new icms_member_user_Object($newid);

			// Activation by user
			if ($icmsConfigUser['activation_type'] == 0) {
				$xoopsMailer = new icms_messaging_Handler();
				$xoopsMailer->useMail();
				$xoopsMailer->setTemplate('register.tpl');
				$xoopsMailer->setToUsers(new icms_member_user_Object($newid));
				$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
				$xoopsMailer->setFromName($icmsConfig['sitename']);
				$xoopsMailer->setSubject(sprintf(_US_USERKEYFOR, $uname));
				if (!$xoopsMailer->send()) {
					echo _US_YOURREGMAILNG;
				} else {
					echo _US_YOURREGISTERED;
				}
				// activation by admin
			} elseif ($icmsConfigUser['activation_type'] == 2) {
				$xoopsMailer = new icms_messaging_Handler();
				$xoopsMailer->useMail();
				$xoopsMailer->setTemplate('adminactivate.tpl');
				$xoopsMailer->assign('USERNAME', $uname);
				$xoopsMailer->assign('USERLOGINNAME', $login_name);
				$xoopsMailer->assign('USEREMAIL', $email);
				$xoopsMailer->assign('USERACTLINK', ICMS_URL . '/user.php?op=actv&amp;id=' . $newid . '&amp;actkey=' . $newuser->getVar('actkey'));
				$member_handler = icms::handler('icms_member');
				$xoopsMailer->setToGroups($member_handler->getGroup($icmsConfigUser['activation_group']));
				$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
				$xoopsMailer->setFromName($icmsConfig['sitename']);
				$xoopsMailer->setSubject(sprintf(_US_USERKEYFOR, $uname));
				if (!$xoopsMailer->send()) {
					echo _US_YOURREGMAILNG;
				} else {
					echo _US_YOURREGISTERED2;
				}
			}
		} else {
			echo "<span style='color:#ff0000; font-weight:bold;'>$stop</span>";
			include 'include/registerform.php';
			$reg_form->display();
		}
		$xoopsTpl->assign('icms_pagetitle', _US_USERREG);
		include 'footer.php';
		break;
		
	case 'register':
	default:
		$invite_code = isset($_GET['code']) ? filter_input(INPUT_GET, 'code') : NULL;
		if ($icmsConfigUser['activation_type'] == 3 || !empty($invite_code)) {
			include 'include/checkinvite.php';
			load_invite_code($invite_code);
		}
		// invite is ok, show register form
		include 'header.php';
		include 'include/registerform.php';
		$reg_form->display();
		$xoopsTpl->assign('icms_pagetitle', _US_USERREG);
		include 'footer.php';
		break;
}