<?php

//include '../../../mainfile.php';

class SyncCompareCatalog {

    private $db = null;

    // Catalog is an array of structured array, one for each table. The structured arrays contain a list of data about
    //      a change that was found using the addRecord compare
    private $catalog = array();

    // A few arrays to contain the information from records for the commitChanges() function to use later
    public $changes = array();

    function __construct() {
        $this->db = new \PDO('mysql'.':host='.XOOPS_DB_HOST.';dbname='.XOOPS_DB_NAME, XOOPS_DB_USER, XOOPS_DB_PASS);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    function __destruct() {
        $this->db = null; // destroy PDO object to close connection
    }

    public function addRecord($tableName, $record, $fields) {
        // there should be one record value for each field string
        if (count($record) != count($fields)) {
            throw new Exception("compare(...) requires record and fields to have the same number of values");
        }

        // if the table doesn't exist, cause error exception
        if (!$this->tableExists($tableName)) {
            $this->addTableChange($tableName, $fields, $record);
        }
        else {
            $primkeyField = $this->getPrimKey($tableName);
            $recPrimkeyValue = $record[array_search($primkeyField, $fields)];

            $result = $this->getRecord($tableName, $primkeyField, $recPrimkeyValue);
            $recordExists = $result->rowCount() > 0;

            if (!$recordExists) {
                $this->addRecChange("insert", $tableName, $fields, $record);
            } else {  // if the record exists, compare the data values, add any update statement to $compareResults
                $dbRecord = $result->fetchAll()[0];

                // compare each record field for changes
                $updates = array();
                for ($i = 0; $i < count($record); $i++) {
                    $field = $fields[$i];
                    $value = $record[$i];
                    $dbValue = (string)$dbRecord[$field];
                    if ($dbValue != $value) {
                        $updates[$field] = $value;
                    }
                }
                // add in primary key information
                $data = $updates;
                $data[$primkeyField] = $recPrimkeyValue;
                $this->addRecChange("update", $tableName, $fields, $data);
            }
        }
    }

    public function commitChanges() {
        // TODO: implement commiting changes to DB
    }

    private function addTableChange($tableName, $fields, $record) {
        // if this is the first record of a table, create the data structure for it
        if (!isset($this->changes[$tableName])) {
            $this->changes[$tableName] = array("fields" => $fields, "inserts" => array(),
                "updates" => array(), "createTable" => TRUE);
        }

        $this->addRecChange("insert", $tableName, $fields, $record);
    }

    private function addRecChange($type, $tableName, $fields, $data) {
        if ($type !== "insert" && $type !== "update") {
            throw new Exception("SyncCompareCatalog::addRecChange() only supports 'insert'/'update' change types.");
        }

        // simple modification of change type for indexing into the $changes table data structure
        $typeArrayName = $type.'s';

        // if this is the first record of a table, create the data structure for it
        if (!isset($this->changes[$tableName])) {
            $this->changes[$tableName] = array("fields" => $fields, "inserts" => array(),
                "updates" => array(), "createTable" => FALSE);
        }

        // now add record to the correct list
        $changeTypeList = &$this->changes[$tableName][$typeArrayName];
        array_push($changeTypeList, $data);
    }

    private function tableExists($tableName) {
        $result = $this->db->query('SHOW TABLES LIKE "'.$tableName.'"');
        $tableExists = $result->rowCount() > 0;
        return $tableExists;
    }

    private function getRecord($tableName, $primkeyField, $primkeyValue) {
        $result = $this->db->query('SELECT * FROM '.$tableName.' WHERE '.$primkeyField.' = "'.$primkeyValue.'"');
        return $result;
    }

    private function getPrimkey($tableName) {
        $result = $this->db->query('SHOW COLUMNS FROM ' . $tableName . ' WHERE `Key` = "PRI"')->fetchAll();
        if (count($result) > 1) {
            throw new Exception("Synchronization compare for table " . $tableName . " returns multiple primary key fields");
        }
        return $result[0]['Field'];
    }

}

/*
 *
 * // SQL functions not being used but save queries for later
    private function genInsertSQL($tableName, $record, $fields) {
        $sql = 'INSERT INTO '.$tableName.' ('.join(", ", $fields).') VALUES (';

        // add comma seperated list of values
        for ($i = 0; $i < count($fields); $i++) {
            $value = $record[$i];
            $sql .= $value;
            if ($i < count($fields)-1) {
                $sql .= ', ';
            }
        }
        $sql .= ')';

        return $sql;
    }

    private function genUpdateSQL($tableName, $primkeyField, $primkeyValue, $field, $value) {
        $sql = 'UPDATE '.$tableName.' SET '.$field.'="'.$value.'" WHERE '.$primkeyField.'="'.$primkeyValue.'"';
        return $sql;
    }
 */

/*
$tableName = 'if34aeb83_groups';
$record = array('4','Webmasters','Webmasters of this site','Admin');
$fields = array('groupid', 'name', 'description', 'group_type');
$catalog = new SyncCompareCatalog();
$catalog->addRecord($tableName, $record, $fields);
print_r($catalog->changes);
*/

