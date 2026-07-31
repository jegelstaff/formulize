<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2007 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
###############################################################################
##  Project: Formulize                                                       ##
###############################################################################

// Returns, as JSON, where an element is used and what deleting it would cost.
// Asked for on demand by the Usage and Delete links on the form Elements tab, because the report reads every
// screen, every element's settings and every custom code file - far too much to run for each row of a form's
// element list when the page loads.

include_once "../../../mainfile.php";
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/common.php';

header('Content-Type: application/json');

/**
 * Send a JSON reply and stop.
 * @param array $payload
 * @param int $status HTTP status code
 * @return void
 */
function formulize_elementUsageReply($payload, $status = 200) {
    http_response_code($status);
    print json_encode($payload);
    exit();
}

global $xoopsUser;
if (!$xoopsUser) {
    formulize_elementUsageReply(['error' => _AM_ELE_USAGE_ERROR_LOGIN], 403);
}

$elementId = isset($_GET['ele_id']) ? intval($_GET['ele_id']) : 0;
if (!$elementId) {
    formulize_elementUsageReply(['error' => _AM_ELE_USAGE_ERROR_NOELEMENT], 400);
}

$element_handler = xoops_getmodulehandler('elements', 'formulize');
if (!$elementObject = $element_handler->get($elementId)) {
    formulize_elementUsageReply(['error' => _AM_ELE_USAGE_ERROR_NOELEMENT], 404);
}

// the report exposes how a form is put together, so it is gated on the same permission as editing the form,
// which is what the links that open it are gated on too
$gperm_handler = xoops_gethandler('groupperm');
$groups = $xoopsUser->getGroups();
$mid = getFormulizeModId();
if (!$gperm_handler->checkRight("edit_form", $elementObject->getVar('fid'), $groups, $mid)) {
    formulize_elementUsageReply(['error' => _AM_ELE_USAGE_ERROR_PERMISSION], 403);
}

formulize_elementUsageReply($element_handler->elementUsageReport($elementObject));
