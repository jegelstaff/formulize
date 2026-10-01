<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
###############################################################################
##  Author of this file: Formulize Incorporated                              ##
##  Project: Formulize                                                       ##
###############################################################################

// The size tokens: the sizes the Appearance page's Size presets and advanced
// settings can change, kept in appearance_tokens.json beside this file. That
// file is data only; this is what reads it: what values each type of token can
// have, how a value is written in CSS, and how a value written in CSS is read
// back, which is how a token's Default is found (it is whatever the CSS
// declares, and is not in the JSON).
//
// Depends on nothing else, so the catalog check (ui_catalog.php --check) can
// use it from the command line.

/**
 * The size token map, as appearance_tokens.json has it: 'types', 'groups' and
 * 'tokens', each token with its group, label, description, type and presets.
 *
 * @return array the map
 */
function formulize_appearanceTokenMap() {
    static $map = null;
    if ($map === null) {
        $map = json_decode(file_get_contents(__DIR__ . '/appearance_tokens.json'), true);
        if (!is_array($map)) {
            $map = array('types' => array(), 'groups' => array(), 'tokens' => array());
        }
    }
    return $map;
}

/**
 * Whether a value is one a type of token can have.
 *
 * @param array $type the type, from the map's 'types'
 * @param mixed $value the value, as the map and the saved settings write it: a
 *                     number of steps, a text step name, a weight, a line
 *                     height or a number of characters
 * @return boolean
 */
function formulize_appearanceTokenValueIsValid($type, $value) {
    if (isset($type['values'])) {
        // compared as strings, so that 600 and "600", or 1.5 and "1.5", are the same value
        return in_array((string) $value, array_map('strval', $type['values']), true);
    }
    if (!is_numeric($value)) {
        return false;
    }
    $value = (float) $value;
    if ($value < $type['min'] OR $value > $type['max']) {
        return false;
    }
    $steps = ($value - $type['min']) / $type['step'];
    return abs($steps - round($steps)) < 0.000001;
}

/**
 * Whether a value is one a particular token can be set to: one its type can
 * have, and inside the token's own limits, its min and max or its list of
 * values. The limits are what the Appearance page offers, chosen so that every
 * value in them changes how things look.
 *
 * @param array $map the map, from formulize_appearanceTokenMap()
 * @param string $token the token, eg: --fz-field-height
 * @param mixed $value the value, as the map writes values
 * @return boolean
 */
function formulize_appearanceTokenAllows($map, $token, $value) {
    if (!isset($map['tokens'][$token]) OR !isset($map['types'][$map['tokens'][$token]['type']])) {
        return false;
    }
    $entry = $map['tokens'][$token];
    if (!formulize_appearanceTokenValueIsValid($map['types'][$entry['type']], $value)) {
        return false;
    }
    if (isset($entry['values'])) {
        return in_array((string) $value, array_map('strval', $entry['values']), true);
    }
    if (isset($entry['min']) AND (float) $value < $entry['min']) {
        return false;
    }
    if (isset($entry['max']) AND (float) $value > $entry['max']) {
        return false;
    }
    return true;
}

/**
 * The size of each text step at the default 16px root, in px: the values of
 * the --fz-text-* tokens in formulize-ui.css.
 *
 * @return array step => px
 */
function formulize_appearanceTextPx() {
    return array('xs' => 12, 'xs-plus' => 13, 'sm' => 14, 'sm-plus' => 15, 'base' => 16, 'lg' => 18, 'xl' => 20, '2xl' => 24, '3xl' => 30);
}

/**
 * The least a token with a floor can usefully be, given the other tokens'
 * values: one line of a text token at a line height plus extra px, rounded up
 * to a half step; or another token plus a number of steps. The advanced size
 * editor works this out the same way (include/js/appearance_sizes.js).
 *
 * @param array $floor the token's floor, from the map
 * @param array $values token => value, for the tokens the floor refers to
 * @return float|null the floor in steps, or null if a value it needs is missing
 */
function formulize_appearanceTokenFloor($floor, $values) {
    if (isset($floor['text'])) {
        $px = formulize_appearanceTextPx();
        if (!isset($values[$floor['text']], $px[$values[$floor['text']]])) {
            return null;
        }
        return ceil(($px[$values[$floor['text']]] * $floor['leading'] + $floor['extra']) / 4 * 2) / 2;
    }
    return isset($values[$floor['token']]) ? $values[$floor['token']] + $floor['plus'] : null;
}

/**
 * A token's value written as CSS.
 *
 * @param string $typeName the token's type: spacing, text, weight, leading or measure
 * @param mixed $value a value the type can have (see formulize_appearanceTokenValueIsValid)
 * @return string the CSS, eg: calc(var(--fz-spacing) * 8.5), var(--fz-text-sm), 65ch
 */
function formulize_appearanceTokenCss($typeName, $value) {
    switch ($typeName) {
        case 'spacing':
            return 'calc(var(--fz-spacing) * ' . formulize_appearanceTokenNumber($value) . ')';
        case 'text':
            return 'var(--fz-text-' . $value . ')';
        case 'measure':
            return formulize_appearanceTokenNumber($value) . 'ch';
        default:
            return formulize_appearanceTokenNumber($value);
    }
}

/**
 * Read a token's value back from the CSS it is written as: the reverse of
 * formulize_appearanceTokenCss. Only the forms that function writes are
 * understood, which is what keeps the Defaults in the CSS readable by the
 * Appearance page.
 *
 * @param string $typeName the token's type
 * @param string $css the CSS value
 * @return mixed the value, or null if the CSS isn't in the form the type is written in
 */
function formulize_appearanceTokenParse($typeName, $css) {
    $css = trim($css);
    switch ($typeName) {
        case 'spacing':
            return preg_match('/^calc\(\s*var\(--fz-spacing\)\s*\*\s*([0-9]+(?:\.[0-9]+)?)\s*\)$/', $css, $match) ? (float) $match[1] : null;
        case 'text':
            return preg_match('/^var\(--fz-text-([a-z0-9-]+)\)$/', $css, $match) ? $match[1] : null;
        case 'measure':
            return preg_match('/^([0-9]+(?:\.[0-9]+)?)ch$/', $css, $match) ? (float) $match[1] : null;
        default:
            return is_numeric($css) ? (float) $css : null;
    }
}

/**
 * A number as short as it can be written: 8, not 8.0; 8.5, not 8.50.
 *
 * @param mixed $value a number
 * @return string
 */
function formulize_appearanceTokenNumber($value) {
    $number = rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    return $number === '' ? '0' : $number;
}

/**
 * The value each size token is declared with in a stylesheet's :root rules:
 * its Default, when the stylesheet is formulize-ui.css or a theme's tokens.
 *
 * @param string $css the stylesheet
 * @return array token => its CSS value, for the size tokens the stylesheet declares on :root
 */
function formulize_appearanceTokenDeclarations($css) {
    $css = preg_replace('/\/\*.*?\*\//s', '', $css);
    $map = formulize_appearanceTokenMap();
    $declared = array();
    // only rules whose selector is exactly :root: a token set on a component
    // class is that component's own adjustment, not the default
    preg_match_all('/(?:^|(?<=}))\s*:root\s*\{([^}]*)\}/', $css, $rules);
    foreach ($rules[1] as $body) {
        preg_match_all('/(--fz-[a-z0-9-]+)\s*:\s*([^;]+);/', $body, $declarations, PREG_SET_ORDER);
        foreach ($declarations as $declaration) {
            if (isset($map['tokens'][$declaration[1]])) {
                $declared[$declaration[1]] = trim($declaration[2]);
            }
        }
    }
    return $declared;
}

/**
 * Check the map against formulize-ui.css: every token has a known group and
 * type, its own limits are values its type can have, every preset value is
 * inside the token's limits, and every token has a Default in formulize-ui.css,
 * written in the form its type is written in and inside its limits. Also the
 * advanced size editor's parts: each token's phone token, home part and floor
 * are well formed, every token a part names exists, and every token can be
 * reached from a part.
 *
 * @param array $map the map, from formulize_appearanceTokenMap()
 * @param string $css the contents of formulize-ui.css
 * @return array problems, as sentences; empty if there are none
 */
function formulize_appearanceCheckTokenMap($map, $css) {
    $problems = array();
    $declared = formulize_appearanceTokenDeclarations($css);
    foreach ($map['tokens'] as $token => $entry) {
        if (!isset($map['groups'][$entry['group']])) {
            $problems[] = "The size token $token is in a group, '" . $entry['group'] . "', that appearance_tokens.json doesn't list.";
        }
        if (!isset($map['types'][$entry['type']])) {
            $problems[] = "The size token $token is of a type, '" . $entry['type'] . "', that appearance_tokens.json doesn't list.";
            continue;
        }
        $type = $map['types'][$entry['type']];
        if (isset($entry['values'])) {
            foreach ($entry['values'] as $value) {
                if (!formulize_appearanceTokenValueIsValid($type, $value)) {
                    $problems[] = "The size token $token lists a value, " . json_encode($value) . ", that a " . $entry['type'] . " token can't have.";
                }
            }
        } elseif (!isset($entry['min']) OR !isset($entry['max'])) {
            $problems[] = "The size token $token has no limits: give it a min and a max, or a list of values.";
        } elseif (!formulize_appearanceTokenValueIsValid($type, $entry['min']) OR !formulize_appearanceTokenValueIsValid($type, $entry['max']) OR $entry['min'] > $entry['max']) {
            $problems[] = "The size token $token has limits, " . json_encode($entry['min']) . " to " . json_encode($entry['max']) . ", that aren't a range of " . $entry['type'] . " values.";
        }
        foreach ($entry['presets'] as $preset => $value) {
            if (!formulize_appearanceTokenAllows($map, $token, $value)) {
                $problems[] = "The size token $token has a $preset value, " . json_encode($value) . ", outside what it can be set to.";
            }
        }
        foreach (array('phone' => 'phone token', 'home' => 'home part') as $field => $noun) {
            if (!isset($entry[$field])) {
                continue;
            }
            $ok = ($field == 'phone')
                ? (isset($map['tokens'][$entry[$field]]) AND $map['tokens'][$entry[$field]]['type'] == $entry['type'])
                : (isset($map['components'][$entry[$field]]) AND in_array($token, $map['components'][$entry[$field]]['tokens']));
            if (!$ok) {
                $problems[] = "The size token $token has a $noun, '" . $entry[$field] . "', that isn't " . ($field == 'phone' ? "a size token of the same type" : "a part that lists it") . ".";
            }
        }
        if (isset($entry['floor'])) {
            $floor = $entry['floor'];
            $ref = isset($floor['text']) ? $floor['text'] : (isset($floor['token']) ? $floor['token'] : '');
            $refType = isset($map['tokens'][$ref]) ? $map['tokens'][$ref]['type'] : '';
            if ($entry['type'] != 'spacing' OR !(isset($floor['text']) ? ($refType == 'text' AND isset($floor['leading'], $floor['extra'])) : ($refType == 'spacing' AND isset($floor['plus'])))) {
                $problems[] = "The size token $token has a floor that isn't one of the two kinds: a text token with a leading and extra px, or a spacing token plus a number of steps.";
            }
        }
        if (!isset($declared[$token])) {
            $problems[] = "The size token $token has no Default: formulize-ui.css doesn't declare it on :root.";
        } else {
            $default = formulize_appearanceTokenParse($entry['type'], $declared[$token]);
            if ($default !== null AND formulize_appearanceTokenValueIsValid($type, $default) AND !formulize_appearanceTokenAllows($map, $token, $default)) {
                $problems[] = "The size token $token's Default in formulize-ui.css, '" . $declared[$token] . "', is outside what it can be set to.";
            } elseif ($default === null OR !formulize_appearanceTokenValueIsValid($type, $default)) {
                $problems[] = "The size token $token is declared in formulize-ui.css as '" . $declared[$token] . "', which isn't a " . $entry['type'] . " value written the way the Appearance page writes one (eg: " . formulize_appearanceTokenCss($entry['type'], isset($type['values']) ? $type['values'][0] : $type['min']) . ").";
            }
        }
    }
    // every preset, and the Defaults, keep to the floors: a value under its floor
    // would change nothing, since the thing it sizes is held taller by its contents
    $defaults = array();
    foreach ($map['tokens'] as $token => $entry) {
        $parsed = isset($declared[$token]) ? formulize_appearanceTokenParse($entry['type'], $declared[$token]) : null;
        if ($parsed !== null) {
            $defaults[$token] = $parsed;
        }
    }
    foreach (array('default' => 'Default', 'compact' => 'Compact', 'comfortable' => 'Comfortable') as $preset => $presetName) {
        $values = $defaults;
        foreach ($map['tokens'] as $token => $entry) {
            if ($preset != 'default' AND isset($entry['presets'][$preset])) {
                $values[$token] = $entry['presets'][$preset];
            }
        }
        foreach ($map['tokens'] as $token => $entry) {
            if (isset($entry['floor'], $values[$token])) {
                $floor = formulize_appearanceTokenFloor($entry['floor'], $values);
                if ($floor !== null AND $values[$token] < $floor) {
                    $problems[] = "At $presetName, the size token $token is " . formulize_appearanceTokenNumber($values[$token]) . ", under its floor of " . formulize_appearanceTokenNumber($floor) . " steps, so it would change nothing.";
                }
            }
        }
    }
    // the parts of the interface the advanced size editor selects: every token they
    // name exists, and every token is in a part, or is another token's phone token
    $inPart = array();
    foreach (isset($map['components']) ? $map['components'] : array() as $key => $component) {
        foreach (array('name', 'plural', 'tokens', 'gaps') as $field) {
            if (!isset($component[$field])) {
                $problems[] = "The part $key has no $field.";
            }
        }
        foreach (isset($component['tokens']) ? $component['tokens'] : array() as $token) {
            if (!isset($map['tokens'][$token])) {
                $problems[] = "The part $key names a size token, $token, that appearance_tokens.json doesn't have.";
            }
            $inPart[$token] = true;
        }
    }
    foreach ($map['tokens'] as $token => $entry) {
        if (isset($entry['phone'])) {
            $inPart[$entry['phone']] = true;
        }
    }
    foreach (array_keys($map['tokens']) as $token) {
        if (!isset($inPart[$token])) {
            $problems[] = "The size token $token isn't in any part, so the advanced size editor has no way to reach it.";
        }
    }
    if (empty($map['screens'])) {
        $problems[] = "appearance_tokens.json lists no sample screens.";
    }
    return $problems;
}

/**
 * Check a theme's sample screens for the advanced size editor: every part they
 * mark with data-fz-part is a part in the map, and every file is named after a
 * screen in the map, or starts with an underscore (a piece the samples include).
 *
 * @param array $map the map, from formulize_appearanceTokenMap()
 * @param string $dir the theme's appearance_preview folder
 * @param string $theme the theme's name, for the messages
 * @return array problems, as sentences; empty if there are none
 */
function formulize_appearanceCheckPreviewSamples($map, $dir, $theme) {
    $problems = array();
    foreach (glob($dir . '/*.html') as $file) {
        $name = basename($file, '.html');
        if ($name[0] != '_' AND !isset($map['screens'][$name])) {
            $problems[] = "The $theme theme has a sample screen, $name.html, that isn't one of the screens in appearance_tokens.json.";
        }
        preg_match_all('/data-fz-part="([^"]*)"/', file_get_contents($file), $parts);
        foreach (array_unique($parts[1]) as $part) {
            if (!isset($map['components'][$part])) {
                $problems[] = "The $theme theme's sample " . basename($file) . " marks a part, '$part', that isn't one of the parts in appearance_tokens.json.";
            }
        }
    }
    return $problems;
}
