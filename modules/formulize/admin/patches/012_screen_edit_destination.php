<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Adds the editdestination column to the list of entries screen table. It records where the edit icon
// in a list opens an entry: on the full form screen, as it always has, or in the right-side drawer on
// a theme that has one. The column defaults to 'screen', so every list screen that exists keeps the
// behaviour it has now until somebody chooses the drawer for it.
//
// A new site has always had this column, because it is in sql/mysql.sql. Existing sites were meant to
// get it from 000_schema_migrations, but that file was closed to sites at database version 18 and
// above before they did, so this patch adds it for them. listOfEntriesScreen.php names the column when
// it saves a list screen, and saving one fails on a site where the column is missing.
//
// Gated to run once, when the stored dbversion is below 20.
function formulize_patch_012_screen_edit_destination($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 20) {
        return true; // already applied
    }

    $listScreenTable = $xoopsDB->prefix('formulize_screen_listofentries');

    // A site can arrive here with the column already present, since 000_schema_migrations still adds it
    // on a site coming from below database version 18.
    $existing = $xoopsDB->queryF("SHOW COLUMNS FROM $listScreenTable LIKE 'editdestination'");
    if ($existing AND $xoopsDB->getRowsNum($existing) > 0) {
        echo '<p>Edit destination (drawer/screen) option for list screens already added. result: OK</p>';
        return true;
    }

    if (!$xoopsDB->queryF("ALTER TABLE $listScreenTable ADD `editdestination` varchar(10) NOT NULL default 'screen'")) {
        echo '<p>012_screen_edit_destination: could not add the editdestination column to the list of entries screen table: '
            . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    echo '<p>Added the setting for where the edit icon in a list opens an entry. Every list screen opens'
        . ' entries on the full form screen, as it does now, until you choose the right-side drawer for'
        . ' that screen.</p>';
    return true;
}
