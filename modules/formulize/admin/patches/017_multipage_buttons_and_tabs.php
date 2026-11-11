<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Replaces the navstyle setting of form screens with a setting for each thing it, and the button labels, used to decide:
// showtabs for the tabs across the top, and showprevbutton, showsavebutton, showclosebutton and shownextbutton for the
// four buttons at the bottom. navstyle was 0 for buttons, 1 for tabs, 2 for both and 3 for neither, where "buttons" meant
// the previous and next buttons only. The Save and Close buttons had no setting, and were hidden by blanking their label.
//
// Each screen gets the settings that show exactly what it shows today, with two exceptions. A screen with a Thank You
// page gets the Save and Continue button, since that is the button that leads to the Thank You page, and the settings
// insist on it from now on. And a label left blank now gets the standard text instead of hiding its button, so where
// Save and Go Back or Save and Continue was hidden on only some pages by a blank label, it now shows on all of them.
//
// A new site gets the columns from sql/mysql.sql, and has no navstyle.
//
// Safe to run again: a column is only added if it is missing, and the old setting is only read, and then dropped, if it
// is still there.
function formulize_patch_017_multipage_buttons_and_tabs($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 32) {
        return true; // already applied
    }

    $table = $xoopsDB->prefix('formulize_screen_multipage');

    foreach (array('showtabs', 'showprevbutton', 'showsavebutton', 'showclosebutton', 'shownextbutton') as $column) {
        $existing = $xoopsDB->queryF("SHOW COLUMNS FROM $table LIKE '$column'");
        if ($existing AND $xoopsDB->getRowsNum($existing) > 0) {
            continue;
        }
        if (!$xoopsDB->queryF("ALTER TABLE $table ADD `$column` tinyint(1) NOT NULL default 0")) {
            echo '<p>017_multipage_buttons_and_tabs: could not add the ' . $column . ' setting to form screens: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
    }

    $navstyleExists = $xoopsDB->queryF("SHOW COLUMNS FROM $table LIKE 'navstyle'");
    if (!$navstyleExists OR $xoopsDB->getRowsNum($navstyleExists) == 0) {
        echo '<p>Form screens already have a setting for each navigation button. result: OK</p>';
        return true;
    }

    if (!$result = $xoopsDB->queryF("SELECT sid, navstyle, buttontext, finishisdone FROM $table")) {
        echo '<p>017_multipage_buttons_and_tabs: could not read the form screens: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }
    $updated = 0;
    while ($row = $xoopsDB->fetchArray($result)) {
        $navstyle = intval($row['navstyle']);
        $buttonText = @unserialize((string) $row['buttontext']);
        // The rule the buttons followed until now: a screen with no labels stored had the standard ones, and so every
        // button. Once a screen had labels, a button whose label was missing, empty or 0 was not drawn.
        $labelled = function($key) use ($buttonText) {
            return !is_array($buttonText) OR (isset($buttonText[$key]) AND (string) $buttonText[$key] !== '' AND (string) $buttonText[$key] !== '0');
        };
        $paging = ($navstyle == 0 OR $navstyle == 2);
        $settings = array(
            'showtabs' => ($navstyle == 1 OR $navstyle == 2) ? 1 : 0,
            // Save and Go Back is Save and Close on the first page, so it showed if either label did
            'showprevbutton' => ($paging AND ($labelled('prevButtonText') OR $labelled('leaveButtonText'))) ? 1 : 0,
            'showsavebutton' => $labelled('saveButtonText') ? 1 : 0,
            'showclosebutton' => $labelled('closeButtonText') ? 1 : 0,
            // Save and Continue is Save and Finish on the last page. A Thank You page always has it now.
            'shownextbutton' => (intval($row['finishisdone']) == 0 OR ($paging AND ($labelled('nextButtonText') OR $labelled('finishButtonText')))) ? 1 : 0,
        );
        $sets = array();
        foreach ($settings as $column => $value) {
            $sets[] = "`$column` = " . intval($value);
        }
        if (!$xoopsDB->queryF("UPDATE $table SET " . implode(', ', $sets) . " WHERE sid = " . intval($row['sid']))) {
            echo '<p>017_multipage_buttons_and_tabs: could not save the navigation settings of form screen ' . intval($row['sid']) . ': ' . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
        $updated++;
    }

    if (!$xoopsDB->queryF("ALTER TABLE $table DROP COLUMN `navstyle`")) {
        echo '<p>017_multipage_buttons_and_tabs: could not remove the old navigation setting from form screens: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    echo '<p>Form screens now have a setting for the tabs and for each navigation button (' . $updated . ' form screen' . ($updated == 1 ? '' : 's') . ' updated). result: OK</p>';
    return true;
}
