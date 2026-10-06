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

// Appearance admin page: where a theme's appearance is changed, in the appearance
// editor (appearance_editor.php), which this page opens. The editor has the
// theme's looks, the one applied to the site, its logo, colours, fonts and page
// width, and, in advanced mode, every part of it; this page says which theme is
// being looked at, which look is applied, and whether the theme's appearance
// folder can be written, which nothing can be saved without.
//
// The theme picker works the same way as the one in the Theme Editor
// (admin/themeeditor.php): it lists the installed themes and starts on the
// site's active theme, and switching it reloads this page with ?theme=.

if(!defined('_FORMULIZE_UI_PHP_INCLUDED')) { exit(); }

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/appearance.php";

$themes = formulize_getAppearanceThemes();
$selectedTheme = formulize_resolveAppearanceTheme(isset($_GET['theme']) ? $_GET['theme'] : '');
$settings = formulize_getAppearanceSettings($selectedTheme);
$looks = formulize_getAppearanceLooks($selectedTheme);
$applied = ($settings['appearance_look'] !== '' AND isset($looks[$settings['appearance_look']])) ? $settings['appearance_look'] : 'default';

$adminPage['home_tabs'] = getHomeTabs('appearance');
$adminPage['themes'] = $themes;
$adminPage['selected_theme'] = $selectedTheme;
$adminPage['active_theme'] = formulize_getDefaultAppearanceTheme();
$adminPage['theme_supports_appearance'] = formulize_themeSupportsAppearance($selectedTheme);
$adminPage['appearance_dir'] = formulize_getAppearanceDir($selectedTheme);
$adminPage['appearance_css'] = formulize_getAppearanceCssPath($selectedTheme);
// Warn up front if this theme's appearance folder can't be written, rather than
// letting the admin make changes in the editor and only then discover the save
// can't land.
$adminPage['appearance_dir_writable'] = formulize_appearanceDirIsWritable($selectedTheme);
$adminPage['editorUrl'] = formulize_getAppearanceEditorUrl($selectedTheme);
$adminPage['lookName'] = isset($looks[$applied]) ? $looks[$applied]['name'] : 'Default';
$adminPage['modeName'] = formulize_appearanceThemeHasAdvancedMode($selectedTheme) ? ($settings['appearance_mode'] == 'advanced' ? 'Advanced' : 'Simple') : '';
$adminPage['template'] = "db:admin/appearance.html";

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = "Appearance";
