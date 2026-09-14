<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
###############################################################################
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

// Reports whether the browser sent this site's session cookie back with this request.
//
// The embed theme asks once the screen has loaded. By then the browser has either kept the cookie
// from the page response or refused it, so the answer here is the real one: a screen that cannot
// keep a cookie has no session, and without a session a submission cannot be saved.
//
// Nothing about the session is revealed, only whether a cookie arrived.

require_once "../../mainfile.php";
icms::$logger->disableLogger();

while(ob_get_level()) {
    ob_end_clean();
}

global $icmsConfig;
$sessionName = ($icmsConfig['use_mysession'] AND $icmsConfig['session_name'] != '')
    ? $icmsConfig['session_name'] : session_name();

header('Content-Type: text/plain');
header('Cache-Control: no-store, no-cache, must-revalidate');
print (isset($_COOKIE[$sessionName]) ? 1 : 0);
