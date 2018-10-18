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

// this file handles saving of submissions from the screen_list_view page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}


$aid = intval($_POST['aid']);
$sid = $_POST['formulize_admin_key'];

$screens = $processedValues['screens'];


$screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
$screen = $screen_handler->get($sid);

// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $screen->getVar('fid'), $groups, $mid)) {
  return;
}

$advanceview = array();
$currentRow = 0;
$index = 0;
$numberOfRows = intval($_POST['rows']);

while($currentRow <= $numberOfRows) {
  if($_POST['sort-by'] == $index) {
    $sort = 1;
  }
  else {
    $sort = 0;
  }
  
  if($_POST['col-value'][$index] != NULL && $_POST['col-value'][$index] != 0) {
    $advanceview[$currentRow] = array($_POST['col-value'][$index], $_POST['search-value'][$index], $sort);
    $currentRow++;
  }
  
  //If the value is of the columns is the default, do not save it as part of the view
  if($_POST['col-value'][$index] == 0) {
    $currentRow++;
  }
  $index++;
}
$screens['advanceview'] = $advanceview;

$defaultview = array();
foreach($_POST['defaultview_group'] as $key=>$groupid) {
  $defaultview[$groupid] = $_POST['defaultview_view'][$key];
}

if(!isset($screens['limitviews'])) {
    $screens['limitviews'] = serialize(array(0=>'allviews'));
}

$screen->setVar('defaultview',serialize($defaultview)); // need to serialize things that have the array datatype, when they are manually generated here by us!
$screen->setVar('usecurrentviewlist',$screens['usecurrentviewlist']);
$screen->setVar('limitviews',$screens['limitviews']); // do not need to serialize things that come directly from the page as an array already, admin/save.php does this for us
$screen->setVar('advanceview', serialize($screens['advanceview'])); // need to serialize things that have the array datatype, when they are manually generated here by us!
$screen->setVar('useworkingmsg',(array_key_exists('useworkingmsg',$screens))?$screens['useworkingmsg']:0);
$screen->setVar('usescrollbox',(array_key_exists('usescrollbox',$screens))?$screens['usescrollbox']:0);
$screen->setVar('entriesperpage',$screens['entriesperpage']);
$screen->setVar('viewentryscreen',$screens['viewentryscreen']);


if(!$screen_handler->insert($screen)) {
  print "Error: could not save the screen properly: ".$xoopsDB->error();
}
?>
