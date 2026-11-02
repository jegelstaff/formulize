<?php
/**
 * Where an element is referred to, worked out by formulizeElementReferences from the declaration in
 * xoops_version.php and each element type's $eleValueReferences - and what deleting, renaming, and converting
 * old handles to ids does at each of those places.
 *
 * A standalone logic test: it loads the real class and the real declarations, and runs them against an
 * in-memory stand-in for the database, so it needs nothing but PHP. The element type handlers it needs are
 * stand-ins too, each carrying the $eleValueReferences copied out of the real class file, so a change to a
 * declaration is what gets tested.
 *
 * WHAT THIS COVERS
 * 1. The usage report and the clean up after a deletion, for the same fixtures as PART 2 and 3 of
 *    element_reference_integrity_test.php, which checks the same things against a built system.
 * 2. A rename changes only the positions declared to hold a reference: a condition's element but not its
 *    term, a list column but not its search value, a custom button's element but not its value. Legacy
 *    handles in settings Formulize now saves as ids become ids; tokens, {search_handle} tokens and
 *    notification tags take the new handle; ids are left alone.
 * 3. Converting old handles to ids without a rename, and what a numeric legacy handle does.
 * 4. What formulizeElementHandleCode rewrites in code - the handle given to a display function, a filter term, the
 *    variables Formulize names after a handle, moving a variable of the author's own out of the way - and what it
 *    leaves for a person, which the usage report marks and a rename lists.
 *
 * Usage:
 *   php tests/element_references_test.php
 *
 * @package Formulize
 * @subpackage tests
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$repo = dirname(__DIR__);
// the stand-in class files are written somewhere of their own, which XOOPS_ROOT_PATH points at
$stubRoot = sys_get_temp_dir().'/formulize_element_references_test';
@mkdir($stubRoot.'/modules/formulize/class', 0777, true);
define('XOOPS_ROOT_PATH', $stubRoot);
// ---- constants the declarations use ----
define('ELE_VALUE_SELECT_OPTIONS', 2);
define('ELE_VALUE_SELECT_LINK_FILTERS', 5);
define('ELE_VALUE_SELECT_LINK_ALTLISTELEMENTS', 10);
define('ELE_VALUE_SELECT_LINK_ALTEXPORTELEMENTS', 11);
define('ELE_VALUE_SELECT_LINK_SORT', 12);
define('ELE_VALUE_SELECT_LINK_ALTFORMELEMENTS', 17);
define('ELE_VALUE_SELECT_LINK_LIMITBYELEMENT', 'optionsLimitByElement');
define('ELE_VALUE_SELECT_LINK_LIMITBYELEMENTFILTER', 'optionsLimitByElementFilter');
define('ELE_VALUE_SELECT_LINK_SOURCEMAPPINGS', 'linkedSourceMappings');
define('ELE_VALUE_TEXT_DEFAULTVALUE', 2);
define('EV_MULTIPLE_LIST_COLUMNS', 10);
define('EV_MULTIPLE_FORM_COLUMNS', 17);
define('EV_MULTIPLE_SPREADSHEET_COLUMNS', 11);
foreach(array('CALENDAR_SCREENS', 'FORM_SCREENS', 'LIST_SCREENS', 'MAP_SCREENS', 'SCREEN_ADDRESSES', 'OTHER_ELEMENTS',
	'SAVED_VIEWS', 'GROUP_FILTERS', 'FORM_SETTINGS', 'RELATIONSHIPS', 'NOTIFICATIONS') as $section) {
	define('_AM_ELE_USAGE_SECTION_'.$section, 'SECTION '.$section);
}

// ---- stub class files, with the ele_value declarations copied out of the real ones ----
function declarationOf($file) {
	$src = file_get_contents($file);
	if(!preg_match('/public static \$eleValueReferences = (array\(.*?\n\t\));/s', $src, $m)) {
		die("no declaration in $file\n");
	}
	return $m[1];
}
$classDir = $repo.'/modules/formulize/class/';
$stubs = array(
	'selectLinked' => declarationOf($classDir.'selectElement.php'),
	'grid' => declarationOf($classDir.'gridElement.php'),
	'subformListings' => declarationOf($classDir.'subformListingsElement.php'),
	'text' => declarationOf($classDir.'textElement.php'),
	'checkboxLinked' => declarationOf($classDir.'checkboxLinkedElement.php'),
	'derived' => declarationOf($classDir.'derivedElement.php'),
);
foreach($stubs as $type => $declaration) {
	file_put_contents($stubRoot."/modules/formulize/class/{$type}Element.php",
		"<?php\nclass formulize".ucfirst($type)."ElementHandler { public static \$eleValueReferences = $declaration; }\n");
}
file_put_contents($stubRoot.'/modules/formulize/class/savedViews.php', "<?php\nif(!class_exists('formulizeSavedViewsHandler')) { class formulizeSavedViewsHandler {
	const SEARCH_DELIMITER = '&*=%4#'; const CALCULATION_DELIMITER = '/'; const HIDDEN_COLUMN_PREFIX = 'hiddencolumn_'; } }\n");
file_put_contents($stubRoot.'/modules/formulize/class/calendarScreen.php', "<?php\nif(!class_exists('formulizeCalendarScreenDataset')) { class formulizeCalendarScreenDataset {
	public \$vars = array(); function setVar(\$k, \$v) { \$this->vars[\$k] = \$v; } function getVar(\$k) { return isset(\$this->vars[\$k]) ? \$this->vars[\$k] : null; } } }\n");
require $stubRoot.'/modules/formulize/class/calendarScreen.php';

// how a handle is written in code, and what a rename rewrites there - the real class, which stands on its own
require $repo.'/modules/formulize/class/elementHandleCode.php';

// ---- the declaration from xoops_version.php ----
$xv = file_get_contents($repo.'/modules/formulize/xoops_version.php');
$start = strpos($xv, "\$modversion['formulize_element_references'] = array(");
$end = strpos($xv, "\n);", $start);
if($start === false OR $end === false) { die("declaration not found\n"); }
$modversion = array();
eval(substr($xv, $start, $end - $start + 3));
$GLOBALS['__declaration'] = $modversion['formulize_element_references'];

class FakeModule { function getInfo($name) { return $name == 'formulize_element_references' ? $GLOBALS['__declaration'] : false; } }
class FakeModuleHandler { function getByDirname($d) { return new FakeModule(); } }
function xoops_gethandler($name) { return new FakeModuleHandler(); }

// ---- an in-memory database: SELECT returns every row of the table, UPDATE writes back ----
class FakeDB {
	public $tables = array();
	private $quoted = array();
	function prefix($t) { return 'x_'.$t; }
	function quoteString($v) { $this->quoted[] = (string) $v; return 'Q#'.(count($this->quoted) - 1).'#'; }
	function query($sql) {
		if(stripos(ltrim($sql), 'select') !== 0 AND stripos(ltrim($sql), 'update') !== 0) {
			// the usage report is drawn on a GET request, when the real database refuses everything but a SELECT
			die("query() is given only SELECTs while reading, as the database proxy allows on a GET request: $sql\n");
		}
		if(preg_match('/^SELECT COLUMN_NAME AS Field FROM information_schema\.COLUMNS .* TABLE_NAME = Q#(\d+)# /', $sql, $m)) {
			$m[1] = substr($this->quoted[$m[1]], 2); // the quoted, prefixed table name, without its 'x_'
			$fields = array();
			foreach(isset($this->tables[$m[1]]) ? $this->tables[$m[1]] : array() as $row) { $fields += array_flip(array_keys($row)); }
			return array_map(function($field) { return array('Field' => $field); }, array_keys($fields));
		}
		if(preg_match('/^SELECT .* FROM x_([a-z_]+) t /s', $sql, $m)) {
			return array_values(isset($this->tables[$m[1]]) ? $this->tables[$m[1]] : array());
		}
		if(preg_match('/^UPDATE x_([a-z_]+) SET (.*) WHERE `([a-z_]+)` = (\d+)$/s', $sql, $m)) {
			preg_match_all('/`([a-zA-Z_]+)` = Q#(\d+)#/', $m[2], $sets, PREG_SET_ORDER);
			foreach($this->tables[$m[1]] as $i => $row) {
				if(intval($row[$m[3]]) === intval($m[4])) {
					foreach($sets as $set) {
						$this->tables[$m[1]][$i][$set[1]] = $this->quoted[$set[2]];
					}
				}
			}
			return true;
		}
		die("unexpected SQL: $sql\n");
	}
	function queryF($sql) { return $this->query($sql); }
	function fetchArray(&$result) { return is_array($result) ? array_shift($result) : false; }
	function row($table, $key, $id) {
		foreach($this->tables[$table] as $row) { if(intval($row[$key]) === intval($id)) { return $row; } }
		return null;
	}
}
$GLOBALS['xoopsDB'] = new FakeDB();

require $repo.'/modules/formulize/class/elementReferences.php';

$GLOBALS['__pass'] = 0; $GLOBALS['__fail'] = 0;
function check($label, $got, $want) {
	if($got === $want) { $GLOBALS['__pass']++; return; }
	$GLOBALS['__fail']++;
	printf("  FAIL %s\n       got:  %s\n       want: %s\n", $label, var_export($got, true), var_export($want, true));
}
function ok($label, $condition, $detail = '') {
	if($condition) { $GLOBALS['__pass']++; return; }
	$GLOBALS['__fail']++;
	printf("  FAIL %s%s\n", $label, $detail === '' ? '' : "\n       $detail");
}

// apply a deletion the way formulizeElementsHandler::removeElementReferences() does
function removeReferences($refs) {
	global $xoopsDB;
	foreach($refs->references() as $reference) {
		if(!$reference['table'] OR !$reference['updates']) { continue; }
		$sets = array();
		foreach($reference['updates'] as $column => $value) { $sets[] = "`$column` = ".$xoopsDB->quoteString((string) $value); }
		$xoopsDB->query("UPDATE x_".$reference['table']." SET ".implode(', ', $sets)." WHERE `".$reference['key_column']."` = ".$reference['key_value']);
	}
}
function sections($refs) {
	$sections = array();
	foreach($refs->references() as $reference) { $sections[$reference['section']][] = $reference['description']; }
	return $sections;
}

function resetTables($tables) { global $xoopsDB; $xoopsDB->tables = $tables; (new ReflectionProperty("formulizeElementReferences", "tableColumns"))->setValue(null, array()); }
global $xoopsDB;
$fid = 7;
$id = 500;
$handle = 'zz_reference_integrity_fixture';

$dataset = new formulizeCalendarScreenDataset();
$dataset->setVar('fid', $fid);
$dataset->setVar('datehandle', $handle);

resetTables(array(
	'formulize_saved_views' => array(
		array('sv_id' => 1, 'sv_name' => 'reference integrity fixture', 'sv_oldcols' => "first_col,$handle,last_col",
			'sv_quicksearches' => 'search1&*=%4#!persist!&*=%4#search3', 'sv_sort' => $handle, 'sv_calc_cols' => "11/$id/22",
			'sv_calc_calcs' => 'sum/avg/count', 'sv_calc_blanks' => 'all/noblanks/all', 'sv_calc_grouping' => '/grp/'),
		array('sv_id' => 2, 'sv_name' => 'reference integrity hidden fixture', 'sv_oldcols' => "a,hiddencolumn_$handle,b",
			'sv_quicksearches' => 'sa&*=%4#!hidden!&*=%4#sb', 'sv_sort' => 'a', 'sv_calc_cols' => '', 'sv_calc_calcs' => '',
			'sv_calc_blanks' => '', 'sv_calc_grouping' => ''),
	),
	'formulize_screen_map' => array(
		array('sid' => 10, 'title' => 'Reference integrity map', 'lat_element' => "$id", 'lng_element' => '999999', 'label_element' => $handle,
			'description_element' => '', 'columns' => serialize(array(array('other_col', '', 'Box'), array($handle, '', 'Box'))),
			'fundamental_filters' => serialize(array(0 => array($id, 'zzz'), 1 => array('=', '='), 2 => array('x', 'y'), 3 => array('all', 'all')))),
	),
	'formulize_screen_form' => array(
		array('sid' => 11, 'title' => 'Reference integrity form', 'formelements' => serialize(array(999999, $id, 999998)),
			'elementdefaults' => serialize(array(999999 => 'keep', $id => 'go'))),
		array('sid' => 12, 'title' => 'Reference integrity only', 'formelements' => serialize(array($id)), 'elementdefaults' => 'a:0:{}'),
	),
	'formulize_screen_calendar' => array(
		array('sid' => 13, 'title' => 'Reference integrity calendar', 'datasets' => serialize(array($dataset)), 'toptemplate' => '', 'bottomtemplate' => ''),
	),
	'formulize_screen' => array(
		array('sid' => 14, 'title' => 'Reference integrity address', 'rewriteruleElement' => $id),
	),
	'formulize_screen_multipage' => array(
		array('sid' => 15, 'title' => 'Reference integrity legacy',
			'pages' => serialize(array(array(999997, $id))),
			'conditions' => serialize(array(
				0 => array('pagecons' => 'yes', 'details' => array('elements' => array('keep_me', $id), 'ops' => array('=', '='), 'terms' => array('a', 'b'))),
				1 => array(0 => array($id, 'other'), 1 => array('='), 2 => array('x', 'y'), 3 => array('all', 'all')))),
			'elementdefaults' => serialize(array($id => 'default', 999997 => 'keep'))),
		array('sid' => 16, 'title' => 'Reference integrity pages',
			'pages' => serialize(array(0 => array(999996, $id), 1 => array($id), 2 => array(999995))),
			'conditions' => serialize(array(0 => array(), 1 => array(), 2 => array())), 'elementdefaults' => 'a:0:{}'),
	),
	'formulize' => array(
		array('ele_id' => $id, 'ele_type' => 'text', 'ele_handle' => $handle, 'id_form' => $fid, 'ele_caption' => "Me {{$handle}}", 'ele_desc' => '',
			'ele_filtersettings' => '', 'ele_disabledconditions' => '', 'ele_dynamicdefault_conditions' => '', 'ele_dynamicdefault_source' => 0, 'ele_value' => 'a:0:{}'),
		array('ele_id' => 601, 'ele_type' => 'selectLinked', 'ele_handle' => 'zz_fixture_linked', 'id_form' => $fid, 'ele_caption' => '', 'ele_desc' => '',
			'ele_filtersettings' => '', 'ele_disabledconditions' => '', 'ele_dynamicdefault_conditions' => '', 'ele_dynamicdefault_source' => 0,
			'ele_value' => serialize(array(
				ELE_VALUE_SELECT_LINK_SORT => $id,
				ELE_VALUE_SELECT_LINK_ALTLISTELEMENTS => array(999991, $id),
				ELE_VALUE_SELECT_LINK_SOURCEMAPPINGS => array(array('thisForm' => $id, 'sourceForm' => 999990), array('thisForm' => '{'.$handle.'}', 'sourceForm' => 999989))))),
		array('ele_id' => 602, 'ele_type' => 'grid', 'ele_handle' => 'zz_fixture_grid', 'id_form' => $fid, 'ele_caption' => '', 'ele_desc' => '',
			'ele_filtersettings' => '', 'ele_disabledconditions' => '', 'ele_dynamicdefault_conditions' => '', 'ele_dynamicdefault_source' => 0,
			'ele_value' => serialize(array(4 => $id))),
		array('ele_id' => 603, 'ele_type' => 'subformListings', 'ele_handle' => 'zz_fixture_subform', 'id_form' => $fid, 'ele_caption' => '', 'ele_desc' => '',
			'ele_filtersettings' => '', 'ele_disabledconditions' => '', 'ele_dynamicdefault_conditions' => '', 'ele_dynamicdefault_source' => 0,
			'ele_value' => serialize(array(1 => '999992,'.$id,
				7 => array(0 => array($id, 'keep_me'), 1 => array('=', '='), 2 => array('a', 'b'), 3 => array('all', 'all')),
				'SortingElement' => $id))),
		array('ele_id' => 604, 'ele_type' => 'text', 'ele_handle' => 'zz_fixture_dependent', 'id_form' => $fid, 'ele_caption' => '', 'ele_desc' => '',
			'ele_filtersettings' => '', 'ele_disabledconditions' => '',
			'ele_dynamicdefault_conditions' => serialize(array(0 => array($id), 1 => array('='), 2 => array('v'), 3 => array('all'))),
			'ele_dynamicdefault_source' => $id, 'ele_value' => 'a:0:{}'),
	),
));

$refs = new formulizeElementReferences($id, $handle, $fid);

echo "== the report, before anything changes\n";
$sections = sections($refs);
ok('report mentions the map screen', isset($sections[_AM_ELE_USAGE_SECTION_MAP_SCREENS]));
ok('report mentions the calendar screen', isset($sections[_AM_ELE_USAGE_SECTION_CALENDAR_SCREENS]));
ok('report mentions the address bar screen', isset($sections[_AM_ELE_USAGE_SECTION_SCREEN_ADDRESSES]));
$others = isset($sections[_AM_ELE_USAGE_SECTION_OTHER_ELEMENTS]) ? $sections[_AM_ELE_USAGE_SECTION_OTHER_ELEMENTS] : array();
ok('report mentions the linked list that sorts by it', count(preg_grep('/zz_fixture_linked.*sorted by/', $others)) === 1, implode(' | ', $others));
ok('report mentions the grid', count(preg_grep('/zz_fixture_grid.*initial values/', $others)) === 1, implode(' | ', $others));
ok('report mentions the subform', count(preg_grep('/zz_fixture_subform.*one of the elements it shows/', $others)) === 1, implode(' | ', $others));
ok('report says the source mapping is removed', count(preg_grep('/a source mapping, which is removed/', $others)) === 1, implode(' | ', $others));
ok('report does not list the element itself', count(preg_grep("/'$handle'/", $others)) === 0, implode(' | ', $others));
check('report mentions both saved views', count(isset($sections[_AM_ELE_USAGE_SECTION_SAVED_VIEWS]) ? $sections[_AM_ELE_USAGE_SECTION_SAVED_VIEWS] : array()), 2);
$formScreens = isset($sections[_AM_ELE_USAGE_SECTION_FORM_SCREENS]) ? $sections[_AM_ELE_USAGE_SECTION_FORM_SCREENS] : array();
ok('report warns a form screen will show nothing', count(preg_grep('/the only element chosen to appear/', $formScreens)) === 1, implode(' | ', $formScreens));
$pagesItem = implode(' | ', preg_grep('/Reference integrity pages/', $formScreens));
ok('report names the page the element shares', strpos($pagesItem, 'on page 1') !== false, $pagesItem);
ok('report says what becomes of the page it is alone on', strpos($pagesItem, 'the only element on page 2, so that page will be empty') !== false, $pagesItem);
echo "  sample: ".implode("\n          ", array_slice(array_merge($others, $formScreens), 0, 4))."\n";

echo "== the clean up\n";
removeReferences($refs);
$view = $xoopsDB->row('formulize_saved_views', 'sv_id', 1);
check('view: the column is gone', $view['sv_oldcols'], 'first_col,last_col');
check('view: its search with it', $view['sv_quicksearches'], 'search1&*=%4#search3');
check('view: sort falls back', $view['sv_sort'], '');
check('view: calculation column gone', $view['sv_calc_cols'], '11/22');
check('view: calcs in step', $view['sv_calc_calcs'], 'sum/count');
check('view: blanks in step', $view['sv_calc_blanks'], 'all/all');
check('view: grouping in step', $view['sv_calc_grouping'], '/');
$hidden = $xoopsDB->row('formulize_saved_views', 'sv_id', 2);
check('view: hiddencolumn_ column removed', $hidden['sv_oldcols'], 'a,b');
check('view: and its search', $hidden['sv_quicksearches'], 'sa&*=%4#sb');
$map = $xoopsDB->row('formulize_screen_map', 'sid', 10);
check('map: latitude cleared', $map['lat_element'], '');
check('map: other element left', $map['lng_element'], '999999');
check('map: label cleared', $map['label_element'], '');
check('map: column removed', unserialize($map['columns']), array(array('other_col', '', 'Box')));
check('map: filter removed', unserialize($map['fundamental_filters']), array(0 => array('zzz'), 1 => array('='), 2 => array('y'), 3 => array('all')));
$form = $xoopsDB->row('formulize_screen_form', 'sid', 11);
check('form screen: removed from chosen list', unserialize($form['formelements']), array(999999, 999998));
check('form screen: default goes', unserialize($form['elementdefaults']), array(999999 => 'keep'));
$only = $xoopsDB->row('formulize_screen_form', 'sid', 12);
check('form screen: only-element screen left alone', unserialize($only['formelements']), array($id));
$cal = $xoopsDB->row('formulize_screen_calendar', 'sid', 13);
$datasets = unserialize($cal['datasets']);
ok('calendar: still objects', $datasets[0] instanceof formulizeCalendarScreenDataset);
check('calendar: date handle cleared', $datasets[0]->getVar('datehandle'), '');
check('calendar: rest survives', intval($datasets[0]->getVar('fid')), $fid);
check('address bar: falls back to entry id', intval($xoopsDB->row('formulize_screen', 'sid', 14)['rewriteruleElement']), 0);
$legacy = $xoopsDB->row('formulize_screen_multipage', 'sid', 15);
$legacyAfter = unserialize($legacy['conditions']);
check('legacy conditions: element dropped, shape kept', $legacyAfter[0]['details'], array('elements' => array('keep_me'), 'ops' => array('='), 'terms' => array('a')));
check('legacy conditions: pagecons survives', $legacyAfter[0]['pagecons'], 'yes');
check('current conditions: dropped, missing operator filled', $legacyAfter[1], array(0 => array('other'), 1 => array('='), 2 => array('y'), 3 => array('all')));
check('multipage: element default removed', unserialize($legacy['elementdefaults']), array(999997 => 'keep'));
check('multipage: pages left for removeElementsFromScreens()', unserialize($legacy['pages']), array(array(999997, $id)));
$dependent = $xoopsDB->row('formulize', 'ele_id', 604);
check('dynamic default: source cleared', intval($dependent['ele_dynamicdefault_source']), 0);
check('dynamic default: conditions go too', unserialize($dependent['ele_dynamicdefault_conditions']), array());
$linked = unserialize($xoopsDB->row('formulize', 'ele_id', 601)['ele_value']);
check('linked list: sort set to none', $linked[ELE_VALUE_SELECT_LINK_SORT], 'none');
check('linked list: out of alt list elements', $linked[ELE_VALUE_SELECT_LINK_ALTLISTELEMENTS], array(999991));
check('linked list: the mapping on the element goes as a whole, a typed value that looks like its handle stays', $linked[ELE_VALUE_SELECT_LINK_SOURCEMAPPINGS], array(1 => array('thisForm' => '{'.$handle.'}', 'sourceForm' => 999989)));
$grid = unserialize($xoopsDB->row('formulize', 'ele_id', 602)['ele_value']);
check('grid: cleared', $grid[4], 'none');
$subform = unserialize($xoopsDB->row('formulize', 'ele_id', 603)['ele_value']);
check('subform: removed from comma list, still a string', $subform[1], '999992');
check('subform: out of its conditions', $subform[7], array(0 => array('keep_me'), 1 => array('='), 2 => array('b'), 3 => array('all')));
check('subform: sort cleared', $subform['SortingElement'], 'none');
check('the element itself is untouched', $xoopsDB->row('formulize', 'ele_id', $id)['ele_caption'], "Me {{$handle}}");

echo "== rename: only declared positions change\n";
resetTables(array(
	'formulize' => array(
		array('ele_id' => 700, 'ele_type' => 'text', 'ele_handle' => 'approved', 'id_form' => 3, 'ele_caption' => 'Approved {approved}', 'ele_desc' => '',
			'ele_filtersettings' => '', 'ele_disabledconditions' => '', 'ele_dynamicdefault_conditions' => '', 'ele_dynamicdefault_source' => 0, 'ele_value' => 'a:0:{}'),
		array('ele_id' => 701, 'ele_type' => 'text', 'ele_handle' => 'notes', 'id_form' => 3, 'ele_caption' => 'See {approved}, not approved', 'ele_desc' => '',
			// approved = 'yes' AND status = 'approved': only the first names the element
			'ele_filtersettings' => serialize(array(0 => array('approved', 702), 1 => array('=', '='), 2 => array('yes', 'approved'), 3 => array('all', 'all'))),
			'ele_disabledconditions' => serialize(array(0 => array(700), 1 => array('='), 2 => array('{approved}'), 3 => array('all'))),
			'ele_dynamicdefault_conditions' => '', 'ele_dynamicdefault_source' => 700,
			'ele_value' => serialize(array(ELE_VALUE_TEXT_DEFAULTVALUE => 'Was {approved} and approved'))),
		array('ele_id' => 702, 'ele_type' => 'selectLinked', 'ele_handle' => 'status', 'id_form' => 3, 'ele_caption' => '', 'ele_desc' => '',
			'ele_filtersettings' => '', 'ele_disabledconditions' => '', 'ele_dynamicdefault_conditions' => '', 'ele_dynamicdefault_source' => 0,
			'ele_value' => serialize(array(ELE_VALUE_SELECT_OPTIONS => '3#*=:*approved', ELE_VALUE_SELECT_LINK_SORT => 'approved',
				ELE_VALUE_SELECT_LINK_ALTLISTELEMENTS => array('approved', 700, 'other'),
				ELE_VALUE_SELECT_LINK_SOURCEMAPPINGS => array(array('thisForm' => '{approved}', 'sourceForm' => 'approved'))))),
	),
	'formulize_saved_views' => array(
		array('sv_id' => 3, 'sv_name' => 'v', 'sv_oldcols' => 'approved,hiddencolumn_approved,approved_by', 'sv_quicksearches' => 'approved&*=%4#{search_approved}&*=%4#x',
			'sv_sort' => 'approved', 'sv_calc_cols' => '700', 'sv_calc_calcs' => 'sum', 'sv_calc_blanks' => 'all', 'sv_calc_grouping' => '700!@^%*12'),
	),
	'formulize_screen_listofentries' => array(
		array('sid' => 20, 'title' => 'List', 'advanceview' => serialize(array(array('approved', 'approved', 'ASC', 'box'))), 'hiddencolumns' => serialize(array(700)),
			'decolumns' => 'a:0:{}', 'fundamental_filters' => '', 'customactions' => serialize(array(1 => array('name' => 'approved', 0 => array('element' => 'approved', 'value' => 'approved'))))),
	),
	'formulize_notification_conditions' => array(
		array('not_cons_id' => 30, 'not_cons_fid' => 3, 'form_title' => 'F', 'not_cons_con' => 'all', 'not_cons_elementuids' => 0, 'not_cons_linkcreator' => 0,
			'not_cons_elementemail' => 0, 'not_cons_subject' => 'Now {ELEMENT_APPROVED} and {ELEMENTAPPROVED}, APPROVED'),
	),
));
$renamer = new formulizeElementReferences(700, 'approved', 3);
$renamer->rename('is_approved');
$notes = $xoopsDB->row('formulize', 'ele_id', 701);
check('conditions: the handle at position 0 becomes the id, the term "approved" is untouched',
	unserialize($notes['ele_filtersettings']), array(0 => array(700, 702), 1 => array('=', '='), 2 => array('yes', 'approved'), 3 => array('all', 'all')));
check('conditions: an id is left as an id, a {handle} term is renamed',
	unserialize($notes['ele_disabledconditions']), array(0 => array(700), 1 => array('='), 2 => array('{is_approved}'), 3 => array('all')));
check('caption: the token is renamed, the word is not', $notes['ele_caption'], 'See {is_approved}, not approved');
check('dynamic default source: an id, untouched', $notes['ele_dynamicdefault_source'], 700);
check('text default: token renamed, word not', unserialize($notes['ele_value'])[ELE_VALUE_TEXT_DEFAULTVALUE], 'Was {is_approved} and approved');
check('the element\'s own caption token is renamed too', $xoopsDB->row('formulize', 'ele_id', 700)['ele_caption'], 'Approved {is_approved}');
$status = unserialize($xoopsDB->row('formulize', 'ele_id', 702)['ele_value']);
check('linked source: the legacy handle becomes the id', $status[ELE_VALUE_SELECT_OPTIONS], '3#*=:*700');
check('linked sort: holds ids only, so a word that is the handle stays', $status[ELE_VALUE_SELECT_LINK_SORT], 'approved');
check('alt list: the handle becomes the id, the id and other entries kept', $status[ELE_VALUE_SELECT_LINK_ALTLISTELEMENTS], array(700, 700, 'other'));
check('source mapping: holds ids only, so values a person typed stay as they are', $status[ELE_VALUE_SELECT_LINK_SOURCEMAPPINGS], array(array('thisForm' => '{approved}', 'sourceForm' => 'approved')));
$view = $xoopsDB->row('formulize_saved_views', 'sv_id', 3);
check('view columns: legacy handles become the id, prefix kept, approved_by not touched', $view['sv_oldcols'], '700,hiddencolumn_700,approved_by');
check('view searches: {search_handle} renamed, the term approved not', $view['sv_quicksearches'], 'approved&*=%4#{search_is_approved}&*=%4#x');
check('view sort: legacy handle becomes the id', $view['sv_sort'], '700');
check('view calc ids untouched', $view['sv_calc_cols'].'|'.$view['sv_calc_grouping'], '700|700!@^%*12');
$list = $xoopsDB->row('formulize_screen_listofentries', 'sid', 20);
check('advanceview: the handle at position 0 becomes the id, the search value is untouched', unserialize($list['advanceview']), array(array(700, 'approved', 'ASC', 'box')));
check('custom button: the effect element becomes the id, its value and the button name untouched',
	unserialize($list['customactions']), array(1 => array('name' => 'approved', 0 => array('element' => 700, 'value' => 'approved'))));
check('notification tags renamed, the word not', $xoopsDB->row('formulize_notification_conditions', 'not_cons_id', 30)['not_cons_subject'],
	'Now {ELEMENT_IS_APPROVED} and {ELEMENTIS_APPROVED}, APPROVED');

echo "== mentions\n";
$mentions = (new formulizeElementReferences(700, 'is_approved', 3))->mentions();
$described = array_map(function($m) { return $m['table'].':'.$m['key_value']; }, $mentions);
sort($described);
check('mentions: caption tokens, condition terms, text default, notification tag - not the element itself',
	$described, array('formulize:701', 'formulize_notification_conditions:30', 'formulize_saved_views:3'));

echo "== a deletion after the rename, with numeric references and a grouping\n";
$deleter = new formulizeElementReferences(700, 'is_approved', 3);
removeReferences($deleter);
$view = $xoopsDB->row('formulize_saved_views', 'sv_id', 3);
check('calc column by id removed', $view['sv_calc_cols'], '');
check('its grouping goes with it', $view['sv_calc_grouping'], '');
$list = $xoopsDB->row('formulize_screen_listofentries', 'sid', 20);
check('hidden column by id removed', unserialize($list['hiddencolumns']), array());
check('custom button effect removed, button kept', unserialize($list['customactions']), array(1 => array('name' => 'approved')));
check('notification left for a person', $xoopsDB->row('formulize_notification_conditions', 'not_cons_id', 30)['not_cons_subject'],
	'Now {ELEMENT_IS_APPROVED} and {ELEMENTIS_APPROVED}, APPROVED');

echo "== a grouping that names the element, inside another column's calculation\n";
resetTables(array('formulize_saved_views' => array(
	array('sv_id' => 4, 'sv_name' => 'g', 'sv_oldcols' => 'x', 'sv_quicksearches' => '', 'sv_sort' => '', 'sv_calc_cols' => '11/22',
		'sv_calc_calcs' => 'sum,avg/count', 'sv_calc_blanks' => 'all,all/all', 'sv_calc_grouping' => '700!@^%*12,12/700'),
)));
removeReferences(new formulizeElementReferences(700, 'is_approved', 3));
check('grouping id removed where it appears, columns untouched', $xoopsDB->row('formulize_saved_views', 'sv_id', 4)['sv_calc_grouping'].'|'.$xoopsDB->row('formulize_saved_views', 'sv_id', 4)['sv_calc_cols'], '12,12/|11/22');

echo "== converting legacy handles to ids, without renaming\n";
resetTables(array(
	'formulize' => array(
		array('ele_id' => 800, 'ele_type' => 'text', 'ele_handle' => 'name', 'id_form' => 5, 'ele_caption' => 'Hello {name}', 'ele_desc' => '',
			'ele_filtersettings' => '', 'ele_disabledconditions' => '', 'ele_dynamicdefault_conditions' => '', 'ele_dynamicdefault_source' => 0, 'ele_value' => 'a:0:{}'),
		array('ele_id' => 801, 'ele_type' => 'text', 'ele_handle' => 'other', 'id_form' => 5, 'ele_caption' => '{name}', 'ele_desc' => '',
			'ele_filtersettings' => serialize(array(0 => array('name', 800), 1 => array('=', '='), 2 => array('name', '{name}'), 3 => array('all', 'all'))),
			'ele_disabledconditions' => '', 'ele_dynamicdefault_conditions' => '', 'ele_dynamicdefault_source' => 0, 'ele_value' => 'a:0:{}'),
	),
	'formulize_saved_views' => array(
		array('sv_id' => 5, 'sv_name' => 'v', 'sv_oldcols' => 'name,x', 'sv_quicksearches' => '', 'sv_sort' => 'name',
			'sv_calc_cols' => '', 'sv_calc_calcs' => '', 'sv_calc_blanks' => '', 'sv_calc_grouping' => ''),
	),
	'formulize_screen_map' => array(
		array('sid' => 40, 'title' => 'Map', 'lat_element' => 'name', 'lng_element' => '801', 'label_element' => '', 'description_element' => '',
			'columns' => serialize(array(array('name', '', 'Box'), array('creation_uid', '', 'Box'))), 'fundamental_filters' => ''),
	),
));
(new formulizeElementReferences(800, 'name', 5))->convertLegacyHandlesToIds();
$other = $xoopsDB->row('formulize', 'ele_id', 801);
check('to ids: the condition handle becomes the id, terms and tokens untouched',
	unserialize($other['ele_filtersettings']), array(0 => array(800, 800), 1 => array('=', '='), 2 => array('name', '{name}'), 3 => array('all', 'all')));
check('to ids: captions untouched', $other['ele_caption'], '{name}');
$view = $xoopsDB->row('formulize_saved_views', 'sv_id', 5);
check('to ids: saved view columns and sort become ids', $view['sv_oldcols'].'|'.$view['sv_sort'], '800,x|800');
$map = $xoopsDB->row('formulize_screen_map', 'sid', 40);
check('to ids: a map pin element becomes the id', $map['lat_element'], '800'); // a text column
check('to ids: map columns become ids, a metadata column is left', unserialize($map['columns']), array(array(800, '', 'Box'), array('creation_uid', '', 'Box')));

echo "== a rename where the old handle is a number\n";
resetTables(array(
	'formulize_saved_views' => array(
		array('sv_id' => 6, 'sv_name' => 'v', 'sv_oldcols' => '12,13', 'sv_quicksearches' => '', 'sv_sort' => '12',
			'sv_calc_cols' => '12', 'sv_calc_calcs' => 'sum', 'sv_calc_blanks' => 'all', 'sv_calc_grouping' => ''),
	),
	'formulize_screen_listofentries' => array(
		array('sid' => 21, 'title' => 'L', 'advanceview' => serialize(array(array(12, '', '', ''))), 'hiddencolumns' => 'a:0:{}',
			'decolumns' => 'a:0:{}', 'fundamental_filters' => '', 'customactions' => ''),
	),
));
(new formulizeElementReferences(12, '12', 5))->rename('given_name');
$view = $xoopsDB->row('formulize_saved_views', 'sv_id', 6);
check('numeric handle: a number in saved view columns and sort is already the id, and stays', $view['sv_oldcols'].'|'.$view['sv_sort'], '12,13|12');
check('numeric handle: the calculation, which holds ids, is left', $view['sv_calc_cols'], '12');
check('numeric handle: an advanceview id stays an id', unserialize($xoopsDB->row('formulize_screen_listofentries', 'sid', 21)['advanceview']), array(array(12, '', '', '')));

echo "== several declarations on one column, and the places a review of the declaration found missing\n";
resetTables(array(
	'formulize' => array(
		array('ele_id' => 900, 'ele_type' => 'text', 'ele_handle' => 'approved', 'id_form' => 3, 'ele_caption' => '', 'ele_desc' => '',
			'ele_filtersettings' => '', 'ele_disabledconditions' => '', 'ele_dynamicdefault_conditions' => '', 'ele_dynamicdefault_source' => 0, 'ele_value' => 'a:0:{}'),
		array('ele_id' => 901, 'ele_type' => 'grid', 'ele_handle' => 'g', 'id_form' => 3, 'ele_caption' => '', 'ele_desc' => '',
			'ele_filtersettings' => '', 'ele_disabledconditions' => '', 'ele_dynamicdefault_conditions' => '', 'ele_dynamicdefault_source' => 0,
			'ele_value' => serialize(array(1 => 'Row {approved},approved', 2 => 'Column one', 4 => 'none'))),
	),
	'formulize_saved_views' => array(
		array('sv_id' => 7, 'sv_name' => 'v', 'sv_oldcols' => 'x', 'sv_quicksearches' => '', 'sv_sort' => 'x,approved', 'sv_order' => 'SORT_ASC,SORT_DESC',
			'sv_pubfilters' => 'approved,x', 'sv_calc_cols' => '', 'sv_calc_calcs' => '', 'sv_calc_blanks' => '', 'sv_calc_grouping' => ''),
	),
	'formulize_screen_listofentries' => array(
		array('sid' => 22, 'title' => 'L', 'advanceview' => serialize(array(array('approved', '{approved}', '', 'Box'), array(901, 'approved', '', 'Box'))),
			'hiddencolumns' => 'a:0:{}', 'decolumns' => 'a:0:{}', 'fundamental_filters' => '',
			'customactions' => serialize(array(1 => array('name' => 'b', 0 => array('element' => 901, 'value' => '$value = display($entry, \'approved\');'))))),
	),
	'formulize_screen_map' => array(
		array('sid' => 41, 'title' => 'M', 'lat_element' => '', 'lng_element' => '', 'label_element' => '', 'description_element' => '',
			'columns' => serialize(array(array(901, '{search_approved}', 'Box'))), 'fundamental_filters' => ''),
	),
	'formulize_screen_multipage' => array(
		array('sid' => 23, 'title' => 'P', 'pages' => serialize(array(array('PHP', 'print $approved;'), array(901))), 'conditions' => 'a:0:{}', 'elementdefaults' => 'a:0:{}'),
		// an HTML page is printed as it is, so an attribute that is the handle is not a mention
		array('sid' => 25, 'title' => 'H', 'pages' => serialize(array(array('HTML', '<div class="approved">{approved}</div>'))), 'conditions' => 'a:0:{}', 'elementdefaults' => 'a:0:{}'),
	),
	'formulize_notification_conditions' => array(
		array('not_cons_id' => 31, 'not_cons_fid' => 3, 'form_title' => 'F',
			'not_cons_con' => serialize(array(serialize(array(900)), serialize(array('=')), serialize(array('{approved}')))),
			'not_cons_elementuids' => 0, 'not_cons_linkcreator' => 0, 'not_cons_elementemail' => 0, 'not_cons_subject' => ''),
	),
	'formulize_advanced_calculations' => array(
		array('acid' => 50, 'name' => 'c', 'input' => '', 'output' => '', 'steps' => serialize(array(array('description' => 'approved', 'sql' => 'SELECT approved_by FROM t', 'calculate' => ''))),
			'fltr_grps' => serialize(array(0 => array('form' => '900', 'handle' => 'f1'), 1 => array('form' => '901', 'handle' => 'f2')))),
		array('acid' => 51, 'name' => 'd', 'input' => '', 'output' => '', 'steps' => serialize(array(array('description' => '', 'sql' => 'SELECT SUM(approved) FROM t', 'calculate' => ''))), 'fltr_grps' => ''),
	),
));
$mentions = (new formulizeElementReferences(900, 'approved', 3))->mentions();
$described = array_map(function($m) { return $m['table'].':'.$m['key_value']; }, $mentions);
sort($described);
check('mentions: a list column search token, a map column search token, a PHP custom page, a button value, a grid caption, a bare column in a query - not a notification term, not approved_by, not a step\'s description, not an HTML page',
	$described, array('formulize:901', 'formulize_advanced_calculations:51', 'formulize_screen_listofentries:22', 'formulize_screen_map:41', 'formulize_screen_multipage:23'));
check('mentions: the code a rename would not entirely update is marked - the PHP custom page\'s variable and the query\'s column, not the button value, whose display() call it rewrites',
	array_map(function($m) { return $m['table'].':'.$m['key_value'].':'.(substr($m['description'], -strlen(formulizeElementHandleCode::LEFT_BY_A_RENAME)) === formulizeElementHandleCode::LEFT_BY_A_RENAME ? 'left' : 'updated'); }, $mentions),
	array('formulize_screen_listofentries:22:updated', 'formulize_screen_map:41:updated', 'formulize_screen_multipage:23:left', 'formulize:901:updated', 'formulize_advanced_calculations:51:left'));
$leftByTheRename = (new formulizeElementReferences(900, 'approved', 3))->rename('is_approved');
sort($leftByTheRename);
check('rename: names the code it left naming the old handle, and nothing else', $leftByTheRename, array('P (screen 23)', 'the advanced calculation d (calculation 51)'));
$list = $xoopsDB->row('formulize_screen_listofentries', 'sid', 22);
check('advanceview: the element becomes the id and its search token is renamed, a search that is only the word is not',
	unserialize($list['advanceview']), array(array(900, '{is_approved}', '', 'Box'), array(901, 'approved', '', 'Box')));
check('custom button value: the handle given to display() is renamed', unserialize($list['customactions'])[1][0]['value'], '$value = display($entry, \'is_approved\');');
check('map column search token renamed', unserialize($xoopsDB->row('formulize_screen_map', 'sid', 41)['columns']), array(array(901, '{search_is_approved}', 'Box')));
check('custom page code: a variable of its own is left as it is', unserialize($xoopsDB->row('formulize_screen_multipage', 'sid', 23)['pages']), array(array('PHP', 'print $approved;'), array(901)));
$grid = unserialize($xoopsDB->row('formulize', 'ele_id', 901)['ele_value']);
check('grid captions: the token is renamed, the word is not', $grid[1].'|'.$grid[2], 'Row {is_approved},approved|Column one');
$view = $xoopsDB->row('formulize_saved_views', 'sv_id', 7);
check('view sort with several columns: the legacy handle becomes the id', $view['sv_sort'].'|'.$view['sv_order'], 'x,900|SORT_ASC,SORT_DESC');
check('view published filters: the legacy handle becomes the id', $view['sv_pubfilters'], '900,x');
check('notification term: never read for tokens, so never renamed',
	unserialize(unserialize($xoopsDB->row('formulize_notification_conditions', 'not_cons_id', 31)['not_cons_con'])[2]), array('{approved}'));
removeReferences(new formulizeElementReferences(900, 'is_approved', 3));
$view = $xoopsDB->row('formulize_saved_views', 'sv_id', 7);
check('deleting: the sort column goes, and its direction with it', $view['sv_sort'].'|'.$view['sv_order'], 'x|SORT_ASC');
check('deleting: the published filter goes', $view['sv_pubfilters'], 'x');
check('deleting: a calculation filter tied to it is untied, the other kept',
	unserialize($xoopsDB->row('formulize_advanced_calculations', 'acid', 50)['fltr_grps']), array(0 => array('form' => 'none', 'handle' => 'f1'), 1 => array('form' => '901', 'handle' => 'f2')));

echo "== a custom page marker is not an element\n";
resetTables(array('formulize_screen_multipage' => array(
	array('sid' => 24, 'title' => 'P', 'pages' => serialize(array(array('PHP', 'print 1;'))), 'conditions' => 'a:0:{}', 'elementdefaults' => 'a:0:{}'),
)));
(new formulizeElementReferences(910, 'PHP', 3))->rename('php_element');
check('an element whose handle is PHP does not turn a custom page into a list of elements',
	unserialize($xoopsDB->row('formulize_screen_multipage', 'sid', 24)['pages']), array(array('PHP', 'print 1;')));

echo "== what a rename rewrites in code\n";
$C = 'formulizeElementHandleCode';
check('display(): the handle given as a quoted string, in either kind of quote',
	$C::renameStrings('$a = display($entry, \'old\'); $b = getValue($entry, "old");', 'old', 'new'),
	'$a = display($entry, \'new\'); $b = getValue($entry, "new");');
check('display(): a call inside the first argument is found, and so is the outer one',
	$C::renameStrings('getValue(getData(1, 2, \'old/**/5/**/=\'), display($e, \'old\'), 0);', 'old', 'new'),
	'getValue(getData(1, 2, \'new/**/5/**/=\'), display($e, \'new\'), 0);');
check('display(): by name, and called as \display()',
	$C::renameStrings('getValue($e, handle: \'old\'); \display($e, \'old\');', 'old', 'new'),
	'getValue($e, handle: \'new\'); \display($e, \'new\');');
check('display(): every one of the display functions, at its element argument',
	$C::renameStrings("displayElement('', 'old'); displayCaption('', 'old'); displayDescription('', 'old'); displayButton('Go', 'old', 5); displayTogether(\$e, 'old', ','); displayPara(\$e, 'old'); displayBR(\$e, 'old'); displayList(\$e, 'old'); displayRichText(\$e, 'old');", 'old', 'new'),
	"displayElement('', 'new'); displayCaption('', 'new'); displayDescription('', 'new'); displayButton('Go', 'new', 5); displayTogether(\$e, 'new', ','); displayPara(\$e, 'new'); displayBR(\$e, 'new'); displayList(\$e, 'new'); displayRichText(\$e, 'new');");
$leftAlone = '$c = $obj->display($e, \'old\'); $d = display($e, \'old_x\'); $e2 = display($e, \'x_\'.$y); $f = display($e, "old$y"); $g = $row[\'old\']; $h = mydisplay($e, \'old\'); // display($e, \'old\')'."\n".'function display($x, $y) {} $i = display(\'old\', $e);';
check('left alone: a method of the same name, another handle, a handle put together, a key, another function, a comment, the first argument',
	$C::renameStrings($leftAlone, 'old', 'new'), $leftAlone);
check('filter terms: first in a string, and after ][, in either kind of quote, and in a string with variables',
	$C::renameStrings('$f = \'old/**/1/**/=][other/**/2\'; $g = "x/**/1][old/**/2"; $h = "old/**/$v"; $i = "$v][old/**/2";', 'old', 'new'),
	'$f = \'new/**/1/**/=][other/**/2\'; $g = "x/**/1][new/**/2"; $h = "new/**/$v"; $i = "$v][new/**/2";');
check('filter terms: not in the middle of a string, nor without /**/ after it',
	$C::renameStrings('$f = "$v old/**/2"; $g = \'xold/**/1\'; $h = \'old/*1\';', 'old', 'new'), '$f = "$v old/**/2"; $g = \'xold/**/1\'; $h = \'old/*1\';');
check('text with PHP in it: only what is inside the tags',
	$C::renameStrings('<div>display($e, \'old\')</div><?php echo display($e, \'old\'); ?>', 'old', 'new', false),
	'<div>display($e, \'old\')</div><?php echo display($e, \'new\'); ?>');
check('text without PHP in it: nothing', $C::renameStrings('<p>display($e, \'old\')</p>', 'old', 'new', false), '<p>display($e, \'old\')</p>');
check('variables: $old becomes $new, not $old_x', $C::renameVariables(array('$old + $old_x'), 'old', 'new'), array('$new + $old_x'));
check('variables: a $new already there is moved to the first free $new_N, past the ones in use and the ones unavailable, in every text of the group',
	$C::renameVariables(array('a' => '$old + $new + $new_1', 'b' => '{$new} {$old}'), 'old', 'new', array(''), array('new_2')),
	array('a' => '$new + $new_3 + $new_1', 'b' => '{$new_3} {$new}'));
check('variables: with a prefix, only the variable with that prefix',
	$C::renameVariables(array('$quickSearchold $quickDateRangeold $old'), 'old', 'new', array('quickSearch', 'quickDateRange')),
	array('$quickSearchnew $quickDateRangenew $old'));
check('variables: nothing moved out of the way when the old handle is not there', $C::renameVariables(array('$new'), 'old', 'new'), array('$new'));
check('tokens', $C::renameTokens('{old} {old_x} {search_old}', 'old', 'new'), '{new} {old_x} {search_old}');
check('still named: a key, a backticked column, a bare column only in a query, a variable, a token; not another handle',
	array($C::stillNamed('$r[\'old\']', 'old'), $C::stillNamed('SELECT `old` FROM t', 'old'), $C::stillNamed('SELECT old FROM t', 'old'),
		$C::stillNamed('SELECT old FROM t', 'old', true), $C::stillNamed('$old', 'old'), $C::stillNamed('{old}', 'old'), $C::stillNamed('$old_x \'old_x\'', 'old')),
	array(true, true, false, true, true, true, false));
check('reserved names: a formula\'s own variables, and any others asked for', array_values(array_intersect(array('value', 'entry', 'i', 'zz'), $C::reservedNames(array('i')))), array('value', 'entry', 'i'));

printf("\nRESULT: %d passed, %d failed\n", $GLOBALS['__pass'], $GLOBALS['__fail']);
exit($GLOBALS['__fail'] === 0 ? 0 : 1);
