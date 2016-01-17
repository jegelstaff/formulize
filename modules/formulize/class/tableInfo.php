<?php

defined('DB_INFO_NAME')? NULL : define('DB_INFO_NAME', 'information_schema');

class tableInfo {

    private function openConn($dbname) {
        try {
            $conn = new \PDO(DB_TYPE.':host='.DB_HOST.';dbname='.$dbname, DB_USER, DB_PASS);
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $conn;
        }
        catch (\PDOException $e) {
            die('Failed to Connect to db named ' . $dbname . ' due to: ' . $e->getMessage());
        }
    }

    private function getTableTypes($tableName) {
        $conn = $this->openConn(DB_INFO_NAME);

        $query = "SELECT DATA_TYPE FROM COLUMNS WHERE TABLE_NAME = '".$tableName."';";
        $types = $conn->query($query)->fetchAll();

        return $types;
    }

    private function getTableCols($tableName) {
        $conn = $this->openConn(DB_INFO_NAME);

        $query = "SELECT COLUMN_NAME FROM COLUMNS WHERE TABLE_NAME = '".$tableName."';";
        $cols = $conn->query($query)->fetchAll();

        return $cols;
    }

    private function getTableRecords($tableName) {
        $conn = $this->openConn(DB_NAME);

        $query = "SELECT * FROM ".$tableName.";";
        $records = $conn->query($query)->fetchAll();

        return $records;
    }

    private function getFilteredTableRecords($tableName, $columnName, $value) {
        $conn = $this->openConn(DB_NAME);

        $query = "SELECT * FROM ".$tableName." WHERE ".$columnName." = ".$value.";";
        $records = $conn->query($query)->fetchAll();

        return $records;
    }

    public function get($tableName) {
        return array(
            "name" => $tableName,
            "columns" => $this->getTableCols($tableName),
            "types" => $this->getTableTypes($tableName),
            "records" => $this->getTableRecords($tableName)
        );
    }

    // tableInfo->getWithFilter is a function which will retrieve tableInfo but with a filter to select
    //                  only the records where $columnName is equal to $value
    public function getWithFilter($tableName, $columnName, $value) {
        return array(
            "name" => $tableName,
            "columns" => $this->getTableCols($tableName),
            "types" => $this->getTableTypes($tableName),
            "records" => $this->getFilteredTableRecords($tableName, $columnName, $value)
        );
    }

}
