<?php
/**
 * Standalone logic tests for the way an element's value carries the text typed into its Other box.
 *
 * These are NOT PHPUnit / Playwright - deliberately a single dependency-free script, following the same pattern as
 * tests/time_format_test.php, so it can run anywhere PHP is available:
 *   php tests/other_text_values_test.php
 *
 * WHAT THIS COVERS
 * Radio buttons and checkboxes can have an Other option, such as {OTHER|30}, where people type their own text. The data
 * table stores the Other option, and the text is kept in the formulize_other table. A value being written carries the
 * text after the Other option and a colon, {OTHER|30}:text, which writeEntry() separates out and saves. These are the
 * two functions everything uses for that format:
 *
 *   formulize_attachOtherText(), which puts the text into a value, after its Other option
 *   formulize_splitOtherText(), which takes it out again, and says whether the value has an Other option at all
 *
 * So this checks that they agree with each other for single values and lists of values, wherever the Other option is in
 * the list, for text with a colon or the separator in it, for empty text, and for values without an Other option.
 *
 * WHAT THIS DOES NOT COVER
 * Saving and reading the text, which needs the module bootstrap and a database. The Playwright spec
 * 03-validate-sequential/085-other-option-text.spec.js does that through the application.
 *
 * @package Formulize
 * @subpackage tests
 */

// Command-line / CI only. A web request just 404s - this ships in the repo and must never be a live endpoint.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$functionsFile = __DIR__ . '/../modules/formulize/include/functions.php';
if (!is_file($functionsFile)) {
    fwrite(STDERR, "Cannot find functions.php at: $functionsFile\n");
    exit(2);
}

/**
 * Lift a single function's source out of a PHP file using the tokenizer, so braces inside strings or comments cannot be
 * mistaken for its own. (Same approach as time_format_test.php - functions.php can't be included standalone, it pulls in
 * the module bootstrap.)
 */
function extractFunctionSource($source, $functionName) {
    $tokens = token_get_all($source);
    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
            continue;
        }
        $j = $i + 1;
        while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }
        if (!is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING || $tokens[$j][1] !== $functionName) {
            continue;
        }
        $collected = '';
        $depth = 0;
        $seenBody = false;
        for ($k = $i; $k < $count; $k++) {
            $text = is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
            $collected .= $text;
            $isArrayToken = is_array($tokens[$k]);
            if ($text === '{'
                || ($isArrayToken && ($tokens[$k][0] === T_CURLY_OPEN || $tokens[$k][0] === T_DOLLAR_OPEN_CURLY_BRACES))) {
                $depth++;
                $seenBody = true;
            } elseif ($text === '}') {
                $depth--;
                if ($seenBody && $depth === 0) {
                    return $collected;
                }
            }
        }
    }
    return false;
}

$functionsSource = file_get_contents($functionsFile);
foreach (array('formulize_splitOtherText', 'formulize_attachOtherText') as $name) {
    $code = extractFunctionSource($functionsSource, $name);
    if ($code === false) {
        fwrite(STDERR, "Could not extract $name()\n");
        exit(2);
    }
    eval($code);
}

$GLOBALS['__pass'] = 0;
$GLOBALS['__fail'] = 0;
function check($label, $got, $want) {
    $ok = ($got === $want);
    $GLOBALS[$ok ? '__pass' : '__fail']++;
    if (!$ok) {
        echo "FAIL: $label\n      got:  " . var_export($got, true) . "\n      want: " . var_export($want, true) . "\n";
    }
}

// a single value: radio buttons
check('radio, Other with text', formulize_attachOtherText('{OTHER|30}', 'Pending'), '{OTHER|30}:Pending');
check('radio, split it again', formulize_splitOtherText('{OTHER|30}:Pending'), array('{OTHER|30}', 'Pending', true));
check('radio, Other with no text given', formulize_splitOtherText('{OTHER|30}'), array('{OTHER|30}', null, true));
check('radio, Other with empty text', formulize_splitOtherText(formulize_attachOtherText('{OTHER|30}', '')), array('{OTHER|30}', '', true));
check('radio, an ordinary option', formulize_splitOtherText('Individual'), array('Individual', null, false));
check('radio, no text to attach leaves the value', formulize_attachOtherText('{OTHER|30}', null), '{OTHER|30}');
check('radio, text for a value without the Other option', formulize_attachOtherText('Individual', 'Pending'), 'Individual');
check('a box width of more than two digits', formulize_splitOtherText('{OTHER|120}:Long box'), array('{OTHER|120}', 'Long box', true));

// the text can contain what the format uses
check('text with a colon', formulize_splitOtherText(formulize_attachOtherText('{OTHER|30}', 'Note: later')), array('{OTHER|30}', 'Note: later', true));
check('text that looks like an Other option', formulize_splitOtherText(formulize_attachOtherText('{OTHER|30}', '{OTHER|5}')), array('{OTHER|30}', '{OTHER|5}', true));
check('text with a line break', formulize_splitOtherText(formulize_attachOtherText('{OTHER|30}', "one\ntwo")), array('{OTHER|30}', "one\ntwo", true));
check('the separator is taken out of the text', formulize_attachOtherText('{OTHER|30}', 'a*=+*:b'), '{OTHER|30}:ab');
check('text escaped for HTML is kept as it is', formulize_splitOtherText(formulize_attachOtherText('{OTHER|30}', 'O&#039;Brien &amp; Sons')), array('{OTHER|30}', 'O&#039;Brien &amp; Sons', true));

// lists of values: checkboxes, with the Other option first, in the middle, last, or the only one
$lists = array(
    'Other last' => '*=+*:Children*=+*:Adults*=+*:{OTHER|30}',
    'Other in the middle' => '*=+*:Children*=+*:{OTHER|30}*=+*:Adults',
    'Other first' => '*=+*:{OTHER|30}*=+*:Adults',
    'Other alone' => '*=+*:{OTHER|30}',
);
foreach ($lists as $label => $list) {
    $withText = formulize_attachOtherText($list, 'Seniors only');
    check("checkboxes, $label, text goes after the Other option", substr_count($withText, '{OTHER|30}:Seniors only'), 1);
    check("checkboxes, $label, split it again", formulize_splitOtherText($withText), array($list, 'Seniors only', true));
    check("checkboxes, $label, no text given", formulize_splitOtherText($list), array($list, null, true));
}
check('checkboxes without the Other option', formulize_splitOtherText('*=+*:Children*=+*:Adults'), array('*=+*:Children*=+*:Adults', null, false));
check('checkboxes, text for a list without the Other option', formulize_attachOtherText('*=+*:Children*=+*:Adults', 'Seniors'), '*=+*:Children*=+*:Adults');
check('an option that only mentions the Other option is not it', formulize_attachOtherText('*=+*:See {OTHER|30} below', 'x'), '*=+*:See {OTHER|30} below');

// values that aren't text
check('null', formulize_splitOtherText(null), array(null, null, false));
check('a number', formulize_splitOtherText(5), array(5, null, false));
check('attach to null', formulize_attachOtherText(null, 'x'), null);

echo $GLOBALS['__pass'] . " passed, " . $GLOBALS['__fail'] . " failed\n";
exit($GLOBALS['__fail'] ? 1 : 0);
