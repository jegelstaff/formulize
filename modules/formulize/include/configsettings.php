<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
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
/**
 * Config Settings library
 *
 * Renders an arbitrary mix of ImpressCMS/XOOPS config settings (system, user,
 * mailer, auth categories) and Formulize module preferences as a Formulize-styled
 * form, and saves them by delegating to the existing system preferences save
 * handler (/modules/system/admin.php?fct=preferences&op=save).
 *
 * Delegating the save means all of the system's controller-level side-effects
 * (e.g. the auth_2fa profile-field permission updates, enc_type password expiry,
 * theme/language/template cache rebuilds and the savingSystemAdminPreferencesItem
 * events) AND the persistence-level service-enablement checks in
 * icms_config_Item_Handler::insert() fire exactly as they do on the legacy
 * preferences page - with no duplication and no changes to any system/core files.
 *
 * @package Formulize
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

/**
 * Map a friendly scope name to the (conf_modid, conf_catid) pair that uniquely
 * locates a config item.
 *
 * @param string $scope One of: system, user, mailer/email, auth, formulize
 * @return array|false  array($conf_modid, $conf_catid), or false if unknown
 */
function formulize_configScopeToContext($scope) {
    switch ($scope) {
        case 'system':
            return array(0, ICMS_CONF);
        case 'user':
            return array(0, ICMS_CONF_USER);
        case 'mailer':
        case 'email':
            return array(0, ICMS_CONF_MAILER);
        case 'auth':
            return array(0, ICMS_CONF_AUTH);
        case 'formulize':
            return array(getFormulizeModId(), 0);
        default:
            return false;
    }
}

/**
 * Load the icms_config_Item_Object for a given setting name + scope.
 * Results are cached for the duration of the request.
 *
 * @param string $name  The conf_name
 * @param string $scope The scope (see formulize_configScopeToContext)
 * @return object|false The config item object, or false if not found
 */
function formulize_resolveConfigItem($name, $scope) {
    static $cache = array();
    $context = formulize_configScopeToContext($scope);
    if ($context === false) {
        return false;
    }
    list($modid, $catid) = $context;
    $key = (int) $modid . '|' . (int) $catid . '|' . $name;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $config_handler = icms::handler('icms_config');
    $criteria = new icms_db_criteria_Compo();
    $criteria->add(new icms_db_criteria_Item('conf_modid', (int) $modid));
    $criteria->add(new icms_db_criteria_Item('conf_catid', (int) $catid));
    $criteria->add(new icms_db_criteria_Item('conf_name', $name));
    $configs = $config_handler->getConfigs($criteria);
    $cache[$key] = (is_array($configs) && count($configs) > 0) ? $configs[0] : false;
    return $cache[$key];
}

/**
 * Build the HTML input control for a config item, reusing the ImpressCMS form
 * element classes (we only use them to render the control; the caption and help
 * text are rendered separately by the form wrapper).
 *
 * Mirrors the escaping rules of modules/system/admin/preferences/main.php:
 * text-like values are passed through htmlSpecialChars, selects/groups/yesno
 * receive the raw output value.
 *
 * @param object $config An icms_config_Item_Object
 * @return string HTML for the input control
 */
function formulize_configFormElementHtml($config) {
    $config_handler = icms::handler('icms_config');
    $name = $config->getVar('conf_name');
    $formtype = $config->getVar('conf_formtype');
    $value = $config->getConfValueForOutput();

    switch ($formtype) {

        case 'yesno':
            $ele = new icms_form_elements_Radioyn('', $name, $value, _YES, _NO);
            break;

        case 'select':
        case 'select_multi':
            $multi = ($formtype == 'select_multi');
            $ele = new icms_form_elements_Select('', $name, $value, $multi ? 5 : 1, $multi);
            $options = $config_handler->getConfigOptions(new icms_db_criteria_Item('conf_id', $config->getVar('conf_id')));
            $opcount = count($options);
            for ($j = 0; $j < $opcount; $j++) {
                $optval = defined($options[$j]->getVar('confop_value'))
                    ? constant($options[$j]->getVar('confop_value'))
                    : $options[$j]->getVar('confop_value');
                $optkey = defined($options[$j]->getVar('confop_name'))
                    ? constant($options[$j]->getVar('confop_name'))
                    : $options[$j]->getVar('confop_name');
                $ele->addOption($optval, $optkey);
            }
            break;

        case 'textarea':
            $ele = new icms_form_elements_Dhtmltextarea('', $name, icms_core_DataFilter::htmlSpecialChars($value));
            break;

        case 'textsarea':
            $textValue = is_array($value) ? implode('|', $value) : $value;
            $ele = new icms_form_elements_Textarea('', $name, icms_core_DataFilter::htmlSpecialChars($textValue), 5, 50);
            break;

        case 'password':
            $ele = new icms_form_elements_Password('', $name, 50, 255, icms_core_DataFilter::htmlSpecialChars($value));
            break;

        case 'group':
            $ele = new icms_form_elements_select_Group('', $name, true, $value, 1, false);
            break;

        case 'group_multi':
            $ele = new icms_form_elements_select_Group('', $name, true, $value, 5, true);
            break;

        case 'language':
            $ele = new icms_form_elements_select_Lang('', $name, $value);
            break;

        case 'timezone':
            $ele = new icms_form_elements_select_Timezone('', $name, $value);
            break;

        case 'textbox':
        default:
            $ele = new icms_form_elements_Text('', $name, 50, 255, icms_core_DataFilter::htmlSpecialChars($value));
            break;
    }

    return $ele->render();
}

/**
 * The settings-tabs registry. The actual data lives in the data-only file
 * include/configsettings_registry.php, so settings can be mixed and matched into
 * tabs without editing any code. Loaded once per request.
 *
 * @return array Map of page-slug => array('name'=>..., 'sections'=>array(heading=>array(descriptor,...)))
 */
function formulize_configSettingsRegistry() {
    static $registry = null;
    if ($registry === null) {
        $registry = include __DIR__ . '/configsettings_registry.php';
        if (!is_array($registry)) {
            $registry = array();
        }
    }
    return $registry;
}

/**
 * Get the definition for a single settings tab.
 *
 * @param string $slug The page-slug
 * @return array|false The tab definition, or false if no such tab is declared
 */
function formulize_getConfigSettingsTab($slug) {
    $registry = formulize_configSettingsRegistry();
    return isset($registry[$slug]) ? $registry[$slug] : false;
}

/**
 * Whether the given page-slug is a registry-declared settings tab.
 *
 * @param string $slug The page-slug
 * @return bool
 */
function formulize_isConfigSettingsTab($slug) {
    return formulize_getConfigSettingsTab($slug) !== false;
}

/**
 * Resolve a single inline descriptor array into a normalized descriptor with
 * name/scope/caption/description and the loaded config object.
 *
 * @param array $setting Descriptor array: array('name'=>conf_name, 'scope'=>scope, 'caption'=>..., 'description'=>...)
 * @return array|false   array('name','scope','caption','description','config'), or false
 */
function formulize_normalizeConfigSetting($setting) {
    if (!is_array($setting)) {
        return false;
    }
    $descriptor = $setting;

    $name = isset($descriptor['name']) ? $descriptor['name'] : '';
    $scope = isset($descriptor['scope']) ? $descriptor['scope'] : '';
    $config = formulize_resolveConfigItem($name, $scope);
    if (!$config) {
        return false;
    }

    // caption: explicit override, else the system's language-constant title
    if (isset($descriptor['caption']) && $descriptor['caption'] !== '') {
        $caption = $descriptor['caption'];
    } else {
        $confTitle = $config->getVar('conf_title');
        $caption = defined($confTitle) ? constant($confTitle) : $confTitle;
    }

    // description: explicit override, else the system's language-constant description (if any)
    if (isset($descriptor['description']) && $descriptor['description'] !== '') {
        $description = $descriptor['description'];
    } else {
        $confDesc = $config->getVar('conf_desc');
        $description = ($confDesc && defined($confDesc)) ? constant($confDesc) : '';
    }

    return array(
        'name' => $name,
        'scope' => $scope,
        'caption' => $caption,
        'description' => $description,
        'config' => $config,
    );
}

/**
 * Render a complete, Formulize-styled settings form for an arbitrary mix of
 * settings. The form POSTs to the existing system preferences save handler, so
 * all of that handler's side-effects and validation are preserved.
 *
 * @param array  $sections    Ordered map of sectionHeading => array of settings.
 *                            Each setting is an inline descriptor array
 *                            (array('name'=>conf_name, 'scope'=>scope, 'caption'=>...,
 *                            'description'=>...)). Use an integer/empty key for an
 *                            unlabelled section.
 * @param string $redirectUrl Absolute URL to return to after saving (e.g. the
 *                            Formulize admin tab the form lives on).
 * @return string HTML for the <form>.
 */
function formulize_renderConfigSettingsForm($sections, $redirectUrl) {
    $systemPrefsUrl = XOOPS_URL . "/modules/system/admin.php?fct=preferences";

    $html = "<form class='formulize-config-settings' method='post' action='" . htmlspecialchars($systemPrefsUrl, ENT_QUOTES) . "'>\n";
    $renderedAny = false;

    foreach ($sections as $heading => $settings) {
        $sectionHtml = '';
        foreach ((array) $settings as $setting) {
            $normalized = formulize_normalizeConfigSetting($setting);
            if (!$normalized) {
                $label = is_string($setting) ? $setting : (is_array($setting) && isset($setting['name']) ? $setting['name'] : 'unknown');
                $sectionHtml .= "<!-- formulize config: could not resolve setting '" . htmlspecialchars($label, ENT_QUOTES) . "' -->\n";
                continue;
            }
            $config = $normalized['config'];
            $confName = $config->getVar('conf_name');
            $inputHtml = formulize_configFormElementHtml($config);

            $sectionHtml .= "<div class='formulize-config-setting'>\n";
            $sectionHtml .= "  <label class='formulize-config-caption' for='" . htmlspecialchars($confName, ENT_QUOTES) . "'>" . $normalized['caption'] . "</label>\n";
            if ($normalized['description'] !== '') {
                $sectionHtml .= "  <p class='formulize-config-description' id='" . htmlspecialchars($confName, ENT_QUOTES) . "-help-text'>" . $normalized['description'] . "</p>\n";
            }
            $sectionHtml .= "  <div class='formulize-config-input'>" . $inputHtml . "</div>\n";
            $sectionHtml .= "  <input type='hidden' name='conf_ids[]' value='" . (int) $config->getVar('conf_id') . "'>\n";
            $sectionHtml .= "</div>\n";
            $renderedAny = true;
        }
        if ($sectionHtml !== '') {
            if (!is_int($heading) && $heading !== '') {
                $html .= "<h2 class='formulize-config-section'>" . htmlspecialchars($heading, ENT_QUOTES) . "</h2>\n";
            }
            $html .= $sectionHtml;
        }
    }

    // Fields the system save handler (op=save) needs.
    $html .= "<input type='hidden' name='op' value='save'>\n";
    $html .= "<input type='hidden' name='redirect' value='" . htmlspecialchars($redirectUrl, ENT_QUOTES) . "'>\n";
    $html .= icms::$security->getTokenHTML();

    if ($renderedAny) {
        $html .= "<p class='formulize-config-actions'><input type='submit' class='formButton' value='Save Changes'></p>\n";
    }

    $html .= "</form>\n";
    return $html;
}
