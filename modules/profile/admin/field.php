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
 * @version		$Id: field.php 22253 2011-08-18 13:32:42Z phoenyx $
 */

/**
 * Edit a Field
 *
 * @param int $field_id Fieldid to be edited
 */
function editfield($field_id = 0) {
	global $profile_field_handler, $icmsAdminTpl;

	$fieldObj = $profile_field_handler->get($field_id);

	if (!$fieldObj->isNew()){
		icms::$module->displayAdminMenu(2, _AM_PROFILE_FIELDS." > "._CO_ICMS_EDITING);
		if ($fieldObj->getVar('system') == 1) $fieldObj->hideFieldFromForm(array('field_type', 'field_name', 'field_required', 'field_maxlength', 'field_notnull', 'field_edit', 'field_options'));
		$sform = $fieldObj->getForm(_AM_PROFILE_FIELD_EDIT, 'addfield');
		$sform->assign($icmsAdminTpl);
	} else {
		icms::$module->displayAdminMenu(2, _AM_PROFILE_FIELDS." > "._CO_ICMS_CREATINGNEW);
		$sform = $fieldObj->getForm(_AM_PROFILE_FIELD_CREATE, 'addfield');
		$sform->assign($icmsAdminTpl);
	}
	$icmsAdminTpl->display('db:profile_admin_field.html');
}

include_once 'admin_header.php';

$profile_field_handler = icms_getModuleHandler('field', basename(dirname(dirname(__FILE__))), 'profile');
/* Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);
$clean_field_id = isset($_GET['fieldid']) ? (int)$_GET['fieldid'] : 0 ;

/** 
 * Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array('mod', 'changedField', 'addfield', 'del', '');

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
			editfield($clean_field_id);
			break;
		case "changedField":
			foreach ($_POST['mod_profile_Field_objects'] as $k => $v){
				$fieldObj = $profile_field_handler->get($v);
				if ($fieldObj->getVar('field_weight','e') != $_POST['field_weight'][$k]){
					$fieldObj->setVar('field_weight', (int)$_POST['field_weight'][$k]);
					$profile_field_handler->insert($fieldObj);
				}
			}
			redirect_header('field.php', 3, _AM_PROFILE_FIELD_MODIFIED);
			break;
		case "addfield":
			$controller = new icms_ipf_Controller($profile_field_handler);
			$controller->storeFromDefaultForm(_AM_PROFILE_FIELD_CREATED, _AM_PROFILE_FIELD_MODIFIED);
			break;
		case "del":
			$controller = new icms_ipf_Controller($profile_field_handler);
			$controller->handleObjectDeletion();
			break;
		default:
			icms_cp_header();
			icms::$module->displayAdminMenu(2, _AM_PROFILE_FIELDS);

			$objectTable = new icms_ipf_view_Table($profile_field_handler, false, array('edit'));
			$objectTable->addColumn(new icms_ipf_view_Column('field_show', _CENTER, FALSE, 'getShow'));
			$objectTable->addColumn(new icms_ipf_view_Column('catid', _GLOBAL_LEFT, false, 'getCatid', false, false, false));
			$objectTable->addColumn(new icms_ipf_view_Column('field_name', _GLOBAL_LEFT, false, 'getFieldName'));
			$objectTable->addColumn(new icms_ipf_view_Column('field_title'));
			$objectTable->addColumn(new icms_ipf_view_Column('field_description'));
			$objectTable->addColumn(new icms_ipf_view_Column('field_weight', _CENTER, false, 'getField_weightControl'));
			$objectTable->addFilter('catid', 'getCategoriesArray');
			$objectTable->addIntroButton('addfield', 'field.php?op=mod', _AM_PROFILE_FIELD_CREATE);
			$objectTable->addQuickSearch(array('field_name', 'field_title', 'field_description'));
			$objectTable->addCustomAction('getDeleteButtonForDisplay');
			$objectTable->addActionButton('changedField', false, _SUBMIT);

			$icmsAdminTpl->assign('profile_field_table', $objectTable->fetch());
			$icmsAdminTpl->display('db:profile_admin_field.html');
			break;
	}
	icms_cp_footer();
}
?>