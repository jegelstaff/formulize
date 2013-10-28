<?php
/**
 * Admin page to manage videoss
 *
 * List, add, edit and delete videos objects
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.3
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @package		profile
 * @version		$Id: videos.php 20428 2010-11-21 12:38:18Z phoenyx $
 */

/**
 * Edit a Video
 *
 * @param int $videos_id Videosid to be edited
 * @global mod_profile_VideosHandler $profile_videos_handler object handler
 * @global object $icmsAdminTpl template object
 */
function editvideos($videos_id = 0) {
	global $profile_videos_handler, $icmsAdminTpl;

	$videosObj = $profile_videos_handler->get($videos_id);

	if (!$videosObj->isNew()){
		icms::$module->displayAdminMenu(10, _AM_PROFILE_VIDEOS." > "._CO_ICMS_EDITING);
		$sform = $videosObj->getForm(_AM_PROFILE_VIDEOS_EDIT, 'addvideos');
		$sform->assign($icmsAdminTpl);
	} else {
		icms::$module->displayAdminMenu(10, _AM_PROFILE_VIDEOS." > "._CO_ICMS_CREATINGNEW);
		$sform = $videosObj->getForm(_AM_PROFILE_VIDEOS_CREATE, 'addvideos');
		$sform->assign($icmsAdminTpl);
	}
	$icmsAdminTpl->display('db:profile_admin_videos.html');
}

include_once 'admin_header.php';

$profile_videos_handler = icms_getModuleHandler('videos', basename(dirname(dirname(__FILE__))), 'profile');
/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);
$clean_videos_id = isset($_GET['videos_id']) ? (int)$_GET['videos_id'] : 0;

/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod', 'changedField', 'addvideos', 'del', 'view', '');

/**
 * in_array() is a native PHP function that will determine if the value of the
 * first argument is found in the array listed in the second argument. Strings
 * are case sensitive and the 3rd argument determines whether type matching is
 * required
*/
if (in_array($clean_op, $valid_op, true)){
	switch ($clean_op) {
		case "mod":
		case "changedField":
			icms_cp_header();
			editvideos($clean_videos_id);
			break;
		case "del":
			$controller = new icms_ipf_Controller($profile_videos_handler);
			$controller->handleObjectDeletion();
			break;
		default:
			icms_cp_header();
			icms::$module->displayAdminMenu(10, _AM_PROFILE_VIDEOS);

			$objectTable = new icms_ipf_view_Table($profile_videos_handler);
			$objectTable->addColumn(new icms_ipf_view_Column('videos_id'));
			$objectTable->addColumn(new icms_ipf_view_Column('uid_owner', false, false, 'getVideoSender'));
			$objectTable->addColumn(new icms_ipf_view_Column('video_title', _GLOBAL_LEFT, false, 'getVideoTitle'));
			$objectTable->addColumn(new icms_ipf_view_Column('video_desc'));
			$objectTable->addColumn(new icms_ipf_view_Column('youtube_code', 'center', 330, 'getVideoToDisplay', false, false, false));
			$objectTable->addQuickSearch(array('video_title', 'video_desc', 'youtube_code'));
			$objectTable->setDefaultSort('videos_id');
			$objectTable->setDefaultOrder('DESC');

			$icmsAdminTpl->assign('profile_videos_table', $objectTable->fetch());
			$icmsAdminTpl->display('db:profile_admin_videos.html');
			break;
	}
	icms_cp_footer();
}
?>