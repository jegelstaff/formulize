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
$gperm_handler = xoops_gethandler('groupperm');
$appObjects = $application_handler->getAllApplications();
$apps = array();
$foundForms = array();

foreach($appObjects as $thisAppObject) {
  $apps = readApplicationData($thisAppObject->getVar('appid'), $apps);
}
$apps = readApplicationData(0,$apps); // lastly, get forms that don't have an application

$adminPage['apps'] = $apps;
$adminPage['template'] = "db:admin/home.html";

$breadcrumbtrail[1]['text'] = "Home";

function readApplicationData($aid, $apps) {
  static $i = 1;
  global $form_handler, $application_handler, $gperm_handler, $screen_handler, $xoopsUser;
  $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
  if($aid == 0) {
    $apps[$i]['name'] = _AM_APP_FORMWITHNOAPP;
    $apps[$i]['content']['description'] = "";
  } else {
    $thisAppObject = $application_handler->get($aid);
    $apps[$i]['name'] = "Application: ".$thisAppObject->getVar('name');
    $apps[$i]['content']['description'] = $thisAppObject->getVar('description');
  }
  $apps[$i]['content']['aid'] = $aid;
  $formObjects = $form_handler->getFormsByApplication($aid);
  $x = 0;
  foreach($formObjects as $thisFormObject) {
    $fid = $thisFormObject->getVar('id_form');
    // check if the user has edit permission on this form
    if(!$gperm_handler->checkRight("edit_form", $fid, $groups, getFormulizeModId())) {
      continue;
    }
    $hasDelete = $gperm_handler->checkRight("delete_form", $fid, $groups, getFormulizeModId());
    $apps[$i]['content']['forms'][$x]['fid'] = $fid;
    $apps[$i]['content']['forms'][$x]['name'] = $thisFormObject->getVar('title');
    $apps[$i]['content']['forms'][$x]['hasdelete'] = $hasDelete;
    $apps[$i]['content']['forms'][$x]['lockedform'] = $thisFormObject->getVar('lockedform');
    $apps[$i]['content']['forms'][$x]['istableform'] = $thisFormObject->getVar('tableform');
    $defaultFormScreen = $thisFormObject->getVar('defaultform');
    $defaultListScreen = $thisFormObject->getVar('defaultlist');
    $defaultFormObject = $screen_handler->get($defaultFormScreen);
    $defaultListObject = $screen_handler->get($defaultListScreen);
    if(is_object($defaultFormObject)) {
      $defaultFormName = $defaultFormObject->getVar('title');
      $apps[$i]['content']['forms'][$x]['defaultformscreenid'] = $defaultFormScreen;
      $apps[$i]['content']['forms'][$x]['defaultformscreenname'] = $defaultFormName;
    }
    if(is_object($defaultListObject)) {
      $defaultListName = $defaultListObject->getVar('title');
      $apps[$i]['content']['forms'][$x]['defaultlistscreenid'] = $defaultListScreen;
      $apps[$i]['content']['forms'][$x]['defaultlistscreenname'] = $defaultListName;
    }
    $x++;
  }
  $i++;
  return $apps;
}
