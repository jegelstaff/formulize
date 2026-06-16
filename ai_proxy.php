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

include_once "mainfile.php";
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

// POST: forward chat completion request
$body = file_get_contents('php://input');
if (!$body) {
    $errBody = json_encode(['error' => ['message' => 'Empty request body']]);
    http_response_code(400);
    header('Content-Length: ' . strlen($errBody));
    echo $errBody;
    exit();
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
