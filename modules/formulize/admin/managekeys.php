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

$apiKeyHandler = xoops_getmodulehandler('apikey', 'formulize');

// delete any expired keys, and/or requested keys
$deleteKey = (isset($_POST['deletekey']) AND $_POST['deletekey']) ? $_POST['deletekey'] : "";
$apiKeyHandler->delete($deleteKey);

// create any new keys requested
if(isset($_POST['uid']) AND isset($_POST['save'])) {
    $apiKeyHandler->insert(intval($_POST['uid']),intval($_POST['expiry']));
}

$member_handler = xoops_gethandler('member');

// gather all keys and send to screen
$allKeys = array();
foreach($apiKeyHandler->get() as $key) {
    $userObject = $member_handler->getUser($key->getVar('uid'));
    $uid = $userObject ? $userObject->getVar('login_name') : 0;
    $allKeys[] = array('user'=>$uid,'key'=>$key->getVar('key'),'expiry'=>$key->getVar('expiry'));
}

// if user has searched for a username, find the matches...
$foundUsers = array();
if($_POST['usersearch']) {
    $criteria = new CriteriaCompo(new Criteria('email', $_POST['usersearch']));
    $criteria->add(new Criteria('login_name', $_POST['usersearch']), 'OR');
    $criteria->add(new Criteria('uname', $_POST['usersearch']), 'OR');
    $criteria->add(new Criteria('name', $_POST['usersearch']), 'OR');
    $users = $member_handler->getUsers($criteria);
    foreach($users as $user) {
        $foundUsers[$user->getVar('uid')] = $user->getVar('name') ? $user->getVar('name') : $user->getVar('uname');
    }
    $adminPage['uids'] = $foundUsers;
}

$adminPage['keys'] = $allKeys;
$adminPage['template'] = "db:admin/managekeys.html";

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = "Manage Keys";

