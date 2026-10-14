<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) Formulize Project                        ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
###############################################################################
##  Project: Formulize                                                       ##
###############################################################################

// The Looks tab of Admin > Appearance: a theme's looks, as a tree of the looks
// each is based on, and everything done to them except changing how one looks,
// which is the appearance editor's job. Which look the site uses; making, copying,
// renaming, rebasing and deleting looks; which screens use each; and a way into the
// editor for each. The looks themselves are in include/appearance.php.
//
// Two parts. Included by admin/configsubject.php, as the tab's controller, it
// renders the tab into $adminPage['looks_html'] (template admin/looks.html). Posted
// to directly by the tab's forms, it does what was asked and goes back to the tab:
// the admin page has sent its header by the time a tab's controller runs, so the
// tab can't redirect after a post, which is what keeps a reload from posting again.
//
// Formulize module admins only, and nothing is changed while the system is locked,
// like the rest of the admin pages. The popovers, menus and dialogs are Formulize
// UI's, which the tab loads, since the admin pages don't.

// ---- A form, posted here ------------------------------------------------------
if (isset($_POST['looks_action'])) {
	include_once "../../../mainfile.php";
	include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
	include_once XOOPS_ROOT_PATH . '/modules/formulize/include/appearance.php';

	global $xoopsUser;
	if (!$xoopsUser OR !$xoopsUser->isAdmin(getFormulizeModId())) {
		redirect_header(XOOPS_URL, 3, _NOPERM);
		exit();
	}
	$themes = formulize_looksThemes();
	$theme = isset($_POST['theme']) ? (string) $_POST['theme'] : '';
	$back = formulize_looksTabUrl($theme);
	$action = (string) $_POST['looks_action'];
	$key = isset($_POST['look']) ? strtolower((string) $_POST['look']) : '';
	$name = isset($_POST['name']) ? (string) $_POST['name'] : '';
	$base = isset($_POST['base']) ? strtolower((string) $_POST['base']) : '';
	$problem = '';
	if (!isset($themes[$theme])) {
		$problem = _AM_APPEARANCE_LOOKS_ERR_THEME;
	} elseif (sendSaveLockPrefToTemplate()) {
		$problem = _AM_APPEARANCE_LOOKS_ERR_LOCKED;
	} elseif (!$GLOBALS['xoopsSecurity']->check(true, false, 'formulize_looks_token')) {
		$problem = _AM_APPEARANCE_LOOKS_ERR_TOKEN;
	} elseif ($action == 'create') {
		$problem = formulize_createAppearanceLook($theme, $name, $base, isset($_POST['size']) ? (string) $_POST['size'] : '', $key);
	} elseif ($action == 'duplicate') {
		$problem = formulize_duplicateAppearanceLook($theme, $key, $name, $key);
	} elseif ($action == 'rename') {
		$problem = formulize_renameAppearanceLook($theme, $key, $name);
	} elseif ($action == 'rebase') {
		$problem = formulize_rebaseAppearanceLook($theme, $key, $base);
	} elseif ($action == 'site') {
		$problem = formulize_setAppearanceSiteLook($theme, $key);
	} elseif ($action == 'delete') {
		$looks = formulize_getAppearanceLooks($theme);
		$deletedName = isset($looks[$key]) ? $looks[$key]['name'] : '';
		$problem = formulize_deleteAppearanceLook($theme, $key);
		$key = $deletedName; // the look is gone, so the message names it
	} else {
		$problem = _AM_APPEARANCE_LOOKS_ERR_ACTION;
	}
	header('Location: ' . $back . ($problem === '' ? '&done=' . urlencode($action) . '&look=' . urlencode($key) : '&problem=' . urlencode($problem)));
	exit();
}

/**
 * The themes whose looks can be managed here: those the appearance editor can edit.
 *
 * @return array theme folder name => theme name
 */
function formulize_looksThemes() {
	$themes = array();
	foreach (formulize_getAppearanceThemes() as $themeDir => $themeName) {
		if (formulize_getAppearanceEditorUrl($themeDir)) {
			$themes[$themeDir] = $themeName;
		}
	}
	return $themes;
}

/**
 * The Looks tab, for a theme.
 *
 * @param string $theme theme folder name, or '' for the site's
 * @return string the URL
 */
function formulize_looksTabUrl($theme = '') {
	return XOOPS_URL . '/modules/formulize/admin/ui.php?page=appearance&view=looks' . ($theme !== '' ? '&theme=' . urlencode($theme) : '');
}

function formulize_looksH($text) {
	return htmlspecialchars((string) $text, ENT_QUOTES);
}

// Words from the language file, as HTML, with their blanks (%s, or %1$s in a
// translation that orders them differently) filled in with what follows, which is
// HTML already: formulize_looksT(_AM_APPEARANCE_LOOKS_DELETE_TITLE, $name)
function formulize_looksT($text) {
	$args = func_get_args();
	$args[0] = formulize_looksH($text);
	return call_user_func_array('sprintf', $args);
}

// The icon for each kind of change a look makes, in the Based on popover: 16px, in
// the text's colour
function formulize_looksIcon($kind) {
	$paths = array(
		'logo' => '<rect x="2" y="3" width="12" height="10" rx="1.5"/><circle cx="6" cy="6.5" r="1"/><path d="M14 11l-3.5-3.5L4 13"/>',
		'favicon' => '<rect x="2" y="3" width="12" height="10" rx="1.5"/><path d="M2 6.5h12"/><path d="M4.5 4.75h.01M6.5 4.75h.01"/>',
		'colours' => '<path d="M8 2.5s4 4.2 4 7a4 4 0 0 1-8 0c0-2.8 4-7 4-7z"/>',
		'fonts' => '<path d="M3 4.5V3h10v1.5M8 3v10M6 13h4"/>',
		'width' => '<path d="M2 8h12M4.5 5.5 2 8l2.5 2.5M11.5 5.5 14 8l-2.5 2.5"/>',
		'sizes' => '<rect x="2" y="2" width="12" height="12" rx="1.5"/><path d="M5.5 10.5l5-5M7 5.5h3.5V9"/>',
		'parts' => '<path d="M2.5 5h5M10.5 5h3M2.5 11h2M7.5 11h6"/><circle cx="9" cy="5" r="1.5"/><circle cx="6" cy="11" r="1.5"/>',
		'branding' => '<path d="M8 2a6 6 0 0 0 0 12c.9 0 1.4-.6 1.4-1.3 0-.4-.2-.7-.4-1-.2-.2-.4-.5-.4-.9 0-.7.6-1.3 1.3-1.3h1.4A2.7 2.7 0 0 0 14 6.8C14 4.1 11.3 2 8 2z"/><circle cx="5" cy="7" r=".6"/><circle cx="7.5" cy="4.8" r=".6"/><circle cx="10.5" cy="5.5" r=".6"/>',
		'layout' => '<rect x="2" y="2" width="12" height="12" rx="1.5"/><path d="M5.5 10.5l5-5M7 5.5h3.5V9"/>',
		// where a look is used
		'site' => '<circle cx="8" cy="8" r="6"/><path d="M2 8h12M8 2c1.8 1.7 2.6 3.7 2.6 6S9.8 12.3 8 14M8 2C6.2 3.7 5.4 5.7 5.4 8s.8 4.3 2.6 6"/>',
		'screen' => '<rect x="2" y="2.5" width="12" height="11" rx="1.5"/><path d="M2 5.5h12M4.5 8h7M4.5 10.5h4"/>',
		// what can be done to a look
		'simple' => '<path d="M10.5 2.5l3 3L6 13H3v-3z"/>',
		'advanced' => '<path d="M3 2.5l9.5 4-4 1.5-1.5 4z"/>',
		'add' => '<path d="M8 3v10M3 8h10"/>',
		'duplicate' => '<rect x="5" y="5" width="8.5" height="8.5" rx="1.5"/><path d="M11 5V3.5A1 1 0 0 0 10 2.5H3.5a1 1 0 0 0-1 1V10a1 1 0 0 0 1 1H5"/>',
		'rename' => '<path d="M2.5 8.6V3.5a1 1 0 0 1 1-1h5.1l5 5a1 1 0 0 1 0 1.4l-4.1 4.1a1 1 0 0 1-1.4 0z"/><circle cx="5.5" cy="5.5" r=".6"/>',
		'rebase' => '<circle cx="4.5" cy="3.5" r="1.5"/><circle cx="4.5" cy="12.5" r="1.5"/><circle cx="11.5" cy="5.5" r="1.5"/><path d="M4.5 5v6M11.5 7c0 2.5-2 3.5-7 4"/>',
		'delete' => '<path d="M2.5 4.5h11M6 4.5V3a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v1.5M4 4.5l.7 8.6a1 1 0 0 0 1 .9h4.6a1 1 0 0 0 1-.9l.7-8.6"/>',
	);
	return '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . (isset($paths[$kind]) ? $paths[$kind] : '') . '</svg>';
}

// What a look changes, in three kinds a person thinks in, for its row: its branding
// (logo, favicon, colours, fonts), its layout (named by what it is: a size, "Custom
// size", "Page width", or "Layout" for both), and its parts, with how many. A short,
// fixed set of words, so it always fits.
function formulize_looksCategories($summary) {
	$kinds = array();
	foreach ($summary as $item) {
		$kinds[$item['kind']] = $item;
	}
	$categories = array();
	if (array_intersect_key($kinds, array_flip(array('logo', 'favicon', 'colours', 'fonts')))) {
		$categories[] = array('icon' => 'branding', 'text' => _AM_APPEARANCE_LOOKS_BRANDING);
	}
	if (isset($kinds['sizes']) OR isset($kinds['width'])) {
		$sizeText = isset($kinds['sizes']) ? ($kinds['sizes']['size'] == 'custom' ? _AM_APPEARANCE_LOOKS_CUSTOM_SIZE : $kinds['sizes']['detail']) : '';
		$categories[] = array('icon' => 'layout', 'text' => (isset($kinds['sizes']) AND isset($kinds['width'])) ? _AM_APPEARANCE_LOOKS_LAYOUT : (isset($kinds['width']) ? _AM_APPEARANCE_LOOKS_PAGE_WIDTH : $sizeText));
	}
	if (isset($kinds['parts'])) {
		$categories[] = array('icon' => 'parts', 'text' => sprintf($kinds['parts']['count'] == 1 ? _AM_APPEARANCE_LOOKS_PART : _AM_APPEARANCE_LOOKS_PARTS, $kinds['parts']['count']));
	}
	return $categories;
}

// An icon for a menu item (Formulize UI's fz-menu__icon)
function formulize_looksMenuIcon($kind) {
	return '<span class="fz-menu__icon" aria-hidden="true">' . formulize_looksIcon($kind) . '</span>';
}

// A screen's Appearance tab, in the admin pages
function formulize_looksScreenUrl($screen) {
	return XOOPS_URL . '/modules/formulize/admin/ui.php?page=screen&fid=' . $screen['fid'] . '&sid=' . $screen['sid'] . '&tab=appearance';
}

// The looks as options of a select, in the order of the tree, indented, with
// Nothing first; the looks in $exclude can't be chosen
function formulize_looksBaseOptions($tree, $looks, $theme, $selected, $exclude = array()) {
	$html = '<option value=""' . ($selected === '' ? ' selected' : '') . '>' . formulize_looksT(_AM_APPEARANCE_LOOKS_BASE_NOTHING, formulize_looksH($theme)) . '</option>';
	foreach ($tree as $row) {
		$off = in_array($row['key'], $exclude, true);
		$html .= '<option value="' . formulize_looksH($row['key']) . '"' . ($row['key'] === $selected ? ' selected' : '') . ($off ? ' disabled' : '') . '>'
			. str_repeat('&emsp;', $row['depth']) . formulize_looksH($looks[$row['key']]['name'])
			. ($off ? ' ' . formulize_looksH($row['key'] === $exclude[0] ? _AM_APPEARANCE_LOOKS_BASE_THIS : _AM_APPEARANCE_LOOKS_BASE_DESCENDANT) : '') . '</option>';
	}
	return $html;
}

// The hidden fields every form here has
function formulize_looksFormFields($action, $theme, $key = '') {
	return '<input type="hidden" name="looks_action" value="' . formulize_looksH($action) . '">'
		. '<input type="hidden" name="theme" value="' . formulize_looksH($theme) . '">'
		. ($key !== '' ? '<input type="hidden" name="look" value="' . formulize_looksH($key) . '">' : '')
		. $GLOBALS['xoopsSecurity']->getTokenHTML('formulize_looks_token');
}

// ---- The tab ------------------------------------------------------------------
global $xoopsUser, $xoopsDB;
if (!$xoopsUser OR !$xoopsUser->isAdmin(getFormulizeModId())) {
	return; // configsubject.php renders nothing for a controller that returns nothing
}
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/appearance.php';

$saveLocked = sendSaveLockPrefToTemplate();
$siteTheme = formulize_getDefaultAppearanceTheme();
$themes = formulize_looksThemes();
$askedTheme = isset($_GET['theme']) ? (string) $_GET['theme'] : '';
$theme = isset($themes[$askedTheme]) ? $askedTheme : (isset($themes[$siteTheme]) ? $siteTheme : (string) key($themes));
$isSiteTheme = ($theme === $siteTheme);
$postUrl = XOOPS_URL . '/modules/formulize/admin/looks.php';

$looks = $theme !== '' ? formulize_getAppearanceLooks($theme) : array();
$siteLook = $theme !== '' ? formulize_getAppearanceSiteLook($theme) : '';
$tree = formulize_appearanceLookTree($looks);
$sizes = formulize_appearanceSizes($theme);
$hasAdvanced = formulize_appearanceThemeHasAdvancedMode($theme);
$editorUrl = formulize_getAppearanceEditorUrl($theme);
$looksCount = count($looks);

// the screens set to each look, and how many use the site look
$lookScreens = formulize_appearanceLookScreens();
$onSiteLook = 0;
$result = $xoopsDB->query('SELECT COUNT(*) FROM ' . $xoopsDB->prefix('formulize_screen') . " WHERE look = ''");
if ($result) {
	list($onSiteLook) = $xoopsDB->fetchRow($result);
}

// what was just done, or why it couldn't be, said once the tab is back
$message = '';
$problem = isset($_GET['problem']) ? (string) $_GET['problem'] : '';
if (isset($_GET['done']) AND $problem === '') {
	$doneKey = isset($_GET['look']) ? (string) $_GET['look'] : '';
	$doneName = isset($looks[$doneKey]) ? $looks[$doneKey]['name'] : $doneKey;
	$messages = array(
		'create' => _AM_APPEARANCE_LOOKS_DONE_CREATE,
		'duplicate' => _AM_APPEARANCE_LOOKS_DONE_DUPLICATE,
		'rename' => _AM_APPEARANCE_LOOKS_DONE_RENAME,
		'rebase' => _AM_APPEARANCE_LOOKS_DONE_REBASE,
		'site' => $isSiteTheme ? _AM_APPEARANCE_LOOKS_DONE_SITE : _AM_APPEARANCE_LOOKS_DONE_SITE_THEME,
		'delete' => _AM_APPEARANCE_LOOKS_DONE_DELETE,
	);
	if (isset($messages[$_GET['done']])) {
		$message = sprintf($messages[$_GET['done']], $doneName, $theme);
	}
}

$notices = array();
if ($saveLocked) {
	$notices[] = _AM_APPEARANCE_LOOKS_LOCKED;
}
if ($theme !== '' AND !formulize_appearanceDirIsWritable($theme)) {
	$notices[] = sprintf(_AM_APPEARANCE_LOOKS_NOT_WRITABLE, $theme, formulize_getAppearanceDir($theme));
}
if (!$themes) {
	$notices[] = _AM_APPEARANCE_LOOKS_NO_THEMES;
}

ob_start();
?>
<link rel="stylesheet" type="text/css" media="all" href="<?php echo formulize_looksH(XOOPS_URL . formulize_uiStylesheetPath()); ?>">
<script type="text/javascript" src="<?php echo formulize_looksH(XOOPS_URL . formulize_uiScriptPath()); ?>"></script>
<style>
/* The tab's own layout. The classes are formulize-, since they are Formulize's
   own; everything else is Formulize UI. */
.formulize-looks { font-family: var(--fz-font-sans); font-size: var(--fz-text-sm); line-height: var(--fz-leading-normal); color: var(--fz-color-text); }
.formulize-looks__context { display: flex; flex-wrap: wrap; align-items: center; gap: calc(var(--fz-spacing) * 2) calc(var(--fz-spacing) * 5); }
.formulize-looks__context-label { font-size: var(--fz-text-xs-plus); font-weight: var(--fz-font-weight-medium); }
.formulize-looks__context .fz-select { width: auto; min-width: 11rem; text-align: start; }
.formulize-looks__site td { background: var(--fz-color-accent-soft); }
.formulize-looks__site:hover td { background: var(--fz-color-accent-soft-2); }
/* the Site look badge would be the same tint as its row: on the row, it's a white pill */
.formulize-looks__site .fz-badge--accent { --fz-tone-soft: var(--fz-color-surface); }
.formulize-looks__name { display: inline-flex; align-items: center; gap: calc(var(--fz-spacing) * 2); vertical-align: middle; }
.formulize-looks__name strong { font-weight: var(--fz-font-weight-semibold); }
/* a value that opens a popover with more about it: a ghost button, its text lined up with the column's */
.formulize-looks__more { gap: calc(var(--fz-spacing) * 1.5); max-width: 100%; margin-inline-start: calc(var(--fz-spacing) * -3); font-weight: var(--fz-font-weight-normal); vertical-align: middle; }
.formulize-looks__more > span:first-child { text-decoration: underline dotted var(--fz-color-text-subtle); text-underline-offset: 3px; }
.formulize-looks__more small { color: var(--fz-color-text-muted); font-size: var(--fz-text-xs); }
.formulize-looks__more--quiet > span:first-child { color: var(--fz-color-text-muted); }
.formulize-looks__col-base { width: 10rem; }
.formulize-looks__col-changes { width: 19rem; min-width: 19rem; }
/* what a look changes, in a few words, each with a small icon */
.formulize-looks__changes-btn { gap: calc(var(--fz-spacing) * 2); margin-inline-start: calc(var(--fz-spacing) * -3); font-weight: var(--fz-font-weight-normal); white-space: nowrap; }
.formulize-looks__category { display: inline-flex; align-items: center; gap: calc(var(--fz-spacing) * 1.25); }
.formulize-looks__category svg { flex: none; width: calc(var(--fz-spacing) * 3.5); height: calc(var(--fz-spacing) * 3.5); color: var(--fz-color-text-muted); }
.formulize-looks__dot { color: var(--fz-color-text-subtle); }
.formulize-looks__col-used { width: 13rem; }
.formulize-looks__col-actions { width: 8.5rem; }
.formulize-looks__actions { display: inline-flex; justify-content: flex-end; gap: calc(var(--fz-spacing) * 1.5); width: 100%; }
/* the Based on popover: the looks it is based on, and what it changes, a row for each kind */
.formulize-looks__about { min-width: 17rem; }
.formulize-looks__chain { display: flex; flex-wrap: wrap; align-items: center; gap: calc(var(--fz-spacing) * 1.5); font-weight: var(--fz-font-weight-medium); }
.formulize-looks__chain span[aria-hidden] { color: var(--fz-color-text-subtle); font-weight: var(--fz-font-weight-normal); }
.formulize-looks__changes { grid-template-columns: max-content minmax(0, 1fr); align-items: center; gap: calc(var(--fz-spacing) * 2.5) calc(var(--fz-spacing) * 5); font-size: var(--fz-text-xs-plus); }
.formulize-looks__changes dt { display: flex; align-items: center; gap: calc(var(--fz-spacing) * 2.5); color: var(--fz-color-text); white-space: nowrap; }
.formulize-looks__changes dd { color: var(--fz-color-text-muted); }
.formulize-looks__icon { display: grid; place-items: center; flex: none; width: calc(var(--fz-spacing) * 6.5); height: calc(var(--fz-spacing) * 6.5); border-radius: var(--fz-radius-md); background: var(--fz-color-surface-3); color: var(--fz-color-text-muted); }
.formulize-looks__thumb { display: block; max-width: 6rem; max-height: calc(var(--fz-spacing) * 6.5); border-radius: var(--fz-radius-sm); }
.formulize-looks__swatches { display: flex; flex-wrap: wrap; gap: calc(var(--fz-spacing) * 1.5); }
.formulize-looks__swatch { width: calc(var(--fz-spacing) * 4.5); height: calc(var(--fz-spacing) * 4.5); border-radius: var(--fz-radius-full); box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.12); }
/* a screen's name in a popover, in the text's colour (the admin pages' links are blue) */
.formulize-looks a.formulize-looks__link { color: var(--fz-color-text); text-decoration: none; }
.formulize-looks a.formulize-looks__link:hover { text-decoration: underline; }
.formulize-looks__foot { padding-top: calc(var(--fz-spacing) * 2.5); border-top: 1px solid var(--fz-color-border); color: var(--fz-color-text-subtle); font-size: var(--fz-text-xs); }
.formulize-looks__choices { max-height: 18rem; overflow: auto; padding: calc(var(--fz-spacing) * 2); border: 1px solid var(--fz-color-border); border-radius: var(--fz-radius-md); }
.formulize-looks__choices .fz-choice { padding-inline-start: calc(var(--d, 0) * var(--fz-spacing) * 5); }
</style>

<div class="formulize-looks formulize-admin-ui fz-stack fz-gap-5">

	<div class="fz-stack fz-gap-3">
		<div class="fz-toolbar">
			<div class="fz-toolbar__start formulize-looks__context">
				<form method="get" action="<?php echo formulize_looksH(XOOPS_URL . '/modules/formulize/admin/ui.php'); ?>" class="fz-cluster fz-gap-2">
					<input type="hidden" name="page" value="appearance">
					<input type="hidden" name="view" value="looks">
					<label for="looks-theme" class="formulize-looks__context-label"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_THEME); ?></label>
					<select id="looks-theme" name="theme" class="fz-select" onchange="this.form.submit()">
						<?php foreach ($themes as $themeDir => $themeName) { ?>
						<option value="<?php echo formulize_looksH($themeDir); ?>"<?php echo $themeDir === $theme ? ' selected' : ''; ?>><?php echo formulize_looksH($themeDir === $siteTheme ? sprintf(_AM_APPEARANCE_LOOKS_THEME_ACTIVE, $themeName) : $themeName); ?></option>
						<?php } ?>
					</select>
					<noscript><button type="submit" class="fz-btn"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_SHOW); ?></button></noscript>
				</form>
				<?php if ($looksCount > 1) { ?>
				<div class="fz-cluster fz-gap-2">
					<span class="formulize-looks__context-label"><?php echo $isSiteTheme ? formulize_looksH(_AM_APPEARANCE_LOOKS_SITE_LOOK_IS) : formulize_looksT(_AM_APPEARANCE_LOOKS_SITE_LOOK_IS_THEME, formulize_looksH($theme)); ?></span>
					<strong class="fz-font-semibold"><?php echo formulize_looksH($looks[$siteLook]['name']); ?></strong>
					<button type="button" class="fz-btn fz-btn--sm" id="looks-site-button" commandfor="looks-site" command="show-modal"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_CHANGE_SITE); ?></button>
				</div>
				<?php } ?>
			</div>
			<div class="fz-toolbar__end"><button type="button" class="fz-btn fz-btn--primary" commandfor="looks-create" command="show-modal" data-looks-base="<?php echo isset($looks['default']) ? 'default' : ''; ?>"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_CREATE); ?></button></div>
		</div>
		<p class="fz-text-muted" style="max-width: 46rem"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_INTRO); ?></p>
	</div>

	<?php if (!$isSiteTheme AND $theme !== '') { ?>
	<div class="fz-callout fz-callout--info">
		<p class="fz-callout__title"><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_OTHER_THEME_TITLE, formulize_looksH($theme)); ?></p>
		<p><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_OTHER_THEME, formulize_looksH($siteTheme), '<a href="' . formulize_looksH(XOOPS_URL . '/modules/formulize/admin/ui.php?page=appearance&view=settings') . '">' . formulize_looksH(_AM_APPEARANCE_LOOKS_SETTINGS_LINK) . '</a>'); ?></p>
	</div>
	<?php } ?>
	<?php foreach ($notices as $notice) { ?>
	<div class="fz-callout fz-callout--warning"><p><?php echo formulize_looksH($notice); ?></p></div>
	<?php } ?>
	<?php if ($problem !== '') { ?>
	<div class="fz-callout fz-callout--danger" role="alert"><p><?php echo formulize_looksH($problem); ?></p></div>
	<?php } ?>
	<?php if ($message !== '') { ?>
	<div class="fz-callout fz-callout--success" role="status"><p><?php echo formulize_looksH($message); ?></p></div>
	<?php } ?>

	<?php if ($looks) { ?>
	<div class="fz-card fz-card--flush fz-overflow-x-auto">
		<table class="fz-table fz-tree" aria-label="<?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_TABLE, formulize_looksH($theme)); ?>">
			<thead><tr><th scope="col"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_COL_LOOK); ?></th><th scope="col" class="formulize-looks__col-base"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_COL_BASE); ?></th><th scope="col" class="formulize-looks__col-changes"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_COL_CHANGES); ?></th><th scope="col" class="formulize-looks__col-used"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_COL_USED); ?></th><th scope="col" class="formulize-looks__col-actions"><span class="fz-sr-only"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_COL_ACTIONS); ?></span></th></tr></thead>
			<tbody>
			<?php foreach ($tree as $row) {
				$key = $row['key'];
				$look = $looks[$key];
				$isSite = ($key === $siteLook);
				$id = 'looks-' . $key;
				$chain = formulize_appearanceLookChain($looks, $key);
				array_pop($chain); // the look itself
				$summary = formulize_appearanceLookSummary($looks, $key, $theme);
				$from = $look['base'] !== '' ? $looks[$look['base']]['name'] : sprintf(_AM_APPEARANCE_LOOKS_THEME_SETTINGS, $theme);
				$screens = isset($lookScreens[$key]) ? $lookScreens[$key] : array();
				$through = array();
				foreach (formulize_appearanceLookDescendants($looks, $key) as $descendant) {
					foreach (isset($lookScreens[$descendant]) ? $lookScreens[$descendant] : array() as $screen) {
						$through[] = $screen + array('via' => $looks[$descendant]['name']);
					}
				}
				$lines = '';
				foreach ($row['through'] as $level) {
					$lines .= '<span class="fz-tree__line" aria-hidden="true" style="--fz-level: ' . $level . '"></span>';
				}
				if ($row['depth'] > 0) {
					$lines .= '<span class="fz-tree__line fz-tree__line--elbow" aria-hidden="true" style="--fz-level: ' . ($row['depth'] - 1) . '"></span>';
					if (!$row['last']) {
						$lines .= '<span class="fz-tree__line" aria-hidden="true" style="--fz-level: ' . ($row['depth'] - 1) . '"></span>';
					}
				}
				if ($row['parent']) {
					$lines .= '<span class="fz-tree__line fz-tree__line--stem" aria-hidden="true" style="--fz-level: ' . $row['depth'] . '"></span>';
				}
			?>
				<tr<?php echo $isSite ? ' class="formulize-looks__site"' : ''; ?>>
					<td class="fz-tree__cell" style="--fz-depth: <?php echo $row['depth']; ?>"><?php echo $lines; ?><span class="formulize-looks__name"><strong><?php echo formulize_looksH($look['name']); ?></strong><?php
						if ($isSite) { echo '<span class="fz-badge fz-badge--accent">' . formulize_looksH(_AM_APPEARANCE_LOOKS_SITE_LOOK) . '</span>'; }
					?></span></td>
					<td><?php echo $look['base'] !== '' ? formulize_looksH($looks[$look['base']]['name']) : '<span class="fz-text-subtle">' . formulize_looksH(_AM_APPEARANCE_LOOKS_NOTHING) . '</span>'; ?></td>
					<td>
						<?php
						// what the look changes, in three kinds, in words; the popover has the rest
						$categories = formulize_looksCategories($summary);
						$phrases = array_map(function ($item) { return $item['phrase']; }, $summary);
						?>
						<button type="button" class="fz-btn fz-btn--ghost fz-btn--sm formulize-looks__changes-btn" popovertarget="<?php echo $id; ?>-base" style="anchor-name: --<?php echo $id; ?>-base" aria-label="<?php echo formulize_looksH($phrases ? sprintf(_AM_APPEARANCE_LOOKS_CHANGES_ARE, implode(', ', $phrases)) : _AM_APPEARANCE_LOOKS_NO_CHANGES); ?>">
							<?php if (!$categories) { ?>
							<span class="fz-text-subtle"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_NO_CHANGES); ?></span>
							<?php } ?>
							<?php foreach ($categories as $index => $category) { ?>
							<?php if ($index) { ?><span class="formulize-looks__dot" aria-hidden="true">·</span><?php } ?>
							<span class="formulize-looks__category"><?php echo formulize_looksIcon($category['icon']); ?><?php echo formulize_looksH($category['text']); ?></span>
							<?php } ?>
						</button>
						<div class="fz-popover formulize-looks__about" id="<?php echo $id; ?>-base" popover style="position-anchor: --<?php echo $id; ?>-base">
							<?php if ($chain) { ?>
							<p class="fz-popover__title"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_BASED_ON); ?></p>
							<p class="formulize-looks__chain"><?php echo implode('<span aria-hidden="true">›</span>', array_map(function ($at) use ($looks) { return '<span>' . formulize_looksH($looks[$at]['name']) . '</span>'; }, $chain)); ?></p>
							<hr class="fz-popover__sep">
							<?php } ?>
							<p class="fz-popover__title"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_CHANGES); ?></p>
							<?php if ($summary) { ?>
							<dl class="fz-dl formulize-looks__changes">
								<?php foreach ($summary as $item) { ?>
								<div>
									<dt><span class="formulize-looks__icon"><?php echo formulize_looksIcon($item['kind']); ?></span><?php echo formulize_looksH($item['label']); ?></dt>
									<dd><?php
									if (!empty($item['image'])) {
										echo '<img class="formulize-looks__thumb" src="' . formulize_looksH($item['image']) . '" alt="' . formulize_looksH(sprintf(_AM_APPEARANCE_LOOKS_IMAGE, $look['name'], $item['phrase'])) . '">';
									} elseif (!empty($item['swatches'])) {
										echo '<span class="formulize-looks__swatches">';
										foreach ($item['swatches'] as $colourLabel => $hex) {
											echo '<span class="formulize-looks__swatch" style="background: ' . formulize_looksH($hex) . '" title="' . formulize_looksH($colourLabel . ' ' . $hex) . '"></span>';
										}
										echo '</span><span class="fz-sr-only">' . formulize_looksH(implode(', ', array_keys($item['swatches']))) . '</span>';
									} else {
										echo formulize_looksH($item['detail']);
									}
									?></dd>
								</div>
								<?php } ?>
							</dl>
							<p class="formulize-looks__foot"><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_EVERYTHING_ELSE, formulize_looksH($from)); ?></p>
							<?php } else { ?>
							<p class="fz-text-muted"><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_NOTHING_YET, formulize_looksH($from)); ?></p>
							<?php } ?>
						</div>
					</td>
					<td>
						<?php if (!$isSite AND !$screens AND !$through) { ?>
						<span class="fz-text-subtle"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_NOT_USED); ?></span>
						<?php } else { ?>
						<button type="button" class="fz-btn fz-btn--ghost fz-btn--sm formulize-looks__more" popovertarget="<?php echo $id; ?>-used" style="anchor-name: --<?php echo $id; ?>-used"><span><?php
							$n = count($screens);
							echo formulize_looksH($isSite
								? ($n ? sprintf($n == 1 ? _AM_APPEARANCE_LOOKS_USED_SITE_SCREEN : _AM_APPEARANCE_LOOKS_USED_SITE_SCREENS, $n) : _AM_APPEARANCE_LOOKS_USED_SITE)
								: ($n ? sprintf($n == 1 ? _AM_APPEARANCE_LOOKS_USED_SCREEN : _AM_APPEARANCE_LOOKS_USED_SCREENS, $n) : _AM_APPEARANCE_LOOKS_USED_NO_SCREENS));
						?></span><?php echo $through ? '<small>' . formulize_looksT(_AM_APPEARANCE_LOOKS_INDIRECTLY, count($through)) . '</small>' : ''; ?></button>
						<div class="fz-popover formulize-looks__about" id="<?php echo $id; ?>-used" popover style="position-anchor: --<?php echo $id; ?>-used">
							<?php if ($isSite) { ?>
							<p class="fz-popover__title"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_THE_SITE); ?></p>
							<dl class="fz-dl formulize-looks__changes">
								<div>
									<dt><span class="formulize-looks__icon"><?php echo formulize_looksIcon('site'); ?></span><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_EVERY_PAGE); ?></dt>
									<dd><?php echo $isSiteTheme ? formulize_looksT($onSiteLook == 1 ? _AM_APPEARANCE_LOOKS_FOLLOWS_IT : _AM_APPEARANCE_LOOKS_FOLLOW_IT, $onSiteLook) : formulize_looksT(_AM_APPEARANCE_LOOKS_WHEN_THEME, formulize_looksH($theme)); ?></dd>
								</div>
							</dl>
							<?php } ?>
							<?php if ($screens) { ?>
							<?php if ($isSite) { ?><hr class="fz-popover__sep"><?php } ?>
							<p class="fz-popover__title"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_SET_TO_IT); ?></p>
							<dl class="fz-dl formulize-looks__changes">
								<?php foreach ($screens as $screen) { ?>
								<div>
									<dt><span class="formulize-looks__icon"><?php echo formulize_looksIcon('screen'); ?></span><a class="formulize-looks__link" href="<?php echo formulize_looksH(formulize_looksScreenUrl($screen)); ?>"><?php echo formulize_looksH($screen['title']); ?></a></dt>
									<dd></dd>
								</div>
								<?php } ?>
							</dl>
							<?php } ?>
							<?php if ($through) { ?>
							<?php if ($isSite OR $screens) { ?><hr class="fz-popover__sep"><?php } ?>
							<p class="fz-popover__title"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_THROUGH); ?></p>
							<dl class="fz-dl formulize-looks__changes">
								<?php foreach ($through as $screen) { ?>
								<div>
									<dt><span class="formulize-looks__icon"><?php echo formulize_looksIcon('screen'); ?></span><a class="formulize-looks__link" href="<?php echo formulize_looksH(formulize_looksScreenUrl($screen)); ?>"><?php echo formulize_looksH($screen['title']); ?></a></dt>
									<dd><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_VIA, formulize_looksH($screen['via'])); ?></dd>
								</div>
								<?php } ?>
							</dl>
							<p class="formulize-looks__foot"><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_REACHES, formulize_looksH($look['name'])); ?></p>
							<?php } ?>
						</div>
						<?php } ?>
					</td>
					<td>
						<span class="formulize-looks__actions">
							<button type="button" class="fz-btn fz-btn--sm" popovertarget="<?php echo $id; ?>-edit" style="anchor-name: --<?php echo $id; ?>-edit"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_EDIT); ?></button>
							<div class="fz-popover fz-menu" id="<?php echo $id; ?>-edit" popover style="position-anchor: --<?php echo $id; ?>-edit">
								<a class="fz-menu__item" href="<?php echo formulize_looksH($editorUrl . '&look=' . urlencode($key) . '&mode=simple'); ?>"><?php echo formulize_looksMenuIcon('simple') . formulize_looksH(_AM_APPEARANCE_LOOKS_SIMPLE); ?><span class="fz-menu__note"><?php echo formulize_looksH($sizes ? _AM_APPEARANCE_LOOKS_SIMPLE_NOTE_SIZE : _AM_APPEARANCE_LOOKS_SIMPLE_NOTE); ?></span></a>
								<?php if ($hasAdvanced) { ?>
								<a class="fz-menu__item" href="<?php echo formulize_looksH($editorUrl . '&look=' . urlencode($key) . '&mode=advanced'); ?>"><?php echo formulize_looksMenuIcon('advanced') . formulize_looksH(_AM_APPEARANCE_LOOKS_ADVANCED); ?><span class="fz-menu__note"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_ADVANCED_NOTE); ?></span></a>
								<?php } else { ?>
								<span class="fz-menu__item" aria-disabled="true"><span class="fz-menu__note"><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_SIMPLE_ONLY, formulize_looksH($theme)); ?></span></span>
								<?php } ?>
							</div>
							<button type="button" class="fz-btn fz-btn--sm fz-btn--icon fz-btn--ghost" popovertarget="<?php echo $id; ?>-more" style="anchor-name: --<?php echo $id; ?>-more" aria-label="<?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_MORE, formulize_looksH($look['name'])); ?>"><svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><circle cx="3.5" cy="8" r="1.3" fill="currentColor"/><circle cx="8" cy="8" r="1.3" fill="currentColor"/><circle cx="12.5" cy="8" r="1.3" fill="currentColor"/></svg></button>
							<div class="fz-popover fz-menu" id="<?php echo $id; ?>-more" popover style="position-anchor: --<?php echo $id; ?>-more">
								<button type="button" class="fz-menu__item" commandfor="looks-create" command="show-modal" data-looks-base="<?php echo formulize_looksH($key); ?>"><?php echo formulize_looksMenuIcon('add') . formulize_looksH(_AM_APPEARANCE_LOOKS_CREATE_BASED); ?></button>
								<button type="button" class="fz-menu__item" commandfor="<?php echo $id; ?>-duplicate" command="show-modal"><?php echo formulize_looksMenuIcon('duplicate') . formulize_looksH(_AM_APPEARANCE_LOOKS_DUPLICATE); ?></button>
								<button type="button" class="fz-menu__item" commandfor="<?php echo $id; ?>-rename" command="show-modal"><?php echo formulize_looksMenuIcon('rename') . formulize_looksH(_AM_APPEARANCE_LOOKS_RENAME); ?></button>
								<button type="button" class="fz-menu__item" commandfor="<?php echo $id; ?>-rebase" command="show-modal"><?php echo formulize_looksMenuIcon('rebase') . formulize_looksH(_AM_APPEARANCE_LOOKS_REBASE); ?></button>
								<hr class="fz-menu__sep">
								<?php if (!$isSite) { ?>
								<button type="button" class="fz-menu__item" commandfor="<?php echo $id; ?>-site" command="show-modal"><?php echo formulize_looksMenuIcon('site') . ($isSiteTheme ? formulize_looksH(_AM_APPEARANCE_LOOKS_SET_SITE) : formulize_looksT(_AM_APPEARANCE_LOOKS_SET_SITE_THEME, formulize_looksH($theme))); ?></button>
								<?php } ?>
								<button type="button" class="fz-menu__item fz-menu__item--danger" commandfor="<?php echo $id; ?>-delete" command="show-modal"><?php echo formulize_looksMenuIcon('delete') . formulize_looksH(_AM_APPEARANCE_LOOKS_DELETE); ?></button>
							</div>
						</span>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	</div>
	<?php } ?>

<?php
// ---- The dialogs ------------------------------------------------------------
// One for making a look, and one for the site look; for each look, one for each
// thing that can be done to it, so each says exactly what will happen to that look.
?>
<dialog class="fz-dialog" id="looks-create" aria-labelledby="looks-create-title">
	<form method="post" action="<?php echo formulize_looksH($postUrl); ?>">
		<?php echo formulize_looksFormFields('create', $theme); ?>
		<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="looks-create-title"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_CREATE); ?></h2></div>
		<div class="fz-dialog__body">
			<div class="fz-field">
				<label class="fz-field__label" for="looks-create-name"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_NAME); ?></label>
				<div class="fz-field__body"><input class="fz-input" id="looks-create-name" name="name" type="text" maxlength="60" required autocomplete="off"></div>
			</div>
			<div class="fz-field">
				<label class="fz-field__label" for="looks-create-base"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_BASED_ON); ?></label>
				<div class="fz-field__body">
					<select class="fz-select" id="looks-create-base" name="base"><?php echo formulize_looksBaseOptions($tree, $looks, $theme, isset($looks['default']) ? 'default' : ''); ?></select>
					<p class="fz-field__help"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_CREATE_BASE_HELP); ?></p>
				</div>
			</div>
			<?php if ($sizes) { ?>
			<fieldset class="fz-field">
				<legend class="fz-field__label"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_SIZE); ?></legend>
				<div class="fz-choice-group">
					<label class="fz-choice"><input class="fz-radio" type="radio" name="size" value="" checked> <span><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_SIZE_SAME); ?></span></label>
					<?php foreach ($sizes as $sizeKey => $size) { ?>
					<label class="fz-choice"><input class="fz-radio" type="radio" name="size" value="<?php echo formulize_looksH($sizeKey); ?>"> <span><?php echo formulize_looksH($size['name']); ?> <span class="fz-text-muted">· <?php echo formulize_looksH($size['description']); ?></span></span></label>
					<?php } ?>
				</div>
				<p class="fz-field__help"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_SIZE_HELP); ?></p>
			</fieldset>
			<?php } ?>
		</div>
		<div class="fz-dialog__footer">
			<button type="button" class="fz-btn" commandfor="looks-create" command="close"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_CANCEL); ?></button>
			<button type="submit" class="fz-btn fz-btn--primary"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_CREATE_SUBMIT); ?></button>
		</div>
	</form>
</dialog>

<?php if ($looksCount > 1) { ?>
<dialog class="fz-dialog" id="looks-site" aria-labelledby="looks-site-title">
	<form method="post" action="<?php echo formulize_looksH($postUrl); ?>">
		<?php echo formulize_looksFormFields('site', $theme); ?>
		<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="looks-site-title"><?php echo $isSiteTheme ? formulize_looksH(_AM_APPEARANCE_LOOKS_CHANGE_SITE) : formulize_looksT(_AM_APPEARANCE_LOOKS_CHANGE_SITE_THEME, formulize_looksH($theme)); ?></h2></div>
		<div class="fz-dialog__body">
			<fieldset class="fz-field">
				<legend class="fz-field__label"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_SITE_LEGEND); ?></legend>
				<div class="fz-choice-group formulize-looks__choices">
					<?php foreach ($tree as $row) { ?>
					<label class="fz-choice" style="--d: <?php echo $row['depth']; ?>"><input class="fz-radio" type="radio" name="look" value="<?php echo formulize_looksH($row['key']); ?>"<?php echo $row['key'] === $siteLook ? ' checked' : ''; ?>> <span><?php echo formulize_looksH($looks[$row['key']]['name']) . ($row['key'] === $siteLook ? ' <span class="fz-text-muted">· ' . formulize_looksH(_AM_APPEARANCE_LOOKS_NOW) . '</span>' : ''); ?></span></label>
					<?php } ?>
				</div>
			</fieldset>
		</div>
		<div class="fz-dialog__footer">
			<button type="button" class="fz-btn" commandfor="looks-site" command="close"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_CANCEL); ?></button>
			<button type="submit" class="fz-btn fz-btn--primary"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_SET_SITE); ?></button>
		</div>
	</form>
</dialog>
<?php } ?>

<?php foreach ($tree as $row) {
	$key = $row['key'];
	$look = $looks[$key];
	$id = 'looks-' . $key;
	$name = formulize_looksH($look['name']);
	$children = formulize_appearanceLookChildren($looks, $key);
	$changes = formulize_appearanceLookChanges($looks, $key, $theme);
	$changeList = $changes ? formulize_looksT(_AM_APPEARANCE_LOOKS_LIST, formulize_looksH(implode(', ', $changes))) : '';
	$screens = isset($lookScreens[$key]) ? $lookScreens[$key] : array();
?>
<dialog class="fz-dialog" id="<?php echo $id; ?>-duplicate" aria-labelledby="<?php echo $id; ?>-duplicate-title">
	<form method="post" action="<?php echo formulize_looksH($postUrl); ?>">
		<?php echo formulize_looksFormFields('duplicate', $theme, $key); ?>
		<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="<?php echo $id; ?>-duplicate-title"><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_DUPLICATE_TITLE, $name); ?></h2></div>
		<div class="fz-dialog__body">
			<div class="fz-field">
				<label class="fz-field__label" for="<?php echo $id; ?>-duplicate-name"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_NAME); ?></label>
				<div class="fz-field__body"><input class="fz-input" id="<?php echo $id; ?>-duplicate-name" name="name" type="text" maxlength="60" required autocomplete="off" value="<?php echo formulize_looksH(sprintf(_AM_APPEARANCE_LOOKS_COPY_NAME, $look['name'])); ?>"></div>
			</div>
			<p><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_DUPLICATE_TEXT, formulize_looksH($look['base'] !== '' ? $looks[$look['base']]['name'] : _AM_APPEARANCE_LOOKS_DUPLICATE_NOTHING), $name, $changeList); ?></p>
			<?php if ($children) { ?><p class="fz-text-muted"><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_DUPLICATE_CHILDREN, $name); ?></p><?php } ?>
		</div>
		<div class="fz-dialog__footer">
			<button type="button" class="fz-btn" commandfor="<?php echo $id; ?>-duplicate" command="close"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_CANCEL); ?></button>
			<button type="submit" class="fz-btn fz-btn--primary"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_DUPLICATE); ?></button>
		</div>
	</form>
</dialog>
<dialog class="fz-dialog" id="<?php echo $id; ?>-rename" aria-labelledby="<?php echo $id; ?>-rename-title">
	<form method="post" action="<?php echo formulize_looksH($postUrl); ?>">
		<?php echo formulize_looksFormFields('rename', $theme, $key); ?>
		<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="<?php echo $id; ?>-rename-title"><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_RENAME_TITLE, $name); ?></h2></div>
		<div class="fz-dialog__body">
			<div class="fz-field">
				<label class="fz-field__label" for="<?php echo $id; ?>-rename-name"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_NAME); ?></label>
				<div class="fz-field__body"><input class="fz-input" id="<?php echo $id; ?>-rename-name" name="name" type="text" maxlength="60" required autocomplete="off" value="<?php echo $name; ?>"></div>
			</div>
			<p class="fz-text-muted"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_RENAME_TEXT); ?></p>
		</div>
		<div class="fz-dialog__footer">
			<button type="button" class="fz-btn" commandfor="<?php echo $id; ?>-rename" command="close"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_CANCEL); ?></button>
			<button type="submit" class="fz-btn fz-btn--primary"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_RENAME); ?></button>
		</div>
	</form>
</dialog>
<dialog class="fz-dialog" id="<?php echo $id; ?>-rebase" aria-labelledby="<?php echo $id; ?>-rebase-title">
	<form method="post" action="<?php echo formulize_looksH($postUrl); ?>">
		<?php echo formulize_looksFormFields('rebase', $theme, $key); ?>
		<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="<?php echo $id; ?>-rebase-title"><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_REBASE_TITLE, $name); ?></h2></div>
		<div class="fz-dialog__body">
			<div class="fz-field">
				<label class="fz-field__label" for="<?php echo $id; ?>-rebase-base"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_BASED_ON); ?></label>
				<div class="fz-field__body"><select class="fz-select" id="<?php echo $id; ?>-rebase-base" name="base"><?php echo formulize_looksBaseOptions($tree, $looks, $theme, $look['base'], array_merge(array($key), formulize_appearanceLookDescendants($looks, $key))); ?></select></div>
			</div>
			<p><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_REBASE_TEXT, $name, $changeList); ?></p>
			<?php if ($children) { ?><p class="fz-text-muted"><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_REBASE_CHILDREN, $name); ?></p><?php } ?>
		</div>
		<div class="fz-dialog__footer">
			<button type="button" class="fz-btn" commandfor="<?php echo $id; ?>-rebase" command="close"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_CANCEL); ?></button>
			<button type="submit" class="fz-btn fz-btn--primary"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_REBASE); ?></button>
		</div>
	</form>
</dialog>
<?php if ($key !== $siteLook) { ?>
<dialog class="fz-dialog" id="<?php echo $id; ?>-site" aria-labelledby="<?php echo $id; ?>-site-title">
	<form method="post" action="<?php echo formulize_looksH($postUrl); ?>">
		<?php echo formulize_looksFormFields('site', $theme, $key); ?>
		<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="<?php echo $id; ?>-site-title"><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_SITE_TITLE, $name); ?></h2></div>
		<div class="fz-dialog__body">
			<p><?php echo ($isSiteTheme ? formulize_looksT(_AM_APPEARANCE_LOOKS_SITE_TEXT, $name) : formulize_looksT(_AM_APPEARANCE_LOOKS_SITE_TEXT_THEME, formulize_looksH($theme), $name)) . ' ' . formulize_looksT(_AM_APPEARANCE_LOOKS_SITE_NOW, formulize_looksH($looks[$siteLook]['name'])); ?></p>
		</div>
		<div class="fz-dialog__footer">
			<button type="button" class="fz-btn" commandfor="<?php echo $id; ?>-site" command="close"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_CANCEL); ?></button>
			<button type="submit" class="fz-btn fz-btn--primary"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_SET_SITE); ?></button>
		</div>
	</form>
</dialog>
<?php } ?>
<dialog class="fz-dialog" id="<?php echo $id; ?>-delete" aria-labelledby="<?php echo $id; ?>-delete-title">
	<?php if ($key === $siteLook) { ?>
	<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="<?php echo $id; ?>-delete-title"><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_DELETE_CANT_TITLE, $name); ?></h2></div>
	<div class="fz-dialog__body"><p><?php echo $isSiteTheme ? formulize_looksT(_AM_APPEARANCE_LOOKS_DELETE_SITE, $name) : formulize_looksT(_AM_APPEARANCE_LOOKS_DELETE_SITE_THEME, $name, formulize_looksH($theme)); ?></p></div>
	<div class="fz-dialog__footer">
		<?php if ($looksCount > 1) { ?><button type="button" class="fz-btn" commandfor="looks-site" command="show-modal"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_CHANGE_SITE); ?></button><?php } ?>
		<button type="button" class="fz-btn fz-btn--primary" commandfor="<?php echo $id; ?>-delete" command="close"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_CLOSE); ?></button>
	</div>
	<?php } elseif ($children) { ?>
	<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="<?php echo $id; ?>-delete-title"><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_DELETE_YET_TITLE, $name); ?></h2></div>
	<div class="fz-dialog__body">
		<p><?php echo count($children) == 1 ? formulize_looksT(_AM_APPEARANCE_LOOKS_DELETE_CHILD, $name) : formulize_looksT(_AM_APPEARANCE_LOOKS_DELETE_CHILDREN, count($children), $name); ?></p>
		<ul><?php foreach ($children as $child) { echo '<li>' . formulize_looksH($looks[$child]['name']) . '</li>'; } ?></ul>
		<p><?php echo formulize_looksH(count($children) == 1 ? _AM_APPEARANCE_LOOKS_DELETE_CHILD_FIRST : _AM_APPEARANCE_LOOKS_DELETE_CHILDREN_FIRST); ?></p>
	</div>
	<div class="fz-dialog__footer"><button type="button" class="fz-btn fz-btn--primary" commandfor="<?php echo $id; ?>-delete" command="close"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_CLOSE); ?></button></div>
	<?php } else { ?>
	<form method="post" action="<?php echo formulize_looksH($postUrl); ?>">
		<?php echo formulize_looksFormFields('delete', $theme, $key); ?>
		<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="<?php echo $id; ?>-delete-title"><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_DELETE_TITLE, $name); ?></h2></div>
		<div class="fz-dialog__body">
			<?php if ($screens) { ?>
			<p><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_DELETE_SCREENS, $name, formulize_looksH($looks[$siteLook]['name'])); ?></p>
			<ul><?php foreach ($screens as $screen) { echo '<li>' . formulize_looksH($screen['title']) . '</li>'; } ?></ul>
			<?php } else { ?>
			<p><?php echo formulize_looksT(_AM_APPEARANCE_LOOKS_DELETE_UNUSED, $name); ?></p>
			<?php } ?>
			<p class="fz-text-muted"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_DELETE_UNDONE); ?></p>
		</div>
		<div class="fz-dialog__footer">
			<button type="button" class="fz-btn" commandfor="<?php echo $id; ?>-delete" command="close"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_CANCEL); ?></button>
			<button type="submit" class="fz-btn fz-btn--danger"><?php echo formulize_looksH(_AM_APPEARANCE_LOOKS_DELETE_SUBMIT); ?></button>
		</div>
	</form>
	<?php } ?>
</dialog>
<?php } ?>
</div>

<script>
// "Create look based on this" opens the same dialog as Create new look, with that
// look chosen as the base. The rest of the tab needs no script of its own.
document.addEventListener('click', function (event) {
	var button = event.target.closest ? event.target.closest('[data-looks-base]') : null;
	var base = document.getElementById('looks-create-base');
	if (button && base) {
		base.value = button.getAttribute('data-looks-base');
		var name = document.getElementById('looks-create-name');
		if (name) { name.value = ''; }
	}
});
</script>
<?php
$adminPage['looks_html'] = ob_get_clean();
