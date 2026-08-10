<?php
/**
 * Standalone logic tests for partial date searches - a search that names a whole month or year instead of one day.
 *
 * These are NOT PHPUnit / Playwright - deliberately a single dependency-free script, following the same pattern as
 * tests/password_hashing_test.php and tests/normalize_then_escape_test.php, so it can run anywhere PHP is available:
 *   php tests/date_search_test.php
 *
 * WHAT THIS COVERS
 * Date searches are run through strtotime, which always produces one particular day. That quietly does the wrong thing
 * for a term that describes a period: "2026-10" becomes the 1st, and "October" becomes today's date in October, so
 * ">=October" meant "on or after October 9th" rather than "on or after October 1st". Two pieces fix that, and this
 * exercises both, using the REAL source lifted out of the shipped files rather than a reimplementation:
 *
 *   formulize_parseDatePeriodSearchTerm()   is this term a whole month or year, and which days does it span
 *   formulizeDateElementHandler::buildSearchWhereClause()   the range comparison that replaces the single date
 *
 * Three properties matter, and each is why a whole section exists below rather than a couple of happy path checks.
 *
 * PRECISION, NOT PARSING. date_parse() cannot be used on its own to decide this, because "2026-10" reports day=1 rather
 * than day=false, making it indistinguishable from "2026-10-01". So the shapes that mean a period are matched
 * explicitly, and everything else has to fall through untouched - a single date, a relative phrase like "last Thursday",
 * a day of the week, or a word that is not a date at all. A term wrongly treated as a period would widen a search
 * silently, so the fall-through cases are as important as the matches.
 *
 * LANGUAGE. Month names come from three places, and the tests cover each: English always, because people mix languages
 * when typing; the language the page is being read in, which matters because a ?lang= override changes that without
 * reloading the site's language files; and the _CAL_ constants of whatever language files are loaded, so a site running
 * in a language Formulize does not ship a table for still works. Names are compared with accents folded and entities
 * decoded, since the language files store them encoded (F&eacute;vrier) and nobody types accents consistently.
 *
 * OPERATORS. Naming a period changes what the comparison operators should mean: after October is after all of it, before
 * October is before any of it. That matrix is checked in full, because getting one of them backwards would return
 * results that look plausible.
 *
 * WHAT THIS DOES NOT COVER
 * Whether the clause is reached at all. That depends on formulize_tryDelegatedSearchWhere() finding the handler and on
 * the surrounding filter parsing, which need the module bootstrap and a database, and are exercised against a running
 * instance by the e2e suite. Note the handler deliberately returns false for anything that is not a period, so every
 * ordinary date search still takes the path it always did.
 *
 * @package Formulize
 * @subpackage tests
 */

// Command-line / CI only. A web request just 404s - this ships in the repo and must never be a live endpoint.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

date_default_timezone_set('UTC');

$functionsFile = isset($argv[1]) ? $argv[1] : (__DIR__ . '/../modules/formulize/include/functions.php');
$dateFile      = isset($argv[2]) ? $argv[2] : (__DIR__ . '/../modules/formulize/class/dateElement.php');
foreach (array('functions.php' => $functionsFile, 'dateElement.php' => $dateFile) as $label => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Cannot find $label at: $path\n");
        exit(2);
    }
}

/**
 * Lift a single function or method's source out of a PHP file using the tokenizer, so braces inside strings or comments
 * cannot be mistaken for its own. (Same approach as normalize_then_escape_test.php - neither file can be included
 * standalone, they pull in the module bootstrap.)
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
foreach (array('formulize_normalizeMonthNameForSearch', 'formulize_monthNamesForSearch',
               'formulize_datePeriodForMonth', 'formulize_parseDatePeriodSearchTerm') as $name) {
    $code = extractFunctionSource($functionsSource, $name);
    if ($code === false) {
        fwrite(STDERR, "Could not extract $name()\n");
        exit(2);
    }
    eval($code);
}

// The real buildSearchWhereClause, wrapped in a bare class so it can be called without the module bootstrap. Only the
// one property it reads is declared, so if the method ever starts depending on more of the handler this stops compiling
// rather than quietly testing something different.
$clauseSource = extractFunctionSource(file_get_contents($dateFile), 'buildSearchWhereClause');
if ($clauseSource === false) {
    fwrite(STDERR, "Could not extract buildSearchWhereClause() from $dateFile\n");
    exit(2);
}
eval('class DateHandlerUnderTest { public $searchPeriod = false; ' . $clauseSource . ' }');

// Dates contain nothing that needs escaping, so this stands in faithfully for the real one.
function formulize_db_escape($value) { return addslashes($value); }

// ---- tiny assertion harness -------------------------------------------------
$GLOBALS['__pass'] = 0;
$GLOBALS['__fail'] = 0;
function check($label, $got, $want) {
    $ok = ($got === $want);
    $GLOBALS[$ok ? '__pass' : '__fail']++;
    printf("  [%s] %-42s %s\n", $ok ? 'PASS' : 'FAIL', $label,
        $ok ? var_export($got, true) : 'got=' . var_export($got, true) . ' want=' . var_export($want, true));
}
// Collapse a period into one comparable string, or say it is not a period.
function periodOf($value) {
    $period = formulize_parseDatePeriodSearchTerm($value);
    return $period ? $period['start'] . '..' . $period['end'] : 'not a period';
}
$thisYear = date('Y');

echo "Partial date searches - logic tests\n";
echo "(today is " . date('Y-m-d') . ", so terms with no year resolve into $thisYear)\n\n";

echo "PERIODS - numeric forms:\n";
check('a whole year',            periodOf('2026'),       '2026-01-01..2026-12-31');
check('a year and month',        periodOf('2026-10'),    '2026-10-01..2026-10-31');
check('single digit month',      periodOf('2026-1'),     '2026-01-01..2026-01-31');
check('short month, 30 days',    periodOf('2026-04'),    '2026-04-01..2026-04-30');
check('february, leap year',     periodOf('2024-02'),    '2024-02-01..2024-02-29');
check('february, common year',   periodOf('2026-02'),    '2026-02-01..2026-02-28');
check('month 13 is not a month', periodOf('2026-13'),    'not a period');

echo "\nPERIODS - month names in English, which are always accepted:\n";
check('full name',               periodOf('October'),      "$thisYear-10-01..$thisYear-10-31");
check('abbreviated',             periodOf('Oct'),          "$thisYear-10-01..$thisYear-10-31");
check('lowercase',               periodOf('october'),      "$thisYear-10-01..$thisYear-10-31");
check('uppercase',               periodOf('OCT'),          "$thisYear-10-01..$thisYear-10-31");
check('trailing dot',            periodOf('Oct.'),         "$thisYear-10-01..$thisYear-10-31");
check('four letter form',        periodOf('Sept'),         "$thisYear-09-01..$thisYear-09-30");
check('with a year',             periodOf('October 2026'), '2026-10-01..2026-10-31');
check('abbreviated with a year', periodOf('Feb 2024'),     '2024-02-01..2024-02-29');
check('comma before the year',   periodOf('Oct, 2024'),    '2024-10-01..2024-10-31');
// May is both a month and an ordinary word, but on a date column it can only mean the month.
check('May is a month',          periodOf('May'),          "$thisYear-05-01..$thisYear-05-31");

echo "\nNOT PERIODS - these must fall through to the ordinary single date handling:\n";
check('a full date',             periodOf('2026-10-15'),   'not a period');
check('a day and month',         periodOf('July 25'),      'not a period');
check('the same, reversed',      periodOf('25 July'),      'not a period');
check('a relative phrase',       periodOf('last Thursday'),'not a period');
check('a day of the week',       periodOf('Monday'),       'not a period');
check('an abbreviated weekday',  periodOf('Sat'),          'not a period');
check('not a date at all',       periodOf('bread'),        'not a period');
check('empty',                   periodOf(''),             'not a period');

echo "\nLANGUAGE - the language the page is being read in, on top of English:\n";
$_GET['lang'] = 'fr';
check('french full name',        periodOf('Octobre'),      "$thisYear-10-01..$thisYear-10-31");
check('french lowercase',        periodOf('octobre'),      "$thisYear-10-01..$thisYear-10-31");
check('french abbreviated',      periodOf('oct'),          "$thisYear-10-01..$thisYear-10-31");
check('accented',                periodOf('Février'),      "$thisYear-02-01..$thisYear-02-28");
check('accent left off',         periodOf('fevrier'),      "$thisYear-02-01..$thisYear-02-28");
check('accented, uppercase',     periodOf('FÉVRIER'),      "$thisYear-02-01..$thisYear-02-28");
check('circumflex',              periodOf('Août'),         "$thisYear-08-01..$thisYear-08-31");
check('circumflex left off',     periodOf('aout'),         "$thisYear-08-01..$thisYear-08-31");
check('french with a year',      periodOf('Janvier 2024'), '2024-01-01..2024-01-31');
check('juin, four letters',      periodOf('juin'),         "$thisYear-06-01..$thisYear-06-30");
check('juil, four letters',      periodOf('juil'),         "$thisYear-07-01..$thisYear-07-31");
// "jui" is the first three letters of both juin and juillet, so it is dropped rather than guessed at.
check('ambiguous short form',    periodOf('jui'),          'not a period');
check('english still works',     periodOf('October'),      "$thisYear-10-01..$thisYear-10-31");
unset($_GET['lang']);

echo "\nLANGUAGE - whatever the loaded language files call the months:\n";
// Stands in for a site whose language files are neither of the two Formulize ships a table for. The names are stored
// HTML encoded, exactly as the real language files store them.
define('_CAL_OCTOBER', 'Oktober');
define('_CAL_MARCH', 'M&auml;rz');
$_GET['lang'] = 'de'; // a language with no built-in table, so only English and the constants are in play
check('name from language file', periodOf('Oktober'),      "$thisYear-10-01..$thisYear-10-31");
check('entity decoded',          periodOf('März'),         "$thisYear-03-01..$thisYear-03-31");
check('umlaut left off',         periodOf('marz'),         "$thisYear-03-01..$thisYear-03-31");
check('english still works',     periodOf('October'),      "$thisYear-10-01..$thisYear-10-31");
unset($_GET['lang']);

echo "\nOPERATORS - naming a period changes what each comparison should mean:\n";
$handler = new DateHandlerUnderTest();
$handler->searchPeriod = array('start' => '2026-10-01', 'end' => '2026-10-31');
function clauseFor($handler, $operator) {
    $clause = $handler->buildSearchWhereClause('ignored', $operator, "'", '', 1, 'main', 'main.`when`');
    return $clause === false ? false : trim(preg_replace('/\s+/', ' ', $clause));
}
check('no operator (LIKE)', clauseFor($handler, 'LIKE'), "( main.`when` >= '2026-10-01' AND main.`when` <= '2026-10-31' )");
check('equals is the month', clauseFor($handler, '='),   "( main.`when` >= '2026-10-01' AND main.`when` <= '2026-10-31' )");
check('on or after the 1st', clauseFor($handler, '>='),  "main.`when` >= '2026-10-01'");
check('after the whole month', clauseFor($handler, '>'), "main.`when` > '2026-10-31'");
check('on or before the 31st', clauseFor($handler, '<='),"main.`when` <= '2026-10-31'");
check('before the whole month', clauseFor($handler, '<'),"main.`when` < '2026-10-01'");
check('not equal is outside', clauseFor($handler, '!='), "( main.`when` < '2026-10-01' OR main.`when` > '2026-10-31' )");
check('not like is outside',  clauseFor($handler, 'NOT LIKE'), "( main.`when` < '2026-10-01' OR main.`when` > '2026-10-31' )");

echo "\nOPERATORS - anything that is not a period is handed back to the ordinary handling:\n";
// Returning false is what keeps every existing date search on exactly the path it took before this feature.
$plain = new DateHandlerUnderTest();
check('no period recorded',   clauseFor($plain, 'LIKE'), false);
$handler->searchPeriod = array('start' => '2026-10-01', 'end' => '2026-10-31');
check('no column to compare', $handler->buildSearchWhereClause('x', 'LIKE', "'", '', 1, 'main', ''), false);
check('an operator with no meaning here', clauseFor($handler, 'IN'), false);

echo "\n";
printf("RESULT: %d passed, %d failed\n", $GLOBALS['__pass'], $GLOBALS['__fail']);
exit($GLOBALS['__fail'] === 0 ? 0 : 1);
