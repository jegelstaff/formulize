<?php
/**
* Admin page to manage tribess
*
* List, add, edit and delete tribes objects
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.3
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @package		profile
* @version		$Id$
*/

/**
 * Edit a Tribes
 *
 * @param int $tribes_id Tribesid to be edited
*/
function edittribes($tribes_id = 0)
{
	global $profile_tribes_handler, $icmsModule, $icmsAdminTpl;

	$tribesObj = $profile_tribes_handler->get($tribes_id);

	if (!$tribesObj->isNew()){
		$icmsModule->displayAdminMenu(8, _AM_PROFILE_TRIBES . " > " . _CO_ICMS_EDITING);
		$sform = $tribesObj->getForm(_AM_PROFILE_TRIBES_EDIT, 'addtribes');
		$sform->assign($icmsAdminTpl);

	} else {
		redirect_header(PROFILE_ADMIN_URL.'tribes.php');
	}
	$icmsAdminTpl->display('db:profile_admin_tribes.html');
}

include_once("admin_header.php");

$profile_tribes_handler = icms_getModuleHandler('tribes');
/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod','changedField','addtribes','del','view','');

if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);

/** Again, use a naming convention that indicates the source of the content of the variable */
$clean_tribes_id = isset($_GET['tribes_id']) ? (int) $_GET['tribes_id'] : 0 ;

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

  		edittribes($clean_tribes_id);
  		break;

  	case "addtribes":
          include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
          $controller = new IcmsPersistableController($profile_tribes_handler);
  		$controller->storeFromDefaultForm(_AM_PROFILE_TRIBES_CREATED, _AM_PROFILE_TRIBES_MODIFIED);

  		break;

  	case "del":
  	    include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
          $controller = new IcmsPersistableController($profile_tribes_handler);
  		$controller->handleObjectDeletion();

  		break;

  	default:

  		icms_cp_header();

  		$icmsModule->displayAdminMenu(8, _AM_PROFILE_TRIBES);

  		include_once ICMS_ROOT_PATH."/kernel/icmspersistabletable.php";
  		$objectTable = new IcmsPersistableTable($profile_tribes_handler);
  		$objectTable->addColumn(new IcmsPersistableColumn('tribes_id'));
  		$objectTable->addColumn(new IcmsPersistableColumn('uid_owner', false, false, 'getTribeSender'));
  		$objectTable->addColumn(new IcmsPersistableColumn('title', _GLOBAL_LEFT, false, 'getLinkedTribeTitle'));
		$objectTable->addColumn(new IcmsPersistableColumn('tribe_img', 'center', 330, 'getTribePicture', false, false, false));

		$objectTable->addQuickSearch(array('title'));
		$objectTable->setDefaultSort('tribes_id');
		$objectTable->setDefaultOrder('DESC');

  		$icmsAdminTpl->assign('profile_tribes_table', $objectTable->fetch());
  		$icmsAdminTpl->display('db:profile_admin_tribes.html');
  		break;
  }
  icms_cp_footer();
}
/**
 * If you want to have a specific action taken because the user input was invalid,
 * place it at this point. Otherwise, a blank page will be displayed
 */
?>