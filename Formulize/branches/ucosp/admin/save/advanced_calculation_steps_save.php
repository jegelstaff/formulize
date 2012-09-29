<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2010 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions                                  ##
##  URL: http://www.freeformsolutions.ca/formulize                           ##
##  Project: Formulize                                                       ##
###############################################################################

// this file handles saving of submissions from the advance_calculation_steps step of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

/*print_r($_POST);
print_r($processedValues);
return;*/

$aid = intval($_POST['aid']);
$acid = intval($_POST['formulize_admin_key']);
$op = $_POST['formulize_admin_op'];
$index = $_POST['formulize_admin_index'];

$advcalc = $processedValues['advcalc'];

// load an existing item
$advanced_calculation_handler = xoops_getmodulehandler('advancedCalculation', 'formulize');
$advCalcObject = $advanced_calculation_handler->get($acid);

// CHECK IF THE FORM IS LOCKED DOWN AND SCOOT IF SO
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($advCalcObject->getVar('fid'));
if($formObject->getVar('lockedform')) {
  return;
}
// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $advCalcObject->getVar('fid'), $groups, $mid)) {
  return;
}

/*
// apply user changes
$advCalcObject->setVar('input',$advCalc['input']);
$advCalcObject->setVar('output',$advCalc['output']);

// save object, and if a new item, reload page
if(!$acid = $advanced_calculation_handler->insert($advCalcObject)) {
  print "Error: could not save the advanced calculation properly: ".mysql_error();
}
*/

$steps = array();
$steptitles = array();

foreach($advcalc as $k=>$v) {
  //print $k . '=>' . $v ."\n";
	if(substr($k, 0, 10) == "steptitle_") {
		$step_number = intval(substr($k, 10));
		$steptitles[$step_number] = $v;
  } else if(substr($k, 0, 12) == "description_") {
		$step_number = intval(substr($k, 12));
		$steps[$step_number][substr($k, 0, 11)] = $v;
  } else if(substr($k, 0, 4) == "sql_") {
		$step_number = intval(substr($k, 4));
		$steps[$step_number][substr($k, 0, 3)] = $v;
  } else if(substr($k, 0, 13) == "preCalculate_") {
		$step_number = intval(substr($k, 13));
		$steps[$step_number][substr($k, 0, 12)] = $v;
  } else if(substr($k, 0, 10) == "calculate_") {
		$step_number = intval(substr($k, 10));
		$steps[$step_number][substr($k, 0, 9)] = $v;
  } else if(substr($k, 0, 14) == "postCalculate_") {
		$step_number = intval(substr($k, 14));
		$steps[$step_number][substr($k, 0, 13)] = $v;
  }
}


//print_r( $_POST['steporder'] );

// get the new order of the steps...
$newOrder = explode("drawer-3[]=", str_replace("&", "", $_POST['steporder']));
unset($newOrder[0]);
// newOrder will have keys corresponding to the new order, and values corresponding to the old order
// need to add in conditions handling here too
$newsteps = array();
$newsteptitles = array();
$stepsHaveBeenReordered = false;
foreach($steptitles as $oldOrderNumber=>$values) {
	$newOrderNumber = array_search($oldOrderNumber,$newOrder);
	$newOrderNumberKey = $newOrderNumber-1;
	$newsteps[$newOrderNumberKey] = $steps[$oldOrderNumber];
	$newsteptitles[$newOrderNumberKey] = $steptitles[$oldOrderNumber];
	if(($newOrderNumber - 1) != $oldOrderNumber) {
		$stepsHaveBeenReordered = true;
		$_POST['reload_advance_calculation_steps'] = 1;
	}
}

if($stepsHaveBeenReordered) {
	$steps = $newsteps;
	$steptitles = $newsteptitles;
}


// alter the information based on a user add or delete
switch ($op) {
	case "addstep":
    $steps[]=array('description'=>'','sql'=>'','preCalculate'=>'','calculate'=>'','postCalculate'=>'');
    $steptitles[]='New step';
		break;
	case "delstep":
    array_splice($steps, $index, 1);
    array_splice($steptitles, $index, 1);
		break;
  case "clonestep":
    $step = $steps[$index];
    $newStep = array();
    foreach( $step as $key => $value ) {
      $newStep[$key] = $value;
    }
    $steps[]=$newStep;
    $steptitles[]=$steptitles[$index].' copy';
    break;
}


/*print_r($steps);
print_r($steptitles);
return;*/

$advCalcObject->setVar('steps',$steps);
$advCalcObject->setVar('steptitles',$steptitles);

if(!$advanced_calculation_handler->insert($advCalcObject)) {
  print "Error: could not save the advanced calculation properly: ".mysql_error();
}

// reload the step if the state has changed
if($op == "addstep" OR $op=="delstep" OR $op=="clonestep" OR $_POST['reload_advance_calculation_steps']) {
    print "/* eval */ reloadWithScrollPosition();";
}
?>
