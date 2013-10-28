<?php
/**
* Administration of content pages, versionfile
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		System
* @subpackage	Symlinks
* @since		1.1
* @author		Rodrigo Pereira Lima (AKA TheRplima) <therplima@impresscms.org>
* @version		SVN: $Id: xoops_version.php 20836 2011-02-15 01:29:18Z skenow $
*/

$modversion = array(
	'name' => _MD_AM_PAGES,
	'version' => "1",
	'description' => _MD_AM_PAGES_DSC,
	'author' => "Rodrigo Pereira Lima aka TheRplima",
	'credits' => "The ImpressCMS Project",
	'help' => "pages.html",
	'license' => "GPL see LICENSE",
	'official' => 1,
	'image' => "pages.gif",
	'hasAdmin' => 1,
	'adminpath' => "admin.php?fct=pages",
	'category' => XOOPS_SYSTEM_PAGES,
	'group' => _MD_AM_GROUPS_CONTENT);