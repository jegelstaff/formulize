<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Provisions the 'formulizeEnforceHtmlPurification' Formulize module preference (Settings -> Advanced ->
// Debugging) on existing installs, with a value of 1 (ENFORCE - filter unsafe HTML before display), which
// is the same default fresh installs get from xoops_version.php.
// If content IS affected switch the setting to report-only, then use the Log Viewer to see exactly
// which elements/entries would be filtered and fix them, before switching enforcement back on.
// Purification strips <button>/onclick, <form>/<input>, <iframe> and <svg>, so interactive markup
// produced by derived elements or template screens is the content most likely to need attention.

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

    // conf_value is '1' (ENFORCE) - matching the fresh-install default, see the note at the top of this file.
    // conf_title / conf_desc store the language-constant NAMES (resolved via constant() at display time),
    // matching how every other module config item is stored.
    $sql = "INSERT INTO $configTable (conf_modid, conf_catid, conf_name, conf_title, conf_value, conf_desc, conf_formtype, conf_valuetype, conf_order) VALUES ("
        . $modid . ", 0, "
        . $xoopsDB->quoteString('formulizeEnforceHtmlPurification') . ", "
        . $xoopsDB->quoteString('_MI_formulize_ENFORCEHTMLPURIFICATION') . ", "
        . $xoopsDB->quoteString('1') . ", "
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

    echo '<p>Added the "Filter unsafe HTML from displayed data?" setting (Settings &rarr; Advanced &rarr; Debugging), turned ON. If any of your content breaks, switch this setting to off - it then runs in report-only mode, and the Log Viewer will show you exactly which elements and entries are affected so you can fix them and turn it back on.</p>';
    return true;
}
