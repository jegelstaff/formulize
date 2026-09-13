<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Adds the embedOrigins column to the screen table. It holds the websites a screen may be embedded
// in, one origin per line, and Formulize sends a Content-Security-Policy: frame-ancestors header for
// any screen that names some. A screen with nothing listed is not restricted, so existing embedding
// keeps working and this patch changes nothing about a running site.
//
// Gated to run once, when the stored dbversion is below 19.
function formulize_patch_011_screen_embed_origins($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 19) {
        return true; // already applied
    }

    $screenTable = $xoopsDB->prefix('formulize_screen');

    // A site can arrive here with the column already present, since it was briefly added by
    // 000_schema_migrations before this patch existed.
    $existing = $xoopsDB->queryF("SHOW COLUMNS FROM $screenTable LIKE 'embedOrigins'");
    if ($existing AND $xoopsDB->getRowsNum($existing) > 0) {
        echo '<p>Allowed embedding websites for screens already added. result: OK</p>';
        return true;
    }

    if (!$xoopsDB->queryF("ALTER TABLE $screenTable ADD `embedOrigins` varchar(1000) NOT NULL default ''")) {
        echo '<p>011_screen_embed_origins: could not add the embedOrigins column to the screen table: '
            . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    echo '<p>Added the setting naming which other websites may embed each screen. Every screen starts'
        . ' with nothing listed, which places no restriction on embedding, exactly as before.</p>';
    return true;
}
