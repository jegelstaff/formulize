<?php
/**
* Audios page
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.3
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @package		profile
* @version		$Id: audio.php 20510 2010-12-11 12:08:20Z phoenyx $
*/

/**
 * Edit a Audio
 *
 * @param mod_profile_Audio $audioObj object to be edited
*/
function editaudio($audioObj, $hideForm=false) {
	global $profile_audio_handler, $icmsTpl;

	$icmsTpl->assign('hideForm', $hideForm);
	if (!$audioObj->isNew()){
		if (!$audioObj->userCanEditAndDelete()) redirect_header($audioObj->getItemLink(true), 3, _NOPERM);
		$audioObj->hideFieldFromForm(array('creation_time', 'uid_owner', 'url'));
		$sform = $audioObj->getSecureForm($hideForm ? '' : _MD_PROFILE_AUDIOS_EDIT, 'addaudio');
		$sform->assign($icmsTpl, 'profile_audioform');
		$icmsTpl->assign('lang_audioform_title', _MD_PROFILE_AUDIOS_EDIT);
	} else {
		if (!$profile_audio_handler->userCanSubmit()) redirect_header(PROFILE_URL, 3, _NOPERM);
		if (!$profile_audio_handler->checkUploadLimit()) return;
		$audioObj->setVar('uid_owner', icms::$user->getVar('uid'));
		$audioObj->setVar('creation_time', date(_DATESTRING));
		$audioObj->hideFieldFromForm(array('creation_time', 'uid_owner'));
		$sform = $audioObj->getSecureForm($hideForm ? '' : _MD_PROFILE_AUDIOS_SUBMIT, 'addaudio');
		$sform->assign($icmsTpl, 'profile_audioform');
		$icmsTpl->assign('lang_audioform_title', _MD_PROFILE_AUDIOS_SUBMIT);
	}
}


$profile_template = 'profile_audio.html';
include_once 'header.php';

$profile_audio_handler = icms_getModuleHandler('audio', basename(dirname(__FILE__)), 'profile');

/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';

if (isset($_GET['op'])) $clean_op = $_GET['op'];
if (isset($_POST['op'])) $clean_op = $_POST['op'];

/** Again, use a naming convention that indicates the source of the content of the variable */
$clean_audio_id = 0;
if (isset($_GET['audio_id'])) $clean_audio_id = (int)($_GET['audio_id']);
if (isset($_POST['audio_id'])) $clean_audio_id = (int)($_POST['audio_id']);
$real_uid = is_object(icms::$user) ? (int)icms::$user->getVar('uid') : 0;
$clean_uid = isset($_GET['uid']) ? (int)$_GET['uid'] : $real_uid ;
$audioObj = $profile_audio_handler->get($clean_audio_id);
/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod', 'addaudio', 'del', '');

$isAllowed = $profile_configs_handler->userCanAccessSection('audio', $clean_uid);
if (!$isAllowed || !icms::$module->config['enable_audio']) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
$icmsTpl->assign('uid_owner', $uid);

/**
 * Only proceed if the supplied operation is a valid operation
 */
if (in_array($clean_op,$valid_op,true)){
  switch ($clean_op) {
	case "mod":
		if ($clean_audio_id > 0 && $audioObj->isNew()) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
		editaudio($audioObj);
		break;
	case "addaudio":
		if (!icms::$security->check()) {
			redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));
		}

		// check upload limit for this user
		if ($audioObj->isNew() && !$profile_audio_handler->checkUploadLimit()) {
			redirect_header(icms_getPreviousPage('index.php'), 3, sprintf(_MD_PROFILE_UPLOADLIMIT, icms::$module->config['nb_audio']));
		}

		$controller = new icms_ipf_Controller($profile_audio_handler);
		$controller->storeFromDefaultForm(_MD_PROFILE_AUDIOS_CREATED, _MD_PROFILE_AUDIOS_MODIFIED, PROFILE_URL.basename(__FILE__));
		break;
	case "del":
		if ($audioObj->isNew() || !$audioObj->userCanEditAndDelete()) redirect_header(PROFILE_URL.basename(__FILE__), 3, _NOPERM);

		if (isset($_POST['confirm'])) {
		    if (!icms::$security->check()) {
		    	redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED . implode('<br />', icms::$security->getErrors()));
		    }
		}

		$controller = new icms_ipf_Controller($profile_audio_handler);
		$controller->handleObjectDeletionFromUserSide();
		$icmsTpl->assign('profile_category_path', $audioObj->getVar('title') . ' > ' . _DELETE);
		break;
	default:
		$clean_start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
		if($real_uid && $real_uid == $uid) editaudio($audioObj, true);

		if ($clean_uid > 0 || $real_uid > 0) {
			$uid = ($clean_uid > 0) ? $clean_uid : $real_uid;

			$audiosArray = $profile_audio_handler->getAudios($clean_start, icms::$module->config['audiosperpage'], $uid);
			if (count($audiosArray) == 0) {
				$icmsTpl->assign('lang_nocontent', _MD_PROFILE_AUDIOS_NOCONTENT);
			} else {
				$total_audios_count = $profile_audio_handler->getCount(new icms_db_criteria_Compo(new icms_db_criteria_Item('uid_owner', $uid)));

				$pagenav = new icms_view_PageNav($total_audios_count, icms::$module->config['audiosperpage'], $clean_start, 'start', 'uid=' . $uid);

				$icmsTpl->assign('profile_audios_pagenav', $pagenav->renderNav());
				$icmsTpl->assign('profile_audios', $audiosArray);
				unset($total_audios_count, $pagenav);
			}
		} else {
			redirect_header(PROFILE_URL);
		}

		icms_makeSmarty(array(
			'lang_player'      => _MD_PROFILE_AUDIOS_PLAYER,
			'lang_author'      => _MD_PROFILE_AUDIOS_AUTHOR,
			'lang_title'       => _MD_PROFILE_AUDIOS_TITLE,
			'lang_lastupdated' => _MD_PROFILE_AUDIOS_LASTUPDATED,
			'lang_actions'     => _MD_PROFILE_AUDIOS_ACTIONS,
			'actions'          => is_object(icms::$user) && ($profile_isAdmin || $real_uid == $uid)
		));

		break;
	}
}
$icmsTpl->assign('profile_category_path', _MD_PROFILE_AUDIOS);

include_once 'footer.php';
?>