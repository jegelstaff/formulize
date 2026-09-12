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

$formulize_publicApiStartTime = microtime(true);
$formulize_publicApiMaxExec = 60; // max seconds the script has to execute in. Based on the lowest time limit the script is operating under, could be fastcgi limit, php limit, something else...we could set this with config option in xoopsVersion.php if we want to get fancy and give the user control
set_time_limit(60);

// include mainfile, exit if that failed somehow, without a fatal PHP error
require_once '../../../mainfile.php';
if(!defined('XOOPS_MAINFILE_INCLUDED')) {
    exit();
}

// Work out the request path and the caller's own query string, before anything else has a
// chance to read $_GET.
//
// REQUEST_URI is the original request line, untouched by the internal rewrite, so it is the
// source of truth when a /formulize-public-api/ URL was used. Falling back to the apiPath
// parameter keeps direct calls to this file working, which is handy on a server whose
// rewrite rules are not set up.
$formulize_apiRequestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$formulize_apiRequestPath = (string) parse_url($formulize_apiRequestUri, PHP_URL_PATH);
if(stripos($formulize_apiRequestPath, '/formulize-public-api/') === 0) {
    $formulize_apiPath = ltrim($formulize_apiRequestPath, '/');
} else {
    $formulize_apiPath = isset($_GET['apiPath']) ? $_GET['apiPath'] : '';
}

// The rewrite rule substitutes its own query string. Without the QSA flag the caller's
// parameters never reach $_GET at all, and with QSA they arrive alongside a second apiPath
// that PHP would prefer over ours. Parsing the original request URI sidesteps both, and
// means an install whose rewrite rule predates QSA still passes parameters correctly.
$formulize_apiQuery = array();
$formulize_apiQueryString = parse_url($formulize_apiRequestUri, PHP_URL_QUERY);
if($formulize_apiQueryString) {
    parse_str($formulize_apiQueryString, $formulize_apiQuery);
}
unset($formulize_apiQuery['apiPath']); // routing is decided above, never by a query parameter
$_GET = $formulize_apiQuery;

include_once XOOPS_ROOT_PATH.'/modules/formulize/include/common.php';
// clear out any extra stuff that would otherwise be appended to the http stream
icms::$logger->disableLogger();
while(ob_get_level()) {
    ob_end_clean();
}

// check if the API is enabled, if so, set a flag that the rest of the API can use to tell if it should run or not
// call the right version of the API
$apiPathParts = explode('/', $formulize_apiPath);
$version = FormulizeObject::sanitize_handle_name($apiPathParts[1] ?? '');
$objectOrAction = FormulizeObject::sanitize_handle_name($apiPathParts[2] ?? '');
$id = FormulizeObject::sanitize_handle_name($apiPathParts[3] ?? '');
$method = FormulizeObject::sanitize_handle_name($apiPathParts[4] ?? '');
$subId = FormulizeObject::sanitize_handle_name($apiPathParts[5] ?? ''); // for endpoints that address one item, ie: an entry id
$formulize_publicApiGateResult = (isPublicAPIEnabled() OR ($objectOrAction == 'status' AND $id == 'formulize_check_if_public_api_is_properly_enabled_please'));

if($formulize_publicApiGateResult) {
    define('FORMULIZE_PUBLIC_API_REQUEST', 1);
    include_once XOOPS_ROOT_PATH.'/modules/formulize/class/apiexception.php';
    include_once XOOPS_ROOT_PATH.'/modules/formulize/public_api/response.php';
    include_once XOOPS_ROOT_PATH.'/modules/formulize/public_api/authenticate.php';

    // Settle the caller's origin first. A browser origin that the administrator has not allowed
    // is refused outright in here, including on the preflight, so that a site that is not allowed
    // never reaches an endpoint at all.
    formulize_publicApiEnforceOrigin();

    // A cross origin request carrying a JSON body or an Authorization header is preflighted,
    // so answer OPTIONS before doing any work.
    if(isset($_SERVER['REQUEST_METHOD']) AND $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit();
    }

    // Establish who is calling, once, before any endpoint runs.
    //
    // This belongs here rather than in each endpoint because the globals it assigns are what
    // the data layer reads. getData consults $xoopsUser directly for the per-group filters that
    // decide which rows a user may see, for the main form and every linked form (see
    // include/extract.php), and again when deciding whether to redact creator_email. An endpoint
    // that forgot to authenticate would not fail closed: it would run as whatever session the
    // browser happened to carry, which for a same origin page is a real, logged in user.
    //
    // status is exempt, and must stay exempt, or the Public API can never be enabled again.
    // Enabling the preference makes ICMS cURL its own server at this endpoint, sending a
    // well formed Authorization header carrying the fake key test-header-passthrough-check,
    // so that status can report whether the header survived the trip to PHP at all (see
    // icms_config_item_Handler::insert in libraries/icms/config/item/Handler.php). That
    // request carries no session cookie, so authenticating it would reject the fake key and
    // return an error envelope. The enable check looks for status == healthy, would not find
    // it, and forces the preference back to 0. The setting would simply refuse to stick, with
    // nothing to indicate why.
    $formulize_publicApiUser = null;
    if($objectOrAction != 'status') {
        try {
            $formulize_publicApiUser = formulize_publicApiAuthenticate();
        } catch (FormulizeApiException $e) {
            formulize_publicApiSendException($e); // sends the error envelope and exits
        }
    }

    $apiFilePath = XOOPS_ROOT_PATH."/modules/formulize/public_api/$version/$objectOrAction.php";
    if(file_exists($apiFilePath)) {
        include_once $apiFilePath; // INCLUDED IN GLOBAL SCOPE, so that it has access to all previously defined variables and functions - if this changes, dependent code in each endpoint may break.
    } else {
        // no file for the requested api object or action, 404
        http_response_code(404);
    }

// API disabled, fail with 503 - service unavailable
} else {
    http_response_code(503);
}
