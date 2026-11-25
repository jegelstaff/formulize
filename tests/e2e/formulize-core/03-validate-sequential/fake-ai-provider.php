<?php
/**
 * A stand-in for an OpenAI-compatible AI provider, for 099-ai-openai-compatible-provider.spec.js.
 *
 * The spec runs it in the web container with PHP's own web server, as the router script:
 *   php -S 127.0.0.1:11499 fake-ai-provider.php
 * It answers the way such a provider does, according to what the last thing in the conversation says.
 */

if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

header('Content-Type: application/json');

function answer($body) {
    echo json_encode($body);
    exit;
}

function reply($content, $finishReason = 'stop', $toolCalls = null) {
    $message = array('role' => 'assistant', 'content' => $content);
    if ($toolCalls) {
        $message['tool_calls'] = $toolCalls;
    }
    answer(array(
        'id' => 'fake',
        'object' => 'chat.completion',
        'choices' => array(array('index' => 0, 'message' => $message, 'finish_reason' => $finishReason)),
    ));
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/v1/models') {
    answer(array('object' => 'list', 'data' => array(
        array('id' => 'fake-model-instruct', 'object' => 'model', 'context_length' => 50000),
        array('id' => 'another-fake-model', 'object' => 'model'),
        array('id' => 'fake-model-without-tools', 'object' => 'model', 'supported_parameters' => array('temperature')),
    )));
}

if ($path !== '/v1/chat/completions') {
    http_response_code(404);
    answer(array('error' => array('message' => "There is nothing at $path")));
}

$request = json_decode(file_get_contents('php://input'), true);
$last = end($request['messages']);

if ($last['role'] === 'tool') {
    reply('The tool said: ' . substr($last['content'], 0, 2000));
}
if (strpos($last['content'], 'Use a tool') !== false) {
    // with a finish_reason of 'stop', as some providers have it
    reply('', 'stop', array(array(
        'id' => 'call_1',
        'type' => 'function',
        'function' => array('name' => 'test_connection', 'arguments' => '{}'),
    )));
}
if (strpos($last['content'], 'Fail') !== false) {
    // with a status of 200, as OpenRouter has it once it has started to answer
    answer(array('error' => array('code' => 502, 'message' => 'The model is not available right now')));
}
if (strpos($last['content'], 'Say nothing') !== false) {
    answer(array('id' => 'fake', 'object' => 'chat.completion', 'choices' => array()));
}
$key = isset($_SERVER['HTTP_AUTHORIZATION']) ? 'a key' : 'no key';
reply("I am {$request['model']}, with " . count($request['tools'] ?? array()) . " tools and $key.");
