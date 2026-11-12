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

// The answers to the questions asked when the site's theme is changed to Lyris on Appearance > Settings
// (admin/lyris_switch_wizard.php): copy the chosen templates, give the chosen screens Lyris's settings, start Lyris
// with the chosen look and the site's logo, then make Lyris the site's theme. The page saves the rest of the
// settings after this, when it prints nothing, which is how a save handler says it worked. Anything it prints is
// what went wrong, and then the site's theme hasn't changed. See include/lyris_update.php.

// if we aren't coming from what appears to be save.php, then return nothing
if (!isset($processedValues)) {
    return;
}

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/lyris_update.php';

// only webmasters can change the site's theme, and the questions are only asked once
if (!formulize_lyrisSwitchWizardApplies()) {
    print _AM_FORMULIZE_LYRIS_SWITCH_NOT_ASKED;
    return;
}
if (!icms::$security->check(true, false, 'formulize_lyris_switch_token')) {
    print _AM_FORMULIZE_LYRIS_SWITCH_TOKEN_FAILED;
    return;
}

// what was done is said by the Settings page, so only what went wrong is printed
ob_start();
$lyrisSwitched = formulize_lyrisUpdateApply(formulize_lyrisSwitchReadChoices($_POST));
$lyrisProblems = ob_get_clean();
if (!$lyrisSwitched) {
    print $lyrisProblems !== '' ? $lyrisProblems : _AM_FORMULIZE_LYRIS_SWITCH_FAILED;
}
