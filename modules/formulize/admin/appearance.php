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

if(isset($_POST['appearance_save']) OR isset($_POST['appearance_reset'])) {

    // build the settings to write: a reset writes the defaults, and a save what the
    // form asks for. The logo and the favicon are files, uploaded or removed here.
    $submitted = isset($_POST['appearance_save'])
        ? formulize_appearanceSubmittedSettings($_POST, $settings, $selectedTheme, $errors)
        : formulize_defaultAppearanceSettings();
    $submitted = formulize_saveAppearanceUploads($submitted, $settings, $selectedTheme, isset($_POST['appearance_reset']), $errors);

    // writing the stylesheet is the save: if it can't be written, nothing was saved,
    // so say that rather than reporting success the settings didn't survive
    if(formulize_regenerateAppearanceCss($submitted, $selectedTheme)) {
        $saved = (count($errors) == 0);
    } else {
        $errors[] = "Nothing was saved. The " . $selectedTheme . " theme's settings are kept in its generated stylesheet, and that file could not be written to " . formulize_getAppearanceDir($selectedTheme) . ". Make that folder writable by the web server and save again.";
    }

    // show what was submitted either way, so a failed save doesn't mean retyping it
    $settings = formulize_sanitizeAppearanceSettings($submitted, $selectedTheme);
}

$colours = array();
foreach($colourMap as $key => $colour) {
    $colours[] = array(
        'key' => $key,
        'label' => $colour['label'],
        'description' => $colour['description'],
        'default' => $colour['default'],
        'value' => $settings['appearance_' . $key] ? $settings['appearance_' . $key] : $colour['default'],
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

$sizes = array();
foreach(formulize_appearanceSizePresets() as $key => $label) {
    $sizes[] = array('key' => $key, 'label' => $label);
}

// The logo can still be sitting in the legacy uploads/appearance folder on a site
// that had one uploaded before appearance files moved into the theme folders, so the
// shared lookup (which checks both places) builds the preview URLs. These are built
// from $settings rather than from the active theme's helpers, because this page edits
// whichever theme the picker is on, not the one it is being rendered with.
$uploadUrls = array();
foreach(array_keys(formulize_appearanceUploads()) as $uploadSetting) {
    $uploadUrls[$uploadSetting] = formulize_getAppearanceFileUrl(
        formulize_locateAppearanceFile($settings[$uploadSetting], $selectedTheme), $selectedTheme);
}

// Warn up front if this theme's appearance folder can't be written, rather than
// letting the admin fill the form in and only then discover the save can't land.
// The stylesheet is where the settings are kept, so an unwritable folder means
// nothing can be saved for this theme at all.
$appearanceDirWritable = formulize_appearanceDirIsWritable($selectedTheme);

$adminPage['home_tabs'] = getHomeTabs('appearance');
$adminPage['colours'] = $colours;
$adminPage['fonts'] = $fonts;
$adminPage['currentFont'] = $settings['appearance_font'] ? $settings['appearance_font'] : 'geist';
$adminPage['currentCustomFont'] = $settings['appearance_customfont'];
$adminPage['headingFonts'] = $headingFonts;
$adminPage['currentHeadingFont'] = $settings['appearance_headingfont'] ? $settings['appearance_headingfont'] : 'geist';
$adminPage['currentHeadingCustomFont'] = $settings['appearance_headingcustomfont'];
$adminPage['fontStacksJson'] = json_encode($fontStacks);
$adminPage['sizes'] = $sizes;
$adminPage['currentSize'] = $settings['appearance_size'];
$adminPage['themeUsesSizes'] = formulize_appearanceThemeUsesSizes($selectedTheme);
// the advanced editor, for a theme built on the component tokens that provides
// sample screens to preview them on; and the parts that have settings of their own
// there, which the settings on this page don't change
$adminPage['editorUrl'] = formulize_getAppearanceEditorUrl($selectedTheme);
$adminPage['overrideParts'] = implode(', ', formulize_appearanceOverrideParts($settings));
// Content width: full width, or a maximum width in pixels, for a theme that lays
// its content out to one (formulize_appearanceThemeUsesContentWidth). The width box
// starts at the default width while the content is full width.
$contentWidthLimits = formulize_appearanceContentWidthLimits();
$adminPage['themeUsesContentWidth'] = formulize_appearanceThemeUsesContentWidth($selectedTheme);
$adminPage['contentWidthMax'] = ($settings['appearance_contentwidth'] !== '');
$adminPage['contentWidth'] = $settings['appearance_contentwidth'] !== '' ? $settings['appearance_contentwidth'] : $contentWidthLimits['default'];
$adminPage['contentWidthLimits'] = $contentWidthLimits;
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
