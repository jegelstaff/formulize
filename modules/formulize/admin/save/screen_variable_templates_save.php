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

// Saves the Variables sub-tab of a screen's Appearance tab: the screen's own variable templates, in
// templates/screens/<theme>/<sid>/variables/, which draw parts of a list such as $pageNavControls.
// Only the files the screen already has are written. Any other name that's posted is ignored, so a name from the
// request never becomes a path. A box that's emptied removes its file, and that part is drawn with the default.

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

$screen_handler = xoops_getmodulehandler('screen', 'formulize');
if(!$screen = $screen_handler->get(intval($_POST['formulize_admin_key']))) {
  return;
}
// CHECK IF THE FORM IS LOCKED DOWN AND SCOOT IF SO
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($screen->getVar('fid'));
if(!$formObject OR $formObject->getVar('lockedform')) {
  return;
}
// check if the user has permission to edit the form
if(!$gperm_handler->checkRight("edit_form", $screen->getVar('fid'), $groups, $mid)) {
  return;
}

$theme = $screen->getVar('theme');
$posted = isset($processedValues['variabletemplates']) ? $processedValues['variabletemplates'] : array();
$failed = array();
$removedOne = false;
foreach($screen_handler->variableTemplateFiles($screen->getVar('sid'), $theme) as $name => $file) {
  if(!isset($posted[$name]) OR is_array($posted[$name])) {
    continue;
  }
  $content = trim((string) $posted[$name]);
  if(!$screen->writeVariableTemplateFile($content, $name, $theme)) {
    $failed[] = $name;
  } elseif(!file_exists($file)) {
    $removedOne = true;
  }
}

if($failed) {
  print sprintf(_AM_FORMULIZE_SCREEN_VARIABLES_SAVE_FAILED, implode(', ', $failed), formulize_folderForPeople(FORMULIZE_SCREEN_TEMPLATES_PATH));
} elseif($removedOne) {
  // so the tab goes once the last of them is cleared, and the Templates tab offers to customize again
  print "/* eval */ reloadWithScrollPosition();";
}
