<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Backfills the help text ("Type the password twice for confirmation") onto existing
// userAccountPassword elements that have no description yet. New password elements get this
// description automatically (userAccountPasswordElementHandler::setupAndValidateElementProperties),
// and the System Users form's copy is kept in sync by ensureUsersTableForm()'s drift check; this
// patch covers elements created before the description existed.
//
// Only elements with an empty description are touched, so any custom description an admin set is
// preserved. Idempotent, and gated to run once when the stored dbversion is below 8 (this ships in
// the same dbversion 8 batch as patch 005).
function formulize_patch_006_password_help_text($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 8) {
        return true; // already applied (part of the dbversion 8 batch)
    }

    $helpText = defined('_formulize_USERACCOUNT_PWREPEATDESC')
        ? _formulize_USERACCOUNT_PWREPEATDESC
        : 'Type the password twice for confirmation';

    $sql = "UPDATE " . $xoopsDB->prefix('formulize')
        . " SET ele_desc = " . $xoopsDB->quoteString($helpText)
        . " WHERE ele_type = 'userAccountPassword'"
        . " AND (ele_desc = '' OR ele_desc IS NULL)";
    if (!$xoopsDB->queryF($sql)) {
        echo '<p>Error: failed to set help text on userAccountPassword elements: '
            . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    echo '<p>Added the password confirmation help text to existing account-password fields.</p>';
    return true;
}
