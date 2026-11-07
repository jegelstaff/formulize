<?php
/**
 * Standalone logic tests for what the update patch takes to be an old spelling of an element handle.
 *
 * Not PHPUnit or Playwright - a single dependency-free script, like the other tests in this folder:
 *   php tests/element_handle_normalization_test.php
 *
 * WHAT THIS COVERS
 * formulize_normalize_element_handles() in admin/patches/002_always_run.php rewrites references written with
 * capitals or hyphens to elements whose own handles are already right. Whether a word really is such a reference
 * is decided by formulizeElementReferences, against a live database. This covers the two steps in front of that:
 * - formulize_handle_spellings_in_text(), which proposes the words worth asking about. Formulize's own {tokens} in
 *   capitals and the correct notification tags have to stay out, because each would be a rewrite of something
 *   that is not out of date, and what can be written in front of a handle has to come off so the handle is matched.
 * - formulize_canonical_handle(), which folds the two changes that left references behind and nothing else.
 *
 * @package Formulize
 * @subpackage tests
 */

if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit;
}

function stop($message) { // not die(), which exits with 0, so a test it stopped would pass in CI
	fwrite(STDERR, $message);
	exit(1);
}

$repo = dirname(__DIR__);
define('XOOPS_ROOT_PATH', $repo);

// The prefix as the real class has it, without bringing in what the class needs around it
if (!preg_match("/const HIDDEN_COLUMN_PREFIX = '([^']+)';/", file_get_contents($repo . '/modules/formulize/class/savedViews.php'), $match)) {
	stop("no HIDDEN_COLUMN_PREFIX in savedViews.php\n");
}
eval('class formulizeSavedViewsHandler { const HIDDEN_COLUMN_PREFIX = ' . var_export($match[1], true) . '; }');

require_once $repo . '/modules/formulize/admin/patches/002_always_run.php';

$failures = 0;
function check($label, $got, $want) {
	global $failures;
	sort($got);
	sort($want);
	if ($got === $want) {
		echo "ok   $label\n";
	} else {
		$failures++;
		echo "FAIL $label\n     got:  " . json_encode($got) . "\n     want: " . json_encode($want) . "\n";
	}
}

// ---- what counts as a spelling worth asking about ----
check('capitals and an underscore', formulize_handle_spellings_in_text('Artifacts_Year'), array('Artifacts_Year'));
check('a hyphen', formulize_handle_spellings_in_text('artifacts-year'), array('artifacts-year'));
check('a handle as it should be is not asked about', formulize_handle_spellings_in_text('artifacts_year'), array());
check('a single word is not, it could be any word', formulize_handle_spellings_in_text('Name'), array());
check('a list of them', formulize_handle_spellings_in_text('49,Artifacts_Year,artifacts_year,My-Handle'), array('Artifacts_Year', 'My-Handle'));
check('accented characters stay part of the word', formulize_handle_spellings_in_text("Caf\xc3\xa9_Name"), array("Caf\xc3\xa9_Name"));

// ---- settings stored serialized are read the same way ----
check('a serialized setting', formulize_handle_spellings_in_text(serialize(array(array('Artifacts_Year', 'name'), array('='), array('term')))), array('Artifacts_Year'));

// ---- what is written in front of a handle ----
check('a hidden column in a saved view', formulize_handle_spellings_in_text('hiddencolumn_Artifacts_Year'), array('hiddencolumn_Artifacts_Year', 'Artifacts_Year'));
check('a {token}', formulize_handle_spellings_in_text('Hello {Artifacts_Year}'), array('Artifacts_Year'));
check('a {search_handle} token', formulize_handle_spellings_in_text('{search_Artifacts_Year}'), array('search_Artifacts_Year', 'Artifacts_Year'));
check('a correct handle after a prefix is nothing to ask about', formulize_handle_spellings_in_text('{search_artifacts_year}'), array());

// ---- Formulize's own tokens are not old spellings of an element that happens to share the name ----
foreach (array('{USER}', '{TODAY}', '{BLANK}', '{ID}', '{FULLNAMES}', '{USERNAMES}') as $token) {
	check("$token is Formulize's own", formulize_handle_spellings_in_text($token), array());
}

// ---- notification tags are capitals by design, so only a hyphen makes one out of date ----
check('a tag spelled correctly is not picked out', array_diff(formulize_handle_spellings_in_text('{ELEMENT_ARTIFACTS_YEAR}'), array('ELEMENT_ARTIFACTS_YEAR')), array());
check('nor a revision tag', array_diff(formulize_handle_spellings_in_text('{REVISION_ELEMENT_ARTIFACTS_YEAR}'), array('REVISION_ELEMENT_ARTIFACTS_YEAR')), array());
check('a tag with a hyphen is', array_values(array_diff(formulize_handle_spellings_in_text('{ELEMENT_ARTIFACTS-YEAR}'), array('ELEMENT_ARTIFACTS-YEAR'))), array('ARTIFACTS-YEAR'));
check('a revision tag with a hyphen is', array_values(array_diff(formulize_handle_spellings_in_text('{REVISION_ELEMENT_ARTIFACTS-YEAR}'), array('REVISION_ELEMENT_ARTIFACTS-YEAR'))), array('ARTIFACTS-YEAR'));

// ---- folding: the two changes that left references behind, and no more ----
function checkCanonical($label, $handle, $want) {
	global $failures;
	$got = formulize_canonical_handle($handle);
	echo ($got === $want ? "ok   " : "FAIL ") . "$label" . ($got === $want ? '' : ": got $got, want $want") . "\n";
	$failures += ($got === $want ? 0 : 1);
}
checkCanonical('capitals are folded', 'Artifacts_Year', 'artifacts_year');
checkCanonical('hyphens are folded', 'artifacts-year', 'artifacts_year');
checkCanonical('anything else is not, so foo.bar is not an old spelling of foobar', 'foo.bar', 'foo.bar');

echo $failures ? "\n$failures FAILED\n" : "\nALL PASSED\n";
exit($failures ? 1 : 0);
