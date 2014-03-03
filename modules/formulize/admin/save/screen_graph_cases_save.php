<?php
// this file handles saving of graph dimension

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}


$aid = intval($_POST['aid']);
$sid = $_POST['formulize_admin_key'];

$screens = $processedValues['screens'];


$screen_handler = xoops_getmodulehandler('graphScreen', 'formulize');
$screen = $screen_handler->get($sid);

// CHECK IF THE FORM IS LOCKED DOWN AND SCOOT IF SO
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($screen->getVar('fid'));
if($formObject->getVar('lockedform')) {
  return;
}
// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $screen->getVar('fid'), $groups, $mid)) {
  return;
}


$screen->setVar('ops',$screens['ops']);
$screen->setVar('labelelem',$screens['labelelem']);
$screen->setVar('dataelem',$screens['dataelem']);


if(!$screen_handler->insert($screen)) {
  print "Error: could not save the screen properly: ".mysql_error();
}
?>
