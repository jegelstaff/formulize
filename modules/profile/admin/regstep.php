<?php
/**
 * Admin page to manage regsteps
 *
 * List, add, edit and delete regstep objects
 *
 * @copyright	The ImpressCMS Project <http://www.impressscms.org>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.3
 * @author      Jan Pedersen
 * @author      The SmartFactory <www.smartfactory.ca>
 * @author	   	Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 * @package		improfile
 * @version		$Id: regstep.php 20428 2010-11-21 12:38:18Z phoenyx $
 */

/**
 * Edit a Regstep
 *
 * @param int $regstep_id Regstepid to be edited
 * @global ProfileRegstepHandler $profile_regstep_handler regstep handler
 * @global object $icmsAdminTpl template object
 */
function editregstep($regstep_id = 0) { 
	global $profile_regstep_handler, $icmsAdminTpl;

	$regstepObj = $profile_regstep_handler->get($regstep_id);

	if (!$regstepObj->isNew()){
		icms::$module->displayAdminMenu(3, _AM_PROFILE_REGSTEPS." > "._CO_ICMS_EDITING);
		$sform = $regstepObj->getForm(_AM_PROFILE_REGSTEP_EDIT, 'addregstep');
		$sform->assign($icmsAdminTpl);
	} else {
		icms::$module->displayAdminMenu(3, _AM_PROFILE_REGSTEPS." > "._CO_ICMS_CREATINGNEW);
		$sform = $regstepObj->getForm(_AM_PROFILE_REGSTEP_CREATE, 'addregstep');
		$sform->assign($icmsAdminTpl);
	}
	$icmsAdminTpl->display('db:profile_admin_regstep.html');
}

include_once 'admin_header.php';

$profile_regstep_handler = icms_getModuleHandler('regstep', basename(dirname(dirname(__FILE__))), 'profile');
/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);
$clean_regstep_id = isset($_GET['step_id']) ? (int)$_GET['step_id'] : 0 ;

/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod', 'changedField', 'addregstep', 'del', '');

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
			editregstep($clean_regstep_id);
			break;
		case "addregstep":
			$controller = new icms_ipf_Controller($profile_regstep_handler);
			$controller->storeFromDefaultForm(_AM_PROFILE_REGSTEP_CREATED, _AM_PROFILE_REGSTEP_MODIFIED);
			break;
		case "del":
			$controller = new icms_ipf_Controller($profile_regstep_handler);
			$controller->handleObjectDeletion();
			break;
		default:
			icms_cp_header();
			icms::$module->displayAdminMenu(3, _AM_PROFILE_REGSTEPS);

			$objectTable = new icms_ipf_view_Table($profile_regstep_handler);
			$objectTable->addColumn(new icms_ipf_view_Column('step_order'));
			$objectTable->addColumn(new icms_ipf_view_Column('step_name', false, false, 'getCustomStepName'));
			$objectTable->addColumn(new icms_ipf_view_Column('step_save', 'center', false, 'getCustomStepSave'));
			$objectTable->addColumn(new icms_ipf_view_Column('step_intro'));
			$objectTable->addIntroButton('addregstep', 'regstep.php?op=mod', _AM_PROFILE_REGSTEP_CREATE);

			$icmsAdminTpl->assign('profile_regstep_table', $objectTable->fetch());
			$icmsAdminTpl->display('db:profile_admin_regstep.html');
			break;
	}
	icms_cp_footer();
}
?>