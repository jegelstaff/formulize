<?php
/**
 * Helps create the page for synchronizing these two systems (DBs)
 * User: Vanessa Synesael
 * Date: 2016-01-16
 */


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// only webmasters can interact with this page!
global $xoopsUser;
if(!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
    return;
}

$apiKeyHandler = xoops_getmodulehandler('token', 'formulize');

// delete any expired keys, and/or requested keys
$deleteKey = (isset($_POST['deletekey']) AND $_POST['deletekey']) ? $_POST['deletekey'] : "";
$apiKeyHandler->delete($deleteKey);

// create any new keys requested
if(isset($_POST['uid']) AND isset($_POST['save'])) {
    $apiKeyHandler->insert(intval($_POST['uid']),intval($_POST['expiry']), intval($_POST['$tokenlength']));
}

$member_handler = xoops_gethandler('member');
$users = $member_handler->getUsers();
$userList = array();
foreach($users as $user) {
    $userList[$user->getVar('uid')] = $user->getVar('name');
}

// gather all keys and send to screen
$allKeys = array();
foreach($apiKeyHandler->get() as $key) {
    $allKeys[] = array('user'=>$userList[$key->getVar('uid')],'key'=>$key->getVar('key'),'expiry'=>$key->getVar('expiry'));
}

$adminPage['uids'] = $userList;
$adminPage['keys'] = $allKeys;
$adminPage['template'] = "db:admin/managetokens.html";

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = "Manage Tokens";

