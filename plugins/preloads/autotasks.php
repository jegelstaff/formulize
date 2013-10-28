<?php
/**
 * ImpressCMS Autotasks features
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		libraries
 * @since		1.1
 * @author		mekdrop <mekdrop@gmail.com>
 * @version		$Id: autotasks.php 19812 2010-07-13 23:23:40Z skenow $
 */
/**
 *
 * Preload class and event for Autotasks
 * @since	1.2
 *
 */
class IcmsPreloadAutotasks extends icms_preload_Item {

	/**
	 * Function to be triggered at the end of the core boot process
	 */
	function eventFinishCoreBoot() {
		$handler = &icms_getModuleHandler('autotasks', 'system');
		if ($handler->needExecution()) {
			$rez = $handler->execTasks();
			$handler->startIfNeeded();
			if ($handler->needExit()) {
				var_dump($rez);
				exit(0);
			}
		}
	}

	/**
	 * Do this event when saving item
	 *
	 * @param array config array
	 */
	function eventAfterSaveSystemAdminPreferencesItems($array) {
		if (!isset($array[ICMS_CONF_AUTOTASKS])) return;
		$handler = icms_getModuleHandler('autotasks', 'system');
		$handler->virtual_config = array();
		$array = &$array[ICMS_CONF_AUTOTASKS];
		$vconfig1 = array();
		$vconfig2 = array();
		foreach ($array as $key => $values) {
			$vconfig1[$key] = $values[0];
			$vconfig2[$key] = $values[1];
		}
		$handler->enableVirtualConfig($vconfig1);
		$system = $handler->getCurrentSystemHandler(true);
		if ($system->isEnabled()) {
			$system->stop();
		}
		$handler->enableVirtualConfig($vconfig2);
		$system = $handler->getCurrentSystemHandler(true);
		if ($rez = $system->canRun()) {
			$time = (int) ($handler->getRealTasksRunningTime());
			$rez = $system->start($time);
		} else {
			icms_loadLanguageFile('system', 'autotasks', true);
			icms_core_Message::error(_CO_ICMS_AUTOTASKS_INIT_ERROR);
			return false;
		}
		$handler->disableVirtualConfig();
	}

}
