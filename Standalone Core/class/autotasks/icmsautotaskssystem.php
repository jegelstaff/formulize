<?php
/**
 * ImpressCMS AUTOTASKSs Library - Base class
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.2 alpha 2
 * @author		MekDrop <mekdrop@gmail.com>
 */

require_once ICMS_ROOT_PATH . '/class/autotasks/iautotasksystem.php';

abstract class IcmsAutoTasksSystem
	implements iAutoTaskSystem  {

	function getName() {
		return strtolower(substr(get_class($this),strlen('IcmsAutoTasks')));
	}

	public function needExecution() {
		static $execMode = null;
		if ($execMode === null) {
			$execMode = defined('ICMS_AUTOTASKS_EXECMODE') && ICMS_AUTOTASKS_EXECMODE;
		}
		return $execMode;
	}

	public function needExit() {
		return true;
	}

}

?>