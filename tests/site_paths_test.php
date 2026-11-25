<?php
/**
 * Standalone logic test: the folders a site writes to are named in one place, include/sitepaths.php, and the code
 * uses those constants rather than building the paths from XOOPS_ROOT_PATH.
 *
 * Not PHPUnit / Playwright - a single dependency-free script, the same pattern as the other tests in this
 * folder, so it runs anywhere PHP is available:  php tests/site_paths_test.php
 *
 * WHY THIS EXISTS
 * A site's own files (custom code, screen templates, uploads, caches, logs, locks...) are going to live in one
 * folder of their own, which can be outside the code, such as a volume mounted into a container. That only works
 * if every place that reads or writes one of those folders asks include/sitepaths.php where it is. A single
 * XOOPS_ROOT_PATH."/modules/formulize/code/" left behind would quietly read and write inside the code instead.
 * This test fails when one comes back. See ai-context/site-directory.md.
 *
 * It checks two things:
 *   1. include/sitepaths.php defines every constant, with no trailing slash, and keeps a value set before it.
 *   2. No PHP file builds one of those folders from XOOPS_ROOT_PATH, ICMS_ROOT_PATH, XOOPS_URL or ICMS_URL, or from
 *      __DIR__ with a relative path, apart from the exceptions listed below with their reasons.
 *
 * Usage:
 *   php tests/site_paths_test.php
 *
 * @package Formulize
 * @subpackage tests
 */

// Command-line / CI only. A web request just 404s - this ships in the repo and must never be a live endpoint.
if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit;
}

$root = realpath(__DIR__ . '/..');
$failures = array();

// 1. include/sitepaths.php ---------------------------------------------------------------------------------------

$expectedConstants = array(
	'ICMS_UPLOAD_PATH', 'ICMS_UPLOAD_URL', 'ICMS_CACHE_PATH', 'ICMS_COMPILE_PATH', 'ICMS_TOKEN_PATH', 'ICMS_PURIFIER_CACHE',
	'FORMULIZE_CODE_PATH', 'FORMULIZE_SCREEN_TEMPLATES_PATH', 'FORMULIZE_SCREEN_DEFAULTS_PATH', 'FORMULIZE_CONFIG_PATH',
	'FORMULIZE_LOG_PATH', 'FORMULIZE_QUEUE_PATH', 'FORMULIZE_NOTIFICATION_QUEUE_PATH', 'FORMULIZE_SITE_LANGUAGE_PATH',
	'FORMULIZE_CACHE_PATH', 'FORMULIZE_TEMP_PATH', 'FORMULIZE_EXPORT_PATH', 'FORMULIZE_DOWNLOADS_PATH', 'FORMULIZE_DOWNLOADS_URL',
);

// Run sitepaths.php in a separate process, so the constants it defines don't collide with this script's,
// and so a value set before it can be checked.
function sitePathsDefinedBy($root, $preamble, $trustPath = '/srv/trust') {
	$script = '<?php ' . $preamble . '
		define("XOOPS_ROOT_PATH", "/srv/formulize");
		define("XOOPS_TRUST_PATH", ' . var_export($trustPath, true) . ');
		define("XOOPS_URL", "https://example.com");
		include ' . var_export($root . '/include/sitepaths.php', true) . ';
		$c = get_defined_constants(true);
		echo json_encode($c["user"]);';
	$file = tempnam(sys_get_temp_dir(), 'sitepaths');
	file_put_contents($file, $script);
	$output = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file));
	unlink($file);
	return json_decode((string) $output, true);
}

$defined = sitePathsDefinedBy($root, '');
if (!is_array($defined)) {
	$failures[] = 'include/sitepaths.php could not be run on its own';
	$defined = array();
}
foreach ($expectedConstants as $name) {
	if (!isset($defined[$name])) {
		$failures[] = "include/sitepaths.php does not define $name";
	} elseif (substr($defined[$name], -1) == '/') {
		$failures[] = "$name ends with a slash: " . $defined[$name];
	}
}
// The defaults are the folders inside the code tree that Formulize has always used.
$legacyDefaults = array(
	'FORMULIZE_CODE_PATH' => '/srv/formulize/modules/formulize/code',
	'ICMS_UPLOAD_PATH' => '/srv/formulize/uploads',
	'ICMS_UPLOAD_URL' => 'https://example.com/uploads',
	'FORMULIZE_SCREEN_TEMPLATES_PATH' => '/srv/formulize/modules/formulize/templates/screens',
	'ICMS_PURIFIER_CACHE' => '/srv/trust/cache/htmlpurifier',
);
foreach ($legacyDefaults as $name => $value) {
	if (isset($defined[$name]) AND $defined[$name] !== $value) {
		$failures[] = "$name defaults to " . $defined[$name] . ", expected $value";
	}
}
// A value set first is kept.
$overridden = sitePathsDefinedBy($root, 'define("FORMULIZE_CODE_PATH", "/elsewhere/code");');
if (!isset($overridden['FORMULIZE_CODE_PATH']) OR $overridden['FORMULIZE_CODE_PATH'] !== '/elsewhere/code') {
	$failures[] = 'include/sitepaths.php replaced a FORMULIZE_CODE_PATH that was set before it';
}

// A site with a folder of its own keeps everything in it: settings and what its people wrote in private/, which is
// the trust path, served files in uploads/, and generated files in var/
$siteDefined = sitePathsDefinedBy($root, 'define("FORMULIZE_SITE_PATH", "/srv/formulize/sites/default");', '/srv/formulize/sites/default/private');
$siteLayout = array(
	'FORMULIZE_CODE_PATH' => '/srv/formulize/sites/default/private/code',
	'FORMULIZE_SCREEN_TEMPLATES_PATH' => '/srv/formulize/sites/default/private/screens',
	'FORMULIZE_SCREEN_DEFAULTS_PATH' => '/srv/formulize/modules/formulize/templates/screens',
	'FORMULIZE_SITE_LANGUAGE_PATH' => '/srv/formulize/sites/default/private/language',
	'ICMS_UPLOAD_PATH' => '/srv/formulize/sites/default/uploads',
	'ICMS_UPLOAD_URL' => 'https://example.com/sites/default/uploads',
	'FORMULIZE_DOWNLOADS_URL' => 'https://example.com/sites/default/uploads/downloads',
	'ICMS_COMPILE_PATH' => '/srv/formulize/sites/default/var/templates_c',
	'FORMULIZE_TEMP_PATH' => '/srv/formulize/sites/default/var/temp',
	'ICMS_PURIFIER_CACHE' => '/srv/formulize/sites/default/var/htmlpurifier',
);
foreach ($siteLayout as $name => $value) {
	if (!isset($siteDefined[$name]) OR $siteDefined[$name] !== $value) {
		$failures[] = "With a site folder, $name is " . (isset($siteDefined[$name]) ? $siteDefined[$name] : 'not defined') . ", expected $value";
	}
}
// A site folder outside the web root, such as a container's volume, has its uploads served at /uploads, by an alias
$outsideDefined = sitePathsDefinedBy($root, 'define("FORMULIZE_SITE_PATH", "/site");', '/site/private');
if (!isset($outsideDefined['ICMS_UPLOAD_URL']) OR $outsideDefined['ICMS_UPLOAD_URL'] !== 'https://example.com/uploads') {
	$failures[] = 'With a site folder outside the web root, ICMS_UPLOAD_URL is ' . (isset($outsideDefined['ICMS_UPLOAD_URL']) ? $outsideDefined['ICMS_UPLOAD_URL'] : 'not defined') . ', expected https://example.com/uploads';
}
// FORMULIZE_VAR_PATH moves the generated files on their own
$varDefined = sitePathsDefinedBy($root, 'define("FORMULIZE_SITE_PATH", "/site"); define("FORMULIZE_VAR_PATH", "/fast/var");', '/site/private');
if (!isset($varDefined['ICMS_CACHE_PATH']) OR $varDefined['ICMS_CACHE_PATH'] !== '/fast/var/cache' OR $varDefined['FORMULIZE_CODE_PATH'] !== '/site/private/code') {
	$failures[] = 'FORMULIZE_VAR_PATH did not move only the generated files';
}

// 2. No site folder built from the root ---------------------------------------------------------------------------

// Folders that aren't Formulize's own code: other people's libraries, a site's files, and generated files.
$skipFolders = array(
	'.git', 'node_modules', 'vendor', 'tests', 'docs', 'install', 'cache', 'templates_c', 'uploads', 'trust', 'tokens', 'logs', 'sites',
	'libraries/googleapiclient', 'libraries/phpopenid', 'libraries/php-saml', 'libraries/htmlpurifier', 'libraries/tcpdf',
	'libraries/smarty/libs', 'libraries/phpmailer', 'libraries/jquery', 'libraries/paginationstyles', 'libraries/geshi',
	'modules/formulize/code', 'modules/formulize/cache', 'modules/formulize/temp', 'modules/formulize/queue',
	'modules/formulize/export', 'modules/formulize/templates/screens',
);

// file => why it may build a site folder from the root
$allowed = array(
	'include/sitepaths.php' => 'it is where the folders are named',
	'modules/formulize/templates/css/build-drupal-css.php' => 'runs without the bootstrap for the Drupal integration, and rewrites url()s relative to its output folder',
);

$patterns = array(
	'a site folder built from the root path' => '~(XOOPS|ICMS)_ROOT_PATH\s*\.\s*[\'"]/?(modules/formulize/(code|cache|temp|queue|export|config|templates/screens)|uploads|cache|templates_c|tokens|logs)(?=[/\'"])~',
	'a site URL built from the root URL' => '~(XOOPS|ICMS)_URL\s*\.\s*[\'"]/(uploads|cache)(?=[/\'"])~',
	'a site folder found from __DIR__' => '~(__DIR__|dirname\(__FILE__\))\s*\.\s*[\'"]/\.\./(temp|cache|code|queue|export)(?=[/\'"])~',
	'a site folder found from the working folder' => '~[\'"](\.\./)+(cache|uploads|templates_c)/~',
);

$skipRegex = '~^(' . implode('|', array_map(function ($f) { return preg_quote($f, '~'); }, $skipFolders)) . ')(/|$)~';
$iterator = new RecursiveIteratorIterator(
	new RecursiveCallbackFilterIterator(
		new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
		function ($file) use ($root, $skipRegex) {
			$relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
			return !preg_match($skipRegex, $relative);
		}
	)
);
$checked = 0;
foreach ($iterator as $file) {
	if ($file->getExtension() != 'php') {
		continue;
	}
	$relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
	if (isset($allowed[$relative])) {
		continue;
	}
	$checked++;
	$lines = file($file->getPathname());
	foreach ($lines as $number => $line) {
		$trimmed = ltrim($line);
		if (substr($trimmed, 0, 2) == '//' OR substr($trimmed, 0, 1) == '*' OR substr($trimmed, 0, 1) == '#') {
			continue; // comments can name folders
		}
		foreach ($patterns as $what => $pattern) {
			if (preg_match($pattern, $line)) {
				$failures[] = "$relative:" . ($number + 1) . " has $what. Use the constant from include/sitepaths.php: " . trim($line);
			}
		}
	}
}
if ($checked < 100) {
	$failures[] = "Only $checked PHP files were checked, so the search must have gone wrong";
}

// Report ----------------------------------------------------------------------------------------------------------

if ($failures) {
	echo "FAIL\n";
	foreach ($failures as $failure) {
		echo "  - $failure\n";
	}
	exit(1);
}
echo "PASS: include/sitepaths.php defines " . count($expectedConstants) . " folders, and none of $checked PHP files builds one from the root\n";
exit(0);
