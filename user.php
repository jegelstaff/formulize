<?php
/**
 * Login page for users, will redirect to userinfo.php if the user is logged in
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		Member
 * @subpackage	Users
 * @version		SVN: $Id: user.php 20898 2011-02-27 00:57:00Z skenow $
 */

$xoopsOption['pagetype'] = 'user';
include 'mainfile.php';

$op = (isset($_GET['op']))
	? trim(filter_input(INPUT_GET, 'op', FILTER_SANITIZE_STRING))
	: ((isset($_POST['op'])) ? trim(filter_input(INPUT_POST, 'op', FILTER_SANITIZE_STRING)) : 'main');

switch ($op) {
	default:
	case 'main':
		if (!icms::$user) {
			$xoopsOption['template_main'] = 'system_userform.html';
			include 'header.php';
			$redirect = FALSE;
			if (isset($_GET['xoops_redirect'])) {
				$redirect = htmlspecialchars(trim($_GET['xoops_redirect']), ENT_QUOTES);
				$isExternal = FALSE;
				if ($pos = strpos($redirect, '://')) {
					$icmsLocation = substr(ICMS_URL, strpos(ICMS_URL, '://') +3);
					if (substr($redirect, $pos + 3, strlen($icmsLocation)) != $icmsLocation) {
						$redirect = ICMS_URL;
					} elseif (substr($redirect, $pos + 3, strlen($icmsLocation)+1) == $icmsLocation . '.') {
						$redirect = ICMS_URL;
					}
				}
			}
			icms_makeSmarty(array(
	            'usercookie' => isset($_COOKIE[$icmsConfig['usercookie']]) ? $_COOKIE[$icmsConfig['usercookie']] : FALSE,
	            'lang_login' => _LOGIN,
	            'lang_username' => _USERNAME,
	            'redirect_page' => $redirect,
	            'lang_password' => _PASSWORD,
	            'lang_notregister' => _US_NOTREGISTERED,
	            'lang_lostpassword' => _US_LOSTPASSWORD,
	            'lang_noproblem' => _US_NOPROBLEM,
	            'lang_youremail' => _US_YOUREMAIL,
	            'lang_sendpassword' => _US_SENDPASSWORD,
	            'lang_rememberme' => _US_REMEMBERME,
	            'lang_youoid' => _US_OPENID_URL,
	            'lang_login_oid' => _US_OPENID_LOGIN,
	            'lang_back2normoid' => _US_OPENID_NORMAL_LOGIN,
	            'mailpasswd_token' => icms::$security->createToken(),
	            'allow_registration' => $icmsConfigUser['allow_register'],
	            'rememberme' => $icmsConfigUser['remember_me'],
	            'auth_openid' => $icmsConfigAuth['auth_openid'],
	            'icms_pagetitle' => _LOGIN
			));
			include 'footer.php';
		} elseif (!empty($_GET['xoops_redirect'])) {
			$redirect = htmlspecialchars(trim($_GET['xoops_redirect']));
			$isExternal = FALSE;
			if ($pos = strpos($redirect, '://')) {
				$icmsLocation = substr(ICMS_URL, strpos(ICMS_URL, '://') +3);
				if (substr($redirect, $pos + 3, strlen($icmsLocation)) != $icmsLocation) {
					$redirect = ICMS_URL;
				} elseif (substr($redirect, $pos + 3, strlen($icmsLocation)+1) == $icmsLocation . '.') {
					$redirect = ICMS_URL;
				}
			}
			header('Location: ' . $redirect);
			exit();
		} else {
			header('Location: ' . ICMS_URL . '/userinfo.php?uid='. (int) icms::$user->getVar('uid'));
			exit();
		}
		exit();
		break;

	case 'resetpass':
		if (!icms::$user) {
			$xoopsOption['template_main'] = 'system_userform.html';
			include 'header.php';
			$redirect = FALSE;
			if (isset($_GET['xoops_redirect'])) {
				$redirect = htmlspecialchars(trim($_GET['xoops_redirect']), ENT_QUOTES);
				$isExternal = FALSE;
				if ($pos = strpos( $redirect, '://' )) {
					$icmsLocation = substr( ICMS_URL, strpos( ICMS_URL, '://' ) + 3 );
					if (substr($redirect, $pos + 3, strlen($icmsLocation)) != $icmsLocation) {
						$redirect = ICMS_URL;
					} elseif (substr($redirect, $pos + 3, strlen($icmsLocation)+1) == $icmsLocation . '.') {
						$redirect = ICMS_URL;
					}
				}
			}
			icms_makeSmarty(array(
	            'redirect_page' => $redirect,
	            'lang_reset' => 1,
	            'lang_username' => _USERNAME,
	            'lang_uname' => isset($_GET['uname']) ? filter_input(INPUT_GET, 'uname') : '',
	            'lang_resetpassword' => _US_RESETPASSWORD,
	            'lang_resetpassinfo' => _US_RESETPASSINFO,
	            'lang_youremail' => _US_YOUREMAIL,
	            'lang_sendpassword' => _US_SENDPASSWORD,
	            'lang_subresetpassword' => _US_SUBRESETPASSWORD,
	            'lang_currentpass' => _US_CURRENTPASS,
	            'lang_newpass' => _US_NEWPASSWORD,
	            'lang_newpass2' => _US_VERIFYPASS,
	            'resetpassword_token' => icms::$security->createToken(),
	            'icms_pagetitle' => _LOGIN
			));
			include 'footer.php';
		} elseif (!empty($_GET['xoops_redirect'])) {
			$redirect = htmlspecialchars(trim($_GET['xoops_redirect']));
			$isExternal = FALSE;
			if ($pos = strpos($redirect, '://')) {
				$icmsLocation = substr(ICMS_URL, strpos(ICMS_URL, '://') +3);
				if (substr($redirect, $pos + 3, strlen($icmsLocation)) != $icmsLocation) {
					$redirect = ICMS_URL;
				} elseif (substr($redirect, $pos + 3, strlen($icmsLocation)+1) == $icmsLocation . '.') {
					$redirect = ICMS_URL;
				}
			}
			header('Location: ' . $redirect);
			exit();
		} else {
			header('Location: ' . ICMS_URL . '/userinfo.php?uid='. (int) icms::$user->getVar('uid'));
			exit();
		}
		exit();
		break;

	case 'login':
		include_once ICMS_ROOT_PATH . '/include/checklogin.php';
		exit();
		break;

	case 'logout':
		$sessHandler = icms::$session;
		$sessHandler->sessionClose(icms::$user->getVar('uid'));
		redirect_header(ICMS_URL . '/index.php', 3, _US_LOGGEDOUT . '<br />' . _US_THANKYOUFORVISIT);
		break;

	case 'actv':
		$id = (int) $_GET['id'];
		$actkey = trim(filter_input(INPUT_GET, 'actkey'));
		if (empty($id)) {
			redirect_header('index.php',1,'');
		}
		$member_handler = icms::handler('icms_member');
		$thisuser =& $member_handler->getUser($id);
		if (!is_object($thisuser)) {
			exit();
		}
		if ($thisuser->getVar('actkey') != $actkey) {
			redirect_header('index.php',5,_US_ACTKEYNOT);
		} else {
			if ($thisuser->getVar('level') > 0) {
				redirect_header('user.php', 5, _US_ACONTACT, FALSE);
			} else {
				if (FALSE !== $member_handler->activateUser($thisuser)) {
					if ($icmsConfigUser['activation_type'] == 2) {
						$icmsMailer = new icms_messaging_Handler();
						$icmsMailer->useMail();
						$icmsMailer->setTemplate('activated.tpl');
						$icmsMailer->assign('SITENAME', $icmsConfig['sitename']);
						$icmsMailer->assign('ADMINMAIL', $icmsConfig['adminmail']);
						$icmsMailer->assign('SITEURL', ICMS_URL . '/');
						$icmsMailer->setToUsers($thisuser);
						$icmsMailer->setFromEmail($icmsConfig['adminmail']);
						$icmsMailer->setFromName($icmsConfig['sitename']);
						$icmsMailer->setSubject(sprintf(_US_YOURACCOUNT, $icmsConfig['sitename']));
						include 'header.php';
						if (!$icmsMailer->send()) {
							printf(_US_ACTVMAILNG, $thisuser->getVar('uname'));
						} else {
							printf(_US_ACTVMAILOK, $thisuser->getVar('uname'));
						}
						include 'footer.php';
					} else {
						$thisuser->sendWelcomeMessage();
						redirect_header('user.php', 3, _US_ACTLOGIN, FALSE);
					}
				} else {
					redirect_header('index.php', 3, 'Activation failed!');
				}
			}
		}
		exit();
		break;
		
	case 'delete':
		if (!icms::$user || $icmsConfigUser['self_delete'] != 1) {
			redirect_header('index.php',5,_US_NOPERMISS);
		} else {
			$groups = icms::$user->getGroups();
			if (in_array(XOOPS_GROUP_ADMIN, $groups)) {
				redirect_header('user.php', 5, _US_ADMINNO);
			}
			$ok = !isset($_POST['ok']) ? 0 : (int) $_POST['ok'];
			if ($ok != 1) {
				include 'header.php';
				icms_core_Message::confirm(array('op' => 'delete', 'ok' => 1), 'user.php', _US_SURETODEL . '<br/>' . _US_REMOVEINFO);
				include 'footer.php';
			} else {
				$del_uid = (int) icms::$user->getVar('uid');
				$member_handler = icms::handler('icms_member');
				if (FALSE != $member_handler->deleteUser(icms::$user)) {
					$online_handler = icms::handler('icms_core_Online');
					$online_handler->destroy($del_uid);
					xoops_notification_deletebyuser($del_uid);
					redirect_header('index.php', 5, _US_BEENDELED);
				}
				redirect_header('index.php',5,_US_NOPERMISS);
			}
			exit();
		}
		break;


    // when users do not have permission, display a message instead of getting into a redirect loop
    case 'nopermission':
        if (!icms::$user){
            redirect_header('index.php', 5, _US_NOPERMISS);
        } elseif (!empty($_GET['xoops_redirect'])) {
            redirect_header(ICMS_URL."/modules/formulize/application.php?id=all", 5,
                "Sorry, you do not have permission to view this page. Please contact your site administrator.");
            exit();
        } else {
            header('Location: ' . ICMS_URL . '/userinfo.php?uid=' . intval(icms::$user->getVar('uid')));
            exit();
        }
        break;
}
