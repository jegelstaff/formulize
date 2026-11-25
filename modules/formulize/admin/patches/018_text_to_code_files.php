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
// - On a site coming from before dbversion 28, the backslashes Formulize used to remove when it used some of this text
//   are taken out of it, so it works the way it did. See formulize_patch_018_backslashes_shown_before_28(). Full Width
//   Content still in the database is done here as it moves to its file, which happens once: on a retry the file
//   already holds the content, and the database copy is blank.
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
    $codeDir = FORMULIZE_CODE_PATH . '/';

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
        // introtext is dropped first, just below, so while it is still here this has not been done, and once it is
        // gone it has. See formulize_patch_018_backslashes_shown_before_28()
        if ($prev_dbversion < 28 AND in_array('introtext', $textColumns)) {
            if (!formulize_patch_018_backslashes_shown_before_28($codeDir)) {
                return false;
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
    $backslashesRemoved = 0;
    while ($row = $xoopsDB->fetchArray($result)) {
        $eleValue = @unserialize((string) $row['ele_value']);
        if (!is_array($eleValue) OR !isset($eleValue[0]) OR formulize_authoredTextIsEmpty($eleValue[0]) OR (string) $row['ele_handle'] === '') {
            continue;
        }
        $text = (string) $eleValue[0];
        $fileName = $row['ele_type'] . '_' . $row['ele_handle'] . '.php';
        clearstatcache(true, $codeDir . $fileName);
        // Until dbversion 28, Full Width Content was shown with stripslashes() run on it, so the text is written to its
        // file the way visitors saw it. This is only when the file is to be written here: a file with something in it
        // is kept, and was dealt with by formulize_patch_018_backslashes_shown_before_28(). PHP is left alone.
        if ($prev_dbversion < 28 AND $row['ele_type'] == 'fullWidthContent' AND !formulize_patch_018_ranAsPHP($text)
            AND !(is_file($codeDir . $fileName) AND filesize($codeDir . $fileName) > 0)) {
            $asShown = stripslashes($text);
            if ($asShown !== $text) {
                $text = $asShown;
                $backslashesRemoved++;
            }
        }
        if (!$writeAndCheck($fileName, $text)) {
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
    if ($backslashesRemoved) {
        echo '<p>Backslashes that Formulize used to remove when it showed Full Width Content have been taken out of the content of '
            . $backslashesRemoved . ' element' . ($backslashesRemoved == 1 ? '' : 's') . ', so ' . ($backslashesRemoved == 1 ? 'it looks' : 'they look') . ' the same as before. result: OK</p>';
    }
    return true;
}

/**
 * Whether Full Width Content was run as PHP, by the test the element has always used.
 *
 * @param string $text The content
 * @return bool
 */
function formulize_patch_018_ranAsPHP($text) {
    return (strstr($text, '$value=') OR strstr($text, '$value ='));
}

/**
 * Take out of authored text the backslashes Formulize used to remove whenever it used that text, on a site coming from
 * before dbversion 28.
 *
 * Until 28, stripslashes() was run on some text admins write every time it was used, so an apostrophe could be stored as
 * \' and still come out as '. That stopped at 28, and from then the backslashes showed, or broke the markup or code they
 * were in. Running stripslashes() once on what is stored gives exactly what was used before:
 *
 * - Text box and textarea default values. A plain default had its backslashes removed before it was shown, and a PHP
 *   default before it was run, so both are done. A PHP default is in text_<handle>.php or textarea_<handle>.php.
 * - Full Width Content that is in a file but isn't PHP. Content that mentioned $value was put in a file even when it
 *   wasn't run as PHP. Full Width Content still in the database is done as it moves to its file, in the patch above.
 *
 * Full Width Content that is PHP ran as written, and only what it printed had its backslashes removed, so a person has
 * to look at it, and it is listed. Number elements and Captioned Content never had their backslashes removed, so they
 * are left alone.
 *
 * stripslashes() can't be run twice, because a \\ typed to show a real backslash would lose it. So the patch calls this
 * only while the introtext column is still there, just before dropping it, and once anything has been changed, a
 * failure is reported and the rest carries on, rather than stopping the update, so a retry can't repeat it.
 *
 * @param string $codeDir The folder the code files are in, ending with a slash
 * @return bool False only if the elements could not be read, in which case nothing has been changed
 */
function formulize_patch_018_backslashes_shown_before_28($codeDir) {
    global $xoopsDB;
    require_once XOOPS_ROOT_PATH . '/modules/formulize/class/textElement.php'; // ELE_VALUE_TEXT_DEFAULTVALUE
    require_once XOOPS_ROOT_PATH . '/modules/formulize/class/textareaElement.php'; // ELE_VALUE_TEXTAREA_DEFAULTVALUE

    $elementsTable = $xoopsDB->prefix('formulize');
    $sql = "SELECT e.ele_id, e.ele_type, e.ele_handle, e.ele_caption, e.ele_value, f.form_title FROM $elementsTable AS e
        LEFT JOIN " . $xoopsDB->prefix('formulize_id') . " AS f ON f.id_form = e.id_form
        WHERE e.ele_type IN ('text', 'textarea', 'fullWidthContent')";
    if (!$result = $xoopsDB->queryF($sql)) {
        echo '<p>018_text_to_code_files: could not read the elements to check for backslashes: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    // True if the file reads back as exactly this text
    $rewrite = function($fileName, $text) use ($codeDir) {
        if (formulize_writeCodeToFile($fileName, $text) === false) {
            return false;
        }
        clearstatcache(true, $codeDir . $fileName);
        return file_get_contents($codeDir . $fileName) === $text;
    };

    $changed = 0;
    $notChanged = array();
    $toCheck = array();
    while ($row = $xoopsDB->fetchArray($result)) {
        $eleValue = @unserialize((string) $row['ele_value']);
        if (!is_array($eleValue)) {
            continue;
        }
        $type = $row['ele_type'];
        $fileName = (string) $row['ele_handle'] !== '' ? $type . '_' . $row['ele_handle'] . '.php' : '';
        $fileText = '';
        if ($fileName) {
            clearstatcache(true, $codeDir . $fileName);
            $fileText = is_file($codeDir . $fileName) ? (string) file_get_contents($codeDir . $fileName) : '';
        }
        $label = '<li>' . htmlspecialchars((string) $row['form_title']) . ': ' . htmlspecialchars(strip_tags((string) $row['ele_caption']))
            . ' (<code>' . htmlspecialchars((string) $row['ele_handle']) . '</code>, element id ' . intval($row['ele_id']) . ')</li>';

        if ($type == 'fullWidthContent') {
            // the file is what the element showed, when it has something in it
            $shownText = $fileText !== '' ? $fileText : (isset($eleValue[0]) ? (string) $eleValue[0] : '');
            if (strpos($shownText, '\\') === false) {
                continue;
            }
            if (formulize_patch_018_ranAsPHP($shownText)) {
                $toCheck[] = $label;
            } elseif ($fileText !== '') {
                if ($rewrite($fileName, stripslashes($fileText))) {
                    $changed++;
                } else {
                    $notChanged[] = $label;
                }
            }
            continue;
        }

        // text and textarea defaults, in the file when they are PHP, and in the database
        $key = $type == 'text' ? ELE_VALUE_TEXT_DEFAULTVALUE : ELE_VALUE_TEXTAREA_DEFAULTVALUE;
        $didChange = false;
        $failed = false;
        if (strpos($fileText, '\\') !== false) {
            if ($rewrite($fileName, stripslashes($fileText))) {
                $didChange = true;
            } else {
                $failed = true;
            }
        }
        if (isset($eleValue[$key]) AND is_string($eleValue[$key]) AND strpos($eleValue[$key], '\\') !== false) {
            $eleValue[$key] = stripslashes($eleValue[$key]);
            if ($xoopsDB->queryF("UPDATE $elementsTable SET ele_value = " . $xoopsDB->quoteString(serialize($eleValue)) . " WHERE ele_id = " . intval($row['ele_id']))) {
                $didChange = true;
            } else {
                $failed = true;
            }
        }
        if ($failed) {
            $notChanged[] = $label;
        } elseif ($didChange) {
            $changed++;
        }
    }

    if ($changed) {
        echo '<p>Backslashes that Formulize used to remove when it used them have been taken out of the default values of text boxes and textareas, and out of Full Width Content kept in files, so '
            . $changed . ' element' . ($changed == 1 ? '' : 's') . ' work' . ($changed == 1 ? 's' : '') . ' the same as before. result: OK</p>';
    }
    if ($notChanged) {
        echo '<h3>Backslashes that could not be taken out:</h3>';
        echo '<p>Formulize used to remove backslashes (\\) from these when it used them, and doesn&#039;t any more. They could not be changed, so the backslashes will now show, or stop the markup or PHP code working. Remove the backslashes that are only there in front of a \' or a ", for example change \\\' to \':</p><ul>' . implode('', $notChanged) . '</ul>';
    }
    if ($toCheck) {
        echo '<h3>PHP code in Full Width Content to check for backslashes:</h3>';
        echo '<p>Formulize used to remove backslashes (\\) from what the PHP code in these elements printed, and doesn&#039;t any more. The code has been left as it is, because only a person can tell which backslashes it needs. If the page now shows a backslash it shouldn&#039;t, change the code so it doesn&#039;t print one:</p><ul>' . implode('', $toCheck) . '</ul>';
    }
    return true;
}
