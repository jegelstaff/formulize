<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Provisions the 'formulizePublicAPIAllowedOrigins' Formulize module preference
// (Settings -> Advanced -> Public API) on existing installs, with an empty value, which is
// the same default fresh installs get from xoops_version.php.
//
// Empty means the Public API answers browser requests only from pages on this site itself.
// An administrator adds one website address per line to let Javascript on those sites read
// data through the Public API, or a single asterisk to allow any site. The setting only
// affects browsers; what a caller can actually read is still decided by Formulize
// permissions, either those of the user an API key belongs to, or those of the Anonymous
// group when no key is supplied.
//
// Gated on dbversion 18, so it runs once for anyone whose stored dbversion is below 18.
function formulize_patch_011_public_api_allowed_origins($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 18) {
        return true; // already applied
    }

    $modid = intval(getFormulizeModId());
    if (!$modid) {
        echo '<p>Error: could not resolve the Formulize module id while provisioning formulizePublicAPIAllowedOrigins.</p>';
        return false;
    }

    $configTable = $xoopsDB->prefix('config');

    // Skip if it already exists (module config: conf_modid = formulize mid, conf_catid = 0).
    $checkRes = $xoopsDB->queryF(
        "SELECT conf_id FROM $configTable WHERE conf_name = 'formulizePublicAPIAllowedOrigins'"
        . " AND conf_modid = $modid AND conf_catid = 0"
    );
    if ($checkRes && $xoopsDB->getRowsNum($checkRes) > 0) {
        return true;
    }

    // Place it at the end of this module's config order.
    $orderRes = $xoopsDB->queryF("SELECT MAX(conf_order) AS m FROM $configTable WHERE conf_modid = $modid");
    $orderRow = $orderRes ? $xoopsDB->fetchArray($orderRes) : null;
    $confOrder = ($orderRow && $orderRow['m'] !== null) ? intval($orderRow['m']) + 1 : 0;

    // conf_title / conf_desc store the language-constant NAMES (resolved via constant() at
    // display time), matching how every other module config item is stored.
    $sql = "INSERT INTO $configTable (conf_modid, conf_catid, conf_name, conf_title, conf_value, conf_desc, conf_formtype, conf_valuetype, conf_order) VALUES ("
        . $modid . ", 0, "
        . $xoopsDB->quoteString('formulizePublicAPIAllowedOrigins') . ", "
        . $xoopsDB->quoteString('_MI_formulize_PUBLICAPIALLOWEDORIGINS') . ", "
        . $xoopsDB->quoteString('') . ", "
        . $xoopsDB->quoteString('_MI_formulize_PUBLICAPIALLOWEDORIGINS_DESC') . ", "
        . $xoopsDB->quoteString('textsarea') . ", "
        . $xoopsDB->quoteString('text') . ", "
        . $confOrder
        . ")";
    if (!$xoopsDB->queryF($sql)) {
        echo '<p>Error: failed to insert the formulizePublicAPIAllowedOrigins config item: '
            . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    echo '<p>Added the "Websites allowed to call the Public API" setting (Settings &rarr; Advanced &rarr; Public API). It starts empty, which means only pages on this site can call the Public API from a browser.</p>';
    return true;
}
