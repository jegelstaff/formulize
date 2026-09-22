<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Widens session.sess_data from TEXT to MEDIUMTEXT.
//
// TEXT holds 65,535 bytes. When a request ends with more encoded session data than that, the write
// in icms_core_Session::writeSession() either gets silently truncated (no strict sql_mode) or is
// rejected outright (strict sql_mode). Truncated data stops mid serialized value, so PHP decodes
// none of it on the next request and $_SESSION comes back empty; a rejected write leaves the row
// holding whatever it had before. Either way the user is logged out mid-work, with no error
// anywhere, on a page load that did nothing wrong. It looks random to them and to us.
//
// The size itself is a bug whenever it happens, and those get fixed where they are. This is about
// the consequence: 64 KB is a small budget to hang a silent logout on, and MEDIUMTEXT gives 256
// times the headroom for a column that only ever allocates what it stores. It turns a catastrophic
// failure into a non-event while the real cause is found.
//
// Gated to run once, when the stored dbversion is below 21.
function formulize_patch_013_session_data_mediumtext($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 21) {
        return true; // already applied
    }

    $sessionTable = $xoopsDB->prefix('session');

    // A site may already have been widened by hand, and there is no reason to narrow a column that
    // someone deliberately made even bigger, so anything at or above MEDIUMTEXT is left alone.
    $existing = $xoopsDB->query("SHOW COLUMNS FROM $sessionTable LIKE 'sess_data'");
    if (!$existing OR !$row = $xoopsDB->fetchArray($existing)) {
        echo '<p>013_session_data_mediumtext: could not read the session table structure: '
            . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    // A site may already have been widened by hand, and there is no reason to narrow a column that
    // someone deliberately made even bigger, so anything at or above MEDIUMTEXT is left as it is.
    // Note this only skips the widening: the lock column below still has to be dealt with, and
    // skipping ahead to a return here would leave such a site without it.
    $type = strtolower($row['Type']);
    if ($type === 'mediumtext' OR $type === 'longtext') {
        echo '<p>Session data column is already ' . htmlspecialchars($type) . '. result: OK</p>';
    } elseif (!$xoopsDB->queryF("ALTER TABLE $sessionTable MODIFY sess_data MEDIUMTEXT NOT NULL")) {
        echo '<p>013_session_data_mediumtext: could not widen sess_data to MEDIUMTEXT: '
            . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    } else {
        echo '<p>Widened the session data column from 64KB to 16MB. Sessions that grow larger than the'
            . ' old limit used to be truncated as they were saved, which logged the user out on their'
            . ' next page load with no error reported anywhere.</p>';
    }

    return formulize_patch_013_add_session_lock_column($sessionTable);
}

// Gives the session lock a column of its own.
//
// icms_core_Session::readSession() marks a session as being worked on so that a second request -
// an AJAX call arriving alongside the first, normally - waits rather than reading a copy that is
// about to be overwritten. It used to mark it by writing the literal value 1 into sess_updated,
// the same column that records when the session was last saved.
//
// That is what made sessions disappear. gcSession() deletes every row whose sess_updated is older
// than the expiry cutoff, and 1 is one second past 1970, so a session with a request in flight was
// always inside the delete window. PHP calls the gc handler immediately after read() in the same
// request, which means a request running garbage collection deleted the session row of every other
// request in flight at that moment. Any request that then read one of those rows, in the gap before
// its owner wrote it back, found no row at all, got an empty $_SESSION, and logged its user out. It
// looks entirely random, because it depends on two unrelated requests overlapping while a third
// happens to be the one in a hundred that runs collection.
//
// With the lock in sess_locked, sess_updated always holds a real timestamp, the collector can tell
// an in-flight session from an expired one, and a lock left behind by a request that died expires
// by itself instead of making that session permanently unreadable or permanently immortal.
function formulize_patch_013_add_session_lock_column($sessionTable) {
    global $xoopsDB;

    $existing = $xoopsDB->query("SHOW COLUMNS FROM $sessionTable LIKE 'sess_locked'");
    if ($existing AND $xoopsDB->getRowsNum($existing) > 0) {
        echo '<p>Session lock column already present. result: OK</p>';
        return true;
    }

    if (!$xoopsDB->queryF("ALTER TABLE $sessionTable ADD `sess_locked` int(10) unsigned NOT NULL default '0'")) {
        echo '<p>013_session_data_mediumtext: could not add the sess_locked column to the session table: '
            . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    // Any session currently showing the old in-flight marker is left by a request that finished long
    // ago, since no request survives this update. Put a real timestamp back so the collector can
    // treat those rows normally instead of keeping them forever.
    $xoopsDB->queryF("UPDATE $sessionTable SET sess_updated = " . time() . " WHERE sess_updated = 1");

    echo '<p>Gave the session lock its own column. Sessions with a request in progress used to be'
        . ' indistinguishable from sessions that expired in 1970, so garbage collection could delete'
        . ' a session while it was being used, logging that user out at random.</p>';
    return true;
}
