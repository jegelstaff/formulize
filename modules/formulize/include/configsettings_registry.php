<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################
/**
 * Formulize admin "settings" registry — DATA ONLY.
 *
 * Declares the subject tabs of the Formulize admin homepage, the sub-views within
 * each, and which preferences appear on each settings view. No code changes are
 * needed to rearrange things: this data is rendered automatically (see
 * getHomeTabs() in admin/ui.php, the generic handler admin/configsubject.php, the
 * wrapper template templates/admin/configsubject.html, and the settings body
 * template templates/admin/configsettings.html).
 *
 * The Apps tab is NOT declared here — it is a plain page tab (the forms dashboard,
 * admin/home.php) added directly in getHomeTabs().
 *
 * Structure (two levels):
 *
 *   '<subject-slug>' => array(
 *       'name'  => 'Tab Label',
 *       'views' => array(
 *           '<view-slug>' => array(
 *               'name' => 'Sub-nav Label',
 *               'type' => 'settings',          // a page of config settings
 *               'sections' => array(
 *                   'Section Heading' => array(
 *                       array('name'=>'<conf_name>', 'scope'=>'<scope>',
 *                             'caption'=>'...', 'description'=>'...', 'showWhen'=>array(...)),
 *                       ...
 *                   ),
 *               ),
 *           ),
 *           '<view-slug>' => array(
 *               'name' => 'Sub-nav Label',
 *               'type' => 'page',              // an existing admin page, relocated here
 *               'page' => '<controller-slug>', // admin/<slug>.php + templates/admin/<slug>.html
 *           ),
 *       ),
 *   ),
 *
 * scope values:
 *   'system'     -> ImpressCMS/XOOPS general settings   (ICMS_CONF)
 *   'user'       -> User settings category              (ICMS_CONF_USER)
 *   'mailer'     -> Email/SMTP settings category        (ICMS_CONF_MAILER; alias 'email')
 *   'auth'       -> Authentication settings category    (ICMS_CONF_AUTH)
 *   'metafooter' -> Meta tags & footer category         (ICMS_CONF_METAFOOTER)
 *   'formulize'  -> Formulize module preferences
 *
 * caption/description: omit to fall back to the setting's own (system) text.
 *
 * showWhen (visibility dependencies) — equality only, deliberately not a language:
 *   array('name'=>'auth_openid', 'value'=>1)                  // show when == 1
 *   array('name'=>'x', 'value'=>array('a','b'))               // "is one of" (OR within one condition)
 *   array( array('name'=>..), array('name'=>..) )             // all must hold (AND)
 *   array('op'=>'any', 'conditions'=>array(...))              // at least one must hold (OR between conditions)
 *   A condition's 'scope' defaults to the setting's own scope.
 *
 * Saving is delegated to the system preferences handler, so every side-effect and
 * validation still fires exactly as on the legacy preferences page.
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Load user-visible strings from the active language, falling back to English.
global $xoopsConfig;
$_cfgLangFile = XOOPS_ROOT_PATH . '/modules/formulize/language/'
    . (isset($xoopsConfig['language']) ? $xoopsConfig['language'] : 'english')
    . '/configsettings_registry.php';
if (!file_exists($_cfgLangFile)) {
    $_cfgLangFile = XOOPS_ROOT_PATH . '/modules/formulize/language/english/configsettings_registry.php';
}
include_once $_cfgLangFile;
unset($_cfgLangFile);

return array(

    // =====================================================================
    'users' => array(
        'name' => _AM_CFG_TAB_USERS,
        'views' => array(
            'email' => array(
                'name' => _AM_CFG_VIEW_USERS_EMAIL,
                'type' => 'page',
                'page' => 'mailusers',
            ),
            'apikeys' => array(
                'name' => _AM_CFG_VIEW_USERS_APIKEYS,
                'type' => 'page',
                'page' => 'managekeys',
            ),
						'settings' => array(
                'name' => _AM_CFG_VIEW_USERS_SETTINGS,
                'type' => 'settings',
                'sections' => array(
                    _AM_CFG_SEC_SIGNING_IN => array(
                        // Two-factor settings only apply to normal username/password
                        // logins, so they hide when an external identity provider
                        // (Google or Okta) is in use. Okta is "on" when its endpoint
                        // textbox has a value, so the condition checks for empty.
                        array(
                            'name' => 'auth_2fa',
                            'scope' => 'auth',
                            'showWhen' => array(
                                array('name' => 'auth_openid', 'value' => 0),
                                array('name' => 'auth_okta', 'value' => ''),
                            ),
                        ),
                        array(
                            'name' => 'auth_2fa_groups',
                            'scope' => 'auth',
                            'showWhen' => array(
                                array('name' => 'auth_openid', 'value' => 0),
                                array('name' => 'auth_okta', 'value' => ''),
                                array('name' => 'auth_2fa', 'value' => 1),
                            ),
                        ),
                        // How long a "remember this device" choice keeps 2FA from
                        // re-prompting on that device (days, clamped 1-365). Only
                        // relevant when 2FA is on, so it shares 2FA's visibility rules.
                        // This setting is Formulize-scoped, so the auth-scoped conditions
                        // it depends on must name their scope explicitly.
                        array(
                            'name' => 'tfaRememberDeviceDays',
                            'scope' => 'formulize',
                            'showWhen' => array(
                                array('name' => 'auth_openid', 'scope' => 'auth', 'value' => 0),
                                array('name' => 'auth_okta', 'scope' => 'auth', 'value' => ''),
                                array('name' => 'auth_2fa', 'scope' => 'auth', 'value' => 1),
                            ),
                        ),
                        // Alternative sign-in methods (less common, so below 2FA).
                        // Google is stored as 'auth_openid' (repurposed but never renamed).
                        array(
                            'name' => 'auth_openid',
                            'scope' => 'auth',
                        ),
                        array(
                            'name' => 'auth_googleonly',
                            'scope' => 'auth',
                            'showWhen' => array('name' => 'auth_openid', 'value' => 1),
                        ),
                        // Okta SAML: enabled by entering its SSO endpoint URL; blank = off.
                        // Caption overridden for clarity; the system description carries
                        // the setup instructions (settings.php in /libraries/php-saml/).
                        array(
                            'name' => 'auth_okta',
                            'scope' => 'auth',
                        ),
                    ),
                    _AM_CFG_SEC_NEW_USER_DEFAULTS => array(
                        // Whether visitors may create their own accounts via the public signup page
                        // (signup.php). Off by default; most sites stay closed. When on, an invitation
                        // token in the signup URL can also grant group membership (Users → Tokens).
                        array(
                            'name' => 'allow_register',
                            'scope' => 'user',
                        ),
                        // Require an invitation token to sign up. Only meaningful when self-registration
                        // is enabled, so it hides unless allow_register is on. When on, signup.php will
                        // not create an account without a valid token.
                        array(
                            'name' => 'requireTokenForSignup',
                            'scope' => 'formulize',
                            'showWhen' => array('name' => 'allow_register', 'scope' => 'user', 'value' => 1),
                        ),
                        array(
                            'name' => 'default_TZ',
                            'scope' => 'system',
                            'caption' => _AM_CFG_CAP_DEFAULT_TZ,
                        ),
                    ),
                ),
            ),
            'tokens' => array(
                'name' => _AM_CFG_VIEW_USERS_TOKENS,
                'type' => 'page',
                'page' => 'managetokens',
            ),
						'permissions' => array(
                'name' => _AM_CFG_VIEW_SETTINGS_PERMISSIONS,
                'type' => 'page',
                'page' => 'managepermissions',
            ),
        ),
    ),

    // =====================================================================
    'appearance' => array(
        'name' => _AM_CFG_TAB_APPEARANCE,
        'views' => array(
            // Theme Editor is the only view for now; more appearance settings are
            // planned and will become the default (first-declared) view then.
            'themeeditor' => array(
                'name' => _AM_CFG_VIEW_APPEARANCE_THEMEEDITOR,
                'type' => 'page',
                'page' => 'themeeditor',
            ),
        ),
    ),

    // =====================================================================
    'settings' => array(
        'name' => _AM_CFG_TAB_SETTINGS,
        'views' => array(

            'elements' => array(
                'name' => _AM_CFG_VIEW_SETTINGS_ELEMENTS,
                'type' => 'settings',
                'sections' => array(
                    _AM_CFG_SEC_TEXTBOX_DEFAULTS => array(
                        array('name' => 't_max', 'scope' => 'formulize'),
                        array('name' => 'ta_rows', 'scope' => 'formulize'),
                    ),
                    _AM_CFG_SEC_NUMBER_BOX_DEFAULTS => array(
                        array('name' => 'number_decimals', 'scope' => 'formulize'),
                        array('name' => 'number_prefix', 'scope' => 'formulize'),
                        array('name' => 'number_suffix', 'scope' => 'formulize'),
                        array('name' => 'number_decimalsep', 'scope' => 'formulize'),
                        array('name' => 'number_sep', 'scope' => 'formulize'),
                    ),
                    _AM_CFG_SEC_TIME_DEFAULTS => array(
                        array('name' => 'time_format', 'scope' => 'formulize'),
                    ),
                    _AM_CFG_SEC_CHECKBOX_RADIO_DEFAULTS => array(
                        array('name' => 'delimeter', 'scope' => 'formulize'),
                    ),
                ),
            ),

            'forms' => array(
                'name' => _AM_CFG_VIEW_SETTINGS_FORMS,
                'type' => 'settings',
                'sections' => array(
                    _AM_CFG_SEC_FORM_DISPLAY => array(
                        array('name' => 'show_empty_elements_when_read_only', 'scope' => 'formulize'),
                        array('name' => 'formulizeShowPrintableViewButtons', 'scope' => 'formulize'),
                        array('name' => 'printviewStylesheets', 'scope' => 'formulize', 'showWhen' => array('name' => 'formulizeShowPrintableViewButtons', 'value' => 1)),
                        array('name' => 'heading_help_link', 'scope' => 'formulize'),
                    ),
                    _AM_CFG_SEC_REVISION_HISTORY => array(
                        array('name' => 'formulizeRevisionsForAllForms', 'scope' => 'formulize'),
                    ),
                    _AM_CFG_SEC_EXPORT => array(
                        array('name' => 'downloadDefaultToExcel', 'scope' => 'formulize'),
                        array('name' => 'exportIntroChar', 'scope' => 'formulize'),
                    ),
                    _AM_CFG_SEC_LISTS => array(
                        array('name' => 'LOE_limit', 'scope' => 'formulize'),
                        array('name' => 'formulizeDefaultEditIconStyle', 'scope' => 'formulize'),
                    ),
                ),
            ),

            'messaging' => array(
                'name' => _AM_CFG_VIEW_SETTINGS_MESSAGING,
                'type' => 'settings',
                'sections' => array(
                    _AM_CFG_SEC_EMAIL_DELIVERY => array(
                        array('name' => 'adminmail', 'scope' => 'system'),
                        array('name' => 'mailmethod', 'scope' => 'mailer'),
                        array('name' => 'smtphost', 'scope' => 'mailer', 'showWhen' => array('name' => 'mailmethod', 'value' => array('smtp', 'smtpauth'))),
                        array('name' => 'smtpuser', 'scope' => 'mailer', 'showWhen' => array('name' => 'mailmethod', 'value' => array('smtpauth'))),
                        array('name' => 'smtppass', 'scope' => 'mailer', 'showWhen' => array('name' => 'mailmethod', 'value' => array('smtpauth'))),
                        array('name' => 'smtpsecure', 'scope' => 'mailer', 'showWhen' => array('name' => 'mailmethod', 'value' => array('smtp', 'smtpauth'))),
                        array('name' => 'smtpauthport', 'scope' => 'mailer', 'showWhen' => array('name' => 'mailmethod', 'value' => array('smtp', 'smtpauth'))),
                        array('name' => 'sendmailpath', 'scope' => 'mailer', 'showWhen' => array('name' => 'mailmethod', 'value' => 'sendmail')),
                    ),
                    _AM_CFG_SEC_TEXT_MESSAGES => array(
                        array('name' => 'sms_provider', 'scope' => 'mailer'),
                        array('name' => 'sms_account_sid', 'scope' => 'mailer'),
                        array('name' => 'sms_auth_token', 'scope' => 'mailer'),
                        array('name' => 'sms_from_number', 'scope' => 'mailer'),
                    ),
                    _AM_CFG_SEC_NOTIFICATIONS => array(
                        array('name' => 'notifyByCron', 'scope' => 'formulize'),
                    ),
                ),
            ),

            'ai' => array(
                'name' => _AM_CFG_VIEW_SETTINGS_AI,
                'type' => 'settings',
                'sections' => array(
                    _AM_CFG_SEC_AI => array(
                        array('name' => 'formulizeAIAssistantEnabled', 'scope' => 'formulize'),
                        array(
                            'name' => 'formulizeAIAssistantGroups',
                            'scope' => 'formulize',
                            'showWhen' => array('name' => 'formulizeAIAssistantEnabled', 'value' => 1),
                        ),
                        array('name' => 'formulizeMCPServerEnabled', 'scope' => 'formulize'),
                        array(
                            'name' => 'system_specific_instructions',
                            'scope' => 'formulize',
                            'showWhen' => array(
                                'op' => 'any',
                                'conditions' => array(
                                    array('name' => 'formulizeAIAssistantEnabled', 'value' => 1),
                                    array('name' => 'formulizeMCPServerEnabled', 'value' => 1),
                                ),
                            ),
                        ),
                    ),
                ),
            ),

            'system' => array(
                'name' => _AM_CFG_VIEW_SETTINGS_SYSTEM,
                'type' => 'settings',
                'sections' => array(
                    _AM_CFG_SEC_IDENTITY => array(
                        array('name' => 'sitename', 'scope' => 'system'),
                        array('name' => 'adminmail', 'scope' => 'system'),
                        array('name' => 'fromuid', 'scope' => 'mailer', 'caption' => _AM_CFG_CAP_FROMUID),
                        array('name' => 'language', 'scope' => 'system'),
                    ),
                    _AM_CFG_SEC_CUSTOM_URLS => array(
                        array('name' => 'formulizeRewriteRulesEnabled', 'scope' => 'formulize'),
                    ),
                    _AM_CFG_SEC_LOGGING => array(
                        array('name' => 'formulizeLoggingOnOff', 'scope' => 'formulize'),
                        array(
                            'name' => 'formulizeLogFileLocation',
                            'scope' => 'formulize',
                            'showWhen' => array('name' => 'formulizeLoggingOnOff', 'value' => 1),
                        ),
                        array(
                            'name' => 'formulizeLogFileStorageDurationHours',
                            'scope' => 'formulize',
                            'showWhen' => array('name' => 'formulizeLoggingOnOff', 'value' => 1),
                        ),
                    ),
                    _AM_CFG_SEC_DATABASE => array(
                        array('name' => 'server_TZ', 'scope' => 'system', 'caption' => _AM_CFG_CAP_SERVER_TZ, 'description' => _AM_CFG_DESC_SERVER_TZ),
                    ),
                    _AM_CFG_SEC_DATE_TIME_FORMATS => array(
                        '_section_help' => _AM_CFG_HELP_DATE_TIME_FORMATS,
                        array('name' => 'datestring', 'scope' => 'formulize', 'preview' => 'datetime',
                            'description' => _AM_CFG_DESC_DATESTRING),
                        array('name' => 'shortdatestring', 'scope' => 'formulize', 'preview' => 'datetime',
                            'description' => _AM_CFG_DESC_SHORTDATESTRING),
                        array('name' => 'shorttimestring', 'scope' => 'formulize', 'preview' => 'datetime',
                            'description' => _AM_CFG_DESC_SHORTTIMESTRING),
                    ),
                    _AM_CFG_SEC_SEO => array(
                        array('name' => 'meta_description', 'scope' => 'metafooter'),
                        array('name' => 'meta_robots', 'scope' => 'metafooter'),
                    ),
                    _AM_CFG_SEC_APPEARANCE => array(
                        array('name' => 'theme_set', 'scope' => 'system'),
                        array('name' => 'theme_admin_set', 'scope' => 'system'),
                        array('name' => 'footer', 'scope' => 'metafooter', 'description' => _AM_CFG_DESC_FOOTER),
                        array('name' => 'footadm', 'scope' => 'metafooter', 'description' => _AM_CFG_DESC_FOOTADM),
                    ),
                    _AM_CFG_SEC_AVAILABILITY => array(
                        array('name' => 'closesite', 'scope' => 'system'),
                        array(
                            'name' => 'closesite_text',
                            'scope' => 'system',
                            'showWhen' => array('name' => 'closesite', 'value' => 1),
                        ),
                        array(
                            'name' => 'closesite_okgrp',
                            'scope' => 'system',
                            'showWhen' => array('name' => 'closesite', 'value' => 1),
                        ),
                    ),
                ),
            ),

            'advanced' => array(
                'name' => _AM_CFG_VIEW_SETTINGS_ADVANCED,
                'type' => 'settings',
                'sections' => array(
                    _AM_CFG_SEC_PUBLIC_API => array(
                        array('name' => 'formulizePublicAPIEnabled', 'scope' => 'formulize'),
                    ),
                    _AM_CFG_SEC_SESSIONS_COOKIES => array(
                        array('name' => 'session_expire', 'scope' => 'system'),
                        array('name' => 'session_name', 'scope' => 'system', 'description' => _AM_CFG_DESC_SESSION_NAME),
                        array('name' => 'cookie_samesite', 'scope' => 'system'),
                    ),
                    _AM_CFG_SEC_DEBUGGING => array(
                        array('name' => 'debug_mode', 'scope' => 'system'),
                        array('name' => 'theme_fromfile', 'scope' => 'system'),
                        array('name' => 'debugDerivedValues', 'scope' => 'formulize'),
                        array('name' => 'logProcedure', 'scope' => 'formulize'),
                        array('name' => 'validateCode', 'scope' => 'formulize'),
                    ),
                    _AM_CFG_SEC_BASEMENT => array(
                        array('name' => 'useToken', 'scope' => 'formulize'),
                        array('name' => 'useCache', 'scope' => 'formulize'),
                        array('name' => 'all_done_singles', 'scope' => 'formulize'),
                        array('name' => 'f7MenuTemplate', 'scope' => 'formulize'),
                        array('name' => 'useOldCustomButtonEffectWriting', 'scope' => 'formulize'),
                        array('name' => 'isSaveLocked', 'scope' => 'formulize'),
                        array('name' => 'customScope', 'scope' => 'formulize'),
                    ),
                ),
            ),

        ),
    ),

);
