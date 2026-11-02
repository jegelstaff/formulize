<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Removes the copy of each template screen's code and template that was kept in the database, in the custom_code
// and template columns of the template screen table. The files in templates/screens/<theme>/<sid>/ - code.php and
// template.html - are what a template screen runs, and what its admin page edits, the same as every other kind of
// screen's templates. Nothing is written out from the copy: the files are the screen, and a file that is missing
// may have been removed on purpose.
//
// A new site has never had these columns from this version on, because they are gone from sql/mysql.sql.
//
// Gated to run once, when the stored dbversion is below 26.
function formulize_patch_015_template_screen_files_only($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 26) {
        return true; // already applied
    }

    $templateTable = $xoopsDB->prefix('formulize_screen_template');
    $dropped = array();
    foreach (array('custom_code', 'template') as $column) {
        $existing = $xoopsDB->queryF("SHOW COLUMNS FROM $templateTable LIKE '$column'");
        if (!$existing OR $xoopsDB->getRowsNum($existing) == 0) {
            continue;
        }
        if (!$xoopsDB->queryF("ALTER TABLE $templateTable DROP COLUMN `$column`")) {
            echo '<p>015_template_screen_files_only: could not remove the ' . $column . ' column from the template screen table: '
                . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
        $dropped[] = $column;
    }

    echo $dropped
        ? '<p>Template screens now keep their code and template only in their files, like every other kind of screen. result: OK</p>'
        : '<p>Template screens already keep their code and template only in their files. result: OK</p>';
    return true;
}
