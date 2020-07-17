<?php
/**
* Friendships page
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.3
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @package		profile
* @version		$Id: friendship.php 22413 2011-08-27 10:21:21Z phoenyx $
*/

$profile_template = 'profile_friendship.html';
include_once 'header.php';

$profile_friendship_handler = icms_getModuleHandler('friendship', basename(dirname(__FILE__)), 'profile');

/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = $_GET['op'];
if (isset($_POST['op'])) $clean_op = $_POST['op'];

/** Again, use a naming convention that indicates the source of the content of the variable */
$real_uid = is_object(icms::$user) ? (int)icms::$user->getVar('uid') : 0;
$clean_uid = isset($_GET['uid']) ? (int)$_GET['uid'] : $real_uid ;
$clean_friendship_id = 0;
if (isset($_GET['friendship_id'])) $clean_friendship_id = (int)$_GET['friendship_id'];
if (isset($_POST['friendship_id'])) $clean_friendship_id = (int)$_POST['friendship_id'];

/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('del', '');

$isAllowed = $profile_configs_handler->userCanAccessSection('friendship', $clean_uid);
if (!$isAllowed || !icms::$module->config['enable_friendship']) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);

/* Only proceed if the supplied operation is a valid operation */
if (in_array($clean_op, $valid_op, true)){
	switch ($clean_op) {
		case 'del':
			$friendshipObj = $profile_friendship_handler->get($clean_friendship_id);
			if (!$friendshipObj->userCanEditAndDelete()) redirect_header(icms_getPreviousPage('friendship.php?uid='.$clean_uid), 3, _NOPERM);

			if (isset($_POST['confirm'])) {
				if (!icms::$security->check()) {
					redirect_header(icms_getPreviousPage('friendship.php?uid='.$clean_uid), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));
				}
			}
			$controller = new icms_ipf_Controller($profile_friendship_handler);
			$controller->handleObjectDeletionFromUserSide();

			break;
		default:
			if ($clean_uid > 0 || $real_uid > 0) {
				$uid = ($clean_uid > 0) ? $clean_uid : $real_uid;

				$friendshipsArray = $profile_friendship_handler->getFriendshipsSorted($uid, $isOwner);
				if ((count($friendshipsArray[PROFILE_FRIENDSHIP_STATUS_PENDING]) + count($friendshipsArray[PROFILE_FRIENDSHIP_STATUS_ACCEPTED]) + count($friendshipsArray[PROFILE_FRIENDSHIP_STATUS_REJECTED])) == 0) {
					$icmsTpl->assign('lang_nocontent', _MD_PROFILE_FRIENDSHIPS_NOCONTENT);
				} else {
					$icmsTpl->assign('profile_friendships', $friendshipsArray);
				}
			} else {
				redirect_header(PROFILE_URL);
			}

			icms_makeSmarty(array(
				'lang_friendships_pending'  => _MD_PROFILE_FRIENDSHIP_PENDING,
				'lang_friendships_accepted' => _MD_PROFILE_FRIENDSHIP_ACCEPTED,
				'lang_friendships_rejected' => _MD_PROFILE_FRIENDSHIP_REJECTED,
				'lang_friendship_accept'    => _MD_PROFILE_FRIENDSHIP_ACCEPT,
				'lang_friendship_reject'    => _MD_PROFILE_FRIENDSHIP_REJECT,
				'image_ok'                  => ICMS_IMAGES_SET_URL."/actions/button_ok.png",
				'image_cancel'              => ICMS_IMAGES_SET_URL."/actions/button_cancel.png",
				'rowitems'                  => icms::$module->config['rowitems'],
				'itemwidth'                 => round(100 / icms::$module->config['rowitems'], 0)
			));

			break;
		}
}
$icmsTpl->assign('profile_category_path', _MD_PROFILE_FRIENDS);

include_once 'footer.php';
?>