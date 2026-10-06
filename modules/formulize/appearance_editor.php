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

// The advanced editor: everything on the Appearance page, and the settings of
// each part of the interface, on a live preview. The page is the theme's sample
// screens (appearance_preview.php) and an inspector: click a part of the
// preview, such as a button or a list row, and the inspector shows that part's
// settings, which apply to every one of it on the site. With nothing selected,
// it shows the site-wide settings (the logo, colours, fonts, the look applied and page
// width, as on the Appearance page), and everything that has been changed.
//
// It saves the same settings the Appearance page does, the same way (see
// formulize_appearanceSubmittedSettings), plus the parts' own settings
// (appearance_overrides), all in the theme's generated appearance stylesheet.
// The parts and their settings are in include/appearance_tokens.json; the
// page's behaviour is include/js/appearance_editor.js.
//
// Webmasters only, like the style guide. Linked from the top of the Appearance
// page, for themes it can edit (formulize_getAppearanceEditorUrl).

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
$pageUrl = formulize_getAppearanceEditorUrl($theme);
$errors = array();
$settings = formulize_getAppearanceSettings($theme);

// What the editor starts from, and what a save sends back to it: the settings
// as they stand, in the editor's terms.
function formulize_appearanceEditorState($settings, $theme) {
	$colours = array();
	foreach (formulize_appearanceColourMap($theme) as $key => $colour) {
		$colours[$key] = $settings['appearance_' . $key] ? $settings['appearance_' . $key] : $colour['default'];
	}
	$uploads = array();
	foreach (array_keys(formulize_appearanceUploads()) as $uploadSetting) {
		$uploads[$uploadSetting] = (string) formulize_getAppearanceFileUrl(formulize_locateAppearanceFile($settings[$uploadSetting], $theme), $theme);
	}
	$overrides = json_decode($settings['appearance_overrides'], true);
	return array(
		'preset' => $settings['appearance_look'], // the look applied to the site
		// 'full', or a maximum width in pixels: the one chosen, or the theme's own
		'contentWidth' => formulize_appearanceContentWidth($settings, $theme),
		'overrides' => (is_array($overrides) AND $overrides) ? $overrides : new stdClass(), // an object, even when empty
		'colours' => $colours,
		'fonts' => array(
			'main' => $settings['appearance_font'] ? $settings['appearance_font'] : 'geist',
			'maincustom' => $settings['appearance_customfont'],
			'heading' => $settings['appearance_headingfont'] ? $settings['appearance_headingfont'] : 'geist',
			'headingcustom' => $settings['appearance_headingcustomfont'],
		),
		'uploads' => $uploads,
	);
}

// Saving: every setting, as the Appearance page saves them, and the logo and
// favicon if they were replaced or removed. The editor saves in the background
// (appearance_editor_ajax), and gets back what was saved, so it stays where it
// was; without scripts the form posts as usual and comes back here, so a reload
// doesn't post again. A background save sends X-Requested-With, which keeps the
// page's token good for the next save.
$saved = false;
if (isset($_POST['appearance_editor_save']) AND $pageUrl) {
	if (!$GLOBALS['xoopsSecurity']->check(true, false, 'formulize_appearance_editor_token')) {
		$errors[] = 'Nothing was saved, because the page had been open too long. Reload the page, make the changes again and save.';
	} else {
		$submitted = formulize_appearanceSubmittedSettings($_POST, $settings, $theme, $errors);
		$submitted = formulize_saveAppearanceUploads($submitted, $settings, $theme, false, $errors);
		$submitted = formulize_sanitizeAppearanceSettings($submitted, $theme);
		if (formulize_regenerateAppearanceCss($submitted, $theme)) {
			$saved = true;
			$settings = $submitted;
		} else {
			$errors[] = 'Nothing was saved. The ' . $theme . " theme's settings are kept in its generated stylesheet, and that file could not be written to " . formulize_getAppearanceDir($theme) . '. Make that folder writable by the web server and save again.';
		}
	}
	if (isset($_POST['appearance_editor_ajax'])) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array('saved' => $saved, 'errors' => $errors, 'state' => formulize_appearanceEditorState($settings, $theme)), JSON_UNESCAPED_SLASHES);
		exit();
	}
	if ($saved AND !$errors) {
		header('Location: ' . $pageUrl . '&saved=1');
		exit();
	}
}

$screens = $pageUrl ? formulize_getAppearancePreviewScreens($theme) : array();
$map = formulize_appearanceTokenMap();
$defaults = formulize_appearanceSizeDefaults($theme);
foreach ($map['tokens'] as $token => $entry) {
	$map['tokens'][$token]['default'] = isset($defaults[$token]) ? $defaults[$token] : null;
}
$state = formulize_appearanceEditorState($settings, $theme);

// The colours, with the CSS each one writes: the editor previews a colour by
// setting those properties on the preview, and puts back what the theme itself
// declares (themeTokens) when a colour is back at its default.
$colours = array();
foreach (formulize_appearanceColourMap($theme) as $key => $colour) {
	$colours[$key] = array(
		'label' => $colour['label'],
		'description' => $colour['description'],
		'default' => $colour['default'],
		'tokens' => $colour['tokens'],
	);
}
$fonts = array();
foreach (formulize_appearanceFontMap($theme) as $key => $font) {
	$fonts[] = array('key' => $key, 'label' => $font['label']);
}
$headingFonts = array();
foreach (formulize_appearanceHeadingFontMap($theme) as $key => $font) {
	$headingFonts[] = array('key' => $key, 'label' => $font['label']);
}
$themeTokens = formulize_appearanceThemeTokens($theme);
$restore = array();
foreach ($colours as $colour) {
	foreach (array_keys($colour['tokens']) as $token) {
		$restore[$token] = isset($themeTokens[$token]) ? $themeTokens[$token] : null;
	}
}
foreach (array_merge(array('--fz-font-sans', '--fz-font-heading'), array_map(function ($colour) { return $colour['css']; }, $map['colours'])) as $token) {
	$restore[$token] = isset($themeTokens[$token]) ? $themeTokens[$token] : null;
}
$uploads = array();
foreach (formulize_appearanceUploads() as $uploadSetting => $upload) {
	$uploads[$uploadSetting] = array(
		'accept' => implode(',', array_unique(array_keys($upload['types']))),
		'types' => $upload['typesLabel'],
	);
}

// The page width, for a theme that lays its pages out to one: the theme's own,
// which is what a reset goes back to, and the limits of the width box. The
// preview puts the theme's own declaration back (restore) when the width is the
// theme's own.
$contentWidth = null;
if (formulize_appearanceThemeUsesContentWidth($theme)) {
	$contentWidth = formulize_appearanceContentWidthLimits();
	$contentWidth['theme'] = formulize_appearanceContentWidthDefault($theme);
	$restore['--formulize-content-max-width'] = $themeTokens['--formulize-content-max-width'];
}

// the looks the site can be given, Default (recorded as '') first
$lookList = array();
foreach (formulize_getAppearanceLooks($theme) as $key => $look) {
	$lookList[$key == 'default' ? '' : $key] = array('name' => $look['name'], 'description' => $look['description']);
}

$editorData = array(
	'theme' => $theme,
	'map' => array(
		'types' => $map['types'],
		'screens' => $screens,
		'components' => $map['components'],
		'gaps' => $map['gaps'],
		'tokens' => $map['tokens'],
		'colours' => $map['colours'],
	),
	'looks' => $lookList,
	'contentWidth' => $contentWidth,
	'state' => $state,
	'colours' => $colours,
	'fonts' => array(
		'main' => $fonts,
		'heading' => $headingFonts,
		'stacks' => formulize_appearanceFontPreviewStacks($theme),
	),
	'restore' => $restore,
	'uploads' => $uploads,
	'themeLogoUrl' => XOOPS_URL . '/themes/' . rawurlencode($theme) . '/images/logo.png',
	'previewUrl' => XOOPS_URL . '/modules/formulize/appearance_preview.php?theme=' . urlencode($theme) . '&screen=',
);

$cssPath = '/modules/formulize/templates/css/appearance_editor.css';
$jsPath = '/modules/formulize/include/js/appearance_editor.js';
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Advanced editor - <?php echo htmlspecialchars($theme, ENT_QUOTES); ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo XOOPS_URL . formulize_uiStylesheetPath(); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo XOOPS_URL . $cssPath . '?v=' . formulize_get_file_version($cssPath); ?>">
</head>
<body class="formulize-editor">
<?php if (!$screens) { ?>
<main class="formulize-editor__unsupported">
	<h1>Advanced editor</h1>
	<p>The <?php echo htmlspecialchars($theme, ENT_QUOTES); ?> theme can't be edited in the advanced editor: it isn't built on Formulize UI's component settings<?php echo formulize_appearanceThemeUsesSizes($theme) ? ', or has no sample screens to preview them on' : ''; ?>. Its colours, fonts and logo can be changed on the Appearance page.</p>
	<p><a href="<?php echo htmlspecialchars($appearanceUrl, ENT_QUOTES); ?>">Back to Appearance</a></p>
</main>
<?php } else { ?>
<div class="formulize-editor__app">
	<header class="formulize-editor__top">
		<div class="formulize-editor__crumbs">
			<a href="<?php echo htmlspecialchars($appearanceUrl, ENT_QUOTES); ?>">Appearance</a><span aria-hidden="true">/</span><h1>Advanced editor</h1>
			<span class="formulize-editor__theme" title="The theme being edited"><?php echo htmlspecialchars($theme, ENT_QUOTES); ?></span>
		</div>
		<div class="formulize-editor__spacer"></div>
		<form class="formulize-editor__actions" method="post" enctype="multipart/form-data" action="<?php echo htmlspecialchars($pageUrl, ENT_QUOTES); ?>" id="formulize-editor-form">
			<?php echo $GLOBALS['xoopsSecurity']->getTokenHTML('formulize_appearance_editor_token'); ?>
			<input type="hidden" name="theme" value="<?php echo htmlspecialchars($theme, ENT_QUOTES); ?>">
			<?php // every setting is filled in from the editor when the form is sent; the
			// page width only for a theme that has one, so a save keeps it otherwise
			foreach (array_merge(array_map(function ($key) { return 'appearance_' . $key; }, array_keys($colours)),
				array('appearance_font', 'appearance_customfont', 'appearance_headingfont', 'appearance_headingcustomfont', 'appearance_look', 'appearance_overrides', 'appearance_logo_remove', 'appearance_favicon_remove'),
				$contentWidth ? array('appearance_contentwidth') : array()) as $name) { ?>
			<input type="hidden" name="<?php echo $name; ?>" data-setting="<?php echo $name; ?>" value="">
			<?php } ?>
			<?php // the uploads are chosen from the inspector, which labels these
			foreach ($uploads as $uploadSetting => $upload) { ?>
			<input type="file" class="formulize-editor__file" name="<?php echo $uploadSetting; ?>_file" id="formulize-editor-<?php echo $uploadSetting; ?>" accept="<?php echo htmlspecialchars($upload['accept'], ENT_QUOTES); ?>" tabindex="-1" aria-hidden="true">
			<?php } ?>
			<span class="formulize-editor__count" id="formulize-editor-count" hidden></span>
			<button type="button" class="formulize-editor__btn" id="formulize-editor-reset" disabled>Reset changes</button>
			<button type="submit" class="formulize-editor__btn formulize-editor__btn--primary" name="appearance_editor_save" value="1" id="formulize-editor-save">Save</button>
		</form>
	</header>
	<div id="formulize-editor-messages">
	<?php foreach ($errors as $error) { ?><p class="formulize-editor__message formulize-editor__message--error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></p><?php } ?>
	<?php if (isset($_GET['saved']) AND !$errors) { ?><p class="formulize-editor__message" role="status">Saved. These settings now apply across the site in the <?php echo htmlspecialchars($theme, ENT_QUOTES); ?> theme.</p><?php } ?>
	</div>
	<div class="formulize-editor__main">
		<section class="formulize-editor__stage" aria-label="Preview">
			<div class="formulize-editor__bar">
				<div class="formulize-editor__tabs" role="tablist" id="formulize-editor-screens"></div>
				<div class="formulize-editor__seg" role="group" aria-label="Preview width" id="formulize-editor-width">
					<button type="button" data-w="desktop" aria-pressed="true">Desktop</button>
					<button type="button" data-w="phone" aria-pressed="false">Phone</button>
				</div>
				<span class="formulize-editor__hint" id="formulize-editor-hint">Click anything in the preview to change it.</span>
			</div>
			<div class="formulize-editor__canvas" id="formulize-editor-canvas">
				<iframe class="formulize-editor__frame" id="formulize-editor-frame" title="Preview"></iframe>
			</div>
		</section>
		<aside class="formulize-editor__insp" aria-label="Settings" aria-live="polite">
			<div class="formulize-editor__insp-head" id="formulize-editor-insp-head"></div>
			<div class="formulize-editor__insp-body" id="formulize-editor-insp-body"></div>
		</aside>
	</div>
</div>
<script>var formulizeAppearanceEditor = <?php echo json_encode($editorData, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS); ?>;</script>
<script src="<?php echo XOOPS_URL . $jsPath . '?v=' . formulize_get_file_version($jsPath); ?>"></script>
<?php } ?>
</body>
</html>
