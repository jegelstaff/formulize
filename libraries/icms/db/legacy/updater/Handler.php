<?php
/**
 * Contains the classes for updating database tables
 *
 * @license GNU
 * @author marcan <marcan@smartfactory.ca>
 * @version $Id: Handler.php 20427 2010-11-21 00:06:11Z skenow $
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

/**
 * Include the language constants for the icms_db_legacy_updater_Handler
 */
global $icmsConfigPersona;
icms_loadLanguageFile('core', 'databaseupdater');

/**
 * icms_db_legacy_updater_Handler class
 *
 * Class performing the database update for the module
 *
 * @package SmartObject
 * @author marcan <marcan@smartfactory.ca>
 * @link http://www.smartfactory.ca The SmartFactory
 */
class icms_db_legacy_updater_Handler {

	var $_dbTypesArray;

	/**
	 * xoopsDB database object
	 *
	 * @var @link XoopsDatabase object
	 */
	var $_db;
	var $db;

	/**
	 *
	 * @var array of messages
	 */
	var $_messages = array();

	function __construct() {
		// backward compat
		$this->_db = icms::$xoopsDB;
		$this->db = icms::$xoopsDB;

		$this->_dbTypesArray[XOBJ_DTYPE_TXTBOX] = 'varchar(255)';
		$this->_dbTypesArray[XOBJ_DTYPE_TXTAREA] = 'text';
		$this->_dbTypesArray[XOBJ_DTYPE_INT] = 'int(11)';
		$this->_dbTypesArray[XOBJ_DTYPE_URL] = 'varchar(255)';
		$this->_dbTypesArray[XOBJ_DTYPE_EMAIL] = 'varchar(255)';
		$this->_dbTypesArray[XOBJ_DTYPE_ARRAY] = 'text';
		$this->_dbTypesArray[XOBJ_DTYPE_OTHER] = 'text';
		$this->_dbTypesArray[XOBJ_DTYPE_SOURCE] = 'text';
		$this->_dbTypesArray[XOBJ_DTYPE_STIME] = 'int(11)';
		$this->_dbTypesArray[XOBJ_DTYPE_MTIME] = 'int(11)';
		$this->_dbTypesArray[XOBJ_DTYPE_LTIME] = 'int(11)';
		$this->_dbTypesArray[XOBJ_DTYPE_SIMPLE_ARRAY] = 'text';
		$this->_dbTypesArray[XOBJ_DTYPE_CURRENCY] = 'text';
		$this->_dbTypesArray[XOBJ_DTYPE_FLOAT] = 'float';
		$this->_dbTypesArray[XOBJ_DTYPE_TIME_ONLY] = 'int(11)';
		$this->_dbTypesArray[XOBJ_DTYPE_URLLINK] = 'int(11)';
		$this->_dbTypesArray[XOBJ_DTYPE_FILE] = 'int(11)';
		$this->_dbTypesArray[XOBJ_DTYPE_IMAGE] = 'varchar(255)';
	}

	/**
	 * Use to execute a general query
	 *
	 * @param string $query query that will be executed
	 * @param string $goodmsg message displayed on success
	 * @param string $badmsg message displayed on error
	 * @param bool 	$force force the query even in a GET process
	 *
	 * @return bool true if success, false if an error occured
	 *
	 */
	function runQuery($query, $goodmsg = "", $badmsg = "", $force = false) {
		if ($force) {
			$ret = $this->_db->queryF($query);
		} else {
			$ret = $this->_db->query($query);
		}

		if (!$ret) {
			$this->_messages[] =  "&nbsp;&nbsp;$badmsg";
			return false;
		} else {
			$this->_messages[] =  "&nbsp;&nbsp;$goodmsg";
			return true;
		}
	}

	/**
	 * Use to rename a table
	 *
	 * @param string $from name of the table to rename
	 * @param string $to new name of the renamed table
	 * @param bool 	$force force the query even in a GET process
	 *
	 * @return bool true if success, false if an error occured
	 */
	function renameTable($from, $to, $force=false) {
		$from = $this->_db->prefix($from);
		$to = $this->_db->prefix($to);
		$query = sprintf("ALTER TABLE %s RENAME %s", $from, $to);
		if ($force) {
			$ret = $this->_db->queryF($query);
		} else {
			$ret = $this->_db->query($query);
		}
		if (!$ret) {
			$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_RENAME_TABLE_ERR, $from);
			return false;
		} else {
			$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_RENAME_TABLE, $from, $to);
			return true;
		}
	}

	/**
	 * Use to update a table
	 *
	 * @param object $table {@link icms_db_legacy_updater_Table} that will be updated
	 * @param bool	$force force the query even in a GET process
	 *
	 * @see icms_db_legacy_updater_Table
	 *
	 * @return bool true if success, false if an error occured
	 */
	function updateTable($table, $force=false) {
		$ret = true;
		$table->force = $force;

		// If table has a structure, create the table
		if ($table->getStructure()) {
			$ret = $table->createTable() && $ret;
		}
		// If table is flag for drop, drop it
		if ($table->_flagForDrop) {
			$ret = $table->dropTable() && $ret;
		}
		// If table has data, insert it
		if ($table->getData()) {
			$ret = $table->addData() && $ret;
		}
		// If table has new fields to be added, add them
		if ($table->getNewFields()) {
			$ret = $table->addNewFields() && $ret;
		}
		// If table has altered field, alter the table
		if ($table->getAlteredFields()) {
			$ret = $table->alterTable() && $ret;
		}
		// If table has droped field, alter the table
		if ($table->getDropedFields()) {
			$ret = $table->dropFields() && $ret;
		}
		// If table has updateAll items, update the table
		if ($table->getUpdateAll()) {
			$ret = $table->updateAll() && $ret;
		}
		return $ret;
	}

	/**
	 * Upgrade automaticaly an item of a module
	 *
	 * Note that currently, $item needs to represent the name of an object derived
	 * from SmartObject, for example, $item == 'invoice' wich will represent $dirnameInvoice
	 * for example SmartbillingInvoice which extends SmartObject class
	 *
	 * @param string $dirname dirname of the module
	 * @param mixed $item name or array of names of the item to upgrade
	 */
	function automaticUpgrade($dirname, $item) {
		if (is_array($item)) {
			foreach($item as $v) {
				$this->upgradeObjectItem($dirname, $v);
			}
		} else {
			$this->upgradeObjectItem($dirname, $item);
		}
	}

	/**
	 * Get the type of the field based on the info of the var
	 *
	 * @param array $var array containing information about the var
	 * @return string type of the field
	 */
	function getFieldTypeFromVar($var) {
		$ret = isset($this->_dbTypesArray[$var['data_type']]) ? $this->_dbTypesArray[$var['data_type']] : 'text';
		return $ret;
	}

	/**
	 * Get the default value based on the info of the var
	 *
	 * @param array $var array containing information about the var
	 * @param bool $key TRUE if the var is the primary key
	 * @return string default value
	 */
	function getFieldDefaultFromVar($var, $key = false) {
		if (in_array($var['data_type'], array(
				XOBJ_DTYPE_TXTAREA,
				XOBJ_DTYPE_SOURCE,
				XOBJ_DTYPE_OTHER,
				XOBJ_DTYPE_SIMPLE_ARRAY,
				XOBJ_DTYPE_CURRENCY,
				XOBJ_DTYPE_ARRAY,
		))) {
			return 'nodefault';
		} elseif ($var['value']) {
			return $var['value'];
		} else {
			if (in_array($var['data_type'], array(
				XOBJ_DTYPE_INT,
				XOBJ_DTYPE_STIME,
				XOBJ_DTYPE_MTIME,
				XOBJ_DTYPE_LTIME,
				XOBJ_DTYPE_TIME_ONLY,
				XOBJ_DTYPE_URLLINK,
				XOBJ_DTYPE_FILE,
				XOBJ_DTYPE_FLOAT,
			))) {
				return '0';
			} else {
				return '';
			}
		}
	}

	/*
	 * Upgrades the object
	 * @param string $dirname
	 */
	function upgradeObjectItem($dirname, $item) {
		$module_handler = icms_getModuleHandler($item, $dirname);
		if (!$module_handler) {
			return false;
		}

		$table = new icms_db_legacy_updater_Table($dirname . '_' . $item);
		$object = $module_handler->create();
		$objectVars = $object->getVars();

		if (!$table->exists()) {
			// table was never created, let's do it
			$structure = "";
			foreach($objectVars as $key=>$var) {
				if ($var['persistent']) {
					$type = $this->getFieldTypeFromVar($var);
					if ($key == $module_handler->keyName) {
						$extra = "auto_increment";
					} else {
						$default =  $this->getFieldDefaultFromVar($var);
						if ($default != 'nodefault') {
							$extra = "default '$default'";
						} else {
							$extra = false;
						}
					}
					if ($extra) {
						$structure .= "`$key` $type not null $extra,";
					} else {
						$structure .= "`$key` $type not null,";
					}

				}
			}
			$ModKeyNames = $module_handler->keyName;
			$structure .= "PRIMARY KEY  (";
			if (is_array($ModKeyNames)){
				$structure .= "`".$ModKeyNames[0]."`";
				foreach( $ModKeyNames as $ModKeyName){
					$structure .= ($ModKeyName != $ModKeyNames[0])?", `".$ModKeyName."`":"";
				}
			}else{
				$structure .= "`".$ModKeyNames."`";
			}
			$structure .= ")";
			$table->setStructure($structure);
			if (!$this->updateTable($table)) {
				/**
				 * @todo trap the errors
				 */
			}
			foreach ($table->_messages as $msg) {
				$this->_messages[] = $msg;
			}
		} else {
			$existingFieldsArray = $table->getExistingFieldsArray();
			foreach($objectVars as $key=>$var) {
				if ($var['persistent']) {
					if (!isset($existingFieldsArray[$key])) {
						// the fiels does not exist, let's create it
						$type = $this->getFieldTypeFromVar($var);
						$default =  $this->getFieldDefaultFromVar($var);
						if ($default != 'nodefault') {
							$extra = "default '$default'";
						} else {
							$extra = false;
						}
						$table->addNewField($key, "$type not null " . $extra);
					} else {
						// if field already exists, let's check if the definition is correct
						$definition =  strtolower($existingFieldsArray[$key]);
						$type = $this->getFieldTypeFromVar($var);

						if ($key == $module_handler->keyName) {
							$extra = "auto_increment";
						} else {
							$default =  $this->getFieldDefaultFromVar($var, $key);
							if ($default != 'nodefault') {
								$extra = "default '$default'";
							} else {
								$extra = false;
							}
						}
						$actual_definition = "$type not null";
						if ($extra) {
							$actual_definition .= " $extra";
						}
						if ($definition != $actual_definition) {
							$table->addAlteredField($key, $actual_definition);
						}
					}
				}
			}

			// check to see if there are some unused fields left in the table
			foreach ($existingFieldsArray as $key=>$v) {
				if (!isset($objectVars[$key]) || !$objectVars[$key]['persistent']) {
					$table->addDropedField($key);
				}
			}

			if (!$this->updateTable($table)) {
				/**
				 * @todo trap the errors
				 */
			}
		}
	}

	/**
	 * Insert a config in System Preferences
	 *
	 * @param int $conf_catid
	 * @param string $conf_name
	 * @param string $conf_title
	 * @param mixed $conf_value
	 * @param string $conf_desc
	 * @param string $conf_formtype
	 * @param string $conf_valuetype
	 * @param int $conf_order
	 */
	function insertConfig($conf_catid, $conf_name, $conf_title, $conf_value, $conf_desc, $conf_formtype, $conf_valuetype, $conf_order) {
		global $dbVersion;
		$configitem_handler = icms::handler('icms_config_item');
		$configitemObj = $configitem_handler->create();
		$configitemObj->setVar('conf_modid', 0);
		$configitemObj->setVar('conf_catid', $conf_catid);
		$configitemObj->setVar('conf_name', $conf_name);
		$configitemObj->setVar('conf_title', $conf_title);
		$configitemObj->setVar('conf_value', $conf_value);
		$configitemObj->setVar('conf_desc', $conf_desc);
		$configitemObj->setVar('conf_formtype', $conf_formtype);
		$configitemObj->setVar('conf_valuetype', $conf_valuetype);
		$configitemObj->setVar('conf_order', $conf_order);
		if (!$configitem_handler->insert($configitemObj)) {
			$querry_answer = sprintf(_DATABASEUPDATER_MSG_CONFIG_ERR, $conf_title);
		} else{
			$querry_answer = sprintf(_DATABASEUPDATER_MSG_CONFIG_SCC, $conf_title);
		}
		$this->_messages[] =  $querry_answer;
	}

	/*
	 * Module Upgrade
	 * @param object reference to Module Object
	 * @return bool whether upgrade succeeded or not
	 */
	function moduleUpgrade(&$module, $tables_first=false) {
		$dirname = $module->getVar('dirname');

		//		ob_start();

		$dbVersion  = $module->getDbversion();

		$newDbVersion = constant(strtoupper($dirname . '_db_version')) ? constant(strtoupper($dirname . '_db_version')) : 0;
		$textcurrentversion = sprintf(_DATABASEUPDATER_CURRENTVER, $dbVersion);
		$textlatestversion = sprintf(_DATABASEUPDATER_LATESTVER, $newDbVersion);
		$this->_messages[] =  $textcurrentversion;
		$this->_messages[] =  $textlatestversion;
		if(!$tables_first){
			if ($newDbVersion > $dbVersion) {
				for($i=$dbVersion+1;$i<=$newDbVersion; $i++) {
					$upgrade_function = $dirname . '_db_upgrade_' . $i;
					if (function_exists($upgrade_function)) {
						$upgrade_function();
					}
				}
			}
		}
		$this->_messages[] =  _DATABASEUPDATER_UPDATE_UPDATING_DATABASE;

		// if there is a function to execute for this DB version, let's do it
		//$function_

		$this->automaticUpgrade($dirname, $module->modinfo['object_items']);
		/*
		 if (method_exists($module, "setMessage")) {
			$module->setMessage($this->_messages);
			} else {
			foreach($this->_messages as $feedback){
			echo $feedback;
			}
			}
			*/
		if($tables_first){
			if ($newDbVersion > $dbVersion) {
				for($i=$dbVersion+1;$i<=$newDbVersion; $i++) {
					$upgrade_function = $dirname . '_db_upgrade_' . $i;
					if (function_exists($upgrade_function)) {
						$upgrade_function();
					}
				}
			}
		}

		$this->updateModuleDBVersion($newDbVersion, $dirname);
		return true;
	}

	/**
	 * Update the DBVersion of a module
	 *
	 * @param int $newDVersion new database version
	 * @param string $dirname dirname of the module
	 *
	 * @return bool TRUE if success FALSE if not
	 */
	function updateModuleDBVersion($newDBVersion, $dirname) {
		if (!$dirname) {
			$dirname = icms_getCurrentModuleName();
		}
		$module_handler = icms::handler('icms_module');
		$module = $module_handler->getByDirname($dirname);
		$module->setVar('dbversion', $newDBVersion);

		if (!$module_handler->insert($module)) {
			$module->setErrors(_DATABASEUPDATER_MSG_DB_VERSION_ERR);
			return false;
		}
		return true;

	}
}

?>