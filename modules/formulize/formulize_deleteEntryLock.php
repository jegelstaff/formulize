<?php

// TRIGGERED WHEN A FORMULIZE FORM OR LIST UNLOADS via beacon,
// WHEN THE USER SWITCHES TABS CAUSING A REFRESH OF THE FORM via Jquery
// AND WHEN BUILDING ELEMENTS FOR DISPLAYING ON SCREEN (because of the potential race condition with the beacon and we don't want the beacon to be late to the party and erase the locks the new page just put in place, so delete the locks first, and then make the new ones. By the time the beacon catches up, if it's slow, the deletion will have happened, and the token file will be gone, so no subsequent deletion will take place)

// IF THE WEBMASTER IS BUILDING A CUSTOM INTERFACE RELYING ON RENDERING ELEMENTS ON THEIR OWN, LOCKS WILL NOT WORK RIGHT
// Custom screens (and template screens?) won't have the beacon in place for handling unload events in the window and they don't pass the token through POST like a standard screen does

$userPassedUid = false;
if(!defined('XOOPS_MAINFILE_INCLUDED')) { // if we're requested directly, setup stuff...
    ignore_user_abort(true);
    header("Access-Control-Allow-Origin: *");
    include_once "../../mainfile.php";
    icms::$logger->disableLogger();
    // if no user passed uid when we're requested directly, then do nothing because the request is invalid
    if(isset($_POST['formulize_entry_lock_uid'])) {
        $userPassedUid = $_POST['formulize_entry_lock_uid'];
    } else {
        exit();
    }
}

if(isset($_POST['formulize_entry_lock_token'])) {
    $token = $_POST['formulize_entry_lock_token'];
    global $xoopsUser;
    $uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
    $userPassedUid = $userPassedUid === false ? $uid : $userPassedUid; // if the deletion is happening as part of a normal page load, not ajax, then we'll just use the active uid from the logged in user
    $tokenFileName = XOOPS_ROOT_PATH."/modules/formulize/temp/".str_replace(array('/','\\','.'),'',$token).$userPassedUid.'.token'; // must use the passed uid (if any) to check for the token+uid filename, because if the user logs out, the $uid will be 0 but the stuff we need to delete was catalogued with the old uid!
    if(file_exists($tokenFileName) AND ($uid == 0 OR $uid == $userPassedUid)) { // if $uid is zero, can't validate against the uid from the prior pageload, because the user may have just logged out! But we are validating somewhat by checking for the file name based on the token and prior uid.
        $entriesThatWereLockedThatPageLoad = unserialize(file_get_contents($tokenFileName));
        unlink($tokenFileName);
        unset($_POST['formulize_entry_lock_token']);
        foreach($entriesThatWereLockedThatPageLoad as $thisForm=>$theseEntries) {
            foreach(array_keys($theseEntries) as $entry_id) {
                $fileName = XOOPS_ROOT_PATH."/modules/formulize/temp/entry_".$entry_id."_in_form_".$thisForm."_is_locked_for_editing";
                if(file_exists($fileName)) {
                    unlink($fileName);
                }
            }
        }
    }
}