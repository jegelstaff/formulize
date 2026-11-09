<?php
/**
 * The folders that coverage leaves out, for prepend.php and merge.php
 *
 * The tests themselves, files Formulize writes while it runs, and libraries that aren't Formulize's own. The ICMS core in
 * libraries/icms is Formulize's own, so it stays in.
 *
 * @package Formulize
 * @subpackage Tests
 */

/**
 * Get the folders that coverage leaves out
 *
 * @param string $webroot The folder Formulize is in, with no slash at the end
 * @return array The folders, each with a slash at the end, so they match as the start of a path
 */
function formulize_coverageExcludedPaths($webroot) {
	$excluded = array(
		"$webroot/tests/",
		"$webroot/templates_c/",
		"$webroot/cache/",
		"$webroot/uploads/",
		"$webroot/modules/formulize/cache/",
		"$webroot/modules/formulize/temp/",
		"$webroot/modules/formulize/code/",
	);
	foreach ((array) @scandir("$webroot/libraries") as $library) {
		if ($library !== '.' AND $library !== '..' AND $library !== 'icms' AND is_dir("$webroot/libraries/$library")) {
			$excluded[] = "$webroot/libraries/$library/";
		}
	}
	return $excluded;
}
