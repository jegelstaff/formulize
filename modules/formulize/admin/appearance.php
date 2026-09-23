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

// delete an uploaded appearance file a theme is using, when it is being removed or
// replaced. Only a file in the theme's own appearance folder is deleted: a file still
// sitting in the legacy uploads/appearance folder predates per-theme settings and can be
// shared with another theme, so it is left alone and simply stops being referenced.
function formulize_deleteAppearanceUploadedFile($file, $theme) {
    $path = formulize_locateAppearanceFile($file, $theme);
    if($path AND strpos($path, formulize_getAppearanceDir($theme) . '/') === 0) {
        unlink($path);
    }
}

// The images that can be uploaded on this page: the logo in the site header, and the
// favicon in the browser tab. They are handled identically - the file goes in the
// theme's appearance folder and the stylesheet records which file is in use - so each
// one is just a description of its own form fields and the image types it accepts.
// The favicon also takes .ico, which browsers only ever want for a favicon.
function formulize_appearanceUploads() {
    $imageTypes = array(
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
        'image/webp' => 'webp',
    );
    return array(
        'appearance_logo' => array(
            'noun' => 'logo',
            'filePrefix' => 'formulize-appearance-logo-',
            'types' => $imageTypes,
            'typesLabel' => 'PNG, JPEG, GIF, SVG, or WebP',
        ),
        'appearance_favicon' => array(
            'noun' => 'favicon',
            'filePrefix' => 'formulize-appearance-favicon-',
            'types' => $imageTypes + array(
                'image/vnd.microsoft.icon' => 'ico',
                'image/x-icon' => 'ico',
            ),
            'typesLabel' => 'PNG, ICO, SVG, GIF, JPEG, or WebP',
        ),
    );
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
        $submitted['appearance_headingfont'] = isset($_POST['appearance_headingfont']) ? $_POST['appearance_headingfont'] : '';
        $submitted['appearance_headingcustomfont'] = isset($_POST['appearance_headingcustomfont']) ? $_POST['appearance_headingcustomfont'] : '';
        $submitted['appearance_fontsize'] = isset($_POST['appearance_fontsize']) ? $_POST['appearance_fontsize'] : '';
        foreach(array_keys(formulize_appearanceUploads()) as $uploadSetting) {
            $submitted[$uploadSetting] = $settings[$uploadSetting]; // kept unless removed or replaced below
        }
        if($submitted['appearance_font'] == 'custom' AND !formulize_sanitizeAppearanceFontFamily($submitted['appearance_customfont'])) {
            $errors[] = "Please enter a Google Font name to use a custom font. The default font has been kept.";
        }
        if($submitted['appearance_headingfont'] == 'custom' AND !formulize_sanitizeAppearanceFontFamily($submitted['appearance_headingcustomfont'])) {
            $errors[] = "Please enter a Google Font name to use a custom secondary font. Headings and labels have been left following the main font.";
        }
    }

    // The logo and the favicon are images, so they can't be values in the stylesheet the
    // way the colours and the font are. The files are kept beside the stylesheet in the
    // theme's appearance folder, and the stylesheet records which file is in use, so the
    // stylesheet is still the one place the settings are read from.
    foreach(formulize_appearanceUploads() as $uploadSetting => $upload) {
        $field = $uploadSetting . '_file';
        $newFile = '';
        if(isset($_FILES[$field]) AND $_FILES[$field]['error'] == UPLOAD_ERR_OK) {
            $mimeType = mime_content_type($_FILES[$field]['tmp_name']);
            if(isset($upload['types'][$mimeType])) {
                $fileName = $upload['filePrefix'] . time() . '.' . $upload['types'][$mimeType];
                $appearanceDir = formulize_prepareAppearanceDir($selectedTheme);
                if($appearanceDir AND move_uploaded_file($_FILES[$field]['tmp_name'], $appearanceDir . '/' . $fileName)) {
                    $newFile = $fileName;
                } else {
                    $errors[] = "Could not move the uploaded " . $upload['noun'] . " into " . formulize_getAppearanceDir($selectedTheme) . ". Check the folder permissions.";
                }
            } else {
                $errors[] = "The " . $upload['noun'] . " must be a " . $upload['typesLabel'] . " image.";
            }
        }
        // the old file only goes when there is something to put in its place, or the admin
        // asked for it to go, so a rejected upload leaves the current file alone
        if($newFile OR isset($_POST['appearance_reset']) OR isset($_POST[$uploadSetting . '_remove'])) {
            formulize_deleteAppearanceUploadedFile($settings[$uploadSetting], $selectedTheme);
            $submitted[$uploadSetting] = $newFile;
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

// What each choice actually renders as, for the preview. The default choice is the
// theme's own --fz-font-sans (Geist on Lyris, Poppins on Anari), not the font map's
// nominal Geist stack, so previewing "default" shows the theme being edited. 'custom'
// carries nothing: the browser builds it from whatever family name has been typed in.
$themeTokens = formulize_appearanceThemeTokens($selectedTheme);
$fontStacks = array();
foreach($fontMap as $key => $font) {
    if($key == 'custom') {
        $fontStacks[$key] = array('stack' => '', 'google' => '');
    } elseif($key == 'geist') {
        $fontStacks[$key] = array(
            'stack' => isset($themeTokens['--fz-font-sans']) ? $themeTokens['--fz-font-sans'] : $font['stack'],
            'google' => str_replace(' ', '+', formulize_appearanceThemeFontName($selectedTheme)) . ':wght@400;500;600;700',
        );
    } else {
        $fontStacks[$key] = array('stack' => $font['stack'], 'google' => $font['google'] ? $font['google'] : '');
    }
}

// The sizes on offer are the size the standard content text renders at, not the root
// font size underneath it, and they are the selected theme's: each theme sets its
// content text at a different step of its own scale. See the Text size group of
// functions in include/appearance.php for the translation between the two.
$fontSizes = array();
foreach(formulize_appearanceFontSizeMap($selectedTheme) as $size => $label) {
    $fontSizes[] = array('key' => $size, 'label' => $label);
}
$defaultFontSize = formulize_appearanceThemeContentSize($selectedTheme);

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
$adminPage['fontSizes'] = $fontSizes;
$adminPage['defaultFontSize'] = $defaultFontSize;
$adminPage['currentFontSize'] = $settings['appearance_fontsize'] ? $settings['appearance_fontsize'] : $defaultFontSize;
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
