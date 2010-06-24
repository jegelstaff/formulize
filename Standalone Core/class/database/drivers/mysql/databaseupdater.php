<?php
/**
 * Contains the classes for updating database tables
 *
 * @license GNU
 * @author marcan <marcan@smartfactory.ca>
 * @version $Id: databaseupdater.php 8662 2009-05-01 09:04:30Z pesianstranger $
 * @link http://www.smartfactory.ca The SmartFactory
 * @package SmartObject
 */

if (!defined("ICMS_ROOT_PATH")) {
	die("ImpressCMS root path not defined");
}

/**
 * base class
 */
include_once ICMS_ROOT_PATH."/class/database/databaseupdater.php";

class IcmsMysqlDatabasetable extends IcmsDatabasetable {
	
}

class IcmsMysqlDatabaseupdater extends IcmsDatabaseupdater {

}
?>