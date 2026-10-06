<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) Formulize Project                        ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Project: Formulize                                                       ##
###############################################################################

// Appearance admin page: colours, font, logo, and favicon for the end-user UI, edited
// one theme at a time. The theme picker works the same way as the one in the
// Theme Editor (admin/themeeditor.php): it lists the installed themes and
// starts on the site's active theme, and switching it reloads this page with
// ?theme= so the form always shows the selected theme's own settings.
//
// A theme's settings live in the stylesheet generated for it, in that theme's
// own appearance folder. Saving means writing that file, and the form is filled
// in by reading it back, so there is one place the settings can be, and a theme
// with no generated stylesheet simply shows the defaults.

if(!defined('_FORMULIZE_UI_PHP_INCLUDED')) { exit(); }

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/appearance.php";

$saved = false;
$errors = array();

// Which theme's settings are being edited: whatever was picked in the theme select
// (the form posts it back so a save stays on the same theme), falling back to the
// site's active theme. Anything not actually installed resolves back to that too.
$themes = formulize_getAppearanceThemes();
$requestedTheme = isset($_POST['appearance_theme']) ? $_POST['appearance_theme'] : (isset($_GET['theme']) ? $_GET['theme'] : '');
$selectedTheme = formulize_resolveAppearanceTheme($requestedTheme);

// The colours and fonts on offer, with the defaults of the theme being edited, which
// is not necessarily the theme this admin page is being rendered with. Each theme
// declares its own palette and font, so what "default" means here follows the picker
// above rather than whatever the admin happens to be looking at the site in.
$colourMap = formulize_appearanceColourMap($selectedTheme);
$fontMap = formulize_appearanceFontMap($selectedTheme);
$headingFontMap = formulize_appearanceHeadingFontMap($selectedTheme);

// the settings as they stand, read out of the theme's generated stylesheet
$settings = formulize_getAppearanceSettings($selectedTheme);

// The settings the look applied to the site sets: the site has the look's value,
// so this page shows it, can't change it, and keeps the page's own value when it
// saves. They are changed in the advanced editor.
$locked = formulize_appearanceLockedSettings($settings, $selectedTheme);

if(isset($_POST['appearance_save']) OR isset($_POST['appearance_reset'])) {

    // build the settings to write: a reset writes the defaults, and a save what the
    // form asks for. The logo and the favicon are files, uploaded or removed here.
    $submitted = isset($_POST['appearance_save'])
        ? formulize_appearanceSubmittedSettings($_POST, $settings, $selectedTheme, $errors)
        : formulize_defaultAppearanceSettings();
    $submitted = formulize_saveAppearanceUploads($submitted, $settings, $selectedTheme, isset($_POST['appearance_reset']), $errors);
    if(isset($_POST['appearance_save'])) {
        foreach($locked['names'] as $name) {
            $submitted[$name] = $settings[$name];
        }
    }

    // writing the stylesheet is the save: if it can't be written, nothing was saved,
    // so say that rather than reporting success the settings didn't survive
    if(formulize_regenerateAppearanceCss($submitted, $selectedTheme)) {
        $saved = (count($errors) == 0);
    } else {
        $errors[] = "Nothing was saved. The " . $selectedTheme . " theme's settings are kept in its generated stylesheet, and that file could not be written to " . formulize_getAppearanceDir($selectedTheme) . ". Make that folder writable by the web server and save again.";
    }

    // show what was submitted either way, so a failed save doesn't mean retyping it
    $settings = formulize_sanitizeAppearanceSettings($submitted, $selectedTheme);
    $locked = formulize_appearanceLockedSettings($settings, $selectedTheme);
}

// What the page shows: its own settings, and for those the applied look sets, the
// look's values, which are what the site has.
$shown = $settings;
$effective = formulize_getAppearanceEffectiveSettings($selectedTheme, $settings);
foreach($locked['names'] as $name) {
    $shown[$name] = $effective[$name];
}
$isLocked = function ($names) use ($locked) {
    return count(array_intersect((array) $names, $locked['names'])) > 0;
};

$colours = array();
foreach($colourMap as $key => $colour) {
    $colours[] = array(
        'key' => $key,
        'label' => $colour['label'],
        'description' => $colour['description'],
        'default' => $colour['default'],
        'value' => $shown['appearance_' . $key] ? $shown['appearance_' . $key] : $colour['default'],
        'locked' => $isLocked('appearance_' . $key),
    );
}

// The pickers are rendered from these, and the live preview beside each one is driven
// by $fontStacks below, so what the preview shows is the same font-family value the
// save would write rather than a second list that could drift from this one.
$fonts = array();
foreach($fontMap as $key => $font) {
    $fonts[] = array('key' => $key, 'label' => $font['label']);
}
$headingFonts = array();
foreach($headingFontMap as $key => $font) {
    $headingFonts[] = array('key' => $key, 'label' => $font['label']);
}

// What each choice actually renders as, for the preview: see
// formulize_appearanceFontPreviewStacks().
$fontStacks = formulize_appearanceFontPreviewStacks($selectedTheme);

// The looks the site can be given: Default, which is the settings on this page as
// they are, the other looks that come with Formulize, and the looks made in the
// advanced editor, each of which changes the settings on this page.
$looks = array();
foreach(formulize_getAppearanceLooks($selectedTheme) as $key => $look) {
    $looks[] = array('key' => ($key == 'default' ? '' : $key), 'label' => $look['name'], 'description' => $look['description'], 'builtin' => $look['builtin']);
}

// The logo can still be sitting in the legacy uploads/appearance folder on a site
// that had one uploaded before appearance files moved into the theme folders, so the
// shared lookup (which checks both places) builds the preview URLs. These are built
// from $settings rather than from the active theme's helpers, because this page edits
// whichever theme the picker is on, not the one it is being rendered with.
$uploadUrls = array();
foreach(array_keys(formulize_appearanceUploads()) as $uploadSetting) {
    $uploadUrls[$uploadSetting] = formulize_getAppearanceFileUrl(
        formulize_locateAppearanceFile($shown[$uploadSetting], $selectedTheme), $selectedTheme);
}

// Warn up front if this theme's appearance folder can't be written, rather than
// letting the admin fill the form in and only then discover the save can't land.
// The stylesheet is where the settings are kept, so an unwritable folder means
// nothing can be saved for this theme at all.
$appearanceDirWritable = formulize_appearanceDirIsWritable($selectedTheme);

$adminPage['home_tabs'] = getHomeTabs('appearance');
$adminPage['colours'] = $colours;
$adminPage['fonts'] = $fonts;
$adminPage['currentFont'] = $shown['appearance_font'] ? $shown['appearance_font'] : 'geist';
$adminPage['currentCustomFont'] = $shown['appearance_customfont'];
$adminPage['headingFonts'] = $headingFonts;
$adminPage['currentHeadingFont'] = $shown['appearance_headingfont'] ? $shown['appearance_headingfont'] : 'geist';
$adminPage['currentHeadingCustomFont'] = $shown['appearance_headingcustomfont'];
// which of the page's settings the applied look sets, and where to change them
$adminPage['lockedBy'] = $locked['name'];
$adminPage['lockedUrl'] = formulize_getAppearanceEditorUrl($selectedTheme) . '&look=' . urlencode($locked['look']);
$adminPage['anyLocked'] = count($locked['names']) > 0;
$adminPage['lockedFont'] = $isLocked(array('appearance_font', 'appearance_customfont'));
$adminPage['lockedHeadingFont'] = $isLocked(array('appearance_headingfont', 'appearance_headingcustomfont'));
$adminPage['lockedWidth'] = $isLocked('appearance_contentwidth');
$adminPage['lockedLogo'] = $isLocked('appearance_logo');
$adminPage['lockedFavicon'] = $isLocked('appearance_favicon');
$adminPage['fontStacksJson'] = json_encode($fontStacks);
$adminPage['looks'] = $looks;
$adminPage['currentLook'] = $settings['appearance_look'];
$adminPage['offerLooks'] = (count($looks) > 1); // more than Default
$adminPage['madeLooks'] = (count(array_filter($looks, function ($look) { return !$look['builtin']; })) > 0);
$adminPage['themeUsesSizes'] = formulize_appearanceThemeUsesSizes($selectedTheme);
// the advanced editor, for a theme built on the component tokens that provides
// sample screens to preview them on
$adminPage['editorUrl'] = formulize_getAppearanceEditorUrl($selectedTheme);
// Page width, in the Size group: full width, or a maximum width in pixels, for a
// theme that lays its pages out to one (formulize_appearanceThemeUsesContentWidth).
// The width box starts at the default width while the page is at full width.
$contentWidthLimits = formulize_appearanceContentWidthLimits();
$contentWidth = formulize_appearanceContentWidth($shown, $selectedTheme);
$contentWidthDefault = formulize_appearanceContentWidthDefault($selectedTheme);
$adminPage['themeUsesContentWidth'] = formulize_appearanceThemeUsesContentWidth($selectedTheme);
$adminPage['contentWidthMax'] = ($contentWidth != 'full');
$adminPage['contentWidth'] = $contentWidth != 'full' ? $contentWidth : $contentWidthLimits['default'];
$adminPage['contentWidthLimits'] = $contentWidthLimits;
$adminPage['contentWidthDefault'] = $contentWidthDefault == 'full' ? 'full width' : 'a maximum width of ' . $contentWidthDefault . ' pixels';
$adminPage['logoUrl'] = $uploadUrls['appearance_logo'];
$adminPage['faviconUrl'] = $uploadUrls['appearance_favicon'];
$adminPage['saved'] = $saved;
$adminPage['errors'] = $errors;
$adminPage['themes'] = $themes;
$adminPage['selected_theme'] = $selectedTheme;
$adminPage['active_theme'] = formulize_getDefaultAppearanceTheme();
$adminPage['theme_supports_appearance'] = formulize_themeSupportsAppearance($selectedTheme);
$adminPage['appearance_dir'] = formulize_getAppearanceDir($selectedTheme);
$adminPage['appearance_css'] = formulize_getAppearanceCssPath($selectedTheme);
$adminPage['appearance_dir_writable'] = $appearanceDirWritable;
$adminPage['template'] = "db:admin/appearance.html";

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = "Appearance";
