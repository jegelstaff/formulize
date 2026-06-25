<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################
/**
 * Formulize admin "settings tabs" registry — DATA ONLY.
 *
 * Edit this file to declare settings tabs, decide which preferences appear on
 * each tab, group them into sections, and give them friendly captions and
 * descriptions. No code changes are needed: the tabs declared here are rendered
 * automatically into the Formulize admin homepage (see getHomeTabs() in
 * admin/ui.php, the generic handler admin/configsettings.php, and the shared
 * template templates/admin/configsettings.html).
 *
 * Structure:
 *
 *   '<page-slug>' => array(
 *       'name' => 'Tab Label',                 // shown on the tab
 *       'sections' => array(
 *           'Section Heading' => array(        // heading shown above the group ('' = no heading)
 *               array(
 *                   'name'        => '<conf_name>',   // the config setting's conf_name
 *                   'scope'       => 'auth',          // where the setting lives (see below)
 *                   'caption'     => '...',           // optional: overrides the system label
 *                   'description' => '...',           // optional: overrides the system help text
 *               ),
 *               // ...more settings...
 *           ),
 *           // ...more sections...
 *       ),
 *   ),
 *
 * scope values:
 *   'system'    -> ImpressCMS/XOOPS general settings   (e.g. debug mode, template checking)
 *   'user'      -> User settings category              (e.g. password encryption)
 *   'mailer'    -> Email/SMTP settings category        (alias: 'email')
 *   'auth'      -> Authentication settings category    (e.g. 2FA, login method)
 *   'formulize' -> Formulize module preferences        (existing labels are usually fine)
 *
 * Notes:
 *   - The page-slug must be unique and must NOT collide with an existing admin
 *     page file (home, managekeys, mailusers, config-sync, synchronize,
 *     managepermissions, managetokens, logviewer).
 *   - Omit 'caption'/'description' to fall back to the system's original text.
 *   - Saving is delegated to the system preferences handler, so every side-effect
 *     and validation still fires exactly as on the legacy preferences page.
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

return array(

    'users' => array(
        'name' => 'Users',
        'sections' => array(
            'Authentication' => array(
                array(
                    'name' => 'auth_2fa',
                    'scope' => 'auth',
                    'caption' => 'Require two-factor authentication (2FA)',
                    'description' => 'When enabled, users can secure their accounts with a second verification step at login. Use the setting below to require it for specific groups of users.',
                ),
                array(
                    'name' => 'auth_2fa_groups',
                    'scope' => 'auth',
                    'caption' => 'Groups required to use two-factor authentication',
                    'description' => 'Members of the selected groups will be required to set up and use two-factor authentication. (Has no effect unless 2FA is enabled above.)',
                ),
            ),
        ),
    ),

);
