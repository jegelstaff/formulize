<?php
/**
 * Standalone logic tests for the T3.1' form-redisplay XSS fix (normalize-then-escape).
 *
 * These are NOT PHPUnit / Playwright - deliberately a single dependency-free script, following the same
 * pattern as tests/password_hashing_test.php and tests/reference_binding_test.php, so it can run anywhere
 * PHP is available:  php tests/normalize_then_escape_test.php
 *
 * WHAT THIS COVERS
 * The exact expression the two core form sinks emit for a plain-text value:
 *     icms_core_DataFilter::htmlSpecialChars(undoAllHTMLChars($value))
 * as written in libraries/icms/form/elements/Text.php::render() (the value='...' attribute) and
 * libraries/icms/form/elements/Textarea.php::render() (the textarea body). It runs the REAL code - the
 * actual icms_core_DataFilter class and the actual undoAllHTMLChars() lifted from functions.php - not a
 * reimplementation.
 *
 * The property under test is what makes the fix safe CMS-wide: because the value is fully decoded before
 * being escaped once, the result is IDEMPOTENT - identical whether the caller passed a raw value or one it
 * had already escaped (the ~140 core callers that pre-escape via getVar($key,'e') vs. Formulize elements
 * that pass raw). That is what stops the fix double-escaping half the admin, which was the trap in the
 * original T3.1. And of course the output must be inert: no quote or angle bracket survives that could
 * break out of the attribute / textarea it sits in.
 *
 * WHAT THIS DOES NOT COVER
 * The purification paths (formulize_purifyHtmlValue, makeValueSafeForReadOnlyDisplay, the {ref} composed
 * -string purification) - those depend on HTMLPurifier and the module bootstrap, and are exercised
 * end-to-end by the Playwright specs (034, 036) against a running instance instead.
 *
 * Usage:
 *   php tests/normalize_then_escape_test.php [path/to/DataFilter.php] [path/to/functions.php]
 * The optional arguments let you point at edited copies (handy when the running container is mounted from
 * a different checkout); they default to this repo's copies.
 *
 * @package Formulize
 * @subpackage tests
 */

// Command-line / CI only. A web request just 404s - this ships in the repo and must never be a live
// endpoint (it is harmless, but there is no reason to expose it).
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// icms_core_DataFilter::htmlSpecialChars() reads _CHARSET (with a utf-8 fallback). Define it up front so
// the real class behaves exactly as it does in the app.
if (!defined('_CHARSET')) {
    define('_CHARSET', 'UTF-8');
}

$dataFilterFile = isset($argv[1]) ? $argv[1] : (__DIR__ . '/../libraries/icms/core/DataFilter.php');
$functionsFile  = isset($argv[2]) ? $argv[2] : (__DIR__ . '/../modules/formulize/include/functions.php');
foreach (array('DataFilter.php' => $dataFilterFile, 'functions.php' => $functionsFile) as $label => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Cannot find $label at: $path\n");
        exit(2);
    }
}

// The real DataFilter class parses standalone (no `extends`, and its only require_once calls are inside
// methods this test never invokes), so load it directly and call the real static method.
require_once $dataFilterFile;

/**
 * Lift a single function's source out of a PHP file using the tokenizer, so braces inside strings or
 * comments cannot be mistaken for the function's own. (Same approach as reference_binding_test.php - the
 * whole functions.php cannot be included standalone, it pulls in the module bootstrap.)
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

$extracted = extractFunctionSource(file_get_contents($functionsFile), 'undoAllHTMLChars');
if ($extracted === false) {
    fwrite(STDERR, "Could not extract undoAllHTMLChars() from $functionsFile\n");
    exit(2);
}
eval($extracted);

// The exact pipeline both core sinks emit for a plain-text value.
function normalizeThenEscape($value) {
    return icms_core_DataFilter::htmlSpecialChars(undoAllHTMLChars($value));
}

// ---- tiny assertion harness -------------------------------------------------
$GLOBALS['__pass'] = 0;
$GLOBALS['__fail'] = 0;
function check($label, $got, $want) {
    $ok = ($got === $want);
    $GLOBALS[$ok ? '__pass' : '__fail']++;
    printf("  [%s] %-40s got=%s want=%s\n",
        $ok ? 'PASS' : 'FAIL', $label, var_export($got, true), var_export($want, true));
}
// A value is "inert" in an attribute / textarea sink if no raw quote or angle bracket survives to break
// out of it. (& alone cannot break out of either context, so it is not required to be encoded.)
function assertInert($label, $value) {
    $out = normalizeThenEscape($value);
    $hasRaw = (preg_match('/["\'<>]/', $out) === 1);
    $GLOBALS[$hasRaw ? '__fail' : '__pass']++;
    printf("  [%s] %-40s output=%s\n", $hasRaw ? 'FAIL' : 'PASS', $label, var_export($out, true));
}

echo "T3.1' normalize-then-escape - logic tests\n";
echo "pipeline: icms_core_DataFilter::htmlSpecialChars(undoAllHTMLChars(\$value))\n\n";

echo "IDEMPOTENCE - raw and pre-escaped inputs must reach the SAME output (no double-escaping):\n";
// The apostrophe is the attribute-breakout char; core callers pre-escape it to &#039; via getVar('e').
check('raw apostrophe',            normalizeThenEscape("O'Brien"),        'O&#039;Brien');
check('pre-escaped apostrophe',    normalizeThenEscape('O&#039;Brien'),   'O&#039;Brien');
check('double-escaped apostrophe', normalizeThenEscape('O&amp;#039;Brien'), 'O&#039;Brien'); // heals legacy double-escaping
// The full payload, raw vs. pre-escaped, must also converge.
$rawPayload = '\'"><script>alert(1)</script>';
$escPayload = '&#039;&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;';
check('raw payload == escaped payload',
    normalizeThenEscape($rawPayload), normalizeThenEscape($escPayload));

echo "\nSTABILITY - the pipeline is idempotent (running it twice changes nothing more):\n";
foreach (array("O'Brien", 'O&#039;Brien', $rawPayload, $escPayload, 'plain text', '50&copy; caf&eacute;') as $n => $in) {
    $once = normalizeThenEscape($in);
    check("stable #$n", normalizeThenEscape($once), $once);
}

echo "\nINERTNESS - no raw quote/bracket survives to break out of an attribute or textarea:\n";
assertInert('raw payload',           $rawPayload);
assertInert('pre-escaped payload',   $escPayload);
assertInert('textarea breakout',     '</textarea><script>alert(1)</script>');
assertInert('attribute breakout',    '" onmouseover="alert(1)');
assertInert('single-quote breakout', "' onmouseover='alert(1)");

echo "\nFIDELITY - plain content passes through unharmed:\n";
check('plain text unchanged', normalizeThenEscape('Just some words.'), 'Just some words.');
check('ampersand not broken', normalizeThenEscape('Tom & Jerry'),      'Tom & Jerry'); // htmlSpecialChars reverts &amp; -> &, harmless for XSS

echo "\n";
printf("RESULT: %d passed, %d failed\n", $GLOBALS['__pass'], $GLOBALS['__fail']);
exit($GLOBALS['__fail'] === 0 ? 0 : 1);
