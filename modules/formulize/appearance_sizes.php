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

// Advanced sizes: the Appearance page's size settings, one part of the
// interface at a time. The page is a live preview of the theme's sample screens
// (appearance_preview.php) and an inspector: click a part of the preview, such
// as a button or a list row, and the inspector shows the sizes of that part,
// which apply to every one of it on the site. With nothing selected, it lists
// every size that has been changed.
//
// What it saves is the Size preset (appearance_size) and the sizes changed on
// top of it (appearance_sizeoverrides), in the theme's generated appearance
// stylesheet, the same way the Appearance page saves everything else. The
// sizes, their limits and the parts of the interface are all in
// include/appearance_tokens.json; the page's behaviour is include/js/appearance_sizes.js.
//
// Webmasters only, like the style guide. Linked from the Size section of the
// Appearance page, for themes built on the size tokens.

require_once "../../mainfile.php";

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/appearance.php';

global $xoopsUser, $xoopsConfig;
if (!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
	redirect_header(XOOPS_URL, 3, _NOPERM);
	exit();
}

$theme = formulize_resolveAppearanceTheme(isset($_POST['theme']) ? (string) $_POST['theme'] : (isset($_GET['theme']) ? (string) $_GET['theme'] : ''));
$appearanceUrl = XOOPS_URL . '/modules/formulize/admin/ui.php?page=appearance&theme=' . urlencode($theme);
$pageUrl = XOOPS_URL . '/modules/formulize/appearance_sizes.php?theme=' . urlencode($theme);
$error = '';

// Saving: the preset and the changed sizes, on top of the theme's other settings,
// which are kept as they are. Then back here, so a reload doesn't post again.
if (isset($_POST['appearance_sizes_save']) AND formulize_appearanceThemeUsesSizes($theme)) {
	if (!$GLOBALS['xoopsSecurity']->check(true, false, 'formulize_appearance_sizes_token')) {
		$error = 'Nothing was saved, because the page had been open too long. Make the changes again and save.';
	} else {
		$settings = formulize_getAppearanceSettings($theme);
		$settings['appearance_size'] = isset($_POST['appearance_size']) ? (string) $_POST['appearance_size'] : '';
		$settings['appearance_sizeoverrides'] = isset($_POST['appearance_sizeoverrides']) ? (string) $_POST['appearance_sizeoverrides'] : '';
		if (formulize_regenerateAppearanceCss(formulize_sanitizeAppearanceSettings($settings, $theme), $theme)) {
			header('Location: ' . $pageUrl . '&saved=1');
			exit();
		}
		$error = 'Nothing was saved. The ' . $theme . " theme's settings are kept in its generated stylesheet, and that file could not be written to " . formulize_getAppearanceDir($theme) . '. Make that folder writable by the web server and save again.';
	}
}

$screens = formulize_appearanceThemeUsesSizes($theme) ? formulize_getAppearancePreviewScreens($theme) : array();
$settings = formulize_getAppearanceSettings($theme);
$map = formulize_appearanceTokenMap();
$defaults = formulize_appearanceSizeDefaults($theme);
foreach ($map['tokens'] as $token => $entry) {
	$map['tokens'][$token]['default'] = isset($defaults[$token]) ? $defaults[$token] : null;
}
$overrides = json_decode($settings['appearance_sizeoverrides'], true);
$editorData = array(
	'theme' => $theme,
	'map' => array(
		'types' => $map['types'],
		'screens' => $screens,
		'components' => $map['components'],
		'gaps' => $map['gaps'],
		'tokens' => $map['tokens'],
	),
	'presets' => formulize_appearanceSizePresets(),
	'preset' => $settings['appearance_size'],
	'overrides' => (is_array($overrides) AND $overrides) ? $overrides : new stdClass(), // an object, even when empty
	'previewUrl' => XOOPS_URL . '/modules/formulize/appearance_preview.php?theme=' . urlencode($theme) . '&screen=',
);

$cssPath = '/modules/formulize/templates/css/appearance_sizes.css';
$jsPath = '/modules/formulize/include/js/appearance_sizes.js';
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Advanced sizes - <?php echo htmlspecialchars($theme, ENT_QUOTES); ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo XOOPS_URL . formulize_uiStylesheetPath(); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo XOOPS_URL . $cssPath . '?v=' . formulize_get_file_version($cssPath); ?>">
</head>
<body class="formulize-sizes">
<?php if (!$screens) { ?>
<main class="formulize-sizes__unsupported">
	<h1>Advanced sizes</h1>
	<p>The <?php echo htmlspecialchars($theme, ENT_QUOTES); ?> theme isn't built on Formulize UI's size settings<?php echo formulize_appearanceThemeUsesSizes($theme) ? ', or has no sample screens to preview them on' : ''; ?>, so there are no sizes to adjust for it here.</p>
	<p><a href="<?php echo htmlspecialchars($appearanceUrl, ENT_QUOTES); ?>">Back to Appearance</a></p>
</main>
<?php } else { ?>
<div class="formulize-sizes__app">
	<header class="formulize-sizes__top">
		<div class="formulize-sizes__crumbs">
			<a href="<?php echo htmlspecialchars($appearanceUrl, ENT_QUOTES); ?>">Appearance</a><span aria-hidden="true">/</span><h1>Advanced sizes</h1>
			<span class="formulize-sizes__theme" title="The theme being edited"><?php echo htmlspecialchars($theme, ENT_QUOTES); ?></span>
		</div>
		<div class="formulize-sizes__spacer"></div>
		<div class="formulize-sizes__preset">
			<span id="formulize-sizes-preset-label">Size preset</span>
			<div class="formulize-sizes__seg" role="group" aria-labelledby="formulize-sizes-preset-label" id="formulize-sizes-preset"></div>
		</div>
		<form class="formulize-sizes__actions" method="post" action="<?php echo htmlspecialchars($pageUrl, ENT_QUOTES); ?>" id="formulize-sizes-form">
			<?php echo $GLOBALS['xoopsSecurity']->getTokenHTML('formulize_appearance_sizes_token'); ?>
			<input type="hidden" name="theme" value="<?php echo htmlspecialchars($theme, ENT_QUOTES); ?>">
			<input type="hidden" name="appearance_size" id="formulize-sizes-size" value="">
			<input type="hidden" name="appearance_sizeoverrides" id="formulize-sizes-overrides" value="">
			<span class="formulize-sizes__count" id="formulize-sizes-count" hidden></span>
			<button type="button" class="formulize-sizes__btn" id="formulize-sizes-reset" disabled>Reset changes</button>
			<button type="submit" class="formulize-sizes__btn formulize-sizes__btn--primary" name="appearance_sizes_save" value="1" id="formulize-sizes-save">Save</button>
		</form>
	</header>
	<?php if ($error) { ?><p class="formulize-sizes__message formulize-sizes__message--error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></p><?php } ?>
	<?php if (isset($_GET['saved']) AND !$error) { ?><p class="formulize-sizes__message" role="status" id="formulize-sizes-saved">Saved. The sizes now apply across the site in the <?php echo htmlspecialchars($theme, ENT_QUOTES); ?> theme.</p><?php } ?>
	<div class="formulize-sizes__main">
		<section class="formulize-sizes__stage" aria-label="Preview">
			<div class="formulize-sizes__bar">
				<div class="formulize-sizes__tabs" role="tablist" id="formulize-sizes-screens"></div>
				<div class="formulize-sizes__seg" role="group" aria-label="Preview width" id="formulize-sizes-width">
					<button type="button" data-w="desktop" aria-pressed="true">Desktop</button>
					<button type="button" data-w="phone" aria-pressed="false">Phone</button>
				</div>
				<span class="formulize-sizes__hint" id="formulize-sizes-hint">Click anything in the preview to change its size.</span>
			</div>
			<div class="formulize-sizes__canvas" id="formulize-sizes-canvas">
				<iframe class="formulize-sizes__frame" id="formulize-sizes-frame" title="Preview"></iframe>
			</div>
		</section>
		<aside class="formulize-sizes__insp" aria-label="Settings" aria-live="polite">
			<div class="formulize-sizes__insp-head" id="formulize-sizes-insp-head"></div>
			<div class="formulize-sizes__insp-body" id="formulize-sizes-insp-body"></div>
		</aside>
	</div>
</div>
<script>var formulizeAppearanceSizes = <?php echo json_encode($editorData, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?>;</script>
<script src="<?php echo XOOPS_URL . $jsPath . '?v=' . formulize_get_file_version($jsPath); ?>"></script>
<?php } ?>
</body>
</html>
