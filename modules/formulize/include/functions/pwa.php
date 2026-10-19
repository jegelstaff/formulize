<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################

// The site installed as an app on a phone or computer (a progressive web app): the gate that
// decides which pages get the app's features, the manifest, the icons and colours, the offline
// page, and the tags footer.php adds to every page's head. See pwa-plan.md at the root of the
// repository.
//
// Part of functions.php, which requires this file at its end. It is a file of its own only to
// keep functions.php to a size that can be worked with: the functions are global, and are
// available wherever functions.php is.

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

/**
 * Determine if this page gets the features that let people install the site as an app on their
 * phone or computer (a progressive web app).
 *
 * Every one of those features asks this, and nothing tests the conditions itself:
 *  - the manifest link, the theme-color and apple-touch-icon tags, and the script that registers the
 *    service worker, all added to the head by formulize_addAppHeadTags()
 *  - the install item at the end of the Formulize menu (formulize_pwaInstallMenuSection()), which
 *    pwa.js shows, and pwa.js is only on the pages where this is true
 *  - when push notifications are added, the item that turns them on for a device
 * A feature added later belongs on this list.
 *
 * The conditions:
 *  - the formulizePWAEnabled preference is on
 *  - the site is in a secure context, because browsers only run service workers there: https, or
 *    localhost for development
 *  - the page is not embedded in another website, and is not in a frame at all. An embedded screen
 *    is somebody else's page, and must not offer itself for installing or register a service worker.
 *    formulize_isEmbeddedRequest() can be asked here because this only decides what a page shows:
 *    a visitor who types formulize_embed=1 loses nothing but the manifest.
 *
 * The native app will add that the page is not being shown in the app (formulize_isAppRequest()).
 *
 * @return bool TRUE if this page gets the app features
 */
function formulize_pwaFeaturesApply() {
    static $apply = null;
    if ($apply === null) {
        $apply = (formulize_pwaEnabled()
            AND formulize_pwaSecureContext()
            AND !formulize_isEmbeddedRequest()
            AND !formulize_isAuthoritativelyEmbeddedRequest());
    }
    return $apply;
}

/**
 * Whether an administrator has let people install this site as an app. Only the preference: pages
 * ask formulize_pwaFeaturesApply(). The service worker script asks this directly, because when it is
 * off the script has to remove the worker from the devices that already have it.
 *
 * @return bool
 */
function formulize_pwaEnabled() {
    return (bool) formulize_moduleConfigValue('formulizePWAEnabled');
}

/**
 * Whether browsers will run a service worker for this site: it is served over https, or it is on
 * localhost, which browsers treat as secure so that it can be developed without a certificate.
 *
 * @return bool
 */
function formulize_pwaSecureContext() {
    if (icms_core_Session::siteIsSecure()) {
        return true;
    }
    $host = strtolower((string) parse_url(XOOPS_URL, PHP_URL_HOST));
    return ($host == 'localhost' OR $host == '127.0.0.1' OR $host == '[::1]' OR substr($host, -10) == '.localhost');
}

/**
 * The path of the site from the root of its host, with a slash at the end: '/' for a site at the root
 * of its domain, '/formulize/' for one in a folder.
 *
 * The app's manifest and service worker use this, never XOOPS_URL. XOOPS_URL is built from the name
 * the server was reached by, so a site reached under two host names would write the wrong one into
 * the app on one of them. The path also keeps two sites in folders side by side on one host from
 * claiming each other's pages.
 *
 * @return string
 */
function formulize_pwaSitePath() {
    return rtrim((string) parse_url(XOOPS_URL, PHP_URL_PATH), '/') . '/';
}

/**
 * A URL on this site as a path from the root of the host, for the same reason as
 * formulize_pwaSitePath(). URLs that are not on this site are returned as they are.
 *
 * @param string $url a URL that starts with XOOPS_URL, or a path relative to it that starts with /
 * @return string
 */
function formulize_pwaPath($url) {
    $base = rtrim(formulize_pwaSitePath(), '/');
    if (strpos($url, XOOPS_URL . '/') === 0) {
        return $base . substr($url, strlen(XOOPS_URL));
    }
    if (substr($url, 0, 1) == '/' AND substr($url, 0, 2) != '//') {
        return $base . $url;
    }
    return $url;
}

/**
 * The site's own theme, as the site is configured: the one visitors see, and the one the app is
 * made from. Not the theme this request is being drawn with, which can be the admin theme for a
 * webmaster (include/common.php puts the admin theme in place of the site's for them).
 *
 * @return string theme folder name, or '' if the site has no usable themes
 */
function formulize_pwaSiteTheme() {
    include_once XOOPS_ROOT_PATH . '/modules/formulize/include/appearance.php';
    $systemConfig = icms::$config->getConfigsByCat(ICMS_CONF);
    return formulize_resolveAppearanceTheme(isset($systemConfig['theme_set']) ? $systemConfig['theme_set'] : '');
}

/**
 * The colours of the installed app: the colour of the window's title bar, which is the colour of the
 * theme's top bar, and the colour of the screen the app opens on before the first page has drawn.
 *
 * Always the Appearance settings, for the look asked for, with the theme's own colour (or the design
 * system's) where the settings have none. Both Anari and Lyris draw their top bar in the Surface
 * colour, and the page in the Page background colour.
 *
 * The one place the app reads the Appearance settings, so that a change in how they are kept is
 * made here and nowhere else.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @param string|null $lookKey the look, defaults to the one applied to the site
 * @return array 'theme' => the title bar colour, 'background' => the opening screen colour, as hex
 */
function formulize_pwaColours($theme = null, $lookKey = null) {
    include_once XOOPS_ROOT_PATH . '/modules/formulize/include/appearance.php';
    $settings = formulize_getAppearanceLookSettings($theme, $lookKey);
    $map = formulize_appearanceColourMap($theme);
    $colours = array();
    foreach (array('theme' => 'surface', 'background' => 'background') as $use => $key) {
        $value = isset($settings['appearance_' . $key]) ? $settings['appearance_' . $key] : '';
        $colours[$use] = $value ? $value : $map[$key]['default'];
    }
    return $colours;
}

/**
 * The URL of one of the installed app's icons, as a path from the root of the host.
 *
 * A site's own icons are the five files below in uploads/pwa, put there by hand. When there is no
 * uploads/pwa/icon-512.png, the Formulize icons in modules/formulize/images/pwa are used instead. The
 * two sets are never mixed. Within a site's own set, a missing icon is replaced by icon-512.png, and
 * the browser scales it, except for a maskable icon: a maskable icon is cropped to the shape the phone
 * uses, so an ordinary icon in its place would lose its edges, and the icon is left out instead.
 *
 *   icon-512.png, icon-192.png                    the icon
 *   icon-maskable-512.png, icon-maskable-192.png  the icon inside the middle 80%, on a solid background
 *   apple-touch-icon.png                          180 pixels, on a solid background, for iPhones and iPads
 *
 * When the Appearance editor gets an App icon of its own, it is read here first.
 *
 * @param int $size 512, 192, or 180 for the iPhone and iPad icon
 * @param bool $maskable TRUE for the maskable version
 * @return string the path, with the file's version for cache busting, or '' when there is none
 */
function formulize_getPWAIconUrl($size, $maskable = false) {
    $size = intval($size);
    if ($size == 180) {
        $file = 'apple-touch-icon.png';
    } elseif ($size == 192 OR $size == 512) {
        $file = ($maskable ? 'icon-maskable-' : 'icon-') . $size . '.png';
    } else {
        return '';
    }
    $folder = file_exists(XOOPS_ROOT_PATH . '/uploads/pwa/icon-512.png') ? '/uploads/pwa' : '/modules/formulize/images/pwa';
    $path = $folder . '/' . $file;
    if (!file_exists(XOOPS_ROOT_PATH . $path)) {
        if ($maskable) {
            return '';
        }
        $path = $folder . '/icon-512.png';
    }
    return formulize_pwaPath($path) . '?v=' . formulize_get_file_version($path);
}

/**
 * The installed app's manifest: its name, its icons, its colours, and where it starts.
 *
 * @return array to be served as JSON
 */
function formulize_pwaManifest() {
    global $icmsConfig;
    $sitePath = formulize_pwaSitePath();
    $name = trim(strip_tags((string) $icmsConfig['sitename']));
    $shortName = trim(strip_tags((string) formulize_moduleConfigValue('formulizePWAShortName')));
    $colours = formulize_pwaColours(formulize_pwaSiteTheme());
    $icons = array();
    foreach (array(false, true) as $maskable) {
        foreach (array(192, 512) as $size) {
            if ($src = formulize_getPWAIconUrl($size, $maskable)) {
                $icons[] = array('src' => $src, 'sizes' => $size . 'x' . $size, 'type' => 'image/png', 'purpose' => ($maskable ? 'maskable' : 'any'));
            }
        }
    }
    return array(
        'id' => $sitePath,
        'name' => $name,
        'short_name' => ($shortName !== '' ? $shortName : $name),
        'start_url' => $sitePath,
        'scope' => $sitePath,
        // Chrome and Edge draw back and reload buttons in the app's title bar in minimal-ui, which also
        // show when a page is loading. Safari doesn't know display_override, and uses standalone: a
        // window with no buttons at all, where the theme's back button and formulize-app.js's loading
        // bar take their place.
        'display_override' => array('minimal-ui'),
        'display' => 'standalone',
        'theme_color' => $colours['theme'],
        'background_color' => $colours['background'],
        'icons' => $icons,
    );
}

/**
 * Load the language strings of the installed app, in the site's language where there are some.
 */
function formulize_pwaLoadLanguage() {
    global $xoopsConfig;
    $language = isset($xoopsConfig['language']) ? preg_replace('/[^a-z_]/i', '', $xoopsConfig['language']) : 'english';
    $file = XOOPS_ROOT_PATH . '/modules/formulize/language/' . $language . '/pwa.php';
    include_once (file_exists($file) ? $file : XOOPS_ROOT_PATH . '/modules/formulize/language/english/pwa.php');
}

/**
 * The page the installed app shows when a page can't be loaded because the device is offline.
 *
 * The service worker keeps it, with the stylesheets it is drawn with, so it can be shown with no
 * network at all. It is drawn with the site's theme and the site's Appearance settings, and nothing
 * on it belongs to anyone, so keeping it on a shared device leaves nothing behind.
 *
 * @return array 'html' => the page, 'stylesheets' => the paths of the stylesheets it uses, which the
 *               service worker keeps with it
 */
function formulize_pwaOfflinePage() {
    global $icmsConfig, $xoopsConfig;
    include_once XOOPS_ROOT_PATH . '/modules/formulize/include/appearance.php';
    formulize_pwaLoadLanguage();
    $theme = formulize_pwaSiteTheme();
    $links = '<link rel="stylesheet" type="text/css" media="all" href="' . XOOPS_URL . formulize_uiStylesheetPath() . '" />' . "\n";
    foreach (array('reset.css', 'style.css') as $stylesheet) {
        $path = '/themes/' . $theme . '/css/' . $stylesheet;
        if ($theme AND file_exists(XOOPS_ROOT_PATH . $path)) {
            $links .= '<link rel="stylesheet" type="text/css" media="all" href="' . XOOPS_URL . $path . '?v=' . formulize_get_file_version($path) . '" />' . "\n";
        }
    }
    // formulize_renderAppearanceHead() draws the theme the page is in, which this page has none of,
    // so it falls back to the configured default theme. Make that the site's own theme while it does.
    $configuredTheme = $xoopsConfig['theme_set'];
    $xoopsConfig['theme_set'] = $theme;
    $links .= formulize_renderAppearanceHead();
    $xoopsConfig['theme_set'] = $configuredTheme;
    $colours = formulize_pwaColours($theme);
    $siteName = htmlspecialchars(trim(strip_tags((string) $icmsConfig['sitename'])));
    $html = "<!DOCTYPE html>\n<html lang=\"" . htmlspecialchars(_LANGCODE) . "\">\n<head>\n"
        . "<meta charset=\"utf-8\" />\n"
        . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, viewport-fit=cover\" />\n"
        . "<meta name=\"theme-color\" content=\"" . htmlspecialchars($colours['theme']) . "\" />\n"
        . "<title>" . htmlspecialchars(_formulize_PWA_OFFLINE_TITLE) . " : " . $siteName . "</title>\n"
        . $links
        . "</head>\n<body class=\"fz-bg-page\">\n"
        . "<main class=\"fz-container fz-container--narrow fz-py-16\">\n"
        . "<div class=\"fz-empty\">\n"
        . "<h1 class=\"fz-empty__title\">" . htmlspecialchars(_formulize_PWA_OFFLINE_TITLE) . "</h1>\n"
        . "<p>" . sprintf(htmlspecialchars(_formulize_PWA_OFFLINE_TEXT), $siteName) . "</p>\n"
        . "<button type=\"button\" class=\"fz-btn fz-btn--primary\" onclick=\"window.location.reload()\">" . htmlspecialchars(_formulize_PWA_OFFLINE_RETRY) . "</button>\n"
        . "</div>\n</main>\n</body>\n</html>\n";
    // paths, never the host: the page is shown at whatever host the app was opened on
    $html = str_replace(XOOPS_URL . '/', rtrim(formulize_pwaSitePath(), '/') . '/', $html);
    $stylesheets = array();
    if (preg_match_all('/<link\b[^>]*\brel="stylesheet"[^>]*\bhref="([^"]+)"/i', $html, $matches)) {
        foreach ($matches[1] as $href) {
            $href = html_entity_decode($href);
            if (substr($href, 0, 1) == '/' AND substr($href, 0, 2) != '//') {
                $stylesheets[] = $href;
            }
        }
    }
    return array('html' => $html, 'stylesheets' => array_values(array_unique($stylesheets)));
}

/**
 * Add what every page needs in the head for the site to work as an installed app, and on the pages
 * where the site can be installed (formulize_pwaFeaturesApply()), what makes it installable.
 *
 * Called from footer.php just before the page is drawn, so the whole page has been built by then, and
 * the screen it shows, and that screen's look, are known. Done here rather than in the themes, so
 * every theme gets it, including the ones nobody will edit.
 *
 * On every page: formulize-app.js, which a page in an installed app needs whether or not the site can
 * still be installed (an app installed earlier stays on the device after the preference is turned off).
 * It keeps links that would open a new window inside the app, and in a window with no browser buttons,
 * shows the theme's back button and a bar while a page is loading.
 *
 * Where the site can be installed: the manifest, the colour of the window's title bar (the top bar
 * colour of the look this page is drawn with, so a screen with a look of its own has a title bar
 * that matches it), the iPhone and iPad icon, pwa.js for the install item in the menu, and the
 * registration of the service worker.
 */
function formulize_addAppHeadTags() {
    global $xoTheme;
    if (!is_object($xoTheme)) {
        return;
    }
    $sitePath = formulize_pwaSitePath();
    formulize_pwaLoadLanguage();
    $xoTheme->addScript('', array('type' => 'text/javascript'),
        'window.formulize = window.formulize || {}; window.formulize.appScope = ' . json_encode($sitePath) . ';'
        . ' window.formulize.appStrings = ' . json_encode(array('loading' => _formulize_PWA_LOADING)) . ';');
    $xoTheme->addScript(XOOPS_URL . '/modules/formulize/include/js/formulize-app.js?v=' . formulize_get_file_version('/modules/formulize/include/js/formulize-app.js'), array('type' => 'text/javascript'));
    if (!formulize_pwaFeaturesApply()) {
        return;
    }
    include_once XOOPS_ROOT_PATH . '/modules/formulize/include/appearance.php';
    $theme = formulize_getActiveAppearanceTheme();
    $colours = formulize_pwaColours($theme, formulize_appearancePageLook($theme));
    $xoTheme->addLink('manifest', formulize_pwaPath('/modules/formulize/pwa/manifest.php'));
    $xoTheme->addMeta('meta', 'theme-color', $colours['theme']);
    $xoTheme->addLink('apple-touch-icon', formulize_getPWAIconUrl(180));
    $config = array(
        'serviceWorker' => formulize_pwaPath('/modules/formulize/pwa/sw.php'),
        'scope' => $sitePath,
        'strings' => array(
            'installPhone' => _formulize_PWA_INSTALL_PHONE,
            'installDock' => _formulize_PWA_INSTALL_DOCK,
            'installComputer' => _formulize_PWA_INSTALL_COMPUTER,
            'installIOS' => _formulize_PWA_INSTALL_IOS,
            'installMacSafari' => _formulize_PWA_INSTALL_MAC_SAFARI,
            'close' => _formulize_PWA_INSTALL_CLOSE,
        ),
    );
    // registered once the page has loaded, so it never competes with the page being drawn
    $xoTheme->addScript('', array('type' => 'text/javascript'),
        'window.formulize.pwa = ' . json_encode($config) . ';'
        . " if ('serviceWorker' in navigator) { window.addEventListener('load', function () {"
        . " navigator.serviceWorker.register(window.formulize.pwa.serviceWorker, { scope: window.formulize.pwa.scope, updateViaCache: 'none' })"
        . ".catch(function (error) { if (window.console) { console.warn('The service worker for the installed app could not be registered:', error); } });"
        . ' }); }');
    $xoTheme->addScript(XOOPS_URL . '/modules/formulize/include/js/pwa.js?v=' . formulize_get_file_version('/modules/formulize/include/js/pwa.js'), array('type' => 'text/javascript'));
}

/**
 * The item at the end of the Formulize menu that installs the site as an app, or shows the steps for
 * the browsers that can't install it from a page. Only for people who are signed in, and only on the
 * pages where the site can be installed. Anyone else can still install it from the browser's own
 * menu, or from the banner Chrome shows on Android.
 *
 * It starts hidden. pwa.js shows it where the browser can install the site, gives it the words that
 * browser uses (home screen, Dock, or app), and never shows it in the installed app.
 *
 * Built for both menu templates, like the menu's other sections (see block_formulizeMENU_show()).
 * The old template's link goes in a <div>, so that the link's own class can't give it a display that
 * wins over hidden.
 *
 * @return array the HTML for the old menu template, and the item for templates/blocks/menu.html; or
 *               false and false when there is no item
 */
function formulize_pwaInstallMenuSection() {
    global $xoopsUser;
    if (!$xoopsUser OR !formulize_pwaFeaturesApply()) {
        return array(false, false);
    }
    formulize_pwaLoadLanguage();
    $title = htmlspecialchars(_formulize_PWA_INSTALL_COMPUTER);
    $block = "<div data-formulize-install-app hidden><a class=\"menuMain\" href=\"#\" role=\"button\">$title</a></div>";
    $data = array(
        'url' => '#',
        'title' => $title,
        'active' => 0,
        'target' => '',
        'icon' => '',
        // added to the <li> and its link by templates/blocks/menu.html
        'itemAttributes' => 'data-formulize-install-app hidden',
        'linkAttributes' => 'role="button"',
    );
    return array($block, $data);
}
