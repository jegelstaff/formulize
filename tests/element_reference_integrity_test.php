<?php
/**
 * Integrity tests for the element reference scan - what deleting an element would disturb, and what
 * deleting it actually changes.
 *
 * Unlike the other tests in this folder, this one is NOT dependency free. It boots the application and
 * runs against the live database, because the thing under test is a sweep of every screen, every element's
 * settings, every saved view and every form on a real system. That is why it runs AFTER the Playwright
 * suite in CI rather than before it: by then the museum system the setup specs build is complete, and it
 * is a far richer body of configuration than any fixture this file could invent.
 *
 * WHAT THIS COVERS
 *
 * 1. THE LIVE SYSTEM. Every element is put through formulizeElementsHandler::elementUsageReport(), and
 *    every change the cleanup would make is inspected without being applied. The invariants checked are
 *    the ones whose failure is silent and expensive:
 *      - conditions are four arrays that stay parallel and 0-indexed. buildConditionsFilterSQL() walks
 *        $conditions[0] and reads the other three at the same key, so a set that falls out of step does
 *        not error, it filters on the wrong field.
 *      - no condition is left without an operator, which would build broken SQL further down.
 *      - a saved view's columns, searches and calculations stay in step with each other. These are
 *        position-linked lists with nothing but position tying them together, and a shift moves every
 *        later search onto the wrong column.
 *      - everything written back unserializes.
 *
 * 2. THE PLACES THE MUSEUM SYSTEM DOES NOT HAPPEN TO HAVE. Map screens, calendar screens, single page
 *    form screens, saved views with searches and calculations, dynamic default values, screens naming an
 *    entry in the address bar, and page conditions in the format that predates the current one. These are
 *    built as fixtures, cleaned up for real, and checked - all inside a transaction that is rolled back,
 *    so the database is exactly as it was when the test finishes. The fixtures hang off an element this
 *    test creates, so no real row is touched.
 *
 * 3. THE MULTIPAGE PAGE THAT EMPTIES. Deleting the last element on a page leaves the page in place, and
 *    traverseScreenPages() does not carry an empty page through to the form.
 *
 * WHAT THIS DOES NOT COVER
 * The deletion itself - delete() drops a column from the form's data table, which is not transactional and
 * cannot be rolled back, so it has no business running against a system another test may still be reading.
 * The Playwright specs delete elements through the admin UI on forms they own. What this file tests is the
 * part of deletion that reaches across the whole system.
 *
 * Usage:
 *   docker exec formulize-web-1 php /var/www/html/tests/element_reference_integrity_test.php
 *
 * @package Formulize
 * @subpackage tests
 */

// Command-line / CI only. A web request just 404s - this ships in the repo and must never be a live
// endpoint, and it writes to the database (inside a transaction, but still).
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// The bootstrap reads these. Running from the command line there is no request to read them from.
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';

$root = dirname(__DIR__);
if (!is_file($root . '/mainfile.php')) {
    fwrite(STDERR, "Cannot find mainfile.php at: $root/mainfile.php - is Formulize installed?\n");
    exit(2);
}
include $root . '/mainfile.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/calendarScreen.php';

global $xoopsDB;
$element_handler = xoops_getmodulehandler('elements', 'formulize');
$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
if (!$element_handler) {
    fwrite(STDERR, "Could not load the elements handler - the module bootstrap did not complete.\n");
    exit(2);
}

// scanElementReferences() is private: the report and the cleanup are its only callers by design. The test
// reaches in because what it needs to check is the change that would be written, which neither of those
// two exposes - the report describes it in words, and the cleanup applies it and moves on.
$scan = new ReflectionMethod('formulizeElementsHandler', 'scanElementReferences');
$scan->setAccessible(true);

$GLOBALS['__pass'] = 0;
$GLOBALS['__fail'] = 0;

function check($label, $got, $want) {
    if ($got === $want) {
        $GLOBALS['__pass']++;
        return true;
    }
    $GLOBALS['__fail']++;
    printf("  FAIL %s\n       got:  %s\n       want: %s\n", $label, var_export($got, true), var_export($want, true));
    return false;
}

function ok($label, $condition, $detail = '') {
    if ($condition) {
        $GLOBALS['__pass']++;
        return true;
    }
    $GLOBALS['__fail']++;
    printf("  FAIL %s%s\n", $label, $detail === '' ? '' : "\n       $detail");
    return false;
}

function prefix($table) {
    global $xoopsDB;
    return $xoopsDB->prefix($table);
}

function fetchOne($sql) {
    global $xoopsDB;
    $result = $xoopsDB->query($sql);
    return $result ? $xoopsDB->fetchArray($result) : false;
}

/**
 * A conditions set is four arrays that have to stay parallel and 0-indexed, and every condition needs an
 * operator. Anything else filters on the wrong thing rather than failing.
 */
function checkConditionsShape($value, $where) {
    if (!is_array($value) || !isset($value[0])) {
        return; // an empty array is how "no conditions" is stored, and is fine
    }
    $count = count($value[0]);
    for ($part = 0; $part <= 3; $part++) {
        ok("$where: conditions have part $part", isset($value[$part]));
        if (!isset($value[$part])) {
            continue;
        }
        ok("$where: part $part has $count entries like the element list does",
            count($value[$part]) === $count,
            'part ' . $part . ' has ' . count($value[$part]));
        if ($count > 0) {
            ok("$where: part $part is 0-indexed", array_keys($value[$part]) === range(0, $count - 1));
        }
    }
    if (isset($value[1])) {
        foreach ($value[1] as $i => $operator) {
            ok("$where: condition $i has an operator", trim((string) $operator) !== '');
        }
    }
}

// Columns whose stored form is a serialized array, so whatever is written back has to unserialize.
$serializedColumns = array(
    'advanceview', 'hiddencolumns', 'decolumns', 'customactions', 'fundamental_filters', 'conditions',
    'elementdefaults', 'formelements', 'columns', 'datasets', 'filter', 'ele_filtersettings',
    'ele_disabledconditions', 'ele_dynamicdefault_conditions', 'entries_are_users_conditions',
    'entries_are_users_default_groups_element_links',
);
// Of those, the ones that hold a conditions set.
$conditionColumns = array(
    'fundamental_filters', 'filter', 'ele_filtersettings', 'ele_disabledconditions',
    'ele_dynamicdefault_conditions',
);

echo "\n";
echo "=====================================================================\n";
echo " PART 1 - every element on this system\n";
echo "=====================================================================\n";

$elementIds = array();
$result = $xoopsDB->query("SELECT ele_id FROM " . prefix('formulize') . " ORDER BY ele_id ASC");
while ($row = $xoopsDB->fetchArray($result)) {
    $elementIds[] = intval($row['ele_id']);
}
ok('the system has elements to check', count($elementIds) > 0, 'found ' . count($elementIds));

$started = microtime(true);
$elementsWithReferences = 0;
$totalReferences = 0;
$sectionCounts = array();

foreach ($elementIds as $elementId) {
    if (!$elementObject = $element_handler->get($elementId)) {
        continue;
    }

    try {
        $report = $element_handler->elementUsageReport($elementObject);
    } catch (Throwable $t) {
        ok("element $elementId: the usage report can be produced", false,
            get_class($t) . ': ' . $t->getMessage() . ' @ ' . $t->getFile() . ':' . $t->getLine());
        continue;
    }
    $GLOBALS['__pass']++; // the report was produced

    // the report is what a person is shown before they agree to a deletion, so it has to say what the
    // element is and be a list of sections rather than whatever the scan happened to return
    ok("element $elementId: report identifies the element", isset($report['element_handle']) && $report['element_handle'] !== '');
    ok("element $elementId: report has a section list", isset($report['cleaned_up_automatically']) && is_array($report['cleaned_up_automatically']));
    foreach ($report['cleaned_up_automatically'] as $section) {
        ok("element $elementId: section has a heading", !empty($section['what']));
        ok("element $elementId: section has entries", !empty($section['items']) && is_array($section['items']));
    }

    $references = $scan->invoke($element_handler, $elementObject);
    if ($references) {
        $elementsWithReferences++;
    }
    $totalReferences += count($references);

    foreach ($references as $reference) {
        $sectionCounts[$reference['section']] = (isset($sectionCounts[$reference['section']]) ? $sectionCounts[$reference['section']] : 0) + 1;
        ok("element $elementId: reference is described", trim((string) $reference['description']) !== '');
        if (!$reference['table']) {
            continue; // reported only, cleaned up elsewhere
        }
        ok("element $elementId: reference on {$reference['table']} names a row", intval($reference['key_value']) > 0);

        foreach ($reference['updates'] as $column => $value) {
            $where = "element $elementId -> {$reference['table']}.$column";
            if (!ok("$where: the new value is a literal", is_string($value) || is_int($value), 'it is a ' . gettype($value))) {
                continue;
            }
            if (!in_array($column, $serializedColumns)) {
                continue;
            }
            $restored = @unserialize((string) $value);
            if (!ok("$where: the new value unserializes", $restored !== false || (string) $value === 'b:0;')) {
                continue;
            }
            if (in_array($column, $conditionColumns)) {
                checkConditionsShape($restored, $where);
            }
            // a multipage screen's conditions are per page, and a form's entries-are-users conditions are
            // per group, so in both cases the shape to check is one level in
            if (($column === 'conditions' || $column === 'entries_are_users_conditions') && is_array($restored)) {
                foreach ($restored as $key => $inner) {
                    checkConditionsShape($inner, "$where [$key]");
                }
            }
        }

        // a saved view's lists are tied to each other by position and nothing else
        if ($reference['table'] === 'formulize_saved_views' && $reference['updates']) {
            $before = fetchOne("SELECT * FROM " . prefix('formulize_saved_views') . " WHERE sv_id = " . intval($reference['key_value']));
            $after = array_merge((array) $before, $reference['updates']);
            $viewLabel = "view {$reference['key_value']}";
            if ($before && (string) $before['sv_quicksearches'] !== '') {
                $columnsBefore = count(explode(',', (string) $before['sv_oldcols']));
                $searchesBefore = count(explode('&*=%4#', (string) $before['sv_quicksearches']));
                if ($columnsBefore === $searchesBefore) {
                    ok("$viewLabel: columns and searches stay in step",
                        count(explode(',', (string) $after['sv_oldcols'])) === count(explode('&*=%4#', (string) $after['sv_quicksearches'])));
                }
            }
            if ($before && isset($reference['updates']['sv_calc_cols'])) {
                $calcColumnsBefore = count(explode('/', (string) $before['sv_calc_cols']));
                foreach (array('sv_calc_calcs', 'sv_calc_blanks', 'sv_calc_grouping') as $parallel) {
                    if ($calcColumnsBefore === count(explode('/', (string) $before[$parallel]))) {
                        ok("$viewLabel: calculations stay in step with $parallel",
                            count(explode('/', (string) $after['sv_calc_cols'])) === count(explode('/', (string) $after[$parallel])));
                    }
                }
            }
        }
    }
}

printf("\n  %d elements, %d with references, %d references in %0.1fs\n\n",
    count($elementIds), $elementsWithReferences, $totalReferences, microtime(true) - $started);
foreach ($sectionCounts as $section => $count) {
    printf("  %5d  %s\n", $count, $section);
}
echo "\n";

// A scan that quietly stopped finding anything would sail through every check above, because every check
// is about the shape of what it found. This is the floor: the museum system the setup specs build is full
// of screens and relationships, so the scan has to see them.
ok('the scan finds references on this system', $elementsWithReferences > 0);
ok('and across more than one kind of place', count($sectionCounts) > 1, 'sections found: ' . implode(', ', array_keys($sectionCounts)));
ok('including form screens', isset($sectionCounts[_AM_ELE_USAGE_SECTION_FORM_SCREENS]));
ok('including list screens', isset($sectionCounts[_AM_ELE_USAGE_SECTION_LIST_SCREENS]));

echo "=====================================================================\n";
echo " PART 2 - the kinds of reference this system does not happen to have\n";
echo "=====================================================================\n";

// Declared out here so the rollback check at the end can see them even if the fixtures throw part way
// through. $borrowedRowBefore stays null until a real row has actually been written to.
$borrowedRowBefore = null;
$otherElementId = 0;
$elementId = 0;

$xoopsDB->queryF("START TRANSACTION");

try {
    // A form to hang the fixtures off, and an element of our own so that cleaning up cannot touch a real
    // row. It is inserted directly rather than through the handler: the handler's insert is a bigger
    // operation than this needs, and nothing here ever calls delete(), so no data column is involved.
    $formRow = fetchOne("SELECT id_form FROM " . prefix('formulize_id') . " ORDER BY id_form ASC LIMIT 1");
    if (!$formRow) {
        throw new Exception('there are no forms on this system to attach fixtures to');
    }
    $fixtureFid = intval($formRow['id_form']);
    $handle = 'zz_reference_integrity_fixture';
    $xoopsDB->queryF("INSERT INTO " . prefix('formulize') . "
        (id_form, ele_type, ele_caption, ele_handle, ele_order, ele_display, ele_value)
        VALUES ($fixtureFid, 'text', 'Reference integrity fixture', '$handle', 9999, 1, 'a:0:{}')");
    $elementId = intval($xoopsDB->getInsertId());
    if (!$fixture = $element_handler->get($elementId, true)) {
        throw new Exception('could not read back the fixture element');
    }
    ok('a fixture element can be created to hang the fixtures on', $elementId > 0);

    $newScreen = function ($screenHandle, $title, $type, $rewriteElement = 0) use ($xoopsDB, $fixtureFid) {
        $xoopsDB->queryF("INSERT INTO " . prefix('formulize_screen') . "
            (screen_handle, title, fid, frid, type, rewriteruleElement)
            VALUES ('$screenHandle', '$title', $fixtureFid, 0, '$type', " . intval($rewriteElement) . ")");
        return intval($xoopsDB->getInsertId());
    };

    // a saved view: a column with a search on it, a calculation, and the sort column
    $xoopsDB->queryF("INSERT INTO " . prefix('formulize_saved_views') . "
        (sv_name, sv_formframe, sv_mainform, sv_oldcols, sv_quicksearches, sv_sort, sv_calc_cols, sv_calc_calcs, sv_calc_blanks, sv_calc_grouping)
        VALUES ('reference integrity fixture', $fixtureFid, 0, 'first_col,$handle,last_col',
        'search1&*=%4#!persist!&*=%4#search3', '$handle', '11/$elementId/22', 'sum/avg/count', 'all/noblanks/all', '/grp/')");
    $viewId = intval($xoopsDB->getInsertId());
    // a second view where the column was only carried along for the sake of a persistent search
    $xoopsDB->queryF("INSERT INTO " . prefix('formulize_saved_views') . "
        (sv_name, sv_formframe, sv_mainform, sv_oldcols, sv_quicksearches, sv_sort)
        VALUES ('reference integrity hidden fixture', $fixtureFid, 0, 'a,hiddencolumn_$handle,b', 'sa&*=%4#!hidden!&*=%4#sb', 'a')");
    $hiddenViewId = intval($xoopsDB->getInsertId());

    // a map screen plotted on this element
    $mapSid = $newScreen('zzfixmap', 'Reference integrity map', 'map');
    $xoopsDB->queryF("INSERT INTO " . prefix('formulize_screen_map') . "
        (sid, lat_element, lng_element, label_element, columns, fundamental_filters) VALUES ($mapSid,
        '$elementId', '999999', '$handle',
        " . $xoopsDB->quoteString(serialize(array(array('other_col', '', 'Box'), array($handle, '', 'Box')))) . ",
        " . $xoopsDB->quoteString(serialize(array(0 => array($elementId, 'zzz'), 1 => array('=', '='), 2 => array('x', 'y'), 3 => array('all', 'all')))) . ")");

    // a single page form screen showing this element among others, and one showing only this element
    $formSid = $newScreen('zzfixform', 'Reference integrity form', 'form');
    $xoopsDB->queryF("INSERT INTO " . prefix('formulize_screen_form') . " (sid, formelements, elementdefaults)
        VALUES ($formSid, " . $xoopsDB->quoteString(serialize(array(999999, $elementId, 999998))) . ",
        " . $xoopsDB->quoteString(serialize(array(999999 => 'keep', $elementId => 'go'))) . ")");
    $onlySid = $newScreen('zzfixonly', 'Reference integrity only', 'form');
    $xoopsDB->queryF("INSERT INTO " . prefix('formulize_screen_form') . " (sid, formelements, elementdefaults)
        VALUES ($onlySid, " . $xoopsDB->quoteString(serialize(array($elementId))) . ", 'a:0:{}')");

    // a calendar screen placing entries on this element's date
    $dataset = new formulizeCalendarScreenDataset();
    $dataset->setVar('fid', $fixtureFid);
    $dataset->setVar('datehandle', $handle);
    $calSid = $newScreen('zzfixcal', 'Reference integrity calendar', 'calendar');
    $xoopsDB->queryF("INSERT INTO " . prefix('formulize_screen_calendar') . " (sid, caltype, datasets)
        VALUES ($calSid, 'month', " . $xoopsDB->quoteString(serialize(array($dataset))) . ")");

    // a screen naming entries in the address bar by this element
    $addressSid = $newScreen('zzfixaddr', 'Reference integrity address', 'listOfEntries', $elementId);

    // a multipage screen with page conditions in the format that predates the parallel arrays, and one
    // condition that has lost its operator along the way
    $legacySid = $newScreen('zzfixlegacy', 'Reference integrity legacy', 'multiPage');
    $legacyConditions = array(
        0 => array('pagecons' => 'yes', 'details' => array(
            'elements' => array('keep_me', $elementId), 'ops' => array('=', '='), 'terms' => array('a', 'b'))),
        1 => array(0 => array($elementId, 'other'), 1 => array('='), 2 => array('x', 'y'), 3 => array('all', 'all')),
    );
    $xoopsDB->queryF("INSERT INTO " . prefix('formulize_screen_multipage') . "
        (sid, pages, pagetitles, conditions, disabledpages, elementdefaults) VALUES ($legacySid,
        " . $xoopsDB->quoteString(serialize(array(array(999997, $elementId)))) . ", 'a:0:{}',
        " . $xoopsDB->quoteString(serialize($legacyConditions)) . ", 'a:0:{}',
        " . $xoopsDB->quoteString(serialize(array($elementId => 'default', 999997 => 'keep'))) . ")");

    // Another element whose dynamic default value is read out of this one. This is the one place the test
    // borrows a row it did not create - it needs an element that points AT the fixture, and inventing a
    // second element would not exercise anything the first one does not. Its original values are kept so
    // that the rollback can be checked properly at the end rather than taken on trust.
    $otherRow = fetchOne("SELECT ele_id, ele_dynamicdefault_source, ele_dynamicdefault_conditions
        FROM " . prefix('formulize') . " WHERE ele_id != $elementId ORDER BY ele_id DESC LIMIT 1");
    $otherElementId = intval($otherRow['ele_id']);
    $borrowedRowBefore = $otherRow;
    $xoopsDB->queryF("UPDATE " . prefix('formulize') . " SET ele_dynamicdefault_source = $elementId,
        ele_dynamicdefault_conditions = " . $xoopsDB->quoteString(serialize(array(
            0 => array($elementId), 1 => array('='), 2 => array('v'), 3 => array('all')))) . "
        WHERE ele_id = $otherElementId");

    // ---- the report has to mention all of it, before anything is changed ----
    $report = $element_handler->elementUsageReport($fixture);
    $sections = array();
    foreach ($report['cleaned_up_automatically'] as $section) {
        $sections[$section['what']] = $section['items'];
    }
    ok('the report mentions the map screen', isset($sections[_AM_ELE_USAGE_SECTION_MAP_SCREENS]));
    ok('the report mentions the calendar screen', isset($sections[_AM_ELE_USAGE_SECTION_CALENDAR_SCREENS]));
    ok('the report mentions the address bar screen', isset($sections[_AM_ELE_USAGE_SECTION_SCREEN_ADDRESSES]));
    ok('the report mentions the element depending on this one', isset($sections[_AM_ELE_USAGE_SECTION_OTHER_ELEMENTS]));
    check('the report mentions both saved views', count(isset($sections[_AM_ELE_USAGE_SECTION_SAVED_VIEWS]) ? $sections[_AM_ELE_USAGE_SECTION_SAVED_VIEWS] : array()), 2);
    ok('the report warns that a form screen will be left showing nothing',
        isset($sections[_AM_ELE_USAGE_SECTION_FORM_SCREENS])
        && count(preg_grep('/the only element chosen to appear/', $sections[_AM_ELE_USAGE_SECTION_FORM_SCREENS])) === 1);

    // ---- clean up for real, and look at what came out ----
    $element_handler->removeElementReferences($fixture);

    $view = fetchOne("SELECT * FROM " . prefix('formulize_saved_views') . " WHERE sv_id = $viewId");
    check('view: the column is gone', $view['sv_oldcols'], 'first_col,last_col');
    check('view: so is the search that went with it, and the rest still line up', $view['sv_quicksearches'], 'search1&*=%4#search3');
    check('view: the sort falls back to the default order', $view['sv_sort'], '');
    check('view: the calculation column is gone', $view['sv_calc_cols'], '11/22');
    check('view: the calculations stay in step', $view['sv_calc_calcs'], 'sum/count');
    check('view: the blank settings stay in step', $view['sv_calc_blanks'], 'all/all');
    check('view: the grouping stays in step', $view['sv_calc_grouping'], '/');

    $hiddenView = fetchOne("SELECT * FROM " . prefix('formulize_saved_views') . " WHERE sv_id = $hiddenViewId");
    check('view: a hiddencolumn_ prefixed column is recognised and removed', $hiddenView['sv_oldcols'], 'a,b');
    check('view: and its search with it', $hiddenView['sv_quicksearches'], 'sa&*=%4#sb');

    $map = fetchOne("SELECT * FROM " . prefix('formulize_screen_map') . " WHERE sid = $mapSid");
    check('map: the latitude is cleared', $map['lat_element'], '');
    check('map: another element is left alone', $map['lng_element'], '999999');
    check('map: the label is cleared', $map['label_element'], '');
    check('map: the column is removed', unserialize($map['columns']), array(array('other_col', '', 'Box')));
    check('map: the filter is removed', unserialize($map['fundamental_filters']),
        array(0 => array('zzz'), 1 => array('='), 2 => array('y'), 3 => array('all')));

    $formScreen = fetchOne("SELECT * FROM " . prefix('formulize_screen_form') . " WHERE sid = $formSid");
    check('form screen: the element is removed from the chosen list', unserialize($formScreen['formelements']), array(999999, 999998));
    check('form screen: its default value goes with it', unserialize($formScreen['elementdefaults']), array(999999 => 'keep'));
    $onlyScreen = fetchOne("SELECT * FROM " . prefix('formulize_screen_form') . " WHERE sid = $onlySid");
    check('form screen: one showing only this element is left alone, because an empty list means show everything',
        unserialize($onlyScreen['formelements']), array($elementId));

    $calendar = fetchOne("SELECT * FROM " . prefix('formulize_screen_calendar') . " WHERE sid = $calSid");
    $datasets = unserialize($calendar['datasets']);
    ok('calendar: the datasets are still the objects they are stored as', $datasets[0] instanceof formulizeCalendarScreenDataset);
    check('calendar: the date handle is cleared', $datasets[0]->getVar('datehandle'), '');
    check('calendar: the rest of the dataset survives the rewrite', intval($datasets[0]->getVar('fid')), $fixtureFid);

    $address = fetchOne("SELECT rewriteruleElement FROM " . prefix('formulize_screen') . " WHERE sid = $addressSid");
    check('address bar: the screen falls back to the entry id', intval($address['rewriteruleElement']), 0);

    $legacy = fetchOne("SELECT conditions, elementdefaults FROM " . prefix('formulize_screen_multipage') . " WHERE sid = $legacySid");
    $legacyAfter = unserialize($legacy['conditions']);
    check('legacy conditions: the element is dropped and the page keeps its shape', $legacyAfter[0]['details'],
        array('elements' => array('keep_me'), 'ops' => array('='), 'terms' => array('a')));
    check('legacy conditions: the flag beside the details survives', $legacyAfter[0]['pagecons'], 'yes');
    check('current conditions: the element is dropped, and a condition missing its operator gets one',
        $legacyAfter[1], array(0 => array('other'), 1 => array('='), 2 => array('y'), 3 => array('all')));
    check('multipage: the element default is removed', unserialize($legacy['elementdefaults']), array(999997 => 'keep'));

    $dependent = fetchOne("SELECT ele_dynamicdefault_source, ele_dynamicdefault_conditions FROM " . prefix('formulize') . " WHERE ele_id = $otherElementId");
    check('dynamic default: the source is cleared', intval($dependent['ele_dynamicdefault_source']), 0);
    check('dynamic default: its conditions go with it', unserialize($dependent['ele_dynamicdefault_conditions']), array());

    echo "\n";
    echo "=====================================================================\n";
    echo " PART 3 - a multipage page that loses its last element\n";
    echo "=====================================================================\n";

    $pageSid = $newScreen('zzfixpages', 'Reference integrity pages', 'multiPage');
    $xoopsDB->queryF("INSERT INTO " . prefix('formulize_screen_multipage') . "
        (sid, pages, pagetitles, conditions, disabledpages, elementdefaults) VALUES ($pageSid,
        " . $xoopsDB->quoteString(serialize(array(0 => array(999996, $elementId), 1 => array($elementId), 2 => array(999995)))) . ",
        " . $xoopsDB->quoteString(serialize(array(0 => 'keeps', 1 => 'empties', 2 => 'after'))) . ",
        " . $xoopsDB->quoteString(serialize(array(0 => array(), 1 => array(), 2 => array()))) . ",
        " . $xoopsDB->quoteString(serialize(array(0 => 0, 1 => 0, 2 => 1))) . ", 'a:0:{}')");

    $report = $element_handler->elementUsageReport($fixture);
    $saidAboutPages = '';
    foreach ($report['cleaned_up_automatically'] as $section) {
        foreach ($section['items'] as $item) {
            if (strpos($item, 'Reference integrity pages') !== false) {
                $saidAboutPages = $item;
            }
        }
    }
    ok('the report names the page the element shares with others', strpos($saidAboutPages, 'on page 1') !== false, $saidAboutPages);
    ok('the report says what becomes of the page it is alone on',
        strpos($saidAboutPages, 'the only element on page 2, so that page will be empty') !== false, $saidAboutPages);

    // what delete() does, in the order delete() does it
    $screen_handler->removeElementsFromScreens($elementId);
    $element_handler->removeElementReferences($fixture);

    $pageRow = fetchOne("SELECT pages, pagetitles, disabledpages FROM " . prefix('formulize_screen_multipage') . " WHERE sid = $pageSid");
    $pagesAfter = unserialize($pageRow['pages']);
    check('the element is removed from the page it shared', $pagesAfter[0], array(0 => 999996));
    check('the page it was alone on is kept, not deleted', $pagesAfter[1], array());
    check('so the page titles are left as they were', unserialize($pageRow['pagetitles']), array('keeps', 'empties', 'after'));
    check('and so are the read only flags', unserialize($pageRow['disabledpages']), array(0, 0, 1));

    // an empty page must not reach the form, and must not complain on the way past
    $diagnostics = array();
    set_error_handler(function ($number, $string) use (&$diagnostics) {
        $diagnostics[] = $string;
        return true;
    });
    $pageScreen = $screen_handler->get($pageSid);
    list($compiledPages, $compiledTitles) = $screen_handler->traverseScreenPages($pageScreen);
    restore_error_handler();
    check('the form is built from the two pages that still have something on them', count($compiledPages), 2);
    check('and their titles follow', array_values($compiledTitles), array('keeps', 'after'));
    check('and nothing is logged or raised about the empty one', count($diagnostics), 0);

} catch (Throwable $t) {
    $GLOBALS['__fail']++;
    printf("  FAIL fixtures could not be built or checked\n       %s: %s @ %s:%s\n",
        get_class($t), $t->getMessage(), $t->getFile(), $t->getLine());
}

$xoopsDB->queryF("ROLLBACK");

echo "\n";
echo "=====================================================================\n";
echo " the database is as it was\n";
echo "=====================================================================\n";

// If this does not hold then everything above wrote to a real system, so it is checked rather than assumed.
$leftovers = fetchOne("SELECT COUNT(*) AS c FROM " . prefix('formulize') . " WHERE ele_handle = 'zz_reference_integrity_fixture'");
check('the fixture element is gone', intval($leftovers['c']), 0);
$leftovers = fetchOne("SELECT COUNT(*) AS c FROM " . prefix('formulize_screen') . " WHERE screen_handle LIKE 'zzfix%'");
check('the fixture screens are gone', intval($leftovers['c']), 0);
$leftovers = fetchOne("SELECT COUNT(*) AS c FROM " . prefix('formulize_saved_views') . " WHERE sv_name LIKE 'reference integrity%'");
check('the fixture saved views are gone', intval($leftovers['c']), 0);
// The one real row the test writes to. Checking that nothing points at the fixture any more would prove
// nothing here - the cleanup sets that pointer to 0 itself, so it reads as clean whether the rollback
// happened or not. The original values are what has to come back.
if ($borrowedRowBefore) {
    $borrowedRowAfter = fetchOne("SELECT ele_dynamicdefault_source, ele_dynamicdefault_conditions
        FROM " . prefix('formulize') . " WHERE ele_id = " . intval($otherElementId));
    check('the element whose dynamic default was borrowed has its source back',
        (string) $borrowedRowAfter['ele_dynamicdefault_source'], (string) $borrowedRowBefore['ele_dynamicdefault_source']);
    check('and its conditions back',
        (string) $borrowedRowAfter['ele_dynamicdefault_conditions'], (string) $borrowedRowBefore['ele_dynamicdefault_conditions']);
}

echo "\n";
printf("RESULT: %d passed, %d failed\n", $GLOBALS['__pass'], $GLOBALS['__fail']);
exit($GLOBALS['__fail'] === 0 ? 0 : 1);
