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

// The appearance editor: one of a theme's looks, on a live preview. It is opened
// on a look from the Looks tab (admin/looks.php), which is where looks are made,
// managed and used; here, a look is only changed. The page is the theme's sample
// screens (appearance_preview.php) and an inspector.
//
// The simple editor has the look's logo and favicon, colours, fonts, page width
// and size (Standard, Compact or Comfortable). The advanced editor has those, and
// every part of the interface: click a part of the preview, such as a button or a
// list row, and the inspector shows that part's settings, which apply to every one
// of it on the site. The two are one page; switching between them keeps what
// hasn't been saved.
//
// A look is measured against the look it is based on (or, with no base, the
// theme's own appearance): what differs is the look's own, and everything else
// follows its base. Saving keeps what the look changes itself (see
// formulize_saveAppearanceLookSettings). The parts and their settings are in
// include/appearance_tokens.json; the page's behaviour is
// include/js/appearance_editor.js.
//
// Formulize module admins only, and nothing is saved while the system is locked,
// like the rest of the admin pages.

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

// the same people as the rest of the Formulize admin pages: the module's admins
global $xoopsUser;
$gperm_handler = xoops_gethandler('groupperm');
if (!$xoopsUser OR !$gperm_handler->checkRight('module_admin', getFormulizeModId(), $xoopsUser->getGroups(), 1)) {
	redirect_header(XOOPS_URL, 3, _NOPERM);
	exit();
}
// with the system locked (the isSaveLocked preference), nothing is saved here either
$saveLocked = sendSaveLockPrefToTemplate();
$saveLockedError = _AM_APPEARANCE_EDITOR_ERR_LOCKED;

// The look being edited, of the theme asked for: the one asked for, or the site look
$theme = formulize_resolveAppearanceTheme(isset($_POST['theme']) ? (string) $_POST['theme'] : (isset($_GET['theme']) ? (string) $_GET['theme'] : ''));
$looks = formulize_getAppearanceLooks($theme);
$askedLook = strtolower((string) (isset($_POST['look']) ? $_POST['look'] : (isset($_GET['look']) ? $_GET['look'] : '')));
$editing = isset($looks[$askedLook]) ? $askedLook : formulize_getAppearanceSiteLook($theme);
$editorUrl = formulize_getAppearanceEditorUrl($theme);
$pageUrl = $editorUrl ? $editorUrl . '&look=' . urlencode($editing) : '';
$looksUrl = XOOPS_URL . '/modules/formulize/admin/ui.php?page=appearance&view=looks&theme=' . urlencode($theme);
// The simple editor, or the advanced one, for a theme that has it: the one asked for
$hasAdvanced = formulize_appearanceThemeHasAdvancedMode($theme);
$askedMode = isset($_POST['mode']) ? (string) $_POST['mode'] : (isset($_GET['mode']) ? (string) $_GET['mode'] : '');
$mode = ($hasAdvanced AND $askedMode == 'advanced') ? 'advanced' : 'simple';
$errors = array();

// A look's settings in the editor's terms: what it starts from, and what a save
// sends back to it.
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
		// 'full', or a maximum width in pixels: the one chosen, or the theme's own
		'contentWidth' => formulize_appearanceContentWidth($settings, $theme),
		// 'left', 'center' or 'right': where a page narrower than the window sits in it
		'contentAlign' => formulize_appearanceContentAlign($settings, $theme),
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

// What the look is measured against, in the editor's terms: the look it is based on,
// as it resolves; or for a look based on nothing, the theme's own appearance
function formulize_appearanceEditorReference($looks, $key, $theme) {
	return formulize_appearanceEditorState($looks[$key]['base'] !== ''
		? formulize_appearanceResolveLook($looks, $looks[$key]['base'], $theme)
		: formulize_sanitizeAppearanceSettings(formulize_defaultAppearanceSettings(), $theme), $theme);
}

// Saving: what the look changes itself, which the editor works out (everything that
// differs from its base), and its own logo and favicon, kept, replaced or taken away.
//
// The editor saves in the background (appearance_editor_ajax), and gets back what
// was saved, so it stays where it was; without scripts the form posts as usual and
// comes back here, so a reload doesn't post again. A background save sends
// X-Requested-With, which keeps the page's token good for the next save.
$saved = false;
if (isset($_POST['appearance_editor_save']) AND $pageUrl) {
	// a background save answers with JSON alone: anything else printed while saving
	// (a PHP notice, with errors shown) is dropped, so the editor can read the answer
	$bufferLevel = ob_get_level();
	if (isset($_POST['appearance_editor_ajax'])) {
		ob_start();
	}
	if ($saveLocked) {
		$errors[] = $saveLockedError;
	} elseif (!$GLOBALS['xoopsSecurity']->check(true, false, 'formulize_appearance_editor_token')) {
		$errors[] = _AM_APPEARANCE_EDITOR_ERR_TOKEN;
	} else {
		$own = json_decode(isset($_POST['appearance_look_settings']) ? (string) $_POST['appearance_look_settings'] : '', true);
		$own = is_array($own) ? $own : array();
		// the look's own logo and favicon: as they are, unless replaced or taken away
		$before = array();
		foreach (array_keys(formulize_appearanceUploads()) as $name) {
			unset($own[$name]);
			$before[$name] = isset($looks[$editing]['settings'][$name]) ? $looks[$editing]['settings'][$name] : '';
			if ($before[$name] !== '') {
				$own[$name] = $before[$name];
			}
		}
		$own = formulize_saveAppearanceUploads($own, $theme, $errors);
		$after = array();
		foreach (array_keys(formulize_appearanceUploads()) as $name) {
			$after[$name] = isset($own[$name]) ? $own[$name] : '';
			if ($after[$name] === '') {
				unset($own[$name]); // none of its own: its base's
			}
		}
		$problem = formulize_saveAppearanceLookSettings($theme, $editing, $own);
		if ($problem === '') {
			$saved = true;
		} else {
			$errors[] = $problem;
		}
		// the images the save stopped naming, and after a save that failed, the ones
		// uploaded with it: each goes if no look names it now
		foreach (array_merge(array_diff($before, $after), array_diff($after, $before)) as $file) {
			if ($file !== '') {
				formulize_deleteAppearanceUploadedFile($file, $theme);
			}
		}
		$looks = formulize_getAppearanceLooks($theme, true);
	}
	if (isset($_POST['appearance_editor_ajax'])) {
		$answer = json_encode(array('saved' => $saved, 'errors' => $errors,
			'state' => formulize_appearanceEditorState(formulize_appearanceResolveLook($looks, $editing, $theme), $theme)), JSON_UNESCAPED_SLASHES);
		while (ob_get_level() > $bufferLevel) {
			ob_end_clean();
		}
		header('Content-Type: application/json; charset=utf-8');
		echo $answer;
		exit();
	}
	if ($saved AND !$errors) {
		header('Location: ' . $pageUrl . '&mode=' . $mode . '&saved=1');
		exit();
	}
}

$screens = $pageUrl ? formulize_getAppearancePreviewScreens($theme) : array();
$notices = array();
if ($saveLocked) {
	$notices[] = _AM_APPEARANCE_EDITOR_LOCKED;
}
if (!formulize_appearanceDirIsWritable($theme)) {
	$notices[] = sprintf(_AM_APPEARANCE_EDITOR_NOT_WRITABLE, $theme, formulize_getAppearanceDir($theme));
}
if (!formulize_themeSupportsAppearance($theme)) {
	$notices[] = sprintf(_AM_APPEARANCE_EDITOR_UNSUPPORTED, $theme);
}
$map = formulize_appearanceTokenMap();
$defaults = formulize_appearanceSizeDefaults($theme);
foreach ($map['tokens'] as $token => $entry) {
	$map['tokens'][$token]['default'] = isset($defaults[$token]) ? $defaults[$token] : null;
}
$state = formulize_appearanceEditorState(formulize_appearanceResolveLook($looks, $editing, $theme), $theme);
$base = $looks[$editing]['base'];
$reach = formulize_appearanceLookReach($theme, $editing);
// the sizes, for the simple editor's Size, with the values each gives the size tokens
$sizes = array();
foreach (formulize_appearanceSizes($theme) as $sizeKey => $size) {
	$sizes[] = array('key' => $sizeKey, 'name' => $size['name'], 'description' => $size['description'], 'values' => (object) formulize_appearanceSizeValues($sizeKey, $theme));
}

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
	$fonts[] = array('key' => $key, 'label' => $font['label'], 'name' => $font['name']);
}
$headingFonts = array();
foreach (formulize_appearanceHeadingFontMap($theme) as $key => $font) {
	$headingFonts[] = array('key' => $key, 'label' => $font['label'], 'name' => $font['name']);
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
// the tokens that follow a part's setting (the main button's hover colour), as
// declared, for when the setting is back at its Default: the saved stylesheet in
// the preview may have them following the site look
$declaredTokens = formulize_appearanceDeclaredTokens($theme);
foreach (formulize_appearanceDerivedTokens($map) as $token) {
	$restore[$token] = isset($declaredTokens[$token]) ? $declaredTokens[$token] : null;
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
	// and where a page narrower than the window sits in it, for a theme that can place
	// it: the theme's own, and each alignment's value for --formulize-content-align
	if (formulize_appearanceThemeUsesContentAlign($theme)) {
		$contentWidth['align'] = formulize_appearanceContentAlignDefault($theme);
		$contentWidth['aligns'] = formulize_appearanceContentAligns();
		$restore['--formulize-content-align'] = $themeTokens['--formulize-content-align'];
	}
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
	// the look being edited, and the look it is based on
	'editing' => array('key' => $editing, 'name' => $looks[$editing]['name'], 'base' => $base, 'baseName' => $base !== '' ? $looks[$base]['name'] : ''),
	// what the look is measured against: its base as it resolves, or null for the theme's own
	'base' => $base !== '' ? formulize_appearanceEditorReference($looks, $editing, $theme) : null,
	'reference' => formulize_appearanceEditorReference($looks, $editing, $theme),
	// what a save changes: the site (on the site's theme, or when this one is), and screens
	'reach' => array('site' => $reach['site'], 'screens' => $reach['screens'], 'siteTheme' => $theme == formulize_getDefaultAppearanceTheme()),
	'sizes' => $sizes,
	'sizeTokens' => formulize_appearanceSizeTokens(),
	'mode' => $mode,
	'hasAdvanced' => $hasAdvanced,
	'pageUrl' => $pageUrl,
	'errors' => $errors,
	'saved' => (isset($_GET['saved']) AND !$errors),
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
	'previewUrl' => XOOPS_URL . '/modules/formulize/appearance_preview.php?theme=' . urlencode($theme) . '&look=' . urlencode($editing) . '&screen=',
	// the script's words: the language file's _AM_APPEARANCE_EDITOR_JS_ constants, by
	// the rest of their names
	'text' => formulize_scriptText('_AM_APPEARANCE_EDITOR_JS_'),
);

$cssPath = '/modules/formulize/templates/css/appearance_editor.css';
$jsPath = '/modules/formulize/include/js/appearance_editor.js';
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="<?php echo defined('_LANGCODE') ? _LANGCODE : 'en'; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?php echo htmlspecialchars(sprintf(_AM_APPEARANCE_EDITOR_TITLE, $theme), ENT_QUOTES); ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo XOOPS_URL . formulize_uiStylesheetPath(); ?>">
<link rel="stylesheet" type="text/css" href="<?php echo XOOPS_URL . $cssPath . '?v=' . formulize_get_file_version($cssPath); ?>">
</head>
<body class="formulize-editor<?php echo $mode == 'advanced' ? ' formulize-editor--advanced' : ''; ?>">
<?php if (!$screens) { ?>
<main class="formulize-editor__unsupported">
	<h1><?php echo htmlspecialchars(_AM_APPEARANCE_EDITOR_HEADING, ENT_QUOTES); ?></h1>
	<p><?php echo htmlspecialchars(sprintf(_AM_APPEARANCE_EDITOR_NO_SAMPLES, $theme), ENT_QUOTES); ?></p>
	<p><a href="<?php echo htmlspecialchars($looksUrl, ENT_QUOTES); ?>"><?php echo htmlspecialchars(_AM_APPEARANCE_EDITOR_BACK, ENT_QUOTES); ?></a></p>
</main>
<?php } else { ?>
<div class="formulize-editor__app">
	<header class="formulize-editor__top">
		<div class="formulize-editor__crumbs">
			<a class="formulize-editor__btn formulize-editor__back" href="<?php echo htmlspecialchars($looksUrl, ENT_QUOTES); ?>" id="formulize-editor-back"><svg width="14" height="14" viewBox="0 0 16 16" aria-hidden="true"><path d="M10 3 5 8l5 5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg><?php echo htmlspecialchars(_AM_APPEARANCE_EDITOR_BACK, ENT_QUOTES); ?></a>
			<div class="formulize-editor__editing">
				<h1><?php echo htmlspecialchars($looks[$editing]['name'], ENT_QUOTES); ?></h1>
				<span><?php echo htmlspecialchars($base !== '' ? sprintf(_AM_APPEARANCE_EDITOR_BASED_ON, $theme, $looks[$base]['name']) : sprintf(_AM_APPEARANCE_EDITOR_BASED_ON_NOTHING, $theme), ENT_QUOTES); ?></span>
			</div>
		</div>
		<?php if ($hasAdvanced) { // the simple editor or the advanced one, switched in place ?>
		<div class="formulize-editor__seg formulize-editor__mode" role="group" aria-label="<?php echo htmlspecialchars(_AM_APPEARANCE_EDITOR_MODE, ENT_QUOTES); ?>" id="formulize-editor-mode">
			<button type="button" data-mode="simple" aria-pressed="<?php echo $mode == 'simple' ? 'true' : 'false'; ?>"><?php echo htmlspecialchars(_AM_APPEARANCE_EDITOR_SIMPLE, ENT_QUOTES); ?></button>
			<button type="button" data-mode="advanced" aria-pressed="<?php echo $mode == 'advanced' ? 'true' : 'false'; ?>"><?php echo htmlspecialchars(_AM_APPEARANCE_EDITOR_ADVANCED, ENT_QUOTES); ?></button>
		</div>
		<?php } ?>
		<div class="formulize-editor__spacer"></div>
		<form class="formulize-editor__actions" method="post" enctype="multipart/form-data" action="<?php echo htmlspecialchars($pageUrl, ENT_QUOTES); ?>" id="formulize-editor-form">
			<?php echo $GLOBALS['xoopsSecurity']->getTokenHTML('formulize_appearance_editor_token'); ?>
			<input type="hidden" name="theme" value="<?php echo htmlspecialchars($theme, ENT_QUOTES); ?>">
			<input type="hidden" name="look" value="<?php echo htmlspecialchars($editing, ENT_QUOTES); ?>">
			<input type="hidden" name="mode" id="formulize-editor-mode-field" value="<?php echo $mode; ?>">
			<?php // what the look changes itself, and its images taken away: filled in from the
			// editor when the form is sent ?>
			<input type="hidden" name="appearance_look_settings" data-setting="appearance_look_settings" value="">
			<?php foreach ($uploads as $uploadSetting => $upload) { ?>
			<input type="hidden" name="<?php echo $uploadSetting; ?>_remove" data-setting="<?php echo $uploadSetting; ?>_remove" value="">
			<?php } ?>
			<?php // the uploads are chosen from the inspector, which labels these
			foreach ($uploads as $uploadSetting => $upload) { ?>
			<input type="file" class="formulize-editor__file" name="<?php echo $uploadSetting; ?>_file" id="formulize-editor-<?php echo $uploadSetting; ?>" accept="<?php echo htmlspecialchars($upload['accept'], ENT_QUOTES); ?>" tabindex="-1" aria-hidden="true">
			<?php } ?>
			<?php // when a save goes live: filled in by the editor's script ?>
			<span class="formulize-editor__live" id="formulize-editor-live" hidden></span>
			<?php // what the look changes from its base, and what hasn't been saved: each a panel,
			// filled in by the editor's script ?>
			<button type="button" class="formulize-editor__btn formulize-editor__changes" id="formulize-editor-diff" popovertarget="formulize-editor-diff-panel" style="anchor-name: --formulize-editor-diff"><?php echo htmlspecialchars(_AM_APPEARANCE_EDITOR_JS_SAVED_CHANGES, ENT_QUOTES); ?><svg class="formulize-editor__chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>
			<button type="button" class="formulize-editor__btn formulize-editor__changes" id="formulize-editor-unsaved" popovertarget="formulize-editor-unsaved-panel" style="anchor-name: --formulize-editor-unsaved" disabled><?php echo htmlspecialchars(_AM_APPEARANCE_EDITOR_JS_UNSAVED, ENT_QUOTES); ?><span class="formulize-editor__count" id="formulize-editor-count" hidden></span><svg class="formulize-editor__chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>
			<button type="submit" class="formulize-editor__btn formulize-editor__btn--primary" name="appearance_editor_save" value="1" id="formulize-editor-save"><?php echo htmlspecialchars(_AM_APPEARANCE_EDITOR_JS_SAVE, ENT_QUOTES); ?></button>
		</form>
		<div class="fz-popover fz-popover--panel formulize-editor__changes-panel" id="formulize-editor-diff-panel" popover tabindex="-1" role="dialog" aria-labelledby="formulize-editor-diff" style="position-anchor: --formulize-editor-diff">
			<div class="fz-popover__body" id="formulize-editor-diff-body"></div>
		</div>
		<div class="fz-popover fz-popover--panel formulize-editor__changes-panel" id="formulize-editor-unsaved-panel" popover tabindex="-1" role="dialog" aria-labelledby="formulize-editor-unsaved" style="position-anchor: --formulize-editor-unsaved">
			<div class="fz-popover__body" id="formulize-editor-unsaved-body"></div>
			<div class="fz-popover__foot" id="formulize-editor-unsaved-foot"></div>
		</div>
	</header>
	<?php // what stands in the way, for as long as it does
	foreach ($notices as $notice) { ?><p class="formulize-editor__message formulize-editor__message--note"><?php echo htmlspecialchars($notice, ENT_QUOTES); ?></p><?php } ?>
	<div class="formulize-editor__main">
		<section class="formulize-editor__stage" aria-label="<?php echo htmlspecialchars(_AM_APPEARANCE_EDITOR_PREVIEW, ENT_QUOTES); ?>">
			<?php // what an action or a save did: a note over the bottom of the preview, which
			// moves nothing on the page (filled in by the editor's script) ?>
			<div class="formulize-editor__toasts" id="formulize-editor-toasts"></div>
			<div class="formulize-editor__bar">
				<div class="formulize-editor__tabs" role="tablist" id="formulize-editor-screens"></div>
				<div class="formulize-editor__seg" role="group" aria-label="<?php echo htmlspecialchars(_AM_APPEARANCE_EDITOR_PREVIEW_WIDTH, ENT_QUOTES); ?>" id="formulize-editor-width">
					<button type="button" data-w="desktop" aria-pressed="true"><?php echo htmlspecialchars(_AM_APPEARANCE_EDITOR_JS_DESKTOP, ENT_QUOTES); ?></button>
					<button type="button" data-w="phone" aria-pressed="false"><?php echo htmlspecialchars(_AM_APPEARANCE_EDITOR_PHONE, ENT_QUOTES); ?></button>
				</div>
				<span class="formulize-editor__hint" id="formulize-editor-hint"><?php echo htmlspecialchars($mode == 'simple' ? _AM_APPEARANCE_EDITOR_JS_HINT_SIMPLE : _AM_APPEARANCE_EDITOR_JS_HINT_ADVANCED, ENT_QUOTES); ?></span>
			</div>
			<div class="formulize-editor__canvas" id="formulize-editor-canvas">
				<iframe class="formulize-editor__frame" id="formulize-editor-frame" title="<?php echo htmlspecialchars(_AM_APPEARANCE_EDITOR_PREVIEW, ENT_QUOTES); ?>"></iframe>
			</div>
		</section>
		<aside class="formulize-editor__insp" aria-label="<?php echo htmlspecialchars(_AM_APPEARANCE_EDITOR_SETTINGS, ENT_QUOTES); ?>" aria-live="polite">
			<div class="formulize-editor__insp-head" id="formulize-editor-insp-head"></div>
			<div class="formulize-editor__insp-body" id="formulize-editor-insp-body"></div>
		</aside>
	</div>
</div>
<script>var formulizeAppearanceEditor = <?php echo json_encode($editorData, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS); ?>;</script>
<script src="<?php echo XOOPS_URL . formulize_textScriptPath(); ?>"></script>
<script src="<?php echo XOOPS_URL . formulize_uiScriptPath(); ?>"></script>
<script src="<?php echo XOOPS_URL . $jsPath . '?v=' . formulize_get_file_version($jsPath); ?>"></script>
<?php } ?>
</body>
</html>
