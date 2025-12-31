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
 * @version		$Id: readpmsg.php 21047 2011-03-14 15:52:14Z m0nty_ $
 */

$xoopsOption['pagetype'] = "pmsg";
include_once "mainfile.php";

if (!is_object(icms::$user)) {
	redirect_header("user.php",0);
} else {
	$pm_handler = icms::handler('icms_data_privmessage');
	if (!empty($_POST['delete'])) {
		if (!icms::$security->check()) {
			echo implode('<br />', icms::$security->getErrors());
			exit();
		}
		$pm =& $pm_handler->get( (int) ($_POST['msg_id']));
		if (!is_object($pm) || $pm->getVar('to_userid') != icms::$user->getVar('uid') || !$pm_handler->delete($pm)) {
			exit();
		} else {
			redirect_header("viewpmsg.php",1,_PM_DELETED);
			exit();
		}
	}
	$start = !empty($_GET['start']) ? (int) ($_GET['start']) : 0;
	$total_messages = !empty($_GET['total_messages']) ? (int) ($_GET['total_messages']) : 0;
	include ICMS_ROOT_PATH.'/header.php';
	include_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';
	$criteria = new icms_db_criteria_Item('to_userid', (int) (icms::$user->getVar('uid')));
	$criteria->setLimit(1);
	$criteria->setStart($start);
	$criteria->setSort('msg_time');
	$pm_arr =& $pm_handler->getObjects($criteria);
	if (empty($pm_arr)) {
		echo '<h1>'._PM_PRIVATEMESSAGE.'</h1>';
		echo '<p>'._PM_YOUDONTHAVE.'</p>';
	} else {
		if (!$pm_handler->setRead($pm_arr[0])) {
			//echo "failed";
		}

		$poster = new icms_member_user_Object((int) $pm_arr[0]->getVar("from_userid"));
		if (!$poster->isActive()) {
			$poster = false;
		}

		// Convert timestamp to user's timezone
		$msg_time = $pm_arr[0]->getVar("msg_time");
		$user_offset = formulize_getUserUTCOffsetSecs(icms::$user, $msg_time);
		$adjusted_time = $msg_time + $user_offset;

		echo "<div class='pm-breadcrumbs'><a href='viewpmsg.php'>Inbox</a> &raquo; ".$pm_arr[0]->getVar("subject")."</div>";
		echo "<form action='readpmsg.php' method='post' name='delete".$pm_arr[0]->getVar("msg_id")."'>
			<table border='0' cellpadding='4' cellspacing='1' class='outer' width='100%'>
			<tr><th><h1>".$pm_arr[0]->getVar("subject")."</h1></th></tr>
			<tr class='even'>
			<td><strong>"._PM_SENTC."</strong> ".formatTimestamp($adjusted_time, 'm')."</td></tr>
			<tr class='even'><td>\n";
		$var = $pm_arr[0]->getVar('msg_text', 'N');
		echo icms_core_DataFilter::checkVar($var, 'html', 'output') . "</td></tr>
			<tr class='foot'><td>";
		echo "<input type='hidden' name='delete' value='1' />";
		echo icms::$security->getTokenHTML();
		echo "<input type='hidden' name='msg_id' value='".$pm_arr[0]->getVar("msg_id")."' />";
		echo "<input type='submit' class='formButton' value='"._PM_DELETE."' onclick='return confirm(\""._PM_DELETE_CONFIRM_SINGLE."\");' />";
		echo "</td></tr><tr><td class='pm-navigation'>";
		$previous = $start - 1;
		$next = $start + 1;
		echo "<span class='pm-nav-item'>";
		if ($previous >= 0) {
			echo "<a href='readpmsg.php?start=". (int) ($previous)."&amp;total_messages=". (int) ($total_messages)."'>"._PM_PREVIOUS."</a>";
		} else {
			echo _PM_PREVIOUS;
		}
		echo "</span><span class='pm-nav-separator'> | </span>";
		echo "<span class='pm-nav-item'>";
		if ($next < $total_messages) {
			echo "<a href='readpmsg.php?start=". (int) ($next)."&amp;total_messages=". (int) ($total_messages)."'>"._PM_NEXT."</a>";
		} else {
			echo _PM_NEXT;
		}
		echo "</span><span class='pm-nav-separator'> | </span>";
		echo "<span class='pm-nav-item'><a href='viewpmsg.php'>Inbox</a></span>";
		echo "</td></tr></table></form>\n";
	}
	include "footer.php";
}