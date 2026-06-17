<?php
/**
 * Proxy for Anthropic Claude API calls.
 *
 * GET  → forwards to /v1/models  (model discovery)
 * POST → forwards to /v1/messages (chat completion)
 *
 * The API key is loaded server-side from the formulize_ai_keys table.
 * An X-API-Key request header is accepted as a fallback only during initial
 * model discovery, before the user has saved their key for the first time.
 * After first save, the key never travels from browser to server again.
 */

include_once "../mainfile.php";
if (isset(icms::$logger)) {
    icms::$logger->disableLogger();
}
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');

if (!$xoopsUser) {
    http_response_code(401);
    echo json_encode(['error' => ['message' => 'Not authenticated']]);
    exit();
}

// Load Claude key from DB; fall back to X-API-Key header for initial model
// discovery before the user has saved their key for the first time.
$apiKey = '';
if (defined('XOOPS_DB_SALT') && XOOPS_DB_SALT) {
    $uid    = (int)$xoopsUser->getVar('uid');
    $table  = $xoopsDB->prefix('formulize_ai_keys');
    $result = @$xoopsDB->query("SELECT encrypted_key FROM $table WHERE uid = $uid AND provider = 'claude'");
    if ($result && ($row = $xoopsDB->fetchArray($result))) {
        $raw = base64_decode($row['encrypted_key']);
        if (strlen($raw) >= 17) {
            $dec = openssl_decrypt(substr($raw, 16), 'AES-256-CBC', hash('sha256', XOOPS_DB_SALT, true), 0, substr($raw, 0, 16));
            if ($dec !== false) $apiKey = $dec;
        }
    }
}
if (!$apiKey) {
    $apiKey = isset($_SERVER['HTTP_X_API_KEY']) ? trim($_SERVER['HTTP_X_API_KEY']) : '';
}

if (!$apiKey) {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'No Claude API key configured. Please save your settings first.']]);
    exit();
}

$anthropicHeaders = [
    'x-api-key: ' . $apiKey,
    'anthropic-version: 2023-06-01',
];

// GET: list available Claude models
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $ch = curl_init('https://api.anthropic.com/v1/models?limit=100');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $anthropicHeaders);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        $errBody = json_encode(['error' => ['message' => 'Proxy curl error: ' . $curlError]]);
        http_response_code(502);
        header('Content-Length: ' . strlen($errBody));
        echo $errBody;
        exit();
    }

    http_response_code($httpCode);
    header('Content-Length: ' . strlen($response));
    echo $response;
    exit();
}

// POST: forward chat completion request.
// Supports two request shapes:
//   1. application/json  — full payload as JSON (no file attachments)
//   2. multipart/form-data — 'payload' field contains JSON with file_ref placeholders;
//      binary file fields (file_0, file_1, …) are re-encoded to base64 here before
//      forwarding to Anthropic, reducing the browser→server transfer by ~33 %.
$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

if (strpos($contentType, 'multipart/form-data') !== false) {
    if (empty($_POST['payload'])) {
        $errBody = json_encode(['error' => ['message' => 'Missing payload field in multipart request']]);
        http_response_code(400);
        header('Content-Length: ' . strlen($errBody));
        echo $errBody;
        exit();
    }
    // Decode WITHOUT the assoc flag so PHP preserves JSON objects as stdClass.
    // With true, empty JSON objects {} become empty PHP arrays [] and then
    // re-encode as [], breaking Claude's schema validation (e.g. input_schema.properties).
    $payload = json_decode($_POST['payload']);
    if (!$payload) {
        $errBody = json_encode(['error' => ['message' => 'Invalid payload JSON in multipart request']]);
        http_response_code(400);
        header('Content-Length: ' . strlen($errBody));
        echo $errBody;
        exit();
    }

    // Replace file_ref placeholders with base64-encoded file content.
    // $payload->messages is a PHP array (JSON arrays stay as arrays even without assoc flag).
    // Individual message and block objects are stdClass — object properties are modified in-place.
    foreach ($payload->messages as $msg) {
        if (!is_array($msg->content)) continue;
        foreach ($msg->content as $block) {
            if (!isset($block->source->type) || $block->source->type !== 'file_ref') continue;
            $ref      = $block->source->ref;
            $mimeType = $block->source->media_type;
            if (!isset($_FILES[$ref])) continue;
            if ($_FILES[$ref]['error'] === UPLOAD_ERR_INI_SIZE) {
                $errBody = json_encode(['error' => ['message' =>
                    'The attached file exceeds the server\'s upload limit. '
                    . 'Ask your admin to increase upload_max_filesize in PHP config.']]);
                http_response_code(413);
                header('Content-Length: ' . strlen($errBody));
                echo $errBody;
                exit();
            }
            if ($_FILES[$ref]['error'] !== UPLOAD_ERR_OK) continue;
            $src             = new stdClass();
            $src->type       = 'base64';
            $src->media_type = $mimeType;
            $src->data       = base64_encode(file_get_contents($_FILES[$ref]['tmp_name']));
            $block->source   = $src;
        }
    }

    $body = json_encode($payload);
} else {
    $body = file_get_contents('php://input');
    if (!$body) {
        $errBody = json_encode(['error' => ['message' => 'Empty request body']]);
        http_response_code(400);
        header('Content-Length: ' . strlen($errBody));
        echo $errBody;
        exit();
    }
}

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
    ['Content-Type: application/json'],
    $anthropicHeaders
));

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    $errBody = json_encode(['error' => ['message' => 'Proxy curl error: ' . $curlError]]);
    http_response_code(502);
    header('Content-Length: ' . strlen($errBody));
    echo $errBody;
    exit();
}

http_response_code($httpCode);
header('Content-Length: ' . strlen($response));
echo $response;
