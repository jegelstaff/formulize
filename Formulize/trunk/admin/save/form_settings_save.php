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

// this file handles saving of submissions from the form_settings page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

// invoke the necessary objects
$form_handler = xoops_getmodulehandler('forms','formulize');
$application_handler = xoops_getmodulehandler('applications','formulize');
$newAppObject = false;
$selectedAppObjects = array();
if($_POST['formulize_admin_key'] == "new") {
  $formObject = $form_handler->create();
} else {
  $fid = intval($_POST['formulize_admin_key']);
  $formObject = $form_handler->get($fid);
}
if(($_POST['new_app_yes_no'] == "yes" AND $_POST['applications-name'])) {
  $newAppObject = $application_handler->create();  
} 

// get all the existing applcations that this form object was assigned to
if(isset($_POST['apps']) AND count($_POST['apps']) > 0) {
  $selectedAppObjects = $application_handler->get($_POST['apps']);
}

// interpret form object values that were submitted and need special handling
$processedValues['forms']['headerlist'] = "*=+*:".implode("*=+*:",$_POST['headerlist']);
foreach($processedValues['forms'] as $property=>$value) {
  $formObject->setVar($property, $value);
}
if(!$form_handler->insert($formObject)) {
  print "Error: could not save the form properly: ".mysql_error();
}
$fid = $formObject->getVar('id_form');
if($_POST['formulize_admin_key'] == "new") {
  if(!$tableCreateRes = $form_handler->createDataTable($fid)) {
    print "Error: could not create data table for new form";
  }
}

if($newAppObject) {
  // assign the form id to this new application
  $processedValues['applications']['forms'] = serialize(array($fid));
  foreach($processedValues['applications'] as $property=>$value) {
    $newAppObject->setVar($property, $value);
  }
  if(!$application_handler->insert($newAppObject)) {
    print "Error: could not save the new application properly: ".mysql_error();
  }
}

// assign this form as required to the selected applications
foreach($selectedAppObjects as $thisAppObject) {
  $thisAppForms = $thisAppObject->getVar('forms');
  if(!in_array($fid, $thisAppForms)) {
    $thisAppForms[] = $fid;
    $thisAppObject->setVar('forms', serialize($thisAppForms));
    if(!$application_handler->insert($thisAppObject)) {
      print "Error: could not add the form to one of the applications properly: ".mysql_error();
    }
  }
}

// if we're making a new table form, then synch the "elements" for the form with the target table
if(isset($_POST['forms-tableform'])) {
  if(!$form_handler->createTableFormElements($_POST['forms-tableform'], $fid)) {
    print "Error: could not create all the placeholder elements for the tableform";
  }
}

// need to do some other stuff here later to setup defaults for...
// screens?
// menu items?
// permissions?