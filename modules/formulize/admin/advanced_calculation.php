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

// this file gets all the data about applications, so we can display the Settings/forms/advanced calculations tabs for applications

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";

//echo "ADVANCED CALCULATIONS";

// get application info
$aid = intval($_GET['aid']);
$application_handler = xoops_getmodulehandler('applications','formulize');
if($aid == 0) {
	$appName = "Forms with no app";
} else {
	$appObject = $application_handler->get($aid);
	$appName = $appObject->getVar('name');
}

// get form info
$fid = intval($_GET['fid']);
$form_handler = xoops_getmodulehandler('forms', 'formulize');
if(!($fid == 0)) {
	$formObject = $form_handler->get($fid);
	$formName = $formObject->getVar('title');
}

// get advanced calculations info
$acid = $_GET['acid'];
if($acid == 'new') {
  $isNew = true;
} else {
  $isNew = false;
  $acid = intval($acid);
}

$advanced_calculation_handler = xoops_getmodulehandler('advancedCalculation', 'formulize');
if(!$isNew) {
  $advCalcObject = $advanced_calculation_handler->get($acid);
}

// prepare info for templates

$common = array();
$advCalc = array();

if($isNew) {
  $advCalc['name'] = 'New calculation';
} else {
  $advCalc['name'] = $advCalcObject->getVar('name');
  $advCalc['description'] = $advCalcObject->getVar('description');
  $advCalc['input'] = $advCalcObject->getVar('input');
  $advCalc['output'] = $advCalcObject->getVar('output');

	// get step titles
  $stepTitles = $advCalcObject->getVar('steptitles');
  $stepElements = $advCalcObject->getVar('steps');

  // group entries
  $steps = array();
	//for($i=0;$i<(count($stepTitles)+$stepCounterOffset);$i++) {
	for($i=0;$i<(count($stepTitles));$i++) {
    $steps[$i]['name'] = $stepTitles[$i];
    $steps[$i]['content']['index'] = $i;
    $steps[$i]['content']['number'] = $i+1;
    $steps[$i]['content']['title'] = $stepTitles[$i];
    $steps[$i]['content']['steps'] = $stepElements[$i];
    //print_r( $steps[$i]['content']['steps'] );
  }

  $advCalc['steps'] = $steps;


	// get filters and grouping titles
  $fltr_grpTitles = $advCalcObject->getVar('fltr_grptitles');
  $fltr_grpElements = $advCalcObject->getVar('fltr_grps');

  // group entries
  $fltr_grps = array();
	//for($i=0;$i<(count($fltr_grpTitles)+$fltr_grpCounterOffset);$i++) {
	for($i=0;$i<(count($fltr_grpTitles));$i++) {
    $fltr_grps[$i]['name'] = $fltr_grpTitles[$i];
    $fltr_grps[$i]['content']['index'] = $i;
    $fltr_grps[$i]['content']['number'] = $i+1;
    $fltr_grps[$i]['content']['title'] = $fltr_grpTitles[$i];
    $fltr_grps[$i]['content']['fltr_grps'] = $fltr_grpElements[$i];
    //print_r( $fltr_grps[$i]['content']['fltr_grps'] );
    $elementList = createFieldList("", true);
    $elementList->setName('advcalc-form_'.$i);
    $elementList->setValue($fltr_grpElements[$i]['form']);
    $fltr_grps[$i]['content']['form_html'] = $elementList->render();
  }
  if($fltr_grps[0]['name'] == "") {
	$fltr_grps[0]['name'] = "New filter";
  }

  $advCalc['fltr_grps'] = $fltr_grps;
}

$common['acid'] = $acid;
$common['fid'] = $fid;
$common['aid'] = $aid;

// define tabs for screen sub-page
$adminPage['tabs'][1]['name'] = "Settings";
$adminPage['tabs'][1]['template'] = "db:admin/advanced_calculation_settings.html";
$adminPage['tabs'][1]['content'] = $advCalc + $common;

if(!$isNew) {
  $adminPage['tabs'][2]['name'] = "Input/Output";
  $adminPage['tabs'][2]['template'] = "db:admin/advanced_calculation_input_output.html";
  $adminPage['tabs'][2]['content'] = $advCalc + $common;

  $adminPage['tabs'][3]['name'] = "Steps";
  $adminPage['tabs'][3]['template'] = "db:admin/advanced_calculation_steps.html";
  $adminPage['tabs'][3]['content'] = $advCalc + $common;

  $adminPage['tabs'][4]['name'] = "Filters and Grouping";
  $adminPage['tabs'][4]['template'] = "db:admin/advanced_calculation_fltr_grp.html";
  $adminPage['tabs'][4]['content'] = $advCalc + $common;
}

$adminPage['pagetitle'] = "Procedure: ".$advCalc['name'];
$adminPage['needsave'] = true;

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['url'] = "page=application&aid=$aid&tab=forms";
$breadcrumbtrail[2]['text'] = $appName;
$breadcrumbtrail[3]['url'] = "page=form&aid=$aid&fid=$fid&tab=procedures";
$breadcrumbtrail[3]['text'] = $formName;
$breadcrumbtrail[4]['text'] = $advCalc['name'];
?>
