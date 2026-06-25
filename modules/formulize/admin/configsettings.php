<?php

// Generic handler for any registry-declared settings tab. The specific tab is
// determined by the 'page' parameter, which ui.php has already matched against
// the settings registry (see include/configsettings_registry.php) before routing
// here. Saving is delegated to the system preferences handler, preserving all of
// its side-effects and validation.

// only webmasters can interact with this page!
global $xoopsUser;
if(!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
    return;
}

$requestedPage = isset($_GET['page']) ? $_GET['page'] : '';
$tab = formulize_getConfigSettingsTab($requestedPage);
if(!$tab) {
    return;
}

$redirectUrl = XOOPS_URL . "/modules/formulize/admin/ui.php?page=" . $requestedPage;

$adminPage['settingsTitle'] = isset($tab['name']) ? $tab['name'] : '';
$adminPage['settingsForm'] = formulize_renderConfigSettingsForm($tab['sections'], $redirectUrl);
$adminPage['home_tabs'] = getHomeTabs($requestedPage);

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = $adminPage['settingsTitle'];
