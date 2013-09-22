<?php
/**
* Admin page to manage banners
*
* List, add, edit and delete banner objects
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: banner.php 20431 2010-11-21 12:40:45Z phoenyx $
*/

/**
 * Edit a Banner
 *
 * @param int $banner_id Bannerid to be edited
 * @global object $banners_banner_handler banner handler
 * @global object $icmsAdminTpl administration template ojbect
*/
function editbanner($banner_id = 0) {
	global $banners_banner_handler, $icmsAdminTpl;

	$bannerObj = $banners_banner_handler->get($banner_id);

	if (isset($_POST['op']) && $_POST['op'] == 'changedField' && in_array($_POST['changedField'], array('type', 'contract'))) {
		$controller = new icms_ipf_Controller($banners_banner_handler);
		$controller->postDataToObject($bannerObj);
	}

	if ($bannerObj->getVar('type') == BANNERS_BANNER_TYPE_IMAGE) {
		$bannerObj->hideFieldFromForm('source');
		$bannerObj->setFieldAsRequired('filename');
	} elseif ($bannerObj->getVar('type') == BANNERS_BANNER_TYPE_HTML) {
		$bannerObj->hideFieldFromForm(array('filename', 'link', 'target'));
		$bannerObj->setFieldAsRequired('source');
	} elseif ($bannerObj->getVar("type") == BANNERS_BANNER_TYPE_FLASH) {
		$bannerObj->setControl('filename', 'file');
		$bannerObj->hideFieldFromForm('source');
		if ($bannerObj->isNew()) $bannerObj->setFieldAsRequired('filename');
	}
	if ($bannerObj->getVar('contract') == BANNERS_BANNER_CONTRACT_TIME) {
		$bannerObj->hideFieldFromForm('impressions_purchased');
	} elseif ($bannerObj->getVar('contract') == BANNERS_BANNER_CONTRACT_IMPRESSIONS) {
		$bannerObj->hideFieldFromForm(array('begin', 'end'));
		$bannerObj->setFieldAsRequired('impressions_purchased');
	}

	if (!$bannerObj->isNew()) {
		$bannerObj->hideFieldFromForm('type');
		icms::$module->displayAdminMenu(0, _AM_BANNERS_BANNERS . " > " . _CO_ICMS_EDITING);
		$sform = $bannerObj->getForm(_AM_BANNERS_BANNER_EDIT, 'addbanner');
		$sform->assign($icmsAdminTpl);
	} else {
		$bannerObj->setVar('begin', time());
		$bannerObj->setVar('end', time()+604800);
		icms::$module->displayAdminMenu(0, _AM_BANNERS_BANNERS . " > " . _CO_ICMS_CREATINGNEW);
		$sform = $bannerObj->getForm(_AM_BANNERS_BANNER_CREATE, 'addbanner');
		$sform->assign($icmsAdminTpl);
	}
	$icmsAdminTpl->display('db:banners_admin_banner.html');
}

include_once "admin_header.php";

$banners_banner_handler = icms_getModuleHandler('banner', basename(dirname(dirname(__FILE__))), 'banners');
/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);

$clean_banner_id = isset($_GET['banner_id']) ? (int)$_GET['banner_id'] : 0 ;

/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod', 'changedField', 'addbanner', 'del', '');

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
			// check if at least one position is available
			$banners_position_handler = icms_getModuleHandler('position', basename(dirname(dirname(__FILE__))), 'banners');
			$positions = $banners_position_handler->getCount();
			if ($positions == 0) redirect_header(BANNERS_ADMIN_URL . $banners_position_handler->_page . "?op=mod", 3, _AM_BANNERS_BANNER_NOPOSITIONS);
			unset($positions, $banners_position_handler);

			// check if at least one client is available
			$banners_client_handler = icms_getModuleHandler('client', basename(dirname(dirname(__FILE__))), 'banners');
			$clients = $banners_client_handler->getCount();
			if ($clients == 0) redirect_header(BANNERS_ADMIN_URL . $banners_client_handler->_page . "?op=mod", 3, _AM_BANNERS_BANNER_NOCLIENTS);
			unset($clients, $banners_client_handler);

			icms_cp_header();
			editbanner($clean_banner_id);
			break;
		case "addbanner":
			$bannerObj = $banners_banner_handler->get($clean_banner_id);

			// do not allow to change the type for existing banners
			if (!$bannerObj->isNew() && $bannerObj->getVar('type') != (int)$_POST['type']) {
				redirect_header(BANNERS_ADMIN_URL, 3, _NOPERM);
			}

			// do not allow to delete banners here
			unset($_POST['delete_filename']);

			$banners_banner_handler->setAllowedMimetypes((int)$_POST['type']);
			$controller = new icms_ipf_Controller($banners_banner_handler);
			$controller->storeFromDefaultForm(_AM_BANNERS_BANNER_CREATED, _AM_BANNERS_BANNER_MODIFIED);
			break;
		case "del":
			$controller = new icms_ipf_Controller($banners_banner_handler);
			$controller->handleObjectDeletion();
			break;
		default:
			icms_cp_header();
			icms::$module->displayAdminMenu(0, _AM_BANNERS_BANNERS);
			$tableObj = new icms_ipf_view_Table($banners_banner_handler);
			$tableObj->addColumn(new icms_ipf_view_Column('status', 'center', FALSE, 'getStatusForTableDisplay', FALSE, FALSE, FALSE));
			$tableObj->addColumn(new icms_ipf_view_Column('banner_id', 'center'));
			$tableObj->addColumn(new icms_ipf_view_Column('client_id', _GLOBAL_LEFT, FALSE, 'getClientForTableDisplay', FALSE, FALSE, FALSE));
			$tableObj->addColumn(new icms_ipf_view_Column('description', _GLOBAL_LEFT, FALSE, 'getDescriptionForTableDisplay'));
			$tableObj->addColumn(new icms_ipf_view_Column('begin', _GLOBAL_LEFT, FALSE, 'getBeginForTableDisplay'));
			$tableObj->addColumn(new icms_ipf_view_Column('end', _GLOBAL_LEFT, FALSE, 'getEndForTableDisplay'));
			$tableObj->addColumn(new icms_ipf_view_Column('impressions_purchased', _GLOBAL_LEFT, FALSE, 'getImpressionsPurchasedForTableDisplay'));
			$tableObj->addColumn(new icms_ipf_view_Column('impressions_made'));
			$tableObj->addColumn(new icms_ipf_view_Column('clicks', 'center', FALSE, 'getClicksForTableDisplay'));
			$tableObj->addColumn(new icms_ipf_view_Column('clicks_percent', 'center', FALSE, 'getClicksPercentForTableDisplay'));
			$tableObj->addFilter('client_id', 'getClientArray');
			$tableObj->addIntroButton('addbanner', basename(__FILE__) . '?op=mod', _AM_BANNERS_BANNER_CREATE);
			$tableObj->addQuickSearch(array('description'));
			$tableObj->setDefaultSort('banner_id');
			$tableObj->setDefaultOrder('DESC');
			$icmsAdminTpl->assign('banners_banner_table', $tableObj->fetch());
			$icmsAdminTpl->display('db:banners_admin_banner.html');
			break;
	}
	icms_cp_footer();
}