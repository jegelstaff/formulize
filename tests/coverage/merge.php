<?php
/**
 * Puts together the coverage that prepend.php recorded during a test run, and writes the report
 *
 * Run it on the command line in the web container, after the tests, without the prepend file:
 *   php -d auto_prepend_file= -d xdebug.mode=off tests/coverage/merge.php --data=/var/www/coverage --out=/var/www/coverage/report
 *     [--tracked=/var/www/coverage/tracked.txt] [--diff=/var/www/coverage/changed.diff] [--base=master]
 *
 * --tracked is the PHP files in the repository, one per line, from git ls-files. Only those are in the report, and the
 * ones no test loaded are listed. --diff is git diff -U0 between the base branch and the branch that was tested, for the
 * report on the lines that changed. --base is the name of the base branch, for the report.
 *
 * What it writes in --out:
 * - summary.md, the totals, by part of the code, by set of tests, and the files with the most lines that didn't run
 * - changed-lines.md, the lines that changed and can run, and whether any test ran them, if there's a diff
 * - files.md, every file in the report
 * - coverage.json, the lines that can run and the lines that ran, by file and by set of tests, for looking into further
 * - lcov.info, the same in the format coverage tools and editors read, ie: the Coverage Gutters extension for VS Code
 *
 * A line counts as able to run if a request that recorded those lines said so, or if a test ran it. Files loaded only by
 * requests that didn't record them only have the lines that ran, so they're listed without a share.
 *
 * @package Formulize
 * @subpackage Tests
 */

if (PHP_SAPI !== 'cli') {
	exit();
}

require_once __DIR__ . '/excluded_paths.php';

$options = getopt('', array('data:', 'out:', 'tracked::', 'diff::', 'base::', 'webroot::'));
if (empty($options['data']) OR empty($options['out'])) {
	fwrite(STDERR, "Usage: php merge.php --data=folder --out=folder [--tracked=file] [--diff=file] [--base=branch] [--webroot=folder]\n");
	exit(1);
}
$webroot = rtrim(isset($options['webroot']) ? $options['webroot'] : dirname(__DIR__, 2), '/');
$outFolder = rtrim($options['out'], '/');
if (!is_dir($outFolder) AND !mkdir($outFolder, 0777, true)) {
	fwrite(STDERR, "Can't make $outFolder\n");
	exit(1);
}

// the lines that ran, by file, and by file in each set of tests
$linesRun = array();
$linesRunByPhase = array();
$requestFiles = 0;
foreach ((array) glob(rtrim($options['data'], '/') . '/data/*', GLOB_ONLYDIR) as $phaseFolder) {
	$phase = basename($phaseFolder);
	foreach ((array) glob("$phaseFolder/*.json.gz") as $requestFile) {
		$request = json_decode((string) @gzdecode((string) file_get_contents($requestFile)), true);
		if (!is_array($request)) {
			continue;
		}
		$requestFiles++;
		foreach ($request as $file => $ranges) {
			foreach (formulize_coverageLinesInRanges($ranges) as $line) {
				$linesRun[$file][$line] = true;
				$linesRunByPhase[$phase][$file][$line] = true;
			}
		}
	}
}

// the lines that can run, by file
$executableLines = array();
foreach ((array) glob(rtrim($options['data'], '/') . '/executable/*.json.gz') as $mapFile) {
	$map = json_decode((string) @gzdecode((string) file_get_contents($mapFile)), true);
	if (is_array($map) AND isset($map['file'])) {
		foreach (formulize_coverageLinesInRanges($map['lines']) as $line) {
			$executableLines[$map['file']][$line] = true;
		}
	}
}

// the files in the report, by their path in the repository
$excluded = formulize_coverageExcludedPaths($webroot);
$tracked = null;
if (!empty($options['tracked']) AND is_readable($options['tracked'])) {
	$tracked = array();
	foreach (file($options['tracked'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $relativePath) {
		$tracked[trim($relativePath)] = true;
	}
}
$files = array();
foreach (array_keys($linesRun + $executableLines) as $file) {
	if (strpos($file, "$webroot/") !== 0 OR formulize_coverageIsExcluded($file, $excluded)) {
		continue;
	}
	$relativePath = substr($file, strlen("$webroot/"));
	if ($tracked !== null AND !isset($tracked[$relativePath])) {
		continue; // made while the tests ran, ie: a screen template
	}
	$run = isset($linesRun[$file]) ? $linesRun[$file] : array();
	$executable = (isset($executableLines[$file]) ? $executableLines[$file] : array()) + $run;
	ksort($executable);
	$files[$relativePath] = array(
		'executable' => array_keys($executable),
		'run' => array_keys($run),
		'hasExecutableLines' => isset($executableLines[$file]),
		'phases' => array(),
	);
	foreach ($linesRunByPhase as $phase => $phaseFiles) {
		if (isset($phaseFiles[$file])) {
			$files[$relativePath]['phases'][$phase] = array_keys($phaseFiles[$file]);
		}
	}
}
ksort($files);
$neverLoaded = array();
if ($tracked !== null) {
	foreach (array_keys($tracked) as $relativePath) {
		if (!isset($files[$relativePath]) AND !formulize_coverageIsExcluded("$webroot/$relativePath", $excluded)) {
			$neverLoaded[] = $relativePath;
		}
	}
	sort($neverLoaded);
}
$phases = formulize_coveragePhasesInOrder(array_keys($linesRunByPhase));

// totals
$totals = array('executable' => 0, 'run' => 0, 'files' => 0, 'filesWithoutShare' => 0);
$areas = array();
foreach ($files as $relativePath => $info) {
	$area = formulize_coverageArea($relativePath);
	if (!isset($areas[$area])) {
		$areas[$area] = array('executable' => 0, 'run' => 0, 'files' => 0);
	}
	$areas[$area]['files']++;
	$totals['files']++;
	if (!$info['hasExecutableLines']) {
		$totals['filesWithoutShare']++;
		continue;
	}
	$areas[$area]['executable'] += count($info['executable']);
	$areas[$area]['run'] += count($info['run']);
	$totals['executable'] += count($info['executable']);
	$totals['run'] += count($info['run']);
}
ksort($areas);

// summary.md
$summary = "# PHP coverage of the test suite\n\n";
$summary .= "From $requestFiles distinct requests. Requests that ran the same lines are counted once.\n\n";
$summary .= "**" . formulize_coverageShare($totals['run'], $totals['executable']) . "** of the lines that can run were run, " . number_format($totals['run']) . " of " . number_format($totals['executable']) . ", in " . number_format($totals['files']) . " files that the tests loaded.";
if ($totals['filesWithoutShare']) {
	$summary .= " " . $totals['filesWithoutShare'] . " of those files were only loaded by requests that didn't record which lines can run, so they aren't in the share.";
}
$summary .= "\n\n";
if ($tracked !== null) {
	$summary .= number_format(count($neverLoaded)) . " PHP files in the repository were never loaded. They're listed in files.md.\n\n";
}
$summary .= "## By part of the code\n\n| Folder | Files loaded | Lines run | Share |\n|---|---|---|---|\n";
foreach ($areas as $area => $areaTotals) {
	$summary .= "| `$area` | " . $areaTotals['files'] . " | " . number_format($areaTotals['run']) . " of " . number_format($areaTotals['executable']) . " | " . formulize_coverageShare($areaTotals['run'], $areaTotals['executable']) . " |\n";
}
$summary .= "\n## By set of tests\n\nThe lines each set ran, and the lines no other set ran.\n\n| Set | Lines run | Only this set |\n|---|---|---|\n";
foreach ($phases as $phase) {
	$runCount = 0;
	$onlyCount = 0;
	foreach ($linesRunByPhase[$phase] as $file => $phaseLines) {
		$runCount += count($phaseLines);
		foreach (array_keys($phaseLines) as $line) {
			$inAnother = false;
			foreach ($phases as $otherPhase) {
				if ($otherPhase !== $phase AND isset($linesRunByPhase[$otherPhase][$file][$line])) {
					$inAnother = true;
					break;
				}
			}
			$onlyCount += $inAnother ? 0 : 1;
		}
	}
	$summary .= "| `$phase` | " . number_format($runCount) . " | " . number_format($onlyCount) . " |\n";
}
$byUnrun = array();
foreach ($files as $relativePath => $info) {
	if ($info['hasExecutableLines']) {
		$byUnrun[$relativePath] = count($info['executable']) - count($info['run']);
	}
}
arsort($byUnrun);
$summary .= "\n## The files with the most lines that didn't run\n\n| File | Lines that didn't run | Share run |\n|---|---|---|\n";
foreach (array_slice($byUnrun, 0, 40, true) as $relativePath => $unrun) {
	$summary .= "| `$relativePath` | " . number_format($unrun) . " | " . formulize_coverageShare(count($files[$relativePath]['run']), count($files[$relativePath]['executable'])) . " |\n";
}
file_put_contents("$outFolder/summary.md", $summary);

// files.md
$fileList = "# Every file in the coverage report\n\n| File | Lines run | Share |\n|---|---|---|\n";
foreach ($files as $relativePath => $info) {
	$fileList .= "| `$relativePath` | " . number_format(count($info['run'])) . ($info['hasExecutableLines'] ? " of " . number_format(count($info['executable'])) . " | " . formulize_coverageShare(count($info['run']), count($info['executable'])) : " | not recorded") . " |\n";
}
if ($tracked !== null) {
	$fileList .= "\n## Never loaded\n\n";
	foreach ($neverLoaded as $relativePath) {
		$fileList .= "- `$relativePath`\n";
	}
}
file_put_contents("$outFolder/files.md", $fileList);

// changed-lines.md
if (!empty($options['diff']) AND is_readable($options['diff'])) {
	$base = !empty($options['base']) ? $options['base'] : 'the base branch';
	$changedLines = formulize_coverageChangedLines((string) file_get_contents($options['diff']));
	$changedTotals = array('executable' => 0, 'run' => 0);
	$sections = '';
	$notLoaded = array();
	foreach ($changedLines as $relativePath => $lines) {
		if (formulize_coverageIsExcluded("$webroot/$relativePath", $excluded)) {
			continue;
		}
		if (!isset($files[$relativePath])) {
			if (is_file("$webroot/$relativePath")) {
				$notLoaded[] = $relativePath . " (" . count($lines) . " changed lines)";
			}
			continue;
		}
		$info = $files[$relativePath];
		$executable = array_flip($info['executable']);
		$run = array_flip($info['run']);
		$changedExecutable = array_values(array_filter($lines, function ($line) use ($executable) { return isset($executable[$line]); }));
		$changedNotRun = array_values(array_filter($changedExecutable, function ($line) use ($run) { return !isset($run[$line]); }));
		$changedTotals['executable'] += count($changedExecutable);
		$changedTotals['run'] += count($changedExecutable) - count($changedNotRun);
		if (!$changedNotRun) {
			continue;
		}
		$sourceLines = @file("$webroot/$relativePath", FILE_IGNORE_NEW_LINES);
		$sections .= "\n## `$relativePath`\n\n" . count($changedNotRun) . " of " . count($changedExecutable) . " changed lines that can run were not run" . ($info['hasExecutableLines'] ? "" : ", counting only lines that ran somewhere in this file, since none of its requests recorded the rest") . ".\n\n";
		foreach (formulize_coverageGroups($changedNotRun) as $group) {
			$sections .= "- Line" . (count($group) > 1 ? "s " . $group[0] . "-" . end($group) : " " . $group[0]) . ":\n";
			foreach ($group as $line) {
				$text = isset($sourceLines[$line - 1]) ? trim($sourceLines[$line - 1]) : '';
				$text = mb_strlen($text) > 140 ? mb_substr($text, 0, 140) . '...' : $text;
				$sections .= "  - `" . str_replace('`', "'", $text) . "`\n";
			}
		}
	}
	$changedReport = "# Changed lines that no test ran\n\nThe lines that differ from $base, that can run, and that no test ran.\n\n";
	$changedReport .= "**" . number_format($changedTotals['run']) . " of " . number_format($changedTotals['executable']) . "** changed lines that can run were run (" . formulize_coverageShare($changedTotals['run'], $changedTotals['executable']) . ").\n";
	if ($notLoaded) {
		$changedReport .= "\nChanged files that no test loaded:\n\n- `" . implode("`\n- `", $notLoaded) . "`\n";
	}
	file_put_contents("$outFolder/changed-lines.md", $changedReport . $sections);
}

// coverage.json and lcov.info
$json = array('requests' => $requestFiles, 'phases' => $phases, 'files' => array(), 'neverLoaded' => $neverLoaded);
$lcov = '';
foreach ($files as $relativePath => $info) {
	$json['files'][$relativePath] = array(
		'executable' => formulize_coverageRangesFromLines($info['executable']),
		'run' => formulize_coverageRangesFromLines($info['run']),
		'executableRecorded' => $info['hasExecutableLines'],
		'phases' => array_map('formulize_coverageRangesFromLines', $info['phases']),
	);
	$run = array_flip($info['run']);
	$lcov .= "SF:$relativePath\n";
	foreach ($info['executable'] as $line) {
		$lcov .= "DA:$line," . (isset($run[$line]) ? 1 : 0) . "\n";
	}
	$lcov .= "LF:" . count($info['executable']) . "\nLH:" . count($info['run']) . "\nend_of_record\n";
}
file_put_contents("$outFolder/coverage.json", json_encode($json));
file_put_contents("$outFolder/lcov.info", $lcov);

echo "Coverage report written to $outFolder: " . formulize_coverageShare($totals['run'], $totals['executable']) . " of the lines that can run were run, from $requestFiles distinct requests\n";

/**
 * Get the line numbers in ranges, ie: 3-6,9 is 3, 4, 5, 6 and 9
 *
 * @param string $ranges The ranges, separated by commas
 * @return array The line numbers
 */
function formulize_coverageLinesInRanges($ranges) {
	$lines = array();
	foreach (explode(',', (string) $ranges) as $range) {
		if (strpos($range, '-') !== false) {
			list($start, $end) = explode('-', $range);
			$lines = array_merge($lines, range(intval($start), intval($end)));
		} elseif ($range !== '') {
			$lines[] = intval($range);
		}
	}
	return $lines;
}

/**
 * Get ranges from line numbers, the opposite of formulize_coverageLinesInRanges
 *
 * @param array $lines The line numbers
 * @return string The ranges, separated by commas
 */
function formulize_coverageRangesFromLines($lines) {
	return implode(',', array_map(function ($group) { return count($group) > 1 ? $group[0] . '-' . end($group) : (string) $group[0]; }, formulize_coverageGroups($lines)));
}

/**
 * Group line numbers into runs of lines next to each other
 *
 * @param array $lines The line numbers
 * @return array The runs, each an array of line numbers
 */
function formulize_coverageGroups($lines) {
	sort($lines);
	$groups = array();
	foreach ($lines as $line) {
		if ($groups AND end($groups[count($groups) - 1]) == $line - 1) {
			$groups[count($groups) - 1][] = $line;
		} else {
			$groups[] = array($line);
		}
	}
	return $groups;
}

/**
 * Check if a file is in one of the folders coverage leaves out
 *
 * @param string $file The file's full path
 * @param array $excluded The folders, from formulize_coverageExcludedPaths
 * @return bool True if it's left out
 */
function formulize_coverageIsExcluded($file, $excluded) {
	foreach ($excluded as $folder) {
		if (strpos($file, $folder) === 0) {
			return true;
		}
	}
	return false;
}

/**
 * Get the part of the code a file is in, for the totals, ie: modules/formulize/include, libraries/icms or kernel
 *
 * @param string $relativePath The file's path in the repository
 * @return string The part of the code
 */
function formulize_coverageArea($relativePath) {
	$parts = explode('/', $relativePath);
	if (count($parts) == 1) {
		return '(root)';
	}
	if ($parts[0] == 'modules' AND count($parts) > 3) {
		return implode('/', array_slice($parts, 0, 3));
	}
	if (($parts[0] == 'modules' OR $parts[0] == 'libraries') AND count($parts) > 2) {
		return implode('/', array_slice($parts, 0, 2));
	}
	return $parts[0];
}

/**
 * Put the sets of tests in the order the workflow runs them. Others, if there are any, go after them, in alphabetical order.
 *
 * @param array $phases The labels of the sets of tests that were recorded
 * @return array The labels, in order
 */
function formulize_coveragePhasesInOrder($phases) {
	$order = array('php-logic', '01-setup', '02-validate', '03-validate-sequential', '04-appearance', 'php-integrity');
	usort($phases, function ($a, $b) use ($order) {
		$aPosition = array_search($a, $order);
		$bPosition = array_search($b, $order);
		$aPosition = $aPosition === false ? count($order) : $aPosition;
		$bPosition = $bPosition === false ? count($order) : $bPosition;
		return $aPosition == $bPosition ? strcmp($a, $b) : $aPosition - $bPosition;
	});
	return $phases;
}

/**
 * Get a share as a percentage, ie: 45.2%
 *
 * @param int $part The part
 * @param int $whole The whole
 * @return string The percentage, or a dash if the whole is 0
 */
function formulize_coverageShare($part, $whole) {
	return $whole ? number_format(100 * $part / $whole, 1) . '%' : '-';
}

/**
 * Get the lines that a diff adds or changes, by file. The diff is git diff -U0, so every line it shows on the new side is one that changed.
 *
 * @param string $diff The diff
 * @return array The line numbers in each file, by the file's path in the repository
 */
function formulize_coverageChangedLines($diff) {
	$changed = array();
	$file = null;
	foreach (explode("\n", $diff) as $diffLine) {
		if (strpos($diffLine, '+++ ') === 0) {
			$file = (strpos($diffLine, '+++ b/') === 0) ? rtrim(substr($diffLine, 6), "\r") : null; // +++ /dev/null is a file that was deleted
		} elseif ($file !== null AND preg_match('/^@@ -\d+(?:,\d+)? \+(\d+)(?:,(\d+))? @@/', $diffLine, $hunk)) {
			$count = isset($hunk[2]) ? intval($hunk[2]) : 1;
			for ($line = intval($hunk[1]); $line < intval($hunk[1]) + $count; $line++) {
				$changed[$file][] = $line;
			}
		}
	}
	return $changed;
}
