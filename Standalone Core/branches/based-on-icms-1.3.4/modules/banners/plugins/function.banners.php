<?php
/**
* Smarty Plugin for banners module
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: function.banners.php 20562 2010-12-19 18:23:02Z phoenyx $
*/

/**
 * construct the html code to display a banner
 *
 * @param array $params
 * @param object $smarty
 * @return string html code for the banner
 */
function smarty_function_banners($params, &$smarty) {
	static $cache;

	$module = icms::handler("icms_module")->getByDirname('banners');

	// only proceed if banners module is present and active
	if (!is_object($module) || !$module->getVar("isactive")) return;

	// get the banner for the given position
	$banners_banner_handler = icms_getModuleHandler('banner', $module->getVar("dirname"), 'banners');
	$banners_client_handler = icms_getModuleHandler('client', $module->getVar("dirname"), 'banners');
	$banners_position_handler = icms_getModuleHandler('position', $module->getVar("dirname"), 'banners');
	$banners_positionlink_handler = icms_getModuleHandler('positionlink', $module->getVar("dirname"), 'banners');
	$banners_visiblein_handler = icms_getModuleHandler('visiblein', $module->getVar("dirname"), 'banners');

	$modid = icms_view_PageBuilder::getPage();

	$sql  = 'SELECT b.* ';
	$sql .= '  FROM '.$banners_banner_handler->table.' b, '.$banners_client_handler->table.' c, ';
	$sql .=           $banners_position_handler->table.' p, '.$banners_positionlink_handler->table.' pl, ';
	$sql .=           $banners_visiblein_handler->table.' v ';
	$sql .= ' WHERE b.banner_id = pl.banner_id ';
	$sql .= '   AND b.banner_id = v.banner_id ';
	$sql .= '   AND b.client_id = c.client_id ';
	$sql .= '   AND pl.position_id = p.position_id ';
	$sql .= '   AND p.name = "'.$params['position'].'" ';
	$sql .= '   AND ((v.module = '.$modid['module'].' AND v.page = '.$modid['page'].') ';
	$sql .= '    OR  (v.module = 0 AND v.page = 0)) ';
	$sql .= '   AND b.active = 1';
	$sql .= '   AND c.active = 1';
	$sql .= '   AND ((b.type = '.BANNERS_BANNER_TYPE_IMAGE.' AND b.filename <> "") OR b.type <> '.BANNERS_BANNER_TYPE_IMAGE.')';
	if (isset($cache) && is_array($cache) && count($cache) > 0) {
		$sql .= '   AND b.banner_id NOT IN ('.implode(', ', $cache).')';
	}
	$sql .= '   AND ((b.contract = '.BANNERS_BANNER_CONTRACT_TIME.' AND b.begin <= '.time().' AND b.end >= '.time().') ';
	$sql .= '    OR  (b.contract = '.BANNERS_BANNER_CONTRACT_IMPRESSIONS.' AND (b.impressions_purchased = 0 OR b.impressions_purchased > b.impressions_made))) ';
	$sql .= ' ORDER BY RAND() LIMIT 1';

	// reset the banner cache if required
	if (!isset($params['cache']) || $params['cache'] == FALSE) unset($cache);

	$banners = $banners_banner_handler->getObjects(NULL, FALSE, TRUE, $sql);
	if (count($banners) != 1) return;

	// store banner id in the cache if requested
	if (isset($params['cache']) && $params['cache'] == TRUE) $cache[] = $banners[0]->getVar('banner_id');

	$banners[0]->incrementImpressions();
	return $banners[0]->render();
}