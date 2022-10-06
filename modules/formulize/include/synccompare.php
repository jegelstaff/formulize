<?php

include_once '../class/forms.php';
include_once 'functions.php';

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
    public $doneFilePaths = array();

    function __construct() {
        // open a connection to the database
        $this->db = new \PDO('mysql'.':host='.XOOPS_DB_HOST.';dbname='.XOOPS_DB_NAME, XOOPS_DB_USER, XOOPS_DB_PASS);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        $getModes = 'SELECT @@SESSION.sql_mode';
        $modesSet = false;
        if($res = $this->db->query($getModes)) {
            $modes = $res->fetch( PDO::FETCH_NUM );
            $modes = $modes[0]; // only one result
            $modesSet = true;
            if(strstr($modes, 'STRICT_')) {
                $modes = explode(',', str_replace(array('STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES'), '', $modes)); // remove strict options
                $modes = array_filter($modes); // remove blanks, possibly caused by commas after removed modes
                $setModes = "SET SESSION sql_mode = '".implode(',',$modes)."'";
                if(!$res = $this->db->query($setModes)) {
                    $modesSet = false;
                }
            }
        }
        if(!$modesSet) {
            exit('Error: the database mode could not be set for proper operation of Formulize. Please notify a webmaster immediately. Thank you.');            
        }
        

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
            $result = $this->getRecord($tableName, $record, $fields);
            $recordExists = $result->rowCount() > 0;

            /*static $counter = 0;
            //if($tableName == "formulize_milestones" AND $record[8] == "Standard") {
                $counter++;
                static $debugOn;
                $debugOn = true;
                print "<pre>";
                
                print "Fields: \n\r";
                print_r($fields);
                
                print "source record number $counter:\n\r";
                print_r($record);
                if($recordExists) {
                    $dbRecorddebug = $result->fetchAll();
                    $dbRecorddebug = $dbRecorddebug[0];
                    $dbRecord = $dbRecorddebug;
                    print "target record:\n\r";
                    print_r($dbRecorddebug);
                } else {
                    print "No matching record found!\n\r";
                }
                print "</pre>";
            } else {
                $debugOn = false;
            }*/
            
            if (!$recordExists) {
                $this->addRecChange("insert", $tableName, $fields, $record);
                /*if($debugOn) {
                    print "INSERTING RECORD<BR>";
                }*/
            
            // if the record exists, compare the data values, add any update statement to $compareResults
            // Except for entry_owner_groups, we don't update records there, since if we found an entry, then it already exists, no need to update. Only inserts need to be made (or deletions???)
            } elseif($tableName != "formulize_entry_owner_groups") {
                //if(!$debugOn) {
                $dbRecord = $result->fetchAll();
                $dbRecord = $dbRecord[0];
                //}

                // compare each record field for changes
                for ($i = 0; $i < count($record); $i++) {
                    $field = $fields[$i];
                    $value = $record[$i];
                    $dbValue = $dbRecord[$field];
                    if ($dbValue != $value) {
                        /*if($debugOn) {
                            print "NO MATCH ON FIELD: $field [$dbValue : $value]-- UPDATING RECORD<BR>";
                        }*/
                    $this->addRecChange("update", $tableName, $fields, $record);
                        break;
                    }
                }
            }
        }
    }

    public function getChanges() {
        return $this->changes;
    }

    // returns the record descriptions by table and by updates/inserts/deletes
    public function getChangeDescrs() {
        $descrs = array();

        foreach ($this->changes as $tableName => $tableInfo) {

            $tableName = trim($tableName, "_");

            $descrs[$tableName] = array("inserts"=>array(), "updates"=>array(), "deletes"=>array());
            $descrs[$tableName]["createTable"] = $tableInfo["createTable"];

            foreach ($tableInfo["inserts"] as $rec) {
                $metadata = $this->getRecMetadata($tableName, "insert", $rec);
                $descrs[$tableName]["inserts"][] = implode(" / ", $metadata);
            }

            foreach ($tableInfo["updates"] as $rec) {
                $metadata = $this->getRecMetadata($tableName, "update", $rec);
                $descrs[$tableName]["updates"][] = implode(" / ", $metadata);
            }

            foreach ($tableInfo["deletes"] as $rec) {
                $metadata = $this->getRecMetadata($tableName, "delete", $rec);
                $descrs[$tableName]["deletes"][] = implode(" / ", $metadata);
            }
        }
        return $descrs;
    }

    public function cacheChanges() {
        $sessVarName = "sync-changes-" .  session_id() . ".cache";
        cacheVar($this->changes, $sessVarName);
    }

    public function cacheFilePath($filePath) {
        $sessVarName = "sync-filepaths-" .  session_id() . ".cache";
        $this->doneFilePaths[] = $filePath;
        cacheVar($this->doneFilePaths, $sessVarName);
    }
    
    public function loadCachedChanges() {
        // TODO - if loaded changes was successful but an empty array then this returns false
        //           and a "no import data" error is displayed on UI...
        $sessVarName = "sync-changes-" .  session_id() . ".cache";
        $sessVarNameFilePaths = "sync-filepaths-" .  session_id() . ".cache";
        $this->changes = loadCachedVar($sessVarName);
        $this->doneFilePaths = loadCachedVar($sessVarNameFilePaths);
        return $this->changes ? true : false;
    
    }

    public static function clearCachedChanges() {
        unlink(XOOPS_ROOT_PATH.'/modules/formulize/cache/'."sync-changes-" .  session_id() . ".cache");
        unlink(XOOPS_ROOT_PATH.'/modules/formulize/cache/'."sync-filepaths-" .  session_id() . ".cache");
    }

    public function commitChanges($onlyThisTableName = false) {
        static $numSuccess = 0;
        static $numFail = 0;

        static $processedTables = array();

        foreach ($this->changes as $tableName => $tableData) {
            if(!isset($processedTables[$tableName]) AND (!$onlyThisTableName OR $tableName == $onlyThisTableName)) {
            $fields = $tableData["fields"];
                foreach ($tableData["updates"] as $rec) {
                    ($this->commitUpdate($tableName, $rec)) ? $numSuccess++ : $numFail++;
                }
                foreach ($tableData["inserts"] as $rec) {
                    ($this->commitInsert($tableName, $rec, $fields)) ? $numSuccess++ : $numFail++;
                }
                //print "<BR>FINISHED WITH $tableName<br>";
                    $processedTables[$tableName] = true;
                    if($tableName == $onlyThisTableName) {
                        break;
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
                "updates" => array(), "deletes" => array(), "createTable" => TRUE);
        }

        $this->addRecChange("insert", $tableName, $fields, $record);
    }

    private function addRecChange($type, $tableName, $fields, $record) {
        if ($type !== "insert" && $type !== "update" && $type !== "delete") {
            throw new Exception("SyncCompareCatalog::addRecChange() only supports 'insert'/'update' change types.");
        }

        // convert record to associative array
        $data = $this->convertRec($record, $fields);

        // simple modification of change type for indexing into the $changes table data structure
        $typeArrayName = $type.'s';

        // if this is the first record of a table, create the data structure for it
        if (!isset($this->changes[$tableName])) {
            $this->changes[$tableName] = array("fields" => $fields, "inserts" => array(),
                "updates" => array(), "deletes" => array(), "createTable" => FALSE);
        }

        // now add record to the correct list
        $changeTypeList = &$this->changes[$tableName][$typeArrayName];
        array_push($changeTypeList, $data);
    }

    private function tableExists($tableName) {
        static $checkedTables = array();
        if(!isset($checkedTables[$tableName])) {
        $result = $this->db->query('SHOW TABLES LIKE "'.prefixTable($tableName).'";');
            $checkedTables[$tableName] = $result->rowCount() > 0;
        }
        return $checkedTables[$tableName];
    }

    private function getRecord($tableName, $record, $fields) {
        if($tableName == "formulize_entry_owner_groups") {
            $result = $this->db->query('SELECT * FROM '.prefixTable($tableName).' WHERE fid='.intval($record[array_search("fid", $fields)]).' AND entry_id = '.intval($record[array_search("entry_id", $fields)]).' AND groupid = '.intval($record[array_search("groupid", $fields)]).';');
        } else {
            $primaryField = $this->getPrimaryField($tableName);
            $recPrimaryValue = $record[array_search($primaryField, $fields)];
            $recPrimaryValue = is_numeric($recPrimaryValue) ? $recPrimaryValue : '"'.formulize_db_escape($recPrimaryValue).'"';
            $result = $this->db->query('SELECT * FROM '.prefixTable($tableName).' WHERE '.$primaryField.' = '.$recPrimaryValue.';');    
        }
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

    private function getRecMetadata($tableName, $type, $record) {
        // table has no metadata if not in the table_metadata list
        $tableMetadata = $this->metadata["table_metadata"];
        if (!array_key_exists($tableName, $tableMetadata)) {
            if(isset($record['entry_id'])) { // try the entry id if it exists
                return array('entry_id'=> $record['entry_id']);
            }
        }

        $tableMetaInfo = $tableMetadata[$tableName];

            $metadata = $this->getRecMetadataFromChanges($tableName, $tableMetaInfo, $record);

        return $metadata;
    }

    // this function will search the changes list first then fallback to the DB
    private function getRecMetadataFromChanges($tableName, $tableMetaInfo, $record) {
        $metadata = array();

        // first add the fields from this very table record that might be indicated as metadata
        if ($tableMetaInfo["fields"]) {
            foreach ($tableMetaInfo["fields"] as $field) {
                $metadata[$field] = $record[$field];
            }
        }

        // for joined table fields check the changes list, then fallback to DB
        if (isset($tableMetaInfo["joins"]) AND count($tableMetaInfo["joins"]) > 0) {
            foreach ($tableMetaInfo["joins"] as $joinTableInfo) {
                
                $joinTableName = $joinTableInfo["join_table"];
                $joinTableKey = $joinTableInfo["join_field"][1];
                $joinTableField = $joinTableInfo["field"];
                $recTableKey = $joinTableInfo["join_field"][0];
                $recTableKeyVal = $record[$recTableKey];

                $changesTable = $this->changes[$joinTableName];
                $fieldValue = false;
                if ($changesTable) {
                    foreach ($changesTable["inserts"] as $rec) {
                        if ($rec[$joinTableKey] == $recTableKeyVal) {
                            $fieldValue = $rec[$joinTableField];
                            break;
                        }
                    }
                    if(!$fieldValue) {
                    foreach ($changesTable["updates"] as $rec) {
                        if ($rec[$joinTableKey] == $recTableKeyVal) {
                            $fieldValue = $rec[$joinTableField];
                                break;
                        }
                    }
                    }
                }
                if(!$fieldValue) { // fall back to DB
                    $targetVal = is_numeric($recTableKeyVal) ? $recTableKeyVal : "'".formulize_db_escape($recTableKeyVal)."'";
                    $sql = "SELECT `".$joinTableField."` FROM ".prefixTable($joinTableName)." WHERE `".$joinTableKey."`=".$targetVal.";";
                    $result = $this->db->query($sql)->fetchAll();
                    $fieldValue = $result[0][$joinTableField];
                }
                $metadata[$joinTableField] = $fieldValue;
            }
        }

        return $metadata;
    }

    // insert a new record into the database
    private function commitInsert($tableName, $record, $fields) {
        if(strstr($tableName, "formulize_entry_owner_groups")) {
            unset($fields[array_search("owner_id", $fields)]);
            unset($record['owner_id']);
        }

        $sql = 'INSERT INTO '.prefixTable($tableName).' (`'.join("`, `", $fields).'`) VALUES (';

        // add comma separated list of values
        foreach ($record as $field => $value) {
            //$sanitizedValue = $this->db->quote($value);
            //$sql .= '"'.$sanitizedValue.'", ';
            if(is_numeric($value)) {
                $sql .= $value.", ";
            } else {
                $sql .= '"'.formulize_db_escape($value).'", ';    
            }
            
        }
        $sql = substr($sql, 0, -2); // remove the unnecessary trailing ', '
        $sql .= ');'; //close values brackets

        //file_put_contents(XOOPS_ROOT_PATH."/modules/formulize/temp/importSQL.sql", $sql."\n\r", FILE_APPEND);
        $result = $this->db->query($sql);
        
        // creation operations depend on the metadata being inserted into the db already!
        if($tableName == "formulize_id") {
            $this->commitCreateTable($record['id_form']);
        }
        if($tableName == "formulize") {
            $this->commitCreateField($record['ele_id']);
        }
        
        // returns success/failure of query based on number of affected rows
        return $result->rowCount() == 1;
    }

    // update an existing record in the database
    private function commitUpdate($tableName, $record) {
        if(strstr($tableName, "formulize_entry_owner_groups")) {
            unset($record['owner_id']);
        }

        if($tableName == "formulize") {
            // get the current name, then update the field
            $element_handler = xoops_getmodulehandler('elements', 'formulize');
            $curElement = $element_handler->get($record['ele_id']);
            if($record['ele_handle'] != $curElement->getVar('ele_handle')) {
                $this->commitUpdateField($record['ele_id'], $curElement->getVar('ele_handle'), false, $record['ele_handle']); // false means no datatype, changes to data type have to be made manually
            }
        }
        if($tableName == "formulize_id") {
            // get the current name, then update the table
            $form_handler = xoops_getmodulehandler('forms','formulize');
            $curForm = $form_handler->get($record['id_form']);
            if($record['form_handle'] != $curForm->getVar('form_handle')) {
                $this->commitUpdateTable($curForm->getVar('form_handle'), $record['form_handle'], $curForm);
            }
        }

        $sql = 'UPDATE '.prefixTable($tableName).' SET ';

        foreach ($record as $field => $value) {
            $value = is_numeric($value) ? $value : '"'.formulize_db_escape($value).'"';
            $sql .= "`$field` = $value, ";
        }

        // remove the unnecessary trailing ', '
        $sql = substr($sql, 0, -2);

        // add the where clause to specify which record to update
        if(strstr($tableName, "formulize_entry_owner_groups")) {
            $sql .= ' WHERE fid='.intval($record['fid']).' AND entry_id='.intval($record['entry_id']).';';
        } else {
            $primaryField = $this->getPrimaryField($tableName);
            $recPrimaryValue = $record[$primaryField];
            $recPrimaryValue = is_numeric($recPrimaryValue) ? $recPrimaryValue : '"'.formulize_db_escape($recPrimaryValue).'"';
            $sql .= ' WHERE '.$primaryField.' = '.$recPrimaryValue.';';
        }

        //file_put_contents(XOOPS_ROOT_PATH."/modules/formulize/temp/importSQL.sql", $sql."\n\r", FILE_APPEND);
        $result = $this->db->query($sql);
        // returns success/failure of query based on number of affected rows
        return $result->rowCount() == 1;
    }

    // use the forms class to create a new form data table in the database
    private function commitCreateTable($fid) {
        static $createdTables = array();
        if(!isset($createdTables[$fid])) {
        // get the fid for the data table based on the table name
            $formHandler = xoops_getmodulehandler('forms', 'formulize');
        // create the data table and return the boolean success result
            //file_put_contents(XOOPS_ROOT_PATH."/modules/formulize/temp/importSQL.sql", "Create table $fid\n\r", FILE_APPEND);
            $createdTables[$fid] = $formHandler->createDataTable($fid);
        }
        return $createdTables[$fid];
    }
    
    // add fields to existing datatables
    private function commitCreateField($element) {
        $formHandler = xoops_getmodulehandler('forms', 'formulize');
        return $formHandler->insertElementField($element, false); // we'll specify no datatype and end up with a 'text' field
    }
    
    // remove fields on existing datatables
    private function commitDeleteField($element) {
        $formHandler = xoops_getmodulehandler('forms', 'formulize');
        return $formHandler->deleteElementField($element);
    }

    // rename a table
    private function commitUpdateTable($oldName, $newName, $formObject) {
        $formHandler = xoops_getmodulehandler('forms', 'formulize');
        return $formHandler->renameDataTable($oldName, $newName, $formObject);
    }
    
    // rename/update a field
    private function commitUpdateField($element, $oldName, $dataType=false, $newName="") {
        $formHandler = xoops_getmodulehandler('forms', 'formulize');
        return $formHandler->updateField($element, $oldName, $dataType, $newName);    
    }
    
    
}

function prefixTable($tableName) {
    return XOOPS_DB_PREFIX."_".trim($tableName, "_"); // sometimes it has a preceeding _ and sometimes not!! Ugh
}

function cacheVar($var, $varname) {
    // cleanup any old files from this cached variable
    formulize_scandirAndClean(XOOPS_ROOT_PATH."/modules/formulize/cache/", ".cache");

    // serialize variable and write to file in cache
    $filepath = XOOPS_ROOT_PATH . "/modules/formulize/cache/" . $varname;
    file_put_contents($filepath, serialize($var));
}

function loadCachedVar($varname) {
    // cleanup any old files from this cached variable
    formulize_scandirAndClean(XOOPS_ROOT_PATH."/modules/formulize/cache/", ".cache");

    // get cached variable and unserialize
    try {
        $fileStr = file_get_contents(XOOPS_ROOT_PATH . "/modules/formulize/cache/".$varname);
    }
    catch (Exception $e) {
        throw $e;
    }
    return unserialize($fileStr);
}
