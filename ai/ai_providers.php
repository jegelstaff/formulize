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
        $models[] = array(
            'id' => $id,
            'name' => isset($m['displayName']) ? $m['displayName'] : $id,
        );
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
