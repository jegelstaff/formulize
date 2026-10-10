<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2010 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
###############################################################################

// Saves a screen's Appearance tab: the look and page width of its page, and its introductory text.
// Included by admin/save.php, which has set up $processedValues, $gperm_handler, $groups and $mid.

if(!isset($processedValues)) {
  return;
}

$sid = intval($_POST['formulize_admin_key']);
$screens = isset($processedValues['screens']) ? $processedValues['screens'] : array();

$screen_handler = xoops_getmodulehandler('screen', 'formulize');
if(!$sid OR !$screen = $screen_handler->get($sid)) {
  return;
}

$form_handler = xoops_getmodulehandler('forms', 'formulize');
$formObject = $form_handler->get($screen->getVar('fid'));
if($formObject->getVar('lockedform')) {
  return;
}
if(!$gperm_handler->checkRight("edit_form", $screen->getVar('fid'), $groups, $mid)) {
  return;
}

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/appearance.php';
$appearanceTheme = formulize_getDefaultAppearanceTheme();

// the look: one the site's theme has, or '' for the site's own. The fields are only on the tab when the
// theme uses them, so one that wasn't sent is left as it is
if(isset($screens['look'])) {
  $look = (string) $screens['look'];
  $looks = $appearanceTheme ? formulize_getAppearanceLooks($appearanceTheme) : array();
  $screen->setVar('look', isset($looks[$look]) ? $look : '');
}
if(isset($screens['pagewidth'])) {
  $screen->setVar('pagewidth', $screens['pagewidth'] == 'full' ? 'full' : 'look');
}

// the introductory text is the administrator's own markup, kept as it was written (see
// formulizeScreen::introductoryText)
if(isset($screens['toptext'])) {
  $screen->setVar('toptext', trim((string) $screens['toptext']));
  $screen->setVar('toptextcode', !empty($screens['toptextcode']) ? 1 : 0);
}

if(!$screen_handler->insert($screen)) {
  print "Error: could not save the screen's appearance: ".$xoopsDB->error();
}
