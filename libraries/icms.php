<?php
/**
 * ICMS Services manager class definition
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		icms
 * @subpackage	icms
 * @since		1.3
 * @version		SVN: $Id: Kernel.php 19775 2010-07-11 18:54:25Z malanciault $
 * @internal	This class should normally be "icms_Kernel", marcan and I agreed on calling it "icms"
 * @internal	for convenience, as we are not targetting php 5.3+ yet
 */

/**
 * ICMS Kernel / Services manager
 *
 * The icms services manager handles the core bootstrap process, and paths/urls generation.
 * Global services are made available as static properties of this class.
 *
 * @category	ICMS
 * @package		icms
 * @subpackage	icms
 * @since 		1.3
 */
abstract class icms {
	/**
	 * Preload handler
	 * @var icms_preload_Handler
	 */
	static public $preload;
	/**
	 * Security service
	 * @var icms_core_Security
	 */
	static public $security;
	/**
	 * Logger
	 * @var icms_core_Logger
	 */
	static public $logger;
	/**
	 * Database connection
	 * @var icms_db_IConnection
	 */
	static public $db;
	/**
	 * Legacy database connection
	 * @var icms_db_legacy_Database
	 */
	static public $xoopsDB;
	/**
	 * Configuration service
	 * @var icms_config_Handler
	 */
	static public $config;
	/**
	 * Session service
	 * @var icms_core_Session
	 */
	static public $session;
	/**
	 * Current user
	 * @var icms_member_user_Object
	 */
	static public $user;
	/**
	 * Current module / application
	 * @var icms_module_Object
	 */
	static public $module;
	/**
	 * Registered services definition
	 * @var array
	 */
	static public $services = array(
		"boot" => array(
			'security'	=> array(array('icms_core_Security', 'service'), array()),
			"logger"	=> array(array("icms_core_Logger", 'instance'), array()),
			"db"		=> array(array('icms_db_Factory', 'pdoInstance'), array()),
			"xoopsDB"	=> array(array('icms_db_Factory', 'instance'), array()),
			'config'	=> array(array('icms_config_Handler', 'service'), array()),
			'session'	=> array(array('icms_core_Session', 'service'), array()),
		),
		"optional" => array(),
	);

	/**
	 * ImpressCMS paths locations
	 *
	 * @var array
	 */
	static public $paths = array(
		'www' => array(), 'modules' => array(), 'themes' => array(),
	);

	/** @var array */
	static public $urls = false;

	/**
	 * array of handlers
	 * @var array
	 */
	static protected $handlers;

	/**
	 * Initialize ImpressCMS before bootstrap
	 */
	static public function setup() {
		self::$paths['www']		= array(ICMS_ROOT_PATH, ICMS_URL);
		self::$paths['modules']	= array(ICMS_ROOT_PATH . '/modules', ICMS_URL . '/modules');
		self::$paths['themes']	= array(ICMS_THEME_PATH, ICMS_THEME_URL);
		self::buildRelevantUrls();
		// Initialize the autoloader
		require_once dirname(__FILE__ ) . '/icms/Autoloader.php';
		icms_Autoloader::setup();
		register_shutdown_function(array(__CLASS__, 'shutdown'));
	}

	/**
	 * Launch bootstrap and instanciate global services
	 * @return void
	 */
	static public function boot() {
		// We just hardcode the preload first, as we need to trigger an event
		self::$preload = icms_preload_Handler::getInstance();
		self::$preload->triggerEvent('startCoreBoot');

		foreach (self::$services['boot'] as $name => $definition) {
			list($factory, $args) = $definition;
			self::loadService($name, $factory, $args);
		}
		//Cant do this here until common.php 100% refactored
		//self::$preload->triggerEvent('finishCoreBoot');
	}

	/**
	 * Instanciate the specified service
	 * @param string $name
	 * @param mixed $factory
	 * @param array $args
	 * @return object
	 */
	static public function loadService($name, $factory, $args = array()) {
			self::$$name = self::create($factory, $args);
			icms_Event::trigger('icms', 'loadService', null, array('name' => $name, 'service' => self::$$name));
			icms_Event::trigger('icms', 'loadService-' . $name, null, array('name' => $name, 'service' => self::$$name));
	}

	/**
	 * Register module class repositories and load module service
	 *
	 * The system module is not excluded from getObjects to make sure it's already cached for later
	 * use throughout the system. This function is one of the first functions called in the boot
	 * process so it's the best place to cache the modules.
	 * IPF based modules are definied in their own namespace.
	 */
	static public function launchModule() {
		$module_handler = icms::handler("icms_module");
		$modules = $module_handler->getObjects();
		foreach ($modules as $module) $module->registerClassPath(TRUE);

		$isAdmin = (defined('ICMS_IN_ADMIN') && (int)ICMS_IN_ADMIN);
		self::loadService('module', array('icms_module_Handler', 'service'), array($isAdmin));
	}

	static public function shutdown() {
		// Ensure the session service can write data before the DB connection is closed
		if (session_id()) session_write_close();
		// Ensure the logger can decorate output before objects are destroyed
		while (ob_get_level()) {
			ob_end_flush();
		}
	}

	/**
	 * Creates an object instance from an object definition.
	 * The factory parameter can be:
	 * - A fully qualified class name starting with '\': \MyClass or on PHP 5.3+ \ns\sub\MyClass
	 * - A valid PHP callback
	 *
	 * @param mixed $factory
	 * @param array $args Factory/Constructor arguments
	 * @return object
	 */
	static public function create($factory, $args = array()) {
		if (is_string($factory) && substr($factory, 0, 1) == '\\') {	// Class name
			$class = substr($factory, 1);
			if (!isset($args)) {
				$instance = new $class();
			} else {
				$reflection = new ReflectionClass($class);
				$instance = $reflection->newInstanceArgs($args);
			}
		} else {
			$instance = call_user_func_array($factory, $args);
		}
		return $instance;
	}

	/**
	 * Convert a ImpressCMS path to a physical one
	 * @param	string	$url URL string to convert to a physical path
	 * @param 	boolean	$virtual
	 * @return 	string
	 */
	static public function path($url, $virtual = false) {
		$path = '';
		@list($root, $path) = explode('/', $url, 2);
		if (!isset(self::$paths[$root])) {
			list($root, $path) = array('www', $url);
		}
		if (!$virtual) {
			// Returns a physical path
			return self::$paths[$root][0] . '/' . $path;
		}
		return !isset(self::$paths[$root][1]) ? '' : (self::$paths[$root][1] . '/' . $path );
	}

	/**
	 * Convert a ImpressCMS path to an URL
	 * @param 	string	$url
	 * @return 	string
	 */
	static public function url($url) {
		return (false !== strpos($url, '://' ) ? $url : self::path($url, true ));
	}

	/**
	 * Build an URL with the specified request params
	 * @param 	string 	$url
	 * @param 	array	$params
	 * @return 	string
	 */
	static public function buildUrl($url, $params = array()) {
		if ($url == '.') {
			$url = $_SERVER['REQUEST_URI'];
		}
		$split = explode('?', $url);
		if (count($split) > 1) {
			list($url, $query) = $split;
			parse_str($query, $query);
			$params = array_merge($query, $params);
		}
		if (!empty($params)) {
			foreach ($params as $k => $v) {
				$params[$k] = $k . '=' . rawurlencode($v);
			}
			$url .= '?' . implode('&', $params);
		}
		return $url;
	}

	/**
	 * Gets the handler for a class
	 *
	 * @param string  $name  The name of the handler to get
	 * @param bool  $optional	Is the handler optional?
	 * @return		object		$inst		The instance of the object that was created
	 */
	static public function &handler($name, $optional = false ) {
		if (!isset(self::$handlers[$name])) {
			$class = $name . "Handler";
			if (!class_exists($class)) {
				$class = $name . "_Handler";
				if (!class_exists($class)) {
					// Try old style handler loading (should be removed later, in favor of the
					// lookup table present in xoops_gethandler)
					$lower = strtolower(trim($name));
					if (file_exists($hnd_file = ICMS_ROOT_PATH.'/kernel/' . $lower . '.php')) {
						require_once $hnd_file;
					} elseif (file_exists($hnd_file = ICMS_ROOT_PATH.'/class/' . $lower . '.php')) {
						require_once $hnd_file;
					}
					if (!class_exists($class = 'Xoops' . ucfirst($lower) . 'Handler', false)) {
						if (!class_exists($class = 'Icms' . ucfirst($lower) . 'Handler', false)) {
							// Not found at all
							$class = false;
						}
					}
				}
			}
			self::$handlers[$name] = $class ? new $class(self::$xoopsDB) : false;
		}
		if (!self::$handlers[$name] && !$optional) {
			//trigger_error(sprintf("Handler <b>%s</b> does not exist", $name), E_USER_ERROR);
			throw new RuntimeException(sprintf("Handler <b>%s</b> does not exist", $name));
		}
		return self::$handlers[$name];
	}

	/**
	 * Build URLs for global use throughout the application
	 * @return 	array
	 */
	static protected function buildRelevantUrls() {
		if (!self::$urls) {
			$http = strpos(ICMS_URL, "https://") === false
				? "http://"
				: "https://";
			$phpself = $_SERVER['PHP_SELF'];
			$httphost = $_SERVER['HTTP_HOST'];
			$querystring = $_SERVER['QUERY_STRING'];
			if ($querystring != '' ) {
				$querystring = '?' . $querystring;
			}
			$currenturl = $http . $httphost . $phpself . $querystring;
			self::$urls = array();
			self::$urls['http'] = $http;
			self::$urls['httphost'] = $httphost;
			self::$urls['phpself'] = $phpself;
			self::$urls['querystring'] = $querystring;
			self::$urls['full_phpself'] = $http . $httphost . $phpself;
			self::$urls['full'] = $currenturl;

			$previouspage = '';
			if (array_key_exists('HTTP_REFERER', $_SERVER) && isset($_SERVER['HTTP_REFERER'])) {
				self::$urls['previouspage'] = $_SERVER['HTTP_REFERER'];
			}
			//self::$urls['isHomePage'] = (ICMS_URL . "/index.php") == ($http . $httphost . $phpself);
		}
		return self::$urls;
	}
}