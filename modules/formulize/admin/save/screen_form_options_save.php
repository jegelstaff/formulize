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

// this file handles saving of submissions from the screen_form_options page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}


//print_r($_POST);
//print_r($processedValues);


$aid = intval($_POST['aid']);
$sid = $_POST['formulize_admin_key'];

$screens = $processedValues['screens'];

$screen_handler = xoops_getmodulehandler('formScreen', 'formulize');
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
$screen->setVar('donedest',$screens['donedest']);
$screen->setVar('savebuttontext',$screens['savebuttontext']);
$screen->setVar('saveandleavebuttontext',$screens['saveandleavebuttontext']);
$screen->setVar('printableviewbuttontext',$screens['printableviewbuttontext']);
$screen->setVar('alldonebuttontext',$screens['alldonebuttontext']);
$screen->setVar('displayheading',array_key_exists('displayheading',$screens)?1:0);
$screen->setVar('reloadblank',$screens['reloadblank']);
// if formelements is not set, force to blank otherwise changes will not be saved
$screen->setVar('formelements', isset($screens['formelements']) ? $screens['formelements'] : "");
$screen->setVar('elementdefaults', isset($screens['elementdefaults']) ? $screens['elementdefaults'] : "");
$screen->setVar('displaycolumns', isset($screens['displaycolumns']) ? $screens['displaycolumns'] : 2);
$screen->setVar('column1width', isset($screens['column1width']) ? $screens['column1width'] : null);
$screen->setVar('column2width', isset($screens['column2width']) ? $screens['column2width'] : null);

if(!$screen_handler->insert($screen)) {
  print "Error: could not save the screen properly: ".$xoopsDB->error();
}

