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

// The Looks page: a theme's looks, as a tree of the looks each is based on, and
// everything done to them except changing how one looks, which is the appearance
// editor's job. Which look the site uses; making, copying, renaming, rebasing and
// deleting looks; which screens use each; and a way into the editor for each.
// The looks themselves are in include/appearance.php.
//
// Formulize module admins only, and nothing is changed while the system is locked,
// like the rest of the admin pages. It is the Looks tab of the Appearance admin
// pages (include/configsettings_registry.php). It is shown in the site's theme,
// with Formulize UI, as the style guide is: the popovers, menus and dialogs here
// are Formulize UI's.

require_once "../../mainfile.php";

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/appearance.php';

global $xoopsUser, $xoopsDB;
$gperm_handler = xoops_gethandler('groupperm');
if (!$xoopsUser OR !$gperm_handler->checkRight('module_admin', getFormulizeModId(), $xoopsUser->getGroups(), 1)) {
	redirect_header(XOOPS_URL, 3, _NOPERM);
	exit();
}
$saveLocked = sendSaveLockPrefToTemplate();

// The themes whose looks can be managed here: those the appearance editor can edit.
// The page opens on the site's theme; choosing another doesn't change the site's.
$siteTheme = formulize_getDefaultAppearanceTheme();
$themes = array();
foreach (formulize_getAppearanceThemes() as $themeDir => $themeName) {
	if (formulize_getAppearanceEditorUrl($themeDir)) {
		$themes[$themeDir] = $themeName;
	}
}
$askedTheme = isset($_POST['theme']) ? (string) $_POST['theme'] : (isset($_GET['theme']) ? (string) $_GET['theme'] : '');
$theme = isset($themes[$askedTheme]) ? $askedTheme : (isset($themes[$siteTheme]) ? $siteTheme : (string) key($themes));
$isSiteTheme = ($theme === $siteTheme);
$pageUrl = XOOPS_URL . '/modules/formulize/looks.php?theme=' . urlencode($theme);

// ---- What was asked for -----------------------------------------------------
// Each form posts back here, and on success goes back to the page (so a reload
// doesn't post again), saying what was done.
$errors = array();
$action = isset($_POST['looks_action']) ? (string) $_POST['looks_action'] : '';
if ($action !== '' AND $theme !== '') {
	$key = isset($_POST['look']) ? strtolower((string) $_POST['look']) : '';
	$name = isset($_POST['name']) ? (string) $_POST['name'] : '';
	$base = isset($_POST['base']) ? strtolower((string) $_POST['base']) : '';
	$looks = formulize_getAppearanceLooks($theme);
	$problem = '';
	$done = '';
	if ($saveLocked) {
		$problem = 'Nothing was changed, because this system is locked. Turn off the lock in the Formulize preferences to make changes.';
	} elseif (!$GLOBALS['xoopsSecurity']->check(true, false, 'formulize_looks_token')) {
		$problem = 'Nothing was changed, because the page had been open too long. Please try again.';
	} elseif ($action == 'create') {
		$problem = formulize_createAppearanceLook($theme, $name, $base, isset($_POST['size']) ? (string) $_POST['size'] : '', $key);
		$done = 'created';
	} elseif ($action == 'duplicate') {
		$problem = formulize_duplicateAppearanceLook($theme, $key, $name, $key);
		$done = 'duplicated';
	} elseif ($action == 'rename') {
		$problem = formulize_renameAppearanceLook($theme, $key, $name);
		$done = 'renamed';
	} elseif ($action == 'rebase') {
		$problem = formulize_rebaseAppearanceLook($theme, $key, $base);
		$done = 'rebased';
	} elseif ($action == 'site') {
		$problem = formulize_setAppearanceSiteLook($theme, $key);
		$done = 'site';
	} elseif ($action == 'delete') {
		$deletedName = isset($looks[$key]) ? $looks[$key]['name'] : '';
		$problem = formulize_deleteAppearanceLook($theme, $key);
		$done = 'deleted';
		$key = $deletedName; // the look is gone, so the message names it
	}
	if ($problem === '' AND $done !== '') {
		header('Location: ' . $pageUrl . '&done=' . $done . '&look=' . urlencode($key));
		exit();
	}
	$errors[] = $problem;
}

// ---- The looks, as they stand ---------------------------------------------------
$looks = $theme !== '' ? formulize_getAppearanceLooks($theme) : array();
$siteLook = $theme !== '' ? formulize_getAppearanceSiteLook($theme) : '';
$tree = formulize_appearanceLookTree($looks);
$sizes = formulize_appearanceSizes($theme);
$hasAdvanced = formulize_appearanceThemeHasAdvancedMode($theme);
$editorUrl = formulize_getAppearanceEditorUrl($theme);

// the screens set to each look, and how many use the site look
$lookScreens = formulize_appearanceLookScreens();
$onSiteLook = 0;
$result = $xoopsDB->query('SELECT COUNT(*) FROM ' . $xoopsDB->prefix('formulize_screen') . " WHERE look = ''");
if ($result) {
	list($onSiteLook) = $xoopsDB->fetchRow($result);
}

// what was just done, said once the page is back
$message = '';
if (isset($_GET['done']) AND !$errors) {
	$doneKey = isset($_GET['look']) ? (string) $_GET['look'] : '';
	$doneName = isset($looks[$doneKey]) ? $looks[$doneKey]['name'] : $doneKey;
	$messages = array(
		'created' => 'Created %s.',
		'duplicated' => 'Created %s, a copy.',
		'renamed' => 'Renamed it %s.',
		'rebased' => 'Changed what %s is based on.',
		'site' => '%s is now the site look' . ($isSiteTheme ? '.' : ' for ' . $theme . '.'),
		'deleted' => 'Deleted %s.',
	);
	if (isset($messages[$_GET['done']])) {
		$message = sprintf($messages[$_GET['done']], $doneName);
	}
}

$notices = array();
if ($saveLocked) {
	$notices[] = 'Nothing can be changed, because this system is locked. Turn off the lock in the Formulize preferences to make changes.';
}
if ($theme !== '' AND !formulize_appearanceDirIsWritable($theme)) {
	$notices[] = 'Nothing can be changed for the ' . $theme . ' theme: the web server cannot write to ' . formulize_getAppearanceDir($theme) . '. Make that folder writable on your server.';
}

function formulize_looksH($text) {
	return htmlspecialchars((string) $text, ENT_QUOTES);
}

// A screen's Appearance tab, in the admin pages
function formulize_looksScreenUrl($screen) {
	return XOOPS_URL . '/modules/formulize/admin/ui.php?page=screen&fid=' . $screen['fid'] . '&sid=' . $screen['sid'] . '&tab=appearance';
}

// The looks as options of a select, in the order of the tree, indented, with
// Nothing first; the looks in $exclude can't be chosen
function formulize_looksBaseOptions($tree, $looks, $theme, $selected, $exclude = array()) {
	$html = '<option value=""' . ($selected === '' ? ' selected' : '') . '>Nothing: start from ' . formulize_looksH($theme) . '\'s own settings</option>';
	foreach ($tree as $row) {
		$off = in_array($row['key'], $exclude, true);
		$html .= '<option value="' . formulize_looksH($row['key']) . '"' . ($row['key'] === $selected ? ' selected' : '') . ($off ? ' disabled' : '') . '>'
			. str_repeat('&emsp;', $row['depth']) . formulize_looksH($looks[$row['key']]['name'])
			. ($off ? ($row['key'] === $exclude[0] ? ' (this look)' : ' (based on this look)') : '') . '</option>';
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

include_once XOOPS_ROOT_PATH . '/header.php';
formulize_uiAddToTheme();

$appearanceUrl = XOOPS_URL . '/modules/formulize/admin/ui.php?page=appearance';
$looksCount = count($looks);
?>
<style>
/* This page's own layout. The classes are formulize-, since they are Formulize's
   own; everything else is Formulize UI. */
.formulize-looks__crumbs { display: flex; flex-wrap: wrap; gap: calc(var(--fz-spacing) * 1.5); color: var(--fz-color-text-muted); font-size: var(--fz-text-xs-plus); }
.formulize-looks__crumbs a { color: inherit; text-decoration: none; }
.formulize-looks__crumbs a:hover { text-decoration: underline; }
.formulize-looks__context { display: flex; flex-wrap: wrap; align-items: center; gap: calc(var(--fz-spacing) * 2) calc(var(--fz-spacing) * 5); }
.formulize-looks__context label, .formulize-looks__context-label { font-size: var(--fz-text-xs-plus); font-weight: var(--fz-font-weight-medium); }
.formulize-looks__context .fz-select { width: auto; min-width: 11rem; text-align: start; }
.formulize-looks__site td { background: var(--fz-color-accent-soft); }
.formulize-looks__site:hover td { background: var(--fz-color-accent-soft-2); }
.formulize-looks__name { display: inline-flex; align-items: center; gap: calc(var(--fz-spacing) * 2); vertical-align: middle; }
.formulize-looks__name strong { font-weight: var(--fz-font-weight-semibold); }
/* a value that opens a popover with more about it: a ghost button, its text lined up with the column's */
.formulize-looks__more { gap: calc(var(--fz-spacing) * 1.5); max-width: 100%; margin-inline-start: calc(var(--fz-spacing) * -3); font-weight: var(--fz-font-weight-normal); vertical-align: middle; }
.formulize-looks__more > span:first-child { text-decoration: underline dotted var(--fz-color-text-subtle); text-underline-offset: 3px; }
.formulize-looks__more small { color: var(--fz-color-text-muted); font-size: var(--fz-text-xs); }
.formulize-looks__more--quiet > span:first-child { color: var(--fz-color-text-muted); }
.formulize-looks__col-base { width: 11rem; }
.formulize-looks__col-used { width: 13rem; }
.formulize-looks__col-actions { width: 8.5rem; }
.formulize-looks__actions { display: inline-flex; justify-content: flex-end; gap: calc(var(--fz-spacing) * 1.5); width: 100%; }
.formulize-looks__chain { display: flex; flex-wrap: wrap; gap: calc(var(--fz-spacing) * 1.5); }
.formulize-looks__chain span[aria-hidden] { color: var(--fz-color-text-subtle); }
.formulize-looks__choices { max-height: 18rem; overflow: auto; padding: calc(var(--fz-spacing) * 2); border: 1px solid var(--fz-color-border); border-radius: var(--fz-radius-md); }
.formulize-looks__choices .fz-choice { padding-inline-start: calc(var(--d, 0) * var(--fz-spacing) * 5); }
</style>

<div class="formulize-looks fz-container fz-container--wide fz-py-6">
	<div class="fz-stack fz-gap-5">

		<div class="fz-stack fz-gap-3">
			<nav class="formulize-looks__crumbs" aria-label="Breadcrumb"><a href="<?php echo formulize_looksH(XOOPS_URL . '/modules/formulize/admin/ui.php'); ?>">Admin</a><span aria-hidden="true">›</span><a href="<?php echo formulize_looksH($appearanceUrl); ?>">Appearance</a><span aria-hidden="true">›</span><span>Looks</span></nav>
			<div class="fz-toolbar">
				<div class="fz-toolbar__start"><h1 class="fz-text-2xl fz-font-semibold">Looks</h1></div>
				<div class="fz-toolbar__end formulize-looks__context">
					<form method="get" action="<?php echo formulize_looksH(XOOPS_URL . '/modules/formulize/looks.php'); ?>" class="fz-cluster fz-gap-2">
						<label for="looks-theme">Theme</label>
						<select id="looks-theme" name="theme" class="fz-select" onchange="this.form.submit()">
							<?php foreach ($themes as $themeDir => $themeName) { ?>
							<option value="<?php echo formulize_looksH($themeDir); ?>"<?php echo $themeDir === $theme ? ' selected' : ''; ?>><?php echo formulize_looksH($themeName) . ($themeDir === $siteTheme ? ' (active)' : ''); ?></option>
							<?php } ?>
						</select>
						<noscript><button type="submit" class="fz-btn">Show</button></noscript>
					</form>
					<?php if ($looksCount > 1) { ?>
					<div class="fz-cluster fz-gap-2">
						<span class="formulize-looks__context-label" id="looks-site-label"><?php echo $isSiteTheme ? 'Site look' : 'Site look when ' . formulize_looksH($theme) . ' is active'; ?></span>
						<button type="button" class="fz-select" aria-labelledby="looks-site-label looks-site-button" id="looks-site-button" commandfor="looks-site" command="show-modal"><?php echo formulize_looksH($looks[$siteLook]['name']); ?></button>
					</div>
					<?php } ?>
				</div>
			</div>
			<p class="fz-text-muted" style="max-width: 46rem">A look is the logo, colours, fonts, page width and sizes that pages are shown with. The site uses one look, and a screen can use another. A look based on another follows it, except for what it changes itself.</p>
		</div>

		<?php if (!$isSiteTheme) { ?>
		<div class="fz-callout fz-callout--info">
			<p class="fz-callout__title">You're looking at <?php echo formulize_looksH($theme); ?>'s looks.</p>
			<p>The site's theme is <?php echo formulize_looksH($siteTheme); ?>. Choosing a theme here doesn't change it; that's in <a href="<?php echo formulize_looksH($appearanceUrl); ?>">Appearance › Settings</a>.</p>
		</div>
		<?php } ?>
		<?php foreach ($notices as $notice) { ?>
		<div class="fz-callout fz-callout--warning"><p><?php echo formulize_looksH($notice); ?></p></div>
		<?php } ?>
		<?php foreach (array_filter($errors) as $error) { ?>
		<div class="fz-callout fz-callout--danger" role="alert"><p><?php echo formulize_looksH($error); ?></p></div>
		<?php } ?>
		<?php if ($message !== '') { ?>
		<div class="fz-callout fz-callout--success" role="status"><p><?php echo formulize_looksH($message); ?></p></div>
		<?php } ?>

		<section class="fz-stack fz-gap-3" aria-labelledby="looks-all">
			<div class="fz-toolbar">
				<div class="fz-toolbar__start"><h2 class="fz-text-lg fz-font-semibold" id="looks-all">All looks <span class="fz-text-subtle fz-font-normal">(<?php echo $looksCount; ?>)</span></h2></div>
				<div class="fz-toolbar__end"><button type="button" class="fz-btn fz-btn--primary" commandfor="looks-create" command="show-modal" data-looks-base="<?php echo isset($looks['default']) ? 'default' : ''; ?>">Create new look</button></div>
			</div>
			<div class="fz-card fz-card--flush fz-overflow-x-auto">
				<table class="fz-table fz-tree">
					<thead><tr><th scope="col">Look</th><th scope="col" class="formulize-looks__col-base">Based on</th><th scope="col" class="formulize-looks__col-used">Used by</th><th scope="col" class="formulize-looks__col-actions"><span class="fz-sr-only">Actions</span></th></tr></thead>
					<tbody>
					<?php foreach ($tree as $row) {
						$key = $row['key'];
						$look = $looks[$key];
						$isSite = ($key === $siteLook);
						$id = 'looks-' . $key;
						$chain = formulize_appearanceLookChain($looks, $key);
						array_pop($chain); // the look itself
						$changes = formulize_appearanceLookChanges($look['settings'], $theme);
						$from = $look['base'] !== '' ? $looks[$look['base']]['name'] : $theme . '\'s own settings';
						$screens = isset($lookScreens[$key]) ? $lookScreens[$key] : array();
						$through = array();
						foreach (formulize_appearanceLookDescendants($looks, $key) as $descendant) {
							foreach (isset($lookScreens[$descendant]) ? $lookScreens[$descendant] : array() as $screen) {
								$through[] = $screen + array('via' => $looks[$descendant]['name']);
							}
						}
						// the marks: a look with a base that has a logo, colours or fonts of its own
						$marks = array();
						if ($look['base'] !== '') {
							if (isset($look['settings']['appearance_logo']) OR isset($look['settings']['appearance_favicon'])) { $marks[] = 'Own logo'; }
							foreach (array_keys(formulize_appearanceColourMapDefinition()) as $colourKey) {
								if (isset($look['settings']['appearance_' . $colourKey])) { $marks[] = 'Own colours'; break; }
							}
							if (isset($look['settings']['appearance_font']) OR isset($look['settings']['appearance_headingfont'])) { $marks[] = 'Own fonts'; }
						}
						$lines = '';
						foreach ($row['through'] as $level) {
							$lines .= '<span class="fz-tree__line" style="--fz-level: ' . $level . '"></span>';
						}
						if ($row['depth'] > 0) {
							$lines .= '<span class="fz-tree__line fz-tree__line--elbow" style="--fz-level: ' . ($row['depth'] - 1) . '"></span>';
							if (!$row['last']) {
								$lines .= '<span class="fz-tree__line" style="--fz-level: ' . ($row['depth'] - 1) . '"></span>';
							}
						}
						if ($row['parent']) {
							$lines .= '<span class="fz-tree__line fz-tree__line--stem" style="--fz-level: ' . $row['depth'] . '"></span>';
						}
					?>
						<tr<?php echo $isSite ? ' class="formulize-looks__site"' : ''; ?>>
							<td class="fz-tree__cell" style="--fz-depth: <?php echo $row['depth']; ?>"><?php echo $lines; ?><span class="formulize-looks__name"><strong><?php echo formulize_looksH($look['name']); ?></strong><?php
								if ($isSite) { echo '<span class="fz-badge fz-badge--accent">Site look</span>'; }
								foreach ($marks as $mark) { echo '<span class="fz-badge fz-badge--plain">' . $mark . '</span>'; }
							?></span></td>
							<td>
								<button type="button" class="fz-btn fz-btn--ghost fz-btn--sm formulize-looks__more<?php echo $look['base'] === '' ? ' formulize-looks__more--quiet' : ''; ?>" popovertarget="<?php echo $id; ?>-base" style="anchor-name: --<?php echo $id; ?>-base"><span><?php echo $look['base'] !== '' ? formulize_looksH($looks[$look['base']]['name']) : 'Nothing'; ?></span></button>
								<div class="fz-popover" id="<?php echo $id; ?>-base" popover style="position-anchor: --<?php echo $id; ?>-base">
									<p class="fz-popover__title">Based on</p>
									<?php if ($chain) { ?>
									<p class="formulize-looks__chain"><?php echo implode('<span aria-hidden="true">›</span>', array_map(function ($at) use ($looks) { return '<span>' . formulize_looksH($looks[$at]['name']) . '</span>'; }, $chain)); ?></p>
									<?php } else { ?>
									<p>Nothing. It starts from <?php echo formulize_looksH($theme); ?>'s own settings.</p>
									<?php } ?>
									<hr class="fz-popover__sep">
									<p class="fz-popover__title">What <?php echo formulize_looksH($look['name']); ?> changes</p>
									<?php if ($changes) { ?>
									<ul class="fz-list-none fz-p-0 fz-m-0"><?php foreach ($changes as $change) { echo '<li>' . formulize_looksH($change) . '</li>'; } ?></ul>
									<p class="fz-text-muted">Everything else comes from <?php echo formulize_looksH($from); ?>.</p>
									<?php } else { ?>
									<p class="fz-text-muted">Nothing yet: it looks the same as <?php echo formulize_looksH($from); ?>.</p>
									<?php } ?>
									<hr class="fz-popover__sep">
									<button type="button" class="fz-btn fz-btn--sm" commandfor="<?php echo $id; ?>-rebase" command="show-modal">Change base</button>
								</div>
							</td>
							<td>
								<?php if (!$isSite AND !$screens AND !$through) { ?>
								<span class="fz-text-subtle">Not used</span>
								<?php } else { ?>
								<button type="button" class="fz-btn fz-btn--ghost fz-btn--sm formulize-looks__more" popovertarget="<?php echo $id; ?>-used" style="anchor-name: --<?php echo $id; ?>-used"><span><?php
									echo $isSite ? 'The site' . ($screens ? ', ' . count($screens) . (count($screens) == 1 ? ' screen' : ' screens') : '') : ($screens ? count($screens) . (count($screens) == 1 ? ' screen' : ' screens') : 'No screens');
								?></span><?php echo $through ? '<small>+' . count($through) . ' indirectly</small>' : ''; ?></button>
								<div class="fz-popover" id="<?php echo $id; ?>-used" popover style="position-anchor: --<?php echo $id; ?>-used">
									<?php if ($isSite) { ?>
									<p class="fz-popover__title">The site</p>
									<p><?php echo $isSiteTheme ? 'Every page, except screens set to another look. ' . $onSiteLook . ($onSiteLook == 1 ? ' screen uses' : ' screens use') . ' the site look.' : 'When ' . formulize_looksH($theme) . ' is the site\'s theme.'; ?></p>
									<?php } ?>
									<?php if ($screens) { ?>
									<?php if ($isSite) { ?><hr class="fz-popover__sep"><?php } ?>
									<p class="fz-popover__title">Screens set to <?php echo formulize_looksH($look['name']); ?></p>
									<ul class="fz-list-none fz-p-0 fz-m-0"><?php foreach ($screens as $screen) { echo '<li><a href="' . formulize_looksH(formulize_looksScreenUrl($screen)) . '">' . formulize_looksH($screen['title']) . '</a></li>'; } ?></ul>
									<?php } ?>
									<?php if ($through) { ?>
									<?php if ($isSite OR $screens) { ?><hr class="fz-popover__sep"><?php } ?>
									<p class="fz-popover__title">Indirectly, through looks based on it</p>
									<ul class="fz-list-none fz-p-0 fz-m-0"><?php foreach ($through as $screen) { echo '<li><a href="' . formulize_looksH(formulize_looksScreenUrl($screen)) . '">' . formulize_looksH($screen['title']) . '</a> <span class="fz-text-muted fz-text-xs">· ' . formulize_looksH($screen['via']) . '</span></li>'; } ?></ul>
									<p class="fz-text-muted">A change to <?php echo formulize_looksH($look['name']); ?> reaches these too, except where their look sets its own.</p>
									<?php } ?>
								</div>
								<?php } ?>
							</td>
							<td>
								<span class="formulize-looks__actions">
									<button type="button" class="fz-btn fz-btn--sm" popovertarget="<?php echo $id; ?>-edit" style="anchor-name: --<?php echo $id; ?>-edit">Edit</button>
									<div class="fz-popover fz-menu" id="<?php echo $id; ?>-edit" popover style="position-anchor: --<?php echo $id; ?>-edit">
										<a class="fz-menu__item" href="<?php echo formulize_looksH($editorUrl . '&look=' . urlencode($key) . '&mode=simple'); ?>">Simple editor<span class="fz-menu__note">Logo, colours, fonts, page width<?php echo $sizes ? ' and size' : ''; ?></span></a>
										<?php if ($hasAdvanced) { ?>
										<a class="fz-menu__item" href="<?php echo formulize_looksH($editorUrl . '&look=' . urlencode($key) . '&mode=advanced'); ?>">Advanced editor<span class="fz-menu__note">Every part, one at a time</span></a>
										<?php } else { ?>
										<span class="fz-menu__item" aria-disabled="true"><span class="fz-menu__note"><?php echo formulize_looksH($theme); ?> has the simple editor only.</span></span>
										<?php } ?>
									</div>
									<button type="button" class="fz-btn fz-btn--sm fz-btn--icon fz-btn--ghost" popovertarget="<?php echo $id; ?>-more" style="anchor-name: --<?php echo $id; ?>-more" aria-label="More for <?php echo formulize_looksH($look['name']); ?>"><svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><circle cx="3.5" cy="8" r="1.3" fill="currentColor"/><circle cx="8" cy="8" r="1.3" fill="currentColor"/><circle cx="12.5" cy="8" r="1.3" fill="currentColor"/></svg></button>
									<div class="fz-popover fz-menu" id="<?php echo $id; ?>-more" popover style="position-anchor: --<?php echo $id; ?>-more">
										<button type="button" class="fz-menu__item" commandfor="looks-create" command="show-modal" data-looks-base="<?php echo formulize_looksH($key); ?>">Create look based on this</button>
										<button type="button" class="fz-menu__item" commandfor="<?php echo $id; ?>-duplicate" command="show-modal">Duplicate</button>
										<button type="button" class="fz-menu__item" commandfor="<?php echo $id; ?>-rename" command="show-modal">Rename</button>
										<button type="button" class="fz-menu__item" commandfor="<?php echo $id; ?>-rebase" command="show-modal">Change base</button>
										<hr class="fz-menu__sep">
										<?php if (!$isSite) { ?>
										<button type="button" class="fz-menu__item" commandfor="<?php echo $id; ?>-site" command="show-modal">Set as site look<?php echo $isSiteTheme ? '' : ' for ' . formulize_looksH($theme); ?></button>
										<?php } ?>
										<button type="button" class="fz-menu__item fz-menu__item--danger" commandfor="<?php echo $id; ?>-delete" command="show-modal">Delete</button>
									</div>
								</span>
							</td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
			</div>
		</section>
	</div>
</div>

<?php
// ---- The dialogs ------------------------------------------------------------
// One for making a look, and one for the site look; for each look, one for each
// thing that can be done to it, so each says exactly what will happen to that look.
?>
<dialog class="fz-dialog" id="looks-create" aria-labelledby="looks-create-title">
	<form method="post" action="<?php echo formulize_looksH($pageUrl); ?>">
		<?php echo formulize_looksFormFields('create', $theme); ?>
		<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="looks-create-title">Create new look</h2></div>
		<div class="fz-dialog__body">
			<div class="fz-field">
				<label class="fz-field__label" for="looks-create-name">Name</label>
				<div class="fz-field__body"><input class="fz-input" id="looks-create-name" name="name" type="text" maxlength="60" required autocomplete="off"></div>
			</div>
			<div class="fz-field">
				<label class="fz-field__label" for="looks-create-base">Based on</label>
				<div class="fz-field__body">
					<select class="fz-select" id="looks-create-base" name="base"><?php echo formulize_looksBaseOptions($tree, $looks, $theme, isset($looks['default']) ? 'default' : ''); ?></select>
					<p class="fz-field__help">The new look follows its base, except for what you change in it.</p>
				</div>
			</div>
			<?php if ($sizes) { ?>
			<fieldset class="fz-field">
				<legend class="fz-field__label">Size</legend>
				<div class="fz-choice-group">
					<label class="fz-choice"><input class="fz-radio" type="radio" name="size" value="" checked> <span>The same as its base</span></label>
					<?php foreach ($sizes as $sizeKey => $size) { ?>
					<label class="fz-choice"><input class="fz-radio" type="radio" name="size" value="<?php echo formulize_looksH($sizeKey); ?>"> <span><?php echo formulize_looksH($size['name']); ?> <span class="fz-text-muted">· <?php echo formulize_looksH($size['description']); ?></span></span></label>
					<?php } ?>
				</div>
				<p class="fz-field__help">A size is a starting point: its values are written into the new look, which you can then change.</p>
			</fieldset>
			<?php } ?>
		</div>
		<div class="fz-dialog__footer">
			<button type="button" class="fz-btn" commandfor="looks-create" command="close">Cancel</button>
			<button type="submit" class="fz-btn fz-btn--primary">Create look</button>
		</div>
	</form>
</dialog>

<?php if ($looksCount > 1) { ?>
<dialog class="fz-dialog" id="looks-site" aria-labelledby="looks-site-title">
	<form method="post" action="<?php echo formulize_looksH($pageUrl); ?>">
		<?php echo formulize_looksFormFields('site', $theme); ?>
		<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="looks-site-title">Change site look<?php echo $isSiteTheme ? '' : ' for ' . formulize_looksH($theme); ?></h2></div>
		<div class="fz-dialog__body">
			<fieldset class="fz-field">
				<legend class="fz-field__label">Every page uses the site look, except screens set to another</legend>
				<div class="fz-choice-group formulize-looks__choices">
					<?php foreach ($tree as $row) { ?>
					<label class="fz-choice" style="--d: <?php echo $row['depth']; ?>"><input class="fz-radio" type="radio" name="look" value="<?php echo formulize_looksH($row['key']); ?>"<?php echo $row['key'] === $siteLook ? ' checked' : ''; ?>> <span><?php echo formulize_looksH($looks[$row['key']]['name']) . ($row['key'] === $siteLook ? ' <span class="fz-text-muted">· now</span>' : ''); ?></span></label>
					<?php } ?>
				</div>
			</fieldset>
		</div>
		<div class="fz-dialog__footer">
			<button type="button" class="fz-btn" commandfor="looks-site" command="close">Cancel</button>
			<button type="submit" class="fz-btn fz-btn--primary">Set as site look</button>
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
	$changes = array_map('strtolower', formulize_appearanceLookChanges($look['settings'], $theme));
	$screens = isset($lookScreens[$key]) ? $lookScreens[$key] : array();
?>
<dialog class="fz-dialog" id="<?php echo $id; ?>-duplicate" aria-labelledby="<?php echo $id; ?>-duplicate-title">
	<form method="post" action="<?php echo formulize_looksH($pageUrl); ?>">
		<?php echo formulize_looksFormFields('duplicate', $theme, $key); ?>
		<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="<?php echo $id; ?>-duplicate-title">Duplicate <?php echo $name; ?></h2></div>
		<div class="fz-dialog__body">
			<div class="fz-field">
				<label class="fz-field__label" for="<?php echo $id; ?>-duplicate-name">Name</label>
				<div class="fz-field__body"><input class="fz-input" id="<?php echo $id; ?>-duplicate-name" name="name" type="text" maxlength="60" required autocomplete="off" value="<?php echo formulize_looksH($look['name'] . ' copy'); ?>"></div>
			</div>
			<p>The copy is based on <?php echo $look['base'] !== '' ? formulize_looksH($looks[$look['base']]['name']) : 'nothing'; ?>, like <?php echo $name; ?>, and starts with <?php echo $name; ?>'s own changes<?php echo $changes ? ' (' . formulize_looksH(implode(', ', $changes)) . ')' : ''; ?>. It looks the same, but won't follow <?php echo $name; ?> after this.</p>
			<?php if ($children) { ?><p class="fz-text-muted">The looks based on <?php echo $name; ?> aren't copied.</p><?php } ?>
		</div>
		<div class="fz-dialog__footer">
			<button type="button" class="fz-btn" commandfor="<?php echo $id; ?>-duplicate" command="close">Cancel</button>
			<button type="submit" class="fz-btn fz-btn--primary">Duplicate</button>
		</div>
	</form>
</dialog>
<dialog class="fz-dialog" id="<?php echo $id; ?>-rename" aria-labelledby="<?php echo $id; ?>-rename-title">
	<form method="post" action="<?php echo formulize_looksH($pageUrl); ?>">
		<?php echo formulize_looksFormFields('rename', $theme, $key); ?>
		<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="<?php echo $id; ?>-rename-title">Rename <?php echo $name; ?></h2></div>
		<div class="fz-dialog__body">
			<div class="fz-field">
				<label class="fz-field__label" for="<?php echo $id; ?>-rename-name">Name</label>
				<div class="fz-field__body"><input class="fz-input" id="<?php echo $id; ?>-rename-name" name="name" type="text" maxlength="60" required autocomplete="off" value="<?php echo $name; ?>"></div>
			</div>
			<p class="fz-text-muted">Screens and looks that use it keep using it.</p>
		</div>
		<div class="fz-dialog__footer">
			<button type="button" class="fz-btn" commandfor="<?php echo $id; ?>-rename" command="close">Cancel</button>
			<button type="submit" class="fz-btn fz-btn--primary">Rename</button>
		</div>
	</form>
</dialog>
<dialog class="fz-dialog" id="<?php echo $id; ?>-rebase" aria-labelledby="<?php echo $id; ?>-rebase-title">
	<form method="post" action="<?php echo formulize_looksH($pageUrl); ?>">
		<?php echo formulize_looksFormFields('rebase', $theme, $key); ?>
		<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="<?php echo $id; ?>-rebase-title">Change what <?php echo $name; ?> is based on</h2></div>
		<div class="fz-dialog__body">
			<div class="fz-field">
				<label class="fz-field__label" for="<?php echo $id; ?>-rebase-base">Based on</label>
				<div class="fz-field__body"><select class="fz-select" id="<?php echo $id; ?>-rebase-base" name="base"><?php echo formulize_looksBaseOptions($tree, $looks, $theme, $look['base'], array_merge(array($key), formulize_appearanceLookDescendants($looks, $key))); ?></select></div>
			</div>
			<p><?php echo $name; ?> keeps its own changes<?php echo $changes ? ' (' . formulize_looksH(implode(', ', $changes)) . ')' : ''; ?>, and takes everything else from its new base. Any of its changes that are the same as the new base's are dropped.</p>
			<?php if ($children) { ?><p class="fz-text-muted">The looks based on <?php echo $name; ?> move with it.</p><?php } ?>
		</div>
		<div class="fz-dialog__footer">
			<button type="button" class="fz-btn" commandfor="<?php echo $id; ?>-rebase" command="close">Cancel</button>
			<button type="submit" class="fz-btn fz-btn--primary">Change base</button>
		</div>
	</form>
</dialog>
<?php if ($key !== $siteLook) { ?>
<dialog class="fz-dialog" id="<?php echo $id; ?>-site" aria-labelledby="<?php echo $id; ?>-site-title">
	<form method="post" action="<?php echo formulize_looksH($pageUrl); ?>">
		<?php echo formulize_looksFormFields('site', $theme, $key); ?>
		<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="<?php echo $id; ?>-site-title">Set <?php echo $name; ?> as the site look?</h2></div>
		<div class="fz-dialog__body">
			<p><?php echo $isSiteTheme ? 'Every page will use ' . $name . ', except screens set to another look.' : 'When ' . formulize_looksH($theme) . ' is the site\'s theme, every page will use ' . $name . ', except screens set to another look.'; ?> It is <?php echo formulize_looksH($looks[$siteLook]['name']); ?> now.</p>
		</div>
		<div class="fz-dialog__footer">
			<button type="button" class="fz-btn" commandfor="<?php echo $id; ?>-site" command="close">Cancel</button>
			<button type="submit" class="fz-btn fz-btn--primary">Set as site look</button>
		</div>
	</form>
</dialog>
<?php } ?>
<dialog class="fz-dialog" id="<?php echo $id; ?>-delete" aria-labelledby="<?php echo $id; ?>-delete-title">
	<?php if ($key === $siteLook) { ?>
	<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="<?php echo $id; ?>-delete-title"><?php echo $name; ?> can't be deleted</h2></div>
	<div class="fz-dialog__body"><p><?php echo $name; ?> is the <?php echo $isSiteTheme ? 'site look' : 'site look for ' . formulize_looksH($theme); ?>. Change the site look first, then delete it.</p></div>
	<div class="fz-dialog__footer">
		<?php if ($looksCount > 1) { ?><button type="button" class="fz-btn" commandfor="looks-site" command="show-modal">Change site look</button><?php } ?>
		<button type="button" class="fz-btn fz-btn--primary" commandfor="<?php echo $id; ?>-delete" command="close">Close</button>
	</div>
	<?php } elseif ($children) { ?>
	<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="<?php echo $id; ?>-delete-title"><?php echo $name; ?> can't be deleted yet</h2></div>
	<div class="fz-dialog__body">
		<p><?php echo count($children) == 1 ? '1 look is' : count($children) . ' looks are'; ?> based on <?php echo $name; ?>:</p>
		<ul><?php foreach ($children as $child) { echo '<li>' . formulize_looksH($looks[$child]['name']) . '</li>'; } ?></ul>
		<p>Change what <?php echo count($children) == 1 ? 'it is' : 'they are'; ?> based on, or delete <?php echo count($children) == 1 ? 'it' : 'them'; ?>, first.</p>
	</div>
	<div class="fz-dialog__footer"><button type="button" class="fz-btn fz-btn--primary" commandfor="<?php echo $id; ?>-delete" command="close">Close</button></div>
	<?php } else { ?>
	<form method="post" action="<?php echo formulize_looksH($pageUrl); ?>">
		<?php echo formulize_looksFormFields('delete', $theme, $key); ?>
		<div class="fz-dialog__header"><h2 class="fz-dialog__title" id="<?php echo $id; ?>-delete-title">Delete <?php echo $name; ?>?</h2></div>
		<div class="fz-dialog__body">
			<?php if ($screens) { ?>
			<p>These screens use <?php echo $name; ?>, and will use the site look, <?php echo formulize_looksH($looks[$siteLook]['name']); ?>, instead:</p>
			<ul><?php foreach ($screens as $screen) { echo '<li>' . formulize_looksH($screen['title']) . '</li>'; } ?></ul>
			<?php } else { ?>
			<p>Nothing uses <?php echo $name; ?>.</p>
			<?php } ?>
			<p class="fz-text-muted">This can't be undone.</p>
		</div>
		<div class="fz-dialog__footer">
			<button type="button" class="fz-btn" commandfor="<?php echo $id; ?>-delete" command="close">Cancel</button>
			<button type="submit" class="fz-btn fz-btn--danger">Delete look</button>
		</div>
	</form>
	<?php } ?>
</dialog>
<?php } ?>

<script>
// "Create look based on this" opens the same dialog as Create new look, with that
// look chosen as the base. The rest of the page needs no script of its own.
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
include XOOPS_ROOT_PATH . '/footer.php';
