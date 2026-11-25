<?php
/**
 * The looks that come with a theme, and how the files of a theme's looks are written.
 *
 * The looks that come with a theme, such as Lyris's Altan and Varetta, are stylesheets in version control. Formulize
 * writes a theme's looks again whenever any of them is saved, and each look keeps only what differs from its base. So
 * a look that came with a setting the same as its base's was rewritten without it by the first save of any other look,
 * and the site had a changed file nobody had touched: Varetta did, when the Lyris update wizard gave Default the
 * site's logo. This checks that:
 *   - each look that comes with a theme is exactly what a save writes, with Default as it comes
 *   - a file that would come out the same isn't written, and one that can't be written is reported
 *   - nothing is left beside the files
 *
 * A look with a description is one that came with a theme, or a copy of one, since people can't give a look a
 * description. On a site where those looks have been edited, the first check is of them as edited.
 *
 * Like the other integrity tests, this boots the application, so CI runs it after the Playwright suite. It writes
 * nothing of the system's: the looks are built in memory, and the files it writes are in a folder of its own.
 *
 * WHAT THIS DOES NOT COVER
 * - Saving looks, and what pages are shown with. The specs in 04-appearance do that through the application.
 *
 * Usage:
 *   docker exec formulize-web-1 php /var/www/html/tests/appearance_looks_test.php
 *
 * @package Formulize
 * @subpackage tests
 */

// Command-line / CI only. A web request just 404s - this ships in the repo and must never be a live endpoint.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';

$root = dirname(__DIR__);
if (!is_file($root . '/mainfile.php')) {
    fwrite(STDERR, "Cannot find mainfile.php at: $root/mainfile.php - is Formulize installed?\n");
    exit(2);
}
include $root . '/mainfile.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/appearance.php';

$GLOBALS['__pass'] = 0;
$GLOBALS['__fail'] = 0;

function check($label, $got, $want) {
    if ($got === $want) {
        $GLOBALS['__pass']++;
        return true;
    }
    $GLOBALS['__fail']++;
    echo "FAIL: $label\n   got:  " . var_export($got, true) . "\n   want: " . var_export($want, true) . "\n";
    return false;
}

// ---------------------------------------------------------------------------
// The looks that come with a theme are what a save writes
// ---------------------------------------------------------------------------
$shipped = 0;
foreach (formulize_getAppearanceThemes() as $theme) {
    // reading the looks of a theme that has none would give it some
    if (!glob(formulize_getAppearanceLooksDir($theme) . '/*.css') OR !is_file(formulize_getAppearanceCssPath($theme))) {
        continue;
    }
    $looks = formulize_getAppearanceLooks($theme);
    if (isset($looks['default'])) {
        $looks['default']['settings'] = array(); // Default as it comes, whatever this site's changes
    }
    $saved = $looks;
    foreach (array_keys($saved) as $key) {
        $saved[$key]['settings'] = formulize_appearanceLookDifferences($looks, $key, $theme);
    }
    foreach ($looks as $key => $look) {
        if ($look['description'] === '') {
            continue;
        }
        $shipped++;
        $file = str_replace("\r\n", "\n", (string) file_get_contents(formulize_getAppearanceLooksDir($theme) . '/' . $key . '.css'));
        $written = formulize_buildAppearanceLookCss($key, $saved, $theme);
        // the lines that differ, which say what to change in the look's file
        check("$theme's $key is what a save writes: lines only in the file", array_values(array_diff(explode("\n", $file), explode("\n", $written))), array());
        check("$theme's $key is what a save writes: lines only in the save", array_values(array_diff(explode("\n", $written), explode("\n", $file))), array());
        check("$theme's $key is what a save writes", $file === $written, true);
    }
}
check('there are looks that came with a theme to check', $shipped > 0, true);

// ---------------------------------------------------------------------------
// Writing a file
// ---------------------------------------------------------------------------
$dir = sys_get_temp_dir() . '/formulize_appearance_looks_test_' . getmypid();
mkdir($dir);
$path = $dir . '/look.css';

check('a new file is written', formulize_writeAppearanceFile($path, "one\n"), true);
check('a new file has its contents', file_get_contents($path), "one\n");

touch($path, 1000000000);
clearstatcache();
check('an unchanged file is fine', formulize_writeAppearanceFile($path, "one\n"), true);
clearstatcache();
check('an unchanged file is left alone', filemtime($path), 1000000000);

chmod($path, 0640);
clearstatcache();
$permissions = fileperms($path);
check('a changed file is written', formulize_writeAppearanceFile($path, "two\n"), true);
check('a changed file has its new contents', file_get_contents($path), "two\n");
check('a changed file has a new time', filemtime($path) !== 1000000000, true);
clearstatcache();
check('a changed file keeps its permissions', fileperms($path), $permissions);

// a folder that isn't there stands in for one that can't be written, since root, which tests run as, can write any
check('a file that cannot be written is reported', @formulize_writeAppearanceFile($dir . '/missing/look.css', "one\n"), false);
// and a folder where the file should be can't be replaced by it
mkdir($dir . '/folder.css');
check('a file that cannot be moved into place is reported', @formulize_writeAppearanceFile($dir . '/folder.css', "one\n"), false);
check('nothing is left beside the files', array_values(array_diff(scandir($dir), array('.', '..'))), array('folder.css', 'look.css'));

rmdir($dir . '/folder.css');
unlink($path);
rmdir($dir);

echo $GLOBALS['__fail'] ? "{$GLOBALS['__fail']} check(s) failed, {$GLOBALS['__pass']} passed\n" : "PASS: {$GLOBALS['__pass']} checks, of $shipped look(s) that came with a theme and of writing files\n";
exit($GLOBALS['__fail'] ? 1 : 0);
