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

// this file handles saving of changes on the home page

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}

// if deletion requested and the user has permission for that
if(isset($_POST['deleteform']) AND $_POST['deleteform'] > 0 AND $gperm_handler->checkRight("delete_form", intval($_POST['deleteform']), $xoopsUser->getGroups(), getFormulizeModId())) {
  $form_handler = xoops_getmodulehandler('forms', 'formulize');
  $formObject = $form_handler->get($_POST['deleteform']);
  if(!$formObject->getVar('lockedform')) {
    $form_handler->delete(intval($_POST['deleteform']));
    print "/* evalnow */ reloadWithScrollPosition()";
  } else {
    print "Error: this form is locked!";
  }
}

if((isset($_POST['cloneform']) AND $_POST['cloneform'] > 0) OR (isset($_POST['cloneformdata']) AND $_POST['cloneformdata'] > 0)) {
  $formToClone = (isset($_POST['cloneform']) AND $_POST['cloneform'] > 0) ? intval($_POST['cloneform']) : intval($_POST['cloneformdata']);
  $cloneData = (isset($_POST['cloneform']) AND $_POST['cloneform'] > 0) ? false : true;
  $form_handler = xoops_getmodulehandler('forms', 'formulize');
  $form_handler->cloneForm($formToClone, $cloneData);
  print "/* evalnow */ reloadWithScrollPosition()";
}

if(isset($_POST['lockdown']) AND $_POST['lockdown'] > 0 AND $gperm_handler->checkRight("delete_form", intval($_POST['lockdown']), $xoopsUser->getGroups(), getFormulizeModId())) {
 	$form_handler = xoops_getmodulehandler('forms', 'formulize');
  $formObject = $form_handler->get($_POST['lockdown']);
  if(!$formObject->getVar('lockedform')) {
    if(!$form_handler->lockForm(intval($_POST['lockdown']))) {
    	print "Error: could not lock the form";
    } else {
    	print "/* evalnow */ reloadWithScrollPosition()";
    }
  } else {
    print "Error: this form is locked!";
  }
}
?>
