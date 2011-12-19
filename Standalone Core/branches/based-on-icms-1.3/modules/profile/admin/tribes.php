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
 * @version		$Id: tribes.php 22417 2011-08-27 12:56:36Z phoenyx $
 */

/**
 * Edit a tribe
 *
 * @param int $tribes_id id of tribe to be edited
 * @global mod_profile_TribesHandler $profile_tribes_handler
 * @global object $icmsAdminTpl template object
 */
function edittribes($tribes_id = 0) {
	global $profile_tribes_handler, $icmsAdminTpl;

	$tribesObj = $profile_tribes_handler->get($tribes_id);
	if ($tribesObj->isNew()) redirect_header(PROFILE_ADMIN_URL.'tribes.php');
	icms::$module->displayAdminMenu(7, _AM_PROFILE_TRIBES . " > " . _CO_ICMS_EDITING);
	$sform = $tribesObj->getForm(_AM_PROFILE_TRIBES_EDIT, 'addtribes');
	$sform->assign($icmsAdminTpl);
	$icmsAdminTpl->display('db:profile_admin_tribes.html');
}

/**
 * merge a tribe
 *
 * @param int $tribes_id id of tribe to be merged
 * @global mod_profile_TribesHandler $profile_tribes_handler
 * @global object $icmsAdminTpl template object
 */
function mergetribes($tribes_id) {
	global $profile_tribes_handler, $icmsAdminTpl;
	
	$tribesObj = $profile_tribes_handler->get($tribes_id);
	if ($tribesObj->isNew()) redirect_header(PROFILE_ADMIN_URL.'tribes.php');
	icms::$module->displayAdminMenu(7, _AM_PROFILE_TRIBES . " > " . _AM_PROFILE_TRIBES_MERGING);
	$sform = $tribesObj->getMergeForm();
	$sform->assign($icmsAdminTpl);
	$icmsAdminTpl->display('db:profile_admin_tribes.html');
}

include_once 'admin_header.php';

$profile_tribes_handler = icms_getModuleHandler('tribes', basename(dirname(dirname(__FILE__))), 'profile');
/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);
$clean_tribes_id = isset($_GET['tribes_id']) ? (int)$_GET['tribes_id'] : 0;

/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod', 'changedField', 'addtribes', 'del', 'merge', 'mergefinal', '');

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
			edittribes($clean_tribes_id);
			break;
		case "addtribes":
			$controller = new icms_ipf_Controller($profile_tribes_handler);
			$controller->storeFromDefaultForm(_AM_PROFILE_TRIBES_CREATED, _AM_PROFILE_TRIBES_MODIFIED);
			break;
		case "del":
			$controller = new icms_ipf_Controller($profile_tribes_handler);
			$controller->handleObjectDeletion();
			break;
		case "merge":
			icms_cp_header();
			mergetribes($clean_tribes_id);
			break;
		case "mergefinal":
			$clean_tribes_id = isset($_POST['tribes_id']) ? (int)$_POST['tribes_id'] : 0;
			$clean_merge_tribes_id = isset($_POST['merge_tribes_id']) ? (int)$_POST['merge_tribes_id'] : 0;
			$profile_tribes_handler->mergeTribes($clean_tribes_id, $clean_merge_tribes_id);
			redirect_header(PROFILE_ADMIN_URL.'tribes.php', 3, _AM_PROFILE_TRIBES_MERGE_SUCCESS);
			break;
		default:
			icms_cp_header();
			icms::$module->displayAdminMenu(7, _AM_PROFILE_TRIBES);

			$objectTable = new icms_ipf_view_Table($profile_tribes_handler);
			$objectTable->addColumn(new icms_ipf_view_Column('tribes_id'));
			$objectTable->addColumn(new icms_ipf_view_Column('uid_owner', false, false, 'getTribeSender'));
			$objectTable->addColumn(new icms_ipf_view_Column('title', _GLOBAL_LEFT, false, 'getLinkedTribeTitle'));
			$objectTable->addColumn(new icms_ipf_view_Column('tribe_img', 'center', 330, 'getTribePicture', false, false, false));
			$objectTable->addQuickSearch(array('title'));
			$objectTable->addFilter('uid_owner', 'getTribeOwnerArray');
			$objectTable->addCustomAction('getMergeItemLink');
			$objectTable->setDefaultSort('tribes_id');
			$objectTable->setDefaultOrder('DESC');

			$icmsAdminTpl->assign('profile_tribes_table', $objectTable->fetch());
			$icmsAdminTpl->display('db:profile_admin_tribes.html');
			break;
	}
	icms_cp_footer();
}
?>