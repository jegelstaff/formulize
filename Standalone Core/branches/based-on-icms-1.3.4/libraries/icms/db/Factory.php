<?php


abstract class icms_db_Factory {
	/**
	 * PDO database adapter
	 * @var icms_db_IConnection
	 */
	static protected $pdoInstance = false;
	/**
	 * Legacy database adapter
	 * @var icms_db_legacy_Database
	 */
	static protected $xoopsInstance = false;

	/**
	 * Instanciate the PDO compatible DB adapter (if appropriate).
	 * @throws RuntimeException
	 */
	static public function pdoInstance() {
		if ( self::$pdoInstance !== false ) return self::$pdoInstance;
		if (substr(XOOPS_DB_TYPE, 0, 4) != 'pdo.') return self::$pdoInstance = null;
		if (!class_exists('PDO', false)) {
			throw new RuntimeException("PDO extension not available.");
		}
		$driver = substr(XOOPS_DB_TYPE, 4);
		$dsn = $driver . ':' . XOOPS_DB_DSN;
		$class = "icms_db_{$driver}_Connection";
		if (!class_exists($class)) {
			$class = "icms_db_Connection";
		}
		return self::$pdoInstance = new $class($dsn, XOOPS_DB_USER, XOOPS_DB_PASS);
	}
	/**
	 * Get a reference to the only instance of database class and connects to DB
	 *
	 * if the class has not been instantiated yet, this will also take
	 * care of that
	 *
	 * @static
	 * @return      object  Reference to the only instance of database class
	 */
	static public function instance() {
		if ( self::$xoopsInstance !== false ) return self::$xoopsInstance;
		$allowWebChanges = defined('XOOPS_DB_PROXY') ? false : true;
		if (substr(XOOPS_DB_TYPE, 0, 4) == 'pdo.') {
			self::$xoopsInstance = new icms_db_legacy_PdoDatabase(self::$pdoInstance, $allowWebChanges);
		} else {
			if (defined('XOOPS_DB_ALTERNATIVE') && class_exists(XOOPS_DB_ALTERNATIVE)) {
				$class = XOOPS_DB_ALTERNATIVE;
			} else {
				$class = 'icms_db_legacy_' . XOOPS_DB_TYPE;
				$class .= $allowWebChanges ? '_Safe' : '_Proxy';
			}
			self::$xoopsInstance = new $class();
			self::$xoopsInstance->setLogger(icms::$logger);
			if (!self::$xoopsInstance->connect()) {
				icms_loadLanguageFile('core', 'core');
				trigger_error(_CORE_DB_NOTRACEDB, E_USER_ERROR);
			}
		}
		self::$xoopsInstance->setPrefix(XOOPS_DB_PREFIX);
		return self::$xoopsInstance;
	}



}