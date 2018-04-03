<?php
// check for notification status change request from user
if(strstr($_SERVER['REQUEST_URI'],"edituser.php") AND isset($_POST['submit'])) {
    global $xoopsUser;
    $member_handler = xoops_gethandler('member');
    $currentUserGroups = $member_handler->getGroupsByUser($xoopsUser->getVar('uid'));
    $getDaraNotificationsGroupId = 17;
    $subscribed = $_POST['getDaraNotifications'] == 1 ? true : false;
    if($subscribed AND !in_array($getDaraNotificationsGroupId, $currentUserGroups)) {
        $member_handler->addUserToGroup($getDaraNotificationsGroupId, $xoopsUser->getVar('uid'));
    }
    if(!$subscribed AND in_array($getDaraNotificationsGroupId, $currentUserGroups)) {
        $member_handler->removeUsersFromGroup($getDaraNotificationsGroupId, array($xoopsUser->getVar('uid')));
    }
}