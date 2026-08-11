<?php
/**
 * Public site-discovery endpoint for the Formulize mobile app.
 *
 * The app asks the user "What is the name of your Formulize site?" and turns their answer into a
 * URL. This endpoint is how the app finds out whether a real Formulize site is actually there,
 * before asking for any credentials, so a typo produces "We couldn't find a Formulize site with
 * that name" rather than an incomprehensible network error at sign-in time.
 *
 * DELIBERATELY UNAUTHENTICATED, and therefore deliberately dull: it returns only the site name and
 * logo, both of which any visitor already sees on the site's own login page. It must never grow to
 * expose anything about users, forms, applications or configuration - that is what the
 * session-protected endpoints are for.
 */

// The app has to be able to discover and sign in to a site that has been closed to the public,
// so that an administrator can still reach it. Without this, the CMS bootstrap would render the
// "site closed" HTML page and the app would receive that instead of JSON. login.php enforces the
// closed-site access rule properly, the same way include/checklogin.php does.
$xoopsOption['ignore_closed_site'] = true;

require_once "../../../mainfile.php";
include_once XOOPS_ROOT_PATH . '/modules/formulize/app/common.php';
formulize_app_bootstrap();

global $icmsConfig;

// Whether the site has 2FA switched on at all. The app uses this only to decide how to word its
// sign-in screen; whether a *particular account* needs 2FA is decided by login.php, because that
// depends on the user and cannot be revealed before the password has been checked.
$config_handler = icms::handler('icms_config');
$tfaEnabled = false;
if ($auth_2fa = $config_handler->getConfigs(new Criteria('conf_name', 'auth_2fa'))) {
    $tfaEnabled = (bool) $auth_2fa[0]->getConfValueForOutput();
}

formulize_app_respond(array(
    'status' => 'ok',
    'formulize' => true,
    'site_name' => $icmsConfig['sitename'],
    'site_url' => XOOPS_URL,
    'tfa_enabled' => $tfaEnabled,
    // Colours, font and logo from the Formulize Appearance settings, so the app can style its own
    // native sign-in screen to look like this site. Null on a Formulize that predates those
    // settings, which the app must treat as "use your own defaults". See formulize_app_appearance().
    'appearance' => formulize_app_appearance(),
));
