<?php
/**
 * Admin page to manage tribeusers
 *
 * List and delete tribeuser objects
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.3
 * @package		profile
 * @version		$Id: tribeuser.php 20428 2010-11-21 12:38:18Z phoenyx $
 */

/**
 * Add/Edit a Tribeuser
 */
function edittribeuser($tribeuser_id) {
	global $profile_tribeuser_handler, $icmsAdminTpl;

	$tribeuserObj = $profile_tribeuser_handler->get($tribeuser_id);
	if (!$tribeuserObj->isNew()) {
		$tribeuserObj->hideFieldFromForm('tribe_id');
		$tribeuserObj->hideFieldFromForm('user_id');
		icms::$module->displayAdminMenu(8, _AM_PROFILE_TRIBEUSERS." > "._CO_ICMS_EDITING);
		$sform = $tribeuserObj->getForm(_AM_PROFILE_TRIBEUSER_MODIFY, 'addtribeuser');
	} else {
		icms::$module->displayAdminMenu(8, _AM_PROFILE_TRIBEUSERS." > "._CO_ICMS_CREATINGNEW);
		$sform = $tribeuserObj->getForm(_AM_PROFILE_TRIBEUSER_CREATE, 'addtribeuser');
	}
	
	$sform->assign($icmsAdminTpl);
	$icmsAdminTpl->display('db:profile_admin_tribeuser.html');
}

include_once 'admin_header.php';

$profile_tribeuser_handler = icms_getModuleHandler('tribeuser', basename(dirname(dirname(__FILE__))), 'profile');
/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);

$clean_tribeuser_id = isset($_GET['tribeuser_id']) ? (int) $_GET['tribeuser_id'] : 0 ;

/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod', 'addtribeuser', 'del', '');

/**
 * in_array() is a native PHP function that will determine if the value of the
 * first argument is found in the array listed in the second argument. Strings
 * are case sensitive and the 3rd argument determines whether type matching is
 * required
 */
if (in_array($clean_op, $valid_op, true)) {
	switch ($clean_op) {
		case "mod":
			$profile_tribes_handler = icms_getModuleHandler('tribes', basename(dirname(dirname(__FILE__))), 'profile');
			$tribes = $profile_tribes_handler->getAllTribes();
			if (count($tribes) == 0) redirect_header(PROFILE_ADMIN_URL.'tribeuser.php', 3, _AM_PROFILE_TRIBEUSER_NOTTRIBESYET);

			icms_cp_header();
			edittribeuser($clean_tribeuser_id);
			break;
		case "addtribeuser":
			$controller = new icms_ipf_Controller($profile_tribeuser_handler);
			$controller->storeFromDefaultForm(_AM_PROFILE_TRIBEUSER_CREATED, _AM_PROFILE_TRIBEUSER_MODIFIED);
			break;
		case "del":
			$controller = new icms_ipf_Controller($profile_tribeuser_handler);
			$controller->handleObjectDeletion();
			break;
		default:
			icms_cp_header();
			icms::$module->displayAdminMenu(8, _AM_PROFILE_TRIBEUSERS);

			$objectTable = new icms_ipf_view_Table($profile_tribeuser_handler);
			$objectTable->addColumn(new icms_ipf_view_Column('tribeuser_id', _GLOBAL_LEFT, false, 'getTribeuserId'));
			$objectTable->addColumn(new icms_ipf_view_Column('tribe_id', _GLOBAL_LEFT, false, 'getTribeName', false, false, false));
			$objectTable->addColumn(new icms_ipf_view_Column('user_id', _GLOBAL_LEFT, false, 'getTribeuserSender', false, false, false));
			$objectTable->addIntroButton('addtribeuser', 'tribeuser.php?op=mod', _AM_PROFILE_TRIBEUSER_CREATE);

			$icmsAdminTpl->assign('profile_tribeuser_table', $objectTable->fetch());
			$icmsAdminTpl->display('db:profile_admin_tribeuser.html');
			break;
	}
	icms_cp_footer();
}
?>