<?php

defined('DB_INFO_NAME')? NULL : define('DB_INFO_NAME', 'information_schema');

class tableInfo {

    private function openConn($dbname) {
        try {
            $conn = new \PDO('mysql'.':host='.XOOPS_DB_HOST.';dbname='.$dbname, XOOPS_DB_USER, XOOPS_DB_PASS);
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $conn;
        }
        catch (\PDOException $e) {
            die('Failed to Connect to db named ' . $dbname . ' due to: ' . $e->getMessage());
        }
    }

    private function getTableTypes($tableName) {
        $conn = $this->openConn(DB_INFO_NAME);
        $conn->query("SET NAMES utf8mb4");
        $query = "SELECT DATA_TYPE FROM COLUMNS WHERE TABLE_SCHEMA = '".XOOPS_DB_NAME."' AND TABLE_NAME = '".$tableName."';";
        $types = $conn->query($query)->fetchAll();

        return $types;
    }

    private function getTableCols($tableName) {
        $conn = $this->openConn(DB_INFO_NAME);
        $conn->query("SET NAMES utf8mb4");
        $query = "SELECT COLUMN_NAME FROM COLUMNS WHERE TABLE_SCHEMA = '".XOOPS_DB_NAME."' AND TABLE_NAME = '".$tableName."';";
        $cols = $conn->query($query)->fetchAll();

        return $cols;
    }

    private function getTableRecords($tableName) {
        $conn = $this->openConn(XOOPS_DB_NAME);
        $conn->query("SET NAMES utf8mb4");
        $eogfilter = "";
        if(strstr($tableName, "_formulize_entry_owner_groups")) {
            $eogfilter = array();
            foreach($_POST['forms'] as $fid) {
                $eogfilter[] = " fid = ".intval($fid)." ";
            }
            $eogfilter = " WHERE ".implode(" OR ",$eogfilter);
        }

        $query = "SELECT * FROM ".$tableName."$eogfilter;";
        $records = $conn->query($query)->fetchAll();
        return $records;
    }

    private function getFilteredTableRecords($tableName, $columnName, $value) {
        $conn = $this->openConn(XOOPS_DB_NAME);
        $conn->query("SET NAMES utf8mb4");
        $query = "SELECT * FROM ".$tableName." WHERE ".$columnName." = ".$value.";";
        $records = $conn->query($query)->fetchAll();

        return $records;
    }

    // the Other values for the given forms, limited to the entries in each form's data table
    // formDataTables is an array of form ids => full names of their data tables
    private function getOtherRecords($otherTableName, $formDataTables) {
        $conn = $this->openConn(XOOPS_DB_NAME);
        $conn->query("SET NAMES utf8mb4");
        $elementsTableName = XOOPS_DB_PREFIX."_formulize";
        $records = array();
        foreach($formDataTables as $fid => $dataTableName) {
            $query = "SELECT o.* FROM ".$otherTableName." AS o
                INNER JOIN ".$elementsTableName." AS e ON e.ele_id = o.ele_id
                INNER JOIN `".str_replace("`", "", $dataTableName)."` AS d ON d.entry_id = o.id_req
                WHERE e.id_form = ".intval($fid)."
                ORDER BY o.other_id;";
            try {
                $records = array_merge($records, $conn->query($query)->fetchAll());
            } catch (\PDOException $e) {
                // the form has no data table of its own, so it has no Other values to export
                continue;
            }
        }
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

    // tableInfo->getOtherValues retrieves tableInfo for the formulize_other table, but only with the records for
    //                  the given forms that belong to entries in their data tables
    public function getOtherValues($otherTableName, $formDataTables) {
        return array(
            "name" => $otherTableName,
            "columns" => $this->getTableCols($otherTableName),
            "types" => $this->getTableTypes($otherTableName),
            "records" => $this->getOtherRecords($otherTableName, $formDataTables)
        );
    }

}
