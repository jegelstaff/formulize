<?php
/**
 * Extended User Profile
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		LICENSE.txt
 * @license		GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package		modules
 * @since		1.2
 * @author		Jan Pedersen
 * @author		Marcello Brandao <marcello.brandao@gmail.com>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: index.php 22279 2011-08-20 11:39:32Z phoenyx $
 */

/**
 * Edit a Friendship
 *
 * @param mod_prpfile_Friendship $friendshipObj object to be edited
 * @param int $uid user id
 * @param bool $hideForm true is form should be hidden by default
*/
function editfriendship($friendshipObj, $uid=false, $hideForm=false) {
	global $profile_friendship_handler, $icmsTpl, $xoTheme;

	$uid = (int) $uid;

	$icmsTpl->assign('hideForm', $hideForm);
	if ($friendshipObj->isNew()){
		if (!$profile_friendship_handler->userCanSubmit() || !$uid) redirect_header(PROFILE_URL, 3, _NOPERM);

		$friendshipObj->setVar('friend1_uid', icms::$user->getVar('uid'));
		$friendshipObj->setVar('friend2_uid', $uid);
		$friendshipObj->setVar('creation_time', date(_DATESTRING));
		$friendshipObj->hideFieldFromForm(array('creation_time', 'friend2_uid', 'friend1_uid', 'status'));
		$sform = $friendshipObj->getSecureForm($hideForm ? '' : _MD_PROFILE_FRIENDSHIP_ADD, 'addfriendship');
		$sform->assign($icmsTpl, 'profile_friendshipform');
		$icmsTpl->assign('lang_friendshipform_title', _MD_PROFILE_FRIENDSHIP_ADD);
	}
}

$profile_template = 'profile_index.html';
$profile_current_page = basename(__FILE__);
include_once 'header.php';

if (icms::$module->config['profile_social'] == 0){
	header('Location: '.PROFILE_URL.'/userinfo.php?uid='.$uid);
	exit();
}

// log visitor
$profile_visitors_handler = icms_getModuleHandler('visitors', basename(dirname(__FILE__)), 'profile');
$profile_visitors_handler->logVisitor($uid);

// Use a naming convention that indicates the source of the content of the variable
$clean_op = '';
if (isset($_GET['op'])) $clean_op = $_GET['op'];
if (isset($_POST['op'])) $clean_op = $_POST['op'];

// Again, use a naming convention that indicates the source of the content of the variable
$clean_friendship_id = isset($_GET['friendship_id']) ? (int)$_GET['friendship_id'] : 0 ;
$profile_friendship_handler = icms_getModuleHandler('friendship', basename(dirname(__FILE__)), 'profile');

/*  Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition */
$valid_op = array ('addfriendship','editfriendship', '');

// Only proceed if the supplied operation is a valid operation
if (in_array($clean_op,$valid_op,true) && is_object(icms::$user)){
	switch ($clean_op) {
		case "addfriendship":
			$uid = (int)filter_input(INPUT_POST, 'friend2_uid');
			if (!icms::$security->check()) redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));
			$controller = new icms_ipf_Controller($profile_friendship_handler);
			$controller->storeFromDefaultForm(_MD_PROFILE_FRIENDSHIP_CREATED, _MD_PROFILE_FRIENDSHIP_MODIFIED, PROFILE_URL . "/index.php?uid=" . $uid);
			break;
		case "editfriendship":
			if (!icms::$security->check()) redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));
			$clean_friendship_id = isset($_POST['friendship_id']) ? (int)$_POST['friendship_id'] : 0;
			$friendshipObj = $profile_friendship_handler->get($clean_friendship_id);

			if (!$friendshipObj->isNew() && $friendshipObj->getVar('friend2_uid') == $uid) {
				$clean_status = isset($_POST['status']) ? (int)$_POST['status'] : '';
				$valid_status = array(PROFILE_FRIENDSHIP_STATUS_ACCEPTED, PROFILE_FRIENDSHIP_STATUS_REJECTED);
				if (in_array($clean_status, $valid_status, true)) {
					$friendshipObj->setVar('status', $clean_status);
					$friendshipObj->store(true);
					if (strpos(icms_getPreviousPage(), $friendshipObj->handler->_moduleUrl.$friendshipObj->handler->_page) !== false) {
						header('Location: '.$friendshipObj->handler->_moduleUrl.$friendshipObj->handler->_page.'?uid='.$uid);
					}
				}
			}
		default:
			if (icms::$user->getVar('uid') != $uid) {
				$friendships = $profile_friendship_handler->getFriendships(0, 1, icms::$user->getVar('uid'), $uid);
				if (count($friendships) == 0) {
					$friendshipObj = $profile_friendship_handler->get($clean_friendship_id);
					editfriendship($friendshipObj, $uid, true);
				}
			}
		break;
	}
}

// passing language constants to smarty
icms_makeSmarty(array(
	'lang_delete'            => _MD_PROFILE_DELETE,
	'lang_editprofile'       => _MD_PROFILE_EDITPROFILE,
	'lang_selectavatar'      => _MD_PROFILE_SELECTAVATAR,
	'lang_usercontributions' => _MD_PROFILE_USERCONTRIBUTIONS,
	'lang_visitors'          => _MD_PROFILE_VISITORS));

// passing user information to smarty
$icmsTpl->assign('user_name_header', $owner_name);
$icmsTpl->assign('uid_owner', $uid);
if ($thisUser->getVar('user_avatar') && $thisUser->getVar('user_avatar') != 'blank.gif' && $thisUser->getVar('user_avatar') != ''){
	$icmsTpl->assign('user_avatar', ICMS_UPLOAD_URL.'/'.$thisUser->getVar('user_avatar'));
} elseif ($icmsConfigUser['avatar_allow_gravatar'] == 1) {
	$icmsTpl->assign('user_avatar', $thisUser->gravatar('G', $icmsConfigUser['avatar_width']));
	$icmsTpl->assign('gravatar', true);
}
$allow_avatar_upload = ($isOwner && is_object(icms::$user) && $icmsConfigUser['avatar_allow_upload'] == 1 && icms::$user->getVar('posts') >= $icmsConfigUser['avatar_minposts']);
$icmsTpl->assign('allow_avatar_upload', $allow_avatar_upload);

// visitors
$visitors = $profile_visitors_handler->getVisitors(0, 5, $uid);
$rtn = array();
$i = 0;
foreach($visitors as $visitor) {
	$visitorUser = $member_handler->getUser($visitor['uid_visitor']);
	$rtn[$i]['uid'] = $visitor['uid_visitor'];
	$rtn[$i]['uname'] = $visitorUser->getVar('uname');
	$rtn[$i]['time'] = $visitor['visit_time'];
	$i++;
}
$icmsTpl->assign('visitors', $rtn);
unset($visitors);

// Dynamic User Profiles
$field_handler = icms_getModuleHandler('field', basename(dirname(__FILE__)), 'profile');
$icmsTpl->assign('fields', $field_handler->getProfileFields($thisUser));
unset($field_handler);

// getting user contributions
if (icms::$module->config['profile_search'] && $permissions['profile_usercontributions']) {
	$groups = is_object(icms::$user) ? icms::$user->getGroups() : ICMS_GROUP_ANONYMOUS;
	$module_handler = icms::handler('icms_module');
	$mids = array_keys($module_handler->getList(icms_buildCriteria(array('hassearch' => 1, 'isactive' => 1))));

	foreach ($mids as $mid) {
		if (icms::handler('icms_member_groupperm')->checkRight('module_read', $mid, $groups)) {
			$module = $module_handler->get($mid);
			$results = $module->search('', '', 5, 0, $thisUser->getVar('uid'));
			$count = count($results);
			if (is_array($results) && $count > 0) {
				for ($i = 0; $i < $count; $i++) {
					if (isset($results[$i]['image']) && $results[$i]['image'] != '') {
						$results[$i]['image'] = 'modules/'.$module->getVar('dirname').'/'.$results[$i]['image'];
					} else {
						$results[$i]['image'] = 'images/icons/posticon2.gif';
					}

					if (!preg_match("/^http[s]*:\/\//i", $results[$i]['link'])) {
						$results[$i]['link'] = ICMS_URL."/modules/".$module->getVar('dirname')."/".$results[$i]['link'];
					}

					$results[$i]['title'] = icms_core_DataFilter::checkVar($results[$i]['title'], 'text', 'output');
					$results[$i]['time'] = $results[$i]['time'] ? formatTimestamp($results[$i]['time'], 'm') : '';
				}
				if ($count == 5) {
					$showall_link = '<a href="'.ICMS_URL.'/search.php?action=showallbyuser&amp;mid='.$mid.'&amp;uid='.$thisUser->getVar('uid').'">'._US_SHOWALL.'</a>';
				} else {
					$showall_link = '';
				}
				$icmsTpl->append('modules', array('name' => $module->getVar('name'), 'results' => $results, 'showall_link' => $showall_link));
			}
			unset($module);
		}
	}
}

// getting social content
// pictures
if (icms::$module->config['enable_pictures'] && $permissions['pictures']) {
	$profile_pictures_handler = icms_getModuleHandler('pictures', basename(dirname(__FILE__)), 'profile');
	$pictures = $profile_pictures_handler->getPictures(0, 3, $uid);
	$rtn = array();
	$i = 0;
	foreach($pictures as $picture) {
		$rtn[$i++]['content'] = $picture['picture_content'];
	}
	$icmsTpl->assign('pictures', $rtn);
	unset($pictures);
	$icmsTpl->assign('lang_pictures_goto', _MD_PROFILE_GOTO._MD_PROFILE_PHOTOS);
}

// audio
if (icms::$module->config['enable_audio'] && $permissions['audio']) {
	$profile_audio_handler = icms_getModuleHandler('audio', basename(dirname(__FILE__)), 'profile');
	$audios = $profile_audio_handler->getAudios(0, 1, $uid);
	$rtn = array();
	foreach($audios as $audio) {
		$rtn['content'] = $audio['audio_content'];
	}
	$icmsTpl->assign('audio', $rtn);
	unset($audios);
	$icmsTpl->assign('lang_audio_goto', _MD_PROFILE_GOTO._MD_PROFILE_AUDIOS);
}

// friends
if (icms::$module->config['enable_friendship'] && $permissions['friendship']) {
	$friends = $profile_friendship_handler->getFriendships(0, 3, $uid, 0, PROFILE_FRIENDSHIP_STATUS_ACCEPTED);
	$rtn = array();
	$i = 0;
	foreach($friends as $friend) {
		$rtn[$i]['user_avatar'] = $friend['friendship_avatar'];
		$rtn[$i]['uname'] = $friend['friendship_linkedUname'];
		$i++;
	}
	$icmsTpl->assign('friends', $rtn);
	unset($friends);
	// get waiting friendships
	if (is_object(icms::$user) && icms::$user->getVar('uid') == $uid) {
		$friends = $profile_friendship_handler->getFriendships(0, 0, 0, $uid, PROFILE_FRIENDSHIP_STATUS_PENDING);
		$rtn = array();
		$i = 0;
		foreach($friends as $friend) {
			$rtn[$i]['friendship_id'] = $friend['friendship_id'];
			$rtn[$i]['uname'] = $friend['friendship_linkedUname'];
			$i++;
		}
		$icmsTpl->assign('friends_pending', $rtn);
		$icmsTpl->assign('lang_friends_pending', _MD_PROFILE_FRIENDSHIP_PENDING);
		$icmsTpl->assign('lang_friendship_accept', _MD_PROFILE_FRIENDSHIP_ACCEPT);
		$icmsTpl->assign('lang_friendship_reject', _MD_PROFILE_FRIENDSHIP_REJECT);
		unset($friends);
	}
	$icmsTpl->assign('lang_friends_goto', _MD_PROFILE_GOTO._MD_PROFILE_FRIENDS);
}

// video
if (icms::$module->config['enable_videos'] && $permissions['videos']) {
	$profile_videos_handler = icms_getModuleHandler('videos', basename(dirname(__FILE__)), 'profile');
	$videos = $profile_videos_handler->getVideos(0, 1, $uid);
	$rtn = array();
	foreach($videos as $video) {
		$rtn['content'] = $video['video_content_main'];
	}
	$icmsTpl->assign('video', $rtn);
	unset($videos);
	$icmsTpl->assign('lang_video_goto', _MD_PROFILE_GOTO._MD_PROFILE_VIDEOS);
}

// tribes
if (icms::$module->config['enable_tribes'] && $permissions['tribes']) {
	// get tribes where the user is the owner
	$profile_tribes_handler = icms_getModuleHandler('tribes', basename(dirname(__FILE__)), 'profile');
	$tribes = $profile_tribes_handler->getTribes(0, 0, $uid, false, true);
	$rtn = array();
	$ownTribes = array();
	$i = 0;
	foreach($tribes as $tribe) {
		$rtn[$i]['title'] = $tribe['title'];
		$rtn[$i]['itemLink'] = $tribe['itemLink'];
		$ownTribes[] = $tribe['tribes_id'];
		$i++;
	}
	unset($tribes);
	// get tribes where the user is a member
	$tribes = $profile_tribes_handler->getMembershipTribes($uid);
	foreach($tribes as $tribe) {
		$rtn[$i]['title'] = $tribe['title'];
		$rtn[$i]['itemLink'] = $tribe['itemLink'];
		$i++;
	}
	// finally sort the array
	usort($rtn, 'sortList');
	$icmsTpl->assign('tribes', $rtn);
	unset($tribes);
	// get awaiting approvals
	if ($isOwner) {
		$profile_tribeuser_handler = icms_getmodulehandler('tribeuser', basename(dirname(__FILE__)), 'profile');
		$tribeusers = $profile_tribeuser_handler->getApprovals($ownTribes);
		$rtn = array();
		$i = 0;
		foreach ($tribeusers as $tribeuser) {
			$rtn[$i]['tribeuser_id'] = $tribeuser['tribeuser_id'];
			$rtn[$i]['uid'] = $tribeuser['user_id'];
			$rtn[$i]['uname'] = icms_member_user_Handler::getUserLink($tribeuser['user_id']);
			$rtn[$i]['tribes_id'] = $tribeuser['tribe_id'];
			$rtn[$i]['tribe_itemLink'] = $tribeuser['tribe_itemLink'];
			$i++;
		}
		$icmsTpl->assign('tribes_approvals', $rtn);
		$icmsTpl->assign('lang_approvals', _MD_PROFILE_TRIBES_APPROVALS);
		$icmsTpl->assign('lang_approve', _MD_PROFILE_TRIBEUSER_APPROVE);
		unset($tribeusers);
	}
	// get invitations
	if ($isOwner) {
		$tribeusers = $profile_tribeuser_handler->getInvitations($uid);
		$rtn = array();
		$i = 0;
		foreach ($tribeusers as $tribeuser) {
			$rtn[$i]['tribeuser_id'] = $tribeuser['tribeuser_id'];
			$rtn[$i]['tribes_id'] = $tribeuser['tribe_id'];
			$rtn[$i]['itemLink'] = $tribeuser['tribe_itemLink'];
			$i++;
		}
		$icmsTpl->assign('tribes_invitations', $rtn);
		$icmsTpl->assign('lang_invitations', _MD_PROFILE_TRIBES_INVITATIONS);
		$icmsTpl->assign('lang_accept', _MD_PROFILE_TRIBEUSER_ACCEPT);
		unset($tribeusers);
	}
	$icmsTpl->assign('lang_tribes_goto', _MD_PROFILE_GOTO._MD_PROFILE_TRIBES);
}

$icmsTpl->assign('image_ok', ICMS_IMAGES_SET_URL."/actions/button_ok.png");
$icmsTpl->assign('image_cancel', ICMS_IMAGES_SET_URL."/actions/button_cancel.png");

// Comments
include ICMS_ROOT_PATH.'/include/comment_view.php';
// adding PM javascript
$xoTheme->addScript('', array('type' => 'text/javascript'), 'jQuery(document).ready(function(){jQuery("a.profile-pm").colorbox({width:600, height:395, iframe:true});});');
// Closing the page
include 'footer.php';

function sortList($a, $b) {
	$a = strtolower($a['title']);
	$b = strtolower($b['title']);
	return ($a == $b) ? 0 : ($a < $b) ? -1 : +1;
}