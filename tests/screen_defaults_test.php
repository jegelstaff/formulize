<?php
/**
 * Standalone logic test: the settings new screens start with are complete, and the same in every theme's file.
 *
 * Not PHPUnit / Playwright - a single dependency-free script, the same pattern as the other tests in this
 * folder, so it runs anywhere PHP is available:  php tests/screen_defaults_test.php
 *
 * WHY THIS EXISTS
 * The settings with fixed values that a new screen starts with are in JSON files, not in code:
 *   modules/formulize/include/screen_defaults.json - Formulize's standard values, used for anything a theme's
 *     file leaves out, and for themes with no file
 *   themes/<theme>/screen-defaults.json - each theme's own values
 * formulizeScreenHandler::getThemeScreenDefaults() reads them, and the 8.30 update wizard compares existing screens
 * with what it returns. Each theme's file is meant to show the whole picture, so it lists every setting the standard
 * file does, even where its value is the same. This test fails when:
 *   - a file isn't valid JSON
 *   - the standard file names a setting the screen type doesn't have, or one a theme can't set
 *   - a theme's file leaves out a setting or a screen type the standard file has
 *   - a theme's file names a setting the screen type doesn't have, or one a theme can't set
 *   - a value is a list where the setting takes a single value, or the other way round
 * Lyris and Anari must each have a file. Any other theme with one is checked too.
 *
 * The settings a screen type has are read from the initVar() calls in its class and in formulizeScreen, and the
 * ones a theme can't set from formulizeScreenHandler::THEME_DEFAULTS_EXCLUDED, so the test follows the code.
 *
 * Usage:
 *   php tests/screen_defaults_test.php
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
$classDir = $root . '/modules/formulize/class';
$coreFile = $root . '/modules/formulize/include/screen_defaults.json';
$requiredThemes = array('Lyris', 'Anari');

// screen type => the file and class that declare its settings. Not legacy form screens: no new ones are made
$screenClasses = array(
    'listOfEntries' => array('listOfEntriesScreen.php', 'formulizeListOfEntriesScreen'),
    'multiPage'     => array('multiPageScreen.php', 'formulizeMultiPageScreen'),
    'template'      => array('templateScreen.php', 'formulizeTemplateScreen'),
    'calendar'      => array('calendarScreen.php', 'formulizeCalendarScreen'),
    'map'           => array('mapScreen.php', 'formulizeMapScreen'),
);

$failures = array();

function fail($message) {
    global $failures;
    $failures[] = $message;
}

/**
 * The settings a class declares, as name => true when it's a list (XOBJ_DTYPE_ARRAY), from the initVar() calls in the
 * class's own body, so another class in the same file isn't counted.
 */
function declaredSettings($file, $className) {
    $source = file_get_contents($file);
    if ($source === false) {
        fail("Cannot read $file");
        return array();
    }
    if (!preg_match('/^class\s+' . preg_quote($className, '/') . '\b.*?^\}/ms', $source, $match)) {
        fail("Cannot find class $className in $file");
        return array();
    }
    preg_match_all('/initVar\(\s*[\'"]([A-Za-z0-9_]+)[\'"]\s*,\s*(XOBJ_DTYPE_[A-Z_]+)/', $match[0], $vars, PREG_SET_ORDER);
    $settings = array();
    foreach ($vars as $var) {
        $settings[$var[1]] = ($var[2] === 'XOBJ_DTYPE_ARRAY');
    }
    return $settings;
}

function readJson($file) {
    $decoded = json_decode((string) file_get_contents($file), true);
    if (!is_array($decoded)) {
        fail("$file isn't valid JSON: " . json_last_error_msg());
        return null;
    }
    return $decoded;
}

// What each type has, and what a theme can't set
$baseSettings = declaredSettings($classDir . '/screen.php', 'formulizeScreen');
$typeSettings = array();
foreach ($screenClasses as $type => list($file, $className)) {
    $typeSettings[$type] = $baseSettings + declaredSettings($classDir . '/' . $file, $className);
}
$excluded = array();
if (preg_match('/const\s+THEME_DEFAULTS_EXCLUDED\s*=\s*array\(([^)]*)\)/', (string) file_get_contents($classDir . '/screen.php'), $match)) {
    preg_match_all('/[\'"]([A-Za-z0-9_]+)[\'"]/', $match[1], $names);
    $excluded = $names[1];
} else {
    fail('Cannot find formulizeScreenHandler::THEME_DEFAULTS_EXCLUDED in class/screen.php');
}

// Each setting in a file is one the type has, a theme can set, and given the right kind of value
function checkSettings($label, $type, $settings, $typeSettings, $excluded) {
    if (!is_array($settings)) {
        fail("$label: $type isn't a set of settings");
        return;
    }
    foreach ($settings as $name => $value) {
        if (!isset($typeSettings[$type][$name])) {
            fail("$label: $type has no setting called $name");
        } elseif (in_array($name, $excluded, true)) {
            fail("$label: $type.$name comes from where the screen is made, and can't be set here");
        } elseif (is_array($value) !== $typeSettings[$type][$name]) {
            fail("$label: $type.$name should be " . ($typeSettings[$type][$name] ? 'a list' : 'a single value'));
        }
    }
}

$core = readJson($coreFile);
if ($core !== null) {
    foreach ($core as $type => $settings) {
        if (!isset($screenClasses[$type])) {
            fail("include/screen_defaults.json: $type isn't a type of screen that new screens are made as");
            continue;
        }
        checkSettings('include/screen_defaults.json', $type, $settings, $typeSettings, $excluded);
    }
    foreach (array_keys($screenClasses) as $type) {
        if (!isset($core[$type])) {
            fail("include/screen_defaults.json: has no settings for $type");
        }
    }
}

$themeFiles = glob($root . '/themes/*/screen-defaults.json');
$themesWithFiles = array();
foreach ($themeFiles as $file) {
    $theme = basename(dirname($file));
    $themesWithFiles[] = $theme;
    $label = "themes/$theme/screen-defaults.json";
    $themeDefaults = readJson($file);
    if ($themeDefaults === null OR $core === null) {
        continue;
    }
    foreach ($themeDefaults as $type => $settings) {
        if (!isset($screenClasses[$type])) {
            fail("$label: $type isn't a type of screen that new screens are made as");
            continue;
        }
        checkSettings($label, $type, $settings, $typeSettings, $excluded);
    }
    foreach ($core as $type => $settings) {
        if (!isset($themeDefaults[$type]) OR !is_array($themeDefaults[$type])) {
            fail("$label: has no settings for $type");
            continue;
        }
        foreach (array_keys($settings) as $name) {
            if (!array_key_exists($name, $themeDefaults[$type])) {
                fail("$label: $type.$name is missing. Every theme's file lists every setting in include/screen_defaults.json");
            }
        }
    }
}
foreach ($requiredThemes as $theme) {
    if (!in_array($theme, $themesWithFiles, true)) {
        fail("themes/$theme/screen-defaults.json is missing");
    }
}

if ($failures) {
    foreach ($failures as $message) {
        echo "FAIL: $message\n";
    }
    echo count($failures) . " problem(s) with the screen defaults\n";
    exit(1);
}
echo 'PASS: the screen defaults are complete in ' . count($themesWithFiles) . ' theme file(s): ' . implode(', ', $themesWithFiles) . "\n";
exit(0);
