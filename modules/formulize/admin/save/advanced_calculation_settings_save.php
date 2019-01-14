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
##  URL: http://www.formulize.org                           ##
##  Project: Formulize                                                       ##
###############################################################################

// this file handles saving of submissions from the advanced_calculations_settings page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
	return;
}

// get admin info
$fid = $_POST['formulize_admin_fid'];
$aid = $_POST['aid'];

// get advanced calculations info
$acid = $_POST['formulize_admin_key'];
if($acid == 'new') {
	$isNew = true;
} else {
	$isNew = false;
	$acid = intval($acid);
}

$advCalc = $processedValues['advcalc'];

// create a new item, or load an existing item
$advanced_calculation_handler = xoops_getmodulehandler('advancedCalculation', 'formulize');
if($isNew) {
	$advCalcObject = $advanced_calculation_handler->create();
	$advCalcObject->setVar('steptitles',array(0=>'New step'));
  //$advCalcObject->setVar('steps',array(0=>array()));
  //$advCalcObject->setVar('steps',array(0=>array('description'=>'This step...','sql'=>'SELECT * FROM [...]','preCalculate'=>'// setup','calculate'=>'// do per entry','postCalculate'=>'// teardown')));
	$advCalcObject->setVar('steps',array(0=>array('description'=>'','sql'=>'','preCalculate'=>'','calculate'=>'','postCalculate'=>'')));
} else {
	$advCalcObject = $advanced_calculation_handler->get($acid);
}

// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $fid, $groups, $mid)) {
	return;
}

// apply user changes
$advCalcObject->setVar('name',$advCalc['name']);
$advCalcObject->setVar('description',$advCalc['description']);
$advCalcObject->setVar('fid',$fid);

// save object, and if a new item, reload page
if(!$acid = $advanced_calculation_handler->insert($advCalcObject)) {
	print "Error: could not save the advanced calculation properly: ".$xoopsDB->error();
} else if($isNew) {
  // send code to client that will to be evaluated
	$url = XOOPS_URL . "/modules/formulize/admin/ui.php?page=advanced-calculation&tab=settings&aid=".$aid.'&fid='.$fid.'&acid='.$acid;
	print '/* eval */ window.location = "'.$url.'";';
}
