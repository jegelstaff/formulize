<?php
/**
 * Contains the classes for updating database tables
 *
 * @license GNU
 * @author marcan <marcan@smartfactory.ca>
 * @version $Id: databaseupdater.php 20119 2010-09-09 17:55:46Z phoenyx $
 * @link http://www.smartfactory.ca The SmartFactory
 * @package database
 */
/**
 * icms_db_legacy_updater_Table class
 *
 * Information about an individual table
 *
 * @package SmartObject
 * @author marcan <marcan@smartfactory.ca>
 * @link http://www.smartfactory.ca The SmartFactory
 */
defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

class IcmsDatabasetable extends icms_db_legacy_updater_Table {
	private $_errors;
	public function __construct($name) {
		parent::__construct($name);
		$this->_errors = icms_core_Debug::setDeprecated('icms_db_legacy_updater_Table', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

/**
 * icms_db_legacy_updater_Handler class
 *
 * Class performing the database update for the module
 *
 * @package SmartObject
 * @author marcan <marcan@smartfactory.ca>
 * @link http://www.smartfactory.ca The SmartFactory
 */
class IcmsDatabaseUpdater extends icms_db_legacy_updater_Handler {
	private $_errors;
	public function __construct() {
		parent::__construct();
		$this->_errors = icms_core_Debug::setDeprecated('icms_db_legacy_updater_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

?>