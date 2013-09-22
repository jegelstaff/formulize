<?php
/**
 * Handler for plugins
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Plugins
 * @since		1.2
 * @author		ImpressCMS
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: Handler.php 11439 2011-11-12 21:39:36Z skenow $
 */
 /**
  * Handler for the plugins object
  * @category	ICMS
  * @package	Plugins
  */
class icms_plugins_Handler {

	public $pluginPatterns = false;

	/**
	 * Get a plugin object from a path and dirname
	 * @param string $path
	 * @param string $dirname
	 * @return	mixed	A plugin object or False
	 */
	public function getPlugin($path, $dirname) {
		$pluginName = ICMS_PLUGINS_PATH . '/' . $path . '/' . $dirname . '.php';
		if (file_exists($pluginName)) {
			include_once $pluginName ;
			$function = 'icms_plugin_' . $dirname;
			if (function_exists($function)) {
				$array = $function();
				$ret = new icms_plugins_Object($array);
				return $ret;
			}
		}
		return FALSE;
	}

	/**
	 * Get an array of plugins
	 * @param string $path
	 * @return multitype:
	 */
	public function getPluginsArray($path) {

		$module_handler = icms::handler('icms_module');
		$criteria = new icms_db_criteria_Compo();
		$criteria->add(new icms_db_criteria_Item('isactive', 1));
		$tempModulesObj = $module_handler->getObjects($criteria);
		$modulesObj = array();
		foreach ($tempModulesObj as $moduleObj) {
			$modulesObj[$moduleObj->getVar('dirname')] = $moduleObj;
		}

		$aFiles = str_replace('.php', '', icms_core_Filesystem::getFileList(ICMS_PLUGINS_PATH . '/' . $path . '/', '', array('php')));
		$ret = array();
		foreach($aFiles as $pluginName) {
			$module_xoops_version_file = ICMS_MODULES_PATH . "/$pluginName/xoops_version.php";
			$module_icms_version_file = ICMS_MODULES_PATH . "/$pluginName/icms_version.php";
			if ((file_exists($module_xoops_version_file) || file_exists($module_icms_version_file)) && isset($modulesObj[$pluginName])) {
				$ret[$pluginName] = $modulesObj[$pluginName]->getVar('name');
			}
		}
		return $ret;
	}
}
