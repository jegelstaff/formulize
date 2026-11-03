<?php
/**
 * Integrity tests for the SQL that conditions become, against the built museum system.
 *
 * Run after the e2e suite, once the setup specs have built the museum system:
 *   php tests/condition_filters_test.php
 *
 * WHAT THIS COVERS
 * Conditions - a subform's filter, a linked element's options filter, dynamic defaults, a list's fundamental filters - are
 * turned into SQL by buildConditionsFilterSQL(), and the same conditions are checked against an entry's values by
 * checkConditionsAgainstAnEntry() when elements are displayed. Each scenario here is a set of conditions on one of the museum
 * forms. The test builds its SQL, runs it, and compares the entries found with the entries an independently written query
 * finds. The independent query treats NULL and '' as the same - a blank field - which is what conditions mean, and what the
 * display side sees, since it compares values from a dataset, where both are ''. The scenarios combine "match all" and
 * "match one or more" conditions, and cover:
 *
 *   - negations (!=, NOT LIKE), which must include blank fields, alone and in combination, on one field and several
 *   - {BLANK}, and empty terms, which mean the same, including on numeric fields, where 0 is not blank
 *   - linked elements, single and multi value: matches, negations (no linked value matches), and entry ids
 *   - non-linked checkboxes, Yes/No terms, dates written out, and whole years and months, which are ranges of days
 *   - { } references to a field in another entry, blank or not, and to a field in a new entry, which has no value yet
 *   - asynchronous values, the way conditional elements pass them in
 *   - metadata fields
 *
 * Every scenario is checked three ways:
 *   1. The SQL for the conditions finds the same entries as the independent query. All the scenarios are built in order, and
 *      then again in reverse order in the same request, so a condition's SQL cannot depend on what was built before it.
 *   2. The same conditions in extraction mode - the way the extraction layer builds fundamental filters - find the same entries.
 *   3. checkConditionsAgainstAnEntry() passes the same entries, so a condition means the same thing when it filters entries
 *      as when it decides whether an element is displayed.
 * Checks 2 and 3 skip scenarios with { } references, asynchronous values or a new entry, because those depend on the
 * context of one particular entry. Check 3 skips the one known difference: a numeric term on a linked element is an entry
 * id in SQL, but the display side compares the linked entry's text.
 *
 * It asserts that the two ways of finding entries agree, not counts, so adding entries to the setup suite does not break
 * it. A scenario meant to tell entries apart fails if the independent query finds every entry or none, because then it no
 * longer tests anything, and the museum data it relied on has changed.
 *
 * Read-only: the request is a GET, so the database layer refuses any write.
 *
 * @package Formulize
 * @subpackage tests
 */

// Command-line / CI only. A web request just 404s - this ships in the repo and must never be a live endpoint.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SERVER['REQUEST_METHOD'] = 'GET'; // read only: on anything but POST, icms_core_Security::service() makes query() refuse to write
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTP_USER_AGENT'] = 'condition_filters_test';

$root = dirname(__DIR__);
if (!is_file($root . '/mainfile.php')) {
    fwrite(STDERR, "Cannot find mainfile.php at: $root/mainfile.php - is Formulize installed?\n");
    exit(2);
}
include $root . '/mainfile.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';

global $xoopsDB;
$element_handler = xoops_getmodulehandler('elements', 'formulize');
$form_handler = xoops_getmodulehandler('forms', 'formulize');
if (!$element_handler OR !$form_handler) {
    fwrite(STDERR, "Could not load the elements or forms handlers - the module bootstrap did not complete.\n");
    exit(2);
}

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

/**
 * The entry ids a query finds, sorted, as a comma separated string, or the SQL error
 */
function entryIdsFor($sql) {
    global $xoopsDB;
    if (!$res = $xoopsDB->query($sql)) {
        return 'SQL ERROR: ' . $xoopsDB->error() . ' IN: ' . $sql;
    }
    $ids = array();
    while ($row = $xoopsDB->fetchRow($res)) {
        $ids[] = intval($row[0]);
    }
    $ids = array_unique($ids);
    sort($ids, SORT_NUMERIC);
    return implode(',', $ids);
}

/**
 * The entry id of the one entry in a museum form with a given value in a field, looked up rather than assumed
 */
function museumEntryId($formHandle, $field, $value) {
    global $xoopsDB;
    $sql = "SELECT entry_id FROM " . $xoopsDB->prefix('formulize_' . $formHandle) . " WHERE `$field` = '" . formulize_db_escape($value) . "'";
    $res = $xoopsDB->query($sql);
    $row = $res ? $xoopsDB->fetchRow($res) : false;
    if (!$row) {
        fwrite(STDERR, "The museum system has no $formHandle entry with $field '$value' - have the setup specs run?\n");
        exit(2);
    }
    return intval($row[0]);
}

// The museum entries the scenarios rely on, by name
$freeform = museumEntryId('donors', 'donors_name', 'Freeform Solutions'); // an organization, so no first or last name
$voltaire = museumEntryId('donors', 'donors_first_name', 'François-Marie');
$romanCoin = museumEntryId('artifacts', 'artifacts_short_name', 'Roman Coin');
$weapons = museumEntryId('collections', 'collections_name', 'Weapons');
$coins = museumEntryId('collections', 'collections_name', 'Coins');
// the uid of whoever made the Freeform Solutions entry, for the metadata scenarios
$freeformCreator = intval($xoopsDB->fetchRow($xoopsDB->query("SELECT creation_uid FROM " . $xoopsDB->prefix('formulize_donors') . " WHERE entry_id = $freeform"))[0]);

$artifactsTable = $xoopsDB->prefix('formulize_artifacts');
$comments = "COALESCE(t1.surveys_staff_comments,'')";
$firstName = "COALESCE(t1.donors_first_name,'')";
$lastName = "COALESCE(t1.donors_last_name,'')";
$email = "COALESCE(t1.donors_email,'')";
$anonymous = "COALESCE(t1.donors_anonymous,'')";
$audience = "COALESCE(t1.collections_suitable_audience,'')";
$donor = "COALESCE(t1.artifacts_donor,0)";
$collections = "COALESCE(t1.artifacts_collections,'')";
$latitude = "COALESCE(t1.artifacts_latitude,'')";
$acquired = "t1.artifacts_date_of_acquisition";
$hasAudience = function($item) use ($audience) { return "($audience LIKE '%*=+*:$item*=+*:%' OR $audience LIKE '%*=+*:$item')"; };
$inCollection = function($id) use ($collections) { return "$collections LIKE '%,$id,%'"; };

/**
 * The scenarios. Each is:
 *   label, form handle, conditions as array(element handle or metadata field, operator, term, all|oom),
 *   the expected WHERE clause on alias t1 with NULL and '' treated as the same,
 *   and options: entry (the entry { } references are resolved in, or 'new'), async (handle, database value, readable value),
 *   trivial (true if every entry or none is a right answer), displayGap (true to skip the display side check)
 */
$scenarios = array(
    // negations, alone and together
    array("all: comments != 'Great Job', flagged = Yes", 'surveys',
        array(array('surveys_staff_comments', 'NOT', 'Great Job', 'all'), array('surveys_flagged_to_the_staff', '=', 'Yes', 'all')),
        "$comments != 'Great Job' AND t1.surveys_flagged_to_the_staff = 1"),
    array("all: comments NOT LIKE Great, comments NOT LIKE 'Can we'", 'surveys',
        array(array('surveys_staff_comments', 'NOT LIKE', 'Great', 'all'), array('surveys_staff_comments', 'NOT LIKE', 'Can we', 'all')),
        "$comments NOT LIKE '%Great%' AND $comments NOT LIKE '%Can we%'"),
    array("oom: comments != a OR comments != b", 'surveys',
        array(array('surveys_staff_comments', 'NOT', 'a', 'oom'), array('surveys_staff_comments', 'NOT', 'b', 'oom')),
        "$comments != 'a' OR $comments != 'b'", array('trivial' => true)),
    array("oom: comments = zzz OR comments != yyy", 'surveys',
        array(array('surveys_staff_comments', '=', 'zzz', 'oom'), array('surveys_staff_comments', 'NOT', 'yyy', 'oom')),
        "$comments = 'zzz' OR $comments != 'yyy'", array('trivial' => true)),
    array("all: first name != Emilie, email != nobody, anonymous != Yes", 'donors',
        array(array('donors_first_name', 'NOT', 'Emilie', 'all'), array('donors_email', 'NOT', 'nobody', 'all'), array('donors_anonymous', 'NOT', 'Yes', 'all')),
        "$firstName != 'Emilie' AND $email != 'nobody' AND $anonymous != 'Yes'"),
    array("all: first name != Emilie, anonymous != Yes, email = formulize@freeform.ca", 'donors',
        array(array('donors_first_name', 'NOT', 'Emilie', 'all'), array('donors_anonymous', 'NOT', 'Yes', 'all'), array('donors_email', '=', 'formulize@freeform.ca', 'all')),
        "$firstName != 'Emilie' AND $anonymous != 'Yes' AND t1.donors_email = 'formulize@freeform.ca'"),
    array("oom: first name != Emilie OR first name != François-Marie", 'donors',
        array(array('donors_first_name', 'NOT', 'Emilie', 'oom'), array('donors_first_name', 'NOT', 'François-Marie', 'oom')),
        "$firstName != 'Emilie' OR $firstName != 'François-Marie'", array('trivial' => true)),
    array("all: first name != Emilie, first name != François-Marie", 'donors',
        array(array('donors_first_name', 'NOT', 'Emilie', 'all'), array('donors_first_name', 'NOT', 'François-Marie', 'all')),
        "$firstName != 'Emilie' AND $firstName != 'François-Marie'"),
    array("numeric: latitude != 0", 'artifacts',
        array(array('artifacts_latitude', 'NOT', '0', 'all')),
        "$latitude != '0'", array('trivial' => true)),

    // blanks: {BLANK}, empty terms, and numeric fields
    array("oom: first name = {BLANK} OR email != {BLANK}", 'donors',
        array(array('donors_first_name', '=', '{BLANK}', 'oom'), array('donors_email', 'NOT', '{BLANK}', 'oom')),
        "$firstName = '' OR $email != ''", array('trivial' => true)),
    array("oom: first name = {BLANK} OR email = {BLANK}", 'donors',
        array(array('donors_first_name', '=', '{BLANK}', 'oom'), array('donors_email', '=', '{BLANK}', 'oom')),
        "$firstName = '' OR $email = ''"),
    array("all: first name != {BLANK}, email != {BLANK}", 'donors',
        array(array('donors_first_name', 'NOT', '{BLANK}', 'all'), array('donors_email', 'NOT', '{BLANK}', 'all')),
        "$firstName != '' AND $email != ''"),
    array("comments = ''", 'surveys', array(array('surveys_staff_comments', '=', '', 'all')), "$comments = ''"),
    array("comments != ''", 'surveys', array(array('surveys_staff_comments', 'NOT', '', 'all')), "$comments != ''"),
    array("comments LIKE ''", 'surveys', array(array('surveys_staff_comments', 'LIKE', '', 'all')), "$comments = ''"),
    array("comments NOT LIKE ''", 'surveys', array(array('surveys_staff_comments', 'NOT LIKE', '', 'all')), "$comments != ''"),
    array("numeric: latitude = ''", 'artifacts', array(array('artifacts_latitude', '=', '', 'all')), "t1.artifacts_latitude IS NULL"),
    array("numeric: latitude != {BLANK}", 'artifacts', array(array('artifacts_latitude', 'NOT', '{BLANK}', 'all')), "t1.artifacts_latitude IS NOT NULL"),
    array("radio: era LIKE ''", 'artifacts', array(array('artifacts_era', 'LIKE', '', 'all')), "COALESCE(t1.artifacts_era,'') = ''", array('trivial' => true)),

    // non-linked checkboxes
    array("checkbox: audience != Children", 'collections',
        array(array('collections_suitable_audience', 'NOT', 'Children', 'all')),
        "NOT " . $hasAudience('Children')),
    array("all: audience != Children, name != Coins", 'collections',
        array(array('collections_suitable_audience', 'NOT', 'Children', 'all'), array('collections_name', 'NOT', 'Coins', 'all')),
        "NOT " . $hasAudience('Children') . " AND t1.collections_name != 'Coins'", array('trivial' => true)),
    array("oom: audience != Children OR name != Coins", 'collections',
        array(array('collections_suitable_audience', 'NOT', 'Children', 'oom'), array('collections_name', 'NOT', 'Coins', 'oom')),
        "NOT " . $hasAudience('Children') . " OR t1.collections_name != 'Coins'", array('trivial' => true)),
    array("all: audience = Adults, audience NOT LIKE Child", 'collections',
        array(array('collections_suitable_audience', '=', 'Adults', 'all'), array('collections_suitable_audience', 'NOT LIKE', 'Child', 'all')),
        $hasAudience('Adults') . " AND $audience NOT LIKE '%Child%'"),

    // linked elements
    array("all: donor != Freeform Solutions, era != CE", 'artifacts',
        array(array('artifacts_donor', 'NOT', 'Freeform Solutions', 'all'), array('artifacts_era', 'NOT', 'CE', 'all')),
        "$donor != $freeform AND t1.artifacts_era != 'CE'"),
    array("oom: donor != Freeform Solutions OR era != CE", 'artifacts',
        array(array('artifacts_donor', 'NOT', 'Freeform Solutions', 'oom'), array('artifacts_era', 'NOT', 'CE', 'oom')),
        "$donor != $freeform OR t1.artifacts_era != 'CE'"),
    array("multi linked: collections != Weapons", 'artifacts',
        array(array('artifacts_collections', 'NOT', 'Weapons', 'all')),
        "NOT " . $inCollection($weapons)),
    array("multi linked: collections = Weapons", 'artifacts',
        array(array('artifacts_collections', '=', 'Weapons', 'all')),
        $inCollection($weapons)),
    array("multi linked: collections LIKE ns, which matches two collections, Coins and Weapons", 'artifacts',
        array(array('artifacts_collections', 'LIKE', 'ns', 'all')),
        "(" . $inCollection($coins) . " OR " . $inCollection($weapons) . ")"),
    array("multi linked: collections NOT LIKE ns", 'artifacts',
        array(array('artifacts_collections', 'NOT LIKE', 'ns', 'all')),
        "NOT " . $inCollection($coins) . " AND NOT " . $inCollection($weapons)),
    array("single linked: donor NOT LIKE Solutions", 'artifacts',
        array(array('artifacts_donor', 'NOT LIKE', 'Solutions', 'all')),
        "$donor != $freeform"),
    array("single linked: donor != the Freeform Solutions entry id", 'artifacts',
        array(array('artifacts_donor', 'NOT', (string) $freeform, 'all')),
        "$donor != $freeform", array('displayGap' => true)),
    array("single linked: donor = the Freeform Solutions entry id", 'artifacts',
        array(array('artifacts_donor', '=', (string) $freeform, 'all')),
        "t1.artifacts_donor = $freeform", array('displayGap' => true)),
    array("oom: collections != Weapons OR donor NOT LIKE Solutions", 'artifacts',
        array(array('artifacts_collections', 'NOT', 'Weapons', 'oom'), array('artifacts_donor', 'NOT LIKE', 'Solutions', 'oom')),
        "NOT " . $inCollection($weapons) . " OR $donor != $freeform"),

    // dates
    array("date = 2002", 'artifacts', array(array('artifacts_date_of_acquisition', '=', '2002', 'all')), "YEAR($acquired) = 2002"),
    array("date != 2002", 'artifacts', array(array('artifacts_date_of_acquisition', 'NOT', '2002', 'all')), "COALESCE(YEAR($acquired),0) != 2002"),
    array("date > 2020", 'artifacts', array(array('artifacts_date_of_acquisition', '>', '2020', 'all')), "$acquired > '2020-12-31'"),
    array("date >= 2020", 'artifacts', array(array('artifacts_date_of_acquisition', '>=', '2020', 'all')), "$acquired >= '2020-01-01'"),
    array("date < 2000", 'artifacts', array(array('artifacts_date_of_acquisition', '<', '2000', 'all')), "$acquired < '2000-01-01'"),
    array("date <= 2002", 'artifacts', array(array('artifacts_date_of_acquisition', '<=', '2002', 'all')), "$acquired <= '2002-12-31'"),
    array("date LIKE 2002-12", 'artifacts', array(array('artifacts_date_of_acquisition', 'LIKE', '2002-12', 'all')), "$acquired LIKE '2002-12%'"),
    array("date = December 2002", 'artifacts', array(array('artifacts_date_of_acquisition', '=', 'December 2002', 'all')), "$acquired LIKE '2002-12%'"),
    array("date = August 21 2020, written out", 'artifacts', array(array('artifacts_date_of_acquisition', '=', 'August 21 2020', 'all')), "$acquired = '2020-08-21'"),
    array("date NOT LIKE 2002", 'artifacts', array(array('artifacts_date_of_acquisition', 'NOT LIKE', '2002', 'all')), "COALESCE(YEAR($acquired),0) != 2002"),
    array("date LIKE 05, part of a date", 'artifacts', array(array('artifacts_date_of_acquisition', 'LIKE', '05', 'all')), "$acquired LIKE '%05%'"),
    array("range: date >= 1950, date <= 2002-12-02", 'artifacts',
        array(array('artifacts_date_of_acquisition', '>=', '1950', 'all'), array('artifacts_date_of_acquisition', '<=', '2002-12-02', 'all')),
        "$acquired BETWEEN '1950-01-01' AND '2002-12-02'"),

    // combinations of all and oom, with blanks, negations, linked elements, checkboxes and dates
    array("all: flagged = No; oom: comments LIKE Great OR comments = ''", 'surveys',
        array(array('surveys_flagged_to_the_staff', '=', 'No', 'all'), array('surveys_staff_comments', 'LIKE', 'Great', 'oom'), array('surveys_staff_comments', '=', '', 'oom')),
        "t1.surveys_flagged_to_the_staff = 2 AND ($comments LIKE '%Great%' OR $comments = '')"),
    array("all: flagged = Yes; oom: comments NOT LIKE Great OR comments NOT LIKE 'Can we'", 'surveys',
        array(array('surveys_flagged_to_the_staff', '=', 'Yes', 'all'), array('surveys_staff_comments', 'NOT LIKE', 'Great', 'oom'), array('surveys_staff_comments', 'NOT LIKE', 'Can we', 'oom')),
        "t1.surveys_flagged_to_the_staff = 1 AND ($comments NOT LIKE '%Great%' OR $comments NOT LIKE '%Can we%')"),
    array("all: flagged != Yes, comments = {BLANK}; oom: comments NOT LIKE x OR flagged != No", 'surveys',
        array(array('surveys_flagged_to_the_staff', 'NOT', 'Yes', 'all'), array('surveys_staff_comments', '=', '{BLANK}', 'all'),
            array('surveys_staff_comments', 'NOT LIKE', 'x', 'oom'), array('surveys_flagged_to_the_staff', 'NOT', 'No', 'oom')),
        "t1.surveys_flagged_to_the_staff != 1 AND $comments = '' AND ($comments NOT LIKE '%x%' OR t1.surveys_flagged_to_the_staff != 2)"),
    array("all: first name != {BLANK}, anonymous != Yes; oom: email = {BLANK} OR first name != Emilie", 'donors',
        array(array('donors_first_name', 'NOT', '{BLANK}', 'all'), array('donors_anonymous', 'NOT', 'Yes', 'all'),
            array('donors_email', '=', '{BLANK}', 'oom'), array('donors_first_name', 'NOT', 'Emilie', 'oom')),
        "$firstName != '' AND $anonymous != 'Yes' AND ($email = '' OR $firstName != 'Emilie')"),
    array("all: last name = {BLANK}; oom: email != {BLANK} OR anonymous = No", 'donors',
        array(array('donors_last_name', '=', '{BLANK}', 'all'), array('donors_email', 'NOT', '{BLANK}', 'oom'), array('donors_anonymous', '=', 'No', 'oom')),
        "$lastName = '' AND ($email != '' OR $anonymous = 'No')"),
    array("all: era = BCE, donor != Freeform Solutions; oom: collections != Weapons OR date = 2002 OR latitude = {BLANK}", 'artifacts',
        array(array('artifacts_era', '=', 'BCE', 'all'), array('artifacts_donor', 'NOT', 'Freeform Solutions', 'all'),
            array('artifacts_collections', 'NOT', 'Weapons', 'oom'), array('artifacts_date_of_acquisition', '=', '2002', 'oom'), array('artifacts_latitude', '=', '{BLANK}', 'oom')),
        "t1.artifacts_era = 'BCE' AND $donor != $freeform AND (NOT " . $inCollection($weapons) . " OR YEAR($acquired) = 2002 OR t1.artifacts_latitude IS NULL)"),
    array("all: date >= 1950, date <= 2002-12-02, era != CE; oom: donor = {BLANK} OR donor NOT LIKE Solutions", 'artifacts',
        array(array('artifacts_date_of_acquisition', '>=', '1950', 'all'), array('artifacts_date_of_acquisition', '<=', '2002-12-02', 'all'), array('artifacts_era', 'NOT', 'CE', 'all'),
            array('artifacts_donor', '=', '{BLANK}', 'oom'), array('artifacts_donor', 'NOT LIKE', 'Solutions', 'oom')),
        "$acquired BETWEEN '1950-01-01' AND '2002-12-02' AND t1.artifacts_era != 'CE' AND ($donor = 0 OR $donor != $freeform)"),
    array("all: collections NOT LIKE ns, latitude != {BLANK}; oom: era = CE OR donor = {BLANK}", 'artifacts',
        array(array('artifacts_collections', 'NOT LIKE', 'ns', 'all'), array('artifacts_latitude', 'NOT', '{BLANK}', 'all'),
            array('artifacts_era', '=', 'CE', 'oom'), array('artifacts_donor', '=', '{BLANK}', 'oom')),
        "NOT " . $inCollection($coins) . " AND NOT " . $inCollection($weapons) . " AND t1.artifacts_latitude IS NOT NULL AND (t1.artifacts_era = 'CE' OR $donor = 0)"),
    array("all: date != 2002, collections = Weapons; oom: era != BCE OR date < 2000", 'artifacts',
        array(array('artifacts_date_of_acquisition', 'NOT', '2002', 'all'), array('artifacts_collections', '=', 'Weapons', 'all'),
            array('artifacts_era', 'NOT', 'BCE', 'oom'), array('artifacts_date_of_acquisition', '<', '2000', 'oom')),
        "COALESCE(YEAR($acquired),0) != 2002 AND " . $inCollection($weapons) . " AND (t1.artifacts_era != 'BCE' OR $acquired < '2000-01-01')"),
    array("all: audience != Children; oom: name LIKE History OR name = Coins OR audience = {BLANK}", 'collections',
        array(array('collections_suitable_audience', 'NOT', 'Children', 'all'),
            array('collections_name', 'LIKE', 'History', 'oom'), array('collections_name', '=', 'Coins', 'oom'), array('collections_suitable_audience', '=', '{BLANK}', 'oom')),
        "NOT " . $hasAudience('Children') . " AND (t1.collections_name LIKE '%History%' OR t1.collections_name = 'Coins' OR $audience = '')"),
    array("the same negation in all and in oom: first name != Emilie", 'donors',
        array(array('donors_first_name', 'NOT', 'Emilie', 'all'), array('donors_first_name', 'NOT', 'Emilie', 'oom'), array('donors_email', '=', 'nobody', 'oom')),
        "$firstName != 'Emilie' AND ($firstName != 'Emilie' OR t1.donors_email = 'nobody')"),

    // metadata fields
    array("metadata: creation_uid != the uid of the Freeform Solutions entry's creator", 'donors',
        array(array('creation_uid', 'NOT', (string) $freeformCreator, 'all')),
        "t1.creation_uid != $freeformCreator"),
    array("all: metadata creation_uid != that uid, first name = {BLANK}", 'donors',
        array(array('creation_uid', 'NOT', (string) $freeformCreator, 'all'), array('donors_first_name', '=', '{BLANK}', 'all')),
        "t1.creation_uid != $freeformCreator AND $firstName = ''"),

    // { } references to a field in an existing entry
    array("Freeform Solutions: last name = {first name}, which is blank there", 'donors',
        array(array('donors_last_name', '=', '{donors_first_name}', 'all')),
        "$lastName = ''", array('entry' => $freeform)),
    array("Freeform Solutions: first name != {last name}, which is blank there", 'donors',
        array(array('donors_first_name', 'NOT', '{donors_last_name}', 'all')),
        "$firstName != ''", array('entry' => $freeform)),
    array("Freeform Solutions: first name LIKE {last name}, which is blank there", 'donors',
        array(array('donors_first_name', 'LIKE', '{donors_last_name}', 'all')),
        "$firstName = ''", array('entry' => $freeform)),
    array("Freeform Solutions: first name NOT LIKE {last name}, which is blank there", 'donors',
        array(array('donors_first_name', 'NOT LIKE', '{donors_last_name}', 'all')),
        "$firstName != ''", array('entry' => $freeform)),
    array("François-Marie: first name != {first name}", 'donors',
        array(array('donors_first_name', 'NOT', '{donors_first_name}', 'all')),
        "$firstName != 'François-Marie'", array('entry' => $voltaire)),
    array("François-Marie, oom: first name != {first name} OR email = formulize@freeform.ca", 'donors',
        array(array('donors_first_name', 'NOT', '{donors_first_name}', 'oom'), array('donors_email', '=', 'formulize@freeform.ca', 'oom')),
        "$firstName != 'François-Marie' OR t1.donors_email = 'formulize@freeform.ca'", array('entry' => $voltaire)),
    array("François-Marie, all and oom: email != {BLANK}; oom: first name NOT LIKE {first name} OR last name = {BLANK}", 'donors',
        array(array('donors_email', 'NOT', '{BLANK}', 'all'), array('donors_first_name', 'NOT LIKE', '{donors_first_name}', 'oom'), array('donors_last_name', '=', '{BLANK}', 'oom')),
        "$email != '' AND ($firstName NOT LIKE '%François-Marie%' OR $lastName = '')", array('entry' => $voltaire)),
    array("Roman Coin, oom: era != {era} OR short name = Roman Coin", 'artifacts',
        array(array('artifacts_era', 'NOT', '{artifacts_era}', 'oom'), array('artifacts_short_name', '=', 'Roman Coin', 'oom')),
        "t1.artifacts_era != (SELECT artifacts_era FROM $artifactsTable WHERE entry_id = $romanCoin) OR t1.artifacts_short_name = 'Roman Coin'", array('entry' => $romanCoin)),
    array("Roman Coin, all: era != {era}, donor != Freeform Solutions", 'artifacts',
        array(array('artifacts_era', 'NOT', '{artifacts_era}', 'all'), array('artifacts_donor', 'NOT', 'Freeform Solutions', 'all')),
        "t1.artifacts_era != (SELECT artifacts_era FROM $artifactsTable WHERE entry_id = $romanCoin) AND $donor != $freeform", array('entry' => $romanCoin)),
    array("Roman Coin: latitude != {latitude}, which some artifacts don't have", 'artifacts',
        array(array('artifacts_latitude', 'NOT', '{artifacts_latitude}', 'all')),
        "$latitude != COALESCE((SELECT artifacts_latitude FROM $artifactsTable WHERE entry_id = $romanCoin),'')", array('entry' => $romanCoin)),

    // { } references in a new entry, which has no value yet, so the condition is left out
    array("new entry: last name = {first name}", 'donors', array(array('donors_last_name', '=', '{donors_first_name}', 'all')), "1", array('entry' => 'new', 'trivial' => true)),
    array("new entry: last name != {first name}", 'donors', array(array('donors_last_name', 'NOT', '{donors_first_name}', 'all')), "1", array('entry' => 'new', 'trivial' => true)),
    array("new entry: last name LIKE {first name}", 'donors', array(array('donors_last_name', 'LIKE', '{donors_first_name}', 'all')), "1", array('entry' => 'new', 'trivial' => true)),
    array("new entry: last name IN {first name}", 'donors', array(array('donors_last_name', 'IN', '{donors_first_name}', 'all')), "1", array('entry' => 'new', 'trivial' => true)),

    // asynchronous values, the way conditional elements pass in what is on screen
    array("async: flagged != {flagged}, which is Yes on screen", 'surveys',
        array(array('surveys_flagged_to_the_staff', 'NOT', '{surveys_flagged_to_the_staff}', 'all')),
        "t1.surveys_flagged_to_the_staff != 1", array('entry' => 'new', 'async' => array('surveys_flagged_to_the_staff', 1, 'Yes'))),
    array("async: flagged = {flagged}, which is Yes on screen", 'surveys',
        array(array('surveys_flagged_to_the_staff', '=', '{surveys_flagged_to_the_staff}', 'all')),
        "t1.surveys_flagged_to_the_staff = 1", array('entry' => 'new', 'async' => array('surveys_flagged_to_the_staff', 1, 'Yes'))),
    array("async, oom: comments != {comments}, which is 'Great Job' on screen, OR flagged = Yes", 'surveys',
        array(array('surveys_staff_comments', 'NOT', '{surveys_staff_comments}', 'oom'), array('surveys_flagged_to_the_staff', '=', 'Yes', 'oom')),
        "$comments != 'Great Job' OR t1.surveys_flagged_to_the_staff = 1", array('entry' => 'new', 'async' => array('surveys_staff_comments', 'Great Job', 'Great Job'), 'trivial' => true)),
);

/**
 * Build the conditions for a scenario, in the form buildConditionsFilterSQL() and checkConditionsAgainstAnEntry() take them
 */
function conditionsFor($scenario) {
    global $element_handler;
    $conditions = array(array(), array(), array(), array());
    foreach ($scenario[2] as $condition) {
        $conditions[0][] = isMetaDataField($condition[0]) ? $condition[0] : $element_handler->get($condition[0])->getVar('ele_id');
        $conditions[1][] = $condition[1];
        $conditions[2][] = $condition[2];
        $conditions[3][] = $condition[3];
    }
    return $conditions;
}

/**
 * The entries the SQL for a scenario's conditions finds, as buildConditionsFilterSQL() builds it for one entry's context,
 * or as the extraction layer builds it, when $extractionMode is true
 */
function entriesFoundBySQL($scenario, $extractionMode = false) {
    global $xoopsDB, $form_handler;
    $options = isset($scenario[4]) ? $scenario[4] : array();
    $entry = isset($options['entry']) ? $options['entry'] : null;
    $fid = $form_handler->get($scenario[1])->getVar('id_form');
    $table = $xoopsDB->prefix('formulize_' . $scenario[1]);
    if (isset($options['async'])) {
        list($handle, $databaseValue, $readableValue) = $options['async'];
        $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$entry][$handle] = $databaseValue;
        $GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$entry][$handle] = $readableValue;
    }
    try {
        if ($extractionMode) {
            list($filter, $oomFilter, $from) = buildConditionsFilterSQL(conditionsFor($scenario), array($fid => 'main'));
            $found = entryIdsFor("SELECT main.entry_id FROM $table AS main WHERE 1 " . $filter[0] . " " . $oomFilter[0]);
        } else {
            list($filter, $oomFilter, $from) = buildConditionsFilterSQL(conditionsFor($scenario), $fid, $entry, null, $fid, 't1');
            $found = entryIdsFor("SELECT t1.entry_id FROM $table AS t1 $from WHERE 1 $filter $oomFilter");
        }
    } catch (Throwable $e) {
        $found = 'EXCEPTION: ' . $e->getMessage() . ' at line ' . $e->getLine();
    }
    unset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'], $GLOBALS['formulize_asynchronousFormDataInAPIFormat']);
    return $found;
}

/**
 * The entries a scenario's conditions should find, by its independently written query
 */
function entriesExpected($scenario) {
    global $xoopsDB;
    $table = $xoopsDB->prefix('formulize_' . $scenario[1]);
    return entryIdsFor("SELECT t1.entry_id FROM $table AS t1 WHERE " . $scenario[3]);
}

/**
 * True if the scenario depends on one entry's context, ie: it has { } references, asynchronous values, or is a new entry
 */
function dependsOnEntryContext($scenario) {
    $options = isset($scenario[4]) ? $scenario[4] : array();
    return isset($options['entry']) OR isset($options['async']);
}

// 1. each scenario's SQL, in order and then in reverse order in the same request
print "Building condition SQL for " . count($scenarios) . " scenarios, in order and then in reverse order\n";
foreach (array('in order' => $scenarios, 'in reverse order' => array_reverse($scenarios)) as $orderLabel => $scenariosInOrder) {
    foreach ($scenariosInOrder as $scenario) {
        $expected = entriesExpected($scenario);
        $options = isset($scenario[4]) ? $scenario[4] : array();
        if ($orderLabel == 'in order' AND empty($options['trivial'])) {
            $allEntries = entryIdsFor("SELECT entry_id FROM " . $xoopsDB->prefix('formulize_' . $scenario[1]));
            if (!check("[{$scenario[1]}] {$scenario[0]}: the museum data tells entries apart", ($expected !== '' AND $expected !== $allEntries), true)) {
                print "       the independent query finds " . ($expected === '' ? "no entries" : "every entry") . ", so this scenario no longer tests anything - has the museum data changed?\n";
            }
        }
        check("[{$scenario[1]}] {$scenario[0]} ($orderLabel)", entriesFoundBySQL($scenario), $expected);
    }
}

// 2. extraction mode, the way the extraction layer builds a list's fundamental filters
print "Building the same condition SQL in extraction mode\n";
foreach ($scenarios as $scenario) {
    if (dependsOnEntryContext($scenario)) {
        continue;
    }
    check("[{$scenario[1]}] {$scenario[0]} (extraction mode)", entriesFoundBySQL($scenario, true), entriesExpected($scenario));
}

// 3. the display side: checkConditionsAgainstAnEntry() passes the same entries
print "Checking the same conditions against each entry, the way elements are displayed\n";
foreach ($scenarios as $scenario) {
    $options = isset($scenario[4]) ? $scenario[4] : array();
    if (dependsOnEntryContext($scenario) OR !empty($options['displayGap'])) {
        continue;
    }
    $fid = $form_handler->get($scenario[1])->getVar('id_form');
    $passed = array();
    $res = $xoopsDB->query("SELECT entry_id FROM " . $xoopsDB->prefix('formulize_' . $scenario[1]));
    while ($row = $xoopsDB->fetchRow($res)) {
        if (checkConditionsAgainstAnEntry(conditionsFor($scenario), $fid, $row[0], cacheId: 'condition_filters_test')) {
            $passed[] = intval($row[0]);
        }
    }
    sort($passed, SORT_NUMERIC);
    check("[{$scenario[1]}] {$scenario[0]} (display side)", implode(',', $passed), entriesExpected($scenario));
}

printf("\n%d passed, %d failed\n", $GLOBALS['__pass'], $GLOBALS['__fail']);
exit($GLOBALS['__fail'] > 0 ? 1 : 0);
