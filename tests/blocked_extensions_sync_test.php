<?php
/**
 * Standalone logic test: the list of script extensions that must never be executable is kept in step
 * everywhere it appears.
 *
 * Not PHPUnit / Playwright - a single dependency-free script, the same pattern as the other tests in this
 * folder, so it runs anywhere PHP is available:  php tests/blocked_extensions_sync_test.php
 *
 * WHY THIS EXISTS
 * The same set of dangerous extensions is written out in four places, because they are enforced by four
 * different things that cannot share a value at runtime:
 *   1. formulizeFileUploadElementHandler::BLOCKED_EXTENSIONS - refused on upload (the canonical source)
 *   2. uploads/.htaccess - denied by Apache when the file is served
 *   3. include/uploads_execution_probe.php - the fallback copy printed if uploads/.htaccess goes missing
 *   4. docs/uploads_not_executable.md - the copy a site owner is told to recreate the file from
 * If these drift apart, a type refused on upload might still be served and run (or vice versa), which is
 * exactly the hole the list is meant to close. This test fails if any of 2-4 stops matching 1, so they
 * can only be changed together.
 *
 * The extension deny list is a backstop, not the main defence (the per-element allowed-types list and the
 * server not executing the uploads folder at all are what really matter), but a backstop that has quietly
 * drifted is worse than none, because it looks like protection.
 *
 * Usage:
 *   php tests/blocked_extensions_sync_test.php
 *
 * @package Formulize
 * @subpackage tests
 */

// Command-line / CI only. A web request just 404s - this ships in the repo and must never be a live endpoint.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = __DIR__ . '/..';
$sources = array(
    'BLOCKED_EXTENSIONS'          => $root . '/modules/formulize/class/fileUploadElement.php',
    'uploads/.htaccess'           => $root . '/uploads/.htaccess',
    'uploads_execution_probe.php' => $root . '/modules/formulize/include/uploads_execution_probe.php',
    'uploads_not_executable.md'   => $root . '/docs/uploads_not_executable.md',
);
foreach ($sources as $label => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Cannot find $label at: $path\n");
        exit(2);
    }
}

/**
 * The extensions named in the single-quoted entries of the BLOCKED_EXTENSIONS constant. Read from the source
 * text rather than loaded, because the class cannot be instantiated without the whole module bootstrap.
 */
function blockedExtensionsFromConstant($file) {
    $src = file_get_contents($file);
    if (!preg_match('/const\s+BLOCKED_EXTENSIONS\s*=\s*array\s*\((.*?)\)\s*;/s', $src, $m)) {
        fwrite(STDERR, "Could not find the BLOCKED_EXTENSIONS constant in $file\n");
        exit(2);
    }
    // the array body holds only single-quoted extension strings; the // comments contain no single quotes
    preg_match_all("/'([A-Za-z0-9]+)'/", $m[1], $tokens);
    return $tokens[1];
}

/**
 * The extensions named in the FilesMatch alternation of a .htaccess, ie: the (php|php3|...) group after \.
 * Works on the shipped file, the fallback copy in the probe, and the fenced copy in the docs page.
 */
function blockedExtensionsFromHtaccess($file) {
    $src = file_get_contents($file);
    if (!preg_match('/<FilesMatch\s+"[^"]*?\\\\\.\(([A-Za-z0-9|]+)\)/i', $src, $m)) {
        fwrite(STDERR, "Could not find a FilesMatch extension list in $file\n");
        exit(2);
    }
    return explode('|', $m[1]);
}

/** Normalise a list to a comparable set: lowercase, unique, sorted. */
function asSet($list) {
    $set = array_values(array_unique(array_map('strtolower', $list)));
    sort($set);
    return $set;
}

$canonical = asSet(blockedExtensionsFromConstant($sources['BLOCKED_EXTENSIONS']));
if (count($canonical) < 10) {
    fwrite(STDERR, "Only " . count($canonical) . " extensions read from BLOCKED_EXTENSIONS; something is wrong with the parsing.\n");
    exit(2);
}

$copies = array(
    'uploads/.htaccess'           => asSet(blockedExtensionsFromHtaccess($sources['uploads/.htaccess'])),
    'uploads_execution_probe.php' => asSet(blockedExtensionsFromHtaccess($sources['uploads_execution_probe.php'])),
    'uploads_not_executable.md'   => asSet(blockedExtensionsFromHtaccess($sources['uploads_not_executable.md'])),
);

$failed = false;
foreach ($copies as $label => $set) {
    $missing = array_diff($canonical, $set); // in the constant but not denied here
    $extra   = array_diff($set, $canonical); // denied here but not in the constant
    if ($missing || $extra) {
        $failed = true;
        fwrite(STDERR, "MISMATCH: $label does not match BLOCKED_EXTENSIONS\n");
        if ($missing) {
            fwrite(STDERR, "  not denied there, but refused on upload: " . implode(', ', $missing) . "\n");
        }
        if ($extra) {
            fwrite(STDERR, "  denied there, but not in BLOCKED_EXTENSIONS: " . implode(', ', $extra) . "\n");
        }
    } else {
        fwrite(STDOUT, "OK: $label matches BLOCKED_EXTENSIONS (" . count($canonical) . " extensions)\n");
    }
}

if ($failed) {
    fwrite(STDERR, "\nThe blocked-extension lists have drifted. Update every copy to match "
        . "formulizeFileUploadElementHandler::BLOCKED_EXTENSIONS.\n");
    exit(1);
}

fwrite(STDOUT, "\nAll blocked-extension lists are in step.\n");
exit(0);
