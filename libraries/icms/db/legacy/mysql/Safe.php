<?php
/**
 * Legacy MySQL query method
 *
 * @category	ICMS
 * @package		Database
 * @subpackage	Legacy
 */
defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

/**
 * Safe Connection to a MySQL database.
 *
 * @category	ICMS
 * @package     Database
 * @subpackage  Legacy
 * @author      Kazumi Ono  <onokazu@xoops.org>
 */
class icms_db_legacy_mysql_Safe extends icms_db_legacy_mysql_Database {

	/**
	 * perform a query on the database
	 *
	 * @param string $sql a valid MySQL query
	 * @param int $limit number of records to return
	 * @param int $start offset of first record to return
	 * @return resource query result or FALSE if successful
	 * or TRUE if successful and no result
	 */
	public function query($sql, $limit = 0, $start = 0) {
		return $this->queryF($sql, $limit, $start);
	}
}