<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Adds the embedOrigins column to the screen table. It holds the websites a screen may be embedded
// in, one origin per line, and Formulize adds them to the Content-Security-Policy: frame-ancestors
// header it sends for that screen.
//
// From this version every page says that only this site may frame it, until an administrator turns
// embedding on and names the websites allowed, either site-wide or on individual screens. A site that
// is displayed inside another system's frame today stops appearing there after updating until that
// is done, which is what the message below tells the person running the update.
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

    echo '<p>Added the setting naming which other websites may embed each screen.</p>'
        . '<p><b>Other websites can no longer display this site in a frame</b> until you allow them. If this'
        . ' site appears inside another system, such as a learning management system or a portal, turn on'
        . ' <i>Allow this site to be embedded in other websites</i> under Settings &rarr; Advanced &rarr;'
        . ' Embedding in other websites, and name that system\'s address there.</p>';
    return true;
}
