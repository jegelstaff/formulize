<?php
/*************************************************************************************************
 *get information from \modules\formulize\admin\application.php by passing object $appObject
 *and put custom_code into $appObject then pass it to function insert(Object) in \modules\formulize\class\applications.php to insert
 *
 *ADDED BY JINFU IN JAN 2015
 ************************************************************************************************/

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
    return;
}

$filename=XOOPS_ROOT_PATH."/modules/formulize/custom_code/application_custom_code_".intval($_POST['formulize_admin_key']).".php";
file_put_contents($filename,$processedValues['applications']['custom_code']);
