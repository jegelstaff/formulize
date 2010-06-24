<?php
/**
* Administration of content pages, versionfile
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		Administration
* @since		1.1
* @author		Rodrigo Pereira Lima (AKA TheRplima) <therplima@impresscms.org>
* @version		$Id: xoops_version.php 8570 2009-04-11 13:48:52Z icmsunderdog $
*/

$modversion['name'] = _MD_AM_PAGES;
$modversion['version'] = "1";
$modversion['description'] = "ImpressCMS Content Manager";
$modversion['author'] = "Rodrigo Pereira Lima aka TheRplima";
$modversion['credits'] = "The ImpressCMS Project";
$modversion['help'] = "pages.html";
$modversion['license'] = "GPL see LICENSE";
$modversion['official'] = 1;
$modversion['image'] = "pages.gif";

$modversion['hasAdmin'] = 1;
$modversion['adminpath'] = "admin.php?fct=pages";
$modversion['category'] = XOOPS_SYSTEM_PAGES;
?>