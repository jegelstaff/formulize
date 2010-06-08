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

// this function shows the new admin homepage in F4

$application_handler = xoops_getmodulehandler('applications', 'formulize');
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$screen_handler = xoops_getmodulehandler('screen','formulize');
$appObjects = $application_handler->getAllApplications();
$apps = array();
$foundForms = array();
$i = 1;
foreach($appObjects as $thisAppObject) {
  $aid = $thisAppObject->getVar('appid');
  $apps[$i]['name'] = $thisAppObject->getVar('name');
  $apps[$i]['content']['aid'] = $aid;
  $apps[$i]['content']['description'] = $thisAppObject->getVar('description');
  $formObjects = $form_handler->getFormsByApplication($thisAppObject);
  $x = 0;
  foreach($formObjects as $thisFormObject) {
    $fid = $thisFormObject->getVar('id_form');
    $foundForms[] = $fid; // mark this as found, so we don't get it later
    $apps[$i]['content']['forms'][$x]['fid'] = $fid;
    $apps[$i]['content']['forms'][$x]['name'] = $thisFormObject->getVar('title');
    $screenObjects = $screen_handler->getObjects(null,$fid);
    $y = 0;
    foreach($screenObjects as $thisScreenObject) {
      $sid = $thisScreenObject->getVar('sid');
      $apps[$i]['content']['forms'][$x]['screens'][$y]['sid'] = $sid;
      $apps[$i]['content']['forms'][$x]['screens'][$y]['name'] = $thisScreenObject->getVar('title');
      $y++;
    }
    $x++;
  }
  $i++;
}

// now we need to make a placeholder application for all forms not mentioned elsewhere
// figure out the forms that are missing above
global $xoopsDB;
$forms = array();
$whereClause = count($foundForms) > 0 ? " WHERE id_form NOT IN (".implode(",",$foundForms).")" : "";
$sql = "SELECT id_form, desc_form FROM ".$xoopsDB->prefix("formulize_id").$whereClause." ORDER BY desc_form";
if($res = $xoopsDB->query($sql)) {
  $x = 0;
  while($array = $xoopsDB->fetchArray($res)) {
    $forms[$x]['fid'] = $array['id_form'];
    $forms[$x]['name'] = $array['desc_form'];
    $screenObjects = $screen_handler->getObjects(null,$array['id_form']);
    $y = 0;
    foreach($screenObjects as $thisScreenObject) {
      $sid = $thisScreenObject->getVar('sid');
      $forms[$x]['screens'][$y]['sid'] = $sid;
      $forms[$x]['screens'][$y]['name'] = $thisScreenObject->getVar('title');
      $y++;
    }
    $x++;
  }
}
if(count($forms) > 0) {
  $apps[$i]['name'] = "Forms that don't belong to an application";
  $apps[$i]['content']['aid'] = 0;
  $apps[$i]['content']['description'] = "";
  $apps[$i]['content']['forms'] = $forms; 
}

$adminPage['apps'] = $apps;
$adminPage['pagetitle'] = "Formulize 4";
$adminPage['template'] = "db:admin/home.html";

$breadcrumbtrail[1]['text'] = "Home";
