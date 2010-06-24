<?php
/**
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		core
* @since		XOOPS
* @author		http://www.xoops.org The XOOPS Project
* @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id: register.php 9722 2010-01-09 23:02:57Z skenow $
*/
/**
 *  Registration process for new users
 *  Gathers required information and validates the new user
 *  @package kernel
 *  @subpackage users
 */
/**
 *
 */
$xoopsOption['pagetype'] = 'user';

include 'mainfile.php';
if(icms_get_module_status('profile') && file_exists(ICMS_ROOT_PATH.'/modules/profile/register.php'))
{
	header('Location: '.ICMS_URL.'/modules/profile/register.php');
	exit();
}

$myts =& MyTextSanitizer::getInstance();

if ($icmsConfigUser['allow_register'] == 0 && $icmsConfigUser['activation_type'] != 3) {
	redirect_header('index.php', 6, _US_NOREGISTER);
}
if(is_object('xoopsUser')){
	redirect_header('index.php', 6, _US_ALREADY_LOGED_IN);
}
$op = !isset($_POST['op']) ? 'register' : $_POST['op'];
$login_name = isset($_POST['login_name']) ? $myts->stripSlashesGPC($_POST['login_name']) : '';
$uname = isset($_POST['uname']) ? $myts->stripSlashesGPC($_POST['uname']) : '';
$email = isset($_POST['email']) ? trim($myts->stripSlashesGPC($_POST['email'])) : '';
$url = isset($_POST['url']) ? trim($myts->stripSlashesGPC($_POST['url'])) : '';
$pass = isset($_POST['pass']) ? $myts->stripSlashesGPC($_POST['pass']) : '';
$vpass = isset($_POST['vpass']) ? $myts->stripSlashesGPC($_POST['vpass']) : '';
$timezone_offset = isset($_POST['timezone_offset']) ? floatval($_POST['timezone_offset']) : $icmsConfig['default_TZ'];
$user_viewemail = (isset($_POST['user_viewemail']) && intval($_POST['user_viewemail'])) ? 1 : 0;
$user_mailok = (isset($_POST['user_mailok']) && intval($_POST['user_mailok'])) ? 1 : 0;
$agree_disc = (isset($_POST['agree_disc']) && intval($_POST['agree_disc'])) ? 1 : 0;
$actkey = isset($_POST['actkey']) ? trim($myts->stripSlashesGPC($_POST['actkey'])) : '';
$salt = isset($_POST['salt']) ? trim($myts->stripSlashesGPC($_POST['salt'])) : '';
$enc_type = $icmsConfigUser['enc_type'];

$thisuser = new XoopsUserHandler();
switch ( $op ) {
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
	if (!$GLOBALS['xoopsSecurity']->check()) {
	    $stop .= implode('<br />', $GLOBALS['xoopsSecurity']->getErrors())."<br />";
	}
	if ($icmsConfigUser['reg_dispdsclmr'] != 0 && $icmsConfigUser['reg_disclaimer'] != '') {
		if (empty($agree_disc)) {
			$stop .= _US_UNEEDAGREE.'<br />';
		}
	}
	$stop .= $thisuser->userCheck($login_name, $uname, $email, $pass, $vpass);
	if (empty($stop)) {
		echo _US_LOGINNAME.": ".$myts->htmlSpecialChars($login_name)."<br />";
		echo _US_NICKNAME.": ".$myts->htmlSpecialChars($uname)."<br />";
		echo _US_EMAIL.": ".$myts->htmlSpecialChars($email)."<br />";
		if ($url != '') {
			$url = formatURL($url);
			echo _US_WEBSITE.': '.$myts->htmlSpecialChars($url).'<br />';
		}
		$f_timezone = ($timezone_offset < 0) ? 'GMT '.$timezone_offset : 'GMT +'.$timezone_offset;
		echo _US_TIMEZONE.": $f_timezone<br />";
		echo "<form action='register.php' method='post'>
		<input type='hidden' name='login_name' value='".$myts->htmlSpecialChars($login_name)."' />
		<input type='hidden' name='uname' value='".$myts->htmlSpecialChars($uname)."' />
		<input type='hidden' name='email' value='".$myts->htmlSpecialChars($email)."' />";
		echo "<input type='hidden' name='user_viewemail' value='".$user_viewemail."' />
		<input type='hidden' name='timezone_offset' value='".$timezone_offset."' />
		<input type='hidden' name='url' value='".$myts->htmlSpecialChars($url)."' />
		<input type='hidden' name='pass' value='".$myts->htmlSpecialChars($pass)."' />
		<input type='hidden' name='vpass' value='".$myts->htmlSpecialChars($vpass)."' />
		<input type='hidden' name='user_mailok' value='".$user_mailok."' />
		<input type='hidden' name='actkey' value='".$myts->htmlSpecialChars($actkey)."' />
		<input type='hidden' name='salt' value='".$myts->htmlSpecialChars($salt)."' />
		<input type='hidden' name='enc_type' value='".intval($enc_type)."' />
		<input type='hidden' name='agree_disc' value='" . (int) $agree_disc . "' />
		<br /><br /><input type='hidden' name='op' value='finish' />".$GLOBALS['xoopsSecurity']->getTokenHTML()."<input type='submit' value='". _US_FINISH ."' /></form>";
	} else {
		echo "<span style='color:#ff0000;'>$stop</span>";
		include 'include/registerform.php';
		$reg_form->display();
	}
	$xoopsTpl->assign('xoops_pagetitle', _US_USERREG);
	include 'footer.php';
	break;
case 'finish':
	include 'header.php';
	$stop = $thisuser->userCheck($login_name, $uname, $email, $pass, $vpass);
	if (!$GLOBALS['xoopsSecurity']->check()) {
	    $stop .= implode('<br />', $GLOBALS['xoopsSecurity']->getErrors())."<br />";
	}
	if ($icmsConfigUser['use_captcha'] == 1) {
		include_once (ICMS_ROOT_PATH ."/class/captcha/captcha.php");
			include_once(ICMS_ROOT_PATH ."/class/xoopsformloader.php");
            $icmsCaptcha = IcmsCaptcha::instance();
            if(! $icmsCaptcha->verify() ) {
                   $stop .= $icmsCaptcha->getMessage().'<br />';

            }
    }


	if ($icmsConfigUser['reg_dispdsclmr'] != 0 && $icmsConfigUser['reg_disclaimer'] != '') {
		if (empty($agree_disc)) {
			$stop .= _US_UNEEDAGREE.'<br />';
		}
	}

	if ( empty($stop) ) {
		$member_handler =& xoops_gethandler('member');
		$newuser =& $member_handler->createUser();
		$newuser->setVar('user_viewemail',$user_viewemail, true);
		$newuser->setVar('login_name', $login_name, true);
		$newuser->setVar('uname', $uname, true);
		$newuser->setVar('email', $email, true);
		if ($url != '') {
			$newuser->setVar('url', formatURL($url), true);
		}
		$newuser->setVar('user_avatar','blank.gif', true);
		include_once 'include/checkinvite.php';
		$valid_actkey = check_invite_code($actkey);
		$newuser->setVar('actkey', $valid_actkey ? $actkey : substr(md5(uniqid(mt_rand(), 1)), 0, 8), true);

        include_once ICMS_ROOT_PATH.'/class/icms_Password.php';
        $icmspass = new icms_Password();

		$salt = $icmspass->icms_createSalt();
		$newuser->setVar('salt', $salt, true);
		$pass1 = $icmspass->icms_encryptPass($pass, $salt);
		$newuser->setVar('pass', $pass1, true);
		$newuser->setVar('timezone_offset', $timezone_offset, true);
		$newuser->setVar('user_regdate', time(), true);
		$newuser->setVar('uorder',$icmsConfig['com_order'], true);
		$newuser->setVar('umode',$icmsConfig['com_mode'], true);
		$newuser->setVar('user_mailok',$user_mailok, true);
		$newuser->setVar('enc_type',$enc_type, true);
		$newuser->setVar('notify_method', 2);
		if ($valid_actkey || $icmsConfigUser['activation_type'] == 1) {
			$newuser->setVar('level', 1, true);
		}
		if (!$member_handler->insertUser($newuser)) {
			echo _US_REGISTERNG;
			include 'footer.php';
			exit();
		}
		$newid = intval($newuser->getVar('uid'));
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

		$thisuser = new XoopsUser($newid);

		// Activation by user
		if ($icmsConfigUser['activation_type'] == 0) {
			$xoopsMailer =& getMailer();
			$xoopsMailer->useMail();
			$xoopsMailer->setTemplate('register.tpl');
			$xoopsMailer->setToUsers(new XoopsUser($newid));
			$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
			$xoopsMailer->setFromName($icmsConfig['sitename']);
			$xoopsMailer->setSubject(sprintf(_US_USERKEYFOR, $uname));
			if ( !$xoopsMailer->send() ) {
				echo _US_YOURREGMAILNG;
			} else {
				echo _US_YOURREGISTERED;
			}
		// activation by admin
		} elseif ($icmsConfigUser['activation_type'] == 2) {
			$xoopsMailer =& getMailer();
			$xoopsMailer->useMail();
			$xoopsMailer->setTemplate('adminactivate.tpl');
			$xoopsMailer->assign('USERNAME', $uname);
			$xoopsMailer->assign('USERLOGINNAME', $login_name);
			$xoopsMailer->assign('USEREMAIL', $email);
			$xoopsMailer->assign('USERACTLINK', ICMS_URL.'/user.php?op=actv&id='.$newid.'&actkey='.$newuser->getVar('actkey'));
			$member_handler =& xoops_gethandler('member');
			$xoopsMailer->setToGroups($member_handler->getGroup($icmsConfigUser['activation_group']));
			$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
			$xoopsMailer->setFromName($icmsConfig['sitename']);
			$xoopsMailer->setSubject(sprintf(_US_USERKEYFOR, $uname));
			if ( !$xoopsMailer->send() ) {
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
	$xoopsTpl->assign('xoops_pagetitle', _US_USERREG);
	include 'footer.php';
	break;
case 'register':
default:
	$invite_code = isset($_GET['code'])?$_GET['code']:null;
	if ($icmsConfigUser['activation_type'] == 3 || !empty($invite_code)) {
		include 'include/checkinvite.php';
		load_invite_code($invite_code);
	}
	// invite is ok, show register form
	include 'header.php';
	include 'include/registerform.php';
	$reg_form->display();
	$xoopsTpl->assign('xoops_pagetitle', _US_USERREG);
	include 'footer.php';
	break;
}
?>