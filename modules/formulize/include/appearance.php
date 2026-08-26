<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) Formulize Project                        ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Project: Formulize                                                       ##
###############################################################################

// Appearance settings: colours, font, and logo, configured on the Appearance
// page in the Formulize admin UI, and rendered by themes as CSS custom property
// overrides on :root.
//
// Settings are kept per theme, in the theme's own generated stylesheet. That
// stylesheet, and any uploaded logo, live in an "appearance" folder inside the
// theme's folder, ie: themes/Lyris/appearance/. That folder is inside the web
// root and is not access-protected the way uploads/ usually is, so the files are
// reachable by the browser wherever the site is deployed.
//
// The generated stylesheet is the record of the settings: it carries them in a
// machine-readable block at the top of the file, which is what the Appearance
// page reads back. There is nothing else to keep in sync, per-theme settings
// fall out of per-theme files, and deleting a theme's appearance folder is all
// it takes to put that theme back to the defaults. See
// formulize_buildAppearanceSettingsBlock() and formulize_readAppearanceCssSettings().

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

/**
 * The colours users can set, with the design-system default for each, and the
 * CSS custom properties each one drives. Derived tokens use color-mix() so any
 * user-picked base colour produces coherent hover/soft/muted variants.
 *
 * @return array config name (without the appearance_ prefix) => array with
 *               'label', 'description', 'default' (hex), and 'tokens', a map of
 *               CSS custom property => value template where %s is the base colour
 */
function formulize_appearanceColourMap() {
    return array(
        'primary' => array(
            'label' => 'Primary',
            'description' => 'Buttons, links, selected rows, focus rings',
            'default' => '#2e3340',
            'tokens' => array(
                '--c-accent' => '%s',
                '--c-accent-fg' => '%s',
                '--c-accent-hover' => 'color-mix(in srgb, %s 85%%, #000)',
                '--c-accent-soft' => 'color-mix(in srgb, %s 8%%, #fff)',
                '--c-accent-soft-2' => 'color-mix(in srgb, %s 16%%, #fff)',
                '--c-border-focus' => 'color-mix(in srgb, %s 85%%, #000)',
                '--sh-focus' => '0 0 0 3px color-mix(in srgb, %s 15%%, transparent)',
            ),
        ),
        'background' => array(
            'label' => 'Page background',
            'description' => 'The backdrop behind all content',
            'default' => '#f7f7f5',
            'tokens' => array(
                '--c-bg' => '%s',
            ),
        ),
        'surface' => array(
            'label' => 'Surface',
            'description' => 'Cards, tables, panels and other raised areas',
            'default' => '#ffffff',
            'tokens' => array(
                '--c-surface' => '%s',
                '--c-surface-2' => 'color-mix(in srgb, %s 98%%, #555)',
                '--c-surface-3' => 'color-mix(in srgb, %s 94%%, #555)',
            ),
        ),
        'text' => array(
            'label' => 'Text',
            'description' => 'Main text colour; muted and subtle text are derived from it',
            'default' => '#14161a',
            'tokens' => array(
                '--c-text' => '%s',
                '--c-text-muted' => 'color-mix(in srgb, %s 65%%, #fff)',
                '--c-text-subtle' => 'color-mix(in srgb, %s 45%%, #fff)',
            ),
        ),
        'border' => array(
            'label' => 'Borders',
            'description' => 'Dividers and outlines; strong borders are derived',
            'default' => '#e6e6e1',
            'tokens' => array(
                '--c-border' => '%s',
                '--c-border-strong' => 'color-mix(in srgb, %s 90%%, #000)',
            ),
        ),
        'success' => array(
            'label' => 'Success',
            'description' => 'Positive statuses and badges',
            'default' => '#2a7a4f',
            'tokens' => array(
                '--c-success' => '%s',
                '--c-success-soft' => 'color-mix(in srgb, %s 12%%, #fff)',
            ),
        ),
        'warning' => array(
            'label' => 'Warning',
            'description' => 'Caution statuses and badges',
            'default' => '#a86a14',
            'tokens' => array(
                '--c-warning' => '%s',
                '--c-warning-soft' => 'color-mix(in srgb, %s 12%%, #fff)',
            ),
        ),
        'danger' => array(
            'label' => 'Danger',
            'description' => 'Errors, destructive actions',
            'default' => '#b3261e',
            'tokens' => array(
                '--c-danger' => '%s',
                '--c-danger-soft' => 'color-mix(in srgb, %s 12%%, #fff)',
            ),
        ),
        'info' => array(
            'label' => 'Info',
            'description' => 'Informational statuses and badges',
            'default' => '#2e5fa8',
            'tokens' => array(
                '--c-info' => '%s',
                '--c-info-soft' => 'color-mix(in srgb, %s 12%%, #fff)',
            ),
        ),
    );
}

/**
 * Curated font choices. 'google' is the family parameter for the Google Fonts
 * css2 API (false when no webfont needs loading), 'stack' is the CSS
 * font-family value for --font-sans.
 *
 * @return array font key => array with 'label', 'google', 'stack'
 */
function formulize_appearanceFontMap() {
    $fallback = "-apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif";
    return array(
        'geist' => array(
            'label' => 'Geist (default)',
            'google' => 'Geist:wght@400;500;600;700',
            'stack' => "'Geist', $fallback",
        ),
        'system' => array(
            'label' => 'System UI (no webfont)',
            'google' => false,
            'stack' => $fallback,
        ),
        'inter' => array(
            'label' => 'Inter',
            'google' => 'Inter:wght@400;500;600;700',
            'stack' => "'Inter', $fallback",
        ),
        'source-sans' => array(
            'label' => 'Source Sans 3',
            'google' => 'Source+Sans+3:wght@400;500;600;700',
            'stack' => "'Source Sans 3', $fallback",
        ),
        'ibm-plex' => array(
            'label' => 'IBM Plex Sans',
            'google' => 'IBM+Plex+Sans:wght@400;500;600;700',
            'stack' => "'IBM Plex Sans', $fallback",
        ),
        'nunito-sans' => array(
            'label' => 'Nunito Sans',
            'google' => 'Nunito+Sans:wght@400;500;600;700',
            'stack' => "'Nunito Sans', $fallback",
        ),
        'public-sans' => array(
            'label' => 'Public Sans',
            'google' => 'Public+Sans:wght@400;500;600;700',
            'stack' => "'Public Sans', $fallback",
        ),
        'work-sans' => array(
            'label' => 'Work Sans',
            'google' => 'Work+Sans:wght@400;500;600;700',
            'stack' => "'Work Sans', $fallback",
        ),
        'custom' => array(
            'label' => 'Other Google Font…',
            'google' => null, // built at runtime from the appearance_customfont setting
            'stack' => null,
        ),
    );
}

/**
 * The names of all the appearance settings, ie: the keys of a settings array
 *
 * @return array of setting names
 */
function formulize_appearanceSettingNames() {
    $names = array('appearance_font', 'appearance_customfont', 'appearance_logo');
    foreach (array_keys(formulize_appearanceColourMap()) as $key) {
        $names[] = 'appearance_' . $key;
    }
    return $names;
}

/**
 * A settings array with every setting empty, ie: every setting at its default.
 * An empty value always means "use the default", never "no value at all", so
 * defaults are free to evolve without every site being pinned to today's.
 *
 * @return array setting name => ''
 */
function formulize_defaultAppearanceSettings() {
    return array_fill_keys(formulize_appearanceSettingNames(), '');
}

/**
 * The themes installed in this site, as folder name => folder name. Same list
 * the Theme Editor and the system theme preference work from.
 *
 * @return array theme folder name => theme folder name
 */
function formulize_getAppearanceThemes() {
    static $themes = null; // scans the themes folder, and is asked for on every path lookup
    if ($themes === null) {
        $themes = icms_view_theme_Factory::getThemesList();
    }
    return $themes;
}

/**
 * The site's default theme, ie: the one the Appearance page starts on. Falls
 * back to the first installed theme if the configured one is gone (a theme can
 * be deleted from the server without the site config being updated).
 *
 * @return string theme folder name, or '' if the site has no usable themes
 */
function formulize_getDefaultAppearanceTheme() {
    global $xoopsConfig;
    $themes = formulize_getAppearanceThemes();
    $default = isset($xoopsConfig['theme_set']) ? $xoopsConfig['theme_set'] : '';
    if ($default AND isset($themes[$default])) {
        return $default;
    }
    return $themes ? reset($themes) : '';
}

/**
 * The theme the current page is actually being rendered with, which is the one
 * whose appearance settings and generated files apply right now. Users can be
 * on a theme other than the site default, so the live theme object is asked
 * first, falling back to the site default.
 *
 * @return string theme folder name, or '' if the site has no usable themes
 */
function formulize_getActiveAppearanceTheme() {
    $themes = formulize_getAppearanceThemes();
    if (isset($GLOBALS['xoTheme']) AND is_object($GLOBALS['xoTheme'])) {
        $folder = $GLOBALS['xoTheme']->folderName;
        if ($folder AND isset($themes[$folder])) {
            return $folder;
        }
    }
    return formulize_getDefaultAppearanceTheme();
}

/**
 * Resolve a theme name to work with: the one asked for when it is installed,
 * otherwise the theme rendering the site. Used everywhere a theme name can
 * come in from a request or from settings saved for a theme that has since
 * been deleted, so nothing downstream ever builds a path from a bogus name.
 *
 * @param string|null $theme requested theme folder name, or null for the active theme
 * @return string an installed theme's folder name, or '' if the site has no usable themes
 */
function formulize_resolveAppearanceTheme($theme = null) {
    if ($theme !== null AND $theme !== '') {
        $themes = formulize_getAppearanceThemes();
        if (isset($themes[$theme])) {
            return $theme;
        }
    }
    return formulize_getActiveAppearanceTheme();
}

/**
 * Whether a theme actually does anything with the appearance settings, ie: it
 * calls formulize_renderAppearanceHead() to pull in the generated stylesheet.
 * Themes that don't (older themes that aren't built on the Formulize design
 * tokens) can still have settings saved for them, but they won't show, so the
 * admin page says so rather than leaving the admin wondering.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return boolean
 */
function formulize_themeSupportsAppearance($theme = null) {
    static $support = array();
    $theme = formulize_resolveAppearanceTheme($theme);
    if (!isset($support[$theme])) {
        $themeFile = ICMS_THEME_PATH . '/' . $theme . '/theme.html';
        $support[$theme] = ($theme AND file_exists($themeFile)
            AND strpos(file_get_contents($themeFile), 'formulize_renderAppearanceHead') !== false);
    }
    return $support[$theme];
}

/**
 * Validate a user-supplied colour value. Only hex colours are accepted.
 *
 * @param string $value the submitted colour
 * @return string the normalized hex colour, or '' if invalid/empty
 */
function formulize_sanitizeAppearanceColour($value) {
    $value = trim((string) $value);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : '';
}

/**
 * Validate a user-supplied font family name. Google Font families are letters,
 * digits and spaces, and keeping to that also keeps the name safe to write into
 * a CSS comment and a Google Fonts URL.
 *
 * @param string $value the submitted family name
 * @return string the cleaned family name, or '' if nothing usable is left
 */
function formulize_sanitizeAppearanceFontFamily($value) {
    return trim(preg_replace('/[^a-zA-Z0-9 ]/', '', (string) $value));
}

/**
 * Normalize a set of appearance settings: every setting name present, every
 * value either valid or empty (meaning "use the default"). Anything that isn't
 * recognized is dropped rather than passed along.
 *
 * Everything that writes settings and everything that reads them goes through
 * this one function, which is what makes the settings survive a round trip
 * through the generated stylesheet: what is written out is exactly what is read
 * back in. It is also what keeps a hand-edited or damaged stylesheet from
 * pushing junk into the CSS or into the admin form.
 *
 * @param array $values setting name => raw value, any subset
 * @return array setting name => clean value (all setting names present)
 */
function formulize_sanitizeAppearanceSettings($values) {
    $clean = formulize_defaultAppearanceSettings();
    foreach (formulize_appearanceColourMap() as $key => $colour) {
        $value = formulize_sanitizeAppearanceColour(isset($values['appearance_' . $key]) ? $values['appearance_' . $key] : '');
        // nothing is recorded for a colour that is the design default, so the defaults can evolve
        $clean['appearance_' . $key] = ($value == $colour['default']) ? '' : $value;
    }
    $fonts = formulize_appearanceFontMap();
    $font = isset($values['appearance_font']) ? trim((string) $values['appearance_font']) : '';
    $customFont = formulize_sanitizeAppearanceFontFamily(isset($values['appearance_customfont']) ? $values['appearance_customfont'] : '');
    if (!isset($fonts[$font]) OR ($font == 'custom' AND $customFont === '')) {
        $font = 'geist'; // not a font we offer, or a custom font with no usable family name
    }
    $clean['appearance_font'] = ($font == 'geist') ? '' : $font;
    $clean['appearance_customfont'] = ($font == 'custom') ? $customFont : '';
    // the logo is a bare filename in the theme's appearance folder, never a path
    $logo = basename(trim((string) (isset($values['appearance_logo']) ? $values['appearance_logo'] : '')));
    $clean['appearance_logo'] = preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $logo) ? $logo : '';
    return $clean;
}

/**
 * The lines that delimit the settings block inside a generated stylesheet. The
 * start marker carries a format version, so a stylesheet written by a later
 * version of Formulize is not misread by this one: it simply doesn't match, and
 * the defaults apply until the file is regenerated.
 *
 * @return array with 'start' and 'end'
 */
function formulize_appearanceSettingsBlockMarkers() {
    return array(
        'start' => 'BEGIN FORMULIZE APPEARANCE SETTINGS v1',
        'end' => 'END FORMULIZE APPEARANCE SETTINGS',
    );
}

/**
 * Build the settings block that goes at the top of a theme's generated
 * stylesheet, as a CSS comment. This block is where a theme's appearance
 * settings are recorded, and formulize_readAppearanceCssSettings() reads them
 * back out of it.
 *
 * A comment block, rather than the custom properties further down the file, is
 * what carries the settings, for two reasons. The properties are derived (a
 * single colour produces half a dozen color-mix() tokens) so reversing them
 * would be guesswork, and not every setting is a CSS value in the first place:
 * the chosen font is a key into our own list, and the logo is a filename. One
 * block holds all of them in the form they were entered in.
 *
 * Only settings that differ from the defaults are written, so a theme left
 * alone gets an empty block, which reads back as "everything default".
 *
 * @param array $settings the settings to record, already sanitized
 * @param string $theme theme folder name, for the human-readable heading
 * @return string the CSS comment block, no trailing newline
 */
function formulize_buildAppearanceSettingsBlock($settings, $theme) {
    $markers = formulize_appearanceSettingsBlockMarkers();
    $lines = array(
        '/* Formulize appearance settings for the ' . preg_replace('/[^A-Za-z0-9._ -]/', '', (string) $theme) . ' theme.',
        ' *',
        ' * Generated from the Appearance page in the Formulize admin UI, and rewritten',
        ' * every time those settings are saved. The block below is where the settings',
        ' * themselves are kept, and it is what the Appearance page reads them back out',
        ' * of. If this file is deleted, or the block below is removed or damaged, the',
        ' * built-in defaults apply.',
        ' *',
        ' * ' . $markers['start'],
    );
    foreach (formulize_appearanceSettingNames() as $name) {
        if (isset($settings[$name]) AND $settings[$name] !== '') {
            $lines[] = ' * ' . $name . ': ' . $settings[$name];
        }
    }
    $lines[] = ' * ' . $markers['end'];
    $lines[] = ' */';
    return implode("\n", $lines);
}

/**
 * Read the appearance settings back out of a generated stylesheet.
 *
 * The whole block has to be there: if the start marker is missing the file was
 * not written by us (or predates the format), and if the end marker is missing
 * the file was truncated part way through the settings. Either way the answer
 * is "no settings here", so the caller falls back to the defaults rather than
 * applying whichever half of the settings happened to survive. Individual
 * values are validated by formulize_sanitizeAppearanceSettings(), so a single
 * mangled line costs that one setting and nothing else.
 *
 * @param string $path path of the stylesheet to read
 * @return array|false settings array, or false when the file has no usable settings block
 */
function formulize_readAppearanceCssSettings($path) {
    if (!$path OR !is_file($path)) {
        return false;
    }
    $css = @file_get_contents($path);
    if ($css === false) {
        return false;
    }
    $markers = formulize_appearanceSettingsBlockMarkers();
    $start = strpos($css, $markers['start']);
    $end = ($start === false) ? false : strpos($css, $markers['end'], $start);
    if ($start === false OR $end === false) {
        return false;
    }
    $values = array();
    foreach (explode("\n", substr($css, $start, $end - $start)) as $line) {
        if (preg_match('/^\s*\*?\s*(appearance_[a-z]+)\s*:\s*(.*?)\s*$/', $line, $match)) {
            $values[$match[1]] = $match[2];
        }
    }
    return formulize_sanitizeAppearanceSettings($values);
}

/**
 * The appearance settings recorded in the module config items, which is where
 * they were kept before the generated stylesheet became the record of them.
 *
 * Those config items are no longer written to. They are read only when a theme
 * has no generated stylesheet yet, so a site that upgrades keeps exactly the
 * colours, font and logo it already had, with no admin action: the next page
 * render generates the stylesheet from these values (see
 * formulize_renderAppearanceHead), and from then on the file is the record and
 * these items are never consulted for that theme again.
 *
 * The old values were site-wide, so they are read as the settings of the site's
 * default theme, which is the theme that is displaying them today. Other themes
 * start from the defaults.
 *
 * @param string $theme theme folder name
 * @return array settings array
 */
function formulize_getLegacyAppearanceSettings($theme) {
    if ($theme === '' OR $theme !== formulize_getDefaultAppearanceTheme()) {
        return formulize_defaultAppearanceSettings();
    }
    $config_handler = xoops_gethandler('config');
    $formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
    $values = array();
    foreach (formulize_appearanceSettingNames() as $name) {
        $values[$name] = isset($formulizeConfig[$name]) ? $formulizeConfig[$name] : '';
    }
    return formulize_sanitizeAppearanceSettings($values);
}

/**
 * A theme's appearance settings, read out of that theme's generated stylesheet,
 * falling back to the settings a pre-upgrade site left in the module configs,
 * and to the defaults. Empty string means "use the default". Cached per theme
 * for the request.
 *
 * @param string|null $theme theme folder name, defaults to the theme rendering the page
 * @return array setting name => value (all setting names present)
 */
function formulize_getAppearanceSettings($theme = null) {
    static $cache = array();
    $theme = formulize_resolveAppearanceTheme($theme);
    if (!isset($cache[$theme])) {
        $settings = formulize_readAppearanceCssSettings(formulize_getAppearanceCssPath($theme));
        $cache[$theme] = ($settings === false) ? formulize_getLegacyAppearanceSettings($theme) : $settings;
    }
    return $cache[$theme];
}

/**
 * Resolve the Google Fonts css2 URL and the --font-sans value for the current
 * settings. Geist Mono is always requested alongside, since --font-mono uses it.
 *
 * @param array|null $settings appearance settings to use, defaults to the saved ones
 * @return array with 'url' (string|false) and 'stack' (string|false when default)
 */
function formulize_getAppearanceFont($settings = null) {
    $settings = is_array($settings) ? $settings : formulize_getAppearanceSettings();
    $fonts = formulize_appearanceFontMap();
    $choice = isset($fonts[$settings['appearance_font']]) ? $settings['appearance_font'] : 'geist';
    $googleFamily = $fonts[$choice]['google'];
    $stack = $fonts[$choice]['stack'];
    if ($choice == 'custom') {
        $family = formulize_sanitizeAppearanceFontFamily($settings['appearance_customfont']);
        if ($family) {
            $googleFamily = str_replace(' ', '+', $family) . ':wght@400;500;600;700';
            $stack = "'" . $family . "', " . $fonts['system']['stack'];
        } else {
            // no usable custom family entered, fall back to the default font
            $choice = 'geist';
            $googleFamily = $fonts['geist']['google'];
            $stack = $fonts['geist']['stack'];
        }
    }
    $url = false;
    if ($googleFamily) {
        $url = 'https://fonts.googleapis.com/css2?family=' . $googleFamily . '&family=Geist+Mono:wght@400;500&display=swap';
    }
    return array(
        'url' => $url,
        'stack' => ($choice == 'geist') ? false : $stack, // false means the theme's own default applies
    );
}

/**
 * The folder where a theme's appearance artifacts live: the uploaded logo and
 * the generated appearance.css. Inside the theme's own folder, so both are
 * served directly as static files from a location that is publicly reachable
 * (unlike uploads/, which is commonly protected by the web server).
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string filesystem path of the appearance folder, no trailing slash
 */
function formulize_getAppearanceDir($theme = null) {
    return ICMS_THEME_PATH . '/' . formulize_resolveAppearanceTheme($theme) . '/appearance';
}

/**
 * The URL of a theme's appearance folder
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string URL of the appearance folder, no trailing slash
 */
function formulize_getAppearanceUrl($theme = null) {
    return ICMS_THEME_URL . '/' . formulize_resolveAppearanceTheme($theme) . '/appearance';
}

/**
 * The folder appearance artifacts were written to before they moved into the
 * theme folders. Still read from, so a site that has a logo uploaded under the
 * old scheme keeps showing it until a new one is uploaded. Never written to.
 *
 * @return string filesystem path of the legacy appearance folder, no trailing slash
 */
function formulize_getLegacyAppearanceDir() {
    return XOOPS_ROOT_PATH . '/uploads/appearance';
}

/**
 * The URL of the legacy appearance folder
 *
 * @return string URL of the legacy appearance folder, no trailing slash
 */
function formulize_getLegacyAppearanceUrl() {
    return XOOPS_URL . '/uploads/appearance';
}

/**
 * Deprecated aliases for formulize_getAppearanceDir()/Url(), from when there
 * was a single appearance folder in uploads/. Kept so any theme or custom code
 * calling them keeps working, and keeps pointing at the file that is actually
 * in use.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string path/URL of the appearance folder, no trailing slash
 */
function formulize_getAppearanceUploadDir($theme = null) {
    return formulize_getAppearanceDir($theme);
}
function formulize_getAppearanceUploadUrl($theme = null) {
    return formulize_getAppearanceUrl($theme);
}

/**
 * Locate one of a theme's appearance files, checking the theme's appearance
 * folder first and then the legacy uploads folder, so files written before the
 * move are still found.
 *
 * @param string $file  bare filename, as stored in the settings
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string path of the file, or '' if it isn't in either folder
 */
function formulize_locateAppearanceFile($file, $theme = null) {
    $file = basename((string) $file);
    if ($file === '') {
        return '';
    }
    foreach (array(formulize_getAppearanceDir($theme), formulize_getLegacyAppearanceDir()) as $dir) {
        if (file_exists($dir . '/' . $file)) {
            return $dir . '/' . $file;
        }
    }
    return '';
}

/**
 * The filesystem path of the uploaded custom logo, if any
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string path of the logo file, or '' when no custom logo is set
 */
function formulize_getAppearanceLogoPath($theme = null) {
    $settings = formulize_getAppearanceSettings($theme);
    // stored as a bare filename in the theme's appearance folder
    return formulize_locateAppearanceFile($settings['appearance_logo'], $theme);
}

/**
 * The URL of the uploaded custom logo, if any. A direct static URL, with the
 * file's modification time included for cache busting.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string URL of the logo, or '' when no custom logo is set
 */
function formulize_getAppearanceLogoUrl($theme = null) {
    $path = formulize_getAppearanceLogoPath($theme);
    if ($path) {
        $base = (strpos($path, formulize_getLegacyAppearanceDir() . '/') === 0)
            ? formulize_getLegacyAppearanceUrl()
            : formulize_getAppearanceUrl($theme);
        return $base . '/' . rawurlencode(basename($path)) . '?v=' . filemtime($path);
    }
    return '';
}

/**
 * The filesystem path of a theme's generated appearance stylesheet
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string path of the generated CSS file
 */
function formulize_getAppearanceCssPath($theme = null) {
    return formulize_getAppearanceDir($theme) . '/appearance.css';
}

/**
 * Make sure a theme's appearance folder exists and can be written to, creating
 * it and trying to correct the permissions if not. A theme folder that the web
 * server can't write is a normal state on a locked-down deployment, so this
 * reports failure rather than raising anything: callers surface it to the admin
 * (the Appearance page) or fall back to inline styles (the theme head).
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string the folder path, or false if it can't be created/written
 */
function formulize_prepareAppearanceDir($theme = null) {
    $theme = formulize_resolveAppearanceTheme($theme);
    if ($theme === '') {
        return false;
    }
    $dir = formulize_getAppearanceDir($theme);
    if (!is_dir($dir)) {
        if (!is_dir(dirname($dir)) OR !is_writable(dirname($dir))) {
            return false;
        }
        if (!@mkdir($dir, 0755, true) AND !is_dir($dir)) {
            return false;
        }
    }
    if (!is_writable($dir)) {
        @chmod($dir, 0755);
    }
    return is_writable($dir) ? $dir : false;
}

/**
 * Whether a theme's appearance folder can actually be written, established by
 * writing a file and deleting it again. is_writable() is not enough on its own:
 * on some deployments (containers with bind-mounted volumes, for instance) it
 * reports a folder as writable that the web server still can't write to, and
 * the point of asking is to warn the admin before they fill in the form.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return boolean
 */
function formulize_appearanceDirIsWritable($theme = null) {
    $dir = formulize_prepareAppearanceDir($theme);
    if ($dir === false) {
        return false;
    }
    $probe = $dir . '/.formulize-write-test';
    if (@file_put_contents($probe, '') === false) {
        return false;
    }
    @unlink($probe);
    return true;
}

/**
 * The CSS custom property overrides the current settings call for: the font
 * stack when a non-default font is chosen, and the colour tokens (with their
 * derived variants) for every colour that differs from the design defaults.
 *
 * @param array|null $settings appearance settings to use, defaults to the saved ones
 * @return array CSS custom property => value
 */
function formulize_getAppearanceCssOverrides($settings = null) {
    $settings = is_array($settings) ? $settings : formulize_getAppearanceSettings();
    $overrides = array();
    $font = formulize_getAppearanceFont($settings);
    if ($font['stack']) {
        $overrides['--font-sans'] = $font['stack'];
    }
    foreach (formulize_appearanceColourMap() as $key => $colour) {
        $value = formulize_sanitizeAppearanceColour($settings['appearance_' . $key]);
        if ($value AND $value != $colour['default']) {
            foreach ($colour['tokens'] as $token => $template) {
                $overrides[$token] = sprintf($template, $value);
            }
        }
    }
    return $overrides;
}

/**
 * Build a theme's appearance stylesheet for a set of settings: the settings
 * themselves as a comment block at the top (see
 * formulize_buildAppearanceSettingsBlock), followed by the webfont import and
 * the custom property overrides they call for.
 *
 * Assembled here in PHP rather than from a Smarty template, deliberately. This
 * file is the record of a theme's appearance settings, so it has to come out
 * complete every single time, and a template could not promise that: Smarty
 * compiles templates into templates_c and only re-checks the source when the
 * "update module templates from file" setting is on, which on a normal site it
 * is not. A site that had generated a stylesheet under an earlier version of
 * Formulize would keep rendering that version's template until templates_c was
 * cleared by hand, silently dropping the settings block from every stylesheet
 * it wrote from then on - and with it the record of the uploaded logo, whose
 * filename that block is the only place to keep. The colours would still look
 * right (they are custom properties further down the file, which the older
 * template still emitted), so nothing would look wrong until someone noticed
 * their logo had stopped appearing. Building the file in PHP means the code
 * that writes it and the code that reads it back can never fall out of step.
 *
 * @param array|null $settings appearance settings to use, defaults to the theme's saved ones
 * @param string|null $theme   theme folder name, defaults to the active theme
 * @return string the CSS
 */
function formulize_buildAppearanceCss($settings = null, $theme = null) {
    $theme = formulize_resolveAppearanceTheme($theme);
    $settings = is_array($settings)
        ? formulize_sanitizeAppearanceSettings($settings)
        : formulize_getAppearanceSettings($theme);
    $font = formulize_getAppearanceFont($settings);
    $css = formulize_buildAppearanceSettingsBlock($settings, $theme) . "\n";
    if ($font['url']) {
        $css .= '@import url("' . $font['url'] . '");' . "\n";
    }
    $overrides = formulize_getAppearanceCssOverrides($settings);
    if ($overrides) {
        $css .= ":root {\n";
        foreach ($overrides as $token => $value) {
            $css .= '  ' . $token . ': ' . $value . ";\n";
        }
        $css .= "}\n";
    }
    return $css;
}

/**
 * Write a theme's appearance stylesheet into that theme's appearance folder.
 * This is how appearance settings are saved: the file is the record of them, so
 * a failure here means the settings were not saved at all, and callers must say
 * so rather than reporting success (the Appearance page does, and warns about an
 * unwritable folder before the admin fills the form in).
 *
 * Also called lazily by formulize_renderAppearanceHead when a theme has no
 * stylesheet yet. The file is always generated, even with all-default settings,
 * so themes can link it unconditionally (the default file just imports the
 * default webfont).
 *
 * @param array|null $settings appearance settings to write, defaults to the
 *                             theme's current ones
 * @param string|null $theme   theme folder name, defaults to the active theme
 * @return boolean whether the file was written successfully
 */
function formulize_regenerateAppearanceCss($settings = null, $theme = null) {
    $theme = formulize_resolveAppearanceTheme($theme);
    $dir = formulize_prepareAppearanceDir($theme);
    if ($dir === false) {
        return false;
    }
    $css = formulize_buildAppearanceCss($settings, $theme);
    $written = (file_put_contents($dir . '/appearance.css', $css) !== false);
    clearstatcache(true, $dir . '/appearance.css'); // the file's existence and mtime are read right after this
    return $written;
}

/**
 * Render the head markup a theme needs for the appearance settings: preconnect
 * hints for the webfont host, and the link tag for the generated stylesheet,
 * which is regenerated first if it doesn't exist yet. Goes after the theme's
 * own stylesheet links, so the overrides win the cascade.
 *
 * The settings and the stylesheet are those of the theme rendering the page,
 * so each theme gets its own look. Regenerating the file when it is missing is
 * also what carries a pre-upgrade site's settings out of the module configs and
 * into the file (see formulize_getLegacyAppearanceSettings). If the stylesheet
 * can't be written (a theme folder the web server has no write access to), the
 * same CSS is emitted inline instead, so the configured appearance is never
 * simply lost.
 *
 * @return string HTML to print in the head, after the theme stylesheet links
 */
function formulize_renderAppearanceHead() {
    $theme = formulize_getActiveAppearanceTheme();
    $cssPath = formulize_getAppearanceCssPath($theme);
    $cssExists = file_exists($cssPath);
    if (!$cssExists) {
        // settings are read before the file is written, so the values that go into it
        // are the ones this page is already rendering with, legacy or default
        $cssExists = formulize_regenerateAppearanceCss(formulize_getAppearanceSettings($theme), $theme);
    }
    $html = '';
    $font = formulize_getAppearanceFont(formulize_getAppearanceSettings($theme));
    if ($font['url']) {
        $html .= '<link rel="preconnect" href="https://fonts.googleapis.com" />' . "\n";
        $html .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />' . "\n";
    }
    if ($cssExists) {
        $html .= '<link rel="stylesheet" type="text/css" media="all" href="' . formulize_getAppearanceUrl($theme) . '/appearance.css?v=' . filemtime($cssPath) . '" />' . "\n";
    } else {
        $html .= '<style type="text/css" media="all">' . "\n" . formulize_buildAppearanceCss(formulize_getAppearanceSettings($theme), $theme) . "\n" . '</style>' . "\n";
    }
    return $html;
}
