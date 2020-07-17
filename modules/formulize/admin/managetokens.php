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
$groupList = array();
foreach($allGroups as $group) {
    $groupid = $group->getVar('groupid');
    //dont display registered users group since we will always add the user to that group
    if($groupid != XOOPS_GROUP_USERS){
        $groupList[$groupid] =  array('name'=>$group->getVar('name'), 'groupid'=>$groupid);
    }
}


// create any new keys requested
if(isset($_POST['save'])) {

    $groups = "";

    foreach($groupList as $group) {
        $id = $group['groupid'];
        if(isset($_POST[$id ])){
            
            $groups = $groups ." " .$id;

        }
    }
    $tokenHandler->insert($groups,intval($_POST['expiry']), intval($_POST['tokenlength']), intval($_POST['maxuses']));
}

// gather all keys and send to screen
$allKeys = array();
foreach($tokenHandler->get() as $key) {
    //map the ids stored back to the group names for the user's view
    $tokenGroups = explode(" ", $key->getVar('groups'));
    $allKeyGroups = "";
    foreach($tokenGroups as $groupid) {
          $allKeyGroups  =  $allKeyGroups  ." " . $groupList[$groupid]['name'];
    }
    $usesText = $key->getVar('currentuses') == 1 ? 'use' : 'uses';
    $usesLeft = $key->getVar('maxuses') > 0 ? ($key->getVar('maxuses')-$key->getVar('currentuses')) : "Unlimited (".$key->getVar('currentuses')." $usesText so far)";
    $allKeys[] = array('group'=>$allKeyGroups,'key'=>$key->getVar('key'),'expiry'=>$key->getVar('expiry'), 'usesleft'=>$usesLeft);
}

$adminPage['groups'] = $groupList;
$adminPage['keys'] = $allKeys;
$adminPage['template'] = "db:admin/managetokens.html";

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = "Manage Tokens";

