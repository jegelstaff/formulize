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

// this file handles saving of submissions from the screen_list_headings_view page of the new admin UI

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

if($screens['decolumns']=="") {
  $screens['decolumns'] = serialize(array());
}
if($screens['hiddencolumns']=="") {
  $screens['hiddencolumns'] = serialize(array());
}

$screen->setVar('useheadings',(array_key_exists('useheadings',$screens))?$screens['useheadings']:0);
$screen->setVar('repeatheaders',$screens['repeatheaders']);
$screen->setVar('usesearchcalcmsgs',$screens['usesearchcalcmsgs']);
$screen->setVar('usesearch',(array_key_exists('usesearch',$screens))?$screens['usesearch']:0);
$screen->setVar('columnwidth',$screens['columnwidth']);
$screen->setVar('textwidth',$screens['textwidth']);
$screen->setVar('usecheckboxes',$screens['usecheckboxes']);
$screen->setVar('useviewentrylinks',(array_key_exists('useviewentrylinks',$screens))?$screens['useviewentrylinks']:0);
$screen->setVar('hiddencolumns',$screens['hiddencolumns']);
$screen->setVar('decolumns',$screens['decolumns']);
$screen->setVar('dedisplay',$screens['dedisplay']);
$screen->setVar('desavetext',$screens['desavetext']);

if(!$screen_handler->insert($screen)) {
  print "Error: could not save the screen properly: ".mysql_error();
}
?>
