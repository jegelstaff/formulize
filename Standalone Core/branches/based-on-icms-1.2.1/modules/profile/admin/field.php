<?php
/**
 * Admin page to manage fields
 *
 * List, add, edit and delete field objects
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 * @package		profile
 * @version		$Id$
 */

/**
 * Edit a Field
 *
 * @param int $field_id Fieldid to be edited
*/
function editfield($field_id = 0) {
	global $profile_field_handler, $icmsModule, $icmsAdminTpl;

	$fieldObj = $profile_field_handler->get($field_id);

	if (!$fieldObj->isNew()){
		$icmsModule->displayAdminMenu(3, _AM_PROFILE_FIELDS . " > " . _CO_ICMS_EDITING);
		$sform = $fieldObj->getForm(_AM_PROFILE_FIELD_EDIT, 'addfield');
		$sform->assign($icmsAdminTpl);

	} else {
		$icmsModule->displayAdminMenu(3, _AM_PROFILE_FIELDS . " > " . _CO_ICMS_CREATINGNEW);
		$sform = $fieldObj->getForm(_AM_PROFILE_FIELD_CREATE, 'addfield');
		$sform->assign($icmsAdminTpl);

	}
	$icmsAdminTpl->display('db:profile_admin_field.html');
}

include_once("admin_header.php");

$profile_field_handler = xoops_getModuleHandler('field');
/* Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
/** 
 * Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod','changedField','addfield','del','view','');

if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);

/** Again, use a naming convention that indicates the source of the content of the variable */
$clean_field_id = isset($_GET['fieldid']) ? (int) $_GET['fieldid'] : 0 ;

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

  		editfield($clean_field_id);
  		break;
  	case "addfield":
       	include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
        $controller = new IcmsPersistableController($profile_field_handler);
  		$controller->storeFromDefaultForm(_AM_PROFILE_FIELD_CREATED, _AM_PROFILE_FIELD_MODIFIED);

  		break;

  	case "del":
  	    include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
        $controller = new IcmsPersistableController($profile_field_handler);
  		$controller->handleObjectDeletion();

  		break;

  	case "view" :
  		$fieldObj = $profile_field_handler->get($clean_field_id);

  		icms_cp_header();
  		$icmsModule->displayAdminMenu(3, _AM_PROFILE_FIELD_VIEW . ' > ' . $fieldObj->getVar('field_name'));

//  		icms_collapsableBar('fieldview', $fieldObj->getVar('field_name') . $fieldObj->getEditFieldLink(), _AM_IMPROFILE_FIELD_VIEW_DSC);

  		$fieldObj->displaySingleObject();

//  		icms_close_collapsable('fieldview');

  		break;

  	default:

  		icms_cp_header();

  		$icmsModule->displayAdminMenu(3, _AM_PROFILE_FIELDS);

  		include_once ICMS_ROOT_PATH."/kernel/icmspersistabletable.php";
  		$objectTable = new IcmsPersistableTable($profile_field_handler);
  		$objectTable->addColumn(new IcmsPersistableColumn('field_name', _GLOBAL_LEFT, false, 'getFieldName'));
  		$objectTable->addColumn(new IcmsPersistableColumn('field_title'));
  		$objectTable->addColumn(new IcmsPersistableColumn('field_description'));

  		$objectTable->addIntroButton('addfield', 'field.php?op=mod', _AM_PROFILE_FIELD_CREATE);
  		$icmsAdminTpl->assign('profile_field_table', $objectTable->fetch());
  		$icmsAdminTpl->display('db:profile_admin_field.html');
  		break;
  }
  icms_cp_footer();
}
/**
 * If you want to have a specific action taken because the user input was invalid,
 * place it at this point. Otherwise, a blank page will be displayed
 */
?>