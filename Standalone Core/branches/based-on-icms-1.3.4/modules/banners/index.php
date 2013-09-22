<?php
/**
* User index page of the module
*
* Including the banner page
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: index.php 20431 2010-11-21 12:40:45Z phoenyx $
*/

include_once "header.php";

if (!is_object(icms::$user)) redirect_header(icms_getPreviousPage(ICMS_URL), 3, _NOPERM);

// check if a client is assigned to the current user
$banners_client_handler = icms_getModuleHandler('client', basename(dirname(__FILE__)), 'banners');
$client_id = $banners_client_handler->getUserClientId(TRUE);
if ($client_id !== FALSE) {
	header("location: banner.php");
} else {
	header("location: client.php");
}