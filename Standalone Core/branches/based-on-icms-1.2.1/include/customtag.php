<?php
/**
* File included to initiate the ImpressCMS Custom Tag Feature
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		core
* @since		1.1
* @author		marcan <marcan@impresscms.org>
* @version		$Id: customtag.php 8543 2009-04-11 10:27:02Z icmsunderdog $
*/

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**
* Initialize the customtag
*/
function icms_customtag_initiate() {
	global $xoopsTpl, $icms_customtag_handler;
	if (is_object($xoopsTpl)) {
		foreach($icms_customtag_handler->objects as $k=>$v) {
			$xoopsTpl->assign($k, $v->render());
		}
	}
}

icms_loadLanguageFile('system', 'customtag', true);
global $icms_customtag_handler;
$icms_customtag_handler = xoops_getModuleHandler('customtag', 'system');
$icms_customTagsObj = $icms_customtag_handler->getCustomtagsByName();

?>