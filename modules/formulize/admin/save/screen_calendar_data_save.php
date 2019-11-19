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

// this file handles saving of submissions from the screen_multipage_pages page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

//print_r($_POST);
//print_r($processedValues);


$aid = intval($_POST['aid']);
$sid = $_POST['formulize_admin_key'];
$op = $_POST['formulize_admin_op'];
$index = $_POST['formulize_admin_index'];

$screens = $processedValues['screens'];

$screen_handler = xoops_getmodulehandler('calendarScreen', 'formulize');
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

// get page titles

$datasets = $screen->getVar('datasets');

// alter the information based on a user add or delete
switch ($op) {
	case "adddata":
        $datasets[]= new formulizeCalendarScreenDataset();
		break;
	case "deldata":
		ksort($datasets);
        array_splice($datasets, $index, 1);
		break;
}

foreach($datasets as $i=>$dataset) {
    $datasets[$i]->setVar('fid', $_POST['fids'][$i] ? intval($_POST['fids'][$i]) : null);
    $datasets[$i]->setVar('frid', $_POST['frids'][$i] ? intval($_POST['frids'][$i]) : null);
    $datasets[$i]->setVar('scope', in_array($_POST['scopes'][$i], array('mine', 'group', 'all')) ? $_POST['scopes'][$i] : 'mine');
    $datasets[$i]->setVar('useaddicons', intval($_POST['useaddicons'][$i]));
    $datasets[$i]->setVar('usedeleteicons', intval($_POST['usedeleteicons'][$i]));
    $datasets[$i]->setVar('textcolor', $_POST['textcolors'][$i] ? strip_tags(htmlspecialchars($_POST['textcolors'][$i], ENT_QUOTES))  : null);
    $datasets[$i]->setVar('datehandle', $_POST['datehandles'][$i] ? strip_tags(htmlspecialchars($_POST['datehandles'][$i], ENT_QUOTES))  : null);
    $datasets[$i]->setVar('viewentryscreen', $_POST['viewentryscreens'][$i] ? intval($_POST['viewentryscreens'][$i]) : null);                                                                   
    $datasets[$i]->setVar('clicktemplate', $_POST['clicktemplates'][$i] ? $_POST['clicktemplates'][$i] : null);                                                                   
}

$screen->setVar('datasets',serialize($datasets));

if(!$screen_handler->insert($screen)) {
  print "Error: could not save the screen properly: ".$xoopsDB->error();
}

// reload the page if the state has changed
if($op == "adddata" OR $op=="deldata" OR $_POST['reload_calendar_data']) {
    print "/* eval */ reloadWithScrollPosition();";
}
?>
