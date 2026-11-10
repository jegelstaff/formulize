<?php
/**
 * Standalone logic tests for the formats time elements show times in.
 *
 * These are NOT PHPUnit / Playwright - deliberately a single dependency-free script, following the same pattern as
 * tests/date_search_test.php, so it can run anywhere PHP is available:
 *   php tests/time_format_test.php
 *
 * WHAT THIS COVERS
 * Times are stored as HH:MM:SS. Time elements show them in one of two formats the site sets, _12HTIMEFORMAT and
 * _24HTIMEFORMAT, depending on whether the element (or the site) is set to 12-hour or 24-hour times. Whatever the format
 * is, what's shown has to come back to the same time when it's read, because three things read it:
 *
 *   searches and conditions, which turn what was typed into the time the database compares,
 *     formulizeTimeElementHandler::prepareLiteralTextForDB()
 *   the check of conditions against one entry, which compares the dataset's value, the time as it's shown,
 *     formulize_timeValueAsISO()
 *   derived values that work out the time between two times, with strtotime or formulize_timeValueAsISO()
 *
 * So each format is checked both ways, the defaults, which have to give what time elements have always shown, and
 * formats a site might choose, including one strtotime can't read. Checking conditions against an entry compares
 * the times as text, so the ordering checks are on HH:MM:SS text, the way that comparison sees them.
 *
 * WHAT THIS DOES NOT COVER
 * Whether the settings reach the constants, which happens when the settings are saved and global.php is loaded, and
 * whether buildEvaluationCondition() is reached, which needs the module bootstrap and a database.
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

$functionsFile = __DIR__ . '/../modules/formulize/include/functions.php';
$timeFile      = __DIR__ . '/../modules/formulize/class/timeElement.php';
foreach (array('functions.php' => $functionsFile, 'timeElement.php' => $timeFile) as $label => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Cannot find $label at: $path\n");
        exit(2);
    }
}

/**
 * Lift a single function or method's source out of a PHP file using the tokenizer, so braces inside strings or comments
 * cannot be mistaken for its own. (Same approach as date_search_test.php - neither file can be included standalone,
 * they pull in the module bootstrap.)
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
foreach (array('formulize_timeValueAsISO', 'formulize_mysqlTimeFormat', 'formulize_timeSearchTerm', 'formulize_timeLikeComparison', 'formulize_timeMatchesSearch') as $name) {
    $code = extractFunctionSource($functionsSource, $name);
    if ($code === false) {
        fwrite(STDERR, "Could not extract $name()\n");
        exit(2);
    }
    eval($code);
}

// Times contain nothing that needs escaping, and the patterns only have backslashes, so this stands in faithfully for the real one.
function formulize_db_escape($value) { return addslashes($value); }

// The real methods, wrapped in a bare class so they can be called without the module bootstrap. getEffectiveTimeFormat()
// reads the element and the site's settings, which need the bootstrap, so this stands in for it: the "element" is just
// '12' or '24', or null for the site's setting, which is $GLOBALS['__siteTimeFormat'].
$timeSource = file_get_contents($timeFile);
$methodSources = '';
foreach (array('getTimeFormatStrings', 'formatTime', 'prepareLiteralTextForDB', 'convert12To24HourTime', 'buildSearchWhereClause') as $name) {
    $code = extractFunctionSource($timeSource, $name);
    if ($code === false) {
        fwrite(STDERR, "Could not extract $name() from $timeFile\n");
        exit(2);
    }
    $methodSources .= $code . "\n";
}
$GLOBALS['__siteTimeFormat'] = '12';
eval('class TimeHandlerUnderTest { ' . $methodSources . '
    function getEffectiveTimeFormat($element) { return $element === null ? $GLOBALS["__siteTimeFormat"] : $element; }
    function elementForSearchColumn($column) { return null; }
}');

// ---- tiny assertion harness -------------------------------------------------
$GLOBALS['__pass'] = 0;
$GLOBALS['__fail'] = 0;
function check($label, $got, $want) {
    $ok = ($got === $want);
    $GLOBALS[$ok ? '__pass' : '__fail']++;
    if (!$ok) {
        echo "FAIL: $label\n      got:  " . var_export($got, true) . "\n      want: " . var_export($want, true) . "\n";
    }
}

/**
 * Run the checks with a pair of formats. The constants can only be defined once, so each pair runs in its own process.
 */
function runWithFormats($twelve, $twentyFour) {
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --formats=' . escapeshellarg(base64_encode(json_encode(array($twelve, $twentyFour))));
    passthru($command, $exitCode);
    return $exitCode;
}

$formatsArgument = null;
foreach (array_slice($argv, 1) as $argument) {
    if (strpos($argument, '--formats=') === 0) {
        $formatsArgument = json_decode(base64_decode(substr($argument, strlen('--formats='))), true);
    }
}

if ($formatsArgument === null) {
    // the parent: run each pair of formats in a child process, and report on them all
    $pairs = array(
        array('g:ia', 'H:i'),       // the defaults, which give what time elements have always shown
        array('g:i A', 'G:i'),      // 1:30 PM and 9:05, common choices
        array('h:i a', 'H\hi'),     // 01:30 pm and 13h30, which strtotime can't read
    );
    $failed = 0;
    foreach ($pairs as $pair) {
        echo "Formats: 12-hour " . $pair[0] . ", 24-hour " . $pair[1] . "\n";
        if (runWithFormats($pair[0], $pair[1]) !== 0) {
            $failed++;
        }
    }
    echo $failed ? "\n$failed set(s) of formats FAILED\n" : "\nAll sets of formats passed\n";
    exit($failed ? 1 : 0);
}

define('_12HTIMEFORMAT', $formatsArgument[0]);
define('_24HTIMEFORMAT', $formatsArgument[1]);
$handler = new TimeHandlerUnderTest();
$isDefault = (_12HTIMEFORMAT === 'g:ia' AND _24HTIMEFORMAT === 'H:i');

// ---- showing a time ---------------------------------------------------------
// every hour and a few minutes, in both formats, as PHP's own date() shows them
$storedTimes = array();
for ($hour = 0; $hour < 24; $hour++) {
    foreach (array(0, 5, 30, 59) as $minute) {
        $storedTimes[] = sprintf('%02d:%02d:00', $hour, $minute);
    }
}
foreach ($storedTimes as $stored) {
    $moment = DateTime::createFromFormat('!H:i:s', $stored);
    check("12-hour $stored", $handler->formatTime($stored, '12'), $moment->format(_12HTIMEFORMAT));
    check("24-hour $stored", $handler->formatTime($stored, '24'), $moment->format(_24HTIMEFORMAT));
    check("without seconds $stored", $handler->formatTime(substr($stored, 0, 5), '24'), $moment->format(_24HTIMEFORMAT));
}

// the site's setting applies when there's no element
$GLOBALS['__siteTimeFormat'] = '24';
check('no element, site is 24-hour', $handler->formatTime('13:30:00', null), DateTime::createFromFormat('!H:i', '13:30')->format(_24HTIMEFORMAT));
$GLOBALS['__siteTimeFormat'] = '12';
check('no element, site is 12-hour', $handler->formatTime('13:30:00', null), DateTime::createFromFormat('!H:i', '13:30')->format(_12HTIMEFORMAT));

// what time elements have always shown, which the default formats have to keep
if ($isDefault) {
    check('default 12-hour, afternoon', $handler->formatTime('13:30:00', '12'), '1:30pm');
    check('default 12-hour, midnight', $handler->formatTime('00:30:00', '12'), '12:30am');
    check('default 12-hour, noon', $handler->formatTime('12:00:00', '12'), '12:00pm');
    check('default 12-hour, morning', $handler->formatTime('09:05:00', '12'), '9:05am');
    check('default 24-hour, morning', $handler->formatTime('09:05:00', '24'), '09:05');
    check('default 24-hour, evening', $handler->formatTime('23:59:00', '24'), '23:59');
}

// blank stays blank, and something that isn't a time comes back as it was
check('blank', $handler->formatTime('', '12'), '');
check('null', $handler->formatTime(null, '12'), null);
check('not a time', $handler->formatTime('soon', '12'), 'soon');
check('more than a day, which a TIME column can hold', $handler->formatTime('100:00:00', '12'), '100:00:00');

// ---- reading a shown time back ---------------------------------------------
// what's shown, in either format, comes back as the time that was stored, whichever format the element uses
foreach ($storedTimes as $stored) {
    $shown12 = $handler->formatTime($stored, '12');
    $shown24 = $handler->formatTime($stored, '24');
    foreach (array('12', '24') as $elementFormat) {
        $formats = $handler->getTimeFormatStrings($elementFormat);
        check("read back $shown12 on a $elementFormat-hour element", formulize_timeValueAsISO($shown12, $formats), $stored);
        check("read back $shown24 on a $elementFormat-hour element", formulize_timeValueAsISO($shown24, $formats), $stored);
    }
    check("read back $shown12 with the site's formats", formulize_timeValueAsISO($shown12), $stored);
}
check('stored with seconds', formulize_timeValueAsISO('13:30:15'), '13:30:15');
check('stored without seconds', formulize_timeValueAsISO('13:30'), '13:30:00');
check('one digit hour', formulize_timeValueAsISO('9:05'), '09:05:00');
check('spaces around it', formulize_timeValueAsISO(' 13:30 '), '13:30:00');
check('blank is left alone', formulize_timeValueAsISO(''), '');
check('null is left alone', formulize_timeValueAsISO(null), null);
check('an hour that is too big is not a time', formulize_timeValueAsISO('25:00'), '25:00');
check('a word is not a time', formulize_timeValueAsISO('soon'), 'soon');

// ---- what was typed, for the database ---------------------------------------
// a time typed the way they're stored is left as typed, so a partial search for 9:00 finds 9:00 and 19:00
check('typed as stored', $handler->prepareLiteralTextForDB('13:30', '12'), '13:30');
check('typed as stored, one digit hour', $handler->prepareLiteralTextForDB('9:00', '12'), '9:00');
check('typed as stored, seconds', $handler->prepareLiteralTextForDB('13:30:00', '24'), '13:30:00');
check('blank typed', $handler->prepareLiteralTextForDB('', '12'), '');
check('no element, site format', $handler->prepareLiteralTextForDB($handler->formatTime('13:30:00', null), null), '13:30');
// a time typed the way it's shown becomes HH:MM
foreach (array('13:30:00', '00:05:00', '12:00:00', '09:59:00') as $stored) {
    foreach (array('12', '24') as $elementFormat) {
        $shown = $handler->formatTime($stored, $elementFormat);
        $want = substr($stored, 0, 5);
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $shown)) {
            $want = $shown; // shown the way it's stored, so it's left as typed
        }
        check("typed $shown on a $elementFormat-hour element", $handler->prepareLiteralTextForDB($shown, $elementFormat), $want);
    }
}
// the loose forms people type, whatever the formats are
check('1pm', $handler->prepareLiteralTextForDB('1pm', '12'), '13:00');
check('1:30PM', $handler->prepareLiteralTextForDB('1:30PM', '24'), '13:30');
check('12:15am', $handler->prepareLiteralTextForDB('12:15am', '12'), '00:15');

// ---- comparing times the way the check against one entry does ---------------
// times shown in any format, read back, put in order as text, the same order the database puts them in
$shownInOrder = array();
foreach (array('00:05:00', '09:00:00', '11:59:00', '12:00:00', '13:30:00', '23:59:00') as $stored) {
    $shownInOrder[] = $handler->formatTime($stored, '12');
}
for ($i = 1; $i < count($shownInOrder); $i++) {
    $earlier = formulize_timeValueAsISO($shownInOrder[$i - 1]);
    $later = formulize_timeValueAsISO($shownInOrder[$i]);
    check("{$shownInOrder[$i - 1]} is before {$shownInOrder[$i]}", $earlier < $later, true);
}
$term = formulize_timeValueAsISO($handler->prepareLiteralTextForDB('9:00', '12'));
check('a typed 9:00 compared with 1:30pm', formulize_timeValueAsISO($handler->formatTime('13:30:00', '12')) > $term, true);

// ---- searching for part of a time --------------------------------------------
// What a LIKE search or condition finds, through formulize_timeMatchesSearch, which is how a condition is checked against one entry.
// formulize_timeLikeComparison builds the same thing in SQL, which can't be run here, so its SQL is checked as text further down.
function timesFoundBy($handler, $term, $elementFormat, $storedTimes) {
    $formats = $handler->getTimeFormatStrings($elementFormat);
    $search = formulize_timeSearchTerm($term, $handler->prepareLiteralTextForDB($term, $elementFormat, true), $formats);
    $found = array();
    foreach ($storedTimes as $stored) {
        if (formulize_timeMatchesSearch($handler->formatTime($stored, $elementFormat), $stored, $search)) {
            $found[] = $stored;
        }
    }
    return $found;
}
// a time typed the way the element shows it finds that time, and only that time
foreach (array('12', '24') as $elementFormat) {
    foreach ($storedTimes as $stored) {
        $shown = $handler->formatTime($stored, $elementFormat);
        check("searching for $shown on a $elementFormat-hour element", timesFoundBy($handler, $shown, $elementFormat, $storedTimes), array($stored));
    }
}
// a time typed in the other format finds it too, ie: 13:30 on an element that shows 1:30pm
foreach ($storedTimes as $stored) {
    $shown24 = $handler->formatTime($stored, '24');
    check("searching for $shown24 on a 12-hour element", in_array($stored, timesFoundBy($handler, $shown24, '12', $storedTimes)), true);
}
check('blank finds nothing', timesFoundBy($handler, '', '12', $storedTimes), array());
check('a blank time is never found', formulize_timeMatchesSearch('', '', formulize_timeSearchTerm('5', '5', array(_12HTIMEFORMAT))), false);
if ($isDefault) {
    // 5 is anywhere in the time, as it's shown
    $withFive = array_values(array_filter($storedTimes, function ($stored) use ($handler) { return strpos($handler->formatTime($stored, '12'), '5') !== false; }));
    check('5 is anywhere in the time, as shown', timesFoundBy($handler, '5', '12', $storedTimes), $withFive);
    check('5 finds 5:30pm', in_array('17:30:00', $withFive), true);
    check('5 does not find 3:00pm, though 15:00 has a 5', in_array('15:00:00', timesFoundBy($handler, '5', '12', $storedTimes)), false);
    // 5: is the hour, so 5am and 5pm on a 12-hour element, and only 5am on a 24-hour one
    check('5: on a 12-hour element', timesFoundBy($handler, '5:', '12', $storedTimes), array('05:00:00', '05:05:00', '05:30:00', '05:59:00', '17:00:00', '17:05:00', '17:30:00', '17:59:00'));
    check('5: on a 24-hour element', timesFoundBy($handler, '5:', '24', $storedTimes), array('05:00:00', '05:05:00', '05:30:00', '05:59:00'));
    check('05: on a 24-hour element', timesFoundBy($handler, '05:', '24', $storedTimes), array('05:00:00', '05:05:00', '05:30:00', '05:59:00'));
    check('1: is not 11 or 21', timesFoundBy($handler, '1:', '24', $storedTimes), array('01:00:00', '01:05:00', '01:30:00', '01:59:00'));
    check('5:3 is the hour and the start of the minutes', timesFoundBy($handler, '5:3', '12', $storedTimes), array('05:30:00', '17:30:00'));
    check('pm is the afternoon and evening', count(timesFoundBy($handler, 'pm', '12', $storedTimes)), 48);
    check('PM is the same', count(timesFoundBy($handler, 'PM', '12', $storedTimes)), 48);
    check('pm is nothing on a 24-hour element', timesFoundBy($handler, 'pm', '24', $storedTimes), array());
    check('30pm is part of a time', count(timesFoundBy($handler, '30pm', '12', $storedTimes)), 12);
    check("1pm is one o'clock", timesFoundBy($handler, '1pm', '12', $storedTimes), array('13:00:00'));
    check('pm is left as typed for the database', $handler->prepareLiteralTextForDB('pm', '12', true), 'pm');
    // the SQL
    check('TIME_FORMAT for g:ia', formulize_mysqlTimeFormat('g:ia'), '%l:%i%p');
    check('TIME_FORMAT for H:i', formulize_mysqlTimeFormat('H:i'), '%H:%i');
    check('TIME_FORMAT for a literal letter', formulize_mysqlTimeFormat('H\hi'), '%Hh%i');
    check('the SQL for 5:', $handler->buildSearchWhereClause('5:', 'LIKE', "'", '%', 1, 'main', 'main.`starts`', '5:'),
        " ( LOWER(TIME_FORMAT(main.`starts`,'%l:%i%p')) REGEXP '(^|[^0-9:])0*5\\\\:' ) ");
    check('the SQL for 1:30pm, which is a whole time', $handler->buildSearchWhereClause('13:30', 'NOT LIKE', "'", '%', 1, 'main', 'main.`starts`', '1:30pm'),
        " ( NOT ( LOWER(TIME_FORMAT(main.`starts`,'%l:%i%p')) REGEXP '(^|[^0-9:])0*1\\\\:30pm' OR TIME_FORMAT(main.`starts`,'%H:%i') = '13:30' ) ) ");
    check('= is not done here', $handler->buildSearchWhereClause('13:30', '=', "'", '', 1, 'main', 'main.`starts`', '1:30pm'), false);
}

// ---- derived values ----------------------------------------------------------
// the defaults have always been readable by strtotime, which derived values that work out the time between two times rely on
if ($isDefault) {
    foreach ($storedTimes as $stored) {
        foreach (array('12', '24') as $elementFormat) {
            $shown = $handler->formatTime($stored, $elementFormat);
            check("strtotime reads $shown", date('H:i:s', strtotime("2000-01-01 $shown")), $stored);
        }
    }
}
// and formulize_timeValueAsISO reads any format, so the time between two times comes out right whatever the format
$start = formulize_timeValueAsISO($handler->formatTime('09:15:00', '12'));
$end = formulize_timeValueAsISO($handler->formatTime('17:45:00', '12'));
check('time between 9:15 and 17:45, in minutes', (strtotime("2000-01-01 $end") - strtotime("2000-01-01 $start")) / 60, 510);

echo "  " . $GLOBALS['__pass'] . " passed, " . $GLOBALS['__fail'] . " failed\n";
exit($GLOBALS['__fail'] ? 1 : 0);
