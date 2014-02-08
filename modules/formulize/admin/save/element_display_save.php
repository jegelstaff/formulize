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

// this file handles saving of submissions from the element display page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

// invoke the necessary objects
$element_handler = xoops_getmodulehandler('elements','formulize');
if(!$ele_id = intval($_GET['ele_id'])) { // on new element saves, new ele_id can be passed through the URL of this ajax save
  if(!$ele_id = intval($_POST['formulize_admin_key'])) {
    print "Error: could not determine element id when saving display settings";
    return;
  }
}
$element = $element_handler->get($ele_id);
$fid = $element->getVar('id_form');

$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($fid);
if($formObject->getVar('lockedform')) {
  return;
}
// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $fid, $groups, $mid)) {
  return;
}


// grab any conditions for this page too
// add new ones to what was passed from before
if($_POST["new_elementfilter_term"] != "") {
  $_POST["elementfilter_elements"][] = $_POST["new_elementfilter_element"];
  $_POST["elementfilter_ops"][] = $_POST["new_elementfilter_op"];
  $_POST["elementfilter_terms"][] = $_POST["new_elementfilter_term"];
  $_POST["elementfilter_types"][] = "all";
}
if($_POST["new_elementfilter_oom_term"] != "") {
  $_POST["elementfilter_elements"][] = $_POST["new_elementfilter_oom_element"];
  $_POST["elementfilter_ops"][] = $_POST["new_elementfilter_oom_op"];
  $_POST["elementfilter_terms"][] = $_POST["new_elementfilter_oom_term"];
  $_POST["elementfilter_types"][] = "oom";
}
// then remove any that we need to
$filter_key = 'elementfilter';
$conditionsDeleteParts = explode("_", $_POST['conditionsdelete']);
$deleteTarget = $conditionsDeleteParts[1];
if($_POST['conditionsdelete']) { 
  // go through the passed filter settings starting from the one we need to remove, and shunt the rest down one space
  // need to do this in a loop, because unsetting and key-sorting will maintain the key associations of the remaining high values above the one that was deleted
  $originalCount = count($_POST[$filter_key."_elements"]);
  for($i=$deleteTarget;$i<$originalCount;$i++) { // 2 is the X that was clicked for this page
    if($i>$deleteTarget) {
      $_POST[$filter_key."_elements"][$i-1] = $_POST[$filter_key."_elements"][$i];
      $_POST[$filter_key."_ops"][$i-1] = $_POST[$filter_key."_ops"][$i];
      $_POST[$filter_key."_terms"][$i-1] = $_POST[$filter_key."_terms"][$i];
      $_POST[$filter_key."_types"][$i-1] = $_POST[$filter_key."_types"][$i];
    }
    if($i==$deleteTarget OR $i+1 == $originalCount) {
      // first time through or last time through, unset things
      unset($_POST[$filter_key."_elements"][$i]);
      unset($_POST[$filter_key."_ops"][$i]);
      unset($_POST[$filter_key."_terms"][$i]);
      unset($_POST[$filter_key."_types"][$i]);
    }
  }	
}
$elementFilterSettings = array();
$elementFilterSettings[0] = $_POST["elementfilter_elements"];
$elementFilterSettings[1] = $_POST["elementfilter_ops"];
$elementFilterSettings[2] = $_POST["elementfilter_terms"];
$elementFilterSettings[3] = $_POST["elementfilter_types"];
	
$element->setVar('ele_filtersettings',$elementFilterSettings); // do not need to serialize this when assigning, since the elements class calls cleanvars from the xoopsobject on all properties prior to insertion, and that intelligently serializes properties that have been declared as arrays

// check that the checkboxes have no values, and if so, set them to "" in the processedValues array
if(!isset($_POST['elements-ele_forcehidden'])) {
    $processedValues['elements']['ele_forcehidden'] = "";
}
if(!isset($_POST['elements-ele_private'])) {
    $processedValues['elements']['ele_private'] = "";
}
foreach($processedValues['elements'] as $property=>$value) {
  $element->setVar($property, $value);
}

if($_POST['elements_ele_display'][0] == "all") {
	$display = 1;        
} else if($_POST['elements_ele_display'][0] == "none") {
	$display = 0;        
} else {
	$display = "," . implode(",", $_POST['elements_ele_display']) . ",";
}
$element->setVar('ele_display', $display);

if($_POST['elements_ele_disabled'][0] == "none") {
	$disabled = 0;        
} else if($_POST['elements_ele_disabled'][0] == "all"){
  $disabled = 1;        
} else {
  $disabled = "," . implode(",", $_POST['elements_ele_disabled']) . ",";
}
$element->setVar('ele_disabled', $disabled);

if(!$ele_id = $element_handler->insert($element)) {
  print "Error: could not save the display settings for element: ".$xoopsDB->error();
}

if($_POST['reload_element_pages']) {
  print "/* evalnow */ if(redirect=='') { redirect = 'reloadWithScrollPosition();'; }";
}

