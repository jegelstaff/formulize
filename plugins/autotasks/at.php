<?php
/**
 * ImpressCMS AUTOTASKSs Library - AT Support
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.2 alpha 2
 * @author		MekDrop <mekdrop@gmail.com>
 */

class IcmsAutoTasksAt
extends icms_sys_autotasks_System {

	/*
	 * check if can run
	 * @return bool
	 */
	function canRun() {
		if (PHP_OS != 'WINNT') return false;
		if (!isset($_SERVER['COMSPEC']) && (!isset($_SERVER['ComSpec']))) return false;
		return isset($_SERVER['COMSPEC'])?file_exists($_SERVER['COMSPEC']):file_exists($_SERVER['ComSpec']);
	}

	/*
	 * Set Checking Interval (if not enabled enables automated tasks system
	 * @param  int	$interval	interval of checking for new tasks
	 * @return bool				returns true if start was succesfull
	 */
	function start(int $interval) {
		if ($this->isEnabled()) $this->stop();
		$rez = shell_exec('at '.date('H:i', time() + $interval * 60 + 10 ).' '.$this->getCommandLine());
		return (substr($rez, 0, 5) == 'Added');
	}

	/*
	 * Stops automated tasks system
	 * @return bool returns true if was succesfull
	 */
	function stop() {
		$id = $this->getProcessId();
		if ($id < 0) return false;
		$rez = shell_exec('at '.$id.' /DELETE');
		return true;
	}

	/*
	 *  checks if core is enabled
	 *
	 * @return bool
	 */
	function isEnabled() {
		return ($this->getProcessId()>0);
	}

	/*
	 * gets command executed
	 * @return string
	 */
	function getCommandLine() {
		$atasks_handler = &icms_getModuleHandler('autotasks', 'system');
		$config_atasks = &$atasks_handler->getConfig();
		if (($config_atasks['autotasks_helper_path'] = trim($config_atasks['autotasks_helper_path'])) != '') {
			if (substr($config_atasks['autotasks_helper_path'], -1) != '\\') {
				$config_atasks['autotasks_helper_path'] .= '\\';
			}
		}
		return (isset($_SERVER['COMSPEC'])?$_SERVER['COMSPEC']:$_SERVER['ComSpec']) . ' /C ' . str_replace( array('\\/','/\\','/'), array('/','\\','\\') , '"'.$config_atasks['autotasks_helper_path'].str_replace(array('%path%','%url%'), array(str_replace('/','\\',ICMS_ROOT_PATH.'/include/autotasks.php'),ICMS_URL.'/include/autotasks.php'),$config_atasks['autotasks_helper']).' > NUL"');
	}

	/*
	 * gets running process id
	 *
	 * @return int
	 */
	function getProcessId() {
		$rez = shell_exec('at');
		if (strstr($rez, 'There are no entries in the list.')) return -1;
		$rez = explode("\n", $rez);
		$pos = array(0 => strpos($rez[0], 'Status'),
		1 => strpos($rez[0], 'ID'),
		2 => strpos($rez[0], 'Day'),
		3 => strpos($rez[0], 'Time'),
		4 => strpos($rez[0], 'Command Line')
		);
		$count = array(count($rez), count($pos));
		$cmd_to_find = $this->getCommandLine();
		for ($i=2; $i<$count[0]; $i++) {
			$id		= (int)trim(substr($rez[$i], $pos[1], $pos[2] - $pos[1]));
			$cmd	= str_replace(array('\\/','/\\'),array('\\','\\'),substr($rez[$i], $pos[$count[1]-1]));
			if ($cmd == '"'.$cmd_to_find.'"') return $id;
			if ($cmd == $cmd_to_find) return $id;
		}
		return -2;
	}

	/**
	 *  Checks if need set new timer when automated task object was executed
	 *
	 *  @return bool
	 */
	function needStart() {
		return true;
	}

	public function &getConfigArray() {
		return array();
	}

}

?>