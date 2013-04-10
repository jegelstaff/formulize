<?php

$cache = strstr($_GET['cache'], ".") ? "" : $_GET['cache']; // don't allow inclusion of badly formed cache filenames, could be hacking attempt
$term = $_GET['q'];
$found = array();

if(file_exists("../../../cache/".$cache)) {
    include "../../../cache/".$cache;
} else {
    include "../../../".$cache; // if the file doesn't exist in the cache folder, then look in the root of the system
}

if(count($found) == 0) {
    if($_GET['allow_new_values']) {
        // write the $term and newterm:$term as the key/value
        $found[] = '["'.$term.'","newvalue:'.$term.'"]';
    } else {
        include_once "../../../mainfile.php";
        global $xoopsConfig;
        if ( file_exists("../language/".$xoopsConfig['language']."/main.php") ) {
            include_once "../language/".$xoopsConfig['language']."/main.php";
        } else {
            include_once "../language/english/main.php";
        }
        $found[]='["'._formulize_NO_MATCH_FOUND.'","none"]';
    }
} else {
    if($_GET['allow_new_values']) {
        // user may want to add a new entry that's a shorter version of an existing entry.
        // for example, let the user add "John" when "John Smith" already exists
        $value_is_new = true;
        foreach ($found as $value) {                    // case insensitive search so if John exists, JOHN cannot be added
            if (0 === stripos($value, "[\"$term\",")) { // match the whole value by searching for the formated value: ["value",
                $value_is_new = false;                  // an exact match was found, so do not add a new value
                break;
            }
        }
        if ($value_is_new) {
            $found[] = '["'.$term.'","newvalue:'.$term.'"]';
        }
    }
}

print "[".implode(",", $found)."]";
