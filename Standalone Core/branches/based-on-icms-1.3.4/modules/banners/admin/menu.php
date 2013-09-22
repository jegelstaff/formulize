<?php
/**
* Configuring the amdin side menu for the module
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: menu.php 20562 2010-12-19 18:23:02Z phoenyx $
*/

$adminmenu[] = array(
	"title" => _MI_BANNERS_BANNERS,
	"link"  => "admin/banner.php",
	"icon"  => "images/icon_big.png",
	"small" => "images/icon_small.png");
$adminmenu[] = array(
	"title" => _MI_BANNERS_CLIENTS,
	"link"  => "admin/client.php",
	"icon"  => "images/client_big.png",
	"small" => "images/client_small.png");
$adminmenu[] = array(
	"title" => _MI_BANNERS_POSITIONS,
	"link"  => "admin/position.php",
	"icon"  => "images/position_big.png",
	"small" => "images/position_small.png");

$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))));
if (isset($module)) {
	$headermenu[] = array(
		"title" => _PREFERENCES,
		"link"  => "../../system/admin.php?fct=preferences&amp;op=showmod&amp;mod=" . $module->getVar("mid"));
	$headermenu[] = array(
		"title" => _CO_ICMS_GOTOMODULE,
		"link"  => ICMS_URL . "/modules/" . $module->getVar("dirname"));
	$headermenu[] = array(
		"title" => _CO_ICMS_UPDATE_MODULE,
		"link"  => ICMS_URL . "/modules/system/admin.php?fct=modulesadmin&op=update&module=" . $module->getVar("dirname"));
	$headermenu[] = array(
		"title" => _MODABOUT_ABOUT,
		"link"  => ICMS_URL . "/modules/" . $module->getVar("dirname") . "/admin/about.php");
}