<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################

// The service worker of the site installed as an app. What it does is in worker.js: it shows a page
// saying the device is offline when a page can't be loaded, and nothing else.
//
// Browsers check this for changes as pages of the site are opened, and install it again whenever any
// byte of it differs. It may be kept for a day, so a browser asks the server at most once a day, rather
// than alongside every page. So everything the worker keeps is written into it here: the offline page
// itself, and the list of stylesheets it is drawn with, whose addresses carry their own versions. A change
// to any of them, such as a colour on the Appearance page, makes a new worker, which a device gets within
// a day, and which takes over once the site has been closed on that device. The cache it keeps them in is
// named after all of that too.
//
// When an administrator turns the app off, this serves remove-worker.js instead, which deletes the
// worker's cache and unregisters the worker on every device that opens a page of the site again, within a
// day. That works even though the pages no longer register a worker: the installed worker is what checks.

require_once "../../../mainfile.php";
icms::$logger->disableLogger();
while (ob_get_level()) {
    ob_end_clean();
}

if (!formulize_pwaSecureContext()) {
    http_response_code(404);
    exit();
}

$sitePath = formulize_pwaSitePath();
// one cache name prefix per site, since sites in folders side by side on one host share their caches
$cachePrefix = 'formulize-app-' . substr(sha1($sitePath), 0, 8) . '-';

header('Content-Type: application/javascript; charset=utf-8');
// a day is as long as browsers keep a worker before asking again, whatever this says. private, so a
// shared cache along the way can't keep it longer. Without the headers the session sent, which say
// not to keep it at all.
header('Cache-Control: private, max-age=86400');
header_remove('Expires');
header_remove('Pragma');
header('Service-Worker-Allowed: ' . $sitePath);

if (formulize_pwaEnabled()) {
    $offlinePage = formulize_pwaOfflinePage();
    $config = array(
        'cachePrefix' => $cachePrefix,
        'offlineUrl' => $sitePath . 'modules/formulize/pwa/offline',
        'offlineHtml' => $offlinePage['html'],
        'stylesheets' => $offlinePage['stylesheets'],
    );
    $config['version'] = substr(sha1(json_encode($config)), 0, 12);
    $worker = 'worker.js';
} else {
    $config = array('cachePrefix' => $cachePrefix);
    $worker = 'remove-worker.js';
}
echo 'self.formulizeApp = ' . json_encode($config, JSON_UNESCAPED_SLASHES) . ";\n";
readfile(__DIR__ . '/' . $worker);
