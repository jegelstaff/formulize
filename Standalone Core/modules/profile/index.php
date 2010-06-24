<?php
/**
 * Extended User Profile
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license	LICENSE.txt
 * @license	GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package	modules
 * @since	1.2
 * @author	Jan Pedersen
 * @author	Marcello Brandao <marcello.brandao@gmail.com>
 * @author	Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version	$Id$
 */

/**
 * Edit a Friendship
 *
 * @param object $friendshipObj ProfileFriendship object to be edited
*/
function editfriendship($friendshipObj, $uid=false, $hideForm=false) {
	global $profile_friendship_handler, $xoTheme, $icmsTpl, $icmsUser;

	$icmsTpl->assign('hideForm', $hideForm);
	if ($friendshipObj->isNew()){
		if (!$profile_friendship_handler->userCanSubmit() || !$uid) {
			redirect_header(PROFILE_URL, 3, _NOPERM);
		}
		$friendshipObj->setVar('friend1_uid', $icmsUser->uid());
		$friendshipObj->setVar('friend2_uid', $uid);
		$friendshipObj->setVar('creation_time', time());
		$friendshipObj->hideFieldFromForm(array('creation_time', 'friend2_uid', 'friend1_uid', 'status'));
		$sform = $friendshipObj->getSecureForm($hideForm ? '' : _MD_PROFILE_FRIENDSHIP_ADD, 'addfriendship');
		$sform->assign($icmsTpl, 'profile_friendshipform');
		$icmsTpl->assign('lang_friendshipform_title', _MD_PROFILE_FRIENDSHIP_ADD);
	}
}

$profile_template = 'profile_index.html';
$profile_current_page = basename(__FILE__);
include_once 'header.php';

if($icmsModuleConfig['profile_social']==0){
	header('Location: '.ICMS_URL.'/modules/profile/userinfo.php?uid='.$uid);
	exit();
}

// log visitor
$profile_visitors_handler = icms_getModuleHandler('visitors');
$profile_visitors_handler->logVisitor($uid);

// Use a naming convention that indicates the source of the content of the variable
$clean_op = '';
if (isset($_GET['op'])) $clean_op = $_GET['op'];
if (isset($_POST['op'])) $clean_op = $_POST['op'];

// Again, use a naming convention that indicates the source of the content of the variable
$clean_friendship_id = isset($_GET['friendship_id']) ? intval($_GET['friendship_id']) : 0 ;
$profile_friendship_handler = icms_getModuleHandler('friendship');

/*  Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition */
$valid_op = array ('addfriendship','editfriendship', '');

// Only proceed if the supplied operation is a valid operation
if (in_array($clean_op,$valid_op,true) && is_object($icmsUser)){
	switch ($clean_op) {
		case "addfriendship":
			if (!$xoopsSecurity->check()) {
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', $xoopsSecurity->getErrors()));
				exit();
			}
			include_once ICMS_ROOT_PATH.'/kernel/icmspersistablecontroller.php';
			$controller = new IcmsPersistableController($profile_friendship_handler);
			$controller->storeFromDefaultForm(_MD_PROFILE_FRIENDSHIP_CREATED, _MD_PROFILE_FRIENDSHIP_MODIFIED);
			break;
		case "editfriendship":
			if (!$xoopsSecurity->check()) {
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', $xoopsSecurity->getErrors()));
				exit();
			}
			$clean_friendship_id = isset($_POST['friendship_id']) ? intval($_POST['friendship_id']) : 0;
			$friendshipObj = $profile_friendship_handler->get($clean_friendship_id);

			if (!$friendshipObj->isNew() && $friendshipObj->getVar('friend2_uid') == $uid) {
				$clean_status = isset($_POST['status']) ? intval($_POST['status']) : '';
				$valid_status = array (PROFILE_FRIENDSHIP_STATUS_ACCEPTED, PROFILE_FRIENDSHIP_STATUS_REJECTED);
				if (in_array($clean_status, $valid_status, true)) {
					$friendshipObj->setVar('status', $clean_status);
					$friendshipObj->store(true);
					if (strpos(icms_getPreviousPage(), $friendshipObj->handler->_moduleUrl.$friendshipObj->handler->_page) !== false) {
						header('Location: '.$friendshipObj->handler->_moduleUrl.$friendshipObj->handler->_page.'?uid='.$uid);
					}
				}
			}
		default:
			if($icmsUser->getVar('uid') != $uid) {
				$friendships = $profile_friendship_handler->getFriendships(0, 1, $icmsUser->getVar('uid'), $uid);
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
	'lang_aim'               => _US_AIM,
	'lang_basicInfo'         => _US_BASICINFO,
	'lang_contactinfo'       => _MD_PROFILE_CONTACTINFO,
	'lang_delete'            => _MD_PROFILE_DELETE,
	'lang_detailsinfo'       => _MD_PROFILE_USERDETAILS,
	'lang_editdesc'          => _MD_PROFILE_EDITDESC,
	'lang_editprofile'       => _MD_PROFILE_EDITPROFILE,
	'lang_email'             => _US_EMAIL,
	'lang_extrainfo'         => _US_EXTRAINFO,
	'lang_icq'               => _US_ICQ,
	'lang_interest'          => _US_INTEREST,
	'lang_lastlogin'         => _US_LASTLOGIN,
	'lang_location'          => _US_LOCATION,
	'lang_membersince'       => _US_MEMBERSINCE,
	'lang_more'              => _US_MOREABOUT,
	'lang_msnm'              => _US_MSNM,
	'lang_myinfo'            => _US_MYINFO,
	'lang_notregistered'     => _US_NOTREGISTERED,
	'lang_occupation'        => _US_OCCUPATION,
	'lang_openid'            => _US_OPENID_FORM_CAPTION,
	'lang_posts'             => _US_POSTS,
	'lang_privmsg'           => _US_PM,
	'lang_rank'              => _US_RANK,
	'lang_realname'          => _US_REALNAME,
	'lang_selectavatar'      => _MD_PROFILE_SELECTAVATAR,
	'lang_signature'         => _US_SIGNATURE,
	'lang_statistics'        => _US_STATISTICS,
	'lang_uname'             => _US_NICKNAME,
	'lang_usercontributions' => _MD_PROFILE_USERCONTRIBUTIONS,
	'lang_visitors'          => _MD_PROFILE_VISITORS,
	'lang_website'           => _US_WEBSITE,
	'lang_yim'               => _US_YIM));

// passing user information to smarty
$icmsTpl->assign('show_empty', $icmsModuleConfig['show_empty']);
$icmsTpl->assign('user_name_header', $owner_name);
$icmsTpl->assign('uid_owner', $uid);
$icmsTpl->assign('section_name', _MD_PROFILE_PROFILE);
$icmsTpl->assign('user_uname', $thisUser->getVar('uname'));
$icmsTpl->assign('user_realname', $thisUser->getVar('name'));
$icmsTpl->assign('user_websiteurl', ($thisUser->getVar('url', 'E') != '') ? $myts->makeClickable(formatURL($thisUser->getVar('url', 'E'))) : '');
$icmsTpl->assign('user_icq', $thisUser->getVar('user_icq'));
$icmsTpl->assign('user_aim', $thisUser->getVar('user_aim'));
$icmsTpl->assign('user_yim', $thisUser->getVar('user_yim'));
$icmsTpl->assign('user_msnm', $thisUser->getVar('user_msnm'));
$icmsTpl->assign('user_location', $thisUser->getVar('user_from'));
$icmsTpl->assign('user_occupation', $thisUser->getVar('user_occ'));
$icmsTpl->assign('user_interest', $thisUser->getVar('user_intrest'));
$icmsTpl->assign('user_extrainfo', trim($thisUser->getVar('bio')) ? $myts->displayTarea($thisUser->getVar('bio', 'N'),0,1,1) : '');
$icmsTpl->assign('user_joindate', formatTimestamp($thisUser->getVar('user_regdate'), 's'));
$icmsTpl->assign('user_posts', $thisUser->getVar('posts'));
$icmsTpl->assign('user_signature', trim($thisUser->getVar('user_sig')) ? $myts->displayTarea($thisUser->getVar('user_sig', 'N'), 1, 1, 1) : '');
$icmsTpl->assign('user_email', ($thisUser->getVar('user_viewemail') == 1 || $profile_isAdmin || $isOwner) ? $thisUser->getVar('email', 'E') : '');
$icmsTpl->assign('user_lastlogin', ($thisUser->getVar("last_login") != 0) ? formatTimestamp($thisUser->getVar("last_login"), "m") : '');
$userrank = $thisUser->rank();
$icmsTpl->assign('user_ranktitle', $userrank['title']);
if ($userrank['image']) {
	$icmsTpl->assign('user_rankimage', '<img src="'.ICMS_UPLOAD_URL.'/'.$userrank['image'].'" alt="" />');
}
if ($thisUser->getVar('user_avatar') && $thisUser->getVar('user_avatar') != 'blank.gif' && $thisUser->getVar('user_avatar') != ''){
	$icmsTpl->assign('user_avatar', ICMS_UPLOAD_URL.'/'.$thisUser->getVar('user_avatar'));
} elseif ($icmsConfigUser['avatar_allow_gravatar'] == 1) {
	$icmsTpl->assign('user_avatar', $thisUser->gravatar('G', $icmsConfigUser['avatar_width']));
	$icmsTpl->assign('gravatar', true);
}
$allow_avatar_upload = ($isOwner && is_object($icmsUser) && $icmsConfigUser['avatar_allow_upload'] == 1 && $icmsUser->getVar('posts') >= $icmsConfigUser['avatar_minposts']);
$icmsTpl->assign('allow_avatar_upload', $allow_avatar_upload);
if ($icmsConfigAuth['auth_openid'] == 1 && ($thisUser->getVar('user_viewoid') == 1 || $profile_isAdmin || $isOwner)) {
	$icmsTpl->assign('openid', $thisUser->getVar('openid'));
}

// visitors
$visitors = $profile_visitors_handler->getVisitors(0, 5, $uid);
$rtn = array();
$i = 0;
foreach($visitors as $visitor) {
	$visitorUser =& $member_handler->getUser($visitor['uid_visitor']);
	$rtn[$i]['uid'] = $visitor['uid_visitor'];
	$rtn[$i]['uname'] = $visitorUser->getVar('uname');
	$rtn[$i]['time'] = $visitor['visit_time'];
	$i++;
}
$icmsTpl->assign('visitors', $rtn);
unset($visitors);

// getting user contributions
if ($icmsModuleConfig['profile_search'] && $permissions['profile_usercontributions']) {
	$gperm_handler = & xoops_gethandler('groupperm');
	$groups = is_object($icmsUser) ? $icmsUser->getGroups() : ICMS_GROUP_ANONYMOUS;
	$module_handler =& xoops_gethandler('module');
	$criteria = new CriteriaCompo(new Criteria('hassearch', 1));
	$criteria->add(new Criteria('isactive', 1));
	$mids = array_keys($module_handler->getList($criteria));

	foreach ($mids as $mid) {
		if ($gperm_handler->checkRight('module_read', $mid, $groups)) {
			$module =& $module_handler->get($mid);
			$user_uid =$thisUser->getVar('uid');
			$results = $module->search('', '', 5, 0, $user_uid);
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

					$results[$i]['title'] = $myts->displayTarea($results[$i]['title']);
					$results[$i]['time'] = $results[$i]['time'] ? formatTimestamp($results[$i]['time']) : '';
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
if ($icmsModuleConfig['enable_pictures'] && $permissions['pictures']) {
	$profile_pictures_handler = icms_getModuleHandler('pictures');
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
if ($icmsModuleConfig['enable_audio'] && $permissions['audio']) {
	$profile_audio_handler = icms_getModuleHandler('audio');
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
if ($icmsModuleConfig['enable_friendship'] && $permissions['friendship']) {
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
	if (is_object($icmsUser) && $icmsUser->getVar('uid') == $uid) {
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
if ($icmsModuleConfig['enable_videos'] && $permissions['videos']) {
	$profile_videos_handler = icms_getModuleHandler('videos');
	$videos = $profile_videos_handler->getVideos(0, 1, $uid);
	$rtn = array();
	foreach($videos as $video) {
		$rtn['content'] = $video['video_content'];
	}
	$icmsTpl->assign('video', $rtn);
	unset($videos);
	$icmsTpl->assign('lang_video_goto', _MD_PROFILE_GOTO._MD_PROFILE_VIDEOS);
}

// tribes
if ($icmsModuleConfig['enable_tribes'] && $permissions['tribes']) {
	// get tribes where the user is the owner
	$profile_tribes_handler = icms_getModuleHandler('tribes');
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
		$profile_tribeuser_handler = icms_getmodulehandler('tribeuser');
		$tribeusers = $profile_tribeuser_handler->getApprovals($ownTribes);
		$rtn = array();
		$i = 0;
		foreach ($tribeusers as $tribeuser) {
			$rtn[$i]['tribeuser_id'] = $tribeuser['tribeuser_id'];
			$rtn[$i]['uid'] = $tribeuser['user_id'];
			$rtn[$i]['uname'] = icms_getLinkedUnameFromId($tribeuser['user_id']);
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
// Closing the page
include("footer.php");

function sortList($a, $b) {
	$a = strtolower($a['title']);
	$b = strtolower($b['title']);
	return ($a == $b) ? 0 : ($a < $b) ? -1 : +1;
}
?>