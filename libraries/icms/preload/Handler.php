<?php
/**
 * ICMS Preload Handler
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Preload
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		SVN: $Id: Handler.php 20641 2011-01-04 02:53:47Z skenow $
 */

defined('ICMS_ROOT_PATH') or die('ImpressCMS root path not defined');

/**
 * icms_preload_Handler
 *
 * Class handling preload events automatically detect from the files in ICMS_PRELOAD_PATH
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Preload
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 */
class icms_preload_Handler {

	/**
	 * @var array $_preloadFilesArray array containing a list of all preload files in ICMS_PRELOAD_PATH
	 */
	private $_preloadFilesArray = array();

	/**
	 * @var array $_preloadEventsArray array containing a list of all events for all preload file, indexed by event name and sorted by order ox execution
	 */
	private $_preloadEventsArray = array();

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct() {
		$preloadFilesArray = str_replace('.php', '', icms_core_Filesystem::getFileList(ICMS_PRELOAD_PATH, '', array('php')));
		foreach ($preloadFilesArray as $filename) {
			// exclude index.html
			if (!in_array($this->getClassName($filename), get_declared_classes())) {
				$this->_preloadFilesArray[] = $filename;
				$this->addPreloadEvents($filename);
			}
		}

		// add ondemand preload
		global $icmsOnDemandPreload;
		if (isset($icmsOnDemandPreload) && count($icmsOnDemandPreload) > 0) {
			foreach ($icmsOnDemandPreload as $onDemandPreload) {
				$this->_preloadFilesArray[] = $onDemandPreload['filename'];
				$this->addPreloadEvents($onDemandPreload['filename'], $onDemandPreload['module']);
			}
		}
	}

	/**
	 * Add the events defined in filename
	 *
	 * @param string $filename
	 */
	public function addPreloadEvents($filename, $module = false) {
		if ($module) {
			$filepath = ICMS_ROOT_PATH . "/modules/$module/preload/$filename.php";
		} else {
			$filepath = ICMS_PRELOAD_PATH . "/$filename.php";
		}
		include_once $filepath;

		$classname = $this->getClassName($filename);

		if (in_array($classname, get_declared_classes())) {
			$preloadItem = new $classname();

			$class_methods = get_class_methods($classname);
			foreach ($class_methods as $method) {
				if (strpos($method, 'event') === 0) {
					$preload_event = strtolower(str_replace('event', '', $method));

					$callback = array( $preloadItem, $method );
					icms_Event::attach('icms', $preload_event, $callback);
					/*
					$preload_event_weight_define_name = strtoupper($classname) . '_' . strtoupper($preload_event);
					if (defined($preload_event_weight_define_name)) {
						$preload_event_weight = constant($preload_event_weight_define_name);
						$this->_preloadEventsArray[$preload_event][$preload_event_weight] = $preload_event_array;
					} else {
						$this->_preloadEventsArray[$preload_event][] = $preload_event_array;
					}*/
				}
			}
		}
	}

	/**
	 * Access the only instance of this class
	 *
	 * @static
	 * @staticvar   object
	 * @return	object
	 *
	 */
	static public function &getInstance() {
		static $instance;
		if (!isset($instance)) {
			$instance = new icms_preload_Handler();
		}
		return $instance;
	}

	/**
	 * Triggers a specific event on all the libraries
	 *
	 * Here are the currently supported events:
	 * - startCoreBoot : this event is triggered at the  start of the core booting process (start  of include/common.php)
	 * - finishCoreBoot : this event is triggered at the end of the core booting process (end of include/common.php)
	 * - adminHeader : this event is triggered when calling icms_cp_header() and is used to output content in the head section of the admin side
	 * - beforeFooter : this event is triggered when include/footer.php is called, at the begining of the file
	 * - startOutputInit : this event is triggered when starting to output the content, in include/header.php after instantiation of $xoopsTpl
	 *
	 * @param $event string name of the event to trigger
	 * @param $array mixed container to pass any arguments to be used by the library
	 *
	 * @return	TRUE if successful, FALSE if not
	 */
	public function triggerEvent($event, $array = array()) {
		$event = strtolower($event);
		icms_Event::trigger('icms', $event, null, $array);
	}

	/**
	 * Construct the name of the class based on the filename
	 *
	 * @param $filename string filename where the class is located
	 *
	 * @return	string name of the class
	 *
	 */
	public function getClassName($filename) {
		return 'IcmsPreload' . ucfirst(str_replace('.php', '', $filename));
	}

}

