<?php
/**
 * Formulize Log Viewer
 * Admin page for viewing and filtering system log files
 */

// Only webmasters can access this page
global $xoopsUser;
if(!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
    return;
}
// Set the page chrome up front, so the several early returns below (logging off,
// no log dir, no files) still render with the admin tabs and breadcrumb.
$adminPage['home_tabs'] = getHomeTabs('themeeditor');
$breadcrumbtrail[1]['url'] = "page=themeeditor";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = "Theme Editor";
$adminPage['test'] = "Hello!";






