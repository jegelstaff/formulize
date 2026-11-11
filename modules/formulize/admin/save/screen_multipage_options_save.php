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

// this file handles saving of submissions from the screen_multipage_options page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if(!isset($processedValues)) {
  return;
}


$aid = intval($_POST['aid']);
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

$buttonText = array(
    'thankyoulinktext'=>$_POST['thankyoulinktext'],
    'leaveButtonText'=>$_POST['leaveButtonText'],
    'prevButtonText'=>$_POST['prevButtonText'],
    'saveButtonText'=>$_POST['saveButtonText'],
    'nextButtonText'=>$_POST['nextButtonText'],
    'finishButtonText'=>$_POST['finishButtonText'],
    'printableViewButtonText'=>$_POST['printableViewButtonText'],
		'closeButtonText'=>$_POST['closeButtonText']
);

// "Back to the previous page" is no done destination of the screen's own: Formulize works out where the user came from
$donedest = (isset($_POST['donedestmode']) AND $_POST['donedestmode'] == 'url' AND isset($screens['donedest'])) ? trim((string) $screens['donedest']) : '';

$column1width = null;
if(isset($_POST['singlecolumn1width']) AND isset($screens['displaycolumns']) AND $screens['displaycolumns'] == 1) {
    $column1width = $_POST['singlecolumn1width'];
} elseif(isset($_POST['doublecolumn1width']) AND isset($screens['displaycolumns']) AND $screens['displaycolumns'] == 2) {
    $column1width = $_POST['doublecolumn1width'];
}

// parse the drag-to-reorder form order (jQuery UI sortable serialize string, eg: formorderitem[]=3&formorderitem[]=5)
// only present/relevant when the screen unifies more than one form; skip the property entirely when absent so we don't wipe an existing order
$formorder = null;
if(isset($_POST['formorder']) AND trim($_POST['formorder']) !== "") {
    $formorderParts = explode("formorderitem[]=", str_replace("&", "", $_POST['formorder']));
    unset($formorderParts[0]); // the piece before the first key is empty
    $formorder = array();
    foreach($formorderParts as $formorderFid) {
        if(is_numeric($formorderFid)) {
            $formorder[] = intval($formorderFid);
        }
    }
}

// delegate persistence to the shared upsert apparatus (handles the insert serialization quirk, handle uniqueness, etc.)
$properties = array(
    'paraentryform' => $screens['paraentryform'],
    'paraentryrelationship' => $screens['paraentryrelationship'],
    'donedest' => $donedest,
    'buttontext' => $buttonText, // plain array; upsert serializes it
    'printall' => $screens['printall'],
    // a screen with thanks text, or one whose Thank You page is on, always ends on the Thank You page, and has the
    // Save and Continue button - the upsert makes sure of both
    'finishisdone' => !empty($_POST['showthankspage']) ? 0 : 1,
    'displaycolumns' => isset($screens['displaycolumns']) ? $screens['displaycolumns'] : 2,
    'column1width' => $column1width,
    'column2width' => isset($screens['column2width']) ? $screens['column2width'] : null,
    'showtabs' => !empty($screens['showtabs']) ? 1 : 0,
    'showprevbutton' => !empty($screens['showprevbutton']) ? 1 : 0,
    'showsavebutton' => !empty($screens['showsavebutton']) ? 1 : 0,
    'showclosebutton' => !empty($screens['showclosebutton']) ? 1 : 0,
    'shownextbutton' => !empty($screens['shownextbutton']) ? 1 : 0,
    'showpagetitles' => !empty($screens['showpagetitles']) ? 1 : 0,
    'showpageindicator' => !empty($screens['showpageindicator']) ? 1 : 0,
    'showpageselector' => !empty($screens['showpageselector']) ? 1 : 0,
    'elementdefaults' => isset($screens['elementdefaults']) ? $screens['elementdefaults'] : "",
    'reloadblank' => $screens['reloadblank'],
);
if(is_array($formorder)) {
    $properties['formorder'] = $formorder; // upsert serializes it (formorder is an array field)
}
try {
  formulizeHandler::upsertMultiPageScreen($properties, $sid);
} catch (Exception $e) {
  print "Error: could not save the screen properly: ".$e->getMessage();
}

