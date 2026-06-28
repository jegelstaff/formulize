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
 *   array('name'=>'x', 'value'=>array('a','b'))               // "is one of" (OR)
 *   array( array('name'=>..), array('name'=>..) )             // all must hold (AND)
 *   A condition's 'scope' defaults to the setting's own scope.
 *
 * Saving is delegated to the system preferences handler, so every side-effect and
 * validation still fires exactly as on the legacy preferences page.
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

return array(

    // =====================================================================
    'users' => array(
        'name' => 'Users',
        'views' => array(
            'settings' => array(
                'name' => 'Settings',
                'type' => 'settings',
                'sections' => array(
                    'Signing in' => array(
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
                            'caption' => 'Okta SAML endpoint',
                        ),
                    ),
                    'New-user defaults' => array(
                        array(
                            'name' => 'default_TZ',
                            'scope' => 'system',
                            'caption' => 'Default timezone for new users',
                        ),
                    ),
                ),
            ),
            'email' => array(
                'name' => 'Email Users',
                'type' => 'page',
                'page' => 'mailusers',
            ),
            'apikeys' => array(
                'name' => 'API Keys',
                'type' => 'page',
                'page' => 'managekeys',
            ),
            'tokens' => array(
                'name' => 'Account Tokens',
                'type' => 'page',
                'page' => 'managetokens',
            ),
        ),
    ),

    // =====================================================================
    'settings' => array(
        'name' => 'Settings',
        'views' => array(

            'system' => array(
                'name' => 'System',
                'type' => 'settings',
                'sections' => array(
                    'Identity' => array(
                        array('name' => 'sitename', 'scope' => 'system'),
                        array('name' => 'adminmail', 'scope' => 'system', 'caption' => 'Site email address'),
                        array('name' => 'language', 'scope' => 'system', 'caption' => 'Default language'),
                    ),
                    'Appearance' => array(
                        array('name' => 'theme_set', 'scope' => 'system', 'caption' => 'Default theme'),
                        array('name' => 'theme_admin_set', 'scope' => 'system', 'caption' => 'Admin theme'),
                    ),
                    'Time' => array(
                        array('name' => 'server_TZ', 'scope' => 'system', 'caption' => 'Database timezone'),
                    ),
                    'Date & time formats' => array(
                        array('name' => 'datestring', 'scope' => 'formulize', 'caption' => 'Date &amp; time format', 'preview' => 'datetime',
                            'description' => 'Used for date-and-time display throughout Formulize.'),
                        array('name' => 'shortdatestring', 'scope' => 'formulize', 'caption' => 'Short date format', 'preview' => 'datetime',
                            'description' => 'Used for short date display.'),
                        array('name' => 'shorttimestring', 'scope' => 'formulize', 'caption' => 'Short time format', 'preview' => 'datetime',
                            'description' => "Used for short time display.<details class='formulize-config-help'><summary>Show date/time format codes</summary><div class='formulize-config-help-codes'><b>Year:</b> Y=2026, y=26<br><b>Month:</b> m=06, n=6, M=Jun, F=June<br><b>Day:</b> d=05, j=5, D=Thu, l=Thursday<br><b>Hour:</b> H=14, G=14 (no leading zero), h=02, g=2 (12-hour)<br><b>Minutes:</b> i=05 &nbsp;&nbsp; <b>Seconds:</b> s=09<br><b>AM/PM:</b> a=pm, A=PM</div></details>"),
                    ),
                    'Availability' => array(
                        array('name' => 'closesite', 'scope' => 'system', 'caption' => 'Take the site offline'),
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
                    'Search engines (SEO)' => array(
                        array('name' => 'meta_description', 'scope' => 'metafooter'),
                        array('name' => 'meta_robots', 'scope' => 'metafooter'),
                        array('name' => 'google_meta', 'scope' => 'metafooter', 'caption' => 'Google Search Console verification'),
                    ),
                    'Footer' => array(
                        array('name' => 'footer', 'scope' => 'metafooter', 'caption' => 'Site footer'),
                        array('name' => 'footadm', 'scope' => 'metafooter', 'caption' => 'Admin footer'),
                    ),
                    'Logging' => array(
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
                ),
            ),

            'forms' => array(
                'name' => 'Forms',
                'type' => 'settings',
                'sections' => array(
                    'Form elements' => array(
                        array('name' => 't_width', 'scope' => 'formulize'),
                        array('name' => 't_max', 'scope' => 'formulize'),
                        array('name' => 'ta_rows', 'scope' => 'formulize'),
                        array('name' => 'ta_cols', 'scope' => 'formulize'),
                    ),
                    'Numbers' => array(
                        array('name' => 'number_decimals', 'scope' => 'formulize'),
                        array('name' => 'number_prefix', 'scope' => 'formulize'),
                        array('name' => 'number_suffix', 'scope' => 'formulize'),
                        array('name' => 'number_decimalsep', 'scope' => 'formulize'),
                        array('name' => 'number_sep', 'scope' => 'formulize'),
                    ),
                    'Dates & display' => array(
                        array('name' => 'time_format', 'scope' => 'formulize'),
                        array('name' => 'delimeter', 'scope' => 'formulize'),
                    ),
                    'Revision History' => array(
                        array('name' => 'formulizeRevisionsForAllForms', 'scope' => 'formulize'),
                    ),
                    'Export' => array(
                        array('name' => 'downloadDefaultToExcel', 'scope' => 'formulize'),
                        array('name' => 'exportIntroChar', 'scope' => 'formulize'),
                    ),
                    'Form display' => array(
                        array('name' => 'show_empty_elements_when_read_only', 'scope' => 'formulize'),
                        array('name' => 'formulizeShowPrintableViewButtons', 'scope' => 'formulize'),
                        array('name' => 'printviewStylesheets', 'scope' => 'formulize'),
                        array('name' => 'heading_help_link', 'scope' => 'formulize'),
                    ),
                    'Lists' => array(
                        array('name' => 'LOE_limit', 'scope' => 'formulize'),
                    ),
                ),
            ),

            'messaging' => array(
                'name' => 'Messaging',
                'type' => 'settings',
                'sections' => array(
                    'Email delivery' => array(
                        array('name' => 'mailmethod', 'scope' => 'mailer'),
                        array('name' => 'smtphost', 'scope' => 'mailer', 'showWhen' => array('name' => 'mailmethod', 'value' => array('smtp', 'smtpauth'))),
                        array('name' => 'smtpuser', 'scope' => 'mailer', 'showWhen' => array('name' => 'mailmethod', 'value' => array('smtp', 'smtpauth'))),
                        array('name' => 'smtppass', 'scope' => 'mailer', 'showWhen' => array('name' => 'mailmethod', 'value' => array('smtp', 'smtpauth'))),
                        array('name' => 'smtpsecure', 'scope' => 'mailer', 'showWhen' => array('name' => 'mailmethod', 'value' => array('smtp', 'smtpauth'))),
                        array('name' => 'smtpauthport', 'scope' => 'mailer', 'showWhen' => array('name' => 'mailmethod', 'value' => array('smtp', 'smtpauth'))),
                        array('name' => 'sendmailpath', 'scope' => 'mailer', 'showWhen' => array('name' => 'mailmethod', 'value' => 'sendmail')),
                        array('name' => 'fromname', 'scope' => 'mailer', 'caption' => 'Sender name'),
                    ),
                    'Text messages (SMS)' => array(
                        array('name' => 'sms_provider', 'scope' => 'mailer', 'caption' => 'SMS provider'),
                        array('name' => 'sms_account_sid', 'scope' => 'mailer', 'caption' => 'Account SID / API key'),
                        array('name' => 'sms_auth_token', 'scope' => 'mailer', 'caption' => 'Auth token / API secret'),
                        array('name' => 'sms_from_number', 'scope' => 'mailer', 'caption' => 'From number / sender ID'),
                    ),
                    'Notifications' => array(
                        array('name' => 'notifyByCron', 'scope' => 'formulize'),
                    ),
                ),
            ),

            'ai' => array(
                'name' => 'AI',
                'type' => 'settings',
                'sections' => array(
                    'AI' => array(
                        array('name' => 'formulizeAIAssistantEnabled', 'scope' => 'formulize'),
                        array(
                            'name' => 'formulizeAIAssistantGroups',
                            'scope' => 'formulize',
                            'showWhen' => array('name' => 'formulizeAIAssistantEnabled', 'value' => 1),
                        ),
                        array('name' => 'formulizeMCPServerEnabled', 'scope' => 'formulize'),
                        array('name' => 'system_specific_instructions', 'scope' => 'formulize'),
                    ),
                ),
            ),

						'advanced' => array(
                'name' => 'Advanced',
                'type' => 'settings',
                'sections' => array(
                    'Sessions & cookies' => array(
                        array('name' => 'session_name', 'scope' => 'system', 'caption' => 'Session cookie name'),
                        array('name' => 'session_expire', 'scope' => 'system', 'caption' => 'Session timeout (minutes)'),
                        array('name' => 'cookie_samesite', 'scope' => 'system', 'caption' => 'Session cookie SameSite policy'),
                    ),
                    'Custom URLs' => array(
                        array('name' => 'formulizeRewriteRulesEnabled', 'scope' => 'formulize'),
                    ),
                    'Public API' => array(
                        array('name' => 'formulizePublicAPIEnabled', 'scope' => 'formulize'),
                    ),
                    'Debugging' => array(
                        array('name' => 'debug_mode', 'scope' => 'system', 'caption' => 'Debug mode'),
                        array('name' => 'theme_fromfile', 'scope' => 'system', 'caption' => 'Bypass template cache (load templates from files every time)'),
                        array('name' => 'debugDerivedValues', 'scope' => 'formulize'),
                        array('name' => 'logProcedure', 'scope' => 'formulize'),
                        array('name' => 'validateCode', 'scope' => 'formulize'),
                    ),
                    "Basement (don't touch unless you really have to)" => array(
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

            'permissions' => array(
                'name' => 'Copy Permissions',
                'type' => 'page',
                'page' => 'managepermissions',
            ),

        ),
    ),

);
