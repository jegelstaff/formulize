<?php
/**
 * Server-side proxy for every AI provider the assistant supports.
 *
 * ai_proxy.php?provider=<claude|gemini|openai|ollama>&op=<models|chat|upload>
 *
 * All provider traffic goes through here so that an API key never reaches the browser.
 * That is essential once an administrator can supply one key for the whole site - the
 * people using it must not be able to read it - and it is worth doing for a person's own
 * key too, which used to be handed to the page in clear text.
 *
 * op=models   list the models available, normalized to {models:[{id,name}]}
 * op=chat     forward a chat turn; the body stays in the provider's native shape
 *
 * OpenAI's Files API, used for PDFs it will not take inline, stays in ai_upload.php,
 * which has its own multipart handling; it loads its key through the same helpers.
 *
 * When an administrator has pinned the provider and model, whatever the client asks for
 * is ignored in favour of those - the lock has to hold even for a request the assistant
 * did not make.
 *
 * Requests are accepted with either an application/json body, or multipart/form-data
 * where a 'payload' field holds the JSON and binary parts hold attachments. Multipart
 * keeps large attachments about a third smaller than base64-in-JSON would.
 */

include_once "../mainfile.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/aiadminconfig.php";
include_once XOOPS_ROOT_PATH . "/ai/ai_providers.php";
if (isset(icms::$logger)) {
    icms::$logger->disableLogger();
}
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');

/**
 * Fail in the shape the assistant already understands: {"error":{"message":...}}.
 */
function formulizeAI_proxyFail($code, $message) {
    $body = json_encode(array('error' => array('message' => $message)));
    http_response_code($code);
    header('Content-Length: ' . strlen($body));
    echo $body;
    exit();
}

if (!$xoopsUser) {
    formulizeAI_proxyFail(401, 'Not authenticated');
}

// op defaults by method so the shape of a request stays obvious
$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
$op = isset($_GET['op']) ? $_GET['op'] : ($isPost ? 'chat' : 'models');
if (!in_array($op, array('models', 'chat'))) {
    formulizeAI_proxyFail(400, 'Unknown operation');
}

// scope=system: the admin settings page (Settings -> AI) previewing which models a
// candidate site-wide key/provider actually has access to, while configuring it - which
// can happen before formulizeAIAssistantEnabled is even on, and regardless of whether
// this administrator is in formulizeAIAssistantGroups. Only meaningful for op=models; a
// chat turn always goes through the real, saved assistant configuration. Gate matches
// ai_keys.php's own scope=system (XOOPS_GROUP_ADMIN), not the assistant's enabled/group
// check, since this is the person setting that configuration up in the first place.
$systemScope = ($op === 'models' && isset($_GET['scope']) && $_GET['scope'] === 'system');

if ($systemScope) {
    if (!in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
        formulizeAI_proxyFail(403, 'Only webmasters can preview models for the site-wide key');
    }
} elseif (!isAIAssistantEnabled()) {
    formulizeAI_proxyFail(403, 'The AI assistant is not enabled for you');
}

$uid = (int)$xoopsUser->getVar('uid');
$adminConfig = formulizeAI_adminConfig();

// An administrator's choice of provider is not negotiable for the assistant itself.
// Ignoring the parameter rather than rejecting the request means a client that asks for
// something else simply gets the configured provider, instead of being handed a way to
// probe the configuration. In system scope there is nothing to lock against yet - the
// provider parameter IS the setting being configured - so it is always taken as given.
if (!$systemScope && $adminConfig['providerLocked']) {
    $provider = $adminConfig['provider'];
} else {
    $provider = isset($_GET['provider']) ? preg_replace('/[^a-z]/', '', $_GET['provider']) : 'claude';
}
$adapter = formulizeAI_providerAdapter($provider);
if (!$adapter) {
    formulizeAI_proxyFail(400, 'Unknown provider');
}

// The site's own Ollama address, which is deliberately never sent to the browser. In
// system scope the settings page may be previewing a not-yet-saved address, so a
// same-origin query param can override the stored one (never trusted for anything beyond
// this GET-only, admin-gated model listing).
$ollamaBaseUrl = ($systemScope && isset($_GET['baseUrl']) && $_GET['baseUrl'] !== '')
    ? trim($_GET['baseUrl'])
    : $adminConfig['ollamaBaseUrl'];

// --- the API key ---
//
// Ollama has none. Otherwise, in system scope it is always the site-wide key (that is the
// key being configured); elsewhere it is the site-wide key when an administrator chose
// the provider, and the person's own key when they did.
$apiKey = '';
if ($provider !== 'ollama') {
    if ($systemScope) {
        $apiKey = formulizeAI_loadKey(FORMULIZE_AI_SYSTEM_UID, $provider);
    } else {
        $apiKey = $adminConfig['providerLocked']
            ? formulizeAI_loadKey(FORMULIZE_AI_SYSTEM_UID, $provider)
            : formulizeAI_loadKey($uid, $provider);
    }

    // Before a key has ever been saved there is nothing to look up, and both the
    // assistant's own first-time setup and the admin settings page still need to list
    // models so the key being typed can be previewed before it is saved. Only then, and
    // only for that: a chat turn always uses a stored key, so a header can never be used
    // to drive the proxy with an arbitrary key.
    if (!$apiKey && $op === 'models' && ($systemScope || !$adminConfig['providerLocked'])
        && isset($_SERVER['HTTP_X_API_KEY'])) {
        $apiKey = trim($_SERVER['HTTP_X_API_KEY']);
    }

    if (!$apiKey) {
        formulizeAI_proxyFail(400, $systemScope
            ? 'Enter an API key to preview the available models.'
            : ($adminConfig['providerLocked']
                ? 'Your administrator has not finished setting up the AI assistant: no API key has been saved.'
                : 'No API key configured. Please save your settings first.'));
    }
}

// Nothing below this point needs the session, and everything below can take minutes on a
// slow model. PHP holds an exclusive lock on the session file until the request ends, so
// without this every other request from the same browser would block behind this one.
if (session_id()) {
    session_write_close();
}

// --------------------------------------------------------------------------
// op=models
// --------------------------------------------------------------------------

if ($op === 'models') {
    $endpoint = formulizeAI_resolveEndpoint($adapter['models'], $apiKey, '', $ollamaBaseUrl);
    // Listing models is a quick call whatever the provider; it does not need the long
    // wait a chat turn does.
    list($response, $httpCode, $curlError) = formulizeAI_proxyCurl($endpoint['url'], $endpoint['headers'], null, 30);

    if ($curlError) {
        formulizeAI_proxyFail(502, 'Could not reach the AI provider: ' . $curlError);
    }
    $data = json_decode($response, true);
    if ($httpCode >= 400 || !is_array($data)) {
        // Pass the provider's own complaint through - it is usually the useful one
        // (an invalid key, a disabled account) and the assistant displays it as-is.
        http_response_code($httpCode ?: 502);
        header('Content-Length: ' . strlen($response));
        echo $response;
        exit();
    }

    $body = json_encode(array('models' => call_user_func($adapter['normalize'], $data)));
    header('Content-Length: ' . strlen($body));
    echo $body;
    exit();
}

if (!$isPost) {
    formulizeAI_proxyFail(405, 'Method not allowed');
}

// --------------------------------------------------------------------------
// op=chat
// --------------------------------------------------------------------------

$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

if (strpos($contentType, 'multipart/form-data') !== false) {
    if (empty($_POST['payload'])) {
        formulizeAI_proxyFail(400, 'Missing payload field in multipart request');
    }
    // Decode WITHOUT the assoc flag so PHP preserves JSON objects as stdClass. With it,
    // an empty JSON object {} becomes an empty PHP array [] and re-encodes as [], which
    // breaks schema validation (e.g. a tool's input_schema.properties).
    $payload = json_decode($_POST['payload']);
    if (!$payload) {
        formulizeAI_proxyFail(400, 'Invalid payload JSON in multipart request');
    }

    $fileErrors = array();
    $payload = formulizeAI_expandFileRefs($payload, $adapter['expandFileRef'], $_FILES, $fileErrors);
    if ($fileErrors) {
        formulizeAI_proxyFail(413, implode(' ', array_unique($fileErrors)));
    }
} else {
    $raw = file_get_contents('php://input');
    if (!$raw) {
        formulizeAI_proxyFail(400, 'Empty request body');
    }
    $payload = json_decode($raw);
    if (!$payload) {
        formulizeAI_proxyFail(400, 'Invalid request body');
    }
}

// The model: an administrator's choice wins, otherwise whatever the client asked for,
// otherwise the provider's default.
$modelDefaults = formulizeAI_modelDefaults();
if ($adminConfig['providerLocked']) {
    $model = $adminConfig['model'];
} else {
    $model = isset($payload->model) ? (string)$payload->model : '';
}
if ($model === '') {
    $model = $modelDefaults[$provider];
}

// Gemini names the model in the URL rather than the body.
if ($provider === 'gemini') {
    unset($payload->model);
} else {
    $payload->model = $model;
}

$endpoint = formulizeAI_resolveEndpoint($adapter['chat'], $apiKey, $model, $ollamaBaseUrl);
list($response, $httpCode, $curlError) = formulizeAI_proxyCurl(
    $endpoint['url'],
    array_merge(array('Content-Type: application/json'), $endpoint['headers']),
    json_encode($payload),
    isset($adapter['timeout']) ? $adapter['timeout'] : 600
);

if ($curlError) {
    formulizeAI_proxyFail(502, 'Could not reach the AI provider: ' . $curlError);
}

http_response_code($httpCode);
header('Content-Length: ' . strlen($response));
echo $response;

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
