<?php
/**
* header.php code for pre-2.0 themes
*
* @copyright	The Xoops project http://www.xoops.org/
* @license	  http://www.fsf.org/copyleft/gpl.html GNU public license
* @author	   Kazumi Ono (onokazu)
* @since		Xoops 2.0.14
* @version		$Id: xoops13_header.php 8768 2009-05-16 22:48:26Z pesianstranger $
* @package 		core
*/
defined( 'ICMS_ROOT_PATH' ) or die();

$xoopsOption['theme_use_smarty'] = 0;
if (file_exists(ICMS_THEME_PATH.'/'.$icmsConfig['theme_set'].'/language/lang-'.$icmsConfig['language'].'.php')) {
	include ICMS_THEME_PATH.'/'.$icmsConfig['theme_set'].'/language/lang-'.$icmsConfig['language'].'.php';
} elseif (file_exists(ICMS_THEME_PATH.'/'.$icmsConfig['theme_set'].'/language/lang-english.php')) {
	include ICMS_THEME_PATH.'/'.$icmsConfig['theme_set'].'/language/lang-english.php';
}
xoops_header(false);
include ICMS_THEME_PATH.'/'.$icmsConfig['theme_set'].'/theme.php';
$xoopsOption['show_rblock'] = (!empty($xoopsOption['show_rblock'])) ? $xoopsOption['show_rblock'] : 0;
// include Smarty template engine and initialize it
require_once ICMS_ROOT_PATH.'/class/template.php';
$xoopsTpl = new XoopsTpl();
if ($icmsConfig['debug_mode'] == 3) {
	$xoopsTpl->xoops_setDebugging(true);
}
if ($icmsUser != '') {
	$xoopsTpl->assign(array('xoops_isuser' => true, 'xoops_userid' => $icmsUser->getVar('uid'), 'xoops_uname' => $icmsUser->getVar('uname'), 'xoops_isadmin' => $icmsUserIsAdmin));
}
$xoopsTpl->assign('xoops_requesturi', htmlspecialchars($GLOBALS['xoopsRequestUri'], ENT_QUOTES));
include ICMS_ROOT_PATH.'/include/old_functions.php';

if ($xoopsOption['show_cblock'] || (!empty($icmsModule) && preg_match("/index\.php$/i", xoops_getenv('PHP_SELF')) && $icmsConfig['startpage'] == $icmsModule->getVar('dirname'))) {
	$xoopsOption['show_rblock'] = $xoopsOption['show_cblock'] = 1;
}
themeheader($xoopsOption['show_rblock']);
if ($xoopsOption['show_cblock']) make_cblock();  //create center block

?>