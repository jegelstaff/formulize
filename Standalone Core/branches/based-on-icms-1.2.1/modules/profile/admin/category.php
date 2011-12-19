<?php
/**
* Admin page to manage categorys
*
* List, add, edit and delete category objects
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.3
* @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
* @package		improfile
* @version		$Id$
*/

/**
 * Edit a Category
 *
 * @param int $category_id Categoryid to be edited
*/
function editcategory($category_id = 0)
{
	global $profile_category_handler, $icmsModule, $icmsAdminTpl;

	$categoryObj = $profile_category_handler->get($category_id);

	if (!$categoryObj->isNew()){
		$icmsModule->displayAdminMenu(2, _AM_PROFILE_CATEGORYS . " > " . _CO_ICMS_EDITING);
		$sform = $categoryObj->getForm(_AM_PROFILE_CATEGORY_EDIT, 'addcategory');
		$sform->assign($icmsAdminTpl);

	} else {
		$icmsModule->displayAdminMenu(2, _AM_PROFILE_CATEGORYS . " > " . _CO_ICMS_CREATINGNEW);
		$sform = $categoryObj->getForm(_AM_PROFILE_CATEGORY_CREATE, 'addcategory');
		$sform->assign($icmsAdminTpl);

	}
	$icmsAdminTpl->display('db:profile_admin_category.html');
}

include_once("admin_header.php");

$profile_category_handler = xoops_getModuleHandler('category');
/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod','changedField','addcategory','del','view','');

if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);

/** Again, use a naming convention that indicates the source of the content of the variable */
$clean_category_id = isset($_GET['catid']) ? (int) $_GET['catid'] : 0 ;

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

  		xoops_cp_header();

  		editcategory($clean_category_id);
  		break;
  	case "addcategory":
          include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
          $controller = new IcmsPersistableController($profile_category_handler);
  		  $controller->storeFromDefaultForm(_AM_PROFILE_CATEGORY_CREATED, _AM_PROFILE_CATEGORY_MODIFIED);

  		break;

  	case "del":
  	    include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
          $controller = new IcmsPersistableController($profile_category_handler);
  		$controller->handleObjectDeletion();

  		break;

  	case "view" :
  		$categoryObj = $profile_category_handler->get($clean_category_id);

  		icms_cp_header();
  		$icmsModule->displayAdminMenu(2, _AM_PROFILE_CATEGORY_VIEW . ' > ' . $categoryObj->getVar('category_name'));

//  		smart_collapsableBar('categoryview', $categoryObj->getVar('category_name') . $categoryObj->getEditCategoryLink(), _AM_IMPROFILE_CATEGORY_VIEW_DSC);

  		$categoryObj->displaySingleObject();

//  		smart_close_collapsable('categoryview');

  		break;

  	default:

  		icms_cp_header();

  		$icmsModule->displayAdminMenu(2, _AM_PROFILE_CATEGORYS);

  		include_once ICMS_ROOT_PATH."/kernel/icmspersistabletable.php";
  		$objectTable = new IcmsPersistableTable($profile_category_handler);
		$objectTable->addColumn(new IcmsPersistableColumn('cat_title', _GLOBAL_LEFT, false, 'getCatTitle'));
  		$objectTable->addColumn(new IcmsPersistableColumn('cat_description'));

  		$objectTable->addIntroButton('addcategory', 'category.php?op=mod', _AM_PROFILE_CATEGORY_CREATE);
  		$icmsAdminTpl->assign('profile_category_table', $objectTable->fetch());
  		$icmsAdminTpl->display('db:profile_admin_category.html');
  		break;
  }
  icms_cp_footer();
}
/**
 * If you want to have a specific action taken because the user input was invalid,
 * place it at this point. Otherwise, a blank page will be displayed
 */
?>