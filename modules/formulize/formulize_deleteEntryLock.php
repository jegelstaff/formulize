<?php

// NEED TO PASS THE SAME TOKEN WITH EVERY PAGELOAD, SO WE CAN INVALIDATE THE UNLOAD EVENT BY REMOVING THE TOKEN FILE, WHEN DRAWING THE PAGE AGAIN ON A RELOAD? UNLOAD SHOULD ONLY ACTUALLY ACTIVATE WHEN LANDING ON A NON-FORMULIZE PAGE?? 

ignore_user_abort(true);
header("Access-Control-Allow-Origin: *");
include "../../mainfile.php";
icms::$logger->disableLogger();

global $xoopsUser;
$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
if (
    (
     ($uid > 0 AND $uid == $_POST['uid']) OR $uid == 0
     ) AND (
      file_exists(XOOPS_ROOT_PATH."/modules/formulize/temp/".str_replace(array('/','\\','.'),'',$_POST['token']).'.token')
    )
   ) {
    unlink(XOOPS_ROOT_PATH."/modules/formulize/temp/".str_replace(array('/','\\','.'),'',$_POST['token']).'.token');
    foreach($_POST['form_ids'] as $fid) {
        foreach($_POST['entry_ids_'.$fid] as $entry_id) {
            $fileName = XOOPS_ROOT_PATH."/modules/formulize/temp/entry_".$entry_id."_in_form_".$fid."_is_locked_for_editing";
            if(file_exists($fileName)) {
                unlink($fileName);
            }
        }
    }
}
