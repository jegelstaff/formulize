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

// handle public api requests, sent to /formulize-public-api/
// URL syntax should be:
// /formulize-public-api/{version}/{object-or-action}/{id}/etc...

// supported actions are:
// status - responds with JSON object of metadata
// queue/{id}/process - triggers parsing of the queue specified, if no {id} then runs all queues - queues currently only setup by internal APIs, and triggered by cron using the public API because cron is not logged in

// include mainfile, exit if that failed somehow, without a fatal PHP error
require_once '../../../mainfile.php';
if(!defined('XOOPS_MAINFILE_INCLUDED')) {
    exit();
}
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/common.php';
// clear out any extra stuff that would otherwise be appended to the http stream
icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

// check if the API is enabled, if so, set a flag that the rest of the API can use to tell if it should run or not
// call the right version of the API
$apiPathParts = explode('/', $_GET['apiPath']);
$version = FormulizeObject::sanitize_handle_name($apiPathParts[1]);
$objectOrAction = FormulizeObject::sanitize_handle_name($apiPathParts[2]);
$id = FormulizeObject::sanitize_handle_name($apiPathParts[3]);
$config_handler = $config_handler = xoops_gethandler('config');
$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
if($formulizeConfig['formulizePublicAPIEnabled'] OR ($objectOrAction == 'status' AND $id == 'formulize-check-if-public-api-is-properly-enabled-please')) {
    define('FORMULIZE_PUBLIC_API_REQUEST', 1);
    $apiFilePath = XOOPS_ROOT_PATH."/modules/formulize/public_api/$version/$objectOrAction.php";
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

