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

// After the settings on Appearance > Settings (see 'include' in include/configsettings_registry.php): the questions
// about Lyris, for the first time the site's theme is changed to it there. They're drawn in a dialog that saving the
// settings opens, when the site's theme has been changed to Lyris. See admin/lyris_update_wizard.php, and
// include/lyris_update.php for when they're asked.

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/lyris_update.php';

// The site's theme is recorded as one that has been the site's theme. Changing the theme records it too, but a new
// site starts on Lyris without changing it, and has to change its theme here, so this is where that's recorded.
formulize_markThemeActivated(formulize_storedSiteTheme('theme_set'));

if (formulize_lyrisSwitchWizardApplies()) {
    $lyrisWizardContext = 'switch';
    include XOOPS_ROOT_PATH . '/modules/formulize/admin/lyris_update_wizard.php';
}
