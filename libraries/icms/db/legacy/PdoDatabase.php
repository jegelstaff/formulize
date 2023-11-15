<?php

global $icmsConfig;
if(is_array($icmsConfig) AND isset($icmsConfig['language']) AND file_exists(XOOPS_ROOT_PATH.'/language/'.$icmsConfig['language'].'/core.php')) {
    require_once XOOPS_ROOT_PATH.'/language/'.$icmsConfig['language'].'/core.php';
} elseif(file_exists(XOOPS_ROOT_PATH.'/language/english/core.php')) {
    require_once XOOPS_ROOT_PATH.'/language/english/core.php';
}

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
        if($res = $this->query('SELECT @@character_set_database, @@collation_database')) {
            $collation = $this->fetchRow($res);
            if(strstr($collation[0], 'utf8mb4') AND strstr($collation[1], 'utf8mb4')) {
                $this->query('SET NAMES utf8mb4');
            }
        }
        $getModes = 'SELECT @@SESSION.sql_mode';
        $modesSet = false;
        if($res = $this->query($getModes)) {
            $modes = $this->fetchRow($res);
            $modes = $modes[0]; // only one result
            $modesSet = true;
            if(strstr($modes, 'STRICT_')) {
                $modes = explode(',', str_replace(array('STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES'), '', $modes)); // remove strict options
                $modes = array_filter($modes); // remove blanks, possibly caused by commas after removed modes
                $setModes = "SET SESSION sql_mode = '".implode(',',$modes)."'";
                if(!$res = $this->queryF($setModes)) {
                    $modesSet = false;
                }
            }
        }
        if(!$modesSet) {
            exit('Error: the database mode could not be set for proper operation of Formulize. Please notify a webmaster immediately. Thank you.');            
        }
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
        $result = false;
		if (!empty ($limit)) {
			$start = !empty($start) ? (int)$start . ',' : '';
			$sql .= ' LIMIT ' . $start . (int)$limit;
		}
		try {
			$result = $this->pdo->query($sql);
            $this->rowCount = $result ? $result->rowCount() : false;
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
		if ($result) {
            $column = $result->getColumnMeta($offset);
            return $column['name'];
		} else {
			return false;
        }
	}
	public function getFieldType($result, $offset) {
		if ($result) {
            $column = $result->getColumnMeta($offset);
            return $column['mysql:decl_type'];
		} else {
			return false;
        }
	}
	public function getFieldsNum($result) {
		return $result ? $result->columnCount() : false;
	}
	public function fetchRow($result) {
		return $result ? $result->fetch( PDO::FETCH_NUM ) : false;
	}
	public function fetchArray($result) {
		return $result ? $result->fetch( PDO::FETCH_ASSOC ) : false;
	}
	public function fetchBoth($result) {
		return $result ? $result->fetch( PDO::FETCH_BOTH ) : false;
	}
	public function getRowsNum($result) {
		return $result ? $result->rowCount() : false;
	}
	public function freeRecordSet($result) {
		if ($result) {
            $result->closeCursor();
			return false;
		} else {
			return true;
		}
	}
    
    public function queryFromFile($file) {
        if (false !== ($fp = fopen($file, 'r'))) {
			$sql_queries = trim(fread($fp, filesize($file)));
            $pieces = array();
			icms_db_legacy_mysql_Utility::splitMySqlFile($pieces, $sql_queries);
			foreach ($pieces as $query) {
				// [0] contains the prefixed query
				// [4] contains unprefixed table name
				$prefixed_query = $sqlutil->prefixQuery(trim($query), $this->prefix());
				if ($prefixed_query != false) {
					$this->query($prefixed_query[0]);
				}
			}
			return true;
		}
		return false;
    }
    

}

