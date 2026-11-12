<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Adds the "Use only Google authentication" preference (auth_googleonly, in the authentication
// preferences) to sites that don't have it.
//
// It used to be added only by 000_schema_migrations, which sites past dbversion 18 skip, and the
// installer didn't create it, so no site installed since then has it. A new site now gets it from
// install/sql/pdo.mysql.formulize_standalone.sql. It's off by default, as it was before.
//
// Safe to run again: the preference is only added when it isn't there.
function formulize_patch_020_auth_googleonly_setting($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 35) {
        return true; // already applied
    }

    $configTable = $xoopsDB->prefix('config');
    $existing = $xoopsDB->queryF("SELECT conf_id FROM $configTable WHERE conf_modid = 0 AND conf_name = 'auth_googleonly'");
    if (!$existing) {
        echo '<p>020_auth_googleonly_setting: could not check for the preference: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }
    if ($xoopsDB->getRowsNum($existing) > 0) {
        return true; // already there
    }

    $sql = "INSERT INTO $configTable (conf_modid, conf_catid, conf_name, conf_title, conf_value, conf_desc, conf_formtype, conf_valuetype, conf_order)"
        . " VALUES (0, " . ICMS_CONF_AUTH . ", 'auth_googleonly', '_MD_AM_GOOGLEONLY', '0', '_MD_AM_GOOGLEONLYDSC', 'yesno', 'int', 1)";
    if (!$xoopsDB->queryF($sql)) {
        echo '<p>020_auth_googleonly_setting: could not add the preference: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    echo '<p>Added the "Use only Google authentication" preference.</p>';
    return true;
}
