<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
###############################################################################
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

// Reports whether the browser kept a cookie that lets this screen be submitted.
//
// The embed theme asks once the screen has loaded. By then the browser has either kept the cookies
// from the page response or refused them, so the answer here is the real one: a screen that could
// keep neither cannot have a submission of its own validated, and saying so now is what puts the
// "open in a new window" link in front of the visitor rather than an error after they have typed.
//
// Either cookie will do. The session is what an ordinary request is validated against; an anonymous
// visitor inside somebody else's frame gets no session, and is validated against the bind cookie
// instead (see formulize_anonTokenBindKey()).
//
// This request is an XMLHttpRequest the framed page makes for itself, so the browser reports it as
// such rather than as a frame load. It therefore cannot ask whether it is embedded, and does not
// need to: whether the cookies came back is the entire question.
//
// Nothing about either cookie is revealed, only whether one arrived.

require_once "../../mainfile.php";
icms::$logger->disableLogger();

while(ob_get_level()) {
    ob_end_clean();
}

$canBeSubmitted = (isset($_COOKIE[formulize_sessionCookieName()]) OR formulize_anonTokenBindKey());

header('Content-Type: text/plain');
header('Cache-Control: no-store, no-cache, must-revalidate');
print ($canBeSubmitted ? 1 : 0);
