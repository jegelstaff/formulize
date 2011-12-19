<?php

// FORMULIZE PDO SUPPORT, BASED ON PDO SUPPORT IN IMPRESSCMS
// JULIAN EGELSTAFF - NOV 16 2010

if (!defined("ICMS_ROOT_PATH")) {
    die("ImpressCMS root path not defined");
}

include_once ICMS_ROOT_PATH."/class/database/database.php";

class XoopsPDODatabase extends XoopsDatabase {

	var $conn;

	/**
	 * Row count of the most recent statement
	 * @var int
	 */
	var $rowCount = 0;
	
	var $allowWebChanges = false;

	public function connect($selectdb = true) {
		$this->conn = new PDO("sqlsrv:Server=(local)\sqlexpress;Database=f4dev;quotedid=0", 'sqluser', 'sqlpassword'); // each driver has a specific connection string structure of its own
		$this->conn->setAttribute(PDO::SQLSRV_ATTR_DIRECT_QUERY,1);
		// these lines should be unnecessary thanks to quotedid=0 which sets this value on the connection itself
		//$this->conn->query("SET QUOTED_IDENTIFIER OFF;");
		return true;
	}
	public function close() {
		$this->conn = null;
		return true;
	}
	public function quoteString($string) {
		return $this->conn->quote($string);
	}
	public function escapeString($string) {
		return substr($this->conn->quote($string), 1, -1);
	}
	public function quote($string) {
		return $this->conn->quote($string);
	}
	public function escape($string) {
		return $this->conn->escape($string);
	}
	public function error() {
		$error = $this->conn->errorInfo();
		return $error[2];
	}
	public function errno() {
		$error = $this->conn->errorInfo();
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
		$returnValue = true;
		try {
			$sql = $this->convertToSQLServerSyntax($sql);
			if(strstr($sql, "CREATE TABLE")) {
			    $result = $this->conn->query("$sql"); // no need for cursor control on create table statements?? but seems to work ok -- needs more testing
			} else {
			    $pdoStatement = $this->conn->prepare("$sql", array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL)); // should now return a $result for which the rowCount will be accurate
			    if(!$pdoStatement) {
				print "Prepare failed for this SQL:<br>$sql<br>SQL Server reports: ".$this->error()."<br>";
			    }
			    $result = $pdoStatement->execute();
			}
			if(!$result) {
			    print "Error in this SQL:<br>$sql<br>SQL Server reports: ";
			    if($pdoStatement) {
				print_r($pdoStatement->errorInfo());
			    } else {
				print $this->error();
			    }
			    print "<br>";
			    $returnValue = false;
			}
			if($pdoStatement) {
			    $this->rowCount = $pdoStatement->rowCount(); // the cursor needs to be set to always return the full result in order for the rowcount to be a valid number
			}
		} catch (Exception $e) {
		    print "exception!!";
		    $returnValue = false;
		}
		if($returnValue) {
		    return $pdoStatement ? $pdoStatement : $returnValue;
		} else {
		    return false;
		}
		
	}
	public function getInsertId() {
		// Need to check with MS staff to see that lastInsertId is bound to our connection
		return $this->conn->lastInsertId();
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
		return $result->rowCount(); // the cursor needs to be set to always return the full result in order for the rowcount to be a valid number
	}
	public function freeRecordSet($result) {
		$result->closeCursor();
		return true;
	}

	private function convertToSQLServerSyntax($sql) {
	    // change SHOW COLUMNS to the SQL Server equivalent
	    if(substr($sql, 0, 12)=="SHOW COLUMNS") {
		// need to get the table name and then insert it as appropriate in the replacement SQL syntax
		// need to get the LIKE param and handle that too
		$fromPos = strpos($sql, "FROM");
		$tableName = substr($sql, $fromPos+5);
		$sql = "SELECT column_name FROM information_schema.columns WHERE table_name='" . trim($tableName) . "'";
		if($likePos = strpos($tableName, "LIKE")) {
		    $likeName = substr($tableName, $likePos+5);
		    $sql = substr($sql, 0, 69+$likePos);
		    $sql .= "' AND column_name = ".trim($likeName);
		} 
	    }
	    
	    $sql = str_replace("NOW(), ", "SYSDATETIME(), ", $sql);

	    if(substr($sql, 0, 11)=="ALTER TABLE"){
		$sql = str_replace("varchar", "nvarchhar", $sql);
		$sql = str_replace("char", "nchar", $sql);
		$sql = str_replace("varchhar", "varchar", $sql);
		$sql = str_replace("text", "nvarchar(max)", $sql);
		$sql = str_replace("int", "bigint", $sql); // no unsigned ints so need to go up to a bigger size
		while($intPos = strpos($sql, "int(", $intPos+3)) {
		    $secondParenPos = strpos($sql,")",$intPos);
		    $sql = substr_replace($sql, "int", $intPos,$secondParenPos-$intPos+1);
		}
		// remove the first column name reference
		if($changePos = strpos($sql, "CHANGE") AND strpos($sql, " default")) { // for full type rewrites....these don't work in SQL Server because we have to drop the "default constraint" first, which will be hard to find through some system table roundabout search voodoo.
        		$backtickPos = strpos($sql, "`", $changePos);
        		$secondBacktickPos = strpos($sql, "`", $backtickPos+1);
        		$sql = substr_replace($sql, "", $backtickPos, $secondBacktickPos-$backtickPos+1);
			$sql = str_replace("CHANGE", "ALTER COLUMN", $sql);
		} elseif(strpos($sql, "CHANGE")) { // for just renames, where no default is being affected
		    $sql = str_replace("ALTER TABLE ", "sp_RENAME '", $sql);
		    $sql = str_replace("` `", "', '", $sql); // backticks to apos
		    $sql = str_replace(" `", " .", $sql); // backticks to . (dot appends to the end of the table name)
		    $sql = str_replace(" CHANGE ", "", $sql); // remove change keyword
		    $sql = substr_replace($sql, "', 'COLUMN'",-2); // last backtick to apos, and 'COLUMN' key
		}
		
	    }

	    
	    if(trim($sql) == "SHOW TABLES") {
		// convert to get table names from the information schema
		$sql = "SELECT table_name FROM information_schema.tables";
	    }
	    if(strstr($sql,"CREATE TABLE")) {
		// auto_increment changes to IDENTITY(1,1)
		// strip  TYPE=MyISAM;
		// varchar becomes nvarchar
		// char becomes nchar
		// text converts to nvarchar(max)
		// tinyint goes to smallint
		// Datetime goes to datetime2
		
		// remove the (num) from after int
		while($intPos = strpos($sql, "int(", $intPos+3)) {
		    $secondParenPos = strpos($sql,")",$intPos);
		    $sql = substr_replace($sql, "int", $intPos,$secondParenPos-$intPos+1);
		}
		$sql = str_replace(" TYPE=MyISAM;", "", $sql);
		$sql = str_replace("unsigned", "", $sql);
		$sql = str_replace("NOT NULL auto_increment", "IDENTITY(1,1) NOT NULL", $sql);
		$sql = str_replace("NULL auto_increment", "IDENTITY(1,1) NULL", $sql);
		$sql = str_replace("varchar", "nvarchhar", $sql);
		$sql = str_replace("char", "nchar", $sql);
		$sql = str_replace("varchhar", "varchar", $sql);
		$sql = str_replace("text", "nvarchar(max)", $sql);
		$sql = str_replace("mediumint", "bigint", $sql); // no medium ints 
		$sql = str_replace("int", "bigint", $sql); // no unsigned ints so need to go up to a bigger size
		$sql = str_replace("tinyint", "int", $sql); 
		$sql = str_replace("Datetime", "datetime2", $sql);
		$sql = str_replace("INDEX", "; CREATE NONCLUSTERED INDEX", $sql);
		// get table name:
		$firstParenPos = strpos($sql, "(");
		$tableName = substr($sql, 13, $firstParenPos-14); // CREATE TABLE is 14 chars long
		// Index syntax: CREATE NONCLUSTERED INDEX NameOfIndex ON Table/View (col, col, col);
		while($indexPos = strpos($sql, "INDEX",$indexPos+25)) {
		    // find the next (
		    $parenPos = strpos($sql,"(",$indexPos);
		    $sql = substr_replace($sql, "ON $tableName (",$parenPos,1);
		}
		// remove the final )
		$lastParenPos = strrpos($sql, ")");
		$sql = substr_replace($sql, ";",$lastParenPos);
		// put a closing ) before the first CREATE for the first index
		$firstCreatePos = strpos($sql, ",; CREATE"); 
		$sql = substr_replace($sql, ")", $firstCreatePos,1);
	    }

	    // replace backticks with [ ]
	    $replacement = "[";
	    $backtickPos = 0;
	    while($backtickPos = strpos($sql, "`", $backtickPos+1)) {
		$sql = substr_replace($sql, $replacement,$backtickPos,1);
		$replacement = $replacement == "[" ? "]" : "[";
	    }
	    
	    // check for a limit clause and if found, send to the necessary function
	    // assume all caps LIMIT space and a number near the end of the statement is the actual limit clause that we need to work with
	    if($limitPos = strrpos($sql, "LIMIT")) {
		// verify it's at the end
		// check for comma
		// if one value, then it's the limit, if two, second is limit, first is offset
		if($limitPos >= strlen($sql) - 20) {// if the limit is pretty much at the end of the sql....
        	    $limitClause = substr($sql, $limitPos);
		    $sql = substr($sql, 0, $limitPos);
		    if($commaPos = strpos($limitClause, ",")) {
			$limit = intval(substr($limitClause,$commaPos+1));
			$offset = intval(str_replace("LIMIT", "", substr($limitClause, 0, $commaPos)));
		    } else {
			$limit = intval(str_replace("LIMIT", "", $limitClause));
			$offset = 0;
		    }
		}
		$sql = $this->limit_to_top_n($sql, $offset, $limit);
	    }
	    return $sql;
	}

	// thanks to moodle for this code
	private function limit_to_top_n($sql, $offset, $limit) {
	    if ($limit < 1 && $offset < 1) {
		return $sql;
	    }
	    $limit = max(0, $limit);
	    $offset = max(0, $offset);
    
	    if ($limit > 0 && $offset == 0) {
		$sql1 = preg_replace('/^([\s(])*SELECT( DISTINCT | ALL)?(?!\s*TOP\s*\()/i',
		    "\\1SELECT\\2 TOP $limit", $sql);
	    } else {
		// Only apply TOP clause if we have any limitnum (limitfrom offset is hadled later)
		if ($limit < 1) {
		   $limit = "9223372036854775806"; // MAX BIGINT -1
		}
		if (preg_match('/\w*FROM[\s|{]*([\w|.]*)[\s|}]?/i', $sql, $match)) {
		    $from_table = $match[1];
		    if (preg_match('/SELECT[\w|\s]*(\*)[\w|\s]*FROM/i', $sql)) {
			/*
			// Nov 17 2010 - I don't get why this is necessary, why not just use the columns as declared in the original statement?!
			// Need all the columns as the emulation returns some temp cols
			$cols = array_keys($this->get_columns($from_table));
			$cols = implode(', ', $cols);
			*/
			$fromPos = strpos($sql, "FROM");
			$cols = str_replace("SELECT", "", substr($sql, 0, $fromPos-1));
		    } else {
			$cols = '*';
		    }
		    $sql1 = 'SELECT '.$cols.' FROM ( '
			.'SELECT sub2.*, ROW_NUMBER() OVER(ORDER BY sub2.line2) AS line3 FROM ( '
			.'SELECT 1 AS line2, sub1.* FROM '
			.''.$from_table.' AS sub1 '
			.') AS sub2 '
			.') AS sub3 '
			.'WHERE line3 BETWEEN '.($offset+1).' AND '
			.($offset + $limit);
		} else {
		    $sql1 = "SELECT 'Invalid table'";
		}
	    }
    
	    return $sql1;
	}

}

class XoopsPDODatabaseSafe extends XoopsPDODatabase
{

  /**
   * perform a query on the database
   *
   * @param string $sql a valid MySQL query
   * @param int $limit number of records to return
   * @param int $start offset of first record to return
   * @return resource query result or FALSE if successful
   * or TRUE if successful and no result
   */
	function query($sql, $limit=0, $start=0)
	{
		return $this->queryF($sql, $limit, $start);
	}
}

/**
 * Read-Only connection to a MySQL database.
 *
 * This class allows only SELECT queries to be performed through its
 * {@link query()} method for security reasons.
 *
 * @package     database
 * @subpackage  mysql
 * @since XOOPS
 *
 * @author      Kazumi Ono  <onokazu@xoops.org>
 * @copyright   copyright (c) 2000-2003 XOOPS.org
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 */
class XoopsPDODatabaseProxy extends XoopsPDODatabase
{

  
}

