<?php
/**
 *
 * Plugin for SmartPartner.
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.2
 * @author		ImpressCMS
 * @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id$
 */

function icms_plugin_smartpartner() {
	$pluginInfo = array();

	$pluginInfo['items']['partner']['caption'] = 'Partner';
	$pluginInfo['items']['partner']['url'] = 'partner.php?partnerid=%u';
	$pluginInfo['items']['partner']['request'] = 'partnerid';

	$pluginInfo['items']['category']['caption'] = 'Category';
	$pluginInfo['items']['category']['url'] = 'index.php?view_category_id=%u';
	$pluginInfo['items']['category']['request'] = 'view_category_id';

	return $pluginInfo;
}

?>