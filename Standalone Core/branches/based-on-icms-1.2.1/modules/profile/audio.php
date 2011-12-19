<?php
/**
* Audios page
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.3
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @package		profile
* @version		$Id$
*/

/**
 * Edit a Audio
 *
 * @param object $audioObj ProfileAudio object to be edited
*/
function editaudio($audioObj, $hideForm=false)
{
	global $profile_audio_handler, $xoTheme, $icmsTpl, $icmsUser;

	$icmsTpl->assign('hideForm', $hideForm);
	if (!$audioObj->isNew()){
		if (!$audioObj->userCanEditAndDelete()) redirect_header($audioObj->getItemLink(true), 3, _NOPERM);
		$audioObj->hideFieldFromForm(array('creation_time', 'uid_owner', 'meta_keywords', 'meta_description', 'short_url', 'url'));
		$sform = $audioObj->getSecureForm($hideForm ? '' : _MD_PROFILE_AUDIOS_EDIT, 'addaudio');
		$sform->assign($icmsTpl, 'profile_audioform');
		$icmsTpl->assign('lang_audioform_title', _MD_PROFILE_AUDIOS_EDIT);
	} else {
		if (!$profile_audio_handler->userCanSubmit()) redirect_header(PROFILE_URL, 3, _NOPERM);
		if (!$profile_audio_handler->checkUploadLimit()) return;
		$audioObj->setVar('uid_owner', $icmsUser->uid());
		$audioObj->setVar('creation_time', time());
		$audioObj->hideFieldFromForm(array('creation_time', 'uid_owner', 'meta_keywords', 'meta_description', 'short_url'));
		$sform = $audioObj->getSecureForm($hideForm ? '' : _MD_PROFILE_AUDIOS_SUBMIT, 'addaudio');
		$sform->assign($icmsTpl, 'profile_audioform');
		$icmsTpl->assign('lang_audioform_title', _MD_PROFILE_AUDIOS_SUBMIT);
	}
}


$profile_template = 'profile_audio.html';
include_once 'header.php';

$profile_audio_handler = icms_getModuleHandler('audio');

/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';

if (isset($_GET['op'])) $clean_op = $_GET['op'];
if (isset($_POST['op'])) $clean_op = $_POST['op'];

/** Again, use a naming convention that indicates the source of the content of the variable */
global $icmsUser;
$clean_audio_id = isset($_GET['audio_id']) ? intval($_GET['audio_id']) : 0 ;
$real_uid = is_object($icmsUser)?intval($icmsUser->uid()):0;
$clean_uid = isset($_GET['uid']) ? intval($_GET['uid']) : $real_uid ;
$audioObj = $profile_audio_handler->get($clean_audio_id);
/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod','addaudio','del','');

$isAllowed = getAllowedItems('audio', $clean_uid);
if (!$isAllowed || !$icmsModuleConfig['enable_audio']) {
	redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
}
$xoopsTpl->assign('uid_owner',$uid);

/**
 * Only proceed if the supplied operation is a valid operation
 */
if (in_array($clean_op,$valid_op,true)){
  switch ($clean_op) {
	case "mod":
		$audioObj = $profile_audio_handler->get($clean_audio_id);
		if ($clean_audio_id > 0 && $audioObj->isNew()) {
			redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			exit();
		}
		editaudio($audioObj);
		break;

	case "addaudio":
		if (!$xoopsSecurity->check()) {
			redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', $xoopsSecurity->getErrors()));
			exit();
		}

		// check upload limit for this user
		if ($audioObj->isNew() && !$profile_audio_handler->checkUploadLimit()) {
			redirect_header(icms_getPreviousPage('index.php'), 3, sprintf(_MD_PROFILE_UPLOADLIMIT, $icmsModuleConfig['nb_audio']));
		}

		include_once ICMS_ROOT_PATH.'/kernel/icmspersistablecontroller.php';
		$controller = new IcmsPersistableController($profile_audio_handler);
		$controller->storeFromDefaultForm(_MD_PROFILE_AUDIOS_CREATED, _MD_PROFILE_AUDIOS_MODIFIED);
		break;

	case "del":
		$audioObj = $profile_audio_handler->get($clean_audio_id);
		if (!$audioObj->userCanEditAndDelete()) {
			redirect_header($audioObj->getItemLink(true), 3, _NOPERM);
			exit();
		}
		if (isset($_POST['confirm'])) {
		    if (!$xoopsSecurity->check()) {
		    	redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', $xoopsSecurity->getErrors()));
			exit();
		    }
		}
		include_once ICMS_ROOT_PATH.'/kernel/icmspersistablecontroller.php';
		$controller = new IcmsPersistableController($profile_audio_handler);
		$controller->handleObjectDeletionFromUserSide();
		$icmsTpl->assign('profile_category_path', $audioObj->getVar('title') . ' > ' . _DELETE);

		break;

	default:
		$clean_start = isset($_GET['start']) ? intval($_GET['start']) : 0;

		if($real_uid && $real_uid == $uid){
			$audioObj = $profile_audio_handler->get($clean_audio_id);
			editaudio($audioObj, true);
		}
		if ($clean_audio_id > 0) {
			$profile_audio_handler->updateCounter($clean_audio_id);
			$icmsTpl->assign('profile_single_audio', $audioObj->toArray());
		} elseif ($clean_uid > 0 || $real_uid > 0) {
			$uid = ($clean_uid > 0) ? $clean_uid : $real_uid;

			$audiosArray = $profile_audio_handler->getAudios($clean_start, $icmsModuleConfig['audiosperpage'], $uid);
			if (count($audiosArray) == 0) {
				$icmsTpl->assign('lang_nocontent', _MD_PROFILE_AUDIOS_NOCONTENT);
			} else {
				$total_audios_count = $profile_audio_handler->getCount(new CriteriaCompo(new Criteria('uid_owner', $uid)));

				include_once ICMS_ROOT_PATH.'/class/pagenav.php';
				$pagenav = new XoopsPageNav($total_audios_count, $icmsModuleConfig['audiosperpage'], $clean_start, 'start', 'uid='.$uid);

				$icmsTpl->assign('profile_audios_pagenav', $pagenav->renderNav());
				$icmsTpl->assign('profile_audios', $audiosArray);
				unset($total_audios_count, $pagenav);
			}
		} else {
			redirect_header(PROFILE_URL);
		}

		icms_makeSmarty(array(
			'lang_player'  => _MD_PROFILE_AUDIOS_PLAYER,
			'lang_author'  => _MD_PROFILE_AUDIOS_AUTHOR,
			'lang_title'   => _MD_PROFILE_AUDIOS_TITLE,
			'lang_actions' => _MD_PROFILE_AUDIOS_ACTIONS,
			'actions'      => is_object($icmsUser) && ($profile_isAdmin || $real_uid == $uid)

		));

		/**
		 * Generating meta information for this page
		 */
		$icms_metagen = new IcmsMetagen($audioObj->getVar('title'), $audioObj->getVar('meta_keywords','n'), $audioObj->getVar('meta_description', 'n'));
		$icms_metagen->createMetaTags();

		break;
	}
}
$icmsTpl->assign('profile_category_path', _MD_PROFILE_AUDIOS);

include_once 'footer.php';
?>