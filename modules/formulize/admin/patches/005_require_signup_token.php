<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Provisions the 'requireTokenForSignup' Formulize module preference (Users → Settings → New users)
// on existing installs. Fresh installs get it from xoops_version.php; this patch adds it to systems
// that were installed before the setting existed. Idempotent: skips if the config item already
// exists. Gated to run once, when the stored dbversion is below 8.
function formulize_patch_005_require_signup_token($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 8) {
        return true; // already applied
    }

    $modid = intval(getFormulizeModId());
    if (!$modid) {
        echo '<p>Error: could not resolve the Formulize module id while provisioning requireTokenForSignup.</p>';
        return false;
    }

    $configTable = $xoopsDB->prefix('config');

    // Skip if it already exists (module config: conf_modid = formulize mid, conf_catid = 0).
    $checkRes = $xoopsDB->queryF(
        "SELECT conf_id FROM $configTable WHERE conf_name = 'requireTokenForSignup'"
        . " AND conf_modid = $modid AND conf_catid = 0"
    );
    if ($checkRes && $xoopsDB->getRowsNum($checkRes) > 0) {
        return true;
    }

    // Place it at the end of this module's config order.
    $orderRes = $xoopsDB->queryF("SELECT MAX(conf_order) AS m FROM $configTable WHERE conf_modid = $modid");
    $orderRow = $orderRes ? $xoopsDB->fetchArray($orderRes) : null;
    $confOrder = ($orderRow && $orderRow['m'] !== null) ? intval($orderRow['m']) + 1 : 0;

    // conf_title / conf_desc store the language-constant NAMES (resolved via constant() at display
    // time), matching how every other module config item is stored.
    $sql = "INSERT INTO $configTable (conf_modid, conf_catid, conf_name, conf_title, conf_value, conf_desc, conf_formtype, conf_valuetype, conf_order) VALUES ("
        . $modid . ", 0, "
        . $xoopsDB->quoteString('requireTokenForSignup') . ", "
        . $xoopsDB->quoteString('_MI_formulize_REQUIRETOKENFORSIGNUP') . ", "
        . $xoopsDB->quoteString('0') . ", "
        . $xoopsDB->quoteString('_MI_formulize_REQUIRETOKENFORSIGNUP_DESC') . ", "
        . $xoopsDB->quoteString('yesno') . ", "
        . $xoopsDB->quoteString('int') . ", "
        . $confOrder
        . ")";
    if (!$xoopsDB->queryF($sql)) {
        echo '<p>Error: failed to insert the requireTokenForSignup config item: '
            . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    echo '<p>Added the "Require account tokens for public sign-ups?" setting (Users &rarr; Settings &rarr; New users).</p>';
    return true;
}
