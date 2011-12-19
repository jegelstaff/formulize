<?php
/**
* Pictures page
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.3
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @package		profile
* @version		$Id$
*/

/**
 * Edit a Picture
 *
 * @param object $picturesObj ProfilePicture object to be edited
 * @param bool   $hideForm
*/
function editpictures($picturesObj, $hideForm=false)
{
	global $profile_pictures_handler, $xoTheme, $icmsTpl, $icmsUser;

	$icmsTpl->assign('hideForm', $hideForm);
	if (!$picturesObj->isNew()){
		if (!$picturesObj->userCanEditAndDelete()) redirect_header($picturesObj->getItemLink(true), 3, _NOPERM);
		$picturesObj->hideFieldFromForm(array('url', 'creation_time', 'uid_owner', 'meta_keywords', 'meta_description', 'short_url'));
		$sform = $picturesObj->getSecureForm($hideForm ? '' : _MD_PROFILE_PICTURES_EDIT, 'addpictures');
		$sform->assign($icmsTpl, 'profile_picturesform');
		$icmsTpl->assign('lang_picturesform_title', _MD_PROFILE_PICTURES_EDIT);
	} else {
		if (!$profile_pictures_handler->userCanSubmit()) redirect_header(PROFILE_URL, 3, _NOPERM);
		if (!$profile_pictures_handler->checkUploadLimit()) return;
		$picturesObj->setVar('uid_owner', $icmsUser->uid());
		$picturesObj->setVar('creation_time', time());
		$picturesObj->hideFieldFromForm(array('creation_time', 'uid_owner', 'meta_keywords', 'meta_description', 'short_url'));
		$sform = $picturesObj->getSecureForm($hideForm ? '' : _MD_PROFILE_PICTURES_SUBMIT, 'addpictures');
		$sform->assign($icmsTpl, 'profile_picturesform');
		$icmsTpl->assign('lang_picturesform_title', _MD_PROFILE_PICTURES_SUBMIT);
	}
}


$profile_template = 'profile_pictures.html';
include_once 'header.php';

$profile_pictures_handler = icms_getModuleHandler('pictures');

/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';

if (isset($_GET['op'])) $clean_op = $_GET['op'];
if (isset($_POST['op'])) $clean_op = $_POST['op'];

/** Again, use a naming convention that indicates the source of the content of the variable */
$clean_pictures_id = 0;
if (isset($_GET['pictures_id'])) $clean_pictures_id = intval($_GET['pictures_id']);
if (isset($_POST['pictures_id'])) $clean_pictures_id = intval($_POST['pictures_id']);

$real_uid = is_object($icmsUser) ? intval($icmsUser->uid()) : 0;
$clean_uid = isset($_GET['uid']) ? intval($_GET['uid']) : $real_uid ;
$picturesObj = $profile_pictures_handler->get($clean_pictures_id);

/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('setavatar', 'delavatar', 'mod','addpictures','del','');

$isAllowed = getAllowedItems('pictures', $clean_uid);
if (!$isAllowed || !$icmsModuleConfig['enable_pictures']) {
	redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
}
$xoopsTpl->assign('uid_owner',$uid);

/**
 * Only proceed if the supplied operation is a valid operation
 */
if (in_array($clean_op,$valid_op,true)){
	switch ($clean_op) {
		case "setavatar":
			if(!$xoopsSecurity->check()) {
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', $xoopsSecurity->getErrors()));
				exit();
			}
			$profile_pictures_handler->makeAvatar($clean_pictures_id);

			break;
		case "delavatar":
			if(!$xoopsSecurity->check()) {
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', $xoopsSecurity->getErrors()));
				exit();
			}
			if ($uid != $real_uid || !is_object($icmsUser)) {
				redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
				exit();
			}

			$avt_handler =& xoops_gethandler('avatar');
			$oldavatar = $icmsUser->getVar('user_avatar');
			if(!empty($oldavatar) && preg_match("/^cavt/", strtolower($oldavatar))) {
				$avatars =& $avt_handler->getObjects(new Criteria('avatar_file', $oldavatar));
				if(!empty($avatars) && count($avatars) == 1 && is_object($avatars[0])) {
					$avt_handler->delete($avatars[0]);
					$oldavatar_path = str_replace("\\", "/", realpath(ICMS_UPLOAD_PATH.'/'.$oldavatar));
					if(0 === strpos($oldavatar_path, ICMS_UPLOAD_PATH) && is_file($oldavatar_path)) {
						unlink($oldavatar_path);
					}
				}
			}

			$icmsUser->setVar('user_avatar', 'blank.gif');
			$user_handler =& xoops_gethandler('user');

			if($user_handler->insert($icmsUser)) {
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_PICTURES_AVATAR_DELETED);
			} else {
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_PICTURES_AVATAR_NOTDELETED);
			}

			break;

		case "mod":
			$picturesObj = $profile_pictures_handler->get($clean_pictures_id);
			if ($clean_pictures_id > 0 && $picturesObj->isNew()) {
				redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			}
			editpictures($picturesObj);
			break;

		case "addpictures":
			if (!$xoopsSecurity->check()) {
				redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', $xoopsSecurity->getErrors()));
			}

			// we need to check whether the user has modified the url for an existing picture (NOT ALLOWED!)
			if (!$picturesObj->isNew() && isset($_POST['url']) && $picturesObj->getVar('url') != $_POST['url']) {
				redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			}

			// check upload limit for this user
			if ($picturesObj->isNew() && !$profile_pictures_handler->checkUploadLimit()) {
				redirect_header(icms_getPreviousPage('index.php'), 3, sprintf(_MD_PROFILE_UPLOADLIMIT, $icmsModuleConfig['nb_pict']));
			}

			include_once ICMS_ROOT_PATH.'/kernel/icmspersistablecontroller.php';
			$controller = new IcmsPersistableController($profile_pictures_handler);
			$controller->storeFromDefaultForm(_MD_PROFILE_PICTURES_CREATED, _MD_PROFILE_PICTURES_MODIFIED);
			break;

		case "del":
			$picturesObj = $profile_pictures_handler->get($clean_pictures_id);
			if (!$picturesObj->userCanEditAndDelete()) {
				redirect_header($picturesObj->getItemLink(true), 3, _NOPERM);
			}
			if (isset($_POST['confirm'])) {
				if (!$xoopsSecurity->check()) {
					redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', $xoopsSecurity->getErrors()));
				}
			}
			include_once ICMS_ROOT_PATH.'/kernel/icmspersistablecontroller.php';
			$controller = new IcmsPersistableController($profile_pictures_handler);
			$controller->handleObjectDeletionFromUserSide();
			$icmsTpl->assign('profile_category_path', $picturesObj->getVar('title') . ' > ' . _DELETE);
			break;

		default:
			$clean_start = isset($_GET['start']) ? intval($_GET['start']) : 0;
			
			if($real_uid && $real_uid == $uid){
				$picturesObj = $profile_pictures_handler->get($clean_pictures_id);
				editpictures($picturesObj, true);
			}
			if ($clean_pictures_id > 0) {
				$profile_pictures_handler->updateCounter($clean_pictures_id);
				$icmsTpl->assign('profile_picture', $picturesObj->toArray());
			} elseif ($clean_uid > 0 || $real_uid > 0) {
				$uid = ($clean_uid > 0) ? $clean_uid : $real_uid;

				$picturesArray = $profile_pictures_handler->getPictures($clean_start, $icmsModuleConfig['picturesperpage'], $uid);
				if (count($picturesArray) == 0) {
					$icmsTpl->assign('lang_nocontent', _MD_PROFILE_PICTURES_NOCONTENT);
				} else {
					$total_pictures_count = $profile_pictures_handler->getCount(new CriteriaCompo(new Criteria('uid_owner', $uid)));

					include_once ICMS_ROOT_PATH.'/class/pagenav.php';
					$pagenav = new XoopsPageNav($total_pictures_count, $icmsModuleConfig['picturesperpage'], $clean_start, 'start', 'uid='.$uid);

					$icmsTpl->assign('profile_pictures_pagenav', $pagenav->renderNav());
					$icmsTpl->assign('profile_pictures', $picturesArray);
					unset($total_pictures_count, $pagenav);
				}
			} else {
				redirect_header(PROFILE_URL);
			}

			$allow_avatar_upload = ($isOwner && is_object($icmsUser) && $icmsConfigUser['avatar_allow_upload'] == 1 && $icmsUser->getVar('posts') >= $icmsConfigUser['avatar_minposts']);
			$icmsTpl->assign('allow_avatar_upload', $allow_avatar_upload);
			$icmsTpl->assign('lang_avatar', _MD_PROFILE_PICTURES_AVATAR_SET);

			/**
			 * Generating meta information for this page
			 */
			$icms_metagen = new IcmsMetagen($picturesObj->getVar('title'), $picturesObj->getVar('meta_keywords','n'), $picturesObj->getVar('meta_description', 'n'));
			$icms_metagen->createMetaTags();

			break;
	}
}
$icmsTpl->assign('profile_category_path', _MD_PROFILE_PHOTOS);

include_once 'footer.php';
?>