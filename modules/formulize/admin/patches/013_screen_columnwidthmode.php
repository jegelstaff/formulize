<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Adds the columnwidthmode column to the list of entries screen table (issue #919). It records how
// the width of columns in a list view table is determined: 'natural' (each column sized to fit its
// content, the new default), 'full' (the table stretches to fill its container - the behaviour every
// list screen had before this column existed), or 'fixed' (every column gets the same pixel width,
// taken from the existing columnwidth setting).
//
// The column defaults to 'natural', which is the new default for list views (issue #919), and is
// already the right value for every existing screen that had columnwidth = 0. Screens with a nonzero
// columnwidth were relying on the old fixed pixel width behaviour, so this patch flips those
// specifically to 'fixed', preserving their existing pixel value and rendering. Screens that migrate
// to 'natural' switch from the old always-stretch-to-100% rendering to content-based column widths.
//
// A new site has always had this column, because it is in sql/mysql.sql. listOfEntriesScreen.php
// names the column when it saves a list screen, and saving one fails on a site where the column is
// missing.
//
// Gated to run once, when the stored dbversion is below 21.
function formulize_patch_013_screen_columnwidthmode($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 21) {
        return true; // already applied
    }

    $listScreenTable = $xoopsDB->prefix('formulize_screen_listofentries');

    $existing = $xoopsDB->queryF("SHOW COLUMNS FROM $listScreenTable LIKE 'columnwidthmode'");
    if ($existing AND $xoopsDB->getRowsNum($existing) > 0) {
        echo '<p>Column width mode option for list screens already added. result: OK</p>';
        return true;
    }

    if (!$xoopsDB->queryF("ALTER TABLE $listScreenTable ADD `columnwidthmode` varchar(10) NOT NULL default 'natural'")) {
        echo '<p>013_screen_columnwidthmode: could not add the columnwidthmode column to the list of entries screen table: '
            . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    if (!$xoopsDB->queryF("UPDATE $listScreenTable SET columnwidthmode = 'fixed' WHERE columnwidth <> 0")) {
        echo '<p>013_screen_columnwidthmode: could not migrate existing nonzero column-width screens to fixed-width mode: '
            . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    echo '<p>Added the setting for how column widths are determined in list view tables (natural, full'
        . ' width, or fixed pixel width). Screens that had a pixel width set keep that fixed width.'
        . ' Every other screen switches to the new natural, content-based width - the new default for'
        . ' list views - in place of the old always-stretch-to-100% behaviour.</p>';
    return true;
}
