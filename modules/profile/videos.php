<?php
/**
* Videos page
*
* @copyright	GNU General Public License (GPL)
* @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since	1.3
* @author	Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @package	profile
* @version	$Id: videos.php 20510 2010-12-11 12:08:20Z phoenyx $
*/

/**
 * Edit a Video
 *
 * @param object $videosObj ProfileVideo object to be edited
 * @param bool $hideForm true if form should be hidden
 * @global mod_profile_VideosHandler $profile_videos_handler object handler
 * @global object $icmsTpl template object
 */
function editvideos($videosObj, $hideForm = false) {
	global $profile_videos_handler, $icmsTpl;

	$icmsTpl->assign('hideForm', $hideForm);
	if (!$videosObj->isNew()){
		if (!$videosObj->userCanEditAndDelete()) redirect_header($videosObj->getItemLink(true), 3, _NOPERM);
		$videosObj->hideFieldFromForm(array('uid_owner', 'creation_time'));
		$sform = $videosObj->getSecureForm($hideForm ? '' : _MD_PROFILE_VIDEOS_EDIT, 'addvideos');
		$sform->assign($icmsTpl, 'profile_videosform');
		$icmsTpl->assign('lang_videosform_title', _MD_PROFILE_VIDEOS_EDIT);
	} else {
		if (!$profile_videos_handler->userCanSubmit()) redirect_header(PROFILE_URL, 3, _NOPERM);
		$videosObj->setVar('uid_owner', icms::$user->getVar('uid'));
		$videosObj->setVar('creation_time', date(_DATESTRING));
		$videosObj->hideFieldFromForm(array('creation_time', 'uid_owner'));
		$sform = $videosObj->getSecureForm($hideForm ? '' : _MD_PROFILE_VIDEOS_SUBMIT, 'addvideos');
		$sform->assign($icmsTpl, 'profile_videosform');
		$icmsTpl->assign('lang_videosform_title', _MD_PROFILE_VIDEOS_SUBMIT);
	}
}


$profile_template = 'profile_videos.html';
include_once 'header.php';

$profile_videos_handler = icms_getModuleHandler('videos', basename(dirname(__FILE__)), 'profile');

/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = $_GET['op'];
if (isset($_POST['op'])) $clean_op = $_POST['op'];

/** Again, use a naming convention that indicates the source of the content of the variable */
$clean_videos_id = 0;
if (isset($_GET['videos_id'])) $clean_videos_id = (int)$_GET['videos_id'];
if (isset($_POST['videos_id'])) $clean_videos_id = (int)$_POST['videos_id'];
$real_uid = is_object(icms::$user)? (int)icms::$user->getVar('uid') : 0;
$clean_uid = isset($_GET['uid']) ? (int)$_GET['uid'] : $real_uid ;
$videosObj = $profile_videos_handler->get($clean_videos_id);
/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod', 'addvideos', 'del', '');

$isAllowed = $profile_configs_handler->userCanAccessSection('videos', $clean_uid);
if (!$isAllowed || !icms::$module->config['enable_videos']) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
$icmsTpl->assign('uid_owner',$uid);

/* Only proceed if the supplied operation is a valid operation */
if (in_array($clean_op,$valid_op,true)){
	switch ($clean_op) {
		case "mod":
			if ($clean_videos_id > 0 && $videosObj->isNew()) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			editvideos($videosObj);
			break;
		case "addvideos":
			if (!icms::$security->check()) redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED.implode('<br />', icms::$security->getErrors()));
			$controller = new icms_ipf_Controller($profile_videos_handler);
			$controller->storeFromDefaultForm(_MD_PROFILE_VIDEOS_CREATED, _MD_PROFILE_VIDEOS_MODIFIED, PROFILE_URL.basename(__FILE__));
			break;
		case "del":
			if (!$videosObj->userCanEditAndDelete()) redirect_header($videosObj->getItemLink(true), 3, _NOPERM);
			if (isset($_POST['confirm']) && !icms::$security->check()) redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));
			$controller = new icms_ipf_Controller($profile_videos_handler);
			$controller->handleObjectDeletionFromUserSide();
			break;
		default:
			$clean_start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
			if ($real_uid && $real_uid == $uid) editvideos($videosObj, true);

			if ($clean_uid > 0 || $real_uid > 0) {
				$uid = ($clean_uid > 0) ? $clean_uid : $real_uid;

				$videosArray = $profile_videos_handler->getVideos($clean_start, icms::$module->config['videosperpage'], $clean_uid);
				if (count($videosArray) == 0) {
					$icmsTpl->assign('lang_nocontent', _MD_PROFILE_VIDEOS_NOCONTENT);
				} else {
					$total_videos_count = $profile_videos_handler->getCount(new icms_db_criteria_Compo(new icms_db_criteria_Item('uid_owner', $uid)));

					$pagenav = new icms_view_PageNav($total_videos_count, icms::$module->config['videosperpage'], $clean_start, 'start', 'uid='.$uid);

					$icmsTpl->assign('profile_videos_pagenav', $pagenav->renderNav());
					$icmsTpl->assign('profile_videos', $videosArray);
					unset($total_videos_count, $pagenav);
				}
			} else {
				redirect_header(PROFILE_URL);
			}

			icms_makeSmarty(array(
				'lang_video'       => _MD_PROFILE_VIDEOS_VIDEO,
				'lang_description' => _MD_PROFILE_VIDEOS_DESCRIPTION,
				'lang_actions'     => _MD_PROFILE_VIDEOS_ACTIONS,
				'actions'          => is_object(icms::$user) && ($profile_isAdmin || $real_uid == $uid)
			));

			break;
	}
}
$icmsTpl->assign('profile_category_path', _MD_PROFILE_VIDEOS);

include_once 'footer.php';
?>