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

	function db_manager() {
		$this->db = icms_db_legacy_Factory::getDatabase();
		$this->db->setPrefix(XOOPS_DB_PREFIX);
		$this->db->setLogger(icms_core_Logger::instance());
	}

	function isConnectable() {
		return ($this->db->connect(false) != false) ? true : false;
	}

	function queryFromFile($sql_file_path) {
		$tables = array();

		if (!file_exists($sql_file_path)) {
			return false;
		}
		$sql_query = trim(fread(fopen($sql_file_path, 'r'), filesize($sql_file_path)));
		icms_db_legacy_mysql_Utility::splitSqlFile($pieces, $sql_query);
		$this->db->connect();
		foreach ($pieces as $piece) {
			$piece = trim($piece);
			// [0] contains the prefixed query
			// [4] contains unprefixed table name
			$prefixed_query = icms_db_legacy_mysql_Utility::prefixQuery($piece, $this->db->prefix());
			if ($prefixed_query != false) {
				$table = $this->db->prefix($prefixed_query[4]);
				if ($prefixed_query[1] == 'CREATE TABLE') {
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
		$this->db->connect();
		return $this->db->query($sql);
	}

	function prefix($table) {
		$this->db->connect();
		return $this->db->prefix($table);
	}

	function fetchArray($ret) {
		$this->db->connect();
		return $this->db->fetchArray($ret);
	}

	function insert($table, $query) {
		$this->db->connect();
		$table = $this->db->prefix($table);
		$query = 'INSERT INTO '.$table.' '.$query;
		if (!$this->db->queryF($query)) {
			//var_export($query);
			//echo '<br />' . mysql_error() . '<br />';
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
			$this->db->connect();
			$sql = 'SELECT COUNT(*) FROM '.$this->db->prefix($table);
			$ret = (false != $this->db->query($sql)) ? true : false;
		}
		return $ret;
	}
}

?>