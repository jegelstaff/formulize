<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################

// The manifest that lets people install the site as an app: its name, icons and colours, and where
// it starts. See formulize_pwaManifest() in modules/formulize/include/functions.php.
//
// Never cached, so a change on the Appearance page, or a new icon, reaches the apps already installed
// the next time their browser reads the manifest again.

require_once "../../../mainfile.php";
icms::$logger->disableLogger();
while (ob_get_level()) {
    ob_end_clean();
}

if (!formulize_pwaEnabled() OR !formulize_pwaSecureContext()) {
    http_response_code(404);
    exit();
}

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: no-cache');
echo json_encode(formulize_pwaManifest(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
