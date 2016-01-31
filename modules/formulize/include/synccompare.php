<?php

//include '../../../mainfile.php';

/*
 * compare - given some table information and a table records, compare it to the current database
 *
 *  returns: indexed array of ('desc', 'sql') associative arrays that are the changes this record would make to the table
 */
function compare($tableName, $record, $fields, $types=array()) {
    $compareResult = array();

    // there should be one record value for each field string
    if (count($record) != count($fields)) {
        throw new Exception("compare(...) requires record and fields to have the same number of values");
    }

    // set up a database connection
    $pdo = new \PDO('mysql'.':host='.XOOPS_DB_HOST.';dbname='.XOOPS_DB_NAME, XOOPS_DB_USER, XOOPS_DB_PASS);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

    // if the table doesn't exist, add a create table statement to $compareResult
    $tableExists = $pdo->query('SHOW TABLES LIKE "'.$tableName.'"')->rowCount() > 0;
    if (!$tableExists) {
        // create statement for creating table
        $tabledesc = "Creating new table.";
        $tablesql = ""; // TODO: generate create data table SQL
        array_push($compareResult, array("desc" => $tabledesc, "sql" => $tablesql));

        // create statement for inserting record
        $recdesc = "Inserting record into new table.";
        $recsql = ""; // TODO: generate insert record SQL
        array_push($compareResult, array("desc" => $recdesc, "sql" => $recsql));
    }
    else {
        // determine the primary key field for the table
        $result = $pdo->query('SHOW COLUMNS FROM ' . $tableName . ' WHERE `Key` = "PRI"')->fetchAll();
        if (count($result) > 1) {
            throw new Exception("Synchronization compare for table " . $tableName . " returns multiple primary key fields");
        }
        $primkey_field = $result[0]['Field'];

        // use the primary key to check if the record currently exists
        $recordPrimVal = $record[array_search($primkey_field, $fields)];
        $result = $pdo->query('SELECT * FROM '.$tableName.' WHERE '.$primkey_field.' = '.$recordPrimVal);
        $dbRecordExists = $result->rowCount() > 0;

        if (!$dbRecordExists) { // if the record doesn't exist add an insert record statement to $compareResult
            $desc = "Inserting new table record: " . join(",", $record);
            $sql = ""; // TODO: generate insert record SQL
            array_push($compareResult, array("desc" => $desc, "sql" => $sql));
        } else {  // if the record exists, compare the data values, add any update statement to $compareResults
            $dbRecord = $result->fetchAll()[0];

            // compare each record field for changes
            for ($i = 0; $i < count($record); $i++) {
                $field = $fields[$i];
                $value = $record[$i];
                $dbValue = (string)$dbRecord[$field];
                if ($dbValue != $value) {
                    $desc = "Updating field '" . $field . "': '" . $dbValue . "''->'" . $value . "'.";
                    $sql = ""; // TODO: add update record sql
                    array_push($compareResult, array("desc" => $desc, "sql" => $sql));
                }
            }
        }
    }

    return $compareResult;
}

/*
$record = array('1','Webmasters','Webmasters of this site','Admin');
$fields = array('groupid', 'name', 'description', 'group_type');
print_r(compare('if34aeb83_groups', $record, $fields));
*/