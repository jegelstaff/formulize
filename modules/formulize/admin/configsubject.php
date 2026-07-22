<?php

// Generic handler for a registry-declared SUBJECT tab (see
// include/configsettings_registry.php). ui.php routes here whenever ?page matches
// a subject slug. A subject has sub-views (rendered as a secondary nav); each view
// is either:
//   - type 'settings': a page of config settings, saved by delegating to the
//     system preferences handler (preserving all its side-effects/validation), or
//   - type 'page': an existing admin page relocated under this subject.

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
$isPageView = (isset($view['type']) AND $view['type'] === 'page');

// Webmaster-only applies to 'settings' views, which we render/save ourselves here.
// A 'page' view hands off to its own controller file below, which enforces whatever
// permission model it wants (they generally already do their own checks).
if(!$isPageView) {
    global $xoopsUser;
    if(!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
        return;
    }
}

if($isPageView) {
    // An existing admin page relocated under this subject: include its controller
    // (which populates $adminPage) and render its template inside our wrapper.
    // The controller enforces its own permission model. IMPORTANT: a `return;` inside
    // an included file only exits that file, not this one - execution here would
    // otherwise carry on and render the page chrome (subnav, breadcrumbs, template)
    // regardless of the controller's denial. PHP does give us a way to detect this
    // though: include() yields the included file's return value, defaulting to int(1)
    // when the file runs to completion without an explicit `return`. So a denied
    // controller's bare `return;` (value NULL) is distinguishable from a normal
    // completion, and we bail out here too rather than building any chrome.
    $pageSlug = $view['page'];
    $pageFile = XOOPS_ROOT_PATH . "/modules/formulize/admin/" . $pageSlug . ".php";
    $pageResult = true;
    if(file_exists($pageFile)) {
        $pageResult = include $pageFile;
    }
    if($pageResult === null) {
        return;
    }
}

// Build the secondary navigation for this subject. Only reached once the active
// view's own access check (if any, per above) has passed.
$subnav = array();
foreach($subject['views'] as $viewSlug => $viewDef) {
    $subnav[] = array(
        'name' => $viewDef['name'],
        'url' => 'ui.php?page=' . $subjectSlug . '&view=' . $viewSlug,
        'active' => ($viewSlug === $activeViewSlug),
    );
}
$adminPage['subnav'] = $subnav;

if($isPageView) {
    $adminPage['subview_template'] = 'db:admin/' . $view['page'] . '.html';
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

// A 'page' sub-view can add its own trailing crumbs (e.g. the Theme Editor appends
// the file currently being edited) by populating $adminPage['extra_breadcrumbs'] with
// an array of array('text'=>..., 'url'=>optional) entries.
if(!empty($adminPage['extra_breadcrumbs']) AND is_array($adminPage['extra_breadcrumbs'])) {
    $crumbIndex = 3;
    foreach($adminPage['extra_breadcrumbs'] as $extraCrumb) {
        if(!isset($extraCrumb['text']) OR $extraCrumb['text'] === '') {
            continue;
        }
        $crumbIndex++;
        $breadcrumbtrail[$crumbIndex]['text'] = $extraCrumb['text'];
        if(isset($extraCrumb['url']) AND $extraCrumb['url'] !== '') {
            $breadcrumbtrail[$crumbIndex]['url'] = $extraCrumb['url'];
        }
    }
}
