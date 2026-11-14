<?php
/**
 * Standalone logic test: which of a screen's templates count as changed, and what a screen takes along when its
 * templates are copied to another theme.
 *
 * Not PHPUnit / Playwright - a single dependency-free script, the same pattern as the other tests in this
 * folder, so it runs anywhere PHP is available:  php tests/screen_template_copy_test.php
 *
 * WHY THIS EXISTS
 * A screen's own templates are in templates/screens/<theme>/<sid>/. "Customize the Templates" fills that folder
 * with copies of the defaults, so most of what's there may never have been edited. copyTemplateFilesToTheme(), the
 * Templates tab's copy button and the Lyris update wizard only take along screens with at least one template that
 * was changed, and those take every template they're drawn with, so a screen draws the same in the other theme:
 *   - a copy of a default is not a change, whatever its line endings and the space at either end
 *   - a template that differs from its default, or has no default, is a change
 *   - the theme's own defaults come first, then the system's, as when a screen is drawn
 *   - a screen with nothing changed takes nothing
 *   - a screen with a change takes its changed templates, and the defaults of its theme for the rest of its
 *     templates, its variable templates included, which its Variables tab can clear
 * The functions are lifted from modules/formulize/class/screen.php (and removeOpeningPHPTag() from include/functions.php),
 * since those files can't be included without the module bootstrap, and run against folders made for the test.
 *
 * Usage:
 *   php tests/screen_template_copy_test.php
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

/**
 * Lift a single function's source out of a PHP file using the tokenizer, so braces inside strings or
 * comments cannot be mistaken for the function's own. (Same approach as normalize_then_escape_test.php.)
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
    $screenFile => array('formulize_screenTemplateIsEmpty', 'formulize_normalizeScreenTemplate', 'formulize_screenTemplateFilesIn',
        'formulize_screenTemplateDefaults', 'formulize_screenTemplatesChanged', 'formulize_screenTemplatesToTake'),
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

// a templates/screens folder of our own, with a system default, a theme's defaults, and a screen's folder in it
$screens = sys_get_temp_dir() . '/formulize_screen_template_copy_test_' . getmypid();
function put($path, $contents) {
    if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }
    file_put_contents($path, $contents);
}
function removeFolder($folder) {
    if (!is_dir($folder)) {
        return;
    }
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($folder);
}
// the system default has every template, and a variable template the theme doesn't have
put("$screens/default/listOfEntries/toptemplate.php", "<?php\nprint 'system top';\n");
put("$screens/default/listOfEntries/listtemplate.php", "<?php\nprint 'system row';\n");
put("$screens/default/listOfEntries/variables/currentViewList.php", "<?php\nprint 'system views';\n");
put("$screens/default/listOfEntries/index.html", "<html></html>"); // not a template
// the theme has its own top and row, and an empty one that doesn't take the system's place
put("$screens/Anari/default/listOfEntries/toptemplate.php", "<?php\nprint 'anari top';\n");
put("$screens/Anari/default/listOfEntries/listtemplate.php", "<?php\nprint 'anari row';\n");
put("$screens/Anari/default/listOfEntries/bottomtemplate.php", "<?php\n");
put("$screens/default/listOfEntries/bottomtemplate.php", "<?php\nprint 'system bottom';\n");

try {
    // ---- The defaults ----
    $defaults = formulize_screenTemplateDefaults($screens, 'Anari', 'listOfEntries');
    check('the theme\'s default takes the system\'s place', isset($defaults['toptemplate.php']) && $defaults['toptemplate.php'] === "$screens/Anari/default/listOfEntries/toptemplate.php");
    check('an empty theme default leaves the system\'s in place', isset($defaults['bottomtemplate.php']) && $defaults['bottomtemplate.php'] === "$screens/default/listOfEntries/bottomtemplate.php");
    check('a variable template only the system has is a default', isset($defaults['variables/currentViewList.php']));
    check('files that aren\'t templates are not defaults', !isset($defaults['index.html']));
    check('a type that isn\'t a name has no defaults', formulize_screenTemplateDefaults($screens, 'Anari', '../listOfEntries') === array());
    check('a theme with no defaults of its own has the system\'s', formulize_screenTemplateDefaults($screens, 'Nowhere', 'listOfEntries')['toptemplate.php'] === "$screens/default/listOfEntries/toptemplate.php");

    // ---- Comparing ----
    check('line endings and space at either end are evened out', formulize_normalizeScreenTemplate("\n  <?php\r\nprint 1;\r\n\r\n") === "<?php\nprint 1;");

    // a screen seeded with copies of the defaults, through an editor that wrote Windows line endings, and trimmed
    $sid = "$screens/Anari/12";
    put("$sid/toptemplate.php", str_replace("\n", "\r\n", "<?php\nprint 'anari top';\n"));
    put("$sid/listtemplate.php", "<?php\nprint 'anari row';");
    put("$sid/variables/currentViewList.php", "\n<?php\nprint 'system views';\n\n");
    $files = formulize_screenTemplateFilesIn($sid);
    $found = array_keys($files);
    sort($found);
    check('the screen\'s own templates are found, variable templates with forward slashes', $found === array('listtemplate.php', 'toptemplate.php', 'variables/currentViewList.php'));
    check('copies of the defaults are not changes', formulize_screenTemplatesChanged($files, $defaults) === array());
    check('a screen with nothing changed takes nothing along', formulize_screenTemplatesToTake($files, $defaults) === array());

    // a copy of the system's top, when the theme has its own, is a change: the screen draws differently from the theme's
    put("$sid/toptemplate.php", "<?php\nprint 'system top';\n");
    $files = formulize_screenTemplateFilesIn($sid);
    check('a copy of the system default, where the theme has its own, is a change', array_keys(formulize_screenTemplatesChanged($files, $defaults)) === array('toptemplate.php'));

    // an edited row
    put("$sid/toptemplate.php", "<?php\nprint 'anari top';\n");
    put("$sid/listtemplate.php", "<?php\nprint 'my own row';\n");
    $files = formulize_screenTemplateFilesIn($sid);
    $changed = formulize_screenTemplatesChanged($files, $defaults);
    check('an edited template is a change', array_keys($changed) === array('listtemplate.php'));

    // ---- What a screen with a change takes along ----
    $take = formulize_screenTemplatesToTake($files, $defaults);
    check('it takes its changed template', isset($take['listtemplate.php']) && $take['listtemplate.php'] === "$sid/listtemplate.php");
    check('it takes the theme\'s default for a template it has an unchanged copy of', isset($take['toptemplate.php']) && $take['toptemplate.php'] === "$screens/Anari/default/listOfEntries/toptemplate.php");
    check('it takes the default for a template it has no copy of', isset($take['bottomtemplate.php']) && $take['bottomtemplate.php'] === "$screens/default/listOfEntries/bottomtemplate.php");
    check('it takes the default for a variable template, as it does for the main templates', isset($take['variables/currentViewList.php']) && $take['variables/currentViewList.php'] === "$screens/default/listOfEntries/variables/currentViewList.php");
    check('it takes nothing that isn\'t a template', !isset($take['index.html']));

    // a template with no default is the screen's own, and counts as a change
    put("$sid/variables/myOwnPart.php", "<?php\nprint 'mine';\n");
    $files = formulize_screenTemplateFilesIn($sid);
    check('a template with no default is a change', isset(formulize_screenTemplatesChanged($files, $defaults)['variables/myOwnPart.php']));
    check('a changed variable template is taken along', isset(formulize_screenTemplatesToTake($files, $defaults)['variables/myOwnPart.php']));
    // a screen whose only change is a variable template still takes the defaults for its main templates
    put("$sid/listtemplate.php", "<?php\nprint 'anari row';\n");
    $take = formulize_screenTemplatesToTake(formulize_screenTemplateFilesIn($sid), $defaults);
    check('a change only in a variable template still brings the main templates', isset($take['variables/myOwnPart.php']) && isset($take['listtemplate.php']) && $take['listtemplate.php'] === "$screens/Anari/default/listOfEntries/listtemplate.php");

    // an empty template is not one of the screen's templates at all
    put("$sid/closelisttemplate.php", "<?php  \n");
    check('an empty template isn\'t one of the screen\'s', !isset(formulize_screenTemplateFilesIn($sid)['closelisttemplate.php']));
} finally {
    removeFolder($screens);
}

echo "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
