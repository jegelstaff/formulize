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

// this file gets all the data about applications, so we can display the Settings/forms/relationships tabs for applications

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";

// need to listen for $_GET['aid'] later so we can limit this to just the application that is requested
$aid = intval($_GET['aid']);
$application_handler = xoops_getmodulehandler('applications','formulize');
// get a list of all applications
$allApps = $application_handler->getAllApplications();

if($aid == 0) {
	$appName = "Forms with no app"; 
} else {
	$appObject = $application_handler->get($aid);
	$appName = $appObject->getVar('name');
}

$elements = array();
if($_GET['fid'] != "new") {
  $fid = intval($_GET['fid']);
  $form_handler = xoops_getmodulehandler('forms', 'formulize');
  $formObject = $form_handler->get($fid);
  $formName = $formObject->getVar('title');
  $singleentry = $formObject->getVar('single');
  $tableform = $formObject->getVar('tableform');
  $headerlist = $formObject->getVar('headerlist');
  $headerlistArray = explode("*=+*:",trim($headerlist,"*=+*:"));
  
  $element_handler = xoops_getmodulehandler('elements', 'formulize');
  $elementObjects = $element_handler->getObjects2(null, $fid);
  $elements = array();
  $elementHeadings = array();
  $formApplications = array();
  // $elements array is going to be used to populate accordion sections, so it must contain the following:
  // a 'name' key and a 'content' key for each form that is found
  // Name will be the heading of the section, content is data used in the template for each section
  $i = 1; 
  foreach($elementObjects as $thisElement) {
    $elementCaption = printSmart($thisElement->getVar('ele_caption'),75);
    $ele_id = $thisElement->getVar('ele_id');
    $elements[$i]['name'] = $elementCaption;
    $elements[$i]['content']['ele_id'] = $ele_id;
    $elements[$i]['content']['ele_handle'] = $thisElement->getVar('ele_handle');
    $elements[$i]['content']['ele_type'] = $thisElement->getVar('ele_type');
    $elements[$i]['content']['ele_req'] = $thisElement->getVar('ele_req');
    $elements[$i]['content']['ele_display'] = $thisElement->getVar('ele_display');
    $elements[$i]['content']['ele_private'] = $thisElement->getVar('ele_private');
    $colhead = printSmart($thisElement->getVar('ele_caption'),75);
    $elementHeadings[$i]['text'] = $colhead ? $colhead : printSmart($thisElement->getVar('ele_caption'),75);
    $elementHeadings[$i]['ele_id'] = $ele_id;
    $elementHeadings[$i]['selected'] = in_array($ele_id, $headerlistArray) ? " selected" : "";
    $i++;
  }
  // add in the metadata headers
  $creator_email_selected = (in_array('creator_email', $headerlistArray)) ? " selected" : "";
  array_unshift($elementHeadings,array('text'=>_formulize_DE_CALC_CREATOR_EMAIL, 'ele_id'=>'creator_email', 'selected'=>$creator_email_selected));

  $mod_datetime_selected = (in_array('mod_datetime', $headerlistArray) OR in_array('mod_date', $headerlistArray)) ? " selected" : "";
  array_unshift($elementHeadings,array('text'=>_formulize_DE_CALC_MODDATE, 'ele_id'=>'mod_date', 'selected'=>$mod_datetime_selected));
  
  $creation_datetime_selected = (in_array('creation_datetime', $headerlistArray) OR in_array('creation_date', $headerlistArray)) ? " selected" : "";
  array_unshift($elementHeadings,array('text'=>_formulize_DE_CALC_CREATEDATE, 'ele_id'=>'creation_datetime', 'selected'=>$creation_datetime_selected));

  $mod_uid_selected = (in_array('mod_uid', $headerlistArray) OR in_array('proxyid', $headerlistArray)) ? " selected" : "";
  array_unshift($elementHeadings,array('text'=>_formulize_DE_CALC_MODIFIER, 'ele_id'=>'mod_uid', 'selected'=>$mod_uid_selected));
 
  $creation_uid_selected = (in_array('creation_uid', $headerlistArray) OR in_array('uid', $headerlistArray)) ? " selected" : "";
  array_unshift($elementHeadings,array('text'=>_formulize_DE_CALC_CREATOR, 'ele_id'=>'creation_uid', 'selected'=>$creation_uid_selected));
  
  // get a list of applications this form is involved with
  $thisFormApplications = $application_handler->getApplicationsByForm($fid);
  foreach($thisFormApplications as $thisApp) {
    $formApplications[] = $thisApp->getVar('appid');
  }
} else {
  $fid = $_GET['fid'];
  if($_GET['tableform']) {
    $newtableform = true;
  }
  $formName = "New form";
  $singleentry = "off"; // need to send a default for this
}

$i = 1;
$applications = array();
foreach($allApps as $thisApp) {
  $applications[$i]['appid'] = $thisApp->getVar('appid');
  $applications[$i]['text'] = printSmart($thisApp->getVar('name'),50);
  if(isset($formApplications)) {
    $applications[$i]['selected'] = in_array($thisApp->getVar('appid'),$formApplications) ? " selected" : "";
  } else {
    $applications[$i]['selected'] = "";
  }
  $i++;
}

// common values should be assigned to all tabs
$common['name'] = $formName;
$common['fid'] = $fid;

$permissions = array();
$permissions['hello'] = "Hello Permission World";

// need to get screen data so this can be populated properly
$screens = array();
$screens[1]['name'] = "dummy screen 1";
$screens[1]['content']['hello'] = "hello screen 1 world";
$screens[2]['name'] = "dummy screen 2";
$screens[2]['content']['hello'] = "hello screen 2 world";

$settings = array();
$settings['singleentry'] = $singleentry;
$settings['istableform'] = $tableform OR $newtableform ? true : false;


$adminPage['tabs'][1]['name'] = "Settings";
$adminPage['tabs'][1]['template'] = "db:admin/form_settings.html";
$adminPage['tabs'][1]['content'] = $settings + $common;
$adminPage['tabs'][1]['content']['applications'] = $applications;
if(isset($elementHeadings)) {
  $adminPage['tabs'][1]['content']['elementheadings'] = $elementHeadings;
}
if(isset($formApplications)) {
  $adminPage['tabs'][1]['content']['formapplications'] = $formApplications;
}

$adminPage['tabs'][2]['name'] = "Elements";
$adminPage['tabs'][2]['template'] = "db:admin/form_elements.html";
$adminPage['tabs'][2]['content'] = $common;
if(isset($elements)) {
  $adminPage['tabs'][2]['content']['elements'] = $elements;
}

$adminPage['tabs'][3]['name'] = "Permissions";
$adminPage['tabs'][3]['template'] = "db:admin/form_permissions.html";
$adminPage['tabs'][3]['content'] = $permissions + $common; 

$adminPage['tabs'][4]['name'] = "Screens";
$adminPage['tabs'][4]['template'] = "db:admin/form_screens.html";
$adminPage['tabs'][4]['content'] = $screens + $common;

$adminPage['pagetitle'] = "Form: ".$formName;
$adminPage['needsave'] = true;

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['url'] = "page=application&aid=$aid";
$breadcrumbtrail[2]['text'] = $appName;
$breadcrumbtrail[3]['text'] = $formName;

