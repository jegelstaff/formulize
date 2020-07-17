<?php
/**
 * ImpressCMS AUTOTASKS
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		Administration
 * @subpackage	Autotasks
 * @since		1.2 alpha 2
 * @author		MekDrop <mekdrop@gmail.com>
 * @version		SVN: $Id: autotasks.php 21886 2011-06-27 18:17:04Z phoenyx $
 */
defined('ICMS_ROOT_PATH') || die('ImpressCMS root path not defined');

//error_reporting(E_ALL);
//ini_set('display_errors', '1');

icms_loadLanguageFile('system', 'autotasks', TRUE);

/**
 * Task objects
 * 
 * @package		Administration
 * @subpackage	Autotasks
 */
class SystemAutoTasks extends icms_ipf_Object {

	public $content = FALSE;

	/**
	 * Constructor
	 * 
	 * @param object $handler
	 */
	public function __construct(&$handler) {
		parent::__construct($handler);

		$this->quickInitVar('sat_id', XOBJ_DTYPE_INT, FALSE);
		$this->quickInitVar('sat_lastruntime', XOBJ_DTYPE_INT, FALSE, _CO_ICMS_AUTOTASKS_LASTRUNTIME, NULL, 0);
		$this->quickInitVar('sat_name', XOBJ_DTYPE_TXTBOX, TRUE, _CO_ICMS_AUTOTASKS_NAME, _CO_ICMS_AUTOTASKS_NAME_DSC);
		$this->quickInitVar('sat_code', XOBJ_DTYPE_SOURCE, TRUE, _CO_ICMS_AUTOTASKS_CODE, _CO_ICMS_AUTOTASKS_CODE_DSC);
		$this->quickInitVar('sat_repeat', XOBJ_DTYPE_INT, TRUE, _CO_ICMS_AUTOTASKS_REPEAT, _CO_ICMS_AUTOTASKS_REPEAT_DSC, 0);
		$this->quickInitVar('sat_interval', XOBJ_DTYPE_INT, TRUE, _CO_ICMS_AUTOTASKS_INTERVAL, _CO_ICMS_AUTOTASKS_INTERVAL_DSC, 24 * 60);
		$this->quickInitVar('sat_onfinish', XOBJ_DTYPE_INT, TRUE, _CO_ICMS_AUTOTASKS_ONFINISH, _CO_ICMS_AUTOTASKS_ONFINISH_DSC, 0);
		$this->quickInitVar('sat_enabled', XOBJ_DTYPE_INT, TRUE, _CO_ICMS_AUTOTASKS_ENABLED, _CO_ICMS_AUTOTASKS_ENABLED_DSC, 1);
		$this->quickInitVar('sat_type', XOBJ_DTYPE_TXTBOX, TRUE, _CO_ICMS_AUTOTASKS_TYPE, NULL, ':custom');
		$this->quickInitVar('sat_addon_id', XOBJ_DTYPE_INT, FALSE);

		$this->setControl('sat_name', 'text');
		$this->setControl('sat_onfinish', 'yesno');
		$this->setControl('sat_enabled', 'yesno');

		$this->doHideFieldFromForm('sat_addon_id');
		$this->doHideFieldFromForm('sat_type');
		$this->doHideFieldFromForm('sat_lastruntime');
	}

	/**
	 * Get the last time a task was run and format it for display
	 * @return	string
	 */
	public function getLastRunTimeForDisplay() {
		if ($this->getVar('sat_lastruntime') < 1) {
			return _CO_ICMS_AUTOTASKS_NOTYETRUNNED;
		} else {
			return formatTimestamp($this->getVar('sat_lastruntime'));
		}
	}

	/**
	 * Get the recurrence for the task and format it for display
	 * @return	string
	 */
	public function getRepeatForDisplay() {
		if ($this->getVar('sat_repeat') < 1) {
			return _CO_ICMS_AUTOTASKS_FOREVER;
		} else {
			return $this->getVar('sat_repeat');
		}
	}

	/**
	 * Get the recur interval and format it for display
	 * @return	string
	 */
	public function getIntervalForDisplay() {

		$int = $this->getVar('sat_interval');
		$day = (int) ($int / 60 / 24);
		$hou = (int) (($int - $day * 24 * 60) / 60);
		$min = (int) (($int - $day * 24 * 60) - $hou * 60);

		$ret = '';
		if ($day == 1) {
			$ret .= _DAY . ' ';
		} elseif ($day > 1) {
			$ret .= sprintf(_DAYS, $day) . ' ';
		}

		if ($hou == 1) {
			$ret .= _HOUR . ' ';
		} elseif ($hou > 1) {
			$ret .= sprintf(_HOURS, $hou) . ' ';
		}

		if ($min == 1) {
			$ret .= _MINUTE;
		} elseif ($min > 1) {
			$ret .= sprintf(_MINUTES, $min);
		}

		return trim($ret);
	}

	/**
	 * Get the autotask type
	 * 
	 * @param string $part
	 * @return	string
	 */
	public function getType($part = NULL) {
		$type = $this->getVar('sat_type');
		if ($type[0] == ':') {
			$type = substr($type, 1);
		}
		$type = explode(DIRECTORY_SEPARATOR, $type);
		if ($part === NULL) return $type;
		return $type[$part];
	}

	/**
	 * Format the type for display
	 * @return string
	 */
	public function getTypeForDisplay() {
		return constant('_CO_ICMS_AUTOTASKS_TYPE_' . strtoupper($this->getType(0)));
	}

	/**
	 * Retrieve and format the enabled status of the task
	 * @return	string
	 */
	public function getEnableForDisplay() {
		return ($this->getVar('sat_enabled')==1) ? _YES : _NO;
	}

	/**
	 * Retrieve and format for display if the task will be deleted on completion
	 * @return	string
	 */
	public function getOnFinishForDisplay() {
		return ($this->getVar('sat_onfinish')==1) ? _YES : _NO;
	}

	/**
	 * Executes code attached to event
	 *
	 * @return bool
	 */
	public function exec() {
		if (!$this->getVar('sat_enabled')) return FALSE;
		if (((int) $this->getVar('sat_lastruntime') + (int) $this->getVar('sat_interval') * 60) > time()) return FALSE;
		$code = $this->getVar('sat_code');
		ignore_user_abort(TRUE);
		if (substr($this->getVar('sat_type'), 0, 6) == 'addon/') {
			$dirname = substr($this->getVar('sat_type'), 6);
			if ($dirname == '') return FALSE;
			
			// only execute autotasks for active modules
			$module = icms::handler("icms_module")->getByDirname($dirname);
			if ($module->getVar("isactive") != 1) return FALSE;
			
			$dirname = ICMS_MODULES_PATH . DIRECTORY_SEPARATOR . $dirname;
			$dirname = $dirname . DIRECTORY_SEPARATOR . $code;
			$code = " require '" . $dirname . "';";
			$is_bug = !(@highlight_string(file_get_contents($dirname), TRUE));
		} else {
			$is_bug = !(@highlight_string('<?' . 'php '. $code . ' return TRUE; ?' . '>', TRUE));
		}
		if ($is_bug) {
			trigger_error(sprintf(_CO_ICMS_AUTOTASKS_SOURCECODE_ERROR, $code));
			return FALSE;
		}
		eval($code);
		$count = $this->getVar('sat_repeat');
		if ($count > 0) {
			if ($count == 1) {
				if ($this->getVar('sat_onfinish')) {
					// delete this task
					$this->handler->delete($this);
					return TRUE;
				} else {
					// disable this task
					$this->setVar('sat_enabled', 0);
				}
			}
			$count--;
			$this->setVar('sat_repeat', $count);
		}
		$this->setVar('sat_lastruntime', time());
		$this->handler->insert($this, TRUE);
		return TRUE;
	}

	/**
	 * Custom form generation for autotasks
	 * @see icms_ipf_Object::getForm()
	 */
	public function getForm($form_caption, $form_name, $form_action = FALSE, $submit_button_caption = _CO_ICMS_SUBMIT, $cancel_js_action = FALSE, $captcha = FALSE) {
		if ($this->getType(0)=='addon') {
			$this->doHideFieldFromForm('sat_code');
			$this->doHideFieldFromForm('sat_onfinish');
		} else {
			$this->doShowFieldOnForm('sat_code');
			$this->doShowFieldOnForm('sat_onfinish');
		}
		return parent::getForm($form_caption, $form_name, $form_action, $submit_button_caption, $cancel_js_action, $captcha);
	}

	/**
	 * Determine if the user can delete the task and display a button
	 * @return	mixed
	 */
	public function getDeleteButtonForDisplay() {
		static $controller = NULL;
		if ($this->getType(0) == 'addon') return;
		if ($controller === NULL) $controller = new icms_ipf_Controller($this->handler);
		return $controller->getDeleteItemLink($this, FALSE, TRUE, FALSE);
	}

	/**
	 * Retrieve name for display
	 * @return	string
	 */
	public function getNameForDisplay() {
		return $this->getVar('sat_name');
	}

}

/**
 * Handler for the autotask objects
 * 
 * @package		Administration
 * @subpackage	Autotasks
 */
class SystemAutotasksHandler extends icms_ipf_Handler {

	private $_use_virtual_config = FALSE;
	private $_virtual_config = array();

	/**
	 * Constructor
	 * 
	 * @param object $db	Database object
	 */
	public function __construct($db) {
		parent::__construct($db, 'autotasks', 'sat_id', 'sat_name', 'sat_code', 'system');
	}

	/**
	 * Enable virtual configuartion and set it
	 *
	 * @param	array
	 */
	public function enableVirtualConfig(&$array) {
		$this->_virtual_config = $array;
		$this->_use_virtual_config = TRUE;
	}

	/**
	 * Get virtual configuration status
	 *
	 * @return bool
	 */
	public function isVirtualConfigEnabled() {
		return $this->_use_virtual_config;
	}

	/**
	 * Disable virtual configuration
	 */
	public function disableVirtualConfig() {
		$this->_use_virtual_config = FALSE;
	}

	/**
	 * Gets selected type current events for current user
	 *
	 * @param int $ type
	 * @return Object
	 */
	public function getTasks() {
		$criteria = new icms_db_criteria_Compo();
		$criteria->setSort('sat_lastruntime');
		$criteria->setOrder('ASC');
		$criteria->add( new icms_db_criteria_Item('(sat_lastruntime + sat_interval)', time(), '<=', NULL, "%s" ));
		$criteria->add( new icms_db_criteria_Item('sat_repeat', 0, '>=', NULL, "'%s'"));
		$criteria->add( new icms_db_criteria_Item('sat_enabled', 1));
		$rez = $this->getObjects($criteria, FALSE);
		return $rez;
	}

	/**
	 * Executes events
	 *
	 * @return array
	 */
	public function execTasks() {
		$rez = array('all' => 0, 'ok' => 0);
		if (!($tasks = $this->getTasks())) return $rez;
		foreach ($tasks as $task) {
			if ($task->exec()) {
				$rez['ok']++;
			}
			$rez['all']++;
		}
		return $rez;
	}

	/**
	 * Get if current autotask handler needs execution
	 *
	 * @return TRUE
	 */
	public function needExecution() {
		return $this->getCurrentSystemHandler()->needExecution();
	}

	/**
	 * Returns if all tasks was executed to do no more php lines processing
	 *
	 * @param bool
	 */
	public function needExit() {
		return $this->getCurrentSystemHandler()->needExit();
	}

	/**
	 * Starts handler if needed
	 */
	public function startIfNeeded() {
		$system = $this->getCurrentSystemHandler();
		if ($system->needStart()) {
			if ($system->canRun()) {
				$system->start($this->getRealTasksRunningTime());
			} else {
				trigger_error("Can't start selected automated tasks handler.");
			}
		}
		unset($system);
	}

	/**
	 * Tasks are executed in some times periods but not always exatcly the same
	 * as in administration. This will get real tasks execution interval.
	 *
	 * @return int
	 */
	public function getRealTasksRunningTime() {
		$sql = 'SELECT MIN(sat_interval) INTV FROM ' . $this->db->prefix('system_autotasks') . ' WHERE sat_enabled = TRUE LIMIT 1';
		if (!$result = $this->db->query($sql)) {
			return 0;
		}
		$data = $this->db->fetchArray($result);
		$interval = (int) $data['INTV'];
		return ($interval == 0) ? strtotime('60 minutes') : $interval;
	}

	/**
	 * Get selected autotask system handler
	 *
	 * @param string system name
	 *
	 * @return AutomatedTasks
	 */
	public function getSelectedSystemHandler($name) {
		if ("$name" == '') {
			$name = 'internal';
		}
		$name = trim(strtolower($name));
		require_once $this->getSystemHandlerFileName((string) $name);
		$handler_name = 'IcmsAutoTasks' . ucfirst($name);
		if (class_exists($handler_name)) {
			$handler = new $handler_name($this);
		} else {
			trigger_error('Needed autotask handler not found!');
		}
		return $handler;
	}

	/**
	 * Gets system handler filename
	 *
	 * @param	string	name
	 * @return	string
	 */
	private function getSystemHandlerFileName($name) {
		return ICMS_PLUGINS_PATH . '/autotasks/' . $name . '.php';
	}

	/**
	 * Get system handler name from filename
	 *
	 * @param string filename
	 * @return string
	 */
	private function getSystemHandlerNameFromFileName($filename) {
		return substr($filename, strlen(ICMS_PLUGINS_PATH . '/autotasks/'), -strlen('.php'));
	}

	/**
	 * Gets autotasks settings
	 *
	 * @return Array(ConfigObjectItems)
	 */
	public function getConfig() {
		if ($this->isVirtualConfigEnabled()) {
			return $this->_virtual_config;
		}
		//$old_handler_name = get_class($handler);
		$config_handler = icms::handler('icms_config');
		$config_atasks = $config_handler->getConfigsByCat(ICMS_CONF_AUTOTASKS);
		return $config_atasks;
	}

	/**
	 * Get AutoTasks System
	 *
	 * @param bool force update handler
	 *
	 * @return AutomatedTasks
	 */
	public function getCurrentSystemHandler($forceUpdate = FALSE) {
		static $handler = FALSE;
		if ($forceUpdate || ($handler === FALSE)) {
			$config_atasks = $this->getConfig();
			$handler = $this->getSelectedSystemHandler($config_atasks['autotasks_system']);
		}
		return $handler;
	}

	/**
	 * Gets all avaible system handlers
	 *
	 * @param	bool	checkIfItIsAvaibleOnCurrentSystem
	 *
	 * @return	array
	 */
	public function getSystemHandlersList($checkIfItIsAvaibleOnCurrentSystem = TRUE) {
		static $ret = NULL;
		if ($ret === NULL) {
			$files = glob($this->getSystemHandlerFileName('*'));
			$ret = FALSE;
			foreach ($files as $file) {
				$name = (string)$this->getSystemHandlerNameFromFileName((string) $file);
				$handler = $this->getSelectedSystemHandler($name);
				if (!$handler) continue;
				if ($checkIfItIsAvaibleOnCurrentSystem && (!$handler->canRun())) {
					continue;
				}
				$ret[] = $name;
			}
		}
		sort($ret);
		return $ret;
	}
}
