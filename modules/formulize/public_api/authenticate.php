<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################
/**
 * Authentication for the Public API.
 *
 * One seam, called once by the router before it dispatches, so that a future write
 * endpoint behaves the same way, so no endpoint can forget to authenticate and quietly
 * run as whatever session the browser carried, and so there is exactly one place to
 * consult an API key's scope if scoped or read only keys are added later. The router
 * exempts status, which has to answer before any credential is considered; see the
 * comment at the call site.
 *
 * Three ways in, in order of precedence:
 *
 *   1. an existing logged in session, for same origin JavaScript on this site
 *   2. an Authorization: Bearer {apikey} header, for servers and integrations
 *   3. anonymous
 *
 * Anonymous is a first class mode, not a failure. The caller becomes the anonymous
 * user and ordinary Formulize permissions decide what, if anything, they can see.
 * Since the anonymous group has no view_form permission until an administrator
 * grants it, this exposes nothing by default.
 */

if (!defined('FORMULIZE_PUBLIC_API_REQUEST')) {
    http_response_code(500);
    exit();
}

/**
 * Read the Authorization header, whatever the server configuration calls it.
 *
 * Some setups, notably CGI and some FastCGI configurations, do not expose it at all
 * unless the server is configured to pass it through, which is why the Public API
 * enable check tests for it.
 *
 * @return string The header value, or an empty string
 */
function formulize_publicApiGetAuthorizationHeader() {
    if (function_exists('getallheaders')) {
        $allHeaders = getallheaders();
        foreach ($allHeaders as $name => $value) {
            if (strtolower($name) === 'authorization') {
                return $value;
            }
        }
    }
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return $_SERVER['HTTP_AUTHORIZATION'];
    }
    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    return '';
}

/**
 * Establish who is making this request.
 *
 * Assigns the $xoopsUser and $icmsUser globals so that everything downstream behaves
 * exactly as it would during a normal page load. That assignment is not a convenience:
 * the data layer reads those globals directly, for the per-group filters that decide
 * which rows a user may see and for creator_email redaction, so a user object returned
 * without it would only be half honoured. This mirrors what the MCP server and
 * makecsv.php do. No session is created: the globals last for this request only, and
 * nothing is written to the session store or sent back as a cookie.
 *
 * @return object|null The authenticated user, or null for anonymous
 * @throws FormulizeApiException if a key was supplied but is not usable
 */
function formulize_publicApiAuthenticate() {

    global $xoopsUser, $icmsUser;

    // 1. an existing session wins, so same origin JavaScript needs no key at all
    if ($xoopsUser) {
        return $xoopsUser;
    }

    $authHeader = formulize_publicApiGetAuthorizationHeader();

    // 2. no credential offered, so this is an anonymous request
    if (trim($authHeader) === '') {
        return null;
    }

    if (!preg_match('/^\s*Bearer\s+(.+)$/i', $authHeader, $matches)) {
        throw new FormulizeApiException(
            'Invalid Authorization header format. Use: Bearer {api_key}',
            'authentication_error'
        );
    }
    $key = trim($matches[1]);

    // 3. resolve the key to the user it belongs to
    $apiKeyHandler = xoops_getmodulehandler('apikey', 'formulize');
    $apiKeyHandler->delete(); // clears out expired keys
    if (!$apikey = $apiKeyHandler->get($key)) {
        throw new FormulizeApiException('Invalid or expired API key', 'authentication_error');
    }

    $member_handler = xoops_gethandler('member');
    if (!$userObject = $member_handler->getUser($apikey->getVar('uid'))) {
        throw new FormulizeApiException('The user for this API key no longer exists', 'authentication_error');
    }

    // If scoped or read only API keys are added later, this is the one place to check
    // the scope carried by $apikey against the endpoint being called.

    $xoopsUser = $userObject;
    $icmsUser = $userObject;
    return $userObject;
}
