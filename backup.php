<?php

include_once "mainfile.php";

define('MAX_TABLE_BACKUP_SIZE', 20);    // in MB

error_reporting(E_ALL | E_STRICT);

if ($xoopsUser and userIsInGroup("Webmasters", $xoopsUser)) {
    if (isset($_GET["do_backup"])) {
        send_backup();
    } else {
        output_database_size();
    }
} else {
    header("Location: /");
}


function output_database_size() {
    $database_size = get_database_size(SDATA_DB_NAME, SDATA_DB_PREFIX);
echo <<<EOF
<html>
<head>
<style>
body {
    font-family: Helvetica;
    font-size: 13pt;
}
h1 {
    margin-top: 2em;
}
th, td {
    text-align: left;
}
#database-table-list {
    margin-top: 2em;
    margin-bottom: 4em;
}
</style>
</head>
<body>
<center>
<h1>Database Backup</h1>
<p>Database is {$database_size} MB*. <a href="?do_backup=1">Backup Now</a></p>
EOF;

$big_tables = array();
foreach (get_table_list() as $table_name) {
    $table_size = get_table_size(SDATA_DB_NAME, $table_name);
    if ($table_size > MAX_TABLE_BACKUP_SIZE) {
        $big_tables[$table_name] = round($table_size, 2);
    }
}

if (count($big_tables) > 0) {
    echo "<br /><br /><br /><p>The following tables are too large and must be backed up separately.</p>";
    echo "<table id='database-table-list'>";
    echo "<tr><th>Table</th><th style='text-align:right;'>Size</th></tr>";
    foreach ($big_tables as $table_name => $table_size) {
        echo "<tr><td><a href='?do_backup=1&table_name=".urlencode($table_name)."'>$table_name</a></td><td style='text-align:right;'>$table_size MB</td></tr>";
    }
    echo "</table>";
} else {
    echo "<small><p>Each table is smaller than ".MAX_TABLE_BACKUP_SIZE." MB and will be included in the backup file.<p></small>";
}

echo <<<EOF
<small><p>* Size of the database on disk will not match the size of the backup file, which may be much smaller.<p></small>
</center>
</body>
</html>
EOF;
}


function get_database_size($database_name, $table_prefix = "*") {
    if ("*" == $table_prefix) {
        $table_prefix = "";
    } else {
        $table_prefix = "\n    and table_name like '{$table_prefix}%'";
    }
    // sum( data_length + index_length ) / 1024 / 1024 AS "size_in_mb"
    $sql = <<<EOF
SELECT table_schema AS "db_name",
sum(data_length) / 1024 / 1024 AS "size_in_mb"
FROM information_schema.TABLES
where TABLE_SCHEMA like '$database_name'$table_prefix
    and TABLE_ROWS > 0
GROUP BY table_schema;
EOF;
    $row = mysql_fetch_row(mysql_query($sql));
    if ($row and isset($row[1])) {
        return round($row[1], 2);
    }
    return -1;
}


function get_table_size($database_name, $table_name) {
    $sql = <<<EOF
SELECT table_schema AS "db_name",
sum( data_length ) / 1024 / 1024 AS "size_in_mb"
FROM information_schema.TABLES
where TABLE_SCHEMA = '$database_name'
    and TABLE_NAME = '$table_name'
GROUP BY table_schema;
EOF;
    $row = mysql_fetch_row(mysql_query($sql));
    if ($row and isset($row[1])) {
        return round($row[1], 2);
    }
    return -1;
}


function send_backup() {
    // send the database content to the user
    header("Content-Transfer-Encoding: Binary");
    #header("Content-length: ".strlen($file_content));
    header("Content-type: application/octet-stream");
    header("Last-Modified: ".gmdate("D, d M Y H:i:s", time())." GMT");
    if (isset($_GET["table_name"])) {
        // only allow basic characters in the table name. no spaces and no quotes
        $table_name = preg_replace("/[^a-zA-Z0-9_]+/", "", urldecode($_GET["table_name"]));
        header("Content-disposition: filename=\"".SDATA_DB_NAME."-".date("Y-m-d", time())."-".$table_name.".sql\"");
        backup_tables(SDATA_DB_HOST, SDATA_DB_USER, SDATA_DB_PASS, SDATA_DB_NAME, $table_name, false);
    } else {
        header("Content-disposition: filename=\"".SDATA_DB_NAME."-".date("Y-m-d", time()).".sql\"");
        backup_tables(SDATA_DB_HOST, SDATA_DB_USER, SDATA_DB_PASS, SDATA_DB_NAME, '*');
    }
}


// return true if the user is a member of a group which has $type in its name
function userIsInGroup($type, $user_id_or_object) {
    if (!$user = getUserObjectFromId($user_id_or_object)) {
        return false;
    }
    static $cachedResults = array();
    if (!isset($cachedResults[$user->getVar('uid')][$type])) {
        $groups = $user->getGroups();
        $member_handler = xoops_gethandler('member');
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('groupid', '('.implode(',', $groups).')', 'IN'), 'AND');
        $criteria->add(new Criteria('name', '%'.$type.'%', 'LIKE'), 'AND');
        $cachedResults[$user->getVar('uid')][$type] = $member_handler->getGroups($criteria);
    }
    if (!empty($cachedResults[$user->getVar('uid')][$type])) {
        return true;
    } else {
        return false;
    }
}


// return a user object if passed in an id, otherwise return an object if passed an object, or false if not.
function getUserObjectFromId($user_id_or_object) {
    if (is_numeric($user_id_or_object)) {
        $member_handler = xoops_gethandler('member');
        return $member_handler->getUser($user_id_or_object);
    } elseif (is_object($user_id_or_object)) {
        return $user_id_or_object;
    } else {
        return false;
    }
}


function get_table_list() {
    $tables = array();
    $result = mysql_query('SHOW TABLES LIKE "'.SDATA_DB_PREFIX.'%"');
    while($row = mysql_fetch_row($result)) {
        $tables[] = $row[0];
    }
    return $tables;
}


function backup_tables($host, $user, $pass, $name, $tables = '*', $check_size = true) {
    $link = mysql_connect($host, $user, $pass);
    mysql_select_db($name, $link);

    // get list of tables
    if ($tables == '*') {
        $tables = get_table_list();
    } else {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
    }

    // loop through tables
    foreach($tables as $table) {
        echo 'DROP TABLE IF EXISTS '.$table.';';
        $row = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));
        echo "\n\n".$row[1].";\n\n";

        $table_size = get_table_size(SDATA_DB_NAME, $table);
        if ($check_size and $table_size > MAX_TABLE_BACKUP_SIZE) {
            // else: the table is too large to be included in the main dump
            echo "-- table $table is too large ($table_size MB) so the rows have been excluded from this database dump\n\n\n\n";
        } else {
            $result = mysql_query('SELECT * FROM '.$table);
            $num_fields = mysql_num_fields($result);

            while ($row = mysql_fetch_row($result)) {
                set_time_limit(90);
                echo 'INSERT INTO '.$table.' VALUES(';
                for ($j = 0; $j < $num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = ereg_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        echo '"'.$row[$j].'"' ;
                    } else {
                        echo '""';
                    }
                    if ($j < ($num_fields - 1)) {
                        echo ',';
                    }
                }
                echo ");\n";
            }

            echo "\n\n\n";
        }
    }
}
