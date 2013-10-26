<?php

class icms_db_legacy_PdoDatabase extends icms_db_legacy_Database {
	/**
	 * The PDO connection that performs operations behind the scenes
	 * @var icms_db_IConnection
	 */
	protected $pdo;
	/**
	 * Row count of the most recent statement
	 * @var int
	 */
	protected $rowCount = 0;

	public function __construct( $connection, $allowWebChanges = false ) {
		parent::__construct($connection, $allowWebChanges);
		$this->pdo = $connection;
	}
	public function connect($selectdb = true) {
		return true;
	}
	public function close() {
 		$this->pdo = null;
  		return true;
	}
	public function quoteString($string) {
		return $this->pdo->quote($string);
	}
	public function quote($string) {
		return $this->pdo->quote($string);
	}
	public function escape($string) {
		return $this->pdo->escape($string);
	}
	public function error() {
		$error = $this->pdo->errorInfo();
		return $error[2];
	}
	public function errno() {
		$error = $this->pdo->errorInfo();
		return $error[1];
	}
	public function genId($sequence) {
		return 0; // will use auto_increment
	}

	public function query($sql, $limit = 0, $start = 0) {
		if (!$this->allowWebChanges && strtolower(substr(trim($sql), 0, 6)) != 'select')  {
			trigger_error(_CORE_DB_NOTALLOWEDINGET, E_USER_WARNING);
			return false;
		}
		return $this->queryF($sql, $limit, $start);
	}
	public function queryF($sql, $limit = 0, $start = 0) {
		if (!empty ($limit)) {
			$start = !empty($start) ? (int)$start . ',' : '';
			$sql .= ' LIMIT ' . $start . (int)$limit;
		}
		$result = false;
		try {
			$result = $this->pdo->query($sql);
			$this->rowCount = $result->rowCount();
		} catch (Exception $e) {
		}
		return $result;
	}
	public function getInsertId() {
		return $this->pdo->lastInsertId();
	}
	public function getAffectedRows() {
		return $this->rowCount;
	}
	public function getFieldName($result, $offset) {
		$column = $result->getColumnMeta($offset);
		return $column['name'];
	}
	public function getFieldType($result, $offset) {
		$column = $result->getColumnMeta($offset);
		return $column['mysql:decl_type'];
	}
	public function getFieldsNum($result) {
		return $result->columnCount();
	}
	public function fetchRow($result) {
		return $result->fetch( PDO::FETCH_NUM );
	}
	public function fetchArray($result) {
		return $result->fetch( PDO::FETCH_ASSOC );
	}
	public function fetchBoth($result) {
		return $result->fetch( PDO::FETCH_BOTH );
	}
	public function getRowsNum($result) {
		return $result->rowCount();
	}
	public function freeRecordSet($result) {
		$result->closeCursor();
		return true;
	}

}

