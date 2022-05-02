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

// this file handles saving of submissions from the screen_settings page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}


$aid = intval($_POST['aid']);
$sid = $_POST['formulize_admin_key'];
$fid = intval($_POST['formulize_admin_fid']);

$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($fid);
if($formObject->getVar('lockedform')) {
  return;
}
// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $fid, $groups, $mid)) {
  return;
}


$screens = $processedValues['screens'];

$isNew = ($sid=='new');

if($screens['type'] == 'multiPage') {
  $screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
} else if($screens['type'] == 'listOfEntries') {
  $screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
} else if($screens['type'] == 'form') {
  $screen_handler = xoops_getmodulehandler('formScreen', 'formulize');
} else if($screens['type'] == 'template') {
    $screen_handler = xoops_getmodulehandler('templateScreen', 'formulize');
} else if($screens['type'] == 'calendar') {
    $screen_handler = xoops_getmodulehandler('calendarScreen', 'formulize');
}

global $xoopsConfig;

if($isNew) {
  $screen = $screen_handler->create();
  $screen->setVar('theme', $xoopsConfig['theme_set']);  
  if($screens['type'] == 'multiPage') {
    $screen_handler->setDefaultFormScreenVars($screen, $screens['title'], $fid, $screens['title']);
  } else if($screens['type'] == 'listOfEntries') {
    $screen_handler->setDefaultListScreenVars($screen, 'none', $screens['title'], $fid);
  } else if($screens['type'] == 'form') {
      $screen->setVar('displayheading', 1);
      $screen->setVar('reloadblank', 0);
      $screen->setVar('savebuttontext', _formulize_SAVE);
      $screen->setVar('saveandleavebuttontext', _formulize_SAVE_AND_LEAVE);
      $screen->setVar('printableviewbuttontext', _formulize_PRINTVIEW);
      $screen->setVar('savebuttontext', _formulize_SAVE);
      $screen->setVar('alldonebuttontext', _formulize_DONE);
  } else if ($screens['type'] == 'template') {
      $screen->setVar('custom_code', "");
      $screen->setVar('template', "");
      $screen->setVar('savebuttontext', _formulize_SAVE);
      $screen->setVar('donebuttontext', _formulize_SAVE_AND_LEAVE);
      $screen->setVar('donedest', "");
  } else if($screens['type'] == 'calendar') {
      $screen->setVar('caltype', 'month');
      $screen->setVar('datasets', array());
  }
} else {
  $screen = $screen_handler->get($sid);
}

$screen->setVar('title',$screens['title']);  
$screen->setVar('fid',$fid);
$screen->setVar('type',$screens['type']);
$screen->setVar('useToken',$screens['useToken']);
$screen->setVar('anonNeedsPasscode',$screens['anonNeedsPasscode']);

if(!$sid = $screen_handler->insert($screen)) {
  print "Error: could not save the screen properly: ".$xoopsDB->error();
}

$reloadNow = false;
$passcode_handler = xoops_getmodulehandler('passcode', 'formulize');
if($_POST['delete_passcode']) {
    $passcode_handler->delete($_POST['delete_passcode']);
    $reloadNow = true;
}
foreach($_POST as $key=>$value) {
    if(substr($key, 0, 16)=="passcode_expiry_") {
        $id = str_replace("passcode_expiry_", "", $key);
        $passcode_handler->updateExpiry($id, $value);
    }
}
if($_POST['add_existing_passcode']) {
    $passcode_handler->copyPasscodeToScreen($_POST['existing_passcode'], $sid);
    $reloadNow = true;
}
if($_POST['make_new_passcode']) {
    $passcode_handler->insert($_POST['new_passcode'], $_POST['new_notes'], $sid);
    $reloadNow = true;
}


if($isNew) {
  
  // write out the necessary templates...
  if($screens['type'] == "multiPage") {
    $screen_handler->writeTemplateToFile("", 'toptemplate', $screen);
    $screen_handler->writeTemplateToFile("", 'elementtemplate', $screen);
    $screen_handler->writeTemplateToFile("", 'bottomtemplate', $screen);
  } elseif($screens['type'] == "listOfEntries") {
    $screen_handler->writeTemplateToFile("", 'toptemplate', $screen);
    $screen_handler->writeTemplateToFile("", 'listtemplate', $screen);
    $screen_handler->writeTemplateToFile("", 'bottomtemplate', $screen);
  } elseif($screens['type'] == "template") {
      $screen_handler->write_custom_code_to_file("", $screen);
      $screen_handler->write_template_to_file("", $screen);
  } elseif($screens['type'] == "calendar") {
      $screen_handler->writeTemplateToFile("", 'toptemplate', $screen);
      $screen_handler->writeTemplateToFile("", 'bottomtemplate', $screen);
  }

    // send code to client that will to be evaluated
  $url = XOOPS_URL . "/modules/formulize/admin/ui.php?page=screen&tab=settings&aid=".$aid.'&fid='.$fid.'&sid='.$sid;
  print '/* eval */ window.location = "'.$url.'";';
} elseif($reloadNow) {
  print '/* eval */ reloadWithScrollPosition();';
}
