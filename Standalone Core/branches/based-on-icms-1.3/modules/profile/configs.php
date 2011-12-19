<?php
/**
* configs page
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.3
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @package		profile
* @version		$Id: configs.php 20428 2010-11-21 12:38:18Z phoenyx $
*/

/**
 * Edit a config
 *
 * @param	object	$configsObj					Profileconfig object to be edited
 * @param	int		$uid						user id
 * @global	object	$profile_configs_handler	Profile config handler
 * @global	object	$icmsTpl					Template object
 * @global	bool	$profile_isAdmin			true if current user is admin for this module
 */
function editconfigs($configsObj, $uid=0) {
	global $profile_configs_handler, $icmsTpl, $profile_isAdmin;

	// hide fields in regard to module preferences
	if (!icms::$module->config['profile_search']) $configsObj->hideFieldFromForm('profile_usercontributions');
	if (!icms::$module->config['enable_pictures']) $configsObj->hideFieldFromForm('pictures');
	if (!icms::$module->config['enable_videos']) $configsObj->hideFieldFromForm('videos');
	if (!icms::$module->config['enable_audio']) $configsObj->hideFieldFromForm('audio');
	if (!icms::$module->config['enable_friendship']) $configsObj->hideFieldFromForm('friendship');
	if (!icms::$module->config['enable_tribes']) $configsObj->hideFieldFromForm('tribes');

	if($profile_isAdmin && $uid > 0 && icms::$user->getVar('uid') != $uid) {
		$configsObj->setVar('config_uid', $uid);
		$configsObj->hideFieldFromForm(array('pictures', 'audio', 'videos', 'friendship', 'tribes', 'profile_usercontributions'));
		$sform = $configsObj->getSecureForm(_MD_PROFILE_CONFIGS_EDIT, 'addconfigs');
		$sform->assign($icmsTpl, 'profile_configsform');
	} elseif (!$configsObj->isNew()) {
		if (!$configsObj->userCanEditAndDelete()) redirect_header($configsObj->getItemLink(true), 3, _NOPERM);

		$configsObj->hideFieldFromForm(array('suspension', 'end_suspension'));
		$sform = $configsObj->getSecureForm(_MD_PROFILE_CONFIGS_EDIT, 'addconfigs');
		$sform->assign($icmsTpl, 'profile_configsform');
	} else {
		if (!$profile_configs_handler->userCanSubmit()) redirect_header(PROFILE_URL, 3, _NOPERM);

		$configsObj->setVar('config_uid', icms::$user->getVar('uid'));
		$configsObj->hideFieldFromForm(array('suspension', 'end_suspension'));
		$sform = $configsObj->getSecureForm(_MD_PROFILE_CONFIGS_SUBMIT, 'addconfigs');
		$sform->assign($icmsTpl, 'profile_configsform');
	}
}

$profile_template = 'profile_configs.html';
$profile_current_page = basename(__FILE__);
include_once 'header.php';

$profile_configs_handler = icms_getModuleHandler('configs', basename(dirname(__FILE__)), 'profile');

/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = $_GET['op'];
if (isset($_POST['op'])) $clean_op = $_POST['op'];

/** Again, use a naming convention that indicates the source of the content of the variable */
$real_uid = is_object(icms::$user) ? (int)icms::$user->getVar('uid') : 0;
$clean_uid = isset($_GET['uid']) ? (int)$_GET['uid'] : $real_uid;
$configsObj = $profile_configs_handler->getConfigPerUser($clean_uid, true);

/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition */
$valid_op = array('addconfigs', 'suspend', '');

if (!is_object(icms::$user) || icms::$module->config['profile_social'] == false) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);

/** Only proceed if the supplied operation is a valid operation */
if (in_array($clean_op, $valid_op, true)){
	switch ($clean_op) {
		case "suspend":
			if (empty($clean_uid) || !$profile_isAdmin) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			editconfigs($configsObj, $clean_uid);
			break;
		case "addconfigs":
			if (!icms::$security->check()) redirect_header(icms_getPreviousPage('index.php'), 3, _MD_PROFILE_SECURITY_CHECK_FAILED.implode('<br />', icms::$security->getErrors()));

			//check if current user is allowed to perform this action
			if ($real_uid == 0 || ($real_uid != (int)$_POST['config_uid'] && !$profile_isAdmin)) redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			
			$controller = new icms_ipf_Controller($profile_configs_handler);
			$controller->storeFromDefaultForm(_MD_PROFILE_CONFIGS_CREATED, _MD_PROFILE_CONFIGS_MODIFIED, PROFILE_URL);
			break;
		default:
			if ($real_uid > 0 &&  icms::$user->getVar('uid') == $clean_uid) {
				editconfigs($configsObj);
			} elseif ($profile_isAdmin && $clean_uid > 0) {
				$configsObj = $profile_configs_handler->getConfigPerUser($clean_uid, true);
				editconfigs($configsObj, $clean_uid);
			} else {
				redirect_header(icms_getPreviousPage('index.php'), 3, _NOPERM);
			}
			break;
	}
}
$icmsTpl->assign('profile_category_path', _MD_PROFILE_CONFIGS);

include_once 'footer.php';
?>