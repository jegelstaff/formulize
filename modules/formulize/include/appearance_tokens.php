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
 * written in the form its type is written in and inside its limits.
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
    return $problems;
}
