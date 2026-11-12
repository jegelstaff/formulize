<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) Formulize Incorporated                   ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
###############################################################################

// The site's theme settings: reading them as they are stored, and changing them.

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

/**
 * One of the site's theme settings, as it is stored on Admin > Appearance > Settings. Not
 * $xoopsConfig['theme_set'], which include/common.php swaps for the admin theme when a webmaster is
 * signed in, and for a theme chosen in the session. The config handler's cached copy is untouched
 * by those swaps.
 *
 * @param string $setting 'theme_set' (the site's theme) or 'theme_admin_set' (the theme webmasters see)
 * @return string theme folder name, or '' if the setting has no value
 */
function formulize_storedSiteTheme($setting = 'theme_set') {
    $systemConfig = icms::$config->getConfigsByCat(ICMS_CONF);
    return isset($systemConfig[$setting]) ? (string) $systemConfig[$setting] : '';
}

/**
 * Change the site's theme, the theme webmasters see, or both, the way saving them on Admin >
 * Appearance > Settings does: the themes are added to the allowed themes, and when the site's theme
 * changes, every user's own theme and every screen's theme follow it (see
 * formulize_siteThemeChanged).
 *
 * @param string|null $siteTheme the new site theme (theme_set), or null to leave it
 * @param string|null $adminTheme the new theme for webmasters (theme_admin_set), or null to leave it
 * @return string '' when saved, or what went wrong
 */
function formulize_setSiteThemes($siteTheme, $adminTheme) {
    $themes = formulize_selectableThemesList();
    $changes = array();
    foreach (array('theme_set' => $siteTheme, 'theme_admin_set' => $adminTheme) as $setting => $theme) {
        if ($theme === null) {
            continue;
        }
        if (!isset($themes[$theme])) {
            return sprintf(_AM_FORMULIZE_SITE_THEME_NOT_INSTALLED, htmlspecialchars((string) $theme));
        }
        $changes[$setting] = $theme;
    }
    if (!$changes) {
        return '';
    }
    $config_handler = icms::handler('icms_config');

    // a theme has to be allowed for users to have it, and the core lists the default theme there too
    if ($allowedItem = formulize_resolveConfigItem('theme_set_allowed', 'system')) {
        $allowed = (array) $allowedItem->getConfValueForOutput();
        $newAllowed = array_values(array_unique(array_merge($allowed, array_values($changes))));
        if ($newAllowed != $allowed) {
            $allowedItem->setConfValueForInput($newAllowed);
            if (!$config_handler->insertConfig($allowedItem, true)) {
                return _AM_FORMULIZE_SITE_THEME_NOT_SAVED;
            }
        }
    }

    foreach ($changes as $setting => $theme) {
        if (!$item = formulize_resolveConfigItem($setting, 'system')) {
            return _AM_FORMULIZE_SITE_THEME_NOT_SAVED;
        }
        if ($item->getConfValueForOutput() === $theme) {
            continue;
        }
        $item->setConfValueForInput($theme);
        if (!$config_handler->insertConfig($item, true)) {
            return _AM_FORMULIZE_SITE_THEME_NOT_SAVED;
        }
        if ($setting == 'theme_set') {
            formulize_siteThemeChanged($theme);
        }
    }
    return '';
}

/**
 * What follows when the site's theme changes, wherever it is changed: every user's own theme is reset
 * to it, as the core has always done, and every screen's theme changes to it, so the Templates tab
 * opens on the templates the site is using, and "Customize the Templates" starts from that theme's
 * defaults. The admin theme alone changes neither, since webmasters often change it just to preview
 * a theme.
 *
 * Called by the system preferences save (modules/system/admin/preferences/main.php), which Admin >
 * Appearance > Settings saves through, and by formulize_setSiteThemes.
 *
 * @param string $theme the site's new theme
 * @return boolean whether everything was changed
 */
function formulize_siteThemeChanged($theme) {
    $member_handler = icms::handler('icms_member');
    $usersChanged = $member_handler->updateUsersByField('theme', $theme);
    $screen_handler = xoops_getmodulehandler('screen', 'formulize');
    $screensChanged = $screen_handler->setThemeForAllScreens($theme);
    return ($usersChanged AND $screensChanged);
}
