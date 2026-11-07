<?php
/**
 * What the update patch does to a site whose element handles are still in their old shapes.
 *
 * Unlike the logic tests in this folder, nothing here is a stand-in. It boots the application against a real
 * database, runs the real patch with the real rename behind it, and then looks at what is there: the fields of the
 * data tables, the code files, and what getData() gives back for an entry.
 *
 * The only thing done around the application is making the old site in the first place. Formulize refuses to create
 * a handle with capitals or a hyphen now, so those are written straight into the database, which is what such a
 * site has. That is why this needs a database of its own: the renamed fields are real ALTER TABLEs, so they cannot be
 * rolled back, and the test will not start in a database that is not named as a copy. It also rewrites code files,
 * so it keeps what was in those folders and puts it back when it finishes.
 *
 * WHAT THIS COVERS
 * 1. Elements with capitals or hyphens: the handle, the field in the data and revisions tables and the code file are
 *    all renamed, the values survive, and an entry reads back correctly through getData().
 * 2. What refers to them: tokens, a saved view's columns, code that names them, and what is left for a person.
 * 3. Old spellings of elements that are already right, rewritten only on the update that reaches version 27.
 * 4. Code that uses a stale spelling beside the real variable, which become one variable.
 * 5. What only looks like a handle, in a caption or a condition's term, which is left alone.
 * 6. An element that cannot be fixed, which is listed and tried again, and a handle that would collide, which gets a
 *    handle of its own.
 * 7. A run with nothing left to do, and the whole patch function as an update calls it.
 *
 * Usage, from a site with the museum system built. This copies its database, then runs against the copy:
 *   docker exec formulize-mariadb-1 mariadb -uroot -pabc123 -e "CREATE DATABASE formulize_patch_test; GRANT ALL ON formulize_patch_test.* TO 'user'@'%'"
 *   docker exec formulize-mariadb-1 sh -c "mariadb-dump -uroot -pabc123 formulize | mariadb -uroot -pabc123 formulize_patch_test"
 *   docker exec -e FORMULIZE_PATCH_TEST_DB=formulize_patch_test formulize-web-1 php /var/www/html/tests/element_handle_patch_test.php
 *   docker exec formulize-mariadb-1 mariadb -uroot -pabc123 -e "DROP DATABASE formulize_patch_test"
 *
 * @package Formulize
 * @subpackage tests
 */

if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit;
}

$testDatabase = (string) getenv('FORMULIZE_PATCH_TEST_DB');
if (substr($testDatabase, -11) !== '_patch_test') {
	fwrite(STDERR, "This test damages the database it runs in. Give it a copy, in FORMULIZE_PATCH_TEST_DB, with a name ending in _patch_test - see the top of this file.\n");
	exit(1);
}

$_SERVER['REQUEST_METHOD'] = 'POST'; // writes reach the database only on POST
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';

// The application takes the name of its database from the trust file. Whichever defines this first wins, so the
// application runs unchanged, against the copy. The trust file's own define is the one warning expected on the way in.
define('SDATA_DB_NAME', $testDatabase);
set_error_handler(fn($number, $message) => strpos($message, 'SDATA_DB_NAME') !== false);
include dirname(__DIR__) . '/mainfile.php';
restore_error_handler();
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/extract.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/admin/patches/000_schema_migrations.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/admin/patches/002_always_run.php';
global $xoopsDB;
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');

$inDatabase = $xoopsDB->fetchArray($xoopsDB->queryF("SELECT DATABASE() AS name"));
if ($inDatabase['name'] !== $testDatabase) {
	fwrite(STDERR, "Connected to {$inDatabase['name']} rather than $testDatabase, so nothing was done.\n");
	exit(1);
}

// A read of one value, as a process of its own - see valueInTheApplication()
if (isset($argv[1]) AND $argv[1] == 'read') {
	$data = getData('', intval($argv[2]), intval($argv[3]));
	echo "VALUE:" . json_encode($data ? getValue($data[0], $argv[4]) : null) . "
";
	exit(0);
}

// What the patch can rewrite on disk, put back when this finishes, however it finishes
function filesUnder($folders) {
	$files = array();
	foreach ($folders as $folder) {
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS)) as $file) {
			if ($file->isFile()) {
				$files[$file->getPathname()] = file_get_contents($file->getPathname());
			}
		}
	}
	return $files;
}
$rewrittenFolders = array_merge(
	array(XOOPS_ROOT_PATH . '/modules/formulize/code', XOOPS_ROOT_PATH . '/modules/formulize/templates/screens'),
	glob(XOOPS_ROOT_PATH . '/modules/formulize/language/*/mail_template', GLOB_ONLYDIR));
$filesBefore = filesUnder($rewrittenFolders);
register_shutdown_function(function() use ($rewrittenFolders, $filesBefore) {
	$filesNow = filesUnder($rewrittenFolders);
	foreach (array_diff_key($filesNow, $filesBefore) as $path => $contents) {
		unlink($path);
	}
	foreach ($filesBefore as $path => $contents) {
		if (!isset($filesNow[$path]) OR $filesNow[$path] !== $contents) {
			file_put_contents($path, $contents);
		}
	}
});

// What a person would get for this element in this entry, by way of the application rather than the table. Asked of
// a new process, because that is what the next page load after an update is: it starts with nothing cached, where
// this one has been holding on to what it read before the rename.
function valueInTheApplication($fid, $entry, $handle) {
	$output = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' read ' . intval($fid) . ' ' . intval($entry) . ' ' . escapeshellarg($handle) . ' 2>/dev/null');
	if (!preg_match('/^VALUE:(.*)$/m', (string) $output, $match)) {
		fwrite(STDERR, "Could not read the value:
$output
");
		exit(1);
	}
	return json_decode($match[1], true);
}

$failures = 0;
function stop($message) { // not die(), which exits with 0, so a test it stopped would pass in CI
	fwrite(STDERR, $message);
	exit(1);
}
function ok($label, $condition, $detail = '') {
	global $failures;
	echo ($condition ? "  ok   " : "  FAIL ") . $label . (!$condition && $detail !== '' ? "\n       got: " . print_r($detail, true) : '') . "\n";
	if (!$condition) { $failures++; }
}
function sq($sql) {
	global $xoopsDB;
	if (!$result = $xoopsDB->queryF($sql)) { stop("SQL failed: $sql\n" . $xoopsDB->error() . "\n"); }
	return $result;
}
function one($sql) { global $xoopsDB; return $xoopsDB->fetchArray(sq($sql)); }
function t($name) { global $xoopsDB; return $xoopsDB->prefix($name); }
function eleId($handle) { $r = one("SELECT ele_id FROM " . t('formulize') . " WHERE ele_handle = '$handle'"); if (!$r) { stop("no element $handle\n"); } return intval($r['ele_id']); }
function storedHandle($id) { $r = one("SELECT BINARY ele_handle AS h, ele_caption FROM " . t('formulize') . " WHERE ele_id = $id"); return $r['h']; }
function caption($id) { $r = one("SELECT ele_caption FROM " . t('formulize') . " WHERE ele_id = $id"); return $r['ele_caption']; }
function columns($table) { $names = array(); $res = sq("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'"); global $xoopsDB; while ($row = $xoopsDB->fetchArray($res)) { $names[] = $row['COLUMN_NAME']; } return $names; }
function setHandle($id, $handle) { sq("UPDATE " . t('formulize') . " SET ele_handle = '$handle' WHERE ele_id = $id"); }
function addView($columns, $searches, $sort, $order) {
	global $xoopsDB, $fid;
	sq("INSERT INTO " . t('formulize_saved_views') . " SET sv_name = 'Handle patch test', sv_formframe = $fid, sv_mainform = 0, sv_oldcols = " . $xoopsDB->quoteString($columns)
		. ", sv_quicksearches = " . $xoopsDB->quoteString($searches) . ", sv_sort = " . $xoopsDB->quoteString($sort) . ", sv_order = " . $xoopsDB->quoteString($order) . ", sv_pubfilters = '', sv_currentview = 'all'");
	return intval($xoopsDB->getInsertId());
}
function run($prev) {
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	$element_handler->clearElementCache();
	ob_start();
	$result = formulize_normalize_element_handles($prev);
	$out = ob_get_clean();
	return array($result, strip_tags(str_replace(array('</p>', '</h3>', '</li>'), "\n", $out)));
}

$D = t('formulize_artifacts');
$R = t('formulize_artifacts_revisions');
$codeDir = XOOPS_ROOT_PATH . '/modules/formulize/code/';

// ------------------------------------------------------------------ seed an old site
$era = eleId('artifacts_era');
$yearEra = eleId('artifacts_year_era');
$year = eleId('artifacts_year');
$shortName = eleId('artifacts_short_name');
$height = eleId('artifacts_height');
$depth = eleId('artifacts_depth');
$width = eleId('artifacts_width');
$idNumber = eleId('artifacts_id_number');
$acquired = eleId('artifacts_date_of_acquisition');
$fid = intval(one("SELECT id_form FROM " . t('formulize') . " WHERE ele_id = $era")['id_form']);

$entry = intval(one("SELECT MIN(entry_id) AS first FROM $D")['first']);
sq("UPDATE $D SET artifacts_era = 'BC' WHERE entry_id = $entry");
$entries = intval(one("SELECT COUNT(*) AS c FROM $D WHERE artifacts_era IS NOT NULL AND artifacts_era != ''")['c']);

// A: capitals in the element table, the data table and the revisions table
sq("ALTER TABLE $D CHANGE artifacts_era Artifacts_Era text DEFAULT NULL");
setHandle($era, 'Artifacts_Era');
// B: capitals and hyphens, in the element table, the data table and a code file
sq("ALTER TABLE $D CHANGE artifacts_year_era `Artifacts-Year-Era` text DEFAULT NULL");
setHandle($yearEra, 'Artifacts-Year-Era');
@unlink($codeDir . 'derived_artifacts_year_era.php');
file_put_contents($codeDir . 'derived_Artifacts-Year-Era.php',
	"<?php\n\$value = \$Artifacts_Era . getData('', $fid, 'Artifacts_Era/**/x/**/=') . \$row['Artifacts_Era'];\n"
	. "\$both = \$Artifacts_Short_Name . \$artifacts_short_name;\n"
	. "\$hyphen = \$artifacts-depth . \$artifacts_depth;\n");
// E: stored with capitals, with no field for it anywhere
sq("ALTER TABLE $D CHANGE artifacts_date_of_acquisition zz_moved date DEFAULT NULL");
setHandle($acquired, 'Artifacts_Date_Of_Acquisition');
sq("DROP TABLE IF EXISTS $R");
sq("CREATE TABLE $R LIKE $D");
sq("ALTER TABLE $R CHANGE zz_moved artifacts_date_of_acquisition date DEFAULT NULL");

// references to A
sq("UPDATE " . t('formulize') . " SET ele_caption = CONCAT(ele_caption, ' {Artifacts_Era}') WHERE ele_id = $year");
$viewOfA = addView('49,50,hiddencolumn_Artifacts_Era,94', '&*=%4#{search_Artifacts_Era}&*=%4#&*=%4#', '', '');

// C: references with old spellings to elements whose own handles are right
sq("UPDATE " . t('formulize') . " SET ele_caption = CONCAT(ele_caption, ' {Artifacts_Short_Name}') WHERE ele_id = $height");
$viewOfC = addView('hiddencolumn_Artifacts_Short_Name,50,59,62,63,94', '', 'artifacts-depth', 'SORT_ASC');
sq("INSERT INTO " . t('formulize_notification_conditions') . " (not_cons_subject) VALUES ('Now {ELEMENT_ARTIFACTS-WIDTH} and {ELEMENT_ARTIFACTS_YEAR}')");
$notification = $xoopsDB->getInsertId();

// D: things that look like a handle but are not references to it
sq("UPDATE " . t('formulize') . " SET ele_caption = 'See Artifacts_Id_Number and Artifacts-Id-Number here' WHERE ele_id = $idNumber");
$conditions = serialize(array(array($year), array('='), array('Artifacts_Id_Number'), array('all')));
$filterRow = one("SELECT sid FROM " . t('formulize_screen_listofentries') . " LIMIT 1");
sq("UPDATE " . t('formulize_screen_listofentries') . " SET fundamental_filters = " . $xoopsDB->quoteString($conditions) . " WHERE sid = " . intval($filterRow['sid']));

// F: only differs from an element elsewhere by case, in another form
$elementColumns = array_diff(columns(t('formulize')), array('ele_id'));
$list = '`' . implode('`,`', $elementColumns) . '`';
$select = str_replace('`ele_handle`', "'ARTIFACTS_HEIGHT'", str_replace('`id_form`', '9999', $list));
$formTwo = intval(one("SELECT id_form FROM " . t('formulize_id') . " WHERE form_handle = 'donors'")['id_form']);
$select = str_replace('9999', (string) $formTwo, $select);
sq("INSERT INTO " . t('formulize') . " ($list) SELECT $select FROM " . t('formulize') . " WHERE ele_id = $height");
$twin = $xoopsDB->getInsertId();
sq("ALTER TABLE " . t('formulize_donors') . " ADD `ARTIFACTS_HEIGHT` decimal(65,1) DEFAULT NULL");

ok('A: before the patch, the application does not find the value under the handle, which is why such an element opens empty', valueInTheApplication($fid, $entry, 'artifacts_era') !== 'BC');

echo "\n== first run, as an update from 27: renames misspelled elements, leaves stale references alone\n";
list($result, $output) = run(27);
echo $output;
ok('returned true', $result === true);
ok('A: handle lowercased', storedHandle($era) === 'artifacts_era', storedHandle($era));
ok('A: the application reads the value again', valueInTheApplication($fid, $entry, 'artifacts_era') === 'BC', valueInTheApplication($fid, $entry, 'artifacts_era'));
ok('A: data field renamed', in_array('artifacts_era', columns($D), true) AND !in_array('Artifacts_Era', columns($D), true));
ok('A: revisions field renamed', in_array('artifacts_era', columns($R), true) AND !in_array('Artifacts_Era', columns($R), true));
ok('A: values survive', intval(one("SELECT COUNT(*) AS c FROM $D WHERE artifacts_era IS NOT NULL AND artifacts_era != ''")['c']) === $entries);
ok('A: {token} in another element', strpos(caption($year), '{artifacts_era}') !== false, caption($year));
$sv1 = one("SELECT sv_oldcols, sv_quicksearches FROM " . t('formulize_saved_views') . " WHERE sv_id = $viewOfA");
ok('A: hidden saved view column becomes the id, prefix kept', $sv1['sv_oldcols'] === "49,50,hiddencolumn_$era,94", $sv1['sv_oldcols']);
ok('A: {search_} token', strpos($sv1['sv_quicksearches'], '{search_artifacts_era}') !== false, $sv1['sv_quicksearches']);
ok('B: capitals and hyphens gone', storedHandle($yearEra) === 'artifactsyearera' OR storedHandle($yearEra) === 'artifacts_year_era', storedHandle($yearEra));
$yearEraHandle = storedHandle($yearEra);
ok('B: field follows', in_array($yearEraHandle, columns($D), true) AND !in_array('Artifacts-Year-Era', columns($D), true));
$file = $codeDir . "derived_$yearEraHandle.php";
ok('B: code file renamed', is_file($file) AND !in_array('derived_Artifacts-Year-Era.php', scandir($codeDir), true));
$code = is_file($file) ? file_get_contents($file) : '';
ok('A+B: variable in the code rewritten', strpos($code, '$artifacts_era .') !== false, $code);
ok('A+B: filter term in the code rewritten', strpos($code, "'artifacts_era/**/x/**/='") !== false, $code);
ok('A+B: array key left for a person', strpos($code, "\$row['Artifacts_Era']") !== false, $code);
ok('A+B: that array key is reported', strpos($output, 'Artifacts_Era') !== false AND strpos($output, 'still used in custom code') !== false, $output);
ok('E: stored handle untouched', storedHandle($acquired) === 'Artifacts_Date_Of_Acquisition', storedHandle($acquired));
ok('E: moved field untouched', in_array('zz_moved', columns($D), true));
ok('E: revisions field untouched', in_array('artifacts_date_of_acquisition', columns($R), true) AND !in_array('Artifacts_Date_Of_Acquisition', columns($R), true));
ok('E: reported as a problem', strpos($output, 'could not be fixed') !== false AND strpos($output, 'Artifacts_Date_Of_Acquisition') !== false, $output);
$twinHandle = storedHandle($twin);
ok('F: gets a handle of its own', $twinHandle !== 'artifacts_height' AND strpos($twinHandle, 'artifacts_height') === 0 AND $twinHandle === strtolower($twinHandle), $twinHandle);
ok('F: its field follows', in_array($twinHandle, columns(t('formulize_donors')), true), columns(t('formulize_donors')));
ok('F: the other element is left alone', storedHandle($height) === 'artifacts_height' AND in_array('artifacts_height', columns($D), true));
ok('C: stale token untouched at 27', strpos(caption($height), '{Artifacts_Short_Name}') !== false, caption($height));

echo "\n== second run, as an update from 26: stale references too\n";
sq("ALTER TABLE $D CHANGE zz_moved Artifacts_Date_Of_Acquisition date DEFAULT NULL"); // the person mends E
sq("ALTER TABLE $R CHANGE artifacts_date_of_acquisition Artifacts_Date_Of_Acquisition date DEFAULT NULL");
list($result, $output) = run(26);
echo $output;
ok('returned true', $result === true);
ok('E: tried again, and now done', storedHandle($acquired) === 'artifacts_date_of_acquisition' AND in_array('artifacts_date_of_acquisition', columns($D), true) AND in_array('artifacts_date_of_acquisition', columns($R), true), storedHandle($acquired));
$bothCode = file_get_contents($file);
ok('BOTH: a working variable keeps its name beside a stale capital spelling', strpos($bothCode, '$both = $artifacts_short_name . $artifacts_short_name;') !== false, $bothCode);
ok('BOTH: and beside a stale hyphen spelling', strpos($bothCode, '$hyphen = $artifacts_depth . $artifacts_depth;') !== false, $bothCode);
ok('BOTH: nothing moved to _1', strpos($bothCode, 'artifacts_short_name_1') === false AND strpos($bothCode, 'artifacts_depth_1') === false, $bothCode);
ok('C: stale {token} rewritten', strpos(caption($height), '{artifacts_short_name}') !== false, caption($height));
$sv2 = one("SELECT sv_oldcols, sv_sort FROM " . t('formulize_saved_views') . " WHERE sv_id = $viewOfC");
ok('C: stale hidden column becomes the id', strpos($sv2['sv_oldcols'], "hiddencolumn_$shortName,") === 0, $sv2['sv_oldcols']);
ok('C: hyphenated sort becomes the id', $sv2['sv_sort'] === (string) $depth, $sv2['sv_sort']);
$subject = one("SELECT not_cons_subject FROM " . t('formulize_notification_conditions') . " WHERE not_cons_id = $notification")['not_cons_subject'];
ok('C: hyphenated tag rewritten, the correct tag left', $subject === 'Now {ELEMENT_ARTIFACTS_WIDTH} and {ELEMENT_ARTIFACTS_YEAR}', $subject);
ok('D: caption words left alone', caption($idNumber) === 'See Artifacts_Id_Number and Artifacts-Id-Number here', caption($idNumber));
$stored = one("SELECT fundamental_filters FROM " . t('formulize_screen_listofentries') . " WHERE sid = " . intval($filterRow['sid']))['fundamental_filters'];
ok('D: condition term left alone', unserialize($stored)[2][0] === 'Artifacts_Id_Number', $stored);
ok('D: not reported as updated', strpos($output, 'written as') === false OR strpos($output, 'Artifacts_Id_Number') === false, $output);
ok('D: artifacts_year tag not reported as stale', strpos($output, 'ARTIFACTS_YEAR') === false, $output);

echo "\n== third run: nothing left\n";
list($result, $output) = run(26);
echo "[output: " . strlen(trim($output)) . " characters]\n$output";
ok('returned true', $result === true);
$found = formulize_find_misspelled_element_handles();
ok('no misspellings left', $found[0] === array() AND $found[1] === array(), $found);
ok('no stale spellings left', formulize_find_stale_handle_spellings() === array(), formulize_find_stale_handle_spellings());
$remaining = strpos($output, 'Renamed') === false AND strpos($output, 'Updated references') === false;
ok('nothing renamed', $remaining, $output);

echo "\n== the whole patch function, as the update runs it\n";
ob_start();
$whole = formulize_patch_002_always_run(26, 27);
$output = strip_tags(ob_get_clean());
ok('formulize_patch_002_always_run returned true', $whole === true, substr($output, 0, 600));

echo "\n" . ($failures ? "$failures FAILED\n" : "ALL PASSED\n");
exit($failures ? 1 : 0);
