<?php
/**
*
* Class To load plugins for modules.
*
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		core
* @since		1.2
* @author		ImpressCMS
* @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id$
*/

class IcmsPlugins {

	public $_infoArray;

	function IcmsPlugins($array) {
		$this->_infoArray = $array;
	}

	function getItemInfo($item) {
		if (isset($this->_infoArray['items'][$item])) {
			return $this->_infoArray['items'][$item];
		} else {
			return false;
		}
	}

	function getItemList() {
		$itemsArray = $this->_infoArray['items'];
    	foreach ($itemsArray as $k=>$v) {
			$ret[$k] = $v['caption'];
    	}
    	return $ret;
	}

	function getItem() {
		$ret = false;
		foreach($this->_infoArray['items'] as $k => $v) {
			$search_str = str_replace('%u', '', $v['url']);
			if (strpos($_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'], $search_str) > 0) {
				$ret = $k;
				break;
			}
		}
		return $ret;
	}

	function getItemIdForItem($item) {
		return $_REQUEST[$this->_infoArray['items'][$item]['request']];
	}
}

class IcmsPluginsHandler {

	public $pluginPatterns = false;

	function getPlugin($path, $dirname) {
		$pluginName = ICMS_ROOT_PATH . '/plugins/'.$path.'/' . $dirname . '.php';
		if (file_exists($pluginName)) {
			include_once($pluginName);
			$function = 'icms_plugin_' . $dirname;
			if (function_exists($function)) {
				$array = $function();
				$ret = new IcmsPlugins($array);
				return $ret;
			}
		}
		return false;
	}

	function getPluginsArray($path) {
		include_once(XOOPS_ROOT_PATH . "/class/xoopslists.php");

		$module_handler = xoops_gethandler('module');
		$criteria = new CriteriaCompo();
		$criteria->add(new Criteria('isactive', 1));
		$tempModulesObj = $module_handler->getObjects($criteria);
		$modulesObj = array();
		foreach ($tempModulesObj as $moduleObj) {
			$modulesObj[$moduleObj->getVar('dirname')] = $moduleObj;
		}

		$aFiles = XoopsLists::getPhpListAsArray(ICMS_ROOT_PATH . '/plugins/'.$path.'/');
		$ret = array();
		foreach($aFiles as $pluginName) {
				$module_xoops_version_file = XOOPS_ROOT_PATH . "/modules/$pluginName/xoops_version.php";
				$module_icms_version_file = XOOPS_ROOT_PATH . "/modules/$pluginName/icms_version.php";
				if ((file_exists($module_xoops_version_file) || file_exists($module_icms_version_file))&& isset($modulesObj[$pluginName])) {
					$ret[$pluginName] = $modulesObj[$pluginName]->getVar('name');
				}
		}
		return $ret;
	}
}
?>