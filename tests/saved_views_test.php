<?php
/**
 * Reading and writing saved views.
 *
 * A saved view is what someone keeps when they have set a list up the way they want it: the columns, the
 * searches on them, the calculations, the sort, the scope. formulizeSavedViewsHandler is the only thing
 * that reads or writes them, and this checks that a view survives the trip to the database and back.
 *
 * Why this exists: that storage is unusually easy to get quietly wrong. Several of the columns are lists
 * tied to each other by position and nothing else - the searches line up with the columns one for one,
 * joined by a delimiter chosen to be unlikely rather than impossible, and the calculations line up with
 * three more lists describing what is calculated on each. Nothing errors when they slip out of step; the
 * list just filters on a column nobody asked about. Views also hold names people typed, so the escaping
 * has to survive apostrophes and quotes, and until this handler existed that SQL was assembled by hand in
 * the middle of a five thousand line display file.
 *
 * Like element_reference_integrity_test.php, this boots the application and uses the live database, so CI
 * runs it after the Playwright suite rather than before. Everything it writes happens inside a transaction
 * that is rolled back, and it only ever touches rows it created.
 *
 * WHAT THIS DOES NOT COVER
 * Showing a view - reading the settings out of $_POST, putting a loaded view's settings back into it,
 * deciding which view to load. That stays in include/entriesdisplay.php and is exercised through the
 * browser by the Playwright specs.
 *
 * Usage:
 *   docker exec formulize-web-1 php /var/www/html/tests/saved_views_test.php
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
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/savedViews.php';

global $xoopsDB;
$handler = xoops_getmodulehandler('savedViews', 'formulize');
if (!$handler) {
    fwrite(STDERR, "Could not load the saved views handler - the module bootstrap did not complete.\n");
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

echo "\nsaved views: writing one, reading it back, changing it, deleting it\n\n";

$xoopsDB->queryF("START TRANSACTION");

try {
    // The columns displayEntries() assembles when somebody saves a view, with values chosen to break naive
    // escaping and to exercise each of the position-linked lists.
    $name = "O'Neill & Sons \"stock\"";
    $searchDelimiter = formulizeSavedViewsHandler::SEARCH_DELIMITER;
    $values = array(
        'sv_name' => $name,
        'sv_pubgroups' => '1,2',
        'sv_mod_uid' => 1,
        'sv_owner_uid' => 7,
        'sv_formframe' => 5,
        'sv_mainform' => '',
        'sv_lockcontrols' => 1,
        'sv_hidelist' => 0,
        'sv_hidecalc' => 1,
        'sv_sort' => 'artifacts_name',
        'sv_order' => 'DESC',
        'sv_oldcols' => 'a,b,c',
        'sv_currentview' => 'all',
        'sv_calc_cols' => '11/22',
        'sv_calc_calcs' => 'sum/avg',
        'sv_calc_blanks' => 'all/noblanks',
        'sv_calc_grouping' => '/grp',
        'sv_quicksearches' => 's1' . $searchDelimiter . '!persistent!' . $searchDelimiter . 's3',
        'sv_global_search' => 'x',
        'sv_pubfilters' => '',
        'sv_entriesperpage' => 25,
        'sv_use_features' => 'cols,searches',
        'sv_searches_are_fundamental' => 1,
    );

    $id = $handler->save($values);
    check('saving a new view gives back its id', is_int($id) && $id > 0, true);

    $row = $handler->get($id);
    check('it can be read back by id', is_array($row), true);

    $mismatched = array();
    foreach ($values as $column => $value) {
        // sv_mainform is an int column, and the blank that a view with no relationship is saved with lands
        // in it as 0. The SQL this replaced wrote '' into that same int column, so this is the behaviour
        // being preserved rather than something to put right here.
        if ($column === 'sv_mainform') {
            $value = 0;
        }
        if ((string) $row[$column] !== (string) $value) {
            $mismatched[] = $column . ': ' . var_export($row[$column], true) . ' != ' . var_export($value, true);
        }
    }
    check('every column comes back as it went in', $mismatched, array());
    check('including a name with an apostrophe, an ampersand and quotes in it', $row['sv_name'], $name);
    check('and the searches, delimiter and all', $row['sv_quicksearches'], 's1&*=%4#!persistent!&*=%4#s3');
    check('a view with no relationship keeps a blank mainform, which the column stores as 0',
        (string) $row['sv_mainform'], '0');

    // A name is only unique within the form the view was saved against, so looking one up that way has to
    // be scoped. Asking by name with no form is refused rather than answered - the answer would be whichever
    // view of that name was created first anywhere in the system, which is not a useful thing to hand back.
    $byName = $handler->get($name, 5, 0);
    check('it can be read back by name within its own form', $byName ? intval($byName['sv_id']) : 0, $id);
    check('the same name in another form is not it', $handler->get($name, 999, 0), false);
    check('a name that was never saved is not found', $handler->get('zz no such view zz', 5, 0), false);
    $refused = false;
    try {
        $handler->get($name);
    } catch (Throwable $expected) {
        $refused = true;
    }
    check('asking by name with no form is refused rather than guessed at', $refused, true);
    check('the owner is reported', $handler->getOwner($id), 7);

    // Updating writes only what it is given, which is what lets the caller leave out the columns that
    // belong to the view's creation - who owns it and which form it is for.
    check('updating gives back the same id', $handler->save(array('sv_name' => 'renamed', 'sv_sort' => 'other_col'), $id), $id);
    $row = $handler->get($id);
    check('the columns that were passed changed', array($row['sv_name'], $row['sv_sort']), array('renamed', 'other_col'));
    check('the columns that were not passed did not', $row['sv_oldcols'], 'a,b,c');
    check('and an update does not reset who owns it', intval($row['sv_owner_uid']), 7);

    check('deleting says it worked', $handler->delete($id), true);
    check('and it is gone', $handler->get($id), false);

} catch (Throwable $t) {
    $GLOBALS['__fail']++;
    printf("  FAIL the round trip could not be completed\n       %s: %s @ %s:%s\n",
        get_class($t), $t->getMessage(), $t->getFile(), $t->getLine());
}

$xoopsDB->queryF("ROLLBACK");

// Checked rather than assumed: everything above wrote to a real table.
$leftovers = $xoopsDB->fetchArray($xoopsDB->query("SELECT COUNT(*) AS c FROM " . $xoopsDB->prefix('formulize_saved_views') . "
    WHERE sv_name = " . $xoopsDB->quoteString("O'Neill & Sons \"stock\"") . " OR sv_name = 'renamed'"));
check('rollback: the view this test made is not in the database', intval($leftovers['c']), 0);

echo "\n";
printf("RESULT: %d passed, %d failed\n", $GLOBALS['__pass'], $GLOBALS['__fail']);
exit($GLOBALS['__fail'] === 0 ? 0 : 1);
