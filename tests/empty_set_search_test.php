<?php
/**
 * Standalone logic tests for EMPTYSET searches, and for the search-term prefix parsing they share with OR/ORSET.
 *
 * These are NOT PHPUnit / Playwright - deliberately a single dependency-free script, following the same pattern as
 * tests/password_hashing_test.php and tests/normalize_then_escape_test.php, so it can run anywhere PHP is available:
 *   php tests/empty_set_search_test.php
 *
 * WHAT THIS COVERS
 * An EMPTYSET term asks "no connected entry matches these conditions", which the extraction layer renders as a
 * NOT EXISTS instead of a row level comparison. Getting a term from a search box or a screen's conditions into that
 * shape is pure string work, done by four functions this exercises directly, using the REAL source lifted out of the
 * shipped files rather than a reimplementation:
 *
 *   formulize_extractSearchGroupPrefix()    which group a term belongs to, and what that group means
 *   formulize_unwrapSearchBoxValue()        the value inside whatever wrapper a search box was holding
 *   splitUpSearchStringIntoSearchTerms()    how "X AND Y" in one box becomes separate terms
 *   formulize_extractEmptySetConditions()   turning a screen's EMPTYSET conditions into filter expressions
 *
 * Two properties matter most here, and both are the reason for the case lists below rather than a happy path check.
 *
 * BACKWARD COMPATIBILITY. The prefix parser took over parsing that ORSET and OR had been doing for years, including
 * a no-delimiter ORSET syntax that consumes exactly one character as the group key. Existing saved views hold those
 * strings, so the legacy forms have to keep resolving identically. The ORANGE case is deliberate: it documents a
 * long standing quirk (a search for "ORANGE" is read as an OR search for "ANGE") that we preserve rather than fix,
 * because fixing it would change what existing saved views return.
 *
 * FAILING CLOSED. Every uncertain case here has to drop the term rather than search for something approximate.
 * An EMPTYSET search that quietly matches the wrong thing removes entries from a list, and nobody notices a row
 * that isn't there. So an unresolved reference, a compound term, or a wrapped {BLANK} must all produce no filter
 * expression at all - and, just as importantly, must not leave the raw text behind in the screen's conditions where
 * it would become a literal search matching nothing.
 *
 * WHAT THIS DOES NOT COVER
 * The SQL itself. Whether a term becomes a NOT EXISTS, and whether a numeric term becomes a foreign key comparison
 * against a linked element's alternate columns, is decided inside formulize_parseFilter() and getData(), which need
 * the module bootstrap and a database. Those paths are exercised against a running instance by the e2e suite.
 *
 * @package Formulize
 * @subpackage tests
 */

// Command-line / CI only. A web request just 404s - this ships in the repo and must never be a live endpoint.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$functionsFile = isset($argv[1]) ? $argv[1] : (__DIR__ . '/../modules/formulize/include/functions.php');
$extractFile   = isset($argv[2]) ? $argv[2] : (__DIR__ . '/../modules/formulize/include/extract.php');
foreach (array('functions.php' => $functionsFile, 'extract.php' => $extractFile) as $label => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Cannot find $label at: $path\n");
        exit(2);
    }
}

/**
 * Lift a single function's source out of a PHP file using the tokenizer, so braces inside strings, regexes or
 * comments cannot be mistaken for the function's own. (Same approach as normalize_then_escape_test.php - neither
 * functions.php nor extract.php can be included standalone, they pull in the module bootstrap.)
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
$extractSource   = file_get_contents($extractFile);
$toLoad = array(
    'extractOperatorFromString'            => $functionsSource,
    'convertStringToUseSpecialCharsToMatchDB' => $functionsSource,
    'convertDynamicFilterTerms'            => $functionsSource,
    'formulize_unwrapSearchBoxValue'       => $functionsSource,
    'formulize_extractSearchGroupPrefix'   => $functionsSource,
    'splitUpSearchStringIntoSearchTerms'   => $functionsSource,
    'formulize_extractEmptySetConditions'  => $extractSource,
);
foreach ($toLoad as $name => $source) {
    $code = extractFunctionSource($source, $name);
    if ($code === false) {
        fwrite(STDERR, "Could not extract $name()\n");
        exit(2);
    }
    eval($code);
}

// splitUpSearchStringIntoSearchTerms() looks up the element only to fetch its ele_uitext, which it then swaps into
// the terms. Standing in for that lookup with "no element found" reproduces exactly what happens for an element with
// no ele_uitext configured, which is the ordinary case, and leaves the splitting logic under test untouched.
function _getElementObject($elementIdentifier) { return false; }
function formulize_swapDBText($value, $ele_uitext) { return $value; }

// ---- tiny assertion harness -------------------------------------------------
$GLOBALS['__pass'] = 0;
$GLOBALS['__fail'] = 0;
function check($label, $got, $want) {
    $ok = ($got === $want);
    $GLOBALS[$ok ? '__pass' : '__fail']++;
    printf("  [%s] %-46s %s\n", $ok ? 'PASS' : 'FAIL', $label,
        $ok ? var_export($got, true) : 'got=' . var_export($got, true) . ' want=' . var_export($want, true));
}
// Collapse a prefix parse into one comparable string: group/key/term.
function prefixOf($input) {
    $p = formulize_extractSearchGroupPrefix($input);
    return $p['group'] . '/' . $p['key'] . '/' . $p['term'];
}

echo "EMPTYSET searches - logic tests\n\n";

echo "PREFIX PARSING - which group a term belongs to:\n";
check('EMPTYSET, no key',           prefixOf('EMPTYSET:{search_student_terms}'), 'emptyset//{search_student_terms}');
check('EMPTYSET, numeric key',      prefixOf('EMPTYSET1:termX'),                 'emptyset/1/termX');
check('EMPTYSET, multi-digit key',  prefixOf('EMPTYSET12:termX'),                'emptyset/12/termX');
check('EMPTYSET, empty term',       prefixOf('EMPTYSET:'),                       'emptyset//');
check('EMPTYSET, operator survives',prefixOf('EMPTYSET:=61'),                    'emptyset//=61');
// No colon means no key. Unlike the legacy ORSET syntax below, EMPTYSET never eats a character off the front of
// the term, because doing so silently changes what the user typed.
check('EMPTYSET, no colon, no key', prefixOf('EMPTYSETtermX'),                   'emptyset//termX');

echo "\nPREFIX PARSING - legacy ORSET and OR forms must resolve exactly as before:\n";
check('ORSET legacy, numeric key',  prefixOf('ORSET1x'),                         'orset/1/x');
check('ORSET legacy, letter key',   prefixOf('ORSETax'),                         'orset/a/x');
check('ORSET legacy, longer term',  prefixOf('ORSET1termX'),                     'orset/1/termX');
check('ORSET colon form',           prefixOf('ORSET1:termX'),                    'orset/1/termX');
check('ORSET colon, no key',        prefixOf('ORSET:termX'),                     'orset//termX');
check('ORSET colon, multi-digit',   prefixOf('ORSET12:termX'),                   'orset/12/termX');
check('OR legacy',                  prefixOf('ORterm'),                          'or//term');
check('OR colon form',              prefixOf('OR:term'),                         'or//term');
check('bare OR is too short',       prefixOf('OR'),                              '//OR');

echo "\nPREFIX PARSING - a term written entirely in capitals is a search, not an OR prefix:\n";
// ORANGE used to be read as an OR search for ANGE. An OR search is written against the value being looked for, so
// the give-away is that there is no lowercase anywhere in it. Anything with lowercase in it is still an OR search.
check('all capitals is a search',   prefixOf('ORANGE'),                          '//ORANGE');
check('another all-capitals word',  prefixOf('ORDERS'),                          '//ORDERS');
check('colon overrides that',       prefixOf('OR:ORANGE'),                       'or//ORANGE');
check('capitalised value still OR', prefixOf('ORSmith'),                         'or//Smith');
check('lowercase value still OR',   prefixOf('ORorange'),                        'or//orange');
// Digits are neither case, so a term with no letters keeps its previous meaning rather than changing under this rule.
check('digits are unaffected',      prefixOf('OR123'),                           'or//123');

echo "\nPREFIX PARSING - terms with no prefix are left alone:\n";
check('plain word',                 prefixOf('apple'),                           '//apple');
check('operator and number',        prefixOf('=61'),                             '//=61');
check('bare reference',             prefixOf('{search_x}'),                      '//{search_x}');

echo "\nSEARCH BOX VALUES - the value inside whatever wrapper the box was holding:\n";
check('quickfilter wrapper',        formulize_unwrapSearchBoxValue('qsf_5_61'),            '61');
// Take everything after the counter rather than exploding on underscores, so a value containing one survives.
check('underscores in the value',   formulize_unwrapSearchBoxValue('qsf_7_JULY_BLOCK_26'), 'JULY_BLOCK_26');
check('spaces in the value',        formulize_unwrapSearchBoxValue('qsf_0_July Block'),    'July Block');
check('not wrapped at all',         formulize_unwrapSearchBoxValue('July Block'),          'July Block');
check('operator, not wrapped',      formulize_unwrapSearchBoxValue('=61'),                 '=61');

echo "\nINLINE AND/OR - a box holding several terms splits before the prefixes are read:\n";
// The splitter prepends OR to each piece it makes, so a piece that already carried a prefix arrives as
// OREMPTYSET:red. Reading that as an OR search for the literal text "EMPTYSET:red" would match nothing, silently.
$pieces = function($input) {
    $out = array();
    foreach (splitUpSearchStringIntoSearchTerms($input, 'somehandle') as $term) {
        $out[] = prefixOf($term);
    }
    return implode(' + ', $out);
};
check('AND keeps both empty sets',  $pieces('EMPTYSET:{search_term} AND EMPTYSET:Apples'),
    'emptyset//{search_term} + emptyset//Apples');
check('prefix is not implied',      $pieces('EMPTYSET:{search_term} AND Apples'),
    'emptyset//{search_term} + //Apples');
check('keys survive the split',     $pieces('EMPTYSET1:a AND EMPTYSET2:b'),
    'emptyset/1/a + emptyset/2/b');
check('OR does not swallow prefix', $pieces('EMPTYSET:red OR EMPTYSET:blue'),
    'emptyset//red + emptyset//blue');
check('OR does not swallow ORSET',  $pieces('ORSET1:a OR ORSET1:b'),
    'orset/1/a + orset/1/b');
// Writing AND and OR between terms in one box is not supported - there is no way to know which part was meant to group
// together - so the splitter leaves the string whole and it becomes a plain text search. Documented in the cheat sheet,
// and pinned here because the failure is silent: it matches nothing rather than reporting a problem.
check('AND and OR together is text', $pieces('red OR blue AND green'),
    '//red OR blue AND green');
// The supported way to say the same thing: join with AND, and mark the terms that only need one match with a prefix.
// These produce (santa) AND (cruz OR claus), and (red OR blue) AND (small OR large), once the buckets are assembled.
check('OR: makes a group inside AND', $pieces('santa AND OR:cruz AND OR:claus'),
    '//santa + or//cruz + or//claus');
check('numbered groups inside AND',   $pieces('ORSET1:red AND ORSET1:blue AND ORSET2:small AND ORSET2:large'),
    'orset/1/red + orset/1/blue + orset/2/small + orset/2/large');

echo "\nSCREEN CONDITIONS - EMPTYSET rows become filter expressions, and leave the conditions:\n";
// A screen's conditions are four parallel arrays: element ids, operators, terms, types.
$_POST['search_student_terms'] = 'qsf_5_61';   // what a term dropdown posts
$_POST['search_status']        = '=active';    // a typed value carrying its own operator
$_POST['search_blanky']        = 'qsf_0_{BLANK}';

// Collapse a translation into comparable strings: what is left in the conditions, and what expressions came out.
function translate($conditions, $filter = array()) {
    list($outConditions, $outFilter) = formulize_extractEmptySetConditions($conditions, $filter);
    $expressions = array();
    foreach ($outFilter as $expr) {
        $expressions[] = $expr[0] . '|' . $expr[1] . '|' . (isset($expr[2]) ? $expr[2] : '-');
    }
    return array(
        'conditions'  => empty($outConditions) ? '(empty)' : json_encode($outConditions),
        'expressions' => implode('  ~  ', $expressions),
    );
}

$got = translate(array(
    0 => array(88, 42),
    1 => array('=', '='),
    2 => array('EMPTYSET:{search_student_terms}', 'active'),
    3 => array('all', 'all'),
), array(0 => array(0 => 'and', 1 => 'student_last_name/**/Smith/**/LIKE')));
check('the ordinary condition stays put', $got['conditions'], '[[42],["="],["active"],["all"]]');
check('the empty set becomes a term',     $got['expressions'],
    'and|student_last_name/**/Smith/**/LIKE|-  ~  and|88/**/61/**/=/**/student_terms|none');

$got = translate(array(0 => array(88), 1 => array('='), 2 => array('EMPTYSET:{search_student_terms}'), 3 => array('all')));
check('nothing left behind',              $got['conditions'],  '(empty)');
check('provenance is recorded',           $got['expressions'], 'and|88/**/61/**/=/**/student_terms|none');

// Several conditions in one empty set describe one connected entry, so they are AND'd into a single expression.
// A different key is a different question, and becomes its own expression.
$got = translate(array(
    0 => array(88, 89, 90),
    1 => array('=', '=', '!='),
    2 => array('EMPTYSET1:{search_student_terms}', 'EMPTYSET1:{search_status}', 'EMPTYSET2:{search_student_terms}'),
    3 => array('all', 'all', 'all'),
));
check('same key groups, different key splits', $got['expressions'],
    'and|88/**/61/**/=/**/student_terms][89/**/active/**/=/**/status|none  ~  and|90/**/61/**/!=/**/student_terms|none');

$got = translate(array(0 => array(88), 1 => array('='), 2 => array('EMPTYSET:Apples'), 3 => array('all')));
check('a plain literal needs no reference', $got['expressions'], 'and|88/**/Apples/**/=|none');

$got = translate(array(0 => array(88), 1 => array('NOT'), 2 => array('EMPTYSET:{search_student_terms}'), 3 => array('all')));
check('NOT maps to !=',                     $got['expressions'], 'and|88/**/61/**/!=/**/student_terms|none');

echo "\nSCREEN CONDITIONS - anything uncertain is dropped, and never left behind as literal text:\n";
// Each of these must produce no expression AND remove the row. Left in the conditions, the raw text would reach
// buildConditionsFilterSQL as a search value and match nothing, blanking the screen with no indication why.
foreach (array(
    'unresolved reference' => 'EMPTYSET:{search_nonexistent}',
    'compound term'        => 'EMPTYSET:{search_student_terms} AND {search_other} AND Apples',
    'wrapped {BLANK}'      => 'EMPTYSET:{search_blanky}',
) as $label => $term) {
    $got = translate(array(0 => array(88), 1 => array('='), 2 => array($term), 3 => array('all')));
    check("$label - no expression", $got['expressions'], '');
    check("$label - row removed",   $got['conditions'],  '(empty)');
}

echo "\nSCREEN CONDITIONS - conditions with no empty sets are handed back untouched:\n";
$got = translate(array(0 => array(42), 1 => array('='), 2 => array('active'), 3 => array('all')),
    array(0 => array(0 => 'and', 1 => 'x/**/y/**/=')));
check('conditions unchanged',  $got['conditions'],  '[[42],["="],["active"],["all"]]');
check('filter unchanged',      $got['expressions'], 'and|x/**/y/**/=|-');

echo "\n";
printf("RESULT: %d passed, %d failed\n", $GLOBALS['__pass'], $GLOBALS['__fail']);
exit($GLOBALS['__fail'] === 0 ? 0 : 1);
