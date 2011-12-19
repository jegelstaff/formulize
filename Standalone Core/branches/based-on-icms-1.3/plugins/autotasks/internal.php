<?php
/**
 * ImpressCMS AUTOTASKSs Library - Internal Support
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.2 alpha 2
 * @author		MekDrop <mekdrop@gmail.com>
 */

class IcmsAutoTasksInternal
extends icms_sys_autotasks_System {

	/**
	 * check if can run
	 * @return bool
	 */
	function canRun() {
		return true;
	}

	/**
	 * Set Checking Interval (if not enabled enables automated tasks system
	 * @param  int	$interval	interval of checking for new tasks
	 * @return bool				returns true if start was succesfull
	 */
	public function start(int $interval) {
		return true;
	}

	/**
	 * Stops automated tasks system
	 * @return bool returns true if was succesfull
	 */
	function stop() {
		return false;
	}

	/**
	 *  checks if core is enabled
	 *
	 * @return bool
	 */
	function isEnabled() {
		return true;
	}

	/**
	 *  Checks if need set new timer when automated task object was executed
	 *
	 *  @return bool
	 */
	function needStart() {
		return false;
	}

	public function needExecution() {
		return true;
	}

	public function needExit() {
		return false;
	}

}

?>