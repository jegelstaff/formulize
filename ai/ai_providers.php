<?php
/**
 * Per-provider request translation for the AI Assistant proxy.
 *
 * Every provider the assistant supports is reached the same way - the browser talks only
 * to ai_proxy.php, and the proxy talks to the provider - so that an API key never has to
 * exist in the browser. That matters most when an administrator supplies the key for the
 * whole site, since the people using it must not be able to read it, but it is equally
 * true of a person's own key.
 *
 * Each adapter answers three questions for its provider:
 *   modelsRequest()   where to ask for the list of models, and how to authenticate
 *   normalizeModels() how to turn that answer into a uniform array of {id, name}
 *   chatRequest()     where to send a chat turn, and how to authenticate
 *
 * Chat request bodies stay in each provider's own native shape and are passed through
 * untouched, apart from forcing the model when an administrator has pinned it and
 * expanding uploaded file references. The proxy is a courier, not a translator.
 *
 * @package Formulize
 * @subpackage AI
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

/**
 * The adapter for a provider.
 *
 * @param string $provider One of formulizeAI_providers()
 * @return array|false Adapter definition, or false for an unknown provider
 */
function formulizeAI_providerAdapter($provider) {
    $adapters = array(

        'claude' => array(
            'models' => array(
                'url' => 'https://api.anthropic.com/v1/models?limit=100',
                'headers' => array('x-api-key: {KEY}', 'anthropic-version: 2023-06-01'),
            ),
            'chat' => array(
                'url' => 'https://api.anthropic.com/v1/messages',
                'headers' => array('x-api-key: {KEY}', 'anthropic-version: 2023-06-01'),
            ),
            'normalize' => 'formulizeAI_normalizeClaudeModels',
            // Claude carries files as a content block's "source" object
            'expandFileRef' => 'formulizeAI_expandFileRefClaude',
            'timeout' => 600,
        ),

        'gemini' => array(
            'models' => array(
                // The key goes in a header, never the query string: a URL lands in browser
                // history, proxy logs and error reports.
                'url' => 'https://generativelanguage.googleapis.com/v1beta/models',
                'headers' => array('x-goog-api-key: {KEY}'),
            ),
            'chat' => array(
                'url' => 'https://generativelanguage.googleapis.com/v1beta/models/{MODEL}:generateContent',
                'headers' => array('x-goog-api-key: {KEY}'),
            ),
            'normalize' => 'formulizeAI_normalizeGeminiModels',
            'expandFileRef' => 'formulizeAI_expandFileRefGemini',
            'timeout' => 600,
        ),

        'openai' => array(
            'models' => array(
                'url' => 'https://api.openai.com/v1/models',
                'headers' => array('Authorization: Bearer {KEY}'),
            ),
            'chat' => array(
                'url' => 'https://api.openai.com/v1/chat/completions',
                'headers' => array('Authorization: Bearer {KEY}'),
            ),
            'normalize' => 'formulizeAI_normalizeOpenAIModels',
            // OpenAI takes images as data URLs and PDFs through its Files API
            // (see ai_upload.php), so there is no multipart file-ref shape for it.
            'expandFileRef' => '',
            'timeout' => 600,
        ),

        'ollama' => array(
            'models' => array(
                'url' => '{BASE}/api/tags',
                'headers' => array(),
            ),
            'chat' => array(
                'url' => '{BASE}/v1/chat/completions',
                'headers' => array(),
            ),
            'normalize' => 'formulizeAI_normalizeOllamaModels',
            'expandFileRef' => '',
            // Ollama is the one provider with no ceiling of its own - it is somebody's own
            // machine, and a large model on CPU can take a very long time. There is no way
            // to tell that apart from a dead connection, so this is set high enough that it
            // should never interrupt real work, and exists only so a hung socket is
            // eventually let go rather than held until TCP keepalive notices, two hours on.
            'timeout' => 1800,
        ),
    );

    return isset($adapters[$provider]) ? $adapters[$provider] : false;
}

/**
 * Fill the placeholders in an adapter's url/headers.
 *
 * @param array $spec An adapter's 'models' or 'chat' definition
 * @param string $key The API key ('' for keyless providers)
 * @param string $model The model name, for providers that put it in the URL
 * @param string $baseUrl The Ollama base URL
 * @return array array('url' => ..., 'headers' => array(...))
 */
function formulizeAI_resolveEndpoint($spec, $key, $model = '', $baseUrl = '') {
    $replace = array(
        '{MODEL}' => rawurlencode($model),
        '{BASE}' => rtrim($baseUrl, '/'),
    );
    $url = strtr($spec['url'], $replace);

    $headers = array();
    foreach ($spec['headers'] as $header) {
        // A keyless provider gets no auth header at all rather than an empty one
        if (strpos($header, '{KEY}') !== false && $key === '') {
            continue;
        }
        $headers[] = str_replace('{KEY}', $key, $header);
    }
    return array('url' => $url, 'headers' => $headers);
}

// --------------------------------------------------------------------------
// Model list normalization
//
// Each provider describes its models differently. Normalizing here rather than in the
// browser means the assistant has one code path for model discovery, and means these
// rules live next to the endpoints they belong to.
// --------------------------------------------------------------------------

function formulizeAI_normalizeClaudeModels($data) {
    $models = array();
    foreach ((isset($data['data']) ? $data['data'] : array()) as $m) {
        if (!isset($m['id'])) {
            continue;
        }
        $models[] = array(
            'id' => $m['id'],
            'name' => isset($m['display_name']) ? $m['display_name'] : $m['id'],
        );
    }
    return $models;
}

function formulizeAI_normalizeGeminiModels($data) {
    $models = array();
    foreach ((isset($data['models']) ? $data['models'] : array()) as $m) {
        if (!isset($m['name'])) {
            continue;
        }
        // Only models that can actually hold a conversation
        $methods = isset($m['supportedGenerationMethods']) ? $m['supportedGenerationMethods'] : array();
        if (!in_array('generateContent', $methods)) {
            continue;
        }
        $id = preg_replace('/^models\//', '', $m['name']);
        $model = array(
            'id' => $id,
            'name' => isset($m['displayName']) ? $m['displayName'] : $id,
        );
        // Gemini is the one provider whose models endpoint reports an actual context size
        // (in tokens, via inputTokenLimit). Everywhere else in the assistant works in
        // characters (see formulizeAI_contextWindowDefaults), so convert with the same
        // ~3 chars/token approximation used for every provider's hand-set default.
        if (isset($m['inputTokenLimit']) && is_numeric($m['inputTokenLimit'])) {
            $model['contextWindow'] = (int) $m['inputTokenLimit'] * 3;
        }
        $models[] = $model;
    }
    return $models;
}

function formulizeAI_normalizeOpenAIModels($data) {
    $models = array();
    foreach ((isset($data['data']) ? $data['data'] : array()) as $m) {
        if (!isset($m['id'])) {
            continue;
        }
        // Drop the models that cannot be used for chat, so the list stays readable
        if (preg_match('/(embedding|whisper|tts|dall|moderation|instruct|audio|realtime)/i', $m['id'])) {
            continue;
        }
        $models[] = array('id' => $m['id'], 'name' => $m['id'], 'created' => isset($m['created']) ? $m['created'] : 0);
    }
    // Newest first, which is nearly always the one wanted
    usort($models, function($a, $b) {
        return $b['created'] - $a['created'];
    });
    foreach ($models as &$m) {
        unset($m['created']);
    }
    return $models;
}

function formulizeAI_normalizeOllamaModels($data) {
    $models = array();
    foreach ((isset($data['models']) ? $data['models'] : array()) as $m) {
        if (!isset($m['name'])) {
            continue;
        }
        $models[] = array('id' => $m['name'], 'name' => $m['name']);
    }
    return $models;
}

// --------------------------------------------------------------------------
// Uploaded file expansion
//
// Attachments are sent to the proxy as binary multipart parts rather than base64 inside
// the JSON body, because base64 is about a third larger and large attachments otherwise
// run into upload_max_filesize / post_max_size / LimitRequestBody. The body carries a
// placeholder naming the multipart part; these functions put the real content back in
// the shape the provider expects, just before it is forwarded.
// --------------------------------------------------------------------------

/**
 * Claude: a document/image block's "source" object.
 */
function formulizeAI_expandFileRefClaude($mediaType, $base64) {
    $source = new stdClass();
    $source->type = 'base64';
    $source->media_type = $mediaType;
    $source->data = $base64;
    return $source;
}

/**
 * Gemini: an "inlineData" part.
 */
function formulizeAI_expandFileRefGemini($mediaType, $base64) {
    $inline = new stdClass();
    $inline->mimeType = $mediaType;
    $inline->data = $base64;
    return $inline;
}

/**
 * Walk a decoded request body and replace every file-reference placeholder with the
 * uploaded content, in the shape the provider expects.
 *
 * A placeholder is any object carrying a 'file_ref' property (the name of the multipart
 * part) alongside a 'media_type'. Recursing for it, rather than looking in the one place
 * Claude happens to put it, means a provider that nests attachments differently needs no
 * change here.
 *
 * Objects are modified in place through the reference, which is why $payload must be
 * decoded WITHOUT the associative flag - see the note in ai_proxy.php about empty JSON
 * objects.
 *
 * @param mixed $node The decoded body, or any part of it
 * @param callable|string $expander The provider's expandFileRef function
 * @param array $files $_FILES
 * @param array &$errors Collects human-readable problems (e.g. a file too large)
 * @return mixed The node, with placeholders replaced
 */
function formulizeAI_expandFileRefs($node, $expander, $files, &$errors) {
    if (is_array($node)) {
        foreach ($node as $k => $child) {
            $node[$k] = formulizeAI_expandFileRefs($child, $expander, $files, $errors);
        }
        return $node;
    }
    if (!is_object($node)) {
        return $node;
    }

    foreach (get_object_vars($node) as $prop => $child) {
        if (is_object($child) && isset($child->file_ref)) {
            $ref = $child->file_ref;
            $mediaType = isset($child->media_type) ? $child->media_type : 'application/octet-stream';

            if (!isset($files[$ref])) {
                $errors[] = 'An attachment was referenced but not received.';
                continue;
            }
            if ($files[$ref]['error'] === UPLOAD_ERR_INI_SIZE) {
                $errors[] = 'The attached file exceeds the server\'s upload limit. '
                    . 'Ask your admin to increase upload_max_filesize in PHP config.';
                continue;
            }
            if ($files[$ref]['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'An attachment failed to upload.';
                continue;
            }
            if (!$expander || !function_exists($expander)) {
                $errors[] = 'This provider does not accept file attachments this way.';
                continue;
            }
            $node->$prop = call_user_func(
                $expander,
                $mediaType,
                base64_encode(file_get_contents($files[$ref]['tmp_name']))
            );
            continue;
        }
        $node->$prop = formulizeAI_expandFileRefs($child, $expander, $files, $errors);
    }
    return $node;
}

/**
 * One place for every outbound call, so timeouts and error handling cannot drift apart
 * between providers.
 *
 * Nothing here streams: every provider is asked for a complete response and we wait for
 * the whole body. So CURLOPT_TIMEOUT is the right bound - it caps the total wait, which
 * is exactly the thing worth capping. (A streaming transport would need LOW_SPEED_LIMIT
 * and LOW_SPEED_TIME instead, since a slow stream is alive rather than stuck.)
 *
 * CURLOPT_CONNECTTIMEOUT covers only TCP and TLS setup, so it never cuts into the time
 * the model spends thinking.
 *
 * There is no way to tell "the model is still thinking" from "the connection is dead":
 * in a non-streaming request no bytes flow in either case, so any bound is a policy
 * rather than a detection. Two things make the choice easy anyway:
 *
 *   - The browser gives up first and shows its own message, and aborting a fetch does
 *     not stop this request. So by the time this timeout matters the person has already
 *     moved on; it is not cutting anybody's answer short, only reclaiming the worker.
 *   - The hosted providers have ceilings of their own (Anthropic ends non-streaming
 *     requests around ten minutes and tells you to stream past that), so for those the
 *     value below is nearly unreachable.
 *
 * Previously there was no bound at all: cURL waits forever by default, and PHP's
 * max_execution_time does not count time spent inside an external call, so a black-holed
 * connection held a worker until TCP keepalive gave up - two hours, by default.
 *
 * @param string $url
 * @param array $headers
 * @param string|null $body POST body, or null for a GET
 * @param int $timeout Seconds to wait for the complete response
 * @return array array($responseBody, $httpCode, $curlErrorString)
 */
function formulizeAI_proxyCurl($url, $headers, $body = null, $timeout = 300) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return array($response, $httpCode, $error);
}

// --------------------------------------------------------------------------
// Server-side send/receive
//
// The embedded assistant builds its requests in the browser and the proxy passes them
// through. Server-side features (automations, so far) need the same providers without a
// browser: formulizeAI_complete() is one complete, non-streaming exchange - a prompt in,
// text out - with the site-wide provider, model and key from Settings -> AI.
// --------------------------------------------------------------------------

/**
 * A failure talking to an AI provider. isRetryable() says whether trying again later
 * might work (the provider was busy, rate limited, or unreachable), as opposed to a
 * problem with the configuration or the request that will fail the same way again.
 */
class FormulizeAIException extends Exception {
    private $retryable;
    public function __construct($message, $retryable = false) {
        parent::__construct($message);
        $this->retryable = $retryable;
    }
    public function isRetryable() {
        return $this->retryable;
    }
}

/**
 * Send a prompt to the site's AI model and get back its reply, from server-side code.
 *
 * Uses the site-wide provider, model and key from Settings -> AI. A provider left as
 * "user specified" has no site-wide key, so it can't be used here.
 *
 * @param string $system Instructions for the model (the system prompt), or ''
 * @param string $prompt The message to send
 * @param array $options Optional: 'max_tokens' (default 2048), 'json' (true to ask for a JSON reply), 'timeout' (seconds)
 * @return array 'text' => the reply, 'provider', 'model', 'stop_reason', 'usage' (provider's own usage data, if any)
 * @throws FormulizeAIException
 */
function formulizeAI_complete($system, $prompt, $options = array()) {
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/aiadminconfig.php";
    $config = formulizeAI_adminConfig();
    if (!$config['providerLocked']) {
        throw new FormulizeAIException('No site-wide AI provider is chosen. In Settings, AI, choose a provider (not "user specified"), and save its API key.');
    }
    $provider = $config['provider'];
    $model = $config['model'];
    $adapter = formulizeAI_providerAdapter($provider);
    if (!$adapter) {
        throw new FormulizeAIException("Unknown AI provider '$provider'.");
    }
    $apiKey = '';
    if ($provider !== 'ollama') {
        $apiKey = formulizeAI_loadKey(FORMULIZE_AI_SYSTEM_UID, $provider);
        if (!$apiKey) {
            throw new FormulizeAIException('No site-wide API key is saved for the AI provider. Add one in Settings, AI.');
        }
    }
    $maxTokens = isset($options['max_tokens']) ? max(16, intval($options['max_tokens'])) : 2048;
    $json = !empty($options['json']);
    if ($json) {
        $system = trim($system . "\n\nRespond with a single JSON object only, with no other text before or after it.");
    }

    switch ($provider) {
        case 'claude':
            $body = array('model' => $model, 'max_tokens' => $maxTokens, 'messages' => array(array('role' => 'user', 'content' => $prompt)));
            if ($system !== '') {
                $body['system'] = $system;
            }
            break;
        case 'gemini':
            $body = array(
                'contents' => array(array('role' => 'user', 'parts' => array(array('text' => $prompt)))),
                'generationConfig' => array('maxOutputTokens' => $maxTokens),
            );
            if ($system !== '') {
                $body['systemInstruction'] = array('parts' => array(array('text' => $system)));
            }
            if ($json) {
                $body['generationConfig']['responseMimeType'] = 'application/json';
            }
            break;
        default: // openai, and ollama's OpenAI-compatible endpoint
            $messages = array();
            if ($system !== '') {
                $messages[] = array('role' => 'system', 'content' => $system);
            }
            $messages[] = array('role' => 'user', 'content' => $prompt);
            $body = array('model' => $model, 'messages' => $messages);
            // OpenAI's current models take max_completion_tokens and reject max_tokens; Ollama takes max_tokens
            $body[$provider === 'openai' ? 'max_completion_tokens' : 'max_tokens'] = $maxTokens;
            if ($json) {
                $body['response_format'] = array('type' => 'json_object');
            }
    }

    $endpoint = formulizeAI_resolveEndpoint($adapter['chat'], $apiKey, $model, $config['ollamaBaseUrl']);
    $timeout = isset($options['timeout']) ? intval($options['timeout']) : min(300, isset($adapter['timeout']) ? $adapter['timeout'] : 300);
    list($response, $httpCode, $curlError) = formulizeAI_proxyCurl(
        $endpoint['url'],
        array_merge(array('Content-Type: application/json'), $endpoint['headers']),
        json_encode($body),
        $timeout
    );
    if ($curlError) {
        throw new FormulizeAIException('Could not reach the AI provider: ' . $curlError, true);
    }
    $data = json_decode(strval($response), true);
    if ($httpCode >= 400 OR !is_array($data)) {
        $message = is_array($data) ? ($data['error']['message'] ?? ($data['error'] ?? '')) : '';
        $message = is_string($message) && $message !== '' ? $message : substr(strval($response), 0, 300);
        // busy, rate limited, overloaded or a server error: worth trying again later
        $retryable = ($httpCode == 408 OR $httpCode == 429 OR $httpCode >= 500 OR $httpCode == 0);
        throw new FormulizeAIException("The AI provider returned an error (HTTP $httpCode): $message", $retryable);
    }

    $text = '';
    $stopReason = '';
    switch ($provider) {
        case 'claude':
            foreach ((array) ($data['content'] ?? array()) as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $text .= $block['text'];
                }
            }
            $stopReason = $data['stop_reason'] ?? '';
            break;
        case 'gemini':
            foreach ((array) ($data['candidates'][0]['content']['parts'] ?? array()) as $part) {
                $text .= $part['text'] ?? '';
            }
            $stopReason = $data['candidates'][0]['finishReason'] ?? '';
            break;
        default:
            $text = strval($data['choices'][0]['message']['content'] ?? '');
            $stopReason = $data['choices'][0]['finish_reason'] ?? '';
    }
    return array(
        'text' => $text,
        'provider' => $provider,
        'model' => $model,
        'stop_reason' => $stopReason,
        'usage' => $data['usage'] ?? ($data['usageMetadata'] ?? array()),
    );
}
