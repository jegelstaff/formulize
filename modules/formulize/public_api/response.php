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
 * Check the caller's origin, and emit the CORS headers for it.
 *
 * A browser puts an Origin header on every cross origin request, and cannot be talked out
 * of it, so an origin that is present and not on the administrator's allowlist is refused
 * here with a 403, before any endpoint runs. Leaving it to the browser to discard the
 * response would mean a site that is not allowed could still make this one do the work, and
 * incur whatever side effects that work has. This site's own address is always allowed, since
 * browsers send an Origin header on same origin POSTs too, and rejecting those would break
 * the site's own Javascript whenever the allowlist is blank.
 *
 * A request with no Origin header at all is allowed through to be authenticated as usual.
 * That is not a hole in the above: a browser never omits the header on a cross origin
 * request, so the only callers that arrive without one are same origin GETs, where the
 * browser omits it by design, and things that are not browsers, which could put any origin
 * they liked on the wire anyway. The allowlist governs browsers on other websites, which is
 * what it is for, and it is not the security boundary. The boundary is the API key and the
 * Formulize permissions of the user it belongs to, or the permissions of the anonymous user
 * when no key is supplied.
 *
 * @return void
 */
function formulize_publicApiEnforceOrigin() {
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? trim($_SERVER['HTTP_ORIGIN']) : '';
    if ($origin === '') {
        return;
    }
    $allowedOrigins = formulize_publicApiAllowedOrigins();
    // Echo back the normalised origin, not the header as it arrived. A browser compares this
    // against its own origin byte for byte, and our matching is deliberately more forgiving
    // than that, so an origin that matched only because of the normalisation has to be
    // answered in its normalised form or the browser will reject an allowed caller.
    $normalisedOrigin = rtrim(strtolower($origin), '/');
    if (formulize_publicApiAllowsEveryOrigin($allowedOrigins)) {
        header('Access-Control-Allow-Origin: *');
    } elseif (formulize_publicApiOriginIsThisSite($normalisedOrigin)
        OR formulize_publicApiOriginIsAllowed($normalisedOrigin, $allowedOrigins)) {
        header('Access-Control-Allow-Origin: '.$normalisedOrigin);
        // The response varies by origin, so shared caches must not reuse it across sites.
        header('Vary: Origin');
    } else {
        header('Vary: Origin');
        formulize_publicApiSendError(
            'origin_not_allowed',
            'Requests from '.$normalisedOrigin.' are not allowed by this site. An administrator can allow it in the Formulize preferences, under the websites allowed to call the Public API.',
            403
        );  // sends the error envelope and exits
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
 * Explain a permission refusal that may really be a stripped Authorization header.
 *
 * This is the failure mode that costs people an afternoon. A server that does not pass the
 * Authorization header through to PHP - CGI and some FastCGI setups, unless told to - drops
 * the caller's API key before anything here ever sees it. The request is then anonymous, and
 * anonymous almost never has permission, so the caller is told they do not have permission to
 * view the form. They go and check the key, the user, the groups and the form permissions,
 * all of which are fine, because the problem is in the web server configuration and nothing
 * in the response points there.
 *
 * So when a request is refused on permissions AND arrived with no Authorization header at all,
 * say both of the things it could mean. It is stated as the fact it is - no header arrived -
 * rather than as a diagnosis, because a genuinely anonymous caller hitting a genuine
 * permissions problem gets this too, and for them the first sentence is the whole answer.
 *
 * @param string code The error code about to be sent
 * @return string The hint, or an empty string when it does not apply
 */
function formulize_publicApiAuthHeaderHint($code) {
    if ($code !== 'permission_denied') {
        return '';
    }
    // A session authenticated caller was not treated as anonymous, so none of this applies to
    // them, however their request was refused.
    if (!empty($GLOBALS['formulize_publicApiUser'])) {
        return '';
    }
    if (!function_exists('formulize_publicApiGetAuthorizationHeader')
        OR trim(formulize_publicApiGetAuthorizationHeader()) !== '') {
        return '';
    }
    return 'This request carried no Authorization header, so it was handled as anonymous, and what it '
        .'may read is decided by the permissions of the Anonymous group. If you did send an API key, '
        .'then this web server is not passing the Authorization header through to PHP, and the key never '
        .'arrived: on Apache, adding "CGIPassAuth On" to the .htaccess file at the root of the site '
        .'usually solves it. An administrator can confirm which it is on the Formulize API keys page.';
}

/**
 * Send an error envelope built from an exception, and stop.
 * @param FormulizeApiException e
 * @return void
 */
function formulize_publicApiSendException($e) {
    $error = $e->toErrorArray();
    if ($hint = formulize_publicApiAuthHeaderHint($error['code'])) {
        $error['hint'] = $hint;
    }
    formulize_publicApiSendJson(array('error' => $error), $e->toHTTPStatusCode());
}

/**
 * Send an error envelope built from a code and message, and stop.
 * @param string code A short machine readable code
 * @param string message A human readable explanation
 * @param int statusCode The HTTP status to send
 * @return void
 */
function formulize_publicApiSendError($code, $message, $statusCode = 400) {
    $error = array('code' => $code, 'message' => $message);
    if ($hint = formulize_publicApiAuthHeaderHint($code)) {
        $error['hint'] = $hint;
    }
    formulize_publicApiSendJson(array('error' => $error), $statusCode);
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
