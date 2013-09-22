<?php
/**
* Admin page to manage positions
*
* List, add, edit and delete position objects
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: position.php 20431 2010-11-21 12:40:45Z phoenyx $
*/

/**
 * Edit a Position
 *
 * @param int $position_id Positionid to be edited
 * @global object $banners_banner_handler banner handler
 * @global object $icmsAdminTpl administration template ojbect
*/
function editposition($position_id = 0) {
	global $banners_position_handler, $icmsAdminTpl;
	$positionObj = $banners_position_handler->get($position_id);

	if (!$positionObj->isNew()) {
		$positionObj->makeFieldReadOnly('name');
		icms::$module->displayAdminMenu(2, _AM_BANNERS_POSITIONS." > "._CO_ICMS_EDITING);
		$sform = $positionObj->getForm(_AM_BANNERS_POSITION_EDIT, 'addposition');
		$sform->assign($icmsAdminTpl);
	} else {
		icms::$module->displayAdminMenu(2, _AM_BANNERS_POSITIONS." > "._CO_ICMS_CREATINGNEW);
		$sform = $positionObj->getForm(_AM_BANNERS_POSITION_CREATE, 'addposition');
		$sform->assign($icmsAdminTpl);
	}
	$icmsAdminTpl->display('db:banners_admin_position.html');
}

include_once "admin_header.php";

$banners_position_handler = icms_getModuleHandler('position', basename(dirname(dirname(__FILE__))), 'banners');
/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);

$clean_position_id = isset($_GET['position_id']) ? (int)$_GET['position_id'] : 0 ;

/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod', 'changedField', 'addposition', 'del', '');

/**
 * in_array() is a native PHP function that will determine if the value of the
 * first argument is found in the array listed in the second argument. Strings
 * are case sensitive and the 3rd argument determines whether type matching is
 * required
*/
if (in_array($clean_op, $valid_op, TRUE)){
	switch ($clean_op) {
		case "mod":
		case "changedField":
			icms_cp_header();
			editposition($clean_position_id);
			break;
		case "addposition":
			$controller = new icms_ipf_Controller($banners_position_handler);
			$controller->storeFromDefaultForm(_AM_BANNERS_POSITION_CREATED, _AM_BANNERS_POSITION_MODIFIED);
			break;
		case "del":
			// only allow to delete the position if no banner is assigned to this position
			$banners_positionlink_handler = icms_getModuleHandler('positionlink', basename(dirname(dirname(__FILE__))), 'banners');
			$count = $banners_positionlink_handler->getCount(new icms_db_criteria_Compo(new icms_db_criteria_Item('position_id', $clean_position_id)));
			if ($count > 0) {
				redirect_header(BANNERS_ADMIN_URL . $banners_position_handler->_page, 3, _AM_BANNERS_POSITION_NODELETE_BANNER);
			}

			$controller = new icms_ipf_Controller($banners_position_handler);
			$controller->handleObjectDeletion();
			break;
		default:
			icms_cp_header();
			icms::$module->displayAdminMenu(2, _AM_BANNERS_POSITIONS);
			$tableObj = new icms_ipf_view_Table($banners_position_handler);
			$tableObj->addColumn(new icms_ipf_view_Column('name'));
			$tableObj->addColumn(new icms_ipf_view_Column('title', _GLOBAL_LEFT, FALSE, 'getTitleForTableDisplay'));
			$tableObj->addColumn(new icms_ipf_view_Column('dimension'));
			$tableObj->addIntroButton('addposition', basename(__FILE__) . '?op=mod', _AM_BANNERS_POSITION_CREATE);
			$icmsAdminTpl->assign('banners_position_table', $tableObj->fetch());
			$icmsAdminTpl->assign('banners_position_info', _AM_BANNERS_POSITION_INFO);
			$icmsAdminTpl->display('db:banners_admin_position.html');
			break;
	}
	icms_cp_footer();
}