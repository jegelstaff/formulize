<?php
/**
* ImpressCMS AUTOTASKSs
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		core
* @since		1.2 alpha 2
* @author		MekDrop <mekdrop@gmail.com>
*/
if(!defined('ICMS_ROOT_PATH')) die('ImpressCMS root path not defined');

//error_reporting(E_ALL);
//ini_set('display_errors', '1');

include_once ICMS_ROOT_PATH . "/kernel/icmspersistableobject.php";

icms_loadLanguageFile('system', 'autotasks', true);

class SystemAutoTasks extends IcmsPersistableObject {

	public $content = false;

	function __construct(&$handler) {
		$this->IcmsPersistableObject($handler);

		$this->quickInitVar('sat_id', XOBJ_DTYPE_INT, false);
		$this->quickInitVar('sat_lastruntime', XOBJ_DTYPE_INT, false, _CO_ICMS_AUTOTASKS_LASTRUNTIME, null, 0);
		$this->quickInitVar('sat_name', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_AUTOTASKS_NAME, _CO_ICMS_AUTOTASKS_NAME_DSC);
		$this->quickInitVar('sat_code', XOBJ_DTYPE_SOURCE, true, _CO_ICMS_AUTOTASKS_CODE, _CO_ICMS_AUTOTASKS_CODE_DSC);
		$this->quickInitVar('sat_repeat', XOBJ_DTYPE_INT, true, _CO_ICMS_AUTOTASKS_REPEAT, _CO_ICMS_AUTOTASKS_REPEAT_DSC, 0);
		$this->quickInitVar('sat_interval', XOBJ_DTYPE_INT, true, _CO_ICMS_AUTOTASKS_INTERVAL, _CO_ICMS_AUTOTASKS_INTERVAL_DSC, 24 * 60);
		$this->quickInitVar('sat_onfinish', XOBJ_DTYPE_INT, true, _CO_ICMS_AUTOTASKS_ONFINISH, _CO_ICMS_AUTOTASKS_ONFINISH_DSC, 0);
		$this->quickInitVar('sat_enabled', XOBJ_DTYPE_INT, true, _CO_ICMS_AUTOTASKS_ENABLED, _CO_ICMS_AUTOTASKS_ENABLED_DSC, 1);
		$this->quickInitVar('sat_type', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_AUTOTASKS_TYPE, null, ':custom');
		$this->quickInitVar('sat_addon_id', XOBJ_DTYPE_INT, false);

		$this->setControl('sat_name', 'text');
		$this->setControl('sat_onfinish', 'yesno');
		$this->setControl('sat_enabled', 'yesno');

		$this->doHideFieldFromForm('sat_addon_id');
		$this->doHideFieldFromForm('sat_type');
		$this->doHideFieldFromForm('sat_lastruntime');
	}

	function getLastRunTimeForDisplay()
	{
		if($this->getVar('sat_lastruntime') < 1)
		{
			return _CO_ICMS_AUTOTASKS_NOTYETRUNNED;
		}
		else
		{
			return formatTimestamp($this->getVar('sat_lastruntime'));
		}
	}

	function getRepeatForDisplay()
	{
		if($this->getVar('sat_repeat') < 1)
		{
			return _CO_ICMS_AUTOTASKS_FOREVER;
		}
		else
		{
			return $this->getVar('sat_repeat');
		}
	}

	function getIntervalForDisplay() {

		$int = $this->getVar('sat_interval');
		$day = (int)($int / 60 / 24);
		$hou = (int)(($int - $day * 24 * 60) / 60);
		$min = (int)(($int - $day * 24 * 60) - $hou * 60);

		$ret = '';
		if($day == 1)
		{
			$ret .= _DAY.' ';
		}
		elseif($day > 1)
		{
			$ret .= sprintf(_DAYS,$day).' ';
		}

		if($hou == 1)
		{
			$ret .= _HOUR.' ';
		}
		elseif($hou > 1)
		{
			$ret .= sprintf(_HOURS,$hou).' ';
		}

		if($min == 1)
		{
			$ret .= _MINUTE;
		}
		elseif($min > 1)
		{
			$ret .= sprintf(_MINUTES,$min);
		}

		return trim($ret);
	}

	function getType($part=null)
	{
		$type = $this->getVar('sat_type');
		if($type{0} == ':')
		{
			$type = substr($type, 1);
		}
		$type = explode('/',$type);
		if($part===null) return $type;
		return $type[$part];
	}

	function getTypeForDisplay()
	{
		return constant('_CO_ICMS_AUTOTASKS_TYPE_'.strtoupper($this->getType(0)));
	}

	function getEnableForDisplay()
	{
		return ($this->getVar('sat_enabled')==1)?_YES:_NO;
	}

	function getOnFinishForDisplay()
	{
		return ($this->getVar('sat_onfinish')==1)?_YES:_NO;
	}


	/**
	* Executes code attached to event
	*
	* @return bool
	*/
	function exec()
	{
		if(!$this->getVar('sat_enabled')) return false;
		if(((int)$this->getVar('sat_lastruntime') + (int)$this->getVar('sat_interval') * 60) > time()) return false;
		$code = $this->getVar('sat_code');
		ignore_user_abort(true);
		if(substr($this->getVar('sat_type'), 0, 6) == 'addon/')
		{
			$module = substr($this->getVar('sat_type'), 6);
			if($module == '') return false;
			$module = ICMS_MODULES_PATH.'/'.$module;
			$module = $module.'/'.$code;
			$code = ' require ("'.$module.'");';;
			$is_bug = !(@highlight_string(file_get_contents($module), true));
		} else {
			$is_bug = !(@highlight_string('<?'.'php '. $code.' return true; ?'.'>', true));
		}
		if($is_bug) {
			trigger_error(sprintf(_CO_ICMS_AUTOTASKS_SOURCECODE_ERROR, $code));
			return false;
		}
		eval($code);
		$count = $this->getVar('sat_repeat');
		if($count > 0)
		{
			if($count == 1)
			{
				if($this->getVar('sat_onfinish'))
				{
					// delete this task
					$this->handler->delete($this);
					return true;
				}
				else
				{
					// disable this task
					$this->setVar('sat_enabled', 0);
				}
			}
			$count--;
			$this->setVar('sat_repeat', $count);
		}
		$this->setVar('sat_lastruntime', time());
		$this->handler->insert($this, true);
		return true;
	}

	function getForm($form_caption, $form_name, $form_action=false, $submit_button_caption = _CO_ICMS_SUBMIT, $cancel_js_action=false, $captcha=false)
	{
		if($this->getType(0)=='addon')
		{
			$this->doHideFieldFromForm('sat_code');
			$this->doHideFieldFromForm('sat_onfinish');
		}
		else
		{
			$this->doShowFieldOnForm('sat_code');
			$this->doShowFieldOnForm('sat_onfinish');
		}
		return parent::getForm($form_caption, $form_name, $form_action, $submit_button_caption, $cancel_js_action, $captcha);
	}

	function getDeleteButtonForDisplay()
	{
		static $controller = null;
		if($this->getType(0) == 'addon') return;
		if($controller===null) $controller = new IcmsPersistableController($this->handler);
		return $controller->getDeleteItemLink($this, false, true, false);
	}

	function getNameForDisplay()
	{
		return $this->getVar('sat_name');
	}

}

class SystemAutotasksHandler extends IcmsPersistableObjectHandler
{

	private $_use_virtual_config = false;
	private $_virtual_config = array();

	function SystemAutotasksHandler($db)
	{
		$this->IcmsPersistableObjectHandler($db, 'autotasks', 'sat_id', 'sat_name', 'sat_code', 'system');
	}

	/**
	* Enable virtual configuartion and set it
	*
	* @param	array
	*/
	function enableVirtualConfig(&$array)
	{
		$this->_virtual_config = $array;
		$this->_use_virtual_config = true;
	}

	/**
	* Get virtual configuration status
	*
	* @return bool
	*/
	function isVirtualConfigEnabled()
	{
		return $this->_use_virtual_config;
	}

	/**
	* Disable virtual configuration
	*/
	function disableVirtualConfig()
	{
		$this->_use_virtual_config = false;
	}

	/**
	* Gets selected type current events for current user
	*
	* @param int $ type
	* @return Object
	*/
	function getTasks()
	{
		$criteria = new CriteriaCompo();
		$criteria->setSort('sat_lastruntime');
		$criteria->setOrder('ASC');
		$criteria->add( new Criteria('(sat_lastruntime + sat_interval)', time(), '<=', null, "%s" ));
		$criteria->add( new Criteria('sat_repeat', 0, '>=', null, "'%s'"));
		$criteria->add( new Criteria('sat_enabled', 1));
		$rez = $this->getObjects($criteria, false);
		return $rez;
	}

	/**
	* Executes events
	*
	* @return array
	*/
	function execTasks()
	{
		$rez = array('all' => 0, 'ok' => 0);
		if(!($tasks = $this->getTasks())) return $rez;
		foreach($tasks as $task)
		{
			if($task->exec())
			{
				$rez['ok']++;
			}
			$rez['all']++;
		}
		return $rez;
	}

	/**
	* Get if current autotask handler needs execution
	*
	* @return true
	*/
	function needExecution()
	{
		return $this->getCurrentSystemHandler()->needExecution();
	}

	/**
	* Returns if all tasks was executed to do no more php lines processing
	*
	* @param bool
	*/
	public function needExit()
	{
		return $this->getCurrentSystemHandler()->needExit();
	}

	/**
	* Starts handler if needed
	*/
	function startIfNeeded()
	{
		$system = $this->getCurrentSystemHandler();
		if($system->needStart())
		{
			if($system->canRun())
			{
				$system->start($this->getRealTasksRunningTime());
			}
			else
			{
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
	function getRealTasksRunningTime()
	{
		$sql = 'SELECT MIN(sat_interval) INTV FROM '.$this->db->prefix('system_autotasks').' WHERE sat_enabled = true LIMIT 1';
		if(!$result = $this->db->query($sql))
		{
			return 0;
		}
		$data = $this->db->fetchArray($result);
		$interval = (int)$data['INTV'];
		return ($interval==0)?strtotime('60 minutes'):$interval;
	}

	/**
	* Get selected autotask system handler
	*
	* @param string system name
	*
	* @return AutomatedTasks
	*/
	function getSelectedSystemHandler($name)
	{
		if("$name" == '')
		{
			$name = 'internal';
		}
		$name = trim(strtolower($name));
		require_once $this->getSystemHandlerFileName((string)$name);
		$handler_name = 'IcmsAutoTasks'.ucfirst($name);
		if(class_exists($handler_name))
		{
			$handler = new $handler_name($this);
		}
		else
		{
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
	private function getSystemHandlerFileName($name)
	{
		return ICMS_PLUGINS_PATH.'/autotasks/'.$name.'.php';
	}

	/**
	* Get system handler name from filename
	*
	* @param string filename
	* @return string
	*/
	private function getSystemHandlerNameFromFileName($filename)
	{
		return substr($filename, strlen(ICMS_PLUGINS_PATH.'/autotasks/'), -strlen('.php'));
	}

	/**
	* Gets autotasks settings
	*
	* @return Array(ConfigObjectItems)
	*/
	function getConfig()
	{
		if($this->isVirtualConfigEnabled())
		{
			return $this->_virtual_config;
		}
		//$old_handler_name = get_class($handler);
		$config_handler = xoops_gethandler('config');
		$config_atasks = $config_handler->getConfigsByCat(IM_CONF_AUTOTASKS);
		return $config_atasks;
	}

	/**
	* Get AutoTasks System
	*
	* @param bool force update handler
	*
	* @return AutomatedTasks
	*/
	function getCurrentSystemHandler($forceUpdate = false)
	{
		static $handler = false;
		if($forceUpdate || ($handler === false))
		{
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
	function getSystemHandlersList($checkIfItIsAvaibleOnCurrentSystem = true)
	{
		static $ret = null;
		if($ret == null)
		{
			$files = glob($this->getSystemHandlerFileName('*'));
			$ret = false;
			foreach($files as $file)
			{
				$name = (string)$this->getSystemHandlerNameFromFileName((string)$file);
				$handler = $this->getSelectedSystemHandler($name);
				if(!$handler) continue;
				if($checkIfItIsAvaibleOnCurrentSystem && (!$handler->canRun()))
				{
					continue;
				}
				$ret[] = $name;
			}
		}
		sort($ret);
		return $ret;
	}
}
?>