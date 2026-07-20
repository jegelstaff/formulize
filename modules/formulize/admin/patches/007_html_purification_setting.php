<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Provisions the 'formulizeEnforceHtmlPurification' Formulize module preference (Settings -> Advanced ->
// Debugging) on existing installs. Fresh installs get it from xoops_version.php with a default of 1
// (ENFORCE - filter unsafe HTML before display). Existing installs are deliberately provisioned with 0
// (REPORT-ONLY - display data as-is, but log which elements would be filtered), so that upgrading does
// NOT suddenly change how an established site's data displays. The admin can review the log (Log Viewer)
// to see which elements would be affected, then turn enforcement on when ready.
//
// Idempotent: skips if the config item already exists. Gated to run once, when the stored dbversion is
// below 13 (the version that introduced this setting).
function formulize_patch_007_html_purification_setting($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 13) {
        return true; // already applied
    }

    $modid = intval(getFormulizeModId());
    if (!$modid) {
        echo '<p>Error: could not resolve the Formulize module id while provisioning formulizeEnforceHtmlPurification.</p>';
        return false;
    }

    $configTable = $xoopsDB->prefix('config');

    // Skip if it already exists (module config: conf_modid = formulize mid, conf_catid = 0).
    $checkRes = $xoopsDB->queryF(
        "SELECT conf_id FROM $configTable WHERE conf_name = 'formulizeEnforceHtmlPurification'"
        . " AND conf_modid = $modid AND conf_catid = 0"
    );
    if ($checkRes && $xoopsDB->getRowsNum($checkRes) > 0) {
        return true;
    }

    // Place it at the end of this module's config order.
    $orderRes = $xoopsDB->queryF("SELECT MAX(conf_order) AS m FROM $configTable WHERE conf_modid = $modid");
    $orderRow = $orderRes ? $xoopsDB->fetchArray($orderRes) : null;
    $confOrder = ($orderRow && $orderRow['m'] !== null) ? intval($orderRow['m']) + 1 : 0;

    // conf_value is '0' (report-only) for existing installs - see the note at the top of this file.
    // conf_title / conf_desc store the language-constant NAMES (resolved via constant() at display time),
    // matching how every other module config item is stored.
    $sql = "INSERT INTO $configTable (conf_modid, conf_catid, conf_name, conf_title, conf_value, conf_desc, conf_formtype, conf_valuetype, conf_order) VALUES ("
        . $modid . ", 0, "
        . $xoopsDB->quoteString('formulizeEnforceHtmlPurification') . ", "
        . $xoopsDB->quoteString('_MI_formulize_ENFORCEHTMLPURIFICATION') . ", "
        . $xoopsDB->quoteString('0') . ", "
        . $xoopsDB->quoteString('_MI_formulize_ENFORCEHTMLPURIFICATION_DESC') . ", "
        . $xoopsDB->quoteString('yesno') . ", "
        . $xoopsDB->quoteString('int') . ", "
        . $confOrder
        . ")";
    if (!$xoopsDB->queryF($sql)) {
        echo '<p>Error: failed to insert the formulizeEnforceHtmlPurification config item: '
            . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    echo '<p>Added the "Filter unsafe HTML from displayed data?" setting (Settings &rarr; Advanced &rarr; Debugging), set to report-only for this existing install. Review the Log Viewer to see which elements would be filtered, then turn it on when ready.</p>';
    return true;
}
