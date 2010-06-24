<?php
/**
* File included to initiate the ImpressCMS Adsense Feature
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		core
* @since		1.2
* @author		stranger <marcan@impresscms.org>
* @version		$Id: adsense.php 8919 2009-06-24 12:02:50Z skenow $
*/

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
* Initialize the adsense
*/
function icms_adsense_initiate() {
	global $xoopsTpl, $icms_adsense_handler;
	if (is_object($xoopsTpl)) {
		foreach($icms_adsense_handler->objects as $k=>$v) {
			$xoopsTpl->assign('adsense_' . $k, $v->render());
		}
	}
}

icms_loadLanguageFile('system', 'adsense', true);
global $icms_adsense_handler;
$icms_adsense_handler = xoops_getModuleHandler('adsense', 'system');
$icms_adsensesObj = $icms_adsense_handler->getAdsensesByTag();

?>