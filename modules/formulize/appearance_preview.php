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
// the saved stylesheet is loaded here.
//
// The sample is the theme's own markup (themes/<theme>/appearance_preview/,
// see formulize_renderAppearancePreview), drawn with the stylesheets a real
// page of the theme loads, in the same order: Formulize UI, Formulize's own
// stylesheet, the theme's reset and main stylesheets, and the theme's generated
// appearance stylesheet, so its colours, fonts and saved sizes show. No
// scripts: the editor handles every click in the preview itself.
//
// Webmasters only, like the editor.

require_once "../../mainfile.php";

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/appearance.php';

global $xoopsUser;
if (!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
	header('HTTP/1.1 403 Forbidden');
	exit();
}

$theme = formulize_resolveAppearanceTheme(isset($_GET['theme']) ? (string) $_GET['theme'] : '');
$screen = isset($_GET['screen']) ? (string) $_GET['screen'] : '';
$markup = formulize_renderAppearancePreview($screen, $theme);
if ($markup === '') {
	header('HTTP/1.1 404 Not Found');
	exit();
}

$stylesheets = array('/modules/formulize/templates/css/formulize-ui.css', '/modules/formulize/templates/css/formulize.css');
foreach (array('reset.css', 'style.css') as $file) {
	if (is_file(ICMS_THEME_PATH . '/' . $theme . '/css/' . $file)) {
		$stylesheets[] = '/themes/' . $theme . '/css/' . $file;
	}
}
$appearanceCss = formulize_getAppearanceCssPath($theme);
if ($appearanceCss AND is_file($appearanceCss)) {
	$stylesheets[] = substr($appearanceCss, strlen(XOOPS_ROOT_PATH));
}

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?php echo htmlspecialchars(formulize_getAppearancePreviewScreens($theme)[$screen], ENT_QUOTES); ?></title>
<?php foreach ($stylesheets as $path) {
	echo '<link rel="stylesheet" type="text/css" media="all" href="' . XOOPS_URL . htmlspecialchars($path, ENT_QUOTES) . '?v=' . formulize_get_file_version($path) . '">' . "\n";
} ?>
</head>
<body id="formulize" class="en formulize-screen formulize-appearance-preview">
<?php echo $markup; ?>
</body>
</html>
