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
 * @package		profile
 * @version		$Id: category.php 20428 2010-11-21 12:38:18Z phoenyx $
 */

/**
 * Edit a Category
 *
 * @param int $category_id Categoryid to be edited
 * @global mod_profile_CategoryHandler $profile_category_handler handler object
 * @global object $icmsAdminTpl template object
 */
function editcategory($category_id = 0) {
	global $profile_category_handler, $icmsAdminTpl;

	$categoryObj = $profile_category_handler->get($category_id);

	if (!$categoryObj->isNew()){
		icms::$module->displayAdminMenu(1, _AM_PROFILE_CATEGORYS." > "._CO_ICMS_EDITING);
		$sform = $categoryObj->getForm(_AM_PROFILE_CATEGORY_EDIT, 'addcategory');
		$sform->assign($icmsAdminTpl);
	} else {
		icms::$module->displayAdminMenu(1, _AM_PROFILE_CATEGORYS." > "._CO_ICMS_CREATINGNEW);
		$sform = $categoryObj->getForm(_AM_PROFILE_CATEGORY_CREATE, 'addcategory');
		$sform->assign($icmsAdminTpl);
	}
	$icmsAdminTpl->display('db:profile_admin_category.html');
}

include_once 'admin_header.php';

$profile_category_handler = icms_getModuleHandler('category', basename(dirname(dirname(__FILE__))), 'profile');
/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);
$clean_category_id = isset($_GET['catid']) ? (int)$_GET['catid'] : 0 ;

/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array('mod', 'changedField', 'addcategory', 'del', '');

/**
 * in_array() is a native PHP function that will determine if the value of the
 * first argument is found in the array listed in the second argument. Strings
 * are case sensitive and the 3rd argument determines whether type matching is
 * required
*/
if (in_array($clean_op, $valid_op, true)){
	switch ($clean_op) {
		case "mod":
			icms_cp_header();
			editcategory($clean_category_id);
			break;
		case "changedField":
			foreach ($_POST['mod_profile_Category_objects'] as $k => $v){
				$categoryObj = $profile_category_handler->get($v);
				if ($categoryObj->getVar('cat_weight','e') != $_POST['cat_weight'][$k]){
					$categoryObj->setVar('cat_weight', (int)$_POST['cat_weight'][$k]);
					$profile_category_handler->insert($categoryObj);
				}
			}
			redirect_header('category.php', 3, _AM_PROFILE_CATEGORY_MODIFIED);
			break;
		case "addcategory":
			$controller = new icms_ipf_Controller($profile_category_handler);
			$controller->storeFromDefaultForm(_AM_PROFILE_CATEGORY_CREATED, _AM_PROFILE_CATEGORY_MODIFIED);
			break;
		case "del":
			$controller = new icms_ipf_Controller($profile_category_handler);
			$controller->handleObjectDeletion();
			break;
		default:
			icms_cp_header();
			icms::$module->displayAdminMenu(1, _AM_PROFILE_CATEGORYS);

			$objectTable = new icms_ipf_view_Table($profile_category_handler);
			$objectTable->addColumn(new icms_ipf_view_Column('cat_title', _GLOBAL_LEFT, false, 'getCatTitle'));
			$objectTable->addColumn(new icms_ipf_view_Column('cat_description'));
			$objectTable->addColumn(new icms_ipf_view_Column('cat_weight', _CENTER, false, 'getCat_weightControl'));
			$objectTable->setDefaultSort('cat_weight');
			$objectTable->addIntroButton('addcategory', 'category.php?op=mod', _AM_PROFILE_CATEGORY_CREATE);
			$objectTable->addActionButton('changedField', false, _SUBMIT);

			$icmsAdminTpl->assign('profile_category_table', $objectTable->fetch());
			$icmsAdminTpl->display('db:profile_admin_category.html');
			break;
	}
	icms_cp_footer();
}
?>