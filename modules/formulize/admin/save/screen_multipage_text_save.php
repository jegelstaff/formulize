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

// this file handles saving of submissions from the screen_multipage_text page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}


$sid = $_POST['formulize_admin_key'];

$screens = $processedValues['screens'];


$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
if(!$screen = $screen_handler->get($sid)) {
  return; // no such form screen
}
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
// whether the screen has text for its Thank You page, which turns the Thank You page on - see formulizeHandler::upsertMultiPageScreen()
$hadThanksText = !formulize_authoredTextIsEmpty($screen->getVar('thankstext', 'n'));

// delegate persistence to the shared upsert apparatus (introtext/thankstext are passed and written to their files exactly as typed)
$properties = array(
    'introtext' => isset($screens['introtext']) ? $screens['introtext'] : '',
    'thankstext' => isset($screens['thankstext']) ? $screens['thankstext'] : '',
);
try {
  formulizeHandler::upsertMultiPageScreen($properties, $sid);
  // the Options tab shows the Thank You page as held on while there is thanks text, so it has to be drawn again
  // when that changes. Reloading after all the forms are saved keeps whatever the Options tab saved as well.
  if($hadThanksText != !formulize_authoredTextIsEmpty($properties['thankstext'])) {
    print "/* eval */ reloadWithScrollPosition();";
  }
} catch (Exception $e) {
  print "Error: could not save the screen properly: ".$e->getMessage();
}

