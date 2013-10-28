<?php
/**
 * ImpressCMS AUTOTASKSs Library - Base class
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Autotasks
 * @since		1.2 alpha 2
 * @author		MekDrop <mekdrop@gmail.com>
 * @version		SVN: $Id:System.php 19775 2010-07-11 18:54:25Z malanciault $
 */
/**
 *
 *
 * @category	ICMS
 * @package		Autotasks
 */
abstract class icms_sys_autotasks_System
implements icms_sys_autotasks_ISystem  {

	/**
	 *
	 */
	public function getName() {
		return strtolower(substr(get_class($this),strlen('IcmsAutoTasks')));
	}

	/**
	 *
	 */
	public function needExecution() {
		static $execMode = null;
		if ($execMode === null) {
			$execMode = defined('ICMS_AUTOTASKS_EXECMODE') && ICMS_AUTOTASKS_EXECMODE;
		}
		return $execMode;
	}

	/**
	 *
	 */
	public function needExit() {
		return true;
	}

}

