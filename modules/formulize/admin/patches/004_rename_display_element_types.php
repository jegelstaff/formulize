<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Renames the legacy display element types to their new class-based identifiers:
//   ib        -> fullWidthContent
//   areamodif -> captionedContent
// Updates ele_type in the formulize elements table, and renames the corresponding PHP code
// files in /modules/formulize/code/ (used by elements whose content is authored as PHP code).
// The new element class files (fullWidthContentElement.php, captionedContentElement.php) build
// these code-file names from ele_type, so the files must be renamed to match the new types.
//
// Idempotent: only acts on rows/files that still use the old type names. Runs once, when the
// stored dbversion is below 5.
function formulize_patch_004_rename_display_element_types($prev_dbversion, $required_dbversion) {
    if ($prev_dbversion >= 5) {
        return true; // already applied
    }
    global $xoopsDB;

    $typeMap = array(
        'ib' => 'fullWidthContent',
        'areamodif' => 'captionedContent',
    );

    // update the element types in the database
    $totalUpdated = 0;
    foreach ($typeMap as $oldType => $newType) {
        $countRes = $xoopsDB->query("SELECT COUNT(*) FROM " . $xoopsDB->prefix('formulize') . " WHERE ele_type = '" . $oldType . "'");
        if ($countRes AND $countRow = $xoopsDB->fetchRow($countRes)) {
            $totalUpdated += intval($countRow[0]);
        }
        $xoopsDB->queryF("UPDATE " . $xoopsDB->prefix('formulize') . " SET ele_type = '" . $newType . "' WHERE ele_type = '" . $oldType . "'");
    }

    // rename the /code/ files for any of these elements that store PHP code:
    //   <oldtype>_<handle>.php -> <newtype>_<handle>.php
    $codeDir = XOOPS_ROOT_PATH . '/modules/formulize/code';
    $renamedFiles = 0;
    $notWritable = array();
    foreach ($typeMap as $oldType => $newType) {
        foreach ((array)glob($codeDir . '/' . $oldType . '_*.php') as $oldFilePath) {
            $oldFileName = basename($oldFilePath);
            // replace only the leading "<oldtype>" prefix, preserving the "_<handle>.php" remainder
            $newFileName = $newType . substr($oldFileName, strlen($oldType));
            $newFilePath = $codeDir . '/' . $newFileName;
            if (file_exists($newFilePath)) {
                continue; // already migrated
            }
            if (!is_writable($oldFilePath)) {
                $notWritable[] = $oldFileName;
                continue;
            }
            if (rename($oldFilePath, $newFilePath)) {
                $renamedFiles++;
            }
        }
    }

    // report results
    if ($totalUpdated > 0 OR $renamedFiles > 0 OR !empty($notWritable)) {
        echo '<p><strong>Display element types migrated:</strong> ' . intval($totalUpdated) . ' element(s) renamed from the legacy "ib"/"areamodif" types to "fullWidthContent"/"captionedContent".';
        if ($renamedFiles > 0) {
            echo ' ' . intval($renamedFiles) . ' associated code file(s) renamed.';
        }
        if (!empty($notWritable)) {
            echo '<br><strong>WARNING:</strong> the following code file(s) could not be renamed (not writable by the web server) and must be renamed manually: ' . implode(', ', $notWritable) . '. Rename the leading "ib_"/"areamodif_" prefix to "fullWidthContent_"/"captionedContent_".';
        }
        echo '</p>';
    }

    return true;
}
