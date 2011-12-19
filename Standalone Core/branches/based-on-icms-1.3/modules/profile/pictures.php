<?php
/**
* Pictures page
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.3
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @package		profile
* @version		$Id: pictures.php 22413 2011-08-27 10:21:21Z phoenyx $
*/

/**
 * Edit a Picture
 *
 * @param object $picturesObj ProfilePicture object to be edited
 * @param bool   $hideForm
 * @global mod_profile_PicturesHandler $profile_pictures_handler picture handler
 * @global object $icmsTpl template
 *
*/
function editpictures($picturesObj, $hideForm=false) {
	global $profile_pictures_handler, $icmsTpl;

	$icmsTpl->assign('hideForm', $hideForm);
	if (!$picturesObj->isNew()){
		if (!$picturesObj->userCanEditAndDelete()) redirect_header($picturesObj->getItemLink(true), 3, _NOPERM);
		$picturesObj->hideFieldFromForm(array('url', 'creation_time', 'uid_owner'));
		$sform = $picturesObj->getSecureForm($hideForm ? '' : _MD_PROFILE_PICTURES_EDIT, 'addpictures');
		$sform->assign($icmsTpl, 'profile_picturesform');
		$icmsTpl->assign('lang_picturesform_title', _MD_PROFILE_PICTURES_EDIT);
	} else {
		if (!$profile_pictures_handler->userCanSubmit()) redirect_header(PROFILE_URL, 3, _NOPERM);
		if (!$profile_pictures_handler->checkUploadLimit()) return;
		$picturesObj->setVar('uid_owner', icms::$user->getVar('uid'));
		$picturesObj->setVar('creation_time', date(_DATESTRING));
		$picturesObj->hideFieldFromForm(array('creation_time', 'uid_owner'));
		$sform = $picturesObj->getSecureForm($hideForm ? '' : _MD_PROFILE_PICTURES_SUBMIT, 'addpictures');
		$sform->assign($icmsTpl, 'profile_picturesform');
		$icmsTpl->assign('lang_picturesform_title', _MD_PROFILE_PICTURES_SUBMIT);
	}
}

$profile_template = 'profile_pictures.html';
include_once 'header.php';

$profile_pictures_handler = icms_getModuleHandler('pictures', basename(dirname(__FILE__)), 'profile');

/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = $_GET['op'];
if (isset($_POST['op'])) $clean_op = $_POST['op'];

/** Again, use a naming convention that indicates the source of the content of the variable */
$clean_pictures_id = 0;
if (isset($_GET['pictures_id'])) $clean_pictures_id = (int)$_GET['pictures_id'];
if (isset($_POST['pictures_id'])) $clean_pictures_id = (int)$_POST['pictures_id'];
$real_uid = is_object(icms::$user) ? (int)icms::$user->getVar('uid') : 0;
$clean_uid = isset($_GET['uid']) ? (int)$_GET['uid'] : $real_uid ;
$picturesObj = $profile_pictures_handler->get($clean_pictures_id);

/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('setavatar', 'delavatar', 'mod', 'addpictures', 'del', '');

$isAllowed = $profile_configs_handler->userCanAccessSection('pictures', $clean_uid);
if (!$isAllowed || !icms::$module->config['enable_pictures']) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);

/**
 * Only proceed if the supplied operation is a valid operation
 */
if (in_array($clean_op,$valid_op,true)){
	switch ($clean_op) {
		case "setavatar":
			if (!icms::$security->check())
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED.implode('<br />', icms::$security->getErrors()));

			$profile_pictures_handler->makeAvatar($clean_pictures_id);
			break;
		case "delavatar":
			if (!icms::$security->check())
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));

			if ($uid != $real_uid || !is_object(icms::$user)) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);

			$avt_handler = icms::handler('icms_data_avatar');
			$oldavatar = icms::$user->getVar('user_avatar');
			if(!empty($oldavatar) && preg_match("/^cavt/", strtolower($oldavatar))) {
				$avatars = $avt_handler->getObjects(new icms_db_criteria_Item('avatar_file', $oldavatar));
				if(!empty($avatars) && count($avatars) == 1 && is_object($avatars[0])) {
					$avt_handler->delete($avatars[0]);
					$oldavatar_path = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH.'/'.$oldavatar));
					if (0 === strpos($oldavatar_path, ICMS_UPLOAD_PATH) && is_file($oldavatar_path)) {
						unlink($oldavatar_path);
					}
				}
			}

			icms::$user->setVar('user_avatar', 'blank.gif');
			if (icms::handler('icms_member_user')->insert(icms::$user)) {
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_PICTURES_AVATAR_DELETED);
			} else {
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_PICTURES_AVATAR_NOTDELETED);
			}

			break;
		case "mod":
			$picturesObj = $profile_pictures_handler->get($clean_pictures_id);
			if ($clean_pictures_id > 0 && $picturesObj->isNew()) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			editpictures($picturesObj);
			break;
		case "addpictures":
			if (!icms::$security->check())
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));

			// we need to check whether the user has modified the url for an existing picture (NOT ALLOWED!)
			if (!$picturesObj->isNew() && isset($_POST['url']) && $picturesObj->getVar('url') != $_POST['url'])
				redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);

			// check upload limit for this user
			if ($picturesObj->isNew() && !$profile_pictures_handler->checkUploadLimit())
				redirect_header(icms_getPreviousPage('index.php'), 3, sprintf(_MD_PROFILE_UPLOADLIMIT, icms::$module->config['nb_pict']));

			$controller = new icms_ipf_Controller($profile_pictures_handler);
			$controller->storeFromDefaultForm(_MD_PROFILE_PICTURES_CREATED, _MD_PROFILE_PICTURES_MODIFIED, PROFILE_URL.basename(__FILE__)."?uid=".$_POST['uid_owner']);
			break;
		case "del":
			$picturesObj = $profile_pictures_handler->get($clean_pictures_id);
			if (!$picturesObj->userCanEditAndDelete()) redirect_header($picturesObj->getItemLink(true), 3, _NOPERM);
			if (isset($_POST['confirm'])) {
				if (!icms::$security->check()) {
					redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));
				}
			}
			$controller = new icms_ipf_Controller($profile_pictures_handler);
			$controller->handleObjectDeletionFromUserSide();
			$icmsTpl->assign('profile_category_path', $picturesObj->getVar('title') . ' > ' . _DELETE);
			break;
		default:
			$clean_start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
			
			if ($real_uid && $real_uid == $uid){
				$picturesObj = $profile_pictures_handler->get($clean_pictures_id);
				editpictures($picturesObj, true);
			}
			if ($clean_uid > 0 || $real_uid > 0) {
				$uid = ($clean_uid > 0) ? $clean_uid : $real_uid;

				$picturesArray = $profile_pictures_handler->getPictures($clean_start, icms::$module->config['picturesperpage'], $uid);
				if (count($picturesArray) == 0) {
					$icmsTpl->assign('lang_nocontent', _MD_PROFILE_PICTURES_NOCONTENT);
				} else {
					$total_pictures_count = $profile_pictures_handler->getCount(new icms_db_criteria_Compo(new icms_db_criteria_Item('uid_owner', $uid)));

					$pagenav = new icms_view_PageNav($total_pictures_count, icms::$module->config['picturesperpage'], $clean_start, 'start', 'uid='.$uid);

					icms_makeSmarty(array(
						'profile_pictures_pagenav' => $pagenav->renderNav(),
						'profile_pictures'         => $picturesArray,
						'rowitems'                 => icms::$module->config['rowitems'],
						'itemwidth'                => round(100 / icms::$module->config['rowitems'], 0)
					));
					unset($total_pictures_count, $pagenav);
				}
			} else {
				redirect_header(PROFILE_URL);
			}

			$allow_avatar_upload = ($isOwner && is_object(icms::$user) && $icmsConfigUser['avatar_allow_upload'] == 1 && icms::$user->getVar('posts') >= $icmsConfigUser['avatar_minposts']);
			$icmsTpl->assign('allow_avatar_upload', $allow_avatar_upload);
			$icmsTpl->assign('lang_avatar', _MD_PROFILE_PICTURES_AVATAR_SET);
			break;
	}
}
$icmsTpl->assign('profile_category_path', _MD_PROFILE_PHOTOS);

include_once 'footer.php';
?>