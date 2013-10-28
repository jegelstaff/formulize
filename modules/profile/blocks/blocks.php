<?php
/**
 * Extended User Profile
 *
 * @copyright       The ImpressCMS Project http://www.impresscms.org/
 * @license         LICENSE.txt
 * @license         GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package         modules
 * @since           1.2
 * @author          Jan Pedersen
 * @author          Marcello Brandao <marcello.brandao@gmail.com>
 * @author          Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version         $Id: blocks.php 21843 2011-06-23 14:54:52Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

function b_profile_friends_show($options) {
	global $xoTheme;

	if (!empty(icms::$user)){
		$profile_friendship_handler = icms_getModuleHandler('friendship', basename(dirname(dirname(__FILE__))), 'profile');
		$friends = $profile_friendship_handler->getFriendships(0, 0, icms::$user->getVar('uid'), 0, PROFILE_FRIENDSHIP_STATUS_ACCEPTED);
		if (count($friends) == 0) return;
		$block = array();
		$i = 0;
		foreach($friends as $friend) {
			$friend_uid = icms::$user->getVar('uid') == $friend['friend1_uid'] ? $friend['friend2_uid'] : $friend['friend1_uid'];
			$block['friends'][$i]['uname'] = icms_member_user_Handler::getUserLink($friend_uid);
			$block['friends'][$i]['friend_uid']  = $friend_uid;
			$block['friends'][$i]['sort'] = icms_member_user_Object::getUnameFromId($friend_uid);
			$i++;
		}
		if (isset($block['friends']) && count($block['friends']) > 0) usort($block['friends'], 'sortFriendsArray');

		// adding PM javascript, $xoTheme cannot be used in this place because jQuery is not yet loaded
		if (count($block['friends']) > 0) $block['jQuery'] = 'jQuery(document).ready(function(){jQuery("a.block-profile-pm").colorbox({width:600, height:395, iframe:true});});';
	}

	return $block;
}

function b_profile_friends_edit($options) {
	$form = _MB_PROFILE_NUMBER_FRIENDS.": <input type='text' value='".$options['0']."'id='options[]' name='options[]' />";

	return $form;
}

function sortFriendsArray($a, $b) {
	$a = strtolower($a['sort']);
	$b = strtolower($b['sort']);
	return ($a == $b) ? 0 : ($a < $b) ? -1 : +1;
}

function b_profile_usermenu_show($options) {
	global $icmsConfigUser;

	if (!is_object(icms::$user)) return;
	icms_loadLanguageFile(basename(dirname(dirname(__FILE__))), 'modinfo');

	$block = array();
	$dirname = basename(dirname(dirname(__FILE__)));

	$config_handler = icms::handler('icms_config');
	$privmessage_handler = icms::handler('icms_data_privmessage');
	$module = icms::handler('icms_module')->getByDirname($dirname, TRUE);
	$criteria = new icms_db_criteria_Compo();
	$criteria->add(new icms_db_criteria_Item('read_msg', 0));
	$criteria->add(new icms_db_criteria_Item('to_userid', icms::$user->getVar('uid')));
	$newmsg = $privmessage_handler->getCount($criteria);

	$i = 0;
	if (icms::$user->isAdmin()) {
		$block[++$i]['name'] = _MB_SYSTEM_ADMENU;
		$block[$i]['url'] = ICMS_URL."/admin.php";
	}
	$block[++$i]['name'] = _MB_SYSTEM_VACNT;
	$block[$i]['url'] = ICMS_URL."/modules/".$dirname."/".($module->config['profile_social'] ? "index.php?uid=".icms::$user->getVar('uid') : "userinfo.php?uid=".icms::$user->getVar('uid'));
	$block[++$i]['name'] = _MB_SYSTEM_INBOX;
	$block[$i]['url'] = ICMS_URL."/viewpmsg.php";
	$block[$i]['extra'] = $newmsg;
	$block[++$i]['name'] = _MB_SYSTEM_NOTIF;
	$block[$i]['url'] = ICMS_URL."/notifications.php";
	if ($module->config['profile_social']) {
		$block[++$i]['name'] = _MI_PROFILE_SEARCH;
		$block[$i]['url'] = ICMS_URL."/modules/".$dirname."/search.php";
	}
	$block[++$i]['name'] = _MI_PROFILE_EDITACCOUNT;
	$block[$i]['url'] = ICMS_URL."/modules/".$dirname."/edituser.php";
	$block[++$i]['name'] = _MI_PROFILE_CHANGEPASS;
	$block[$i]['url'] = ICMS_URL."/modules/".$dirname."/changepass.php";
	if ($icmsConfigUser['allow_chgmail']) {
		$block[++$i]['name'] = _MI_PROFILE_CHANGEMAIL;
		$block[$i]['url'] = ICMS_URL."/modules/".$dirname."/changemail.php";
	}
	if ($icmsConfigUser['self_delete']) {
		$block[++$i]['name'] = _MI_PROFILE_DELETEACCOUNT;
		$block[$i]['url'] = ICMS_URL."/modules/".$dirname."/edituser.php?op=delete";
	}
	if ($module->config['profile_social']) {
		$block[++$i]['name'] = _MI_PROFILE_MYCONFIGS;
		$block[$i]['url'] = ICMS_URL."/modules/".$dirname."/configs.php";
	}
	$block[++$i]['name'] = _MB_SYSTEM_LOUT;
	$block[$i]['url'] = ICMS_URL."/user.php?op=logout";

	return $block;
}
?>