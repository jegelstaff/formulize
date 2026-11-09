<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Adds the ele_searchtype column to the elements table: the search interface an element gets when it appears in a list
// and the list doesn't choose one for it. Existing elements start out with it empty, which means "not chosen", so they
// get the default for their kind of element until someone chooses one in the element's settings.
//
// A new site gets the column from sql/mysql.sql.
//
// Gated to run once, when the stored dbversion is below 29.
function formulize_patch_016_element_search_type($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 29) {
        return true; // already applied
    }

    $table = $xoopsDB->prefix('formulize');

    $existing = $xoopsDB->queryF("SHOW COLUMNS FROM $table LIKE 'ele_searchtype'");
    if ($existing AND $xoopsDB->getRowsNum($existing) > 0) {
        echo '<p>Elements already have a search interface setting. result: OK</p>';
        return true;
    }

    if (!$xoopsDB->queryF("ALTER TABLE $table ADD `ele_searchtype` varchar(30) NOT NULL default ''")) {
        echo '<p>016_element_search_type: could not add the search interface setting to the elements table: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    echo '<p>Added the search interface setting to elements. result: OK</p>';
    return true;
}
