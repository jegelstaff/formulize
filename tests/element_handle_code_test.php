<?php
/**
 * What a rename rewrites in code, and how it reads the code to know.
 *
 * This is the one part of renaming that is a unit test situation: formulizeElementHandleCode takes a piece of PHP and
 * a handle, and gives back PHP. PHP can be written many more ways than the museum system happens to, and a browser
 * adds nothing to finding out which of them are rewritten, so the cases are written out here. What a rename does to a
 * real system is tested through the application, in 03-validate-sequential.
 *
 * Nothing here is a stand-in. It boots the application, and uses the real classes and the parameters really declared
 * in the source files, so it runs after the setup specs have installed the system.
 *
 * WHAT THIS COVERS
 * - the handle given to a function or method that takes elements, in the shapes those parameters take them, and what
 *   is left alone because it may not be the element
 * - filter terms, {handle} tokens, and the variables Formulize names after a handle, including moving a variable of the
 *   author's own out of the way, and two spellings of one handle becoming one variable
 * - that every function and method taking elements is declared, and that the declarations are read correctly
 * - what counts as still naming a handle, for a person to look at
 *
 * Usage:
 *   docker exec formulize-web-1 php /var/www/html/tests/element_handle_code_test.php
 *
 * @package Formulize
 * @subpackage tests
 */

if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit;
}

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';
$repo = dirname(__DIR__);
include $repo . '/mainfile.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';

$GLOBALS['__pass'] = 0;
$GLOBALS['__fail'] = 0;
function check($label, $got, $want) {
	if ($got === $want) {
		$GLOBALS['__pass']++;
		return;
	}
	$GLOBALS['__fail']++;
	printf("  FAIL %s\n       got:  %s\n       want: %s\n", $label, var_export($got, true), var_export($want, true));
}

$P = formulizeElementParameters::all();
$declaringFiles = array();
foreach (formulizeElementParameters::sourceFiles($repo . '/modules/formulize/') as $sourceFile) {
	if (strpos(file_get_contents($sourceFile), formulizeElementParameters::TAG) !== false) {
		$declaringFiles[] = substr($sourceFile, strlen($repo . '/modules/formulize/'));
	}
}

echo "== what a rename rewrites in code\n";
$C = 'formulizeElementHandleCode';
check('display(): the handle given as a quoted string, in either kind of quote',
	$C::renameStrings('$a = display($entry, \'old\'); $b = getValue($entry, "old");', 'old', 'new', $P),
	'$a = display($entry, \'new\'); $b = getValue($entry, "new");');
check('display(): a call inside the first argument is found, and so is the outer one',
	$C::renameStrings('getValue(getData(1, 2, \'old/**/5/**/=\'), display($e, \'old\'), 0);', 'old', 'new', $P),
	'getValue(getData(1, 2, \'new/**/5/**/=\'), display($e, \'new\'), 0);');
check('display(): by name, and called as \display()',
	$C::renameStrings('getValue($e, handle: \'old\'); \display($e, \'old\');', 'old', 'new', $P),
	'getValue($e, handle: \'new\'); \display($e, \'new\');');
check('display(): every one of the display functions, at its element argument',
	$C::renameStrings("displayElement('', 'old'); displayCaption('', 'old'); displayDescription('', 'old'); displayButton('Go', 'old', 5); displayTogether(\$e, 'old', ','); displayPara(\$e, 'old'); displayBR(\$e, 'old'); displayList(\$e, 'old'); displayRichText(\$e, 'old');", 'old', 'new', $P),
	"displayElement('', 'new'); displayCaption('', 'new'); displayDescription('', 'new'); displayButton('Go', 'new', 5); displayTogether(\$e, 'new', ','); displayPara(\$e, 'new'); displayBR(\$e, 'new'); displayList(\$e, 'new'); displayRichText(\$e, 'new');");
$leftAlone = '$c = $obj->display($e, \'old\'); $d = display($e, \'old_x\'); $e2 = display($e, \'x_\'.$y); $f = display($e, "old$y"); $g = $row[\'old\']; $h = mydisplay($e, \'old\'); // display($e, \'old\')'."\n".'function display($x, $y) {} $i = display(\'old\', $e);';
check('left alone: a method of the same name, another handle, a handle put together, a key, another function, a comment, the first argument',
	$C::renameStrings($leftAlone, 'old', 'new', $P), $leftAlone);
check('filter terms: first in a string, and after ][, in either kind of quote, and in a string with variables',
	$C::renameStrings('$f = \'old/**/1/**/=][other/**/2\'; $g = "x/**/1][old/**/2"; $h = "old/**/$v"; $i = "$v][old/**/2";', 'old', 'new', $P),
	'$f = \'new/**/1/**/=][other/**/2\'; $g = "x/**/1][new/**/2"; $h = "new/**/$v"; $i = "$v][new/**/2";');
check('filter terms: not in the middle of a string, nor without /**/ after it',
	$C::renameStrings('$f = "$v old/**/2"; $g = \'xold/**/1\'; $h = \'old/*1\';', 'old', 'new', $P), '$f = "$v old/**/2"; $g = \'xold/**/1\'; $h = \'old/*1\';');
check('text with PHP in it: only what is inside the tags',
	$C::renameStrings('<div>display($e, \'old\')</div><?php echo display($e, \'old\'); ?>', 'old', 'new', $P, false),
	'<div>display($e, \'old\')</div><?php echo display($e, \'new\'); ?>');
check('text without PHP in it: nothing', $C::renameStrings('<p>display($e, \'old\')</p>', 'old', 'new', $P, false), '<p>display($e, \'old\')</p>');
check('one element: the data handler\'s methods, called on an object or the class, and the other functions declared to take one',
	$C::renameStrings("\$h->findAllEntriesWithValue('old', 1); \$h?->getElementValueInEntry(5, 'old'); formulizeDataHandler::findAllValuesForField('old'); _getElementObject('old'); buildFilter('f', 'old'); writeElementValue('', 'old', 5, 1);", 'old', 'new', $P),
	"\$h->findAllEntriesWithValue('new', 1); \$h?->getElementValueInEntry(5, 'new'); formulizeDataHandler::findAllValuesForField('new'); _getElementObject('new'); buildFilter('f', 'new'); writeElementValue('', 'new', 5, 1);");
check('elements separated by commas: each one that is the handle, keeping the spaces, by position and by name',
	$C::renameStrings("gatherDataset(5, array(), '', 'AND', '', null, null, 'old'); gatherDataset(5, sortField: 'year, old,old_x'); getData('', 5, '', 'AND', '', 0, 0, 'x,old');", 'old', 'new', $P),
	"gatherDataset(5, array(), '', 'AND', '', null, null, 'new'); gatherDataset(5, sortField: 'year, new,old_x'); getData('', 5, '', 'AND', '', 0, 0, 'x,new');");
check('lists of elements, in an array keyed by form, written either way',
	$C::renameStrings("gatherDataset(5, array(5 => array('old', 'x'), 6 => ['old'])); getData('', 5, filterElements: [5 => ['x', 'old']]);", 'old', 'new', $P),
	"gatherDataset(5, array(5 => array('new', 'x'), 6 => ['new'])); getData('', 5, filterElements: [5 => ['x', 'new']]);");
check('elements as keys: the keys, not the values',
	$C::renameStrings("formulize_writeEntry(array('old' => 1, 'x' => 'old')); \$h->writeEntry('new', ['old' => \$v]); \$h->findFirstEntryWithAllValues(array('old' => 2));", 'old', 'new', $P),
	"formulize_writeEntry(array('new' => 1, 'x' => 'old')); \$h->writeEntry('new', ['new' => \$v]); \$h->findFirstEntryWithAllValues(array('new' => 2));");
check('the data handler\'s searches for all values: the operators keyed by element, and the fields to return',
	$C::renameStrings("\$h->findAllEntriesWithAllValues(array('old' => 2), array('old' => '>'), 'entry_id, old'); \$h->findFirstEntryWithAllValues(array('old' => 2), array('LIKE'), 'old'); \$h->findFirstEntryWithAllValues(array('old' => 2), 'old', fieldsToReturn: '*');", 'old', 'new', $P),
	"\$h->findAllEntriesWithAllValues(array('new' => 2), array('new' => '>'), 'entry_id, new'); \$h->findFirstEntryWithAllValues(array('new' => 2), array('LIKE'), 'new'); \$h->findFirstEntryWithAllValues(array('new' => 2), 'old', fieldsToReturn: '*');");
check('elements as keys: an id is left as it is, a number or a string of one',
	$C::renameStrings("formulize_writeEntry(array(12 => 1, 'old' => 2));", 'old', 'new', $P),
	"formulize_writeEntry(array(12 => 1, 'new' => 2));");
$numbers = "formulize_writeEntry(array(12 => 1, '12' => 2)); display(\$e, '12'); \$f = '12/**/x/**/=';";
check('a numeric old handle: what is given as that number is an element\'s id, so nothing is rewritten',
	$C::renameStrings($numbers, '12', 'twelve', $P), $numbers);
check('elements as keys and values both',
	$C::renameStrings("\$h->cloneData(5, array('old' => 'old_copy', 'x' => 'old'));", 'old', 'new', $P),
	"\$h->cloneData(5, array('new' => 'old_copy', 'x' => 'new'));");
$leftAlone = "\$a = array('old' => 1); formulize_writeEntry(\$a); formulize_writeEntry(array('old' => 1) + \$b); gatherDataset(5, \$lists, sortField: \$s.'old'); gatherDataset(5, array('old')); display(\$e, ...\$args); \$x->get('old'); function writeEntry(\$values = array('old' => 1)) {} function &display(\$a, \$b = 'old') {}";
check('left alone: an array made beforehand, an array added to, a string put together, a list not keyed by form, arguments spread, a method not declared, a declaration',
	$C::renameStrings($leftAlone, 'old', 'new', $P), $leftAlone);

// the shapes at their edges: what is rewritten, written every way PHP allows, and what is left because it is not
// certainly the element
$edges = <<<'CODE'
$a = $h->cloneData(5, array('old' => 'old', 'x' => array('old'), 0 => 'old'));
$b = formulize_writeEntry(array('old' => array('old'), 'old_x' => 1));
$c = gatherDataset(5, array(5 => 'old', 6 => array('x', 'old'), 7 => ['old']));
$d = formulize_writeEntry(array( /* c */ 'old' /* d */ => 1, // e
	'x' => 2,
));
$e2 = display($e, <<<EOT
old
EOT);
$f = display($e, "old\n"); $g = formulize_writeEntry(array('ol'.'d' => 1)); $i = formulize_writeEntry([...$v, 'old' => 1]);
$j = formulize_writeEntry(array('old' => display($e, 'old'))); $k = formulize_writeEntry(array('old' => 1)[0]);
$l = gatherDataset(sortField: 'old', fid: 5); $m = gatherDataset(5, filter: 'old'); $n = DISPLAY($e, 'old') . GatherDataset(5, sortField: 'old');
$o = \Some\Place\display($e, 'old'); $p = Some::display($e, 'old'); $q = display(...); $r = "{$h->findAllEntriesWithValue('old', 1)}";
$s = formulize_writeEntry(array(('old') => 1)); $t = display($e, $c ? 'old' : 'x');
$u = gatherDataset(5, sortField: 'old,'); $w = gatherDataset(5, sortField: ',old'); $x = gatherDataset(5, sortField: 'old ,old'); $y = gatherDataset(5, sortField: 'old_x,old'); $z = gatherDataset(5, sortField: "old,$v");
CODE;
$edgesRenamed = <<<'CODE'
$a = $h->cloneData(5, array('new' => 'new', 'x' => array('old'), 0 => 'new'));
$b = formulize_writeEntry(array('new' => array('old'), 'old_x' => 1));
$c = gatherDataset(5, array(5 => 'old', 6 => array('x', 'new'), 7 => ['new']));
$d = formulize_writeEntry(array( /* c */ 'new' /* d */ => 1, // e
	'x' => 2,
));
$e2 = display($e, <<<EOT
old
EOT);
$f = display($e, "old\n"); $g = formulize_writeEntry(array('ol'.'d' => 1)); $i = formulize_writeEntry([...$v, 'new' => 1]);
$j = formulize_writeEntry(array('new' => display($e, 'new'))); $k = formulize_writeEntry(array('old' => 1)[0]);
$l = gatherDataset(sortField: 'new', fid: 5); $m = gatherDataset(5, filter: 'old'); $n = DISPLAY($e, 'new') . GatherDataset(5, sortField: 'new');
$o = \Some\Place\display($e, 'old'); $p = Some::display($e, 'old'); $q = display(...); $r = "{$h->findAllEntriesWithValue('new', 1)}";
$s = formulize_writeEntry(array(('old') => 1)); $t = display($e, $c ? 'old' : 'x');
$u = gatherDataset(5, sortField: 'new,'); $w = gatherDataset(5, sortField: ',new'); $x = gatherDataset(5, sortField: 'new ,new'); $y = gatherDataset(5, sortField: 'old_x,new'); $z = gatherDataset(5, sortField: "old,$v");
CODE;
check('the shapes at their edges: nesting, comments, trailing commas, spreading, names in any order and case; and left alone: a value not in its shape, heredocs, escapes, keys put together, an array used for something else, other namespaces and classes, a callable, a choice between strings, a string with variables',
	$C::renameStrings($edges, 'old', 'new', $P), $edgesRenamed);
check('the shapes at their edges: line endings are kept', $C::renameStrings(str_replace("\n", "\r\n", $edges), 'old', 'new', $P), str_replace("\n", "\r\n", $edgesRenamed));

check('the right argument, whatever the others hold: the same nested array in the arguments either side of the declared one is left',
	$C::renameStrings("gatherDataset(array(5 => array('old')), array(5 => array('old')), array(5 => array('old')), 'old', 'old', 'old', 'old', 'old', 'old');", 'old', 'new', $P),
	"gatherDataset(array(5 => array('old')), array(5 => array('new')), array(5 => array('old')), 'old', 'old', 'old', 'old', 'new', 'old');");
check('no deeper than the shape: a list inside a list of lists, and an array keyed by elements inside one, are left',
	$C::renameStrings("gatherDataset(5, array(5 => array('old', array('old'), 'x'), 6 => array(array('old' => 1)))); formulize_writeEntry(array('x' => array('old' => 1), 'old' => array('old' => 1)));", 'old', 'new', $P),
	"gatherDataset(5, array(5 => array('new', array('old'), 'x'), 6 => array(array('old' => 1)))); formulize_writeEntry(array('x' => array('old' => 1), 'new' => array('old' => 1)));");
check('nested calls at every level: each call\'s own declared argument, inside the arrays and arguments of the others',
	$C::renameStrings("formulize_writeEntry(array('old' => gatherDataset(display(\$e, 'old'), array(5 => array(getValue(\$e, 'old'), 'old')), sortField: 'old')));", 'old', 'new', $P),
	"formulize_writeEntry(array('new' => gatherDataset(display(\$e, 'new'), array(5 => array(getValue(\$e, 'new'), 'new')), sortField: 'new')));");
check('written across lines, with the brackets mixed and everything trailing',
	$C::renameStrings("gatherDataset(\n\t5,\n\t[\n\t\t5 => array(\n\t\t\t'x', // a comment, with 'old' in it\n\t\t\t'old',\n\t\t),\n\t],\n);", 'old', 'new', $P),
	"gatherDataset(\n\t5,\n\t[\n\t\t5 => array(\n\t\t\t'x', // a comment, with 'old' in it\n\t\t\t'new',\n\t\t),\n\t],\n);");

// the declarations themselves: each one readable, the methods named only once, and the parameters that take elements found
$problems = array();
$declared = array();
foreach($declaringFiles as $relative) {
	foreach(formulizeElementParameters::declaredIn(file_get_contents($repo.'/modules/formulize/'.$relative)) as $declaration) {
		if($declaration['problem'] !== '') {
			$problems[] = $relative.': '.$declaration['name'].': '.$declaration['problem'];
		}
		$declared[$declaration['kind']][] = $declaration['name'];
	}
}
check('every declaration names a parameter of its function and a shape', $problems, array());
// a method is known by its name alone, so no other method in Formulize can have the name of one that is declared
$sameName = array();
foreach(formulizeElementParameters::sourceFiles($repo.'/modules/formulize/') as $sourceFile) {
	$source = file_get_contents($sourceFile);
	foreach(array_unique($declared['method']) as $method) {
		// every definition, so two classes in one file defining it are two
		$definitions = preg_match_all('/function\s+&?\s*'.preg_quote($method, '/').'\s*\(/i', $source);
		for($d = 0; $d < $definitions; $d++) {
			$sameName[$method][] = basename($sourceFile);
		}
	}
}
check('every method declared is the only method of that name', array_filter($sameName, function($files) { return count($files) > 1; }), array());
check('the functions and methods that take elements are all declared', array(
	array_values(array_diff(array('display', 'getValue', 'displayTogether', 'displayPara', 'displayBR', 'displayList', 'displayRichText',
		'displayElement', 'displayCaption', 'displayDescription', 'displayButton', 'gatherDataset', 'getData', 'formulize_writeEntry',
		'writeElementValue', 'prepDataForWrite', 'getElementValue', 'buildFilter', '_getElementObject', 'addDefaultValuesToDataToWrite',
		'applyDefaultToEntries'), $declared['function'])),
	array_values(array_diff(array('elementHasValueInEntry', 'getElementValueInEntry', 'findLastEntryWithValue', 'findFirstEntryWithValue',
		'findAllEntriesWithValue', 'findFirstEntryWithAllValues', 'findAllEntriesWithAllValues', 'findEntryOrEntriesWithAllValues',
		'findAllValuesForEntries', 'findAllValuesForField', 'writeEntry', 'changeUserSubmittedValues', 'cloneData', 'reassignLSB',
		'buildSafeWhereClause', 'dataTypeIsDate', 'dataTypeIsNumeric', 'formatValueForQuery', 'convertRadioDataToCheckbox',
		'convertCheckboxDataToRadio', 'toggleEncryption'), $declared['method'])),
), array(array(), array()));
check('the declarations are read the way PHP reads them: methods in a class, a docblock before modifiers, references, and what is wrong with a bad one',
	array_map(function($d) { return array($d['kind'], $d['class'], $d['name'], $d['parameter'], $d['position'], $d['shape'], $d['problem']); },
		formulizeElementParameters::declaredIn('<?php
			/** @formulizeElement $b one */
			function &f($a = array(1, $z), $b = null) {}
			class K {
				/**
				 * @param array $v
				 * @formulizeElement v keys
				 */
				public static function m($v) {}
				/** @formulizeElement $x one */
				function n($v) {}
				/** @formulizeElement $v many */
				function o($v) {}
			}
			/** @formulizeElement $q one */
			$closure = function($q) {};')),
	array(
		array('function', '', 'f', 'b', 1, 'one', ''),
		array('method', 'K', 'm', 'v', 0, 'keys', ''),
		array('method', 'K', 'n', 'x', null, 'one', 'there is no parameter called x'),
		array('method', 'K', 'o', 'v', null, 'many', "'many' is not a shape - it can be one, commas, list, lists, keys, map"),
	));
check('the declarations are kept in the cache folder, and read back from it', is_array(include XOOPS_ROOT_PATH.'/modules/formulize/cache/element_parameters.php'), true);
check('variables: $old becomes $new, not $old_x', $C::renameVariables(array('$old + $old_x'), 'old', 'new'), array('$new + $old_x'));
check('variables: a $new already there is moved to the first free $new_N, past the ones in use and the ones unavailable, in every text of the group',
	$C::renameVariables(array('a' => '$old + $new + $new_1', 'b' => '{$new} {$old}'), 'old', 'new', array(''), array('new_2')),
	array('a' => '$new + $new_3 + $new_1', 'b' => '{$new_3} {$new}'));
check('variables: with a prefix, only the variable with that prefix',
	$C::renameVariables(array('$quickSearchold $quickDateRangeold $old'), 'old', 'new', array('quickSearch', 'quickDateRange')),
	array('$quickSearchnew $quickDateRangenew $old'));
check('variables: nothing moved out of the way when the old handle is not there', $C::renameVariables(array('$new'), 'old', 'new'), array('$new'));
check('variables: capitals are another spelling of the same handle, so the two are one variable and nothing is moved',
	$C::renameVariables(array('$value = $My_Handle + $my_handle;'), 'My_Handle', 'my_handle'), array('$value = $my_handle + $my_handle;'));
check('variables: and so are hyphens', $C::renameVariables(array('$my-handle + $my_handle'), 'my-handle', 'my_handle'), array('$my_handle + $my_handle'));
check('variables: and across the texts that share their variables, with a prefix',
	$C::renameVariables(array('a' => '$quickSearchMy_Handle', 'b' => '{$quickSearchmy_handle}'), 'My_Handle', 'my_handle', array('quickSearch')),
	array('a' => '$quickSearchmy_handle', 'b' => '{$quickSearchmy_handle}'));
$twoSpellings = $C::renameVariables(array('$My_Handle + $MY_HANDLE + $my_handle'), 'My_Handle', 'my_handle');
check('variables: a second old spelling joins the first, rather than moving it', $C::renameVariables($twoSpellings, 'MY_HANDLE', 'my_handle'), array('$my_handle + $my_handle + $my_handle'));
check('variables: a suffix makes it a different name, so a variable already called that is still moved',
	$C::renameVariables(array('$My_Handle + $my_handle_f6'), 'My_Handle', 'my_handle_f6'), array('$my_handle_f6 + $my_handle_f6_1'));
check('tokens', $C::renameTokens('{old} {old_x} {search_old}', 'old', 'new'), '{new} {old_x} {search_old}');
check('still named: a key, a backticked column, a bare column only in a query, a variable, a token; not another handle',
	array($C::stillNamed('$r[\'old\']', 'old'), $C::stillNamed('SELECT `old` FROM t', 'old'), $C::stillNamed('SELECT old FROM t', 'old'),
		$C::stillNamed('SELECT old FROM t', 'old', true), $C::stillNamed('$old', 'old'), $C::stillNamed('{old}', 'old'), $C::stillNamed('$old_x \'old_x\'', 'old')),
	array(true, true, false, true, true, true, false));
check('reserved names: a formula\'s own variables, and any others asked for', array_values(array_intersect(array('value', 'entry', 'i', 'zz'), $C::reservedNames(array('i')))), array('value', 'entry', 'i'));

printf("\nRESULT: %d passed, %d failed\n", $GLOBALS['__pass'], $GLOBALS['__fail']);
exit($GLOBALS['__fail'] === 0 ? 0 : 1);
