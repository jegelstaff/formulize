<?php
/**
 * Administration of users, main file
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	Administration
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: main.php 21140 2011-03-20 21:10:43Z m0nty_ $
 */

if (!is_object(icms::$user) || !is_object($icmsModule) || !icms::$user->isAdmin($icmsModule->getVar('mid'))) {
	exit("Access Denied");
}

include_once ICMS_ROOT_PATH."/modules/system/admin/users/users.php";
$allowedHTML = array('user_sig','bio');

if (!empty($_POST)) { foreach ($_POST as $k => $v) { if (!in_array($k,$allowedHTML)) {${$k} = StopXSS($v);} else {${$k} = $v;}}}
if (!empty($_GET)) { foreach ($_GET as $k => $v) { if (!in_array($k,$allowedHTML)) {${$k} = StopXSS($v);} else {${$k} = $v;}}}
$op = (isset($_GET['op']))?trim(StopXSS($_GET['op'])):((isset($_POST['op']))?trim(StopXSS($_POST['op'])):'mod_users');
if (isset($_GET['op'])) {
	if (isset($_GET['uid'])) {
		$uid = (int) $_GET['uid'];
	}
}
switch ($op) {
	case 'modifyUser':
		modifyUser($uid);
		break;

	case 'updateUser':
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=users', 3, implode('<br />', icms::$security->getErrors()));
		}
		// RMV-NOTIFY
		$user_avatar = $theme = null;
		if (!isset($attachsig)) {
			$attachsig = null;
		}
		if (!isset($user_viewemail)) {
			$user_viewemail = null;
		}
		if (!isset($user_viewoid)) {
			$user_viewoid = null;
		}
		if (!isset($openid)) {
			$openid = null;
		}
		$groups = isset($_POST['groups']) ? $groups : array(XOOPS_GROUP_ANONYMOUS);
		if (@is_array($groups_hidden)) {
			$groups = array_unique(array_merge($groups, $groups_hidden)) ;
		}
		updateUser($uid, $username, $login_name, $name, $url, $email, $user_icq, $user_aim, $user_yim, $user_msnm,
					$user_from, $user_occ, $user_intrest, $user_viewemail, $user_avatar, $user_sig, $attachsig,
					$theme, $password, $pass2, $rank, $bio, $uorder, $umode, $notify_method, $notify_mode,
					$timezone_offset, $user_mailok, $language, $openid, $salt, $user_viewoid, $pass_expired,
					$enc_type, $groups
				);
		break;

	case 'delUser':
		icms_cp_header();
		$member_handler = icms::handler('icms_member');
		$userdata =& $member_handler->getUser($uid);
		icms_core_Message::confirm(array('fct' => 'users',
											'op' => 'delUserConf',
											'del_uid' => $userdata->getVar('uid')
										), 'admin.php', sprintf(_AM_AYSYWTDU,$userdata->getVar('uname')));
		icms_cp_footer();
		break;

	case 'delete_many':
		icms_cp_header();
		$count = count($memberslist_id);
		if ($count > 0) {
			$list = "<a href='".ICMS_URL."/userinfo.php?uid=".$memberslist_id[0]."' rel='external'>"
						. $memberslist_uname[$memberslist_id[0]]."</a>";
			$hidden = "<input type='hidden' name='memberslist_id[]' value='".$memberslist_id[0]."' />\n";
			for ($i = 1; $i < $count; $i++) {
				$list .= ", <a href='".ICMS_URL."/userinfo.php?uid=".$memberslist_id[$i]."' rel='external'>"
							. $memberslist_uname[$memberslist_id[$i]]."</a>";
				$hidden .= "<input type='hidden' name='memberslist_id[]' value='".$memberslist_id[$i]."' />\n";
			}
			echo "<div><h4>".sprintf(_AM_AYSYWTDU," ".$list." ")."</h4>";
			echo _AM_BYTHIS."<br /><br />
				<form action='admin.php' method='post'>
				<input type='hidden' name='fct' value='users' />
				<input type='hidden' name='op' value='delete_many_ok' />
				".icms::$security->getTokenHTML()."
				<input type='submit' value='"._YES."' />
				<input type='button' value='"._NO."' onclick='javascript:location.href=\"admin.php?op=adminMain\"' />";
			echo $hidden;
			echo "</form></div>";
		} else {echo _AM_NOUSERS;}
		icms_cp_footer();
		break;

	case 'delete_many_ok':
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=users', 3, implode('<br />', icms::$security->getErrors()));
		}
		$count = count($memberslist_id);
		$output = '';
		$member_handler = icms::handler('icms_member');
		for ($i = 0; $i < $count; $i++) {
			$deluser =& $member_handler->getUser($memberslist_id[$i]);
			$delgroups = $deluser->getGroups();
			if (in_array(XOOPS_GROUP_ADMIN, $delgroups)) {
				$output .= sprintf(_AM_ADMIN_CAN_NOT_BE_DELETEED.' ('._AM_NICKNAME.': %s)',
									$deluser->getVar('uname')).'<br />';
			} else {
				if (!$member_handler->deleteUser($deluser)) {
					$output .= _AM_COULD_NOT_DELETE.' '.$deluser->getVar('uname').'<br />';
				} else {
					$output .= $deluser->getVar('uname').' '._AM_USERS_DELETEED.'<br />';
				}
				// RMV-NOTIFY
				xoops_notification_deletebyuser($deluser->getVar('uid'));
			}
		}
		icms_cp_header();
		echo $output;
		icms_cp_footer();
		break;

	case 'delUserConf':
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=users', 3, implode('<br />', icms::$security->getErrors()));
		}
		$member_handler = icms::handler('icms_member');
		$user =& $member_handler->getUser($del_uid);
		$groups = $user->getGroups();
		if (in_array(XOOPS_GROUP_ADMIN, $groups)) {
			icms_cp_header();
			echo sprintf(_AM_ADMIN_CAN_NOT_BE_DELETEED.'. ('._AM_NICKNAME.': %s)', $user->getVar('uname'));
			icms_cp_footer();
		} elseif (!$member_handler->deleteUser($user)) {
			icms_cp_header();
			echo _AM_ADMIN_CAN_NOT_BE_DELETEED.$deluser->getVar('uname');
			icms_cp_footer();
		} else {
			$online_handler = icms::handler('icms_core_Online');
			$online_handler->destroy($del_uid);
			// RMV-NOTIFY
			xoops_notification_deletebyuser($del_uid);
			redirect_header('admin.php?fct=users',1,_AM_DBUPDATED);
		}
		break;

	case 'addUser':
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=users', 3, implode('<br />', icms::$security->getErrors()));
		}
		if (!$username || !$email || !$password || !$login_name) {
			$adduser_errormsg = _AM_YMCACF;
		} else {
			$member_handler = icms::handler('icms_member');
			// make sure the username doesnt exist yet
			if ($member_handler->getUserCount(new icms_db_criteria_Item('uname', $username)) > 0
				|| $member_handler->getUserCount(new icms_db_criteria_Item('login_name', $login_name)) > 0 ) {
				$adduser_errormsg = _AM_NICKNAME.' '.$username.' '._AM_ALREADY_EXISTS;
			} elseif ($member_handler->getUserCount(new icms_db_criteria_Item('email', $email)) > 0) {
				$adduser_errormsg = _AM_A_USER_WITH_THIS_EMAIL_ADDRESS.' "'.$email.'" '._AM_ALREADY_EXISTS;
			} else {
				$newuser =& $member_handler->createUser();
				if (isset($user_viewemail)) {
					$newuser->setVar('user_viewemail',$user_viewemail);
				}
				if (isset($user_viewoid)) {
					$newuser->setVar('user_viewoid',$user_viewoid);
				}
				if (isset($attachsig)) {
					$newuser->setVar('attachsig',$attachsig);
				}
				$newuser->setVar('name', $name);
				$newuser->setVar('login_name', $login_name);
				$newuser->setVar('uname', $username);
				$newuser->setVar('email', $email);
				$newuser->setVar('url', formatURL($url));
				$newuser->setVar('user_avatar','blank.gif');
				$newuser->setVar('user_icq', $user_icq);
				$newuser->setVar('user_from', $user_from);
				$newuser->setVar('user_sig', $user_sig);
				$newuser->setVar('user_aim', $user_aim);
				$newuser->setVar('user_yim', $user_yim);
				$newuser->setVar('user_msnm', $user_msnm);
				if ($pass2 != '') {
					if ($password != $pass2) {
						icms_cp_header();
						echo '<b>'._AM_STNPDNM.'</b>';
						icms_cp_footer();
						exit();
					}
					if ($password == $username || $password == icms_core_DataFilter::utf8_strrev($username, true)
						|| strripos($password, $username) === true || $password == $login_name
						|| $password == icms_core_Datafilter::utf8_strrev($login_name, true)
						|| strripos($password, $login_name) === true) {
						icms_cp_header();
						echo '<b>'._AM_BADPWD.'</b>';
						icms_cp_footer();
						exit();
					}
					
					$icmspass = new icms_core_Password();
					$newuser->setVar('salt', $salt);
					$newuser->setVar('enc_type', $enc_type);
					$password = $icmspass->encryptPass($password, $salt, $enc_type);
					$newuser->setVar('pass', $password);
				}
				$newuser->setVar('timezone_offset', $timezone_offset);
				$newuser->setVar('uorder', $uorder);
				$newuser->setVar('umode', $umode);
				// RMV-NOTIFY
				$newuser->setVar('notify_method', $notify_method);
				$newuser->setVar('notify_mode', $notify_mode);
				$newuser->setVar('bio', $bio);
				$newuser->setVar('rank', $rank);
				$newuser->setVar('level', 1);
				$newuser->setVar('user_occ', $user_occ);
				$newuser->setVar('user_intrest', $user_intrest);
				$newuser->setVar('user_mailok', $user_mailok);
				$newuser->setVar('language', $language);

				if ($icmsConfigAuth['auth_openid'] == 1) {
					$newuser->setVar('openid', $openid);}
					if (!$member_handler->insertUser($newuser)) {
						$adduser_errormsg = _AM_CNRNU;
					} else {
						$groups_failed = array();
						if (!isset($_POST['groups'])) {
							$groups = array(XOOPS_GROUP_ANONYMOUS);
						}
						foreach ($groups as $group) {
							if (!$member_handler->addUserToGroup($group, $newuser->getVar('uid'))) {
								$groups_failed[] = $group;
							}
						}
						if (!empty($groups_failed)) {
							$group_names = $member_handler->getGroupList(
									new icms_db_criteria_Item('groupid', "(".implode(", ", $groups_failed).")", 'IN'));
							$adduser_errormsg = sprintf(_AM_CNRNU2, implode(", ", $group_names));
						} else {
							/* Hack by marcan <INBOX>
							 * Sending a confirmation email to the newly registered user
							 */
							/**
							 * @todo this has been commented out for now as we need to add a check box on the
							 * form to ask the admin if he wants to send the welcome message or not
							 */
							/*
							 $xoopsMailer = new icms_messaging_Handler();
							 $xoopsMailer->useMail();
							 $xoopsMailer->setTemplate('welcome.tpl');
							 $xoopsMailer->assign('UNAME', $uname);
							 $xoopsMailer->assign('PASSWORD', $vpass);
							 $xoopsMailer->assign('X_UEMAIL', $email);
							 $xoopsMailer->setToEmails($email);
							 $xoopsMailer->setFromEmail($icmsConfig['adminmail']);
							 $xoopsMailer->setFromName($icmsConfig['sitename']);
							 $xoopsMailer->setSubject(sprintf(_US_YOURREGISTRATION,icms_core_DataFilter::stripSlashesGPC($icmsConfig['sitename'])));
							 $xoopsMailer->send();
							 /* Hack by marcan <INBOX>
							 * Sending a confirmation email to the newly registered user
							 */
							redirect_header('admin.php?fct=users',1,_AM_DBUPDATED);
						}
					}
			}
		}
		icms_cp_header();
		icms_core_Message::error($adduser_errormsg);
		icms_cp_footer();
		break;

	case 'synchronize':
		if (!icms::$security->check()) {
			redirect_header('admin.php?fct=users', 3, implode('<br />', icms::$security->getErrors()));
		}
		synchronize($id, $type);
		break;

	case 'reactivate':
		$result = icms::$xoopsDB->query(
				"UPDATE ".icms::$xoopsDB->prefix('users')." SET level='1' WHERE uid='". (int) $uid."'"
				);
		if (!$result) {
			exit();
		}
		redirect_header('admin.php?fct=users&amp;op=modifyUser&amp;uid='. (int) $uid, 1 , _AM_DBUPDATED);
		break;

	case 'mod_users':
	default:
		displayUsers();
		break;
}