<?php

//include '../../../mainfile.php';

class SyncCompareCatalog {

    private $db = null;
    private $metadata = null;
    private $metadataAdded = false;

    /*
     * tableName : {
     *      createTable: TRUE/FALSE
     *      fields: [...]   # list of fields in this table
     *      inserts: [
     *          { record: [..], metadata: [..] }
     *      ]
     *      updates: [
     *          { record, metadata} // contains entire record, with updated values in it
     *      ]
     *      metadataFields: [...] # list of fields for the metadata
     * }
     */
    private $changes = array();

    function __construct() {
        $this->db = new \PDO('mysql'.':host='.XOOPS_DB_HOST.';dbname='.XOOPS_DB_NAME, XOOPS_DB_USER, XOOPS_DB_PASS);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // pull metadata from xoops_version file
        $module_handler = xoops_gethandler('module');
        $formulizeModule = $module_handler->getByDirname("formulize");
        $this->metadata = $formulizeModule->getInfo();
    }

    function __destruct() {
        $this->db = null;
        $this->metadata = null;
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
            $primaryField = $this->getPrimaryField($tableName);
            $recPrimaryValue = $record[array_search($primaryField, $fields)];

            $result = $this->getRecord($tableName, $primaryField, $recPrimaryValue);
            $recordExists = $result->rowCount() > 0;

            if (!$recordExists) {
                $this->addRecChange("insert", $tableName, $fields, $record);
            } else {  // if the record exists, compare the data values, add any update statement to $compareResults
                $dbRecord = $result->fetchAll()[0];

                // compare each record field for changes
                $isChanged = false;
                for ($i = 0; $i < count($record); $i++) {
                    $field = $fields[$i];
                    $value = $record[$i];
                    $dbValue = (string)$dbRecord[$field];
                    if ($dbValue != $value) {
                        $isChanged = true;
                    }
                }
                if ($isChanged) {
                    $this->addRecChange("update", $tableName, $fields, $record);
                }
            }
        }
    }

    public function getSQL() {
        // TODO: return properly ordered array of SQL statements following the below method
        /*
         * method:
         *          - commit inserts & updated for tables that are not to-be-created
         *          - create to-be-created tables
         *          - commit the inserts into the newly created tables
         *  The reason for this is that the tables that will be created are form data tables which rely upon data already
         *      being in other tables upon creation. So we must complete all inserts & updates for other records before
         *      creating the data tables to ensure they are created without errors.
         */
    }

    public function getChanges() {
        if ($this->metadataAdded) {
            return $this->changes;
        }
        else {
            // add metadata to all records
            foreach ($this->changes as $tableName => $tableData) {
                // inserts
                foreach ($tableData["inserts"] as &$insertData) {
                    $insertData["metadata"] = $this->getRecMetadata($tableName, $insertData["record"]);
                }
                // updates
                foreach ($tableData["updates"] as &$insertData) {
                    $insertData["metadata"] = $this->getRecMetadata($tableName, $insertData["record"]);
                }
            }
            $this->metadataAdded = true;
            return $this->changes;
        }
    }

    private function addTableChange($tableName, $fields, $record) {
        // if this is the first record of a table, create the data structure for it
        if (!isset($this->changes[$tableName])) {
            $this->changes[$tableName] = array("fields" => $fields, "inserts" => array(),
                "updates" => array(), "createTable" => TRUE);
        }

        $this->addRecChange("insert", $tableName, $fields, $record);
    }

    private function addRecChange($type, $tableName, $fields, $record) {
        if ($type !== "insert" && $type !== "update") {
            throw new Exception("SyncCompareCatalog::addRecChange() only supports 'insert'/'update' change types.");
        }

        // convert record to associative array
        $data = $this->convertRec($record, $fields);

        // simple modification of change type for indexing into the $changes table data structure
        $typeArrayName = $type.'s';

        // if this is the first record of a table, create the data structure for it
        if (!isset($this->changes[$tableName])) {
            $this->changes[$tableName] = array("fields" => $fields, "inserts" => array(),
                "updates" => array(), "createTable" => FALSE);
        }

        // now add record to the correct list
        $changeTypeList = &$this->changes[$tableName][$typeArrayName];
        array_push($changeTypeList, array("record"=>$data, "metadata"=>array()));
    }

    private function tableExists($tableName) {
        $result = $this->db->query('SHOW TABLES LIKE "'.prefixTable($tableName).'"');
        $tableExists = $result->rowCount() > 0;
        return $tableExists;
    }

    private function getRecord($tableName, $primaryField, $primaryValue) {
        $result = $this->db->query('SELECT * FROM '.prefixTable($tableName).' WHERE '.$primaryField.' = "'.$primaryValue.'"');
        return $result;
    }

    private function getPrimaryField($tableName) {
        $result = $this->db->query('SHOW COLUMNS FROM '.prefixTable($tableName).' WHERE `Key` = "PRI"')->fetchAll();
        if (count($result) > 1) {
            throw new Exception("Synchronization compare for table ".$tableName." returns multiple primary key fields");
        }
        return $result[0]['Field'];
    }

    private function convertRec($record, $fields) {
        $result = array();
        for ($i = 0; $i < count($record); $i++) {
            $key = $fields[$i];
            $val = $record[$i];
            $result[$key] = $val;
        }
        return $result;
    }

    private function getRecMetadata($tableName, $record) {
        // TODO - if required data is not in database then check the $this->changes list
        // TODO - or check $this->changes first then fall back to database? <----!!!!!

        // this will need to search the DB and fall back to the changes struct if not found in DB
        //      due to newly inserted data maybe only being in changes not DB
        $tableMetadata = $this->metadata["table_metadata"];
        if (!array_key_exists($tableName, $tableMetadata)) {
            // table has no metadata if not in the table_metadata list
            return array();
        }

        $tableMetaInfo = $tableMetadata[$tableName];
        $sql = $this->genRecMetadataJoinSQL($tableName, $tableMetaInfo, $record);
        $result = $this->db->query($sql);
        return $result->fetchAll()[0];
    }

    private function genRecMetadataJoinSQL($tableName, $tableMetaInfo, $record) {
        $sqlSelect = 'SELECT ';
        $sqlFrom = 'FROM '.prefixTable($tableName).' t';
        $sqlJoins = array();
        $sqlWhere = 'WHERE ';

        // if the table has fields of its own for metadata, add them
        $tableFieldsCount = count($tableMetaInfo["fields"]);
        if ($tableFieldsCount > 0) {
            for ($i = 0; $i < $tableFieldsCount; $i++) {
                $fieldName = $tableMetaInfo["fields"][$i];
                $sqlSelect .= "t.".$fieldName.", ";
            }
        }

        // now add the information from the join tables
        $joinTableNum = 0;
        if (count($tableMetaInfo["joins"]) > 0) {
            foreach ($tableMetaInfo["joins"] as $joinTableInfo) {
                $joinTableNum += 1;
                $tableAbbrev = "j".$joinTableNum;
                $joinTableName = $joinTableInfo["join_table"];
                $mainTableJoinField = $joinTableInfo["join_field"][0];
                $joinTableJoinField = $joinTableInfo["join_field"][1];
                $joinField = $joinTableInfo["field"];

                // add field for this table join
                $sqlSelect .= $tableAbbrev.'.'.$joinField.', ';

                // add the left join information for this table
                $tableJoinSql = 'LEFT JOIN '.prefixTable($joinTableName).' '.$tableAbbrev.' on ';
                $tableJoinSql .= 't.'.$mainTableJoinField.' = '.$tableAbbrev.'.'.$joinTableJoinField;
                array_push($sqlJoins, $tableJoinSql);
            }
        }

        // remove the unnecessary trailing ', ' on the end of the SQL select fragment
        $sqlSelect = substr($sqlSelect, 0, -2);

        // generate where clause using table primary key and record values
        $primaryField = $this->getPrimaryField($tableName);
        $primaryFieldVal = $record[$primaryField];
        $sqlWhere .= 't.'.$primaryField.' = '.$primaryFieldVal;

        // combine the pieces of the sql statement and return it
        return $sqlSelect.' '.$sqlFrom.' '.implode(" ", $sqlJoins).' '.$sqlWhere;
    }

/*
        "formulize_menu_permissions" => array (
            "fields" => array(),
            "joins" => array(
                array(
                    "join_table" => "formulize_menu",
                    "join_field" => array("menu_id", "menu_id"),
                    "field" => "link_text"
                )
            ),
        ),
 */
}

function prefixTable($tableName) {
    return XOOPS_DB_PREFIX."_".$tableName;
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

    private function genUpdateSQL($tableName, $primaryField, $primaryValue, $field, $value) {
        $sql = 'UPDATE '.$tableName.' SET '.$field.'="'.$value.'" WHERE '.$primaryField.'="'.$primaryValue.'"';
        return $sql;
    }
 */

/*
$tableName = 'formulize_menu_permissions';
$record = array('2','1','1','0');
$fields = array("permission_id","menu_id","group_id","default_screen");
$catalog = new SyncCompareCatalog();
$catalog->addRecord($tableName, $record, $fields);
$record2 = array('1','1','2','0');
$catalog->addRecord($tableName, $record2, $fields);
print_r($catalog->getChanges());
*/

