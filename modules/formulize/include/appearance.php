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

// Appearance settings: colours, font, logo, and favicon, configured on the
// Appearance page in the Formulize admin UI, and rendered by themes as CSS custom
// property overrides on :root.
//
// Settings are kept per theme, in the theme's own generated stylesheet. That
// stylesheet, and any uploaded logo or favicon, live in an "appearance" folder inside the
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
 * The custom properties a theme defines in its own stylesheet, ie: the palette
 * it looks like before any appearance settings are applied. Read out of the
 * first :root block in the theme's css/tokens.css, the dedicated design tokens
 * stylesheet a theme declares its defaults in, falling back to css/style.css for
 * a theme that still declares them inline at the top of its main stylesheet.
 *
 * This is what makes the defaults on the Appearance page the defaults of the
 * theme being edited, rather than one shared palette: each theme's real values
 * are already written down in its own stylesheet, so they are read from there
 * rather than copied into PHP, and a theme's palette can never drift from the
 * defaults the Appearance page offers to reset to.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return array CSS custom property (including the leading --) => declared value
 */
function formulize_appearanceThemeTokens($theme = null) {
    static $tokens = array(); // parses a file, and is asked for once per colour
    $theme = formulize_resolveAppearanceTheme($theme);
    if (!isset($tokens[$theme])) {
        $tokens[$theme] = array();
        $css = false;
        // tokens.css is where a theme declares its defaults; style.css is the fallback
        // for themes that have not been split up that way (yet), since they used to all
        // declare their tokens at the top of it.
        foreach (array('tokens.css', 'style.css') as $name) {
            $file = ICMS_THEME_PATH . '/' . $theme . '/css/' . $name;
            if ($theme AND is_file($file) AND ($contents = @file_get_contents($file)) !== false) {
                $css = $contents;
                break;
            }
        }
        // The first :root block only: later ones are media/scheme variations, not the base
        // palette. The block has to start a line (or follow a rule) to be the real thing
        // and not a :root mentioned inside some longer selector.
        if ($css !== false AND preg_match('/(?:^|\})\s*:root\s*\{([^}]*)\}/m', $css, $block)) {
            if (preg_match_all('/(--[A-Za-z0-9_-]+)\s*:\s*([^;]+);/', $block[1], $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $tokens[$theme][$match[1]] = trim($match[2]);
                }
            }
        }
    }
    return $tokens[$theme];
}

/**
 * The colours users can set, with the default for each in the theme being
 * asked about, and the CSS custom properties each one drives. Derived tokens
 * use color-mix() so any user-picked base colour produces coherent hover/soft/
 * muted variants.
 *
 * The defaults written in below are the Formulize design system's, and are used
 * as-is for a theme that doesn't declare a colour itself. Where the theme does
 * declare it (every theme built on these tokens declares all of them), the
 * theme's own value is the default, so the Appearance page shows, and resets to,
 * what that theme actually looks like. See formulize_appearanceThemeTokens().
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return array config name (without the appearance_ prefix) => array with
 *               'label', 'description', 'default' (hex), and 'tokens', a map of
 *               CSS custom property => value template where %s is the base colour
 */
function formulize_appearanceColourMap($theme = null) {
    $map = formulize_appearanceColourMapDefinition();
    $themeTokens = formulize_appearanceThemeTokens($theme);
    foreach ($map as $key => $colour) {
        // the base token is the one the colour is used at unchanged, ie: the plain %s
        // template. The rest are derived from it and can't be read back as a base colour.
        foreach ($colour['tokens'] as $token => $template) {
            if ($template !== '%s') {
                continue;
            }
            $value = formulize_sanitizeAppearanceColour(isset($themeTokens[$token]) ? $themeTokens[$token] : '');
            if ($value) { // a theme can declare it as rgba(), a var(), or not at all: keep our default then
                $map[$key]['default'] = $value;
            }
            break;
        }
    }
    return $map;
}

/**
 * The colour map as it is written down here: the design system's own defaults,
 * before the theme being edited has had its say. Separate from
 * formulize_appearanceColourMap() only so that function has something to layer
 * the theme's values onto; everything else should call that one.
 *
 * @return array same shape as formulize_appearanceColourMap()
 */
function formulize_appearanceColourMapDefinition() {
    return array(
        'primary' => array(
            'label' => 'Primary',
            'description' => 'Buttons, links, selected rows, focus rings',
            'default' => '#2e3340',
            'tokens' => array(
                '--fz-color-accent' => '%s',
                '--fz-color-accent-text' => '%s',
                '--fz-color-accent-hover' => 'color-mix(in srgb, %s 85%%, #000)',
                '--fz-color-accent-soft' => 'color-mix(in srgb, %s 8%%, #fff)',
                '--fz-color-accent-soft-2' => 'color-mix(in srgb, %s 16%%, #fff)',
                '--fz-color-focus' => 'color-mix(in srgb, %s 85%%, #000)',
                '--fz-focus-ring' => '0 0 0 3px color-mix(in srgb, %s 15%%, transparent)',
            ),
        ),
        'background' => array(
            'label' => 'Page background',
            'description' => 'The backdrop behind all content',
            'default' => '#f7f7f5',
            'tokens' => array(
                '--fz-color-page' => '%s',
            ),
        ),
        'surface' => array(
            'label' => 'Surface',
            'description' => 'Cards, tables, panels and other raised areas',
            'default' => '#ffffff',
            'tokens' => array(
                '--fz-color-surface' => '%s',
                '--fz-color-surface-2' => 'color-mix(in srgb, %s 98%%, #555)',
                '--fz-color-surface-3' => 'color-mix(in srgb, %s 94%%, #555)',
            ),
        ),
        'text' => array(
            'label' => 'Text',
            'description' => 'Main text colour; muted and subtle text are derived from it',
            'default' => '#14161a',
            'tokens' => array(
                '--fz-color-text' => '%s',
                '--fz-color-text-muted' => 'color-mix(in srgb, %s 65%%, #fff)',
                '--fz-color-text-subtle' => 'color-mix(in srgb, %s 45%%, #fff)',
            ),
        ),
        'border' => array(
            'label' => 'Borders',
            'description' => 'Dividers and outlines; strong borders are derived',
            'default' => '#e6e6e1',
            'tokens' => array(
                '--fz-color-border' => '%s',
                '--fz-color-border-strong' => 'color-mix(in srgb, %s 90%%, #000)',
            ),
        ),
        'success' => array(
            'label' => 'Success',
            'description' => 'Positive statuses and badges',
            'default' => '#2a7a4f',
            'tokens' => array(
                '--fz-color-success' => '%s',
                '--fz-color-success-soft' => 'color-mix(in srgb, %s 12%%, #fff)',
            ),
        ),
        'warning' => array(
            'label' => 'Warning',
            'description' => 'Caution statuses and badges',
            'default' => '#a86a14',
            'tokens' => array(
                '--fz-color-warning' => '%s',
                '--fz-color-warning-soft' => 'color-mix(in srgb, %s 12%%, #fff)',
            ),
        ),
        'danger' => array(
            'label' => 'Danger',
            'description' => 'Errors, destructive actions',
            'default' => '#b3261e',
            'tokens' => array(
                '--fz-color-danger' => '%s',
                '--fz-color-danger-soft' => 'color-mix(in srgb, %s 12%%, #fff)',
            ),
        ),
        'info' => array(
            'label' => 'Info',
            'description' => 'Informational statuses and badges',
            'default' => '#2e5fa8',
            'tokens' => array(
                '--fz-color-info' => '%s',
                '--fz-color-info-soft' => 'color-mix(in srgb, %s 12%%, #fff)',
            ),
        ),
    );
}

/**
 * The name of the font a theme uses when no font has been chosen on the
 * Appearance page, ie: the first family in the --fz-font-sans it declares itself.
 * That is what the default option in the font picker actually gives you, so it
 * is what that option is labelled with.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string the family name, or 'Geist' when the theme doesn't declare one
 */
function formulize_appearanceThemeFontName($theme = null) {
    $tokens = formulize_appearanceThemeTokens($theme);
    if (isset($tokens['--fz-font-sans'])) {
        $first = trim(strtok($tokens['--fz-font-sans'], ','), " \t\"'");
        // a var() or other indirection isn't a family name we can show
        if ($first !== '' AND preg_match('/^[A-Za-z0-9 _-]+$/', $first)) {
            return $first;
        }
    }
    return 'Geist';
}

/**
 * Curated font choices. 'google' is the family parameter for the Google Fonts
 * css2 API (false when no webfont needs loading), 'stack' is the CSS
 * font-family value for --fz-font-sans.
 *
 * The first choice is "leave the theme's own font alone", which is why its
 * label names the theme's font rather than a fixed one: on Lyris that is Geist,
 * on Anari it is Poppins. Its key stays 'geist' because that key is written
 * into saved settings, and nothing is recorded for the default anyway.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return array font key => array with 'label', 'google', 'stack'
 */
function formulize_appearanceFontMap($theme = null) {
    $fallback = "-apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif";
    return array(
        'geist' => array(
            'label' => formulize_appearanceThemeFontName($theme) . ' (default)',
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
 * The font choices offered for the secondary font, ie: the one headings, form
 * labels and the drawer's title are set in.
 *
 * Same list of families as the main font, with one difference: the first option
 * doesn't mean "this theme's own font", it means "don't use a second font at
 * all". A theme declares --fz-font-heading as var(--fz-font-sans), so leaving this
 * setting alone is what makes headings follow whatever the main font is, and
 * nothing is written for it. That is also what the issue asks for: the second
 * font only does anything when it is different from the first.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return array font key => array with 'label', 'google', 'stack'
 */
function formulize_appearanceHeadingFontMap($theme = null) {
    $fonts = formulize_appearanceFontMap($theme);
    $fonts['geist']['label'] = 'Same as the main font (default)';
    return $fonts;
}

/* ---- Text size ----
 *
 * Two different sizes are in play here, and keeping them apart is the whole
 * point of this group of functions.
 *
 * The size that has to be *set* is the root font size: `html { font-size }`,
 * which both themes express their whole type scale as a proportion of, and so
 * the only value that moves every text size together.
 *
 * The size an admin is *thinking about* is the one they can see: the standard
 * text in lists and content. In Lyris that is --fs-13, ie: 13px, not the 16px
 * root it is derived from. Showing them "16px" and calling it the font size is
 * telling them their content text is 16px when it is 13px.
 *
 * So the Appearance page works entirely in content text size - that is what the
 * dropdown offers, what is recorded in the generated stylesheet's settings
 * block, and what is read back into the form - and the conversion to the root
 * size happens once, on the way into the CSS, using the ratio the theme
 * declares. Nothing the admin sees is ever the root size.
 */

/**
 * How big a theme's standard content text is as a proportion of its root font
 * size, from the --font-size-content-ratio the theme declares.
 *
 * Each theme declares its own because each sets its content text at a different
 * step of its scale: Lyris's content rules are --fs-13 (0.8125rem) and so it
 * declares 0.8125, while Anari sizes content text at the root size itself and
 * declares 1. A theme that declares nothing is read as 1, which is the safe
 * reading: the setting then simply means what it says.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return float a ratio greater than zero
 */
function formulize_appearanceContentRatio($theme = null) {
    $tokens = formulize_appearanceThemeTokens($theme);
    $ratio = isset($tokens['--font-size-content-ratio']) ? (float) $tokens['--font-size-content-ratio'] : 0;
    return ($ratio > 0) ? $ratio : 1;
}

/**
 * The root font size a theme uses when none has been chosen on the Appearance
 * page, ie: the --formulize-font-size-base it declares itself. Read from the theme the
 * same way the default colours are, so the Appearance page shows and resets to
 * what the theme actually looks like.
 *
 * Not validated against the sizes on offer: those are content sizes now, and
 * this is a root size, so the only question is whether it is a pixel length.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string a css length, eg: '16px'
 */
function formulize_appearanceThemeFontSize($theme = null) {
    $tokens = formulize_appearanceThemeTokens($theme);
    $value = strtolower(trim(isset($tokens['--formulize-font-size-base']) ? $tokens['--formulize-font-size-base'] : ''));
    return preg_match('/^[0-9]+(?:\.[0-9]+)?px$/', $value) ? $value : '16px';
}

/**
 * The content text size a theme renders at out of the box: its own root size
 * taken through its content ratio. This is the theme's default as far as the
 * Appearance page is concerned - what it shows when nothing has been chosen,
 * and what "Reset Everything to Defaults" goes back to.
 *
 * Rounded to a whole pixel, because the sizes on offer are whole pixels: a
 * theme whose content text lands on a fraction is offered the nearest whole
 * size, which is a difference of well under a pixel and not one anybody would
 * rather see written as 12.9999px in a dropdown.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string a css length, eg: '13px'
 */
function formulize_appearanceThemeContentSize($theme = null) {
    $base = (float) formulize_appearanceThemeFontSize($theme);
    return round($base * formulize_appearanceContentRatio($theme)) . 'px';
}

/**
 * The content text sizes on offer, for the theme being edited. A short list of
 * whole pixel sizes rather than a free number field: the type scale is
 * proportional, so a couple of steps either side of the theme's own size is the
 * whole useful range.
 *
 * The steps are the same proportions the list has always offered (0.875 to 1.25
 * of the default), applied to the theme's own content size instead of to a fixed
 * 16px, so the middle option is always the theme's real current size and the
 * others are the same relative jumps. On Anari, whose content ratio is 1, that
 * reproduces exactly the 14-20px list; on Lyris it becomes 11-16px around 13px.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return array css length => label
 */
function formulize_appearanceFontSizeMap($theme = null) {
    $default = (float) formulize_appearanceThemeContentSize($theme);
    $steps = array(
        array(0.875,  'smaller'),
        array(0.9375, ''),
        array(1,      'default'),
        array(1.0625, ''),
        array(1.125,  'larger'),
        array(1.25,   'largest'),
    );
    $sizes = array();
    foreach ($steps as $step) {
        $size = round($default * $step[0]) . 'px';
        // rounding to whole pixels can land two steps on the same size when the
        // theme's own size is small. The labelled steps are the ones worth keeping,
        // so an unlabelled duplicate is dropped rather than overwriting one.
        if (isset($sizes[$size]) AND $step[1] === '') {
            continue;
        }
        $sizes[$size] = $step[1] ? ($size . ' - ' . $step[1]) : $size;
    }
    return $sizes;
}

/**
 * Validate a user-supplied content text size. Only the sizes we offer for that
 * theme are accepted, so nothing arbitrary can be written into the generated
 * stylesheet, and a size saved for one theme can't be read back as a valid one
 * for another whose scale is different.
 *
 * @param string $value the submitted size
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string the size, or '' if it isn't one we offer
 */
function formulize_sanitizeAppearanceFontSize($value, $theme = null) {
    $value = strtolower(trim((string) $value));
    $sizes = formulize_appearanceFontSizeMap($theme);
    return isset($sizes[$value]) ? $value : '';
}

/**
 * The root font size to set so that a theme's content text comes out at the
 * size an admin picked: the chosen size divided by the theme's content ratio.
 * This is the one place the translation happens, and it is the only place the
 * root size is ever produced from a setting.
 *
 * Kept to four decimal places, which puts the resulting content text within a
 * thousandth of a pixel of the size asked for while staying readable in the
 * generated stylesheet.
 *
 * @param string $contentSize the chosen content text size, eg: '14px'
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string a css length, eg: '17.2308px', or '' if the size is unusable
 */
function formulize_appearanceBaseFontSizeFor($contentSize, $theme = null) {
    $content = (float) $contentSize;
    if ($content <= 0) {
        return '';
    }
    $base = number_format($content / formulize_appearanceContentRatio($theme), 4, '.', '');
    return rtrim(rtrim($base, '0'), '.') . 'px';
}

/**
 * The names of all the appearance settings, ie: the keys of a settings array
 *
 * @return array of setting names
 */
function formulize_appearanceSettingNames() {
    $names = array('appearance_font', 'appearance_customfont', 'appearance_headingfont',
        'appearance_headingcustomfont', 'appearance_fontsize', 'appearance_logo',
        'appearance_favicon');
    // the definition, not the theme-aware map: the setting names are the same for every
    // theme, and only the defaults differ, so there is no theme to resolve here
    foreach (array_keys(formulize_appearanceColourMapDefinition()) as $key) {
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
 * @param string|null $theme theme the settings belong to, whose own palette is
 *                           what counts as default. Defaults to the active theme.
 * @return array setting name => clean value (all setting names present)
 */
function formulize_sanitizeAppearanceSettings($values, $theme = null) {
    $clean = formulize_defaultAppearanceSettings();
    foreach (formulize_appearanceColourMap($theme) as $key => $colour) {
        $value = formulize_sanitizeAppearanceColour(isset($values['appearance_' . $key]) ? $values['appearance_' . $key] : '');
        // nothing is recorded for a colour that is this theme's default, so the theme's own
        // palette keeps applying (derived variants included) and the defaults can evolve
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
    // the secondary font is the same list, and 'geist' means "no second font", which is
    // the default and so is recorded as nothing at all, exactly like the main font
    $headingFont = isset($values['appearance_headingfont']) ? trim((string) $values['appearance_headingfont']) : '';
    $headingCustomFont = formulize_sanitizeAppearanceFontFamily(isset($values['appearance_headingcustomfont']) ? $values['appearance_headingcustomfont'] : '');
    if (!isset($fonts[$headingFont]) OR ($headingFont == 'custom' AND $headingCustomFont === '')) {
        $headingFont = 'geist';
    }
    $clean['appearance_headingfont'] = ($headingFont == 'geist') ? '' : $headingFont;
    $clean['appearance_headingcustomfont'] = ($headingFont == 'custom') ? $headingCustomFont : '';
    // Recorded as the content text size the admin picked, not the root font size it
    // works out to: the setting means what the Appearance page says it means, and the
    // translation happens on the way into the CSS. Nothing is recorded for the theme's
    // own size, the same way a default colour isn't, so the theme keeps deciding what
    // its default type scale is.
    $fontSize = formulize_sanitizeAppearanceFontSize(isset($values['appearance_fontsize']) ? $values['appearance_fontsize'] : '', $theme);
    $clean['appearance_fontsize'] = ($fontSize == formulize_appearanceThemeContentSize($theme)) ? '' : $fontSize;
    // the logo and the favicon are bare filenames in the theme's appearance folder,
    // never paths
    foreach (array('appearance_logo', 'appearance_favicon') as $fileSetting) {
        $file = basename(trim((string) (isset($values[$fileSetting]) ? $values[$fileSetting] : '')));
        $clean[$fileSetting] = preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $file) ? $file : '';
    }
    return $clean;
}

/**
 * The comment that says which generation of this code wrote a theme's appearance
 * stylesheet. The settings block is the record of the settings and keeps its own
 * format version (see formulize_appearanceSettingsBlockMarkers); this is about the
 * CSS written after it, which changes whenever the tokens the themes use change.
 * A stylesheet without the current marker is rewritten from the settings recorded
 * in it the next time a page renders (see formulize_renderAppearanceHead), so that
 * an admin's saved colours and fonts keep applying across such a change.
 *
 * Generation 2: the tokens were renamed to the Formulize UI names (--c-accent
 * became --fz-color-accent, and so on).
 *
 * @return string the whole comment, one line
 */
function formulize_appearanceCssGenerationMarker() {
    return '/* Formulize appearance stylesheet, generation 2 */';
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
 * @param string|null $theme the theme the stylesheet belongs to, whose own
 *                           palette is what counts as default when validating
 * @return array|false settings array, or false when the file has no usable settings block
 */
function formulize_readAppearanceCssSettings($path, $theme = null) {
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
    return formulize_sanitizeAppearanceSettings($values, $theme);
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
    return formulize_sanitizeAppearanceSettings($values, $theme);
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
        $settings = formulize_readAppearanceCssSettings(formulize_getAppearanceCssPath($theme), $theme);
        $cache[$theme] = ($settings === false) ? formulize_getLegacyAppearanceSettings($theme) : $settings;
    }
    return $cache[$theme];
}

/**
 * Resolve one font choice (a key into formulize_appearanceFontMap, plus the
 * family typed in for the 'custom' choice) into the webfont to fetch and the
 * font-family value to use. Shared by the main font and the secondary font, so
 * the two behave identically, custom families included.
 *
 * @param string $choice       font key as saved in the settings
 * @param string $customFamily family name for the 'custom' choice
 * @return array with 'key' (the choice actually resolved to), 'google'
 *               (css2 family parameter, or false when no webfont is needed) and
 *               'stack' (the css font-family value)
 */
function formulize_resolveAppearanceFontChoice($choice, $customFamily) {
    $fonts = formulize_appearanceFontMap();
    $choice = isset($fonts[$choice]) ? $choice : 'geist';
    $googleFamily = $fonts[$choice]['google'];
    $stack = $fonts[$choice]['stack'];
    if ($choice == 'custom') {
        $family = formulize_sanitizeAppearanceFontFamily($customFamily);
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
    return array('key' => $choice, 'google' => $googleFamily, 'stack' => $stack);
}

/**
 * Resolve the Google Fonts css2 URL and the font-family values for the current
 * settings. Geist Mono is always requested alongside, since --fz-font-mono uses it,
 * and the secondary font is requested too when one has been chosen and is not
 * the family the main font already brings in.
 *
 * @param array|null $settings appearance settings to use, defaults to the saved ones
 * @return array with 'url' (string|false), 'stack' (string|false when default)
 *               and 'heading' (string|false when headings follow the main font)
 */
function formulize_getAppearanceFont($settings = null) {
    $settings = is_array($settings) ? $settings : formulize_getAppearanceSettings();
    $font = formulize_resolveAppearanceFontChoice(
        isset($settings['appearance_font']) ? $settings['appearance_font'] : '',
        isset($settings['appearance_customfont']) ? $settings['appearance_customfont'] : ''
    );
    $heading = formulize_resolveAppearanceFontChoice(
        isset($settings['appearance_headingfont']) ? $settings['appearance_headingfont'] : '',
        isset($settings['appearance_headingcustomfont']) ? $settings['appearance_headingcustomfont'] : ''
    );
    // 'geist' on the secondary font means "follow the main font", so it brings nothing
    // of its own, and neither does picking the same family the main font already fetched
    $headingIsSeparate = ($heading['key'] != 'geist');
    $families = array();
    if ($font['google']) {
        $families[] = $font['google'];
    }
    if ($headingIsSeparate AND $heading['google'] AND $heading['google'] != $font['google']) {
        $families[] = $heading['google'];
    }
    // "System UI (no webfont)" for both picks means exactly that: nothing is fetched,
    // Geist Mono included, the same as before there was a secondary font to consider
    $url = false;
    if ($families) {
        $families[] = 'Geist+Mono:wght@400;500';
        $url = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', $families) . '&display=swap';
    }
    return array(
        'url' => $url,
        'stack' => ($font['key'] == 'geist') ? false : $font['stack'], // false means the theme's own default applies
        'heading' => $headingIsSeparate ? $heading['stack'] : false,   // false means headings follow --fz-font-sans
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
    return formulize_getAppearanceFileUrl(formulize_getAppearanceLogoPath($theme), $theme);
}

/**
 * The filesystem path of the uploaded custom favicon, if any. Kept alongside the
 * logo, and in exactly the same way: a bare filename recorded in the theme's
 * generated stylesheet, and the file itself in the theme's appearance folder.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string path of the favicon file, or '' when no custom favicon is set
 */
function formulize_getAppearanceFaviconPath($theme = null) {
    $settings = formulize_getAppearanceSettings($theme);
    return formulize_locateAppearanceFile($settings['appearance_favicon'], $theme);
}

/**
 * The URL of the uploaded custom favicon, if any. A theme falls back to its own
 * built-in icons when this is empty, so an unset favicon changes nothing.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string URL of the favicon, or '' when no custom favicon is set
 */
function formulize_getAppearanceFaviconUrl($theme = null) {
    return formulize_getAppearanceFileUrl(formulize_getAppearanceFaviconPath($theme), $theme);
}

/**
 * The URL of one of a theme's appearance files, given its path. A direct static
 * URL, with the file's modification time included for cache busting. The file
 * can still be sitting in the legacy uploads/appearance folder on a site that
 * predates per-theme appearance files, so the folder it was actually found in
 * decides the base URL.
 *
 * @param string $path path of the file, as returned by formulize_locateAppearanceFile()
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string URL of the file, or '' when $path is empty
 */
function formulize_getAppearanceFileUrl($path, $theme = null) {
    if (!$path) {
        return '';
    }
    $base = (strpos($path, formulize_getLegacyAppearanceDir() . '/') === 0)
        ? formulize_getLegacyAppearanceUrl()
        : formulize_getAppearanceUrl($theme);
    return $base . '/' . rawurlencode(basename($path)) . '?v=' . filemtime($path);
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
 * stack when a non-default font is chosen, the secondary font stack when a
 * separate one is chosen for headings and labels, the root font size the chosen
 * text size works out to when it
 * differs from the theme's, and the colour tokens (with their derived variants)
 * for every colour that differs from the design defaults.
 *
 * @param array|null $settings appearance settings to use, defaults to the saved ones
 * @param string|null $theme the theme being styled, whose own palette is what
 *                           counts as default. Defaults to the active theme.
 * @return array CSS custom property => value
 */
function formulize_getAppearanceCssOverrides($settings = null, $theme = null) {
    $settings = is_array($settings) ? $settings : formulize_getAppearanceSettings($theme);
    $overrides = array();
    $font = formulize_getAppearanceFont($settings);
    if ($font['stack']) {
        $overrides['--fz-font-sans'] = $font['stack'];
    }
    // Left alone, a theme's --fz-font-heading is var(--fz-font-sans), so headings and labels
    // follow the main font without anything being written here.
    if ($font['heading']) {
        $overrides['--fz-font-heading'] = $font['heading'];
    }
    // The text size setting is the size of the standard content text, so it is
    // converted here to the root font size that produces it - the root size being
    // what the themes express the rest of their type scale relative to, and so the
    // one value that moves every text size together.
    $fontSize = formulize_sanitizeAppearanceFontSize(isset($settings['appearance_fontsize']) ? $settings['appearance_fontsize'] : '', $theme);
    if ($fontSize AND $fontSize != formulize_appearanceThemeContentSize($theme)) {
        $overrides['--formulize-font-size-base'] = formulize_appearanceBaseFontSizeFor($fontSize, $theme);
    }
    foreach (formulize_appearanceColourMap($theme) as $key => $colour) {
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
        ? formulize_sanitizeAppearanceSettings($settings, $theme)
        : formulize_getAppearanceSettings($theme);
    $font = formulize_getAppearanceFont($settings);
    $css = formulize_buildAppearanceSettingsBlock($settings, $theme) . "\n";
    $css .= formulize_appearanceCssGenerationMarker() . "\n";
    if ($font['url']) {
        $css .= '@import url("' . $font['url'] . '");' . "\n";
    }
    $overrides = formulize_getAppearanceCssOverrides($settings, $theme);
    if ($overrides) {
        $css .= ":root {\n";
        foreach ($overrides as $token => $value) {
            $css .= '  ' . $token . ': ' . $value . ";\n";
        }
        $css .= "}\n";
    }
    // Apply the root font size here as well as declaring the token, rather than
    // relying on the theme to have wired --formulize-font-size-base up to `html` itself.
    // This file is the record of the setting and is loaded after the theme's own
    // stylesheets, so having it do the applying means the size can never be
    // recorded here and yet have no effect - which is exactly what a theme that
    // declared the token without applying it would produce, and is not something
    // anyone looking at this file would be able to see. Both bundled themes do
    // apply it, so for them this is the same declaration twice over, and a
    // theme's own rule is still what a default site renders with.
    if (isset($overrides['--formulize-font-size-base'])) {
        $css .= "html {\n  font-size: var(--formulize-font-size-base);\n}\n";
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
 * which is regenerated first if it doesn't exist yet, or was written by an earlier
 * generation of this code (see formulize_appearanceCssGenerationMarker). Goes after the theme's
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
    // written by an earlier generation of this code, whose CSS may use tokens the
    // theme no longer has: rewrite it from the settings it records
    $cssIsCurrent = ($cssExists AND strpos((string) @file_get_contents($cssPath), formulize_appearanceCssGenerationMarker()) !== false);
    if (!$cssExists OR !$cssIsCurrent) {
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
