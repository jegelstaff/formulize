<?php
/**
 * Helps create the page for synchronizing these two systems (DBs)
 * User: Vanessa Synesael
 * Date: 2016-01-16
 */

// only webmasters can interact with this page!
global $xoopsUser;
if(!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
    return;
}



$tokenHandler = xoops_getmodulehandler('token', 'formulize');

// delete any expired keys, and/or requested keys
$deleteKey = (isset($_POST['deletekey']) AND $_POST['deletekey']) ? $_POST['deletekey'] : "";
$tokenHandler->delete($deleteKey);

$member_handler = xoops_gethandler('member');
$allGroups = $member_handler->getGroups();

// Groups that must never be offered as something a signup token can grant:
//  - Webmasters: a token granting site-admin rights would let anyone take over the site.
//  - Anonymous Users: nonsensical, and users are never "members" of it.
//  - Registered Users: everyone who signs up is added to it automatically.
//  - Template groups (is_group_template=1): no user is ever a direct member of these; only the
//    per-entry groups derived from them (created by entries-are-groups forms) hold memberships.
global $xoopsDB;
$excludedGroupIds = array(XOOPS_GROUP_ADMIN, XOOPS_GROUP_USERS, XOOPS_GROUP_ANONYMOUS);
if($templateRes = $xoopsDB->query("SELECT groupid FROM ".$xoopsDB->prefix('groups')." WHERE is_group_template = 1")) {
    while($templateRow = $xoopsDB->fetchArray($templateRes)) {
        $excludedGroupIds[] = intval($templateRow['groupid']);
    }
}

$groupList = array();
foreach($allGroups as $group) {
    $groupid = $group->getVar('groupid');
    if(!in_array($groupid, $excludedGroupIds)){
        $groupList[$groupid] =  array('name'=>$group->getVar('name'), 'groupid'=>$groupid);
    }
}


// create any new keys requested
$tokenError = "";
if(isset($_POST['save'])) {

    $groups = "";

    foreach($groupList as $group) {
        $id = $group['groupid'];
        if(isset($_POST[$id])){
            $groups = $groups ." " .intval($id);
        }
    }
    $customKey = isset($_POST['customkey']) ? trim($_POST['customkey']) : "";
    if($tokenHandler->insert($groups, intval($_POST['expiry']), intval($_POST['tokenlength']), intval($_POST['maxuses']), $customKey) === false AND $customKey !== "") {
        // The only expected failure here is a custom key that is blank once sanitized, or already in use.
        $tokenError = "The custom token value '".htmlspecialchars($customKey, ENT_QUOTES)."' could not be used. It may already be in use, or contain no letters or numbers. Tokens may only contain letters and numbers.";
    }
}

// gather all keys and send to screen
$allKeys = array();
foreach($tokenHandler->get() as $key) {
    //map the ids stored back to the group names for the user's view
    $tokenGroups = explode(" ", trim($key->getVar('groups')));
    $groupNames = array();
    foreach($tokenGroups as $groupid) {
        $groupid = intval($groupid);
        if($groupid AND isset($groupList[$groupid])) {
            $groupNames[] = $groupList[$groupid]['name'];
        }
    }
    $allKeyGroups = implode(", ", $groupNames);
    $usesText = $key->getVar('currentuses') == 1 ? 'use' : 'uses';
    $usesLeft = $key->getVar('maxuses') > 0 ? ($key->getVar('maxuses')-$key->getVar('currentuses')) : "Unlimited (".$key->getVar('currentuses')." $usesText so far)";
    $allKeys[] = array('group'=>$allKeyGroups,'key'=>$key->getVar('key'),'expiry'=>$key->getVar('expiry'), 'usesleft'=>$usesLeft);
}

$adminPage['groups'] = $groupList;
$adminPage['keys'] = $allKeys;
$adminPage['error'] = $tokenError;

