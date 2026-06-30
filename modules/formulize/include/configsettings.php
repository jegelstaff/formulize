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
 * @param string $scope One of: system, user, mailer/email, auth, metafooter, formulize
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
        case 'metafooter':
            return array(0, ICMS_CONF_METAFOOTER);
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
 * Ensure the language file that defines a config item's title/description
 * constants is loaded, so constant() resolves to real text instead of the raw
 * constant name. These files are not normally loaded in the Formulize admin:
 *   - System config items (conf_modid 0): labels are _MD_AM_* constants defined
 *     in the system module's preferences language file.
 *   - Module config items: labels are _MI_<dirname>_* constants defined in that
 *     module's modinfo language file.
 * Loaded at most once per owning module per request.
 *
 * @param object $config An icms_config_Item_Object
 * @return void
 */
function formulize_loadConfigSettingLanguage($config) {
    static $loaded = array();
    $modid = (int) $config->getVar('conf_modid');
    if (isset($loaded[$modid])) {
        return;
    }
    $loaded[$modid] = true;
    if ($modid == 0) {
        icms_loadLanguageFile('system', 'preferences', true);
    } else {
        $module_handler = icms::handler('icms_module');
        $module = $module_handler->get($modid);
        if (is_object($module)) {
            icms_loadLanguageFile($module->getVar('dirname'), 'modinfo');
        }
    }
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

        case 'theme':
        case 'theme_admin':
            $ele = new icms_form_elements_Select('', $name, $value);
            $dirlist = ($formtype == 'theme_admin')
                ? icms_view_theme_Factory::getAdminThemesList()
                : icms_view_theme_Factory::getThemesList();
            if (!empty($dirlist)) {
                asort($dirlist);
                $ele->addOptionArray($dirlist);
            }
            break;

        case 'user':
            require_once XOOPS_ROOT_PATH . '/modules/formulize/class/selectElement.php';
            require_once XOOPS_ROOT_PATH . '/modules/formulize/class/selectUsersElement.php';
            require_once XOOPS_ROOT_PATH . '/modules/formulize/class/autocompleteUsersElement.php';
            $handler = xoops_getmodulehandler('autocompleteUsersElement', 'formulize');
            $eleObj = $handler->create();
            $eleValue = $handler->getDefaultEleValue();

            // Pre-select the current UID by setting {SELECTEDNAMES} the same way loadValue() does,
            // then let render() build the full user list and cache file itself.
            $currentUid = intval($value);
            if ($currentUid) {
                $eleValue[ELE_VALUE_SELECT_OPTIONS]['{SELECTEDNAMES}'] = array($currentUid);
                $eleValue[ELE_VALUE_SELECT_OPTIONS]['{OWNERGROUPS}'] = array();
            }

            $eleObj->setVar('ele_value', $eleValue);
            $eleObj->setVar('ele_handle', $name);
            $eleObj->setVar('ele_type', 'autocompleteUsers');
            $eleObj->setVar('ele_display', 1);

            $formEle = $handler->render($eleValue, '', $name, false, $eleObj, 'new');
            return $formEle->render()
                . "<script src='" . XOOPS_URL . "/modules/formulize/include/js/autocomplete.js'></script>";

        case 'textbox':
        default:
            $ele = new icms_form_elements_Text('', $name, 50, 255, icms_core_DataFilter::htmlSpecialChars($value));
            break;
    }

    return $ele->render();
}

/**
 * The settings registry. The actual data lives in the data-only file
 * include/configsettings_registry.php, so tabs/settings can be mixed and matched
 * without editing any code. Loaded once per request.
 *
 * Two-level structure: subject-slug => array(
 *     'name'  => tab label,
 *     'views' => array( view-slug => array(
 *         'name'     => sub-nav label,
 *         'type'     => 'settings' | 'page',
 *         'sections' => ... (when type 'settings'),
 *         'page'     => '<admin controller slug>' (when type 'page'),
 *     )),
 * )
 *
 * @return array
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
 * Get the definition for a single subject tab.
 *
 * @param string $slug The subject (page) slug
 * @return array|false The subject definition, or false if no such subject is declared
 */
function formulize_getConfigSubject($slug) {
    $registry = formulize_configSettingsRegistry();
    return isset($registry[$slug]) ? $registry[$slug] : false;
}

/**
 * Whether the given page-slug is a registry-declared subject tab.
 *
 * @param string $slug The subject (page) slug
 * @return bool
 */
function formulize_isConfigSubject($slug) {
    return formulize_getConfigSubject($slug) !== false;
}

/**
 * Resolve which view of a subject is active, given the requested view slug.
 * Falls back to the subject's first view when the requested one is missing.
 *
 * @param array  $subject       A subject definition (from formulize_getConfigSubject)
 * @param string $requestedView The requested view slug (may be empty)
 * @return array|false array('slug'=>view-slug, 'view'=>view definition), or false if the subject has no views
 */
function formulize_resolveConfigView($subject, $requestedView) {
    if (empty($subject['views']) || !is_array($subject['views'])) {
        return false;
    }
    if ($requestedView !== '' && isset($subject['views'][$requestedView])) {
        return array('slug' => $requestedView, 'view' => $subject['views'][$requestedView]);
    }
    // default to the first declared view
    reset($subject['views']);
    $firstSlug = key($subject['views']);
    return array('slug' => $firstSlug, 'view' => $subject['views'][$firstSlug]);
}

/**
 * Normalize a descriptor's optional 'showWhen' into a conditions wrapper.
 *
 * Returns array('op'=>'all'|'any', 'conditions'=>[...]), or array() when there
 * are no conditions. 'op' controls how the list is evaluated: 'all' means every
 * condition must hold (AND); 'any' means at least one must hold (OR). Within a
 * single condition, the controlling setting matching ANY of its listed values
 * counts as holding. This is intentionally limited to equality so the registry
 * stays declarative rather than becoming a language.
 *
 * Input forms:
 *   single condition  — assoc array with a 'name' key
 *   AND list          — indexed array of conditions (default, op='all')
 *   OR wrapper        — array('op'=>'any', 'conditions'=>[...])
 * Each condition's 'value' may be a scalar or an array.
 *
 * @param mixed  $showWhen     The descriptor's 'showWhen' value (or null)
 * @param string $defaultScope Scope to assume for a condition that omits its own
 * @return array Wrapper array (empty if no conditions)
 */
function formulize_normalizeConfigConditions($showWhen, $defaultScope) {
    if (empty($showWhen) || !is_array($showWhen)) {
        return array();
    }
    if (isset($showWhen['op'])) {
        // OR/ANY wrapper: array('op'=>'any', 'conditions'=>[...])
        $op = ($showWhen['op'] === 'any') ? 'any' : 'all';
        $rawList = isset($showWhen['conditions']) ? $showWhen['conditions'] : array();
    } elseif (isset($showWhen['name'])) {
        // single condition
        $op = 'all';
        $rawList = array($showWhen);
    } else {
        // indexed list of conditions — AND by default
        $op = 'all';
        $rawList = $showWhen;
    }
    $conditions = array();
    foreach ($rawList as $cond) {
        if (!is_array($cond) || !isset($cond['name'])) {
            continue;
        }
        $values = isset($cond['value']) ? $cond['value'] : (isset($cond['values']) ? $cond['values'] : array());
        if (!is_array($values)) {
            $values = array($values);
        }
        $conditions[] = array(
            'name' => $cond['name'],
            'scope' => isset($cond['scope']) ? $cond['scope'] : $defaultScope,
            'values' => array_map('strval', $values),
        );
    }
    if (empty($conditions)) {
        return array();
    }
    return array('op' => $op, 'conditions' => $conditions);
}

/**
 * Evaluate a conditions wrapper against the current saved config values,
 * for deciding a setting's initial server-side visibility. The same logic runs
 * client-side (see formulize_configSettingsScriptBlock) for live toggling.
 *
 * @param array $wrapper From formulize_normalizeConfigConditions(): array('op'=>..., 'conditions'=>[...])
 * @return bool True if the setting should be visible
 */
function formulize_configConditionsPass($wrapper) {
    if (empty($wrapper) || !isset($wrapper['conditions'])) {
        return true;
    }
    $anyMode = (isset($wrapper['op']) && $wrapper['op'] === 'any');
    foreach ($wrapper['conditions'] as $cond) {
        $ctrl = formulize_resolveConfigItem($cond['name'], $cond['scope']);
        $current = $ctrl ? $ctrl->getConfValueForOutput() : null;
        $hit = false;
        if (is_array($current)) {
            $current = array_map('strval', $current);
            foreach ($cond['values'] as $v) {
                if (in_array($v, $current, true)) {
                    $hit = true;
                    break;
                }
            }
        } else {
            $hit = in_array((string) $current, $cond['values'], true);
        }
        if ($anyMode && $hit) {
            return true;
        }
        if (!$anyMode && !$hit) {
            return false;
        }
    }
    return !$anyMode;
}

/**
 * Resolve a single inline descriptor array into a normalized descriptor with
 * name/scope/caption/description, the loaded config object, and any visibility
 * conditions.
 *
 * @param array $setting Descriptor array: array('name'=>conf_name, 'scope'=>scope, 'caption'=>..., 'description'=>..., 'showWhen'=>...)
 * @return array|false   array('name','scope','caption','description','config','conditions'), or false
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

    // make sure the setting's own title/description constants are available
    formulize_loadConfigSettingLanguage($config);

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

    $conditions = formulize_normalizeConfigConditions(
        isset($descriptor['showWhen']) ? $descriptor['showWhen'] : null,
        $scope
    );

    return array(
        'name' => $name,
        'scope' => $scope,
        'caption' => $caption,
        'description' => $description,
        'config' => $config,
        'conditions' => $conditions,
        'preview' => isset($descriptor['preview']) ? $descriptor['preview'] : '',
    );
}

/**
 * The JavaScript that drives showWhen visibility dependencies, so dependent
 * settings appear/disappear live as the controlling settings are changed. The
 * initial state is already correct from the server (see the render function), so
 * this only handles changes. Emitted once per request.
 *
 * Written for jQuery 1.4.2 (the admin UI's version): read HTML5 data-* via
 * attr() (not data()), and use $.parseJSON.
 *
 * @return string A <script> block the first time it is called, '' thereafter.
 */
function formulize_configSettingsScriptBlock() {
    static $emitted = false;
    if ($emitted) {
        return '';
    }
    $emitted = true;
    return <<<JS
<script type="text/javascript">
(function($){
    function fzGetSettingValue(name){
        var \$radios = $('.formulize-config-settings input[type=radio][name="' + name + '"]');
        if(\$radios.length){ return \$radios.filter(':checked').val(); }
        var \$checks = $('.formulize-config-settings input[type=checkbox][name="' + name + '"]');
        if(\$checks.length){ return \$checks.filter(':checked').val(); }
        return $('.formulize-config-settings [name="' + name + '"]').val();
    }
    function fzConditionsPass(wrapper){
        var anyMode = (wrapper.op === 'any');
        var conditions = wrapper.conditions || [];
        for(var i=0; i<conditions.length; i++){
            var cond = conditions[i];
            var current = fzGetSettingValue(cond.name);
            var values = cond.values || [];
            var hit = false;
            if(current && current.constructor === Array){
                for(var a=0; a<current.length; a++){
                    for(var b=0; b<values.length; b++){
                        if(String(current[a]) === String(values[b])){ hit = true; }
                    }
                }
            } else {
                for(var c=0; c<values.length; c++){
                    if(String(current) === String(values[c])){ hit = true; break; }
                }
            }
            if(anyMode && hit){ return true; }
            if(!anyMode && !hit){ return false; }
        }
        return !anyMode;
    }
    var fzDur = 250; // duration of each step (slide, fade) in ms
    // Two-step reveal: open the vertical space first (content still invisible),
    // then fade the content in. Reverse on hide: fade out, then collapse the space.
    function fzShow(\$setting){
        var \$row = \$setting.find('.formulize-config-row');
        \$setting.stop(true, true);
        \$row.stop(true, true).css('opacity', 0);
        \$setting.slideDown(fzDur, function(){
            \$row.animate({opacity: 1}, fzDur);
        });
    }
    function fzHide(\$setting){
        var \$row = \$setting.find('.formulize-config-row');
        \$setting.stop(true, true);
        \$row.stop(true, true).animate({opacity: 0}, fzDur, function(){
            \$setting.slideUp(fzDur);
        });
    }
    function fzApplyDependencies(animate){
        $('.formulize-config-setting[data-showwhen]').each(function(){
            var \$setting = $(this);
            var conditions;
            try { conditions = $.parseJSON(\$setting.attr('data-showwhen')); } catch(e){ return; }
            var now = fzConditionsPass(conditions) ? '1' : '0';
            // only act when the visibility actually changes, so unchanged rows
            // never re-animate (which would flash on every keystroke/click)
            if(\$setting.attr('data-fzvisible') === now){ return; }
            \$setting.attr('data-fzvisible', now);
            if(!animate){
                if(now === '1'){ \$setting.show(); } else { \$setting.hide(); }
            } else {
                if(now === '1'){ fzShow(\$setting); } else { fzHide(\$setting); }
            }
        });
    }
    // Minimal PHP date() formatter for the live preview under date/time format
    // boxes. Supports the common codes; an unknown code is printed as-is, and a
    // backslash escapes the next character (PHP-style literal).
    function fzPhpDate(fmt, d){
        var days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        function pad(n){ return (n < 10 ? '0' : '') + n; }
        var H = d.getHours();
        var h12 = H % 12; if(h12 === 0){ h12 = 12; }
        var out = '';
        for(var i = 0; i < fmt.length; i++){
            var c = fmt.charAt(i);
            switch(c){
                case 'd': out += pad(d.getDate()); break;
                case 'j': out += d.getDate(); break;
                case 'D': out += days[d.getDay()].substr(0,3); break;
                case 'l': out += days[d.getDay()]; break;
                case 'N': out += (d.getDay() === 0 ? 7 : d.getDay()); break;
                case 'w': out += d.getDay(); break;
                case 'm': out += pad(d.getMonth() + 1); break;
                case 'n': out += (d.getMonth() + 1); break;
                case 'M': out += months[d.getMonth()].substr(0,3); break;
                case 'F': out += months[d.getMonth()]; break;
                case 'Y': out += d.getFullYear(); break;
                case 'y': out += pad(d.getFullYear() % 100); break;
                case 'H': out += pad(H); break;
                case 'G': out += H; break;
                case 'h': out += pad(h12); break;
                case 'g': out += h12; break;
                case 'i': out += pad(d.getMinutes()); break;
                case 's': out += pad(d.getSeconds()); break;
                case 'a': out += (H < 12 ? 'am' : 'pm'); break;
                case 'A': out += (H < 12 ? 'AM' : 'PM'); break;
                case '\\\\': i++; if(i < fmt.length){ out += fmt.charAt(i); } break;
                default: out += c;
            }
        }
        return out;
    }
    function fzUpdatePreviews(){
        // a fixed sample moment so the preview is a consistent reference
        var sample = new Date(2026, 5, 25, 14, 5, 9);
        $('.formulize-config-preview[data-preview-type="datetime"]').each(function(){
            var name = $(this).attr('data-preview-for');
            var fmt = $('.formulize-config-settings [name="' + name + '"]').val();
            if(fmt === null || fmt === undefined){ fmt = ''; }
            $(this).text(fmt === '' ? '' : 'Preview: ' + fzPhpDate(fmt, sample));
        });
    }
    $(function(){
        // initial state is already correct from the server, so snap to it (no animation);
        // animate only in response to the user changing a setting
        $('.formulize-config-settings').find('input, select').change(function(){ fzApplyDependencies(true); });
        fzApplyDependencies(false);

        // live date/time format previews: update as the format string is edited
        $('.formulize-config-preview[data-preview-type="datetime"]').each(function(){
            var name = $(this).attr('data-preview-for');
            $('.formulize-config-settings [name="' + name + '"]').bind('keyup change', fzUpdatePreviews);
        });
        fzUpdatePreviews();

        // show the unsaved-changes warning in the floating toolbar on any edit
        $('.formulize-config-settings').find('input, select, textarea').bind('change keyup', function(){
            $('#savewarning').show();
        });

        // preserve the vertical scroll position across the save reload: append the
        // current scroll offset to the redirect URL the system save handler returns to
        $('#formulize-config-settings-form').bind('submit', function(){
            var r = $(this).find('input[name=redirect]');
            if(r.length){ r.val(r.val() + '&scrollx=' + $(window).scrollTop()); }
        });
    });
})(jQuery);
</script>

JS;
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

    $body = '';
    $renderedAny = false;
    $renderedSections = array();

    foreach ($sections as $heading => $settings) {
        $sectionHtml = '';
        $headingHelp = '';
        foreach ((array) $settings as $key => $setting) {
            if ($key === '_section_help') {
                $headingHelp = $setting;
                continue;
            }
            $normalized = formulize_normalizeConfigSetting($setting);
            if (!$normalized) {
                $label = is_string($setting) ? $setting : (is_array($setting) && isset($setting['name']) ? $setting['name'] : 'unknown');
                $sectionHtml .= "<!-- formulize config: could not resolve setting '" . htmlspecialchars($label, ENT_QUOTES) . "' -->\n";
                continue;
            }
            $config = $normalized['config'];
            $confName = $config->getVar('conf_name');
            $inputHtml = formulize_configFormElementHtml($config);

            // Visibility dependencies (showWhen): set the correct initial state
            // server-side, and carry the conditions for the client-side script.
            $settingAttrs = " data-conf-name='" . htmlspecialchars($confName, ENT_QUOTES) . "'";
            $settingStyle = '';
            if (!empty($normalized['conditions'])) {
                $settingAttrs .= " data-showwhen='" . htmlspecialchars(json_encode($normalized['conditions']), ENT_QUOTES) . "'";
                if (!formulize_configConditionsPass($normalized['conditions'])) {
                    $settingStyle = " style='display:none'";
                }
            }

            $sectionHtml .= "<div class='formulize-config-setting'" . $settingAttrs . $settingStyle . ">\n";
            $sectionHtml .= "  <div class='formulize-config-row'>\n";
            $sectionHtml .= "    <div class='formulize-config-text'>\n";
            $sectionHtml .= "      <label class='formulize-config-caption' for='" . htmlspecialchars($confName, ENT_QUOTES) . "'>" . $normalized['caption'] . "</label>\n";
            if ($normalized['description'] !== '') {
                $sectionHtml .= "      <div class='formulize-config-description' id='" . htmlspecialchars($confName, ENT_QUOTES) . "-help-text'>" . $normalized['description'] . "</div>\n";
            }
            $sectionHtml .= "    </div>\n";
            // Optional live preview rendered directly under the control (e.g. a
            // sample date reformatted as the admin edits a date/time format string).
            $previewHtml = '';
            if (!empty($normalized['preview'])) {
                $previewHtml = "<div class='formulize-config-preview' data-preview-for='" . htmlspecialchars($confName, ENT_QUOTES) . "' data-preview-type='" . htmlspecialchars($normalized['preview'], ENT_QUOTES) . "'></div>";
            }
            $sectionHtml .= "    <div class='formulize-config-input'>" . $inputHtml . $previewHtml . "<input type='hidden' name='conf_ids[]' value='" . (int) $config->getVar('conf_id') . "'></div>\n";
            $sectionHtml .= "  </div>\n";
            $sectionHtml .= "</div>\n";
            $renderedAny = true;
        }
        if ($sectionHtml !== '') {
            $renderedSections[] = array('heading' => $heading, 'html' => $sectionHtml, 'heading_help' => $headingHelp);
        }
    }

    // A single-section page shows no section heading — the tab/sub-nav title already
    // names it. With multiple sections, each heading shows and its settings are
    // indented beneath it so the headings stand out.
    $showHeadings = (count($renderedSections) > 1);
    foreach ($renderedSections as $rs) {
        $heading = $rs['heading'];
        $headingHelp = isset($rs['heading_help']) ? $rs['heading_help'] : '';
        $bodyClass = 'formulize-config-indented formulize-config-section-body';
        if ($showHeadings && !is_int($heading) && $heading !== '') {
            $body .= "<div class='formulize-config-section-header'>"
                   . "<h2 class='formulize-config-section'>" . htmlspecialchars($heading, ENT_QUOTES) . "</h2>"
                   . $headingHelp
                   . "</div>\n";
            $bodyClass .= 'formulize-config-indented';
        }
        $body .= "<div class='$bodyClass'>\n" . $rs['html'] . "</div>\n";
    }

    $html = formulize_configSettingsScriptBlock();
    // The save button is rendered by ui.html's standard #admin_toolbar (above the
    // tabs, exactly where the regular Formulize Save button appears). It is a submit
    // button bound to this form via its id (HTML5 form="" attribute), so it submits
    // this settings form (the delegated save) without the 'savebutton' class that
    // would otherwise fire the Apps element-save handler. See configsubject.php,
    // which sets $adminPage['needsave'] + ['settingsSaveFormId'] for settings views.
    $html .= "<form class='formulize-config-settings' id='formulize-config-settings-form' method='post' action='" . htmlspecialchars($systemPrefsUrl, ENT_QUOTES) . "'>\n";

    $html .= $body;

    // Fields the system save handler (op=save) needs.
    $html .= "<input type='hidden' name='op' value='save'>\n";
    $html .= "<input type='hidden' name='redirect' value='" . htmlspecialchars($redirectUrl, ENT_QUOTES) . "'>\n";
    $html .= icms::$security->getTokenHTML();

    $html .= "</form>\n";
    return $html;
}
