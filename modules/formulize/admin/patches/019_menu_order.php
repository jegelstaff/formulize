<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Lets the top level of the menu be put in order, and hold links of its own beside the applications.
//
// Adds formulize_menu_order, the order of the applications, "Forms with no app" and the top-level links. An empty
// table means today's order (the applications by name, then "Forms with no app"), so nothing is written into it here.
// And lets formulize_menu_links.appid be negative: top-level links have the application id -1
// (FORMULIZE_MENU_TOP_LEVEL), since 0 is already "Forms with no app". See ai-context/menus.md.
//
// A new site gets both from sql/mysql.sql.
//
// Safe to run again: the table is only created if it is missing, and the column only changed if it is still unsigned.
function formulize_patch_019_menu_order($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 34) {
        return true; // already applied
    }

    $orderTable = $xoopsDB->prefix('formulize_menu_order');
    $existing = $xoopsDB->queryF("SHOW TABLES LIKE '$orderTable'");
    if ($existing AND $xoopsDB->getRowsNum($existing) > 0) {
        echo '<p>The menu order table already exists. result: OK</p>';
    } else {
        $sql = "CREATE TABLE `$orderTable` (
            `order_id` int(11) unsigned NOT NULL auto_increment,
            `item_type` varchar(20) NOT NULL default '',
            `item_id` int(11) NOT NULL default 0,
            `position` int(11) NOT NULL default 0,
            PRIMARY KEY (`order_id`),
            UNIQUE KEY i_menu_order_item (`item_type`, `item_id`)
        ) ENGINE=InnoDB";
        if (!$xoopsDB->queryF($sql)) {
            echo '<p>019_menu_order: could not create the menu order table: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
        echo '<p>Added the menu order table.</p>';
    }

    $linksTable = $xoopsDB->prefix('formulize_menu_links');
    if (!$column = $xoopsDB->queryF("SHOW COLUMNS FROM $linksTable LIKE 'appid'")) {
        echo '<p>019_menu_order: could not read the menu links table: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }
    $columnInfo = $xoopsDB->fetchArray($column);
    if ($columnInfo AND stristr($columnInfo['Type'], 'unsigned')) {
        if (!$xoopsDB->queryF("ALTER TABLE $linksTable MODIFY `appid` int(11) NOT NULL")) {
            echo '<p>019_menu_order: could not let menu links be at the top level of the menu: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
        echo '<p>Menu links can now be at the top level of the menu.</p>';
    } else {
        echo '<p>Menu links can already be at the top level of the menu. result: OK</p>';
    }

    return true;
}
