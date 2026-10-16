<?php
/**
 * View and manage your private messages
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @subpackage	Privmessage
 * @version		SVN: $Id: viewpmsg.php 20767 2011-02-05 22:43:30Z skenow $
 */
$xoopsOption['pagetype'] = 'pmsg';
include_once 'mainfile.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';
$module_handler = icms::handler('icms_module');
$messenger_module = $module_handler->getByDirname('messenger');
if ($messenger_module && $messenger_module->getVar('isactive')) {
	header('location: ./modules/messenger/msgbox.php');
	exit();
}

if (!is_object(icms::$user)) {
	$errormessage = _PM_SORRY . '<br />' . _PM_PLZREG . '';
	redirect_header('user.php', 2, $errormessage);
} else {
	$pm_handler = icms::handler('icms_data_privmessage');
	if (isset($_POST['delete_messages']) && isset($_POST['msg_id'])) {
		if (!icms::$security->check()) {
			echo implode('<br />', icms::$security->getErrors());
			exit();
		}
		foreach ((array) $_POST['msg_id'] as $msg_id) {
			$pm = $pm_handler->get((int) $msg_id);
			if (is_object($pm) && $pm->getVar('to_userid') == icms::$user->getVar('uid')) {
				$pm_handler->delete($pm);
			}
			unset($pm);
		}
		redirect_header('viewpmsg.php', 1, _PM_DELETED);
	}
	include ICMS_ROOT_PATH . '/header.php';
	$criteria = new icms_db_criteria_Item('to_userid', (int) (icms::$user->getVar('uid')));
	$criteria->setOrder('DESC');
	$pm_arr =& $pm_handler->getObjects($criteria);
	$total_messages = count($pm_arr);

	// the confirmation text goes into a script string inside an attribute, and some translations write it with HTML entities
	$confirmDelete = htmlspecialchars("return confirm(" . json_encode(html_entity_decode(_PM_DELETE_CONFIRM_MULTIPLE, ENT_QUOTES, _CHARSET)) . ");");

	echo "<form id='prvmsg' class='fz-stack' method='post' action='viewpmsg.php'>\n";
	echo "<div class='fz-toolbar'>\n";
	echo "<div class='fz-toolbar__start'><h1 class='fz-text-xl fz-font-semibold'>" . _PM_PRIVATEMESSAGE . "</h1></div>\n";
	echo "<div class='fz-toolbar__end'>";
	if ($total_messages > 0) {
		echo icms::$security->getTokenHTML()
			. "<button type='submit' class='fz-btn fz-btn--danger' name='delete_messages' value='1' onclick='" . $confirmDelete . "'>" . _PM_DELETE . "</button>";
	}
	// a new message is written in the message popup; without script, the link opens it in this window
	echo "<a href='pmlite.php?send=1' class='fz-btn fz-btn--primary' onclick='openWithSelfMain(this.href, \"pmlite\", 800, 680); return false;'>" . _PM_NEWMESSAGE . "</a>";
	echo "</div>\n";
	echo "</div>\n";

	if ($total_messages == 0) {
		echo "<div class='fz-empty'><p class='fz-empty__title'>" . _PM_YOUDONTHAVE . "</p></div>\n";
	} else {
		echo "<div class='fz-card fz-card--flush fz-overflow-x-auto'>\n";
		echo "<table class='fz-table'>\n";
		echo "<thead><tr>"
			. "<th class='fz-w-fit'><input type='checkbox' class='fz-checkbox' name='allbox' id='allbox' aria-label='" . _PM_SELECTALL . "' onclick='xoopsCheckAll(\"prvmsg\", \"allbox\");' /></th>"
			. "<th class='fz-hide-on-mobile'>" . _PM_FROM . "</th>"
			. "<th>" . _PM_SUBJECT . "</th>"
			. "<th class='fz-hide-on-mobile'>" . _PM_DATE . "</th>"
			. "</tr></thead>\n";
		echo "<tbody>\n";
		$senders = array();
		for ($i = 0; $i < $total_messages; $i++) {
			$from_userid = (int) $pm_arr[$i]->getVar('from_userid');
			if (!isset($senders[$from_userid])) {
				$senders[$from_userid] = icms_member_user_Object::getUnameFromId($from_userid);
			}
			$from = $senders[$from_userid];
			$msg_id = (int) $pm_arr[$i]->getVar('msg_id');
			$unread = $pm_arr[$i]->getVar('read_msg') != 1;
			$msg_time = $pm_arr[$i]->getVar('msg_time');
			$adjusted_time = $msg_time + formulize_getUserUTCOffsetSecs(icms::$user, $msg_time);
			// readpmsg.php pages through the messages oldest first, so this one's place in that order
			$readUrl = "readpmsg.php?start=" . (int) ($total_messages - $i - 1) . "&amp;total_messages=" . (int) $total_messages;
			$sent = formatTimestamp($adjusted_time, 'm');
			// on a phone the sender and the date go under the subject, so the subject has the width of the table
			echo "<tr>"
				. "<td><input type='checkbox' class='fz-checkbox' name='msg_id[]' id='message_$msg_id' value='$msg_id' aria-labelledby='subject_$msg_id' /></td>"
				. "<td class='fz-hide-on-mobile'>" . $from . "</td>"
				. "<td><div class='fz-cluster'>"
				. "<a href='$readUrl' id='subject_$msg_id'" . ($unread ? " class='fz-font-semibold'" : "") . ">" . $pm_arr[$i]->getVar('subject') . "</a>"
				. ($unread ? "<span class='fz-badge fz-badge--accent'>" . _PM_NOTREAD . "</span>" : "")
				. "</div>"
				. "<p class='fz-show-on-mobile fz-text-xs fz-text-muted fz-tabular-nums'>" . $from . " &middot; " . $sent . "</p>"
				. "</td>"
				. "<td class='fz-hide-on-mobile fz-text-nowrap fz-text-muted fz-tabular-nums'>" . $sent . "</td>"
				. "</tr>\n";
		}
		echo "</tbody>\n";
		echo "</table>\n";
		echo "</div>\n";
	}
	echo "</form>\n";
	include 'footer.php';
}
