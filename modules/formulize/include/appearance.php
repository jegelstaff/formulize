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

// Appearance settings: site-wide colours, font, and logo, configured on the
// Appearance page in the Formulize admin UI, stored as module config items,
// and rendered by themes as CSS custom property overrides on :root.

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
 * The names of all appearance config items, as stored in the module configs
 *
 * @return array of config names
 */
function formulize_appearanceConfigNames() {
    $names = array('appearance_font', 'appearance_customfont', 'appearance_logo');
    foreach (array_keys(formulize_appearanceColourMap()) as $key) {
        $names[] = 'appearance_' . $key;
    }
    return $names;
}

/**
 * Read the saved appearance settings from the module configs. Empty string
 * means "use the theme default". Cached for the duration of the request.
 *
 * @return array config name => saved value (all appearance_* keys present)
 */
function formulize_getAppearanceSettings() {
    static $settings = null;
    if ($settings === null) {
        $config_handler = xoops_gethandler('config');
        $formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
        $settings = array();
        foreach (formulize_appearanceConfigNames() as $name) {
            $settings[$name] = isset($formulizeConfig[$name]) ? trim($formulizeConfig[$name]) : '';
        }
    }
    return $settings;
}

/**
 * Validate a user-supplied colour value. Only hex colours are accepted.
 *
 * @param string $value the submitted colour
 * @return string the normalized hex colour, or '' if invalid/empty
 */
function formulize_sanitizeAppearanceColour($value) {
    $value = trim($value);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : '';
}

/**
 * Resolve the Google Fonts css2 URL and the --font-sans value for the current
 * settings. Geist Mono is always requested alongside, since --font-mono uses it.
 *
 * @return array with 'url' (string|false) and 'stack' (string|false when default)
 */
function formulize_getAppearanceFont() {
    $settings = formulize_getAppearanceSettings();
    $fonts = formulize_appearanceFontMap();
    $choice = isset($fonts[$settings['appearance_font']]) ? $settings['appearance_font'] : 'geist';
    $googleFamily = $fonts[$choice]['google'];
    $stack = $fonts[$choice]['stack'];
    if ($choice == 'custom') {
        $family = trim(preg_replace('/[^a-zA-Z0-9 ]/', '', $settings['appearance_customfont']));
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
 * The folder where the uploaded logo is stored. Inside the trust path, so the
 * file sits outside the web root (and the codebase) and is served by logo.php.
 *
 * @return string filesystem path of the logo folder, no trailing slash
 */
function formulize_getAppearanceLogoDir() {
    return XOOPS_TRUST_PATH . '/modules/formulize/appearance';
}

/**
 * The filesystem path of the uploaded custom logo, if any
 *
 * @return string path of the logo file, or '' when no custom logo is set
 */
function formulize_getAppearanceLogoPath() {
    $settings = formulize_getAppearanceSettings();
    $file = basename($settings['appearance_logo']); // stored as a bare filename in the logo folder
    if ($file AND file_exists(formulize_getAppearanceLogoDir() . '/' . $file)) {
        return formulize_getAppearanceLogoDir() . '/' . $file;
    }
    return '';
}

/**
 * The URL of the uploaded custom logo, if any. Served through logo.php since
 * the file itself lives in the trust path, outside the web root. The file's
 * modification time is included for cache busting.
 *
 * @return string URL of the logo, or '' when no custom logo is set
 */
function formulize_getAppearanceLogoUrl() {
    $path = formulize_getAppearanceLogoPath();
    if ($path) {
        return XOOPS_URL . '/modules/formulize/logo.php?v=' . filemtime($path);
    }
    return '';
}

/**
 * Render the head markup a theme needs for the appearance settings: the webfont
 * link tag, and a style block overriding the design tokens that differ from the
 * defaults. Returns an empty style/font set untouched themes can safely print.
 *
 * @return string HTML to print in the head, after the theme stylesheet links
 */
function formulize_renderAppearanceHead() {
    $settings = formulize_getAppearanceSettings();
    $html = '';

    $font = formulize_getAppearanceFont();
    if ($font['url']) {
        $html .= '<link rel="preconnect" href="https://fonts.googleapis.com" />' . "\n";
        $html .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />' . "\n";
        $html .= '<link href="' . htmlspecialchars($font['url'], ENT_QUOTES) . '" rel="stylesheet" />' . "\n";
    }

    $overrides = array();
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
    if (count($overrides) > 0) {
        $css = '';
        foreach ($overrides as $token => $value) {
            $css .= $token . ': ' . $value . '; ';
        }
        $html .= '<style id="formulize-appearance">:root { ' . $css . '}</style>' . "\n";
    }

    return $html;
}
