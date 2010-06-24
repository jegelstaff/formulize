<?php
/**
* Configuring the amdin side menu for the module
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
* @package		content
* @version		$Id$
*/

$i = -1;

$i++;
$adminmenu[$i]['title'] = _MI_CONTENT_CONTENTS;
$adminmenu[$i]['link'] = 'admin/content.php';


global $xoopsModule;
if (isset($xoopsModule)) {

	$i = -1;

	$i++;
	$headermenu[$i]['title'] = _PREFERENCES;
	$headermenu[$i]['link'] = '../../system/admin.php?fct=preferences&amp;op=showmod&amp;mod=' . $xoopsModule->getVar('mid');

	$i++;
	$headermenu[$i]['title'] = _CO_ICMS_GOTOMODULE;
	$headermenu[$i]['link'] = ICMS_URL . '/modules/content/';

	$i++;
	$headermenu[$i]['title'] = _CO_ICMS_UPDATE_MODULE;
	$headermenu[$i]['link'] = ICMS_URL . '/modules/system/admin.php?fct=modulesadmin&op=update&amp;module=' . $xoopsModule->getVar('dirname');

	$i++;
	$headermenu[$i]['title'] = _MODABOUT_ABOUT;
	$headermenu[$i]['link'] = ICMS_URL . '/modules/content/admin/about.php';
}
?>