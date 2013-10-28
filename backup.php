<?php

include_once "mainfile.php";

error_reporting(E_ALL | E_STRICT);

if ($xoopsUser and userIsInGroup("Webmasters", $xoopsUser)) {
    // send the database content to the user
    header("Content-Transfer-Encoding: Binary");
    #header("Content-length: ".strlen($file_content));
    header("Content-disposition: filename=\"".SDATA_DB_NAME."-".date("Y-m-d", time()).".sql\"");
    header("Content-type: application/octet-stream");
    header("Last-Modified: ".gmdate("D, d M Y H:i:s", time())." GMT");
    backup_tables("localhost", SDATA_DB_USER, SDATA_DB_PASS, SDATA_DB_NAME, '*');
} else {
    header("Location: /");
}

// return true if the user is a member of a group which has $type in its name
function userIsInGroup($type, $user_id_or_object) {
    if(!$user = getUserObjectFromId($user_id_or_object)) {
        return false;
    }
    static $cachedResults = array();
    if(!isset($cachedResults[$user->getVar('uid')][$type])) {
        $groups = $user->getGroups();
        $member_handler = xoops_gethandler('member');
        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('groupid', '('.implode(',', $groups).')', 'IN'), 'AND');
        $criteria->add(new Criteria('name', '%'.$type.'%', 'LIKE'), 'AND');
        $cachedResults[$user->getVar('uid')][$type] = $member_handler->getGroups($criteria);
    }
    if(!empty($cachedResults[$user->getVar('uid')][$type])) {
        return true;
    } else {
        return false;
    }
}

// return a user object if passed in an id, otherwise return an object if passed an object, or false if not.
function getUserObjectFromId($user_id_or_object) {
    if(is_numeric($user_id_or_object)) {
        $member_handler = xoops_gethandler('member');
        return $member_handler->getUser($user_id_or_object);
    } elseif(is_object($user_id_or_object)) {
        return $user_id_or_object;
    } else {
        return false;
    }
}

function backup_tables($host, $user, $pass, $name, $tables = '*')
{
    $link = mysql_connect($host, $user, $pass);
    mysql_select_db($name, $link);

    // get list of tables
    if ($tables == '*')
    {
        $tables = array();
        $result = mysql_query('SHOW TABLES');
        while($row = mysql_fetch_row($result))
        {
            $tables[] = $row[0];
        }
    }
    else
    {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
    }

    // cycle through tables
    foreach($tables as $table)
    {
        $result = mysql_query('SELECT * FROM '.$table);
        $num_fields = mysql_num_fields($result);

        $return.= 'DROP TABLE IF EXISTS '.$table.';';
        $row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));
        $return.= "\n\n".$row2[1].";\n\n";

        echo $return;
        $return = "";

        for ($i = 0; $i < $num_fields; $i++)
        {
            while($row = mysql_fetch_row($result))
            {
                set_time_limit(90);
                $return.= 'INSERT INTO '.$table.' VALUES(';
                for($j=0; $j<$num_fields; $j++)
                {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = ereg_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                    if ($j<($num_fields-1)) { $return.= ','; }
                }
                $return.= ");\n";
            }
            echo $return;
            $return = "";
        }
        $return.="\n\n\n";
        echo $return;
        $return = "";
    }
}
