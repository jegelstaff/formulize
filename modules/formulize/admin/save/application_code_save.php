<?php
//this file saves the info from application_code


// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
	return;
}

$application_handler = xoops_getmodulehandler('applications', 'formulize');
$appObject = $application_handler->get($_POST['formulize_admin_key']);

//error_log("variable: ".print_r($processedValues, true));

//echo("variable: ".print_r($_GET['aid'], true));
foreach($processedValues['applications'] as $property=>$value) {
	
	$appObject->setVar($property, $value);
	error_log("variable: ".print_r($property, true));
	error_log("variable: ".print_r($value, true));
	error_log("variable: ".print_r($appObject, true));
}



$application_handler->insert($appObject);


?>