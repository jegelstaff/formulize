<?php
/**
 * Contains the classes for updating database tables
 *
 * @license GNU
 * @author marcan <marcan@smartfactory.ca>
 * @version $Id: databaseupdater.php 9354 2009-09-09 23:11:10Z skenow $
 * @link http://www.smartfactory.ca The SmartFactory
 * @package database
 */
/**
 * IcmsDatabasetable class
 *
 * Information about an individual table
 *
 * @package SmartObject
 * @author marcan <marcan@smartfactory.ca>
 * @link http://www.smartfactory.ca The SmartFactory
 */
if (!defined("ICMS_ROOT_PATH")) {
	die("ImpressCMS root path not defined");
}

/**
 * Include the language constants for the IcmsDatabaseupdater
 */
global $icmsConfigPersona;
icms_loadLanguageFile('core', 'databaseupdater');

class IcmsDatabasetable {
	/**
	 * @var string $_name name of the table
	 */
	var $_name;
	/**
	 * @var string $_structure structure of the table
	 */
	var $_structure;

	/**
	 * @var array $_data containing valued of each records to be added
	 */
	var $_data;

	/**
	 * @var array $_alteredFields containing fields to be altered
	 */
	var $_alteredFields;

	/**
	 * @var array $_newFields containing new fields to be added
	 */
	var $_newFields;

	/**
	 * @var array $_dropedFields containing fields to be droped
	 */
	var $_dropedFields;

	/**
	 * @var array $_flagForDrop flag table to drop it
	 */
	var $_flagForDrop = false;

	/**
	 * @var array $_updateAll containing items to be updated
	 */
	var $_updateAll;

	/**
	 * @var array $_deleteAll containing items to be deleted
	 */
	var $_deleteAll;

	var $_existingFieldsArray=false;

	/**
	 * @var bool $force force the query even in a GET process
	 */
	var $force=false;

	/** For backward compat */
	var $_db;

	/**
	 * xoopsDB database object
	 *
	 * @var @link XoopsDatabase object
	 */
	var $db;

	/**
	 * @var array $_messages containing messages to be shown
	 */
	var $_messages = array();

	/**
	 * Constructor
	 *
	 * @param string $name name of the table
	 *
	 */
	function IcmsDatabasetable($name) {
		global $xoopsDB;

		$this->db = $xoopsDB;
		/** For backward compat */
		$this->_db = $xoopsDB;

		$this->_name = $name;
		$this->_data = array ();
	}

	/**
	 * Return the table name, prefixed with site table prefix
	 *
	 * @return string table name
	 *
	 */
	function name() {
		return $this->_db->prefix($this->_name);
	}

	/**
	 * Detemines if a table exists in the current db
	 *
	 * Checks if the table already exists in the database
	 * @return bool True if table exists, false if not
	 *
	 * @access public
	 * @author xhelp development team
	 */
	function exists() {
		$table = $this->_name;
		$bRetVal = false;
		//Verifies that a MySQL table exists
		$realname = $this->_db->prefix($table);
		$ret = mysql_list_tables(XOOPS_DB_NAME, $this->_db->conn);
		while (list ($m_table) = $this->_db->fetchRow($ret)) {
			if ($m_table == $realname) {
				$bRetVal = true;
				break;
			}
		}
		$this->_db->freeRecordSet($ret);
		return ($bRetVal);
	}


   /*
   * Gets the field array from one table name
   * @return array array of fields
   */
	function getExistingFieldsArray() {
		$sql = "SHOW COLUMNS FROM " . $this->name();
		$result = $this->_db->queryF($sql);
		while ($existing_field = $this->_db->fetchArray($result)) {
			$fields[$existing_field['Field']] = $existing_field['Type'];
			if ($existing_field['Null'] != "YES") {
				$fields[$existing_field['Field']] .= " NOT NULL";
			}
			if ($existing_field['Extra']) {
				$fields[$existing_field['Field']] .= " " . $existing_field['Extra'];
			}
			if (!($existing_field['Default'] === NULL) && ($existing_field['Default'] || $existing_field['Default'] == '' || $existing_field['Default'] == 0)) {
				$fields[$existing_field['Field']] .= " default '" . $existing_field['Default'] . "'";
			}
		}
		return $fields;
	}



   /*
   * Checks whether the field exists or not
   * @param string $field does the field exist in the database
   * @return bool whether the field exists or not
   */
	function fieldExists($field) {
		$existingFields = $this->getExistingFieldsArray();
		return isset($existingFields[$field]);
	}



	/**
	 * Set the table structure
	 *
	 * Example :
	 *
	 *	 	$table->setStructure("`transactionid` int(11) NOT NULL auto_increment,
	 * 				  `date` int(11) NOT NULL default '0',
	 * 				  `status` int(1) NOT NULL default '-1',
	 * 				  `itemid` int(11) NOT NULL default '0',
	 * 				  `uid` int(11) NOT NULL default '0',
	 * 				  `price` float NOT NULL default '0',
	 * 				  `currency` varchar(100) NOT NULL default '',
	 * 				  PRIMARY KEY  (`transactionid`)");
	 *
	 * @param  string $structure table structure
	 *
	 */
	function setStructure($structure) {
		$this->_structure = $structure;
	}


	/**
	* Returns the table structure
	*
	* @return string table structure
	*
	*/
	function getStructure() {
		return sprintf($this->_structure, $this->name());
	}


	/**
	 * Add values of a record to be added
	 *
	 * @param string $data values of a record
	 *
	 */
	function setData($data) {
		$this->_data[] = $data;
	}

	/**
	 * Get the data array
	 *
	 * @return array containing the records values to be added
	 *
	 */
	function getData() {
		return $this->_data;
	}


	/**
	 * Use to insert data in a table
	 *
	 * @return bool true if success, false if an error occured
	 *
	 */
	function addData() {
		foreach ($this->getData() as $data) {
			$query = sprintf('INSERT INTO %s VALUES (%s)', $this->name(), $data);
			if ($this->force) {
				$ret = $this->_db->queryF($query);
			} else {
				$ret = $this->_db->query($query);
			}
			if (!$ret) {
				$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_ADD_DATA_ERR, $this->name());
			} else {
				$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_ADD_DATA, $this->name());
			}
		}
		return $ret;
	}


	/**
	 * Add a field to be added
	 *
	 * @param string $name name of the field
	 * @param string $properties properties of the field
	 *
	 */
	function addAlteredField($name, $properties, $newname=false, $showerror = true) {
		$field['name'] = $name;
		$field['properties'] = $properties;
		$field['showerror'] = $showerror;
		$field['newname'] = $newname;
		$this->_alteredFields[] = $field;
	}

	/**
	 * Add new field of a record to be added
	 *
	 * @param string $name name of the field
	 * @param string $properties properties of the field
	 *
	 */
	function addNewField($name, $properties) {
		$field['name'] = $name;
		$field['properties'] = $properties;
		$this->_newFields[] = $field;
	}


	/**
	 * Get fields that need to be altered
	 *
	 * @return array fields that need to be altered
	 *
	 */
	function getAlteredFields() {
		return $this->_alteredFields;
	}


	/**
	 * Add item to be updated on the the table via the UpdateAll method
	 *
   * @param   string  $fieldname  Name of the field
   * @param   string  $fieldvalue Value to write
   * @param   object  $criteria   {@link CriteriaElement}
   * @param 	bool	$fieldvalueIsOperation TRUE if fieldvalue is an operation, for example, conf_order+1
	 *
   * @return  bool
	 */
	function addUpdateAll($fieldname, $fieldvalue, $criteria, $fieldvalueIsOperation) {
		$item['fieldname'] = $fieldname;
		$item['fieldvalue'] = $fieldvalue;
		$item['criteria'] = $criteria;
		$item['fieldvalueIsOperation'] = $fieldvalueIsOperation;
		$this->_updateAll[] = $item;
	}


	/**
	 * Add item to be updated on the the table via the UpdateAll method
	 *
   * @param   string  $fieldname  Name of the field
   * @param   string  $fieldvalue Value to write
   * @param   object  $criteria   {@link CriteriaElement}
	 *
   * @return  bool
	 */
	function addDeleteAll($criteria) {
		$item['criteria'] = $criteria;
		$this->_deleteAll[] = $item;
	}


	/**
	 * Get new fields to be added
	 *
	 * @return array fields to be added
	 *
	 */
	function getNewFields() {
		return $this->_newFields;
	}


	/**
	 * Get items to be updated
	 *
	 * @return array items to be updated
	 *
	 */
	function getUpdateAll() {
		return $this->_updateAll;
	}


	/**
	 * Get items to be deleted
	 *
	 * @return array items to be deleted
	 *
	 */
	function getDeleteAll() {
		return $this->_deleteAll;
	}


	/**
	 * Add values of a record to be added
	 *
	 * @param string $name name of the field
	 *
	 */
	function addDropedField($name) {
		$this->_dropedFields[] = $name;
	}


	/**
	 * Get fields that need to be droped
	 *
	 * @return array fields that need to be droped
	 *
	 */
	function getDropedFields() {
		return $this->_dropedFields;
	}


	/**
	 * Set the flag to drop the table
	 *
	 */
	function setFlagForDrop() {
		$this->_flagForDrop = true;
	}


	/**
	 * Use to create a table
	 *
	 * @return bool true if success, false if an error occured
	 *
	 */
	function createTable() {
		$query = $this->getStructure();
		$query = "CREATE TABLE `" . $this->name() . "` (" . $query . ") TYPE=MyISAM";

		if ($this->force) {
			$ret = $this->_db->queryF($query);
		} else {
			$ret = $this->_db->query($query);
		}
		if (!$ret) {
			$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_CREATE_TABLE_ERR, $this->name()) . " (" . $this->_db->error(). ")";

		} else {
			$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_CREATE_TABLE, $this->name());
		}
		return $ret;
	}


	/**
	 * Use to drop a table
	 *
	 * @return bool true if success, false if an error occured
	 *
	 */
	function dropTable() {
		$query = sprintf("DROP TABLE %s", $this->name());
		if ($this->force) {
			$ret = $this->_db->queryF($query);
		} else {
			$ret = $this->_db->query($query);
		}
		if (!$ret) {
			$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_DROP_TABLE_ERR, $this->name()) . " (" . $this->_db->error(). ")";
			return false;
		} else {
			$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_DROP_TABLE, $this->name());
			return true;
		}
	}


	/**
	 * Use to alter a table
	 *
	 * @return bool true if success, false if an error occured
	 *
	 */
	function alterTable() {
		$ret = true;

		foreach ($this->getAlteredFields() as $alteredField) {
			if (!$alteredField['newname']) {
				$alteredField['newname'] = $alteredField['name'];
			}

			$query = sprintf("ALTER TABLE `%s` CHANGE `%s` `%s` %s", $this->name(), $alteredField['name'], $alteredField['newname'], $alteredField['properties']);
			if ($this->force) {
				$ret = $ret && $this->_db->queryF($query);
			} else {
				$ret = $ret && $this->_db->query($query);
			}

			if ($alteredField['showerror']) {
				if (!$ret) {
				$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_CHGFIELD_ERR, $alteredField['name'], $this->name()) . " (" . $this->_db->error(). ")";
				} else {
					$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_CHGFIELD, $alteredField['name'], $this->name());
				}
			}
		}

		return $ret;
	}


	/**
	 * Use to add new fileds in the table
	 *
	 * @return bool true if success, false if an error occured
	 *
	 */
	function addNewFields() {
		$ret = true;
		foreach ($this->getNewFields() as $newField) {
			$query = sprintf("ALTER TABLE `%s` ADD `%s` %s", $this->name(), $newField['name'], $newField['properties']);

			if ($this->force) {
				$ret = $ret && $this->_db->queryF($query);
			} else {
				$ret = $ret && $this->_db->query($query);
			}

			if (!$ret) {
				$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_NEWFIELD_ERR, $newField['name'], $this->name());
			} else {
				$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_NEWFIELD, $newField['name'], $this->name());
			}
		}
		return $ret;
	}

  /**
   * Change a value for objects with a certain criteria
   *
   * @param   string  $fieldname  Name of the field
   * @param   string  $fieldvalue Value to write
   * @param   object  $criteria   {@link CriteriaElement}
   *
   * @return  bool
   **/
  function updateAll()
  {
  	$ret = true;
  	foreach ($this->getUpdateAll() as $item) {
  		$fieldname = $item['fieldname'];
  		$fieldvalue = $item['fieldvalue'];
  		$criteria = isset($item['criteria']) ? $item['criteria'] : null;

  		$set_clause = $fieldname . ' = ';
   	if ( is_numeric( $fieldvalue ) || $item['fieldvalueIsOperation']) {
   		$set_clause .=  $fieldvalue;
   	} elseif ( is_array( $fieldvalue ) ) {
   		$set_clause .= $this->_db->quoteString( implode( ',', $fieldvalue ) );
   	} else {
   		$set_clause .= $this->_db->quoteString( $fieldvalue );
   	}
	   $sql = 'UPDATE '.$this->name().' SET '.$set_clause;
	   if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
		   $sql .= ' '.$criteria->renderWhere();
	   }
	   if ($this->force) {
	   	$ret = $this->_db->queryF($sql);
	   } else {
	   	$ret = $this->_db->query($sql);
	   }
		  if (!$ret) {
	   	$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_UPDATE_TABLE_ERR, $this->name()) . " (" . $this->_db->error(). ")";
	   } else {
	   	$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_UPDATE_TABLE, $this->name());
	   }
  	}
  	return $ret;
  }

  /**
   * delete all objects meeting the conditions
   *
   * @param object $criteria {@link CriteriaElement} with conditions to meet
   * @return bool
   */

  function deleteAll()
  {
	$ret = true;
  	foreach ($this->getDeleteAll() as $item) {
  		$criteria = isset($item['criteria']) ? $item['criteria'] : null;
	   if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
		   $sql = 'DELETE FROM '.$this->table;
		   $sql .= ' '.$criteria->renderWhere();
		   if ($this->force) {
		   	$result = $this->_db->queryF($sql);
		   } else {
		   	$result = $this->_db->query($sql);
		   }
		   if (!$result) {
			   $this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_DELETE_TABLE_ERR, $this->name()) . " (" . $this->_db->error(). ")";
		   } else {
		   	$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_DELETE_TABLE, $this->name()) . " (" . $this->_db->error(). ")";
		   }
	   }
	   $ret = $result && $ret;
  	}
   return $ret;
 }


	/**
	 * Use to drop fields
	 *
	 * @return bool true if success, false if an error occured
	 *
	 */
	function dropFields() {
		$ret = true;
		foreach ($this->getdropedFields() as $dropedField) {
			$query = sprintf("ALTER TABLE %s DROP %s", $this->name(), $dropedField);
			if ($this->force) {
				$ret = $ret && $this->_db->queryF($query);
			} else {
				$ret = $ret && $this->_db->query($query);
			}

			if (!$ret) {
				$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_DROPFIELD_ERR, $dropedField, $this->name()) . " (" . $this->_db->error(). ")";
			} else {
				$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_DROPFIELD, $dropedField, $this->name());
			}
		}
		return $ret;
	}
}



/**
 * IcmsDatabaseupdater class
 *
 * Class performing the database update for the module
 *
 * @package SmartObject
 * @author marcan <marcan@smartfactory.ca>
 * @link http://www.smartfactory.ca The SmartFactory
 */
class IcmsDatabaseupdater {

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

	function IcmsDatabaseupdater() {
		global $xoopsDB;

		// backward compat
		$this->_db = $xoopsDB;
		$this->db = $xoopsDB;

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
	function runQuery($query, $goodmsg, $badmsg, $force=false) {
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
	 * @param object $table {@link IcmsDatabasetable} that will be updated
	 * @param bool	$force force the query even in a GET process
	 *
	 * @see IcmsDatabasetable
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
		if ($var['data_type'] == XOBJ_DTYPE_TXTAREA) {
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
							XOBJ_DTYPE_FILE
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
		$module_handler = xoops_getModuleHandler($item, $dirname);
		if (!$module_handler) {
			return false;
		}

		$table = new IcmsDatabasetable($dirname . '_' . $item);
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
							$extra = "default '$default'
";
						} else {
							$extra = false;
						}
					}
					if ($extra) {
						$structure .= "`$key` $type not null $extra,
";
					} else {
						$structure .= "`$key` $type not null,
";
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
		$configitem_handler = xoops_gethandler('configitem');
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
		$module = XoopsModuleHandler::getByDirname($dirname);
		$module->setVar('dbversion', $newDBVersion);
		$module_handler = xoops_getHandler('module');

		if (!$module_handler->insert($module)) {
			$module->setErrors(_DATABASEUPDATER_MSG_DB_VERSION_ERR);
			return false;
		}
		return true;

	}
}


?>