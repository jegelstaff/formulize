<?php
/**
 * Standalone logic test: what the module update clears out of the screens' own templates, and what deleting a screen
 * removes.
 *
 * Not PHPUnit / Playwright - a single dependency-free script, the same pattern as the other tests in this
 * folder, so it runs anywhere PHP is available:  php tests/screen_template_cleanup_test.php
 *
 * WHY THIS EXISTS
 * A screen's own templates are in templates/screens/<theme>/<sid>/, with its variable templates in variables/ there.
 * formulize_remove_empty_screen_templates(), which runs on every module update (admin/patches/002_always_run.php),
 * clears out what isn't customized, and a mistake in it deletes someone's work, so:
 *   - the folders of screens that don't exist go, variables/ and all
 *   - when the screens couldn't be read, no screen's folder is taken to be a deleted screen's
 *   - copies of the theme's defaults go, whatever their line endings, main templates and variable templates
 *   - only the default/ folder beside the screen's folder counts, not the system's: a copy of the system's default
 *     is kept
 *   - an edited template stays, and its folder with it
 *   - a template screen's files are left alone
 *   - empty templates go, in variables/ too, and a screen's folders go once nothing is left in them
 * And deleting a screen (formulize_deleteScreenTemplateFolders) removes everything in its folders, without following
 * a link out of them.
 * The functions are lifted from modules/formulize/class/screen.php, the patch, and removeOpeningPHPTag() from
 * include/functions.php, since those files can't be included without the module bootstrap, and run against folders
 * made for the test, with XOOPS_ROOT_PATH set to a temporary folder.
 *
 * Usage:
 *   php tests/screen_template_cleanup_test.php
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
$screenFile = $root . '/modules/formulize/class/screen.php';
$functionsFile = $root . '/modules/formulize/include/functions.php';
$patchFile = $root . '/modules/formulize/admin/patches/002_always_run.php';

/**
 * Lift a single function's source out of a PHP file using the tokenizer, so braces inside strings or
 * comments cannot be mistaken for the function's own. (Same approach as screen_template_copy_test.php.)
 */
function extractFunctionSource($source, $functionName) {
    $tokens = token_get_all($source);
    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
            continue;
        }
        $j = $i + 1;
        while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }
        if (!is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING || $tokens[$j][1] !== $functionName) {
            continue;
        }
        $collected = '';
        $depth = 0;
        $seenBody = false;
        for ($k = $i; $k < $count; $k++) {
            $text = is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
            $collected .= $text;
            $isArrayToken = is_array($tokens[$k]);
            if ($text === '{'
                || ($isArrayToken && ($tokens[$k][0] === T_CURLY_OPEN || $tokens[$k][0] === T_DOLLAR_OPEN_CURLY_BRACES))) {
                $depth++;
                $seenBody = true;
            } elseif ($text === '}') {
                $depth--;
                if ($seenBody && $depth === 0) {
                    return $collected;
                }
            }
        }
    }
    return false;
}

$sources = array(
    $functionsFile => array('removeOpeningPHPTag'),
    $screenFile => array('formulize_screenTemplateIsEmpty', 'formulize_normalizeScreenTemplate', 'formulize_screenTemplateMatchesDefault',
        'formulize_removeScreenTemplateFolderIfEmpty', 'formulize_removeScreenTemplateFoldersIfEmpty', 'formulize_deleteScreenTemplateFolders',
        'formulize_removeScreenTemplatePath'),
    $patchFile => array('formulize_remove_empty_screen_templates'),
);
foreach ($sources as $file => $functionNames) {
    if (!is_file($file)) {
        fwrite(STDERR, "Cannot find $file\n");
        exit(2);
    }
    $source = file_get_contents($file);
    foreach ($functionNames as $functionName) {
        $extracted = extractFunctionSource($source, $functionName);
        if ($extracted === false) {
            fwrite(STDERR, "Could not extract $functionName() from $file\n");
            exit(2);
        }
        eval($extracted);
    }
}

$failures = 0;
$passes = 0;
function check($label, $condition) {
    global $failures, $passes;
    if ($condition) {
        $passes++;
    } else {
        $failures++;
        echo "FAIL: $label\n";
    }
}

function put($path, $contents) {
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }
    file_put_contents($path, $contents);
}
function removeFolder($folder) {
    if (!is_dir($folder) || is_link($folder)) {
        return;
    }
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
        if ($file->isLink() || !$file->isDir()) {
            unlink($file->getPathname());
        } else {
            rmdir($file->getPathname());
        }
    }
    rmdir($folder);
}
// the defaults: Lyris has its own, with variable templates; Anari has its own main templates and no variable templates;
// the system's have a variable template
function putDefaults($screens) {
    put("$screens/default/listOfEntries/toptemplate.php", "<?php\nprint 'system top';\n");
    put("$screens/default/listOfEntries/listtemplate.php", "<?php\nprint 'system row';\n");
    put("$screens/default/listOfEntries/variables/currentViewList.php", "<?php\nprint 'system views';\n");
    put("$screens/Lyris/default/listOfEntries/toptemplate.php", "<?php\nprint 'lyris top';\n");
    put("$screens/Lyris/default/listOfEntries/listtemplate.php", "<?php\nprint 'lyris row';\n");
    put("$screens/Lyris/default/listOfEntries/variables/currentViewList.php", "<?php\nprint 'lyris views';\n");
    put("$screens/Lyris/default/listOfEntries/variables/pageNavControls.php", "<?php\nprint 'lyris pages';\n");
    put("$screens/Anari/default/listOfEntries/toptemplate.php", "<?php\nprint 'anari top';\n");
    put("$screens/Anari/default/listOfEntries/listtemplate.php", "<?php\nprint 'anari row';\n");
}
function runCleanup($types) {
    ob_start();
    formulize_remove_empty_screen_templates($types);
    return ob_get_clean();
}

$base = sys_get_temp_dir() . '/formulize_screen_template_cleanup_test_' . getmypid();
define('XOOPS_ROOT_PATH', $base); // the functions find templates/screens under it, as on a site
$screens = "$base/modules/formulize/templates/screens";

try {
    putDefaults($screens);

    // ---- Comparing with the defaults ----
    put("$screens/Lyris/20/toptemplate.php", str_replace("\n", "\r\n", "  <?php\nprint 'lyris top';\n\n"));
    put("$screens/Lyris/20/variables/pageNavControls.php", "<?php\nprint 'lyris pages';");
    put("$screens/Lyris/20/listtemplate.php", "<?php\nprint 'my row';\n");
    put("$screens/Lyris/20/bottomtemplate.php", "<?php\nprint 'no default beside it';\n");
    put("$screens/Lyris/20/variables/moreActionsButton.php", "<?php\n");
    check('a copy with Windows line endings and space around it matches', formulize_screenTemplateMatchesDefault("$screens/Lyris/20/toptemplate.php", 'listOfEntries'));
    check('a copy of a default variable template matches', formulize_screenTemplateMatchesDefault("$screens/Lyris/20/variables/pageNavControls.php", 'listOfEntries'));
    check('an edited template doesn\'t match', !formulize_screenTemplateMatchesDefault("$screens/Lyris/20/listtemplate.php", 'listOfEntries'));
    check('a template with no default beside it doesn\'t match', !formulize_screenTemplateMatchesDefault("$screens/Lyris/20/bottomtemplate.php", 'listOfEntries'));
    check('an empty template doesn\'t match', !formulize_screenTemplateMatchesDefault("$screens/Lyris/20/variables/moreActionsButton.php", 'listOfEntries'));
    check('a template screen has no defaults', !formulize_screenTemplateMatchesDefault("$screens/Lyris/20/toptemplate.php", 'template'));
    check('a type that isn\'t a name has no defaults', !formulize_screenTemplateMatchesDefault("$screens/Lyris/20/toptemplate.php", '../listOfEntries'));
    removeFolder("$screens/Lyris/20");

    // ---- Deleting a screen ----
    put("$screens/Lyris/30/toptemplate.php", "<?php\nprint 'mine';\n");
    put("$screens/Lyris/30/variables/pageNavControls.php", "<?php\nprint 'mine';\n");
    put("$screens/Lyris/30/notes.txt", "kept beside the templates");
    put("$screens/Anari/30/code.php", "<?php\nprint 'mine';\n");
    put("$base/outside/keep.php", "<?php\nprint 'not the screen\\'s';\n");
    $linked = @symlink("$base/outside", "$screens/Lyris/30/linked");
    check('nothing is done for a sid that isn\'t a positive whole number', formulize_deleteScreenTemplateFolders('0') === array()
        && formulize_deleteScreenTemplateFolders('-30') === array() && formulize_deleteScreenTemplateFolders('30abc') === array()
        && formulize_deleteScreenTemplateFolders('*') === array() && is_dir("$screens/Lyris/30"));
    $removed = formulize_deleteScreenTemplateFolders(30);
    sort($removed);
    check('deleting a screen removes its folder in every theme', $removed === array("$screens/Anari/30", "$screens/Lyris/30") && !file_exists("$screens/Lyris/30") && !file_exists("$screens/Anari/30"));
    if ($linked) {
        check('a link in the screen\'s folder is removed, not followed', is_file("$base/outside/keep.php"));
    } else {
        echo "(skipped the link check: links can't be made here)\n";
    }
    check('deleting a screen leaves the defaults alone', is_file("$screens/Lyris/default/listOfEntries/toptemplate.php"));

    // ---- The update ----
    // 40: a deleted screen, with variable templates, in two themes
    put("$screens/Lyris/40/toptemplate.php", "<?php\nprint 'mine';\n");
    put("$screens/Lyris/40/variables/pageNavControls.php", "<?php\nprint 'mine';\n");
    put("$screens/Anari/40/listtemplate.php", "<?php\nprint 'mine';\n");
    // 41: seeded in Lyris and never edited, some through an editor with Windows line endings
    put("$screens/Lyris/41/toptemplate.php", str_replace("\n", "\r\n", "<?php\nprint 'lyris top';\n"));
    put("$screens/Lyris/41/listtemplate.php", "<?php\nprint 'lyris row';\n");
    put("$screens/Lyris/41/variables/currentViewList.php", str_replace("\n", "\r\n", "<?php\nprint 'lyris views';\n"));
    put("$screens/Lyris/41/variables/pageNavControls.php", "<?php\nprint 'lyris pages';\n");
    // 42: in Anari, a variable template that matches only the system's default, and a main template that matches
    // only the system's default where Anari has its own
    put("$screens/Anari/42/variables/currentViewList.php", "<?php\nprint 'system views';\n");
    put("$screens/Anari/42/toptemplate.php", "<?php\nprint 'system top';\n");
    put("$screens/Anari/42/listtemplate.php", "<?php\nprint 'anari row';\n");
    // 43: one edited template among copies, and an empty variable template, and one that holds only the opening tag
    put("$screens/Lyris/43/toptemplate.php", "<?php\nprint 'lyris top';\n");
    put("$screens/Lyris/43/listtemplate.php", "<?php\nprint 'my own row';\n");
    put("$screens/Lyris/43/variables/currentViewList.php", "<?php\nprint 'lyris views';\n");
    put("$screens/Lyris/43/variables/pageNavControls.php", "");
    put("$screens/Lyris/43/variables/moreActionsButton.php", "<?php\n");
    // 44: a template screen, with a file that's the same as a list's default
    put("$screens/Lyris/44/code.php", "<?php\nprint 'code';\n");
    put("$screens/Lyris/44/template.html", "<p>template</p>");
    put("$screens/Lyris/44/toptemplate.php", "<?php\nprint 'lyris top';\n");
    // 45: only empty templates left
    put("$screens/Lyris/45/toptemplate.php", "  \n");
    put("$screens/Lyris/45/variables/pageNavControls.php", "");

    $types = array(41 => 'listOfEntries', 42 => 'listOfEntries', 43 => 'listOfEntries', 44 => 'template', 45 => 'listOfEntries');

    // a failed screen query first: no folder is taken to be a deleted screen's, and nothing is compared with defaults
    $report = runCleanup(false);
    check('when the screens can\'t be read, a screen\'s folder isn\'t deleted', is_file("$screens/Lyris/40/toptemplate.php") && is_file("$screens/Lyris/40/variables/pageNavControls.php") && is_file("$screens/Anari/40/listtemplate.php"));
    check('when the screens can\'t be read, copies of the defaults are kept', is_file("$screens/Lyris/41/toptemplate.php") && is_file("$screens/Lyris/41/variables/pageNavControls.php"));
    check('when the screens can\'t be read, the update says so', strpos($report, 'could not be read') !== false);

    $report = runCleanup($types);
    check('a deleted screen\'s folders go, variables/ and all', !file_exists("$screens/Lyris/40") && !file_exists("$screens/Anari/40"));
    check('the deleted screens\' folders are listed', strpos($report, 'modules/formulize/templates/screens/Lyris/40') !== false && strpos($report, 'modules/formulize/templates/screens/Anari/40') !== false);
    check('a folder of copies of the defaults goes', !file_exists("$screens/Lyris/41"));
    check('a variable template that matches only the system\'s default is kept', is_file("$screens/Anari/42/variables/currentViewList.php"));
    check('a main template that matches only the system\'s default is kept', is_file("$screens/Anari/42/toptemplate.php"));
    check('a copy of the theme\'s default beside it goes', !file_exists("$screens/Anari/42/listtemplate.php"));
    check('an edited template stays', is_file("$screens/Lyris/43/listtemplate.php"));
    check('the copies beside an edited template go', !file_exists("$screens/Lyris/43/toptemplate.php") && !file_exists("$screens/Lyris/43/variables/currentViewList.php"));
    check('an empty variable template goes', !file_exists("$screens/Lyris/43/variables/pageNavControls.php"));
    check('a variable template holding only the opening tag is marked blank on purpose', is_file("$screens/Lyris/43/variables/moreActionsButton.php")
        && strpos(file_get_contents("$screens/Lyris/43/variables/moreActionsButton.php"), 'Blank on purpose') !== false);
    check('a template screen\'s files are left alone', is_file("$screens/Lyris/44/code.php") && is_file("$screens/Lyris/44/template.html") && is_file("$screens/Lyris/44/toptemplate.php"));
    check('a folder of empty templates goes, variables/ first', !file_exists("$screens/Lyris/45"));
    check('the defaults are left alone', is_file("$screens/Lyris/default/listOfEntries/toptemplate.php") && is_file("$screens/Lyris/default/listOfEntries/variables/pageNavControls.php") && is_file("$screens/default/listOfEntries/variables/currentViewList.php"));
    check('each step reports its result', strpos($report, 'result: OK') !== false && strpos($report, 'copies of their theme') !== false);

    // running it again changes nothing more
    $report = runCleanup($types);
    check('a second run finds nothing more to do', $report === '' && is_file("$screens/Lyris/43/listtemplate.php") && is_file("$screens/Lyris/43/variables/moreActionsButton.php"));

    // a site with no screens at all is a query that worked, so every screen's folder goes
    runCleanup(array());
    check('a site with no screens has no screen folders left', !file_exists("$screens/Lyris/43") && !file_exists("$screens/Lyris/44") && !file_exists("$screens/Anari/42"));
    check('a site with no screens keeps the defaults', is_file("$screens/Lyris/default/listOfEntries/toptemplate.php"));

    // ---- A screen's folders go once both sets are cleared ----
    put("$screens/Lyris/50/variables/pageNavControls.php", "<?php\nprint 'mine';\n");
    formulize_removeScreenTemplateFoldersIfEmpty("$screens/Lyris/50/");
    check('a folder with variable templates in it stays', is_dir("$screens/Lyris/50/variables"));
    unlink("$screens/Lyris/50/variables/pageNavControls.php");
    put("$screens/Lyris/50/toptemplate.php", "<?php\nprint 'mine';\n");
    formulize_removeScreenTemplateFoldersIfEmpty("$screens/Lyris/50/");
    check('an empty variables/ goes while a main template stays', !file_exists("$screens/Lyris/50/variables") && is_file("$screens/Lyris/50/toptemplate.php"));
    unlink("$screens/Lyris/50/toptemplate.php");
    formulize_removeScreenTemplateFoldersIfEmpty("$screens/Lyris/50");
    check('the screen\'s folder goes once both are cleared', !file_exists("$screens/Lyris/50"));
} finally {
    removeFolder($base);
}

echo "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
