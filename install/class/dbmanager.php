<?php
/**
 * DB Manager Class
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	installer
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: dbmanager.php 20502 2010-12-08 00:27:17Z skenow $
 */

/**
 * database manager for XOOPS installer
 *
 * @author Haruki Setoyama  <haruki@planewave.org>
 * @version $Id: dbmanager.php 20502 2010-12-08 00:27:17Z skenow $
 * @access public
 **/

class db_manager {

	var $s_tables = array();
	var $f_tables = array();
	var $db;
    var $prefix;
    var $logger;
    var $connected = false;

	function __construct() {
	    $this->connected = icms_db_Factory::pdoInstance();
        $this->db = icms_db_Factory::instance();
        $this->setPrefix(XOOPS_DB_PREFIX);
		$this->setLogger(icms_core_Logger::instance());
	}

    public function setLogger($logger) {
		$this->logger = $logger;
	}
	public function setPrefix($value) {
		$this->prefix = $value;
	}
	public function prefix($tablename='') {
		if ( $tablename != '' ) {
			return $this->prefix .'_'. $tablename;
		} else {
			return $this->prefix;
		}
	}

	function isConnectable() {
		return $this->connected; 
	}

	function queryFromFile($sql_file_path) {
		$tables = array();

		if (!file_exists($sql_file_path)) {
			return false;
		}
		$sql_query = trim(fread(fopen($sql_file_path, 'r'), filesize($sql_file_path)));
		icms_db_legacy_mysql_Utility::splitSqlFile($pieces, $sql_query);
		foreach ($pieces as $piece) {
			$piece = trim($piece);
			// [0] contains the prefixed query
			// [4] contains unprefixed table name
			$prefixed_query = icms_db_legacy_mysql_Utility::prefixQuery($piece, $this->prefix());
			if ($prefixed_query != false) {
				$table = $this->prefix($prefixed_query[4]);
				if ($prefixed_query[1] == 'CREATE TABLE') {
				    // TODO problem area, check this out
					if ($this->db->query($prefixed_query[0]) != false) {
						if (! isset($this->s_tables['create'][$table])) {
							$this->s_tables['create'][$table] = 1;
						}
					} else {
						if (! isset($this->f_tables['create'][$table])) {
							$this->f_tables['create'][$table] = 1;
						}
					}
				}
				elseif ($prefixed_query[1] == 'INSERT INTO') {
					if ($this->db->query($prefixed_query[0]) != false) {
						if (! isset($this->s_tables['insert'][$table])) {
							$this->s_tables['insert'][$table] = 1;
						} else {
							$this->s_tables['insert'][$table]++;
						}
					} else {
						print $this->db->error().'<br>With SQL:<br>'.$prefixed_query[0].'<br><br>';
						if (! isset($this->f_tables['insert'][$table])) {
							$this->f_tables['insert'][$table] = 1;
						} else {
							$this->f_tables['insert'][$table]++;
						}
					}
				} elseif ($prefixed_query[1] == 'ALTER TABLE') {
					if ($this->db->query($prefixed_query[0]) != false) {
						if (! isset($this->s_tables['alter'][$table])) {
							$this->s_tables['alter'][$table] = 1;
						}
					} else {
						if (! isset($this->s_tables['alter'][$table])) {
							$this->f_tables['alter'][$table] = 1;
						}
					}
				} elseif ($prefixed_query[1] == 'DROP TABLE') {
					if ($this->db->query('DROP TABLE '.$table) != false) {
						if (! isset($this->s_tables['drop'][$table])) {
							$this->s_tables['drop'][$table] = 1;
						}
					} else {
						if (! isset($this->s_tables['drop'][$table])) {
							$this->f_tables['drop'][$table] = 1;
						}
					}
				}
			}
		}
		return true;
	}

	var $successStrings = array(
    	'create'	=> TABLE_CREATED,
    	'insert'	=> ROWS_INSERTED,
    	'alter'		=> TABLE_ALTERED,
    	'drop'		=> TABLE_DROPPED,
	);
	var $failureStrings = array(
    	'create'	=> TABLE_NOT_CREATED,
    	'insert'	=> ROWS_FAILED,
    	'alter'		=> TABLE_NOT_ALTERED,
    	'drop'		=> TABLE_NOT_DROPPED,
	);


	function report() {
		$commands = array( 'create', 'insert', 'alter', 'drop' );
		$content = '<ul class="log">';
		foreach ( $commands as $cmd) {
			if (!@empty( $this->s_tables[$cmd] )) {
				foreach ( $this->s_tables[$cmd] as $key => $val) {
					$content .= '<li class="success">';
					$content .= ($cmd!='insert') ? sprintf( $this->successStrings[$cmd], $key ) : sprintf( $this->successStrings[$cmd], $val, $key );
					$content .= "</li>\n";
				}
			}
		}
		foreach ( $commands as $cmd) {
			if (!@empty( $this->f_tables[$cmd] )) {
				foreach ( $this->f_tables[$cmd] as $key => $val) {
					$content .= '<li class="failure">';
					$content .= ($cmd!='insert') ? sprintf( $this->failureStrings[$cmd], $key ) : sprintf( $this->failureStrings[$cmd], $val, $key );
					$content .= "</li>\n";
				}
			}
		}
		$content .= '</ul>';
		return $content;
	}

	function query($sql) {
		return $this->db->query($sql);
	}

	function fetchArray($ret) {
		return $this->db->fetchArray($ret);
	}

	function insert($table, $query) {
		$table = $this->prefix($table);
		$query = 'INSERT INTO '.$table.' '.$query;
		if (!$this->db->queryF($query)) {
			if (!isset($this->f_tables['insert'][$table])) {
				$this->f_tables['insert'][$table] = 1;
			} else {
				$this->f_tables['insert'][$table]++;
			}
			return false;
		} else {
			if (!isset($this->s_tables['insert'][$table])) {
				$this->s_tables['insert'][$table] = 1;
			} else {
				$this->s_tables['insert'][$table]++;
			}
			return $this->db->getInsertId();
		}
	}

	function isError() {
		return (isset($this->f_tables)) ? true : false;
	}

	function tableExists($table) {
		$table = trim($table);
		$ret = false;
		if ($table != '') {
			$sql = 'SELECT COUNT(*) FROM '.$this->prefix($table);
			$ret = (false != $this->db->query($sql)) ? true : false;
		}
		return $ret;
	}
}

?>