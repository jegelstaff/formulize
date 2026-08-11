<?php
/**
 * The site menu, as data, for the app to draw as native mobile UI.
 *
 * This is the endpoint that makes the app feel like an app rather than a website in a box. The
 * WebView shows page content only; the sidebar and the account links that normally come from
 * themes/<theme>/theme.html are stripped out (see header.php's app-mode branch) and redrawn here
 * as a native drawer.
 *
 * IT DOES NOT BUILD A MENU OF ITS OWN. formulize_buildMenuData() in blocks/mymenu.php is the same
 * function the website's sidebar block calls, so the app menu and the web menu are the same menu -
 * same applications, same links, same permission filtering, same active-item logic. If they could
 * be built separately they would eventually disagree, and the app would start showing people
 * things they cannot open.
 *
 * The account section below is the other half of the chrome app mode removes. Its items are
 * Formulize and ImpressCMS features (private messages, account editing, the admin UI, signing
 * out), NOT things any particular theme invented, so they are decided here from the same
 * conditions the platform uses - never by reading what some theme happens to render.
 */

require_once "../../../mainfile.php";
include_once XOOPS_ROOT_PATH . '/modules/formulize/app/common.php';
formulize_app_bootstrap();

// Explicitly require a signed-in user. The 2013-era mobile endpoint this replaces
// (modules/formulize/app_list.php, now deleted) did not, relying entirely on permission filtering
// inside the handlers it called - too thin a guarantee to rest a data endpoint on.
$user = formulize_app_requireSession();

global $icmsConfig;

include_once XOOPS_ROOT_PATH . '/modules/formulize/blocks/mymenu.php';
if (!defined('_AM_NOFORMS_AVAIL')) {
    include_once XOOPS_ROOT_PATH . '/modules/formulize/language/english/main.php';
}

// The HTML half of the return value is for the website's sidebar; the app wants only the data.
list($unusedHtml, $menuData) = formulize_buildMenuData();

// The AI assistant entry is decided here rather than being taken on trust from the menu builder.
//
// drawAIAssistantMenuSection() suppresses itself on some themes, because a theme may present the
// assistant in its own way rather than as a sidebar item. That is a reasonable decision for a web
// page and a wrong one for the app: the app draws its own menu, so a theme opting out of rendering
// the link would silently remove the assistant from the app altogether. Whether the assistant is
// available is a permission-and-configuration question (isAIAssistantEnabled(), which checks the
// module preference and the user's groups), not a styling question.
//
// So: if the assistant is enabled for this user and the menu does not already contain it, add it.
$aiUrl = XOOPS_URL . '/ai/';
if (isAIAssistantEnabled()) {
    $alreadyPresent = false;
    foreach ($menuData as $section) {
        if (isset($section['url']) && rtrim($section['url'], '/') === rtrim($aiUrl, '/')) {
            $alreadyPresent = true;
            break;
        }
    }
    if (!$alreadyPresent) {
        $menuData[] = array(
            'url' => $aiUrl,
            'title' => 'Use AI',
            'active' => 0,
            'target' => '',
            'icon' => '',
        );
    }
}

// Account items. Kept separate from $menuData because the app presents them differently - pinned
// at the bottom of the drawer rather than listed among the applications.
$account = array();

// Inbox, gated exactly as the theme gates it: notify_method == 1 means the user has chosen to
// receive private messages in the site inbox. The unread count query is the one from footer.php.
if ($user->getVar('notify_method') == 1) {
    $pm_handler = icms::handler('icms_data_privmessage');
    $criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('to_userid', $user->getVar('uid')));
    $criteria->add(new icms_db_criteria_Item('read_msg', 0));
    $account[] = array(
        'key' => 'inbox',
        'title' => 'Inbox',
        'url' => XOOPS_URL . '/viewpmsg.php',
        'badge' => intval($pm_handler->getCount($criteria)),
    );
}

$account[] = array(
    'key' => 'account',
    'title' => 'Edit Account',
    'url' => XOOPS_URL . '/edituser.php',
    'badge' => 0,
);

// Admin, gated by the same isAdmin() call the theme uses for $xoops_isadmin.
if ($user->isAdmin()) {
    $account[] = array(
        'key' => 'admin',
        'title' => 'Admin',
        'url' => XOOPS_URL . '/modules/formulize/admin/ui.php',
        'badge' => 0,
    );
}

// Sign out is listed for the app to render, but carries no URL on purpose. The app must call
// logout.php rather than navigating the WebView to /user.php?op=logout, because signing out has to
// revoke the device token as well as end the session - otherwise the app would silently sign the
// user straight back in on the next launch.
$account[] = array(
    'key' => 'logout',
    'title' => 'Logout',
    'url' => '',
    'badge' => 0,
);

formulize_app_respond(array(
    'status' => 'ok',
    'site_name' => $icmsConfig['sitename'],
    'user' => array(
        'uid' => intval($user->getVar('uid')),
        'name' => $user->getVar('uname'),
        'is_admin' => (bool) $user->isAdmin(),
    ),
    // Applications and their links, plus the Users and Groups and AI sections when the user is
    // entitled to them - all decided inside formulize_buildMenuData().
    'menu' => $menuData,
    'account' => $account,
    // Re-sent on every menu fetch, not just at sign-in, so a change to the site's Appearance
    // settings reaches the app's native chrome without the user signing out and back in.
    'appearance' => formulize_app_appearance(),
));
