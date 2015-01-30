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

$application_handler = xoops_getmodulehandler('applications', 'formulize');
$appObject = $application_handler->get($_POST['formulize_admin_key']);

foreach($processedValues['applications'] as $property=>$value) {
    $appObject->setVar($property, $value);
}

$application_handler->insert($appObject);
