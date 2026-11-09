<?php
/**
 * Records which lines of PHP each request runs, for a coverage report of the whole test suite
 *
 * PHP runs this before every web request and every command line script, when the Manual E2E Test Suite is run with
 * coverage on. coverage.ini makes it the auto_prepend_file, and docker-compose.coverage.yaml puts that in the web
 * container. It does nothing anywhere else, ie: if it's requested directly, or Xdebug isn't in coverage mode.
 *
 * Each request's lines go into a file named for what's in it, so all the requests that run the same lines write
 * one file. merge.php puts them together after the run. The lines go under the label in the phase file, which the
 * workflow changes as it starts each set of tests, so the report can say what each set runs.
 *
 * Some requests also record which lines in each file can run at all, so the report can say what share of them ran.
 * That takes longer, so it's one web request in ten, and every command line script, since there are only a few.
 * Each file's lines are written once.
 *
 * @package Formulize
 * @subpackage Tests
 */

define('FORMULIZE_COVERAGE_FOLDER', '/var/www/coverage');

if (realpath((string) ini_get('auto_prepend_file')) === __FILE__
	AND function_exists('xdebug_start_code_coverage')
	AND in_array('coverage', explode(',', (string) ini_get('xdebug.mode')))
	AND is_dir(FORMULIZE_COVERAGE_FOLDER . '/data')) {

	require_once __DIR__ . '/excluded_paths.php';
	xdebug_set_filter(XDEBUG_FILTER_CODE_COVERAGE, XDEBUG_PATH_EXCLUDE, formulize_coverageExcludedPaths(dirname(__DIR__, 2)));
	$GLOBALS['formulize_coverageRecordsExecutableLines'] = (PHP_SAPI === 'cli' OR mt_rand(1, 10) === 1);
	xdebug_start_code_coverage($GLOBALS['formulize_coverageRecordsExecutableLines'] ? XDEBUG_CC_UNUSED : 0);

	// Registered from inside a shutdown function, so it goes after every shutdown function the request registers, and gets their lines too
	register_shutdown_function(function () {
		register_shutdown_function('formulize_coverageWriteRequest');
	});
}

/**
 * Write the lines this request ran to the coverage data folder, and the lines that can run in the files it loaded, if it recorded those
 *
 * @return void
 */
function formulize_coverageWriteRequest() {
	$coverage = xdebug_get_code_coverage();
	xdebug_stop_code_coverage(false);
	$testsFolder = dirname(__DIR__) . '/';
	$linesRun = array();
	foreach ($coverage as $file => $lines) {
		// code made at runtime has no file of its own, and the filter only starts after this file has been read, so it's skipped here
		if (substr($file, 0, 1) !== '/' OR strpos($file, "eval()'d code") !== false OR strpos($file, $testsFolder) === 0) {
			continue;
		}
		if ($GLOBALS['formulize_coverageRecordsExecutableLines']) {
			formulize_coverageWriteExecutableLines($file, array_keys($lines));
		}
		$lineNumbers = array_keys(array_filter($lines, function ($hits) { return $hits > 0; }));
		if ($lineNumbers) {
			$linesRun[$file] = formulize_coverageRanges($lineNumbers);
		}
	}
	if (!$linesRun) {
		return;
	}
	ksort($linesRun);
	$phase = preg_replace('/[^A-Za-z0-9._-]/', '', trim((string) @file_get_contents(FORMULIZE_COVERAGE_FOLDER . '/phase')));
	$content = json_encode($linesRun);
	formulize_coverageWriteOnce(FORMULIZE_COVERAGE_FOLDER . '/data/' . ($phase !== '' ? $phase : 'unlabelled'), md5($content) . '.json.gz', $content);
}

/**
 * Write the lines that can run in a file, if they haven't been written already
 *
 * @param string $file The file
 * @param array $lineNumbers The lines in it that can run
 * @return void
 */
function formulize_coverageWriteExecutableLines($file, $lineNumbers) {
	$name = md5($file) . '.json.gz';
	$folder = FORMULIZE_COVERAGE_FOLDER . '/executable';
	if ($lineNumbers AND !file_exists("$folder/$name")) {
		formulize_coverageWriteOnce($folder, $name, json_encode(array('file' => $file, 'lines' => formulize_coverageRanges($lineNumbers))));
	}
}

/**
 * Write compressed content to a file in a folder, unless the file is there already
 *
 * @param string $folder The folder, which is made if it isn't there
 * @param string $name The name of the file
 * @param string $content What to write
 * @return void
 */
function formulize_coverageWriteOnce($folder, $name, $content) {
	if (!is_dir($folder)) {
		@mkdir($folder, 0777, true);
		@chmod($folder, 0777); // the web server and the command line run as different users, and both write here
	}
	if (!file_exists("$folder/$name")) {
		@file_put_contents("$folder/$name", gzencode($content, 6), LOCK_EX);
	}
}

/**
 * Turn a list of line numbers into ranges, ie: 3,4,5,6,9 becomes 3-6,9. Lines run in runs, so this is much smaller than a list.
 *
 * @param array $lineNumbers The line numbers
 * @return string The ranges, separated by commas
 */
function formulize_coverageRanges($lineNumbers) {
	sort($lineNumbers);
	$ranges = array();
	$start = $previous = array_shift($lineNumbers);
	foreach ($lineNumbers as $lineNumber) {
		if ($lineNumber == $previous + 1) {
			$previous = $lineNumber;
			continue;
		}
		$ranges[] = $start == $previous ? "$start" : "$start-$previous";
		$start = $previous = $lineNumber;
	}
	$ranges[] = $start == $previous ? "$start" : "$start-$previous";
	return implode(',', $ranges);
}
