<?php

$cache = strstr($_GET['cache'], ".") ? "" : $_GET['cache']; // don't allow inclusion of badly formed cache filenames, could be hacking attempt
$term = $_GET['term'];
$currentlySelectedIds = explode(',', $_GET['current']);
$found = array();

if(file_exists("../../../cache/".$cache)) {
    include "../../../cache/".$cache;
} else {
    include "../../../".$cache; // if the file doesn't exist in the cache folder, then look in the root of the system
}

if (isset($$cache)) {
    // the array variable has the same name as the cache file
    $match_existing_value = false;
    foreach ($$cache as $id => $text) {
        if (!in_array($id, $currentlySelectedIds) AND stristr($text, $term)) {
				    if ($term == $text) {
                // found a value that matches an existing value
                $match_existing_value = true;
            }
            $found[] = array('label'=>$text, 'value'=>$id);
        }
    }

    if($_GET['allow_new_values']) {
        // allow adding new values, so allow this as a new value, except if it matches an existing value
        if (!$match_existing_value AND trim($term)) {
            // write the $term and newterm:$term as the key/value
            include_once "../../../mainfile.php";
            icms::$logger->disableLogger();
            while(ob_get_level()) {
                ob_end_clean();
            }
            global $xoopsConfig;
            if (file_exists("../language/".$xoopsConfig['language']."/main.php")) {
                include_once "../language/".$xoopsConfig['language']."/main.php";
            } else {
                include_once "../language/english/main.php";
            }
            array_unshift($found, array('label'=>_formulize_NEW_VALUE.$term, 'value'=>"newvalue:".$term));
        }
    }
}

if (0 == count((array) $found)) {
    include_once "../../../mainfile.php";
    icms::$logger->disableLogger();
    while(ob_get_level()) {
        ob_end_clean();
    }
    global $xoopsConfig;
    if (file_exists("../language/".$xoopsConfig['language']."/main.php")) {
        include_once "../language/".$xoopsConfig['language']."/main.php";
    } else {
        include_once "../language/english/main.php";
    }
    $found[] = array('label'=>_formulize_NO_MATCH_FOUND, 'value'=>"none");
}

print json_encode($found);
