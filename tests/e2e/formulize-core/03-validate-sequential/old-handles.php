<?php
/**
 * Turns the finished museum system into a site with its element handles in their old shapes, for
 * 060-update-fixes-old-handles.spec.js, and says what state the system is in.
 *
 * Handles used to be able to have capitals and hyphens. Formulize refuses to create those now, so the only way to get
 * such a site is to write it. This does that to real elements that drive real features, in every place an old site
 * has them: the element table, the fields of the data and revisions tables, the code files and what is in them, and
 * the settings that name the element, where an old site has the handle and a new one has the id. Settings stored as
 * serialized arrays are unserialized, changed and serialized again, so their lengths stay right. The spec then runs
 * the real update from the admin UI.
 *
 * What the update has to do is known without anyone writing it down, because the system was right before this broke
 * it. So 'capture' keeps the system as it was, and 'compare' says everything that is not back the same. It reads
 * tables and files directly, and asks nothing of Formulize's own code.
 *
 * Usage, from the web container:
 *   php old-handles.php add-references     adds the kinds of setting the museum system does not use, to be captured with the rest
 *   php old-handles.php remove-references  takes those out again
 *   php old-handles.php capture      keeps the Formulize tables and the code and template files
 *   php old-handles.php break        puts the system in its old shape, and prints how many places it changed
 *   php old-handles.php second-visit mends the field a person has to mend, and leaves an old spelling for the update to ignore
 *   php old-handles.php planted      prints what was planted, as it stands now, for the spec to check
 *   php old-handles.php tidy <handle>  removes what was planted that belongs to no real feature, given the handle the
 *                                    update gave the element that collided
 *   php old-handles.php compare      prints what differs from what capture kept, as JSON, and an empty list if nothing does
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
$root = dirname(__DIR__, 4);
include $root . '/mainfile.php';

global $xoopsDB;
$command = isset($argv[1]) ? $argv[1] : '';
$keepFile = sys_get_temp_dir() . '/formulize_old_handles.json';

// Elements an old site has misspelled everywhere, and what each one drives
$misspelled = array(
	'artifacts_year' => 'Artifacts_Year',                           // the Year-Era formula
	'artifacts_era' => 'ARTIFACTS_ERA',                             // the Year-Era formula
	'artifacts_year_era' => 'Artifacts_Year_Era',                   // a derived element, with a code file of its own
	'artifacts_short_name' => 'Artifacts-Short-Name',               // the map, saved views, a notification, an index
	'artifacts_latitude' => 'artifacts-latitude',                   // the map
	'artifacts_donated_to_museum' => 'Artifacts_Donated_To_Museum', // the condition on the Donor list, and a procedure
	'donors_name' => 'Donors_Name',                                 // the source of the Donor list, with a code file
	'surveys_exhibit' => 'Surveys-Exhibit',                         // a {handle} term in a linked list's filter
);
// An element that is right everywhere, with references left behind in the old spelling. In the procedure that uses
// it, one use is misspelled beside the others, so the procedure names the element both ways.
$staleElement = 'artifacts_id_number';
$staleSpelling = 'Artifacts_ID_Number';
// Another, whose settings name it with hyphens, which is the other change that left references behind: the custom
// button on the Artifacts list names it
$staleWithHyphens = array('element' => 'artifacts_condition', 'spelling' => 'artifacts-condition');
// Takes the handle of an element in another form, which only differs from it by case
$collision = array('element' => 'donors_bio', 'spelling' => 'ARTIFACTS_HEIGHT');
// Has no field to rename, because its field is under some other name, until a person mends that
$noField = array('element' => 'artifacts_date_of_acquisition', 'spelling' => 'Artifacts_Date_Of_Acquisition', 'field' => 'zz_moved_by_someone');

// Tables whose rows the update or a page load changes for reasons of their own
$notCompared = array('formulize_tokens', 'formulize_procedure_logs', 'formulize_procedure_logs_params', 'formulize_deletion_logs', 'formulize_digest_data');

function old_table($name) { global $xoopsDB; return $xoopsDB->prefix($name); }
function old_q($value) { global $xoopsDB; return $xoopsDB->quoteString((string) $value); }
function old_rows($sql) {
	global $xoopsDB;
	$rows = array();
	if (!$result = $xoopsDB->queryF($sql)) {
		fwrite(STDERR, "could not read: $sql\n" . $xoopsDB->error() . "\n");
		exit(1);
	}
	while ($row = $xoopsDB->fetchArray($result)) {
		$rows[] = $row;
	}
	return $rows;
}
function old_write($sql) {
	global $xoopsDB;
	if (!$xoopsDB->queryF($sql)) {
		fwrite(STDERR, "could not write: $sql\n" . $xoopsDB->error() . "\n");
		exit(1);
	}
}

// A file has to stay writable by the web server, which this does not usually run as
function old_put($path, $contents) {
	if (file_put_contents($path, $contents) === false) {
		fwrite(STDERR, "could not write $path\n");
		exit(1);
	}
	@chown($path, fileowner(dirname($path)));
	@chgrp($path, filegroup(dirname($path)));
}

function old_files() {
	$folders = array_merge(
		array(XOOPS_ROOT_PATH . '/modules/formulize/code', XOOPS_ROOT_PATH . '/modules/formulize/templates/screens'),
		glob(XOOPS_ROOT_PATH . '/modules/formulize/language/*/mail_template', GLOB_ONLYDIR));
	$files = array();
	foreach ($folders as $folder) {
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS)) as $file) {
			if ($file->isFile()) {
				$files[substr($file->getPathname(), strlen(XOOPS_ROOT_PATH))] = str_replace("\r\n", "\n", file_get_contents($file->getPathname()));
			}
		}
	}
	return $files;
}

function old_state() {
	global $notCompared;
	$prefix = substr(old_table('x'), 0, -1); // with its underscore, which prefix('') does not give
	$state = array('tables' => array(), 'files' => old_files());
	foreach (old_rows("SELECT TABLE_NAME AS name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME LIKE " . old_q(str_replace('_', '\_', $prefix) . 'formulize%')) as $table) {
		$name = substr($table['name'], strlen($prefix));
		if (in_array($name, $notCompared)) {
			continue;
		}
		$columns = old_rows("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . old_q($table['name']) . " ORDER BY ORDINAL_POSITION");
		$rows = array();
		foreach (old_rows("SELECT * FROM `{$table['name']}`") as $row) {
			$rows[reset($row) . '|' . md5(json_encode($row, JSON_INVALID_UTF8_SUBSTITUTE))] = $row;
		}
		$state['tables'][$name] = array(
			'columns' => $columns,
			'indexes' => old_rows("SELECT INDEX_NAME, COLUMN_NAME, SEQ_IN_INDEX FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . old_q($table['name']) . " ORDER BY INDEX_NAME, SEQ_IN_INDEX"),
			'rows' => array_values($rows),
		);
	}
	return $state;
}

// An id comes back as a number where the setting had it as a string of digits, which reads the same everywhere
function old_same_value($value) {
	return is_string($value) ? preg_replace('/s:\d+:"(\d+)";/', 'i:$1;', $value) : $value;
}

function old_compare($before, $after) {
	$differences = array();
	foreach (array_keys($before['tables'] + $after['tables']) as $table) {
		if (!isset($after['tables'][$table]) OR !isset($before['tables'][$table])) {
			$differences[] = "table $table " . (isset($after['tables'][$table]) ? 'is new' : 'has gone');
			continue;
		}
		foreach (array('columns', 'indexes') as $part) {
			if ($before['tables'][$table][$part] !== $after['tables'][$table][$part]) {
				$differences[] = "$table: $part differ: " . substr(json_encode(array_values(array_udiff($before['tables'][$table][$part], $after['tables'][$table][$part], fn($a, $b) => strcmp(json_encode($a), json_encode($b))))), 0, 300)
					. ' now ' . substr(json_encode(array_values(array_udiff($after['tables'][$table][$part], $before['tables'][$table][$part], fn($a, $b) => strcmp(json_encode($a), json_encode($b))))), 0, 300);
			}
		}
		$keyOf = fn($row) => reset($row);
		$rowsAfter = array();
		foreach ($after['tables'][$table]['rows'] as $row) {
			$rowsAfter[$keyOf($row)][] = $row;
		}
		foreach ($before['tables'][$table]['rows'] as $row) {
			$key = $keyOf($row);
			if (empty($rowsAfter[$key])) {
				$differences[] = "$table: row $key has gone";
				continue;
			}
			// rows that share a first column, like the tables with no key of their own, are matched to the closest one
			$best = null;
			$bestDifferences = null;
			foreach ($rowsAfter[$key] as $index => $candidate) {
				$found = array();
				foreach ($row as $column => $value) {
					$now = array_key_exists($column, $candidate) ? $candidate[$column] : '(no such column)';
					if (old_same_value($value) !== old_same_value($now)) {
						$found[] = "$table row $key $column: was " . substr((string) $value, 0, 260) . ' now ' . substr((string) $now, 0, 260);
					}
				}
				if ($bestDifferences === null OR count($found) < count($bestDifferences)) {
					$best = $index;
					$bestDifferences = $found;
				}
			}
			unset($rowsAfter[$key][$best]);
			$differences = array_merge($differences, $bestDifferences);
		}
		foreach ($rowsAfter as $key => $left) {
			foreach ($left as $row) {
				$differences[] = "$table: row $key is new";
			}
		}
	}
	$gone = array_diff_key($before['files'], $after['files']);
	$new = array_diff_key($after['files'], $before['files']);
	// a file that has gone, and one that is new with what it had, is the same file under another name
	foreach ($gone as $path => $contents) {
		$sameContents = array_keys($new, $contents, true);
		if ($sameContents) {
			$differences[] = "file $path is now called " . basename($sameContents[0]);
			unset($new[$sameContents[0]]);
		} else {
			$differences[] = "file $path has gone";
		}
	}
	foreach ($new as $path => $contents) {
		$differences[] = "file $path is new";
	}
	foreach (array_intersect_key($before['files'], $after['files']) as $path => $contents) {
		if ($after['files'][$path] !== $contents) {
			$differences[] = "file $path has changed: " . old_changed_lines($contents, $after['files'][$path]);
		}
	}
	return $differences;
}

// The lines of a file that are not as they were, and what is there instead
function old_changed_lines($was, $now) {
	$wasLines = explode("\n", $was);
	$nowLines = explode("\n", $now);
	$described = array();
	foreach (array_diff($wasLines, $nowLines) as $line) {
		$described[] = 'was "' . substr(trim($line), 0, 160) . '"';
	}
	foreach (array_diff($nowLines, $wasLines) as $line) {
		$described[] = 'now "' . substr(trim($line), 0, 160) . '"';
	}
	return $described ? implode(', ', $described) : 'the same lines, in another order';
}

// ---- the places an old site has an element by its handle, where a new one has the id ----

$changed = array(); // place => how many references were changed there

function old_note($place) {
	global $changed;
	$changed[$place] = (isset($changed[$place]) ? $changed[$place] : 0) + 1;
}

// One reference, if it is the id of one of the elements
function old_reference($reference, $spellings, $place) {
	if ((is_int($reference) OR (is_string($reference) AND ctype_digit($reference))) AND isset($spellings[intval($reference)])) {
		old_note($place);
		return $spellings[intval($reference)];
	}
	return $reference;
}

// Four arrays, and the first holds the elements
function old_conditions($conditions, $spellings, $place) {
	if (is_array($conditions) AND isset($conditions[0]) AND is_array($conditions[0])) {
		foreach ($conditions[0] as $index => $element) {
			$conditions[0][$index] = old_reference($element, $spellings, $place);
		}
	}
	return $conditions;
}

function old_list($values, $spellings, $place) {
	foreach ($values as $index => $element) {
		$values[$index] = old_reference($element, $spellings, $place);
	}
	return $values;
}

// A comma separated list, whose entries can have hiddencolumn_ in front
function old_text_list($text, $spellings, $place) {
	$entries = explode(',', (string) $text);
	foreach ($entries as $index => $entry) {
		$hidden = strpos($entry, 'hiddencolumn_') === 0 ? 'hiddencolumn_' : '';
		$entries[$index] = $hidden . old_reference(substr($entry, strlen($hidden)), $spellings, $place);
	}
	return implode(',', $entries);
}

// {handle} tokens wherever they are, in text or anywhere in an array
function old_tokens($value, $tokens, $place) {
	if (is_array($value)) {
		foreach ($value as $key => $item) {
			$value[$key] = old_tokens($item, $tokens, $place);
		}
		return $value;
	}
	if (is_string($value)) {
		$rewritten = str_replace(array_keys($tokens), array_values($tokens), $value, $count);
		if ($count) {
			old_note($place);
		}
		return $rewritten;
	}
	return $value;
}

// A handle given as a quoted string in code kept in a setting
function old_quoted($code, $misspelled, $place) {
	foreach ($misspelled as $handle => $spelling) {
		$code = str_replace("'$handle'", "'$spelling'", (string) $code, $count);
		if ($count) {
			old_note($place);
		}
	}
	return $code;
}

function old_unserialized($text) {
	$value = @unserialize((string) $text, array('allowed_classes' => false));
	return is_array($value) ? $value : null;
}

if ($command == 'capture') {
	$state = old_state();
	file_put_contents($keepFile, json_encode($state, JSON_INVALID_UTF8_SUBSTITUTE));
	echo json_encode(array('tables' => count($state['tables']), 'elements' => count($state['tables']['formulize']['rows']), 'files' => count($state['files']))) . "\n";
	exit(0);
}

if ($command == 'compare') {
	if (!file_exists($keepFile)) {
		fwrite(STDERR, "nothing was captured\n");
		exit(1);
	}
	echo json_encode(old_compare(json_decode(file_get_contents($keepFile), true), json_decode(json_encode(old_state(), JSON_INVALID_UTF8_SUBSTITUTE), true)), JSON_INVALID_UTF8_SUBSTITUTE) . "\n";
	exit(0);
}

$codeFolder = XOOPS_ROOT_PATH . '/modules/formulize/code/';
$revisionsOfArtifacts = old_table('formulize_artifacts_revisions');
$plantedCaption = 'See Artifacts_Year and Artifacts-Era here';
$plantedKey = "\n\$oldKey = \$anArray['Artifacts_Year'];\n";
$plantedStale = ' {Artifacts_Width}';

function old_element($handle) {
	$rows = old_rows("SELECT e.ele_id, e.id_form, e.ele_caption, e.ele_filtersettings, f.form_handle FROM " . old_table('formulize') . " AS e INNER JOIN " . old_table('formulize_id') . " AS f ON f.id_form = e.id_form WHERE e.ele_handle = " . old_q($handle));
	if (!$rows) {
		fwrite(STDERR, "the museum system has no element $handle to work with\n");
		exit(1);
	}
	return $rows[0];
}

// The museum system does not use every kind of setting that can refer to an element. These add the rest to it, the way
// a new site stores them, before the system is captured, so that the same comparison covers them. None of them are
// meant to stay, because they change what the screens do.
$referencesFile = sys_get_temp_dir() . '/formulize_old_handles_references.json';

if ($command == 'add-references') {
	if (file_exists($referencesFile)) {
		fwrite(STDERR, "already added - remove them first\n");
		exit(1);
	}
	$shortName = intval(old_element('artifacts_short_name')['ele_id']);
	$year = intval(old_element('artifacts_year')['ele_id']);
	$fid = intval(old_element('artifacts_short_name')['id_form']);
	$list = old_rows("SELECT sid FROM " . old_table('formulize_screen') . " WHERE title = 'Artifacts' AND type = 'listOfEntries'")[0];
	$form = old_rows("SELECT sid FROM " . old_table('formulize_screen') . " WHERE title = 'Artifact' AND type = 'multiPage'")[0];
	$map = old_rows("SELECT sid FROM " . old_table('formulize_screen') . " WHERE screen_handle = 'artifact_origins'")[0];
	$groupFilter = old_rows("SELECT filterid FROM " . old_table('formulize_group_filters') . " WHERE fid = $fid LIMIT 1");

	// table, key column, key, column, what to do to the stored value
	$changes = array(
		// a default value and a grid's row caption, each showing an element's value through a token
		array('formulize', 'ele_id', old_element('artifacts_id_number')['ele_id'], 'ele_value', function($value) { $value[2] = 'Made in {artifacts_year}'; return $value; }),
		array('formulize', 'ele_id', old_element('artifacts_dimensions')['ele_id'], 'ele_value', function($value) { $value[1] = 'Row {artifacts_year}'; return $value; }),
		// a list searching a column for another element's search box, hiding a column, and a button with code in it
		array('formulize_screen_listofentries', 'sid', $list['sid'], 'advanceview', function($value) { $value[0][1] = '{search_artifacts_short_name}'; return $value; }),
		array('formulize_screen_listofentries', 'sid', $list['sid'], 'hiddencolumns', function($value) use ($shortName) { return array($shortName); }),
		array('formulize_screen_listofentries', 'sid', $list['sid'], 'customactions', function($value) use ($shortName) {
			$value[0][2] = array('element' => $shortName, 'action' => 'replace', 'value' => '$value = display($entry, \'artifacts_short_name\');');
			return $value;
		}),
		// a map column searching the same way
		array('formulize_screen_map', 'sid', $map['sid'], 'columns', function($value) { $value[0][1] = '{search_artifacts_short_name}'; return $value; }),
		// a form screen with a default for an element, a condition on a page, and a page of PHP
		array('formulize_screen_multipage', 'sid', $form['sid'], 'elementdefaults', function($value) use ($year) { return array($year => '1900'); }),
		array('formulize_screen_multipage', 'sid', $form['sid'], 'conditions', function($value) use ($year) {
			$value[0] = array(0 => array($year), 1 => array('='), 2 => array('1900'), 3 => array('all'));
			return $value;
		}),
		array('formulize_screen_multipage', 'sid', $form['sid'], 'pages', function($value) {
			$value[] = array('PHP', 'print display($entry, \'artifacts_short_name\');');
			return $value;
		}),
	);
	if ($groupFilter) {
		$changes[] = array('formulize_group_filters', 'filterid', $groupFilter[0]['filterid'], 'filter', function($value) use ($year) {
			return array(0 => array($year), 1 => array('!='), 2 => array('0'), 3 => array('all'));
		});
	}
	$keep = array();
	foreach ($changes as list($table, $keyColumn, $key, $column, $change)) {
		$stored = old_rows("SELECT `$column` AS stored FROM " . old_table($table) . " WHERE `$keyColumn` = " . intval($key))[0]['stored'];
		$keep[] = array($table, $keyColumn, intval($key), $column, $stored);
		$value = old_unserialized($stored);
		old_write("UPDATE " . old_table($table) . " SET `$column` = " . old_q(serialize($change(is_array($value) ? $value : array()))) . " WHERE `$keyColumn` = " . intval($key));
	}
	// a saved view with a hidden column, and a search box that goes with it
	old_write("INSERT INTO " . old_table('formulize_saved_views') . " SET sv_name = 'Old handles view', sv_formframe = $fid, sv_mainform = 0, sv_oldcols = "
		. old_q("$shortName,hiddencolumn_$year") . ", sv_quicksearches = " . old_q('&*=%4#{search_artifacts_short_name}&*=%4#') . ", sv_sort = " . old_q((string) $year)
		. ", sv_order = 'SORT_ASC', sv_pubfilters = " . old_q((string) $shortName) . ", sv_currentview = 'all'");
	file_put_contents($referencesFile, json_encode($keep));
	echo "added\n";
	exit(0);
}

if ($command == 'remove-references') {
	if (!file_exists($referencesFile)) {
		echo "nothing to remove\n";
		exit(0);
	}
	foreach (json_decode(file_get_contents($referencesFile), true) as list($table, $keyColumn, $key, $column, $stored)) {
		old_write("UPDATE " . old_table($table) . " SET `$column` = " . old_q($stored) . " WHERE `$keyColumn` = " . intval($key));
	}
	old_write("DELETE FROM " . old_table('formulize_saved_views') . " WHERE sv_name = 'Old handles view'");
	unlink($referencesFile);
	echo "removed\n";
	exit(0);
}

if ($command == 'break') {
	if (!file_exists($keepFile)) {
		fwrite(STDERR, "capture the system first\n");
		exit(1);
	}

	// the id of each element, and what is written where a new site has the id
	$spellings = array();
	$elements = array();
	foreach ($misspelled as $handle => $spelling) {
		$elements[$handle] = old_element($handle);
		$spellings[intval($elements[$handle]['ele_id'])] = $spelling;
	}
	$spellings[intval(old_element($staleElement)['ele_id'])] = $staleSpelling;
	$spellings[intval(old_element($staleWithHyphens['element'])['ele_id'])] = $staleWithHyphens['spelling'];
	$tokens = array();
	foreach ($misspelled as $handle => $spelling) {
		$tokens['{' . $handle . '}'] = '{' . $spelling . '}';
		$tokens['{search_' . $handle . '}'] = '{search_' . $spelling . '}';
		$tokens['{ELEMENT_' . strtoupper($handle) . '}'] = '{ELEMENT_' . strtoupper($spelling) . '}';
		$tokens['{REVISION_ELEMENT_' . strtoupper($handle) . '}'] = '{REVISION_ELEMENT_' . strtoupper($spelling) . '}';
	}

	// the settings
	foreach (old_rows("SELECT ele_id, ele_type, ele_caption, ele_desc, ele_filtersettings, ele_disabledconditions, ele_dynamicdefault_conditions, ele_value FROM " . old_table('formulize')) as $row) {
		$sets = array();
		foreach (array('ele_caption', 'ele_desc') as $column) {
			$text = old_tokens((string) $row[$column], $tokens, 'tokens in captions and help text');
			if ($text !== (string) $row[$column]) {
				$sets[] = "$column = " . old_q($text);
			}
		}
		foreach (array('ele_filtersettings', 'ele_disabledconditions', 'ele_dynamicdefault_conditions') as $column) {
			if ($conditions = old_unserialized($row[$column])) {
				$rewritten = old_conditions($conditions, $spellings, 'conditions on elements');
				if ($rewritten !== $conditions) {
					$sets[] = "$column = " . old_q(serialize($rewritten));
				}
			}
		}
		if ($eleValue = old_unserialized($row['ele_value'])) {
			$rewritten = old_tokens($eleValue, $tokens, 'tokens in element settings');
			// Split and written by hand, not with formulize_linkedSourceParts() and formulize_linkedSourceString(), which
			// are for everything else: they would give back the id, and a source by handle is what this is here to write.
			if (isset($rewritten[2]) AND is_string($rewritten[2]) AND substr_count($rewritten[2], '#*=:*') == 1) {
				list($form, $source) = explode('#*=:*', $rewritten[2]);
				$rewritten[2] = $form . '#*=:*' . old_reference($source, $spellings, 'linked sources');
			}
			if (strpos((string) $row['ele_type'], 'Linked') !== false) {
				foreach (array(10, 11, 17) as $key) {
					if (isset($rewritten[$key]) AND is_array($rewritten[$key])) {
						$rewritten[$key] = old_list($rewritten[$key], $spellings, 'alternate elements of linked lists');
					}
				}
				if (isset($rewritten[5])) {
					$rewritten[5] = old_conditions($rewritten[5], $spellings, 'filters of linked lists');
				}
			}
			if ($rewritten !== $eleValue) {
				$sets[] = "ele_value = " . old_q(serialize($rewritten));
			}
		}
		if ($sets) {
			old_write("UPDATE " . old_table('formulize') . " SET " . implode(', ', $sets) . " WHERE ele_id = " . intval($row['ele_id']));
		}
	}
	foreach (old_rows("SELECT sid, advanceview, hiddencolumns, decolumns, fundamental_filters, customactions FROM " . old_table('formulize_screen_listofentries')) as $row) {
		$sets = array();
		if ($columns = old_unserialized($row['advanceview'])) {
			foreach ($columns as $index => $column) {
				$columns[$index][0] = old_reference($column[0], $spellings, 'list columns');
				$columns[$index][1] = old_tokens($column[1], $tokens, 'tokens in list column searches');
			}
			$sets[] = "advanceview = " . old_q(serialize($columns));
		}
		foreach (array('hiddencolumns', 'decolumns') as $column) {
			if ($list = old_unserialized($row[$column])) {
				$sets[] = "$column = " . old_q(serialize(old_list($list, $spellings, 'hidden and inline editing columns')));
			}
		}
		if ($conditions = old_unserialized($row['fundamental_filters'])) {
			$sets[] = "fundamental_filters = " . old_q(serialize(old_conditions($conditions, $spellings, 'list filters')));
		}
		if ($buttons = old_unserialized($row['customactions'])) {
			foreach ($buttons as $button => $settings) {
				foreach ((array) $settings as $key => $effect) {
					if (is_array($effect) AND isset($effect['element'])) {
						$buttons[$button][$key]['element'] = old_reference($effect['element'], $spellings, 'custom buttons');
						$buttons[$button][$key]['value'] = old_quoted($effect['value'], $misspelled, 'handles in custom button code');
					}
				}
			}
			$sets[] = "customactions = " . old_q(serialize($buttons));
		}
		if ($sets) {
			old_write("UPDATE " . old_table('formulize_screen_listofentries') . " SET " . implode(', ', $sets) . " WHERE sid = " . intval($row['sid']));
		}
	}
	foreach (old_rows("SELECT sv_id, sv_oldcols, sv_sort, sv_pubfilters, sv_quicksearches FROM " . old_table('formulize_saved_views')) as $row) {
		old_write("UPDATE " . old_table('formulize_saved_views') . " SET sv_oldcols = " . old_q(old_text_list($row['sv_oldcols'], $spellings, 'saved view columns'))
			. ", sv_sort = " . old_q(old_text_list($row['sv_sort'], $spellings, 'saved view sorting'))
			. ", sv_pubfilters = " . old_q(old_text_list($row['sv_pubfilters'], $spellings, 'saved view search boxes'))
			. ", sv_quicksearches = " . old_q(old_tokens((string) $row['sv_quicksearches'], $tokens, 'tokens in saved view searches'))
			. " WHERE sv_id = " . intval($row['sv_id']));
	}
	foreach (old_rows("SELECT sid, lat_element, lng_element, label_element, description_element, columns, fundamental_filters FROM " . old_table('formulize_screen_map')) as $row) {
		$sets = array();
		foreach (array('lat_element', 'lng_element', 'label_element', 'description_element') as $column) {
			$sets[] = "$column = " . old_q(old_reference($row[$column], $spellings, 'map elements'));
		}
		if ($columns = old_unserialized($row['columns'])) {
			foreach ($columns as $index => $column) {
				$columns[$index][0] = old_reference($column[0], $spellings, 'map columns');
				$columns[$index][1] = old_tokens($column[1], $tokens, 'tokens in map column searches');
			}
			$sets[] = "columns = " . old_q(serialize($columns));
		}
		old_write("UPDATE " . old_table('formulize_screen_map') . " SET " . implode(', ', $sets) . " WHERE sid = " . intval($row['sid']));
	}
	foreach (old_rows("SELECT filterid, filter FROM " . old_table('formulize_group_filters')) as $row) {
		if ($conditions = old_unserialized($row['filter'])) {
			old_write("UPDATE " . old_table('formulize_group_filters') . " SET filter = " . old_q(serialize(old_conditions($conditions, $spellings, 'group filters'))) . " WHERE filterid = " . intval($row['filterid']));
		}
	}
	foreach (old_rows("SELECT sid, pages, conditions, elementdefaults FROM " . old_table('formulize_screen_multipage')) as $row) {
		$sets = array();
		if ($pages = old_unserialized($row['conditions'])) {
			foreach ($pages as $page => $conditions) {
				$pages[$page] = old_conditions($conditions, $spellings, 'page conditions');
			}
			$sets[] = "conditions = " . old_q(serialize($pages));
		}
		if ($pages = old_unserialized($row['pages'])) {
			foreach ($pages as $page => $contents) {
				if (is_array($contents) AND isset($contents[0]) AND $contents[0] === 'PHP') {
					$pages[$page][1] = old_quoted($contents[1], $misspelled, 'handles in custom pages');
				}
			}
			$sets[] = "pages = " . old_q(serialize($pages));
		}
		if ($defaults = old_unserialized($row['elementdefaults'])) {
			$rewritten = array();
			foreach ($defaults as $element => $default) {
				$rewritten[old_reference($element, $spellings, 'screen defaults')] = $default;
			}
			$sets[] = "elementdefaults = " . old_q(serialize($rewritten));
		}
		if ($sets) {
			old_write("UPDATE " . old_table('formulize_screen_multipage') . " SET " . implode(', ', $sets) . " WHERE sid = " . intval($row['sid']));
		}
	}
	foreach (old_rows("SELECT not_cons_id, not_cons_subject FROM " . old_table('formulize_notification_conditions')) as $row) {
		old_write("UPDATE " . old_table('formulize_notification_conditions') . " SET not_cons_subject = " . old_q(old_tokens((string) $row['not_cons_subject'], $tokens, 'notification subjects')) . " WHERE not_cons_id = " . intval($row['not_cons_id']));
	}

	// what is in the code files and the screens' own templates
	$codeFiles = array_filter(array_merge(glob($codeFolder . '*.php'), glob(XOOPS_ROOT_PATH . '/modules/formulize/templates/screens/*/[0-9]*/*')), 'is_file');
	foreach ($codeFiles as $path) {
		$contents = $original = file_get_contents($path);
		foreach ($misspelled as $handle => $spelling) {
			$contents = preg_replace('/\$' . $handle . '(?![a-zA-Z0-9_])/', '$' . $spelling, $contents, -1, $count);
			$changed['variables in code'] = (isset($changed['variables in code']) ? $changed['variables in code'] : 0) + $count;
			$contents = str_replace("'$handle'", "'$spelling'", $contents, $count);
			$changed['handles in code'] = (isset($changed['handles in code']) ? $changed['handles in code'] : 0) + $count;
		}
		// the stale element: the first use in the file, so the rest of the file still names it correctly
		$contents = preg_replace('/\$' . $staleElement . '(?![a-zA-Z0-9_])/', '$' . $staleSpelling, $contents, 1, $count);
		$changed['a stale variable beside the right one'] = (isset($changed['a stale variable beside the right one']) ? $changed['a stale variable beside the right one'] : 0) + $count;
		$contents = str_replace("'$staleElement'", "'$staleSpelling'", $contents, $count);
		$changed['handles in code'] = (isset($changed['handles in code']) ? $changed['handles in code'] : 0) + $count;
		if ($contents !== $original) {
			old_put($path, $contents);
		}
	}

	// the elements themselves, their fields, and the code files named after them
	foreach ($misspelled as $handle => $spelling) {
		$element = $elements[$handle];
		old_write("UPDATE " . old_table('formulize') . " SET ele_handle = " . old_q($spelling) . " WHERE ele_id = " . intval($element['ele_id']));
		old_note('element handles');
		old_write("ALTER TABLE `" . old_table('formulize_' . $element['form_handle']) . "` RENAME COLUMN `$handle` TO `$spelling`");
		old_note('data table fields');
		foreach (array('fullWidthContent', 'captionedContent', 'text', 'textarea', 'number', 'derived') as $type) {
			if (file_exists($codeFolder . $type . '_' . $handle . '.php')) {
				rename($codeFolder . $type . '_' . $handle . '.php', $codeFolder . $type . '_' . $spelling . '.php');
				old_note('code file names');
			}
		}
	}

	// a form with revisions switched on has every field twice
	old_write("DROP TABLE IF EXISTS `$revisionsOfArtifacts`");
	old_write("CREATE TABLE `$revisionsOfArtifacts` LIKE `" . old_table('formulize_artifacts') . "`");
	old_note('revisions table fields');

	// an element in another form that only differs from an element here by case
	$element = old_element($collision['element']);
	old_write("UPDATE " . old_table('formulize') . " SET ele_handle = " . old_q($collision['spelling']) . " WHERE ele_id = " . intval($element['ele_id']));
	old_write("ALTER TABLE `" . old_table('formulize_' . $element['form_handle']) . "` RENAME COLUMN `{$collision['element']}` TO `{$collision['spelling']}`");

	// an element with no field under any spelling of its handle
	$element = old_element($noField['element']);
	old_write("UPDATE " . old_table('formulize') . " SET ele_handle = " . old_q($noField['spelling']) . " WHERE ele_id = " . intval($element['ele_id']));
	old_write("ALTER TABLE `" . old_table('formulize_' . $element['form_handle']) . "` RENAME COLUMN `{$noField['element']}` TO `{$noField['field']}`");

	// what only looks like a handle: words in a caption, and the term of a real condition
	$description = old_element('artifacts_full_description');
	old_write("UPDATE " . old_table('formulize') . " SET ele_caption = " . old_q($plantedCaption) . " WHERE ele_id = " . intval($description['ele_id']));
	$bio = old_element('donors_org_background');
	$conditions = unserialize($bio['ele_filtersettings']);
	$conditions[2][0] = 'Artifacts_Year';
	old_write("UPDATE " . old_table('formulize') . " SET ele_filtersettings = " . old_q(serialize($conditions)) . " WHERE ele_id = " . intval($bio['ele_id']));

	// code the update cannot be sure about, which it has to leave and report
	$procedure = $codeFolder . 'on_before_save_artifacts.php';
	old_put($procedure, file_get_contents($procedure) . $plantedKey);

	ksort($changed);
	echo json_encode($changed) . "\n";
	exit(0);
}

if ($command == 'second-visit') {
	$element = old_element($noField['spelling']);
	old_write("ALTER TABLE `" . old_table('formulize_' . $element['form_handle']) . "` RENAME COLUMN `{$noField['field']}` TO `{$noField['spelling']}`");
	old_write("ALTER TABLE `$revisionsOfArtifacts` RENAME COLUMN `{$noField['element']}` TO `{$noField['spelling']}`");
	$description = old_element('artifacts_full_description');
	old_write("UPDATE " . old_table('formulize') . " SET ele_caption = " . old_q($description['ele_caption'] . $plantedStale) . " WHERE ele_id = " . intval($description['ele_id']));
	echo "done\n";
	exit(0);
}

if ($command == 'planted') {
	$procedure = file_get_contents($codeFolder . 'on_before_save_artifacts.php');
	$columnsOf = function($table) {
		return array_map(fn($row) => $row['COLUMN_NAME'], old_rows("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . old_q($table) . " ORDER BY ORDINAL_POSITION"));
	};
	echo json_encode(array(
		'caption' => old_element('artifacts_full_description')['ele_caption'],
		'term' => unserialize(old_element('donors_org_background')['ele_filtersettings'])[2][0],
		'key_left_in_code' => strpos($procedure, $plantedKey) !== false,
		'data_fields' => $columnsOf(old_table('formulize_artifacts')),
		'revisions_fields' => $columnsOf($revisionsOfArtifacts),
		'dbversion' => intval(old_rows("SELECT dbversion FROM " . old_table('modules') . " WHERE dirname = 'formulize'")[0]['dbversion']),
	)) . "\n";
	exit(0);
}

if ($command == 'tidy') {
	$before = json_decode(file_get_contents($keepFile), true);
	$original = function($table, $keyColumn, $key, $column) use ($before) {
		foreach ($before['tables'][$table]['rows'] as $row) {
			if ($row[$keyColumn] == $key) {
				return $row[$column];
			}
		}
		fwrite(STDERR, "$table row $key was not captured\n");
		exit(1);
	};
	$description = old_element('artifacts_full_description');
	old_write("UPDATE " . old_table('formulize') . " SET ele_caption = " . old_q($original('formulize', 'ele_id', $description['ele_id'], 'ele_caption')) . " WHERE ele_id = " . intval($description['ele_id']));
	$bio = old_element('donors_org_background');
	old_write("UPDATE " . old_table('formulize') . " SET ele_filtersettings = " . old_q($original('formulize', 'ele_id', $bio['ele_id'], 'ele_filtersettings')) . " WHERE ele_id = " . intval($bio['ele_id']));
	$procedure = $codeFolder . 'on_before_save_artifacts.php';
	old_put($procedure, str_replace($plantedKey, '', file_get_contents($procedure)));
	old_write("DROP TABLE IF EXISTS `$revisionsOfArtifacts`");
	// the element that was given another element's handle, under the handle the update gave it in its place
	$collided = isset($argv[2]) ? $argv[2] : '';
	$element = old_element($collided);
	old_write("UPDATE " . old_table('formulize') . " SET ele_handle = " . old_q($collision['element']) . " WHERE ele_id = " . intval($element['ele_id']));
	old_write("ALTER TABLE `" . old_table('formulize_' . $element['form_handle']) . "` RENAME COLUMN `$collided` TO `{$collision['element']}`");
	echo "tidied\n";
	exit(0);
}

fwrite(STDERR, "usage: php old-handles.php capture | break | second-visit | planted | tidy | compare\n");
exit(1);
