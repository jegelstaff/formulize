<?php
/**
 * All functions for pm manager are going through here.
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		XOOPS
 * @author		http://www.xoops.org The XOOPS Project
 * @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: pmlite.php 21047 2011-03-14 15:52:14Z m0nty_ $
 */

$xoopsOption['pagetype'] = "pmsg";

include "mainfile.php";
$reply = !empty($_GET['reply']) ? 1 : 0;
$send = !empty($_GET['send']) ? 1 : 0;
$send2 = !empty($_GET['send2']) ? 1 : 0;
$to_userid = !empty($_GET['to_userid']) ? (int) ($_GET['to_userid']) : 0;
$msg_id = !empty($_GET['msg_id']) ? (int) ($_GET['msg_id']) : 0;
if (empty($_GET['refresh'] ) && isset($_POST['op']) && $_POST['op'] != "submit") {
	$jump = "pmlite.php?refresh=".time()."";
	if ($send == 1) {
		$jump .= "&amp;send=".$send."";
	} elseif ($send2 == 1) {
		$jump .= "&amp;send2=".$send2."&amp;to_userid=".$to_userid."";
	} elseif ($reply == 1) {
		$jump .= "&amp;reply=".$reply."&amp;msg_id=".$msg_id."";
	} else {
	}
	echo "<html><head><meta http-equiv='Refresh' content='0; url=".$jump."' /></head><body></body></html>";
	exit();
}

// drawn as one of the Formulize popups, so it looks like the rest of the site in whichever theme is in use
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/popup_functions.php';

/**
 * A caption from the language file as a field label: without the colon some of them end with
 *
 * @param string $caption the caption, such as _PM_SUBJECTC
 * @return string HTML
 */
function pmlite_label($caption) {
	return rtrim(str_replace('&nbsp;', ' ', $caption), ': ');
}

/**
 * A message in a callout, with buttons below it
 *
 * @param string $tone the callout's variation: success or danger
 * @param string $message the message, as HTML
 * @param string $buttons the buttons, as HTML
 * @return string HTML
 */
function pmlite_result($tone, $message, $buttons) {
	return "<div class='fz-callout fz-callout--" . $tone . "' role='status'>" . $message . "</div>\n"
		. "<div class='fz-cluster'>" . $buttons . "</div>\n";
}

$goBackButton = "<button type='button' class='fz-btn' onclick='history.go(-1);'>" . _PM_GOBACK . "</button>";
$closeButton = "<button type='button' class='fz-btn fz-btn--ghost' onclick='window.close();'>" . _CLOSE . "</button>";

// the user picker's links to search for more users use xoops.js, which no theme puts in a popup
formulize_popupHeader(_PM_PRIVATEMESSAGE, "<script type='text/javascript' src='" . ICMS_URL . "/include/xoops.js'></script>\n");
if (icms::$user) {
	if (isset($_POST['op']) && $_POST['op'] == "submit") {
		$res = icms::$xoopsDB->query("SELECT COUNT(*) FROM ".icms::$xoopsDB->prefix("users")." WHERE uid='". (int) ($_POST['to_userid'])."'");
		list($count) = icms::$xoopsDB->fetchRow($res);
		if (!icms::$security->check()) {
			echo pmlite_result('danger', "<p>" . implode('<br />', icms::$security->getErrors()) . "</p>", $goBackButton);
		} elseif ($count != 1) {
			echo pmlite_result('danger', "<p class='fz-callout__title'>" . _PM_USERNOEXIST . "</p><p>" . _PM_PLZTRYAGAIN . "</p>", $goBackButton);
		} else {
			$pm_handler = icms::handler('icms_data_privmessage');
			$pm =& $pm_handler->create();
			$pm->setVar("subject", $_POST['subject']);
			$pm->setVar("msg_text", $_POST['message']);
			$pm->setVar("to_userid", (int) ($_POST['to_userid']));
			$pm->setVar("from_userid", (int) (icms::$user->getVar("uid")));
			if (!$pm_handler->insert($pm)) {
				echo pmlite_result('danger', $pm->getHtmlErrors(), $goBackButton);
			} else {
				// Send a Private Message email notification
				$userHandler = icms::handler('icms_member_user');
				$toUser =& $userHandler->get( (int) ($_POST['to_userid']));
				// Only send email notif if notification method is mail
				if ($toUser->getVar('notify_method') == 2) {
					$xoopsMailer = new icms_messaging_Handler();
					$xoopsMailer->useMail();
					$xoopsMailer->setToEmails($toUser->getVar('email'));
					if (icms::$user->getVar('user_viewemail')) {
						$xoopsMailer->setFromEmail(icms::$user->getVar('email'));
						$xoopsMailer->setFromName(icms::$user->getVar('uname'));
					} else {
						$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
						$xoopsMailer->setFromName($icmsConfig['sitename']);
					}
					$xoopsMailer->setTemplate('new_pm.tpl');
					$xoopsMailer->assign('X_SITENAME', $icmsConfig['sitename']);
					$xoopsMailer->assign('X_SITEURL', ICMS_URL."/");
					$xoopsMailer->assign('X_ADMINMAIL', $icmsConfig['adminmail']);
					$xoopsMailer->assign('X_UNAME', $toUser->getVar('uname'));
					$xoopsMailer->assign('X_FROMUNAME', icms::$user->getVar('uname'));
					$xoopsMailer->assign('X_SUBJECT', icms_core_DataFilter::stripSlashesGPC($_POST['subject']));
					$xoopsMailer->assign('X_MESSAGE', icms_core_DataFilter::stripSlashesGPC($_POST['message']));
					$xoopsMailer->assign('X_ITEM_URL', ICMS_URL . "/viewpmsg.php");
					$xoopsMailer->setSubject(sprintf(_PM_MESSAGEPOSTED_EMAILSUBJ, $icmsConfig['sitename']));
					$xoopsMailer->send();
				}
				// this page is usually a popup, but some links open it in the main window, where there is no opener to send to the inbox
				$inboxButton = "<a href='" . ICMS_URL . "/viewpmsg.php' class='fz-btn fz-btn--primary' onclick='if (window.opener) { window.opener.location = this.href; window.close(); return false; }'>" . _PM_INBOX . "</a>";
				echo pmlite_result('success', "<p>" . _PM_MESSAGEPOSTED . "</p>", $inboxButton . $closeButton);
			}
		}
	} elseif ($reply == 1 || $send == 1 || $send2 == 1) {
		$message = '';
		$subject = '';
		if ($reply == 1) {
			$pm_handler = icms::handler('icms_data_privmessage');
			$pm =& $pm_handler->get($msg_id);
			if (is_object($pm) && $pm->getVar("to_userid") == (int) (icms::$user->getVar('uid'))) {
				$pm_uname = icms_member_user_Object::getUnameFromId($pm->getVar("from_userid"));
				$message  = "[quote]\n";
				$message .= sprintf(_PM_USERWROTE,$pm_uname);
				$message .= "\n".$pm->getVar("msg_text", "E")."\n[/quote]";
				$subject = $pm->getVar('subject', 'E');
				if (!preg_match("/^Re:/i",$subject)) {
					$subject = 'Re: '.$subject;
				}
			} else {
				unset($pm);
				$reply = $send2 = 0;
				$send = 1;
			}
		}

		echo "<form action='pmlite.php' method='post' name='coolsus' class='fz-card'>\n";
		echo "<div class='fz-card__header'><div>";
		echo "<h1 class='fz-card__title'>" . ($reply == 1 ? _PM_REPLY : _PM_NEWMESSAGE) . "</h1>";
		// a reply, or a message from a link on someone's profile, already has its recipient
		if ($reply == 1) {
			echo "<p class='fz-card__subtitle'>" . _PM_TO . $pm_uname . "</p>";
		} elseif ($send2 == 1) {
			echo "<p class='fz-card__subtitle'>" . _PM_TO . icms_member_user_Object::getUnameFromId($to_userid) . "</p>";
		}
		echo "</div></div>\n";
		if ($reply == 1) {
			echo "<input type='hidden' name='to_userid' value='" . (int) $pm->getVar("from_userid") . "' />\n";
		} elseif ($send2 == 1) {
			echo "<input type='hidden' name='to_userid' value='" . $to_userid . "' />\n";
		} else {
			$user_sel = new icms_form_elements_select_User("", "to_userid");
			$user_sel_elements = $user_sel->getElements();
			$user_sel_elements[0]->setExtra("class='fz-select'");
			echo formulize_popupField(pmlite_label(_PM_TO), $user_sel->render(), '', 'to_userid');
		}
		echo formulize_popupField(pmlite_label(_PM_SUBJECTC), "<input type='text' class='fz-input' name='subject' id='subject' value='" . $subject . "' maxlength='100' />", '', 'subject');
		echo formulize_popupField(pmlite_label(_PM_MESSAGEC), "<textarea class='fz-textarea' name='message' id='message' rows='10'>" . $message . "</textarea>", '', 'message');
		echo "<div class='fz-card__footer'>"
			. "<input type='hidden' name='op' value='submit' />" . icms::$security->getTokenHTML()
			. "<button type='submit' class='fz-btn fz-btn--primary' name='submit' value='1'>" . _PM_SUBMIT . "</button>"
			. "<button type='reset' class='fz-btn'>" . _PM_CLEAR . "</button>"
			. "<button type='button' class='fz-btn fz-btn--ghost' name='cancel' onclick='window.close();'>" . _PM_CANCELSEND . "</button>"
			. "</div>\n";
		echo "</form>\n";
	}
} else {
	echo "<div class='fz-callout'><p>" . _PM_SORRY . "</p></div>\n";
	echo "<div class='fz-cluster'><a href='" . ICMS_URL . "/register.php' class='fz-btn fz-btn--primary'>" . _PM_REGISTERNOW . "</a></div>\n";
}

formulize_popupFooter();
