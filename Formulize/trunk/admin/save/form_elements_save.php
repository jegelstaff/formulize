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


$fid = intval($_POST['formulize_admin_key']);

// invoke the necessary objects
$element_handler = xoops_getmodulehandler('elements','formulize');

// group elements by id
$processedElements = array();
foreach($processedValues['elements'] as $property=>$values) {
  foreach($values as $key=>$value) {
    $processedElements[$key][$property] = $value;
  }
}

// retrieve all the elements that belong to this form
$elements = $element_handler->getObjects2(null,$fid);

// modify elements
foreach($elements as $element) {
  $ele_id = $element->getVar('ele_id');

  // reset elements to deault
  $element->setVar('ele_display',0);
  $element->setVar('ele_private',0);

  // apply settings submitted by user
  foreach($processedElements[$ele_id] as $property=>$value) {
    $element->setVar($property,$value);
  }

  // presist changes
  if(!$element_handler->insert($element)) {
    print "Error: could not save the form elements properly: ".mysql_error();
  }
}
?>
