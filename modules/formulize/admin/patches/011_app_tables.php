<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Creates the two tables that back sign-in from the Formulize mobile app:
// formulize_app_devices (per-device tokens) and formulize_app_throttle (sign-in rate limiting).
//
// WHY A SEPARATE DEVICES TABLE, RATHER THAN REUSING THE EXISTING 2FA "remember this device" MECHANISM:
//
// The web mechanism (include/2fa/manage.php) stores a hash of its trust token in the user's
// profile, and tfa_deviceTokenHash() deliberately folds $_SERVER['REMOTE_ADDR'] into that hash so
// a stolen cookie replayed from another network simply won't match. That is the right design for
// a browser, and it is NOT changed by any of this.
//
// It cannot work for a phone. A mobile device changes IP constantly as it moves between cell
// towers and WiFi networks, so an IP-bound token would stop matching several times a day and the
// user would face a 2FA challenge almost every time they opened the app.
//
// So app sign-in gets its own token, stored here and NOT bound to an IP. What compensates for the
// lost IP binding:
//   - the raw token lives in the device's OS keychain/keystore, not in a cookie
//   - it is single-purpose: it can only be exchanged for a session, never used as a password
//   - it is revocable server-side per device (the `revoked` column), which a password is not
//   - it expires (the `expires` column)
//
// Only a hash of the token is stored, so a database disclosure does not yield usable tokens.
//
// `tfa_trusted` records that the token was issued after the user actually passed a 2FA challenge
// on that device. modules/formulize/app/session.php uses it to skip re-challenging, which is the
// "let the app bypass 2FA" behaviour, kept honest by the fact that 2FA was genuinely satisfied
// once on this specific device.
//
// The second table, formulize_app_throttle, backs rate limiting on modules/formulize/app/login.php.
// That endpoint checks a username and password without any prior session, which is a new
// unauthenticated password-guessing surface, and nothing existing covers it: the limits in
// include/2fa/manage.php (TFA_MAX_ATTEMPTS) count wrong 2FA *codes* against a tfa_codes row, which
// only exists once a password has already been accepted. So the password step needs its own
// counter, keyed generically so other app endpoints can share it later.
//
// Ships with dbversion 18 in modules/formulize/xoops_version.php - that bump is what makes the
// update actually get offered and run. The guards below are the separate, belt-and-braces concern
// of what happens once it does run.
//
// Idempotent: each table is guarded on its own existence, so this is safe to re-run at any
// dbversion, and safe to re-run after a partial failure (the first table having been created does
// not prevent the second from being created on a retry).

// $prev_dbversion/$required_dbversion are unused on purpose: every patch is called with them
// positionally by the on_update.php discovery loop, so they must be declared. This patch gates on
// whether the tables exist rather than on the version numbers, which is strictly safer here: a
// site restored from a backup taken mid-upgrade, or one where the tables were dropped by hand,
// gets them back on the next update regardless of what dbversion claims.
function formulize_patch_011_app_tables($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    // Table names are built entirely from the DB prefix and a literal, so there is no user input
    // here to escape - matching how 001_schema_migrations.php checks for existing tables.

    $devicesTable = $xoopsDB->prefix('formulize_app_devices');
    $res = $xoopsDB->queryF("SHOW TABLES LIKE '$devicesTable'");
    if (!$res || $xoopsDB->getRowsNum($res) == 0) {
        // token_hash is a sha256 hex digest (64 chars). UNIQUE both enforces one row per token and
        // provides the index for the lookup on every session exchange, which is the hot path here.
        $sql = "CREATE TABLE `$devicesTable` (
            `device_id` int(11) unsigned NOT NULL auto_increment,
            `uid` int(11) unsigned NOT NULL,
            `token_hash` char(64) NOT NULL,
            `device_name` varchar(255) NOT NULL default '',
            `platform` varchar(20) NOT NULL default '',
            `tfa_trusted` tinyint(1) unsigned NOT NULL default 0,
            `created` int(11) unsigned NOT NULL default 0,
            `last_used` int(11) unsigned NOT NULL default 0,
            `expires` int(11) unsigned NOT NULL default 0,
            `revoked` tinyint(1) unsigned NOT NULL default 0,
            PRIMARY KEY (`device_id`),
            UNIQUE KEY u_token_hash (`token_hash`),
            INDEX i_uid (`uid`)
        ) ENGINE=InnoDB;";
        if (!$xoopsDB->queryF($sql)) {
            echo '<p>Error: failed to create the ' . htmlspecialchars($devicesTable) . ' table, so '
                . 'sign-in from the Formulize mobile app will not work: '
                . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
        echo '<p>Created the ' . htmlspecialchars($devicesTable) . ' table, which stores the revocable '
            . 'per-device tokens used to sign in from the Formulize mobile app.</p>';
    }

    $throttleTable = $xoopsDB->prefix('formulize_app_throttle');
    $res = $xoopsDB->queryF("SHOW TABLES LIKE '$throttleTable'");
    if (!$res || $xoopsDB->getRowsNum($res) == 0) {
        // throttle_key is kept to 190 chars so it can be a PRIMARY KEY under utf8mb4, where the
        // index-length limit works out to 191 characters.
        $sql = "CREATE TABLE `$throttleTable` (
            `throttle_key` varchar(190) NOT NULL,
            `attempts` int(11) unsigned NOT NULL default 0,
            `first_attempt` int(11) unsigned NOT NULL default 0,
            `last_attempt` int(11) unsigned NOT NULL default 0,
            PRIMARY KEY (`throttle_key`),
            INDEX i_last_attempt (`last_attempt`)
        ) ENGINE=InnoDB;";
        if (!$xoopsDB->queryF($sql)) {
            echo '<p>Error: failed to create the ' . htmlspecialchars($throttleTable) . ' table, so '
                . 'rate limiting on mobile app sign-in would not work. Refusing to continue rather '
                . 'than leave the sign-in endpoint unthrottled: '
                . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
        echo '<p>Created the ' . htmlspecialchars($throttleTable) . ' table, which rate limits '
            . 'sign-in attempts from the Formulize mobile app.</p>';
    }

    return true;
}
