<?php
/**
* Admin page to manage clients
*
* List, add, edit and delete client objects
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: client.php 20431 2010-11-21 12:40:45Z phoenyx $
*/

/**
 * Edit a Client
 *
 * @param int $client_id Clientid to be edited
 * @param bool $restore_data TRUE if data should be restored
 * @global object $banners_banner_handler banner handler
 * @global object $icmsAdminTpl administration template ojbect
 * @global object $controller icms_ipf_Controller
*/
function editclient($client_id = 0, $restore_data = FALSE) {
	global $banners_client_handler, $icmsAdminTpl, $controller;

	$clientObj = $banners_client_handler->get($client_id);
	if ($restore_data) $controller->postDataToObject($clientObj);

	if (!$clientObj->isNew()) {
		icms::$module->displayAdminMenu(1, _AM_BANNERS_CLIENTS." > "._CO_ICMS_EDITING);
		$sform = $clientObj->getForm(_AM_BANNERS_CLIENT_EDIT, 'addclient');
		$sform->assign($icmsAdminTpl);
	} else {
		icms::$module->displayAdminMenu(1, _AM_BANNERS_CLIENTS." > "._CO_ICMS_CREATINGNEW);
		$sform = $clientObj->getForm(_AM_BANNERS_CLIENT_CREATE, 'addclient');
		$sform->assign($icmsAdminTpl);
	}
	$icmsAdminTpl->display('db:banners_admin_client.html');
}

include_once "admin_header.php";

$banners_client_handler = icms_getModuleHandler('client', basename(dirname(dirname(__FILE__))), 'banners');
/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);

$clean_client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0 ;

/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod', 'changedField', 'addclient', 'del', '');

/**
 * in_array() is a native PHP function that will determine if the value of the
 * first argument is found in the array listed in the second argument. Strings
 * are case sensitive and the 3rd argument determines whether type matching is
 * required
*/
if (in_array($clean_op, $valid_op, TRUE)) {
	switch ($clean_op) {
		case "mod":
		case "changedField":
			icms_cp_header();
			editclient($clean_client_id);
			break;
		case "addclient":
			$controller = new icms_ipf_Controller($banners_client_handler);
			$clean_client_id = (int)$_POST['client_id'];

			// make sure the specified userid isn't assigned to another client
			$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('uid', (int)$_POST['uid']));
			if ($clean_client_id != 0) $criteria->add(new icms_db_criteria_Item('client_id', $clean_client_id, '!='));
			$count = $banners_client_handler->getCount($criteria);
			if ($count > 0) {
				icms_cp_header();
				$icmsAdminTpl->assign('error', _AM_BANNERS_CLIENT_USERNOTUNIQUE);
				editclient($clean_client_id, TRUE);
			} else {
				$controller->storeFromDefaultForm(_AM_BANNERS_CLIENT_CREATED, _AM_BANNERS_CLIENT_MODIFIED);
			}
			break;
		case "del":
			// only allow to delete the client if no banner is assigned to this client
			$banners_banner_handler = icms_getModuleHandler('banner', basename(dirname(dirname(__FILE__))), 'banners');
			$count = $banners_banner_handler->getCount(icms_buildCriteria(array('client_id' => $clean_client_id)));
			if ($count > 0) {
				redirect_header(BANNERS_ADMIN_URL . $banners_client_handler->_page, 3, _AM_BANNERS_CLIENT_NODELETE_BANNER);
			}

			$controller = new icms_ipf_Controller($banners_client_handler);
			$controller->handleObjectDeletion();
			break;
		default:
			icms_cp_header();
			icms::$module->displayAdminMenu(1, _AM_BANNERS_CLIENTS);
			$tableObj = new icms_ipf_view_Table($banners_client_handler);
			$tableObj->addColumn(new icms_ipf_view_Column('active', 'center', FALSE, 'getActiveForTableDisplay'));
			$tableObj->addColumn(new icms_ipf_view_Column('uid', _GLOBAL_LEFT, FALSE, 'getUsernameForTableDisplay'));
			$tableObj->addColumn(new icms_ipf_view_Column('first_name'));
			$tableObj->addColumn(new icms_ipf_view_Column('last_name', _GLOBAL_LEFT, FALSE, 'getLastNameForTableDisplay'));
			$tableObj->addColumn(new icms_ipf_view_Column('company'));
			$tableObj->addColumn(new icms_ipf_view_Column('since'));
			$tableObj->addColumn(new icms_ipf_view_Column('banner_count'));
			$tableObj->addFilter('active', 'getActiveArray');
			$tableObj->addIntroButton('addclient', basename(__FILE__) . '?op=mod', _AM_BANNERS_CLIENT_CREATE);
			$tableObj->addQuickSearch(array('first_name', 'last_name', 'company'));
			$icmsAdminTpl->assign('banners_client_table', $tableObj->fetch());
			$icmsAdminTpl->display('db:banners_admin_client.html');
			break;
	}
	icms_cp_footer();
}