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

// this file handles saving of submissions from the form_screens page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

$fid = intval($_POST['formulize_admin_key']);
$aid = intval($_POST['formulize_admin_aid']);

// CHECK IF THE FORM IS LOCKED DOWN AND SCOOT IF SO
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($fid);
if($formObject->getVar('lockedform')) {
  return;
}
// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $fid, $groups, $mid)) {
  return;
}

// currently, this only saves the forms-on_before_save value, but if more items are added this will save them
foreach ($processedValues['forms'] as $property => $value) {
    $formObject->setVar($property, $value);
}
if (!$form_handler->insert($formObject)) {
    print "Error: could not save the form properly: ".$xoopsDB->error();
}

// do cloning here
if(intval($_POST['cloneadvanced_calculations'])) {
  $advanced_calculation_handler = xoops_getmodulehandler('advancedCalculation', 'formulize');
  if(!$advanced_calculation_handler->cloneProcedure(intval($_POST['cloneadvanced_calculations']))) {
    print "Error: could not clone Procedure ".intval($_POST['cloneadvanced_calculations']);
  } else {
    print "/* eval */ reloadWithScrollPosition()";
  }
}

// do deletion here
if(intval($_POST['deleteadvanced_calculations'])) {
  $advanced_calculation_handler = xoops_getmodulehandler('advancedCalculation', 'formulize');
  if(!$advanced_calculation_handler->delete(intval($_POST['deleteadvanced_calculations']))) {
    print "Error: could not delete Procedure ".intval($_POST['deleteadvanced_calculations']);
  } else {
    print "/* eval */ reloadWithScrollPosition()";
  }
}

// if the form name was changed, then force a reload of the page...reload will be the application id
if($_POST['gotoadvanced_calculations']) {
  $gotoacid = $_POST['gotoadvanced_calculations'] === "new" ? "new" : intval($_POST['gotoadvanced_calculations']);
  print "/* eval */ window.location = '". XOOPS_URL ."/modules/formulize/admin/ui.php?page=advanced-calculation&aid=$aid&fid=$fid&acid=$gotoacid'";
}
