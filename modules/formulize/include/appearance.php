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

// Appearance: the looks a theme's pages are shown with (colours, fonts, sizes, page
// width, logo and favicon), managed on the Looks tab (admin/looks.php: Admin >
// Appearance > Looks) and changed in the appearance editor (appearance_editor.php),
// and rendered by themes as CSS custom property overrides on :root.
//
// Looks are kept per theme, in generated stylesheets in an "appearance" folder
// inside the theme's folder, ie: themes/Lyris/appearance/, with any uploaded logos
// and favicons. That folder is inside the web root and is not access-protected the
// way uploads/ usually is, so the files are reachable by the browser wherever the
// site is deployed.
//
// The generated stylesheets are the record of the looks: each carries a
// machine-readable block at the top, which is what is read back. There is nothing
// else to keep in sync, per-theme looks fall out of per-theme files, and deleting
// a theme's appearance folder is all it takes to put that theme back to its own
// appearance. See the Looks section below.

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/appearance_tokens.php";

// The words of the looks, the Looks tab and the editor: the site's language's, or
// English when there is no translation of them
icms_loadLanguageFile('formulize', 'appearance');

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
            'label' => _AM_APPEARANCE_COLOUR_PRIMARY,
            'description' => _AM_APPEARANCE_COLOUR_PRIMARY_DESC,
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
            'label' => _AM_APPEARANCE_COLOUR_BACKGROUND,
            'description' => _AM_APPEARANCE_COLOUR_BACKGROUND_DESC,
            'default' => '#f7f7f5',
            'tokens' => array(
                '--fz-color-page' => '%s',
            ),
        ),
        'surface' => array(
            'label' => _AM_APPEARANCE_COLOUR_SURFACE,
            'description' => _AM_APPEARANCE_COLOUR_SURFACE_DESC,
            'default' => '#ffffff',
            'tokens' => array(
                '--fz-color-surface' => '%s',
                '--fz-color-surface-2' => 'color-mix(in srgb, %s 98%%, #555)',
                '--fz-color-surface-3' => 'color-mix(in srgb, %s 94%%, #555)',
            ),
        ),
        'text' => array(
            'label' => _AM_APPEARANCE_COLOUR_TEXT,
            'description' => _AM_APPEARANCE_COLOUR_TEXT_DESC,
            'default' => '#14161a',
            'tokens' => array(
                '--fz-color-text' => '%s',
                '--fz-color-text-muted' => 'color-mix(in srgb, %s 65%%, #fff)',
                '--fz-color-text-subtle' => 'color-mix(in srgb, %s 45%%, #fff)',
            ),
        ),
        'border' => array(
            'label' => _AM_APPEARANCE_COLOUR_BORDER,
            'description' => _AM_APPEARANCE_COLOUR_BORDER_DESC,
            'default' => '#e6e6e1',
            'tokens' => array(
                '--fz-color-border' => '%s',
                '--fz-color-border-strong' => 'color-mix(in srgb, %s 90%%, #000)',
            ),
        ),
        'success' => array(
            'label' => _AM_APPEARANCE_COLOUR_SUCCESS,
            'description' => _AM_APPEARANCE_COLOUR_SUCCESS_DESC,
            'default' => '#2a7a4f',
            'tokens' => array(
                '--fz-color-success' => '%s',
                '--fz-color-success-soft' => 'color-mix(in srgb, %s 12%%, #fff)',
            ),
        ),
        'warning' => array(
            'label' => _AM_APPEARANCE_COLOUR_WARNING,
            'description' => _AM_APPEARANCE_COLOUR_WARNING_DESC,
            'default' => '#a86a14',
            'tokens' => array(
                '--fz-color-warning' => '%s',
                '--fz-color-warning-soft' => 'color-mix(in srgb, %s 12%%, #fff)',
            ),
        ),
        'danger' => array(
            'label' => _AM_APPEARANCE_COLOUR_DANGER,
            'description' => _AM_APPEARANCE_COLOUR_DANGER_DESC,
            'default' => '#b3261e',
            'tokens' => array(
                '--fz-color-danger' => '%s',
                '--fz-color-danger-soft' => 'color-mix(in srgb, %s 12%%, #fff)',
            ),
        ),
        'info' => array(
            'label' => _AM_APPEARANCE_COLOUR_INFO,
            'description' => _AM_APPEARANCE_COLOUR_INFO_DESC,
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
 * The webfont a theme's own font is fetched as, when no font has been chosen on
 * the Appearance page: the family parameter for the Google Fonts css2 API.
 *
 * A theme whose own font is a Google webfont other than Geist says so by
 * declaring the weights it uses, in --formulize-font-weights with its other
 * tokens (Anari does: "400 500 600", for Poppins). A webfont is a file for each
 * weight, and those are the ones fetched: text in any other weight is drawn with
 * the nearest of them. The family is the first one
 * in its --fz-font-sans. The font then arrives with the generated appearance
 * stylesheet, which is on every page that takes on the theme's appearance: the
 * pages the theme draws, the list of entries popups, and embedded screens. A
 * theme that loaded its font from its own theme.html would have it only on the
 * first of those.
 *
 * A theme that declares no weights gets Geist, which is Lyris's font.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string the family parameter, eg: 'Poppins:wght@400;500;600'
 */
function formulize_appearanceThemeWebfont($theme = null) {
    $tokens = formulize_appearanceThemeTokens($theme);
    if (isset($tokens['--formulize-font-weights']) AND preg_match('/^\d{3}(\s+\d{3})*$/', $tokens['--formulize-font-weights'])) {
        // the API takes each weight once, lightest first, and refuses the request otherwise
        $weights = array_unique(array_map('intval', preg_split('/\s+/', $tokens['--formulize-font-weights'])));
        sort($weights);
        return str_replace(' ', '+', formulize_appearanceThemeFontName($theme)) . ':wght@' . implode(';', $weights);
    }
    return 'Geist:wght@400;500;600;700';
}

/**
 * Curated font choices. 'google' is the family parameter for the Google Fonts
 * css2 API (false when no webfont needs loading), 'stack' is the CSS
 * font-family value for --fz-font-sans.
 *
 * The first choice is "leave the theme's own font alone", which is why its
 * label names the theme's font rather than a fixed one, and its webfont is the
 * theme's own (formulize_appearanceThemeWebfont): on Lyris that is Geist,
 * on Anari it is Poppins. Its key stays 'geist' because that key is written
 * into saved settings, and nothing is recorded for the default anyway.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return array font key => array with 'label', 'name' (the label without its
 *               note), 'google', 'stack'
 */
function formulize_appearanceFontMap($theme = null) {
    $fallback = "-apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif";
    return array(
        'geist' => array(
            'label' => sprintf(_AM_APPEARANCE_FONT_DEFAULT, formulize_appearanceThemeFontName($theme)),
            'name' => formulize_appearanceThemeFontName($theme),
            'google' => formulize_appearanceThemeWebfont($theme),
            'stack' => "'Geist', $fallback",
        ),
        'system' => array(
            'label' => _AM_APPEARANCE_FONT_SYSTEM_LABEL,
            'name' => _AM_APPEARANCE_FONT_SYSTEM,
            'google' => false,
            'stack' => $fallback,
        ),
        'inter' => array(
            'label' => 'Inter',
            'name' => 'Inter',
            'google' => 'Inter:wght@400;500;600;700',
            'stack' => "'Inter', $fallback",
        ),
        'source-sans' => array(
            'label' => 'Source Sans 3',
            'name' => 'Source Sans 3',
            'google' => 'Source+Sans+3:wght@400;500;600;700',
            'stack' => "'Source Sans 3', $fallback",
        ),
        'ibm-plex' => array(
            'label' => 'IBM Plex Sans',
            'name' => 'IBM Plex Sans',
            'google' => 'IBM+Plex+Sans:wght@400;500;600;700',
            'stack' => "'IBM Plex Sans', $fallback",
        ),
        'nunito-sans' => array(
            'label' => 'Nunito Sans',
            'name' => 'Nunito Sans',
            'google' => 'Nunito+Sans:wght@400;500;600;700',
            'stack' => "'Nunito Sans', $fallback",
        ),
        'public-sans' => array(
            'label' => 'Public Sans',
            'name' => 'Public Sans',
            'google' => 'Public+Sans:wght@400;500;600;700',
            'stack' => "'Public Sans', $fallback",
        ),
        'work-sans' => array(
            'label' => 'Work Sans',
            'name' => 'Work Sans',
            'google' => 'Work+Sans:wght@400;500;600;700',
            'stack' => "'Work Sans', $fallback",
        ),
        'custom' => array(
            'label' => _AM_APPEARANCE_FONT_CUSTOM,
            'name' => _AM_APPEARANCE_FONT_CUSTOM,
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
 * @return array font key => array with 'label', 'name' (the label without its
 *               note), 'google', 'stack'
 */
function formulize_appearanceHeadingFontMap($theme = null) {
    $fonts = formulize_appearanceFontMap($theme);
    $fonts['geist']['label'] = sprintf(_AM_APPEARANCE_FONT_DEFAULT, _AM_APPEARANCE_FONT_HEADING_SAME);
    $fonts['geist']['name'] = _AM_APPEARANCE_FONT_HEADING_SAME;
    return $fonts;
}

// ---------------------------------------------------------------------------
// Looks
//
// A look is how pages look: the logo and favicon, colours, fonts, page width,
// and the size of every part. Each theme has its own looks. A new theme starts
// with one, Default. A look is based on another look, or on nothing (it starts
// from the theme's own settings), and records only what it changes itself:
// everything else comes from its base, and from that look's base, and so on. So
// the looks of a theme form a tree, and a change to a look reaches every look
// based on it.
//
// The site uses one look (formulize_getAppearanceSiteLook), and a screen can use
// another (formulize_appearancePageLook). Each look is kept in its own stylesheet
// in the theme's appearance/looks folder: a block with its name, its base and
// what it changes, which is the record of it, then the CSS of the look as pages
// are shown with it. The theme's appearance.css records which look the site uses,
// and is that look's CSS. Looks are managed on the Looks tab (admin/looks.php).
// ---------------------------------------------------------------------------

/**
 * A theme's looks, by key, from the stylesheets in its appearance/looks folder.
 * A theme with none yet is given Default (see formulize_appearanceCreateDefaultLook).
 * A look whose base is gone, or is the look itself or one based on it (which only
 * a hand-edited file could do), is read as having no base.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @param boolean $reload read the theme's looks again, after one has changed
 * @return array look key => array with 'name', 'description', 'base' (a look's
 *               key, or '' for none) and 'settings', the settings the look
 *               changes itself (see formulize_sanitizeAppearanceLookSettings).
 *               Default first, then by name.
 */
function formulize_getAppearanceLooks($theme = null, $reload = false) {
    static $cache = array();
    $theme = formulize_resolveAppearanceTheme($theme);
    if (isset($cache[$theme]) AND !$reload) {
        return $cache[$theme];
    }
    $looks = array();
    foreach (glob(formulize_getAppearanceLooksDir($theme) . '/*.css') ?: array() as $file) {
        $key = basename($file, '.css');
        if (!preg_match('/^[a-z0-9][a-z0-9-]*$/', $key)) {
            continue;
        }
        $block = formulize_appearanceParseLookBlock((string) @file_get_contents($file));
        if ($block !== false) {
            $looks[$key] = array(
                'name' => $block['name'],
                'description' => $block['description'],
                'base' => strtolower($block['base']),
                'settings' => formulize_sanitizeAppearanceLookSettings($block['settings'], $theme),
            );
        }
    }
    if (!$looks) {
        $cache[$theme] = formulize_appearanceCreateDefaultLook($theme);
        return $cache[$theme];
    }
    // a base that is gone, or that would go round in a circle, is no base
    foreach (array_keys($looks) as $key) {
        if ($looks[$key]['base'] !== '' AND !isset($looks[$looks[$key]['base']])) {
            $looks[$key]['base'] = '';
        }
    }
    foreach (array_keys($looks) as $key) {
        $seen = array($key => true);
        for ($at = $looks[$key]['base']; $at !== ''; $at = $looks[$at]['base']) {
            if (isset($seen[$at])) {
                $looks[$key]['base'] = '';
                break;
            }
            $seen[$at] = true;
        }
    }
    uksort($looks, function ($a, $b) use ($looks) {
        if ($a == 'default' OR $b == 'default') {
            return $a == 'default' ? -1 : 1;
        }
        return strcasecmp($looks[$a]['name'], $looks[$b]['name']);
    });
    $cache[$theme] = $looks;
    return $looks;
}

/**
 * Give a theme that has no looks yet its first: Default, with no base. It takes
 * whatever appearance settings the theme had before looks were kept this way:
 * those in its appearance.css, or in the module's configs before that (see
 * formulize_getLegacyAppearanceSettings). Its stylesheet, and the theme's, are
 * written straight away, where the folder can be written; otherwise it is only in
 * this request, and the theme's pages use the inline CSS fallback.
 *
 * @param string $theme theme folder name
 * @return array the theme's looks: just Default
 */
function formulize_appearanceCreateDefaultLook($theme) {
    $values = formulize_readAppearanceCssValues(formulize_getAppearanceCssPath($theme));
    $values = ($values === false) ? formulize_getLegacyAppearanceSettings($theme) : $values;
    $own = array();
    foreach (formulize_sanitizeAppearanceLookSettings($values, $theme) as $name => $value) {
        if ($value !== '') {
            $own[$name] = $value;
        }
    }
    $looks = array('default' => array('name' => _AM_APPEARANCE_LOOK_DEFAULT, 'description' => '', 'base' => '', 'settings' => $own));
    if (formulize_prepareAppearanceDir($theme) !== false) {
        formulize_writeAppearanceLookFiles($theme, $looks, 'default');
    }
    return $looks;
}

/**
 * The folder a theme's looks are kept in: looks, in its appearance folder. Each
 * look is a stylesheet there, named after the look's key.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string the folder's path
 */
function formulize_getAppearanceLooksDir($theme = null) {
    return formulize_getAppearanceDir($theme) . '/looks';
}

/**
 * Validate the settings a look changes. Unlike the settings a look resolves to,
 * where nothing is recorded for a default, a look records each setting it
 * changes, the default included: a look can put a colour its base changed back
 * to the theme's own. So each setting the look has is kept, as a valid value, or
 * dropped if it can't be one; an empty value means the default.
 *
 * @param array $values setting name => value
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return array setting name => value, for the settings the look changes
 */
function formulize_sanitizeAppearanceLookSettings($values, $theme = null) {
    $clean = array();
    $fonts = formulize_appearanceFontMap($theme);
    foreach (formulize_appearanceSettingNames() as $name) {
        if (!array_key_exists($name, $values)) {
            continue;
        }
        $value = trim((string) $values[$name]);
        if ($name == 'appearance_overrides') {
            $value = formulize_sanitizeAppearanceOverrides($value);
            if ($value !== '') {
                $clean[$name] = $value;
            }
        } elseif ($name == 'appearance_font' OR $name == 'appearance_headingfont') {
            if ($value === '' OR isset($fonts[$value])) {
                $clean[$name] = $value;
            }
        } elseif ($name == 'appearance_customfont' OR $name == 'appearance_headingcustomfont') {
            $clean[$name] = formulize_sanitizeAppearanceFontFamily($value);
        } elseif ($name == 'appearance_contentwidth') {
            $clean[$name] = formulize_sanitizeAppearanceContentWidth($value, $theme);
        } elseif ($name == 'appearance_logo' OR $name == 'appearance_favicon') {
            $file = basename($value);
            if ($file === '' OR preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $file)) {
                $clean[$name] = $file;
            }
        } elseif ($value === '' OR formulize_sanitizeAppearanceColour($value)) {
            $clean[$name] = $value === '' ? '' : formulize_sanitizeAppearanceColour($value);
        }
    }
    return $clean;
}

/**
 * Settings with what a look changes put on top: each setting the look changes
 * replaces the one underneath, except the component tokens (appearance_overrides),
 * which are merged, token by token, so a look that changes the row height keeps
 * the button colour of the look it is based on. A font and its Google Font name
 * go together.
 *
 * @param array $settings the settings underneath
 * @param array $look the settings the look changes
 * @return array settings array, not yet sanitized
 */
function formulize_mergeAppearanceLook($settings, $look) {
    foreach ($look as $name => $value) {
        if ($name == 'appearance_overrides') {
            $merged = array_merge(formulize_appearanceLookOverrides($settings), formulize_appearanceLookOverrides($look));
            $settings[$name] = $merged ? json_encode($merged, JSON_UNESCAPED_SLASHES) : '';
        } else {
            $settings[$name] = $value;
            if ($name == 'appearance_font' OR $name == 'appearance_headingfont') {
                $custom = ($name == 'appearance_font') ? 'appearance_customfont' : 'appearance_headingcustomfont';
                $settings[$custom] = isset($look[$custom]) ? $look[$custom] : '';
            }
        }
    }
    return $settings;
}

/**
 * The keys of the looks a look is based on, the furthest first, and the look
 * itself last.
 *
 * @param array $looks a theme's looks (formulize_getAppearanceLooks)
 * @param string $key the look's key
 * @return array of look keys
 */
function formulize_appearanceLookChain($looks, $key) {
    $chain = array();
    for ($at = $key; $at !== '' AND isset($looks[$at]) AND !in_array($at, $chain); $at = $looks[$at]['base']) {
        array_unshift($chain, $at);
    }
    return $chain;
}

/**
 * The keys of the looks based on a look, directly, by name; or of the looks
 * based on nothing, Default first, for ''.
 *
 * @param array $looks a theme's looks (formulize_getAppearanceLooks)
 * @param string $key the look's key, or ''
 * @return array of look keys
 */
function formulize_appearanceLookChildren($looks, $key) {
    $children = array();
    foreach ($looks as $childKey => $look) {
        if ($look['base'] === $key) {
            $children[] = $childKey; // already Default first, then by name
        }
    }
    return $children;
}

/**
 * The keys of every look based on a look, directly or through others.
 *
 * @param array $looks a theme's looks (formulize_getAppearanceLooks)
 * @param string $key the look's key
 * @return array of look keys
 */
function formulize_appearanceLookDescendants($looks, $key) {
    $found = array();
    foreach (formulize_appearanceLookChildren($looks, $key) as $child) {
        $found[] = $child;
        $found = array_merge($found, formulize_appearanceLookDescendants($looks, $child));
    }
    return $found;
}

/**
 * A theme's looks in the order of a tree: each look followed by the looks based
 * on it. For each, what a tree needs to draw its row (see fz-tree in
 * formulize-ui.css): how deep it is, the levels its row carries a line straight
 * through for, whether it is the last of the looks with its base, and whether
 * any look is based on it.
 *
 * @param array $looks a theme's looks (formulize_getAppearanceLooks)
 * @return array of arrays with 'key', 'depth', 'through' (levels), 'last' and 'parent'
 */
function formulize_appearanceLookTree($looks) {
    $rows = array();
    $walk = function ($key, $depth, $through) use (&$walk, &$rows, $looks) {
        $siblings = formulize_appearanceLookChildren($looks, $looks[$key]['base']);
        $last = (end($siblings) === $key);
        $children = formulize_appearanceLookChildren($looks, $key);
        $rows[] = array('key' => $key, 'depth' => $depth, 'through' => $through, 'last' => $last, 'parent' => (bool) $children);
        $next = ($depth > 0 AND !$last) ? array_merge($through, array($depth - 1)) : $through;
        foreach ($children as $child) {
            $walk($child, $depth + 1, $next);
        }
    };
    foreach (formulize_appearanceLookChildren($looks, '') as $root) {
        $walk($root, 0, array());
    }
    return $rows;
}

/**
 * The settings a look resolves to, that pages are shown with: what every look in
 * its chain changes, from the furthest base to the look itself, on top of the
 * theme's own settings.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @param string|null $key the look's key, defaults to the look the site uses
 * @return array settings array, sanitized
 */
function formulize_getAppearanceLookSettings($theme = null, $key = null) {
    $theme = formulize_resolveAppearanceTheme($theme);
    $looks = formulize_getAppearanceLooks($theme);
    $key = ($key === null OR !isset($looks[$key])) ? formulize_getAppearanceSiteLook($theme) : $key;
    return formulize_appearanceResolveLook($looks, $key, $theme);
}

/**
 * The settings a look resolves to, from a given set of looks: the ones being
 * saved, which may not be the ones on disk yet.
 *
 * @param array $looks a theme's looks
 * @param string $key the look's key
 * @param string $theme theme folder name
 * @return array settings array, sanitized
 */
function formulize_appearanceResolveLook($looks, $key, $theme) {
    $settings = formulize_defaultAppearanceSettings();
    foreach (formulize_appearanceLookChain($looks, $key) as $at) {
        $settings = formulize_mergeAppearanceLook($settings, $looks[$at]['settings']);
    }
    return formulize_sanitizeAppearanceSettings($settings, $theme);
}

/**
 * The settings a theme's pages are shown with: those of a look, by default the one
 * the site uses. Kept for the code that asked for the theme's effective settings
 * when there was one set of them.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @param string|null $lookKey the look, defaults to the one the site uses
 * @return array settings array, sanitized
 */
function formulize_getAppearanceEffectiveSettings($theme = null, $lookKey = null) {
    return formulize_getAppearanceLookSettings($theme, $lookKey);
}

/**
 * The look a theme's site uses: the one its appearance.css records, if the theme
 * still has it; otherwise Default, or if Default has been deleted, the first look
 * with no base.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string the look's key
 */
function formulize_getAppearanceSiteLook($theme = null) {
    $theme = formulize_resolveAppearanceTheme($theme);
    $looks = formulize_getAppearanceLooks($theme);
    $values = formulize_readAppearanceCssValues(formulize_getAppearanceCssPath($theme));
    $key = ($values !== false AND isset($values['appearance_look'])) ? strtolower($values['appearance_look']) : '';
    if (isset($looks[$key])) {
        return $key;
    }
    if (isset($looks['default'])) {
        return 'default';
    }
    $roots = formulize_appearanceLookChildren($looks, '');
    return $roots ? $roots[0] : (string) key($looks);
}

// ---------------------------------------------------------------------------
// Sizes: Standard (a theme's own), Compact and Comfortable. Not looks: each is a
// set of values for the component size tokens, which choosing it writes into a
// look. See formulize_appearanceSizePresets() in appearance_tokens.php.
// ---------------------------------------------------------------------------

/**
 * The sizes a look can be given, for a theme that has them: Standard first.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return array size key => array with 'name' and 'description'; empty for a
 *               theme without the size tokens
 */
function formulize_appearanceSizes($theme = null) {
    if (!formulize_appearanceThemeUsesSizes($theme)) {
        return array();
    }
    $sizes = array('standard' => array('name' => _AM_APPEARANCE_SIZE_STANDARD, 'description' => _AM_APPEARANCE_SIZE_STANDARD_DESC));
    // a preset's words are in its file, and in the language file, which has the say
    foreach (array_filter(formulize_appearanceSizePresets()) as $key => $preset) {
        $constant = '_AM_APPEARANCE_SIZE_' . strtoupper(str_replace('-', '_', $key));
        $sizes[$key] = array(
            'name' => defined($constant) ? constant($constant) : $preset['name'],
            'description' => defined($constant . '_DESC') ? constant($constant . '_DESC') : $preset['description'],
        );
    }
    return $sizes;
}

/**
 * The tokens a size sets: every token any of the presets sets.
 *
 * @return array of token names
 */
function formulize_appearanceSizeTokens() {
    $tokens = array();
    foreach (array_filter(formulize_appearanceSizePresets()) as $preset) {
        $tokens = array_merge($tokens, array_keys($preset['overrides']));
    }
    return array_values(array_unique($tokens));
}

/**
 * The values a size gives the size tokens: a preset's own, or for Standard, the
 * theme's own (what formulize-ui.css and the theme's tokens.css declare).
 *
 * @param string $size the size's key
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return array token => value, as appearance_tokens.json writes values
 */
function formulize_appearanceSizeValues($size, $theme = null) {
    $presets = formulize_appearanceSizePresets();
    if ($size != 'standard') {
        return !empty($presets[$size]) ? $presets[$size]['overrides'] : array();
    }
    $defaults = formulize_appearanceSizeDefaults($theme);
    $values = array();
    foreach (formulize_appearanceSizeTokens() as $token) {
        if (isset($defaults[$token])) {
            $values[$token] = $defaults[$token];
        }
    }
    return $values;
}

/**
 * Which size a look has: none of its own (it sets no size, so its sizes are its
 * base's), a size whose values it has, or custom. Measured on the look as it
 * resolves, since a value the same as its base's isn't kept as its own: a look
 * made Compact over Standard keeps only the values that differ.
 *
 * @param array $looks a theme's looks
 * @param string $key the look's key
 * @param string $theme theme folder name
 * @return string '' for none of its own, a size's key, or 'custom'
 */
function formulize_appearanceLookSize($looks, $key, $theme) {
    $sizeTokens = formulize_appearanceSizeTokens();
    if (!array_intersect_key(formulize_appearanceLookOverrides($looks[$key]['settings']), array_flip($sizeTokens))) {
        return '';
    }
    $resolved = formulize_appearanceLookOverrides(formulize_appearanceResolveLook($looks, $key, $theme)) + formulize_appearanceSizeDefaults($theme);
    foreach (array_keys(formulize_appearanceSizes($theme)) as $size) {
        $matches = true;
        foreach (formulize_appearanceSizeValues($size, $theme) as $token => $value) {
            if (!isset($resolved[$token]) OR $resolved[$token] != $value) {
                $matches = false;
                break;
            }
        }
        if ($matches) {
            return $size;
        }
    }
    return 'custom';
}

/**
 * What a look changes itself, for a person to take in at a glance: each kind of
 * change (its logo, favicon, colours, fonts, page width, sizes and parts), with what
 * it changes it to. The Looks tab lists these.
 *
 * @param array $looks a theme's looks
 * @param string $key the look's key
 * @param string $theme theme folder name
 * @return array of arrays with 'kind' (logo, favicon, colours, fonts, width, sizes,
 *               parts), 'label', 'detail' (words, or ''), 'phrase' (for a sentence:
 *               "2 colours"), and for the logo and favicon 'image' (a URL, or '' for
 *               the theme's own), for the colours 'swatches' (label => hex), for
 *               the sizes 'size' (a size's key, or 'custom'), for the parts 'count'
 *               (how many parts)
 */
function formulize_appearanceLookSummary($looks, $key, $theme) {
    $settings = $looks[$key]['settings'];
    $count = function ($n, $one, $many) { return sprintf($n == 1 ? $one : $many, $n); };
    $themeOwn = sprintf(_AM_APPEARANCE_CHANGE_THEME_OWN, $theme);
    $items = array();
    foreach (array('appearance_logo' => array('logo', _AM_APPEARANCE_CHANGE_LOGO, _AM_APPEARANCE_PHRASE_LOGO), 'appearance_favicon' => array('favicon', _AM_APPEARANCE_CHANGE_FAVICON, _AM_APPEARANCE_PHRASE_FAVICON)) as $name => $kind) {
        if (isset($settings[$name])) {
            $url = $settings[$name] !== '' ? (string) formulize_getAppearanceFileUrl(formulize_locateAppearanceFile($settings[$name], $theme), $theme) : '';
            $items[] = array('kind' => $kind[0], 'label' => $kind[1], 'detail' => $url === '' ? $themeOwn : '', 'image' => $url, 'phrase' => $kind[2]);
        }
    }
    $swatches = array();
    foreach (formulize_appearanceColourMap($theme) as $colourKey => $colour) {
        if (isset($settings['appearance_' . $colourKey])) {
            $swatches[$colour['label']] = $settings['appearance_' . $colourKey] !== '' ? $settings['appearance_' . $colourKey] : $colour['default'];
        }
    }
    if ($swatches) {
        $items[] = array('kind' => 'colours', 'label' => _AM_APPEARANCE_CHANGE_COLOURS, 'detail' => '', 'swatches' => $swatches, 'phrase' => $count(count($swatches), _AM_APPEARANCE_PHRASE_COLOUR, _AM_APPEARANCE_PHRASE_COLOURS));
    }
    $fonts = array();
    $fontMap = formulize_appearanceFontMap($theme);
    $fontName = function ($choice, $custom) use ($fontMap, $themeOwn) {
        if ($choice === 'custom') {
            return $custom;
        }
        return ($choice === '' OR !isset($fontMap[$choice])) ? $themeOwn : $fontMap[$choice]['name'];
    };
    if (isset($settings['appearance_font'])) {
        $fonts[] = $fontName($settings['appearance_font'], isset($settings['appearance_customfont']) ? $settings['appearance_customfont'] : '');
    }
    if (isset($settings['appearance_headingfont'])) {
        $heading = $settings['appearance_headingfont'] === '' ? _AM_APPEARANCE_CHANGE_MAIN_FONT : $fontName($settings['appearance_headingfont'], isset($settings['appearance_headingcustomfont']) ? $settings['appearance_headingcustomfont'] : '');
        $fonts[] = sprintf(_AM_APPEARANCE_CHANGE_HEADINGS, $heading);
    }
    if ($fonts) {
        $items[] = array('kind' => 'fonts', 'label' => count($fonts) == 1 ? _AM_APPEARANCE_CHANGE_FONT : _AM_APPEARANCE_CHANGE_FONTS, 'detail' => implode(', ', $fonts), 'phrase' => count($fonts) == 1 ? _AM_APPEARANCE_PHRASE_FONT : _AM_APPEARANCE_PHRASE_FONTS);
    }
    if (isset($settings['appearance_contentwidth'])) {
        $width = $settings['appearance_contentwidth'];
        $items[] = array('kind' => 'width', 'label' => _AM_APPEARANCE_CHANGE_WIDTH, 'detail' => $width === 'full' ? _AM_APPEARANCE_CHANGE_FULL_WIDTH : ($width === '' ? $themeOwn : sprintf(_AM_APPEARANCE_CHANGE_AT_MOST, $width)), 'phrase' => _AM_APPEARANCE_PHRASE_WIDTH);
    }
    $overrides = formulize_appearanceLookOverrides($settings);
    $sizeTokens = array_flip(formulize_appearanceSizeTokens());
    $sizes = count(array_intersect_key($overrides, $sizeTokens));
    if ($sizes) {
        $size = formulize_appearanceLookSize($looks, $key, $theme);
        $named = formulize_appearanceSizes($theme);
        $detail = isset($named[$size]) ? $named[$size]['name'] : sprintf(_AM_APPEARANCE_CHANGE_CUSTOM_SIZE, $count($sizes, _AM_APPEARANCE_PHRASE_SIZE, _AM_APPEARANCE_PHRASE_SIZES));
        $items[] = array('kind' => 'sizes', 'label' => _AM_APPEARANCE_CHANGE_SIZE, 'detail' => $detail, 'size' => isset($named[$size]) ? $size : 'custom',
            'phrase' => isset($named[$size]) ? sprintf(_AM_APPEARANCE_PHRASE_NAMED_SIZE, $named[$size]['name']) : $count($sizes, _AM_APPEARANCE_PHRASE_SIZE, _AM_APPEARANCE_PHRASE_SIZES));
    }
    $parts = array_diff_key($overrides, $sizeTokens);
    if ($parts) {
        // the parts they belong to, by name, in the order the editor has them
        $map = formulize_appearanceTokenMap();
        $desktop = array();
        foreach ($map['tokens'] as $token => $entry) {
            if (isset($entry['phone'])) {
                $desktop[$entry['phone']] = $token;
            }
        }
        $names = array();
        foreach ($map['components'] as $component) {
            foreach (array_keys($parts) as $token) {
                $token = isset($desktop[$token]) ? $desktop[$token] : $token;
                if (in_array($token, $component['tokens'], true)) {
                    $names[$component['name']] = true;
                    break;
                }
            }
        }
        $names = array_keys($names);
        $detail = implode(', ', array_slice($names, 0, 3));
        $detail = count($names) > 3 ? sprintf(_AM_APPEARANCE_CHANGE_MORE, $detail, count($names) - 3) : $detail;
        $items[] = array('kind' => 'parts', 'label' => count($names) == 1 ? _AM_APPEARANCE_CHANGE_PART : _AM_APPEARANCE_CHANGE_PARTS, 'detail' => $detail, 'count' => count($names), 'phrase' => $count(count($parts), _AM_APPEARANCE_PHRASE_PART, _AM_APPEARANCE_PHRASE_PARTS));
    }
    return $items;
}

/**
 * What a look changes itself, in a few words for a sentence: "logo, 2 colours,
 * Compact sizes".
 *
 * @param array $looks a theme's looks
 * @param string $key the look's key
 * @param string $theme theme folder name
 * @return array of short phrases
 */
function formulize_appearanceLookChanges($looks, $key, $theme) {
    return array_map(function ($item) { return $item['phrase']; }, formulize_appearanceLookSummary($looks, $key, $theme));
}

// ---------------------------------------------------------------------------
// Managing looks. Each of these checks what it is asked to do, does it, and
// writes the theme's stylesheets again, and says why when it can't.
// ---------------------------------------------------------------------------

/**
 * Write a theme's looks: each look's stylesheet, and the theme's appearance.css,
 * which records the look the site uses and is that look's CSS. Every one is
 * written, since a change to a look reaches every look based on it.
 *
 * @param string $theme theme folder name
 * @param array $looks the theme's looks, as they are to be
 * @param string $siteLook the key of the look the site is to use
 * @return boolean whether every file was written
 */
function formulize_writeAppearanceLookFiles($theme, $looks, $siteLook) {
    $dir = formulize_getAppearanceLooksDir($theme);
    if (formulize_prepareAppearanceDir($theme) === false OR (!is_dir($dir) AND !@mkdir($dir, 0775))) {
        return false;
    }
    $written = true;
    foreach ($looks as $key => $look) {
        $written = (file_put_contents($dir . '/' . $key . '.css', formulize_buildAppearanceLookCss($key, $looks, $theme)) !== false) AND $written;
    }
    $cssPath = formulize_getAppearanceCssPath($theme);
    $written = (file_put_contents($cssPath, formulize_buildAppearanceCss($theme, $looks, $siteLook)) !== false) AND $written;
    clearstatcache(true, $cssPath); // the file's mtime is read right after this
    return $written;
}

/**
 * Save a theme's looks as they are to be: write their files, and read them again.
 *
 * @param string $theme theme folder name
 * @param array $looks the theme's looks, as they are to be
 * @param string|null $siteLook the look the site is to use, defaults to the one it uses
 * @return string '' when saved, or what went wrong
 */
function formulize_saveAppearanceLooks($theme, $looks, $siteLook = null) {
    $siteLook = ($siteLook === null) ? formulize_getAppearanceSiteLook($theme) : $siteLook;
    // each look records only what differs from its base (formulize_appearanceLookDifferences);
    // dropping what is the same as a base changes nothing about how any look resolves,
    // so each is measured against the looks as they were given
    $given = $looks;
    foreach (array_keys($looks) as $key) {
        $looks[$key]['settings'] = formulize_appearanceLookDifferences($given, $key, $theme);
    }
    if (!formulize_writeAppearanceLookFiles($theme, $looks, $siteLook)) {
        formulize_getAppearanceLooks($theme, true);
        return sprintf(_AM_APPEARANCE_ERR_NOT_SAVED, $theme, formulize_getAppearanceDir($theme));
    }
    formulize_getAppearanceLooks($theme, true);
    return '';
}

/**
 * Whether a name can be given to a look: one none of the theme's looks has
 * already (not counting the look being renamed), and not too long. Names are
 * compared without regard to case.
 *
 * @param string $name the name
 * @param string $theme theme folder name
 * @param string $except the key of the look being renamed, if one is
 * @return string an error message, or '' if the name can be used
 */
function formulize_appearanceLookNameProblem($name, $theme, $except = '') {
    if ($name === '') {
        return _AM_APPEARANCE_ERR_NAME_EMPTY;
    }
    if (strlen($name) > 60) {
        return _AM_APPEARANCE_ERR_NAME_LONG;
    }
    // the lines its stylesheet's block starts and ends with, which would cut the block short
    foreach (formulize_appearanceLookBlockMarkers() as $marker) {
        if (strpos($name, $marker) !== false) {
            return _AM_APPEARANCE_ERR_NAME_BAD;
        }
    }
    foreach (formulize_getAppearanceLooks($theme) as $key => $look) {
        if ($key != $except AND strtolower($look['name']) == strtolower($name)) {
            return sprintf(_AM_APPEARANCE_ERR_NAME_TAKEN, $theme, $look['name']);
        }
    }
    return '';
}

/**
 * A name as a person typed it, made tidy: spaces trimmed and runs of them made one.
 *
 * @param string $name the name as typed
 * @return string the name
 */
function formulize_appearanceCleanLookName($name) {
    return trim(preg_replace('/\s+/', ' ', (string) $name));
}

/**
 * A key for a new look, made from its name: lower case letters, numbers and
 * hyphens, as its stylesheet's file name, and different from every look the
 * theme has, and every file in its looks folder.
 *
 * @param string $name the look's name
 * @param string $theme theme folder name
 * @return string the key
 */
function formulize_appearanceNewLookKey($name, $theme) {
    $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
    $base = ($base === '') ? 'look' : substr($base, 0, 40);
    $looks = formulize_getAppearanceLooks($theme);
    $key = $base;
    for ($n = 2; isset($looks[$key]) OR is_file(formulize_getAppearanceLooksDir($theme) . '/' . $key . '.css'); $n++) {
        $key = $base . '-' . $n;
    }
    return $key;
}

/**
 * Make a look. It is based on a look, or on nothing, and changes nothing, so it
 * looks like its base, unless it is given a size: that size's values are written
 * into it.
 *
 * @param string $theme theme folder name
 * @param string $name its name
 * @param string $base the key of the look it is based on, or '' for nothing
 * @param string $size a size's key (formulize_appearanceSizes), or '' for its base's
 * @param string $key set to the new look's key
 * @return string '' when made, or what went wrong
 */
function formulize_createAppearanceLook($theme, $name, $base, $size, &$key = null) {
    $looks = formulize_getAppearanceLooks($theme);
    $name = formulize_appearanceCleanLookName($name);
    if ($problem = formulize_appearanceLookNameProblem($name, $theme)) {
        return $problem;
    }
    if ($base !== '' AND !isset($looks[$base])) {
        return _AM_APPEARANCE_ERR_BASE_GONE;
    }
    $settings = array();
    $sizes = formulize_appearanceSizes($theme);
    // a size is written into the look; Standard needs nothing written into a look based on nothing
    if ($size !== '' AND isset($sizes[$size]) AND !($size == 'standard' AND $base === '')) {
        $settings['appearance_overrides'] = json_encode(formulize_appearanceSizeValues($size, $theme), JSON_UNESCAPED_SLASHES);
    }
    $key = formulize_appearanceNewLookKey($name, $theme);
    $looks[$key] = array('name' => $name, 'description' => '', 'base' => $base, 'settings' => formulize_sanitizeAppearanceLookSettings($settings, $theme));
    return formulize_saveAppearanceLooks($theme, $looks);
}

/**
 * Copy a look: a look with the same base, and the same changes of its own. It
 * looks the same, but doesn't follow the look it was copied from. The looks based
 * on that look aren't copied.
 *
 * @param string $theme theme folder name
 * @param string $from the key of the look to copy
 * @param string $name the copy's name
 * @param string $key set to the copy's key
 * @return string '' when made, or what went wrong
 */
function formulize_duplicateAppearanceLook($theme, $from, $name, &$key = null) {
    $looks = formulize_getAppearanceLooks($theme);
    if (!isset($looks[$from])) {
        return _AM_APPEARANCE_ERR_LOOK_GONE;
    }
    $name = formulize_appearanceCleanLookName($name);
    if ($problem = formulize_appearanceLookNameProblem($name, $theme)) {
        return $problem;
    }
    $key = formulize_appearanceNewLookKey($name, $theme);
    $looks[$key] = array('name' => $name, 'description' => $looks[$from]['description'], 'base' => $looks[$from]['base'], 'settings' => $looks[$from]['settings']);
    return formulize_saveAppearanceLooks($theme, $looks);
}

/**
 * Give a look another name. Its key, which screens and other looks know it by,
 * stays the same.
 *
 * @param string $theme theme folder name
 * @param string $key the look's key
 * @param string $name its new name
 * @return string '' when renamed, or what went wrong
 */
function formulize_renameAppearanceLook($theme, $key, $name) {
    $looks = formulize_getAppearanceLooks($theme);
    if (!isset($looks[$key])) {
        return _AM_APPEARANCE_ERR_LOOK_GONE;
    }
    $name = formulize_appearanceCleanLookName($name);
    if ($problem = formulize_appearanceLookNameProblem($name, $theme, $key)) {
        return $problem;
    }
    $looks[$key]['name'] = $name;
    return formulize_saveAppearanceLooks($theme, $looks);
}

/**
 * Base a look on another look, or on nothing. It keeps what it changes itself,
 * and takes everything else from its new base; a change of its own that is the
 * same as the new base's is dropped, and follows the new base from then on (see
 * formulize_appearanceLookDifferences, which every save applies). The
 * looks based on it move with it. It can't be based on itself, or on a look
 * based on it.
 *
 * @param string $theme theme folder name
 * @param string $key the look's key
 * @param string $base the key of its new base, or '' for nothing
 * @return string '' when done, or what went wrong
 */
function formulize_rebaseAppearanceLook($theme, $key, $base) {
    $looks = formulize_getAppearanceLooks($theme);
    if (!isset($looks[$key])) {
        return _AM_APPEARANCE_ERR_LOOK_GONE;
    }
    if ($base !== '' AND !isset($looks[$base])) {
        return _AM_APPEARANCE_ERR_BASE_GONE;
    }
    if ($base === $key OR in_array($base, formulize_appearanceLookDescendants($looks, $key), true)) {
        return $base === $key ? sprintf(_AM_APPEARANCE_ERR_BASE_SELF, $looks[$key]['name']) : sprintf(_AM_APPEARANCE_ERR_BASE_DESCENDANT, $looks[$key]['name'], $looks[$base]['name']);
    }
    if ($base === $looks[$key]['base']) {
        return '';
    }
    $looks[$key]['base'] = $base;
    return formulize_saveAppearanceLooks($theme, $looks);
}

/**
 * What a look sets itself that differs from its base: its own settings, without
 * any that are the same as the look it is based on, as that resolves, or as the
 * theme's own settings for a look with no base look. A look only records what
 * differs from its base, so it follows its base in everything else, including
 * when the base changes later. Every write of a theme's looks keeps them so
 * (formulize_saveAppearanceLooks), whatever made the value the same as the base's:
 * a change to the look, a change to its base, or a new base.
 *
 * @param array $looks a theme's looks
 * @param string $key the look's key
 * @param string $theme theme folder name
 * @return array the look's settings, without those that are the same as its base's
 */
function formulize_appearanceLookDifferences($looks, $key, $theme) {
    $base = $looks[$key]['base'];
    // what its base resolves to, unsanitized and sanitized, to compare each setting with
    $under = formulize_defaultAppearanceSettings();
    foreach (($base === '' OR !isset($looks[$base])) ? array() : formulize_appearanceLookChain($looks, $base) as $at) {
        $under = formulize_mergeAppearanceLook($under, $looks[$at]['settings']);
    }
    $baseSettings = formulize_sanitizeAppearanceSettings($under, $theme);
    $baseTokens = formulize_appearanceLookOverrides($baseSettings) + formulize_appearanceSizeDefaults($theme);
    $own = $looks[$key]['settings'];
    foreach ($own as $name => $value) {
        if ($name == 'appearance_overrides') {
            $tokens = formulize_appearanceLookOverrides($own);
            foreach ($tokens as $token => $tokenValue) {
                if (isset($baseTokens[$token]) AND $baseTokens[$token] == $tokenValue) {
                    unset($tokens[$token]);
                }
            }
            if ($tokens) {
                $own[$name] = json_encode($tokens, JSON_UNESCAPED_SLASHES);
            } else {
                unset($own[$name]);
            }
        } elseif ($name != 'appearance_customfont' AND $name != 'appearance_headingcustomfont') {
            $with = formulize_sanitizeAppearanceSettings(formulize_mergeAppearanceLook($under, array_intersect_key($own, array_flip(array($name, 'appearance_customfont', 'appearance_headingcustomfont')))), $theme);
            if ($with[$name] === $baseSettings[$name]) {
                unset($own[$name]);
                if ($name == 'appearance_font') {
                    unset($own['appearance_customfont']);
                } elseif ($name == 'appearance_headingfont') {
                    unset($own['appearance_headingcustomfont']);
                }
            }
        }
    }
    return $own;
}

/**
 * Use a look for the site: every page of the theme's, except a screen set to
 * another look.
 *
 * @param string $theme theme folder name
 * @param string $key the look's key
 * @return string '' when done, or what went wrong
 */
function formulize_setAppearanceSiteLook($theme, $key) {
    $looks = formulize_getAppearanceLooks($theme);
    if (!isset($looks[$key])) {
        return _AM_APPEARANCE_ERR_LOOK_GONE;
    }
    return formulize_saveAppearanceLooks($theme, $looks, $key);
}

/**
 * Save what a look changes itself, from the appearance editor: its own settings,
 * in place of the ones it had. Its name, description and base stay as they are.
 *
 * @param string $theme theme folder name
 * @param string $key the look's key
 * @param array $settings what the look is to change itself
 * @return string '' when saved, or what went wrong
 */
function formulize_saveAppearanceLookSettings($theme, $key, $settings) {
    $looks = formulize_getAppearanceLooks($theme);
    if (!isset($looks[$key])) {
        return _AM_APPEARANCE_ERR_LOOK_GONE_UNSAVED;
    }
    $looks[$key]['settings'] = formulize_sanitizeAppearanceLookSettings($settings, $theme);
    return formulize_saveAppearanceLooks($theme, $looks);
}

/**
 * What a change to a look reaches: whether the site, which it does when the look
 * is the site look or one the site look is based on, and how many screens, set to
 * the look or to a look based on it.
 *
 * @param string $theme theme folder name
 * @param string $key the look's key
 * @return array with 'site' (boolean) and 'screens' (how many)
 */
function formulize_appearanceLookReach($theme, $key) {
    $looks = formulize_getAppearanceLooks($theme);
    $reached = array_merge(array($key), formulize_appearanceLookDescendants($looks, $key));
    $screens = 0;
    foreach (formulize_appearanceLookScreens() as $lookKey => $list) {
        if (in_array($lookKey, $reached, true)) {
            $screens += count($list);
        }
    }
    return array('site' => in_array(formulize_getAppearanceSiteLook($theme), $reached, true), 'screens' => $screens);
}

/**
 * Delete a look, and the logo and favicon of its own that no other look has. The
 * look the site uses can't be deleted, nor a look others are based on. Screens set
 * to it use the site's look instead: on the site's own theme, they are set back
 * to it; on another theme they keep its key, and fall back to the site's look as
 * any screen does whose look its theme doesn't have.
 *
 * @param string $theme theme folder name
 * @param string $key the look's key
 * @return string '' when deleted, or what went wrong
 */
function formulize_deleteAppearanceLook($theme, $key) {
    $looks = formulize_getAppearanceLooks($theme);
    if (!isset($looks[$key])) {
        return '';
    }
    if ($key == formulize_getAppearanceSiteLook($theme)) {
        return sprintf(_AM_APPEARANCE_ERR_DELETE_SITE, $looks[$key]['name']);
    }
    if ($children = formulize_appearanceLookChildren($looks, $key)) {
        return sprintf(_AM_APPEARANCE_ERR_DELETE_CHILDREN, $looks[$key]['name']);
    }
    if (!@unlink(formulize_getAppearanceLooksDir($theme) . '/' . $key . '.css')) {
        return sprintf(_AM_APPEARANCE_ERR_NOT_DELETED, formulize_getAppearanceLooksDir($theme));
    }
    $gone = $looks[$key];
    unset($looks[$key]);
    $problem = formulize_saveAppearanceLooks($theme, $looks);
    foreach (array_keys(formulize_appearanceUploads()) as $name) {
        if (!empty($gone['settings'][$name])) {
            formulize_deleteAppearanceUploadedFile($gone['settings'][$name], $theme);
        }
    }
    if ($theme == formulize_getDefaultAppearanceTheme()) {
        global $xoopsDB;
        $xoopsDB->queryF('UPDATE ' . $xoopsDB->prefix('formulize_screen') . " SET look = '' WHERE look = " . $xoopsDB->quoteString($key));
    }
    return $problem;
}

/**
 * The screens set to a look of their own, by the look's key: what the Looks page
 * shows as each look's Used by. A screen's look is a key, the same whatever the
 * site's theme: it is the theme the page is shown in that has the look, or not.
 *
 * @return array look key => list of arrays with 'sid', 'fid' and 'title'
 */
function formulize_appearanceLookScreens() {
    global $xoopsDB;
    $screens = array();
    $result = $xoopsDB->query('SELECT sid, fid, title, look FROM ' . $xoopsDB->prefix('formulize_screen') . " WHERE look != '' ORDER BY title");
    while ($result AND $row = $xoopsDB->fetchArray($result)) {
        $screens[$row['look']][] = array('sid' => (int) $row['sid'], 'fid' => (int) $row['fid'], 'title' => (string) $row['title']);
    }
    return $screens;
}

/**
 * Whether the appearance editor has an advanced mode for a theme: clicking the
 * parts of the preview to change them, and looks of your own. Only for a theme
 * built on the component tokens (formulize_appearanceThemeUsesSizes); any other
 * theme is edited in simple mode only.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return boolean
 */
function formulize_appearanceThemeHasAdvancedMode($theme = null) {
    return formulize_appearanceThemeUsesSizes($theme);
}

/**
 * Whether a theme is built on the component size tokens, so that the sizes
 * (Standard, Compact, Comfortable) and the advanced editor change how it looks. A theme says so itself, by
 * declaring --formulize-size-tokens with its other tokens (Lyris does, in
 * css/tokens.css). A theme that doesn't, such as Anari, styles most of its
 * markup with its own fixed sizes, so offering it a Size setting that changed
 * only a few things would be confusing: the appearance editor leaves the setting
 * out for it, and no sizes are written into its stylesheet.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return boolean
 */
function formulize_appearanceThemeUsesSizes($theme = null) {
    $tokens = formulize_appearanceThemeTokens($theme);
    return isset($tokens['--formulize-size-tokens']);
}

/**
 * The narrowest and widest the maximum page width can be set to, in pixels, and
 * the width the appearance editor offers for a maximum width when the page is at
 * full width.
 *
 * @return array with 'min', 'max' and 'default'
 */
function formulize_appearanceContentWidthLimits() {
    return array('min' => 600, 'max' => 3840, 'default' => 1200);
}

/**
 * Whether a theme can keep its pages to a maximum width, so that the Page width
 * setting (appearance_contentwidth, in the appearance editor) changes how it
 * looks. A theme says so itself, by declaring --formulize-content-max-width with
 * its other tokens (Lyris does, in css/tokens.css) and reading it in its layout. A theme that doesn't, such as
 * Anari, isn't offered the setting, and no width is written into its stylesheet.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return boolean
 */
function formulize_appearanceThemeUsesContentWidth($theme = null) {
    $tokens = formulize_appearanceThemeTokens($theme);
    return isset($tokens['--formulize-content-max-width']);
}

/**
 * A page width as a whole number of pixels within the limits.
 *
 * @param string $value the width
 * @return string the width, or '' when it isn't one
 */
function formulize_appearanceContentWidthPixels($value) {
    $value = trim((string) $value);
    $limits = formulize_appearanceContentWidthLimits();
    return (ctype_digit($value) AND $value >= $limits['min'] AND $value <= $limits['max']) ? (string) (int) $value : '';
}

/**
 * A theme's own page width, which is what it has until another is chosen: the
 * width in pixels it declares for --formulize-content-max-width (Lyris declares
 * 1200px), or 'full' when it declares 100%, or doesn't declare the token.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string a width in pixels, eg: '1200', or 'full'
 */
function formulize_appearanceContentWidthDefault($theme = null) {
    $tokens = formulize_appearanceThemeTokens($theme);
    $declared = isset($tokens['--formulize-content-max-width']) ? $tokens['--formulize-content-max-width'] : '';
    $width = preg_match('/^(\d+)px$/', $declared, $match) ? formulize_appearanceContentWidthPixels($match[1]) : '';
    return $width !== '' ? $width : 'full';
}

/**
 * Validate a page width: 'full', or a maximum width as a whole number of pixels
 * within the limits. The theme's own width is recorded as nothing, like every
 * other default, so the setting is only written when it differs from the theme,
 * and 'full' is recorded for full width in a theme whose own width is a maximum.
 * Anything else is the theme's own width too.
 *
 * @param string $value the submitted width
 * @param string|null $theme the theme the width is for, whose own width is the
 *                           default. Defaults to the active theme.
 * @return string the width, 'full', or '' (the theme's own)
 */
function formulize_sanitizeAppearanceContentWidth($value, $theme = null) {
    $value = strtolower(trim((string) $value));
    $value = ($value === 'full') ? 'full' : formulize_appearanceContentWidthPixels($value);
    return ($value === formulize_appearanceContentWidthDefault($theme)) ? '' : $value;
}

/**
 * The page width a theme's settings call for: the one chosen, or the theme's own.
 *
 * @param array $settings appearance settings, already sanitized
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string a width in pixels, eg: '1200', or 'full'
 */
function formulize_appearanceContentWidth($settings, $theme = null) {
    $width = isset($settings['appearance_contentwidth']) ? (string) $settings['appearance_contentwidth'] : '';
    return $width !== '' ? $width : formulize_appearanceContentWidthDefault($theme);
}

/**
 * What the component tokens, and the tokens that follow them, are declared as for
 * a theme, as CSS: what formulize-ui.css declares on :root, overridden by anything
 * the theme declares on :root in its own css/tokens.css.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return array token => its CSS value
 */
function formulize_appearanceDeclaredTokens($theme = null) {
    $theme = formulize_resolveAppearanceTheme($theme);
    $declared = formulize_appearanceTokenDeclarations((string) @file_get_contents(XOOPS_ROOT_PATH . '/modules/formulize/templates/css/formulize-ui.css'));
    $themeTokens = ICMS_THEME_PATH . '/' . $theme . '/css/tokens.css';
    if ($theme AND is_file($themeTokens)) {
        $declared = array_merge($declared, formulize_appearanceTokenDeclarations((string) @file_get_contents($themeTokens)));
    }
    return $declared;
}

/**
 * The Default value of each size token for a theme: what formulize-ui.css
 * declares on :root, overridden by anything the theme declares on :root in its
 * own css/tokens.css. This is what the advanced editor shows as Default,
 * and what a preset or override is measured against.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return array token => value, as appearance_tokens.json writes values
 */
function formulize_appearanceSizeDefaults($theme = null) {
    $map = formulize_appearanceTokenMap();
    $declared = formulize_appearanceDeclaredTokens($theme);
    $defaults = array();
    foreach ($map['tokens'] as $token => $entry) {
        $value = isset($declared[$token]) ? formulize_appearanceTokenParse($entry['type'], $declared[$token]) : null;
        if ($value !== null) {
            $defaults[$token] = $value;
        }
    }
    return $defaults;
}

/**
 * The folder a theme keeps its sample screens for the advanced editor in:
 * appearance_preview, in the theme's own folder. Each sample is an HTML file
 * named after one of the screens in appearance_tokens.json (form.html,
 * list.html...); files starting with an underscore are pieces the samples
 * include (see formulize_renderAppearancePreview).
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string the folder's path
 */
function formulize_getAppearancePreviewDir($theme = null) {
    return ICMS_THEME_PATH . '/' . formulize_resolveAppearanceTheme($theme) . '/appearance_preview';
}

/**
 * The sample screens a theme provides, in the order appearance_tokens.json
 * lists them.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return array screen key => its name, eg: 'form' => 'Form'
 */
function formulize_getAppearancePreviewScreens($theme = null) {
    $map = formulize_appearanceTokenMap();
    $dir = formulize_getAppearancePreviewDir($theme);
    $screens = array();
    foreach (isset($map['screens']) ? $map['screens'] : array() as $key => $name) {
        if (is_file($dir . '/' . $key . '.html')) {
            $screens[$key] = $name;
        }
    }
    return $screens;
}

/**
 * The markup of one of a theme's sample screens, ready to put in a page's body.
 * A sample can include a piece shared with other samples by naming it in double
 * braces: {{list}} is the contents of _list.html in the same folder. {{logo_url}}
 * and {{site_name}} are the theme's logo (the uploaded one, or the theme's own
 * images/logo.png) and the site's name. Pieces can include other pieces, a few
 * levels deep.
 *
 * @param string $screen a screen key, eg: 'form'
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string the markup, or '' if the theme has no such sample
 */
function formulize_renderAppearancePreview($screen, $theme = null) {
    global $xoopsConfig;
    $theme = formulize_resolveAppearanceTheme($theme);
    $screens = formulize_getAppearancePreviewScreens($theme);
    if (!isset($screens[$screen])) {
        return '';
    }
    $dir = formulize_getAppearancePreviewDir($theme);
    $html = (string) file_get_contents($dir . '/' . $screen . '.html');
    $logo = formulize_getAppearanceLogoUrl($theme);
    $values = array(
        'logo_url' => htmlspecialchars($logo ? $logo : XOOPS_URL . '/themes/' . $theme . '/images/logo.png', ENT_QUOTES),
        'site_name' => htmlspecialchars(isset($xoopsConfig['sitename']) ? $xoopsConfig['sitename'] : '', ENT_QUOTES),
    );
    for ($depth = 0; $depth < 4; $depth++) {
        $html = preg_replace_callback('/\{\{([a-z][a-z0-9_]*)\}\}/', function ($match) use ($values, $dir) {
            if (isset($values[$match[1]])) {
                return $values[$match[1]];
            }
            $piece = $dir . '/_' . $match[1] . '.html';
            return is_file($piece) ? (string) file_get_contents($piece) : '';
        }, $html);
    }
    return $html;
}

/**
 * Validate the overrides set in the advanced editor: a JSON object of token =>
 * value, the values written the way appearance_tokens.json writes them (a
 * number of spacing steps, a text step, a weight...). Tokens that aren't in the
 * map, and values outside what a token can be set to (its own limits), are
 * dropped, so nothing arbitrary reaches the stylesheet. Kept as JSON, in token
 * order, so the same overrides always read and write the same way.
 *
 * @param mixed $value the overrides, as a JSON string or an array
 * @return string the clean overrides as JSON, or '' when there are none
 */
function formulize_sanitizeAppearanceOverrides($value) {
    $overrides = is_array($value) ? $value : json_decode((string) $value, true);
    if (!is_array($overrides)) {
        return '';
    }
    $map = formulize_appearanceTokenMap();
    $clean = array();
    foreach ($map['tokens'] as $token => $entry) {
        if (isset($overrides[$token]) AND formulize_appearanceTokenAllows($map, $token, $overrides[$token])) {
            // numbers stay numbers and text steps stay names, as in the map
            $clean[$token] = is_numeric($overrides[$token]) ? $overrides[$token] + 0 : (string) $overrides[$token];
        }
    }
    return $clean ? json_encode($clean, JSON_UNESCAPED_SLASHES) : '';
}

/**
 * The value each component token gets from a theme's settings, before it is
 * written as CSS: the ones a look sets, itself or through the looks it is based on
 * (see formulize_getAppearanceEffectiveSettings). Tokens that aren't set are
 * left out, so the CSS's own Default applies.
 *
 * @param array $settings appearance settings, already sanitized
 * @return array token => value, as appearance_tokens.json writes values
 */
function formulize_getAppearanceSizeValues($settings) {
    $map = formulize_appearanceTokenMap();
    $overrides = json_decode(isset($settings['appearance_overrides']) ? (string) $settings['appearance_overrides'] : '', true);
    $overrides = is_array($overrides) ? $overrides : array();
    $values = array();
    foreach ($map['tokens'] as $token => $entry) {
        if (isset($overrides[$token])) {
            $values[$token] = $overrides[$token];
        }
    }
    return $values;
}

/**
 * The names of all the appearance settings, ie: the keys of a settings array:
 * everything a look can change. Which look the site uses is recorded apart from
 * them (see formulize_getAppearanceSiteLook).
 *
 * @return array of setting names
 */
function formulize_appearanceSettingNames() {
    $names = array('appearance_font', 'appearance_customfont', 'appearance_headingfont',
        'appearance_headingcustomfont', 'appearance_overrides',
        'appearance_contentwidth', 'appearance_logo', 'appearance_favicon');
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
 * The images that can be uploaded for a theme: the logo in the site header, and
 * the favicon in the browser tab. They are handled identically - the file goes
 * in the theme's appearance folder and the stylesheet records which file is in
 * use - so each one is just a description of its own form fields and the image
 * types it accepts. The favicon also takes .ico, which browsers only ever want
 * for a favicon.
 *
 * The form fields are <setting>_file for the upload, and <setting>_remove to go
 * back to the theme's own image.
 *
 * @return array setting name => array with 'noun', 'filePrefix', 'types' (mime
 *               type => extension) and 'typesLabel'
 */
function formulize_appearanceUploads() {
    $imageTypes = array(
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
        'image/webp' => 'webp',
    );
    return array(
        'appearance_logo' => array(
            'noun' => _AM_APPEARANCE_UPLOAD_LOGO,
            'filePrefix' => 'formulize-appearance-logo-',
            'types' => $imageTypes,
            'typesLabel' => _AM_APPEARANCE_UPLOAD_LOGO_TYPES,
        ),
        'appearance_favicon' => array(
            'noun' => _AM_APPEARANCE_UPLOAD_FAVICON,
            'filePrefix' => 'formulize-appearance-favicon-',
            'types' => $imageTypes + array(
                'image/vnd.microsoft.icon' => 'ico',
                'image/x-icon' => 'ico',
            ),
            'typesLabel' => _AM_APPEARANCE_UPLOAD_FAVICON_TYPES,
        ),
    );
}

/**
 * Delete an uploaded appearance file a theme is using, when it is being removed
 * or replaced. Only a file in the theme's own appearance folder is deleted: a
 * file still sitting in the legacy uploads/appearance folder predates per-theme
 * settings and can be shared with another theme, so it is left alone and simply
 * stops being referenced.
 *
 * @param string $file the file name, as the settings record it
 * @param string $theme theme folder name
 */
function formulize_deleteAppearanceUploadedFile($file, $theme) {
    $path = formulize_locateAppearanceFile($file, $theme);
    // a look can have the same image, as a copy of another look does, and the theme's
    // own settings name theirs: an image something still names stays
    if ($path AND strpos($path, formulize_getAppearanceDir($theme) . '/') === 0 AND !formulize_appearanceFileInUse($file, $theme)) {
        unlink($path);
    }
}

/**
 * Whether one of a theme's looks has an uploaded image, a logo or favicon, so the
 * file has to stay. Called once the looks that stopped using the file have been
 * saved. A copy of a look has the same images as the look it was copied from.
 *
 * @param string $file the file's name
 * @param string $theme theme folder name
 * @return boolean
 */
function formulize_appearanceFileInUse($file, $theme) {
    foreach (formulize_getAppearanceLooks($theme, true) as $look) {
        foreach (array_keys(formulize_appearanceUploads()) as $name) {
            if (isset($look['settings'][$name]) AND $look['settings'][$name] === $file) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Take the logo and favicon uploads of a submitted form, from the appearance
 * editor. The logo and the favicon are images, so they can't be values in the
 * stylesheet the way the colours and the font are: the files are kept beside the
 * stylesheet in the theme's appearance folder, and the stylesheet records which
 * file is in use, so the stylesheet is still the one place the settings are read
 * from.
 *
 * A setting only changes when there is a new file to put in its place, or the old
 * one was asked to go (<setting>_remove), so a rejected upload leaves it alone.
 * Nothing is deleted here: the file a setting no longer names is only deleted
 * once the settings that stopped naming it have been written, and a new file once
 * the save it came with has failed (formulize_deleteAppearanceUploadedFile, by
 * the caller), so a save that fails never leaves a stylesheet naming a file that
 * is gone.
 *
 * Each file gets a name of its own, with some random characters as well as the
 * time, so two uploads in the same second can't have the same name.
 *
 * @param array $submitted the settings being saved, with the logo and favicon as
 *                         they stand; they are set to the new file, or '' when removed
 * @param string $theme theme folder name
 * @param array $errors gets a message for each upload that couldn't be taken
 * @param array|null $only the uploads to take (setting names), or null for both
 * @return array $submitted, with the logo and favicon settled
 */
function formulize_saveAppearanceUploads($submitted, $theme, &$errors, $only = null) {
    foreach (formulize_appearanceUploads() as $uploadSetting => $upload) {
        if ($only !== null AND !in_array($uploadSetting, $only)) {
            continue;
        }
        $field = $uploadSetting . '_file';
        $newFile = '';
        if (isset($_FILES[$field]) AND $_FILES[$field]['error'] == UPLOAD_ERR_OK) {
            $mimeType = mime_content_type($_FILES[$field]['tmp_name']);
            if (isset($upload['types'][$mimeType])) {
                $fileName = $upload['filePrefix'] . time() . '-' . bin2hex(random_bytes(4)) . '.' . $upload['types'][$mimeType];
                $appearanceDir = formulize_prepareAppearanceDir($theme);
                if ($appearanceDir AND move_uploaded_file($_FILES[$field]['tmp_name'], $appearanceDir . '/' . $fileName)) {
                    $newFile = $fileName;
                } else {
                    $errors[] = sprintf(_AM_APPEARANCE_UPLOAD_NOT_MOVED, $upload['noun'], formulize_getAppearanceDir($theme));
                }
            } else {
                $errors[] = sprintf(_AM_APPEARANCE_UPLOAD_WRONG_TYPE, $upload['noun'], $upload['typesLabel']);
            }
        }
        if ($newFile OR !empty($_POST[$uploadSetting . '_remove'])) {
            $submitted[$uploadSetting] = $newFile;
        }
    }
    return $submitted;
}

/**
 * What each main font choice renders as, for previewing it before it is saved:
 * the font-family value a save would write, and the Google Fonts css2 family
 * parameter to load it with ('' when there is nothing to load). The default
 * choice is the theme's own --fz-font-sans (Geist on Lyris, Poppins on Anari),
 * not the font map's nominal Geist stack, so previewing "default" shows the
 * theme being edited. 'custom' carries nothing: it is built from whatever
 * family name has been typed in.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return array font key => array with 'stack' and 'google'
 */
function formulize_appearanceFontPreviewStacks($theme = null) {
    $themeTokens = formulize_appearanceThemeTokens($theme);
    $stacks = array();
    foreach (formulize_appearanceFontMap($theme) as $key => $font) {
        if ($key == 'custom') {
            $stacks[$key] = array('stack' => '', 'google' => '');
        } elseif ($key == 'geist') {
            $stacks[$key] = array(
                'stack' => isset($themeTokens['--fz-font-sans']) ? $themeTokens['--fz-font-sans'] : $font['stack'],
                'google' => formulize_appearanceThemeWebfont($theme),
            );
        } else {
            $stacks[$key] = array('stack' => $font['stack'], 'google' => $font['google'] ? $font['google'] : '');
        }
    }
    return $stacks;
}

/**
 * The address of the appearance editor for a theme, or '' when the theme can't be
 * edited there: it has to provide sample screens to preview its appearance on.
 * Lyris and Anari do. Advanced mode is only for a theme built on Formulize UI's
 * component tokens too (formulize_appearanceThemeHasAdvancedMode).
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string the URL, or ''
 */
function formulize_getAppearanceEditorUrl($theme = null) {
    $theme = formulize_resolveAppearanceTheme($theme);
    if (!formulize_getAppearancePreviewScreens($theme)) {
        return '';
    }
    return XOOPS_URL . '/modules/formulize/appearance_editor.php?theme=' . urlencode($theme);
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
    $clean['appearance_overrides'] = formulize_sanitizeAppearanceOverrides(isset($values['appearance_overrides']) ? $values['appearance_overrides'] : '');
    // the theme's own page width (1200 pixels in Lyris) is recorded as nothing, and
    // full width, in a theme with a maximum width of its own, as 'full'
    $clean['appearance_contentwidth'] = formulize_sanitizeAppearanceContentWidth(isset($values['appearance_contentwidth']) ? $values['appearance_contentwidth'] : '', $theme);
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
 * Generation 3: the Text size and Density settings (the root font size and
 * --fz-spacing) were replaced by the Size preset and the advanced size
 * settings, which set the component size tokens.
 * Generation 4: the Size preset became the look applied to the site
 * (appearance_look), and the CSS is the theme's settings with that look on top;
 * each look has a stylesheet of its own too, in appearance/looks.
 * Generation 5: a part's own settings (the component tokens) belong to looks,
 * and are no longer written from the theme's own settings.
 * Generation 6: the theme's own settings are the Default look, which every other
 * look builds on, and have a part's own settings again.
 * Generation 7: the webfont fetched when no font has been chosen is the theme's
 * own (Poppins on Anari), where it was Geist whatever the theme.
 * Generation 8: looks are a tree, Default among them, and the theme's own
 * stylesheet records which look the site uses and is that look's CSS.
 *
 * @return string the whole comment, one line
 */
function formulize_appearanceCssGenerationMarker() {
    return '/* Formulize appearance stylesheet, generation 8 */';
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
 * Build the block at the top of a theme's appearance.css, as a CSS comment: the
 * record of which look the site uses. Each look's own stylesheet has the record
 * of that look (see formulize_buildAppearanceLookCss).
 *
 * A comment block, rather than anything in the CSS further down, so that it can
 * be read back as it was written (see formulize_readAppearanceCssValues).
 *
 * @param string $siteLook the key of the look the site uses
 * @param string $theme theme folder name, for the human-readable heading
 * @return string the CSS comment block, no trailing newline
 */
function formulize_buildAppearanceSettingsBlock($siteLook, $theme) {
    $markers = formulize_appearanceSettingsBlockMarkers();
    return implode("\n", array(
        '/* Formulize appearance for the ' . preg_replace('/[^A-Za-z0-9._ -]/', '', (string) $theme) . ' theme.',
        ' *',
        ' * Written by Formulize whenever the theme\'s looks change (Admin > Appearance >',
        ' * Looks). The block below records the look the site uses, and the CSS after it',
        ' * is that look\'s. Each look is kept in its own stylesheet, in the looks folder',
        ' * beside this file. If this file is deleted, it is written again.',
        ' *',
        ' * ' . $markers['start'],
        ' * appearance_look: ' . preg_replace('/[^a-z0-9-]/', '', (string) $siteLook),
        ' * ' . $markers['end'],
        ' */',
    ));
}

/**
 * Read the values in the block at the top of a theme's appearance.css, as they
 * were written: the look the site uses, and in a stylesheet written before looks
 * were kept apart, the theme's own appearance settings, which become Default's
 * (see formulize_appearanceCreateDefaultLook).
 *
 * The whole block has to be there: if the start marker is missing the file was
 * not written by us, and if the end marker is missing it was cut short. Either
 * way the answer is "nothing here". Whoever uses a value validates it.
 *
 * @param string $path path of the stylesheet to read
 * @return array|false setting name => value, or false when the file has no usable block
 */
function formulize_readAppearanceCssValues($path) {
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
    return $values;
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
 * The appearance settings a theme's pages are shown with: those of the look the
 * site uses (see formulize_getAppearanceLookSettings for any other look).
 *
 * @param string|null $theme theme folder name, defaults to the theme rendering the page
 * @return array setting name => value (all setting names present)
 */
function formulize_getAppearanceSettings($theme = null) {
    return formulize_getAppearanceLookSettings($theme);
}

/**
 * Resolve one font choice (a key into formulize_appearanceFontMap, plus the
 * family typed in for the 'custom' choice) into the webfont to fetch and the
 * font-family value to use. Shared by the main font and the secondary font, so
 * the two behave identically, custom families included.
 *
 * @param string $choice       font key as saved in the settings
 * @param string $customFamily family name for the 'custom' choice
 * @param string|null $theme   the theme the choice is for, whose own font the default
 *                             choice is. Defaults to the active theme.
 * @return array with 'key' (the choice actually resolved to), 'google'
 *               (css2 family parameter, or false when no webfont is needed) and
 *               'stack' (the css font-family value)
 */
function formulize_resolveAppearanceFontChoice($choice, $customFamily, $theme = null) {
    $fonts = formulize_appearanceFontMap($theme);
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
 * @param string|null $theme the theme the settings are for, whose own font is the
 *                           default. Defaults to the active theme.
 * @return array with 'url' (string|false), 'stack' (string|false when default)
 *               and 'heading' (string|false when headings follow the main font)
 */
function formulize_getAppearanceFont($settings = null, $theme = null) {
    $settings = is_array($settings) ? $settings : formulize_getAppearanceSettings($theme);
    $font = formulize_resolveAppearanceFontChoice(
        isset($settings['appearance_font']) ? $settings['appearance_font'] : '',
        isset($settings['appearance_customfont']) ? $settings['appearance_customfont'] : '',
        $theme
    );
    $heading = formulize_resolveAppearanceFontChoice(
        isset($settings['appearance_headingfont']) ? $settings['appearance_headingfont'] : '',
        isset($settings['appearance_headingcustomfont']) ? $settings['appearance_headingcustomfont'] : '',
        $theme
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
    // the page's screen may have a look of its own, with a logo of its own
    $settings = formulize_getAppearanceEffectiveSettings($theme, formulize_appearancePageLook($theme));
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
    $settings = formulize_getAppearanceEffectiveSettings($theme, formulize_appearancePageLook($theme));
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
 * (the appearance editor) or fall back to inline styles (the theme head).
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
 * separate one is chosen for headings and labels, the component size tokens
 * a look sets, and the colour tokens
 * (with their derived variants) for every colour that differs from the design
 * defaults.
 *
 * @param array|null $settings appearance settings to use, defaults to the saved ones
 * @param string|null $theme the theme being styled, whose own palette is what
 *                           counts as default. Defaults to the active theme.
 * @return array CSS custom property => value
 */
function formulize_getAppearanceCssOverrides($settings = null, $theme = null) {
    $settings = is_array($settings) ? $settings : formulize_getAppearanceSettings($theme);
    $overrides = array();
    $font = formulize_getAppearanceFont($settings, $theme);
    if ($font['stack']) {
        $overrides['--fz-font-sans'] = $font['stack'];
    }
    // Left alone, a theme's --fz-font-heading is var(--fz-font-sans), so headings and labels
    // follow the main font without anything being written here.
    if ($font['heading']) {
        $overrides['--fz-font-heading'] = $font['heading'];
    }
    if (formulize_appearanceThemeUsesSizes($theme)) {
        $map = formulize_appearanceTokenMap();
        foreach (formulize_getAppearanceSizeValues($settings) as $token => $value) {
            $overrides[$token] = formulize_appearanceTokenCss($map['tokens'][$token]['type'], $value);
            // and the tokens that follow it, such as the main button's hover colour
            $overrides = array_merge($overrides, formulize_appearanceDerivedCss($map['tokens'][$token], $overrides[$token]));
        }
    }
    // only a page width other than the theme's own is recorded, so the theme's
    // declaration applies otherwise
    if (!empty($settings['appearance_contentwidth']) AND formulize_appearanceThemeUsesContentWidth($theme)) {
        $overrides['--formulize-content-max-width'] = ($settings['appearance_contentwidth'] == 'full') ? '100%' : $settings['appearance_contentwidth'] . 'px';
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
 * Build a theme's appearance.css: the record of which look the site uses (see
 * formulize_buildAppearanceSettingsBlock), followed by that look's CSS.
 *
 * Assembled here in PHP rather than from a Smarty template, deliberately: this
 * file and each look's are the record of the theme's appearance, so they have to
 * come out complete every time, and Smarty only re-checks a template's source
 * when the "update module templates from file" setting is on. Building the files
 * in PHP means the code that writes them and the code that reads them back can
 * never fall out of step.
 *
 * @param string $theme theme folder name
 * @param array $looks the theme's looks, as they are to be
 * @param string $siteLook the key of the look the site uses
 * @return string the CSS
 */
function formulize_buildAppearanceCss($theme, $looks, $siteLook) {
    return formulize_buildAppearanceSettingsBlock($siteLook, $theme) . "\n"
        . formulize_buildAppearanceCssRules(formulize_appearanceResolveLook($looks, $siteLook, $theme), $theme);
}

/**
 * The CSS for a set of settings, after a stylesheet's settings block: the
 * generation marker, the webfont import, and the custom properties they set.
 *
 * @param array $settings appearance settings, sanitized: a look's, as it resolves
 * @param string $theme theme folder name
 * @return string the CSS
 */
function formulize_buildAppearanceCssRules($settings, $theme) {
    $font = formulize_getAppearanceFont($settings, $theme);
    $css = formulize_appearanceCssGenerationMarker() . "\n";
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
    return $css;
}

/**
 * Build the stylesheet for one of a theme's looks: the look's own block, which is
 * the record of it (its name, description, base and the settings it changes, see
 * formulize_appearanceParseLookBlock), followed by the CSS of the look as pages
 * are shown with it. A screen set to the look uses this stylesheet in place of
 * the theme's appearance.css.
 *
 * @param string $key the look's key
 * @param array $looks the theme's looks, as they are to be
 * @param string $theme theme folder name
 * @return string the CSS
 */
function formulize_buildAppearanceLookCss($key, $looks, $theme) {
    $look = $looks[$key];
    $markers = formulize_appearanceLookBlockMarkers();
    // the name and description are one line each, in a comment, and can't have the
    // block's own start and end in them, which would cut the block short
    $clean = function ($text) use ($markers) {
        return str_replace(array("\r", "\n", '*/', $markers['start'], $markers['end']), ' ', (string) $text);
    };
    $lines = array(
        '/* Formulize appearance look for the ' . preg_replace('/[^A-Za-z0-9._ -]/', '', (string) $theme) . ' theme.',
        ' *',
        ' * Written by Formulize whenever the theme\'s looks change (Admin > Appearance >',
        ' * Looks). The block below is the record of the look: its name, the look it is',
        ' * based on, if any, and what it changes itself. The CSS after it is the look',
        ' * as pages are shown with it.',
        ' *',
        ' * ' . $markers['start'],
        ' * name: ' . $clean($look['name']),
    );
    if ($look['description'] !== '') {
        $lines[] = ' * description: ' . $clean($look['description']);
    }
    if ($look['base'] !== '') {
        $lines[] = ' * base: ' . $look['base'];
    }
    foreach ($look['settings'] as $name => $value) {
        $lines[] = rtrim(' * ' . $name . ': ' . str_replace('*/', '', $value));
    }
    $lines[] = ' * ' . $markers['end'];
    $lines[] = ' */';
    return implode("\n", $lines) . "\n" . formulize_buildAppearanceCssRules(formulize_appearanceResolveLook($looks, $key, $theme), $theme);
}

/**
 * Write a theme's appearance files again, as its looks stand: each look's
 * stylesheet and the theme's appearance.css. The files are the record of the
 * looks, so a failure here means nothing was saved, and callers must say so.
 *
 * Also called by formulize_renderAppearanceHead when the theme's appearance.css
 * is missing, or was written by an earlier generation of this code.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return boolean whether every file was written
 */
function formulize_regenerateAppearanceCss($theme = null) {
    $theme = formulize_resolveAppearanceTheme($theme);
    if (formulize_prepareAppearanceDir($theme) === false) {
        return false;
    }
    return formulize_writeAppearanceLookFiles($theme, formulize_getAppearanceLooks($theme), formulize_getAppearanceSiteLook($theme));
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
        $cssExists = formulize_regenerateAppearanceCss($theme);
    }
    $html = '';
    // the page's screen can have a look of its own, which is shown with that look's stylesheet in place of
    // the theme's own: each look's stylesheet is the whole of the theme's appearance with the look
    $pageLook = formulize_appearancePageLook($theme);
    $font = formulize_getAppearanceFont(formulize_getAppearanceEffectiveSettings($theme, $pageLook), $theme);
    if ($font['url']) {
        $html .= '<link rel="preconnect" href="https://fonts.googleapis.com" />' . "\n";
        $html .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />' . "\n";
    }
    $lookCssPath = $pageLook !== null ? formulize_getAppearanceLooksDir($theme) . '/' . $pageLook . '.css' : '';
    if ($lookCssPath AND !file_exists($lookCssPath)) {
        formulize_regenerateAppearanceCss($theme); // the theme's files are behind its looks
        clearstatcache(true, $lookCssPath);
    }
    if ($lookCssPath AND file_exists($lookCssPath)) {
        $html .= '<link rel="stylesheet" type="text/css" media="all" href="' . formulize_getAppearanceUrl($theme) . '/looks/' . $pageLook . '.css?v=' . filemtime($lookCssPath) . '" />' . "\n";
    } elseif ($lookCssPath) {
        $html .= '<style type="text/css" media="all">' . "\n" . formulize_buildAppearanceCssRules(formulize_getAppearanceEffectiveSettings($theme, $pageLook), $theme) . "\n" . '</style>' . "\n";
    } elseif ($cssExists) {
        $html .= '<link rel="stylesheet" type="text/css" media="all" href="' . formulize_getAppearanceUrl($theme) . '/appearance.css?v=' . filemtime($cssPath) . '" />' . "\n";
    } else {
        $html .= '<style type="text/css" media="all">' . "\n" . formulize_buildAppearanceCssRules(formulize_getAppearanceSettings($theme), $theme) . "\n" . '</style>' . "\n";
    }
    // a screen set to use the full width of the window, whatever its look allows. After the stylesheet,
    // so it wins; a theme that keeps its pages to the page width uses this property to do it
    $pageScreen = formulize_appearancePageScreen();
    if ($pageScreen AND $pageScreen->getVar('pagewidth') == 'full' AND formulize_appearanceThemeUsesContentWidth($theme)) {
        $html .= '<style type="text/css" media="all">:root { --formulize-content-max-width: 100%; }</style>' . "\n";
    }
    return $html;
}

/**
 * The screen that a page is, if it is one: the one Formulize rendered as the page, whose id it gives
 * the theme as formulize_screen_id (see initialize.php). Not a screen inside another, such as a form
 * a template screen's code displays: the page's appearance is the outer screen's.
 *
 * @return object|null the screen, or null when the page is not a screen
 */
function formulize_appearancePageScreen() {
    global $xoopsTpl;
    static $screens = array();
    $sid = is_object($xoopsTpl) ? intval($xoopsTpl->get_template_vars('formulize_screen_id')) : 0;
    if (!$sid) {
        return null;
    }
    if (!array_key_exists($sid, $screens)) {
        $screen_handler = xoops_getmodulehandler('screen', 'formulize');
        $screen = $screen_handler->get($sid);
        $screens[$sid] = $screen ? $screen : null;
    }
    return $screens[$sid];
}

/**
 * The look a page is shown with, when it isn't the site look: the look the page's
 * screen is set to (on its Appearance tab), if the theme has that look.
 *
 * @param string|null $theme theme folder name, defaults to the active theme
 * @return string|null the look's key, or null for the site's look
 */
function formulize_appearancePageLook($theme = null) {
    $screen = formulize_appearancePageScreen();
    $look = $screen ? (string) $screen->getVar('look') : '';
    if ($look === '') {
        return null;
    }
    $looks = formulize_getAppearanceLooks($theme);
    return isset($looks[$look]) ? $look : null;
}
