<?php
/**
 * Integrity tests for the SQL that conditions become, against the built museum system.
 *
 * Run after the e2e suite, once the setup specs have built the museum system:
 *   php tests/condition_filters_test.php
 * and again as a site that shows dates as d/m/Y, since how dates are written in conditions depends on that:
 *   php tests/condition_filters_test.php d/m/Y
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
 * Every scenario is checked these ways:
 *   1. The SQL for the conditions finds the same entries as the independent query, in each of the shapes of query that
 *      Formulize puts it into:
 *        - a query on one form, with the form aliased t1, the way dynamic defaults and subform filter values use it
 *        - a subform's query, by calling the real formulize_subformEntriesMeetingConditions()
 *        - a linked element's options query, where the conditions sit inside the "safety net" brackets, ( ... ) OR t1.entry_id IN
 *          (the selected options), and GROUP BY t1.entry_id (selectElement, checkboxElement)
 *      All the scenarios are built in order, and then again in reverse order in the same request, so a condition's SQL cannot
 *      depend on what was built before it.
 *   2. The same conditions as a list's fundamental filters, through gatherDataset(), the real extraction layer.
 *   3. checkConditionsAgainstAnEntry() passes the same entries, so a condition means the same thing when it filters entries
 *      as when it decides whether an element is displayed.
 * Checks 2 and 3 skip scenarios with { } references, asynchronous values or a new entry, because those depend on the
 * context of one particular entry. Check 3 skips the one known difference: a numeric term on a linked element is an entry
 * id in SQL, but the display side compares the linked entry's text.
 *
 * Conditions on forms connected in a relationship are checked through gatherDataset() too, where each condition is also used
 * on its own, inside the EXISTS subqueries the extraction layer builds for connected forms. The extraction layer only finds a
 * main form entry through a condition on a connected form if it has a connected entry that meets the condition - a main entry
 * with no connected entries is not found, even by a negation - so that is what these scenarios expect.
 *
 * The same term handling is used when a list is searched, so date searches go through the real search parser and the
 * extraction layer as well, including the way a global search puts one term on every column, where a term that can't be a
 * date must not stop the other columns being searched.
 *
 * Conditions with = also assign their value to new entries, so that is checked too: a whole year or month is a range of
 * days, not a value that can be saved in a date element.
 *
 * EMPTYSET conditions, which are how to ask for main entries with no connected entry that matches, are covered along with
 * the conditions on connected forms.
 *
 * Also checked directly: reading dates in the site's format (formulize_dateValueAsISO), the MySQL format for showing dates
 * the way the site does (formulize_mysqlDateFormat), and how a date element reads a term (dateElement::prepareLiteralTextForDB).
 *
 * It asserts that the two ways of finding entries agree, not counts, so adding entries to the setup suite does not break
 * it. A scenario meant to tell entries apart fails if the independent query finds every entry or none, because then it no
 * longer tests anything, and the museum data it relied on has changed.
 *
 * Dates are compared in ways that work in MariaDB and MySQL. MySQL gives an error if a date is compared with text, and this test
 * finds that if it is run against a copy of the museum in MySQL, as long as blank dates are added to the copy to exercise them.
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

// Optionally run as a site that shows dates in another format, ie: d/m/Y. The language files only define the format if it isn't already.
if (isset($argv[1]) AND $argv[1] !== '') {
    define('_SHORTDATESTRING', $argv[1]);
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
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/subformListingsElement.php';

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
$chineseSword = museumEntryId('artifacts', 'artifacts_short_name', 'Chinese Sword');
$egyptianChariot = museumEntryId('artifacts', 'artifacts_short_name', 'Egyptian Chariot'); // an artifact with no donor
$modernAmazements = museumEntryId('exhibits', 'exhibits_name', 'Modern Amazements');
$ancientWonders = museumEntryId('exhibits', 'exhibits_name', 'Ancient Wonders');
// the uid of whoever made the Freeform Solutions entry, for the metadata scenarios
$freeformCreator = intval($xoopsDB->fetchRow($xoopsDB->query("SELECT creation_uid FROM " . $xoopsDB->prefix('formulize_donors') . " WHERE entry_id = $freeform"))[0]);

/**
 * The entry ids in a field of a museum entry that holds linked values, ie: ,1,4,
 */
function linkedIdsInMuseumEntry($formHandle, $field, $entryId) {
    global $xoopsDB;
    $row = $xoopsDB->fetchRow($xoopsDB->query("SELECT `$field` FROM " . $xoopsDB->prefix('formulize_' . $formHandle) . " WHERE entry_id = " . intval($entryId)));
    return array_values(array_filter(array_map('intval', explode(',', (string) $row[0]))));
}
$swordCollections = linkedIdsInMuseumEntry('artifacts', 'artifacts_collections', $chineseSword);
$romanCoinDonor = linkedIdsInMuseumEntry('artifacts', 'artifacts_donor', $romanCoin)[0];
$wonderArtifacts = implode(',', linkedIdsInMuseumEntry('exhibits', 'exhibits_artifacts', $ancientWonders));

$artifactsTable = $xoopsDB->prefix('formulize_artifacts');
$artifactsCreationYear = $xoopsDB->fetchRow($xoopsDB->query("SELECT YEAR(MIN(creation_datetime)) FROM $artifactsTable"))[0];
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
// a date is blank if it is NULL, or an empty date from old data, 0000-00-00. The museum has neither, but a site's data can, and this test can be run on a copy that does
$dateIsBlank = "($acquired IS NULL OR $acquired < '0001-01-01')";
$dateIsNotBlank = "$acquired >= '0001-01-01'";
$hasAudience = function($item) use ($audience) { return "($audience LIKE '%*=+*:$item*=+*:%' OR $audience LIKE '%*=+*:$item')"; };
$inCollection = function($id) use ($collections) { return "$collections LIKE '%,$id,%'"; };
$inNoneOfCollections = function($ids) use ($inCollection) { return "(NOT " . implode(" AND NOT ", array_map($inCollection, $ids)) . ")"; };
$inAnyOfCollections = function($ids) use ($inCollection) { return "(" . implode(" OR ", array_map($inCollection, $ids)) . ")"; };
$romanCoinLatitude = "COALESCE((SELECT artifacts_latitude FROM $artifactsTable WHERE entry_id = $romanCoin),'')";
$romanCoinEra = "(SELECT artifacts_era FROM $artifactsTable WHERE entry_id = $romanCoin)";

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

    // < and > never match a blank field, on either side, rather than blank counting as less than everything
    array("numeric: latitude < 50", 'artifacts', array(array('artifacts_latitude', '<', '50', 'all')), "t1.artifacts_latitude < 50"),
    array("numeric: latitude >= -90, every artifact with a latitude", 'artifacts', array(array('artifacts_latitude', '>=', '-90', 'all')), "t1.artifacts_latitude >= -90"),
    array("text: comments < Z", 'surveys', array(array('surveys_staff_comments', '<', 'Z', 'all')), "t1.surveys_staff_comments < 'Z'"),
    array("text: comments > A", 'surveys', array(array('surveys_staff_comments', '>', 'A', 'all')), "t1.surveys_staff_comments > 'A'"),
    array("oom: latitude < 0 OR era = BCE", 'artifacts',
        array(array('artifacts_latitude', '<', '0', 'oom'), array('artifacts_era', '=', 'BCE', 'oom')),
        "t1.artifacts_latitude < 0 OR t1.artifacts_era = 'BCE'"),

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

    // dates compared with things that aren't dates, which is an error in MySQL, so what they mean is decided before the SQL is written
    array("date = {BLANK}, which no artifact is", 'artifacts', array(array('artifacts_date_of_acquisition', '=', '{BLANK}', 'all')), $dateIsBlank, array('trivial' => true)),
    array("date != {BLANK}, which every artifact is", 'artifacts', array(array('artifacts_date_of_acquisition', 'NOT', '{BLANK}', 'all')), $dateIsNotBlank, array('trivial' => true)),
    array("date = ''", 'artifacts', array(array('artifacts_date_of_acquisition', '=', '', 'all')), $dateIsBlank, array('trivial' => true)),
    array("date LIKE ''", 'artifacts', array(array('artifacts_date_of_acquisition', 'LIKE', '', 'all')), $dateIsBlank, array('trivial' => true)),
    array("date NOT LIKE ''", 'artifacts', array(array('artifacts_date_of_acquisition', 'NOT LIKE', '', 'all')), $dateIsNotBlank, array('trivial' => true)),
    array("date = bread", 'artifacts', array(array('artifacts_date_of_acquisition', '=', 'bread', 'all')), "0", array('trivial' => true)),
    array("date != bread, every entry is not bread", 'artifacts', array(array('artifacts_date_of_acquisition', 'NOT', 'bread', 'all')), "1", array('trivial' => true)),
    array("date > bread", 'artifacts', array(array('artifacts_date_of_acquisition', '>', 'bread', 'all')), "0", array('trivial' => true)),
    array("date <= bread", 'artifacts', array(array('artifacts_date_of_acquisition', '<=', 'bread', 'all')), "0", array('trivial' => true)),
    array("date = 2026-13-45, not a real date", 'artifacts', array(array('artifacts_date_of_acquisition', '=', '2026-13-45', 'all')), "0", array('trivial' => true)),
    array("date LIKE bread", 'artifacts', array(array('artifacts_date_of_acquisition', 'LIKE', 'bread', 'all')), "0", array('trivial' => true)),
    array("date IN bread, nothing in the list is a date", 'artifacts', array(array('artifacts_date_of_acquisition', 'IN', 'bread', 'all')), "0", array('trivial' => true)),
    array("date IN 2020-08-21, bread, 2002-12-02, only the dates count", 'artifacts', array(array('artifacts_date_of_acquisition', 'IN', '2020-08-21, bread, 2002-12-02', 'all')), "$acquired IN ('2020-08-21','2002-12-02')"),
    array("all: era = BCE; oom: date = bread OR date > 2020", 'artifacts',
        array(array('artifacts_era', '=', 'BCE', 'all'), array('artifacts_date_of_acquisition', '=', 'bread', 'oom'), array('artifacts_date_of_acquisition', '>', '2020', 'oom')),
        "t1.artifacts_era = 'BCE' AND $acquired > '2020-12-31'", array('trivial' => true)),
    array("async: date = {date}, which is blank on screen", 'artifacts', array(array('artifacts_date_of_acquisition', '=', '{artifacts_date_of_acquisition}', 'all')),
        $dateIsBlank, array('entry' => 'new', 'async' => array('artifacts_date_of_acquisition', '', ''), 'trivial' => true)),
    array("async: date != {date}, which is blank on screen", 'artifacts', array(array('artifacts_date_of_acquisition', 'NOT', '{artifacts_date_of_acquisition}', 'all')),
        $dateIsNotBlank, array('entry' => 'new', 'async' => array('artifacts_date_of_acquisition', '', ''), 'trivial' => true)),
    array("async: date > {date}, which is blank on screen", 'artifacts', array(array('artifacts_date_of_acquisition', '>', '{artifacts_date_of_acquisition}', 'all')),
        "0", array('entry' => 'new', 'async' => array('artifacts_date_of_acquisition', '', ''), 'trivial' => true)),
    array("async: date = {date}, a date on screen, readable in the site's format", 'artifacts', array(array('artifacts_date_of_acquisition', '=', '{artifacts_date_of_acquisition}', 'all')),
        "$acquired = '2020-08-21'", array('entry' => 'new', 'async' => array('artifacts_date_of_acquisition', '2020-08-21', '21/08/2020'))),
    array("async: date != {date}, a date on screen, readable in the site's format", 'artifacts', array(array('artifacts_date_of_acquisition', 'NOT', '{artifacts_date_of_acquisition}', 'all')),
        "($acquired IS NULL OR $acquired != '2020-08-21')", array('entry' => 'new', 'async' => array('artifacts_date_of_acquisition', '2020-08-21', '21/08/2020'))),

    // dates in the metadata, which have a time as well. These are checked in SQL, the display side doesn't treat them as dates
    array("metadata: creation_datetime = {BLANK}", 'artifacts', array(array('creation_datetime', '=', '{BLANK}', 'all')), "t1.creation_datetime IS NULL", array('trivial' => true, 'displayGap' => true)),
    array("metadata: creation_datetime != {BLANK}", 'artifacts', array(array('creation_datetime', 'NOT', '{BLANK}', 'all')), "t1.creation_datetime IS NOT NULL", array('trivial' => true, 'displayGap' => true)),
    array("metadata: creation_datetime = bread", 'artifacts', array(array('creation_datetime', '=', 'bread', 'all')), "0", array('trivial' => true, 'displayGap' => true)),
    array("metadata: creation_datetime != bread", 'artifacts', array(array('creation_datetime', 'NOT', 'bread', 'all')), "1", array('trivial' => true, 'displayGap' => true)),
    array("metadata: creation_datetime LIKE bread", 'artifacts', array(array('creation_datetime', 'LIKE', 'bread', 'all')), "0", array('trivial' => true, 'displayGap' => true)),
    array("metadata: creation_datetime >= 2000, a year", 'artifacts', array(array('creation_datetime', '>=', '2000', 'all')), "t1.creation_datetime >= '2000-01-01'", array('trivial' => true, 'displayGap' => true)),
    array("metadata: creation_datetime = this year's creation year, all of it, to the last second", 'artifacts', array(array('creation_datetime', '=', $artifactsCreationYear, 'all')), "YEAR(t1.creation_datetime) = $artifactsCreationYear", array('trivial' => true, 'displayGap' => true)),
    array("metadata: creation_datetime = August 21 2020, written out", 'artifacts', array(array('creation_datetime', '=', 'August 21 2020', 'all')), "t1.creation_datetime = '2020-08-21'", array('trivial' => true, 'displayGap' => true)),

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

    // { } references to a field in an entry in another form, the way a subform's conditions refer to the main form's entry
    // Artifacts in the Weapons collection: the artifacts' multi value Collections element is linked to the Collections form's Name
    array("Weapons collection: collections = {collections_name}", 'artifacts',
        array(array('artifacts_collections', '=', '{collections_name}', 'all')),
        $inCollection($weapons), array('entry' => $weapons, 'curlyForm' => 'collections')),
    array("Weapons collection: collections != {collections_name}", 'artifacts',
        array(array('artifacts_collections', 'NOT', '{collections_name}', 'all')),
        "NOT " . $inCollection($weapons), array('entry' => $weapons, 'curlyForm' => 'collections')),
    array("Weapons collection, oom: collections = {collections_name} OR era = BCE", 'artifacts',
        array(array('artifacts_collections', '=', '{collections_name}', 'oom'), array('artifacts_era', '=', 'BCE', 'oom')),
        $inCollection($weapons) . " OR t1.artifacts_era = 'BCE'", array('entry' => $weapons, 'curlyForm' => 'collections')),
    array("Weapons collection, oom: collections != {collections_name} OR short name = Chinese Sword", 'artifacts',
        array(array('artifacts_collections', 'NOT', '{collections_name}', 'oom'), array('artifacts_short_name', '=', 'Chinese Sword', 'oom')),
        "NOT " . $inCollection($weapons) . " OR t1.artifacts_short_name = 'Chinese Sword'", array('entry' => $weapons, 'curlyForm' => 'collections')),
    array("Weapons collection, all: era = CE; oom: collections != {collections_name} OR latitude = {BLANK}", 'artifacts',
        array(array('artifacts_era', '=', 'CE', 'all'), array('artifacts_collections', 'NOT', '{collections_name}', 'oom'), array('artifacts_latitude', '=', '{BLANK}', 'oom')),
        "t1.artifacts_era = 'CE' AND (NOT " . $inCollection($weapons) . " OR t1.artifacts_latitude IS NULL)", array('entry' => $weapons, 'curlyForm' => 'collections')),

    // { } references to linked values from the same source as the linked field. A negation means none of the values are in the field
    array("Chinese Sword: collections != {collections}, which has several, so none of them", 'artifacts',
        array(array('artifacts_collections', 'NOT', '{artifacts_collections}', 'all')),
        $inNoneOfCollections($swordCollections), array('entry' => $chineseSword)),
    array("Chinese Sword: collections NOT LIKE {collections}, so none of them", 'artifacts',
        array(array('artifacts_collections', 'NOT LIKE', '{artifacts_collections}', 'all')),
        $inNoneOfCollections($swordCollections), array('entry' => $chineseSword)),
    array("Chinese Sword: collections LIKE {collections}, so any of them", 'artifacts',
        array(array('artifacts_collections', 'LIKE', '{artifacts_collections}', 'all')),
        $inAnyOfCollections($swordCollections), array('entry' => $chineseSword, 'trivial' => true)),
    array("Chinese Sword, oom: collections != {collections} OR era = BCE", 'artifacts',
        array(array('artifacts_collections', 'NOT', '{artifacts_collections}', 'oom'), array('artifacts_era', '=', 'BCE', 'oom')),
        $inNoneOfCollections($swordCollections) . " OR t1.artifacts_era = 'BCE'", array('entry' => $chineseSword)),
    array("Roman Coin: donor != {donor}, one value in a field with one value", 'artifacts',
        array(array('artifacts_donor', 'NOT', '{artifacts_donor}', 'all')),
        "$donor != $romanCoinDonor", array('entry' => $romanCoin)),
    array("Egyptian Chariot: donor != {donor}, which is blank there", 'artifacts',
        array(array('artifacts_donor', 'NOT', '{artifacts_donor}', 'all')),
        "t1.artifacts_donor IS NOT NULL", array('entry' => $egyptianChariot)),
    array("Ancient Wonders: favourite artifact != {exhibits_artifacts}, several values, a field with one value", 'surveys',
        array(array('surveys_favourite_artifact', 'NOT', '{exhibits_artifacts}', 'all')),
        "COALESCE(t1.surveys_favourite_artifact,0) NOT IN ($wonderArtifacts)", array('entry' => $ancientWonders, 'curlyForm' => 'exhibits')),
    array("Ancient Wonders: favourite artifact LIKE {exhibits_artifacts}, several values, a field with one value", 'surveys',
        array(array('surveys_favourite_artifact', 'LIKE', '{exhibits_artifacts}', 'all')),
        "t1.surveys_favourite_artifact IN ($wonderArtifacts)", array('entry' => $ancientWonders, 'curlyForm' => 'exhibits')),

    // { } references where the referenced form is the source of the linked field, and the referenced field is linked to somewhere else
    array("Modern Amazements: exhibit = {exhibits_collections}", 'surveys',
        array(array('surveys_exhibit', '=', '{exhibits_collections}', 'all')),
        "t1.surveys_exhibit = $modernAmazements", array('entry' => $modernAmazements, 'curlyForm' => 'exhibits')),
    array("Modern Amazements: exhibit != {exhibits_collections}", 'surveys',
        array(array('surveys_exhibit', 'NOT', '{exhibits_collections}', 'all')),
        "COALESCE(t1.surveys_exhibit,0) != $modernAmazements", array('entry' => $modernAmazements, 'curlyForm' => 'exhibits')),

    // { } references in all and oom together, and to an entry that doesn't exist
    array("Roman Coin, all: era != {era}; oom: latitude != {latitude} OR donor != {donor}", 'artifacts',
        array(array('artifacts_era', 'NOT', '{artifacts_era}', 'all'), array('artifacts_latitude', 'NOT', '{artifacts_latitude}', 'oom'), array('artifacts_donor', 'NOT', '{artifacts_donor}', 'oom')),
        "t1.artifacts_era != $romanCoinEra AND ($latitude != $romanCoinLatitude OR $donor != $romanCoinDonor)", array('entry' => $romanCoin)),
    array("Weapons collection, all: collections = {collections_name}; oom: collections != {collections_name} OR era = BCE", 'artifacts',
        array(array('artifacts_collections', '=', '{collections_name}', 'all'), array('artifacts_collections', 'NOT', '{collections_name}', 'oom'), array('artifacts_era', '=', 'BCE', 'oom')),
        $inCollection($weapons) . " AND (NOT " . $inCollection($weapons) . " OR t1.artifacts_era = 'BCE')", array('entry' => $weapons, 'curlyForm' => 'collections')),
    array("an entry that doesn't exist: first name != {last name}", 'donors',
        array(array('donors_first_name', 'NOT', '{donors_last_name}', 'all')),
        "0", array('entry' => 99999999, 'trivial' => true)),
    array("an entry that doesn't exist, oom: first name != {last name} OR email = formulize@freeform.ca", 'donors',
        array(array('donors_first_name', 'NOT', '{donors_last_name}', 'oom'), array('donors_email', '=', 'formulize@freeform.ca', 'oom')),
        "t1.donors_email = 'formulize@freeform.ca'", array('entry' => 99999999)),

    // { } references in a new entry, which has no value yet, so the condition is left out
    array("new entry: last name = {first name}", 'donors', array(array('donors_last_name', '=', '{donors_first_name}', 'all')), "1", array('entry' => 'new', 'trivial' => true)),
    array("new entry: last name != {first name}", 'donors', array(array('donors_last_name', 'NOT', '{donors_first_name}', 'all')), "1", array('entry' => 'new', 'trivial' => true)),
    array("new entry: last name LIKE {first name}", 'donors', array(array('donors_last_name', 'LIKE', '{donors_first_name}', 'all')), "1", array('entry' => 'new', 'trivial' => true)),
    array("new entry: last name IN {first name}", 'donors', array(array('donors_last_name', 'IN', '{donors_first_name}', 'all')), "1", array('entry' => 'new', 'trivial' => true)),

    // other markers for an entry that isn't saved yet, which work the same as 'new', rather than going into the SQL as if they were an entry id
    array("proxy entry: last name = {first name}", 'donors', array(array('donors_last_name', '=', '{donors_first_name}', 'all')), "1", array('entry' => 'proxy', 'trivial' => true)),
    array("proxy entry: last name != {first name}", 'donors', array(array('donors_last_name', 'NOT', '{donors_first_name}', 'all')), "1", array('entry' => 'proxy', 'trivial' => true)),
    array("proxy entry: last name LIKE {first name}", 'donors', array(array('donors_last_name', 'LIKE', '{donors_first_name}', 'all')), "1", array('entry' => 'proxy', 'trivial' => true)),
    array("proxy entry: donor = {short name}, on a linked element", 'artifacts', array(array('artifacts_donor', '=', '{artifacts_short_name}', 'all')), "1", array('entry' => 'proxy', 'trivial' => true)),
    array("new_0 entry: last name = {first name}", 'donors', array(array('donors_last_name', '=', '{donors_first_name}', 'all')), "1", array('entry' => 'new_0', 'trivial' => true)),
    array("new_0 entry: last name IN {first name}", 'donors', array(array('donors_last_name', 'IN', '{donors_first_name}', 'all')), "1", array('entry' => 'new_0', 'trivial' => true)),
    array("new_0 entry, oom: last name != {first name} OR email = formulize@freeform.ca", 'donors',
        array(array('donors_last_name', 'NOT', '{donors_first_name}', 'oom'), array('donors_email', '=', 'formulize@freeform.ca', 'oom')),
        "t1.donors_email = 'formulize@freeform.ca'", array('entry' => 'new_0')),
    array("blank entry: last name = {first name}", 'donors', array(array('donors_last_name', '=', '{donors_first_name}', 'all')), "1", array('entry' => '', 'trivial' => true)),
    array("async on a proxy entry: flagged != {flagged}, which is Yes on screen, so the values under proxy are found", 'surveys',
        array(array('surveys_flagged_to_the_staff', 'NOT', '{surveys_flagged_to_the_staff}', 'all')),
        "t1.surveys_flagged_to_the_staff != 1", array('entry' => 'proxy', 'async' => array('surveys_flagged_to_the_staff', 1, 'Yes'))),
    array("async on a new_0 entry: collections != {collections}, which is Weapons and Coins on screen", 'artifacts',
        array(array('artifacts_collections', 'NOT', '{artifacts_collections}', 'all')),
        $inNoneOfCollections(array($weapons, $coins)), array('entry' => 'new_0', 'async' => array('artifacts_collections', ",$weapons,$coins,", array('Weapons', 'Coins')))),

    // asynchronous values on linked and checkbox elements
    array("async: collections != {collections}, which is Weapons and Coins on screen, so neither", 'artifacts',
        array(array('artifacts_collections', 'NOT', '{artifacts_collections}', 'all')),
        $inNoneOfCollections(array($weapons, $coins)), array('entry' => 'new', 'async' => array('artifacts_collections', ",$weapons,$coins,", array('Weapons', 'Coins')))),
    array("async: collections NOT LIKE {collections}, which is Weapons and Coins on screen, so neither", 'artifacts',
        array(array('artifacts_collections', 'NOT LIKE', '{artifacts_collections}', 'all')),
        $inNoneOfCollections(array($weapons, $coins)), array('entry' => 'new', 'async' => array('artifacts_collections', ",$weapons,$coins,", array('Weapons', 'Coins')))),
    array("async: collections LIKE {collections}, which is Weapons and Coins on screen, so either", 'artifacts',
        array(array('artifacts_collections', 'LIKE', '{artifacts_collections}', 'all')),
        $inAnyOfCollections(array($weapons, $coins)), array('entry' => 'new', 'async' => array('artifacts_collections', ",$weapons,$coins,", array('Weapons', 'Coins')))),
    array("async: collections != {collections}, which is blank on screen", 'artifacts',
        array(array('artifacts_collections', 'NOT', '{artifacts_collections}', 'all')),
        "$collections != ''", array('entry' => 'new', 'async' => array('artifacts_collections', '', ''), 'trivial' => true)),
    array("async: donor != {donor}, which is one value on screen", 'artifacts',
        array(array('artifacts_donor', 'NOT', '{artifacts_donor}', 'all')),
        "$donor != $freeform", array('entry' => 'new', 'async' => array('artifacts_donor', $freeform, 'Freeform Solutions'))),
    array("async: donor != {short name}, text on screen", 'artifacts',
        array(array('artifacts_donor', 'NOT', '{artifacts_short_name}', 'all')),
        "$donor != $freeform", array('entry' => 'new', 'async' => array('artifacts_short_name', 'Freeform Solutions', 'Freeform Solutions'))),
    array("async: audience != {audience}, on a checkbox", 'collections',
        array(array('collections_suitable_audience', 'NOT', '{collections_suitable_audience}', 'all')),
        "NOT " . $hasAudience('Children'), array('entry' => 'new', 'async' => array('collections_suitable_audience', 'Children', 'Children'))),

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

// Dates written the way a site that shows dates as d/m/Y writes them, when running as one: php tests/condition_filters_test.php d/m/Y
if (_SHORTDATESTRING === 'd/m/Y') {
    $acquiredAsShown = "DATE_FORMAT($acquired,'%d/%m/%Y')";
    $scenarios = array_merge($scenarios, array(
        array("d/m/Y site: date LIKE 12/2002, part of a date as the site shows it", 'artifacts',
            array(array('artifacts_date_of_acquisition', 'LIKE', '12/2002', 'all')), "$acquiredAsShown LIKE '%12/2002%'"),
        array("d/m/Y site: date NOT LIKE 12/2002", 'artifacts',
            array(array('artifacts_date_of_acquisition', 'NOT LIKE', '12/2002', 'all')), "COALESCE($acquiredAsShown,'') NOT LIKE '%12/2002%'"),
        array("d/m/Y site: date LIKE 21/08, a day and month", 'artifacts',
            array(array('artifacts_date_of_acquisition', 'LIKE', '21/08', 'all')), "$acquiredAsShown LIKE '%21/08%'"),
        array("d/m/Y site: date = 21/08/2020, a whole date as the site shows it, not read as the 8th of the 21st month", 'artifacts',
            array(array('artifacts_date_of_acquisition', '=', '21/08/2020', 'all')), "$acquired = '2020-08-21'"),
        array("d/m/Y site: date = 02/12/2002, not read as February 12", 'artifacts',
            array(array('artifacts_date_of_acquisition', '=', '02/12/2002', 'all')), "$acquired = '2002-12-02'"),
        array("d/m/Y site: date LIKE 2002-12, part of a date as it is stored, still matches", 'artifacts',
            array(array('artifacts_date_of_acquisition', 'LIKE', '2002-12', 'all')), "$acquired LIKE '%2002-12%'"),
        array("d/m/Y site, all: era = BCE; oom: date LIKE 08/2020 OR date NOT LIKE 2002", 'artifacts',
            array(array('artifacts_era', '=', 'BCE', 'all'), array('artifacts_date_of_acquisition', 'LIKE', '08/2020', 'oom'), array('artifacts_date_of_acquisition', 'NOT LIKE', '2002', 'oom')),
            "t1.artifacts_era = 'BCE' AND ($acquiredAsShown LIKE '%08/2020%' OR COALESCE(YEAR($acquired),0) != 2002)"),
    ));
}

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
 * The query shapes that buildConditionsFilterSQL()'s SQL is put into around Formulize
 */
const SHAPE_ONE_FORM = 'one form, aliased t1';
const SHAPE_SUBFORM = 'subform';
const SHAPE_LINKED_OPTIONS = 'linked options, in the safety net';

/**
 * The entry that is always selected in a linked element's options query, so it is always found: the safety net in
 * prepareLinkedElementSafetyNets(). The highest entry id in the form.
 */
function selectedOptionFor($formHandle) {
    global $xoopsDB;
    return intval($xoopsDB->fetchRow($xoopsDB->query("SELECT MAX(entry_id) FROM " . $xoopsDB->prefix('formulize_' . $formHandle)))[0]);
}

/**
 * The entries the SQL for a scenario's conditions finds, built by buildConditionsFilterSQL() the way the code that uses it
 * builds it, and put into a query of the same shape
 */
function entriesFoundBySQL($scenario, $shape) {
    global $xoopsDB, $form_handler;
    $options = isset($scenario[4]) ? $scenario[4] : array();
    $entry = isset($options['entry']) ? $options['entry'] : null;
    $fid = $form_handler->get($scenario[1])->getVar('id_form');
    $curlyBracketFid = isset($options['curlyForm']) ? $form_handler->get($options['curlyForm'])->getVar('id_form') : $fid;
    $table = $xoopsDB->prefix('formulize_' . $scenario[1]);
    if (isset($options['async'])) {
        list($handle, $databaseValue, $readableValue) = $options['async'];
        $GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'][$entry][$handle] = $databaseValue;
        $GLOBALS['formulize_asynchronousFormDataInAPIFormat'][$entry][$handle] = $readableValue;
    }
    $found = "no query of the shape '$shape'";
    try {
        switch ($shape) {
            case SHAPE_ONE_FORM: // data.php, formdisplay.php, selectElement's options limit
                list($filter, $oomFilter, $from) = buildConditionsFilterSQL(conditionsFor($scenario), $fid, $entry, null, $curlyBracketFid, 't1');
                $found = entryIdsFor("SELECT t1.entry_id FROM $table AS t1 $from WHERE 1 $filter $oomFilter");
                break;
            case SHAPE_SUBFORM: // the real function, as if every entry in the form is connected to the main entry
                $connectedEntries = explode(',', entryIdsFor("SELECT entry_id FROM $table"));
                $found = array_map('intval', formulize_subformEntriesMeetingConditions($connectedEntries, conditionsFor($scenario), $fid, $entry, null, $curlyBracketFid));
                sort($found, SORT_NUMERIC);
                $found = implode(',', $found);
                break;
            case SHAPE_LINKED_OPTIONS: // selectElement, checkboxElement, and the linked element filters in functions.php
                list($filter, $oomFilter, $from) = buildConditionsFilterSQL(conditionsFor($scenario), $fid, $entry, null, $curlyBracketFid, 't1');
                list($safetyNetStart, $safetyNetEnd) = prepareLinkedElementSafetyNets(array(selectedOptionFor($scenario[1])));
                $extraClause = prepareLinkedElementExtraClause('', $from, $safetyNetStart);
                $found = entryIdsFor("SELECT t1.entry_id FROM $table AS t1 $extraClause $filter $oomFilter $safetyNetEnd GROUP BY t1.entry_id");
                break;
        }
    } catch (Throwable $e) {
        $found = 'EXCEPTION: ' . $e->getMessage() . ' at line ' . $e->getLine();
    }
    unset($GLOBALS['formulize_asynchronousFormDataInDatabaseReadyFormat'], $GLOBALS['formulize_asynchronousFormDataInAPIFormat']);
    return $found;
}

/**
 * The main form entries gatherDataset() returns with a scenario's conditions as fundamental filters
 */
function entriesFoundByGatherDataset($scenario, $frid) {
    global $form_handler;
    $fid = $form_handler->get($scenario[1])->getVar('id_form');
    try {
        $data = gatherDataset($fid, filter: array('fundamental_filters' => conditionsFor($scenario)), frid: $frid, bypassCache: true);
    } catch (Throwable $e) {
        return 'EXCEPTION: ' . $e->getMessage() . ' at line ' . $e->getLine();
    }
    $ids = array();
    foreach ((array) $data as $record) {
        foreach (internalRecordIds($record, $fid) as $id) {
            $ids[] = intval($id);
        }
    }
    $ids = array_unique($ids);
    sort($ids, SORT_NUMERIC);
    return implode(',', $ids);
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
 * The expected entries plus another one, sorted, for the linked options query, where the selected option is always found
 */
function withEntry($entryIds, $entryId) {
    $ids = $entryIds === '' ? array() : array_map('intval', explode(',', $entryIds));
    $ids[] = intval($entryId);
    $ids = array_unique($ids);
    sort($ids, SORT_NUMERIC);
    return implode(',', $ids);
}

/**
 * True if the scenario depends on one entry's context, ie: it has { } references, asynchronous values, or is a new entry
 */
function dependsOnEntryContext($scenario) {
    $options = isset($scenario[4]) ? $scenario[4] : array();
    return isset($options['entry']) OR isset($options['async']);
}

/**
 * Fail a scenario that is meant to tell entries apart, if its independent query finds every entry or none
 */
function checkNotTrivial($scenario, $expected) {
    global $xoopsDB;
    $options = isset($scenario[4]) ? $scenario[4] : array();
    if (!empty($options['trivial'])) {
        return;
    }
    $allEntries = entryIdsFor("SELECT entry_id FROM " . $xoopsDB->prefix('formulize_' . $scenario[1]));
    if (!check("[{$scenario[1]}] {$scenario[0]}: the museum data tells entries apart", ($expected !== '' AND $expected !== $allEntries), true)) {
        print "       the independent query finds " . ($expected === '' ? "no entries" : "every entry") . ", so this scenario no longer tests anything - has the museum data changed?\n";
    }
}

print "Dates are shown as " . _SHORTDATESTRING . "\n";

// 1. each scenario's SQL in each shape of query, in order and then in reverse order in the same request
print "Building condition SQL for " . count($scenarios) . " scenarios, in order and then in reverse order\n";
foreach ($scenarios as $scenario) {
    checkNotTrivial($scenario, entriesExpected($scenario));
}
foreach (array('in order' => $scenarios, 'in reverse order' => array_reverse($scenarios)) as $orderLabel => $scenariosInOrder) {
    foreach ($scenariosInOrder as $scenario) {
        $expected = entriesExpected($scenario);
        check("[{$scenario[1]}] {$scenario[0]} (" . SHAPE_ONE_FORM . ", $orderLabel)", entriesFoundBySQL($scenario, SHAPE_ONE_FORM), $expected);
        check("[{$scenario[1]}] {$scenario[0]} (" . SHAPE_SUBFORM . ", $orderLabel)", entriesFoundBySQL($scenario, SHAPE_SUBFORM), $expected);
        // the selected option is always found, whatever the conditions are, because the conditions are in the WHERE clause, inside the safety net
        check("[{$scenario[1]}] {$scenario[0]} (" . SHAPE_LINKED_OPTIONS . ", $orderLabel)", entriesFoundBySQL($scenario, SHAPE_LINKED_OPTIONS), withEntry($expected, selectedOptionFor($scenario[1])));
    }
}

// 2. as a list's fundamental filters, through the extraction layer, on the form alone
print "Using the same conditions as fundamental filters in gatherDataset()\n";
foreach ($scenarios as $scenario) {
    if (dependsOnEntryContext($scenario)) {
        continue;
    }
    check("[{$scenario[1]}] {$scenario[0]} (gatherDataset)", entriesFoundByGatherDataset($scenario, 0), entriesExpected($scenario));
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

// 4. conditions on forms connected in the Primary Relationship, through the extraction layer, which uses each condition in the
// main query and on its own in the EXISTS subqueries it builds for connected forms. Donors connect to their artifacts through the
// artifacts' Donor element. A donor is found if they have an artifact that meets the conditions on artifacts - a donor with no
// artifacts is not found by a condition on artifacts, even a negation, because the extraction layer does not consider a missing
// connected entry for fundamental filters.
print "Using conditions on connected forms as fundamental filters in gatherDataset(), through the Primary Relationship\n";
$artifactOfDonor = "SELECT 1 FROM $artifactsTable AS a WHERE a.artifacts_donor = t1.entry_id";
$aCollections = "COALESCE(a.artifacts_collections,'')";
$relationshipScenarios = array(
    array("artifacts: era = BCE", 'donors',
        array(array('artifacts_era', '=', 'BCE', 'all')),
        "EXISTS ($artifactOfDonor AND a.artifacts_era = 'BCE')"),
    array("artifacts: era != CE", 'donors',
        array(array('artifacts_era', 'NOT', 'CE', 'all')),
        "EXISTS ($artifactOfDonor AND COALESCE(a.artifacts_era,'') != 'CE')"),
    array("artifacts: collections != Weapons (no linked value is Weapons)", 'donors',
        array(array('artifacts_collections', 'NOT', 'Weapons', 'all')),
        "EXISTS ($artifactOfDonor AND $aCollections NOT LIKE '%,$weapons,%')"),
    array("artifacts, all: date != 2002, era = BCE (the same artifact)", 'donors',
        array(array('artifacts_date_of_acquisition', 'NOT', '2002', 'all'), array('artifacts_era', '=', 'BCE', 'all')),
        "EXISTS ($artifactOfDonor AND COALESCE(YEAR(a.artifacts_date_of_acquisition),0) != 2002 AND a.artifacts_era = 'BCE')"),
    array("all: donor first name != {BLANK}, artifacts date = 2002", 'donors',
        array(array('donors_first_name', 'NOT', '{BLANK}', 'all'), array('artifacts_date_of_acquisition', '=', '2002', 'all')),
        "$firstName != '' AND EXISTS ($artifactOfDonor AND YEAR(a.artifacts_date_of_acquisition) = 2002)"),
    array("artifacts, oom: collections = Weapons OR date < 1960", 'donors',
        array(array('artifacts_collections', '=', 'Weapons', 'oom'), array('artifacts_date_of_acquisition', '<', '1960', 'oom')),
        "EXISTS ($artifactOfDonor AND ($aCollections LIKE '%,$weapons,%' OR a.artifacts_date_of_acquisition < '1960-01-01'))"),
    array("all: artifacts era = CE; oom: donor email = {BLANK} OR artifacts collections NOT LIKE ns", 'donors',
        array(array('artifacts_era', '=', 'CE', 'all'), array('donors_email', '=', '{BLANK}', 'oom'), array('artifacts_collections', 'NOT LIKE', 'ns', 'oom')),
        "EXISTS ($artifactOfDonor AND a.artifacts_era = 'CE' AND ($email = '' OR ($aCollections NOT LIKE '%,$coins,%' AND $aCollections NOT LIKE '%,$weapons,%')))"),
    // EMPTYSET is how to ask for main entries with no connected entry that matches, which includes main entries with no connected entries at all
    array("EMPTYSET: no artifact has era CE, which includes a donor with no artifacts", 'donors',
        array(array('artifacts_era', '=', 'EMPTYSET:CE', 'all')),
        "NOT EXISTS ($artifactOfDonor AND a.artifacts_era = 'CE')"),
    array("EMPTYSET: no artifact has era BCE, which includes a donor with no artifacts", 'donors',
        array(array('artifacts_era', '=', 'EMPTYSET:BCE', 'all')),
        "NOT EXISTS ($artifactOfDonor AND a.artifacts_era = 'BCE')"),
    array("EMPTYSET: no artifact is in Weapons, on a linked field", 'donors',
        array(array('artifacts_collections', '=', 'EMPTYSET:Weapons', 'all')),
        "NOT EXISTS ($artifactOfDonor AND $aCollections LIKE '%,$weapons,%')"),
    array("EMPTYSET with a condition on the donor: first name != {BLANK}, no artifact has era CE", 'donors',
        array(array('donors_first_name', 'NOT', '{BLANK}', 'all'), array('artifacts_era', '=', 'EMPTYSET:BCE', 'all')),
        "$firstName != '' AND NOT EXISTS ($artifactOfDonor AND a.artifacts_era = 'BCE')", array('trivial' => true)),
);
foreach ($relationshipScenarios as $scenario) {
    $expected = entriesExpected($scenario);
    checkNotTrivial($scenario, $expected);
    check("[{$scenario[1]}] {$scenario[0]} (gatherDataset, Primary Relationship)", entriesFoundByGatherDataset($scenario, -1), $expected);
}

// 5. searching a list: the search boxes go through the same date handling, by way of the real search parser and extraction layer
print "Searching a list by date, through the search parser and gatherDataset()\n";
$shortName = "COALESCE(t1.artifacts_short_name,'')";
$dateSearches = array(
    array("a term that can't be a date finds nothing", array('artifacts_date_of_acquisition' => 'bread'), "0", true),
    array("a year", array('artifacts_date_of_acquisition' => '2002'), "YEAR($acquired) = 2002"),
    array("a year and month", array('artifacts_date_of_acquisition' => '2002-12'), "$acquired LIKE '2002-12%'"),
    array("a month written out", array('artifacts_date_of_acquisition' => 'December 2002'), "$acquired LIKE '2002-12%'"),
    array("a date written out", array('artifacts_date_of_acquisition' => 'August 21 2020'), "$acquired = '2020-08-21'"),
    array("part of a date", array('artifacts_date_of_acquisition' => '05'), "$acquired LIKE '%05%'"),
    array("after a year", array('artifacts_date_of_acquisition' => '>2020'), "$acquired > '2020-12-31'"),
    array("on or after a year", array('artifacts_date_of_acquisition' => '>=2020'), "$acquired >= '2020-01-01'"),
    array("not in a year", array('artifacts_date_of_acquisition' => '!2002'), "YEAR($acquired) != 2002"),
    array("between two dates", array('artifacts_date_of_acquisition' => 'between 1950-01-01 and 2002-12-02'), "$acquired BETWEEN '1950-01-01' AND '2002-12-02'"),
    // comparing a date with something that isn't one is an error in MySQL
    array("{BLANK} on a date", array('artifacts_date_of_acquisition' => '{BLANK}'), $dateIsBlank, true),
    array("not {BLANK} on a date", array('artifacts_date_of_acquisition' => '!{BLANK}'), $dateIsNotBlank, true),
    array("= a term that can't be a date", array('artifacts_date_of_acquisition' => '=bread'), "0", true),
    array("!= a term that can't be a date", array('artifacts_date_of_acquisition' => '!=bread'), "$acquired IS NOT NULL", true),
    array("> a term that can't be a date", array('artifacts_date_of_acquisition' => '>bread'), "0", true),
    array("not a term that can't be a date", array('artifacts_date_of_acquisition' => '!bread'), "$acquired IS NOT NULL", true),
    array("{BLANK} on the metadata date", array('creation_datetime' => '{BLANK}'), "t1.creation_datetime IS NULL", true),
    array("not {BLANK} on the metadata date", array('creation_datetime' => '!{BLANK}'), "t1.creation_datetime IS NOT NULL", true),
    array("= a term that can't be a date on the metadata date", array('creation_datetime' => '=bread'), "0", true),
    array("> a term that can't be a date on the metadata date", array('creation_datetime' => '>bread'), "0", true),
    array("!= a term that can't be a date on the metadata date", array('creation_datetime' => '!=bread'), "t1.creation_datetime IS NOT NULL", true),
    array("the metadata date in a year, which is all of it", array('creation_datetime' => "=$artifactsCreationYear"), "YEAR(t1.creation_datetime) = $artifactsCreationYear", true),
    array("the metadata date after a year", array('creation_datetime' => ">2000"), "t1.creation_datetime > '2000-12-31 23:59:59'", true),
    // a global search puts one term on every column, ORed together. This is why terms that weren't dates used to be dropped: they became empty searches, which match everything
    array("global: a word in another column finds it, and the date column isn't a match for everything", array('artifacts_short_name' => 'ORRoman', 'artifacts_date_of_acquisition' => 'ORRoman'), "$shortName LIKE '%Roman%'"),
    array("global: a word in no column finds nothing", array('artifacts_short_name' => 'ORzzz', 'artifacts_date_of_acquisition' => 'ORzzz'), "0", true),
    array("global: a year finds it in the date column", array('artifacts_short_name' => 'OR2002', 'artifacts_date_of_acquisition' => 'OR2002'), "YEAR($acquired) = 2002 OR $shortName LIKE '%2002%'"),
);
if (_SHORTDATESTRING === 'd/m/Y') {
    $dateSearches[] = array("d/m/Y site: part of a date as the site shows it", array('artifacts_date_of_acquisition' => '12/2002'), "DATE_FORMAT($acquired,'%d/%m/%Y') LIKE '%12/2002%'");
    $dateSearches[] = array("d/m/Y site: a whole date as the site shows it", array('artifacts_date_of_acquisition' => '02/12/2002'), "$acquired = '2002-12-02'");
    $dateSearches[] = array("d/m/Y site: not part of a date as the site shows it", array('artifacts_date_of_acquisition' => '!12/2002'), "DATE_FORMAT($acquired,'%d/%m/%Y') NOT LIKE '%12/2002%'");
}
$artifactsFid = $form_handler->get('artifacts')->getVar('id_form');
foreach ($dateSearches as $search) {
    list($label, $searches, $expectedWhere) = $search;
    $expected = entryIdsFor("SELECT t1.entry_id FROM $artifactsTable AS t1 WHERE $expectedWhere");
    if (empty($search[3])) {
        checkNotTrivial(array($label, 'artifacts'), $expected);
    }
    $found = array();
    try {
        $data = gatherDataset($artifactsFid, filter: formulize_parseSearchesIntoFilter($searches), frid: 0, bypassCache: true);
        foreach ((array) $data as $record) {
            foreach (internalRecordIds($record, $artifactsFid) as $id) {
                $found[] = intval($id);
            }
        }
        $found = array_unique($found);
        sort($found, SORT_NUMERIC);
        $found = implode(',', $found);
    } catch (Throwable $e) {
        $found = 'EXCEPTION: ' . $e->getMessage();
    }
    check("[artifacts] search: $label", $found, $expected);
}

// 6. saving: an = condition's value is assigned to new entries, which only works if it is a value the element can hold
print "Checking which condition values are assigned to new entries\n";
$dateElementId = $element_handler->get('artifacts_date_of_acquisition')->getVar('ele_id');
$yesNoElementId = $element_handler->get('artifacts_donated_to_museum')->getVar('ele_id');
$valuesFor = function($elementId, $term) {
    return getFilterValuesForEntry(array(array($elementId), array('='), array($term), array('all')));
};
check("saved: a whole date", $valuesFor($dateElementId, '2020-08-21'), array($artifactsFid => array('artifacts_date_of_acquisition' => '2020-08-21')));
check("saved: a date written out", $valuesFor($dateElementId, 'August 21 2020'), array($artifactsFid => array('artifacts_date_of_acquisition' => '2020-08-21')));
check("not saved: a year, which is a range of days", $valuesFor($dateElementId, '2026'), array());
check("not saved: a month, which is a range of days", $valuesFor($dateElementId, 'March'), array());
check("not saved: a term that can't be a date", $valuesFor($dateElementId, 'bread'), array());
check("saved: Yes on a Yes/No element, as its code", $valuesFor($yesNoElementId, 'Yes'), array($artifactsFid => array('artifacts_donated_to_museum' => 1)));

// 7. dates: reading a date in the site's format, the MySQL format that shows dates the way the site does, and how a date element reads a term
print "Checking how dates are read and shown\n";
check("formulize_dateValueAsISO: 05/03/2026 as d/m/Y", formulize_dateValueAsISO('05/03/2026', 'd/m/Y'), '2026-03-05');
check("formulize_dateValueAsISO: 05/03/2026 as m/d/Y", formulize_dateValueAsISO('05/03/2026', 'm/d/Y'), '2026-05-03');
check("formulize_dateValueAsISO: 5 March 2026 as j F Y", formulize_dateValueAsISO('5 March 2026', 'j F Y'), '2026-03-05');
check("formulize_dateValueAsISO: YYYY-MM-DD with a time", formulize_dateValueAsISO('2026-03-05 10:30:00', 'd/m/Y'), '2026-03-05');
check("formulize_dateValueAsISO: 31/02/2026 is not a date as d/m/Y", formulize_dateValueAsISO('31/02/2026', 'd/m/Y'), '31/02/2026');
check("formulize_dateValueAsISO: part of a date is left as it is", formulize_dateValueAsISO('03/2026', 'd/m/Y'), '03/2026');
check("formulize_dateValueAsISO: blank is left blank", formulize_dateValueAsISO('', 'd/m/Y'), '');
check("formulize_mysqlDateFormat: d/m/Y", formulize_mysqlDateFormat('d/m/Y'), '%d/%m/%Y');
check("formulize_mysqlDateFormat: Y-m-d", formulize_mysqlDateFormat('Y-m-d'), '%Y-%m-%d');
check("formulize_mysqlDateFormat: n/j/y", formulize_mysqlDateFormat('n/j/y'), '%c/%e/%y');
check("formulize_mysqlDateFormat: D, d M Y", formulize_mysqlDateFormat('D, d M Y'), '%a, %d %b %Y');
check("formulize_mysqlDateFormat: jS F Y, the day with its suffix", formulize_mysqlDateFormat('jS F Y'), '%D %M %Y');
check("formulize_mysqlDateFormat: an escaped letter is literal", formulize_mysqlDateFormat('d \o\f F'), '%d of %M');
check("formulize_mysqlDateFormat: a % is literal", formulize_mysqlDateFormat('d%m'), '%d%%%m');
$dateElementObject = $element_handler->get('artifacts_date_of_acquisition');
$dateTypeHandler = xoops_getmodulehandler('dateElement', 'formulize');
check("date term: 2026 is a year, kept as typed", $dateTypeHandler->prepareLiteralTextForDB('2026', $dateElementObject), '2026');
check("date term: March is a month, kept as typed", $dateTypeHandler->prepareLiteralTextForDB('March', $dateElementObject), 'March');
check("date term: something that isn't a date is kept as typed", $dateTypeHandler->prepareLiteralTextForDB('garbage', $dateElementObject), 'garbage');
check("date term: August 21 2020, written out", $dateTypeHandler->prepareLiteralTextForDB('August 21 2020', $dateElementObject), '2020-08-21');
check("date term: August 21 2020, written out, when part of a date can be searched for", $dateTypeHandler->prepareLiteralTextForDB('August 21 2020', $dateElementObject, true), '2020-08-21');
check("date term: Oct 5 when part of a date can be searched for, words are read as a date", $dateTypeHandler->prepareLiteralTextForDB('Oct 5', $dateElementObject, true), date('Y') . '-10-05');
check("date term: 05/03 when part of a date can be searched for, kept as typed", $dateTypeHandler->prepareLiteralTextForDB('05/03', $dateElementObject, true), '05/03');
check("date term: 05/03/2026 in the site's format", $dateTypeHandler->prepareLiteralTextForDB('05/03/2026', $dateElementObject), _SHORTDATESTRING === 'd/m/Y' ? '2026-03-05' : '2026-05-03');

printf("\n%d passed, %d failed\n", $GLOBALS['__pass'], $GLOBALS['__fail']);
exit($GLOBALS['__fail'] > 0 ? 1 : 0);
