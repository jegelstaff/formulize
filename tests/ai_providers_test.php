<?php
/**
 * Standalone test of what the AI assistant knows about its providers: that everything that
 * lists them agrees, and how the lists of models they send back are read.
 *
 * What it does NOT cover: talking to a provider. 03-validate-sequential/099 in the e2e suite
 * does that, with a stand-in for one.
 *
 * Usage:
 *   php tests/ai_providers_test.php
 *
 * @package Formulize
 * @subpackage tests
 */

// Command-line / CI only.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// aiadminconfig.php includes functions.php, which needs a booted system. So XOOPS_ROOT_PATH is
// a temporary folder with an empty one.
$repo = dirname(__DIR__);
$root = sys_get_temp_dir() . '/formulize_ai_providers_test_' . getmypid();
$include = $root . '/modules/formulize/include';
mkdir($include, 0700, true);
file_put_contents($include . '/functions.php', "<?php\n");
register_shutdown_function(function() use ($root, $include) {
    unlink($include . '/functions.php');
    rmdir($include);
    rmdir($root . '/modules/formulize');
    rmdir($root . '/modules');
    rmdir($root);
});
define('XOOPS_ROOT_PATH', $root);
require_once $repo . '/modules/formulize/include/aiadminconfig.php';
require_once $repo . '/ai/ai_providers.php';

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

$providers = formulizeAI_providers();
$keyed = array_values(array_filter($providers, 'formulizeAI_providerNeedsKey'));

// ---- everything that lists the providers ------------------------------------
echo "Everything that lists the providers agrees\n";
foreach ($providers as $provider) {
    check("$provider is a name the proxy and the keys table can hold", preg_match('/^[a-z]{1,20}$/', $provider), 1);
    $adapter = formulizeAI_providerAdapter($provider);
    check("$provider has an adapter", is_array($adapter), true);
    check("$provider's adapter can read its models", function_exists($adapter['normalize']), true);
    check("$provider sends a key only if it has one",
        strpos(implode(' ', $adapter['chat']['headers']), '{KEY}') !== false, formulizeAI_providerNeedsKey($provider));
}
check('a provider that does not exist has no adapter', formulizeAI_providerAdapter('nothing'), false);
check('a provider that does not exist has no key', formulizeAI_providerNeedsKey('nothing'), false);

/** The values in a piece of source, in quotes, between two other pieces of it. */
function quotedValues($file, $from, $to, $pattern) {
    $source = file_get_contents($file);
    $start = strpos($source, $from);
    $end = $start === false ? false : strpos($source, $to, $start);
    if ($end === false) {
        return "not found in $file";
    }
    preg_match_all($pattern, substr($source, $start, $end - $start), $matches);
    return $matches[1];
}

$choices = quotedValues($repo . '/modules/formulize/xoops_version.php',
    "'name' => 'formulizeAIProvider'", '),', "/=> '([a-z]+)'/");
check('the setting offers every provider', array_slice(array_diff($choices, array('select', 'text', 'userspecified')), 0),
    $providers);

check('the API key setting shows for every provider with a key',
    quotedValues($repo . '/modules/formulize/include/configsettings_registry.php',
        "// Ollama needs no key", ')),', "/'((?!name|formulizeAIProvider|value)[a-z]+)'/"),
    $keyed);

check('the assistant offers every provider', quotedValues($repo . '/ai/index.php',
    'id="provider-select"', '</select>', '/<option value="([a-z]+)"/'), $providers);

foreach (array('english', 'french') as $language) {
    $folder = $repo . "/modules/formulize/language/$language/";
    $words = file_get_contents($folder . 'modinfo.php') . file_get_contents($folder . 'ai_chat.php');
    $missing = array();
    foreach ($providers as $provider) {
        foreach (array('_MI_formulize_AIPROVIDER_', '_MD_FORMULIZE_AI_PROVIDER_') as $prefix) {
            if (!preg_match('/define\([\'"]' . $prefix . strtoupper($provider) . '[\'"]/', $words)) {
                $missing[] = $prefix . strtoupper($provider);
            }
        }
    }
    check("every provider has a name in $language", $missing, array());
}

// ---- lists of models --------------------------------------------------------
echo "Reading lists of models\n";
$openRouter = array('data' => array(
    array('id' => 'zeta/model', 'name' => 'Zeta: Model', 'context_length' => 200000,
        'supported_parameters' => array('temperature', 'tools', 'tool_choice')),
    array('id' => 'alpha/model-instruct', 'name' => 'alpha: Model Instruct', 'context_length' => 32768,
        'supported_parameters' => array('tools')),
    array('id' => 'beta/no-tools', 'name' => 'Beta: No Tools', 'context_length' => 32768,
        'supported_parameters' => array('temperature')),
    array('name' => 'No id'),
));
check('OpenRouter: only models with tools, by name, with their context windows in characters',
    formulizeAI_normalizeCompatibleModels($openRouter), array(
        array('id' => 'alpha/model-instruct', 'name' => 'alpha: Model Instruct', 'contextWindow' => 98304),
        array('id' => 'zeta/model', 'name' => 'Zeta: Model', 'contextWindow' => 600000),
    ));
check('Ollama: models with nothing but an id',
    formulizeAI_normalizeCompatibleModels(array('object' => 'list', 'data' => array(
        array('id' => 'qwen3.8:27b', 'object' => 'model', 'created' => 1, 'owned_by' => 'library'),
        array('id' => 'llama3.2:latest', 'object' => 'model', 'created' => 2, 'owned_by' => 'library'),
    ))), array(
        array('id' => 'llama3.2:latest', 'name' => 'llama3.2:latest'),
        array('id' => 'qwen3.8:27b', 'name' => 'qwen3.8:27b'),
    ));
check('no models', formulizeAI_normalizeCompatibleModels(array('object' => 'list', 'data' => array())), array());
check('no models, as null', formulizeAI_normalizeCompatibleModels(array('object' => 'list', 'data' => null)), array());
check('something else altogether', formulizeAI_normalizeCompatibleModels(array('error' => 'no')), array());

// ---- addresses --------------------------------------------------------------
echo "Addresses\n";
$ollama = formulizeAI_providerAdapter('ollama');
$endpoint = formulizeAI_resolveEndpoint($ollama['chat'], '', 'm', 'http://localhost:11434/');
check('Ollama\'s is the one in its setting', $endpoint,
    array('url' => 'http://localhost:11434/v1/chat/completions', 'headers' => array()));
foreach (array(
    'http://localhost:11434' => 'http://localhost:11434',
    ' http://localhost:11434/ ' => 'http://localhost:11434',
    'https://ai.example/v1' => 'https://ai.example',
    'https://ai.example/v1/' => 'https://ai.example',
    'https://ai.example/ollama/v1' => 'https://ai.example/ollama',
    'https://v1.example' => 'https://v1.example',
    'localhost:11434' => '',
    'file:///etc/passwd' => '',
    'http://' => '',
    '' => '',
) as $typed => $cleaned) {
    check("the address '$typed' is used as '$cleaned'", formulizeAI_cleanBaseUrl($typed), $cleaned);
}
$openRouterAdapter = formulizeAI_providerAdapter('openrouter');
$endpoint = formulizeAI_resolveEndpoint($openRouterAdapter['chat'], 'a-key', 'm', 'http://somewhere-else.example');
check('OpenRouter\'s is its own, with the key', $endpoint, array(
    'url' => 'https://openrouter.ai/api/v1/chat/completions', 'headers' => array('Authorization: Bearer a-key')));
$endpoint = formulizeAI_resolveEndpoint($openRouterAdapter['models'], 'a-key', '', 'http://somewhere-else.example');
check('OpenRouter is not sent the key for its list of models', $endpoint, array(
    'url' => 'https://openrouter.ai/api/v1/models', 'headers' => array()));

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
