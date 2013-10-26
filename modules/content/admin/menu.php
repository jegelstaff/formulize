<?php
/**
 * Configuring the amdin side menu for the module
 *
 * @copyright	The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
 * @package		content
 * @version		$Id: menu.php 20561 2010-12-19 18:24:19Z phoenyx $
 */

$adminmenu[] = array(
	'title'	=> _MI_CONTENT_CONTENTS,
	'link'	=> 'admin/content.php');

$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))));
$headermenu[] = array(
	'title'	=> _PREFERENCES,
	'link'	=> '../../system/admin.php?fct=preferences&amp;op=showmod&amp;mod=' . $module->getVar('mid'));

$headermenu[] = array(
	'title'	=> _CO_ICMS_GOTOMODULE,
	'link'	=> ICMS_URL . '/modules/content/');

$headermenu[] = array(
	'title'	=> _CO_ICMS_UPDATE_MODULE,
	'link'	=> ICMS_URL . '/modules/system/admin.php?fct=modulesadmin&op=update&amp;module=' . $module->getVar('dirname'));

$headermenu[] = array(
	'title'	=> _MODABOUT_ABOUT,
	'link'	=> ICMS_URL . '/modules/content/admin/about.php');