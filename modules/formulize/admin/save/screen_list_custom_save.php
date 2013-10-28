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

// this file handles saving of submissions from the screen_list_buttons page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

$aid = intval($_POST['aid']);
$sid = $_POST['formulize_admin_key'];

$screens = $processedValues['screens'];

$screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
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

// data schema:
// array[actionid][handle]
// array[actionid][buttontext]
// array[actionid][messagetext] -- text to appear on the screen after this button has been clicked.  Intended for confirmation stuff like "your items have been signed out".
// array[actionid][appearinline] -- either 1, yes, or 0, no
// array[actionid][applyto] -- either 'inline', 'selected', 'all', 'new'
// OLD NOTES: 'selected' for selected, 'individual' for individual meaning the entry where this appears inline, or 'x_new', or 'x_all', or 'x_y_z_etc' -- x is the form (possibly the current form, or some other)
// so to support apply to setting, we need a fairly complex interface where people can select the main three options (all, or selected or individual in this form) or new entry in this form, or apply to another form and if so to which entries: new or all or x, y, z
// x, y, z, are essentially a surrogate for user being able to check off entries -- app developer must be able to specify in advance which entries those are.
// array[actionid][effectid][element] -- element to alter
// array[actionid][effectid][action] -- type of action
// array[actionid][effectid][value] -- value to use in a
// array[actionid][effectid][code] -- PHP code if this is a PHP button
// array[actionid][effectid][html] -- PHP code for making HTML if this is an HTML button

// watch out for buttons that are supposed to be deleted...don't save them
// watch out for effects hat are supposed to be deleted...don't save them
$deleteButton = $_POST['deletebutton'];
$removeEffect = $_POST['removeeffect'] ? explode("_", $_POST['removeeffect']) : array("","");


// read all the button info that was submitted, pack it up and assign it to the screen object
foreach($_POST as $k=>$v) {
  if(substr($k, 0, 7)=="handle_") {
    // found a button, grab all its info
    $buttonId = substr($k, 7);
    if((string)$buttonId === (string)$deleteButton) { continue; }
    $buttonData[$buttonId]['handle'] = $v;
    $buttonData[$buttonId]['buttontext'] = $_POST['buttontext_'.$buttonId];
    $buttonData[$buttonId]['messagetext'] = $_POST['messagetext_'.$buttonId];
    $buttonData[$buttonId]['appearinline'] = $_POST['appearinline_'.$buttonId];
    $buttonData[$buttonId]['applyto'] = $_POST['applyto_'.$buttonId];
    $buttonData[$buttonId]['groups'] = $_POST['groups_'.$buttonId];
    if(isset($_POST['code_'.$buttonId])) {
      foreach($_POST['code_'.$buttonId] as $effectId=>$code) { 
        if((string)$effectId === (string)$removeEffect[1] AND (string)$buttonId === (string)$removeEffect[0]) { continue; }
        $buttonData[$buttonId][$effectId]['code'] = $code;    
      }
    }
    if(isset($_POST['html_'.$buttonId])) {
      foreach($_POST['html_'.$buttonId] as $effectId=>$html) {
        if((string)$effectId === (string)$removeEffect[1] AND (string)$buttonId === (string)$removeEffect[0]) { continue; }
        $buttonData[$buttonId][$effectId]['html'] = $html;    
      }
    }
    if(isset($_POST['element_'.$buttonId])) {
      foreach($_POST['element_'.$buttonId] as $effectId=>$element) {
        if((string)$effectId === (string)$removeEffect[1] AND (string)$buttonId === (string)$removeEffect[0]) { continue; }
        $buttonData[$buttonId][$effectId]['element'] = $element;
        $buttonData[$buttonId][$effectId]['action'] = $_POST['action_'.$buttonId][$effectId];
        $buttonData[$buttonId][$effectId]['value'] = $_POST['value_'.$buttonId][$effectId];
      }
    }
  }
}

// handle any requests for new buttons or effects
if($_POST['newbutton']) {
  $buttonId = count($buttonData);
  $buttonData[$buttonId]['handle'] = "New button";
}
if($_POST['neweffect']!=="") {
  $buttonId = $_POST['neweffect'];
  $effectCounter = 1;
  foreach($buttonData[$buttonId] as $key=>$value) {
    if(is_numeric($key)) {
      $effectCounter++;
    }
  }
  if($buttonData[$buttonId]['applyto'] == "custom_code") {
    $buttonData[$buttonId][$effectCounter]['code'] = "";  
  } elseif($buttonData[$buttonId]['applyto'] == "custom_html") {
    $buttonData[$buttonId][$effectCounter]['html'] = "";
  } else {
    $buttonData[$buttonId][$effectCounter]['element'] = "";  
  }
}

$screen->setVar('customactions', serialize($buttonData));
if(!$screen_handler->insert($screen)) {
  print "Error: could not save the screen properly: ".mysql_error();
}

if($_POST['reload_list_pages']) {
  print "/* eval */ reloadWithScrollPosition();";
}
?>
