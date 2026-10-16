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

	echo "<div class='fz-stack'>\n";
	if (empty($pm_arr)) {
		echo "<div class='fz-toolbar'><div class='fz-toolbar__start'><h1 class='fz-text-xl fz-font-semibold'>" . _PM_PRIVATEMESSAGE . "</h1></div></div>\n";
		echo "<div class='fz-empty'><p class='fz-empty__title'>" . _PM_YOUDONTHAVE . "</p><a href='viewpmsg.php' class='fz-btn'>" . _PM_INBOX . "</a></div>\n";
	} else {
		$pm = $pm_arr[0];
		$pm_handler->setRead($pm);

		// Convert timestamp to user's timezone
		$msg_time = $pm->getVar("msg_time");
		$user_offset = formulize_getUserUTCOffsetSecs(icms::$user, $msg_time);
		$adjusted_time = $msg_time + $user_offset;

		// Previous is the older message, and Next the newer one, as readpmsg.php pages through them oldest first
		$previous = $start - 1;
		$next = $start + 1;
		$previousLink = $previous >= 0
			? "<a href='readpmsg.php?start=" . (int) $previous . "&amp;total_messages=" . (int) $total_messages . "' class='fz-btn fz-btn--sm'>" . _PM_PREVIOUS . "</a>"
			: "<span class='fz-btn fz-btn--sm' aria-disabled='true'>" . _PM_PREVIOUS . "</span>";
		$nextLink = $next < $total_messages
			? "<a href='readpmsg.php?start=" . (int) $next . "&amp;total_messages=" . (int) $total_messages . "' class='fz-btn fz-btn--sm'>" . _PM_NEXT . "</a>"
			: "<span class='fz-btn fz-btn--sm' aria-disabled='true'>" . _PM_NEXT . "</span>";

		// the confirmation text goes into a script string inside an attribute, and some translations write it with HTML entities
		$confirmDelete = htmlspecialchars("return confirm(" . json_encode(html_entity_decode(_PM_DELETE_CONFIRM_SINGLE, ENT_QUOTES, _CHARSET)) . ");");

		echo "<nav class='fz-toolbar' aria-label='" . _PM_PRIVATEMESSAGE . "'>\n";
		echo "<div class='fz-toolbar__start'><a href='viewpmsg.php' class='fz-btn fz-btn--ghost fz-btn--sm'>&larr; " . _PM_INBOX . "</a></div>\n";
		echo "<div class='fz-toolbar__end'>" . $previousLink . $nextLink . "</div>\n";
		echo "</nav>\n";

		// the sender can be replied to while their account is active, unless it is your own, as on a notification of something you did
		$from_userid = (int) $pm->getVar("from_userid");
		$poster = icms::handler('icms_member')->getUser($from_userid);
		$canReply = is_object($poster) && $poster->isActive() && $from_userid != icms::$user->getVar('uid');

		echo "<article class='fz-card'>\n";
		echo "<div class='fz-card__header'><div>"
			. "<h1 class='fz-card__title'>" . $pm->getVar("subject") . "</h1>"
			. "<p class='fz-card__subtitle'>" . _PM_FROMC . icms_member_user_Object::getUnameFromId($from_userid) . " &middot; " . _PM_SENTC . formatTimestamp($adjusted_time, 'm') . "</p>"
			. "</div></div>\n";
		// a message typed as plain text keeps its line breaks; one written in HTML has its own
		$msg_text = $pm->getVar('msg_text', 'N');
		if ($msg_text == strip_tags($msg_text)) {
			$msg_text = nl2br($msg_text);
		}
		echo "<div class='fz-prose fz-wrap-break-word'>" . icms_core_DataFilter::checkVar($msg_text, 'html', 'output') . "</div>\n";
		echo "<div class='fz-card__footer'>";
		if ($canReply) {
			// the reply is written in the message popup; without script, the link opens it in this window
			echo "<a href='pmlite.php?reply=1&amp;msg_id=" . (int) $pm->getVar("msg_id") . "' class='fz-btn fz-btn--primary fz-btn--sm' onclick='openWithSelfMain(this.href, \"pmlite\", 800, 680); return false;'>" . _PM_REPLY . "</a>";
		}
		echo "<form class='fz-ms-auto' action='readpmsg.php' method='post' name='delete" . (int) $pm->getVar("msg_id") . "'>"
			. "<input type='hidden' name='delete' value='1' />"
			. icms::$security->getTokenHTML()
			. "<input type='hidden' name='msg_id' value='" . (int) $pm->getVar("msg_id") . "' />"
			. "<button type='submit' class='fz-btn fz-btn--danger fz-btn--sm' onclick='" . $confirmDelete . "'>" . _PM_DELETE . "</button>"
			. "</form>";
		echo "</div>\n";
		echo "</article>\n";
	}
	echo "</div>\n";
	include "footer.php";
}
