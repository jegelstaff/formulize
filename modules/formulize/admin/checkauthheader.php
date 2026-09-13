<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################
/**
 * Re-test whether this server passes the Authorization header through to PHP.
 *
 * Answers {"passthrough": true|false|null, "message": "..."} to the button that the
 * Authorization header warning renders - see formulize_publicApiAuthHeaderWarningHtml()
 * in include/functions.php for what that warning is and why it exists.
 *
 * The point of this file is timing. The answer is cached for a few minutes so that ordinary
 * admin pages are not making an HTTP round trip every time they render, but someone who has
 * just added CGIPassAuth to their .htaccess wants to know whether it worked now. This forces
 * a fresh probe and updates the cache with what it finds, so the warning is gone the next
 * time any admin page renders.
 *
 * Webmasters only. The probe is a request this server makes to itself, at a fixed URL with a
 * fixed fake credential, so it cannot be aimed anywhere or used to read anything - but there
 * is no reason for anyone else to be able to make the server issue it.
 */

include_once '../../../mainfile.php';
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/common.php';
if(isset(icms::$logger)) {
    icms::$logger->disableLogger();
}
while(ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');

global $xoopsUser;
if(!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
    http_response_code(403);
    print json_encode(array('passthrough' => null, 'message' => 'Only a webmaster can run this test.'));
    exit();
}

$passthrough = formulize_authHeaderPassthrough(0); // 0 means do not use the cached answer

if($passthrough === true) {
    $message = 'The Authorization header is reaching Formulize. API keys will work.';
} elseif($passthrough === false) {
    $message = 'The Authorization header is still being stripped before it reaches Formulize.';
} else {
    // Null means the probe could not reach the status endpoint at all, which says nothing
    // about the header either way, and would be actively misleading reported as a failure.
    $message = 'The test could not be completed: this server could not make a request to itself at '
        .formulize_selfRequestUrl('/formulize-public-api/v1/status')
        .'. That is a separate problem from the Authorization header, and the Public API will not work until it is fixed.';
}

print json_encode(array('passthrough' => $passthrough, 'message' => $message));
