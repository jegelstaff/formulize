<?php
/**
* Admin page to manage picturess
*
* List, add, edit and delete pictures objects
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.3
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @package		profile
* @version		$Id$
*/

/**
 * Edit a Pictures
 *
 * @param int $pictures_id Picturesid to be edited
*/
function editpictures($pictures_id = 0)
{
	global $profile_pictures_handler, $icmsModule, $icmsAdminTpl;

	$picturesObj = $profile_pictures_handler->get($pictures_id);

	if (!$picturesObj->isNew()){
		$icmsModule->displayAdminMenu(7, _AM_PROFILE_PICTURES . " > " . _CO_ICMS_EDITING);
		$sform = $picturesObj->getForm(_AM_PROFILE_PICTURES_EDIT, 'addpictures');
		$sform->assign($icmsAdminTpl);

	} else {
		redirect_header(PROFILE_ADMIN_URL.'pictures.php');
	}
	$icmsAdminTpl->display('db:profile_admin_pictures.html');
}

include_once("admin_header.php");

$profile_pictures_handler = icms_getModuleHandler('pictures');
/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod','changedField','addpictures','del','view','');

if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);

/** Again, use a naming convention that indicates the source of the content of the variable */
$clean_pictures_id = isset($_GET['pictures_id']) ? (int) $_GET['pictures_id'] : 0 ;

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

  		editpictures($clean_pictures_id);
  		break;

  	case "addpictures":
          include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
          $controller = new IcmsPersistableController($profile_pictures_handler);
  		$controller->storeFromDefaultForm(_AM_PROFILE_PICTURES_CREATED, _AM_PROFILE_PICTURES_MODIFIED);

  		break;

  	case "del":
  	    include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
          $controller = new IcmsPersistableController($profile_pictures_handler);
  		$controller->handleObjectDeletion();

  		break;

  	default:

  		icms_cp_header();

  		$icmsModule->displayAdminMenu(7, _AM_PROFILE_PICTURES);

  		include_once ICMS_ROOT_PATH."/kernel/icmspersistabletable.php";
  		$objectTable = new IcmsPersistableTable($profile_pictures_handler);
  		$objectTable->addColumn(new IcmsPersistableColumn('pictures_id'));
  		$objectTable->addColumn(new IcmsPersistableColumn('uid_owner', false, false, 'getPictureSender'));
  		$objectTable->addColumn(new IcmsPersistableColumn('title', _GLOBAL_LEFT, false, 'getPictureTitle'));
		$objectTable->addColumn(new IcmsPersistableColumn('url', 'center', 330, 'getProfilePicture', false, false, false));

		$objectTable->addQuickSearch(array('title'));
		$objectTable->setDefaultSort('pictures_id');
		$objectTable->setDefaultOrder('DESC');

  		$icmsAdminTpl->assign('profile_pictures_table', $objectTable->fetch());
  		$icmsAdminTpl->display('db:profile_admin_pictures.html');
  		break;
  }
  icms_cp_footer();
}
/**
 * If you want to have a specific action taken because the user input was invalid,
 * place it at this point. Otherwise, a blank page will be displayed
 */
?>