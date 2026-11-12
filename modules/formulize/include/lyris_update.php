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

// The questions about Lyris: which look it starts with, and which screens should take their custom templates along
// and start using Lyris's screen settings (admin/lyris_update_wizard.php draws them). They're asked in two places:
//
// - The 8.30 update, for a site that uses any theme but Lyris, which is also asked whether to switch. Nothing changes
//   until the update runs, and then patch 021 applies the answers, which admin/op.php hands it through
//   formulize_lyrisUpdateChoices().
// - Appearance > Settings, the first time the site's theme is changed to Lyris from any other theme. The answers are
//   applied by admin/save/lyris_switch_save.php, which switches the theme too, before the rest of the settings are
//   saved. Whether Lyris has been the site's theme is recorded by a file in its appearance folder (see
//   formulize_markThemeActivated), so this happens once.
//
// Either way, the theme changes last, since every screen's theme follows the site's, and what's offered depends on
// the theme each screen has before that.
//
// Included where it's needed: admin/op.php, admin/lyris_update_wizard.php, admin/lyris_switch_wizard.php, patch 021,
// the switch's save handler, and the System > Modules update, which sends Formulize's update to Formulize's own
// update page when there are questions to ask.

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/appearance.php';

// the dbversion that brings in Lyris as the standard theme, which patch 021 is gated on
define('FORMULIZE_LYRIS_UPDATE_DBVERSION', 36);

// the theme being offered
define('FORMULIZE_LYRIS_UPDATE_TO', 'Lyris');

/**
 * The theme the site is moving from: the site's theme, when it isn't Lyris. Most sites that update to 8.30 use Anari,
 * but it can be any theme.
 *
 * @return string the theme's folder name, or '' when the site uses Lyris already
 */
function formulize_lyrisUpdateFromTheme() {
    $theme = formulize_storedSiteTheme('theme_set');
    return $theme !== FORMULIZE_LYRIS_UPDATE_TO ? $theme : '';
}

/**
 * Whether the update should ask the person running it about Lyris: the update that brings Lyris in hasn't run yet,
 * the person is a webmaster, who is the only one who can change the site's theme, and the site uses another theme.
 *
 * @return boolean
 */
function formulize_lyrisUpdateWizardApplies() {
    global $xoopsUser;
    if (!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
        return false;
    }
    $themes = formulize_selectableThemesList();
    if (!isset($themes[FORMULIZE_LYRIS_UPDATE_TO])) {
        return false;
    }
    $module_handler = xoops_gethandler('module');
    $formulizeModule = $module_handler->getByDirname('formulize');
    if (!$formulizeModule OR $formulizeModule->getDBVersion() >= FORMULIZE_LYRIS_UPDATE_DBVERSION) {
        return false;
    }
    return formulize_lyrisUpdateFromTheme() !== '';
}

/**
 * Whether changing the site's theme to Lyris on Appearance > Settings should ask about Lyris first: the person is a
 * webmaster, Lyris is installed, the 8.30 update has run (until it does, it asks the questions itself), the site uses
 * another theme, and Lyris has never been the site's theme.
 *
 * @return boolean
 */
function formulize_lyrisSwitchWizardApplies() {
    global $xoopsUser;
    if (!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
        return false;
    }
    $themes = formulize_selectableThemesList();
    if (!isset($themes[FORMULIZE_LYRIS_UPDATE_TO])) {
        return false;
    }
    $module_handler = xoops_gethandler('module');
    $formulizeModule = $module_handler->getByDirname('formulize');
    if (!$formulizeModule OR $formulizeModule->getDBVersion() < FORMULIZE_LYRIS_UPDATE_DBVERSION) {
        return false;
    }
    return formulize_lyrisUpdateFromTheme() !== '' AND !formulize_themeHasBeenActivated(FORMULIZE_LYRIS_UPDATE_TO);
}

/**
 * The theme choices the person can make in the update.
 *
 * @return array choice => its label, escaped for HTML. 'switch' (the site and webmasters), 'try' (webmasters only,
 *               when they don't see Lyris already), and 'keep'. None when the site uses Lyris already.
 */
function formulize_lyrisUpdateThemeChoices() {
    $fromTheme = formulize_lyrisUpdateFromTheme();
    if ($fromTheme === '') {
        return array();
    }
    $choices = array('switch' => _AM_FORMULIZE_LYRIS_CHOICE_SWITCH);
    if (formulize_storedSiteTheme('theme_admin_set') !== FORMULIZE_LYRIS_UPDATE_TO) {
        $choices['try'] = _AM_FORMULIZE_LYRIS_CHOICE_TRY;
    }
    $choices['keep'] = sprintf(_AM_FORMULIZE_LYRIS_CHOICE_KEEP, htmlspecialchars($fromTheme));
    return $choices;
}

/**
 * The looks Lyris has, which one can start as the site look.
 *
 * @return array look key => array with 'name' and 'description', escaped for HTML
 */
function formulize_lyrisUpdateLooks() {
    $looks = array();
    foreach (formulize_getAppearanceLooks(FORMULIZE_LYRIS_UPDATE_TO) as $key => $look) {
        $looks[$key] = array('name' => htmlspecialchars($look['name']), 'description' => htmlspecialchars($look['description']));
    }
    return $looks;
}

/**
 * The logo and browser icon the site shows in the theme it's moving from, when they're the site's own, so Lyris can
 * show them too: the ones uploaded in that theme's Appearance settings, or else the files in the theme's own folder,
 * if they're not ones Formulize has shipped with Anari. For the icon, that's icons/icon.png, which browsers use, or
 * icons/favicon.ico. An image Lyris already has one of its own of is left out.
 *
 * @param string $fromTheme the theme the site is moving from
 * @return array the Appearance setting ('appearance_logo', 'appearance_favicon') => the path of its file, for each
 *               image to bring along
 */
function formulize_lyrisUpdateImagesToCarry($fromTheme) {
    static $cache = array();
    if (!isset($cache[$fromTheme])) {
        $cache[$fromTheme] = formulize_lyrisFindImagesToCarry($fromTheme);
    }
    return $cache[$fromTheme];
}

/**
 * Find the images for formulize_lyrisUpdateImagesToCarry.
 *
 * @param string $fromTheme the theme the site is moving from
 * @return array the Appearance setting => the path of its file
 */
function formulize_lyrisFindImagesToCarry($fromTheme) {
    $images = array();
    $themes = formulize_getAppearanceThemes();
    if (!isset($themes[$fromTheme]) OR !isset($themes[FORMULIZE_LYRIS_UPDATE_TO]) OR $fromTheme === FORMULIZE_LYRIS_UPDATE_TO) {
        return $images;
    }
    // every logo and icon Anari has shipped with, so a file that is one of them isn't taken for the site's own, in
    // Anari or in a theme copied from it
    $shipped = array(
        'appearance_logo' => array(
            '2ed8fd6dc4210048219f2376d6e6abfe1a7e3739',
            'ebd738b67db035fc9a1a2979335bec5c89f5a4aa',
            '9ddfdecccbf98016fa4e6011a408e4933524e8b5',
            'dbc2cf08ebef4b9f31663c536589931dddb4e68a',
        ),
        'appearance_favicon' => array(
            '2e44443b358dd7fc734078f1149b49280117095f',
            '58ac9dd0cb664b120e4f6a520152fc6d0c976485',
            '6ea545f548db45067ea413a974b0f5e78f085bae',
            'b2b16f5003c78b97365ee193d34f34a13288e2c9',
        ),
    );
    $themeFiles = array(
        'appearance_logo' => array('images/logo.png'),
        'appearance_favicon' => array('icons/icon.png', 'icons/favicon.ico'),
    );
    $lyrisSettings = formulize_getAppearanceLookSettings(FORMULIZE_LYRIS_UPDATE_TO, formulize_getAppearanceSiteLook(FORMULIZE_LYRIS_UPDATE_TO));
    // a theme without Appearance settings has none to read, and reading them would give it an appearance folder
    $fromSettings = formulize_themeSupportsAppearance($fromTheme) ? formulize_getAppearanceLookSettings($fromTheme, formulize_getAppearanceSiteLook($fromTheme)) : array();
    foreach ($themeFiles as $setting => $files) {
        if (!empty($lyrisSettings[$setting])) {
            continue;
        }
        if (!empty($fromSettings[$setting]) AND $path = formulize_locateAppearanceFile($fromSettings[$setting], $fromTheme)) {
            $images[$setting] = $path;
            continue;
        }
        foreach ($files as $file) {
            $path = ICMS_THEME_PATH . '/' . $fromTheme . '/' . $file;
            if (is_file($path) AND !in_array(sha1_file($path), $shipped[$setting])) {
                $images[$setting] = $path;
                break;
            }
        }
    }
    return $images;
}

/**
 * The label for one of Lyris's screen settings, for a type of screen, describing Lyris's value for it. A label is
 * looked for first for the value (_AM_FORMULIZE_LYRIS_SETTING_LISTOFENTRIES_EDITDESTINATION_DRAWER), then for the
 * setting, with the value put in its %s (_AM_FORMULIZE_LYRIS_SETTING_LISTOFENTRIES_ENTRIESPERPAGE). A setting with no
 * label isn't offered.
 *
 * @param string $type the screen type
 * @param string $property the setting
 * @param mixed $value Lyris's value for it
 * @return string the label, or '' when there's none
 */
function formulize_lyrisUpdateSettingLabel($type, $property, $value) {
    if (is_array($value)) {
        return '';
    }
    $name = '_AM_FORMULIZE_LYRIS_SETTING_' . strtoupper($type) . '_' . strtoupper($property);
    $valueName = $name . '_' . strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $value));
    if (defined($valueName)) {
        return constant($valueName);
    }
    if (defined($name)) {
        return sprintf(constant($name), htmlspecialchars((string) $value));
    }
    return '';
}

/**
 * The settings that go along with one that is offered, and are set to Lyris's values with it, so they're never
 * offered on their own: a form screen's column widths go with its number of columns.
 *
 * @param string $property the setting
 * @return array the settings that go with it
 */
function formulize_lyrisUpdateSettingCompanions($property) {
    return $property == 'displaycolumns' ? array('column1width', 'column2width') : array();
}

/**
 * Every screen, with what the questions need to know about it: its type, its theme, and where it is (its form, and
 * the application the form is in).
 *
 * @return array sid => array with 'title', 'type', 'theme', 'fid' and 'where' (application and form, escaped)
 */
function formulize_lyrisUpdateScreens() {
    static $screens = null;
    if ($screens !== null) {
        return $screens;
    }
    $screens = array();
    $screen_handler = xoops_getmodulehandler('screen', 'formulize');
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $application_handler = xoops_getmodulehandler('applications', 'formulize');
    $applicationOfForm = array();
    foreach ((array) $application_handler->getAllApplications() as $application) {
        foreach ((array) $application->getVar('forms') as $fid) {
            if (!isset($applicationOfForm[$fid])) {
                $applicationOfForm[$fid] = $application->getVar('name');
            }
        }
    }
    $formTitles = array();
    foreach ($screen_handler->getObjects() as $screen) {
        $fid = intval($screen->getVar('fid'));
        if (!isset($formTitles[$fid])) {
            $formObject = $form_handler->get($fid);
            $formTitles[$fid] = $formObject ? $formObject->getVar('title') : '';
        }
        $where = isset($applicationOfForm[$fid]) ? sprintf(_AM_FORMULIZE_LYRIS_SCREEN_WHERE, $applicationOfForm[$fid], $formTitles[$fid]) : $formTitles[$fid];
        $screens[$screen->getVar('sid')] = array(
            'title' => $screen->getVar('title'),
            'type' => $screen->getVar('type'),
            'theme' => $screen->getVar('theme', 'n'),
            'fid' => $fid,
            'where' => $where,
        );
    }
    uasort($screens, function ($a, $b) {
        $byWhere = strcasecmp(html_entity_decode($a['where']), html_entity_decode($b['where']));
        return $byWhere ? $byWhere : strcasecmp(html_entity_decode($a['title']), html_entity_decode($b['title']));
    });
    return $screens;
}

/**
 * The screens whose settings can be brought into line with Lyris's: for each type of screen, the settings Lyris
 * starts new screens with that have a label, and the screens that still have the value their own theme starts them
 * with, which isn't Lyris's. A screen whose setting was changed from its theme's value keeps it, and isn't offered.
 *
 * @return array type => array with 'columns' (setting => label) and 'screens' (sid => the settings offered to it)
 */
function formulize_lyrisUpdateSettingsOffered() {
    static $offered = null;
    if ($offered !== null) {
        return $offered;
    }
    $offered = array();
    if (!is_file(ICMS_THEME_PATH . '/' . FORMULIZE_LYRIS_UPDATE_TO . '/screen-defaults.json')) {
        return $offered;
    }
    $screen_handler = xoops_getmodulehandler('screen', 'formulize');
    foreach (formulize_lyrisUpdateScreens() as $sid => $screenInfo) {
        $type = $screenInfo['type'];
        if (!isset($offered[$type])) {
            $columns = array();
            foreach ($screen_handler->getThemeScreenDefaults($type, FORMULIZE_LYRIS_UPDATE_TO) as $property => $value) {
                if ($label = formulize_lyrisUpdateSettingLabel($type, $property, $value)) {
                    $columns[$property] = $label;
                }
            }
            $offered[$type] = array('columns' => $columns, 'screens' => array());
        }
        if (!$offered[$type]['columns'] OR !file_exists(XOOPS_ROOT_PATH . '/modules/formulize/class/' . $type . 'Screen.php')) {
            continue;
        }
        $typeHandler = xoops_getmodulehandler($type . 'Screen', 'formulize');
        if (!$screen = $typeHandler->get($sid)) {
            continue;
        }
        $lyrisDefaults = $screen_handler->getThemeScreenDefaults($type, FORMULIZE_LYRIS_UPDATE_TO);
        $ownDefaults = $screen_handler->getThemeScreenDefaults($type, $screenInfo['theme']);
        foreach (array_keys($offered[$type]['columns']) as $property) {
            $stored = (string) $screen->getVar($property, 'n');
            if ($stored !== (string) $lyrisDefaults[$property] AND isset($ownDefaults[$property]) AND !is_array($ownDefaults[$property]) AND $stored === (string) $ownDefaults[$property]) {
                $offered[$type]['screens'][$sid][] = $property;
            }
        }
    }
    foreach (array_keys($offered) as $type) {
        if (!$offered[$type]['screens']) {
            unset($offered[$type]);
        }
    }
    return $offered;
}

/**
 * The screens, other than template screens, that have templates of their own in their theme, and none in Lyris, so
 * they can take copies along.
 *
 * @return array sid => the theme the templates are in
 */
function formulize_lyrisUpdateTemplatesOffered() {
    static $offered = null;
    if ($offered !== null) {
        return $offered;
    }
    $offered = array();
    $screen_handler = xoops_getmodulehandler('screen', 'formulize');
    foreach (formulize_lyrisUpdateScreens() as $sid => $screenInfo) {
        if ($screenInfo['type'] == 'template' OR $screenInfo['theme'] === FORMULIZE_LYRIS_UPDATE_TO) {
            continue;
        }
        if ($screen_handler->templateFilesInTheme($sid, $screenInfo['theme']) AND !$screen_handler->templateFilesInTheme($sid, FORMULIZE_LYRIS_UPDATE_TO)) {
            $offered[$sid] = $screenInfo['theme'];
        }
    }
    return $offered;
}

/**
 * The template screens that will get copies of their code and template in Lyris, whatever is chosen, because they
 * have no defaults to fall back on there: those with files in their theme and none in Lyris.
 *
 * @return array sid => the theme the files are in
 */
function formulize_lyrisUpdateTemplateScreensToCopy() {
    $toCopy = array();
    $screen_handler = xoops_getmodulehandler('screen', 'formulize');
    foreach (formulize_lyrisUpdateScreens() as $sid => $screenInfo) {
        if ($screenInfo['type'] != 'template' OR $screenInfo['theme'] === FORMULIZE_LYRIS_UPDATE_TO OR !isset(formulize_selectableThemesList()[$screenInfo['theme']])) {
            continue;
        }
        if ($screen_handler->templateFilesInTheme($sid, $screenInfo['theme']) AND !$screen_handler->templateFilesInTheme($sid, FORMULIZE_LYRIS_UPDATE_TO)) {
            $toCopy[$sid] = $screenInfo['theme'];
        }
    }
    return $toCopy;
}

/**
 * The answers to the questions, from the submitted update form, checked against what was offered: anything that
 * wasn't offered is left out.
 *
 * @param array $submitted the submitted form, ie: $_POST
 * @return array with 'theme' (a key of formulize_lyrisUpdateThemeChoices), 'from' (the theme the site is moving
 *               from, as it was when the questions were answered), 'look' (a look key or ''), 'images' (the
 *               logo and icon to bring along, from formulize_lyrisUpdateImagesToCarry), 'templates' (sid => theme to
 *               copy from) and 'settings' (sid => settings)
 */
function formulize_lyrisUpdateReadChoices($submitted) {
    $choices = array('theme' => 'keep', 'from' => formulize_lyrisUpdateFromTheme(), 'look' => '', 'images' => array(), 'templates' => array(), 'settings' => array());
    $themeChoices = formulize_lyrisUpdateThemeChoices();
    if (isset($submitted['lyris_theme_choice']) AND is_string($submitted['lyris_theme_choice']) AND isset($themeChoices[$submitted['lyris_theme_choice']])) {
        $choices['theme'] = $submitted['lyris_theme_choice'];
    }
    // keeping the theme as it is changes nothing else: the look, the screen settings and the templates aren't asked
    // about, so whatever the form sends for them is left out
    if ($choices['theme'] == 'keep') {
        return $choices;
    }
    return formulize_lyrisReadLyrisChoices($submitted, $choices, $choices['from']);
}

/**
 * The answers to the questions asked when the site's theme is changed to Lyris on Appearance > Settings, from the
 * submitted questions, checked against what was offered. The theme isn't asked about, since Lyris was chosen
 * already, so the answer about it is always 'site': the site's theme changes, and the admin theme is saved with the
 * rest of the settings.
 *
 * @param array $submitted the submitted questions, ie: $_POST
 * @return array as formulize_lyrisUpdateReadChoices gives
 */
function formulize_lyrisSwitchReadChoices($submitted) {
    $choices = array('theme' => 'site', 'from' => formulize_lyrisUpdateFromTheme(), 'look' => '', 'images' => array(), 'templates' => array(), 'settings' => array());
    return formulize_lyrisReadLyrisChoices($submitted, $choices, $choices['from']);
}

/**
 * The answers about Lyris itself, its look and what screens take along to it, which both sets of questions ask.
 *
 * @param array $submitted the submitted questions, ie: $_POST
 * @param array $choices the answers so far, with the answer about the theme
 * @param string $fromTheme the theme the site is moving from
 * @return array the answers, with the look, images, templates and settings filled in
 */
function formulize_lyrisReadLyrisChoices($submitted, $choices, $fromTheme) {
    $looks = formulize_lyrisUpdateLooks();
    if (isset($submitted['lyris_look']) AND is_string($submitted['lyris_look']) AND isset($looks[$submitted['lyris_look']])) {
        $choices['look'] = $submitted['lyris_look'];
    }
    // the site's own logo and icon always come along, since a site that has its own wouldn't want to lose them
    $choices['images'] = formulize_lyrisUpdateImagesToCarry($fromTheme);
    $templatesOffered = formulize_lyrisUpdateTemplatesOffered();
    foreach (isset($submitted['lyris_copy_templates']) ? (array) $submitted['lyris_copy_templates'] : array() as $sid) {
        if (isset($templatesOffered[intval($sid)])) {
            $choices['templates'][intval($sid)] = $templatesOffered[intval($sid)];
        }
    }
    $settingsOffered = array();
    foreach (formulize_lyrisUpdateSettingsOffered() as $offered) {
        $settingsOffered += $offered['screens'];
    }
    foreach (isset($submitted['lyris_screen_settings']) ? (array) $submitted['lyris_screen_settings'] : array() as $sid => $properties) {
        if (!isset($settingsOffered[intval($sid)])) {
            continue;
        }
        $properties = array_values(array_intersect(array_filter((array) $properties, 'is_string'), $settingsOffered[intval($sid)]));
        if ($properties) {
            $choices['settings'][intval($sid)] = $properties;
        }
    }
    return $choices;
}

/**
 * The answers to the questions, kept for the update to apply. admin/op.php sets them before it starts the update,
 * and patch 021 reads them, since a patch is given nothing but the version numbers.
 *
 * @param array|null $choices the answers to keep, from formulize_lyrisUpdateReadChoices, or null to read them
 * @return array|null the answers, or null when there are none, as when nobody was asked
 */
function formulize_lyrisUpdateChoices($choices = null) {
    static $kept = null;
    if ($choices !== null) {
        $kept = $choices;
    }
    return $kept;
}

/**
 * Apply the answers: copy templates, change screen settings, start Lyris with the chosen look and the site's logo,
 * then change the themes. Template screens get copies of their files in Lyris whatever the answers, or when nobody
 * was asked. Everything here is safe to run again: a copy is only made where there's none, and a setting is only
 * changed to the value it is being changed to.
 *
 * For the update, what was done is echoed for its results, along with anything that didn't work. For a switch on
 * Appearance > Settings (the theme answer is 'site'), only what didn't work is echoed, and then the theme isn't
 * changed, so the site stays as it was where it can, and the switch can be tried again.
 *
 * @param array|null $choices the answers, from formulize_lyrisUpdateChoices or formulize_lyrisSwitchReadChoices
 * @return boolean whether it all worked
 */
function formulize_lyrisUpdateApply($choices) {
    $ok = true;
    $report = !($choices AND $choices['theme'] == 'site');
    $screen_handler = xoops_getmodulehandler('screen', 'formulize');
    $themes = formulize_selectableThemesList();
    if (!isset($themes[FORMULIZE_LYRIS_UPDATE_TO])) {
        echo '<p>' . _AM_FORMULIZE_LYRIS_APPLY_NO_LYRIS . '</p>';
        return true;
    }

    // the looks that come with Lyris are based on Default, and their stylesheets were written with Default as it
    // comes, so they're written again with Default as this site has it
    if (!formulize_regenerateAppearanceCss(FORMULIZE_LYRIS_UPDATE_TO)) {
        echo '<p>' . sprintf(_AM_APPEARANCE_ERR_NOT_SAVED, FORMULIZE_LYRIS_UPDATE_TO, formulize_getAppearanceDir(FORMULIZE_LYRIS_UPDATE_TO)) . '</p>';
    }

    // templates, first, while each screen still has the theme its templates are in
    $templates = formulize_lyrisUpdateTemplateScreensToCopy();
    if ($choices) {
        $templates += $choices['templates'];
    }
    $copied = 0;
    foreach ($templates as $sid => $fromTheme) {
        if (!$screen = $screen_handler->get($sid)) {
            continue;
        }
        if ($screen_handler->copyTemplateFilesToTheme($screen, $fromTheme, FORMULIZE_LYRIS_UPDATE_TO)) {
            $copied++;
        } else {
            echo '<p>' . sprintf($report ? _AM_FORMULIZE_LYRIS_APPLY_TEMPLATES_FAILED : _AM_FORMULIZE_LYRIS_SWITCH_TEMPLATES_FAILED, $screen->getVar('title'), htmlspecialchars($fromTheme)) . '</p>';
            $ok = false;
        }
    }
    if ($copied AND $report) {
        echo '<p>' . ($copied == 1 ? _AM_FORMULIZE_LYRIS_APPLY_TEMPLATES_DONE_ONE : sprintf(_AM_FORMULIZE_LYRIS_APPLY_TEMPLATES_DONE, $copied)) . '</p>';
    }

    if (!$choices) {
        echo '<p>' . _AM_FORMULIZE_LYRIS_APPLY_NOTHING_CHOSEN . '</p>';
        return $ok;
    }

    // screen settings
    $changed = 0;
    foreach ($choices['settings'] as $sid => $properties) {
        if (!$base = $screen_handler->get($sid)) {
            continue;
        }
        $type = $base->getVar('type');
        $typeHandler = xoops_getmodulehandler($type . 'Screen', 'formulize');
        if (!$screen = $typeHandler->get($sid)) {
            continue;
        }
        $lyrisDefaults = $screen_handler->getThemeScreenDefaults($type, FORMULIZE_LYRIS_UPDATE_TO);
        foreach ($properties as $property) {
            foreach (array_merge(array($property), formulize_lyrisUpdateSettingCompanions($property)) as $setting) {
                if (isset($lyrisDefaults[$setting]) AND !is_array($lyrisDefaults[$setting]) AND isset($screen->vars[$setting])) {
                    $screen->setVar($setting, $lyrisDefaults[$setting]);
                }
            }
        }
        if ($typeHandler->insert($screen)) {
            $changed++;
        } else {
            echo '<p>' . sprintf($report ? _AM_FORMULIZE_LYRIS_APPLY_SETTINGS_FAILED : _AM_FORMULIZE_LYRIS_SWITCH_SETTINGS_FAILED, $screen->getVar('title')) . '</p>';
            $ok = false;
        }
    }
    if ($changed AND $report) {
        echo '<p>' . ($changed == 1 ? _AM_FORMULIZE_LYRIS_APPLY_SETTINGS_DONE_ONE : sprintf(_AM_FORMULIZE_LYRIS_APPLY_SETTINGS_DONE, $changed)) . '</p>';
    }

    if ($choices['theme'] == 'keep') {
        return $ok;
    }

    // the look Lyris starts with
    if ($choices['look'] AND $choices['look'] !== formulize_getAppearanceSiteLook(FORMULIZE_LYRIS_UPDATE_TO)) {
        if ($error = formulize_setAppearanceSiteLook(FORMULIZE_LYRIS_UPDATE_TO, $choices['look'])) {
            echo '<p>' . htmlspecialchars($error) . '</p>';
            $ok = false;
        }
    }
    // the site's logo and icon, on Default, which the looks that come with Lyris are based on, and on the chosen
    // look too when it isn't based on Default
    if ($choices['images']) {
        $lookKeys = array('default');
        if ($choices['look'] AND !in_array('default', formulize_appearanceLookChain(formulize_getAppearanceLooks(FORMULIZE_LYRIS_UPDATE_TO), $choices['look']), true)) {
            $lookKeys[] = $choices['look'];
        }
        foreach ($choices['images'] as $setting => $path) {
            if ($error = formulize_adoptAppearanceImage($setting, $path, FORMULIZE_LYRIS_UPDATE_TO, $lookKeys)) {
                echo '<p>' . htmlspecialchars($error) . '</p>';
                $ok = false;
            }
        }
        if ($ok AND $report) {
            echo '<p>' . _AM_FORMULIZE_LYRIS_APPLY_IMAGES_DONE . '</p>';
        }
    }

    // a switch that didn't all work leaves the site on its theme, where everything that worked is ready for Lyris
    // and changes nothing else, so it can be tried again: what is offered then is only what's left to do
    if ($choices['theme'] == 'site' AND !$ok) {
        echo '<p>' . sprintf(_AM_FORMULIZE_LYRIS_SWITCH_NOT_SWITCHED, htmlspecialchars(formulize_storedSiteTheme('theme_set'))) . '</p>';
        return false;
    }

    // the themes, last, since the site's theme changing moves every screen to it, and records that Lyris has been the
    // site's theme (see formulize_siteThemeChanged)
    if ($choices['theme'] == 'switch') {
        $error = formulize_setSiteThemes(FORMULIZE_LYRIS_UPDATE_TO, FORMULIZE_LYRIS_UPDATE_TO);
    } elseif ($choices['theme'] == 'site') {
        $error = formulize_setSiteThemes(FORMULIZE_LYRIS_UPDATE_TO, null);
    } else {
        $error = formulize_setSiteThemes(null, FORMULIZE_LYRIS_UPDATE_TO);
        // trying it: webmasters are the ones using the Templates tab, and they see Lyris now
        if (!$error AND !$screen_handler->setThemeForAllScreens(FORMULIZE_LYRIS_UPDATE_TO)) {
            $error = _AM_FORMULIZE_LYRIS_APPLY_SCREENS_FAILED;
        }
        // the screens have Lyris's settings and templates now, so switching the site later has nothing to ask
        if (!$error) {
            formulize_markThemeActivated(FORMULIZE_LYRIS_UPDATE_TO);
        }
    }
    if ($error) {
        echo '<p>' . $error . '</p>';
        return false;
    }
    if ($report) {
        echo '<p>' . ($choices['theme'] == 'switch' ? _AM_FORMULIZE_LYRIS_APPLY_SWITCHED : _AM_FORMULIZE_LYRIS_APPLY_ADMIN_SWITCHED) . '</p>';
    }
    return $ok;
}

/**
 * What to do after the update, for the answers given: shown with the update's results.
 *
 * @param array $choices the answers, from formulize_lyrisUpdateReadChoices
 * @return string HTML
 */
function formulize_lyrisUpdateNextSteps($choices) {
    $settingsUrl = htmlspecialchars(XOOPS_URL . '/modules/formulize/admin/ui.php?page=appearance&view=settings');
    $looksUrl = htmlspecialchars(XOOPS_URL . '/modules/formulize/admin/ui.php?page=appearance&view=looks');
    $docsUrl = 'https://formulize.org/documentation/switching_to_lyris/';
    $html = '<div class="formulize-lyris-next-steps"><h2>' . _AM_FORMULIZE_LYRIS_NEXT_TITLE . '</h2><ul>';
    if ($choices['theme'] == 'switch') {
        $html .= '<li>' . sprintf(_AM_FORMULIZE_LYRIS_NEXT_SWITCHED_LOOK, htmlspecialchars(XOOPS_URL . '/')) . '</li>';
        $leftBehind = array();
        $screens = formulize_lyrisUpdateScreens();
        foreach (formulize_lyrisUpdateTemplatesOffered() as $sid => $theme) {
            if (!isset($choices['templates'][$sid])) {
                $leftBehind[] = '<a href="' . htmlspecialchars(XOOPS_URL . '/modules/formulize/admin/ui.php?page=screen&sid=' . intval($sid) . '&fid=' . intval($screens[$sid]['fid']) . '&tab=templates') . '">' . $screens[$sid]['title'] . '</a>';
            }
        }
        if ($leftBehind) {
            $html .= '<li>' . _AM_FORMULIZE_LYRIS_NEXT_SWITCHED_TEMPLATES . ' ' . implode(', ', $leftBehind) . '</li>';
        }
        $html .= '<li>' . sprintf(_AM_FORMULIZE_LYRIS_NEXT_LOOKS, $looksUrl) . '</li>';
        $html .= '<li>' . sprintf(_AM_FORMULIZE_LYRIS_NEXT_SWITCHED_BACK, htmlspecialchars($choices['from']), $settingsUrl) . '</li>';
    } elseif ($choices['theme'] == 'try') {
        $html .= '<li>' . sprintf(_AM_FORMULIZE_LYRIS_NEXT_TRY, htmlspecialchars($choices['from'])) . '</li>';
        $html .= '<li>' . sprintf(_AM_FORMULIZE_LYRIS_NEXT_LOOKS, $looksUrl) . '</li>';
        $html .= '<li>' . sprintf(_AM_FORMULIZE_LYRIS_NEXT_TRY_READY, $settingsUrl) . '</li>';
    } else {
        $html .= '<li>' . sprintf(_AM_FORMULIZE_LYRIS_NEXT_KEEP, $settingsUrl) . '</li>';
        $html .= '<li>' . _AM_FORMULIZE_LYRIS_NEXT_KEEP_TEMPLATES . '</li>';
    }
    $html .= '<li>' . sprintf(_AM_FORMULIZE_LYRIS_NEXT_DOCS, htmlspecialchars($docsUrl)) . '</li>';
    $html .= '</ul></div>';
    return $html;
}
