<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Adds 'hr' to the HTML Purifier allowed-elements whitelist (ICMS purifier preference
// purifier_HTML_AllowedElements, config category ICMS_CONF_PURIFIER) on existing installs.
//
// CKEditor emits <hr> for a horizontal rule, but the ~15-year-old default whitelist omitted it, so
// purification stripped horizontal rules out of displayed rich-text values. Fresh installs already
// get 'hr' from install/makedata.php and install/sql/pdo.mysql.formulize_standalone.sql; this brings
// existing installs into line.
//
// No HTMLPurifier cache clearing is needed: its definition cache (trust/cache/htmlpurifier) is keyed
// by a hash of the relevant config directives, so changing the allowed-elements list produces a new
// cache key and the definition is rebuilt automatically on the next purify (the old file is simply
// orphaned). The ICMS config itself is read from the DB (only a per-request in-memory cache), so the
// updated value is picked up on the next request.
//
// Idempotent: 'hr' is only added when not already present, so this is safe to re-run.

function formulize_patch_009_purifier_allow_hr($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 13) {
        return true; // already applied (this batch's dbversion)
    }

    $configTable = $xoopsDB->prefix('config');
    $res = $xoopsDB->queryF(
        "SELECT conf_id, conf_value FROM $configTable"
        . " WHERE conf_name = 'purifier_HTML_AllowedElements' AND conf_catid = " . ICMS_CONF_PURIFIER
    );
    if (!$res || !($row = $xoopsDB->fetchArray($res))) {
        // The purifier config should exist on any ImpressCMS install; if it does not there is nothing
        // to amend, and that is not a reason to fail (and roll back) the whole module update.
        echo '<p>Note: purifier_HTML_AllowedElements preference not found; skipped adding "hr" to the HTML whitelist.</p>';
        return true;
    }

    $allowed = @unserialize($row['conf_value']);
    if (!is_array($allowed)) {
        echo '<p>Note: purifier_HTML_AllowedElements was not a serialized array; skipped adding "hr" to the HTML whitelist.</p>';
        return true;
    }
    if (in_array('hr', $allowed, true)) {
        return true; // already present - nothing to do
    }

    $allowed[] = 'hr';
    $newValue = serialize(array_values($allowed));
    $sql = "UPDATE $configTable SET conf_value = " . $xoopsDB->quoteString($newValue)
        . " WHERE conf_id = " . intval($row['conf_id']);
    if (!$xoopsDB->queryF($sql)) {
        echo '<p>Error: failed to add "hr" to the HTML Purifier allowed-elements whitelist: '
            . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    echo '<p>Added "hr" to the HTML Purifier allowed-elements whitelist so horizontal rules survive purification.</p>';
    return true;
}
