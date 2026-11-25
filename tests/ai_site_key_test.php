<?php
/**
 * Standalone test of what happens to the site-wide AI API keys when the setting that holds
 * them, formulizeAIApiKey, is written.
 *
 * It runs the real icms_config_Item_Handler::insert(), with a stand-in for the database,
 * the way the two things that write the setting call it: saving the settings page, and the
 * module update, which deletes every setting and inserts it again.
 *
 * What it does NOT cover: the settings page and the module update themselves. That needs
 * an installed system.
 *
 * Usage:
 *   php tests/ai_site_key_test.php
 *
 * @package Formulize
 * @subpackage tests
 */

// Command-line / CI only.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

// The hook includes aiadminconfig.php from XOOPS_ROOT_PATH, and that includes functions.php,
// which needs a booted system. So XOOPS_ROOT_PATH is a temporary folder, with the real
// aiadminconfig.php and an empty functions.php.
$repo = dirname(__DIR__);
$root = sys_get_temp_dir() . '/formulize_ai_site_key_test_' . getmypid();
$include = $root . '/modules/formulize/include';
mkdir($include, 0700, true);
file_put_contents($include . '/functions.php', "<?php\n");
file_put_contents($include . '/aiadminconfig.php',
    "<?php\nrequire_once " . var_export($repo . '/modules/formulize/include/aiadminconfig.php', true) . ";\n");
register_shutdown_function(function() use ($root, $include) {
    unlink($include . '/functions.php');
    unlink($include . '/aiadminconfig.php');
    rmdir($include);
    rmdir($root . '/modules/formulize');
    rmdir($root . '/modules');
    rmdir($root);
});

define('XOOPS_ROOT_PATH', $root);
define('ICMS_ROOT_PATH', $root);
define('ICMS_CONF_CENSOR', 4);
define('XOOPS_DB_SALT', 'a-salt-for-this-test-only');

spl_autoload_register(function($class) use ($repo) {
    // the folders have lowercase names, even where the class name does not, ie: icms_config_Item_Handler
    $parts = explode('_', $class);
    $file = array_pop($parts);
    $path = $repo . '/libraries/' . strtolower(implode('/', $parts)) . '/' . $file . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

class fakeConfig {
    function getConfigsByCat($category) {
        return array('censor_enable' => false);
    }
}

class icms {
    public static $config;
}
icms::$config = new fakeConfig();

/** Holds rows of the config table, by conf_id, and keeps what is written to it. */
class fakeDatabase {
    public $rows = array();
    public $written = array();

    function prefix($table) {
        return 'test_' . $table;
    }
    function quoteString($string) {
        return "'" . addslashes($string) . "'";
    }
    function genId($sequence) {
        return 0;
    }
    function getInsertId() {
        return 99;
    }
    function query($sql) {
        if (preg_match("/SELECT conf_value FROM test_config WHERE conf_id = '(\d+)'/", $sql, $match)) {
            return array(isset($this->rows[$match[1]]) ? $this->rows[$match[1]] : null);
        }
        $this->written[] = $sql;
        return true;
    }
    function queryF($sql) {
        return $this->query($sql);
    }
    function fetchArray($result) {
        return $result[0] === null ? false : array('conf_value' => $result[0]);
    }
}

$db = new fakeDatabase();
$handler = (new ReflectionClass('icms_config_Item_Handler'))->newInstanceWithoutConstructor();
$handlerClass = new ReflectionClass($handler);
while (!$handlerClass->hasProperty('db')) {
    $handlerClass = $handlerClass->getParentClass();
}
$property = $handlerClass->getProperty('db');
if (PHP_VERSION_ID < 80100) {
    $property->setAccessible(true);
}
$property->setValue($handler, $db);

require_once XOOPS_ROOT_PATH . '/modules/formulize/include/aiadminconfig.php';

// ---- helpers ----------------------------------------------------------------
$passed = 0;
$failed = 0;
function check($label, $got, $want) {
    global $passed, $failed;
    $ok = ($got === $want);
    $ok ? $passed++ : $failed++;
    printf("  [%s] %s\n", $ok ? 'PASS' : 'FAIL', $label);
    if (!$ok) {
        printf("         got  %s\n         want %s\n", var_export($got, true), var_export($want, true));
    }
}

/** The setting, as a new item or as the one with this id, holding this value. */
function setting($value, $id = 0) {
    $config = new icms_config_Item_Object();
    if ($id) {
        $config->assignVar('conf_id', $id);
    } else {
        $config->setNew();
    }
    $config->setVar('conf_modid', 5);
    $config->setVar('conf_catid', 0);
    $config->setVar('conf_name', 'formulizeAIApiKey');
    $config->setVar('conf_title', '_MI_formulize_AIAPIKEY');
    $config->setVar('conf_desc', '_MI_formulize_AIAPIKEY_DESC');
    $config->setVar('conf_formtype', 'aikey');
    $config->setVar('conf_valuetype', 'text');
    $config->setVar('conf_order', 1);
    $config->setVar('conf_value', $value, true);
    return $config;
}

/** The keys in what was last written to the database, decrypted, by provider. */
function writtenKeys() {
    global $db;
    $sql = stripslashes((string) end($db->written));
    if (!preg_match('/(\{"[a-z]+":.*?\}|\[\])/s', $sql, $match)) {
        return 'no keys found in: ' . $sql;
    }
    $keys = array();
    foreach ((array) json_decode($match[1], true) as $provider => $encrypted) {
        $keys[$provider] = formulizeAI_decryptKey($encrypted);
    }
    return $keys;
}

/** The setting's fields on the settings page, which reach insert() as globals. */
function posted($fields) {
    foreach (array('formulizeAIApiKey_new', 'formulizeAIApiKey_clear', 'formulizeAIProvider') as $name) {
        unset($GLOBALS[$name]);
        if (isset($fields[$name])) {
            $GLOBALS[$name] = $fields[$name];
        }
    }
}

function encryptedKeys($keys) {
    return json_encode(array_map('formulizeAI_encryptKey', $keys));
}

$twoKeys = array('claude' => 'claude-test-key', 'openai' => 'openai-test-key');

// ---- the settings page ------------------------------------------------------
echo "Saving the settings page\n";
$db->rows = array(7 => encryptedKeys($twoKeys));

posted(array('formulizeAIApiKey_new' => '', 'formulizeAIProvider' => 'claude'));
$handler->insert(setting('touch', 7));
check('typing nothing keeps every key', writtenKeys(), $twoKeys);

posted(array('formulizeAIApiKey_new' => 'new-key', 'formulizeAIProvider' => 'claude'));
$handler->insert(setting('touch', 7));
check('typing a key replaces that provider\'s and keeps the others', writtenKeys(),
    array('claude' => 'new-key', 'openai' => 'openai-test-key'));

posted(array('formulizeAIApiKey_clear' => 'on', 'formulizeAIProvider' => 'claude'));
$handler->insert(setting('touch', 7));
check('removing a key removes only that provider\'s', writtenKeys(), array('openai' => 'openai-test-key'));

// ---- the module update ------------------------------------------------------
echo "The module update\n";
posted(array());

// More than one, because the encrypted keys are different every time, and some of them
// have characters in them that JSON and the database escape.
$kept = 0;
for ($i = 0; $i < 50; $i++) {
    $db->rows = array(); // the update has deleted the row
    $handler->insert(setting(encryptedKeys($twoKeys)));
    $kept += (writtenKeys() === $twoKeys) ? 1 : 0;
}
check('inserting the setting again keeps every key', $kept, 50);

$handler->insert(setting(''));
check('a setting that never had a key starts with none', writtenKeys(), array());

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
