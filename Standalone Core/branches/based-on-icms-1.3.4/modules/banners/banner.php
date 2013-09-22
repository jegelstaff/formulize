<?php
/**
* Banner page
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: banner.php 23917 2012-03-21 02:21:55Z qm-b $
*/

/**
 * edit a banner
 *
 * @param int $client_id client id
 * @param int $banner_id Bannerid to be edited
 * @global object $banners_banner_handler banner handler
 * @global object $icmsTpl template ojbect
*/
function editbanner($client_id, $banner_id = 0) {
	global $banners_banner_handler, $icmsTpl;

	$bannerObj = $banners_banner_handler->get($banner_id);

	// only allow to edit own banners
	if (!$bannerObj->isNew() && $bannerObj->getVar('client_id') != $client_id) {
		redirect_header(BANNERS_URL, 3, _NOPERM);
	}

	// reset control for type to reflect a filtered list of values for the client
	$bannerObj->setControl('type', array(
		'itemHandler' => 'banner',
		'method'      => 'getTypeArrayForClient',
		'module'      => 'banners',
		'onSelect'    => 'submit'));

	if (isset($_POST['op']) && $_POST['op'] == 'changedField' && in_array($_POST['changedField'], array('type', 'contract'))) {
		$controller = new icms_ipf_Controller($banners_banner_handler);
		$controller->postDataToObject($bannerObj);
	} elseif ($bannerObj->isNew()) {
		// set initial value for type
		$typesForClient_keys = array_keys($banners_banner_handler->getTypeArrayForClient());
		if (count($typesForClient_keys) > 0) {
			$bannerObj->setVar('type', $typesForClient_keys[0]);
		}
	}

	if ($bannerObj->getVar('type') == BANNERS_BANNER_TYPE_IMAGE) {
		$bannerObj->hideFieldFromForm('source');
		$bannerObj->setFieldAsRequired('filename');
	} elseif ($bannerObj->getVar('type') == BANNERS_BANNER_TYPE_HTML) {
		$bannerObj->hideFieldFromForm(array('filename', 'link', 'target'));
		$bannerObj->setFieldAsRequired('source');
	} elseif ($bannerObj->getVar('type') == BANNERS_BANNER_TYPE_FLASH) {
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

	if ($bannerObj->isNew()) {
		$bannerObj->setVar('begin', time());
		$bannerObj->setVar('end', time()+604800);
	} else {
		$bannerObj->hideFieldFromForm('type');
	}
	$bannerObj->setVar('client_id', $client_id);
	$bannerObj->setVar('active', 0);
	$bannerObj->hideFieldFromForm(array('client_id', 'active'));
	$sform = $bannerObj->getForm('', 'addbanner');
	$sform->assign($icmsTpl, 'banners_bannerform');
	$icmsTpl->assign('lang_bannerform_title', _MD_BANNERS_BANNER_CREATE);
}

include_once 'header.php';

// check if a client is assigned to the current user
$banners_client_handler = icms_getModuleHandler('client', basename(dirname(__FILE__)), 'banners');

$xoopsOption['template_main'] = "banners_banner.html";
include_once ICMS_ROOT_PATH . "/header.php";

$banners_banner_handler = icms_getModuleHandler('banner', basename(dirname(__FILE__)), 'banners');

/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = $_GET['op'];
if (isset($_POST['op'])) $clean_op = $_POST['op'];

$clean_banner_id = isset($_GET['banner_id']) ? (int)$_GET['banner_id'] : 0 ;

/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod', 'addbanner', 'changedField', 'view', '');

/** Only proceed if the supplied operation is a valid operation */
if (in_array($clean_op, $valid_op, TRUE)) {
	switch ($clean_op) {
		case 'mod':
		case 'changedField':
			if (!is_object(icms::$user)) redirect_header(icms_getPreviousPage(ICMS_URL), 3, _NOPERM);
			$client_id = $banners_client_handler->getUserClientId(TRUE);
			if ($client_id === FALSE) {
				header("location: client.php");
				exit();
			}
			// check if at least one position is available
			$banners_position_handler = icms_getModuleHandler('position', basename(dirname(__FILE__)), 'banners');
			$positions = $banners_position_handler->getCount();
			if ($positions == 0) redirect_header(BANNERS_URL . basename(__FILE__), 3, _MD_BANNERS_BANNER_SETUP_POSITION);
			editbanner($client_id, $clean_banner_id);
			break;
		case 'addbanner':
			if (!is_object(icms::$user)) redirect_header(icms_getPreviousPage(ICMS_URL), 3, _NOPERM);
			$client_id = $banners_client_handler->getUserClientId(TRUE);
			if ($client_id === FALSE) {
				header("location: client.php");
				exit();
			}
			$bannerObj = $banners_banner_handler->get($clean_banner_id);
			$isNew = $bannerObj->isNew();

			// only allow to edit own banners
			if (!$bannerObj->isNew() && $bannerObj->getVar('client_id') != $client_id || $_POST['client_id'] != $client_id) {
				redirect_header(BANNERS_URL, 3, _NOPERM);
			}

			// do not allow to change the type for existing banners
			if (!$bannerObj->isNew() && $bannerObj->getVar('type') != (int)$_POST['type']) {
				redirect_header(BANNERS_URL, 3, _NOPERM);
			}

			// do not allow to delete banners here
			unset($_POST['delete_filename']);

			$banners_banner_handler->setAllowedMimetypes((int)$_POST['type']);
			$controller = new icms_ipf_Controller($banners_banner_handler);
			$bannerObj = $controller->storeFromDefaultForm(_MD_BANNERS_BANNER_CREATED, _MD_BANNERS_BANNER_MODIFIED, NULL);

			// check for errors
			$errors = $bannerObj->getErrors();
			if (!empty($errors)) {
				redirect_header(icms_getPreviousPage(), 3, _CO_ICMS_SAVE_ERROR . $bannerObj->getHtmlErrors());
			}

			// overwrite the client to make sure the user hasn't changed it in the background
			$bannerObj->setVar('client_id', $client_id);

			// inactivate the banner again (same reason)
			$bannerObj->setVar('active', 0);

			// store the object
			$bannerObj->skipSaveEvents = TRUE;
			$bannerObj->store();
			$bannerObj->skipSaveEvents = FALSE;

			// send email to webmaster
			$bannerObj->notifyWebmaster();

			redirect_header(BANNERS_URL . basename(__FILE__), 3, $isNew ? _MD_BANNERS_BANNER_CREATED : _MD_BANNERS_BANNER_MODIFIED);
			break;
		case 'view':
			$bannerObj = $banners_banner_handler->get($clean_banner_id);
			$bannerObj->incrementClicks();
			header('location: ' . str_replace('{ICMS_URL}', ICMS_URL, $bannerObj->getVar('link', 'e')));
			exit();
			break;
		default:
			$client_id = $banners_client_handler->getUserClientId(TRUE);
			if ($client_id === FALSE) {
				header("location: client.php");
				exit();
			}
			if (!is_object(icms::$user)) redirect_header(icms_getPreviousPage(ICMS_URL), 3, _NOPERM);
			// list all banners for this customer
			$tableObj = new icms_ipf_view_Table($banners_banner_handler, icms_buildCriteria(array('client_id' => $client_id)), array('edit'), TRUE);
			$tableObj->addColumn(new icms_ipf_view_Column('status', 'center', FALSE, 'getStatusForTableDisplay', FALSE, FALSE, FALSE));
			$tableObj->addColumn(new icms_ipf_view_Column('banner_id', 'center'));
			$tableObj->addColumn(new icms_ipf_view_Column('description', _GLOBAL_LEFT, FALSE, 'getDescriptionForTableDisplay'));
			$tableObj->addColumn(new icms_ipf_view_Column('begin', _GLOBAL_LEFT, FALSE, 'getBeginForTableDisplay'));
			$tableObj->addColumn(new icms_ipf_view_Column('end', _GLOBAL_LEFT, FALSE, 'getEndForTableDisplay'));
			$tableObj->addColumn(new icms_ipf_view_Column('impressions_purchased', _GLOBAL_LEFT, FALSE, 'getImpressionsPurchasedForTableDisplay'));
			$tableObj->addColumn(new icms_ipf_view_Column('impressions_made'));
			$tableObj->addColumn(new icms_ipf_view_Column('clicks', 'center', FALSE, 'getClicksForTableDisplay'));
			$tableObj->addColumn(new icms_ipf_view_Column('clicks_percent', 'center', FALSE, 'getClicksPercentForTableDisplay'));
			$tableObj->setDefaultSort('banner_id');
			$tableObj->setDefaultOrder('DESC');
			$icmsTpl->assign('banners_banner_table', $tableObj->fetch());
			$icmsTpl->assign('banners_title', _MD_BANNERS_YOUR_BANNERS);
	}
} else {
	header("location: " . BANNERS_URL);
	exit();
}

include_once 'footer.php';