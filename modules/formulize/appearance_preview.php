<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
###############################################################################
##  Author of this file: Formulize Incorporated                              ##
##  Project: Formulize                                                       ##
###############################################################################

// One of a theme's sample screens, as a page of its own, for the preview in the
// advanced editor (appearance_editor.php), which shows it in an iframe and
// sets the settings being edited on it as they are changed: over the saved
// ones, or back to the theme's own, which the editor also sets explicitly since
// the saved stylesheet is loaded here. The 8.30 update shows it too.
//
// The page is formulize_appearancePreviewDocument's: the theme's own markup,
// with a look's stylesheet and logo. No scripts: the editor handles every click
// in the preview itself. The Looks tab draws the same page without asking for
// it here, which is quicker.
//
// Formulize module admins only, like the editor.

require_once "../../mainfile.php";

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/appearance.php';

// disable the debugger/logger
if (isset(icms::$logger)) {
    icms::$logger->disableLogger();
}
while (ob_get_level()) {
    ob_end_clean();
}

global $xoopsUser;
$gperm_handler = xoops_gethandler('groupperm');
if (!$xoopsUser OR !$gperm_handler->checkRight('module_admin', getFormulizeModId(), $xoopsUser->getGroups(), 1)) {
	header('HTTP/1.1 403 Forbidden');
	exit();
}

$theme = formulize_resolveAppearanceTheme(isset($_GET['theme']) ? (string) $_GET['theme'] : '');
$screen = isset($_GET['screen']) ? (string) $_GET['screen'] : '';
// the look being edited, as saved, which the editor shows its changes on; or the site look
$look = isset($_GET['look']) ? strtolower((string) $_GET['look']) : '';
// another theme's logo, to see this theme the way it would look with it, as the 8.30 update does with the site's logo
$logoTheme = (isset($_GET['logofrom']) AND isset(formulize_getAppearanceThemes()[(string) $_GET['logofrom']])) ? (string) $_GET['logofrom'] : null;
$page = formulize_appearancePreviewDocument($screen, $theme, formulize_getAppearancePreviewLookCssUrl($theme, $look), formulize_getAppearancePreviewLogoUrl($theme, $look, $logoTheme));
if ($page === '') {
	header('HTTP/1.1 404 Not Found');
	exit();
}

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
echo $page;
