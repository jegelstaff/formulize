<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Ensures use_mysession is set to 1. This setting must be enabled for Formulize's
// database-backed session storage to work correctly. Gates on dbversion 5.
function formulize_patch_004_use_mysession($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    $configTable = $xoopsDB->prefix('config');

    $result = $xoopsDB->queryF("SELECT conf_value FROM $configTable WHERE conf_modid = 0 AND conf_name = 'session_name'");
    if (!$result) {
        echo '<p>004_use_mysession: failed to read session_name: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }
    $row = $xoopsDB->fetchRow($result);
    if (empty($row[0])) {
        echo '<p>session_name is not set; leaving use_mysession unchanged.</p>';
        return true;
    }

    if (!$xoopsDB->queryF("UPDATE $configTable SET conf_value = '1' WHERE conf_modid = 0 AND conf_name = 'use_mysession'")) {
        echo '<p>004_use_mysession: failed to set use_mysession to 1: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    echo '<p>Enabled database session storage (use_mysession = 1).</p>';
    return true;
}
