<?php
/**
 * All functions for Registering users by invitation are going through here.
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @package		Member
 * @subpackage	Users
 * @author		marcan <marcan@impresscms.org>
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: invite.php 21047 2011-03-14 15:52:14Z m0nty_ $
 */

$xoopsOption['pagetype'] = 'user';

include 'mainfile.php';

// If not a user and invite needs one, redirect
if ($icmsConfigUser['activation_type'] == 3 && $icmsConfigUser['allow_register'] == 0 && !is_object(icms::$user)) {
	redirect_header('index.php', 6, _US_INVITEBYMEMBER);
	exit();
}

$op = !isset($_POST['op']) ? 'invite' : $_POST['op'];
$email = isset($_POST['email']) ? trim(icms_core_DataFilter::stripSlashesGPC($_POST['email'])) : '';

switch ($op) {
	case 'finish':
		include 'header.php';
		$stop = '';
		if (!icms::$security->check()) {
			$stop .= implode('<br />', icms::$security->getErrors()) . "<br />";
		}
		$icmsCaptcha = icms_form_elements_captcha_Object::instance();
		if (! $icmsCaptcha->verify()) {
			$stop .= $icmsCaptcha->getMessage() . '<br />';

		}
		if (!checkEmail($email)) {
			$stop .= _US_INVALIDMAIL . '<br />';
		}
		if (empty($stop)) {
			$invite_code = substr(md5(uniqid(mt_rand(), 1)), 0, 8);
			$sql = sprintf('INSERT INTO ' . icms::$xoopsDB->prefix('invites') . '
							(invite_code, from_id, invite_to, invite_date, extra_info) VALUES
							(%s, %d, %s, %d, %s)',
							icms::$xoopsDB->quoteString(addslashes($invite_code)),
							is_object(icms::$user) ? icms::$user->getVar('uid') : 0,
							icms::$xoopsDB->quoteString(addslashes($email)),
							time(),
							icms::$xoopsDB->quoteString(addslashes(serialize(array())))
						);
			icms::$xoopsDB->query($sql);
			// if query executed successful
			if (icms::$xoopsDB->getAffectedRows() == 1) {
				$xoopsMailer = new icms_messaging_Handler();
				$xoopsMailer->useMail();
				$xoopsMailer->setTemplate('invite.tpl');
				$xoopsMailer->assign('SITENAME', $icmsConfig['sitename']);
				$xoopsMailer->assign('ADMINMAIL', $icmsConfig['adminmail']);
				$xoopsMailer->assign('SITEURL', ICMS_URL . "/");
				$xoopsMailer->assign('USEREMAIL', $email);
				$xoopsMailer->assign('REGISTERLINK', ICMS_URL . '/register.php?code=' . $invite_code);
				$xoopsMailer->setToEmails($email);
				$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
				$xoopsMailer->setFromName($icmsConfig['sitename']);
				$xoopsMailer->setSubject(sprintf(_US_INVITEREGLINK, ICMS_URL));
				if (!$xoopsMailer->send()) {
					$stop .= _US_INVITEMAILERR;
				} else {
					echo _US_INVITESENT;
				}
			} else {
				$stop .= _US_INVITEDBERR;
			}
		}
		if (! empty($stop)) {
			echo "<span style='color:#ff0000; font-weight:bold;'>$stop</span>";
			include 'include/inviteform.php';
			$invite_form->display();
		}
		include 'footer.php';
		break;
		
	case 'invite':
	default:
		include 'header.php';
		include 'include/inviteform.php';
		$invite_form->display();
		include 'footer.php';
		break;
}
