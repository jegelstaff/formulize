<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// When a template group is chosen as part of a group's scope ("the groups whose entries this group can see"),
// its entry groups are now stored with it, the same way they are for a linked element's scope. Template groups
// own no entries, so a scope that held only the template group showed nothing from it. This adds the entry groups
// to the scopes saved before that, except in the scopes of groups from the template group's own form, where the
// template group means the same entry's group. See formulizePermHandler::addEntryGroupsOfTemplateGroups().
//
// A new site has no scopes yet, so it has nothing to do. Running this again changes nothing more, because a group
// is only added to a scope that doesn't have it.

function formulize_patch_022_entry_groups_in_group_scopes($prev_dbversion, $required_dbversion) {

    if ($prev_dbversion >= 38) {
        return true;
    }

    include_once XOOPS_ROOT_PATH . '/modules/formulize/class/usersGroupsPerms.php';
    if (!formulizePermHandler::refreshEntryGroupsInScopes()) {
        global $xoopsDB;
        echo '<p>022_entry_groups_in_group_scopes: could not add entry groups to the group scopes: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    echo '<p>Added entry groups to the group scopes that hold their template groups.</p>';
    return true;
}
