<?php
/**
 * DataBase Base class file for MySQL driver
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Database
 * @subpackage	Legacy
 * @version		SVN: $Id: Database.php 20458 2010-12-03 00:01:23Z skenow $
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

/**
 * connection to a mysql database
 *
 * @category	ICMS
 * @package     Catabase
 * @subpackage	Legacy
 * @author      Kazumi Ono  <onokazu@xoops.org>
 */
abstract class icms_db_legacy_mysql_Database extends icms_db_legacy_Database {
	/**
	 * Database connection
	 * @var resource
	 */
	public $conn;

	/**
	 * connect to the database
	 *
	 * @param bool $selectdb select the database now?
	 * @return bool successful?
	 */
	public function connect($selectdb = true) {
		static $db_charset_set;

		$this->allowWebChanges = ($_SERVER['REQUEST_METHOD'] != 'GET');

		if (!extension_loaded('mysql')) {
			trigger_error(_CORE_DB_NOTRACE, E_USER_ERROR);
			return false;
		}

		if (XOOPS_DB_PCONNECT == 1) {
			$this->conn = @ mysql_pconnect(XOOPS_DB_HOST, XOOPS_DB_USER, XOOPS_DB_PASS, true); // ALTERED BY FREEFORM SOLUTIONS FOR THE FORMULIZE STANDALONE RELEASE
		} else {
			$this->conn = @ mysql_connect(XOOPS_DB_HOST, XOOPS_DB_USER, XOOPS_DB_PASS, true); // ALTERED BY FREEFORM SOLUTIONS FOR THE FORMULIZE STANDALONE RELEASE
		}

		if (!$this->conn) {
			$this->logger->addQuery('', $this->error(), $this->errno());
			return false;
		}
		if ($selectdb != false) {
			if (!mysql_select_db(XOOPS_DB_NAME)) {
				$this->logger->addQuery('', $this->error(), $this->errno());
				return false;
			}
		}

		if (!isset ($db_charset_set) && defined('XOOPS_DB_CHARSET') && XOOPS_DB_CHARSET && XOOPS_DB_CHARSET !== 'ucs2') {
			$this->queryF("SET NAMES '" . XOOPS_DB_CHARSET . "'");
		}
		$db_charset_set = 1;

		return true;
	}

	/**
	 * generate an ID for a new row
	 *
	 * This is for compatibility only. Will always return 0, because MySQL supports
	 * autoincrement for primary keys.
	 *
	 * @param string $sequence name of the sequence from which to get the next ID
	 * @return int always 0, because mysql has support for autoincrement
	 */
	public function genId($sequence) {
		return 0; // will use auto_increment
	}

	/**
	 * Get a result row as an enumerated array
	 *
	 * @param resource $result
	 * @return array the fetched rows
	 */
	public function fetchRow($result) {
		return @ mysql_fetch_row($result);
	}

	/**
	 * Fetch a result row as an associative array
	 *
	 * @return array the fetched associative array
	 */
	public function fetchArray($result) {
		return @ mysql_fetch_assoc($result);
	}

	/**
	 * Fetch a result row as an associative array and numerical array
	 *
	 * @return array the associative and numerical array
	 */
	public function fetchBoth($result) {
		return @ mysql_fetch_array($result, MYSQL_BOTH);
	}

	/**
	 * Get the ID generated from the previous INSERT operation
	 *
	 * @return int
	 */
	public function getInsertId() {
		return mysql_insert_id($this->conn);
	}

	/**
	 * Get number of rows in result
	 *
	 * @param resource query result
	 * @return int the number of rows in the resultset
	 */
	public function getRowsNum($result) {
		return @ mysql_num_rows($result);
	}

	/**
	 * Get number of affected rows
	 *
	 * @return int number of affected rows
	 */
	public function getAffectedRows() {
		return mysql_affected_rows($this->conn);
	}

	/**
	 * Closes MySQL connection
	 *
	 */
	public function close() {
		mysql_close($this->conn);
	}

	/**
	 * will free all memory associated with the result identifier result.
	 *
	 * @param resource query result
	 * @return bool TRUE on success or FALSE on failure.
	 */
	public function freeRecordSet($result) {
		return mysql_free_result($result);
	}

	/**
	 * Returns the text of the error message from previous MySQL operation
	 *
	 * @return string Returns the error text from the last MySQL function, or '' (the empty string) if no error occurred.
	 */
	public function error() {
		return @ mysql_error();
	}

	/**
	 * Returns the numerical value of the error message from previous MySQL operation
	 *
	 * @return int Returns the error number from the last MySQL function, or 0 (zero) if no error occurred.
	 */
	public function errno() {
		return @ mysql_errno();
	}

	/**
	 * Returns escaped string text with single quotes around it to be safely stored in database
	 *
	 * @param string $str unescaped string text
	 * @return string escaped string text with single quotes around
	 */
	public function quoteString($str) {
		return $this->quote($str);
		$str = "'" . str_replace('\\"', '"', addslashes($str)) . "'";
		return $str;
	}

	/**
	 * Quotes a string for use in a query using mysql_real_escape_string.
	 *
	 * @param string $str unescaped string text
	 * @return string escaped string text using mysql_real_escape_string
	 */
	public function quote($string) {
		return "'" . mysql_real_escape_string($string, $this->conn) . "'";
	}
	public function escape($string) {
		return mysql_real_escape_string($string, $this->conn);
	}
	/**
	 * perform a query on the database
	 *
	 * @param string $sql a valid MySQL query
	 * @param int $limit number of records to return
	 * @param int $start offset of first record to return
	 * @return resource query result or FALSE if successful
	 * or TRUE if successful and no result
	 */
	public function queryF($sql, $limit = 0, $start = 0) {
		if (!empty ($limit)) {
			if (empty ($start)) {
				$start = 0;
			}
			$sql = $sql . ' LIMIT ' . (int) $start . ', ' . (int) $limit;
		}
		$result = mysql_query($sql, $this->conn);
		if ($result) {
			$this->logger->addQuery($sql);
			return $result;
		} else {
			// ignore query trying to insert duplicate entries into the session table
			if (false === strpos($sql, "INSERT INTO ".SDATA_DB_PREFIX."_session")) {
				error_log("SQL query failed with ".$this->errno().": ".$this->error()." -- $sql");
			}
			$this->logger->addQuery($sql, $this->error(), $this->errno());
			return false;
		}
	}

	/**
	 * perform queries from SQL dump file in a batch
	 *
	 * @param string $file file path to an SQL dump file
	 *
	 * @return bool FALSE if failed reading SQL file or TRUE if the file has been read and queries executed
	 */
	public function queryFromFile($file) {
		if (false !== ($fp = fopen($file, 'r'))) {
			
			$sql_queries = trim(fread($fp, filesize($file)));
			icms_db_legacy_mysql_Utility::splitMySqlFile($pieces, $sql_queries);
			foreach ($pieces as $query) {
				// [0] contains the prefixed query
				// [4] contains unprefixed table name
				$prefixed_query = icms_db_legacy_mysql_Utility::prefixQuery(trim($query), $this->prefix());
				if ($prefixed_query != false) {
					$this->query($prefixed_query[0]);
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Get field name
	 *
	 * @param resource $result query result
	 * @param int numerical field index
	 * @return string the fieldname
	 */
	public function getFieldName($result, $offset) {
		return mysql_field_name($result, $offset);
	}

	/**
	 * Get field type
	 *
	 * @param resource $result query result
	 * @param int $offset numerical field index
	 * @return string the fieldtype
	 */
	public function getFieldType($result, $offset) {
		return mysql_field_type($result, $offset);
	}

	/**
	 * Get number of fields in result
	 *
	 * @param resource $result query result
	 * @return int number of fields in the resultset
	 */
	public function getFieldsNum($result) {
		return mysql_num_fields($result);
	}
}
