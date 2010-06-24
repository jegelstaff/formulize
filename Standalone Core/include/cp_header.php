<?php
/**
* module files can include this file for admin authorization
* the file that will include this file must be located under xoops_url/modules/module_directory_name/admin_directory_name/
* 
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by underdog <underdog@impresscms.org>
* @version	$Id: cp_header.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/
//error_reporting(0);
include_once '../../../mainfile.php';
include_once XOOPS_ROOT_PATH . "/include/cp_functions.php";
$moduleperm_handler = & xoops_gethandler( 'groupperm' );
if ( $icmsUser ) {
  $url_arr = explode('/',strstr($xoopsRequestUri,'/modules/'));
  $module_handler =& xoops_gethandler('module');
  $icmsModule =& $module_handler->getByDirname($url_arr[2]);
  $xoopsModule =& $module_handler->getByDirname($url_arr[2]);
  unset($url_arr);

  if ( !$moduleperm_handler->checkRight( 'module_admin', $icmsModule->getVar( 'mid' ), $icmsUser->getGroups() ) ) {
	  redirect_header( XOOPS_URL . "/user.php", 1, _NOPERM, false );
  }
} else {
  redirect_header( XOOPS_URL . "/user.php", 1, _NOPERM, false );
}

// set config values for this module
if ( $icmsModule->getVar( 'hasconfig' ) == 1 || $icmsModule->getVar( 'hascomments' ) == 1 ) {
  $config_handler = & xoops_gethandler( 'config' );
  $icmsModuleConfig =& $config_handler->getConfigsByCat( 0, $icmsModule->getVar( 'mid' ) );
  $xoopsModuleConfig =& $config_handler->getConfigsByCat( 0, $icmsModule->getVar( 'mid' ) );
}

// include the default language file for the admin interface
icms_loadLanguageFile($icmsModule->dirname(), 'admin');

?>