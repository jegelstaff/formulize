<?php

include "../../mainfile.php";

global $xoopsUser;
$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;
if ($uid > 0) {
    foreach($_POST['form_ids'] as $fid) {
        foreach($_POST['entry_ids_'.$fid] as $entry_id) {
            $fileName = XOOPS_ROOT_PATH."/modules/formulize/temp/entry_".$entry_id."_in_form_".$fid."_is_locked_for_editing";
            if(file_exists($fileName)) {
                unlink($fileName);
            }
        }
    }
}
