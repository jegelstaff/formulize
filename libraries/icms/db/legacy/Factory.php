<?php
/**
 * ImpressCMS Database Factory Class
 *
 * @category	ICMS
 * @package		Database
 * @author      Gustavo Pilla  (aka nekro) <nekro@impresscms.org>
 * @copyright   The ImpressCMS Project <http://www.impresscms.org>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @version		SVN: $Id: Factory.php 20165 2010-09-19 13:48:17Z phoenyx $
 */

class icms_db_legacy_Factory {
	/**
	 * Constructor
	 *
	 * Makes nothing.
	 */
	protected function __construct() { /* Empty! */ }

	/**
	 * Get a reference to the only instance of database class and connects to DB
	 *
	 * if the class has not been instantiated yet, this will also take
	 * care of that
	 *
	 * @static
	 * @staticvar   object  The only instance of database class
	 * @return      object  Reference to the only instance of database class
	 */
	static public function &instance() {
		static $instance;
		if (!isset($instance)) {
			if (defined('XOOPS_DB_ALTERNATIVE') && class_exists(XOOPS_DB_ALTERNATIVE)) {
				$class = XOOPS_DB_ALTERNATIVE ;
			} elseif (!defined('XOOPS_DB_PROXY')) {
				$class = 'icms_db_legacy_' . XOOPS_DB_TYPE . '_Safe';
			} else {
				$class = 'icms_db_legacy_' . XOOPS_DB_TYPE . '_Proxy';
			}
			$instance = new $class();
			$instance->setLogger(icms_core_Logger::instance());
			$instance->setPrefix(XOOPS_DB_PREFIX);
			if (!$instance->connect()) {
				icms_loadLanguageFile('core', 'core');
				trigger_error(_CORE_DB_NOTRACEDB, E_USER_ERROR);
			}
		}
		return $instance;
	}

	/**
	 * Gets a reference to the only instance of database class. Currently
	 * only being used within the installer.
	 *
	 * @static
	 * @staticvar   object  The only instance of database class
	 * @return      object  Reference to the only instance of database class
	 */
	static public function &getDatabase() {
		static $database;
		if (!isset($database)) {
			if (!defined('XOOPS_DB_PROXY')) {
				$class = 'icms_db_legacy_' . XOOPS_DB_TYPE . '_Safe';
			} else {
				$class = 'icms_db_legacy_' . XOOPS_DB_TYPE . '_Proxy';
			}
			$database = new $class();
		}
		return $database;
	}

	/**
	 * Gets the databaseupdater object .
	 *
	 * @return	object  @link icms_db_legacy_updater_Handler
	 * @static
	 */
	static public function getDatabaseUpdater() {
		return new icms_db_legacy_updater_Handler();
	}
}