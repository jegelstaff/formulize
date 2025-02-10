<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) Formulize Project                        ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
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
##  Author of this file: Formulize Project                                   ##
##  Project: Formulize                                                       ##
###############################################################################

// handle rest api requests, sent to /formulize-rest-api/
// URL syntax should be:
// /formulize-rest-api/{version}/{object-or-action}

// supported actions are:
// ping - responds with a 1, to show the API is active/available
// queue/{id} - triggers parsing of the queue specified, if no {id} then runs all queues

// include mainfile, exit if that failed somehow, without a fatal PHP error
require_once '../../../mainfile.php';
if(!defined('XOOPS_MAINFILE_INCLUDED')) {
    exit();
}
// clear out any extra stuff that would otherwise be appended to the http stream
icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

// check if the API is enabled, if so, set a flag that the rest of the API can use to tell if it should run or not
// call the right version of the API
$apiPathParts = explode('/', $_GET['apiPath']);
$version = floatval($apiPathParts[1]);
$objectOrAction = $apiPathParts[2];
$id = $apiPathParts[3];
$config_handler = $config_handler = xoops_gethandler('config');
$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
if($formulizeConfig['formulizeRESTAPIEnabled'] OR ($objectOrAction == 'ping' AND $id == 'formulize-check-if-rest-api-is-properly-enabled-please')) {
    define('FORMULIZE_RESTAPI_REQUEST', 1);
    $apiFilePath = XOOPS_ROOT_PATH."/modules/formulize/rest/$version/$objectOrAction.php";
    if(file_exists($apiFilePath)) {
        include_once $apiFilePath;
    } else {
        // no file for the requested api object or action, 404
        http_response_code(404);
    }

// API disabled, fail with 503 - service unavailable 
} else {
    http_response_code(503);
}

