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
 *    entry in the address bar, page conditions in the format that predates the current one, and the
 *    element-type settings buried in ele_value - a linked list sorting its options by another element, a
 *    grid taking its initial values from one, a subform showing one. These are built as fixtures, cleaned
 *    up for real, and checked - all inside a transaction that is rolled back, so the database is exactly
 *    as it was when the test finishes. Every fixture, including the elements that point at the one being
 *    deleted, is created by this test, so no row it did not make is ever written to.
 *
 * 3. THE MULTIPAGE PAGE THAT EMPTIES. Deleting the last element on a page leaves the page in place, and
 *    traverseScreenPages() does not carry an empty page through to the form.
 *
 * 4. CODE AND TEMPLATE FILES. Code files, screens' own template files and mail templates that name the element,
 *    in the ways a rename rewrites and the ways it leaves for a person: the usage report names each and says which
 *    a rename would leave, a real rename rewrites them and warns about the same ones, and a cloned screen gets
 *    copies of its template files. The files are written and removed by name, since a rollback cannot take them.
 *
 * 5. RESERVED NAMES. The names Formulize keeps for its own variables in code cannot be element handles.
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
$_SERVER['REQUEST_METHOD'] = 'POST'; // writes reach the database only on POST - see icms_core_Security::service(), which defines XOOPS_DB_PROXY on anything else and makes query() refuse to write
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
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/selectElement.php'; // for the ELE_VALUE_SELECT_* keys

global $xoopsDB;
$element_handler = xoops_getmodulehandler('elements', 'formulize');
$screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
if (!$element_handler) {
    fwrite(STDERR, "Could not load the elements handler - the module bootstrap did not complete.\n");
    exit(2);
}

// The references themselves, as the report and the cleanup both read them, because what this needs to check is
// the change that would be written, which neither of those two exposes - the report describes it in words, and
// the cleanup applies it and moves on.
$referencesTo = function ($elementObject) {
    return formulizeElementReferences::forElement($elementObject)->references();
};

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

// What the configuration-as-code system says each element depends on, asked once. The scan uses the same
// routine to decide what counts as a reference, and this is here so that it goes on doing so: if anyone
// ever gives the scan its own idea of what a reference is, the two drift apart silently, and the way that
// shows up is a deletion quietly leaving something behind. Built from the same properties the scan reads.
$dependenciesOfElement = array();
$dependencyResult = $xoopsDB->query("SELECT ele_id, ele_type, ele_filtersettings, ele_disabledconditions,
    ele_dynamicdefault_conditions, ele_dynamicdefault_source, ele_value FROM " . prefix('formulize'));
while ($dependencyRow = $xoopsDB->fetchArray($dependencyResult)) {
    $id = intval($dependencyRow['ele_id']);
    unset($dependencyRow['ele_id']);
    try {
        $dependenciesOfElement[$id] = $element_handler->getElementDependencies($dependencyRow);
    } catch (Throwable $t) {
        $dependenciesOfElement[$id] = array(); // an element naming something that no longer exists
    }
}

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

    $references = $referencesTo($elementObject);
    if ($references) {
        $elementsWithReferences++;
    }
    $totalReferences += count($references);

    // Everything the configuration-as-code system believes refers to this element has to turn up in the
    // report. Not necessarily as something that gets rewritten - some references are reported and left for
    // a person - but it must be named, because being told is the whole point.
    $reportedElementIds = array();
    foreach ($references as $reference) {
        if ($reference['section'] === _AM_ELE_USAGE_SECTION_OTHER_ELEMENTS) {
            $reportedElementIds[intval($reference['key_value'])] = true;
        }
    }
    foreach ($dependenciesOfElement as $otherId => $handles) {
        if ($otherId === $elementId || !in_array($report['element_handle'], $handles)) {
            continue;
        }
        ok("element $elementId: element $otherId depends on it, so the report says so",
            isset($reportedElementIds[$otherId]),
            'getElementDependencies() names it, the usage report does not');
    }

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

// Declared out here so the checks at the end can see them even if the fixtures throw part way through.
$otherElementId = 0;
$elementId = 0;
// files PART 4 writes, which the rollback cannot take away, so they are removed by name at the end
$fixtureTemplateRoot = XOOPS_ROOT_PATH . '/modules/formulize/templates/screens/zz_fixture_theme/';
$fixtureMailTemplate = XOOPS_ROOT_PATH . '/modules/formulize/language/english/mail_template/zz_fixture_reference.tpl';
$codeDir = XOOPS_ROOT_PATH . '/modules/formulize/code/';
$fixtureCodeFiles = array(
    XOOPS_ROOT_PATH . '/modules/formulize/code/derived_zz_fixture_reference_formula.php',
    XOOPS_ROOT_PATH . '/modules/formulize/code/zz_fixture_reference_code.php',
);

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

    // Elements of other types whose own settings point at this one. These references live inside ele_value,
    // whose meaning is type specific: each type declares its keys in a static $eleValueReferences on its
    // handler - nothing outside the type knows that key 12 of a linked list is a single element you blank
    // while key 10 is a list you splice. PART 1 checks those declarations still find everything
    // getElementDependencies() does.
    $newElement = function ($type, $handle, $eleValue) use ($xoopsDB, $fixtureFid) {
        $xoopsDB->queryF("INSERT INTO " . prefix('formulize') . "
            (id_form, ele_type, ele_caption, ele_handle, ele_order, ele_display, ele_value)
            VALUES ($fixtureFid, '$type', '$handle', '$handle', 9999, 1, " . $xoopsDB->quoteString(serialize($eleValue)) . ")");
        return intval($xoopsDB->getInsertId());
    };

    $linkedId = $newElement('selectLinked', 'zz_fixture_linked', array(
        ELE_VALUE_SELECT_LINK_SORT => $elementId,                           // a single reference, cleared
        ELE_VALUE_SELECT_LINK_ALTLISTELEMENTS => array(999991, $elementId),  // a list, the entry removed
        ELE_VALUE_SELECT_LINK_SOURCEMAPPINGS => array(                       // the pair naming it goes, a typed value stays
            array('thisForm' => $elementId, 'sourceForm' => 999990),
            array('thisForm' => '{' . $handle . '}', 'sourceForm' => 999989),
        ),
    ));
    $gridId = $newElement('grid', 'zz_fixture_grid', array(4 => $elementId));
    $subformId = $newElement('subformListings', 'zz_fixture_subform', array(
        1 => '999992,' . $elementId, // stored as a comma separated string rather than an array
        7 => array(0 => array($elementId, 'keep_me'), 1 => array('=', '='), 2 => array('a', 'b'), 3 => array('all', 'all')),
        'SortingElement' => $elementId,
    ));

    // another element whose dynamic default value is read out of this one
    $otherElementId = $newElement('text', 'zz_fixture_dependent', array());
    $xoopsDB->queryF("UPDATE " . prefix('formulize') . " SET ele_dynamicdefault_source = $elementId,
        ele_dynamicdefault_conditions = " . $xoopsDB->quoteString(serialize(array(
            0 => array($elementId), 1 => array('='), 2 => array('v'), 3 => array('all')))) . "
        WHERE ele_id = $otherElementId");

    // Written out by hand rather than from the declaration, so that a reference the declaration misses fails
    // here: these are the places a review of the declaration found missing from it.
    // A view sorted by two columns, and offering this element's search box.
    $xoopsDB->queryF("INSERT INTO " . prefix('formulize_saved_views') . "
        (sv_name, sv_formframe, sv_mainform, sv_oldcols, sv_quicksearches, sv_sort, sv_order, sv_pubfilters)
        VALUES ('reference integrity sort fixture', $fixtureFid, 0, 'x', '', 'x,$elementId', 'SORT_ASC,SORT_DESC', '$elementId,x')");
    $sortViewId = intval($xoopsDB->getInsertId());
    // an advanced calculation with a filter tied to this element, and a step querying its column by name
    $xoopsDB->queryF("INSERT INTO " . prefix('formulize_advanced_calculations') . "
        (fid, name, description, input, output, steps, steptitles, fltr_grps, fltr_grptitles) VALUES ($fixtureFid,
        'reference integrity calculation', '', '', '', " . $xoopsDB->quoteString(serialize(array(array('description' => '', 'sql' => "SELECT SUM($handle) FROM t", 'preCalculate' => '', 'calculate' => '', 'postCalculate' => '')))) . ", 'a:0:{}',
        " . $xoopsDB->quoteString(serialize(array(0 => array('form' => (string) $elementId, 'handle' => 'f1'), 1 => array('form' => '999999', 'handle' => 'f2')))) . ", 'a:0:{}')");
    $calculationId = intval($xoopsDB->getInsertId());
    // a multipage screen with a custom page of PHP naming this element
    $customPageSid = $newScreen('zzfixcustom', 'Reference integrity custom page', 'multiPage');
    $xoopsDB->queryF("INSERT INTO " . prefix('formulize_screen_multipage') . "
        (sid, pages, pagetitles, conditions, disabledpages, elementdefaults) VALUES ($customPageSid,
        " . $xoopsDB->quoteString(serialize(array(array('PHP', 'print $' . $handle . ';')))) . ", 'a:0:{}', 'a:0:{}', 'a:0:{}', 'a:0:{}')");
    // a grid whose row caption shows this element's value
    $captionGridId = $newElement('grid', 'zz_fixture_caption_grid', array(1 => 'Row {' . $handle . '}', 2 => 'Column', 4 => 'none'));

    // ---- the report has to mention all of it, before anything is changed ----
    $report = $element_handler->elementUsageReport($fixture);
    $inCode = isset($report['references_in_code_that_you_will_need_to_fix']) ? $report['references_in_code_that_you_will_need_to_fix'] : array();
    ok('the report names the custom page that uses it', count(preg_grep('/Reference integrity custom page/', $inCode)) === 1, implode(' | ', $inCode));
    ok('the report names the grid caption that shows it', count(preg_grep('/zz_fixture_caption_grid/', $inCode)) === 1, implode(' | ', $inCode));
    ok('the report names the calculation step that queries it', count(preg_grep('/reference integrity calculation/', $inCode)) === 1, implode(' | ', $inCode));
    $sections = array();
    foreach ($report['cleaned_up_automatically'] as $section) {
        $sections[$section['what']] = $section['items'];
    }
    ok('the report mentions the map screen', isset($sections[_AM_ELE_USAGE_SECTION_MAP_SCREENS]));
    ok('the report mentions the calendar screen', isset($sections[_AM_ELE_USAGE_SECTION_CALENDAR_SCREENS]));
    ok('the report mentions the address bar screen', isset($sections[_AM_ELE_USAGE_SECTION_SCREEN_ADDRESSES]));
    ok('the report mentions the element depending on this one', isset($sections[_AM_ELE_USAGE_SECTION_OTHER_ELEMENTS]));
    $otherElements = isset($sections[_AM_ELE_USAGE_SECTION_OTHER_ELEMENTS]) ? $sections[_AM_ELE_USAGE_SECTION_OTHER_ELEMENTS] : array();
    ok('the report mentions the linked list that sorts by it',
        count(preg_grep('/zz_fixture_linked.*sorted by/', $otherElements)) === 1, implode(' | ', $otherElements));
    ok('the report mentions the grid that takes its initial values from it',
        count(preg_grep('/zz_fixture_grid.*initial values/', $otherElements)) === 1, implode(' | ', $otherElements));
    ok('the report mentions the subform that shows it',
        count(preg_grep('/zz_fixture_subform.*one of the elements it shows/', $otherElements)) === 1, implode(' | ', $otherElements));
    ok('the report says the source mapping is removed',
        count(preg_grep('/a source mapping, which is removed/', $otherElements)) === 1, implode(' | ', $otherElements));
    check('the report mentions all three saved views', count(isset($sections[_AM_ELE_USAGE_SECTION_SAVED_VIEWS]) ? $sections[_AM_ELE_USAGE_SECTION_SAVED_VIEWS] : array()), 3);
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

    $sortView = fetchOne("SELECT * FROM " . prefix('formulize_saved_views') . " WHERE sv_id = $sortViewId");
    check('view: of the two columns it sorts by, this one goes, and its direction with it', $sortView['sv_sort'] . '|' . $sortView['sv_order'], 'x|SORT_ASC');
    check('view: the search box it offered for this element goes', $sortView['sv_pubfilters'], 'x');
    $calculation = fetchOne("SELECT fltr_grps FROM " . prefix('formulize_advanced_calculations') . " WHERE acid = $calculationId");
    check('calculation: the filter tied to this element is untied, the other is left', unserialize($calculation['fltr_grps']),
        array(0 => array('form' => 'none', 'handle' => 'f1'), 1 => array('form' => '999999', 'handle' => 'f2')));

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

    $eleValueOf = function ($id) {
        $row = fetchOne("SELECT ele_value FROM " . prefix('formulize') . " WHERE ele_id = " . intval($id));
        return unserialize($row['ele_value']);
    };

    $linked = $eleValueOf($linkedId);
    check('linked list: the element it sorted by is set to none, the value the editor writes for nothing chosen',
        $linked[ELE_VALUE_SELECT_LINK_SORT], 'none');
    check('linked list: it is taken out of the alternate list elements, and the other one is left',
        $linked[ELE_VALUE_SELECT_LINK_ALTLISTELEMENTS], array(999991));
    check('linked list: the mapping naming this element goes as a whole, and a typed value that looks like its handle stays',
        $linked[ELE_VALUE_SELECT_LINK_SOURCEMAPPINGS],
        array(1 => array('thisForm' => '{' . $handle . '}', 'sourceForm' => 999989)));

    $grid = $eleValueOf($gridId);
    check('grid: the element it took its initial values from is cleared', $grid[4], 'none');

    $subform = $eleValueOf($subformId);
    check('subform: removed from the comma separated list of elements it shows, still a comma separated string',
        $subform[1], '999992');
    check('subform: taken out of the conditions deciding which entries it shows',
        $subform[7], array(0 => array('keep_me'), 1 => array('='), 2 => array('b'), 3 => array('all')));
    check('subform: the element it sorted by is cleared', $subform['SortingElement'], 'none');

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

    echo "\n";
    echo "=====================================================================\n";
    echo " PART 4 - code files, screen template files and mail templates, reported and renamed\n";
    echo "=====================================================================\n";

    // A template screen, a list screen and a multipage screen with template files of their own, a template screen
    // on a form that cannot see this element, two code files and a mail template. Each names the element both in
    // the ways a rename rewrites and in ways it must leave for a person, beside a longer handle that starts with
    // this one, and several already use a variable called by the new handle. The files are removed again below,
    // whatever happens.
    $newHandle = 'zz_renamed_fixture';
    $H = $handle;
    $N = $newHandle;
    $templateSid = $newScreen('zzfixtpl', 'Reference integrity template screen', 'template');
    $listSid = $newScreen('zzfixlist', 'Reference integrity list templates', 'listOfEntries');
    $formSid = $newScreen('zzfixform', 'Reference integrity form templates', 'multiPage');
    $xoopsDB->queryF("INSERT INTO " . prefix('formulize_screen_template') . " (sid, donedest, savebuttontext, donebuttontext, viewentryscreen)
        VALUES ($templateSid, '', '', '', '')");
    $otherFormRow = fetchOne("SELECT id_form FROM " . prefix('formulize_id') . " WHERE id_form != $fixtureFid ORDER BY id_form ASC LIMIT 1");
    $otherFid = $otherFormRow ? intval($otherFormRow['id_form']) : 0;
    $xoopsDB->queryF("INSERT INTO " . prefix('formulize_screen') . " (screen_handle, title, fid, frid, type, rewriteruleElement)
        VALUES ('zzfixothertpl', 'Reference integrity other template screen', $otherFid, 0, 'template', 0)");
    $otherTemplateSid = intval($xoopsDB->getInsertId());

    // path => what it holds, what a rename makes of it, and whether a rename leaves some of it for a person
    $files = array(
        // the screen's variables are renamed, and a $N of the code's own moves out of the way, the same in both
        $fixtureTemplateRoot . $templateSid . '/code.php' => array(
            '<?php $total = $' . $H . ' + $' . $H . '_era + $' . $N . '; print display($entry, \'' . $H . '\'); $row = $templateScreenData[\'' . $H . '\'];',
            '<?php $total = $' . $N . ' + $' . $H . '_era + $' . $N . '_1; print display($entry, \'' . $N . '\'); $row = $templateScreenData[\'' . $H . '\'];',
            true),
        $fixtureTemplateRoot . $templateSid . '/template.html' => array(
            '{$' . $H . '} and {$' . $H . '_era} and {$' . $N . '}',
            '{$' . $N . '} and {$' . $H . '_era} and {$' . $N . '_1}',
            false),
        // the search box variables and a filter term are renamed, a variable of the template's own is not
        $fixtureTemplateRoot . $listSid . '/toptemplate.php' => array(
            '<?php print $quickSearch' . $H . '; print $quickDateRange' . $H . '; print $quickFilter' . $H . '; print $' . $H . '; print $quickSearch' . $H . '_era; print getData("", 1, "' . $H . '/**/x/**/=");',
            '<?php print $quickSearch' . $N . '; print $quickDateRange' . $N . '; print $quickFilter' . $N . '; print $' . $H . '; print $quickSearch' . $H . '_era; print getData("", 1, "' . $N . '/**/x/**/=");',
            true),
        // a screen that gets no variables: only what can only be the element
        $fixtureTemplateRoot . $formSid . '/elementtemplate1.php' => array(
            '<?php print displayElement(\'\', \'' . $H . '\'); print $entry[\'' . $H . '\'];',
            '<?php print displayElement(\'\', \'' . $N . '\'); print $entry[\'' . $H . '\'];',
            true),
        // a template screen on a form that cannot see this element: its variable of the same name is its own
        $fixtureTemplateRoot . $otherTemplateSid . '/code.php' => array(
            '<?php $' . $H . ' = 1; print display($entry, \'' . $H . '\');',
            '<?php $' . $H . ' = 1; print display($entry, \'' . $N . '\');',
            true),
        // a formula, whose variables are renamed, and whose own $N moves out of the way
        $fixtureCodeFiles[0] => array(
            '$value = $' . $H . ' + $' . $N . '; $x = display($entry, \'' . $H . '\');',
            '$value = $' . $N . ' + $' . $N . '_1; $x = display($entry, \'' . $N . '\');',
            false),
        // code that gets no variables: the string given to getValue() only, not its own variable or a column in a query
        $fixtureCodeFiles[1] => array(
            '<?php $' . $H . ' = 5; print getValue($e, "' . $H . '"); $q = "SELECT `' . $H . '` FROM t";',
            '<?php $' . $H . ' = 5; print getValue($e, "' . $N . '"); $q = "SELECT `' . $H . '` FROM t";',
            true),
    );
    foreach ($files as $path => $file) {
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, $file[0]);
    }
    $upper = strtoupper($handle);
    $mailTemplate = 'Hello {ELEMENT_' . $upper . '} {ELEMENT' . $upper . '} {REVISION_ELEMENT_' . $upper . '} {ELEMENT_' . $upper . '_ERA}';
    file_put_contents($fixtureMailTemplate, $mailTemplate);
    $shortName = function ($path) {
        return str_replace(array(XOOPS_ROOT_PATH . '/modules/formulize/templates/screens/', XOOPS_ROOT_PATH . '/modules/formulize/code/'), '', $path);
    };
    $namedIn = function ($path, $list) use ($shortName) {
        return preg_grep('/' . preg_quote("'" . $shortName($path) . "'", '/') . '/', $list);
    };

    $report = $element_handler->elementUsageReport($fixture);
    $inCode = isset($report['references_in_code_that_you_will_need_to_fix']) ? $report['references_in_code_that_you_will_need_to_fix'] : array();
    $left = formulizeElementHandleCode::LEFT_BY_A_RENAME;
    foreach ($files as $path => $file) {
        $named = $namedIn($path, $inCode);
        ok('the report names ' . $shortName($path) . ($file[2] ? ', as left by a rename' : ', as updated by a rename'),
            count($named) === 1 AND (substr(reset($named), -strlen($left)) === $left) === $file[2], implode(' | ', $inCode));
    }
    ok('the report names the mail template', count(preg_grep('/zz_fixture_reference\.tpl/', $inCode)) === 1, implode(' | ', $inCode));

    $fixture->setVar('ele_handle', $newHandle);
    $leftByTheRename = $element_handler->renameElementResources($fixture, $handle);
    foreach ($files as $path => $file) {
        check('renamed: ' . $shortName($path), file_get_contents($path), $file[1]);
        ok('the rename ' . ($file[2] ? 'warns about ' : 'does not warn about ') . $shortName($path),
            (count($namedIn($path, $leftByTheRename)) === 1) === $file[2], implode(' | ', $leftByTheRename));
    }
    $newUpper = strtoupper($newHandle);
    check('mail template: every form of the tag is renamed, the longer one is not', file_get_contents($fixtureMailTemplate),
        'Hello {ELEMENT_' . $newUpper . '} {ELEMENT' . $newUpper . '} {REVISION_ELEMENT_' . $newUpper . '} {ELEMENT_' . $upper . '_ERA}');

    // a clone of a screen has its own copies of the template files, a handle of its own, and no address
    $xoopsDB->queryF("UPDATE " . prefix('formulize_screen') . " SET rewriteruleAddress = 'zzfix-address' WHERE sid = $templateSid");
    xoops_getmodulehandler('templateScreen', 'formulize')->cloneScreen($templateSid);
    $cloneRow = fetchOne("SELECT MAX(sid) AS sid FROM " . prefix('formulize_screen') . " WHERE title LIKE 'Reference integrity template screen%'");
    $cloneSid = intval($cloneRow['sid']);
    ok('a template screen is cloned', $cloneSid > $templateSid);
    check('the clone has a copy of the code', @file_get_contents($fixtureTemplateRoot . $cloneSid . '/code.php'), $files[$fixtureTemplateRoot . $templateSid . '/code.php'][1]);
    check('and of the template', @file_get_contents($fixtureTemplateRoot . $cloneSid . '/template.html'), $files[$fixtureTemplateRoot . $templateSid . '/template.html'][1]);
    $clone = fetchOne("SELECT screen_handle, rewriteruleAddress FROM " . prefix('formulize_screen') . " WHERE sid = $cloneSid");
    ok('the clone has a handle of its own, made from its title', $clone['screen_handle'] !== 'zzfixtpl' AND strpos($clone['screen_handle'], 'reference_integrity_template_screen') === 0, $clone['screen_handle']);
    $sameHandle = fetchOne("SELECT COUNT(*) AS c FROM " . prefix('formulize_screen') . " WHERE screen_handle = " . $xoopsDB->quoteString($clone['screen_handle']));
    check('and no other screen has it', intval($sameHandle['c']), 1);
    check('the clone has no address, which stays the original\'s', $clone['rewriteruleAddress'], '');

    // a clone of a list has its own copies of the code its custom buttons keep in files, under its own handle
    $realList = fetchOne("SELECT * FROM " . prefix('formulize_screen_listofentries') . " ORDER BY sid LIMIT 1");
    unset($realList['listofentriesid']);
    $realList['sid'] = $listSid;
    $realList['customactions'] = serialize(array(1 => array('handle' => 'zzfixbtn', 'applyto' => 'custom_code', 0 => array('code' => ''))));
    $xoopsDB->queryF("INSERT INTO " . prefix('formulize_screen_listofentries') . " (`" . implode('`, `', array_keys($realList)) . "`) VALUES ("
        . implode(', ', array_map(array($xoopsDB, 'quoteString'), array_values($realList))) . ")");
    $buttonCode = '<?php print "zz fixture button";';
    $fixtureCodeFiles[] = $codeDir . 'custom_code_0_zzfixbtn_zzfixlist.php';
    file_put_contents($codeDir . 'custom_code_0_zzfixbtn_zzfixlist.php', $buttonCode);
    xoops_getmodulehandler('listOfEntriesScreen', 'formulize')->cloneScreen($listSid);
    $listClone = fetchOne("SELECT screen_handle FROM " . prefix('formulize_screen') . " WHERE sid = (SELECT MAX(sid) FROM " . prefix('formulize_screen') . " WHERE title LIKE 'Reference integrity list templates%')");
    $fixtureCodeFiles[] = $codeDir . 'custom_code_0_zzfixbtn_' . $listClone['screen_handle'] . '.php';
    ok('a list is cloned with a handle of its own', $listClone AND $listClone['screen_handle'] !== 'zzfixlist', $listClone ? $listClone['screen_handle'] : 'no clone');
    check('and its button\'s code is copied under it', @file_get_contents($codeDir . 'custom_code_0_zzfixbtn_' . $listClone['screen_handle'] . '.php'), $buttonCode);
    check('and the original keeps its own', file_get_contents($codeDir . 'custom_code_0_zzfixbtn_zzfixlist.php'), $buttonCode);
    // and a list whose handle changes takes its button's code with it
    $fixtureCodeFiles[] = $codeDir . 'custom_code_0_zzfixbtn_zzfixlist_renamed.php';
    $list_screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
    $fixtureList = $list_screen_handler->get($listSid);
    $fixtureList->setVar('screen_handle', 'zzfixlist_renamed');
    ok('a list can be given a new handle', (bool) $list_screen_handler->insert($fixtureList));
    check('its button\'s code moves to the file named for the new handle', @file_get_contents($codeDir . 'custom_code_0_zzfixbtn_zzfixlist_renamed.php'), $buttonCode);
    check('and is not left under the old one', file_exists($codeDir . 'custom_code_0_zzfixbtn_zzfixlist.php'), false);

    echo "\n";
    echo "=====================================================================\n";
    echo " PART 5 - names Formulize keeps for its own variables are not handles\n";
    echo "=====================================================================\n";

    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    foreach (array('value', 'entry', 'entry_id', 'form_id', 'i', 'code_filename', 'this') as $reserved) {
        check("'$reserved' is not available as a handle", $form_handler->isElementHandleUnique($reserved, '', $fixtureFid), false);
    }
    check('a handle that is not kept is available', $form_handler->isElementHandleUnique('zz_fixture_not_kept', '', $fixtureFid), true);
    ok('asking for a kept name gives a handle with a suffix instead, as a name already taken does',
        formulizeHandler::enforceUniqueElementHandles('value', $elementId, $fixtureFid) !== 'value');

} catch (Throwable $t) {
    $GLOBALS['__fail']++;
    printf("  FAIL fixtures could not be built or checked\n       %s: %s @ %s:%s\n",
        get_class($t), $t->getMessage(), $t->getFile(), $t->getLine());
}

$xoopsDB->queryF("ROLLBACK");
foreach ((array) glob($fixtureTemplateRoot . '*/*') as $fixtureFile) {
    @unlink($fixtureFile);
}
foreach ((array) glob($fixtureTemplateRoot . '*', GLOB_ONLYDIR) as $fixtureDir) {
    @rmdir($fixtureDir);
}
@rmdir($fixtureTemplateRoot);
@unlink($fixtureMailTemplate);
foreach ($fixtureCodeFiles as $fixtureCodeFile) {
    @unlink($fixtureCodeFile);
}

echo "\n";
echo "=====================================================================\n";
echo " the database is as it was\n";
echo "=====================================================================\n";

// If this does not hold then everything above wrote to a real system, so it is checked rather than assumed.
$leftovers = fetchOne("SELECT COUNT(*) AS c FROM " . prefix('formulize') . " WHERE ele_handle LIKE 'zz_fixture%' OR ele_handle = 'zz_reference_integrity_fixture'");
check('the fixture elements are gone', intval($leftovers['c']), 0);
$leftovers = fetchOne("SELECT COUNT(*) AS c FROM " . prefix('formulize_screen') . " WHERE screen_handle LIKE 'zzfix%' OR title LIKE 'Reference integrity%'");
check('the fixture screens are gone', intval($leftovers['c']), 0);
$leftovers = fetchOne("SELECT COUNT(*) AS c FROM " . prefix('formulize_saved_views') . " WHERE sv_name LIKE 'reference integrity%'");
check('the fixture saved views are gone', intval($leftovers['c']), 0);
check('the fixture template and code files are gone', is_dir($fixtureTemplateRoot) OR file_exists($fixtureMailTemplate) OR count(array_filter($fixtureCodeFiles, 'file_exists')) > 0, false);
// Nothing above wrote to a row this test did not create, so there is no borrowed state to restore - the
// three checks above cover it. Anything still pointing at the fixture element would mean both that the
// rollback did not take and that a real row had been reached, so it is worth asking even so.
$leftovers = fetchOne("SELECT COUNT(*) AS c FROM " . prefix('formulize') . "
    WHERE ele_dynamicdefault_source = " . intval($elementId));
check('nothing anywhere is left pointing at the fixture element', intval($leftovers['c']), 0);

echo "\n";
printf("RESULT: %d passed, %d failed\n", $GLOBALS['__pass'], $GLOBALS['__fail']);
exit($GLOBALS['__fail'] === 0 ? 0 : 1);
