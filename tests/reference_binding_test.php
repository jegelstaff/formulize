<?php
/**
 * Standalone logic tests for the {element_handle} reference binding used when admin-authored PHP
 * (textbox default values, static content elements, help text) is eval'd.
 *
 * These are NOT PHPUnit / Playwright — deliberately a single dependency-free script so it can be
 * run anywhere PHP is available, including inside the container:
 *   php tests/reference_binding_test.php
 *
 * WHAT IS BEING PROTECTED
 * Admin-authored PHP may reference entry data as {some_handle}. That data can be entered by a
 * non-admin user, so it must never become part of the code that runs. Rather than escaping the
 * value and splicing it in (which can only ever protect a reference the admin put inside quotes -
 * a reference in a bare context such as `$default = {a} + {b};` was still exploitable), the
 * reference is rewritten to a variable and the value is supplied at runtime as data. This is the
 * same approach derived value formulas use.
 *
 * The rewrite has to be aware of PHP's lexical context, because what a reference must become
 * depends on where it sits, and a plain string search cannot tell a string delimiter from a quote
 * character that is merely part of another string's contents:
 *   bare code        {a}    ->  $__formulizeRef_a            (direct substitution)
 *   double quoted  "x {a}"  ->  "x {$__formulizeRef_a}"      (curly interpolation - the braces are
 *                                                             required so adjacent word characters,
 *                                                             as in "{a}kg" or "{a}_{b}", cannot
 *                                                             merge into the variable name)
 *   single quoted  'x {a}'  ->  ('x ' . $__formulizeRef_a)   (concatenated - single quotes do not
 *                                                             interpolate, and converting them to
 *                                                             double quotes would change how $ and
 *                                                             \ behave inside the literal)
 * Heredoc bodies use the same curly interpolation. References in contexts where no variable could
 * ever resolve - comments, inline HTML, nowdoc bodies - are left untouched.
 *
 * WHAT THIS COVERS
 * The pure transform: formulize_bindCurlyReferencesInPHPCode() and its helper
 * formulize_rewriteSingleQuotedStringWithRefs(), both in modules/formulize/include/functions.php.
 * Each case transforms a snippet, binds the values the way the renderer does at runtime, eval's the
 * result, and asserts on the value produced - so an injection attempt failing here means the
 * payload was genuinely inert, not merely that the output looked different.
 *
 * WHAT THIS DOES NOT COVER
 * Anything requiring the database or the XOOPS bootstrap: resolving a handle to its value
 * (formulizeElementRenderer::resolveReferenceValue), the entry context and error handling in
 * formulizeElementRenderer::evalAdminPHPWithReferences, and the three call sites that use it.
 * Those still need a real page render against a running instance.
 *
 * The functions under test are lifted out of functions.php rather than included, because that file
 * pulls in the whole module bootstrap and cannot be loaded standalone. The extraction is
 * token-based so that braces inside strings and comments cannot throw off the function boundaries.
 *
 * Usage:
 *   php tests/reference_binding_test.php [path/to/functions.php]
 *
 * @package Formulize
 * @subpackage tests
 */

// Command-line / CI only. This ships in the repo, and it eval's code by design, so it must never
// be reachable over the web. A web request just 404s.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$functionsFile = isset($argv[1]) ? $argv[1] : (__DIR__ . '/../modules/formulize/include/functions.php');
if (!is_file($functionsFile)) {
    fwrite(STDERR, "Cannot find functions.php at: $functionsFile\n");
    exit(2);
}

/**
 * Lift a single function's source out of a PHP file, using the tokenizer so that braces appearing
 * inside string literals or comments cannot be mistaken for the function's own braces.
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
            // T_CURLY_OPEN / T_DOLLAR_OPEN_CURLY_BRACES are the interpolation forms of {, and are
            // closed by an ordinary } token, so counting them keeps the depth balanced
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

$source = file_get_contents($functionsFile);
foreach (array('formulize_rewriteSingleQuotedStringWithRefs', 'formulize_bindCurlyReferencesInPHPCode') as $needed) {
    $extracted = extractFunctionSource($source, $needed);
    if ($extracted === false) {
        fwrite(STDERR, "Could not extract $needed() from $functionsFile\n");
        exit(2);
    }
    eval($extracted);
}

// ---- tiny assertion harness -------------------------------------------------
$GLOBALS['__pass'] = 0;
$GLOBALS['__fail'] = 0;
function check($label, $got, $want) {
    $ok = ($got === $want);
    $GLOBALS[$ok ? '__pass' : '__fail']++;
    printf("  [%s] %-46s got=%s want=%s\n",
        $ok ? 'PASS' : 'FAIL',
        $label,
        var_export($got, true),
        var_export($want, true));
}

/**
 * Transform a snippet the way the renderer does, bind the referenced values, run it, and return
 * whatever the code assigned to $default. Mirrors formulizeElementRenderer::bindReferencesForPHPEval
 * and evalAdminPHPWithReferences, minus everything that needs the database.
 */
function runSnippet($code, $elementValues) {
    $isKnownHandle = function ($handle) use ($elementValues) {
        return array_key_exists($handle, $elementValues);
    };
    list($transformed, $bindings) = formulize_bindCurlyReferencesInPHPCode($code, $isKnownHandle);
    $__formulizeRefValues = array();
    $preamble = '';
    foreach ($bindings as $handle => $variableName) {
        $__formulizeRefValues[$variableName] = $elementValues[$handle];
        $preamble .= '$' . $variableName . ' = $__formulizeRefValues[\'' . $variableName . '\'];' . "\n";
    }
    $default = null;
    try {
        eval($preamble . $transformed);
    } catch (\Throwable $e) {
        return 'THREW(' . get_class($e) . ': ' . $e->getMessage() . ')';
    }
    return $default;
}

echo "{element_handle} reference binding - logic tests\n";
echo "source: $functionsFile\n\n";

echo "lexical context handling\n";
check('bare arithmetic',            runSnippet('$default = {a} + {b};', array('a' => 2, 'b' => 3)), 5);
check('double quoted',              runSnippet('$default = "Hello {a}";', array('a' => 'World')), 'Hello World');
check('single quoted',              runSnippet('$default = \'Hello {a}\';', array('a' => 'World')), 'Hello World');
check('no references at all',       runSnippet('$default = 1 + 1;', array()), 2);
check('unknown handle left alone',  runSnippet('$default = "x {nope} y";', array('a' => 1)), 'x {nope} y');
check('same reference twice',       runSnippet('$default = "{a}-{a}";', array('a' => 'Z')), 'Z-Z');
check('two references one string',  runSnippet('$default = "{a} {b}";', array('a' => 'x', 'b' => 'y')), 'x y');
check('reference is whole literal', runSnippet('$default = \'{a}\';', array('a' => 'solo')), 'solo');
check('reference starts literal',   runSnippet('$default = \'{a} tail\';', array('a' => 'H')), 'H tail');

echo "\ndelimiter safety - adjacent characters must not merge into the variable name\n";
// a bare $__formulizeRef_a spliced into a double-quoted string would absorb any word characters
// that follow it into the variable name ("{a}kg" -> "$__formulizeRef_akg", an undefined variable),
// which is why references in interpolating strings become {$__formulizeRef_a} instead
check('ref followed by letters in dq',
    runSnippet('$default = "{a}kg";', array('a' => '5')), '5kg');
check('refs joined by underscore in dq',
    runSnippet('$default = "{a}_{b}";', array('a' => 'x', 'b' => 'y')), 'x_y');
check('ref followed by digits in dq',
    runSnippet('$default = "{a}99";', array('a' => 'v')), 'v99');
check('ref beside interpolated variable',
    runSnippet('$x = "yo"; $default = "$x{a}";', array('a' => 'z')), 'yoz');
check('ref followed by letters in heredoc',
    runSnippet("\$default = <<<EOT\nx {a}kg\nEOT;", array('a' => '5')), 'x 5kg');

echo "\nother string-like contexts\n";
// nowdocs cannot interpolate anything, so a reference in one is left untouched rather than being
// rewritten into variable text that could never resolve
check('nowdoc body left untouched',
    runSnippet("\$default = <<<'EOT'\n{a}\nEOT;", array('a' => 'v')), '{a}');
// b/B binary-prefixed strings behave exactly like their unprefixed forms
check('binary single quoted string',
    runSnippet('$default = b\'x {a}\';', array('a' => 'v')), 'x v');
check('binary double quoted string',
    runSnippet('$default = b"{a}kg";', array('a' => '5')), '5kg');

echo "\nwhy single quotes are concatenated rather than swapped for double quotes\n";
// literal single quotes inside a double-quoted string: a naive '{a}' -> "{a}" rewrite would inject
// unbalanced double quotes here and corrupt a case that is otherwise fine
check('literal single quotes in dq string',
    runSnippet('$default = "Order for \'{a}\' ready";', array('a' => 'John')), "Order for 'John' ready");
// swapping the quotes would start interpolating $total, which is literal text in a single-quoted string
check('$ stays literal in sq string',
    runSnippet('$default = \'Cost $total now {a}\';', array('a' => '5')), 'Cost $total now 5');
check('backslash-n stays literal in sq string',
    runSnippet('$default = \'x\\ny {a}\';', array('a' => 'z')), 'x\\ny z');
check('backslash before reference in sq',
    runSnippet('$default = \'a\\{a}\';', array('a' => 'B')), 'a\\B');
check('escaped quote in sq string',
    runSnippet('$default = \'it\\\'s {a}\';', array('a' => 'here')), "it's here");

echo "\ninjection attempts (values are attacker controlled)\n";
check('quote breakout in dq string',
    runSnippet('$default = "{a}";', array('a' => '"; system("calc"); $x="')), '"; system("calc"); $x="');
check('quote breakout in sq string',
    runSnippet('$default = \'{a}\';', array('a' => "'; system('calc'); \$x='")), "'; system('calc'); \$x='");
// the case escaping alone could never protect: no quotes or $ involved, so it depends entirely on
// the value never becoming part of the code
check('code injection in bare context',
    runSnippet('$default = {a};', array('a' => 'phpinfo()')), 'phpinfo()');
check('concatenation breakout in sq string',
    runSnippet('$default = \'x{a}y\';', array('a' => "' . phpinfo() . '")), "x' . phpinfo() . 'y");
check('variable interpolation attempt',
    runSnippet('$default = "{a}";', array('a' => '$secret')), '$secret');
check('complex interpolation attempt',
    runSnippet('$default = "{a}";', array('a' => '{$secret}')), '{$secret}');
check('backslash payload',
    runSnippet('$default = "{a}";', array('a' => 'back\\slash')), 'back\\slash');
check('value that looks like a reference',
    runSnippet('$default = "{a}";', array('a' => '{b}')), '{b}');

echo "\nvalues that are not plain strings\n";
// a multi-value element arrives as a real array, not a flattened string - code can work with it
check('array value stays an array', runSnippet('$default = {a};', array('a' => array('p', 'q'))), array('p', 'q'));
check('null value',                 runSnippet('$default = {a};', array('a' => null)), null);
check('int keeps its type',         runSnippet('$default = {a};', array('a' => 7)), 7);

echo "\ngenerated variable names\n";
$known = function ($handle) { return in_array($handle, array('price', 'qty', 'a-b', 'a_b')); };
list($code, $bindings) = formulize_bindCurlyReferencesInPHPCode('$default = {price} * {qty};', $known);
// element handles are globally unique and already sanitized to [a-z0-9_] when created, so the
// handle alone names the variable
check('handle names the variable', $bindings['price'], '__formulizeRef_price');
check('no leftover references',    (strpos($code, '{') === false), true);
// legacy or imported handles that predate that rule could sanitize to the same identifier
list($code2, $bindings2) = formulize_bindCurlyReferencesInPHPCode('$default = "{a-b}/{a_b}";', $known);
check('colliding sanitized handles stay distinct',
    (count(array_unique($bindings2)) === count($bindings2)), true);

echo "\nnon-executable contexts\n";
// a reference in a comment can never resolve to a variable, so it is not rewritten - and it must
// not be bound either, because binding would resolve and catalogue an element that the code never
// actually uses
list($code3, $bindings3) = formulize_bindCurlyReferencesInPHPCode("// mentions {price}\n\$default = 1;", $known);
check('ref in comment left untouched', (strpos($code3, '{price}') !== false), true);
check('ref in comment not bound',      $bindings3, array());
check('code after comment still runs', runSnippet("// mentions {a}\n\$default = 2;", array('a' => 'v')), 2);

echo "\n";
printf("%d passed, %d failed\n", $GLOBALS['__pass'], $GLOBALS['__fail']);
exit($GLOBALS['__fail'] === 0 ? 0 : 1);
