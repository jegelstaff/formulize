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

    function getTableTypes($tableName) {
        $conn = $this->openConn(DB_INFO_NAME);

        $query = "SELECT DATA_TYPE FROM COLUMNS WHERE TABLE_NAME = '".$tableName."';";
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
