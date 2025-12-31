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
		$size = count($_POST['msg_id']);
		$msg =& $_POST['msg_id'];
		for ($i = 0; $i < $size; $i++) {
			$pm =& $pm_handler->get($msg[$i]);
			if ($pm->getVar('to_userid') == icms::$user->getVar('uid')) {
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
	echo "<form id='prvmsg' method='post' action='viewpmsg.php'>";
	echo "<table border='0' cellspacing='1' cellpadding='4' width='100%' class='outer'>\n";
	echo "<tr><th colspan='4' class='pm-table-title'><h1>" . _PM_PRIVATEMESSAGE . "</h1></th></tr>\n";
	echo "<tr class='pm-table-header'><th>"
	. "<input name='allbox' id='allbox' onclick='xoopsCheckAll(\"prvmsg\", \"allbox\");'"
	. "type='checkbox' value='Check All' /></th><th>&nbsp;</th><th>"
	. _PM_DATE . "</th><th>" . _PM_SUBJECT . "</th></tr>\n";
	$total_messages = count($pm_arr);
	if ($total_messages == 0) {
		echo "<tr><td class='even pm-no-messages' colspan='4'>" . _PM_YOUDONTHAVE . "</td></tr>";
		$display = 0;
	} else {
		$display = 1;
	}

	for ($i = 0; $i < $total_messages; $i++) {
		$class = ($i % 2 == 0) ? 'even' : 'odd';
		echo "<tr class='pm-message-row $class'>"
		. "<td class='pm-checkbox'><input type='checkbox' id='message_"
		. $pm_arr[$i]->getVar('msg_id') . "' name='msg_id[]' value='" . $pm_arr[$i]->getVar('msg_id') . "' /></td>\n";
		if ($pm_arr[$i]->getVar('read_msg') == 1) {
			echo "<td class='pm-status'>&nbsp;</td>\n";
		} else {
			echo "<td class='pm-status pm-status-unread'>"
			. "<i class='fas fa-envelope inbox-link__icon inbox-link__icon--unread'></i>"
			. "<span class='inbox-link__badge inbox-link__badge--table'>●</span></td>\n";
		}
		$msg_time = $pm_arr[$i]->getVar('msg_time');
		$user_offset = formulize_getUserUTCOffsetSecs(icms::$user, $msg_time);
		$adjusted_time = $msg_time + $user_offset;
		echo "<td class='pm-date'>"
		. formatTimestamp($adjusted_time, format: 'm') . "</td>";
		echo "<td class='pm-subject'><a href='readpmsg.php?start="
		. (int) (($total_messages-$i-1)) . "&amp;total_messages="
		. (int) $total_messages . "'>" . $pm_arr[$i]->getVar('subject') . "</a></td></tr>";
	}

	if ($display == 1) {
		echo "<tr class='foot pm-table-footer'><td colspan='4'>"
		. "<input type='submit' class='formButton' name='delete_messages' value='"
		. _PM_DELETE . "' onclick='return confirm(\""._PM_DELETE_CONFIRM_MULTIPLE."\");' />" . icms::$security->getTokenHTML() . "</td></tr></table></form>";
	} else {
		echo "</table></form>";
	}
	include 'footer.php';
}
