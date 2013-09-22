<?php
/**
* Client page
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: client.php 20431 2010-11-21 12:40:45Z phoenyx $
*/

/**
 * edit a client
 *
 * @param int $banner_id Bannerid to be edited
 * @param bool $hideForm hide the form by default
 * @global object $banners_client_handler client handler
 * @global object $icmsTpl template ojbect
*/
function editclient($client_id = 0, $hideForm = FALSE) {
	global $banners_client_handler, $icmsTpl;

	$clientObj = $banners_client_handler->get($client_id);
	$clientObj->setVar('uid', icms::$user->getVar('uid'));
	$clientObj->setVar('email', icms::$user->getVar('email'));
	$clientObj->hideFieldFromForm(array('uid', 'since', 'active'));
	$sform = $clientObj->getForm('', 'addclient');
	$sform->assign($icmsTpl, 'banners_clientform');
	$icmsTpl->assign('lang_clientform_title', _MD_BANNERS_CLIENT_CREATE);
	$icmsTpl->assign('hideForm', $hideForm);
}

include_once 'header.php';

if (!is_object(icms::$user)) redirect_header(icms_getPreviousPage(ICMS_URL), 3, _NOPERM);

$xoopsOption['template_main'] = 'banners_client.html';
include_once ICMS_ROOT_PATH . '/header.php';

// check if a client is assigned to the current user
$banners_client_handler = icms_getModuleHandler('client', basename(dirname(__FILE__)), 'banners');
$client_id = $banners_client_handler->getUserClientId();
if ($client_id !== FALSE && $client_id > 0) {
	$clientObj = $banners_client_handler->get($client_id);
	if ($clientObj->getVar('active')) {
		header("location: banner.php");
		exit();
	} else {
		redirect_header(ICMS_URL, 3, _MD_BANNERS_CLIENT_NOTACTIVE);
	}
}

/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
if (isset($_GET['op'])) $clean_op = $_GET['op'];
if (isset($_POST['op'])) $clean_op = $_POST['op'];

/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('addclient', '');

/** Only proceed if the supplied operation is a valid operation */
if (in_array($clean_op, $valid_op, TRUE)) {
	switch ($clean_op) {
		case 'addclient':
			$controller = new icms_ipf_Controller($banners_client_handler);
			$clientObj = $controller->storeFromDefaultForm(_MD_BANNERS_CLIENT_CREATED, _MD_BANNERS_CLIENT_MODIFIED, NULL);

			// overwrite some data to make sure the user hasn't manipulated it in the source and store the object again
			$clientObj->setVar('uid', icms::$user->getVar('uid'));
			$clientObj->setVar('since', time());
			$clientObj->setVar('active', 0);
			$clientObj->store();

			// send email to webmaster
			$clientObj->notifyWebmaster();

			redirect_header(ICMS_URL, 3, _MD_BANNERS_CLIENT_CREATED);
			break;
		default:
			editclient(0);
	}
} else {
	header("location: " . BANNERS_URL);
	exit();
}

include_once 'footer.php';