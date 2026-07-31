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

$appId = intval($_POST['formulize_admin_key']);
if(!$appId) {
    return;
}
$filename = XOOPS_ROOT_PATH."/modules/formulize/code/application_custom_code_".$appId.".php";

// A submission that does not carry the field at all is not a request to empty the file. This used to write
// whatever was in $processedValues unconditionally, so anything reaching this handler without a populated
// custom_code field silently truncated the application's code to nothing - and because the file is only ever
// read at request time, the loss showed up later as a fatal "call to undefined function" rather than as an
// error at the point it happened. Absent means leave it alone; empty means the person cleared the box.
if(!isset($processedValues['applications']['custom_code'])) {
    return;
}
$code = $processedValues['applications']['custom_code'];
if(trim($code) === '') {
    if(file_exists($filename)) {
        unlink($filename);
    }
} elseif(file_put_contents($filename, $code) === false) {
    print "Error: could not save the custom code for this application.";
}
