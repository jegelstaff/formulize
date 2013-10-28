<?php
/**
 * Contains the classes for updating database tables
 *
 * @license GNU
 * @author marcan <marcan@smartfactory.ca>
 * @version $Id: databaseupdater.php 20322 2010-11-04 03:57:45Z skenow $
 * @link http://www.smartfactory.ca The SmartFactory
 * @package SmartObject
 */

if (!defined("ICMS_ROOT_PATH")) {
	die("ImpressCMS root path not defined");
}

/**
 * base class
 */

class IcmsMysqlDatabasetable extends icms_db_legacy_updater_Table {
	private $_errors;
	public function __construct($name) {
		parent::__construct($name);
		$this->_errors = icms_core_Debug::setDeprecated('icms_db_legacy_updater_Table', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

class IcmsMysqlDatabaseupdater extends icms_db_legacy_updater_Handler {
	private $_errors;
	public function __construct() {
		parent::__construct();
		$this->_errors = icms_core_Debug::setDeprecated('icms_db_legacy_updater_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>