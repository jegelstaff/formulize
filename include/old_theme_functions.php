<?php
/**
 * Handles all old theme functions within ImpressCMS (for old themes)
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: old_theme_functions.php 19118 2010-03-27 17:46:23Z skenow $
 */

// These are needed when viewing old modules (that don't use Smarty template files) when a theme that use Smarty templates are selected.

// function_exists check is needed for inclusion from the admin side

if (!function_exists('opentable')) {
	function OpenTable($width='100%')
	{
		echo '<table width="'.$width.'" cellspacing="0" class="outer"><tr><td class="even">';
	}
}

if (!function_exists('closetable')) {
	function CloseTable()
	{
		echo '</td></tr></table>';
	}
}

if (!function_exists('themecenterposts')) {
	function themecenterposts($title, $content)
	{
		echo '<table cellpadding="4" cellspacing="1" width="98%" class="outer"><tr><td class="head">'.$title.'</td></tr><tr><td><br />'.$content.'<br /></td></tr></table>';
	}
}
?>