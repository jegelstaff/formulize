<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Removes text in the formulize_other table that no longer goes with an entry's value.
//
// What people type into the box of a radio button or checkbox Other option is kept apart from the entry, in
// formulize_other, and older versions could leave it behind: choosing another option instead of Other kept the text,
// and so did deleting an element or a form. Text like that isn't shown in lists, but searches still found the entry by
// it, the form put it back in the box when Other was chosen again, and synchronization copied it to other sites. Saving
// an entry now removes text its value doesn't use, deleting an element or a form removes its text, and a
// synchronization import tidies up the forms it imports, so this clears what was left from before.
//
// Removed: text for an element, or an element's form, that no longer exists. Then, for each form, what
// formulizeDataHandler::removeOrphanedOtherTexts() removes: text for an entry that no longer exists, text for an entry
// whose value for the element doesn't have the Other option chosen, and any second copy of an entry's text for the
// same element.
//
// A new site has nothing to remove. Running this again removes nothing more.
function formulize_patch_019_orphaned_other_text($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 34) {
        return true; // already applied
    }

    include_once XOOPS_ROOT_PATH . '/modules/formulize/class/data.php';
    $otherTable = $xoopsDB->prefix('formulize_other');
    $elementsTable = $xoopsDB->prefix('formulize');
    $formsTable = $xoopsDB->prefix('formulize_id');
    $removed = 0;
    $leftAlone = 0;

    // text for an element, or an element's form, that no longer exists
    if (!$xoopsDB->queryF("DELETE o FROM $otherTable AS o
        LEFT JOIN $elementsTable AS e ON e.ele_id = o.ele_id
        LEFT JOIN $formsTable AS f ON f.id_form = e.id_form
        WHERE e.ele_id IS NULL OR f.id_form IS NULL")) {
        echo '<p>019_orphaned_other_text: could not remove the Other text of deleted elements: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }
    $removed += $xoopsDB->getAffectedRows();

    // then each form that has Other text
    if (!$formsWithText = $xoopsDB->queryF("SELECT DISTINCT e.id_form FROM $otherTable AS o INNER JOIN $elementsTable AS e ON e.ele_id = o.ele_id")) {
        echo '<p>019_orphaned_other_text: could not read which forms have Other text: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }
    while ($row = $xoopsDB->fetchArray($formsWithText)) {
        $dataHandler = new formulizeDataHandler(intval($row['id_form']));
        if (!$result = $dataHandler->removeOrphanedOtherTexts()) {
            echo '<p>019_orphaned_other_text: could not remove the Other text that form ' . intval($row['id_form']) . "'s entries don't use: " . htmlspecialchars($xoopsDB->error()) . '</p>';
            return false;
        }
        $removed += $result['removed'];
        $leftAlone += $result['leftAlone'];
    }

    echo '<p>Removed Other text that no longer goes with an entry (' . $removed . ' removed'
        . ($leftAlone ? ', and the text of ' . $leftAlone . ' element' . ($leftAlone == 1 ? '' : 's') . ' with no field to compare with was left alone' : '')
        . '). result: OK</p>';
    return true;
}
