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

// Appearance admin page: colours, font, and logo for the end-user UI, edited
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

$colourMap = formulize_appearanceColourMap();
$fontMap = formulize_appearanceFontMap();
$saved = false;
$errors = array();

// Which theme's settings are being edited: whatever was picked in the theme select
// (the form posts it back so a save stays on the same theme), falling back to the
// site's active theme. Anything not actually installed resolves back to that too.
$themes = formulize_getAppearanceThemes();
$requestedTheme = isset($_POST['appearance_theme']) ? $_POST['appearance_theme'] : (isset($_GET['theme']) ? $_GET['theme'] : '');
$selectedTheme = formulize_resolveAppearanceTheme($requestedTheme);

// the settings as they stand, read out of the theme's generated stylesheet
$settings = formulize_getAppearanceSettings($selectedTheme);

// delete the logo file a theme is using, when the logo is being removed or replaced.
// Only a file in the theme's own appearance folder is deleted: a logo still sitting in
// the legacy uploads/appearance folder predates per-theme settings and can be shared
// with another theme, so it is left alone and simply stops being referenced.
function formulize_deleteAppearanceLogoFile($logoFile, $theme) {
    $path = formulize_locateAppearanceFile($logoFile, $theme);
    if($path AND strpos($path, formulize_getAppearanceDir($theme) . '/') === 0) {
        unlink($path);
    }
}

if(isset($_POST['appearance_save']) OR isset($_POST['appearance_reset'])) {

    // build the settings to write, starting from the defaults, which is also exactly
    // what a reset writes
    $submitted = formulize_defaultAppearanceSettings();

    if(isset($_POST['appearance_save'])) {
        foreach($colourMap as $key => $colour) {
            $submitted['appearance_' . $key] = isset($_POST['appearance_' . $key]) ? $_POST['appearance_' . $key] : '';
        }
        $submitted['appearance_font'] = isset($_POST['appearance_font']) ? $_POST['appearance_font'] : '';
        $submitted['appearance_customfont'] = isset($_POST['appearance_customfont']) ? $_POST['appearance_customfont'] : '';
        $submitted['appearance_logo'] = $settings['appearance_logo']; // kept unless removed or replaced below
        if($submitted['appearance_font'] == 'custom' AND !formulize_sanitizeAppearanceFontFamily($submitted['appearance_customfont'])) {
            $errors[] = "Please enter a Google Font name to use a custom font. The default font has been kept.";
        }
    }

    // The logo is an image, so it can't be a value in the stylesheet the way the
    // colours and the font are. The file is kept beside the stylesheet in the theme's
    // appearance folder, and the stylesheet records which file is in use, so the
    // stylesheet is still the one place the settings are read from.
    $newLogo = '';
    if(isset($_FILES['appearance_logo_file']) AND $_FILES['appearance_logo_file']['error'] == UPLOAD_ERR_OK) {
        $allowedTypes = array(
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            'image/webp' => 'webp',
        );
        $mimeType = mime_content_type($_FILES['appearance_logo_file']['tmp_name']);
        if(isset($allowedTypes[$mimeType])) {
            $fileName = 'formulize-appearance-logo-' . time() . '.' . $allowedTypes[$mimeType];
            $appearanceDir = formulize_prepareAppearanceDir($selectedTheme);
            if($appearanceDir AND move_uploaded_file($_FILES['appearance_logo_file']['tmp_name'], $appearanceDir . '/' . $fileName)) {
                $newLogo = $fileName;
            } else {
                $errors[] = "Could not move the uploaded logo into " . formulize_getAppearanceDir($selectedTheme) . ". Check the folder permissions.";
            }
        } else {
            $errors[] = "The logo must be a PNG, JPEG, GIF, SVG, or WebP image.";
        }
    }
    // the old file only goes when there is something to put in its place, or the admin
    // asked for it to go, so a rejected upload leaves the current logo alone
    if($newLogo OR isset($_POST['appearance_reset']) OR isset($_POST['appearance_logo_remove'])) {
        formulize_deleteAppearanceLogoFile($settings['appearance_logo'], $selectedTheme);
        $submitted['appearance_logo'] = $newLogo;
    }

    // writing the stylesheet is the save: if it can't be written, nothing was saved,
    // so say that rather than reporting success the settings didn't survive
    if(formulize_regenerateAppearanceCss($submitted, $selectedTheme)) {
        $saved = (count($errors) == 0);
    } else {
        $errors[] = "Nothing was saved. The " . $selectedTheme . " theme's settings are kept in its generated stylesheet, and that file could not be written to " . formulize_getAppearanceDir($selectedTheme) . ". Make that folder writable by the web server and save again.";
    }

    // show what was submitted either way, so a failed save doesn't mean retyping it
    $settings = formulize_sanitizeAppearanceSettings($submitted);
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

$fonts = array();
foreach($fontMap as $key => $font) {
    $fonts[$key] = $font['label'];
}

// the logo can still be sitting in the legacy uploads/appearance folder on a site
// that had one uploaded before appearance files moved into the theme folders, so
// the shared lookup (which checks both places) builds the preview URL
$logoPath = formulize_locateAppearanceFile($settings['appearance_logo'], $selectedTheme);
$logoUrl = '';
if($logoPath) {
    $logoBase = (strpos($logoPath, formulize_getLegacyAppearanceDir() . '/') === 0)
        ? formulize_getLegacyAppearanceUrl()
        : formulize_getAppearanceUrl($selectedTheme);
    $logoUrl = $logoBase . '/' . rawurlencode(basename($logoPath)) . '?v=' . filemtime($logoPath);
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
$adminPage['logoUrl'] = $logoUrl;
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
