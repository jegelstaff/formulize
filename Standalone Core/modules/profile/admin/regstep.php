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
 * @version		$Id$
 */

/**
 * Edit a Regstep
 *
 * @param int $regstep_id Regstepid to be edited
*/
function editregstep($regstep_id = 0) { 
	global $profile_regstep_handler, $icmsModule, $icmsAdminTpl;

	$regstepObj = $profile_regstep_handler->get($regstep_id);

	if (!$regstepObj->isNew()){
		$icmsModule->displayAdminMenu(4, _AM_PROFILE_REGSTEPS . " > " . _CO_ICMS_EDITING);
		$sform = $regstepObj->getForm(_AM_PROFILE_REGSTEP_EDIT, 'addregstep');
		$sform->assign($icmsAdminTpl);

	} else {
		$icmsModule->displayAdminMenu(4, _AM_PROFILE_REGSTEPS . " > " . _CO_ICMS_CREATINGNEW);
		$sform = $regstepObj->getForm(_AM_PROFILE_REGSTEP_CREATE, 'addregstep');
		$sform->assign($icmsAdminTpl);

	}
	$icmsAdminTpl->display('db:profile_admin_regstep.html');
}

include_once("admin_header.php");

$profile_regstep_handler = xoops_getModuleHandler('regstep');
/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod','changedField','addregstep','del','view','');

if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);

/** Again, use a naming convention that indicates the source of the content of the variable */
$clean_regstep_id = isset($_GET['step_id']) ? (int) $_GET['step_id'] : 0 ;

/**
 * in_array() is a native PHP function that will determine if the value of the
 * first argument is found in the array listed in the second argument. Strings
 * are case sensitive and the 3rd argument determines whether type matching is
 * required
*/
if (in_array($clean_op,$valid_op,true)){
  switch ($clean_op) {
  	case "mod":
  	case "changedField":

  		icms_cp_header();

  		editregstep($clean_regstep_id);
  		break;
  	case "addregstep":
          include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
          $controller = new IcmsPersistableController($profile_regstep_handler);
  		$controller->storeFromDefaultForm(_AM_PROFILE_REGSTEP_CREATED, _AM_PROFILE_REGSTEP_MODIFIED);

  		break;

  	case "del":
  	    include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
        $controller = new IcmsPersistableController($profile_regstep_handler);
  		$controller->handleObjectDeletion();

  		break;

  	case "view" :
  		$regstepObj = $profile_regstep_handler->get($clean_regstep_id);

  		icms_cp_header();
  		$icmsModule->displayAdminMenu(4, _AM_IMPROFILE_REGSTEP_VIEW . ' > ' . $regstepObj->getVar('regstep_name'));

//  		icms_collapsableBar('regstepview', $regstepObj->getVar('regstep_name') . $regstepObj->getEditRegstepLink(), _AM_IMPROFILE_REGSTEP_VIEW_DSC);

  		$regstepObj->displaySingleObject();

//  		icms_close_collapsable('regstepview');

  		break;

  	default:

  		icms_cp_header();

  		$icmsModule->displayAdminMenu(4, _AM_PROFILE_REGSTEPS);

  		include_once ICMS_ROOT_PATH."/kernel/icmspersistabletable.php";
  		$objectTable = new IcmsPersistableTable($profile_regstep_handler);
  		$objectTable->addColumn(new IcmsPersistableColumn('step_order'));
  		$objectTable->addColumn(new IcmsPersistableColumn('step_name', false, false, 'getCustomStepName'));
  		$objectTable->addColumn(new IcmsPersistableColumn('step_save', 'center', false, 'getCustomStepSave'));
  		$objectTable->addColumn(new IcmsPersistableColumn('step_intro'));

  		$objectTable->addIntroButton('addregstep', 'regstep.php?op=mod', _AM_PROFILE_REGSTEP_CREATE);
  		$icmsAdminTpl->assign('profile_regstep_table', $objectTable->fetch());
  		$icmsAdminTpl->display('db:profile_admin_regstep.html');
  		break;
  }
  icms_cp_footer();
}
/**
 * If you want to have a specific action taken because the user input was invalid,
 * place it at this point. Otherwise, a blank page will be displayed
 */
?>