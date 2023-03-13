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
    private $syncOnlyGroupsInCommon = false;
    private $changes = array();
    private $changeDetails = array(); // Only set on first processing after initial upload, not present in cache.
    private $groupsToSync = array(); // group catalog that is based on the ids and names from the SOURCE system. Only set on first processing after initial upload, not present in cache.
    public $doneFilePaths = array();

    function __construct() {
        // open a connection to the database
        $this->db = new \PDO('mysql'.':host='.XOOPS_DB_HOST.';dbname='.XOOPS_DB_NAME, XOOPS_DB_USER, XOOPS_DB_PASS);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        $this->syncOnlyGroupsInCommon = (isset($_POST['groupsMatch']) AND $_POST['groupsMatch'] == 2) ? true : false; // only set first time through!
        $this->syncOnlyGroupsInCommon = (isset($_GET['groupsMatch']) AND $_GET['groupsMatch'] == 2) ? true : $this->syncOnlyGroupsInCommon; // set subsequent times!
        
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
        if (count((array) $record) != count((array) $fields)) {
            throw new Exception("compare(...) requires record and fields to have the same number of values");
        }

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

            // for groupsInCommon synchronization...
            // no new entries in groups
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
                $changeFound = false;
                $newRecord = array();
                for ($i = 0; $i < count((array) $record); $i++) {
                    $field = $fields[$i];
                    $value = $this->cleanEncoding($record[$i]);
                    $dbValue = $dbRecord[$field];
                    if ($dbValue != $value) {
                        if(!$changeFound) {
                            $changeFound = true;
                            // first time, add record to the change list
                            $newRecord = $this->addRecChange("update", $tableName, $fields, $record);
                            if($newRecord === false) {
                                continue;
                            }
                        }
                        /*if($debugOn) {
                            print "NO MATCH ON FIELD: $field [$dbValue : $value]-- UPDATING RECORD<BR>";
                        }*/
                        $this->addChangeDetail($tableName, $fields, $newRecord, $field, $dbValue, $newRecord[array_search($field, $fields)]);
                    }
                }

                // for groupsInCommon synchronization...
                // make a list of groups that we will import data for -- groups table happens to be the first one we parse, and must be so (this is because the canonical list of tables is seeded with the groups table to begin with before others are added)
                // it's the groups where the entries are identical already
                if($tableName == "groups" AND $this->syncOnlyGroupsInCommon AND !$changeFound) {
                    $this->groupsToSync[$record[array_search('groupid',$fields)]] = $record[array_search('name', $fields)];
                }
            }
        }
    }

    private function cleanEncoding($text) {
        // absurd encoding detection required to know if we should explicitly force UTF-8, which is necessary in case there are Microsoft Smart Quotes and/or accented characters, etc.
        // Because Windows 1252 contains ISO-8859-1, we can convert from Windows 1252 safely. Detection will often incorrectly detect ISO, but all we really care about is that it's one of these and not already UTF-8 for example.
        // When mb_detect_encoding is not in strict mode (last param, default is false), it will return the closest matching encoding, and never be FALSE, so we can always expect one of the declared encodings as the result
        if(mb_detect_encoding($text,'UTF-8,Windows-1252,ISO-8859-1') != 'UTF-8') {
            $text = mb_convert_encoding($text,'UTF-8','Windows-1252'); // from windows to utf-8
        }
        return $text;
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
                list($fields, $changes) = $this->getRecDetails($tableName, $rec, $metadata);
                $descrs[$tableName]["updates"][] = implode(" / ", $metadata);
                $descrs[$tableName]["fields"][] = $fields; 
                $descrs[$tableName]["changes"][] = $changes; 
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
        $this->cacheDetails();
        $this->cacheGroupsToSync();
    }
    
    public function cacheDetails() {
        $sessVarName = "sync-change-details-" .  session_id() . ".cache";
        cacheVar($this->changeDetails, $sessVarName);
    }

    public function cacheFilePath($filePath) {
        $sessVarName = "sync-filepaths-" .  session_id() . ".cache";
        $this->doneFilePaths[] = $filePath;
        cacheVar($this->doneFilePaths, $sessVarName);
    }
    
    public function cacheGroupsToSync() {
        $sessVarName = "sync-groups-to-sync-" .  session_id() . ".cache";
        cacheVar($this->groupsToSync, $sessVarName);
    }
    
    public function loadCachedChanges() {
        // TODO - if loaded changes was successful but an empty array then this returns false
        //           and a "no import data" error is displayed on UI...
        $sessVarName = "sync-changes-" .  session_id() . ".cache";
        $sessVarNameFilePaths = "sync-filepaths-" .  session_id() . ".cache";
        $sessVarNameDetails = "sync-change-details-" .  session_id() . ".cache";
        $sessVarNameGTS = "sync-groups-to-sync-" .  session_id() . ".cache";
        $this->changes = loadCachedVar($sessVarName);
        $this->changeDetails = loadCachedVar($sessVarNameDetails);
        $this->doneFilePaths = loadCachedVar($sessVarNameFilePaths);
        $this->groupsToSync = loadCachedVar($sessVarNameGTS);
        $this->groupsToSync = is_array($this->groupsToSync) ? $this->groupsToSync : array(); // doesn't come out of cache right sometimes?
        return $this->changes ? true : false;
    
    }

    public static function clearCachedChanges() {
        unlink(XOOPS_ROOT_PATH.'/modules/formulize/cache/'."sync-changes-" .  session_id() . ".cache");
        unlink(XOOPS_ROOT_PATH.'/modules/formulize/cache/'."sync-filepaths-" .  session_id() . ".cache");
        unlink(XOOPS_ROOT_PATH.'/modules/formulize/cache/'."sync-changes-" .  session_id() . ".cache");
    }

    public function commitChanges($onlyThisTableName = false) {
        static $numSuccess = 0;
        static $numFail = 0;

        static $processedTables = array();

        foreach ($this->changes as $tableName => $tableData) {
            if(!isset($processedTables[$tableName]) AND (!$onlyThisTableName OR $tableName == $onlyThisTableName)) {
                $fields = $tableData["fields"];
                $this->db->query("SET NAMES utf8mb4"); // necessary for real utf8 multibyte with accents, SET NAMES forces server to understand requests, as well as send data, according to this charset
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

        // convert record to associative array (which implicitly handles encoding too)
        $data = $this->convertRec($record, $fields);
        
        // if we're only doing groups in common, check if this record should be used
        if($this->syncOnlyGroupsInCommon) {
            $failed = $this->dataFailsGroupsInCommon($data, $tableName);
            if($failed === true) { return false; } // a boolean true returned means it failed, we must skip it (and return false up the food chain)
            $data = $failed; // didn't fail so use this data, which has possibly been modified
        }

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
        
        return $record;
    }

    private function addChangeDetail($tableName, $fields, $record, $field, $dbValue, $sourceValue) {
        $data = $this->convertRec($record, $fields); // this is how things are packaged up and then unpacked later when reading them
        $record = sha1(serialize($data));
        $this->changeDetails[$tableName][$record][$field]['db'] = $dbValue;
        $this->changeDetails[$tableName][$record][$field]['sourceValue'] = $sourceValue;
    }
    
    private function dataFailsGroupsInCommon($data, $tableName) {
        $syncGroupsInCommonLists = syncGroupsInCommonLists();
        // if there are no groups to sync set, everything passes (at least groups 1, 2, 3 will be in common if we're synching groups in common)
        if(count($this->groupsToSync) == 0) {
            return $data;
        }
        // if the table is not a table being synched, skip it
        if(in_array($tableName, $syncGroupsInCommonLists['group_tables'])) {
            return true; // true, data fails
        }
        // if a group id field is a reference to a group id that we're not synching, skip the record
        if(isset($syncGroupsInCommonLists['group_id_fields'][$tableName])) {
            foreach($syncGroupsInCommonLists['group_id_fields'][$tableName] as $groupFieldName) {
                if(!isset($this->groupsToSync[$data[$groupFieldName]])) {
                    return true; // true, data fails
                }
            }
        }
        // if a group id key is embedded in a field, we should strip it. Yuck!
        // need to try and detect what method of extraction of id we need to perform
        if(isset($syncGroupsInCommonLists['group_id_embedded'][$tableName])) {
            foreach($syncGroupsInCommonLists['group_id_embedded'][$tableName] as $groupFieldName) {
                // possible situations... comma separated list of groupids, which might have trailing and preceeding commas, or not
                // a serialized array, which has a numeric key, and for each of those a 'groups' key which is probably an array of group ids, but must be unserialized if it's not an array and then after unserialization it will be an array of group ids
                // a serialized array, which has a key 3, or a key formlink_scope, which is a comma separated group id list, and does not have trailing commas or preceeding commas                
                $usRecord = unserialize($data[$groupFieldName]);
                if(is_array($usRecord)) {
                    if(isset($usRecord[0]) AND is_array($usRecord[0]) AND isset($usRecord[0]['groups']) AND isset($usRecord[0]['buttontext']) AND isset($usRecord[0]['applyto'])) {
                        foreach($usRecord as $i=>$customActionMetadata) {
                            if(!is_array($usRecord[$i]['groups'])) {
                                $usRecord[$i]['groups'] = unserialize($usRecord[$i]['groups']);
                            }
                            /*print "customactions groups switch: ";
                            print_r($usRecord[$i]['groups']);
                            print "<br>";*/
                            $usRecord[$i]['groups'] = array_intersect($usRecord[$i]['groups'], array_keys($this->groupsToSync));
                            /*print_r($usRecord[$i]['groups']);
                            print "<br>";*/
                        }
                    } elseif(isset($data['ele_type']) AND $data['ele_type'] == 'select' AND isset($usRecord[3]) AND is_string($usRecord[3]) AND preg_replace("/[^,0-9]/", "", $usRecord[3]) === $usRecord[3]) {
                        /*print "selectbox $groupFieldName switch: ";
                        print $usRecord[3];
                        print "<br>";*/
                        $usRecord[3] = $this->stripGroupsFromCommaList($usRecord[3]);
                        /*print $usRecord[3];
                        print "<br>";*/
                    } elseif(isset($data['ele_type']) AND $data['ele_type'] == 'checkbox' AND isset($usRecord['formlink_scope']) AND is_string($usRecord['formlink_scope']) AND preg_replace("/[^,0-9]/", "", $usRecord['formlink_scope']) === $usRecord['formlink_scope']) {
                        /*print "checkbox $groupFieldName switch: ";
                        print $usRecord['formlink_scope'];
                        print "<br>";*/
                        $usRecord['formlink_scope'] = $this->stripGroupsFromCommaList($usRecord['formlink_scope']);
                        /*print $usRecord['formlink_scope'];
                        print "<br>";*/
                    } 
                    $data[$groupFieldName] = serialize($usRecord);
                } elseif(is_string($data[$groupFieldName]) AND strstr($data[$groupFieldName],',') !== false AND preg_replace("/[^,0-9]/", "", $data[$groupFieldName]) === $data[$groupFieldName]) {
                    /*print "checkbox $groupFieldName switch: ";
                    print $data[$groupFieldName];
                    print "<br>";*/
                    $data[$groupFieldName] = $this->stripGroupsFromCommaList($data[$groupFieldName]);
                    /*print $data[$groupFieldName];
                    print "<br>";*/
                } 
            }
        }
        return $data; // record passes, possibly with modifications based on group_id_embedded
    }
    
    private function stripGroupsFromCommaList($commaSeparatedList) {
        if(!is_string($commaSeparatedList) OR count($this->groupsToSync)==0) { return $commaSeparatedList; }
        $trailingPreceeding = (substr($commaSeparatedList, 0, 1) == ',' AND substr($commaSeparatedList, -1) == ',') ? true : false;
        if($trailingPreceeding) {
            $commaSeparatedList = trim($commaSeparatedList,',');
        }
        $commaSeparatedList = explode(',',$commaSeparatedList);
        $commaSeparatedList = array_intersect($commaSeparatedList, array_keys($this->groupsToSync));
        $commaSeparatedList = implode(',',$commaSeparatedList);
        if($trailingPreceeding) {
            $commaSeparatedList = ','.$commaSeparatedList.',';
        }
        return $commaSeparatedList;
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
        if (count((array) $result) > 1) {
            throw new Exception("Synchronization compare for table ".$tableName." returns multiple primary key fields");
        }
        return $result[0]['Field'];
    }

    private function convertRec($record, $fields) {
        $result = array();
        for ($i = 0; $i < count((array) $record); $i++) {
            $key = $fields[$i];
            $val = $this->cleanEncoding($record[$i]);
            $result[$key] = $val;
        }
        return $result;
    }

    private function getRecDetails($tableName, $record) {
        $fields = array();
        $changes = array();
        $record = sha1(serialize($record));
        foreach($this->changeDetails[$tableName][$record] as $field=>$values) {
            $fields[] = $field;
            $changes[$field]['sourceValue'] = $this->tidyArrayForPrint($values['sourceValue']);
            $changes[$field]['db'] = $this->tidyArrayForPrint($values['db']);
        }
        return array($fields, $changes);
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
        if (isset($tableMetaInfo["joins"]) AND count((array) $tableMetaInfo["joins"]) > 0) {
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
        //return true;
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
        //return true;
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
    
    private function tidyArrayForPrint($array) {
        $usValue = null;
        if(!is_array($array)) {
            $usValue = unserialize($array);
        }
        $tidyValue = is_array($usValue) ? $usValue : $array;
        $tidyValue = $this->recursivePrintSmart($tidyValue);
        $tidyValue = is_array($tidyValue) ? json_encode($tidyValue, JSON_PRETTY_PRINT) : $tidyValue;
        $tidyValue = str_replace("\n", '\n', str_replace('\"', '&quot;"', $tidyValue));
        return $tidyValue;    
    }
    
    private function recursivePrintSmart($value) {
        if(!is_array($value)) {
            return printSmart(removeLanguageTags($this->cleanEncoding($value)), 200);
        } else {
            foreach($value as $k=>$v) {
                $value[$k] = $this->recursivePrintSmart($v);
            }
            return $value;
        }
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

function removeLanguageTags($string) {
    if(defined('EASIESTML_LANGS')) {
        global $icmsConfigMultilang;
        $easiestml_langnames = explode(',', $icmsConfigMultilang['ml_names']);
        $easiestml_langs = explode(',', EASIESTML_LANGS);
        foreach($easiestml_langs as $i=>$langCode) {
            $string = str_replace("[$langCode]", "[".$easiestml_langnames[$i]."]",$string);
            $string = str_replace("[/$langCode]", "[/".$easiestml_langnames[$i]."]",$string);
        }
    }
    return $string;
}


