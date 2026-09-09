<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################
/**
 * Shared response handling for the Public API: JSON envelopes and CORS.
 *
 * The original two endpoints, status and queue, print bare JSON and set no
 * Content-Type at all. Those are left exactly as they are, because the admin
 * check that decides whether the Public API may be switched on parses status's
 * output. New endpoints use the helpers here instead.
 */

if (!defined('FORMULIZE_PUBLIC_API_REQUEST')) {
    http_response_code(500);
    exit();
}

/**
 * Emit the CORS headers for this request, if the caller's origin is allowed.
 *
 * A request with no Origin header is same origin, or is not from a browser at all,
 * and needs no CORS negotiation. When the origin is not on the administrator's
 * allowlist we deliberately send no CORS headers, which is what makes the browser
 * refuse the response.
 *
 * This constrains browsers, not attackers: it is a hygiene control, not a security
 * boundary. The security boundary is that an unauthenticated caller gets anonymous
 * permissions and can only reach forms an administrator has opened to anonymous.
 *
 * @return void
 */
function formulize_publicApiSendCorsHeaders() {
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? trim($_SERVER['HTTP_ORIGIN']) : '';
    if ($origin === '') {
        return;
    }
    $allowedOrigins = formulize_publicApiAllowedOrigins();
    $normalisedOrigin = rtrim(strtolower($origin), '/');
    if (in_array('*', $allowedOrigins)) {
        header('Access-Control-Allow-Origin: *');
    } elseif (in_array($normalisedOrigin, $allowedOrigins)) {
        header('Access-Control-Allow-Origin: '.$origin);
        // The response varies by origin, so shared caches must not reuse it across sites.
        header('Vary: Origin');
    } else {
        return;
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    header('Access-Control-Max-Age: 86400');
}

/**
 * Send a JSON response and stop.
 * @param array payload The body to encode
 * @param int statusCode The HTTP status to send
 * @return void
 */
function formulize_publicApiSendJson($payload, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    print json_encode($payload);
    exit();
}

/**
 * Send an error envelope built from an exception, and stop.
 * @param FormulizeApiException e
 * @return void
 */
function formulize_publicApiSendException($e) {
    formulize_publicApiSendJson(array('error' => $e->toErrorArray()), $e->toHTTPStatusCode());
}

/**
 * Send an error envelope built from a code and message, and stop.
 * @param string code A short machine readable code
 * @param string message A human readable explanation
 * @param int statusCode The HTTP status to send
 * @return void
 */
function formulize_publicApiSendError($code, $message, $statusCode = 400) {
    formulize_publicApiSendJson(array('error' => array('code' => $code, 'message' => $message)), $statusCode);
}

/**
 * Read the request parameters for an endpoint.
 *
 * A JSON body is the primary way in, since it carries nested filters comfortably and
 * keeps an API key out of server logs. Query string parameters are supported for
 * simple cases; the router has already recovered them from the original request URI,
 * so they arrive whether or not the site's rewrite rule carries the QSA flag.
 *
 * @return array The decoded parameters
 * @throws FormulizeApiException if a body was sent but is not valid JSON
 */
function formulize_publicApiReadParameters() {
    $body = file_get_contents('php://input');
    if (trim((string) $body) === '') {
        return $_GET;
    }
    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        throw new FormulizeApiException(
            'The request body must be a JSON object: '.json_last_error_msg(),
            'invalid_arguments'
        );
    }
    // Query string parameters stay available, with the body winning on any collision.
    return array_merge($_GET, $decoded);
}
