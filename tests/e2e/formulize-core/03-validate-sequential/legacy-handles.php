<?php
/**
 * Writes, reads and removes the legacy settings that 020-legacy-handles.spec.js renames an element under.
 *
 * Formulize saves an element's id in its settings now, but sites can still hold settings written when it saved the
 * handle. This puts that kind of setting straight into the database - the element by its handle in a condition, a
 * list column, a map column, a custom button effect, a screen default, a saved view and a group filter - each one
 * next to a value that is the handle's text but is not a reference to the element: a condition's term, a search
 * term, a button's value, a default value. The spec renames the element through the admin UI, and then asks this
 * what is stored.
 *
 * Usage, from the web container:
 *   php legacy-handles.php install <handle>   writes the settings, keeping what was there to put back
 *   php legacy-handles.php read <handle>      prints what is now stored at each place, as JSON
 *   php legacy-handles.php remove             puts back what install replaced, and removes what it added
 *
 * The Donor list's source has commands of its own, because the spec checks that one on the artifact's form, where the
 * condition install puts on the Donor list would hide it:
 *   php legacy-handles.php install-source <handle>   stores the source by handle, keeping what was there to put back
 *   php legacy-handles.php read-source               prints the source as it is stored now
 *   php legacy-handles.php remove-source             puts back what install-source replaced
 *
 * And for an element whose handle is a number, as on sites made before handles existed:
 *   php legacy-handles.php install-numeric   gives Latitude its id as its handle and its field name, and prints the id
 *   php legacy-handles.php remove-numeric    puts Latitude's handle and field back, if a run left them as a number
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
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/class/savedViews.php';

global $xoopsDB;
$keepFile = sys_get_temp_dir() . '/formulize_legacy_handles_shim.json';
$command = isset($argv[1]) ? $argv[1] : '';
$handle = isset($argv[2]) ? $argv[2] : '';

function legacy_table($name) { global $xoopsDB; return $xoopsDB->prefix($name); }
function legacy_one($sql) {
	global $xoopsDB;
	$result = $xoopsDB->query($sql);
	return $result ? $xoopsDB->fetchArray($result) : false;
}
function legacy_write($sql) {
	global $xoopsDB;
	if (!$xoopsDB->queryF($sql)) {
		fwrite(STDERR, "could not write: $sql\n" . $xoopsDB->error() . "\n");
		exit(1);
	}
}
function legacy_q($value) { global $xoopsDB; return $xoopsDB->quoteString((string) $value); }

// the places, found the same way every time
function legacy_places() {
	$artifacts = legacy_one("SELECT id_form FROM " . legacy_table('formulize_id') . " WHERE form_title = 'Artifacts'");
	$list = legacy_one("SELECT sid FROM " . legacy_table('formulize_screen') . " WHERE title = 'Artifacts' AND type = 'listOfEntries'");
	$map = legacy_one("SELECT sid FROM " . legacy_table('formulize_screen') . " WHERE screen_handle = 'artifact_origins'");
	$form = legacy_one("SELECT sid FROM " . legacy_table('formulize_screen') . " WHERE title = 'Artifact' AND type = 'multiPage'");
	$donor = legacy_one("SELECT ele_id FROM " . legacy_table('formulize') . " WHERE ele_handle = 'artifacts_donor'");
	$shortName = legacy_one("SELECT ele_id FROM " . legacy_table('formulize') . " WHERE ele_handle = 'artifacts_short_name'");
	$donorsBio = legacy_one("SELECT ele_id FROM " . legacy_table('formulize') . " WHERE ele_handle = 'donors_bio'");
	$group = legacy_one("SELECT groupid FROM " . legacy_table('groups') . " WHERE name = 'Departments - Curators'");
	foreach (array($artifacts, $list, $map, $form, $donor, $shortName, $donorsBio, $group) as $found) {
		if (!$found) {
			fwrite(STDERR, "the museum system is not there to put the settings in\n");
			exit(1);
		}
	}
	return array('fid' => intval($artifacts['id_form']), 'list' => intval($list['sid']), 'map' => intval($map['sid']),
		'form' => intval($form['sid']), 'donor' => intval($donor['ele_id']), 'shortName' => intval($shortName['ele_id']),
		'donorsBio' => intval($donorsBio['ele_id']), 'group' => intval($group['groupid']));
}

$places = legacy_places();

if ($command == 'install') {
	if ($handle === '' OR file_exists($keepFile)) {
		fwrite(STDERR, $handle === '' ? "give the handle\n" : "already installed - remove it first\n");
		exit(1);
	}
	$keep = array();

	// a condition naming the element by handle, beside one whose term is the handle's text
	$row = legacy_one("SELECT ele_filtersettings FROM " . legacy_table('formulize') . " WHERE ele_id = {$places['donor']}");
	$keep['filtersettings'] = $row['ele_filtersettings'];
	$conditions = array(0 => array($handle, $places['shortName']), 1 => array('=', '='), 2 => array('Yes', $handle), 3 => array('all', 'all'));
	legacy_write("UPDATE " . legacy_table('formulize') . " SET ele_filtersettings = " . legacy_q(serialize($conditions)) . " WHERE ele_id = {$places['donor']}");

	// A setting that only ever holds ids, where a word is something a person typed: a source mapping copies its value
	// into each donor made from the Donor list, and this one copies the handle's text. A rename has to leave it.
	$row = legacy_one("SELECT ele_value FROM " . legacy_table('formulize') . " WHERE ele_id = {$places['donor']}");
	$keep['donor_ele_value'] = $row['ele_value'];
	$eleValue = unserialize($row['ele_value']);
	$eleValue['linkedSourceMappings'] = array(array('thisForm' => $handle, 'sourceForm' => $places['donorsBio']));
	legacy_write("UPDATE " . legacy_table('formulize') . " SET ele_value = " . legacy_q(serialize($eleValue)) . " WHERE ele_id = {$places['donor']}");

	// a list column by handle, searching for the handle's text; a custom button effect by handle, writing the handle's text
	$row = legacy_one("SELECT advanceview, customactions FROM " . legacy_table('formulize_screen_listofentries') . " WHERE sid = {$places['list']}");
	$keep['advanceview'] = $row['advanceview'];
	$keep['customactions'] = $row['customactions'];
	$advanceview = unserialize($row['advanceview']);
	$advanceview[] = array($handle, $handle, '', 'Box');
	$customactions = unserialize($row['customactions']);
	$button = key($customactions);
	$customactions[$button][] = array('element' => $handle, 'action' => 'replace', 'value' => $handle);
	legacy_write("UPDATE " . legacy_table('formulize_screen_listofentries') . " SET advanceview = " . legacy_q(serialize($advanceview)) . ", customactions = " . legacy_q(serialize($customactions)) . " WHERE sid = {$places['list']}");

	// a map column by handle, with no search, so the map still shows every pin
	$row = legacy_one("SELECT columns FROM " . legacy_table('formulize_screen_map') . " WHERE sid = {$places['map']}");
	$keep['mapcolumns'] = $row['columns'];
	$columns = unserialize($row['columns']);
	$columns[] = array($handle, '', 'Box');
	legacy_write("UPDATE " . legacy_table('formulize_screen_map') . " SET columns = " . legacy_q(serialize($columns)) . " WHERE sid = {$places['map']}");

	// a screen default by handle, whose default is the handle's text
	$row = legacy_one("SELECT elementdefaults FROM " . legacy_table('formulize_screen_multipage') . " WHERE sid = {$places['form']}");
	$keep['elementdefaults'] = $row['elementdefaults'];
	$defaults = unserialize((string) $row['elementdefaults']);
	$defaults = is_array($defaults) ? $defaults : array(); // a screen with none can hold b:0;
	$defaults[$handle] = $handle;
	legacy_write("UPDATE " . legacy_table('formulize_screen_multipage') . " SET elementdefaults = " . legacy_q(serialize($defaults)) . " WHERE sid = {$places['form']}");

	// a saved view naming it by handle in its columns, sort and published search box, searching for the handle's text
	legacy_write("INSERT INTO " . legacy_table('formulize_saved_views') . " SET sv_name = 'Legacy handles shim', sv_formframe = {$places['fid']}, sv_mainform = 0,
		sv_oldcols = " . legacy_q("$handle,artifacts_short_name") . ", sv_sort = " . legacy_q($handle) . ", sv_order = 'SORT_ASC', sv_pubfilters = " . legacy_q($handle) . ",
		sv_quicksearches = " . legacy_q($handle . formulizeSavedViewsHandler::SEARCH_DELIMITER) . ", sv_currentview = 'all'");
	$keep['view'] = intval($xoopsDB->getInsertId());

	// a group filter naming it by handle, comparing it to the handle's text
	$filter = array(0 => array($handle), 1 => array('!='), 2 => array($handle), 3 => array('all'));
	legacy_write("INSERT INTO " . legacy_table('formulize_group_filters') . " (fid, groupid, filter) VALUES ({$places['fid']}, {$places['group']}, " . legacy_q(serialize($filter)) . ")");
	$keep['groupfilter'] = intval($xoopsDB->getInsertId());

	file_put_contents($keepFile, json_encode($keep));
	echo "installed\n";
	exit(0);
}

if ($command == 'read') {
	// what is at each reference, and at the value beside it that only looks like the handle
	$filtersettings = unserialize(legacy_one("SELECT ele_filtersettings FROM " . legacy_table('formulize') . " WHERE ele_id = {$places['donor']}")['ele_filtersettings']);
	$list = legacy_one("SELECT advanceview, customactions FROM " . legacy_table('formulize_screen_listofentries') . " WHERE sid = {$places['list']}");
	$advanceview = unserialize($list['advanceview']);
	$customactions = unserialize($list['customactions']);
	$effects = array_filter(reset($customactions), 'is_array');
	$mapColumns = unserialize(legacy_one("SELECT columns FROM " . legacy_table('formulize_screen_map') . " WHERE sid = {$places['map']}")['columns']);
	$defaults = unserialize((string) legacy_one("SELECT elementdefaults FROM " . legacy_table('formulize_screen_multipage') . " WHERE sid = {$places['form']}")['elementdefaults']);
	$defaults = is_array($defaults) ? $defaults : array();
	$view = legacy_one("SELECT sv_oldcols, sv_sort, sv_pubfilters, sv_quicksearches FROM " . legacy_table('formulize_saved_views') . " WHERE sv_name = 'Legacy handles shim'");
	$groupFilter = unserialize(legacy_one("SELECT filter FROM " . legacy_table('formulize_group_filters') . " WHERE fid = {$places['fid']} AND groupid = {$places['group']} ORDER BY filterid DESC")['filter']);
	$lastEffect = end($effects);
	echo json_encode(array(
		'condition_element' => (string) $filtersettings[0][0], 'condition_term' => (string) $filtersettings[2][1],
		'list_column' => (string) end($advanceview)[0], 'list_search' => (string) end($advanceview)[1],
		'map_column' => (string) end($mapColumns)[0],
		'button_element' => (string) $lastEffect['element'], 'button_value' => (string) $lastEffect['value'],
		'screen_default_keys' => array_map('strval', array_keys($defaults)), 'screen_default_values' => array_map('strval', array_values($defaults)),
		'view_columns' => $view['sv_oldcols'], 'view_sort' => $view['sv_sort'], 'view_pubfilters' => $view['sv_pubfilters'],
		'view_search' => explode(formulizeSavedViewsHandler::SEARCH_DELIMITER, $view['sv_quicksearches'])[0],
		'group_filter_element' => (string) $groupFilter[0][0], 'group_filter_term' => (string) $groupFilter[2][0],
		'mapping_value' => (string) unserialize(legacy_one("SELECT ele_value FROM " . legacy_table('formulize') . " WHERE ele_id = {$places['donor']}")['ele_value'])['linkedSourceMappings'][0]['thisForm'],
	)) . "\n";
	exit(0);
}

if ($command == 'remove') {
	if (!file_exists($keepFile)) {
		echo "nothing to remove\n";
		exit(0);
	}
	$keep = json_decode(file_get_contents($keepFile), true);
	legacy_write("UPDATE " . legacy_table('formulize') . " SET ele_filtersettings = " . legacy_q($keep['filtersettings']) . ", ele_value = " . legacy_q($keep['donor_ele_value']) . " WHERE ele_id = {$places['donor']}");
	legacy_write("UPDATE " . legacy_table('formulize_screen_listofentries') . " SET advanceview = " . legacy_q($keep['advanceview']) . ", customactions = " . legacy_q($keep['customactions']) . " WHERE sid = {$places['list']}");
	legacy_write("UPDATE " . legacy_table('formulize_screen_map') . " SET columns = " . legacy_q($keep['mapcolumns']) . " WHERE sid = {$places['map']}");
	legacy_write("UPDATE " . legacy_table('formulize_screen_multipage') . " SET elementdefaults = " . legacy_q($keep['elementdefaults']) . " WHERE sid = {$places['form']}");
	legacy_write("DELETE FROM " . legacy_table('formulize_saved_views') . " WHERE sv_id = " . intval($keep['view']));
	legacy_write("DELETE FROM " . legacy_table('formulize_group_filters') . " WHERE filterid = " . intval($keep['groupfilter']));
	unlink($keepFile);
	echo "removed\n";
	exit(0);
}

$sourceKeepFile = sys_get_temp_dir() . '/formulize_legacy_source_shim.json';
function legacy_donor_ele_value($donor) {
	return legacy_one("SELECT ele_value FROM " . legacy_table('formulize') . " WHERE ele_id = " . intval($donor))['ele_value'];
}

if ($command == 'install-source') {
	if ($handle === '' OR file_exists($sourceKeepFile)) {
		fwrite(STDERR, $handle === '' ? "give the handle\n" : "already installed - remove it first\n");
		exit(1);
	}
	$stored = legacy_donor_ele_value($places['donor']);
	file_put_contents($sourceKeepFile, json_encode(array('ele_value' => $stored)));
	$eleValue = unserialize($stored);
	$eleValue[2] = explode('#*=:*', $eleValue[2])[0] . '#*=:*' . $handle;
	legacy_write("UPDATE " . legacy_table('formulize') . " SET ele_value = " . legacy_q(serialize($eleValue)) . " WHERE ele_id = {$places['donor']}");
	echo "installed\n";
	exit(0);
}

if ($command == 'read-source') {
	echo unserialize(legacy_donor_ele_value($places['donor']))[2] . "\n";
	exit(0);
}

if ($command == 'remove-source') {
	if (!file_exists($sourceKeepFile)) {
		echo "nothing to remove\n";
		exit(0);
	}
	$keep = json_decode(file_get_contents($sourceKeepFile), true);
	legacy_write("UPDATE " . legacy_table('formulize') . " SET ele_value = " . legacy_q($keep['ele_value']) . " WHERE ele_id = {$places['donor']}");
	unlink($sourceKeepFile);
	echo "removed\n";
	exit(0);
}

// Sites made before element handles existed have a number for each one, the element's own id, and their data table
// field has that name as well. A rename of such an element has to leave every number in the settings alone, because
// each is the id it always was.
if ($command == 'install-numeric' OR $command == 'remove-numeric') {
	$element = legacy_one("SELECT ele_id, ele_handle FROM " . legacy_table('formulize') . " WHERE ele_handle IN ('artifacts_latitude', ele_id) AND id_form = {$places['fid']} ORDER BY ele_handle = 'artifacts_latitude' DESC LIMIT 1");
	if (!$element) {
		fwrite(STDERR, "there is no Latitude element\n");
		exit(1);
	}
	$id = intval($element['ele_id']);
	$data = legacy_table('formulize_artifacts');
	if ($command == 'install-numeric' AND $element['ele_handle'] === 'artifacts_latitude') {
		legacy_write("UPDATE " . legacy_table('formulize') . " SET ele_handle = '$id' WHERE ele_id = $id");
		legacy_write("ALTER TABLE `$data` RENAME COLUMN `artifacts_latitude` TO `$id`");
		echo "$id\n";
	} elseif ($command == 'remove-numeric' AND $element['ele_handle'] === (string) $id) {
		legacy_write("UPDATE " . legacy_table('formulize') . " SET ele_handle = 'artifacts_latitude' WHERE ele_id = $id");
		legacy_write("ALTER TABLE `$data` RENAME COLUMN `$id` TO `artifacts_latitude`");
		echo "removed\n";
	} else {
		echo $command == 'install-numeric' ? "already installed\n" : "nothing to remove\n";
	}
	exit(0);
}

fwrite(STDERR, "usage: php legacy-handles.php install <handle> | read <handle> | remove | install-source <handle> | read-source | remove-source | install-numeric | remove-numeric\n");
exit(1);
