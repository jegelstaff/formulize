<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Adds the settings on a screen's Appearance tab to the screen table:
// - look: the look the screen's page is shown with, by its key; '' is the look applied to the site;
// - pagewidth: how wide the screen's page can be, 'look' (as wide as the look allows) or 'full';
// - toptext: introductory text, markup printed above the screen's content by the default templates;
// - toptextcode: 1 when the introductory text is edited as code rather than in the rich text editor.
//
// Every existing screen keeps the look the site has. Lists get the full width of the window, which is
// the new default for lists, since they usually need the room for their columns; every other screen
// keeps the width the look gives it. Both can be changed on each screen, and the width a new screen
// of each type starts with is the Default Screen Widths setting.
//
// A new site has always had these columns, because they are in sql/mysql.sql. screen.php names them
// when it saves a screen, and saving one fails on a site where they are missing.
//
// Gated to run once, when the stored dbversion is below 23.
function formulize_patch_014_screen_appearance($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 23) {
        return true; // already applied
    }

    $screenTable = $xoopsDB->prefix('formulize_screen');

    $columns = array(
        'look' => "varchar(100) NOT NULL default ''",
        'pagewidth' => "varchar(10) NOT NULL default 'look'",
        'toptext' => "text NULL",
        'toptextcode' => "tinyint(1) NOT NULL default 0",
    );
    $added = array();
    foreach ($columns as $column => $definition) {
        $existing = $xoopsDB->queryF("SHOW COLUMNS FROM $screenTable LIKE '$column'");
        if ($existing AND $xoopsDB->getRowsNum($existing) > 0) {
            continue;
        }
        if (!$xoopsDB->queryF("ALTER TABLE $screenTable ADD `$column` $definition")) {
            echo '<p>014_screen_appearance: could not add the ' . $column . ' column to the screen table: '
                . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
        $added[] = $column;
    }

    if (!$added) {
        echo '<p>Appearance settings for screens already added. result: OK</p>';
        return true;
    }

    // only when the width column is new, so a site that had it already keeps the widths it has
    if (in_array('pagewidth', $added)) {
        if (!$xoopsDB->queryF("UPDATE $screenTable SET pagewidth = 'full' WHERE type = 'listOfEntries'")) {
            echo '<p>014_screen_appearance: could not set list screens to the full width of the window: '
                . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
    }

    echo '<p>Added an Appearance tab to screens, where each screen can have its own look, use the full width'
        . ' of the window, and have introductory text above it. <b>List screens now use the full width of the'
        . ' window</b>, so their columns have more room. To put a list back to the width of the rest of the'
        . ' site, open the screen and choose <i>Defined by the look</i> under Appearance &rarr; Page width.</p>';
    return true;
}

