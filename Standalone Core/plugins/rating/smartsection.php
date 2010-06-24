<?php
/**
*
* Plugin for SmartSection.
*
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		core
* @since		1.2
* @author		ImpressCMS
* @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id$
*/

function icms_plugin_smartsection() {
	$pluginInfo = array();

	$pluginInfo['items']['item']['caption'] = 'Article';
	$pluginInfo['items']['item']['url'] = 'item.php?itemid=%u';
	$pluginInfo['items']['item']['request'] = 'itemid';

	$pluginInfo['items']['category']['caption'] = 'Category';
	$pluginInfo['items']['category']['url'] = 'category.php?categoryid=%u';
	$pluginInfo['items']['category']['request'] = 'categoryid';

	return $pluginInfo;
}

?>