<?php
/**
 * Language strings for the admin settings registry
 * (modules/formulize/include/configsettings_registry.php).
 *
 * Covers: subject-tab names, sub-view names, section headings,
 * and caption/description overrides used in the settings UI.
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// --- Subject tab names ---
define('_AM_CFG_TAB_USERS', 'Users');
define('_AM_CFG_TAB_APPEARANCE', 'Appearance');
define('_AM_CFG_TAB_SETTINGS', 'Settings');

// --- View names: Users tab ---
define('_AM_CFG_VIEW_USERS_SETTINGS', 'Settings');
define('_AM_CFG_VIEW_USERS_EMAIL', 'Email Users');
define('_AM_CFG_VIEW_USERS_APIKEYS', 'API Keys');
define('_AM_CFG_VIEW_USERS_TOKENS', 'Account Tokens');

// --- View names: Appearance tab ---
define('_AM_CFG_VIEW_APPEARANCE_THEMEEDITOR', 'Theme Editor');

// --- View names: Settings tab ---
define('_AM_CFG_VIEW_SETTINGS_ELEMENTS', 'Elements');
define('_AM_CFG_VIEW_SETTINGS_FORMS', 'Forms');
define('_AM_CFG_VIEW_SETTINGS_MESSAGING', 'Messaging');
define('_AM_CFG_VIEW_SETTINGS_AI', 'AI');
define('_AM_CFG_VIEW_SETTINGS_SYSTEM', 'System');
define('_AM_CFG_VIEW_SETTINGS_ADVANCED', 'Advanced');
define('_AM_CFG_VIEW_SETTINGS_PERMISSIONS', 'Copy Permissions');

// --- Section headings: Users > Settings ---
define('_AM_CFG_SEC_SIGNING_IN', 'Signing in');
define('_AM_CFG_SEC_NEW_USER_DEFAULTS', 'New users');

// --- Section headings: Settings > Elements ---
define('_AM_CFG_SEC_TEXTBOX_DEFAULTS', 'Textbox defaults');
define('_AM_CFG_SEC_NUMBER_BOX_DEFAULTS', 'Number box defaults');
define('_AM_CFG_SEC_TIME_DEFAULTS', 'Time defaults');
define('_AM_CFG_SEC_CHECKBOX_RADIO_DEFAULTS', 'Checkbox and radio button defaults');

// --- Section headings: Settings > Forms ---
define('_AM_CFG_SEC_FORM_DISPLAY', 'Form display');
define('_AM_CFG_SEC_REVISION_HISTORY', 'Revision History');
define('_AM_CFG_SEC_EXPORT', 'Export');
define('_AM_CFG_SEC_LISTS', 'Lists');

// --- Section headings: Settings > Messaging ---
define('_AM_CFG_SEC_EMAIL_DELIVERY', 'Email delivery');
define('_AM_CFG_SEC_TEXT_MESSAGES', 'Text messages (SMS)');
define('_AM_CFG_SEC_NOTIFICATIONS', 'Notifications');

// --- Section headings: Settings > AI ---
define('_AM_CFG_SEC_AI', 'AI');
define('_AM_CFG_SEC_AI_ADMIN_CONTROL', 'Set up the assistant for everyone');
define('_AM_CFG_SEC_AI_ADMIN_CONTROL_HELP', "<div class='formulize-config-help'>Leave these as <i>User Specified</i> and everyone configures the assistant for themselves, which is how it has always worked. Set them here instead to configure it once for the whole site, using your own API key. People will not be able to see the key or change what you choose.</div>");

// --- Section headings: Settings > System ---
define('_AM_CFG_SEC_IDENTITY', 'Identity');
define('_AM_CFG_SEC_CUSTOM_URLS', 'Custom URLs');
define('_AM_CFG_SEC_LOGGING', 'Logging');
define('_AM_CFG_SEC_DATABASE', 'Database');
define('_AM_CFG_SEC_DATE_TIME_FORMATS', 'Date & time formats');
define('_AM_CFG_SEC_SEO', 'Search engines (SEO)');
define('_AM_CFG_SEC_APPEARANCE', 'Appearance');
define('_AM_CFG_SEC_AVAILABILITY', 'Availability');

// --- Section headings: Settings > Advanced ---
define('_AM_CFG_SEC_PUBLIC_API', 'Public API');
define('_AM_CFG_SEC_SESSIONS_COOKIES', 'Sessions & cookies');
define('_AM_CFG_SEC_DEBUGGING', 'Debugging');
define('_AM_CFG_SEC_BASEMENT', "Basement (don't touch unless you really have to)");

// --- Caption overrides ---
define('_AM_CFG_CAP_FROMUID', 'Sender of private messages');
define('_AM_CFG_CAP_SERVER_TZ', 'Timezone used by the database server');
define('_AM_CFG_CAP_DEFAULT_TZ', 'Default timezone for new users');

// --- Description overrides ---
define('_AM_CFG_DESC_SERVER_TZ', 'This is the timezone that would be reported by <i>SELECT @@global.time_zone;</i> in MariaDB');
define('_AM_CFG_DESC_DATESTRING', 'Used for date-and-time display throughout Formulize.');
define('_AM_CFG_DESC_SHORTDATESTRING', 'Used for short date display.');
define('_AM_CFG_DESC_SHORTTIMESTRING', 'Used for short time display.');
define('_AM_CFG_DESC_FOOTER', 'Content for the footer of every page, if your theme supports this. HTML is allowed.');
define('_AM_CFG_DESC_FOOTADM', 'Content for the footer of every admin page, if your theme supports this. HTML is allowed.');
define('_AM_CFG_DESC_SESSION_NAME', 'The name of the session cookie');

// --- The site-wide AI API key field (formtype 'aikey') ---
define('_AM_CFG_AIKEY_PLACEHOLDER_NEW', 'Paste the API key here');
define('_AM_CFG_AIKEY_PLACEHOLDER_REPLACE', 'Paste a new key here to replace the saved one');
define('_AM_CFG_AIKEY_SAVED', 'A key is saved for this provider. It cannot be shown again - to change it, paste a new one above.');
define('_AM_CFG_AIKEY_NONE', 'No key is saved for this provider yet. Nobody can use the assistant until you add one.');
define('_AM_CFG_AIKEY_CLEAR', 'Remove the saved key');
define('_AM_CFG_AIKEY_SAVE_FAILED', 'The API key could not be saved, so nothing else was saved either:');

// --- The AI tool picker (formtype 'aitools') ---
define('_AM_CFG_AITOOLS_ALL', 'Select all');
define('_AM_CFG_AITOOLS_NONE', 'Select none');
define('_AM_CFG_AITOOLS_COUNT', '%s of %s selected');
define('_AM_CFG_AITOOLS_NONE_FOUND', 'No AI tools could be read from this system, so there is nothing to choose from.');

// --- Section help HTML: date/time format reference ---
define('_AM_CFG_HELP_DATE_TIME_FORMATS', "<details class='formulize-config-help'><summary>Show date/time format codes</summary><div class='formulize-config-help-codes'><b>Year:</b> Y=2026, y=26<br><b>Month:</b> m=06, n=6, M=Jun, F=June<br><b>Day:</b> d=05, j=5, D=Thu, l=Thursday<br><b>Hour:</b> H=14, G=14 (no leading zero), h=02, g=2 (12-hour)<br><b>Minutes:</b> i=05 &nbsp;&nbsp; <b>Seconds:</b> s=09<br><b>AM/PM:</b> a=pm, A=PM</div></details>");
