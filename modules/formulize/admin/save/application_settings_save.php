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

// this file handles saving of submissions from the application_settings page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
	return;
}

$application_handler = xoops_getmodulehandler('applications', 'formulize');
$appObject = $application_handler->get($_POST['formulize_admin_key']);

// The page submits the forms that were in the application when the page was opened, as well as the ones that are selected now.
// Only what the person changed is applied, so a form that someone else has put in the application, or taken out of it, since the
// page was opened stays as it is. Saving any tab of the page submits this, whatever the person was working on. A copy of the page
// from before it submitted the first list is taken to have been opened with the forms that are in the application now.
$fidsFrom = function($values) {
	return array_unique(array_filter(array_map('intval', $values)));
};
$currentForms = $fidsFrom($appObject->getVar('forms'));
$formsWhenOpened = isset($_POST['forms_when_opened']) ? $fidsFrom(explode(',', $_POST['forms_when_opened'])) : $currentForms;
$selectedForms = $fidsFrom((array) ($_POST['applications-forms'] ?? array()));
$forms = array_diff($currentForms, array_diff($formsWhenOpened, $selectedForms)); // without the ones the person removed
$forms = array_merge($forms, array_diff($selectedForms, $formsWhenOpened, $forms)); // with the ones the person added
$processedValues['applications']['forms'] = serialize(array_values($forms));

foreach($processedValues['applications'] as $property=>$value) {
	$appObject->setVar($property, $value);
}
$application_handler->insert($appObject);

// if the form name was changed, then force a reload of the page...reload will be the application id
if(isset($_POST['reload_settings']) AND $_POST['reload_settings'] == 1) {
	print "/* eval */ reloadWithScrollPosition();";
}