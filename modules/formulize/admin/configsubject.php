<?php

// Generic handler for a registry-declared SUBJECT tab (see
// include/configsettings_registry.php). ui.php routes here whenever ?page matches
// a subject slug. A subject has sub-views (rendered as a secondary nav); each view
// is either:
//   - type 'settings': a page of config settings, saved by delegating to the
//     system preferences handler (preserving all its side-effects/validation), or
//   - type 'page': an existing admin page relocated under this subject.

// only webmasters can interact with this page!
global $xoopsUser;
if(!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
    return;
}

// ui.php only routes here for registry subject slugs, so 'page' is always set;
// resolve it (an unknown/empty slug just yields no subject and returns below).
$subjectSlug = isset($_GET['page']) ? $_GET['page'] : '';
$subject = formulize_getConfigSubject($subjectSlug);
if(!$subject) {
    return;
}

$requestedView = isset($_GET['view']) ? $_GET['view'] : '';
$resolved = formulize_resolveConfigView($subject, $requestedView);
if(!$resolved) {
    return;
}
$activeViewSlug = $resolved['slug'];
$view = $resolved['view'];

// Build the secondary navigation for this subject.
$subnav = array();
foreach($subject['views'] as $viewSlug => $viewDef) {
    $subnav[] = array(
        'name' => $viewDef['name'],
        'url' => 'ui.php?page=' . $subjectSlug . '&view=' . $viewSlug,
        'active' => ($viewSlug === $activeViewSlug),
    );
}
$adminPage['subnav'] = $subnav;

// Render the active view.
if(isset($view['type']) AND $view['type'] === 'page') {
    // An existing admin page relocated under this subject: include its controller
    // (which populates $adminPage) and render its template inside our wrapper.
    $pageSlug = $view['page'];
    $pageFile = XOOPS_ROOT_PATH . "/modules/formulize/admin/" . $pageSlug . ".php";
    if(file_exists($pageFile)) {
        include $pageFile;
    }
    $adminPage['subview_template'] = 'db:admin/' . $pageSlug . '.html';
} else {
    // A settings view: render the config form (saved via delegation).
    $redirectUrl = XOOPS_URL . "/modules/formulize/admin/ui.php?page=" . $subjectSlug . "&view=" . $activeViewSlug;
    $sections = isset($view['sections']) ? $view['sections'] : array();
    $adminPage['settingsForm'] = formulize_renderConfigSettingsForm($sections, $redirectUrl);
    $adminPage['subview_template'] = 'db:admin/configsettings.html';
    // Render the standard admin save toolbar (above the tabs), bound to submit the
    // settings form by its id. Same look/position as the regular Formulize Save button.
    $adminPage['needsave'] = true;
    $adminPage['settingsSaveFormId'] = 'formulize-config-settings-form';
}

$adminPage['home_tabs'] = getHomeTabs($subjectSlug);

$breadcrumbtrail = array();
$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['url'] = "page=" . $subjectSlug;
$breadcrumbtrail[2]['text'] = $subject['name'];
$breadcrumbtrail[3]['text'] = $view['name'];
