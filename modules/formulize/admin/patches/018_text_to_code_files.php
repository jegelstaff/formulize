<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Moves text that administrators write out of the database and into files in modules/formulize/code/, alongside the
// other things they write, such as derived value formulas and form procedures:
//
// - A form screen's intro text and thanks text, which were the introtext and thankstext columns of the form screens
//   table. Each one that has something in it is written to multipage_introtext_<screen handle>.php or
//   multipage_thankstext_<screen handle>.php, and once every one of them has been written and read back, the two
//   columns are dropped.
// - The content of Full Width Content and Captioned Content elements, which was only kept in a file when it was PHP
//   code. Now HTML and text are kept there too, in <element type>_<element handle>.php, and the copy in the element's
//   settings in the database is blanked once the file holds it. A file that is already there, with something in it,
//   is what the element has been showing, so it is kept as it is.
//
// A new site has no columns to move, and writes the files from the start.
//
// Safe to run again: a file is only written when it is missing or empty, and the columns are only read while they are
// still there. If anything can't be written, nothing is dropped, and the update can be run again once the folder can be
// written to.
function formulize_patch_018_text_to_code_files($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 33) {
        return true; // already applied
    }

    xoops_getmodulehandler('multiPageScreen', 'formulize'); // for formulizeMultiPageScreen::codeFileName()
    $codeDir = XOOPS_ROOT_PATH . '/modules/formulize/code/';

    // Write the text to its file, unless the file already has something in it, which is then what is kept. True if the
    // file is there afterwards, and when it was written here, if it reads back as exactly this text.
    $writeAndCheck = function($fileName, $text) use ($codeDir) {
        $path = $codeDir . $fileName;
        clearstatcache(true, $path);
        if (is_file($path) AND filesize($path) > 0) {
            return true;
        }
        if (formulize_writeCodeToFile($fileName, $text) === false) {
            return false;
        }
        clearstatcache(true, $path);
        return is_file($path) AND file_get_contents($path) === $text;
    };

    // Form screens
    $table = $xoopsDB->prefix('formulize_screen_multipage');
    $textColumns = array();
    foreach (array('introtext', 'thankstext') as $column) {
        $existing = $xoopsDB->queryF("SHOW COLUMNS FROM $table LIKE '$column'");
        if ($existing AND $xoopsDB->getRowsNum($existing) > 0) {
            $textColumns[] = $column;
        }
    }
    $screensWritten = 0;
    if ($textColumns) {
        $sql = "SELECT s.sid, s.screen_handle, m." . implode(", m.", $textColumns) . " FROM $table AS m, " . $xoopsDB->prefix('formulize_screen') . " AS s WHERE s.sid = m.sid";
        if (!$result = $xoopsDB->queryF($sql)) {
            echo '<p>018_text_to_code_files: could not read the form screens: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
        while ($row = $xoopsDB->fetchArray($result)) {
            foreach ($textColumns as $column) {
                $text = (string) $row[$column];
                if (formulize_authoredTextIsEmpty($text)) {
                    continue;
                }
                $fileName = formulizeMultiPageScreen::codeFileName($column, $row['screen_handle']);
                if (!$fileName) {
                    echo '<p>018_text_to_code_files: form screen ' . intval($row['sid']) . ' has no handle, so its ' . ($column == 'introtext' ? 'intro' : 'thanks') . ' text has nowhere to go. Give the screen a handle on its Settings tab, then run the update again.</p>';
                    return false;
                }
                if (!$writeAndCheck($fileName, $text)) {
                    echo '<p>018_text_to_code_files: could not write ' . htmlspecialchars($codeDir . $fileName) . ' for form screen ' . intval($row['sid']) . '. Check that the web server can write to that folder, then run the update again.</p>';
                    return false;
                }
                $screensWritten++;
            }
        }
        foreach ($textColumns as $column) {
            if (!$xoopsDB->queryF("ALTER TABLE $table DROP COLUMN `$column`")) {
                echo '<p>018_text_to_code_files: could not remove the ' . $column . ' column from form screens: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
                return false;
            }
        }
    }

    // Static content elements
    $elementsTable = $xoopsDB->prefix('formulize');
    if (!$result = $xoopsDB->queryF("SELECT ele_id, ele_type, ele_handle, ele_value FROM $elementsTable WHERE ele_type IN ('fullWidthContent', 'captionedContent')")) {
        echo '<p>018_text_to_code_files: could not read the static content elements: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }
    $elementsMoved = 0;
    while ($row = $xoopsDB->fetchArray($result)) {
        $eleValue = @unserialize((string) $row['ele_value']);
        if (!is_array($eleValue) OR !isset($eleValue[0]) OR formulize_authoredTextIsEmpty($eleValue[0]) OR (string) $row['ele_handle'] === '') {
            continue;
        }
        $fileName = $row['ele_type'] . '_' . $row['ele_handle'] . '.php';
        if (!$writeAndCheck($fileName, (string) $eleValue[0])) {
            echo '<p>018_text_to_code_files: could not write ' . htmlspecialchars($codeDir . $fileName) . ' for element ' . intval($row['ele_id']) . '. Check that the web server can write to that folder, then run the update again.</p>';
            return false;
        }
        $eleValue[0] = '';
        if (!$xoopsDB->queryF("UPDATE $elementsTable SET ele_value = " . $xoopsDB->quoteString(serialize($eleValue)) . " WHERE ele_id = " . intval($row['ele_id']))) {
            echo '<p>018_text_to_code_files: could not update element ' . intval($row['ele_id']) . ': ' . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
        $elementsMoved++;
    }

    echo '<p>Intro and thanks text of form screens, and the content of static content elements, are now kept in files in modules/formulize/code/ ('
        . $screensWritten . ' text' . ($screensWritten == 1 ? '' : 's') . ' from form screens, ' . $elementsMoved . ' element' . ($elementsMoved == 1 ? '' : 's') . '). result: OK</p>';
    return true;
}
