<?php

include_once 'PDO_Conn.php';

defined('DB_INFO_NAME')? NULL : define('DB_INFO_NAME', 'information_schema');

class tableInfo {

    function openConn($dbname) {
        try {
            $conn = new \PDO(DB_TYPE.':host='.DB_HOST.';dbname='.$dbname, DB_USER, DB_PASS);
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $conn;
        }
        catch (\PDOException $e) {
            die('Failed to Connect to db named ' . $dbname . ' due to: ' . $e->getMessage());
        }
    }

    function getTableNames() {
        $conn = $this->openConn(DB_INFO_NAME);

        // perhaps add LIKE clause for limiting the tables to _formulize_ ?
        $query = "SELECT table_name from tables;";
        $tableNames = $conn->query($query)->fetchAll();

        return $tableNames;
    }

    function getTableTypes($tableName) {
        $conn = $this->openConn(DB_INFO_NAME);

        $query = "SELECT data_type FROM columns WHERE table_name = '".$tableName."';";
        $types = $conn->query($query)->fetchAll();

        return $types;
    }

    function getTableCols($tableName) {
        $conn = $this->openConn(DB_INFO_NAME);

        $query = "SELECT COLUMN_NAME FROM COLUMNS WHERE TABLE_NAME = '".$tableName."';";
        $cols = $conn->query($query)->fetchAll();

        return $cols;
    }

}

$ti = new tableInfo();
print_r($ti->getTableTypes  ('if34aeb83_formulize'));