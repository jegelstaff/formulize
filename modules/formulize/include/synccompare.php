<?php

include_once '../class/forms.php';

class SyncCompareCatalog {

    // connection to the DB
    private $db = null;

    // $metadata from xoops_versions file
    private $metadata = null;

    /*
     * tableName : {
     *      createTable: TRUE/FALSE
     *      fields: [...]   # list of fields in this table
     *      inserts: [
     *          { record: [..], metadata: [..] }
     *      ]
     *      updates: [
     *          { record: [..], metadata: [..] } // contains entire record, with updated values in it
     *      ]
     * }
     */
    private $changes = array();

    function __construct() {
        // open a connection to the database
        $this->db = new \PDO('mysql'.':host='.XOOPS_DB_HOST.';dbname='.XOOPS_DB_NAME, XOOPS_DB_USER, XOOPS_DB_PASS);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // pull metadata from xoops_version file
        $module_handler = xoops_gethandler('module');
        $formulizeModule = $module_handler->getByDirname("formulize");
        $this->metadata = $formulizeModule->getInfo();
    }

    function __destruct() {
        // explicitly null some variables so they are garbage collected
        $this->db = null;
        $this->metadata = null;
    }

    // === PUBLIC FUNCTIONS ===

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
                $isChanged = FALSE;
                for ($i = 0; $i < count($record); $i++) {
                    $field = $fields[$i];
                    $value = $record[$i];
                    $dbValue = (string)$dbRecord[$field];
                    if ($dbValue != $value) {
                        $isChanged = TRUE;
                    }
                }
                if ($isChanged) {
                    $this->addRecChange("update", $tableName, $fields, $record);
                }
            }
        }
    }

    public function getChanges() {
        return $this->changes;
    }

    public function getFormattedChangeDescrs() {
        $formattedChanges = array();
    }

    public function cacheChanges() {
        $sessVarName = "sync-changes-" .  session_id() . ".cache";
        cacheVar($this->changes, $sessVarName);
    }

    public function loadCachedChanges() {
        // TODO - if loaded changes was successful but an empty array then this returns false
        //           and a "no import data" error is displayed on UI...
        $sessVarName = "sync-changes-" .  session_id() . ".cache";
        $this->changes = loadCachedVar($sessVarName);
        return boolval($this->changes);
    }

    public function commitChanges() {
        $numSuccess = 0;
        $numFail = 0;

        // iterate through, commit all inserts that are not on new tables
        foreach ($this->changes as $tableName => $tableData) {
            $fields = $tableData["fields"];
            if ($tableData["createTable"] == FALSE) {
                foreach ($tableData["inserts"] as $recordData) {
                    ($this->commitInsert($tableName, $recordData["record"], $fields)) ? $numSuccess++ : $numFail++;
                }
            }
        }

        // now commit all updates not on new tables
        foreach ($this->changes as $tableName => $tableData) {
            $fields = $tableData["fields"];
            if ($tableData["createTable"] == FALSE) {
                foreach ($tableData["updates"] as $recordData) {
                    ($this->commitUpdate($tableName, $recordData["record"])) ? $numSuccess++ : $numFail++;
                }
            }
        }

        // now create all the data tables and insert the new records into them
        foreach ($this->changes as $tableName => $tableData) {
            $fields = $tableData["fields"];
            if ($tableData["createTable"] == TRUE) {
                ($this->commitCreateTable($tableName)) ? $numSuccess++ : $numFail++;

                // now insert all records that go into this table
                foreach ($tableData["inserts"] as $recordData) {
                    ($this->commitInsert($tableName, $recordData["record"], $fields)) ? $numSuccess++ : $numFail++;
                }
            }
        }

        return array("success"=>$numSuccess, "fail"=>$numFail);
    }

    // === PRIVATE FUNCTIONS ===

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

        // get all the metadata for the record
        $metadata =  $this->getRecMetadata($tableName, $data);

        // create the desc_fields array
        $descFields = $this->getRecDescFields($tableName);

        // now add record to the correct list
        $changeTypeList = &$this->changes[$tableName][$typeArrayName];
        array_push($changeTypeList, array("record"=>$data, "metadata"=>$metadata, "desc_fields"=>$descFields));
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

    private function getRecDescFields($tableName) {
        $tableMetadata = $this->metadata["table_metadata"];
        if (!array_key_exists($tableName, $tableMetadata)) {
            return array();
        }

        $descFields = $tableMetadata["fields"]; // description fields on this table directly
        foreach ($tableMetadata["joins"] as $joinTableInfo) {
            array_push($descFields, $joinTableInfo["field"]);
        }

        return $descFields;
    }

    private function getRecMetadata($tableName, $record) {
        // TODO - determine if we need to worry about getting metadata from $this->changes, then fall back to the DB?

        // table has no metadata if not in the table_metadata list
        $tableMetadata = $this->metadata["table_metadata"];
        if (!array_key_exists($tableName, $tableMetadata)) {
            return array();
        }

        $tableMetaInfo = $tableMetadata[$tableName];
        return $this->getRecMetadataFromDB($tableName, $tableMetaInfo, $record);
    }

    private function getRecMetadataFromDB($tableName, $tableMetaInfo, $record) {
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

        // combine the pieces of the sql statement, execute the query, and return the data
        $sql = $sqlSelect.' '.$sqlFrom.' '.implode(" ", $sqlJoins).' '.$sqlWhere;
        $result = $this->db->query($sql);
        return $result->fetchAll()[0];
    }

    // insert a new record into the database
    private function commitInsert($tableName, $record, $fields) {
        $sql = 'INSERT INTO '.prefixTable($tableName).' ('.join(", ", $fields).') VALUES (';

        // add comma separated list of values
        foreach ($record as $field => $value) {
            $sql .= '"'.$value.'", ';
        }
        $sql = substr($sql, 0, -2); // remove the unnecessary trailing ', '
        $sql .= ');'; //close values brackets

        $result = $this->db->query($sql);
        // returns success/failure of query based on number of affected rows
        return $result->rowCount() == 1;
    }

    // update an existing record in the database
    private function commitUpdate($tableName, $record) {
        $primaryField = $this->getPrimaryField($tableName);
        $recPrimaryValue = $record[$primaryField];

        $sql = 'UPDATE '.prefixTable($tableName).' SET ';

        foreach ($record as $field => $value) {
            $sql .= $field.'="'.$value.'", ';
        }

        // remove the unnecessary trailing ', '
        $sql = substr($sql, 0, -2);

        // add the where clause to specify which record to update
        $sql .= ' WHERE '.$primaryField.'="'.$recPrimaryValue.'"';

        $result = $this->db->query($sql);
        // returns success/failure of query based on number of affected rows
        return $result->rowCount() == 1;
    }

    // use the forms class to create a new form data table in the database
    private function commitCreateTable($tableName) {
        // get the fid for the data table based on the table name
        $formHandle = substr($tableName, strlen(XOOPS_DB_PREFIX."_formulize_"));
        $formHandler =& xoops_getmodulehandler('forms', 'formulize');
        $fid = $formHandler->getByHandle($formHandle);

        // create the data table and return the boolean success result
        $success = $formHandler->createDataTable($fid);
        return $success;
    }
}

function prefixTable($tableName) {
    return XOOPS_DB_PREFIX."_".$tableName;
}

function cacheVar($var, $varname) {
    // cleanup any old files from this cached variable
    formulize_scandirAndClean(XOOPS_ROOT_PATH."/modules/formulize/cache/", $varname);

    // serialize variable and write to file in cache
    $filepath = XOOPS_ROOT_PATH . "/modules/formulize/cache/" . $varname;
    file_put_contents($filepath, serialize($var));
}

function loadCachedVar($varname) {
    // cleanup any old files from this cached variable
    formulize_scandirAndClean(XOOPS_ROOT_PATH."/modules/formulize/cache/", $varname);

    // get cached variable and unserialize
    try {
        $fileStr = file_get_contents(XOOPS_ROOT_PATH . "/modules/formulize/cache/".$varname);
    }
    catch (Exception $e) {
        throw $e;
    }
    return unserialize($fileStr);
}
