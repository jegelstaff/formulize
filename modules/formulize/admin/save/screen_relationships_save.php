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

// this file handles saving of submissions from the screen_relationships page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

if($_POST['deleteframework']) {
    $framework_handler = xoops_getmodulehandler('frameworks','formulize');
    $frameworkObject = $framework_handler->get($_POST['deleteframework']);
    if(!$framework_handler->delete($frameworkObject)) {
        print "Error: could not delete the requested relationship.";
    } else {
        print "/* eval */ reloadWithScrollPosition();";
    }
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

if($screens['type'] == 'multiPage') {
  $screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
} else if($screens['type'] == 'listOfEntries') {
  $screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
} else if($screens['type'] == 'form') {
  $screen_handler = xoops_getmodulehandler('formScreen', 'formulize');
} else if($screens['type'] == 'calendar') {
  $screen_handler = xoops_getmodulehandler('calendarScreen', 'formulize');
} else if($screens['type'] == 'template') {
  $screen_handler = xoops_getmodulehandler('templateScreen', 'formulize');
}

if ("new" != $sid) {
    $screen = $screen_handler->get($sid);
    if (null == $screen) {
        error_log("coald not load screen with id ".print_r($sid, true));
    }
    $originalFrid = $screen->getVar('frid');
    $screen->setVar('frid',$screens['frid']);

    if (!$sid = $screen_handler->insert($screen)) {
        print "Error: could not save the screen properly: ".$xoopsDB->error();
    }

    if ($originalFrid != $screens['frid']) {
        print '/* eval */ reloadWithScrollPosition();';
    }
}
