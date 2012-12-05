<?php
// displayGraph($graphType, $fid, $frid, $labelElement, $dataElement, $operation, $graphOptions)
if(!defined("XOOPS_ROOT_PATH")) {
	include_once "../../../mainfile.php"; // include this if it hasn't been already!  -- we can call readelements.php directly when saving data via ajax...jump up three levels to get it, because we assume that we're running here as the start of the process when such an ajax call is made.  But when a normal page loads, it won't find the mainfile that high up, because the root of the normal page load is the index.php file one directory higher than /include/
	ob_end_clean();
	ob_end_clean(); // turn off two levels of output buffering, just in case (don't want extra stuff sent back with our ajax response)!
}

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/graphdisplay.php";

if (isset($_POST['graphType'])) {
	$graphType = $_POST['graphType'];
} else {
	$graphType = 0;
}
if (isset($_POST['frid'])) {
	$frid = $_POST['frid'];
} else {
	$frid = 0;
}
if (isset($_POST['fid'])) {
	$fid = $_POST['fid'];
} else {
	$fid = 0;
}
if (isset($_POST['labelElement'])) {
	$labelElement = $_POST['labelElement'];
} else {
	$labelElement = 0;
}
if (isset($_POST['dataElement'])) {
	$dataElement = $_POST['dataElement'];
} else {
	$dataElement = 0;
}
if (isset($_POST['operation'])) {
	$operation = $_POST['operation'];
} else {
	$operation = 0;
}

// print "$graphType $fid $frid $labelElement $dataElement $operation";
displayGraph($graphType, $fid, $frid, $labelElement, $dataElement, $operation);
?>