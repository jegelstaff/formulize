<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Puts each form screen's intro text and thanks text back into the form the admin typed it in.
//
// These used to be HTML encoded every time a form screen was saved, and many saves loaded the screen and saved it
// again, so the stored text could be encoded several times over. undoAllHTMLChars() was then run on it, to peel off
// every layer, wherever it was shown. That also removed the entities an admin had typed on purpose: &nbsp; became a
// plain space and &lt;b&gt; became bold. From this version the text is stored exactly as typed, and shown or run
// without being decoded.
//
// So this sets each stored value to undoAllHTMLChars() of itself, which is exactly what visitors and the admin
// page see today, so nothing changes on any page. Non-breaking space characters are then written back as &nbsp;,
// which renders the same, recovers the &nbsp; that decoding had turned into an invisible character, and keeps it
// visible in the editor. Other entities that decoding already removed can't be told apart from what an admin
// typed, so they stay as they are now.
//
// A new site has no form screens to correct, and stores the text as typed from the start.
//
// Running this again is harmless on text nobody has edited since, because fully decoded text decodes to itself.
// It would decode any entities typed after the first run, so the gate keeps it to once, when the stored dbversion
// is below 28. A site whose update fails at a later patch runs this again on the retry, which is only a risk if
// someone edits a form screen's text in between.
function formulize_patch_016_multipage_text_as_typed($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 28) {
        return true; // already applied
    }

    $table = $xoopsDB->prefix('formulize_screen_multipage');
    if (!$result = $xoopsDB->queryF("SELECT sid, introtext, thankstext FROM $table")) {
        echo '<p>016_multipage_text_as_typed: could not read the form screens: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    $updated = 0;
    while ($row = $xoopsDB->fetchArray($result)) {
        $sets = array();
        foreach (array('introtext', 'thankstext') as $field) {
            $stored = (string) $row[$field];
            $asTyped = str_replace("\u{00A0}", '&nbsp;', undoAllHTMLChars($stored));
            if ($asTyped !== $stored) {
                $sets[] = "`$field` = " . $xoopsDB->quoteString($asTyped);
            }
        }
        if (!$sets) {
            continue;
        }
        if (!$xoopsDB->queryF("UPDATE $table SET " . implode(', ', $sets) . " WHERE sid = " . intval($row['sid']))) {
            echo '<p>016_multipage_text_as_typed: could not save the intro and thanks text of form screen ' . intval($row['sid']) . ': '
                . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
        $updated++;
    }

    echo '<p>Form screen intro and thanks text is now stored as it was typed (' . $updated . ' form screen' . ($updated == 1 ? '' : 's') . ' updated). result: OK</p>';
    return true;
}
