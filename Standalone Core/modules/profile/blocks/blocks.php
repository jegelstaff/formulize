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
 * @version         $Id$
 */

if (!defined('ICMS_ROOT_PATH')){ exit(); }

function b_profile_friends_show($options) {
	global $icmsUser;

	if (!empty($icmsUser)){
		$profile_friendship_handler = icms_getModuleHandler('friendship', 'profile');
		$friends = $profile_friendship_handler->getFriendships(0, 0, $icmsUser->getVar('uid'), 0, PROFILE_FRIENDSHIP_STATUS_ACCEPTED);
		$block = array();
		$i = 0;
		foreach($friends as $friend) {
			$block['friends'][$i]['uname'] = $friend['friendship_linkedUname'];
			$block['friends'][$i]['friend_uid']  = $friend['friend_uid'];
			$block['friends'][$i]['sort'] = $friend['friendship_uname'];
			$i++;
		}
		if (count($block['friends']) > 0) usort($block['friends'], 'sortFriendsArray');
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
?>