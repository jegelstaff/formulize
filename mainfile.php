<?php
/**
 * Every request starts here.
 *
 * Nothing in this file belongs to one site, so it is never edited. A site's own settings, such as its database
 * login, are in settings.php in the site's folder, with the rest of the site's files: sites/default, or the folder
 * a FORMULIZE_SITE_PATH variable names, from the web server or the environment. include/site.php finds that folder
 * and reads the settings. See ai-context/site-directory.md.
 *
 * Define $xoopsOption['nocommon'] before including this file to get the site's settings and paths without
 * starting the rest of the system.
 */

if (!defined("XOOPS_MAINFILE_INCLUDED")) {
	define("XOOPS_MAINFILE_INCLUDED", 1);

	define('XOOPS_ROOT_PATH', realpath(dirname(__FILE__)));

	require XOOPS_ROOT_PATH . '/include/site.php';

	if (!isset($xoopsOption['nocommon'])) {
		include_once XOOPS_ROOT_PATH . "/include/common.php";
	}
}
