<?php
/**
 * Contains the classes for updating database tables
 *
 * @license GNU
 * @author marcan <marcan@smartfactory.ca>
 * @version $Id: Table.php 21294 2011-03-26 21:18:22Z skenow $
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

class icms_db_legacy_updater_Table {
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
	function __construct($name) {
		$this->db = icms::$xoopsDB;
		/** For backward compat */
		$this->_db = icms::$xoopsDB;

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
		$bRetVal = false;
		$ret = $this->_db->queryF("SHOW TABLES FROM `" . XOOPS_DB_NAME . "` LIKE '" . $this->name() . "'");
		list ($m_table) = $this->_db->fetchRow($ret);
		if ($m_table == $this->name()) $bRetVal = true;
		return $bRetVal;
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
		$str = '(' . implode( '), (', $this->getData() ) . ')';
		$query = sprintf( 'INSERT INTO %s VALUES %s', $this->name(), $str );

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
	 * @param   object  $criteria   {@link icms_db_criteria_Element}
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
	 * @param   object  $criteria   {@link icms_db_criteria_Element}
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
		$query = "CREATE TABLE `" . $this->name() . "` (" . $query . ")";

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
		$query = 'ALTER TABLE `' . $this->name() .'`';
		foreach ($this->getAlteredFields() as $alteredField) {
			if (!$alteredField['newname']) {
				$alteredField['newname'] = $alteredField['name'];
			}
			$query .= sprintf( ' CHANGE `%s` `%s` %s,', $alteredField['name'], $alteredField['newname'], $alteredField['properties'] );
		}
		$query = substr( $query, 0, -1 );
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
		$query = 'ALTER TABLE `' . $this->name() . '`';
		foreach ($this->getNewFields() as $newField) {
			$query .= sprintf( ' ADD `%s` %s,', $newField['name'], $newField['properties'] );
		}
		$query = substr( $query, 0, -1 );
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
		return $ret;
	}

	/**
	 * Change a value for objects with a certain criteria
	 *
	 * @param   string  $fieldname  Name of the field
	 * @param   string  $fieldvalue Value to write
	 * @param   object  $criteria   {@link icms_db_criteria_Element}
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
			if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
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
	 * @param object $criteria {@link icms_db_criteria_Element} with conditions to meet
	 * @return bool
	 */

	function deleteAll()
	{
		$ret = true;
		foreach ($this->getDeleteAll() as $item) {
			$criteria = isset($item['criteria']) ? $item['criteria'] : null;
			if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
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
		$str = implode( ', DROP ', $this->getdropedFields() );
		$query = 'ALTER TABLE ' . $this->name() . ' DROP ' . $str;
			if ($this->force) {
				$ret = $ret && $this->_db->queryF($query);
			} else {
				$ret = $ret && $this->_db->query($query);
			}

			if (!$ret) {
				$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_DROPFIELD_ERR, $str, $this->name()) . " (" . $this->_db->error(). ")";
			} else {
				$this->_messages[] =  "&nbsp;&nbsp;" . sprintf(_DATABASEUPDATER_MSG_DROPFIELD, $str, $this->name());
			}
		return $ret;
	}
}