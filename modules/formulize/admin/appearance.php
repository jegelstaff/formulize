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

// Appearance admin page: site-wide colours, font, and logo for the end-user UI

if(!defined('_FORMULIZE_UI_PHP_INCLUDED')) { exit(); }

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/appearance.php";

$config_handler = xoops_gethandler('config');
$formulizeModId = getFormulizeModId();

// gather the appearance config item objects, keyed by name
$criteria = new CriteriaCompo(new Criteria('conf_modid', $formulizeModId));
$configItems = array();
foreach($config_handler->getConfigs($criteria) as $configItem) {
    if(strstr($configItem->getVar('conf_name'), 'appearance_')) {
        $configItems[$configItem->getVar('conf_name')] = $configItem;
    }
}

$colourMap = formulize_appearanceColourMap();
$fontMap = formulize_appearanceFontMap();
$saved = false;
$errors = array();

// helper to write one appearance config value, if the config item exists
// (items only exist once the module has been updated in the system admin)
function formulize_saveAppearanceConfig($name, $value, &$configItems, &$errors) {
    global $config_handler;
    if(!isset($configItems[$name])) {
        $errors[] = "Could not save setting '$name'. The Formulize module may need to be updated in the system admin.";
        return;
    }
    $configItems[$name]->setConfValueForInput($value);
    if(!$config_handler->insertConfig($configItems[$name])) {
        $errors[] = "Could not save setting '$name' to the database.";
    }
}

if(isset($_POST['appearance_save']) OR isset($_POST['appearance_reset'])) {

    if(isset($_POST['appearance_reset'])) {

        // reset everything to defaults, including removing any uploaded logo
        if(isset($configItems['appearance_logo'])) {
            $oldLogo = basename($configItems['appearance_logo']->getConfValueForOutput());
            if($oldLogo AND file_exists(formulize_getAppearanceLogoDir() . '/' . $oldLogo)) {
                unlink(formulize_getAppearanceLogoDir() . '/' . $oldLogo);
            }
        }
        foreach(formulize_appearanceConfigNames() as $name) {
            formulize_saveAppearanceConfig($name, '', $configItems, $errors);
        }

    } else {

        // colours
        foreach($colourMap as $key => $colour) {
            $value = formulize_sanitizeAppearanceColour(isset($_POST['appearance_' . $key]) ? $_POST['appearance_' . $key] : '');
            // store nothing when the user has kept the default, so theme defaults can evolve
            if($value == $colour['default']) {
                $value = '';
            }
            formulize_saveAppearanceConfig('appearance_' . $key, $value, $configItems, $errors);
        }

        // font
        $font = (isset($_POST['appearance_font']) AND isset($fontMap[$_POST['appearance_font']])) ? $_POST['appearance_font'] : 'geist';
        $customFont = isset($_POST['appearance_customfont']) ? trim(preg_replace('/[^a-zA-Z0-9 ]/', '', $_POST['appearance_customfont'])) : '';
        if($font == 'custom' AND !$customFont) {
            $font = 'geist';
            $errors[] = "Please enter a Google Font name to use a custom font. The default font has been kept.";
        }
        formulize_saveAppearanceConfig('appearance_font', ($font == 'geist') ? '' : $font, $configItems, $errors);
        formulize_saveAppearanceConfig('appearance_customfont', ($font == 'custom') ? $customFont : '', $configItems, $errors);

        // logo: remove and/or replace
        $currentLogo = isset($configItems['appearance_logo']) ? basename($configItems['appearance_logo']->getConfValueForOutput()) : '';
        $newUpload = (isset($_FILES['appearance_logo_file']) AND $_FILES['appearance_logo_file']['error'] == UPLOAD_ERR_OK);
        if((isset($_POST['appearance_logo_remove']) OR $newUpload) AND $currentLogo) {
            if(file_exists(formulize_getAppearanceLogoDir() . '/' . $currentLogo)) {
                unlink(formulize_getAppearanceLogoDir() . '/' . $currentLogo);
            }
            formulize_saveAppearanceConfig('appearance_logo', '', $configItems, $errors);
        }
        if($newUpload) {
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
                if(!is_dir(formulize_getAppearanceLogoDir())) {
                    mkdir(formulize_getAppearanceLogoDir(), 0755, true);
                }
                if(move_uploaded_file($_FILES['appearance_logo_file']['tmp_name'], formulize_getAppearanceLogoDir() . '/' . $fileName)) {
                    formulize_saveAppearanceConfig('appearance_logo', $fileName, $configItems, $errors);
                } else {
                    $errors[] = "Could not move the uploaded logo into the logo folder in the trust path. Check the folder permissions.";
                }
            } else {
                $errors[] = "The logo must be a PNG, JPEG, GIF, SVG, or WebP image.";
            }
        }
    }

    $saved = (count($errors) == 0);
}

// prepare current values for the template, reading from the config objects so
// values saved above are reflected immediately
$currentValues = array();
foreach(formulize_appearanceConfigNames() as $name) {
    $currentValues[$name] = isset($configItems[$name]) ? trim($configItems[$name]->getConfValueForOutput()) : '';
}

$colours = array();
foreach($colourMap as $key => $colour) {
    $colours[] = array(
        'key' => $key,
        'label' => $colour['label'],
        'description' => $colour['description'],
        'default' => $colour['default'],
        'value' => $currentValues['appearance_' . $key] ? $currentValues['appearance_' . $key] : $colour['default'],
    );
}

$fonts = array();
foreach($fontMap as $key => $font) {
    $fonts[$key] = $font['label'];
}

$logoFile = basename($currentValues['appearance_logo']);
$logoPath = formulize_getAppearanceLogoDir() . '/' . $logoFile;
$logoUrl = ($logoFile AND file_exists($logoPath)) ? XOOPS_URL . '/modules/formulize/download.php?file=appearance/' . rawurlencode($logoFile) . '&inline=1&v=' . filemtime($logoPath) : '';

$adminPage['colours'] = $colours;
$adminPage['fonts'] = $fonts;
$adminPage['currentFont'] = $currentValues['appearance_font'] ? $currentValues['appearance_font'] : 'geist';
$adminPage['currentCustomFont'] = $currentValues['appearance_customfont'];
$adminPage['logoUrl'] = $logoUrl;
$adminPage['saved'] = $saved;
$adminPage['errors'] = $errors;
$adminPage['configsMissing'] = !isset($configItems['appearance_primary']);
$adminPage['template'] = "db:admin/appearance.html";

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = "Appearance";
