<?php
/**
 *
 * Plugin for XCGAL.
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.2
 * @author		ImpressCMS
 * @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id$
 */

function icms_plugin_xcgal() {
	global $icmsConfig;

	$pluginInfo = array();
	$pluginInfo['items']['album']['caption'] = 'Album';
	$pluginInfo['items']['item']['url'] = 'thumbnails.php?album=%u';
	$pluginInfo['items']['item']['request'] = 'album';

	return $pluginInfo;
}

?>