<?php
/**
* Videos page
*
* @copyright	GNU General Public License (GPL)
* @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since	1.3
* @author	Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @package	profile
* @version	$Id:$
*/

/**
 * Edit a Video
 *
 * @param object $videosObj ProfileVideo object to be edited
*/
function editvideos($videosObj, $hideForm=false)
{
	global $profile_videos_handler, $xoTheme, $icmsTpl, $icmsUser;

	$icmsTpl->assign('hideForm', $hideForm);
	if (!$videosObj->isNew()){
		if (!$videosObj->userCanEditAndDelete()) {
			redirect_header($videosObj->getItemLink(true), 3, _NOPERM);
		}
		$videosObj->hideFieldFromForm(array('uid_owner', 'creation_time', 'meta_keywords', 'meta_description', 'short_url'));
		$sform = $videosObj->getSecureForm($hideForm ? '' : _MD_PROFILE_VIDEOS_EDIT, 'addvideos');
		$sform->assign($icmsTpl, 'profile_videosform');
		$icmsTpl->assign('lang_videosform_title', _MD_PROFILE_VIDEOS_EDIT);
	} else {
		if (!$profile_videos_handler->userCanSubmit()) {
			redirect_header(PROFILE_URL, 3, _NOPERM);
		}
		$videosObj->setVar('uid_owner', $icmsUser->uid());
		$videosObj->setVar('creation_time', time());
		$videosObj->hideFieldFromForm(array('creation_time', 'uid_owner', 'meta_keywords', 'meta_description', 'short_url'));
		$sform = $videosObj->getSecureForm($hideForm ? '' : _MD_PROFILE_VIDEOS_SUBMIT, 'addvideos');
		$sform->assign($icmsTpl, 'profile_videosform');
		$icmsTpl->assign('lang_videosform_title', _MD_PROFILE_VIDEOS_SUBMIT);
	}
}


$profile_template = 'profile_videos.html';
include_once 'header.php';

$profile_videos_handler = icms_getModuleHandler('videos');

/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = $_GET['op'];
if (isset($_POST['op'])) $clean_op = $_POST['op'];

/** Again, use a naming convention that indicates the source of the content of the variable */
$clean_videos_id = 0;
if (isset($_GET['videos_id'])) $clean_videos_id = intval($_GET['videos_id']);
if (isset($_POST['videos_id'])) $clean_videos_id = intval($_POST['videos_id']);
$real_uid = is_object($icmsUser)?intval($icmsUser->uid()):0;
$clean_uid = isset($_GET['uid']) ? intval($_GET['uid']) : $real_uid ;
$videosObj = $profile_videos_handler->get($clean_videos_id);
/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod','addvideos','del','');

$isAllowed = getAllowedItems('videos', $clean_uid);
if (!$isAllowed || !$icmsModuleConfig['enable_videos']) {
	redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
}
$xoopsTpl->assign('uid_owner',$uid);

/* Only proceed if the supplied operation is a valid operation */
if (in_array($clean_op,$valid_op,true)){
	switch ($clean_op) {
		case "mod":
			if ($clean_videos_id > 0 && $videosObj->isNew()) {
				redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			}
			editvideos($videosObj);
			break;

		case "addvideos":
			if (!$xoopsSecurity->check()) {
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', $xoopsSecurity->getErrors()));
			}
			include_once ICMS_ROOT_PATH.'/kernel/icmspersistablecontroller.php';
			$controller = new IcmsPersistableController($profile_videos_handler);
			$controller->storeFromDefaultForm(_MD_PROFILE_VIDEOS_CREATED, _MD_PROFILE_VIDEOS_MODIFIED);
			break;

		case "del":
			if (!$videosObj->userCanEditAndDelete()) {
				redirect_header($videosObj->getItemLink(true), 3, _NOPERM);
			}
			if (isset($_POST['confirm'])) {
				if (!$xoopsSecurity->check()) {
					redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', $xoopsSecurity->getErrors()));
				}
			}
			include_once ICMS_ROOT_PATH.'/kernel/icmspersistablecontroller.php';
			$controller = new IcmsPersistableController($profile_videos_handler);
			$controller->handleObjectDeletionFromUserSide();

			break;

		default:
			if ($real_uid && $real_uid == $uid) editvideos($videosObj, true);

			if ($clean_videos_id > 0) {
				$profile_videos_handler->updateCounter($clean_videos_id);
				$icmsTpl->assign('profile_single_video', $videosObj->toArray());
			} elseif ($clean_uid > 0) {
				$videosArray = $profile_videos_handler->getVideos(false, false, $clean_uid);
				$icmsTpl->assign('profile_allvideos', $videosArray);
				if (count($videosArray) == 0) $icmsTpl->assign('lang_nocontent', _MD_PROFILE_VIDEOS_NOCONTENT);
			} elseif ($real_uid > 0) {
				$videosArray = $profile_videos_handler->getVideos(false, false, $real_uid);
				$icmsTpl->assign('profile_allvideos', $videosArray);
				if (count($videosArray) == 0) $icmsTpl->assign('lang_nocontent', _MD_PROFILE_VIDEOS_NOCONTENT);
			} else {
				redirect_header(PROFILE_URL);
			}

			icms_makeSmarty(array(
				'lang_video'       => _MD_PROFILE_VIDEOS_VIDEO,
				'lang_description' => _MD_PROFILE_VIDEOS_DESCRIPTION,
				'lang_actions'     => _MD_PROFILE_VIDEOS_ACTIONS,
				'actions'          => is_object($icmsUser) && ($profile_isAdmin || $real_uid == $uid)
			));

			/* Generating meta information for this page */
			$icms_metagen = new IcmsMetagen($videosObj->getVar('video_desc'), $videosObj->getVar('meta_keywords','n'), $videosObj->getVar('meta_description', 'n'));
			$icms_metagen->createMetaTags();

			break;
	}
}
$icmsTpl->assign('profile_category_path', _MD_PROFILE_VIDEOS);

include_once 'footer.php';
?>